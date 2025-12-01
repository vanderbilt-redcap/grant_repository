<?php

namespace Vanderbilt\GrantRepository;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use Records;
use REDCap;
use Project;
use Logging;
use Twig\TwigFunction;
use ZipArchive;

class GrantRepository extends AbstractExternalModule
{
	public const VALID_ROLES = [1, 2, 3];
	public const AWARDS = [
		"k_awards" => "K Awards",
		"r_awards" => "R Awards",
		"misc_awards" => "Misc. Awards",
		"lrp_awards" => "LRP Awards",
		"va_merit_awards" => "VA Merit Awards",
		"f_awards" => "F Awards"
	];

	public const DATA_TABLE_HEADERS = [
		['title' => 'Title'],
		['title' => 'PI'],
		['title' => 'Grant Number'],
		['title' => 'NIH Submission Number'],
		['title' => 'Awards'],
		['title' => 'Date'],
		['title' => 'View'],
		['title' => 'Abstract']
	];
	private $userProjectId;
	private $grantProjectId;

	public function redcap_module_ajax($action, $payload) {
		$result = ["errors" => ["No valid action"]];
		if ($action == "grantList") {
			$result = $this->getGrantList($payload['searchParams']);
		} elseif ($action == "statResults") {
			$result = $this->getStatResults($payload['searchParams']);
		} elseif ($action == "redirect") {
			$result = $this->getUrl("interfaces/".$payload['page']);
		} elseif ($action == "addComment") {
			$result = $this->addComment($payload['record'], $payload['comment']);
		} elseif ($action == "getComments") {
			$result = $this->getComments($payload['record']);
		} elseif ($action == "logFileDownload") {
			$result = $this->logFileDownload($payload['record'], $payload['userid']);
		}

		return $result;
	}

	public function addComment($record, $comment) {
		$grantProject = new Project($this->getGrantProjectId());

		$userName = $this->getUserNameById(USERID);

		$result = Records::saveData([
			'project_id' => $this->getGrantProjectId(),'dataFormat' => 'json-array','overwriteBehavior' => 'overwrite',
			'data' => [0 => [
				$grantProject->table_pk => $this->escape($record),
				'comment' => $this->escape($comment),
				'comment_user' => $this->escape($userName),
				'comment_user_id' => USERID,
				'comment_date' => date('Y-m-d H:i:s'),
				'redcap_repeat_instance' => 'new',
				'redcap_repeat_instrument' => 'comment_log'
			]]
		]);
		$returnStatus = ['status' => false];
		if (empty($result['errors'])) {
			$returnStatus['status'] = true;
		}
		return $returnStatus;
	}

	public function getComments($recordId) {
		$grantProject = new Project($this->getGrantProjectId());
		$returnArray = ['headers' => ['Author','Comment'],'rows' => []];
		$result = Records::getData([
			'project_id' => $this->getGrantProjectId(),
			'return_format' => 'json-array',
			'records' => [$this->escape($recordId)],
			'fields' => [$grantProject->table_pk,'comment','comment_user','comment_user_id','comment_date','comment_approved'],
			'filterLogic' => "[comment_approved] = '1' or [comment_user_id] = '".USERID."'"
		]);
		foreach ($result as $row) {
			if ($row['redcap_repeat_instance'] == "") {
				continue;
			}
			$returnArray['rows'][(int)$row['redcap_repeat_instance']] = [
				'comment' => $row['comment'],
				'info' => $row['comment_user']."<br/>".date('m-d-Y H:i:s', strtotime($row['comment_date']))
			];
		}
		if (empty($returnArray['rows'])) {
			$returnArray['rows'][] = ['comment' => '','info' => ''];
		}
		$returnArray['rows'] = array_reverse($returnArray['rows']);
		return $returnArray;
	}

