<?php

namespace App\Repository;

use App\Entity\Game;
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
    
    public function findBySearch(string $searchTerm): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.name LIKE :search')
            ->setParameter(':search', '%' . $searchTerm . '%')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    public function findIncompleteGames(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.completionPercentage > 0')
            ->andWhere('g.completionPercentage < 100')
            ->orderBy('g.completionPercentage', 'DESC')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOrderByBestRating(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.siteRating > 4')
            ->orderBy('g.siteRating', 'DESC')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNotStartedOrderByBestRating(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.siteRating > 4')
            ->andWhere('g.completionPercentage = 0')
            ->orderBy('g.siteRating', 'DESC')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findShortest(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.completionEstimate != :blank')
            ->andWhere('g.completionEstimate <= 10')
            ->orderBy('g.completionEstimate', 'ASC')
            ->orderBy('g.name', 'ASC')
            ->setParameter(':blank', '')
            ->getQuery()
            ->getResult();
    }

    public function findShortestNotStarted(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.completionEstimate != :blank')
            ->andWhere('g.completionEstimate <= 10')
            ->andWhere('g.completionPercentage = 0')
            ->orderBy('g.completionEstimate', 'ASC')
            ->orderBy('g.name', 'ASC')
            ->setParameter(':blank', '')
            ->getQuery()
            ->getResult();
    }

    public function findLongest(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.completionEstimate != :blank')
            ->andWhere('g.completionEstimate > 100')
            ->setParameter(':blank', '')
            ->getQuery()
            ->getResult();
    }

    public function findWithNonZeroTaTotal(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.taTotal > 0')
            ->getQuery()
            ->getResult();
    }

    public function findRecent(): array
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.created', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    public function findPaid(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.purchasedPrice > 0')
            ->orderBy('g.purchasedPrice', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findWithWalkthrough(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.walkthroughUrl != :blank')
            ->orderBy('g.name', 'ASC')
            ->setParameter(':blank', '')
            ->getQuery()
            ->getResult();
    }

    public function findNotCompletedDlc(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.hasDlc = 1')
            ->andWhere('g.dlcCompletionPercentage < 100')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOnSale(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.status = :sale')
            ->setParameter(':sale', 'sale')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFree(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.purchasedPrice = 0')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
