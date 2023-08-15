<?php

# verify user access
if (!isset($_COOKIE['grant_repo'])) {
	header("Location: index.php");
}

require_once("base.php");

# get event_id
$sql = "SELECT event_id
		FROM redcap_events_metadata           
		WHERE arm_id =
			(SELECT arm_id
			FROM redcap_events_arms
			WHERE project_id = $grantsProjectId)";

if (isset($_GET['searchTerms']) && $_GET['searchTerms']) {
    $searchTerms = $_GET['searchTerms'];
    $terms = [];
    while (preg_match("/\"(.+)\"/", $searchTerms, $matches)) {
        $quotedTerm = $matches[1];
        $terms[] = $quotedTerm;
        $searchTerms = str_replace("\"$quotedTerm\"", "", $searchTerms);
    }
    $normalTerms = preg_split("/\s+/", $searchTerms);
    $terms = array_merge($terms, $normalTerms);
    $foundItems = searchForTerms($grantsProjectId, $eventId, $terms);
    if (!empty($foundItems)) {
        echo makeSearchHTML($foundItems);
        exit;
    } else {
        echo "<h4>No items found</h4>";
    }
}

$awards = array(
		"k_awards" => "K Awards",
		"r_awards" => "R Awards",
		"misc_awards" => "Misc. Awards",
		"lrp_awards" => "LRP Awards",
		"va_merit_awards" => "VA Merit Awards",
		"f_awards" => "F Awards",
		);

# get query string variables
$search = (isset($_GET['s'])) ? preg_replace('#[^a-z 0-9?!]#i', '', sanitize($_GET['s'])) : "";
$sort = (isset($_GET['o'])) ? sanitize($_GET['o']) : "pi";

$sortSql = "";
$searchSql = "";

# get metadata
$metadataJSON = \REDCap::getDataDictionary($grantsProjectId, "json");
$choices = getChoices(json_decode($metadataJSON, true));

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
		$awardValues = explode(",", $_GET[$award]);
		if (in_array("ALL", $awardValues)) {
			$awardField = $award;
			$awardClause = "INNER JOIN redcap_data d7 ON (d7.project_id =d.project_id AND d7.record = d.record AND d7.field_name = '$awardField' AND d7.value IN ('".implode("','", array_keys($choices[$award]))."'))";
			$search = "all ".$awardTitle;
		} else {
			$awardField = $award;
            $awardValueStr = "('".implode("','", $awardValues)."')";
			$awardClause = "INNER JOIN redcap_data d7 ON (d7.project_id =d.project_id AND d7.record = d.record AND d7.field_name = '$awardField' AND d7.value IN $awardValueStr)";
            $awardStrs = [];
            foreach ($awardValues as $awardValue) {
                $awardStrs[] = $choices[$award][$awardValue];
            }
			$search = $awardTitle." as ".implode(" OR ", $awardStrs);
		}
	}
}

# Get the list of grants
$sql = "SELECT DISTINCT d.record as 'record', d.value as 'title', d2.value as 'pi', d3.value as 'number', d4.value as 'file', d5.value as 'date', d6.value as 'format'
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

if (isset($_GET['test'])) {
    $redcapData = \REDCap::getData($grantsProjectId, "json-array", NULL, ["record_id"]);
    $message .= " ".count($redcapData)." rows of REDCap records";
    $records = [];
    foreach ($redcapData as $row) {
        $records[] = $row['record_id'];
    }
}

?>

