<?php

namespace Vanderbilt\GrantRepository;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use Records;
use REDCap;
use Project;
use Twig\TwigFunction;

class GrantRepository extends AbstractExternalModule
{
    const VALID_ROLES = [1, 2, 3];
    const AWARDS = [
        "k_awards" => "K Awards",
        "r_awards" => "R Awards",
        "misc_awards" => "Misc. Awards",
        "lrp_awards" => "LRP Awards",
        "va_merit_awards" => "VA Merit Awards",
        "f_awards" => "F Awards"
    ];
    private $userProjectId;
    private $grantProjectId;
    public function __construct()
    {
        parent::__construct();
        $this->userProjectId = $this->getSystemSetting('user-project');
        $this->grantProjectId = $this->getSystemSetting('grant-project');
    }

    public function redcap_module_ajax($action, $payload)
    {
        $result = ["errors" => ["No valid action"]];
        if ($action == "grantList") {
            $result = $this->getGrantList($payload['searchParams']);
        } elseif ($action == "statResults") {
            $result = $this->getStatResults($payload['searchParams']);
        } elseif ($action == "redirect") {
            $result = $this->getUrl("interfaces/".$payload['page']);
        }
        elseif ($action == "addComment") {
            $result = $this->addComment($payload['record'],$payload['comment']);
        }
        elseif ($action == "getComments") {
            $result = $this->getComments($payload['record']);
        }
        return $result;
    }

    public function addComment($record, $comment) {
        $grantProject = new Project($this->getGrantProjectId());
        $formInstances = array_keys(\RepeatInstance::getRepeatEventInstanceList($record, $grantProject->firstEventId, $grantProject) ?? []);

        if (!empty($formInstances)) {
            $newInstance = max($formInstances);
        }

        $result = Records::saveData([
            'project_id'=>$this->getGrantProjectId(),'dataFormat'=>'json-array','overwriteBehavior'=>'overwrite',
            'data' => [0 => [
                $grantProject->table_pk=>$this->escape($record),
                'comment'=>$this->escape($comment),
                'comment_user'=>USERID,
                'comment_date'=>date('Y-m-d H:i:s'),
                'redcap_repeat_instance'=>$newInstance,
                'redcap_repeat_instrument'=>'comment_log'
            ]]
        ]);
        $returnStatus = ['status'=>false];
        if (empty($result['errors'])) {
            return $returnStatus['status'] = true;
        }
        return $result;
    }

    public function getComments($recordId) {
        $grantProject = new Project($this->getGrantProjectId());
        $returnArray = ['headers'=>['Author','Comment'],'rows'=>[]];
        $result = Records::getData([
            'project_id' => $this->getGrantProjectId(),
            'return_format' => 'json-array',
            'records' => [$this->escape($recordId)],
            'fields' => [$grantProject->table_pk,'comment','comment_user','comment_date']
        ]);
        foreach ($result as $row) {
            if ($row['redcap_repeat_instance'] == "") continue;
            $returnArray['rows'][(int)$row['redcap_repeat_instance']] = [
                'comment'=>$row['comment'],
                'info'=>$row['comment_user']."<br/>".date('m-d-Y H:i:s',strtotime($row['comment_date']))
            ];
        }
        if (empty($returnArray['rows'])) { $returnArray['rows'] = ['comment'=>'','info'=>'']; }
        $returnArray['rows'] = array_reverse($returnArray['rows']);
        return $returnArray;
    }

