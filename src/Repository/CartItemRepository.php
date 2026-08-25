<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\Interface\CartItemRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 */
final class CartItemRepository extends ServiceEntityRepository implements CartItemRepositoryInterface
{
    use RepositorySupportTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    /**
     * @return list<CartItem>
     */
    public function findWithProducts(User $user): array
    {
        /** @var list<CartItem> $result */
        $result = $this->createQueryBuilder('ci')
            ->innerJoin('ci.cart', 'c')
            ->leftJoin('ci.product', 'p')
            ->addSelect('p')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findByCartAndProduct(Cart $cart, Product $product): ?CartItem
    {
        return $this->findOneBy(['cart' => $cart, 'product' => $product]);
    }
}
