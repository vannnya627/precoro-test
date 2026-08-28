<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Tests\AbstractWebTestCase;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class CartControllerTest extends AbstractWebTestCase
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
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ],
            json_encode($payload)
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
            json_encode($payload)
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
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetProductsSuccess(): void
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
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ],
            json_encode([
                'productId' => $product->id,
                'quantity' => 3,
            ])
        );
        $this->assertResponseIsSuccessful();

        $client->request(
            'GET',
            '/api/v1/cart',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token]
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

    public function testGetProductsUnauthorized(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/cart');

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

    private function createProduct(ObjectManager $em): Product
    {
        $product = Product::create('Test Product', 'Test Description', 100);

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
            json_encode(['email' => 'user@test.com', 'password' => 'password'])
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        return $response['token'];
    }
}
