<?php
// ItIsCrazyBankModule

$module = 'bankomat'; // системное название модуля

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

$modules[$module]['name'] = 'Банкомат'; // человеческое название модуля

$modules[$module]['action'][0] = 'show_balance'; // первое действие, реализуемое модулем
$modules[$module]['menu'][0] = 'Проверить баланс'; // соответствующий пункт меню
$modules[$module]['title'][0] = 'Баланс счета';

$modules[$module]['action'][1] = 'transmit_money';
$modules[$module]['menu'][1] = 'Совершить денежный перевод';
$modules[$module]['title'][1] = 'Денежный перевод';

$modules[$module]['action'][2] = 'account_history';
$modules[$module]['menu'][2] = 'История операций';
$modules[$module]['title'][2] = 'История операций по счету';

$modules[$module]['groups'][] = 'guest'; // группы, которым разрешено пользоваться модулем
$modules[$module]['groups'][] = 'company';
//$modules[$module]['groups'][] = 'admin';

function print_report ($log) {
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
	foreach ($log as $log_item) {
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
	echo '</table>';
}

function execute_transfer( $account_id_from, $account_id_to, $n, $currency, $comment )
{
	$transfersToUsersCommission = 0.25;

	if( accounttype( $account_id_to ) == 'user' )
	{
		$commission = $n * $transfersToUsersCommission;
		$res =
			transmit( $account_id_from, $account_id_to, ($n-$commission), $currency, $comment, false ) &&
			transmit( $account_id_from, 0, $commission, $currency, 'Комиссия за перевод '.$comment, false );
	}
	else
	{
		$res = transmit( $account_id_from, $account_id_to, $n, $currency, $comment );
	}
	return $res;
}

function show_bankomat( $action ) {
	global $modules, $account, $accountlist;
	$module = $modules['bankomat'];

	$currency = getCurrencyList();
	//$rates = getRates();

	switch ($action) {
		case $module['action'][0]: // Проверка баланса
			if ( isset ($_POST[$module['action'][0]]) )	{
				if ( check_password($_POST['account_id'],$_POST['pin']) ) {
					$account = get_account_info ($_POST['account_id']);
				}
			}

			if ( isset ($account['id']) ) {

				echo '<p>';
				echo 'У Вас на счету <big>'.balance_format ($account['balance']).'&nbsp;'.$currency[$account['currency']].'</big><br />';
				foreach ($currency as $cbankname=>$cname) {
					if ($cbankname !== $account['currency'])
					echo 'В пересчете это примерно <big>'.number_format ($account['balance']*$rates[$account['currency']]/$rates[$cbankname], 0).'&nbsp;'.$cname.'</big>';
				}
				echo '</p>';
				//echo '<p>Иначе у Вас на счету примерно <big>'.number_format ($account['balance']*$rates[$account['currency']], 0).'&nbsp;лит</big></p>';

			}
			else {
				echo '
				<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
				<table class="form">
					<tr><td>Номер счета:</td><td><input type="text" name="account_id" size="20" maxlength="4" /></td></tr>
					<tr><td>PIN-код:</td><td><input type="password" name="pin" size="20" maxlength="6" /></td></tr>
					<tr><td></td><td><input type="submit" name="'.$module['action'][0].'" value="Проверить баланс" /></td></tr>
				</table>
				</form>
				';
			}
			break;

		case $module['action'][1]: // Перевод денег
			$accountlist = TRUE;

			if ( isset ($_POST['confirm']) ) {
				$transmit=FALSE;
				if  (empty ($account['id']) ) {
					if ( check_password($_POST['account_id'],$_POST['pin']) ) {
						if ( execute_transfer( $_POST['account_id'], $_POST['account_id_to'], $_POST['cash'], $_POST['currency'], $_POST['comment'] ) )  $transmit=TRUE;
					}
				}
				else {
					if ( $_POST['session_id'] == session_id() ) {
						if ( execute_transfer( $_SESSION['account_id'], $_POST['account_id_to'], $_POST['cash'], $_POST['currency'], $_POST['comment'] ) ) $transmit=TRUE;
					}
					else report_error('Зафиксирована попытка обмануть банк. Аяяяй.');

				}
				echo '<p style="margin-bottom: 40px;"><i>Перевод прошел успешно!</i></p>';
			}
			if ( isset ($_POST[$module['action'][1]]) ) {
				if ( $_POST['cash'] <= 0  ) {
					echo 'Вы хотите перевести неположительную сумму. Зачем..?<br /><i><a href="javascript:history.back()">Назад</a></i>'; die; }
				if ( $_POST['comment'] == ''  ) {
					echo '<span style="color: red">Вы не указали комментарий к переводу.</span><br />В случае каких-либо проблем никто не сможет узнать, что это за перевод<br /><i><a href="javascript:history.back()">Назад</a></i>'; }
				if ( (empty ($account['id']) && $_POST['account_id'] == $_POST['account_id_to'] ) || ( isset ($account['id']) && $account['id'] == $_POST['account_id_to'] ) ) {
					echo 'Вы хотите перевести деньги себе. Зачем..?<br /><i><a href="javascript:history.back()">Назад</a></i>'; die; }

				if ( empty ($account['id']) && !accountIsActive ( $_POST['account_id'] ) ) {
					report_error ('Счет совершающего перевод не существует или заблокирован. Не удалось совершить перевод'); die; }
				if ( !accountIsActive ( $_POST['account_id_to'] ) ) {
					report_error ('Счет получателя не существует или заблокирован. Не удалось совершить перевод'); die; }

				echo '<p>Вы собираетесь перевести '.$_POST['cash'].'&nbsp;'.$currency[$_POST['currency']].' на счет '.$_POST['account_id_to'].'</p>
				<p>Информация о счете:</p>
				<p>';
				print_account_info ($_POST['account_id_to'], 'bankomat');
				echo '</p>';
				echo '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
				<p>Подтвердите, пожалуйста, перевод';
				if (empty ($account['id']) ) echo ' вводом своего ПИН-кода:
				<br /><input type="password" name="pin" />
				<input type="hidden" value="'.$_POST['account_id'].'" name="account_id" />
				';
				else echo "\n".'<input type="hidden" value="'.session_id().'" name="session_id" />';
				echo '
				<input type="hidden" value="'.$_POST['account_id_to'].'" name="account_id_to" />
				<input type="hidden" value="'.$_POST['cash'].'" name="cash" />
				<input type="hidden" value="'.$_POST['currency'].'" name="currency" />
				<input type="hidden" value="'.$_POST['comment'].'" name="comment" maxlength="128" />
				<br /><input type="submit" name="confirm" value="Подтвердить" />
				</p>
				</form>
				';
			}
			if ( isset ($_POST['confirm']) ) echo '<p>Совершить еще один перевод:</p>';
			if (!isset ($_POST[$module['action'][1]])) {
				echo '
				<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
				<table class="form">';
				if (empty ($account['id']) ) echo '
				<tr><td>Ваш номер счета:</td><td><input type="text" name="account_id" size="20" maxlength="4" /></td></tr>';
				echo '
				<tr><td>Номер счета получателя:</td><td><input type="text" name="account_id_to" id="account_id" size="20" maxlength="4" /></td></tr>
				<tr><td>Сумма перевода:</td><td><input type="text" name="cash" size="9" />
				<select name="currency">';
				foreach ($currency as $bankname=>$name) {
					if (@$account['currency']==$bankname) $sel='selected'; else $sel='';
					echo '<option value="'.$bankname.'" '.$sel.'>'.$name.'</option>';
				}
				echo '
				</select>
				</td></tr>
				<tr><td>Комментарий к переводу:</td><td><input type="text" name="comment" maxlength="128" size="30" /></td></tr>
				<tr><td></td><td><input type="submit" name="'.$module['action'][1].'" value="Совершить перевод" /></td></tr>
				</table>
				<p><b>Внимание</b>: при переводе на счета пользователей (не компаний) взимается комиссия 25 %. Получатель получит 75 % суммы.</p>
				</form>
				';
			}
			break;

		case 'account_history':

			//echo '<h2>'.$states[$account['state']].'</h2>';

			if( !@$account['account_id'] )
			{
				echo 'Невозможно получить информацию о счёте.';
				break;
			}

			$income = getMoneyLog( '', $account['account_id'] );
			$outgoing = getMoneyLog( $account['account_id'], '' );

			echo '<h3>Расходы</h3>';
			print_account_log ( $outgoing['logs'] );
			echo '<p>Сумма: '.$outgoing['sum'].'</p>';
			echo '<h3>Доходы</h3>';
			print_account_log ( $income['logs'] );
			echo '<p>Сумма: '.$income['sum'].'</p>';
			break;

		default:

	}
}

?>