	public function downloadFile($path, $filename) {
		$path = $this->escape($path);
		$filename = $this->escape($filename);
		if (file_exists($path)) {
			header('Content-Type: '.mime_content_type($path));
			header('Content-Disposition: inline; filename="' . $filename . '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($path));
			ob_clean();
			flush();
			readfile($path);
			exit;
		}
	}
	public function logFileDownload($record, $userid) {
		$return = \Logging::logEvent("", "redcap_edocs_metadata", "MANAGE", $record, "", "Download uploaded document", "", $userid, $this->getGrantProjectId());
		return $return;
	}

	public function getGrantList(array $searchParams) {
		$thresholdDate = date("Y-m-d", strtotime("-10 years"));

		$grantsProject = new Project($this->getGrantProjectId());
		$grantEventID = $grantsProject->firstEventId;

		$returnData = [
			'all_award_types' => $this->getAwards(),
			'this_award_type' => '',
			'data' => [],
			'columns' => $this->getDataTableHeaders()];
		$getDataParams = [
			'project_id' => $this->getGrantProjectId(),
			'return_format' => 'json-array',
			'fields' => array_merge([$grantsProject->table_pk,'grants_title','grants_abstract','grants_pi','grants_file','grants_number','grants_date','nih_format','nih_submission_number'], array_keys(self::AWARDS)),
			'exportAsLabels' => true,
			'combine_checkbox_values' => true
		];

		if (!isset($searchParams['show_all'])) {
			$getDataParams['filterLogic'] = "[grants_date] > '$thresholdDate'";
		}

		$result = Records::getData($getDataParams);

		/*Green (primary) RGB 79, 184, 82 CMYK 70, 00, 93, 00 HEX #4eb851
		Blue (secondary) RGB 38, 165, 205 CMYK 73, 17, 10, 00 HEX #26a4cd
		Yellow RGB 253, 186, 99 CMYK 01, 25, 88, 00 HEX #fbc23b*/

		// Names need filter to do proper casing (look for 1-2 letter all caps to be left alone as initials)
		foreach ($result as $row) {
			$grantProject = new Project($this->getGrantProjectId());
			$formInstances = $this->getComments($row[$grantsProject->table_pk]);
			$returnData['data'][] = [
				(strtoupper($row['grants_title']) == $row['grants_title'] ? mb_convert_case($row['grants_title'], MB_CASE_TITLE, 'UTF-8') : $row['grants_title']),
				$row['grants_pi'],
				"<div style='display:inline;'>".$row['grants_number']."</div>&nbsp;<div class='comment_link' onclick='viewCommentModal(\"".$row[$grantsProject->table_pk]."\");'><img style='height:15px;' src='".(!empty($formInstances['rows'][0]['comment']) ? $this->getUrl('img/chat-fill.svg') : $this->getUrl('img/comment.svg'))."'/></div>",
				$row['nih_submission_number'],
				$this->processAwards($row),
				($row['grants_date'] != "" ? date('Y-m', strtotime($row['grants_date'])) : ""),
				(is_numeric($row['grants_file']) ? "<div class='textlink'><a href='".$this->getUrl('interfaces/download.php')."&id=".$row[$grantProject->table_pk]."&edoc_id=".$row['grants_file']."&grant=".$this->escape($row['grants_number'])."'>View</a></div>" : "<div class='textlink'>N/A</div>"),
				$row['grants_abstract']
			];
		}
		return $returnData;
	}

	public function processAwards($data): string {
		$returnString = "";

		foreach (self::AWARDS as $field => $award) {
			if (isset($data[$field]) && !empty($data[$field])) {
				/*$returnString .= "<p>$award</p><ul><li>";
				$returnString .= str_replace(",", "</li><li>", $data[$field]);
				$returnString .= "</li></ul>";*/
				$returnString .= "$award";
			}
		}
		return $returnString;
	}

	public function getStatResults($userid, array $searchParams) {
		$userid = $this->escape($userid);
		$userStatus = $this->processUserAccess($userid);

		if ($userid == '' || !is_numeric($userStatus)) {
			header("Location: " . $this->getUrl('interfaces/index.php'));
		}

		$grantsProject = new Project($this->getGrantProjectId());
		$usersProject = new Project($this->getUserProjectId());
		$filterGrantLogic = $filterUserLogic = "";
		# Anyone with role = 2 needs to only see grants specific to them
		if (($userid !== "pearsosj" && $userid !== "moorejr5") && ($userStatus == 2)) {
			$filterGrantLogic = "[pi_vunet_id] = '$userid'";
			$filterUserLogic = "[vunet_id] = '$userid'";
		}

		$grantsResult = Records::getData([
			'project_id' => $this->getGrantProjectId(),
			'return_format' => 'json-array',
			'fields' => [$grantsProject->table_pk,'grants_title','grants_pi','grants_number'],
			'exportAsLabels' => true,
			'combine_checkbox_values' => true,
			'filterLogic' => $filterGrantLogic,
		]);
		$downloads = [];
		$piList = [];

		foreach ($grantsResult as $row) {
			$downloads[$this->escape($row[$grantsProject->table_pk])]['title'] = $this->escape($row['grants_title']);
			$downloads[$this->escape($row[$grantsProject->table_pk])]['number'] = $this->escape($row['grants_number']);
			$downloads[$this->escape($row[$grantsProject->table_pk])]['pi'] = $this->escape($row['grants_pi']);
			$piList[] = $this->escape($row['grants_pi']);
		}

		$usersResult = Records::getData([
			'project_id' => $this->getUserProjectId(),
			'return_format' => 'json-array',
			'fields' => [$usersProject->table_pk,'vunet_id','first_name','last_name'],
			'exportAsLabels' => true,
			'combine_checkbox_values' => true,
			'filterLogic' => $filterUserLogic,
		]);

		$vuNets = [];
		foreach ($usersResult as $row) {
			$vuNets[$this->escape($row['vunet_id'])] = ['first_name' => $this->escape($row['first_name']),"last_name" => $this->escape($row['last_name'])];
		}

		$logEventTable = method_exists('\Logging', 'getLogEventTable') ? \Logging::getLogEventTable($this->getGrantProjectId()) : "redcap_log_event";
		# get all log events for file downloads
		$sql = "SELECT ts, user, pk
            FROM $logEventTable
            WHERE project_id = ?
                AND description = 'Download uploaded document'
                AND pk IN (".implode(",", array_keys($downloads)).")
            ORDER BY ts DESC";
		$logsResult = $this->query($sql, [$this->getGrantProjectId()]);

		while ($row = $logsResult->fetch_assoc()) {
			$user = $this->escape($row['user']);
			$name = "";
			if (isset($vuNets[$user])) {
				if ($vuNets[$user]['first_name'] != "" && $vuNets[$user]['last_name'] != "") {
					$name = $vuNets[$user]['first_name'] . " " . $vuNets[$user]['last_name']." (".$this->escape($user).")";
				} else {
					$name = $this->escape($user);
				}
			}

			$downloads[$this->escape($row['pk'])]['hits'][] = ['ts' => $this->escape(date("Y-m-d H:i:s", strtotime($row['ts']))), 'user' => $name];
		}

		usort($downloads, function ($a, $b) {
			return strcmp($a['pi'], $b['pi']);
		});
		return $downloads;
	}


	public function getGrantProjectId() {
		if (empty($this->grantProjectId)) {
			$this->grantProjectId = $this->getSystemSetting('grant-project');
		}
		return $this->grantProjectId;
	}

	public function getUserProjectId() {
		if (empty($this->userProjectId)) {
			$this->userProjectId = $this->getSystemSetting('user-project');
		}
		return $this->userProjectId;
	}

	public function getAwards(): array {
		return self::AWARDS;
	}
	public function getDataTableHeaders(): array {
		return self::DATA_TABLE_HEADERS;
	}
	public function loadTwigExtensions(): void {
		include_once(__DIR__ . "/vendor/autoload.php");
		$this->initializeTwig();

		$this->getTwig()->addFunction(new TwigFunction('loadREDCapFiles', function () {
			return $this->framework->loadBootstrap() . $this->framework->loadREDCapJS();
		}));

		$this->getTwig()->addFunction(new TwigFunction('addJS', function ($path) {
			$this->addJS($path);
		}));

		$this->getTwig()->addFunction(new TwigFunction('getCSRFToken', function () {
			return $this->getCSRFToken();
		}));

		$this->getTwig()->addFunction(new TwigFunction('getDataTableHeaders', function () {
			return $this->getDataTableHeaders();
		}));
	}

	public function loadIndexTwig($userid) {
		$userid = $this->escape($userid);

		$userStatus = $this->processUserAccess($userid);

		if (isset($_POST['submit']) && is_numeric($userStatus)) {
			$saveData = [
				"vunet_id" => htmlspecialchars($userid),
				"accessed" => '1',
			];
			$json = json_encode([$saveData]);
			\REDCap::saveData($this->getUserProjectId(), "json", $json, "overwrite");

			header("Location: ".$this->getUrl('interfaces/grants.php'));
		}

		return $this->getTwig()->render('index.html.twig', [
			'project_id' => $this->getProjectId(),
			'userID' => $userid,
			'userStatus' => $userStatus
		]);
	}

	public function loadGrantsTwig($userid) {
		$userid = $this->escape($userid);
		$userStatus = $this->processUserAccess($userid);

		if ($userid == '' || !is_numeric($userStatus)) {
			header("Location: ".$this->getUrl('interfaces/index.php'));
		}

		$grantProjectPath = APP_PATH_WEBROOT."index.php?pid=".$this->getGrantProjectId();
		$userProjectPath = APP_PATH_WEBROOT."index.php?pid=".$this->getuserProjectId();

		return $this->getTwig()->render('grants.html.twig', [
			'project_id' => $this->getProjectId(),
			'userID' => $userid,
			'userStatus' => $userStatus,
			'grantProjectPath' => $grantProjectPath,
			'userProjectPath' => $userProjectPath,
			'grantList' => $this->getGrantList([])
		]);
	}

	public function loadDownloadTwig($userid, $record, int $edocid, $grantNum) {
		$userid = $this->escape($userid);
		$userStatus = $this->processUserAccess($userid);

		if ($userid == '' || !is_numeric($userStatus)) {
			header("Location: ".$this->getUrl('interfaces/index.php'));
		}

		return $this->getTwig()->render('download.html.twig', [
			'project_id' => $this->getProjectId(),
			'record' => $this->escape($record),
			'userID' => $this->escape($userid),
			'userStatus' => $userStatus,
			'grant' => $this->escape($grantNum),
			'files' => $this->processGrantsFile($edocid),
			'csrf_token' => $this->framework->getCSRFToken()
		]);
	}

	public function loadStatsTwig($userid) {
		$userid = $this->escape($userid);
		$userStatus = $this->processUserAccess($userid);

		if ($userid == '' || !is_numeric($userStatus)) {
			header("Location: ".$this->getUrl('interfaces/index.php'));
		}

		$grantProjectPath = APP_PATH_WEBROOT."index.php?pid=".$this->getgrantProjectId();
		$userProjectPath = APP_PATH_WEBROOT."index.php?pid=".$this->getuserProjectId();

		return $this->getTwig()->render('statistics.html.twig', [
			'project_id' => $this->getProjectId(),
			'userID' => $userid,
			'userStatus' => $userStatus,
			'grantProjectPath' => $grantProjectPath,
			'userProjectPath' => $userProjectPath,
			'statList' => $this->getStatResults($userid, []),
			'statsHTML' => "<table id='stats_table'></table>"
		]);
	}

	public function addJS(string $path): void {
		$this->initializeJavascriptModuleObject();
		echo "<script src='".$this->getUrl($path)."'></script>
            <script src='https://code.jquery.com/jquery-3.7.1.slim.js' integrity='sha256-UgvvN8vBkgO0luPSUl2s8TIlOSYRoGFAX4jlCIm9Adc=' crossorigin='anonymous'></script>  
            <link href='https://cdn.datatables.net/v/dt/dt-2.3.2/b-3.2.4/b-colvis-3.2.4/datatables.min.css' rel='stylesheet' integrity='sha384-XPuogoBP+FZ8OyAeG98HZK8AwqEncT0ApG1/dLGPTF2/hDZ1mgVIApEy0nYV7ESE' crossorigin='anonymous'>
            <script src='https://cdn.datatables.net/v/dt/dt-2.3.2/b-3.2.4/b-colvis-3.2.4/datatables.min.js' integrity='sha384-0orDQyjg1TNlLwCB04+J6pfHi6xcljp58sU3QacbK1exhnsX8jZfjyKs0cY3DTYN' crossorigin='anonymous'></script>
        ";
	}

	public function processUserAccess(string $userid): string {
		$userid = $this->escape($userid);
		$timestamp = date('Y-m-d');
		$returnStatus = "Unable to locate user $userid.";

		if ($userid != '') {
			$result = \REDCap::getData([
				'project_id' => $this->getUserProjectId(),
				'return_format' => 'json-array',
				'fields' => ['vunet_id', 'user_role', 'user_expiration'],
				'filterLogic' => "[vunet_id] = '$userid'",
			]);

			foreach ($result as $data) {
				if ($data['vunet_id'] == $userid) {
					if (in_array($data['user_role'], self::VALID_ROLES) && ($timestamp < $data['user_expiration'] || $data['user_expiration'] == "")) {
						$returnStatus = $data['user_role'];
					} elseif (!in_array($data['user_role'], self::VALID_ROLES)) {
						$returnStatus = "Your user ID $userid was not in a valid role for accessing the dashboard.";
					} elseif ($timestamp >= $data['user_expiration'] && $data['user_expiration'] != "") {
						$returnStatus = "Your user ID $userid was expired.";
					}
				}
			}
		}

		return $returnStatus;
	}

	public function getUserNameById(string $userid) {
		$userid = $this->escape($userid);
		$name = $userid;

		if ($userid != '') {
			$result = \REDCap::getData([
				'project_id' => $this->getUserProjectId(),
				'return_format' => 'json-array',
				'fields' => ['vunet_id', 'first_name', 'last_name'],
				'filterLogic' => "[vunet_id] = '$userid'",
			]);

			foreach ($result as $data) {
				if ($data['vunet_id'] == $userid) {
					$name = $data['first_name']." ".$data['last_name'];
				}
			}
		}

		return $name;
	}

	public function processGrantsFile(int $edocid) {
		$returnArray = ['errors' => [], 'files' => []];

		$sql = "select * from redcap_edocs_metadata where project_id = ? and doc_id = ?";
		$q = $this->query($sql, [$this->getGrantProjectId(),$this->escape($edocid)]);
		$this_file = db_fetch_array($q);

		$basename = preg_replace("/\.[^\.]*$/", "", $this->escape($this_file['stored_name']));
		if (!preg_match("/\/$/", $basename)) {
			$basename .= "/";
		}
		$outDir = $this->framework->getSafePath(APP_PATH_TEMP.$basename, APP_PATH_TEMP);
		$linkDir = APP_PATH_WEBROOT_FULL."temp/".$basename;
		mkdir($outDir);

		$files = [];
		if (preg_match("/\.zip$/i", $this->escape($this_file['stored_name'])) || ($this->escape($this_file['mime_type']) == "application/x-zip-compressed")) {
			$zip = new ZipArchive();
			$zipFile = $this->framework->getSafePath(EDOC_PATH.$this->escape($this_file['stored_name']), EDOC_PATH);

			$res = $zip->open($zipFile);
			if ($res) {
				for ($i = 0; $i < $zip->numFiles; $i++) {
					$fileContent = $zip->getFromIndex($i);
					$fileName = $zip->getNameIndex($i);
					if (str_ends_with($fileName, '/')) {
						continue;
					} else {
						if ($fileContent !== false) {
							$fullFilePath = $outDir.$fileName;
							$parts = explode('/', $fullFilePath);
							$file = array_pop($parts);
							if (str_starts_with($file, '.')) {
								continue;
							}
							$dir = '';
							// If file name includes directories then need to make sure they exist.
							foreach ($parts as $part) {
								if (!is_dir($dir .= "/$part")) {
									mkdir($dir);
								}
							}
							// Write the content to a new file with the desired name and extension
							if (preg_match("/\.xls$/i", $fileName) || preg_match("/\.xlsx$/i", $fileName) || preg_match("/\.csv$/i", $fileName) || preg_match("/\.docx$/i", $fileName)) {
								file_put_contents(APP_PATH_TEMP.$fileName, $fileContent);
								$fileName = $this->convertFileToPDF(APP_PATH_TEMP.$fileName, $fullFilePath);
							} else {
								file_put_contents($fullFilePath, $fileContent);
							}
						}
					}
				}
				$zip->close();
			}
		} else {
			$inFile = $this->framework->getSafePath(EDOC_PATH.$this->escape($this_file['stored_name']), EDOC_PATH);
			$saveFile = $this->framework->getSafePath($outDir.$this->escape($this_file['doc_name']), $outDir);
			$outFile = $this->convertFileToPDF($inFile, $saveFile);
		}

		$files = $this->inspectDir($outDir, $linkDir);

		if (!empty($files)) {
			$returnArray['files'] = $files;
		} else {
			$returnArray['errors'] = "No files were found.";
		}

		return $returnArray;
	}

	public function truncateFile($filename) {
		return str_replace(APP_PATH_TEMP, "", $filename);
	}

	public function inspectDir($dir, $linkdir) {
		$files = [];

		$allFiles = scandir($dir);
		$skip = [".", ".."];
		foreach ($allFiles as $filename) {
			if (!in_array($filename, $skip)) {
				if (is_dir($dir.$filename)) {
					$files = array_merge($files, $this->inspectDir($dir.$filename."/", $linkdir.$filename."/"));
				} elseif (!preg_match("/^\./", $filename)) {
					$files[] = ['path' => $dir . $filename,'name' => $filename];
				}
			}
		}
		return $files;
	}

	public function convertFileToPDF($sourceFile, $output) {
		$pdfOut = str_replace(".", "_", $output)."_pdf.pdf";
		$phpOfficeObj = null;

		if (preg_match("/\.docx$/i", $sourceFile)) {
			# Word doc
			$domPdfPath = realpath(dirname(__FILE__). '/vendor/dompdf/dompdf');
			\PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
			\PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');

			$phpOfficeObj = \PhpOffice\PhpWord\IOFactory::load($sourceFile);
			$xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpOfficeObj, 'PDF');
			$xmlWriter->save($pdfOut);
		} elseif (preg_match("/\.csv$/i", $sourceFile)) {
			# CSV
			$fileSize = filesize($sourceFile);
			if ($fileSize < 1048576) {
				$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
				$reader->setReadDataOnly(true);
				$spreadsheet = $reader->load($sourceFile);
				$this->setupSpreadsheet($spreadsheet);
				$class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf::class;
				\PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('PDF', $class);
				$xmlWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "PDF");
				$xmlWriter->save($pdfOut);
			} else {
				copy($sourceFile, $output);
				return $output;
			}
		} elseif (preg_match("/\.xls$/i", $sourceFile) || preg_match("/\.xlsx$/i", $sourceFile)) {
			# Excel
			$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($sourceFile);
			$spreadsheet = $reader->load($sourceFile);
			$this->setupSpreadsheet($spreadsheet);
			$class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf::class;
			\PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('PDF', $class);
			$xmlWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "PDF");
			$xmlWriter->save($pdfOut);
		} else {
			copy($sourceFile, $output);
			return $output;
		}
		return $pdfOut;
	}

	public function setupSpreadsheet(&$spreadsheet): void {
		$spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
		$spreadsheet->getActiveSheet()->getPageMargins()->setTop(0.5);
		$spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.5);
		$spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.5);
		$spreadsheet->getActiveSheet()->getPageMargins()->setBottom(0.5);
	}

	private function apiCurlRequest($url, $data, $project_id) {
		$error_message = "";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		$push_response = curl_exec($ch);
		if (curl_errno($ch)) {
			$error_message = curl_error($ch);
		}
		curl_close($ch);
		if ($error_message != "") {
			\REDCap::logEvent(self::REDCAP_LOG_MESSAGE, $error_message, '', null, null, $project_id);
			return false;
		}
		return $push_response;
	}
}
