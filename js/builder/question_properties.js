var lastHundredYears = [];
(function(){
    var date = new Date();
    // get current year
    cyear = (date.getYear() < 1000) ? date.getYear() + 1900 : date.getYear();
    var years = [];
    for(var year = cyear; year >= (cyear - 100); year--){
        years.push(year+"");
    }
    
    lastHundredYears = years;
})();

/**
 * Bulk options for dropdowns, radios, and checkboxes
 */
var special_options = {
    "None":{
        controls:"dropdown,radio,checkbox,matrix"
    },
    "US States":{
        controls:"dropdown",
        value:["Alabama", "Alaska", "Arizona", "Arkansas", "California", "Colorado", "Connecticut", "District of Columbia", "Delaware", "Florida", "Georgia", "Hawaii", "Idaho", "Illinois", "Indiana", "Iowa", "Kansas", "Kentucky", "Louisiana", "Maine", "Maryland", "Massachusetts", "Michigan", "Minnesota", "Mississippi", "Missouri", "Montana", "Nebraska", "Nevada", "New Hampshire", "New Jersey", "New Mexico", "New York", "North Carolina", "North Dakota", "Ohio", "Oklahoma", "Oregon", "Pennsylvania", "Rhode Island", "South Carolina", "South Dakota", "Tennessee", "Texas", "Utah", "Vermont", "Virginia", "Washington", "West Virginia", "Wisconsin", "Wyoming", "Alberta", "British Columbia", "Manitoba", "New Brunswick", "Newfoundland", "Northwest Territories", "Nova Scotia", "Nunavut", "Ontario", "Prince Edward Island", "Quebec", "Saskatchewan", "Yukon", "Alabama", "Alaska", "Arizona", "Arkansas", "California", "Colorado", "Connecticut", "District of Columbia", "Delaware", "Florida", "Georgia", "Hawaii", "Idaho", "Illinois", "Indiana", "Iowa", "Kansas", "Kentucky", "Louisiana", "Maine", "Maryland", "Massachusetts", "Michigan", "Minnesota", "Mississippi", "Missouri", "Montana", "Nebraska", "Nevada", "New Hampshire", "New Jersey", "New Mexico", "New York", "North Carolina", "North Dakota", "Ohio", "Oklahoma", "Oregon", "Pennsylvania", "Rhode Island", "South Carolina", "South Dakota", "Tennessee", "Texas", "Utah", "Vermont", "Virginia", "Washington", "West Virginia", "Wisconsin", "Wyoming", "Alberta", "British Columbia", "Manitoba", "New Brunswick", "Newfoundland", "Northwest Territories", "Nova Scotia", "Nunavut", "Ontario", "Prince Edward Island", "Quebec", "Saskatchewan", "Yukon"]
    },
    "US States Abbr":{
        controls:"dropdown",
        value:["AL", "AK", "AR", "AZ", "CA", "CO", "CT", "DC", "DE", "FL", "GA", "HI", "ID", "IL", "IN", "IA", "KS", "KY", "LA", "ME", "MD", "MA", "MI", "MN", "MS", "MO", "MT", "NE", "NV", "NH", "NJ", "NM", "NY", "NC", "ND", "OH", "OK", "OR", "PA", "RI", "SC", "SD", "TN", "TX", "UT", "VT", "VA", "WA", "WV", "WI", "WY"]
    },
    "Countries":{
        controls:"dropdown",
        value:["United States", "Abkhazia", "Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "The Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "People's Republic of China", "Republic of China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands", "Faroe Islands", "Fiji", "Finland", "France", "French Polynesia", "Gabon", "The Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guam", "Guatemala", "Guernsey", "Guinea", "Guinea-Bissau", "Guyana Guyana", "Haiti Haiti", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jersey", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "North Korea", "South Korea", "Kosovo", "Kuwait", "Kyrgyzstan", "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia", "Moldova", "Monaco", "Mongolia", "Montenegro", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Nagorno-Karabakh", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Turkish Republic of Northern Cyprus", "Northern Mariana", "Norway", "Pakistan", "Palau", "Palestine", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn Islands", "Poland", "Portugal", "Transnistria Pridnestrovie", "Puerto Rico", "Qatar", "Romania", "Russia", "Rwanda", "Saint Barthelemy", "Saint Helena", "Saint Kitts and Nevis", "Saint Lucia", "Saint Martin", "Saint Pierre and Miquelon", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Serbia", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "Somaliland", "South Africa", "South Ossetia", "Spain", "Sri Lanka", "Sudan", "Suriname", "Svalbard", "Swaziland", "Sweden", "Switzerland", "Syria", "Taiwan", "Tajikistan", "Tanzania", "Thailand", "Timor-Leste", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tristan da Cunha", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "Uruguay", "Uzbekistan", "Vanuatu", "Vatican City", "Venezuela", "Vietnam", "British Virgin Islands", "US Virgin Islands", "Wallis and Futuna", "Western Sahara", "Yemen", "Zambia", "Zimbabwe"]
    },
    "Last 100 Years":{
        controls:'dropdown',
        value: lastHundredYears
    },
    "Gender":{
        controls:"dropdown,radio,checkbox",
        value:["Male".locale(), "Female".locale(), "N/A".locale()]
    },
    "Days":{
        controls:"dropdown,radio,checkbox",
        value:["Monday".locale(),"Tuesday".locale(),"Wednesday".locale(),"Thursday".locale(),"Friday".locale(),"Saturday".locale(),"Sunday".locale()]
    },
    "Months":{
        controls:"dropdown,radio,checkbox",
        value:["January".locale(), "February".locale(), "March".locale(), "April".locale(), "May".locale(), "June".locale(), "July".locale(), "August".locale(), "September".locale(), "October".locale(), "November".locale(), "December".locale()],
        nonLocale:["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"] 
    },
    "Time Zones":{
        controls:"dropdown",
        value:["[[Africa]]", "Abidjan (GMT)", "Accra (GMT)", "Addis Ababa (GMT+03:00)", "Algiers (GMT+01:00)", "Asmara (GMT+03:00)", "Bamako (GMT)", "Bangui (GMT+01:00)", "Banjul (GMT)", "Bissau (GMT)", "Blantyre (GMT+02:00)", "Brazzaville (GMT+01:00)", "Bujumbura (GMT+02:00)", "Cairo (GMT+03:00)", "Casablanca (GMT)", "Ceuta (GMT+02:00)", "Conakry (GMT)", "Dakar (GMT)", "Dar es Salaam (GMT+03:00)", "Djibouti (GMT+03:00)", "Douala (GMT+01:00)", "El Aaiun (GMT)", "Freetown (GMT)", "Gaborone (GMT+02:00)", "Harare (GMT+02:00)", "Johannesburg (GMT+02:00)", "Kampala (GMT+03:00)", "Khartoum (GMT+03:00)", "Kigali (GMT+02:00)", "Kinshasa (GMT+01:00)", "Lagos (GMT+01:00)", "Libreville (GMT+01:00)", "Lome (GMT)", "Luanda (GMT+01:00)", "Lubumbashi (GMT+02:00)", "Lusaka (GMT+02:00)", "Malabo (GMT+01:00)", "Maputo (GMT+02:00)", "Maseru (GMT+02:00)", "Mbabane (GMT+02:00)", "Mogadishu (GMT+03:00)", "Monrovia (GMT)", "Nairobi (GMT+03:00)", "Ndjamena (GMT+01:00)", "Niamey (GMT+01:00)", "Nouakchott (GMT)", "Ouagadougou (GMT)", "Porto-Novo (GMT+01:00)", "Sao Tome (GMT)", "Tripoli (GMT+02:00)", "Tunis (GMT+02:00)", "Windhoek (GMT+01:00)", "[[America]]", "Adak (GMT-09:00)", "Anchorage (GMT-08:00)", "Anguilla (GMT-04:00)", "Antigua (GMT-04:00)", "Araguaina (GMT-03:00)", "Buenos Aires, Argentina (GMT-03:00)", "Catamarca, Argentina (GMT-03:00)", "Cordoba, Argentina (GMT-03:00)", "Jujuy, Argentina (GMT-03:00)", "La Rioja, Argentina (GMT-03:00)", "Mendoza, Argentina (GMT-03:00)", "Rio Gallegos, Argentina (GMT-03:00)", "Salta, Argentina (GMT-03:00)", "San Juan, Argentina (GMT-03:00)", "San Luis, Argentina (GMT-04:00)", "Tucuman, Argentina (GMT-03:00)", "Ushuaia, Argentina (GMT-03:00)", "Aruba (GMT-04:00)", "Asuncion (GMT-04:00)", "Atikokan (GMT-05:00)", "Bahia (GMT-03:00)", "Barbados (GMT-04:00)", "Belem (GMT-03:00)", "Belize (GMT-06:00)", "Blanc-Sablon (GMT-04:00)", "Boa Vista (GMT-04:00)", "Bogota (GMT-05:00)", "Boise (GMT-06:00)", "Cambridge Bay (GMT-06:00)", "Campo Grande (GMT-04:00)", "Cancun (GMT-05:00)", "Caracas (GMT-04:30)", "Cayenne (GMT-03:00)", "Cayman (GMT-05:00)", "Chicago (GMT-05:00)", "Chihuahua (GMT-06:00)", "Costa Rica (GMT-06:00)", "Cuiaba (GMT-04:00)", "Curacao (GMT-04:00)", "Danmarkshavn (GMT)", "Dawson (GMT-07:00)", "Dawson Creek (GMT-07:00)", "Denver (GMT-06:00)", "Detroit (GMT-04:00)", "Dominica (GMT-04:00)", "Edmonton (GMT-06:00)", "Eirunepe (GMT-04:00)", "El Salvador (GMT-06:00)", "Fortaleza (GMT-03:00)", "Glace Bay (GMT-03:00)", "Godthab (GMT-02:00)", "Goose Bay (GMT-03:00)", "Grand Turk (GMT-04:00)", "Grenada (GMT-04:00)", "Guadeloupe (GMT-04:00)", "Guatemala (GMT-06:00)", "Guayaquil (GMT-05:00)", "Guyana (GMT-04:00)", "Halifax (GMT-03:00)", "Havana (GMT-04:00)", "Hermosillo (GMT-07:00)", "Indianapolis, Indiana (GMT-04:00)", "Knox, Indiana (GMT-05:00)", "Marengo, Indiana (GMT-04:00)", "Petersburg, Indiana (GMT-04:00)", "Tell City, Indiana (GMT-05:00)", "Vevay, Indiana (GMT-04:00)", "Vincennes, Indiana (GMT-04:00)", "Winamac, Indiana (GMT-04:00)", "Inuvik (GMT-06:00)", "Iqaluit (GMT-04:00)", "Jamaica (GMT-05:00)", "Juneau (GMT-08:00)", "Louisville, Kentucky (GMT-04:00)", "Monticello, Kentucky (GMT-04:00)", "La Paz (GMT-04:00)", "Lima (GMT-05:00)", "Los Angeles (GMT-07:00)", "Maceio (GMT-03:00)", "Managua (GMT-06:00)", "Manaus (GMT-04:00)", "Marigot (GMT-04:00)", "Martinique (GMT-04:00)", "Mazatlan (GMT-06:00)", "Menominee (GMT-05:00)", "Merida (GMT-05:00)", "Mexico City (GMT-05:00)", "Miquelon (GMT-02:00)", "Moncton (GMT-03:00)", "Monterrey (GMT-05:00)", "Montevideo (GMT-03:00)", "Montreal (GMT-04:00)", "Montserrat (GMT-04:00)", "Nassau (GMT-04:00)", "New York (GMT-04:00)", "Nipigon (GMT-04:00)", "Nome (GMT-08:00)", "Noronha (GMT-02:00)", "Center, North Dakota (GMT-05:00)", "New Salem, North Dakota (GMT-05:00)", "Panama (GMT-05:00)", "Pangnirtung (GMT-04:00)", "Paramaribo (GMT-03:00)", "Phoenix (GMT-07:00)", "Port-au-Prince (GMT-05:00)", "Port of Spain (GMT-04:00)", "Porto Velho (GMT-04:00)", "Puerto Rico (GMT-04:00)", "Rainy River (GMT-05:00)", "Rankin Inlet (GMT-05:00)", "Recife (GMT-03:00)", "Regina (GMT-06:00)", "Resolute (GMT-05:00)", "Rio Branco (GMT-04:00)", "Santarem (GMT-03:00)", "Santiago (GMT-04:00)", "Santo Domingo (GMT-04:00)", "Sao Paulo (GMT-03:00)", "Scoresbysund (GMT)", "Shiprock (GMT-06:00)", "St Barthelemy (GMT-04:00)", "St Johns (GMT-02:30)", "St Kitts (GMT-04:00)", "St Lucia (GMT-04:00)", "St Thomas (GMT-04:00)", "St Vincent (GMT-04:00)", "Swift Current (GMT-06:00)", "Tegucigalpa (GMT-06:00)", "Thule (GMT-03:00)", "Thunder Bay (GMT-04:00)", "Tijuana (GMT-07:00)", "Toronto (GMT-04:00)", "Tortola (GMT-04:00)", "Vancouver (GMT-07:00)", "Whitehorse (GMT-07:00)", "Winnipeg (GMT-05:00)", "Yakutat (GMT-08:00)", "Yellowknife (GMT-06:00)", "[[Antarctica]]", "Casey (GMT+11:00)", "Davis (GMT+05:00)", "DumontDUrville (GMT+10:00)", "Mawson (GMT+05:00)", "McMurdo (GMT+12:00)", "Palmer (GMT-04:00)", "Rothera (GMT-03:00)", "South Pole (GMT+12:00)", "Syowa (GMT+03:00)", "Vostok (GMT+06:00)", "[[Arctic]]", "Longyearbyen (GMT+02:00)", "[[Asia]]", "Aden (GMT+03:00)", "Almaty (GMT+06:00)", "Amman (GMT+03:00)", "Anadyr (GMT+13:00)", "Aqtau (GMT+05:00)", "Aqtobe (GMT+05:00)", "Ashgabat (GMT+05:00)", "Baghdad (GMT+03:00)", "Bahrain (GMT+03:00)", "Baku (GMT+05:00)", "Bangkok (GMT+07:00)", "Beirut (GMT+03:00)", "Bishkek (GMT+06:00)", "Brunei (GMT+08:00)", "Choibalsan (GMT+08:00)", "Chongqing (GMT+08:00)", "Colombo (GMT+05:30)", "Damascus (GMT+03:00)", "Dhaka (GMT+07:00)", "Dili (GMT+09:00)", "Dubai (GMT+04:00)", "Dushanbe (GMT+05:00)", "Gaza (GMT+03:00)", "Harbin (GMT+08:00)", "Ho Chi Minh (GMT+07:00)", "Hong Kong (GMT+08:00)", "Hovd (GMT+07:00)", "Irkutsk (GMT+09:00)", "Jakarta (GMT+07:00)", "Jayapura (GMT+09:00)", "Jerusalem (GMT+03:00)", "Kabul (GMT+04:30)", "Kamchatka (GMT+13:00)", "Karachi (GMT+06:00)", "Kashgar (GMT+08:00)", "Kathmandu (GMT+05:45)", "Kolkata (GMT+05:30)", "Krasnoyarsk (GMT+08:00)", "Kuala Lumpur (GMT+08:00)", "Kuching (GMT+08:00)", "Kuwait (GMT+03:00)", "Macau (GMT+08:00)", "Magadan (GMT+12:00)", "Makassar (GMT+08:00)", "Manila (GMT+08:00)", "Muscat (GMT+04:00)", "Nicosia (GMT+03:00)", "Novokuznetsk (GMT+07:00)", "Novosibirsk (GMT+07:00)", "Omsk (GMT+07:00)", "Oral (GMT+05:00)", "Phnom Penh (GMT+07:00)", "Pontianak (GMT+07:00)", "Pyongyang (GMT+09:00)", "Qatar (GMT+03:00)", "Qyzylorda (GMT+06:00)", "Rangoon (GMT+06:30)", "Riyadh (GMT+03:00)", "Sakhalin (GMT+11:00)", "Samarkand (GMT+05:00)", "Seoul (GMT+09:00)", "Shanghai (GMT+08:00)", "Singapore (GMT+08:00)", "Taipei (GMT+08:00)", "Tashkent (GMT+05:00)", "Tbilisi (GMT+04:00)", "Tehran (GMT+04:30)", "Thimphu (GMT+06:00)", "Tokyo (GMT+09:00)", "Ulaanbaatar (GMT+08:00)", "Urumqi (GMT+08:00)", "Vientiane (GMT+07:00)", "Vladivostok (GMT+11:00)", "Yakutsk (GMT+10:00)", "Yekaterinburg (GMT+06:00)", "Yerevan (GMT+05:00)", "[[Atlantic]]", "Azores (GMT)", "Bermuda (GMT-03:00)", "Canary (GMT+01:00)", "Cape Verde (GMT-01:00)", "Faroe (GMT+01:00)", "Madeira (GMT+01:00)", "Reykjavik (GMT)", "South Georgia (GMT-02:00)", "St Helena (GMT)", "Stanley (GMT-04:00)", "[[Australia]]", "Adelaide (GMT+09:30)", "Brisbane (GMT+10:00)", "Broken Hill (GMT+09:30)", "Currie (GMT+10:00)", "Darwin (GMT+09:30)", "Eucla (GMT+08:45)", "Hobart (GMT+10:00)", "Lindeman (GMT+10:00)", "Lord Howe (GMT+10:30)", "Melbourne (GMT+10:00)", "Perth (GMT+08:00)", "Sydney (GMT+10:00)", "[[Europe]]", "Amsterdam (GMT+02:00)", "Andorra (GMT+02:00)", "Athens (GMT+03:00)", "Belgrade (GMT+02:00)", "Berlin (GMT+02:00)", "Bratislava (GMT+02:00)", "Brussels (GMT+02:00)", "Bucharest (GMT+03:00)", "Budapest (GMT+02:00)", "Chisinau (GMT+03:00)", "Copenhagen (GMT+02:00)", "Dublin (GMT+01:00)", "Gibraltar (GMT+02:00)", "Guernsey (GMT+01:00)", "Helsinki (GMT+03:00)", "Isle of Man (GMT+01:00)", "Istanbul (GMT+03:00)", "Jersey (GMT+01:00)", "Kaliningrad (GMT+03:00)", "Kiev (GMT+03:00)", "Lisbon (GMT+01:00)", "Ljubljana (GMT+02:00)", "London (GMT+01:00)", "Luxembourg (GMT+02:00)", "Madrid (GMT+02:00)", "Malta (GMT+02:00)", "Mariehamn (GMT+03:00)", "Minsk (GMT+03:00)", "Monaco (GMT+02:00)", "Moscow (GMT+04:00)", "Oslo (GMT+02:00)", "Paris (GMT+02:00)", "Podgorica (GMT+02:00)", "Prague (GMT+02:00)", "Riga (GMT+03:00)", "Rome (GMT+02:00)", "Samara (GMT+05:00)", "San Marino (GMT+02:00)", "Sarajevo (GMT+02:00)", "Simferopol (GMT+03:00)", "Skopje (GMT+02:00)", "Sofia (GMT+03:00)", "Stockholm (GMT+02:00)", "Tallinn (GMT+03:00)", "Tirane (GMT+02:00)", "Uzhgorod (GMT+03:00)", "Vaduz (GMT+02:00)", "Vatican (GMT+02:00)", "Vienna (GMT+02:00)", "Vilnius (GMT+03:00)", "Volgograd (GMT+04:00)", "Warsaw (GMT+02:00)", "Zagreb (GMT+02:00)", "Zaporozhye (GMT+03:00)", "Zurich (GMT+02:00)", "[[Indian]]", "Antananarivo (GMT+03:00)", "Chagos (GMT+06:00)", "Christmas (GMT+07:00)", "Cocos (GMT+06:30)", "Comoro (GMT+03:00)", "Kerguelen (GMT+05:00)", "Mahe (GMT+04:00)", "Maldives (GMT+05:00)", "Mauritius (GMT+04:00)", "Mayotte (GMT+03:00)", "Reunion (GMT+04:00)", "[[Pacific]]", "Apia (GMT-11:00)", "Auckland (GMT+12:00)", "Chatham (GMT+12:45)", "Easter (GMT-06:00)", "Efate (GMT+11:00)", "Enderbury (GMT+13:00)", "Fakaofo (GMT-10:00)", "Fiji (GMT+12:00)", "Funafuti (GMT+12:00)", "Galapagos (GMT-06:00)", "Gambier (GMT-09:00)", "Guadalcanal (GMT+11:00)", "Guam (GMT+10:00)", "Honolulu (GMT-10:00)", "Johnston (GMT-10:00)", "Kiritimati (GMT+14:00)", "Kosrae (GMT+11:00)", "Kwajalein (GMT+12:00)", "Majuro (GMT+12:00)", "Marquesas (GMT-09:30)", "Midway (GMT-11:00)", "Nauru (GMT+12:00)", "Niue (GMT-11:00)", "Norfolk (GMT+11:30)", "Noumea (GMT+11:00)", "Pago Pago (GMT-11:00)", "Palau (GMT+09:00)", "Pitcairn (GMT-08:00)", "Ponape (GMT+11:00)", "Port Moresby (GMT+10:00)", "Rarotonga (GMT-10:00)", "Saipan (GMT+10:00)", "Tahiti (GMT-10:00)", "Tarawa (GMT+12:00)", "Tongatapu (GMT+13:00)", "Truk (GMT+10:00)", "Wake (GMT+12:00)", "Wallis (GMT+12:00)"]
    },
    "LocationCountries":{
        controls:'location',
        value:["Canada","United States","Afghanistan","Albania","Algeria","American Samoa","Andorra","Angola","Anguilla","Antarctica","Antigua and Barbuda","Argentina","Armenia","Aruba","Australia","Austria","Azerbaijan","Bahamas","Bahrain","Bangladesh","Barbados","Belarus","Belgium","Belize","Benin","Bermuda","Bhutan","Bolivia","Bosnia\/Herzegowina","Botswana","Bouvet Island","Brazil","British Ind. Ocean","Brunei Darussalam","Bulgaria","Burkina Faso","Burundi","Cambodia","Cameroon","Cape Verde","Cayman Islands","Central African Rep.","Chad","Chile","China","Christmas Island","Cocoa (Keeling) Is.","Colombia","Comoros","Congo","Cook Islands","Costa Rica","Cote Divoire","Croatia","Cuba","Cyprus","Czech Republic","Denmark","Djibouti","Dominica","Dominican Republic","East Timor","Ecuador","Egypt","El Salvador","Equatorial Guinea","Eritrea","Estonia","Ethiopia","Falkland Islands","Faroe Islands","Fiji","Finland","France","Gabon","Gambia","Georgia","Germany","Ghana","Gibraltar","Greece","Greenland","Grenada","Guadeloupe","Guam","Guatemala","Guinea","Guinea-Bissau","Guyana","Haiti","Honduras","Hong Kong","Hungary","Iceland","India","Indonesia","Iran","Iraq","Ireland","Israel","Italy","Jamaica","Japan","Jordan","Kazakhstan","Kenya","Kiribati","Korea","Kuwait","Kyrgyzstan","Lao","Latvia","Lebanon","Lesotho","Liberia","Liechtenstein","Lithuania","Luxembourg","Macau","Macedonia","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Marshall Islands","Martinique","Mauritania","Mauritius","Mayotte","Mexico","Micronesia","Moldova","Monaco","Mongolia","Montserrat","Morocco","Mozambique","Myanmar","Namibia","Nauru","Nepal","Netherlands","New Caledonia","New Zealand","Nicaragua","Niger","Nigeria","Niue","Norfolk Island","Norway","Oman","Pakistan","Palau","Panama","Papua New Guinea","Paraguay","Peru","Philippines","Pitcairn","Poland","Portugal","Puerto Rico","Qatar","Reunion","Romania","Russia","Rwanda","Saint Lucia","Samoa","San Marino","Saudi Arabia","Senegal","Seychelles","Sierra Leone","Singapore","Slovakia","Solomon Islands","Somalia","South Africa","Spain","Sri Lanka","St. Helena","Sudan","Suriname","Swaziland","Sweden","Switzerland","Syria","Taiwan","Tajikistan","Tanzania","Thailand","Togo","Tokelau","Tonga","Trinidad and Tobago","Tunisia","Turkey","Turkmenistan","Tuvalu","Uganda","Ukraine","United Arab Emirates","United Kingdom","Uruguay","Uzbekistan","Vanuatu","Vatican","Venezuela","Viet Nam","Virgin Islands","Western Sahara","Yeman","Yugoslavia","Zaire","Zambia"]
    },
    /**
     * Gets the special options for specific element
     * @example special_options.getByType('dropdown') => ['Months', 'Days', 'US States' .... ] 
     * @param {Object} type
     */
    getByType: function(type){
        
        var options = [];
        for(var key in special_options){
            if(special_options[key].controls && special_options[key].controls.indexOf(type) >= 0){
                options.push(key);
            }
        }
        return options;
    }
};

