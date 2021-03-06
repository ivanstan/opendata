<?php

namespace App\DataFixtures;

use App\Entity\Tle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class TleFixtures extends Fixture
{
    private static $satelliteId = 43550;
    private static $name = '1998-067NY';
    private static $line1 = '1 43550U 98067NY  18321.21573649  .00013513  00000-0  18402-3 0  9990';
    private static $line2 = '2 43550  51.6389 334.0891 0005785  67.0956 293.0647 15.57860024 19804';
    public static $date = '2018-11-17T05:10:39+00:00';

    public static function create(): Tle
    {
        $tle = new Tle();
        $tle->setSatelliteId(self::$satelliteId);
        $tle->setName(self::$name);
        $tle->setLine1(self::$line1);
        $tle->setLine2(self::$line2);

        return $tle;
    }

    public function load(ObjectManager $manager): void
    {
        // create single record
        $manager->persist(self::create());

        // create additional nine records with dummy satelliteIds
        for ($satelliteId = 1; $satelliteId < 10; $satelliteId++) {
            $tle = self::create();
            $tle->setSatelliteId($satelliteId);

            $manager->persist($tle);
        }

        $manager->flush();
    }
}
