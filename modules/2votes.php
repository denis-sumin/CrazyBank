<?php
// ItIsCrazyBankModule

$module = 'votes';

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

$modules[$module]['name'] = 'Голосования'; // человеческое название модуля

$modules[$module]['action'][] = 'votes_view';
$modules[$module]['menu'][] = 'Доступные голосования';
$modules[$module]['title'][] = 'Голосования';

$modules[$module]['groups'][] = 'admin';

function show_votes ($action) {
	global $modules, $account, $accountlist, $group;
	$module = $modules['admin'];

	switch ($action) {
		case 'votes_view':
			if ( isset($_GET['vote_id']) ) {
				if ( isset ($_POST['send_vote']) ) {
					if ($_GET['vote_id']!=$_POST['vote_id']) report_error('Пахнет обманом. Никогда так не делайте больше');
					if ( send_vote ( $account, $_POST['vote_id'], $_POST['choice'] ) ) {
						echo '<p>Ваш голос учтен. Спасибо ;-)</p>';
					}
				}
				if ( $vote_results = get_vote_results ( $_GET['vote_id'], $account ) ) {
					$vote_data		= $vote_results['vote_data'];
					$vote_choices	= $vote_results['vote_choices'];

					echo '
					<h2>'.$vote_data['vote_topic'].': результаты</h2>
					<table>
						<tr>
							<th>Вариант ответа</th>
							<th>Голосов</th>
							<th>Процентов</th>
						</tr>
					';
					foreach ($vote_data['vote_variants'] as $variant_id => $text) {
						echo '
						<tr>
							<td>'.$text.'</td>
							<td>'.$vote_choices[$variant_id].'</td>
							<td>'.round( ($vote_choices[$variant_id]/$vote_data['votes_count']*100), 2 ).'</td>
						</tr>';
					}
					echo '</table>';
					echo '<p>Всего проголосовало: '.$vote_data['votes_count'].' человек</p>';
					echo '
						<script type="text/javascript" src="https://www.google.com/jsapi"></script>
						<script type="text/javascript">
						  google.load("visualization", "1", {packages:["corechart"]});
						  google.setOnLoadCallback(drawChart);
						  function drawChart() {
							var data = new google.visualization.DataTable();
					';
					foreach ($vote_data['vote_variants'] as $variant_id => $text)
						echo 'data.addColumn("number", "'.$text.'");';
					echo '
							data.addRows([
								[
					';
					foreach ($vote_data['vote_variants'] as $variant_id => $text)
						echo $vote_choices[$variant_id].', ';
					echo '
								]
							]);

							var options = {
							  width: 640, height: 360,
							  title: "Результаты голосования",
							  vAxis: { minValue: 0 }
							};

							var chart = new google.visualization.ColumnChart(document.getElementById("chart_div"));
							chart.draw(data, options);
						  }
						</script>
						<div id="chart_div"></div>
					';
				}
				else {
					$vote_data = get_vote_data ($_GET['vote_id']);
					echo '
					<form method="post" action="'.$_SERVER["REQUEST_URI"].'">
						<h2>'.$vote_data['vote_topic'].'</h2>
						<p>
							Варианты ответа:<br />
					';
					foreach ($vote_data['vote_variants'] as $variant_id => $text) {
						echo '
							<input type="radio" name="choice" value="'.$variant_id.'" />&nbsp;'.$text.'<br />
						';
					}
					echo '
						</p>
						<p>
						<input type="hidden" name="vote_id" value="'.$_GET['vote_id'].'" />
						<input type="submit" name="send_vote" value="Отправить голос" /> <input type="reset" value="Сбросить" /></p>
					</form>
					';
				}
			}
			else {
				$active_votes = get_votes_list ($account['state'], 1);
				$not_active_votes = get_votes_list ($account['state'], 0);

				if (!empty($active_votes)) {
					echo '<p>Активные голосования:</p><ul>';
					foreach ( $active_votes as $votes_item )
						echo '<li><a href="'.$_SERVER["REQUEST_URI"].'&vote_id='.$votes_item['id'].'">'.$votes_item['topic'].'</a></li>';
					echo '</ul>';
				}
				else echo '<p>В данный момент не проводится ни одно голосование</p>';
				if (!empty($not_active_votes)) {
					echo '<p>Неактивные голосования:</p><ul>';
					foreach ( $not_active_votes as $votes_item )
						echo '<li><a href="'.$_SERVER["REQUEST_URI"].'&vote_id='.$votes_item['id'].'">'.$votes_item['topic'].'</a></li>';
					echo '</ul>';
				}
			}
		break;
	}
}
?>
