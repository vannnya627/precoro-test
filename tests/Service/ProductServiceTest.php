<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\Request\ProductRequestDTO;
use App\DTO\Request\UpdateProductRequestDTO;
use App\DTO\Response\ProductResponseDTO;
use App\Entity\Product;
use App\Exception\ProductNotFoundException;
use App\Repository\Interface\ProductRepositoryInterface;
use App\Service\ProductService;
use App\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AllowMockObjectsWithoutExpectations]
class ProductServiceTest extends AbstractTestCase
{
    private ProductRepositoryInterface|MockObject $productRepository;
    private NormalizerInterface|MockObject $normalizer;
    private DenormalizerInterface|MockObject $denormalizer;
    private ProductService $productService;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->normalizer = $this->createMock(NormalizerInterface::class);
        $this->denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->productService = new ProductService($this->productRepository, $this->normalizer, $this->denormalizer);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetOne(): void
    {
        $productId = 1;

        $product = new Product()
            ->setName('name')
            ->setDescription('description')
            ->setPrice(123);

        $this->setEntityId($product, $productId);

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn($product);

        $expectedResponse = ProductResponseDTO::create($product);
        $response = $this->productService->getOne($productId);
        $this->assertEquals($expectedResponse, $response);
    }

    public function testGetOneWhenThrowsExceptionProductNotFound(): void
    {
        $productId = 1;
        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn(null);

        $this->expectException(ProductNotFoundException::class);
        $this->productService->getOne($productId);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetAll(): void
    {
        $product1 = new Product()
            ->setName('name1')
            ->setDescription('description1')
            ->setPrice(123);

        $this->setEntityId($product1, 1);

        $product2 = new Product()
            ->setName('name2')
            ->setDescription('description2')
            ->setPrice(123);

        $this->setEntityId($product2, 1);
        $products = [$product1, $product2];

        $this->productRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($products);

        $expectedResponse = array_map(function (Product $product) {
            return ProductResponseDTO::create($product);
        }, $products);

        $response = $this->productService->getAll();
        $this->assertEquals($expectedResponse, $response);
    }

    public function testGetAllWhenProductIsEmpty(): void
    {
        $products = [];
        $this->productRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($products);

        $expectedResponse = array_map(function (Product $product) {
            return ProductResponseDTO::create($product);
        }, $products);

        $response = $this->productService->getAll();
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testCreate(): void
    {
        $request = new ProductRequestDTO(
            name: 'name',
            description: 'description',
            price: 123
        );

        $this->productRepository->expects($this->once())
            ->method('saveAndCommit')
            ->willReturnCallback(function (Product $savedProduct) use ($request) {
                $this->assertEquals($request->name, $savedProduct->getName());
                $this->assertEquals($request->description, $savedProduct->getDescription());
                $this->assertEquals($request->price, $savedProduct->getPrice());

                $this->setEntityId($savedProduct, 1);
            });

        $response = $this->productService->create($request);

        $this->assertEquals(1, $response->id);
        $this->assertEquals($request->name, $response->name);
        $this->assertEquals($request->description, $response->description);
        $this->assertEquals($request->price, $response->price);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testUpdate(): void
    {
        $productId = 1;
        $request = new UpdateProductRequestDTO(
            name: 'new-name',
            price: 321
        );

        $product = new Product()
            ->setName('old-name')
            ->setDescription('old-description')
            ->setPrice(123);

        $this->setEntityId($product, $productId);

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn($product);

        $dtoAsArray = ['name' => 'new-name', 'price' => 321];
        $this->normalizer->expects($this->once())
            ->method('normalize')
            ->with($request, null, [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            ])
            ->willReturn($dtoAsArray);

        $this->denormalizer->expects($this->once())
            ->method('denormalize')
            ->with($dtoAsArray, Product::class, null, [
                AbstractNormalizer::OBJECT_TO_POPULATE => $product,
            ])
            ->willReturnCallback(function () use ($product, $request) {
                $product->setName($request->name)
                    ->setPrice($request->price);

                return $product;
            });

        $this->productRepository->expects($this->once())
            ->method('saveAndCommit')
            ->willReturnCallback(function (Product $savedProduct) use ($request) {
                $this->assertEquals($request->name, $savedProduct->getName());
                $this->assertEquals($request->price, $savedProduct->getPrice());

                $this->setEntityId($savedProduct, 1);
            });
        $response = $this->productService->update($productId, $request);
        $this->assertEquals(1, $response->id);
        $this->assertEquals($request->name, $response->name);
        $this->assertEquals($product->getDescription(), $response->description);
        $this->assertEquals($request->price, $response->price);
    }

    /**
     * @throws \Throwable
     */
    public function testUpdateWhenThrowsExceptionProductNotFound(): void
    {
        $productId = 1;
        $request = new UpdateProductRequestDTO(name: 'test', price: 100);

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn(null);

        $this->normalizer->expects($this->never())->method('normalize');
        $this->denormalizer->expects($this->never())->method('denormalize');
        $this->productRepository->expects($this->never())->method('saveAndCommit');

        $this->expectException(ProductNotFoundException::class);

        $this->productService->update($productId, $request);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testDelete(): void
    {
        $productId = 1;

        $product = new Product()
            ->setName('name')
            ->setDescription('description')
            ->setPrice(123);

        $this->setEntityId($product, $productId);

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn($product);

        $this->productRepository->expects($this->once())
            ->method('removeAndCommit')
            ->with($product);

        $this->productService->delete($productId);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testDeleteWhenThrowsExceptionProductNotFound(): void
    {
        $productId = 1;

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn(null);

        $this->productRepository->expects($this->never())
            ->method('removeAndCommit');

        $this->expectException(ProductNotFoundException::class);
        $this->productService->delete($productId);
    }
}