<html>
	<head>
		<title>The Grant Repository from Edge for Scholars</title> 
		<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
        	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
		<style>
			body { font-family: 'Avenir Next Regular', Arial, Helvetica, sans-serif; }
			td { font-family: 'Adobe Caslon Pro', 'Avenir Next LT Pro', 'Times New Roman', Times, serif; }
			table { border-collapse: collapse; }
			table.dataTable tbody tr.even { background-color: #f2f2f2; } 
		</style>
	</head>
	<body>
		<br/>
		<script>
		$(document).ready( function () {
			// $('#filterTable').DataTable();
			$('#grantsTable').DataTable({
                "pageLength": 1000,
                "language": {
                    "search": "Search Grants Titles &amp; Authors:"
                }
            });
		});
		</script>
		<div id="container">
			<div id="header">
				<img src="img/efs_small.png" style="vertical-align:middle"/>
				<hr>
				<a href="grants.php">Grants</a> | 
				<?php
				if ($_COOKIE['grant_repo'] != 1) {
					echo '<a href="statistics.php">Use Statistics</a> | ';
				}
				if ($_COOKIE['grant_repo'] == 3) {
					echo "<a href='".APP_PATH_WEBROOT."index.php?pid=$grantsProjectId' target='_blank'>Register Grants</a> | ";
					echo "<a href='".APP_PATH_WEBROOT."index.php?pid=$userProjectId' target='_blank'>Administer Users</a> | ";
				} ?>
				<a href ="http://projectreporter.nih.gov/reporter.cfm">NIH RePORTER</a> | 
				<a href ="http://grants.nih.gov/grants/oer.htm">NIH-OER</a>
				<h3>Edge for Scholars Funded Grant Repository</h3>
				<i>You may vew grant documents by clicking "View" links below. The use of the grants document repository is strictly limited to authorized individuals and you are not permitted to share files or any embedded content with other individuals. All file downloads are logged.</i>
				<hr/>
			</div>

			<div id="filter">
				<table id='filterTable' style="width: 100%">
				<tbody>
				<tr>
					<td style='vertical-align: middle;'>
						Filter By: <select id='award_type' onchange='displayAwardList();'>
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
echo "<div style='float: left; max-width: 600px;'>";
echo "<form style='margin-bottom: 0px;' method='get'>";
foreach($awards as $award => $awardTitle) {
	echo "<select name='$award' id='$award' onchange='displayFilterButton();' style='display: none;'>";
	echo "<option value=''>---SELECT---</option>";
	echo "<option value='ALL'>---ALL---</option>";
    $items = [];
	foreach ($choices[$award] as $value => $label) {
        $shortenedLabel = preg_replace("/^Original /", "", $label);
        $shortenedLabel = preg_replace("/^Resub /", "", $shortenedLabel);
        if (!isset($items[$shortenedLabel])) {
            $items[$shortenedLabel] = [];
        }
        $items[$shortenedLabel][] = $value;
    }
	foreach ($items as $label => $values) {
		echo "<option value='".implode(",", $values)."'>$label</option>";
	}
	echo "</select>";
}
echo "<input type='submit' style='display: none;' id='filterButton' value='Filter'>";
echo "<input type='hidden' name='s' value='' />";
echo "<input type='hidden' name='o' value='<?= $sort ?>' />";
echo "</form>";
echo "</div>";
echo "<div style='max-width: 400px; float: right;'>";
echo "<form style='margin-bottom: 0px;' method='get'>";
echo "<label for='searchTerms'>Search Abstracts:</label> <input type='text' id='searchTerms' name='searchTerms' value='' /> <button>Go!</button><br/>(Double quotes are permitted to search for an exact phrase.)";
echo "</form>";
echo "</div>";

?>
<script>
	function displayFilterButton() {
		var item = $('#award_type').val();
		var sel = $('#'+item).val();
		if (sel != "") {
			$('#filterButton').show();
		}
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
				</tbody>
				</table>
			</div>

			<div id="grants">
				<div><strong><?= $message ?></strong></div>
				<br/>
				<table id="grantsTable">
				<thead>
					<tr>
                        <?= isset($_GET['test']) ? "<th>Record</th>" : "" ?>
						<th>PI</th>
						<th>Grant Title</th>
						<th style="width: 150px;">Grant Date</th>
						<th>Grant #</th>
						<th>View</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$recordsWithAwards = [];
                    $recordsSeen = [];
					while ($row = db_fetch_assoc($grants)) {
                        $recordsSeen[] = sanitize($row['record']);
						$url = "download.php?p=$grantsProjectId&id=" .
							sanitize($row['file']) . "&s=&page=register_grants&record=" . sanitize($row['record']) . "&event_id=" .
							$eventId . "&field_name=grants_file";

						echo "<tr>";
                        if (isset($_GET['test'])) {
                            echo "<td>".sanitize($row['record'])."</td>";
                        }
						echo "<td style='white-space:nowrap;'>" . sanitize($row['pi']) . "</td>";
						echo "<td>" . sanitize($row['title']) . "</td>";
						echo "<td style='text-align: center;'>" . sanitize($row['date']) ."</td>";
						echo "<td style='white-space:nowrap;'>" . sanitize($row['number']) . "</td>";
						echo "<td style='text-align: center;'><a href='$url'>View</a></td>";
						echo "</tr>";
					}
					?>
				</tbody>
				</table>
			</div>
		</div>
        <?php
        if (isset($_GET['test'])) {
            $recordsMissing = [];
            foreach ($records as $recordId) {
                if (!in_array($recordId, $recordsSeen)) {
                    $recordsMissing[] = $recordId;
                }
            }
            echo "<p>Records Missing: ".implode(", ", $recordsMissing)."</p>";
        }
        ?>
    </body>
</html>

