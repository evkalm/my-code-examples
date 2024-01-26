<?php
	include_once 'settings.php';
	include_once 'php/functions.php';
	include 'php/data_from_db.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>"Экзамен Битрикс24"</title>

	<link href="css/main.css?v1" rel="stylesheet">
</head>

<body id="application">
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="//api.bitrix24.com/api/v1/"></script>
	<script src="js/functions.js"></script>
	
	<h2 id="error_message"></h2>

	<h3>Приложение "Экзамен Битрикс24"</h3>
	<h4>Выберете билет</h4>

	<table id="tickets-table">
		<thead>
			<tr>
				<th>Номер билета</th>
				<th>Тема (раздел)</th>
				<th>Сложность</th>
				<th class='send-data'></th>
			</tr>
		</thead>
		<tbody>
			<?php
				$i = 1;

				while ($row = mysqli_fetch_array($res_sql_tickets)) {
				
					if (in_array($i, $arr_list_tickets)) {
						echo "<tr><td>Билет №" . $row[0] . "</td>";
						echo "<td>" . $row[1] . "</td>";
						echo "<td>" . $row[3] . "</td>";
						$url = $row[2];
						echo "<td class='send-data'><span>Пройти тест</span><input class='data-num-ticket' type='text' value=" . $row[0] . " hidden><input class='data-url' type='text' value=" . $url . " hidden></td>";
					}

					$i++;
				}
			?>
		</tbody>
	</table>
	
	<input id="portal_name" type="text" value="<?=$cur_name_portal?>" hidden>
	<input id="user_name" type="text" hidden>
	<input id="user_surname" type="text" hidden>
	<input id="url_back" type="text" value="<?=$url_back?>" hidden>

	<br>
	<h4>Результаты тестов</h4>
	
	<?php
		if ($is_admin) {
			echo "<div class='poisk' style='display: inline-block'>Выберете портал &nbsp;&nbsp;</div>";
			echo "<select id='select-portal'>";
			echo "<option>Все</option>";
			
				for ($i = 0; $i < count($arr_portals); $i++) {
					echo "<option>" . $arr_portals[$i] . "</option>";
				}
				
			echo "</select>";
			echo "<span>&nbsp;&nbsp;(задействованы билеты: <span id='nums-tickets'></span>)</span>";
		}

		echo "<div class='poisk'><span>Поиск:&nbsp;</pan> <input class='form-control' type='text' placeholder='' id='search-text' onkeyup='tableSearch()'></div>";

		echo "<div class='poisk'>Примечание: тест считается успешным, если набранно не менее " . SUCCESS_PERCENT . "% баллов</div>";

		echo "<table id='info-table'><thead><tr>";

			if ($is_admin) {echo '<th>Портал</th>';};
			echo '<th>Сотрудник</th><th>№ билета</th><th>Дата</th><th>Набранные баллы</th><th>Результат</th></tr></thead><tbody>';

			while ($row = mysqli_fetch_array($res_sql_result)) {
				
				if ($row[8]) {
					echo '<tr>';
					if ($is_admin) {echo "<td>" . $row[1] . "</td>";}
					echo "<td>" . $row[2] . "</td>";
					echo "<td>Билет №" . $row[3] . "</td>";
					echo "<td>" . $row[6] . "</td>";
					echo "<td>" . $row[7] . "%</td>";
					if ($row[8] == FAIL_EXAM) {
						echo "<td class='red-result'>" . $row[8] . "</td></tr>";
					} else {
						echo "<td class='green-result'>" . $row[8] . "</td></tr>";
					}

					
				}
			
			}
		
		echo '</tbody></table>';
	?>

<script>

	$(document).ready(function () {

		// REST
		BX24.init(function(){

			// Определяем и записываем текущего пользователя
			BX24.callMethod(
				'user.current',
				{},
				function(result){
					$('#user_name').val(result.data().NAME);
					$('#user_surname').val(result.data().LAST_NAME);
				}
			);

		});

	
		// Запускаем экзамен и пробрасываем соответсвующие данные
		$(document).on('click', '.send-data', function() {
			
			let exam_key = makeRandomKey();

			let get_data = 'v1=' + $('#portal_name').val();
				get_data += '&v2=' + encodeURIComponent($('#user_name').val());
				get_data += '&v3=' + encodeURIComponent($('#user_surname').val());
				get_data += '&v4=' + $(this).children('.data-num-ticket').val();
				get_data += '&v5=' + exam_key;
				get_data += '&v6=' + $('#url_back').val();

			let url_ticket = $(this).children('.data-url').val() + '?exam_key=' + exam_key;

			var ERROR = false;

			if (!ERROR) {
				$.ajax({
					url: 'https://xxxxxxx.ru/exam/php/first_write_db.php',
					method: 'get',
					data: get_data,
					success: function(){
						window.open(url_ticket);
					}
				});
			} else {
				$('#error_message').text('Не определен пользователь. Попробуйте зайти позже, или обратиться к партнеру "Хххххх"');
			}

		});


		// ПОИСК
		// определяем массив с ключами "портал" => "список билетов"
		var arr1 = JSON.parse('<?php echo $arr_tickets_portals_for_js; ?>');
		// "рисуем" перечень билетов "админского" портала, т.е. все билеты (т.к. открываем впервый раз страницу)
		$('#nums-tickets').text(arr1['<?=NAME_ADMIN_PORTAL?>']);

		
		// делаем выборку по названию портала
		$(document).on('change','#select-portal',function(){
			tableSelectPortals();

			// и "рисуем" номера билетов, которые подключены для выбранного портала
			let value = document.getElementById('select-portal').value;
			let list = '';

			if (value == 'Все') {
				list = arr1['<?=NAME_ADMIN_PORTAL?>'];
			} else {
				list = arr1[value];
			}
			
			$('#nums-tickets').text(list);
		});
	
	});

</script>

</body>
</html>