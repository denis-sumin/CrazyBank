<?php
// ItIsCrazyBankModule

$module = 'shop_admin';

if ( !defined("RequestModule") || RequestModule !== 'core' ) die;

$modules[$module]['name'] = 'Интернет-магазин'; // человеческое название модуля

$modules[$module]['action'][] = 'goods_manage';
$modules[$module]['menu'][] = 'Управление товарами';
$modules[$module]['title'][] = 'Управление товарами в магазине';

$modules[$module]['action'][] = 'goods_manage_categories';
$modules[$module]['menu'][] = 'Управление категориями товаров';
$modules[$module]['title'][] = 'Управление категориями товаров';

$modules[$module]['action'][] = 'goods_add_item';
$modules[$module]['title'][] = 'Управление товарами в магазине';

$modules[$module]['action'][] = 'goods_save_item';
$modules[$module]['title'][] = 'Управление товарами в магазине';

$modules[$module]['groups'][] = 'admin';
$modules[$module]['groups'][] = 'company';

function print_good_edit_form( $action, $values )
{
	global $account;

	echo '
	<p>
	<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'" enctype="multipart/form-data">';
	if( $action == 'goods_save_item' )
	{
		echo '<input type="hidden" name="id" value="'.@$values['id'].'" /></td>';
	}
	echo '
	<table class="userinfo">
		<tr>
			<td>Название</td> 
			<td><input type="text" name="title" value="'.@$values['title'].'" /></td>
		</tr>
		<tr>
			<td>Описание</td> 
			<td><textarea name="description" style="height: 150px; width: 300px;">'.@$values['description'].'</textarea></td>
		</tr>
		<tr>
			<td>Категория</td> 
			<td><select name="category_id">';
			foreach (getGoodsCategories() as $category_id=>$name) {
				echo '<option value="'.$category_id.'">'.$name.'</option>';
			}
			echo '
			</select></td>
		</tr>
		<tr>
			<td>Цена</td> 
			<td><input type="text" name="price" value="'.@$values['price'].'" /></td>
		</tr>
		';

		if( accounttype( $account['id'] ) == 'company' )
		{
			echo '<input type="hidden" name="company_id" value="'.id2account( $account['id'] ).'" />';
		}
		else
		{
			echo '
			<tr>
				<td>Счёт компании-продавца</td> 
				<td><input type="text" name="company_id" id="account_id" value="'.@$values['company_id'].'" /></td>
			</tr>';			
		}
		echo '
		<tr>
			<td>Изображение</td> 
			<td>
				<input type="file" name="photo" /><br />
				Разрешённые типы файлов: jpg, png, gif.<br />
				Ограничение на размер файла: 20 Мб.<br />
				<img src="/upload/shop/'.@$values['id'].'.'.@$values['photo_extension'].'" width="300px" /></td>
		</tr>';

		echo '
		<tr><td>&nbsp;</td> 
			<td> <input type="submit" name="'.$action.'" value="Сохранить" /></td>
		</tr>	
	</table>
	</form>
	</p>';
}

function parse_file_upload( $good_id )
{
	$allowedExts = array("gif", "jpeg", "jpg", "png");
	$temp = explode(".", $_FILES["photo"]["name"]);
	$extension = end($temp);

	$destination_dir = 'upload/shop';

	if ( ( ($_FILES["photo"]["type"] == "image/gif")
		|| ($_FILES["photo"]["type"] == "image/jpeg")
		|| ($_FILES["photo"]["type"] == "image/jpg")
		|| ($_FILES["photo"]["type"] == "image/pjpeg")
		|| ($_FILES["photo"]["type"] == "image/x-png")
		|| ($_FILES["photo"]["type"] == "image/png"))
		&& ($_FILES["photo"]["size"] < 20000000)
		&& in_array($extension, $allowedExts) )
	{
		if ($_FILES["photo"]["error"] > 0) {
			echo "Return Code: " . $_FILES["photo"]["error"] . "<br>";
		}
		else
		{
			$extension = explode( '.', $_FILES["photo"]["name"] );
			$extension = $extension[ count($extension)-1 ];

			$filename = $good_id.'.'.$extension;

			return move_uploaded_file( $_FILES["photo"]["tmp_name"],  $destination_dir . '/' . $filename );
		}
	}
	else
	{
		echo "Invalid file";
		return false;
	}
}

