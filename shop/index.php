<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Crazy Bank</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link type="text/css" rel="stylesheet" media="all" href="../style/style.css" />
		<style type="text/css">
			body {
				margin: 20px auto;
				width: 800px;
			}
		</style>
	</head>
	<body>

		<h1>Интернет-магазин Crazy Week</h1>

<?php
include ("../mysql_config.php");
include ("../config.php");
include ("../functions_controller.php");
include ("../functions_view.php");

if (!mysql_connect($mysql_server,$mysql_user,$mysql_password))
	exit ('Произошла ошибка подключения к базе данных. Повторите попытку и сообщите, пожалуйста, о случившемся правительству Crazy Week.');

mysql_select_db($mysql_db);

$goods_list = getGoodsList( 0 );

foreach ( $goods_list as $key => $value ) {
	echo '<h2>'.$value['title'].'</h2>';
	echo '<p><img src="/upload/shop/'.$key.'.'.$value['photo_extension'].'" align="left" style="max-width: 400px; max-height: 400px; margin-right: 10px;"/>'.$value['description'].'</p>';

	$company = get_account_info( $value['company_id'] );

	echo '<p style="clear: both">Продавец: '.$company['name'].'</p>';
	echo '<p>Цена: '.$value['price'].' профлома</p>';
}

?>
	</body>
</html>
