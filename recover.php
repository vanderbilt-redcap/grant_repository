<?php

$records = array(
	"187 France DJ - The Impact of Non-Routine Events on Neonatal Safety in the Perioperative Environment",
);

foreach ($records as $record) {
	$sql = "SELECT * FROM redcap_log_event WHERE pk = '".db_real_escape_string($record)."' AND project_id = 27635";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		echo json_encode($row)."<br>";
	}
}
