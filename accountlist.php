<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Crazy Bank</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link type="text/css" rel="stylesheet" media="all" href="style/style.css" />
		<style type="text/css">
		body {
			background: transparent;
			margin: 0; padding: 0;
			width: 330px;
		}
		body * {
			font-size: 11pt;

		}
		#filters, #hfilters {
			background: #fff;
			top:0px;
			font-size: 13pt;
			right:1px;
			left:0px;
			margin: 0px;
			padding-top: 5px;
			position: fixed !important;
			position: absolute;
			z-index: 10;
			width: 100%;
		}
		#accounttable {
			position: absolute;
			top:34px;
			left: 1px !important;
			left: 0px;
			min-width: 325px;
			width: 330px;
		}
		#note {
			background: #fff;
			bottom:0px;
			font-size: 11pt;
			left:0px;
			right:1px;
			margin: 0px;
			padding-top: 5px;
			position: absolute;
			position: fixed;
			text-align: center;
			z-index: 10;
		}
		</style>
		<script src="js/forms.js" type="text/javascript" /></script>
	</head>
	<body>

	<div style="display:none">
<?php
include ("./mysql_config.php");
include ("./config.php");
include ("./functions_controller.php");
include ("./functions_view.php");

if (!mysql_connect($mysql_server,$mysql_user,$mysql_password))
	exit ('Произошла ошибка подключения к базе данных. Повторите попытку и сообщите, пожалуйста, о случившемся правительству Crazy Week.');

mysql_select_db($mysql_db);

define ("RequestModule", 'accountlist');
include ("./modules/3accountlists.php");
echo '</div>';

if (empty($_GET['action'])) $action = 'show_users_list';
	else $action = $_GET['action'];

if ($action == 'show_users_list' || $action == 'account_info') $out = 'пользователи&nbsp;&nbsp;<a href="?action=show_companies_list">компании</a>';
	 else $out = '<a href="?action=show_users_list">пользователи</a>&nbsp;&nbsp;компании';

echo '<div id="note">'.$out.'</div>';

show_accountlists ( $action );

?>
	</body>
</html>
