<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * TODO:
 * - pagination
 * - remaining filters
 * - price/purchase info
 * - import (see test.php)
 * - proper sorting by completion estimate (also for hoursPlayed)
 * - proper sorting by hours played (by hoursPlayed or completionEstimate)
 * - sorting by complex things (e.g. mostPlayed and easiest/hardest filters)
 */

use App\Entity\Game;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route("/search")]
    public function searchFilter(ManagerRegistry $doctrine, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        $searchTerm = $request->query->get('search');
        if (is_null($searchTerm) || trim($searchTerm) === '') {
            return $this->indexAction($doctrine);
        }
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBySearch($searchTerm);

        return $this->renderPageWithResults($games);
    }

    #[Route("/all", name: "index")]
    public function allFilter(ManagerRegistry $doctrine): Response
    {
        return $this->indexAction($doctrine);
    }

    #[Route("/completed", name: "filter_completed")]
    public function completedFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['completionPercentage' => 100], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/xb1", name: "filter_xb1")]
    public function xboxOneFilter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['platform' => 'Xbox One'], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/360", name: "filter_360")]
    public function xbox360Filter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['platform' => 'Xbox 360'], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/xsx", name: "filter_xsx")]
    public function xboxSeriesSX(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['platform' => 'Xbox Series X|S'], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/win", name: "filter_win")]
    public function windowsFilter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['platform' => 'Windows'], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/bc", name: "filter_bc")]
    public function backwardsCompatibleFilter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['backwardsCompatible' => 1], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/nonbc", name: "filter_nonbc")]
    public function notBackwardsCompatibleFilter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['backwardsCompatible' => 0], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/nonbckinect", name: "filter_nonbckinect")]
    public function notBackwardsCompatibleKinectFilter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['backwardsCompatible' => 0, 'kinectRequired' => 1], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/nonbcperiph", name: "filter_nonbcperiph")]
    public function notBackwardsCompatiblePeripheralFilter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['backwardsCompatible' => 0, 'peripheralRequired' => 1], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/nonbconline", name: "filter_nonbconline")]
    public function notBackwardsCompatibleOnlineMultiplayerFilter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['backwardsCompatible' => 0, 'onlineMultiplayer' => 1], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/walkthrough", name: "filter_walkthrough")]
    public function walktroughFilter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findWithWalkthrough();

        return $this->renderPageWithResults($games);
    }

    #[Route("/nowalkthrough", name: "filter_nowalkthrough")]
    public function noWalkthroughFilter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['walkthroughUrl' => ''], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/nodlc", name: "filter_nodlc")]
    public function noDlcFilter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['hasDlc' => 0], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/withdlc", name: "filter_withdlc")]
    public function withDlcFilter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['hasDlc' => 1], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/dlccompleted", name: "filter_dlccompleted")]
    public function dlcCompletedFilter(ManagerRegistry $doctrine): Response
    {

        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['hasDlc' => 1, 'dlcCompletionPercentage' => 100], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/dlcnotcompleted", name: "filter_dlcnotcompleted")]
    public function dlcNotCompletedFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findNotCompletedDlc();

        return $this->renderPageWithResults($games);
    }

    #[Route("/incomplete", name: "filter_incomplete")]
    public function incompleteFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findIncompleteGames();

        return $this->renderPageWithResults($games);
    }

    #[Route("/notstarted", name: "filter_notstarted")]
    public function notStartedFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findBy(['completionPercentage' => 0], ['name' => 'ASC']);

        return $this->renderPageWithResults($games);
    }

    #[Route("/bestrating", name: "filter_bestrating")]
    public function bestRatingFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findOrderByBestRating();

        return $this->renderPageWithResults($games);
    }

    #[Route("/notstartedbestrating", name: "filter_notstartedbestrating")]
    public function notStartedBestRatingFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findNotStartedOrderByBestRating();

        return $this->renderPageWithResults($games);
    }

    #[Route("/shortest", name: "filter_shortest")]
    public function shortestFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findShortest();

        return $this->renderPageWithResults($games);
    }

    #[Route("/shortestnotstarted", name: "filter_notstartedshortest")]
    public function shortestNotStartedFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findShortestNotStarted();

        return $this->renderPageWithResults($games);
    }

    #[Route("/longest", name: "filter_longest")]
    public function longestFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findLongest();

        return $this->renderPageWithResults($games);
    }

    #[Route("/mostplayed", name: "filter_mostplayed")]
    public function mostPlayedFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findMostPlayed();

        return $this->renderPageWithResults($games);
    }

    #[Route("/easiest", name: "filter_easiest")]
    public function easiestFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findEasiest();

        return $this->renderPageWithResults($games);
    }

    #[Route("/hardest", name: "filter_hardest")]
    public function hardestFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findHardest();

        return $this->renderPageWithResults($games);
    }

    #[Route("/recent", name: "filter_recent")]
    public function recentFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findRecent();

        return $this->renderPageWithResults($games);
    }

    #[Route("/paid", name: "filter_paid")]
    public function paidFilter(ManagerRegistry $doctrine): Response
    {
        $gameRepository = $doctrine->getRepository(Game::class);
        $games = $gameRepository->findPaid();

        return $this->renderPageWithResults($games);
    }

}