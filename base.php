<?php
/** Author: Jon Scherdin */

if (strpos($_SERVER['HTTP_HOST'], 'redcap.vanderbilt.edu') !== false) {
    // $grantsProjectId = 27635;
    $grantsProjectId = 165634;
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

function getChoices($metadata) {
	$choicesStrs = array();
	$multis = array("checkbox", "dropdown", "radio");
	foreach ($metadata as $row) {
		if (in_array($row['field_type'], $multis) && $row['select_choices_or_calculations']) {
			$choicesStrs[$row['field_name']] = $row['select_choices_or_calculations'];
		} else if ($row['field_type'] == "yesno") {
			$choicesStrs[$row['field_name']] = "0,No|1,Yes";
		} else if ($row['field_type'] == "truefalse") {
			$choicesStrs[$row['field_name']] = "0,False|1,True";
		}
	}
	$choices = array();
	foreach ($choicesStrs as $fieldName => $choicesStr) {
		$choicePairs = preg_split("/\s*\|\s*/", $choicesStr);
		$choices[$fieldName] = array();
		foreach ($choicePairs as $pair) {
			$a = preg_split("/\s*,\s*/", $pair);
			if (count($a) == 2) {
				$choices[$fieldName][$a[0]] = $a[1];
			} else if (count($a) > 2) {
				$a = preg_split("/,/", $pair);
				$b = array();
				for ($i = 1; $i < count($a); $i++) {
					$b[] = $a[$i];
				}
				$choices[$fieldName][trim($a[0])] = implode(",", $b);
			}
		}
	}
	return $choices;
}

