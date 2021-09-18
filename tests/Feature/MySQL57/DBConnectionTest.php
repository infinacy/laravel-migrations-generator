<?php

namespace Tests\Feature\MySQL57;

use Illuminate\Support\Facades\DB;
use PDO;

class DBConnectionTest extends MySQL57TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'mysql8');
        $app['config']->set('database.connections.mysql8', [
            'driver'         => 'mysql',
            'url'            => null,
            'host'           => env('MYSQL8_HOST'),
            'port'           => env('MYSQL8_PORT'),
            'database'       => env('MYSQL8_DATABASE'),
            'username'       => env('MYSQL8_USERNAME'),
            'password'       => env('MYSQL8_PASSWORD'),
            'unix_socket'    => env('DB_SOCKET', ''),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_general_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => null,
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ]);
    }

    public function testDBConnection()
    {
        $migrateTemplates = function () {
            $this->migrateGeneral('mysql57');
        };

        $generateMigrations = function () {
            $this->artisan(
                'migrate:generate',
                [
                    '--connection'   => 'mysql57',
                    '--path'         => $this->storageMigrations(),
                    '--templatePath' => base_path('src/MigrationsGenerator/stub/migration.stub'),
                ]
            )
                ->expectsQuestion('Do you want to log these migrations in the migrations table? [Y/n] ', 'y')
                ->expectsQuestion('Log into current connection: mysql57? [Y = mysql57, n = mysql8 (default connection)] [Y/n] ', 'y')
                ->expectsQuestion('Next Batch Number is: 1. We recommend using Batch Number 0 so that it becomes the "first" migration [Default: 0] ', 0);
        };

        $this->verify($migrateTemplates, $generateMigrations);
    }

    private function verify(callable $migrateTemplates, callable $generateMigrations)
    {
        $migrateTemplates();

        DB::connection('mysql57')->table('migrations')->truncate();
        $this->dumpSchemaAs($this->storageSql('expected.sql'));

        $generateMigrations();

        $this->assertMigrations();

        $this->dropAllTables();

        $this->runMigrationsFrom('mysql57', $this->storageMigrations());

        DB::connection('mysql57')->table('migrations')->truncate();
        $this->dumpSchemaAs($this->storageSql('actual.sql'));

        $this->assertFileEqualsIgnoringOrder(
            $this->storageSql('expected.sql'),
            $this->storageSql('actual.sql')
        );
    }
}