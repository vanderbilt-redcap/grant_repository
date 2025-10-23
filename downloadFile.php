<?php
$postData = $module->escape($_POST);
if (isset($postData['path']) && isset($postData['filename'])) {
    $path = $postData['path'];
    $filename = $postData['filename'];
    $module->downloadFile($path,$filename);
}

