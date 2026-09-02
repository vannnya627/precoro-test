<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Entity\User;
use App\Tests\AbstractWebTestCase;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class LoginActionTest extends AbstractWebTestCase
{
    public function testLoginSuccess(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createUser($em);

        $payload = [
            'email' => 'test@test.com',
            'password' => 'password',
        ];

        $client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('token', $responseContent);
        $this->assertIsString($responseContent['token']);
    }

    public function testLoginInvalidCredentials(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->createUser($em);

        $payload = [
            'email' => 'test@test.com',
            'password' => 'wrong_password',
        ];

        $client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(401);

        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseContent);
    }

    public function testLoginUserNotFound(): void
    {
        $client = static::createClient();

        $payload = [
            'email' => 'not_exist@test.com',
            'password' => 'password',
        ];

        $client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testHttpExceptionListenerTest(): void
    {
        $client = static::createClient();
        $badUri = '/api/v1/auth/fake-route';
        $payload = [
            'email' => 'not_exist@test.com',
            'password' => 'password',
        ];

        $client->request(
            'POST',
            $badUri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(404);
    }

    //    private function createUser(ObjectManager $em): void
    //    {
    //        $passwordHasherFactory = static::getContainer()->get(PasswordHasherFactoryInterface::class);
    //        $hasher = $passwordHasherFactory->getPasswordHasher(User::class);
    //        $passwordHash = $hasher->hash('password');
    //
    //        $user = User::createCustomer('test@test.com', $passwordHash);
    //
    //        $em->persist($user);
    //        $em->flush();
    //    }
}
