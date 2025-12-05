<?php

namespace KitLoong\MigrationsGenerator\Migration;

use Illuminate\Support\Collection;
use KitLoong\MigrationsGenerator\Migration\Blueprint\DBUnpreparedBlueprint;
use KitLoong\MigrationsGenerator\Migration\Enum\MigrationFileType;
use KitLoong\MigrationsGenerator\Migration\Writer\MigrationWriter;
use KitLoong\MigrationsGenerator\Migration\Writer\SquashWriter;
use KitLoong\MigrationsGenerator\Schema\Models\Trigger;
use KitLoong\MigrationsGenerator\Setting;
use KitLoong\MigrationsGenerator\Support\MigrationNameHelper;
use KitLoong\MigrationsGenerator\Support\TableName;

class TriggerMigration
{
    use TableName;

    /**
     * @var \KitLoong\MigrationsGenerator\Support\MigrationNameHelper
     */
    private $migrationNameHelper;

    /**
     * @var \KitLoong\MigrationsGenerator\Migration\Writer\MigrationWriter
     */
    private $migrationWriter;

    /**
     * @var \KitLoong\MigrationsGenerator\Setting
     */
    private $setting;

    /**
     * @var \KitLoong\MigrationsGenerator\Migration\Writer\SquashWriter
     */
    private $squashWriter;

    public function __construct(
        MigrationNameHelper $migrationNameHelper,
        MigrationWriter $migrationWriter,
        Setting $setting,
        SquashWriter $squashWriter
    ) {
        $this->migrationNameHelper = $migrationNameHelper;
        $this->migrationWriter     = $migrationWriter;
        $this->setting             = $setting;
        $this->squashWriter        = $squashWriter;
    }

    /**
     * Create trigger migration.
     *
     * @param  \KitLoong\MigrationsGenerator\Schema\Models\Trigger>  $trigger
     * @return string The migration file path.
     */
    public function write(string $table, Trigger $trigger): string
    {
        $up   = $this->up($trigger);
        $down = $this->down($trigger);

        $this->migrationWriter->writeTo(
            $path = $this->makeMigrationPath($table, $trigger->getName()),
            $this->setting->getStubPath(),
            $this->makeMigrationClassName($table, $trigger->getName()),
            new Collection([$up]),
            new Collection([$down]),
            MigrationFileType::TRIGGER()
        );

        return $path;
    }

    /**
     * Write trigger migration into temporary file.
     *
     * @param  \KitLoong\MigrationsGenerator\Schema\Models\Trigger  $trigger
     */
    public function writeToTemp(Trigger $trigger): void
    {
        $up   = $this->up($trigger);
        $down = $this->down($trigger);

        $this->squashWriter->writeToTemp(new Collection([$up]), new Collection([$down]));
    }

    /**
     * Generates `up` schema for trigger.
     *
     * @param  \KitLoong\MigrationsGenerator\Schema\Models\Trigger  $trigger
     */
    private function up(Trigger $trigger): DBUnpreparedBlueprint
    {
        return new DBUnpreparedBlueprint($trigger->getDefinition());
    }

    /**
     * Generates `down` schema for trigger.
     *
     * @param  \KitLoong\MigrationsGenerator\Schema\Models\Trigger>  $trigger
     */
    private function down(Trigger $trigger): DBUnpreparedBlueprint
    {
        return new DBUnpreparedBlueprint($trigger->getDropDefinition());
    }

    /**
     * Makes class name for trigger migration.
     *
     * @param  string  $table  Table name.
     * @param  string  $triggerName  Trigger name.
     */
    private function makeMigrationClassName(string $table, string $triggerName): string
    {
        $withoutPrefix = $this->stripTablePrefix($table);
        $concatedName = $withoutPrefix . '_' . $triggerName;
        return $this->migrationNameHelper->makeClassName(
            $this->setting->getTriggerFilename(),
            $concatedName
        );
    }

    /**
     * Makes file path for trigger migration.
     *
     * @param  string  $table  Table name.
     * @param  string  $triggerName  Trigger name.
     */
    private function makeMigrationPath(string $table, string $triggerName): string
    {
        $withoutPrefix = $this->stripTablePrefix($table);
        $concatedName = $withoutPrefix . '_' . $triggerName;
        return $this->migrationNameHelper->makeFilename(
            $this->setting->getTriggerFilename(),
            $this->setting->getDateForMigrationFilename(),
            $concatedName
        );
    }

}
