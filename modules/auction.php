<?php
// ItIsCrazyBankModule

if( $g_config['auction_module_enabled'] )
{
	$module = 'auction';

	if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

	$modules[$module]['name'] = 'Проведение аукциона'; // человеческое название модуля

	$modules[$module]['action'][] = 'print_cheque'; // работает полностью
	$modules[$module]['menu'][] = 'Печать чеков';
	$modules[$module]['title'][] = 'Печать чеков';

	$modules[$module]['groups'][] = 'admin';

	function show_auction ($action) {
		global $modules, $account, $accountlist;	
		$module = $modules['register'];
		
		switch ($action) {
			
			case 'print_cheque':		
				print_cheque();
				break;
		}
	}
}

/////////////////////////////////////////////////////////////////////////////////////////////////
?>
