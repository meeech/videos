<?php
mysql_connect('localhost', 'videos5', 'videos5') or die("Can't connect to MySQL database.");
mysql_select_db('videos5') or die("Database 'videos5' not found, or unusable.");

$ratings_definitions = array(
	'Unrated' => '',
	'G' => 'General audience',
	'PG' => 'Parental guidance suggested',
	'PG-13' => 'Parents strongly cautioned',
	'R' => 'Restricted',
	'NC-17' => 'No one 17 and under admitted',
	'TV-Y' => 'All children',
	'TV-Y7' => 'Children 7 and older',
	'TV-G' => 'General audience',
	'TV-PG' => 'Parental guidance suggested',
	'TV-14' => 'May be unsuitable for children under 14',
	'TV-MA' => 'Mature audience'
);

$result = mysql_query("SELECT value FROM settings WHERE name = 'encode_extension'") or die("Error while querying DB: " . mysql_error());
if (mysql_num_rows($result) == 0) {
	die("Can't find encode_extension setting in database.");
}
$row = mysql_fetch_object($result);
$encode_extension = $row->value;

$paths = load_paths();

if (isset($argv)) {
	mysql_set_charset('utf8');
	if ($argc == 1 || $argv[1] == 'find') {
		set_time_limit(23*60*60); // 23h!
		find_videos();
	} else if ($argc > 2 && $argv[1] == 'identify') {
		$real_file = $argv[2];
		list($video_codec, $audio_codec, $width, $height, $extra_infos) = get_video_infos($real_file);
		echo "Video infos: video_codec: $video_codec, audio_codec: $audio_codec, width: $width, height: $height\n";
		list($html5_ready_browser, $html5_ready_mobile, $html5_ready_ipad, $html5_ready_android) = is_html5_ready($real_file, $video_codec, $audio_codec);
		echo "File is_html5_ready (browser)?: " . ($html5_ready_browser ? 'yes' : 'no') . "\n";
		echo "File is_html5_ready (mobile)?: " . ($html5_ready_mobile ? 'yes' : 'no') . "\n";
		echo "File is_html5_ready (iPad)?: " . ($html5_ready_ipad ? 'yes' : 'no') . "\n";
		echo "File is_html5_ready (Android)?: " . ($html5_ready_android ? 'yes' : 'no') . "\n";
		$rating = get_video_rating($real_file);
		echo "Rating: $rating\n";
		echo "Debug info: "; var_dump($extra_infos);
	} else if ($argc > 1 && $argv[1] == 'encode') {
		$already_running = exec('ps aux | grep "HandBrakeCLI" | grep -v grep | wc -l');
		if ($already_running) {
			#echo "Already encoding (HandBrakeCLI). Exiting.\n";
			exit(0);
		}
		$already_running = exec('ps aux | grep "index.php encode >>" | grep -v grep | wc -l');
		if ($already_running == 2) {
			#echo "Already encoding (index.php encode). Exiting.\n";
			exit(0);
		}
		set_time_limit(0); // No time limit! Encode forever...
		$result = mysql_query("SELECT value FROM settings WHERE name = 'encode_command'") or die("Error while querying DB: " . mysql_error());
		if (mysql_num_rows($result) == 0) {
			die("Can't find encode_command setting in database.");
		}
		$row = mysql_fetch_object($result);
		$encode_command = $row->value;
		
		while (TRUE) {
			$query = "SELECT * FROM files WHERE queued_for_encode != 'no' ORDER BY CAST(queued_for_encode AS UNSIGNED) LIMIT 1";
			$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
			if (mysql_num_rows($result) == 0) {
				break;
			}
			$row = mysql_fetch_object($result);

			if (!file_exists($row->path)) {
				$row->path = mb_convert_encoding($row->path, "UTF-8", "windows-1252");
			}
			if (is_dir($row->path)) {
				$output_file = $row->path . '.' . $encode_extension;
			} else {
				$output_file = substr($row->path, 0, strrpos($row->path, '.')) . '.' . $encode_extension;
			}

			$command = str_replace(array('$input', '$output'), array(quoted_form($row->path), quoted_form($output_file)), $encode_command);
			echo "Launching encode: $command\n";
			passthru($command);

			if (file_exists($output_file)) {
				echo "Encode complete. Resulting file: $output_file\n";
				list($video_codec, $audio_codec, $width, $height) = get_video_infos($output_file);
				echo "Resulting file video infos: video_codec: $video_codec, audio_codec: $audio_codec, width: $width, height: $height\n";

				list($html5_ready_browser, $html5_ready_mobile, $html5_ready_ipad, $html5_ready_android) = is_html5_ready($output_file, $video_codec, $audio_codec);
				echo "Resulting file is_html5_ready (browser)?: " . ($html5_ready_browser ? 'yes' : 'no') . "\n";
				echo "Resulting file is_html5_ready (mobile)?: " . ($html5_ready_mobile ? 'yes' : 'no') . "\n";
				echo "Resulting file is_html5_ready (iPad)?: " . ($html5_ready_ipad ? 'yes' : 'no') . "\n";
				echo "Resulting file is_html5_ready (Android)?: " . ($html5_ready_android ? 'yes' : 'no') . "\n";

				if ($html5_ready_browser && $html5_ready_mobile && $html5_ready_ipad && $html5_ready_android) {
					$query = sprintf("UPDATE files SET queued_for_encode = 'no' WHERE path = '%s'",
						mysql_escape_string($row->path)
					);
					mysql_query($query) or die("Error while updating DB: " . mysql_error());
					insert_video($output_file, $row->rating);
				} else {
					unlink($output_file);
				}
			} else {
				echo "Error: can't find output file ($output_file) after encode.";
			}
			sleep(5);
		}
	}
	exit(0);
}

if (isset($_GET['rr'])) {
	if (!is_dir($_GET['r'])) {
		$_GET['r'] = substr($_GET['r'], 0, strrpos($_GET['r'], '.'));
	}
	$query = sprintf("UPDATE files SET rating = '%s' WHERE path LIKE '%s%%'",
		mysql_escape_string($_GET['rr']),
		mysql_escape_string($_GET['r'])
	);
	mysql_query($query) or die("Error while updating rating: " . mysql_error());

	if (is_dir($_GET['r'])) {
		$query = sprintf("UPDATE dir_ratings SET rating = '%s' WHERE path LIKE '%s%%'",
			mysql_escape_string($_GET['rr']),
			mysql_escape_string('/' . trim($_GET['r'], '/'))
		);
		mysql_query($query) or die("Error while updating dir rating: " . mysql_error());
	}
	
	save_rating_nfo($_GET['rr'], $_GET['r']);
	
	echo 'OK';
	exit(0);
}

$user_id = null;
if (isset($_GET['uu'])) {
	$query = sprintf("SELECT * FROM users WHERE id = %d",
		mysql_escape_string($_GET['uu'])
	);
	$result = mysql_query($query) or die("Error while querying DB for user: " . mysql_error());
	$user = mysql_fetch_object($result);
	
	if (empty($user->password) || (isset($_POST['password']) && $_POST['password'] == $user->password)) {
		setcookie('videos5_user', $_GET['uu']);
		$user_id = $_GET['uu'];
	} else {
		unset($_COOKIE['videos5_user']);
	}

	if (empty($_GET['p'])) {
		$_GET['p'] = '/';
	}
} else if (empty($_COOKIE['videos5_user'])) {
	// No cookie? Go to user selection page.
	$_GET = array();
}

load_current_user($user_id);

