<?php

$module->loadTwigExtensions();
echo $module->loadIndexTwig((defined("USERID") ? USERID : ''));
