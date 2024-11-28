<?php

namespace KitLoong\MigrationsGenerator\Repositories;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use KitLoong\MigrationsGenerator\Repositories\Entities\MySQL\ShowColumn;
use KitLoong\MigrationsGenerator\Repositories\Entities\ProcedureDefinition;

class MySQLRepository extends Repository
{
    /**
     * Show column by table and column name.
     *
     * @param  string  $table  Table name.
     * @param  string  $column  Column name.
     */
    public function showColumn(string $table, string $column): ?ShowColumn
    {
        $result = DB::selectOne("SHOW COLUMNS FROM `$table` WHERE Field = '$column'");
        return $result === null ? null : new ShowColumn($result);
    }

    /**
     * Checks if column has `on update CURRENT_TIMESTAMP`
     *
     * @param  string  $table  Table name.
     * @param  string  $column  Column name.
     */
    public function isOnUpdateCurrentTimestamp(string $table, string $column): bool
    {
        // MySQL5.7 shows "on update CURRENT_TIMESTAMP"
        // MySQL8 shows "DEFAULT_GENERATED on update CURRENT_TIMESTAMP"
        $result = DB::selectOne(
            "SHOW COLUMNS FROM `$table`
                WHERE Field = '$column'
                    AND (Type LIKE 'timestamp%' OR Type LIKE 'datetime%')
                    AND Extra LIKE '%on update CURRENT_TIMESTAMP%'",
        );
        return !($result === null);
    }

    /**
     * Get the virtual column definition by table and column name.
     *
     * @param  string  $table  Table name.
     * @param  string  $column  Column name.
     * @return string|null  The virtual column definition. NULL if not found.
     */
    public function getVirtualDefinition(string $table, string $column): ?string
    {
        return $this->getGenerationExpression($table, $column, 'VIRTUAL GENERATED');
    }

    /**
     * Get the stored column definition by table and column name.
     *
     * @param  string  $table  Table name.
     * @param  string  $column  Column name.
     * @return string|null  The stored column definition. NULL if not found.
     */
    public function getStoredDefinition(string $table, string $column): ?string
    {
        return $this->getGenerationExpression($table, $column, 'STORED GENERATED');
    }

    /**
     * Get a list of stored procedures.
     *
     * @param  string  $type  'PROCEDURE' or 'FUNCTION'.
     * @return \Illuminate\Support\Collection<int, \KitLoong\MigrationsGenerator\Repositories\Entities\ProcedureDefinition>
     */
    public function getProcedures(): Collection {
        $list       = new Collection();
        $procedures = DB::select("SHOW PROCEDURE STATUS WHERE Db='" . DB::getDatabaseName() . "'");
        $functions = DB::select("SHOW FUNCTION STATUS WHERE Db='" . DB::getDatabaseName() . "'");

        $procedures = array_merge($procedures, $functions);

        foreach ($procedures as $procedure) {
            // Change all keys to lowercase.
            $procedureArr = array_change_key_case((array) $procedure);
            $type = strtoupper($procedureArr['type']);
            $createProc   = $this->getProcedure($procedureArr['name'], $type);

            // Change all keys to lowercase.
            $createProcArr = array_change_key_case((array) $createProc);

            $definitionKey = $type === 'PROCEDURE' ? 'create procedure' : 'create function';

            if ($createProcArr[$definitionKey] === null || $createProcArr[$definitionKey] === '') {
                continue;
            }

            // Remove DEFINER from procedure definition.
            $definition = preg_replace("/(?=DEFINER=)(.+?)(?= $type) /u", '', $createProcArr[$definitionKey]);

            $list->push(new ProcedureDefinition($procedureArr['name'], $definition));
        }

        return $list;
    }

    /**
     * Get the SRID by table and column name.
     */
    public function getSrID(string $table, string $column): ?int
    {
        try {
            $srsID = DB::selectOne(
                "SELECT SRS_ID
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = '" . DB::getDatabaseName() . "'
                    AND TABLE_NAME = '" . $table . "'
                    AND COLUMN_NAME = '" . $column . "'",
            );
        } catch (QueryException $exception) {
            if (
                // `SRS_ID` available since MySQL 8.0.3.
                // https://dev.mysql.com/doc/relnotes/mysql/8.0/en/news-8-0-3.html
                Str::contains(
                    $exception->getMessage(),
                    "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'SRS_ID'",
                    true,
                )
            ) {
                return null;
            }

            throw $exception;
        }

        if ($srsID === null) {
            return null;
        }

        $srsIDArr = array_change_key_case((array) $srsID);
        return $srsIDArr['srs_id'] ?? null;
    }

    /**
     * Get single stored procedure by name.
     *
     * @param  string  $procedure  Procedure name.
     * @return mixed
     */
    private function getProcedure(string $procedure, $type = 'PROCEDURE') {
        $type = strtoupper($type);
        $type = in_array($type, ['PROCEDURE', 'FUNCTION']) ? $type : 'PROCEDURE';
        return DB::selectOne("SHOW CREATE $type $procedure");
    }

    /**
     * Get the column GENERATION_EXPRESSION when EXTRA is 'VIRTUAL GENERATED' or 'STORED GENERATED'.
     *
     * @param  'VIRTUAL GENERATED'|'STORED GENERATED'  $extra
     */
    private function getGenerationExpression(string $table, string $column, string $extra): ?string
    {
        try {
            $definition = DB::selectOne(
                "SELECT GENERATION_EXPRESSION
                FROM information_schema.COLUMNS
                WHERE TABLE_NAME = '$table'
                    AND COLUMN_NAME = '$column'
                    AND EXTRA = '$extra'",
            );
        } catch (QueryException $exception) {
            // Check if error caused by missing column 'GENERATION_EXPRESSION'.
            // The column is introduced since MySQL 5.7 and MariaDB 10.2.5.
            // @see https://mariadb.com/kb/en/information-schema-columns-table/
            // @see https://dev.mysql.com/doc/refman/5.7/en/information-schema-columns-table.html
            if (
                Str::contains(
                    $exception->getMessage(),
                    "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'GENERATION_EXPRESSION'",
                    true,
                )
            ) {
                return null;
            }

            throw $exception;
        }

        if ($definition === null) {
            return null;
        }

        $definitionArr = array_change_key_case((array) $definition);
        return $definitionArr['generation_expression'] !== '' ? $definitionArr['generation_expression'] : null;
    }
}
