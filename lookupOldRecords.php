<?php

use \Vanderbilt\GrantRepostoryLibrary\LDAP;

require_once(dirname(__FILE__)."/base.php");
require_once(dirname(__FILE__)."/classes/Autoload.php");

$json = \REDCap::getData($userProjectId, "json");
$data = json_decode($json, TRUE);

$upload = [];
foreach ($data as $row) {
	if (!$row['first_name'] || !$row['last_name']) {
		$userid = $row['vunet_id'];
		list($first, $last) = LDAP::getName($userid);
		if ($first && $last) {
			$upload[] = [
				"vunet_id" => $userid,
				"first_name" => $first,
				"last_name" => $last,
			];
			echo "Adding $first $last for $userid<br>";
		}
	}
}
if (!empty($upload)) {
	$json = json_encode($upload);
	\REDCap::saveData($userProjectId, "json", $json);
}
