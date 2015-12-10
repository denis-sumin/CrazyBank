<?php
// ItIsCrazyBankModule

$module = 'logs';

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

$modules[$module]['name'] = 'Вывод логов';

$modules[$module]['action'][] = 'logs_admin';
$modules[$module]['menu'][] = 'Логи администратора';
$modules[$module]['title'][] = 'Логи действий администраторов';

$modules[$module]['action'][] = 'logs_errors';
$modules[$module]['menu'][] = 'Логи ошибок';
$modules[$module]['title'][] = 'Логи ошибок';

$modules[$module]['action'][] = 'logs_logins';
$modules[$module]['menu'][] = 'Логи входа';
$modules[$module]['title'][] = 'Логи авторизации пользователей в системе';

$modules[$module]['action'][] = 'logs_money';
$modules[$module]['menu'][] = 'Логи денежных переводов';
$modules[$module]['title'][] = 'Логи денежных переводов';

$modules[$module]['groups'][] = 'admin'; // группы, которым разрешено пользоваться модулем

function show_logs( $action ) {
	global $modules, $account, $accountlist;
	// $module = $modules['rates'];

  	$offset = 20;

	if (isset($_POST['n'])){
		$n = $_POST['n'];
	}
	else {
		$n = 0;
	}

	switch ($action) {
		case 'logs_admin':
		  $log = getGlobalAdminLog();
		  print_admin_log (less($log, $n * $offset, $offset));
		  break;

		case 'logs_errors':
		  $log = getGlobalErrorsLog();
		  print_errors_log (less($log, $n * $offset, $offset));
		  break;

		case 'logs_logins':
		  $log = getGlobalLoginsLog();
		  print_logins_log (less($log, $n * $offset, $offset));
		  break;

		case 'logs_money':
		  $log = getGlobalMoneyLog();
		  print_money_log (less($log, $n * $offset, $offset));
		  break;
    }

    if(count($log) - (int)(count($log) / $offset) > 0)
    {
      $page_num = (int)(count($log) / $offset) + 1;
    }
    else
    {
      $page_num = (int)(count($log) / $offset);
    }
		echo '
		  <p><br /><form method="post" name="num"><small>Номер страницы лога:&nbsp;</small><select name="n">';
			for ($i = 0; $i < $page_num; $i++){
        if ($i==$n){
          $sel='selected';
        }
        else {
          $sel='';
        }
        $value = $i;
        echo '<option value="'.$value.'" '.$sel.'>'.$value.'</option>';
      }
			echo '</select><input type="submit" name="" value="Показать" /></form></p>
			';
	}
?>
