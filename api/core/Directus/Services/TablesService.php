<?php

/**
 * Directus – <http://getdirectus.com>
 *
 * @link      The canonical repository – <https://github.com/directus/directus>
 * @copyright Copyright 2006-2017 RANGER Studio, LLC – <http://rangerstudio.com>
 * @license   GNU General Public License (v3) – <http://www.gnu.org/copyleft/gpl.html>
 */

namespace Directus\Services;

use Directus\Util\SchemaUtils;

/**
 * Util methods for managing a table schema
 *
 * @author Welling Guzmán <welling@rngr.org>
 */
class TablesService extends AbstractService
{
    public function createTable($name, $columns = [])
    {
        $schema = $this->getSchemaManager();

        if ($schema->tableExists($name)) {
            return -1;
        }

        $tableGateway = $this->createTableGateway('directus_columns');

        if (!is_array($columns)) {
            $columns = [$columns];
        }

        // Through API:
        // Remove spaces and symbols from table name
        // And in lowercase
        $name = SchemaUtils::cleanTableName($name);

        $this->app->triggerAction('table.create:before', $name);

        $schema->createTable($name, $columns);

        $this->app->triggerAction('table.create', $name);
        $this->app->triggerAction('table.create:after', $name);

        // ----------------------------------------------------------------------------
        // Create system columns interface information
        // ----------------------------------------------------------------------------
        $tableGateway->insert([
            'table_name' => $name,
            'column_name' => 'id',
            'data_type' => 'INT',
            'ui' => 'primary_key'
        ]);

        if (in_array('sort', $columns)) {
            $tableGateway->insert([
                'table_name' => $name,
                'column_name' => 'sort',
                'data_type' => 'INT',
                'ui' => 'sort'
            ]);
        }

        if (in_array('status', $columns)) {
            $tableGateway->insert([
                'table_name' => $name,
                'column_name' => 'status',
                'data_type' => 'INT',
                'ui' => 'status'
            ]);
        }
    }

    /**
     * Drops the given table and its table and columns information
     *
     * @param $name
     *
     * @return bool
     */
    public function dropTable($name)
    {
        $tableGateway = $this->createTableGateway($name);
        $columnsTableGateway = $this->createTableGateway('directus_columns');
        $tablesTableGateway = $this->createTableGateway('directus_tables');

        $columnsTableGateway->delete(['table_name' => $name]);
        $tablesTableGateway->delete(['table_name' => $name]);

        return $tableGateway->drop();
    }

    /**
     * Checks whether the given name is a valid clean table name
     *
     * @param $name
     *
     * @return bool
     */
    public function isValidName($name)
    {
        $isTableNameAlphanumeric = preg_match("/[a-z0-9]+/i", $name);
        $zeroOrMoreUnderscoresDashes = preg_match("/[_-]*/i", $name);

        return $isTableNameAlphanumeric && $zeroOrMoreUnderscoresDashes;
    }
}
