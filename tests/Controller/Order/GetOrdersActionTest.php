<?php

declare(strict_types=1);

namespace App\Tests\Controller\Order;

use App\Tests\AbstractWebTestCase;

class GetOrdersActionTest extends AbstractWebTestCase
{
    public function testGetOrdersSuccess(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createUser($em);

        $product = $this->createProduct($em, 2000);
        $token = $this->getAuthToken($client);

        $this->addProductToCart($client, $token, $product->id, 1);
        $client->request(
            'POST',
            '/api/v1/order',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->addProductToCart($client, $token, $product->id, 2);
        $client->request(
            'POST',
            '/api/v1/order',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $client->request(
            'GET',
            '/api/v1/orders',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('data', $responseContent);

        $this->assertCount(2, $responseContent['data']);

        $order = $responseContent['data'][0];
        $this->assertEquals(4000, $order['totalPrice']);
    }

    public function testGetOrdersEmpty(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createUser($em);

        $token = $this->getAuthToken($client);

        $client->request(
            'GET',
            '/api/v1/orders',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('data', $responseContent);
        $this->assertCount(0, $responseContent['data']);
    }

    public function testGetOrdersUnauthorized(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/orders');

        $this->assertResponseStatusCodeSame(401);
    }
}
