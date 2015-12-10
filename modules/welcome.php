<?php
// ItIsCrazyBankModule

$module = 'welcome';

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

//$modules[$module]['name'] = 'Приветствие системы';
$modules[$module]['action'][0] = 'welcome';
$modules[$module]['title'][0] = 'Добро&nbsp;пожаловать&nbsp;в&nbsp;платежную&nbsp;систему&nbsp;Crazy&nbsp;Банк';

$modules[$module]['groups'][] = 'guest'; // группы, которым разрешено пользоваться модулем
$modules[$module]['groups'][] = 'company';

function show_welcome () {
	global $account;

	if (check_account_access ('admin', $account))
	// echo '
	// <p>Заметки администратора администратору :-)</p>
	// ';
	/*
	<p><i>new:</i> Добавил возможность видеть балансы счетов в списках пользователей и компаний. Только для пользователей, включенных в группу «Банковский кассир»</p>

	<p>Смотреть логи можно, но пока не в замечательном интерфейсе платежной системы.<br />
	Иди в <a href="http://dev.304.ru/phpmyadmin">PHPMyAdmin</a>, логин crazy-dev, пароль сам знаешь.<br />
	Тебе будут доступны для просмотра таблицы с логами</p>
	';
	*/

	else {
		echo '
		<p><big>Добро пожаловать в&nbsp;Crazy Банк!</big></p>
		<p>Crazy Банк&nbsp;&mdash; это электронная банковская система поддержки Crazy-week.</p>
		<p>Она позволяет узнать информацию о&nbsp;банковском счете, перевести деньги на&nbsp;другой банковский счет.</p>
		<p>Компаниям система предоставляет интерфейс для оплаты товаров и&nbsp;услуг покупателем (электронным переводом).
		Руководители компаний также могут перевести деньги со&nbsp;счета компании на&nbsp;счета её&nbsp;сотрудников.</p>
		<p>Чтобы начать пользоваться системой, воспользуйтесь ссылкой в&nbsp;правом верхнем углу :-)</p>
		';
	}
	if ( isset ($account['id']) ) {
		echo '
		<h1>Информация о Вашем счете:</h1>';
		echo '<p>';
		print_account_info ( id2account ($account['id']) ) ;
		echo '</p>';
	}
}
?>
