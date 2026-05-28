<?php

namespace App\Database;

use Aws\Rds\AuthTokenGenerator;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class RdsIamTokenGenerator
{
    /**
     * Cache for 14 minutes — IAM tokens expire after 15 minutes.
     */
    private const TOKEN_TTL_SECONDS = 840;

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly string $awsRegion,
        private readonly mixed $credentialsProvider,
    ) {}

    /**
     * Return a cached or freshly generated IAM auth token for the given RDS endpoint.
     */
    public function token(string $host, int $port, string $username): string
    {
        $cacheKey = "rds_iam_token:{$host}:{$port}:{$username}";

        return $this->cache->remember(
            $cacheKey,
            self::TOKEN_TTL_SECONDS,
            fn (): string => $this->generateToken($host, $port, $username),
        );
    }

    protected function generateToken(string $host, int $port, string $username): string
    {
        $generator = new AuthTokenGenerator($this->credentialsProvider);

        return $generator->createToken("{$host}:{$port}", $this->awsRegion, $username);
    }
}
