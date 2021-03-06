<?php

namespace App\Command;

use App\Entity\Tle;
use App\Model\TleModel;
use App\Repository\TleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportTleCommand extends Command
{
    private const BATCH_SIZE = 50;

    private const SOURCES = [
        'https://www.celestrak.com/NORAD/elements/tle-new.txt',
        'https://www.celestrak.com/NORAD/elements/stations.txt',
        'https://www.celestrak.com/NORAD/elements/visual.txt',
        'https://www.celestrak.com/NORAD/elements/active.txt',
        'https://www.celestrak.com/NORAD/elements/analyst.txt',
        'https://www.celestrak.com/NORAD/elements/1999-025.txt',
        'https://www.celestrak.com/NORAD/elements/iridium-33-debris.txt',
        'https://www.celestrak.com/NORAD/elements/cosmos-2251-debris.txt',
        'https://www.celestrak.com/NORAD/elements/2012-044.txt',
        'https://www.celestrak.com/NORAD/elements/weather.txt',
        'https://www.celestrak.com/NORAD/elements/noaa.txt',
        'https://www.celestrak.com/NORAD/elements/goes.txt',
        'https://www.celestrak.com/NORAD/elements/resource.txt',
        'https://www.celestrak.com/NORAD/elements/sarsat.txt',
        'https://www.celestrak.com/NORAD/elements/dmc.txt',
        'https://www.celestrak.com/NORAD/elements/tdrss.txt',
        'https://www.celestrak.com/NORAD/elements/argos.txt',
        'https://www.celestrak.com/NORAD/elements/planet.txt',
        'https://www.celestrak.com/NORAD/elements/spire.txt',
        'https://www.celestrak.com/NORAD/elements/geo.txt',
        'https://www.celestrak.com/NORAD/elements/intelsat.txt',
        'https://www.celestrak.com/NORAD/elements/ses.txt',
        'https://www.celestrak.com/NORAD/elements/iridium.txt',
        'https://www.celestrak.com/NORAD/elements/iridium-NEXT.txt',
        'https://www.celestrak.com/NORAD/elements/orbcomm.txt',
        'https://www.celestrak.com/NORAD/elements/globalstar.txt',
        'https://www.celestrak.com/NORAD/elements/amateur.txt',
        'https://www.celestrak.com/NORAD/elements/x-comm.txt',
        'https://www.celestrak.com/NORAD/elements/other-comm.txt',
        'https://www.celestrak.com/NORAD/elements/gorizont.txt',
        'https://www.celestrak.com/NORAD/elements/raduga.txt',
        'https://www.celestrak.com/NORAD/elements/molniya.txt',
        'https://www.celestrak.com/NORAD/elements/gps-ops.txt',
        'https://www.celestrak.com/NORAD/elements/glo-ops.txt',
        'https://www.celestrak.com/NORAD/elements/galileo.txt',
        'https://www.celestrak.com/NORAD/elements/beidou.txt',
        'https://www.celestrak.com/NORAD/elements/sbas.txt',
        'https://www.celestrak.com/NORAD/elements/nnss.txt',
        'https://www.celestrak.com/NORAD/elements/musson.txt',
        'https://www.celestrak.com/NORAD/elements/science.txt',
        'https://www.celestrak.com/NORAD/elements/geodetic.txt',
        'https://www.celestrak.com/NORAD/elements/engineering.txt',
        'https://www.celestrak.com/NORAD/elements/education.txt',
        'https://www.celestrak.com/NORAD/elements/military.txt',
        'https://www.celestrak.com/NORAD/elements/radar.txt',
        'https://www.celestrak.com/NORAD/elements/cubesat.txt',
        'https://www.celestrak.com/NORAD/elements/other.txt',
    ];

    /** @var EntityManagerInterface */
    private $em;

    /** @var TleModel[] */
    private $updateQueue = [];

    /** @var TleModel[] */
    private $insertQueue = [];

    /** @var Tle[] */
    private $satellites = [];

    /** @var TleRepository */
    private $repository;

    /** @var OutputInterface */
    private $output;

    public function __construct(
        EntityManagerInterface $em,
        TleRepository $repository
    ) {
        parent::__construct();
        $this->em = $em;
        $this->repository = $repository;
    }

    protected function configure(): void
    {
        $this->setName('import:tle');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;
        $this->satellites = $this->repository->fetchAllIndexed();

        $totalInsert = 0;
        $totalUpdate = 0;

        foreach (self::SOURCES as $source) {
            $data = file_get_contents($source);

            $data = explode("\n", $data);
            $data = array_filter($data);
            $raw = array_chunk($data, 3);

            foreach ($raw as $item) {
                if (!isset($item[1], $item[2])) {
                    continue;
                }

                $tle = new TleModel($this->trim($item[1]), $this->trim($item[2]), $this->trim($item[0]));

                if (array_key_exists($tle->getId(), $this->satellites)) {
                    $this->updateQueue[$tle->getId()] = $tle;
                } else {
                    $this->insertQueue[$tle->getId()] = $tle;
                }
            }

            $totalInsert += \count($this->insertQueue);
            $totalUpdate += \count($this->updateQueue);

            $this->flushInsert();
            $this->flushUpdate();
        }

        $this->updatePRN();

        $this->output->writeln(\sprintf('<info>Inserted %d TLE records to database</info>', $totalInsert));
        $this->output->writeln(\sprintf('<info>Updated %d TLE records in database</info>', $totalUpdate));
    }

    protected function flushInsert(): void
    {
        $counter = 0;
        foreach ($this->insertQueue as $model) {
            $tle = new Tle();
            $tle->setSatelliteId($model->getId());
            $tle->setLine1($model->getLine1());
            $tle->setLine2($model->getLine2());
            $tle->setName($model->getName());

            $this->satellites[$model->getId()] = $tle;
            $this->em->persist($tle);
            $this->output->writeln(\sprintf('<info>TLE record queued for insert: %d</info>', $model->getId()));

            if (($counter % self::BATCH_SIZE) === 0) {
                $this->em->flush();
            }
            ++$counter;
        }

        $this->em->flush();

        $this->insertQueue = [];
    }

    protected function flushUpdate(): void
    {
        $counter = 0;
        foreach ($this->updateQueue as $model) {
            $tle = $this->satellites[$model->getId()];
            $tle->setLine1($model->getLine1());
            $tle->setLine2($model->getLine2());
            $tle->setName($model->getName());
            $this->output->writeln(\sprintf('<info>TLE record queued for update: %d</info>', $model->getId()));

            if (($counter % self::BATCH_SIZE) === 0) {
                $this->em->flush();
            }
            ++$counter;
        }
        $this->em->flush();

        $this->updateQueue = [];
    }

    protected function trim(string $string): string
    {
        $string = str_replace(["/\r\n/", "/\r/", "/\n/"], '', $string);

        return trim($string);
    }

    protected function updatePRN(): void
    {
        $query = $this->em->createQuery('SELECT tle FROM App\Entity\Tle tle WHERE tle.name LIKE \'%PRN%\'');
        $result = $query->getResult();

        /** @var Tle $tle */
        foreach ($result as $tle) {
            $tle->setPRN($this->extractPRN($tle->getName()));
        }

        $this->em->flush();
    }

    protected function extractPRN(string $name): ?int
    {
        preg_match_all('/PRN\s(.\d+)/i', $name, $matches);

        $prn = (int)$matches[1][0] ?? 0;

        if ($prn === 0) {
            $prn = null;
        }

        return $prn;
    }
}
