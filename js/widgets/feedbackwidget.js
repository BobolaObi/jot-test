Feedback = {
	formWindow: false,
    iframeEl: false,
	openFeedbackForm: function() {
		if (Feedback.formWindow) {
			Feedback.formWindow.close();
			return;
		}

        if (!Feedback.iframeEl) {
            // Open a window with iframe of the form as child.
            Feedback.iframeEl = new Element('iframe', {src: (Utils.HTTP_URL || "") + "form/1062041021&prev", allowTransparency:'true', frameborder:'0'}).setStyle({
                width:'100%',
                height:'100%',
                border:'none'
            });
        }
        
        this.formWindow = document.window({
            title: "Post Feedback".locale(),
			width: "415px",
			height: Prototype.Browser.IE? "510px" : "490px",
            content: Feedback.iframeEl,
            contentPadding:0,
            onClose: function() {
    			Feedback.formWindow = false;
    		}
        });
    },
    // init: function() {
    //     document.observe('dom:loaded', function() {
    //         Element.observe('feedback-tab-link', 'click', function() {
    //             Feedback.openFeedbackForm();
    //         });
    //         $('feedback-tab').show();
    //     });
    // }
    //

    init: function() {
        document.observe('dom:loaded', function() {
            console.log('DOM is fully loaded');
            Element.observe('feedback-tab-link', 'click', function() {
                console.log('feedback-tab-link was clicked');
                Feedback.openFeedbackForm();
            });
            var feedbackTab = $('feedback-tab');
            if (feedbackTab) {
                console.log('feedback-tab is present');
                feedbackTab.show();
            } else {
                console.log('feedback-tab is missing');
            }
        });
    }
};

Feedback.init();