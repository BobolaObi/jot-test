<?php


/**
 * Database Migration Object.
 * Will keep the database changes and save them in a file then alter the other databases with these changes
 * @package JotForm_Utils
 * @author Seyhun Sarıyıldız <seyhun@interlogy.com>
 *
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Quarantine;

use DB;

class DBMigrate extends DB
{
    /**
     * Takes the database config and saves it into an array,
     * and saves it in to the database schema file with JSON format.
     */
    static public function commitDatabaseSchema()
    {

        // Open schema file to write
        $fh = @fopen(SCHEMA_FILE_PATH, 'w+');
        if (!$fh) {
            throw new \Exception('Cannot open database schema file for write.');
        }
        if (!@fwrite($fh, json_encode(self::getLocalDatabaseSchema()))) {
            throw new \Exception('Cannot write to database schema file.');
        }
        if (!@fclose($fh)) {
            throw new \Exception('Cannot close database schema file after write.');
        }
    }

    /**
     * Returns the database setting
     * @return array of database setting
     */
    static private function getLocalDatabaseSchema()
    {
        // The local database config.
        $localDBConfig = [];

        // Get the table names and details
        foreach (self::getTables() as $tableName) {
            $localDBConfig[$tableName]['columns'] = self::getTableColumns($tableName);
            $localDBConfig[$tableName]['indexes'] = self::getIndexesDetails($tableName);
            $localDBConfig[$tableName]['triggers'] = self::getTriggers($tableName);
        }

        return $localDBConfig;
    }

    /**
     * Gets the any trigger assotiated with given table
     * @param  $tableName
     * @return
     */
    static private function getTriggers($tableName)
    {
        $response = self::read("SHOW TRIGGERS LIKE ':table'", $tableName);
        if ($response->rows < 1) {
            return [];
        }
        $triggers = [];
        foreach ($response->result as $line) {
            $filteredLine = [];
            $filteredLine['Trigger'] = $line['Trigger'];
            $filteredLine['Event'] = $line['Event'];
            $filteredLine['Statement'] = $line['Statement'];
            $filteredLine['Timing'] = $line['Timing'];
            $filteredLine['Table'] = $line['Table'];

            $triggers[$filteredLine['Trigger']] = $filteredLine;
        }
        return $triggers;
    }

    /**
     *
     * @param  $localDBConfig
     * @param  $fileDBConfig
     * @return
     */
    static private function addRemoveExtraTablesQueries($localDBConfig, $fileDBConfig)
    {
        # Find the tables that must be dropped.
        $deletedTables = array_diff_assoc($localDBConfig, $fileDBConfig);
        foreach ($deletedTables as $tableName => $properties) {
            array_push(self::$syncQueries, "DROP TABLE IF EXISTS `" . $tableName . "`");
        }
    }

    /**
     * Creates queries for missing tables according to the schema file
     * @param  $localDBConfig
     * @param  $fileDBConfig
     * @return
     */
    static private function addCreateMissingTablesQueries($localDBConfig, $fileDBConfig)
    {
        # Find the tables that must be created.
        $createdTables = array_diff_assoc($fileDBConfig, $localDBConfig);
        foreach ($createdTables as $tableName => $properties) {

            # Get the field properties
            $fields = $properties['columns'];

            # Get the indexes
            $indexes = $properties['indexes'];

            # will hold line of the sql query for creating a table
            $lines = [];

            # add columns to the query
            foreach ($fields as $fieldName => $fieldProperties) {
                array_push($lines, self::generateFieldLine($fieldProperties));
            }

            # add indexes to the query
            foreach ($indexes as $indexName => $indexProperties) {
                array_push($lines, self::generateIndexLine($indexProperties));
            }

            # Create the query
            $query = "CREATE TABLE `" . $tableName . "` (" . implode(",", $lines) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
            array_push(self::$syncQueries, $query);
        }
    }

    /**
     * Creates ALTER queries according to the schema file
     * @param  $localDBConfig
     * @param  $fileDBConfig
     * @return
     */
    static private function addChagedTablesQueries($localDBConfig, $fileDBConfig)
    {
        # Compare the table properties which exists in both array.
        foreach ($fileDBConfig as $tableName => $properties) {

            # if table does not exists in local, continue because it is created
            # with its all details.
            if (!isset($localDBConfig[$tableName])) {
                continue;
            }

            # Remove the fields that are too much..
            $deletedFields = array_diff_assoc($localDBConfig[$tableName]['columns'], $fileDBConfig[$tableName]['columns']);
            foreach ($deletedFields as $fieldName => $fieldProperties) {
                array_push(self::$syncQueries, "ALTER TABLE `" . $tableName . "` DROP `" . $fieldName . "`");
            }

            # Add the field that are missing.
            $createdFields = array_diff_assoc($fileDBConfig[$tableName]['columns'], $localDBConfig[$tableName]['columns']);
            foreach ($createdFields as $fieldName => $fieldProperties) {
                array_push(self::$syncQueries, "ALTER TABLE `" . $tableName . "` ADD " . self::generateFieldLine($fieldProperties));
            }

            # Change the fields that are not same.
            $intersectTables = array_intersect_assoc($fileDBConfig[$tableName]['columns'], $localDBConfig[$tableName]['columns']);
            foreach ($intersectTables as $fieldName => $fieldProperties) {

                # Flag for seeing if the fields are same or not.
                $isSame = true;

                foreach ($fileDBConfig[$tableName]['columns'][$fieldName] as $prop => $value) {
                    if ($prop === "Key") continue;
                    # if the fields are not same, change the field
                    # mark the flag and break the loop.
                    if (trim($fileDBConfig[$tableName]['columns'][$fieldName][$prop])
                        !== trim($localDBConfig[$tableName]['columns'][$fieldName][$prop])) {
                        $isSame = false;
                        break;
                    }
                }

                # If there are not same add new alter query.
                if ($isSame === false) {
                    array_push(self::$syncQueries, "ALTER TABLE `" . $tableName . "` CHANGE `" . $fieldProperties['Field'] . "` " . self::generateFieldLine($fieldProperties));
                }
            }

            # Remove the indexes that are too much..
            $deletedIndexes = array_diff_assoc($localDBConfig[$tableName]['indexes'], $fileDBConfig[$tableName]['indexes']);
            foreach ($deletedIndexes as $indexName => $indexProperties) {
                $deleteIndex = $isPrimary ? "DROP PRIMARY KEY " : "DROP INDEX `" . $indexName . "` ";
                array_push(self::$syncQueries, "ALTER TABLE `" . $tableName . "` " . $deleteIndex);
            }

            # Add the indexes that are missing.
            $createdIndexes = array_diff_assoc($fileDBConfig[$tableName]['indexes'], $localDBConfig[$tableName]['indexes']);
            foreach ($createdIndexes as $indexName => $indexProperties) {

                # if primary key exists before delete it
                $deletePrimary = ($indexName === "PRIMARY" && isset($localDBConfig[$tableName]['indexes']['PRIMARY'])) ? "DROP PRIMARY KEY, " : "";
                array_push(self::$syncQueries, "ALTER TABLE `" . $tableName . "` " . $deletePrimary . "ADD " . self::generateIndexLine($indexProperties));
            }

            # Change the indexes that are not same
            $intersectIndexes = array_intersect_assoc($fileDBConfig[$tableName]['indexes'], $localDBConfig[$tableName]['indexes']);
            foreach ($intersectIndexes as $indexName => $indexProperties) {
                # Hold flag
                $isSame = true;

                # if the indexes are not identical drop the index and create it again.
                for ($i = 0; $i < count($fileDBConfig[$tableName]['indexes'][$indexName]); $i++) {
                    if (!self::compareConfigArrays($fileDBConfig[$tableName]['indexes'][$indexName][$i],
                        $localDBConfig[$tableName]['indexes'][$indexName][$i])) {
                        $isSame = false;
                        break;
                    }
                }

                if (!$isSame) {
                    $deleteIndex = $isPrimary ? "DROP PRIMARY KEY, " : "DROP INDEX `" . $indexName . "`, ";
                    array_push(self::$syncQueries, "ALTER TABLE `" . $tableName . "` " . $deleteIndex . "ADD " . self::generateIndexLine($indexProperties));
                }
            }

            # Remove the indexes that are too much..
            $deletedTriggers = array_diff_assoc($localDBConfig[$tableName]['triggers'], $fileDBConfig[$tableName]['triggers']);
            foreach ($deletedTriggers as $triggerName => $triggerProperties) {
                array_push(self::$syncQueries, "DROP TRIGGER IF EXISTS `" . $triggerName . "`");
            }

            # Add the indexes that are missing.
            $createdTriggers = array_diff_assoc($fileDBConfig[$tableName]['triggers'], $localDBConfig[$tableName]['triggers']);
            foreach ($createdTriggers as $triggerName => $triggerProperties) {
                # if primary key exists before delete it
                $query = "CREATE TRIGGER `" . $triggerProperties['Trigger'] . "` " . $triggerProperties['Timing'] . " " . $triggerProperties['Event']
                    . " ON `" . $triggerProperties['Table'] . "` FOR EACH ROW " . $triggerProperties['Statement'];
                array_push(self::$syncQueries, $query);
            }

            # Change the trigger that are not same
            $intersectTriggers = array_intersect_assoc($fileDBConfig[$tableName]['triggers'], $localDBConfig[$tableName]['triggers']);
            foreach ($intersectTriggers as $triggerName => $triggerProperties) {
                if (!self::compareConfigArrays($triggerProperties, $localDBConfig[$tableName]['triggers'][$triggerName])) {
                    array_push(self::$syncQueries, "DROP TRIGGER IF EXISTS `" . $triggerName . "`");
                    # if primary key exists before delete it
                    $query = "CREATE TRIGGER `" . $triggerProperties['Trigger'] . "` " . $triggerProperties['Timing'] . " " . $triggerProperties['Event']
                        . " ON `" . $triggerProperties['Table'] . "` FOR EACH ROW " . $triggerProperties['Statement'];
                    array_push(self::$syncQueries, $query);
                }
            }

        }
    }

    /**
     * Compares two configurations. Curren database and schema file
     * @param  $array1
     * @param  $array2
     * @return
     */
    static private function compareConfigArrays($array1, $array2)
    {
        if (count($array1) != count($array2)) {
            return false;
        }

        foreach ($array1 as $prop => $value) {
            if (trim($value) != $array2[$prop]) {
                return false;
            }
        }
        return true;
    }

    /**
     * Gets the database chanes and updates the sync queries.
     */
    static public function getDatabaseChanges()
    {
        # Reset the syncQueries array.
        self::$syncQueries = [];

        # Get the json config of the current database.
        $localDBConfig = self::getLocalDatabaseSchema();

        # Get the json config of the svn database.
        $fileDBConfig = self::getDatabaseSchemaFromFile();

        # Add remove extra tables queries to syncQueries
        self::addRemoveExtraTablesQueries($localDBConfig, $fileDBConfig);

        # Add create missing tables queries to syncQueries
        self::addCreateMissingTablesQueries($localDBConfig, $fileDBConfig);

        # Edit tables that are changed.
        self::addChagedTablesQueries($localDBConfig, $fileDBConfig);
    }

    /**
     * Creates an sql line for a new field for creation according to fieldProperties.
     * @param array $fieldProperties
     * @return string
     */
    static private function generateFieldLine($fieldProperties)
    {
        $line = '`' . $fieldProperties['Field'] . '` ' . $fieldProperties['Type'];
        if ($fieldProperties['Null'] != 'YES') {
            $line .= ' NOT NULL';
        }
        if ($fieldProperties['Default'] != '') {
            $line .= ' DEFAULT \'' . $fieldProperties['Default'] . '\'';
        }
        if ($fieldProperties['Extra']) {
            $line .= ' ' . strtoupper($fieldProperties['Extra']);
        }
        return $line;
    }

    /**
     * Creates an sql line for a new index for creation according to indexProperties.
     * @param $indexProperties
     * @return string
     */
    static private function generateIndexLine($indexProperties)
    {
        # Hold the index name
        $indexName = $indexProperties[0]['Key_name'];
        # hold flag for primary keys
        $isPrimary = false;
        # hold flag for unique.
        $isUniq = false;
        # store the columns
        $columnNames = [];
        # loop the index columns
        foreach ($indexProperties as $column) {
            # add the column name
            $columnName = "`" . $column['Column_name'] . "`";
            if ($column['Sub_part']) $columnName .= "(" . $column['Sub_part'] . ")";
            array_push($columnNames, $columnName);
            # check if primary
            if ($column['Key_name'] == 'PRIMARY') {
                $isPrimary = true;
            }
            # check if uniq
            if (!$column['Non_unique']) {
                $isUniq = true;
            }
        }
        $line = "";
        $deletePrimary = "";

        if ($isPrimary) {
            $line .= "PRIMARY KEY ";
        } else if ($isUniq) {
            $line .= "UNIQUE KEY `" . $indexName . "` ";
        } else {
            $line .= "INDEX `" . $indexName . "` ";
        }

        $line .= "(" . implode(",", $columnNames) . ")";

        return $line;
    }

    /**
     * Get the database config array from the database schema file.
     * @return array of database config from array
     */
    static private function getDatabaseSchemaFromFile()
    {
        // Open schema file to write
        if (!file_exists(SCHEMA_FILE_PATH)) {
            throw new \Exception('Database schema file does not exists.');
        }

        if (!($fileStream = file_get_contents(SCHEMA_FILE_PATH))) {
            throw new \Exception('Cannot read database schema file.');
        }

        if (!($fileDBConfig = json_decode($fileStream, true))) {
            throw new \Exception('JSON cannot be converted to array.');
        }

        return $fileDBConfig;
    }

    /**
     *
     * @param string $tableName
     * @return // gets the index details of given table array
     */
    static private function getIndexesDetails($tableName)
    {

        $response = self::read("SHOW INDEXES FROM `:table`", $tableName);
        if ($response->rows < 1) {
            return [];
        }
        $indexes = [];
        foreach ($response->result as $line) {
            // filtered line
            $filteredLine = [];
            $filteredLine['Column_name'] = $line['Column_name'];
            $filteredLine['Sub_part'] = $line['Sub_part'];
            $filteredLine['Key_name'] = $line['Key_name'];
            $filteredLine['Non_unique'] = $line['Non_unique'];

            $indexes[$line['Key_name']][$line['Seq_in_index'] - 1] = $filteredLine;
        }
        return $indexes;

    }

    /**
     * Syncronize local database with the SVN version
     * Update the sync
     */
    public static function syncDatabase()
    {
        self::getDatabaseChanges();
        foreach (self::$syncQueries as $query) {
            $res = self::query($query);
            if ($res === false) {
                throw new \Exception(mysql_error(self::$dlink));
            }
        }
    }
}

?>