if (isset($_GET['ee'])) {
	$parent_path = $_GET['ee'];
	
	$query = sprintf("SELECT * FROM files WHERE queued_for_encode = 'no' AND path LIKE '%s%%' ORDER BY path",
		mysql_escape_string($_GET['ee'])
	);
	$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
	$idle_files = array();
	while ($row = mysql_fetch_object($result)) {
		$idle_files[$row->path] = $row;
	}
	
	remove_duplicate_videos($idle_files);

	// Don't offer to encode universal or already queued videos
	$files_to_encode = array();
	foreach ($idle_files as $v => $infos) {
		if (!is_video_universal($infos->html5_ready) && $infos->queued_for_encode == 'no') {
			$files_to_encode[$v] = $infos;
		}
	}
	
	if (empty($_GET['go'])) {
		// Make sure apache can write in all dirs
		$missing_permissions = array();
		$checked_dirs = array();
		foreach ($files_to_encode as $v => $infos) {
			list($file_path, $filename) = explode_full_path($v);
			if (array_search($file_path, $checked_dirs) !== FALSE) {
				continue;
			}
			if (!file_exists($file_path)) {
				$file_path = mb_convert_encoding($file_path, "UTF-8", "windows-1252");
			}
			$fp = fopen($file_path . '/test.file', 'w');
			if ($fp) {
				fclose($fp);
				@unlink($file_path . '/test.file');
			} else {
				$missing_permissions[] = $file_path;
			}
			$checked_dirs[] = $file_path;
		}
	} else {
		foreach ($files_to_encode as $file_to_encode => $infos) {
			queue_video_for_encode($file_to_encode);
		}
	}
}
else if (isset($_GET['q'])) {
	$parent_path = $_GET['q'];
	
	$query = "SELECT * FROM files WHERE queued_for_encode != 'no' ORDER BY CAST(queued_for_encode AS UNSIGNED)";
	$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
	$queued_encodes = array();
	while ($row = mysql_fetch_object($result)) {
		$queued_encodes[] = $row->path;
	}
}
else if (isset($_GET['a'])) {
	$real_file = $_GET['a'];
	list($parent_path, $filename) = explode_full_path($real_file);
	$parent_path .= '/';

	$query = sprintf("SELECT * FROM files WHERE path = '%s'",
		mysql_escape_string($real_file)
	);
	$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
	if (mysql_num_rows($result) == 0) {
		die("Error: Couldn't find $real_file in the videos5.files database.");
	}
	$row = mysql_fetch_object($result);
}
else if (isset($_GET['s'])) {
	$real_file = $_GET['s'];
	list($parent_path, $filename) = explode_full_path($real_file);
	$parent_path .= '/';
	
	$query = sprintf("UPDATE files SET queued_for_encode = 'no' WHERE path = '%s'",
		mysql_escape_string($real_file)
	);
	mysql_query($query) or die("Error while updating DB: " . mysql_error());
	
	$kill_command = 'kill `ps ax | grep "HandBrakeCLI.*' . $real_file . '" | grep -v grep | awk \'{print $1}\'`';
	passthru($kill_command);
}
else if (isset($_GET['v'])) {
	$real_file = $_GET['v'];
	list($parent_path, $filename) = explode_full_path($real_file);
	$parent_path .= '/';
	
	$query = sprintf("SELECT * FROM files WHERE path = '%s'",
		mysql_escape_string($real_file)
	);
	$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
	if (mysql_num_rows($result) == 0) {
		die("Error: Couldn't find $real_file in the videos5.files database.");
	}
	$row = mysql_fetch_object($result);
	$web_file = symlink_video($real_file);
	$row->resolution = explode('x', $row->resolution);
	$row->width = (int) $row->resolution[0];
	$row->height = (int) $row->resolution[1];
	$current_rating = get_video_rating($_GET['v']);
}
else if (isset($_GET['e'])) {
	$real_file = $_GET['e'];
	list($parent_path, $filename) = explode_full_path($real_file);
	$parent_path .= '/';

	$missing_permission = TRUE;

	if (!file_exists($parent_path)) {
		$parent_path = mb_convert_encoding($parent_path, "UTF-8", "windows-1252");
	}
	$fp = fopen($parent_path . '/test.file', 'w');
	if ($fp) {
		fclose($fp);
		@unlink($parent_path . '/test.file');
		$missing_permission = FALSE;
		
		queue_video_for_encode($real_file);

		$query = sprintf("SELECT * FROM files WHERE path = '%s'",
			mysql_escape_string($real_file)
		);
		$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
		if (mysql_num_rows($result) == 0) {
			die("Error: Couldn't find $real_file in the videos5.files database.");
		}
		$row = mysql_fetch_object($result);
	}
} else if (isset($_GET['p']) && !isset($_GET['u'])) {
	$query = "SELECT MIN(CAST(queued_for_encode AS UNSIGNED)) AS min FROM files WHERE queued_for_encode != 'no'";
	$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
	if (mysql_num_rows($result) == 0) {
		$min_queued_for_encode = 1;
	} else {
		$row = mysql_fetch_object($result);
		$min_queued_for_encode = $row->min;
	}
	
	if ($_GET['p'] == '/') {
		$_GET['p'] = '';

		list($content_files, $content_dirs) = get_files_for_user('/');
		foreach ($content_files as $v => $infos) {
			foreach ($paths as $path) {
				if (strpos($infos->path, $path) === 0) {
					$content_dirs[$path] = $path;
					break;
				}
			}
		}
		$content_files = array();
		
		if (count($content_dirs) == 1) {
			header('Location: index.php?p=' . urlencode(array_pop($content_dirs)));
			exit(0);
		}
	} else {
		list($content_files, $content_dirs) = get_files_for_user($_GET['p']);
	}
	
	sort($content_dirs);
	ksort($content_files);
	
	remove_duplicate_videos($content_files);

	$all_files_encoded_or_queued = TRUE;
	foreach ($content_files as $v => $infos) {
		if (!is_video_universal($infos->html5_ready) && $infos->queued_for_encode == 'no') {
			$all_files_encoded_or_queued = FALSE;
		}
	}

	if (array_search($_GET['p'], $paths) !== FALSE) {
		$parent_path = '/';
	} else {
		list($parent_path, $dirname) = explode_full_path('/' . trim($_GET['p'], '/'));
		if ($parent_path != '') {
			$parent_path .= '/';
		}
	}
	
	$current_rating = get_directory_rating($_GET['p']);
} else {
	if (!isset($_GET['u']) || (is_numeric($_GET['u']) && $_GET['u'] == 0)) {
		setcookie('videos5_user', '', time() - 3600);
		$current_user = FALSE;
	}
	if (!isset($_GET['u'])) {
		$_GET['u'] = '/';
	}
	$parent_path = $_GET['u'];
	
	if (isset($_POST['save'])) {
		if ($_POST['user_id'] > 0) {
			$query = sprintf("UPDATE users SET name = '%s', password = %s, is_admin = '%s', allowed_ratings = '%s' WHERE id = %d",
				mysql_escape_string($_POST['name']),
				!empty($_POST['password']) ? "'".mysql_escape_string($_POST['password'])."'" : 'NULL',
				!empty($_POST['is_admin']) ? 'yes' : 'no',
				mysql_escape_string(implode(',', array_keys($_POST['ratings']))),
				mysql_escape_string($_POST['user_id'])
			);
		} else {
			$query = sprintf("INSERT INTO users (name, password, is_admin, allowed_ratings) VALUEs ('%s', %s, '%s', '%s')",
				mysql_escape_string($_POST['name']),
				!empty($_POST['password']) ? "'".mysql_escape_string($_POST['password'])."'" : 'NULL',
				!empty($_POST['is_admin']) ? 'yes' : 'no',
				mysql_escape_string(implode(',', array_keys($_POST['ratings'])))
			);
		}
		mysql_query($query) or die("Error while updating user: " . mysql_error());
	}
	else if (isset($_POST['delete'])) {
		$query = sprintf("DELETE FROM users WHERE id = %d",
			mysql_escape_string($_POST['user_id'])
		);
		mysql_query($query) or die("Error while deleting user: " . mysql_error());
	}
	
	$query = "SELECT * FROM users";
	if (is_numeric($_GET['u'])) {
		$query .= sprintf(" WHERE id = %d",
			mysql_escape_string($_GET['u'])
		);
	} else {
		$query .= " ORDER BY name";
	}
	$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
	$users = array();
	while ($row = mysql_fetch_object($result)) {
		$users[] = $row;
	}
}
?>
<?php header('Content-type: text/html; charset=utf-8') ?>
<!DOCTYPE HTML>
<html>
<head>
	<title>Videos5</title>
	<meta content="yes" name="apple-mobile-web-app-capable" />
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta content="minimum-scale=1.0, width=device-width, maximum-scale=0.6667, user-scalable=no" name="viewport" />
	<link href="images/homescreen.gif" rel="apple-touch-icon" />
	<link href="iwebkit/css/style.css" rel="stylesheet" media="screen" type="text/css" />
	<script src="iwebkit/javascript/functions.js" type="text/javascript"></script>
	<script type="text/javascript">
	function xmlhttpGet(strURL, strQueryString) {
		document.getElementById('rating_save_result').innerHTML = 'Saving new rating...';
		var xmlHttpReq = false;
		var self = this;
		if (window.XMLHttpRequest) { // Mozilla/Safari
			self.xmlHttpReq = new XMLHttpRequest();
		} else if (window.ActiveXObject) { // IE
			self.xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
		}
		self.xmlHttpReq.open('GET', strURL + '?' + strQueryString, true);
		self.xmlHttpReq.onreadystatechange = function() {
			if (self.xmlHttpReq.readyState == 4) {
				if (self.xmlHttpReq.responseText == 'OK') {
					document.getElementById('rating_save_result').innerHTML = 'New rating saved.';
				} else {
					document.getElementById('rating_save_result').innerHTML = 'Error while saving new rating: ' + self.xmlHttpReq.responseText;
				}
			}
		}
		self.xmlHttpReq.send();
	}
	</script>
	<style media="screen" type="text/css">
		li.store.directory a {
			background: lightblue;
		}
		li.store.directory:hover {
			background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#058CF5), to(#015FE6));
		}
		li.store.video.html5_ready a .arrow, li.store.video.ask a .arrow {
			background-position: right top;
			width: 70px !important;
			right: 10px;
			color: green;
			text-align: right;
			padding-right: 15px;
		}
		li.store.video.need_encode a .arrow {
			background-position: right top;
			width: 70px !important;
			right: 10px;
			color: red;
			text-align: right;
			padding-right: 15px;
		}
		li.store.video.encoding a .arrow {
			background: none;
			width: 160px !important;
			right: 10px;
			color: blue;
			text-align: right;
			padding-right: 15px;
		}
		li.store.video a:hover .arrow {
			background-position: right top !important;
			color: white;
		}
		li.store.video.encoding a .arrow, li.store.video.ask a .arrow {
			top: 25px !important;
			background-position: right 10px !important;
			height: 23px !important;
		}
		.roundbuttons {
			width: 210px !important;
		}
		.roundbuttons .store .name {
			max-width: 90%;
		}
		.red.button {
			background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #c38e8e), color-stop(0.5, #922f2f), color-stop(0.51, #7f0d0d), color-stop(1.0, #a33a10));
			border-radius: 8px 8px;
			-webkit-border-radius: 8px 8px;
		}
		.green.button {
			background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #8cc38c), color-stop(0.5, #349434), color-stop(0.51, #0d7f0c), color-stop(1.0, #3aa310));
			border-radius: 8px 8px;
			-webkit-border-radius: 8px 8px;
		}
		.green.button input, .red.button input {
			color: #FFF;
			border-radius: 8px 8px;
			-webkit-border-radius: 8px 8px;
		}
	</style>
