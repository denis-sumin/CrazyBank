<?php
// ItIsCrazyBankModule

$module = 'votes_admin';

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

$modules[$module]['name'] = 'Управление голосованиями'; // человеческое название модуля

$modules[$module]['action'][] = 'vote_add';
$modules[$module]['menu'][] = 'Добавить голосование';
$modules[$module]['title'][] = 'Новое голосование';

$modules[$module]['action'][] = 'vote_edit';
$modules[$module]['menu'][] = 'Изменить голосование';
$modules[$module]['title'][] = 'Редактирование голосований';

$modules[$module]['groups'][] = 'admin';

function show_votes_admin ($action) {
	global $modules, $account, $accountlist, $group;	
	$module = $modules['admin'];
	
	switch ($action) {
		case 'vote_add':			
			if ( isset ($_POST['vote_add']) ) {
				if ( isset($_POST['active']) && $_POST['active'] == '1' ) $active_flag = 1;
				else $active_flag = 0;
				foreach ( $_POST['variant'] as $value ) if ($value !== '') $variant[] = $value;
				
				echo '<p>';
				if ( add_vote ( $_POST['vote_topic'], $variant, $_POST['state'], $active_flag ) )
					echo 'Голосование успешно добавлено';
				else 'Голосование добавлено не было, произошла ошибка';
				echo '</p><p><a href="'.$_SERVER["REQUEST_URI"].'">Добавить еще одно голосование</a></p>';
			}
			else {
				echo '
				<form method="post" action="'.$_SERVER["REQUEST_URI"].'">
					<p>Тема голосования: <input type="text" name="vote_topic" maxlength="256" size="50" /></p>
					<p>
						Варианты ответа:<br />
				';
				for ($i=0; $i<10; $i++) {
					echo '
						<input type="text" name="variant['.$i.']" placeholder="вариант '.($i+1).'" size="40" maxlength="256" />
					';
				}
				echo '
					</p>
					<p>Голосование активно <input type="checkbox" name="active" value="1" /></p>
					<p>Голосование доступно:
						<select name="state" size="1" style="width: 200px;">
							<option value="">всем</option>
					';				
					foreach (getStatesList() as $key=>$value) {
						echo '<option value="'.$key.'">'.$value.'</option>';
					}				
					echo '
						</select>
					</p>
					<p><input type="submit" name="vote_add" value="Добавить голосование" /> <input type="reset" value="Отменить заполнение" /></p>
				</form>
				';
			}
			break;
		
		case 'vote_edit':
			if ( isset($_GET['vote_id']) ) {				
				if ( isset ($_POST['vote_save']) ) {
					if ($_GET['vote_id']!==$_POST['vote_id']) report_error('Пахнет обманом. Никогда так не делайте больше');
					
					if ( isset($_POST['active']) && $_POST['active'] == '1' ) $active_flag = 1;
					else $active_flag = 0;
					foreach ( $_POST['variant'] as $value ) if ($value !== '') $variant[] = $value;
					
					echo '<p>';
					if ( save_vote ( $_POST['vote_id'], $_POST['vote_topic'], $variant, $_POST['state'], $active_flag ) )
						echo 'Голосование успешно сохранено';
					else 'Голосование сохранено не было, произошла ошибка';
					echo '</p>';
				}
				else {
					$vote_data = get_vote_data ($_GET['vote_id']);
					echo '
					<form method="post" action="'.$_SERVER["REQUEST_URI"].'">
						<p>Тема голосования: <input type="text" name="vote_topic" value="'.$vote_data['vote_topic'].'" maxlength="256" size="50" ';
					if ($vote_data['votes_count']>0) echo 'readonly="readonly"';
					echo '/></p>
						<p>
							Варианты ответа:<br />
					';
					for ($i=0; $i<10; $i++) {
						echo '
							<input type="text" name="variant['.$i.']" value="'.$vote_data['vote_variants'][$i].'" placeholder="вариант '.($i+1).'" size="40" maxlength="256" ';
						if ($vote_data['votes_count']>0) echo 'readonly="readonly"';
						echo ' />
						';
					}
					echo '
						</p>
						<p>Голосование активно <input type="checkbox" name="active" value="1" ';
					if ($vote_data['active_flag']) echo 'checked';
					echo ' /></p>
						<p>Голосование доступно:
							<select name="state" size="1" style="width: 200px;">
								<option value="">всем</option>
						';				
						foreach (getStatesList() as $key=>$value) {
							if ($key == $vote_data['state_filter']) $atr = 'selected';
								else $atr = '';
							echo '<option value="'.$key.'" '.$atr.'>'.$value.'</option>';
						}				
						echo '
							</select>
						</p>
						<p>
						<input type="hidden" name="vote_id" value="'.$_GET['vote_id'].'" />
						<input type="submit" name="vote_save" value="Сохранить голосование" /> <input type="reset" value="Отменить заполнение" /></p>
					</form>
					';
				}
			}
			else {
				echo '<p>Активные голосования:</p><ul>';
				foreach ( get_votes_list ('', 1) as $votes_item )
					echo '<li><a href="'.$_SERVER["REQUEST_URI"].'&vote_id='.$votes_item['id'].'">'.$votes_item['topic'].'</a></li>';
				echo '</ul><p>Неактивные голосования:</p><ul>';
				foreach ( get_votes_list ('', 0) as $votes_item )
					echo '<li><a href="'.$_SERVER["REQUEST_URI"].'&vote_id='.$votes_item['id'].'">'.$votes_item['topic'].'</a></li>';
				echo '</ul>';
			}
			break;
	}
}
?>