<?php
require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$login_fabrica = 158;


$data_log = date("Y-m-d H:i:s");


$sql = "
    SELECT
        routine_schedule_log 
    FROM tbl_routine_schedule_log 
    WHERE date_finish IS NULL
    AND routine_schedule IN (
        SELECT
            routine_schedule 
        FROM tbl_routine_schedule
        WHERE routine IN (15,17,18,19,20)
    )
    AND date_start < now() + interval '45 minutes';
";

$res = pg_query($con, $sql);
$row = pg_num_rows($res);

if ($row == 0) {
        system ("echo $data_log sem pendencias >> /var/log/imbera-rotina-destrava.log");
}else{
        system ("echo $data_log com $row pendencias >> /var/log/imbera-rotina-destrava.log");
}

$i = 0;
for ($i = 0 ; $i < $row ; $i++) {
    $routine_schedule_log = pg_fetch_result ($res,$i,'routine_schedule_log');
    $sql = "DELETE FROM tbl_routine_schedule_log WHERE routine_schedule_log = {$routine_schedule_log}";
    $resX = pg_query ($con, $sql);
    system ("echo $data_log deletado routine_schedule_log $routine_schedule_log >> /var/log/imbera-rotina-destrava.log");
}


