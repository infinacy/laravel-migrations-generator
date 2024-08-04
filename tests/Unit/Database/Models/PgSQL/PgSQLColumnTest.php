<?php

namespace KitLoong\MigrationsGenerator\Tests\Unit\Database\Models\PgSQL;

use KitLoong\MigrationsGenerator\Database\Models\PgSQL\PgSQLColumn;
use KitLoong\MigrationsGenerator\Enum\Migrations\Method\ColumnType;
use KitLoong\MigrationsGenerator\Repositories\PgSQLRepository;
use KitLoong\MigrationsGenerator\Support\CheckLaravelVersion;
use KitLoong\MigrationsGenerator\Tests\TestCase;
use Mockery\MockInterface;

class PgSQLColumnTest extends TestCase
{
    use CheckLaravelVersion;

    public function testSpatialTypeNameWithDot(): void
    {
        $this->mock(PgSQLRepository::class, static function (MockInterface $mock): void {
            $mock->shouldReceive('getStoredDefinition');
        });

        $column = new PgSQLColumn('table', [
            'name'           => 'column',
            'type_name'      => 'geography',
            'type'           => 'extensions.geography(Point,4326)',
            'collation'      => null,
            'nullable'       => false,
            'default'        => null,
            'auto_increment' => false,
            'comment'        => null,
        ]);

        if ($this->atLeastLaravel11()) {
            $this->assertSame(ColumnType::GEOGRAPHY, $column->getType());
            $this->assertSame('point', $column->getSpatialSubType());
            $this->assertSame(4326, $column->getSpatialSrID());
            return;
        }

        $this->assertSame(ColumnType::POINT, $column->getType());
    }

    public function testSpatialTypeNameWithoutDot(): void
    {
        $this->mock(PgSQLRepository::class, static function (MockInterface $mock): void {
            $mock->shouldReceive('getStoredDefinition');
        });

        $column = new PgSQLColumn('table', [
            'name'           => 'column',
            'type_name'      => 'geography',
            'type'           => 'geography(Point,4326)',
            'collation'      => null,
            'nullable'       => false,
            'default'        => null,
            'auto_increment' => false,
            'comment'        => null,
        ]);

        if ($this->atLeastLaravel11()) {
            $this->assertSame(ColumnType::GEOGRAPHY, $column->getType());
            $this->assertSame('point', $column->getSpatialSubType());
            $this->assertSame(4326, $column->getSpatialSrID());
            return;
        }

        $this->assertSame(ColumnType::POINT, $column->getType());
    }
}