/**
 * Font types to be used in form properties
 */
var fonts = ['Default', 'Arial', 'Arial Black', 'Courier', 'Courier New', 'Comic Sans MS', 'Georgia', 'Gill Sans', 'Helvetica', 'Lucida', 'Lucida Grande', 'Trebuchet MS', 'Tahoma', 'Times New Roman', 'Verdana']; 

/**
 *  Payment field names
 */
var payment_fields = ['control_payment', 'control_paypal', 'control_paypalpro', 'control_clickbank', 'control_2co', 'control_worldpay', 'control_googleco', 'control_onebip', 'control_authnet'];

/**
 * Controls with no input fields
 * These fields cannot be used in reports, emails, or something like that
 */
var not_input = ['control_pagebreak', 'control_collapse', 'control_head', 'control_text', 'control_image', 'control_button', 'control_captcha'];

/**
 * Recurring period texts for payment wizards
 */
var recurring_periods = [['Daily', 'Daily'.locale()], ['Weekly', 'Weekly'.locale()], ['Bi-Weekly', 'Bi-Weekly'.locale()], ['Monthly', 'Monthly'.locale()], ['Bi-Monthly', 'Bi-Monthly'.locale()], ['Quarterly', 'Quarterly'.locale()], ['Semi-Yearly', 'Semi-Yearly'.locale()], ['Yearly', 'Yearly'.locale()], ['Bi-Yearly', 'Bi-Yearly'.locale()]];

