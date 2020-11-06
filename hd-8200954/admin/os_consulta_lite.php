<?php
$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../':'');

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "funcoes.php";
include_once '../class/sms/sms.class.php';
include __DIR__ . '/../class/ComunicatorMirror.php';
include __DIR__ . "/../class/communicator.class.php";


include_once '../class/AuditorLog.php';

$programa_insert = $_SERVER['PHP_SELF'];

require_once "../classes/excelwriter/excelwriter.inc.php";
use Posvenda\DistribuidorSLA;
$oDistribuidorSLA = new DistribuidorSLA();
$oDistribuidorSLA->setFabrica($login_fabrica);


if ($login_admin == 2286) {
  echo traduz("Programa em manutencao");
  exit;
}

if (!defined('CA_APP_PATH')) {
    $admin_privilegios = "call_center,gerencia";
    include "autentica_admin.php";
}

if ($usaNovaTelaConsultaOs) {
    header("Location: consulta_lite_new.php");
}
// SMS
if (SMS::getFabricasSms($login_fabrica)) {
    $sms = new SMS();
}

if ($login_fabrica == 117) {
    include('carrega_macro_familia.php');
}


if ($_POST["ajax_reabrir_os"]) {
    try {
        $os      = $_POST["os"];
        $mesagem = trim($_POST["mensagem"]);

        if (empty($os)) {
            throw new Exception(traduz("Erro ao reabrir: Ordem de Serviço não encontrada"));
        }

        if (empty($mensagem)) {
            throw new Exception(traduz("Erro ao reabrir: Motivo não informado"));
        }

        $sql = "
            SELECT sua_os, posto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}
        ";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception(trauz("Erro ao reabrir: Ordem de Serviço não encontrada"));
        }

        $sua_os = pg_fetch_result($res, 0, "sua_os");
        $posto  = pg_fetch_result($res, 0, "posto");

        pg_query($con, "BEGIN");

        $transaction = true;

	if(in_array($login_fabrica,array(30,91))){
		$campos_reabrir_os = "data_fechamento = null, finalizada = null, ";
	}

        if($login_fabrica == 30){
            $sqlReabrir = "UPDATE tbl_os_extra SET
                            obs_fechamento = ''
                            WHERE i_fabrica = {$login_fabrica}
                            AND os = {$os}";            

            $resReabrir = pg_query($con, $sqlReabrir);
        }

        $sql = "
            UPDATE tbl_os SET
                {$campos_reabrir_os}
                excluida = false,
                status_checkpoint = 1
            WHERE fabrica = {$login_fabrica}
            AND os = {$os}
        ";

        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception(traduz("Erro ao reabrir ordem de serviço"));
        }

        $sql = "
            INSERT INTO tbl_os_interacao
            (os, data, admin, comentario, interno, fabrica)
            VALUES
            ({$os}, CURRENT_TIMESTAMP, {$login_admin}, E'".traduz("Ordem de Serviço reaberta pela fábrica").": {$mensagem}', false, $login_fabrica)
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception(traduz("Erro ao reabrir ordem de serviço"));
        }
 
        $sql = "
            INSERT INTO tbl_comunicado
            (mensagem, descricao, tipo, fabrica, obrigatorio_site, posto, ativo)
            VALUES
            (E'{$mensagem}', '".traduz("Ordem de Serviço reaberta")." - $sua_os', 'Comunicado', $login_fabrica, true, $posto, true)
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception(traduz("Erro ao reabrir ordem de serviço"));
        }

        if (in_array($login_fabrica, [178])) {
            $sql = "SELECT os_revenda FROM tbl_os_campo_extra WHERE os = {$os} AND fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);

            if (!pg_num_rows($res) > 0) {
                throw new Exception("Ordem de serviço não encontrada");
            }

            if (!strlen(pg_last_error()) > 0) {
                $os_revenda = pg_fetch_result($res, 0, 'os_revenda');

                $sql2 = "SELECT * FROM tbl_os_revenda WHERE os_revenda = {$os_revenda} AND fabrica = {$login_fabrica}";
                $res2 = pg_query($con, $sql2);

                if (!pg_num_rows($res2) > 0) {
                    throw new Exception("Ordem de serviço não encontrada");
                }

                if (!strlen(pg_last_error()) > 0) { 
                    $update = "UPDATE tbl_os_revenda SET excluida = NULL, data_fechamento = NULL, finalizada = NULL WHERE os_revenda = {$os_revenda} AND fabrica = {$login_fabrica}";
                    $res3   = pg_query($con, $update);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Ordem de serviço não encontrada");       
                    }
                }
            }
        }

        pg_query($con, "COMMIT");

        exit(json_encode(array("sucesso" => true)));
    } catch (Exception $e) {
        if ($transaction) {
            pg_query($con, "ROLLBACK");
        }

        exit(json_encode(array("erro" => traduz(utf8_encode($e->getMessage())))));
    }
}

if ($_POST['fechar']) {

    $os_fechar = $_POST['fechar'];

    $msg_erro = "";
    $res = pg_query ($con,"BEGIN TRANSACTION");

    $sql = "SELECT status_os
            FROM tbl_os_status
            WHERE os = $os_fechar
            AND status_os IN (62,64,65,72,73,87,81,88,116,117)
            ORDER BY data DESC
            LIMIT 1";
    $res = pg_query ($con,$sql);
    if (pg_num_rows($res)>0){
        $status_os = trim(pg_fetch_result($res,0,status_os));
        if ($status_os=="72" || $status_os=="62" || $status_os=="87" || $status_os=="116"){
            $msg_erro .= traduz("OS está em intervenção, não pode ser fechada");
        }
    }

    if (empty($msg_erro)) {
        $sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $os_fechar AND fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);
        $msg_erro .= pg_errormessage($con) ;
    }

    if (empty($msg_erro)) {
        $sql = "SELECT fn_finaliza_os($os_fechar, $login_fabrica)";
        $res = pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);
    }

    if (empty($msg_erro)) {
        $res = pg_query ($con,"COMMIT TRANSACTION");
        echo "ok";
        exit();
    }else{
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
        echo "Erro no fechamento da OS";
        exit();
    }
}

if ($_POST["ajax_cancelar_os"]) {
    try {
        $os        = $_POST["os"];
        $mesagem   = trim($_POST["mensagem"]);
        $continuar = $_POST["continuar"];

        if ($continuar == "false") {
            $continuar = false;
        }

        if (empty($os)) {
            throw new Exception(traduz("Erro ao cancelar: Ordem de Serviço não encontrada"));
        }

        if (empty($mensagem)) {
            throw new Exception(traduz("Erro ao cancelar: Motivo não informado"));
        }

        $sql = "
            SELECT sua_os, posto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}
        ";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception(traduz("Erro ao cancelar: Ordem de Serviço não encontrada"));
        }

        $sua_os = pg_fetch_result($res, 0, "sua_os");
        $posto  = pg_fetch_result($res, 0, "posto");

        if (!$continuar) {
            $sql = "
                SELECT *
                FROM tbl_os_item
                INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tbl_pedido.fabrica = {$login_fabrica}
                WHERE tbl_os_produto.os = {$os}
                AND (tbl_pedido_item.qtde - (COALESCE(tbl_pedido_item.qtde_faturada, 0) + COALESCE(tbl_pedido_item.qtde_cancelada, 0))) > 0
                AND tbl_pedido.status_pedido NOT IN(1)
            ";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                exit(json_encode(array("continuar" => utf8_encode(traduz("A OS possui peças aguardando a emissão de nota fiscal, também é necessário o cancelamento do pedido da peça no ERP para evitar futuros erros na emissão de nota fiscal, deseja continuar com o cancelamento ?")))));
            }
        }

        pg_query($con, "BEGIN");

        $transaction = true;

        $sql = "
            UPDATE tbl_os SET
                excluida = TRUE,
				status_checkpoint = 28,
				admin_excluida = $login_admin
            WHERE fabrica = {$login_fabrica}
            AND os = {$os}
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception(traduz("Erro ao cancelar ordem de serviço"));
        }

        $sql = "
            INSERT INTO tbl_os_interacao
            (os, data, admin, comentario, interno, fabrica)
            VALUES
            ({$os}, CURRENT_TIMESTAMP, {$login_admin}, E'".traduz("Ordem de Serviço cancelada pela fábrica").": {$mensagem}', false, $login_fabrica)
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception(traduz("Erro ao cancelar ordem de serviço"));
        }

        $sql = "
            INSERT INTO tbl_comunicado
            (mensagem, descricao, tipo, fabrica, obrigatorio_site, posto, ativo)
            VALUES
            (E'{$mensagem}', '".traduz("Ordem de Serviço cancelada")." - $sua_os', 'Comunicado', $login_fabrica, true, $posto, true)
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception(traduz("Erro ao cancelar ordem de serviço"));
        }

        $sql = "
            UPDATE tbl_auditoria_os SET
                cancelada = CURRENT_TIMESTAMP,
                admin = {$login_admin},
                justificativa = '".traduz('Ordem de Serviço cancelada')."'
            WHERE os = {$os}
            AND liberada IS NULL AND reprovada IS NULL AND cancelada IS NULL
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception(traduz("Erro ao cancelar ordem de serviço"));
        }

        $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = $login_fabrica AND LOWER(descricao) = 'cancelado'";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception(traduz("Erro ao cancelar ordem de serviço"));
        }

        $servico_realizado = pg_fetch_result($res, 0, "servico_realizado");

        $sql = "
            SELECT oi.os_item, oi.pedido_item, pi.pedido, (pi.qtde - (COALESCE(pi.qtde_faturada, 0) + COALESCE(pi.qtde_cancelada, 0))) AS qtde_pendente
            FROM tbl_os_item oi
            INNER JOIN tbl_os_produto op ON op.os_produto = oi.os_produto
            LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
            WHERE op.os = {$os}
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            foreach (pg_fetch_all($res) as $i => $row) {
                $row = (object) $row;

                if (!empty($row->pedido_item) && $row->qtde_pendente == 0) {
                    continue;
                }

                $update = "
                    UPDATE tbl_os_item SET
                        servico_realizado = {$servico_realizado}
                    WHERE os_item = {$row->os_item}
                ";
                $resUpdate = pg_query($update);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception(traduz("Erro ao cancelar ordem de serviço"));
                }

                if (!empty($row->pedido_item) && $row->qtde_pendente > 0) {
                    $update = "
                        UPDATE tbl_pedido_item SET
                            qtde_cancelada = {$row->qtde_pendente}
                        WHERE pedido_item = {$row->pedido_item};

                        SELECT fn_atualiza_status_pedido({$login_fabrica}, {$row->pedido});
                    ";
                    $resUpdate = pg_query($con, $update);

                     if (strlen(pg_last_error()) > 0) {
                        throw new Exception(traduz("Erro ao cancelar ordem de serviço"));
                    }
                }
            }
        }

        if ($usaMobile) {
            $cockpit = new \Posvenda\Cockpit($login_fabrica);
            $cockpit->cancelaOsMobile($os, $con);
        }

        pg_query($con, "COMMIT");

        if ($login_fabrica == 178){
            $sql_os_revenda = "SELECT os_revenda FROM tbl_os_campo_extra WHERE os = {$os} AND fabrica = {$login_fabrica}";
            $res_os_revenda = pg_query($con, $sql_os_revenda);

            if (pg_num_rows($res_os_revenda) > 0){
                $os_revenda = pg_fetch_result($res_os_revenda, 0, "os_revenda");
                
                $sql = "
                    SELECT tbl_os_campo_extra.os_revenda
                    FROM tbl_os_campo_extra 
                    JOIN tbl_os ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os.fabrica = {$login_fabrica}
                    WHERE tbl_os_campo_extra.os_revenda = {$os_revenda}
                    AND tbl_os_campo_extra.fabrica = {$login_fabrica}
                    AND tbl_os.excluida IS NOT TRUE
                    GROUP BY tbl_os_campo_extra.os_revenda";
                $res = pg_query($con, $sql);
                
                if (pg_num_rows($res) == 0){
                    if (file_exists("../classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
                        include_once "../classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
                        $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
                        $classOs = new $className($login_fabrica, $os);

                        $classOs->cancelaOs($con, $os_revenda, $mesagem);
                    }
                }        
            }
        }

        exit(json_encode(array("sucesso" => true)));
    } catch (Exception $e) {
        if ($transaction) {
            pg_query($con, "ROLLBACK");
        }

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if (isset($_POST["verifica_pedido_os"]) && $login_fabrica == 1) {

    $os = $_POST["os"];
    $dados = array();

    $sql = "SELECT  tbl_os_item.os_item
            FROM    tbl_os_item
            JOIN    tbl_os_produto          ON  tbl_os_produto.os_produto   = tbl_os_item.os_produto
                                            AND tbl_os_produto.os           = $os
            JOIN    tbl_os                  ON  tbl_os.os                   = tbl_os_produto.os
                                            AND tbl_os.os                   = $os
            JOIN    tbl_pedido              ON  tbl_pedido.pedido           = tbl_os_item.pedido
            JOIN    tbl_pedido_item         ON  tbl_pedido_item.pedido      = tbl_pedido.pedido
                                            AND tbl_pedido_item.peca        = tbl_os_item.peca
            JOIN    tbl_peca                ON  tbl_peca.peca               = tbl_os_item.peca
            WHERE   tbl_os_item.fabrica_i = {$login_fabrica}
            AND     (
                        SELECT  COUNT(tbl_os_item_nf.os_item)
                        FROM    tbl_os_item_nf
                        WHERE   tbl_os_item_nf.os_item = tbl_os_item.os_item
                    ) = 0
            AND     (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) > 0
            AND     tbl_pedido.pedido NOT IN (
                        SELECT  tbl_pedido_cancelado.pedido
                        FROM    tbl_pedido_cancelado
                        WHERE   tbl_pedido_cancelado.pedido         = tbl_pedido.pedido
                        AND     tbl_pedido_cancelado.pedido_item    = tbl_pedido_item.pedido_item
                        AND     tbl_pedido_cancelado.qtde           = tbl_pedido_item.qtde
                    )
    ";

    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

        $count = pg_num_rows($res);

        $pedido_nao_faturado = 0;

        for($i = 0; $i < $count; $i++){

            $os_item = pg_fetch_result($res, $i, "os_item");

            $sql_nf_item = "SELECT os_item_nf FROM tbl_os_item_nf WHERE os_item = {$os_item}";
            $res_nf_item = pg_query($con, $sql_nf_item);

            if(pg_num_rows($res_nf_item) == 0){
                $pedido_nao_faturado++;
            }

        }

        $dados["status"] = ($pedido_nao_faturado > 0) ? false : true;

    }else{
        $dados = array("status" => true);
    }

    exit(json_encode($dados));

}

function convertData($data){
    $data = explode(" ",$data);
    list($dia, $mes, $ano) = explode("/", $data[0]);
    return $ano."-".$mes."-".$dia;
}

if($_GET['finalizar_os_trom'] == 'true'){
    $id_os           = $_GET['id_os'];
    $motivo          = $_GET['motivo'];
    $data_conserto   = $_GET['data_conserto'];
    $data_inicio     = $_GET['data_inicio'];
    $programa_insert = $_SERVER['PHP_SELF'];

    try {
	if (file_exists("classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
		include_once "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
        $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
        $classOs   = new $className($login_fabrica, $id_os);
	} else {
		$classOs = new \Posvenda\Os($login_fabrica, $id_os);
	}

	$classOs->calculaOs();

    $sql            = "select data_digitacao from tbl_os where os = $id_os";
    $res            = pg_query($con, $sql);
    $data_digitacao = pg_fetch_result($res, 0, 'data_digitacao');

	pg_query($con,"BEGIN");

	if ($login_fabrica == 158 && strlen($data_inicio) > 5 && strlen($data_conserto) > 5) {
        $dd = new DateTime($data_digitacao);
        $dd->setTimeZone(new DateTimeZone('America/Sao_Paulo'));
        $data_digitacao = $dd->format("Y-m-d H:i");

		list($data, $hora)     = explode(" ", $data_conserto);
		list($dia, $mes, $ano) = explode("/", $data);

		$data = "$ano-$mes-$dia $hora";

        if (strtotime($data) < strtotime($data_digitacao)) {
            $erro = traduz("O início do atendimento não pode ser inferior a data de abertura ");
        } else {

    		list($dataI, $horaI)     = explode(" ", $data_inicio);
    		list($diaI, $mesI, $anoI) = explode("/", $dataI);

    		$dataI = "$anoI-$mesI-$diaI $horaI";


    		$sql = "UPDATE tbl_os
    			   SET data_conserto = '{$data}'
    			 WHERE os = {$id_os}
    			   AND fabrica = {$login_fabrica};";
    		$res  = pg_query($con, $sql);

    		if(strlen(pg_last_error()) > 0){
    			$erro = pg_last_error($con);
				if(strpos($erro, 'tbl_os_data_conserto_check') !== false) {
					$erro = traduz("Não pode colocar a data futura em data de conserto") ;
				}
    		}

			if(empty($erro)) {
				$sqlExt = "UPDATE tbl_os_extra
					SET  termino_atendimento = '{$data}', inicio_atendimento = '{$dataI}'
					WHERE os = {$id_os};";
					$resExt  = pg_query($con, $sqlExt);

					if(strlen(pg_last_error()) > 0){
						$erro = pg_last_error($con);
					}
			}
        }
	}

	if(strlen($erro) == 0){
		$classOs->finaliza($con,false,$login_admin);

		$sqlData = " INSERT INTO tbl_os_interacao (
			       os,
			       data,
			       admin,
			       comentario,
			       fabrica,
			       programa
			       ) VALUES (
				$id_os,
				CURRENT_TIMESTAMP,
				$login_admin,
				'OS finalizada: $motivo',
				$login_fabrica,
				'$programa_insert'
			    )";
		$resData = pg_query($con,$sqlData);

		if(strlen(pg_last_error()) > 0){
			$erro = pg_last_error($con);
		}
	}

    } catch(Exception $e) {
        $erro = $e->getMessage();
    }

    if(empty($erro)){
	pg_query($con,"COMMIT");
        $retorno = "ok";
    } else {
	pg_query($con,"ROLLBACK");
        $retorno = $erro;
    }
    echo $retorno;
    exit;
}

if($_POST['novo_fechar_os_cadence'] == 'true') {

    $aux_os  = $_POST["os"];

    try {
        if (file_exists("classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
            include_once "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
            $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
            $classOs = new $className($login_fabrica, $aux_os);
        } else {
            $classOs = new \Posvenda\Os($login_fabrica, $aux_os);
        }
        pg_query($con,"BEGIN");
            if (!empty($aux_os)) {
                $aux_sql = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP WHERE fabrica = $login_fabrica AND os = $aux_os";
                $aux_res = pg_query($con, $aux_sql);

                if (pg_last_error()) {
                    echo "KO|Erro ao fechar a O.S. $aux_os";
                } else {
                    $aux_sql = "SELECT os FROM tbl_os_campo_extra WHERE os = $aux_os LIMIT 1";
                    $aux_res = pg_query($con, $aux_sql);
                    $ver_os  = pg_fetch_result($aux_res, 0, 0);
                    if (empty($ver_os)) {
                        $aux_admin["admin_finaliza_os"] = $login_admin;
                        $aux_admin                      = json_encode($aux_admin);

                        $aux_sql = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($aux_os, $login_fabrica, '$aux_admin') RETURNING campos_adicionais";
                    } else {
                        $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $aux_os";
                        $aux_res = pg_query($con, $aux_sql);

                        $aux_admin = pg_fetch_result($aux_res, 0, 0);
                        $aux_admin = (array) json_decode($aux_admin);
                        $aux_admin["admin_finaliza_os"] = $login_admin;

                        $aux_admin = json_encode($aux_admin);

                        $aux_sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$aux_admin' WHERE fabrica = $login_fabrica AND os = $aux_os RETURNING campos_adicionais";
                    }
                    $aux_res           = pg_query($con, $aux_sql);
                    $campos_adicionais = pg_fetch_result($aux_res, 0, 0);

                    if (empty($campos_adicionais)) {
                        echo "KO|Erro ao registrar o admin responsável pelo fechamento da O.S. $os";
                        pg_query($con,"ROLLBACK");
                    } else {

                        $classOs->finaliza($con,false,$login_admin);

                        if(strlen(pg_last_error()) == 0){

                            if($login_fabrica == 35){
                                //Verifica se O.S tem atendimento callcenter e se o atendimento tem postagem
                                //se tiver não pode enviar sms
                                $enviarSMSCadence = true;

                                $sqlVerAtendimento = "SELECT tbl_hd_chamado_extra.os
                                                        FROM tbl_hd_chamado_extra
                                                        JOIN tbl_hd_chamado_postagem USING (hd_chamado)
                                                        WHERE tbl_hd_chamado_extra.os = {$aux_os}";
                                $resVerAtendimento = pg_query($con, $sqlVerAtendimento);

                                if(pg_num_rows($resVerAtendimento)>0){
                                    $enviarSMSCadence = false;
                                }

                                if($enviarSMSCadence == true){
                                    $sms->enviarMensagem($celular, $aux_os, '', $sms_msg);
                                    echo "OK|A O.S. $aux_os foi finalizada com sucesso!";
                                } else { 
                                    
                                    echo "SMS_NAO|A O.S. $aux_os foi finalizada com sucesso!";
                                }

                            }else{
                                $sms->enviarMensagem($celular, $aux_os, '', $sms_msg);
                                echo "OK|A O.S. $aux_os foi finalizada com sucesso!";
                            }

                            pg_query($con,"COMMIT");
                            
                            # disparo de email no fechamento com pesquisa de satisfação #
                            // HD-7717990
                            if ($login_fabrica == 35 && 1==2) {

                                $sql_os = "SELECT o.os, 
                                                o.sua_os, 
                                                o.consumidor_nome,
                                                o.data_fechamento data_finalizacao,
                                                o.consumidor_email, 
                                                pd.referencia, 
                                                pd.descricao, o.fabrica, 
                                                p.nome posto_autorizado, 
                                                hd.nome nome_consumidor_protocolo, 
                                                hd.produto nome_produto_protocolo
                                           FROM tbl_os o 
                                           JOIN tbl_os_produto op ON op.os = o.os 
                                           JOIN tbl_posto p ON p.posto = o.posto
                                           JOIN tbl_produto pd ON pd.produto = op.produto 
                                           LEFT JOIN tbl_hd_chamado_extra hd ON hd.os = o.os 
                                           WHERE o.os = $aux_os";
                                
                                $os = pg_query($con, $sql_os);
                                $os = pg_fetch_object($os);

                                $sql = "SELECT pf.formulario, pf.pesquisa_formulario, p.descricao AS titulo, p.categoria, p.pesquisa, p.texto_ajuda AS texto_email
                                    FROM tbl_pesquisa p
                                    LEFT JOIN tbl_pesquisa_formulario pf ON (pf.pesquisa = p.pesquisa)
                                    WHERE p.fabrica = {$os->fabrica} AND p.categoria = 'os_email' AND p.ativo = 't'";

                                $pesquisa = pg_query($con, $sql);

                                    if (pg_num_rows($pesquisa) > 0) {

                                        $pesquisa = pg_fetch_object($pesquisa);

                                        if (!empty($os->consumidor_email)) {

                                            $texto_email = $pesquisa->texto_email;

                                            if (preg_match('/\:os/', $texto_email)) {
                                                $texto_email = str_replace(':os', $os->sua_os, $texto_email);
                                            }

                                            if (preg_match('/\:finalizacao_os/', $texto_email)) {
                                                $texto_email = str_replace(':finalizacao_os', date("d/m/Y", strtotime($os->data_finalizacao)), $texto_email);
                                            }

                                            if (preg_match('/\:posto_autorizado/', $texto_email)) {
                                                $texto_email = str_replace(':posto_autorizado', $os->posto_autorizado, $texto_email);
                                            }
                                            if (preg_match('/\:nome_consumidor_os/', $texto_email)) {
                                                $texto_email = str_replace(':nome_consumidor_os', $os->consumidor_nome, $texto_email);
                                            }
                                            if (preg_match('/\:nome_consumidor_protocolo/', $texto_email)) {
                                                $texto_email = str_replace(':nome_consumidor_protocolo', "", $texto_email);
                                            }

                                            if (preg_match('/\:nome_produto_protocolo/', $texto_email)) {
                                                $texto_email = str_replace(':nome_produto_protocolo', "", $texto_email);
                                            }

                                            $token = sha1($os->fabrica . $os->os);

                                            if ($_serverEnvironment == 'development') {
                                                $url = "https://novodevel.telecontrol.com.br/~williamcastro/chamados/hd-6890195/externos/pesquisa_satisfacao_os_email.php?token={$token}&os={$os->os}&tipo=email";
                                            } else {
                                                $url = "https://posvenda.telecontrol.com.br/assist/externos/pesquisa_satisfacao_os_email.php?token={$token}&os={$os->os}&tipo=email";
                                            }
                                            
                                            $texto_email = str_replace(':link', "<a href='{$url}' target='_blank' >clique aqui</a>", $texto_email);

                                            $texto_email = str_replace("\n", '<br />', $texto_email);

                                            $email = $os->consumidor_email;
                                   
                                            $mailTc = new TcComm('cadence.telecontrol');
                                            
                                            try {

                                                $mailTc->sendMail(
                                                    $email,
                                                    utf8_encode($pesquisa->titulo),
                                                    utf8_encode($texto_email),
                                                    "pesquisa@jcsbrasil.com.br"
                                                );

                                                #exit(json_encode(array('success' => true)));

                                            } catch (\Exception $e) {

                                                throw new \Exception('Erro ao Enviar E-mail');
                                            }
                                        }
                                    }
                                }
                                # --------------------------------------------------- #
                        } else {
                            echo "KO|Erro ao fechar O.S. $os";
                        }
                    }
                }
            } else {
                echo "KO|Erro ao fechar a O.S. $aux_os";
            }
    } catch(Exception $e) {
        $erro = utf8_decode($e->getMessage());
        echo "KO|".$erro;
    }

    exit;
}

if (in_array($login_fabrica, array(35, 104))) {
    require_once '../class/sms/sms.class.php';
}

if($_POST['enviar_sms_os'] == 'true') {
    $aux_os  = $_POST["os"];
    $aux_sql = "SELECT consumidor_celular, consumidor_nome FROM tbl_os WHERE fabrica = $login_fabrica AND os = $aux_os";
    $aux_res = pg_query($con, $aux_sql);
    $celular = pg_fetch_result($aux_res, 0, 'consumidor_celular');

    if (empty($celular)) {
        echo "KO|".traduz("A O.S. % não possui um celular vinculado ao consumidor", null, null, [$os]);
    } else {
        $sms     = new SMS();
        $sms_con = " ".pg_fetch_result($aux_res, 0, 'consumidor_nome');

        if ($login_fabrica == 35){
            $sms_msg = traduz("Olá! Seu produto Cadence / Oster já está disponível para retirada na Assistência Técnica. Dúvidas: 0800 644 644 2.");
        }else{
            $sms_msg = traduz("PREZADO(A) %, O SEU PRODUTO REFERENTE A ORDEM DE SERVIÇO NÚMERO % JÁ FOI REPARADO E ESTÁ DISPONÍVEL PARA RETIRADA NO POSTO AUTORIZADO.", null, null, [strtoupper($sms_con), $aux_os]);
        }
        $sms->enviarMensagem($celular, $aux_os, '', $sms_msg);

        echo "OK|".traduz("SMS enviado com sucesso!");
    }

    exit;
}

if($_POST['ajax'] == "reverter_os"){

    $os = $_POST['os'];
    $acao = $_POST['acao'];
    $motivo = $_POST['motivo'];

    $res = pg_query($con,"BEGIN TRANSACTION");

    $sql = "
        UPDATE  tbl_os
        SET     cancelada = false 
        WHERE   os = $os
    ";
    $res = pg_query($con,$sql);

    if(pg_last_error($con)){
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        echo json_encode(array("erro"=>"ok"));
    }else{
        if($motivo != ""){
            $sql = "INSERT INTO tbl_os_interacao
                    (programa,fabrica, os, admin, comentario, interno, exigir_resposta)
                    VALUES
                    ('$programa_insert',$login_fabrica, $os, $login_admin, '$text de OS. Motivo: $motivo', TRUE, FALSE)";
            $res = pg_query($con,$sql);
        }

        //Registrar log 
        $AuditorLog = new AuditorLog;
        $dados = 'OS revertida em '.date("d-m-Y H:i").' por '.$login_login. " Motivo: $motivo" ;
        $PrimaryKey = $login_fabrica . '*' . $os;
        $Table = 'tbl_os';
        $AuditorLog->enviarLog("INSERT", $Table, $PrimaryKey, 'os_consulta_lite.php', $dados);

        $res = pg_query($con,"COMMIT TRANSACTION");
        echo json_encode(array("result"=>"ok"));
    }

    exit; 
}


if($_POST['ajax'] == "cancelar_os"){
    $os = $_POST['os'];
    $acao = $_POST['acao'];
    $motivo = $_POST['motivo'];

    $res = pg_query($con,"BEGIN TRANSACTION");
    if($acao == "liberar" || $acao == "reabrir"){
		$text = "Liberação";
		$sql = "INSERT INTO tbl_os_status (
                    os         ,
                    observacao ,
                    status_os  ,
                    admin
                ) VALUES (
                    $os,
                    '$motivo' ,
                    17       ,
                    $login_admin
                );";
        $res = pg_query ($con,$sql);

        if ($acao == "reabrir") {
            $sql = "
                UPDATE  tbl_os SET
                    finalizada = null,
                    data_fechamento = null
                WHERE   os = $os
            ";
        }else{
            $sql = "
                UPDATE  tbl_os
                SET     excluida = FALSE
                WHERE   os = $os
            ";
        }
        $res = pg_query($con,$sql);
    
        if (in_array($login_fabrica, [178])) {
            $sql = "SELECT os_revenda FROM tbl_os_campo_extra WHERE os = {$os} AND fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);

            if (!strlen(pg_last_error()) > 0) {
                $os_revenda = pg_fetch_result($res, 0, 'os_revenda');

                $sql2 = "SELECT * FROM tbl_os_revenda WHERE os_revenda = {$os_revenda} AND fabrica = {$login_fabrica}";
                $res2 = pg_query($con, $sql2);

                if (!strlen(pg_last_error()) > 0) { 
                    $update = "UPDATE tbl_os_revenda SET excluida = NULL, data_fechamento = NULL, finalizada = NULL WHERE os_revenda = {$os_revenda} AND fabrica = {$login_fabrica}";
                    $res3   = pg_query($con, $update);
                }
            }
        }

        //Registrar log 
        $AuditorLog = new AuditorLog;
        $dados = 'OS reaberta em '.date("d-m-Y H:i").' por '.$login_login. " Motivo: $motivo ";
        $PrimaryKey = $login_fabrica . '*' . $os;
        $Table = 'tbl_os';
        $AuditorLog->enviarLog("INSERT", $Table, $PrimaryKey, 'os_consulta_lite.php', $dados);

    }else{
        if($login_fabrica == 30){
            $status_os = 15;
            $sql_excluida = " excluida = TRUE ";
        }elseif(in_array($login_fabrica, array(72,74))){
            $status_os = 156;
            $sql_excluida = " cancelada = TRUE";
            //, status_checkpoint = 28
        }

        if($login_fabrica == 72){
            $status_checkpoint = ' , status_checkpoint = 28 ';
        }

        $sql = "INSERT INTO tbl_os_status (
                    os         ,
                    observacao ,
                    status_os  ,
                    admin
                ) VALUES (
                    $os,
                    '$motivo' ,
                    $status_os   ,
                    $login_admin
                );";
        $res = pg_query ($con,$sql);

        $text = "Cancelamento";
        $sql = "
            UPDATE  tbl_os
            SET     $sql_excluida
            $status_checkpoint
            WHERE   os = $os
        ";
        $res = pg_query($con,$sql);
    }

    if(pg_last_error($con)){
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        echo "erro";
    }else{
        if($motivo != ""){

            if($login_fabrica == 35){
                $mensagem = $dados; 
            }else{
                $mensagem = "$text de OS. Motivo: $motivo"; 
            }


            $sql = "INSERT INTO tbl_os_interacao
                    (programa,fabrica, os, admin, comentario, interno, exigir_resposta)
                    VALUES
                    ('$programa_insert',$login_fabrica, $os, $login_admin, '$mensagem', TRUE, FALSE)";
            $res = pg_query($con,$sql);
        }

        if(in_array($login_fabrica, array(72,74))){
            $sql = "UPDATE tbl_os set finalizada = now(), data_fechamento = now() WHERE os = $os";
            $res = pg_query($con, $sql);
        }

        if(in_array($login_fabrica, array(72))){
            $sql = "UPDATE tbl_os set  status_checkpoint = 28  WHERE os = $os";
            $res = pg_query($con, $sql);
        }


        if (in_array($login_fabrica, array(30))) {
            $sql = "SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE os = {$os};";

            $res = pg_query($con,$sql);
            if (pg_num_rows($res) > 0) {
                $hd_chamado_deletar = pg_fetch_result($res, 0, "hd_chamado");
                $sql = "UPDATE tbl_hd_chamado_extra SET os = null WHERE hd_chamado = {$hd_chamado_deletar}";
                $res = pg_query($con,$sql);
                if(pg_last_error($con)){
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                    echo "erro";
                    exit;
                }else{
                    $sql = "INSERT INTO tbl_hd_chamado_item(
                                hd_chamado   ,
                                data         ,
                                comentario   ,
                                admin        ,
                                interno      ,
                                status_item
                            )VALUES(
                                $hd_chamado_deletar ,
                                current_timestamp ,
                                'Foi cancelado a OS {$os}, portanto desvinculado deste atendimento.',
                                $login_admin ,
                                't'  ,
                                'Aberto'
                            )";
                    $res = pg_query($con,$sql);
                    if(pg_last_error($con)){
                        $res = pg_query($con,"ROLLBACK TRANSACTION");
                        echo "erro";
                        exit;
                    }
                }
            }
        }

        $res = pg_query($con,"COMMIT TRANSACTION");
        echo json_encode(array("result"=>"ok"));
    }
    exit;
}

if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    $zerar_mo = filter_input(INPUT_POST,"zerar_mo");
    $motivo = filter_input(INPUT_POST,"motivo");

    $res = pg_query($con,"BEGIN TRANSACTION");

    $mensagem_motivo = traduz("Cancelamento de mão-de-obra. Motivo: ")."$motivo";

    if ($login_fabrica == 101) {
        $mensagem_motivo = traduz("Cancelamento de mão-de-obra.");
    }

    $sql = "
        INSERT INTO tbl_os_status (
            os,
            status_os,
            observacao,
            admin
        ) VALUES (
            $zerar_mo,
            81,
            '$mensagem_motivo',
            $login_admin
        );
    ";
    $res = pg_query($con,$sql);

    $sql2 = "
        UPDATE  tbl_os
        SET     mao_de_obra = '0.00'
        WHERE   os = $zerar_mo
    ";
    $res2 = pg_query($con,$sql2);

    if (pg_last_error($con)) {
        $erro = pg_last_error($con);
        pg_query($con,"ROLLBACK TRANSACTION");
        echo "Erro: ".$sql;
        exit;
    }

    if ($login_fabrica == 101) {
        $sqlOsExtra = "SELECT extrato FROM tbl_os_extra WHERE os={$zerar_mo}";
        $resOsExtra = pg_query($con, $sqlOsExtra);

        if (pg_num_rows($resOsExtra) > 0) {
            $extrato = pg_fetch_result($resOsExtra, 0, extrato);

            if (strlen($extrato) > 0) {
                $sqlExtrato = "SELECT fn_calcula_extrato($login_fabrica,$extrato);";
                $resExtrato = pg_query ($con, $sqlExtrato);

                if (pg_last_error($con)) {
                    $erro = pg_last_error($con);
                    pg_query($con,"ROLLBACK TRANSACTION");
                    echo "Erro: ".$sql;
                    exit;
                }

            }

        }
    }

    pg_query($con,"COMMIT TRANSACTION");
    echo json_encode(array("ok"=>true));
    exit;
}

if (isset($_POST["acao_exclui_os"]) && $_POST["acao_exclui_os"] == "t") {

    $os_array         = $_POST["exclui_os"];
    $motivo_exclui_os = trim($_POST["motivo_exclui_os"]);
    $msg_ok_excluir = array();

    if (!count($os_array)) {
        $msg_erro_excluir = traduz("Nenhuma ordem de serviço selecionada para excluir");
    } else if (!strlen($motivo_exclui_os)) {
        $msg_erro_excluir = traduz("Informe o motivo para excluir");
    } else {
        foreach ($os_array as $key => $os) {
            if (tem_pedido_os($os)) {

                $sql = "SELECT sua_os,hd_chamado FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {

                    $sua_os             = pg_fetch_result($res, 0, "sua_os");
                    $hd_chamado_excluir = pg_fetch_result($res, 0, "hd_chamado");

                    pg_query($con, "BEGIN");

                    $sql = "INSERT INTO tbl_os_status (os,status_os,observacao,admin)
                              VALUES ($os, 15, '$motivo_exclui_os', $login_admin)";
                    $res = pg_query($con, $sql);

                    if(strlen(pg_last_error()) > 0){
                        $msg_erro_excluir .= "{$sua_os} ".pg_last_error()."<br />";
                        pg_query($con, "ROLLBACK" );
                    }else{
                        $sql = "SELECT fn_os_excluida({$os}, {$login_fabrica}, {$login_admin})";
                        $res = pg_query($con,$sql);

                        $sql_m = "UPDATE tbl_os_excluida SET motivo_exclusao = '$motivo_exclui_os' WHERE os = $os AND fabrica = $login_fabrica";
                        $res_m = pg_query($con, $sql_m);

                        if ($login_fabrica == 156 && !empty($hd_chamado_excluir)) {

                            /*
                             * - ELGIN AUTOMAÇÃO:
                             * Ao excluir uma OS que esteja vinculada a um chamado,
                             * retirar para que não seja necessária abertura de novo
                             * chamado
                             */

                            $sqlUpHd = "
                                UPDATE  tbl_os
                                SET     hd_chamado = NULL
                                WHERE   os          = $os
                                AND     hd_chamado  = $hd_chamado_excluir;

                                UPDATE  tbl_hd_chamado_extra
                                SET     os      = NULL,
                                        abre_os = NULL
                                WHERE   hd_chamado  = $hd_chamado_excluir
                                AND     os          = $os
                            ";
                            $resUpHd = pg_query($con,$sqlUpHd);

                            if (!pg_last_error($con)) {

                                /*
                                 * - Gravação de interação no chamado
                                 * reportando a exclusão da OS anteriormente
                                 * vinculada
                                 */

                                $sqlInsJustOs = "
                                    INSERT INTO tbl_hd_chamado_item (
                                        hd_chamado,
                                        comentario,
                                        admin,
                                        interno
                                    ) VALUES (
                                        $hd_chamado_excluir,
                                        E'A OS <b>$sua_os</b> ".traduz("foi excluída e, por esse motivo, desvinculada desse atendimento").". <br> Motivo: $motivo_exclui_os',
                                        $login_admin,
                                        TRUE
                                    )
                                ";
                                $resInsJustOs = pg_query($con,$sqlInsJustOs);
                            }
                        }

                        if(strlen(pg_last_error()) > 0){
                            $msg_erro_excluir .= "{$sua_os} ".pg_last_error()."<br />";
                            pg_query($con, "ROLLBACK" );
                        }else{
                            $msg_ok_excluir[] = $sua_os;
                            pg_query($con, "COMMIT" );
                        }

                    }
                    //insert na tbl_os_status
                    //se der erro, dar rollback falar que a os atual deu erro e ir para a proxima os

                    //se não deu nenhum erro chamar a função do banco SELECT fn_os_excluida({$os}, {$login_fabrica}, {$login_admin})
                    //se deu algum erro na função, falar que a os atual deu erro, dar rollback e ir para a proxima

                    /*
                     * comando de rollback pg_query($con, "ROLLBACK");
                     * se não deu nenhum erro realizar commit para salvar alteração pg_query($con, "COMMIT");
                     * para ir para a proximo item do loop em caso de erro use o comando continue;
                     */
                } else {
                    $msg_erro_excluir .= traduz("Ordem de Serviço {$os} não encontrada<br />");
                }
            } else {
                $msg_erro_excluir .= traduz("OS {$os} possui pedido e não pode ser excluida <br />");
            }
        }
    }
}

if (isset($_POST["post_anterior"]) && isset($_POST["acao_exclui_os"])) {
    $_POST = array_merge($_POST, json_decode(str_replace("\\\"", "\"", $_POST["post_anterior"]), true));

    unset($_POST["post_anterior"]);
}

//  Define alguns comportamentos do programa
$fabrica_copia_os_excluida      = in_array($login_fabrica, array(52,81,114,30,122,128,148,158));

//  Além desta variável, precisa definir abaixo qual dos dois tipos de relatório
//  será gerado para o admin
$fabrica_baixa_relatorio_os     = in_array($login_fabrica, array(15, 42, 52, 81, 85, 114, 30,72));
$fabrica_autoriza_troca_revenda = in_array($login_fabrica, array(81,114));
$fabrica_autoriza_ressarcimento = ($novaTelaOs) ? true : in_array($login_fabrica, array(81,114));
$mostra_data_conserto           = in_array($login_fabrica, array(3,11,14,15,43,45,66,80,153,165,172));

if (in_array($login_fabrica, array(101,141,144)) && $_POST["solicitaTroca"] == true) {
    $os = $_POST["os"];

    if (strlen($os) > 0) {
        $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            $retorno = array("erro" => utf8_encode("OS não encontrada"));
        } else {
            $sql = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND status_os IN(192,193,194) ORDER BY data DESC LIMIT 1";
            $res = pg_query($con, $sql);
            $rows = pg_num_rows($res);

            if ($rows > 0) {
                $status_os_troca_produto = pg_fetch_result($res, 0, "status_os");
            }

            if ($rows > 0 && in_array($status_os_troca_produto, array(192,193)) && !in_array($login_fabrica, array(101))) {
                switch ($status_os_troca_produto) {
                    case 192:
                        $retorno = array("erro" => utf8_encode(traduz("Já foi solicitado a troca de produto dessa OS")));
                        break;

                    case 193:
                        $retorno = array("erro" => utf8_encode(traduz("OS já teve a troca de produto efetuada")));
                        break;
                }
            } else {
                $insert = "INSERT INTO tbl_os_status
                                (os, status_os, observacao)
                                VALUES
                                ({$os}, 192, 'OS com troca de produto em auditoria')";
                $res = pg_query($con, $insert);

                if (strlen(pg_last_error()) > 0) {
                    $retorno = array("erro" => utf8_encode(traduz("Erro ao solicitar troca de produto")));
                } else {
                    $retorno = array("ok" => utf8_encode(traduz("Foi solicitada a troca de produto para a OS")));
                }
            }
        }
    } else {
        $retorno = array("erro" => utf8_encode(traduz("OS não informada")));
    }

    exit(json_encode($retorno));
}


if(in_array($login_fabrica,array(85)) && isset($_POST['abrir_atendimento']) && $_POST['abrir_atendimento'] == "ok"){

    $os = $_POST['os'];

    $sql_dados_os = "
        SELECT
            tbl_os.sua_os,
            tbl_os.posto,
            tbl_os.data_abertura,
            tbl_os.data_nf,
            tbl_os.consumidor_nome,
            tbl_os.consumidor_cpf,
            tbl_os.consumidor_endereco,
            tbl_os.consumidor_numero,
            tbl_os.consumidor_cep,
            tbl_os.consumidor_complemento,
            tbl_os.consumidor_bairro,
            tbl_os.consumidor_cidade,
            tbl_os.consumidor_estado,
            tbl_os.consumidor_fone,
            tbl_os.revenda_cnpj,
            tbl_os.revenda_nome,
            tbl_os.revenda_fone,
            tbl_os.produto,
            replace(tbl_os.serie, '&#8203', '') as serie,
            tbl_os.defeito_reclamado_descricao,
            tbl_os.revenda,
            tbl_os.consumidor_revenda,
            tbl_os.nota_fiscal,
            tbl_os.tipo_atendimento,
            tbl_os.cod_ibge,
            regexp_replace(tbl_os.obs,'\\s+',' ', 'g') as obs,
            tbl_posto.nome AS posto_nome,
            tbl_posto_fabrica.codigo_posto
            FROM tbl_os
            JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            WHERE os  = {$os}
    ";
    $res_dados_os = pg_query($con, $sql_dados_os);

    if(pg_num_rows($res_dados_os) > 0){

        if (!function_exists('tira_acentos')) {
            function tira_acentos ($texto) {
                $acentos = array(
                    "com" => "áâàãäéêèëíîìïóôòõúùüçñÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇÑ",
                    "sem" => "aaaaaeeeeiiiioooouuucnAAAAAEEEEIIIIOOOOUUUCn"
                );
                return strtr($texto,$acentos['com'], $acentos['sem']);
            }
        }

        $sua_os                      = pg_fetch_result($res_dados_os, 0, 'sua_os');
        $posto                       = pg_fetch_result($res_dados_os, 0, 'posto');
        $data_abertura               = pg_fetch_result($res_dados_os, 0, 'data_abertura');
        $data_nf                     = pg_fetch_result($res_dados_os, 0, 'data_nf');
        $consumidor_nome             = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos(pg_fetch_result($res_dados_os, 0, 'consumidor_nome')));
        $consumidor_cpf              = pg_fetch_result($res_dados_os, 0, 'consumidor_cpf');
        $consumidor_endereco         = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos(pg_fetch_result($res_dados_os, 0, 'consumidor_endereco')));
        $consumidor_endereco         = str_replace('\\','' ,$consumidor_endereco);
        $consumidor_numero           = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos(pg_fetch_result($res_dados_os, 0, 'consumidor_numero')));
        $consumidor_cep              = pg_fetch_result($res_dados_os, 0, 'consumidor_cep');
        $consumidor_complemento      = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos(pg_fetch_result($res_dados_os, 0, 'consumidor_complemento')));
        $consumidor_bairro           = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos(pg_fetch_result($res_dados_os, 0, 'consumidor_bairro')));
        $consumidor_cidade           = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos(pg_fetch_result($res_dados_os, 1, 'consumidor_cidade')));
        $consumidor_estado           = pg_fetch_result($res_dados_os, 0, 'consumidor_estado');
        $consumidor_fone             = pg_fetch_result($res_dados_os, 0, 'consumidor_fone');
        $consumidor_email            = pg_fetch_result($res_dados_os, 0, 'consumidor_email');
        $revenda_cnpj                = pg_fetch_result($res_dados_os, 0, 'revenda_cnpj');
        $revenda_nome                = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos(pg_fetch_result($res_dados_os, 0, 'revenda_nome')));
        $revenda_fone                = pg_fetch_result($res_dados_os, 0, 'revenda_fone');
        $produto                     = pg_fetch_result($res_dados_os, 0, 'produto');
        $serie                       = pg_fetch_result($res_dados_os, 0, 'serie');
        $defeito_reclamado_descricao = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos(pg_fetch_result($res_dados_os, 0, 'defeito_reclamado_descricao')));
        $revenda                     = pg_fetch_result($res_dados_os, 0, 'revenda');
        $consumidor_revenda          = pg_fetch_result($res_dados_os, 0, 'consumidor_revenda');
        $nota_fiscal                 = pg_fetch_result($res_dados_os, 0, 'nota_fiscal');
        $tipo_atendimento            = pg_fetch_result($res_dados_os, 0, 'tipo_atendimento');
        $cod_ibge                    = pg_fetch_result($res_dados_os, 0, 'cod_ibge');
        $obs                         = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos(pg_fetch_result($res_dados_os, 0, 'obs')));
        $posto_nome                  = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos(pg_fetch_result($res_dados_os, 0, 'posto_nome')));
        $codigo_posto                = pg_fetch_result($res_dados_os, 0, 'codigo_posto');

        if(!empty($cod_ibge)){
            $sql_cidade = "SELECT cidade
                           FROM tbl_cidade
                           WHERE UPPER(fn_retira_especiais(nome)) = (SELECT UPPER(fn_retira_especiais(cidade)) FROM tbl_ibge WHERE cod_ibge = $cod_ibge) AND UPPER(estado) = (SELECT UPPER(estado) FROM tbl_ibge WHERE cod_ibge = $cod_ibge)";
            $res_cidade = pg_query($con, $sql_cidade);
            if(pg_num_rows($res_cidade) > 0){
                $cod_ibge = pg_fetch_result($res_cidade, 0, cidade);
            }else{
                $cod_ibge = "null";
            }

        }else{
            $cod_ibge = "null";
        }

		$sql_os = "SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE os = $os";
		$res_os = pg_query($con,$sql_os);
		if(pg_num_rows($res_os) == 0) {
			$sql_abre_chamado = "
				INSERT INTO tbl_hd_chamado
				(
					posto,
					titulo,
					status,
					atendente,
					categoria,
					admin,
					fabrica_responsavel,
					fabrica
				)
				VALUES
				(
					$posto,
					'Atendimento interativo',
					'Aberto',
					$login_admin,
					'reclamacao_produto',
					$login_admin,
					$login_fabrica,
					$login_fabrica
				) RETURNING hd_chamado
				";
			$res_abre_chamado = pg_query($con, $sql_abre_chamado);

			$hd_chamado = pg_fetch_result($res_abre_chamado, 0, hd_chamado);

			if(!empty($hd_chamado)){

				$sql = "UPDATE tbl_os SET hd_chamado = $hd_chamado WHERE os = $os";
				$res = pg_query($con, $sql);

				$sql_extra = "
					INSERT INTO tbl_hd_chamado_extra
					(
						hd_chamado,
						produto,
						revenda_nome,
						posto,
						os,
						serie,
						data_nf,
						nota_fiscal,
						defeito_reclamado_descricao,
						nome,
						endereco,
						numero,
						complemento,
						bairro,
						cep,
						fone,
						email,
						cpf,
						cidade,
						revenda_cnpj
					)
					VALUES
					(
						$hd_chamado,
						$produto,
						'$revenda_nome',
						$posto,
						$os,
						'$serie',
						'$data_nf',
						'$nota_fiscal',
						'$defeito_reclamado_descricao',
						'$consumidor_nome',
						'$consumidor_endereco',
						'$consumidor_numero',
						'$consumidor_complemento',
						'$consumidor_bairro',
						'$consumidor_cep',
						'$consumidor_fone',
						'$consumidor_email',
						'$consumidor_cpf',
						$cod_ibge,
						'$revenda_cnpj'
					)
					";

				$res_extra = pg_query($con, $sql_extra);

				if(!$res_extra){
					echo pg_last_error();
				}else{

					echo "$hd_chamado";
				}

			}else{
				echo pg_last_error();
			}
		}else{
			echo pg_fetch_result($res_os, 0, hd_chamado);
		}
    }else{
        echo traduz("Não foi possivel abrir o Atendimento através dessa OS");
    }

    exit;

}

if(isset($_POST['exclui_hd_chamado'])){

    $hd_chamado = $_POST['exclui_hd_chamado'];
    $motivo = $_POST['motivo'];

    if($login_fabrica == 137){

        $sql_motivo = "INSERT INTO tbl_hd_chamado_item(
                            hd_chamado          ,
                            data                ,
                            comentario          ,
                            admin               ,
                            interno             ,
                            status_item
                        ) values (
                            $hd_chamado         ,
                            current_timestamp   ,
                            '$motivo'           ,
                            $login_admin        ,
                            't'  ,
                            'Aberto'
                        )";

        $res_motivo = pg_query($con, $sql_motivo);

    }

    $sql = "UPDATE tbl_hd_chamado_extra SET abre_os = 'f' WHERE hd_chamado = $hd_chamado";
    $res = pg_query($con, $sql);

    if(pg_affected_rows($res) > 0){
        echo "success";
    }else{
        echo "Error";
    }

    exit;
}

if(@$_POST['ajax'] == 'ajax'){

    if($_POST['acao'] == 'intervencao'){
        $os = $_POST['os'];

        $sql = "INSERT INTO tbl_os_status
                    (os,status_os,data,observacao, admin)
                VALUES
                    ($os,158,current_timestamp,'Intervenção Departamento Jurídico', $login_admin)";
        if(pg_query($con,$sql)){
            echo 1; //sucesso!!!
        }
    }

    exit;
}

if (strlen($_POST["btn_acao_pre_os"]) > 0) $btn_acao_pre_os = strtoupper($_POST["btn_acao_pre_os"]);
if (strlen($_GET["btn_acao_pre_os"]) > 0)  $btn_acao_pre_os = strtoupper($_GET["btn_acao_pre_os"]);
//echo "LOGIN FABRICA =".$login_fabrica;

if (!function_exists('verificaSelect')) {

    function verificaSelect($valor1, $valor2) {
        return ($valor1 == $valor2) ? " selected = 'selected' " : "";
    }

}

function gravaRespostaInitPesquisa($os) {
    global $con, $login_fabrica, $login_admin;

    $sqlPesquisaSa = "SELECT o.os
                        FROM tbl_os o
                        JOIN tbl_resposta r ON r.os = o.os
                        JOIN tbl_pesquisa_formulario pf ON pf.pesquisa_formulario = r.pesquisa_formulario
                        JOIN tbl_pesquisa p ON p.pesquisa = r.pesquisa
                       WHERE o.os = {$os}
                         AND p.categoria='os'
                         AND p.ativo IS TRUE
                         AND p.fabrica={$login_fabrica}";
    $resPesquisaSa = pg_query($con, $sqlPesquisaSa);

    if (pg_num_rows($resPesquisaSa) == 0) {

        $sqlx = "SELECT tbl_pesquisa.pesquisa,
                        tbl_pesquisa_formulario.pesquisa_formulario
                   FROM tbl_pesquisa
                   JOIN tbl_pesquisa_formulario ON tbl_pesquisa_formulario.pesquisa=tbl_pesquisa.pesquisa  AND tbl_pesquisa_formulario.ativo IS TRUE
                  WHERE tbl_pesquisa.categoria='os'
                    AND tbl_pesquisa.ativo IS TRUE
                    AND tbl_pesquisa.fabrica={$login_fabrica}";
        $resx = pg_query($con, $sqlx);

        if (pg_num_rows($resx) > 0) {
            $xpesquisa = pg_fetch_result($resx, 0, 'pesquisa');
            $xpesquisa_formulario = pg_fetch_result($resx, 0, 'pesquisa_formulario');

            $sqly = " INSERT INTO tbl_resposta (
                                    pesquisa_formulario,
                                    os,
                                    pesquisa,
                                    data_input,
                                    sem_resposta,
                                    admin
                                ) VALUES (
                                    $xpesquisa_formulario,
                                    $os,
                                    $xpesquisa,
                                    CURRENT_TIMESTAMP,
                                    TRUE,
                                    $login_admin
                                )";
            $resy = pg_query($con, $sqly);
            if (pg_last_error($con)){

                return false;
            }
            return true;
        }
    }
}
# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {

    $busca      = $_GET["busca"];
    $tipo_busca = $_GET["tipo_busca"];

    if (strlen($q) > 2) {

        if ($tipo_busca == 'posto') {

            $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                    WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

            $sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND tbl_posto.nome ilike '%$q%' ";

            $res = pg_query($con,$sql);

            if (pg_num_rows ($res) > 0) {

                for ($i = 0; $i < pg_num_rows($res); $i++) {

                    $cnpj         = trim(pg_fetch_result($res, $i, 'cnpj'));
                    $nome         = trim(pg_fetch_result($res, $i, 'nome'));
                    $codigo_posto = trim(pg_fetch_result($res, $i, 'codigo_posto'));

                    echo "$cnpj|$nome|$codigo_posto";
                    echo "\n";

                }

            }

        }

        if ($tipo_busca == "produto") {

            $sql = "SELECT tbl_produto.produto,
                            tbl_produto.referencia,
                            tbl_produto.descricao
                    FROM tbl_produto
                    WHERE tbl_produto.fabrica_i = $login_fabrica ";

            $sql .=  ($busca == "codigo") ? " AND tbl_produto.referencia like '%$q%' " : " AND UPPER(tbl_produto.descricao) ilike '%$q%' ";

            $res = pg_query($con,$sql);
            if (pg_num_rows ($res) > 0) {
                for ($i = 0; $i < pg_num_rows($res); $i++) {
                    $produto    = trim(pg_fetch_result($res,$i,'produto'));
                    $referencia = trim(pg_fetch_result($res,$i,'referencia'));
                    $descricao  = trim(pg_fetch_result($res,$i,'descricao'));
                    echo "$produto|$descricao|$referencia";
                    echo "\n";
                }
            }

        }

        if ($tipo_busca=="consumidor_cidade"){

            $sql = "SELECT      DISTINCT tbl_posto.cidade
                    FROM        tbl_posto_fabrica
                    JOIN tbl_posto using(posto)
                    WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
                    AND         tbl_posto.cidade ILIKE '%$q%'
                    ORDER BY    tbl_posto.cidade";

            $res = pg_query($con,$sql);
            if (pg_num_rows ($res) > 0) {
                for ($i=0; $i<pg_num_rows ($res); $i++ ){
                    $consumidor_cidade        = trim(pg_fetch_result($res,$i,cidade));
                    echo "$consumidor_cidade";
                    echo "\n";
                }
            }
        }
    }
    exit;
}

$os_excluir = $_GET['excluir']; //hd 61698 waldir

if (strlen ($os_excluir) > 0) {

    if (tem_pedido_os($os_excluir)) {

        include_once '../anexaNF_inc.php';

        if($login_fabrica == 1){
            $sql = "SELECT posto FROM tbl_os WHERE os = $os_excluir";
            $res = pg_query ($con,$sql);
            if(pg_num_rows($res) > 0){
                $posto = pg_fetch_result($res, 0, 'posto');
            }

            $sql = "SELECT tbl_os.os
                    FROM tbl_os
                    WHERE tbl_os.fabrica = $login_fabrica
                    AND   tbl_os.posto   = $posto
                    AND   (tbl_os.data_abertura + INTERVAL '60 days') <= current_date
                    AND   tbl_os.data_fechamento IS NULL
                    AND  tbl_os.excluida is FALSE LIMIT 1";

            $res = pg_query ($con,$sql);
            if(pg_num_rows($res) > 0){
                $tem_os_aberta = pg_fetch_result($res, 0, 'os');
            }
        }

        if ($fabrica_copia_os_excluida) {//HD 278885

            $motivo = $_GET['motivo'];

            $res = pg_query ($con,"BEGIN TRANSACTION");

            $sql = "INSERT INTO tbl_os_status (
                        os         ,
                        observacao ,
                        status_os  ,
                        admin
                    ) VALUES (
                        $os_excluir,
                        '$motivo' ,
                        15       ,
                        $login_admin
                    );";

            $res = pg_query ($con,$sql);

            $sql = "UPDATE tbl_os SET excluida = true
                    WHERE  tbl_os.os           = $os_excluir
                    AND    tbl_os.fabrica      = $login_fabrica;";
            $res = pg_query($con,$sql);

            $msg_erro = pg_errormessage($con);

            if (!in_array($login_fabrica, array(30))) {
                $sql = "INSERT INTO tbl_os_excluida (
                                fabrica           ,
                                admin             ,
                                os                ,
                                sua_os            ,
                                posto             ,
                                codigo_posto      ,
                                produto           ,
                                referencia_produto,
                                data_digitacao    ,
                                data_abertura     ,
                                data_fechamento   ,
                                serie             ,
                                nota_fiscal       ,
                                data_nf           ,
                                motivo_exclusao   ,
                                consumidor_nome
                            )
                            SELECT  tbl_os.fabrica            ,
                                $login_admin                  ,
                                tbl_os.os                     ,
                                tbl_os.sua_os                 ,
                                tbl_os.posto                  ,
                                tbl_posto_fabrica.codigo_posto,
                                tbl_os.produto                ,
                                tbl_produto.referencia        ,
                                tbl_os.data_digitacao         ,
                                tbl_os.data_abertura          ,
                                tbl_os.data_fechamento        ,
                                tbl_os.serie                  ,
                                tbl_os.nota_fiscal            ,
                                tbl_os.data_nf                ,
                                '$motivo'                     ,
                                tbl_os.consumidor_nome
                            FROM    tbl_os
                            JOIN    tbl_posto_fabrica        on tbl_posto_fabrica.posto = tbl_os.posto and tbl_os.fabrica          = tbl_posto_fabrica.fabrica
                            JOIN    tbl_produto              on tbl_produto.produto     = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
                            WHERE   tbl_os.os      = $os_excluir
                            AND     tbl_os.fabrica = $login_fabrica ";

                $res = pg_query($con,$sql);

            } else {
                $sql = "INSERT INTO tbl_os_interacao
                        (programa,fabrica, os, admin, comentario, interno, exigir_resposta)
                        VALUES
                        ('$programa_insert',$login_fabrica, $os_excluir, $login_admin, '".traduz("Cancelamento de OS. Motivo:")." $motivo', TRUE, FALSE)";
                $res = pg_query($con,$sql);
            }
            //HD 278885
            //PARA A SALTON NAO EXCLUI PEDIDO, OS OPERADORES VÃO ADICIONAR UM VALOR AVULSO NO EXTRATO
            //CASO O POSTO QUEIRA FICAR COM A PEÇA, SENAO A OS SERÁ EXCLUIDA APENAS QUANDO O POSTO DEVOLVER A PEÇA
            if ($login_fabrica == 1) {

                $res = pg_query ($con,$sql);
                $msg_erro = pg_errormessage($con);

                #VERIFICA SE TEM PEDIDO PARA EXCLUIR
                $sql = "SELECT tbl_os_item.pedido_item
                                FROM tbl_os
                                    JOIN tbl_os_produto USING(os)
                                    JOIN tbl_os_item USING(os_produto)
                                WHERE os = $os_excluir";

                $res = pg_query ($con,$sql);

                if (pg_num_rows($res) > 0) {

                    for ($i = 0; $i < pg_num_rows($res); $i++) {
                        $pedido_item = pg_fetch_result($res,$i,pedido_item);

                        if (!empty($pedido_item)) {
                            $sql_ped = "SELECT  PE.pedido      ,
                                        PE.distribuidor,
                                        PI.pedido_item ,
                                        PI.peca        ,
                                        PI.qtde        ,
                                        OP.os
                                        FROM   tbl_pedido        PE
                                        JOIN   tbl_pedido_item   PI ON PI.pedido     = PE.pedido
                                        LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
                                        LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
                                        WHERE PI.pedido_item  = $pedido_item
                                        AND   PE.fabrica = $login_fabrica
                                        AND   PI.qtde > PI.qtde_cancelada
                                        AND   PI.qtde_faturada = 0";

                            $res_ped = pg_query ($con,$sql_ped);

                            if (pg_num_rows($res_ped) > 0) {
                                $pedido         = pg_fetch_result ($res_ped,0,pedido);
                                $peca           = pg_fetch_result ($res_ped,0,peca);
                                $qtde           = pg_fetch_result ($res_ped,0,qtde);
                                $os             = pg_fetch_result ($res_ped,0,os);
                                $distribuidor   = pg_fetch_result ($res_ped,0,distribuidor);

                                $sql  = "SELECT fn_pedido_cancela(1,$login_fabrica,$pedido,$peca,'OS excluída pelo fabricante',$login_admin)";
                                $resY = pg_query ($con,$sql);
                                $msg_erro .= pg_errormessage($con);
                            } else {
                                $msg_erro = traduz("OS com Peça já faturada");
                            }
                        }
                    }
                }

            }//HD 278885

            if (strlen($msg_erro) == 0) {

                /**
                 * Exclui os arquivos em anexo, se tiver
                 **/
                if (count($anexos = temNF($os, 'path'))) { //'path' devolve um array com todos os anexos
                    foreach ($anexos as $arquivoAnexo) {
                        excluirNF($arquivoAnexo);
                    }
                }

                $res = pg_query ($con,"COMMIT");

                if(!empty($tem_os_aberta)){
                    $dir = __DIR__."/../rotinas/blackedecker/bloqueia-posto.php";
                    echo `/usr/bin/php $dir $posto`;
                }

                $verbo = ($login_fabrica == 30) ? "Cancelada" : "Excluída";

                echo "<script language='javascript'>
                    alert('".traduz("Os $verbo com sucesso!")."');
                    window.location = '$PHP_SELF';
                </script>";
            } else {
                $res = pg_query ($con,"ROLLBACK");
                echo "<script language='javascript'>
                        alert('".traduz("Não foi possível excluir OS! ")."');
                        window.location = '$PHP_SELF';
                </script>";
            }

        } else {

            /**
             * Exclui os arquivos em anexo, se tiver
             **/
            if (count($anexos = temNF($os, 'path'))) { //'path' devolve um array com todos os anexos
                foreach ($anexos as $arquivoAnexo) {
                    excluirNF($arquivoAnexo);
                }
            }

            $sql = "SELECT fn_os_excluida($os_excluir,$login_fabrica,$login_admin);";
            $res = @pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);
            if (strlen ($msg_erro) == 0) {
                header("Location: os_parametros.php");
                exit;
            }
        }
    } else {
        echo "<script language='javascript'>
                alert('".traduz("OS possui pedido e não pode ser excluida")."');
                window.location = '$PHP_SELF';
        </script>";
    }
}

$excluir_troca = $_GET['excluir_troca']; //HD 157191

if (strlen ($excluir_troca) > 0) {

    if($login_fabrica == 1){
        $sql = "SELECT posto FROM tbl_os WHERE os = $excluir_troca";
        $res = pg_query ($con,$sql);
        if(pg_num_rows($res) > 0){
            $posto = pg_fetch_result($res, 0, 'posto');
        }

        $sql = "SELECT tbl_os.os
                FROM tbl_os
                WHERE tbl_os.fabrica = $login_fabrica
                AND   tbl_os.posto   = $posto
                AND   (tbl_os.data_abertura + INTERVAL '60 days') <= current_date
                AND   tbl_os.data_fechamento IS NULL
                AND  tbl_os.excluida is FALSE LIMIT 1";

        $res = pg_query ($con,$sql);
        if(pg_num_rows($res) > 0){
            $tem_os_aberta = pg_fetch_result($res, 0, 'os');
        }
    }

    $sql = "UPDATE tbl_os SET data_fechamento = current_date WHERE os = $excluir_troca";
    $res = pg_query ($con,$sql);

    $sql="UPDATE tbl_os_extra set extrato = 0 where os = $excluir_troca;";
    $res= pg_query($con, $sql);

    $sql="UPDATE tbl_os_troca set status_os = 13 where os = $excluir_troca;";
    $res= pg_query($con, $sql);

    $sql = "INSERT INTO tbl_os_status (
                        os             ,
                        status_os      ,
                        observacao     ,
                        admin          ,
                        status_os_troca
                    ) VALUES (
                        '$excluir_troca'             ,
                        '13'                         ,
                        'OS Recusada pelo Fabricante',
                        $login_admin                 ,
                        't'
                    );";

    $res = pg_query ($con,$sql);
    $msg_erro = pg_errormessage($con);

    if (strlen ($msg_erro) == 0) {
        $res = pg_query ($con,"COMMIT TRANSACTION");

        if(!empty($tem_os_aberta)){
            $dir = __DIR__."/../rotinas/blackedecker/bloqueia-posto.php";
            echo `/usr/bin/php $dir $posto`;
        }

    }else{
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    }

    if (strlen ($msg_erro) == 0) {
        header("Location: os_parametros.php");
        exit;
    }
}


$os_fechar = $_GET['fechar'];

if (strlen ($os_fechar) > 0) {
    if ($login_fabrica == 91 && $_GET["sem_pagamento"]) {
        $motivo_fechar = $_GET["motivo"];
    }

    $msg_erro = "";
    $res = pg_query ($con,"BEGIN TRANSACTION");

    $sql = "SELECT status_os
                FROM tbl_os_status
                WHERE os = $os_fechar
                AND status_os IN (62,64,65,72,73,87,81,88,116,117)
                ORDER BY data DESC
                LIMIT 1";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res)>0){
            $status_os = trim(pg_fetch_result($res,0,status_os));
            if ($status_os=="72" || $status_os=="62" || $status_os=="87" || $status_os=="116"){
                if ($login_fabrica ==51) { // HD 59408
                    $sql = " INSERT INTO tbl_os_status
                            (os,status_os,data,observacao)
                            VALUES ($os_fechar,64,current_timestamp,'OS Fechada pelo posto')";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    $sql = "UPDATE tbl_os_item SET servico_realizado = 671 FROM tbl_os_produto
                            WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
                            AND   tbl_os_produto.os = $os_fechar";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    $sql = "UPDATE tbl_os SET defeito_constatado = 10536,solucao_os = 491
                            WHERE tbl_os.os = $os_fechar";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }else{
                    $msg_erro .= traduz("OS está em intervenção, não pode ser fechada");
                }
            }
        }

        if ($login_fabrica == 91 && $_GET["sem_pagamento"] && strlen ($msg_erro) == 0) {
            $sql = "INSERT INTO tbl_os_status
                    (os, status_os, data, observacao)
                    VALUES
                    ({$os_fechar}, 90, current_timestamp, 'OS fechada sem pagamento: {$motivo_fechar}')";
            $res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con) ;
        }

        if (strlen ($msg_erro) == 0) {
            $sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $os_fechar AND fabrica = $login_fabrica";
            $res = pg_query ($con,$sql);
            $msg_erro .= pg_errormessage($con) ;
        }

        if($login_fabrica == 30 && $msg_erro == ""){
            $sql = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP WHERE os = $os_fechar AND fabrica = $login_fabrica";
            $res = pg_query ($con,$sql);
            $msg_erro .= pg_errormessage($con) ;

            if($msg_erro == ""){
                $sql_ce = "SELECT tbl_posto_fabrica.contato_estado
                            FROM tbl_os
                                JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                            WHERE tbl_os.os = $os_fechar ";
                $res_ce = pg_query($con,$sql_ce);
                if (pg_num_rows($res_ce) > 0) {
                    $sql_ce = "UPDATE tbl_os_extra SET obs_fechamento = '$login_login' WHERE os = $os_fechar ;";
                    $res_ce = pg_query ($con,$sql_ce);
                    $msg_erro .= pg_errormessage($con) ;
                }
            }
        }

        if($login_fabrica == 50){
            $sql = "SELECT os from tbl_os_extra WHERE os = $os_fechar and i_fabrica = $login_fabrica";
            $res = pg_query($con, $sql);
            if(pg_num_rows($res)>0){
                $sql_ce = "UPDATE tbl_os_extra SET obs_fechamento = '$login_login' WHERE os = $os_fechar ;";
            }else{
                $sql_ce = "INSERT INTO tbl_os_extra (os, obs_fechamento) VALUES ($os_fechar, '$login_login')";
            }
            $res_ce = pg_query ($con,$sql_ce);
            $msg_erro .= pg_errormessage($con);
        }

        if (strlen ($msg_erro) == 0 AND $login_fabrica == 1) {
            $sql = "SELECT fn_valida_os_item($os_fechar, $login_fabrica)";
            $res = @pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        if (strlen ($msg_erro) == 0) {
            $sql = "SELECT fn_finaliza_os($os_fechar, $login_fabrica)";
            $res = pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);
            if (strlen ($msg_erro) == 0 and ($login_fabrica==1 or $login_fabrica==24)) {
                $sql = "SELECT fn_estoque_os($os_fechar, $login_fabrica)";
                $res = @pg_query ($con,$sql);
                $msg_erro = pg_errormessage($con);
            }
        }

        if($login_fabrica == 101){

            $sql_dc = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP WHERE os = {$os_fechar} AND data_conserto ISNULL";
            $res_dc = pg_query($con, $sql_dc);

            /*$sql_celular = "SELECT
                                consumidor_celular,
                                sua_os,
                                referencia,
                                descricao,
                                nome
                            FROM tbl_os
                            INNER JOIN tbl_produto USING(produto)
                            INNER JOIN tbl_posto USING(posto)
                            WHERE
                                os = {$os_fechar}";
            $res_celular = pg_query($con, $sql_celular);
            $envia_sms = false;

            if (pg_num_rows($res_celular) > 0) {

                $consumidor_celular = pg_fetch_result($res_celular, 0, 'consumidor_celular');
                $sms_os             = pg_fetch_result($res_celular, 0, 'sua_os');
                $sms_produto        = pg_fetch_result($res_celular, 0, 'referencia') . ' - ' . pg_fetch_result($res_celular, 0, 'descricao');
                $sms_posto          = pg_fetch_result($res_celular, 0, 'nome');

                if (!empty($consumidor_celular)) {
                    $envia_sms = true;
                }

                $qry_enviou_sms = pg_query($con, "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os_fechar");
                if (pg_num_rows($qry_enviou_sms) > 0) {
                    $arr_campos_adicionais = json_decode(pg_fetch_result($qry_enviou_sms, 0, 'campos_adicionais'), true);
                    if (!empty($arr_campos_adicionais) and array_key_exists('enviou_sms', $arr_campos_adicionais)) {
                        if ($arr_campos_adicionais['enviou_sms'] == 't') {
                            $envia_sms = false;
                        }
                    }
                }

                if (true === $envia_sms) {

                    $sms_msg = "Conserto de Produto DeLonghi-Kenwood - OS {$sms_os}. Informamos que seu produto {$sms_produto} que esta no Posto autorizado {$sms_posto}, já esta consertado. Por favor solicitamos comparecer ao Posto para retirada. Atenciosamente, DeLonghi Kenwood.";

                    if ($sms->enviarMensagem($consumidor_celular, $sms_os, '', $sms_msg)) {
                        $ins_campos_adicionais = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($os_fechar, $login_fabrica, '{\"enviou_sms\": \"t\"}')";
                        $qry_campos_adicionais = pg_query($con, $ins_campos_adicionais);
                    }
                }
            }*/
        }

        if($login_fabrica == 50 AND strlen(trim($msg_erro))==0){
            $sql_ver_peca_obrigatoria = "SELECT tbl_os.os, tbl_faturamento_item.pedido, tbl_faturamento_item.faturamento_item
                    FROM tbl_os
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
                    INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                    INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                    left JOIN tbl_faturamento_item ON (tbl_faturamento_item.pedido = tbl_os_item.pedido OR tbl_faturamento_item.os_item = tbl_os_item.os_item )
                    AND tbl_os_item.peca = tbl_faturamento_item.peca
                    WHERE tbl_os.os = $os_fechar
                    AND tbl_os.fabrica = $login_fabrica
                    /*AND tbl_os_item.pedido is not null*/
                    AND tbl_os_item.peca_obrigatoria = 't'
                    AND tbl_servico_realizado.troca_de_peca is true ";
            $res_ver_peca_obrigatoria = pg_query($con, $sql_ver_peca_obrigatoria);
            if(pg_num_rows($res_ver_peca_obrigatoria) > 0){

                $sql = "SELECT os FROM tbl_os_campo_extra where os = $os_fechar AND fabrica = $login_fabrica";
                $res = pg_query($con, $sql);
                if(pg_num_rows($res)==0){
                    $sql_campo_extra = "INSERT INTO tbl_os_campo_extra (os, fabrica, os_bloqueada) VALUES ($os_fechar, $login_fabrica, true)";
                }else{
                    $sql_campo_extra = "UPDATE tbl_os_campo_extra SET os_bloqueada = true WHERE os = $os_fechar AND fabrica = $login_fabrica ";
                }
                $res_campo_extra = pg_query($con, $sql_campo_extra);
                include '../grava_faturamento_peca_estoque_colormaq.php';
            }
        }

        if (strlen ($msg_erro) == 0) {
            $res = pg_query ($con,"COMMIT TRANSACTION");
            echo "ok;XX$os_fechar";
        }else{
            $res = @pg_query ($con,"ROLLBACK TRANSACTION");
            $erro = explode("CONTEXT",$msg_erro);
            $msg_erro = $erro[0];
            echo "$msg_erro ";
        }
    flush();
    exit;
}

$where_tbl_status_checkpoint = "";
if ($login_fabrica == 30) {
    $where_tbl_status_checkpoint = "AND fabricas isnull OR {$login_fabrica} = any(fabricas)";
}

if (in_array($login_fabrica, [175])) {
    $where_tbl_status_checkpoint = "AND status_checkpoint != 0";
}

#HD 234532
$sql_status = "SELECT status_checkpoint,descricao,cor FROM tbl_status_checkpoint";
$sql_status .= (!empty($where_tbl_status_checkpoint)) ? " WHERE 1=1 {$where_tbl_status_checkpoint}" : "";
$res_status = pg_query($con,$sql_status);
$total_status = pg_num_rows($res_status);

for($i=0;$i<$total_status;$i++){
    $id_status = pg_fetch_result($res_status,$i,'status_checkpoint');
    $cor_status = pg_fetch_result($res_status,$i,'cor');
    $descricao_status = pg_fetch_result($res_status,$i,'descricao');

    #Array utilizado posteriormente para definir as cores dos status
    $array_cor_status[$id_status] = $cor_status;
    $array_cor_descricao[$id_status] = $descricao_status;
}

#HD 234532
function exibeImagemStatusCheckpoint($status_checkpoint, $sua_os='', $retorna_descricao = false)
{

    global $array_cor_status;
    global $array_cor_descricao;

    if ($retorna_descricao === true) {
        return $array_cor_descricao[$status_checkpoint];
    } else {
        /*
        0 | Aberta Call-Center  (imagens/status_branco)
        1 | Aguardando Analise  (imagens/status_vermelho)
        2 | Aguardando Peças    (imagens/status_amarelo)
        3 | Aguardando Conserto (imagens/status_rosa)
    4 | Aguardando Retirada (imagens/status_azul)
    8 | Aguardando Produto  (imagens/status_laranja)
        9 | Finalizada          (imagens/status_cinza)
        */
        if(strlen($status_checkpoint) > 0){
            echo '<span class="status_checkpoint" id="st_ch_'.$sua_os.'" style="background-color:'.$array_cor_status[$status_checkpoint].'">&nbsp;</span>';
        }else{
            echo '<span class="status_checkpoint_sem" id="st_ch_'.$sua_os.'" >&nbsp;</span>';
        }
    }
}


$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$condicao_status = ($login_fabrica == 96) ? '0,1,2,3,5,6,7,9' : '0,1,2,3,4,9';

if($login_fabrica == 30){
    $condicao_status = '0,1,2,3,15,16,17,18,4,8,9';
}

if (in_array($login_fabrica, array(51,81,114))) {
    $condicao_status = '0,1,2,3,4,8,9';
}

if($login_fabrica == 72){
    $condicao_status .= ',28';
}

if ($login_fabrica == 144) {
    $condicao_status = '0,1,14,2,8,11,3,10,4,9';
}

if($login_fabrica == 131){ // HD-2181938
  $condicao_status = '0,1,2,3,4,8,9,13';
}

if($login_fabrica == 3){
    $condicao_status = '0,1,2,3,4,9,10';
}

if (isset($novaTelaOs)) {
    $condicao_status = '0,1,2,3,4,8,9';
}

if ($login_fabrica == 141) {
    $condicao_status = '0,1,14,2,8,11,3,10,12,4,9';
}

if ($login_fabrica == 144) {
    $condicao_status = '0,1,14,2,8,11,3,10,4,9';
}

if ($login_fabrica == 165) {
    $condicao_status = '0,1,14,2,8,11,3,12,4,9,29,30';
}

if (in_array($login_fabrica, array(158))) {
    $condicao_status = '1,2,3,9,23,24,25,26,27';
}

if($login_fabrica == 148){ //hd_chamado=3049906
    $condicao_status = '0,1,2,3,4,8,9,28';
}

if (in_array($login_fabrica, array(171,175))) {
    $condicao_status .= ",14";
}

if ($login_fabrica == 35) {
    $condicao_status = '1,2,3,8,9,34';
}

if (in_array($login_fabrica, array(178))){
    $condicao_status .= ",30";
}

if ($cancelaOS) {
    $condicao_status .= ",28";
}

if ($telecontrol_distrib && (!isset($novaTelaOs) || (in_array($login_fabrica, [160]) or $replica_einhell))) {
    $condicao_status .= ",35, 36, 37, 39";
}

if (in_array($login_fabrica, array(169,170))) {
    $condicao_status = "0,1,2,3,4,8,9,14,28,30,45,46,47,48,49,50";
}

if (in_array($login_fabrica, [174])) {
    $condicao_status .= ",39,40,41,42,43";
}

if (in_array($login_fabrica, array(177))) {
    $condicao_status .= ",14";
}

if (in_array($login_fabrica, [167, 203])) {
    $condicao_status .= ",37";
}

if ($login_fabrica == 183) {
    $condicao_status = '0,1,2,3,8,9,28,30';
}

if ($login_fabrica == 151) {
    $condicao_status .= ",54";
}

if (strlen($_POST['btn_acao']) > 0 or strlen($_GET['btn_acao']) > 0) {

    if($login_fabrica == 35){
        $os_aguardando_troca = $_POST["os_aguardando_troca"];
    }

    //HD 393737 : filtro IBBL, OS Lançadas : Hoje, Ontem, Esta Semana, Semana Anterior e no Mês
    if($login_fabrica == 90){
        if($_POST['chk_opt1'])    $chk1        = $_POST['chk_opt1'];
        else if($_GET['chk_opt1'])    $chk1        = $_GET['chk_opt1'];
        if($_POST['chk_opt2'])    $chk2        = $_POST['chk_opt2'];
        else if($_GET['chk_opt2'])    $chk2        = $_GET['chk_opt2'];
        if($_POST['chk_opt3'])    $chk3        = $_POST['chk_opt3'];
        else if($_GET['chk_opt3'])    $chk3        = $_GET['chk_opt3'];
        if($_POST['chk_opt4'])    $chk4        = $_POST['chk_opt4'];
        else if($_GET['chk_opt4'])    $chk4        = $_GET['chk_opt4'];
        if($_POST['chk_opt5'])    $chk5        = $_POST['chk_opt5'];
        else if($_GET['chk_opt5'])    $chk5        = $_GET['chk_opt5'];

        if(!empty($chk1) OR !empty($chk2) OR !empty($chk3) OR !empty($chk4) OR !empty($chk5) ){
            $monta_sql .= " AND ( ";
            if (strlen($chk1) > 0) {
                // data do dia
                $sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
                $resX = pg_exec ($con,$sqlX);
                $dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
                $dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

                $sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
                $resX = pg_exec ($con,$sqlX);
                #  $dia_hoje_final = pg_result ($resX,0,0);

                $monta_sql .=" (tbl_os.data_digitacao BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
            //  if ($ip == '201.42.44.145') echo $monta_sql;
                $dt = 1;

            }

            if (strlen($chk2) > 0) {
                // dia anterior
                $sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
                $resX = pg_exec ($con,$sqlX);
                $dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
                $dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

                if(!empty($chk1) ){
                    $monta_sql .=" OR ";
                }

                $monta_sql .=" (tbl_os.data_digitacao BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
                $dt = 1;

            }

            if (strlen($chk3) > 0) {
                // nesta semana
                $sqlX = "SELECT to_char (current_date , 'D')";
                $resX = pg_exec ($con,$sqlX);
                $dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

                $sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
                $resX = pg_exec ($con,$sqlX);
                $dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

                $sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
                $resX = pg_exec ($con,$sqlX);
                $dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

                if(!empty($chk1) OR !empty($chk2) ){
                    $monta_sql .=" OR ";
                }

                $monta_sql .=" (tbl_os.data_digitacao BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
                $dt = 1;

            }

            if (strlen($chk4) > 0) {
                // semana anterior
                $sqlX = "SELECT to_char (current_date , 'D')";
                $resX = pg_exec ($con,$sqlX);
                $dia_semana_hoje = pg_result ($resX,0,0) - 1 + 7 ;

                $sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
                $resX = pg_exec ($con,$sqlX);
                $dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

                $sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
                $resX = pg_exec ($con,$sqlX);
                $dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

                if(!empty($chk1) OR !empty($chk2) OR !empty($chk3) ){
                    $monta_sql .=" OR ";
                }
                $monta_sql .=" (tbl_os.data_digitacao BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
                $dt = 1;

            }

            if (strlen($chk5) > 0)
            {
                $mes_inicial = trim(date("Y")."-".date("m")."-01");
                $mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

                if(!empty($chk1) OR !empty($chk2) OR !empty($chk3) OR !empty($chk4) ){
                    $monta_sql .=" OR ";
                }

                $monta_sql .= " tbl_os.data_digitacao BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59' ";
                $dt = 1;

            }
            $monta_sql .= ") ";
        }
    }

    //HD 211825: Filtro por tipo de OS: Consumidor/Revenda
    $consumidor_revenda_pesquisa = trim(strtoupper ($_POST['consumidor_revenda_pesquisa']));
    if (strlen($consumidor_revenda_pesquisa) == 0) $consumidor_revenda_pesquisa = trim(strtoupper($_GET['consumidor_revenda_pesquisa']));

    $os_off    = trim (strtoupper ($_POST['os_off']));
    if (strlen($os_off)==0) $os_off = trim(strtoupper($_GET['os_off']));
    $codigo_posto_off      = trim(strtoupper($_POST['codigo_posto_off']));
    if (strlen($codigo_posto_off)==0) $codigo_posto_off = trim(strtoupper($_GET['codigo_posto_off']));
    $posto_nome_off        = trim(strtoupper($_POST['posto_nome_off']));
    if (strlen($posto_nome_off)==0) $posto_nome_off = trim(strtoupper($_GET['posto_nome_off']));

    $sua_os    = trim (strtoupper ($_POST['sua_os']));
    if (strlen($sua_os)==0) $sua_os = trim(strtoupper($_GET['sua_os']));

    $numero_reclamacao    = trim (strtoupper ($_POST['numero_reclamacao']));
    if (strlen($numero_reclamacao)==0) $numero_reclamacao = trim(strtoupper($_GET['numero_reclamacao']));

    $seu_pedido = trim(strtoupper($_POST['seu_pedido']));

    $numero_os_sap    = trim (strtoupper ($_POST['numero_os_sap']));
    if (strlen($numero_os_sap)==0) $numero_os_sap = trim(strtoupper($_GET['numero_os_sap']));

    $serie     = trim (strtoupper ($_POST['serie']));
    if (strlen($serie)==0) $serie = trim(strtoupper($_GET['serie']));

    if ($login_fabrica == 158) {
        $patrimonio     = trim (strtoupper ($_POST['patrimonio']));
        if (strlen($patrimonio)==0) $patrimonio = trim(strtoupper($_GET['patrimonio']));
    }

    $nf_compra = trim (strtoupper ($_POST['nf_compra']));
    if (strlen($nf_compra)==0) $nf_compra = trim(strtoupper($_GET['nf_compra']));
    $consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));
    if (strlen($consumidor_cpf)==0) $consumidor_cpf = trim(strtoupper($_GET['consumidor_cpf']));

    $rg_produto_os = trim (strtoupper ($_POST['rg_produto_os']));
    if (strlen($rg_produto_os)==0) $rg_produto_os = trim(strtoupper($_GET['rg_produto_os']));


    if($login_fabrica == 160 or $replica_einhell){
        $versao = trim($_POST["versao"]);
    }

    if($login_fabrica == 164){
        $destinacao = $_POST["destinacao"];
    }

    $marca = $_POST['marca'];

    if($login_fabrica == 153){
        $recall = $_POST['recall'];
    }

    if ($login_fabrica ==52) {
        if (strlen($marca)==0){
          $marca = $_GET['marca'];
        }
        $cond_marca = (strlen($marca)>0) ? " tbl_os.marca = $marca " :" 1 = 1 ";
    }else if($login_fabrica == 1){
        if (strlen($marca)==0){
          $marca = $_GET['marca'];
        }
        $cond_marca = (strlen($marca)>0) ? " tbl_produto.marca = $marca " :" 1 = 1 ";
    }else{
        if (strlen($marca)==0){
          $marca = $_GET['marca'];
        }
        $cond_marca = (strlen($marca)>0) ? " tbl_marca.marca = $marca " :" 1 = 1 ";

    }

    $regiao     = trim ($_POST['regiao']);
    if (strlen($regiao)==0) $regiao = trim($_GET['regiao']);
    $classificacao_os = trim ($_POST['classificacao_os']); // HD 75762 para Filizola
    if (strlen($classificacao_os)==0) $classificacao_os = trim($_GET['classificacao_os']);
    $cond_classificacao_os = (strlen($classificacao_os)>0) ? " tbl_os_extra.classificacao_os = $classificacao_os " : " 1 = 1 ";

    $natureza = trim ($_POST['natureza']); //HD 45630
    if (strlen($natureza)==0) $natureza = trim($_GET['natureza']);
    $cond_natureza = (strlen($natureza)>0) ? " tbl_os.tipo_atendimento = $natureza " : " 1 = 1 ";

    # HD 48224
    $admin_abriu = trim ($_POST['admin_abriu']);
    if (strlen($admin_abriu)==0) $admin_abriu = trim($_GET['admin_abriu']);
    if(strlen($admin_abriu) > 0){
        $cond_admin = "AND tbl_os.admin = $admin_abriu";
    }

    $rg_produto  = strtoupper(trim ($_POST['rg_produto']));
    $lote        = strtoupper(trim ($_POST['lote']));
	$tipo_garantia = $_REQUEST['tipo_garantia'];
    //takashi - não sei pq colocaram isso, estava com problema... caso necessite voltar, consulte o suporte
    //takashi alterei novamente conforme Tulio e Samuel falaram
    if((strlen($sua_os)>0) and (strlen($sua_os)> 20))$msg= traduz("Digite no máximo 20 caracteres para fazer a pesquisa");
    if((strlen($sua_os)>0) and (strlen($sua_os)<4))$msg= traduz("Digite no minímo 4 caracteres para fazer a pesquisa");


    $codigo_posto       = trim(strtoupper($_POST['codigo_posto']));
    if (strlen($codigo_posto)==0){
        $codigo_posto = trim(strtoupper($_GET['codigo_posto']));
        $posto_nome   = trim(strtoupper($_POST['posto_nome']));
    }

    $tipo_atendimento = trim(@$_REQUEST['tipo_atendimento']);
    $descricao_tipo_atendimento = trim(@$_REQUEST['tipo_atendimento']);

    if (strlen($posto_nome)==0) $posto_nome = trim(strtoupper($_GET['posto_nome']));
    $consumidor_nome    = trim($_POST['consumidor_nome']);
    if (strlen($consumidor_nome)==0) $consumidor_nome = trim($_GET['consumidor_nome']);
    $produto_referencia = trim(strtoupper($_POST['produto_referencia']));
    if (strlen($produto_referencia)==0) $produto_referencia = trim(strtoupper($_GET['produto_referencia']));
    $admin              = trim($_POST['admin']);
    if (strlen($admin)==0) $admin = trim($_GET['admin']);
    $os_aberta          = trim(strtoupper($_POST['os_aberta']));
    if (strlen($os_aberta)==0) $os_aberta = trim(strtoupper($_GET['os_aberta']));
    $os_aberta_kof          = trim(strtoupper($_POST['os_aberta_kof']));
    if (strlen($os_aberta_kof)==0) $os_aberta_kof = trim(strtoupper($_GET['os_aberta_kof']));
    $os_atendida        = trim($_POST['os_atendida']);
    if (strlen($os_atendida)==0) $os_atendida = trim(strtoupper($_GET['os_atendida']));
	$os_troca          = trim(strtoupper($_POST['os_troca']));
    if (strlen($os_troca)==0) $os_troca = trim(strtoupper($_GET['os_troca']));
    $os_callcenter       = trim($_POST['os_callcenter']);
    if (strlen($os_callcenter)==0) $os_callcenter = trim(strtoupper($_GET['os_callcenter']));

    #HD 234532
    $status_checkpoint          = trim(strtoupper($_POST['status_checkpoint']));
    if (strlen($status_checkpoint)==0) $status_checkpoint = trim(strtoupper($_GET['status_checkpoint']));

    $status_checkpoint_pesquisa = $status_checkpoint;

    $admin_sap = trim($_REQUEST['admin_sap']);
    $tipo_posto = trim($_REQUEST['tipo_posto']);

    if (!empty($_REQUEST["os_elgin_status"])) {
        $os_elgin_status = $_REQUEST["os_elgin_status"];

        $qry_status = pg_query($con, "SELECT status_os FROM tbl_status_os WHERE descricao = '{$os_elgin_status}'");

        $status_os_ultimo = '0';

        if (pg_num_rows($qry_status)) {
            $status_os_ultimo = pg_fetch_result($qry_status, 0, 'status_os');
        }
    }

    #115630----
    $os_finalizada      = trim(strtoupper($_POST['os_finalizada']));
    if (strlen($os_finalizada)==0) $os_finalizada = trim(strtoupper($_GET['os_finalizada']));
    #----------
    $os_situacao        = trim(strtoupper($_POST['os_situacao']));
    if (strlen($os_situacao)==0) $os_situacao = trim(strtoupper($_GET['os_situacao']));
    $revenda_cnpj       = trim(strtoupper($_POST['revenda_cnpj']));
    if (strlen($revenda_cnpj)==0) $revenda_cnpj = trim(strtoupper($_GET['revenda_cnpj']));
    $pais               = trim(strtoupper($_POST['pais']));
    if (strlen($pais)==0) $pais = trim(strtoupper($_GET['pais']));

    $tipo_os               = trim(strtoupper($_POST['tipo_os']));
    if (strlen($tipo_os)==0) $tipo_os = trim(strtoupper($_GET['tipo_os']));

    $data_inicial = $_POST['data_inicial'];
    if (strlen($data_inicial)==0){
        $data_inicial = trim($_GET['data_inicial']);
    }
    $data_final   = $_POST['data_final'];
    if (strlen($data_final)==0){
        $data_final = trim($_GET['data_final']);
    }

    // HD 2502295
    $xos_cortesia = $_POST['os_cortesia'];
    if (strlen($xos_cortesia) == 0) {
        $xos_cortesia = $_GET['os_cortesia'];
    }

    if (strlen($xos_cortesia) == 0) {
        $xos_cortesia = "f";
    }

    $data_tipo = $_REQUEST["data_tipo"];
	$intervalo1 = $_REQUEST['intervalo1'];
	$intervalo2 = $_REQUEST['intervalo2'];

    if($login_fabrica == 30 && !empty($sua_os)){
        $sqlVer = "
            SELECT  COUNT(1) AS os_excluida
            FROM    tbl_os_excluida
            WHERE   os = $sua_os
        ";
        $resVer = pg_query($con,$sqlVer);
        $osExcluida = pg_fetch_result($resVer,0,os_excluida);
        if($osExcluida > 0){
            $msg = "OS excluída.";
        }
    }

    if(!empty($data_inicial) OR !empty($data_final)){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi))
            $msg = traduz("Data inicial inválida");

        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf))
            $msg = traduz("Data final inválida");

        if(strlen($msg)==0){
            $aux_data_inicial = "$yi-$mi-$di";
            $aux_data_final = "$yf-$mf-$df";

            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
                $msg = traduz("Data inicial maior do que a data final");
            }
        }
    }

    if (!in_array($login_fabrica, array(15,183))) {
        // HD 139148 - Liberar pesquisa somente com nome do consumidor, deste que seja especificado pello menos 10 letras (augusto)
        if (strlen($consumidor_nome) > 0 && strlen($consumidor_nome) < 10 AND strlen ($codigo_posto) == 0 AND strlen ($produto_referencia) == 0) {
            $msg = traduz("Especifique o posto ou o produto");
        }
    }


    $estado = trim($_POST['estado']);
    if (strlen($estado)==0){
        $estado = $_GET['estado'];
    }

    if($estado){
        switch($estado){
            case 'Norte':
                $consulta_estado = "AC','AP','AM','PA','RO','RR','TO";
            break;

            case 'Nordeste':
                $consulta_estado = "AL','BA','CE','MA','PB','PE','PI','RN','SE";
            break;

            case 'Centro_oeste':
                $consulta_estado = "DF','GO','MT','MS";
            break;

            case 'Sudeste':
                $consulta_estado = "ES','MG','RJ','SP";
            break;

            case 'Sul':
                $consulta_estado = "PR','RS','SC";
            break;

            default: $consulta_estado = $estado;
        }
    }

    $consulta_posto_estado = trim($_POST['posto_estado']);
    if (strlen($consulta_posto_estado)==0){
        $consulta_posto_estado = $_GET['posto_estado'];

        $array_postos_estado = explode(',', $consulta_posto_estado);

        if (count($array_postos_estado) > 1) {
            $consulta_posto_estado = implode("' , '", $array_postos_estado);            
        }
    }

    if (!empty($tipo_atendimento) && $login_fabrica == 183) {
        $cond_tipo_atendimento = $tipo_atendimento;
    }

    if($login_fabrica == 30){
        $consulta_cidade = filter_input(INPUT_POST,'cidade');
        $cons_sql_cidade = " AND tbl_os.consumidor_cidade ILIKE '%$consulta_cidade%'
        ";

        $cliente_admin = filter_input(INPUT_POST,'cliente_admin');
        $os_cancelada = filter_input(INPUT_POST,'os_cancelada');
    }

    if ($login_fabrica == 3) {
        $os_cancelada = filter_input(INPUT_POST,'os_cancelada');
    }

    $consumidor_cpf = preg_replace ("/\D/","",$consumidor_cpf);

    if (strlen ($consumidor_cpf) <> 11 AND strlen ($consumidor_cpf) <> 14 AND strlen ($consumidor_cpf) <> 0) {
        #HD 17333
        if ($login_fabrica<>20){
            $msg = traduz("Tamanho do CPF do consumidor inválido");
        }
    }

    // HD 415550
    if(isset($_POST['nome_tecnico']) && !empty($_POST['nome_tecnico'])  ) {
        if( empty($codigo_posto) )
            $msg = traduz("Selecione o posto para efetuar essa consulta");
        $tecnico = trim ($_POST['nome_tecnico']);
        $condicao_tecnico = (!empty($tecnico)) ? " AND tbl_os.tecnico_nome ILIKE '" . $tecnico . "%' " : '';
    }

    if(in_array($login_fabrica, array(156,165)) AND !empty($_POST["tecnico"])){
	   $tecnico = $_POST["tecnico"];
	   $condicao_tecnico = " AND tbl_os.tecnico = {$tecnico} ";
        if ($login_fabrica == 165) {
            $left_tecnico = "LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_os.tecnico";
            $campo_tecnico = " tbl_tecnico.nome AS nome_tecnico,  ";
        }
    }

    if ($login_fabrica == 24 OR $login_fabrica == 74){ //hd_chamado=2588542

        if ( isset($_POST['os_congelada']) OR isset($_GET['os_congelada'])) {
            $os_congelada = $_REQUEST['os_congelada'];

            if($login_fabrica == 74){

                if($os_congelada == 'congelada'){
                    $cond_congelada = " AND  tbl_os_campo_extra.os_bloqueada IS TRUE ";
                }elseif($os_congelada == 'congelar'){//hd_chamado=2588542
                    $cond_congelada = " AND tbl_os.data_fechamento IS NULL
                                    AND tbl_os_campo_extra.os_bloqueada IS NOT TRUE
                                    AND CURRENT_DATE - data_abertura > 5";
                }
            }else{

                if($os_congelada == 'congelada'){
                    $cond_congelada = " AND  tbl_os.cancelada IS TRUE ";
                }elseif($os_congelada == 'congelar'){

                    $cond_congelada = " AND tbl_os.data_fechamento IS NULL
                                    AND tbl_os.cancelada IS NOT TRUE
                                    AND tbl_os.data_abertura::date BETWEEN (current_date - interval '60 days')::date and (current_date - interval '30 days')::date
                                ";
                }
            }
        }else{

            if($login_fabrica == 74){
                $cond_congelada = "  AND tbl_os_campo_extra.os_bloqueada IS NOT TRUE ";
            }else{
                $cond_congelada = "  AND tbl_os.cancelada IS NOT TRUE ";
            }
        }
    }

    $revenda_cnpj = str_replace (".","",$revenda_cnpj);
    $revenda_cnpj = str_replace (" ","",$revenda_cnpj);
    $revenda_cnpj = str_replace ("-","",$revenda_cnpj);
    $revenda_cnpj = str_replace ("/","",$revenda_cnpj);
    //HD 286369: Voltando pesquisa de CNPJ da revenda para apenas 8 dígitos iniciais
    if (strlen ($revenda_cnpj) <> 8 AND strlen ($revenda_cnpj) > 0) {
        $msg = traduz("Digite CNPJ completo para pesquisar");
    }

    if (strlen ($nf_compra) > 0 ) {
        if (($login_fabrica==19) and strlen($nf_compra) > 6) {
            $nf_compra = "0000000" . $nf_compra;
            $nf_compra = substr ($nf_compra,strlen ($nf_compra)-7);
        } elseif($login_fabrica <> 11 and $login_fabrica <> 172) {
            if($login_fabrica == 3 or $login_fabrica == 151 OR $novaTelaOs){
                $nf_compra = $nf_compra;
            }else{
                if(strlen($nf_compra)<=6) {
                    $nf_compra = "000000" . $nf_compra;
                    $nf_compra = substr ($nf_compra,strlen ($nf_compra)-6);
                }
            }
        }
    }


        $HI = "00:00:00";
        $HF = "23:59:59";
        $data_inicio_consulta = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_inicial);
        $data_fim_consulta = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_final);


        /*=== VALIDAÇÃO DE DATA ===*/
        $data_valida ="t";
        if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(@!checkdate($mi,$di,$yi))
            $data_valida = "f";
        }
        if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(@!checkdate($mf,$df,$yf))
            $data_valida = "f";
        }
        if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
        }
        if(strlen($msg_erro)==0){
            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)
            or strtotime($aux_data_final) > strtotime('today')){
                $data_valida  = "f";
            }
        }

        /*=== FIM VALIDAÇÃO DE DATA ===*/
        if(strlen($sua_os) ==0 && strlen($serie) ==0 && strlen($consumidor_cpf) ==0 && strlen($dt) == 0 && strlen($lote) == 0 && $numero_os_sap == 0 && strlen($seu_pedido) == 0){

            $periodo_6meses ="";
            $periodo_12meses ="";
            if(strlen($posto_nome) > 0){

                if(strlen($msg) ==0){
                    if(strlen($data_inicial) > 0 && strlen($data_final) > 0 || strlen($os_aberta) ==0 || strlen($consumidor_nome) ==0 || strlen($produto_referencia) ==0){
                        if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

                            if(strlen($produto_referencia) ==0){
                                if ($login_fabrica == 131 AND strlen($codigo_posto) > 0){
                                    $sqlX = "SELECT '$data_inicio_consulta'::date + interval '12 months' > '$data_fim_consulta'";
                                    $resX = pg_query($con,$sqlX);
                                    $periodo_12meses = pg_fetch_result($resX,0,0);
                                    if($periodo_12meses == 'f'){
                                        $msg = traduz("AS DATAS DEVEM SER NO MÁXIMO 12 MESES");
                                    }
                                }else{
                                    if(isset($_REQUEST['finalizada_index']) AND $_REQUEST['finalizada_index'] != ''){
                                        $sqlX = "SELECT '$data_inicio_consulta'::date + interval '6 months' < '$data_fim_consulta 23:59:59'";
                                    }else{
                                        $sqlX = "SELECT '$data_inicio_consulta'::date + interval '6 months' > '$data_fim_consulta 23:59:59'";
                                    }
                                        $resX = pg_query($con,$sqlX);
                                        $periodo_6meses = pg_fetch_result($resX,0,0);
                                        if($periodo_6meses == 'f'){
                                            $msg = traduz("AS DATAS DEVEM SER NO MÁXIMO 6 MESES");
                                        }
                                }
                            }

                            #$conds_sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
                        }else{
                            if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

                                if(strlen($produto_referencia) ==0){
                                    $sqlX = "SELECT '$data_inicio_consulta'::date + interval '12 months' > '$data_fim_consulta'";
                                    $resX = pg_query($con,$sqlX);
                                    $periodo_12meses = pg_fetch_result($resX,0,0);
                                    if($periodo_12meses == 'f' && $posto_nome == '' ){
                                        $msg = traduz("AS DATAS DEVEM SER NO MÁXIMO 12 MESES");
                                    }
                                }

                                $conds_sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
            			    }else{
            				    if($login_fabrica == 131 AND strlen ($codigo_posto) > 0){
            					    if($data_tipo == "abertura"){
            						    $conds_sql .= " AND tbl_os.data_abertura BETWEEN CURRENT_DATE - INTERVAL '12 months' and CURRENT_DATE";
            					    }else{
                                        $conds_sql .= " AND tbl_os.data_digitacao BETWEEN CURRENT_DATE - INTERVAL '12 months' and CURRENT_DATE";
            					    }
            				    }else{
                                    $msg = traduz("INFORME A DATA INICIAL E FINAL PARA PESQUISA");
            				    }
            			    }

                        }
                    }else{
                       $msg = traduz("PREENCHA MAIS CAMPOS PARA REALIZAR A PESQUISA!!!");
                    }
                }

            }else{

                if(strlen($msg) ==0){
                    if(strlen($data_inicial) > 0 && strlen($data_final) > 0 && strlen($produto_referencia) ==0){

						$meses_pesquisa = '6';

						if ($login_fabrica == 148) {
							$meses_pesquisa = '12';
						}

                        $label_mes = "MESES";
                        
                        if ($login_fabrica == 158) {
                            $qtde_campos = 0;
                            foreach ($_REQUEST as $key => $value) {
                                if (!empty($value) && !in_array($key, ['formato_excel','btn_acao','token_form','data_inicial','data_final','data_tipo','pagina'])) {
                                    $qtde_campos++;
                                }
                            }

                            if ($qtde_campos == 0) {
                                $meses_pesquisa = '1';
            			         $label_mes = "MÊS";
                            }

                        }

                        if($login_fabrica == 187){
                            $sql_distinct = " DISTINCT ";
                        }

                        if(isset($_REQUEST['finalizada_index']) AND $_REQUEST['finalizada_index'] != ''){
                            $sqlX = "SELECT " . $sql_distinct . "'$data_inicio_consulta'::date + interval '6 months' <= '$data_fim_consulta'";
                        }else{
                            $sqlX = "SELECT " . $sql_distinct . "'$data_inicio_consulta'::date + interval '$meses_pesquisa months' >= '$data_fim_consulta'";
                        }

                        if(isset($_REQUEST['finalizada_index']) AND $_REQUEST['finalizada_index'] != '' AND in_array($login_fabrica,[169,170])){
                            $sqlX = "SELECT '$data_inicio_consulta'::date + interval '6 months' > '$data_fim_consulta 23:59:59'";
                        }

                        $resX = @pg_query($con,$sqlX);

                        $periodo_6meses = @pg_fetch_result($resX,0,0);
                        if($periodo_6meses == 'f'){
                            $msg = traduz("AS DATAS DEVEM SER NO MÁXIMO % %", null, null, [$meses_pesquisa, $label_mes]);
                        }

			            if ($data_tipo == "abertura") {
							if($login_fabrica == 158)  {
								$tipo_data_imbera = "data_abertura";
								$tipo_data_imbera = (!isset($_POST['unidadenegocio'])) ? 'data_hora_abertura': 'data_abertura';
								$tipo_data_imbera = ($tipo_garantia == 'garantia' or !empty($login_cliente_admin)) ? 'data_abertura': $tipo_data_imbera;

								$codicao_data_temp = "
                                AND
                                CASE
                                    WHEN tbl_os.$tipo_data_imbera IS NULL
                                    THEN tbl_os.data_abertura ELSE
                                    tbl_os.$tipo_data_imbera
                                END
                                BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
							}else{
								$codicao_data_temp = " AND   tbl_os.data_abertura BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
							}
                        } else if ($data_tipo == "integracao") {
                            $codicao_data_temp = " AND   tbl_routine_schedule_log.create_at BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
                        } else {
                            if($login_fabrica == 50 && $_GET["dashboard"] == "sim"){
                                $conds_sql .= " AND tbl_os.data_abertura BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
                            }else{
                            }
                        }
                        if($login_fabrica == 141){
                            if(strlen($consulta_estado) > 0){
                                $conds_sql .= " AND tbl_cidade.estado IN ('$consulta_estado')";
                            }
                        }else{
                            if(strlen($consulta_estado) > 0){
                                if ($login_fabrica != 104) {
                                    $conds_sql .= " AND tbl_posto_fabrica.contato_estado IN ('$consulta_estado')";
                                } else {
                                    $conds_sql .= " AND tbl_os.consumidor_estado IN ('$consulta_estado')";
                                }
                            }

                            if (strlen($consulta_posto_estado) > 0) {
                                $conds_sql .= " AND tbl_posto_fabrica.contato_estado IN ('$consulta_posto_estado')";
                            }

                            if (!empty($cond_tipo_atendimento)) {
                                $conds_sql .= " AND tbl_tipo_atendimento.tipo_atendimento IN ($cond_tipo_atendimento)";   
                            }

                        }
                    }else{
                        if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

                            if(strlen($produto_referencia) > 0){
                                $sqlX = "SELECT '$data_inicio_consulta'::date + interval '12 months' > '$data_fim_consulta'";
                                $resX = @pg_query($con,$sqlX);
                                $periodo_12meses = @pg_fetch_result($resX,0,0);
                                if($periodo_12meses == 'f' && strlen($posto_nome) == 0 && strlen($os_aberta) == 0 && strlen($consumidor_nome) == 0){
                                    $msg = traduz("AS DATAS DEVEM SER NO MÁXIMO 12 MESES");
                                }
            			    }
            				if($data_tipo == "abertura"){
								if($login_fabrica == 158)  {
									$tipo_data_imbera = "data_abertura";
									$tipo_data_imbera = (!isset($_POST['unidadenegocio'])) ? 'data_hora_abertura': 'data_abertura';
									$tipo_data_imbera = ($tipo_garantia == 'garantia' or !empty($login_cliente_admin)) ? 'data_abertura': $tipo_data_imbera;
									$codicao_data_temp = "
                                    AND
                                        CASE
                                            WHEN tbl_os.$tipo_data_imbera IS NULL
                                            THEN tbl_os.data_abertura ELSE
                                            tbl_os.$tipo_data_imbera
                                        END
                                    BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
								}else{
									$codicao_data_temp = " AND   tbl_os.data_abertura BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
								}
							} else if ($data_tipo == "integracao") {
                            } else {
            				}

                        }else{
                            ## HD-2507504 ##
                            if($login_fabrica == 52 AND strlen($hd_chamado_numero) > 0 OR strlen($pre_os) > 0){
                                $msg = "";
                            }else if (in_array($login_fabrica, array(158)) && strlen($os_posto) > 0) {
                                $msg = "";
            			    }else if($login_fabrica == 131 AND strlen ($codigo_posto) > 0){
            				    if($data_tipo == "abertura"){
            				    }else{
            				    }
            			    }else{
                                $msg = traduz("INFORME A DATA INICIAL E FINAL PARA PESQUISA");
                            }

                        }

                    }
                }

            }

        }else{
    		if(strlen($data_inicial) > 0 && strlen($data_final) > 0){
    			if($data_tipo == "abertura"){
					if($login_fabrica == 158)  {
						$tipo_data_imbera = "data_abertura";
						$tipo_data_imbera = (!isset($_POST['unidadenegocio'])) ? 'data_hora_abertura': 'data_abertura';
						$tipo_data_imbera = ($tipo_garantia == 'garantia' or !empty($login_cliente_admin)) ? 'data_abertura': $tipo_data_imbera;
						$codicao_data_temp = "
                                        AND
                                            CASE
                                                WHEN tbl_os.$tipo_data_imbera IS NULL
                                                THEN tbl_os.data_abertura ELSE
                                                tbl_os.$tipo_data_imbera
                                            END
                                        BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
					}else{
						$codicao_data_temp = " AND   tbl_os.data_abertura BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
					}
    			} else if ($data_tipo == "integracao") {
                }else{
    			}
            }
        }

        if($login_fabrica == 141){
            if(strlen($consulta_estado) > 0){
                $conds_sql .= " AND tbl_cidade.estado IN ('$consulta_estado')";
            }#
        }else{
            if(strlen($consulta_estado) > 0){
                if ($login_fabrica != 104) {
                    $conds_sql .= " AND tbl_posto_fabrica.contato_estado IN ('$consulta_estado')";
                } else {
                    $conds_sql .= " AND tbl_os.consumidor_estado IN ('$consulta_estado')";
                }
            }
        }

        if($login_fabrica == 52) {
            //echo "FABRICAS =".$login_fabrica;
            $numero_ativo = trim (strtoupper ($_POST['numero_ativo']));
            if(strlen($numero_ativo)==0) {
                $numero_ativo = trim(strtoupper($_GET['numero_ativo']));
            }
            $cidade_do_consumidor = trim (strtoupper ($_POST['cidade_do_consumidor']));
            if(strlen($cidade_do_consumidor)==0) {
                $cidade_do_consumidor = trim(strtoupper($_GET['cidade_do_consumidor']));
            }
            $hd_chamado_numero = trim (strtoupper ($_POST['hd_chamado_numero']));
            if(strlen($hd_chamado_numero)==0) {
                $hd_chamado_numero = trim(strtoupper($_GET['hd_chamado_numero']));
            }

            if(strlen($numero_ativo) > 0) {
                if(strlen($data_inicio_consulta)> 0  && strlen($data_fim_consulta)> 0) {

                    $sqlp = "SELECT '$data_inicio_consulta'::date + interval '1 months' >= '$data_fim_consulta'";
                    $resp = @pg_query($con,$sqlp);
                    $periodo_ativo_1 = @pg_fetch_result($resp,0,0);
                    if($periodo_ativo_1 == 't') {
                        $conds_sql_ativo = " JOIN  tbl_numero_serie ON tbl_os.produto = tbl_numero_serie.produto AND tbl_os.serie = tbl_numero_serie.serie AND tbl_numero_serie.ordem = '$numero_ativo'";
                    }else {
                        $msg = traduz("AS DATAS DEVEM SER NO MÁXIMO 1 MÊS");
                    }

                }else {
                    $msg = traduz("INFORME A DATA INICIAL E FINAL DENTRO DE UM PERÍODO DE 1 MÊS");
                }
            }

            if(strlen($hd_chamado_numero) > 0) {
                $conds_sql .= " AND tbl_os.hd_chamado = '$hd_chamado_numero'";
            }

            if(strlen($cidade_do_consumidor) > 0) {
                if(strlen($data_inicio_consulta)> 0  && strlen($data_fim_consulta)> 0) {

                    $sqlp = "SELECT '$data_inicio_consulta'::date + interval '1 months' >= '$data_fim_consulta'";
                    $resp = @pg_query($con,$sqlp);
                    $periodo_ativo_1 = @pg_fetch_result($resp,0,0);
                    if($periodo_ativo_1 == 't') {
                        $conds_sql .= " AND tbl_os.consumidor_cidade LIKE '%$cidade_do_consumidor%' ";
                    }else {
                        $msg = traduz("AS DATAS DEVEM SER NO MÁXIMO 1 MÊS");
                    }

                }else {
                    $msg = traduz("INFORME A DATA INICIAL E FINAL DENTRO DE UM PERÍODO DE 1 MÊS");
                }
            }
        }

    //validacao para pegar o posto qdo for digitado a os_off
    if(strlen($os_off)>0){
        if ((strlen($codigo_posto_off)==0) OR (strlen($posto_nome_off)==0)){
            $msg = traduz("Informe o Posto desejado");
        }
    }

    if (strlen($msg) == 0 && strlen($opcao2) > 0) {
        if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
        if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo = trim($_GET["posto_codigo"]);
        if (strlen(trim($_POST["posto_nome"])) > 0) $posto_nome = trim($_POST["posto_nome"]);
        if (strlen(trim($_GET["posto_nome"])) > 0)  $posto_nome = trim($_GET["posto_nome"]);
        if (strlen(trim($_GET["produto_referencia"])) > 0)  $produto_referencia = trim($_GET["produto_referencia"]);

        if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
            $sql_posto =    "SELECT tbl_posto.posto        ,
                            tbl_posto.nome                 ,
                            tbl_posto_fabrica.codigo_posto ,
                            tbl_posto_fabrica.contato_cidade,
                            tbl_posto_fabrica.contato_estado
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica USING (posto)
                    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                    AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
            $res = pg_query($con,$sql_posto);
            if (pg_num_rows($res) == 1) {
                $posto        = trim(pg_fetch_result($res,0,posto));
                $posto_codigo = trim(pg_fetch_result($res,0,codigo_posto));
                $posto_nome   = trim(pg_fetch_result($res,0,nome));
            }else{
                $erro .= traduz(" Posto não encontrado. ");
            }
        }
    }

    if ($login_fabrica == 3) {
        $posto_ordenar = $_POST['posto_ordenar'];
    }
}

if($login_fabrica <> 108 and $login_fabrica <> 111){
$layout_menu = "callcenter";
} else {
$layout_menu = "gerencia";
}
$title = traduz("Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas");


$plugins = array(
   "multiselect"
);

$host = "";

if (strstr($_SERVER['PHP_SELF'],"admin_cliente")) {
    include "../admin_cliente/cabecalho.php";
    $host   = $_SERVER['SCRIPT_NAME'];
    $host   = str_replace('admin_cliente','admin',$host);
    $host   = str_replace('/os_consulta_lite.php','',$host)."/";
} else {
    include "cabecalho.php";
}

include ADMCLI_BACK."plugin_loader.php";

?>

<style type="text/css">

.titulo_coluna > th, tbody > tr > td {
    padding: 0px !important;
}

th.headerSortUp {
    /*background-image: url(imagens/asc.gif);*/
    background-position: right center;
    background-repeat: no-repeat;
    background-color: #596d9b !important;
}
th.headerSortDown {
    /*background-image: url(imagens/desc.gif);*/
    background-position: right center;
    background-repeat: no-repeat;
    background-color: #596d9b !important;
}
th.header {
    font-family: verdana;
    font-size: 11px;
    cursor: pointer;
    background-color: #596d9b !important;
}
.status_checkpoint{width:9px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}
.status_checkpoint_sem{width:15px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}

.legenda_os_cor{width:75px;height:15px;border:1px solid #666;margin:2px 5px;padding:0 5px;}
.legenda_os_texto{
    margin:2px 5px;
    padding:0 5px !important;
    font-weight: bold;
}


.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tablesorter thead tr th, table.tablesorter tfoot tr th{
    border:1px solid #596d9b !important;
    background-color: #596d9b !important;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.subtitulo{

    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
}

#dlg_motivo {
    display: none;
    position: fixed;
    text-align: left;
    top:   30%;
    left:  30%;
    width: 40%;
    padding-top: 32px;
    border: 2px solid #999999;
    background-color: #FFFFFF;
    border-radius: 8px;
    -moz-border-radius: 8px;
    -webkit-border-radius: 8px;
    overflow: hidden;
    z-index: 100;
}

.dlg_motivo {
    width: 25% !important;
}

#dlg_motivo #motivo_header {
    position: absolute;
    top:    0;
    left:   0;
    margin: 0;
    width: 100%;
    height: 20px;
    text-align: center;
    background-color: #596D9B;
    padding: 2px 1em;
    color: #FFFFFF;
    font-size: 12px;
    font-weight: bold;
}
#dlg_motivo #motivo_container {
    margin: 0;
    padding: 20px 2em;
    overflow-y: auto;
    overflow-x: hidden;
    background-color: #FFFFFF;
    color: #000000;
}
#dlg_motivo #dlg_fechar {
    position: absolute;
    top: 3px;
    right: 5px;
    width: 16px;
    height:16px;
    font: normal bold 12px Verdana, Arial, Helvetica, sans-serif;
    color:white;
    cursor: pointer;
    margin:0;padding:0;
    vertical-align:top;
    text-align:center;
    color: #FFFFFF;
    background-color: #FF0000;
}
#dlg_motivo #dlg_btn_excluir,
#dlg_motivo  #dlg_btn_cancel {
    float: right;
    margin: 5px;
}
#dlg_motivo input {
    display: block;
    width: 100%;
}
.ms-choice{
    border-radius: 0px !important;
    height: 18px !important;
        border: 1px solid #888888 !important;
}
.ms-choice > div{
    top: -4px !important;
}

#content {
    min-width: 100vh;
}

/*#content tbody, #content thead
{
    display: block;
}

#content tbody
{
   overflow: auto;
   max-height: 400px;
}*/

</style>
<?php

include "javascript_pesquisas_novo.php";
include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>

<link rel="stylesheet" href="<?=$host?>css/multiple-select.css" />
<script src="<?=$host?>js/jquery.multiple.select.js"></script>
<script type="text/javascript" src="<?=$host?>js/tablesorter.min.js"></script>
<script language="javascript">
    $(document).ready(function()
    {

        $("#familia_s").multipleSelect({
                width: '50%',
                selectAllText: '<?=traduz('Selecionar todos')?>',
                allSelected: '<?=traduz('Todos selecionados')?>',
                countSelected: '<?=traduz('# de % selecionado')?>',
                noMatchesFound: '<?=traduz('Nenhum registro encontrado')?>',

        });

        $(".selectUnidade").multipleSelect({
                width: '100%',
                selectAllText: '<?=traduz('Selecionar todos')?>',
                allSelected: '<?=traduz('Todos selecionados')?>',
                countSelected: '<?=traduz('# de % selecionado')?>',
                noMatchesFound: '<?=traduz('Nenhum registro encontrado')?>',

        });
        Shadowbox.init();
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
        $("#data_inicial").mask("99/99/9999");
        $("#data_final").mask("99/99/9999");
        $(".intervencao").click(function()
        {
            var os     = $(this).attr('id');
            var sua_os = $(this).attr('rel');

            if( confirm("Deseja colocar a OS "+sua_os+ " em intervenção Juridica?") )
            {
                $.ajax({
                    type    : 'POST',
                    url     : "<?php echo $_SERVER['PHP_SELF']; ?>",
                    data    : "ajax=ajax&acao=intervencao&os="+os,
                    success : function(data){
                        if(data == 1){
                            $("#"+os).fadeOut();
                            $("#"+os).parent().parent().css('background-color','#FFCCCC');
                        }else
                            alert('<?=traduz("Erro ao colocar OS em intervenção!")?>');
                    }
                });
            }
            return ;
        });
    });

    function disp_prompt(os, sua_os,rec="")
    {
        if (sua_os.length == 0) {
            sua_os = os;
        }
        var motivo = prompt("Qual o Motivo da Exclusão da os "+sua_os+"? (Máx 150 caracteres)",rec,"Motivo da Exclusão");
        if (motivo != null && $.trim(motivo) != "" && (motivo.length > 0 && motivo.length < 151)) {
            var url = '<?=$PHP_SELF?>'+'?excluir='+os+"&motivo="+motivo;
            window.location = url;
        } else {
            var msg_erro;
            var diff = 0;
            if (motivo.length == 0) {
                msg_erro = '<?=traduz("Digite um motivo por favor!")?>';
            } else {
                diff = parseInt(motivo.length) - 150;
                msg_erro = '<?=traduz("Você ultrapassou ")?>' + diff + '<?=traduz(" caracteres.").' '.traduz("Digite um motivo dentro do limite!")?>';
            }
            alert(msg_erro,'Erro');

            if (motivo.length > 0) {
                disp_prompt(os,sua_os,motivo);
            }
        }
    }

<?php
if (in_array($login_fabrica,array(30,35,72,74,148,178))) {
?>

function reverterOs(os,acao)
{
    var motivo;
    motivo = prompt('<?=traduz("Digite o motivo para reverter (cancelamento) a OS")?>');
    
    if (motivo.length > 0) {
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:"reverter_os",
                os:os,
                acao:acao,
                motivo:motivo
            }
        })
        .done(function(data){
            if(data.result == "ok"){               
                alert('<?=traduz("Operação realizada com sucesso")?>');                
                $("#td_"+os).html("");
            }
        })
        .fail(function(){
            alert('<?=traduz("Não foi possível realizar a operação.")?>');
        });
    }
}

function cancelarOs(os,acao)
{
    var motivo;

    if(acao == "cancelar"){
        motivo = prompt('<?=traduz("Digite o motivo do cancelamento da OS")?>');
    }else{
        motivo = prompt('<?=traduz("Digite o motivo da reabertura da OS")?>');
    }

    if (motivo.length > 0) {
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:"cancelar_os",
                os:os,
                acao:acao,
                motivo:motivo
            }
        })
        .done(function(data){
            if(data.result == "ok"){
                if (acao == "liberar" || acao == "reabrir") {
                    alert('<?=traduz("OS reaberta com sucesso")?>');
                } else {
                    alert('<?=traduz("OS cancelada com sucesso")?>');
                }
                $("#td_"+os).html("");
            }
        })
        .fail(function(){
            alert('<?=traduz("Não foi possível realizar a operação.")?>');
        });
    } else {
        if (acao == "cancelar") {
            alert('<?=traduz("Por favor informar o motivo para o cancelamento da OS. ")?>');
        } else {
            alert('<?=traduz("Por favor informar o motivo para a reabertura da OS. ")?>');
        }
    }
}

<?
}
?>

    function escondeColuna()
    {
        if ($("td[rel='esconde_coluna']").css('display') != 'none') {
            $("td[rel='esconde_coluna']").hide();
            $('#esconde').html('<?=traduz('Mostrar Colunas')?>');
        } else {
            $("td[rel='esconde_coluna']").show();
            $('#esconde').html('<?=traduz('Esconder Colunas')?>');
        }

    }

$().ready(function() {

    $("#content").tablesorter({decimal: ",", dateFormat: "uk"});

    function formatItem(row) {
        return row[2] + " - " + row[1];
    }

    /* OFFF Busca pelo Código */
    $("#codigo_posto_off").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[2];}
    });

    $("#codigo_posto_off").result(function(event, data, formatted) {
        $("#posto_nome_off").val(data[1]) ;
    });

    /* Busca pelo Nome */
    $("#posto_nome_off").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[1];}
    });

    $("#cidade").autocomplete("<?echo $PHP_SELF.'?tipo_busca=consumidor_cidade&busca=consumidor_cidade'; ?>", {
        minChars: 3,
        delay: 150,
        width: 205,
        matchContains: true,
        formatItem: function(row) {
            return row[0];
        },
        formatResult: function(row) {
            return row[0];
        }
    });

    $("#posto_nome_off").result(function(event, data, formatted) {
        $("#codigo_posto_off").val(data[2]) ;
        //alert(data[2]);
    });


    /* Busca pelo Código */
    $("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[2];}
    });

    $("#codigo_posto").result(function(event, data, formatted) {
        $("#posto_nome").val(data[1]) ;
    });

    /* Busca pelo Nome */
    $("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[1];}
    });

    $("#posto_nome").result(function(event, data, formatted) {
        $("#codigo_posto").val(data[2]) ;
        //alert(data[2]);
    });


    /* Busca por Produto */
    $("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[1];}
    });

    $("#produto_descricao").result(function(event, data, formatted) {
        $("#produto_referencia").val(data[2]) ;
    });

    /* Busca pelo Nome */
    $("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[2];}
    });

    $("#produto_referencia").result(function(event, data, formatted) {
        $("#produto_descricao").val(data[1]) ;
        //alert(data[2]);
    });

});

<?php 
if($login_fabrica == 1){
?>

function verifica_pedido_os(os)
{

    if(os != ""){

        $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            type: "POST",
            dataType:"JSON",
            data: {
                os : os,
                verifica_pedido_os : true
            },
            complete: function(data){

                console.log(data);

                if(data.status == true){
                    alert('<?=traduz("OS sem pendências de Pedido")?>');
                } else {

                    var link = "faturar_pedido_os.php?os="+os;

                    window.open(link, "_blank");

                }

            }
        });

    }

}

<?php
}
?>

<?php
    if($login_fabrica == 137){
    ?>

        function motivoExclusao(hd_chamado){

            Shadowbox.open({
                content: '<div style="width: 90%; padding: 20px;"> \
                            <h1>Informe o Motivo</h1> <br /> \
                            <textarea name="motivo_exclusao" class="input" id="motivo_exclusao" cols="50" rows="6"></textarea> <br /> \
                            <button onClick="exclui_hd_chamado('+hd_chamado+')"> Cadastrar Motivo</button> \
                        </div>',
                player: "html",
                options: {
                    enableKeys: false
                },
                title: '<?=traduz("Motivo de Exclusão")?>',
                width: 500,
                height: 250
            });

        }

    <?php
    }
?>
function zerar_mao_obra(os)
{
    var motivo = "";

    <?php if ($login_fabrica != 101) {?>
    motivo = $("#motivo_zerar").val();

    if(motivo == ""){
        alert('<?=traduz("Por favor digite o Motivo de Exclusão")?>');
        $('#motivo_exclusao').focus();
        return;
    }

    window.parent.Shadowbox.close();
    <?php }?>

    $.ajax({
        url:"os_consulta_lite.php",
        type:"POST",
        dataType:"JSON",
        data:{
            ajax:true,
            zerar_mo:os,
            motivo:motivo
        }
    })
    .done(function(data){
        if (data.ok) {
            alert('<?=traduz("Mão-de-obra da OS ")?>' + os + '<?=traduz(" entrará zerada no extrato.")?>');
            $("#zerar_mo_"+os).css("display","none");
            $(".btn-zerar-mo-"+os).css("display","none");
        }
    })
    .fail(function(){
        alert('<?=traduz("Erro ao zerar Mão-de-obra")?>');
    });
}

function exclui_hd_chamado(hd_chamado) {

    var motivo = "";

    <?php
        if($login_fabrica == 137){
            ?>
            motivo = $('#motivo_exclusao').val();
            if(motivo == ""){
                alert('<?=traduz("Por favor digite o Motivo de Exclusão")?>');
                $('#motivo_exclusao').focus();
                return;
            }

            window.parent.Shadowbox.close();

            <?php
        }
    ?>

    $.ajax({
        url: "<?php echo $_SERVER['PHP_SELF']; ?>",
        type: 'post',
        data: { exclui_hd_chamado : hd_chamado, motivo : motivo },
        complete: function(res){
            var data = res.responseText;
            if(data == "success"){
                alert('<?=traduz("Pré-OS do Atendimento ")?>' + hd_chamado + '<?=traduz(" Excluído com Sucesso!")?>');
                $('#div_atendimento_'+hd_chamado).remove();
            }else{
                alert('<?=traduz("Erro ao Excluir a Pré-OS ")?>' + hd_chamado);
            }
        }
    });

}

function _trim (s)
{
   //   /            open search
   //     ^            beginning of string
   //     \s           find White Space, space, TAB and Carriage Returns
   //     +            one or more
   //   |            logical OR
   //     \s           find White Space, space, TAB and Carriage Returns
   //     $            at end of string
   //   /            close search
   //   g            global search

   return s.replace(/^\s+|\s+$/g, "");
}

function retornaFechamentoOS (http , sinal, excluir, lancar) {
    if (http.readyState == 4) {
        if (http.status == 200) {
            results = http.responseText.split(";");
            if (typeof (results[0]) != 'undefined') {
                if (_trim(results[0]) == 'ok') {
                    alert ("OS fechada com sucesso");

                    if (sinal != undefined) {
                        sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
                        sinal.src='../imagens/pixel.gif';
                    }

                    if (excluir != undefined) {
                        excluir.src='../imagens/pixel.gif';
                    }

                    if (lancar != undefined) {
                        lancar.src='../imagens/pixel.gif';
                    }

                    return true;
                }else{

                    if (http.responseText.indexOf ('de-obra para instala') > 0) {
                        alert ('<? echo("esta.os.nao.tem.mao-de-obra.para.instalacao") ?>');
                    }else if (http.responseText.indexOf ('Nota Fiscal de Devol') > 0) {
                        alert ('<? echo("por.favor.utilizar.a.tela.de.fechamento.de.os.para.informar.a.nota.fiscal.de.devolucao") ?>');
                    }else if (http.responseText.indexOf ('o-de-obra para atendimento') > 0) {
                        alert ('<? echo("esta.os.nao.tem.mao-de-obra.para.este.atendimento") ?>');
                    }else if (http.responseText.indexOf ('Favor informar aparência do produto e acessórios') > 0) {
                        alert ('<? echo("por.favor.verifique.os.dados.digitados.aparencia.e.acessorios.na.tela.de.lancamento.de.itens") ?>');
                    }else if (http.responseText.indexOf ('Type informado para o produto não é válido') > 0) {
                        alert ('<? echo("type.informado.para.o.produto.nao.e.valido") ?>');
                    }else if (http.responseText.indexOf ('OS com peças pendentes') > 0) {
                        alert ('<? echo("os.com.pecas.pendentes,.favor.informar.o.motivo.na.tela.de.fechamento.da.os") ?>');
                    }else if(http.responseText.indexOf ('OS não pode ser fechada, Favor Informar a Kilometragem') > 0){
                        alert ('<? echo("os.nao.pode.ser.fechada,.favor.informar.a.kilometragem") ?>');
                    }else if (http.responseText.indexOf ('OS não pode ser fechada, Kilometragem Recusada') > 0){
                        alert ('<? echo("os.nao.pode.ser.fechada,.kilometragem.recusada") ?>');
                    }else if (http.responseText.indexOf ('OS não pode ser fechada, aguardando aprovação de Kilometragem') > 0){
                        alert ('<? echo("os.nao.pode.ser.fechada,.aguardando.aprovacao.de.kilometragem") ?>');
                    }else if (http.responseText.indexOf ('Esta OS teve o número de série recusado e não pode ser finalizada') > 0){
                        alert ('<? echo("esta.os.teve.o.numero.de.serie.recusado.e.nao.pode.ser.finalizada") ?>');
                    }else if (http.responseText.indexOf ('Informar defeito constatado (Reparo) para OS') > 0){
                        alert ('<? echo("por.favor.verifique.os.dados.digitados.em.defeito.constatado.(reparo).na.tela.de.lancamento.de.itens") ?>');
                    }else if (http.responseText.indexOf ('Por favor, informar o conserto do produto na tela CONSERTADO') > 0){
                        alert ('<? echo("por.favor.informar.o.conserto.do.produto.na.tela.consertado") ?>');
                    }else if (http.responseText.indexOf ('pois pedido foi faturado a menos de sete dias') > 0){
                        alert ('<? echo("os.nao.pode.ser.fechada,.faturamento.menos.sete.dias") ?>');
                    }else if (http.responseText.indexOf ('pois pedido não foi faturado') > 0){
                        alert ('<? echo("os.nao.pode.ser.fechada.nao.possui.faturamento") ?>');
                    }else if (http.responseText.indexOf ('pois não há pedido gerado') > 0){
                        alert ('<? echo("os.nao.pode.ser.fechada.nao.possui.pedido") ?>');
                    } else if (http.responseText.indexOf ('preencher o Check List') > 0) {
						alert ('Para finalizar a OS é preciso preencher o Check List');
					} else if (http.responseText.indexOf ('o motivo da reinci') > 0) {
                        alert ('Informe o motivo da reincidencia da OS');
                    } else if (http.responseText.indexOf ('Não foi informado número de série, não pode finalizar') > 0) {
                        alert ('Não foi informado número de série, a OS não pode ser finalizada!');
                    } else if (http.responseText.indexOf ('Favor informar o defeito constatado para a ordem de serviço') > 0){
                        alert ('Favor informar o defeito constatado para a ordem de serviço');
                    } else if (http.responseText.indexOf ('Esta OS está em intervenção e não pode ser finalizada') > 0) {
                        alert ('Esta OS está em intervenção e não pode ser finalizada');
                    } else if (http.responseText.indexOf("os.com.intervencao,.nao.pode.ser.fechada") > 0) {
                        alert ('OS com intervenção, não pode ser fechada');
                    } else if (http.responseText.indexOf("OS não pode ser fechada, pois o pedido de peça está pendente") > 0) {
                        alert ("OS não pode ser fechada, pois o pedido de peça está pendente");
                    } else {
                        alert (http.responseText);
                    }
                }
            }else{
                alert ('<? echo traduz("fechamento.nao.processado") ?>');
            }
        }
    }
}

$(function () {

    $("#content .titulo_coluna > th").css({'white-space': 'nowrap', 'height' : '30px'}).append(" &#9660;");

    $("#seleciona_todas_os_excluida").change(function() {
        if ($(this).is(":checked")) {
            $("input[type=checkbox][name='exclui_os[]']").each(function() {
                $(this)[0].checked = true;
            });
        } else {
            $("input[type=checkbox][name='exclui_os[]']").each(function() {
                $(this)[0].checked = false;
            });
        }
    });

    $("#button_exclui_os").click(function() {
        var motivo = $.trim($("#motivo_exclui_os").val());

        if (motivo.length == 0) {
            alert('<?=traduz("Informe o motivo para excluir")?>');
        } else {
            $("#form_exclui_os").submit();
        }
    });

    $("img[name^=fechar_os_30_dias_]").click(function () {
        if (confirm("Deseja realmente fechar a OS ?")) {
            var os = $(this).attr("rel");
            var i  = $(this).next("input[name=i]").val();

            window.open("fechar_os_30_dias.php?os="+os+"&posicao="+i, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
        }
    });

    $("button[id^=zerar_mo_]").click(function(e){
        e.preventDefault();
        var aux = $(this).attr("id");
        var aux2 = aux.split("_");
        var os = aux2[2];

        Shadowbox.open({
            content: '<div style="width: 90%; padding: 20px;"> \
                        <h1><?=traduz("Informe o Motivo")?></h1> <br /> \
                        <textarea name="motivo_zerar" class="input" id="motivo_zerar" cols="50" rows="6"></textarea> <br /> \
                        <button onClick="zerar_mao_obra('+os+')"> Zerar Mão-de-Obra</button> \
                    </div>',
            player: "html",
            options: {
                enableKeys: false
            },
            title: '<?=traduz("Zerar Mão-de-Obra")?>',
            width: 500,
            height: 250
        });

    });
});

function fecha_os_30_dias (os, i, motivo, sem_pagamento) {
    var date = new Date();

    var url = "<?=$_SERVER['PHP_SELF']?>?fechar="+os+"&motivo="+motivo+"&dt="+date;

    if (sem_pagamento != undefined && sem_pagamento == true) {
        url += "&sem_pagamento=true";
    }

    http.open("GET", url, true);
    http.onreadystatechange = function () {
        if (retornaFechamentoOS(http) == true) {
            $("img[name=fechar_os_30_dias_"+i+"]").remove();
        }
    };
    http.send(null);
}

function fechaOS (os , sinal , excluir , lancar ) {
    var curDateTime = new Date();
    url = "<?= $PHP_SELF ?>?fechar=" + escape(os) + '&dt='+curDateTime;
    http.open("GET", url , true);
    http.onreadystatechange = function () { retornaFechamentoOS (http , sinal, excluir, lancar) ; } ;
    http.send(null);
}

function fechaOSTermo (os) {

    $.ajax({
        url: "<?=$PHP_SELF?>",
        type: 'POST',
        data: {fechar: os},
    })
    .done(function(data) {
        if (data == 'ok') {
            alert('<?=traduz("OS fechada com sucesso")?>');
            location.reload();
        } else {
            alert(data);
        }
    });
}

function selecionarTudo(){
    $('input[@rel=imprimir]').each( function (){
        this.checked = !this.checked;
    });
}

function imprimirSelecionados(){
    var qtde_selecionados = 0;
    var linhas_seleciondas = "";
    $('input[@rel=imprimir]:checked').each( function (){
        if (this.checked){
            linhas_seleciondas = this.value+", "+linhas_seleciondas;
            qtde_selecionados++;
        }
    });

    if (qtde_selecionados>0){
        janela = window.open('os_print_selecao.php?lista_os='+linhas_seleciondas,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=850,height=600,top=18,left=0");
    }
}

function aprovaOrcamento(sua_os, num_os,opcao){

    if(confirm("Deseja "+opcao+" a OS : "+sua_os)){
        $.post('../admin/ajax_aprova_orcamento.php',{os : num_os, op : opcao},
            function (resposta){
                if(resposta === "OK"){
                    if(opcao=="Aprovar"){
                        $("#st_ch_"+sua_os).css('background','#3BAD48');
                        alert('<?=traduz("Orçamento aprovado com sucesso")?>');
                        //$('#aprovar_'+num_os).parent().parent().css('background','#33CC00');
                    }else{
                        //$('#reprovar_'+num_os).parent().parent().css('background','#C94040');
                        $("#st_ch_"+sua_os).css('background','#6E54FF');
                        alert('<?=traduz("Orçamento Reprovado com sucesso")?>');
                    }
                    $('#aprovar_'+num_os).remove();
                    $('#reprovar_'+num_os).remove();
                }else{
                    alert(resposta);
                }
        });
    }
}

function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
        gravaDados("codigo_posto",codigo_posto);
        gravaDados("posto_nome",nome);
        <?if ($login_fabrica == 19 || $login_fabrica == 10)
        {?>
        gravaDados("codigo_posto_off",codigo_posto);
        gravaDados("posto_nome_off",nome);
        <?}?>
}
function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria,posicao){
        gravaDados("produto_referencia",referencia);
        gravaDados("produto_descricao",descricao);
}

<?php
if ($cancelaOS || in_array($login_fabrica, [30,91])) {
?>
    function cancelar_os(os, btn, continuar) {

        if (typeof continuar == "undefined" || continuar == null) {
            continuar = false;
        }

        if (continuar || confirm('<?=traduz("Deseja realmente cancelar a Ordem de Serviço ?")?>')) {
            if (continuar) {
                var mensagem = continuar;
            } else {
                var mensagem = prompt('<?=traduz("Informe o motivo do cancelamento")?>');

                if (mensagem == null) {
                    return false;
                }

                mensagem = $.trim(mensagem);

                 if (mensagem.length == 0) {
                    alert('<?=traduz("É necessário informar o motivo para cancelar a ordem de serviço")?>');
                    return false;
                }
            }

            if (!continuar) {
                $(btn).hide();
                $(btn).after('<?=traduz("<label>Cancelando...</label>")?>');
            }

            $.ajax({
               url: window.location,
               type: "post",
               data: { ajax_cancelar_os: true, os: os, mensagem: mensagem, continuar: continuar },
               timeout: 60000
            }).fail(function(res) {
                alert('<?=traduz("Erro ao cancelar a ordem de serviço, tempo limite esgotado.")?>');
                $(btn).next("label").remove();
                $(btn).show();
            }).done(function(res) {
                res = JSON.parse(res);

                if (res.erro) {
                    alert(res.erro);
                    $(btn).next("label").remove();
                    $(btn).show();
                } else if (res.continuar) {
                    if (confirm(res.continuar)) {
                        cancelar_os(os, btn, mensagem);
                    } else {
                        $(btn).next("label").remove();
                        $(btn).show();
                    }
                } else {
                    $(btn).next("label").text('<?=traduz("Cancelada")?>');
                    $(btn).parents("td").prevAll().each(function() {
                        var id = $(this).attr("id");

                        if (typeof id == "undefined" || id == null) {
                            return;
                        }

                        if (id.match(/^(td_alterar)|(td_trocar)/))  {
                            $(this).html("&nbsp;");
                        }
                    });
                    $(btn).parents("tr").find("td:first").find(".status_checkpoint").css({ "background-color": "#FF0000" });
                    $(btn).remove();
                }
            });
        }
    }

    function reabrir_os(os, btn) {
        if (confirm('<?=traduz("Deseja realmente reabrir a Ordem de Serviço ?")?>')) {
            var mensagem = prompt("Informe o motivo para reabrir");

            if (mensagem == null) {
                return false;
            }

            mensagem = $.trim(mensagem);

            if (mensagem.length == 0) {
                alert('<?=traduz("É necessário informar o motivo para reabrir a ordem de serviço")?>');
                return false;
            }

            $(btn).hide();
            $(btn).after('<?=traduz("<label>Aguarde...</label>")?>');

            $.ajax({
               url: window.location,
               type: "post",
               data: { ajax_reabrir_os: true, os: os, mensagem: mensagem },
               timeout: 60000
            }).fail(function(res) {
                alert('<?=traduz("Erro ao reabrir a ordem de serviço, tempo limite esgotado.")?>');
                $(btn).next("label").remove();
                $(btn).show();
            }).done(function(res) {
                res = JSON.parse(res);

                if (res.erro) {
                    alert(res.erro);
                    $(btn).next("label").remove();
                    $(btn).show();
                } else {
                    $(btn).next("label").text('<?=traduz("Reaberta")?>');
                    $(btn).parents("tr").attr({ "bgcolor": "#FFFFFF" }).find("td:first").find(".status_checkpoint").css({ "background-color": "#FF8282" });
                    $(btn).remove();
                }
            });
        }
    }
<?php
}

?>
$(document).ready(function() {
    f5();

    $("input[name=btn_acao]").click(function (){
        esconderCampos();
    });
    $(window).load( function() {
        esconderCampos();
    });

    window.onload = function() {
        mostrarCampos();
    };
});

function esconderCampos() {
    $("input[name=btn_aux_acao]").val("enviado");
    $("input[name=btn_acao]").hide();
    $("input[name=gerar_excel]").hide();
    $("label[name=lbl_gerar_excel]").hide();
    $("select[name=formato_excel]").hide();
}

function mostrarCampos() {
    $("input[name=btn_aux_acao]").val("");
    $("input[name=btn_acao]").show();
    $("input[name=gerar_excel]").show();
    $("label[name=lbl_gerar_excel]").show();
    $("select[name=formato_excel]").show();
}

function f5() {
    'use strict';

    document.addEventListener('keydown', (event) => {
        const keyName = event.key;
        if (keyName == 'F5') {
            var btn_aux_acao = $("input[name=btn_aux_acao]").val();
            if (btn_aux_acao == "enviado") {
                alert('<?=traduz("Favor aguardar gerar o relatório para atualizar a página.")?>');
            } else {
                esconderCampos();
            }
        }
    });
}

</script>
<!-- HD-4100449 -->
<input type="hidden" name="btn_aux_acao" value="">
<?
$pre_os = $_POST['pre_os'];

if (strlen($pre_os)>0) {
    if(strlen($btn_acao_pre_os) == 0 or empty($btn_acao_pre_os)) {
        $msg = traduz(" Para consultar por número de atendimento, favor clicar em Pesquisar Pré-OS" );
    }
}

if (isset($_POST["acao_exclui_os"]) && strlen($msg_erro_excluir) > 0) {
    echo "<div class='msg_erro' style='width: 700px; margin: 0 auto;' >".traduz($msg_erro_excluir)."</div>";
}

if (isset($_POST["acao_exclui_os"]) && count($msg_ok_excluir) > 0) {
    echo "<br /><div class='msg_sucesso' style='width: 700px; margin: 0 auto; font: bold 16px Arial; color: #FFF; text-align: center; background-color: #438900;' >".traduz("OS(s) excluidas com sucesso:")." ".implode(", ", $msg_ok_excluir)."</div>";
}

if (in_array($login_fabrica,array(35))) {
    if (!empty($_POST['os_posto']) and empty($_POST['codigo_posto'])) {
        $msg.= traduz('Por favor informe o Posto.<br/>');
    }
}

if(strlen($msg)>0){
    echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
    echo "<tr>";
    echo "<td  class='msg_erro' align='left'> $msg</td>";
    echo "</tr>";
    echo "</table>";
}

if (((strlen($_POST['btn_acao']) > 0 or strlen($_GET['btn_acao']) > 0) AND strlen($msg) == 0)OR strlen($btn_acao_pre_os) > 0) {
    $pre_os = $_POST['pre_os'];

    if (strlen($pre_os)>0) {
        $sql_pre_os = " AND tbl_hd_chamado.hd_chamado = $pre_os";
    }

    if(in_array($login_fabrica, array(164))){

        $periodo_tempo_os = $_GET["periodo"];

        $cond_intervalo = "";

        if(strlen($periodo_tempo_os) > 0){

            list($dia_ant, $dia) = explode("-", $periodo_tempo_os);
			$cond_intervalo = " AND data_conserto::date - data_abertura::date ";
            if($dia == 31){
                $cond_intervalo .= " >= '{$dia}' AND finalizada NOTNULL AND data_conserto NOTNULL ";
            }else{
                $cond_intervalo .= " BETWEEN '{$dia_ant}' AND '{$dia}' AND finalizada NOTNULL AND data_conserto NOTNULL ";
            }

        }

            $estado = $_GET["regiao"];

            switch ($estado) {
                case 'norte':
                    $cond_regiao = " AND tbl_os.consumidor_estado IN ('AC', 'AP', 'AM', 'PA', 'RR', 'RO', 'TO') ";
                    break;
                case 'nordeste':
                    $cond_regiao = " AND tbl_os.consumidor_estado IN ('AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE') ";
                    break;
                case 'centro_oeste':
                    $cond_regiao = " AND tbl_os.consumidor_estado IN ('DF', 'GO', 'MT', 'MS') ";
                    break;
                case 'sudeste':
                    $cond_regiao = " AND tbl_os.consumidor_estado IN ('ES', 'MG', 'RJ', 'SP') ";
                    break;
                case 'sul':
                    $cond_regiao = " AND tbl_os.consumidor_estado IN ('PR', 'RS', 'SC') ";
                    break;
            }
    }

    if (strlen($btn_acao_pre_os) > 0) {

        // Habilitar Filtros para consulta de Pré OS HD2535001
        $dataInicialPreOS = $_POST['data_inicial'];
        if (strlen($dataInicialPreOS) == 0) $dataInicialPreOS = $_GET['data_inicial'];
        $dataFinalPreOS = $_POST['data_final'];
        if (strlen($dataFinalPreOS) == 0) $dataFinalPreOS = $_GET['data_final'];
        $seriePreOS = $_POST['serie'];
        $NFCompraPreOS = $_POST['nf_compra'];
        $consumidorCPFPreOS = $_POST['consumidor_cpf'];
        $consumidorNomePreOS = $_POST['consumidor_nome'];
        $codPostoPreOS = $_POST['codigo_posto'];
        $nomePostoPreOS = $_POST['posto_nome'];
        $produtoRefPreOS = $_POST['produto_referencia'];
        $produtoDescPreOS = $_POST['produto_descricao'];


        $sqlFiltrosPreOS = '';

        if (in_array($login_fabrica, array(11, 169, 170, 172)) && (strlen($dataInicialPreOS) > 0 && strlen($dataFinalPreOS) > 0)) {

            if(!empty($dataInicialPreOS) OR !empty($dataFinalPreOS)){
                list($di, $mi, $yi) = explode("/", $dataInicialPreOS);
                if(!checkdate($mi,$di,$yi))
                    $msg = traduz("Data inicial inválida");

                list($df, $mf, $yf) = explode("/", $dataFinalPreOS);
                if(!checkdate($mf,$df,$yf))
                    $msg = traduz("Data final inválida");

                if(strlen($msg)==0){
                    $auxDataInicialPreOS = "$yi-$mi-$di";
                    $auxDataFinalPreOS = "$yf-$mf-$df";

                    if(strtotime($auxDataInicialPreOS) < strtotime($auxDataFinalPreOS)) {
                        $msg = traduz("Data inicial maior do que a data final");
                    }
                }
            }

            $sqlFiltrosPreOS = " AND tbl_hd_chamado.data BETWEEN to_date('".$auxDataInicialPreOS." 00:00:00', 'YYYY-MM-DD HH24:MI:SS') AND to_date('".$auxDataFinalPreOS." 23:59:59', 'YYYY-MM-DD HH24:MI:SS')";
        }

        if (in_array($login_fabrica, array(11, 35, 172)) && strlen($seriePreOS) > 0) {
            $sqlFiltrosPreOS .= " AND tbl_hd_chamado_extra.serie = '".$seriePreOS."'";
        }

        if (in_array($login_fabrica, array(11, 35, 169, 170, 172)) && strlen($NFCompraPreOS) > 0) {
            $sqlFiltrosPreOS .= " AND tbl_hd_chamado_extra.nota_fiscal = '".$NFCompraPreOS."'";
        }

        if (in_array($login_fabrica, array(11, 35, 169, 170, 172)) && strlen($consumidorCPFPreOS) > 0) {
            $sqlFiltrosPreOS .= " AND tbl_hd_chamado_extra.cpf = '".$consumidorCPFPreOS."'";
        }

        if (in_array($login_fabrica, array(11, 35, 169, 170, 172)) && strlen($consumidorNomePreOS) > 0) {
            $sqlFiltrosPreOS .= " AND upper(tbl_hd_chamado_extra.nome) LIKE upper('%".$consumidorNomePreOS."%')";
        }

        if (in_array($login_fabrica, array(11, 35, 169, 170, 172)) && strlen($codPostoPreOS) > 0) {
            $sqlFiltrosPreOS .= " AND tbl_posto_fabrica.codigo_posto = '".$codPostoPreOS."'";
        }

        if (in_array($login_fabrica, array(11, 35, 169, 170, 172)) && strlen($nomePostoPreOS) > 0) {
            $sqlFiltrosPreOS .= " AND upper(tbl_posto.nome) LIKE upper('".$nomePostoPreOS."')";
        }

        if (in_array($login_fabrica, array(11, 35, 169, 170, 172)) && strlen($produtoRefPreOS) > 0) {
            $sqlFiltrosPreOS .= " AND tbl_produto.referencia = '".$produtoRefPreOS."'";
        }

        if (in_array($login_fabrica, array(11, 35, 169, 170, 172)) && strlen($produtoDescPreOS) > 0) {
            $sqlFiltrosPreOS .= " AND upper(tbl_produto.descricao) LIKE upper('".$produtoDescPreOS."')";
        }
        // Fim - Habilitar Filtros para consulta de Pré OS HD2535001


        if (!in_array($login_fabrica,array(30,52,96,151))) {
            if(strlen($login_cliente_admin)>0){
                $cond_cliente_admin = " AND tbl_hd_chamado.cliente_admin = $login_cliente_admin ";
            }

            if($login_fabrica == 104){//HD-3139131
                $campo_postagem = ", tbl_hd_chamado_postagem.numero_postagem AS cod_postagem ";
                $left_join_postagem = " LEFT JOIN tbl_hd_chamado_postagem ON tbl_hd_chamado_postagem.hd_chamado = tbl_hd_chamado.hd_chamado ";
            }

	    if (!in_array($login_fabrica, array(169, 170))) {
		$whereAtendimentoResolvido = "AND UPPER(tbl_hd_chamado.status) != 'RESOLVIDO'";
		$whereOs = "AND tbl_hd_chamado_extra.os is null";
	    } else {
		$leftJoinTblOs = "LEFT JOIN tbl_os ON tbl_os.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_os.fabrica = $login_fabrica";
		$whereOs = "AND tbl_os.os IS NULL";
	    }

            $sqlinf = "
                SELECT
                    tbl_hd_chamado.hd_chamado, '' as sua_os, tbl_hd_chamado_extra.serie, tbl_hd_chamado_extra.nota_fiscal    ,
                    TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY')   AS data               ,
                    TO_CHAR(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY')            AS data_nf           ,
                    tbl_hd_chamado_extra.posto                                        ,
                    tbl_posto_fabrica.codigo_posto                                    ,
                    tbl_posto_fabrica.credenciamento                                  ,
                    tbl_posto.nome                              AS posto_nome         ,
                    tbl_hd_chamado_extra.fone as consumidor_fone                      ,
                    tbl_hd_chamado_extra.nome                                         ,
                    tbl_hd_chamado_extra.array_campos_adicionais                      ,
                    tbl_marca.nome as marca_nome                                      ,
                    tbl_produto.referencia                                            ,
                    tbl_produto.descricao
                    $campo_postagem
                FROM tbl_hd_chamado_extra
                JOIN tbl_hd_chamado using(hd_chamado)
                LEFT JOIN tbl_produto on tbl_hd_chamado_extra.produto = tbl_produto.produto AND tbl_produto.fabrica_i=$login_fabrica
                LEFT JOIN tbl_marca   on tbl_produto.marca = tbl_marca.marca
                LEFT JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_hd_chamado_extra.posto
                LEFT JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                $left_join_postagem
		$leftJoinTblOs
                WHERE tbl_hd_chamado.fabrica = $login_fabrica
                $cond_cliente_admin
                $sql_pre_os
                $sqlFiltrosPreOS
                AND tbl_hd_chamado_extra.abre_os = 't'
                $whereAtendimentoResolvido
		$whereOs
            ";
        } else {
            if ($login_fabrica == 52) {
                $campo_serie = " , tbl_numero_serie.ordem as ordem_ativo ";
                $join_serie = " LEFT JOIN tbl_numero_serie ON tbl_hd_chamado_item.produto = tbl_numero_serie.produto and tbl_hd_chamado_item.serie = tbl_numero_serie.serie and tbl_numero_serie.fabrica = $login_fabrica";
            }

            $sqlinf = "
                SELECT
                    tbl_hd_chamado.hd_chamado,
                    tbl_hd_chamado_item.hd_chamado_item,
                    '' as sua_os                                                           ,
                    tbl_hd_chamado_item.serie                                              ,
                    tbl_hd_chamado_extra.nota_fiscal                                                            ,
                    TO_CHAR(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY')            AS data_nf           ,
                    TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY')            AS data           ,
                    TO_CHAR(tbl_hd_chamado.data,'YYYY-MM-DD HH24:MI:SS') AS dt_hr_abertura ,
                    tbl_posto_fabrica.codigo_posto                                         ,
                    tbl_posto_fabrica.credenciamento                                       ,
                    tbl_posto.nome                              AS posto_nome              ,
                    tbl_hd_chamado_extra.fone as consumidor_fone                           ,
                    tbl_hd_chamado_extra.nome                                              ,
                    tbl_hd_chamado_extra.tipo_atendimento                                  ,
                    tbl_marca.nome as marca_nome                                           ,
                    tbl_produto.referencia, tbl_produto.descricao
                    $campo_serie
                FROM tbl_hd_chamado
                JOIN tbl_hd_chamado_extra using(hd_chamado)
                LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado and tbl_hd_chamado_item.produto is not null
                LEFT JOIN tbl_produto on (tbl_hd_chamado_item.produto = tbl_produto.produto or tbl_hd_chamado_extra.produto = tbl_produto.produto and tbl_produto.fabrica_i=$login_fabrica)
                LEFT JOIN tbl_marca   on tbl_produto.marca = tbl_marca.marca
                JOIN      tbl_posto         ON  tbl_posto.posto         = tbl_hd_chamado_extra.posto
                LEFT JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                $join_serie
                WHERE tbl_hd_chamado.fabrica = $login_fabrica
                AND tbl_hd_chamado_extra.abre_os = 't'
                ".(($login_fabrica != 96) ? "AND UPPER(tbl_hd_chamado.status) NOT IN ('CANCELADO','RESOLVIDO')" : "")."
                $sql_pre_os
		AND tbl_hd_chamado_item.os is null
		AND tbl_hd_chamado_extra.os is null
            ";

            if ($login_fabrica == 30) {
                if ($cook_cliente_admin_master == 't') {//ADMIN MASTER vê de toda fabrica
                    if(strlen($login_cliente_admin)>0){
                        $sqlinf .= " AND tbl_hd_chamado.cliente_admin = $login_cliente_admin ";
                    }
                } else {//ADMIN vê apenas o que ele cadastrou
                    if(strlen($cook_admin)>0){
                        $sqlinf .= " AND tbl_hd_chamado.admin = $cook_admin";
                    }
                    if(strlen($login_cliente_admin)>0){
                        $sqlinf .= " AND tbl_hd_chamado.cliente_admin = $login_cliente_admin ";
                    }
                }

            } else {
                if(strlen($login_cliente_admin)>0){
                    $sqlinf .= " AND tbl_hd_chamado.cliente_admin = $login_cliente_admin ";
                }
            }

        }

        $res = pg_query ($con,$sqlinf);

        ##### PAGINAÇÃO - INÍCIO #####
        $sqlCount  = "SELECT count(*) FROM (";
        $sqlCount .= $sqlinf;
        $sqlCount .= ") AS count";

        require "_class_paginacao.php";

        // definicoes de variaveis
        $max_links = 11;                // máximo de links à serem exibidos
        $max_res   = 50;                // máximo de resultados à serem exibidos por tela ou pagina
        $mult_pag  = new Mult_Pag();    // cria um novo objeto navbar
        $mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

        #echo "<BR>".nl2br($sql)."<BR>";exit;
        $res = $mult_pag->executar($sqlinf, $sqlCount, $con, "otimizada", "pgsql");

        ##### PAGINAÇÃO - FIM #####

    } else {

        $join_especifico = "";
        $especifica_mais_1 = "1=1";
        $especifica_mais_2 = "1=1";

        if (strlen ($produto_referencia) > 0) {
            $sqlX = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.referencia = '$produto_referencia'";

            $resX = pg_query ($con,$sqlX);
            if (pg_num_rows ($resX) > 0){
                $produto = pg_fetch_result ($resX,0,0);
                $especifica_mais_1 = "tbl_os.produto = $produto";
            }
        }

        if (strlen ($codigo_posto) > 0) {
            $sqlX = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND upper(codigo_posto) = upper('$codigo_posto')";
			$resX = pg_query ($con,$sqlX);
            if (pg_num_rows($resX) > 0) {
				$resultados = pg_fetch_all($resX);
				$postos = array();
				foreach($resultados as $key => $value) {
					$postos[] = $value['posto'];
				}
                $especifica_mais_2 = "tbl_os.posto in ( ". implode(",",$postos) ." ) ";
            }
        }

        $verCortesia = "";
        if (in_array($login_fabrica, array(11, 172, 169, 170)) && $xos_cortesia == 't') {
            $verCortesia = " AND tbl_os.cortesia IS TRUE";
        }

        if($login_fabrica ==50 AND $tipo_os =='OS_COM_TROCA'){ // HD 48198
            $join_troca = " JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os ";
        }

        if($login_fabrica ==45 AND ($tipo_os =='TROCA' OR $tipo_os == 'RESSARCIMENTO')){ //HD 62394 waldir
                $join_troca = " JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os ";
                $join_troca .= ($tipo_os =='TROCA') ? " AND tbl_os_troca.ressarcimento IS FALSE ":" and tbl_os_troca.ressarcimento ";
        }

        if ($login_fabrica == 45){
            if($tipo_os =='RESOLVIDOS'){
                $join_troca = "
                            JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
                            JOIN tbl_faturamento_item USING(pedido,peca)
                ";
            }

            if($tipo_os =='PENDENTES'){
                $join_troca = "
                            JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.ressarcimento IS FALSE
                        LEFT JOIN tbl_faturamento_item USING(pedido,peca)
                ";
                $where_troca = " AND tbl_faturamento_item.faturamento_item isnull ";
            }

        }
        if($login_fabrica==7){
            $HI = "00:00:00";
            $HF = "23:59:59";
        }

        if (strlen($consumidor_revenda_pesquisa)) {
            $condicao_consumidor_revenda = " AND consumidor_revenda='$consumidor_revenda_pesquisa'";
        }

        if (strlen($os_off) > 0) {
            $condicao_os_off = " AND (tbl_os.sua_os_offline = '$os_off') ";
        }

        if (!empty($numero_reclamacao)){
            $cond_numero_reclamacao = " AND tbl_os.sua_os_offline = '$numero_reclamacao' ";
        }

        if (!empty($seu_pedido)) {
            $cond_seu_pedido        = "AND (
                                            SELECT tbl_pedido.pedido
                                            FROM tbl_pedido_item
                                            JOIN tbl_os_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                                            AND fabrica_i = {$login_fabrica}
                                            JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                            JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
                                            AND UPPER(tbl_pedido.seu_pedido) = UPPER('{$seu_pedido}')
                                            AND tbl_pedido.fabrica = {$login_fabrica}
                                            WHERE tbl_os_produto.os = tbl_os.os
                                            LIMIT 1
                                       ) > 0";
        }

        if (strlen($serie) > 0) {
            $condicao_serie = " AND tbl_os.serie = '$serie'";
            if($login_fabrica == 94) {
                $condicao_serie = " AND lpad(tbl_os.serie, 12, '0') = lpad('$serie', 12, '0') ";
            }
        }

        if ($login_fabrica == 158 && !empty($patrimonio)) {
            $condicao_serie .= " AND UPPER(tbl_os_extra.serie_justificativa) = '$patrimonio' ";
        }

        if ($login_fabrica == 158) {
            if (isset($_GET['unidadenegocio']) && strlen($_GET['unidadenegocio']) > 0) {
                $unidadenegocio_kof = explode("-", $_GET['unidadenegocio']);
            } else {
                $unidadenegocio_kof = $_POST['unidadenegocio'];
            }

            if ($data_tipo == "integracao" || (strlen(trim($os_aberta_kof)) > 0 && !in_array(6300, $unidadenegocio_kof))) {
                $join_schedule_log = "
                    JOIN tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado_cockpit.fabrica = {$login_fabrica}
                    JOIN tbl_routine_schedule_log ON tbl_routine_schedule_log.routine_schedule_log = tbl_hd_chamado_cockpit.routine_schedule_log
                ";
            } else {
                $join_schedule_log = "
                    LEFT JOIN tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado_cockpit.fabrica = {$login_fabrica}
                    LEFT JOIN tbl_routine_schedule_log ON tbl_routine_schedule_log.routine_schedule_log = tbl_hd_chamado_cockpit.routine_schedule_log";
            }
        }

        if($login_fabrica == 160 or $replica_einhell){
            if(strlen($versao) > 0){
                $condicao_versao = " AND tbl_os.type = '$versao'";
            }
        }

        if($login_fabrica == 164){
            if(strlen($destinacao) > 0){
                $condicao_destinacao = " AND tbl_os.segmento_atuacao = {$destinacao} ";
            }
        }

        if (strlen($nf_compra) > 0) {
            if($login_fabrica == 1){
                $condicao_nf_compra = " AND tbl_os.nota_fiscal ILIKE '%$nf_compra%'";
            }else{
                $condicao_nf_compra = " AND tbl_os.nota_fiscal = '$nf_compra'";
            }
        }

        if (strlen($consumidor_cpf) > 0) {
            $condicao_consumidor_cpf = " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
        }

        if (strlen($status_checkpoint) > 0) {

            if($status_checkpoint == 10){
                $condicao_status_checkpoint = " AND tbl_os.hd_chamado isnull ";
            }else{
                $condicao_status_checkpoint = " AND tbl_os.status_checkpoint in ($status_checkpoint)";
            }

        }

            if(in_array($login_fabrica, [169,170])) {

               $peca_pedido  = explode(",",$_REQUEST['pedido_peca']);

                if (count($peca_pedido) > 0) {

                    if (count($peca_pedido) <= 1) {

                           if(in_array('sem_pedido', $peca_pedido)) {

                            $cond_status_pedido  .= " AND (SELECT tbl_os_item.pedido
                                                      FROM tbl_os AS o
                                                      JOIN tbl_os_produto ON tbl_os_produto.os = o.os
                                                      JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                                      WHERE tbl_os_item.pedido IS NOT NULL
                                                      AND o.os = tbl_os.os
                                                      LIMIT 1) IS NULL  "; //traz todas as OS que nao tem pedido

                           }
                           if(in_array('com_pedido', $peca_pedido)) {

                            $cond_status_pedido .= " AND (SELECT tbl_os_item.pedido
                                                     FROM tbl_os AS o
                                                     JOIN tbl_os_produto ON tbl_os_produto.os = o.os
                                                     JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                                     WHERE tbl_os_item.pedido IS NOT NULL
                                                     AND o.os = tbl_os.os
                                                     LIMIT 1) IS NOT NULL  "; //traz todas as OS que tem pedido
                           }
                     }
                }
            }


        if (strlen($admin_sap) > 0) {
            $conds_sql .= " AND tbl_posto_fabrica.admin_sap in ({$admin_sap}) ";
        }

        if (strlen($tipo_posto) > 0) {
            $conds_sql .= " AND tbl_posto_fabrica.tipo_posto in ({$tipo_posto}) ";
        }

        if (isset($status_os_ultimo)) {
            $conds_sql .= " AND tbl_os.status_os_ultimo = $status_os_ultimo ";
        }

	   if(strlen($data_inicial > 0 ) && strlen($data_final > 0 )){

            if($data_tipo == "abertura"){
				$codicao_data_temp = " AND   tbl_os.data_abertura BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
            } else if ($data_tipo == "integracao") {
                $codicao_data_temp = " AND   tbl_routine_schedule_log.create_at BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
            } else if ($data_tipo == "fechamento") {
                $codicao_data_temp = " AND   tbl_os.data_fechamento BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
            } else {
                $codicao_data_temp = " AND   tbl_os.data_digitacao BETWEEN '$data_inicio_consulta $HI' AND '$data_fim_consulta $HF'";
            }

        }

		if(!empty($intervalo1) and !empty($intervalo2) and empty($data_inicial)) {
			$condicao_dashboard = " AND   tbl_os.data_digitacao BETWEEN '$data_fim_consulta $HI'::timestamp - interval '$intervalo1 days' AND '$data_fim_consulta $HF'::timestamp - interval '$intervalo2 days' ";

		}
		if (strlen($os_aberta) > 0) {

            $condicao_os_temp = " AND tbl_os.os_fechada IS FALSE
            AND tbl_os.excluida IS NOT TRUE";
        }

        if (strlen($consumidor_nome) > 0) {
            if ($login_fabrica == 183){
                $condicao_os_consumidor = " AND tbl_os.consumidor_nome ILIKE '%$consumidor_nome%'";
            }else{
                $condicao_os_consumidor = " AND tbl_os.consumidor_nome = '$consumidor_nome'";
            }
        }

        $condicao_sua_os = "1 = 1";

        if(strlen($sua_os) > 0){
            //HD 683858 - inicio - Não estava pegando o $xsua_os qndo fazia a consulta da temp abaixo. então copiei este trecho da linha 1659 para poder fazer a consulta qndo passar a "sua_os"
            if ($login_fabrica == 1) {
                $pos = strpos($sua_os, "-");
                if ($pos === false) {
                    //hd 47506
                    if(strlen ($sua_os) > 12) {
                        $pos = strlen($sua_os) - (strlen($sua_os)-6);
                    }else if(strlen ($sua_os) > 11){
                        $pos = strlen($sua_os) - (strlen($sua_os)-5);
                    } elseif(strlen ($sua_os) > 10) {
                        $pos = strlen($sua_os) - (strlen($sua_os)-6);
                    } elseif(strlen ($sua_os) > 9) {
                        $pos = strlen($sua_os) - (strlen($sua_os)-5);
                    }else{
                        $pos = strlen($sua_os);
                    }
                }else{
                    //hd 47506
                    if(strlen (substr($sua_os,0,$pos)) > 11){#47506
                        $pos = $pos - 7;
                    } else if(strlen (substr($sua_os,0,$pos)) > 10) {
                        $pos = $pos - 6;
                    } elseif(strlen ($sua_os) > 9) {
                        $pos = $pos - 5;
                    }
                }
                if(strlen ($sua_os) > 9) {
		    $xsua_os = substr($sua_os, $pos,strlen($sua_os));
                    $codigo_posto = substr($sua_os,0,$pos);
                    $sqlPosto = "SELECT posto from tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
                    $res = pg_exec($con,$sqlPosto);
                    $xposto =  pg_result($res,0,posto) ;
					if(!empty($xposto)) {
						$condicao_sua_os .= " AND tbl_os.posto = $xposto ";
					}
                }
            }
            //HD 683858 - fim

            $pos = strpos($sua_os, "-");
            if ($pos === false && !in_array($login_fabrica, array(121,137,169,170,173,175,178,183))) {
                if(!ctype_digit($sua_os)){
                    $condicao_sua_os .= " AND tbl_os.sua_os = '$sua_os' ";
                  }else{
                    //hd 47506 - acrescentado OR "tbl_os.sua_os = '$sua_os'"
                    //HD 683858 - acrescentado OR "tbl_os.os = $sua_os"

                    if($login_fabrica == 1){
                        $condicao_sua_os .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os = '$xsua_os' OR tbl_os.os = $sua_os) ";
                    }else{
                        if($login_fabrica == 144) {
                        $condicao_sua_os .= " AND (tbl_os.os_numero::text like '$sua_os%' OR tbl_os.sua_os::text  like '$sua_os%')";
                        } else {
                        $condicao_sua_os .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os  = '$sua_os')";

                        }
                    }
                }

            }else{
                $conteudo = explode("-", $sua_os);
                $os_numero    = $conteudo[0];
                $os_sequencia = $conteudo[1];
                if (!ctype_digit($os_sequencia) && !in_array($login_fabrica, array(121,137,169,170,175,178)) and !$novaTelaOs) {
                    $condicao_sua_os .= " AND tbl_os.sua_os = '$sua_os' ";
                }else{
                    if($login_fabrica ==1) { // HD 51334
                        $sua_os2 = $sua_os;
                        $sua_os = "000000" . trim ($sua_os);
                        if(strlen ($sua_os) > 12 AND $login_fabrica == 1) {
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
                        }elseif(strlen ($sua_os) > 11 AND $login_fabrica == 1){#46900
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 6 , 6);
                        }else{
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 5 , 5);
                        }
                        $sua_os = strtoupper ($sua_os);

                        $condicao_sua_os .= "   AND (
                                        tbl_os.sua_os = '$sua_os' OR
                                        tbl_os.sua_os = '0$sua_os' OR
                                        tbl_os.sua_os = '00$sua_os' OR
                                        tbl_os.sua_os = '000$sua_os' OR
                                        tbl_os.sua_os = '0000$sua_os' OR
                                        tbl_os.sua_os = '00000$sua_os' OR
                                        tbl_os.sua_os = '000000$sua_os' OR
                                        tbl_os.sua_os = '0000000$sua_os' OR
                                        tbl_os.sua_os = '00000000$sua_os' OR
                                        tbl_os.sua_os = substr('$sua_os2',6,length('$sua_os2')) OR
                                        tbl_os.sua_os = substr('$sua_os2',7,length('$sua_os2'))     ";
                        /* hd 4111 */
                        for ($i=1;$i<=40;$i++) {
                            $condicao_sua_os .= "OR tbl_os.sua_os = '$sua_os-$i' ";
                        }
                        $condicao_sua_os .= " OR 1=2) ";
                    }else{
                        if (in_array($login_fabrica, array(121,137,169,170,175,178)) or $novaTelaOs) {
                            $condicao_sua_os .= " AND tbl_os.sua_os like '$sua_os%' ";
                        } elseif(in_array($login_fabrica, array(157))) {
                            $os_numero = (int) $os_numero;
                            $condicao_sua_os .= " AND tbl_os.os_numero = $os_numero AND tbl_os.os_sequencia = '$os_sequencia' ";
                        } else {
                            if ($login_fabrica == 144) {
                                $condicao_sua_os .= " AND tbl_os.sua_os::text ilike '%$os_numero%'";
                            } else {
                                $condicao_sua_os .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
                            }
                        }
                    }
                }
            }
        }

        if (in_array($login_fabrica, array(169,170))){
            if (strlen(trim($numero_os_sap)) > 0){
                $condicao_os_sap = " AND tbl_os.os_posto = '$numero_os_sap' ";
            }
        }

        if(isset($novaTelaOs)){
            $column_serie = " replace(tbl_os_produto.serie, '&#8203;', '') as serie, ";
            $join_produto = " LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                            LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i = $login_fabrica ";
        }else{

            if($login_fabrica == 50 && isset($_GET["dashboard"]) && $_GET["dashboard"] == "sim"){
                $column_serie = "tbl_os_produto.serie,";
                $join_produto = " JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                                  JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i = $login_fabrica ";
            }else{
                $join_produto = " JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto and tbl_produto.fabrica_i = $login_fabrica ";
                $column_serie = " replace(tbl_os.serie, '&#8203;', '') as serie, ";
            }
        }


        if (in_array($login_fabrica, array(35,131,145,151,153))) {
            $distinct_os = "DISTINCT ON(tbl_os.os)";
        }

        /*if ((in_array($login_fabrica, [160]) || $telecontrol_distrib) && $_POST["gerar_excel"] == "t") {
            $distinct_faturamento = "DISTINCT ON(tbl_os_item.os_item)";
        }*/

        if($login_fabrica == 138 AND $sem_listar_peca == 1){ //hd_chamado=2439865 retirada fabrica 138
            $distinct_os = "DISTINCT ON(tbl_os.os)";
        }

        $consultar_os_sem_listar_pecas = trim($_POST["consultar_os_sem_listar_pecas"]);
        if($login_fabrica == 72 AND $consultar_os_sem_listar_pecas == 't'){
            $distinct_os = "DISTINCT ON(tbl_os.os)";
        }

        $whereUnidadeNegocio = "";
        if ($login_fabrica == 158) {

            if (isset($_GET['unidadenegocio']) && strlen($_GET['unidadenegocio']) > 0) {
                $unidadenegocio = explode("-", $_GET['unidadenegocio']);
            } else {
                $unidadenegocio = $_POST['unidadenegocio'];
            }

            if (count($unidadenegocio) > 0) {

                if (in_array("6101", $unidadenegocio)) {
                    array_push($unidadenegocio, 6102,6103,6104,6105,6106,6107,6108);
                }

                foreach ($unidadenegocio as $key => $value) {
                    $unidade_negocios[] = "'$value'";
                }
                $whereUnidadeNegocio = "AND tbl_os_campo_extra.campos_adicionais::jsonb->>'unidadeNegocio' IN (".implode(',', $unidade_negocios).")";
            }

            if(!empty($_REQUEST["tipo_garantia"])) {
                switch ($_REQUEST["tipo_garantia"]) {
                    case "garantia":
                        $cond_tipo_gar = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
                        break;

                    case "fora_garantia":
                        $cond_tipo_gar = " AND tbl_tipo_atendimento.fora_garantia IS TRUE ";
                        break;
                }
            }

        }

        if(strlen($msg) == 0) {
            if ($login_fabrica == 45){
                if ($tipo_os == 'INTERACAO'){
                    $sqlTP = "
                            SELECT inte.os, $login_fabrica AS fabrica
                            INTO TEMP tmp_consulta_$login_admin
                            FROM tbl_os
                            JOIN tbl_os_interacao inte on tbl_os.os = inte.os and os_interacao in (select max(os_interacao) from tbl_os_interacao where tbl_os_interacao.fabrica = 45 and tbl_os.os = tbl_os_interacao.os )
                            $join_troca
                            $conds_sql_ativo
                            WHERE tbl_os.fabrica = $login_fabrica
                            AND inte.admin IS NULL
                            AND   $especifica_mais_1
                            AND   $especifica_mais_2
                            AND   $condicao_sua_os
                            $condicao_os_temp
                            $condicao_os_consumidor
                            $codicao_data_temp
                            $condicao_consumidor_revenda
                            $condicao_os_off
                            $condicao_tecnico
                            $condicao_serie
                            $condicao_nf_compra
                            $condicao_consumidor_cpf
                            $condicao_status_checkpoint
                            $conds_sql
                            $where_troca
                    ";
                }else{

                    $sqlTP = "
                            SELECT tbl_os.os, $login_fabrica AS fabrica
                            INTO TEMP tmp_consulta_$login_admin
                            FROM tbl_os
                            $join_troca
                            $join_schedule_log
                            $conds_sql_ativo
                            WHERE tbl_os.fabrica = $login_fabrica
                            AND   $especifica_mais_1
                            AND   $especifica_mais_2
                            AND   $condicao_sua_os
                            $condicao_os_temp
                            $condicao_os_consumidor
                            $codicao_data_temp
							$condicao_dashboard
                            $condicao_consumidor_revenda
                            $condicao_os_off
                            $condicao_tecnico
                            $condicao_serie
                            $condicao_nf_compra
                            $condicao_consumidor_cpf
                            $cond_status_pedido
                            $condicao_status_checkpoint
                            $conds_sql
                            $where_troca ";

                }
            }else{

                if ($_GET['dash'] == 1) {

                    /* echo "<pre>";
                    print_r($_GET);
                    echo "<pre>";
                    exit; */

                    if ($_GET['tipo_fechada'] > 0 AND $_GET['status_checkpoint'] != 9) {
                        switch ($_GET['tipo_fechada']) {
                            case '1':
                                $condTipoFechada = ($login_fabrica == 50) ? " AND data_digitacao between current_date - interval '10 days' and current_date - interval '0 days' " : " AND data_digitacao between current_date - interval '3 days'";
                                break;
                            case '2':
                                $condTipoFechada = ($login_fabrica == 50) ? " AND data_digitacao between current_date - interval '20 days' and current_date - interval '11 days' " : " AND data_digitacao between current_date - interval '7 days' and current_date - interval '4 days'";
                                break;
                            case '3':
                                $condTipoFechada = ($login_fabrica == 50) ? " AND data_digitacao between current_date - interval '30 days' and current_date - interval '21 days' " : " AND data_digitacao between current_date - interval '15 days' and current_date - interval '8 days'";
                                break;
                            case '4':
                                $condTipoFechada = ($login_fabrica == 50) ? " AND data_digitacao between current_date - interval '90 days' and current_date - interval '31 days' " : " AND data_digitacao between current_date - interval '25 days' and current_date - interval '16 days'";
                                break;
                            case '5':
                                $condTipoFechada = ($login_fabrica == 50) ? "" : ($login_fabrica == 160 or $replica_einhell) ? " AND data_digitacao between current_date - interval '90 days' and current_date - interval '25 days' " : " AND data_digitacao > (current_date - interval '90 days') ";
                                break;
                            default:
                                $condTipoFechada = "";
                                break;
                        }
                    }

                    if ($_GET['tipo_fechada'] > 0 AND $_GET['status_checkpoint'] == 9) {
                        switch ($_GET['tipo_fechada']) {
                            case '1':
                                $condTipoFechada = ($login_fabrica == 50) ? " AND finalizada - data_digitacao between '0 day' and '10 days' " : " AND finalizada - data_digitacao between '0 day' and '3 days' ";
                                break;
                            case '2':
                                $condTipoFechada = ($login_fabrica == 50) ? " AND (tbl_os.finalizada - tbl_os.data_digitacao) between '11 days 1 second' and '20 days' " : " AND (tbl_os.finalizada - tbl_os.data_digitacao) between '3 days 1 second' and '7 days'";
                                break;
                            case '3':
                                $condTipoFechada = ($login_fabrica == 50) ? " AND (tbl_os.finalizada - tbl_os.data_digitacao) between '21 days 1 second' and '30 days' " : " AND (tbl_os.finalizada - tbl_os.data_digitacao) between '7 days 1 second' and '15 days'";
                                break;
                            case '4':
                                $condTipoFechada = ($login_fabrica == 50) ? " AND (tbl_os.finalizada - tbl_os.data_digitacao) between '31 days 1 second' and '90 days' " : " AND (tbl_os.finalizada - tbl_os.data_digitacao) between '15 days 1 second' and '25 days'";
                                break;
                            case '5':
                                $condTipoFechada = ($login_fabrica == 50) ? " AND (tbl_os.finalizada - tbl_os.data_digitacao) ::interval > interval '90 days' " : " AND (tbl_os.finalizada - tbl_os.data_digitacao) ::interval > interval '25 days'";
                                break;
                            default:
                                $condTipoFechada = "";
                                break;
                        }
                    }

                    // OS de Troca ou somente Peça
                    if ($_GET['os_produto_peca'] == 'produto' OR $_GET['os_produto_peca'] == 'peca') {
                        $os_produto_peca == $_GET['os_produto_peca'];

                        if ($os_produto_peca == 'produto') {
                            $joinOSPP = "
                            JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
                                                            AND tbl_os.fabrica = $login_fabrica";
                            $condOSpp = "";
                        } elseif ($os_produto_peca == 'peca') {
                            $joinOSPP = "
                            LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
                                                            AND tbl_os.fabrica = $login_fabrica";
                            $condOSpp =  " AND tbl_os_troca.os is null";
                        }else{
                            $condOSpp = "";
                            $joinOSPP = "";
                        }
                    }

                    //Posto
                    if (!empty($_GET['dash_codigo_posto'])) {
                        $dash_codigo_posto = $_GET['dash_codigo_posto'];
                        $sql_dash_posto = "SELECT posto FROM tbl_posto_fabrica where fabrica = $login_fabrica and codigo_posto = '$dash_codigo_posto'";
                        $res_dash_posto = pg_query($con,$sql_dash_posto);
                        if(pg_num_rows($res_dash_posto) > 0 ) {
                            $dash_posto = pg_fetch_result($res_dash_posto,0,0);
                            $dashCondPosto = "AND posto = $dash_posto";
                        }
                    }

                    //Produto
                    if (!empty($_GET['dash_produto_referencia'])) {
                        $dash_produto_referencia =  $_GET["dash_produto_referencia"];
                        $sql_prod_dash = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND referencia = '$dash_produto_referencia'";
                        $res_prod_dash = pg_query($con,$sql_prod_dash);
                        if (pg_num_rows($res_prod_dash) > 0) {
                            $dash_produto = pg_fetch_result($res_prod_dash, 0, produto);
                            $dashCondProduto = " AND tbl_os.produto = $dash_produto";
                        }
                    }

                    //Fechada sem reparo
                    if(!empty($_GET['fechadas_sem_reparo']) AND $_GET['fechadas_sem_reparo'] == 'true'){ //hd_chamado=2787856
                        $dashCondSemReparo = "AND tbl_os.status_os_ultimo = 240";
                    }

                    //Peça
                    if (!empty($_GET['dash_peca_referencia'])) {
                        $dash_peca_referencia = $_GET['dash_peca_referencia'];

                        $sql_peca_dash = "SELECT peca FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = '$dash_peca_referencia';";
                        $res_peca_dash = pg_query($con,$sql_peca_dash);

                        if (pg_num_rows($res_peca_dash) > 0) {
                            $dashPeca = pg_fetch_result($res_peca_dash, 0, peca);

                            $dashJoinPeca = "
                            JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                                                            AND tbl_os.fabrica = $login_fabrica
                            JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                                            AND tbl_os_item.fabrica_i = $login_fabrica";

                            $dashCondPeca = " AND tbl_os_item.peca = $dashPeca";
                        }
                    }
                    $dashCond = "   AND tbl_os.os in (SELECT tbl_os.os
                                    FROM tbl_os
                                    $dashJoinPeca
                                    $join_schedule_log
                                    $joinOSPP
                                    WHERE fabrica = $login_fabrica
                                    $condicao_status_checkpoint
                                    $condTipoFechada
                                    $codicao_data_temp
                                    $dashCondSemReparo
                                    $condOSpp
                                    $dashCondPosto
                                    $dashCondProduto
                                    $dashCondPeca
                                    AND posto not in (6359)
                                    )";
                                    //die(nl2br($dashCond));
                    if (strlen($_GET['tipo_fechada']) == 0 AND strlen($_GET['status_checkpoint']) == 0 AND strlen($_GET['os_aberta']) == 0) {
                        $dashCond .= "AND tbl_os.finalizada IS NOT NULL";
                    }

                    // OS de Revenda ou Consumidor
                    if ($_GET['os_revenda_consumidor'] == 'consumidor' OR $_GET['os_revenda_consumidor'] == 'revenda') {
                        $os_revenda_consumidor = ($_GET['os_revenda_consumidor'] == "consumidor")? 'C':'R';
                        $dashCond .= "
                            AND consumidor_revenda='".$os_revenda_consumidor."'";
                    }
                }

                $join_nf_recebimento = '';
                $os_campos_adicionais = '';

                if ($login_fabrica == 156 and !empty($_POST['nf_recebimento'])) {
                    $nf_recebimento = $_POST['nf_recebimento'];
                    $os_campos_adicionais = ' , tbl_os_campo_extra.campos_adicionais ';
                    $join_nf_recebimento = ' JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
                        AND tbl_os_campo_extra.campos_adicionais IS NOT NULL ';
                }

                if( $login_fabrica == 24 ){
                    $condOsBloqueada = " AND tbl_os_campo_extra.os_bloqueada IS NOT TRUE";

                    if ($os_situacao == "BLOQUEADA") {
                        $condOsBloqueada = " AND tbl_os_campo_extra.os_bloqueada IS TRUE";
                    }
                    
                }

                $cond_consulta_debaixo = "";
                if(in_array($login_fabrica,[158]) && !$cancelaOS){
                    $cond_consulta_debaixo =" AND tbl_os.excluida IS NOT TRUE ";

                    if(!isset($_GET["dashboard"])){
                        $cond_consulta_debaixo .= " AND (tbl_os_extra.status_os NOT IN (13,15) OR tbl_os_extra.status_os IS NULL) ";
                    }
                }

                #criando tmp para consulta principal
                $sqlTP = "
                    SELECT tbl_os.os, $login_fabrica AS fabrica $os_campos_adicionais
                    INTO TEMP tmp_consulta_$login_admin
                    FROM tbl_os
                    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                    LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
                    AND tbl_os_campo_extra.fabrica = {$login_fabrica}
                    LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                    $join_nf_recebimento
                    LEFT JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
                    LEFT JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade
                    LEFT JOIN tbl_tipo_atendimento ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
                    $join_schedule_log
                    $join_troca
                    $conds_sql_ativo
                    WHERE tbl_os.fabrica = $login_fabrica
                    AND   $especifica_mais_1
                    AND   $especifica_mais_2
                    AND   $condicao_sua_os
                    $condicao_os_sap
                    $verCortesia
                    $condicao_os_temp
                    $condicao_os_consumidor
                    $codicao_data_temp
                    $condicao_dashboard
                    $condicao_consumidor_revenda
                    $condicao_os_off
                    $cond_numero_reclamacao
                    $cond_seu_pedido
                    $condicao_tecnico
                    $condicao_serie
                    $condicao_versao
                    $condicao_destinacao
                    $condicao_nf_compra
                    $condicao_consumidor_cpf
                    $cond_consulta_debaixo
                    $cond_status_pedido
                    $condicao_status_checkpoint
                    $conds_sql
                    $dashCond
                    $where_troca
                    $whereUnidadeNegocio
                    $cond_tipo_gar
                    $condOsBloqueada
					$cond_intervalo
					$cond_regiao
                    ";
            }
            if($login_fabrica == 45 and $tipo_os == 'RESSARCIMENTO'){
                $sqlTP .=" AND tbl_os_troca.ressarcimento = 't'";
            }
            if(empty($sua_os) and empty($condicao_serie) and empty($condicao_consumidor_cpf) and empty($condicao_os_consumidor)){
                $data_inicio_explode = explode("-", $data_inicio_consulta);
                $data_fim_explode = explode("-", $data_fim_consulta);
                $data_resultado = $data_fim_explode[2]-$data_inicio_explode[2];

                if(($data_fim_explode[2]>$data_inicio_explode[2] && $data_resultado>3) || $data_inicio_explode[1]!=$data_fim_explode[1]){
                    $sqlTP .= ";CREATE INDEX tmp_consulta_OS_$login_admin ON tmp_consulta_$login_admin(os)";
                }
	    }
            $resX = pg_query ($con,$sqlTP);
        }

        if(!isset($_GET["dashboard"])){
            $join_especifico = "JOIN tmp_consulta_$login_admin oss ON tbl_os.os = oss.os AND oss.fabrica = $login_fabrica ";
        }

        if ($login_fabrica == 11 or $login_fabrica == 172) {
            if (strlen($rg_produto_os)>0) {
                $sql_rg_produto = " AND tbl_os.rg_produto = '$rg_produto_os' ";
            }
        }

        //HD 14927
        if($mostra_data_conserto){
            $sql_data_conserto=" , to_char(tbl_os.data_conserto,'DD/MM/YYYY') as data_conserto ";
        }

        if ($login_fabrica == 145) {
            $pesquisa_satisfacao = $_POST["pesquisa_satisfacao"];

            switch ($pesquisa_satisfacao) {
                case "realizada":
                    $joinPesquisaSatisfacao = "
                        INNER JOIN tbl_resposta ON tbl_resposta.os = tbl_os.os
                        INNER JOIN tbl_pesquisa ON tbl_pesquisa.pesquisa = tbl_resposta.pesquisa AND tbl_pesquisa.fabrica = {$login_fabrica}
                    ";
                    break;

                case "nao_realizada":
                    $joinPesquisaSatisfacao = "
                        LEFT JOIN tbl_resposta ON tbl_resposta.os = tbl_os.os
                        LEFT JOIN tbl_pesquisa ON tbl_pesquisa.pesquisa = tbl_resposta.pesquisa AND tbl_pesquisa.fabrica = {$login_fabrica}
                    ";
                    $wherePesquisaSatisfacao = "
                        AND tbl_resposta.resposta IS NULL
                    ";
                    break;
            }
        }

        if(($login_fabrica == 50 && isset($_GET["dashboard"]) && $_GET["dashboard"] == "sim") || in_array($login_fabrica,[158,160])){
            $distinct_os = " DISTINCT ON(tbl_os.os) ";
        }

        if (in_array($login_fabrica, array(162)) and !empty($_POST["tecnico"])) {
            $tecnico = $_POST['tecnico'];
            $cond_tecnico = " AND tbl_os.tecnico = $tecnico ";
        }

        if($login_fabrica == 162){
            $left_tecnico = "LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_os.tecnico";
            $campo_tecnico = " tbl_tecnico.nome AS nome_tecnico,  ";
        }

        if ($login_fabrica == 158 && $data_tipo == 'integracao') {
            $campo_data_integracao = "TO_CHAR(tbl_routine_schedule_log.create_at,'DD/MM/YYYY') AS integracao,";
        }

        $campo_abertura = "tbl_os.data_abertura,'DD/MM/YYYY'";

        if ($login_fabrica == 158) {
            $campo_abertura = "tbl_os.data_digitacao, 'DD/MM/YYYY HH24:MI'";
        }

       /* if($login_fabrica == 131){
            $campos_pressure = " tbl_peca.referencia as referencia_peca, tbl_peca.descricao as descricao_peca, tbl_os_item.digitacao_item as data_digitacao_peca,  tbl_pedido.data as dt_geracao, tbl_faturamento.nota_fiscal as nf_peca, ";
        }*/

        // OS não excluída
        $sql =  "SELECT
			            {$distinct_os}
                        {$distinct_faturamento}
                        {$campos_pressure}
			            tbl_os.os                                                         ,
                        tbl_os.fabrica                                                     ,
                        tbl_os.sua_os                                                     ,
                        tbl_os.type                                                       ,
                        tbl_os.cancelada                                                  ,
                        tbl_os.nota_fiscal                                                ,
						regexp_replace(tbl_os.obs,'\\s+',' ', 'g') as obs                 ,
                        tbl_os.os_numero                                                  ,
                        tbl_os.admin                                                      ,
                        sua_os_offline                                                    ,
                        LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
                        TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
                        TO_CHAR({$campo_abertura})   AS abertura          ,
                        TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
                        TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
                        TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')        as data_nf            ,
                        $campo_data_integracao
                        $column_serie
                        tbl_os.consumidor_estado                                          ,
                        $campo_tecnico
                        tbl_os.excluida                                                   ,
                        tbl_os.mao_de_obra                                                   ,
                        tbl_os.motivo_atraso                                              ,
                        tbl_os.tipo_os_cortesia                                           ,
                        tbl_os.consumidor_revenda                                         ,
                        tbl_os.consumidor_nome                                            ,
                        tbl_os.consumidor_fone                                            , ";
        if (in_array($login_fabrica, array(152,180,181,182))) {
            $sql .= " tbl_os.data_abertura - tbl_os.data_nf AS tempo_para_defeito,  ";
        }

        if (in_array($login_fabrica, array(169,170))) {
            $sql .= "(
                        SELECT date(tbl_tecnico_agenda.data_agendamento)
                        FROM tbl_tecnico_agenda
                        WHERE tbl_tecnico_agenda.os = tbl_os.os
                        AND tbl_os.fabrica = $login_fabrica
                        ORDER BY tbl_tecnico_agenda.data_agendamento ASC
                        LIMIT 1
                    ) as primeira_data_agendamento,
                    (
                        SELECT date(tbl_tecnico_agenda.confirmado)
                        FROM tbl_tecnico_agenda
                        WHERE tbl_tecnico_agenda.os = tbl_os.os
                        AND tbl_os.fabrica = $login_fabrica
                        AND tbl_tecnico_agenda.confirmado IS NOT NULL
                        LIMIT 1
                    ) as data_confirmacao,
                    (
                        SELECT tbl_admin.nome_completo
                        FROM tbl_admin
                        WHERE tbl_admin.admin = tbl_posto_fabrica.admin_sap
                    ) as inspetor_sap,
                    tbl_os.consumidor_bairro as cons_bairro,
                    tbl_os.consumidor_cidade AS cons_cidade,
                     ";
        }
        if ($login_fabrica == 178){
            $sql .= '(
                        SELECT tbl_admin.nome_completo
                        FROM tbl_admin
                        WHERE tbl_admin.admin = tbl_posto_fabrica.admin_sap
                    ) as inspetor_sap,';
        }
        if($login_fabrica == 72){
            $sql .= " tbl_os.defeito_reclamado_descricao,
                    tbl_solucao.descricao as solucao_os,
                    tbl_defeito_constatado.descricao as defeito_constatado, ";
        }

        if (($telecontrol_distrib || in_array($login_fabrica, [160])) && $_POST["gerar_excel"] == "t") {
            $sql .= "tbl_pedido.pedido as pedido_tc,
                     tbl_faturamento.nota_fiscal as nota_fiscal_tc,
                     TO_CHAR(tbl_faturamento.emissao, 'dd/mm/yyyy') as emissao_nf,
                     tbl_faturamento.conhecimento as codigo_rastreio,
                     TO_CHAR(tbl_faturamento_correio.data, 'dd/mm/yyyy') as data_entrega,
                     tbl_faturamento_correio.data::date - tbl_os.data_abertura as dias_entrega,
		     tbl_peca.referencia AS referencia_peca,
	             tbl_peca.descricao as descricao_peca,
                     tbl_os_item.qtde as qtde_componentes,
                     tbl_transportadora.nome AS nome_transportadora, ";
        }

        if (in_array($login_fabrica, array(50, 137, 169, 170))) {

            $sql .= "
                    tbl_os.revenda_cnpj AS revenda_cnpj,
            ";

        }

        if(in_array($login_fabrica, array(30))){
            $sql .= "
                    coalesce(tbl_os.mao_de_obra, 0) + coalesce(tbl_os.qtde_km_calculada, 0) + coalesce(tbl_os.pecas, 0) AS valor_os, tbl_os.consumidor_cep, tbl_os.consumidor_numero, tbl_os.consumidor_bairro, ";
        }
        if ($telecontrol_distrib) {
             $sql .= " tbl_os.consumidor_cpf,";
        }
        if (in_array($login_fabrica, array(30, 50, 137, 164))) {
            $sql .= " tbl_os.consumidor_endereco,
                      tbl_os.consumidor_cidade,
                      tbl_os.consumidor_cpf,
                      tbl_os.defeito_reclamado_descricao AS defeito_reclamado_os,
                      tbl_defeito_reclamado.descricao AS defeito_reclamado_descricao_os,
                      tbl_defeito_constatado.descricao AS defeito_constatado,";
        }

        if ($login_fabrica == 94) {
            $sql .= " tbl_os.defeito_reclamado_descricao AS defeito_reclamado_os,
                      tbl_defeito_constatado.descricao AS defeito_constatado_desc,";
        }

        $sql .= " tbl_os.revenda_nome                                               ,
            tbl_os.tipo_atendimento                                           ,
            tbl_os.os_reincidente                      AS reincidencia        ,
            tbl_os.os_posto                                                   ,
            tbl_os.aparencia_produto                                          ,
            tbl_os.tecnico_nome                                               ,
            tbl_os.rg_produto                                                 ,";
        if(in_array($login_fabrica,array(30,35,85,145))){
            $sql .= "tbl_hd_chamado.hd_chamado,";
        }else{
            $sql .= "tbl_os.hd_chamado,";
        }

                if ($login_fabrica == 158) {
                    $sql .= "
                        regexp_replace(tbl_hd_chamado_cockpit.dados,'\\\\r|\\\\n','','g') AS json_kof,
                        tbl_hd_chamado_cockpit.geolocalizacao,
                        tbl_cliente_admin.nome AS cliente_admin_nome,
                        tbl_familia.descricao AS familia_produto,
                        tbl_os_extra.serie_justificativa,
                        tbl_os.consumidor_endereco,
                        tbl_os.consumidor_celular,
                        tbl_os.consumidor_email,
                        tbl_os.consumidor_bairro,
                        tbl_os.consumidor_cep,
                        tbl_os.consumidor_cidade,
                        tbl_routine_schedule_log.file_name AS arquivo_kof,
                        TO_CHAR(tbl_routine_schedule_log.create_at, 'DD/MM/YYYY HH24:MI:SS') AS data_integracao,
                        tbl_os_campo_extra.campos_adicionais,
                        TO_CHAR(tbl_os.exportado, 'DD/MM/YYYY HH24:MI:SS') AS exportado,
                        unidade_negocio.nome AS unidade_negocio,
                        TO_CHAR(tbl_os.data_digitacao, 'YYYY-MM-DD HH24:MI:SS') AS digitacao_hora,
                        TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY HH24:MI:SS') AS digitacao_hora_f,
                        (SELECT TO_CHAR(data, 'DD/MM/YYYY HH24:MI:SS') FROM tbl_os_modificacao WHERE tbl_os_modificacao.os = tbl_os.os ORDER BY data DESC LIMIT 1) AS ultima_modificacao,
                        tbl_os.data_conserto,
                        distribuidor_principal.descricao AS distribuidor_principal,
                        tbl_defeito_reclamado.descricao AS defeito_reclamado,
                        tbl_os_extra.inicio_atendimento,
                        tbl_os_extra.termino_atendimento,
                        tbl_admin.nome_completo AS admin_nome,
                        regiao_distribuidor_principal.nome AS regiao_distribuidor_principal,
                        ARRAY_TO_STRING(ARRAY(
                            SELECT dc.descricao
                            FROM tbl_os_defeito_reclamado_constatado osdc
                            INNER JOIN tbl_defeito_constatado dc ON dc.defeito_constatado = osdc.defeito_constatado AND dc.fabrica = {$login_fabrica}
                            WHERE osdc.fabrica = 158
                            AND osdc.os = tbl_os.os
                            AND osdc.defeito_constatado IS NOT NULL
                        ), ', ') AS defeitos_constatados,
                        ARRAY_TO_STRING(ARRAY(
                            SELECT s.descricao
                            FROM tbl_os_defeito_reclamado_constatado oss
                            INNER JOIN tbl_solucao s ON s.solucao = oss.solucao AND s.fabrica = {$login_fabrica}
                            WHERE oss.fabrica = 158
                            AND oss.os = tbl_os.os
                            AND oss.solucao IS NOT NULL
                        ), ', ') AS solucoes,
                        tbl_tipo_posto.descricao as tipo_posto,
                        (
                            SELECT c.descricao
                            FROM tbl_os_defeito_reclamado_constatado odrc
                            INNER JOIN tbl_solucao s ON s.solucao = odrc.solucao AND s.fabrica = {$login_fabrica}
                            INNER JOIN tbl_classificacao c ON c.classificacao = s.classificacao AND c.fabrica = {$login_fabrica}
                            WHERE odrc.os = tbl_os.os
                            ORDER BY c.peso DESC
                            LIMIT 1
                        ) AS classificacao,
                        CASE WHEN (select count(1) from fn_calendario(tbl_os.data_hora_abertura::date, current_date ) where nome_dia <> 'Domingo' and nome_dia !~ 'bado') - 1 - (select count(1) from tbl_feriado where tbl_feriado.data between tbl_os.data_hora_abertura and current_timestamp and tbl_feriado.fabrica = 158) between 0 and 1
                             THEN 'dentro_sla'
                             ELSE 'fora_sla'
                        END AS sla_refrigerador_vending_machine,
                        CASE WHEN (EXTRACT(EPOCH FROM tbl_os.data_hora_abertura - current_timestamp) / 3600) between 0 and 3
                             THEN 'dentro_sla'
                             ELSE 'fora_sla'
                        END AS sla_chopeira_postmix,
                    ";
                }

                $sql .= "
                        tbl_tipo_atendimento.descricao                                    ,
                        tbl_tipo_atendimento.grupo_atendimento,
                        tbl_posto.posto,
                        tbl_posto_fabrica.codigo_posto                                    ,
                        tbl_posto_fabrica.contato_estado                                  ,
                        tbl_posto_fabrica.contato_cidade                                  ,
                        tbl_posto_fabrica.credenciamento                                  ,
                        tbl_posto.nome                              AS posto_nome         ,
                        tbl_posto.capital_interior                                        ,
                        tbl_posto.estado                                                  ,
                        tbl_tipo_posto.posto_interno,
                        tbl_os_extra.impressa                                             ,
                        tbl_os_extra.obs_adicionais                                       ,
                        tbl_os_extra.extrato                                              ,
                        tbl_os_extra.os_reincidente                                       ,
                        tbl_produto.referencia_fabrica                      AS produto_referencia_fabrica ,
                        tbl_produto.referencia                      AS produto_referencia ,
                        tbl_produto.descricao                       AS produto_descricao  ,
                        tbl_produto.voltagem                        AS produto_voltagem   ,
                        tbl_os.status_checkpoint                                          ,
                        distrib.codigo_posto                        AS codigo_distrib     ,";
        if (in_array($login_fabrica,array(30,138,152,180,181,182))) { //2439865
            $sem_listar_peca = $_POST['sem_listar_peca'];
            if($sem_listar_peca <> 1) { // HD-2415933
                if(in_array($login_fabrica,array(30,152,180,181,182))){
                    $sql .= " tbl_os_item.qtde AS peca_qtde, ";
                }

                $sql.= "
                    TO_CHAR(tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item ,
                    tbl_pedido_item.pedido,
                    tbl_faturamento.nota_fiscal as nf_fat,
                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS nf_emissao,
                    tbl_peca.referencia as peca_referencia,
                    tbl_peca.descricao as peca_descricao, ";
            }

            if($login_fabrica == 30){
                $sql.= "
                    tbl_cliente_admin.nome AS cliente_admin_nome,
                    tbl_os_extra.termino_atendimento,
                    CASE
                        WHEN tbl_cliente_admin.nome IS NOT NULL AND tbl_hd_chamado_extra.hd_chamado notnull AND finalizada IS NULL AND termino_atendimento notnull AND termino_atendimento::date - CURRENT_DATE >= 3 THEN
                            3
                        WHEN tbl_cliente_admin.nome IS NOT NULL AND tbl_hd_chamado_extra.hd_chamado notnull AND finalizada IS NULL AND termino_atendimento notnull AND termino_atendimento::date - CURRENT_DATE > 1 THEN
                            2
                        WHEN tbl_cliente_admin.nome IS NOT NULL AND tbl_hd_chamado_extra.hd_chamado notnull AND finalizada IS NULL AND termino_atendimento notnull AND termino_atendimento::date - CURRENT_DATE <= 1 THEN
                            1
                        ELSE
                            4
			    END AS termino,
			    tbl_hd_chamado_extra.numero_processo,
                ";
            }
        }

        if($login_fabrica == 115){
            $sql .= "
                TO_CHAR(tbl_os.data_conserto,'DD/MM/YYYY')  AS data_conserto,
                tbl_familia.descricao                       AS familia_produto,
                tbl_defeito_reclamado.descricao             AS defeito_reclamado,
                tbl_defeito_constatado.descricao            AS defeito_constatado,
            ";
        }
        if($login_fabrica == 6){
            $sql.="
                tbl_os.consumidor_email,
                tbl_os.revenda_cnpj,
                ";
        }
        if ($login_fabrica == 24) {
            $sql .= "
                    CASE WHEN tbl_os.data_abertura::date BETWEEN (current_date - interval '60 days')::date AND (current_date - interval '30 days')::date THEN 'true' AND tbl_os.finalizada IS NULL
                    END AS congelar ,
                    ";
        }
        if ($login_fabrica == 74) { //hd_chamado=2588542
            $sql .= "
                    CASE WHEN tbl_os.data_abertura::date < (current_date - interval '5 days')::date THEN 'true' AND tbl_os.finalizada IS NULL
                    END AS congelar ,
                    tbl_os_campo_extra.os_bloqueada,
                    ";
        }

        if ($login_fabrica == 3 OR $login_fabrica == 86 or $multimarca == 't') {
                            $sql .= "tbl_marca.marca ,
                                     tbl_marca.nome as marca_nome,";
        }

        if($login_fabrica == 74) {
                            $sql .= "tbl_os_interacao.os_interacao, ";
                            $sql .= "tbl_os_interacao.atendido, ";
                            $sql .= "TO_CHAR(tbl_os_interacao.data_contato,'DD/MM/YYYY') AS data_contato, ";
        }
        if ($login_fabrica == 52) {
                            $sql .= "tbl_os.marca,tbl_cliente_admin.nome AS cliente_admin_nome,
                                     tbl_numero_serie.ordem AS ordem_ativo,";
        }

        if ($login_fabrica == 115 OR $login_fabrica == 116 OR $login_fabrica == 117 OR $login_fabrica == 120) {
                            $sql .= " tbl_os.qtde_km  AS valor_km,";
        }
        if ($login_fabrica == 45){
                            //SE O RESULTADO DA SUBQUERY TROUXER VAZIO É PORQUE NÃO É INTERACAO DO POSTO, LOGO NAO SERÁ ATRIBUIDO A COR DE LEG
                            $sql .= "
                                    (SELECT admin from tbl_os_interacao where tbl_os_interacao.os = tbl_os.os order by data desc limit 1) as campo_interacao,
                            ";
        }
        if($login_fabrica == 30 OR $login_fabrica == 85){
            $sql .= "
                        tbl_hd_chamado_extra.array_campos_adicionais,
            ";
        }

        if($login_fabrica == 164){
            $sql .= "
                        tbl_segmento_atuacao.descricao AS segmento_atuacao,
            ";
        }

        if ($login_fabrica == 158) { /*HD - 6115865*/
            $sql .= " (SELECT ARRAY_TO_STRING(ARRAY(SELECT DISTINCT a.descricao FROM tbl_auditoria_os ao INNER JOIN tbl_auditoria_status a ON a.auditoria_status = ao.auditoria_status WHERE ao.os = tbl_os.os), ', ')) AS auditorias_pendentes, ";
        }

        if($login_fabrica == 165){
            $sqlProduto_trocado .= ", (pt.referencia || ' - ' || pt.descricao) AS produto_trocado  ";
        }

            $sql .= " status_os_ultimo AS status_os
				$sql_data_conserto
                $sqlProduto_trocado
		INTO TEMP tmp_os_consulta_lite_$login_admin
                FROM      tbl_os
                $join_especifico";

        if($login_fabrica == 74) {
                $sql .=  " left join tbl_os_interacao ON tbl_os.os = tbl_os_interacao.os AND os_interacao in (select max(os_interacao) from tbl_os_interacao where tbl_os_interacao.fabrica = $login_fabrica and tbl_os.os = tbl_os_interacao.os )" . (strlen($os_atendida) > 0 ? " AND (tbl_os_interacao.atendido IS FALSE)" : "");
        }

        if ($login_fabrica == 94) {
            $sql .= " LEFT JOIN tbl_os_defeito_reclamado_constatado ON tbl_os.os = tbl_os_defeito_reclamado_constatado.os
                      LEFT JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado";
        }

        if ($login_fabrica == 52) {
            $sql .= " LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_os.cliente_admin ";
        }

        if($login_fabrica == 72){
            $sql .= "left join tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado and tbl_os.fabrica = $login_fabrica";
            $sql .= "left join tbl_solucao on tbl_solucao.solucao = tbl_os.solucao_os and tbl_os.fabrica = $login_fabrica ";
        }

        if (in_array($login_fabrica, array(30, 50, 137, 164))) {

            $sql .= "LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado ";

        }

        if($login_fabrica == 35){
            if($os_aguardando_troca ==  "t"){
                    $sql .= " join tbl_auditoria_os on tbl_auditoria_os.os = tbl_os.os ";
            }
        }

        if (in_array($login_fabrica, array(30,50,137,164))) {
            $sql .= "LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado ";
            if($login_fabrica == 30){
                $sql .= "
                    LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_os.cliente_admin
                ";
            }
        }

        if (in_array($login_fabrica, [177])) {
            $sql .= " LEFT JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os.status_os_ultimo ";
        }

        if($login_fabrica == 52) {
           $sql .="LEFT JOIN  tbl_numero_serie ON tbl_os.produto = tbl_numero_serie.produto
                    AND tbl_os.serie = tbl_numero_serie.serie ";

            ## HD-2507504 ##
            if (strlen($pre_os)>0) {
                $sql .=" LEFT JOIN tbl_hd_chamado ON tbl_os.posto = tbl_hd_chamado.posto ";
            }
        }

        $os_garantia_peca = filter_input(INPUT_POST,'os_garantia_peca');
        $os_garantia_estendida = filter_input(INPUT_POST, 'os_garantia_estendida');
        $os_estoque_posto = filter_input(INPUT_POST, 'os_estoque_posto');
				
				if ($os_garantia_peca == 1) {
            $sql .= " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento";
        } else if ($os_garantia_estendida == 1) {
            $sql .= " JOIN tbl_cliente_garantia_estendida ON tbl_os.os = tbl_cliente_garantia_estendida.os";
            $sql .= " LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                                                AND tbl_tipo_atendimento.descricao ILIKE ('Devolução de peças')
                                                AND tbl_tipo_atendimento.fabrica = $login_fabrica";
        } else {
            $sql .= " LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica";
        }

        $sql .= "
            JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
            JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = $login_fabrica
            $join_produto ";

        if(isset($novaTelaOs)){
            $sql .="
                LEFT JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha and tbl_linha.fabrica = $login_fabrica
                LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia and tbl_familia.fabrica = $login_fabrica ";
        } else {
            $sql .="
                JOIN      tbl_linha       ON  tbl_produto.linha       = tbl_linha.linha and tbl_linha.fabrica = $login_fabrica
                JOIN      tbl_familia     ON  tbl_produto.familia     = tbl_familia.familia and tbl_familia.fabrica = $login_fabrica ";
        }

        $sql .="
            LEFT JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os and tbl_os_extra.i_fabrica = $login_fabrica
            JOIN           tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica  and tbl_fabrica.fabrica = $login_fabrica ";

        if($login_fabrica == 141){
            $sql .= " JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
            LEFT JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade";
        }

    if($os_troca == 1){
        $sql.= " JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os " ;
        if($login_fabrica == 165){
            $sql.= " JOIN tbl_produto pt ON pt.produto = tbl_os_troca.produto ";
        }
    }else if($login_fabrica == 165) {
        $sql.= " LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
        LEFT JOIN tbl_produto pt ON pt.produto = tbl_os_troca.produto";
    }

    if (in_array($login_fabrica, array(30,138,152,180,181,182))) { //2439865
	    $sem_listar_peca = $_POST['sem_listar_peca'];

	    if($sem_listar_peca <> 1){ // HD-2415933 //2439865
            if($login_fabrica == 30){
                $sql.= " left JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os ";
            }

    		$sql .=" LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto and tbl_os_item.fabrica_i = $login_fabrica
    			left join tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
    		    left join tbl_faturamento_item on tbl_pedido_item.pedido = tbl_faturamento_item.pedido and tbl_pedido_item.peca = tbl_faturamento_item.peca
    		    left join tbl_faturamento using(faturamento)";

    		if(in_array($login_fabrica, [30,138])){ //2439865
    		    $sql .= "LEFT JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica";
    		}else if(in_array($login_fabrica, array(152,180,181,182))) {
    		    $sql .= "LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica";
    		}
	    }
    }

    /*if($login_fabrica == 131){
        $sql .= "left JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
        LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto and tbl_os_item.fabrica_i = $login_fabrica
        left join tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
        LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica

        left join tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido and tbl_pedido.fabrica = $login_fabrica
        left join tbl_faturamento_item on tbl_pedido_item.pedido = tbl_faturamento_item.pedido and tbl_pedido_item.peca = tbl_faturamento_item.peca
        left join tbl_faturamento using(faturamento)

        ";
    }*/

    if(strlen($os_callcenter) > 0){
        $sql .= " JOIN tbl_hd_chamado_extra ON tbl_os.os = tbl_hd_chamado_extra.os ";
    }

        if (strlen($os_situacao) > 0) {

            if (in_array($os_situacao,array("PAGA","FINALIZADASEMEXTRATO","APROVADA"))) {
                $leftSit = "";
                if ($os_situacao == "FINALIZADASEMEXTRATO") {
                $leftSit = " LEFT ";
                }
                $sql .= " {$leftSit} JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato and tbl_extrato.fabrica = $login_fabrica";
                if ($os_situacao == "PAGA") {
                    $sql .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
                }
            }
            if ($os_situacao == "MAOOBRAZERADA") {
                $sql .= "JOIN tbl_os_status ON  tbl_os_status.os        = tbl_os.os
                                            AND tbl_os_status.status_os = tbl_os.status_os_ultimo
                                            AND tbl_os.status_os_ultimo = 81
                ";
            }
        }
        if ($login_fabrica == 3 OR $login_fabrica == 86 or $multimarca =='t') {
            $sql .= " LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca ";
    }

    if (in_array($login_fabrica,array(148)) && $_REQUEST['os_com_credito']) {
        $os_com_credito = $_REQUEST['os_com_credito'];
        $sql .= " JOIN tbl_extrato_lancamento ON tbl_extrato_lancamento.os = tbl_os.os and tbl_extrato_lancamento.fabrica = $login_fabrica";
    }

    if(in_array($login_fabrica,array(30,35,85,145,158))){
        $sql .= "
                LEFT JOIN tbl_hd_chamado_extra  ON tbl_hd_chamado_extra.os = tbl_os.os and tbl_hd_chamado_extra.posto = tbl_os.posto
                LEFT JOIN tbl_hd_chamado        ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                                                AND tbl_hd_chamado.titulo <> 'Help-Desk Posto'
        ";
        if ($login_fabrica == 30) {
            $sql .= " AND tbl_hd_chamado.titulo <> 'Help-Desk Admin'";
        }
    }

    if ($login_fabrica == 115) {
        $sql .= "
        LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
                                        AND tbl_defeito_reclamado.fabrica = tbl_os.fabrica
        LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
                                        AND tbl_defeito_constatado.fabrica = tbl_os.fabrica

        ";
    }

    if (($telecontrol_distrib || in_array($login_fabrica, [160])) && $_POST["gerar_excel"] == "t") {

            if (!isset($novaTelaOs)) {
                $sql .= "LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os";
            }

            $condFabFaturamento = " AND tbl_faturamento.fabrica = 10";

            $sql .= "
                LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto AND tbl_os_item.fabrica_i = {$login_fabrica}
                LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = {$login_fabrica}
                LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_pedido.status_pedido != 14 AND tbl_pedido.fabrica = {$login_fabrica}
                LEFT JOIN tbl_faturamento_item ON tbl_os_item.peca = tbl_faturamento_item.peca AND tbl_faturamento_item.os = tbl_os.os
                AND tbl_faturamento_item.pedido = tbl_pedido.pedido
                LEFT JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento {$condFabFaturamento}
                LEFT JOIN tbl_faturamento_correio ON tbl_faturamento.faturamento = tbl_faturamento_correio.faturamento AND LOWER(tbl_faturamento_correio.situacao) LIKE 'objeto entregue%' AND tbl_faturamento_correio.fabrica = {$login_fabrica} AND tbl_faturamento_correio.data_input > tbl_faturamento.data_input
                LEFT JOIN tbl_transportadora ON tbl_faturamento.transportadora = tbl_transportadora.transportadora
            ";
    }

    if ($login_fabrica == 158) {
        
        if (isset($_GET['unidadenegocio']) && strlen($_GET['unidadenegocio']) > 0) {
            $unidadenegocio_kof = explode("-", $_GET['unidadenegocio']);
        } else {
            $unidadenegocio_kof = $_POST['unidadenegocio'];
        }

        if ($data_tipo == 'integracao' || (strlen(trim($os_aberta_kof)) > 0 && !in_array(6300, $unidadenegocio_kof))) {
            $sql .= "
                JOIN tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado_cockpit.fabrica = {$login_fabrica}
                JOIN tbl_routine_schedule_log ON tbl_routine_schedule_log.routine_schedule_log = tbl_hd_chamado_cockpit.routine_schedule_log    ";
        }else{
                $sql .= "
                LEFT JOIN tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado_cockpit.fabrica = {$login_fabrica}
                LEFT JOIN tbl_routine_schedule_log ON tbl_routine_schedule_log.routine_schedule_log = tbl_hd_chamado_cockpit.routine_schedule_log";
        }

        $sql .= "
                LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
                LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin AND tbl_cliente_admin.fabrica = {$login_fabrica}
                LEFT JOIN tbl_posto_distribuidor_sla_default ON tbl_posto_distribuidor_sla_default.posto = tbl_posto_fabrica.posto AND tbl_posto_distribuidor_sla_default.fabrica = {$login_fabrica}

                LEFT JOIN tbl_distribuidor_sla distribuidor_principal ON distribuidor_principal.centro = json_field('centroDistribuidor', tbl_hd_chamado_cockpit.dados) AND distribuidor_principal.fabrica = {$login_fabrica} AND distribuidor_principal.unidade_negocio = tbl_os_campo_extra.campos_adicionais::jsonb->>'unidadeNegocio'

                LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado AND tbl_defeito_reclamado.fabrica = {$login_fabrica}
                LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os.admin AND tbl_admin.fabrica = {$login_fabrica}
                LEFT JOIN tbl_cidade regiao_distribuidor_principal ON regiao_distribuidor_principal.cidade = distribuidor_principal.cidade
                LEFT JOIN (
                    SELECT DISTINCT unidade_negocio, cidade
                    FROM tbl_distribuidor_sla
                    WHERE fabrica = {$login_fabrica}
                ) AS unidades ON unidades.unidade_negocio = tbl_os_campo_extra.campos_adicionais::jsonb->>'unidadeNegocio'
                LEFT JOIN tbl_cidade unidade_negocio ON unidade_negocio.cidade = unidades.cidade
		";
    }

        if((in_array($login_fabrica,array(87,94,115,116,117,120,141,144,145,153,156,158,161,163,167,169,170,171,173,174,175,176,177,203))) AND !empty($descricao_tipo_atendimento)){
            $sql2_cond_tipo_atendimento = " AND tbl_tipo_atendimento.tipo_atendimento in ($descricao_tipo_atendimento) ";
        }

        if($login_fabrica == 153){
            $sql .= " left join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto and tbl_os_item.fabrica_i = $login_fabrica";
        }

        if(in_array($login_fabrica, array(52, 74, 152,180,181,182))){
            $sql .= " LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os_campo_extra.fabrica = $login_fabrica ";
        }

        if ($login_fabrica == 117 && isset($_REQUEST['macro_linha']) && !empty($_REQUEST['macro_linha'])) {
            $sql .= " LEFT JOIN tbl_macro_linha_fabrica ON(tbl_macro_linha_fabrica.linha = tbl_linha.linha AND tbl_macro_linha_fabrica.fabrica = {$login_fabrica}) LEFT JOIN tbl_macro_linha ON(tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha)";
        }

        if($login_fabrica == 50){
            if(isset($_GET["dashboard"]) && $_GET["dashboard"] == "sim"){

                switch ($_GET["intervalo_dia"]) {
                    case "0-10":  $cond_intervalo = " AND (current_date - data_abertura) BETWEEN 0 AND 10 "; break;
                    case "11-20": $cond_intervalo = " AND (current_date - data_abertura) BETWEEN 11 AND 20 "; break;
                    case "> 20":  $cond_intervalo = " AND (current_date - data_abertura) BETWEEN 21 AND 30 "; break;
                    case "> 30":  $cond_intervalo = " AND (current_date - data_abertura) BETWEEN 31 AND 90 "; break;
                    case "> 90":  $cond_intervalo = " AND (current_date - data_abertura) BETWEEN 91 AND 300 "; break;
                }

                $cond_dash = "
                    AND tbl_os.posto != 6359
                    AND tbl_os.data_fechamento ISNULL
                    AND tbl_os.finalizada ISNULL
                    AND tbl_os.excluida IS NOT TRUE
                    $cond_intervalo
                ";
            }
        }

        if ($login_fabrica == 164) {
            $sql .= " LEFT JOIN tbl_segmento_atuacao ON tbl_os.segmento_atuacao = tbl_segmento_atuacao.segmento_atuacao AND tbl_segmento_atuacao.fabrica = $login_fabrica ";
        }

        $sql .= "
                LEFT JOIN tbl_posto_linha           ON tbl_posto_linha.linha         = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
                LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
                {$joinPesquisaSatisfacao}
                $left_tecnico
                WHERE tbl_os.fabrica = $login_fabrica {$sql2_cond_tipo_atendimento}
                {$wherePesquisaSatisfacao}
                AND   $especifica_mais_2
                $cond_dash
                $cond_admin
                $cond_tecnico

                ";
        if($login_fabrica == 35){
            if($os_aguardando_troca ==  "t"){
                $sql .= " AND tbl_auditoria_os.auditoria_status = 8 and tbl_auditoria_os.observacao = 'Valor de Peças' ";
            }
        }

        if($login_fabrica == 153 and $recall == 't'){
            $recall = array('recall' => true);
            $recall = json_encode($recall, true);
            $sql .= " AND tbl_os_item.parametros_adicionais = '$recall' ";
        }

        if ($login_fabrica == 42 AND $_POST["entrega_tecnica"] == 't') {
            $sql .= " AND tbl_tipo_atendimento.entrega_tecnica IS TRUE ";
        }

        if(!in_array($login_fabrica,array(3,11,20,24,30,45,172))) {
            if(!in_array($login_fabrica,array(14,35,50,153)) && !$cancelaOS){
                $sql .=" AND tbl_os.excluida IS NOT TRUE ";
            }
            if(!isset($_GET["dashboard"])){
                $sql .= " AND (tbl_os_extra.status_os NOT IN (13,15) OR tbl_os_extra.status_os IS NULL)";
            }
        }

        if ($cancelaOS) {
            $sql .= " AND (tbl_os.excluida IS NOT TRUE OR (tbl_os.excluida IS TRUE AND tbl_os.status_checkpoint = 28)) ";
        }


        if((in_array($login_fabrica, [30]) && empty($sua_os)) || ($login_fabrica == 3 && !empty($os_cancelada))) {
            if($os_cancelada == 1){
                if ($login_fabrica == 30) {
                    $sql .= "\nAND tbl_os.excluida IS TRUE\n ";
                } else {
                    $sql .= "\nAND tbl_os.cancelada IS TRUE\n ";
                }
            }else if ($login_fabrica != 3){
                $sql .= "\nAND tbl_os.excluida IS NOT TRUE\n ";
            }
        }

		if ($login_fabrica == 158)  {
			if($_POST['os_nao_exportada'] == '1') {
				$os_nao_exportada = $_POST['os_nao_exportada'];
				$sql .= "AND tbl_os.exportado ISNULL" ;
			}
        }

        #HD 13940 - Para mostrar as OS recusadas
        if($login_fabrica==20) {
            $sql .=" AND  (status_os NOT IN (13,15) OR status_os IS NULL)";
        }

        if (strlen($linha) > 0 && !in_array($login_fabrica, array(117))) { // HD 72899
            $sql .= " AND tbl_linha.linha in ($linha)  ";
        }

        if (strlen($familia) > 0 || !empty($_REQUEST["familia"])) { // HD 72899
            if (!strlen($familia)) {
                $familia = $_REQUEST['familia'];
            }

            if ($login_fabrica == 148) {
                $sql .= " AND tbl_familia.familia IN (".implode(',', $familia).")";
            } else {
                $sql .= " AND tbl_familia.familia = $familia ";
            }
        }

        if (!empty($condicao_tecnico) ) {
            $sql .= $condicao_tecnico;
        }
        if ($login_fabrica == 24 OR $login_fabrica == 74) { //hd_chamado=2588542
            $sql .= $cond_congelada;
        }

        if (in_array($login_fabrica, array(152,180,181,182))) {
            if (!empty($_POST["classificacao_esab"])) {
                $classificacao_filtro = $_POST["classificacao_esab"];

                $sql .= " AND tbl_os_campo_extra.campos_adicionais::jsonb->'classificacao' ? '$classificacao_filtro' ";
            }

        }

        if ($login_fabrica == 52) { /*HD - 4304128*/
            if (!empty($_POST["consumidor_pais"])) {
                $pais_filtro = $_POST["consumidor_pais"];

                $sql .= " AND tbl_os_campo_extra.campos_adicionais::jsonb->'pais' ? '$pais_filtro' ";
            }
        }


        if(strlen($consulta_cidade) > 0){
            $sql .= $cons_sql_cidade;
        }

        if(strlen($cliente_admin) > 0){
            $sql .= "
                AND tbl_cliente_admin.cliente_admin = $cliente_admin
            ";
        }

        if (strlen($idPosto) > 0) {
            $sql .= " AND (tbl_os.posto = '$idPosto' OR distrib.posto = '$idPosto')";
        }

        if (strlen($produto_referencia) > 0) {
            $sql .= " AND tbl_produto.referencia = '$produto_referencia' ";
        }

        if (strlen($admin) > 0) {
            $sql .= " AND tbl_os.admin = '$admin' ";
        }
        if(in_array($login_fabrica,array(1,3,52,86)) or $multimarca == 't' ) {
            $sql .= " AND $cond_marca ";
        }

        if (in_array($login_fabrica, [177]) && !empty($_POST['status_orcamento'])) {
            $status_orcamento_desc = $_POST['status_orcamento'];

            $sql .= "AND tbl_status_os.descricao = '$status_orcamento_desc'";
        }

        if($login_fabrica == 7 ){
            $sql .= " AND $cond_natureza AND $cond_classificacao_os"; // HD 75762 para Filizola
        }

        if($login_fabrica == 137 && !empty($lote)){
            $sql .= " AND tbl_os.serie ilike '%{$lote}%' "; // HD 75762 para Filizola
        }

        if (strlen($lote) and $login_fabrica == 161) { // HD 3521169
            $lote = preg_replace('/\D/', '', $lote); // tira o 'L' se mandou
            $sql .= " AND tbl_os.serie ~ 'L$lote$' ";
        }

        if($login_fabrica == 45) {
            if(strlen($rg_produto)>0){
                $sql .= " AND tbl_os.os IN (SELECT os FROM tbl_produto_rg_item WHERE UPPER(rg) = '$rg_produto') ";
            }
        }
        ##tirou o ilike porque estava travando o banco 30/06/2010 o samuel que pediu para tirar
        if (strlen($os_posto) > 0) { // HD 72899
            $sql .= " AND tbl_os.os_posto = '$os_posto' ";
        }

        if ($os_estoque_posto == 1) {
            $sql .= " AND tbl_os.conferido_saida IS TRUE ";
        }

        if (strlen($sua_os) > 0) {
            #A Black tem consulta separada(os_consulta_avancada.php).
            if ($login_fabrica == 1) {
                $pos = strpos($sua_os, "-");

                if ($pos === false) {
                    //hd 47506
                    if(strlen ($sua_os) > 11){
                        $pos = strlen($sua_os) - (strlen($sua_os)-5);
                    } elseif(strlen ($sua_os) > 10) {
                        $pos = strlen($sua_os) - (strlen($sua_os)-6);
                    } elseif(strlen ($sua_os) > 9) {
                        $pos = strlen($sua_os) - (strlen($sua_os)-5);
                    }else{
                        $pos = strlen($sua_os);
                    }
                }else{

                    //hd 47506
                    if(strlen (substr($sua_os,0,$pos)) > 11){#47506
                        $pos = $pos - 7;
                    } else if(strlen (substr($sua_os,0,$pos)) > 10) {
                        $pos = $pos - 6;
                    } elseif(strlen ($sua_os) > 9) {
                        $pos = $pos - 5;
                    }
                }
                if(strlen ($sua_os) > 9) {
                    #$xsua_os = substr($sua_os, $pos,strlen($sua_os));
                    if(strlen($sua_os) == 13){
                        // 200213
                        $codigo_posto = substr($sua_os,0,6);
                    }else{
                        $codigo_posto = substr($sua_os,0,5);
                    }

                    $sqlPosto = "SELECT posto from tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
                    $res = pg_exec($con,$sqlPosto);
                    $xposto = pg_result($res,0,posto);
					if(!empty($xposto)) {
						$sql .= " AND tbl_os.posto = $xposto ";
					}
                }
            }
            $sua_os = strtoupper ($sua_os);
            $pos = strpos($sua_os, "-");
            if ($pos === false && !in_array($login_fabrica, array(121,137,144,169,170,173,175,178,183))) {

                if(!ctype_digit($sua_os)){
                    $sql .= " AND tbl_os.sua_os = '$sua_os' ";

                }else{
                    //hd 47506 - acrescentado OR "tbl_os.sua_os = '$sua_os'"
                    //HD 683858 - acrescentado OR "tbl_os.os = $sua_os"
                    if($login_fabrica ==1){
                        #$sql .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os like '%$xsua_os' )";

                    }else{
                          if($login_fabrica == 144) {
                         $sql .=  " AND (tbl_os.os_numero::text like '$sua_os%' OR tbl_os.sua_os::text  like '$sua_os%')";

                    }else{
                        $sql .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os  = '$sua_os')";

                    }
                }
            }
             }else{

                $conteudo = explode("-", $sua_os);
                $os_numero    = $conteudo[0];
                $os_sequencia = $conteudo[1];
                if(!ctype_digit($os_sequencia) && !in_array($login_fabrica, array(121,137,144,169,170,175)) and !$novaTelaOs) {
                    $sql .= " AND tbl_os.sua_os = '$sua_os' ";

                }else{
                    if($login_fabrica ==1) { // HD 51334
                        $sua_os = "000000" . trim ($sua_os);
                        if(strlen ($sua_os) > 12 AND $login_fabrica == 1) {
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
                        }elseif(strlen ($sua_os) > 11 AND $login_fabrica == 1){#46900
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 6 , 6);
                        }else{
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 5 , 5);
                        }
                        $sua_os = strtoupper ($sua_os);

                        $sql .= "   AND (
                                    tbl_os.sua_os = '$sua_os' OR
                                    tbl_os.sua_os = '0$sua_os' OR
                                    tbl_os.sua_os = '00$sua_os' OR
                                    tbl_os.sua_os = '000$sua_os' OR
                                    tbl_os.sua_os = '0000$sua_os' OR
                                    tbl_os.sua_os = '00000$sua_os' OR
                                    tbl_os.sua_os = '000000$sua_os' OR
                                    tbl_os.sua_os = '0000000$sua_os' OR
                                    tbl_os.sua_os = '00000000$sua_os' OR
                                    tbl_os.sua_os = substr('$sua_os2',6,length('$sua_os2')) OR
                                    tbl_os.sua_os = substr('$sua_os2',7,length('$sua_os2'))     ";
                        /* hd 4111 */
                        for ($i=1;$i<=40;$i++) {
                               $sql .= "OR tbl_os.sua_os = '$sua_os-$i' ";
                        }
                        $sql .= " OR 1=2) ";


                    }else{
                        if (in_array($login_fabrica, array(121,137,144,169,170,175)) or $novaTelaOs) {
                            $sql .= " AND tbl_os.sua_os ilike '%$sua_os%'";
                        } elseif(in_array($login_fabrica, array(157))) {
                            $os_numero = (int) $os_numero;
                            $sql .= " AND tbl_os.os_numero = $os_numero AND tbl_os.os_sequencia = '$os_sequencia' ";

                        } else {
                            $sql .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
                        }
                    }
                }

            }
        }

        //HD 211825: Filtro por tipo de OS: Consumidor/Revenda
        if (strlen($consumidor_revenda_pesquisa)) {
            $sql .= " AND tbl_os.consumidor_revenda='$consumidor_revenda_pesquisa'";
        }

        if (strlen($os_off) > 0) {
            #$sql .= " AND (tbl_os.sua_os_offline LIKE '$os_off%') ";
            $sql .= " AND (tbl_os.sua_os_offline = '$os_off') ";

        }

        if (!empty($numero_reclamacao)){
            $sql .= " AND tbl_os.sua_os_offline = '$numero_reclamacao' ";
        }

        if (strlen($serie) > 0) {
            if($login_fabrica == 94 ) {
                $sql .= " AND lpad(tbl_os.serie, 12, '0') = lpad('$serie', 12, '0') ";
            }else{
                $sql .= " AND tbl_os.serie = '$serie'";
            }
        }

        if (strlen($nf_compra) > 0) {

            if($login_fabrica == 1){
                $nf_compra = (int)$nf_compra;
                $sql .= " AND tbl_os.nota_fiscal ilike '%$nf_compra'";
            }else{
                $sql .= " AND tbl_os.nota_fiscal = '$nf_compra'";
            }
        }

        if (strlen($consumidor_nome) > 0) {
            if($login_fabrica == 183){
                $sql .= " AND tbl_os.consumidor_nome ILIKE '%$consumidor_nome%'";
            }else{
                $sql .= " AND tbl_os.consumidor_nome = '$consumidor_nome'";
            }
        }

        if (strlen($consumidor_cpf) > 0) {
            $sql .= " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
        }

        if (strlen($os_aberta) > 0) {
            $sql .= " AND tbl_os.os_fechada IS FALSE
                      AND tbl_os.excluida IS NOT TRUE";
        }

        #HD 234532
        if (strlen($status_checkpoint) > 0) {
            if($status_checkpoint == 10){
                $sql .= " AND tbl_os.hd_chamado isnull";
            }else{
                $sql .= " AND tbl_os.status_checkpoint in ($status_checkpoint) ";
            }
        }


        #HD 115630---------
        if($login_fabrica==35){
            if (strlen($os_finalizada) > 0) {
                $sql .= " AND tbl_os.os_fechada IS TRUE
                          AND tbl_os.excluida IS NOT TRUE";
            }

        }
        #------------------
        if ($os_situacao == "APROVADA") {
            $sql .= " AND tbl_extrato.aprovado IS NOT NULL ";
        }
        if ($os_situacao == "PAGA") {
            $sql .= " AND tbl_extrato_financeiro.data_envio IS NOT NULL ";
        }

        if ($os_situacao == "FINALIZADASEMEXTRATO") {
            $sql .= " AND tbl_os_extra.extrato IS NULL ";
            $sql .= " AND tbl_os.os_fechada IS TRUE
                          AND tbl_os.excluida IS NOT TRUE";
        }

        if (strlen($revenda_cnpj) > 0) {
            //HD 286369: Voltando pesquisa de CNPJ da revenda para apenas 8 dígitos iniciais
            $sql .= " AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%' ";
            //$sql .= " AND tbl_os.revenda_cnpj = '$revenda_cnpj' ";
        }

        if (strlen($pais) > 0) {
            $sql .= " AND tbl_posto.pais ='$pais' ";
        }

        if ($login_fabrica == 11 or $login_fabrica == 172){
            $sql .= $sql_rg_produto ;
        }

        if($login_fabrica == 141){
            if(strlen($consulta_estado) > 0){
                $sql .= " AND tbl_cidade.estado IN ('$consulta_estado')";
            }
        }else{
            if(strlen($consulta_estado) > 0){
                if ($login_fabrica != 104) {
                    $sql .= " AND tbl_posto_fabrica.contato_estado IN ('$consulta_estado')";
                } else {
                    $sql .= " AND tbl_os.consumidor_estado IN ('$consulta_estado')";
                }
            }
        }

        if ($login_fabrica == 45 AND strlen($regiao) > 0) {
            if ($regiao == 1) {
                $sql .= " AND tbl_posto_fabrica.contato_estado = 'SP'";
            }
            if ($regiao == 2) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('SC', 'RS', 'PR')";
            }
            if ($regiao == 3) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('RJ', 'ES', 'MG')";
            }
            if ($regiao == 4) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('GO', 'MS', 'MT', 'DF', 'CE', 'RN')";
            }
            if ($regiao == 5) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('SE','AL', 'PE', 'PB', 'BA')";
            }
            if ($regiao == 6) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('TO', 'PA', 'AP', 'RR', 'AM', 'AC', 'RO', 'MA', 'PI')";
            }
            if ($regiao == 7) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('GO', 'MS', 'MT', 'DF', 'CE', 'RN', 'TO', 'PA', 'AP', 'RR', 'AM', 'AC', 'RO', 'MA', 'PI')";
            }

        }

        if ($login_fabrica == 80 AND strlen($regiao) > 0) {
            if ($regiao == 1) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('PE','PB')";
            }
            if ($regiao == 2) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('RJ','GO','MG','AC','AM','DF','ES','PI','MA','MS','MT','PA','PR','RO','RR','RS','SC','TO','AP')";
            }
            if ($regiao == 3) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('BA','SE','AL')";
            }
            if ($regiao == 4) {
                $sql .= " AND tbl_posto_fabrica.contato_estado IN ('CE','RN','SP')";
            }
        }

        if($login_fabrica == 50 AND strlen($tipo_os) >0) { // HD 48198
            if($tipo_os=='REINCIDENTE'){
                $sql .=" AND tbl_os.os_reincidente IS TRUE ";
            }elseif($tipo_os=='MAIS_CINCO_DIAS'){
                $sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 5
                         AND CURRENT_DATE - tbl_os.data_abertura < 10
                         AND tbl_os.data_fechamento IS NULL
                         AND tbl_os.excluida IS NOT TRUE ";
            }elseif($tipo_os=='MAIS_DEZ_DIAS'){
                $sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 10
                         AND CURRENT_DATE - tbl_os.data_abertura < 20
                         AND tbl_os.data_fechamento IS NULL
                         AND tbl_os.excluida IS NOT TRUE ";
            }elseif($tipo_os=='MAIS_VINTE_DIAS'){
                $sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 20
                         AND tbl_os.data_fechamento IS NULL
                         AND tbl_os.excluida IS NOT TRUE ";
            }elseif($tipo_os == 'EXCLUIDAS'){
                $sql .=" AND tbl_os.excluida IS TRUE ";
            }
        }

        if ($login_fabrica == 45 AND strlen($tipo_os) > 0) { // HD 62394 waldir
            if ($tipo_os == 'REINCIDENTE') {
                $sql .=" AND tbl_os.os_reincidente IS TRUE ";
            } elseif($tipo_os == 'BOM') {
                $sql .=" AND CURRENT_DATE - tbl_os.data_abertura < 16
                         AND tbl_os.data_fechamento IS NULL
                         AND tbl_os.excluida IS NOT TRUE ";
            } elseif ($tipo_os == 'MEDIO') {
                $sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 15
                         AND CURRENT_DATE - tbl_os.data_abertura < 26
                         AND tbl_os.data_fechamento IS NULL
                         AND tbl_os.excluida IS NOT TRUE ";
            } elseif ($tipo_os == 'RUIM') {
                $sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 25
                         AND tbl_os.data_fechamento IS NULL
                         AND tbl_os.excluida IS NOT TRUE ";
            } elseif ($tipo_os == 'EXCLUIDA') {
                $sql .=" AND tbl_os.excluida IS TRUE ";
            }
        }

        if(in_array($login_fabrica, array(3,148))){
            if(!empty($tipo_atendimento)){
                $sql .= " AND tbl_os.tipo_atendimento = {$tipo_atendimento} ";
            }
            if(isset($_POST["fora_garantia"])){
                $sql .= " AND tbl_os.tipo_os = 17 ";
            }
        }

        if (in_array($login_fabrica,array(7,30,52,96)) and !empty($login_cliente_admin)) {
            $sql .= " AND tbl_os.cliente_admin = $login_cliente_admin ";
        }

        if($admin_consulta_os AND $login_fabrica == 19){
            $sql .= " AND tbl_os.tipo_atendimento = 20
                    AND tbl_os.os_fechada IS TRUE
                    AND tbl_os.excluida IS NOT TRUE";
        }

        if (!empty($nf_recebimento)) {
            $sql .= ' AND campos_adicionais LIKE \'%"nf_envio":"' . $nf_recebimento . '"%\' ';
        }

        if (in_array($login_fabrica, array(169,170))){
            if (isset($_GET['finalizada_index'])) {// dados vem do arquivo admin/dashboard_novo.php
                if ($_GET['finalizada_index'] == '0'){
                    $sql .= 'AND (EXTRACT(DAYS FROM (tbl_os.data_conserto - tbl_os.data_digitacao)) <= 10) AND tbl_os.data_conserto IS NOT NULL';
                }

                if ($_GET['finalizada_index'] == 1) {
                    $sql .= 'AND (EXTRACT(DAYS FROM (tbl_os.data_conserto - tbl_os.data_digitacao)) > 10) AND tbl_os.data_conserto IS NOT NULL';
                }
            }

            $sql .= ' AND tbl_os.posto != 6359 ';
        }

        if($login_fabrica == 52){ ## HD-2507504 ##
            if (strlen($pre_os)>0) {
                $sql_pre_os = " AND tbl_os.hd_chamado = $pre_os";

                $sql .= $sql_pre_os;
            }
        }

        if ($login_fabrica == 117) {
            $xlinha = (isset($_REQUEST['macro_linha']) && !empty($_REQUEST['macro_linha'])) ? " AND tbl_macro_linha.macro_linha = {$_REQUEST['macro_linha']}" : '';
            $xmacro_familia = (isset($_REQUEST['linha']) && !empty($_REQUEST['linha'])) ? " AND tbl_linha.linha = {$_REQUEST['linha']}" : '';

            $sql .= "{$xlinha} {$xmacro_familia}";
        }

        //HD 393737 IBBL
        if($login_fabrica == 90){
            $sql .= $monta_sql;
        }

        if(in_array($login_fabrica, array(164)) && strlen($cond_intervalo) > 0){

            $sql .= $cond_intervalo;
			$sql .= " $cond_regiao ";
        }
        if (strstr($_SERVER['PHP_SELF'],"admin_cliente")) {
            $sql .= " AND tbl_os.cliente_admin = {$login_cliente_admin}";
        }

        $sql .= $conds_sql;

        #if ($login_fabrica == 7){
        #    $sql .= " ORDER BY tbl_os.data_abertura ASC, LPAD(tbl_os.sua_os,20,'0') ASC ";
        #} elseif ($login_fabrica == 45){
        #    $sql .= " ORDER BY tbl_os.data_abertura DESC ";
        #}elseif ($login_fabrica == 30){
        #    $sql .= " ORDER BY termino ASC";
        #}else {
        #    # $sql .= " ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC "; Sameul 02-07-2009
        #    if ($login_fabrica == 3 and $posto_ordenar == 'sim') {
        #        $sql .= " ORDER BY tbl_posto_fabrica.codigo_posto ";
        #    }else if($login_fabrica == 121 OR $login_fabrica == 137){
        #        $sql .= " ORDER BY tbl_os.os ASC ";
        #    } else {
        #        $sql .= " ORDER BY tbl_os.os DESC ";
        #    }

        #    if ($login_fabrica == 30) {
        #        $sql.= ', pedido ASC';
        #    }
        #}
         //echo nl2br($sql);
        // echo $login_fabrica."<<<<>>>> ";
        if ($login_fabrica == 72){   //inicio excel mallory

            $sql_excel =  "SELECT
                                    $distinct_os
                                    tbl_os.os                                                         ,
                                    tbl_os.sua_os                                                     ,
                                    tbl_os.type                                                       ,
                                    tbl_os.nota_fiscal                                                ,
                                    tbl_os.os_numero                                                  ,
                                    sua_os_offline                                                    ,
                                    LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
                                    TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY HH24:MI')  AS digitacao         ,
                                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
                                    TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
                                    TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
                                    to_char(tbl_os.data_nf,'DD/MM/YYYY')        as data_nf            ,
                                    $column_serie
                                    tbl_os.excluida                                                   ,
                                    tbl_os.mao_de_obra                                                   ,
                                    tbl_os.motivo_atraso                                              ,
                                    tbl_os.tipo_os_cortesia                                           ,
                                    tbl_os.consumidor_revenda                                         ,
                                    tbl_os.consumidor_nome                                            ,
                                    tbl_os.consumidor_fone                                            ,
                                    tbl_os.consumidor_cpf                                            ,
                                    tbl_os.defeito_reclamado_descricao,
                                    tbl_solucao.descricao as solucao_os,
                                    tbl_posto_fabrica.contato_estado,
                                    tbl_posto_fabrica.contato_cidade,
                                    tbl_defeito_constatado.defeito_constatado as cod_defeito_constatado,
                                    tbl_defeito_constatado.descricao as defeito_constatado,
                                    tbl_os.revenda_nome                                               ,
                                    tbl_os.tipo_atendimento                                           ,
                                    tbl_os.os_reincidente                      AS reincidencia        ,
                                    tbl_os.os_posto                                                   ,
                                    tbl_os.aparencia_produto                                          ,
                                    tbl_os.tecnico_nome                                               ,
                                    tbl_os.rg_produto                                                 ,
                                    tbl_os.hd_chamado                               ,
                                    tbl_tipo_atendimento.descricao                               ,
                                    tbl_tipo_atendimento.grupo_atendimento          ,
                                    tbl_posto_fabrica.codigo_posto                                ,
                                    tbl_posto_fabrica.contato_estado                             ,
                                    tbl_posto_fabrica.contato_cidade                                  ,
                                    tbl_posto_fabrica.credenciamento                                  ,
                                    tbl_posto.nome                              AS posto_nome         ,
                                    tbl_posto.capital_interior                                        ,
                                    tbl_posto.estado                                                  ,
                                    tbl_os_extra.impressa                                             ,
                                    tbl_os_extra.extrato                                              ,
                                    tbl_os_extra.os_reincidente                                       ,
                                    tbl_produto.referencia                      AS produto_referencia ,
                                    tbl_produto.descricao                       AS produto_descricao  ,
                                    tbl_produto.voltagem                        AS produto_voltagem   ,
                                    tbl_produto.referencia_fabrica              AS produto_referencia_fabrica,
                                    tbl_os.status_checkpoint                                          ,
                                    distrib.codigo_posto                        AS codigo_distrib     ,
                                    TO_CHAR(tbl_os_item.digitacao_item,'DD/MM/YYYY') AS digitacao_item ,
                                    tbl_peca.referencia                 AS peca_referencia,
                                    tbl_peca.descricao                  AS peca_descricao,
                                    tbl_os_item.pedido                  AS pedido,
                                    tbl_faturamento.nota_fiscal AS nota_fiscal_faturamento,
                                    tbl_faturamento.emissao AS emissao,
                                    tbl_os_produto.servico,
                                    tbl_os.revenda_cnpj,
                                    tbl_os.revenda_nome,                                    
                                    tbl_status_checkpoint.descricao as descricao_status_checkpoint,
                                    status_os_ultimo AS status_os
                                    $sql_data_conserto
                            FROM    tbl_os
                                $join_especifico";

            $sql_excel .= " left join tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado and tbl_os.fabrica = $login_fabrica";
            $sql_excel .= " left join tbl_solucao on tbl_solucao.solucao = tbl_os.solucao_os and tbl_os.fabrica = $login_fabrica ";
            $sql_excel .= " LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                            JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
                            JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                            $join_produto
                            LEFT JOIN      tbl_linha       			ON  tbl_produto.linha    			= tbl_linha.linha
                            LEFT JOIN      tbl_familia     			ON  tbl_produto.familia  			= tbl_familia.familia
                            LEFT JOIN      tbl_os_extra    			ON  tbl_os_extra.os      			= tbl_os.os
                            JOIN           tbl_fabrica 	   			ON  tbl_fabrica.fabrica  			= tbl_os.fabrica
                            LEFT JOIN      tbl_status_checkpoint	ON  tbl_status_checkpoint.status_checkpoint	= tbl_os.status_checkpoint";
            $sql_excel .= " left JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                            LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                            left join tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                            left join tbl_faturamento_item on tbl_pedido_item.pedido = tbl_faturamento_item.pedido and tbl_pedido_item.peca = tbl_faturamento_item.peca
                            left join tbl_faturamento using(faturamento)
                            LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca";

            if (strlen($os_situacao) > 0) {
                $leftSit = "";
                if ($os_situacao == "FINALIZADASEMEXTRATO") {
                   $leftSit = " LEFT ";
                }
                $sql_excel .= " {$leftSit} JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato";
                if ($os_situacao == "PAGA")
                    $sql_excel .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
            }

            $sql_excel .= " LEFT JOIN tbl_posto_linha           ON tbl_posto_linha.linha         = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
                            LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
                            {$joinPesquisaSatisfacao}
                            WHERE tbl_os.fabrica = $login_fabrica {$sql2_cond_tipo_atendimento}
                                {$wherePesquisaSatisfacao}
                                AND   $especifica_mais_2
                                $cond_admin";

            if (strlen($linha) > 0) {
                $sql_excel .= " AND tbl_linha.linha = $linha ";
            }

            if (strlen($familia) > 0) {
                $sql_excel .= " AND tbl_familia.familia = $familia ";
            }

            if (!empty($condicao_tecnico) ) {
                $sql_excel .= $condicao_tecnico;
            }

            if(strlen($consulta_cidade) > 0){
                $sql_excel .= $cons_sql_cidade;
            }

            if(strlen($cliente_admin) > 0){
                $sql_excel .= " AND tbl_cliente_admin.cliente_admin = $cliente_admin ";
            }

            if (strlen($idPosto) > 0) {
                $sql_excel .= " AND (tbl_os.posto = '$idPosto' OR distrib.posto = '$idPosto')";
            }

            if (strlen($produto_referencia) > 0) {
                $sql_excel .= " AND tbl_produto.referencia = '$produto_referencia' ";
            }

            if (strlen($admin) > 0) {
                $sql_excel .= " AND tbl_os.admin = '$admin' ";
            }

            ##tirou o ilike porque estava travando o banco 30/06/2010 o samuel que pediu para tirar
            if (strlen($os_posto) > 0) {
                $sql_excel .= " AND tbl_os.os_posto = '$os_posto' ";
            }

             if (strlen($sua_os) > 0) {
                $sua_os = strtoupper ($sua_os);
                    $pos = strpos($sua_os, "-");
                if ($pos === false && $login_fabrica != 121 && $login_fabrica != 137) {
                    if(!ctype_digit($sua_os)){
                        $sql_excel .= " AND tbl_os.sua_os = '$sua_os' ";

                    }else{
                        //hd 47506 - acrescentado OR "tbl_os.sua_os = '$sua_os'"
                            //HD 683858 - acrescentado OR "tbl_os.os = $sua_os"
                            if($login_fabrica ==1){
                                #$sql_excel .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os like '%$xsua_os' )";
                                $sql_excel .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os = '$xsua_os' or tbl_os.os = $sua_os )";
                            }else{
                                $sql_excel .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os  = '$sua_os')";
                            }
                        }
                  }else{
                    $conteudo = explode("-", $sua_os);
                        $os_numero    = $conteudo[0];
                        $os_sequencia = $conteudo[1];
                        if(!ctype_digit($os_sequencia) && $login_fabrica != 121 && $login_fabrica != 137){
                            $sql_excel .= " AND tbl_os.sua_os = '$sua_os' ";
                        }else{
                            $sql_excel .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
                        }
                    }
            }


            if (strlen($consumidor_revenda_pesquisa)) {
                $sql_excel .= " AND tbl_os.consumidor_revenda='$consumidor_revenda_pesquisa'";
            }

            if (strlen($os_off) > 0) {
                #$sql_excel .= " AND (tbl_os.sua_os_offline LIKE '$os_off%') ";
                $sql_excel .= " AND (tbl_os.sua_os_offline = '$os_off') ";
            }

            if (strlen($serie) > 0) {
                $sql_excel .= " AND tbl_os.serie = '$serie'";
            }

            if($login_fabrica == 160 or $replica_einhell){
                if(strlen($versao) > 0){
                    $sql_excel = " AND tbl_os.type = '$versao'";
                }
            }

            if (strlen($nf_compra) > 0) {
                $sql_excel .= " AND tbl_os.nota_fiscal = '$nf_compra'";
            }

            if (strlen($consumidor_nome) > 0) {
                $sql_excel .= " AND tbl_os.consumidor_nome = '$consumidor_nome'";
            }

            if (strlen($consumidor_cpf) > 0) {
                $sql_excel .= " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
            }

            if (strlen($os_aberta) > 0) {
                $sql_excel .= " AND tbl_os.os_fechada IS FALSE
                                AND tbl_os.excluida IS NOT TRUE";
            }

            if (strlen($status_checkpoint) > 0) {
                if($status_checkpoint == 10){
                    $sql_excel .= " AND tbl_os.hd_chamado isnull";
                }else{
                    $sql_excel .= " AND tbl_os.status_checkpoint = $status_checkpoint";
                }
            }

            if (strlen($admin_sap) > 0) {
                $sql_excel .= " AND tbl_posto_fabrica.admin_sap = {$admin_sap}";
            }

            if ($os_situacao == "APROVADA") {
                $sql_excel .= " AND tbl_extrato.aprovado IS NOT NULL ";
            }

            if ($os_situacao == "PAGA") {
                $sql_excel .= " AND tbl_extrato_financeiro.data_envio IS NOT NULL ";
            }

            if ($os_situacao == "FINALIZADASEMEXTRATO") {
                $sql_excel .= " AND tbl_os_extra.extrato IS NULL ";
                $sql_excel .= " AND tbl_os.os_fechada IS TRUE
                          AND tbl_os.excluida IS NOT TRUE";
            }

            if (strlen($revenda_cnpj) > 0) {
                //HD 286369: Voltando pesquisa de CNPJ da revenda para apenas 8 dígitos iniciais
                $sql_excel .= " AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%' ";
                //$sql_excel .= " AND tbl_os.revenda_cnpj = '$revenda_cnpj' ";
            }

            if (strlen($pais) > 0) {
                $sql_excel .= " AND tbl_posto.pais ='$pais' ";
            }

            if(strlen($consulta_estado) > 0){
                if ($login_fabrica != 104) {
                    $sql_excel .= " AND tbl_posto_fabrica.consumidor_estado IN ('$consulta_estado')";
                } else {
                    $sql_excel .= " AND tbl_os.consumidor_estado IN ('$consulta_estado')";
                }
            }

            /*$consultar_os_sem_listar_pecas = trim($_POST["consultar_os_sem_listar_pecas"]);
            if($consultar_os_sem_listar_pecas == 't') {
                $sql_excel .= " GROUP BY tbl_os.os";
            }*/

            $sql_excel .= $conds_sql;

            $sql_excel .= " ORDER BY tbl_os.os DESC ";

            //die(nl2br($sql_excel));
			$resxls_excel = pg_query($con,$sql_excel);
            /*print_r(pg_fetch_all($resxls_excel)); exit;
            echo pg_last_error($con); exit;*/
        }//fim excel da Mallory

        $sqlT = str_replace ("\n"," ",$sql) ;
        $sqlT = str_replace ("\t"," ",$sqlT) ;
    
        $resT = pg_query ($con,$sqlT );

    $sql_order='';
	if ($login_fabrica == 7){
            $sql_order .= " ORDER BY tmp_os_consulta_lite_$login_admin.data_abertura ASC, LPAD(tmp_os_consulta_lite_$login_admin.sua_os,20,'0') ASC ";
        } elseif ($login_fabrica == 45){
            $sql_order .= " ORDER BY tmp_os_consulta_lite_$login_admin.data_abertura DESC ";
        }elseif ($login_fabrica == 30){
            $sql_order .= " ORDER BY tmp_os_consulta_lite_$login_admin.termino ASC";
        }else {
            # $sql .= " ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC "; Sameul 02-07-2009
            if ($login_fabrica == 3 and $posto_ordenar == 'sim') {
                $sql_order .= " ORDER BY tmp_os_consulta_lite_$login_admin.codigo_posto ";
            }else if($login_fabrica == 121 OR $login_fabrica == 137){
                $sql_order .= " ORDER BY tmp_os_consulta_lite_$login_admin.os ASC ";
            } else {
                $sql_order .= " ORDER BY tmp_os_consulta_lite_$login_admin.os DESC ";
            }

            if ($login_fabrica == 30) {
                $sql_order .= ', tmp_os_consulta_lite_'.$login_admin.'.pedido ASC';
            }
        }

    	$sql = "SELECT distinct * FROM tmp_os_consulta_lite_$login_admin ";
    	$sql .= $sql_order;

        $sqlT = str_replace ("\n"," ",$sql) ;
        $sqlT = str_replace ("\t"," ",$sqlT) ;

    	if($_POST["gerar_excel"] == "t"){
    //  echo nl2br($sql);
    		$resxls = pg_query ($con,$sql);
//     		echo pg_last_error($con);
    	}

        flush();

        ##### PAGINAÇÃO - INÍCIO #####
        $sqlCount  = "SELECT count(*) FROM tmp_os_consulta_lite_$login_admin ";
        #$sqlCount .= $sql;
        #$sqlCount .= ") AS count";

        require "_class_paginacao.php";

        // definicoes de variaveis
        $max_links = 11;                // máximo de links à serem exibidos
        $max_res   = 50;                // máximo de resultados à serem exibidos por tela ou pagina
        $mult_pag  = new Mult_Pag();    // cria um novo objeto navbar
        $mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

        $res = $mult_pag->Executar($sql, $sqlCount, $con, "otimizada", "pgsql");
        ##### PAGINAÇÃO - FIM #####
    }

    $resultados = pg_num_rows($res);

    $old_os = 0;

    if (pg_num_rows($res) > 0) {
        if (in_array($login_fabrica, array(169,170))) {
            $ordemStatus = "
                , CASE WHEN status_checkpoint = 0 THEN 0
                WHEN status_checkpoint = 1 THEN 1
                WHEN status_checkpoint = 2 THEN 2
                WHEN status_checkpoint = 8 THEN 3
                WHEN status_checkpoint = 45 THEN 4
                WHEN status_checkpoint = 46 THEN 5
                WHEN status_checkpoint = 47 THEN 6
                WHEN status_checkpoint = 3 THEN 7
                WHEN status_checkpoint = 4 THEN 8
                WHEN status_checkpoint = 14 THEN 9
                WHEN status_checkpoint = 30 THEN 10
                WHEN status_checkpoint = 9 THEN 11
                WHEN status_checkpoint = 48 THEN 12
                WHEN status_checkpoint = 49 THEN 13
                WHEN status_checkpoint = 50 THEN 14
                WHEN status_checkpoint = 28 THEN 15 END AS ordem
            ";
            $orderByStatus = "ORDER BY ordem ASC";
        }

        if (in_array($login_fabrica, [174])) {
            $ordemStatus = "
               ,CASE WHEN status_checkpoint = 0 THEN 0
                WHEN status_checkpoint = 40 THEN 1
                WHEN status_checkpoint = 1 THEN 2
                WHEN status_checkpoint = 2 THEN 3
                WHEN status_checkpoint = 3 THEN 4
                WHEN status_checkpoint = 41 THEN 5
                WHEN status_checkpoint = 42 THEN 6
                WHEN status_checkpoint = 43 THEN 7
                WHEN status_checkpoint = 4 THEN 8
                END AS ordem
            ";
            $orderByStatus = "ORDER BY ordem ASC";
        } else if ($telecontrol_distrib) {
            $ordemStatus = "
               ,CASE WHEN status_checkpoint = 1 THEN 0
                WHEN status_checkpoint = 37 THEN 1
                WHEN status_checkpoint = 35 THEN 2
                WHEN status_checkpoint = 2 THEN 3
                WHEN status_checkpoint = 36 THEN 4
                WHEN status_checkpoint = 3 THEN 5
                WHEN status_checkpoint = 4 THEN 6
                WHEN status_checkpoint = 9 THEN 7
                WHEN status_checkpoint = 0 THEN 8
                WHEN status_checkpoint = 39 THEN 9
                END AS ordem
            ";
            $orderByStatus = "ORDER BY ordem ASC";
        }

        if ($telecontrol_distrib && !isset($novaTelaOs)) {
            $campoDesc = ",CASE WHEN descricao = 'Aguardando Analise'
                          THEN 'Aguardando Analise Posto'
                          ELSE descricao
                          END AS descricao";
        } else {
            $campoDesc = ', descricao';
        }

        $sql_status   = "SELECT status_checkpoint {$campoDesc},cor $ordemStatus FROM tbl_status_checkpoint WHERE status_checkpoint IN (".$condicao_status.") {$where_tbl_status_checkpoint} $orderByStatus";
        $res_status   = pg_query($con, $sql_status);
        $total_status = pg_num_rows($res_status);
?>
<div id="resultado_consulta">
            <table border='0' cellspacing='0' cellpadding='0' width='700px' style="font-size:11px;" align='center'>
                <tr>
                    <td style='text-align: left; '  valign='bottom'>
<?php
        if($login_fabrica == 96 AND strlen($btn_acao_pre_os) > 0){
                        //Retirar OS status para BOSCH HD - 669464
        }else{
?>
                        <div align='left' style='position:relative;left:25'>
                            <h4><?=traduz('Status das OS')?></h4>
                            <table border='0' cellspacing='0' cellpadding='0'>
<?php
            for($i=0;$i<$total_status;$i++){

                $id_status = pg_fetch_result($res_status,$i,'status_checkpoint');
                $cor_status = pg_fetch_result($res_status,$i,'cor');
                $descricao_status = pg_fetch_result($res_status,$i,'descricao');

                if($login_fabrica == 148 AND $id_status == 28){//3049906 - HD-7730294
                    $descricao_status = "OS Cancelada2";
                }

                #Array utilizado posteriormente para definir as cores dos status
                $array_cor_status[$id_status] = $cor_status;

                if ($login_fabrica <> 87 OR ($login_fabrica == 87 AND $id_status != 0)) {
?>
                                <tr height='18'>
                                    <td width='18' >
                                        <div class="status_checkpoint" style="background-color:<?php echo $cor_status;?>">&nbsp;</div>
                                    </td>
                                    <td align='left'>
                                        <font size='1'>
                                            <b>
                                                <!-- <a href=\"javascript: filtro('vermelho')\"> -->
                                                    <? if ($login_fabrica == 165) {
                                                        switch ($descricao_status) {
                                                            case "Aguardando Faturamento":
                                                                $descricao_status = "Aguardando Expedição";
                                                                break;
                                                            default:
                                                                $descricao_status = $descricao_status;
                                                                break;
                                                        }
                                                    }

                                                    if($login_fabrica == 96 AND $id_status == 3){
                                                        $descricao_status = "Em conserto";
                                                    }

                                                    echo traduz($descricao_status); ?>
                                                <!-- </a> -->
                                            </b>
                                        </font>
                                    </td>

                                </tr>
<?php
                }
            }
    if ($login_fabrica == 120) { ?>
                                <tr>
                                    <td width='18' >
                                        <div class="status_checkpoint" style="background-color:#FF0000;">&nbsp;</div>
                                    </td>
                                    <td align='left'>
                                        <font size='1'>
                                            <b>
                                            <?=traduz('Cancelada')?>
                                            </b>
                                        </font>
                                    </td>
                                </tr>
                                <?
                                }
                                ?>
                            </table>
                        </div>
<?php
        }
?>
                    </td>
                    <td style='text-align: left; '  valign='bottom'>
<?php
        ##### LEGENDAS - INÍCIO #####
        echo "<div align='left' style='margin: 0 auto;width:90%;'>";
        echo "<table border='0' cellspacing='0' cellpadding='0' align='center' width='400px;'>";

        if ($login_fabrica == 96 && strlen($btn_acao_pre_os) > 0) { //HD391024

            echo "<tr height='18'>";
                echo "<td width='18' bgcolor='#C94040' class='legenda_os_cor'></td>";
                echo traduz("<td align='left' class='legenda_os_texto'>Fora de garantia</td>");
            echo "</tr>";
            echo "<tr height='3'><td colspan='2'></td></tr>";

            echo "<tr height='18'>";
                echo "<td width='18' bgcolor='#FFFF66' class='legenda_os_cor'></td>";
                echo traduz("<td align='left' class='legenda_os_texto'>Garantia</td>");
            echo "</tr>";
            echo "<tr height='3'><td colspan='2'></td></tr>";

            echo "<tr height='18'>";
                echo "<td width='18' bgcolor='#33CC00' class='legenda_os_cor'>&nbsp;</td>";
                echo traduz("<td align='left' class='legenda_os_texto'>Retorno de garantia</td>");
            echo "</tr>";
            echo "<tr height='3'><td colspan='2'></td></tr>";

        } else {
            if ($excluida == "t") {
                ?>
                <tr height='18'>
                    <td width='18' bgcolor='#FFE1E1' class='legenda_os_cor'><?php echo "&nbsp"; ?></td>
                    <td align='left' class='legenda_os_texto'>'<?=traduz("Excluídas do sistema")?>'</td>
                </tr>
                <tr height='3'><td colspan='2'></td></tr>
                <?php
            }

            if ($login_fabrica != 1) {
                if ($login_fabrica == 87){
                    $cor = "#40E0D0";
                }elseif($login_fabrica == 30){
                    $cor = "#5F9EA0";
                }else{
                    $cor = "#D7FFE1";
                }

                if (in_array($login_fabrica, array(72,152,180,181,182))) {
                    ?>
                    <tr height='3'>
                        <td width='55' bgcolor='#FF0000' class='legenda_os_cor'><?php echo "&nbsp"; ?></td>
                        <td align='left' class='legenda_os_texto'><?=traduz("OS cancelada")?></td>
                    </tr>
                    <tr height='3'><td colspan='2'></td></tr>
                    <?php
                }
                ?>

                <tr height='3'>
                    <td width='55' bgcolor='<?=$cor?>' class='legenda_os_cor'>&nbsp;</td>
                    <td align='left' class='legenda_os_texto'><?=traduz("Reincidências")?></td>
                </tr>
                <tr height='3'><td colspan='2'></td></tr>
                    <?php

            } else {

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FFC891' class='legenda_os_cor'></td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica == 85) { #HD 284058

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#AEAEFF' class='legenda_os_cor'></td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>Peça fora da garantia aprovada na intervenção da OS para gerar pedido</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica == 14) {

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#91C8FF' class='legenda_os_cor'></td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>OSs abertas há mais de 3 dias sem data de fechamento</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'></td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>OSs abertas há mais de 5 dias sem data de fechamento</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FFE1E1' class='legenda_os_cor'></td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>Excluídas do sistema</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            } else {

                if ($login_fabrica == 50) {

                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#91C8FF' class='legenda_os_cor'></td>";
                        echo traduz("<td align='left' class='legenda_os_texto'>OSs abertas há mais de 5 dias sem data de fechamento</td>");
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";

                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#FF6633' class='legenda_os_cor'></td>";
                        echo traduz("<td align='left' class='legenda_os_texto'>OSs abertas há mais de 10 dias sem data de fechamento</td>");
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";

                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'></td>";
                        echo traduz("<td align='left' class='legenda_os_texto'>OSs abertas há mais de 20 dias sem data de fechamento</td>");
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";

                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#FFE1E1' class='legenda_os_cor'></td>";
                        echo traduz("<td align='left' class='legenda_os_texto'>Excluídas do sistema</td>");
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";

                } else {

                    if ($login_fabrica == 45) {

                        echo "<tr height='18'>";
                            echo "<td width='18' bgcolor='#1e85c7' class='legenda_os_cor'></td>";
                            echo traduz("<td align='left' class='legenda_os_texto'>BOM (OSs abertas até 15 dias sem data de fechamento)</td>");
                        echo "</tr>";
                        echo "<tr height='3'><td colspan='2'></td></tr>";

                        echo "<tr height='18'>";
                            echo "<td width='18' bgcolor='#FF6633' class='legenda_os_cor'></td>";
                            echo traduz("<td align='left' class='legenda_os_texto'>MÉDIO (OSs abertas entre 15 dias e 25 dias sem data de fechamento)</td>");
                        echo "</tr>";
                        echo "<tr height='3'><td colspan='2'></td></tr>";

                        echo "<tr height='18'>";
                            echo "<td width='18' bgcolor='#9512cc' class='legenda_os_cor'></td>";
                            echo traduz("<td align='left' class='legenda_os_texto'>RUIM (OSs abertas a mais de 25 dias sem data de fechamento)</td>");
                        echo "</tr>";
                        echo "<tr height='3'><td colspan='2'></td></tr>";

                    } else if ($login_fabrica == 43) {

                        echo "<tr height='18'>";
                            echo "<td width='18' bgcolor='#FF0033' class='legenda_os_cor'></td>";
                            echo traduz("<td align='left' class='legenda_os_texto'>OSs abertas há mais de 10 dias sem data de fechamento</td>");
                        echo "</tr>";
                        echo "<tr height='3'><td colspan='2'></td></tr>";

                    } else {

                        if ($login_fabrica == 24) {
                            echo "<tr height='18'>";
                                echo "<td width='18' bgcolor='#54A8AE' class='legenda_os_cor'>&nbsp;</td>";
                                echo "<td align='left' class='legenda_os_texto'>OS com mais de 7 dias sem lançamento de peças</td>";
                            echo "</tr>";
                            echo "<tr height='3'><td colspan='2'></td></tr>";
                        }

                        if ($login_fabrica == 87){
                            $cor = "#A4B3FF";
                        }else{
                            $cor = "#91C8FF";
                        }
                        echo "<tr height='3'>";
                            echo "<td width='55' bgcolor='$cor' class='legenda_os_cor'></td>";
                            $qte_days = $login_fabrica == 91 ? "30" : "25";

                            ?><td align='left' nowrap class='legenda_os_texto'>
                                OSs abertas há mais de <?=$qte_days?> dias sem data de fechamento
                            </td>
                            <?php
                        echo "</tr>";
                        echo "<tr height='3'><td colspan='2'></td></tr>";

                    }

                }

                if ($login_fabrica == 35) {

                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'></td>";
                        echo traduz("<td align='left' class='legenda_os_texto'>Excluídas do sistema</td>");
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";

                }

            }

            if ($login_fabrica == 91 or $login_fabrica == 114) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FFCCCC' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo traduz("OS com Intervenção da Fábrica. Aguardando Liberação");
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if (in_array($login_fabrica, array(3,11,51,43,87,115,116,117,120,121,122,123,125,172))) {

                if ($login_fabrica == 87){
                    $cor = "#FFA5A4";
                }else {
                    $cor = "#FFCCCC";
                }

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='$cor' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo traduz("OS com Intervenção da Fábrica. Aguardando Liberação");
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

                if ($login_fabrica != 87) {

                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#FFFF99' class='legenda_os_cor'></td>";
                        echo "<td align='left' class='legenda_os_texto'> ";
                        echo traduz("OS com Intervenção da Fábrica. Reparo na Fábrica");
                        echo "</td>";
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";

                }

                if ($login_fabrica == 87){
                    $cor = "#FEFFA4";
                }else{
                    $cor = "#00EAEA";
                }

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='$cor' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'> ";
                    echo traduz("OS Liberada Pela Fábrica");
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if (in_array($login_fabrica, [3,11,20,45,172])) {

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo traduz(" OS Cancelada");
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
                if (!in_array($login_fabrica, [20])) {
                    echo "<tr height='18'>";
                        echo "<td width='18' bgcolor='#CCCCFF' class='legenda_os_cor'></td>";
                        echo "<td align='left' class='legenda_os_texto'>";
                        echo traduz("OS com Ressarcimento Financeiro");
                        echo "</td>";
                    echo "</tr>";
                    echo "<tr height='3'><td colspan='2'></td></tr>";
                }


            }

            if ($login_fabrica == 20) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#CACACA' class='legenda_os_cor'></td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>OS Reprovada pelo Promotor</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            //HD 211825: Novo status de OS de Troca criado: Autorização para Troca pela Revenda, somente Salton
            //HD 907550: Também Cobimex
            if ($fabrica_autoriza_troca_revenda) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#d89988' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'> ";
                    echo traduz("Autorização de Devolução de Venda");
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if ($login_fabrica == 1) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FFC0CB' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'> ";
                    echo traduz("OS Reincidente em devolução de peças");
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            //HD 163220 - Colocar legenda nas OSs com atendimento Procon/Jec (Jurídico) - tbl_hd_chamado.categoria='procon'
            if ($login_fabrica == 11 or $login_fabrica == 172) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#C29F6A' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'> ";
                    echo traduz("OS com Atendimento Procon/Jec (Jurídico) no Call-Center");
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if ($login_fabrica == 51) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#CACACA' class='legenda_os_cor'></td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>OS Recusada do extrato</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            echo "<tr height='3'>";
            if ($login_fabrica == 87){
                $cor = "#D2D2D2";
            }else{
                $cor = "#CC9900";
            }
            echo "<td width='55' bgcolor='$cor' class='legenda_os_cor'></td>";
            ?><td align='left' class='legenda_os_texto'><?=traduz("OS reincidente e aberta a mais de 25 dias")?></td>
            <?php
            echo "</tr>";
            echo "<tr height='3'><td colspan='2'></td></tr>";

            if ($login_fabrica != 87) {
                echo "<tr height='3'>";
                    echo "<td width='55' bgcolor='#FFCC66' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo traduz("OS com Troca de Produto");
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if ($login_fabrica == 158) {
                echo "<tr height='3'>";
                    echo "<td width='55' bgcolor='#FF0000' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo "SLA";
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if ($login_fabrica == 30) {
?>
                <tr height='18'>
                    <td width='18' bgcolor='#33CC00' class='legenda_os_cor'></td>
                    <td align='left' class='legenda_os_texto'><?=traduz('OS com limite a mais de 72 horas ')?></td>
                </tr>
                <tr height='3'><td colspan='2'></td></tr>

                <tr height='18'>
                    <td width='18' bgcolor='#FFFF66' class='legenda_os_cor'></td>
                    <td align='left' class='legenda_os_texto'><?=traduz('OS com limite a mais de 24 horas e menos de 72 horas')?></td>
                </tr>
                <tr height='3'><td colspan='2'></td></tr>

                <tr height='18'>
                    <td width='18' bgcolor='#FF0000' class='legenda_os_cor'></td>
                    <td align='left' class='legenda_os_texto'><?=traduz('OS com limite a menos de 24 horas')?></td>
                </tr>
                <tr height='3'><td colspan='2'></td></tr>
<?
            }

            if ($login_fabrica == 94) {

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='$cor' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'> ";
                    echo traduz("OS com Intervenção da Fábrica. Aguardando Liberação");
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($fabrica_autoriza_ressarcimento) {

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#CCCCFF' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo traduz("Os com Ressarcimento");
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica == 158) { /*HD - 6115865*/
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#F78F8F' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo traduz("OS em Auditoria");
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if ($login_fabrica == 131) { /*HD - 6840585*/
                $cor_os_reprovada_auditoria = "#FFB5C5";
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='{$cor_os_reprovada_auditoria}' class='legenda_os_cor'></td>";
                    echo "<td align='left' class='legenda_os_texto'>";
                    echo "OS reprovada na Auditoria";
                    echo "</td>";
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if ($login_fabrica == 40) {#HD 284058

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#BFCDDB' class='legenda_os_cor'>&nbsp;</td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>OS com 3 ou mais peças</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica == 94) {#HD 785254

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='silver' class='legenda_os_cor'>&nbsp;</td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>OS foi Aberta automaticamente por causa de uma troca gerada</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

            }

            if ($login_fabrica == 3) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#CB82FF' class='legenda_os_cor'>&nbsp;</td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>OS com pendência de fotos</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#A4A4A4' class='legenda_os_cor'>&nbsp;</td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>OS com intervenção de display</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if (in_array($login_fabrica, array(141,144))) {
            ?>
                <tr height="18" >
                    <td width="18" bgcolor="#CB82FF" class="legenda_os_cor" >&nbsp;</td>
                    <td align="left" class="legenda_os_texto" ><?=traduz('OS com troca de produto recusada')?></td>
                </tr>
                <tr height="3"><td colspan="2"></td></tr>
            <?php
            }

             if ($login_fabrica == 91) {
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#CB82FF' class='legenda_os_cor'>&nbsp;</td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>OS recusada pela fábrica</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }

            if ($login_fabrica == 45){
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#F98BB2' class='legenda_os_cor'>&nbsp;</td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>Os com Interação do Posto</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#56BB71' class='legenda_os_cor'>&nbsp;</td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>Os com Troca de Produtos - Resolvidos</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";

                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#EAEA1E' class='legenda_os_cor'>&nbsp;</td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>Os com Troca de Produtos - Pendentes</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }
            if($login_fabrica == 30){
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#F0F' class='legenda_os_cor'>&nbsp;</td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>Os Cancelada</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }
            if($login_fabrica == 74){
                echo "<tr height='18'>";
                    echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'>&nbsp;</td>";
                    echo traduz("<td align='left' class='legenda_os_texto'>Cancelada</td>");
                echo "</tr>";
                echo "<tr height='3'><td colspan='2'></td></tr>";
            }
        }

        if (in_array($login_fabrica, array(148,157))) { ?>
            <tr height="18" >
                <td width="18" bgcolor="#FF0000" class="legenda_os_cor" >&nbsp;</td>
                <td align="left" class="legenda_os_texto"><?php echo traduz("os.reprovada.da.auditoria");?></td>
            </tr>
            <tr height="3"><td colspan="2"></td></tr>
        <?
        }

        echo "<tr height='3'><td colspan='2'></td></tr>";
        echo "</table>";
        echo "</div>";
        ##### LEGENDAS - FIM #####
?>
                    </td>
                </tr>
             </table>

<?php

        echo "<br>";

        if (strlen($btn_acao_pre_os) > 0 and $login_fabrica == 52 and pg_num_rows($res) > 0){
            flush();
            echo `rm /tmp/assist/relatorio-pre-os-$login_fabrica.xls`;
            $fp = fopen ("/tmp/assist/relatorio-pre-os-$login_fabrica.html","w");

            fputs ($fp,"<table border='1' align='center' cellspacing='5px' cellpadding='2px' width='950'>
                            <tr>
                                <th colspan='8' style='color: #373B57; background-color: #F1C913;'>Relatório de Pré-OS</th>
                            </tr>
                            <tr>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Nº Atendimento</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Nº Ativo</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Série</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Versão Produto</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Data Abertura</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Posto</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Consumidor</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Telefone</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Produto</th>
                                <th style='color: #FFFFFF; background-color: #373B57;'>Marca</th>
                            </tr>");

            for ($i = 0; $i < pg_num_rows($res); $i++){
                $hd_chamado         = trim(pg_result($res, $i, "hd_chamado"));
                if (in_array($login_fabrica, [52])) {
                    $cliente_fricon     = pg_result($res, $i, "cliente_admin_nome");
                }
                $numero_ativo_res   = trim(pg_result($res, $i, "ordem_ativo"));
                $serie              = trim(pg_result($res, $i, "serie"));
                $type              = trim(pg_result($res, $i, "type"));
                $abertura           = trim(pg_result($res, $i, "data"));
                $posto_nome         = trim(pg_result($res, $i, "posto_nome"));
                $consumidor_nome    = trim(pg_result($res, $i, "nome"));
                $marca_logo         = trim(pg_result($res, $i, "marca"));
                if($login_fabrica == 85){
                    $array_campos_adicionais = pg_fetch_result($res,$i,array_campos_adicionais);
                    if(!empty($array_campos_adicionais)){
                        $campos_adicionais = json_decode($array_campos_adicionais);
                        if($campos_adicionais->consumidor_cpf_cnpj == 'R'){
                            $consumidor_nome = $campos_adicionais->nome_fantasia;
                        }
                    }
                }

                $consumidor_fone    = trim(pg_result($res, $i, "consumidor_fone"));
                $produto_referencia = trim(pg_result($res, $i, "referencia"));
                $produto_descricao  = trim(pg_result($res, $i, "descricao"));

                $cor   = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

                if(!empty($marca_logo)) {
                    $sql_logo="select nome from tbl_marca where marca = $marca_logo;";
                    $res_logo=pg_exec($con,$sql_logo);
                    $marca_logo_nome         = pg_fetch_result($res_logo, 0, 'nome');
                }
                fputs ($fp,"<tr style='text-align: left;'>
                                <td style='background-color: $cor;' nowrap>$hd_chamado</td>
                                <td style='background-color: $cor;' nowrap>$numero_ativo_res</td>
                                <td style='background-color: $cor;' nowrap>$serie</td>
                                <td style='background-color: $cor;' nowrap>$type</td>
                                <td style='background-color: $cor;' nowrap>$abertura</td>
                                <td style='background-color: $cor;' nowrap>$posto_nome</td>
                                <td style='background-color: $cor;' nowrap>$consumidor_nome</td>
                                <td style='background-color: $cor;' nowrap>$consumidor_fone</td>
                                <td style='background-color: $cor;' nowrap>$produto_referencia - $produto_descricao</td>
                                <td style='background-color: $cor;' nowrap>$marca_logo_nome</td>
                            </tr>");
            }

            $preos_total = pg_num_rows($res);
            fputs ($fp,"    <tr>
                                <th colspan='8' style='color: #373B57; background-color: #F1C913;'>Total de Pré-OS: $preos_total</th>
                            </tr>
                        </table>");
            fclose ($fp);

            $data = date("Y-m-d").".".date("H-i-s");

            if (strlen($login_cliente_admin) == 0){
                rename("/tmp/assist/relatorio-pre-os-$login_fabrica.html", "xls/relatorio-pre-os-$login_fabrica.$data.xls");

                echo "<br /> <a href='xls/relatorio-pre-os-$login_fabrica.$data.xls' target='_blank'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;".traduz("Gerar Arquivo Excel")."</a> <br />";
            }else{
                rename("/tmp/assist/relatorio-pre-os-$login_fabrica.html", "../admin/xls/relatorio-pre-os-$login_fabrica.$data.xls");

                echo "<br /> <a href='../admin/xls/relatorio-pre-os-$login_fabrica.$data.xls' target='_blank'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;".traduz("Gerar Arquivo Excel")."</a> <br />";
            }
        }

        if($login_fabrica == 50) {
            echo traduz("<button onclick='javascript: escondeColuna()' id='esconde'>Esconder Colunas</button><br/><br/>");
        }

        if(in_array($login_fabrica, array(152,180,181,182))) {
            $sem_listar_peca = $_POST['sem_listar_peca'];
        }
        //comentei porque estava deixado tr em branco e retirando a cor do status_os
        //$table_cor = ($login_fabrica == 74) ?' tablesorter ':'';
        for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {

            if($login_fabrica == 131){
                $referencia_peca = pg_fetch_result($res,$i, "referencia_peca");
                $descricao_peca = pg_fetch_result($res, $i, "descricao_peca");
                $peca_referencia_descricao = "$referencia_peca - $descricao_peca";

                /*$dt_digitacao_peca = mostra_data( substr(pg_fetch_result($res, $i, "data_digitacao_peca"), 0,10 ));
                $dt_geracao_pedido = mostra_data(substr(pg_fetch_result($res, $i, "dt_geracao"),0,10));
                $nf_peca            = pg_fetch_result($res, $i, "nf_peca");*/
            }

            if ($i % 50 == 0) {
                echo "</table>";
                flush();

                echo "<table border='0' cellpadding='2' cellspacing='1' class='$table_cor tabela scrollableTable'  align='center' width='80%' id='content' style='background-color: grey;'>";
            }

            if ($i % 50 == 0) {

                // O código é mais longo, mas é mais fácil de entender e manter
                switch ($login_fabrica) {
                    case 7:
                    case (in_array($login_fabrica, array(30, 96,183))):
                        $colspan = 5;
                        break;

                    case in_array($login_fabrica,array(1,14,20,24,66)):
                        $colspan = 6;
                        break;

                    case in_array($login_fabrica,array(50)):
                        $colspan = 7;
                        break;
                    case in_array($login_fabrica,array(101)):
                        $colspan = 8;
                        break;

                    case (in_array($login_fabrica, array(11,81,114,30, 122, 165 ,172))):
                        $colspan = 4;
                        break;

                    default:
                        $colspan = 3;
                        break;
                }

                $titulo = (strlen($btn_acao_pre_os) > 0) ? "pre-os" : "os";

                echo "<thead>
                        <tr class='titulo_coluna'>";

                /* Titulo da Pré-OS */
                if($titulo == "pre-os"){

                    if ($login_fabrica == 3) {
                        echo "<th>CÓD POSTO</th>";
                    }

                    if (strlen($btn_acao_pre_os) == 0) {
                        echo "<th>OS</th>";
                        if($login_fabrica == 74) {
                            echo traduz("<th>Status de Atendimento OS</th>");
                            echo traduz("<th>Dt. Contato&nbsp;&nbsp;&nbsp;</th>");
                        }
                    } else {

                        echo "<th>Nº Atendimento</th>";
                        if(in_array($login_fabrica, array(35))){
                            echo "<th>Código Rastreio</th>";
                        }

                        if($login_fabrica == 74) {
                            echo traduz("<th>Status de Atendimento OS</th>");
                            echo traduz("<th>Dt. Contato&nbsp;&nbsp;&nbsp;</th>");
                        }
                    }

                    if ((in_array($login_fabrica, array(30, 52))) && strlen($btn_acao_pre_os) == 0) {
                        echo "<th>Nº Atendimento</th>";
                    }

                    if($login_fabrica == 52){
                        echo "<th>Cliente Fricon</th>";
                        echo "<th>Número Ativo</th>";
                    }

                    echo ($login_fabrica==19 OR $login_fabrica==10 OR $login_fabrica==1) ? "<th>OS OFF LINE</th>" : "";


                    if(!in_array($login_fabrica,array(1,3,20,50,81,145))){
                        echo "<th>";
                        echo ($login_fabrica==35) ? "PO#" : "SÉRIE ";
                        echo "</th>";
                    }

                    echo "<th>AB</th>";

                    echo ($login_fabrica==11 or $login_fabrica == 172) ? "<th>DP</th>" : ""; // HD 74587

                    if ($mostra_data_conserto){
                        echo "<th><acronym title='Data de conserto do produto' style='cursor:help;'>DC</a></th>"; //HD 14927
                    }

                    if(!in_array($login_fabrica, array(3,139)))
                        echo "<th>DF</th>";
                    //echo "<th>FC</th>";

                    echo (in_array($login_fabrica, array(115,116,117,120,141,144))) ? "<th align='center'>Tipo de Atendimento</th>" : "";

                    if ($login_fabrica == 52){
                        echo "<th> Aberto acima de </th>";
                    }

                    if(!in_array($login_fabrica, array(3,24,85, 11,96, 172)) && $login_fabrica != ($login_fabrica > 100)){
                        echo "<th>POSTO</th>";
                    }

                    if(in_array($login_fabrica,array(106,114,122,123,127))){
                        echo "<th>POSTO</th>";
                    }

                    echo "<th nowrap>".strtoupper(traduz("NOME POSTO"))."</th>";

                    if ($login_fabrica == 11 or $login_fabrica == 172) {
                        echo "<th nowrap>SITUAÇÃO POSTO</th>";
                    }

                    $esconde_coluna = ($login_fabrica == 50) ? "" : "esconde_coluna";

                    echo ($login_fabrica==2)  ? "<th>CONSUMIDOR/REVENDA</th>" : "<th rel='$esconde_coluna'>CONSUMIDOR/REVENDA</th>";

                    if(in_array($login_fabrica, array(3,6,11,50,1,117,90,15,19,42,72,40,45,88,80,24,91,74,81,114,85,52,94,96,35,98,101,127,86,106,123,122,20,124,172))){
                        echo "<th >TELEFONE</th>";
                    }

                    if(!in_array($login_fabrica, array(3,6,11,50,1,117,90,15,19,42,72,40,45,88,80,24,91,74,81,114,85,52,94,96,35,98,101,127,86,106,123,122,20,124,142,143,172))){
                        echo "<th>NF</th>";
                    }
                    echo ($login_fabrica==3 OR $login_fabrica == 86 OR $login_fabrica == 52 or $multimarca =='t')  ? "<th>MARCA</th>" : "";
                    echo ($login_fabrica==80 )  ? "<th>DATA DE COMPRA</th>" : "";

                    echo ($login_fabrica==11 or $login_fabrica == 172) ? "<th>REFERÊNCIA</th>" : "<th rel='$esconde_coluna'>PRODUTO</th>"; // hd 74587

                    echo ($login_fabrica == 145) ? "<th>DIAS SEM OS</th>" : "";

                    if ($login_fabrica == 30 || $login_fabrica == 50){
                        echo "<th nowrap rel='$esconde_coluna'>DEFEITO RECLAMADO</th>";
                        echo "<th nowrap rel='$esconde_coluna'>END. CONSUMIDOR</th>";
                        echo "<th nowrap rel='$esconde_coluna'>CIDADE CONSUMIDOR</th>";
                        echo "<th nowrap rel='$esconde_coluna'>UF CONSUMIDOR</th>";
                        echo "<th nowrap rel='$esconde_coluna'>DEFEITO CONSTATADO</th>";
                    }

                    if(in_array($login_fabrica, array(30))){
                        echo "<th nowrap rel='esconde_coluna'>ADMIN</th>";
                        echo "<th nowrap rel='esconde_coluna'>Valor OS</th>";
                    }

                    echo ($login_fabrica==45 or $login_fabrica == 11 or $login_fabrica == 172) ? "<th align='center'>RG PRODUTO</th>" : "";

                    echo ($login_fabrica==19) ? "<th>Atendimento</th>" : "";

                    echo ($login_fabrica==19 || $login_fabrica == 94) ? "<th>Nome do técnico</th>" : "";

                    echo ($login_fabrica==1)  ? "<th>APARÊNCIA</th>" : "";//TAKASHI HD925

                    echo ($login_fabrica==115 or $login_fabrica == 116 OR $login_fabrica == 117 OR $login_fabrica == 120) ? "<th align='center'>KM</th>" : "";

                    if(($login_fabrica == 11 or $login_fabrica == 172) AND $login_admin_intervensor){
                        $colspan += 1;
                    }

                    if(in_array($login_fabrica,array(85)) && empty($hd_chamado)){
                        $colspan = 4;
                    }

                    if($telecontrol_distrib){
                        $colspan = 5;
                    }

                    if($login_fabrica == 104){ //HD-3139131
                        echo "<th>Código Postagem</th>";
                    }


                    if($login_fabrica == 35){
                        echo "<th colspan=5>AÇÕES</th>";
                    } else {
                        echo "<th colspan='$colspan'>AÇÕES</th>";
                    }

                    echo ($login_fabrica==7)  ? "<th colspan='$colspan'> <a href='javascript:selecionarTudo();' style='color:#FFFFFF'><img src='imagens/img_impressora.gif'></a></th>" : "";

                } else {
                    /* Titulo da OS */

                    if ($login_fabrica == 3) {
                        echo "<th>CÓD POSTO</th>";
                    }

                    if (strlen($btn_acao_pre_os)==0) {

                        if($telecontrol_distrib OR in_array($login_fabrica, [146,156,131])){
                            echo "<th><input type='checkbox' id='seleciona_todas_os_excluida' form='form_exclui_os' title='selecionar todas' /></th>";
                        }

                        echo "<th>OS</th>";
                        if(in_array($login_fabrica, array(152,180,181,182))) {
                            ?>
                            <th><?=traduz("TIPO")?></th>
                            <?php
                        }

                        if($login_fabrica == 138){
                            echo "<th>PEDIDO</th>";
                        }

                        if($login_fabrica == 74) {
                            echo traduz("<th>Status de Atendimento OS</th>");
                            echo traduz("<th>Dt. Contato&nbsp;&nbsp;&nbsp;</th>");
                        }

                    } else {

                        ?>
                        <th><?=traduz("Nº Atendimento")?></th>
                        <?php

                        if($login_fabrica == 74) {
                            echo traduz("<th>Status de Atendimento OS</th>");
                            echo traduz("<th>Dt. Contato&nbsp;&nbsp;&nbsp;</th>");
                        }
                    }

                    if (in_array($login_fabrica, array(30, 35, 50, 52, 104)) && strlen($btn_acao_pre_os) == 0) {
                        echo "<th>Nº Atendimento</th>";
                    }


                    if($login_fabrica == 52){
                        echo "<th>Cliente Fricon</th>";
                        echo "<th>Número Ativo</th>";
                    }

                    echo ($login_fabrica==19 OR $login_fabrica==10 OR $login_fabrica==1) ? "<th>OS OFF LINE</th>" : "";

                    if($login_fabrica == 19){ //hd_chamado=2881143
                        echo "<th>Extrato</th>";
                    }

                    if ($login_fabrica == 158) { /*HD - 6115865*/
                        echo "<th>Auditorias</th>";
                    }

                    if(!in_array($login_fabrica,array(1,3,20,50,81,137,127,138,145))){ // HD-2296739
                        echo "<th>";

                        if($login_fabrica == 160 or $replica_einhell){
                            echo "Nº  LOTE";
                        }elseif($login_fabrica == 35){
                            echo "PO#";
                        }else{
                            echo "SÉRIE";
                        }
                        echo "</th>";
                    }

                    if ($login_fabrica == 104) {
                        echo "<th nowrap>DIAS EM ABERTO</th>";
                    }
                    if($login_fabrica == 160 or $replica_einhell){
                        echo "<th>VERSÃO PRODUTO</th>";
                    }


                    if (in_array($login_fabrica, array(137))) {
                        echo "<th>N. LOTE</th>";
                    }

                    if ($login_fabrica == 158) {
                        echo "<th>DCR</th>";
                    }

                    echo "<th>AB</th>";

                    if (in_array($login_fabrica, array(169,170))) {
                        echo "<th>Data de agendamento Callcenter</th>";
                        echo "<th>Data Confirmação Posto</th>";
                        echo "<th>Data de Reagendamento Posto</th>";
                        echo "<th>Inspetor</th>";
                    }

                    if ($login_fabrica == 178){
                        echo "<th>Inspetor</th>";
                    }

                    if($login_fabrica == 138){ //2439865
                        echo '<th>DG</th>';
                    }

                    echo ($login_fabrica==11 or $login_fabrica == 172) ? "<th>DP</th>" : ""; // HD 74587


                    if ($mostra_data_conserto) {
                        echo "<th><acronym title='Data de conserto do produto' style='cursor:help;'>DC</a></th>"; //HD 14927
                    }
                    if(!in_array($login_fabrica, array(3))) echo "<th>FC </th>";

                    if ($login_fabrica == 158) {
                        echo "<th>UM</th>";
                    }

                    if(in_array($login_fabrica, array(156))){ echo "<th>STATUS ELGIN</th>"; }

                    echo ($login_fabrica==115 or $login_fabrica == 116 OR $login_fabrica == 117 OR $login_fabrica == 120) ? "<th align='center'>Tipo de Atendimento</th>" : "";


                    if(in_array($login_fabrica, array(87,94,141,144, 153,156,161,163,167,171,174,175,176,177,203))){
                        echo "<td>TIPO DE ATENDIMENTO</td>";

                        if(in_array($login_fabrica, array(94,141,144,153,161,163,167,175,176,177,203))){
                            echo "<td>C / R</td>";
                        }
                    }elseif(!in_array($login_fabrica, [30,138])){ //2439865
                        echo "<th>C / R</th>";
                    }

                    if ($login_fabrica == 164) {
                        echo "<th>Nome Fantasia/Transferência</th>";
                        echo "<th>UF Revenda</th>";
                    }

                    if ($login_fabrica == 52){
                        echo "<th> Aberto acima de </th>";
                    }

                    echo ($login_fabrica==72) ? "<th>Data NF</th>" : "";

                    if (in_array($login_fabrica, array(74))) {
                        echo "<th>POSTO</th>";
                    }

                    if ($login_fabrica == 158) {
                        echo "<th>UNIDADE DE NEGÓCIO</th>";
                        if (empty($login_cliente_admin)) {
                            echo "<th>PROTOCOLO CLIENTE</th>";
                        }
                    }

                    if(in_array($login_fabrica, array(157, 158)) AND empty($login_cliente_admin)) {
                        echo "<th>".(($login_fabrica == 158) ? "OS CLIENTE" : "OS INTERNA")."</th>";
                    }

                    if(in_array($login_fabrica, array(162,165))) {
                        echo "<th nowrap>NOME TÉCNICO</th>";
                    }

                    echo "<th nowrap>".strtoupper(traduz("NOME POSTO"))."</th>";

                    if(in_array($login_fabrica, array(156))){ echo "<th>POSTO INTERNO</th>"; }

                    if ($login_fabrica == 11 or $login_fabrica == 172) {
                        echo "<th nowrap>SITUAÇÃO POSTO</th>";
                    }
					if (!in_array($login_fabrica, array(104)) && strlen($btn_acao_pre_os) == 0){ //HD-3139131
						echo "<th>CIDADE</th>";
						echo "<th>ESTADO</th>";
					}


                    if ($login_fabrica == 164) {
                        echo "<th nowrap>CPF/CNPJ</th>";
                    }

                    if ($login_fabrica == 52) { /*HD - 4304128*/
                        echo "<th>PAÍS</th>";
                    }

					if ($login_fabrica == 2) {
						echo "<th>CONSUMIDOR/REVENDA</th>";
					} elseif (in_array($login_fabrica, array(169,170))) {
                     	echo "
	                     	<th rel='$esconde_coluna'>REVENDA</th>
	                     	<th rel='$esconde_coluna'>CNPJ REVENDA</th>
	                     	<th rel='$esconde_coluna'>CONSUMIDOR</th>
                            <th>BAIRRO</th>
                            <th>CIDADE</th>
                        ";
					} elseif($login_fabrica <> 144) {
                     	echo "<th rel='$esconde_coluna'>".strtoupper(traduz("CONSUMIDOR"))."/".strtoupper(traduz("REVENDA"))."</th>";
                    }


                    if(!empty($sua_os) && strstr($sua_os, "-") and !isset($novaTelaOs)){
                        $coluna_revenda = true;
                        echo "<th>".traduz("REVENDA")."</th>";
                    }

                    if($login_fabrica == 141){ //HD - 2386867
                        echo "<th>UF CONSUMIDOR</th>";
                        echo "<th>UF POSTO</th>";
                    }

                    if(in_array($login_fabrica, array(1,3,6,11,15,19,20,24,30,35,40,42,45,50,52,72,74,80,81,85,86,88,90,91,94,96,98,101,106,114,117,122,123,124,127,172))) {
                        echo "<th rel='$esconde_coluna'>TELEFONE</th>";
                    }
                    echo ($login_fabrica==80 )  ? "<th>DATA DE COMPRA</th>" : "";
                    if(!in_array($login_fabrica, array(1,3,6,11,15,19,20,24,30,35,40,42,45,50,52,72,74,80,81,85,86,88,90,91,94,96,98,101,106,114,117,122,123,124,127,172))) {
                        if($login_fabrica == 138){ //2439865
                            echo "<th>NF VENDA PRODUTO</th>";
                            echo "<th>DATA NF</th>";
                        }else{
                            echo "<th>NF</th>";
                        }

                    }

                    if(in_array($login_fabrica, array(156))){ echo "<th>DATA NF</th> <th>NF REMESSA</th>";  }

                    if($login_fabrica == 138){
                        echo "<th>PRODUTO</th>";
                        echo "<th>SÉRIE</th>";
                        echo "<th>NOTA FISCAL PEÇAS</th>";
                        echo "<th>DATA NF</th>";
                    }

                    echo ($login_fabrica==3 OR $login_fabrica == 86 OR $login_fabrica == 52 or $multimarca=='t')  ? "<th>MARCA</th>" : "";

                    if ($login_fabrica == 158) {
                        echo "<th>FAMÍLIA</th>";
                    }
                    echo ($login_fabrica == 171) ? "<th>REFERÊNCIA GROHE</th> <th>REFERÊNCIA FN</th>" : "";

                    if(!in_array($login_fabrica,[138,171])){
                        echo ($login_fabrica==11 or $login_fabrica == 172) ? "<th>REFERÊNCIA</th>" : "<th rel='$esconde_coluna'>PRODUTO</th>"; // hd 74587
                    }

                    if($login_fabrica == 94){
                        echo "<th rel='$esconde_coluna'>DEFEITO RECLAMADO</th>";
                        echo "<th rel='$esconde_coluna'>DEFEITO CONSTATADO</th>";
                    }

                    if ($login_fabrica == 3) {
                        echo "<th rel='$esconde_coluna'>TIPO DE ATENDIMENTO</th>";
                    }

                    if($login_fabrica == 165){
                         echo "<th nowrap>PRODUTO TROCADO</th>";
                         echo "<th nowrap>DEFEITO CONSTATADO</th>";
                    }

                    if(in_array($login_fabrica, array(152,180,181,182))) {
                        if ($sem_listar_peca <> 1){
                        ?>
                        <th><?=strtoupper(traduz("Descrição Peça"))?></th>
                        <th><?=strtoupper(traduz("Qtde"))?></th>
                        <th><?=strtoupper(traduz("Código"))?></th>
                        <th><?=strtoupper(traduz("Classificação"))?></th>
                        <?php
                        }
                        ?>
                        <th><?=strtoupper(traduz("DESCRIÇÃO DETALHADA DO PROBLEMA"))?></th>
                        <?php
                    }

                    if($login_fabrica == 131){ ## HD -2181938
                        //echo "<th>DATA DO PEDIDO</th>";
                        echo "<th>DATA DA REPROVA</th>";
                        //echo "<th>AGUARDANDO CONSERTO</th>";
                        //echo "<th>PEÇA</th>";
                        echo "<th>DIGITAÇÃO PEÇA</th>";
                        echo "<th>GERAÇÃO PEDIDO</th>";
                        echo "<th>DATA NF</th>";
                        echo "<th>PREVISÃO ENTREGA</th>";
                    }

                    echo ($login_fabrica == 85) ? "<th nowrap>DIAS EM ABERTO</th>" : "";

                    if ($login_fabrica == 145 && strlen($btn_acao_pre_os) > 0) {
                        echo "<th nowrap>DIAS EM ABERTO</th>";
                    }

                    if ($login_fabrica == 30 || $login_fabrica == 50){

                        if (!in_array($login_fabrica, [30])) {
                            echo "<th nowrap rel='esconde_coluna'>DEFEITO RECLAMADO</th>";
                        }

                        echo "<th nowrap rel='esconde_coluna'>END. CONSUMIDOR</th>";
                        echo "<th nowrap rel='esconde_coluna'>CIDADE CONSUMIDOR</th>";
                        echo "<th nowrap rel='esconde_coluna'>UF CONSUMIDOR</th>";
                    }

                    if(in_array($login_fabrica, array(137,50,164))){
                        echo "<th nowrap rel='esconde_coluna'>DEFEITO CONSTATADO</th>";
                    }
                    if($login_fabrica == 164){
                        echo "<th nowrap rel='esconde_coluna'>DESTINAÇÃO</th>";
                    }

                    if($login_fabrica == 50){
                        echo "<th nowrap rel='esconde_coluna'>REVENDA (CLIENTE COLORMAQ)</th>";
                        echo "<th nowrap rel='esconde_coluna'>CNPJ</th>";
                        echo "<th nowrap rel='esconde_coluna'>FONE</th>";
                        echo "<th nowrap rel='esconde_coluna'>DATA NF</th>";
                        echo "<th nowrap rel='esconde_coluna'>REVENDA (CONSUMIDOR)</th>";
                        echo "<th nowrap rel='esconde_coluna'>CNPJ</th>";
                        echo "<th nowrap rel='esconde_coluna'>FONE</th>";
                        echo "<th nowrap rel='esconde_coluna'>DATA NF</th>";
                    }

                    echo ($login_fabrica==45 or $login_fabrica == 11 or $login_fabrica == 172) ? "<th align='center'>RG PRODUTO</th>" : "";

                    echo ($login_fabrica==19) ? "<th>Atendimento</th>" : "";

                    echo ($login_fabrica==19 || $login_fabrica == 94) ? "<th>Nome do técnico</th>" : "";

                    echo ($login_fabrica==1)  ? "<th>APARÊNCIA</th>" : "";//TAKASHI HD925

                    if($login_fabrica == 137){
                        echo "<th>CFOP</th>";
                        echo "<th>Valor Unitário</th>";
                        echo "<th style='min-width: 80px !important;'>Valor Total Nota</th>";
                    }

                    if(in_array($login_fabrica, array(143))){
                        echo "<th>Horimetro</th>";
                    }

                    echo (in_array($login_fabrica, [115,116,117,120])) ? "<th align='center'>KM</th>" : "";

                    if($telecontrol_distrib || in_array($login_fabrica, [11,30,156,161,163,167,172,203])) {
                        $colspan = 5;
                    }

                    if((in_array($login_fabrica, [11,172])) AND $login_admin_intervensor){
                        $colspan += 1;
                    }

                    if((in_array($login_fabrica,array(85)) && empty($hd_chamado)) || in_array($login_fabrica, array(52,158,104))){
                        $colspan = 4;
                    }

                    if (strlen($btn_acao_pre_os)==0) {
                        if( in_array($login_fabrica,array(45,74,91,128))){
                            $colspan += 1; /* HD 940122 - Deixado com colspan de 4 */
                        }
                        if( in_array($login_fabrica,array(148))){
                            $colspan += 2;
                        }
                        if($login_fabrica == 72){
                            $colspan = 4;
                        }

                        if($login_fabrica == 74){
                            $colspan = 6;
                        }

                        if ($login_fabrica == 174)
                        {
                            $colspan = 5;
                        }

                        if ($cancelaOS) {
                            $colspan += 1;
                        }

                        if ($login_fabrica == 158 && empty($login_cliente_admin)) {
                            $colspan = 5;
                        } elseif (in_array($login_fabrica, [158]) && !empty($login_cliente_admin)) {
                            $colspan = 1;
                        }

                        if (in_array($login_fabrica, [35, 104])) {
                            $colspan = 5;
                        ?>
                            <style>
                            .finalizar_os_35 {
                                cursor: pointer;
                                color: #63798D !important;
                            }

                            .finalizar_os_35:hover {
                                color: #111 !important;
                            }
                            </style>

                            <script>
                                $( document ).ready(function() {
                                    $("#dlg_fechar").click( function(){
                                        $("#dlg_motivo").css("display", "none");
                                    });

                                    $("#dlg_btn_nao").click( function(){
                                        $("#dlg_motivo").css("display", "none");
                                    });

                                    $("#dlg_btn_sim").click( function(){
                                        var os = $("#dlg_aux_os").val();
                                        dlgEnviarSMS(os);
                                    });

                                });

                                function novoFinalizarOsCadence(os, linha, fabrica) {
                                    if (confirm('Deseja realmente finalizar a O.S. ' + os + ' ?') == false) return false;

                                    $.ajax({
                                        url : "<?php echo $_SERVER['PHP_SELF']; ?>",
                                        type: "POST",
                                        data: {
                                            novo_fechar_os_cadence: 'true',
                                            os : os
                                        },
                                        complete: function(data){
                                            var response = data.responseText;
                                            console.log(response);
                                            $("#dlg_pergunta").css("display", "block");
                                            $("#sms_success").css("display", "none");

                                            response = response.split("|");

                                            if (response[0] == "OK"){
                                                $("#dlg_aux_os").val(os);
                                                $("#dlg_motivo").css("display", "block");
                                            }else if(response[0] == "SMS_NAO"){
                                                $("#dlg_aux_os").val(os);
                                                $("#dlg_motivo").css("display", "none");
                                            } else {
                                                alert(response[1]);
                                            }
                                        }
                                    });
                                }

                                function dlgEnviarSMS(os) {
                                    if (os == '') {
                                        alert("Erro ao identifcar a O.S.");
                                    } else {
                                        $.ajax({
                                            url : "<?php echo $_SERVER['PHP_SELF']; ?>",
                                            type: "POST",
                                            data: {
                                                enviar_sms_os: 'true',
                                                os : os
                                            },
                                            complete: function(data){
                                                var response = data.responseText;
                                                response = response.split("|");

                                                if (response[0] == "KO") {
                                                    $("#sms_erro").css("display", "block");
                                                    $("#lbl_error").html(response[1]);
                                                } else {
                                                    $("#exclusao").text("");
                                                    $("#sms_success").css("display", "block");
                                                    $("#lbl_success").html(response[1]);
                                                }

                                                $("#dlg_pergunta").css("display", "none");
                                            }
                                        });
                                    }
                                }
                            </script>

                            <div id='dlg_motivo'>
                                <div id='motivo_header'>Enviar SMS</div>
                                <div id='dlg_fechar'>X</div>
                                <div id='motivo_container'>
                                    <center>
                                        <p id="exclusao" style='font-size:12px;font-weight:bold;color:green;'>
                                            OS finalizada com sucesso!
                                        </p>
                                        <p id="sms_erro" style='display:none;font-size:12px;font-weight:bold;color:red;'>
                                            <label id="lbl_error"></label>
                                        </p>
                                        <p id="sms_success" style='display:none;font-size:12px;font-weight:bold;color:green;'>
                                            <label id="lbl_success"></label>
                                        </p>
                                        <div id="dlg_pergunta">
                                            <p style='font-size:12px;font-weight:bold;color:black;'>
                                                Enviar SMS ao consumidor?
                                            </p>
                                            <br>
                                            <input type="hidden" id="dlg_aux_os">
                                            <button type="button" id="dlg_btn_sim" style="cursor: pointer;font-weight:bold;">Sim</button>
                                            <button type="button" id="dlg_btn_nao" style="cursor: pointer;font-weight:bold;">Não</button>
                                        </div>
                                    </center>
                                </div>
                            </div>
                        <? }

                        if (in_array($login_fabrica, [169,170])) {
                            echo "<td>Data Chegada</td>";
                        }

                        if (in_array($login_fabrica, [173])) {
                            $colspan = 4;
                        } else if (in_array($login_fabrica, [3,72,144,152,169,170,178,180,181,182])) {
                            $colspan = 5;
                        } else if (in_array($login_fabrica, [30,35,91,158])) {
                            $colspan = 6;
                        } else if (in_array($login_fabrica, [52,101])) {
                            $colspan = 10;
                        }

                        if ($login_fabrica == 148) {
                            $colspan += 1;
                        }

                        echo "<th colspan='$colspan'>".strtoupper(traduz("AÇÕES"))."</th>";

                        echo ($login_fabrica==7)  ? "<th colspan='$colspan'> <a href='javascript:selecionarTudo();' style='color:#FFFFFF'><img src='imagens/img_impressora.gif'></a></th>" : "";

                    }
                }

                    echo "</tr></thead><tbody>";
            }

                if (strlen($btn_acao_pre_os) > 0) {
                    $hd_chamado         = trim(pg_fetch_result($res,$i,hd_chamado));
                    $codigo_posto       = trim(pg_fetch_result($res,$i,codigo_posto));
                    if($login_fabrica == 52){
                        $cliente_fricon         = pg_fetch_result($res, $i,cliente_admin_nome);
                        $numero_ativo_res       = trim(pg_fetch_result($res,$i,ordem_ativo));
                    }
                    if($login_fabrica == 104){ //HD-3139131
                        $cod_postagem = pg_fetch_result($res, $i, 'cod_postagem');
                    }
                    $sua_os             = trim(pg_fetch_result($res,$i,sua_os));
                    $serie              = trim(pg_fetch_result($res,$i,serie));
                    $nota_fiscal        = trim(pg_fetch_result($res,$i,nota_fiscal));
                    $abertura           = trim(pg_fetch_result($res,$i,data));
                    if($login_fabrica==30 or $login_fabrica==52) $dt_hr_abertura     = trim(pg_fetch_result($res,$i,dt_hr_abertura));
                    $consumidor_nome    = trim(pg_fetch_result($res,$i,nome));
                    $consumidor_fone    = trim(pg_fetch_result($res,$i,consumidor_fone));
                    if($login_fabrica == 85){
                        $array_campos_adicionais = pg_fetch_result($res,$i,array_campos_adicionais);
                        if(!empty($array_campos_adicionais)){
                            $campos_adicionais = json_decode($array_campos_adicionais);
                            if($campos_adicionais->consumidor_cpf_cnpj == 'R'){
                                $consumidor_nome = $campos_adicionais->nome_fantasia;
                            }
                        }
                    }
                    $posto_nome         = trim(pg_fetch_result($res,$i,posto_nome));
                    $situacao_posto     = trim(pg_fetch_result($res,$i,credenciamento));
                    $marca_nome         = trim(pg_fetch_result($res,$i,marca_nome));
                    $produto_referencia = trim(pg_fetch_result($res,$i,referencia));
                    $data_nf            = trim(pg_fetch_result($res, $i, data_nf));
                    $produto_descricao  = trim(pg_fetch_result($res,$i,descricao));
                    if($login_fabrica == 96){
                        $tipo_atendimento = trim(pg_fetch_result($res,$i,tipo_atendimento));
                    }
                } else {
                    $cidade_posto       = trim(pg_fetch_result($res,$i,contato_cidade));
                    $estado_posto       = trim(pg_fetch_result($res,$i,contato_estado));
                    $cidade_uf          = $cidade_posto."/".$estado_posto;


                    $os                 = trim(pg_fetch_result($res,$i,os));

                    if ($login_fabrica == 164) {
                        $sql_revenda = "SELECT tbl_estado.estado, tbl_os_campo_extra.campos_adicionais AS revenda_fantasia
                                        FROM tbl_cidade
                                        JOIN tbl_revenda ON tbl_revenda.cidade = tbl_cidade.cidade
                                        JOIN tbl_estado ON tbl_cidade.estado = tbl_estado.estado
                                        JOIN tbl_os ON tbl_os.revenda = tbl_revenda.revenda AND tbl_os.os = {$os}
                                        LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os
                                        WHERE tbl_os.os = {$os} AND tbl_os.fabrica = $login_fabrica";
                        $res_revenda = pg_query($con, $sql_revenda);

                        if(pg_num_rows($res_revenda) > 0){
                            $campos_adicionais_fantasia = pg_fetch_result($res_revenda, 0, 'revenda_fantasia');
                            if (!empty($campos_adicionais_fantasia)) {
                                $nome_fantasia = json_decode($campos_adicionais_fantasia, true);
                                $nome_fantasia = $nome_fantasia['revenda_fantasia'];
                            }
                            $uf_revenda = pg_fetch_result($res_revenda, 0, 'estado');
                        }
                    }

                    $sua_os             = trim(pg_fetch_result($res,$i,sua_os));
                    $hd_chamado         = trim(pg_fetch_result($res,$i,hd_chamado));

                    if($login_fabrica == 52){
                        $cliente_fricon         = pg_fetch_result($res, $i,cliente_admin_nome);
                        $numero_ativo_res       = trim(pg_fetch_result($res,$i,ordem_ativo));
                    }

                    $nota_fiscal        = trim(pg_fetch_result($res,$i,nota_fiscal));
                    $os_numero          = trim(pg_fetch_result($res,$i,os_numero));

        			if (isset($novaTelaOs)) {
        				$os_reparo = $os_numero;
        				$tipo_posto_interno = pg_fetch_result($res, $i, posto_interno);
        			}

                    $digitacao          = trim(pg_fetch_result($res,$i,digitacao));
                    $abertura           = trim(pg_fetch_result($res,$i,abertura));
                    $fechamento         = trim(pg_fetch_result($res,$i,fechamento));
                    $finalizada         = trim(pg_fetch_result($res,$i,finalizada));
                    $serie              = trim(pg_fetch_result($res,$i,serie));
                    $type               = trim(pg_fetch_result($res,$i,type));
                    $excluida           = trim(pg_fetch_result($res,$i,excluida));
                    $motivo_atraso      = trim(pg_fetch_result($res,$i,motivo_atraso));
                    $tipo_os_cortesia   = trim(pg_fetch_result($res,$i,tipo_os_cortesia));
                    $consumidor_revenda = trim(pg_fetch_result($res,$i,consumidor_revenda));
                    $consumidor_nome    = trim(pg_fetch_result($res,$i,consumidor_nome));
                    $consumidor_fone    = trim(pg_fetch_result($res,$i,consumidor_fone));
					$consumidor_estado  = trim(pg_fetch_result($res,$i,consumidor_estado));
                    $revenda_nome       = trim(pg_fetch_result($res,$i,revenda_nome));
                    $maoDeObra          = trim(pg_fetch_result($res,$i,mao_de_obra));
                    if ($login_fabrica == 161) {
                        $serie = strtoupper($serie);
                    }

                    if (in_array($login_fabrica, array(169,170))) {
                        $consumidor_bairro    = trim(pg_fetch_result($res,$i,cons_bairro));
                        $consumidor_cidade    = trim(pg_fetch_result($res,$i,cons_cidade));
                    }

                    $nome_consumidor_revenda = ($consumidor_revenda == "C" || empty($consumidor_revenda)) ? $consumidor_nome : $revenda_nome;
                    
                    if (in_array($login_fabrica, [169,170])) {
                    	$nome_consumidor = $consumidor_nome;
                    	$nome_revenda    = $revenda_nome;
                    }

                    if ($login_fabrica == 156 and empty($nome_consumidor_revenda)) {
                        $nome_consumidor_revenda = $consumidor_nome;
                    }

                    if($login_fabrica == 85){
                        $array_campos_adicionais = pg_fetch_result($res,$i,array_campos_adicionais);
                        if(!empty($array_campos_adicionais)){
                            $campos_adicionais = json_decode($array_campos_adicionais);
                            if($campos_adicionais->consumidor_cpf_cnpj == 'R'){
                                $consumidor_nome = $campos_adicionais->nome_fantasia;
                            }
                        }
                    }
                    if($login_fabrica == 137){
                        $consumidor_cidade      = trim(pg_fetch_result($res,$i,consumidor_cidade));
                    }
                    if($login_fabrica == 30 || $login_fabrica == 50 ){

                        $consumidor_endereco  = trim(pg_fetch_result($res,$i,consumidor_endereco));
                        $consumidor_numero    = trim(pg_fetch_result($res,$i,consumidor_numero));
                        $consumidor_cep       = trim(pg_fetch_result($res,$i,consumidor_cep));
                        $consumidor_bairro    = trim(pg_fetch_result($res,$i,consumidor_bairro));
                        $consumidor_cidade    = trim(pg_fetch_result($res,$i,consumidor_cidade));
                        $defeito_constatado   = trim(pg_fetch_result($res,$i,defeito_constatado));
                        $defeito_reclamado_os = (in_array($login_fabrica, array(50))) ? trim(pg_fetch_result($res, $i, "defeito_reclamado_descricao_os")) : trim(pg_fetch_result($resxls, $i, "defeito_reclamado_os"));
                        $cliente_admin_nome   = trim(pg_fetch_result($res,$i,cliente_admin_nome));
                        $data_limite          = trim(pg_fetch_result($res,$i,termino_atendimento));

                        $cliente_admin_nome = (empty($cliente_admin_nome)) ? "Normal" : $cliente_admin_nome;

                        $array_campos_adicionais = pg_fetch_result($res,$i,array_campos_adicionais);

                        if(!empty($array_campos_adicionais)){
                            $campos_adicionais = json_decode($array_campos_adicionais);
//                             $data_limite = $campos_adicionais->data_limite;

//                             if(strlen($data_limite) > 0){
//                                 list($d,$m,$y) = explode("/", $data_limite);
//                                 $data_limite = "$y-$m-$d";
//                             }

                        }else{
                            $data_limite = "";
                        }
                    }

                    if ($login_fabrica == 94) {
                        $defeito_reclamado_os = trim(pg_fetch_result($res, $i, "defeito_reclamado_os"));
                        $defeito_constatado_desc = trim(pg_fetch_result($res, $i, "defeito_constatado_desc"));
                    }

                    if(in_array($login_fabrica, array(30))){
			            $admin = pg_fetch_result($res, $i, "admin");
			            $os_revendedor = pg_fetch_result($res,$i,"numero_processo");

                        if(strlen($admin) > 0){
                            $sql_admin = "SELECT nome_completo FROM tbl_admin WHERE admin = {$admin} AND fabrica = {$login_fabrica}";
                            $res_admin = pg_query($con, $sql_admin);
                            if(pg_num_rows($res_admin) > 0){
                                $admin = pg_fetch_result($res_admin, 0, "nome_completo");
                            }
                        }

                        $valor_os = number_format(pg_fetch_result($res, $i, "valor_os"), 2, ",", ".");
                    }

                    if(in_array($login_fabrica, array(50,30,137,164))){
                        $defeito_constatado = trim(pg_fetch_result($res,$i,defeito_constatado));
                    }

                    if(in_array($login_fabrica, array(50,169,170))){
                        $revenda_cnpj_2 = trim(pg_fetch_result($res, $i, revenda_cnpj));
                    }

                    $revenda_nome       = trim(pg_fetch_result($res,$i,revenda_nome));
                    $codigo_posto       = trim(pg_fetch_result($res,$i,codigo_posto));
                    $uf_posto           = pg_fetch_result($res, $i, "contato_estado");
                    $posto_nome         = trim(pg_fetch_result($res,$i,posto_nome));

                    if(in_array($login_fabrica, array(162,165))){
                        $nome_tecnico   = trim(pg_fetch_result($res,$i,nome_tecnico));
                    }

                    $situacao_posto     = trim(pg_fetch_result($res,$i,credenciamento));
                    $impressa           = trim(pg_fetch_result($res,$i,impressa));
                    $extrato            = trim(pg_fetch_result($res,$i,extrato));
                    $os_reincidente     = trim(pg_fetch_result($res,$i,os_reincidente));
                    $produto_referencia_fabrica = trim(pg_fetch_result($res,$i,produto_referencia_fabrica));
                    $produto_referencia = trim(pg_fetch_result($res,$i,produto_referencia));
                    $produto_descricao  = trim(pg_fetch_result($res,$i,produto_descricao));
                    $produto_voltagem   = trim(pg_fetch_result($res,$i,produto_voltagem));
                    $tipo_atendimento   = trim(pg_fetch_result($res,$i,tipo_atendimento));
                    $grupo_atendimento  = pg_fetch_result($res, $i, "grupo_atendimento");
                    $data_nf            = trim(pg_fetch_result($res,$i,'data_nf'));
                    $tecnico_nome       = trim(pg_fetch_result($res,$i,tecnico_nome));
                    $nome_atendimento   = trim(pg_fetch_result($res,$i,descricao));
                    $sua_os_offline     = trim(pg_fetch_result($res,$i,sua_os_offline));
                    $numero_reclamacao  = trim(pg_fetch_result($res,$i,sua_os_offline));
                    $reincidencia       = trim(pg_fetch_result($res,$i,reincidencia));
                    $rg_produto         = trim(pg_fetch_result($res,$i,rg_produto));
                    $aparencia_produto  = trim(pg_fetch_result($res,$i,aparencia_produto));//TAKASHI HD925
                    $status_os          = trim(pg_fetch_result($res,$i,status_os)); //fabio
                    //HD391024
                    $status_checkpoint   = trim(pg_fetch_result($res,$i,status_checkpoint));
                    #117540
                    $status_cancelada = pg_fetch_result($res,$i,'cancelada');

                    if ($login_fabrica == 164) {
                        $segmento_atuacao                 = pg_fetch_result($res, $i, "segmento_atuacao");
                    }
                    if($login_fabrica == 165){
                        $produto_trocado   = trim(pg_fetch_result($res,$i,produto_trocado));

                        unset($label_defeito_constato);
                        $aux_sql = "
                            SELECT descricao AS defeito_constatado
                            FROM tbl_defeito_constatado
                            JOIN tbl_os ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                            WHERE tbl_os.os = $os
                        ";
                        $aux_res = pg_query($con, $aux_sql);

                        $label_defeito_constato = pg_fetch_result($aux_res, 0, 'defeito_constatado');
                    }

                    if ($login_fabrica == 158) {
                        $familia_produto                  = pg_fetch_result($res, $i, "familia_produto");
                        $digitacao_hora                   = pg_fetch_result($res, $i, "digitacao_hora");
                        $digitacao_hora_f                 = pg_fetch_result($res, $i, "digitacao_hora_f");
                        $ultima_modificacao               = pg_fetch_result($res, $i, "ultima_modificacao");
                        $tipo_atendimento                 = pg_fetch_result($res, $i, "descricao");
                        $data_conserto                    = pg_fetch_result($res, $i, "data_conserto");
                        $json_kof                         = pg_fetch_result($res, $i, "json_kof");
                        $json_kof                         = json_decode($json_kof, true);
                        $campos_adicionais                = pg_fetch_result($res, $i, "campos_adicionais");
                        $campos_adicionais                = json_decode($campos_adicionais, true);
                        $unidade_negocio                  = pg_fetch_result($res, $i, "unidade_negocio");

                        $xUnidadeNegocio = $oDistribuidorSLA->SelectUnidadeNegocioNotIn(null,null,$campos_adicionais['unidadeNegocio']);
                        $xxUnidadeNegocio = $xUnidadeNegocio[0]["cidade"];

                        $familia_produto                  = pg_fetch_result($res, $i, "familia_produto");
                        $sla_chopeira_postmix             = pg_fetch_result($res, $i, "sla_chopeira_postmix");
                        $sla_refrigerador_vending_machine = pg_fetch_result($res, $i, "sla_refrigerador_vending_machine");
                        $data_inicio_atendimento       = pg_fetch_result($res,$i,"inicio_atendimento");

                    }

                    if(in_array($login_fabrica,array(6,30,157,169,170))){
                        $os_posto_x   = trim(pg_fetch_result($res,$i,os_posto));
                    }

                    if($login_fabrica == 30){
                        $os_posto_x = (empty($os_revendedor) OR $os_revendedor == "null") ? $os_posto_x : $os_revendedor;
                    }

                    if ($login_fabrica == 158) {
                            $os_posto_x = $json_kof['osKof'];
                    }

                    if(in_array($login_fabrica, array(3,52,86)) or $multimarca =='t'){
                        $marca     = trim(pg_fetch_result($res,$i,marca));
                        $marca_nome     = trim(pg_fetch_result($res,$i,marca_nome));
                    }
                    if($login_fabrica ==52 and !empty($marca)){
                        $sqlx="select nome from tbl_marca where marca = $marca;";
                        $resx=pg_exec($con,$sqlx);
                        $marca_logo_nome         = pg_fetch_result($resx, 0, 'nome');
                    }

                    //HD 14927
                    if($mostra_data_conserto){
                        $data_conserto=trim(pg_fetch_result($res,$i,data_conserto));
                    }

                    if ($login_fabrica == 45){
                        $campo_interacao = trim(pg_fetch_result($res, $i, 'campo_interacao'));
                    }

                    if(in_array($login_fabrica, [3,35,72])){
                        $cancelada = pg_fetch_result($res, $i, "cancelada");
                    }

                    if($login_fabrica == 74) {
                        $os_interacao = pg_fetch_result($res, $i, os_interacao);
                        $atendido     = pg_fetch_result($res, $i, atendido);
                        $data_contato = pg_fetch_result($res, $i, data_contato);
                        $congelar = pg_fetch_result($res,$i,'congelar'); //hd_chamado=2588542
                        $status_cancelada = pg_fetch_result($res,$i,'os_bloqueada'); //hd_chamado=2588542
                        $cancelada = pg_fetch_result($res, $i, "cancelada");

                    }


                    if(in_array($login_fabrica,array(115,116,117,120))){
                        $valor_km = trim(pg_fetch_result($res,$i,valor_km));
                    }
                    if ($login_fabrica == 24) {
                        #$status_cancelada = pg_fetch_result($res,$i,'cancelada');
                        $congelar = pg_fetch_result($res,$i,'congelar');
                    }

                    if((in_array($login_fabrica, array(152,180,181,182))) && $sem_listar_peca <> 1) {
                    	$peca_referencia = pg_fetch_result($res, $i, 'peca_referencia');
						$peca_descricao  = pg_fetch_result($res, $i, 'peca_descricao');
						$peca_qtde       = pg_fetch_result($res, $i, 'peca_qtde');
                    }

                    if (in_array($login_fabrica, array(152,180,181,182))) $aux_obs = pg_fetch_result($res, $i, 'obs');

                    if ($login_fabrica == 30) {
                        $pedido = pg_fetch_result($res, $i, 'pedido');

                        if (!empty($pedido)) {

                            $digitacao_item = pg_fetch_result($res, $i, 'digitacao_item');
                            $nf = pg_fetch_result($res, $i, 'nf_fat') . ' ' . pg_fetch_result($res, $i, 'nf_emissao');
                            $peca_referencia = pg_fetch_result($res, $i, 'peca_referencia');
                            $peca_descricao = pg_fetch_result($res, $i, 'peca_descricao');
                            $peca_qtde       = pg_fetch_result($res, $i, 'peca_qtde');

                        } else {

                            /*
                             * Removido para mostrar todas as Peças da OS quando não estiver marcado a opção de
                             * "Consultar OS sem listar peça" HD 2597110 Esmaltec
                             * if ($old_os == $os) {
                             *   continue;
                             * }
                            */
                            $digitacao_item = '';
                            $nf = '';
                            $peca_referencia = '';
                            $peca_descricao = '';
                            $peca_qtde = "";
                        }

                        $old_os = $os;
                    }
                }

                $cor   = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
                $botao = ($i % 2 == 0) ? "azul" : "amarelo";

                /*IGOR - HD: 44202 - 22/10/2008 */
                if($login_fabrica==3){
                    $sqlI = "SELECT  status_os
                            FROM    tbl_os_status
                            WHERE   os = $os
                            AND status_os IN (120, 122, 123, 126, 140, 141, 142, 143, 246)
                            ORDER BY data DESC LIMIT 1";
                    $resI = pg_query ($con,$sqlI);
                    if (pg_num_rows ($resI) > 0){
                        $status_os = trim(pg_fetch_result($resI,0,status_os));
                        if ($status_os == 126 || $status_os == 143 || $status_os == 246) {
                            $cor="#FF0000";
                            #$excluida = "t"; HD 56464
                        }
                    }
                }

                ##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - INÍCIO #####
                unset($marca_reincidencia);
                if ($reincidencia =='t' and $login_fabrica != 1 ) {
                    if($login_fabrica == 87) $cor = "#40E0D0"; elseif($login_fabrica == 30) $cor = "#5F9EA0"; else $cor = "#D7FFE1";
                    $marca_reincidencia = 'sim';
                }

                if ($login_fabrica == 1 && $reincidencia == 't' && $tipo_atendimento == 334) {
                    $cor = "#FFC0CB";
                }
                if ($excluida == "t" && !isset($cancelaOS)){
                    if($login_fabrica != 30){
                        $cor = "#FF0000";
                    }else{
                        $cor = "#F0F";
                    }
                }

                if($login_fabrica == 74 and $cancelada == "t"){
                    $cor = "#FF0000";
                }


                if ($login_fabrica==20 AND $status_os == "94" AND $excluida == "t"){
                    $cor = "#CACACA";
                }
                $vintecincodias = "";

                if ($login_fabrica == 91 && $status_os == 179) {
                    $cor="#FFCCCC";
                }

                if ($login_fabrica == 91 && $status_os == 13) {
                    $cor = "#CB82FF";
                }

                if($login_fabrica == 114){
                    if ($status_os == "62") {
                        $cor = ($login_fabrica == 114) ? "#FFCCCC" : "#E6E6FA";
                    }
                }
                if (in_array($login_fabrica,array(3,11,43,51,87,172))) {

                    if ($status_os == "62") {
                        $cor = ($login_fabrica==43 or $login_fabrica==51) ? "#FFCCCC" : "#E6E6FA"; //HD 46730 HD 288642
                    }
                    if (in_array($status_os,array("72","87","116","120","122","140","141"))){
                        $cor="#FFCCCC";
                    }

                    if($login_fabrica == 87 AND ($cor == "#FFCCCC" OR $cor == "#E6E6FA")) {
                        $cor = "#FFA5A4";
                    }

                    if (($status_os=="64" OR $status_os=="73"  OR $status_os=="88" OR $status_os=="117") && strlen($fechamento)==0) {
                        if($login_fabrica == 87){
                            $cor = "#FEFFA4";
                        }else{
                            $cor = "#00EAEA";
                        }
                    }
                    if ($status_os=="65"){
                        $cor="#FFFF99";
                    }
                }

                if (in_array($login_fabrica, array(141,144))) {
                    switch ($status_os) {
                        case 192:
                            $cor = "#FFCCCC";
                            break;

                        case 193:
                            $cor = "#CCFFFF";
                            break;

                        case 194:
                            $cor = "#CB82FF";
                            break;
                    }
                }

                if ($login_fabrica==94){
                    $sqlI = "SELECT status_os
                            FROM    tbl_os_status
                            WHERE   os = $os
                            AND     status_os IN (62,64)
                      ORDER BY      data DESC
                            LIMIT   1";
                    $resI = pg_query ($con,$sqlI);
                    if (pg_num_rows ($resI) > 0){
                        $status_os = trim(pg_fetch_result($resI,0,status_os));
                        if ($status_os <> 64) {
                            $cor="#FFCCCC";
                        }
                    }
                }

                if ($login_fabrica == 3 && $status_os == 174) {
                    $cor = "#CB82FF";
                }

                if($status_os == "175"){
                    $cor = "#A4A4A4";
                }

                // OSs abertas há mais de 25 dias sem data de fechamento
                if (strlen($fechamento) == 0 && $excluida != "t" && $cancelada != "t" && $login_fabrica != 14) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '".(($login_fabrica == 91) ? "30" : "25")." days','YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_atual = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
                        if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                        $vintecincodias = "sim";
                    }
                }

                if (strlen($btn_acao_pre_os) > 0) {

                    // OSs abertas há menos de 24 horas sem data de fechamento
                    if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 30) {

                        $sqlX = "SELECT TO_CHAR('$dt_hr_abertura'::timestamp + INTERVAL '24 hours','YYYY-MM-DD HH24:MI:SS')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_timestamp,'YYYY-MM-DD HH24:MI:SS');";
                        $resX = pg_query ($con,$sqlX);
                        $aux_atual = pg_fetch_result ($resX,0,0);

                        if ($aux_consulta >= $aux_atual) {
                            $cor = "#33CC00";
                            $vintequatrohoras = "sim";
                            $smile = 'js/fckeditor/editor/images/smiley/msn/regular_smile.gif';
                        }

                    }

                    // OSs abertas há mais de 24 horas e menor que 72 sem data de fechamento
                    // maior que 72 horas sem data de fechamento
                    if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 30) {

                        //$dt_hr_abertura = '2010-06-11 16:04:23';//data de teste
                        $sqlX = "SELECT TO_CHAR('$dt_hr_abertura'::timestamp + INTERVAL '72 hours','YYYY-MM-DD HH24:MI:SS')";
                        $resX = pg_query ($con,$sqlX);
                        $aux_consulta = pg_fetch_result($resX,0,0);

                        $sqlX = "SELECT TO_CHAR(current_timestamp,'YYYY-MM-DD HH24:MI:SS');";
                        $resX = pg_query ($con,$sqlX);
                        $aux_atual = pg_fetch_result ($resX,0,0);

                        if ($aux_consulta <= $aux_atual) {
                            $cor = "#FF0000";//maior que 72
                            $smile = 'js/fckeditor/editor/images/smiley/msn/angry_smile.gif';
                        } else if ($vintequatrohoras != 'sim' && $aux_consulta > $aux_atual) {
                            $cor = "#FFFF66";//menor que 72
                            $smile = 'js/fckeditor/editor/images/smiley/msn/whatchutalkingabout_smile.gif';
                        }

                    }

                }

                /**
                 * - Legendas para prazo limite
                 */
                if(strlen($fechamento) == 0  && $login_fabrica == 30 && strlen($data_limite) > 0){

                    $sqlLimite = "SELECT ('$data_limite' - CURRENT_DATE) AS limite;";
//                     echo $sqlLimite."<br>";
                    $resLimite = pg_query($con,$sqlLimite);
                    $tempo_limite = pg_fetch_result($resLimite,0,limite);
                   // $tempo_limite = explode("day",$auxLimite);
                   // $tempo_limite = (int)$tempo_limite[0];

                    if(strlen($fechamento) == 0 && $tempo_limite > 3){
                        $cor = "#3C0";
                    }elseif(strlen($fechamento) == 0 && ($tempo_limite > 1 && $tempo_limite < 4)){
                        $cor = "#FF6";
                    }elseif(strlen($fechamento) == 0 && $tempo_limite <= 1){
                        $cor = "#F00";
                    }
                }else{
                    $tempo_limite = "";
                }

                // OSs abertas há mais de 10 dias sem data de fechamento - Nova
                if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 43) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '10 days','YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_atual = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                        $cor = "#FF0033";
                    }
                }

                // CONDIÇÕES PARA INTELBRÁS - INÍCIO
                if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 14) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '3 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_atual = pg_fetch_result($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                        if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                    }

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_atual = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
                }
                // CONDIÇÕES PARA INTELBRÁS - FIM

                // CONDIÇÕES PARA COLORMAQ - INÍCIO
                if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 50) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_atual = pg_fetch_result($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                        if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                    }

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '10 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_atual = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                        $cor = "#FF6633";
                    }

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_atual = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                        $cor = "#FF0000";
                    }
                }

                if($excluida=='t' AND ($login_fabrica==50 or $login_fabrica ==14)){//HD 37007 5/9/2008
                    $cor = "#FFE1E1";
                }
                // CONDIÇÕES PARA COLORMAQ - FIM

                // CONDIÇÕES PARA NKS - INÍCIO
                if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 45) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR(current_date - INTERVAL '15 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);
                    $sqlX = "SELECT TO_CHAR($aux_abertura::date,'YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta2 = pg_fetch_result($resX,0,0);

                    if ($aux_consulta < $aux_consulta2 && strlen($fechamento) == 0) $cor = "#1e85c7";

                    $sqlX = "SELECT TO_CHAR(current_date - INTERVAL '15 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date - INTERVAL '25 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta2 = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_consulta3 = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta2 <= $aux_consulta3 AND $aux_consulta3 <= $aux_consulta && strlen($fechamento) == 0) $cor = "#FF6633";

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_consulta2 = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta < $aux_consulta2 && strlen($fechamento) == 0) $cor = "#9512cc";
                }
                // CONDIÇÕES PARA NKS - FIM

                // CONDIÇÕES PARA BLACK & DECKER - INÍCIO
                // Verifica se não possui itens com 5 dias de lançamento

                //HD 163220 - Colocar legenda nas OSs com atendimento Procon/Jec (Jurídico) - tbl_hd_chamado.categoria='procon'
                if ($login_fabrica == 11 or $login_fabrica == 172) {
                    $sql_procon = "SELECT tbl_hd_chamado.hd_chamado
                                            FROM tbl_hd_chamado
                                            JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
                                            WHERE tbl_hd_chamado_extra.os = $os
                                            AND tbl_hd_chamado.categoria IN ('pr_reclamacao_at', 'pr_info_at', 'pr_mau_atend', 'pr_posto_n_contrib', 'pr_demonstra_desorg', 'pr_bom_atend', 'pr_demonstra_org')";
                    $res_procon = pg_query($con, $sql_procon);

                    if (pg_num_rows($res_procon)) {
                        $cor = "#C29F6A";
                    }
                }

                // OS com mais de 7 dias sem lançamento de peças
                if ($login_fabrica == 24) {
                    $sql_7_dias = "SELECT os FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica AND JSON_FIELD('os_7_dias_sem_peca', campos_adicionais) = 'true'";
                    $res_7_dias = pg_query($con, $sql_7_dias);
                    if (pg_num_rows($res_7_dias) > 0) {
                        $cor = "#54A8AE";    
                    }
                }

                // Verifica se está sem fechamento há 20 dias ou mais da data de abertura
                if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $aux_atual = pg_fetch_result($resX,0,0);

                    if ($consumidor_revenda != "R") {
                        if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
                            $mostra_motivo = 1;
                            if($login_fabrica == 87){
                                $cor = "#A4B3FF";
                            }else{
                                $cor = "#91C8FF";
                            }
                        }
                    }
                }

                if ($login_fabrica == 30) {
                    $sqlX = "SELECT os_troca,ressarcimento,gerar_pedido FROM tbl_os_troca WHERE os = $os ORDER BY data desc limit 1 ";
                    $resX = @pg_query($con,$sqlX);
                    $ressarc = pg_fetch_result($resX,0,ressarcimento);
                    $gp = pg_fetch_result($resX,0,gerar_pedido);

                    if($ressarc == 't' || $gerar_pedido == 't'){
                        $cor = "#FC6";
                    }
                } else if ($fabrica_autoriza_ressarcimento) {
                    $sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os ORDER BY data desc limit 1 ";
                    $resX = @pg_query($con,$sqlX);
                    if(pg_num_rows($resX) == 1 && pg_fetch_result($resX, 0, ressarcimento) == 't'){
                        $cor = "#CCCCFF";
                    }
                }

                if ($login_fabrica == 1) {
                    $aux_abertura = fnc_formata_data_pg($abertura);

                    $sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
                    $resX = pg_query($con,$sqlX);
                    $data_hj_mais_5 = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sql = "SELECT COUNT(tbl_os_item.*) AS total_item
                            FROM tbl_os_item
                            JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                            JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
                            WHERE tbl_os.os = $os
                            AND   tbl_os.data_abertura::date >= '$aux_consulta'";
                    $resItem = pg_query($con,$sql);

                    $itens = pg_fetch_result($resItem,0,total_item);

                    if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#ffc891";

                    $mostra_motivo = 2;
                }
                // CONDIÇÕES PARA BLACK & DECKER - FIM

                // Gama
                if ($login_fabrica==51){ // HD 65821
                    $sqlX = "SELECT status_os,os FROM tbl_os JOIN tbl_os_status USING(os) WHERE os = $os AND status_os = 13";
                    $resX = pg_query($con,$sqlX);
                    if(pg_num_rows($resX)> 0){
                        $cor = "#CACACA";
                    }
                }

                if ($login_fabrica == 94 AND strlen($os) > 0) {

                    $sqlT = "SELECT os FROM tbl_os_campo_extra WHERE os = $os";
                    $resT = pg_query($con, $sqlT);

                    if (pg_num_rows($resT)) {
                        $cor = "silver";
                    }

                }

                //HD 211825: Novo status de OS de Troca criado: Autorização para Troca pela Revenda, somente Salton
                if ($fabrica_autoriza_troca_revenda && strlen($os)) {
                    $sql = "SELECT troca_revenda FROM tbl_os_troca WHERE os = $os";
                    $res_troca_revenda = pg_query($con, $sql);

                    if (pg_num_rows($res_troca_revenda)) {
                        $troca_revenda = pg_result($res_troca_revenda, 0, troca_revenda);
                    }
                    else {
                        $troca_revenda = "";
                    }
                }

                if ($troca_revenda == 't') {
                    $cor = "#d89988";
                }

                if ($vintecincodias == 'sim' and $marca_reincidencia == 'sim') {
                    if($login_fabrica == 87) $cor = "#D2D2D2"; else $cor = "#CC9900";
                }

                // CONDIÇÕES PARA GELOPAR - INÍCIO
                if($login_fabrica==85 AND strlen($os)>0){
                    $sqlG = "SELECT
                                interv.os
                            FROM (
                                SELECT
                                ultima.os,
                                (
                                    SELECT status_os
                                    FROM tbl_os_status
                                    WHERE status_os IN (147)
                                    AND tbl_os_status.os = ultima.os
                                    ORDER BY data
                                    DESC LIMIT 1
                                ) AS ultimo_status
                                FROM (
                                        SELECT os FROM tbl_os WHERE tbl_os.os = $os
                                ) ultima
                            ) interv
                            WHERE interv.ultimo_status IN (64,147);";
                            #echo nl2br($sqlG);
                    $resG = pg_exec($con,$sqlG);

                    if(pg_numrows($resG)>0){
                        $cor = "#AEAEFF";
                    }
                }
                // CONDIÇÕES PARA GELOPAR - FIM

                ##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - FIM #####

                if (strlen($sua_os) == 0){
                    $sua_os = $os;
                }
                if ($login_fabrica == 1) {
                    $sua_os2 = $codigo_posto.$sua_os;
                    $sua_os = "<a href='os_press.php?os=$os' target='_blank'>" . $codigo_posto.$sua_os . "</a>";
                }

                //HD391024
                if($login_fabrica == 96 and strlen($btn_acao_pre_os) > 0){
                    if($tipo_atendimento == '92') $cor = "#FFFF66";
                    if($tipo_atendimento == '93') $cor = "#C94040";
                    if($tipo_atendimento == '94') $cor = "#33CC00";
                }

                if($login_fabrica == 40 AND $status_os == 118){
                    $cor = "#BFCDDB";
                }

                if (in_array($status_os, array(158))){
                    $cor="#FFCCCC";
                }

                if ($login_fabrica == 45){
                    //INTERACAO

                    $sqlxyz = "SELECT count(*) from tbl_os_interacao where os = $os";
                    $resxyz = pg_query($con,$sqlxyz);
                    $count_interacao = pg_fetch_result($resxyz, 0, 0);
                    if ($count_interacao > 0){

                        if (strlen(trim($campo_interacao))==0){
                            $cor = "#F98BB2";
                        }
                    }

                    if ($tipo_os != 'INTERACAO'){

                        //OS TROCA - RESOLVIDO
                        $sqlaaa = "SELECT tbl_os.os from tbl_os join tbl_os_troca using(os) join tbl_faturamento_item using(pedido,peca) where tbl_os.os=$os";
                        $resaaa = pg_query($con,$sqlaaa);

                        if (pg_num_rows($resaaa)>0){
                            $cor = "#56BB71";
                        }

                        //OS TROCA - PENDENTE
                        $sqlbbb = "SELECT tbl_os.os from tbl_os join tbl_os_troca using(os) left join tbl_faturamento_item using(pedido,peca) where tbl_os.os=$os and tbl_faturamento_item.faturamento_item is null and tbl_os_troca.ressarcimento is false ";
                        $resbbb = pg_query($con,$sqlbbb);

                        if (pg_num_rows($resbbb)>0){
                            $cor = "#EAEA1E";
                        }
                    }
                }

                $atendido = ($atendido == 't' or (strlen($finalizada) and !strlen($os_interacao))) ? 't' : 'f';

                if ($login_fabrica == 158 && (empty($data_conserto) && empty($finalizada)) && !empty($json_kof)) {
                    if (strtolower($tipo_atendimento) == "sanitização") {
                        list($jk_data, $jk_hora)        = explode(" ", $json_kof["dataAbertura"]);
                        list($jk_dia, $jk_mes, $jk_ano) = explode("/", $jk_data);
                        $jk_data                        = "$jk_ano-$jk_mes-$jk_dia $jk_hora";

                        if (strtotime($jk_data) < strtotime("now")) {
                            $cor = "#FF0000";
                        }
                    } else {
                        if(strtoupper($familia_produto) == 'REFRIGERADOR' || strtoupper($familia_produto) == 'VENDING MACHINE') {
                            if ($sla_refrigerador_vending_machine == "fora_sla") {
                                $cor = "#FF0000";
                            }
                        } else {
                            if ($sla_chopeira_postmix == "fora_sla") {
                                $cor = "#FF0000";
                            }
                        }
                    }
                }
                if (!empty($os) && $login_fabrica != 87) {
                    $sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os LIMIT 1 ";
                    $resX = pg_query($con,$sqlX);

                    if(pg_num_rows($resX)==1){
                        $cor = (pg_fetch_result($resX,0,ressarcimento)=='t') ? "#CCCCFF" : "#FFCC66";
                    }
                }

				if (($status_cancelada == 't' or $excluida == 't') && in_array($login_fabrica, array(11,172,72,152,180,181,182))) {
                    $cor = '#FF0000';
                }


                if ($login_fabrica == 158) {
                    unset($auditorias_pendentes);
                    $auditorias_pendentes = trim(pg_fetch_result($res, $i, 'auditorias_pendentes'));

                    if (strlen($auditorias_pendentes) > 0) {
                        $cor = "#F78F8F";
                    }
                }

                if ($login_fabrica == 131){
                    $sqlReprovada = "SELECT tbl_auditoria_os.auditoria_os
                        FROM tbl_auditoria_os
                        WHERE os = $os
                        AND reprovada IS NOT NULL
                        ORDER BY reprovada DESC LIMIT 1";
                    $resReprovada = pg_query($con, $sqlReprovada);

                    if (pg_num_rows($resReprovada) > 0) {
                        $cor = $cor_os_reprovada_auditoria;
                    }
                }

                if ($login_fabrica == 148 && !empty($finalizada)) {
                    $sqlReprov = "  SELECT tbl_auditoria_os.reprovada
                                    FROM tbl_auditoria_os
                                    JOIN tbl_os ON tbl_auditoria_os.os = tbl_os.os
                                    WHERE tbl_auditoria_os.os = $os
                                    AND tbl_auditoria_os.reprovada NOTNULL
                                    ORDER BY tbl_auditoria_os.data_input DESC";
                                            
                    $resReprov = pg_query($con, $sqlReprov);

                    if (pg_num_rows($resReprov) > 0) {
                        $cor = "#FF0000";
                    }
                }

                echo "<tr data-os='$os' class='Conteudo' height='15' bgcolor='$cor' align='left' id='div_atendimento_$hd_chamado'>";
                if ($login_fabrica == 146) {
                    $sqlPedido = "  SELECT status_pedido
                                    FROM tbl_os_item
                                    JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				    JOIN tbl_pedido ON tbl_pedido.pedido = tbl_os_item.pedido
                                    WHERE tbl_os_produto.os = $os
				    AND status_pedido <> 14";
                    $resPedido = pg_query($con, $sqlPedido);
                    if ( pg_numrows($resPedido) == 0 and empty($extrato)){
                        echo "<td nowrap><input type='checkbox' name='exclui_os[]' value='$os' form='form_exclui_os' /></td>";
                    } else {
                        echo "<td nowrap></td>";
                    }
                }

                if(($telecontrol_distrib OR in_array($login_fabrica, [131,156])) and empty($extrato)){
                    echo "<td nowrap><input type='checkbox' name='exclui_os[]' value='$os' form='form_exclui_os' /></td>";
                } else if ($telecontrol_distrib OR in_array($login_fabrica, [131,156])) {
                    echo "<td nowrap></td>";
                }

                if ($login_fabrica == 3) {
                    echo "<td nowrap>&nbsp;$codigo_posto</td>";
                }
                if (strlen($btn_acao_pre_os) == 0) {
                    //hd 231922
                    echo "<td nowrap>&nbsp;";

                        // Verifica se OS está em AUD
                        if (in_array($login_fabrica, [167, 203])) {
                            $sql_aud = "SELECT os FROM tbl_auditoria_os where os = $os AND liberada IS NULL AND cancelada IS NULL AND reprovada IS NULL";
                            $res_aud = pg_query($con, $sql_aud);
                            if (pg_num_rows($res_aud) > 0) {
                                $status_checkpoint = 37;
                            }
                        }

                        exibeImagemStatusCheckpoint($status_checkpoint,$sua_os);
                        if (in_array($login_fabrica, array(178))){
                            $os_link = explode("-", $sua_os);
?>
                            <a target='_blank' href='os_revenda_press.php?os_revenda=<?=$os_link[0]?>'><?=$sua_os?></a>
<?php
                        }else if ($login_fabrica == 183) {
                            $os_link = explode("-", $sua_os);
                            if (count($os_link) > 1){ ?>
                                <a target='_blank' href='os_revenda_press.php?os_revenda=<?=$os_link[0]?>'><?=$sua_os?></a>
<?php
                            }else{ ?>
                                <a href="os_press.php?os=<?=$os?>" target="_blank"><?=$sua_os?></a>
<?php
                            }
                        }else{ ?>
                            <a href="os_press.php?os=<?=$os?>" target="_blank"><?=$sua_os?></a>
<?php
                        }
                    echo "</td>";

                    if($login_fabrica == 138){
                        $pedido = pg_fetch_result($res, $i, 'pedido');
                        echo "<td nowrap>&nbsp; $pedido </td>";
                    }
                }else {
                    if($login_fabrica == 96){
                        echo "<td nowrap align='center'><a href='print_atendimento_gravado.php?hd_chamado=$hd_chamado' target=_blank>&nbsp;" . $hd_chamado . "</a></td>";
                    } else {
                        echo "<td nowrap align='center'><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank'>" . $hd_chamado . "</a></td>";
                    }
                }

                if(in_array($login_fabrica, array(152,180,181,182))){
                    $sql = "SELECT tbl_tipo_atendimento.tipo_atendimento FROM tbl_os
                        INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                            AND tbl_tipo_atendimento.entrega_tecnica IS TRUE
                        WHERE tbl_tipo_atendimento.fabrica = {$login_fabrica} AND tbl_os.os = {$os}";
                    $resEntregaTecnica = pg_query($con, $sql);

                    if(pg_num_rows($resEntregaTecnica) > 0){
                        $entrega_tecnica = strtoupper(traduz("ENTREGA TÉCNICA"));
                    }else{
                        $entrega_tecnica = strtoupper(traduz("REPARO"));
                    }
                    echo "<td nowrap>".$entrega_tecnica."</td>";
                }

               if(in_array($login_fabrica, array(35)) AND strlen($btn_acao_pre_os) > 0 ) {
                    $sql_codigo_rastreio = "SELECT tbl_hd_chamado_postagem.numero_postagem
                                 FROM tbl_hd_chamado_postagem
                                 JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_postagem.hd_chamado AND tbl_hd_chamado_postagem.fabrica=$login_fabrica
                                 WHERE tbl_hd_chamado_postagem.hd_chamado=$hd_chamado AND tbl_hd_chamado_postagem.fabrica=$login_fabrica";
                    $resCodigoRastreio = pg_query($con, $sql_codigo_rastreio);
                    $n_codigo_rastreio = pg_fetch_result($resCodigoRastreio,0, "numero_postagem");
                    if(pg_num_rows($resCodigoRastreio) > 0){
                        $sql_conhecimento = "SELECT conhecimento AS conhecimento
                                  FROM tbl_faturamento_correio
                                 WHERE fabrica     = $login_fabrica
                                   AND numero_postagem = '$n_codigo_rastreio'";
                        $resconhecimento = pg_query($con, $sql_conhecimento);
                        $n_conhecimento = pg_fetch_result($resconhecimento,0, "conhecimento");
                        echo "<td align='center'><a href='./relatorio_faturamento_correios.php?conhecimento={$n_conhecimento}' rel='shadowbox'>$n_conhecimento</a></td>";
                    } else {
                        echo "<td></td>";
                    }
               }

                if (in_array($login_fabrica, array(30, 35, 50, 52, 104)) and strlen($btn_acao_pre_os)==0) {
                    if ($login_fabrica == 30){
                        if ($login_cliente_admin){
                            echo "<td nowrap><a href='../admin_cliente/pre_os_cadastro_sac_esmaltec.php?callcenter=$hd_chamado' target=_blank>" . $hd_chamado . "</a></td>";
                        }else{
                            echo "<td nowrap align='center'><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target=_blank>" . $hd_chamado . "</a></td>";
                        }
                    }else{

                        echo "<td nowrap><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target=_blank>" . $hd_chamado . "</a></td>";
                    }
                    if($login_fabrica == 30){
?>
                        <td nowrap><?=$cliente_admin_nome?></td>
<?
                    }
                }

                echo ($login_fabrica==19 OR $login_fabrica==10 OR $login_fabrica==1) ? "<td nowrap>&nbsp;" . $sua_os_offline . "</td>" : "";
                #117540

                if ($login_fabrica == 158) {
                    echo "<td>$auditorias_pendentes</td>";
                }

                if($login_fabrica == 19){ //hd_chamado=2881143
                    echo '<td nowrap align="center"><a href="extrato_consulta_os.php?extrato='.$extrato.'" target="_blank">'.$extrato.'</a></td>';
                }

                if($login_fabrica==52) {
                    echo "<td nowrap><acronym title='Cliente Fricon: $cliente_fricon' style='cursor:help;'>&nbsp" . substr($cliente_fricon, 0, 20) .  "...</acronym></td>";
                    echo "<td nowrap>&nbsp;" .$numero_ativo_res. "</td>";
                }

                # HD-776394
                if($login_fabrica == 74) {
                    echo "<td nowrap>&nbsp;" . ($atendido == 't' ? "" : "Não ") . "Atendido</td>";
                    echo "<td nowrap>" . ((empty($os_interacao) and !empty($finalizada)) ? "" : $data_contato) . "</td>";
                }

                if(!in_array($login_fabrica,array(1,3,20,30,50,81,127,138,145))){ // HD-2296739
                    echo "<td id='td_serie_{$os}' nowrap>&nbsp;" . $serie . "</td>";
                }

                if ($login_fabrica == 104) {

                    if(!empty($os)){
                        $sql_dias_aberto = "SELECT data_abertura::date - CASE WHEN data_fechamento::date IS NULL THEN DATE(NOW()) ELSE data_fechamento::date END AS dias_aberto FROM tbl_os WHERE os = {$os}";
                        $res_dias_aberto = pg_query($con, $sql_dias_aberto);

                        if(pg_num_rows($res_dias_aberto) == 1){
                            $dias_aberto = pg_fetch_result($res_dias_aberto, 0, 'dias_aberto');
                            $dias_aberto = str_replace("-", "", $dias_aberto." dia(s)");
                        }

                        echo "<td nowrap align='center'>$dias_aberto</td>";

                    }
                       echo "<td nowrap><acronym title='Data Abertura: $abertura' style='cursor: help;'>&nbsp;" . substr($abertura,0,5) . "</acronym></td>";
                }

                if(in_array($login_fabrica,array(160)) or $replica_einhell){ // HD-2296739
                    echo "<td nowrap>&nbsp;" . $type . "</td>";
                }

                if ($login_fabrica == 158) {
                    echo "<td nowrap><acronym title='Data Criação: $digitacao_hora_f' style='cursor: help;'>".$digitacao_hora_f."</acronym></td>";
                }

                if($login_fabrica <> 104){ //HD-3139131
                    if ($login_fabrica == 158) {
                        echo "<td nowrap align='center'><acronym title='Data Abertura: $abertura' style='cursor: help;'>&nbsp;" . $abertura . "</acronym></td>";
                    } else {
                        echo "<td nowrap align='center'><acronym title='".traduz("Data Abertura").": $abertura' style='cursor: help;'>&nbsp;" . substr($abertura,0,5) . "</acronym></td>";
                    }
                }

                if (in_array($login_fabrica, array(169,170))) {

                    $primeira_data_agendamento = pg_fetch_result($res,$i,'primeira_data_agendamento');
                    $data_confirmacao          = pg_fetch_result($res,$i,'data_confirmacao');
                    $inspetor_sap              = pg_fetch_result($res,$i,'inspetor_sap');

					if(!empty($primeira_data_agendamento)) {
						$sqlReagendamento = "SELECT date(tbl_tecnico_agenda.data_agendamento) as data_agendamento
											FROM tbl_tecnico_agenda
											WHERE tbl_tecnico_agenda.os = $os
											AND tbl_tecnico_agenda.data_agendamento != '$primeira_data_agendamento'
											AND tbl_tecnico_agenda.confirmado IS NOT NULL
											ORDER BY tbl_tecnico_agenda.data_agendamento DESC
											LIMIT 1
											";
						$resReagendamento = pg_query($con, $sqlReagendamento);

						$ultima_data = pg_fetch_result($resReagendamento, 0, 'data_agendamento');
					}
                    echo "<td nowrap>".mostra_data($primeira_data_agendamento)."</td>";
                    echo "<td nowrap>".mostra_data($data_confirmacao)."</td>";
                    echo "<td nowrap>".mostra_data($ultima_data)."</td>";
                    echo "<td nowrap>$inspetor_sap</td>";
                }

                if ($login_fabrica == 178){
                    $inspetor_sap = pg_fetch_result($res,$i,'inspetor_sap');
                    echo "<td nowrap>$inspetor_sap</td>";
                }

                if($login_fabrica == 138){ //2439865
                    echo '<td nowrap>' . $digitacao . '</td>';
                }

                if ($login_fabrica ==11 or $login_fabrica == 172) { // HD 74587
                    $sql_p = " SELECT to_char(tbl_pedido.data,'DD/MM/YYYY') as data_pedido
                                FROM tbl_os_produto
                                JOIN tbl_os_item USING(os_produto)
                                JOIN tbl_pedido  USING(pedido)
                                WHERE tbl_os_produto.os = $os
                                AND   tbl_pedido.fabrica = $login_fabrica
                                ORDER BY tbl_pedido.pedido ASC LIMIT 1 ";
                    $res_p = @pg_query($con,$sql_p);
                    echo "<td nowrap >";
                    if (pg_num_rows($res_p) > 0) {
                        $data_pedido = pg_fetch_result($res_p,0,data_pedido);
                        echo "<acronym title='Data Pedido: $data_pedido' style='cursor: help;'>" . substr($data_pedido,0,5) . "</acronym>";
                    }
                    echo "</td>";
                }

                //HD 14927
                if($mostra_data_conserto){
                    echo "<td nowrap ><acronym title='".traduz("Data do Conserto").": $data_conserto' style='cursor: help;'>&nbsp;" . substr($data_conserto,0,5) . "</acronym></td>";
                }

                $aux_fechamento = ($login_fabrica == 1) ? $finalizada : $fechamento;
                //HD 204146: Fechamento automático de OS
                if ($login_fabrica == 3) {
                    $sql = "SELECT sinalizador FROM tbl_os WHERE os=$os";
                    $res_sinalizador = pg_query($con, $sql);
                    $sinalizador = pg_result($res_sinalizador, 0, sinalizador);
                }

                if ($sinalizador == 18) {
                    echo "<td nowrap align='center'><acronym title='Data Fechamento: $aux_fechamento - FECHAMENTO AUTOMÁTICO' style='cursor: help; color:#FF0000; font-weight: bold;'>F. AUT</acronym></td>";
                }else {
                    if(!in_array($login_fabrica, array(3))) echo "<td nowrap align='center'><acronym title='Data Fechamento: $aux_fechamento' style='cursor: help;'>&nbsp;" . substr($aux_fechamento,0,5) . "</acronym></td>";
                }

                if ($login_fabrica == 158) {
                    echo "<td nowrap ><acronym title='Última modificação: $ultima_modificacao' style='cursor: help;'>&nbsp;" .$ultima_modificacao. "</acronym></td>";
                }

                if(in_array($login_fabrica, array(156))){

                    if($tipo_posto_interno == "t"){

                        $status_os = (strlen($status_os) == 0) ? 0 : $status_os;
                        $status_elgin = "";

                        if($status_os > 0){

                            $sql_status_desc = "SELECT descricao FROM tbl_status_os WHERE status_os = {$status_os}";
                            $res_status_desc = pg_query($con, $sql_status_desc);

                            if(pg_num_rows($res_status_desc) > 0){
                                $status_elgin = pg_fetch_result($res_status_desc, 0, "descricao");
                            }

                        }


                    }else{
                        $status_elgin = "";
                    }

                    echo "<td nowrap> $status_elgin </td>";
                }

                //HD 211825: Filtrar por tipo de OS: Consumidor/Revenda
                if ($btn_acao_pre_os) {
					if($login_fabrica == 120) {
                        echo "<td nowrap>$desc_tipo_atendimento</td>";
					}

                }else {
                    if(in_array($login_fabrica, array(87)) AND !empty($tipo_atendimento)){
                        $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                        $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);
                        $desc_tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');

                        echo "<td nowrap>$desc_tipo_atendimento</td>";
                    }else{
                        if(in_array($login_fabrica, array(94,115,116,117,120,141,144,153,156,161,163,167,171,174,175,176,177,203))){
                            if(!empty($tipo_atendimento)){
                                $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                                $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);
                                $desc_tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');
                            }else{
                                $desc_tipo_atendimento = "";
                            }

                            echo "<td nowrap>$desc_tipo_atendimento</td>";
                        }
                        if(!in_array($login_fabrica,array(30,156,138,171,174))){ //2439865

                            if ($login_fabrica == 178){
                                switch ($consumidor_revenda) {
                                    case "C":
                                        echo "<td nowrap align='center'><acronym title='Consumidor' style='cursor: help;'>CONS</acronym></td>";
                                    break;

                                    case "R":
                                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>REV</acronym></td>";
                                    break;

                                    case "A":
                                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>ARQ/ENG</acronym></td>";
                                    break;

                                    case "S":
                                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>CONST</acronym></td>";
                                    break;

                                    case "E":
                                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>EQP. COMER</acronym></td>";
                                    break;

                                    case "I":
                                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>INST</acronym></td>";
                                    break;

                                    case "P":
                                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>POSTO</acronym></td>";
                                    break;

                                    case "":
                                        echo"<td nowrap> &nbsp; </td>";
                                    break;
                                }
                            }else{
                                switch ($consumidor_revenda) {
                                    case "C":
                                        echo "<td nowrap align='center'><acronym title='Consumidor' style='cursor: help;'>CONS</acronym></td>";
                                    break;

                                    case "R":
                                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>REV</acronym></td>";
                                    break;

                                    case "":
                                            /*HD-1899424*/
                                            if(in_array($login_fabrica, array(138)))    echo "<td nowrap> &nbsp; </td>";
                                            else                                        echo "<td nowrap> N/I</td>";
                                    break;
                                }
                            }
                        }
                    }

                }

                if ($login_fabrica == 52) {
                    $sql = "SELECT data_abertura,current_date as data_atual
                            FROM   tbl_os
                            where fabrica = $login_fabrica
                            and os=$os";
                    $resx = pg_query($con,$sql);
                    if(pg_num_rows($resx) > 0){
                        $data_abertura_os = pg_fetch_result($resx, 0, 0);
                        $data_atual = pg_fetch_result($resx, 0, 1);
                        $start = strtotime($data_abertura_os);

                        $end = strtotime($data_atual);

                        $diff = $end - $start;

                        $diff = ($diff / 86400);

                        if ($diff == 1){
                            $diferença = traduz("24 horas");
                        }

                        if ($diff == 2){
                            $diferença = traduz("48 horas");
                        }

                        if ($diff == 3){
                            $diferença = traduz("72 horas");
                        }

                        if ($diff > 3){
                            $diferença = traduz("+ 72 horas");
                        }

                        if (empty($diferença)) {
                            $diferença = "&nbsp;";
                        }
                        if (empty($aux_fechamento)){
                            echo "<td nowrap>".$diferença."</td>";
                        }else{
                            echo "<td nowrap>".$diferença."</td>";
                        }
                    }else{
                        echo "<td nowrap></td>";
                    }
                }

                if ($login_fabrica == 158) {
					echo "<td nowrap>";
					echo !empty($xxUnidadeNegocio) ? $xxUnidadeNegocio : $campos_adicionais['unidadeNegocio']; 
					echo "</td>";
                    if (empty($login_cliente_admin)) {
                        echo "<td nowrap>{$json_kof['protocoloKof']}</td>";
                    }
                }

                if ($login_fabrica == 72) {
                    echo "<td nowrap>$data_nf</td>";
                }

                if (in_array($login_fabrica, array(74))) {
                    echo "<td nowrap>$codigo_posto</td>";
                }

                if(in_array($login_fabrica,array(157,158)) && empty($login_cliente_admin)){
                    echo "<td nowrap align='center'>&nbsp; " . $os_posto_x . "</td>";
                }

                if(in_array($login_fabrica,array(162,165))){
                    echo "<td nowrap>$nome_tecnico</td>";
                }

                if ($login_fabrica == 164) {
                    echo "<td nowrap>$nome_fantasia</td>";
                    echo "<td nowrap>$uf_revenda</td>";
                }

                if(in_array($login_fabrica, array(156))){
                    echo "<td nowrap><acronym title='Posto: $codigo_posto - $posto_nome' style='cursor: help;'>" . $codigo_posto . " - " . $posto_nome . "</acronym></td>";
                }else{
                    echo "<td nowrap><acronym title='".traduz("Posto").": $codigo_posto - $posto_nome' style='cursor: help;'>" . substr($posto_nome,0,15) . "</acronym></td>";
                }

                if ($login_fabrica == 11 or $login_fabrica == 172){
                    echo "<td nowrap>$situacao_posto</td>";
                }

                if(in_array($login_fabrica, array(156))){

                    $posto_interno_nome = "";

                    if($tipo_posto_interno == "t"){

                        if(strlen($os_reparo) > 0){

                            $sql_posto_interno = "SELECT
                                                        tbl_posto.nome
                                                    FROM tbl_os
                                                    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                                                    INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                                                    WHERE
                                                        tbl_os.os = {$os_reparo}";
                            $res_posto_interno = pg_query($con, $sql_posto_interno);

                            if(pg_num_rows($res_posto_interno) > 0){
                                $posto_interno_nome = pg_fetch_result($res_posto_interno, 0, "nome");
                            }

                        }

                    }

                    echo "<td nowrap> $posto_interno_nome </td>";
                }

                if ($login_fabrica != 104 && strlen($btn_acao_pre_os) == 0){ //HD-3139131
                    echo "<td nowrap>$cidade_posto</td>";
                    echo "<td nowrap align='center'>$estado_posto</td>";
                } 

                    if ($login_fabrica == 164) {

                        $query_cpf_cnpj = "SELECT consumidor_revenda, revenda_cnpj, consumidor_cpf FROM tbl_os WHERE os = $os";

                        $res_cpf_cnpj = pg_query($con, $query_cpf_cnpj);

                        if ( pg_num_rows($res_cpf_cnpj) > 0 ) {

                            $cpf_cnpj = pg_fetch_object($res_cpf_cnpj);

                            if ($cpf_cnpj->consumidor_revenda == 'R' || ($cpf_cnpj->consumidor_revenda == 'C' && $cpf_cnpj->consumidor_cpf == "") ) {
                                echo "<td nowrap align='center'>$cpf_cnpj->revenda_cnpj</td>";
                            } else {

                                echo "<td nowrap align='center'>{$cpf_cnpj->consumidor_cpf}</td>";
                            }
                        } else {

                            echo "<td nowrap align='center'> </td>";
                        }
                    }

                if ($login_fabrica == 52) { /*HD - 4304128*/
                    $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
                    $aux_res = pg_query($con, $aux_sql);
                    $aux_arr = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);

                    if (!empty($aux_arr["pais"])) {
                        $pais_consumidor = $aux_arr["pais"];
                    } else {
                        $pais_consumidor = "";
                    }

                    echo "<td nowrap>$pais_consumidor</td>";
                    unset($aux_sql, $aux_res, $aux_arr);
                }

                if ($login_fabrica == 158) { /*HD - 6286912*/
                    if (strlen($consumidor_nome) == 0 && strlen($revenda_nome) > 0) {
                        $consumidor_nome = $revenda_nome;
                    }

                    echo "<td nowrap><acronym title='Consumidor/Revenda: $consumidor_nome' style='cursor: help;'>&nbsp;" . substr($consumidor_nome,0,15) . "</acronym></td>";
                }

                if ($login_fabrica==2 and $consumidor_revenda=="R" and $consumidor_nome==''){
                    echo "<td nowrap><acronym title='Revenda: $revenda_nome' style='cursor: help;'>&nbsp;" . substr($revenda_nome,0,15) . "</acronym></td>";
                }else if (!isset($novaTelaOs) or $login_fabrica == 35 or $login_fabrica == 164) {
                    if($consumidor_revenda=="C" || strlen($btn_acao_pre_os) > 0 || empty($consumidor_revenda)){
                        echo "<td nowrap rel='$esconde_coluna'>&nbsp;<acronym title='".strtoupper(traduz("Consumidor")).": $consumidor_nome' style='cursor: help;'>";
                        if (strlen($smile) > 0) {
                            echo '<img src="'.$smile.'" border="0" />&nbsp;';
                        }

                        if (in_array($login_fabrica, [30])) {
                            echo $consumidor_nome . "</acronym></td>";
                        } else {
                            echo substr($consumidor_nome,0,15) . "</acronym></td>";
                        }

                    }else{
                        if ($coluna_revenda) {
                                echo "<td nowrap></td>";
						}elseif($login_fabrica ==35) {
                                echo "<td nowrap><acronym title='Revenda: $revenda_nome' style='cursor: help;'>&nbsp;" . substr($revenda_nome,0,15) . "</acronym></td>";
						}elseif($login_fabrica == 164) {
							echo "<td nowrap><acronym title='Revenda: $revenda_nome' style='cursor: help;'>&nbsp;" . substr($revenda_nome,0,15) . "</acronym></td>";
						}
                    }
                }

                if($consumidor_revenda == "C"){
                    $tipo_consumidor_revenda = traduz("Consumidor");
                }elseif($consumidor_revenda == "R"){
                    $tipo_consumidor_revenda = traduz("Revenda");
                }

                if (in_array($login_fabrica,[152,174,180,181,182])) /*HD - 4379163*/ {
                    echo "<td nowrap><acronym title='$consumidor_nome' style='cursor: help;'>". substr($consumidor_nome, 0, 50) . "</acronym></td>";
                }

                if (in_array($login_fabrica, array(169, 170))) {
                    echo "<td nowrap>&nbsp;<acronym title='REVENDA: $nome_revenda' style='cursor: help;'>".$nome_revenda."</acronym></td>";
                    echo "<td nowrap align='center'>$revenda_cnpj_2</td>";
                    echo "<td nowrap>&nbsp;<acronym title='CONSUMIDOR: $nome_consumidor' style='cursor: help;'>".$nome_consumidor."</acronym></td>";
                }

                if ($login_fabrica == 175){
                    echo "<td nowrap>&nbsp;<acronym title='$tipo_consumidor_revenda: $consumidor_nome' style='cursor: help;'>".$consumidor_nome."</acro      nym></td>";
                }

                if(((!empty($sua_os) && strstr($sua_os, "-")) or $consumidor_revenda=='R') and empty($novaTelaOs) && $login_fabrica != 19){
                    // HD-4369591
                    if($login_fabrica == 80 && !empty($consumidor_nome)){
                        echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>&nbsp;" . substr($consumidor_nome,0,15) . "</acronym></td>";
                    }else{
                        echo "<td nowrap><acronym title='Revenda: $revenda_nome' style='cursor: help;'>&nbsp;" . substr($revenda_nome,0,15) . "</acronym></td>";
                    }
                }

                if (in_array($login_fabrica,[131, 138, 146, 147, 148, 149, 151, 157, 160, 161, 163, 165, 167,171, 173, 176, 177, 179, 183, 203]) or $replica_einhell) {
					if ($consumidor_revenda == "R") {
						echo "<td nowrap><acronym title='Revenda: $revenda_nome' style='cursor: help;'>&nbsp;" . substr($revenda_nome,0,15) . "</acronym></td>";
					} else {
						echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>&nbsp;" . substr($consumidor_nome,0,15) . "</acronym></td>";
					}
				}else if ($login_fabrica == 178){
                    echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>&nbsp;" . substr($consumidor_nome,0,15) . "</acronym></td>";
                }

                if (in_array($login_fabrica, array(169,170))){
                    echo "<td nowrap>{$consumidor_bairro}</td>";
                    echo "<td nowrap>{$consumidor_cidade}</td>";
                }

                if($login_fabrica == 141){ //HD -2386867
                        if($consumidor_revenda == "R" ){
                            $uf_consumidor = pg_fetch_result($res, $i, 'estado');
                        }else{
                            $uf_consumidor = $consumidor_estado;
                        }

						$uf_posto = pg_fetch_result($res,$i,'contato_estado');
                    echo "<td nowrap>$uf_consumidor</td>";
                    echo "<td nowrap>$uf_posto</td>";

                }

                if(in_array($login_fabrica, array(1,3,6,11,15,19,20,24,30,35,40,42,45,50,52,72,74,80,81,85,86,88,90,91,94,96,98,101,106,114,117,122,123,124,127, 172))) {
                    echo "<td nowrap rel='$esconde_coluna' >&nbsp;<acronym title='Telefone: $consumidor_fone' style='cursor: help;'>&nbsp;" .
                    $consumidor_fone. "</acronym></td>";
                }
                if(!in_array($login_fabrica, array(1,3,6,11,15,19,20,24,30,35,40,42,45,50,52,72,74,80,81,85,86,88,90,91,94,96,98,101,106,114,117,122,123,124,127, 172))) {
                    echo "<td nowrap>&nbsp;<acronym title='NF: $nota_fiscal' style='cursor: help;'>$nota_fiscal</acronym></td>";
                }

                if(in_array($login_fabrica, array(156))){

                    if($tipo_posto_interno == "t"){

                        $nf_remessa = "";

                        $sql_nf_remessa = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";
                        $res_nf_remessa = pg_query($con, $sql_nf_remessa);

                        if(pg_num_rows($res_nf_remessa) > 0){

                            $campos_adicionais = pg_fetch_result($res_nf_remessa, 0, "campos_adicionais");
                            if(strlen($campos_adicionais) > 0){

                                $campos_adicionais = json_decode($campos_adicionais, true);

                                if(isset($campos_adicionais["nf_envio"])){
                                    $nf_remessa = $campos_adicionais["nf_envio"];
                                }

                            }

                        }

                    }

                    echo "<td nowrap>$data_nf</td>";
                    echo "<td nowrap> $nf_remessa </td>";
                }

                echo ($login_fabrica==3 OR $login_fabrica == 86 or $multimarca == 't' ) ? "<td nowrap>&nbsp;$marca_nome</td>" : "";//TAKASHI HD925
                echo ($login_fabrica == 52) ? "<td nowrap>&nbsp;$marca_logo_nome</td>" : "";//Tobias

                $produto = ($login_fabrica ==11 or $login_fabrica == 172) ? $produto_referencia : $produto_referencia . " - " . $produto_descricao; # hd 74587

                if ($login_fabrica == 80) {
                    echo "<td nowrap>$data_nf</td>";
                }


                if($login_fabrica == 138){
                    $produto = $produto_referencia;
                    $nf             = pg_fetch_result($res, $i, 'nf_fat');
                    $data_nf        = trim(pg_fetch_result($res,$i,'data_nf'));
                    $data_nf_fat    =   pg_fetch_result($res, $i, 'nf_emissao');

                    echo "<td nowrap>$data_nf</td>";
                    echo "<td nowrap rel='$esconde_coluna' >&nbsp;<acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>&nbsp;" . substr($produto,0,20) . "</acronym></td>";
                    echo "<td nowrap>&nbsp;" . $serie . "</td>";
                    echo '<td nowrap>' . $nf . '</td>';
                    echo '<td nowrap>' . $data_nf_fat . '</td>';
                }

                if ($login_fabrica == 158) {
                    echo "<td nowrap >$familia_produto</td>";
                }

                if($login_fabrica <> 138){
                    echo "<td nowrap rel='$esconde_coluna' >&nbsp;<acronym title='".traduz("Referência").": $produto_referencia \n".traduz("Descrição").": $produto_descricao \n".traduz("Voltagem").": $produto_voltagem' style='cursor: help;'>&nbsp;" . (in_array($login_fabrica, [30]) ? $produto : substr($produto,0,20)). "</acronym></td>";
                }

				if ($login_fabrica == 171) {
                    echo "<td nowrap>{$produto_referencia_fabrica}</td>";
                }
                if ($login_fabrica == 94) {
                    echo "<td nowrap>{$defeito_reclamado_os}</td>";
                    echo "<td nowrap>{$defeito_constatado_desc}</td>";
                }

                if ($login_fabrica == 3) {
                    $aux_sql = "SELECT tbl_tipo_atendimento.descricao FROM tbl_tipo_atendimento JOIN tbl_os ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento AND tbl_os.fabrica = $login_fabrica WHERE tbl_os.os = $os";
                    $aux_res = pg_query($con, $aux_sql);
                    $aux_val = pg_fetch_result($aux_res, 0, 'descricao');

                    echo "<td nowrap>".utf8_decode($aux_val)."</td>";
                }

                if($login_fabrica == 165){
                     echo "<td nowrap>$produto_trocado</td>";
                     echo "<td nowrap>$label_defeito_constato</td>";
                }

                if(in_array($login_fabrica, array(152,180,181,182))){
                    if($sem_listar_peca <> 1){
                        echo "<td nowrap>&nbsp;<acronym title='".traduz("Descrição Peça").": $peca_descricao' style='cursor: help;'>$peca_descricao</acronym></td>";
                        echo "<td nowrap>&nbsp;<acronym title='".traduz("Quantidade Peça").": $peca_qtde' style='cursor: help;'>$peca_qtde</acronym></td>";
                        echo "<td nowrap>&nbsp;<acronym title='".traduz("Código Peça").": $peca_referencia' style='cursor: help;'>$peca_referencia</acronym></td>";
                    }

                    unset($label_classificacao_nao_usar);

                    $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
                    $aux_res = pg_query($con, $aux_sql);
                    $aux_add = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);

                    if (empty($aux_add["classificacao"])) {
                        $label_classificacao_nao_usar = "";
                    } else {
                        $classificacoes = $aux_add["classificacao"];
                        $aux_label_classificacao = array();

                        foreach ($classificacoes as $classificacao_os) {
                            switch ($classificacao_os) {
                                case 'tecnico':
                                    $aux_label_classificacao[] = traduz("Técnico");
                                break;

                                case 'logistico':
                                    $aux_label_classificacao[] = traduz("Logístico");
                                break;

                                case 'comercial':
                                    $aux_label_classificacao[] = traduz("Comercial");
                                break;

                                default:
                                    $aux_label_classificacao[] = "";
                                break;
                            }
                        }

                        $label_classificacao_nao_usar = implode(", ", $aux_label_classificacao);
                    }

                    echo "<td nowrap>&nbsp;<acronym title='".traduz("Classificação").": $label_classificacao_nao_usar' style='cursor: help;'>$label_classificacao_nao_usar</acronym></td>";
                    echo "<td nowrap>&nbsp;<acronym title='$aux_obs' style='cursor: help;'>". substr($aux_obs, 0, 15) ."</acronym></td>";
                }

                if(in_array($login_fabrica, array(85))){

                    if(!empty($os)){

                        $sql_dias_aberto = "SELECT data_abertura::date - CASE WHEN data_fechamento::date IS NULL THEN DATE(NOW()) ELSE data_fechamento::date END AS dias_aberto FROM tbl_os WHERE os = {$os}";
                        $res_dias_aberto = pg_query($con, $sql_dias_aberto);

                        if(pg_num_rows($res_dias_aberto) == 1){
                            $dias_aberto = pg_fetch_result($res_dias_aberto, 0, 'dias_aberto');
                            $dias_aberto = str_replace("-", "", $dias_aberto." dia(s)");
                        }

                    }

                    echo "<td nowrap align='center'>$dias_aberto</td>";
                }

                if ($login_fabrica == 145 && strlen($btn_acao_pre_os) > 0) {

                    if (!empty($os)) {
                        $dias_aberto = traduz("Pré-OS Atendida");
                    } else {
                        $sql_dias_aberto = "SELECT date(now())::date - data::date AS dias_aberto FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado} AND fabrica = {$login_fabrica}";
                        $res_dias_aberto = pg_query($con, $sql_dias_aberto);

                        if(pg_num_rows($res_dias_aberto) == 1){
                            $dias_aberto = pg_fetch_result($res_dias_aberto, 0, 'dias_aberto');
                            $dias_aberto = str_replace("-", "", $dias_aberto." dia(s)");
                        }
                    }

                    echo "<td nowrap align='center'>$dias_aberto</td>";
                }

                if($login_fabrica == 131){ // HD-2181938

                    $digitacaoItem = '';
                    $sqlDigitacao = "SELECT TO_CHAR(tbl_os_item.digitacao_item,'DD/MM/YYYY') AS digitacao_item,
                          TO_CHAR(tbl_pedido_item.data_item,'DD/MM/YYYY')  as digitacao_pedido, tbl_faturamento.nota_fiscal,  TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') as emissao_nf
                        FROM tbl_os_item
                        JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                        left join tbl_pedido_item on tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                        left join tbl_faturamento_item on tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item
                        left join tbl_faturamento on tbl_faturamento_item.faturamento = tbl_faturamento.faturamento and tbl_faturamento.fabrica = $login_fabrica
                        WHERE tbl_os_produto.os = $os
                        AND tbl_os_item.fabrica_i = $login_fabrica
                        ORDER BY digitacao_item DESC LIMIT 1";
                    $resDigitacao = pg_query($con, $sqlDigitacao);

                    $digitacaoItem = "";
                    $dt_geracao_pedido = "";
                    $nf_peca = "";
                    $emissao_nf = "";

                    if(pg_num_rows($resDigitacao) > 0){
                        $digitacaoItem = pg_fetch_result($resDigitacao, 0, 'digitacao_item');
                        $dt_geracao_pedido = pg_fetch_result($resDigitacao, 0, 'digitacao_pedido');
                        $nf_peca = pg_fetch_result($resDigitacao, 0, 'nota_fiscal');
                        $emissao_nf = pg_fetch_result($resDigitacao, 0, 'emissao_nf');
                    }

                    $dataReprova = '';
                    $sqlReprovada = "SELECT TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') AS data_intervencao,
                            tbl_os_status.status_os
                            FROM tbl_os_status
                            WHERE os = $os
                            AND tbl_os_status.status_os = 203
                            AND tbl_os_status.fabrica_status = $login_fabrica
                            ORDER BY data DESC
                            LIMIT 1";
                    $resReprovada = pg_query ($con,$sqlReprovada);
                    if (pg_num_rows ($resReprovada) > 0){
                        $dataReprova = trim(pg_fetch_result($resReprovada,0,'data_intervencao'));
                    } else {
						$sqlReprovada = "SELECT TO_CHAR(reprovada, 'DD/MM/YYYY') AS data_reprova
							FROM tbl_auditoria_os
							WHERE os = $os
							AND reprovada IS NOT NULL
							ORDER BY reprovada DESC LIMIT 1";
						$resReprovada = pg_query($con, $sqlReprovada);

						if (pg_num_rows($resReprovada) > 0) {
							$dataReprova = pg_fetch_result($resReprovada, 0, 'data_reprova');
						}
					}

                    $query_adicionais = "SELECT campos_adicionais
                                         FROM tbl_os_campo_extra
                                         WHERE os = $os";

                    $res_adicionais = pg_query($con, $query_adicionais);

                    $campos_adicionais = pg_fetch_result($res_adicionais, 0, campos_adicionais);

		    $campos_adicionais = json_decode($campos_adicionais);
		    $previsao_entrega = $campos_adicionais->previsao_entrega;
		    $previsao_entrega = (strlen($previsao_entrega) > 0) ? date('d/m/Y',strtotime($previsao_entrega)) : "";

                    /*$dataAprovacao = '';

                    $sqlAprovada = "SELECT TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') AS data_intervencao,
                            tbl_os_status.status_os
                            FROM tbl_os_status
                            WHERE os = $os
                            AND tbl_os_status.status_os = 204
                            AND tbl_os_status.fabrica_status = $login_fabrica
                            ORDER BY os_status DESC
                            LIMIT 1";
                    $resAprovada = pg_query ($con,$sqlAprovada);
                    if (pg_num_rows ($resAprovada) > 0){
                        $dataAprovacao = trim(pg_fetch_result($resAprovada,0,'data_intervencao'));
                    }*/

                    //echo "<td nowrap>".$digitacaoItem."</td>";
                    echo "<td nowrap>".$dataReprova."</td>";
                    //echo "<td nowrap>".$dataAprovacao."</td>";
                    //echo "<td nowrap>".$peca_referencia_descricao."</td>";
                    echo "<td nowrap>".$digitacaoItem."</td>";
                    echo "<td nowrap>".$dt_geracao_pedido."</td>";
                    echo "<td nowrap>".$emissao_nf."</td>";
                    echo "<td nowrap>".$previsao_entrega."</td>";
                }

                if($login_fabrica == 137 || $login_fabrica == 164) echo "<td nowrap rel='esconde_coluna'>$defeito_constatado</td>";

                if($login_fabrica == 164) {
                    echo "<td nowrap rel='esconde_coluna'>$segmento_atuacao</td>";
                }

                echo ($login_fabrica==45 or $login_fabrica == 11 or $login_fabrica == 172) ? "<td nowrap align='center'>$rg_produto</td>" : "";

                    if($login_fabrica == 137){
                        $dados = json_decode($rg_produto);
                        echo "<td nowrap>".$dados->cfop."</td>";
                        echo "<td nowrap>".$dados->vu."</td>";
                        echo "<td nowrap>".$dados->vt."</td>";
                    }

                    if(in_array($login_fabrica, array(143))){
                         echo "<td nowrap>".$rg_produto."</td>";
                    }

                    echo ($login_fabrica==19) ? "<td nowrap>&nbsp; $nome_atendimento </td>" : "";
                    echo ($login_fabrica==19 || $login_fabrica == 94) ? "<td nowrap>$tecnico_nome</td>" : "";
                    echo ($login_fabrica==1) ? "<td nowrap>&nbsp;$aparencia_produto</td>" : "";//TAKASHI HD925
                    echo ($login_fabrica==115 OR $login_fabrica == 116 OR $login_fabrica == 117 OR $login_fabrica == 120) ? "<td align='center' nowrap>".number_format($valor_km,2,',','.')."</td>" : "";
                    if ($login_fabrica == 50 ) echo "<td nowrap rel='esconde_coluna'>$defeito_reclamado_os</td>";
                    if ($login_fabrica ==30 or $login_fabrica == 50 ){
                        if($login_fabrica == 30){
                            if(strlen($consumidor_endereco) > 0){
                                $consumidor_endereco .= ",";
                            }
                            echo "<td nowrap rel='esconde_coluna'>$consumidor_endereco $consumidor_numero</td>";

                            echo "<td nowrap rel='esconde_coluna'>$consumidor_cidade</td>";
                            echo "<td nowrap rel='esconde_coluna' align='center'>$consumidor_estado</td>";
                        }else {
                            echo "<td nowrap rel='esconde_coluna'>$consumidor_endereco</td>";
                            echo "<td nowrap rel='esconde_coluna'>$consumidor_cidade</td>";
                            echo "<td nowrap rel='esconde_coluna'>$consumidor_estado</td>";
                        }
                    }

                    if ($login_fabrica == 50 ) echo "<td nowrap rel='esconde_coluna'>$defeito_constatado</td>";

                    if($login_fabrica == 50){

                        $sql_serie = "SELECT
                                    cnpj,
                                    to_char(data_venda, 'dd/mm/yyyy') as data_venda
                                FROM tbl_numero_serie
                                WHERE serie = trim('$serie')";

                        $res_serie = pg_query ($con,$sql_serie);

                        if (pg_num_rows ($res_serie) > 0) {

                            $txt_cnpj   = trim(pg_fetch_result($res_serie,0,cnpj));
                            $data_venda = trim(pg_fetch_result($res_serie,0,data_venda));

                            $sql_dados_revenda = "SELECT      tbl_revenda.nome              ,
                                                tbl_revenda.revenda           ,
                                                tbl_revenda.cnpj              ,
                                                tbl_revenda.cidade            ,
                                                tbl_revenda.fone              ,
                                                tbl_revenda.endereco          ,
                                                tbl_revenda.numero            ,
                                                tbl_revenda.complemento       ,
                                                tbl_revenda.bairro            ,
                                                tbl_revenda.cep               ,
                                                tbl_revenda.email             ,
                                                tbl_cidade.nome AS nome_cidade,
                                                tbl_cidade.estado
                                    FROM        tbl_revenda
                                    LEFT JOIN   tbl_cidade USING (cidade)
                                    LEFT JOIN   tbl_estado using(estado)
                                    WHERE       tbl_revenda.cnpj ='$txt_cnpj' ";

                            $res_dados_revenda = pg_query ($con,$sql_dados_revenda);


                            if (pg_num_rows ($res_dados_revenda) > 0) {
                                $revenda_nome_1       = trim(pg_fetch_result($res_dados_revenda,0,nome));
                                $revenda_cnpj_1       = trim(pg_fetch_result($res_dados_revenda,0,cnpj));

                                $revenda_bairro_1     = trim(pg_fetch_result($res_dados_revenda,0,bairro));
                                $revenda_cidade_1     = trim(pg_fetch_result($res_dados_revenda,0,cidade));
                                $revenda_fone_1       = trim(pg_fetch_result($res_dados_revenda,0,fone));
                            }

                        }

                        echo "<td nowrap rel='esconde_coluna'>$revenda_nome_1 </td><td nowrap rel='esconde_coluna'> $revenda_cnpj_1 </td><td nowrap rel='esconde_coluna'> $revenda_fone_1 </td><td nowrap rel='esconde_coluna'> $data_venda</td>";
                        echo "<td nowrap rel='esconde_coluna'>$revenda_nome </td><td nowrap rel='esconde_coluna'> $revenda_cnpj_2 </td><td nowrap rel='esconde_coluna'> $nota_fiscal </td><td nowrap rel='esconde_coluna'> $data_nf</td>";
                    }

                    //HD 194732 - Para OSs com extrato não deve ser possível alterar
                    if ($btn_acao_pre_os){
                        $os = 0;
                    }

                    if($reparoNaFabrica){
                        $sql = "SELECT recolhimento from tbl_os_extra where os = {$os}";
                        $resRec = pg_query($con,$sql);
                        if(pg_num_rows($res)>0){
                            $aux_reparo_produto = pg_fetch_result($resRec,0,"recolhimento");
                        }
                    }

                    if($login_fabrica == 1){

                        echo "<td nowrap>";
                        ?>
                        <button type="button" onclick="verifica_pedido_os(<?=$os?>)"><?=traduz('Pedido da OS')?></button>
                        <?php
                        echo "</td>";
                    }

					if($login_fabrica == 104) {
						echo "<td nowrap>$cod_postagem</td>";

					}


                    /*HD - 4096786*/
                    if ($login_fabrica == 101) {
                        $aux_sql = "SELECT data_fechamento, finalizada FROM tbl_os WHERE os = $os";
                        $aux_res = pg_query($con, $aux_sql);

                        $aux_data_fechamento = pg_fetch_result($aux_res, 0, 'data_fechamento');
                        $aux_finalizada      = pg_fetch_result($aux_res, 0, 'finalizada');

                        if (empty($aux_data_fechamento) && empty($aux_finalizada)) {
                            $exibir_troca_ressarcimento = true;
                        } else {
                            $exibir_troca_ressarcimento = false;
                        }
                    }

		    if ( in_array($login_fabrica, [169,170]) ) {
                        $sqlPrev = "SELECT os, to_char(previsao_chegada, 'dd/mm/yyyy') as previsao_chegada
                                    FROM tbl_faturamento
                                    JOIN tbl_os_item USING (pedido)
                                    JOIN tbl_os_produto USING (os_produto)
                                    WHERE fabrica = {$login_fabrica}
                                    AND os = {$os}
                                    GROUP BY os, previsao_chegada";
                        $resPrev = pg_query($con, $sqlPrev);
                        $previsao_chegada = pg_fetch_result($resPrev, 0, previsao_chegada);
                        echo "<td>{$previsao_chegada}</td>";
                    }

                    //HD 194731 - No programa os_cadastro.php estavam na mesma tela as opções de alteração da OS
                    //e de troca da OS, no entanto, em formulários diferentes. Desta forma ao submeter um formulário
                    //as alterações do outro se perdiam. Dentro do programa os_cadastro.php continuam as duas funções
                    //mas agora cada uma é acessada por um botão diferente
                    if($login_fabrica == 96 and $login_cliente_admin != ""){
                    } else {

                        if ( (empty($extrato) && $excluida <>'t' OR (in_array($login_fabrica,array(151,162))) ) AND (empty($login_cliente_admin)) ) { //HD-2732207
                            echo "<td nowrap width='60' id='td_trocar_$i' align='center'>";

                                if ($login_fabrica == 1 && !(in_array($tipo_atendimento ,array(17,18,34,334)))) {
                                    echo "<a href='os_cadastro_troca.php?os=$os&acao=troca' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
                                } else {

                                    if ($login_fabrica == 30 and !$login_cliente_admin) {
                                        $sqlAdmin = "
                                            SELECT  tbl_admin.responsavel_postos, tbl_admin.responsavel_ti
                                            FROM    tbl_admin
                                            WHERE   fabrica = $login_fabrica
                                            AND     admin   = $login_admin;
                                        ";
                                        $resAdmin = pg_query($con,$sqlAdmin);
                                        $cadastra_laudo = pg_fetch_result($resAdmin,0,responsavel_postos);
                                        $responsavel_ti = pg_fetch_result($resAdmin,0,responsavel_ti);

                                        if($responsavel_ti == 't'){
                                            $responsavel_ti_admin = "sim";
                                        }else{
                                            $responsavel_ti_admin = "nao";
                                        }

                                        $sqlTroca = "
                                            SELECT
                                                tbl_os.os AS trocou_os,
                                                tbl_laudo_tecnico_os.observacao,
                                                tbl_laudo_tecnico_os.afirmativa,
                                                tbl_os_troca.admin_autoriza,
                                                tbl_os.status_os_ultimo
											FROM tbl_laudo_tecnico_os
											INNER JOIN tbl_os USING(os)
											LEFT JOIN tbl_os_troca USING(os)
                                            WHERE
                                                tbl_os.os = $os
                                            ORDER BY tbl_laudo_tecnico_os.laudo_tecnico_os DESC
                                            LIMIT 1";
                                        // echo nl2br($sqlTroca);
                                        $resTroca         = pg_query($con,$sqlTroca);

                                        $trocou_os        = pg_fetch_result($resTroca, 0, "trocou_os");
                                        $admin_autoriza   = pg_fetch_result($resTroca, 0, "admin_autoriza");
                                        $status_os_ultimo = pg_fetch_result($resTroca, 0, "status_os_ultimo");
                                        $observacao       = json_decode(pg_fetch_result($resTroca, 0, "observacao"), true);
                                        $afirmativa       = pg_fetch_result($resTroca, 0, "afirmativa");

                                        $tipo_laudo_esmaltec = $observacao['laudo'];

                                        if((($cadastra_laudo == 't' || $login_privilegios == '*') && strlen($trocou_os) == 0) || $afirmativa == 'f' /* $status_os_ultimo == 194 */ ){
                                            echo "<a href='os_cadastro.php?os=$os&osacao=trocar' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
                                        }elseif (strlen(trim($admin_autoriza)) == 0){
                                            echo "<a href='cadastro_laudo_troca.php?alterar=$trocou_os&admin=$responsavel_ti_admin&laudo=$tipo_laudo_esmaltec' target='_blank'>Alterar Laudo</a>";
                                        }

                                    } else if (in_array($login_fabrica, array(101,141,144))) {
                                        $select_troca_produto = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND status_os IN(192,193,194) ORDER BY data DESC LIMIT 1";
                                        $res_troca_produto    = pg_query($con, $select_troca_produto);

                                        if (!(pg_num_rows($res_troca_produto) > 0 && in_array(pg_fetch_result($res_troca_produto, 0, "status_os"), array(192,193))) || in_array($login_fabrica, array(101))) {
                                            /*HD - 4096786*/
                                            if (($login_fabrica == 101 && $exibir_troca_ressarcimento == true) || !in_array($login_fabrica, array(101))) {
                                                echo "<img border='0' src='imagens_admin/btn_trocar_$botao.gif' onclick='solicitaTroca($os, this)' >";
                                            };
                                        }
                                    } else if (isset($novaTelaOs)) {
                                        if (in_array($login_fabrica, array(145))) {
                                            if ($grupo_atendimento != "R") {
                                                $select_troca_produto = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND status_os IN(192,193,194) ORDER BY data DESC LIMIT 1";
                                                $res_troca_produto    = pg_query($con, $select_troca_produto);

                                                if (!(pg_num_rows($res_troca_produto) > 0 && in_array(pg_fetch_result($res_troca_produto, 0, "status_os"), array(192,193)))) {
                                                    echo "<a href='os_troca_subconjunto.php?os={$os}' target='_blank' ><img src='imagens_admin/btn_trocar_{$botao}.gif' /></a>";
                                                }
                                            }
                                        }else if(in_array($login_fabrica, array(147))) {
                                                $select_produto = "SELECT produto from tbl_os where os = {$os} and fabrica = {$login_fabrica}";
                                                $res_produto    = pg_query($con, $select_produto);

                                                if ((pg_num_rows($res_produto) > 0 && !in_array(pg_fetch_result($res_produto, 0, "produto"), array(234103)))) {
                                                    echo "<a href='os_troca_subconjunto.php?os={$os}' target='_blank' ><img src='imagens_admin/btn_trocar_{$botao}.gif' /></a>";
                                                }
                                        } else {

                                            if($login_fabrica == 151){
                                                if(strlen(trim($extrato))==0){
                                                    echo "<a href='os_troca_subconjunto.php?os={$os}' target='_blank' ><img src='imagens_admin/btn_trocar_{$botao}.gif' /></a>";
                                                }
                                            }else{
                                                if($login_fabrica == 148){ //hd_chamado=3049906
                                                    if($status_cancelada <> 't'){
                                                        echo "<a href='os_troca_subconjunto.php?os={$os}' target='_blank' ><img src='imagens_admin/btn_trocar_{$botao}.gif' /></a>";
                                                    }
                                                }else if (empty($fechamento) && !in_array($login_fabrica, [174]) || (in_array($login_fabrica, [174]) && !in_array($status_checkpoint, [40]))) {
                                                    echo "<a href='os_troca_subconjunto.php?os={$os}' target='_blank' ><img src='imagens_admin/btn_trocar_{$botao}.gif' /></a>";
                                                }
                                            }
                                        }
                                    } else if ($login_fabrica <> 30) {
                                        if ($login_fabrica == 52) {
                                            if (strlen($finalizada) == 0) {
                                                echo "<a href='os_cadastro.php?os=$os&osacao=trocar' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
                                            }
                                        } else {

                                            if ($login_fabrica == 155) { //hd_chamado=2598734
                                                $sqlAuditoria = "SELECT status_os
                                                            FROM tbl_os_status
                                                            WHERE tbl_os_status.os = $os
                                                            AND   tbl_os_status.fabrica_status= $login_fabrica
                                                            AND   status_os IN (70,19)
                                                        ORDER BY os_status DESC LIMIT 1";
                                                $resAuditoria = pg_query($con,$sqlAuditoria);

                                                $status_atual = "";
                                                if(pg_num_rows($resAuditoria) > 0){
                                                    $status_atual = pg_fetch_result($resAuditoria, 0, 'status_os');
                                                }

                                                if($status_atual == 19 OR $status_atual == '' ){
                                                    echo "<a href='os_cadastro.php?os=$os&osacao=trocar' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
                                                }

                                            } else {
                                                if (in_array($login_fabrica, array(11,172))) {

                                                    if (strlen($_POST["btn_acao_pre_os"]) == 0) {
                                                        echo "<a href='os_cadastro.php?os=$os&osacao=trocar' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
                                                    }

                                                } else {
                                                    if (in_array($tipo_atendimento ,array(17,18,334))) {
                                                        echo "&nbsp;";
                                                    } else {
                                                        if($login_fabrica == 3){
                                                            if($cancelada != "t"){
                                                                echo "<a href='os_cadastro.php?os=$os&osacao=trocar' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
                                                            }
                                                        }else{
															if ($login_fabrica == 19) {
																$sql_import = "SELECT os FROM tbl_os WHERE os = $os AND importacao_fabrica IS NULL";
																$qry_import = pg_query($con, $sql_import);

																if (pg_num_rows($qry_import) > 0) {
																	echo "<a href='os_cadastro.php?os=$os&osacao=trocar' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
																}
															} else {
																echo "<a href='os_cadastro.php?os=$os&osacao=trocar' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
															}
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            echo "</td>";

                        } elseif ( empty($login_cliente_admin)) {  // RETIRADO - HD 410675 - Nao pode trocar OS em extrato.. pois gerou varios problemas
                            //Mesmo se a OS estiver finalizada pode fazer a TROCA novamente
                            if($login_fabrica==3 and strlen($btn_acao_pre_os) == 0 || $login_fabrica == 162){
                                echo "<td nowrap width='60' align='center'>&nbsp;";
                                echo "<a href='os_cadastro.php?os=$os&osacao=trocar' target='_blank'><img border='0' src='imagens_admin/btn_trocar_$botao.gif'></a>";
                                echo "</td>";
                            }else if (!in_array($login_fabrica, array(139))) {
                                    echo "<td nowrap></td>";
                            }else if(in_array($login_fabrica, array(145)) && strlen($btn_acao_pre_os) > 0){
                                echo "<td nowrap><a href='direcionar_pre_os.php?hd_chamado=$hd_chamado' rel='shadowbox;height=450;width=800'><button type='button'>Direcionar Pré-OS</button></a></td>";
                            }
                        }


                        // FIM BTN TROCAR

                        /*HD-4096786*/
                        if ($login_fabrica == 101){
                            echo "<td nowrap width='60' align='center'>&nbsp;";
                            if ($exibir_troca_ressarcimento == true) {
                                echo "<a href='ressarcimento_cadastro.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_ressarcimento.jpg'></a>";
                            }
                            echo "</td>";
                        }

                        // BTN ALTERAR
                        if (strlen($btn_acao_pre_os)==0 && empty($login_cliente_admin)) {

                            echo "<td nowrap width='60' id='td_alterar_$i' align='center'>";
                            if($excluida <>'t'){
                                if (empty($extrato) && $login_fabrica != 162) {
                                    if ($login_fabrica == 1 && in_array($tipo_atendimento, [17,18,35])) {
                                        echo "<a href='os_cadastro_troca.php?os=$os&acao=alterar' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                    } else {
                                        if (($login_fabrica == 11 or $login_fabrica == 172) AND strlen($finalizada) > 0){
                                            echo "<a href='os_cadastro.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                        } else {
                                            if ($login_fabrica == 30 and !$login_cliente_admin){
                                                echo "<a href='os_cadastro.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                            } else if ($login_fabrica <> 30) {
                                                if (isset($novaTelaOs) || in_array($login_fabrica, array(52))) {

                                                    if(in_array($login_fabrica,array(145,152,180,181,182))){
                                                        if($grupo_atendimento == "R"){
                                                            echo "<a href='cadastro_os_revisao.php?os_id=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                        }elseif($grupo_atendimento == "A"){
                                                            if($login_fabrica == 145){
                                                                echo "<a href='cadastro_os_entrega_tecnica.php?os_id=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                            }
                                                        }else if (empty($fechamento)) {
                                                            $sql_troca_produto = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND status_os IN(192,193,194) ORDER BY data DESC LIMIT 1";
                                                            $res_troca_produto = pg_query($con, $sql_troca_produto);

                                                            $status_troca_produto = pg_fetch_result($res_troca_produto, 0, "status_os");

                                                            $sql_troca_peca = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND status_os IN(199,200,201) ORDER BY data DESC LIMIT 1";
                                                            $res_troca_peca = pg_query($con, $sql_troca_peca);

                                                            $status_troca_peca = pg_fetch_result($res_troca_peca, 0, "status_os");

                                                            if ($status_troca_produto != 194 && $status_troca_peca != 201) {
                                                                echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                            }
                                                        }
                                                    }else{
                                                        if($login_fabrica == 52){
                                                            if(strlen($finalizada) == 0){
                                                                echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                            }
                                                        }else{
                                                            if($login_fabrica == 148){ //hd_chamado=3049906
                                                                if($status_cancelada <> 't'){
                                                                    echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                                }
                                                            }else if (empty($fechamento)) {
                                                                echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    if($login_fabrica == 3){
                                                        if($cancelada != 't'){
                                                            echo "<a href='os_cadastro.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                        }
                                                    }else{
														if ($login_fabrica == 19) {
															$sql_import = "SELECT os FROM tbl_os WHERE os = $os AND importacao_fabrica IS NULL";
															$qry_import = pg_query($con, $sql_import);

															if (pg_num_rows($qry_import) > 0) {
																echo "<a href='os_cadastro.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
															}
														} else {
                                                            if ($tipo_atendimento <> 35 || $login_fabrica <> 1) {
															    echo "<a href='os_cadastro.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                                            }
														}
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    if ($login_fabrica == 162) {
                                        echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
                                    }
                                }
                            }
                            echo "</td>\n";
                        }
                        // FIM BTN ALTERAR

                    }

                    if (in_array($login_fabrica, [169,170])) { ?>
                        <td width="60" align="center" nowrap>
                            <?php if (!empty($finalizada) && empty($os_posto_x) && $excluida != 't') { ?>
                                <button rel="shadowbox" style="font-size:10px;white-space:nowrap;" onclick="shadowAlterarSerie(<?= $os; ?>);"><?=traduz('Alterar Série')?></button>
                            <?php } ?>
                        </td>
                    <?php }

                    /**
                     * - BOTÃO ZERAR MO - COLORMAQ -
                     */
                    if ($login_fabrica == 50) { ?>
                        <td width="60" align="center" nowrap>
                            <?php
                            if ($status_os != 81 && empty($extrato)) { ?>
                                <button id="zerar_mo_<?=$os?>"><?=traduz('Zerar MO')?></button>
                            <?php
                            } ?>
                        </td>
                    <?php
                    }

                    // BTN CONSULTAR
                    if (strlen($btn_acao_pre_os)==0) {
                        echo "<td nowrap width='60' align='center'>";
                        if ($login_fabrica == 145 && $grupo_atendimento == "R") {
                            echo "<a href='os_press_revisao.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consultar_$botao.gif'></a>";
                        } else {
                            echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consultar_$botao.gif'></a>";
                        }
                        echo "</td>\n";
                        if ($cancelaOS && empty($finalizada)) {

                            if ($excluida != "t") {
                                echo "
                                    <td nowrap>
                                        <img src='imagens/btn_cancelar.gif' onclick='cancelar_os({$os}, $(this));' style='cursor: pointer;' />
                                    </td>
                                ";
                            } else {

                                echo "
                                    <td nowrap>
                                        <img src='imagens/btn_reabriros.gif' onclick='reabrir_os({$os}, $(this));' style='cursor: pointer;' />
                                    </td>
                                ";
                            }
                        } elseif (!in_array($login_fabrica, [30,173])) {
                            echo "<td nowrap></td>";
                        }

                        if(in_array($login_fabrica,array(30,91)) && !empty($finalizada)){
                            $sqlExtrato = "SELECT 
                                            extrato
                                            FROM tbl_os_extra
                                            WHERE i_fabrica = {$login_fabrica}
                                            AND os = {$os}
                                            AND extrato IS NULL";                          

                            $resExtrato = pg_query($con, $sqlExtrato);

                            if(pg_num_rows($resExtrato) > 0) {
                                echo "
                                    <td nowrap>                                    
                                        <img src='imagens/btn_reabriros.gif' onClick='reabrir_os({$os}, $(this));' style='cursor: pointer;' />
                                    </td>
                                ";
                            } else {
                                echo "<td nowrap>&nbsp;</td>";
                            }
			}else if($login_fabrica == 91){
				echo "<td nowrap>&nbsp;</td>";
			    }

                        if(in_array($login_fabrica,array(104,153,161,178,183))){
                        ?>
                            <td nowrap align='center'>
                                <?php
                                if($status_checkpoint <> 9){
                                    if (in_array($login_fabrica, array(178,183))){
                                    ?>
                                        <a href="fechamento_os.php?sua_os=<?=($login_fabrica == 178) ? "$sua_os" : "$os"?>&btn_acao=submit" target="_blank">
                                            <img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'>
                                        </a>
                                    <?php       
                                    }else{
                                ?>
                                        <input type='button' id='btn_finaliza_<?=$i?>' value='Finalizar OS' onClick='finalizaOsTron("<?=$os?>",<?=$i?>,<?=$login_fabrica?>)'>
                                <?php
                                    }
                                }
                                ?>
                            </td>
                        <?php
                        }
                        if (in_array($login_fabrica, [158]) && empty($login_cliente_admin)) {
                            echo "<td nowrap align='center'>";
                                if($status_checkpoint <> 9 and empty($finalizada)){
                                    $dataIniAten = "";
                                    $dataConserto = "";
                                    if (!empty($data_inicio_atendimento)) {
                                        list($data, $hora)     = explode(" ", $data_conserto);
                                        list($ano, $mes, $dia) = explode("-", $data);

                                        $dataConserto = "$dia/$mes/$ano $hora";
                                    }
                                    if (!empty($data_inicio_atendimento)) {

                                        list($dataI, $horaI)     = explode(" ", $data_inicio_atendimento);
                                        list($anoI, $mesI, $diaI) = explode("-", $dataI);

                                        $dataIniAten = "$diaI/$mesI/$anoI $horaI";
                                    }

                                    if($tipo_atendimento != 'Piso'){
                                        echo "<input type='button' id='btn_finaliza_$i' value='Finalizar OS' onClick='finalizaOs(\"{$os}\",\"{$i}\",\"{$dataConserto}\",\"{$dataIniAten}\")'>";
                                    }
                                }
                            echo "</td>\n";
                        }
                        if($login_fabrica == 96 AND !empty($status_checkpoint)){ //HD391024
                            if($status_checkpoint == 5){
                                echo "<td nowrap align='center'>&nbsp;";
                                    echo "<input type='button' value='Aprovar' onclick=\"aprovaOrcamento(".$sua_os.",".$os.",'Aprovar')\" id='aprovar_".$os."'>&nbsp;";
                                    echo "<input type='button' value='Reprovar' onclick=\"aprovaOrcamento(".$sua_os.",".$os.",'Reprovar')\" id='reprovar_".$os."'>&nbsp;";
                                    echo "<input type='button' value='Orçamento' onclick=\"window.open('../print_orcamento.php?os=".$os."','Orçamento')\" id='orcamento'>";
                                echo "</td>\n";
                            }elseif($status_checkpoint == 6 OR $status_checkpoint == 7){
                                $status_checkpoint_ant = $status_checkpoint;
                                echo "<td nowrap align='center'>&nbsp;";
                                    echo "<input type='button' value='Orçamento' onclick=\"window.open('../print_orcamento.php?os=".$os."')\" id='orcamento'>";
                                echo "</td>\n";
                            }else{
                                echo "<td nowrap width='160' align='center'>&nbsp;</td>\n";
                            }
                        }
                    }else if(!in_array($login_fabrica, array(139,145,104))) {
                            echo "<td nowrap></td>";
                    }
                    if ( in_array($login_fabrica, [173])) {
                        echo "<td nowrap>  <a class='btn btn-success' href='imprimir_etiqueta.php?imprimir=true&os={$os}' target='_blank'>Imprimir Etiqueta</a></td>";
                    }
                    // FIN BTN CONSULTAR

                    if(in_array($login_fabrica, array(85))){
                        if(empty($hd_chamado)){
                            echo "<td nowrap align='center' id='box_{$i}'>";
                                echo "<button style='white-space: nowrap; font-size: 12px;' onClick='abreAtendimento(\"{$os}\", \"{$i}\")'>Abrir Atendimento</button>";
                            echo "</td>\n";
                        }else{
                            echo "<td nowrap></td>";
                        }
                    }

                    if(($login_fabrica == 11 or $login_fabrica == 172) AND $admin_interventor == 't' ){
                        echo "<td nowrap width='60' align='center'>&nbsp;";
                        if($status_os != 158 AND empty($fechamento)){

                            if(in_array($login_fabrica, array(11,172))){

                                if(strlen($_POST["btn_acao_pre_os"]) == 0){
                                    echo "<input type='button' name='intervencao' class='intervencao' id='$os'  rel='$sua_os' title='Intervenção Departamento Juridico' value=' Bloquear ' style='cursor: pointer' />";
                                }

                            }else{
                                echo "<input type='button' name='intervencao' class='intervencao' id='$os'  rel='$sua_os' title='Intervenção Departamento Juridico' value=' Bloquear ' style='cursor: pointer' />";
                            }

                            echo "</td>\n";

                        }
                    }
                if($login_fabrica == 24 OR $login_fabrica == 74){ //hd_chamado=2588542
                     if (strlen($fechamento) == 0 and strlen($btn_acao_pre_os) == 0){

                        if ($status_cancelada == "t") {
                            echo "<td nowrap align='center' id='box_{$i}'>";
                                echo "<img border='0' src='imagens_admin/descongelar_os.gif' onClick='congelar_os(\"{$os}\", \"{$status_cancelada}\", \"{$i}\")'>";
                            echo "</td>\n";
                        }elseif($congelar == 't'){
                            echo "<td nowrap align='center' id='box_{$i}'>";
                                echo "<img border='0' src='imagens_admin/congelar_os.jpg' onClick='congelar_os(\"{$os}\", \"{$status_cancelada}\", \"{$i}\")' >";
                            echo "</td>\n";
                        }else{
                            if($login_fabrica == 74){
                                echo "<td nowrap align='center' id='box_{$i}'>";
                                    echo "<img border='0' src='imagens_admin/congelar_os.jpg' onClick='congelar_os(\"{$os}\", \"{$status_cancelada}\", \"{$i}\")' >";
                                echo "</td>\n";
                            }else{
                                echo "<td nowrap></td>";
                            }
                        }
                    }else{
                            echo "<td nowrap></td>";
                    }
                }

                    if (($login_fabrica==50 and ($excluida <> 't')) or ($login_fabrica == 14 and $excluida <> 't') or in_array($login_fabrica,array(20,24,66,101,144)) ) {
                        if (strlen($fechamento) == 0 and !in_array($login_fabrica,array(20,24,144)) and strlen($btn_acao_pre_os) == 0) {
                            echo "<td nowrap>&nbsp;<a href='os_item.php?os=$os' target='_blank'>";
                            if($sistema_lingua == "ES"){
                                echo "<img id='lancar_$i' border='0' src='imagens/btn_lanzar.gif'>";
                            }else{
                                // $data_conserto > "03/11/2008" HD 50435
                                $xdata_conserto = fnc_formata_data_pg($data_conserto);

                                $sqlDC = "SELECT $xdata_conserto::date > '2008-11-03'::date AS data_anterior";
                                $resDC = pg_query($con, $sqlDC);
                                if(pg_num_rows($resDC)>0){
                                    $data_anterior = pg_fetch_result($resDC, 0, 0);
                                }

                                echo (($login_fabrica==11 or $login_fabrica == 172) AND strlen($data_conserto)>0 AND $data_anterior == 't') ? "" : "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'>";
                            }
                            echo "</a></td>";
                        }else if(in_array($login_fabrica, array(101))){
                            echo "<td nowrap></td>";
                        }

                        if ($login_fabrica <> 74){

                            echo ((strlen($fechamento) == 0 and strlen($btn_acao_pre_os) == 0) or ($login_fabrica == 20 and strlen($btn_acao_pre_os) == 0)) ? "<td nowrap='' align='center'><a href=\"javascript: if (confirm('Deseja realmente excluir a os $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\">&nbsp; <img id='excluir_$i' border='0' src='imagens/btn_excluir_novo.gif'></a></td>" : "<td nowrap width='60' align='center'> </td>";
                        }else if(in_array($login_fabrica, array(101))){
                            echo "<td nowrap></td>";
                        }

                        if ((strlen($fechamento) == 0 AND $status_os!="62" && $status_os!="65" && $status_os!="72" && $status_os!="87" && $status_os!="116" && $status_os!="120" && $status_os!="122" && $status_os!="126" && $status_os!="140" && $status_os!="141" && $status_os!="143" and !in_array($login_fabrica,array(20,24,74,144)) and strlen($btn_acao_pre_os) == 0)) {
                            echo "<td nowrap align='center'><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif' onclick=\"javascript: if (confirm('Caso a data da entrega do produto para o consumidor nao seja hoje, utilize a opcao de fechamento de os para informar a data correta! confirma o fechamento da os $sua_os com a data de hoje?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"></td>";
                        }else if(in_array($login_fabrica, array(101))){
                            echo "<td nowrap></td>";
                        }

                        if(in_array($login_fabrica, array(101))){

                            echo "<td nowrap align='center'>";

                            if(strlen($fechamento) > 0){

                                echo "<a href='os_item.php?os=$os&reabrir=ok' target='_blank' ><img border='0' $display_button_cancelado src='imagens/btn_reabriros.gif'></a>";

                            }

                            echo "</td>";

                        }

                    }

                    if( $login_fabrica == 30){
                        $sqlInter = "SELECT intervensor FROM tbl_admin WHERE admin = $login_admin";
                        $resInter = pg_query($con,$sqlInter);
                        $adminFechaOs = pg_fetch_result($resInter,0,intervensor);
                        echo "<td nowrap style='vertical-align:middle;'>";
                        if(strlen($fechamento) == 0 && $adminFechaOs == 't'){
                            echo "<img id='sinal_$i' border='0' src='imagens/btn_fecha.gif' onclick=\"javascript: if (confirm('Caso a data da entrega do produto para o consumidor nao seja hoje, utilize a opcao de fechamento de os para informar a data correta! confirma o fechamento da os $sua_os com a data de hoje?') == true) { fechaOS ($os,sinal_$i,excluir_$i, '') ; }\">";
                        }
                        echo "</td>";
                    }

                    if ($fabrica_copia_os_excluida and strlen($btn_acao_pre_os) == 0) { //HD 278885

                        if($login_fabrica == 30){
                            if($excluida == "t"){
                                $botaoExcluir = "imagens/btn_reabriros.gif";
                                $verbo = "reabrir";
                                $acao = "liberar";

                            }else{
                                $botaoExcluir = "imagens/btn_cancelar.gif";
                                $verbo = "cancelar";
                                $acao = "cancelar";
                            }

                            echo "<td nowrap width='60' align='center' id='td_{$os}'>";
                            echo (empty($extrato)) ? "<a href='javascript:void(0);' onclick=\"if (confirm('Deseja realmente $verbo a os $sua_os2?') == true) cancelarOs({$os},'{$acao}');\">
                            <img id='excluir_$i' border='0' src='$botaoExcluir'></a>" : "&nbsp;";
                            echo "</td>";
                        } elseif ( empty($login_cliente_admin) ) {
                            $botaoExcluir = "imagens/btn_excluir_novo.gif";
                            $verbo = "excluir";

                            echo "<td nowrap style='width: 60px;' align='center'>";
                            if($login_fabrica == 148){
                                if($status_cancelada <> 't'){
                                    echo (empty($extrato)) ? "<a href=\"javascript: if (confirm('Deseja realmente $verbo a os $sua_os2?') == true) disp_prompt('$os','$sua_os2');\">
                                    <img id='excluir_$i' border='0' src='$botaoExcluir'></a>" : "&nbsp;";
                                }
                            }else{
                                if ($login_fabrica == 158) {
                                    if ($status_checkpoint == 1) {
                                        echo (empty($extrato) and empty($os_posto_x)) ? "<a href=\"javascript: if (confirm('Deseja realmente $verbo a os $sua_os2?') == true) disp_prompt('$os','$sua_os2');\">
                                           <img id='excluir_$i' border='0' src='$botaoExcluir'></a>" : "&nbsp;";
                                    }
                                } else {
                                    echo (empty($extrato)) ? "<a href=\"javascript: if (confirm('Deseja realmente $verbo a os $sua_os2?') == true) disp_prompt('$os','$sua_os2');\">
                                    <img id='excluir_$i' border='0' src='$botaoExcluir'></a>" : "&nbsp;";
                                }
                            }
                            echo "</td>";
                            if ($login_fabrica == 148) {
                                echo "<td nowrap style='width: 60px;' align='center'>";

                                if ($fechamento == date('d/m/Y')) {
                                    $botaoExcluir = "imagens/btn_reabriros.gif";
                                    $verbo = "reabrir";
                                    $acao = "reabrir";

                                    echo (empty($extrato)) ? "<a href='javascript:void(0);' onclick=\"if (confirm('Deseja realmente $verbo a os $sua_os?') == true) cancelarOs({$os},'{$acao}');\">
                                        <img id='excluir_$i' border='0' src='$botaoExcluir'></a>" : "&nbsp;";
                                }

                                echo "</td>";
                            }
                        }
                    }

                    if (in_array($login_fabrica, [35,178])) {
                        echo "<td nowrap style='width: 60px;' align='center'>";

                        if (!empty($fechamento) AND $excluida != "t") {
                            $botaoExcluir = "imagens/btn_reabriros.gif";
                            $verbo = "reabrir";
                            $acao = "reabrir";

                            echo (empty($extrato)) ? "<a href='javascript:void(0);' onclick=\"if (confirm('Deseja realmente $verbo a os $sua_os?') == true) cancelarOs({$os},'{$acao}');\">
                                <img id='excluir_$i' border='0' src='$botaoExcluir'></a>" : "&nbsp;";
                        }

                        if(in_array($login_fabrica, [35])){                            
                            if ($cancelada == 't' and empty($fechamento)) {
                                $botao = "imagens/btn_reabriros.gif";
                                $verbo = "reverter";
                                $acao = "reverter";
                            
                                echo "<a href='javascript:void(0);' onclick=\"if (confirm('Deseja realmente $verbo (Cancelamento) da os $sua_os?') == true) reverterOs({$os},'{$acao}');\"> Reverter ";
                            }                                
                        
                        }



                        echo "</td>";
                    }

                    if(in_array($login_fabrica, array(72,74))){
                        $botaoExcluir = "";
                        $verbo = "";
                        $acao = "";
                        if($cancelada <> "t"){
                            $botaoExcluir = "imagens/btn_cancelar.gif";
                            $verbo = "cancelar";
                            $acao = "cancelar";
                        }

                        echo "<td nowrap width='60' align='center' id='td_{$os}'>";
                        echo "<a href='javascript:void(0);' onclick=\"if (confirm('Deseja realmente $verbo a os $sua_os2?') == true) cancelarOs({$os},'{$acao}');\">
                        <img id='excluir_$i' border='0' src='$botaoExcluir'></a>" ;
                        echo "</td>";
                    }

                    if( in_array($login_fabrica, array(7, 45)) ){ // HD 31598, 48441 e 940122

                        if( $login_fabrica == 45 and empty($fechamento) ){  // HD 940122 - não mostrar o botão "Lançar Itens" para OS Finalizadas na NKS
                            echo "<td nowrap width='60' align='center'>&nbsp;";
                            echo "<a href='os_item.php?os=$os' target='_blank'><img border='0' src='imagens/btn_lanca_$botao.gif'></a>";
                            echo "</td>\n";
                        }else if( $login_fabrica == 7 ){
                            echo "<td nowrap width='60' align='center'>&nbsp;";
                            echo "<a href='os_item.php?os=$os' target='_blank'><img border='0' src='imagens/btn_lanca_$botao.gif'></a>";
                            echo "</td>\n";


                            echo "<td nowrap width='60' align='center'>&nbsp;";
                            echo "<a href='os_transferencia_filizola.php?sua_os=$sua_os&posto_codigo_origem=$codigo_posto&posto_nome_origem=$posto_nome' target='_blank'><img border='0' src='imagens/btn_transferir_$botao.gif'></a>";
                            echo "</td>\n";

                            echo "<td nowrap width='60' align='center'>&nbsp;";
                            echo ($consumidor_revenda=="R") ? "<a href='os_print_manutencao.php?os_manutencao=$os_numero' target='_blank'>" : "<a href='os_print.php?os=$os' target='_blank'>";//HD 80470
                            echo "<img border='0' src='imagens/btn_imprimir_$botao.gif'></a></td>\n";

                            echo "<td nowrap width='60' align='center'>&nbsp;";
                            echo "<input name='imprimir_$i' type='checkbox' id='imprimir' rel='imprimir' value='".$os."' />";
                            echo "</td>\n";
                        }
                    }

                    if ($login_fabrica == 91 && $vintecincodias == "sim" and strlen($btn_acao_pre_os) == 0) {
                        echo "<td nowrap>
                            <img src='imagens/btn_fecha.gif' name='fechar_os_30_dias_{$i}' rel='{$os}' style='cursor: pointer;' alt='Fechar OS com mais de 30 dias aberta' title='Fechar OS com mais de 30 dias aberta' />
                            <input type='hidden' name='i' value='{$i}' />
                        </td>";
                    }

                    $texto = ($login_fabrica == 145) ? "Pré-OS" : "";

                    if(strlen($btn_acao_pre_os) > 0){
                        $onClick = ($login_fabrica != 137) ? "exclui_hd_chamado($hd_chamado)" : "motivoExclusao($hd_chamado)";
                        echo "<td nowrap> <button type='button' onClick='{$onClick}'>".traduz("Excluir")." $texto</button> </td>";
                    }

                    //zera mo
                    if ($login_fabrica == 101) {
                        echo '<td nowrap align="center" nowrap>';
                        if ($status_os != 81 && $maoDeObra > 0) {
                            echo '<button class="btn-zerar-mo-'.$os.'"  onClick="zerar_mao_obra('.$os.');">'.traduz("Zerar MO").'</button>';
                        }
                        echo '</td>';
                    }

                    if($login_fabrica == 35){
                        echo "<td nowrap>";
                        $sqlV = "
                            SELECT  extrato
                            FROM    tbl_os_extra
                            WHERE   os = $os
                        ";
                        $resV = pg_query($con,$sqlV);
                        $verificaExtrato = pg_fetch_result($resV,0,extrato);

                        if($verificaExtrato == ""){
                            $sqlMO = "
                                SELECT  DISTINCT
                                        status_os
                                FROM    tbl_os_status
                                WHERE   os = $os
                                AND     status_os = 81
                            ";
                            $resMO = pg_query($con,$sqlMO);
                            $status_os_cancela = pg_fetch_result($resMO,0,status_os);
                            if($status_os_cancela == ""){
                                echo "<a href='os_cadastro.php?os=$os&cancela_mao_obra=ok' target='_blank'>".traduz("Cancelar Mão-obra")."</a>";
                            }else{
                                echo "&nbsp;";
                            }
                        }else{
                            echo "&nbsp;";
                        }
                        echo "</td>";

                        /*HD-4206757*/
                        if ($status_checkpoint <> 9) { ?>
                            <td nowrap>
                                <a class='finalizar_os_35' id='btn_finaliza_<?=$i?>' onclick='novoFinalizarOsCadence("<?=$os?>",<?=$i?>,<?=$login_fabrica?>)'><b>Finalizar OS</b></a>&nbsp;
                            </td>
                        <? } else {
                            ?> <td nowrap></td> <?
                        }
                    }

                    if (in_array($login_fabrica, [123,160]) || $replica_einhell) {
                        $sql_sac_tc = " SELECT JSON_FIELD('sacTelecontrol', parametros_adicionais) AS sac_telecontrol
                                        FROM tbl_admin
                                        WHERE admin = $login_admin
                                        AND fabrica = $login_fabrica";
                        $res_sac_tc = pg_query($con, $sql_sac_tc);
                        if (pg_fetch_result($res_sac_tc, 0, 'sac_telecontrol') == "true" && strlen(trim($fechamento)) == 0) {
                            echo "<td nowrap style='vertical-align:middle; background-color: #ffffff'>";
                            echo "<img id='sinal_$i' border='0' src='imagens/btn_fecha.gif' onclick=\"javascript: if (confirm('Deseja Realmente Fechar a OS $os ?') == true) { fechaOSTermo ($os) ; }\">";
                            echo "</td>";
                        } else {
                            echo "<td background-color: #ffffff'></td>";
                        }
                    }

                    echo "</tr>";

                if ($login_fabrica == 7) {
                    echo "<tr>";
                    echo "<td nowrap colspan='11'>";
                    echo "&nbsp;";
                    echo "</td>";
                    echo "<td nowrap colspan='2'>&nbsp;";
                    echo "<a href='javascript:imprimirSelecionados()' style='font-size:10px'>".traduz("Imprime Selecionados")."</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            }
    echo "</tbody>";
    if($telecontrol_distrib OR in_array($login_fabrica, [131,146,156])){
        echo "
                <tfoot>
                    <tr class='titulo_coluna' >
                        <td colspan='100%' style='text-align: left;' >
                            Motivo:
                            <input type='text' id='motivo_exclui_os' name='motivo_exclui_os' style='width: 300px;' form='form_exclui_os' />
                            <button type='button' id='button_exclui_os' name='button_exclui_os' >".traduz("Excluir OS(s)")."</button>
                        </td>
                    </tr>
            </tfoot>";
    }

        echo "</table>";
        } else {
            if (strlen($btn_acao_pre_os) > 0) {
                echo traduz("Não Existem Pré-Ordens de Serviço.");
            } else {
                echo traduz("Nenhuma OS encontrada");
            }
        }

            ##### PAGINAÇÃO - INÍCIO #####
            echo "<br />";
            echo "<div>";

            if($pagina < $max_links){
                $paginacao = $pagina + 1;
            }else{
                $paginacao = $pagina;
            }

            // pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
            @$todos_links       = $mult_pag->Construir_Links("strings", "sim");

            // função que limita a quantidade de links no rodape
            $links_limitados    = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

            for ($n = 0; $n < count($links_limitados); $n++) {
                echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
            }

            echo "</div>";

            $resultado_inicial = ($pagina * $max_res) + 1;
            $resultado_final   = $max_res + ( $pagina * $max_res);
            $registros         = $mult_pag->Retorna_Resultado();

            $valor_pagina   = $pagina + 1;
            $numero_paginas = intval(($registros / $max_res) + 1);

            if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

            if ($registros > 0){
                echo "<br>";
                echo "<div>";
                echo traduz("Resultados de")." <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> ".traduz("registros.");
                echo "<font color='#cccccc' size='1'>";
                echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
                echo "</font>";
                echo "</div>";
            }
            ##### PAGINAÇÃO - FIM #####
            echo "<br />";
			if($_POST['gerar_excel'] == 't') {

				if (in_array($login_fabrica, array(15, 30, 50, 85, 72, 137, 156)) and pg_num_rows($resxls)) { # HD 193344

					flush();

                    $formato_excel = trim($_REQUEST['formato_excel']);
					$data         = date ("d/m/Y H:i:s");
					$arquivo_nome = "consulta-os-$login_admin.$formato_excel";
					#$path         = "/www/assist/www/admin/xls/";
					$path         = "xls/"; // Para teste remover comentário
					$path_tmp     = "/tmp/";

					$arquivo_completo     = $path.$arquivo_nome;
					$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

					echo `rm $arquivo_completo_tmp `;
					echo `rm $arquivo_completo `;

					// Cria um novo relatório
					//$excel = new ExcelWriter($arquivo_completo_tmp);
                    $fp = fopen ($arquivo_completo,"w+");

					switch($login_fabrica) {
					case 30 :
						$titulos = array("OS",
							"N. ATENDIMENTO",
							"ATENDIMENTO CENTRALIZADO",
							"OS REVENDEDOR",
							"STATUS",
							"SITUAÇÃO OS",
							"SÉRIE",
							"AB",
							"DATA PEDIDO",
							"Nº PEDIDO",
							"NOTA FISCAL",
							"CÓDIGO",
							"PEÇA DESCRIÇÃO",
							"QTDE",
							"DC",
							"FC",
							"C / R",
							"POSTO",
							"NOME POSTO",
							"UF",
							"CIDADE",
							"CONSUMIDOR/REVENDA",
							"TELEFONE",
							"NF PRODUTO",
							"PRODUTO",
							"DEFEITO RECLAMADO",
							"END. CONSUMIDOR",
                            "CEP CONSUMIDOR",
                            "BAIRRO CONSUMIDOR",
							"CIDADE CONSUMIDOR",
							"UF CONSUMIDOR",
							"ADMIN",
							"VALOR OS",
							"DEFEITO CONSTATADO"
						);
						break;
					case 50 :
						$titulos = array("OS",
							"N. Atendimento",
							"Usuário",
							"AB",
							"FC",
							"C / R",
							"POSTO",
							"CIDADE",
							"UF",
							"CONSUMIDOR/REVENDA",
							"TELEFONE",
							"PRODUTO",
							"DEFEITO RECLAMADO",
							"END. CONSUMIDOR",
							"CIDADE CONSUMIDOR",
							"UF CONSUMIDOR",
							"DEFEITO CONSTATADO",
							"REVENDA (CLIENTE COLORMAQ)",
							"CNPJ",
							"FONE",
							"DATA DA NF",
							"REVENDA (CONSUMIDOR)",
							"CNPJ",
							"NUMERO DA NF",
							"DATA DA NF"
						);
						break;
					case 85 :
						$titulos = array("OS",
							"SÉRIE",
							"AB",
							"FC",
							"C / R",
							"POSTO",
							"CONSUMIDOR/REVENDA",
							"TELEFONE",
							"PRODUTO",
							"DAIS EM ABERTO");
						break;
					case 137 :
						$titulos = array("OS",
							"DATA DE ABERTURA",
							"CPF/CNPJ",
							"CONSUMIDOR/REVENDA",
							"CIDADE",
							"ESTADO",
							"NOTA FISCAL ENTRADA",
							"NOTA FISCAL SAIDA",
							"CFOP",
							"DATA NOTA ENTRADA",
							"DATA NOTA SAIDA",
							"VALOR UNITARIO PRODUTO",
							"VALOR TOTAL DA NF",
							"PRODUTO",
							"TRANSPORTADORA",
							"NUMERO LOTE",
							"DEFEITO CONSTATADO");
						break;
					case 72 :
                    if($consultar_os_sem_listar_pecas == 't'){
						$titulos = array("OS",
							"COD. PRODUTO",
							"PRODUTO",
							"SÉRIE",
							"DEFEITO RECLAMADO",
                            "DEFEITO CONSTATADO",
							"FALHA EM POTENCIAL",
							"SOLUÇÃO",
							"DATA DA DIGITAÇÃO DA OS",//digitacao OS
							"AB",//abertura OS
							"DC",//conserto OS
							"FC",//fechamento OS
							"NOTA FISCAL DE FATURAMENTO",
							"DATA DO FATURAMENTO",
							"Data NF",//data da nota fiscal
							"C/R",//consumidor ou revenda
							"COD. POSTO",
							"POSTO",
							"CIDADE",
							"UF",
							"CONSUMIDOR/REVENDA",
							"TELEFONE CONSUMIDOR"
						);
                        break;
                    } else {
                        $titulos = array("OS",
                            "COD. PRODUTO",
                            "PRODUTO",
                            "SÉRIE",
                            "DEFEITO RECLAMADO",
                            "DEFEITO CONSTATADO",
                            "FALHA EM POTENCIAL",
                            "SOLUÇÃO",
                            "COD. PEÇA",
                            "PEÇA",
                            "DATA DIGITAÇÃO DA PEÇA NA OS",
                            "DATA DA DIGITAÇÃO DA OS",//digitacao OS
                            "AB",//abertura OS
                            "DC",//conserto OS
                            "FC",//fechamento OS
                            "NOTA FISCAL DE FATURAMENTO",
                            "DATA DO FATURAMENTO",
                            "Data NF",//data da nota fiscal
                            "C/R",//consumidor ou revenda
                            "COD. POSTO",
                            "POSTO",
                            "CIDADE",
                            "UF",
                            "CONSUMIDOR/REVENDA",
                            "TELEFONE CONSUMIDOR"
						); 						
	                    if($login_fabrica == 72){
	                    	array_push($titulos, "NOME DA REVENDA", "CNPJ REVENDA", "STATUS OS");
	                    }
						break; }
					case 145:
						$titulos = array("OS",
							"AB",
							"DC",
							"FC",
							"POSTO",
							"CONSUMIDOR/REVENDA",
							"NF",
							"PRODUTO");
						break;
					case 156 :
						$titulos = array(
							"OS",
							"SÉRIE",
							"AB",
							"FC",
							"STATUS ELGIN",
							"TIPO DE ATENDIMENTO",
							"NOME POSTO",
							"POSTO INTERNO",
							"CIDADE",
							"ESTADO",
							"CONSUMIDOR/REVENDA",
							"NF",
							"DATA NF",
							"NF REMESSA",
							"PRODUTO"
						);
						break;
					default :
						$titulos = array("OS",
							"SÉRIE",
							"AB",
							"DC",
							"FC",
							"POSTO",
							"CONSUMIDOR/REVENDA",
							"TELEFONE",
							"PRODUTO"
                        );
						break;
                    }
                    
					//$excel->writeLine($titulos, "default_title");
                    if ($formato_excel == "xls") {
                        fputs ($fp,"<table align='center' width='100%'><tr>");

                        for ($i = 0; $i <= count($titulos); $i++) {
                            fputs ($fp, "<td><b>{$titulos[$i]}</b></td>");
                        }

                        fputs ($fp,"</tr>");
                    } else {
                        fputs ($fp,implode(";",$titulos)."\n");
                    }

					$old_os = 0;

					if ($login_fabrica == 72) {
						$contador_resxls_excel = pg_num_rows($resxls_excel);

						for($x =0;$x<$contador_resxls_excel;$x++) {
							$sua_os                      = trim(pg_fetch_result($resxls_excel,$x,sua_os));
							$nota_fiscal                 = trim(pg_fetch_result($resxls_excel,$x,nota_fiscal));
							$digitacao                   = trim(pg_fetch_result($resxls_excel,$x,digitacao));
							$abertura                    = trim(pg_fetch_result($resxls_excel,$x,abertura));
							$fechamento                  = trim(pg_fetch_result($resxls_excel,$x,fechamento));
							$finalizada                  = trim(pg_fetch_result($resxls_excel,$x,finalizada));
							$data_conserto               = trim(@pg_fetch_result($resxls_excel,$x,data_conserto));
							$serie                       = trim(pg_fetch_result($resxls_excel,$x,serie));
							$consumidor_nome             = trim(pg_fetch_result($resxls_excel,$x,consumidor_nome));
							$consumidor_fone             = trim(pg_fetch_result($resxls_excel,$x,consumidor_fone));
							$codigo_posto                = trim(pg_fetch_result($resxls_excel,$x,codigo_posto));
							$posto_nome                  = trim(pg_fetch_result($resxls_excel,$x,posto_nome));
							$situacao_posto              = trim(pg_fetch_result($resxls_excel,$i,credenciamento));
							$uf_posto                    = pg_fetch_result($resxls_excel, $x, 'contato_estado');
							$produto_referencia          = trim(pg_fetch_result($resxls_excel,$x,produto_referencia));
							$produto_descricao           = trim(pg_fetch_result($resxls_excel,$x,produto_descricao));
							$produto_voltagem            = trim(pg_fetch_result($resxls_excel,$x,produto_voltagem));
							$consumidor_revenda          = trim(pg_fetch_result($resxls_excel,$x,consumidor_revenda));
							$data_nf                     = trim(pg_fetch_result($resxls_excel,$x,data_nf));
							$revenda_cnpj                = trim(pg_fetch_result($resxls_excel,$x,revenda_cnpj));
							$revenda_nome                = trim(pg_fetch_result($resxls_excel,$x,revenda_nome));
							$status_checkpoint           = trim(pg_fetch_result($resxls_excel,$x,status_checkpoint));
							$defeito_constatado          = trim(pg_fetch_result($resxls_excel,$x,defeito_constatado));
							$rg_produto                  = trim(pg_fetch_result($resxls_excel,$x,rg_produto));
							$defeito_reclamado_descricao = trim(pg_fetch_result($resxls_excel,$x,defeito_reclamado_descricao));
							$defeito_constatado          = trim(pg_fetch_result($resxls_excel,$x,defeito_constatado));
							$solucao_os                  = trim(pg_fetch_result($resxls_excel,$x,solucao_os));
							$contato_estado              = trim(pg_fetch_result($resxls_excel,$x,contato_estado));
							$contato_cidade              = trim(pg_fetch_result($resxls_excel,$x,contato_cidade));
							$pedido                      = pg_fetch_result($resxls_excel, $x, 'pedido');
							$emissao_faturamento         = trim(pg_fetch_result($resxls_excel,$x,emissao));
							$nota_fiscal_faturamento     = trim(pg_fetch_result($resxls_excel,$x,nota_fiscal_faturamento));
							$revenda_cnpj 			     = trim(pg_fetch_result($resxls_excel,$x,revenda_cnpj));
							$revenda_nome 			     = trim(pg_fetch_result($resxls_excel,$x,revenda_nome));
							$descricao_status_checkpoint = trim(pg_fetch_result($resxls_excel,$x,descricao_status_checkpoint));

							$digitacao_item = pg_fetch_result($resxls_excel, $x, 'digitacao_item');
							//$nf = pg_fetch_result($resxls_excel, $x, 'nf_fat') . ' ' . pg_fetch_result($resxls_excel, $x, 'nf_emissao');
							$peca_referencia = pg_fetch_result($resxls_excel, $x, 'peca_referencia');
							$peca_descricao = pg_fetch_result($resxls_excel, $x, 'peca_descricao');


							$sql_revenda = "SELECT tbl_cidade.nome, tbl_cidade.estado
								FROM tbl_cidade
								JOIN tbl_revenda ON tbl_revenda.cidade = tbl_cidade.cidade
								JOIN tbl_os ON tbl_os.revenda = tbl_revenda.revenda AND tbl_os.os = {$os}";
							$res_revenda = pg_query($con, $sql_revenda);

							if(pg_num_rows($res_revenda) > 0){
								$cidade_revenda = pg_fetch_result($res_revenda, 0, 'nome');
								$estado_revenda = pg_fetch_result($res_revenda, 0, 'estado');
							}

                            $servico = pg_fetch_result($resxls_excel, $x, 'servico');
                            $cod_defeito_constatado = pg_fetch_result($resxls_excel, $x, 'cod_defeito_constatado');
                            $nome_falha = "";
                            if (strlen($servico) > 0 && strlen($cod_defeito_constatado) > 0) {
                                $sqlFalha = "SELECT
                                            tbl_servico.descricao AS nome_falha,
                                            tbl_defeito_constatado.descricao AS nome_defeito,
                                            tbl_diagnostico.diagnostico,
                                            tbl_diagnostico.defeito_constatado,
                                            tbl_diagnostico.servico
                                    FROM    tbl_diagnostico
                                    JOIN tbl_servico ON tbl_servico.servico=tbl_diagnostico.servico
                                    JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado=tbl_diagnostico.defeito_constatado
                                    WHERE tbl_diagnostico.fabrica = $login_fabrica
                                    and  tbl_diagnostico.servico = $servico
                                    and  tbl_diagnostico.defeito_constatado = $cod_defeito_constatado
                                    ORDER BY tbl_diagnostico.diagnostico DESC;";
                                $resFalha = pg_query($con, $sqlFalha);

                                if (pg_num_rows($resFalha) > 0) {
                                    $nome_falha = pg_fetch_result($resFalha, 0, 'nome_falha');
                                }
                            }


							if(!empty($sua_os)){
								$sql_dias_aberto = "SELECT data_abertura::date - CASE WHEN data_fechamento::date IS NULL THEN DATE(NOW()) ELSE data_fechamento::date END AS dias_aberto FROM tbl_os WHERE os = {$sua_os}";

								$res_dias_aberto = pg_query($con, $sql_dias_aberto);

								if(pg_num_rows($res_dias_aberto) == 1){
									$dias_aberto = pg_fetch_result($res_dias_aberto, 0, 'dias_aberto');
									$dias_aberto = str_replace("-", "", $dias_aberto." dia(s)");
								}
							}

							// CHAMADO 2528193 TODAS AS FABRICA RECEBERÃO consumidor/revenda
							$nome_consumidor_revenda = ($consumidor_revenda == "C" || empty($consumidor_revenda)) ? $consumidor_nome : $revenda_nome;

							switch ($consumidor_revenda) {

							case "C":
								$consumidor_revenda = "CONS";
								break;
							case "R":
								$consumidor_revenda = "REV";
								break;
							case "":
								$consumidor_revenda = "";
								break;
							}

                            if($consultar_os_sem_listar_pecas == 't'){
							$titulos = array($sua_os,
								$produto_referencia,
								$produto_descricao,
								$serie,
								$defeito_reclamado_descricao,
								$defeito_constatado,
                                $nome_falha,
								$solucao_os,
								$digitacao,
								$abertura,
								$data_conserto,
								$fechamento,
								$nota_fiscal_faturamento,
								$emissao_faturamento,
								$data_nf,
								$consumidor_revenda,
								$codigo_posto,
								$posto_nome,
								$contato_cidade,
								$contato_estado,
								$nome_consumidor_revenda,
								$consumidor_fone
							);}else{
                            $titulos = array($sua_os,
                                $produto_referencia,
                                $produto_descricao,
                                $serie,
                                $defeito_reclamado_descricao,
                                $defeito_constatado,
                                $nome_falha,
                                $solucao_os,
                                $peca_referencia,
                                $peca_descricao,
                                $digitacao_item,
                                $digitacao,
                                $abertura,
                                $data_conserto,
                                $fechamento,
                                $nota_fiscal_faturamento,
                                $emissao_faturamento,
                                $data_nf,
                                $consumidor_revenda,
                                $codigo_posto,
                                $posto_nome,
                                $contato_cidade,
                                $contato_estado,
                                $nome_consumidor_revenda,
                                $consumidor_fone
                            );}

                            array_push($titulos, "$revenda_nome", "$revenda_cnpj", "$descricao_status_checkpoint");

                            if ($formato_excel == "xls") {
								fputs ($fp,"<tr>");
                                for ($i = 0; $i <= count($titulos); $i++) {
                                    fputs ($fp, "<td>{$titulos[$i]}</td>");
                                }
								fputs ($fp,"</tr>");
                            } else {
                                fputs ($fp,implode(";",$titulos)."\n");
                            }

						}

                        if ($formato_excel == "xls") {
                            fputs ($fp, "</table>");
                        }

					}else{
                        for($x =0;$x<pg_num_rows($resxls);$x++) {
							$os = pg_fetch_result($resxls, $x, 'os');
							$sua_os             = trim(pg_fetch_result($resxls,$x,sua_os));
							$posto              = trim(pg_fetch_result($resxls,$x,posto));
							$nota_fiscal        = trim(pg_fetch_result($resxls,$x,nota_fiscal));
							$digitacao          = trim(pg_fetch_result($resxls,$x,digitacao));
							$abertura           = trim(pg_fetch_result($resxls,$x,abertura));
							$fechamento         = trim(pg_fetch_result($resxls,$x,fechamento));
							$finalizada         = trim(pg_fetch_result($resxls,$x,finalizada));
							$data_conserto      = trim(@pg_fetch_result($resxls,$x,data_conserto));
							$serie              = trim(pg_fetch_result($resxls,$x,serie));
							$consumidor_nome    = trim(pg_fetch_result($resxls,$x,consumidor_nome));
							$consumidor_cpf    = trim(pg_fetch_result($resxls,$x,consumidor_cpf));
							$consumidor_fone    = trim(pg_fetch_result($resxls,$x,consumidor_fone));
							$codigo_posto       = trim(pg_fetch_result($resxls,$x,codigo_posto));
							$posto_nome         = trim(pg_fetch_result($resxls,$x,posto_nome));
							$situacao_posto     = trim(pg_fetch_result($resxls,$i,credenciamento));
							$uf_posto           = pg_fetch_result($resxls, $x, "contato_estado");
							$cidade_posto       = pg_fetch_result($resxls, $x, "contato_cidade");
							$produto_referencia = trim(pg_fetch_result($resxls,$x,produto_referencia));
							$produto_descricao  = trim(pg_fetch_result($resxls,$x,produto_descricao));
							$produto_voltagem   = trim(pg_fetch_result($resxls,$x,produto_voltagem));
							$consumidor_revenda = trim(pg_fetch_result($resxls,$x,consumidor_revenda));
							$data_nf            = trim(pg_fetch_result($resxls,$x,data_nf));
							$revenda_cnpj       = trim(pg_fetch_result($resxls,$x,revenda_cnpj));
							$revenda_nome       = trim(pg_fetch_result($resxls,$x,revenda_nome));
							$status_checkpoint  = trim(pg_fetch_result($resxls,$x,status_checkpoint));
							$defeito_constatado = trim(pg_fetch_result($resxls,$x,defeito_constatado));
							$rg_produto         = trim(pg_fetch_result($resxls,$x,rg_produto));
							$tipo_atendimento   = trim(pg_fetch_result($resxls,$x,tipo_atendimento));
							$os_excluida   		= trim(pg_fetch_result($resxls,$x,excluida));
							$xxfabrica   		= trim(pg_fetch_result($resxls,$x,fabrica));

							if(in_array($login_fabrica, array(156))){

								$contato_estado     = pg_fetch_result($resxls, $x, "contato_estado");
								$contato_cidade     = pg_fetch_result($resxls, $x, "contato_cidade");
								$status_os          = pg_fetch_result($resxls, $x, "status_os");
								$os_reparo          = pg_fetch_result($resxls, $x, "os_numero");
								$tipo_posto_interno = pg_fetch_result($resxls, $x, "posto_interno");

							}

							$sql_revenda = "SELECT tbl_cidade.nome, tbl_cidade.estado
								FROM tbl_cidade
								JOIN tbl_revenda ON tbl_revenda.cidade = tbl_cidade.cidade
								JOIN tbl_os ON tbl_os.revenda = tbl_revenda.revenda AND tbl_os.os = {$os}";
							$res_revenda = pg_query($con, $sql_revenda);

							if(pg_num_rows($res_revenda) > 0){
								$cidade_revenda = pg_fetch_result($res_revenda, 0, 'nome');
								$estado_revenda = pg_fetch_result($res_revenda, 0, 'estado');
							}
							if(!empty($sua_os)){

								$sql_dias_aberto = "SELECT data_abertura::date - CASE WHEN data_fechamento::date IS NULL THEN DATE(NOW()) ELSE data_fechamento::date END AS dias_aberto FROM tbl_os WHERE os = {$sua_os}";
								$res_dias_aberto = pg_query($con, $sql_dias_aberto);

								if(pg_num_rows($res_dias_aberto) == 1){
									$dias_aberto = pg_fetch_result($res_dias_aberto, 0, 'dias_aberto');
									$dias_aberto = str_replace("-", "", $dias_aberto." dia(s)");
								}

							}
							// CHAMADO 2528193 TODAS AS FABRICA RECEBERÃO consumidor/revenda
							$nome_consumidor_revenda = ($consumidor_revenda == "C" || empty($consumidor_revenda)) ? $consumidor_nome : $revenda_nome;

							if($login_fabrica == 30 or $login_fabrica == 50){

							/*

							"OS",
							"N. ATENDIMENTO",
							"ATENDIMENTO CENTRALIZADO",
							"OS REVENDEDOR",
							"STATUS",
							"SÉRIE",
							"AB",
							"DATA PEDIDO",
							"Nº PEDIDO",
							"NOTA FISCAL",
							"CÓDIGO",
							"DESCRIÇÃO",
							"DC",
							"FC",
							"C / R",
							"POSTO",
							"NOME POSTO",
							"UF",
							"CIDADE",
							"CONSUMIDOR/REVENDA",
							"TELEFONE",
							"NF PRODUTO",
							"PRODUTO",
							"DEFEITO RECLAMADO",
							"END. CONSUMIDOR",
							"CIDADE CONSUMIDOR",
							"UF CONSUMIDOR",
							"DEFEITO CONSTATADO"

							 */

								switch ($consumidor_revenda) {
								case "C":
									$consumidor_revenda = "CONS";
									break;

								case "R":
									$consumidor_revenda = "REV";
									break;

								case "":
									$consumidor_revenda = "";
									break;
								}

                                $consumidor_endereco  = trim(pg_fetch_result($resxls,$x,consumidor_endereco));
                                $consumidor_numero    = trim(pg_fetch_result($resxls,$x,consumidor_numero));
                                $consumidor_bairro    = trim(pg_fetch_result($resxls,$x,consumidor_bairro));
                                $consumidor_cep       = trim(pg_fetch_result($resxls,$x,consumidor_cep));
                                $consumidor_cidade    = trim(pg_fetch_result($resxls,$x,consumidor_cidade));
                                $consumidor_estado    = trim(pg_fetch_result($resxls,$x,consumidor_estado));
                                $defeito_constatado   = trim(pg_fetch_result($resxls,$x,defeito_constatado));
                                $defeito_reclamado_os = (in_array($login_fabrica, array(50))) ? trim(pg_fetch_result($resxls, $x, "defeito_reclamado_descricao_os")) : trim(pg_fetch_result($resxls, $x, "defeito_reclamado_os"));

								$pedido = pg_fetch_result($resxls, $x, 'pedido');

								if(in_array($login_fabrica, array(30))){
									$admin = pg_fetch_result($resxls, $x, "admin");
									$valor_os = pg_fetch_result($resxls, $x, "valor_os");
									$numero_processo = pg_fetch_result($resxls,$x,"numero_processo");

									if(strlen($admin) > 0){
										$sql_admin = "SELECT nome_completo FROM tbl_admin WHERE admin = {$admin} AND fabrica = {$login_fabrica}";
										$res_admin = pg_query($con, $sql_admin);
										if(pg_num_rows($res_admin) > 0){
											$admin = pg_fetch_result($res_admin, 0, "nome_completo");
										}
									}

									$valor_os = number_format($valor_os, 2, ",", ".");

								}

								if (!empty($pedido)) {
									$digitacao_item = pg_fetch_result($resxls, $x, 'digitacao_item');
									$nf = pg_fetch_result($resxls, $x, 'nf_fat') . ' ' . pg_fetch_result($resxls, $x, 'nf_emissao');
									$peca_referencia = pg_fetch_result($resxls, $x, 'peca_referencia');
									$peca_descricao = pg_fetch_result($resxls, $x, 'peca_descricao');
									$peca_qtde = pg_fetch_result($resxls, $x, 'peca_qtde');
								} else {


									$digitacao_item = '';
									$nf = '';
									$peca_referencia = '';
									$peca_descricao = '';
									$peca_qtde = '';
								}

								$old_os = $os;

								$sql_add = "SELECT os_posto, hd_chamado FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
								$res_add = pg_query($con, $sql_add);

								if(pg_num_rows($res_add) > 0){
									$hd_chamado = pg_fetch_result($res_add, 0, "hd_chamado");
									$os_revendedor = pg_fetch_result($res_add, 0, "os_posto");
									if(empty($hd_chamado)) {
										$sql_e = "SELECT hd_chamado FROM tbl_hd_chamado_extra JOIN tbl_hd_chamado using(hd_chamado) WHERE os = $os AND fabrica = $login_fabrica";
										$res_e = pg_query($con, $sql_e);
										if(pg_num_rows($res_e) > 0){
											$hd_chamado = pg_fetch_result($res_e, 0, "hd_chamado");
										}

									}
								}
								if($login_fabrica == 30 ){
									$os_revendedor = (strlen($os_revendedor) > 0 AND $os_revendedor != "null") ? $os_revendedor : $numero_processo;
									$os_revendedor = ($os_revendedor == "null") ? "" : $os_revendedor;									}

								$sql_cliente_admin = "SELECT tbl_cliente_admin.nome FROM tbl_cliente_admin JOIN tbl_os USING(cliente_admin) WHERE tbl_os.os = $os AND tbl_os.fabrica = $login_fabrica";
								$res_cliente_admin = pg_query($con, $sql_cliente_admin);
								$cliente_admin_nome = null;
								if(pg_num_rows($res_cliente_admin) > 0){

									$cliente_admin_nome = pg_fetch_result($res_cliente_admin, 0, "nome");

								}

								$cliente_admin_nome = (empty($cliente_admin_nome)) ? "Normal" : $cliente_admin_nome;

								$status_os_xls = exibeImagemStatusCheckpoint($status_checkpoint, $sua_os, true);

								if($login_fabrica == 50){
									$nome_admin = "";
									if(!empty($hd_chamado)) {
										$sql = "SELECT nome_completo FROM tbl_admin JOIN tbl_hd_chamado USING(admin)  WHERE hd_chamado =$hd_chamado";
										$res_admin = pg_query($con,$sql);
										$nome_admin = pg_fetch_result($res_admin,0,'nome_completo');
									}

									$titulos = array(
										$sua_os,
										$hd_chamado,
										$nome_admin,
										$abertura,
										$fechamento,
										$consumidor_revenda,
										$posto_nome,
										$uf_posto,
										$cidade_posto,
										$nome_consumidor_revenda,
										$consumidor_fone,
										$produto_referencia . "-" . $produto_descricao,
										$defeito_reclamado_os,
										$consumidor_endereco,
										$consumidor_cidade,
										$consumidor_estado,
										$defeito_constatado
									);

								}else{
                                    $quebra_linha            = array('<br>', ';', '<br/>', '<br />', '\n', '\t', '\r\n' , '"');
                                    $hd_chamado              = str_replace($quebra_linha, "", $hd_chamado);
                                    $cliente_admin_nome      = str_replace($quebra_linha, "", $cliente_admin_nome);
                                    $os_revendedor           = str_replace($quebra_linha, "", $os_revendedor);
                                    $status_os_xls           = str_replace($quebra_linha, "", $status_os_xls);
                                    $serie                   = str_replace($quebra_linha, "", $serie);
                                    $abertura                = str_replace($quebra_linha, "", $abertura);
                                    $digitacao_item          = str_replace($quebra_linha, "", $digitacao_item);
                                    $pedido                  = str_replace($quebra_linha, "", $pedido);
                                    $nf                      = str_replace($quebra_linha, "", $nf);
                                    $peca_referencia         = str_replace($quebra_linha, "", $peca_referencia);
                                    $peca_descricao          = str_replace($quebra_linha, "", $peca_descricao);
                                    $peca_qtde               = str_replace($quebra_linha, "", $peca_qtde);
                                    $data_conserto           = str_replace($quebra_linha, "", $data_conserto);
                                    $fechamento              = str_replace($quebra_linha, "", $fechamento);
                                    $consumidor_revenda      = str_replace($quebra_linha, "", $consumidor_revenda);
                                    $codigo_posto            = str_replace($quebra_linha, "", $codigo_posto);
                                    $posto_nome              = str_replace($quebra_linha, "", $posto_nome);
                                    $uf_posto                = str_replace($quebra_linha, "", $uf_posto);
                                    $nome_consumidor_revenda = str_replace($quebra_linha, "", $nome_consumidor_revenda);
                                    $consumidor_fone         = str_replace($quebra_linha, "", $consumidor_fone);
                                    $nota_fiscal             = str_replace($quebra_linha, "", $nota_fiscal);
                                    $produto_referencia      = str_replace($quebra_linha, "", $produto_referencia);
                                    $produto_descricao       = str_replace($quebra_linha, "", $produto_descricao);
                                    $defeito_reclamado_os    = str_replace($quebra_linha, "", $defeito_reclamado_os);
                                    $consumidor_endereco     = str_replace($quebra_linha, "", $consumidor_endereco);
                                    $consumidor_numero       = str_replace($quebra_linha, "", $consumidor_numero);
                                    $consumidor_cep          = str_replace($quebra_linha, "", $consumidor_cep);
                                    $consumidor_bairro       = str_replace($quebra_linha, "", $consumidor_bairro);
                                    $consumidor_cidade       = str_replace($quebra_linha, "", $consumidor_cidade);
                                    $consumidor_estado       = str_replace($quebra_linha, "", $consumidor_estado);
                                    $orientacao_sac          = str_replace($quebra_linha, "", $orientacao_sac);
                                    $admin                   = str_replace($quebra_linha, "", $admin);
                                    $valor_os                = str_replace($quebra_linha, "", $valor_os);
                                    $defeito_constatado      = str_replace($quebra_linha, "", $defeito_constatado);

                                    if ($formato_excel == "csv") {


                                        $hd_chamado              = "\"$hd_chamado\"";
                                        $cliente_admin_nome      = "\"$cliente_admin_nome\"";
                                        $os_revendedor           = "\"$os_revendedor\"";
                                        $status_os_xls           = "\"$status_os_xls\"";
                                        $serie                   = "\"$serie\"";
                                        $abertura                = "\"$abertura\"";
                                        $digitacao_item          = "\"$digitacao_item\"";
                                        $pedido                  = "\"$pedido\"";
                                        $nf                      = "\"$nf\"";
                                        $peca_referencia         = "\"$peca_referencia\"";
                                        $peca_descricao          = "\"$peca_descricao\"";
                                        $peca_qtde               = "\"$peca_qtde\"";
                                        $data_conserto           = "\"$data_conserto\"";
                                        $fechamento              = "\"$fechamento\"";
                                        $consumidor_revenda      = "\"$consumidor_revenda\"";
                                        $codigo_posto            = "\"$codigo_posto\"";
                                        $posto_nome              = "\"$posto_nome\"";
                                        $uf_posto                = "\"$uf_posto\"";
                                        $nome_consumidor_revenda = "\"$nome_consumidor_revenda\"";
                                        $consumidor_fone         = "\"$consumidor_fone\"";
                                        $nota_fiscal             = "\"$nota_fiscal\"";
                                        $aux_produto_referencia  = "\"$produto_referencia - $produto_descricao\"";
                                        $defeito_reclamado_os    = "\"$defeito_reclamado_os\"";
                                        $aux_consumidor_endereco = "\"$consumidor_endereco, $consumidor_numero\"";
                                        $consumidor_cep          = "\"$consumidor_cep\"";
                                        $consumidor_bairro       = "\"$consumidor_bairro\"";
                                        $consumidor_cidade       = "\"$consumidor_cidade\"";
                                        $consumidor_estado       = "\"$consumidor_estado\"";
                                        $admin                   = "\"$admin\"";
                                        $valor_os                = "\"$valor_os\"";
                                        $defeito_constatado      = "\"$defeito_constatado\"";
                                    } else {
                                        $aux_produto_referencia  = "$produto_referencia - $produto_descricao";
                                        $aux_consumidor_endereco = "$consumidor_endereco, $consumidor_numero";
                                    }

                                    if (strlen($aux_consumidor_endereco) == 4) {
                                        $aux_consumidor_endereco = "";
                                    }


                                    if ($login_fabrica == 30) {

                                    	$situacao_os = "";
                                    	if ($os_excluida =='t' && $xxfabrica  == 30) {
                                    		$situacao_os = traduz("OS Cancelada");
                                    	}

										$titulos = array($sua_os,
										$hd_chamado,
										$cliente_admin_nome,
										$os_revendedor,
										$status_os_xls,
										$situacao_os,
										$serie,
										$abertura,
										$digitacao_item,
										$pedido,
										$nf,
										$peca_referencia,
										$peca_descricao,
										$peca_qtde,
										$data_conserto,
										$fechamento,
										$consumidor_revenda,
										$codigo_posto,
										$posto_nome,
										$uf_posto,
										$cidade_posto,
										$nome_consumidor_revenda,
										$consumidor_fone,
										$nota_fiscal,
										$aux_produto_referencia,
										$defeito_reclamado_os,
										$aux_consumidor_endereco,
                                        $consumidor_cep,
                                        $consumidor_bairro,
										$consumidor_cidade,
										$consumidor_estado,
										$admin,
										$valor_os,
										$defeito_constatado);

									} else {
										$titulos = array($sua_os,
										$hd_chamado,
										$cliente_admin_nome,
										$os_revendedor,
										$status_os_xls,
										$serie,
										$abertura,
										$digitacao_item,
										$pedido,
										$nf,
										$peca_referencia,
										$peca_descricao,
										$peca_qtde,
										$data_conserto,
										$fechamento,
										$consumidor_revenda,
										$codigo_posto,
										$posto_nome,
										$uf_posto,
										$cidade_posto,
										$nome_consumidor_revenda,
										$consumidor_fone,
										$nota_fiscal,
										$aux_produto_referencia,
										$defeito_reclamado_os,
										$aux_consumidor_endereco,
                                        $consumidor_cep,
                                        $consumidor_bairro,
										$consumidor_cidade,
										$consumidor_estado,
										$admin,
										$valor_os,
										$defeito_constatado);
									}
								}

								if($login_fabrica == 50){

									$sql_serie = "SELECT
										cnpj,
										to_char(data_venda, 'dd/mm/yyyy') as data_venda
										FROM tbl_numero_serie
										WHERE serie = trim('$serie')";

									$res_serie = pg_query ($con,$sql_serie);

									if (pg_num_rows ($res_serie) > 0) {


										$txt_cnpj   = trim(pg_fetch_result($res_serie,0,cnpj));
										$data_venda = trim(pg_fetch_result($res_serie,0,data_venda));

										$sql_dados_revenda = "SELECT      tbl_revenda.nome              ,
											tbl_revenda.revenda           ,
											tbl_revenda.cnpj              ,
											tbl_revenda.cidade            ,
											tbl_revenda.fone              ,
											tbl_revenda.endereco          ,
											tbl_revenda.numero            ,
											tbl_revenda.complemento       ,
											tbl_revenda.bairro            ,
											tbl_revenda.cep               ,
											tbl_revenda.email             ,
											tbl_cidade.nome AS nome_cidade,
											tbl_cidade.estado
											FROM        tbl_revenda
											LEFT JOIN   tbl_cidade USING (cidade)
											LEFT JOIN   tbl_estado using(estado)
											WHERE       tbl_revenda.cnpj ='$txt_cnpj' ";

										$res_dados_revenda = pg_query ($con,$sql_dados_revenda);


										if (pg_num_rows ($res_dados_revenda) > 0) {
											$revenda_nome_1       = trim(pg_fetch_result($res_dados_revenda,0,nome));
											$revenda_cnpj_1       = trim(pg_fetch_result($res_dados_revenda,0,cnpj));

											$revenda_bairro_1     = trim(pg_fetch_result($res_dados_revenda,0,bairro));
											$revenda_cidade_1     = trim(pg_fetch_result($res_dados_revenda,0,cidade));
											$revenda_fone_1       = trim(pg_fetch_result($res_dados_revenda,0,fone));
										}

									}

									$titulos[] = $revenda_nome_1;
									$titulos[] = $revenda_cnpj_1;
									$titulos[] = $revenda_fone_1;
									$titulos[] = $data_venda;

									$titulos[] = $revenda_nome;
									$titulos[] = $revenda_cnpj;
									$titulos[] = $nota_fiscal;
									$titulos[] = $data_nf;
								}

							}else if($login_fabrica == 85){

								switch ($consumidor_revenda) {
								case "C":
									$consumidor_revenda = "CONS";
									break;

								case "R":
									$consumidor_revenda = "REV";
									break;

								case "":
									$consumidor_revenda = "";
									break;
								}
								$titulos = array($sua_os,
									$serie,
									$abertura,
									$fechamento,
									$consumidor_revenda,
									$posto_nome,
									$nome_consumidor_revenda,
									$consumidor_fone,
									$produto_referencia . "-" . $produto_descricao,
									$dias_aberto);
							}elseif($login_fabrica == 137 and $consumidor_revenda == "R"){

								$sua_os_tratada = explode("-", $sua_os);

								$os = pg_fetch_result($resxls, $x, 'os');

								$sql_dados_adicionais = "select valor_adicional_justificativa as obs_adicionais
									from tbl_os_revenda
									where fabrica = $login_fabrica and posto = $posto and sua_os::int4 = '$sua_os_tratada[0]' ";
								$res_dados_adicionais = pg_query($con, $sql_dados_adicionais);
								if(pg_num_rows($res_dados_adicionais) > 0){
									$dados_adicionais   = pg_fetch_result($res_dados_adicionais, 0, 'obs_adicionais');
									$dados_adicionais   = json_decode($dados_adicionais);

									$nota_fiscal_saida          = $dados_adicionais->nota_fiscal_saida;
									$data_nota_fiscal_saida     = $dados_adicionais->data_nota_fiscal_saida;
									$transportadora             = $dados_adicionais->transportadora;
								}


								$dados          = json_decode($rg_produto);
								$cfop           = $dados->cfop;
								$valor_unitario = "R$ ".$dados->vu;
								$valor_total    = "R$ ".$dados->vt;

								$titulos = array($sua_os,
									$abertura,
									$revenda_cnpj,
									$revenda_nome,
									$cidade_revenda, // new
									$estado_revenda, // new
									$nota_fiscal,
									$nota_fiscal_saida, // new
									$cfop,
									$data_nf,
									$data_nota_fiscal_saida, // new
									$valor_unitario,
									$valor_total,
									$produto_referencia . "-" . $produto_descricao,
									$transportadora, // new
									$serie,
									$defeito_constatado);

							}elseif($login_fabrica == 137  and $consumidor_revenda == "C" ){

								$consumidor_cidade = trim(pg_fetch_result($resxls,$x,consumidor_cidade)); //hd_chamado=2895754
								$consumidor_estado = trim(pg_fetch_result($resxls,$x,consumidor_estado)); //hd_chamado=2895754
								$titulos = array($sua_os,
									$abertura,
									$consumidor_cpf,
									$consumidor_nome,
									$consumidor_cidade,
									$consumidor_estado,
									$nota_fiscal,
									$nota_fiscal_saida, // new
									$cfop,
									$data_nf,
									$data_nota_fiscal_saida, // new
									$valor_unitario,
									$valor_total,
									$produto_referencia . "-" . $produto_descricao,
									$transportadora, // new
									$serie,
									$defeito_constatado
								);
								//$data_conserto,
								//$fechamento,

							}
							else if($login_fabrica == 145) {
								$titulos = array($sua_os,
									$abertura,
									$data_conserto,
									$fechamento,
									$codigo_posto,
									$nome_consumidor_revenda,
									$nota_fiscal,
									$produto_referencia . "-" . $produto_descricao);
							} else if (isset($novaTelaOs) && !in_array($login_fabrica, array(145,156))) {

								$titulos = array($sua_os,
									$serie,
									$abertura,
									$data_conserto,
									$fechamento,
									$codigo_posto,
									$nome_consumidor_revenda,
									$nota_fiscal,
									$produto_referencia . "-" . $produto_descricao);

							}else if($login_fabrica == 156){

							/*

							"OS",
							"SÉRIE",
							"AB",
							"FC",
							"STATUS ELGIN",
							"TIPO DE ATENDIMENTO",
							"NOME POSTO",
							"POSTO INTERNO",
							"CIDADE",
							"ESTADO",
							"CONSUMIDOR/REVENDA",
							"NF",
							"DATA NF",
							"NF REMESSA",
							"PRODUTO"

							 */

								$status_elgin       = "";
								$posto_interno_nome = "";
								$nf_remessa         = "";

								if($tipo_posto_interno == "t"){

									$status_os = (strlen($status_os) == 0) ? 0 : $status_os;
									$status_elgin = "";

									if($status_os > 0){

										$sql_status_desc = "SELECT descricao FROM tbl_status_os WHERE status_os = {$status_os}";
										$res_status_desc = pg_query($con, $sql_status_desc);

										if(pg_num_rows($res_status_desc) > 0){
											$status_elgin = pg_fetch_result($res_status_desc, 0, "descricao");
										}

									}

									if(strlen($os_reparo) > 0){

										$sql_posto_interno = "SELECT
											tbl_posto.nome
											FROM tbl_os
											INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
											INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
											WHERE
											tbl_os.os = {$os_reparo}";
										$res_posto_interno = pg_query($con, $sql_posto_interno);

										if(pg_num_rows($res_posto_interno) > 0){
											$posto_interno_nome = pg_fetch_result($res_posto_interno, 0, "nome");
										}

									}

									$sql_nf_remessa = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$sua_os}";
									$res_nf_remessa = pg_query($con, $sql_nf_remessa);

									if(pg_num_rows($res_nf_remessa) > 0){

										$campos_adicionais = pg_fetch_result($res_nf_remessa, 0, "campos_adicionais");
										if(strlen($campos_adicionais) > 0){

											$campos_adicionais = json_decode($campos_adicionais, true);

											if(isset($campos_adicionais["nf_envio"])){
												$nf_remessa = $campos_adicionais["nf_envio"];
											}

										}

									}

								}

								$tipo_atendimento_desc = "";

								if(strlen($tipo_atendimento) > 0){

									$sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE tipo_atendimento = {$tipo_atendimento} AND fabrica = {$login_fabrica}";
									$res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);

									if(pg_num_rows($res_tipo_atendimento) > 0){
										$tipo_atendimento_desc = pg_fetch_result($res_tipo_atendimento, 0, "descricao");
									}

								}

								$titulos = array(
									$sua_os,
									$serie,
									$abertura,
									$fechamento,
									$status_elgin,
									$tipo_atendimento_desc,
									$codigo_posto." - ".$posto_nome,
									$posto_interno_nome,
									$contato_cidade,
									$contato_estado,
									$nome_consumidor_revenda,
									$nota_fiscal,
									$data_nf,
									$nf_remessa,
									$produto_referencia . "-" . $produto_descricao
								);


							}else{
								$titulos = array($sua_os,
									$serie,
									$abertura,
									$data_conserto,
									$fechamento,
									$codigo_posto,
									$nome_consumidor_revenda,
									$consumidor_fone,
									$produto_referencia . "-" . $produto_descricao);
							}


                            if ($formato_excel == "xls") {
                                fputs ($fp,"<tr>");

                                for ($i = 0; $i <= count($titulos); $i++) {
                                    fputs ($fp, "<td>{$titulos[$i]}</td>");
                                }

                                fputs ($fp,"</tr>");
                            } else {
                                fputs ($fp,implode(";",$titulos)."\n");
                            }

						}

                        if ($formato_excel == "xls") {
                            fputs ($fp, "</table>");
                        }
					}
                    flush();
					//$excel->close();

					echo ` cp $arquivo_completo_tmp $path `;

					$data = date("Y-m-d").".".date("H-i-s");

					//echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;

					$resposta .= "<br>";
					$resposta .= "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
					$resposta .= "<tr>";
					$resposta .= "<td colspan=\"$colspan_excel\" style='border: 0; font: bold 14px \"Arial\";'><a href=\"xls/$arquivo_nome\" target=\"_blank\" style=\"text-decoration: none; \"><img src=\"imagens/excel.png\" height=\"20px\" width=\"20px\" align=\"absmiddle\">&nbsp;&nbsp;&nbsp;".traduz("Gerar Arquivo Excel")."</a></td>";
					$resposta .= "";
					$resposta .= "</tr>";
					$resposta .= "</table>";
					echo $resposta;
					echo "<br/>";
				} else if (pg_num_rows($resxls) > 0) { # HD 193344
                    $formato_excel = trim($_REQUEST['formato_excel']);
					$host   = $_SERVER['SCRIPT_NAME'];
					$host   = str_replace('admin_cliente','admin',$host);
					$host   = str_replace('/os_consulta_lite.php','',$host);
					$path_2 = getcwd();
					$path_2 = str_replace('admin_cliente','admin/',$path_2);

					flush();
					$data = date ("d/m/Y H:i:s");
					$path             = "/xls/";

                    $artquivo_nome = "consulta-os-$login_fabrica-$login_admin.$formato_excel";
                    $arquivo_completo = $path_2.$path.$artquivo_nome;
                    $caminho_donwload = $host.$path.$artquivo_nome;
                    $fp = fopen ($arquivo_completo,"w+");

                    if ($formato_excel == "xls") {
                        $aux_exc_1 = "<td><b>";
                        $aux_exc_2 = "</b></td>";

                        fputs ($fp,"<table align='center' width='100%'><tr>");
                    } else {

                        $aux_exc_1 = "";
                        $aux_exc_2 = ";";
                    }

					if ($login_fabrica == 158) {
						fputs ($fp,"{$aux_exc_1}".traduz('UNIDADE DE NEGÓCIO')."{$aux_exc_2}");
					}
                        fputs ($fp,"{$aux_exc_1}OS{$aux_exc_2}");

		        if ($login_fabrica == 151) {
				fputs ($fp,"{$aux_exc_1}".traduz('TIPO DE ATENDIMENTO')."{$aux_exc_2}");
			}
					if ($login_fabrica == 158) {
						fputs ($fp,"{$aux_exc_1}".traduz('AUDITORIAS')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('CLIENTE ADMIN')."{$aux_exc_2}");
					}

					if($telecontrol_distrib || in_array($login_fabrica, [138,160])){
						fputs ($fp,"{$aux_exc_1}".traduz('PEDIDO')."{$aux_exc_2}");
					}

					if ($login_fabrica == 52) {
						fputs ($fp,"{$aux_exc_1}".traduz('Nº Atendiment')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('Cliente Fricon')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('Número Ativo')."{$aux_exc_2}");
					}
					if ($login_fabrica == 50) {
						fputs ($fp,"{$aux_exc_1}".traduz('Nº Atendiment')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('Usuário')."{$aux_exc_2}");
					}
					if($login_fabrica == 35){
						fputs ($fp,"{$aux_exc_1}".traduz('Nº Atendimento')."{$aux_exc_2}");
					}

					if(in_array($login_fabrica, array(152,180,181,182))) {
						fputs ($fp,"{$aux_exc_1}".traduz('TIPO')."{$aux_exc_2}");
					}

					if ($login_fabrica == 158) {
						fputs ($fp,"{$aux_exc_1}".traduz('PATRIMÔNIO')."{$aux_exc_2}");
					}

					if(!in_array($login_fabrica,array(1,3,20,81,138,145,160)) and !$replica_einhell){
                        fputs ($fp,"{$aux_exc_1}".traduz('SÉRIE')."{$aux_exc_2}");
					}

					if($login_fabrica == 104){
						fputs($fp, "{$aux_exc_1}".traduz('DIAS EM ABERTO')."{$aux_exc_2}");
					}

					if($login_fabrica == 160 or $replica_einhell){
						fputs ($fp,"{$aux_exc_1}".traduz('Nº LOTE')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('VERSÃO PRODUTO')."{$aux_exc_2}");
					}

                    fputs ($fp,"{$aux_exc_1}AB{$aux_exc_2}");

                    if (in_array($login_fabrica, array(169,170))) {
                        fputs ($fp,"{$aux_exc_1}".traduz('Data de agendamento Callcenter')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('Data Confirmação Posto')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('Data de Reagendamento Posto')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('Inspetor')."{$aux_exc_2}");
                    }

                    if ($login_fabrica == 115) {
                        fputs ($fp,"{$aux_exc_1}".traduz('ENTRADA INTERVENÇÃO TÉCNICA')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('LIBERAÇÃO INTERVENÇÃO TÉCNICA')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('REPROVADO INTERVENÇÃO TÉCNICA')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('DATA PEÇAS FATURADAS')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('PRODUTO CONSERTADO')."{$aux_exc_2}");
                    }

					if($login_fabrica == 6) fputs ($fp,"{$aux_exc_1}DATA NF{$aux_exc_2}");

					if($login_fabrica == 138){ //hd_chamado=2439865
						fputs ($fp,"{$aux_exc_1}DG{$aux_exc_2}");
					}

					if ($login_fabrica == 158) {
						fputs ($fp,"{$aux_exc_1}DC{$aux_exc_2}");
					}

					if($mostra_data_conserto){
						fputs ($fp,"{$aux_exc_1}DC{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}FC{$aux_exc_2}");
					}else{
                        fputs ($fp,"{$aux_exc_1}FC{$aux_exc_2}");
					}

                    if ($login_fabrica == 158) {
                        if (empty($login_cliente_admin)) {
						    fputs ($fp,"{$aux_exc_1}".traduz('DATA ABERTURA KOF')."{$aux_exc_2}");
                        }
						fputs ($fp,"{$aux_exc_1}".traduz('DATA INÍCIO ATENDIMENTO')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('DATA FIM ATENDIMENTO')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('DATA LANÇAMENTO DA PEÇA')."{$aux_exc_2}");
					}

					if(in_array($login_fabrica, array(153,174,175,176,177))){
						fputs ($fp,"{$aux_exc_1}".traduz('TIPO DE ATENDIMENTO')."{$aux_exc_2}");
					}

					if($login_fabrica <> 138){ //hd_chamado=2439865
						if (!in_array($login_fabrica, array(141,144))) {
                           fputs ($fp,"{$aux_exc_1}C/R{$aux_exc_2}");
						} else {
							fputs ($fp,"{$aux_exc_1}".traduz('TIPO DE ATENDIMENTO')."{$aux_exc_2}");
						}
					}

                    if ($login_fabrica == 164) {
                        fputs ($fp,"{$aux_exc_1}".traduz('Nome Fantasia')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('UF Revenda')."{$aux_exc_2}");
                    }

                    if ($telecontrol_distrib || in_array($login_fabrica, [160])) {
                        fputs ($fp,"{$aux_exc_1} NF da OS {$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1} ".traduz('Emissão NF')." {$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1} ".traduz('Transportadora')." {$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1} ".traduz('Conhecimento')." {$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1} ".traduz('Data da Entrega')." {$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1} ".traduz('AB >>> Entrega')." {$aux_exc_2}");
                        if($telecontrol_distrib){
                            fputs ($fp,"{$aux_exc_1} ".traduz('Data Aguardando Conserto')." {$aux_exc_2}"); 
                            fputs ($fp,"{$aux_exc_1} ".traduz('Qtde Dias em Conserto')." {$aux_exc_2}");
                        }
                    }

					if(in_array($login_fabrica, array(152,180,181,182))) {
						fputs ($fp,"{$aux_exc_1}".traduz('Tempo p/ defeito')."{$aux_exc_2}");
					}

					if ($login_fabrica == 158) {
						fputs ($fp,"{$aux_exc_1}".traduz('REGIÃO')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('DISTRIBUIDOR')."{$aux_exc_2}");
					}

                    if (in_array($login_fabrica, array(169,170))) {
                        fputs ($fp,"{$aux_exc_1}".traduz('CODIGO POSTO')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('NOME POSTO')."{$aux_exc_2}");
                    } else {
                        fputs ($fp,"{$aux_exc_1}".traduz('POSTO')."{$aux_exc_2}");
                    }

					if ($login_fabrica == 158) {
                        fputs ($fp,"{$aux_exc_1}".traduz('TIPO DE POSTO')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('CEP')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('ENDEREÇO')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('BAIRRO')."{$aux_exc_2}");
					}


    				fputs ($fp,"{$aux_exc_1}".traduz('CIDADE')."{$aux_exc_2}");
    				fputs ($fp,"{$aux_exc_1}".traduz('ESTADO')."{$aux_exc_2}");

                    if ($login_fabrica == 52) {
                        fputs ($fp,"{$aux_exc_1}".traduz('PAÍS')."{$aux_exc_2}");
                    }


					if ($login_fabrica == 11 or $login_fabrica == 172) {
						fputs ($fp,"{$aux_exc_1}".traduz('SITUAÇÃO POSTO')."{$aux_exc_2}");
					}

					if ($login_fabrica == 158) {
                        if (empty($login_cliente_admin)) {
						    fputs ($fp,"{$aux_exc_1}".traduz('CÓDIGO DO CLIENTE')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('CLIENTE')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('TELEFONE')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('CELULAR')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('EMAIL')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('TIPO DE ATENDIMENTO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('DEFEITO RECLAMADO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('DEFEITO CONSTATADO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('SOLUÇÃO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('Prog. na Chegada PDV')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('Prog. na Saída PDV')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('CLASSIFICAÇÃO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('OBSERVAÇÃO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('OBSERVAÇÃO KOF')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('ADMIN ÚLTIMA ALTERAÇÃO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('ADMIN FINAL')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}OS KOF{$aux_exc_2}");
                        } else {
                            //fputs ($fp,"CÓDIGO DO CLIENTE;");
                            fputs ($fp,"{$aux_exc_1}".traduz('CLIENTE')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('TELEFONE')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('CELULAR')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('EMAIL')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('TIPO DE ATENDIMENTO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('DEFEITO RECLAMADO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('DEFEITO CONSTATADO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('SOLUÇÃO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('CLASSIFICAÇÃO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('OBSERVAÇÃO')."{$aux_exc_2}");
                            //fputs ($fp,"OBSERVAÇÃO KOF;");
                            fputs ($fp,"{$aux_exc_1}".traduz('ADMIN ÚLTIMA ALTERAÇÃO')."{$aux_exc_2}");
                            fputs ($fp,"{$aux_exc_1}".traduz('ADMIN FINAL')."{$aux_exc_2}");
                            //fputs ($fp,"OS KOF;");
                        }
					}

					if ($login_fabrica == 163){
						fputs ($fp,"{$aux_exc_1}".traduz('TIPO DE ATENDIMENTO')."{$aux_exc_2}");
					}

                    if ($login_fabrica == 165 && !empty($_POST['tecnico'])) {
                        fputs ($fp,"{$aux_exc_1}".traduz('NOME TÉCNICO')."{$aux_exc_2}");
                    }

                    if ($login_fabrica == 164) {
                        fputs ($fp,"{$aux_exc_1}".traduz('CPF/CNPJ')."{$aux_exc_2}");
                    }

                    if (!in_array($login_fabrica, array(158,169,170))) {
                        fputs ($fp,"{$aux_exc_1}".traduz('CONSUMIDOR/REVENDA')."{$aux_exc_2}");
                    }
                    if ($telecontrol_distrib){
                        fputs ($fp,"{$aux_exc_1}".traduz('CPF/CNPJ')."{$aux_exc_2}");
                    }

                    if (in_array($login_fabrica, array(169,170))) {
                        fputs ($fp,"{$aux_exc_1}".traduz('REVENDA')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('CNPJ REVENDA')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('CONSUMIDOR')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('BAIRRO')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('CIDADE')."{$aux_exc_2}");
                    }

					if (!in_array($login_fabrica, array(141,144)) && !isset($novaTelaOs)) {
                        fputs ($fp,"{$aux_exc_1}".traduz('TELEFONE')."{$aux_exc_2}");
					}elseif($login_fabrica == 138){ //hd_chamado=2439865
						fputs ($fp,"{$aux_exc_1}".traduz('NF VENDA PRODUTO')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('DATA NF')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('PRODUTO')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('SÉRIE')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('NOTA FISCAL PEÇAS')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('DATA NF')."{$aux_exc_2}");
					} else {
						fputs ($fp,"{$aux_exc_1}".traduz('NOTA FISCAL')."{$aux_exc_2}");
					}

					if($login_fabrica == 141){ // HD-2386867
						fputs ($fp,"{$aux_exc_1}".traduz('UF CONSUMIDOR')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('UF POSTO')."{$aux_exc_2}");
					}

					if ($login_fabrica == 80) {
						fputs($fp,"{$aux_exc_1}".traduz('DATA DE COMPRA')."{$aux_exc_2}");
					}
					if($login_fabrica == 6){
                        fputs ($fp,"{$aux_exc_1}".traduz('E-MAIL CONSUMIDOR')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('NOME REVENDA')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('CNPJ REVENDA')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('NF DA REVENDA')."{$aux_exc_2}");
					}
					if($login_fabrica == 50) {
						fputs ($fp,"{$aux_exc_1}".traduz('END. CONSUMIDOR')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('CIDADE CONSUMIDOR')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('UF CONSUMIDOR')."{$aux_exc_2}");
					}
                    if($login_fabrica == 171){
                        fputs ($fp,"{$aux_exc_1}".traduz('REFERÊNCIA FÁBRICA')."{$aux_exc_2}");
                    }

                    if($login_fabrica <> 138 AND !$telecontrol_distrib){
                        fputs ($fp,"{$aux_exc_1}".traduz('PRODUTO')."{$aux_exc_2}");
                    }

                    if ($login_fabrica == 94) {
                        fputs ($fp,$aux_exc_1.traduz('DEFEITO RECLAMADO').$aux_exc_2);
                        fputs ($fp,$aux_exc_1.traduz('DEFEITO CONSTATADO').$aux_exc_2);   
                    }

		    if ($telecontrol_distrib) {
			fputs ($fp,"{$aux_exc_1}".traduz('REFERÊNCIA PRODUTO')."{$aux_exc_2}");
			fputs ($fp,"{$aux_exc_1}".traduz('DESCRIÇÃO PRODUTO')."{$aux_exc_2}");
			fputs ($fp,$aux_exc_1.traduz('REFERÊNCIA PEÇA').$aux_exc_2);
			fputs ($fp,$aux_exc_1.traduz('DESCRIÇÃO PEÇA').$aux_exc_2);
                        fputs ($fp,$aux_exc_1.traduz('QTDE').$aux_exc_2);   
                    }

                    if ($login_fabrica == 3) {
                        fputs ($fp,"{$aux_exc_1}".traduz('TIPO DE ATENDIMENTO')."{$aux_exc_2}");
                    }

					if (in_array($login_fabrica,array(115,158))) {
						fputs ($fp,"{$aux_exc_1}".traduz('FAMÍLIA DO PRODUTO')."{$aux_exc_2}");
					}

                    if ($login_fabrica == 115){
                        fputs ($fp,"{$aux_exc_1}".traduz('DEFEITO RECLAMADO')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('DEFEITO CONSTATADO')."{$aux_exc_2}");
                    }

					if(in_array($login_fabrica, array(152,180,181,182))) {
						$sem_listar_peca = $_POST['sem_listar_peca'];

						if($sem_listar_peca <> 1){
							fputs ($fp,"{$aux_exc_1}".traduz('DESCRIÇÃO PEÇA')."{$aux_exc_2}");
							fputs ($fp,"{$aux_exc_1}".traduz('QTDE')."{$aux_exc_2}");
							fputs ($fp,"{$aux_exc_1}".traduz('CÓDIGO')."{$aux_exc_2}");
						}
					}

					if($login_fabrica == 85){
						fputs($fp, "{$aux_exc_1}".traduz('DIAS EM ABERTO')."{$aux_exc_2}");
					}
					if(in_array($login_fabrica, array(3,52,86)) or $multimarca =='t'){
						fputs($fp, "{$aux_exc_1}".traduz('MARCA')."{$aux_exc_2}");
					}
					if(in_array($login_fabrica, array(115,116,117,120))){
						fputs($fp, "{$aux_exc_1}KM{$aux_exc_2}");
					}

                    if(in_array($login_fabrica, array(164))){
                        fputs($fp, "{$aux_exc_1}".traduz('DEFEITO CONSTATADO')."{$aux_exc_2}");
                        if ($login_fabrica == 164) {
                            fputs($fp, "{$aux_exc_1}".traduz('DESTINAÇÃO')."{$aux_exc_2}");
                        }
                    }

                    if (in_array($login_fabrica,array(165))) {
                        fputs($fp, "{$aux_exc_1}".traduz('SERVIÇO REALIZADO')."{$aux_exc_2}");
                    }
                    if($login_fabrica == 148){
                        fputs ($fp,"{$aux_exc_1}Produto em Estoque{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}PIN{$aux_exc_2}");
                    }

                    if($login_fabrica == 131){
                        fputs ($fp, "{$aux_exc_1} ".traduz('DATA DA REPROVA ')."{$aux_exc_2}");
                        fputs ($fp, "{$aux_exc_1} ".traduz('DATA DIGITAÇÃO ')."{$aux_exc_2}");
                        fputs ($fp, "{$aux_exc_1} ".traduz('DATA GERAÇÃO ')."{$aux_exc_2}");
                        fputs ($fp, "{$aux_exc_1} ".traduz('DATA NF  ')."{$aux_exc_2}");
                        fputs ($fp, "{$aux_exc_1} ".traduz('PREVISÃO DE ENTREGA ')."{$aux_exc_2}");
                    }

                    fputs ($fp,"{$aux_exc_1}".traduz('STATUS')."{$aux_exc_2}");

					if ($login_fabrica == 158) {
						fputs ($fp,"{$aux_exc_1}".traduz('ARQUIVO ENTRADA')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('DATA INTEGRAÇÃO')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('ARQUIVO SAÍDA')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('DATA SAÍDA')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('LONGITUDE')."{$aux_exc_2}");
						fputs ($fp,"{$aux_exc_1}".traduz('LATITUDE')."{$aux_exc_2}");
					}

					if($login_fabrica == 6){
                        fputs ($fp,"{$aux_exc_1}".traduz('OBSERVAÇÃO')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('ORIENTAÇÃO')."{$aux_exc_2}");
                    }

                    fputs ($fp, "{$aux_exc_1}".traduz('SITUAÇÃO')."{$aux_exc_2}");

                    if (in_array($login_fabrica, array(152,180,181,182))) { /*HD's - 4292800 e 4379163*/
                        fputs ($fp, "{$aux_exc_1}".traduz('CLASSIFICAÇÃO')."{$aux_exc_2}");
                        fputs ($fp, "{$aux_exc_1}".traduz('DESCRIÇÃO DETALHADA DO PROBLEMA')."{$aux_exc_2}");
                    }

                    if($login_fabrica == 165){
                        fputs ($fp,"{$aux_exc_1}".traduz('PRODUTO TROCADO')."{$aux_exc_2}");
                        fputs ($fp,"{$aux_exc_1}".traduz('DEFEITO CONSTATO')."{$aux_exc_2}");
                    }

                    if( $login_fabrica == 90 ){
                        fputs($fp,"{$aux_exc_1}PEÇA{$aux_exc_2}");
                        fputs($fp,"{$aux_exc_1}PEDIDO{$aux_exc_2}");
                    }

                    if ($formato_excel == "xls") {
                        fputs ($fp,"</tr>");


                    } else {
                        fputs ($fp,"\n");

                    }


					for($x =0;$x<pg_num_rows($resxls);$x++) {

						$cor                = "";
						$sua_os             = "";
						$hd_chamado         = "";
						$numero_ativo_res   = "";
						$nota_fiscal        = "";
						$digitacao          = "";
						$abertura           = "";
						$consumidor_revenda = "";
						$fechamento         = "";
						$finalizada         = "";
						$data_conserto      = "";
						$serie              = "";
						$consumidor_nome    = "";
						$consumidor_fone    = "";
                        $xtransportadora    = "";
						$codigo_posto       = "";
						$posto_nome         = "";
                        $produto_referencia = "";
						$produto_referencia_fabrica = "";
						$produto_descricao  = "";
						$produto_voltagem   = "";
						$marca_logo_nome    = "";
						$situacao_posto     = "";
						$data_nf            = "";
						$cidade_uf          = "";
                        $data_lancamento_peca = "";
                        if ($login_fabrica == 164) {
                            $nome_fantasia  = "";
                            $uf_revenda     = "";
                        }
                        if($login_fabrica == 158){
                            $consumidor_celular    = "";
                            $consumidor_email    = "";
                        }
						$consumidor_cidade  = "";
						$cor   = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

						$os                 =  trim(pg_fetch_result($resxls,$x,os));
						$sua_os             = trim(pg_fetch_result($resxls,$x,sua_os));
						$hd_chamado         = trim(pg_fetch_result($resxls,$x,hd_chamado));
						if ($login_fabrica == 52){
                            $cliente_fricon     = pg_fetch_result($resxls,$x,cliente_admin_nome);
							$numero_ativo_res   = trim(pg_fetch_result($resxls,$x,ordem_ativo));
						}

                        $cidade_posto_xls   = trim(pg_fetch_result($resxls,$x,contato_cidade));
						$estado_posto_xls   = trim(pg_fetch_result($resxls,$x,contato_estado));

						$cidade_uf          = $cidade_posto_xls."/".$estado_posto_xls;

						$nota_fiscal        = trim(pg_fetch_result($resxls,$x,nota_fiscal));
						$digitacao          = trim(pg_fetch_result($resxls,$x,digitacao));
						$abertura           = trim(pg_fetch_result($resxls,$x,abertura));
						$consumidor_revenda = trim(pg_fetch_result($resxls,$x,consumidor_revenda));
						$fechamento         = trim(pg_fetch_result($resxls,$x,fechamento));
						$finalizada         = trim(pg_fetch_result($resxls,$x,finalizada));
						$data_conserto      = trim(@pg_fetch_result($resxls,$x,data_conserto));
						$serie              = trim(pg_fetch_result($resxls,$x,serie));
						$type              = trim(pg_fetch_result($resxls,$x,type));
						$reincidencia       = trim(pg_fetch_result($resxls,$x,reincidencia));
						$consumidor_nome    = trim(pg_fetch_result($resxls,$x,consumidor_nome));
						$excluida           = trim(pg_fetch_result($resxls,$x,excluida));
						$consumidor_fone    = trim(pg_fetch_result($resxls,$x,consumidor_fone));
						$data_nf            = trim(pg_fetch_result($resxls,$x,data_nf));
						$codigo_posto       = trim(pg_fetch_result($resxls,$x,codigo_posto));
						$posto_nome         = trim(pg_fetch_result($resxls,$x,posto_nome));
						$produto_referencia = trim(pg_fetch_result($resxls,$x,produto_referencia));
						$status_os          = trim(pg_fetch_result($resxls,$x,status_os));
                        $produto_descricao  = trim(pg_fetch_result($resxls,$x,produto_descricao));
						$produto_referencia_fabrica  = trim(pg_fetch_result($resxls,$x,produto_referencia_fabrica));
						$produto_voltagem   = trim(pg_fetch_result($resxls,$x,produto_voltagem));
						$status_checkpoint  = trim(pg_fetch_result($resxls,$x,status_checkpoint));
						$marca_logo         = trim(pg_fetch_result($resxls,$x,marca));
						$situacao_posto     = trim(pg_fetch_result($resxls,$x,credenciamento));
						$revenda_nome       = trim(pg_fetch_result($resxls,$x,revenda_nome));
						$obs                = trim(pg_fetch_result($resxls,$x,obs));
						$consumidor_endereco            = pg_fetch_result($resxls,$x, consumidor_endereco);
						$consumidor_numero              = pg_fetch_result($resxls,$x, consumidor_numero);
						$consumidor_estado              = pg_fetch_result($resxls,$x, consumidor_estado);
                                                $consumidor_cidade              = pg_fetch_result($resxls,$x, consumidor_cidade);

                        if($login_fabrica == 158){
                            $consumidor_celular    = pg_fetch_result($resxls,$x,"consumidor_celular");
                            $consumidor_email    = pg_fetch_result($resxls,$x,"consumidor_email");
                        }

                        if($login_fabrica == 131){
                            $referencia_peca = pg_fetch_result($resxls,$x, "referencia_peca");
                            $descricao_peca = pg_fetch_result($resxls, $x, "descricao_peca");
                            $peca_referencia_descricao = "$referencia_peca - $descricao_peca";

                           /* $dt_digitacao_peca = mostra_data( substr(pg_fetch_result($resxls, $x, "data_digitacao_peca"), 0,10 ));
                            $dt_geracao_pedido = mostra_data(substr(pg_fetch_result($resxls, $x, "dt_geracao"),0,10));
                            $nf_peca            = pg_fetch_result($resxls, $x, "nf_peca"); */
                            $query_adicionais = "SELECT campos_adicionais
                                                 FROM tbl_os_campo_extra
                                                 WHERE os = $os";

                            $res_adicionais = pg_query($con, $query_adicionais);

                            $campos_adicionais = pg_fetch_result($res_adicionais, 0, campos_adicionais);

                            $campos_adicionais = json_decode($campos_adicionais);
                        }

                        if ($login_fabrica == 161) {
                            $serie = strtoupper($serie);
                        }

                        if ($login_fabrica == 94) {
                            $defeito_reclamado_os = trim(pg_fetch_result($resxls, $x, "defeito_reclamado_os"));
                            $defeito_constatado_desc = trim(pg_fetch_result($resxls, $x, "defeito_constatado_desc"));
                        }

						if(in_array($login_fabrica,array(30,152,180,181,182)) && $sem_listar_peca <> 1){
							$peca_referencia = pg_fetch_result($resxls, $x, peca_referencia);
							$peca_descricao  = pg_fetch_result($resxls, $x, peca_descricao);
							$peca_qtde       = pg_fetch_result($resxls, $x, peca_qtde);

						}

                        if ($telecontrol_distrib) {
                            $cpf_cnpj = pg_fetch_result($resxls, $x, consumidor_cpf);
                        }
						$nome_consumidor_revenda = ($consumidor_revenda == "C" || empty($consumidor_revenda)) ? $consumidor_nome : $revenda_nome;

						$consumidor_email    = trim(pg_fetch_result($resxls,$x,consumidor_email));
						$revenda_cnpj_tec    = trim(pg_fetch_result($resxls,$x,revenda_cnpj));
						$revenda_nome_tec    = trim(pg_fetch_result($resxls,$x,revenda_nome));

                        if ($login_fabrica == 165 ) {
                            $nomeTecnico = trim(pg_fetch_result($resxls,$x,nome_tecnico));
                        }

                        if ($login_fabrica == 163){
                            $descricao_tipo_atendimento    = pg_fetch_result($resxls, $x, "descricao");
                        }

                        if (in_array($login_fabrica, array(169,170))) {
                            unset($xprimeira_data_agendamento);
                            unset($xdata_confirmacao);
                            unset($xinspetor_sap);
                            unset($xultima_data);
                            unset($consumidor_bairro);
                            unset($consumidor_cidade);

                            $xprimeira_data_agendamento = pg_fetch_result($resxls,$x,'primeira_data_agendamento');
                            $xdata_confirmacao          = pg_fetch_result($resxls,$x,'data_confirmacao');
                            $xinspetor_sap              = pg_fetch_result($resxls,$x,'inspetor_sap');
                            $consumidor_bairro          = pg_fetch_result($resxls,$x, 'cons_bairro');
                            $consumidor_cidade          = pg_fetch_result($resxls,$x, 'cons_cidade');

							if(!empty($xprimeira_data_agendamento)) {
								$xsqlReagendamento = "SELECT date(tbl_tecnico_agenda.data_agendamento) as data_agendamento
													FROM tbl_tecnico_agenda
													WHERE tbl_tecnico_agenda.os = $os
													AND tbl_tecnico_agenda.data_agendamento != '$xprimeira_data_agendamento'
													AND tbl_tecnico_agenda.confirmado IS NOT NULL
													ORDER BY tbl_tecnico_agenda.data_agendamento DESC
													LIMIT 1
													";
								$xresReagendamento = pg_query($con, $xsqlReagendamento);

								$xultima_data = pg_fetch_result($xresReagendamento, 0, 'data_agendamento');
							}

                        }

                        if ($login_fabrica == 164) {
                            $sql_revenda = "SELECT tbl_estado.estado, tbl_os_campo_extra.campos_adicionais AS revenda_fantasia
                                            FROM tbl_cidade
                                            JOIN tbl_revenda ON tbl_revenda.cidade = tbl_cidade.cidade
                                            JOIN tbl_estado ON tbl_cidade.estado = tbl_estado.estado
                                            JOIN tbl_os ON tbl_os.revenda = tbl_revenda.revenda AND tbl_os.os = {$os}
                                            LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os
                                            WHERE tbl_os.os = {$os} AND tbl_os.fabrica = $login_fabrica";
                            $res_revenda = pg_query($con, $sql_revenda);

                            if(pg_num_rows($res_revenda) > 0){
                                $campos_adicionais_fantasia = pg_fetch_result($res_revenda, 0, 'revenda_fantasia');
                                if (!empty($campos_adicionais_fantasia)) {
                                    $nome_fantasia = json_decode($campos_adicionais_fantasia, true);
                                    $nome_fantasia = $nome_fantasia['revenda_fantasia'];
                                }
                                $uf_revenda = pg_fetch_result($res_revenda, 0, 'estado');
                            }
                        }

                        if ($login_fabrica == 158) {
							$json_kof                      = pg_fetch_result($resxls, $x, "json_kof");
							$json_kof                      = json_decode($json_kof, true);
							$cliente_admin_nome            = pg_fetch_result($resxls, $x, "cliente_admin_nome");
							$geolocalizacao                = pg_fetch_result($resxls, $x, "geolocalizacao");
							$familia_produto               = pg_fetch_result($resxls, $x, "familia_produto");
							$serie_justificativa           = pg_fetch_result($resxls, $x, "serie_justificativa");
							$consumidor_endereco           = pg_fetch_result($resxls, $x, "consumidor_endereco");
							$consumidor_bairro             = pg_fetch_result($resxls, $x, "consumidor_bairro");
							$consumidor_cep                = pg_fetch_result($resxls, $x, "consumidor_cep");
							$arquivo_kof                   = pg_fetch_result($resxls, $x, "arquivo_kof");
							$data_integracao               = pg_fetch_result($resxls, $x, "data_integracao");
							$campos_adicionais             = pg_fetch_result($resxls, $x, "campos_adicionais");
							$campos_adicionais             = json_decode($campos_adicionais, true);
                            $pdv_chegada                   = (!empty(trim($campos_adicionais["pdv_chegada"]))) ? $campos_adicionais["pdv_chegada"] : "";
                            $pdv_saida                     = (!empty(trim($campos_adicionais["pdv_saida"]))) ? $campos_adicionais["pdv_saida"] : "";
							$exportado                     = pg_fetch_result($resxls, $x, "exportado");
							$descricao_tipo_atendimento    = pg_fetch_result($resxls, $x, "descricao");
							$unidade_negocio               = pg_fetch_result($resxls, $x, "unidade_negocio");
							$distribuidor_principal        = pg_fetch_result($resxls, $x, "distribuidor_principal");
							$defeito_reclamado             = pg_fetch_result($resxls, $x, "defeito_reclamado");
							$inicio_atendimento            = pg_fetch_result($resxls, $x, "inicio_atendimento");
							$termino_atendimento           = pg_fetch_result($resxls, $x, "termino_atendimento");
							$admin_nome                    = pg_fetch_result($resxls, $x, "admin_nome");
							$regiao_distribuidor_principal = pg_fetch_result($resxls, $x, "regiao_distribuidor_principal");
							$defeitos_constatados          = pg_fetch_result($resxls, $x, "defeitos_constatados");
							$solucoes                      = pg_fetch_result($resxls, $x, "solucoes");
                            $tipo_posto                    = pg_fetch_result($resxls,$x,"tipo_posto");
                            $classificacao                 = pg_fetch_result($resxls,$x,"classificacao");
                            $data_inicio_atendimento       = pg_fetch_result($resxls,$x,"inicio_atendimento");
                            $auditorias_pendentes          = pg_fetch_result($resxls,$x,"auditorias_pendentes");

							$geolocalizacao = json_decode($geolocalizacao,true);
							$latitude = $geolocalizacao['lat'];
							$longitude = $geolocalizacao['lng'];
                            $xUnidadeNegocio = $oDistribuidorSLA->SelectUnidadeNegocioNotIn(null,null,$campos_adicionais['unidadeNegocio']);
                            $xxUnidadeNegocio = $xUnidadeNegocio[0]["cidade"];


						}

                        if($login_fabrica == 115){
                            $familia_produto    = pg_fetch_result($resxls,$x,"familia_produto");
                            $defeito_reclamado  = pg_fetch_result($resxls,$x,"defeito_reclamado");
                            $defeito_constatado = pg_fetch_result($resxls,$x,"defeito_constatado");
                        }

                        if($login_fabrica == 165){
                            $produto_trocado = pg_fetch_result($resxls, $x, 'produto_trocado');

                            unset($label_defeito_constato);
                            $aux_sql = "
                                SELECT descricao AS defeito_constatado
                                FROM tbl_defeito_constatado
                                JOIN tbl_os ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                                WHERE tbl_os.os = $os
                            ";
                            $aux_res = pg_query($con, $aux_sql);

                            $label_defeito_constato = pg_fetch_result($aux_res, 0, 'defeito_constatado');

                        }

						if(in_array($login_fabrica,array(115,116,117,120))){
							$valor_km = trim(pg_fetch_result($resxls,$x,valor_km));
						}

                        if(in_array($login_fabrica,array(164))){
                            $defeito_constatado                 = pg_fetch_result($resxls,$x,"defeito_constatado");
                            $segmento_atuacao                 = pg_fetch_result($resxls,$x,"segmento_atuacao");
                        }
						if (in_array($login_fabrica, array(141,144,151,153))) {
							$tipo_atendimento = pg_fetch_result($resxls, $x, tipo_atendimento);
							$sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
							$res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);
							$desc_tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');
						}
						$tempo_para_defeito = trim(pg_fetch_result($resxls,$x,tempo_para_defeito));

						unset($marca_reincidencia);
						if ($reincidencia =='t' and $login_fabrica != 1) {
							if($login_fabrica == 87) $cor = "#40E0D0"; elseif($login_fabrica == 30) $cor = "#5F9EA0"; else $cor = "#D7FFE1";
							$marca_reincidencia = 'sim';
						}
						if ($login_fabrica==20 AND $status_os == "94" AND $excluida == "t"){
							$cor = "#CACACA";
						}
						$vintecincodias = "";

						if ($login_fabrica == 91 && $status_os == 179) {
							$cor="#FFCCCC";
						}

						if ($login_fabrica == 91 && $status_os == 13) {
							$cor = "#CB82FF";
						}

						if($login_fabrica == 114){
							if ($status_os == "62") {
								$cor = ($login_fabrica == 114) ? "#FFCCCC" : "#E6E6FA";
							}
						}
						if (in_array($login_fabrica,array(3,11,43,51,87,172))) {

							if ($status_os == "62") {
								$cor = ($login_fabrica==43 or $login_fabrica==51) ? "#FFCCCC" : "#E6E6FA"; //HD 46730 HD 288642
							}
							if (in_array($status_os,array("72","87","116","120","122","140","141"))){
								$cor="#FFCCCC";
							}

							if($login_fabrica == 87 AND ($cor == "#FFCCCC" OR $cor == "#E6E6FA")) {
								$cor = "#FFA5A4";
							}

							if (($status_os=="64" OR $status_os=="73"  OR $status_os=="88" OR $status_os=="117") && strlen($fechamento)==0) {
								if($login_fabrica == 87){
									$cor = "#FEFFA4";
								}else{
									$cor = "#00EAEA";
								}
							}
							if ($status_os=="65"){
								$cor="#FFFF99";
							}
						}

						if (in_array($login_fabrica, array(141,144))) {
							switch ($status_os) {
							case 192:
								$cor = "#FFCCCC";
								break;

							case 193:
								$cor = "#CCFFFF";
								break;

							case 194:
								$cor = "#CB82FF";
								break;
							}
						}

						if ($login_fabrica==94){
							$sqlI = "SELECT status_os
								FROM    tbl_os_status
								WHERE   os = $os
								AND     status_os IN (62,64)
								ORDER BY      data DESC
								LIMIT   1";
							$resI = pg_query ($con,$sqlI);
							if (pg_num_rows ($resI) > 0){
								$status_os = trim(pg_fetch_result($resI,0,status_os));
								if ($status_os <> 64) {
									$cor="#FFCCCC";
								}
							}
						}

						if ($login_fabrica == 3 && $status_os == 174) {
							$cor = "#CB82FF";
						}

						if($status_os == "175"){
							$cor = "#A4A4A4";
						}


						// OSs abertas há mais de 25 dias sem data de fechamento
						if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica != 14) {
							$aux_abertura = fnc_formata_data_pg($abertura);

							$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '".(($login_fabrica == 91) ? "30" : "25")." days','YYYY-MM-DD')";
							$resX = pg_query ($con,$sqlX);
							$aux_consulta = pg_fetch_result($resX,0,0);

							$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
							$resX = pg_query ($con,$sqlX);
							$aux_atual = pg_fetch_result ($resX,0,0);

							if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
								if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
								$vintecincodias = "sim";
							}
						}
						// OSs abertas há mais de 10 dias sem data de fechamento - Nova
						if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 43) {
							$aux_abertura = fnc_formata_data_pg($abertura);

							$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '10 days','YYYY-MM-DD')";
							$resX = pg_query ($con,$sqlX);
							$aux_consulta = pg_fetch_result($resX,0,0);

							$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
							$resX = pg_query ($con,$sqlX);
							$aux_atual = pg_fetch_result ($resX,0,0);

							if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
								$cor = "#FF0033";
							}
						}

						// CONDIÇÕES PARA INTELBRÁS - INÍCIO
						if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 14) {
							$aux_abertura = fnc_formata_data_pg($abertura);

							$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '3 days','YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_consulta = pg_fetch_result($resX,0,0);

							$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_atual = pg_fetch_result($resX,0,0);

							if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
								if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
							}

							$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_consulta = pg_fetch_result($resX,0,0);

							$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
							$resX = pg_query ($con,$sqlX);
							$aux_atual = pg_fetch_result ($resX,0,0);

							if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
						}
						// CONDIÇÕES PARA INTELBRÁS - FIM

						// CONDIÇÕES PARA COLORMAQ - INÍCIO
						if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 50) {
							$aux_abertura = fnc_formata_data_pg($abertura);

							$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_consulta = pg_fetch_result($resX,0,0);

							$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_atual = pg_fetch_result($resX,0,0);

							if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
								if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
							}

							$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '10 days','YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_consulta = pg_fetch_result($resX,0,0);

							$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
							$resX = pg_query ($con,$sqlX);
							$aux_atual = pg_fetch_result ($resX,0,0);

							if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
								$cor = "#FF6633";
							}

							$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_consulta = pg_fetch_result($resX,0,0);

							$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
							$resX = pg_query ($con,$sqlX);
							$aux_atual = pg_fetch_result ($resX,0,0);

							if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
								$cor = "#FF0000";
							}
						}

						if($excluida=='t' AND ($login_fabrica==50 or $login_fabrica ==14)){//HD 37007 5/9/2008
							$cor = "#FFE1E1";
						}
						// CONDIÇÕES PARA COLORMAQ - FIM

						// CONDIÇÕES PARA NKS - INÍCIO
						if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 45) {
							$aux_abertura = fnc_formata_data_pg($abertura);

							$sqlX = "SELECT TO_CHAR(current_date - INTERVAL '15 days','YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_consulta = pg_fetch_result($resX,0,0);
							$sqlX = "SELECT TO_CHAR($aux_abertura::date,'YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_consulta2 = pg_fetch_result($resX,0,0);

							if ($aux_consulta < $aux_consulta2 && strlen($fechamento) == 0) $cor = "#1e85c7";

							$sqlX = "SELECT TO_CHAR(current_date - INTERVAL '15 days','YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_consulta = pg_fetch_result($resX,0,0);

							$sqlX = "SELECT TO_CHAR(current_date - INTERVAL '25 days','YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_consulta2 = pg_fetch_result($resX,0,0);

							$sqlX = "SELECT TO_CHAR($aux_abertura::date,'YYYY-MM-DD')";
							$resX = pg_query ($con,$sqlX);
							$aux_consulta3 = pg_fetch_result ($resX,0,0);

							if ($aux_consulta2 <= $aux_consulta3 AND $aux_consulta3 <= $aux_consulta && strlen($fechamento) == 0) $cor = "#FF6633";

							$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_consulta = pg_fetch_result($resX,0,0);

							$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
							$resX = pg_query ($con,$sqlX);
							$aux_consulta2 = pg_fetch_result ($resX,0,0);

							if ($aux_consulta < $aux_consulta2 && strlen($fechamento) == 0) $cor = "#9512cc";
						}
						// CONDIÇÕES PARA NKS - FIM


						//HD 163220 - Colocar legenda nas OSs com atendimento Procon/Jec (Jurídico) - tbl_hd_chamado.categoria='procon'
						if ($login_fabrica == 11 or $login_fabrica == 172) {
							$sql_procon = "
								SELECT
								tbl_hd_chamado.hd_chamado

								FROM
								tbl_hd_chamado
								JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado

								WHERE
								tbl_hd_chamado_extra.os=$os
								AND tbl_hd_chamado.categoria IN ('pr_reclamacao_at', 'pr_info_at', 'pr_mau_atend', 'pr_posto_n_contrib', 'pr_demonstra_desorg', 'pr_bom_atend', 'pr_demonstra_org')
								";
							$res_procon = pg_query($con, $sql_procon);

							if (pg_num_rows($res_procon)) {
								$cor = "#C29F6A";
							}
						}

						// Verifica se está sem fechamento há 20 dias ou mais da data de abertura
						if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
							$aux_abertura = fnc_formata_data_pg($abertura);

							$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_consulta = pg_fetch_result($resX,0,0);

							$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$aux_atual = pg_fetch_result($resX,0,0);

							if ($consumidor_revenda != "R") {
								if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
									$mostra_motivo = 1;
									if($login_fabrica == 87){
										$cor = "#A4B3FF";
									}else{
										$cor = "#91C8FF";
									}
								}
							}
						}

						if (!empty($os)) {
							$sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os ORDER BY data desc limit 1 ";
							$resX = @pg_query($con,$sqlX);

							if(@pg_num_rows($resX)==1){
								$cor = (pg_fetch_result($resX,0,ressarcimento)=='t') ? "#CCCCFF" : "#FFCC66";
							}
						}
						// CONDIÇÕES PARA BLACK & DECKER - INÍCIO
						// Verifica se não possui itens com 5 dias de lançamento
						if ($login_fabrica == 1) {
							$aux_abertura = fnc_formata_data_pg($abertura);

							$sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
							$resX = pg_query($con,$sqlX);
							$data_hj_mais_5 = pg_fetch_result($resX,0,0);

							$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
							$resX = pg_query ($con,$sqlX);
							$aux_consulta = pg_fetch_result($resX,0,0);

							$sql = "SELECT COUNT(tbl_os_item.*) AS total_item
								FROM tbl_os_item
								JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
								JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
								WHERE tbl_os.os = $os
								AND   tbl_os.data_abertura::date >= '$aux_consulta'";
							$resItem = pg_query($con,$sql);

							$itens = pg_fetch_result($resItem,0,total_item);

							if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#ffc891";

							$mostra_motivo = 2;
						}
						// CONDIÇÕES PARA BLACK & DECKER - FIM

						// Gama
						if ($login_fabrica==51){ // HD 65821
							$sqlX = "SELECT status_os,os FROM tbl_os JOIN tbl_os_status USING(os) WHERE os = $os AND status_os = 13";
							$resX = pg_query($con,$sqlX);
							if(pg_num_rows($resX)> 0){
								$cor = "#CACACA";
							}
						}

						if ($login_fabrica == 94 AND strlen($os) > 0) {

							$sqlT = "SELECT os FROM tbl_os_campo_extra WHERE os = $os";
							$resT = pg_query($con, $sqlT);

							if (pg_num_rows($resT)) {
								$cor = "silver";
							}

						}

						//HD 211825: Novo status de OS de Troca criado: Autorização para Troca pela Revenda, somente Salton
						if ($fabrica_autoriza_troca_revenda && strlen($os)) {
							$sql = "
								SELECT
								troca_revenda

								FROM
								tbl_os_troca

								WHERE
								os=$os
								";
							$res_troca_revenda = pg_query($con, $sql);

							if (pg_num_rows($res_troca_revenda)) {
								$troca_revenda = pg_result($res_troca_revenda, 0, troca_revenda);
							}
							else {
								$troca_revenda = "";
							}
						}

						if ($troca_revenda == 't') {
							$cor = "#d89988";
						}

						if ($vintecincodias == 'sim' and $marca_reincidencia == 'sim') {
							if($login_fabrica == 87) $cor = "#D2D2D2"; else $cor = "#CC9900";
						}

						// CONDIÇÕES PARA GELOPAR - INÍCIO
						if($login_fabrica==85 AND strlen($os)>0){
							$sqlG = "SELECT
								interv.os
								FROM (
									SELECT
									ultima.os,
									(
										SELECT status_os
										FROM tbl_os_status
										WHERE status_os IN (147)
										AND tbl_os_status.os = ultima.os
										ORDER BY data
										DESC LIMIT 1
									) AS ultimo_status
									FROM (
										SELECT os FROM tbl_os WHERE tbl_os.os = $os
									) ultima
								) interv
								WHERE interv.ultimo_status IN (64,147);";
							#echo nl2br($sqlG);
							$resG = pg_exec($con,$sqlG);

							if(pg_numrows($resG)>0){
								$cor = "#AEAEFF";
							}
						}
						// CONDIÇÕES PARA GELOPAR - FIM

						##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - FIM #####

						if (strlen($sua_os) == 0){
							$sua_os = $os;
						}
						if ($login_fabrica == 1) {
							$sua_os2 = $codigo_posto.$sua_os;
                            $aux_sua_os = $sua_os2;
							$sua_os = "<a href='etiqueta_print.php?os=$os' target='_blank'>" . $codigo_posto.$sua_os . "</a>";
						}

						//HD391024
						if($login_fabrica == 96 and strlen($btn_acao_pre_os) > 0){
							if($tipo_atendimento == '92') $cor = "#FFFF66";
							if($tipo_atendimento == '93') $cor = "#C94040";
							if($tipo_atendimento == '94') $cor = "#33CC00";
						}

						if($login_fabrica == 40 AND $status_os == 118){
							$cor = "#BFCDDB";
						}

						if (in_array($status_os, array(158))){
							$cor="#FFCCCC";
						}

						if ($login_fabrica == 45){
							//INTERACAO

							$sqlxyz = "SELECT count(*) from tbl_os_interacao where os = $os";
							$resxyz = pg_query($con,$sqlxyz);
							$count_interacao = pg_fetch_result($resxyz, 0, 0);
							if ($count_interacao > 0){

								if (strlen(trim($campo_interacao))==0){
									$cor = "#F98BB2";
								}
							}

							if ($tipo_os != 'INTERACAO'){

								//OS TROCA - RESOLVIDO
								$sqlaaa = "SELECT tbl_os.os from tbl_os join tbl_os_troca using(os) join tbl_faturamento_item using(pedido,peca) where tbl_os.os=$os";
								$resaaa = pg_query($con,$sqlaaa);

								if (pg_num_rows($resaaa)>0){
									$cor = "#56BB71";
								}

								//OS TROCA - PENDENTE
								$sqlbbb = "SELECT tbl_os.os from tbl_os join tbl_os_troca using(os) left join tbl_faturamento_item using(pedido,peca) where tbl_os.os=$os and tbl_faturamento_item.faturamento_item is null and tbl_os_troca.ressarcimento is false ";
								$resbbb = pg_query($con,$sqlbbb);

								if (pg_num_rows($resbbb)>0){
									$cor = "#EAEA1E";
								}
							}
						}

                            if ($formato_excel == "xls") {
                                $aux_exc_1 = "<td>";
                                $aux_exc_2 = "</td>";
                                $aux_exc_3 = "";

                                fputs ($fp,"<tr align='left'>");
                            } else {
                                $aux_exc_1 = "";
                                $aux_exc_2 = ";";
                                $aux_exc_3 = ";";
                            }

    						if ($login_fabrica == 158) {
    							fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",strtoupper($xxUnidadeNegocio))."$aux_exc_2");
    						}

                            if ($login_fabrica == 1) {
                                fputs ($fp,"{$aux_exc_1}$aux_sua_os{$aux_exc_2}");
                            } else {
								
                                fputs ($fp,"{$aux_exc_1}$sua_os{$aux_exc_2}");
			    }

			    if($login_fabrica == 151){
				    fputs ($fp,"{$aux_exc_1}".$desc_tipo_atendimento."{$aux_exc_2}");
			    }

    						if ($login_fabrica == 158) {
    							fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$auditorias_pendentes)."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$cliente_admin_nome)."$aux_exc_2");
    						}

                            if ($telecontrol_distrib) {
                                $pedido_tc = pg_fetch_result($resxls, $x, 'pedido_tc');
                                fputs ($fp,"{$aux_exc_1}$pedido_tc{$aux_exc_2}");
                            }

    						if($login_fabrica == 138){ //hd_chamado=2439865
    							$pedido = pg_fetch_result($resxls, $x, 'pedido');
    							fputs ($fp,"{$aux_exc_1}$pedido{$aux_exc_2}");
    						}

    						if ($login_fabrica == 52) {
    							fputs ($fp,"{$aux_exc_1}$hd_chamado{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}$cliente_fricon{$aux_exc_2}");
    							fputs ($fp,"{$aux_exc_1}$numero_ativo_res{$aux_exc_2}");
    						}
    						if($login_fabrica == 35){
    							fputs ($fp,"{$aux_exc_1}$hd_chamado{$aux_exc_2}");
    						}

    						if(in_array($login_fabrica, array(152,180,181,182))) {
    							$entrega_tecnica = "";

    							$sql = "SELECT tbl_tipo_atendimento.tipo_atendimento FROM tbl_os
    								INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
    								AND tbl_tipo_atendimento.entrega_tecnica IS TRUE
    								WHERE tbl_tipo_atendimento.fabrica = {$login_fabrica} AND tbl_os.os = {$os}";
    							$resEntregaTecnica = pg_query($con, $sql);

    							if(pg_num_rows($resEntregaTecnica) > 0){
    								$entrega_tecnica = traduz("ENTREGA TÉCNICA");
    							}else{
    								$entrega_tecnica = traduz("REPARO");
    							}

    							fputs ($fp,"{$aux_exc_1}$entrega_tecnica{$aux_exc_2}");
    						}

    						if ($login_fabrica == 158) {
    							fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$serie_justificativa)."$aux_exc_2");
    						}

    						if ($login_fabrica == 50) {
    							fputs ($fp,"{aux_exc_1}{$serie}C{$aux_exc_2}");
    						}else if(!in_array($login_fabrica,array(1,3,20,50,81,138,145,160)) and !$replica_einhell){ //hd_chamado=2439865
                                fputs ($fp,"{$aux_exc_1}$serie{$aux_exc_2}");
    						}
    						if($login_fabrica == 104){
    							if(!empty($sua_os)){
    								$sql_dias_aberto = "SELECT data_abertura::date - CASE WHEN data_fechamento::date IS NULL THEN DATE(NOW()) ELSE data_fechamento::date END AS dias_aberto FROM tbl_os WHERE os = {$os}";
    								$res_dias_aberto = pg_query($con, $sql_dias_aberto);

    								if(pg_num_rows($res_dias_aberto) == 1){
    									$dias_aberto = pg_fetch_result($res_dias_aberto, 0, 'dias_aberto');
    									$dias_aberto = str_replace("-", "", $dias_aberto);
    								}

    							}

    							fputs ($fp,"{$aux_exc_1}$dias_aberto{$aux_exc_2}");

    						}

    						if($login_fabrica == 160 or $replica_einhell){
    							fputs ($fp,"{$aux_exc_1}$serie{$aux_exc_2}");
    							fputs ($fp,"{$aux_exc_1}$type{$aux_exc_2}");
    						}

    						fputs ($fp,"{$aux_exc_1}$abertura{$aux_exc_2}");

                            if (in_array($login_fabrica, array(169,170))) {
                                fputs ($fp,"{$aux_exc_1}".mostra_data($xprimeira_data_agendamento)."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".mostra_data($xdata_confirmacao)."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".mostra_data($xultima_data)."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".$xinspetor_sap."{$aux_exc_2}");
                            }

                            if ($login_fabrica == 115) {
                                $sqlStatusEntradaIntervencao = "
                                    SELECT  TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') AS data_entrada
                                    FROM    tbl_os_status
                                    WHERE   os = $os
                                    AND     status_os IN (62)
                              ORDER BY      data DESC
                                    LIMIT   1
                                ";
                                $resStatusEntradaIntervencao = pg_query($con,$sqlStatusEntradaIntervencao);

                                if(pg_num_rows($resStatusEntradaIntervencao)>0){
                                    $dataEntradaIntervencao = pg_fetch_result($resStatusEntradaIntervencao,0, 'data_entrada');
                                }else{
                                    $dataEntradaIntervencao = null;
                                }


                                $sqlStatusLiberacao = "
                                    SELECT  TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') AS data_liberacao
                                    FROM    tbl_os_status
                                    WHERE   os = $os
                                    AND     status_os IN (64,99,100,139,155)
                              ORDER BY      data DESC
                                    LIMIT   1
                                ";
                                $resStatusLiberacao = pg_query($con,$sqlStatusLiberacao);

                                $sqlStatusReprova = "
                                    SELECT  TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') AS data_reprova
                                    FROM    tbl_os_status
                                    WHERE   os = $os
                                    AND     status_os IN (15,13,81)
                              ORDER BY      data DESC
                                    LIMIT   1
                                ";
                                $resStatusReprova = pg_query($con,$sqlStatusReprova);

                                $data_liberacao = pg_fetch_result($resStatusLiberacao,0,data_liberacao);
                                $data_reprova = pg_fetch_result($resStatusReprova,0,data_reprova);

                                fputs ($fp,"{$aux_exc_1}".$dataEntradaIntervencao."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".$data_liberacao."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".$data_reprova."{$aux_exc_2}");


                            }

                            if ($login_fabrica == 115) {
                                $sqlFatura = "
                                    SELECT TO_CHAR(MAX(tbl_faturamento.emissao),'DD/MM/YYYY') AS nf_emissao
                                    FROM tbl_faturamento
                                    LEFT JOIN tbl_faturamento_item USING(faturamento)
                                    LEFT JOIN tbl_pedido_item USING(pedido_item,peca)
                                    LEFT JOIN tbl_os_item USING(pedido_item,peca)
                                    LEFT JOIN tbl_os_produto USING(os_produto)
                                    WHERE tbl_os_produto.os = $os;
                                ";
                                $resFatura = pg_query($con,$sqlFatura);
                                $nf_emissao = pg_fetch_result($resFatura,0,nf_emissao);
                                fputs ($fp,"{$aux_exc_1}".$nf_emissao."{$aux_exc_2}");
                            }


                            if($login_fabrica == 6) fputs ($fp,"{$aux_exc_1} $data_nf {$aux_exc_2}");

    						if($login_fabrica == 138){ //hd_chamado=2439865
    							$data_digitacao = pg_fetch_result($resxls, $x, 'digitacao');
    							fputs ($fp,"{$aux_exc_1}".$data_digitacao."{$aux_exc_2}");
    						}

    						if (in_array($login_fabrica,array(115,158))) {
    							fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$s)."$aux_exc_2");
    						}

    						if($mostra_data_conserto) {
    							fputs ($fp,"{$aux_exc_1}".$data_conserto."{$aux_exc_2}");
    							fputs ($fp,"{$aux_exc_1}".$fechamento."{$aux_exc_2}");
    						}else{
                               fputs ($fp,"{$aux_exc_1}".$fechamento."{$aux_exc_2}");
    						}

                            if(in_array($login_fabrica, array(153,174,175,176))){
    							fputs ($fp,"{$aux_exc_1}".$desc_tipo_atendimento."{$aux_exc_2}");
    						}

                             if(in_array($login_fabrica, array(177))){
                                $x_tipo_atendimento = pg_fetch_result($resxls, $x, 'tipo_atendimento');
                                if(!empty($x_tipo_atendimento)){
                                    $sql_x_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $x_tipo_atendimento";
                                    $res_x_tipo_atendimento = pg_query($con, $sql_x_tipo_atendimento);
                                    $desc_x_tipo_atendimento = pg_fetch_result($res_x_tipo_atendimento,0,'descricao');
                                }else{
                                    $desc_x_tipo_atendimento = "";
                                }
                                fputs ($fp,"{$aux_exc_1}{$desc_x_tipo_atendimento}{$aux_exc_2}");
                            }

    						if ($login_fabrica == 158) {
                                
                                  $sql = "SELECT TO_CHAR(MIN(tbl_os_item.digitacao_item),'DD/MM/YYYY') AS data_lancamento_peca
                                          FROM tbl_os_item
                                            JOIN tbl_os_produto USING(os_produto)
                                            JOIN tbl_pedido USING(pedido)
                                            JOIN tbl_tipo_pedido USING(tipo_pedido)
                                            JOIN tbl_pedido_item USING(pedido_item)
                                          WHERE tbl_os_produto.os = $os 
                                                AND tbl_pedido.fabrica = $login_fabrica 
                                                AND tbl_tipo_pedido.fabrica = $login_fabrica 
                                                AND tbl_pedido_item.qtde > tbl_pedido_item.qtde_cancelada 
                                                AND tbl_tipo_pedido.descricao = 'NTP'";

                                $res_query = pg_query($con, $sql);
                                $count_res = pg_num_rows($res_query);

                                if($count_res > 0){
                                    $data_lancamento_peca = pg_fetch_result($res_query,0,'data_lancamento_peca');
                                } 

                                if (empty($login_cliente_admin)) {
    							    fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$json_kof["dataAbertura"])."{$aux_exc_2}");
                                }
    							fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$inicio_atendimento)."{$aux_exc_2}");
    							fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$termino_atendimento)."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$data_lancamento_peca)."{$aux_exc_2}");
    						}


    						if($login_fabrica <> 138){
    							if (!in_array($login_fabrica, array(141,144))) {
    								switch ($consumidor_revenda) {
    								case "C":
    									$auxiliar = "{$aux_exc_1}CONS{$aux_exc_2}";
    									break;

    								case "R":
    									$auxiliar = "{$aux_exc_1}REV{$aux_exc_2}";
    									break;

    								case "":
    									$auxiliar = "{$aux_exc_1}{$aux_exc_2}";
    									break;
    								}

                                    fputs ($fp,"$auxiliar");
    							} else {
    								fputs ($fp,"{$aux_exc_1}$desc_tipo_atendimento{$aux_exc_2}");
    							}
    						}

                            if ($login_fabrica == 164) {
                                fputs ($fp,"{$aux_exc_1}".$nome_fantasia."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".$uf_revenda."{$aux_exc_2}");
                            }

                            if ($telecontrol_distrib || in_array($login_fabrica, [160])) {
                                $nf_tc           = pg_fetch_result($resxls, $x, 'nota_fiscal_tc');
                                $emissao_tc      = pg_fetch_result($resxls, $x, 'emissao_nf');
                                $codigo_rastreio = pg_fetch_result($resxls, $x, 'codigo_rastreio');
                                $data_entrega    = pg_fetch_result($resxls, $x, 'data_entrega');
                                $dias_entrega    = pg_fetch_result($resxls, $x, 'dias_entrega');
                                $xtransportadora = pg_fetch_result($resxls, $x, 'nome_transportadora');

                                fputs ($fp,"{$aux_exc_1}".$nf_tc."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".$emissao_tc."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".$xtransportadora."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".$codigo_rastreio."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".$data_entrega."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".$dias_entrega."{$aux_exc_2}");
                                if($telecontrol_distrib){
                                    $sql_dt_ag_conserto = "SELECT                                                             
                                                            tos.data_abertura,
                                                            ohc.data_input,
                                                            ohc.data_input::date - tos.data_abertura as dias_conserto
                                                            FROM tbl_os tos
                                                            JOIN tbl_os_historico_checkpoint ohc ON ohc.os = tos.os
                                                            WHERE tos.os = {$os}
                                                            AND ohc.tg_grava ILIKE 'fn_os%'
                                                            AND ohc.fabrica = {$login_fabrica}
                                                            AND ohc.status_checkpoint = 3
                                                            ORDER BY ohc.data_input DESC
                                                            LIMIT 1";

                                    $res_dt_ag_conserto     = pg_query($con, $sql_dt_ag_conserto);
                                    if(pg_num_rows($res_dt_ag_conserto) > 0 ){ 
                                        $data_input_conserto    = pg_fetch_result($res_dt_ag_conserto, 0, data_input);
                                        $dias_conserto          = pg_fetch_result($res_dt_ag_conserto, 0, dias_conserto);

                                        $data_input_conserto = date("d/m/Y", strtotime($data_input_conserto));
                                        fputs($fp,"{$aux_exc_1}".$data_input_conserto."{$aux_exc_2}");                                    

                                        $sql_qtde_dias_conserto = "SELECT                                                                 
                                                                    tos.data_digitacao,
                                                                    fc.data         
                                                                    FROM tbl_os tos       
                                                                    JOIN tbl_faturamento_item fi ON fi.os = tos.os 
                                                                    JOIN tbl_faturamento_correio fc ON fc.faturamento = fi.faturamento 
                                                                    WHERE tos.fabrica = {$login_fabrica}
                                                                    AND fc.situacao LIKE 'Objeto entregue ao destinat%'
                                                                    AND tos.os = {$os} 
                                                                    ORDER BY tos.data_digitacao DESC 
                                                                    LIMIT 1";                                         

                                        $res_qtde_dias_conserto = pg_query($con, $sql_qtde_dias_conserto);

                                        if(pg_num_rows($res_qtde_dias_conserto) > 0){
                                            fputs ($fp,"{$aux_exc_1}".$dias_entrega."{$aux_exc_2}");
                                        } else {                                                                                
                                            fputs($fp,"{$aux_exc_1}".$dias_conserto."{$aux_exc_2}");
                                        }
                                    } else {
                                        fputs($fp,"{$aux_exc_1} {$aux_exc_2}");
                                        fputs($fp,"{$aux_exc_1} {$aux_exc_2}");
                                    }

                                }
                            }

    						if(in_array($login_fabrica, array(152,180,181,182))){
    							fputs ($fp,"{$aux_exc_1}".$tempo_para_defeito."{$aux_exc_2}");
    						}

    						if ($login_fabrica == 158) {
    							fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$regiao_distribuidor_principal)."$aux_exc_2");
    							fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$distribuidor_principal)."{$aux_exc_2}");
    						}

                            if (in_array($login_fabrica, array(169,170))) {
                                fputs ($fp,"{$aux_exc_1}".$codigo_posto."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".$posto_nome."{$aux_exc_2}");
                            } else {
                              fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$codigo_posto)."-".str_replace("$aux_exc_3","",$posto_nome)."{$aux_exc_2}");
                            }
    						if ($login_fabrica == 158) {
                                fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$tipo_posto)."{$aux_exc_2}");
    							fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$consumidor_cep)."{$aux_exc_2}");
    							fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$consumidor_endereco)."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$consumidor_bairro)."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$consumidor_cidade)."{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}".str_replace("$aux_exc_3","",$consumidor_estado)."{$aux_exc_2}");
                            }

                            if ($login_fabrica != 158) {

                                fputs ($fp,"{$aux_exc_1}$cidade_posto_xls{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}$estado_posto_xls{$aux_exc_2}");
                            }

                            if ($login_fabrica == 164) {

                                $query_cpf_cnpj = "SELECT consumidor_revenda, revenda_cnpj, consumidor_cpf FROM tbl_oS WHERE os = $os";

                                $res_cpf_cnpj = pg_query($con, $query_cpf_cnpj);

                                if ( pg_num_rows($res_cpf_cnpj) > 0 ) {

                                    $cpf_cnpj = pg_fetch_object($res_cpf_cnpj);

                                    if ($cpf_cnpj->consumidor_revenda == 'R' || ($cpf_cnpj->consumidor_revenda == 'C' && $cpf_cnpj->consumidor_cpf == "") ) {

                                        fputs ($fp,"{$aux_exc_1}{$cpf_cnpj->revenda_cnpj}{$aux_exc_2}");
                                    } else {

                                        fputs ($fp,"{$aux_exc_1}{$cpf_cnpj->consumidor_cpf}{$aux_exc_2}");
                                    }
                                } else {

                                    fputs ($fp,"{$aux_exc_1} {$aux_exc_2}");
                                }
                            }

                            if ($login_fabrica == 52) { /*HD - 4304128*/
                                $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
                                $aux_res = pg_query($con, $aux_sql);
                                $aux_arr = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);

                                if (!empty($aux_arr["pais"])) {
                                    $pais_consumidor = $aux_arr["pais"];
                                } else {
                                    $pais_consumidor = "";
                                }

                                fputs ($fp,"{$aux_exc_1}$pais_consumidor{$aux_exc_2}");
                                unset($aux_sql, $aux_res, $aux_arr);
                            }

    						if ($login_fabrica == 11 or $login_fabrica == 172) {
    							fputs ($fp,"{$aux_exc_1}".$situacao_posto."{$aux_exc_2}");
    						}

                            if ($login_fabrica == 158 and empty($login_cliente_admin)) {
                                fputs ($fp,"$aux_exc_1".$json_kof["idCliente"]."$aux_exc_2");
                            }

                            if ($login_fabrica == 165 && $_POST['tecnico']) {
                                fputs ($fp,"$aux_exc_1".$nomeTecnico."$aux_exc_2");
    			            }

    						if ($login_fabrica == 163) {
    				                	fputs ($fp,"$aux_exc_1".$descricao_tipo_atendimento."$aux_exc_2");
    						}
    						if (!in_array($login_fabrica, array(169,170))) {
                            	fputs ($fp,"$aux_exc_1".$nome_consumidor_revenda."$aux_exc_2");
                        	}
                            if ($telecontrol_distrib){
                                fputs ($fp,"{$aux_exc_1}" . $cpf_cnpj . "{$aux_exc_2}");
                            }

                            if (in_array($login_fabrica, array(169,170))){
                                fputs ($fp,"$aux_exc_1".$revenda_nome."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".$revenda_cnpj_tec."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".$consumidor_nome."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".$consumidor_bairro."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".$consumidor_cidade."$aux_exc_2");
                            }

    						if ($login_fabrica == 158) {

                                fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$consumidor_fone)."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$consumidor_celular)."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$consumidor_email)."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$descricao_tipo_atendimento)."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$defeito_reclamado)."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$defeitos_constatados)."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$solucoes)."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$pdv_chegada)."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$pdv_saida)."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$classificacao)."$aux_exc_2");


                                $obs = str_replace(['"', "'"],"",$obs);
                                fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$obs)."$aux_exc_2");


    							if (empty($login_cliente_admin)) {
                                    fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$json_kof["comentario"])."$aux_exc_2");

                                }
    							fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$admin_nome)."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$admin_nome)."$aux_exc_2");

                                if (empty($login_cliente_admin)) {
    							    fputs ($fp,"$aux_exc_1".$json_kof["osKof"]."$aux_exc_2");
                                }
    						}

    						if (!in_array($login_fabrica, array(141,144)) && !isset($novaTelaOs)) {
                                fputs ($fp,"$aux_exc_1".$consumidor_fone."$aux_exc_2");
    						} else {
    							fputs ($fp,"$aux_exc_1".$nota_fiscal."$aux_exc_2");
    						}

    						if($login_fabrica == 138){ //hd_chamado=2439865
    							$data_nf        = trim(pg_fetch_result($resxls,$x,'data_nf'));
    							$nf             = pg_fetch_result($resxls, $x, 'nf_fat');
    							$data_nf_fat    =   pg_fetch_result($resxls, $x, 'nf_emissao');
    							fputs ($fp,"$aux_exc_1".$data_nf."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".$produto_referencia."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".$serie."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".$nf."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".$data_nf_fat."$aux_exc_2");
    						}


    						if($login_fabrica == 141){ // HD-2386867
    							$sqlEstados = "SELECT tbl_os.consumidor_estado,
    								tbl_cidade.estado,
    								tbl_posto_fabrica.contato_estado
    								FROM tbl_os
    								JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
    								JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
    								JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
    								JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade
    								WHERE os = $os
    								";
    							$resEstados = pg_query($con, $sqlEstados);
    							if(pg_num_rows($resEstados) > 0){
    								if($consumidor_revenda == "R" ){
    									$uf_consumidor = pg_fetch_result($resEstados, 0, 'estado');
    								}else{
    									$uf_consumidor = pg_fetch_result($resEstados, 0, 'consumidor_estado');
    								}

    								$uf_posto = pg_fetch_result($resEstados, 0, 'contato_estado');
    							}
    							fputs ($fp,"$aux_exc_1".$uf_consumidor."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".$uf_posto."$aux_exc_2");
    						}


    						if ($login_fabrica == 80) {
    							fputs ($fp,"$aux_exc_1".$data_nf."$aux_exc_2");
    						}
    						if($login_fabrica == 6){
                                $quebra_linha   = array('<br>', ';', '<br/>', '<br />', '\n', '\t', '\r\n', "\n", "\t" , "\r\n","\r", '\r');
                                $aux_consumidor_email = str_replace($quebra_linha, "", $consumidor_email);

                                fputs ($fp,"$aux_exc_1 $aux_consumidor_email $aux_exc_2");
                                fputs ($fp,"$aux_exc_1 $revenda_nome_tec $aux_exc_2");
                                fputs ($fp,"$aux_exc_1 $revenda_cnpj_tec $aux_exc_2");
                                fputs ($fp,"$aux_exc_1 $nota_fiscal $aux_exc_2");
    						}
    						if($login_fabrica == 50){
    							fputs ($fp,"$aux_exc_1".$consumidor_endereco."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".$consumidor_cidade."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".$consumidor_estado."$aux_exc_2");
    						}

                            if($login_fabrica == 171){
                                fputs ($fp,"$aux_exc_1".$produto_referencia_fabrica."$aux_exc_2");
                            }

                            if($login_fabrica <> 138 AND !$telecontrol_distrib){
                                fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$produto_referencia)."-".str_replace("$aux_exc_3","",$produto_descricao)."$aux_exc_2");
                            }

                            if ($login_fabrica == 94) {
                                fputs ($fp,"{$aux_exc_1}{$defeito_reclamado_os}{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}{$defeito_constatado_desc}{$aux_exc_2}");
                            }

                            if ($telecontrol_distrib) {

				    $referencia_peca       = pg_fetch_result($resxls, $x, 'referencia_peca');
				    $descricao_peca       = pg_fetch_result($resxls, $x, 'descricao_peca');
                                $qtde_componentes = pg_fetch_result($resxls, $x, 'qtde_componentes');

				fputs ($fp,"{$aux_exc_1}{$produto_referencia}{$aux_exc_2}");
				fputs ($fp,"{$aux_exc_1}{$produto_descricao}{$aux_exc_2}");
				fputs ($fp,"{$aux_exc_1}{$referencia_peca}{$aux_exc_2}");
				fputs ($fp,"{$aux_exc_1}{$descricao_peca}{$aux_exc_2}");
                                fputs ($fp,"{$aux_exc_1}{$qtde_componentes}{$aux_exc_2}");
                            }

                            if ($login_fabrica == 3) {
                                $aux_sql = "SELECT tbl_tipo_atendimento.descricao FROM tbl_tipo_atendimento JOIN tbl_os ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento AND tbl_os.fabrica = $login_fabrica WHERE tbl_os.os = $os";
                                $aux_res = pg_query($con, $aux_sql);
                                $aux_val = pg_fetch_result($aux_res, 0, 'descricao');

                                fputs ($fp,"{$aux_exc_1}".utf8_decode($aux_val)."{$aux_exc_2}");
                            }

    						if (in_array($login_fabrica,array(115,158))) {
    							fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$familia_produto)."$aux_exc_2");
    						}
    						if (in_array($login_fabrica,array(115))) {
                                fputs ($fp,"$aux_exc_1".$defeito_reclamado."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".$defeito_constatado."$aux_exc_2");
    						}

    						if((in_array($login_fabrica, array(152,180,181,182))) && $sem_listar_peca <> 1) {
    							fputs ($fp,"$aux_exc_1".$peca_descricao."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".$peca_qtde."$aux_exc_2");
    							fputs ($fp,"$aux_exc_1".$peca_referencia."$aux_exc_2");
    						}

    						if($login_fabrica == 85){

    							if(!empty($sua_os)){

    								$sql_dias_aberto = "SELECT data_abertura::date - CASE WHEN data_fechamento::date IS NULL THEN DATE(NOW()) ELSE data_fechamento::date END AS dias_aberto FROM tbl_os WHERE os = {$sua_os}";
    								$res_dias_aberto = pg_query($con, $sql_dias_aberto);

    								if(pg_num_rows($res_dias_aberto) == 1){
    									$dias_aberto = pg_fetch_result($res_dias_aberto, 0, 'dias_aberto');
    									$dias_aberto = str_replace("-", "", $dias_aberto." dia(s)");
    								}

    							}

    							fputs ($fp,"$aux_exc_1".$dias_aberto."$aux_exc_2");

    						}
    						if(in_array($login_fabrica, array(3,52,86)) or $multimarca =='t'){

    							if($login_fabrica ==52 and !empty($marca_logo)){
    								$sqlx="select nome from tbl_marca where marca = $marca_logo;";
    								$resx=pg_exec($con,$sqlx);
    								$marca_logo_nome         = pg_fetch_result($resx, 0, 'nome');

    								fputs ($fp,"$aux_exc_1".$marca_logo_nome."$aux_exc_2");
    							}else {
    								fputs ($fp,"$aux_exc_1".$marca_nome."$aux_exc_2");
    							}
    						}


    						if(in_array($login_fabrica,array(115,116,117,120))){
    							fputs ($fp,"$aux_exc_1".number_format($valor_km,2,',','.')."$aux_exc_2");
    						}

                            if(in_array($login_fabrica,array(164))){
                                fputs ($fp,"$aux_exc_1".$defeito_constatado."$aux_exc_2");
                                if ($login_fabrica == 164) {
                                    if (empty($segmento_atuacao)) {
                                        fputs ($fp,"{$aux_exc_1}{$aux_exc_2}");
                                    } else {
                                        fputs ($fp,"$aux_exc_1".$segmento_atuacao."$aux_exc_2");
                                    }
                                }
                            }

    						if($login_fabrica == 6){
    							$sql = "select orientacao_sac from tbl_os_extra where os = $os";
    							$res = pg_query($con, $sql);
    							if(pg_num_rows($res)>0){
    								$orientacao_sac = pg_fetch_result($res, 0, orientacao_sac);
    							}
    						}

    						if ($login_fabrica == 165) {
                                unset($serv);
                                $sqlSrv = "
                                    SELECT  DISTINCT
                                            tbl_servico_realizado.descricao,
                                            tbl_servico_realizado.troca_produto
                                    FROM    tbl_servico_realizado
                                    JOIN    tbl_os_item     USING (servico_realizado)
                                    JOIN    tbl_os_produto  USING(os_produto)
                                    WHERE   os = $os
                                ";
                                $resSrv = pg_query($con,$sqlSrv);

                                while ($servicos = pg_fetch_object($resSrv)) {
                                    if (!empty($servicos->descricao)) {
                                        $serv .= $servicos->descricao;

                                        if ($servicos->troca_produto == 't') {
                                            $sqlProd = "
                                                SELECT  tbl_produto.referencia,
                                                        tbl_produto.descricao
                                                FROM    tbl_produto
                                                JOIN    tbl_os_troca USING(produto)
                                                WHERE   tbl_os_troca.os = $os
                                                AND     fabric = $login_fabrica
                                                AND     fabrica_i = $login_fabrica

                                            ";
                                            $resProd = pg_query($con,$sqlProd);

                                            $serv .= " Por ".pg_fetch_result($resProd,0,referencia). " - ".pg_fetch_result($resProd,0,descricao);
                                        }
                                        $serv .= "<br />";
                                    } else {
                                        $serv .= "&nbsp;";
                                    }
                                }
                                fputs($fp,"$aux_exc_1".$serv."$aux_exc_2");
    						}


                            if($login_fabrica == 131){

                                $digitacaoItem = '';
                                $sqlDigitacao = "SELECT TO_CHAR(tbl_os_item.digitacao_item,'DD/MM/YYYY') AS digitacao_item,
                                      TO_CHAR(tbl_pedido_item.data_item,'DD/MM/YYYY')  as digitacao_pedido, tbl_faturamento.nota_fiscal, TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') as emissao_nf
                                    FROM tbl_os_item
                                    JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                    left join tbl_pedido_item on tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                                    left join tbl_faturamento_item on tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item
                                    left join tbl_faturamento on tbl_faturamento_item.faturamento = tbl_faturamento.faturamento and tbl_faturamento.fabrica = $login_fabrica
                                    WHERE tbl_os_produto.os = $os
                                    AND tbl_os_item.fabrica_i = $login_fabrica
                                    ORDER BY digitacao_item ASC LIMIT 1";
                                $resDigitacao = pg_query($con, $sqlDigitacao);

                                $digitacaoItem = "";
                                $dt_geracao_pedido = "";
                                $nf_peca = "";
                                $emissao_nf = "";

                                if(pg_num_rows($resDigitacao) > 0){
                                    $digitacaoItem = pg_fetch_result($resDigitacao, 0, 'digitacao_item');
                                    $dt_geracao_pedido = pg_fetch_result($resDigitacao, 0, 'digitacao_pedido');
                                    $nf_peca = pg_fetch_result($resDigitacao, 0, 'nota_fiscal');
                                    $emissao_nf = pg_fetch_result($resDigitacao, 0, 'emissao_nf');
                                }

								$dataReprova = '';
								$sqlReprovada = "SELECT TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') AS data_intervencao,
										tbl_os_status.status_os
										FROM tbl_os_status
										WHERE os = $os
										AND tbl_os_status.status_os = 203
										AND tbl_os_status.fabrica_status = $login_fabrica
										ORDER BY data DESC
										LIMIT 1";
								$resReprovada = pg_query ($con,$sqlReprovada);
								if (pg_num_rows ($resReprovada) > 0){
									$dataReprova = trim(pg_fetch_result($resReprovada,0,'data_intervencao'));
								} else {
									$sqlReprovada = "SELECT TO_CHAR(reprovada, 'DD/MM/YYYY') AS data_reprova
										FROM tbl_auditoria_os
										WHERE os = $os
										AND reprovada IS NOT NULL
										ORDER BY reprovada DESC LIMIT 1";
									$resReprovada = pg_query($con, $sqlReprovada);

									if (pg_num_rows($resReprovada) > 0) {
										$dataReprova = pg_fetch_result($resReprovada, 0, 'data_reprova');
									}
								}

				$previsao_chegada = (strlen($campos_adicionais->previsao_entrega) > 0) ? date("d/m/Y", strtotime($campos_adicionais->previsao_entrega)) : "";
                                fputs ($fp,"$aux_exc_1".$dataReprova."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".$digitacaoItem."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".$dt_geracao_pedido."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".$emissao_nf."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".$previsao_chegada."$aux_exc_2");
                            }


                            if($login_fabrica == 148 ){
                                
                                $produto_em_estoque = getProdutoEmGarantia($os);

                                fputs ($fp,"$aux_exc_1".$produto_em_estoque."$aux_exc_2");

                                $ordem_Pin = "";
                                $sqlns = "SELECT ordem from tbl_numero_serie where referencia_produto  = '$produto_referencia' and serie = '$serie'  and fabrica = $login_fabrica";
                                $resns = pg_query($con, $sqlns);
                                if(pg_num_rows($resns)>0){
                                    $ordem_Pin =pg_fetch_result($resns, 0, 'ordem');
                                }
                                fputs ($fp,"$aux_exc_1".$ordem_Pin."$aux_exc_2");
                            }

                            fputs ($fp,"$aux_exc_1".$array_cor_descricao[$status_checkpoint]."$aux_exc_2");

    						if ($login_fabrica == 158) {
                                if (empty($login_cliente_admin)) {
                                    fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$arquivo_kof)."$aux_exc_2");
                                    fputs ($fp,"$aux_exc_1".$data_integracao."$aux_exc_2");
                                    fputs ($fp,"$aux_exc_1".str_replace("$aux_exc_3","",$campos_adicionais["arquivo_saida_kof"])."$aux_exc_2");
                                    fputs ($fp,"$aux_exc_1".$exportado."$aux_exc_2");
                                    fputs ($fp,"$aux_exc_1".$longitude."$aux_exc_2");
                                    fputs ($fp,"$aux_exc_1".$latitude."$aux_exc_2");
                                } else {
                                    //fputs ($fp,"".$arquivo_kof."</td>");
                                    fputs ($fp,"$aux_exc_1".$data_integracao."$aux_exc_2");
                                    //fputs ($fp,"".$campos_adicionais["arquivo_saida_kof"]."</td>");
									fputs ($fp,"$aux_exc_1".$exportado."$aux_exc_2");
									fputs ($fp,"$aux_exc_1".$longitude."$aux_exc_2");
                                    fputs ($fp,"$aux_exc_1".$latitude."$aux_exc_2");

                                }

    						}

                            if($login_fabrica == 6){
                                $quebra_linha = array('<br>',';','<br/>', '<br />', '\n', '\t', '\r\n', "\n", "\t" , "\r\n","\r", '\r', '/', '..', '...');

                                $obs_decode            = html_entity_decode($obs);
                                $orientacao_sac_decode = html_entity_decode($orientacao_sac);

                                $obs            = str_replace($quebra_linha, "", $obs_decode);
                                $orientacao_sac = str_replace($quebra_linha, "", $orientacao_sac_decode);

                                fputs ($fp,"$aux_exc_1".$obs."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".$orientacao_sac."$aux_exc_2");
                            }

    						if ($login_fabrica == 96 && strlen($btn_acao_pre_os) > 0) {
    							switch ($cor) {
    							case '#C94040':
    								$aux_msg = "{$aux_exc_1}Retorno de garantia{$aux_exc_2}";
    								break;
    							case '#FFFF66':
    								$aux_msg = "{$aux_exc_1}Garantia{$aux_exc_2}";
    								break;
    							case '#33CC00':
    								$aux_msg = "{$aux_exc_1}Retorno de garantia{$aux_exc_2}";
    								break;
    							default:
    								$aux_msg = "{$aux_exc_1}{$aux_exc_2}";
    								break;
    							}
    						}else{
    							switch ($cor) {

    							case '#FFE1E1':
    								$aux_msg = "$aux_exc_1 ".traduz("Excluidas do sistema")." {$aux_exc_2}";
    								break;
    							case '#40E0D0':
    								$aux_msg = "$aux_exc_1 ".traduz("Reincidências")." {$aux_exc_2}";
    								break;
    							case ($login_fabrica == 30) ? '#5F9EA0' : '#D7FFE1':
    								$aux_msg = "$aux_exc_1 ".traduz("Reincidências")." {$aux_exc_2}";
    								break;
    							case '#ffc891':
    								$aux_msg = "$aux_exc_1 ".traduz("OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento")." {$aux_exc_2}";
    								break;
    							case '#FFCC66':
    								$aux_msg = "$aux_exc_1 ".traduz("OS com Troca de Produto")." {$aux_exc_2}";
    								break;
    							case '#FF0000':
    								if ($login_fabrica == 14) {
    									$aux_msg = "$aux_exc_1 OSs abertas há mais de 5 dias sem data de fechamento{$aux_exc_2}";
    								}elseif ($login_fabrica == 50 ) {
    									$aux_msg = "$aux_exc_1 OSs abertas há mais de 20 dias sem data de fechamento{$aux_exc_2}";
    								}elseif($login_fabrica ==35){
    									$aux_msg = "$aux_exc_1 Excluídas do sistema{$aux_exc_2}";
    								}elseif ($login_fabrica == 3 OR $login_fabrica == 11 or $login_fabrica == 172 OR $login_fabrica == 45) {
    									$aux_msg = "$aux_exc_1 Excluídas do sistema{$aux_exc_2}";
    								}elseif ($login_fabrica == 30 && strlen($btn_acao_pre_os) > 0) {
    									$aux_msg = "$aux_exc_1 OS Abertas a mais de 72 horas{$aux_exc_2}";
    								}
    								break;
    							case '#AEAEFF':
    								$aux_msg = "$aux_exc_1 ".traduz("Peça fora da garantia aprovada na intervenção da OS para gerar pedido")."{$aux_exc_2}";
    								break;
    							case '#91C8FF':
    								if ($login_fabrica == 14) {
    									$aux_msg = "$aux_exc_1 OSs abertas há mais de 3 dias sem data de fechamento{$aux_exc_2}";
    								}elseif ($login_fabrica == 50) {
    									$aux_msg = "$aux_exc_1 OSs abertas há mais de 5 dias sem data de fechamento{$aux_exc_2}";
    								}else{
    									$aux_msg = "$aux_exc_1 ".traduz("OSs abertas há mais de ").(($login_fabrica == 91) ? "30" : "25" ).traduz(" dias sem data de fechamento ")."{$aux_exc_2}";
    								}
    								break;
    							case '#FF6633':
    								if ($login_fabrica==50) {
    									$aux_msg = "{$aux_exc_1}OSs abertas há mais de 10 dias sem data de fechamento{$aux_exc_2}";
    								}elseif ($login_fabrica==45) {
    									$aux_msg = "{$aux_exc_1}MÉDIO (OSs abertas entre 15 dias e 25 dias sem data de fechamento);";
    								}
    								break;
    							case '#1e85c7':
    								$aux_msg = "{$aux_exc_1} ".traduz("BOM (OSs abertas até 15 dias sem data de fechamento)")." {$aux_exc_2}";
    								break;
    							case '#9512cc':
    								$aux_msg = "{$aux_exc_1} ".traduz("RUIM (OSs abertas a mais de 25 dias sem data de fechamento)")." {$aux_exc_2}";
    								break;

    							case '#FF0033':
    								$aux_msg = "{$aux_exc_1} ".traduz("OSs abertas há mais de 10 dias sem data de fechamento")." {$aux_exc_2}";
    								break;
    							case '#A4B3FF':
    								$aux_msg = "{$aux_exc_1} ".traduz("OSs abertas há mais de ").(($login_fabrica == 91) ? "30" : "25" ).traduz(" dias sem data de fechamento")." {$aux_exc_2}";
    								break;
    							case '#FFCCCC':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS com Intervenção da Fábrica. Aguardando Liberação")." {$aux_exc_2}";
    								break;
    							case '#FFA5A4':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS com Intervenção da Fábrica. Aguardando Liberação")." {$aux_exc_2}";
    								break;
    							case '#FFFF99':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS com Intervenção da Fábrica. Reparo na Fábrica")." {$aux_exc_2}";
    								break;
    							case '#FEFFA4':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS Liberada Pela Fábrica")." {$aux_exc_2}";
    								break;
    							case '#00EAEA':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS Liberada Pela Fábrica")." {$aux_exc_2}";
    								break;
    							case '#CCCCFF':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS com Ressarcimento")." {$aux_exc_2}";
    								break;
    							case '#CACACA':
    								if($login_fabrica == 51){
    									$aux_msg = "{$aux_exc_1}OS Recusada do extrato{$aux_exc_2}";
    								}else{
    									$aux_msg = "{$aux_exc_1} ".traduz("OS Reprovada pelo Promotor")." {$aux_exc_2}";
    								}
    								break;
    							case '#d89988':
    								$aux_msg = "{$aux_exc_1} ".traduz("Autorização de Devolução de Venda")." {$aux_exc_2}";
    								break;
    							case '#CC9900':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS reincidente e aberta a mais de 25 dias")." {$aux_exc_2}";
    								break;
    							case '#D2D2D2':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS reincidente e aberta a mais de 25 dias")." {$aux_exc_2}";
    								break;
    							case '#FFFF66':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS Abertas a mais de 24 horas e menos de 72 ho{")." $aux_exc_2}as;";
    								break;
    							case '#33CC00':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS Abertas a menos de 24 horas")." {$aux_exc_2}";
    								break;
    							case '#BFCDDB':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS com 3 ou mais peças")." {$aux_exc_2}";
    								break;
    							case '#silver':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS foi Aberta automaticamente por causa de ")."{$aux_exc_2}ma troca gerada;";
    								break;
    							case '#A4A4A4':
    								$aux_msg = "{$aux_exc_1} ".traduz("OS com intervenção de display")." {$aux_exc_2}";
    								break;
    							case '#CB82FF':
    								if (in_array($login_fabrica, array(141,144))) {
    									$aux_msg = "{$aux_exc_1}OS com troca de produto recusada{$aux_exc_2}";
    								}elseif ($login_fabrica == 3) {
    									$aux_msg = "{$aux_exc_1}OS com pendência de fotos{$aux_exc_2}";
    								}else{
    									$aux_msg = "{$aux_exc_1} ".traduz("OS recusada pela fábrica")." {$aux_exc_2}";
    								}
    								break;
    							case '#F98BB2':
    								$aux_msg = "{$aux_exc_1} ".traduz("Os com Interação do Posto")." {$aux_exc_2}";
    								break;
    							case '#56BB71':
    								$aux_msg = "{$aux_exc_1} ".traduz("Os com Troca de Produtos - Resolvidos")." {$aux_exc_2}";
    								break;
    							case '#EAEA1E':
    								$aux_msg = "{$aux_exc_1} ".traduz("Os com Troca de Produtos - Pendentes")." {$aux_exc_2}";
    								break;
    							default:
    								$aux_msg = "{$aux_exc_1}{$aux_exc_2}";
    								break;
    							}

                                fputs ($fp, $aux_msg);
    						}

                            if($login_fabrica == 165){
                                fputs ($fp,"$aux_exc_1".$produto_trocado."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".$label_defeito_constato."$aux_exc_2");
                            }

                            if (in_array($login_fabrica, array(152,180,181,182))) {
                                $aux_obs = pg_fetch_result($resxls, $x, 'obs');

                                unset($label_classificacao_nao_usar_excel);

                                $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
                                $aux_res = pg_query($con, $aux_sql);
                                $aux_add = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);

                                if (empty($aux_add["classificacao"])) {
                                    $label_classificacao_nao_usar_excel = "";
                                } else {
                                    $classificacoes = $aux_add["classificacao"];
                                    $aux_label_classificacao = array();

                                    foreach ($classificacoes as $classificacao_os) {
                                        switch ($classificacao_os) {
                                            case 'tecnico':
                                                $aux_label_classificacao[] = traduz("Técnico");
                                            break;

                                            case 'logistico':
                                                $aux_label_classificacao[] = traduz("Logístico");
                                            break;

                                            case 'comercial':
                                                $aux_label_classificacao[] = traduz("Comercial");
                                            break;

                                            default:
                                                $aux_label_classificacao[] = "";
                                            break;
                                        }
                                    }

                                    $label_classificacao_nao_usar_excel = implode(", ", $aux_label_classificacao);
                                }

                                fputs ($fp,"$aux_exc_1".$label_classificacao_nao_usar_excel."$aux_exc_2");
                                fputs ($fp,"$aux_exc_1".$aux_obs."$aux_exc_2");
                            }
                            
                            if( $login_fabrica == 90 ){
                                $cidadePosto = pg_fetch_result($resxls, $x, "contato_cidade");
                                $ufPosto = pg_fetch_result($resxls, $x, "contato_estado");

                                $pgResource = pg_query($con, "SELECT descricao FROM tbl_status_checkpoint WHERE status_checkpoint = {$status_checkpoint}");
                                $statusCheckpoint = pg_fetch_assoc($pgResource)['descricao'];

                                $sqlAdicional = "SELECT os.sua_os as os, os.fabrica, p.produto, peca.peca, peca.descricao, peca.referencia, item.pedido
                                                   FROM tbl_os os
                                             INNER JOIN tbl_os_produto p ON p.os = os.os
                                             INNER JOIN tbl_os_item item ON item.os_produto = p.os_produto
                                             INNER JOIN tbl_peca peca ON peca.peca = item.peca
                                                  WHERE os.os = {$os} AND os.fabrica = {$login_fabrica}";
                                $pgResource = pg_query($con, $sqlAdicional);

                                $result = pg_fetch_all($pgResource);
                                $count = count($result);

                                $contadorInterno = 1;
                                foreach($result as $item){
                                    if($contadorInterno == 1){
                                        fputs($fp, "{$aux_exc_1}{$item['referencia']}-{$item['descricao']}{$aux_exc_2}");
                                        fputs($fp, "{$aux_exc_1}{$item['pedido']}{$aux_exc_2}");

                                        if( $count == 1 ) continue;

                                        $formato_excel == 'xls' ? fputs($fp, '</tr>') : fputs ($fp, "\n");
                                        $contadorInterno++;
                                        continue;
                                    }
                                    
                                    $consumidorRevenda = ($consumidor_revenda == 'C' ? 'CONS' : 'REV');

                                    fputs($fp, "{$aux_exc_1}{$sua_os}{$aux_exc_2}");
                                    fputs($fp, "{$aux_exc_1}{$serie}{$aux_exc_2}");
                                    fputs($fp, "{$aux_exc_1}{$abertura}{$aux_exc_2}");
                                    fputs($fp, "{$aux_exc_1}{$fechamento}{$aux_exc_2}");
                                    fputs($fp, "{$aux_exc_1}{$consumidorRevenda}{$aux_exc_2}");
                                    fputs($fp, "{$aux_exc_1}{$codigo_posto}-{$posto_nome}{$aux_exc_2}");
                                    fputs($fp, "{$aux_exc_1}{$cidadePosto}{$aux_exc_2}");
                                    fputs($fp, "{$aux_exc_1}{$ufPosto}{$aux_exc_2}");
                                    fputs($fp, "{$aux_exc_1}{$consumidor_nome}{$aux_exc_2}");
                                    fputs($fp, "{$aux_exc_1}{$consumidor_fone}{$aux_exc_2}");
                                    fputs($fp, "{$aux_exc_1}{$produto_referencia}-{$produto_descricao}{$aux_exc_2}");
                                    fputs($fp, "{$aux_exc_1}{$statusCheckpoint}{$aux_exc_2}");
                                    fputs($fp, $aux_msg);
                                    fputs($fp, "{$aux_exc_1}{$item['referencia']}-{$item['descricao']}{$aux_exc_2}");
                                    fputs($fp, "{$aux_exc_1}{$item['pedido']}{$aux_exc_2}");
                                    
                                    if( $count != $contadorInterno ) $formato_excel == 'xls' ? fputs($fp, '</tr>') : fputs ($fp, "\n");
                                    
                                    $contadorInterno++;
                                }                                
                            }

                            if ($formato_excel == "xls"){
                                fputs ($fp,"</tr>");
                            } else {
                                fputs ($fp, "\n");
                            }
                    }

                    if ($formato_excel == "xls"){
                        fputs ($fp,"</table>");
                    }

                    if ($login_fabrica != 30) {

                        if ($formato_excel == "csv") {
                            $icon_excel  = "imagens/icon_csv.png";
                            $label_excel = traduz("Gerar Arquivo CSV");
                        } else {
                            $icon_excel = "imagens/excel.png";
                            $label_excel = traduz("Gerar Arquivo Excel");
                        }

                        $resposta = "<br>";
                        $resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
                        $resposta .="<tr>";
                        $resposta .= "<td align='center' style='border: 0; font: bold 14px \"Arial\";'><a href=\"$caminho_donwload\" target=\"_blank\" style=\"text-decoration: none; \"><img src=\"$icon_excel\" height=\"40px\" width=\"40px\" align=\"absmiddle\">&nbsp;&nbsp;&nbsp;<span class='txt'>$label_excel</span></a></td>";
                        $resposta .= "</tr>";
                        $resposta .= "</table>";
                        echo $resposta;
                        echo "<br/>";
                    }
				}
			}
        }

        $sua_os             = trim (strtoupper ($_POST['sua_os']));
        if (strlen($sua_os)==0) $sua_os = trim(strtoupper($_GET['sua_os']));
        $serie              = trim (strtoupper ($_POST['serie']));
        if (strlen($serie)==0) $serie = trim(strtoupper($_GET['serie']));
        $nf_compra          = trim (strtoupper ($_POST['nf_compra']));
        if (strlen($nf_compra)==0) $nf_compra = trim(strtoupper($_GET['nf_compra']));
        $consumidor_cpf     = trim (strtoupper ($_POST['consumidor_cpf']));
        if (strlen($consumidor_cpf)==0) $consumidor_cpf = trim(strtoupper($_GET['consumidor_cpf']));
        $produto_referencia = trim (strtoupper ($_POST['produto_referencia']));
        if (strlen($produto_referencia)==0) $produto_referencia = trim(strtoupper($_GET['produto_referencia']));
        $produto_descricao  = trim (strtoupper ($_POST['produto_descricao']));
        if (strlen($produto_descricao)==0) $produto_descricao = trim(strtoupper($_GET['produto_descricao']));
        $codigo_posto    = trim (strtoupper ($_POST['codigo_posto']));
        if (strlen($codigo_posto)==0) $codigo_posto = trim(strtoupper($_GET['codigo_posto']));
        $posto_nome      = trim (strtoupper ($_POST['posto_nome']));
        if (strlen($posto_nome)==0) $posto_nome = trim(strtoupper($_GET['posto_nome']));
        $consumidor_nome = trim ($_POST['consumidor_nome']);
        if (strlen($consumidor_nome)==0) $consumidor_nome = trim($_GET['consumidor_nome']);
        $consumidor_fone = trim (strtoupper ($_POST['consumidor_fone']));
        if (strlen($consumidor_fone)==0) $consumidor_fone = trim(strtoupper($_GET['consumidor_fone']));
        $os_situacao     = trim (strtoupper ($_POST['os_situacao']));
		if (strlen($os_situacao)==0) $os_situacao = trim(strtoupper($_GET['os_situacao']));
		$revenda_cnpj     = trim (strtoupper ($_POST['revenda_cnpj']));
        if (strlen($revenda_cnpj)==0) $revenda_cnpj = trim(strtoupper($_GET['revenda_cnpj']));

        if($login_fabrica == 52) {
            $numero_ativo = trim (strtoupper ($_POST['numero_ativo']));
            if(strlen($numero_ativo)==0) {
                $numero_ativo = trim(strtoupper($_GET['numero_ativo']));
            }
            $cidade_do_consumidor = trim (strtoupper ($_POST['cidade_do_consumidor']));
            if(strlen($cidade_do_consumidor)==0) {
                $cidade_do_consumidor = trim(strtoupper($_GET['cidade_do_consumidor']));
            }
        }
if($telecontrol_distrib OR in_array($login_fabrica, [131,146,156])){
?>

<form id="form_exclui_os" name="form_exclui_os" method="post" style="display: none;" >
    <input type="hidden" name="acao_exclui_os" value="t" />
    <?php

    if (isset($_POST["acao_exclui_os"])) {
        unset($_POST["acao_exclui_os"]);
    }

    if (isset($_POST["exclui_os"])) {
        unset($_POST["exclui_os"]);
    }

    if (isset($_POST["motivo_exclui_os"])) {
        unset($_POST["motivo_exclui_os"]);
    }

    ?>
    <input type="hidden" name="post_anterior" value='<?=arrayToJson($_POST)?>' />

</form>
<?php } ?>
</div>
<form id="formulario_consulta" name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">
<input name="finalizada_index" type="hidden" value="<?=$_REQUEST['finalizada_index']?>">
<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td class="titulo_tabela" ><?=traduz('Parâmetros de Pesquisa')?></td>
    </tr>
</table>
<?php
    if($login_fabrica == 153){
?>
        <div id='dlg_motivo'>
            <div id='motivo_header'><?=traduz('Informe o motivo da Finalização')?></div>
            <div id='dlg_fechar'>X</div>
            <div id='motivo_container'>
                <center><p id="exclusao" style='display:none;font-size:12px;font-weight:bold;color:green;'><?=traduz('OS finalizada com sucesso!')?></p></center>
                <p><?=traduz('Motivo da Finalização da os ')?><span id="motivo_os" alt=''></span>?</p>
                <input type="text" name="str_motivo" id="str_motivo" size='50'>
                <input type="hidden" name="n_linha" id="n_linha" value='' size='50'>
                <br>
                <button type="button" id="dlg_btn_excluir"><?=traduz('Finalizar')?></button>
                <button type="button" id="dlg_btn_cancel"><?=traduz('Cancelar')?></button>
            </div>
        </div>

<?php
    }

    if ($login_fabrica == 158) {
        ?>
        <div id='dlg_motivo' class='dlg_motivo'>
            <div id='motivo_header'>".traduz("Informe a data de conserto")."</div>
            <div id='dlg_fechar' class='dlg_fechar'>X</div>
            <div id='motivo_container' style='text-align:center !important'>
                <div id='mensagem_finaliza_os'></div>
                <div style='display:block;width:100%;' id='data_os'></div>
                <div style='display:inline-block;width:50%;'>
                    <p>".traduz("Data de Inicio:")."</p>
                    <input type='text' style='width:150px;display:inline !important' class='mask-datetimepicker' name='str_data_inicio' id='str_data_inicio' size='20'>
                </div>
                <div style='display:inline-block;width:50%;'>
                    <p>".traduz("Data de Fim:")."</p>
                    <input type='text' style='width:150px;display:inline !important' class='mask-datetimepicker' name='str_data_conserto' id='str_data_conserto' size='20'>
                </div>
                <input type='hidden' class='n_linha' name='n_linha' id='n_linha' value='' size='50'>

                <div align='center' style='width:100%;text-align:center !important;'>
                    <br /><br />

                    <button type='button' class='dlg_btn_finaliza_finaliza'>".traduz("Finalizar")."</button>
                    <button type='button' class='dlg_btn_cancel'>".traduz("Cancelar")."</button>
                </div>
            </div>
        </div>
        <?php
    }

        if($login_fabrica == 90){
?>
        <TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
            <TR><TD COLSPAN="2">&nbsp;</TD></TR>
            <TR>
                <TD style="width: 125px">&nbsp;</TD>
                <TD><INPUT TYPE="checkbox" NAME="chk_opt1" value="1" id='chk_opt1' <?php if(!empty($chk1)) echo "CHECKED";?>><label for='chk_opt1'>&nbsp;<?=traduz('OS  Abertas Hoje')?></label></TD>
            </TR>
            <TR>
                <TD>&nbsp;</TD>
                <TD><INPUT TYPE="checkbox" NAME="chk_opt2" value="2" <?php if(!empty($chk2)) echo "CHECKED";?>>&nbsp;<?=traduz('OS  Abertas Ontem')?></TD>
            </TR>
            <TR>
                <TD>&nbsp;</TD>
                <TD><INPUT TYPE="checkbox" NAME="chk_opt3" value="3" <?php if(!empty($chk3)) echo "CHECKED";?>>&nbsp;<?=traduz('OS  Abertas Nesta Semana')?></TD>
            </TR>
            <TR>
                <TD>&nbsp;</TD>
                <TD><INPUT TYPE="checkbox" NAME="chk_opt4" value="3" <?php if(!empty($chk4)) echo "CHECKED";?>>&nbsp;<?=traduz('OS  Abertas Na Semana Anterior')?></TD>
            </TR>
            <TR>
                <TD>&nbsp;</TD>
                <TD><INPUT TYPE="checkbox" NAME="chk_opt5" value="4" <?php if(!empty($chk5)) echo "CHECKED";?>>&nbsp;<?=traduz('OS  Abertas Neste Mês')?></TD>
            </TR>
            <TR><TD COLSPAN="2">&nbsp;</TD></TR>
        </TABLE>
<?php
        }
?>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2"  class="formulario">
    <tr>
        <td width="100px"> &nbsp; </td>
        <td width="200px"><?=traduz('Número da OS')?></td>
        <?php if (in_array($login_fabrica, array(169, 170))){ ?>
            <td width='200px'><?=traduz(' Numero da OS SAP')?></td>
        <?php } ?>
        <td width="200px">
            <?php
                if($login_fabrica == 35){
                    echo "PO#";

                }elseif($login_fabrica == 160 or $replica_einhell){
                    echo "Nº de Lote";
                }else{
                    echo traduz("Número de Série");
                }
            ?>
        </td>
        <?php if (isFabrica(161)): ?>
        <td width="150"><?=traduz('Por Lote')?></td>
        <?php endif; ?>
        <td width="200px"><?=traduz('NF. Compra')?></td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td><input type="text" name="sua_os" size="10" value="<?echo $sua_os?>" class="frm"></td>
        <?php if (in_array($login_fabrica, array(169,170))){ ?>
        <td>
            <input type="text" name="numero_os_sap" size="15" value="<?=$numero_os_sap?>" class="frm">
        </td>
        <?php } ?>
        <td><input type="text" name="serie"     size="10" value="<?echo $serie?>"     class="frm"></td>
        <?php if (isFabrica(161)): ?>
        <td><input type="text" name="lote"      size="10" value="<?=$_POST['lote']?>" class="frm" maxlength="20" ></td>
        <?php endif; ?>
        <td><input type="text" name="nf_compra" size="10" value="<?echo $nf_compra?>" class="frm"></td>
    </tr>
</table>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <?php
        $tdwidth2 = '230';
        if (in_array($login_fabrica,array(35,157)))  {
            $tdwidth = '163px';
        } else if ($login_fabrica == 148) {
            $tdwidth = '150px';
        } elseif ($login_fabrica == 156) {
            $tdwidth = '96px';
            $tdwidth2 = '196';
        } else {
            $tdwidth = '86px';
        }
        ?>
        <td align="left" style="width: <?=$tdwidth?>;" > &nbsp;</td>
        <td align="left" style="width: <?=$tdwidth2?>px;" ><?=traduz('CPF/CNPJ Consumidor')?></td>

        <? if (in_array($login_fabrica, array(169,170))) { ?>
            <td align="left" style="width: <?=$tdwidth?>px;" ><?=traduz('Inspetor')?></td>
            <td align="left" style="width: <?=$tdwidth?>px;" ><?=traduz('Tipo de Posto')?></td>
        <? } ?>

        <? if (in_array($login_fabrica, array(175))) { ?>
            <td align="left" style="width: 150px;" ><?=traduz('Número da reclamação')?></td>
            <td align="left" style="width: 150px;" ><?=traduz('Pedido Ibramed')?></td>
        <? } ?>

        <?
        if ($login_fabrica == 156) {
            echo traduz('<td align="left">NF Recebimento</td>');
        }

        // HD 415550
        if(in_array($login_fabrica, array(94,162,165))){
            $labelTecnico = ($login_fabrica == 165) ? "Técnico" : "Nome do Técnico";
        ?>
            <td align="left" nowrap ><?= $labelTecnico;?></td>
        <?php
        }
        if($login_fabrica == 137){
        ?>
            <td align="left" width="300px"><?=traduz('N. Lote')?></td>
        <?php
        }
        if($login_fabrica==45){
        ?>
            <td align="left" width="300px"><?=traduz('RG do Produto')?></td>
        <?php
        }
        if($login_fabrica==30){
        ?>
            <td align="left" width="300px"><?=traduz('OS Revendedor')?></td>
        <?php
        }

        if(in_array($login_fabrica, array(148))){
        ?>
            <td align="left" width="200px"></td>
        <?php
        }

        if (in_array($login_fabrica,array(6,35,157,158)) && empty($login_cliente_admin)) {
        ?>
            <td align="left"width="300px">OS
            <?php
            if (in_array($login_fabrica,array(35,157))) {
                echo 'Interna';
            } else if ($login_fabrica == 158) {
                echo "Cliente";
            }else {
                echo 'Posto';
            }
            ?>
            </td>
        <?php
        }
        if ($login_fabrica == 11 or $login_fabrica == 172){?>
            <td align="left" width="400px"><?=traduz('RG do Produto')?></td>
        <?php
        }
        if($login_fabrica != 45 && $login_fabrica != 30 && $login_fabrica != 2 && $login_fabrica != 11 && $login_fabrica != 172 && $login_fabrica != 94 and $login_fabrica <> 156){
        ?>
            <td align="left" width="300px">&nbsp;</td>
        <?php
        }
        ?>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td><input type="text" id="consumidor_cpf" name="consumidor_cpf" size="17" maxlength='14' value="<?php echo $consumidor_cpf?>" class="frm"></td>
        <?php
        if (in_array($login_fabrica, array(169,170,178))) {
            $sqlInspetor = "
                SELECT
                    admin,
                    nome_completo,
                    login
                FROM tbl_admin
                WHERE fabrica = {$login_fabrica}
                AND ativo IS TRUE
                AND admin_sap IS TRUE;
            ";

            $resInspetor = pg_query($con, $sqlInspetor);
            $countInspetor = pg_num_rows($resInspetor);
            ?>
            <td>
                <select id="admin_sap" name="admin_sap" class="frm">
                    <option value=""><?=traduz('Selecione')?></option>
                    <? for ($ins = 0; $ins < $countInspetor; $ins++) {
                        $res_admin_sap = pg_fetch_result($resInspetor, $ins, admin);
                        $res_nome_completo = pg_fetch_result($resInspetor, $ins, nome_completo);
                        $res_login = pg_fetch_result($resInspetor, $ins, login); ?>
                        <option value="<?= $res_admin_sap; ?>" <?= ($res_admin_sap == $admin_sap) ? 'selected="selected"' : ''; ?>><?= empty($res_nome_completo) ? $res_login : $res_nome_completo; ?></option>
                    <? } ?>
                </select>
            </td>
            <?php
            $sqlTipoPosto = "
                SELECT
                    tipo_posto,
                    descricao
                FROM tbl_tipo_posto
                WHERE fabrica = {$login_fabrica}
                AND ativo IS TRUE
            ";

            $resTipoPosto = pg_query($con, $sqlTipoPosto);
            $countTipoPosto = pg_num_rows($resTipoPosto);
            ?>
            <td>
                <select id="tipo_posto" name="tipo_posto" class="frm">
                    <option value=""><?=traduz('Selecione')?></option>
                    <?php
                    for ($ins = 0; $ins < $countTipoPosto; $ins++) {
                        $res_tipo_posto_id = pg_fetch_result($resTipoPosto, $ins, "tipo_posto");
                        $res_tipo_posto_descricao = pg_fetch_result($resTipoPosto, $ins, "descricao");
                        ?>
                        <option value="<?= $res_tipo_posto_id; ?>" <?=($res_tipo_posto_id == $tipo_posto) ? 'selected="selected"' : ''?> ><?=$res_tipo_posto_descricao?></option>
                    <?php
                    }
                    ?>
                </select>
            </td>
        <?php
        }
        ?>
        <?php if ($login_fabrica == 175){ ?>
            <td>
                <input type="text" name="numero_reclamacao" class="frm" value="<?=$numero_reclamacao?>">
            </td>
            <td>
                <input type="text" name="seu_pedido" class="frm" value="<?= $seu_pedido ?>">
            </td>
        <?php } ?>
        <?php
        if ($login_fabrica == 156) {
            echo '<td><input class="frm" type="text" name="nf_recebimento" size="10" value="' . $_POST['nf_recebimento'] . '" ></td>';
        }
        if(in_array($login_fabrica, array(148))){
        ?>
            <td align="left" width="300px">
                <input type="checkbox" name="fora_garantia" id="fora_garantia" <?php echo isset($_POST["fora_garantia"]) ? "checked" : ""; ?>  value="t"><?=traduz('OS fora de garantia')?>
            </td>
        <?php
        }

        if ($login_fabrica == 94) {
        ?>
            <td>
                <input class="frm" type="text" name="nome_tecnico" maxlength="20" value="<?php echo $_POST['nome_tecnico'];?>" />
            </td>
        <?php
        }

		if (in_array($login_fabrica,array(162,165))) {
			if($login_fabrica == 165) {
				$qry_tec = pg_query($con, "SELECT tecnico, upper(tbl_tecnico.nome) as nome from tbl_posto_fabrica join tbl_tecnico using(posto, fabrica) join tbl_tipo_posto using(tipo_posto, fabrica) WHERE fabrica = $login_fabrica and posto_interno order by tbl_tecnico.nome;" ) ;
			}else{
				$qry_tec = pg_query($con, "SELECT tecnico, nome FROM tbl_tecnico WHERE fabrica = $login_fabrica");
			}
                echo '<td>';
                echo '<select name="tecnico" class="frm">';
                echo '<option value=""></option>';
                while ($fetch = pg_fetch_assoc($qry_tec)) {
                  echo '<option value="' . $fetch["tecnico"] . '"';
                  if ($_POST["tecnico"] == $fetch["tecnico"]) {
                      echo ' selected="selected"';
                  }
                  echo '>' , $fetch["nome"] , '</option>';
                }
                echo '</select>';
                echo '</td>';
        }


        if($login_fabrica==45) {
?>
        <td><input class="frm" type="text" name="rg_produto" size="15" maxlength="20" value="<? echo $_POST['rg_produto'] ?>" ></td>
<?
        }elseif($login_fabrica == 137){

?>
        <td><input class="frm" type="text" name="lote" size="15" maxlength="20" value="<? echo $_POST['lote'] ?>" ></td>
<?php

        }elseif(in_array($login_fabrica,array(30,35,157,158)) && empty($login_cliente_admin)) {
?>
        <td><input class="frm" type="text" name="os_posto" size="15" maxlength="20" value="<? echo $_POST['os_posto'] ?>" ></td>
<?
        } elseif($login_fabrica == 6){
            echo'<td><input class="frm" type="text" name="os_posto" size="12" maxlength="10" value="';
            if (isset($_POST['os_posto']{0})){
                echo $_POST['os_posto'];
            }
            echo '" ></td>';
        }
?>
        </td>
<?
        if ($login_fabrica == 11 or $login_fabrica == 172) {
?>
            <td><input type="text" name="rg_produto_os" size="17" value="<?echo $_POST['rg_produto_os']?>" class="frm"></td>
<?
        }
        if (!in_array($login_fabrica, array(2,11,30,45,172))) {
?>
            <td>&nbsp;</td>
        <?php
        }
?>
    </tr>

</table>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<?
        if($login_fabrica==45) {
?>
    <tr>
        <td align="left" width="100px"> &nbsp; </td>
        <td align="left" width="600px"><?=traduz('Status')?></td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td>
            <select name='tipo_os' id='tipo_os' style='font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;font-size: 10px;'>
                <option>TODAS AS OPÇÕES</option>
                <option value='REINCIDENTE' <? if ($tipo_os == 'REINCIDENTE') echo " SELECTED "; ?>>Reincidências</option>
                <option value='BOM' <? if ($tipo_os == 'BOM') echo " SELECTED "; ?>><?=traduz('BOM (OSs abertas até 15 dias sem data de fechamento)')?></option>
                <option value='MEDIO' <? if ($tipo_os == 'MEDIO') echo " SELECTED "; ?>><?=traduz('MÉDIO (OSs abertas entre 15 dias e 25 dias sem data de fechamento)')?></option>
                <option value='RUIM' <? if ($tipo_os == 'RUIM') echo " SELECTED "; ?>><?=traduz('RUIM (OSs abertas a mais de 25 dias sem data de fechamento)')?></option>
                <option value='EXCLUIDA' <? if ($tipo_os == 'EXCLUIDA') echo " SELECTED "; ?>><?=traduz('OS Cancelada ')?></option>
                <option value='RESSARCIMENTO' <? if ($tipo_os == 'RESSARCIMENTO') echo " SELECTED "; ?>><?=traduz('OS com Ressarcimento Financeiro')?></option>
                <option value='TROCA' <? if ($tipo_os == 'TROCA') echo " SELECTED "; ?>><?=traduz('OS com Troca de Produto')?></option>
                <?php if ($login_fabrica == 45): ?>
                    <option value='INTERACAO' <?  if ($tipo_os == 'INTERACAO')  echo " SELECTED "; ?>><?=traduz('OS com interação do posto')?></option>
                    <option value='RESOLVIDOS' <? if ($tipo_os == 'RESOLVIDOS') echo " SELECTED "; ?>><?=traduz('OS com troca de Produtos - Resolvidos')?></option>
                    <option value='PENDENTES' <?  if ($tipo_os == 'PENDENTES')  echo " SELECTED "; ?>><?=traduz('OS com troca de Produtos - Pendentes')?></option>
                <?php endif ?>
            </select>
        </td>
    </tr>
<?
        }
?>

</table>

<!-- CONSULTA OS OFF LINE -->
<?
        if($login_fabrica==19 OR $login_fabrica==10){
?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
        <tr align='left' class="subtitulo">
            <td colspan='2'><center><?=traduz('Consulta OS Off Line')?></center></td>
        </tr>
</table>

    <table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">

        <tr>
            <td align="left" width="100px"> &nbsp; </td>
            <td align="left" width="200px"><?=traduz('OS Off Line')?></td>
            <td align="left" width="400px"> &nbsp; </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td><input type="text" name="os_off" size="10" value="" class="frm"></td>
            <td>&nbsp;</td>
        </tr>
    </table>


    <table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">

        <tr>
            <td align="left" width="100px"> &nbsp; </td>
            <td align="left" width="200px"><?=traduz('Posto')?></td>
            <td align="left" width="400px"><?=traduz('Nome do Posto')?></td>
        </tr>
        <tr>
            <td> &nbsp; </td>
            <td>
                <input width="200" type="text" name="codigo_posto_off" id="codigo_posto_off" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto ('',document.frm_consulta.codigo_posto, '');" <? } ?> value="<? echo $codigo_posto_off ?>" class="frm">
                <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="<?=traduz('Clique aqui para pesquisar postos pelo código')?>" onclick="javascript: fnc_pesquisa_posto ('',document.frm_consulta.codigo_posto, '')">
            </td>
            <td>
                <input type="text" name="posto_nome_off" id="posto_nome_off" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto ('','', document.frm_consulta.posto_nome);" <? } ?> value="<?echo $posto_nome_off ?>" class="frm">
                <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="<?=traduz('Clique aqui para pesquisar postos pelo código')?>" onclick="javascript: fnc_pesquisa_posto ('','', document.frm_consulta.posto_nome)">
            </td>
        </tr>
    </table>

    <table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
        <tr><td align="left" width="700px">&nbsp;</td></tr>
        <tr>
            <td align="center" width="700px">
                <input type="submit" name="btn_acao" value="Pesquisar">
	    </td>
        </tr>
    </table>

<?
        }
?>

<!--fim consulta off line -->

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">

    <? if($login_fabrica==7){ ?>
        <tr>
            <td align='left' width="100px"> &nbsp; </td>
            <td align='left' width="200px">&nbsp;<?=traduz('Data Inicial')?></td>
            <td align='left' width="200px">&nbsp;<?=traduz('Data Final')?></td>
            <td align='left' width="200px">&nbsp;</td>
        </tr>

        <tr valign='top'>
            <td> &nbsp; </td>
            <td>
                <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo (strlen($data_inicial) > 0) ? substr($data_inicial,0,10) : "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
            </td>
            <td>
                <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
                &nbsp;
            </td>
            <td>
                <input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> ><label for="os_aberta"><?=traduz('Apenas OS em aberto')?></label>
            </td>

        </tr>
    <? }else{ ?>
            <!-- HD 211825: Filtrar por tipo de OS: Consumidor/Revenda -->
<?php
            switch ($consumidor_revenda_pesquisa) {
                case "C":
                    $selected_c = "SELECTED";
                break;

                case "R":
                    $selected_r = "SELECTED";
                break;
            }
?>
</table>

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
        <?php
            $tmh = (in_array($login_fabrica, array(153,156))) ? "200px" : "100px";
            $tmh = (in_array($login_fabrica, array(164))) ? "160px" : $tmh;
        
            if (in_array($login_fabrica, array(152,180,181,182))) { /*HD - 4292800*/?>
                <tr>
                    <td align="left" width="<?=$tmh?>"> &nbsp; </td>
                    <td align="left" width="200px">
                        <?=traduz('Classificação')?>
                    </td>
                </tr>
                <tr>
                    <td align="left" width="<?=$tmh?>"> &nbsp; </td>
                    <td align="left" width="200px">
                        <select name="classificacao_esab" id="classificacao_esab" class="frm">
                            <option value=""></option>
                            <option value="tecnico"><?=traduz('Técnico')?></option>
                            <option value="logistico"><?=traduz('Logístico')?></option>
                            <option value="comercial"><?=traduz('Comercial')?></option>
                        </select>
                    </td>
                </tr>
        <?php } ?>
        <tr>
            <td align="left" width="<?=$tmh?>"> &nbsp; </td>
            <td align="left" width="200px">
            <?php
                if(in_array($login_fabrica, array(87,141,144,148))){
                    echo "Tipo de Atendimento";
                }else{
                    echo "Tipo de OS";
                }
            ?>
            </td>

<?php
                if(in_array($login_fabrica, array(94,115,116,117,120,153,156,158,161,163,167,169,170,171,173,174,175,176,177,203))){
?>
                    <td><?=traduz('Tipo de Atendimento')?></td>
<?php
                }
?>
            <td align="left" width="300px">
<?php
                if ($login_fabrica == 156) {
                    echo 'Status Elgin';
                #HD 234532
                } else if($login_fabrica != 96) {
?>
                    <?=traduz('Status da OS')?>
<?php
                }
?>
            </td>

            <?php if($login_fabrica == 164){ ?>
            <td align="left" width="300px">
                <?=traduz('Destinação')?>
            </td>
            <?php } ?>

        </tr>

        <tr>
            <td width="100px"> &nbsp; </td>
            <td>

<?php
                if (in_array($login_fabrica, array(87,141,144,148))) {
                    $sql_tipo_atendimento = "SELECT DISTINCT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
                    $res_tipo_atendimento = pg_query($con,$sql_tipo_atendimento);
                ?>
                <select id="tipo_atendimento" name="tipo_atendimento" class='frm' style='width:160px'>
                <?php
                    if(pg_num_rows($res_tipo_atendimento)>0){
                        echo '<option value="" selected></option>';
                        for ($i=0;pg_num_rows($res_tipo_atendimento)>$i;$i++) {
                            $descricao = pg_fetch_result($res_tipo_atendimento,$i,descricao);
                            $tipo_atendimento = pg_fetch_result($res_tipo_atendimento,$i,tipo_atendimento);

                            echo "<option value='{$tipo_atendimento}' ".verificaSelect($tipo_atendimento, $descricao_tipo_atendimento).">{$descricao}</option>";
                        }
                    }
?>
                </select>
<?php
                } else {
?>
                <select id="consumidor_revenda_pesquisa" name="consumidor_revenda_pesquisa" class='frm' style='width:95px'>
                    <option value="">Todas</option>
                    <option value="C" <?php echo $selected_c; ?>><?=traduz('Consumidor')?></option>
                    <option value="R" <?php echo $selected_r; ?>><?=traduz('Revenda')?></option>
                </select>
<?php
                }
?>

            </td>

<?php
                if(in_array($login_fabrica, array(94,115,116,117,120,153,156,158,161,163,167,169,170,171,173,174,175,176,177,203))){
                    $sql_tipo_atendimento = "SELECT DISTINCT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
                    $res_tipo_atendimento = pg_query($con,$sql_tipo_atendimento);
?>
                    <td nowrap>
                        <select id="tipo_atendimento" name="tipo_atendimento" class='frm' style='width:160px'>
                        <?php
                            if(pg_num_rows($res_tipo_atendimento)>0){
                                echo '<option value="" selected></option>';
                                for($i=0;pg_num_rows($res_tipo_atendimento)>$i;$i++){
                                    $descricao = pg_fetch_result($res_tipo_atendimento,$i,descricao);
                                    $tipo_atendimento = pg_fetch_result($res_tipo_atendimento,$i,tipo_atendimento);

                                    echo "<option value='{$tipo_atendimento}' ".verificaSelect($tipo_atendimento, $descricao_tipo_atendimento).">{$descricao}</option>";
                                }
                            }
                        ?>
                        </select>
                    </td>
<?php
                }
?>
            <td>
<?php
                if ($login_fabrica == 156) {
?>
                <select id="os_elgin_status" name="os_elgin_status" class="frm">
                    <option></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Em analise') ? "SELECTED" : "" ; ?> value='Em analise' ><?=traduz('Em analise')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Pendência de documento') ? "SELECTED" : "" ; ?> value='Pendência de documento' ><?=traduz('Pendência de documento')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Aguardando NF') ? "SELECTED" : "" ; ?> value='Aguardando NF' ><?=traduz('Aguardando NF')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Equip. env. p/ dep.') ? "SELECTED" : "" ; ?> value='Equip. env. p/ dep.' ><?=traduz('Equip. env. p/ dep.')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Emitir Orçamento') ? "SELECTED" : "" ; ?> value='Emitir Orçamento' ><?=traduz('Emitir Orçamento')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Manut. em Terceiro') ? "SELECTED" : "" ; ?> value='Manut. em Terceiro' ><?=traduz('Manut. em Terceiro')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='NF Emitida') ? "SELECTED" : "" ; ?> value='NF Emitida' ><?=traduz('NF Emitida')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='OS Encerrada') ? "SELECTED" : "" ; ?> value='OS Encerrada' ><?=traduz('OS Encerrada')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Orçam. (Aprovação)') ? "SELECTED" : "" ; ?> value='Orçam. (Aprovação)' ><?=traduz('Orçam. (Aprovação)')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Orçam. Aprovado') ? "SELECTED" : "" ; ?> value='Orçam. Aprovado' ><?=traduz('Orçam. Aprovado')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Aguardando Pecas') ? "SELECTED" : "" ; ?> value='Aguardando Pecas' ><?=traduz('Aguardando Pecas')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Troca, Ag. Analise ZPM') ? "SELECTED" : "" ; ?> value='Troca, Ag. Analise ZPM' ><?=traduz('Troca, Ag. Analise ZPM')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Ag. Anal. MFD ZPM') ? "SELECTED" : "" ; ?> value='Ag. Anal. MFD ZPM' ><?=traduz('Ag. Anal. MFD ZPM')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Ingênico, Orç Reprovado') ? "SELECTED" : "" ; ?> value='Ingênico, Orç Reprovado' ><?=traduz('Ingênico, Orç Reprovado')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Pend. p/ pç GECARE') ? "SELECTED" : "" ; ?> value='Pend. p/ pç GECARE' ><?=traduz('Pend. p/ pç GECARE')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Pend. p/ pç SECONT') ? "SELECTED" : "" ; ?> value='Pend. p/ pç SECONT' ><?=traduz('Pend. p/ pç SECONT')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Pend. p/ pç ASSISTÊNCIA') ? "SELECTED" : "" ; ?> value='Pend. p/ pç ASSISTÊNCIA' ><?=traduz('Pend. p/ pç ASSISTÊNCIA')?></option>
                    <option <?= ($_REQUEST["os_elgin_status"]=='Em Solicitação') ? "SELECTED" : "" ; ?> value='Em Solicitação' ><?=traduz('Em Solicitação')?></option>
                </select>
<?php
                #HD 234532
                } elseif($login_fabrica != 96) {

                    $sql_status = "SELECT status_checkpoint,descricao,cor FROm tbl_status_checkpoint WHERE status_checkpoint IN (".$condicao_status.") {$where_tbl_status_checkpoint}";
                    $res_status = pg_query($con,$sql_status);
                    $total_status = pg_num_rows($res_status);
?>
                    <select id="status_checkpoint" name="status_checkpoint" class='frm'>
                        <option value=""></option>
                    <?php
                        for($i=0;$i<$total_status;$i++){
                            $id_status        = pg_fetch_result($res_status,$i,'status_checkpoint');
                            $cor_status       = pg_fetch_result($res_status,$i,'cor');
                            $descricao_status = pg_fetch_result($res_status,$i,'descricao');

                            $selected = ($status_checkpoint_pesquisa == $id_status) ? " selected ": " ";

                            if ($login_fabrica == 165) {
                                switch ($descricao_status) {
                                    case "Aguardando Faturamento":
                                        $descricao_status = traduz("Aguardando Expedição");
                                        break;
                                    default:
                                        $descricao_status = $descricao_status;
                                        break;
                                }
                            }

                            echo "<option value='$id_status' $selected >".traduz($descricao_status)."</option>";
                        }
?>
                    </select>
<?php

                }
?>
            </td>

            <?php if($login_fabrica == 164){ ?>
            <td align="left" width="300px">
                <select name="destinacao" class="frm">

                    <option></option>

                    <?php

                    $sql_destinacao = "SELECT segmento_atuacao, descricao FROM tbl_segmento_atuacao WHERE fabrica = {$login_fabrica} AND ativo = true";
                    $res_destinacao = pg_query($con, $sql_destinacao);

                    if(pg_num_rows($res_destinacao) > 0){

                        for($i = 0; $i < pg_num_rows($res_destinacao); $i++){

                            $segmento_atuacao  = pg_fetch_result($res_destinacao, $i, "segmento_atuacao");
                            $descricao_atuacao = pg_fetch_result($res_destinacao, $i, "descricao");

                            $selected = ($_POST["destinacao"] == $segmento_atuacao) ? "selected" : "";

                            echo "<option value='{$segmento_atuacao}' {$selected} > {$descricao_atuacao} </option>";

                        }

                    }

                    ?>

                </select>
            </td>
            <?php } ?>

        </tr>

        <?php
        if (in_array($login_fabrica, [177])) {
        ?>
            <tr>
                <td></td>
                <td class="espaco"><?php echo traduz("status.orcamento");?></td>
            </tr>
            <tr>
                <td></td>
                <td class="espaco">
                    <select name="status_orcamento" class="frm">
                        <option value=""></option>
                        <?php
                        $status_orcamento = array(
                            "Aguardando Análise" => "Aguardando Análise",
                            "Distribuição" => "Distribuição",
                            "Em analise" => "Em Analise",
                            "Orçam. (Aprovação)" => "Orçam. (Aprovação)",
                            "Aguardando Pecas" => "Aguardando Peças",
                            "Em reparo" => "Em reparo",
                            "Reparado" => "Reparado",
                            "Orçamento Reprovado" => "Orçamento Reprovado",
                            "OS Encerrada" => "OS Encerrada"
                        );

                        foreach ($status_orcamento as $desc_orcamento => $value ) {
                            if($_POST["status_orcamento"] == $desc_orcamento){
                                $selectedTec = "SELECTED";
                            }else{
                                $selectedTec = "";
                            }

                            #$selectedTec = ($_RESULT["os"]["status_orcamento"] == $desc_orcamento) ? " SELECTED" : "";
                            #print_r($desc_orcamento);exit;
                        ?>
                            <option value="<?=$desc_orcamento?>" <?=$selectedTec?> > <?=$value;?> </option>
                        <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>
        <?php
        } ?>

        <?php if ($login_fabrica == 3) {
            $sql_tipo_atendimento = "SELECT DISTINCT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
            $res_tipo_atendimento = pg_query($con,$sql_tipo_atendimento); ?>
            <tr>
                <td width="100px"> &nbsp; </td>
                <td><?=traduz('Tipo de Atendimento')?></td>
            </tr>
            <tr>
                <td width="100px"> &nbsp; </td>
                <td>
                    <select id="tipo_atendimento" name="tipo_atendimento" class='frm' style='width:160px'>
                    <?php
                        if(pg_num_rows($res_tipo_atendimento)>0){
                            echo '<option value="" selected></option>';
                            for ($i=0;pg_num_rows($res_tipo_atendimento)>$i;$i++) {
                                $descricao = pg_fetch_result($res_tipo_atendimento,$i,descricao);
                                $tipo_atendimento = pg_fetch_result($res_tipo_atendimento,$i,tipo_atendimento);

                                echo "<option value='{$tipo_atendimento}' ".verificaSelect($tipo_atendimento, $descricao_tipo_atendimento).">{$descricao}</option>";
                            }
                        } ?>
                    </select>
                </td>
            </tr>
        <?php }

        if ($login_fabrica == 158) {
        ?>
            <tr>
                <td align="left" width="100px"> &nbsp; </td>
                <td><?=traduz('Patrimônio')?></td>
            </tr>
            <tr>
                <td align="left" width="100px"> &nbsp; </td>
                <td><input type="text" name="patrimonio" size="15" value="<?=$patrimonio?>" class="frm" /></td>
            </tr>
        <?php
        }
        ?>

        <?php if($login_fabrica == 160 or $replica_einhell){?>
        <tr>
            <td align="left" width="100px"> &nbsp; </td>
            <td><?=traduz('Versão Produto')?></td>
        </tr>
        <tr>
            <td align="left" width="100px"> &nbsp; </td>
            <td><input type="text" name="versao" value="<?php echo $versao ?>" maxlength="10" class="frm" size="17"></td>
        </tr>

        <?}?>

         <?php
        if ($login_fabrica == 145) {
        ?>
            <tr>
                <td align="left" width="100px"> &nbsp; </td>
                <td><?=traduz('Tipo de Atendimento')?></td>
                <td><?=traduz('Pesquisa de Satisfação')?></td>
            </tr>
            <tr>
                <td align="left" width="100px"> &nbsp; </td>
                <?php
                $sql_tipo_atendimento = "SELECT DISTINCT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
                $res_tipo_atendimento = pg_query($con,$sql_tipo_atendimento);
                ?>
                    <td nowrap>
                        <select id="tipo_atendimento" name="tipo_atendimento" class='frm' style='width:160px'>
                        <?php
                            if(pg_num_rows($res_tipo_atendimento)>0){
                                echo '<option value="" selected></option>';
                                for($i=0;pg_num_rows($res_tipo_atendimento)>$i;$i++){
                                    $descricao = pg_fetch_result($res_tipo_atendimento,$i,descricao);
                                    $tipo_atendimento = pg_fetch_result($res_tipo_atendimento,$i,tipo_atendimento);

                                    echo "<option value='{$tipo_atendimento}' ".verificaSelect($tipo_atendimento, $descricao_tipo_atendimento).">{$descricao}</option>";
                                }
                            }
                        ?>
                        </select>
                    </td>
                <td>
                    <select class="frm" name="pesquisa_satisfacao" >
                        <option value="" ></option>
                        <option value="realizada" <?=($_POST["pesquisa_satisfacao"] == "realizada") ? "selected" : ""?> ><?=traduz('Pesquisa realizada')?></option>
                        <option value="nao_realizada" <?=($_POST["pesquisa_satisfacao"] == "nao_realizada") ? "selected" : ""?> ><?=traduz('Pesquisa não realizada')?></option>
                    </select>
                </td>
            </tr>
        <?php
        }
        if($login_fabrica == 30){
?>
        <tr>
            <td align="left" width="100px"> &nbsp; </td>
            <td colspan="2" align="left"><?=traduz('Atendimento Centralizado')?></td>
        </tr>
        <tr>
            <td align="left" width="100px"> &nbsp; </td>
            <td colspan="2" align="left">
                <select id="cliente_admin" name="cliente_admin" class="frm">
                    <option value=""><?=traduz('Todos')?></option>
<?
            $sql = "SELECT  tbl_cliente_admin.cliente_admin,
                            tbl_cliente_admin.nome
                    FROM    tbl_cliente_admin
                    WHERE   tbl_cliente_admin.fabrica = $login_fabrica
              ORDER BY      tbl_cliente_admin.nome
            ";
            $res = pg_query($con,$sql);
            $todos_cliente_admin = pg_fetch_all($res);

            foreach($todos_cliente_admin as $valor){
?>
                    <option value="<?=$valor['cliente_admin']?>" <?=($valor['cliente_admin'] == $cliente_admin) ? "selected" : ""?>><?=$valor['nome']?></option>
<?
            }

?>
                </select>
            </td>
        </tr>
<?
        }

	if($login_fabrica == 156){
		$sqlTecnico = "SELECT tecnico, nome FROM tbl_tecnico WHERE fabrica = {$login_fabrica} ORDER BY nome";
		$resTecnico = pg_query($con,$sqlTecnico);
		$rowsT = pg_num_rows($resTecnico);
?>
		<tr>
			<td>&nbsp;</td>
			<td colspan='3'>
				Técnico <br>
				<select name="tecnico" class="frm">
					<option value=''></option>
<?php
					for($i = 0; $i < $rowsT; $i++){
						$tecnico_id = pg_fetch_result($resTecnico,$i,'tecnico');
						$nome_tecnico = pg_fetch_result($resTecnico,$i,'nome');
						$selected_tecnico = ($tecnico_id == $tecnico) ? "SELECTED" : "";
						echo "<option value='{$tecnico_id}' {$selected_tecnico} >{$nome_tecnico}</option>";
					}
?>
				</select>
			</td>
		</tr>
<?php

	}
        ?>
        <script>
            $(function(){

                $(".sel_excel").on('change', function() {
                    var formato = $(this).val();
                    $(".sel_excel").val(formato)
                });

                if ($("#check_gerar_excel").is(":checked")) {
                    $("#opcoes_excel").show();
                }

                $("#check_gerar_excel").click(function(){
                    $("#opcoes_excel").toggle();
                });
            });
        </script>
        <tr>
	    <td colspan='4' align='center'>
		  <input type="submit" name="btn_acao" value="<?=traduz('Pesquisar')?>">
	    </td>
        </tr>
<?php
    if($login_fabrica == 72) {
?>
        <tr>
        <td colspan='4' align='center'>
            <input type="checkbox" name="consultar_os_sem_listar_pecas" value="t"> <?=traduz('Consultar OS sem listar peças')?><br><br>
        </td>
        </tr>
        <?php
    }
        if(in_array($login_fabrica, [152,138,180,181,182])){ //2439865
            if($login_fabrica == 138){
                $texto = traduz("Consultar OS Sem listar pedidos");
            }else{
                $texto = traduz("Consultar OS Sem listar peças");
            }
        ?>
            <tr>
            <td colspan="4" align="center">
                <input type='checkbox' name='sem_listar_peca' value='1' <? if (strlen ($sem_listar_peca) > 0 ) echo " checked " ?> ><label for="sem_listar_peca"><?=$texto;?></label><br />
            </td>
            </tr>
            <tr><td>&nbsp;</td></tr>
        <?php
        }
        ?>
</table>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
        <tr align='left' class="subtitulo">
            <td colspan='2'>&nbsp;</td>
        </tr>
</table>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario" >
        <tr>
            <td style="width: 97px;">&nbsp;</td>
<?
if($login_fabrica == 1){
?>
            <td> Marca</td>
<?
}
?>
            <td style="width: 197px;"><?=traduz(' Linha')?></td>
            <?php if($login_fabrica == 117){ ?>
            <td style="width: 197px;"><?=traduz(' Macro-Familia')?></td>
            <?php } ?>
            <td style="width: 197px;"><?=traduz(' Família')?></td>

            <?php if($login_fabrica == 153){ ?>
                <td><?=traduz('Recall')?></td>
            <?php } ?>

<?
if(in_array($login_fabrica,array(1,30))){
?>
            <td>&nbsp;</td>
<?
}
?>
        </tr>
        <tr valign="top">
            <td>&nbsp;</td>
<?
if($login_fabrica == 1){
?>
            <td>
                <select name="marca" class="frm">
                    <option value=''>&nbsp;</option>
<?
    $sqlMarca = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica;
    ";
    $resMarca = pg_query($con,$sqlMarca);
    $marcas = pg_fetch_all($resMarca);

    foreach($marcas as $chave => $valor){
?>
                    <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $_POST['marca']) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
    }
?>
                </select>
            </td>
<?
}
?>
            <td>
<?
                $select = ($login_fabrica == 117) ? "name = 'macro_linha' id='macro_linha'" : "name='linha'";
                echo "<select {$select} class='frm' style='width:95px'>";
                echo "<option value=''></option>";
                if (in_array($login_fabrica, array(117))) {
                    $sql = "SELECT macro_linha AS linha, descricao AS nome FROM tbl_macro_linha WHERE ativo = true ORDER BY descricao";
                }else{
                    $sql = "SELECT linha, nome from tbl_linha where fabrica = $login_fabrica and ativo = true order by nome";
                }
                $res = pg_query($con,$sql);

                if(pg_num_rows($res)>0){
                    for($i=0;pg_num_rows($res)>$i;$i++){
                        $xlinha = pg_fetch_result($res,$i,linha);
                        $xnome = pg_fetch_result($res,$i,nome);
                        if (isset($_REQUEST["macro_linha"])) {
                            $selected = (isset($_REQUEST['macro_linha']) && $_REQUEST['macro_linha'] == $xlinha) ? 'selected' : '';
                        } else {
                            $selected = ($xlinha == $_REQUEST["linha"]) ? "selected" : "";
                        }
?>
                    <option value="<?echo $xlinha;?>"<?=$selected;?> <? //HD 73808 if ($xmarca == $marca) echo " SELECTED "; ?>><?echo $xnome;?></option>
<?
                    }
                }
                echo "</SELECT>";
?>
            </td>

            <?php
            if (in_array($login_fabrica, array(117))) {
?>
            <td>
                <input type="hidden" name="linha_aux" id="linha_aux" value="<?=$_REQUEST["linha"]; ?>">
                <select name='linha' id='linha' size='1' class='frm' style='width:95px'>
                    <option value=''></option>
                </SELECT>
            </td>

<?
            }

            if ($login_fabrica == 148) {

                $checked_os_com_credito = (strlen($os_com_credito) > 0 ) ? "checked='checked'" : "";
                $optionsFamilias = array();

                $sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica={$login_fabrica} AND ativo=true ORDER BY descricao";
                $res = pg_query($con,$sql);
                if (pg_num_rows($res) > 0) {
                    for ($i = 0; pg_num_rows($res) > $i; $i++) {
                        $xfamilia   = pg_fetch_result($res,$i,familia);
                        $xdescricao = pg_fetch_result($res,$i,descricao);
                        $optionsFamilias[$xfamilia] = $xdescricao;

                    }
                }

                echo '<td style="width:57% !important;">';
                echo '<select name="familia[]" id="familia_s" multiple="multiple">';
                    foreach ($optionsFamilias as $valor => $descricao) {
                        $selected = (($valor == $familia) || in_array($valor, $familia)) ? "SELECTED" : '' ;
                        echo '<option value="'.$valor.'" '.$selected.'>'.$descricao.'</option>';
                    }
                echo "</select>";
                echo " <input type='checkbox' name='os_com_credito' value='1' ".$checked_os_com_credito."><label for='os_com_credito'>".traduz("Apenas OS com Crédito Gerado")." </label>";
                echo '</td>';

             } else {
                echo '<td>';
                echo '<input type="hidden" name="familia_aux" id="familia_aux" value="'.$_REQUEST["familia"].'">';
                echo "<select id='familia' name='familia' size='1' class='frm' style='width:95px'>";
                echo "<option value=''></option>";
                if (!in_array($login_fabrica, array(117))) {
                    $sql = "SELECT familia, descricao from tbl_familia where fabrica = $login_fabrica and ativo = true order by descricao";
                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res)>0){
                        for($i=0;pg_num_rows($res)>$i;$i++){
                            $xfamilia = pg_fetch_result($res,$i,familia);
                            $xdescricao = pg_fetch_result($res,$i,descricao);

                            $selected = ($_REQUEST["familia"] == $xfamilia) ? "selected" : "";
                            ?>
                            <option value="<?echo $xfamilia;?>" <?=$selected?> ><?echo $xdescricao;?></option>
                            <?
                        }
                    }
                }
                echo "</SELECT>";
                echo '</td>';
            }
?>
            <?php if($login_fabrica == 153){?>
                <td>
                    <?php
                        $recal = json_decode($recall, true);
                        if($recal['recall'] == 1){
                            $checked = " checked ";
                        }else{
                            $checked = " ";
                        }
                    ?>
                    <input type="checkbox" name="recall" value="t" <?=$checked?> >
                </td>
            <?php } ?>

            <? if (in_array($login_fabrica, array(11, 172, 169, 170))) {
                if ($xos_cortesia == 't'){
                    $checked_cortesia = "checked";
                }
                if (in_array($login_fabrica, array(169,170))){
                    $texto_cortesia = traduz("OS Cortesia");
                }else{
                    $texto_cortesia = traduz("Os Brinde");
                }
            ?>
                <td>
                    <input type='checkbox' value='t' name='os_cortesia' class='frm' <?= $checked_cortesia; ?>><label><?=$texto_cortesia?></label>
                </td>
<?php
            }
            if (!in_array($login_fabrica, array(117))) { ?>
            <td nowrap>
                <input type='checkbox' name='os_troca' value='1' <?=(strlen ($os_troca) > 0 ) ? " checked " : ""?> ><label for="os_troca"><?=traduz('Apenas OS Troca')?></label><br />
<?php
            }
            if ($login_fabrica == 1) {
?>
                <br />
                <input type='checkbox' name='os_garantia_peca' value='1' <?=(strlen ($os_garantia_peca) > 0 ) ? " checked " : ""?> ><label for="os_garantia_peca"><?=traduz('Apenas OS')?> <br /><?=traduz('Devolução de Peça')?></label><br />
            </td>
<?php
            }

            if($login_fabrica == 158) {
?>
			<td>
                <input type='checkbox' name='os_nao_exportada' value='1' <?=(strlen ($os_nao_exportada) > 0 ) ? " checked " : ""?> ><label for="os_nao_exportada"><?=traduz('Apenas OS Não exportada')?></label><br />
			</td>
<?php
            }
?>
        </tr>

</table>

<style>

#fixed-table-header {
    color: #fff;
    background-color: #596D9B;
    display: none;
    position: fixed;
    top: 0px;
    min-width: 100vw;
}

#fixed-table-header div {
    display: inline-block;
    font: bold 11px Arial;
    border: 0px solid grey;
    height: 100%;
}

</style>

<script>
    //HD 115630-----
    function clika_a(){
        if ( document.getElementById('os_aberta').checked == true ) {
            document.getElementById('os_aberta').checked = false
        }
    }
    function clika_b(){
        if ( document.getElementById('os_finalizada').checked == true ) {
            document.getElementById('os_finalizada').checked = false
        }
    }
    //------------


    <?php if($login_fabrica == 158){ ?>
        function finalizaOs(os, linha, data_conserto, data_inicio){

            $('.dlg_motivo').show('fast');
            $('.dlg_motivo .dlg_fechar,.dlg_motivo .dlg_btn_cancel').click(function () {
                $('.dlg_motivo input').val('');
                $('.dlg_motivo').hide('fast');
            });
            $("#data_os").html("<b>OS Nº "+os+"</b>");
			$(".n_linha").val(linha);

            $("input.mask-datetimepicker").unmask();
            $("input.mask-datetimepicker").maskedinput("99/99/9999 99:99:00");
            $("#mensagem_finaliza_os").html('');
            $("input[name=str_data_conserto]").val('');

            if (data_conserto != '') {
                $("input[name=str_data_conserto]").val(data_conserto);
            }
            if (data_inicio != '') {
                $("input[name=str_data_inicio]").val(data_inicio);
            }

	    $(".dlg_btn_finaliza_finaliza").attr("os",os);

        }

	$(function(){
		$('.dlg_motivo .dlg_btn_finaliza_finaliza').click(function () {
                var novaDataConserto = $("input[name=str_data_conserto]").val();
                var novaDataInicio   = $("input[name=str_data_inicio]").val();
                var os               = $(this).attr("os");

                if (novaDataConserto == '') {
                    alert('<?=traduz("Digite a Data de Conserto")?>');
                    return false;
                }

                if (confirm('Deseja realmente finalizar a (OS nº '+os+')') == false) return false;
                $.get('os_consulta_lite.php',
                {'finalizar_os_trom':'true','id_os':os, 'data_conserto':novaDataConserto, 'data_inicio':novaDataInicio},
                    function(resposta) {
                        if (resposta == 'ok') {
                            var linha = $(".n_linha").val();
                            
                            $('#btn_finaliza_'+linha).hide();
                            $("#st_ch_"+os).css("background-color","#8DFF70");
                            $("#td_alterar_"+linha).find('img').hide();
                            $("#td_trocar_"+linha).find('img').hide();
                            $("#mensagem_finaliza_os").show();
                            $("#mensagem_finaliza_os").html("<p style='font-size:12px;font-weight:bold;color:green;'>OS nº "+os+" finalizada com sucesso.</p>");
                             setTimeout(function(){
                                $('.dlg_motivo').hide('fast');
                                $('.dlg_motivo input').val('');
                                $("#mensagem_finaliza_os").html('');
                            },2000);
                        } else {
                            $("#mensagem_finaliza_os").show();
                            $("#mensagem_finaliza_os").html("<p style='font-size:12px;font-weight:bold;color:red;'>"+resposta+"</p>");
                            return false;
                        }
                });//END of GET

	})

	});
    <?php }?>


    function finalizaOsTron(os, linha,fabrica){
        var aguarde_sub;
        if (fabrica == 153) {
            if (confirm('Deseja realmente finalizar a (OS nº '+os+')') == false) return false;
            $('#dlg_motivo #motivo_os').text(os).attr('alt',os);
            $('#dlg_motivo').show('fast');
            $("#n_linha").val(linha);
            $('#dlg_motivo #dlg_fechar,#dlg_motivo #dlg_btn_cancel').click(function () {
                $('#dlg_motivo input').val('');
                $('#dlg_motivo').hide('fast');
            });
        } else {
            if(typeof aguarde_sub !== undefined && aguarde_sub == true){
                alert('<?=traduz("Aguarde a Submissão")?>');
                return;
            }
            aguarde_sub = true;
            $.get('os_consulta_lite.php',
            {'finalizar_os_trom':'true','motivo':'Fechado pela fabrica','id_os':os},
            function(resposta) {
                if (resposta == 'ok') {
                    var fechaOs = $('#dlg_motivo #motivo_os').text();
                    $('#exclusao').show();

                    aguarde_sub = false;
                    $('#btn_finaliza_'+linha).hide();
                    $("#st_ch_"+fechaOs).css("background-color","#8DFF70");
                    $("#td_alterar_"+linha).find('img').hide();
                    $("#td_trocar_"+linha).find('img').hide();


                    if (fabrica == 104) {
                        $('#dlg_motivo #motivo_os').text(os).attr('alt',os);
                        $('#dlg_motivo').show('fast');
                        $("#n_linha").val(linha);
                        $("#dlg_aux_os").val(os);
                        $('#dlg_motivo #dlg_fechar,#dlg_motivo #dlg_btn_cancel').click(function () {
                            $('#dlg_motivo input').val('');
                            $('#dlg_motivo').hide('fast');
                        });
                    }
                } else {
                    $('#dlg_motivo').hide('fast');
                    $('#dlg_motivo input').val('');
                    aguarde_sub = false;
                    alert(resposta);
                    return false;
                }
            });
        }
    }
    $('#dlg_motivo #dlg_btn_excluir').click(function () {
        var aguarde_sub = false;
        var str_motivo = $.trim($('#dlg_motivo input').val());
        var os = $('#dlg_motivo #motivo_os').attr('alt');
        var linha = $("#n_linha").val();
        if (str_motivo != '') {
            if(aguarde_sub == true){
                alert('<?=traduz("Aguarde a Submissão")?>');
                return;
            }
            aguarde_sub = true;
            $.get('os_consulta_lite.php',
            {'finalizar_os_trom':'true','motivo':str_motivo,'id_os':os},
            function(resposta) {
                if (resposta == 'ok') {
                    var os = $('#dlg_motivo #motivo_os').text();
                    $('#exclusao').show();
                    setTimeout(function(){
                        $('#dlg_motivo').hide('fast');
                        $('#dlg_motivo input').val('');
                        $("#exclusao").html('');
                    },2000);
                    aguarde_sub = false;
                    $('#btn_finaliza_'+linha).hide();
                    $("#st_ch_"+os).css("background-color","#8DFF70");
                    $("#td_alterar_"+linha).find('img').hide();
                    $("#td_trocar_"+linha).find('img').hide();
                } else {
                    $('#dlg_motivo').hide('fast');
                    $('#dlg_motivo input').val('');
                    aguarde_sub = false;
                    alert(resposta);
                    return false;
                }
            });//END of GET
        } else {
            aguarde_sub = false;
            alert('<?=traduz('Digite um motivo ou cancele a finalização.')?>');
        }
    });
    <?php
        if (in_array($login_fabrica, array(101,141,144))) {
    ?>

            function solicitaTroca(os, button) {
                if (os != undefined && os > 0) {
                    $.ajax({
                        url: "os_consulta_lite.php",
                        type: "post",
                        data: { solicitaTroca: true, os: os },
                        complete: function(data) {
                            data = $.parseJSON(data.responseText);

                            if (data.erro) {
                                alert(data.erro);
                            } else {
                                <?php if (in_array($login_fabrica, array(101))) { ?>
                                    window.open('os_cadastro.php?os='+os+'&osacao=trocar');
                                <?php }else{ ?>
                                alert(data.ok);
                                button.remove();
                                <?php } ?>
                            }
                        }
                    });
                }
            }

        <?php
        }

        if(in_array($login_fabrica, array(85))){

            ?>

                function abreAtendimento(os, box){

                    $.ajax({
                        url : "<?php echo $_SERVER['PHP_SELF']; ?>",
                        type: "POST",
                        data: {
                            abrir_atendimento : "ok",
                            os : os
                        },
                        complete: function(data){
                            var hd_chamado = data.responseText;
                            hd_chamado = "<a href='callcenter_interativo_new.php?callcenter="+hd_chamado+"&os="+os+"' target='_blank'>"+hd_chamado+"</a>";
                            $('#box_'+box).html(hd_chamado);
                        }
                    });

                }

            <?php

        }
        if ($login_fabrica == 24 OR $login_fabrica == 74) { //hd_chamado=2588542

    ?>
            function congelar_os(os,status,i){
                var res = '';
                if(confirm("Deseja alterar a OS : "+os)){
                    $.post('../admin/ajax_cancela_os.php',{sua_os :os},
                        function (resposta){

                            res = resposta.split("|");

                            var confirma = res[0]
                            var status_congelada = res[1];

                            if(confirma == "OK"){
                                if(status_congelada == "f"){
                                    $( "#box_"+i ).empty();
                                    $( "#box_"+i).append( " <img border='0' src='imagens_admin/congelar_os.jpg' onClick='congelar_os(\""+os+"\",\""+status_congelada+"\",\""+i+"\")'> " );
                                    alert("Os Descongelada com sucesso : "+os);
                                }else{
                                    $( "#box_"+i ).empty();
                                    $( "#box_"+i).append( " <img border='0' src='imagens_admin/descongelar_os.gif' onClick='congelar_os(\""+os+"\",\""+status_congelada+"\", \""+i+"\")'> " );
                                    alert("Os Congelada com sucesso : "+os);
                                }
                            }else{
                                alert(resposta);
                            }
                    });
                }
            }
    <?php
        }
    ?>
        $(window).load(function(){

        $("#content tr td").click(function(){
            let tr_pai = $(this).closest("tr");

            let a     = $.trim($(this).find("a:visible").text());
            let btn   = $.trim($(this).find("button:visible").text());
            let img   = $(this).find("img:visible");
            let input = $(this).find("input:visible");

            if (a == "" && btn == "" && !(img.length) && !(input.length)) {

                $("#content tr td").css({
                    "border-top" : "none",
                    "border-bottom" : "none"
                });

                if (!$(tr_pai).hasClass("destaque")) {
                    $("#content tr").removeClass("destaque");
                    $(tr_pai).addClass("destaque");
                } else {
                    let os = $(tr_pai).data("os");

                    window.open("os_press.php?os="+os, '_blank');
                }

            } else {

                $("#content tr td").css({
                    "border-top" : "none",
                    "border-bottom" : "none"
                });

                $("#content tr").removeClass("destaque");

                $(this).closest("tr").addClass("destaque").css({
                    "border-top" : "2px solid black",
                    "border-bottom" : "2px solid black"
                });;

            }

            $(tr_pai).find("td").css({
                "border-top" : "2px solid black",
                "border-bottom" : "2px solid black"
            });

        });


        const table = $('#content');
        const tableWidth = $(table).css('width');
        const fixedTableHeader = $('<div></div>', {
            id: 'fixed-table-header',
            css: {
                width: tableWidth
            }
        });
        const firstRow     = $(table).find('thead tr');
        const totalColumns = $(firstRow).find('th').length;
        let colWidth, colText, colHeight;

        for (var i = 0; i < totalColumns; i++) {
            colWidth = $($(firstRow).find('th')[i]).css('width');
            colText  = $($(firstRow).find('th')[i]).text().trim();
            colHeight = $(firstRow).css('height');

            $(fixedTableHeader).append($('<div></div>', {
                css: {
                    width: colWidth,
                    maxWidth: colWidth,
                    height: colHeight,
                    'padding-top' : '15px'
                },
                text: colText
            }));
        }

        $(table).before(fixedTableHeader);

        $(window).scroll(function() {
            let tableTop    = $(table).offset().top;
            let tableHeight = $(table).css('height');
            let scrollY     = window.scrollY;
            let scrollX     = window.scrollX;

            tableHeight = parseInt(tableHeight.replace("px"));

            $(fixedTableHeader).css({
                left: '-'+scrollX+'px'
            });

            if (scrollY > tableTop && scrollY < tableHeight + 400) {
                $(fixedTableHeader).show();
            } else {
                $(fixedTableHeader).hide();
            }

        });

    });
</script>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
   <tr>
	<td>&nbsp;</td>
	<td colspan='3'>
	<input type="radio" name="data_tipo" value="abertura" <?=($data_tipo == "abertura" OR strlen($data_tipo) == 0) ? "checked" : ""; ?> ><?=traduz(' Data Abertura')?>
	<input type="radio" name="data_tipo" value="digitacao" <?=($data_tipo == "digitacao") ? "checked" : ""; ?>><?=traduz(' Data Digitação')?>
<?php
    if ($login_fabrica == 158) {
?>
        <input type="radio" name="data_tipo" value="integracao" <?=($data_tipo == "integracao") ? "checked" : ""; ?>><?=traduz(' Data Integração')?>
<?php
    }
    if (in_array($login_fabrica, [164,174]) || $telecontrol_distrib) {
?>
        <input type="radio" name="data_tipo" value="fechamento" <?=($data_tipo == "fechamento") ? "checked" : ""; ?>><?=traduz(' Data Fechamento')?>
<?php
    }
?>
	</td>
    </tr>
    <?php
        $width_vazio    = "100";
        $width_data_ini = "200";
        $width_data_fin = "200";
        $width_data_ext = "200";
        if ($login_fabrica == 148) {
            $width_vazio    = "15%";
            $width_data_ini = "30%";
            $width_data_fin = "12%";
            $width_data_ext = "43%";
        }
    ?>
    <tr>
        <td align='left' width="<?php echo $width_vazio;?>"> &nbsp; </td>
        <td align='left' width="<?php echo $width_data_ini;?>">&nbsp;<?=traduz('Data Inicial')?></td>
        <td align='left' width="<?php echo $width_data_fin;?>">&nbsp;<?=traduz('Data Final')?></td>
        <td align='left' width="<?php echo $width_data_ext;?>">&nbsp;</td>
    </tr>

    <tr valign='top'>
        <td> &nbsp; </td>
        <td>
            <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo (strlen($data_inicial) > 0) ? substr($data_inicial,0,10) : ""; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
        </td>
        <td>
            <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo ""; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
            &nbsp;
        </td>
        <td>
            <input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> ><label for="os_aberta"><?=traduz('Apenas OS em aberto ')?></label>
            <?php if ($login_fabrica == 158) { ?>
                    <br />
                    <input type='checkbox' name='os_aberta_kof' value='1' <? if (strlen ($os_aberta_kof) > 0 ) echo " checked " ?> ><label for="os_aberta_kof"><?=traduz('Apenas OS Kof ')?></label>
            <?php } ?>
            <?php if ($login_fabrica == 1) { ?>
                    <br />
                    <input type='checkbox' name='os_garantia_estendida' value='1' <?=(strlen ($os_garantia_estendida) > 0 ) ? " checked " : ""?> ><label for="os_garantia_estendida"><?=traduz('Apenas OS Garantia Estendida');?></label><br />
                    <input type='checkbox' name='os_estoque_posto' value='1' <?=(strlen ($os_estoque_posto) > 0 ) ? " checked " : ""?> ><label for="os_estoque_posto"><?=traduz('OS Com Movimentação de Estoque Temporário');?></label><br /> 
            <?php }
                  if ($login_fabrica == 74) {?>
	    <input type='checkbox' name='os_atendida' value='1' <? if (strlen ($os_atendida) > 0 ) echo " checked " ?> ><label for="os_atendida"><?=traduz('Apenas OS não atendida ')?></label><br />
	    <input type='checkbox' name='os_callcenter' value='1' <? if (strlen ($os_callcenter) > 0 ) echo " checked " ?> ><label for="os_callcenter"><?=traduz('Apenas OS abertas Callcenter ')?></label>
<?
}
if (in_array($login_fabrica, [3,30])) {
?>
            <br> <input type='checkbox' name='os_cancelada' value='1' <? if (strlen ($os_cancelada) > 0 ) echo " checked " ?> ><label for="os_cancelada"><?=traduz('Apenas OS Canceladas ')?></label>

<? }
            if ($login_fabrica == 42) {?>
            <br> <input type='checkbox' name='entrega_tecnica' value='t' <? if ($_POST["entrega_tecnica"] == "t" ) echo " checked " ?> ><label for="entrega_tecnica"><?=traduz('OS de entrega técnica')?></label>
<?
            }
            if($login_fabrica==35){
?>
                    <br>
                    <input type='checkbox' id='os_finalizada' name='os_finalizada' value='1' <? if (strlen ($os_finalizada) > 0 ) echo " checked " ?> onClick="clika_a();"><label for="os_finalizada"><?=traduz('Apenas OS Fechada')?></label>
<?php
            }else{
?>
                &nbsp;
<?php
            }
?>
        </td>
    </tr>

    </table>
<?
        }
?>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <?php
    if ($login_fabrica == 158) {
    ?>
        <tr>
            <td width="100px"> &nbsp; </td>
            <td align='left' colspan="2" >&nbsp;<?=traduz('Tipo')?></td>
        </tr>
        <tr>
            <td> &nbsp; </td>
            <td colspan="2" >
                <input type="radio" name="tipo_garantia" value="" checked /><?=traduz(' Todas')?>
                <input type="radio" name="tipo_garantia" value="garantia" <?=($_REQUEST["tipo_garantia"] == "garantia") ? "checked" : ""?> /><?=traduz(' Garantia')?>
                <input type="radio" name="tipo_garantia" value="fora_garantia" <?=($_REQUEST["tipo_garantia"] == "fora_garantia") ? "checked" : ""?> /><?=traduz(' Fora de Garantia')?>
            </td>
        </tr>
        <tr>
            <td> &nbsp; </td>
        </tr>
    <?php
    }
    ?>
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="200px">&nbsp;<?=traduz('Posto')?></td>
        <td align='left' width="400px">&nbsp;<?=traduz('Nome do Posto')?></td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td>
            <input type="text" name="codigo_posto" id="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto ('',document.frm_consulta.codigo_posto, '');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
            <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="<?=traduz('Clique aqui para pesquisar postos pelo código')?>" onclick="javascript: fnc_pesquisa_posto ('',document.frm_consulta.codigo_posto, '')">
        </td>
        <td>
            <input type="text" name="posto_nome" id="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto ('','', document.frm_consulta.posto_nome);" <? } ?> value="<?echo $posto_nome?>" class="frm">
            <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="<?=traduz('Clique aqui para pesquisar postos pelo código')?>" onclick="javascript: fnc_pesquisa_posto ('','', document.frm_consulta.posto_nome)">
        </td>
    </tr>
</table>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="200px"><?php echo ($login_fabrica==3 OR $login_fabrica == 86 OR $login_fabrica == 52 or $multimarca == 't') ? "Marca" : ""; ?></td>
        <td align='left' width="400px"><?=traduz('Nome do Consumidor')?></td>
    </tr>

    <tr>
        <td> &nbsp; </td>
        <td>
<?
        if(in_array($login_fabrica, array(3,52,86)) or $multimarca == 't'){
            echo "<select name='marca' size='1' class='frm' style='width:95px'>";
            echo "<option value=''></option>";
            $sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica order by nome";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res)>0){
                for($i=0;pg_num_rows($res)>$i;$i++){
                    $xmarca = pg_fetch_result($res,$i,marca);
                    $xnome = pg_fetch_result($res,$i,nome);
                    ?>
                    <option value="<?echo $xmarca;?>" <? //HD 73808 if ($xmarca == $marca) echo " SELECTED "; ?>><?echo $xnome;?></option>
                    <?
                }
            }
            echo "</SELECT>";
        }
?>
        </td>
        <!-- HD 216395: Mudar todas as buscas de nome para LIKE com % apenas no final. A funcao function mostrarMensagemBuscaNomes() está definida no js/assist.js -->
        <td><input type="text" name="consumidor_nome" size="30" value="<?echo $consumidor_nome?>" class="frm"> <img src='imagens/help.png' title='<?=traduz("Clique aqui para ajuda na busca deste campo")?>' onclick='mostrarMensagemBuscaNomes()'></td>
    </tr>
</table>

<?php

        if($login_fabrica == 86){
            $array_estado = array(  "Norte"         => "Região Norte(AC, AP, AM, PA, RO, RR, TO)"           ,
                            "Nordeste"      => "Região Nordeste(AL, BA, CE, MA, PB, PE, PI, RN, SE)",
                            "Centro_oeste"  => "Região Centro-Oeste(DF, GO, MT, MS)"                ,
                            "Sudeste"       => "Região Sudeste(ES, MG, RJ, SP)"                     ,
                            "Sul"           => "Região Sul(PR, RS, SC)"                             ,
                            "AC"            => "AC - Acre"                                          ,
                            "AL"            => "AL - Alagoas"                                       ,
                            "AM"            => "AM - Amazonas"                                      ,
                            "AP"            => "AP - Amapá"                                         ,
                            "BA"            => "BA - Bahia"                                         ,
                            "CE"            => "CE - Ceará"                                         ,
                            "DF"            => "DF - Distrito Federal"                              ,
                            "ES"            => "ES - Espírito Santo"                                ,
                            "GO"            => "GO - Goiás"                                         ,
                            "MA"            => "MA - Maranhão"                                      ,
                            "MG"            => "MG - Minas Gerais"                                  ,
                            "MS"            => "MS - Mato Grosso do Sul"                            ,
                            "MT"            => "MT - Mato Grosso"                                   ,
                            "PA"            => "PA - Pará"                                          ,
                            "PB"            => "PB - Paraíba"                                       ,
                            "PE"            => "PE - Pernambuco"                                    ,
                            "PI"            => "PI - Piauí"                                         ,
                            "PR"            => "PR - Paraná"                                        ,
                            "RJ"            => "RJ - Rio de Janeiro"                                ,
                            "RN"            => "RN - Rio Grande do Norte"                           ,
                            "RO"            => "RO - Rondônia"                                      ,
                            "RR"            => "RR - Roraima"                                       ,
                            "RS"            => "RS - Rio Grande do Sul"                             ,
                            "SC"            => "SC - Santa Catarina"                                ,
                            "SE"            => "SE - Sergipe"                                       ,
                            "SP"            => "SP - São Paulo"                                     ,
                            "TO"            => "TO - Tocantins"
                    );
        }else{
            $array_estado = array(  "AC"=>"AC - Acre"                   ,
                            "AL"=>"AL - Alagoas"                ,
                            "AM"=>"AM - Amazonas"               ,
                            "AP"=>"AP - Amapá"                  ,
                            "BA"=>"BA - Bahia"                  ,
                            "CE"=>"CE - Ceará"                  ,
                            "DF"=>"DF - Distrito Federal"       ,
                            "ES"=>"ES - Espírito Santo"         ,
                            "GO"=>"GO - Goiás"                  ,
                            "MA"=>"MA - Maranhão"               ,
                            "MG"=>"MG - Minas Gerais"           ,
                            "MS"=>"MS - Mato Grosso do Sul"     ,
                            "MT"=>"MT - Mato Grosso"            ,
                            "PA"=>"PA - Pará"                   ,
                            "PB"=>"PB - Paraíba"                ,
                            "PE"=>"PE - Pernambuco"             ,
                            "PI"=>"PI - Piauí"                  ,
                            "PR"=>"PR - Paraná"                 ,
                            "RJ"=>"RJ - Rio de Janeiro"         ,
                            "RN"=>"RN - Rio Grande do Norte"    ,
                            "RO"=>"RO - Rondônia"               ,
                            "RR"=>"RR - Roraima"                ,
                            "RS"=>"RS - Rio Grande do Sul"      ,
                            "SC"=>"SC - Santa Catarina"         ,
                            "SE"=>"SE - Sergipe"                ,
                            "SP"=>"SP - São Paulo"              ,
                            "TO"=>"TO - Tocantins"
                    );
        }
?>
<?php if ($login_fabrica == 158 && empty($login_cliente_admin)) { ?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> </td>
        <td align='left' width="400px"><?=traduz('Unidade de Negócio')?></td>
        <td align='left' width="200px"> &nbsp; </td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td align='left' width="400px">
            <select id="unidadenegocio" multiple="multiple" name="unidadenegocio[]" class="span12 selectUnidade" >
                <?php
                    if (isset($_GET['unidadenegocio']) && strlen($_GET['unidadenegocio']) > 0) {
                        $unidadesnegocios = explode("-", $_GET['unidadenegocio']);
                    } else {
                        $unidadesnegocios = $_POST['unidadenegocio'];
                    }

                    $distribuidores_disponiveis = $oDistribuidorSLA->SelectUnidadeNegocioNotIn();

                    foreach ($distribuidores_disponiveis as $unidadeNegocio) {
                        if (in_array($unidadeNegocio["unidade_negocio"], array(6102,6103,6104,6105,6106,6107,6108))) {
                            unset($unidadeNegocio["unidade_negocio"]);
                            continue;
                        }
                        $unidade_negocio_agrupado[$unidadeNegocio["unidade_negocio"]] = $unidadeNegocio["cidade"];
                    }

                    foreach ($unidade_negocio_agrupado as $unidade => $descricaoUnidade) {
                        $selected = (in_array($unidade, $unidadesnegocios)) ? 'SELECTED' : '';
                        echo "<option value='{$unidade}' {$selected}> {$descricaoUnidade}</option>";
                    }
                ?>
            </select>
        </td>
        <td> &nbsp; </td>
    </tr>
</table>
<?php }?>
<?php
    if (!in_array($login_fabrica, array(180,181,182))){ ?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px">&nbsp; </td>
        <td align='left' width="400px"><?=traduz('Estado')?></td>
        <td align='left' width="200px"> &nbsp; </td>
    </tr>

    <tr>
        <td> &nbsp; </td>
        <td>
            <select name="estado" id="estado" size="1" class="frm" style="width:350px">
            <option value=""><?=traduz('Selecione um Estado')?><?php if($login_fabrica == 86){ echo traduz(" ou Região"); }?></option>
<?php
        foreach ($array_estado as $k => $v) {
            echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
        }
?>
            </select>
        </td>
        <td> &nbsp; </td>
</table>
<? } ?>
<?php if ($login_fabrica == 52) { ?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="400px"><?=traduz('País')?></td>
        <td align='left' width="200px"> &nbsp; </td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td>
            <select id="consumidor_pais" name="consumidor_pais" class="frm">
                <option value=""></option>
                <?php
                    $aux_sql = "SELECT pais, nome FROM tbl_pais";
                    $aux_res = pg_query($con, $aux_sql);
                    $aux_row = pg_num_rows($aux_res);

                    for ($wz = 0; $wz < $aux_row; $wz++) {
                        $aux_pais = pg_fetch_result($aux_res, $wz, 'pais');
                        $aux_nome = pg_fetch_result($aux_res, $wz, 'nome');

                        if (strlen($_POST["consumidor_pais"]) > 0) {
                            if ($_POST["consumidor_pais"] == $aux_pais) {
                                $selected = "selected";
                            } else {
                                $selected = "";
                            }
                        }
                        ?> <option <?=$selected;?> value="<?=$aux_pais;?>"><?=$aux_nome;?></option> <?
                    }
                ?>
            </select>
        </td>
    </tr>
</table>
<?php }
if($login_fabrica == 30){
?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="400px"><?=traduz('Cidade')?></td>
        <td align='left' width="200px"> &nbsp; </td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td><input type='text' class="frm" id='cidade' name='cidade' value='<?=$cidade?>'>
        <td> &nbsp; </td>
    </tr>
</table>
<?
}
?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<?
        if ($login_fabrica == 45 || $login_fabrica == 80) {
?>
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="600px">
            Região
        </td>
    </tr>
<?
        }
        if($login_fabrica==50){
?>
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="600px">
            Status OS
        </td>
    </tr>
<?
        }
?>
</table>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="600px">
<?
        if($login_fabrica==45){
            echo "<select name='regiao' size='1' class='frm' style='width:370px'>";
?>
                <option value=''></option>
                <option value='1' <? if ($regiao == 1) echo " SELECTED "; ?>>Estado de São Paulo </option>
                <option value='2' <? if ($regiao == 2) echo " SELECTED "; ?>>Sul (SC,RS e PR)</option>
                <option value='3' <? if ($regiao == 3) echo " SELECTED "; ?>>Sudeste (RJ, ES e MG)</option>
                <option value='5' <? if ($regiao == 5) echo " SELECTED "; ?>>Nordeste (SE, AL, PE, PB e BA)</option>
                <option value='7' <? if ($regiao == 7) echo " SELECTED "; ?>>Centro-Oeste, Norte e Nordeste (GO, MS, MT, DF, CE, RN, TO, PA, AP, RR, AM, AC, RO, MA, PI)</option>
<?
            echo "</SELECT>";
        }elseif($login_fabrica==80){
            echo "<select name='regiao' size='1' class='frm' style='width:320px'>";
?>
                <option value=''></option>
                <option value='1' <? if ($regiao == 1) echo " SELECTED "; ?>>PE/PB</option>
                <option value='2' <? if ($regiao == 2) echo " SELECTED "; ?>>RJ/GO/MG/AC/AM/DF/ES/PI/MA/MS/MT/PA/PR/RO/RR/RS/SC/TO/AP</option>
                <option value='3' <? if ($regiao == 3) echo " SELECTED "; ?>>BA/SE/AL</option>
                <option value='4' <? if ($regiao == 4) echo " SELECTED "; ?>>CE/RN/SP</option>
<?
            echo "</SELECT>";
        }elseif($login_fabrica==50){
?>
                <select name='tipo_os' size='1' class='frm' style='width:300px'>";
                <option value=''></option>
                <option value='reincidente' <? if ($tipo_os == 'REINCIDENTE') echo " SELECTED "; ?>><?=traduz('Reincidências')?></option>
                <option value='mais_cinco_dias' <? if ($tipo_os == 'MAIS_CINCO_DIAS') echo " SELECTED "; ?>><?=traduz('Mais de 5 dias sem data de fechamento')?></option>
                <option value='mais_dez_dias' <? if ($tipo_os == 'MAIS_DEZ_DIAS') echo " SELECTED "; ?>><?=traduz('Mais de 10 dias sem data de fechamento')?></option>
                <option value='mais_vinte_dias' <? if ($tipo_os == 'MAIS_VINTE_DIAS') echo " SELECTED "; ?>><?=traduz('Mais de 20 dias sem data de fechamento')?></option>
                <option value='excluidas' <? if ($tipo_os == 'EXCLUIDAS') echo " SELECTED "; ?>><?=traduz('Excluídas do sistema')?></option>
                <option value='os_com_troca' <? if ($tipo_os == 'OS_COM_TROCA') echo " SELECTED "; ?>><?=traduz('OS com Troca de Produto')?></option>
                </SELECT>
<?
        }
?>
        </td>
    </tr>
</table>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="200px"><?=traduz('Ref. Produto')?></td>
        <td align='left' width="400px"><?=traduz('Descrição Produto')?></td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td>
        <input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<?=$produto_referencia?>" >
        &nbsp;
        <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto ('', document.frm_consulta.produto_referencia,'','')">
        </td>
        <td>
        <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
        &nbsp;
        <img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_descricao, '','','')">
    </tr>
</table>


<?php

        if($login_fabrica == 52) {
?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <!-- <td align='left' width="200px"><?=traduz('Cliente Fricon')?></td> -->
        <td align='left' width="200px"><?=traduz('Número Ativo')?></td>
        <td align='left' width="400px"><?=traduz('Cidade do consumidor')?></td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td>
        <input class="frm" type="text" name="numero_ativo" id="numero_ativo" size="15" maxlength="20" value="<?php echo $numero_ativo;?>" >
        </td>
        <td>
        <input class="frm" type="text" name="cidade_do_consumidor" id="cidade_do_consumidor" size="30" value="<? echo $cidade_do_consumidor;?>" >
    </tr>
</table>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="200px"><?=traduz('Número do Atendimento')?></td>
        <td align='left' width="400px">&nbsp; </td>
    </tr>
    <tr>
        <td> &nbsp; </td>
        <td>
        <input class="frm" type="text" name="hd_chamado_numero" id="hd_chamado_numero" size="15" maxlength="20" value="<?php echo $hd_chamado_numero;?>" >
        </td>
        <td>&nbsp;</tr>
</table>

<?php
        }
?>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<?
        if ($login_fabrica == 3) {
?>

    <tr>
        <td align='left' width="100px">&nbsp;</td>
        <td align='left' width="400px"><?=traduz('Admin')?></td>
        <td align='left' width="200px">&nbsp;</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>
        <select name="admin" size="1" class="frm">
            <option value=''></option>
<?
            $sql =  "SELECT admin, login
                    FROM tbl_admin
                    WHERE fabrica = $login_fabrica
                    ORDER BY login;";
            $res = pg_query($con,$sql);
            if (pg_num_rows($res) > 0) {
                for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
                    $x_admin = pg_fetch_result($res,$i,admin);
                    $x_login = pg_fetch_result($res,$i,login);
                    echo "<option value='$x_admin'";
                    if ($admin == $x_admin) echo " selected";
                    echo ">$x_login</option>";
                }
            }
?>
            </select>
        </td>
        <td>&nbsp;</td>
    </tr>

<?
        }
?>
</table>


<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px">&nbsp;</td>
        <td align='left' width="200px"><input type="radio" id="os_situacao_aprovada" name="os_situacao" value="APROVADA" <? if ($os_situacao == "APROVADA") echo "checked"; ?>><label for="os_situacao_aprovada">OS's <?=traduz('Aprovadas')?></label>
        </td>
        <td align='left' width="<?= (in_array($login_fabrica, [24])) ? '200px' : '400px'?>">
            <input type="radio" id="os_situacao_paga" name="os_situacao" value="PAGA" <? if ($os_situacao == "PAGA") echo "checked"; ?>>
            <label for="os_situacao_paga"><?=traduz('OSs Pagas')?></label>
        </td>
<?php 
        if ($login_fabrica == 24) { 
?>
        <td align='left' width="200px">
            <input type="radio" id="os_bloqueadas" name="os_situacao" value="BLOQUEADA" <? if ($os_situacao == "BLOQUEADA") echo "checked"; ?>>
            <label for="os_bloqueadas">OS's Bloqueadas</label>
        </td>

<?php   } 
?>

<?php
        if ($login_fabrica == 145) {
?>
        <td align='left' width="400px">
            <input type="radio" id="os_situacao_finalizada_sem_extrato" name="os_situacao" value="FINALIZADASEMEXTRATO" <?php if ($os_situacao == "FINALIZADASEMEXTRATO") echo "checked"; ?>>
            <label for="os_situacao_finalizada_sem_extrato"><?=traduz('OS´s Finalizadas sem Extrato')?></label>
        </td>
<?php
        }
        if ($login_fabrica == 50) {
?>
        <td align='left' width="400px">
            <input type="radio" id="os_mao_obra_zerada" name="os_situacao" value="MAOOBRAZERADA" <?php if ($os_situacao == "MAOOBRAZERADA") echo "checked"; ?>>
            <label for="os_mao_obra_zerada"><?=traduz('OS´s Com Mão-de-obra zerada')?></label>
        </td>
<?php
        }
?>
    </tr>
    <?php if($login_fabrica == 35){ ?>
        <tr>
            <td></td>
            <td align='left' width="400px" colspan="2">
                <input type="checkbox" name="os_aguardando_troca" id="os_aguardando_troca" value="t" <?php if($os_aguardando_troca == 't') echo " checked "; ?>>
                <label for="os_situacao_finalizada_sem_extrato"><?=traduz('OSs que estão aguardando ser revertidas para troca')?></label>
            </td>
        </tr>
    <?php } ?>
</table>
<?php
if ($login_fabrica == 24 OR $login_fabrica == 74) { //hd_chamado=2588542
?>
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <tr>
        <td align='left' width="100px">&nbsp;</td>
        <td align='left' width="200px">
            <input type="radio" id="os_congelada" name="os_congelada" value="congelada" <? if ($os_congelada == "congelada") echo "checked"; ?>>
            <label for="os_congelada"><?=traduz('OSs congeladas')?></label>
        </td>
        <td align='left' width="400px">
            <input type="radio" id="os_para_congelar" name="os_congelada" value="congelar" <? if ($os_congelada == "congelar") echo "checked"; ?>>
            <label for="os_para_congelar"><?=traduz('OSs para congelar')?></label>
        </td>
    </tr>
</table>

<?php
}
?>

<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <? if (!in_array($login_fabrica,array(6,30,52,158,176)) || $usaPreOS) { ?>
        <tr align='left' class="subtitulo">
            <td colspan='3'><center><?=traduz('Consultar Pré-Ordem de Serviço')?></center></td>
        </tr>
        <tr>
            <td align='left' width="100px">&nbsp; </td>
            <td align='left' width="400px"><?=traduz('Número do Atendimento')?><input type='text' name='pre_os' id='pre_os' class='frm'></td>
            <td align='left' width="200px"><input type="submit" name="btn_acao_pre_os" value="<?=traduz('Pesquisar Pré-OS')?>"></td>
        </tr>
    <? }
    if ($login_fabrica==3) {
        if ($posto_ordenar == 'sim') {
            $checked ='CHECKED';
        } ?>
    <tr>
        <td> &nbsp; </td>
        <td align='left' colspan='2'><input type="checkbox" name="posto_ordenar" value="sim" <?=$checked;?>><?=traduz('Ordenar por Posto ')?></td>
    </tr>
    <? }
    if ($login_fabrica == 20) {
        // MLG 2009-08-04 HD 136625
        $sql = "SELECT pais,nome FROM tbl_pais where america_latina is TRUE;";
        $res = pg_query($con,$sql);
        $p_tot = pg_num_rows($res);
        for ($i=0; $i<$p_tot; $i++) {
            list($p_code,$p_nome) = pg_fetch_row($res, $i);
            $sel_paises .= "\t\t\t\t<option value='$p_code'";
            $sel_paises .= ($pais==$p_code)?" selected":"";
            $sel_paises .= ">$p_nome</option>\n";
        } ?>
        <tr>
            <td> &nbsp; </td>
            <td colspan='2'>País<br>
                <select name='pais' size='1' class='frm'>
                 <option></option>
                <?= $sel_paises;?>
                </select>
            </td>
        </tr>
    <? }
    if (in_array($login_fabrica, array(11, 172))) { ?>
        <!-- <tr>
            <td colspan="3" style="text-align:center;padding: 0 40px;">Os seguintes campos estão habilitados para consulta de pré-OS:<br /><strong>Data Inicial / Data Final, Número de Série, NF Compra, CPF Consumidor, Nome do Consumidor, Posto, Nome do Posto, Ref. Produto e Descrição do Produto.</strong></td>
        </tr> -->
    <? } ?>

    <tr>
        <td colspan='3'> <hr> </td>
    </tr>
    <tr>
        <td align='left' width="100px"> &nbsp; </td>
        <td align='left' width="400px"><?=traduz(' OS em aberto da Revenda = CNPJ')?>
        <!-- HD 286369: Voltando pesquisa de CNPJ da revenda para apenas 8 dígitos iniciais -->
        <input class="frm" type="text" name="revenda_cnpj" size="12" maxlength='8' value="<? echo $revenda_cnpj ?>" > /0000-00
        </td>
        <td align='left' width="200px"> &nbsp; </td>
    </tr>

<?
        if($login_fabrica==7){ // HD 75762 para Filizola ?>
        <tr>
            <td colspan='3'> <hr> </td>
        </tr>
        <tr>
            <td> &nbsp; </td>
            <td colspan='2'>
                <?=traduz('Classificação da OS')?>
                <select name='classificacao_os' id='classificacao_os' size="1" class="frm">
                    <option value='' selected></option>
<?
            $sql = "SELECT  *
                    FROM    tbl_classificacao_os
                    WHERE   fabrica = $login_fabrica
                    AND     ativo IS TRUE
              ORDER BY      descricao";
            $res = @pg_query ($con,$sql);
            if(pg_num_rows($res) > 0){
                for($i=0; $i < pg_num_rows($res); $i++){
                    $classificacao_os=pg_fetch_result($res,$i,classificacao_os);
                    $descricao=pg_fetch_result($res,$i,descricao);
                    echo "<option value='$classificacao_os'>$descricao</option>\n";
                }
            }
?>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan='3'> <hr> </td>
        </tr>
        <tr>
            <td> &nbsp; </td>
            <td colspan='2'>
                Natureza
                <select name="natureza" class="frm">
                    <option value='' selected></option>
<?
            $sqlN = "SELECT *
                FROM tbl_tipo_atendimento
                WHERE fabrica = $login_fabrica
                AND   ativo IS TRUE
                ORDER BY tipo_atendimento";
            $resN = pg_query ($con,$sqlN) ;

            for ($z=0; $z<pg_num_rows($resN); $z++){
                $xxtipo_atendimento = pg_fetch_result($resN,$z,tipo_atendimento);
                $xxcodigo           = pg_fetch_result($resN,$z,codigo);
                $xxdescricao        = pg_fetch_result($resN,$z,descricao);

                echo "<option ";
                $teste1 = $natureza;
                $teste2 = $xxtipo_atendimento;
                if($natureza==$xxtipo_atendimento) echo " selected ";
                echo " value='" . $xxtipo_atendimento . "'" ;
                echo " > ";
                echo $xxcodigo . " - " . $xxdescricao;
                echo "</option>\n";
            }
?>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan='3'> <hr> </td>
        </tr>
        <tr>
            <td> &nbsp; </td>
            <td colspan='2'>
                Aberto por
                <select name="admin_abriu" class="frm">
                    <option value='' selected></option>
                    <?
                    $sqlM = "
                        SELECT
                            admin,
                            nome_completo
                        FROM tbl_admin
                        WHERE fabrica = $login_fabrica
                        AND ativo IS TRUE
                        ORDER BY nome_completo;
                    ";

                    $resM = pg_query ($con,$sqlM);

                    for ($j=0; $j<pg_num_rows($resM); $j++){
                        $jadmin = pg_fetch_result($resM,$j,admin);
                        $jadmin_nome = pg_fetch_result($resM,$j,nome_completo);

                        echo "<option ";
                        if($admin_abriu == $jadmin){
                            echo " selected ";
                        }
                        echo "value='" . $jadmin . "'>";
                        echo $jadmin_nome;
                        echo "</option>";
                    } ?>
                </select>
            </td>
        </tr>
    <? } ?>
</table>

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
    <?php
        $checked_gerar_excel   = (!empty($_POST["gerar_excel"])) ? "checked" : "";
        $checked_formato_excel = $_POST["formato_excel"];
    ?>
    <tr align='center'>
        <td align="right" style="width: 50%;">
            <input type="checkbox" name="gerar_excel" value="t" id="check_gerar_excel" <?= $checked_gerar_excel ?> />
            <label name="lbl_gerar_excel"><?=traduz('Gerar Excel')?></label>
        </td>
        <td align="left" style="width: 50%;">
            <div id="opcoes_excel" style="margin-left: 10px;display:none;">
                <label>
                    XLS <input type="radio" name="formato_excel" value="xls" <?= ($checked_formato_excel == "xls") ? "checked" : "" ?> />
                </label>
                &nbsp;&nbsp;
                <label>
                    CSV <input type="radio" name="formato_excel" value="csv" <?= ($checked_formato_excel == "csv" or empty($checked_formato_excel)) ? "checked" : "" ?> />
                </label>
            </div>
        </td>
    </tr>
    <tr>
      <td colspan='3' align='center'>
            <input type="submit" name="btn_acao" value="<?=traduz("Pesquisar")?>">
      </td>
    </tr>
    <?php
        if($login_fabrica == 72) {
    ?>
        <tr>
        <td colspan='4' align='center'>
            <input type="checkbox" name="consultar_os_sem_listar_pecas" value="t"><?=traduz(' Consultar OS sem listar peças')?><br><br>
        </td>
        </tr>
    <? }
        if (in_array($login_fabrica, array(30,138,152,180,181,182))) {
        if ($login_fabrica == 138) {
            $texto = traduz("Consultar OS Sem listar pedidos");
        } else {
            $texto = traduz("Consultar OS Sem listar peças");
        } ?>
        <tr>
            <td colspan="4" align="center">
                <input type='checkbox' name='sem_listar_peca' value='1' <? if (strlen ($sem_listar_peca) > 0 || (!isset($_POST['sem_listar_peca']) && $login_fabrica == 30)) echo " checked " ?> ><label for="sem_listar_peca"><?=$texto;?></label><br />
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
    <? } ?>
</table>
</table>

<script>
Shadowbox.init();
<?php if (in_array($login_fabrica, array(152,180,181,182))) { ?>
    $("input[name=sem_listar_peca]").on("click",function(){
        if($(this).is(":checked")){
            $("input[name=sem_listar_peca]").prop("checked", true);
        }else{
            $("input[name=sem_listar_peca]").prop("checked", false);
        }
    });
<?php }
if (in_array($login_fabrica, [169,170])) { ?>
    function shadowAlterarSerie(os) {
        Shadowbox.open({
            content: "ajax_alterar_serie.php?os="+os,
            player: "iframe",
            title: "Alterar Número de Série da OS "+os,
            width: 500,
            height: 183
        });
    }

    function atualizaLinhaSerie(os,serie) {
        $('#td_serie_'+os).html(serie);
        window.parent.Shadowbox.close();
    }
<?php }
?>
</script>
</form>
<? include "rodape.php"; ?>
