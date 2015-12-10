<?php
// ItIsCrazyBankModule

$module = 'auth';

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

// Отработать успешный вход и выход нужно до всего остального, чтобы правильно собрать меню
if (	$action === "login" &&
	isset($_POST['login']) &&
	isset($_POST['account_id']) &&
	isset($_POST['pin'])
) {
	if ( check_password ($_POST['account_id'],$_POST['pin']) ) $_SESSION['account_id'] = $_POST['account_id'];
}

if ($action === "logout") {
	unset($_SESSION['account_id']);
	session_unregister('account_id');
}

//$modules[$module]['name'] = 'Авторизация';

$modules[$module]['action'][0] = 'login';
$modules[$module]['title'][0] = 'Вход в систему';
$modules[$module]['action'][1] = 'logout';
$modules[$module]['title'][1] = 'До встречи ;-)';

$modules[$module]['groups'][] = 'guest'; // группы, которым разрешено пользоваться модулем
$modules[$module]['groups'][] = 'company';

echo '<div id="auth">';
if ( !empty ($_SESSION['account_id'])  && $action !== $modules[$module]['action'][1] ) {
	echo 'Вы вошли как '.@$account['name'].' '.@$account['surname'].'<a href="'.$_SERVER["SCRIPT_NAME"].'?action=logout">Выйти</a>';
}
else {
	echo '<a href="'.$_SERVER["SCRIPT_NAME"].'?action=login">Войти</a> в систему';
}
echo '</div>';

function show_auth ($action) {
	global $modules;
	$module = $modules['auth'];

	$changeaction = 1;
	foreach ($modules['auth']['action'] as $key=>$value) {
		if ( $action === $value ) { $changeaction = 0; break; }
	}
	if (!$changeaction) $return = 'welcome';
	else { $return = $action; $action = 'login'; }
	if ( isset($_GET['return'] )) $return = $_GET['return'];

	switch ($action) {
		case $module['action'][0]:
			if ( !empty ($_SESSION['account_id']) ) {
				echo "Вы успешно вошли в систему<br />Перенаправляем...";
				echo '
				<script language="javascript">
				setTimeout("location.href=\''.$_SERVER['PHP_SELF'].'?action='.$return.'\'", 500);
				</script>
				';
				break;
			}
			echo '
				<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'&return='.$return.'">
				<table class="form">
					<tr><td>Номер счета:</td><td><input type="text" name="account_id" value="'.@$_POST['account_id'].'" /></td></tr>
					<tr><td>PIN-код:</td><td><input type="password" name="pin" /></td></tr>
					<tr><td></td><td><input type="submit" name="'.$module['action'][0].'" value="Войти в банк" /></td></tr>
				</table>
				</form>
			';
			break;
		case $module['action'][1]:
			echo 'Переходим на стартовую';
			echo '
				<script language="javascript">
				setTimeout("location.href=\''.$_SERVER['PHP_SELF'].'\'", 500);
				</script>
				';
			break;
		default:

	}
}
?>
