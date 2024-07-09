<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * TODO:
 * - limit results for certain filters if it makes no sense to just list everything (like mostPlayed)
 * - fix search form, submits to /search/[page] instead of /search/[term]/[page]
 * - form password protection
 * - column sorting
 */

use App\Enum\Platform as PlatformEnum;
use App\Repository\GameRepository;
use App\Service\GameFilterService;
use App\Service\GameScraperService;
use App\Service\GameStatsService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
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
        private readonly GameFilterService $gameFilterService,
    ) {}
    
    public function renderListWithResults(
        array $games,
        string $filter = 'all',
        int $pageNum = 1,
    ): Response {

        $adapter = new ArrayAdapter($games);
        try {
            $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage($adapter, $pageNum, self::PAGE_SIZE);
        } catch (OutOfRangeCurrentPageException $e) {
            $pageNum = 1;
            $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage($adapter, $pageNum, self::PAGE_SIZE);
        }
    
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
    public function index(): Response
    {
        return $this->gameFilter('all', 1);
    }

    #[Route("/search/{term}/{page}", name: "search", requirements: ['page' => '\d+'])]
    public function searchFilter(GameRepository $gameRepository, string $term, int $page = 1): Response
    {
        if (is_null($term) || trim($term) === '') {
            return $this->gameFilter('all', $page);
        }

        return $this->renderListWithResults($gameRepository->findBySearch($term), 'search', $page);
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