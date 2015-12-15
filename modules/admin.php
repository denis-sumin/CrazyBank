<?php
// ItIsCrazyBankModule

$module = 'admin';

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

$modules[$module]['name'] = 'Управление банком'; // человеческое название модуля

$modules[$module]['action'][] = 'admin_add_user'; // работает полностью
$modules[$module]['menu'][] = 'Добавить пользователя';
$modules[$module]['title'][] = 'Новый пользователь';

$modules[$module]['action'][] = 'admin_edit_user';
$modules[$module]['menu'][] = 'Редактировать пользователя';
$modules[$module]['title'][] = 'Изменить информацию';

$modules[$module]['action'][] = 'admin_delete_user';
$modules[$module]['menu'][] = 'Удалить пользователя';
$modules[$module]['title'][] = 'Удаление пользователя';

//$modules[$module]['action'][] = 'admin_add_money'; // не готово
//$modules[$module]['menu'][] = 'Зачислить на счёт';

$modules[$module]['action'][] = 'admin_reset_pin'; // работает полностью
$modules[$module]['menu'][] = 'Сбросить ПИН';
$modules[$module]['title'][] = 'Сброс ПИНа';

$modules[$module]['action'][] = 'admin_unblock_account'; // работает полностью
$modules[$module]['menu'][] = 'Разблокировать счет';
$modules[$module]['title'][] = 'Разблокировка счета';

$modules[$module]['action'][] = 'admin_block_account'; // работает полностью
$modules[$module]['menu'][] = 'Заблокировать счет';
$modules[$module]['title'][] = 'Блокировка счета';

$modules[$module]['action'][] = 'admin_end_of_day';
$modules[$module]['menu'][] = 'Завершение игрового дня';
$modules[$module]['title'][] = 'Завершение игрового дня';

$modules[$module]['groups'][] = 'admin';

