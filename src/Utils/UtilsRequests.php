<?php

/**
 * Utilities for handling Requests on Jotform
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;


class UtilsRequests extends UtilsEmails
{
    /**
     * Gets a value from Request array
     * it can be POST or GET checks if it's set then returns the value
     * if not found returns false
     * @param  $key
     * @return
     */
    static function get($key)
    {
        if (isset($_REQUEST[$key])) {
            return $_REQUEST[$key];
        } else if (isset($_GET[$key])) { # Can be placed after $_REQUEST populated
            return $_GET[$key];
        } else if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        return false;
    }

    /**
     * Gets the version of A/B test
     * @return
     */
    static function getTestVersion()
    {
        if (isset($_GET['ver'])) {

            if ($_GET['ver'] == "0") {
                Utils::deleteCookie("ver");
                return false;
            }

            Utils::setCookie("ver", $_GET['ver'], "+1 Day");
            return $_GET['ver'];
        }
        return Utils::getCookie('ver');
    }

    /**
     * Stops browser to post back to old URL
     * @return
     */
    static function stopPostBack()
    {
        header('Pragma: public');
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");                 // Date in the past   
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1
        header("Pragma: no-cache");
        header("Expires: 0");
    }

    /**
     * Retuns the value of debug options
     * @param  $key
     * @return
     */
    static function debugOption($key)
    {

        if (isset($GLOBALS['debug_options']) && isset($GLOBALS['debug_options'][$key])) {
            return $GLOBALS['debug_options'][$key];
        }

        return false;
    }

    /**
     * Tries to get the current formID or reportID
     * from the cookie or URL
     * @return
     */
    static function getCurrentID($type)
    {

        if (isset($_GET['new']) && $_GET['new'] == $type) {
            Utils::deleteCookie("last_" . $type);
            return false;
        } else if (isset($_GET[$type . 'ID']) && $_GET[$type . 'ID'] != 'session') {
            Utils::setCookie("last_" . $type, $_GET[$type . 'ID'], "+1 Month");
            return $_GET[$type . 'ID'];
        } else if (Utils::getCookie("last_" . $type)) {
            return Utils::getCookie("last_" . $type);
        }

        return false;
    }

    /**
     * Sets the current ID for specific type
     * @param  $type
     * @param  $id
     * @return
     */
    static function setCurrentID($type, $id)
    {
        Utils::setCookie("last_" . $type, $id, "+1 Month");
    }

    /**
     * Dletes the current id
     * @param  $type
     * @return
     */
    static function deleteCurrentID($type)
    {
        Utils::deleteCookie("last_" . $type);
    }

    /**
     * Will force given file be downloaded
     * @param  $file  // Source file
     * @param  $name  // Filename to be seen on the screen
     * @param  $mime_type  // [optional]
     * @return
     */
    static function forceDownload($file, $name, $mime_type = '')
    {
        /*
         This function takes a path to a file to output ($file),
         the filename that the browser will see ($name) and
         the MIME type of the file ($mime_type, optional).
         
         If you want to do something on download abort/finish,
         register_shutdown_function('function_name');
         */
        if (!is_readable($file)) {
            throw new \Exception('File not found or inaccessible!');
        }

        $size = filesize($file);
        $name = rawurldecode($name);

        /* Figure out the MIME type (if not specified) */
        $known_mime_types = array(
            "pdf" => "application/pdf",
            "txt" => "text/plain",
            "html" => "text/html",
            "htm" => "text/html",
            "exe" => "application/octet-stream",
            "zip" => "application/zip",
            "doc" => "application/msword",
            "xls" => "application/vnd.ms-excel",
            "ppt" => "application/vnd.ms-powerpoint",
            "gif" => "image/gif",
            "png" => "image/png",
            "jpeg" => "image/jpg",
            "jpg" => "image/jpg",
            "php" => "text/plain"
        );

        if ($mime_type == '') {
            $file_extension = strtolower(substr(strrchr($file, "."), 1));
            if (array_key_exists($file_extension, $known_mime_types)) {
                $mime_type = $known_mime_types[$file_extension];
            } else {
                $mime_type = "application/force-download";
            }
        }


        @ob_end_clean(); //turn off output buffering to decrease cpu usage

        // required for IE, otherwise Content-Disposition may be ignored
        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');

        /* The three lines below basically make the 
         download non-cacheable */
        header("Cache-control: private");
        header('Pragma: private');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        // multipart-download and download resuming support
        if (isset($_SERVER['HTTP_RANGE'])) {
            list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
            list($range) = explode(",", $range, 2);
            list($range, $range_end) = explode("-", $range);
            $range = intval($range);
            if (!$range_end) {
                $range_end = $size - 1;
            } else {
                $range_end = intval($range_end);
            }

            $new_length = $range_end - $range + 1;
            header("HTTP/1.1 206 Partial Content");
            header("Content-Length: $new_length");
            header("Content-Range: bytes $range-$range_end/$size");
        } else {
            $new_length = $size;
            header("Content-Length: " . $size);
        }

        /* output the file itself */
        $chunksize = 1 * (1024 * 1024); //you may want to change this
        $bytes_send = 0;
        if ($file = fopen($file, 'r')) {
            if (isset($_SERVER['HTTP_RANGE'])) {
                fseek($file, $range);
            }

            while (!feof($file) && (!connection_aborted()) && ($bytes_send < $new_length)) {
                $buffer = fread($file, $chunksize);
                print($buffer); //echo($buffer); // is also possible
                flush();
                $bytes_send += strlen($buffer);
            }
            fclose($file);
        } else {
            throw new \Exception('Error - can not open file.');
        }
        die();
    }

    /**
     *
     * @param  $fullPath
     * @return
     */
    static function downloadFile($fullPath)
    {
        if ($fd = fopen($fullPath, "r")) {
            $fsize = filesize($fullPath);
            $path_parts = pathinfo($fullPath);
            $ext = strtolower($path_parts["extension"]);
            switch ($ext) {
                case "zip":
                    header("Cache-Control: public");
                    header("Pragma: public");
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    header("Cache-Control: public");
                    header("Content-Description: File Transfer");
                    header("Content-Type: application/zip");
                    header("Content-Disposition: attachment; filename=\"" . $path_parts["basename"] . "\"");
                    header("Content-Transfer-Encoding: binary");
                    header("Content-Length: " . $fsize);
                    break;
            }
            while (!feof($fd)) {
                $buffer = fread($fd, 2048);
                echo $buffer;
            }
        }
        fclose($fd);
    }

    /**
     * Will decode encoded arguments and put them into get or post values
     * @return
     */
    static function handleBase64Requests()
    {
        if (Utils::get('encoded') === false && Utils::get('enc') === false) {
            return;
        }     // If encoded was provided
        $encoded = Utils::get('encoded') !== false ? Utils::get('encoded') : Utils::get('enc');
        $parameters = Utils::decodeURI($encoded);
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {        // Append the request in globals according to the
            unset($_GET['encoded']);
            $_GET = array_merge($_GET, $parameters);
        } else {
            unset($_POST['encoded']);
            $_POST = array_merge($_POST, $parameters);
        }

        return $parameters;
    }

    /**
     * Checks if the file existed on V2 or not
     * @param  $missingPage
     * @return
     */
    static function checkOnV2($missingPage)
    {

        if (APP) {
            return;
        } # Should not work for applications

        if (file_exists("/www/v2/" . $missingPage)) {
            if (IS_SECURE) {
                $protocol = "http"; // No secure
            } else {
                $protocol = "http";
            }
            Utils::redirect($protocol . "://v2.jotform.com/" . $missingPage, $_GET ? $_GET : $_POST);
            exit;
        }
    }

    /**
     * Displays a proper 404Page
     * @param  $missingPage
     * @return
     */
    static function show404($missingPage)
    {

        Utils::checkOnV2($missingPage);
        ob_clean();
        header("Content-Type:text/html; charset=utf-8");
        header("HTTP/1.0 404 Not Found");
        header("Status: 404 Not Found");
        include ROOT . "opt/404.html";
        exit;
    }

    /**
     * More Convenient cookie function
     * Give expire as a natural dates or string such as
     * <pre>
     * Next tuesday,
     * +1 year,
     * -1 month,
     * Yesterday,
     * 10/20/2012
     * </pre>
     * @param  $name  // Name of the cookie
     * @param  $value  // Value of the cookie
     * @param  $expire  // Expiration date in as human readable format (ex: 1 Month)
     * @param  $path  // [optional] Path of the cookie
     * @param  $domain  // [optional] Domain of the cookie
     * @param  $httponly  // [optional] Is cookie HTTP only?
     * @return // boolean
     */
    static function setCookie($name, $value, $expire, $path = "/", $domain = null, $httponly = null)
    {
        # domain required in php8
        if (DOMAIN) {
            $domain = "." . DOMAIN;
        }

        Utils::deleteCookie($name); # delete the cookie first to prevent duplications
        return setcookie($name, $value, strtotime($expire), $path, $domain, $httponly ?: false);
    }

    /**
     * Reads the cookie
     * @param  $name  // Name of the cookie
     * @return // string|boolean if found returns cookie value if not returns false
     */
    static function getCookie($name)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : false;
    }

    /**
     * Deletes a cookie
     * @param  $name  // Name of the cookie
     * @return // boolean
     */
    static function deleteCookie($name)
    {
        // Delete cookie from domain too
        if (DOMAIN) {
            setcookie($name, "", strtotime("-1 Year"), "/", "." . DOMAIN);
        }
        return setcookie($name, "", strtotime("-1 Year"), "/");
    }

    /**
     * Collects all redirects to one function and makes some work on them
     * @param  $url
     * @param  $data  // [optional] Get parameters
     * @return
     */
    static function redirect($url, $data = array(), $bustFrame = false)
    {

        $quesryString = array();
        session_write_close();
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($value === false) {
                    continue;
                }
                $quesryString[] = $key . "=" . urlencode($value);
            }
        }

        Console::info($url, "Redirect URL");
        Console::info($data, "Redirect Parameters");

        if (count($quesryString) > 0) {
            $url .= "?" . join("&", $quesryString);
        }

        if ($bustFrame) {
            echo "<script>top.location.href = '$url'; </script>";
            ob_flush();
            flush();
            exit;
        }


        if (!@header("Location: $url")) {
            echo "<script>location.href = '$url'; </script>";
            ob_flush();
            flush();
        }

        if (ob_get_contents()) {
            ob_flush();
        }
        exit;
    }

    /**
     * Creates the HTML of post form.
     * @param  $action
     * @param  $parameters
     * @return // string HTML of the form
     */
    static function postRedirect($action, $parameters)
    {

        session_write_close();
        $parameters = Utils::toArray($parameters);
        foreach ($parameters as $key => $value) {
            if ($value === false) { // remove the false values
                unset($parameters[$key]);
            }
        }

        Console::info($action, "Redirect URL");
        Console::info($parameters, "Redirect parameters");
        ob_clean();
        header("Content-type: text/html; charset=utf-8");

        // form submit target.
        $target = "_top";

        // if the action is cms.interlogy.com than change the target.
        if (stristr($action, "cms.interlogy.com")) {
            $target = "_self";
        }

        echo '<html><head><style>html,body{height:100%; width:100%; margin:0; padding:0;}</style></head><body>';
        echo '<table cellpadding="0" height="100%" width="100%" cellspacing="0" border="0"><tr><td align="center" valign="middle" style="font-family:Verdana; font-size:16px;">';
        include DROOT . "/opt/templates/loading_gif.php";
        echo '<img src="' . $loadingGif . '" alt="Redirecting Please wait..." />', "\n";
        echo '<h3>Please wait while redirecting...</h3>', "\n\n";

        $form = '<form action="' . $action . '" method="post" target="' . $target . '" id="submitForm">' . "\n";
        foreach ($parameters as $key => $value) {
            $key = htmlentities($key, ENT_QUOTES, 'UTF-8');
            if ($key == "submit") continue; // breaks the submit() function of the redirect

            if (is_array($value)) {
                foreach ($value as $v) {
                    $v = str_replace('"', "&quot;", $v);
                    $form .= '<input type="hidden" name="' . $key . '[]" value="' . $v . '" />' . "\n";
                }
            } else {
                $value = str_replace('"', "&quot;", $value);
                $form .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />' . "\n";
            }
        }
        $form .= '</form>' . "\n";
        $form .= '<script type="text/javascript">setTimeout(function(){document.getElementById("submitForm").submit();},0);</script>';

        echo $form;

        echo '</td></tr></table></body></html>';
        exit;
    }

    /**
     * Checks if the given url exists
     * @param $url Object
     * @return
     */
    static function urlExists($url)
    {
        $url = parse_url($url);
        $url = $url[host];
        if (strstr($url, "/")) {
            $url = explode("/", $url, 2);
            $url[1] = "/" . $url[1];
        } else {
            $url = array($url, "/");
        }

        $fh = @fsockopen($url[0], 80);
        if ($fh) {
            @fputs($fh, "GET " . $url[1] . " HTTP/1.1\nHost:" . $url[0] . "\n\n");
            if (@fread($fh, 22) == "HTTP/1.1 404 Not Found") {
                return false;
            } else {
                return true;
            }

        } else {
            return false;
        }
    }

    /**
     * Tries to get mime data of the file.
     * @param $filename String
     * @return {String} mime-type of the given file
     * @TODO extend mimetypes array using apache's mime.types file
     */
    static function getMimeType($filename)
    {
        $mime_types = array(

            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = strtolower(array_pop(explode('.', $filename)));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } else if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        } else {
            return 'application/octet-stream';
        }

    }

    /**
     * Get a page source with curl
     * @param  $url
     * @return
     */
    static function curlRequest($url, $params = array())
    {

        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING => "",       // handle all encodings
            // Looks like FireFox but still identifies as JotForm and prevents us from getting blocked
            CURLOPT_USERAGENT => "Mozilla/5.0 (JotForm.com - Form Import Engine; en-US; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13", // who am i
            CURLOPT_AUTOREFERER => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT => 120,      // timeout on response
            CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
        );

        if (count($params) > 0) {
            $options[CURLOPT_POST] = true;
            $paramPairs = array();
            foreach ($params as $key => $value) {
                $paramPairs[] = $key . "=" . urlencode($value);
            }
            $options[CURLOPT_POSTFIELDS] = implode('&', $paramPairs);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);

        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;
        return $header;
    }

    /**
     * Posts data to a page and gets the result back
     * Put false to a value if you want the key to be deleted before post
     * @TODO When you use this function to a page hosted on the same server it gets into a loop Should find a solution
     * @param  $url
     * @param  $data
     * @param  $optional_headers  // [optional]
     * @return
     */
    static function postRequest($url, $data, $optional_headers = null)
    {

        foreach ($data as $key => $value) {
            if ($value === false) { // remove the false values
                unset($data[$key]);
            }
        }

        Console::info($url, "Posted URL");
        Console::info($data, "Posted Array");

        if (!is_string($data)) {
            $data = http_build_query($data);
        }
        $params = array('http' => array(
            'method' => 'POST',
            'content' => $data,
            'header' => 'Content-type:application/x-www-form-urlencoded'
        ));

        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }
        $ctx = stream_context_create($params);
        if ($response = @file($url, NULL, $ctx)) {
            return join("", $response);
        } else {
            Console::error("Data could not be posted to: " . $url, "Error on postRequest");
            return false;
        }
    }

    /**
     * Tries to suppress the post request, Especially good for requests which take long time to complete
     * IMPORTANT: if you need to know the response use Utils::postRequest
     * @param  $url  // Url which data will be posted
     * @param  $request  // Data
     * @return // null
     */
    static function suppressRequest($url, $data)
    {
        # Check curl exists 
        if ($curl = Utils::findCommand('curl')) {

            $params = array();
            foreach ($data as $key => $value) {
                array_push($params, $key . "=" . $value);
            }

            $readyParams = "-d " . join(" -d ", $params);

            # Console::log(escapeshellcmd( $curl . " " . $url . " " . $readyParams ). " > /dev/null 2>&1 &", "Process will be suppressed");

            shell_exec(escapeshellcmd($curl . " " . $url . " " . $readyParams) . " > /dev/null 2>&1 &");
        } else {
            # if no curl then use regular postRequest
            Utils::postRequest($url, $data);
        }
    }

    static function processTweetLinks($text)
    {
        $text = utf8_decode($text);
        $text = preg_replace('@(https?://([-\w\.]+)+(d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', '<a href="$1">$1</a>', $text);
        $text = preg_replace("#(^|[\n ])@([^ \"\t\n\r<]*)#ise", "'\\1<a href=\"http://twitter.com/intent/user/?screen_name=\\2\" >@\\2</a>'", $text);
        $text = preg_replace("#(^|[\n ])\#([^ \"\t\n\r<]*)#ise", "'\\1<a href=\"http://hashtags.org/search?query=\\2\" >#\\2</a>'", $text);
        return $text;
    }

    /**
     * Return the list of latest tweets
     * @return
     */
    static function getLatestTweets($count = 4)
    {
        if (!function_exists("curl_init")) {
            return false;
        }
        # Check tweets every 5 minutes
        $time = Utils::cacheGet("tweets_read", 0);
        $r = Utils::cacheGet("tweets", false);
        if (($r === false || ((time() - $time) > 60 * 10))) {
            Console::log('Feed checked');
            $path = "http://twitter.com/statuses/user_timeline/jotform.json";
            $r = self::curlRequest($path);
            if ($r['http_code'] == "200" || $r['http_code'] == "304") {
                Utils::cacheStore("tweets_read", time());
                Utils::cacheStore("tweets", $r['content']);
            }
        }
        $r = Utils::cacheGet("tweets", false);


        # get tweets
        $tweets = json_decode($r, true);
        # no tweet found
        if ($tweets === null) {
            return false;
        }

        $selectedTweets = array();

        foreach ($tweets as $tweet) {
            # Do not display reply tweets
            if ($tweet['in_reply_to_user_id_str'] !== null) {
                continue;
            }
            # if this is a direct mention do not display here, sort of like a reply
            if (Utils::startsWith($tweet['text'], '@')) {
                continue;
            }
            # do not display our retweets
            if ($tweet['retweeted'] === true) {
                continue;
            }


            $selectedTweets[] = array(
                "id" => $tweet['id_str'],
                "tweet" => self::processTweetLinks($tweet['text']),
                "date" => Utils::dateToWords(strtotime($tweet["created_at"]))
            );

            if (count($selectedTweets) >= $count) {
                break; # collect only what we need
            }
        }

        return $selectedTweets;
    }

    /**
     * Return the HTML markup for tweets
     * @return
     */
    static function getTweetStream()
    {

        $tweets = self::getLatestTweets(4);
        if ($tweets === false) {
            return "";
        } # no tweet returned

        $html = '<ul class="tweets">';
        foreach ($tweets as $i => $tweet) {

            $rt = "RT @JotForm " . strip_tags($tweet['tweet']);

            $html .= '<li class="tweet-' . ($i + 1) . '"><div class="tweet-body">' . $tweet['tweet'] . "</div>" .
                '<div class="tweet-buttons">' .
                '<a title="Reply" class="reply-link" target="_blank" href="http://twitter.com/intent/tweet?in_reply_to=' . $tweet['id'] . '"></a>' .
                '<a title="Retweet" class="retweet-link" target="_blank" href="http://twitter.com/intent/retweet?tweet_id=' . $tweet['id'] . '"></a>' .
                '</div>' .
                '<div class="tweet-date">' .
                $tweet['date'] .
                '</div>' .
                '</li>';
        }
        $html .= "</ul>";
        return $html;
    }
}