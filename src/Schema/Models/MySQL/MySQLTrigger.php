<?php

namespace KitLoong\MigrationsGenerator\Schema\Models\MySQL;

use KitLoong\MigrationsGenerator\Schema\Models\Trigger;

class MySQLTrigger implements Trigger
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $definition;


    public function __construct(string $table, string $name, string $definition)
    {
        $this->tableName        = $table;
        $this->name             = $name;
        $this->definition       = $definition;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @inheritDoc
     */
    public function getDefinition(): string
    {
        return $this->definition;
    }

    /**
     * @inheritDoc
     */
    public function getDropDefinition(): string
    {
        return "DROP TRIGGER IF EXISTS `{$this->name}`";
    }

}
