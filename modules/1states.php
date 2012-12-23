<?php
// ItIsCrazyBankModule

$module = 'states';

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

$modules[$module]['name'] = 'Информация о партиях';

$modules[$module]['action'][] = 'government_lists';
$modules[$module]['menu'][] = 'Списки партий';
$modules[$module]['title'][] = 'Списки партий';

$modules[$module]['action'][] = 'government_reports';
$modules[$module]['menu'][] = 'Отчеты партии';
$modules[$module]['title'][] = 'Отчеты партии';

$modules[$module]['action'][] = 'government_charts';
$modules[$module]['menu'][] = 'Успехи на графике';
$modules[$module]['title'][] = 'Успехи партии на графике';

$modules[$module]['groups'][] = 'user'; // группы, которым разрешено пользоваться модулем

function show_states( $action ) {
	global $modules, $account, $accountlist;	
	$module = $modules['rates'];

	switch ($action) {
		case 'government_lists':
			
			$states = getStatesList();
			$state_accounts = getStatesAccounts();

			foreach ($states as $key=>$value) {
				echo '<h2>'.$states[$key].'</h2>';
				echo '<p>';
				print_account_info ( $state_accounts[$key] ) ;
				echo '</p>';
			}
			
			break;
		case 'government_reports':
		
      if($account['state'] == 'Edro')
      {
        echo 'Вы не состоите в партии!';
      }      
      else
      {
			 $states = getStatesList();
			 $state_accounts = getStatesAccounts();
			
			 echo '<h2>'.$states[$account['state']].'</h2>';
			
			 $income = getMoneyLog( '', $state_accounts[$account['state']] );
			 $outgoing = getMoneyLog( $state_accounts[$account['state']], '' );
			
			 echo '<h3>Траты партии</h3>';
			 print_account_log ( $outgoing['logs'], 'state_report' );
			 echo '<p>Сумма: '.$outgoing['sum'].'</p>';
			 echo '<h3>Доходы партии</h3>';
			 print_account_log ( $income['logs'], 'state_report' );	
			 echo '<p>Сумма: '.$income['sum'].'</p>';	
      } 	
		  break;
		
		case 'government_charts':
			
			$states = getStatesList();
			$state_accounts = getStatesAccounts();
			
			foreach ($states as $key=>$value) {
				$ar[$key] = getMoneyLog( $state_accounts[$key], $state_accounts[$key] );
				$log[$key] = $ar[$key]['logs'];
				$money[$key] = 0;
			}
			
			echo '
			<script type="text/javascript" src="http://www.google.com/jsapi"></script>
			<script type="text/javascript">
			  google.load("visualization", "1", {"packages":["annotatedtimeline"]});
			  google.setOnLoadCallback(drawChart);
			  function drawChart() {
				var data = new google.visualization.DataTable();
				data.addColumn("date", "Date");
				data.addColumn("number", "Leftwing");
				data.addColumn("number", "Rightwing");
				data.addRows([
					[new Date(2011, 12-1 ,21), 0, 0],
			';
			foreach ($states as $key=>$value) {
				foreach ($log[$key] as $row) {
					$row_timestamp = strtotime($row['timestamp']);
					if ( isset ($row_timestamp_prev) && ($row_timestamp-$row_timestamp_prev)>3600)
						echo '[new Date('.date("Y, m-1, d, H, i, s", ($row_timestamp-3600)).'), '.$col['leftwing'].', '.$col['rightwing'].']'.",\n";
					$money[$key] += $row['money'];
					foreach ($states as $key1=>$value1) $col[$key1]='undefined';
					$col[$key]=$money[$key];
					echo '[new Date('.date("Y, m-1, d, H, i, s", $row_timestamp).'), '.$col['leftwing'].', '.$col['rightwing'].']'.",\n";
					$row_timestamp_prev = $row_timestamp;
				} 
			}
			echo '
					[new Date('.date("Y, m-1, d, H, i, s").'), '.$money['leftwing'].', '.$money['rightwing'].']
				]);

				var chart = new google.visualization.AnnotatedTimeLine(document.getElementById("chart_div"));
				chart.draw(data, {displayAnnotations: true});
			  }
			</script>
			';
			
			echo '<div id="chart_div" style="width: 700px; height: 360px;"></div>';
			
			break;
	}

}
?>
