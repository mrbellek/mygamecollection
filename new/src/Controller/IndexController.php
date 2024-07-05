<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * TODO:
 * - price/purchase info
 * - import (see test.php)
 */

use App\Repository\GameRepository;
use App\Service\GameFilterService;
use App\Service\GameStatsService;
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
    
    public function renderPageWithResults(
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

    #[Route("/{filter}/{page}", name: "filter", requirements: ['page' => '\d+'])]
    public function gameFilter(GameFilterService $gameService, string $filter, int $page = 1): Response
    {
        return $this->renderPageWithResults($gameService->getGamesByFilter($filter), $filter, $page);
    }

    #[Route("/search/{page}", name: "search", requirements: ['page' => '\d+'])]
    public function searchFilter(GameRepository $gameRepository, Request $request, int $page = 1): Response
    {
        //@TODO omzetten naar /search/[term]/[page]
        $searchTerm = $request->query->get('search');
        if (is_null($searchTerm) || trim($searchTerm) === '') {
            return $this->gameFilter($gameRepository, 'all', $page);
        }

        return $this->renderPageWithResults($gameRepository->findBySearch($searchTerm), 'search', $page);
    }
}