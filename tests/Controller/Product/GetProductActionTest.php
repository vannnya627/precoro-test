<?php

declare(strict_types=1);

namespace App\Tests\Controller\Product;

use App\Tests\AbstractWebTestCase;

class GetProductActionTest extends AbstractWebTestCase
{
    public function testGetProduct(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $product = $this->createProduct($em);

        $client->request(
            'GET',
            '/api/v1/product/' . $product->id,
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('id', $responseContent);
        $this->assertEquals($product->id, $responseContent['id']);
        $this->assertEquals($product->name, $responseContent['name']);
        $this->assertEquals($product->price, $responseContent['price']);
    }

    public function testGetProductNotFound(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/v1/product/99999',
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
