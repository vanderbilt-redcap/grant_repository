<?php

use \Vanderbilt\GrantRepositoryLibrary\LDAP;

require_once(dirname(__FILE__)."/base.php");
require_once(dirname(__FILE__)."/classes/Autoload.php");

$data = \REDCap::getData($userProjectId, "json-array");

$upload = [];
foreach ($data as $row) {
	if (!$row['first_name'] || !$row['last_name']) {
		$userid = sanitize($row['vunet_id']);
		list($first, $last) = preg_split("/\s+/", LDAP::getName($userid));
		if ($first && $last) {
            $first = sanitize($first);
            $last = sanitize($last);
			$upload[] = [
				"vunet_id" => $userid,
				"first_name" => $first,
				"last_name" => $last,
			];
			echo "Adding $first $last for $userid<br>";
		} else {
			echo "Could not find name for $userid<br>";
		}
	}
}
if (!empty($upload)) {
	$json = json_encode($upload);
	\REDCap::saveData($userProjectId, "json", $json);
}
