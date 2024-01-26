<?php
// ПЕРЕНОС ВЫДЕЛЕННЫХ СТРОК ИЗ ЛИСТА "ХОД РАБОТ" В "АРХИВ"
// 1. ПОЛУЧАЕМ ВСЕ ДАННЫЕ ЛИСТА "ХОД РАБОТ"
// 2. ПОЛУЧАЕМ ВСЕ ДАННЫЕ ЛИСТА "АРХИВ"
// 3. ОПРЕДЕЛЯЕМ ВСПОМОГАТЕЛЬНЫЕ ПРЕМЕННЫЕ
// 4. ОПРЕДЕЛЯЕМ МАССИВ СТРОК, КОТОРЫЕ НЕОБХОДИМО ПЕРЕНЕСТИ В АРХИВ
// 5. ОСТАНАВЛИВАЕМ СКРИПТ, ЕСЛИ НЕ ВЫБРАНО НИ ОДНОЙ ЗАПИСИ ДЛЯ КОПИРОВАНИЯ
// 6. ОПРЕДЕЛЯЕМ МАССИВ КОЛОНОК (ColumnIndex), ГДЕ ЯЧЕЙКИ ОБЪЕДИНЕНЫ
// 7. КОПИРУЕМ ЗАПИСИ В АРХИВ
// 8. УДАЛЯЕМ ЗАПИСИ ИЗ "ХОД РАБОТ"
// 9. УДАЛЯЕМ ИЗ ФАЙЛА "ХРАНИЛИЩА" СДЕЛКИ, КОТОРЫЕ ПЕРЕНЕСЛИ В АРХИВ


use Adamart_lib\oop\google\sheets\GoogleSheetsFunctions;

// 1. ПОЛУЧАЕМ ВСЕ ДАННЫЕ ЛИСТА "ХОД РАБОТ"
	$HOD_RABOT_SHEET_NAME	= GoogleSheetsFunctions::getSheetName($SERVICE_SHEET, $SS_ID, $SHEET_ID_HOD_RABOT);
	$HOD_RABOT_SHEET_VALUES	= $SERVICE_SHEET->spreadsheets_values->get($SS_ID, $HOD_RABOT_SHEET_NAME)['values'];


// 2. ПОЛУЧАЕМ ВСЕ ДАННЫЕ ЛИСТА "АРХИВ"
	$ARHIV_SHEET_NAME	= GoogleSheetsFunctions::getSheetName($SERVICE_SHEET, $SS_ID, $SHEET_ID_ARHIV);
	$ARHIV_SHEET_VALUES	= $SERVICE_SHEET->spreadsheets_values->get($SS_ID, $ARHIV_SHEET_NAME)['values'];


// 3. ОПРЕДЕЛЯЕМ ВСПОМОГАТЕЛЬНЫЕ ПРЕМЕННЫЕ
	// 2.1 Определяем номер колонки для флажков
		$num_col_checkbox = 31;
		$i = 1;
		foreach ($HOD_RABOT_SHEET_VALUES[0] as $value) {
			if ( $value === 'ToArchive') $num_col_checkbox = $i;
			$i++;
		}
	
	// 2.2 С какой строки начинаются записи в таблице
		$num_row_first_line = 5;	// по умолчанию
		foreach ($ARHIV_SHEET_VALUES as $key => $item) {
			if ( isset($item[0]) && $item[0] === 'LastHeadRow') $num_row_first_line = $key + 2;
		}


	// 2.2 Строка для записи в листе "Архив"
		$num_row_for_write = count($ARHIV_SHEET_VALUES) + 1;


// 4. ОПРЕДЕЛЯЕМ МАССИВ СТРОК И ПЕРЕЧЕНЬ DEAL_ID, КОТОРЫЕ НЕОБХОДИМО ПЕРЕНЕСТИ В АРХИВ
	$row_nums_for_arhiv		= [];
	$list_deal_id_for_arhiv	= [];
	foreach ($HOD_RABOT_SHEET_VALUES as $key => $item) {
		if ( isset($item[$num_col_checkbox - 1]) && $item[$num_col_checkbox - 1] === 'TRUE') {
			$row_nums_for_arhiv[]		= $key + 1;
			$list_deal_id_for_arhiv[]	= $item[0];
		}
	}


// 5. ОСТАНАВЛИВАЕМ СКРИПТ, ЕСЛИ НЕ ВЫБРАНО НИ ОДНОЙ ЗАПИСИ ДЛЯ КОПИРОВАНИЯ
	if ( !isset($row_nums_for_arhiv[0]) && empty($row_nums_for_arhiv[0]) )
		die;


