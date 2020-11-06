<?php
require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require __DIR__ . '/../../classes/Posvenda/Os.php';
require __DIR__ . '/./funcoes.php';
require __DIR__ . '/../../admin/cockpit/api/persys.php';

global $login_fabrica;
$login_fabrica = 158;

use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\LogError;

$routines = new \Posvenda\Routine();
$routine = $routines->SelectRoutine('Retorno KOF');

$routineSchedule = new RoutineSchedule();
$routineSchedule->setRoutine($routine[0]['routine']);
$routineSchedule->setWeekDay(date("w"));

$routine_schedule_id = $routineSchedule->SelectRoutineSchedule();
#exit;

$log = new \Posvenda\Log();
$log->setRoutineSchedule($routine_schedule_id);
$log->setDateStart(date("Y-m-d H:i:s"));
$log->Insert();
$log->setRoutineScheduleLog($log->SelectId());

$ftp_server = "201.33.134.254";
$ftp_user   = "BRAPPIMBER";
$ftp_pass   = "W+58+dkQ";

$filedate = date("d/m/Y h:i:s a");
$data_exportado = date("Y-m-d H:i:s");

$filename_ftp = "IMB".date("Ymd")."_0000";

$sql = "
    SELECT DISTINCT date_start FROM tbl_routine_schedule_log WHERE routine_schedule = {$routine_schedule_id} and create_at::date = CURRENT_DATE
";
$res = pg_query($con, $sql);

$row = pg_num_rows($res);

//$filename_ftp .= ($row + 2).".txt";
$filename_ftp .= $row.".txt";
#exit;

$filename = $filename_ftp;
$filename_log = $filename;

function makeIntervalToHours($initialDate, $finalDate){
    $date1         = strtotime($initialDate->format('Y-m-d H:i:s.u'));
    $date2         = strtotime($finalDate->format('Y-m-d H:i:s.u'));
    $interval      = $date2 - $date1;
    $intervalHours = $interval/3600;
    $intervalHours = number_format($intervalHours, 2);

    return $intervalHours; 
}

function saveIntoFile($fileData) {
    global $filename, $_serverEnvironment;

    $filename_ftp = $filename;
    $filename_saida = "/mnt/kof/saida/{$filename}";
    $filename = __DIR__ . "/retorno-kof/{$filename}";

    $file = fopen($filename,'w');

    foreach ($fileData as $line) {
        $fileLine = implode('|',$line)."|||||||||||||||||||||\r\n";

        fwrite($file, $fileLine);
    }

    fwrite($file, "       ".count($fileData)."|".date("d.m.Y")."\n");

    fclose($file);

    if (!file_exists($filename) || (filesize($filename) == 0 || is_null(filesize($filename)))) {
        unlink($filename);
        throw new Exception("Erro ao criar o arquivo de retorno para a KOF #2");
    }

    if ($_serverEnvironment == "production") {

	copy($filename, $filename_saida);
	
        /*$ftp_server = "107.22.251.191";
        $ftp_user   = "ww2novo";
        $ftp_pass   = "tc2006";

        $conexao_ftp = ftp_connect($ftp_server, 21);
        ftp_login($conexao_ftp, $ftp_user, $ftp_pass);
        ftp_pasv($conexao_ftp, true);
	    ftp_chdir($conexao_ftp, "saida");

        if (!ftp_put($conexao_ftp, $filename_ftp, $filename, FTP_ASCII)) {
            unlink($filename);
            throw new Exception("Erro ao criar o arquivo de retorno para a KOF #3");
        }

	ftp_close($conexao_ftp);*/
    }
}

$medida = array(
    '01' => 'CS-GE300',
    '02' => 'CS-PM300',
    '03' => 'CS-PM300',
    '04' => 'CS-CB300',
    '05' => 'CS-VM300',
);

$categoriaDefeito = array(
    '01' => 'CS-GE200',
    '02' => 'CS-PM200',
    '03' => 'CS-PM200',
    '04' => 'CS-CB200',
    '05' => 'CS-VM200',
);