/**
 * Trial period texts
 */
var trial_periods = [['None', 'None'.locale()], ['One Day', 'One Day'.locale()], ['Three Days', 'Three Days'.locale()], ['Five Days', 'Five Days'.locale()], ['10 Days', '10 Days'.locale()], ['15 Days', '15 Days'.locale()], ['30 Days', '30 Days'.locale()], ['60 Days', '60 Days'.locale()], ['6 Months', '6 Months'.locale()], ['1 Year', '1 Year'.locale()]];

var default_email = "<table bgcolor=\"#f7f9fc\" width=\"100%\" height=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td height=\"30\">&nbsp;</td></tr><tr><td align=\"center\"><table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td width=\"13\" height=\"30\" background=\"http://www.jotform.com/images/win2_title_left.gif\"></td><td align=\"right\" background=\"http://www.jotform.com/images/win2_title.gif\" valign=\"bottom\"><img src=\"http://www.jotform.com/images/win2_title_logo.gif\" width=\"63\" height=\"26\" style=\"float:left\"  /></td><td width=\"14\" background=\"http://www.jotform.com/images/win2_title_right.gif\"></td></tr></table><table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td width=\"4\" background=\"http://www.jotform.com/images/win2_left.gif\"></td><td align=\"center\" bgcolor=\"#FFFFFF\"><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\"><tr><td bgcolor=\"#f9f9f9\" width=\"170\" style=\"text-decoration:underline; padding:5px !important;\"><b>Question</b></td><td bgcolor=\"#f9f9f9\" style=\"text-decoration:underline; padding:5px !important;\"><b>Answer</b></td></tr><tr><td bgcolor=\"white\" style=\"padding:5px !important;\" width=170>First Name</td><td bgcolor=\"white\" style=\"padding:5px !important;\">{firstName}</td></tr><tr><td bgcolor=\"#f9f9f9\" style=\"padding:5px !important;\" width=170>Last Name</td><td bgcolor=\"#f9f9f9\" style=\"padding:5px !important;\">{lastName}</td></tr><tr><td bgcolor=\"white\" style=\"padding:5px !important;\" width=170>E-mail</td><td bgcolor=\"white\" style=\"padding:5px !important;\">{email}</td></tr><tr><td bgcolor=\"#f9f9f9\" style=\"padding:5px !important;\" width=170>Address</td><td bgcolor=\"#f9f9f9\" style=\"padding:5px !important;\">{address}</td></tr><tr><td bgcolor=\"white\" style=\"padding:5px !important;\" width=170>City</td><td bgcolor=\"white\" style=\"padding:5px !important;\">{city}</td></tr><tr><td bgcolor=\"#f9f9f9\" style=\"padding:5px !important;\" width=170>State</td><td bgcolor=\"#f9f9f9\" style=\"padding:5px !important;\">{state}</td></tr><tr><td bgcolor=\"white\" style=\"padding:5px !important;\" width=170>Country</td><td bgcolor=\"white\" style=\"padding:5px !important;\">{country}</td></tr><tr><td bgcolor=\"#f9f9f9\" style=\"padding:5px !important;\" width=170>Your Message</td><td bgcolor=\"#f9f9f9\" style=\"padding:5px !important;\">{message}</td></tr></table></td><td width=\"4\" background=\"http://www.jotform.com/images/win2_right.gif\"><div style=\"overflow:hidden; height:0px;\">&nbsp;</div></td></tr><tr><td height=\"4\" background=\"http://www.jotform.com/images/win2_foot_left.gif\"><div style=\"overflow: hidden; height: 0px;\">&nbsp; </div></td><td background=\"http://www.jotform.com/images/win2_foot.gif\"><div style=\"overflow:hidden; height:0px;\">&nbsp;</div></td><td background=\"http://www.jotform.com/images/win2_foot_right.gif\"><div style=\"overflow:hidden; height:0px;\">&nbsp;</div></td></tr></table></td></tr><tr><td height=\"30\">&nbsp;</td></tr></table>";

var styles = [
    {value:'form', text:'Default'.locale(), image:'images/styles/default.png'},
    {value:'pastel', text:'Pastel'.locale(), image:'images/styles/pastel.png'},
    {value:'jottheme', text:'Jot Theme'.locale(), image:'images/styles/jottheme.png'},
    {value:'baby_blue', text:'Baby Blue'.locale(), image:'images/styles/babyblue.png'},
    {value:'paper_grey', text:'Paper Grey'.locale(), image:'images/styles/papergrey.png'},
    {value:'post_it_yellow', text:'Post-It Yellow'.locale(), image:'images/styles/postit.png'},
    {value:'denimjeans', text:'Denim Jeans'.locale(), image:'images/styles/denimjeans.png'},
    {value:'industrial_dark', text:'Industrial Dark'.locale(), image:'images/styles/industry.png'},
    {value:'OldPaper', text:'Old Paper'.locale(), image:'images/styles/oldpaper.png'},
    {value:'solid', text:'Solid Form'.locale(), image:'images/styles/solid.png'},
    {value:'big', text:'XXL Form'.locale(), image:'images/styles/biginput.png'}
];

// Convert styles to a dropdown array
var stylesDropDown = [];
(function(){
    for(var x=0; x < styles.length; x++){
        stylesDropDown.push([styles[x].value, styles[x].text]);
    }
})();

var preferenceTabs = {
    general:'General Settings'.locale(),
    formStyle:'Form Styles'.locale(),
    fieldStyle:'Field Styles'.locale(),
    advanced: 'Advanced Settings'.locale()
};

/**
 * Default question definitions,
 * dropdown: {array}   If specified, array of values will be used in selecting the value of this property
 * text:     {string}  Visisble Text of the Property, Must be readable
 * value:    {string|number|array}  Default value for this field, It will be selected and used.
 * splitter: {string}  Use with textareas, Defiens how textarea value will be splitted and merged back. for example: '|'
 * toolbar:  {boolean} If false is given, property will not be displayed on the toolbar
 * color:    {boolean} If true, colopicker will be used for selecting the value for this property
 * textarea: {boolean} Defines if this value is a long text or not. If set to true, Text area will be used for this item
 * hidden:   {boolean} Do not display this property in any of the pages
 * nolabel:  {boolean} use with title attribute, set true if you want to spread the question form wide. No question label will be printed. Such as buttons
 */ 