// 6. ОПРЕДЕЛЯЕМ МАССИВ КОЛОНОК (ColumnIndex), ГДЕ ЯЧЕЙКИ ОБЪЕДИНЕНЫ
	$start_column_index_arr = [];

	$response = $SERVICE_SHEET->spreadsheets->get($SS_ID)['sheets'];

	// Определяем данные для листа "ход работ"
		$sheet_merges_data = [];
		foreach ($response as $key => $item) {
			if ($item['properties']['sheetId'] == $SHEET_ID_HOD_RABOT) $sheet_merges_data = $item['merges'];
		}

	foreach ($sheet_merges_data as $item) {
		if ($item['startRowIndex'] == $row_nums_for_arhiv[0] - 1) $start_column_index_arr[] = $item['startColumnIndex'];
	}


// 7. КОПИРУЕМ ЗАПИСИ В АРХИВ
	foreach ($row_nums_for_arhiv as $num_row_i) {

		// 7.1. Получаем стили копируемых ячеек
			$last_col_letter	= letterColumnSheetByNumber($num_col_checkbox - 1);
			$range_i			= $HOD_RABOT_SHEET_NAME . '!A' . $num_row_i . ':' . $last_col_letter . ( $num_row_i + 1 );

			$response_i	= $SERVICE_SHEET->spreadsheets->get($SS_ID, ['ranges' => $range_i, 'fields' => 'sheets.data.rowData.values']);
			$rowData_i	= $response_i->getSheets()[0]->getData()[0]->getRowData();


		// 7.2. Подготавливаем запрос на перенос данных
			$requests = [
				// Копируем значения из "Ход работ" в "Архив"
				[
					'copyPaste' => [
						'source' => [
							'sheetId'			=> $SHEET_ID_HOD_RABOT,
							'startRowIndex'		=> $num_row_i - 1,
							'endRowIndex'		=> $num_row_i + 1,
							'startColumnIndex'	=> 0,
							'endColumnIndex'	=> $num_col_checkbox - 1,
						],
						'destination' => [
							'sheetId'			=> $SHEET_ID_ARHIV,
							'startRowIndex'		=> $num_row_for_write - 1,
							'endRowIndex'		=> $num_row_for_write + 1,
							'startColumnIndex'	=> 0,
							'endColumnIndex'	=> $num_col_checkbox - 1,
						],
						'pasteType' => 'PASTE_VALUES',
					],
				],
				// Обновляем формат ячеек в "Архиве"
				[
					'updateCells' => [
						'range' => [
							'sheetId'			=> $SHEET_ID_ARHIV,
							'startRowIndex'		=> $num_row_for_write - 1,
							'endRowIndex'		=> $num_row_for_write + 1,
							'startColumnIndex'	=> 0,
							'endColumnIndex'	=> $num_col_checkbox - 1,
						],
						'rows' => $rowData_i,
						'fields' => 'userEnteredFormat',
					],
				],
			];

		// 7.3. Добавляем в запрос данные для объединения ячеек
			foreach ( $start_column_index_arr as $index_merge_col_i) {
				if ( $index_merge_col_i >= $num_col_checkbox - 1 ) continue;
				$requests[] = [
					'mergeCells' => [
						'range' => [
							'sheetId'			=> $SHEET_ID_ARHIV,
							'startRowIndex'		=> $num_row_for_write - 1,
							'endRowIndex'		=> $num_row_for_write + 1,
							'startColumnIndex'	=> $index_merge_col_i,
							'endColumnIndex'	=> $index_merge_col_i + 1
						],
						'mergeType' => 'MERGE_ALL'
					]
				];
			}
		
		// 7.4. Формируем окончательный запрос и отправляем его
			$body = new Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
				'requests' => $requests
			]);

			$result = $SERVICE_SHEET->spreadsheets->batchUpdate($SS_ID, $body);

		$num_row_for_write += 2;
	}


// 8. УДАЛЯЕМ ЗАПИСИ ИЗ "ХОД РАБОТ"
	$requests = [];
	$q = 0;
	foreach ($row_nums_for_arhiv as $num_row_i) {
		$num_row_i = $num_row_i - $q;
		$requests = [
			'deleteRange' => [
				'range' => [
					'sheetId'		=> $SHEET_ID_HOD_RABOT,
					'startRowIndex'	=> $num_row_i - 1,
					'endRowIndex'	=> $num_row_i + 1
				],
				'shiftDimension' => 'ROWS'
			]
		];

		$batch_update_request = new Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
			'requests' => $requests
		]);
		$response = $SERVICE_SHEET->spreadsheets->batchUpdate($SS_ID, $batch_update_request);
		$q += 2;
	}

// 9. УДАЛЯЕМ ИЗ ФАЙЛА "ХРАНИЛИЩА" СДЕЛКИ, КОТОРЫЕ ПЕРЕНЕСЛИ В АРХИВ
	// получаем "предыдущий" перечень сделок из файла "хранилища"
		$file = __DIR__.'/' . $STORAGE_FILE_NAME . '.txt';
		$pre_list_deal_id = unserialize( file_get_contents($file) );

	// Удаляем сделки, переносимые в архив
		$format_list_deal_id = array_diff($pre_list_deal_id, $list_deal_id_for_arhiv);

	// записываем перечень id сделок в файл "хранилище"
		file_put_contents($file, serialize($format_list_deal_id));




