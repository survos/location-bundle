<?php

namespace Survos\LocationBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Survos\LocationBundle\Entity\Location;
use Survos\LocationBundle\Repository\LocationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'survos:location:load',
    description: 'Load Symfony countries, ISO 2nd level, and world cities as locations',
)]
class LoadCommand extends Command
{
    private EntityManagerInterface $em;
    private LocationRepository $locationRepository;
    private array $levels = ['Continent', 'Country','State','City'];

    public function __construct(ManagerRegistry $registry,
//                                private ValidatorInterface $validator,
                                ?string $name=null)
    {
        // since we don't know EM is associated with the Location    table, pass in the registry instead.
        $this->em = $registry->getManager('survos_location');
        $this->locationRepository = $this->em->getRepository(Location::class);

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);

        $this->load($this->em);

        return Command::SUCCESS;
    }

    /**
     * @var mixed[][]
     */
    private array $lvlCache = [];

    public function load(ObjectManager $manager): void
    {
        $this->output = new ConsoleOutput();
        $this->manager = $manager;
        $this->locationRepository->createQueryBuilder('l')
//            ->where('l.lvl = 3')
            ->delete()->getQuery()->execute();
        $this->em->flush();
//        $this->flushLevel(0);

        $this->loadCountries();
        $this->loadIso3166();
        $this->loadCities();
    }


    private function loadCountries($lvl = 1): void
    {
        $this->output->writeln("Loading Countries from Symfony Intl component");
        $countries = Countries::getNames();
        foreach ($countries as $alpha2=>$name) {
            $countryCode = $alpha2;
            $location = new Location($countryCode, $name, $lvl);
            $location
                ->setCountryCode($alpha2);
//            $errors = $this->validator->validate($location);
//            if (count($errors)) {
//                assert(false, (string) $errors);
//            }

            $this->manager->persist($location);
        }
        $this->flushLevel($lvl);
    }

    // l "states/regions/subcountries" (lvl-2),
    private function loadIso3166($lvl = 2): void
    {
        $json = file_get_contents('https://raw.githubusercontent.com/olahol/iso-3166-2.json/master/iso-3166-2.json');
        // $json = file_get_contents('public/iso-3166-2.json');
        $regions = [];
        $regionsByName = [];

        $countries = [];
        /** @var Location $countryLocation */
        foreach ($this->locationRepository->createQueryBuilder('l')
            ->select('l.countryCode', 'l.id', 'l.countryCode')
            ->where('l.lvl = 1')
                     ->getQuery()->getResult() as $x) {
            $countries[$x['countryCode']] = [
                'id' => $x['id'],
                'code' => $x['countryCode']
            ];
        }
//        findBy(['lvl' => 1]) as $countryLocation) {
//            $countries[$countryLocation->getCountryCode()] = $countryLocation;
//        }
//        dump(array_keys($countries));
        assert(count($countries), "no countries loaded.");

        foreach (json_decode($json) as $countryCode => $country) {
            $this->output->writeln("Reading $countryCode " . count((array)$country->divisions));

            if (!array_key_exists($countryCode, $countries)) {
                continue; // missing TP, East Timor.
            }
            $parentData = $countries[$countryCode];

            $childCount = 0;
            foreach ($country->divisions as $uniqueStateCode => $stateName) {
                $childCount++;
                $stateCode = preg_replace('/.*?-/', '', $uniqueStateCode);

//                dump($uniqueStateCode, $stateName, $lvl);
                $location = (new Location($uniqueStateCode, $stateName, $lvl))
                    ->setCountryCode($parentData['code'])
                    ->setStateCode($stateCode)
                    ->setParent($this->em->getReference(Location::class, $parentData['id']))
                ;
                $this->manager->persist($location);

                $seen[$uniqueStateCode] = $location;

//                $errors = $this->validator->validate($location);
//                if (count($errors)) {
//                    assert(false, $uniqueStateCode . '  ' . (string) $errors);
//                }

                $this->lvlCache[$lvl][$stateName] = $location;
//                try {
//                    $this->flushLevel($lvl);
//                } catch (\Exception $exception) {
//                    dd($uniqueStateCode, $location, $exception);
//                }
            }
//            $parent->setChildCount($childCount);
        }
        $this->flushLevel($lvl);

    }

    public function loadCities(): void
    {
        $lvl = 3;
        $fn = __DIR__ . '/../../data/world-cities.json';
        $json = file_get_contents($fn);
        $data = json_decode($json);
        $this->output->writeln("Reading level $lvl " . count($data));

        $states = [];
        /** @var Location $state */
        foreach ($this->locationRepository->createQueryBuilder('l')
                     ->select('l.name', 'l.id', 'l.countryCode', 'l.stateCode')
                     ->where('l.lvl = 2')
                     ->getQuery()->getResult() as $x) {
            $states[$x['name']] = [
                'id' => $x['id'],
                'stateCode' => $x['stateCode'],
                'countryCode' => $x['countryCode'],
            ];

            // hack
            if ($x['stateCode'] === 'DC') {
                $states['Washington, D.C.'] = $states[$x['name']];
            }
        }

        foreach ($data as $idx => $cityData) {
//            $city = (new City())
//                ->setName($cityData->name)
//                ->setCode($cityData->geonameid)
//                ->setCountry($cityData->country)
//                ->setSubcountry($cityData->subcountry);
//            $this->manager->persist($city);
            // $this->output->writeln(sprintf("%d) Found %s in %s, %s ", $idx, $cityData->name, $cityData->subcountry, $cityData->country));

            // $country = $countriesByName[$data->country];
            if (!array_key_exists($cityData->subcountry, $states)) {
                if ($cityData->country == 'United States') {
//                    dump($cityData);
                }
            } else {
                $parentData = $states[$cityData->subcountry];
                $cityCode = $cityData->geonameid; // unique, could also be based on country / state / cityName
//                $parent->setChildCount($parent->getChildCount() + 1);

                $cityLoc = (new Location($cityCode, $cityData->name, $lvl))
                    ->setStateCode($parentData['stateCode'])
                    ->setCountryCode($parentData['countryCode'])
                    ->setParent($this->em->getReference(Location::class, $parentData['id']));
                $this->manager->persist($cityLoc);
            }
            if ($idx % 2500 == 0) {
                $this->flushLevel($lvl, $idx);
            }
        }
        $this->flushLevel($lvl);
    }

    private function flushLevel(int $lvl, $idx=0): void
    {
        $this->output->writeln("Flushing level $lvl " . $this->levels[$lvl]);
        $this->manager->flush(); // set the IDs
        $this->manager->clear();
        //        $count = $this->locationRepository->count(['lvl'=> $lvl]);
        $count = -1; // $this->locationRepository->count([]);
        $this->output->writeln(sprintf("After level $lvl idx is: %d", $idx));
        if ($lvl) {
            assert($count, "no $lvl locations!");
        } else {
            assert($count === 0, "should be empty");
        }


    }




}
