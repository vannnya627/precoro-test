<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use App\Exception\ProductNotFoundException;
use App\Repository\Interface\ProductRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
final class ProductRepository extends ServiceEntityRepository implements ProductRepositoryInterface
{
    use RepositorySupportTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function getById(int $productId): Product
    {
        return $this->find($productId) ?? throw new ProductNotFoundException($productId);
    }

    /**
     * @return list<Product>
     */
    public function findProducts(): array
    {
        return $this->findAll();
    }
}