var default_properties = {
    /**
     * Form Properties
     */
    "form": {
        title:          { text:'Title'.locale(), value:'Untitled Form'.locale(), toolbar:false, tab:'general' },
        styles:         { text:'Themes'.locale(), value:'form', dropdown: stylesDropDown, toolbar:true , tab:'formStyle'},
        font:           { text:'Font Family'.locale(), value:'Verdana', dropdown:fonts, tab:'formStyle' },
        fontsize:       { text:'Font Size'.locale(), value:'12', unit:'px', tab:'formStyle' },
        fontcolor:      { text:'Font Color'.locale(), value:'Black', color:true, tab:'formStyle' },
        lineSpacing:    { text:'Question Spacing'.locale(), value:'10', unit:'px',  toolbar:false, tab:'fieldStyle' },
        background:     { text:'Background'.locale(), value:'', color:true, tab:'formStyle' },
        formWidth:      { text:'Form Width'.locale(), value:'690', unit:'px', tab:'formStyle'},
        labelWidth:     { text:'Label Width'.locale(), value:'150', tab:'fieldStyle'},
        alignment:      { text:'Label Alignment'.locale(), value:'Left', dropdown:[['Left', 'Left'.locale()], ['Top', 'Top'.locale()], ['Right', 'Right'.locale()]], tab:'fieldStyle' },
        thankurl:       { text:'Thank You URL'.locale(), value:'', hidden:true },
        thanktext:      { text:'Thank You Text'.locale(), value:'', textarea:true, hidden:true },
        highlightLine:  { text:'Highlight Effect'.locale(), value:'Enabled'.locale(), dropdown:[['Enabled', 'Enabled'.locale()], ['Disabled', 'Disabled'.locale()]], toolbar:false, tab:'fieldStyle'},
        activeRedirect: { text:'Active Redirect'.locale(), value:'default', dropdown:[['default', 'Default'.locale()], ['thankurl', 'Thank You URL'.locale()], ['thanktext', 'Thank You Text'.locale()]], hidden:true },
        sendpostdata:   { text:'Send Post Data'.locale(), value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]], toolbar: false, tab:'advanced'},
        // sendEmail:    { text:'Send Email'.locale(), value:'Yes', dropdown:[['Yes', 'Yes'.locale()], ['No', 'No'.locale()]] },
        // secure:       { text:'Require SSL?'.locale(), value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]], toolbar: false },
        unique:         { text:'Unique Submission'.locale(), value:'None', dropdown:[['None', 'No Check'.locale()], ['Loose', 'Loose Check'.locale()], ['Strict', 'Strict Check'.locale()]], toolbar: false, tab:'advanced' },
        status:         { text:'Status'.locale(), value:'Enabled'.locale(), dropdown:[['Enabled', 'Enabled'.locale()], ['Disabled', 'Disabled'.locale()]], toolbar:false, tab:'general'},
        injectCSS:      { text:'Inject Custom CSS', value:'', textarea:true, toolbar:false, tab:'formStyle'},
        formStrings:    {
            text: 'Form Warnings',
            value:[{ // We cannot save objects directly as JSON. it should be contained in an array
                required:           'This field is required.',
                alphabetic:         'This field can only contain letters',
                numeric:            'This field can only contain numeric values',
                alphanumeric:       'This field can only contain letters and numbers.',
                incompleteFields:   'There are incomplete required fields. Please complete them.',
                uploadFilesize:     'File size cannot be bigger than:',
                confirmClearForm:   'Are you sure you want to clear the form',
                lessThan:           'Your score should be less than',
                email:              'Enter a valid e-mail address',
                uploadExtensions:   'You can only upload following files:',
                pleaseWait:         'Please wait...'
            }],
            tab:'advanced'
        },
        emails:         { 
            text:'Emails', 
            value:[],
            hidden:true
        }
    },
    /**
     * Toolbox
     */
    "control_text": {
        text:        { text:'HTML'.locale(), value:'Click to edit this text...'.locale(), nolabel:true, forceDisplay:true, icon:'images/blank.gif', iconClassName:'toolbar-font_new', type:'textarea', hint:'Be careful with the code. If you place unclosed tags or broken HTML it may break your form completely.' }
    },
    "control_head":{
        text:        { text:'Text'.locale(), value:'Click to edit this text...'.locale(), nolabel:true },
        subHeader:   { text:'Sub Heading'.locale(),  value:''.locale() },
        headerType:  { text:'Heading Type'.locale(), value:'Default'.locale(), dropdown:[['Default', 'Default'.locale()], ['Large', 'Large'.locale()], ['Small', 'Small'.locale()]] }
    },
    "control_textbox": {
        text:        { text:'Question'.locale(), value:'....' },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]]},
        required:    { text:'Required'.locale(), value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]]},
        size:        { text:'Size'.locale(), value: 20},
        validation:  { text:'Validation'.locale(), value:'None', dropdown:[['None', 'None'.locale()], ['Email', 'Email'.locale()], ['AlphaNumeric', 'AlphaNumeric'.locale()], ['Alphabetic', 'Alphabetic'.locale()], ['Numeric', 'Numeric'.locale()]]},
        maxsize:     { text:'Max Size'.locale(), value:''},
        defaultValue:{ text:'Default Value'.locale(), value:''},
        subLabel:    { text:'Sub Label'.locale(), value:''},
        hint:        { text:'Hint Example'.locale(), value:''},
        description: { text:'Hover Text'.locale(), value:'', textarea:true}
    },
    "control_textarea": {
        text:        { text:'Question'.locale(), value:'....' },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required'.locale(), value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        cols:        { text:'Columns'.locale(), value: 40 },
        rows:        { text:'Rows'.locale(), value: 6 },
        validation:  { text:'Validation'.locale(), value:'None', dropdown:[['None', 'None'.locale()], ['Email', 'Email'.locale()], ['AlphaNumeric', 'AlphaNumeric'.locale()], ['Alphabetic', 'Alphabetic'.locale()], ['Numeric', 'Numeric'.locale()]] },
        entryLimit:  { text:'Entry Limit'.locale(), value:'None-0', values:[['None', 'No-Limit'.locale()], ['Words', 'Words'.locale()], ['Letters', 'Letters'.locale()]], type:'textarea-combined', hint:'Limity entry by words or letters'.locale() },
        defaultValue:{ text:'Default Value'.locale(), value:''},
        subLabel:    { text:'Sub Label'.locale(), value:''},
        hint:        { text:'Hint Example'.locale(), value:'' },
        description: { text:'Hover Text'.locale(), value:'', textarea:true }
    },
    "control_dropdown": {
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required'.locale(), value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        options:     { text:'Options'.locale(), value: "Option 1|Option 2|Option 3".locale(), textarea:true, splitter:'|', icon:'images/blank.gif', iconClassName: 'toolbar-dropdown_options' },
        special:     { text:'Special Options', value:'None', dropdown: special_options.getByType('dropdown'), icon:'images/blank.gif', iconClassName:'toolbar-dropdown_special'  },
        size:        { text:'Height', value: 0, icon:'images/blank.gif', iconClassName:'toolbar-dropdown_size' },
        width:       { text:'Width', value: 150, icon:'images/blank.gif', iconClassName:'toolbar-dropdown_width' },
        selected:    { text:'Selected', value:'', dropdown:'options', icon:'images/blank.gif', iconClassName: 'toolbar-dropdown_selected'},
        subLabel:    { text:'Sub Label'.locale(), value:''},
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_checkbox": {
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        options:     { text:'Options', value: "Option 1|Option 2|Option 3".locale(), textarea:true, splitter:'|', icon: 'images/blank.gif', iconClassName: 'toolbar-checkbox_options' },
        special:     { text:'Special Options', value:'None', dropdown: special_options.getByType('checkbox'), icon:'images/blank.gif', iconClassName: 'toolbar-checkbox_special'  },
        spreadCols:  { text:'Spread to Columns', value:'1', icon:'images/blank.gif', iconClassName: 'toolbar-checkbox_columns' },
        selected:    { text:'Selected', value:'', dropdown:'options', icon:'images/blank.gif', iconClassName: 'toolbar-checkbox_selected' },
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_radio": {
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        options:     { text:'Options', value: "Option 1|Option 2|Option 3".locale(), textarea:true, splitter:'|', icon: 'images/blank.gif', iconClassName: 'toolbar-radio_options' },
        special:     { text:'Special Options', value:'None', dropdown: special_options.getByType('radio'), icon:'images/blank.gif', iconClassName: 'toolbar-radio_special'},
        allowOther:  { text:'Allow Other', value:'No', dropdown:[['Yes', 'Yes'.locale()], ['No', 'No'.locale()]], icon:'images/blank.gif', iconClassName: 'toolbar-radio_other' },
        selected:    { text:'Selected', value:'', dropdown:'options', icon:'images/blank.gif', iconClassName: 'toolbar-radio_selected'},
        spreadCols:  { text:'Spread to Columns', value:'1', icon:'images/blank.gif', iconClassName: 'toolbar-radio_columns' },
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_datetime": {
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        format:      { text:'Date Format', value: 'mmddyyyy', dropdown:[['mmddyyyy', 'mmddyyyy'.locale()], ['ddmmyyyy', 'ddmmyyyy'.locale()]],icon:'images/blank.gif', iconClassName:'toolbar-date_format' },
        allowTime:   { text:'Allow Time', value: "Yes", dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]], icon:'images/blank.gif', iconClassName: 'toolbar-allow_time' },
        timeFormat:  { text:'Time Format', value: "AM/PM", dropdown:[['24 Hour', '24 Hour'.locale()], ['AM/PM', 'AM/PM'.locale()]], icon:'images/blank.gif', iconClassName: 'toolbar-time_format_24' },
        defaultTime: {  text:'Default Time', value:'Yes', dropdown:[['Yes', 'Yes'.locale()], ['No', 'No'.locale()]]},
        description: { text:'Hover Text', value:'', textarea:true },
        sublabels:   { text:'Sub Labels', value:{
            day:    'Day'.locale(),
            month:  'Month'.locale(),
            year:   'Year'.locale(),
            last:   'Last Name'.locale(),
            hour:   'Hour'.locale(),
            minutes:'Minutes'.locale()
        }, hidden:true, toolbar:false }
    },
    "control_fileupload": {
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        allowMultiple:{ text:'Allow Multiple', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]]/*, hidden: ("document" in window && !document.DEBUG)*/ },
        maxFileSize: { text:'Max File Size', value: "10240", icon:'images/blank.gif', iconClassName: 'toolbar-max_value'},
        extensions:  { text:'Extensions', value: "pdf, doc, docx, xls, csv, txt, rtf, html, zip, mp3, wma, mpg, flv, avi, jpg, jpeg, png, gif", icon:'images/blank.gif', iconClassName:'toolbar-upload_extensions' },
        subLabel:    { text:'Sub Label'.locale(), value:''},
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_button": {
        text:        { text:'Submit Text', value: "Submit Form".locale(), nolabel:true, forceDisplay:true, icon:'images/blank.gif', iconClassName:'toolbar-font_new' },
        useImage:    { text:'Button Image', value:'', icon:'images/blank.gif', iconClassName:'toolbar-image_source'},
        buttonAlign: { text:'Button Align', value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Center', 'Center'.locale()], ['Right', 'Right'.locale()]] },
        clear:       { text:'Reset Button', value:'No', dropdown:[["Yes", "Yes".locale()], ["No", "No".locale()]] },
        print:       { text:'Print Button', value:'No', dropdown:[["Yes", "Yes".locale()], ["No", "No".locale()]] }
    },
    /**
     * Power Tools
     */
    "control_passwordbox":{
        text:        { text:'Question'.locale(), value:'....' },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        size:        { text:'Size', value: 20 },
        validation:  { text:'Validation', value:'None', dropdown:[['None', 'None'.locale()], ['Email', 'Email'.locale()], ['AlphaNumeric', 'AlphaNumeric'.locale()], ['Alphabetic', 'Alphabetic'.locale()], ['Numeric', 'Numeric'.locale()]] },
        maxsize:     { text:'Max Size', value:'' },
        defaultValue:{ text:'Default Value', value:''},
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_hidden":{
        text:        { text:'Question'.locale(), value:'....' },
        defaultValue:{ text:'Default Value', value:''}
    },
    "control_autoincrement":{
        text:        { text:'Auto Increment'.locale(), value:'....' },
        currentIndex:{ text:'Current Index', value:'0'},
        idPrefix:      { text:'Prefix', value:''},
        idPadding:     { text:'Number Padding', value:'0'}
    },
    "control_image": {
        text:        { text:'Text'.locale(), value:'Image', nolabel:true },
        src:         { text:'Image Source'.locale(), value:'http://www.jotform.com/images/logo.png', icon:'images/blank.gif', iconClassName:'toolbar-image_source' },
        link:        { text:'Image Link'.locale(), value:'', icon:'images/blank.gif', iconClassName: 'toolbar-image_link' },
        height:      { text:'Height'.locale(), value:'93'},
        width:       { text:'Width'.locale(), value:'210'},
        align:       { text:'Align'.locale(), value:'Left', dropdown:[['Left', 'Left'.locale()], ['Center', 'Center'.locale()], ['Right', 'Right'.locale()]], icon:'images/blank.gif', iconClassName: 'toolbar-image_align' },
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_captcha":{
        text:        { text:'Question'.locale(), value: "Enter the message as it's shown".locale() },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        useReCaptcha:{ text:'reCaptcha'.locale(), value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]]},
        required:    { text:'Required', value:'Yes', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]], hidden:true }, // Required by default
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_autocomp":{
        text:        { text:'Question'.locale(), value:'....' },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        items:       { text:'Items'.locale(), value: "Item 1|Item 2|Item 3".locale(), textarea:true, splitter:'|' },
        size:        { text:'Size', value: 20 },
        validation:  { text:'Validation', value:'None', dropdown:[['None', 'None'.locale()], ['Email', 'Email'.locale()], ['AlphaNumeric', 'AlphaNumeric'.locale()], ['Alphabetic', 'Alphabetic'.locale()], ['Numeric', 'Numeric'.locale()]] },
        maxsize:     { text:'Max Size', value:'' },
        defaultValue:{ text:'Default Value', value:''},
        subLabel:    { text:'Sub Label'.locale(), value:''},
        hint:        { text:'Hint Example', value:'' },
        description: { text:'Hover Text', value:'', textarea:true }
    },
    /**
     * Survey Tools
     */
    "control_rating":{
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        stars:       { text:'Star Amount'.locale(), value: "5", icon:'images/blank.gif', iconClassName: 'toolbar-star_amount' },
        starStyle:   { text:'Star Style'.locale(), value:'Stars', dropdown:[['Stars', 'Stars'.locale()], ['Stars 2', 'Stars 2'.locale()], ['Hearts', 'Hearts'.locale()], ['Light Bulps', 'Light Bulbs'.locale()], ['Lightnings', 'Lightnings'.locale()], ['Flags', 'Flags'.locale()], ['Shields', 'Shields'.locale()], ['Pluses', 'Pluses'.locale()]], icon:'images/blank.gif', iconClassName: 'toolbar-star_style'},
        defaultValue:{ text:'Default Value', value:''},
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_scale": {
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        fromText:    { text:'"Worst" Text', value:'Worst', icon:'images/blank.gif', iconClassName: 'toolbar-scale_from'},
        toText:      { text:'"Best" Text', value:'Best', icon:'images/blank.gif', iconClassName: 'toolbar-scale_to'},
        scaleAmount: { text:'Scale Amount', value:'5', icon:'images/blank.gif', iconClassName: 'toolbar-scale_amount'},
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_slider":{
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        maxValue:    { text:'Maximum Value', value: "100" },
        width:       { text:'Width', value: "200" },
        defaultValue:{ text:'Default Value', value:'0'},
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_spinner":{
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        width:       { text:'Width', value: "60", icon:'images/blank.gif', iconClassName: 'toolbar-spinner_size' },
        maxValue:    { text:'Maximum Value', value: '' },
        minValue:    { text:'Minimum Value', value: '' },
        addAmount:   { text:'Stepping', value:'1' },
        allowMinus:  { text:'Allow Negatives', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]]  },
        defaultValue:{ text:'Default Value', value:'0'},
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_range":{
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        addAmount:   { text:'Stepping', value:'1' },
        allowMinus:  { text:'Allow Negatives', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]]  },
        defaultFrom: { text:'Default From', value:'0', icon:'images/blank.gif', iconClassName: 'toolbar-range_default_from'},
        defaultTo:   { text:'Default To', value:'0', icon:'images/blank.gif', iconClassName: 'toolbar-range_default_to'},
        description: { text:'Hover Text', value:'', textarea:true },
        sublabels:   { text:'Sub Labels', value:{
            from: 'From'.locale(),
            to:   'To'.locale()
        }, hidden:true, toolbar:false }
    },
    "control_grading":{
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        options:     { text:'Options', value:'Item 1|Item 2|Item 3', textarea:true, splitter:'|', icon:'images/blank.gif', iconClassName:'toolbar-grading_options' },
        total:       { text:'Total', value:'100', icon:'images/blank.gif', iconClassName:'toolbar-grading_total' },
        boxAlign:    { text:'Box Alignment', value:'Left', dropdown:[['Left', 'Left'.locale()], ['Right', 'Right'.locale()]], icon:'images/blank.gif', iconClassName: 'toolbar-label_align' }
    },
    "control_matrix":{
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Top', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        mrows:       { text:'Rows', value:'Service Quality'.locale()   +'|'+
                                          'Overall Hygiene'.locale()   +'|'+
                                          'Responsiveness'.locale()    +'|'+
                                          'Kindness and Helpfulness'.locale(), textarea:true, splitter:'|' },
                                          
        mcolumns:    { text:'Columns', value:'Very Satisfied'.locale()     +'|'+
                                             'Satisfied'.locale()          +'|'+
                                             'Somewhat Satisfied'.locale() +'|'+
                                             'Not Satisfied'.locale(), textarea:true, splitter:'|' },
                                             
        inputType:   { text:'Input Type', value :'Radio Button', dropdown:[['Radio Button', 'Radio Button'.locale()], ['Check Box', 'Check Box'.locale()], ['Text Box', 'Text Box'.locale()], ['Drop Down', 'Drop Down'.locale()]]},
        dropdown:    { text:'Dropdown Options', value:'Yes|No', textarea:true, splitter:'|', hidden:true },
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_collapse":{
        text:        { text:'Text'.locale(), value:'Click to edit this text...', nolabel:true },
        status:      { text:'Status', value:'Closed', dropdown:[['Closed', 'Closed'.locale()], ['Open', 'Open'.locale()]], icon:'images/blank.gif', iconClassName: 'toolbar-collapse_open'},
        visibility:  { text:'Visibility', value:'Visible', dropdown:[['Visible', 'Visible'.locale()], ['Hidden', 'Hidden'.locale()]], icon:'images/blank.gif', iconClassName:'toolbar-visibility'}
    },
    "control_pagebreak":{
        text:        { text:'Text'.locale(), value:'Page Break', nolabel: true }
    },
    /**
     * Quick Fields
     */
    "control_fullname":{
        text:        { text:'Question'.locale(), value: "Full Name".locale() },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        prefix:      { text:'Prefix', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]], icon:'images/blank.gif', iconClassName: 'toolbar-name_prefix' },
        suffix:      { text:'Suffix', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]], icon:'images/blank.gif', iconClassName: 'toolbar-name_suffix' },
        middle:      { text:'Middle Name', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]], icon:'images/blank.gif', iconClassName: 'toolbar-name_middle' },
        description: { text:'Hover Text', value:'', textarea:true },
        sublabels:   { text:'Sub Labels', value:{
            prefix: 'Prefix'.locale(),
            first:  'First Name'.locale(),
            middle: 'Middle Name'.locale(),
            last:   'Last Name'.locale(),
            suffix: 'Suffix'.locale()
        }, hidden:true, toolbar:false }
    },
    "control_location":{
        text:        { text:'Question'.locale(), value: "...." },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        description: { text:'Hover Text', value:'', textarea:true }
    },
    "control_address": {
        text:        { text:'Question'.locale(), value: "Address".locale() },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
        selectedCountry: { text:'Country', value:'', dropdown:special_options.Countries.value , icon:'images/blank.gif', iconClassName:'toolbar-dropdown_selected'},
    	description: { text:'Hover Text', value:'', textarea:true },
        sublabels:   { text:'Sub Labels', value:{
            cc_firstName:   'First Name'.locale(),
            cc_lastName:    'Last Name'.locale(),
            cc_number:      'Credit Card Number'.locale(),
            cc_ccv:         'Security Code'.locale(),
            cc_exp_month:   'Expiration Month'.locale(),
            cc_exp_year:    'Expiration Year'.locale(),
            addr_line1:     'Street Address'.locale(),
            addr_line2:     'Street Address Line 2'.locale(),
            city:           'City'.locale(),
            state:          'State / Province'.locale(),
            postal:         'Postal / Zip Code'.locale(),
            country:        'Country'.locale()
        }, hidden:true, toolbar:false }
    },
    "control_email": {
        text:        { text:'Question'.locale(), value:'E-mail'.locale() },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]]},
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]]},
        size:        { text:'Size', value: 30},
        validation:  { text:'Validation', value:'Email', dropdown:[['None', 'None'.locale()], ['Email', 'Email'.locale()], ['AlphaNumeric', 'AlphaNumeric'.locale()], ['Alphabetic', 'Alphabetic'.locale()], ['Numeric', 'Numeric'.locale()]]},
        maxsize:     { text:'Max Size', value:''},
        defaultValue:{ text:'Default Value', value:''},
        subLabel:    { text:'Sub Label'.locale(), value:''},
        hint:        { text:'Hint Example', value:'ex: myname@example.com'},
        description: { text:'Hover Text', value:'', textarea:true}
    },
    "control_number": {
        text:        { text:'Question'.locale(), value:'Number'.locale() },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]]},
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]]},
        size:        { text:'Size', value: 5},
        validation:  { text:'Validation', value:'Numeric', dropdown:[['None', 'None'.locale()], ['Email', 'Email'.locale()], ['AlphaNumeric', 'AlphaNumeric'.locale()], ['Alphabetic', 'Alphabetic'.locale()], ['Numeric', 'Numeric'.locale()]]},
        maxsize:     { text:'Max Size', value:''},
        defaultValue:{ text:'Default Value', value:''},
        subLabel:    { text:'Sub Label'.locale(), value:''},
        hint:        { text:'Hint Example', value:'ex: 23'},
        description: { text:'Hover Text', value:'', textarea:true}
    },
    "control_phone":{
        text:        { text:'Question'.locale(), value:'Phone Number'.locale() },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]]},
        required:    { text:'Required', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]]},
        description: { text:'Hover Text', value:'', textarea:true},
        sublabels:   { text:'Sub Labels', value:{
            area:  'Area Code'.locale(),
            phone: 'Phone Number'.locale()
        }, hidden:true, toolbar:false }
    },
    "control_birthdate": {
        text:        { text:'Question'.locale(), value:'Birth Date'.locale() },
        labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]]},
        required:    { text:'Required'.locale(), value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]]},
        format:      { text:'Date Format'.locale(), value:'mmddyyyy', dropdown:[['mmddyyyy', 'mmddyyyy'.locale()], ['ddmmyyyy', 'ddmmyyyy'.locale()]],icon:'images/blank.gif', iconClassName:'toolbar-date_format'},
        description: { text:'Hover Text'.locale(), value:'', textarea:true},
        sublabels:   { text:'Sub Labels', value:{
            month: 'Month'.locale(),
            day:   'Day'.locale(),
            year:  'Year'.locale()
        }, hidden:true, toolbar:false }
    },
    /**
     * Payment fields
     */
    "control_payment": { // Will be extended with default payment properties
        currency:     { text:'Currency'.locale(), value:'USD', dropdown:[ [' ', 'No Currency'], ['USD', 'U.S. Dollar'], ['EUR', 'Euro'], ['CAD', 'Canadian Dollar'], ['AUD', 'Australian Dollar'], ['CHF', 'Swiss Franc'], ['CZK', 'Czech Koruna'], ['DKK', 'Danish Krone'], ['GBP', 'Pound Sterling'], ['HKD', 'Hong Kong Dollar'], ['HUF', 'Hungarian Forint'], ['ILS', 'Israeli New Sheqel'], ['JPY', 'Japanese Yen'], ['MXN', 'Mexican Peso'], ['BRL', 'Brazilian real'], ['NOK', 'Norwegian Krone'], ['NZD', 'New Zealand Dollar'], ['PLN', 'Polish Zloty'], ['SEK', 'Swedish Krona'], ['SGD', 'Singapore Dollar'], ['THB', 'Thai baht'], ['PHP', 'Philippine Peso'], ['IDR', 'Indonesian Rupiah']] },
        bridge:       { text:'Bridge', hidden:true, toolbar:false, value:'' }
    },
    "control_paypal": { // Will be extended with default payment properties
        account:      { text:'PayPal Account'.locale(), value:'', /*  'serkan@interlogy.com', */ toolbar: false},
        payeraddress: { text:'Need Payer Address'.locale(), value:'Yes', dropdown:[['Yes', 'Yes'.locale()], ['No', 'No'.locale()]], toolbar: false },
        currency:     { text:'Currency'.locale(), value:'USD', dropdown:[ ['USD', 'U.S. Dollar'], ['EUR', 'Euro'], ['CAD', 'Canadian Dollar'], ['AUD', 'Australian Dollar'], ['CHF', 'Swiss Franc'], ['CZK', 'Czech Koruna'], ['DKK', 'Danish Krone'], ['GBP', 'Pound Sterling'], ['HKD', 'Hong Kong Dollar'], ['HUF', 'Hungarian Forint'], ['ILS', 'Israeli New Sheqel'], ['JPY', 'Japanese Yen'], ['MXN', 'Mexican Peso'], ['NOK', 'Norwegian Krone'], ['NZD', 'New Zealand Dollar'], ['PLN', 'Polish Zloty'], ['SEK', 'Swedish Krona'], ['SGD', 'Singapore Dollar']] },
        bridge:       { text:'Bridge', hidden:true, toolbar:false, value:'' }
    },
    "control_paypalpro":{ // Will be extended with default payment properties
        username:    { text:'API Username'.locale(), value: '', /*'paypro_1258546632_biz_api1.interlogy.com',*/ size:'40', toolbar: false},
        password:    { text:'API Password'.locale(), value:'', /*'5FJVKQS73QFYXS94',*/ size:'40', toolbar: false},
        signature:   { text:'Signature'.locale(), value:'', /*'APNWT6wvwwSEcBxG3LFu7lwBgwnRA0Ok1B6hFI3uQzk3MJU-ahgR3DfW',*/ size:'40', toolbar: false },
        currency:    { text:'Currency'.locale(),  value:'USD', dropdown:[ ['USD', 'U.S. Dollar'], ['EUR', 'Euro'], ['CAD', 'Canadian Dollar'], ['AUD', 'Australian Dollar'], ['CHF', 'Swiss Franc'], ['CZK', 'Czech Koruna'], ['DKK', 'Danish Krone'], ['GBP', 'Pound Sterling'], ['HKD', 'Hong Kong Dollar'], ['HUF', 'Hungarian Forint'], ['ILS', 'Israeli New Sheqel'], ['JPY', 'Japanese Yen'], ['MXN', 'Mexican Peso'], ['NOK', 'Norwegian Krone'], ['NZD', 'New Zealand Dollar'], ['PLN', 'Polish Zloty'], ['SEK', 'Swedish Krona'], ['SGD', 'Singapore Dollar'] ] },
        sublabels:   { text:'Sub Labels', value:{
            cc_firstName:   'First Name'.locale(),
            cc_lastName:    'Last Name'.locale(),
            cc_number:      'Credit Card Number'.locale(),
            cc_ccv:         'Security Code'.locale(),
            cc_exp_month:   'Expiration Month'.locale(),
            cc_exp_year:    'Expiration Year'.locale(),
            addr_line1:     'Street Address'.locale(),
            addr_line2:     'Street Address Line 2'.locale(),
            city:           'City'.locale(),
            state:          'State / Province'.locale(),
            postal:         'Postal / Zip Code'.locale(),
            country:        'Country'.locale()
        }, hidden:true, toolbar:false }
    },
    "control_authnet": { // Will be extended with default payment properties
        apiLoginId:     { text:'API Login ID'.locale(), value:'', toolbar: false},
        transactionKey: { text:'Transaction Key'.locale(), value:'', toolbar: false },
        currency:       { text:'Currency'.locale(), value:'USD', dropdown:[['USD', 'USD'.locale()]] },
        sublabels:   { text:'Sub Labels', value:{
            cc_firstName:   'First Name'.locale(),
            cc_lastName:    'Last Name'.locale(),
            cc_number:      'Credit Card Number'.locale(),
            cc_ccv:         'Security Code'.locale(),
            cc_exp_month:   'Expiration Month'.locale(),
            cc_exp_year:    'Expiration Year'.locale(),
            addr_line1:     'Street Address'.locale(),
            addr_line2:     'Street Address Line 2'.locale(),
            city:           'City'.locale(),
            state:          'State / Province'.locale(),
            postal:         'Postal / Zip Code'.locale(),
            country:        'Country'.locale()
        }, hidden:true, toolbar:false }
    },
    "control_googleco":{ // Will be extended with default payment properties
        merchantID:  { text:'Merchant ID'.locale(), value:'', /*'562119837076467',*/ toolbar: false}, // Sandbox Account
        currency:    { text:'Currency'.locale(), value:'USD', dropdown:[['USD', 'USD'.locale()], ['GBP', 'GBP'.locale()]] }
    },
    "control_onebip":{ // Will be extended with default payment properties
        username:     { text:'OneBip Username', value:'', toolbar: false},
        itemNo:       { text:'Item Number', value:'', size:6, toolbar: false},
        productName:  { text:'Product Name'.locale(), value:'', toolbar: false},
        productPrice: { text:'Product Price'.locale(), value:'', size:6, toolbar: false},
        currency:     { text:'Currency'.locale(), value:'USD', dropdown:[['USD', 'USD'.locale()], ['EUR', 'EUR'.locale()]] }
    },
    "control_worldpay":{ // Will be extended with default payment properties
        installationID: { text:'WorldPay Installation ID', value:'',/*'141159',*/ toolbar: false}, // Sandbox Account
        currency:       { text:'Currency'.locale(), value:'USD', dropdown:[['USD', 'USD'.locale()],['GBP', 'GBP'.locale()], ['EUR', 'EUR'.locale()]] }
    },
    "control_2co":{      // Will be extended with default payment properties
        vendorNumber: { text:'2CheckOut Vendor Number', value:'',/*'1231290',*/ toolbar: false}, // Sandbox Account
        currency:     { text:'Currency'.locale(), value:'USD', dropdown:[['USD', 'USD'.locale()], ['EUR', 'EUR'.locale()]] }
    },
    "control_clickbank":{ // Will be extended with default payment properties
        login:        { text:'ClickBank Account name', value:'', toolbar: false},
        itemNo:       { text:'Item Number', value:'', size:6, toolbar: false},
        productName:  { text:'Product Name'.locale(), value:'', toolbar: false},
        productPrice: { text:'Product Price'.locale(), value:'', size:6, toolbar: false},
        currency:     { text:'Currency', value:'USD', dropdown:[['USD', 'USD'.locale()], ['EUR', 'EUR'.locale()]] }
    }
};

