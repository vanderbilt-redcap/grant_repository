<?php
/** Author: Jon Scherdin */
# verify user access
if (!isset($_COOKIE['grant_repo'])) {
	header("Location: index.php");
}

require_once("base.php");

$dieMssg = "Improper filename ".APP_PATH_TEMP.$_GET['f'];
if (!isset($_GET['f']) || preg_match("/\.\./", $_GET['f']) || preg_match("/^\//", $_GET['f'])) {
	die($dieMssg);
}
$filename = APP_PATH_TEMP.$_GET['f'];
if (!file_exists($filename)) {
	die($dieMssg);
}

require_once(dirname(__FILE__)."/vendor/autoload.php");

use Hfig\MAPI;
use Hfig\MAPI\OLE\Pear;

$phpOfficeObj = NULL;
$pdfOut = $filename."_pdf.pdf"; 
if (preg_match("/\.doc$/i", $filename) || preg_match("/\.docx$/i", $filename)) {
	# Word doc
	$phpOfficeObj = \PhpOffice\PhpWord\IOFactory::load($filename);
	$xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpOfficeObj, 'PDF');
	$xmlWriter->save($pdfOut);  
} else if (preg_match("/\.csv$/i", $filename)) {
	# CSV
	$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
	$phpOfficeObj = $reader->load($filename);
	$xmlWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($phpOfficeObj, "PDF");
	$xmlWriter->save($pdfOut);  
} else if (preg_match("/\.xls$/i", $filename) || preg_match("/\.xlsx$/i", $filename)) {
	# Excel
	$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filename);
	$phpOfficeObj = $reader->load($filename);
	$xmlWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($phpOfficeObj, "PDF");
	$xmlWriter->save($pdfOut);  
} else if (preg_match("/\.pdf$/i", $filename)) {
	# PDF
	$pdfOut = $filename;
} else if (preg_match("/\.msg$/i", $filename)) {
	# Outlook Msg

	$messageFactory = new MAPI\MapiMessageFactory(new Swiftmailer\Factory());
	$documentFactory = new Pear\DocumentFactory(); 

	$ole = $documentFactory->createFromFile($filename);
	$message = $messageFactory->parseMessage($ole);

	$html = $message->getBody();

	$filenameHTML = $filename.".html";
	$fp = fopen($filenameHTML, "w");
	fwrite($fp, $html);
	fclose($fp);

	$phpOfficeObj = \PhpOffice\PhpWord\IOFactory::load($filenameHTML, "HTML");
	$xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpOfficeObj, 'PDF');
	$xmlWriter->save($pdfOut);  
} else {
	# unknown type; just download
	displayFile($filename);
}

if (file_exists($pdfOut)) {
	$jpgOut = $filename.".jpg";

	$im = new Imagick();
	$im->setResolution(300,300);
	$im->readimage($pdfOut); 
	$im->setImageFormat('jpeg');    
	$im->writeImages($jpgOut); 
	$im->clear(); 
	$im->destroy();

        header('Content-Type: image/jpeg');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($jpgOut));

	readfile($jpgOut);
} else {
	die("Could not create intermediate file.");
}

function displayFile($filename) {
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.basename($filename).'"');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($filename));

	readfile($filename);
	exit();
}
