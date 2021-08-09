<?php

namespace \Vanderbilt\GrantRepositoryLibrary\Application;

class Application {
	public static function log($mssg, $pid = "") {
		if ($pid) {
			error_log($pid.": ".$mssg);
		} else {
			error_log($mssg);
		}
	}
}
