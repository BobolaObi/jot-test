<?php
/**
 * JotForm form object
 * @package JotForm
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot;

use Legacy\Jot\Api\Core\RestServer;
use Legacy\Jot\Exceptions\RecordNotFoundException;
use Legacy\Jot\Exceptions\SoftException;
use Legacy\Jot\SiteManagement\PageInfo;
use Legacy\Jot\UserManagement\MonthlyUsage;
use Legacy\Jot\UserManagement\Session;
use Legacy\Jot\UserManagement\User;
use Legacy\Jot\Utils\Console;
use Legacy\Jot\Utils\CSV;
use Legacy\Jot\Utils\DB;
use Legacy\Jot\Utils\Server;
use Legacy\Jot\Utils\Settings;
use Legacy\Jot\Utils\TimeZone;
use Legacy\Jot\Utils\Utils;

class Form
{

    private $owner, $timeZone, $timeFormat = 'Y-m-d H:i:s';
    public $id, $form, $isLoggedInNow = false;
    /**
     * @var //list of payment field to be used in code
     */
    public static $paymentFields = ['control_payment', 'control_paypal', 'control_paypalpro', 'control_clickbank', 'control_2co', 'control_worldpay', 'control_googleco', 'control_onebip', 'control_authnet'];

    public static $chartableElements = ["control_dropdown", "control_checkbox", "control_radio", "control_matrix", "control_scale", "control_rating", "control_grading", "control_slider"];

    public static $nonDataFields = ["control_button", "control_text", "control_head", "control_image", "control_captcha", "control_collapse", "control_pagebreak"];

    /**
     * If set getListing results will use it to filter columns
     * @see Form::useListingFilter
     * @var //array
     */
    public $filterFields = false;

    /**
     * Gets the form from database by given ID
     * @constructor
     * @param  $id // [optional]
     * @return
     */
    function __construct($id = false)
    {

        if ($id == 'session') {
            $id = Utils::getCurrentID('form');

            if ($id === false) {
                throw new SoftException('New Form');
            }
        }
        $this->id = (float)$id;

        if ($this->id < 1) {
            if (Utils::getCurrentID('form') == $this->id) {
                Utils::deleteCurrentID("form");
            }
            throw new RecordNotFoundException('Form not found: Form Id is missing or irregular');
        }

        $response = DB::read("SELECT * FROM `forms` WHERE `id`=#id", $this->id);

        if ($response->rows < 1) {
            if (Utils::getCurrentID('form') == $this->id) {
                Utils::deleteCurrentID("form");
            }
            throw new RecordNotFoundException("Form not found: " . $this->id . "==" . Utils::getCurrentID('form'));
        }

        $this->form = $response->first;
        $this->owner = User::find($this->form['username']);

        if (!empty(Session::$username) && strtolower(Session::$username) == strtolower($this->form['username'])) {
            $this->isLoggedInNow = true;
        }

        $this->timeZone = $this->owner->timeZone;
    }

    public function getQuestionTexts()
    {
        $query = "  SELECT qp.value
                    FROM question_properties qp
                    WHERE qp.form_id='{$this->id}'
                    AND qp.prop='text'
                    AND qp.form_id='{$this->id}'";
        $res = DB::read($query);
        $result = [];
        foreach ($res->result as $row) {
            $result[] = $row['value'];
        }
        return $result;
    }

    /**
     * Returns the cached value of submission counts
     */
    public function getSubmissionCount()
    {
        return $this->form['count'];
    }

    /**
     * Returns the cached value of new submissions
     */
    public function getNewSubmissionCount()
    {
        return $this->form['new'];
    }

    /**
     * Returns the form title
     * @return
     */
    public function getTitle()
    {
        return $this->form['title'];
    }

    /**
     * This function defined if the field can be used in reports or email notifications
     * Since there is no meaning to add page breaks, images or form collapse tools in the reports
     * @param  $type
     * @return // boolean
     */
    static function isDataField($type)
    {
        $nonDataFields = ["control_button", "control_text", "control_head", "control_image", "control_captcha", "control_collapse", "control_pagebreak"];
        return !in_array($type, $nonDataFields);
    }

    /**
     * Returns the given property value
     * @param  $prop
     * @return // false if not found
     */
    public function getProperty($prop)
    {
        $res = DB::read("SELECT `value` FROM `form_properties` WHERE `form_id`=#id AND `prop`=':prop' AND (`type` IS NULL OR `type`='')", $this->id, $prop);
        if ($res->rows < 1) {
            return false;
        }

        return $res->first['value'];
    }

    /**
     * Returns the saved and zipped version of the form properties
     * Which will be used in Form Builder
     * @return // array
     */
    public function getSavedProperties($checkAuth = true)
    {

        if ($checkAuth && strtolower($this->form["username"]) != strtolower(Session::$username)) {
            return ["success" => false, "error" => "Authentication Problem"];
        }

        /**
         * Cache Completely disabled because it's causing real problems
         * I'm keeping the form because we might need it in the future
         */
        if (false) {
            /**
             * If there is a cahce don't bother creating new config
             */
            if (file_exists(CACHEPATH . $this->id . ".js") && !Utils::debugOption('disableFormPropertyCache')) {
                $cache = file_get_contents(CACHEPATH . $this->id . ".js");
                # clear non json parts
                $cache = preg_replace("/^getSavedForm\(/", "", $cache);
                $cache = preg_replace('/\,\s+\"success\"\:true\}\)\;?/', "}", $cache);
                # Convert cache into an array
                $cachedConfig = @json_decode($cache, true);
                # Get the form data from cache
                $props = $cachedConfig['form'];
                # Validate the parsed cache before returning
                # if cannot parse it will disregard the cache
                if (is_array($props) && isset($props['form_id']) && $props['form_id'] == $this->id) {
                    return $props;
                } else {
                    Console::error('Cache Failed for:' . $this->id . "\nCache File: " . $cache);
                }
            }
        }

        $questionProperties = [];
        $formProperties = [];
        $completeProperties = [];

        // Get form properties
        $response = DB::read("SELECT * FROM `form_properties` WHERE form_id=#id", $this->id);

        foreach ($response->result as $line) {
            if (!empty($line['type'])) {
                # Options is a deeply nested element and it's saved as a json on the database
                if (in_array($line['prop'], ['options', 'action', 'terms'])) {
                    $line['value'] = Utils::safeJsonDecode($line['value']);
                }
                # Get deep form properties such as emails, products, conditions or so
                $completeProperties["form_" . $line['type']][((float)$line['item_id'])][$line['prop']] = $line['value'];
            } else {
                # Normal form properties
                $completeProperties["form_" . $line['prop']] = stripslashes($line['value']);
            }
        }

        // Also add form title
        $completeProperties["form_title"] = $this->form['title'];
        $completeProperties["form_status"] = ucfirst(strtolower($this->form['status']));

        // Add form slug if exists
        if ($this->form['slug']) {
            $completeProperties["form_slug"] = $this->form['slug'];
        }

        // Fix deep for properties
        foreach ($completeProperties as $key => $value) {
            if (is_array($value)) {
                $completeProperties[$key] = array_values($value);
            }
        }

        // Get all question properties
        $response = DB::read("SELECT * FROM `question_properties` WHERE `form_id`=#id", $this->id);
        $questions = [];

        // Collect all properties together
        foreach ($response->result as $line) {
            # if this question is an autoincrement field
            # use the latest value from database
            if ($line['prop'] == "currentIndex") {
                $au = Settings::getValue("autoIncrement", $this->id);
                if ($au !== false) {
                    $line['value'] = $au;
                }
            }


            // Removed the strip slashes from here because it was causing UTF 8 encode to break
            $questions[$line['question_id']][$line['prop']] = $line['value'];
        }

        // Sort all questions in display order
        foreach ($questions as $qid => $question) {
            $sortedQuestions[$question['order']] = $question;
        }

        if (!empty($sortedQuestions)) {
            ksort($sortedQuestions);

            // Merge sorted question information into all properties
            foreach ($sortedQuestions as $question) {
                foreach ($question as $prop => $value) {
                    if (isset($question['qid'])) {
                        $completeProperties[$question['qid'] . '_' . $prop] = Utils::safeJsonDecode($value);
                    }
                }
            }
        }

        if (count($completeProperties) > 0) {
            $completeProperties['form_id'] = $this->id;
            if (ID::validateID($this->id)) { # Disable this feature for old form IDs
                $completeProperties['form_hash'] = ID::encodeID($this->id);
            }

            return $completeProperties;
        }
        return ["success" => false, "error" => "There is a problem on the form"];
    }

    /**
     * Get the owner of the form
     * @return // User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Checks the status of the form starting from the User status
     * User status has more priority than form status
     * @return // boolean
     * @todo complete this function by adding form status check
     */
    public function checkStatus()
    {

        $ownerStatus = $this->owner->status;

        // If the status part is empty, it is in normal operation.
        // Status can have "deleted", "suspended", "autosuspended".
        if ($ownerStatus != 'ACTIVE') {
            return false;
        }

        return true;
    }

    /**
     * Clears the cache by given type
     * Types:
     *   all: clears the all caches from folder and database on all servers
     *   folder: clears the cache folder completely on all servers
     *   db: clears the database cache for all forms
     *   id: clears the form cache opn all servers and db, should provide form id
     * @param  $type
     * @param  $id // [optional] form ID to to clean
     * @return
     */
    public static function clearCache($type, $id = false)
    {

        if ($type == 'all' || $type == 'folder') {
            Settings::setSetting("admin", "lastCacheClearDate", date("Y-m-d H:i:s"));

            if (USE_REDIS_CACHE) {
                $cacheDB = Utils::getRedis(CACHEDB);
                $cacheDB->flushdb();
            } else {
                $request = new RequestServer([
                    "action" => 'clearAllCache',
                    "toAll" => "yes"
                ], true);
            }
            if (!Server::isLocalhost()) {
                Utils::suppressRequest($host . "/server.php", [
                    "action" => "clearALLMaxCDNFormCache"
                ]);
            }
        }

        if ($type == "search") {
            $search = $id;

            $request = new RequestServer([
                "action" => 'clearFormCacheBySearch',
                "search" => $search,
                "toAll" => "yes"
            ], true);

        }

        if ($type == 'id') {

            if (USE_REDIS_CACHE) {
                $cacheDB = Utils::getRedis(CACHEDB);
                $keys = $cacheDB->keys($id . "*");
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        $cacheDB->del($key);
                    }
                }
            } else {
                $request = new RequestServer([
                    "action" => 'clearFormCache',
                    "formID" => $id,
                    "toAll" => "yes"
                ], true);
            }

            if (!APP && !Server::isLocalhost()) {

                foreach (["form", "jsform"] as $key) {
                    $res = Settings::getValue("max-cdn-" . $key, $id);
                    if ($res !== false) {
                        if (BETA) {
                            $host = HTTP_URL;
                        } else {
                            $host = Server::getLocalIP();
                        }

                        Utils::suppressRequest($host . "/server.php", [
                            "action" => "clearMaxCDNFormCache",
                            "path" => $key . "/" . $id
                        ]);
                    }
                }

            }
        }
    }

    /**
     * Checks if the form has captcha or not
     * @return // boolean
     */
    public function hasCaptcha()
    {
        return !!$this->getQuestions('control_capthca');
    }

    /**
     * Checks if the form has a payment field or not
     * this function can be real slow find a way to make it faster
     * @return // boolean
     * @deprecated slow and useless
     */
    public function hasPayment()
    {
        return !!$this->getQuestions('control_paypal');
    }

    /**
     * Checks if the form has any upload field or what
     * @return // boolean
     * @deprecated only submissions page uses this and it has a better implementation
     */
    public function hasUpload()
    {
        return !!$this->getQuestions('control_fileupload');
    }

    /**
     * Assign given form to a username This static function can be called without an instance
     * @param  $formID // Form ID to be assigned
     * @param  $username // Username of the new owner
     * @return // boolean
     */
    static function assignOwner($formID, $username, $checkGuest = false)
    {
        if ($checkGuest) {
            $form = new Form($formID);
            if ($form->owner->accountType != "GUEST") {
                return; // Don't assing this form if the form is not belong to a guest account
            }
        }
        $response = DB::write("UPDATE `forms` SET `username`=':username' WHERE `id`=#id", $username, $formID);
        return ($response->rows > 0);
    }

    /**
     * Check if the slug name for this form is available
     * @param  $username
     * @param  $slugName
     * @return
     */
    static function checkSlugAvailable($username, $slugName)
    {
        $response = DB::read("SELECT `id` FROM `forms` WHERE `username`=':username' AND `slug`=':slug'", $username, $slugName);
        if ($response->rows < 1) {
            return true;
        }
        return false;
    }

    /**
     * Inserts the slug name on database
     * @param  $formId
     * @param  $slugName
     * @return
     */
    static function saveSlug($formId, $slugName)
    {
        $res = DB::write("UPDATE `forms` SET `slug`=':slugName' WHERE `id`=':formId'", $slugName, $formId);
        if (!$res->success) {
            return false;
        }
        return true;
    }

    /**
     * Returns the thank you page options for form
     * @return
     */
    public function getThankyouPage()
    {
        $props = [];
        $res = DB::read("SELECT * FROM `form_properties` WHERE `form_id`=#id AND `prop` IN ('activeRedirect', 'thanktext', 'sendpostdata', 'thankurl')", $this->id);
        foreach ($res->result as $line) {
            $props[$line['prop']] = $line['value'];
        }
        return $props;
    }

    /**
     * Get questions of the form
     * @param  $type // [optional] filter questions by type
     * @return
     */
    public function getQuestions($type = false, $qids = false)
    {
        $questions = [];

        if ($type) {
            $qids = []; # Collect question ids for specific type
            if (is_array($type)) {
                $response = DB::read("SELECT `question_id` FROM `question_properties` WHERE `form_id`=#id AND `prop`='type' AND `value` IN ('" . join("', '", $type) . "')", $this->id);
            } else {
                $response = DB::read("SELECT `question_id` FROM `question_properties` WHERE `form_id`=#id AND `prop`='type' AND `value`=':type'", $this->id, $type);
            }
            if ($response->rows < 1) {
                return false;
            }
            foreach ($response->result as $line) {
                $qids[] = $line['question_id'];
            }
        }

        if (is_array($qids)) {
            # Bring collected questions
            $response = DB::read("SELECT * FROM `question_properties` WHERE `form_id`=#id AND `question_id` IN (" . join(', ', $qids) . ")", $this->id);
        } else {
            # Get all questions
            $response = DB::read("SELECT * FROM `question_properties` WHERE `form_id`=#id", $this->id);
        }

        if ($response->rows > 0) {
            foreach ($response->result as $line) {
                if ($line['prop'] == "text") {
                    $line['value'] = stripslashes($line['value']);
                }
                $questions[$line['question_id']][$line['prop']] = $line['value'];
            }
            return $questions;
        }
        return false;
    }

    /**
     * Returns the submissions of the form with a given fileter, sort or page order
     * @param  $sort // [optional]      Define "column name" or "question id" to sort results. Defaults to "created_at"
     * @param  $start // [optional]     Define where will the result array range start. Defaults to "0"
     * @param  $limit // [optional]     Define how many item will be returned. Defaults to "10", SET "-1" to get all submissions
     * @param  $dir // [optional]       Define sort direction. Defaults to "ASC"
     * @param  $keyword // [optional]   Define a search parameter for results. If provided, function will return the results
     *                                     containing this keyword and will keep the sort and page order. Defaults to FALSE
     * @param  $onlyData // [optional]  When set to true only brings the submissin data, doesn't include total and questions array
     * @param  $qids // [optional]      List of questions IDs to limit columns to be shown on results
     * @param  $noMarkup // [optional]  Don't generate HTML code on results
     * @param  $startDate // [optional] Show only the submissions from this date
     * @param  $endDate // [optional]   Show until the submission to this date
     * @return // array Array of results
     */
    public function getSubmissions($sort = "created_at", $start = "0", $limit = "10", $dir = "ASC", $keyword = false, $onlyData = false, $qids = false, $noMarkup = false, $startDate = null, $endDate = null)
    {
        if (USE_DIFFERENT_DB_FOR_SUBMISSONS) {
            DB::useConnection('submissions');
        }
        # If you already have the question beforehand then don't mind doing a query for questions
        if (!$onlyData) {
            $questions = $this->getQuestions(false, $qids);
        } else {
            $questions = is_array($onlyData) ? $onlyData : [];
        }

        # If no limit was sent use 10 as a default
        if (empty($limit)) {
            $limit = 10;
        }

        # If date fielter is sent then create a date range equation to be used in all queries
        $rangeEquation = "";
        if ($startDate) {
            $rangeEquation = " AND (`created_at` >= '" . DB::escape($startDate) . "' AND `created_at` <= '" . DB::escape($endDate) . "' ) ";
        }

        # Initiate the submissions and newOrder array
        $submissions = ["total" => 0, "questions" => $questions];
        $newOrder = ["total" => 0, "questions" => $questions];
        $duplicateAnwerFix = [];
        $items = [];
        $sids = [];
        $searchIDS = [];
        $selectedIDS = [];

        # If keyword was sent, then first search answers for the 
        # matching submissions and collect the submissions IDs
        if ($keyword) {
            if (DEBUGMODE) {
                $response = DB::read("SELECT DISTINCT `submission_id` FROM `answers` WHERE `value` LIKE '%:keyword%' AND form_id=#id ORDER BY IF(`question_id`=#qid, UPPER(`value`), `question_id`) :DIR", ["keyword" => $keyword, "id" => $this->id, "qid" => $sort, "DIR" => $dir]);
            } else {
                $response = DB::read("SELECT `submission_id` FROM `answers` 
                                  WHERE `value` LIKE '%:keyword%' 
                                  AND form_id=#id", ["keyword" => $keyword, "id" => $this->id]);
            }

            foreach ($response->result as $line) {
                $searchIDS[] = $line["submission_id"];
            }
        } else if (DEBUGMODE) {
            $response = DB::read("SELECT DISTINCT `submission_id` FROM `answers` WHERE `form_id`=#formID ORDER BY IF(`question_id`=#qid, UPPER(`value`), `question_id`) :DIR", $this->id, $sort, $dir);
            foreach ($response->result as $line) {
                $selectedIDS[] = $line["submission_id"];
            }
        }

        # If keyword was sent, then get total for matching submissions
        # After total collect all submission data for matching submissions
        if ($keyword && count($searchIDS) > 0) {
            if (!$onlyData) {
                $totResponse = DB::read('SELECT count(id) as total FROM `submissions`
                                         WHERE `id` IN ( :sids )' . $rangeEquation, join(", ", $searchIDS));
            }
            # Get submissios for current page
            $response = DB::read('SELECT * FROM `submissions` WHERE `id` IN ( :sids ) ' . $rangeEquation . ' 
                                  ORDER BY `created_at` :DIR LIMIT #start, #limit',
                ["sids" => join(", ", $searchIDS), "DIR" => $dir, "start" => $start, "limit" => $limit]);

            # If keyword is stated but there no matching answer, then send the empty submissions array
        } else if ($keyword) {
            if (USE_DIFFERENT_DB_FOR_SUBMISSONS) {
                DB::useConnection('new');
            }
            # No Result found with search
            return $submissions;

            # Do the reqular data collection
        } else {
            # If count was already calculated, then don't bother doing it again
            if (!$onlyData) {
                $totResponse = DB::read('SELECT count(id) as total FROM `submissions`
                                         WHERE `form_id`=#id' . $rangeEquation, ["id" => $this->id]);
            }

            # Set created_at as a default sort
            $sortField = "created_at";

            # If if sort field is one of the submissions tale field then use it instead
            if (in_array($sort, ["created_at", "flag", "new", "ip"])) {
                $sortField = $sort;
            }

            $limitText = "";
            # Create the limit phrase of the query
            if ($limit > 0) {
                $limitText = "LIMIT $start, $limit";
            }

            if (count($selectedIDS) > 0) {
                # Get all submissions by given limit sorted by the given column
                $response = DB::read('SELECT * FROM `submissions` WHERE `id` IN ( :sids ) AND `form_id`=#id ' . $rangeEquation . '
                                      ORDER BY `:sortField` :DIR :limitText',
                    ["id" => $this->id, "sortField" => $sortField, "DIR" => $dir, "limitText" => $limitText, "sids" => join(', ', $selectedIDS)]);
            } else {
                # Get all submissions by given limit sorted by the given column
                $response = DB::read('SELECT * FROM `submissions` WHERE `form_id`=#id ' . $rangeEquation . '
                                      ORDER BY `:sortField` :DIR :limitText',
                    ["id" => $this->id, "sortField" => $sortField, "DIR" => $dir, "limitText" => $limitText]);
            }

        }

        # If data was not sent then use the calculated values
        if (!$onlyData) {
            $submissions["total"] = $totResponse->result[0]["total"];
            $newOrder["total"] = $totResponse->result[0]["total"];
        }

        # Preapare the submissions array for collecting answers
        foreach ($response->result as $line) {
            unset($line['form_id']);    # Get rid of the the formID to reduce array size
            $id = $line['id'];          # Put submission ID in a temp value
            unset($line['id']);         # remove submission ID node to reduce array size
            $sids[] = $id;     # put submission ID in submission IDs array
            $submissions[$id] = $line;  # place the database record in submissions array
        }

        # No submission to sort or display
        if (empty($sids)) {
            if (USE_DIFFERENT_DB_FOR_SUBMISSONS) {
                DB::useConnection('new');
            }
            return [];
        }

        if (DEBUGMODE) {
            # Column filter is specified then bring the answers only for given columns
            if (is_array($qids)) {
                $response = DB::read('SELECT * FROM `answers` WHERE `question_id` IN ( :qids ) AND `submission_id` IN ( :sids )',
                    join(", ", $qids), join(", ", $sids));
            } else {
                # Bring all answers sorted by given column
                $response = DB::read('SELECT * FROM `answers` WHERE `submission_id` IN ( :sids )', join(", ", $sids));
            }
        } else {
            # Column filter is specified then bring the answers only for given columns
            if (is_array($qids)) {
                $response = DB::read('SELECT * FROM `answers` WHERE `question_id` IN ( :qids )
                                      AND `submission_id` IN ( :sids ) ORDER BY 
                                      IF(`question_id`=#qid, UPPER(`value`), `question_id`) :DIR 
                                      ', join(", ", $qids), join(", ", $sids), $sort, $dir);
            } else {
                # Bring all answers sorted by given column
                $response = DB::read('SELECT * FROM `answers` WHERE `submission_id` IN ( :sids ) ORDER BY 
                                      IF(`question_id`=#qid, UPPER(`value`), `question_id`) :DIR 
                                      ', join(", ", $sids), $sort, $dir);
            }
        }

        # We have to process the items first to keep the array in order
        foreach ($response->result as $line) {
            # Aliases for values
            # $q = $questions[$line["question_id"]];
            $value = $line['value'];
            # if there is an item name
            if ($line["item_name"] != "") {
                # if not created, create the required nodes in the array 
                if (!isset($items[$line['submission_id']])) {
                    $items[$line['submission_id']] = [];
                }
                if (!isset($items[$line['submission_id']][$line["question_id"]])) {
                    $items[$line['submission_id']][$line["question_id"]] = [];
                }
                # Collect the items in items array. later this items will be used when pouplating the submissions array
                # Also convert JSON items to real arrays
                $items[$line['submission_id']][$line["question_id"]][$line["item_name"]] = Utils::safeJsonDecode($value);
            }
        }

        # Collect values and merge with items
        foreach ($response->result as $line) {
            # If current question is not listed in $questions array skip the living shit out of it.
            if (!isset($questions[$line["question_id"]])) {
                continue;
            }
            # If answers node of submissions array is empty then create a new one
            if (!isset($submissions[$line['submission_id']]["answers"])) {
                $submissions[$line['submission_id']]["answers"] = [];
            }
            # Alias for current question
            $q = $questions[$line["question_id"]];
            # if current question is upload then do what's neccessarry
            if ($q['type'] == "control_fileupload") {
                $files = [];
                if (isset($items[$line['submission_id']][$line["question_id"]])) {
                    foreach ($items[$line['submission_id']][$line["question_id"]] as $f) {
                        $files[] = $f;
                    }
                } else {
                    $files[] = $line['value'];
                }
                $values = [];
                foreach ($files as $filename) {

                    # Place the correct file URL instead of the file name
                    $fullURL = Utils::getUploadURL($this->owner->username, $this->id, $line['submission_id'], $filename);

                    # In order to use data in CSV report or similar we only need URL
                    if ($noMarkup) {
                        $values[] = $fullURL;
                    } else {
                        # For emails and such we need HTML version of the link
                        $values[] = '<a href="' . $fullURL . '" target="_blank">' . $filename . '</a>';
                    }
                }
                $value = join($noMarkup ? "\n" : "<br />", $values);

            } else if ($q['type'] == "control_authnet" || $q['type'] == "control_paypalpro") {
                if (strpos($line['value'], '<table') === false) {
                    Console::error("Broken CC info: " . $line['submission_id']);
                    $value = $items[$line['submission_id']][$line["question_id"]]['price'];
                } else {
                    $value = $line['value'];
                }
            } else {
                # Regular value aliased
                $value = $line['value'];
            }

            # If this is a payent field. fix broken table problem
            # cause of this bug fixed but we keep this here to fix effected entries
            # if you see this comment and have a blank database you don't need this code
            if (in_array($q['type'], Form::$paymentFields)) {
                if (strpos($value, "</tr></td>") !== false) {
                    $value = str_replace("</tr></td>", "</tr></table></td>", $value);
                }
                if (strpos($value, "</table></table>") !== false) {
                    $value = str_replace("</table></table>", "</table>", $value);
                }
            }


            # Create a unique key for each submission to prevent multiple answers in the array
            # This was a problem caused by editing the submissions because we forgot to add unique in the MySQL table
            # and we couldn't be able to add it later, this fix seem to be correct this issue
            $uniqKey = $line['submission_id'] . "-" . $line['form_id'] . "-" . $line['question_id'];

            # If there current answer is not a sub item, such as address values, matrix or checkbox values
            if ($line["item_name"] == "") {

                # Content of the answer which will be placed in the result array
                $answerValue = [
                    "title" => @$q['text'],
                    "name" => @$q["name"],
                    "type" => $q['type'],
                    "submission_id" => $line['submission_id'],
                    "items" => (isset($items[$line['submission_id']][$line["question_id"]]) ? $items[$line['submission_id']][$line["question_id"]] : false),
                    "qid" => $line["question_id"],
                    "order" => $q["order"],
                    "value" => $value //Utils::fixMSWordChars($value) # This shit causes terrible encoding problems.
                ];
                # If this answer was already placed in the array, replace it with the new one
                # By this way we always have the newest entry in the list AKA last edit
                if (isset($duplicateAnwerFix[$uniqKey])) {
                    $submissions[$line['submission_id']]["answers"][$duplicateAnwerFix[$uniqKey]] = $answerValue;
                } else {
                    # If not, then place it in the fix array and push the current one in the list
                    $duplicateAnwerFix[$uniqKey] = count($submissions[$line['submission_id']]["answers"]);
                    $submissions[$line['submission_id']]["answers"][] = $answerValue;
                }
            }
        }

        # Place the submission in $newOrder array to preserve the order of database
        # because $submissions array has the date/time order
        foreach ($response->result as $i => $line) {
            # Place the submission in new array as soon as we see the sorted field
            if ($line["question_id"] == $sort) {
                $newOrder[$line['submission_id']] = $submissions[$line['submission_id']];
            }
        }

        # If it's stated in the arguments remove these fields to reduce request size
        if ($onlyData) {
            unset($submissions['total']);
            unset($submissions['questions']);
            unset($newOrder['total']);
            unset($newOrder['questions']);
        }

        if (USE_DIFFERENT_DB_FOR_SUBMISSONS) {
            DB::useConnection('new');
        }

        # If submissions was sorted by a field in submissions table then use the $submissions order
        if (in_array($sort, ["created_at", "flag", "new", "ip"])) {
            return $submissions;
        } else {
            # if it was a question the use the new order array
            return $newOrder;
        }
    }

    /**
     * Returns the result for a single submission
     * @param  $sid
     * @return
     */
    public static function getSubmissionResult($sid)
    {
        if (USE_DIFFERENT_DB_FOR_SUBMISSONS) {
            DB::useConnection('submissions');
        }

        $response = DB::read("SELECT * FROM `answers` WHERE `submission_id`=':sid'", $sid);
        $result = [];
        $items = [];
        $formID = false;
        $finalResult = [];
        $res = DB::read("SELECT `created_at` FROM `submissions` WHERE `id`=':id'", $sid);
        $date = $res->first['created_at'];
        $result["created_at"] = $date; # @TODO fix time-zone here must must must
        foreach ($response->result as $line) {
            if (!$formID) {
                $formID = $line["form_id"];
            } # get form ID from answers
            if ($line["item_name"] != "") {
                $items[$line["question_id"]][$line["item_name"]] = Utils::safeJsonDecode($line["value"]);
            } else {
                $result[$line["question_id"]] = $line["value"];
            }
        }

        $form = new Form($formID);
        $questions = $form->getQuestions();
        foreach ($result as $qid => $value) {

            if ($qid == "created_at") {
                $finalResult[$qid] = [
                    "value" => $value,
                    "items" => "",
                    "type" => "",
                    "name" => "created_at",
                    "text" => "Submission Date"
                ];
                continue;
            }

            if ($questions[$qid]["type"] == 'control_fileupload') {
                $items[$qid] = $value;
                if (!Utils::contains("<a href", $value)) {
                    $value = Utils::getUploadURL(Session::$username, $formID, $sid, $value);
                }
            }

            $finalResult[$qid] = [
                "value" => $value,
                "items" => (isset($items[$qid]) ? $items[$qid] : ""),
                "type" => $questions[$qid]["type"],
                "name" => $questions[$qid]["name"],
                "text" => $questions[$qid]["text"]
            ];
        }
        if (USE_DIFFERENT_DB_FOR_SUBMISSONS) {
            DB::useConnection('new');
        }
        return $finalResult;
    }

    /**
     * Returns the data for chartable elements
     * @return
     */
    public function getReportsData()
    {
        if (USE_DIFFERENT_DB_FOR_SUBMISSONS) {
            DB::useConnection('submissions');
        }
        $questions = $this->getQuestions(self::$chartableElements);
        if (count($questions) < 1) {
            throw SoftException("No Chartable Elements");
        }

        foreach ($questions as $qid => $question) {

            // When multiple values selected for a question
            if (in_array($question["type"], ["control_checkbox", "control_grading", "control_matrix"])) {
                $response = DB::read("SELECT count(*) as `total`, `value` FROM `answers` WHERE `form_id`=#form_id AND `question_id`=#qid AND `item_name` IS NOT NULL AND `item_name` != '' GROUP BY `value`", $this->id, $qid);
            } else {
                $response = DB::read("SELECT count(*) as `total`, `value` FROM `answers` WHERE `form_id`=#form_id AND `question_id`=#qid AND (`item_name` IS NULL OR `item_name` = '') GROUP BY `value`", $this->id, $qid);
            }

            foreach ($response->result as $line) {
                $questions[$qid]["answers"][$line["value"]] = $line["total"];
            }

        }
        if (USE_DIFFERENT_DB_FOR_SUBMISSONS) {
            DB::useConnection('new');
        }
        return $questions;
    }

    /**
     * Uses The filter of given listing for getSubmissions method
     * @param  $listID
     * @return
     */
    public function useListingFilter($listID)
    {
        if ($list = DataListings::getListing($listID)) {
            $this->filterFields = $list['fields'];
        }
    }

    /**
     * Returns an array of result for ExtJS column structure
     * @return // array
     */
    public function getExtGridStructure($type)
    {

        $questions = $this->getQuestions(false, $this->filterFields);

        $struct["formtitle"] = $this->form['title'];
        $struct["itemname"] = "Submission";

        // Push Submission Defaults
        // if($standAlone){
        if ($type == 'submissions') {


            $struct["fields"][] = ["type" => "string", "name" => "new"];
            $struct["columns"][-6] = ["header" => "New", "dataIndex" => "new", "width" => 30, "sortable" => true, "fixed" => true, "menuDisabled" => true];

            $struct["fields"][] = ["type" => "string", "name" => "flag"];
            $struct["columns"][-5] = ["header" => "Flag", "dataIndex" => "flag", "width" => 30, "sortable" => true, "fixed" => true, "menuDisabled" => true];

            $struct["fields"][] = ["type" => "string", "name" => "del"];
            $struct["columns"][-4] = ["header" => "Del", "dataIndex" => "del", "width" => 30, "sortable" => false, "fixed" => true, "menuDisabled" => true];

        }

        $struct["fields"][] = ["type" => "string", "name" => "id"];
        $struct["fields"][] = ["type" => "string", "name" => "created_at"];
        $struct["fields"][] = ["type" => "string", "name" => "ip"];


        if ($this->filterFields) {
            $hideID = !in_array("-3", $this->filterFields);
        } else {
            $hideID = true;
        }

        $struct["columns"][-3] = ["header" => "ID", "dataIndex" => "id", "width" => 120, "sortable" => true, "hidden" => $hideID];

        $struct["columns"][-2] = ["header" => "Submission Date", "dataIndex" => "created_at", "width" => 130, "sortable" => true];

        # if there is a filter and this filter doesnt containt date time remove it from the list
        if ($this->filterFields && !in_array("-2", $this->filterFields)) {
            unset($struct["columns"][-2]);
        }


        # If there is a filter and contains the IP field
        # then it must be forced to display on the page because IP is initially hidden by default
        if ($this->filterFields) {
            $hideIP = !in_array("-1", $this->filterFields);
        } else {
            $hideIP = true;
        }


        $struct["columns"][-1] = ["header" => "IP", "dataIndex" => "ip", "width" => 120, "sortable" => false, "hidden" => $hideIP];

        # IF there is a filter and IP is hidden then don't even include it on the page
        if ($this->filterFields && $hideIP) {
            unset($struct["columns"][-1]);
        }

        $struct["fields"][] = ["type" => "string", "name" => "submission_id"];
        $struct["fields"][] = ["type" => "string", "name" => "type"];
        $struct["fields"][] = ["type" => "string", "name" => "items"];

        foreach ($questions as $qid => $question) {

            if (!self::isDataField($question["type"])) {
                continue;
            }

            $struct["fields"][] = ["type" => "string", "name" => $qid];
            $struct["columns"][$question["order"]] = [
                "header" => Utils::stripTags($question["text"]), // Strip HTML tags to keep grid columns clean
                "dataIndex" => $qid,
                "order" => $question["order"],
                "width" => ($question["type"] == "control_textarea" || $question["type"] == "control_matrix") ? 500 : 140,
                "sortable" => true,
                "fieldtype" => "textbox"
            ];
        }

        ksort($struct["columns"]);
        $struct["columns"] = array_values($struct["columns"]);

        $struct["formID"] = $this->id;

        if ($type != 'listing') {
            $response = DB::read('SELECT `value` FROM custom_settings `custom_settings` where `identifier` = ":identifier" AND `key` = "extGridState"', $this->id . '-' . $type);

            if ($response->success && $response->rows > 0) {
                $struct['extGridState'] = $response->result[0];
            }
        }

        return $struct;
    }

    /**
     * Returns an array of result set formatted in ExtJS way
     * @param  $sort // [optional]
     * @param  $start // [optional]
     * @param  $limit // [optional]
     * @param  $dir // [optional]
     * @param  $keyword // [optional]
     * @param  $startDate [optional]
     * @param  $endDate [optional]
     * @return // array
     */
    public function getExtGridSubmissions($sort = "created_at", $start = "0", $limit = "10", $dir = "DESC", $keyword = false, $startDate = null, $endDate = null)
    {

        $submissions = $this->getSubmissions($sort, $start, $limit, $dir, $keyword, false, $this->filterFields, false, $startDate, $endDate);

        $questions = $submissions["questions"];

        $response["totalCount"] = $submissions["total"];

        foreach ($submissions as $sid => $sub) {
            if (is_array($sub["answers"])) {
                $answerRow = [];
                if (!isset($answerRow["created_at"])) {

                    $answerRow["created_at"] = TimeZone::convert($sub["created_at"], $this->timeZone, $this->timeFormat); // Convert timezone
                    $answerRow["ip"] = $sub["ip"];
                    $answerRow["id"] = $sid;

                    if ($this->filterFields && !in_array("-1", $this->filterFields)) {
                        unset($answerRow["ip"]);
                    }
                    if ($this->filterFields && !in_array("-2", $this->filterFields)) {
                        unset($answerRow["created_at"]);
                    }
                    $answerRow["flag"] = $sub["flag"] ? 1 : 0;
                    $answerRow["new"] = $sub["new"] ? 1 : 0;
                    $answerRow["del"] = $sub["del"] ? 1 : 0;
                    $answerRow["submission_id"] = "$sid";

                }

                foreach ($sub["answers"] as $answer) {

                    if (!isset($answerRow[$answer["qid"] . "_type"])) {
                        $answerRow[$answer["qid"] . "_type"] = $answer["type"];
                    }

                    if (!empty($answer["items"])) {
                        $answerRow[$answer["qid"] . "_items"] = $answer["items"];
                    }

                    $answerRow[$answer["qid"]] = $answer["value"];
                }
                $response["data"][] = $answerRow;
            }
        }

        return $response;
    }

    /**
     * Returns the array of products associated with this form
     * @return // array|boolean
     */
    public function getProducts()
    {
        $response = DB::read("SELECT * FROM `form_properties` WHERE `form_id`=#id AND `type`='products'", $this->id);
        $products = [];
        if ($response->success && $response->rows > 0) {
            foreach ($response->result as $line) {

                $products[$line['item_id']][$line['prop']] = Utils::safeJsonDecode($line['value']);
            }
            return $products;
        }
        return false;
    }

    /**
     * Sets the flag value of a submission
     * @param  $id
     * @param  $value
     * @return
     */
    public static function setSubmissionFlag($id, $value)
    {
        DB::write("UPDATE `submissions` SET `flag`=#value WHERE `id`=':id'", $value, $id);
    }

    /**
     * Marks submission as read or unread
     * @param  $formID
     * @param  $id
     * @param  $value
     * @return
     */
    public static function setSubmissionReadStatus($formID, $id, $value)
    {

        DB::write("UPDATE `submissions` SET `new`=#value WHERE `id`=':id'", $value, $id);

        if ($value == "1") {
            DB::write("UPDATE `forms` SET `new`=`new`+1 WHERE `id`=':id'", $formID);
        } else {
            DB::write("UPDATE `forms` SET `new`=`new`-1 WHERE `id`=':id'", $formID);
        }

    }

    /**
     * Completely deletes a submission
     * @param  $id
     * @param  $formID
     * @return
     */
    public static function deleteSubmission($id, $formID)
    {
        DB::write("DELETE FROM `submissions` WHERE `id`=':id'", $id);
        DB::write("UPDATE `forms` SET `count`=`count`-1 WHERE `id`=':id'", $formID);

        #------------------------------------------------------------------------------------------------
        # UFSCONTROLLER DELETE---------------------------------------------------------------------------
        #------------------------------------------------------------------------------------------------
        # Create UFSController.
//        $ufsc = new UFSController(Session::$username, $formID, $id);
//        $ufsc->deleteSubmissionFiles();
//        MonthlyUsage::calculateDiskUsage(Session::$username);
        #------------------------------------------------------------------------------------------------
        return true;
    }

    /**
     * Clones the current form
     * @param  $username // [optional] if provided clones the form to given account
     * @return
     */
    public function cloneForm($username = false)
    {
        $newID = ID::generate();

        if (!$username) {
            $username = $this->owner->username;
        }

        # Clone forms table
        $title = $this->form["title"];
        $height = $this->form["height"];
        $source = $this->form["source"];

        # Initial entry 
        $res = DB::write("INSERT INTO `forms` (`id`, `username`, `title`, `height`, `status`, `new`, `count`) 
                          VALUES(#id, ':username', ':title', ':height', 'CLONING', 0, 0)", $newID, $username, "Clone of " . $title, $height);
        if (!$res->success) {
            throw new \Exception("Error while cloning a form. Error:" . $res->error);
        }

        # Clone question properties
        $res = DB::write("INSERT INTO `question_properties` SELECT #newID, `question_id`, `prop`, `value` FROM `question_properties` WHERE form_id=#id", $newID, $this->id);
        if (!$res->success) {
            throw new \Exception("Error while cloning question properties. Error:" . $res->error);
        }
        # clone form properties
        $res = DB::write("INSERT INTO `form_properties` SELECT #newID, `item_id`, `type`, `prop`, `value` FROM `form_properties` WHERE form_id=#id", $newID, $this->id);
        if (!$res->success) {
            throw new \Exception("Error while cloning form properties. Error:" . $res->error);
        }
        # clone reports and such 

        return $newID;
    }

    /**
     * Deletes the form cache for renewal
     * @return
     * @todo fix this function for windows, this is stupid
     */
    public function deleteFormCache()
    {

        $pre = IS_WINDOWS ? "c://" : ""; // Needs this on windows servers

        if (file_exists(CACHEPATH . $this->id . ".html")) {
            if (!unlink($pre . CACHEPATH . $this->id . ".html")) {
                throw new \Exception("Form cache cannot be deleted");
            }
        }
        if (file_exists(CACHEPATH . $this->id . ".js")) {
            if (!unlink($pre . CACHEPATH . $this->id . ".js")) {
                throw new \Exception("Form configuration cache cannot be deleted");
            }
        }
    }

    /**
     * Empty the trash can for user
     * @return
     */
    static function emptyTrash()
    {
        $res = DB::read("SELECT `id` FROM `forms` WHERE `username`=':username' AND `status`='DELETED'", Session::$username);
        foreach ($res->result as $line) {
            $f = new Form($line["id"]);
            $f->deleteForm();
        }
    }

    /**
     * Permanently delete a form
     * @return
     */
    public function deleteForm()
    {
        $res = DB::write('DELETE FROM `forms` WHERE `id`=#id', $this->id);
        if (!$res->success) {
            throw new \Exception("Form cannot be deleted. Error: " . $res->error);
        }

        # Check all form stuf really deleted 
        $res = DB::read('SELECT * FROM `form_properties` WHERE `form_id`=#id', $this->id);

        if ($res->rows > 0) { # Oops foreign keys didn't work, Do this manually
            $tables = ["form_properties", "question_properties", "answers", "listings", "products", "submissions", "upload_files"];
            foreach ($tables as $table) {
                $res = DB::read("DELETE FROM `:table` WHERE `form_id`=#id", $this->id);
            }
        }
        //$this->deleteFormCache();
        MonthlyUsage::calculateDiskUsage($this->owner->username);
        Form::clearCache("id", $this->id); // From all servers
        return true;
    }

    /**
     * Marks the forms as deleted but not really deletes anything
     * @return
     */
    public function markDeleted()
    {
        $res = DB::write("UPDATE `forms` SET `status`='DELETED' WHERE `id`=#id", $this->id);
        if (!$res->success) {
            throw new \Exception("Form cannot be marked as deleted. Error: " . $res->error);
        }
        //$this->deleteFormCache();
        Form::clearCache("id", $this->id); // from all servers
        return true;
    }

    /**
     * Un delete a form
     * @return
     */
    public function unDelete()
    {
        $res = DB::write("UPDATE `forms` SET `status`='' WHERE `id`=#id", $this->id);
        if (!$res->success) {
            throw new \Exception("Form cannot be undeleted. Error: " . $res->error);
        }
        $this->deleteFormCache();
        return true;
    }

    /**
     * Handle slug URL notations such as
     * http://jotfor.ms/username/My_New_Form
     * @return
     */
    public static function handleSlugURLs()
    {

        if (isset($_GET["slug"])) {
            $slug = $_GET["slug"];

            /*
             * form.php is renamed to viewform.php
             * include viewform.php if user is tring to
             * go form.php
             */
            if (trim('' . $slug) === "form.php") {
                include("viewform.php");
                exit;
            }

            # formIdentifier name would be more meaningful
            # for formname. It's not only name id can come also.
            @list($folder, $formname, $more) = explode("/", $slug, 3);


            # Check if the formname belongs to a user name.
            # And open the user's forms.
            if (empty($formname)) { // if no formname
                $p = PageInfo::getPage($folder);

                # If there is a page with this name show it instead
                if (!isset($p["404"])) {
                    $_GET["p"] = $folder;
                    Session::rememberLogin(false);
                    # Never allow guests to create forms
                    if (APP && Session::isGuest() && $folder != 'login') {
                        Utils::redirect(HTTP_URL . "login/");
                    }
                    include ROOT . "/page.php";
                    exit;
                }
                /*
                # Handle the zip files.
                if ( preg_match("/^(\d+)\.zip$/", trim($slug), $m) ){
                    $form_id = $m[1];
                    $file = TRASH_FOLDER . DIRECTORY_SEPARATOR . $form_id.".zip";
                    Utils::downloadFile($file);
                    exit;
                }
                */
                $decodedID = ID::decodeID($folder);
                if (ID::validateID($decodedID)) {
                    Form::displayForm($decodedID);
                    exit;
                } else if (!preg_match('/\D/', $folder)) { # Check if its similiar to a form id
                    // set the prev property
                    Form::displayForm($folder);
                    exit;
                }

                // Utils::redirect(HTTP_URL);
                Utils::show404($slug);
            }

            switch ($folder) {
                case API_URL_BASE:
                    /**
                     * Initialize the REST server
                     * and print the result
                     */
                    $rest = new RestServer();
                    echo $rest->execute();
                    break;
                case "hidebanner":
                    DB::insert('block_email_banners', [
                        "username" => $formname
                    ]);
                    Utils::successPage("You will not receive this anouncement in your e-mail anymore. Thanks.", "Successfully Unsubscribed");
                    break;
                case "unsubscribe":
                    DB::insert('block_list', [
                        "email" => $formname
                    ]);
                    Utils::successPage($formname . " successfully unsubscribed from announcement list.", "Unsubscribed");
                    break;
                case "announcement":

                    $mail = "";
                    $temp = ROOT . "/opt/templates/announcement_mails/";
                    switch ($formname) {
                        case "newyear":
                            $mail = file_get_contents($temp . "new_year_sale_group_a.html");
                            break;
                        case "newyear2":
                            $mail = file_get_contents($temp . "new_year_sale_group_b.html");
                            break;
                        case "newyear3":
                            $mail = file_get_contents($temp . "premiums.html");
                            break;
                        case "last3":
                            $mail = file_get_contents($temp . "new_year_sale_group_last_days3.html");
                            break;
                        case "last2":
                            $mail = file_get_contents($temp . "new_year_sale_group_last_days2.html");
                            break;
                        case "last":
                            $mail = file_get_contents($temp . "new_year_sale_group_last_day.html");
                            break;
                        default:
                            $mail = file_get_contents($temp . $formname . ".html");
                    }
                    $user = User::find($more);
                    if (!$user) {
                        Utils::errorPage("User $more cannot be found on our database", "Wrong username");
                    }
                    $mail = str_replace("{username}", $user->username, $mail);
                    $mail = str_replace("{email}", $user->email, $mail);
                    $mail = str_replace("{FULL_URL}", HTTP_URL, $mail);
                    $mail = str_replace("/images", HTTP_URL . "images", $mail);
                    echo $mail;
                    exit;
                    break;
                case "zip":
                    $file = TRASH_FOLDER . DIRECTORY_SEPARATOR . $formname;
                    Utils::downloadFile($file);
                    break;
                case "submit":
                    if ($_GET) {
                        $_GET['formID'] = $formname;
                    }
                    if ($_POST) {
                        $_POST['formID'] = $formname;
                    }
                    include ROOT . "submit.php";
                    exit;
                    break;
                case "report":
                    if ($_GET) {
                        $_GET['reportID'] = $formname;
                    }
                    if ($_POST) {
                        $_POST['reportID'] = $formname;
                    }
                    include ROOT . "lib/includes/only_report.php";
                    exit;
                    break;
                case "submissions":
                    $_GET["p"] = $folder;
                    $_GET['formID'] = $formname;
                    Utils::setCurrentID("form", $formname);
                    Session::rememberLogin(false);
                    include ROOT . "/page.php";
                    exit;
                    break;
                case "submission":
                    $_GET["sid"] = $formname;
                    include ROOT . "/rssform.php";
                    break;
                case "pdf":
                    $r = new RequestServer([
                        "action" => "exportPDF",
                        "formID" => $formname
                    ]);
                    break;
                case "pdfview":
                    $_GET["sid"] = $formname;
                    include ROOT . "/pdf-view.php";
                    break;
                case "form":
                case "preview":
                    # Check the form on database by using it's id.
                    if (isset($_GET['feedback'])) {
                        Console::log("orangebox," . $_SERVER['HTTP_REFERER'] . "," . $formname . "," . date('Y-m-d', mktime()),
                            "Feedback Track", E_USER_NOTICE, "feedback");
                    }
                    Form::displayForm($formname);
                    break;
                case "jsform":
                    define('JSFORM', true);
                    ob_start();
                    Form::displayForm($formname);
                    $htmlCode = ob_get_contents();
                    ob_end_clean();
                    $jsonCode = json_encode("$htmlCode");

                    parse_str($_SERVER['QUERY_STRING'], $getParams);
                    unset($getParams['slug']);
                    $getParamsJson = json_encode($getParams);

                    header('Content-Type:text/javascript; charset=utf-8');
                    include_once(ROOT . "js/framebuilder.js");
                    break;
                case "grid":
                case "calendar":
                case "rss":
                case "excel":
                case "csv":
                case "table":
                    DataListings::showListing($formname);
                    break;
                default:

                    # Check if the file exist on v2 source
                    Utils::checkOnV2($slug);

                    # Check the form on database
                    $res = DB::read("SELECT * FROM `forms` WHERE `username`=':username' AND `slug`=':slug'", $folder, $formname);
                    if (!$res->success) {
                        Utils::errorPage("There is problem on the page, please correct your URL", "Error on the page");
                    }

                    if ($res->rows < 1) {
                        Utils::show404($slug);
                        # Utils::errorPage("Sorry the form you are looking for is not found on our servers. Please check the URL you've entered", "Form not found");
                    }
                    Form::displayForm($res->first['id']);
                    # Form::displayForm($formname);
                    //Utils::show404($slug);
                    break;
            }
            exit;
        }
    }


    /**
     * Creates a zip file for the source codes.
     * @param  $id
     * @return
     */
    public static function createZip($id, $source)
    {
        /* * /
        # Get the form
        $form = new Form($id);
		
		# get the form name to be used in zip file
        $formName = Utils::fixUploadName($form->getTitle());
		
        # zip path
        $zipFile = TRASH_FOLDER.$formName.'_('.$id.')'.'.zip';
		
        # create the instance
        $zip = new ZipArchive();
        
        # Open a ZipArchive
        if ( $zip->open( $zipFile, ZipArchive::OVERWRITE) === false ){
            throw new \Exception("Cannot create zip archive in Form::createZip function.");
        }
        
        # Create files array to add.
        $files = array( array(  "fileName"  => JS_FOLDER . DIRECTORY_SEPARATOR . "prototype.js",
                                "localName" => "js" . DIRECTORY_SEPARATOR . "prototype.js") ,
        
                        array(  "fileName"  => JS_FOLDER . DIRECTORY_SEPARATOR . "protoplus.js",
                                "localName" => "js" . DIRECTORY_SEPARATOR . "protoplus.js") ,

                        array(  "fileName"  => JS_FOLDER . DIRECTORY_SEPARATOR . "protoplus-ui.js",
                                "localName" => "js" . DIRECTORY_SEPARATOR . "protoplus-ui.js") ,
                        
                        array(  "fileName"  => JS_FOLDER . DIRECTORY_SEPARATOR . "jotform.js",
                                "localName" => "js" . DIRECTORY_SEPARATOR . "jotform.js") ,
                        
                        array(  "fileName"  => JS_FOLDER . DIRECTORY_SEPARATOR . "location.js",
                                "localName" => "js" . DIRECTORY_SEPARATOR . "location.js") ,
                        
                        array(  "fileName"  => JS_FOLDER . DIRECTORY_SEPARATOR . "calendarview.js",
                                "localName" => "js" . DIRECTORY_SEPARATOR . "calendarview.js") ,
                        
                        array(  "fileName"  => STYLE_FOLDER . DIRECTORY_SEPARATOR . "form.css",
                                "localName" => "css" . DIRECTORY_SEPARATOR . "styles" . DIRECTORY_SEPARATOR . "form.css"),
								
					    array(  "fileName"  => CSS_FOLDER . DIRECTORY_SEPARATOR . "calendarview.css",
                                "localName" => "css" . DIRECTORY_SEPARATOR . "calendarview.css"));
                        
        # Add thema css if its needed.
        $style = $form->getProperty('styles');
        if ($style && $style != "form"){
            array_push($files, array("fileName"  => STYLE_FOLDER . DIRECTORY_SEPARATOR . $style.".css",
                                     "localName" => "css/".$style.".css") );
        }
        
        # Add the files return error if the file cannot be added.
        foreach ($files as $file ){
            if ( !$zip->addFile($file['fileName'], $file['localName'] ) ){
                throw new \Exception ("Cannot add ".$file['fileName']." to zip file in Form::createZip function.");
            }
        }
        
        # Add html file here
        if (!$zip->addFromString($formName.'.html', $source)){
            throw new \Exception ("Cannot add source code to the zip file");
        }
        
        # Close the zip folder
        if (!$zip->close()){
            throw new \Exception ("Cannot close zip file.");
        }
        if (!chmod($zipFile, 0777)){
            throw new \Exception ("Cannot change permission of zip file.");
        }
        return $zipFile;
        /* */
    }

    /**
     * Reads the Default properties writtten in javascript then exports them to
     * Only migrate user uses this function it's not needed on applications
     * PHP with JSON
     * @return
     */
    public static function getDefaultProperties()
    {
        $defaultFile = ROOT . "opt/default_options.json";
        if (!file_exists($defaultFile)) {
            chdir("opt/v8/");
            if ($d8 = Utils::findCommand("d8")) {
                system($d8 . ' v8_get_default_properties.js  > ' . $defaultFile);
            } else {
                # Since applications do not use this function I'm not going to implement this
                # but we may need the default properties in the feature then there should be a call to JotForm which
                # asks for the output of this function
            }
        }

        $content = join('', file($defaultFile));
        $exported = json_decode($content, true);

        return $exported['default_properties'];
    }

    /**
     * Checks if the cache file exists either on redis or file cache
     */
    public static function cacheExists($cache)
    {
        if (USE_REDIS_CACHE) {
            $ret = Utils::getRedis(CACHEDB)->exists($cache) === true;
        } else {
            $ret = file_exists(CACHEPATH . $cache);
        }
        return $ret;
    }

    /**
     * Display the form on the page, handle cahces and re-create stuff
     * @param  $id
     * @param  $forceDisplay // [optional]
     * @return
     */
    public static function displayForm($id, $forceDisplay = false)
    {
        $id = (float)$id;

        # Create cache path
        $cache = $id . ".html";
        $dataCache = $id . '.js';
        $v8Config = ROOT . "opt/v8/v8_config.js";
        $configFile = "v8_config";
        $suspendedForm = false;

        # Check if the user has GZIP support or not
        $gzip = strstr(@$_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false;

        if (IS_SECURE) {
            $cache = $id . ".shtml";
            $v8Config = ROOT . "opt/v8/v8_secure_config.js";
            $configFile = "v8_secure_config";
        }

        $key = "max-cdn-form";
        if (defined('JSFORM') && JSFORM === true) {
            $key = "max-cdn-jsform";
            $cache = $id . "-js.html";
            $v8Config = ROOT . "opt/v8/v8_js_config.js";
            $configFile = "v8_js_config";
            if (IS_SECURE) {
                $cache = $id . "-js.shtml";
                $v8Config = ROOT . "opt/v8/v8_secure_js_config.js";
                $configFile = "v8_secure_js_config";
            }
        }

        if (Server::isMaxCDN()) {
            $gzip = true;
            Settings::setSetting($key, $id, date("Y-m-d H:i:s"));
        }

        if (!$gzip) {
            $cache = $id . ".nogz.html";
            $v8Config = ROOT . "opt/v8/v8_nogz_config.js";
            $configFile = "v8_nogz_config";
        }

        if (Utils::debugOption("decompressForm")) {
            $forceDisplay = true;
        }

        $forceDataRenew = false;
        if (Utils::get('nc') !== false) {
            $forceDisplay = true;
            $forceDataRenew = true;
        }

        # Check if the file cache exists
        if ((!self::cacheExists($cache) || $forceDisplay)) { # if not
            # try bringing the form source from database
            $response = DB::read("SELECT * FROM `forms` WHERE `id`=#id", $id);
            if ($response->success) {
                if ($response->first) {
                    $owner = User::find($response->first['username']);
                }

                # if no result was retured then this form must have been deleted
                if ($response->rows < 1 || $response->first["status"] == "DELETED") {

                    if ($forceDisplay) {
                        $printMessage = "This Form has been deleted.";
                    } else {
                        Utils::errorPage("This form has been deleted by the owner. <br>Try contacting the owner of this form.", "Form not found", "", 404);
                    }

                } else if ($response->rows < 1 || $response->first["status"] == "DISABLED") {

                    if ($forceDisplay) {
                        $printMessage = "This Form has been disabled.";
                    } else {
                        Utils::errorPage("This form has been disabled by the owner. <br>Try contacting the owner of this form.", "Form not found", "", 404);
                    }

                } else if ($response->first["status"] == "SUSPENDED" ||
                    $response->first["status"] == "AUTOSUSPENDED" ||
                    $owner->status == "SUSPENDED" || $owner->status == "AUTOSUSPENDED") {
                    # If form is suspended and user is admin than set $forceDisplay
                    # And change the cache file name for people not to reach it.
                    if (Session::isAdmin() || Session::isSupport()) {
                        $forceDisplay = true;
                        $cache = md5($id) . ".html";
                        $dataCache = md5($id) . '.js';
                    }

                    if ($forceDisplay) {
                        $suspendedForm = true;
                        $printMessage = "This Form has been suspended.";
                    } else {
                        Utils::errorPage("This form is disabled.<br><br>", "Form not found", "Suspected Phishing", 404);
                    }
                }

                // Check if the user is OVERLIMIT or something.
                $owner = User::find($response->first['username']);
                if ($owner->status == "OVERLIMIT") {
                    if ($forceDisplay) {
                        $printMessage = "This form has exceeded its allocated quota.";
                    } else {
                        Utils::errorPage("This form has exceeded its allocated quota.<br>", "Form over quota", "", 404);
                    }
                }

                # if there was no cache on the database, then this is the worst case schenario
                $prot = IS_SECURE ? "https" : "http";
                # Force jotform.com in production mode in order to prevent ying.interlogy.com or staging URLs
                $conf = [
                    "HTTP_URL" => (JOTFORM_ENV === "PRODUCTION") ? $prot . "://www.jotform.com/" : HTTP_URL,
                    "SSL_URL" => (JOTFORM_ENV === "PRODUCTION") ? "https://www.jotform.com/" : SSL_URL,
                    "CACHEPATH" => CACHEPATH,
                    "GZIP" => $gzip,
                    "JSFORM" => defined('JSFORM') ? JSFORM : false
                ];

                # If Application use default URLs
                if (APP) {
                    $conf['HTTP_URL'] = HTTP_URL;
                    $conf['SSL_URL'] = SSL_URL;
                }

                if (!file_exists($v8Config)) {
                    touch($v8Config, 0777);
                    chmod($v8Config, 0777);
                    file_put_contents($v8Config, "var V8Config = " . stripslashes(json_encode($conf)) . ";");
                }

                if (!file_exists(CACHEPATH . $dataCache) && !$forceDataRenew) {
                    if (USE_REDIS_CACHE && Utils::getRedis(CACHEDB)->exists($dataCache)) {
                        $data = Utils::getRedis(CACHEDB)->get($dataCache);
                    } else {
                        $form = new Form($id);
                        $prop = $form->getSavedProperties(false);
                        $data = 'getSavedForm({"form":' . json_encode($prop) . ', "success":true})';
                        if (USE_REDIS_CACHE) {
                            Utils::getRedis(CACHEDB)->set($dataCache, $data);
                        }
                    }

                    touch(CACHEPATH . $dataCache, 0777);
                    chmod(CACHEPATH . $dataCache, 0777);
                    file_put_contents(CACHEPATH . $dataCache, $data);
                }

                $debugMode = "";
                if (Utils::debugOption("decompressForm")) {
                    $debugMode = 'debug';
                }
                # Try creating the form source with v8 on the server side
                touch(CACHEPATH . $cache, 0777);
                chmod(CACHEPATH . $cache, 0777);
                chdir("opt/v8/");

                /*
                 * If forceDisplay is enabled, it means that file cache must be created with a name
                 * that JotForm users cannot fetch. This is why I am using md5 of the form id.
                 */
                if ($d8 = Utils::findCommand("d8")) {
                    if ($suspendedForm) {
                        system($d8 . ' v8_build_source.js -- ' . md5($id) . ' ' . $configFile . ' ' . $debugMode . ' > ' . CACHEPATH . $cache);
                    } else {
                        system($d8 . ' v8_build_source.js -- ' . $id . ' ' . $configFile . ' ' . $debugMode . ' > ' . CACHEPATH . $cache);
                    }
                } else {
                    $source = self::retrieveFormSource($id, $prop, $conf, $debugMode == "debug");
                    file_put_contents(CACHEPATH . $cache, $source);
                }

                if (USE_REDIS_CACHE) {
                    Utils::getRedis(CACHEDB)->set($cache, file_get_contents(CACHEPATH . $cache));
                }

            } else {
                # If query gives error print a message on the screen
                Utils::errorPage("There was an error on the server. Please try again later", "Oops!!", $response->error);
            }
        }

        if (USE_REDIS_CACHE) {
            echo Utils::getRedis(CACHEDB)->get($cache);
        } else {
            @chmod(CACHEPATH . $cache, 0777);
            @include CACHEPATH . $cache;
        }

        if (isset($printMessage)) {
            echo "<div style='position:fixed; background:lightyellow; border-bottom:1px solid #ccc; top:0px; left:0px; width:100%; font-family:verdana; opacity:0.5; text-align:right;'>";
            echo " <div style='padding:5px;padding-right:15px;'>";
            echo $printMessage;
            echo " </div>";
            echo "</div>";
        }

        /**
         * If it's in debug mode then delete cache
         */
        if (Utils::debugOption("decompressForm")) {
            if (USE_REDIS_CACHE) {
                $cacheDB->del($cache);
            } else {
                @unlink(CACHEPATH . $cache);
            }
        }
    }

    /**
     * Go ask jotform to create a form source for you.
     * @param  $id
     * @return
     */
    public static function retrieveFormSource($id, $properties = false, $conf = [], $debug = false)
    {

        if ($properties === false || !$properties) {
            $form = new Form($id);
            $prop = $form->getSavedProperties(false);
        } else {
            $prop = $properties;
        }

        $conf = array_merge([
            "HTTP_URL" => HTTP_URL,
            "SSL_URL" => SSL_URL,
            "CACHEPATH" => CACHEPATH,
            "GZIP" => 1,
            "JSFORM" => false
        ], $conf);

        $res = Utils::curlRequest("http://v8.jotform.com/server.php", [
            "action" => "getV8Source",
            "id" => $id,
            "formProperties" => json_encode($prop),
            "config" => json_encode($conf),
            "debug" => $debug
        ]);

        $output = json_decode($res['content']);
        return $output->source;
    }

    /**
     * Create V8 source code for applications that don't have V8 installed
     * @param  $id
     * @param  $formProperties
     * @param  $serverConfig
     * @param  $debug // [optional]
     * @return
     */
    static function createV8Source($id, $formProperties, $serverConfig, $debug = false)
    {
        $TMP_CACHE = '/tmp/appcache/';

        if (!file_exists($TMP_CACHE)) {
            mkdir($TMP_CACHE, 0777);
        }

        $debugMode = "";
        if ($debug) {
            $debugMode = "debug";
        }

        $conf = json_decode($serverConfig, true);

        $cache = $TMP_CACHE . $id . ".html";
        $conf['CACHEPATH'] = $TMP_CACHE;
        $configFile = $TMP_CACHE . $id . "_config";

        file_put_contents($configFile . ".js", "var V8Config = " . stripslashes(json_encode($conf)) . ";");
        file_put_contents($TMP_CACHE . $id . ".js", 'getSavedForm({"form":' . $formProperties . ', "success":true});');
        chdir(ROOT . "opt/v8/");
        # Don't change this. This code runs only on JotForm servers
        system('d8 v8_build_source.js -- ' . $id . ' ' . $configFile . ' ' . $debugMode . ' > ' . $cache);
        if (!file_exists($cache)) {
            throw new \Exception('Could not create form source');
        }

        $html = file_get_contents($cache);
        return $html;
    }

    /**
     * Deletes all the forms associated with the given username.
     * @param  $username
     * @return
     */
    public static function deleteBy($username)
    {
        $response = DB::read("SELECT * FROM forms WHERE `username`=':username'", $username);
        if ($response->success && $response->rows > 0) {
            foreach ($response->result as $line) {
                /* 
                 * For each form, delete answers, form_properties, listings,
                 * products, questions, question_properties, submissions.
                 * TODO: Check for new tables that need to be cleaned.
                 */
                // Delete submissions.
                Submission::deleteBy($username);
                // Delete answers.
                DB::write("DELETE FROM answers WHERE `form_id`='#form_id'", $line['id']);
                // Delete form_properties.
                DB::write("DELETE FROM form_properties WHERE `form_id`='#form_id'", $line['id']);
                // Delete listings.
                DB::write("DELETE FROM listings WHERE `form_id`='#form_id'", $line['id']);
                // Delete questions.
                DB::write("DELETE FROM questions WHERE `form_id`='#form_id'", $line['id']);
                // Delete question properties.
                DB::write("DELETE FROM question_properties WHERE `form_id`='#form_id'", $line['id']);
            }
        }
        // Delete all forms.
        DB::write("DELETE FROM forms WHERE `username`=':username'", $username);
    }

    /**
     * Refreshes the forms ID with the newly created one.
     * @return
     */
    public function renewFormID()
    {

        $newID = ID::generate();
        $res = DB::write("UPDATE `forms` SET `id`=':newID' WHERE `id`=#id", $newID, $this->id);
        if (!$res->success) {
            throw new \Exception('There was a problem on renewing formID');
        }
        # Foreign keys should handle the res but we should double check these kind of stuff 

        $res = DB::read("SELECT * FROM `form_properties` WHERE `form_id`=#id", $newID);
        if (!$res->success) {
            throw new \Exception('There was a problem on renewing formID');
        }

        if ($res->rows < 1) { # Foreign keys didn't work change all tables manually
            $tables = ["form_properties", "question_properties", "answers", "listings", "products", "submissions"];
            foreach ($tables as $table) {
                $res = DB::read("UPDATE `:table` SET `form_id`=':newID' WHERE `form_id`=#id", $table, $newID, $this->id);
                if (!$res->success) {
                    throw new \Exception('There was a problem on renewing formID');
                }
            }
        }
        Utils::setCurrentID("form", $newID);
        Utils::setCookie("formIDRenewed", $newID, "+1 Hour");
    }

    /**
     * Generates an array of data dump, First node of the array contains Titles
     * @return
     */
    public function createDataDump($fieldList = [], $filter = 'exclude', $startDate = "", $endDate = "")
    {

        $dataArray = [[]]; # Data will be filled in this array
        $getQuestions = [];     # In order to protect question order
        $sortedQuestions = [];  # Sort questions and iterate from this array

        $chunk = 1000;               # Split answers into chunks in order to protect server from overload
        $currentChunk = 0;           # Set initial chunk

        $newFieldList = [];
        foreach ($fieldList as $field) {
            if ($field == "-1") {
                $newFieldList[] = "ip";
            } else if ($field == "-2") {
                $newFieldList[] = "created_at";
            } else if ($field == "-3") {
                $newFieldList[] = "id";
            } else {
                $newFieldList[] = $field;
            }
        }

        $fieldList = $newFieldList;

        $initialSubmissions = $this->getSubmissions("created_at", 0, 1); # Get initial data with question information and stuff
        if (empty($initialSubmissions)) {
            return []; # this means there is no submission
        }
        $questions = $initialSubmissions["questions"];                   # Set questions to be used later

        # Sort the questions by their order in the form
        foreach ($questions as $qid => $question) {
            $sortedQuestions[$question['order']] = $question;
        }
        ksort($sortedQuestions);

        array_unshift($sortedQuestions, [
            "type" => "control_textbox",
            "qid" => "ip",
            "text" => "IP"
        ], [
            "type" => "control_textbox",
            "qid" => "id",
            "text" => "Submission ID"
        ], [
            "type" => "control_textbox",
            "qid" => "created_at",
            "text" => "Submission Date"
        ]);

        $matrixProps = [];

        # Seperate non datafields from questions and collect 
        # question titles in the first node of the array
        foreach ($sortedQuestions as $sort => $question) {
            if (in_array($question['type'], self::$nonDataFields)) {
                continue;
            }

            if ($filter == 'exclude') {
                if (in_array($question['qid'], $fieldList)) {
                    continue;
                }
            } else {
                if (!in_array($question['qid'], $fieldList)) {
                    continue;
                }
            }

            $sublabels = false;
            if (isset($question['sublabels']) && !in_array($question['type'], ["control_datetime", "control_phone", "control_paypalpro", "control_authnet", "control_birthdate"])) {
                $sublabels = json_decode($question['sublabels'], true);
                foreach ($sublabels as $key => $name) {
                    if (strpos($key, 'cc_') !== false) {
                        unset($sublabels[$key]);
                        continue;
                    }

                    if ($question['type'] == 'control_fullname') {
                        if (isset($question[$key]) && $question[$key] != "Yes") {
                            unset($sublabels[$key]);
                        }
                    }
                }
            }

            if (Utils::get('old') === false && $question['type'] == "control_matrix") {
                $mrows = explode("|", $question['mrows']);
                $mcolumns = explode("|", $question['mcolumns']);

                $matrixProps[$question['qid']] = [
                    "mrows" => $mrows,
                    "mcolumns" => $mcolumns,
                    "type" => $question['inputType']
                ];

                switch ($question['inputType']) {
                    case "Radio Button":
                        foreach ($mrows as $id => $row) {
                            $dataArray[0][] = Utils::shorten($question['text'], "40") . " >> " . Utils::shorten($row, "40") . "";
                            $getQuestions[] = $question['qid'] . "_" . $id;
                        }
                        break;
                    case "Check Box":
                    case "Text Box":
                    case "Drop Down":
                        foreach ($mrows as $rid => $row) {
                            foreach ($mcolumns as $cid => $column) {
                                $dataArray[0][] = Utils::shorten($question['text'], "40") . " >> " . Utils::shorten($row, "40") . " >> " . Utils::shorten($column, "40") . "";
                            }
                        }
                        $getQuestions[] = $question['qid'] . "_" . $rid;
                        break;
                }

            } else if ($sublabels !== false) {
                foreach ($sublabels as $id => $text) {
                    $dataArray[0][] = $text;
                    $getQuestions[] = $question['qid'] . "_" . $id;
                }
            } else {
                $dataArray[0][] = @$question['text'];
                $getQuestions[] = $question['qid'];
            }
        }

        # Collect data from other chunks
        while ($currentChunk < $initialSubmissions["total"]) {
            # this is a quick fix we need to limit this export by date
            if ($currentChunk > 100000) {
                break;
            }

            $submissions = $this->getSubmissions("created_at", $currentChunk, $chunk, 'DESC', false, $questions, NULL, true, $startDate, $endDate);

            $currentChunk += $chunk;

            foreach ($submissions as $sid => $submission) {
                $answers = [];
                foreach ($getQuestions as $quid) {

                    if ($quid == "ip") {
                        $answers[] = $submission[$quid];
                        continue;
                    }

                    if ($quid == "id") {
                        $answers[] = $sid;
                        continue;
                    }

                    if ($quid == "created_at") {
                        $answers[] = TimeZone::convert($submission[$quid], $this->timeZone, $this->timeFormat); // Convert TimeZone
                        continue;
                    }

                    $itemName = false;
                    if (strpos($quid, "_") !== false) {
                        list($qid, $itemName) = explode("_", $quid, 2);
                    } else {
                        $qid = $quid;
                    }

                    $q = Utils::getArrayValue("qid", $qid, @$submission['answers']); // Get the correct answer in the correct order according to question ID
                    if ($itemName !== false) {
                        if (isset($matrixProps[$qid]) && $matrixProps[$qid]['type'] != 'Radio Button') {
                            if ($matrixProps[$qid]['type'] == 'Check Box') {

                                foreach ($matrixProps[$qid]['mrows'] as $i => $row) {
                                    foreach ($matrixProps[$qid]['mcolumns'] as $column) {
                                        if (isset($q['items'][$i]) && in_array($column, $q['items'][$i])) {
                                            $answers[] = $column;
                                        } else {
                                            $answers[] = "";
                                        }
                                    }
                                }

                            } else if ($matrixProps[$qid]['type'] == "Text Box" || $matrixProps[$qid]['type'] == "Drop Down") {

                                foreach ($matrixProps[$qid]['mrows'] as $i => $row) {
                                    foreach ($matrixProps[$qid]['mcolumns'] as $i2 => $column) {

                                        $answers[] = $q['items'][$i][$i2];
                                    }
                                }

                            }

                        } else if (isset($q['items'][$itemName])) {
                            $answers[] = $q['items'][$itemName];
                        } else {
                            $answers[] = "";
                        }
                    } else {
                        $answers[] = $q["value"];
                    }

                }
                $dataArray[] = $answers;
            }
        }
        return $dataArray;
    }

    /**
     * Recounts the form submissions then updates the table
     * @param  $id
     * @return
     */
    public static function updateSubmissionCount($id)
    {
        $countRes = DB::read("SELECT count(`id`) as `cnt` FROM `submissions` WHERE `form_id`=#id", $id);
        $count = $countRes->first['cnt'];
        DB::write("UPDATE `forms` SET `count`=#count WHERE `id`=#id", $count, $id);
        return $count;
    }

    /**
     * Recounts the new form submissions then updates the table
     * @param  $id
     * @return
     */
    public static function updateNewSubmissionCount($id)
    {
        $countRes = DB::read("SELECT count(`id`) as `cnt` FROM `submissions` WHERE `form_id`=#id AND `new`=1", $id);
        $count = $countRes->first['cnt'];
        DB::write("UPDATE `forms` SET `new`=#count WHERE `id`=#id", $count, $id);
        return $count;
    }

    /**
     * Get CSV data
     * @return
     */
    public function getCSV($fieldList, $filter = 'exclude', $startDate = "", $endDate = "")
    {
        if (!is_array($fieldList)) {
            $fieldList = explode(',', $fieldList);
        }

        $dump = $this->createDataDump($fieldList, $filter, $startDate, $endDate);
        $csv = new CSV($dump);
        $csv->generate();
        if (Utils::get('print') !== false) {
            $csv->printOnScreen(true);
        } else {
            $csv->downloadFile($this->form['title']);
        }
        exit;
    }

    /**
     * Get CSV data
     * @return
     */
    public function getTable($fieldList, $filter = 'exclude', $startDate = "", $endDate = "")
    {
        if (!is_array($fieldList)) {
            $fieldList = explode(',', $fieldList);
        }

        $dump = $this->createDataDump($fieldList, $filter, $startDate, $endDate);
        $csv = new CSV($dump);
        $csv->generateTable($this->form['title']);
        $csv->printOnScreen();
        exit;
    }

    /**
     * Prompts the excel for download
     * @return
     */
    public function getExcel($fieldList = [], $filter = 'exclude', $startDate = "", $endDate = "")
    {
        /* * /

            if(!is_array($fieldList)){
                $fieldList = explode(',', $fieldList);
            }

            $letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $length = strlen($letters);

            $data = $this->createDataDump($fieldList, $filter, $startDate, $endDate);

            $objPHPExcel = new PHPExcel();

            // Set properties
            $objPHPExcel->getProperties()->setCreator("JotForm")
                        ->setLastModifiedBy("Session Username")
                        ->setTitle($this->form['title'])
                        ->setSubject($this->form['title']." Submissions")
                        ->setDescription($this->form['title']." Submissions received at JotForm.com ".time())
                        ->setKeywords("submissions excel jotform")
                        ->setCategory("Submissions");

            $sheet = $objPHPExcel->setActiveSheetIndex(0);

            foreach($data as $i => $line){
                foreach($line as $li => $value){
                    # Prevent Excel column limit
                    if($li > 253){ continue; }

                    if(isset($letters[$li])){
                        $col = $letters[$li];
                    }
                    if($li >= $length){
                        $col = $letters[$li/$length-1].$letters[$li%$length];
                    }

                    $value = Utils::stripTags($value);
                    # Remove Formula mark
                    $value = preg_replace("/^=/", "(=", $value);

                    if($i == 0){ # This is column headers
                        $value = $value;
                    }

                    $sheet->setCellValue($col.($i+1), $value);
                    if($i == 0){ # This is column headers
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }
                }
            }

            $sheet->getStyle('A1:IV1')->applyFromArray(
                array('fill'    => array(
                        'type'      => PHPExcel_Style_Fill::FILL_SOLID,
                        'color'     => array('argb' => 'FFCCFFCC')
                    ),
                    'borders' => array(
                        'bottom'    => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                        'right'     => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
                    ),
                    'font' => array(
                        'bold' =>true,
                        'size' => 11,
                        'align'=> 'center'
                    ),
                    'alignment' => array(
                        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                        'rotation'   => 0,
                        'wrap'       => true
                    )
                )
            );

            $sheet->setTitle('Submissions');
            $date = TimeZone::convert(date('Y-m-d H:i:s'), $this->timeZone, $this->timeFormat);
            $title = preg_replace("/\W+/", "-", $this->form['title']);

            // Redirect output to a client’s web browser (Excel5)
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$title.' - ('.$date.').xls"');
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
            header("Pragma: public");

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
            /* */
    }

    /**
     * Prints a calendar for this form
     * @return
     */
    public function getCalendar($titleField, $dateField)
    {

        $format = "mmddyyyy";
        $events = "";

        # Get the format of the date time field
        $getFormat = DB::read("SELECT `value` FROM `question_properties` WHERE `form_id`=#id AND `question_id`=#qid AND `prop`='format'", $this->id, $dateField);
        if (!empty($getFormat->first['value'])) {
            $format = $getFormat->first['value'];
        }

        //$data = $this->createDataDump(array($titleField, $dateField), 'include');
        $data = $this->getSubmissions($dateField, 0, 10000, 'ASC', false, false, [$dateField, $titleField]);
        unset($data['total']);
        unset($data['questions']);
        foreach ($data as $i => $line) {
            foreach ($line['answers'] as $answer) {
                if ($answer['qid'] == $titleField) {
                    $title = $answer['value'];
                }

                if ($answer['qid'] == $dateField) {
                    $date = $answer['value'];
                }

                if (isset($answer['items']['day'])) {

                    $month = preg_replace("/^0/", "", $answer['items']['month']);
                    $day = preg_replace("/^0/", "", $answer['items']['day']);
                    if ($format == "ddmmyyyy") {
                        $date = $day . "-" . $month . "-" . $answer['items']['year'];
                    } else {
                        $date = $month . "-" . $day . "-" . $answer['items']['year'];
                    }
                }
            }

            $events .= '  Events["' . $date . '"] = "<a href=\"' . HTTP_URL . 'submission/' . $i . '\">' . $title . '</a><br>";' . "\n";
        }

        $html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        $html .= '<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />';
        $html .= '<title>Event Calendar</title></head><style>html,body{height:100%;width:100%;padding:0;margin:0px;}</style><body>';
        $html .= '<script language="javascript" type="text/javascript" src="' . HTTP_URL . 'js/datetimepicker.js"></script>';
        $html .= '<script type="text/javascript">';
        $html .= 'var app_name = "event";';
        $html .= 'var app_field = "event_date";';
        $html .= 'var pmdir = "";';
        $html .= 'var Events = new Array();';

        $html .= $events;

        $html .= '</script>';
        $html .= '<iframe src="about:blank" name="largecalframe" width="100%" height="100%" frameborder="0"></iframe>';
        $html .= '<input type=hidden name=largecalbox id="largecalbox" onChange="alert(this.value)">';
        $html .= '<script>NewCal(\'largecalbox\',\'' . $format . '\',false,12)</script>';
        $html .= '</body></html>';
        echo $html;
    }

    public function deleteAllSubmissions()
    {
        DB::write("DELETE FROM `submissions` WHERE `form_id` = #id", $this->id);
        DB::write("DELETE FROM `pending_submissions` WHERE `form_id` = #id", $this->id);
        DB::write("UPDATE `forms` SET `count` = -1, `new` = -1 WHERE `id` = #id", $this->id);
        DB::write("UPDATE `upload_files` SET `uploaded` = 0 WHERE `form_id` = #id", $this->id);
        MonthlyUsage::calculateDiskUsage(Session::$username);
    }

    /**
     * Gets the form source from cache
     * @param  $id
     * @return
     */
    public static function getSource($id)
    {
        ob_start();
        Form::displayForm($id);
        $formsource = ob_get_contents();
        ob_clean();
        return $formsource;
    }

    /**
     * Delete maxCDN cache
     * @param  $path
     * @return
     */
    public static function clearMaxCDNCache($path)
    {
        /* * /
        if(APP){ return; }
        date_default_timezone_set('America/Los_Angeles');
        @include_once(ROOT."lib/classes/xmlrpc/xmlrpc.php");
        $cur = date('c');
        $apiKey     = Configs::MAX_CDN_API_KEY;
        $apiUserId  = Configs::MAX_CDN_USER_ID;
        $namespace  = 'cache';
        $method     = 'purge';
        $authString = hash('sha256', $cur . ':' . $apiKey . ':' . $method);

        // this is the url to purge
        $url= 'http://'.Configs::MAX_CDN_DOMAIN.'.netdna-cdn.com/'.$path;
        $f = new xmlrpcmsg("$namespace.$method", array(
                php_xmlrpc_encode($apiUserId),
                php_xmlrpc_encode($authString),
                php_xmlrpc_encode($cur),
                php_xmlrpc_encode($url)
             )
        );

        $c = new xmlrpc_client("/xmlrpc/cache", "api.netdna.com", 80, 'http11');
        $r = &$c->send($f);
        # Console::log($r);
        if($r->errno > 0){
            throw new \Exception($r->errmsg);
        }
        return true;
    /* */
    }

    /**
     * Purge all caches from max cdn
     */
    public static function purgeAllMaxCDNCache()
    {
        /* * /
        if(APP){ return; }
        date_default_timezone_set('America/Los_Angeles');
        @include_once(ROOT."lib/classes/xmlrpc/xmlrpc.php");
        $cur = date('c');
        $apiKey     = Configs::MAX_CDN_API_KEY;
        $apiUserId  = Configs::MAX_CDN_USER_ID;
        $namespace  = 'cache';
        $method     = 'purgeAllCache';
        $authString = hash('sha256', $cur . ':' . $apiKey . ':' . $method);
                
        $f = new xmlrpcmsg("$namespace.$method", array(
                php_xmlrpc_encode($apiUserId),
                php_xmlrpc_encode($authString),
                php_xmlrpc_encode($cur),
                php_xmlrpc_encode("form")
             )
        );

        $c = new xmlrpc_client("/xmlrpc/cache", "api.netdna.com", 80, 'http11');
        $r = &$c->send($f);
        # Console::log($r);
        if($r->errno > 0){
            throw new \Exception($r->errmsg);
        }
        return true;
        /* */
    }

}
