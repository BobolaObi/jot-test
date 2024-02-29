Language Files and Other Related Stuff

xml files contain translations from the database at ying.interlogy.
The translations were made from the JotForm v2 interface. It has some cleaning
 up to do, and I did some cleanup for Spanish file.

You are supposed to run xml2js file so that locale_language-COUNTRY.js files 
are generated. locale_lan-COUNTRY.js files are used by form builder to provide 
the user interface in user's own language.

Also, server side PHP file which determines which locale*.js file is to be 
included is the lib/locale.php file. It first checks the DB to see if the 
user has specified a language, if not it uses the browser's accept header to 
serve the right js file.