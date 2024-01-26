<?php

namespace App\Http\Controllers;

use App\Services\Calculations\WeldCalculations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Calc1CalcAllController extends Controller {
	
	public function __invoke(Request $request) {

		$blocks_data = $request->all();

		// 1. Получаем массивы:
			// - сварных швов, по которым необходимо получить выборку расходов из БД
			// - электродов, по которым необходимо получить выборку групп из БД
			$weld_ru_arr	= [];
			$electrodes_arr	= [];
			foreach ($blocks_data as $block_data_i) {
				foreach ($block_data_i['rows'] as $row_data_i) {
					$weld_ru_i		= $row_data_i['cols']['col_4']['value'];
					$electrodes_i	= $row_data_i['cols']['col_2']['value'];
					if ( !in_array($weld_ru_i, $weld_ru_arr) ){
						$weld_ru_arr[] = $weld_ru_i;
					}
					if ( !in_array($electrodes_i, $electrodes_arr) ){
						$electrodes_arr[] = $electrodes_i;
					}
				}
			}

		// 2. Изменяем обозначения швов при необходимости
			for ($i = 0; $i < count($weld_ru_arr); $i++) {
				if ($weld_ru_arr[$i] === "Н1") {
					$weld_ru_arr[$i] = "Т1";
				} elseif ($weld_ru_arr[$i] === "Н2") {
					$weld_ru_arr[$i] = "Т3";
				}
			}
		
		// 2. Получаем данные по расходам из БД для необходимых швов
			$sb30_arr = WeldCalculations::getArrRashodSb30Gost5264($weld_ru_arr);

		// 3. Получаем данные по принадлежности электродов к группам
			// в виде 'electrode_name' => 'group'
			$electrode_groups_arr = WeldCalculations::getArrElectrodeGroups($electrodes_arr);

		// 4. Считаем расход всех электродов
			$res = [];
				// возвращаем в виде массива:
				// $res = [
				// 	[
				// 		'id'		=> $material_id,
				// 		'material'	=> $material_name,
				// 		'quantity'	=> $qty
				// 	]
				// ];

			$material_id = 0;

			// делаем "обход" по блокам
			foreach ($blocks_data as $block_data_i) {

				// считаем только "отмеченные" блоки
				if ($block_data_i['mark']) {

					// делаем "обход" по строкам в блоке
					foreach ($block_data_i['rows'] as $row_data_i) {

						$material_name = $row_data_i['cols']['col_2']['value'];

						$qty = WeldCalculations::calculationAndDefineDataForRow($sb30_arr, $electrode_groups_arr, $row_data_i, $block_data_i['quantity'])['qty'];

						// определяем, записан ли уже материал в $res
							$contains_material = false;
							$key_res	= null;

							foreach ($res as $key => $subArray) {
								if ($subArray['material'] === $material_name) {
									$contains_material = true;
									$key_res = $key;
									break;
								}
							}
						
						// если материал еще не записан, записываем его
							if (!$contains_material) {
								$material_id++;
								$res[] = [
									'id'		=> $material_id,
									'material'	=> $material_name,
									'quantity'	=> $qty
								];
							
						// иначе плюсуем к существующему
							} else {
								$res[$key_res]['quantity'] += (float) $qty;
							}
					}
				}
			}

		// 5. Переопределяем расход электродов с повторным округлением (иначе случается "сбой" в округлениях)
			foreach($res as $key => $item) {
				$res[$key]['quantity'] = round($item['quantity'], 3);
			}

		// 6. Если в итоге строк меньше 4-х, то добавляем пустые строки
			if ( $material_id < 4 ) {
				for ($i = $material_id + 1; $i <= 4; $i++) {
					$res[] = [
						'id'		=> '',
						'material'	=> '',
						'quantity'	=> ''
					];
				}
			}

		return $res;
	}
}