$sql = "
SELECT DISTINCT ON (os.os)
    os.os, 
    os_campo_extra.campos_adicionais,
    os.data_fechamento, 
    os.finalizada, 
    TO_CHAR(ose.termino_atendimento, 'DD.MM.YYYY HH24:MI:SS') as finalizada_formatada,
    os.obs AS observacao,
    os.data_digitacao,
    defeito.codigo AS codigo_defeito,
    defeito.descricao AS descricao_defeito,
    (
        SELECT solucao.codigo
        FROM tbl_os_defeito_reclamado_constatado AS os_solucoes
        INNER JOIN tbl_solucao AS solucao ON solucao.solucao = os_solucoes.solucao AND solucao.fabrica = $login_fabrica
        INNER JOIN tbl_classificacao AS classificacao ON classificacao.classificacao = solucao.classificacao AND classificacao.fabrica = $login_fabrica
        WHERE os_solucoes.os = os.os
        ORDER BY classificacao.peso DESC
        LIMIT 1
    ) AS solucao,
    cockpit.dados AS cockpit,
    (
        SELECT os_mobile.dados 
        FROM tbl_os_mobile AS os_mobile 
        WHERE os_mobile.os = os.os 
        ORDER BY os_mobile.data_input DESC
        LIMIT 1
    ) AS os_mobile,
    familia.codigo_familia
    FROM tbl_os AS os
    INNER JOIN tbl_os_extra ose ON ose.os = os.os
    INNER JOIN tbl_tipo_atendimento AS tipo_atendimento ON tipo_atendimento.tipo_atendimento = os.tipo_atendimento AND tipo_atendimento.fabrica = $login_fabrica
    INNER JOIN tbl_os_campo_extra AS os_campo_extra ON os_campo_extra.os = os.os
    INNER JOIN tbl_os_defeito_reclamado_constatado AS os_defeitos ON os_defeitos.os = os.os AND os_defeitos.defeito_constatado IS NOT NULL
    INNER JOIN tbl_defeito_constatado AS defeito ON defeito.defeito_constatado = os_defeitos.defeito_constatado
    INNER JOIN tbl_hd_chamado AS hd_chamado ON hd_chamado.hd_chamado = os.hd_chamado
    INNER JOIN tbl_hd_chamado_cockpit AS cockpit ON cockpit.hd_chamado = hd_chamado.hd_chamado
    INNER JOIN tbl_cliente_admin AS cliente_admin ON cliente_admin.cliente_admin = hd_chamado.cliente_admin AND cliente_admin.fabrica = $login_fabrica
    INNER JOIN tbl_os_produto AS os_produto ON os_produto.os = os.os
    INNER JOIN tbl_produto AS produto ON produto.produto = os_produto.produto
    INNER JOIN tbl_familia AS familia ON familia.familia = produto.familia
    WHERE os.fabrica = $login_fabrica
    AND (tipo_atendimento.grupo_atendimento != 'S' OR tipo_atendimento.grupo_atendimento IS NULL)
    AND os.data_fechamento IS NOT NULL
    AND os.finalizada IS NOT NULL
    AND os.exportado IS NULL
    AND ose.termino_atendimento IS NOT NULL
    ORDER BY os.os ASC
";
$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {
    $arrOsFechadas = pg_fetch_all($res);
} else {
    $arrOsFechadas = array();
}
    
try {
    pg_query($con, "BEGIN");

    $fileData = array();

    $timezone = new DateTimeZone("America/Sao_Paulo");

    foreach($arrOsFechadas as $osData){
        $cockpitData       = json_decode($osData['cockpit'], true);
        $osMobile          = json_decode($osData['os_mobile'], true);
        $observacao        = (array_key_exists('observacao', $osMobile)) ? $osMobile['observacao'] : '';        
        #$inicioAtendimento = DateTime::createFromFormat('Y-m-d H:i:s',$osData['data_digitacao'], $timezone);
        #$fimAtendimento    = DateTime::createFromFormat('Y-m-d H:i:s', $osData['finalizada'], $timezone);
        #$interval          = makeIntervalToHours($inicioAtendimento, $fimAtendimento);
        list($finalizada_data, $finalizada_hora) = explode(" ", $osData['finalizada_formatada']);

        $line = array(
            str_pad($osData['os'], 10, "0", STR_PAD_LEFT),
            $cockpitData['osKof'],
            $categoriaDefeito[trim($osData['codigo_familia'])],
            trim($osData['codigo_defeito']),
            $medida[trim($osData['codigo_familia'])],
            $osData['solucao'],
            $finalizada_data,
            $finalizada_hora,
            str_pad($cockpitData['protocoloKof'], 12, "0", STR_PAD_LEFT),
            $osData['descricao_defeito'],
            0
        );

        $fileData[] = $line;

        if (empty($osData["campos_adicionais"])) {
            $campos_adicionais = array(
                "arquivo_saida_kof"              => $filename,
                "data_geracao_arquivo_saida_kof" => $filedate
            );
        } else {
            $campos_adicionais = json_decode($osData["campos_adicionais"], true);
            $campos_adicionais["arquivo_saida_kof"]              = $filename;
            $campos_adicionais["data_geracao_arquivo_saida_kof"] = $filedate;
        }

        $campos_adicionais = json_encode($campos_adicionais);

        $update = "
            UPDATE tbl_os SET
                exportado = '{$data_exportado}'
            WHERE fabrica = $login_fabrica
	    AND os = {$osData['os']};";
	$resUpdate = pg_query($con, $update);

	$update = "
            UPDATE tbl_os_campo_extra SET
                campos_adicionais = '{$campos_adicionais}'
            WHERE os = {$osData['os']};
        ";
        $resUpdate = pg_query($con, $update);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao criar o arquivo de retorno para a KOF #1");
        }
    }

    saveIntoFile($fileData);

    pg_query($con, "COMMIT");

    $log->setFileName($filename_log);
    $log->setDateFinish(date("Y-m-d H:i:s"));
    $log->setStatus(1);
    $log->setStatusMessage("Finalizada com sucesso");
    $log->Update();
} catch(Exception $ex) {
    pg_query($con, "ROLLBACK");

    $logError = new \Posvenda\LogError();
    $logError->setRoutineScheduleLog($log->SelectId());
    $logError->setErrorMessage($ex->getMessage());
    $logError->setContents($ex->getMessage());
    $logError->Insert();

    $log->setFileName($filename_log);
    $log->setDateFinish(date("Y-m-d H:i:s"));
    $log->setStatus(0);
    $log->setStatusMessage($ex->getMessage());
    $log->Update();
}
