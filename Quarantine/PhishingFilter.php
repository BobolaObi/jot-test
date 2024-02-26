<?php

namespace Quarantine;;

use CONSTANT;
use Legacy\Jot\Form;
use Legacy\Jot\UserManagement\User;
use Legacy\Jot\Utils\DB;
use Legacy\Jot\Utils\Utils;
use unknown_type;


/**
 * @author Seyhun
 */
class PhishingFilter
{

    /**
     * This is the minimum accepted value
     * for a suspicious spam word
      * @var //CONSTANT integer
     */
    const MIN_ACCEPTED_LENGTH = 4;

    /**
      * @var //CONSTANT integer
     */
    const BACKGROUND_STRENGTH = 5;

    /**
     * Pr(S) is the probability of any incoming message to be spam can be taken
     * 0.5 accoring to wikipedia.
     */
    const SPAM_PROB = 0.5;

    /**
     * The id of the form we are dealing with.
      * @var //String
     */
    private $form;

    /**
     * The works that are stored for calculating the
     * spam probability
      * @var //string array
     */
    private $words = [];

    /**
     * The total score for the form.
      * @var //float
     */
    private $score = 0;

    /**
     * Those variables are counting the total spam
     * occurance
     * Enter description here ...
      * @var //unknown_type
     */
    private $totalSpam, $totalNotSpam;

    private $threshHold = 0.9;

    private $showLog = false;

    /**
     * Construct the phishing filter for a user.
     * $formId is the id of the form.
     * @param String $formId
     */
    public function __construct($formId)
    {
        # Get the form.
        $this->form = new Form($formId);
        # Collect the details from form.
        $this->getFormDetailsForSpamFilter();
    }

    public function showLogs()
    {
        $this->showLog = true;
    }

    public function setThreshHold($newThreshHold)
    {
        $this->threshHold = $newThreshHold;
    }

    /**
     * Mark the form as spam
     */
    public function markAsSpam()
    {
        $this->increaseOccurance(1);
        User::suspend($this->form->form['username']);
    }

    /**
     * Mark the form as not spam
     */
    public function markAsNotSpam()
    {
        $this->increaseOccurance(0);
        $this->addFormToWhiteList();
    }

    /**
     * Mark all words in the words array as spam.
     * @param boolean (0 or 1) $isSpam
     */
    private function increaseOccurance($isSpam)
    {
        foreach ($this->words as $word) {
            $res = DB::read("SELECT * FROM `spam_filter` WHERE `word` = ':s' AND `is_spam` = {$isSpam}", $word);
            if ($res->rows == 0) {
                $addQuery = DB::write("INSERT IGNORE INTO spam_filter (word, occurance_count, is_spam) VALUES ( '{$word}', 1, {$isSpam})");
            } else {
                $count = $res->first['occurance_count'] + 1;
                $id = $res->first['id'];
                $updateQuery = DB::write("UPDATE `spam_filter` SET `occurance_count` = '{$count}' WHERE `id` = {$id}");
            }
        }
    }

    /**
     * This function returns the probability of the form
     * being spam.
     * @return float : probability of being spam
     */
    public function getSpamProb()
    {
        # if total word count is 0. Then we know nothing about it.
        if (count($this->words) === 0) return 0.5;

        # Calculate the score for each word.
        $res = DB::read("SELECT SUM(occurance_count) as occurance_sum FROM spam_filter WHERE is_spam = 1");
        $this->totalSpam = isset($res->first) && $res->first["occurance_sum"] ? $res->first["occurance_sum"] : self::SPAM_PROB;

        $res = DB::read("SELECT SUM(occurance_count) as occurance_sum FROM spam_filter WHERE is_spam = 0");
        $this->totalNotSpam = isset($res->first) && $res->first['occurance_sum'] ? $res->first["occurance_sum"] : self::SPAM_PROB;

        # Combine individual probabilities
        # This variable holds P(S|W1).P(S|W2).P(S|W3)
        $multiplicationValue1 = 1;
        # This variable holds (1-P(S|W1)).(1-P(S|W2)).(1-P(S|W2))
        $multiplicationValue2 = 1;

        foreach ($this->words as $word) {
            $singleProbability = $this->wordscore($word);
            $singleProbability = floatval($singleProbability) === floatval(1) ? 0.999999 : $singleProbability;
            $singleProbability = floatval($singleProbability) === floatval(0) ? 0.000001 : $singleProbability;


            $multiplicationValue1 = $multiplicationValue1 * $singleProbability;

            $multiplicationValue2 = $multiplicationValue2 * (1 - $singleProbability);

            if ($this->showLog) {
                Utils::print_r($word . " -> " . $singleProbability);
                Utils::print_r("multip1 -> " . $multiplicationValue1);
                Utils::print_r("multip2 -> " . $multiplicationValue2);
            }
        }
        $prob = $multiplicationValue1 / ($multiplicationValue1 + $multiplicationValue2);
        if ($this->showLog) {
            Utils::print_r("Total prob: " . $prob);
        }
        return $prob;
    }

