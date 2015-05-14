<?php
function plugin_autoloader($conn) {
	if (isset($_SESSION['load_plugins']) && is_array($_SESSION['load_plugins'])) {
		foreach($_SESSION['load_plugins'] as $init_plugin) {
			$init = "plugins/" . $init_plugin . "/init.php";
			require $init;

		}
		return true;
	}

	if (!isset($_SESSION['addons'])) {
		$_SESSION['addons'] = array();
	}

	$_SESSION['load_plugins'] = array();
	$query = $conn->query("SELECT * FROM plugins WHERE active=1");

	if ($query->num_rows > 0) {
		while($fetch = $query->fetch_object()) {
			$init = "plugins/" . $fetch->folder . "/init.php";
			if (file_exists($init)) {
				require $init;
				$_SESSION['load_plugins'][] = $fetch->folder;
			}
		}
	}
}

plugin_autoloader($conn);