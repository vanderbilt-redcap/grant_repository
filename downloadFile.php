<?php
/** Author: Jon Scherdin */
# verify user access
if (!isset($_COOKIE['grant_repo'])) {
	header("Location: index.php");
}

require_once("base.php");

$dieMssg = "Improper filename";
if (!isset($_GET['f']) || preg_match("/\.\./", $_GET['f'])) {
	die($dieMssg);
}
$filename = APP_PATH_TEMP.$_GET['f'];
if (!file_exists($filename)) {
	die($dieMssg);
}

if (preg_match("/\.doc$/i", $filename) || preg_match("/\.docx$/i", $filename)) {
	# Word doc
} else if (preg_match("/\.csv$/i", $filename)) {
	# CSV
} else if (preg_match("/\.xls$/i", $filename) || preg_match("/\.xlsx$/i", $filename)) {
	# Excel
} else if (preg_match("/\.pdf$/i", $filename)) {
	# PDF
} else if (preg_match("/\.msg$/i", $filename)) {
	# Outlook Msg
} else {
	readfile($filename);
}
