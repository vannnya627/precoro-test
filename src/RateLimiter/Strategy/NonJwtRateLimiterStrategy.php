<?php

declare(strict_types=1);

namespace App\RateLimiter\Strategy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class NonJwtRateLimiterStrategy implements RateLimiterStrategyInterface
{
    public function __construct(
        private RateLimiterFactory $nonJwtLimiter,
    ) {}

    public function support(string $policy): bool
    {
        return 'non_jwt' === $policy;
    }

    public function create(Request $request): LimiterInterface
    {
        return  $this->nonJwtLimiter->create((string) $request->getClientIp());
    }
}
