<?php
// ItIsCrazyBankModule

$module = 'company';

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

$modules[$module]['name'] = 'Операции компании';

$modules[$module]['action'][] = 'company_user_pay';
$modules[$module]['menu'][] = 'Оплатить услуги компании';
$modules[$module]['title'][] = 'Оплатить счет';

$modules[$module]['action'][] = 'company_pay_salary';
$modules[$module]['menu'][] = 'Заплатить сотрудникам';
$modules[$module]['title'][] = 'Зарплата сотрудникам';

$modules[$module]['groups'][] = 'company'; // группы, которым разрешено пользоваться модулем

function show_company ( $action ) {
	global $modules, $account, $accountlist;	
	$module = $modules['company'];
	
	$currency = getCurrencyList();
	$rates = getRates();

	switch ($action) {
		case 'company_user_pay':
			$accountlist = true;
			if ( isset ($_POST['company_user_pay']) )	{
				if ( check_password($_POST['account_id'],$_POST['pin']) ) {
					if (transmit($_POST['account_id'],$account['id'],$_POST['cash'],$_POST['currency'],$_POST['comment'])) 
					echo '<p style="margin-bottom: 40px;"><i>Перевод прошел успешно!</i></p>';
				}				
			}
			elseif ( isset ($_POST['confirm']) ) {
				if ( $_POST['cash'] <= 0  ) {
					echo 'Вы хотите перевести неположительную сумму. Зачем..?<br /><i><a href="javascript:history.back()">Назад</a></i>'; die; }
				if ( $_POST['comment'] == ''  ) {
					echo '<span style="color: red">Вы не указали комментарий к переводу.</span><br />В случае каких-либо проблем никто не сможет узнать, что это за перевод<br /><i><a href="javascript:history.back()">Назад</a></i>'; }
				if ( ( isset ($account['id']) && $account['id'] == $_POST['account_id'] ) ) {
					echo 'Вы хотите перевести деньги себе. Зачем..?<br /><i><a href="javascript:history.back()">Назад</a></i>'; die; }
				
				if ( !accountIsActive ( $_POST['account_id'] ) ) {
					report_error ('Счет совершающего перевод не существует или заблокирован. Не удалось совершить перевод'); die; }
				
				echo '<p>Вы собираетесь перевести '.$_POST['cash'].'&nbsp;'.$currency[$_POST['currency']].' на счет '.$account['id'].'</p>
				<p>Информация о счете:</p>
				<p>';
				print_account_info ($account['id']);
				echo '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
				<p>Подтвердите, пожалуйста, перевод вводом своего ПИН-кода:				
				<br /><input type="password" name="pin" />
				<input type="hidden" value="'.$_POST['account_id'].'" name="account_id" />
				';
				echo '
				<input type="hidden" value="'.$_POST['cash'].'" name="cash" />
				<input type="hidden" value="'.$_POST['currency'].'" name="currency" />
				<input type="hidden" value="'.$_POST['comment'].'" name="comment" maxlength="128" />
				<br /><input type="submit" name="'.'company_user_pay'.'" value="Подтвердить" />
				</p>
				</form>
				';
			}
			if (isset ($_POST['company_user_pay']) ) echo '<p>Совершить еще один перевод:</p>';
			if (!isset ($_POST['confirm']) ) {
				echo '
				<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">';
				echo '
				<p>
				<table class="form"><tr><td>Счет плательщика:</td><td><input type="text" name="account_id" id="account_id" /></td></tr>			
				<tr><td>Какую сумму перевести:</td><td><input type="text" name="cash" size="9" />
				<select name="currency">';
				foreach ($currency as $bankname=>$name) {
					if (@$account['currency']==$bankname) $sel='selected'; else $sel='';
					echo '<option value="'.$bankname.'" '.$sel.'>'.$name.'</option>';
				}
				echo '
				</select></td></tr>	
				<tr style="vertical-align: top;"><td>Комментарий к переводу:</td><td><input type="text" name="comment" maxlength="128" /><br />
					<span class="comment">Например, пирожное «Розочка»</a></td></tr>	
				<tr><td></td><td><input type="submit" name="confirm" value="Перевести" /></td></tr></table>
				</p>
				</form>
				';
			}
			break;
			
		case 'company_pay_salary':
			if (empty($_POST)) {
				echo '<p>';
				echo 'У Вас на счету <big>'.number_format ($account['balance'], 0, ',','').'&nbsp;'.$currency[$account['currency']].'</big>';
				foreach ($currency as $cbankname=>$cname) {
					echo '<br />';
					if ($cbankname !== $account['currency']) 
					echo 'В пересчете это примерно <big>'.number_format ($account['balance']*$rates[$account['currency']]/$rates[$cbankname], 0).'&nbsp;'.$cname.'</big>'; }
				echo '</p>';
				//echo '<p>Иначе у Вас на счету примерно <big>'.number_format ($account['balance']*$rates[$account['currency']], 0).'&nbsp;лит</big></p>';
			
				echo '<br /><p>Если Вы руководитель компании, то есть у Вас самый большой процент участия, то Вы можете выдать всем сотрудниками компании зарплату.</p>
				<p>Для этого введите, какую сумму из общего баланса счета вы выплачиваете и введите свой ПИН-код</p>
				<p>Средства между сотрудниками будут распределены в соответствии с указанными при регистрации долями участия</p>
				<p><form method="post">
				<table class="form"><tr><td>Сумма зарплат</td><td><input type="text" name="cash" value="'.number_format ($account['balance'], 0, ',','').'" />&nbsp;('.$currency[$account['currency']].')</td></tr>
				<tr><td>ПИН-код</td><td><input type="password" name="pin" maxlength="6" /></td></tr>
				<tr><td></td><td><input type="submit" name="pay" value="Выплатить" /></td></tr></table></form></p>';
			}
			else {
				if (pay_salary ($account['id'], $_POST['cash'], $_POST['pin'])) echo '<p><i>Зарплаты выплачены успешно</i></p>';
			}
			break;
			
	}
}
?>
