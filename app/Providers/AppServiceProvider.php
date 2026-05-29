<?php

namespace App\Providers;

use App\Database\RdsIamMySqlConnector;
use App\Database\RdsIamPostgresConnector;
use App\Database\RdsIamTokenGenerator;
use Aws\Credentials\CredentialProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->ensureHomeIsSet();
        $this->registerRdsIamConnectors();
    }

    /**
     * PHP built-in servers (e.g. Herd) may not set HOME in the environment.
     * The AWS SDK requires HOME to locate ~/.aws/config for both RDS and S3.
     * Must run before any AWS SDK client is constructed.
     */
    private function ensureHomeIsSet(): void
    {
        if (! getenv('HOME') && function_exists('posix_getuid')) {
            $passwd = posix_getpwuid(posix_getuid());
            if ($passwd !== false) {
                putenv('HOME='.$passwd['dir']);
            }
        }
    }

    /**
     * Bind RDS IAM-authenticated connectors into the IoC container.
     *
     * ConnectionFactory::createConnector() checks for a `db.connector.{driver}`
     * binding before falling back to the built-in connectors, so this is the
     * official extension point with no monkey-patching required.
     */
    private function registerRdsIamConnectors(): void
    {
        if (! config('rds.iam_auth')) {
            return;
        }

        $this->app->singleton(
            RdsIamTokenGenerator::class,
            fn ($app): RdsIamTokenGenerator => new RdsIamTokenGenerator(
                cache: $app['cache']->store('array'),
                awsRegion: (string) config('rds.aws.region'),
                credentialsProvider: CredentialProvider::defaultProvider(
                    array_filter(['profile' => config('rds.aws.profile')])
                ),
            )
        );

        $this->app->bind(
            'db.connector.mysql',
            fn ($app): RdsIamMySqlConnector => new RdsIamMySqlConnector(
                $app->make(RdsIamTokenGenerator::class)
            )
        );

        $this->app->bind(
            'db.connector.pgsql',
            fn ($app): RdsIamPostgresConnector => new RdsIamPostgresConnector(
                $app->make(RdsIamTokenGenerator::class)
            )
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
