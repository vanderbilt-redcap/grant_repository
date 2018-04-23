<?php
require_once("base.php");

$timestamp = date('Y-m-d');
$role = "";
$vunet = "";

# query table to authenticate user
$sql = "SELECT a.value as 'userid', a2.value as 'role'
		FROM redcap_data a
		JOIN redcap_data a2
		LEFT JOIN redcap_data a3 ON (a3.project_id =a.project_id AND a3.record = a.record AND a3.field_name = 'user_expiration')
		WHERE a.project_id = $userProjectId
			AND a.field_name = 'vunet_id'
			AND a.value = '$userid'
			AND a2.project_id = a.project_id
			AND a2.record = a.record
			AND a2.field_name = 'user_role'
			AND (a3.value IS NULL OR a3.value > '$timestamp')";
$result = db_query($sql);
//echo "$sql<br/>";

# get vunet and role
if (db_num_rows($result) > 0) {
	$vunet = db_result($result, 0, 0);
	$role = db_result($result, 0, 1);
}

# if they have agreed to the terms, create the cookie and redirect them to the grants page
if (isset($_POST['submit'])) {
	setcookie('grant_repo', $role);
	header("Location: grants.php");
}
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="css/basic.css">
    </head>
    <body style="background-color:#f8f8f8">
        <br/>
        <div style="padding-left:8%;  padding-right:10%; margin-left:auto; margin-right:auto; ">  
            <img src="img/EdgeLogoSmall.png" style="vertical-align:middle"/>
            <hr>
            <h3>Edge for Scholars Funded Grant Repository</h3>
            <br/>
            <?php if ($vunet != ""): ?>
                <p><strong>NOTICE: You must agree to the following terms before using the Edge for Scholars Grant Repository</strong></p>
                <ul> 
                    <li>I agree to keep the contents of the example grants confidential.</li>
                    <li>I will not share any part(s) of the grants in the repository.</li>
                    <li>I agree not to utilize any text of the grant in my own grant.</li>
                    <li>I understand that the individuals who provided grants will be able to view a list of names of those who accessed their grants.</li>
                    <li>I agree to provide a copy of my grant to the Office of Research after submission to be kept on file and reviewed for compliance to this agreement.</li>
                </ul>
                <form  method="post">
                    <input type="submit" value="I agree to all terms above" name="submit">
                </form>
			<?php else: ?>
				Please contact Rebecca Helton at <a href='mailto:rebecca.helton@vanderbilt.edu'>rebecca.helton@vanderbilt.edu</a> to gain access to the Edge for Scholars Funded Grant Repository.
			<?php endif ?>
        </div>
    </html>
