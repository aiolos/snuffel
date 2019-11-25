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

    public function findAllSniffers(): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery('SELECT distinct m.sniffer FROM App\Entity\Measurement m');

        return $query->getResult();
    }

    public function findAllDatesForSniffer(string $sniffer): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT distinct FROM_UNIXTIME(m.time, "%Y-%m-%d") as snifDay FROM measurement m where sniffer = :sniffer order by snifDay desc';
        $stmt = $conn->prepare($sql);
        $stmt->execute(['sniffer' => $sniffer]);

        // returns an array of arrays (i.e. a raw data set)
        return $stmt->fetchAll();
    }
}
