<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (!permission_exists('contact_time_add')) { echo "access denied"; exit; }

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get contact uuid
	$contact_uuid = check_str($_REQUEST['contact_uuid']);

//get posted variables & set time status
	if (sizeof($_POST) > 0) {
		$domain_uuid = check_str($_POST['domain_uuid']);
		$contact_time_uuid = check_str($_POST['contact_time_uuid']);
		$contact_uuid = check_str($_POST['contact_uuid']);
		$time_action = check_str($_POST['time_action']);
		$time_description = check_str($_POST['time_description']);

		if ($time_description == 'Description...') { unset($time_description); }

		if ($time_action == 'start') {
			$contact_time_uuid = uuid();
			$sql = "insert into v_contact_times ";
			$sql .= "( ";
			$sql .= "domain_uuid, ";
			$sql .= "contact_time_uuid, ";
			$sql .= "contact_uuid, ";
			$sql .= "user_uuid, ";
			$sql .= "time_start, ";
			$sql .= "time_description ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "( ";
			$sql .= "'".$domain_uuid."', ";
			$sql .= "'".$contact_time_uuid."', ";
			$sql .= "'".$contact_uuid."', ";
			$sql .= "'".$_SESSION["user"]["user_uuid"]."', ";
			$sql .= "'".date("Y-m-d H:i:s")."', ";
			$sql .= "'".$time_description."' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		if ($time_action == 'stop') {
			$sql = "update v_contact_times ";
			$sql .= "set ";
			$sql .= "time_stop = '".date("Y-m-d H:i:s")."', ";
			$sql .= "time_description = '".$time_description."' ";
			$sql .= "where ";
			$sql .= "contact_time_uuid = '".$contact_time_uuid."' ";
			$sql .= "and domain_uuid = '".$domain_uuid."' ";
			$sql .= "and contact_uuid = '".$contact_uuid."' ";
			$sql .= "and user_uuid = '".$_SESSION["user"]["user_uuid"]."' ";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		header("contact_timer.php?contact_uuid=".$contact_uuid);
	}

//get contact details
	$sql = "select ";
	$sql .= "contact_organization, ";
	$sql .= "contact_name_given, ";
	$sql .= "contact_name_family, ";
	$sql .= "contact_nickname ";
	$sql .= "from v_contacts ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and contact_uuid = '".$contact_uuid."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetch(PDO::FETCH_NAMED);
	if (sizeof($result) > 0) {
		$contact_organization = $result["contact_organization"];
		$contact_name_given = $result["contact_name_given"];
		$contact_name_family = $result["contact_name_family"];
		$contact_nickname = $result["contact_nickname"];
	}
	else {
		exit;
	}
	unset ($sql, $prep_statement, $result);

//determine timer state and action
	$sql = "select ";
	$sql .= "contact_time_uuid, ";
	$sql .= "time_description ";
	$sql .= "from v_contact_times ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and user_uuid = '".$_SESSION['user']['user_uuid']."' ";
	$sql .= "and contact_uuid = '".$contact_uuid."' ";
	$sql .= "and time_start is not null ";
	$sql .= "and time_stop is null ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetch(PDO::FETCH_NAMED);
	if (sizeof($result) > 0) {
		$contact_time_uuid = $result["contact_time_uuid"];
		$time_description = $result["time_description"];
	}
	unset ($sql, $prep_statement, $result);

	$timer_state = ($contact_time_uuid != '') ? 'running' : 'stopped';
	$timer_action = ($timer_state == 'running') ? 'stop' : 'start';

//determine contact name to display
	if ($contact_nickname != '') {
		$contact = $contact_nickname;
	}
	else if ($contact_name_given != '') {
		$contact = $contact_name_given;
	}
	if ($contact_name_family != '') {
		$contact .= ($contact != '') ? ' '.$contact_name_family : $contact_name_family;
	}
	if ($contact_organization != '') {
		$contact .= ($contact != '') ? ', '.$contact_organization : $contact_organization;
	}
?>

<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
<head>
	<title>Timer: <?php echo $contact; ?></title>
	<style>
		body {
			color: #5f5f5f;
			font-size: 12px;
			font-family: arial;
			margin: 0;
			padding: 15px;
			}

		b {
			color: #952424;
			font-size: 15px;
			font-family: arial;
			}

		a {
			color: #004083;
			width: 100%;
			}

		a:hover {
			color: #5082ca;
			}

		form {
			margin: 0;
			}

		input.btn, input.button {
			font-family: Candara, Calibri, Segoe, "Segoe UI", Optima, Arial, sans-serif;
			padding: 2px 6px 3px 6px;
			color: #fff;
			font-weight: bold;
			cursor: pointer;
			font-size: 11px;
			-moz-border-radius: 3px;
			-webkit-border-radius: 3px;
			-khtml-border-radius: 3px;
			border-radius: 3px;
			background-image: -moz-linear-gradient(top, #524f59 25%, #000 64%);
			background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, #524f59), color-stop(0.64, #000));
			border: 1px solid #26242a;
			background-color: #000;
			text-align: center;
			text-transform: uppercase;
			text-shadow: 0px 0px 1px rgba(0, 0, 0, 0.85);
			opacity: 0.9;
			-moz-opacity: 0.9;
			}

		input.btn:hover, input.button:hover, img.list_control_icon:hover {
			box-shadow: 0 0 5px #cddaf0;
			-webkit-box-shadow: 0 0 5px #cddaf0;
			-moz-box-shadow: 0 0 5px #cddaf0;
			opacity: 1.0;
			-moz-opacity: 1.0;
			cursor: pointer;
			}

		input.txt, textarea.txt, select.txt, .formfld {
			font-family: arial;
			font-size: 12px;
			color: #000;
			text-align: left;
			padding: 5px;
			border: 1px solid #c0c0c0;
			background-color: #fff;
			box-shadow: 0 0 3px #cddaf0 inset;
			-moz-box-shadow: 0 0 3px #cddaf0 inset;
			-webkit-box-shadow: 0 0 3px #cddaf0 inset;
			border-radius: 3px;
			-moz-border-radius: 3px;
			-webkit-border-radius: 3px;
			}

		input.txt, .formfld {
			transition: width 0.25s;
			-moz-transition: width 0.25s;
			-webkit-transition: width 0.25s;
			max-width: 500px;
			}

		input.txt:focus, .formfld:focus {
			-webkit-box-shadow: 0 0 5px #cddaf0;
			-moz-box-shadow: 0 0 5px #cddaf0;
			box-shadow: 0 0 5px #cddaf0;
			}

		td {
			color: #5f5f5f;
			font-size: 12px;
			font-family: arial;
			}

		.vncell {
			border-bottom: 1px solid #fff;
			background-color: #e5e9f0;
			padding: 8px;
			text-align: right;
			color: #000;
			-moz-border-radius: 4px;
			-webkit-border-radius: 4px;
			border-radius: 4px;
			border-right: 3px solid #e5e9f0;
			}

		DIV.timer {
			width: 100%;
			height: 60px;
			text-align: center;
			background-color: #222;
			font-size: 50px;
			color: #c00;
			font-weight: bold;
			font-family: "Courier New",Courier,"Lucida Sans Typewriter","Lucida Typewriter",monospace;
			padding-top: 7px;
			-moz-border-radius: 4px;
			-webkit-border-radius: 4px;
			border-radius: 4px;

			}

	</style>

	<script type="text/javascript">
		//ajax for refresh
		var refresh = 1500;
		var source_url = 'contact_timer_inc.php?contact_uuid=<?php echo $contact_uuid; ?>&contact_time_uuid=<?php echo $contact_time_uuid; ?>';
		var interval_timer_id;

		function loadXmlHttp(url, id) {
			var f = this;
			f.xmlHttp = null;
			/*@cc_on @*/ // used here and below, limits try/catch to those IE browsers that both benefit from and support it
			/*@if(@_jscript_version >= 5) // prevents errors in old browsers that barf on try/catch & problems in IE if Active X disabled
			try {f.ie = window.ActiveXObject}catch(e){f.ie = false;}
			@end @*/
			if (window.XMLHttpRequest&&!f.ie||/^http/.test(window.location.href))
				f.xmlHttp = new XMLHttpRequest(); // Firefox, Opera 8.0+, Safari, others, IE 7+ when live - this is the standard method
			else if (/(object)|(function)/.test(typeof createRequest))
				f.xmlHttp = createRequest(); // ICEBrowser, perhaps others
			else {
				f.xmlHttp = null;
				 // Internet Explorer 5 to 6, includes IE 7+ when local //
				/*@cc_on @*/
				/*@if(@_jscript_version >= 5)
				try{f.xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");}
				catch (e){try{f.xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");}catch(e){f.xmlHttp=null;}}
				@end @*/
			}
			if(f.xmlHttp != null){
				f.el = document.getElementById(id);
				f.xmlHttp.open("GET",url,true);
				f.xmlHttp.onreadystatechange = function(){f.stateChanged();};
				f.xmlHttp.send(null);
			}
		}

		loadXmlHttp.prototype.stateChanged=function () {
		if (this.xmlHttp.readyState == 4 && (this.xmlHttp.status == 200 || !/^http/.test(window.location.href)))
			//this.el.innerHTML = this.xmlHttp.responseText;
			document.getElementById('ajax_reponse').innerHTML = this.xmlHttp.responseText;
			if (document.getElementById('title_script')) {
				eval(document.getElementById('title_script').innerHTML);
			}
		}

		<? if ($timer_state == 'running') { ?>
			var requestTime = function() {
				var url = source_url;
				new loadXmlHttp(url, 'ajax_reponse');
				refresh_start();
			}

			if (window.addEventListener) {
				window.addEventListener('load', requestTime, false);
			}
			else if (window.attachEvent) {
				window.attachEvent('onload', requestTime);
			}
		<? } ?>

	//refresh controls
		function refresh_start() {
			interval_timer_id = setInterval( function() {
				url = source_url;
				new loadXmlHttp(url, 'ajax_reponse');
			}, refresh);
		}

	//set window title to time when timer is running
		function set_title(title_text) {
			window.document.title = title_text;
		}

	</script>
</head>
<body>
	<img src='resources/images/icon_timer.png' style='width: 24px; height: 24px; border: none; margin-left: 15px;' alt="<?php echo $text['label-time_timer']; ?>" align='right'>
	<b><?php echo $text['label-time_timer']; ?></b>
	<br><br>
	<?php echo $text['description_timer']; ?>
	<br><br>
	<strong><a href="javascript:void(0);" onclick="window.opener.location.href='contact_edit.php?id=<?php echo $contact_uuid; ?>';"><?php echo $contact; ?></a></strong>
	<br><br>
	<div id='ajax_reponse' class='timer' <?php echo ($timer_state == 'stopped') ? "style='color: #888;'" : null; ?>>00:00:00</div>
	<br>
	<form name='frm' id='frm' method='post' action=''>
	<input type='hidden' name='domain_uuid' value="<?php echo $_SESSION['domain_uuid']; ?>">
	<input type='hidden' name='contact_time_uuid' value="<?php echo $contact_time_uuid; ?>">
	<input type='hidden' name='contact_uuid' value="<?php echo $contact_uuid; ?>">
	<input type='hidden' name='time_action' value="<?php echo $timer_action; ?>">
	<table cellpadding='0' cellspacing='0' border='0' style='width: 100%;'>
		<tr>
			<td class='vncell' style='text-align: center; padding: 10px;'>
				<?php echo $text['label-description']; ?>
				<textarea name='time_description' id='timer_description' class='formfld' style='width: 100%; height: 50px; margin-top: 5px;'><?php echo $time_description; ?></textarea>
				<? if ($timer_state == 'stopped') { ?><script>document.getElementById('timer_description').focus();</script><? } ?>
			</td>
		</tr>
	</table>
	<br>
	<center>
	<? if ($timer_state == 'running') { ?>
		<input type='submit' class='btn' value="<?php echo $text['button-stop']; ?>">
	<? } else if ($timer_state == 'stopped') { ?>
		<input type='submit' class='btn' value="<?php echo $text['button-start']; ?>">
	<? } ?>
	</center>
	</form>
</body>
</html>