<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Tests\AbstractWebTestCase;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class OrderControllerTest extends AbstractWebTestCase
{
    public function testCreateOrderSuccess(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createUser($em);

        $product = $this->createProduct($em, 'Phone', 500);
        $token = $this->getAuthToken($client);

        $this->addProductToCart($client, $token, $product->id, 2);

        $client->request(
            'POST',
            '/api/v1/order',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ]
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
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ]
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateOrderUnauthorized(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/order'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetOrdersSuccess(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createUser($em);

        $product = $this->createProduct($em, 'Laptop', 2000);
        $token = $this->getAuthToken($client);

        $this->addProductToCart($client, $token, $product->id, 1);
        $client->request(
            'POST',
            '/api/v1/order',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token]
        );

        $this->addProductToCart($client, $token, $product->id, 2);
        $client->request(
            'POST',
            '/api/v1/order',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token]
        );

        $client->request(
            'GET',
            '/api/v1/orders',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token]
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
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ]
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

    private function createUser(ObjectManager $em): void
    {
        $passwordHasherFactory = static::getContainer()->get(PasswordHasherFactoryInterface::class);
        $hasher = $passwordHasherFactory->getPasswordHasher(User::class);
        $passwordHash = $hasher->hash('password');

        $user = User::createCustomer('user@test.com', $passwordHash);

        $em->persist($user);
        $em->flush();
    }

    private function createProduct(ObjectManager $em, string $name = 'Test Product', int $price = 100): Product
    {
        $product = Product::create($name, 'Test Description', $price);

        $em->persist($product);
        $em->flush();

        return $product;
    }

    private function getAuthToken(KernelBrowser $client): string
    {
        $client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'user@test.com',
                'password' => 'password',
            ])
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        return $response['token'];
    }

    private function addProductToCart(KernelBrowser $client, string $token, int $productId, int $quantity = 1): void
    {
        $client->request(
            'POST',
            '/api/v1/cart',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ],
            json_encode([
                'productId' => $productId,
                'quantity' => $quantity,
            ])
        );
    }
}
