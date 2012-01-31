<?

# strings that explain the meaining in english so that they can be translated more easily
$override = array(
                "Power Tools"   =>      "Advanced Tools",
                "Font"          =>      "Font Type",
                "Font Family"           =>      "Font Type",
                "DateTime"      =>      "Date and Time",
		"Drop Down"	=>	"Dropdown menu",
		"Auto Saved"	=>	"Save",
		"Saved"		=>	"Save",
		"Compose"		=>	"Content",
		"Compose Email"		=>	"E-mail Content",
		"Finish"		=>	"Complete",
		"Disabled"		=>	"Disable",
		"After form submission, the user should be taken to"	=>	"",
		"the default Thank You page."	=>	"Default",
		"Close Themes"		=>	"Themes",
		"Flip"		=>	"Other",
		"Your Form URL"	=>	"Form URL",
		"Paste this code on your web site"	=>	"Paste this code",
		"Hide Advanced"	=>	"Back",
		"Conditions Wizard"	=>	"Conditions",
		"Continue"	=>	"Next",
		"Clone an existing form"	=>	"My Forms",
		"Forward Submission"	=>	"Email",
		"Forward"	=>	"Email",
		"[email_from]"	=>	"Sender",
		"[email_to]"	=>	"Receiver",
		"[email_subject]"	=>	"Subject",
		"Search In Submissions"	=>	"Search",
		"You have no reports created for this form."	=>	"Create your first report!",
		"Save Report"	=>	"Save",
		"Print Report"	=>	"Print",
		"Edit Report"	=>	"Edit",
		"No crippleware. No ads."	=>	"No ads",
		"Watch Movie"	=>	"Movie"

		
		

);

include "manual/original_tr";
include "manual/original_it";
include "manual/original_nl";
include "manual/original_es";
include "manual/original_ct";
include "manual/original_de";
include "manual/original_pt";
include "manual/original_sp";

$lang_override2 = array(

		'tr-TR'	=>	$turkish,
		'it-IT'	=>	$italian,
		'nl-NL'	=>	$dutch,
		'es-MX' =>	$spanish,
		'es-ES' =>	$spanish1,
		'ca-ES' =>	$catalan,
		'de-DE'	=>	$german,
		'pt-PT'	=>	$portugese

);
#print_r($lang_override2);


$run_lang = array(
			'es-ES'	=>	'es',
			'fr-FR'	=>	'fr',
			'it-IT'	=>	'it',
			'pt-PT'	=>	'pt',
			'de-DE'	=>	'de',
			'tr-TR'	=>	'tr',
			'ca-ES'	=>	'ca',
			'nl-NL'	=>	'nl',
			'sv-SE'	=>	'sv',
			'hu-HU'	=>	'hu',
			'no-NO' =>	'no',
			'da-DA'	=>	'da',
			'ro-RO'	=>	'ro'
);


# remove any entries that contain these:
$ignore_list = array(
                        "%s No Condition Here? %s Would you like to %s add a new one? %s"       => 1,
                        "%s %s" => 1,
                        " %s %s"        => 1,
                        'Also %s more..'        => 1,
                        '.' => 1,
                        ' ' => 1,
                        '' => 1,
			'USD'=> 1,
			'GBP'=> 1,
			'EUR'=> 1,
			'%s/%s (%s%)' => 1,
        );