/**
 * All payment objects has these properties in common
 */
var default_payments_properties = {
    text:        { text:'Question'.locale(), value: "My Products" },
    labelAlign:  { text:'Label Align'.locale(), value:'Auto', dropdown:[['Auto', 'Auto'.locale()], ['Left', 'Left'.locale()], ['Right', 'Right'.locale()], ['Top', 'Top'.locale()]] },
    required:    { text:'Required'.locale(), value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
    description: { text:'Hover Text'.locale(), value:'', textarea:true },
    products:    { text:'Products'.locale(), value:'', toolbar:false, hidden:true },
    showTotal:   { text:'Total', value:'No', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
    paymentType: { text:'Payment Type'.locale(), value:'product', dropdown:[['product', 'Product'.locale()], ['subscription', 'Subscription'.locale()], ['donation', 'Donation'.locale()]], toolbar: false },
    multiple:    { text:'Multiple'.locale(), value:'Yes', dropdown:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]], toolbar: false },
    donationText:{ text:'Donation Text'.locale(), value:'Donation', toolbar:false},
    suggestedDonation: { text:'Suggested Donation'.locale(), value:'', toolbar:false }
};

// Populate payment fields with their default properties
//$A(payment_fields).each(function(key){ default_properties[key] = Object.extend(default_properties[key], default_payments_properties); });
(function(){
    for(var i=0; i < payment_fields.length; i++){
        var key = payment_fields[i];
        for(var k in default_payments_properties){
            // You may deepclone default_payments_properties[k] object but you don't need to as of right now
            default_properties[key][k] = default_payments_properties[k];
        }
    }    
})();

