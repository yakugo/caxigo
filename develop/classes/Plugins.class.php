<?php

namespace SocialKit;

class Plugins {
	public static function get_plugins($type) {
		if (! isset($_SESSION['addons'][$type])) {
			return array();
		}

		$get = $_SESSION['addons'][$type];

		if (! is_array($get)) {
			return array();
		}

		return $get;
	}

	public static function invoke($type, $args) {
		foreach (self::get_plugins($type) as $name) {
			$args = self::call($type, $name, $args);
		}

		return $args;
	}

	public static function register($type, $func) {
		if (is_array($func)) {
			$name = $func[0];
			$func_invalid = false;

			foreach ($func as $func_arg) {
				if (! preg_match('/[A-Za-z0-9_]/i', $func_arg)) {
					$func_invalid = true;
				}
			}
		} else {
			$name = $func;
			$func_invalid = (preg_match('/[A-Za-z0-9_]/i', $name)) ? false : true;
		}

		if (isset($_SESSION['addons'][$type][$name])) {
			return false;
		}
		
		if (! preg_match('/[A-Za-z0-9_]/i', $type) or $func_invalid) {
			return false;
		}

		$type = strtolower($type);
		$_SESSION['addons'][$type][$name] = $func;
	}

	public static function call() {
		$args = func_get_args();
		$type = $args[0];
		$func = $args[1];
		unset($args[0], $args[1]);

		return call_user_func_array($func, $args);
	}
}
