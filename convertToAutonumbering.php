<?php

require_once("base.php");

$redcapData = \REDCap::getData($grantsProjectId, "json-array", NULL, ["record_id"]);

$oldRecords = [];
$id = 1;
foreach ($redcapData as $i => $row) {
    $oldRecords[$row["record_id"]] = $id;
    $id++;
}

foreach ($oldRecords as $oldRecord => $newRecord) {
    $sql = "UPDATE redcap_data SET value = ? WHERE project_id = ? AND record = ? AND field_name = ?";
    $params = [$newRecord."_NEW", $grantsProjectId, $oldRecord, "record_id"];
    db_query($sql, $params);

    $sql = "UPDATE redcap_data SET record = ? WHERE record = ? AND project_id = ?";
    $params = [$newRecord."_NEW", $oldRecord, $grantsProjectId];
    db_query($sql, $params);
}
foreach ($oldRecords as $oldRecord => $newRecord) {
    $sql = "UPDATE redcap_data SET value = ? WHERE project_id = ? AND record = ? AND field_name = ?";
    $params = [$newRecord, $grantsProjectId, $newRecord."_NEW", "record_id"];
    db_query($sql, $params);

    $sql = "UPDATE redcap_data SET record = ? WHERE record = ? AND project_id = ?";
    $params = [$newRecord, $newRecord."_NEW", $grantsProjectId];
    db_query($sql, $params);
}
