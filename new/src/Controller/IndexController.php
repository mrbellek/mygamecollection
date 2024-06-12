<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * TODO:
 * - pagination
 * - proper sorting by completion estimate (also for hoursPlayed)
 * - proper sorting by hours played (by hoursPlayed or completionEstimate)
 * - sorting by complex things (e.g. mostPlayed and easiest/hardest filters)
 */

use App\Entity\Game;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IndexController extends AbstractController
{
    public function renderPageWithResults(array $games): Response
    {
        //@TODO: pagination
        $gamesSlice = array_slice($games, 0, 25);
    
        return $this->render('index.html.twig', [
            'games' => $gamesSlice,
            'count' => count($games),
            'stats' => [
                'on_sale' => 0,
                'free' => 0,
                'purchased_free' => 0,
                'delisted' => 0,
                'total_purchased' => 0,
                'total_currentvalue' => 0,
                'total_saved' => 0,
                'average_purchased' => 0,
                'average_value' => 0,
                'total_playtime' => 0,
                'spent_playtime' => 0,
                'spent_week' => 0,
                'spent_week_tooltip' => 0,
                'spent_month' => 0,
                'spent_month_tooltip' => 0,
                'spent_6month' => 0,
                'spent_6month_tooltip' => 0,
                'spent_year' => 0,
            ],
            'pageNum' => 1,
            'perpage' => 25,
            'show' => 'all',
            'search' => '',
        ]);
    }

    #[Route("/", name: "index")]
    public function indexAction(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy([], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/completed", name: "filter_completed")]
    public function completedFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['completionPercentage' => 100], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/incomplete", name: "filter_incomplete")]
    public function incompleteFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findIncompleteGames();

        return $this->renderPageWithResults($games);
    }

    #[Route("notstarted", name: "filter_notstarted")]
    public function notStartedFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['completionPercentage' => 0], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("bestrating", name: "filter_bestrating")]
    public function bestRatingFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findOrderByBestRating();

        return $this->renderPageWithResults($games);
    }

    #[Route("notstartedbestrating", name: "filter_notstartedbestrating")]
    public function notStartedBestRatingFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findNotStartedOrderByBestRating();

        return $this->renderPageWithResults($games);
    }

    #[Route("shortest", name: "filter_shortest")]
    public function shortestFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findShortest();

        return $this->renderPageWithResults($games);
    }

    #[Route("shortestnotstarted", name: "filter_notstartedshortest")]
    public function shortestNotStartedFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findShortestNotStarted();

        return $this->renderPageWithResults($games);
    }

    #[Route("longest", name: "filter_longest")]
    public function longestFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findLongest();

        return $this->renderPageWithResults($games);
    }

    #[Route("mostplayed", name: "filter_mostplayed")]
    public function mostPlayedFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findMostPlayed();

        return $this->renderPageWithResults($games);
    }

    #[Route("easiest", name: "filter_easiest")]
    public function easiestFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findEasiest();

        return $this->renderPageWithResults($games);
    }

    #[Route("hardest", name: "filter_hardest")]
    public function hardestFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findHardest();

        return $this->renderPageWithResults($games);
    }

}