var progress_bar;
document.observe('dom:loaded', function() {
	$('formID').observe('keyup', function(e) {
		if (e.keyCode == 13) {
			getPhishingForm();
		}
	});
	$('formID').hint('Form ID');
	if (pre_open != "no") {
		$('formID').focus();
		$('formID').value = pre_open;
		getPhishingForm(pre_open);
	} else {
		submitFormSpam();
	}
});

function handleForm(transport) {
	var previousUsername = username;
	var previousFormID = formID;

	var response = transport.responseText.evalJSON() || {};
	if (response.noform) {
		$('progress_bar').setStyle( {display : 'none'});
		$('formframeurl').src = 'about:blank';
		$('formtitle').innerHTML = "...";
		setBar(-2);
		return;
	}

	username = response.username;
	formID = response.formID;
	formTitle = response.formTitle;
	var premium = response.isPremium;
	isCurrentPremium = premium;
	var premium_text = "";

	spamPercentage = response.spamPercentage;
	status = response.status;
	var translateText = " - <a href='http://translate.google.com/translate?js=n&prev=_t&hl=en&ie=UTF-8&layout=2&eotf=1&sl=auto&tl=en&act=url&u="            
			+ url
			+ "form.php?formID="
			+ formID
			+ "' target='_blank'>Translate</a>";
	//Set formTitle, formID and spamProb
	if (!username || username == "") {
		$('formtitle').update(formTitle + " - Anonymous");
	} else {
		if (premium) {
			premium_text = " This User is <u>Premium</u>";
		}
		$('formtitle')
				.update(
						formTitle
								+ " - <a style=\"color:#333\" href=\"/admin/?keyword=" + formID + "\" target=\"_blank\">" + username + "</a>"
								+ premium_text + " " + translateText);
	}
	$('formframeurl').writeAttribute("src", url + "/form.php?formID=" + formID);
	$('formframeurl').onload = function() {
		$('formframeurl').setStyle( {
			visibility : 'visible'
		});
	};

	setBar(spamPercentage);

	//Stop Progress bar
	$('progress_bar').setStyle( {
		display : 'none'
	});

}

function whitelistUser() {

	var usern = prompt("Enter username to be whitelisted", username);
	if (!usern) {
		alert("Please enter a username");
		return;
	}

	$('whitelist_icon').src = "/images/phishing/loader-big.gif";

	new Ajax.Request("form_provider.php", {
		method:'get',
		parameters : {
			whiteList : usern
		},
		onComplete : function(t) {
			$('whitelist_icon').src = "/images/phishing/whitelist.png";
		}
	});
}

var isCurrentPremium = false;
var formID = null;
var formTitle = null;
var spamPercentage = null;
var username = null;
var status = null;
var is_spam = false;

function submitFormSpam(buttonValue) {

	//Get is_spam value
	if (buttonValue == 'Spam') {
		is_spam = true;

		if (isCurrentPremium) {
			if (!confirm('This forms owner is a Premium User. Are you sure this is a spam?. Please make sure you are not mistaken.')) {
				return false;
			}
		}

	} else if (buttonValue == 'NotSpam') {
		is_spam = false;
	} else if (buttonValue == 'Ignore') {
		is_spam = 'ignore';

		if (status == "DELETED") {
			$('spamButton').disabled = false;
			$('notspamButton').disabled = false;
		}
	}

	//Start progress bar
	$('progress_bar').setStyle( {
		display : 'block'
	});
	$('formframeurl').setStyle( {
		visibility : 'hidden'
	});
	$('formtitle').update('Loading form... Please wait.');
	setBar(-1);

	//Get selected query type value
	if ($('querytype') != null) {
		var selectedQueryType = $('querytype').value;
	} else {
		var selectedQueryType = "Random";
	}

	//Send the request to train the phishing detector
	var options = {
			method : 'get',
			parameters : {
				query_type : selectedQueryType,
				formID : formID,
				formTitle : formTitle,
				is_spam : is_spam
			},
			onSuccess : handleForm,
			onFailure : function() {
				alert('Something went wrong...');
			}
		};
	new Ajax.Request('form_provider.php', options);
}

