<?php

declare(strict_types=1);

namespace App\RateLimiter\Strategy;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;

#[AutoconfigureTag('app.rate_limiter_strategy')]
interface RateLimiterStrategyInterface
{
    public function support(string $policy): bool;

    public function create(Request $request): LimiterInterface;
}
