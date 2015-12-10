<?php

// Выводит все зарегистрированные ошибки
// Возвращает TRUE/FALSE в случае удачного/неудачного выполнения
// Аргументов нет
function print_errors() {
	global $errors;
	echo '</div>';
	if(isset($errors[0])) {
		echo "\n".'<div id="errors">';
		echo 'Произошли ошибки:
		<ul>';
		foreach ($errors as $key=>$value) {
			echo '<li>'.$value."</li>\n";
		}
		echo '</ul>
		<p><a href="javascript:history.back()"><i>Назад</i></a></p>
		<p>Обо всех ошибках работы системы сообщайте, пожалуйста, Коле Яковлеву&nbsp;(<a href="http://vk.com/deylak">vk.com/deylak</a>) или Денису Сумину (<a href="mailto:denis@304.ru">denis@304.ru</a>)</p>
		</div>';
		print_footer();
		die();
	}
	return TRUE;
}

function print_account_info ($account_id) {
	if ( !($account = get_account_info ($account_id)) ) { return FALSE; }

	$arg = func_get_args();

	switch ( accounttype ($account_id) ) {
		case 'user':
			$states = getStatesList();
			echo '
			<img src="'.$account['photo_url'].'" align="left" style="border: 1px solid #ccc; display: block; margin: -1px 6px 0 0; width: auto !important; width: 150px; max-width: 150px;" />
			<table class="userinfo" style="min-width: 300px;">
				<tr><td>Номер счета:</td><td>'.$account['account_id'].'</td></tr>
				<tr><td>Фамилия:</td><td>'.$account['surname'].'</td></tr>
				<tr><td>Имя:</td><td>'.$account['name'].'</td></tr>
				<tr><td>Группа:</td><td>'.$account['litgroup'].'</td></tr>
				<tr><td>Партия:</td><td>'.@$states[$account['state']].'</td></tr>';
		// лучше проверку прав текущего пользователя
			if ( @check_account_access ('admin') || @check_account_access ('bankteller') || $account['id'] == @$_SESSION['account_id'] ) {

				$currency = getCurrencyList();

				echo '
					<tr><td>Баланс счета:</td><td>'.balance_format ($account['balance'],0).'</td></tr>
					<tr><td>Валюта:</td><td>'.@$currency[$account['currency']].'</td></tr>
					<tr><td>Блокировка счета:</td><td>'.$account['blocked'].'</td></tr>
					<tr><td style="vertical-align: top;">Места работы:</td><td><ul>';
				if (!empty ($account['organization'])) foreach ($account['organization'] as $key=>$value) {
					$q = mysql_query("SELECT * FROM `companies` WHERE id='$value'");
					$f = mysql_fetch_array($q);
					if ( @check_account_access ('accountlists') )
						echo '<li><a href="?action=account_info&account_id='.id2account($value).'">'.$f['oname'].'</a>'."</li>\n";
					else
						echo '<li>'.$f['oname']."</li>\n";
				}
				echo '		</ul></td></tr>';
			}
			if ( @check_account_access ('admin') || $account['id'] == @$_SESSION['account_id'] ) {
				echo '
					<tr><td style="vertical-align: top;">Системные группы:</td><td><ul>';
				if (!empty ($account['group'])) foreach ($account['group'] as $key=>$value) {
					$q = mysql_query("SELECT * FROM `groups` WHERE bankname='$value'");
					$f = mysql_fetch_array($q);
					if ($f['name']!=='') echo '<li>'.$f['name'].'</li>';
				}
				echo '</ul></td></tr>';
			}
			echo '
			</table>'."\n";
			break;

		case 'company':
			$currency = getCurrencyList();

			echo '
			<table class="userinfo" style="min-width: 300px;">
				<tr><td>Номер счета:</td><td>'.$account['id'].'</td></tr>
				<tr><td>Название организации:</td><td>'.$account['name'].'</td></tr>
			';
			if ( @check_account_access ('admin') ) {
			echo'
				<tr><td>Баланс счета:</td><td>'.balance_format ($account['balance'],0).'</td></tr>
				<tr><td>Валюта:</td><td>'.$currency[$account['currency']].'</td></tr>
				<tr><td>Блокировка счета:</td><td>'.$account['blocked'].'</td></tr>
			';
			}
			echo '<tr><td style="vertical-align: top;">Сотрудники:</td><td><ul>';
				if (!empty ($account['users'])) foreach ($account['users'] as $key=>$value) {
					$q = mysql_query("SELECT * FROM `users` WHERE id='$value'");
					$f = mysql_fetch_array($q);
					if (empty ($f)) continue;
					if ( @check_account_access ('accountlists') )
						echo '<li><a href="?action=account_info&account_id='.id2account($f['id']).'">'.$f['name'].' '.$f['surname'].'</a>';
					else
						echo '<li>'.$f['name'].' '.$f['surname'];
					if ( check_account_access ('admin') ) echo ': '.$account['user_percent'][$key]."&nbsp;%";
					echo "</li>\n";
				}
				echo '		</ul></td></tr>
			';
			echo '
			</table>'."\n";
			break;

    case 'state':
      $currency = getCurrencyList();

			echo '
			<table class="userinfo" style="min-width: 300px;">
				<tr><td>Номер счета:</td><td>'.$account['id'].'</td></tr>
				<tr><td>Название партии:</td><td>'.$account['name'].'</td></tr>
			';
			if ( @check_account_access ('admin') ) {
			echo'
				<tr><td>Баланс счета:</td><td>'.balance_format ($account['balance'],0).'</td></tr>
				<tr><td>Валюта:</td><td>'.$currency[$account['currency']].'</td></tr>
				<tr><td>Блокировка счета:</td><td>'.$account['blocked'].'</td></tr>
			';
			}
			echo '<tr><td style="vertical-align: top;">Члены партии:</td><td><ul>';
				if (!empty ($account['users'])) foreach ($account['users'] as $key=>$value) {
					$q = mysql_query("SELECT * FROM `users` WHERE id='$value'");
					$f = mysql_fetch_array($q);
					if (empty ($f)) continue;
					if ( @check_account_access ('accountlists') )
						echo '<li><a href="?action=account_info&account_id='.id2account($f['id']).'">'.$f['name'].' '.$f['surname'].'</a>';
					else
						echo '<li>'.$f['name'].' '.$f['surname'];
					if ( check_account_access ('admin') ) echo ': '.$account['user_percent'][$key]."&nbsp;%";
					echo "</li>\n";
				}
				echo '		</ul></td></tr>
			';
			echo '
			</table>'."\n";
			break;
	}
	return TRUE;
}