$all_languages = array(
  'AFRIKAANS' => 'af',
  'ALBANIAN' => 'sq',
  'AMHARIC' => 'am',
  'ARABIC' => 'ar',
  'ARMENIAN' => 'hy',
  'AZERBAIJANI' => 'az',
  'BASQUE' => 'eu',
  'BELARUSIAN' => 'be',
  'BENGALI' => 'bn',
  'BIHARI' => 'bh',
  'BULGARIAN' => 'bg',
  'BURMESE' => 'my',
  'CATALAN' => 'ca',
  'CHEROKEE' => 'chr',
  'CHINESE' => 'zh',
  'CHINESE_SIMPLIFIED' => 'zh-CN',
  'CHINESE_TRADITIONAL' => 'zh-TW',
  'CROATIAN' => 'hr',
  'CZECH' => 'cs',
  'DANISH' => 'da',
  'DHIVEHI' => 'dv',
  'DUTCH'=> 'nl',  
  'ENGLISH' => 'en',
  'ESPERANTO' => 'eo',
  'ESTONIAN' => 'et',
  'FILIPINO' => 'tl',
  'FINNISH' => 'fi',
  'FRENCH' => 'fr',
  'GALICIAN' => 'gl',
  'GEORGIAN' => 'ka',
  'GERMAN' => 'de',
  'GREEK' => 'el',
  'GUARANI' => 'gn',
  'GUJARATI' => 'gu',
  'HEBREW' => 'iw',
  'HINDI' => 'hi',
  'HUNGARIAN' => 'hu',
  'ICELANDIC' => 'is',
  'INDONESIAN' => 'id',
  'INUKTITUT' => 'iu',
  'IRISH' => 'ga',
  'ITALIAN' => 'it',
  'JAPANESE' => 'ja',
  'KANNADA' => 'kn',
  'KAZAKH' => 'kk',
  'KHMER' => 'km',
  'KOREAN' => 'ko',
  'KURDISH'=> 'ku',
  'KYRGYZ'=> 'ky',
  'LAOTHIAN'=> 'lo',
  'LATVIAN' => 'lv',
  'LITHUANIAN' => 'lt',
  'MACEDONIAN' => 'mk',
  'MALAY' => 'ms',
  'MALAYALAM' => 'ml',
  'MALTESE' => 'mt',
  'MARATHI' => 'mr',
  'MONGOLIAN' => 'mn',
  'NEPALI' => 'ne',
  'NORWEGIAN' => 'no',
  'ORIYA' => 'or',
  'PASHTO' => 'ps',
  'PERSIAN' => 'fa',
  'POLISH' => 'pl',
  'PORTUGUESE' => 'pt-PT',
  'PUNJABI' => 'pa',
  'ROMANIAN' => 'ro',
  'RUSSIAN' => 'ru',
  'SANSKRIT' => 'sa',
  'SERBIAN' => 'sr',
  'SINDHI' => 'sd',
  'SINHALESE' => 'si',
  'SLOVAK' => 'sk',
  'SLOVENIAN' => 'sl',
  'SPANISH' => 'es',
  'SWAHILI' => 'sw',
  'SWEDISH' => 'sv',
  'TAJIK' => 'tg',
  'TAMIL' => 'ta',
  'TAGALOG' => 'tl',
  'TELUGU' => 'te',
  'THAI' => 'th',
  'TIBETAN' => 'bo',
  'TURKISH' => 'tr',
  'UKRAINIAN' => 'uk',
  'URDU' => 'ur',
  'UZBEK' => 'uz',
  'UIGHUR' => 'ug',
  'VIETNAMESE' => 'vi',
  'WELSH' => 'cy',
  'YIDDISH' => 'yi'
);




$browse = array(
		"index.php",
		"js/common.js",
		"js/protoplus-ui.js",
		"js/builder/condition_wizard.js",
		"js/builder/email_wizard.js",
		"js/builder/formBuilder.js",
		"js/builder/logic_wizard.js",
		"js/builder/newform_wizard.js",
		"js/builder/payment_wizard.js",
		"js/builder/question_definitions.js",
		"js/builder/question_properties.js",
		"js/builder/redirect_wizard.js",
		"js/builder/share_wizard.js",
                "js/includes/loginForm.js",
                "js/includes/myaccount.js",
                #"js/includes/myforms.js",
                "js/includes/myforms2.js",
                "js/includes/oldmyforms.js",
                "js/includes/password_reset.js",
		"js/includes/pending_wizard.js",
                "js/includes/reports.js",
                "js/includes/reports_wizard.js",
                "js/includes/signup.js",
                "js/includes/submissions.js",
                "lib/includes/accountInfo.php",
                "lib/includes/footerLinks.php",
                "lib/includes/footer_content.php",
                "lib/includes/login.php",
                "lib/includes/loginForm.php",
                "lib/includes/myaccount.php",
                #"lib/includes/myforms.php",
                "lib/includes/myforms2.php",
                "lib/includes/oldmyforms.php",
                "lib/includes/navigation.php",
                "lib/includes/notfound.php",
                "lib/includes/pass_reset_expired.php",
                "lib/includes/password_reset.php",
                "lib/includes/reports.php",
                "lib/includes/signup.php",
                "lib/includes/submissions.php",
                "wizards/conditionWizards.html",
                "wizards/emailWizard.html",
                "wizards/logicWizard.html",
                "wizards/newformWizard.html",
                "wizards/paymentWizards.html",
                "wizards/pendingWizard.html",
                "wizards/redirectWizards.html",
                "wizards/shareWizard.html",
		"wizards/reportWizard.html",
                "wizards/testfeedbackbutton.php",
		);


?>
