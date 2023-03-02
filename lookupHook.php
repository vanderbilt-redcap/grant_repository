<?php

use \Vanderbilt\GrantRepositoryLibrary\LDAP;

require_once(dirname(__FILE__)."/classes/Autoload.php");
require_once(__DIR__."/base.php");

$data = \REDCap::getData($project_id, "json-array", [$record]);

$upload = [];
foreach ($data as $row) {
	if (!$row['first_name'] || !$row['last_name']) {
		$userid = sanitize($row['vunet_id']);
        $userid = ldap_escape($userid);
		list($first, $last) = preg_split("/\s+/", LDAP::getName($userid));
        $first = sanitize($first);
        $last = sanitize($last);
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
