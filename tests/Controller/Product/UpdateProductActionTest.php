<?php

declare(strict_types=1);

namespace App\Tests\Controller\Product;

use App\Entity\Product;
use App\Tests\AbstractWebTestCase;

class UpdateProductActionTest extends AbstractWebTestCase
{
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
            '/api/v1/product/' . $product->id,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Updated Name', $responseContent['name']);
        $this->assertEquals(999, $responseContent['price']);

        $em->clear();

        $productInDb = $em->getRepository(Product::class)->find($product->id);
        $this->assertEquals('Updated Name', $productInDb->name);
        $this->assertEquals(999, $productInDb->price);
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
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
