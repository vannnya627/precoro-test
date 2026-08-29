<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\AbstractWebTestCase;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AuthControllerTest extends AbstractWebTestCase
{
    public function testSignUp(): void
    {
        $client = static::createClient();

        $em = static::getContainer()->get('doctrine')->getManager();

        $payload = [
            'email' => 'test@test.com',
            'password' => 'password',
        ];

        $client->request(
            'POST',
            '/api/v1/auth/signUp',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);

        $this->assertArrayHasKey('email', $responseContent);
        $this->assertEquals($payload['email'], $responseContent['email']);

        $this->assertArrayHasKey('userId', $responseContent);
        $this->assertIsInt($responseContent['userId']);

        $this->assertArrayHasKey('token', $responseContent);
        $this->assertIsString($responseContent['token']);

        $userInDb = $em->getRepository(User::class)->findOneBy(['email' => $payload['email']]);
        $this->assertNotNull($userInDb);

        $this->assertNotEquals($payload['password'], $userInDb->getPassword());
    }

    public function testSignUpThrowsExceptionUserAlreadyExcists(): void
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
            '/api/v1/auth/signUp',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(409);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json');

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
    }

    public function testSignUpThrowsExceptionValidationError(): void
    {
        $client = static::createClient();

        $payload = [
            'email' => 'testtest.com',
            'password' => 'pas',
        ];

        $client->request(
            'POST',
            '/api/v1/auth/signUp',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json');

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);

        $this->assertArrayHasKey('context', $responseContent);
        $this->assertIsArray($responseContent['context']);

        $this->assertArrayHasKey('email', $responseContent['context']);
        $this->assertIsArray($responseContent['context']['email']);
        $this->assertArrayHasKey('password', $responseContent['context']);
        $this->assertIsArray($responseContent['context']['password']);
    }

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
            json_encode($payload)
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
            json_encode($payload)
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
            json_encode($payload)
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
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(404);
    }

    private function createUser(ObjectManager $em): void
    {
        $passwordHasherFactory = static::getContainer()->get(PasswordHasherFactoryInterface::class);
        $hasher = $passwordHasherFactory->getPasswordHasher(User::class);
        $passwordHash = $hasher->hash('password');

        $user = User::createCustomer('test@test.com', $passwordHash);

        $em->persist($user);
        $em->flush();
    }
}
