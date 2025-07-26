<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\GameRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RankingController extends AbstractController
{
    public function __construct(private readonly GameRepository $gameRepository)
    {
    }

    #[Route("/ranking", name: "ranking")]
    public function ranking(): Response
    {
        $games = $this->gameRepository->fetchTwoRankingGames();

        return $this->render('ranking/index.html.twig', [
            'game1' => $games[0],
            'game2' => $games[1],
        ]);
    }

    #[Route("/ranking/choose/{gameId}/{otherGameId}", name: "ranking_choose", requirements: ['gameId' => '\d+', 'otherGameId' => '\d+'])]
    public function choose(
        int $gameId,
        int $otherGameId,
        ManagerRegistry $doctrine
    ): Response {
        $game = $this->gameRepository->find($gameId);
        $otherGame = $this->gameRepository->find($otherGameId);

        if ($game->getRanking() > 0 && $otherGame->getRanking() > 0) {
            //both games have already been ranked, switch rankings and save
            $t = $game->getRanking();
            $game->setRanking($otherGame->getRanking());
            $otherGame->setRanking($t);

        } elseif ($game->getRanking() > 0 && $otherGame->getRanking() === 0) {
            //chosen game has already been ranked, other game has not - do nothing
        } elseif ($game->getRanking() === 0 && $otherGame->getRanking() > 0) {
            //insert chosen game above other, ranked game
            die('TODO');
        } else {
            //both games have no ranking, insert them below lowest ranked game
            $lowestRanking = $this->gameRepository->getLowestRanking();
            $game->setRanking($lowestRanking + 1);
            $otherGame->setRanking($lowestRanking + 2);
        }

        $manager = $doctrine->getManager();
        $manager->persist($game);
        $manager->persist($otherGame);
        $manager->flush();

        return $this->redirectToRoute('ranking');
    }
}