function show_admin ($action) {
	global $modules, $account, $accountlist, $group;
	$module = $modules['admin'];

	switch ($action) {
// Добавить пользователя
		case 'admin_add_user':
			if ( isset ($_POST[$action]) ) {
				$pin = generatePin();
				if (empty ($_POST['group'])) $sysgroup = Array();
				 else $sysgroup =  $_POST['group'];
				if ( $new_account = addUser ( $_POST['name'], $_POST['surname'], $_POST['litgroup'], $_POST['photo_url'], $pin, $_POST['balance'], $sysgroup, $_POST['currency'] ) )  {
					echo '
					<p>Пользователь успешно добавлен</p>
					<p>';
					print_account_info ( $new_account );
					echo '</p><p>ПИН: '.$pin.'</p>';
				}
				else echo 'Ошибка добавления пользователя';
			}

			if ( isset ($new_account) && !$new_account ) {
				$name = $_POST['name'];
				$surname = $_POST['surname'];
				$litgroup = $_POST['litgroup'];
				$photo_url = $_POST['photo_url'];
			}

			echo '
			<div>
			<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
			<p>
			<table class="userinfo">
				<tr>
					<td>Имя</td>
					<td><input type="text" name="name" value="'.@$name.'" /></td>
				</tr>
				<tr>
					<td>Фамилия  </td>
					<td><input type="text" name="surname" value="'.@$surname.'" /></td>
				</tr>
				<tr><td>Группа</td>
					<td><select name="litgroup" size="1">
				';

				foreach ($group as $key=>$value) {
					if ($value == @$litgroup) $atr = 'selected';
						else $atr = '';
					echo '<option value="'.$value.'" '.$atr.'>'.$value.'</option>';
				}

				echo '
					</select>
				</tr>
				<tr><td>Фотография</td>
					<td><input type="text" name="photo_url" value="'.@$photo_url.'" /></td>
				</tr>
				<tr><td>Начальный баланс счета</td>
					<td><input type="text" name="balance" value="'.start_balance.'" /></td>
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
				<tr><td>Системная<br />группа</td>
					<td><select name="group[]" size="3" multiple>
				';

				foreach ( getGroupsList() as $bankname=>$name) {
					$atr = '';
					foreach ( $editUser['group'] as $value ) {
						if ($value == $bankname) $atr = 'selected';
					}
					echo '<option value="'.$bankname.'" '.$atr.'>'.$name.'</option>';
				}

				echo '</select>
					</td>
				</tr>
				<tr><td>&nbsp;</td>
					<td> <input type="submit" name="'.$action.'" value="Добавить" /></td>
				</tr>
			</table></p>
			</form>
			</div>';
			break;
// Изменить пользователя
		case 'admin_edit_user':
			$accountlist = TRUE;
			if ( isset ($_POST[$action]) ) {
				if (empty ($_POST['group'])) $sysgroup = Array();
				 else $sysgroup =  $_POST['group'];
				if ( editUser ( $_POST['account_id'], $_POST['name'], $_POST['surname'], $_POST['litgroup'], $_POST['photo_url'], $sysgroup, $_POST['state'] ) )  {
					echo '
					<p>Пользователь успешно изменен</p><p>';
					print_account_info ( $_POST['account_id'] );
					echo '</p>';
				}
				else echo 'Ошибка изменения пользователя';
			}

			if ( isset ($_POST['getid']) ) {

				if ( isset ($new_account) && !$new_account ) {
					$name = $_POST['name'];
					$surname = $_POST['surname'];
					$litgroup = $_POST['litgroup'];
					$photo_url = $_POST['photo_url'];
				}

				$editUser = get_account_info ($_POST['account_id']);

				echo '
				<div>
				<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
				<p><img src="'.$editUser['photo_url'].'" align="left" style="border: 1px solid #ccc; display: block; margin: -1px 6px 0 0;" />
				<table class="userinfo">
					<tr>
						<td>Номер счета</td>
						<td>'.$editUser['account_id'].'
						    <input type="hidden" name="account_id" value="'.$editUser['account_id'].'" /></td>
					</tr>
					<tr>
						<td>Имя</td>
						<td><input type="text" name="name" value="'.$editUser['name'].'" /></td>
					</tr>
					<tr>
						<td>Фамилия  </td>
						<td><input type="text" name="surname" value="'.$editUser['surname'].'" /></td>
					</tr>
					<tr><td>Группа</td>
						<td><select name="litgroup" size="1">
					';

					foreach ($group as $key=>$value) {
						if ($value == $editUser['litgroup']) $atr = 'selected';
							else $atr = '';
						echo '<option value="'.$value.'" '.$atr.'>'.$value.'</option>';
					}

					echo '
						</select>
					</tr>
					<tr><td>Фотография</td>
						<td><input type="text" name="photo_url" value="'.$editUser['photo_url'].'" /></td>
					</tr>
					<tr><td>Системная<br />группа</td>
						<td><select name="group[]" size="3" multiple>
					';

					foreach ( getGroupsList() as $bankname=>$name) {
						$atr = '';
						foreach ( $editUser['group'] as $value ) {
							if ($value == $bankname) $atr = 'selected';
						}
						echo '<option value="'.$bankname.'" '.$atr.'>'.$name.'</option>';
					}

					echo '</select>
						</td>
					</tr>';

					$states = getStatesList();
					if (count($states) > 0) {
						echo '<tr><td>Государство</td>
							<td><select name="state" size="1" style="width: 200px;">';

						foreach (getStatesList() as $key=>$value) {
							if ($key == $editUser['state']) $atr = 'selected';
								else $atr = '';
							echo '<option value="'.$key.'" '.$atr.'>'.$value.'</option>';
						}
						echo '</select></tr>';
					} else {
						echo '<input type="hidden" name="state" value="'.$editUser['state'].'" />';
					}
					echo '
					<tr><td>&nbsp;</td>
						<td><input type="submit" name="'.$action.'" value="Изменить" /></td>
					</tr>
				</table></p>
				</form>
				</div>';
			}

			if ( !empty ($_POST) ) echo '<p style="margin: 40px 0 0 0;"><i>Изменить другого пользователя</i></p>';
			$accountlist = TRUE;
			echo '
			<div>
			Введите номер банковского счета:
			<form method="post">
				<input type="text" name="account_id" id="account_id" />
				<input type="submit" name="getid" value="Редактировать">
			</form>
			</div>';
			break;

// Удалить пользователя
		case 'admin_delete_user':
			$accountlist = TRUE;

			if ( empty ($_POST) ) echo '
			<div>
			Введите номер банковского счета:
			<form method="post">
				<input type="text" name="account_id" id="account_id" />
				<input type="submit" name="'.$action.'" value="'.$dif[$action][0].'Удалить">
			</form>
			</div>';

			if ( isset ($_POST[$action]) ) {
				echo '
				<div>
				<p>Вы уверены, что хотите '.$dif[$action][1].'удалить счет?</p><p>';
				if ( print_account_info ( $_POST['account_id'] ) ) {
				echo '</p>
				<form method="post">
					<input type="hidden" name="account_id" value="'.$_POST['account_id'].'">
					<input type="submit" name="confirm" value="Подтвердить">
				</form>
				</div>';
				}
			}

			if ( isset ($_POST['confirm']) ) {
				if ( deleteUser ( $_POST['account_id']) ) {
				echo 'Банковский счет удален';
				}
        else{
				echo 'Произошла ошибка удаления счета';
        }
			}
			break;

// Сброс ПИНа
		case 'admin_reset_pin':
			$accountlist = TRUE;
			if ( empty ($_POST) ) echo '
			<div>
			Введите номер банковского счета:
			<form method="post">
				<input type="text" name="account_id" id="account_id" />
				<input type="submit" name="'.$action.'" value="Запросить новый PIN">
			</form>
			</div>';

			if ( isset ($_POST[$action]) ) {
				echo '
				<div>
				<p>Вы уверены, что хотите изменить PIN?</p><p>';
				if ( print_account_info ( $_POST['account_id'] ) ) {
				echo '</p>
				<form method="post">
					<input type="hidden" name="account_id" value="'.$_POST['account_id'].'">
					<input type="submit" name="confirm" value="Подтвердить">
				</form>
				</div>';
				}
			}

			if ( isset ($_POST['confirm']) ) {
				$pin = generatePin();
				if ( updatePin ( $_POST['account_id'], $pin ) ) {
				echo '<p><big>Новый PIN-код: '.$pin.'</big></p><p><i>Обновление записи в БД произведено успешно<i></p>';
				}
			}
			break;

// (Раз)блокировка
		case 'admin_block_account':
		case 'admin_unblock_account':
			$accountlist = TRUE;

			$dif['admin_block_account'] = array ('За','за',1); // Все, чем различаются блокировка и разблокировка
			$dif['admin_unblock_account'] = array ('Раз','раз',0);

			if ( empty ($_POST) ) echo '
			<div>
			Введите номер банковского счета:
			<form method="post">
				<input type="text" name="account_id" id="account_id" />
				<input type="submit" name="'.$action.'" value="'.$dif[$action][0].'блокировать">
			</form>
			</div>';

			if ( isset ($_POST[$action]) ) {
				echo '
				<div>
				<p>Вы уверены, что хотите '.$dif[$action][1].'блокировать счет?</p><p>';
				if ( print_account_info ( $_POST['account_id'] ) ) {
				echo '</p>
				<form method="post">
					<input type="hidden" name="account_id" value="'.$_POST['account_id'].'">
					<input type="submit" name="confirm" value="Подтвердить">
				</form>
				</div>';
				}
			}

			if ( isset ($_POST['confirm']) ) {
				if ( setBlockFlag ( $_POST['account_id'], $dif[$action][2] ) ) {
				echo 'Банковский счет '.$dif[$action][1].'блокирован';
				}
			}
			break;


// Завершение игрового дня
		case 'admin_end_of_day':
			if ( empty ($_POST) ) echo '
			<p>В конце игрового дня возможно</p>
			<form method="post">
				<p><!--<input type="checkbox" name="reload_rates" />&nbsp; Обновить курсы валют<br />-->
				<input type="checkbox" name="increase_balances" />&nbsp; Увеличить баланс активных счетов на 5 %<br />
				<input type="checkbox" name="increase_state_balances" />&nbsp; Увеличить баланс государств<br />
				<input type="checkbox" name="collect_taxes" />&nbsp; Собрать налоги<br />
				<input type="checkbox" name="distribute_state_balances" />&nbsp; Распределить бюджеты государств по гражданам</p>
				<p><input type="submit" name="'.$action.'" value="Выполнить операции"></p>
			</form>
			';

			if ( isset ($_POST[$action]) ) {
				if (isset($_POST['reload_rates'])) {
					if (reload_rates()) echo '<p>Обновили курсы валют</p>';
				}
				if (isset($_POST['increase_balances'])) {
					if (increase_balances()) echo '<p>Увеличили баланс активных счетов на 5&nbsp;%</p>';
				}
				if (isset($_POST['increase_state_balances'])) {
					if (increase_state_balances()) echo '<p>Увеличили балансы государств</p>';
				}
				if (isset($_POST['collect_taxes'])) {
					if (collect_taxes()) echo '<p>Собрали налоги для государств</p>';
				}
				if (isset($_POST['distribute_state_balances'])) {
					if (distribute_state_balances()) echo '<p>Распределили бюджеты государств по гражданам</p>';
				}
			}
			break;
	}
}

/////////////////////////////////////////////////////////////////////////////////////////////////
?>
