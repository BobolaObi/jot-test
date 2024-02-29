/**
 * English locale file. This locale will only include those sentences that 
 * are long and thus which have IDs and not complete sentences. 
 * This locale is also the fallback where words and sentences that
 * have not been translated yet are served from here.
 * 
 */

// Using namespace Locale. Done because of the clash with codepress library.
var Locale = Locale || {};
Locale.language = {};
Locale.languageEn = {
    "langCode": "en-US",
	"[email_from]": "From:",
	"[email_to]": "To:",
	"[email_subject]": "Subject:",
	"[email_body]": "Body:"
};