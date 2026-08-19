<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use App\Repository\Interface\OrderRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
final class OrderRepository extends ServiceEntityRepository implements OrderRepositoryInterface
{
    use RepositorySupportTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return list<Order>
     */
    public function findAllByUserId(int $userId): array
    {
        /** @var list<Order> $result */
        $result = $this->createQueryBuilder('o')
            ->leftJoin('o.orderItems', 'oi')
            ->addSelect('oi')
            ->leftJoin('oi.product', 'p')
            ->addSelect('p')
            ->where('o.user = :user_id')
            ->setParameter('user_id', $userId)
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
