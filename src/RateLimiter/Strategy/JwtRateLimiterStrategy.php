<?php

declare(strict_types=1);

namespace App\RateLimiter\Strategy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class JwtRateLimiterStrategy implements RateLimiterStrategyInterface
{
    public function __construct(
        private RateLimiterFactory $jwtLimiter,
    ) {}

    public function support(string $policy): bool
    {
        return 'jwt' === $policy;
    }

    public function create(Request $request): LimiterInterface
    {
        $token = substr((string) $request->headers->get('Authorization'), 7);
        $key = $token ? md5($token) : (string) $request->getClientIp();

        return  $this->jwtLimiter->create($key);
    }
}
