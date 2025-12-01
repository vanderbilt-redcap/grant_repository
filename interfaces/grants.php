<?php

use Vanderbilt\GrantRepository;

$module->loadTwigExtensions();
echo $module->loadGrantsTwig((defined("USERID") ? USERID : ''));
