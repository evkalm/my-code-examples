<?php
// ПРОВЕРЯЕМ, ОТНОСИТСЯ ЛИ ЗАДАЧА К ГРУППЕ "ПОДДЕРЖКА"


// 1. "ПРОПУСКАЕМ" ТОЛЬКО ЗАДАЧИ, КОТОРЫЕ ИЗ ГРУППЫ "ПОДДЕРЖКА Б24"
	if (
		isset($TASK_DATA_B24['result']['task']['group']['id'])	&&
		(int) $TASK_DATA_B24['result']['task']['group']['id']  ===  PORTALS_DATA[$DOMAIN_B24]['group_id']
	) {
		$HAS_TASK_VALIDATION = true;
	}


// 2. "ПРОПУСКАЕМ" ИЗМЕНЕННЫЕ ЗАДАЧИ (ONTASKUPDATE), У КОТОРЫХ "НОВАЯ СТАДИЯ" НЕ РАВНА "ПРЕДЫДУЩЕЙ"
	if ( $TASK_EVENT === 'ONTASKUPDATE' ) {
		$pre_stage = $DOMAIN_B24 === 'adamart.bitrix24.ru' ? $OLD_ADAMART_STAGE_ID : $OLD_CLIENT_STAGE_ID;

		if ( (int) $NEW_PRIMARY_TASK_STAGE_ID !== (int) $pre_stage ) {
			$HAS_TASK_VALIDATION = true;
		} else {
			$HAS_TASK_VALIDATION = false;
		}
	}


