<?php
use Vanderbilt\GrantRepository;

$project_id = $_GET["pid"];

if (is_numeric($project_id)) {
    $module->loadTwigExtensions();

    echo $module->loadGrantsTwig((defined("USERID") ? USERID : ''));
}
