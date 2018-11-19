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
	$line_i = 1;
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
		if ((strtolower($name) == strtolower($row['grants_pi'])) && (strtolower($title) == strtolower($row['grants_title']))) {
			// echo "MATCH $name\n";
			$found = TRUE;
			$matches[$line_i] = $row['record_id'];
			break;
		}
		$line_i++;
	}
	if (!$found) {
		// echo "NO MATCH {$row['grants_pi']}\n";
	}
} 

echo count($matches)." matches of ".count($lines)." lines\n";
$line_i = 1;
foreach ($lines as $line) {
	if (!in_array($line_i, array_keys($matches))) {
		echo "Missing line $line_i: ".$line[0]."\n";
	}
	$line_i++;
}

$header2Variable = array();
$skip = array("Name", "Project Number", "Title");
foreach ($headers as $header) {
	if (!in_array($header, $skip)) {
		foreach ($choices['r_awards'] as $value => $text) {
			if ($text == $header) {
				$variable = "r_awards___".$value;
				$header2Variable[$header] = $variable;
				break;
			}
		}
	}
}

$line_i = 1;
foreach ($lines as $line) {
	$j = 0;
	$recordId = $matches[$line_i];
	$row = array("record_id" => $recordId);
	foreach ($line as $item) {
		$header = $headers[$j];
		$variable = "";
		if (isset($header2Variable[$header])) {
			$variable = $header2Variable[$header];
		}
		if ($line[$j] == 'Y') {
			$value = '1';
		} else {
			$value = '0';
		}
		if ($variable) {
			$row[$variable] = $value;
		}
		$j++;
	}
	array_push($upload, $row);
	$line_i++;
}
echo json_encode($upload)."\n";
echo count($upload)." rows\n";
