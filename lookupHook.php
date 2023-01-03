<?php

use \Vanderbilt\GrantRepositoryLibrary\LDAP;

require_once(dirname(__FILE__)."/classes/Autoload.php");

$json = \REDCap::getData($project_id, "json", [$record]);
$data = json_decode($json, TRUE);

$upload = [];
foreach ($data as $row) {
	if (!$row['first_name'] || !$row['last_name']) {
		$userid = $row['vunet_id'];
		list($first, $last) = preg_split("/\s+/", LDAP::getName($userid));
		if ($first && $last) {
			$upload[] = [
				"vunet_id" => $userid,
				"first_name" => $first,
				"last_name" => $last,
			];
		}
	}
}
if (!empty($upload)) {
	$json = json_encode($upload);
	\REDCap::saveData($project_id, "json", $json);
}