    /**
     * Adds form to whitelist
     * @return false on error
     */
    public function addFormToWhiteList()
    {
        $res = DB::write("REPLACE INTO `whitelist` (`form_id`) VALUES (':s')", $this->form->id);
        return $res;
    }

    /**
     * This function returns if the form is white listed or not.
     * @return form is white listed or not
     */
    public function isWhiteListed()
    {
        $res = DB::read("SELECT * FROM `whitelist` WHERE `form_id` = ':s'", $this->form->id);
        return $res->rows > 0;
    }

    public function setSpamProb($spamProb = false)
    {
        if ($spamProb === false) {
            $spamProb = $this->getSpamProb();
        }
        $res = DB::write("REPLACE INTO spam_prob (form_id, spam_prob, suspended) VALUES ({$this->form->id} , {$spamProb} , false)");
        return $spamProb;
    }

    /**
     * Score for one word.
     * @param $word
     */
    private function wordscore($word)
    {
        # Get the scores from database.
        $res = DB::read("SELECT * FROM spam_filter WHERE word=':s' AND is_spam = 1", $word);
        $wordSpamCount = isset($res->first) ? $res->first["occurance_count"] : 0;

        $res = DB::read("SELECT * FROM spam_filter WHERE word=':s' AND is_spam = 0", $word);
        $wordNotSpamCount = isset($res->first) ? $res->first["occurance_count"] : 0;

        $total = $wordSpamCount + $wordNotSpamCount;

        if ($total == 0) return self::SPAM_PROB;

        $wordSpamValue = ($wordSpamCount / $this->totalSpam) / (($wordSpamCount / $this->totalSpam) + ($wordNotSpamCount / $this->totalNotSpam));

        if ($total < self::BACKGROUND_STRENGTH) {
            # P'(S|W) = s.P(S) + n.P(S|W) / s+n
            $prob = ((self::BACKGROUND_STRENGTH * self::SPAM_PROB) + ($total * $wordSpamValue)) / (self::BACKGROUND_STRENGTH + $total);
            return $prob;
        } else {
            return $wordSpamValue;
        }
    }

    /**
     * Include phishing indicators here to the words array.
     */
    private function getFormDetailsForSpamFilter()
    {
        # Get the title of the form.
        $this->addText($this->form->form['title']);
        # Get the text of the questions.
        $this->getQuestionTexts();
    }

    /**
     * Get question texts from the question_properties table.
     */
    private function getQuestionTexts()
    {
        foreach ($this->form->getQuestionTexts() as $text) {
            $this->addText($text);
        }
    }

    /**
     * Split and add the text to the words array.
     * @param String $text
     */
    private function addText($text)
    {
        # normalize and split the words.
        # Also strip tags from the questions.
        $text = strip_tags(trim('' . $text));
        if (preg_match_all('/([a-zA-Z][^a-zA-Z]+){4,}/', $text, $arr, PREG_PATTERN_ORDER) > 0) {
            $text = preg_replace('/[^a-zA-Z]+/', '', $text);
        }
        if ($text) {
            $tempWordsArray = preg_split("/\s+/", $text);
            foreach ($tempWordsArray as $tempWord) {
                $this->addToWordsArray($this->normalize($tempWord));
            }
        }
    }

    /**
     * Filter the word and add to the words array.
     * @param $word
     */
    private function addToWordsArray($word)
    {
        $ignoreWords = ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'name', 'undefined', 'personal', 'his', 'her', 'we', 'you', 'and', 'untitled', 'form', 'e', 'i', 'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how', 'in', 'is', 'it', 'la', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what', 'when', 'where', 'who', 'will', 'with', 'und', 'the', 'www', 'click', 'edit', 'submit'];
        # return if the word is in ignore array and if its added before
        if (in_array($word, $ignoreWords) || in_array($word, $this->words)) return;
        # return if word is small
        if (strlen($word) < self::MIN_ACCEPTED_LENGTH) return;
        # if there is no problem then add the word to the array.
        $this->words[] = $word;
    }

    /**
     * Normalize the text
     * @param String $text
     */
    private function normalize($text)
    {
        # make all string lower case for string comparison
        $text = strtolower($text);
        # Force letters to english (for example: ö => o, Ü => U)
        $text = Utils::fixUTF($text);
        # Remove all non word letters from string
        $text = preg_replace("/\W+/", "", $text);

        return $text;
    }
}
