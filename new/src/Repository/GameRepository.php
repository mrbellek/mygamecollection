<?php

namespace App\Repository;

use App\Entity\Game;
use App\Enum\Status as StatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 *
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    private $qb;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
        $this->qb = $this->createQueryBuilder('g');
    }
    
    /**
     * @return array<Game>
     */
    public function findBySearch(string $searchTerm): array
    {
        return $this->qb
            ->where('g.name LIKE :search')
            ->setParameter(':search', '%' . $searchTerm . '%')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * @return array<Game>
     */
    public function findIncompleteGames(): array
    {
        return $this->qb
            ->where('g.completionPercentage > 0')
            ->andWhere('g.completionPercentage < 100')
            ->addOrderBy('g.completionPercentage', 'DESC')
            ->addOrderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findOrderByBestRating(): array
    {
        return $this->qb
            ->where('g.siteRating > 4')
            ->addOrderBy('g.siteRating', 'DESC')
            ->addOrderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findNotStartedOrderByBestRating(): array
    {
        return $this->qb
            ->where('g.siteRating > 4')
            ->andWhere('g.completionPercentage = 0')
            ->addOrderBy('g.siteRating', 'DESC')
            ->addOrderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findShortest(): array
    {
        return $this->qb
            ->where('g.completionEstimate != :blank')
            ->andWhere('g.completionEstimate <= 10')
            ->addOrderBy('g.completionEstimate', 'ASC')
            ->addOrderBy('g.name', 'ASC')
            ->setParameter(':blank', '')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findShortestNotStarted(): array
    {
        return $this->qb
            ->where('g.completionEstimate != :blank')
            ->andWhere('g.completionEstimate <= 10')
            ->andWhere('g.completionPercentage = 0')
            ->addOrderBy('g.completionEstimate', 'ASC')
            ->addOrderBy('g.name', 'ASC')
            ->setParameter(':blank', '')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findLongest(): array
    {
        return $this->qb
            ->where('g.completionEstimate != :blank')
            ->andWhere('g.completionEstimate > 100')
            ->setParameter(':blank', '')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findPlayed(): array
    {
        return $this->qb
            ->where('g.achievementsWon > 0')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findWithNonZeroTaTotal(): array
    {
        return $this->qb
            ->where('g.taTotal > 0')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findRecent(): array
    {
        return $this->qb
            ->orderBy('g.created', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findPaid(): array
    {
        return $this->qb
            ->where('g.purchasedPrice > 0')
            ->orderBy('g.purchasedPrice', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findWithWalkthrough(): array
    {
        return $this->qb
            ->where('g.walkthroughUrl != :blank')
            ->orderBy('g.name', 'ASC')
            ->setParameter(':blank', '')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findNotCompletedDlc(): array
    {
        return $this->qb
            ->where('g.hasDlc = 1')
            ->andWhere('g.dlcCompletionPercentage < 100')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findFree(): array
    {
        return $this->qb
            ->where('g.purchasedPrice = 0')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findUnavailable(): array
    {
        return $this->qb
            ->where('g.status IN (:status)')
            ->setParameter('status', [
                StatusEnum::STATUS_DELISTED,
                StatusEnum::STATUS_REGION_LOCKED,
                StatusEnum::STATUS_SOLD,
            ])
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function fetchTwoRankingGames(): array
    {
        //@TODO should be using RAND() here but doctrine doesnt natively support it
        //@TODO should be picking both ranked and unranked games, prioritizing unranked ones - UNION?
        $rows = $this->qb
            ->where('g.ranking = 0')
            ->andWhere('g.completionPercentage < 100')
            ->getQuery()
            ->getResult();

        shuffle($rows);

        return array_slice($rows, 0, 2);
    }

    public function getLowestRanking(): int
    {
        return $this->qb
            ->select('MAX(g.ranking) AS lowestRanking')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function shiftRankingDownAt(int $atRanking): self
    {
        $this->qb
            ->update('g')
            ->set('g.ranking', 'g.ranking + 1')
            ->where('g.ranking > :atRanking')
            ->setParameter(':atRanking', $atRanking)
            ->getQuery()
            ->execute();

        return $this;
    }

    public function fetchRankingStats(): array
    {
        $unrankedCount = $this->qb
            ->select('COUNT(g.id) AS cnt')
            ->where('g.ranking = 0')
            ->getQuery()
            ->getSingleScalarResult();

        $topFive = $this->qb
            ->select('g.name')
            ->where('g.ranking > 0')
            ->orderBy('g.ranking', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $bottomFive = $this->qb
            ->select('g.name')
            ->where('g.ranking > 0')
            ->orderBy('g.ranking', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return [
            'unrankedCount' => $unrankedCount,
            'top5' => $topFive,
            'bottom5' => $bottomFive,
        ];
    }
}
