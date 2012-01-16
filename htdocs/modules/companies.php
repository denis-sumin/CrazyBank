<?php
// ItIsCrazyBankModule

$module = 'manage_companies';

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

$modules[$module]['name'] = 'Управление компаниями'; // человеческое название модуля

$modules[$module]['action'][] = 'admin_add_company';
$modules[$module]['menu'][] = 'Добавить компанию';
$modules[$module]['title'][] = 'Новая компания';

$modules[$module]['action'][] = 'admin_edit_usersincompany';
$modules[$module]['menu'][] = 'Изменить список работающих в&nbsp;компании';
$modules[$module]['title'][] = 'Изменение работающих в&nbsp;компании';

$modules[$module]['groups'][] = 'government';
$modules[$module]['groups'][] = 'admin';

function show_manage_companies ($action) {
	global $modules, $account, $accountlist, $group;	
	$module = $modules['manage_companies'];
	
	switch ($action) {
// Добавить компанию			
		case 'admin_add_company':
			if ( isset ($_POST[$action]) ) {
				if ( $new_account = addCompany ( $_POST['name'], $_POST['currency']) )  {
					echo '
					<p>Компания успешно добавлена</p><p>';
					print_account_info ( $new_account );
					echo '</p>';
				}	
				else echo 'Ошибка добавления компании';
			}
	
			if ( isset ($new_account) && !$new_account ) {
				$name = $_POST['name'];
			}
			
			if ( isset ($_POST[$action]) ) echo '<p style="margin-top: 40px;"><i>Еще одна компания:</i></p>';
			echo '
			<p>
			<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
			<table class="userinfo">
				<tr>
					<td>Название</td> 
					<td><input type="text" name="name" value="'.@$name.'" /></td>
				</tr>
				<tr>
					<td>Валюта</td> 
					<td><select name="currency">';
				foreach (getCurrencyList() as $bankname=>$name) {
					if ($acc['currency']==$bankname) $sel='selected'; else $sel='';
					echo '<option value="'.$bankname.'" '.$sel.'>'.$name.'</option>';
				}
				echo '
				</select></td>
				</tr>
				<tr><td>&nbsp;</td> 
					<td> <input type="submit" name="'.$action.'" value="Добавить" /></td>
				</tr>	
			</table>
			</form>
			</p>';
			break;
		case 'admin_add_money':
			admin_add();
			break;
			
			
// Изменение работающих в компании
		case 'admin_edit_usersincompany':
			$accountlist = 2;
			if ( empty ($_POST) ) echo '
			<div>
			Введите номер банковского счета:
			<form method="post">
				<input type="text" name="account_id" id="account_id" />
				<input type="submit" name="'.$action.'" value="Далее">
			</form>
			</div>';
		
			if ( isset ($_POST[$action]) ) {
				if ( accounttype ( $_POST['account_id'] ) != 'company' )
				report_error ('Введенный номер счета не является счетом компании. Попробуйте еще раз, пожалуйста');
			
				echo '
				<p>';
				if ( print_account_info ( $_POST['account_id'] ) ) {
				echo '</p>
				<p>
				<form method="post" name="1">
					<p><table>
					<tr><th>Счет сотрудника</th><th>% участия</th><tr>
				';
				if (isset($_POST['n'])) $n = $_POST['n']; else $n = 4;
				
				$company = get_account_info ($_POST['account_id']);
				if ($n < ($nusers = count (@$company['users']))) $n = $nusers;
				
				for ($i=0; $i<$n; $i++) {
					echo '<tr><td><input type="text" maxlength="3" size="10" value="'.@id2account($company['users'][$i]).'" name="user_id'.$i.'" id="user_id'.$i.'" onclick="setfocus(\'user_id'.$i.'\')" /></td><td><input type="text" name="percent'.$i.'" value="'.@$company['user_percent'][$i].'" size="3" maxlength="3" /></td></tr>';
				}
				echo '
					</table>
					<p><input type="checkbox" name="autopercents" value="on" /> Рассчитать проценты автоматически (поровну)</p>
					<input type="hidden" name="account_id" value="'.$_POST['account_id'].'" />
					<input type="hidden" name="n" value="'.$n.'" />
					</p><p style="margin-top: 5px;"><input type="submit" name="confirm" value="Применить изменения" style="font-size: 110%;"></p>
				</form>
				</p>
				<p><form method="post" name="num"><small>Количество строк в таблице:&nbsp;</small><input type="hidden" name="account_id" value="'.$_POST['account_id'].'" /><select name="n">';
				$nn = array (2,4,6,8,10,20,40,80,160);
				foreach ($nn as $num) { if ($num==$n) $sel='selected'; else $sel=''; echo '<option value="'.$num.'" '.$sel.'>'.$num.'</option>'; }
				echo '</select><input type="submit" name="'.$action.'" value="изменить" /></form></p>';
				}	
			}
	
			if ( isset ($_POST['confirm']) ) {
				$n = $_POST['n'];
				$companyusers = Array();
				$k=0;
				
				for ($i=0; $i<$n; $i++) {
					if ($_POST['user_id'.$i]=='') continue;
					if ( $_POST['percent'.$i] !== '' ) $calculatePercents = false;
					if (!isset($companyusers[$_POST['user_id'.$i]])) {
						$companyusers[$_POST['user_id'.$i]] = $_POST['percent'.$i];
						$k++;
					}
				}
				
				if ( isset ($_POST['autopercents']) && $_POST['autopercents']=='on' ) {
					$percent = round (100 / $k);
					foreach ( $companyusers as $user_id_=>$percent_ ) {
						$companyusers[$user_id_]=$percent;
						$sum+=$percent;		
					}
					$sum-=$percent;
					$companyusers[$user_id_] = 100 - $sum;
				}
				
				$sum = 0;
				foreach ( $companyusers as $user_id_=>$percent_ ) $sum+=$percent_;
				if ( $sum != 100 ) report_error ("Сумма процентов участия сотрудников не равна 100 %. Из-за этого возникнут проблемы при начислении зарплат. Операция прервана.");
				updateCompanyUsers ( $_POST['account_id'], $companyusers );
				
				echo '<p><i>Изменения успешно сохранены</i></p>';
				echo '
				<script language="javascript">
				setTimeout("location.href=\'\'", 1000);
				</script>
				';
			}
			break;
	}
}

/////////////////////////////////////////////////////////////////////////////////////////////////
?>
