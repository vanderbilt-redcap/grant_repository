<?php
use Vanderbilt\GrantRepository;

$module->loadTwigExtensions();
echo $module->loadStatsTwig((defined("USERID") ? USERID : ''));
