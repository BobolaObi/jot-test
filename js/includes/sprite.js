(function(){
	var Utils = Utils || new Common();
	
	function startCreatingCssSprite() {
        Utils.Request({
            parameters:{
                action:'createCssSprite'
            },
            onSuccess: function(res){
                alert("Completed!");
            },
            onFail: function(res){
                alert(res.error);
            }
        });
	}
	
	document.ready(function(){
        $('create-css-sprite').observe('click', startCreatingCssSprite);
    });
})();