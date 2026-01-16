<?php

use Vanderbilt\GrantRepository;

$module->loadTwigExtensions();
//$module->saveNIHData();
echo $module->loadGrantsTwig((defined("USERID") ? USERID : ''));
