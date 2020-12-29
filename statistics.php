<?php
# verify user access
if (!isset($_COOKIE['grant_repo'])) {
	header("Location: index.php");
}
else if (isset($_COOKIE['grant_repo']) && ($_COOKIE['grant_repo'] == 1 )) {
	header("Location: index.php");
}

require_once("base.php");

$role = $_COOKIE['grant_repo'];

# if role=2, then we only want to show stats for their specific grants
if ($role == 2) {
	$filterDataSql = " AND d.record IN
		(SELECT record
		FROM redcap_data
		WHERE project_id = $grantsProjectId
			AND field_name = 'pi_vunet_id'
			AND value = '$userid')";
	$filterLogSql = " AND e.pk IN
		(SELECT record
		FROM redcap_data
		WHERE project_id = $grantsProjectId
		AND field_name = 'pi_vunet_id'
		AND value = '$userid')";
}

$sql = "SELECT d.record, d.value as title, d2.value as number, d3.value as pi
		FROM redcap_data d
		LEFT JOIN redcap_data d2 ON (d2.project_id = d.project_id AND d2.record = d.record AND d2.field_name ='grants_number')
		JOIN redcap_data d3
		WHERE d.project_id = $grantsProjectId
			AND d.field_name = 'grants_title'
			AND d3.project_id = d.project_id
			AND d3.record = d.record
			AND d3.field_name = 'grants_pi'
			$filterDataSql
		ORDER BY d3.value";
$result = db_query($sql);
//echo "$sql<br/>";

# create array to hold log events
$downloads = array();
while ($row = db_fetch_array($result)) {
	$downloads[$row['record']]['title'] = $row['title'];
	$downloads[$row['record']]['number'] = $row['number'];
	$downloads[$row['record']]['pi'] = $row['pi'];
}

# get all log events for file downloads
$sql = "SELECT u.value as vunet, u2.value as firstName, u3.value as lastName
                        FROM redcap_data u
                        LEFT JOIN redcap_data u2 ON (u2.project_id = u.project_id AND u.record = u2.record AND u2.field_name = 'first_name')
                        LEFT JOIN redcap_data u3 ON (u3.project_id = u.project_id AND u.record = u3.record AND u3.field_name = 'last_name')
            WHERE u.project_id = $userProjectId
                AND u.field_name = 'vunet_id'";
$result = db_query($sql);
$vuNets = array();
while ($row = db_fetch_assoc($result)) {
	$vuNets[$row['vunet']] = array($row['firstName'], $row['lastName']);
}

$sql = "SELECT e.ts, e.user, e.pk
		FROM redcap_log_event e
        WHERE e.project_id = $grantsProjectId
            AND e.description = 'Download uploaded document'
			$filterLogSql
		ORDER BY e.ts DESC";
$result = db_query($sql);
//echo "$sql<br/>";

while ($row = db_fetch_array($result)) {
	if ($vuNets[$row['user']] && $vuNets[$row['user']][0])
		$name = $vuNets[$row['user']][0] . " " . $vuNets[$row['user']][1] . " (" . $row['user'] . ")";
	else if ($vuNets[$row['user']])
		$name = $row['user'];

	$downloads[$row['pk']]['hits'][] = array('ts' => $row['ts'], 'user' => $name);
}
?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="css/basic.css">
	</head>
	<body>
		<br/>
		<div style="padding-left:8%;  padding-right:10%; margin-left:auto; margin-right:auto;   ">
			<img src="img/efs_small.png" style="vertical-align:middle"/>
			<hr>
			<a href="grants.php">Grants</a> |
			<?php
			if ($_COOKIE['grant_repo'] != 1) {
				echo '<a href="statistics.php">Use Statistics</a> |';
			}
			if ($_COOKIE['grant_repo'] == 3) {
				echo "<a href='".APP_PATH_WEBROOT."index.php?pid=$grantsProjectId' target='_blank'>Register Grants</a> |";
				echo "<a href='".APP_PATH_WEBROOT."index.php?pid=$userProjectId' target='_blank'>Administer Users</a> |";
			}
			?>
			<a href ="http://projectreporter.nih.gov/reporter.cfm">NIH RePORTER</a> |
			<a href ="http://grants.nih.gov/grants/oer.htm">NIH-OER</a>
			<h3>Edge for Scholars Funded Grant Repository - Usage Statistics</h3>
			<hr><br/>
			<?php
			# loop through each grant
			foreach ($downloads as $id => $value) {
				$count = count($value['hits']);

				echo "<strong>".$value['pi'] . " - " . $value['title'] . " (" . $value['number'] . ")</strong><br/>";
				echo "Record logs indicate " . $count . " download(s) for this project:";

				# loop through array of files download and display
				echo "<ul>";
				foreach ($value['hits'] as $log) {
					$timestamp = strtotime($log['ts']);
					echo "<li>".date('Y-m-d H:i:s', $timestamp) . " --- " . $log['user'] . "</li>";
				}
				echo "</ul>";
			}
			?>
		</div>
	</body>
</html>
