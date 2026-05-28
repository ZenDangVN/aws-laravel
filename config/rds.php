<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RDS IAM Authentication
    |--------------------------------------------------------------------------
    |
    | When enabled, database connections using the `rds_mysql` or `rds_pgsql`
    | drivers will authenticate via short-lived IAM tokens instead of a static
    | password. Requires the DB user to have the rds_iam role (MySQL) or an
    | IAM policy granting rds-db:connect (PostgreSQL).
    |
    */

    'iam_auth' => env('RDS_IAM_AUTH', false),

    'driver' => env('RDS_DRIVER', 'mysql'),

    'ca_bundle' => env('RDS_CA_BUNDLE', storage_path('rds/rds-combined-ca-bundle.pem')),

    'aws' => [
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'profile' => env('AWS_PROFILE'),
    ],

];
