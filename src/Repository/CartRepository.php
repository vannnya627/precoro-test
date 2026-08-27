<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\User;
use App\Repository\Interface\CartRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 */
final class CartRepository extends ServiceEntityRepository implements CartRepositoryInterface
{
    use RepositorySupportTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    public function findByUserId(int $userId): ?Cart
    {
        return $this->find($userId);
    }

    public function findCartWithItemsAndProducts(User $user): ?Cart
    {
        /** @var Cart|null $result */
        $result = $this->createQueryBuilder('c')
            ->leftJoin('c.cartItems', 'ci')
            ->addSelect('ci')
            ->leftJoin('ci.product', 'p')
            ->addSelect('p')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }
}
