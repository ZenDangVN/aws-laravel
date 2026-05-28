<?php

namespace App\Database;

use Illuminate\Database\Connectors\MySqlConnector;
use PDO;

final class RdsIamMySqlConnector extends MySqlConnector
{
    public function __construct(
        private readonly RdsIamTokenGenerator $tokenGenerator,
    ) {}

    public function connect(array $config): PDO
    {
        $config['password'] = $this->tokenGenerator->token(
            host: $config['host'],
            port: (int) ($config['port'] ?? 3306),
            username: $config['username'],
        );

        return parent::connect($config);
    }
}
