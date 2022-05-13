<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\RateLimiter\Storage;

use Symfony\Component\RateLimiter\LimiterStateInterface;

/**
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class InMemoryStorage implements StorageInterface
{
    private array $buckets = [];

    public function save(LimiterStateInterface $limiterState): void
    {
        $expireAt = $this->getExpireAt($limiterState);
        $this->buckets[$limiterState->getId()] = [$expireAt, serialize($limiterState)];
    }

    public function fetch(string $limiterStateId): ?LimiterStateInterface
    {
        if (!isset($this->buckets[$limiterStateId])) {
            return null;
        }

        [$expireAt, $limiterState] = $this->buckets[$limiterStateId];
        if (null !== $expireAt && $expireAt <= microtime(true)) {
            unset($this->buckets[$limiterStateId]);

            return null;
        }

        return unserialize($limiterState);
    }

    public function delete(string $limiterStateId): void
    {
        if (!isset($this->buckets[$limiterStateId])) {
            return;
        }

        unset($this->buckets[$limiterStateId]);
    }

    private function getExpireAt(LimiterStateInterface $limiterState): ?float
    {
        if (isset($this->buckets[$limiterState->getId()])) {
            return $this->buckets[$limiterState->getId()][0];
        }

        $expireSeconds = $limiterState->getExpirationTime();

        return null === $expireSeconds ? null : microtime(true) + $expireSeconds;
    }
}