    public function getGrantList(array $searchParams)
    {
        $thresholdDate = date("Y-m-d", strtotime("-10 years"));
        /*$sortSql = "";
        $searchSql = "";

# get metadata
        $metadataJSON = \REDCap::getDataDictionary($grantsProjectId, "json");
        $choices = getChoices(json_decode($metadataJSON, true));

# if search term has been submitted then search for term else show all grants
        if ($search != "") {
            $searchSql = "AND d.record in (SELECT DISTINCT record FROM $dataTable WHERE project_id = $grantsProjectId AND value like '%$search%')";
        }

# sort - if sort item selected then order by field
        if ($sort == 'pi') {
            $sortSql = "ORDER BY d2.value";
        } elseif ($sort == 'title') {
            $sortSql = "ORDER BY d.value";
        } elseif ($sort == 'number') {
            $sortSql = "ORDER BY d3.value";
        } elseif ($sort == 'date') {
            $sortSql = "ORDER BY d5.value";
        } elseif ($sort == 'format') {
            $sortSql = "ORDER BY d6.value";
        }

        $awardClause = "";
        foreach ($awards as $award => $awardTitle) {
            if (isset($_GET[$award]) && $_GET[$award]) {
                $awardValues = explode(",", sanitize($_GET[$award]));
                if (in_array("ALL", $awardValues)) {
                    $awardField = $award;
                    $awardClause = "INNER JOIN $dataTable d7 ON (d7.project_id =d.project_id AND d7.record = d.record AND d7.field_name = '$awardField' AND d7.value IN ('" . implode("','", array_keys($choices[$award])) . "'))";
                    $search = "all " . $awardTitle;
                } else {
                    $awardField = $award;
                    $awardValueStr = "('" . implode("','", $awardValues) . "')";
                    $awardClause = "INNER JOIN $dataTable d7 ON (d7.project_id =d.project_id AND d7.record = d.record AND d7.field_name = '$awardField' AND d7.value IN $awardValueStr)";
                    $awardStrs = [];
                    foreach ($awardValues as $awardValue) {
                        $awardStrs[] = $choices[$award][$awardValue];
                    }
                    $search = $awardTitle . " as " . implode(" OR ", $awardStrs);
                }
            }
        }
        # Get the list of grants
        $sql = "SELECT DISTINCT d.record as 'record', d.value as 'title', d2.value as 'pi', d3.value as 'number', d4.value as 'file', d5.value as 'date', d6.value as 'format'
		FROM $dataTable d
		JOIN $dataTable d2
		LEFT JOIN $dataTable d3 ON (d3.project_id =d.project_id AND d3.record = d.record AND d3.field_name = 'grants_number')
		JOIN $dataTable d4
		LEFT JOIN $dataTable d5 ON (d5.project_id =d.project_id AND d5.record = d.record AND d5.field_name = 'grants_date')
		LEFT JOIN $dataTable d6 ON (d6.project_id =d.project_id AND d6.record = d.record AND d6.field_name = 'nih_format')
		$awardClause
		WHERE d.project_id = $grantsProjectId
			$searchSql
			AND d.field_name = 'grants_title'
			AND d2.project_id = d.project_id
			AND d2.record = d.record
			AND d2.field_name = 'grants_pi'
			AND d4.project_id = d.project_id
			AND d4.record = d.record
			AND d4.field_name = 'grants_file'
		    AND (
		        d5.value IS NULL
		        OR d5.value >= '$thresholdDate'
		    )
		$sortSql";
// echo "$sql<br/>";
        $grants = db_query($sql);
        $grantCount = db_num_rows($grants);

        while ($row = db_fetch_assoc($grants)) {
            $recordsSeen[] = sanitize($row['record']);
            $url = "download.php?p=$grantsProjectId&id=" .
                sanitize($row['file']) . "&s=&page=register_grants&record=" . sanitize($row['record']) . "&event_id=" .
                $eventId . "&field_name=grants_file";

            echo "<tr>";
            if (isset($_GET['test'])) {
                echo "<td>" . sanitize($row['record']) . "</td>";
            }
            echo "<td style='white-space:nowrap;'>" . sanitize($row['pi']) . "</td>";
            echo "<td>" . sanitize($row['title']) . "</td>";
            echo "<td style='text-align: center;'>" . sanitize($row['date']) . "</td>";
            echo "<td style='white-space:nowrap;'>" . sanitize($row['number']) . "</td>";
            echo "<td style='text-align: center;'><a href='$url'>View</a></td>";
            echo "</tr>";
        }*/

        $grantsProject = new Project($this->getGrantProjectId());
        $grantEventID = $grantsProject->firstEventId;

        $returnData = [
            'data'=>[],
            'columns'=>[
                ['title'=>'Title'],
                ['title'=>'PI'],
                ['title'=>'Grant Number'],
                ['title'=>'Date'],
                ['title'=>'View'],
                ['title'=>'Abstract']
            ]];
        $result = Records::getData([
            'project_id' => $this->getGrantProjectId(),
            'return_format' => 'json-array',
            'fields' => [$grantsProject->table_pk,'grants_title','grants_abstract','grants_pi','grants_file','grants_number','grants_date','nih_format'],
            'filterLogic' => "[grants_date] > '$thresholdDate'",
        ]);

        // Include the abstract for the grant. Hidden column on the table?
        // Dropdown list of filter options for different grant types
        // Add a link to the comments (load as modal) to the grant # column
        /*Green (primary) RGB 79, 184, 82 CMYK 70, 00, 93, 00 HEX #4eb851
        Blue (secondary) RGB 38, 165, 205 CMYK 73, 17, 10, 00 HEX #26a4cd
        Yellow RGB 253, 186, 99 CMYK 01, 25, 88, 00 HEX #fbc23b*/

        // Sidebar in-line with the data tables header?
        // 'Making Research Careers Easier' tagline on page (bottom?), also 'Contact Us' for info@edgeforscholars.org
        // Names need filter to do proper casing (look for 1-2 letter all caps to be left alone as initials)
        foreach ($result as $row) {
            $returnData['data'][] = [
                (strtoupper($row['grants_title']) == $row['grants_title'] ? mb_convert_case($row['grants_title'], MB_CASE_TITLE, 'UTF-8') : $row['grants_title']),
                $row['grants_pi'],
                $row['grants_number']."&nbsp;<div class='comment_link' onclick='viewCommentModal(\"".$row[$grantsProject->table_pk]."\");'><img style='height:15px;' src='".$this->getUrl('img/comment.svg')."'/></div>",
                date('m-d-Y', strtotime($row['grants_date'])),
                "<div class='textlink'><a href='".$this->getUrl('download.php')."'>View</a></div>",
                //https://redcap.vumc.org/plugins/grant_repository/download.php?p=27635&id=8393100&s=&page=register_grants&record=20&event_id=52818&field_name=grants_file
                $row['grants_abstract']
            ];
        }
        return $returnData;
    }