function mass_print_pins () {
	if ( !defined("register") || register !== 'mass_print_pins' ) die;

	$q = mysql_query ("SELECT * FROM `accounts` INNER JOIN `users` ON `accounts`.`id` = `users`.`id` WHERE `blocked`='1' ORDER BY `litgroup`, `surname`");
	for ($i = 0; $i < mysql_num_rows ($q); $i++) {
		$f = mysql_fetch_array($q);
		$pin=generatePin();
		$print = '';
		if ( ($i+1)%6==0 ) { $print = ' style="page-break-after:always"'; }
		echo '<table border="0" id="pinstoprint"'.$print.'>';
		echo '
		<tr><th colspan="2">Персональный конверт с регистрационной информацией</th></tr>
		<tr>
			<td class="pinfo" align="right">
				<table class="form"><tr><td align="right">Номер&nbsp;банковского&nbsp;счета:</td><td>'.id2account($f['id']).'</td></tr>
				<tr><td align="right">ПИН-код:</td><td>'.$pin.'</td></tr></table>
			</td>
			<td class="pinfo">
				<table class="form"><tr><td align="right">Фамилия:</td><td>'.$f['surname'].'</td></tr>
				<tr><td align="right">Имя:</td><td>'.$f['name'].'</td></tr>
				<tr><td align="right">Группа:</td><td>'.$f['litgroup'].'</td></tr></table>
			</td>
		</tr>
		<tr><td colspan="2" class="helpnote">Запомните номер Вашего банковского счета — он понадобится Вам в игре.<br />
		Доступ к Вашему банковскому счету Вы можете получить на сайте: <u>http://crazy.lit.msu.ru</u><br />
		Храните Ваш ПИН-код только в надежном месте, это обеспечит сохранность средств на электронном счете.<br />
		Если Вы забыли Ваш ПИН-код, обратитесь к администрации Crazy-week.</td></tr>
		</table>';

		updatePin ( id2account($f['id']), $pin );
	}

}

