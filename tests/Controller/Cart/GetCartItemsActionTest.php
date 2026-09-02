<?php

declare(strict_types=1);

namespace App\Tests\Controller\Cart;

use App\Tests\AbstractWebTestCase;

class GetCartItemsActionTest extends AbstractWebTestCase
{
    public function testGetCartItemsSuccess(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createUser($em);
        $product = $this->createProduct($em);
        $token = $this->getAuthToken($client);

        $client->request(
            'POST',
            '/api/v1/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'productId' => $product->id,
                'quantity' => 3,
            ]),
        );
        $this->assertResponseIsSuccessful();

        $client->request(
            'GET',
            '/api/v1/cart',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('data', $responseContent);
        $this->assertCount(1, $responseContent['data']);

        $item = $responseContent['data'][0];
        $this->assertEquals($product->id, $item['productId']);
        $this->assertEquals(3, $item['quantity']);
    }

    public function testGetCartItemsUnauthorized(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/cart');

        $this->assertResponseStatusCodeSame(401);
    }
}
