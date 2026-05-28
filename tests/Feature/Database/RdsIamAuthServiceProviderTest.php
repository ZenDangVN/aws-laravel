<?php

use App\Database\RdsIamMySqlConnector;
use App\Database\RdsIamPostgresConnector;
use App\Database\RdsIamTokenGenerator;
use App\Providers\AppServiceProvider;

test('rds iam connectors are not registered when RDS_IAM_AUTH is false', function () {
    config(['rds.iam_auth' => false]);

    $provider = new AppServiceProvider(app());
    $provider->register();

    expect(app()->bound('db.connector.mysql'))->toBeFalse()
        ->and(app()->bound('db.connector.pgsql'))->toBeFalse();
});

test('rds iam mysql connector is registered when RDS_IAM_AUTH is true', function () {
    config([
        'rds.iam_auth' => true,
        'rds.aws.region' => 'us-east-1',
    ]);

    $provider = new AppServiceProvider(app());
    $provider->register();

    expect(app()->bound('db.connector.mysql'))->toBeTrue()
        ->and(app()->make('db.connector.mysql'))->toBeInstanceOf(RdsIamMySqlConnector::class);
});

test('rds iam postgres connector is registered when RDS_IAM_AUTH is true', function () {
    config([
        'rds.iam_auth' => true,
        'rds.aws.region' => 'us-east-1',
    ]);

    $provider = new AppServiceProvider(app());
    $provider->register();

    expect(app()->bound('db.connector.pgsql'))->toBeTrue()
        ->and(app()->make('db.connector.pgsql'))->toBeInstanceOf(RdsIamPostgresConnector::class);
});

test('token generator is a singleton', function () {
    config([
        'rds.iam_auth' => true,
        'rds.aws.region' => 'us-east-1',
    ]);

    $provider = new AppServiceProvider(app());
    $provider->register();

    $a = app(RdsIamTokenGenerator::class);
    $b = app(RdsIamTokenGenerator::class);

    expect($a)->toBe($b);
});