function print_cheque () {
	$q = mysql_query ("SELECT * FROM `accounts_res` INNER JOIN `users` ON `accounts_res`.`id` = `users`.`id` WHERE `balance`>'10' ORDER BY `litgroup`, `surname`");
	if (!$q) {
		report_error("Для генерации чеков необходимо скопировать таблицу accounts в таблицу accounts_res.");
	}
	else {
		for ($i = 0; $i < mysql_num_rows ($q); $i++) {
			$f = mysql_fetch_array($q);
			$print = '';
			if ( ($i+1)%7==0 ) { $print = ' style="page-break-after:always"'; }
			echo '<table width="100%" border="0" id="chequetoprint"'.$print.'>';
			echo '
			<tr><th colspan="3">Чек для предъявления на аукционе Crazy Week</th></tr>
			<tr>
				<td class="pinfo">
					<table class="form"><tr><td align="right">Номер&nbsp;счета:</td><td>'.id2account($f['id']).'</td></tr>
					<tr><td align="right">Фамилия:</td><td>'.$f['surname'].'</td></tr>
					<tr><td align="right">Имя:</td><td>'.$f['name'].'</td></tr>
					<tr><td align="right">Группа:</td><td>'.$f['litgroup'].'</td></tr></table>
				</td>
				<td class="balance">'.number_format($f['balance'],0,'','').'</td>
				<td width="66px"><img src="./images/LITavrik.jpg" width="66px" /></td>
			</tr>
			</table>';

		}

		$q = mysql_query ("SELECT * FROM `accounts_res` INNER JOIN `companies` ON `accounts_res`.`id` = `companies`.`id` WHERE `balance`>'10' ORDER BY `oname`");
		for ($i = 0; $i < mysql_num_rows ($q); $i++) {
			$f = mysql_fetch_array($q);
			$print = '';
			if ( ($i+1)%5==0 ) { $print = ' style="page-break-after:always"'; }
			echo '<table width="100%" border="0" id="chequetoprint"'.$print.'>';
			echo '
			<tr><th colspan="3">Чек для предъявления на аукционе Crazy Week</th></tr>
			<tr>
				<td class="pinfo">
					<table class="form"><tr><td align="right">Номер&nbsp;счета:</td><td>'.id2account($f['id']).'</td></tr>
					<tr><td align="right">Название:</td><td>'.$f['oname'].'</td></tr>
			';
			$account = get_account_info(id2account($f['id']));
			echo '<tr><td style="vertical-align: top;">Сотрудники:</td><td><ul style="margin:0;padding:0;">';
					if (!empty ($account['users'])) foreach ($account['users'] as $key=>$value) {
						$q1 = mysql_query("SELECT * FROM `users` WHERE id='$value'");
						$f1 = mysql_fetch_array($q1);
						if (empty ($f1)) continue;
						echo '<li style="list-style:none;margin-left:0;">'.$f1['name'].' '.$f1['surname'].'';
						echo "</li>\n";
					}
					echo '		</ul></td></tr>
			';
			echo '</table>
				</td>
				<td class="balance">'.number_format($f['balance'],0,'','').'</td>
				<td width="66px"><img src="./images/LITavrik.jpg" width="66px" /></td>
			</tr>
			</table>';

		}
	}
}

