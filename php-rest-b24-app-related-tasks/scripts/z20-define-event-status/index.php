<?php
// ОПРЕДЕЛЯЕМ, КАКОЕ СОБЫТИЕ ОТРАБАТЫВАТЬ:
	// - создание задачи
	// - обновление задачи
	// - добавление комментария


// 1. СОЗДАНИЕ ЗАДАЧИ
	$is_create_task = false;

	if ($DOMAIN_B24 !== 'xxxxx.bitrix24.ru') {	// не должны учитывать создание задачи на нашем портале

		// а) Если было создание задачи и и по условию для данного портала новая заявка должна создаваться при создании задачи (а не при переходе на стадию "new_task")
			if (
				$TASK_EVENT === 'ONTASKADD' &&
				PORTALS_DATA[$DOMAIN_B24]['create_when_add_task']
			) {
				$is_create_task = true;
			}

		// б) Если было обновление задачи и по условию новая заявка должна создаться при попдании на стадию "new_task"
		// также проверяем, чтобы по заявке не было создано ранее задачи, чтобы при случайном возврате на стадию "new_task" не задублировалась заявка
			
			if (!$is_create_task) {

				// проверяем, есть ли задача в БД
					$has_task_in_db = false;

					$task_id	= $TASK_DATA_B24['result']['task']['id'];
					$sql		= "SELECT * FROM xxxxx_support WHERE client_task_id = '$task_id'";
					$result		= mysqli_query($CONN_DB, $sql);
			
					if (mysqli_num_rows($result) > 0) {
						$has_task_in_db = true;
					}

				// переопределяем $is_create_task 
					if (
						!$has_task_in_db									&&
						$TASK_EVENT === 'ONTASKUPDATE'						&&
						!PORTALS_DATA[$DOMAIN_B24]['create_when_add_task']	&&
						(int) $NEW_PRIMARY_TASK_STAGE_ID === PORTALS_DATA[$DOMAIN_B24]['stages']['new_task']
					) {
						$is_create_task = true;
					}
			}
	}

// 2. ОБНОВЛЕНИЕ ЗАДАЧИ
	$is_update_task = false;

	if (!$is_create_task && $TASK_EVENT === 'ONTASKUPDATE') {
		$is_update_task = true;
	}


// 3. ДОБАВЛЕНИЕ КОММЕНТАРИЯ
	$is_add_comment = false;

	if ( $DOMAIN_B24 !== 'xxxxx.bitrix24.ru'  &&  $TASK_EVENT === 'ONTASKCOMMENTADD') {

		if ( PORTALS_DATA[$DOMAIN_B24]['create_when_add_task'] ) {
			$is_add_comment = true;
		} elseif ( (int) $NEW_PRIMARY_TASK_STAGE_ID !== PORTALS_DATA[$DOMAIN_B24]['stages']['new_task'] ) {
			$is_add_comment = true;
		}
	}


// 4. ОПРЕДЕЛЯЕМ СТАТУСЫ
	if ($is_create_task) {
		$EVENT_STATUS = 'create_task';
	} elseif ($is_update_task) {
		$EVENT_STATUS = 'update_task';
	} elseif ($is_add_comment) {
		$EVENT_STATUS = 'add_comment';
	}