    public function getStatResults(array $searchParams)
    {
        
    }

    public function getGrantProjectId()
    {
        return $this->grantProjectId;
    }

    public function getUserProjectId()
    {
        return $this->userProjectId;
    }

    public function getAwards(): array
    {
        return self::AWARDS;
    }

    public function loadTwigExtensions():void
    {
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
    }

    public function loadIndexTwig($userid)
    {
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

    public function loadGrantsTwig($userid)
    {
        $userid = $this->escape($userid);
        $userStatus = $this->processUserAccess($userid);

        if ($userid == '' || !is_numeric($userStatus)) {
            header("Location: ".$this->getUrl('interfaces/index.php'));
        }

        $grantProjectPath = APP_PATH_WEBROOT."index.php?pid=".$this->getgrantProjectId();
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

    public function loadStatsTwig($userid)
    {
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
            'statList' => $this->getGrantList([]),
            'statsHTML' => "<table id='stats_table'></table>"
        ]);
    }

    public function addJS(string $path): void
    {
        $this->initializeJavascriptModuleObject();
        echo "<script src='".$this->getUrl($path)."'></script>
            <script src='https://code.jquery.com/jquery-3.7.1.slim.js' integrity='sha256-UgvvN8vBkgO0luPSUl2s8TIlOSYRoGFAX4jlCIm9Adc=' crossorigin='anonymous'></script>
            <link rel='stylesheet' href='https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.css' />  
            <script src='https://cdn.datatables.net/2.3.2/js/dataTables.js'></script>
        ";
    }

    public function processUserAccess(string $userid):string
    {
        $userid = $this->escape($userid);
        $timestamp = date('Y-m-d');
        $returnStatus = "Unable to locate user $userid.";

        if ($userid != '') {
            $result = \REDCap::getData([
                'project_id' => $this->userProjectId,
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
}
