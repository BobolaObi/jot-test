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
     * @return // array Countries
     */
    static function getCountries()
    {
        $response = DB::read("SELECT * FROM countries WHERE 1");
        $countries = [];
        foreach ($response->result as $line) {
            $countries[] = ["id" => $line[id], "state" => $line['state']];
        }
        return $countries;
    }

    /**
     * Get states by country
     * @param  $countryID // ID of the country
     * @return // array States by country
     */
    static function getStates($countryID)
    {
        $response = DB::read("SELECT * FROM states WHERE country_id =#countryID", $countryID);
        $states = [];
        foreach ($response->result as $line) {
            $states[] = ["id" => $line[id], "state" => $line['state']];
        }
        return $states;
    }

    /**
     * Get cities by state
     * @param  $stateID // ID of the state
     * @return // array cities by state
     */
    static function getCities($stateID)
    {
        $response = DB::read("SELECT * FROM cities WHERE state_id=#stateID", $stateID);
        $cities = [];
        foreach ($response->result as $line) {
            $cities[] = [$line['city']];
        }
        return $cities;
    }

    /**
     * Will save freauqenty added country
     * @param  $country
     * @return // null
     * @todo complete this function
     */
    static function saveNewCountry($country)
    {

    }

    /**
     *
     * Will save freauqenty added states
     * @param  $countryID
     * @param  $state
     * @return // null
     * @todo complete this function
     */
    static function saveNewState($countryID, $state)
    {

    }

    /**
     * Will save freauqenty added cities
     * @param  $countryID
     * @param  $stateID
     * @param  $city
     * @return
     * @todo complete this function
     */
    static function saveNewCity($countryID, $stateID, $city)
    {

    }
}

?>