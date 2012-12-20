<?php

function balance_format ( $balance ) {
	if ( $balance < 10000 ) $balance = number_format ($balance, 0, ',', '');
	else $balance = number_format ($balance, 0, ',', ' ');
	return $balance;
}

// Проверяет пару <номер счета:ПИН-код> 
// Возвращает TRUE/FALSE в случае удачного/неудачного выполнения
// Аргументы: id=номер счета, password=ПИН
function check_password ($account_id, $password) {
	mysql_query ("START TRANSACTION;");

	if ( !($id = account2id ($account_id) ) ) return FALSE;
	
	$q = mysql_query ("SELECT * FROM `accounts` WHERE `id`='$id' AND `blocked`='1';");
	if( mysql_num_rows ($q) > 0 ) {
		report_error( "Счет ".$account_id." заблокирован. О причинах блокировки счета Вы можете узнать в Правительстве Crazy Week (Аудитория 304)" );
		return FALSE;
	}
	
	$time = 10; $num = 3;
	$q = mysql_query ("SELECT * FROM `logs_logins`
			WHERE id='$id' AND
			`timestamp` > DATE_SUB( now( ) , INTERVAL $time MINUTE ) AND 
			`success` != '1' AND
			`ip` = '$_SERVER[REMOTE_ADDR]'
			ORDER BY `timestamp`  DESC;			
	");
	if( mysql_num_rows ($q) >= $num ) {
		for ($i = 0; $i < $num; $i++) $f = mysql_fetch_array($q);
		report_error( "
			С этого компьютера за последние $time минут совершено более $num неудачных попыток войти в банк, используя номер счета $account_id<br />
			Доступ к этому счету с этого компьютера временно заблокирован.<br />
			Блокировка автоматически закончится в ".date ( 'H:i:s', strtotime ($f['timestamp'])+$time*60 ) );
		return FALSE;
	}
	
	$time = 10; $num = 10;
	$q = mysql_query ("SELECT * FROM `logs_logins`
			WHERE id='$id' AND
			`timestamp` > DATE_SUB( now( ) , INTERVAL $time MINUTE ) AND 
			`success` != '1'
			ORDER BY `timestamp`  DESC;			
	");
	if( mysql_num_rows ($q) >= $num ) {
		for ($i = 0; $i < $num; $i++) $f = mysql_fetch_array($q);
		report_error( "
			За последние $time минут совершено более $num неудачных попыток войти в банк, используя номер счета $account_id<br />
			Доступ к этому счету временно заблокирован.<br />
			Блокировка автоматически закончится в ".date ( 'H:i:s', strtotime ($f['timestamp'])+$time*60 ) );
		return FALSE;
	}
	/*
	$time = 5; $num = 10;
	$q = mysql_query ("SELECT * FROM `logs_logins`
			WHERE `timestamp` > DATE_SUB( now( ) , INTERVAL $time MINUTE ) AND 
			`success` != '1' AND
			`ip` = '$_SERVER[REMOTE_ADDR]'
			ORDER BY `timestamp`  DESC;			
	");
	if( mysql_num_rows ($q) >= $num ) {
		for ($i = 0; $i < $num; $i++) $f = mysql_fetch_array($q);
		report_error( "
			С этого компьютера за последние $time минут совершено более $num неудачных попыток войти в банк, используя номер счета $account_id<br />
			Доступ к банку с этого компьютера временно заблокирован.<br />
			Блокировка автоматически закончится в ".date ( 'H:i:s', strtotime ($f['timestamp'])+$time*60 ) );
		return FALSE;
	}
	*/
	switch ( accounttype ($account_id) ) {
		case 'user':
			$q = mysql_query("SELECT * FROM `users` WHERE id='$id'");
			if( mysql_num_rows ($q) == 0 ) {
				report_error( "Счет не найден" );
				return FALSE;
			}
			
			$f = mysql_fetch_array($q);
	
			if (crypt ( $password, $f['hash'] ) == $f['hash'] ) $success = TRUE;
			else $success = FALSE;	
			break;
		case 'company':
			$q = mysql_query("SELECT * FROM `company_participators` WHERE oid='$id'");
			if( mysql_num_rows ($q) == 0 ) report_error( "Счет компании не найден" );
			
			for ($i=0; $i < mysql_num_rows ($q); $i++) {
				$f = mysql_fetch_array($q);
				$user[] = $f['uid'];
			}
			
			foreach ($user as $key=>$id) {
				$q = mysql_query("SELECT * FROM `users` WHERE id='$id'");
				if( mysql_num_rows ($q) == 0 ) report_error( "Счет не найден" );

				$fu = mysql_fetch_array($q);
	
				if (crypt ( $password, $fu['hash'] ) == $fu['hash'] ) { $success = $fu['id'];  break; }
				else $success = FALSE;
			}			
			break;
	}
	
	if ( !mysql_query("INSERT INTO `logs_logins` (`id`,`ip`,`success`) VALUES ('$id','$_SERVER[REMOTE_ADDR]','$success')") ) {
		report_error( "Произошла ошибка записи информации в логи. Пожалуйста, попробуйте войти еще раз" );
		return FALSE;
	}
	
	if (!mysql_query ("COMMIT;")) {
		report_error ('Произошла ошибка во время выполнения запроса. Пожалуйста, попробуйте еще раз');
		return FALSE;
	}
	
	if (!$success) report_error("Неправильный ПИН-код. Поменять ПИН-код можно в Правительстве Crazy Week");
	return $success;
}

// Узнает баланс счета пользователя
// Возвращает значение (баланс)/FALSE в случае удачного/неудачного выполнения
// Аргументы: id=номер счета
function balance ($account_id) {
	$id = account2id ($account_id);
	$q = mysql_query("SELECT * FROM `accounts` WHERE id='$id'");
	$f = mysql_fetch_array($q);
	return $f['balance'];
}

// Изменяет баланс счета на <n> единиц
// Эту функцию нельзя использовать напрямую из интерфейса! Только из других функций этого файла (ведь вложенные транзакции невозможны)
// Возвращает TRUE/FALSE в случае удачного/неудачного выполнения
// Аргументы: id=номер счета, n=количество единиц
function change_balance ($id, $n, $currency='') {

	// mysql_query ("START TRANSACTION;");
	
	$account = get_account_info (id2account($id));
	if ( $currency !== '' ) {
		if ($account['currency'] != $currency) {
			$rates = getRates();
			$n *= $rates[$currency];
			if ($account['currency']!='') $n /= $rates[$account['currency']];
		}
	}
	
	$q = mysql_query("SELECT * FROM `accounts` WHERE `id`='$id'");
	$f = mysql_fetch_array($q);
		
	if ( ($f['balance'] + $n) < 0 ) { 
		report_error("Недостаточно средств на счете для осуществления операции");		
		return FALSE;
	}
	
	$q = mysql_query("
	UPDATE `accounts` SET `balance` = `balance`+'$n' WHERE `accounts`.`id` = '$id' LIMIT 1 ;
	");
	
	if (!$q) {
		report_error ('Произошла ошибка во время выполнения запроса. Пожалуйста, попробуйте еще раз');
		return FALSE;
	}
	
	return TRUE;
}

// Производит перевод средств со счета на счет
// Возвращает TRUE/FALSE в случае удачного/неудачного выполнения
// Аргументы: id_from=номер счета отправителя, id_to=номер счета получателя, n=количество единиц
function transmit ($account_id_from, $account_id_to, $n, $currency, $comment, $statebonus=false) {
	if ($account_id_from == 'bank') $id_from = 0;
		else $id_from = account2id ($account_id_from);		
	if ($account_id_to == 'bank') $id_to = 0;
		else $id_to = account2id ($account_id_to);
	
	if ($n <= 0) report_error ('Сумма перевода должна быть положительной!');

	mysql_query ("START TRANSACTION;");

	$q = mysql_query("SELECT * FROM `accounts` WHERE id='$id_to'");
	if( mysql_num_rows($q) == 0 ) {
		report_error("Неизвестный номер счета получателя");
		return FALSE;
	}

	if ( change_balance ($id_from, -$n, $currency ) ) change_balance ($id_to, $n, $currency);
	else {
		mysql_query ("ROLLBACK;");
		return FALSE;
	}
	/*
	if ( $statebonus ) {
		if ( !increase_state_balance ( $account_id_to, $n ) ) {
			report_error("Произошла ошибка начисления бонусов для племени. Операция прервана");
			return FALSE;
		}
	}
	*/
	
	if (!mysql_query("
	INSERT INTO `logs_money` (`id_from`,`id_to`,`ip`,`money`,`currency`,`comment`)
	VALUES ('$id_from','$id_to','$_SERVER[REMOTE_ADDR]','$n','$currency','".addslashes($comment)."')
	") ) {
		report_error("Произошла ошибка записи в логи. Перевод был отменен");
		return FALSE;
	}
	
	if (!mysql_query ("COMMIT;")) {
		report_error ('Произошла ошибка во время выполнения запроса. Пожалуйста, попробуйте еще раз');
		return FALSE;
	}
	
	return TRUE;
}

// Добавляет ошибку в общий массив ошибок
// Возвращает TRUE/FALSE в случае удачного/неудачного выполнения
// Аргументы: problem=описание ошибки
function report_error($problem) {
	global $errors, $account;
	$errors[] = $problem.' '.mysql_error();
		
	mysql_query ("ROLLBACK;");
	
	if (empty($account['id'])) $id = '0'; else $id = $account['id'];		
	$problemtolog = explode ('<br />', $problem);
	$problemtolog = trim(strip_tags ($problemtolog[0]));	
	mysql_query ("START TRANSACTION;");
	mysql_query ("
	INSERT INTO `logs_errors` (`id` , `error` , `ip`)
	VALUES ('$id', '$problemtolog', '$_SERVER[REMOTE_ADDR]');");
	mysql_query ("COMMIT;");
	
	print_errors(); die();
	
	return TRUE;
}

function get_account_info ($account_id) {
	if ( !($id = account2id ($account_id) ) ) return FALSE;
	
	switch ( accounttype ($account_id) ) {
		case 'user':
			$q = mysql_query("SELECT * FROM `accounts` INNER JOIN `users` ON `accounts`.`id` = `users`.`id` WHERE `users`.`id`='$id'");
			if ( mysql_num_rows($q) == 0 ) { report_error("Счет не найден"); return FALSE; }

			$f = mysql_fetch_array($q);
			$account['id'] = $f['id'];
			$account['account_id'] = $account_id;
			
			$account['name'] = $f['name'];
			$account['surname'] = $f['surname'];
			$account['litgroup'] = $f['litgroup'];
			$account['photo_url'] = $f['photo_url'];
			$account['balance'] = $f['balance'];
			$account['blocked'] = $f['blocked'];
			$account['currency'] = $f['currency'];
			$account['state'] = $f['state'];
	
			$q = mysql_query("SELECT * FROM `usersgroup` WHERE id='$id'");
			for ($i=0; $i<mysql_num_rows($q); $i++) {
				$f = mysql_fetch_array($q);
				$account['group'][] = $f['bankgroup'];
			}
			$account['group'][] = 'user';
			$account['group'][] = 'guest';
	
			$q = mysql_query("SELECT * FROM `company_participators` WHERE uid='$id'");
			for ($i=0; $i < mysql_num_rows ($q); $i++) {
				$f = mysql_fetch_array($q);
				$account['organization'][] = $f['oid'];
			}
			break;
			
		case 'company':
			$q = mysql_query("SELECT * FROM `accounts` INNER JOIN `companies` ON `accounts`.`id` = `companies`.`id` WHERE `companies`.`id`='$id'");
			if ( mysql_num_rows($q) == 0 ) { report_error("Счет не найден"); return FALSE; }
			
			$f = mysql_fetch_array($q);
			$account['id'] = $f['id'];
			$account['account_id'] = $account_id;
			$account['name'] = $f['oname'];
			
			$account['balance'] = $f['balance'];
			$account['blocked'] = $f['blocked'];
			$account['currency'] = $f['currency'];
			
			$q = mysql_query("SELECT * FROM `company_participators` WHERE oid='$id'");
			for ($i=0; $i < mysql_num_rows ($q); $i++) {
				$f = mysql_fetch_array($q);
				$account['users'][$i] = $f['uid'];
				$account['user_percent'][$i] = $f['percentage'];
			}
			
			$account['group'][] = 'company';
			
			break;
	}
	
	return $account;
}

function id2account ($id) {
	settype ( $id, 'string');
	if ( strlen($id) > 4 ) { report_error ("Некорректный ID"); return FALSE; }
	switch (strlen($id)) {
		case 1:
			$account = '00'.$id;
			break;
		case 2:
			$account = '0'.$id;
			break;
		default:
			$account = $id;
	}
	return $account;
}

function account2id ($account) {
	if ( strlen($account) <3 || strlen($account) > 4 || !preg_match("/^[0-9]+$/i",$account) || (strlen($account) == 4 && $account<1000)) { 
		report_error ("
			Некорректный номер счета $account<br />\n
			Номер счета пользователя состоит из трех цифр без пробелов, а номер счета организации &mdash; из четырех цифр");
		return FALSE;
	}
	return (int) $account;
}

function accounttype ($account_id) {
	$type = FALSE;
	switch (strlen($account_id)) {
		case 3:
			$type = 'user';
			break;
		case 4:
			$type = 'company';
			break;
	}
	return $type;
}

function formAccountArray ( $type, $sortField='id', $sortDir='ASC' ) {
	$arg = func_get_args();
	$array = array();
	
	$currency = getCurrencyList();
	$rates = getRates();
	
	switch ( $type ) {
		case 'user':
			if ( $sortField == 'balance' ) $sortTable = 'accounts';
			else $sortTable = 'users';
		
			$q = mysql_query("SELECT * FROM `accounts` INNER JOIN `users` ON `accounts`.`id` = `users`.`id` WHERE  `surname` LIKE '$arg[3]%' AND `litgroup` LIKE '$arg[4]%' ORDER BY  `$sortTable`.`$sortField` $sortDir, `users`.`surname` ASC;");
									
			for ($i=0; $i < mysql_num_rows ($q); $i++) {
				$f = mysql_fetch_array($q);
				$array[$i]['id'] = id2account ( $f['id'] );
				$array[$i]['name'] = $f['name'];
				$array[$i]['surname'] = $f['surname'];
				$array[$i]['litgroup'] = $f['litgroup'];
				$array[$i]['state'] = $f['state'];
				$array[$i]['currency'] = $f['currency'];
				$array[$i]['blocked'] = $f['blocked'];
				$array[$i]['balance'] = balance_format ($f['balance']*$rates[$f['currency']]);
			}
			break;
		case 'company':
			if ( $sortField == 'balance' ) $sortTable = 'accounts';
			else $sortTable = 'companies';
			
			$q = mysql_query("SELECT * FROM `accounts` INNER JOIN `companies` ON `accounts`.`id` = `companies`.`id` ORDER BY `$sortTable`.`$sortField` $sortDir, `companies`.`oname` ASC;");
			
			for ($i=0; $i < mysql_num_rows ($q); $i++) {
				$f = mysql_fetch_array($q);
				$array[$i]['id'] = id2account ( $f['id'] );
				$array[$i]['oname'] = $f['oname'];
				$array[$i]['balance'] = balance_format ($f['balance']*$rates[$f['currency']]);			
			}
			break;
	}
	return $array;
}


function getGroupsList () {
	$q = mysql_query ("SELECT * FROM `groups`");
	for ($i=0; $i < mysql_num_rows ($q); $i++) {
		$f = mysql_fetch_array($q);
		$list[$f['bankname']] = $f['name'];
	}
	return $list;
}

function getCurrencyList() {
	$q = mysql_query ("SELECT * FROM `currency`");
	for ($i=0; $i < mysql_num_rows ($q); $i++) {
		$f = mysql_fetch_array($q);
		$list[$f['bankname']] = $f['name'];
	}
	return $list;
}

function getStatesList() {
	$q = mysql_query ("SELECT * FROM `states`");
	for ($i=0; $i < mysql_num_rows ($q); $i++) {
		$f = mysql_fetch_array($q);
		$list[$f['bankname']] = $f['name'];
	}
	return $list;
}
function getStatesAccounts () {
	$q = mysql_query ("SELECT * FROM `states`");
	for ($i=0; $i < mysql_num_rows ($q); $i++) {
		$f = mysql_fetch_array($q);
		$list[$f['bankname']] = $f['account_id'];
	}
	return $list;
}
function getStatesBalance() {
	$q = mysql_query ("SELECT * FROM `states`");
	for ($i=0; $i < mysql_num_rows ($q); $i++) {
		$f = mysql_fetch_array($q);
		$list[$f['bankname']] = balance_format ( balance( $f['account_id'] ) );
	}
	return $list;
}

function getRates() {
	$q = mysql_query ("SELECT * FROM `currency`");
	for ($i=0; $i < mysql_num_rows ($q); $i++) {
		$f = mysql_fetch_array($q);
		$list[$f['bankname']] = $f['rate'];
	}
	return $list;
}

function accountIsActive ( $account_id ) {
	if ( !($id = account2id ($account_id) ) ) return FALSE;
	
	$q = mysql_query ("SELECT * FROM `accounts` WHERE `id`='$id' AND `blocked`!='1'");
	if (mysql_num_rows($q) == 1) return TRUE;
	 else return FALSE;
}



function pay_salary ($account_id, $n, $pin) {
	global $account;
	
	if ( !($id = account2id ($account_id) ) ) return FALSE;
	if ($n>$account['balance']) report_error('Невозможно выдать зарплату: недостаточно средств');
	
	if (!($user_id = check_password($account_id, $pin))) return FALSE;
	
	
	$boss = TRUE;
	
	foreach ($account['users'] as $key=>$uid) {
		if ($user_id == $uid) $user_percent = $account['user_percent'][$key];
	}
	
	foreach ($account['users'] as $key=>$uid) {
		if ($user_id == $uid) continue;
		if ($account['user_percent'][$key]>$user_percent) { $boss = FALSE; report_error('Невозможно выдать зарплату: Вы не руководитель компании'); }
	}
	if ($boss) {
		mysql_query ("START TRANSACTION;");
		$state_prev = '';
		foreach ($account['users'] as $key=>$uid) {
			
			if ($account['user_percent'][$key]==0) continue;
			
			$id_from = account2id ($account['id']);		
			$id_to = $uid;
			$currency = $account['currency'];
		
			$un = $account['user_percent'][$key]*0.01*$n;
			if ($un <= 0) report_error ('Сумма перевода должна быть положительной!');

			$q = mysql_query("SELECT * FROM `accounts` WHERE id='$id_to'");
			if( mysql_num_rows($q) == 0 ) {
				report_error("Неизвестный номер счета получателя");
				return FALSE;
			}

			if ( change_balance ($id_from, -$un, $currency ) && change_balance ($id_to, $un, $currency) ) {}
			else {
				report_error("Произошла ошибка во время перевода денег. Перевод был отменен. Попробуйте еще раз");
				return FALSE;
			}
			
			$user = get_account_info( id2account($uid) );
			
			if ( $state_prev == '' ) $statebonus = true;			
			else if ( $state_prev != $user['state'] ) $statebonus = false;
			$state_prev = $user['state'];
			
			if (!mysql_query("
			INSERT INTO `logs_money` (`id_from`,`id_to`,`ip`,`money`,`currency`,`comment`)
			VALUES ('$id_from','$id_to','$_SERVER[REMOTE_ADDR]','$un','$currency','Зарплата')
			") ) {
				report_error("Произошла ошибка записи в логи. Перевод был отменен");
				return FALSE;
			}			
		}
		/*
		if ( $statebonus ) {
			foreach ($account['users'] as $key=>$uid) {
				if ($account['user_percent'][$key]==0) continue;
				$un = $account['user_percent'][$key]*0.01*$n;
				if ( !increase_state_balance ( id2account($uid), $un ) ) {
					report_error("Произошла ошибка начисления бонусов для племени. Операция прервана");
					return FALSE;
				}
			}
		}
		*/
		if ( !mysql_query ("
			UPDATE `companies` SET `balance_all` = `balance_all`+'$n'
			WHERE `id` ='$id' LIMIT 1 ;") ) {
			report_error ('Произошла ошибка во время выполнения запроса. Пожалуйста, попробуйте еще раз');
			return FALSE;
		}
		
		if (!mysql_query ("COMMIT;")) {
			report_error ('Произошла ошибка во время выполнения запроса. Пожалуйста, попробуйте еще раз');
			return FALSE;
		}
		
		return TRUE;
	}
	return FALSE;
}


///////////////////////////////////////////////////////////////////////////////
//		Администраторские функции
///////////////////////////////////////////////////////////////////////////////

// returns 1 or 2 just for mysql
function hashState ( $name, $surname ) {
	$str = $name.$surname;
	$num = 0;
	for ( $i=0; $i<strlen($str); $i++ ) {
		$code = ord($str[$i]);
		if ( $code != 208 ) $num+=$code;
	}
	return $num%91%43%17%2+1;
}

function addUser ( $name, $surname, $litgroup, $photo_url, $pin, $balance, $group ) {
	global $account;
	
	$arg = func_get_args();
	
	foreach ( $arg as $key=>$value ) {
		if ( $value == '' ) {
			report_error ("Форма заполнена не полностью");
			return FALSE;
		}
	}
	
	if ( isset ($arg[7]) && $arg[7]=='mass' ) $blocked = 1;
		else $blocked = 0;
		
	foreach (getCurrencyList() as $bankname=>$cur_name) {
		if ( isset ($arg[7]) && $arg[7]==$bankname ) {
			$currency = $arg[7];
			break;
		}
		else $currency = '';
	}
	
	$q = mysql_query ("SELECT * FROM `users` WHERE `name` = '$name' AND `surname` = '$surname' AND `litgroup` = '$litgroup';");
	if ( mysql_num_rows ($q) > 0 ) {
		if ( isset ($arg[7]) && $arg[7]=='mass' ) return FALSE;
		else report_error ("Пользователь с такими именем, фамилией и группой уже существует");
	}
	
	mysql_query ("START TRANSACTION;");
	
	$q = mysql_query ("SELECT MAX(`id`) FROM `users`");
	$f = mysql_fetch_array ($q);
	$id = $f['MAX(`id`)'] + 1;
	
	if ( !mysql_query ("
	INSERT INTO `accounts` (`id`, `balance`, `blocked`, `currency`, `TimeModify`)
	VALUES ('$id', '$balance', '$blocked', '$currency', NOW() )") ) {
		report_error ("Произошла ошибка создания банковского счета");
		return FALSE;
	}
	$hash = crypt ($pin);
	if ( !mysql_query ("
	INSERT INTO `users` (`id`, `name`, `surname`, `litgroup`, `photo_url`, `hash`, `state`)
	VALUES ('$id', '$name', '$surname', '$litgroup', '$photo_url', '$hash', '".hashState($name, $surname)."')") ) {
		report_error ("Произошла ошибка создания пользователя");
		return FALSE;
	}
	
	if ( !updateUsersGroup ( $id, $group ) ) {
		mysql_query ("ROLLBACK;");
		return FALSE;
	}
	
	if ( !mysql_query ("
	INSERT INTO `logs_admin` (`admin_id`, `account_id`, `action`, `ip`)
	VALUES ($account[id], $id, 'Создание пользователя', '$_SERVER[REMOTE_ADDR]');") ) {
		report_error ("Произошла ошибка записи в логи. Пользователь не был добавлен");
		return FALSE;
	}
	
	mysql_query ("COMMIT;");
	
	return id2account ($id);
}

function editUser ( $account_id, $name, $surname, $litgroup, $photo_url, $group, $state ) {
	global $account;
	
	$arg = func_get_args();
	
	foreach ( $arg as $key=>$value ) {
		if ( $value == '' ) {
			report_error ("Форма заполнена не полностью");
			return FALSE;
		}
	}
	
	$id = account2id ($account_id);
	
	$q = mysql_query ("SELECT * FROM `users` WHERE `name` = '$name' AND `surname` = '$surname' AND `litgroup` = '$litgroup';");
	for ($i=0; $i<mysql_num_rows($q); $i++) {
		$f = mysql_fetch_array($q);
		if ( $f['id'] != $id ) {
			echo $f['id'].' '.$id;
			report_error ("Пользователь с такими именем, фамилией и группой уже существует");
			return FALSE;
		}
	}
	
	$q = mysql_query ("SELECT * FROM `users` WHERE `id` = '$id'");
	$old = mysql_fetch_array($q);
	
	mysql_query ("START TRANSACTION;");
	
	if ( !mysql_query ("
	UPDATE `users` SET `name` = '$name', `surname` = '$surname', `litgroup` = '$litgroup', `photo_url` = '$photo_url', `state` = '$state'
	WHERE `id` = '$id' LIMIT 1 ;") ) {
		report_error ("Произошла ошибка изменения пользователя"); 
		mysql_query ("ROLLBACK;");
		return FALSE;
	}
	
	if ( !updateUsersGroup ( $id, $group ) ) {
		mysql_query ("ROLLBACK;");
		return FALSE;
	}
	
	$log = 'Редактирование пользователя '.$id.'. Старые значения: name:'.$old['name'].' surname:'.$old['surname'].' litgroup:'.$old['litgroup'].', photo_url:'.$old['photo_url'].' group:';
	foreach ($group as $bankgroup) {
		$log .= $bankgroup.',';
	}
	
	if ( !mysql_query ("
	INSERT INTO `logs_admin` (`admin_id`, `account_id`, `action`, `ip`)
	VALUES ('$account[id]', '$id', '$log', '$_SERVER[REMOTE_ADDR]');") ) {
		report_error ("Произошла ошибка записи в логи. Пользователь не был добавлен");
		return FALSE;
	}
	
	mysql_query ("COMMIT;");
	
	return TRUE;
}


function deleteUser ($account_id) {
	global $account;
	
		if ( $account_id == '' ) {
			report_error ("Вы не ввели номер пользователя");
			return FALSE;
		}
	
	$id = account2id ($account_id);
	
	if(!mysql_query ("SELECT * FROM `users` WHERE `id` = '$id';"))
  {  
			report_error ("Пользователь с такими номером не существует");
			return FALSE;
  }
	
	$q = mysql_query ("SELECT * FROM `users` WHERE `id` = '$id';");
	$oldInfo = mysql_fetch_array($q);   
	$w = mysql_query ("SELECT * FROM `usersgroup` WHERE `id` = '$id';");
    
    
	
	mysql_query ("START TRANSACTION;");
	
	if ( !mysql_query ("DELETE FROM `accounts` WHERE `accounts`.`id` = '$account_id' LIMIT 1;") ) {
		report_error ("Произошла ошибка удаления аккаунта банка"); 
		mysql_query ("ROLLBACK;");
		return FALSE;
	}  
	if ( !mysql_query ("DELETE FROM `users` WHERE `users`.`id` = '$id' LIMIT 1;") ) {
		report_error ("Произошла ошибка удаления пользователя"); 
		mysql_query ("ROLLBACK;");
		return FALSE;
	}
	  
	if ( !mysql_query ("DELETE FROM `usersgroup` WHERE `usersgroup`.`id` = '$id';") ) {
		report_error ("Произошла ошибка удаления групп пользователя"); 
		mysql_query ("ROLLBACK;");
		return FALSE;
	}
  
  if(accounttype($account_id == 'company') {
    if ( !mysql_query ("DELETE FROM `companies` WHERE `companies`.`id` = '$id';") ) {
		  report_error ("Произошла ошибка удаления записи о предприятии"); 
		  mysql_query ("ROLLBACK;");
		  return FALSE;
	  }
  }
	               
  if(accounttype($account_id == 'user') {
	 $log = 'Удаление пользователя '.$id.'. Параметры аккаунта: name:'.$oldInfo['name'].' surname:'.$oldInfo['surname'].' litgroup:'.$oldInfo['litgroup'].', photo_url:'.$oldInfo['photo_url'].' group:';
	
    for ($i=0; $i<mysql_num_rows($w); $i++) {
	   $oldGroups = mysql_fetch_array($w); 
	   $log .= $oldGroups["bankgroup"].',';
    }
  }
  else
  { 
    $log = 'Удаление компании '.$id.'. Параметры аккаунта: name:'.$oldInfo['oname'].' balance_all:'.$oldInfo['balance_all'];
	}
	
	if ( !mysql_query ("
	INSERT INTO `logs_admin` (`admin_id`, `account_id`, `action`, `ip`)
	VALUES ('$account[id]', '$id', '$log', '$_SERVER[REMOTE_ADDR]');") ) {
		report_error ("Произошла ошибка записи в логи. Компания не была удаена");
		return FALSE;
	}
	
	mysql_query ("COMMIT;");
	
	return TRUE;
}


function updateUsersGroup ( $id, $group ) {
	mysql_query ("START TRANSACTION;");
	
	if ( !mysql_query ("DELETE FROM `usersgroup` WHERE `id`='$id';") ) {
		report_error ("Произошла ошибка обновления групп пользователя");
		return FALSE;
	}
	
	foreach ($group as $bankgroup) {
		if (!mysql_query ("
			INSERT INTO `usersgroup` (`id` , `bankgroup`)
			VALUES ('$id', '$bankgroup');") ) {
			
			report_error ("Произошла ошибка обновления групп пользователя");
			return FALSE;
		}
	}
	
	mysql_query ("COMMIT;");
	return TRUE;
}

function addCompany ( $name, $currency ) {
	global $account;
	
	$arg = func_get_args();
	foreach ( $arg as $key=>$value ) {
		if ( $value == '' ) {
			report_error ("Форма заполнена не полностью");
			return FALSE;
		}
	}
	
	$q = mysql_query ("SELECT * FROM `companies` WHERE `oname` = '$name';");
	if ( mysql_num_rows ($q) > 0 ) {
		report_error ("Компания с такими названием уже существует");
		return FALSE;
	}
	
	$q = mysql_query ("SELECT MAX(`id`) FROM `companies`");
	$f = mysql_fetch_array ($q);
	if ( ($id = $f['MAX(`id`)'] + 1) == 1 ) $id = 1001;
	
	if ( !mysql_query ("
	INSERT INTO `accounts` (`id`, `currency`, `TimeModify`)
	VALUES ('$id', '$currency', NOW() )") ) {
		report_error ("Произошла ошибка создания банковского счета");
		return FALSE;
	}
	if ( !mysql_query ("
	INSERT INTO `companies` (`id`, `oname`)
	VALUES ('$id', '$name')") ) {
		report_error ("Произошла ошибка создания пользователя"); 
		mysql_query ("DELETE FROM `accounts` WHERE `accounts`.`id` = $id LIMIT 1");
		return FALSE;
	}
	
	mysql_query ("INSERT INTO `logs_admin` (`admin_id`, `account_id`, `action`, `ip`)
	VALUES ($account[id], $id, 'Создание компании', '$_SERVER[REMOTE_ADDR]');");
	
	return id2account ($id);
}

function updateCompanyUsers ( $account_id, $companyusers ) {
	$id = account2id ($account_id);
	
	mysql_query ("START TRANSACTION;");
	
	if ( !mysql_query ("DELETE FROM `company_participators` WHERE `oid`='$id';") ) {
		report_error ("Произошла ошибка обновления списка работающих в компании");
		return FALSE;
	}
	
	foreach ($companyusers as $user_id=>$percent) {
		if (!mysql_query ("
			INSERT INTO `company_participators` (`oid`, `uid`, `percentage`)
			VALUES ('$id', '$user_id', '$percent');") ) {
			
			report_error ("Произошла ошибка обновления списка работающих в компании");
			return FALSE;
		}
	}
	
	mysql_query ("COMMIT;");
	return TRUE;
}

function generatePin () {
	$pin = 0;
	do {
		$pin = $pin * 10 + rand (0,9);
	} while ( strlen ($pin) < 6 );
	return $pin;
}

function setBlockFlag ( $account_id, $value ) {
	global $account;

	$id = account2id ( $account_id );
	if ( !mysql_query ("UPDATE `accounts` SET `blocked` ='$value' WHERE `id` =$id LIMIT 1 ;") ) {
		report_error ("Произошла ошибка изменения счета в БД"); 
		return FALSE;
	}
	mysql_query ("INSERT INTO `logs_admin` (`admin_id`, `account_id`, `action`, `ip`)
	VALUES ($account[id], $id, 'Изменение флага блокировки на $value', '$_SERVER[REMOTE_ADDR]');");
	return TRUE;
}

function updatePin ( $account_id, $pin ) {
	global $account;

	$id = account2id ($account_id);
	$hash = crypt ($pin);
	if ( !mysql_query ("UPDATE `users` SET `hash` ='$hash' WHERE `id` =$id LIMIT 1 ;") ) {
		report_error ("Произошла ошибка изменения счета пользователя в БД"); 
		return FALSE;
	}
	
	mysql_query ("INSERT INTO `logs_admin` (`admin_id`, `account_id`, `action`, `ip`)
	VALUES ($account[id], $id, 'Сброс ПИНа', '$_SERVER[REMOTE_ADDR]');");
	
	return TRUE;
}

function activate ( $account_id ) {
	global $account;
	
	$account_to_activate = get_account_info($account_id);
		
	if ( $account_to_activate['blocked']!=1 || $account_to_activate['balance'] != 0 )
		report_error ("Аккаунт не нуждается в активации");

	$id = account2id ($account_id);
	
	if (
		!mysql_query ("UPDATE `accounts` SET `blocked` ='0', `balance`='0' WHERE `id` =$id LIMIT 1 ;") || 
		!transmit ( 0, $account_id, start_balance, '', 'Зачисление стартовой суммы' )
		) {
		report_error ("Произошла ошибка изменения счета пользователя в БД"); 
		return FALSE;
	}
	mysql_query ("INSERT INTO `logs_admin` (`admin_id`, `account_id`, `action`, `ip`)
	VALUES ($account[id], $id, 'Активация', '$_SERVER[REMOTE_ADDR]');");
	
	return TRUE;
}

function import_people_lit_msu_ru () {
	if ( !defined("register") || register !== 'import_people_lit_msu_ru' ) die;
	
	$url = "http://people.lit.msu.ru/people.php";
	$litgroup = array ('staff', 'students');
	
	foreach ( $litgroup as $table ) {
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url.'?table='.$table);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 60);
		$response = curl_exec($ch);
		if ( curl_getinfo($ch,CURLINFO_HTTP_CODE) !== 200 ){
			$response = "An error occured while communicating people.lit.msu.ru. Try again later";
		}
		
		if ( $xml = simplexml_load_string($response) ) {
			for ($i=0; $i < $xml->count(); $i++) {
				$person = (array) $xml->person[$i];
				if ($table == 'staff') $person['litgroup'] = 'Преподаватель';
				else
					if ($table == 'students') {
						if ($person['grade']==0 || $person['group']==0) continue;
						$person['litgroup'] = $person['grade'].'.'.$person['group'];
					}
				if ( $person['photo_url'] == '' ) $person['photo_url'] = 'nophotoexists';
				if ( addUser ( $person['name'], $person['surname'], $person['litgroup'], $person['photo_url'], 'null', '0', array(), 'mass' ) )
					echo $person['name'].' '.$person['surname'].' успешно добавлен<br />';
			}
		}
	}
}

function reload_rates() {
	$q = mysql_query ("SELECT SUM(`balance`) FROM `accounts` WHERE `blocked` != '1' AND `currency` = 'piastre';");
	$f = mysql_fetch_array($q);	
	$piastre = $f['SUM(`balance`)'];
	$q = mysql_query ("SELECT SUM(`balance`) FROM `accounts` WHERE `blocked` != '1' AND `currency` = 'boubloon';");
	$f = mysql_fetch_array($q);
	$boubloon = $f['SUM(`balance`)'];
	
	$rel = $piastre/$boubloon;
	
	if ($rel>1) { $kp=+0.001; $kb=-0.001; }
	else  { $kp=-0.001; $kb=+0.001; }
	(float) $p = 1; (float) $b = 1;
	while (1) {	
		if ($rel>1) { if ( ($p+$kp)/($rel)/($b+$kb) > 1.0) break; }
		else { if ( ($p+$kp)/($rel)/($b+$kb) < 1.0) break; }	
		$p+=$kp;
		$b+=$kb;	
	}
	
	mysql_query ("UPDATE `currency` SET `rate` = '$b' WHERE `bankname` = 'boubloon' LIMIT 1 ;");
	mysql_query ("UPDATE `currency` SET `rate` = '$p' WHERE `bankname` = 'piastre' LIMIT 1 ;");
	
	return TRUE;
}

function increase_balances() {
	mysql_query ("START TRANSACTION;");
	$q = mysql_query ("SELECT * FROM `accounts` WHERE `id` > 0 AND `id`< 1000 AND `blocked` != '1' AND `balance` > 0;");
		
	for ($i=0; $i<mysql_num_rows($q); $i++) {
		$f = mysql_fetch_array($q);
		
		$activity = mysql_query ("
		SELECT *  FROM `logs_money` WHERE (`id_to` = '".$f['id']."') AND `timestamp`>DATE_SUB(NOW(), INTERVAL 1 DAY) AND `ip`!='bank'");
		if (mysql_num_rows($activity)==0) continue;
		
		if ( !mysql_query ("UPDATE `accounts` SET `balance`=`balance`+`balance`*0.05 WHERE `id` = '".$f['id']."' LIMIT 1;") ) report_error ("Не удалось изменить счета участников игры");
		
		if ( !mysql_query("
		INSERT INTO `logs_money` (`id_from`, `id_to`,`ip`,`money`,`currency`,`comment`)
		VALUES ('0', '".$f['id']."','bank','".($f['balance']*0.05)."','".$f['currency']."','Премия за активность')
		") ) report_error ("Произошла ошибка записи в логи. Операция прервана");
	}

	mysql_query ("COMMIT;");
	return TRUE;
}

function increase_state_balances() {
	$state_accounts = getStatesAccounts();
	foreach ( $state_accounts as $key=>$value ) $state_increase[$key] = 0;

	mysql_query ("START TRANSACTION;");
	$q = mysql_query ("SELECT * FROM `accounts` WHERE `id` > 0 AND `id`< 1000 AND `blocked` != '1' AND `balance` > 0;");
	
	for ($i=0; $i<mysql_num_rows($q); $i++) {
		$f = mysql_fetch_array($q);
		unset($diff); unset($f_salaries); unset($f_income); unset($f_outgoing);
		
		$cur_user = get_account_info(id2account($f['id']));
		//$state_increase[$cur_user['state']] += 50;
		/*
		$salaries = mysql_query ("
		SELECT sum(`money`)  FROM `logs_money` WHERE `id_from` >=1000 AND (`id_to` = '".$f['id']."') AND `timestamp`>DATE_SUB(NOW(), INTERVAL 1 DAY)
		");
		$f_salaries = mysql_fetch_array($salaries);
		if ($f_salaries['sum(`money`)']!==null) $state_increase[$cur_user['state']] += 0.1*$f_salaries['sum(`money`)'];
		*/
		$income = mysql_query ("
		SELECT sum(`money`)  FROM `logs_money` WHERE (`id_to` = '".$f['id']."') AND `timestamp`>DATE_SUB(NOW(), INTERVAL 1 DAY) AND `ip`!='bank'");
		$f_income = mysql_fetch_array($income);		
		if ($f_income['sum(`money`)']==null) continue;
		
		$outgoing = mysql_query ("
		SELECT sum(`money`)  FROM `logs_money` WHERE (`id_from` = '".$f['id']."') AND `timestamp`>DATE_SUB(NOW(), INTERVAL 1 DAY) AND `ip`!='bank'");
		$f_outgoing = mysql_fetch_array($outgoing);		
		if ($f_outgoing['sum(`money`)']==null) continue;
		
		if ( ( $diff = ($f_income['sum(`money`)'] - $f_outgoing['sum(`money`)']) ) > 0 ) {
			$state_increase[$cur_user['state']] += 0.1*$diff;
		}
	}
	
	foreach ( $state_accounts as $key=>$value )
		transmit (0, $value, $state_increase[$key], '', 'Премия государства в конце игрового дня');
	
	mysql_query ("COMMIT;");
	return TRUE;
}

function collect_taxes() {	
	$state_accounts = getStatesAccounts();	

	mysql_query ("START TRANSACTION;");
	
	// налоги для leftwing
	$tax = 10;
	$account_id_to = $state_accounts['leftwing'];
	foreach ( formAccountArray ('user') as $account )
		if ( $account['state'] == 'leftwing' ) {
			$q = mysql_query("SELECT * FROM  `accounts` WHERE  `id` = '".$account['id']."' AND `balance` < $tax");
			if (mysql_num_rows($q)!==0) continue;
			$q = mysql_query("SELECT * FROM  `company_participators` WHERE  `uid` = '".$account['id']."'");
			if (mysql_num_rows($q)==0) {
				$accountlist[]=$account['id'];
				$currency[]=$account['currency'];
			}
		}
	foreach ( $accountlist as $key=>$id ) {
		$account_id_from = id2account($id);
		if (!transmit ($account_id_from, $account_id_to, $tax, $currency[$key], 'Налог государства')) {
			mysql_query ("ROLLBACK;");
			return FALSE;
		}
	}
	unset ($accountlist);
	// налоги для rightwing
	$tax = 15;
	$account_id_to = $state_accounts['rightwing'];
	foreach ( formAccountArray ('user') as $account )
		if ( $account['state'] == 'rightwing' ) {
			$q = mysql_query("SELECT * FROM  `accounts` WHERE  `id` = '".$account['id']."' AND `balance` < $tax");
			if (mysql_num_rows($q)!==0) continue;
			$accountlist[]=$account['id'];
			$currency[]=$account['currency'];
		}
	foreach ( $accountlist as $key=>$id ) {
		$account_id_from = id2account($id);
		if (!transmit ($account_id_from, $account_id_to, $tax, $currency[$key], 'Налог государства')) {
			mysql_query ("ROLLBACK;");
			return FALSE;
		}
	}
	
	mysql_query ("COMMIT;");
	return TRUE;
}  

function distribute_state_balances() {	
	$state_accounts = getStatesAccounts();	

	mysql_query ("START TRANSACTION;");
	
	// распределение для rightwing
	$infium = 500; //Нижняя граница, с которой начинают начисляться деньги
  $active_accounts_count = 0;//Количество аккаунтов, на которые нужно перечислять деньги
  $account_has_governement_group = FALSE; //Определяет, являеться ли данный аккаунт членом правительства
	$rightwing_id = $state_accounts['rightwing'];
	foreach ( formAccountArray ('user') as $account )
  {
    $account_has_governement_group = FALSE;         
	  $q = mysql_query("SELECT * FROM  `usersgroup` WHERE  `id` = '".$account['id']."' AND `bankgroup` = 'government'");
    if (mysql_num_rows($q)!==0) $account_has_governement_group = TRUE; 
		if ( $account['state'] == 'rightwing' || $account_has_governement_group == TRUE) {
			$q = mysql_query("SELECT * FROM  `accounts` WHERE  `id` = '".$account['id']."' AND `balance` < $infium");
			if (mysql_num_rows($q)!==0)continue;
      
      $active_accounts_count++; 
			$accountlist[]=$account['id'];
			$currency[]=$account['currency'];
		} 
  } 
    
    $q = mysql_query("SELECT * FROM  `accounts` WHERE  `id` = '".$rightwing_id."';");  
    $f = mysql_fetch_array($q);
    $rightwing_balance = $f['balance'];
    
    $summ_to_distribute = $rightwing_balance / $active_accounts_count; 
    
	foreach ( $accountlist as $key=>$id ) {
		$account_id_to = id2account($id);
		if (!transmit ($rightwing_id, $account_id_to, (int)$summ_to_distribute, $currency[$key], 'Распределение бюджета государства')) {
			mysql_query ("ROLLBACK;");
			return FALSE;
		}
	}
	
	mysql_query ("COMMIT;");
	return TRUE;
}
/*
function increase_state_balance( $account_id, $n ) {
	$user = get_account_info( $account_id );
	if ( !mysql_query ("
	UPDATE `states` SET `balance` =  `balance`+'".$n."' WHERE  `states`.`bankname` =  '".$user['state']."';
	") ) return false;
	return true;
}
*/


function getMoneyLog ( $account_id_from, $account_id_to ) {
	$sql = "SELECT * FROM  `logs_money`";
	
	if ( $account_id_from !== '' || $account_id_to!=='' ) $sql .= " WHERE ";
	if ( $account_id_from !== '' ) {
		$id_from = account2id ( $account_id_from );
		$sql .= "`id_from`=$id_from";
	}
	if ( $account_id_from !== '' && $account_id_from == $account_id_to ) $sql .= " OR ";
	else
		if ( $account_id_from !== '' && $account_id_to!=='' ) $sql .= " AND ";
	if ( $account_id_to !== '' ) {
		$id_to = account2id ( $account_id_to );
		$sql .= "`id_to`=$id_to";
	}
	
	if ( !($q = mysql_query ($sql))) {
		report_error ("Не удалось получить статистику из базы данных"); 
	}
	$sum = 0;
	for ($i = 0; $i < mysql_num_rows ($q); $i++) {
		$f = mysql_fetch_array($q);
		
		$money = $f['money'];
		if ( $account_id_from == $account_id_to && $account_id_from == id2account($f['id_from']) ) $money *= (-1);
		
		$log[$i]['account_id_from'] = id2account($f['id_from']);
		$log[$i]['account_id_to'] = id2account($f['id_to']);
		$log[$i]['money'] = $money;
		$log[$i]['comment'] = $f['comment'];
		$log[$i]['timestamp'] = $f['timestamp'];
		$sum += $f['money'];
	}
	
	return array( 'logs'=>$log, 'sum'=>$sum );
}










function add_vote ( $vote_topic, $vote_variants, $state_filter, $active_flag ) {
	mysql_query ("START TRANSACTION;");
	
	$q = mysql_query ("SELECT MAX(`id`) FROM `votes_list`");
	$f = mysql_fetch_array ($q);
	$id = $f['MAX(`id`)'] + 1;
	
	if ( !mysql_query("
		INSERT INTO `votes_list` (`id`, `topic`, `variants_num`, `state_filter`, `active`)
		VALUES ('$id', '$vote_topic', '".count($vote_variants)."', '$state_filter', '$active_flag');
	") ) {
		report_error("Не удалось добавить опрос в таблицу опросов");
		return FALSE;
	}
	
	foreach ($vote_variants as $variant_id=>$text) {
		if ( !mysql_query("
			INSERT INTO `votes_variants` (`vote_id`, `variant_id`, `text`)
			VALUES ('$id', '$variant_id', '$text');
		") ) {
			report_error("Не удалось добавить варианты ответа для опроса");
			return FALSE;
		}
	}

	mysql_query ("COMMIT;");
	return TRUE;
}

function get_votes_list ( $state_filter, $active_flag ) {

	$sql = "SELECT * FROM `votes_list` WHERE `active`='$active_flag'";
	if ($state_filter !== '') $sql .= " AND (`state_filter`='' OR `state_filter`='$state_filter');";
	
	if ( !($q = mysql_query ($sql)) ) {
		report_error("Не удалось получить список голосований из БД");
		return FALSE;
	}
	for ($i = 0; $i < mysql_num_rows ($q); $i++) {
		$f = mysql_fetch_array($q);
		$votes[$i]['id'] = $f['id'];
		$votes[$i]['topic'] = $f['topic'];
	}
	
	return $votes;
}

function get_vote_data ( $vote_id ) {
	if ( !($q = mysql_query ("SELECT * FROM `votes_list` WHERE `id`='$vote_id'")) ) {
		report_error("Не удалось информацию о голосовании из БД");
		return FALSE;
	}
	if ( mysql_num_rows ($q) !== 1 ) report_error("Голосование не существует");
	else $f = mysql_fetch_array($q);
	$vote_topic = $f['topic'];
	$state_filter = $f['state_filter'];
	$active_flag = $f['active'];
	
	if ( !($q = mysql_query ("SELECT * FROM `votes_variants` WHERE `vote_id`='$vote_id'")) ) {
		report_error("Не удалось информацию о голосовании из БД");
		return FALSE;
	}
	for ($i = 0; $i < mysql_num_rows ($q); $i++) {
		$f = mysql_fetch_array($q);
		$vote_variants[$f['variant_id']] = $f['text'];
	}
	if ( !($q = mysql_query ("SELECT * FROM `votes_choices` WHERE `vote_id`='$vote_id'")) ) {
		report_error("Не удалось информацию о голосовании из БД");
		return FALSE;
	}
	$votes_count = mysql_num_rows ($q);
	return array (
		'vote_topic'		=> $vote_topic,
		'state_filter'		=> $state_filter,
		'active_flag'		=> $active_flag,
		'vote_variants'		=> $vote_variants,
		'votes_count' 		=> $votes_count
	);
}


function save_vote ( $vote_id, $vote_topic, $vote_variants, $state_filter, $active_flag ) {
	$vote = get_vote_data ( $vote_id );
	mysql_query ("START TRANSACTION;");
	
	if ( $vote['votes_count'] > 0 ) {
		// только флаг актвности и фильтр по гос-ву
		if ( !mysql_query("
			UPDATE `votes_list`
			SET	`state_filter` = '$state_filter',
				`active` =  '$active_flag'
			WHERE  `votes_list`.`id` = '$vote_id';
		") ) {
			report_error("Не удалось сохранить опрос");
			return FALSE;
		}
		mysql_query ("COMMIT;");
		return TRUE;
	}
	else {
		// все
		if (
		!mysql_query("
			UPDATE `votes_list`
			SET	`topic` = '$vote_topic',
				`state_filter` = '$state_filter',
				`active` =  '$active_flag'
			WHERE  `votes_list`.`id` = '$vote_id';
		") ||
		!mysql_query("DELETE from `votes_variants` WHERE `votes_variants`.`vote_id` = '$vote_id';")
		) {
			report_error("Не удалось сохранить опрос");
			return FALSE;
		}
		foreach ($vote_variants as $variant_id=>$text) {
			if ( !mysql_query("
				INSERT INTO `votes_variants` (`vote_id`, `variant_id`, `text`)
				VALUES ('$vote_id', '$variant_id', '$text');
			") ) {
				report_error("Не удалось сохранить варианты ответа для опроса");
				return FALSE;
			}
		}
		mysql_query ("COMMIT;");	
		return TRUE;	
	}
}

function get_vote_results ( $vote_id, $account ) {
	$vote = get_vote_data ( $vote_id );
	if ( $vote['state_filter'] !== '' && $vote['state_filter'] !== $account['state'] ) {
		report_error("Это голосование создано для граждан другого государства");
		return FALSE;
	}
	$account_id = $account['id'];
	$q = mysql_query ("
		SELECT * FROM `votes_choices` WHERE `vote_id`='$vote_id' AND `account_id`='$account_id';
	");
	if ( $vote['active_flag'] && mysql_num_rows ($q) == 0 ) {
		return FALSE;
	}
	else {
		foreach ($vote['vote_variants'] as $variant=>$text) $vote_choices[$variant]=0;
		if ( !($q = mysql_query ("SELECT * FROM `votes_choices` WHERE `vote_id`='$vote_id';")) ) {
			report_error("Не удалось информацию о голосовании из БД");
			return FALSE;
		}		
		for ($i = 0; $i < mysql_num_rows ($q); $i++) {
			$f = mysql_fetch_array($q);
			$vote_choices[$f['variant_id']]++;
		}
		return array(
			'vote_data'=>$vote,
			'vote_choices'=>$vote_choices
		);
	}
}

function send_vote ( $account, $vote_id, $variant_id ) {

	$vote = get_vote_data ( $vote_id );
	if ( $vote['state_filter'] !== '' && $vote['state_filter'] !== $account['state'] ) {
		report_error("Это голосование создано для граждан другого государства");
		return FALSE;
	}
	$account_id = $account['id'];

	$q = mysql_query ("
		SELECT * FROM `votes_choices` WHERE `vote_id`='$vote_id' AND `account_id`='$account_id';
	");
	if ( mysql_num_rows ($q) > 0 ) {
		report_error("Вы уже проголосовали в этом опросе. Больше нельзя ;-)");
		return FALSE;
	}
	if ( !mysql_query ("
		INSERT INTO  `votes_choices` ( `vote_id` , `account_id` , `variant_id` )
		VALUES ( '$vote_id',  '$account_id',  '$variant_id' )
	") ) {
		report_error("Не удалось отправить голос");
		return FALSE;
	}
	return TRUE;
}

?>
