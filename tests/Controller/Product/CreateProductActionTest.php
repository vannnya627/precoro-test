<?php

declare(strict_types=1);

namespace App\Tests\Controller\Product;

use App\Entity\Product;
use App\Tests\AbstractWebTestCase;

class CreateProductActionTest extends AbstractWebTestCase
{
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
            json_encode($payload),
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
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(422);

        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('context', $responseContent);
    }
}
