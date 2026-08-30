<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

abstract class AbstractTestCase extends TestCase
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
