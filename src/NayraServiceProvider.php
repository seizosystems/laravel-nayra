<?php
declare(strict_types=1);

namespace Viezel\Nayra;

use Illuminate\Support\ServiceProvider;
use ProcessMaker\Nayra\Contracts\Engine\JobManagerInterface;
use ProcessMaker\Nayra\Contracts\Repositories\ExecutionInstanceRepositoryInterface;
use ProcessMaker\Nayra\Contracts\Repositories\TokenRepositoryInterface;
use Viezel\Nayra\Contracts\RequestRepositoryInterface;
use Viezel\Nayra\Nayra\JobManager;
use Viezel\Nayra\Nayra\Manager;
use Viezel\Nayra\Repositories\InstanceRepository;
use Viezel\Nayra\Repositories\RequestRepository;
use Viezel\Nayra\Repositories\TokenRepository;

class NayraServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(
            'nayra.manager',
            function () {
                return new Manager(app(RequestRepositoryInterface::class));
            }
        );
        $this->app->singleton(RequestRepositoryInterface::class, function () {
            return new RequestRepository();
        });
        $this->app->singleton(JobManagerInterface::class, function () {
            return new JobManager();
        });
        $this->app->singleton(ExecutionInstanceRepositoryInterface::class, function () {
            return new InstanceRepository(app(RequestRepositoryInterface::class));
        });
        $this->app->singleton(TokenRepositoryInterface::class, function () {
            return new TokenRepository();
        });

        if ($this->app->runningInConsole()) {
            $migrationFileName = 'create_requests_table.php';
            if (! $this->migrationFileExists($migrationFileName)) {
                $this->publishes([
                    __DIR__ . "/../database/migrations/{$migrationFileName}.stub" => database_path('migrations/' . date('Y_m_d_His', time()) . '_' . $migrationFileName),
                ], 'migrations');
            }
        }
    }

    public static function migrationFileExists(string $migrationFileName): bool
    {
        $len = strlen($migrationFileName);
        foreach (glob(database_path("migrations/*.php")) as $filename) {
            if ((substr($filename, -$len) === $migrationFileName)) {
                return true;
            }
        }

        return false;
    }
}
