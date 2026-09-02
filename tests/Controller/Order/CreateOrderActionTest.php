<?php

declare(strict_types=1);

namespace App\Tests\Controller\Order;

use App\Tests\AbstractWebTestCase;

class CreateOrderActionTest extends AbstractWebTestCase
{
    public function testCreateOrderSuccess(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createUser($em);

        $product = $this->createProduct($em, 500);
        $token = $this->getAuthToken($client);

        $this->addProductToCart($client, $token, $product->id, 2);

        $client->request(
            'POST',
            '/api/v1/order',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('id', $responseContent);
        $this->assertEquals(1000, $responseContent['totalPrice']);
        $this->assertEquals('NEW', $responseContent['status']);

        $this->assertArrayHasKey('orderItems', $responseContent);
        $this->assertCount(1, $responseContent['orderItems']);
        $this->assertEquals($product->id, $responseContent['orderItems'][0]['productId']);
    }

    public function testCreateOrderEmptyCart(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createUser($em);

        $token = $this->getAuthToken($client);

        $client->request(
            'POST',
            '/api/v1/order',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateOrderUnauthorized(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/order',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
