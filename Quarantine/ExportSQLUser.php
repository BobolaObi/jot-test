<?php

namespace Quarantine;;

use Legacy\Jot\Utils\DB;


class ExportSQLUser
{

    private $username;
    private $tables = [];
    private $queries = [];

    public function __construct($username)
    {

        # Set the username
        $this->username = $username;

        # Set the username selector
        $usernameSelector = ["username" => $this->username];

        # Get data from tables using the selectors.
        $userTable = new ESU_Table("users", $usernameSelector);
        $this->insertQuery($userTable->exportSql());

        $formsTable = new ESU_Table("forms", $usernameSelector);
        $this->insertQuery($formsTable->exportSql());

        # Set the form id selector
        $formIds = $formsTable->getRows(['id'], true);
        $formIdSelector = ["form_id" => $formIds["id"]];

        # export submission table
        $submissionTable = new ESU_Table("submissions", $formIdSelector);
        $this->insertQuery($submissionTable->exportSql());

        # Set the submission table selector
        $submissionIds = $submissionTable->getRows(['id'], true);
        $submissionIdSelector = ["submission_id" => $submissionIds['id']];

        # export api_iphone table
        $apiPhoneTable = new ESU_Table("api_iphone", $usernameSelector);
        $this->insertQuery($apiPhoneTable->exportSql());

        # export form_properties table
        $formPropertiesTable = new ESU_Table("form_properties", $formIdSelector);
        $this->insertQuery($formPropertiesTable->exportSql());

        # export integrations table
        $integrationsTable = new ESU_Table("integrations", $usernameSelector);
        $this->insertQuery($integrationsTable->exportSql());

        # export listings table
        $listingsTable = new ESU_Table("listings", $formIdSelector);
        $this->insertQuery($listingsTable->exportSql());

        # export payment_data_log table
        $paymentDataLogTable = new ESU_Table("payment_data_log", $formIdSelector);
        $this->insertQuery($paymentDataLogTable->exportSql());

        # export payment_log table
        $paymentLogTable = new ESU_Table("payment_log", $submissionIdSelector);
        $this->insertQuery($paymentLogTable->exportSql());

        # export pending_redirects table
        $pendingRedirectsTable = new ESU_Table("pending_redirects", $formIdSelector);
        $this->insertQuery($pendingRedirectsTable->exportSql());

        # export pending_submissions table
        $pendingSubmissionsTable = new ESU_Table("pending_submissions", $formIdSelector);
        $this->insertQuery($pendingSubmissionsTable->exportSql());

        # export announcement table
        $announcementTable = new ESU_Table("announcement", $usernameSelector);
        $this->insertQuery($announcementTable->exportSql());

        # export answers table
        $answersTable = new ESU_Table("answers", $formIdSelector);
        $this->insertQuery($answersTable->exportSql());

        # export monthly_usage table
        $monthlyUsageTable = new ESU_Table("monthly_usage", $usernameSelector);
        $this->insertQuery($monthlyUsageTable->exportSql(true));

        # export payments table
        $paymentsTable = new ESU_Table("payments", $submissionIdSelector);
        $this->insertQuery($paymentsTable->exportSql());

        # export products table
        $productsTable = new ESU_Table("products", $formIdSelector);
        $this->insertQuery($productsTable->exportSql());

        # Set the product id selector
        $productIds = $productsTable->getRows(['product_id'], true);
        $productIdSelector = ["product_id" => $productIds["product_id"]];

        # export payment_products table
        $paymentProductsTable = new ESU_Table("payment_products", $productIdSelector);
        $this->insertQuery($paymentProductsTable->exportSql());

        # export question_properties table
        $questionPropertiesTable = new ESU_Table("question_properties", $formIdSelector);
        $this->insertQuery($questionPropertiesTable->exportSql());

        # export reports table
        $reportsTable = new ESU_Table("reports", $formIdSelector);
        $this->insertQuery($reportsTable->exportSql());

        # export upload_files table
        $uploadFilesTable = new ESU_Table("upload_files", $formIdSelector);
        $this->insertQuery($uploadFilesTable->exportSql());

        # export whitelist table
        $whitelistTable = new ESU_Table("whitelist", $formIdSelector);
        $this->insertQuery($whitelistTable->exportSql());
    }

    private function insertQuery($query)
    {
        if ($query === false) {
            return;
        }
        $this->queries[] = $query;
    }

    public function getQueries()
    {
        return $this->queries;
    }

}

class ESU_Table
{

    private $tableName;
    private $fetchFields = [];
    private $tableColumns;
    private $nl = "\n";    # this is for new line maybe \n or <br/>

    public function __construct($tableName, $fetchFields = [])
    {

        $this->tableName = $tableName;
        $this->fetchFields = $fetchFields;
        $this->tableColumns = DB::getTableColumns($this->tableName);
        $this->whereClause = $this->generateWhereClause();
    }

    public function exportSql($isReplace = false)
    {
        # Fetch values from results to complete the insert query.
        $insertValues = [];
        # Get all the rows for the user to generate the insert values.
        foreach ($this->getRows() as $row) {
            $escaped = [];
            foreach ($row as $key => $value) {
                $escaped[$key] = mysql_real_escape_string($value);
            }
            $insertValues[] = "('" . implode("','", $escaped) . "')";
        }
        # Start generating the insert query.
        $action = $isReplace ? "REPLACE" : "INSERT";
        $insertQuery = "{$action} INTO `{$this->tableName}` (`" .
            implode("`,`", array_keys($this->tableColumns)) .
            "`) VALUES {$this->nl}" .
            implode(",{$this->nl}", $insertValues) . ";";

        # if there is no insert value return false
        if (count($insertValues) === 0) {
            return false;
        } else {
            return $insertQuery;
        }
    }

    public function getRows($selectFields = [], $returnMergedArr = false)
    {
        if (count($selectFields) === 0) {
            $selectValues = "*";
        } else {
            $selectValues = "`" . implode("`,`", $selectFields) . "`";
        }

        $query = "SELECT {$selectValues} FROM `{$this->tableName}` WHERE {$this->whereClause}";
        $res = DB::read($query);
        if ($returnMergedArr) {
            $values = [];
            foreach ($selectFields as $fieldName) {
                $values[$fieldName] = [];
            }
            foreach ($res->result as $row) {
                foreach ($row as $fieldName => $value) {
                    $values[$fieldName][] = $value;
                }
            }
            return $values;
        } else {
            return $res->result;
        }
    }

    private function generateWhereClause()
    {
        # loop all results
        $whereClause = [];

        foreach ($this->fetchFields as $fieldName => $valueArr) {
            if (is_array($valueArr)) {
                $whereClause[] = "`{$fieldName}` IN ('" . implode("','", $valueArr) . "')";
            } else {
                $whereClause[] = "`{$fieldName}` = '{$valueArr}'";
            }
        }
        return implode(" AND ", $whereClause);
    }
}
