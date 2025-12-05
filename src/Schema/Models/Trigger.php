<?php

namespace KitLoong\MigrationsGenerator\Schema\Models;

interface Trigger extends Model
{
    /**
     * Get the trigger name.
     */
    public function getName(): string;

    /**
     * Get the trigger create definition.
     */
    public function getDefinition(): string;

    /**
     * Get the trigger drop definition.
     */
    public function getDropDefinition(): string;

    /**
     * Get the table name.
     */
    public function getTableName(): string;

}
