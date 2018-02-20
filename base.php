<?php
/** Author: Jon Scherdin */

if (strpos($_SERVER['HTTP_HOST'], 'redcap.vanderbilt.edu') !== false) {
	$grantsProjectId = 27635;
	$userProjectId = 27636;
}
else if (strpos($_SERVER['HTTP_HOST'], 'redcaptest.vanderbilt.edu') !== false) {
	$grantsProjectId = 266;
	$userProjectId = 265;
}
else {
	$grantsProjectId = 242;
	$userProjectId = 243;
}

require_once("../../redcap_connect.php");