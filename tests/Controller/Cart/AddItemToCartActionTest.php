<?php

declare(strict_types=1);

namespace App\Tests\Controller\Cart;

use App\Tests\AbstractWebTestCase;

class AddItemToCartActionTest extends AbstractWebTestCase
{
    public function testAddItemToCartSuccess(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createUser($em);
        $product = $this->createProduct($em);

        $token = $this->getAuthToken($client);

        $payload = [
            'productId' => $product->id,
            'quantity' => 2,
        ];

        $client->request(
            'POST',
            '/api/v1/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode($payload),
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('item added success', $responseContent['message']);
    }

    public function testAddItemToCartUnauthorized(): void
    {
        $client = static::createClient();

        $payload = [
            'productId' => 1,
            'quantity' => 1,
        ];

        $client->request(
            'POST',
            '/api/v1/cart',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAddItemToCartProductNotFound(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createUser($em);
        $token = $this->getAuthToken($client);

        $payload = [
            'productId' => 99999,
            'quantity' => 1,
        ];

        $client->request(
            'POST',
            '/api/v1/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
