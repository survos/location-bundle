<?php


namespace Survos\LocationBundle\Service;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use GuzzleHttp\Promise\Promise;
use SplFileObject;
use Survos\LocationBundle\Entity\Location;
use Survos\LocationBundle\Repository\LocationRepository;
use Survos\LocationBundle\Service\ImportInterface;

/**
 * Class AdministrativeImport
 * @author Chris Bednarczyk <chris@tourradar.com>
 * @package Survos\LocationBundle\Import
 */
class AdministrativeImport implements ImportInterface
{

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(
//        private LocationRepository $locationRepository,
        ManagerRegistry $registry)
    {
        // since we don't know EM is associated with the Location table, pass in the registry instead.
        $this->em = $registry->getManagerForClass(Location::class);
    }



    /**
     * @param string $filePath
     * @param callable|null $progress
     * @return bool
     * @author Chris Bednarczyk <chris@tourradar.com>
     */
    public function import($filePath, ?callable $progress = null): bool
    {
        $file = new SplFileObject($filePath);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl("\t");
        $file->seek(PHP_INT_MAX);
        $max = $file->key();
        $file->seek(1); //skip header



//        $administrative = $this->em->getRepository("BordeuxGeoNameBundle:Administrative");

        $pos = 0;

        foreach ($file as $row) {
            $row = array_map('trim',$row);
            list(
                $code,
                $name,
                $asciiName,
                $geoNameId
                ) = $row;

            dd($row);

            $object = $this->locationRepository->findOneBy(['code' => $code]) ?: new Location();
            $object->setCode($code);
            $object->setName($name);
            $object->setAsciiName($asciiName);

            !$object->getId() && $this->em->persist($object);

            is_callable($progress) && $progress(($pos++) / $max);

            if($pos % 10000){
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();
        $this->em->clear();

        return true;
    }

}
