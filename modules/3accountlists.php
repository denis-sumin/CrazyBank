<?php
// ItIsCrazyBankModule

$module = 'accountlists';

if ( !defined("RequestModule") || ( RequestModule !== 'core' && RequestModule !== 'accountlist' ) ) die;

$modules[$module]['name'] = 'Списки банковских счетов';

$modules[$module]['action'][] = 'show_users_list';
$modules[$module]['menu'][] = 'Cчета пользователей';
$modules[$module]['title'][] = 'Пользователи банка';

$modules[$module]['action'][] = 'show_companies_list';
$modules[$module]['menu'][] = 'Счета компаний';
$modules[$module]['title'][] = 'Зарегистрированные компании';

$modules[$module]['action'][] = 'account_info';
$modules[$module]['menu'][] = 'Информация о счете';
$modules[$module]['title'][] = 'Информация о счете';

//$modules[$module]['groups'][] = 'guest'; // группы, которым разрешено пользоваться модулем
//$modules[$module]['groups'][] = 'company';
$modules[$module]['groups'][] = 'admin';
$modules[$module]['groups'][] = 'bankteller';
$modules[$module]['groups'][] = 'user';

function show_accountlists ( $action ) {
	global $modules, $account, $group, $accountlist;;	
	$module = $modules['accountlists'];

	switch ($action) {
		case $module['action'][0]: // Посмотреть список счетов пользователей
		
			if ( RequestModule == 'core' && check_account_access ('bankteller', $account) )  { 
				$field = array ( 'id'=>'', 'surname'=>'Фамилия', 'name'=>'Имя', 'litgroup'=>'', 'balance'=>'Баланс' ); }
			else $field = array ( 'id'=>'', 'surname'=>'Фамилия', 'name'=>'Имя', 'litgroup'=>'' );
		
			if ( empty ($_GET['sortField']) ) $sortField = 'surname'; else $sortField = $_GET['sortField'];
			if ( empty ($_GET['sortDir']) ) $sortDir = 'ASC'; else $sortDir = $_GET['sortDir'];
			if ( empty ($_GET['filterSurname']) ) $filterSurname = ''; else $filterSurname = $_GET['filterSurname'];
			if ( empty ($_GET['filterGroup']) ) $filterGroup = ''; else $filterGroup = $_GET['filterGroup'];
			
			$users = formAccountArray ( 'user', $sortField, $sortDir, $filterSurname, $filterGroup );			
// Начало фильтров			
			$alphabet = array
				('А','Б','В','Г','Д','Е','Ж','З','И','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Э','Ю','Я');
				
			echo "<script>
				function hidefilters() {
					window.document.getElementById('filters').style.display = 'none';
					window.document.getElementById('hfilters').style.display = 'block';
				}

				function showfilters() {
					window.document.getElementById('filters').style.display = 'block';
					window.document.getElementById('hfilters').style.display = 'none';
				}
			</script>";
				
			echo '<div id="hfilters"><a href="javascript:showfilters()">Фильтры</a> 
			<a href="'.$_SERVER["PHP_SELF"].'?action='.$action.'" style="font-size: 75%;">Сбросить</a><span style="font-size: 75%;"> все фильтры</span></div>
			<div id="filters">
			<a href="javascript:hidefilters()">Фильтры</a> 
			<a href="'.$_SERVER["PHP_SELF"].'?action='.$action.'" style="font-size: 75%;">Сбросить</a><span style="font-size: 75%;"> все фильтры</span>		
			<div>
			<p>Фамилия:<br />';
				
			for ($i=0; $i<count($alphabet); $i++)
			echo '
				<a href="'.$_SERVER["PHP_SELF"].'?action='.$action.'&sortField='.$sortField.'
				&sortDir='.$sortDir.'&filterSurname='.$alphabet[$i].'&filterGroup='.$filterGroup.'">'.$alphabet[$i].'</a> ';
			echo '
			</p><p>
			Группа:<br />';
			
			for ($i=0; $i<count($group); $i++) {
			echo '
				<a href="'.$_SERVER["PHP_SELF"].'?action='.$action.'&sortField='.$sortField.'
				&sortDir='.$sortDir.'&filterSurname='.$filterSurname.'&filterGroup='.$group[$i].'">'.$group[$i].'</a> ';
				
			if ( !empty ($group[$i+1]) && ( ( $group[$i][0] != '1' && $group[$i][0] != $group[$i+1][0] ) || ( $group[$i][1] != $group[$i+1][1] ) ) ) echo '<br />';
			}

			echo '
			</p>
			</div>
			</div>';
// Конец фильтров
// Начало вывода таблицы
			echo '			
			<table id="accounttable">
				<tr>';
			if ( RequestModule == 'accountlist' ) echo '
					<th></th>
				';
				
			foreach ($field as $f=>$text) {
				echo '
					<th>'.$text.' 
					<a title="По возрастанию" href="'.$_SERVER["PHP_SELF"].'?action='.$action.'&sortField='.$f.'&sortDir=ASC&filterSurname='.$filterSurname.'&filterGroup='.$filterGroup.'" class="sort-arrow">&uarr;</a><a title="По убыванию" href="'.$_SERVER["PHP_SELF"].'?action='.$action.'&sortField='.$f.'&sortDir=DESC&filterSurname='.$filterSurname.'&filterGroup='.$filterGroup.'" class="sort-arrow">&darr;</a></th>
			';
			}
			echo '</tr>';
			
			if ( count ($users) == 0 ) echo '<tr><td colspan="'.count ($field).'">Ничего не найдено</td></tr>';
			foreach ($users as $key=>$user) {
				echo '<tr style="cursor:pointer" id="'.$user['id'].'" onmouseover="highlight(\''.$user['id'].'\')" onmouseout="nohighlight(\''.$user['id'].'\')">';
				if ( RequestModule == 'accountlist' ) echo '
					<td align="center"><a href="javascript:parent.account(\''.$user['id'].'\')"><</a></td>
				';
				
				foreach ($field as $f=>$text) {
					if ( $f=='litgroup' || $f=='balance' ) $align='right'; elseif ( $f=='id' ) $align='center'; else $align='left';
					
					if ($f == 'litgroup' && $user[$f] == 'Преподаватель') echo '<td align="'.$align.'">П</td>';
					 elseif ($f == 'litgroup' && $user[$f] == 'Выпускник') echo '<td align="'.$align.'">В</td>';					
					  else {
						echo '<td align="'.$align.'" onclick="viewInfo(\''.$user['id'].'\')">';
						echo $user[$f];
						echo '</td>'."\n";
					}
				}
				echo '</tr>';
			}
			echo '</table>';
// Конец вывода таблицы
			break;
			
		case $module['action'][1]: // Посмотреть список счетов компаний
		
			if ( RequestModule == 'core' && check_account_access ('bankteller', $account) )  { 
				$field = array ( 'id'=>'', 'oname'=>'Название компании', 'balance'=>'Баланс' ); }
			else $field = array ( 'id'=>'', 'oname'=>'Название компании' );
		
			if ( empty ($_GET['sortField']) ) $sortField = 'id';
				else $sortField = $_GET['sortField'];
			if ( empty ($_GET['sortDir']) ) $sortDir = 'ASC';
				else $sortDir = $_GET['sortDir'];
			$users = formAccountArray ( 'company', $sortField, $sortDir );
			
			echo '<table id="accounttable">
				<tr>';
			
			if ( RequestModule == 'accountlist' ) echo '
					<th></th>
				';
				
			foreach ($field as $f=>$text) {
			echo '
					<th>'.$text.' 
					<a title="По возрастанию" href="'.$_SERVER["PHP_SELF"].'?action='.$action.'&sortField='.$f.'&sortDir=ASC" class="sort-arrow">&uarr;</a><a title="По убыванию" href="'.$_SERVER["PHP_SELF"].'?action='.$action.'&sortField='.$f.'&sortDir=DESC" class="sort-arrow">&darr;</a></th>
			';
			}
			echo '</tr>';
			foreach ($users as $key=>$user) {
				echo '<tr style="cursor:pointer" id="'.$user['id'].'" onmouseover="highlight(\''.$user['id'].'\')" onmouseout="nohighlight(\''.$user['id'].'\')">';
				
				if ( RequestModule == 'accountlist' ) echo '
					<td align="center"><a href="javascript:parent.account(\''.$user['id'].'\')"><</a></td>
				';
				
				foreach ($field as $f=>$text) {
					if ( $f=='balance' ) $align='right'; elseif ( $f=='id' ) $align='center'; else $align='left';
					echo '<td align="'.$align.'" onclick="viewInfo(\''.$user['id'].'\')">';
					echo $user[$f];
					echo '</td>'."\n";
				}
				echo '</tr>';
			}
			echo '</table>';
			
			break;
			
			
		// Информация о счета
		case 'account_info':
			$accountlist = TRUE;
			
			if ( ( isset ($_POST['account_id']) && $account_id = $_POST['account_id'] ) || ( isset ($_GET['account_id']) && $account_id = $_GET['account_id']  ) ) {
				if ( isset ($_GET['fromlist']) ) echo '<p><a href="javascript:history.back()">Вернуться к таблице</a></p>';
				echo '<p>';
				print_account_info ( $account_id ) ;
				echo '</p>';
				echo '<p style="clear: both;"></p><p style="clear: both; margin: 40px 0 0 0;"><i>Другой счет</i></p>';
			}
			
			echo '
			<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
			<p style="margin-top: 0">Введите номер банковского счета:<br />			
				<input type="text" name="account_id" id="account_id" />
				<input type="submit" name="'.$action.'" value="Вывести информацию">
			</p>
			</form>
			';
			break;
	}
}
?>
