<?php

$postData = $module->escape($_POST);
if (isset($postData['path']) && isset($postData['name'])) {
    $path = $postData['path'];
    $filename = $postData['name'];
    $module->downloadFile($path,$filename);
}

