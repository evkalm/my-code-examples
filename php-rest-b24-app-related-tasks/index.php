<?php
// ОБРАБАТЫВАЕМ ЗАПРОСЫ ИЗ ОЧЕРЕДИ SUPPORT

use Adamart_lib\oop\bitrix24\web_hooks\B24WebHookBase;
use Adamart_lib\oop\bitrix24\web_hooks\tasks\B24WebHookTasksTask;

// I. НАСТРОЙКИ ДЛЯ ОБРАБОТКИ ОШИБОК
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	
	// ДЛя отладки
		// if (1) file_put_contents('debug.php', '<?php' . PHP_EOL); die;


// II. ПРИ ОТСУТСТВИИ КЛЮЧЕЙ ОСТАНАВЛИВАЕМ СКРИПТ
	if ( !isset($_GET['ticket']) && $_GET['ticket'] !== 'sHYk66emeG')
		die;

	
// III. ПОДКЛЮЧАЕМ СКРИПТЫ И ПР.
	require_once 'settings.php';
	require_once '../../vendor/autoload.php';
	require_once $_SERVER['DOCUMENT_ROOT'] . '/xxxxx/xxxxx_lib/functions/controller.php';
	require_once $_SERVER['DOCUMENT_ROOT'] . '/xxxxx/xxxxx_lib/error-handler/controller.php';
	set_error_handler('myErrorHandler');

// IV. ОПРЕДЕЛЯЕМ ДОСТУПЫ К БД
	require_once $_SERVER['DOCUMENT_ROOT'] . '/xxxxx/general-settings/access_db.php';
	// получаем const: MY_SERVER_NAME, DB_USER_NAME, DB_PASSWORD, DB_NAME


try {
	// 1. ПОДКЛЮЧЕНИЕ К БД
		$CONN_DB = mysqli_connect(MY_SERVER_NAME, DB_USER_NAME, DB_PASSWORD, DB_NAME);
		if (!$CONN_DB) throw new Exception("Нет доступа к БД", 1);		// Завершаем, если нет содениения с БД


	// 2. ПОЛУЧАЕМ ДАННЫЕ ПО ЗАДАЧЕ
		// 2.1. Из очереди БД
			$ROW_ID = $_POST['row_id'];		// Номер строки таблицы БД, по которой необходимо получить данные
			$sql			= "SELECT * FROM queue_support WHERE id=$ROW_ID";
			$res_sql		= mysqli_query($CONN_DB, $sql);
			$ROW_DATA		= mysqli_fetch_assoc($res_sql);
				$TASK_STATUS			= $ROW_DATA['status'];
				$task_data_from_queue	= json_decode($ROW_DATA['data'], 1);
					$TASK_EVENT			= $task_data_from_queue['event'];
					$DOMAIN_B24			= $task_data_from_queue['domain'];
					$TASK_ID			= (int) $task_data_from_queue['task_id'];
					$COMMENT_ID			= isset($task_data_from_queue['comment_id']) ? $task_data_from_queue['comment_id'] : null;


		// 2.2. Из основной таблицы БД
			if ($DOMAIN_B24 === 'xxxxx.bitrix24.ru') {
				$sql = "SELECT * FROM xxxxx_support WHERE xxxxx_task_id = '$TASK_ID' LIMIT 1";
			} else {
				$sql = "SELECT * FROM xxxxx_support WHERE client_task_id = '$TASK_ID' LIMIT 1";
			}
			$result_sql		= mysqli_query($CONN_DB, $sql);

			if ($result_sql && mysqli_num_rows($result_sql) > 0) {
				$row = mysqli_fetch_assoc($result_sql);
				$result = [];
					foreach ($row as $key => $value) {
						$result[$key] = $value;
					}

				$OLD_CLIENT_TASK_ID		= $result['client_task_id'];
				$OLD_CLIENT_STAGE_ID	= $result['client_stage_id'];
				$OLD_ADAMART_TASK_ID	= $result['xxxxx_task_id'];
				$OLD_ADAMART_STAGE_ID	= $result['xxxxx_stage_id'];
				$LINKED_DOMAIN_B24		= $result['portal_url'];

				$HAS_TASK_IN_DB = true;

			} else {
				$OLD_CLIENT_TASK_ID		= NULL;
				$OLD_CLIENT_STAGE_ID	= NULL;
				$OLD_ADAMART_TASK_ID	= NULL;
				$OLD_ADAMART_STAGE_ID	= NULL;

				$HAS_TASK_IN_DB = false;
			}


		// 2.3. Из портала Битрикс24
			$data = [
				'taskId' => $TASK_ID,
				'select' => ['*']
			];
			B24WebHookBase::setWebHookURL(PORTALS_DATA[$DOMAIN_B24]['web_hook_url']);
			$TASK_DATA_B24 = B24WebHookTasksTask::get($data,1);
			
			// Если ['result'] не содержит ['task'], значит задача была удалена. Удаляем запись из очереди и прерываем скрипт
			if ( !isset($TASK_DATA_B24['result']['task']) ) {
				$sql		= "DELETE FROM queue_support WHERE id = $ROW_ID";
				$res_sql	= mysqli_query($CONN_DB, $sql);
				mysqli_close($CONN_DB);

				throw new Exception("Обрабатываемая из очереди задача отсутсвует на портале. task_id: $TASK_ID; portal: $DOMAIN_B24", 1);
			}

			$NEW_PRIMARY_TASK_STAGE_ID = (int) $TASK_DATA_B24['result']['task']['stageId'];


	// 3. ВАЛИДАЦИЯ ЗАДАЧ
		$HAS_TASK_VALIDATION = false;
		require_once 'scripts/z10-task-validation/index.php';

	// 2. ОПРЕДЕЛЯЕМ СОБЫТИЕ - ПРОИЗОШЛО СОЗДАНИЕ ЗАДАЧИ, ОБНОВЛЕНИЕ, ИЛИ ДОБАВЛЕНИЕ КОММЕНТАРИЯ
		$EVENT_STATUS = null;
		require_once 'scripts/z20-define-event-status/index.php';

	// 3. ПРИ СОЗДАНИИ ЗАДАЧИ
		if ($EVENT_STATUS === 'create_task' && $HAS_TASK_VALIDATION) {
			require_once 'scripts/z30-when-create-task/index.php';
		}

	// 4. ПРИ ОБНОВЛЕНИИ ЗАДАЧИ
		if ($EVENT_STATUS === 'update_task' && $HAS_TASK_VALIDATION) {
			require_once 'scripts/z40-when-update-task/index.php';
		}

	// 5. ПРИ ДОБАВЛЕНИИ КОММЕНТАРИЯ
		if ($EVENT_STATUS === 'add_comment' && $HAS_TASK_VALIDATION) {
			require_once 'scripts/z50-when-add-comment/index.php';
		}

	// 6. УДАЛЯЕМ СТРОКУ ИЗ ОЧЕРЕДИ
		$sql		= "DELETE FROM queue_support WHERE id = $ROW_ID";
		$res_sql	= mysqli_query($CONN_DB, $sql);
		mysqli_close($CONN_DB);
		

} catch (Throwable $err) {
	handlerExceptionAndErrors($err);
}