/**
 * Common properties for the items on Toolbar.
 * Most of the questions shares the same options and their configuration must be in one place
 * Defined the type, icon and values for these items
 * 
 * icon: {string} Path for the icon of this button, If blank, default icon will be used instead
 * text: {string} Visible text for this button
 * type: {string} Input type of this property
 ***  toggle:      Toggles the value, For example Yes, No or Enable, Disable
 ***  spinner:     Use with Number values. Displays a spinner for easier number selection
 ***  dropdown:    Displays a dropdown to select the value
 ***  textarea:    Displays textarea, splits and merges the value if splitter was defined in default_properties
 ***  colorpicker: Displays a color picker
 ***  text:        Displays the default entry field
 * size: {number} Allows you to change the size of the entry field
 * hint: {string} This small message will be displayed above the input fields
 */
var toolbarItems = {
    background:  { icon: 'images/blank.gif', iconClassName:'toolbar-form_background', text:'Background'.locale(), type:'colorpicker'},
    //font:        { icon: 'images/toolbar/font.png', text:'Font'.locale(), type:'spinner'},
    font:   { 
        icon: 'images/blank.gif',
        iconClassName: 'toolbar-font',
        text: 'Font'.locale(), 
        type: 'menu',
        values:[
            {text: 'Arial', value:'Arial'}, 
            {text: 'Arial Black', value:'Arial Black'}, 
            {text: 'Courier', value:'Courier'}, 
            {text: 'Courier New', value:'Courier New'}, 
            {text: 'Comic Sans MS', value:'Comic Sans MS'}, 
            {text: 'Gill Sans', value:'Gill Sans'}, 
            {text: 'Helvetica', value:'Helvetica'}, 
            {text: 'Lucida', value:'Lucida'}, 
            {text: 'Lucida Grande', value:'Lucida Grande'}, 
            {text: 'Trebuchet MS', value:'Trebuchet MS'}, 
            {text: 'Tahoma', value:'Tahoma'}, 
            {text: 'Times New Roman', value:'Times New Roman'}, 
            {text: 'Verdana', value:'Verdana'}
        ]
    },
    fontcolor:   { icon: 'images/blank.gif', iconClassName: 'toolbar-font_color', text:'Font Color'.locale(), type:'colorpicker'},
    fontsize:    { icon: 'images/blank.gif', iconClassName: 'toolbar-font_size', text:'Font Size'.locale(), type:'spinner', size:10 },
    allowMultiple:{ icon:'images/blank.gif', iconClassName: 'toolbar-collapse_open', text:'Allow Multiple'.locale(), type:'toggle', values:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
    required:    { icon: 'images/blank.gif', iconClassName: 'toolbar-required', text: 'Required'.locale(), type:'toggle', values:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
    useReCaptcha:{ icon: 'images/toolbar/recaptcha.png', /*iconClassName: 'toolbar-required',*/ text: 'reCaptcha'.locale(), type:'toggle', values:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
    size:        { icon: 'images/blank.gif', iconClassName: 'toolbar-input_size', text: 'Size'.locale(), type:'spinner', size:10},
    validation:  { icon: 'images/blank.gif', iconClassName: 'toolbar-validation', text: 'Validation'.locale(), type:'menu',
			values:[
				    {text: 'None'.locale(), value:'None'}, 
				    {text: 'Email'.locale(), value:'Email'}, 
				    {text: 'AlphaNumeric'.locale(), value:'AlphaNumeric'}, 
				    {text: 'Alphabetic'.locale(), value:'Alphabetic'}, 
				    {text: 'Numeric'.locale(), value:'Numeric'}
			]
    }, 
    defaultValue:{ icon: 'images/blank.gif', iconClassName: 'toolbar-default', text:'Default Value'.locale(), type:'text', size:15},
    currentIndex:{ icon: 'images/toolbar/current-index.png', text:'Current Index'.locale(), type:'text', size:15, hint:"WARNING! Changing this value may affect uniqueness.".locale()},
    idPrefix:    { icon: 'images/toolbar/letter-prefix.png', text:'Prefix'.locale(), type:'text', size:15, hint:'Prefix will be added before the ID. Example: GID-1015'.locale()},
    idPadding:   { icon: 'images/toolbar/number-padding.png', text:'Number Padding'.locale(), type:'spinner', size:5, hint:'Given number of zeros will be added before the ID. Example: 00015 or GID-00015'},
    defaultTime: { icon: 'images/blank.gif', iconClassName: 'toolbar-default', text:'Default Time'.locale(), type:'toggle', values:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
    subLabel:    { icon: 'images/toolbar/input_subLabel.png', text:'Sub Label'.locale(), type:'text' },
    hint:        { icon: 'images/blank.gif', iconClassName: 'toolbar-hint', text: 'Hint Example'.locale(), type:'text', size:15 },
    description: { icon: 'images/blank.gif', iconClassName: 'toolbar-description', text: 'Hover Text'.locale(), type:'textarea', hint:'Write a description for your field' },
    cols:        { icon: 'images/blank.gif', iconClassName: 'toolbar-textarea_columns', text: 'Columns'.locale(), type:'spinner', size:10 },
    rows:        { icon: 'images/blank.gif', iconClassName: 'toolbar-textarea_rows', text: 'Rows'.locale(), type:'spinner', size:10 },
    mcolumns:    { icon: 'images/blank.gif', iconClassName: 'toolbar-matrix_columns', text: 'Columns'.locale(), type:'textarea' },
    mrows:       { icon: 'images/blank.gif', iconClassName: 'toolbar-matrix_rows', text: 'Rows'.locale(), type:'textarea' },
    inputType:   { icon: 'images/blank.gif', iconClassName: 'toolbar-matrix_input_type', text:'Input Type'.locale(), type:'dropdown'},
    alignment:   { 
        icon: 'images/blank.gif',
        iconClassName: 'toolbar-label_align',
        text: 'Label Align'.locale(), 
        type: 'menu',
        values:[
            {text: 'Left Aligned'.locale(), value:'Left', icon:'images/blank.gif', iconClassName: 'toolbar-label_left'}, 
            {text: 'Right Aligned'.locale(), value:'Right', icon:'images/blank.gif', iconClassName: 'toolbar-label_right'}, 
            {text: 'Top Aligned'.locale(), value:'Top', icon:'images/blank.gif', iconClassName: 'toolbar-label_top'}
        ]
    },
    special:     { text: 'Special'.locale(), type:'dropdown', values:special_options.getByType('dropdown') },
    labelWidth:  { icon: 'images/blank.gif', iconClassName: 'toolbar-label_width', text:'Label Width'.locale(), type:'spinner', size:10},
    formWidth:   { icon: 'images/blank.gif', iconClassName:'toolbar-form_width', text:'Form Width'.locale(), type:'spinner', size:10},
    allowMinus:  { icon: 'images/blank.gif', iconClassName:'toolbar-negatives', text:'Negatives'.locale(), type:'toggle', values:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]]},
    maxsize:     { icon: 'images/blank.gif', iconClassName: 'toolbar-max_size', text:'Max Size'.locale(), type:'spinner', size:10},
    entryLimit:  { icon: 'images/blank.gif', iconClassName: 'toolbar-max_size', text:'Entry Limit'.locale()},
    maxFileSize: { icon: 'images/blank.gif', iconClassName: 'toolbar-max_size', text:'Max File Size'.locale(), hint:'Enter a value in KB such as 1024', type:'spinner', size: 15 },
    maxValue:    { icon: 'images/blank.gif', iconClassName: 'toolbar-max_value', text:'Max Value'.locale(), type:'spinner', size:10},
    minValue:    { icon: 'images/blank.gif', iconClassName: 'toolbar-min_value', text:'Min Value'.locale(), type:'spinner', size:10},
    width:       { icon: 'images/blank.gif', iconClassName: 'toolbar-width', text:'Width'.locale(), type:'spinner', size:10},
    height:      { icon: 'images/blank.gif', iconClassName:'toolbar-height', text:'Height'.locale(), type:'spinner', size:10},
    addAmount:   { icon: 'images/blank.gif', iconClassName:'toolbar-stepping' , text:'Stepping'.locale(), type:'spinner', size:10},
    selected:    { text: 'Selected'.locale(), type:'dropdown'},
    showTotal:   { icon: 'images/blank.gif', iconClassName: 'toolbar-payment_total', text:'Show Total'.locale(), type:'toggle', values:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
    allowTime:   { text: 'Allow Time'.locale(), type:'toggle', values:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
    dropdown:    { icon: 'images/toolbar/options.png', text:'Options'.locale(), type:'textarea', hint:'Separate the options with new line' },
    options:     { text: 'Options'.locale(), type:'textarea', hint:'Separate the options with new line' },
    items:       { icon: 'images/blank.gif', iconClassName: 'toolbar-autocomplete_options', text:'Items'.locale(), type:'textarea', hint:'Separate the options with new line' },
    spreadCols:  { text: 'Spread Columns'.locale(), type:'spinner', size:10},
    allowOther:  { text: 'Allow Other'.locale(), type:'toggle', values:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
    currency:    { icon: 'images/blank.gif', iconClassName: 'toolbar-currency', text:'Currency'.locale(), type:'dropdown'},
    print:       { icon: 'images/blank.gif', iconClassName: 'toolbar-button_print', text:'Print Button'.locale(), type:'toggle', values:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
    clear:       { icon: 'images/blank.gif', iconClassName: 'toolbar-button_clear', text:'Reset Button'.locale(), type:'toggle', values:[['No', 'No'.locale()], ['Yes', 'Yes'.locale()]] },
    headerType:  { icon: 'images/blank.gif', iconClassName: 'toolbar-header_size' ,text:'Heading Size'.locale(), type:'menu', values:[
	   {value:'Default', text:'Default'.locale()}, {value:'Large', text:'Large'.locale()}, {value:'Small', text:'Small'.locale()}] 
	},
    subHeader:   { icon: 'images/blank.gif', iconClassName: 'toolbar-sub_header', text:'Sub Heading'.locale(), size: 30},
    labelAlign:  { 
        icon: 'images/blank.gif',
        iconClassName: 'toolbar-label_align',
        text: 'Label Align'.locale(), 
        type:'menu',
        values:[
            {text:'Auto'.locale(), value:'Auto', icon:'images/blank.gif', iconClassName: 'toolbar-label_auto'}, 
            {text:'Left'.locale(), value:'Left', icon:'images/blank.gif', iconClassName: 'toolbar-label_left'}, 
            {text:'Right'.locale(), value:'Right', icon:'images/blank.gif', iconClassName: 'toolbar-label_right'}, 
            {text:'Top'.locale(), value:'Top', icon:'images/blank.gif', iconClassName: 'toolbar-label_top'}
        ]
    },
    buttonAlign:  { 
        icon: 'images/blank.gif',
        iconClassName: 'toolbar-button_align',
        text: 'Button Align'.locale(), 
        type:'menu',
        values:[
            {text:'Auto'.locale(), value:'Auto', icon:'images/blank.gif', iconClassName:'toolbar-label_auto' },
            {text:'Left'.locale(), value:'Left', icon:'images/blank.gif', iconClassName: 'toolbar-label_left'}, 
            {text:'Center'.locale(), value:'Center', icon:'images/blank.gif', iconClassName:'toolbar-label_center'}, 
            {text:'Right'.locale(), value:'Right', icon:'images/blank.gif', iconClassName: 'toolbar-label_right'}
        ]
    },
    align:  { 
        icon: 'images/blank.gif',
        iconClassName: 'toolbar-button_align',
        text: 'Align'.locale(), 
        type:'menu',
        values:[
            {text:'Left'.locale(), value:'Left', icon:'images/blank.gif', iconClassName:'toolbar-label_left'}, 
            {text:'Center'.locale(), value:'Center', icon:'images/blank.gif', iconClassName:'toolbar-label_center'}, 
            {text:'Right'.locale(), value:'Right', icon:'images/blank.gif', iconClassName: 'toolbar-label_right'}
        ]
    },
    styles: {
        icon: 'images/blank.gif',
        iconClassName: 'toolbar-themes',
        text:'Themes'.locale(),
        type:'handler', 
        handler:openStyleMenu
    }
};

/**
 * ToolTip messagaes for specific items
 * title: {string} Title for the tooltip
 * tip: {string} Content of the tooltip
 */
var tips = {
    addAmount:      {title:'Stepping'.locale(),             tip:'Defines increase/descrease amount'.locale()},
    alignment:      {title:'Alignment'.locale(),            tip:'Align questions and answers'.locale() },
    allowMinus:     {title:'Allow Negatives'.locale(),      tip:'Allows user to select or enter negative values'.locale()},
    allowOther:     {title:'Allow Other'.locale(),          tip:'Let users type a text'.locale() },
    allowTime:      {title:'Allow Time'.locale(),           tip:'Enable/Disable time section'.locale()},
    background:     {title:'Background'.locale(),           tip:'Change form background color'.locale() },
    boxAlign:       {title:'Box Align'.locale(),            tip:'Define where boxes should be located'.locale()},
    buttonAlign:    {title:'Button Align'.locale(),         tip:'Align submit button to left, center or right'.locale() },
    clear:          {title:'Reset Button'.locale(),         tip:'Show a clear button or not.'.locale() },
    cols:           {title:'Columns'.locale(),              tip:'Width of textarea'.locale() },
    cond:           {title:'Conditions'.locale(),           tip:'Setup Conditional Fields'.locale() },
    defaultFrom:    {title:'Default From'.locale(),         tip:'Set a predefined value'.locale()},
    defaultTime:    {title:'Default Time'.locale(),         tip:'Should we pre-populate date field with current time'.locale()},
    defaultTo:      {title:'Default To'.locale(),           tip:'Set a predefined value'.locale()},
    defaultValue:   {title:'Default Value'.locale(),        tip:'Pre-populate a value'.locale() },
    description:    {title:'Hover Text'.locale(),           tip:'Show description about question'.locale() },
    emails:         {title:'Emails'.locale(),               tip:'Send notification and confirmation emails on submissions'.locale() },
    entryLimit:     {title:'Entry Limit'.locale(),          tip:'Set this value from toolbar.'.locale() },
    extensions:     {title:'Extensions'.locale(),           tip:'Allowed file types'.locale() },
    font:           {title:'Font'.locale(),                 tip:'Change font style'.locale() },
    fontcolor:      {title:'Font Color'.locale(),           tip:'Change font color'.locale() },
    fontsize:       {title:'Font'.locale(),                 tip:'Change font size'.locale() },
    formStrings:    {title:'Form Warnings'.locale(),        tip:'Change warning messages on your form validations.'.locale() },
    formWidth:      {title:'Form Width'.locale(),           tip:'Resize form width'.locale() },
    format:         {title:'Date Format'.locale(),          tip:'Set a format for date: EU or US'.locale() },
    fromText:       {title:'Best Text'.locale(),            tip:'Change the starting point text'.locale()},
    headerType:     {title:'Heading Type'.locale(),         tip:'Size of heading'.locale() },
    highlightLine:  {title:'Hightlight Effect'.locale(),    tip:'Yellow background effect on focused inputs'.locale() },
    hint:           {title:'Input Hint'.locale(),           tip:'Show an example in gray'.locale() },
    injectCSS:      {title:'Inject Custom CSS'.locale(),    tip:'<br>'+'Add your own CSS code to your form. You can change every aspect of the form by using CSS codes. For example:'.locale()+'<br><pre><code>.form-line-active {\n  background:lightblue;\n  color:#000000;\n}\n</code></pre>'+'will change the selected line\'s background color on the live form.'.locale()+'<br><br>'+'Using Firebug or similar tools will help you identify class names and defined styles.'.locale()},
    inputType:      {title:'Input Type'.locale(),           tip:'Define input method for matrix'.locale()},
    items:          {title:'Items'.locale(),                tip:'Words for auto completion'.locale() },
    labelAlign:     {title:'Label Align'.locale(),          tip:'Align question label'.locale() },
    labelWidth:     {title:'Label Width'.locale(),          tip:'Resize question label width'.locale() },
    lineSpacing:    {title:'Question Spacing'.locale(),     tip:'Distance between questions.'.locale() },
    maxFileSize:    {title:'Max File Size'.locale(),        tip:'Maximum file size allowed. Defined as Kilo Bytes. Keep in mind that 1024 KB equals to 1MB'.locale()},
    maxValue:       {title:'Maximum Value'.locale(),        tip:'When you set this value, it won\'t let users to select more than this number'.locale()},
    maxsize:        {title:'Max Size'.locale(),             tip:'Maximum allowed characters for this field'.locale() },
    mcolumns:       {title:'Columns'.locale(),              tip:'Labels at the top of the matrix'.locale()},
    middle:         {title:'Middle Name'.locale(),          tip:'Ask for middle name'.locale() },
    minValue:       {title:'Minimum Value'.locale(),        tip:'When you set this value, it won\'t let users to select less than this number'.locale()},
    mrows:          {title:'Rows'.locale(),                 tip:'Labels at the left side of the matrix'.locale()},
    options:        {title:'Options'.locale(),              tip:'Users can choose from these options'.locale() },
    prefix:         {title:'Prefix'.locale(),               tip:'Ask for prefix: Mr., Mrs., Dr.'.locale() },
    print:          {title:'Print Button'.locale(),         tip:'Show a print button or not.'.locale() },
    properties:     {title:'Preferences'.locale(),          tip:'Update Form Settings'.locale() },
    required:       {title:'Require'.locale(),              tip:'Require completing question'.locale() },
    rows:           {title:'Rows'.locale(),                 tip:'Number of lines on textarea'.locale() },
    scaleAmount:    {title:'Scale Amount'.locale(),         tip:'Choose how may points used in scale'.locale()},
    selected:       {title:'Selected'.locale(),             tip:'Default selected answer'.locale()},
    selectedCountry:{title:'Country'.locale(),              tip:'Set a country to select by default'.locale() },
    sendpostdata:   {title:'Send Post Data'.locale(),       tip:'HTTP POST data to Thank You Page.'.locale()},
    share:          {title:'Embed Form'.locale(),           tip:'Add form to your website or send to others'.locale() },
    size:           {title:'Size'.locale(),                 tip:'Set number of characters users can enter'.locale() },
    special:        {title:'Special Options'.locale(),      tip:'Collection of predefined values to be used on your form. Such as <u>Countries</u>.'.locale()},
    spreadCols:     {title:'Spread To Columns'.locale(),    tip:'Spread inputs into multiple columns. Useful if you have lots of options.'.locale()},
    status:         {title:'Form Status'.locale(),          tip:'Enable or Disable Form.'.locale()},
    styles:         {title:'Themes'.locale(),               tip:'Apply nice styles to your form'.locale() },
    subHeader:      {title:'Sub Header'.locale(),           tip:'Text below heading'.locale() },
    subLabel:       {title:'Sub Label'.locale(),            tip:'Small description below the input field'.locale() },
    suffix:         {title:'Suffix'.locale(),               tip:'Ask for suffix: Ph.D, M.D., Jr, VII'.locale() },
    text:           {title:'Text'.locale(),                 tip:'Label of your question'.locale() },
    thankurl:       {title:'Thank You URL'.locale(),        tip:'Redirect user to a page after submission'.locale() },
    timeFormat:     {title:'Time Format'.locale(),          tip:'Choose 12 Hours or 24 Hours format'.locale()},
    title:          {title:'Form Title'.locale(),           tip:'A short descriptive name for this form'.locale() },
    toText:         {title:'Worst Text'.locale(),           tip:'Change the ending point text'.locale()},
    total:          {title:'Total'.locale(),                tip:'Values must be totaled to this value'.locale()},
    unique:         {title:'Unique Submission'.locale(),    tip:'Use Cookies(Loose Check) or IP Address(Strict Check) to prevent multiple submissions.'.locale()},
    useImage:       {title:'Button Image'.locale(),         tip:'Use an image instead of button'.locale() },
    validation:     {title:'Validation'.locale(),           tip:'Validate entry format'.locale() },
    visibility:     {title:'Visibility'.locale(),           tip:'Hide or show collapse field'.locale()},
    currentIndex:   {title:'Current Index'.locale(),        tip:'Shows the current index. You can change this value to make IDs start from 1000'.locale() },
    idPrefix:       {title:'Prefix'.locale(),               tip:'Add a prefix to your ID such as WD-1005'.locale() },
    idPadding:      {title:'Padding'.locale(),              tip:'Pad your ID to left with zeros such as 000015'.locale() }
};


var control_tooltips = {
    "control_text":        {tip:'Add HTML text into your form.'.locale() },
    "control_head":        {tip:'Add headings to indicate what the section below is about.'.locale() },
    "control_textbox":     {tip:'Text inputs to add single line of text .'.locale() },
    "control_textarea":    {tip:'When you need a longer text entry.'.locale() },
    "control_dropdown":    {tip:'Let users select one of the options'.locale() },
    "control_checkbox":    {tip:'Allows multiple selections.'.locale() },
    "control_radio":       {tip:'Allows user to choose only one option.'.locale() },
    "control_datetime":    {tip:'Ask date and time values in your form.'.locale() },
    "control_fileupload":  {tip:'Let users send you photos or any other kind of files.'.locale() },
    "control_button":      {tip:'Users click on buttons to submit a completed form.'.locale() },
    "control_passwordbox": {tip:'Use this to ask for passwords in your form.'.locale() },
    "control_hidden":      {tip:'Hidden field is not seen but submitted with the form nonetheless.'.locale() },
    "control_autoincrement":{tip:'Add an incremental ID to your form. Which will be associated with the submission later.'.locale() },
    "control_image":       {tip:'Add an image for your form. ie. Your company logo'.locale() },
    "control_captcha":     {tip:'Captcha prevents spam submissions.'.locale() },
    "control_autocomp":    {tip:'Helps user to pick the exact value.'.locale() },
    "control_rating":      {tip:'When you want to receive ratings, as stars, lightbulbs etc.'.locale() },
    "control_scale":       {tip:'Allows user to give points based on a scale.'.locale() },
    "control_slider":      {tip:'Allows user to visually select a number within a range.'.locale() },
    "control_spinner":     {tip:'Makes it easier to submit numbers.'.locale() },
    "control_range":       {tip:'Allow user to define a number range.'.locale() },
    "control_grading":     {tip:'Allows user to select a total value into options as a grade.'.locale() },
    "control_matrix":      {tip:'Allows users to select multiple values for multiple options.'.locale() },
    "control_collapse":    {tip:'Hides a section of the form. Split your form into expandable parts'.locale() },
    "control_pagebreak":   {tip:'Splits your form into pages. Works best with a heading.'.locale() },
    "control_fullname":    {tip:'Makes sure user enters his/her name in a specified format.'.locale() },
    "control_address":     {tip:'Allows users to enter their address in a correct format.'.locale() },
    "control_email":       {tip:'Helps users to enter their e-mail correctly.'.locale() },
    "control_number":      {tip:'Helps users to enter a number correctly.'.locale() },
    "control_phone":       {tip:'Helps users to enter a phone number in a correct format.'.locale() },
    "control_birthdate":   {tip:'Helps users to pick any date easily.'.locale() },
    "control_payment":     {tip:'Create offline payment option.'.locale(), image:'control_payment.png' },
    "control_paypal":      {tip:'Collect payments through PayPal.'.locale(), image:'control_payment.png' },
    "control_paypalpro":   {tip:'Collect payments through credit cards with PayPal Pro.'.locale(), image:'control_payment.png' },
    "control_authnet":     {tip:'Collect payments through credit cards with Authorize.Net.'.locale(), image:'control_payment.png' },
    "control_googleco":    {tip:'Collect payments through Google Checkout.'.locale(), image:'control_payment.png' },
    "control_onebip":      {tip:'Collect payments through mobile phones with 1Bip.'.locale(), image:'control_payment.png' },
    "control_worldpay":    {tip:'Collect payments through WorldPay.'.locale(), image:'control_payment.png' },
    "control_2co":         {tip:'Collect payments through 2CheckOut.'.locale(), image:'control_payment.png' },
    "control_clickbank":   {tip:'Collect payments through ClickBank.'.locale(), image:'control_payment.png' }
};