</head>
<body>
	<div id="topbar" class="transparent">
		<?php if (!empty($_SERVER['QUERY_STRING']) && (count($paths) > 1 || $_GET['p'] != $paths[0])): ?>
			<div id="leftnav">
				<?php if (!isset($_GET['p']) || $_GET['p'] != ''): ?>
					<a href="?p=/"><img alt="Home" src="iwebkit/images/home.png" /></a>
				<?php endif; ?>
				<?php if ($current_user !== FALSE): ?>
					<?php if (isset($_GET['u']) && isset($_GET['edit'])): ?>
						<a href="?p=<?php echo urlencode($_GET['u']) ?>">Back</a>
					<?php elseif (isset($_GET['u']) && strpos($_SERVER['QUERY_STRING'], 'p=') !== FALSE): ?>
						<a href="?u=<?php echo urlencode($_GET['p']) ?>&amp;edit=1">Back</a>
					<?php elseif ($parent_path != ''): ?>
						<a href="?p=<?php echo urlencode($parent_path) ?>">Back</a>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<div id="<?php echo (isset($_GET['u']) && !is_numeric($_GET['u']) ? (isset($_GET['edit']) ? 'bluerightbutton' : 'rightbutton') : 'rightnav') ?>">
			<?php if (!empty($_SERVER['QUERY_STRING']) && (!isset($_GET['u']) || is_numeric($_GET['u']))): ?>
				<a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>">Log out</a>
			<?php endif; ?>
			<?php if (isset($_GET['u'])): ?>
				<?php if (is_numeric($_GET['u']) || $current_user === FALSE): ?>
				<?php elseif (isset($_GET['edit'])): ?>
					<a href="?p=<?php echo urlencode(isset($_GET['u']) ? $_GET['u'] : $parent_path) ?>">Done Editing</a>
				<?php else: ?>
					<a href="?u=<?php echo urlencode(isset($_GET['u']) ? $_GET['u'] : $parent_path) ?>&amp;edit=1">Edit</a>
				<?php endif; ?>
			<?php else: ?>
				<?php if (!isset($_GET['q']) && $current_user != FALSE && $current_user->is_admin == 'yes'): ?>
					<a href="?q=<?php echo urlencode(isset($_GET['p']) ? (empty($_GET['p']) ? '/' : $_GET['p']) : $parent_path) ?>">Encode Queue</a>
				<?php endif; ?>
				<a href="?u=<?php echo urlencode(isset($_GET['p']) ? (empty($_GET['p']) ? '/' : $_GET['p']) : $parent_path) ?><?php echo ($current_user != FALSE && $current_user->is_admin == 'yes' ? '&edit=1' : '') ?>"><?php echo ($current_user != FALSE && $current_user->is_admin == 'yes' ? 'Edit Users' : 'Switch User') ?></a>
			<?php endif; ?>
		</div>
		<div id="title">Videos5</div>
	</div>
	<?php if (isset($_GET['ee'])): ?>
		<div id="content">
			<?php if (!empty($_GET['go'])): ?>
				<div class="graytitle"><?php echo count($files_to_encode) ?> videos have been queued for encode.</div>
			<?php elseif (count($missing_permissions) > 0): ?>
				<div class="graytitle">
					<div style="color:red">Can't encode all videos.</div>
					<div><em>apache</em> user can't write in the following <?php echo (count($missing_permissions) == 1 ? 'directory' : 'directories') ?>:</div>
					<ul class="pageitem">
						<?php foreach ($missing_permissions as $directory): ?>
							<li class="smallfield">
								<span class="name"><?php echo $directory ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php else: ?>
				<div class="graytitle">Are you sure you want to encode the <?php echo count($files_to_encode) ?> following videos?</div>

				<ul style="width: 400px; height: 50px;">
					<li class="green button" style="float: left; width: 150px;">
						<input type="button" value="Encode All" onclick="window.location.href='?ee=<?php echo urlencode($_GET['ee']) ?>&amp;go=1'" />
					</li>
					<li class="red button" style="float: right; width: 150px;">
						<input type="button" value="Cancel" onclick="window.location.href='?p=<?php echo urlencode($_GET['ee']) ?>'" />
					</li>
				</ul>

				<?php $last_parent_path = FALSE; foreach ($files_to_encode as $v => $infos): list($parent_path, $filename) = explode_full_path($infos->path); ?>
					<?php if ($parent_path != $last_parent_path): ?>
						<?php if ($last_parent_path !== FALSE): ?>
							</ul>
						<?php endif; $last_parent_path = $parent_path; ?>
						<div class="graytitle"><?php echo $parent_path ?></div>
						<ul class="pageitem">
					<?php endif; ?>
						<li class="smallfield">
							<span class="name"><?php echo $filename ?></span>
						</li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
	<?php elseif (isset($_GET['uu']) && $current_user === FALSE): ?>
		<div id="content">
			<div class="graytitle">The <span style="color: blue"><?php echo $user->name ?></span> user profile is password protected.</div>
			<form method="post" action="?uu=<?php echo urlencode($_GET['uu']) ?>">
				<fieldset>
					<ul class="pageitem">
						<li class="smallfield">
							<span class="name">PIN</span>
							<input name="password" placeholder="Please enter the correct PIN" value="" type="number" />
						</li>
						<?php if (isset($_POST['password'])): ?>
							<li class="smallfield">
								<input placeholder="Invalid PIN entered" value="Invalid PIN entered" type="text" style="color: red" onfocus="blur()" />
							</li>
						<?php endif; ?>
					</ul>
					<ul class="pageitem">
						<li class="green button">
							<input name="login" type="submit" value="Login" />
						</li>
					</ul>
				</fieldset>
			</form>
		</div>
	<?php elseif (isset($_GET['u']) && !is_numeric($_GET['u'])): ?>
		<div id="content">
			<div class="graytitle"><?php echo (isset($_GET['edit']) ? 'Edit Users Profiles' : 'Select a User Profile') ?></div>
			<ul class="pageitem">
				<?php foreach ($users as $user):
					$url = isset($_GET['edit']) 
						? '?u=' . $user->id . '&amp;p=' . urlencode($_GET['u'])
						: '?uu=' . $user->id . '&amp;p=' . urlencode($_GET['u']); ?>
					<li class="store">
						<a href="<?php echo $url ?>">
							<span class="image" style="background: url('images/user.png') no-repeat center left;"></span>
							<span class="comment">
								<?php echo (!empty($user->password) ? 'Password protected; ' : '') ?>
								Can <span style="color: green">watch</span><?php echo ($user->is_admin == 'yes' ? ', <span style="color: red">encode</span> &amp; <span style="color: black">rate</span>' : '') ?>:
									<?php echo get_html_ratings($user->allowed_ratings) ?></span>
							<span class="name"><?php echo htmlentities($user->name) ?></span>
							<span class="arrow"></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if (isset($_GET['edit'])): ?>
				<ul class="pageitem">
					<li class="store">
						<a href="?u=0&amp;p=<?php echo urlencode($_GET['u']) ?>">
							<span class="name">Create New User...</span>
							<span class="arrow"></span>
						</a>
					</li>
				</ul>
			<?php endif; ?>
		</div>
	<?php elseif (isset($_GET['u']) && is_numeric($_GET['u'])): $user = $users[0]; ?>
		<div id="content">
			<div class="graytitle">Edit User Profile</div>
			<form method="post" action="?u=<?php echo urlencode($_GET['p']) ?>&amp;edit=1">
				<input name="user_id" type="hidden" value="<?php echo $user->id ?>" />
				<fieldset>
					<ul class="pageitem">
						<li class="smallfield">
							<span class="name">Name</span>
							<input name="name" placeholder="Name" value="<?php echo htmlentities($user->name) ?>" type="text" />
						</li>
						<li class="smallfield">
							<span class="name">PIN (Optional)</span>
							<input name="password" placeholder="No PIN" value="<?php echo htmlentities($user->password) ?>" type="number" />
						</li>
						<li class="checkbox">
							<span class="name">Is admin (can encode, edit users &amp; change ratings)?</span>
							<input name="is_admin" type="checkbox" <?php echo ($user->is_admin == 'yes' ? ' checked="checked"' : '') ?>/>
						</li>
					</ul>

					<span class="graytitle">Allowed Ratings</span>
					<ul class="pageitem">
						<?php foreach ($ratings_definitions as $rating => $definition): ?>
							<li class="checkbox">
								<span class="name"><?php echo htmlentities($rating) ?><?php echo !empty($definition) ? ': ' . htmlentities($definition) : '' ?></span>
								<input name="ratings[<?php echo htmlentities($rating) ?>]" type="checkbox" <?php echo (array_search($rating, explode(',', $user->allowed_ratings)) !== FALSE ? ' checked="checked"' : '') ?>/>
							</li>
						<?php endforeach; ?>
					</ul>
					<ul class="pageitem">
						<li class="button">
							<input name="save" type="submit" value="Save" />
						</li>
					</ul>
					<ul class="pageitem">
						<li class="red button">
							<input name="delete" type="submit" value="Delete" onclick="if (!confirm('Are you sure?')) { return false; }" />
						</li>
					</ul>
				</fieldset>
			</form>
		</div>
	<?php elseif (isset($_GET['q'])): ?>
		<div id="content">
			<div style="height: 30px">
				<div class="graytitle" style="float: left">Encode Queue</div>
				<div class="graytitle" style="float: left">
					<?php
					$total_filesize = 0; // bytes
					foreach ($queued_encodes as $q) {
						if (!file_exists($q)) {
							$q = mb_convert_encoding($q, 'UTF-8', 'windows-1252');
						}
						if ($total_filesize == 0) {
							$encoding_progress = get_encoding_progress($q, 0);
							if (preg_match('/Encoding: ([0-9\.]+)%.*ETA: (..)h(..)m(..)s/', $encoding_progress, $regs)) {
								$size_left = filesize($q) * (100-$regs[1]) / 100; // bytes
								$time_left = $regs[4] + (60*$regs[3]) + (60*60*$regs[2]); // seconds
								$avg_speed = $size_left/$time_left; // bytes/second
							}
						}
						$total_filesize += filesize($q);
					}
					if (isset($avg_speed) && count($queued_encodes) > 1) {
						$eta = $total_filesize / $avg_speed;
						$eta_end_time = date('Y-m-d H:i', time() + $eta);

						$eta_hours = floor($eta/3600);
						$eta -= ($eta_hours * 3600);
						$eta_minutes = floor($eta/60);
						$eta -= ($eta_minutes * 60);
						$eta_seconds = $eta;

						printf(" - ETA %02dh%02dm%02ds (%s)", $eta_hours, $eta_minutes, $eta_seconds, $eta_end_time);
					}
					?>
				</div>
				<div class="graytitle" style="float: right; margin-right: 30px">Sysload <?php echo implode(', ', explode(' ', trim(exec("uptime | awk -F',' '{print \$4 \$5 \$6}' | awk -F':' '{print \$2}'")))) ?></div>
			</div>
			<?php if (count($queued_encodes) == 0): ?>
				<div class="graytitle"><br/>You don't have any videos in the queue.</div>
			<?php else: ?>
				<ul class="pageitem">
					<?php
					$i = 0;
					foreach ($queued_encodes as $q) {
						list($parent_path, $filename) = explode_full_path($q);
						$parent_path .= '/';
					
						$real_thumb = get_file_thumb($q);
						$thumb_file = symlink_thumb($real_thumb, 'video');
						list($thumb_width, $thumb_height) = get_thumb_resolution($thumb_file);
					
						$encoding_progress = get_encoding_progress($q, $i++);
						?>
						<li class="store video encoding">
							<a href="?e=<?php echo urlencode($q) ?>">
								<span class="image" style="background: url('/<?php echo $thumb_file ?>') no-repeat center left; -webkit-background-size: <?php echo $thumb_width ?>px <?php echo $thumb_height ?>px;"></span>
								<span class="comment"><?php echo htmlentities($parent_path) ?></span>
								<span class="name"><?php echo htmlentities($filename) ?></span>
								<span class="arrow"><?php echo $encoding_progress ?></span>
							</a>
						</li>
					<?php } ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php elseif (isset($_GET['s'])): ?>
		<div id="content">
			<center>
				<div class="graytitle">Encode of <?php echo htmlentities($_GET['s']) ?> has been canceled.</div>
			</center>
		</div>
	<?php elseif (isset($_GET['a'])): ?>
		<div id="content">
			<center>
				<div class="graytitle"><?php echo htmlentities($_GET['a']) ?></div>
				<div class="graytitle"><br/>Even though this video should play correctly here, it's format makes it incompatible with some devices.<br/><br/></div>
				<ul class="pageitem roundbuttons" id="roundbuttons_list">
					<li class="store">
						<a href="?v=<?php echo urlencode($_GET['a']) ?>">
							<span class="name" style="color: green">Play</span>
						</a>
					</li>
					<li class="store">
						<a href="?e=<?php echo urlencode($_GET['a']) ?>">
							<span class="name" style="color: <?php echo ($row->queued_for_encode != 'no' ? 'blue' : 'red') ?>; left: 30px"><?php echo ($row->queued_for_encode != 'no' ? 'Queued for encoding...' : 'Encode for all devices') ?></span>
						</a>
					</li> 
				</ul>
				<?php if (preg_match('/linux.*chrome/i', $_SERVER['HTTP_USER_AGENT'])): /* For some reason, Chrome on Linux has bigger font in those buttons... So they need to be bigger to show all the text! */ ?>
					<script type="text/javascript">
						document.getElementById('roundbuttons_list').style.width = '240px !important';
					</script>
				<?php endif; ?>
			</center>
		</div>
	<?php elseif (isset($_GET['e'])): ?>
		<div id="content">
			<center>
				<?php if ($missing_permission): ?>
					<div class="graytitle">
						<div style="color:red">Can't encode <?php echo htmlentities($_GET['e']) ?>:</div>
						<div><em>apache</em> user can't write in this directory.</div>
					</div>
				<?php else: ?>
					<div class="graytitle">Encode of <?php echo htmlentities($_GET['e']) ?> has been queued.</div>
					<div class="graytitle"><br/><a href="?s=<?php echo urlencode($_GET['e']) ?>">Stop &amp; Cancel</a> this encode.</div>
				<?php endif; ?>
			</center>
		</div>
	<?php elseif (isset($_GET['v'])): ?>
		<div id="content">
			<center>
				<div class="graytitle">Now Playing: <?php echo htmlentities($_GET['v']) ?></div>
				<?php show_rating_widget($row->rating, 'file') ?>
				<video id="video" width="<?php echo $row->width ?>" height="<?php echo $row->height ?>" autobuffer autoplay controls>
					<source src="<?php echo $web_file ?>" />
				</video>
				<script type="text/javascript">
					window.onload = function() { document.getElementById('video').play(); };
					var browser_width = document.documentElement.clientWidth;
					if (document.getElementById('video').width > browser_width) {
						document.getElementById('video').width = browser_width;
					}
				</script>
			</center>
		</div>
	<?php else: ?>
	<div id="content">
		<span class="graytitle"><?php echo htmlentities($_GET['p']) ?></span>
		<?php show_rating_widget($current_rating, 'dir') ?>
		
		<?php if ($current_user != FALSE && $current_user->is_admin == 'yes' && !$all_files_encoded_or_queued): ?>
			<ul class="pageitem">
				<li class="red button">
					<input type="button" value="Encode All..." onclick="window.location.href='?ee=<?php echo urlencode($_GET['p']) ?>'" />
				</li>
			</ul>
		<?php endif; ?>
		
		<ul class="pageitem">
			<?php foreach ($content_dirs as $v): ?>
				<?php
				$real_dir = $_GET['p'] . $v;
				$real_thumb = $real_dir . 'folder.jpg';
				$thumb_file = symlink_thumb($real_thumb, 'folder');
				list($thumb_width, $thumb_height) = get_thumb_resolution($thumb_file);
				?>
				<li class="store directory">
					<a href="?p=<?php echo urlencode($_GET['p'] . $v) ?>">
						<span class="image" style="background: url('/<?php echo $thumb_file ?>') no-repeat center left; -webkit-background-size: <?php echo $thumb_width ?>px <?php echo $thumb_height ?>px;"></span>
						<span class="comment">[Directory]</span>
						<span class="name"><?php echo htmlentities($v) ?></span>
						<span class="arrow"></span>
					</a>
				</li> 
			<?php endforeach; ?>
			<?php foreach ($content_files as $v => $infos): ?>
				<?php
				$real_file = $_GET['p'] . $v;
				$real_thumb = get_file_thumb($real_file);
				$thumb_file = symlink_thumb($real_thumb, 'video');
				list($thumb_width, $thumb_height) = get_thumb_resolution($thumb_file);

				if ($infos->queued_for_encode != 'no') {
					$encoding_progress = get_encoding_progress($real_file, $infos->queued_for_encode - $min_queued_for_encode);
				}

				$display_infos = '';
				if ($infos->video_codec != 'unknown' && !empty($infos->video_codec)) {
					$vinfos = array();
					foreach (explode('+', $infos->video_codec) as $vcodec) {
						$vinfos[] = to_human_codec($vcodec);
					}
					$display_infos .= implode(' + ', $vinfos);

					$infos->resolution = explode('x', $infos->resolution);
					$infos->width = $infos->resolution[0];
					$infos->height = $infos->resolution[1];
					if ($infos->width != 0 && $infos->height != 0) {
						if ($infos->width >= 1920 || $infos->height == 1080) {
							$display_infos .= " (HD 1080p)";
						}
						else if ($infos->width >= 1280 || $infos->height == 720) {
							$display_infos .= " (HD 720p)";
						}
						else {
							$display_infos .= " ($infos->width".'x'."$infos->height)";
						}
					}
				}
				if ($infos->audio_codec != 'unknown' && !empty($infos->audio_codec)) {
					if ($infos->video_codec != 'unknown' && !empty($infos->video_codec)) {
						$display_infos .= ', ';
					}

					$ainfos = array();
					foreach (explode('+', $infos->audio_codec) as $acodec) {
						$ainfos[] = to_human_codec($acodec);
					}
					$display_infos .= implode(' + ', $ainfos) . ' audio';
				}
				$ext = substr($v, strrpos($v, '.')+1);
				if (is_dir($real_file)) {
					$display_infos .= ', VIDEO_TS (DVD) container';
				}
				else if ($ext == 'mkv') {
					$display_infos .= ', MKV container';
					$v = substr($v, 0, strrpos($v, '.'));
				}
				else if ($ext == 'avi') {
					$display_infos .= ', AVI container';
					$v = substr($v, 0, strrpos($v, '.'));
				}
				else if ($ext == 'm4v' || strpos($ext, 'mp4') !== FALSE) {
					$display_infos .= ', MPEG-4 container';
					$v = substr($v, 0, strrpos($v, '.'));
				}
				else if (strpos($ext, 'mov') !== FALSE) {
					$display_infos .= ', MOV container';
					$v = substr($v, 0, strrpos($v, '.'));
				}
				
				$infos->universal = is_video_universal($infos->html5_ready);
				$infos->html5_playable_here = is_video_playable_here($infos->html5_ready);
				?>
				<?php if ($infos->queued_for_encode != 'no' || !$infos->html5_playable_here): ?>
					<li class="store video <?php echo ($infos->queued_for_encode != 'no' ? 'encoding' : 'need_encode') ?>">
						<a href="?<?php echo ($infos->html5_playable_here ? 'a' : 'e')?>=<?php echo urlencode($real_file) ?>">
							<span class="image" style="background: url('/<?php echo $thumb_file ?>') no-repeat center left; -webkit-background-size: <?php echo $thumb_width ?>px <?php echo $thumb_height ?>px;"></span>
							<span class="comment"><?php echo $display_infos ?></span>
							<span class="name"><?php echo htmlentities($v) ?></span>
							<span class="arrow"><?php echo ($infos->queued_for_encode != 'no' ? $encoding_progress : 'Encode') ?></span>
						</a>
					</li>
				<?php elseif ($infos->html5_playable_here && !$infos->universal): ?>
					<li class="store video ask">
						<a href="?a=<?php echo urlencode($real_file) ?>">
							<span class="image" style="background: url('/<?php echo $thumb_file ?>') no-repeat center left; -webkit-background-size: <?php echo $thumb_width ?>px <?php echo $thumb_height ?>px;"></span>
							<span class="comment"><?php
							echo $display_infos;
							$devices = array();
							foreach (explode(',', $infos->html5_ready) as $d) {
								if ($d == 'browser') {
									$devices[] = 'WebKit browser';
								}
								else if ($d == 'mobile') {
									$devices[] = 'iPhone, iPod Touch';
								}
								else if ($d == 'ipad') {
									$devices[] = 'iPad';
								}
								else if ($d == 'android') {
									$devices[] = 'Android';
								}
							}
							echo " - Compatible with: " . implode(', ', $devices);
							?></span>
							<span class="name"><?php echo htmlentities($v) ?></span>
							<span class="arrow">Play or Encode</span>
						</a>
					</li>
				<?php elseif ($infos->html5_playable_here /* && $infos->universal */): ?>
					<li class="store video html5_ready">
						<a href="?v=<?php echo urlencode($real_file) ?>">
							<span class="image" style="background: url('/<?php echo $thumb_file ?>') no-repeat center left; -webkit-background-size: <?php echo $thumb_width ?>px <?php echo $thumb_height ?>px;"></span>
							<span class="comment"><?php echo $display_infos ?></span>
							<span class="name"><?php echo htmlentities($v) ?></span>
							<span class="arrow">Play</span>
						</a>
					</li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul> 
	</div> 
	<?php endif; ?>
	<div id="footer"> 
		<a href="http://iwebkit.net">Powered by iWebKit</a>
	</div> 
