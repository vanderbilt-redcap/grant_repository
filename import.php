<?php

require_once("base.php");

$fp = fopen(dirname(__FILE__)."/guide.csv", "r");
$i = 0;
$headers = array();
$lines = array();
while ($line = fgetcsv($fp)) {
	if ($i > 0) {
		array_push($lines, $line);
	} else {
		$headers = $line;
	}
	$i++;
}
fclose($fp);

$data = array(
	'token' => '89E246CC671A93F9ECC3180B1EFACE76',
	'content' => 'metadata',
	'format' => 'json',
	'returnFormat' => 'json'
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://redcap.vanderbilt.edu/api/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
$output = curl_exec($ch);
curl_close($ch);
$metadata = json_decode($output, true);
$choices = getChoices($metadata);

$data = array(
	'token' => '89E246CC671A93F9ECC3180B1EFACE76',
	'content' => 'record',
	'format' => 'json',
	'type' => 'flat',
	'fields' => array("record_id", "grants_title", "grants_pi"),
	'rawOrLabel' => 'raw',
	'rawOrLabelHeaders' => 'raw',
	'exportCheckboxLabel' => 'false',
	'exportSurveyFields' => 'false',
	'exportDataAccessGroups' => 'false',
	'returnFormat' => 'json'
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://redcap.vanderbilt.edu/api/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
$output = curl_exec($ch);
curl_close($ch);

$upload = array();
$redcapData = json_decode($output, true);
$matches = array();
foreach ($redcapData as $row) {
	$found = FALSE;
	$i = 1;
	foreach ($lines as $line) {
		$name = "";
		$project = "";
		$title = "";
		$j = 0;
		foreach ($line as $item) {
			$header = $headers[$j];
			if ($header == "Name") {
				$name = $item;
			}
			if ($header == "Project Number") {
				$project = $item;
			}
			if ($header == "Title") {
				$title = $item;
			}
			$j++;
		}
		if (($name == $row['grants_pi']) && ($title == $row['grants_title'])) {
			echo "MATCH $name\n";
			$found = TRUE;
			array_push($matches, $i);
			break;
		}
	}
	if (!$found) {
		// echo "NO MATCH {$row['grants_pi']}\n";
	}
	$i++;
} 

echo "$matches matches of ".count($lines)." lines\n";
$i = 1;
foreach ($lines as $line) {
	if (!in_array($i, $matches)) {
		echo "Missing line $i: ".$line[0]."\n";
	}
	$i++;
}
