<?php
// ItIsCrazyBankModule

$module = 'bankteller';

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

$modules[$module]['name'] = 'Касса'; // человеческое название модуля

$modules[$module]['action'][] = 'bank_give_money';
$modules[$module]['menu'][] = 'Выдать наличные';
$modules[$module]['title'][] = 'Выдача наличных';

$modules[$module]['action'][] = 'bank_take_money';
$modules[$module]['menu'][] = 'Принять наличные';
$modules[$module]['title'][] = 'Прием наличных';

$modules[$module]['action'][] = 'mass_add_money';
$modules[$module]['menu'][] = 'Массовое зачисление денег на счет';
$modules[$module]['title'][] = 'Массовое зачисление денег';

$modules[$module]['action'][] = 'user_account_history';
$modules[$module]['menu'][] = 'История по счету';
$modules[$module]['title'][] = 'История по счету';

$modules[$module]['groups'][] = 'bankteller';

function show_bankteller ($action) {
	global $modules, $account, $accountlist;	
	$module = $modules['bankteller'];
	
	$currency = getCurrencyList();
	
	switch ($action) {
		case 'bank_give_money':
			$accountlist = TRUE;
		
			if ( isset ($_POST['confirm']) ) {
				if ( transmit ($_POST['account_id'], 'bank', $_POST['n'], $_POST['currency'], 'Выдача денег в банке. Оператор '.$account['id']) ) {
				echo 'Операция успешно проведена';
				}
			}
				
			if ( isset ($_POST[$action]) ) {
				echo '
				<div>
				<p>Информация о банковском счете</p>
				<p>';
				if ( print_account_info ( $_POST['account_id'] ) ) {
				echo '</p>
					<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
						<input type="hidden" name="account_id" value="'.$_POST['account_id'].'">
						<p>Введите, пожалуйста, сумму для выдачи<br />
						<input type="text" name="n" value="100" />
						<select name="currency">';
				$acc = get_account_info ( $_POST['account_id'] );
				foreach (getCurrencyList() as $bankname=>$name) {
					if ($acc['currency']==$bankname) $sel='selected'; else $sel='';
					echo '<option value="'.$bankname.'" '.$sel.'>'.$name.'</option>';
				}
				echo '
						</select><br />
						<input type="submit" name="confirm" value="Выдать"></p>
					</form>';
				}
				echo '</div>';
			}
			if ( !empty ($_POST) ) echo '<p style="margin: 40px 0 0 0;"><i>Работать с другим счетом</i></p>';
			echo '<div>
			Введите номер банковского счета:
			<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
				<input type="text" name="account_id" id="account_id" />
				<input type="submit" name="'.$action.'" value="Выдать наличные">
			</form>
			</div>';
			
			break;
		case 'bank_take_money':
			$accountlist = TRUE;
		
			if ( isset ($_POST['confirm']) ) {
				if ( transmit ('bank', $_POST['account_id'], $_POST['n'], $_POST['currency'], 'Прием денег в банке. Оператор '.$account['id']) ) {
				echo 'Операция успешно проведена';
				}
			}
				
			if ( isset ($_POST[$action]) ) {
				echo '
				<div>
				<p>Информация о банковском счете</p>
				<p>';
				if ( print_account_info ( $_POST['account_id'] ) ) {
				echo '</p>
					<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
						<input type="hidden" name="account_id" value="'.$_POST['account_id'].'">
						<p>Введите, пожалуйста, принимаемую сумму<br />
						<input type="text" name="n" value="100" />
						<select name="currency">';
				$acc = get_account_info ( $_POST['account_id'] );
				foreach (getCurrencyList() as $bankname=>$name) {
					if ($acc['currency']==$bankname) $sel='selected'; else $sel='';
					echo '<option value="'.$bankname.'" '.$sel.'>'.$name.'</option>';
				}
				echo '
						</select><br />
						<input type="submit" name="confirm" value="Принять"></p>
					</form>';
				}
				echo '</div>';
			}
			if ( !empty ($_POST) ) echo '<p style="margin: 40px 0 0 0;"><i>Работать с другим счетом</i></p>';
			echo '<div>
			Введите номер банковского счета:
			<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
				<input type="text" name="account_id" id="account_id" />
				<input type="submit" name="'.$action.'" value="Принять наличные">
			</form>
			</div>';
			
			break;
			
		case 'mass_add_money':
			$accountlist = TRUE;
				
			if ( isset ($_POST[$action]) ) {
				$n = $_POST['n'];
				$accounts = array();
				for ($i=0; $i<$n; $i++) {
					if ($_POST['account_id'.$i]=='') continue;
					if ($_POST['cash'.$i]=='') continue;
					
					$comment = '';
					if ($_POST['comment'.$i]=='') $comment = $_POST['comment'.'0'];
						else $comment = $_POST['comment'.$i];
					
					$accounts[] = array (
						'account_id'	=>	$_POST['account_id'.$i],
						'cash'			=>	$_POST['cash'.$i],
						'currency'		=>	$_POST['currency'.$i],
						'comment'		=>	$comment
					);
				}
				foreach ($accounts as $k=>$info) {					
					if ( $info['cash'] <= 0 ) {
						echo 'Сумма перевода должна быть неотрицательной; операция прервана';
						continue;
					}
				
					$comment = $info['comment'];
					$comment.= ' Массовое зачисление. Оператор '.$account['id'];
					
					(bool) $statebonus = false;
					if ( @$_POST['statebonus']=='on' ) $statebonus = true;
					
					if ( transmit ('bank', $info['account_id'], $info['cash'], $info['currency'], $comment, $statebonus) ) {
						echo 'Операция для '.$info['account_id'].' успешно проведена';
					}
					echo '<br />';
				}
			}
			if (!isset($_POST[$action])) {
				echo '
				<p>Внимание: необходимо аккуратно заполнять эту форму.</p>
				<p>Поля с пустым номером счета игнорируются</p>
				<p>
				Пустой комментарий заменяется на первый<br />
				<small>тогда, зачисляя правительству зарплату или начисляя бонус за какой-то конкурс нескольким людям, можно заполнить форму с комментарием только один раз</small></p>
				<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
				<table class="form">';
			
				if (isset($_POST['n'])) $n = $_POST['n']; else $n = 4;
				for ($i=0; $i<$n; $i++) {
				echo '		
					<tr><td>Счет</td><td><input type="text" name="account_id'.$i.'" id="account_id'.$i.'" onfocus="setfocus(\'account_id'.$i.'\')" /></td></tr>
					<tr><td>Сумма</td><td><input type="text" name="cash'.$i.'" size="9" />
					<select name="currency'.$i.'">';
					foreach ($currency as $bankname=>$name) {
						if (@$account['currency']==$bankname) $sel='selected'; else $sel='';
						echo '<option value="'.$bankname.'" '.$sel.'>'.$name.'</option>';
					}
					echo '
					</select>
					</td></tr>
					<tr style="border-bottom: 15px solid transparent;"><td style="padding-right: 15px;">Комментарий</td><td><input type="text" name="comment'.$i.'" />
					<input type="hidden" name="n" value="'.$n.'" /></td></tr>';
				}
				echo '
					<tr><td align="right"><input type="checkbox" name="statebonus" checked /></td>
						<td><small>начислять бонусы государствам</small></td></tr>
					<tr><td></td><td><input type="submit" name="'.$action.'" value="Зачислить на счета"></td></tr>
				</table>
				</form>
				<p><br /><form method="post" name="num"><small>Количество счетов для добавления денег:&nbsp;</small><select name="n">';
					$nn = array (2,4,6,8,10);
					foreach ($nn as $num) { if ($num==$n) $sel='selected'; else $sel=''; echo '<option value="'.$num.'" '.$sel.'>'.$num.'</option>'; }
					echo '</select><input type="submit" name="" value="изменить" /></form></p>
				';
			}
			break;
			
		case 'user_account_history':
			$accountlist = TRUE;
			
			if ( ( isset ($_POST['account_id']) && $account_id = $_POST['account_id'] ) || ( isset ($_GET['account_id']) && $account_id = $_GET['account_id']  ) ) {
				
				$income = getMoneyLog( '', $account_id );
				$outgoing = getMoneyLog( $account_id, '' );
				
				echo '<h3>Расходы</h3>';
				print_account_log ( $outgoing['logs'] );
				echo '<p>Сумма: '.$outgoing['sum'].'</p>';
				echo '<h3>Доходы</h3>';
				print_account_log ( $income['logs'] );	
				echo '<p>Сумма: '.$income['sum'].'</p>';
				
				echo '<p style="clear: both;"></p><p style="clear: both; margin: 40px 0 0 0;"><i>Другой счет</i></p>';
			}
			
			echo '
			<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
			<p style="margin-top: 0">Введите номер банковского счета:<br />			
				<input type="text" name="account_id" id="account_id" />
				<input type="submit" name="'.$action.'" value="Вывести историю">
			</p>
			</form>
			';
			break;
	}
}

/////////////////////////////////////////////////////////////////////////////////////////////////
?>