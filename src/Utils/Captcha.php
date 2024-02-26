<?php
/**
 * Handles captchas
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;

use Legacy\Jot\ID;

class Captcha
{
    /**
     * Makes encoded word, works only one hour
     * @var //string
     */
    private static $salt = 'o-z-H';
    /**
     * Array of words which will be used in captchas
     * @var //array
     */
    public static $words = array("polish", "past", "part", "when", "much", "seed", "soap", "glove", "sticky", "soap", "profit", "bent", "collar",
        "where", "weight", "again", "weight", "boat", "small", "profit", "sound", "chin", "flag", "body", "salt", "birth",
        "crime", "false", "sleep", "square", "canvas", "mine", "safe", "mark", "degree", "bell", "color", "expert", "rule",
        "parcel", "degree", "waste", "after", "army", "moon", "brain", "news", "silver", "rain", "stiff", "horse", "smile",
        "shirt", "this", "grip", "sharp", "knot", "neck", "woman", "smell", "round", "linen", "same", "right", "adjust",
        "jewel", "bell", "pocket", "green", "mother", "mine", "rice", "loss", "tail", "foot", "porter", "spring", "desire",
        "screw", "spade", "bent", "letter", "glass", "sugar", "fear", "every", "muscle", "right", "rate", "butter", "sail",
        "summer", "snake", "wheel", "sheep", "glove", "poison", "tooth", "bucket", "wood", "great", "school", "sudden",
        "wind", "step", "credit", "pain", "design", "front", "push", "seem", "cord", "sound", "scale", "with", "wind",
        "cloth", "screw", "garden", "west", "judge", "goat", "animal", "warm", "join", "turn", "school", "white", "keep",
        "basin", "tooth", "face", "range", "tight", "nail", "seem", "female", "public", "potato", "idea", "snake", "flower",
        "narrow", "still", "hope", "glass", "lock", "hand", "face", "fear", "copper", "debt", "shoe", "paint", "butter",
        "roll", "blood", "story", "doubt", "meat", "offer", "clean", "memory", "like", "wrong", "jump", "amount", "regret",
        "free", "crush", "pull", "dress", "door", "male", "black", "please", "flag", "fact", "nose", "taste", "snake", "cold",
        "attack", "crush", "canvas", "shame", "book", "wound", "nation", "fire", "good");

    /**
     * Checks if the given word is correct or not
     * @param  $word
     * @param  $id
     * @return
     */
    static function checkWord($word, $id)
    {
        $id = self::decode($id);
        return strtolower(self::$words[$id - 1]) == strtolower($word);
    }

    /**
     * Gets a random image
     * @return
     */
    static function getRandom()
    {
        srand(ID::makeSeed());
        $num = rand(1, 190);
        return $num;
    }

    /**
     * encodes the captcha URL
     * @param  $num
     * @return
     */
    public static function encode($num)
    {
        return md5(Captcha . phpdate(self::$salt) . $num);
    }

    /**
     * decodes the captcha image url
     * @param  $encoded
     * @return
     */
    public static function decode($encoded)
    {
        for ($i = 0; $i < count(self::$words); $i++) {
            if ($encoded === md5(Captcha . phpdate(self::$salt) . $i)) {
                return $i;
            }
        }
        return false;
    }

    /**
     * Prints the given image on the screen
     * @param  $code
     * @return
     */
    public static function serveImg($code)
    {
        $num = self::decode($code);
        header('Content-Type:image/png');
        $file = ROOT . "cimg/" . $num . ".png";
        if (file_exists($file)) {
            echo join('', file($file));
            exit;
        }

        Utils::show404($code);
    }

    /**
     * Prints the captcha correction page
     * @param  $sid
     * @return
     */
    public static function printCaptchaPage($sid)
    {
        ob_clean();
        Utils::stopPostBack();
        include ROOT . "opt/captcha.html";
        exit;
    }

    /**
     * Checks the recaptcha parameters
     * @param  $challenge
     * @param  $response
     * @return
     */
    public static function checkReCaptcha($challenge, $response)
    {

        define('API_PUBLIC', '6Ld9UAgAAAAAAMon8zjt30tEZiGQZ4IIuWXLt1ky');
        define('API_PRIVATE', '6Ld9UAgAAAAAAMpq26ktw51roN5pmRw6wCrO3lKh ');
        require_once(ROOT . '/opt/recaptchalib.php');
        $privatekey = API_PRIVATE;

        $ip = $_SERVER["REMOTE_ADDR"];
        $resp = recaptcha_check_answer($privatekey, $ip, $challenge, $response);
        if (!$resp->is_valid) {
            return false;
        }
        return true;
    }

}
