<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Series;
use App\Entity\SeriesGame;
use App\Repository\SeriesGameRepository;
use App\Repository\SeriesRepository;
use App\Resources\FormSeries;
use App\Resources\FormSeriesGame;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SeriesSetlistController extends AbstractController
{
    #[Route("/series-setlist", name: "series_setlist")]
    public function seriesSetlist(
        Request $request,
        SeriesRepository $seriesRepository
    ): Response {

        //@TODO implement this in the template
        $showAll = true;//(bool)$request->request->getInt('showAll');

        $series = $seriesRepository->findAll();
        //@TODO: FormSeriesCollection?
        $formSeries = [];
        foreach ($series as $serie) {
            $formSerie = new FormSeries($serie);

            //hide setlists with 0 owned games by default
            if ($formSerie->getOwnedGamesCount() > 0 || $showAll === true) {
                $formSeries[] = $formSerie;
            }
        }

        usort($formSeries, function(FormSeries $a, FormSeries $b) {
            if ($a->getCompletionPercentage() != $b->getCompletionPercentage()) {
                return $b->getCompletionPercentage() <=> $a->getCompletionPercentage();
            } elseif ($a->getOwnedGamesCount() != $b->getOwnedGamesCount()) {
                return $b->getOwnedGamesCount() <=> $a->getOwnedGamesCount();
            } else {
                return $a->getName() <=> $b->getName();
            }
        });

        return $this->render('series_setlist.html.twig', [
            'series' => $formSeries,
        ]);
    }

    #[Route("/series-setlist/add", name: "series_setlist_add")]
    public function seriesSetlistAdd(
        ManagerRegistry $doctrine,
        Request $request,
    ): Response {

        $name = '';
        $userTitle = '';
        $status = '';
        if ($request->getMethod() === 'POST') {
            $name = $request->request->get('name');
            $userTitle = $request->request->get('userTitle');
            $status = $request->request->get('status');

            $serie = new Series();
            $serie->setName($name);
            $serie->setUserTitle($userTitle);
            $serie->setStatus($status);

            $manager = $doctrine->getManager();
            $manager->persist($serie);
            $manager->flush();

            $this->addFlash('success', 'Changed saved.');
            return $this->redirectToRoute('series_setlist');
        }

        return $this->render('series_setlist_edit.html.twig', [
            'mode' => 'add',
            'serie' => [
                'name' => $name,
                'userTitle' => $userTitle,
                'status' => $status,
            ],
        ]);
    }

    #[Route("/series-setlist/edit/{id}", name: "series_setlist_edit", requirements: ['id' => '\d+'])]
    public function seriesSetlistEdit(
        ManagerRegistry $doctrine,
        Request $request,
        SeriesRepository $seriesRepository,
        int $id
    ): Response {

        $serie = $seriesRepository->find($id);

        if ($request->getMethod() === 'POST') {
            if ($request->request->get('action') === 'Delete') {
                $manager = $doctrine->getManager();
                $manager->remove($serie);
                $manager->flush();

                $this->addFlash('success', 'Changes saved.');

                return $this->redirectToRoute('series_setlist');
            }
            $serie->setName($request->request->get('name'));
            $serie->setUserTitle($request->request->get('userTitle'));
            $serie->setStatus($request->request->get('status'));

            $manager = $doctrine->getManager();
            $manager->persist($serie);
            $manager->flush();

            $this->addFlash('success', 'Changes saved.');
            return $this->redirectToRoute("series_setlist");
        }

        return $this->render('series_setlist_edit.html.twig', [
            'mode' => 'edit',
            'serie' => $serie,
        ]);
    }

    #[Route("/series-setlist/games/{id}", name: "series_setlist_games", requirements: ['id' => '\d+'])]
    public function seriesSetlistGames(
        SeriesRepository $seriesRepository,
        SeriesGameRepository $seriesGameRepository,
        int $id
    ): Response {
        /** @var Series $serie */
        $serie = $seriesRepository->find($id);
        $formSerie = new FormSeries($serie);

        $games = $seriesGameRepository->findBySetlistId($id);
        //@TODO: FormSeriesGameCollection?
        $formSeriesGames = [];
        /** @var SeriesGame $seriesGame **/
        foreach ($games as $seriesGame) {
            $altForName = null;
            if ($seriesGame->getAltForId() > 0) {
                $altSeriesGame = $seriesGameRepository->findOneBy(['gameId' => $seriesGame->getAltForId()]);
                $altForName = $altSeriesGame->getName();
            }
            $formSeriesGames[] = new FormSeriesGame($seriesGame, $altForName);
        }

        return $this->render('series_setlist_games.html.twig', [
            'serie' => $formSerie,
            'games' => $formSeriesGames,
        ]);
    }

    #[Route("/series-setlist/games/{seriesId}/edit/{id}", name: "series_setlist_games_edit", requirements: ['seriesId' => '\d+', 'id' => '\d+'])]
    public function seriesSetlistGamesEdit(
        ManagerRegistry $doctrine,
        Request $request,
        SeriesGameRepository $seriesGameRepository,
        int $seriesId,
        int $id,
    ): Response {

        /** @var SeriesGame $seriesGame **/
        $seriesGame = $seriesGameRepository->find($id);

        if ($request->getMethod() === 'POST') {

            $manager = $doctrine->getManager();
            if ($request->request->get('action') === 'Delete') {
                $manager->remove($seriesGame);
                $manager->flush();
                $this->addFlash('success', 'Changes saved.');

            } else {
                $seriesGame->setGameId($request->request->getInt('gameId'));
                $seriesGame->setName($request->request->get('name'));
                $altForId = !empty($request->request->get('altForId')) ? $request->request->getInt('altForId') : null;
                $seriesGame->setAltForId($altForId);

                $manager->persist($seriesGame);
                $manager->flush();

                $this->addFlash('success', 'Changes saved.');
            }
            return $this->redirectToRoute("series_setlist_games", ['id' => $seriesId]);
        }

        return $this->render('series_setlist_games_edit.html.twig', [
            'mode' => 'edit',
            'seriesId' => $seriesId,
            'seriesGame' => $seriesGame,
        ]);
    }

    #[Route("/series-setlist/games/{seriesId}/add", name: "series_setlist_games_add", requirements: ['seriesId' => '\d+'])]
    public function seriesSetlistGamesAdd(
        ManagerRegistry $doctrine,
        Request $request,
        int $seriesId,
    ): Response {

        if ($request->getMethod() === 'POST') {
            $seriesGame = new SeriesGame();
            $seriesGame->setSetlistId($seriesId);
            $seriesGame->setGameId($request->request->getInt('gameId'));
            $seriesGame->setName($request->request->get('name'));
            $altForId = !empty($request->request->get('altForId')) ? $request->request->getInt('altForId') : null;
            $seriesGame->setAltForId($altForId);

            $manager = $doctrine->getManager();
            //@TODO do I need this? doctrine is being weird
            $gameRepository = $manager->getRepository(Game::class);
            $game = $gameRepository->find($seriesGame->getGameId());
            $seriesGame->setGame($game);
            $seriesRepository = $manager->getRepository(Series::class);
            $series = $seriesRepository->find($seriesId);
            $seriesGame->setSeries($series);

            $manager->persist($seriesGame);
            $manager->flush();
            $this->addFlash('success', 'Changes saved.');

            return $this->redirectToRoute('series_setlist_games', ['id' => $seriesId]);
        }

        return $this->render('series_setlist_games_edit.html.twig', [
            'mode' => 'add',
            'seriesId' => $seriesId,
        ]);
    }


}