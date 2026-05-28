<?php

use App\Database\RdsIamTokenGenerator;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Creates a RdsIamTokenGenerator subclass wired to an in-memory cache store.
 * generateToken() is stubbed to avoid real AWS calls; call count is tracked
 * via a public property on the returned instance.
 */
function makeGenerator(string $fakeToken = 'fake-iam-token'): RdsIamTokenGenerator
{
    $cache = new Repository(new ArrayStore);

    return new class($cache, 'us-east-1', fn () => null, $fakeToken) extends RdsIamTokenGenerator
    {
        public int $generateCallCount = 0;

        public function __construct(
            CacheRepository $cache,
            string $awsRegion,
            mixed $credentialsProvider,
            private readonly string $fakeToken,
        ) {
            parent::__construct($cache, $awsRegion, $credentialsProvider);
        }

        protected function generateToken(string $host, int $port, string $username): string
        {
            $this->generateCallCount++;

            return $this->fakeToken;
        }
    };
}

test('token is returned on first call', function () {
    $generator = makeGenerator('token-abc');

    expect($generator->token('rds.example.com', 3306, 'app_user'))->toBe('token-abc');
});

test('token is served from cache on subsequent calls without re-generating', function () {
    $generator = makeGenerator();

    $generator->token('rds.example.com', 3306, 'app_user');
    $generator->token('rds.example.com', 3306, 'app_user');
    $generator->token('rds.example.com', 3306, 'app_user');

    expect($generator->generateCallCount)->toBe(1);
});

test('different host produces a different cache key and generates a new token', function () {
    $generator = makeGenerator();

    $generator->token('rds-a.example.com', 3306, 'app_user');
    $generator->token('rds-b.example.com', 3306, 'app_user');

    expect($generator->generateCallCount)->toBe(2);
});

test('different username produces a different cache key', function () {
    $generator = makeGenerator();

    $generator->token('rds.example.com', 3306, 'user_a');
    $generator->token('rds.example.com', 3306, 'user_b');

    expect($generator->generateCallCount)->toBe(2);
});

test('different port produces a different cache key', function () {
    $generator = makeGenerator();

    $generator->token('rds.example.com', 3306, 'app_user');
    $generator->token('rds.example.com', 5432, 'app_user');

    expect($generator->generateCallCount)->toBe(2);
});