</body>
</html>
<?php

function explode_full_path($full_path) {
	if (strpos($full_path, '/') === FALSE) {
		return array('', $full_path);
	}
	$filename = substr($full_path, strrpos($full_path, '/')+1);
	$path = substr($full_path, 0, strrpos($full_path, '/'));
	return array($path, $filename);
}

function quoted_form($path) {
	return "'" . str_replace("'", "'\\''", $path) . "'";
}

function symlink_video($real_file) {
	if (!is_dir('videos')) {
		mkdir('videos');
	}
	if (!file_exists($real_file)) {
		$real_file = mb_convert_encoding($real_file, "UTF-8", "windows-1252");
	}
	$ext = substr($real_file, strrpos($real_file, '.')+1);
	if ($ext == 'amp4' || $ext == 'm4v' || strpos($ext, 'mp4') !== FALSE) { $ext = 'mp4'; }
	if (strpos($ext, 'mov') !== FALSE) { $ext = 'mov'; }
	$web_file = 'videos/' . md5($_SERVER['REMOTE_ADDR']) . '.' . $ext;
	@unlink($web_file);
	symlink($real_file, $web_file);
	return $web_file;
}

function symlink_thumb($real_file, $type) {
	if (!is_dir('thumbs')) {
		mkdir('thumbs');
	}
	$thumb_file = "images/$type.png";
	
	if (!file_exists($real_file)) {
		$real_file = mb_convert_encoding($real_file, "UTF-8", "windows-1252");
	}
	if (file_exists($real_file)) {
		$thumb_file = 'thumbs/' . md5($real_file) . '.jpg';
		@unlink($thumb_file);
		symlink($real_file, $thumb_file);
	}
	return $thumb_file;
}