function show_shop_admin ($action) {
	global $modules, $account, $accountlist, $group;	
	// $module = $modules['admin'];
	
	switch ($action) {
			
		case 'goods_add_item':
			if ( isset ($_POST[$action]) ) {

				$photo_extension = explode( '.', @$_FILES["photo"]["name"] );
				$photo_extension = $photo_extension[ count($photo_extension)-1 ];

				$good_id = addGoodsItem ( $_POST['company_id'], $_POST['title'], $_POST['description'], @$_POST['category_id'], $_POST['price'], $photo_extension );
				if ( $good_id && parse_file_upload( $good_id ) )
				{
					echo '<p>Товар успешно добавлен</p>';
				}	
				else echo '<p>Ошибка добавления товара</p>';
				echo '<script>$(document).ready(function () { window.setTimeout(function () { location.href = "'.$_SERVER["PHP_SELF"].'?action=goods_manage"; }, 500) });</script>';				
			}
			else
			{
				report_error( 'Вас тут не должно было быть' );
			}
			break;

		case 'goods_save_item':
			if ( isset ($_POST[$action]) ) {

				$photo_extension = explode( '.', @$_FILES["photo"]["name"] );
				$photo_extension = $photo_extension[ count($photo_extension)-1 ];

				if (
					saveGoodsItem ( $_POST['id'], $_POST['company_id'], $_POST['title'], $_POST['description'], $_POST['category_id'], $_POST['price'], $photo_extension ) &&
					parse_file_upload( $_POST['id'] )
				)  {
					echo '<p>Товар успешно сохранён</p>';
				}	
				else echo '<p>Ошибка сохранения товара</p>';
				echo '<script>$(document).ready(function () { window.setTimeout(function () { location.href = "'.$_SERVER["PHP_SELF"].'?action=goods_manage"; }, 500) });</script>';
			}
			else
			{
				report_error( 'Вас тут не должно было быть' );
			}
			break;

		case 'goods_manage':

			$accountlist = ( accounttype( $account['id'] ) == 'company' ) ? 0 : 2;
			$goods_list = getGoodsList( $account );

			if( isset( $_GET['good_id'] ) )
			{
				$good_information = false;
				foreach ( $goods_list as $key => $value )
				{
					if( $key == $_GET['good_id'] )
					{
						$good_information = $value;
					}
				}
				if( $good_information )
				{
					print_good_edit_form( 'goods_save_item', $good_information );
				}
				else
				{
					echo 'Запрошенный товар не найден. Перейдите к <a href="'.$_SERVER["PHP_SELF"].'?action='.$action.'">списку товаров</a>.';
				}
			}

			else
			{
				if( $goods_list )
				{
					echo '<p>
					<table>
						<tr>
							<th>Название товара</th>
							<th></th>
						</tr>
					';
					foreach( $goods_list as $key => $value ) 
					{
						echo '
							<tr>
								<td>'.$value['title'].'</td>
								<td><a href="'.$_SERVER["PHP_SELF"].'?action='.$action.'&good_id='.$value['id'].'">изменить</a></td>
							</tr>
						';				
					}			
					echo '</table></p>';		
				}

				echo '<h2>Добавить товар</a></h2>';
				print_good_edit_form( 'goods_add_item', array() );
			}
			
			break;

		case 'goods_manage_categories':

			if ( isset ($_POST[$action]) ) {
				if ( addGoodsCategory ( $_POST['name'] ) )  {
					echo '<p>Категория успешно добавлена</p>';
				}	
				else echo '<p>Ошибка добавления категории</p>';
			}

			$categories_list = getGoodsCategories();

			echo '<p>
			<table>
				<tr>
					<th>Название категории</th>
					<th></th>
				</tr>
			';
			foreach( $categories_list as $key => $value ) 
			{
				echo '
					<tr>
						<td>'.$value.'</td>
						<td></td>
					</tr>
				';				
			}
			echo '</table></p>';

			echo '<h2>Добавить категорию</a></h2>';			
			echo '
			<p>
			<form method="post" action="'.$_SERVER["PHP_SELF"].'?action='.$action.'">
			<table class="userinfo">
				<tr>
					<td>Название</td> 
					<td><input type="text" name="name" /></td>
				</tr>
				<tr><td>&nbsp;</td> 
					<td> <input type="submit" name="'.$action.'" value="Добавить" /></td>
				</tr>	
			</table>
			</form>
			</p>';
			
			break;

	}
}
?>