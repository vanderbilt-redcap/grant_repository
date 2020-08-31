
# verify user access
if (!isset($_COOKIE['grant_repo'])) {
	header("Location: index.php");
}

require_once("base.php");

$awards = array(
		"k_awards" => "K Awards",
		"r_awards" => "R Awards",
		"misc_awards" => "Misc. Awards",
		"lrp_awards" => "LRP Awards",
		"va_merit_awards" => "VA Merit Awards",
		"f_awards" => "F Awards",
		);

# get query string variables
$search = (isset($_GET['s'])) ? preg_replace('#[^a-z 0-9?!]#i', '', $_GET['s']) : "";
$sort = (isset($_GET['o'])) ? $_GET['o'] : "pi";

$sortSql = "";
$searchSql = "";

# get metadata
$metadataJSON = \REDCap::getDataDictionary($grantsProjectId, "json");
$choices = getChoices(json_decode($metadataJSON, true));

# get event_id
$sql = "SELECT event_id
		FROM redcap_events_metadata           
		WHERE arm_id =
			(SELECT arm_id
			FROM redcap_events_arms
			WHERE project_id = $grantsProjectId)";
$eventId = db_result(db_query($sql), 0);

# if search term has been submitted then search for term else show all grants
if ($search != "") {
	$searchSql = "AND d.record in (SELECT DISTINCT record FROM redcap_data WHERE project_id = $grantsProjectId AND value like '%$search%')";
}

# sort - if sort item selected then order by field
if ($sort == 'pi') {
	$sortSql = "ORDER BY d2.value";
}
elseif ($sort == 'title') {
	$sortSql = "ORDER BY d.value";
}
elseif ($sort == 'number') {
	$sortSql = "ORDER BY d3.value";
}
else if ($sort == 'date') {
	$sortSql = "ORDER BY d5.value";
}
elseif ($sort == 'format') {
	$sortSql = "ORDER BY d6.value";
}

$awardClause = "";
foreach ($awards as $award => $awardTitle) {
	if (isset($_GET[$award]) && $_GET[$award]) {
		$awardValue = $_GET[$award];
		$awardField = $award;
		$awardClause = "INNER JOIN redcap_data d7 ON (d7.project_id =d.project_id AND d7.record = d.record AND d7.field_name = '$awardField' AND d7.value='$awardValue')";
		$search = $awardTitle." as ".$choices[$award][$awardValue];
	}
}

# Get the list of grants
$sql = "SELECT d.record, d.value as 'title', d2.value as 'pi', d3.value as 'number', d4.value as 'file', d5.value as 'date', d6.value as 'format'
		FROM redcap_data d
		JOIN redcap_data d2
		LEFT JOIN redcap_data d3 ON (d3.project_id =d.project_id AND d3.record = d.record AND d3.field_name = 'grants_number')
		JOIN redcap_data d4
		LEFT JOIN redcap_data d5 ON (d5.project_id =d.project_id AND d5.record = d.record AND d5.field_name = 'grants_date')
		LEFT JOIN redcap_data d6 ON (d6.project_id =d.project_id AND d6.record = d.record AND d6.field_name = 'nih_format')
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
		$sortSql";
// echo "$sql<br/>";
$grants = db_query($sql);
$grantCount = db_num_rows($grants);

# display message to user
if ($search == "")
	$message = "Displaying all $grantCount grants";
else
	$message = "Displaying $grantCount grants for: $search";