function get_thumb_resolution($thumb_file) {
	$thumb_size = getimagesize($thumb_file);
	$thumb_width = $thumb_size[0];
	$thumb_height = $thumb_size[1];
	if ($thumb_width > $thumb_height) {
		$ratio = $thumb_width / 90;
		$thumb_width = 90;
		$thumb_height = round($thumb_height / $ratio);
	} else {
		$ratio = $thumb_height / 90;
		$thumb_height = 90;
		$thumb_width = round($thumb_width / $ratio);
		if ($thumb_width < 62) {
			$thumb_width = 62;
		}
	}
	return array($thumb_width, $thumb_height);
}

function get_video_extensions() {
	global $encode_extension;
	$result = mysql_query("SELECT value FROM settings WHERE name = 'video_extensions'") or die("Error while querying DB: " . mysql_error());
	if ($row = mysql_fetch_object($result)) {
		$extensions = explode(",", $row->value);
	} else {
		$extensions = array('m4v', 'mp4', 'ts', 'mov', 'divx', 'xvid', 'vob', 'm2v', 'avi', 'mpg', 'mpeg', 'mkv', 'm2t', 'm2ts');
	}
	if (array_search($encode_extension, $extensions) === FALSE) {
		$extensions[] = $encode_extension;
	}
	return $extensions;
}

