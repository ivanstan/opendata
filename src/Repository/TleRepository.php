<?php

namespace App\Repository;

use App\Entity\Tle;
use App\Model\PaginationCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TleRepository extends ServiceEntityRepository
{
    public const SORT_ID = 'id';
    public const SORT_NAME = 'name';

    public static $sort = [self::SORT_ID, self::SORT_NAME];

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Tle::class);
    }

    /**
     * @return Tle[]|Collection
     */
    public function fetchAllIndexed()
    {
        return $this->createQueryBuilder('tle', 'tle.satelliteId')
            ->getQuery()
            ->getResult();
    }

    public function collection(
        ?string $search,
        string $sort,
        string $sortDir,
        int $pageSize,
        int $offset
    ): PaginationCollection
    {
        $builder = $this->createQueryBuilder('tle');

        // search
        if ($search) {
            $builder
                ->where(
                    $builder->expr()->orX(
                        $builder->expr()->like('tle.satelliteId', ':search'),
                        $builder->expr()->like('tle.name', ':search')
                    )
                )
                ->setParameter('search', '%'.$search.'%');
        }

        // get total
        $total = \count($builder->getQuery()->getResult());

        // sort
        $builder->orderBy('tle.'.$sort, $sortDir);

        // limit
        $builder->setMaxResults($pageSize);
        $builder->setFirstResult($offset);

        $collection = new PaginationCollection();

        $collection->setCollection($builder->getQuery()->getResult());
        $collection->setTotal($total);

        return $collection;
    }

    /**
     * @return Tle[]|Collection
     */
    public function getByPRN(int $prn)
    {
        $builder = $this->createQueryBuilder('tle');

        $builder
            ->where('tle.prn = :prn')
            ->setParameter('prn', $prn);

        // get total
        $total = \count($builder->getQuery()->getResult());

        $collection = new PaginationCollection();

        $collection->setCollection($builder->getQuery()->getResult());
        $collection->setTotal($total);

        return $collection;
    }
}
