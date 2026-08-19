<?php

declare(strict_types=1);

namespace App\ExceptionHandler;

final class ExceptionMappingResolver
{
    /**
     * @var ExceptionMappingDTO[]
     */
    private array $mappings = [];

    /**
     * @param array<string, array{code: int,type: string, loggable?: bool}> $mappings
     */
    public function __construct(array $mappings)
    {
        foreach ($mappings as $class => $mapping) {
            if (empty($mapping['code'])) {
                throw new \InvalidArgumentException('Missing mapping code');
            }

            $this->addMapping(
                class: $class,
                type: $mapping['type'],
                code: $mapping['code'],
                loggable: $mapping['loggable'] ?? false,
            );
        }
    }

    public function resolve(string $throwableClass): ?ExceptionMappingDTO
    {
        $foundMapping = null;

        foreach ($this->mappings as $class => $mapping) {
            if ($throwableClass === $class || is_subclass_of($throwableClass, $class)) {
                $foundMapping = $mapping;
                break;
            }
        }

        return $foundMapping;
    }

    private function addMapping(string $class, string $type, int $code, bool $loggable): void
    {
        $this->mappings[$class] = new ExceptionMappingDTO($type, $code, $loggable);
    }
}
