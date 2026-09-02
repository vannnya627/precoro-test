<?php

declare(strict_types=1);

namespace App\Tests\Controller\Product;

use App\Tests\AbstractWebTestCase;

class GetProductsActionTest extends AbstractWebTestCase
{
    public function testGetProducts(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createProduct($em);
        $this->createProduct($em);

        $client->request(
            'GET',
            '/api/v1/products',
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('data', $responseContent);

        $this->assertCount(2, $responseContent['data']);
    }
}
