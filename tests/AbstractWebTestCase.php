<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use ReflectionClass;
use ReflectionException;

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
}
