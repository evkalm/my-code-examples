<?php

namespace Adamart_lib\oop\bitrix24\web_hooks;

abstract class B24WebHookBatch extends B24WebHookBase {

	// МЕТОД BATCH (max 2500 запросов)
	static public function batch($data_for_send) {
		$url = self::$common_web_hook_url . 'batch.json';
		$res = self::executeHook($url, $data_for_send);
		return $res;
	}

	// МЕТОД BATCH ДЛЯ ОДНОГО МЕТОДА И ОДИНАКОВЫХ ПАРАМЕТРОВ В ЗАПРОСЕ
	static public function batchGetForIdArr($method, $id_arr) {
		$res = [];
		$batch_50 = [];		// маленький пакет, состоящий из 50 шт. команд

		// Формируем пакет запросов
		$i = 1;
		foreach ($id_arr as $val) {
			$params = ['id' => $val];
			$batch_50[] = $method . '?' . http_build_query($params);

			if ( $i % 50 === 0 || $i === count($id_arr) ) {

				$data_for_send	= ['cmd' => $batch_50];
				$url = self::$common_web_hook_url . 'batch.json';
				$res_i = self::executeHook($url, $data_for_send)['result']['result'];
				$res = array_merge($res, $res_i);
				$batch_50 = [];
			}
			$i++;
		}

		return $res;
	}
}