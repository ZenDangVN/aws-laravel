<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;
use PDO;

final class RdsIamPostgresConnector extends PostgresConnector
{
    public function __construct(
        private readonly RdsIamTokenGenerator $tokenGenerator,
    ) {}

    public function connect(array $config): PDO
    {
        $config['password'] = $this->tokenGenerator->token(
            host: $config['host'],
            port: (int) ($config['port'] ?? 5432),
            username: $config['username'],
        );

        return parent::connect($config);
    }
}
