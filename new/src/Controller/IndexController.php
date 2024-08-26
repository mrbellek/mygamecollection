<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * TODO:
 * . get scraper command working in browser - https://github.com/CoreSphere/ConsoleBundle
 * - proper graphs for top stats instead of buttons
 * - column sorting
 * - reponsive design for mobile
 * - shortlist?
 */

use App\Entity\Game;
use App\Entity\GameCollection;
use App\Enum\Platform as PlatformEnum;
use App\Exception\InvalidFilterException;
use App\Repository\GameRepository;
use App\Service\GameFilterService;
use App\Service\GameScraperService;
use App\Service\GameStatsService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use RuntimeException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IndexController extends AbstractController
{
    private const DEFAULT_PAGE_SIZE = 25;

    public function __construct(
        private readonly GameStatsService $gameStatsService,
        private readonly GameFilterService $gameFilterService,
    ) {}
    
    public function renderListWithResults(
        Request $request,
        GameCollection $games,
        string $filter = 'all',
        int $pageNum = 1,
        string $search = '',
    ): Response {

        $request->getSession()->set('pageNum', $pageNum);
        $request->getSession()->set('filter', $filter);
        $request->getSession()->set('search', $search);

        $pageSize = intval($this->getParameter('app.page_size') ?? self::DEFAULT_PAGE_SIZE);
        $adapter = new ArrayAdapter($games->toArray());
        try {
            $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage($adapter, $pageNum, $pageSize);
        } catch (OutOfRangeCurrentPageException) {
            //@TODO redirect to page 1 instead of sneakily rendering page 1
            $pageNum = 1;
            $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage($adapter, $pageNum, $pageSize);
        }
    
        return $this->render('list.html.twig', [
            'games' => $pagerfanta,
            'count' => $games->count(),
            'stats' => $this->gameStatsService->getStats($games),
            'page' => $pageNum,
            'filter' => $filter,
            'search' => $search,
            'paginateSlug' => strlen($search) > 0 ? sprintf('%s/%s', $filter, $search) : $filter,
        ]);
    }

    #[Route("/", name: "index")]
    public function index(Request $request): Response
    {
        return $this->gameFilter($request, 'all', 1);
    }

    #[Route("/search/{term}/{page}", name: "search", requirements: ['page' => '\d+'])]
    public function searchFilter(GameRepository $gameRepository, Request $request, string $term, int $page = 1): Response
    {
        if (trim($term) === '') {
            return $this->gameFilter($request, 'all', $page);
        }

        $games = GameCollection::createAssociative($gameRepository->findBySearch($term));
        return $this->renderListWithResults($request, $games, 'search', $page, $term);
    }

    #[Route("/game/{id}", name: "detail", requirements: ['page' => '\d+'], methods: ["GET"])]
    public function detail(GameRepository $gameRepository, Request $request, int $id): Response
    {
        $filter = $request->getSession()->get('filter', 'all');
        $page = intval($request->getSession()->get('pageNum', 1));
        $search = $request->getSession()->get('search');

        $game = $gameRepository->find($id);
        if ($game === null) {
            $this->addFlash('danger', sprintf('Game with id %d could not be found.', $id));
            return $this->index($request);
        }

        try {
            $formPassword = $this->getParameter('app.form_password');
        } catch (ServiceNotFoundException|InvalidArgumentException) {
            $formPassword = null;
        }

        return $this->render('game.html.twig', [
            'id' => $id,
            'game' => $game,

            //params for post-save redirect
            'filter' => $filter,
            'page' => $page,
            'search' => $search,
            'use_form_password' => $formPassword !== null,
        ]);
    }

    #[Route("/game/{id}", name: "detail_post", requirements: ['page' => '\d+'], methods: ["POST"])]
    public function detailPost(
        ManagerRegistry $doctrine,
        GameRepository $gameRepository,
        Request $request,
        int $id
    ): Response {
        $game = $gameRepository->find($id);
        if ($game === null) {
            $this->addFlash('danger', sprintf('Game with id %d could not be found.', $id));
            return $this->index($request);
        }

        $manager = $doctrine->getManager();

        $postData = $request->request;
        $filter = $postData->getAlnum('filter', 'all');
        $page = $postData->getInt('page', 1);
        $search = $postData->getAlnum('search');
        $action = $postData->getAlnum('action');
        $userFormPassword = $postData->getAlnum('password');

        //password verification
        $passwordVerificationOk = false;
        try {
            $formPassword = $this->getParameter('app.form_password');
            if (!empty($formPassword) && $formPassword === $userFormPassword) {
                $passwordVerificationOk = true;
            }
        } catch (ServiceNotFoundException|InvalidArgumentException) {
            $passwordVerificationOk = false;
        }

        if ($passwordVerificationOk === false) {
            $this->addFlash('danger', 'The password was incorrect.');
            return $this->detail($gameRepository, $request, $id);
        }
        
        if ($action === 'Delete') {
            try {
                $this->delete($manager, $game);
            } catch(RuntimeException $e) {
                $this->addFlash('danger', sprintf(
                    'Deleting game %d failed (%s).',
                    $id,
                    $e->getMessage()
                ));
            }
        } elseif ($action === 'Save') {
            $this->update($manager, $game, $request);
        } else {
            $this->addFlash('danger', 'Invalid POST action.');
            return $this->redirect('index');
        }

        if (strlen($search) > 0) {
            return $this->redirect(sprintf('/search/%s/%d', $search, $page));
        } else {
            return $this->redirect(sprintf('/%s/%d', $filter, $page));
        }
    }

    private function delete(ObjectManager $manager, Game $game): void
    {
        $manager->remove($game);
        $manager->flush();
    }

    private function update(ObjectManager $manager, Game $game, Request $request): void
    {
        $postData = $request->request;
        $purchasedPrice = $postData->get('purchased_price');

        $backwardsCompatible = null;
        $kinectRequired = null;
        $periphRequired = null;
        $onlineMultiplayer = null;
        if ($game->getPlatform() === PlatformEnum::PLATFORM_360) {
            $backwardsCompatible = $postData->getAlnum('backcompat');
            $kinectRequired = $postData->getAlnum('kinect_required');
            $periphRequired = $postData->getAlnum('peripheral_required');
            $onlineMultiplayer = $postData->getAlnum('online_multiplayer');
        }

        $game->setPurchasedPrice(floatval($purchasedPrice));
        $game->setBackwardsCompatibleByString($backwardsCompatible);
        $game->setKinectRequiredByString($kinectRequired);
        $game->setPeripheralRequiredByString($periphRequired);
        $game->setOnlineMultiplayerByString($onlineMultiplayer);

        $game->setLastModified(new DateTime());
        $manager->persist($game);
        $manager->flush();
    }

    #[Route("/import", name: "scrape")]
    public function scrape(Request $request): Response
    {
        $gamertag = $request->request->get('gamertag');
        if ($gamertag === null) {
            return $this->render('import.html.twig');
        }

        $dir = realpath(getcwd() . '/..');
        $process = new Process([
            'php',
            'bin/console',
            'app:import',
            $gamertag
        ], $dir);
        //@TODO I want the output to display in realtime as the command runs, 
        //but 1) it needs to run  in an iframe or something to give live output,
        //and 2) the anon function doesnt include line endings for some reason.
        $process->run(function($type, $buffer) {
            if ($type !== Process::ERR) {
                //printf('%s: %s<br/>' . PHP_EOL, $type, $buffer);
            }
        });

        return $this->render('import.html.twig', [
            'gamertag' => $gamertag,
            'output' => $process->getOutput(),
        ]);
    }

    /**
     * NB: this route should be last in the controller
     */
    #[Route("/{filter}/{page}", name: "filter", requirements: ['page' => '\d+'])]
    public function gameFilter(
        Request $request,
        string $filter,
        int $page = 1
    ): Response {
        try {
            $games = $this->gameFilterService->getGamesByFilter($filter);
        } catch (InvalidFilterException) {
            $this->addFlash('danger', sprintf('Invalid filter name "%s".', $filter));
            return $this->redirectToRoute('index');
        }

        return $this->renderListWithResults($request, $games, $filter, $page);
    }
}