function whitelistTrain() {

	new Ajax.Request('form_provider.php', {
		method : 'post',
		parameters : {
			action : "whitelist_train"
		},
		onSuccess : function(transport) {
			alert("Spam detector is trained with the whitelisted forms.");
		},
		onFailure : function() {
			alert('Something went wrong...');
		}
	});
}

function showLog(logs) {
	document.window( {
		title : "Suspend Report",
		content : logs,
		resizable : true,
		height : 400,
		modal : false
	});
}
var log = "";
function promptResponse(obj) {

	if (obj.status == "completed") {
		$('suspend_icon').src = "/images/phishing/suspend.png";
		iframe.remove();
		showLog(obj.text);
		log = obj.text;
		$('responseTD').innerHTML = "Completed -- <span style='cursor:pointer' onclick='showLog(log)'><b>Show Log</b></span>";
	} else {
		$('responseTD').innerHTML = obj.text;
	}
}
var iframe = false;
function suspendForms() {

	var spamThreshold = prompt('Please Enter Threshold', '0.99');
	if (spamThreshold<0.98){
		alert("Threshold must be bigger than 0.98");
		return;
	}
	$('suspend_icon').src = "/images/phishing/loader-big.gif";
	iframe = new Element('iframe', {
		src : "form_provider.php?action=suspend_forms&spam_threshold="
				+ spamThreshold
	}).setStyle( {
		display : 'none'
	});
	$(document.body).insert(iframe);

}

function getPhishingForm(form_id) {

	//Start progress bar
	$('progress_bar').setStyle( {
		display : 'block'
	});

	var phishingFormID = form_id ? form_id : $('formID').value;

	new Ajax.Request('form_provider.php', {
		method : 'post',
		parameters : {
			action : 'get_phishing_form',
			formID : phishingFormID
		},
		onSuccess : handleForm,
		onFailure : function() {
			alert('Something went wrong...');
		}
	});
}

function setBar(percent) {
	var noup = false;
	if (percent == -2) {
		percent = 0;
		noup = true;
		$('spamProb').update('No more forms');
	} else if (percent == -1) {
		percent = 0;
		noup = true;
		$('spamProb').update("Calculating...");
	}

	if (percent >= 90) {
		$('pbar-bar').shift( {
			background : '#FF0000',
			width : percent + '%',
			onEnd : function(e) {
				if (percent >= 99) {
					e.shift( {
						background : '#FFFFFF',
						easing : 'pulse',
						easingCustom : 3,
						duration : 2
					});
					$('spamProb').update('Phishing' + ': ' + percent + '%');
				} else {
					$('spamProb').update('Likely' + ': ' + percent + '%');
				}
			}
		});
	} else if (percent >= 80) {
		$('pbar-bar').shift( {
			background : '#FF4500',
			width : percent + '%'
		});
		$('spamProb').update('Maybe' + ': ' + percent + '%');
	} else if (percent >= 70) {
		$('pbar-bar').shift( {
			background : '#FFA500',
			width : percent + '%'
		});
		$('spamProb').update('Not Likely' + ': ' + percent + '%');
	} else if (percent >= 60) {
		$('pbar-bar').shift( {
			background : '#FFE555',
			width : percent + '%'
		});
		$('spamProb').update('Not Likely' + ': ' + percent + '%');
	} else if (percent >= 50) {
		$('pbar-bar').shift( {
			background : '#FFF599',
			width : percent + '%'
		});
		$('spamProb').update('Almost Clean' + ': ' + percent + '%');
	} else {
		$('pbar-bar').shift( {
			background : '#FFFFFF',
			width : percent + '%'
		});
		if (!noup) {
			$('spamProb').update('Good' + ': ' + percent + '%');
		}
	}

}
