<?php
// ini_set('session.gc_maxlifetime', 10);
// ini_set('session.cookie_lifetime', 10);
session_name("CrazyBankSystem");
session_start();

function print_header() {
	echo '
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
	<html>
		<head>
			<title>Crazy Банк</title>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<link type="text/css" rel="stylesheet" media="all" href="style/style.css" />
			<link type="text/css" rel="stylesheet" media="print" href="style/print.css" />
			<script src="js/forms.js" type="text/javascript" /></script>

		</head>
		<body>
		<div id="header">&nbsp;</div>
		<div id="indexlink"><a href="'.$_SERVER["PHP_SELF"].'">Crazy Банк</a></div>';	
	return TRUE;
}

function print_footer() {
	echo '

<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-5819641-3");
pageTracker._trackPageview();
} catch(err) {}</script>

		</body>
	</html>
	';
	
	return TRUE;
}

function menu ($curAction) {
	global $modules, $account;	
	echo '<ul>';
	foreach ($modules as $module=>$m) {
		if ( ($module !== 'welcome') && check_account_access ($module, $account)) {
			if ( isset ($modules[$module]['name']) ) echo '<li class="modulename">'.$modules[$module]['name']."</li>\n";
			echo "<ul>\n";
			foreach ($modules[$module]['action'] as $key=>$action) {
				if (isset($modules[$module]['menu'][$key])) {
					echo '<li>';
					if ($action == $curAction) echo '<i class="cur">';
					echo '<a class="menu" href="'.$_SERVER["SCRIPT_NAME"].'?action='.$action.'">'.$modules[$module]['menu'][$key].'</a>';
					if ($action == $curAction) echo '</i>';
					echo '</li>'."\n";
				}
			}
			echo "</ul>\n";
		}
	}
	echo '</ul>';
	return TRUE;
}

function title ($curAction) {
	global $modules, $account;	
	
	foreach ($modules as $module=>$m) {
		foreach ($modules[$module]['action'] as $key=>$action) {
			if ($action == $curAction) {
				if (isset($modules[$module]['title'][$key])) {
					$title = $modules[$module]['title'][$key];
					return $title;
				}
			}
		}
	}
	return '';
}

function find_module ($action) {
	global $modules;
	foreach ($modules as $module=>$m) {
		foreach ($modules[$module]['action'] as $key=>$value) {
			if ($action === $value) return $module;
		}
	}
	report_error("Произошла ошибка. Не найден модуль, реализующий запрашиваемое действие");
	return FALSE;
}

function exec_module ($module) {
	global $action, $account;
	if ($module === FALSE) return FALSE;
	
	if (! check_account_access ($module, $account) ) {
		show_auth ($action);
		report_error ("У Вас нет права использования этого модуля");
		return FALSE;
	}
	else {
		$func = 'show_'.$module;
		$func($action);
		return TRUE;
	}
}

function check_account_access ( $module ) {
	global $modules, $account;
	foreach ($account['group'] as $ukey=>$ugroup) {
		foreach ($modules[$module]['groups'] as $mkey=>$mgroup) {
			if ( $ugroup == $mgroup ) return TRUE;
		}
	}
	return FALSE;
}


//////////////////////////////////////////////////////////////////////////////

include ("./config.php");
include ("./mysql_config.php");
include ("./functions_controller.php");
include ("./functions_view.php");

$accountlist = FALSE;

print_header();

if (!mysql_connect($mysql_server,$mysql_user,$mysql_password)) 
	exit ('Произошла ошибка подключения к базе данных. Повторите попытку и сообщите, пожалуйста, о случившемся правительству Crazy Week.');
	
mysql_select_db($mysql_db);
	
if (empty($_GET['action'])) $action = 'welcome';
	else $action = $_GET['action'];

// Загружаем данные о пользователе
if ( isset ($_SESSION['account_id']) ) $account = get_account_info ($_SESSION['account_id']);
else $account['group'][] = 'guest';

// Подключаем модули
define ("RequestModule", 'core');
$modpath = "./modules/";
$path = opendir($modpath);
while (($file = readdir($path))!== false)
    {
	$files[] = $file;
    }
closedir($path);
sort ($files);
foreach ($files as $file) {
	if ($file == '.' || $file == '..' || substr($file, -4, 4) != '.php' ) continue;
	$modfile = file($modpath.$file);
	if ($modfile[1] != "// ItIsCrazyBankModule\n") continue;
	include ($modpath.$file);
}

// Шапка: правящая партия и валюта
$states = getStatesList();
$currency = getCurrencyList();
//$states_balance = getStatesBalance();

echo '<div id="states"><b>Состав парламента:</b>';
foreach ($states as $key=>$value) 
echo '   
<span style="padding-left: 10px;">
'.$value.'
</span>';       
echo '   
<span style="padding-left: 10px;">
<b>Валюта:</b>';

foreach ($currency as $key=>$value) 
echo '   
<span style="padding-left: 10px;">
'.$value.'
</span>';   
    
echo '     
</span>';     
echo '</div>';
// END Успехи государств в шапке
	
echo '<div id="menu">';
menu ($action);
echo '</div>';
	
echo '<div id="main">';
if ( ( $title=title ($action) ) != '' ) echo '<h1>'.$title.'</h1>';
exec_module (find_module ($action) );
echo '</div>';

print_errors();

if ($accountlist==1) {
echo '
<div id="accountlist"><iframe src="./accountlist.php" frameborder="0" width="100%" height="100%"></iframe></div>';
}
else if  ($accountlist==2) {
echo '
<div id="accountlist"><iframe src="./accountlist.php?action=show_companies_list" frameborder="0" width="100%" height="100%"></iframe></div>';
}
print_footer();
?>
