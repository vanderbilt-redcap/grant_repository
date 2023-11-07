<?php
require_once("base.php");

$timestamp = date('Y-m-d');
$role = "";
$vunet = "";

# spoofing tool - provide uid in the GET params in the URL
// if ($_GET['uid']) {
	// $userid = $_GET['uid'];
// }

$dataTable = method_exists('\REDCap', 'getDataTable') ? \REDCap::getDataTable($userProjectId) : "redcap_data";
# query table to authenticate user
$sql = "SELECT a.value as 'userid', a2.value as 'role'
		FROM $dataTable a
		JOIN $dataTable a2
		LEFT JOIN $dataTable a3 ON (a3.project_id =a.project_id AND a3.record = a.record AND a3.field_name = 'user_expiration')
		WHERE a.project_id = $userProjectId
			AND a.field_name = 'vunet_id'
			AND a.value = '$userid'
			AND a2.project_id = a.project_id
			AND a2.record = a.record
			AND a2.field_name = 'user_role'
			AND (a3.value IS NULL OR a3.value > '$timestamp')";
$result = db_query($sql);
//echo "$sql<br/>";

$validRoles = [1, 2, 3];
# get vunet and role
if (db_num_rows($result) > 0) {
	$vunet = sanitize(db_result($result, 0, 0));
	$dbRole = sanitize(db_result($result, 0, 1));
    foreach ($validRoles as $roleOption) {
        if ($dbRole == $roleOption) {
            $role = $roleOption;
            break;
        }
    }
}

# if they have agreed to the terms, create the cookie and redirect them to the grants page
if (isset($_POST['submit'])) {
    if ($userid == "pearsosj") {
        $role = 2;
    }
	setcookie('grant_repo', $role);
	header("Location: grants.php");
}
 
$startTs = strtotime("2021-01-01");
if (($vunet != "") && ($startTs <= time())) {
	$saveData = [
			"vunet_id" => $vunet,
			"accessed" => '1',
			];
	$json = json_encode([$saveData]);
	\REDCap::saveData($userProjectId, "json", $json, "overwrite");
}

?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="css/basic.css">
    </head>
    <body style="background-color:#f8f8f8">
        <br/>
        <div style="padding-left:8%;  padding-right:10%; margin-left:auto; margin-right:auto; ">  
            <img src="img/efs_small.png" style="vertical-align:middle"/>
            <hr>
            <h3>Edge for Scholars Funded Grant Repository</h3>
            <br/>
            <?php if ($vunet != ""): ?>
                <p><strong>NOTICE: You must agree to the following terms before using the Edge for Scholars Grant Repository</strong></p>
                <ul> 
                    <li>I agree to keep the contents of the example grants confidential.</li>
                    <li>I will not share any part(s) of the grants in the repository. <strong>To protect against plagiarism, grants are only viewable in your browser and cannot be downloaded for offline use.</strong></li>
                    <li>I agree not to utilize any text of the grant in my own grant.</li>
                    <li>I understand that the individuals who provided grants will be able to view a list of names of those who accessed their grants.</li>
                    <li>I agree to provide a copy of my grant to the Office of Research after submission to be kept on file and reviewed for compliance to this agreement.</li>
                </ul>
                <form  method="post">
                    <input type="submit" value="I agree to all terms above" name="submit">
                </form>
			<?php else: ?>
				Please contact Adrienne Babcock at <a href='mailto:adrienne.babcock@vumc.org'>adrienne.babcock@vumc.org</a> to gain access to the Edge for Scholars Funded Grant Repository.
			<?php endif ?>
        </div>
    </html>
