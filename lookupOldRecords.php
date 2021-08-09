<?php

use \Vanderbilt\GrantRepostoryLibrary\LDAP;

require_once(dirname(__FILE__)."/base.php");

$json = \REDCap::getData($userProjectId, "json");
$data = json_decode($json, TRUE);

$upload = [];
foreach ($data as $row) {
	if (!$row['first_name'] || !$row['last_name']) {
		$userid = $row['vunet_id'];
		list($last, $first) = LDAP::getName($userid);
		$upload[] = [
			"vunet_id" => $userid,
			"first_name" => $first,
			"last_name" => $last,
		];
	}
}
if (!empty($upload)) {
	$json = json_encode($upload);
	\REDCap::saveData($userProjectId, "json", $json);
}
