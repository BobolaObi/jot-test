<?php

namespace Quarantine;;

use AmazonS3Controller;
use Legacy\Jot\RequestServer;
use Legacy\Jot\Utils\Console;
use Legacy\Jot\Utils\DB;
use Legacy\Jot\Utils\Utils;


class WaitingUploadsController
{

    const offset = 500;
    const testLimit = false;
    private $total, $start, $as3c;

    public function loopAllUploads()
    {
        # Initialize amazon
        $this->as3c = new AmazonS3Controller();
        $this->as3c->setProperties();

        # Get the total count of unfinished uploads
        $res = DB::read("SELECT count(*) as c FROM `upload_files` WHERE `uploaded` = 0");
        # Store the total count
        $this->total = $res->first['c'];

        # if total is zero than exit the script.
        if ($this->total === 0) {
            Console::log("There is no waiting uploads.. Exiting the script.");
            return true;
        }

        # Set if there is a test limit.
        if (self::testLimit !== false) {
            $this->total = ($this->total < self::testLimit) ? $this->total : self::testLimit;
        }

        # Set start.
        $this->start = 0;

        # Calculate limit
        while ($this->start < $this->total) {
            usleep(3 * 1000000);

            # Calculate the limit.
            $limit = ($this->total - $this->start) < self::offset ? ($this->total - $this->start) : self::offset;

            # If offset is false, make same as the total.
            if (self::offset === false) {
                $limit = $this->total;
            }

            # Log the loop
            Console::log("Total: {$this->total} From: {$this->start} To: " . ($limit + $this->start));
            # Get the uploads
            $res = DB::read("SELECT * FROM `upload_files` WHERE `uploaded` = 0 ORDER BY `id` ASC LIMIT {$this->start}, {$limit}");

            # Loop uploads
            foreach ($res->result as $row) {
                usleep(0.2 * 1000000);
                # Upload file
                $this->completeUpload($row);
            }
            # Update the start
            $this->start += $limit;
        }
    }

    public function completeUpload($row)
    {
        Console::log("Uploading entry with id: " . $row['id']);

        # File path on server
        $filePath = UPLOAD_FOLDER . "/" . $row['username'] . "/" . $row['form_id'] . "/" . $row['submission_id'] . "/"
            . Utils::fixUploadName($row['name']);
        # base path for amazon
        $baseName = $row['username'] . "/" . $row['form_id'] . "/" . $row['submission_id'] . "/"
            . Utils::fixUploadName($row['name']);

        # Entry id
        $entryID = $row['id'];

        # Set id of the file for completeUpload
        $this->as3c->setInsertID($entryID);

        # in Amazon
        $inAmazon = $this->as3c->fileExists("jufs", $baseName);
        # in submissions
        $res = DB::read("SELECT * FROM `submissions` WHERE `id` = ':sid'", $row['submission_id']);
        $inSubmission = $res->rows > 0 ? true : false;

        if ($row['size'] == 0) {
            Console::log("Size of entry is 0. Passing it.");
            $this->as3c->disableEntry();
        } else if (!$inSubmission && $inAmazon) {
            Console::log("Entry does not exists in submissions, but exists in Amazon.");
            # delete from  amazon and db
            $this->as3c->suppressDelete($baseName);
            $this->as3c->disableEntry();
            Console::log("Entry deleted from amazon and removed from upload_files.");
        } else if (!$inSubmission && !$inAmazon) {
            Console::log("Entry does not exists in submissions and Amazon");
            $this->as3c->disableEntry();
            Console::log("Entry removed from upload_files.");
        } else if ($inSubmission && $inAmazon) {
            Console::log("Entry exists in submissions and Amazon");
            $this->as3c->completeUpload();
            Console::log("Entry updated at upload_files.");
        } else if ($inSubmission && !$inAmazon) {
            Console::log("Entry exists in submissions, but does not exists in Amazon");
            # if file does not exists, look to the other servers
            if (!file_exists($filePath)) {
                Console::log("File is not uploaded in this server. Looking to other servers..");

                $request = new RequestServer(array(
                    "action" => "sendFileToAmazonS3",
                    "filePath" => $filePath,
                    "baseName" => $baseName,
                    "formID" => $formID,
                    "toAll" => "yes",
                    "async" => "no",
                    "skipSelf" => "yes"
                ), true);

                $responses = $request->getResponse()->other_responses;
                $found = false;
                foreach ($responses as $server => $response) {
                    if ($response->success) {
                        $found = true;
                        break;
                    }
                }
                if ($found === false) {
                    Console::log("Cannot find file in other servers: " . print_r($responses, true));
                    $this->as3c->disableEntry();
                } else {
                    Console::log("File founded in other servers.");
                }

            } else {
                Console::log("Entry Exists in this server. Uploading..");
                # Upload the file finally
                Console::log("File path: {$filePath}, Base name: {$baseName}");
                if ($this->as3c->suppressUpload($filePath, $baseName)) {
                    Console::log("Upload completed successfully.");
                } else {
                    Console::log("Error in upload file.");
                }
            }
        }
    }
}
