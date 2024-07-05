<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * TODO:
 * - price/purchase info
 * - details page
 * - import (see test.php)
 * - limit results for certain filters if it makes no sense to just list everything (like mostPlaed)
 */

use App\Enum\Platform as PlatformEnum;
use App\Repository\GameRepository;
use App\Service\GameFilterService;
use App\Service\GameStatsService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IndexController extends AbstractController
{
    private const PAGE_SIZE = 25;

    public function __construct(
        private readonly GameStatsService $gameStatsService,
    ) {}
    
    public function renderListWithResults(
        array $games,
        string $filter = 'all',
        int $pageNum = 1,
    ): Response {

        $adapter = new ArrayAdapter($games);
        $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage($adapter, $pageNum, self::PAGE_SIZE);
    
        return $this->render('index.html.twig', [
            'games' => $pagerfanta,
            'count' => count($games),
            'stats' => $this->gameStatsService->getStats($games),
            'page' => $pageNum,
            'show' => $filter,
            'search' => '',
        ]);
    }

    #[Route("/", name: "index")]
    public function indexAction(GameRepository $gameRepository): Response
    {
        return $this->gameFilter($gameRepository, 'all', 1);
    }

    #[Route("/search/{page}", name: "search", requirements: ['page' => '\d+'])]
    public function searchFilter(GameRepository $gameRepository, Request $request, int $page = 1): Response
    {
        //@TODO omzetten naar /search/[term]/[page]
        $searchTerm = $request->query->get('search');
        if (is_null($searchTerm) || trim($searchTerm) === '') {
            return $this->gameFilter($gameRepository, 'all', $page);
        }

        return $this->renderListWithResults($gameRepository->findBySearch($searchTerm), 'search', $page);
    }

    #[Route("/game/{id}", name: "detail", requirements: ['page' => '\d+'], methods: ["GET"])]
    public function detail(GameRepository $gameRepository, int $id): Response
    {
        $game = $gameRepository->find($id);
        if ($game === null) {
            throw new InvalidArgumentException(sprintf('Game with id %d was not found.', $id));
        }

        return $this->render('index.html.twig', [
            'id' => $id,
            'game' => $game,

            //@TODO params for post-save redirect
            'show' => 'all',
            'page' => 1,
            'search' => '',
            'form_password' => 'test',
        ]);
    }

    #[Route("/game/{id}", name: "defail_post", requirements: ['page' => '\d+'], methods: ["POST"])]
    public function detailPost(
        ManagerRegistry $doctrine,
        GameRepository $gameRepository,
        Request $request,
        int $id
    ): Response {
        $game = $gameRepository->find($id);
        if ($game === null) {
            throw new InvalidArgumentException(sprntf('Game with id %d could not be found.', $id));
        }

        $manager = $doctrine->getManager();

        $postData = $request->request;
        $filter = $postData->get('show', 'all');
        $page = $postData->getInt('page', 1);
        $search = $postData->get('search');

        $purchasedPrice = $postData->get('purchased_price');

        //@TODO password verification
        $formPassword = $postData->get('form_password');

        $kinectRequired = null;
        $periphRequired = null;
        $onlineMultiplayer = null;
        if ($game->getPlatform() === PlatformEnum::PLATFORM_360) {
            $kinectRequired = $postData->get('kinect_required');
            $periphRequired = $postData->get('peripheral_required');
            $onlineMultiplayer = $postData->get('online_multiplayer');
        }

        $game->setPurchasedPrice(floatval($purchasedPrice));
        $game->setKinectRequired($kinectRequired);
        $game->setPeripheralRequired($periphRequired);
        $game->setOnlineMultiplayer($onlineMultiplayer);

        $game->setLastModified(new DateTime());
        $manager->persist($game);
        $manager->flush();

        if (strlen($search) > 0) {
            //@TODO convert to /search/[term]/[page]
            return $this->redirect(sprintf('/search/%d', $page));
        } else {
            return $this->redirect(sprintf('/%s/%d', $filter, $page));
        }
    }

    #[Route("/{filter}/{page}", name: "filter", requirements: ['page' => '\d+'])]
    public function gameFilter(GameFilterService $gameService, string $filter, int $page = 1): Response
    {
        return $this->renderListWithResults($gameService->getGamesByFilter($filter), $filter, $page);
    }
}