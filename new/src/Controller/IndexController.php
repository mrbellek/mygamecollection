<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * TODO:
 * - post-edit redirect url should go back to filter/page/search, without ugly slug
 * - flash messages dont have markup (form password)
 * - column sorting
 * - proper graphs for top stats instead of buttons
 */

use App\Entity\Game;
use App\Enum\Platform as PlatformEnum;
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
        array $games,
        string $filter = 'all',
        int $pageNum = 1,
        string $search = '',
    ): Response {

        $pageSize = intval($this->getParameter('app.page_size') ?? self::DEFAULT_PAGE_SIZE);
        $adapter = new ArrayAdapter($games);
        try {
            $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage($adapter, $pageNum, $pageSize);
        } catch (OutOfRangeCurrentPageException $e) {
            $pageNum = 1;
            $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage($adapter, $pageNum, $pageSize);
        }
    
        return $this->render('index.html.twig', [
            'games' => $pagerfanta,
            'count' => count($games),
            'stats' => $this->gameStatsService->getStats($games),
            'page' => $pageNum,
            'show' => $filter,
            'search' => $search,
            'paginateSlug' => strlen($search) > 0 ? sprintf('%s/%s', $filter, $search) : $filter,
        ]);
    }

    #[Route("/", name: "index")]
    public function index(): Response
    {
        return $this->gameFilter('all', 1);
    }

    #[Route("/search/{term}/{page}", name: "search", requirements: ['page' => '\d+'])]
    public function searchFilter(GameRepository $gameRepository, string $term, int $page = 1): Response
    {
        if (trim($term) === '') {
            return $this->gameFilter('all', $page);
        }

        return $this->renderListWithResults($gameRepository->findBySearch($term), 'search', $page, $term);
    }

    #[Route("/game/{id}", name: "detail", requirements: ['page' => '\d+'], methods: ["GET"])]
    public function detail(GameRepository $gameRepository, Request $request, int $id): Response
    {
        $show = $request->query->get('show', 'all');
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search');

        $game = $gameRepository->find($id);
        if ($game === null) {
            $this->addFlash('error', sprintf('Game with id %d could not be found.', $id));
            return $this->index();
        }

        try {
            $formPassword = $this->getParameter('app.form_password');
        } catch (ServiceNotFoundException|InvalidArgumentException) {
            $formPassword = null;
        }

        return $this->render('index.html.twig', [
            'id' => $id,
            'game' => $game,

            //params for post-save redirect
            'show' => $show,
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
            $this->addFlash('error', sprintf('Game with id %d could not be found.', $id));
            return $this->index();
        }

        $manager = $doctrine->getManager();

        $postData = $request->request;
        $filter = $postData->get('show', 'all');
        $page = $postData->getInt('page', 1);
        $search = $postData->get('search');
        $action = $postData->get('action');
        $userFormPassword = $postData->get('password');

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
            $this->addFlash('error', 'The password was incorrect.');
            return $this->detail($gameRepository, $request, $id);
        }
        
        if ($action === 'Delete') {
            try {
                $this->delete($manager, $game);
            } catch(RuntimeException $e) {
                $this->addFlash('error', sprintf(
                    'Deleting game %d failed (%s).',
                    $id,
                    $e->getMessage()
                ));
            }
        } else {
            //@TODO try/catch?
            //@TODO check for invalid action?
            $this->update($manager, $game, $request);
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
            $backwardsCompatible = $postData->get('backcompat');
            $kinectRequired = $postData->get('kinect_required');
            $periphRequired = $postData->get('peripheral_required');
            $onlineMultiplayer = $postData->get('online_multiplayer');
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

    #[Route("/scrape", name: "scrape")]
    public function scrape(GameScraperService $scraper): Response
    {
        //@TODO somehow get symfony command to run in the browser?
        $scrapedGames = $scraper->scrape('mrbellek');
        dd(($scrapedGames));
    }

    /**
     * NB: this route should be last in the controller
     */
    #[Route("/{filter}/{page}", name: "filter", requirements: ['page' => '\d+'])]
    public function gameFilter(string $filter, int $page = 1): Response
    {
        return $this->renderListWithResults($this->gameFilterService->getGamesByFilter($filter), $filter, $page);
    }
}