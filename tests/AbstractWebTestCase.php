<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Product;
use App\Entity\User;
use App\ValueObject\Email;
use App\ValueObject\Price;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

abstract class AbstractWebTestCase extends WebTestCase
{
    /**
     * @throws ReflectionException
     */
    public function setEntityId(object $entity, int|string $value, string $idField = 'id'): void
    {
        $class = new ReflectionClass($entity);
        $property = $class->getProperty($idField);
        $property->setValue($entity, $value);
    }

    public function createUser(ObjectManager $em): void
    {
        $passwordHasherFactory = static::getContainer()->get(PasswordHasherFactoryInterface::class);
        $hasher = $passwordHasherFactory->getPasswordHasher(User::class);
        $passwordHash = $hasher->hash('password');

        $user = User::createCustomer(Email::create('test@test.com'), $passwordHash);

        $em->persist($user);
        $em->flush();
    }

    public function createProduct(ObjectManager $em, int $price = 200): Product
    {
        $product = Product::create('Test Product', 'Test Description', Price::create($price));

        $em->persist($product);
        $em->flush();

        return $product;
    }

    public function getAuthToken(KernelBrowser $client): string
    {
        $client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'test@test.com', 'password' => 'password']),
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        return $response['token'];
    }

    public function addProductToCart(KernelBrowser $client, string $token, int $productId, int $quantity = 1): void
    {
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
                'productId' => $productId,
                'quantity' => $quantity,
            ]),
        );
    }
}
