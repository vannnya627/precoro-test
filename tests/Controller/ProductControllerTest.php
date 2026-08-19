<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Tests\AbstractWebTestCase;
use Doctrine\Persistence\ObjectManager;

class ProductControllerTest extends AbstractWebTestCase
{
    public function testGetProduct(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $product = $this->createProduct($em);

        $client->request(
            'GET',
            '/api/v1/product/'.$product->getId()
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('id', $responseContent);
        $this->assertEquals($product->getId(), $responseContent['id']);
        $this->assertEquals($product->getName(), $responseContent['name']);
        $this->assertEquals($product->getPrice(), $responseContent['price']);
    }

    public function testGetProductNotFound(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/v1/product/99999'
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetProducts(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createProduct($em);
        $this->createProduct($em);

        $client->request(
            'GET',
            '/api/v1/products'
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('data', $responseContent);

        $this->assertCount(2, $responseContent['data']);
    }

    public function testCreateProduct(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $payload = [
            'name' => 'New Product',
            'description' => 'New Desc',
            'price' => 500,
        ];

        $client->request(
            'POST',
            '/api/v1/product',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('id', $responseContent);
        $this->assertEquals('New Product', $responseContent['name']);
        $this->assertEquals(500, $responseContent['price']);

        $productInDb = $em->getRepository(Product::class)->find($responseContent['id']);
        $this->assertNotNull($productInDb);
    }

    public function testCreateProductValidationError(): void
    {
        $client = static::createClient();

        $payload = [
            'name' => 'Ne',
            'description' => 'New Desc',
            'price' => -10,
        ];

        $client->request(
            'POST',
            '/api/v1/product',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(422);

        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('errors', $responseContent);
    }

    public function testUpdateProduct(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $product = $this->createProduct($em);

        $payload = [
            'name' => 'Updated Name',
            'price' => 999,
        ];

        $client->request(
            'PATCH',
            '/api/v1/product/'.$product->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Updated Name', $responseContent['name']);
        $this->assertEquals(999, $responseContent['price']);

        $em->clear();

        $productInDb = $em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('Updated Name', $productInDb->getName());
        $this->assertEquals(999, $productInDb->getPrice());
    }

    public function testUpdateProductNotFound(): void
    {
        $client = static::createClient();

        $payload = ['name' => 'Updated Name'];

        $client->request(
            'PATCH',
            '/api/v1/product/99999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteProduct(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $product = $this->createProduct($em);
        $productId = $product->getId();

        $client->request(
            'DELETE',
            '/api/v1/product/'.$productId
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('product deleted successfully', $responseContent['message']);

        $em->clear();
        $productInDb = $em->getRepository(Product::class)->find($productId);
        $this->assertNull($productInDb);
    }

    public function testDeleteProductNotFound(): void
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/api/v1/product/99999'
        );

        $this->assertResponseStatusCodeSame(404);
    }

    private function createProduct(ObjectManager $em): Product
    {
        $product = new Product()
            ->setName('Test Product')
            ->setDescription('Test Description')
            ->setPrice(100);

        $em->persist($product);
        $em->flush();

        return $product;
    }
}
