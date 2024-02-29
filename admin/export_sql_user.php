<?php
    include_once "../lib/init.php";
    
    if (!isset($_POST['fetchType']) || $_POST['fetchType'] !== "file" ){
	?>
	<form method="post">
		<input type="field" name="username"  />
		<select name="fetchType">
			<option value="textarea">to textarea</option>
			<option value="file">to file</option>
		</select>
		<input type="submit" value="Export SQL" />
	</form>
	<?php
    }
	if (isset($_POST["username"]) && trim(''.$_POST["username"])){
		$sqlUser = new ExportSQLUser($_POST["username"]);
		$queries = $sqlUser->getQueries();
		$value = implode($queries);
		if (isset($_POST['fetchType']) && $_POST['fetchType'] === "textarea" ) {
			?> 
			<textarea style="width:600px;height:500px;"><?=htmlspecialchars ($value)?></textarea>
			<?php
		}else{
			header("Content-type:plain/text; encoding=utf-8");
			header("Content-Disposition: attachment; filename=\"".$_POST["username"].".sql\"");
			echo $value; 
		}
	}

	