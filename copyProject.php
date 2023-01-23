<?php

require_once(__DIR__."/base.php");

### Copies TO the origProject

$srcProject = 165634;
$destProject = 27635;
$fileFields = ["grants_file", "email_file"];

$redcapData = \REDCap::getData($srcProject, "json-array");
$id = 1;
foreach ($redcapData as $i => $row) {
    $redcapData[$i]["record_id"] = $id;
    $id++;
}

$output = \REDCap::saveData($destProject, "json-array", $redcapData);
echo json_encode($output);

foreach ($redcapData as $i => $row) {
    foreach ($fileFields as $field) {
        if ($row[$field]) {
            $origDocId = $row[$field];
            $newDocId = \REDCap::copyFile($origDocId, $destProject);
            \REDCap::addFileToField($newDocId, $destProject, $row['record_id'], $field);
        }
    }
}

