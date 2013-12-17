<?php
// ItIsCrazyBankModule

$module = 'register';

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

$modules[$module]['name'] = 'Регистрация пользователей'; // человеческое название модуля

$modules[$module]['action'][] = 'mass-add-students'; // работает полностью
$modules[$module]['menu'][] = 'Массовое добавление лицеистов';
$modules[$module]['title'][] = 'Массовое добавление лицеистов';

$modules[$module]['action'][] = 'activate'; // работает полностью
$modules[$module]['menu'][] = 'Активация банковского счета';
$modules[$module]['title'][] = 'Активация банковского счета';

$modules[$module]['action'][] = 'mass-activate';
$modules[$module]['menu'][] = 'Массовая активация';
$modules[$module]['title'][] = 'Массовая активация';

if( $g_config['printing_PINs_enabled'] )
{
	$modules[$module]['action'][] = 'mass_print_pins'; // работает полностью
	$modules[$module]['menu'][] = 'Массовое изменение ПИНов и&nbsp;вывод&nbsp;для&nbsp;печати';	
}

$modules[$module]['action'][] = 'import_people_lit_msu_ru';
$modules[$module]['menu'][] = 'Импорт лицеистов и преподавателей';

$modules[$module]['groups'][] = 'registrar';

function show_register ($action) {
	global $modules, $account, $accountlist, $group;	
	$module = $modules['register'];
	
	switch ($action) {
		case 'mass-add-students':
			if ( empty ( $_POST ) ) {
				
				echo '
				<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
					Выберите, пожалуйста, группу, которую Вы будете добавлять: 
					<select name="group" size="1">
				';
				
				foreach ($group as $key=>$value) echo '<option value="'.$value.'">'.$value.'</option>';
					
				echo '
					</select>
					<input type="submit" name="stage1" value="Дальше" />
				</form>
				';
			}
			elseif ( isset ($_POST['stage1']) ) {
				echo '
				<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
				';
				
				for ($i=0; $i<30; $i++) {
					echo '
					<p>'.($i+1).'<br />
					<table>
						<tr><td>Имя</td><td><input type="text" maxlength="255" name="name'.$i.'" /></td></tr>
						<tr><td>Фамилия</td><td><input type="text" maxlength="255" name="surname'.$i.'" /></td></tr>
						<tr><td>URL фото</td><td><input type="text" maxlength="255" size="40" name="photo_url'.$i.'" /></td></tr>
					</table>
					</p>
					';
				}
				
				echo '	
					<input type="hidden" name="group" value="'.$_POST['group'].'" />
					<input type="submit" name="stage2" value="Добавить группу '.$_POST['group'].'" />
				</form>
				';
			}
			elseif ( isset ($_POST['stage2']) ) {
				for ($i=0; $i<30; $i++) {
					if ( $_POST['name'.$i]=="" && $_POST['surname'.$i]=="" && $_POST['photo_url'.$i]=="" ) continue;
					if ( addUser ( $_POST['name'.$i], $_POST['surname'.$i], $_POST['group'], $_POST['photo_url'.$i], 'null', '0', array(), 'mass' ) )
					echo $_POST['name'.$i].' '.$_POST['surname'.$i].' успешно добавлен<br />';
				}
				echo '<p><a href="'.$_SERVER["PHP_SELF"].'?action='.$action.'">Добавить еще</a></p>';
			}
			break;
		case 'activate':
			$accountlist = TRUE;
		
			if ( isset ($_POST['confirm']) ) {
				if ( activate ( $_POST['account_id'] ) ) {
				echo 'Счет активирован';
				}
			}
				
			if ( isset ($_POST[$action]) ) {
				echo '
				<div>
				Вы уверены, что хотите активировать банковский счет?';
				if ( print_account_info ( $_POST['account_id'] ) ) {
				echo '
					<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
						<input type="hidden" name="account_id" value="'.$_POST['account_id'].'">
						<input type="submit" name="confirm" value="Подтвердить">
					</form>';
				}
				echo '</div>';
			}
			if ( !empty ($_POST) ) echo '<p style="margin: 40px 0 0 0;"><i>Активировать другой счет</i></p>';
			echo '<div>
			Введите номер банковского счета:
			<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
				<input type="text" name="account_id" id="account_id" />
				<input type="submit" name="'.$action.'" value="Активировать">
			</form>
			</div>';
			break;
		case 'mass-activate':
			
			if ( empty ( $_POST ) ) {
				
				echo '
				<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
					Выберите, пожалуйста, группу, которую Вы будете активировать: 
					<select name="group" size="1">
				';
				
				foreach ($group as $key=>$value) echo '<option value="'.$value.'">'.$value.'</option>';
					
				echo '
					</select>
					<input type="submit" name="stage1" value="Дальше" />
				</form>
				';
			}
			elseif ( isset ($_POST['stage1']) ) {
			
				$users = formAccountArray ( 'user', 'surname', 'ASC', '', $_POST['group'] );
				$field = array ( 'checkbox'=>'', 'id'=>'', 'surname'=>'Фамилия', 'name'=>'Имя', 'litgroup'=>'' );
			
				echo '
				<form method="POST" id="activation" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
				<p>';
				
				echo '<table id="accounttable">';
				
				foreach ($users as $key=>$user) {
					if ( $user['blocked'] != 1 ) continue;
					
					echo '<tr>';
					foreach ($field as $f=>$text) {
						if ( $f=='balance' ) $align='right'; elseif ( $f=='id' || $f == 'checkbox' ) $align='center'; else $align='left';
						echo '<td align="'.$align.'">';
						if ($f == 'id') echo '<a href="?action=account_info&account_id='.$user[$f].'&fromlist">'.$user[$f].'</a>';
						else if ( $f == 'checkbox' ) {
							if ( $user['blocked'] == 0 ) $checked = 'checked';
							 else $checked = '';
							echo '<input type="checkbox" value="'.$user['id'].'" name="activate[]" '.$checked.' />';
						}
						else echo $user[$f];
						echo '</td>'."\n";
					}
					echo '</tr>';
				}
				
				echo '</table>
					<input type="hidden" name="group" value="'.$_POST['group'].'" />
					<input type="button" value="Инвертировать чекбоксы" onclick="InvertCheckboxes(\'activation\')" /></p>
					<p><input type="submit" name="stage2" value="Активировать группу '.$_POST['group'].'" /></p>
				</form>
				';
			}
			elseif ( isset ($_POST['stage2']) ) {
				if ( isset ($_POST['activate']) ) {
					foreach ($_POST['activate'] as $value) {
						if ( activate ( $value ) ) echo 'Счет '.$value.' активирован<br />';
					}
				}
				echo '<p><a href="'.$_SERVER["PHP_SELF"].'?action='.$action.'">Активировать еще</a></p>';
			}

			break;
		case 'mass_print_pins':
		
			if ( isset ($_POST['confirm']) ) {
				define ("register", 'mass_print_pins');
				mass_print_pins();
				
			}
			
			if ( empty ($_POST) ) {
				echo '
				<div>
				<span style="color:red">Внимание!</span><br />
				Эта операция изменит и выведет для печати ПИНы всех заблокированных банковских счетов.<br />
				Продолжайте, только если Вы полностью понимаете последствия этого действия.
				<form method="post">
					<input type="submit" name="confirm" value="Подтвердить">
				</form>			
				</div>';
			}
			break;
		
		case 'import_people_lit_msu_ru':
		
			if ( isset ($_POST['confirm']) ) {
				define ("register", 'import_people_lit_msu_ru');
				import_people_lit_msu_ru();			
			}
			
			if ( empty ($_POST) ) {
				echo '
				<div>
				<span style="color:red">Внимание!</span><br />
				Это действие приведет к добавлению из базы лицеистов people.lit.msu.ru всех преподавателей и нынешних лицеистов
				<form method="post">
					<input type="submit" name="confirm" value="Продолжить">
				</form>			
				</div>';
			}
			break;
	}
}

/////////////////////////////////////////////////////////////////////////////////////////////////
?>
