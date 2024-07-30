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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }
    
    /**
     * @return array<Game>
     */
    public function findBySearch(string $searchTerm): array
    {
        return $this->createQueryBuilder('g')
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
        return $this->createQueryBuilder('g')
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
        return $this->createQueryBuilder('g')
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
        return $this->createQueryBuilder('g')
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
        return $this->createQueryBuilder('g')
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
        return $this->createQueryBuilder('g')
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
        return $this->createQueryBuilder('g')
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
        return $this->createQueryBuilder('g')
            ->where('g.achievementsWon > 0')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findWithNonZeroTaTotal(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.taTotal > 0')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findRecent(): array
    {
        return $this->createQueryBuilder('g')
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
        return $this->createQueryBuilder('g')
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
        return $this->createQueryBuilder('g')
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
        return $this->createQueryBuilder('g')
            ->where('g.hasDlc = 1')
            ->andWhere('g.dlcCompletionPercentage < 100')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findOnSale(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.status = :sale')
            ->setParameter(':sale', 'sale')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Game>
     */
    public function findFree(): array
    {
        return $this->createQueryBuilder('g')
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
        return $this->createQueryBuilder('g')
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
}