function load_paths() {
	$result = mysql_query("SELECT value FROM settings WHERE name = 'paths'") or die("Error while querying DB: " . mysql_error());
	if ($row = mysql_fetch_object($result)) {
		$paths = explode("\n", $row->value);
		foreach ($paths as $k => $path) {
			$paths[$k] = '/' . trim(trim($path), '/') . '/';
		}
	} else {
		$paths = array('/var/hda/files/movies/');
	}
	return $paths;
}

function find_videos() {
	global $paths;
	mysql_query("UPDATE files SET found = 'no'") or die("Error while update found flag DB: " . mysql_error());
	foreach ($paths as $path) {
		$extensions = get_video_extensions();
		$extensions = '-name "*.' . implode('" -o -name "*.', $extensions) . '"';

		$command = 'find ' . quoted_form($path) . ' \( ' . $extensions . ' -o -name "VIDEO_TS" -o \( -name "VIDEO_TS.IFO" -a ! -wholename "*/VIDEO_TS/VIDEO_TS.IFO" \) \)';
		#echo "$command\n";
		exec($command, $videos);
		foreach ($videos as $real_file) {
			if (strpos($real_file, "/._") !== FALSE) { continue; }
			insert_video($real_file);
		}
		unset($videos);
	}
	mysql_query("DELETE FROM files WHERE found = 'no'") or die("Error while update found flag DB: " . mysql_error());
}

function insert_video($real_file, $rating=null) {
	echo "$real_file\n";
	list($video_codec, $audio_codec, $width, $height) = get_video_infos($real_file);
	if ($width == 0 && $height == 0) {
		// Maybe it's currently encoding?
		if (strpos(get_encoding_progress($real_file, 1), 'Encoding') !== FALSE) {
			return;
		}
	}
	list($html5_ready_browser, $html5_ready_mobile, $html5_ready_ipad, $html5_ready_android) = is_html5_ready($real_file, $video_codec, $audio_codec);
	$html5_ready = array();
	if ($html5_ready_browser) {
		$html5_ready[] = 'browser';
	}
	if ($html5_ready_mobile) {
		$html5_ready[] = 'mobile';
	}
	if ($html5_ready_ipad) {
		$html5_ready[] = 'ipad';
	}
	if ($html5_ready_android) {
		$html5_ready[] = 'android';
	}
	
	$query = sprintf("SELECT * FROM files WHERE path = '%s'",
		mysql_escape_string($real_file)
	);
	$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_object($result);
		$queued_for_encode = $row->queued_for_encode;
	} else {
		$queued_for_encode = 'no';
	}
	if (empty($rating)) {
		$rating = get_video_rating($real_file);
	}

	$query = sprintf("DELETE FROM files WHERE path = '%s' LIMIT 1",
		mysql_escape_string($real_file)
	);
	mysql_query($query) or die("Can't delete from files table: " . mysql_error());

	$query = sprintf("INSERT INTO files (path, video_codec, audio_codec, html5_ready, resolution, queued_for_encode, rating) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', %s)",
		mysql_escape_string($real_file),
		mysql_escape_string($video_codec),
		mysql_escape_string($audio_codec),
		implode(',', $html5_ready),
		$width . 'x' . $height,
		$queued_for_encode,
		empty($rating) || $rating == 'Unrated' ? 'NULL' : "'" . mysql_escape_string($rating) . "'"
	);
	mysql_query($query) or die("Can't insert in files table: " . mysql_error());
	
	global $paths;
	list($parent_path, $filename) = explode_full_path($real_file);
	$i = 0;
	while (array_search("$parent_path/", $paths) === FALSE && $i++ < 100) {
		$query = sprintf("INSERT INTO dir_ratings (path) VALUES ('%s')",
			mysql_escape_string($parent_path)
		);
		@mysql_query($query); // Will fail on duplicate PK; I'm fine with that.
		list($parent_path, $dirname) = explode_full_path($parent_path);
	}
}

function get_video_infos(&$real_file) {
	if (strpos($real_file, 'VIDEO_TS') == strlen($real_file) - 8) {
		$command = '/usr/bin/mplayer -identify -dvd-device ' . quoted_form($real_file) . ' dvd://1 -vo null -ao null -frames 0 2>&1 | grep -m 50 "AUDIO\|VIDEO\|mplayer\|fail\|audio stream"';
		exec($command, $infos);
	} else if (strpos($real_file, 'VIDEO_TS.IFO') == strlen($real_file) - 12) {
		$real_file = substr($real_file, 0, strlen($real_file) - 13);
		$command = '/usr/bin/mplayer -identify -dvd-device ' . quoted_form($real_file) . ' dvd://1 -vo null -ao null -frames 0 2>&1 | grep -m 50 "AUDIO\|VIDEO\|mplayer\|fail\|audio stream"';
		exec($command, $infos);
	} else {
		$command = 'export LANG="en_US.UTF-8"; /usr/bin/mediainfo ' . quoted_form($real_file) . ' | grep "Video\|Audio\|Format\|Width\|Height\|^Text\|Language"';
		exec($command, $infos);
		if (count($infos) == 0) {
			$command = '/usr/bin/mediainfo ' . quoted_form(mb_convert_encoding($real_file, "UTF-8", "windows-1252")) . ' | grep "Video\|Audio\|Format\|Width\|Height\|^Text\|Language"';
			exec($command, $infos);
			if (count($infos) == 0) {
				$command = '/usr/bin/mplayer -identify ' . quoted_form($real_file) . ' -vo null -ao null -frames 0 2>&1 | grep -m 50 "AUDIO\|VIDEO\|mplayer\|fail"';
				exec($command, $infos);
			}
		}
	}
	$width = $height = 0;
	$video_codec = $audio_codec = '';
	$mode = 'container';
	foreach ($infos as $info) {
		// MPlayer
		if (strpos($info, 'ID_VIDEO_WIDTH') === 0) {
			$width = (int) substr($info, strpos($info, '=')+1);
		}
		else if (strpos($info, 'ID_VIDEO_HEIGHT') === 0) {
			$height = (int) substr($info, strpos($info, '=')+1);
		}
		else if (strpos($info, 'ID_VIDEO_CODEC') === 0) {
			$video_codec = substr($info, strpos($info, '=')+1);
		}
		else if (strpos($info, 'ID_AUDIO_CODEC') === 0) {
			$audio_codec = substr($info, strpos($info, '=')+1);
		}
		else if (strpos($info, 'audio stream') === 0) {
			$audio_codec = substr($info, strpos($info, 'format: ')+8);
			$audio_codec = substr($audio_codec, 0, strpos($audio_codec, '('));
			$audio_codec = substr($audio_codec, 0, strpos($audio_codec, ' '));
		}
		// MediaInfo
		else if (strpos($info, 'Width') === 0) {
			$width = (int) str_replace(array(' pixels', ' '), '', substr($info, strpos($info, ':')+2));
		}
		else if (strpos($info, 'Height') === 0) {
			$height = (int) str_replace(array(' pixels', ' '), '', substr($info, strpos($info, ':')+2));
		}
		else if (strpos($info, 'Video') === 0) {
			$mode = 'Video';
		}
		else if (strpos($info, 'Audio') === 0) {
			$mode = 'Audio';
		}
		else if (strpos($info, 'Text') === 0) {
			$mode = 'Text';
		}
		else if (strpos($info, 'Format  ') === 0) {
			if ($mode == 'Video') {
				$info = substr($info, strpos($info, ':')+2);
				if ($info == 'SRT' || $info == 'ASS') {
					$mode = 'Text';
					continue;
				}
				$video_codec = $info;
			} else if ($mode == 'Audio') {
				if ($audio_codec != '') {
					$audio_codec .= '+';
				}
				$audio_codec .= substr($info, strpos($info, ':')+2);
			}
		}
		else if (strpos($info, 'Format version') === 0) {
			if ($mode == 'Video') {
				$video_codec .= ' v.' . str_replace('Version ', '', substr($info, strpos($info, ':')+2));
			}
		}
		else if (strpos($info, 'Format profile') === 0) {
			if ($mode == 'Video') {
				$video_codec .= ' ' . substr($info, strpos($info, ':')+2);
			} else if ($mode == 'Audio') {
				$audio_codec .= ' ' . substr($info, strpos($info, ':')+2);
			}
		}
		else if (strpos($info, 'Language') === 0) {
			if ($mode == 'Audio') {
				$lang = substr($info, strpos($info, ':')+2);
				if ($lang != 'English') {
					$audio_codec .= ' (' . substr($info, strpos($info, ':')+2) . ')';
				}
			}
		}
	}
	if ($video_codec == '') {
		$video_codec == 'unknown';
	}
	if ($audio_codec == '') {
		$audio_codec == 'unknown';
	}
	return array($video_codec, $audio_codec, $width, $height, $infos);
}

