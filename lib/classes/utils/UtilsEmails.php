<?php
/**
 * Email Handling functions for JotForm
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
class UtilsEmails {
    
    /**
     * Splits the email aaddresses then cheks their format
     * @param object $string
     * @return 
     */
    static function splitEmails($emails){
        # Emails will be collected in this array.
        $mails = array();
        
        if(is_array($emails)){
            foreach ($emails as $email){
                $mails = array_merge( $mails, Utils::splitEmails($email) );
            }
        }else{
            $tokens = preg_split("/\;|\,|\s+|\n/", $emails);
            foreach($tokens as $t){
                if(Utils::checkEmail($t, true)){
                    array_push($mails, $t);
                }
            }
        }
        
        return $mails;
    }
    
    /**
     * If email address is in bounced list it will return true
     * @param object $email
     * @return 
     */
    private static function checkEmailInBouncedList($email){
        $r = DB::read("SELECT * FROM `bounced_emails` WHERE `email` = ':email'", $email);
        return $r->rows > 0;
    }
    
    /**
     * Send all pending email now
     * @return 
     */
    static function sendDelayedEmails(){
        $countRes = DB::read('SELECT count(*) as `cnt` FROM `pending_emails` WHERE `created_at` <= NOW()');
        $chunk = 100;               // Split emails into chunks in order to protect server from overload
        $currentChunk = 0;           // Set initial chunk
        
        while($currentChunk < $countRes->first['cnt']){
            $currentChunk += $chunk;
            $res = DB::read('SELECT * FROM `pending_emails` LIMIT #currentChunk #chunk', $currentChunk, $chunk);
            foreach($res->result as $line){
                if($settings = json_decode($line['settings'], true)){
                    Utils::sendEmail($settings);
                }
            }
        }
    }
    
    /**
     * Send E-mail With Send Grid using the same options
     * @see UtilsEmails::sendEmail()
     * @param object $settings
     * @return 
     */
    static function sendGrid($settings){
        
        # If there is no one to send this email then continue to next one
        if (empty($settings['to'])) { return; } 
        
        # Send grid configuration
        $send = array(
            "api_user"=>Configs::SENDGRID_APIUSER,
            "api_key"=>Configs::SENDGRID_APIKEY
        );
        
        $html = true;
        # Check if this is an HTML E-mail or not
        if (isset($settings['html'])) {
            if ($settings['html'] == "1" || $settings['html'] == true) {
                $html = true;
            }else {
                $html = false;
            }
        }
        # Default sender is set here.
        $noReply = array(NOREPLY, NOREPLY_NAME);
        
        # If default is selected for sender name, then remove it
        if(isset($settings['from'][1]) && ($settings['from'][1] == "none" || $settings['from'][1] == "default")){
            unset($settings['from'][1]);
        }
        
        # If no
        if(empty($settings['from']) || $settings['from'] == "none" || $settings['from'] == "default"){
            $settings['from'] = $noReply;
        }
        
        # Set from address.
        if (is_array($settings['from'])) {
            if (count($settings['from']) > 1) {
                # Add both address, and the name (in that order).
                if (!Utils::checkEmail($settings['from'][0])) {
                    # If it is not a valid e-mail address
                    $send['from'] = $noReply[0];
                    $send['fromname'] = $settings['from'][1];
                } else {
                    $send['from'] = $settings['from'][0];
                    $send['fromname'] = $settings['from'][1];
                }
            } else if (count($settings['from']) == 1) {
                # Add the address only.
                $send['from'] = $settings['from'][0];
            } else {
                # There's no from address setting, use default, which is noreply.
                $send['from'] = $noReply[0];
                $send['fromname'] = $noReply[1];
            }
            # Also add Sender if a custom From address is used, ie. from the form etc.
            # $mail->Sender = $noReply[0];
        } else {
            # If the only thing sent is an e-mail
            if(Utils::checkEmail($settings['from'])){
                $send['from'] = $settings['from'];
            }else{ # If not a valid email address then use noReply instead
                $send['from'] = $noReply[0];
                $send['fromname'] = $settings['from'];
            }
        }
        
        # Split email addresses into tokens, check their validation then return the one can be used in the email
        $toAddresses = Utils::splitEmails($settings['to']);
        
        if(count($toAddresses) < 1){
            # No address found don't send this email
            return; 
        }else if(count($toAddresses) == 1){
            # If there is only one address add it as one
            $send['to'] = $toAddresses[0];
        }else{
            # If multiple to addresses sent, then add them oneby one
            $send['to'] = $toAddresses;
        }
        
        $send['subject'] = $settings['subject'];
        if($html){
            $send['html'] = $settings['body'];
            # $send['text'] = Utils::stripHTML($settings['body']); // Make sure you add alternative text version
        }else{
            $send['text'] = Utils::stripHTML($settings['body']);
        }
        $r = Utils::postRequest("https://sendgrid.com/api/mail.send.json", $send);
        $response = json_decode($r);
        if(isset($response->error)){
            throw new SystemException($response->error);
        }
        return $response;
    }
    
    /**
     * Wrapper around sending e-mails, using the PHPMailer library.
     * @param array $settings
     * $settings is a hash which includes:
     *      a "from" array for the address and the name of the sender
     *      a "to" array of e-mail addresses
     *      a "subject" string
     *      a "message" string
     *  and sends the e-mail.
     *  
     * @TODO: Add new options to the settings hash as there is a need.
     *  For example, add attachement support.
     * @return throws an exception when an error happens. Returns nothing when successful. 
     */
    static function sendEmail($settings, $forceSending = false) {
        
        if(DELAY_EMAILS && !$forceSending){
            DB::insert('pending_emails', array(
                "email_config" => json_encode($settings)
            ));
            return;
        }
        
        # Force JotForm to use sendgrid for every email
        if(Configs::USE_SENDGRID){
            return self::sendGrid($settings);
        }
        
        # If there is no one to send this email then continue to next one
        if (empty($settings['to'])) { return; } 
        
        $mail = new PHPMailerLite(true);
        $mail->CharSet = "utf-8";       # Set charset as UTF-8
        $mail->WordWrap = 80;           # Otherwise Phantom exclamation mark appears
        
        if(isset($settings['customHeader'])){
            $mail->AddCustomHeader($settings['customHeader']);
        }
        
        $html = true;
        # Check if this is an HTML E-mail or not
        if (isset($settings['html'])) {
            if ($settings['html'] == "1" || $settings['html'] == true) {
                $html = true;
            }else {
                $html = false;
            }
        }
        
        $mail->IsHTML($html);
        # Default sender is set here.
        $noReply = array(NOREPLY, NOREPLY_NAME);
        
        # If default is selected for sender name, then remove it
        if(isset($settings['from'][1]) && ($settings['from'][1] == "none" || $settings['from'][1] == "default")){
            unset($settings['from'][1]);
        }
        
        # If no
        if(empty($settings['from']) || $settings['from'] == "none" || $settings['from'] == "default"){
            $settings['from'] = $noReply;
        }
        
        # Set from address.
        if (is_array($settings['from'])) {
            if (count($settings['from']) > 1) {
                
                # Add both address, and the name (in that order).
                if (!Utils::checkEmail($settings['from'][0])) {
                    # If it is not a valid e-mail address
                    $mail->SetFrom($noReply[0], $settings['from'][1]);
                } else {
                    $mail->SetFrom($settings['from'][0], $settings['from'][1]);
                }
                
            } else if (count($settings['from']) == 1) {
                # Add the address only.
                $mail->SetFrom($settings['from'][0]);
            } else {
                # There's no from address setting, use default, which is noreply.
                $mail->SetFrom($noReply[0], $noReply[1]);
            }
            # Also add Sender if a custom From address is used, ie. from the form etc.
            $mail->Sender = $noReply[0];
        } else {
            # If the only thing sent is an e-mail
            if(Utils::checkEmail($settings['from'])){
                $mail->SetFrom($settings['from']);
            }else{ # If not a valid email address then use noReply instead
                $mail->SetFrom($noReply[0], $settings['from']);
            }
        }
        
        # Split email addresses into tokens, check their validation then return the one can be used in the email
        $toAddresses = Utils::splitEmails($settings['to']);
        
        if(count($toAddresses) < 1){
            # No address found don't send this email
            return; 
        }
        
        # If multiple to addresses sent then add them oneby one
        foreach($toAddresses as $toAddress) {
            $mail->AddAddress($toAddress);
        }
        
        $mail->Subject = $settings['subject'];
        if($html){
            $mail->Body = $settings['body'];
        }else{
            # If it's not HTML email make sure there is no HTML code in it
            # $mail->Body = str_replace("<br>", "\n", $settings['body']);
            $mail->Body = Utils::stripHTML($settings['body'], true); 
        }
        if($html){
            # $mail->AltBody = Utils::stripHTML($settings['body']);
        }
        if(!$mail->Send()) {
            throw new Warning($mail->ErrorInfo);
        }
    }
    /**
     * Sends mail
     * @return 
     * @param $to Object address of receiver. one mail or mails seperated by comma
     * @param $subject Object Subject of the mail
     * @param $contents Object Contents of the mail
     * @param $is_html Object[optional] define if the mailcontent is HTML or not.
     * @param $frm Object[optional] From address.
     * @param $cc Object[optional] CC address
     * @param $sendanyway Object[optional] If true. Sends email also blocked users. and Doesn't add block message
     */
    static function sendOldMail($to, $subject, $contents, $is_html = true, $frm = false, $cc = "", $sendanyway = true, $customHeader = ""){
        
        if(Configs::USE_SENDGRID){
            preg_match("/(.*?)\<(.*?)\>/", $frm, $m);
            if(count($m) > 0){
                $frm = array($m[2], $m[1]);
            }
            return Utils::sendGrid(array(
                "from"    => $frm,
                "to"      => $to,
                "subject" => $subject,
                "body"    => $contents,
                "html"    => $is_html
            ));
        }
        
        $from = ($frm)? $frm : NOREPLY_NAME."<".NOREPLY.">";
        $from_header = "From: $from\r\nReturn-Path: $from\r\n";
        if($cc){
            $from_header .= "Cc: ".join(", ", Utils::splitEmails($cc))."\r\n";
        }
        
        if($customHeader){
            $from_header .= $customHeader."\r\n";
        }
        
        if($is_html) $from_header .="Content-Type: text/html; charset=\"UTF-8\"\r\n";
        $to = str_replace(" ", "", $to);
        $to = preg_replace("/\n\r|\n|\r\n|\r/", "\n", $to);
        
        $subject = preg_replace('/([^a-z ])/ie', 'sprintf("=%02x",ord(StripSlashes("\\1")))', $subject); 
        $subject = str_replace(' ', '_', $subject); 
        $subject = "=?UTF-8?Q?$subject?=";
    
        $mails = Utils::splitEmails($to);
        foreach($mails as $to){
            $o = @mail($to, $subject, stripslashes($contents), $from_header); // removed block message  
        }
    }
    /**
     * Checks the mail logs for email sent status
     * @param object $email
     * @return 
     */
    static function checkEmailStatus($email){
        
        # Get the last two lines of the log grep for this e-mail addres
        $grep = Utils::findCommand("grep");
        exec($grep.' '. escapeshellarg($email) .' /var/log/mail.log', $res);
        $log = array_slice($res, -2, 2);
        
        if(count($log) < 2){
            
        }
        
        # Check email sent status        
        if(strpos($log[1], "stat=Sent") !== false){
            # Check if the last line is recipient response
            if(strpos($log[1], "sm-mta") !== false){
               return "E-mail successfully sent";
            }else{
               return "E-mail appears to be sent";
            }
        }else{
             return "E-mail cannot be sent";
        }
    }
    
    /**
     * Validates the email
     * @param object $email
     * @return 
     */
    static function checkEmail($email, $checkDB = false){
        
        // Check to see if this email was in bounced list
        if($checkDB && self::checkEmailInBouncedList($email)){ return false; }
        
        // 22 TLDs as of Sep 2009. From http://en.wikipedia.org/wiki/Tld
        // eregi DEPRECATED as of PHP 5.3.0 and REMOVED as of PHP 6.0.0.
        return preg_match('/^[a-z0-9_\-\+]+(\.[_a-z0-9\-\+]+)*@([_a-z0-9\-]+\.)+([a-z]{2}|aero|asia|arpa|biz|cat|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|nato|net|org|pro|tel|travel)$/i', $email);
    }
    
}