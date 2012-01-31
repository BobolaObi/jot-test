/**
 * JS code for the Ruckusing page, helping managing the DB.
 * 
 */
var Utils = new Common();
(function(){
	var migrationName = "";
	// Sorry for the disgusting heredoc syntax.
	var migrationTemplate = "<"+"?php\n\n"+
		"class RuckusingTestClass extends Ruckusing_BaseMigration {\n"+
		"    public function up() {\n"+
        
        "        $this->execute(\"\");\n"+
        
		"    } //up()\n\n"+
        
		"    public function down() { \n"+
        
        "        $this->execute(\"\");\n"+
        
		"    } //down()\n"+
		"}\n";
	
	var cpDiv, latestVersionNumber = 10000, userVersionNumber = false;
	var codeEditorOpen = false;
	
	function addMigration() {
		getLatestVersionNumber();
		Utils.prompt("Migration Name", "default_migration" + (latestVersionNumber + 1), "What should be the name of the migration?", 
    		function(value){ 
    			if (value) {
                    saveMigrationButton.enable();
    				migrationName = value;
    				cp1.setCode(migrationTemplate.replace('RuckusingTestClass', migrationName.toCamelCase()));
    				cp1.editor.syntaxHighlight('php');
    				// This is basically what CodePress does when the document is first
    				// loaded.
    				if (!codeEditorOpen) {
    					cpDiv.shift({height: 295});
    					codeEditorOpen = true;
    				}
    			}
    		}
        );
	}
	
	function cancelMigration() {
		if (codeEditorOpen) {
			cpDiv.shift({height: 0});
			codeEditorOpen = false;
		}
	}
	
	function saveMigration() {
		cp1.editor.syntaxHighlight('php');
		var toServer = {
			action: "saveMigration",
			name: migrationName,
			code: cp1.getCode()
		};
		
		saveMigrationButton.disable();

        Utils.Request({
            parameters: toServer,
            onComplete: function(response) {
                echoOutput(response.error? response.error : response.message + "<br>" + "<br>");
            }
        });

	}
	
	function migrateLatest() {
		var toServer = {
				action: "migrateLatest"
		};
		cancelMigration();
		migrateLatestButton.disable();
		Utils.Request({
            parameters: toServer,
            onComplete: function(response) {
                echoOutput(response.error? response.error : response.message + "<br>" + "<br>");
                migrateLatestButton.enable();
            }
        });
	}
	
	function echoOutput(msg) {
		responseDiv.insert(msg.replace(/\n/g, "<br>"));
		responseDiv.insert("<br>--------------------------------------------<br><br><br>")
	}
	
	function getLatestVersionNumber() {
		var toServer = {
				action: "getLatestVersionNumber"
		};
		Utils.Request({
            parameters: toServer,
            asynchronous: false,
            onSuccess: function(response) {
            	latestVersionNumber = parseInt(response.message);
            }
        });
		
	}
	
	function getUserVersionNumber() {
		getLatestVersionNumber();
		Utils.prompt("Migration Version", latestVersionNumber,
			"Which version would you like to migrate to?", 
			function(value) {
				userVersionNumber = parseInt(value);
				if (userVersionNumber > 0) {
					migrateToVersion();
				}
			}
		);
	}
	
	function migrateToVersion() {
		migrateVersionButton.disable();
		cancelMigration();
		var toServer = {
				action: "migrateToVersion",
				version: userVersionNumber
		};
		
		Utils.Request({
            parameters: toServer,
            onComplete: function(response) {
                echoOutput(response.error? response.error : response.message + "<br>" + "<br>");
                migrateVersionButton.enable();            
            }
        });
		userVersionNumber = false;
	}
	
	document.observe('dom:loaded', function() {
		cpDiv = $('cp1-div');
		responseDiv = $('response-div');
		migrateLatestButton = $('migrate-latest');
		saveMigrationButton = $('save-migration');
		migrateVersionButton = $('migrate-version');
        Element.observe('add-migration', 'click', addMigration);
        Element.observe('save-migration', 'click', saveMigration);
        Element.observe('cancel-migration', 'click', cancelMigration);
        Element.observe('migrate-latest', 'click', migrateLatest);
        Element.observe('migrate-version', 'click', getUserVersionNumber);
    });
})();