function is_html5_ready($real_file, $video_codec, $audio_codec) {
	$ext = substr($real_file, strrpos($real_file, '.')+1);

	// H.264 or MPEG-4 video
	$browser = to_human_codec($video_codec) == 'H.264' || to_human_codec($video_codec) == 'MPEG-4';
	
	// MP4 or MOV container (Android doesn't support MOV; we'll handle that below...)
	$browser &= (strpos($ext, 'mp4') !== FALSE || strpos($ext, 'm4v') !== FALSE || strpos($ext, 'mov') !== FALSE);
	
	// AAC or MP3 audio (or no audio!)
	$browser &= empty($audio_codec) || strpos($audio_codec, 'AAC') === 0 || strpos($audio_codec, 'MPEG Audio Layer 3') === 0;

	// Mobile (iPhone/iPod Touch) only support Baseline profile, up to level 3.0, with AAC-LC audio
	$mobile = $browser 
		&& (strpos($video_codec, 'Baseline@L1') !== FALSE 
			|| strpos($video_codec, 'Baseline@L2') !== FALSE 
			|| strpos($video_codec, 'Baseline@L3.0') !== FALSE 
			|| strpos($video_codec, 'Simple@') !== FALSE
			|| $video_codec == 'MPEG-4 Visual')
		&& (strpos($audio_codec, 'AAC LC') !== FALSE || empty($audio_codec));

	// Android doesn't support MOV
	$android = $mobile && strpos($ext, 'mov') === FALSE;

	// iPad only support Baseline profile, up to level 3.1, with AAC-LC audio
	$ipad = $browser 
		&& (strpos($video_codec, 'Baseline@L1') !== FALSE 
			|| strpos($video_codec, 'Baseline@L2') !== FALSE 
			|| strpos($video_codec, 'Baseline@L3.0') !== FALSE 
			|| strpos($video_codec, 'Baseline@L3.1') !== FALSE 
			|| strpos($video_codec, 'Main@L1') !== FALSE 
			|| strpos($video_codec, 'Main@L2') !== FALSE 
			|| strpos($video_codec, 'Main@L3.0') !== FALSE 
			|| strpos($video_codec, 'Main@L3.1') !== FALSE 
			|| strpos($video_codec, 'High@L3.0') !== FALSE 
			|| strpos($video_codec, 'High@L3.1') !== FALSE 
			|| strpos($video_codec, 'Simple@') !== FALSE
			|| $video_codec == 'MPEG-4 Visual')
		&& (strpos($audio_codec, 'AAC LC') !== FALSE || empty($audio_codec));

	return array($browser, $mobile, $ipad, $android);
}

function to_human_codec($codec) {
	// MPlayer
	if ($codec == 'ffdivx') return 'MPEG-4 DivX';
	if ($codec == 'ffodivx') return 'MPEG-4';
	if ($codec == 'ffdv') return 'DV';
	if ($codec == 'ffh264') return 'H.264';
	if ($codec == 'ffvc1') return 'VC-1';
	if ($codec == 'ffwmv3') return 'WMV';
	if ($codec == 'mpegpes') return 'MPEG-2';
	// Audio
	if ($codec == 'a52') return 'AC-3';
	if ($codec == 'faad') return 'AAC';
	if ($codec == 'ffadpcmimaqt') return 'ADPCM';
	if ($codec == 'ffvorbis') return 'Ogg Vorbis';
	// MediaInfo
	if ($codec == 'DX50') return 'MPEG-4 DivX';
	if ($codec == 'FMP4') return 'MPEG-4';
	if (strtoupper($codec) == 'XVID') return 'MPEG-4 XviD';
	if (strpos($codec, 'AVC') !== FALSE) return 'H.264';
	if (strpos($codec, 'MPEG Video v.1') !== FALSE) return 'MPEG-1';
	if (strpos($codec, 'MPEG Video v.2') !== FALSE) return 'MPEG-2';
	if (strpos($codec, 'MPEG-4 Visual') !== FALSE) return 'MPEG-4';
	if (strpos($codec, 'VC-1') !== FALSE) return 'VC-1';
	// Audio
	if ($codec == 'MPEG Audio') return 'MPEG';
	if ($codec == 'MPEG Audio Layer 2') return 'MP2';
	if ($codec == 'MPEG Audio Layer 3') return 'MP3';
	if ($codec == 'Vorbis') return 'Ogg Vorbis';
	return strtoupper($codec);
}

function get_encoding_progress($real_file, $queue_position) {
	if (!file_exists($real_file)) {
		$real_file = mb_convert_encoding($real_file, "UTF-8", "windows-1252");
	}
	$command = "grep 'Launching encode' ../logs/encode.log | tail -1";
	exec($command, $results);
	if (preg_match('/ -i (.*) -o /', $results[0], $regs)) {
		$input_file = str_replace("'\\''", "'", trim($regs[1], "'"));
		unset($results);
		if ($real_file == $input_file) {
			$command = "grep 'Encoding: task ' ../logs/encode.log | tail -1";
			exec($command, $results);
			$results = explode("\r", $results[0]);
			$result = array_pop($results);
			if (preg_match('/Encoding: task [0-9]+ of [0-9]+, ([0-9\.]+) % \(([0-9\.]+) fps, avg ([0-9\.]+) fps, ETA ([0-9dhms]+)\)/', $result, $regs)) {
				$percentage = $regs[1];
				$eta = $regs[4];
				return "Encoding: $percentage%<br/>ETA: $eta";
			}
			return "Encoding...";
		}
	}
	return "Queued (#$queue_position)<br/>for encoding...";
}

function is_video_universal($html5_ready) {
	$html5_ready = explode(',', $html5_ready);

	$devices_supported = array('browser', 'mobile', 'ipad', 'android');
	global $ignored_devices;
	if (!isset($ignored_devices)) {
		$ignored_devices = array();
		$result = mysql_query("SELECT value FROM settings WHERE name = 'ignored_devices'") or die("Error while querying DB: " . mysql_error());
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_object($result);
			$ignored_devices = explode(',', $row->value);
		}
	}
	$devices_supported = array_diff($devices_supported, $ignored_devices);

	return count(array_diff($devices_supported, $html5_ready)) === 0;
}

function is_video_playable_here($html5_ready) {
	return strpos($html5_ready, what_is_here()) !== FALSE;
}

function what_is_here() {
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	if (preg_match('/ipad/i', $user_agent)) {
		return 'ipad';
	}
    if (preg_match('/ipod/i', $user_agent) || preg_match('/iphone/i', $user_agent)) {
		return 'mobile';
	}
	if (preg_match('/android/i', $user_agent)) { 
		return 'android';
	}
	if (preg_match('/applewebkit/i', $user_agent)) { 
		return 'browser';
	}
	return 'unknown';
}

function get_file_thumb($real_file) {
	if (is_dir($real_file)) {
		if (file_exists($real_file . '.tbn')) {
			$real_thumb = $real_file . '.tbn';
		} else {
			$real_thumb = $real_file . '/folder.jpg';
		}
	} else {
		$real_thumb = substr($real_file, 0, strrpos($real_file, '.')) . '.tbn';
	}
	return $real_thumb;
}

function get_html_ratings($ratings) {
	global $ratings_definitions;
	if ($ratings == implode(',', array_keys($ratings_definitions))) {
		return 'Everything';
	}
	return htmlentities(implode(', ', explode(',', $ratings)));
}

function load_current_user($user_id = null) {
	global $current_user;
	if ($user_id === null) {
		if (!isset($_COOKIE['videos5_user'])) {
			$current_user = FALSE;
			return;
		}
		$user_id = $_COOKIE['videos5_user'];
	}
	$query = sprintf("SELECT * FROM users WHERE id = %d",
		mysql_escape_string($user_id)
	);
	$result = mysql_query($query) or die("Error while querying DB for current user: " . mysql_error());
	$current_user = mysql_fetch_object($result);
}

function get_directory_rating($real_dir) {
	$real_file = $real_dir . "dummy_file.no";
	return get_rating(get_nfo_files($real_file), $real_file);
}

function get_video_rating($real_file) {
	return get_rating(get_nfo_files($real_file), $real_file);
}

