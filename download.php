<?php
/** Author: Jon Scherdin */
# verify user access
if (!isset($_COOKIE['grant_repo'])) {
	header("Location: index.php");
}

require_once("base.php");

// If ID is not in query_string, then return error
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) exit("{$lang['global_01']}!");

// need to set the project id since we are using a different variable name
if (!isset($_GET['p']) || !is_numeric($_GET['p'])) exit("{$lang['global_01']}!");
$project_id = $_GET['p'];
define("PROJECT_ID", $project_id);

//Download file from the "edocs" web server directory
$sql = "select * from redcap_edocs_metadata where project_id = $project_id and doc_id = ".$_GET['id'];
$q = db_query($sql);
if (!db_num_rows($q)) {
	die("<b>{$lang['global_01']}:</b> {$lang['file_download_03']}");
}
$this_file = db_fetch_array($q);

$basename = preg_replace("/\.[^\.]*$/", "", $this_file['stored_name']);
if (!preg_match("/\/$/", $basename)) {
	$basename.= "/";
}
$outDir = APP_PATH_TEMP.$basename;
mkdir($outDir);

$files = array();
if ($this_file['mime_type'] == "application/x-zip-compressed") {
	$zip = new ZipArchive;
	$res = $zip->open(EDOC_PATH.$this_file['stored_name']);
	if ($res) {
		$zip->extractTo($outDir);
		$zip->close();

		$allFiles = scandir($outDir);
		$skip = array(".", "..");
		foreach ($allFiles as $filename) {
			if (!in_array($filename, $skip)) {
				array_push($files, $filename);
			}
		}
	}
} else {
	$fpIn = fopen(EDOC_PATH.$this_file['stored_name'], "r");
	$fpOut = fopen($outDir.$this_file['doc_name'], "w");
	while ($line = fgets($fpIn)) {
		fwrite($fpOut, $line);
	}
	fclose($fpIn);
	fclose($fpOut);
	$files = array($this_file['doc_name']);
}

if (!empty($files)) {
	echo "<h1>Files (".count($files).")</h1>\n";
	foreach ($files as $filename) {
		echo "<p><a href='downloadFile.php?f=".urlencode($basename.$filename)."'>$filename</a></p>\n";
	}
	exit();
} else {
	echo "<p>No files have been provided.</p>";
}
