<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\ProductRequestDTO;
use App\DTO\Request\UpdateProductRequestDTO;
use App\DTO\Response\ProductResponseDTO;
use App\Entity\Product;
use App\Exception\ProductNotFoundException;
use App\Repository\Interface\ProductRepositoryInterface;
use App\Service\Interface\ProductServiceInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class ProductService implements ProductServiceInterface
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
    ) {
    }

    public function getOne(int $productId): ProductResponseDTO
    {
        $product = $this->productRepository->findById($productId);
        if (null === $product) {
            throw new ProductNotFoundException();
        }

        return ProductResponseDTO::create($product);
    }

    /**
     * @throws \Throwable
     */
    public function create(ProductRequestDTO $request): ProductResponseDTO
    {
        $product = new Product()
        ->setName($request->name)
        ->setDescription($request->description)
        ->setPrice($request->price);

        $this->productRepository->saveAndCommit($product);

        return ProductResponseDTO::create($product);
    }

    /**
     * @return list<ProductResponseDTO>
     */
    public function getAll(): array
    {
        $products = $this->productRepository->findAll();

        return array_map(function (Product $product) {
            return ProductResponseDTO::create($product);
        }, $products);
    }

    /**
     * @throws \Throwable
     */
    public function update(int $productId, UpdateProductRequestDTO $request): ProductResponseDTO
    {
        $product = $this->productRepository->findById($productId);

        if (null === $product) {
            throw new ProductNotFoundException();
        }

        $dtoAsArray = $this->normalizer->normalize($request, null, [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);

        $this->denormalizer->denormalize($dtoAsArray, Product::class, null, [
            AbstractNormalizer::OBJECT_TO_POPULATE => $product,
        ]);

        $this->productRepository->saveAndCommit($product);

        return ProductResponseDTO::create($product);
    }

    /**
     * @throws \Throwable
     */
    public function delete(int $productId): void
    {
        $product = $this->productRepository->findById($productId);

        if (null === $product) {
            throw new ProductNotFoundException();
        }

        $this->productRepository->removeAndCommit($product);
    }
}
