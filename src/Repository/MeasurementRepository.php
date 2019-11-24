<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Measurement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Measurement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Measurement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Measurement[]    findAll()
 * @method Measurement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MeasurementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Measurement::class);
    }

     /**
      * @return Measurement[] Returns an array of Measurement objects
      */
    public function findBySnifferInRange(string $sniffer, int $from, int $to): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.sniffer = :sniffer')
            ->andWhere('m.time >= :from')
            ->andWhere('m.time <= :to')
            ->setParameter('sniffer', $sniffer)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('m.time', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
