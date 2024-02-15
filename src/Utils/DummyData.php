<?
/**
 * Generates a dummy submission data for given form
 * @package JotForm
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;
 
class DummyData{
    
    private $form, $questions, $lorem;
    
    /**
     * Gathers all information for Generation dummydata
     * @constructor 
     * @param object $formID
     * @return 
     */
    public function DummyData($formID){
        $this->form = new Form($formID);
        $this->questions = $this->form->getQuestions();
        $this->lorem = new LoremIpsumGenerator();
    }
    
    public function generate(){
        
        $answers = array();
        Utils::print_r($this->questions);
        foreach($this->questions as $id => $question){
            
            switch($question['type']){
                case "control_textbox": 
                case "control_passwordbox":               
                case "control_hidden":
                case "control_autoincrement":
                    $answer[$id] = $this->lorem->getContent(2);
                break;
                case "control_textarea":
                    $answer[$id] = $this->lorem->getContent(30);
                break;
                case "control_dropdown":
                case "control_radio":
                case "control_checkbox":
                case "control_autocomp": break;
                    // Select random
                break;
                case "control_birthdate":
                case "control_datetime":
                    // Current Date
                break;
                case "control_fileupload":
                    // Put an image link
                break;
                                
                case "control_rating":
                case "control_scale":
                    // Random number within range
                break;
                case "control_range": 
                    // Random number within start and end range
                break;
                case "control_grading": break;
                case "control_slider": break;
                
                case "control_number":
                case "control_spinner":
                    // Random number
                break;
                case "control_matrix":
                    // Hard to generate
                break;
                
                case "control_fullname":
                    
                break;
                case "control_email":
                    // generate email address
                break;
                case "control_address":
                    // generate address
                break;
                
                case "control_phone":
                    // phone number
                break;
                
                case "control_location":
                    // put country city state here
                break;
                case "control_payment":
                case "control_paypal":
                case "control_paypalpro":
                case "control_authnet":
                case "control_googleco":
                case "control_2co":
                case "control_clickbank":
                case "control_worldpay":
                case "control_onebip":
                	// Select one or two products
                    
                    
                case "control_text":
                case "control_head":
                case "control_captcha":
                case "control_image":
                case "control_collapse":
                case "control_pagebreak":
                case "control_button":
                	continue; // No data for these fields
                default:
                	$answer[$id] = $this->lorem->getContent(20);
            }
        }
        
        return $answer;
    }
}

$dummy = new DummyData("1020392731");
Utils::print_r($dummy->generate());
