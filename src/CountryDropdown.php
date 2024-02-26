<?php
/**
 * Gets the country information from database
 * @package JotForm
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot;

use Legacy\Jot\Utils\DB;

class CountryDropdown
{
    /**
     * No need but it's good to have
     * @return \\ array Countries
     */
    static function getCountries(){
        $response = DB::read("SELECT * FROM countries WHERE 1");
        $countries = array();
        foreach($response->result as $line){
            array_push($countries, array("id"=>$line[id], "state"=>$line['state']));
        }
        return $countries;
    }

    /**
     * Get states by country
     * @param  $countryID  // ID of the country
     * @return \\ array States by country
     */
    static function getStates($countryID){
        $response = DB::read("SELECT * FROM states WHERE country_id =#countryID", $countryID);
        $states = array();
        foreach($response->result as $line){
            array_push($states, array("id"=>$line[id], "state"=>$line['state']));
        }
        return $states;
    }

    /**
     * Get cities by state
     * @param  $stateID  // ID of the state
     * @return \\ array cities by state
     */
    static function getCities($stateID){
        $response = DB::read("SELECT * FROM cities WHERE state_id=#stateID", $stateID);
        $cities = array();
        foreach($response->result as $line){
            array_push($cities, array($line['city']));
        }
        return $cities;
    }

    /**
     * Will save freauqenty added country
     * @todo complete this function
     * @param  $country
     * @return \\ null
     */
    static function saveNewCountry($country){

    }
    /**
     *
     * Will save freauqenty added states
     * @todo complete this function
     * @param  $countryID
     * @param  $state
     * @return \\ null
     */
    static function saveNewState($countryID, $state){

    }
    /**
     * Will save freauqenty added cities
     * @todo complete this function
     * @param  $countryID
     * @param  $stateID
     * @param  $city
     * @return
     */
    static function saveNewCity($countryID, $stateID, $city){
    
    }
}

?>