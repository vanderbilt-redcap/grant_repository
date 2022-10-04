<?php

namespace Vanderbilt\GrantRepositoryLibrary;

class Application {
	public static function log($mssg, $pid = "") {
		if ($pid) {
			error_log($pid.": ".$mssg);
		} else {
			error_log($mssg);
		}
	}

    public static function getCredentialsDir() {
        $options = [
            "/app001/credentials",
            "/Users/pearsosj/credentials",
            "/Users/scottjpearson/credentials",
        ];
        foreach ($options as $dir) {
            if (file_exists($dir)) {
                return $dir;
            }
        }
        return "";
    }
}
