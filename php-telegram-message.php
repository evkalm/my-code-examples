<?php
// ОТПРАВКА СООБЩЕНИЙ В ТЕЛЕГРАМ ЧАТ

function sendMessageToTelegram($bot_token, $chat_ids, $message) {
	$url = 'https://api.telegram.org/bot' . $bot_token . '/sendMessage';
	$retry_intervals = [10, 20, 30];						// Интервалы повторных попыток в секундах
	$is_success = false;									// Флаг успешной отправки
	if ( !is_array($chat_ids) ) $chat_ids = [$chat_ids];	// если был указан один chat_id

	// Делаем сообщения для каждого указанного чата ТГ
	foreach ($chat_ids as $chat_id) {

		// Дублируем запросы 4 раза, если они были неудачными
		for($i = 0; $i < count($retry_intervals) + 1; $i++) {
			$ch = curl_init();
			curl_setopt_array(
				$ch,
				array(
					CURLOPT_URL				=> $url,
					CURLOPT_POST			=> TRUE,
					CURLOPT_RETURNTRANSFER	=> TRUE,
					CURLOPT_TIMEOUT			=> 20,
					CURLOPT_POSTFIELDS		=> array(
						'chat_id' => $chat_id,
						'text' => $message,
					),
				)
			);

			$res_json	= curl_exec($ch);
			$res		= json_decode($res_json, true);
			// Прим.: в ответе есть всегда логическое поле "ok".
			// если 'ok'=True	- запрос был успешным
			// если 'ok'=false	- запрос был неудачным

			if ($res_json === false || !$res['ok'] ) {
				
				if ( $i < count($retry_intervals) ) {
					// Неудачная отправка, повторяем попытку через заданный интервал
					sleep($retry_intervals[$i]);
				} else {
					// Превышены все попытки, прекращаем и выбрасываем исключение
					
						// формируем описание ошибки или ответа
							if ($res_json === false) {
								$error_description = curl_error($ch);
							} else {
								$error_description = $res_json;
							}

						curl_close($ch);

						throw new Exception('"Не удалось отправить сообщение в телеграм чат: ' . $message . '"'. PHP_EOL . 'Описание ошибки : "' . $error_description . '"');
				}

			} else {
				if ($res['ok']) $is_success = true;	// Успешно отправлено
			}

			curl_close($ch);
			if ($is_success) break;
		}
	}
}
