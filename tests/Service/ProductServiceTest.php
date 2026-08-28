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

#[AllowMockObjectsWithoutExpectations]
class ProductServiceTest extends AbstractTestCase
{
    private ProductRepositoryInterface|MockObject $productRepository;
    private ProductService $productService;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->productService = new ProductService($this->productRepository);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetOne(): void
    {
        $product = $this->createProduct();
        $productId = $product->id;

        $this->productRepository->expects($this->once())
            ->method('getById')
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
            ->method('getById')
            ->with($productId)
            ->willThrowException(new ProductNotFoundException());

        $this->expectException(ProductNotFoundException::class);
        $this->productService->getOne($productId);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetAll(): void
    {
        $product1 = $this->createProduct();
        $product2 = $this->createProduct();

        $products = [$product1, $product2];

        $this->productRepository->expects($this->once())
            ->method('findProducts')
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
            ->method('findProducts')
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
                $this->assertEquals($request->name, $savedProduct->name);
                $this->assertEquals($request->description, $savedProduct->description);
                $this->assertEquals($request->price, $savedProduct->price);

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
        $request = new UpdateProductRequestDTO(
            name: 'new-name',
            price: 321
        );

        $product = $this->createProduct();
        $productId = $product->id;

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willReturn($product);

        $this->productRepository->expects($this->once())
            ->method('saveAndCommit')
            ->willReturnCallback(function (Product $savedProduct) use ($request) {
                $this->assertEquals($request->name, $savedProduct->name);
                $this->assertEquals($request->price, $savedProduct->price);

                $this->setEntityId($savedProduct, 1);
            });
        $response = $this->productService->update($productId, $request);
        $this->assertEquals(1, $response->id);
        $this->assertEquals($request->name, $response->name);
        $this->assertEquals($product->description, $response->description);
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
            ->method('getById')
            ->with($productId)
            ->willThrowException(new ProductNotFoundException());

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
        $product = $this->createProduct();
        $productId = $product->id;

        $this->productRepository->expects($this->once())
            ->method('getById')
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
            ->method('getById')
            ->with($productId)
            ->willThrowException(new ProductNotFoundException());

        $this->productRepository->expects($this->never())
            ->method('removeAndCommit');

        $this->expectException(ProductNotFoundException::class);
        $this->productService->delete($productId);
    }

    /**
     * @throws \ReflectionException
     */
    private function createProduct(): Product
    {
        $productId = 1;
        $product = Product::create('Test Product', 'Test Description', 123);
        $this->setEntityId($product, $productId);

        return $product;
    }
}
