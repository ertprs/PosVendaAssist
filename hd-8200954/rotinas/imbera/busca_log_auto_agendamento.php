<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../class/tdocs.class.php';

$login_fabrica = 158;

$sql = "
	    SELECT 
	            rsl.routine_schedule_log AS id,
		            TO_CHAR(rsl.date_start, 'DD/MM/YYYY HH24:MI:SS') AS initial_date,
			            TO_CHAR(rsl.date_finish, 'DD/MM/YYYY HH24:MI:SS') AS end_date,
				            rsl.date_start,
					            rsl.total_record AS total_tickets,
						            rsl.total_record_processed AS total_tickets_scheduled
							        FROM tbl_routine r
								    INNER JOIN tbl_routine_schedule rs ON rs.routine = r.routine
								        INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule = rs.routine_schedule
									    WHERE r.factory = {$login_fabrica}
									        AND rsl.create_at BETWEEN '2016-12-08 00:00:00' AND '2016-12-08 23:59:59'
										    AND rsl.date_finish IS NOT NULL
										        AND rsl.file_name IS NOT NULL
											    AND LOWER(r.context) LIKE 'abertura de tickets%'
											        ORDER BY rsl.date_start DESC
												";
$qry = pg_query($con, $sql);

$logs = pg_fetch_all($qry);
$tdocs = new TDocs($con, $login_fabrica);

foreach ($logs as $log) {
	    $log = (object) $log;

	        $attach = $tdocs->getDocumentsByRef($log->id, "log")->attachListInfo;
	        $link   = $tdocs->getDocumentLocation(key($attach));

		    $log_content = file_get_contents($link);

		    system("touch logs_auto_agendamento/{$log->id}.log");
		        file_put_contents("logs_auto_agendamento/{$log->id}.log", $log_content);
}
