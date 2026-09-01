<?php

declare(strict_types=1);

namespace App\RateLimiter;

use App\RateLimiter\Strategy\RateLimiterStrategyInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;

final readonly class RateLimiterRegistry
{
    /**
     * @param iterable<RateLimiterStrategyInterface> $strategies
     */
    public function __construct(
        #[AutowireIterator('app.rate_limiter_strategy')]
        public iterable $strategies,
    ) {}

    public function getLimiter(string $policy, Request $request): LimiterInterface
    {
        /** @var RateLimiterStrategyInterface $strategy */
        foreach ($this->strategies as $strategy) {
            if ($strategy->support($policy)) {
                return $strategy->create($request);
            }
        }
        throw new RuntimeException(sprintf('Rate limiter strategy for policy "%s" not found.', $policy));
    }
}