function print_account_log ($log, $case='') {
	echo '
	<table>
		<tr>
			<th>От</th>
			<th>Кому</th>
			<th>Сумма</th>
			<th>Комментарий</th>
			<th>Время</th>
		</tr>
	';
	if ( $case == 'state_report' ) $GlobalTax = 0;
	foreach ($log as $log_item) {
		if ( $case == 'state_report' ) {
			if ( $log_item['comment'] == 'Налог партии' ) {
				$TaxesToId = $log_item['account_id_to'];
				$TimeOfTaxes = $log_item['timestamp'];
				$GlobalTax += $log_item['money'];
				continue;
			}
			else if ($GlobalTax !== 0) {
				echo '
			<tr>
				<td>Граждане</td>
				<td>'.$TaxesToId.'</td>
				<td>'.$GlobalTax.'</td>
				<td>Налог партии</td>
				<td>'.$TimeOfTaxes.'</td>
			</tr>
				';
				$GlobalTax = 0;
			}
		}
		echo '
		<tr>
			<td>'.$log_item['account_id_from'].'</td>
			<td>'.$log_item['account_id_to'].'</td>
			<td>'.balance_format($log_item['money']).'</td>
			<td>'.$log_item['comment'].'</td>
			<td>'.$log_item['timestamp'].'</td>
		</tr>
		';
	}
	echo '
	</table>
	';
}

function print_admin_log ($log) {
	echo '
	<table>
		<tr>
			<th>ID администратора</th>
			<th>ID счета, над которым совершено действие</th>
			<th>Описание действия</th>
			<th>IP-адрес</th>
			<th>Время действия</th>
		</tr>
	';
	foreach ($log as $log_item) {
		echo '
		<tr>
			<td>'.$log_item['admin_id'].'</td>
			<td>'.$log_item['account_id'].'</td>
			<td>'.$log_item['action'].'</td>
			<td>'.$log_item['ip'].'</td>
			<td>'.$log_item['time'].'</td>
		</tr>
		';
	}
	echo '
	</table>
	';
}

function print_errors_log ($log) {
	echo '
	<table>
		<tr>
			<th>ID пользователя, получившего ошибку.</th>
			<th>Текст выданной ошибки</th>
			<th>IP пользователя</th>
			<th>Время ошибки</th>
		</tr>
	';
	foreach ($log as $log_item) {
		echo '
		<tr>
			<td>'.$log_item['id'].'</td>
			<td>'.$log_item['error'].'</td>
			<td>'.$log_item['ip'].'</td>
			<td>'.$log_item['time'].'</td>
		</tr>
		';
	}
	echo '
	</table>
	';
}

function print_logins_log ($log) {
	echo '
	<table>
		<tr>
			<th>ID счета</th>
			<th>IP-адрес</th>
			<th>Флаг успешного входа</th>
			<th>Время попытки входа</th>
		</tr>
	';
	foreach ($log as $log_item) {
		echo '
		<tr>
			<td>'.$log_item['id'].'</td>
			<td>'.$log_item['ip'].'</td>
			<td>'.$log_item['success'].'</td>
			<td>'.$log_item['timestamp'].'</td>
		</tr>
		';
	}
	echo '
	</table>
	';
}

function print_money_log ($log) {
	echo '
	<table>
		<tr>
			<th>ID счета отправителя</th>
			<th>ID счета получателя</th>
			<th>Сумма перевода</th>
			<th>Валюта, в которой совершен перевод</th>
			<th>Комментарий к переводу</th>
			<th>IP-адрес</th>
			<th>Время перевода</th>
		</tr>
	';
	foreach ($log as $log_item) {
		echo '
		<tr>
			<td>'.$log_item['id_from'].'</td>
			<td>'.$log_item['id_to'].'</td>
			<td>'.$log_item['money'].'</td>
			<td>'.$log_item['currency'].'</td>
			<td>'.$log_item['comment'].'</td>
			<td>'.$log_item['ip'].'</td>
			<td>'.$log_item['timestamp'].'</td>
		</tr>
		';
	}
	echo '
	</table>
	';
}

?>
