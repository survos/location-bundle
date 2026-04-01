<?php

namespace Survos\LocationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Location|null find($id, $lockMode = null, $lockVersion = null)
 * @method Location|null findOneBy(array $criteria, array $orderBy = null)
 * @method Location[]    findAll()
 * @method Location[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Survos\LocationBundle\Entity\Location;

class LocationRepository extends NestedTreeRepository
{

    public function __construct(EntityManagerInterface|ManagerRegistry $registry)
    {
        if ($registry::class == ManagerRegistry::class) {
            $manager = $registry->getManagerForClass(Location::class);
            $metadata = $manager->getClassMetadata(Location::class);
        } else {
            /** @var EntityManagerInterface $manager */
            $manager = $registry;
            $metadata = $manager->getClassMetadata(Location::class);
        }

        parent::__construct($manager, $manager->getClassMetadata(Location::class));
//        dd($registry, $registry::class, $manager);
    }



    // /**
    //  * @return Location[] Returns an array of Location objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Location
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