?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="css/basic.css">
	</head>
	<body>
		<br/>
		<div id="container">
			<div id="header">
				<img src="img/EdgeLogoSmall.png" style="vertical-align:middle"/>
				<hr>
				<a href="grants.php">Grants</a> |
				<?php
				if ($_COOKIE['grant_repo'] != 1) {
					echo '<a href="statistics.php">Use Statistics</a> |';
				}
				if ($_COOKIE['grant_repo'] == 3) {
					echo "<a href='".APP_PATH_WEBROOT."index.php?pid=$grantsProjectId' target='_blank'>Register Grants</a> |";
					echo "<a href='".APP_PATH_WEBROOT."index.php?pid=$userProjectId' target='_blank'>Administer Users</a> |";
				} ?>
				<a href ="http://projectreporter.nih.gov/reporter.cfm">NIH RePORTER</a> |
				<a href ="http://grants.nih.gov/grants/oer.htm">NIH-OER</a>
				<h3>Edge for Scholars Funded Grant Repository</h3>
				<i>You may download grant documents by clicking "download" links below. The use of the grants document repository is strictly limited to authorized individuals and you are not permitted to share files or any embedded content with other individuals. All file downloads are logged.</i><br><br><span style="color:#fe0000;">NEW: We have replaced the spreadsheet of grant components with dropdown menus you can use to filter to grants containing only your component (budget, letters, etc.) of interest.</span> <a href="https://redcap.vanderbilt.edu/plugins/grant_repository/download.php?p=27635&id=3067400&s=&page=register_grants&record=1%20READ%20ME%20FIRST&event_id=52818&field_name=grants_file">Instructions for filtering.</a>
				<hr/>
			</div>

			<div id="filter">
				<table id="filterTable">
				<tr>
					<td>
						<form method="get">
							<select name="o">
								<option value="pi" <?php echo ($sort == "pi") ? "selected" : "" ?>>PI</option>
								<option value="title" <?php echo ($sort == "title") ? "selected" : "" ?>>Grant Title</option>
								<option value="number" <?php echo ($sort == "number") ? "selected" : "" ?>>Grant #</option>
								<option value ="format" <?php echo ($sort == "format") ? "selected" : "" ?>>NIH Format</option>
								<option value ="date" <?php echo ($sort == "date") ? "selected" : "" ?>>NIH Submission Date</option>
							</select>
							<input type="submit" value="Sort"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="hidden" name="s" value="<?= $search ?>" />
						</form>
					</td>
					<td>
						<form method ="get" action ="grants.php" >
							<input type="text" name="s" style="width: 250px;" value="<?= $search ?>" />
							<input type="submit" value="Search Term or Phrase" />
							<input type="hidden" name="o" value="<?= $sort ?>" />
						</form>
					</td>
					<td>
						<form method ="get" action ="grants.php" >
							<strong>OR</strong>&nbsp;
							<input type="submit" value="Show All Grants" />
							<input type="hidden" name="s" value="" />
							<input type="hidden" name="o" value="<?= $sort ?>" />
						</form>
					</td>
				</tr>
				<tr>
					<td style='vertical-align: middle;'>
						Filter By: <select id='award_type' onchange='displayAwardList(); displayFilterButton();'>
							<option value=''>---SELECT---</option>
							<?php
							foreach ($awards as $award => $awardTitle) {
								echo "<option value='$award'>$awardTitle</option>";
							}
							?>
						</select>
					</td>
					<td colspan='2' style='vertical-align: middle;'>
<?php
echo "<form style='margin-bottom: 0px;' method='get'>";
foreach($awards as $award => $awardTitle) {
	echo "<select name='$award' id='$award' onchange='displayFilterButton();' style='display: none;'>";
	echo "<option value=''>---ALL---</option>";
	foreach ($choices[$award] as $value => $label) {
		echo "<option value='$value'>$label</option>";
	}
	echo "</select>";
}
echo "<input type='submit' style='display: none;' id='filterButton' value='Filter'>";
echo "<input type='hidden' name='s' value='' />";
echo "<input type='hidden' name='o' value='<?= $sort ?>' />";
echo "</form>";
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
	function displayFilterButton() {
		var item = $('#award_type').val();
		var sel = $('#'+item).val();
		$('#filterButton').show();
	}

	function displayAwardList() {
		var items = <?= json_encode($awards) ?>;
		var item;
		for (item in items) {
			$('#'+item).hide();
		}
		item = $('#award_type').val();
		if (item !== "") {
			$('#'+item).show();
		}
	}
</script>
					</td>
				</tr>
				</table>
			</div>

			<div id="grants">
				<div><strong><?= $message ?></strong></div>
				<br/>
				<table id="grantsTable">
				<tr>
					<th>PI</th>
					<th>Grant Title</th>
					<th style="width: 90px;">NIH Format</th>
					<th style="width: 150px;">Grant Date</th>
					<th>Grant #</th>
					<th></th>
				</tr>
				<tr>
					<?php
					while ($row = db_fetch_assoc($grants)) {
						$url = "download.php?p=$grantsProjectId&id=" .
							$row['file'] . "&s=&page=register_grants&record=" . $row['record'] . "&event_id=" .
							$eventId . "&field_name=grants_file";

						echo "<td style='white-space:nowrap;'>" . $row['pi'] . "</td>";
						echo "<td>" . $row['title'] . "</td>";
						echo "<td style='text-align: center;'>" . (($row['format'] == "1") ? "NEW" : "OLD") . "</td>";
						echo "<td style='text-align: center;'>" . $row['date']."</td>";
						echo "<td style='white-space:nowrap;'>" . $row['number'] . "</td>";
						echo "<td style='text-align: center;'><a href='$url'>Download</a></td></tr>";
					}
					?>
				</table>
			</div>
		</div>
    </body>
</html>