function get_rating($nfo_files, $real_file) {
	global $ratings_definitions;
	$rating = 'Unrated';
	foreach ($nfo_files as $nfo_file) {
		$nfo = file_get_contents($nfo_file);
		if (preg_match('@<rating>.*rated ([^ ]+).*</rating>@i', $nfo, $regs)) {
			if (array_search($regs[1], array_keys($ratings_definitions)) !== FALSE) {
				$rating = $regs[1];
			}
		} else if (preg_match('@<rating>(.+)</rating>@i', $nfo, $regs)) {
			if (array_search($regs[1], array_keys($ratings_definitions)) !== FALSE) {
				$rating = $regs[1];
			}
		} else if (preg_match('@<mpaa>.*rated ([^ ]+).*</mpaa>@i', $nfo, $regs)) {
			if (array_search($regs[1], array_keys($ratings_definitions)) !== FALSE) {
				$rating = $regs[1];
			}
		} else if (preg_match('@<mpaa>(.+)</mpaa>@i', $nfo, $regs)) {
			if (array_search($regs[1], array_keys($ratings_definitions)) !== FALSE) {
				$rating = $regs[1];
			}
		}
	}
	
	// DB value, if any, takes precedence.

	// First the directory rating...
	global $paths;
	list($parent_path, $filename) = explode_full_path($real_file);
	$i = 0;
	while (array_search("$parent_path/", $paths) === FALSE && $i++ < 100) {
		$query = sprintf("SELECT * FROM dir_ratings WHERE path = '%s'",
			mysql_escape_string($parent_path)
		);
		$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_object($result);
			if (!empty($row->rating)) {
				$rating = $row->rating;
				break;
			}
		}
		list($parent_path, $dirname) = explode_full_path($parent_path);
	}

	// ...then the file rating
	$query = sprintf("SELECT * FROM files WHERE path = '%s'",
		mysql_escape_string($real_file)
	);
	$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_object($result);
		if (!empty($row->rating)) {
			$rating = $row->rating;
		}
	}
	return $rating;
}

function get_nfo_files($real_file) {
	$nfo_files = array();
	
	$nfo_file = substr($real_file, 0, strrpos($real_file, '.')) . '.nfo';
	if (file_exists($nfo_file)) {
		$nfo_files[] = $nfo_file;
	}

	global $paths;
	list($parent_path, $filename) = explode_full_path($real_file);
	$i = 0;
	while (array_search("$parent_path/", $paths) === FALSE && $i++ < 100) {
		$nfo_file = "$parent_path/tvshow.nfo";
		if (file_exists($nfo_file)) {
			$nfo_files[] = $nfo_file;
		}
		list($parent_path, $dirname) = explode_full_path($parent_path);
	}
	
	return $nfo_files;
}

// Parameter: String to escape
// Returns: XML-valid string data
function xmlize($text) {
        $text = preg_replace('/&/','&amp;',$text);
        $text = preg_replace('/</','&lt;',$text);
        $text = preg_replace('/>/','&gt;',$text);
        return $text;
}

function save_rating_nfo($rating, $real_file) {
	if (is_dir($real_file)) {
		$real_file = trim($real_file, '/');
		$nfo_file = "/$real_file/tvshow.nfo";
		
		if (file_exists($nfo_file)) {
			// Update existing file
			$nfo = file_get_contents($nfo_file);
			if (preg_match('@<mpaa>.*</mpaa>@i', $nfo)) {
				$nfo = preg_replace('@<mpaa>.*</mpaa>@i', '<mpaa>' . xmlize($rating) . '</mpaa>', $nfo);
			} else {
				$nfo = preg_replace('@</tvshow>@i', "    <mpaa>" . xmlize($rating) . "</mpaa>\n</tvshow>", $nfo);
			}
			file_put_contents($nfo_file, $nfo);
		}
		
		// Also update the files of all videos found in this folder and sub-folders
		$query = sprintf("SELECT path FROM files WHERE path LIKE '%s%%'",
			mysql_escape_string("/$real_file/")
		);
		$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
		$video_files = array();
		while ($row = mysql_fetch_object($result)) {
			save_rating_nfo($rating, $row->path);
		}
	} else {
		if (strpos($rating, 'TV-') === 0) {
			$tag_name = 'episodedetails';
		} else {
			$tag_name = 'movie';
		}
		$nfo_file = substr($real_file, 0, strrpos($real_file, '.')) . '.nfo';
		if (file_exists($nfo_file)) {
			// Update existing file
			$nfo = file_get_contents($nfo_file);
			if (preg_match('@<mpaa>.*</mpaa>@i', $nfo)) {
				$nfo = preg_replace('@<mpaa>.*</mpaa>@i', '<mpaa>' . xmlize($rating) . '</mpaa>', $nfo);
			} else {
				$nfo = preg_replace('@</'.$tag_name.'>@i', "    <mpaa>" . xmlize($rating) . "</mpaa>\n</$tag_name>", $nfo);
			}
			file_put_contents($nfo_file, $nfo);
		}
	}
}

function show_rating_widget($current_rating, $what) {
	global $ratings_definitions, $current_user;
	if ($current_user->is_admin == 'no') {
		return;
	}
	if ($what == 'dir' && $_GET['p'] == '') {
		return;
	}
	?>
	<fieldset>
		<ul class="pageitem">
			<li class="select">
				<script type="text/javascript">
					var current_rating = '<?php echo $current_rating ?>';
				</script>
				<select name="rating" id="rating" onchange="<?php
					if ($what == 'dir') {
						?>document.getElementById('rating_save_result').innerHTML=''; if (confirm('This will change the rating of all videos inside this directory. Are you sure you want to continue?')) { current_rating = this.value; xmlhttpGet('<?php echo $_SERVER['SCRIPT_NAME'] ?>', 'rr=' + escape(this.value) + '&amp;r=<?php echo urlencode($_GET['p']) ?>'); } else { this.value = current_rating; }<?php
					} else {
						?>document.getElementById('rating_save_result').innerHTML=''; current_rating = this.value; xmlhttpGet('<?php echo $_SERVER['SCRIPT_NAME'] ?>', 'rr=' + escape(this.value) + '&amp;r=<?php echo urlencode($_GET['v']) ?>');<?php
					}
					?>">
					<?php foreach ($ratings_definitions as $rating => $definition): ?>
						<option value="<?php echo htmlentities($rating) ?>"<?php if ($rating == $current_rating) { echo ' selected="selected"'; } ?>>
							<?php
							if (strpos($rating, 'TV-') === 0) {
								echo htmlentities(str_replace('-', ': ', $rating) . " - $definition");
							} else if ($rating === 'Unrated') {
								echo htmlentities($rating);
							} else {
								echo htmlentities("Movie: $rating - $definition");
							}
							?>
						</option>
					<?php endforeach; ?>
				</select>
				<span class="arrow"></span>
			</li>
		</ul>
		<span id="rating_save_result" style="color: red"></span>
	</fieldset>
	<?php
}	

function remove_duplicate_videos(&$content_files) {
	$content_files_clean = array();
	foreach ($content_files as $v => $infos) {
		$filename = substr($v, 0, strrpos($v, '.'));
		$skip = FALSE;
		foreach ($content_files as $v2 => $infos2) {
			$filename2 = substr($v2, 0, strrpos($v2, '.'));
			if ($filename == $filename2 && $v != $v2) {
				if (is_video_universal($infos2->html5_ready)) {
					// Found another video file that is a duplicate of this one, and that is universal. Let's hide this one.
					$skip = TRUE;
					break;
				}
			}
		}
		if (!$skip) {
			$content_files_clean[$v] = $infos;
		}
	}
	$content_files = $content_files_clean;
}

function queue_video_for_encode($real_file) {
		$query = sprintf("SELECT MAX(CAST(queued_for_encode AS UNSIGNED)) AS max FROM files WHERE queued_for_encode != 'no'",
			mysql_escape_string($real_file)
		);
		$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
		if (mysql_num_rows($result) == 0) {
			$max = 0;
		} else {
			$row = mysql_fetch_object($result);
			$max = $row->max;
		}

		$query = sprintf("UPDATE files SET queued_for_encode = '%d' WHERE path = '%s'",
			$row->max + 1,
			mysql_escape_string($real_file)
		);
		mysql_query($query) or die("Error while updating DB: " . mysql_error());
}

function get_files_for_user($current_path) {
	global $current_user;
	$content_files = array();
	$content_dirs = array();
	$query = sprintf("SELECT * FROM files WHERE path LIKE '%s%%' AND (",
		mysql_escape_string($current_path)
	);
	$i = 0;
	foreach (explode(',', $current_user->allowed_ratings) as $allowed_rating) {
		if ($i++ > 0) {
			$query .= " OR ";
		}
		if ($allowed_rating == 'Unrated') {
			$query .= "rating IS NULL";
		} else {
			$query .= sprintf("rating = '%s'",
				mysql_escape_string($allowed_rating)
			);
		}
	}
	$query .= ") ORDER BY path";
	$result = mysql_query($query) or die("Error while querying DB: " . mysql_error());
	while ($row = mysql_fetch_object($result)) {
		$path = str_replace($current_path, '', $row->path);
		$path = explode('/', $path);
		if (count($path) > 1) {
			$content_dirs[$path[0].'/'] = TRUE;
		} else {
			$content_files[$path[0]] = $row;
		}
	}
	$content_dirs = array_keys($content_dirs);
	return array($content_files, $content_dirs);
}
?>
