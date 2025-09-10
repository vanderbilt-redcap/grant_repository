<?php
use Vanderbilt\GrantRepository;

$project_id = $module->escape($_GET["pid"]);
$edoc_id = $module->escape($_GET["edoc_id"]);
$grant = $module->escape($_GET["grant"]);
$record = $module->escape($_GET["id"]);

if (is_numeric($project_id) && is_numeric($edoc_id)) {
    $module->loadTwigExtensions();

    echo $module->loadDownloadTwig((defined("USERID") ? USERID : ''), $record, $edoc_id, $grant);
} else {
    echo "A valid project ID and edoc ID are required to view this page.";
}
