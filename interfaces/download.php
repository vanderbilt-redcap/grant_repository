<?php
use Vanderbilt\GrantRepository;

$edoc_id = $module->escape($_GET["edoc_id"]);
$grant = $module->escape($_GET["grant"]);
$record = $module->escape($_GET["id"]);

if (is_numeric($edoc_id)) {
    $module->loadTwigExtensions();

    echo $module->loadDownloadTwig((defined("USERID") ? USERID : ''), $record, $edoc_id, $grant);
} else {
    echo "A valid edoc ID are required to view this page.";
}
