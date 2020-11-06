<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$login_fabrica = (strlen($_GET['lu_fabrica']) > 0) ? $_GET['lu_fabrica'] : $login_fabrica;
if(isset($_GET["verifica_distrib_geral"])){
    $os = $_GET["os"];

    $sql = "SELECT fabrica, posto, consumidor_revenda FROM tbl_os WHERE os = {$os}";
    $res = pg_query($con, $sql);

    $consumidor_revenda = pg_fetch_result($res, 0, "consumidor_revenda");
    $login_fabrica      = pg_fetch_result($res, 0, "fabrica");
    $login_posto        = pg_fetch_result($res, 0, "posto");
    $xlogin_posto       = pg_fetch_result($res, 0, "posto");

    include_once 'fn_traducao.php';

}else{
    include ($_GET['lu_os'] == 'sim') ? "login_unico_autentica_usuario.php" : 'autentica_usuario.php';
}

if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {
    if ($_POST['action'] == 'visita_realizada') {
        $os_visita  = $_POST['id'];
        $resposta   = $_POST['resposta'];

        $sql = "SELECT tbl_os_visita.hora_chegada_cliente,tbl_justificativa.justificativa,tbl_os_visita.os FROM tbl_os_visita LEFT JOIN tbl_justificativa USING(justificativa) WHERE tbl_os_visita.os_visita = {$os_visita}";
        $res = pg_query($con, $sql);

        $hora_chegada_cliente = (pg_fetch_result($res, 0, "hora_chegada_cliente")) ? 'null' : 'current_timestamp';
        $justificativa        = pg_fetch_result($res, 0, 'justificativa');
        $os                   = pg_fetch_result($res, 0, 'os');
        if ($hora_chegada_cliente == 'current_timestamp' && $justificativa == 1) {
            /* VERIFICA SE JÁ EXISTE UMA VISITA REALIZADA DO TIPO AVALIAÇÃO INICIAL*/
            $sql = "SELECT
                        tbl_os_visita.hora_chegada_cliente
                    FROM tbl_os_visita
                        LEFT JOIN tbl_justificativa USING(justificativa)
                    WHERE justificativa = {$justificativa} AND tbl_os_visita.os = {$os} AND hora_chegada_cliente IS NOT NULL";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0) {
                exit(json_encode(array("erro" => utf8_encode(traduz("nao.sera.possivel.confirmar.pois.ja.existe.uma.visita.do.tipo.avaliacao.inicial.realizada")))));
            }
        }
        $msg_retorno = ($resposta == 'nao') ? utf8_encode(traduz("nao.realizada.por.favor.reagendar")) : traduz("realizada.com.sucesso");

        if($resposta == 'sim'){
            $valores_resposta = " hora_chegada_cliente = {$hora_chegada_cliente},  justificativa_valor_adicional = null ";
        }else{
            $valores_resposta = " justificativa_valor_adicional = 'Visita não realizada. Respondida em ".date('d-m-Y H:m')."',  hora_chegada_cliente = null ";
        }

        $sql_visita = "UPDATE tbl_os_visita SET $valores_resposta WHERE os_visita = {$os_visita}";
        pg_query($con, $sql_visita);
        if (strlen(pg_last_error()) > 0)
            exit(json_encode(array("erro" => traduz("ocorreu.um.erro.ao.tentar.atualizar.a.visita"))));

        exit(json_encode(array("ok" => traduz("visita")." {$msg_retorno} ")));
    }
}

if ($_POST['ajax'] == 'faturamentoCorreios') {
    $codigoRastreamento = trim(strtoupper($_POST['codigoRastreamento']));

    if (!preg_match("/BR/", $codigoRastreamento)) {
        $response = ['error' => 'Código inexistente'];
    } else {
        $qRastreamento = "
            SELECT
                TO_CHAR(data, 'DD/MM/YYYY HH24:MM') AS data,
                situacao,
                local
                obs
            FROM tbl_faturamento_correio
            WHERE conhecimento = '{$codigoRastreamento}'
            AND fabrica = {$login_fabrica}
            ORDER BY data DESC
        ";
        $rRastreamento = pg_query($con, $qRastreamento);

        if (pg_num_rows($rRastreamento) == 0) {
            $response = ['error' => 'Nenhum registro encontrado'];
        } else {
            $rastreamentosAll = pg_fetch_all($rRastreamento);
            $response = array_map(function ($r) {
                $r['situacao'] = utf8_encode($r['situacao']);
                $r['obs'] = utf8_encode($r['obs']);
                $r['local'] = utf8_encode($r['local']);
                return $r;
            }, $rastreamentosAll);
        }
    }

    echo json_encode($response);
    exit;
}

if ($_POST['buscaCorreios']) {

    $objeto = $_POST['objeto'];
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://www2.correios.com.br/sistemas/rastreamento/resultado_semcontent.cfm");
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query(array("Objetos"=>$objeto)));
    $resultado = curl_exec($ch);
	curl_close($ch);
	$resultado = str_replace("home2014/img/SRO/","https://www2.correios.com.br/home2014/img/SRO/",$resultado);
	$resultado = str_replace("block","none",$resultado);

    echo ($resultado);
    exit;
}

if ($_POST["consertar_os"]) {
    $os = $_GET["os"];

    try {
        $sqlOsData = "SELECT data_abertura, data_conserto FROM tbl_os WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND os = {$os}";
        $resOsData = pg_query($con, $sqlOsData);
        $resOsData = pg_fetch_assoc($resOsData);

        $data_conserto   = $_POST["data_conserto"];

        if (empty($data_conserto)) {
            throw new \Exception(traduz("informe.a.data.de.conserto"));
        } else if (strlen($data_conserto) != 10) {
            throw new \Exception(traduz("informe.a.data.de.conserto"));
        } else {
            list($dia, $mes, $ano) = explode("/", $data_conserto);

            if (!strtotime("$ano-$mes-$dia")) {
                throw new \Exception(traduz("data.de.conserto.invalida"));
            }

            if (strtotime("$ano-$mes-$dia") < strtotime($resOsData["data_abertura"])) {
                throw new \Exception(traduz("data.de.conserto.nao.pode.ser.inferior.a.data.de.abertura"));
            } else {
                $data_conserto = "$ano-$mes-$dia 00:00";
            }

            $sql = "
                UPDATE tbl_os SET
                    data_conserto = '{$data_conserto}'
                WHERE fabrica = {$login_fabrica}
                AND posto = {$login_posto}
                AND os = {$os}
            ";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new \Exception(traduz("ocorreu.um.erro.ao.fechar.a.ordem.de.servico"));
            }
            
            $msg_sucesso = traduz("data.de.conserto.gravada.com.sucesso");

        }
    } catch (\Exception $e) {
        $msg_erro = $e->getMessage();
    }
}


if ($_POST["fechar_os"]) {
    $os = $_GET["os"];

    try {
        if (file_exists("classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
            include "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";

            $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
            $osClass = new $className($login_fabrica, $os, $con);
        } else {
            $osClass = new \Posvenda\Os($login_fabrica, $os, $con);
        }

        $calcula_os = true;

        if (in_array($login_fabrica, array(169,170))) {
            $sqlOsData = "SELECT data_abertura, data_conserto FROM tbl_os WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND os = {$os}";
            $resOsData = pg_query($con, $sqlOsData);
            $resOsData = pg_fetch_assoc($resOsData);

            $data_conserto   = $_POST["data_conserto"];
            $data_fechamento = date("Y-m-d");

            if (empty($data_conserto)) {
                throw new \Exception(traduz("informe.a.data.de.conserto"));
            } else if (strlen($data_conserto) != 10) {
                throw new \Exception("Data de conserto inválida");
            } else {
                list($dia, $mes, $ano) = explode("/", $data_conserto);

                if (!strtotime("$ano-$mes-$dia")) {
                    throw new \Exception(traduz("data.de.conserto.invalida"));
                }

                if (strtotime("$ano-$mes-$dia") < strtotime($resOsData["data_abertura"])) {
                    throw new \Exception(traduz("data.de.conserto.nao.pode.ser.inferior.a.data.de.abertura"));
                } else {
                    $data_conserto = "$ano-$mes-$dia 00:00";
                }
            }

            $sql = "
                UPDATE tbl_os SET
                    data_conserto = '{$data_conserto}'
                WHERE fabrica = {$login_fabrica}
                AND posto = {$login_posto}
                AND os = {$os}
            ";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new \Exception(traduz("ocorreu.um.erro.ao.fechar.a.ordem.de.servico"));
            }

            $sql = "
                SELECT
                    CASE WHEN COUNT(*) > 0 THEN 't' ELSE 'f' END
                FROM tbl_os_defeito_reclamado_constatado
                JOIN tbl_defeito_constatado USING(defeito_constatado,fabrica)
                WHERE fabrica = {$login_fabrica}
                AND os = {$os}
                AND lista_garantia = 'fora_garantia';
            ";
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {
                $defeito_fora_garantia = pg_fetch_result($res, 0, 0);

                if ($defeito_fora_garantia == "t") {
                    $calcula_os = false;
                }
            }
        }

        if ($calcula_os) {
            $osClass->calculaOs();
        }

        $osClass->finaliza($con);

        $sql = "
            SELECT o.sua_os
            FROM tbl_auditoria_os ao
            INNER JOIN tbl_os o ON o.os = ao.os AND o.fabrica = {$login_fabrica}
            WHERE ao.os = {$os}
            AND ao.liberada IS NULL
            AND ao.cancelada IS NULL
            AND ao.reprovada IS NULL
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $msg_sucesso = traduz("ordem.de.servico.fechada.e.em.auditoria.aguardando.aprovacao.da.fabrica");
        } else {
            $msg_sucesso = traduz("ordem.de.servico.fechada.com.sucesso");
        }

        $data_fechamento = true;
    } catch(\Exception $e) {
        unset($msg_sucesso);
        $msg_erro = $e->getMessage();

        if (in_array($login_fabrica, array(169,170))) {
            $old_data_conserto = (!empty($resOsData["data_conserto"])) ? "'{$resOsData['data_conserto']}'" : "null";

            $sql_up = "
                UPDATE tbl_os SET
                    data_fechamento = null,
					finalizada = null, 
                    data_conserto = case when data_conserto notnull then data_conserto else null end
                WHERE fabrica = {$login_fabrica}
                AND posto = {$login_posto}
                AND os = {$os}
            ";
            $res_up = pg_query($con, $sql_up);
        }
    }
}

$os = (int)trim($_GET['os']);
if(strlen($os) > 10) {
	$desabilita_tela = traduz('os.%.nao.encontrada', null, null, [$os]);
	include_once "cabecalho.php";
	exit;
}

if(in_array($login_fabrica, array(11,172))){

    if(strlen($os) > 0){

        $sql_fabrica = "SELECT fabrica FROM tbl_os WHERE os = '{$os}'";
        $res_fabrica = pg_query($con, $sql_fabrica);

        if(pg_num_rows($res_fabrica) > 0){

            $fabrica_os = pg_fetch_result($res_fabrica, 0, "fabrica");

            if($fabrica_os != $login_fabrica){

                $self = $_SERVER['PHP_SELF'];
                $self = explode("/", $self);

                unset($self[count($self)-1]);

                $page = implode("/", $self);
                $page = "http://".$_SERVER['HTTP_HOST'].$page."/token_cookie_changes.php";
                $pageReturn = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?os={$os}";

                $params = "?cook_admin=&cook_fabrica={$fabrica_os}&page_return={$pageReturn}";
                $page = $page.$params;

                header("Location: {$page}");
                exit;

            }
        }

    }

}

include_once "funcoes.php";
include_once 'helpdesk/mlg_funciones.php';

$cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_os.fabrica IN (11,172) " : " tbl_os.fabrica = $login_fabrica ";

use model\ModelHolder;
use html\HtmlBuilder;
use html\HtmlHelper;

$fabrica_anexa_os_press = isset($anexo_peca_os) and in_array($login_fabrica, array(138,148,157));


if(in_array($login_fabrica,array(152,180,181,182))){
    $os = trim($_GET["os"]);

        $sql_tipo_os = "SELECT tbl_tipo_atendimento.tipo_atendimento FROM tbl_tipo_atendimento
                                INNER JOIN tbl_os ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
                                WHERE tbl_tipo_atendimento.fabrica = {$login_fabrica} AND tbl_os.os = {$os}
                                AND tbl_tipo_atendimento.entrega_tecnica IS TRUE";
    $res_tipo_os = pg_query($con, $sql_tipo_os);

    if(pg_num_rows($res_tipo_os) > 0){

            header("Location: os_press_entrega_tecnica.php?os={$os}");
            exit;
    }

}

if (in_array($login_fabrica, array(1,3, 167,173,176,203)) or $anexo_os_revenda) {
    include_once "class/tdocs.class.php";
    $s3_tdocs = new TDocs($con, $login_fabrica);

    if($_POST['anexo_fechamento'] == 'anexo_fechamento_os'){
        $os = $_POST['os'];
        $file_fechamento = $_FILES['anexo_upload_fechamento'];

        if(strlen(trim($file_fechamento['name'])) == 0){
            $msg_erro = traduz("por.favor.inserir.o.anexo.do.fechamento.da.os");
        }else{
            $s3_tdocs->setContext('fechamento');
            if($s3_tdocs->uploadFileS3($file_fechamento, $os,  false)){
                header("Location: os_press.php?os={$os}");
                exit;
            }else{
                $msg_erro = traduz("erro.ao.anexar.arquivo");
            }
        }
    }
}

if(in_array($login_fabrica,array(145))){
    $os = trim($_GET["os"]);

        $sql_tipo_os = "SELECT tbl_tipo_atendimento.grupo_atendimento
                        FROM tbl_os
                        JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                        WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$login_fabrica}";
    $res_tipo_os = pg_query($con, $sql_tipo_os);

    if(pg_num_rows($res_tipo_os) > 0){

            $grupo_atendimento = strtoupper(pg_fetch_result($res_tipo_os, 0, "grupo_atendimento"));

        if($grupo_atendimento == "R"){
            header("Location: os_press_revisao.php?os={$os}");
            exit;
        }
    }

}

if (in_array($login_fabrica, array(137,141,144))) {// Verifica se o posto é Interno

    $sql = "SELECT posto
            FROM tbl_posto_fabrica
			JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                               AND tbl_tipo_posto.fabrica    = tbl_posto_fabrica.fabrica
                               AND tbl_tipo_posto.posto_interno
            WHERE tbl_posto_fabrica.fabrica = $login_fabrica
            AND tbl_posto_fabrica.posto     = $login_posto";
    $res = pg_query($con,$sql);
	$posto_interno = (pg_num_rows($res) > 0);
}

if ($login_fabrica == 74) {
    include "classes/FechamentoOS.php";
    $fechamentoOS = new FechamentoOS();
}

if ($login_fabrica == 3) {
    include "helpdesk.inc.php";
}

if($login_fabrica == 88){
  $limite_anexos_nf = 5;
}

include_once('anexaNF_inc.php');// Dentro do include estão definidas as fábricas que anexam imagem da NF e os parâmetros.

if ($S3_sdk_OK) {
	include_once S3CLASS;

	$s3_ge = new anexaS3('ge', (int) $login_fabrica); //Anexo garantia estendida para Elgin
	$S3_online = is_object($s3_ge);

	if ( in_array($login_fabrica, array(11,172)) ) {

		$s3ve = new anexaS3('ve', (int) $login_fabrica);
		$S3_online = is_object($s3ve);
	}

	if (in_array($login_fabrica, array(3,11,35,42,125,126,151,172))) {

		# A class AmazonTC está no arquivo assist/class/aws/anexaS3.class.php
		$amazonTC = new AmazonTC("os", $login_fabrica);
	}
}

if (in_array($login_fabrica, [177]) AND $_POST['ajax'] == 'gravaSaidaChegada') {
    $os = $_GET['os'];
    $dataSaida = "";
    $dataChegada = "";
    if (!empty($_POST['dataSaida'])) {
        $dataSaida = DateTime::createFromFormat('d/m/Y', $_POST['dataSaida'], new DateTimeZone("America/Sao_Paulo"));
    }

    if (!empty($_POST['dataChegada'])) {
        $dataChegada = DateTime::createFromFormat('d/m/Y', $_POST['dataChegada'], new DateTimeZone("America/Sao_Paulo"));
    }

    $qCamposAdicionais = "SELECT oce.os,
                                 oce.campos_adicionais
                          FROM tbl_os_campo_extra oce
                          JOIN tbl_os o ON oce.os = o.os AND o.fabrica = {$login_fabrica}
                          WHERE o.os = {$os}";
    $rCamposAdicionais = pg_query($con, $qCamposAdicionais);

    $camposAdicionais = [];
    if (pg_num_rows($rCamposAdicionais) > 0) {
        $rCamposAdicionais = pg_fetch_result($rCamposAdicionais, 0, 'campos_adicionais');
        $camposAdicionais = json_decode($rCamposAdicionais, true);

        $qType = "update";
    }

    $camposAdicionais['dataSaidaProduto'] = (!empty($dataSaida)) ? $dataSaida->format('Y-m-d H:i:s') : $dataSaida;
    $camposAdicionais['dataChegadaProduto'] = (!empty($dataChegada)) ? $dataChegada->format('Y-m-d H:i:s') : $dataChegada;

    pg_query($con, "BEGIN");

    $response = [];
    $jsonCampos = json_encode($camposAdicionais);

    if ($qType == "update") {
        $qCampos = "UPDATE tbl_os_campo_extra
                          SET campos_adicionais = '{$jsonCampos}'
                          WHERE fabrica = {$login_fabrica}
                          AND os = {$os}";
    } else {
        $qCampos = "INSERT INTO tbl_os_campo_extra
                    (os, fabrica, campos_adicionais)
                    VALUES
                    ($os, $login_fabrica, '$jsonCampos')";
    }

    $rCampos = pg_query($con, $qCampos);
    if (pg_affected_rows($rUpdateCampos) > 1 OR strlen(pg_last_error()) > 0) {
        pg_query($con, "ROLLBACK");
        $response = ['exception' => utf8_encode("Falha ao atualizar informações")];
    } else {
        pg_query($con, "COMMIT");
        $response = ['message' => utf8_encode("Informações atualizadas com sucesso")];
    }
    
    echo json_encode($response);
    exit;
}

if ($_POST["ajax_grava_declaracao"]) {

    $data_receb       = explode("/", $_POST["data_recebimento"]);
    $data_recebimento = $data_receb[2]."-".$data_receb[1]."-".$data_receb[0]." ".date('H:i:s');
    $recebido_por     = $_POST["recebido_por"];
    $os               = $_POST["os"];

    pg_query($con,"BEGIN TRANSACTION");

    $sqlUpOsExt = "UPDATE tbl_os_extra SET termino_atendimento='{$data_recebimento}' WHERE os={$os};";
    $resUpOsExt = pg_query($con,$sqlUpOsExt);
    $msg_erro  .= pg_last_error($con);

    $sqlUpOs    = "UPDATE tbl_os SET consumidor_nome_assinatura='{$recebido_por}' WHERE os={$os};";
    $resUpOs    = pg_query($con,$sqlUpOs);
    $msg_erro  .= pg_last_error($con);

    if (strlen($msg_erro) > 0) {
        pg_query($con,"ROLLBACK TRANSACTION");
        echo json_encode(array("ERRO" => TRUE,"MSN"  => "Erro ao gravar: ". pg_last_error($con)."!",));
    } else {
        pg_query($con,"COMMIT TRANSACTION");
        echo json_encode(array("OK"   => TRUE,"MSN"  => "Gravado com Sucesso!",));
    }

    exit;
}

if ($_POST["ajax_anexo_upload"]) {
    $s3 = new AmazonTC("os", $login_fabrica);

    $os      = $_POST['os'];
    $posicao = $_POST['posicao'];
    $ano     = $_POST['ano'];
    $mes     = $_POST['mes'];
    $file    = $_FILES['anexo_upload'];

    $ext = strtolower(preg_replace('/.+\./', '', $file['name']));

    if ($ext == 'jpeg') {
        $ext = 'jpg';
    }

    if (strlen($file['tmp_name']) > 0) {
        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'pdf', 'doc', 'docx'))) {
            $retorno = array('error' => utf8_encode(traduz("arquivo.em.formato.invalido.sao.aceitos.os.seguintes.formatos").': png, jpeg, bmp, pdf, pdf, doc, docx'));
        } else {
            $arquivo_nome = "{$os}_{$posicao}";

    	    $s3->upload($arquivo_nome, $file, $ano, $mes);

    	    if($ext == "pdf"){
    		    $thumb = "imagens/pdf_icone.png";
    	    }else if(in_array($ext, array("doc", "docx"))){
                $thumb = "imagens/docx_icone.png";
            }else{
        		$thumb = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", false, $ano, $mes);
    	    }

            $full  = $s3->getLink("{$arquivo_nome}.{$ext}", false, $ano, $mes);

            if (!strlen($full) && !strlen($thumb)) {
                $retorno = array("error" => utf8_encode(traduz("erro.ao.anexar.arquivo")));
            } else {
                $retorno = array("full" => $full, "thumb" => $thumb, "posicao" => $posicao);
            }
        }
    } else {
        $retorno = array("error" => utf8_encode(traduz("erro.ao.anexar.arquivo")));
    }

    exit(json_encode($retorno));
}

if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    $os                 = filter_input(INPUT_POST,'os');
    $tecnicoTransferir  = filter_input(INPUT_POST,'tecnicoTransferir');

    pg_query($con,"BEGIN TRANSACTION");

    $sql = "
        UPDATE  tbl_os
        SET     tecnico = $tecnicoTransferir
        WHERE   os = $os
    ";
    $res = pg_query($con,$sql);

    $sqlCom = "
        INSERT INTO tbl_comunicado (
            mensagem,
            descricao,
            tipo,
            destinatario,
            obrigatorio_site,
            posto,
            fabrica,
            ativo
        ) VALUES (
            'A OS $os foi transferida pelo técnico MASTER',
            'Transferência de OS',
            'Comunicado',
            $tecnicoTransferir,
            't',
            $login_posto,
            $login_fabrica,
            't'
        )
    ";
    $res = pg_query($con,$sqlCom);

    if (pg_last_error($con)) {
        echo "erro".pg_last_error($con);
        pg_query($con,"ROLLBACK TRANSACTION");
        exit;
    }

    pg_query($con,"COMMIT TRANSACTION");
    echo json_encode(array(
        "ok" => TRUE
    ));
    exit;
}

if ($_POST["excluir_termo"]) {
    $termo     = $_POST["termo"];
    $os_termo  = $_POST["os"];

    if ($termo == 'entrega') {
        $sql_inativa_termo = "  UPDATE tbl_tdocs 
                                SET situacao = 'inativo' 
                                WHERE referencia_id = $os_termo 
                                AND fabrica = $login_fabrica 
                                AND situacao = 'ativo'
                                AND JSON_FIELD('termo_entrega', obs) = 'ok'";
    } else if ($termo == 'retirada') {
        $sql_inativa_termo = "  UPDATE tbl_tdocs 
                                SET situacao = 'inativo' 
                                WHERE referencia_id = $os_termo 
                                AND fabrica = $login_fabrica 
                                AND situacao = 'ativo'
                                AND JSON_FIELD('termo_devolucao', obs) = 'ok'";
    }

    $res_inativa_termo = pg_query($con, $sql_inativa_termo);

    if (pg_last_error()) {
        $retorno = array("error" => "update");    
    } else {
        $retorno = array("sucess" => "ok");
    }
    exit(json_encode($retorno));
}

if ($_POST["excluir_anexo"]) {
    $anexo = $_POST["anexo"];
    $ano   = $_POST["ano"];
    $mes   = $_POST["mes"];

    if (!empty($anexo) && !empty($ano) && !empty($mes)) {
        $amazonTC = new AmazonTC("os", $login_fabrica);
        $amazonTC->deleteObject($anexo, null, $ano, $mes);

        $retorno = array("ok" => true);
    } else {
        $retorno = array("error" => utf8_encode(traduz("anexo.nao.informado")));
    }

    exit(json_encode($retorno));
}


if ($login_fabrica == 134) {
    $tema          = 'Serviço Realizado';
    $temaPlural    = 'Serviços Realizados';
    $temaMPlural   = 'SERVIÇOS REALIZADOS';
    $temaMaiusculo = 'SERVIÇO REALIZADO';
    $tema_titulo   = 'DEFEITO / SERVIÇO';
    $tema_coluna   = 'REALIZADO';
}else{
    $tema          = 'Defeito Constatado';
    $temaPlural    = 'Defeitos Constatados';
    $temaMPlural   = 'DEFEITOS CONSTATADOS';
    $temaMaiusculo = 'DEFEITO CONSTATADO';
    $tema_titulo   = 'DEFEITOS';
    $tema_coluna   = 'DEFEITO';
    if(isset($novaTelaOs)){
        $tema_coluna = 'CONSTATADO';
    }
}

if($login_fabrica == 88 AND $_POST['excluir_anexo_nf']){
    $arquivo = $_POST['arquivo'];

    $excluido = excluirNF($arquivo);
    if($excluido){
        $retorno = array("sucesso" => utf8_encode(traduz("anexo.excluido.com.sucesso")));
    } else {
        $retorno = array("error" => utf8_encode(traduz("erro.ao.anexar.arquivo")));
    }

    exit(json_encode($retorno));
}


if($login_fabrica == 88 AND $_POST["ajax_anexo"]){

    $os         = $_POST["os"];
    $posicao    = $_POST['posicao'];
    $file       = $_FILES["anexo_upload"];

    if($posicao > 0 ){
        $tag = $posicao+1;
    }
    $ext = strtolower(preg_replace("/.+\./", "", $file["name"]));

    if (strlen($file["tmp_name"]) > 0) {
        if (!in_array($ext, array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx"))) {
            $retorno = array("error" => utf8_encode(traduz("arquivo.em.formato.invalido.sao.aceitos.os.seguintes.formatos").": png, jpeg, bmp, pdf, pdf, doc, docx"));
        }else{
            $anexou = anexaNF($os, $file, null, $tag);
        }
    }

    if($anexou === 0){
        $dadosAnexos = temNF($os, 'array', '', false);

        $anexos = array_fill(0, 4, null);
        foreach ($dadosAnexos['arquivos'] as $i =>  $link) {
            $name = pathinfo($link, PATHINFO_FILENAME);
            $pos = preg_replace("/\w+\-(\d)$/", "$1", $name);
            $pos = strlen($pos) == 1 ? $pos-1 : 0;
            $anexos[$pos]['full'] = $link;
            $anexos[$pos]['thumb'] = $dadosAnexos['thumbs'][$i];
        }

        $name_full  = $anexos[$posicao]['full'];
        $name_thumb = $anexos[$posicao]['thumb'];


        $retorno = array("posicao"=> $posicao, "full" => $name_full, "thumb" => $name_thumb);
    } else {
        $retorno = array("error" => utf8_encode(traduz("erro.ao.anexar.arquivo")));
    }
    exit(json_encode($retorno));
}

if ($fabrica_anexa_os_press && $_POST["ajax_anexo_peca_upload"]) {
    $s3_item = new AmazonTC("os_item", $login_fabrica);

    $os         = $_POST["os"];
    $os_produto = $_POST["os_produto"];
    $os_item    = $_POST["os_item"];
    $posicao    = $_POST['posicao'];
    $file       = $_FILES["anexo_peca_upload"];

    $ext = strtolower(preg_replace("/.+\./", "", $file["name"]));

    if ($ext == "jpeg") {
        $ext = "jpg";
    }

    if (strlen($file["tmp_name"]) > 0) {
        if (!in_array($ext, array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx"))) {
            $retorno = array("error" => utf8_encode(traduz("arquivo.em.formato.invalido.sao.aceitos.os.seguintes.formatos").": png, jpeg, bmp, pdf, pdf, doc, docx"));
        } else {
            $arquivo_nome = "{$os}_{$os_produto}_{$os_item}";
            if ((int)$posicao > 0)
                $arquivo_nome .= "_$posicao";

            $s3_item->upload($arquivo_nome, $file);

            if($ext == "pdf"){
                $thumb = "imagens/pdf_icone.png";
            }else if(in_array($ext, array("doc", "docx"))){
                $thumb = "imagens/docx_icone.png";
            }else{
                $thumb = $s3_item->getLink("thumb_{$arquivo_nome}.{$ext}");
            }

            $full  = $s3_item->getLink("{$arquivo_nome}.{$ext}");

            if (!strlen($full) && !strlen($thumb)) {
                $retorno = array("error" => utf8_encode(traduz("erro.ao.anexar.arquivo")));
            } else {
                $sql = "SELECT tbl_peca.referencia FROM tbl_os_item INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca WHERE tbl_os_item.os_item = {$os_item} AND tbl_peca.fabrica = {$login_fabrica}";
                $res = pg_query($con, $sql);

                $referencia = pg_fetch_result($res, 0, "referencia");

                $retorno = array("full" => $full, "thumb" => $thumb, "os_produto" => $os_produto, "os_item" => $os_item, "referencia" => utf8_encode($referencia));
                if ($posicao)
                    $retorno['posicao'] = $posicao;
            }
        }
    } else {
        $retorno = array("error" => utf8_encode(traduz("erro.ao.anexar.arquivo")));
    }

    exit(json_encode($retorno));
}

// HD 153966
$login_fabrica = (strlen($_GET['lu_fabrica']) > 0) ? $_GET['lu_fabrica'] : $login_fabrica;

if (isset($_GET['lu_fabrica'])) {

    $fabrica = $_GET['lu_fabrica'];

    $sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica,
            tbl_posto_fabrica.posto,
            tbl_posto_fabrica.fabrica
        FROM tbl_posto_fabrica
        WHERE fabrica = $fabrica
        AND posto     = $cook_posto";

    $res = pg_query($con, $sql);

    if (pg_numrows($res) > 0) {

        remove_cookie($cookie_login, "cook_posto_fabrica");
        remove_cookie($cookie_login, "cook_posto");
        remove_cookie($cookie_login, "cook_fabrica");
        remove_cookie($cookie_login, "cook_login_posto");
        remove_cookie($cookie_login, "cook_login_fabrica");
        remove_cookie($cookie_login, "cook_login_pede_peca_garantia");
        remove_cookie($cookie_login, "cook_login_tipo_posto");


        add_cookie($cookie_login, "cook_posto_fabrica", pg_fetch_result($res, 0, 'posto_fabrica'));
        add_cookie($cookie_login, "cook_posto", pg_fetch_result($res, 0, 'posto'));
        add_cookie($cookie_login, "cook_fabrica", pg_fetch_result($res, 0, 'fabrica'));

        set_cookie_login($token_cookie, $cookie_login);

    }

}

/**
 * Rotina para a exclusão de anexo da OS
 **/
if ($_POST['ajax'] == 'excluir_nf') {
    $img_nf = anti_injection($_POST['excluir_nf']);
    //$img_nf = basename($img_nf);

    $excluiu = (excluirNF($img_nf));
    $nome_anexo = preg_replace("/.*\/([rexs]_)?(\d+)([_-]\d)?\..*/", "$1$2", $img_nf);

    if ($excluiu)  $ret = "ok|" . temNF($nome_anexo, 'linkEx') . "|$img_nf|$nome_anexo";
    if (!$excluiu) $ret = 'ko|'.traduz("nao.foi.possivel.excluir.o.arquivo.solicitado");

    exit($ret);
}// FIM Excluir imagem

$os = (isset($_GET['os'])) ? $_GET['os'] : $_POST['os'];
if  (empty($os)){
	if(count($_GET) ==0 and count($_POST) ==0) {
	    header('Location: os_consulta_lite.php');
		exit;
	}
}

if(empty($xlogin_posto) and empty($distribuidor)) {
    if(is_numeric($os)){

        if ($login_fabrica == 183 AND $login_tipo_posto_codigo == "Rep"){
            $sql_os = "
                SELECT tbl_os.os FROM tbl_os 
                JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                JOIN tbl_representante ON tbl_representante.representante = tbl_os_extra.representante AND tbl_representante.cnpj = '{$login_cnpj}'
                WHERE tbl_os.os = {$os}
                AND tbl_representante.fabrica = {$login_fabrica}";
        }else{
            $sql_os = "SELECT os FROM tbl_os WHERE os = $os AND posto = $login_posto AND {$cond_pesquisa_fabrica}";
        }
        $res_os = pg_query($con, $sql_os);
    }
	if (pg_num_rows($res_os) != 1) $msg_erro = 'OS não localizada!';
}
/**
 *
 * HD 739078 - latinatec: os em auditoria (aberta a mais de 60 dias) não pode consultar
 *
 */
if ($login_fabrica == 15) {
    $os_bloq_tipo = '120,201, 122, 123, 126';
    $sqlStOs = "SELECT status_os FROM tbl_os_status WHERE status_os IN ($os_bloq_tipo) AND os = $os AND fabrica_status = $login_fabrica ORDER BY DATA DESC LIMIT 1";
    $resStOs = pg_query($con, $sqlStOs);

    if (pg_num_rows($resStOs) > 0) {
        $status_atual = pg_result($resStOs, 0, 'status_os');
        if ($status_atual == 120) {
            echo '<div style="margin-top: 20px; color: #FF0000; font-weight: bold; text-align: center;">';
                echo 'OS fora do prazo para fechamento.<br/><br/>';
                echo '<input type="button" value=" Fechar " onClick="window.close()" />';
            echo '</div>';
            exit;
        }
    }
}

if ($login_fabrica == 7) {
    header ("Location: os_press_filizola.php?os=$os");
    exit;
}

//forçar o login do posto para distrib (consulta do embarque)
if (strlen($_GET['login_posto']) > 0) {
    $gambiara     = 't';
    $xlogin_posto = $_GET['login_posto'];
}

if(strlen($_GET['distribuidor']) > 0) {
	$distribuidor = $_GET['distribuidor'];
}
/*  HD 135436(+Mondial) HD 193563 (+Dynacom)
    Para adicionar ou excluir uma fábrica ou posto, alterar só essa condição aqui,
    na os_consulta_lite, os_press, admin/os_press e na admin/os_fechamento, sempre nesta função
	#HD 311411 - Adicionado Fábrica 6 (TecToy)
*/
function usaDataConserto($posto, $fabrica) {
    if ($posto == 4311 or ((!in_array($login_fabrica, array(11,172))) and $posto == 6359) or
        in_array($fabrica, array(1,2,3,5,6,7,11,14,15,20,30,35,43,45,50)) or $fabrica > 50) {
        return true;
    }
    return false;
}

//30/08/2010 MLG HD 283928  Fábricas que mostram o status de Intervenção e o histórico. Adicionar 43 (Nova Comp.)
$historico_intervencao = (in_array($login_fabrica, array(1,2,3,6,11,14,24,25,30,35,40,43,45,50,72,74)) or $login_fabrica > 84);

//28/08/2010 MLG HD 237471  Fábricas que mostra o valor da peça, baseado na tbl_os_item
$mostrar_valor_pecas = in_array($login_fabrica, array(1));

$btn_acao = $_POST['btn_acao'];

if ($btn_acao == 670 || $btn_acao == 671 || $btn_acao == 733) {

    $msg_erro = "";

    $res = pg_query ($con,"BEGIN TRANSACTION");
    $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao)
            VALUES ($os,64,current_timestamp,'Posto retirou intervenção da fábrica.')";

    $res = pg_query($con,$sql);
    $msg_erro = pg_errormessage($con);

    if (strlen($msg_erro) == 0) {

        $sql = "SELECT os_produto FROM tbl_os_produto WHERE os = $os";

        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        if (pg_num_rows($res) > 0) {

            for ($i = 0; $i < pg_num_rows($res); $i++) {

                $os_produto = pg_fetch_result($res,$i,os_produto);

                $sql1 = "UPDATE tbl_os_item
                            SET servico_realizado = $btn_acao
                            WHERE os_produto=$os_produto
                            AND servico_realizado = 673";

                $res1 = pg_query($con,$sql1);
                $msg_erro .= pg_errormessage($con);

            }

        }

    }

    if (strlen($msg_erro) > 0) {

        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
        echo "<script language='JavaScript'>alert('".traduz("operacao.nao.realizada").".\n".traduz("por.favor.entre.em.contato.com.a.telecontrol")."\n\n');</script>";

    } else {

        $res = pg_query ($con,"COMMIT TRANSACTION");
        echo "<script language='JavaScript'>alert('".traduz("operacao.realizada.com.sucesso")."');</script>";

    }

}

if(in_array($login_fabrica, [167,177,191,203]) && isset($enviar_orcamento)){
    $os          = $_POST['os'];
    $dados_email = $_POST['dados_email'];
    $dados_email = json_decode($dados_email,true);

    if($login_fabrica == 191){
        include_once "os_cadastro_unico/fabricas/{$login_fabrica}/regras.php";

        $envio = envia_email_consumidor();
        if($envio){
            echo "ok";
        }
        exit;
    }

    $mensagem = traduz("prezado.cliente")."<br/>";
    $mensagem .= traduz("informamos.que.o.equipamento.da")."<br/>";
    $mensagem .= traduz("ordem.de.servico").":".$dados_email["geral"]["os"]."<br/>";

    if (in_array($login_fabrica, [167,203])) {
        $mensagem .= traduz("modelo").":".$dados_email["geral"]["produto_referencia"]." - ".utf8_decode($dados_email["geral"]["produto_descricao"])."<br/>";
    } else {
        $mensagem .= traduz("Produto").":".$dados_email["geral"]["produto_referencia"]." - ".utf8_decode($dados_email["geral"]["produto_descricao"])."<br/> está com o orçamento pronto. Segue o valor e a prescrição do que deve ser feito no equipamento:<br />";
    }

    if (in_array($login_fabrica, [167,203])) {
        $mensagem .= "Série:".$dados_email["geral"]["numero_serie"]."<br/>";

        $mensagem .= traduz("esta.com.o.orcamento.pronto.segue.o.valor.e.a.prescricao.do.que.deve.ser.feito.no.equipamento  
")."<br/> ".traduz("peco.por.gentileza.que.verifique.a.aprovacao.para.darmos.continuidade.do.processo   
")."<br/>".traduz("com.aprovacao.ou.nao.do.orcamento.o.valor.de.r.12000.sera.cobrado.referente.a.mao.de.obra    
");

    }

    $mensagem .= traduz("servicos")." <br/><br/>";

    $count = 0;
    foreach ($dados_email as $key) {

        if(empty($dados_email[$count]["descricao_peca"])){
            continue;
        }

        $mensagem .= traduz("peca").": ".utf8_decode($dados_email[$count]["descricao_peca"])."<br>";
        $mensagem .= traduz("quantidade").": ".$dados_email[$count]["qtde_pecas"]."<br>";
        $mensagem .= traduz("valor.unitario").": ".$dados_email[$count]["preco_unitario"]."<br><br>";
        $count++;
    }

    if ($login_fabrica == 177){
        $mensagem .= traduz("valor.mao.de.obra").": ".$dados_email["geral"]["valor_adicional"]."<br>";
    }else{
        $mensagem .= traduz("valor.adicional").": ".$dados_email["geral"]["valor_adicional"]."<br>";
    }
    $mensagem .= traduz("valor.total.das.pecas").": ".$dados_email["geral"]["valor_total_pecas"]."<br>";
    $mensagem .= traduz("valor.total.geral").": ".$dados_email["geral"]["total_geral"]."<br>";
    $email = $dados_email["geral"]["email_consumidor"];
    $assunto = traduz("orcamento")." - ".$dados_email["geral"]["posto_nome"];

    $headers  = "MIME-Version: 1.0 \r\n";
    $headers .= "Content-type: text/html \r\n";
    $headers .= "From: $from_fabrica_descricao <no_reply@telecontrol.com.br> \r\n";

    if(!mail($consumidor_nome .'<'.$email.'>', $assunto, $mensagem, $headers)){
        $msg_erro = traduz("erro.ao.enviar.email");
    }else{
        $msg_erro = "ok";
    }
    echo $msg_erro;
    exit;
}


#if ($login_fabrica == 11 AND $login_posto == 6359) {
#    header ("Location: os_press_20080515.php?os=$os");
#    exit;
#}
// HD 61323
if (isset($_POST['gravarDataconserto']) AND isset($_POST['os'])) {

    $gravarDataconserto = trim($_POST['gravarDataconserto']);
    $os                 = trim($_POST['os']);
    if (strlen($os) > 0) {
        if (strlen($gravarDataconserto ) > 0) {
            $data = $gravarDataconserto.":00 ";
            $aux_ano  = substr($data,6,4);
            $aux_mes  = substr($data,3,2);
            $aux_dia  = substr($data,0,2);
            $aux_hora = substr($data,11,5).":00";
            $gravarDataconserto ="'". $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora."'";

        } else {
            $gravarDataconserto ='null';
        }

        $erro = "";

        if ($gravarDataconserto != 'null') {

            $sql = "SELECT $gravarDataconserto > CURRENT_TIMESTAMP ";
            $res = pg_query($con,$sql);

            if (pg_fetch_result($res,0,0) == 't'){
                $erro = traduz("data.de.conserto.nao.pode.ser.superior.a.data.atual", $con, $cook_idioma);
            }

        }

        if ($gravarDataconserto != 'null') {

            $sql = "SELECT $gravarDataconserto < tbl_os.data_abertura FROM tbl_os where os=$os";
            $res = pg_query($con, $sql);

            if (pg_fetch_result($res,0,0) == 't'){
                $erro = traduz("data.de.conserto.nao.pode.ser.anterior.a.data.de.abertura", $con, $cook_idioma);
            }

        }
        if (strlen($erro) == 0) {
            $sql = "UPDATE tbl_os SET data_conserto = $gravarDataconserto WHERE os = $os AND fabrica = $login_fabrica AND posto = $login_posto";
            $res = pg_query($con,$sql);
            $retorno = "ok";
        } else {
            $retorno = trim($erro);
        }

        echo trim($retorno);
    }

    exit;

}

$fechar_os = $_GET['fechar'];

if (strlen ($fechar_os) > 0) {

    $msg_erro = "";
    $res = pg_query ($con,"BEGIN TRANSACTION");
    $sql = "SELECT status_os
            FROM tbl_os_status
            WHERE os = $fechar_os
            AND status_os IN (62,64,65,72,73,87,88,116,117,128)
            ORDER BY data DESC
            LIMIT 1";

    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {

        $status_os = trim(pg_fetch_result($res,0,status_os));

        if ($status_os == "72" || $status_os == "62" || $status_os == "87" || $status_os == "116") {
            $msg_erro .= traduz("os.com.intervencao,.nao.pode.ser.fechada.",$con,$cook_idioma);
        }

    }

    $sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $fechar_os AND fabrica = $login_fabrica";
    $res = pg_query ($con,$sql);
    $msg_erro .= pg_errormessage($con) ;

    if (strlen ($msg_erro) == 0) {
        $sql = "SELECT fn_finaliza_os($fechar_os, $login_fabrica)";
        $res = pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con) ;
    }


    if (strlen ($msg_erro) == 0) {

        $sql = "SELECT to_char(data_fechamento,'DD/MM/YYYY') as data_fechamento,
                        to_char(finalizada,'DD/MM/YYYY') as finalizada
                FROM   tbl_os
                WHERE os = $fechar_os";

        $res = pg_query($con,$sql);
        $data_fechamento = pg_fetch_result($res,0,data_fechamento);
        $finalizada      = pg_fetch_result($res,0,finalizada);

        $res = pg_query ($con,"COMMIT TRANSACTION");

        //HD-2843341
            $sql_data = "SELECT SUM(data_fechamento - data_abertura)as final FROM tbl_os WHERE os=$fechar_os";
            $resD = pg_query ($con,$sql_data);
            if (pg_num_rows ($resD) > 0) {
                $total_de_dias_do_conserto = pg_fetch_result ($resD,0,'final');
            }
            if($total_de_dias_do_conserto==0) {
                $dias_conserto = traduz("no.mesmo.dia",$con,$cook_idioma) ;
            }else{
                $dias_conserto = $total_de_dias_do_conserto;
            }
            if($total_de_dias_do_conserto==1) {
                $dias_conserto = $total_de_dias_do_conserto." ".traduz("dia",$con,$cook_idioma) ;
            }
            if($total_de_dias_do_conserto>1) {
                $dias_conserto = $total_de_dias_do_conserto." ".traduz("dias",$con,$cook_idioma);
            }
        //FIM HD-2843341
        echo "ok;$fechar_os;$data_fechamento;$finalizada;$dias_conserto";

    } else {

        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
        echo "erro;$sql ==== $msg_erro ";

    }

    flush();
    exit;

}

//--=== VALIDA REINCIDENCIA DA OS ==================================================
$sql = "SELECT tbl_extrato.extrato FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $os AND tbl_extrato.aprovado IS NOT NULL ; ";
$res2 = pg_query ($con,$sql);
$reic_extrato = @pg_fetch_result($res2,0,0);

//  16/11/2009 HD 171349 - Waldir - também comentada linha 232
// if(strlen($reic_extrato) == 0){
//  //echo "Passou aqui.";
//  if($login_fabrica <> 56){
//      $sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
//  }
//  $res1 = pg_query ($con,$sql);

if(strlen($_GET['os'])>0){
    $os=$_GET['os'];

    $sql = "SELECT  motivo_atraso ,
                    tbl_os.observacao    ,
                    os_reincidente,
                    tbl_os_status.status_os,
                    obs_reincidencia
            FROM tbl_os
            INNER JOIN tbl_os_status on tbl_os.os = tbl_os_status.os
            WHERE tbl_os.os = $os
            AND fabrica = $login_fabrica
            and finalizada is null";
/*takashi 22/10/07 colocou and finalizada is null pois OS ja fechada e paga estava entrando no motivo do atraso, acho que nao há necessidade, se tiver necessidade comente as alterações.*/
    $res = pg_query($con,$sql);

    if(pg_num_rows($res)>0){
        $motivo_atraso    = pg_fetch_result($res,0,motivo_atraso);
        $observacao       = pg_fetch_result($res,0,observacao);
        $os_reincidente   = pg_fetch_result($res,0,os_reincidente);
        $status_os   = pg_fetch_result($res,0,status_os);
        $obs_reincidencia = pg_fetch_result($res,0,obs_reincidencia);

        if($login_fabrica == 2){
            if($os_reincidente=='t' AND (strlen($obs_reincidencia) == 0))
                header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
        } else if($login_fabrica != 3 or ($login_fabrica == 3 and $status_os != 67)) {
            if($os_reincidente=='t' AND strlen($obs_reincidencia )==0 )
                header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
        }
    }
}
// }

#HD 44202 - intervenção OS aberta
$os= trim($_GET['os']);

if ($login_fabrica == 3  AND strlen(trim($_POST['btn_acao']))>0 AND $_POST['btn_acao']=='gravar_justificativa') {
    $txt_justificativa_os_aberta = $_POST['txt_justificativa_os_aberta'];

    $res = @pg_query($con,"BEGIN TRANSACTION");

    $status_os = "";
    $sql = "SELECT status_os
            FROM  tbl_os_status
            WHERE os=$os
            AND status_os IN (120, 122, 123, 126, 140, 141, 142, 143)
            ORDER BY data DESC LIMIT 1";
    $res_intervencao = pg_query($con, $sql);
    $msg_erro        = pg_errormessage($con);

    if (pg_num_rows ($res_intervencao) > 0 ){
        $status_os = pg_fetch_result($res_intervencao,0,status_os);

        if(strlen($txt_justificativa_os_aberta )== 0){
            $msg_erro .= traduz("e.necessario.preencher.a.justificativa.para.os.aberta");
        } else {
            if ($status_os=="120") {
                $sql = "INSERT INTO tbl_os_status
                        (os,status_os,data,observacao)
                        VALUES ($os,122,current_timestamp,'$txt_justificativa_os_aberta')";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);
            } else if ($status_os=="140") {
                $sql = "INSERT INTO tbl_os_status
                        (os,status_os,data,observacao)
                        VALUES ($os,141,current_timestamp,'$txt_justificativa_os_aberta')";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }
        }
    }

    if (strlen($msg_erro)>0){
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    }else {
        #$res = @pg_query ($con,"ROLLBACK TRANSACTION");
        $res = @pg_query ($con,"COMMIT TRANSACTION");
    }
}

#HD 12657 - Posto causa a intervenção
$inter = trim($_GET['inter']);
if($login_fabrica==2 AND $inter=='1'){

    $res = @pg_query($con,"BEGIN TRANSACTION");

    $status_os = "";
    $sql = "SELECT status_os
            FROM  tbl_os_status
            WHERE os=$os
            AND status_os IN (62,64,65)
            ORDER BY data DESC LIMIT 1";
    $res_intervencao = pg_query($con, $sql);
    $msg_erro        = pg_errormessage($con);
    if (pg_num_rows ($res_intervencao) > 0){
        $status_os = pg_fetch_result($res_intervencao,0,status_os);
    }
    if (pg_num_rows ($res_intervencao) == 0 OR $status_os!="62"){
        $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao)
            VALUES ($os,62,current_timestamp,'Auto intervenção.')";
        $res = @pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);
    }

    $status_os = "";
    $sql = "SELECT status_os
            FROM  tbl_os_status
            WHERE os=$os
            AND status_os IN (62,64,65)
            ORDER BY data DESC LIMIT 1";
    $res_intervencao = pg_query($con, $sql);
    if (pg_num_rows ($res_intervencao) > 0){
        $status_os = pg_fetch_result($res_intervencao,0,status_os);
        if ($status_os=="62"){
            $sql = "INSERT INTO tbl_os_status
                    (os,status_os,data,observacao)
                    VALUES ($os,65,current_timestamp,'Reparo do produto deve ser feito pela fábrica.')";
            $res = pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);

            $sql = "INSERT INTO tbl_os_retorno (os) VALUES ($os)";
            $res = pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);
        }
    }

    if (strlen($msg_erro)>0){
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    }else {
        #$res = @pg_query ($con,"ROLLBACK TRANSACTION");
        $res = @pg_query ($con,"COMMIT TRANSACTION");
    }
}

if (in_array($login_fabrica,array(2,3,6,11,14,172)) && !empty($_POST['btn_acao']) && $_POST['btn_acao'] == 'gravar' ){
    try{
        $nota_fiscal_envio_p = trim($_POST['txt_nota_fiscal']);
        $numero_rastreio_p   = trim($_POST['txt_rastreio']);
        $data_envio_p        = trim($_POST['txt_data_envio']);

        if(empty($nota_fiscal_envio_p)){
            throw new Exception(traduz("informacoes.do.envio.a.fabrica.incorretos",$con,$cook_idioma));
        }
        if(empty($numero_rastreio_p) && !in_array($login_fabrica,array(6,14))){
            throw new Exception(traduz("informacoes.do.envio.a.fabrica.incorretos",$con,$cook_idioma));
        }

        $dateTime = DateTime::createFromFormat('d/m/Y',$data_envio_p);
        if(!$dateTime)
            $dateTime = DateTime::createFromFormat('d/m/y',$data_envio_p);
        if(!$dateTime){
            throw new Exception(traduz("data.invalida",$con,$cook_idioma));
        }

        $params = array($nota_fiscal_envio_p,$dateTime->format(DateTime::ISO8601),$numero_rastreio_p,$os);
        $sql = 'UPDATE tbl_os_retorno
                SET nota_fiscal_envio = $1,
                    data_nf_envio     = $2,
                    numero_rastreamento_envio = $3
                WHERE os=$4';
        $result = pg_query_params($con,$sql,$params);
        if(!$result){
            $msg = traduz("erro.ao.gravar.verifique.as.informacoes.digitadas",$con,$cook_idioma);
            $msg.= '<br />('.pg_last_error($con).')';
            throw new Exception($msg);
        }
    }
    catch(Exception $ex){
        $msg_erro = $ex->getMessage();
    }
}

if (( in_array($login_fabrica, array(1,2,3,6,11,14,172)) ) AND $_POST['btn_acao']=='confirmar'){
    $os_retorno = trim($_GET['chegada']);
    if (strlen($os_retorno)==0)
        $msg_erro .= traduz("os.invalida",$con,$cook_idioma).": $os_retorno";

    $data_chegada_retorno = trim($_POST['txt_data_chegada_posto']);
    if (strlen($data_chegada_retorno)!=10){
        $msg_erro.= strtoupper(traduz("data.invalida",$con,$cook_idioma));
    }
    else {
        $data_chegada_retorno = mostra_data($data_chegada_retorno);
        if ($data_chegada_retorno==false) $msg_erro.= traduz("data.no.formato.invalido",$con,$cook_idioma);
    }

    $res = @pg_query($con,"BEGIN TRANSACTION");

    if (strlen($msg_erro)==0){
        $sql =  "UPDATE tbl_os_retorno
                SET retorno_chegada='$data_chegada_retorno'
                WHERE os=$os";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
    }
    if (strlen($msg_erro)==0){
        $sql =  "UPDATE tbl_os_status
                SET status_os=64
                WHERE os=$os";
        $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,64,current_timestamp,'Produto com reparo realizado pela fábrica e recebido pelo posto')";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
    }
    if (strlen($msg_erro)>0){
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    }
    else {
        $res = @pg_query ($con,"COMMIT TRANSACTION");
        header("Location: $PHP_SELF?os=$os&msg_erro=$msg_erro");
    }
}
////////////// FIM ////  ADICIONADO POR FABIO 10/01/2007

$sql = "SELECT  tbl_fabrica.os_item_subconjunto
        FROM    tbl_fabrica
        WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_query ($con,$sql);

if (pg_num_rows($res) > 0) {
    $os_item_subconjunto = pg_fetch_result ($res,0,os_item_subconjunto);
    if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}

if (in_array($login_fabrica, array(19,178))) {//hd 19833 3/6/2008
    $sql_revendas = "tbl_revenda.cnpj AS revenda_cnpj,
                     tbl_revenda.nome AS revenda_nome,
                     tbl_revenda.fone AS revenda_fone,";

    $join_revenda = "LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda";
} else {//lpad 25/8/2008 HD 34515

    $sql_revendas = "tbl_os.revenda_nome,
                    LPAD(tbl_os.revenda_cnpj, 14, '0') AS revenda_cnpj,
                    tbl_os.revenda_fone,";

    if (in_array($login_fabrica, [186])) {
        $sql_revendas = "tbl_os.revenda_nome,
                         tbl_os.revenda_cnpj AS revenda_cnpj,
                         tbl_os.revenda_fone,";
    }

}

#------------ Le OS da Base de dados ------------#

//$os = empty($os) ? $_GET['os'] : $os;
$os = ($os) ? : (string)(int)$_GET['os'];
if ($json_info_posto != null) {
    $info_tp = json_decode($json_info_posto, true);
    $posto_interno = (bool)count(array_filter(array_column($info_tp['tipo_posto'],'posto_interno')));
} else {
    $posto_interno = false;
}

if (isset($novaTelaOs)) {
    if (isset($tipo_posto_multiplo)) {
        $sql = "
            SELECT tbl_tipo_posto.tipo_posto
            FROM tbl_posto_tipo_posto
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto
            WHERE tbl_posto_tipo_posto.fabrica = {$login_fabrica}
            AND tbl_posto_tipo_posto.posto = $login_posto
            AND tbl_tipo_posto.posto_interno IS TRUE
        ";
    } else {
        $sql = "
            SELECT tbl_tipo_posto.tipo_posto
            FROM tbl_posto_fabrica
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND tbl_posto_fabrica.posto = $login_posto
            AND tbl_tipo_posto.posto_interno IS TRUE
        ";
    }

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $posto_interno = true;
    } else {
        $posto_interno = false;
    }
}

if (strlen ($os) > 0) {

    // HD31887
    if ($login_posto != 4311) {
        $cond = "AND tbl_posto_fabrica.fabrica      = $login_fabrica";
    }

    $sql_extra  = '';
    $join_extra = '';

    if ($login_fabrica == 85) {
        $sql_extra  .= "tbl_hd_chamado_extra.array_campos_adicionais,\n";
        $join_extra .= "LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os\n";
    }

    if($login_fabrica == 153){
        $sql_extra .= "tbl_os.codigo_fabricacao,\n";
        $complemento_sql_positron = " tbl_os.codigo_fabricacao, ";
    }

    if (in_array($login_fabrica, array(59,87,156,158,162,165,184,198,200))) {
        $joinTblTec = "LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_os.tecnico AND tbl_tecnico.fabrica = {$login_fabrica}";
        $col_tec = 'tbl_tecnico.nome AS tecnico_nome,';
    }else if ($usaPostoTecnico){
        $joinTblTec = "LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_os.tecnico AND tbl_tecnico.posto = {$login_posto}";
        $col_tec = 'tbl_tecnico.nome AS tecnico_nome,';
    } else {
        $col_tec = 'tbl_os.tecnico_nome,';
    }

    $os = str_replace(explode(' ', '(  ) .'), "", $os);
    $sql_extra = $login_fabrica == 85 ? "\ntbl_hd_chamado_extra.array_campos_adicionais,\n" : "";
    $join_extra = $login_fabrica == 85 ? "\nLEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os\n" : "";


    if($login_fabrica == 30){
        $left_join_os_troca = "LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os";
        $campos_tbl_os_troca = "tbl_os_troca.admin_autoriza, ";
    }

    if($login_fabrica == 50){
        $os_bloqueada = " tbl_os_campo_extra.os_bloqueada, ";
    }

    if( in_array($login_fabrica, array(11,172)) ){
        $cond_os_cancelada = " AND tbl_os.cancelada IS NOT TRUE ";
    }
    
    if($login_fabrica == 178){
        $left_join_marca_troca = " LEFT JOIN tbl_marca mt ON tbl_os.marca = mt.marca ";
        $campos_roca = " mt.nome AS marca_troca, tbl_os_extra.faturamento_cliente_revenda, ";
    }

    if($login_fabrica == 157){
        $left_join_auditoria_reprovada  = " LEFT JOIN tbl_auditoria_os ON tbl_auditoria_os.os = tbl_os.os ";
        $campo_auditoria_reprovada      = " tbl_auditoria_os.reprovada, ";
    }    

    $sql = "SELECT  tbl_os.sua_os,
                    tbl_os.sua_os_offline,
                    tbl_admin.login                              AS admin,
                    troca_admin.login                            AS troca_admin,
                    to_char(tbl_os.data_digitacao,'DD/MM/YYYY HH24:MI:SS')  AS data_digitacao,
                    to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura,
                    to_char(tbl_os.data_hora_abertura,'DD/MM/YYYY HH24:MI:SS')  AS data_hora_abertura,
                    to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                    to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada,
                    to_char(tbl_os.data_nf_saida,'DD/MM/YYYY')   AS data_nf_saida,
                    tbl_os.tipo_atendimento,
                    tbl_os.tecnico,
                    {$campo_auditoria_reprovada}
                    $col_tec
                    $sql_extra
                    tbl_tipo_atendimento.descricao               AS nome_atendimento,
                    tbl_tipo_atendimento.codigo                  AS codigo_atendimento,
                    tbl_os.consumidor_nome,
                    tbl_os.consumidor_fone,
                    tbl_os.consumidor_celular,
                    tbl_os.consumidor_fone_comercial,
                    tbl_os.consumidor_fone_recado,
                    tbl_os.consumidor_endereco,
                    tbl_os.consumidor_numero,
                    $complemento_sql_positron
                    tbl_os.consumidor_complemento,
                    tbl_os.consumidor_bairro,
                    tbl_os.consumidor_cep,
                    tbl_os.consumidor_cidade,
                    tbl_os.consumidor_estado,
                    tbl_os.consumidor_cpf,
                    tbl_os.consumidor_email,
                    $sql_revendas
                    $campos_tbl_os_troca
                    tbl_os.nota_fiscal,
                    tbl_os.nota_fiscal_saida,
                    tbl_os.consumidor_nome_assinatura AS contato_consumidor,
                    tbl_os.condicao AS contador,
                    tbl_os.cliente,
                    tbl_os.revenda,
                    tbl_os.defeito_constatado_grupo AS id_defeito_constatado_grupo,
                    tbl_os.rg_produto,
                    tbl_os.defeito_reclamado_descricao           AS defeito_reclamado_descricao_os,
                    tbl_marca.marca,
                    tbl_marca.nome as marca_nome,
                    tbl_os.marca as marca_os,
                    tbl_os.qtde_produtos as qtde,
                    tbl_os.tipo_os,
                    tbl_tipo_os.descricao AS tipo_os_descricao,
                    TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY')        AS data_nf,
                    tbl_defeito_reclamado.defeito_reclamado      AS defeito_reclamado,
                    tbl_defeito_reclamado.descricao              AS defeito_reclamado_descricao,
                    tbl_defeito_constatado.defeito_constatado    AS defeito_constatado,
                    tbl_defeito_constatado.descricao             AS defeito_constatado_descricao,
                    tbl_defeito_constatado.codigo                AS defeito_constatado_codigo,
                    tbl_causa_defeito.causa_defeito              AS causa_defeito,
                    tbl_causa_defeito.descricao                  AS causa_defeito_descricao,
                    tbl_causa_defeito.codigo                     AS causa_defeito_codigo,
                    tbl_defeito_constatado_grupo.descricao       AS defeito_constatado_grupo,
                    tbl_motivo_reincidencia.descricao            AS motivo_reincidencia_desc,
                    tbl_os.obs_reincidencia,
                    tbl_os.aparencia_produto,
                    tbl_os.acessorios,
                    tbl_os.consumidor_revenda,
                    tbl_os.prateleira_box,
                    tbl_os.hd_chamado,
                    CASE WHEN tbl_os.fabrica IN(52) AND tbl_os.hd_chamado IS NOT NULL
                         THEN tbl_os.obs || '\nMotivo: ' || tbl_os.observacao
                         WHEN tbl_os.fabrica IN(169,170) AND tbl_os.hd_chamado IS NOT NULL
                         THEN tbl_os.obs || '\n\n<b>Observações do Callcenter: </b><br/>' || tbl_os.observacao
                         ELSE tbl_os.obs
                    END AS obs,
                    tbl_os.qtde_diaria,
                    tbl_os.observacao,
                    tbl_os.excluida,
                    tbl_produto.produto,
                    tbl_produto.referencia,
                    tbl_produto.referencia_fabrica               AS modelo,
                    tbl_produto.descricao,
                    tbl_produto.voltagem,
                    tbl_produto.valor_troca,
                    tbl_produto.troca_obrigatoria,
                    tbl_produto.parametros_adicionais AS produto_parametros_adicionais ,
                    tbl_os.qtde_produtos,
                    tbl_os.serie,
                    tbl_os.codigo_fabricacao,
                    tbl_posto_fabrica.codigo_posto               AS posto_codigo,
                    tbl_posto_fabrica.reembolso_peca_estoque,
                    tbl_posto.nome                               AS posto_nome,
                    tbl_os.ressarcimento,
                    tbl_os.certificado_garantia,
                    tbl_os_extra.os_reincidente,
                    tbl_os_extra.recolhimento,
                    tbl_os_extra.natureza_servico,
                    tbl_os_extra.orientacao_sac,
                    tbl_os_extra.reoperacao_gas,
                    tbl_os_extra.obs_nf,
                    tbl_os_extra.recomendacoes,
                    tbl_os.solucao_os,
                    tbl_os.posto,
                    tbl_os.type,
                    tbl_os.promotor_treinamento,
                    tbl_os.fisica_juridica,
                    tbl_os.troca_garantia,
                    tbl_os.troca_garantia_admin,
                    tbl_os.justificativa_adicionais,
                    tbl_os.contrato,
                    tbl_os.troca_faturada,
                    tbl_os_extra.representante,
                    tbl_os_extra.tipo_troca,
                    tbl_os_extra.serie_justificativa,
                    tbl_os_extra.hora_tecnica,
                    tbl_os_extra.qtde_horas,
                    tbl_os_extra.obs_adicionais,
                    tbl_os_extra.pac AS codigo_rastreio,
                    tbl_os_extra.data_fabricacao,
                    tbl_os_extra.pac,
                    tbl_os.capacidade,
                    tbl_os.os_posto,
                    tbl_os.serie_reoperado,
                    tbl_extrato.extrato,
                    TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY HH24:MI')             AS data_ressarcimento,
                    TO_CHAR(tbl_extrato_pagamento.data_pagamento, 'DD/MM/YYYY') AS data_previsao,
                    TO_CHAR(tbl_extrato_pagamento.data_pagamento, 'DD/MM/YYYY') AS data_pagamento,
                    TO_CHAR(tbl_extrato.liberado, 'DD/MM/YYYY')                 AS liberado,
                    tbl_extrato.protocolo,
                    tbl_os.fabricacao_produto,
                    tbl_os.qtde_km,
                    tbl_os.valores_adicionais,
                    tbl_os.os_numero,
                    tbl_os.cortesia,
                    tbl_os.key_code,
                    tbl_os.embalagem_original,
                    tbl_linha.nome AS nome_linha,
                    tbl_familia.descricao AS descricao_familia,
                    tbl_os.nf_os,
                    tbl_os.qtde_hora,
                    tbl_os.hora_tecnica as os_hora_tecnica,
                    $os_bloqueada
                    $campos_roca
                    tbl_os_campo_extra.campos_adicionais AS os_campos_adicionais,
                    tbl_os_campo_extra.valores_adicionais AS os_valores_adicionais,
                    tbl_os_campo_extra.os_revenda AS id_os_revenda,
                    TO_CHAR(tbl_os_extra.inicio_atendimento, 'DD/MM/YYYY HH24:MI:SS') AS inicio_atendimento,
                    TO_CHAR(tbl_os_extra.termino_atendimento, 'DD/MM/YYYY HH24:MI:SS') AS termino_atendimento,
                    tbl_os_extra.regulagem_peso_padrao,
                    tbl_status_checkpoint.descricao AS status_checkpoint,
                    tbl_status_checkpoint.status_checkpoint AS codigo_status_checkpoint
            FROM tbl_os
            JOIN tbl_posto               ON tbl_posto.posto               = tbl_os.posto
            JOIN tbl_posto_fabrica       ON tbl_posto_fabrica.posto       = tbl_os.posto $cond
            LEFT JOIN tbl_motivo_reincidencia ON tbl_os.motivo_reincidencia    = tbl_motivo_reincidencia.motivo_reincidencia
            LEFT JOIN tbl_os_extra            ON tbl_os.os                     = tbl_os_extra.os
            LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os_campo_extra.fabrica = $login_fabrica
            LEFT JOIN tbl_extrato             ON tbl_extrato.extrato           = tbl_os_extra.extrato
            AND tbl_extrato.fabrica = $login_fabrica
            LEFT JOIN tbl_extrato_pagamento   ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
            LEFT JOIN tbl_admin               ON tbl_os.admin                  = tbl_admin.admin
            LEFT JOIN tbl_admin troca_admin   ON tbl_os.troca_garantia_admin   = troca_admin.admin
            LEFT JOIN tbl_defeito_reclamado   ON tbl_os.defeito_reclamado      = tbl_defeito_reclamado.defeito_reclamado
            LEFT JOIN tbl_defeito_constatado  ON tbl_os.defeito_constatado     = tbl_defeito_constatado.defeito_constatado
            LEFT JOIN tbl_causa_defeito       ON tbl_os.causa_defeito          = tbl_causa_defeito.causa_defeito
            LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo
            LEFT JOIN tbl_produto             ON tbl_os.produto                = tbl_produto.produto
            $join_extra
            LEFT JOIN tbl_tipo_atendimento    ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
            LEFT JOIN tbl_marca on tbl_produto.marca = tbl_marca.marca
            LEFT JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
            LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica
	    LEFT JOIN tbl_tipo_os ON tbl_tipo_os.tipo_os = tbl_os.tipo_os
	    LEFT JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
            $left_join_auditoria_reprovada
            $joinTblTec
            $join_revenda
            $left_join_os_troca
	    $left_join_marca_troca
            WHERE tbl_os.os = $os
            $cond_os_cancelada";
    if ($login_e_distribuidor == "t" || !empty($distribuidor)) {
#        $sql .= "AND (tbl_os_extra.distribuidor = $login_posto OR tbl_os.posto = $login_posto) ";
    } else {
        $sql .= "AND tbl_os.posto = $login_posto ";
    }

    //die(nl2br($sql));
    $res = pg_query ($con,$sql);

    if (pg_num_rows ($res) > 0) {
        $hd_chamado      = pg_fetch_result($res, 0, "hd_chamado");
        $sua_os          = pg_fetch_result($res, 0, 'sua_os');
        $admin           = pg_fetch_result($res, 0, 'admin');
        $cortesia        = pg_fetch_result($res, 0, 'cortesia');
        $data_digitacao  = pg_fetch_result($res, 0, 'data_digitacao');
        $codigo_status_checkpoint = pg_fetch_result($res, 0, 'codigo_status_checkpoint');

        if($login_fabrica == 157){
            $auditoria_reprovada     = pg_fetch_result($res, 0, 'reprovada');            
        }

        if ( !in_array($login_fabrica, array(7,11,15,172)) ) {
            $box_prateleira  = trim(pg_fetch_result($res, 0, 'prateleira_box'));
        }
        if($login_fabrica == 50){
            $os_bloqueada  = pg_fetch_result($res, 0, 'os_bloqueada');
        }

        if ($login_fabrica == 178){
            $id_os_revenda = pg_fetch_result($res, 0, 'id_os_revenda');

            $sql_km_os_revenda = "SELECT qtde_km FROM tbl_os_revenda WHERE os_revenda = $id_os_revenda";
            $res_km_os_revenda = pg_query($con, $sql_km_os_revenda);

            if (pg_num_rows($res_km_os_revenda) > 0){
                $qtde_km_revenda = pg_fetch_result($res_km_os_revenda, 0, 'qtde_km');
            }
        }

        if ($login_fabrica != 158) {
            $data_digitacao = explode(" ", $data_digitacao);
            $data_digitacao = $data_digitacao[0];
        }
        $data_abertura   = pg_fetch_result($res, 0, 'data_abertura');
        $data_fechamento = pg_fetch_result($res, 0, 'data_fechamento');
        $data_finalizada = pg_fetch_result($res, 0, 'finalizada');
        $data_nf_saida   = pg_fetch_result($res, 0, 'data_nf_saida');
        if($login_fabrica == 153){
            $codigo_lacre = pg_fetch_result ($res,0,codigo_fabricacao);
        }

        if(in_array($login_fabrica, array(160,176)) or $replica_einhell){
            $type         = pg_fetch_result($res, 0, type);
        }

        if ($login_fabrica == 175){
            $qtde_disparos = pg_fetch_result($res, 0, 'capacidade');
        }

        if(in_array($login_fabrica, [167, 203])){
            $contato_consumidor = pg_fetch_result($res, 0, 'contato_consumidor');
            $contador = pg_fetch_result($res, 0, 'contador');

            $produto_parametros_adicionais = pg_fetch_result($res, 0, 'produto_parametros_adicionais');
            $produto_parametros_adicionais = json_decode($produto_parametros_adicionais,true);
            $suprimento = $produto_parametros_adicionais['suprimento'];
        }

        if ($login_fabrica == 158) {
            $data_hora_abertura = pg_fetch_result($res, 0, 'data_hora_abertura');
            $inicio_atendimento = pg_fetch_result($res, 0, "inicio_atendimento");
            $fim_atendimento    = pg_fetch_result($res, 0, "termino_atendimento");
            $amperagem          = pg_fetch_result($res, 0, "regulagem_peso_padrao");
        }else{
            $regulagem_peso_padrao = pg_fetch_result($res, 0, "regulagem_peso_padrao");
        }

        if (in_array($login_fabrica, [169, 170])) {
            $data_hora_abertura = pg_fetch_result($res, 0, 'data_hora_abertura');
            $data_hora_fechamento = pg_fetch_result($res, 0, 'data_hora_fechamento');
        }

        if (in_array($login_fabrica, array(169,170))) {
            $produto_emprestimo = pg_fetch_result($res, 0, contrato);
            $revenda_contato = pg_fetch_result($res, 0, contato_consumidor);
            $justificativa_adicionais = pg_fetch_result($res, 0, justificativa_adicionais);
            $justificativa_adicionais = json_decode($justificativa_adicionais, true);

            if (isset($justificativa_adicionais["motivo_visita"])) {
                $motivo_visita = utf8_decode($justificativa_adicionais["motivo_visita"]);
            }
        }

        $qtde_diaria = pg_fetch_result($res, 0, "qtde_diaria");

        //--==== INFORMACOES DO CONSUMIDOR =================================================
        $consumidor_nome             = pg_fetch_result ($res,0,consumidor_nome);
        if($login_fabrica == 85){
            $array_campos_adicionais = pg_fetch_result($res,$i,array_campos_adicionais);
            if(!empty($array_campos_adicionais)){
                $campos_adicionais = json_decode($array_campos_adicionais);
                if($campos_adicionais->consumidor_cpf_cnpj == 'R'){
                    $consumidor_nome = $campos_adicionais->nome_fantasia;
                }
            }
        }

        $consumidor_endereco       = pg_fetch_result($res, 0, 'consumidor_endereco');
        $consumidor_numero         = pg_fetch_result($res, 0, 'consumidor_numero');
        $consumidor_complemento    = pg_fetch_result($res, 0, 'consumidor_complemento');
        $consumidor_bairro         = pg_fetch_result($res, 0, 'consumidor_bairro');
        $consumidor_cidade         = pg_fetch_result($res, 0, 'consumidor_cidade');
        $consumidor_estado         = pg_fetch_result($res, 0, 'consumidor_estado');
        $consumidor_cep            = pg_fetch_result($res, 0, 'consumidor_cep');
        $consumidor_fone           = pg_fetch_result($res, 0, 'consumidor_fone');
        $consumidor_celular        = pg_fetch_result($res, 0, 'consumidor_celular');
        $consumidor_fone_comercial = pg_fetch_result($res, 0, 'consumidor_fone_comercial');
        $consumidor_fone_recado    = pg_fetch_result($res, 0, 'consumidor_fone_recado');
        $consumidor_cpf            = pg_fetch_result($res, 0, 'consumidor_cpf');
        $consumidor_email          = pg_fetch_result($res, 0, 'consumidor_email');

        if($login_fabrica == 30){
            $admin_autoriza          = pg_fetch_result($res, 0, 'admin_autoriza');
        }

        $fisica_juridica           = pg_fetch_result($res, 0, 'fisica_juridica');
        $data_ressarcimento        = pg_fetch_result($res, 0, 'data_ressarcimento');
        $recolhimento              = pg_fetch_result($res, 0, 'recolhimento');
        $reoperacao_gas            = pg_fetch_result($res, 0, 'reoperacao_gas');
        $valor_troca               = pg_fetch_result($res, 0, 'valor_troca');
        $hora_tecnica              = pg_fetch_result($res, 0, 'hora_tecnica');
        $qtde_horas                = pg_fetch_result($res, 0, 'qtde_horas');
        $obs_adicionais            = pg_fetch_result($res, 0, 'obs_adicionais');
        $numero_pac                = pg_fetch_result($res, 0, 'pac');
        $tecnico                   = pg_fetch_result($res, 0, 'tecnico');
        $os_campos_adicionais      = json_decode(pg_fetch_result($res, 0, "os_campos_adicionais"), true);
        $os_valores_adicionais     = json_decode(pg_fetch_result($res, 0, "os_valores_adicionais"), true);

        if($login_fabrica == 20){
            $motivo_ordem = $os_campos_adicionais['motivo_ordem'];
        }

        if($login_fabrica == 161){
            $sem_ns = $os_campos_adicionais['sem_ns'];
        }

        if( in_array($login_fabrica, array(152,180,181,182)) ) {
            $tempo_deslocamento = pg_fetch_result($res, 0, "qtde_hora");
        }

        if( in_array($login_fabrica, array(11,172)) ){
            $consumidor_fone_comercial = $consumidor_fone_recado;
        }

        if ($login_fabrica == 148) {
            $os_horimetro = pg_fetch_result($res, 0, "qtde_hora");
            $os_revisao = pg_fetch_result($res, 0, "os_hora_tecnica");

            $obs_adicionais_json = json_decode($obs_adicionais);

            $serie_motor       = $obs_adicionais_json->serie_motor;
            $serie_transmissao = $obs_adicionais_json->serie_transmissao;
            $data_falha        = $obs_adicionais_json->data_falha;
        }

        if(in_array($login_fabrica, array(141, 165)) && $posto_interno == true){
            $cod_rastreio = pg_fetch_result($res, 0, "codigo_rastreio");
        }

        if ($login_fabrica == 122) {
            extract(json_decode($obs_adicionais, true));
        }

        if($fisica_juridica=="F"){
            $fisica_juridica = traduz("pessoa.fisica",$con,$cook_idioma);
        }
        if($fisica_juridica=="J"){
            $fisica_juridica = traduz("pessoa.juridica",$con,$cook_idioma);
        }

        //--==== INFORMACOES DA REVENDA ====================================================
        $revenda_cnpj                = pg_fetch_result($res, 0, 'revenda_cnpj');
        $revenda_nome                = pg_fetch_result($res, 0, 'revenda_nome');
        $revenda_fone                = pg_fetch_result($res, 0, 'revenda_fone');
        $nota_fiscal                 = pg_fetch_result($res, 0, 'nota_fiscal');
        $nota_fiscal_saida           = pg_fetch_result($res, 0, 'nota_fiscal_saida');
        $data_nf                     = pg_fetch_result($res, 0, 'data_nf');
        $cliente                     = pg_fetch_result($res, 0, 'cliente');
        $revenda                     = pg_fetch_result($res, 0, 'revenda');
        $consumidor_revenda          = pg_fetch_result($res, 0, 'consumidor_revenda');

        //--==== INFORMACOES DO PRODUTO ====================================================
        $produto                      = pg_fetch_result($res, 0, 'produto');
        $aparencia_produto            = pg_fetch_result($res, 0, 'aparencia_produto');
        $acessorios                   = pg_fetch_result($res, 0, 'acessorios');
        $produto_referencia           = pg_fetch_result($res, 0, 'referencia');
        $produto_referencia_fabrica   = pg_fetch_result($res, 0, 'modelo');
        $produto_modelo               = pg_fetch_result($res, 0, 'modelo');
        $produto_descricao            = pg_fetch_result($res, 0, 'descricao');
        $produto_voltagem             = pg_fetch_result($res, 0, 'voltagem');
        $serie                        = pg_fetch_result($res, 0, 'serie');
        $codigo_fabricacao            = pg_fetch_result($res, 0, 'codigo_fabricacao');
        $troca_obrigatoria            = pg_fetch_result($res, 0, 'troca_obrigatoria');
        $rg_produto                   = pg_fetch_result($res, 0, 'rg_produto');
        $serie_justificativa          = pg_fetch_result($res, 0, 'serie_justificativa');
        $nome_linha                   = pg_fetch_result($res, 0, 'nome_linha');
        $descricao_familia            = pg_fetch_result($res, 0, 'descricao_familia');
        $key_code                     = pg_fetch_result($res, 0, 'key_code');
        $status_checkpoint            = pg_fetch_result($res, 0, 'status_checkpoint');
	

        if ($login_fabrica == 161) {
            $serie = strtoupper($serie);
        }


        if ($login_fabrica == 190) {
            $rg_produto  = pg_fetch_result ($res,  0, 'qtde_hora');
        }


        if ($login_fabrica == 153) {
            $codigo_lacre               = pg_fetch_result ($res,  0, 'codigo_fabricacao');
        }

       if($login_fabrica == 178){
            $marca_troca = pg_fetch_result($res,0,'marca_troca');
            $enviar_para = pg_fetch_result($res,0,'faturamento_cliente_revenda');
        }
	

        //--==== DEFEITOS RECLAMADOS =======================================================
        $defeito_reclamado              = pg_fetch_result($res,0,'defeito_reclamado');
        $defeito_reclamado_descricao    = pg_fetch_result($res,0,'defeito_reclamado_descricao');
        $defeito_reclamado_descricao_os = pg_fetch_result($res,0,'defeito_reclamado_descricao_os');
        $os_posto                       = pg_fetch_result($res,0,'os_posto');
        $data_fabricacao                = pg_fetch_result($res,0,'data_fabricacao');
        if (!empty($data_fabricacao)) {
            $data_fabricacao = is_date($data_fabricacao, 'ISO', 'EUR');
            // $data_fabricacao = DateTime::createFromFormat('Y-m-d',$data_fabricacao);
            // $data_fabricacao = $data_fabricacao->format('d/m/Y');
        }else{
            $data_fabricacao = '';
        }


        if ($login_fabrica == 145) {
            $os_construtora = (pg_fetch_result($res, 0, "nf_os") == "t") ? "Sim" : "Não";
        }

        if (strlen($defeito_reclamado_descricao)==0){
            $defeito_reclamado_descricao = $defeito_reclamado_descricao_os;
        }

	if($login_fabrica == 3 AND strlen($produto) > 0){
		//HD 172561 - Seleciona defeito reclamado e digita defeito reclamado - por enquanto apenas para linha 528 - Informatica
		$sql = "SELECT tbl_linha.linha
			  FROM tbl_produto
			  JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha
			 WHERE tbl_produto.produto=$produto
			   AND tbl_linha.linha=528 ";
		$res_linha = pg_query($con, $sql);

		if (pg_num_rows($res_linha) > 0) {
		    $sql = "SELECT defeito_reclamado_descricao FROM tbl_os WHERE os=$os";
		    $res_linha = pg_query($con, $sql);
		    $defeito_reclamado_descricao = pg_result($res_linha, 0, 'defeito_reclamado_descricao');
		}
	}

        //--==== DEFEITOS CONSTATADO =======================================================
        $defeito_constatado           = pg_fetch_result($res,0,'defeito_constatado');
        $defeito_constatado_codigo    = pg_fetch_result($res,0,'defeito_constatado_codigo');
        $defeito_constatado_descricao = pg_fetch_result($res,0,'defeito_constatado_descricao');
        $defeito_constatado_grupo     = pg_fetch_result($res,0,'defeito_constatado_grupo');
        $id_defeito_constatado_grupo     = pg_fetch_result($res,0,'id_defeito_constatado_grupo');

        //--==== CAUSA DO DEFEITO ==========================================================
        if (in_array($login_fabrica, [169,170])) {
            $causa_defeito_desc = pg_fetch_result($res,0,'causa_defeito_desc');    
        }
	    $causa_defeito           = pg_fetch_result($res,0,'causa_defeito');
        $causa_defeito_codigo    = pg_fetch_result($res,0,'causa_defeito_codigo');
        $causa_defeito_descricao = pg_fetch_result($res,0,'causa_defeito_descricao');

        $representante            = pg_fetch_result($res,0,'representante');
        $posto_codigo            = pg_fetch_result($res,0,'posto_codigo');
        $reembolso_peca_estoque  = pg_fetch_result($res, 0, reembolso_peca_estoque);
        $posto_nome              = pg_fetch_result($res,0,'posto_nome');
        $obs                     = pg_fetch_result($res,0,'obs');
        $recomendacoes           = pg_fetch_result($res,0,'recomendacoes');
        $qtde_produtos           = pg_fetch_result($res,0,'qtde_produtos');
        $excluida                = pg_fetch_result($res,0,'excluida');
        $os_reincidente          = trim(pg_fetch_result($res,0,'os_reincidente'));
        $orientacao_sac          = trim(pg_fetch_result($res,0,'orientacao_sac'));
        $sua_os_offline          = trim(pg_fetch_result($res,0,'sua_os_offline'));
        $solucao_os              = trim (pg_fetch_result($res,0,'solucao_os'));
        $posto_verificado        = trim(pg_fetch_result($res,0,'posto'));
        $marca_nome              = trim(pg_fetch_result($res,0,'marca_nome'));
        $marca                   = trim(pg_fetch_result($res,0,'marca'));
        $marca_os                = trim(pg_fetch_result($res,0,'marca_os'));
        $ressarcimento           = trim(pg_fetch_result($res,0,'ressarcimento'));
        $certificado_garantia    = trim(pg_fetch_result($res,0,'certificado_garantia'));
        $troca_garantia          = trim(pg_fetch_result($res,0,'troca_garantia'));
        $troca_faturada          = trim(pg_fetch_result($res,0,'troca_faturada'));
        $troca_garantia_admin    = trim(pg_fetch_result($res,0,'troca_garantia_admin'));
        $troca_admin             = trim(pg_fetch_result($res,0,'troca_admin'));
        $qtde                    = pg_fetch_result($res,0,'qtde');
        $tipo_os                 = pg_fetch_result($res,0,'tipo_os');
        $tipo_os_descricao       = pg_fetch_result($res, 0, "tipo_os_descricao");
        $tipo_atendimento        = trim(pg_fetch_result($res,0,'tipo_atendimento'));
        $tecnico                 = trim(pg_fetch_result($res,0,tecnico));
        $tecnico_nome             = trim(pg_fetch_result($res,0,'tecnico_nome'));
        $nome_atendimento             = trim(pg_fetch_result($res,0,'nome_atendimento'));
        $codigo_atendimento           = trim(pg_fetch_result($res,0,'codigo_atendimento'));
        $tipo_troca                   = trim(pg_fetch_result($res,0,'tipo_troca'));
        $serie_reoperado              = trim(pg_fetch_result($res,0,'serie_reoperado'));
        if($login_fabrica==14) $numero_controle = $serie_reoperado; //HD 56740
        $embalagem_original     = pg_fetch_result($res, 0, 'embalagem_original');
        if (in_array($login_fabrica, array(156))) {
            $void = $serie_reoperado;
            $sem_ns = $embalagem_original;
            $natureza_operacao = pg_fetch_result($res, 0, "natureza_servico");
        }


        if ($login_fabrica == 178 and !empty($id_defeito_constatado_grupo)) {

            $sqlGrupo = "SELECT descricao FROM tbl_defeito_constatado_grupo WHERE fabrica = {$login_fabrica} AND defeito_constatado_grupo=".$id_defeito_constatado_grupo;
            $resGrupo = pg_query($con, $sqlGrupo);
            $defeito_constatado_grupo     = pg_fetch_result($resGrupo,0,'descricao');
        }

        //--==== AUTORIZAÇÃO CORTESIA =====================================
        //        $autorizacao_cortesia = trim(pg_fetch_result($res,0,autorizacao_cortesia));
        $promotor_treinamento         = trim(pg_fetch_result($res,0,promotor_treinamento));

        //--==== Dados Extrato HD 61132 ====================================
        $extrato                      = trim(pg_fetch_result($res,0,'extrato'));
        $data_previsao                = trim(pg_fetch_result($res,0,'data_previsao'));
        $data_pagamento               = trim(pg_fetch_result($res,0,'data_pagamento'));
        $liberado                     = trim(pg_fetch_result($res,0,'liberado'));
        $protocolo                    = trim(pg_fetch_result($res,0,'protocolo'));
        $extrato_link                 = ($login_fabrica == 1) ? $protocolo : $extrato;

        // HD 64152
        $fabricacao_produto           = trim(pg_fetch_result($res,0,'fabricacao_produto'));
        $qtde_km                      = trim(pg_fetch_result($res,0,'qtde_km'));
        $os_numero                    = trim(pg_fetch_result($res,0,'os_numero'));
        if(strlen($qtde_km) == 0) $qtde_km = 0;

        //HD 399700
        if ($login_fabrica == 96) {
            $motivo                    = trim(pg_fetch_result($res,0,'obs_nf'));
        }

        if ($login_fabrica == 52) {
            $obs_reincidencia          = pg_fetch_result($res,0,'obs_reincidencia');
            $motivo_reincidencia_desc  = pg_fetch_result($res,0,'motivo_reincidencia_desc');
        }

        if (strlen($promotor_treinamento)>0) {
            $sql = "SELECT nome FROM tbl_promotor_treinamento WHERE promotor_treinamento = $promotor_treinamento";
            $res_pt = pg_query($con,$sql);
            if (is_resource($res_pt) and pg_num_rows($res_pt) >0) {
                $promotor_treinamento  = trim(pg_fetch_result($res_pt,0,'nome'));
            }
        }

        if ($login_fabrica == 15) {
            $preco_produto                    = trim(pg_fetch_result($res,0,'valores_adicionais'));
        }

        //--=== Tradução para outras linguas ============================= Raphael HD:1212
        if(strlen($sistema_lingua)>0){
            if(strlen($produto)>0){
                $sql_idioma = "SELECT * FROM tbl_produto_idioma
                                WHERE produto     = $produto
                                  AND upper(idioma) = '$sistema_lingua'";
                $res_idioma = @pg_query($con,$sql_idioma);
                if (@pg_num_rows($res_idioma) >0) {
                    $produto_descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
                }
            }
            if(strlen($defeito_constatado)>0){
                $sql_idioma = "SELECT * FROM tbl_defeito_constatado_idioma
                                WHERE defeito_constatado = $defeito_constatado
                                AND upper(idioma)        = '$sistema_lingua'";
                $res_idioma = @pg_query($con,$sql_idioma);
                if (@pg_num_rows($res_idioma) >0) {
                    $defeito_constatado_descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
                }
            }
            if (strlen($defeito_reclamado) > 0) {
                $sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
                                WHERE defeito_reclamado = $defeito_reclamado
                                AND upper(idioma)        = '$sistema_lingua'";
                $res_idioma = @pg_query($con,$sql_idioma);
                if (@pg_num_rows($res_idioma) >0) {
                    $defeito_reclamado_descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
                }
            }
            if(strlen( $causa_defeito)>0){
                $sql_idioma = " SELECT * FROM tbl_causa_defeito_idioma
                                WHERE causa_defeito = $causa_defeito
                                AND upper(idioma)   = '$sistema_lingua'";
                $res_idioma = @pg_query($con,$sql_idioma);
                if (@pg_num_rows($res_idioma) >0) {
                    $causa_defeito_descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
                }
			}

			if(strlen( $tipo_atendimento)>0){
                $sql_idioma = " SELECT * FROM tbl_tipo_atendimento_idioma
                                WHERE tipo_atendimento = $tipo_atendimento
                                AND upper(idioma)   = '$sistema_lingua'";
                $res_idioma = @pg_query($con,$sql_idioma);
                if (@pg_num_rows($res_idioma) >0) {
                    $nome_atendimento  = trim(@pg_fetch_result($res_idioma,0,descricao));
                }
            }
        }

        # HD 13940 - Ultimo Status para as Aprovações de OS
        $sql = "SELECT status_os, observacao
                FROM tbl_os_status
                WHERE os = $os
                AND status_os IN (92,93,94)
                ORDER BY data DESC
                LIMIT 1";
        $res_status = pg_query($con,$sql);
        if (pg_num_rows($res_status) >0) {
            $status_recusa_status_os  = trim(pg_fetch_result($res_status,0,status_os));
            $status_recusa_observacao = trim(pg_fetch_result($res_status,0,observacao));
            if($status_recusa_status_os == 94){
                $os_recusada = 't';
            }
        }

        # HD 44202 - Ultimo Status para as Aprovações de OS aberta a mais de 90 dias
        $sql = "SELECT status_os, observacao
                FROM tbl_os_status
                WHERE os = $os
                AND status_os IN (120,122,123,126,140,141,142,143)
                ORDER BY data DESC
                LIMIT 1";
        $res_status = pg_query($con,$sql);
        if (pg_num_rows($res_status) >0) {
            $status_os_aberta     = trim(pg_fetch_result($res_status,0,status_os));
            $status_os_aberta_obs = trim(pg_fetch_result($res_status,0,observacao));
        }

        //--=== Tradução para outras linguas ================================================

         if($login_fabrica == 145){
            $complemento_where = "LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda.cidade";
            $complemento_campos = "tbl_cidade.nome as nome_cidade_revenda,
                            tbl_cidade.estado as sigla_estado_revenda,";
        }

        if (strlen($revenda) > 0) {
            $sql = "SELECT  tbl_revenda.endereco   ,
                            tbl_revenda.numero     ,
                            tbl_revenda.complemento,
                            tbl_revenda.bairro     ,
                            $complemento_campos
                            tbl_revenda.cep        ,
                            tbl_revenda.email
                    FROM    tbl_revenda
                    $complemento_where
                    WHERE   tbl_revenda.revenda = $revenda;";
            $res1 = pg_query ($con,$sql);

            if (pg_num_rows($res1) > 0) {
                $revenda_endereco     = strtoupper(trim(pg_fetch_result($res1, 0, 'endereco')));
                $revenda_numero       = trim(pg_fetch_result($res1,            0, 'numero'));
                $revenda_complemento  = strtoupper(trim(pg_fetch_result($res1, 0, 'complemento')));
                $revenda_bairro       = strtoupper(trim(pg_fetch_result($res1, 0, 'bairro')));
                $revenda_email        = trim(pg_fetch_result($res1,            0, 'email'));
                $revenda_cep          = trim(pg_fetch_result($res1,            0, 'cep'));
                $sigla_estado_revenda = trim(pg_fetch_result($res1,            0, 'sigla_estado_revenda'));
                $nome_cidade_revenda  = trim(pg_fetch_result($res1,            0, 'nome_cidade_revenda'));

                $revenda_cep = preg_replace(RE_FMT_CEP, '$1.$2-$3', $revenda_cep);
            }
        }

        if (!in_array($login_fabrica, [186])) {
            $revenda_cnpj = (strlen($revenda_cnpj) == 14) ?
                preg_replace(RE_FMT_CNPJ, '$1.$2.$3/$4-$5', $revenda_cnpj) :
                preg_replace(RE_FMT_CPF,  '$1.$2.$3-$4',    $revenda_cnpj);
        }

        if($aparencia_produto=='NEW'){
            $aparencia = traduz("bom.estado",$con,$cook_idioma);
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='USL'){
            $aparencia = traduz("uso.intenso",$con,$cook_idioma);
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='USN'){
            $aparencia = traduz("uso.normal",$con,$cook_idioma);
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='USH'){
            $aparencia = traduz("uso.pesado",$con,$cook_idioma);
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='ABU'){
            $aparencia = traduz("uso.abusivo",$con,$cook_idioma);
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='ORI'){
            $aparencia = traduz("original.sem.uso",$con,$cook_idioma);
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='PCK'){
            $aparencia = traduz("embalagem",$con,$cook_idioma);
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }

    }else if( in_array($login_fabrica, array(11,172)) ){

        header("Location: os_consulta_lite.php");
        exit;

    }
}

if (strlen($sua_os) == 0) $sua_os = $os;

$title = traduz("confirmacao.de.ordem.de.servico",$con,$cook_idioma);

$layout_menu = 'os';
include "cabecalho.php";

if($login_fabrica == 156 AND !empty($login_unico) AND $login_unico_master != "t" AND $tecnico != $login_unico_tecnico){

    echo "<center>".traduz("esta.ordem.de.servico.pertence.a.outro.tecnico")."</center>";

    include "rodape.php";
    exit;
}

?>

<!-- <script language='javascript' src='js/jquery-1.6.1.min.js'></script> -->
<script language='javascript' src="js/jquery-1.8.3.min.js"></script>
<script language='javascript' src='js/jquery.maskedinput-1.2.2.js'></script>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='js/anexaNF_excluiAnexo.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script type='text/javascript' src='js/FancyZoom.js'></script> 
<script type='text/javascript' src='js/FancyZoomHTML.js'></script>
<script type="text/javascript" src="admin/js/jquery.mask.js"></script>
<style type="text/css">
    @import "plugins/jquery/datepick/telecontrol.datepick.css";
</style>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">

<?php

if (in_array($login_fabrica, [173])) {
    include 'editar_novo_numero_serie.php';
}
if (isset($novaTelaOs) || in_array($login_fabrica, array(52,88))) {
?>
    <script src="plugins/jquery.form.js"></script>
<?php
}
?>

<script language='javascript'>

$(function() {
    setupZoom();
    $("#data_conserto").maskedinput("99/99/9999 99:99");
    if($("#data_recebimento").length){
        $("#data_recebimento").maskedinput("99/99/9999");
        $('#data_recebimento').datepick({startDate:'01/01/2000'});
    }

    <?php if ($login_fabrica <> 178){ ?>
        $("input[name^=data_agendamento_novo]").datepick({minDate: 0, <?=(!in_array($login_fabrica, array(35))) ? "maxDate:5," : ""?> dateFormat: "dd/mm/yyyy" }).mask("99/99/9999");
    <?php } ?>

    <?php if ($login_fabrica == 178){ ?>
        function converte_data (data){
            let d = data.split('/');
            return d[2]+'/'+d[1]+'/'+d[0];
        }
        $("input[name^=data_agendamento_novo]").datepick({minDate: "-5d", dateFormat: "dd/mm/yyyy" }).mask("99/99/9999");
        $("#data_agendamento_novo").change(function(){
            let data_agendamento = $(this).val();
            let data_abertura = $("#input_data_abertura").val();

            data_abertura = new Date(converte_data(data_abertura));
            data_agendamento = new Date(converte_data(data_agendamento));
            
            if (data_agendamento < data_abertura){
                alert("Data de Agendamento não pode ser menor que a data de abertura da Ordem de serviço");
                $("#data_agendamento_novo").val("");
                return;
            }
        });
    <?php } ?>

    $(".correios").click(function(){
        var obj = $(this).attr("rel");
        $("#historicoCorreios").load("os_press.php #somediv",{"buscaCorreios":true,"objeto":obj}, function(){
            Shadowbox.init();
            Shadowbox.open({
            content: "<style>.listEvent{margin-top:60px;}</style><div style='background-color:#FFF; height:150%;padding:20px;'>"+$("#historicoCorreios").html()+"</div>",
                player: "html",
                title:  "Histórico Correios",
                width:  800,
                height: 500
            });
        });
    });

    $('[data-motivo]').on('click', function() {
        motivo = $(this).data('motivo');
        os = $(this).data('os');
        url_os = 'shadowbox_aquarius_status_os.php?os=' + os + '&acao=' + motivo;
        Shadowbox.open({
            content:    url_os,
            player: "iframe",
            title: "Status OS",
            width:  800,
            height: 500
        });
    });

    $("#consertar_os_ajax").click(function(){

        let os = $(this).data("os");

        let curDateTime = new Date();
        let url = "os_consulta_lite.php?consertado=" + escape(os)+'&dt='+curDateTime ;

         $.ajax({
            url: url,
            type: "GET",
            timeout: 8000
        }).fail(function(){
        }).done(function(data){
            location.reload();
        });

    });

    $("#fechar_os_ajax").click(function(){

        let os = $(this).data("os");

        var today = new Date();
        var dd = today.getDate();
        var mm = today.getMonth()+1; //January is 0!
        var yyyy = today.getFullYear();

        if(dd<10) {
            dd = '0'+dd
        } 

        if(mm<10) {
            mm = '0'+mm
        } 

        today = dd + '/' + mm + '/' + yyyy;

         $.ajax({
            url: 'os_fechamento.php',
            type: "POST",
            data: {
                qtde_os : 1,
                sua_os : os,
                btn_acao_pesquisa : 'continuar',
                btn_acao : 'continuar',
                data_fechamento: today,
                os : {
                    0 : {
                        'os_0'    : os,
                        'ativo_0' : 't',
                    },
                },
            },
            timeout: 8000
        }).fail(function(){
        }).done(function(data){
            location.reload();
        });

    });

});

$().ready(function() {

    $(".excluir_termo_entrega").click(function(){
        var termo_excluir = 'entrega';
        var data_os = $(this).data("os_termo");
        
        exclui_termo(termo_excluir, data_os);

    });

    $(".excluir_termo_retirada").click(function(){
        var termo_excluir = 'retirada';
        var data_os = $(this).data("os_termo");
        
        exclui_termo(termo_excluir, data_os);        
    });

    $('.visualizar_check').click(function(){ //hd_chamado=2881143
        $('#checklist_preenchido').show();
        $('.esconder_check').show();
        $('.visualizar_check').hide();
    });

    $('.esconder_check').click(function(){ //hd_chamado=2881143
        $('#checklist_preenchido').hide();
        $('.esconder_check').hide();
        $('.visualizar_check').show();
    });

    Shadowbox.init();

    $("#data_conserto").blur(function() {
        var campo = $(this);
        $.post('<? echo $PHP_SELF; ?>', {
                gravarDataconserto : campo.val(),
                os: campo.attr("alt")
            },
            function(resposta) {
                if (resposta == 'ok') {
                    $('#consertado').html(campo.val());
                } else {
                    alert(resposta);
                    campo.val('');
                }

            }
        );

    });

    $('input[name^=visita_realizada_').change(function(){
        var os_visita = $(this).val();
        var input = $(this);
        var id = $(this).data('os-visita');
        var resposta = $(this).val();

        $.ajax({
            url: window.location.href,
            type: "POST",
            data: { ajax: 'sim', action: 'visita_realizada', id: id, resposta: resposta },
            timeout: 8000
        }).fail(function(){
        }).done(function(data){
            data = JSON.parse(data);
            if (data.ok !== undefined) {
                alert(data.ok);
            }else{
                alert(data.erro);
                var name = input.attr('name');
                $('input[name='+name+']').first().prop('checked', true);
            }
        });
    });

    <?php if (in_array($login_fabrica, array(178,183))){ ?>
        $(".dc_agendamento").datepick({startDate:'01/01/2000'});
    <?php } ?>
    <?php if ($login_fabrica == 50) {//fputti hd-2892486?>
        $(".btn_grava_declaracao").click(function() {
            var os               = $(this).data('os');
            var data_recebimento = $('#data_recebimento').val();
            var recebido_por     = $('#recebido_por').val();

            if (data_recebimento == '') {
                alert('<?= traduz("preencha.o.campo.produto.entregue.em") ?>:');
                $("#data_recebimento").focus();
                return false;
            } else if (recebido_por == '') {
                alert('<?= traduz("preencha.o.campo.recebido.por") ?>:');
                $("#recebido_por").focus();
                return false;
            } else {
                $.ajax({
                    type: "POST",
                    url: "<?php echo $PHP_SELF;?>",
                    data: {ajax_grava_declaracao: true, os: os, data_recebimento: data_recebimento,recebido_por:recebido_por},
                    complete: function(data) {
                        var dados = $.parseJSON(data.responseText);
                        if (dados.ERRO == 'true') {
                            alert(dados.MSN);
                            return false;
                        } else {
                            alert(dados.MSN);
                            window.location.href = "os_press.php?os="+os;
                        }
                    }
                });
            }
        });
<?php
    }
    if (in_array($login_fabrica, array(178,183))){
?>
    $(".cancelar_agendamento").click(function() {
        var that = $(this);
        var posto = $('#posto').val();
        var os = $(that).data('os');
        var motivo_cancelamento = $(that).prev().val();
        var tecnico_agenda = $(that).data('tecnico_agenda');
        var os_revenda = $(that).data('os_revenda');
        var data_agendada = $(that).data('data_agendada');
        var login_fabrica = <?= $login_fabrica; ?>;

        if (motivo_cancelamento == '' || motivo_cancelamento == undefined){
            alert('<?= traduz("e.necessario.informar.o.motivo.do.cancelamento") ?>');
            return false;
        }

        if(confirm('<?= traduz("tem.certeza.que.deseja.cancelar.visita") ?>')) {
            $.ajax({
                type: "POST",
                url: "agendamentos_pendentes.php",
                dataType:"JSON",
                data: {
                    ajax_cancelar_visita: true,
                    posto: posto,
                    os_revenda: os_revenda,
                    os: os,
                    tecnico_agenda: tecnico_agenda,
                    motivo_cancelamento: motivo_cancelamento,
                    data_agendada: data_agendada,
                    login_fabrica: login_fabrica
                },
                beforeSend: function() {
                    $(that).text("Cancelando...").prop({ disabled: true });
                },
            }).done(function (retorno) {
                if (retorno.sucesso == 1) {
                    // $(that).parents("tr").css("background", "#ff6159");
                    // $(that).parents("tr").find('.td_motivo_cancelamento').html(motivo_cancelamento);
                    // $(that).parent().append().html("Visita cancelada");
                    window.location.href = "os_press.php?os="+os;
                } else {
                    alert(retorno.msg);
                    $(that).text("Cancelar Visita").prop({ disabled: false });
                }
            });
        } else {
            return false;
        }
    });

    $(".agendamento_realizado").click(function() {
        var that = $(this);
        var posto = $('#posto').val();
        var data_confirmacao = $(that).prev().val();
        var tecnico_agenda = $(that).data('tecnico_agenda');
        var os_revenda = $(that).data('os_revenda');
        var data_agendada = $(that).data('data_agendada');
        var login_fabrica = <?= $login_fabrica; ?>;
        var os = <?=$os?>;
        if (data_confirmacao == '' || data_confirmacao == undefined){
            alert('<?= traduz("e.necessario.selecionar.a.data.confirmacao") ?>');
            return false;
        }

        if(confirm('<?= traduz("tem.certeza.que.deseja.confirmar.visita") ?>')) {
            $.ajax({
                type: "POST",
                url: "agendamentos_pendentes.php",
                dataType:"JSON",
                data: {
                    ajax_confirmar_visita: true,
                    posto: posto,
                    os_revenda: os_revenda,
                    os: os,
                    tecnico_agenda: tecnico_agenda,
                    data_confirmacao: data_confirmacao,
                    data_agendada: data_agendada,
                    login_fabrica: login_fabrica
                },
                beforeSend: function() {
                    $(that).text("Confirmando...").prop({ disabled: true });
                },
            }).done(function (retorno) {
                if (retorno.sucesso == 1) {
                    window.location.href = "os_press.php?os="+os;
                    //$(that).parent().append().html(data_confirmacao);
                } else {
                    alert(retorno.msg);
                    $(that).text("Confirmar Visita").prop({ disabled: false });
                }
            });
        } else {
            return false;
        }
    });
<?php        
    }

    if(in_array($login_fabrica, array(35,169,170,171,178,183,190,195))){
?>
        $("#reagendar_os").click(function(){
            $("#rel_agenda").show();
        });

        $(".btn_confirmar").click(function() {
            var that = $(this);
            var posto = $('#posto').val();
            var tecnico_agenda = $(that).data('tecnico-agenda');
            var os_revenda = $(that).data('os_revenda');
            var tecnico = $('#tecnico').val();
            var linha = $(".linha_agenda");
            var data_agendamento = $("#data_agendamento").val();
            var data_agendamento_novo = $("#data_agendamento_novo").val();
            var hd_chamado = $("#hd_chamado").val();
            var os = $(that).data('os');
            var login_fabrica = <?= $login_fabrica; ?>;
            var periodo = $("#periodo").val();
            var obs_motivo_agendamento = $("#obs_motivo_agendamento").val();
            var justificativa = $("#justificativa").val();

            if (login_fabrica == 169 || login_fabrica == 170 || login_fabrica == 171) {
                if (tecnico == '') {
                    alert('<?= traduz("e.necessario.selecionar.um.tecnico.para.efetuar.o.agendamento") ?>');
                    return false;
                }

                if (obs_motivo_agendamento == ''){
                    alert('Descreva o motivo do reagendamento');
                    return false;
                }
            }

            if (login_fabrica == 35) {
                if (justificativa == '') {
                    alert("Selecione a Justificativa do agendamento da visita.");
                    return false;
                }
            }

            if (data_agendamento_novo == ''){
                alert('<?= traduz("e.necessario.selecionar.a.data.de.agendamento") ?>');
                return false;
            }

            if (periodo == ''){
                alert('<?= traduz("selecione.um.periodo.para.efetuar.o.agendamento") ?>');
                return false;
            }

            if (obs_motivo_agendamento == ''){
                alert('<?= traduz("descreva.o.motivo.do.reagendamento") ?>');
                return false;
            }

            if(confirm('<?= traduz("tem.certeza.que.deseja.confirmar.o.agendamento") ?>')) {
                $.ajax({
                    type: "POST",
                    url: "agendamentos_pendentes.php",
                    dataType:"JSON",
                    data: {
                        ajax_reagendar_os: true,
                        posto: posto,
                        tecnico: tecnico,
                        os: os,
                        hd_chamado: hd_chamado,
                        tecnico_agenda: tecnico_agenda,
                        data_agendamento: data_agendamento,
                        data_agendamento_novo: data_agendamento_novo,
                        login_fabrica: login_fabrica,
                        periodo: periodo,
                        obs_motivo_agendamento: obs_motivo_agendamento,
                        justificativa:justificativa,
                        os_revenda: os_revenda
                    },
                    beforeSend: function() {
                        $(that).text("Confirmando...").prop({ disabled: true });
                    },
                }).done(function (retorno) {
                    if (retorno.sucesso == 1) {
                        $("#rel_agenda").hide();
                        $("#data_agendamento_novo").val('');
                        if (login_fabrica == 169 || login_fabrica == 170 || login_fabrica == 171) {
                            $("#tecnico").val('');
                        }

                        if (login_fabrica == 35) {
                            $("#justificativa").val('');
                        }

                        if(retorno.dados["acao"] == "insert"){
                            if (login_fabrica == 35) {
                                if (retorno.opt != "" && retorno.opt != "NULL") {
                                    var opt = retorno.opt;
                                }
                                alert("<?= traduz('agendamento.atualizado.com.sucesso') ?>.\n"+opt);
                            } else {
                                if (login_fabrica == 178 && retorno.auditoria_visita == "true"){
                                    alert("Reagendamento realizado com sucesso. Ordem de serviço em auditoria de visita.");
                                }else{
                                    alert("<?= traduz('reagendamento.realizado.com.sucesso') ?>");
                                }
                            }
                            window.location.href = "os_press.php?os="+os;
                        } else {
                            alert("<?= traduz('agendamento.atualizado.com.sucesso') ?>");
                            window.location.href = "os_press.php?os="+os;
                        }
                    } else {
                        $(that).text("<?= traduz('confirmar') ?>").prop({ disabled: false });
                        $("#rel_agenda").show();
                        alert(retorno.msg);
                    }
                });
            } else {
                return false;
            }
        });

    <?php } ?>
<?php
if (in_array($login_fabrica, [30,35,72,175,203])) { ?>
    $("#abreHelp").click(function(){
        var abreUrl = "helpdesk_posto_autorizado_novo_atendimento.php?os_abertura=<?=$os?>";
        $(window.location).attr({
            href:abreUrl
        });
    });
    $("#histHelp").click(function(){
        Shadowbox.open({
            content :   "helpdesk_posto_autorizado_historico.php?os=<?=$os?>",
            player  :   "iframe",
            title   :   "<?= traduz('historico.de.help.desk') ?>",
            width   :   800,
            height  :   500
        });
    });
<?php
}

if($login_fabrica == 127){ ?>
    $("#dataChegada").mask("99/99/9999");
<?php
}
    if (isset($novaTelaOs) || in_array($login_fabrica, array(52))) {
    ?>
        $("form[name=form_anexo]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    $("span[name=anexo_"+data.posicao+"]").html("<a href='"+data.full+"' target='_blank'><img src='"+data.thumb+"' style='width: 100px; height: 90px;' /></a>");
                    setupZoom();
                }
            }
     });

        $("button[name=anexar_arquivo]").click(function() {
            $(this).parent("span").find("form > input[type=file]").click();
        });

        $("input[name=anexo_upload]").change(function() {
            $(this).parent("form").submit();
        });
    <?php
    }

    if (isset($anexo_peca_os)) { ?>
        $("form[name=form_anexo_peca]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    var spanNameData = ['anexo_peca', data.os_produto, data.os_item, data.posicao];
                    var spanName =  spanNameData.filter(function(i) {return i != undefined && i != 0;}).join('_');
                    $("span[name="+spanName+"]").html(data.referencia+"<br /><a href='"+data.full+"' target='_blank'><img src='"+data.thumb+"' style='width: 100px; height: 90px;' /></a>");
                    setupZoom();
                }
            }
        });

        $("button[name=anexar_arquivo_peca]").click(function() {
            $(this).parent().find("input[type=file]").click();
        });

        $("input[name=anexo_peca_upload]").change(function() {
            $(this).parent().submit();
        });
    <?php
    }
    ?>

    <?php

    if ($login_fabrica == 88) { ?>

        $("form[name=form_anexo]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    var posicao =  data.posicao;
                    $("span[name=anexo_"+posicao+"]").html("<br /><a href='"+data.full+"' target='_blank'><img src='"+data.thumb+"' style='width: 100px; height: 78px;' /></a>");
                    setupZoom();
                }
            }
        });

        $('button[name="excluir_anexo_nf"]').click(function(){
            var arquivo = $(this).parent().find("input[name=anexo]").val();
            var posicao = $(this).parent().find("input[name=posicao_ex]").val();

            $.ajax({
                url:"<?=$PHP_SELF?>",
                type: "POST",
                dataType:"json",
                data: {
                    excluir_anexo_nf: "true",
                    arquivo: arquivo
                },
                complete: function(data){
                    data = $.parseJSON(data.responseText);
                    if (data.error) {
                        alert(data.error);
                    } else {
                        //$("span[name=anexo_"+posicao+"]").hide();
                        $(".ex_anexo_"+posicao).hide();
                        $(".anexo_nf_"+posicao).show();
                        $(".anexo_nf_"+posicao).css('margin-top', '6px');
                        $("span[name='anexo_"+posicao+"']").parent().find("img[name='img_anexo_"+posicao+"']").attr('src','imagens/imagem_upload.png');

                    }
                }
            });

        });

        $("button[name=anexar_arquivo]").click(function() {
            $(this).parent().find("input[type=file]").click();
        });

        $("input[name=anexo_upload]").change(function() {
            $(this).parent().submit();
        });
    <?php
    }
    ?>
});

    <?php if (in_array($login_fabrica, array(167,173,177,191,203))) {
     ?>
        function alterar_anexo_fechamento(){
            $("button[name='alterar_anexo_fechamento']").hide();
            $("#alterar_anexo_fechamento").show();
            $("#anexo_fechamento").show();
        }

        function enviar_orcamento(){
            var dados_email = $("#dados_email").val();
            var os = '<?=$os?>';

            $.ajax({
                url:"<?=$PHP_SELF?>",
                type: "POST",
                data: {
                    enviar_orcamento: "true",
                    dados_email: dados_email,
                    os: os
                },
                complete: function(data){
                    if(data.responseText == "ok"){
                        alert("<?= traduz('email.enviado.com.sucesso') ?>");
                    }else{
                        alert(data.responseText);
                    }
                }
            });
        }

    <?php } ?>

<?php if (in_array($login_fabrica,array(11,35,104,172))){ ?>

function solicitaPostagemPosto(solicitacao,os) {
        tipo_postagem = $('#tipo_postagem').val();        

        if(tipo_postagem != '') {
            Shadowbox.open({
                    content :   "solicitacao_postagem_correios_posto.php?solicitacao="+ solicitacao+"&tipo="+tipo_postagem+"&os="+os,
                    player  :   "iframe",
                    title   :   "Autorização de Postagem",
                    width   :   800,
                    height  :   500
                    });
        } else {
            alert('<?= traduz("escolha.o.tipo.de.postagem") ?>');
        }

}

<?php } ?>

function exclui_termo(termo, os) {

    if (termo != "" && os != "") {

        $.ajax({
            url: "os_press.php",
            type: "post",
            data: {excluir_termo: true, termo: termo, os: os},
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.sucess) {
                    alert('<? fecho("termo.excluido.com.sucesso",$con,$cook_idioma) ?>');
                    location.reload();
                } else {
                    alert('<? fecho("erro.ao.excluir.o.termo",$con,$cook_idioma) ?>');
                }
            }
        });
    }
}

function fechaOS(os, fechar) {

    var curDateTime = new Date();
    var fechamento  = document.getElementById('data_fechamento');
    var finalizada  = document.getElementById('finalizada');
    var fechado_em  = document.getElementById('fechado_em');
    $.ajax({
        type: "GET",
        url: "<?=$PHP_SELF?>",
        data: 'fechar=' + escape(os) + '&dt='+curDateTime,
        beforeSend: function(){
            $(fechar).slideUp('slow');
        },
        complete: function(http) {
            results = http.responseText.split(";");
            if (typeof (results[0]) != 'undefined') {
                if (results[0] == 'ok') {
                    fechar.src='/imagens/pixel.gif';
                    fechar.innerHTML = "";
                    fechamento.innerHTML = results[2];
                    finalizada.innerHTML = results[3];
                    fechado_em.innerHTML = results[4];
                    alert ('OS <? fecho("fechada.com.sucesso",$con,$cook_idioma) ?>');
                } else {

                    $(fechar).show('slow');

                    if (http.responseText.indexOf ('de-obra para instala') > 0) {
                        alert ('<? fecho("esta.os.nao.tem.mao-de-obra.para.instalacao",$con,$cook_idioma) ?>');
                    } else if (http.responseText.indexOf ('Nota Fiscal de Devol') > 0) {
                        alert ('<? fecho("por.favor.utilizar.a.tela.de.fechamento.de.os.para.informar.a.nota.fiscal.de.devolucao",$con,$cook_idioma) ?>');
                    } else if (http.responseText.indexOf ('o-de-obra para atendimento') > 0) {
                        alert ('<? fecho("esta.os.nao.tem.mao-de-obra.para.este.atendimento",$con,$cook_idioma) ?>');
                    } else if (http.responseText.indexOf ('Favor informar aparência do produto e acessórios') > 0) {
                        alert ('<? fecho("por.favor.verifique.os.dados.digitados.aparencia.e.acessorios.na.tela.de.lancamento.de.itens",$con,$cook_idioma) ?>');
                    } else if (http.responseText.indexOf ('Type informado para o produto não é válido') > 0) {
                        alert ('<? fecho("type.informado.para.o.produto.nao.e.valido",$con,$cook_idioma) ?>');
                    } else if (http.responseText.indexOf ('OS com peças pendentes') > 0) {
                        alert ('<? fecho("os.com.pecas.pendentes,.favor.informar.o.motivo.na.tela.de.fechamento.da.os",$con,$cook_idioma) ?>');
                    } else if(http.responseText.indexOf ('OS não pode ser fechada, Favor Informar a Kilometragem') > 0) {
                        alert ('<? fecho("os.nao.pode.ser.fechada,.favor.informar.a.kilometragem",$con,$cook_idioma) ?>');
                    } else if (http.responseText.indexOf ('OS não pode ser fechada, Kilometragem Recusada') > 0) {
                        alert ('<? fecho("os.nao.pode.ser.fechada,.kilometragem.recusada",$con,$cook_idioma) ?>');
                    } else if (http.responseText.indexOf ('OS não pode ser fechada, aguardando aprovação de Kilometragem') > 0) {
                        alert ('<? fecho("os.nao.pode.ser.fechada,.aguardando.aprovacao.de.kilometragem",$con,$cook_idioma) ?>');
                    } else if (http.responseText.indexOf ('Esta OS teve o número de série recusado e não pode ser finalizada') > 0) {
                        alert ('<? fecho("esta.os.teve.o.numero.de.serie.recusado.e.nao.pode.ser.finalizada",$con,$cook_idioma) ?>');
                    } else if (http.responseText.indexOf ('Informar defeito constatado (Reparo) para OS') > 0){
                        alert ('<? fecho("por.favor.verifique.os.dados.digitados.em.defeito.constatado.(reparo).na.tela.de.lancamento.de.itens",$con,$cook_idioma) ?>');
                    } else if (http.responseText.indexOf ('Por favor, informar o conserto do produto na tela CONSERTADO') > 0) {
                        alert ('<? fecho("por.favor.informar.o.conserto.do.produto.na.tela.consertado",$con,$cook_idioma) ?>');
                    } else {
                        alert ('<? fecho("por.favor.verifique.os.dados.digitados.defeito.constatado.e.solucao.na.tela.de.lancamento.de.itens",$con,$cook_idioma)?>');
                    }
                }

            } else {
                alert ('<? fecho("fechamento.nao.processado",$con,$cook_idioma) ?>');
            }
        }
    });

}

function aprovaOrcamento(orcamento) {

    var qtde = document.getElementById('qtde_pecas_orcamento').value;
    var msg = "";
    for (i=0;i<qtde;i++) {
        msg = msg + document.getElementById('peca_orcamento_'+i).value + ' - no valor de '+ document.getElementById('preco_orcamento_'+i).value+' \n';
    }
    if (confirm('<?= traduz("tem.certeza.que.deseja.aprovar.este.orcamento.caso.sim.sera.faturada.as.seguintes.pecas    
") ?>:\n'+msg)== true) {
        requisicaoHTTP('GET','ajax_aprova_orcamento.php?orcamento='+orcamento+'&acao=aprovar', true , 'div_detalhe_carrega');
    }

}

function reprovaOrcamento(orcamento) {
    requisicaoHTTP('GET','ajax_aprova_orcamento.php?orcamento='+orcamento+'&acao=reprovar', true , 'div_detalhe_carrega');
}

function div_detalhe_carrega(campos) {
    campos_array = campos.split("|");
    orcamento = campos_array [0];
    var div = document.getElementById('msg_orcamento');
    var div_btn = document.getElementById('aprova_reprova');
    div.innerHTML = orcamento;
    div_btn.style.display = 'none';
}

function showHideGMap() {
    var gMapDiv = $('#gmaps');
    var newh    = (gMapDiv.css('height')=='5px') ? '486px' : '5px';
    gMapDiv.animate({height: newh}, 400);
    if (newh=='5px') gMapDiv.parent('td').css('height', '2em');
    if (newh!='5px') gMapDiv.parent('td').css('height', 'auto');
}

<?php if (in_array($login_fabrica, array(169,170))){ ?>
    function retornoPostagem(status,hd_chamado){
        if(status == "true"){
            $("#lgr_correios").hide();
        }
        Shadowbox.close();
    }

    function solicitaPostagem(hd_chamado, codigo_posto) {
        Shadowbox.open({
            content :   "solicitacao_postagem_correios_produto.php?hd_chamado="+hd_chamado+"&codigo_posto="+codigo_posto,
            player  :   "iframe",
            title   :   "<?= traduz('solicitar.autorizacao.de.postagem') ?>",
            width   :   1000,
            height  :   700,
            options: {
                modal: true,
                enableKeys: false,
                displayNav: false
            }
        });
    }
<?php } ?>
<?php
if (isset($novaTelaOs) OR $login_fabrica == 52) {
?>
    $(function() {
        $("button[name=excluir_anexo]").click(function() {
            if (confirm("<?= traduz('deseja.realmente.excluir.o.anexo') ?>")) {
                var span  = $(this).parent("span");

                var anexo = $(span).find("input[name=anexo]").val();
                var ano   = $(span).find("input[name=ano]").val();
                var mes   = $(span).find("input[name=mes]").val();

                $.ajax({
                    url: "os_press.php",
                    type: "post",
                    data: { excluir_anexo: true, anexo: anexo, ano: ano, mes: mes },
                    complete: function(data) {
                        data = $.parseJSON(data.responseText);

                        if (data.error) {
                            alert(data.error);
                        } else {
                            $(span).remove();
                        }
                    }
                });
            }
        });

        $("button[name=excluir_anexo_peca]").click(function() {
            if (confirm("<?= traduz('deseja.realmente.excluir.o.anexo.da.peca') ?>")) {
                var span  = $(this).parent("span");
                var anexo = $(span).find("input[name=anexo]").val();

                $.ajax({
                    url: "os_press.php",
                    type: "post",
                    data: {excluir_anexo_peca: true, anexo: anexo},
                    complete: function(data) {
                        data = $.parseJSON(data.responseText);

                        if (data.error) {
                            alert(data.error);
                        } else {
                            $(span).remove();
                        }
                    }
                });
            }
        });
    });
<?php
}

if ($login_fabrica == 162 && $login_unico_master == 't') {
?>
$(function() {
    $("#transferirTec").click(function(e){
        e.preventDefault();
        var tecnicoTransferir = $("select[name=tecnico]").val();
        var os = <?=$os?>;

        $.ajax({
            url:"os_press.php",
            type:"POST",
            dataType:"JSON",
            data: {
                ajax:true,
                os:os,
                tecnicoTransferir:tecnicoTransferir
            }

        })
        .done(function(data){
            alert("OS Transferida");
            location.reload();
        });
    });
});
<?php
}
?>

function visualizaranexo(anexo) {
		Shadowbox.init();
		Shadowbox.open({
	        content: "admin/imagens_comprovante_suggar.php?anexo=" + anexo,
	        player: "iframe",
	        width: 850,
	        height:400,
	        options: {
	            modal: true,
	            enableKeys: true,
	            displayNav: true
	        }
	    });
	}

</script>
<style type="text/css">

.vermelho {color: #f00!important}

body {
    margin: 0px;
}

#DIVanexos table{
    width: 700px;
    text-align: center;
    margin: 0 auto;
    margin-top: 20px;
}

table {
    margin: auto !important;
}

.titulo {
    font-family: Arial;
    font-size: 7pt;
    text-align: right;
    color: #000000;
    background: #ced7e7;
    padding-left: 5px;
    padding-right: 5px;
    text-transform: uppercase;
}

.titulo2 {
    font-family: Arial;
    font-size: 7pt;
    text-align: center;
    color: #000000;
    background: #ced7e7;
    text-transform: uppercase;
}
.titulo3 {
    font-family: Arial;
    font-size: 10px;
    text-align: right;
    color: #000000;
    background: #ced7e7;
    height:16px;
    padding-left:5px;
    padding-right: 1ex;
    text-transform: uppercase;
}

.titulo4 {
    font-family: Arial;
    font-size: 10px;
    text-align: left;
    color: #000000;
    background: #ced7e7;
    height:16px;
    padding-left:0px;
}

.inicio {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    color: #FFFFFF;
    padding-right: 1ex;
    text-transform: uppercase;
}

.conteudo {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    background: #F4F7FB;
    padding-left: 5px;
    padding-right: 5px;
}

.justificativa{
    font-family: Arial;
    FONT-SIZE: 10px;
    background: #F4F7FB;
}

.Tabela{
    border:1px solid #d2e4fc;
    background-color:#485989;
    }

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.highlightSRO{
width:100%;
text-align:center;
font-size:1.2em;
}

.highlightSRO .status{
font-weight: bold;
font-size:1.1em;
}

.highlightSRO img{
padding:14px;
margin:auto;
display:block;
}
table.listEvent tr td.sroDtEvent, table.listEvent tr td.sroLcEvent {
font-size: 1.1em;
color: #8A8A8A;
border-top: 1px dotted #8A8A8A;
}
table.listEvent tr td.sroLbEvent {
font-size: 1.2em;
/*font-weight: bold;*/
border-top: 1px dotted #8A8A8A;
}
table.listEvent tr td.sroLbEvent span{
font-weight: normal;
display:block;
}
.highlightSRO .status{
font-weight: bold;
font-size:1.1em;
}

#somediv {
left: 0; /*numeric value in pixels you would like this div to be from the left */
right: 0; /*same as above, except on the right */
height: 50px;
width: auto;
}

.foto {
width:400px;
/*height:40px;*/
position: relative;
}

.subtitulo {
    font-family: Verdana;
    FONT-SIZE: 9px;
    text-align: left;
    background: #F4F7FB;
    padding-left:5px
}
.inpu{
    border:1px solid #666;
}
.titulo_tabela{
        background-color:#596d9b;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
}

table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
}

table.listEvent{
    border:1px solid #d2e4fc;
    width: 100%;
}

table.listEvent tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

table.listEvent tr:nth-child(2n+1) {
    background: #f9f9f9;
}

.conteudo2 {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    background: #FFDCDC;
}

.conteudo_sac {
    font-family: Arial;
    font-size: 10pt;
    text-align: left;
    background: #F4F7FB;
}
#gmaps {
    width:606px;
    height: 5px;
    /*display:none;*/
    margin:1ex auto;
    background-color:#CED7E7;
    border:6px solid #CED7E7;
    border-top-width:24px;
    border-radius:12px;
    -moz-border-radius:12px;
    cursor:help;
    overflow: hidden;
    z-index:100;
    /*transition: height 0.5s ease-in;
    -o-transition: height 0.5s ease-in;
    -ms-transition: height 0.5s ease-in;
    -moz-transition: height 0.5s ease-in;
    -webkit-transition: height 0.5s ease-in;*/
}

@media print {
    .mapa_gmaps {
        display:none;
    }
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

input[type=button] {
    font-weight: normal;
    font-size: 13px;
}

.previsao_entrega{
    display: inline;
    position: relative;
    /*font-size: 13px;
    vertical-align: middle;*/
}

.btn_agendamento_primay {
    padding: 2px 10px;
    font-size: 11.9px;
    border-radius: 3px;
    background-color: #006dcc;
    color: white;
    background-image: linear-gradient(to bottom, #0088cc, #0044cc);
    background-repeat:  repeat-x;
    border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
}
.btn_agendamento_danger {
    padding: 2px 10px;
    font-size: 11.9px;
    border-radius: 3px;
    background-color: #da4f49;
    color: white;
    background-image: linear-gradient(to bottom, #ee5f5b, #bd362f);
    background-repeat:  repeat-x;
    border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
}

.previsao_entrega:hover:after{
    padding: 5px 15px;
    width: 220px;
    border-radius: 5px;
    background: #333;
    background: rgba(0,0,0,.8);
    content: attr(data-title);
    position: absolute;
    right: 20%;
    bottom: 26px;
    z-index: 98;
    color: #fff;
}

.previsao_entrega:hover:before{
    border: solid;
    border-color: #333 transparent;
    border-width: 6px 6px 0px 6px;
    content: "";
    position: absolute;
    /*left: 50%;*/
    right: 20%;
    bottom: 20px;
    z-index: 99;
}

#msg-alt-extrato{
    height: 30px;
    width: 700px;
    background-color: yellow;
    color: red;
    font-weight: bold;
    font-size: 16px;
    line-height:30px;
}
</style>

<?php

if ($login_fabrica == 148) {  

    $sql = "SELECT auditoria_os reprovada
            FROM tbl_auditoria_os
            WHERE os = {$os}
            AND reprovada IS not NULL";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) { ?> 
    
        <strong>
            <font size="6" color="red">REPROVADA EM AUDITORIA</font>
        </strong>

    <?php } ?>

    <br>

<?php } 

if(in_array($login_fabrica, array(3,42,72))){

    $sql_os_cancelada = "SELECT cancelada FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
    $res_os_cancelada = pg_query($con, $sql_os_cancelada);

    $os_cancelada = pg_fetch_result($res_os_cancelada, 0, "cancelada");
    if($os_cancelada == "t"){

        if($login_fabrica == 3){
            echo "<div style='width: 700px; margin: 0 auto; border-radius: 4px; padding: 10px 0px; text-transform: uppercase; font-weight: bold; font-size: 16px; margin-bottom: 10px; margin-top: 0px; background-color: #ffcccc; color: #ff4d4d;'>";
            echo traduz("O.S cancelada");
            echo "</div>";
        }else{
            echo "<div style='width: 700px; margin: 0 auto; border-radius: 4px; padding: 10px 0px; text-transform: uppercase; font-weight: bold; font-size: 16px; margin-bottom: 10px; margin-top: 30px; background-color: #ffcccc; color: #ff4d4d;'>";
                echo traduz("esta.os.esta.cancelada");
            echo "</div>";
        }
        echo "</div>";
    }
}

	//Verifica se OS existe -- HD 735968
if(empty($xlogin_posto) and empty($distribuidor)) {

    if(is_numeric($os)){
        if ($login_fabrica == 183 AND $login_tipo_posto_codigo == "Rep"){
            $sql = "
                SELECT tbl_os.os FROM tbl_os 
                JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                JOIN tbl_representante ON tbl_representante.representante = tbl_os_extra.representante AND tbl_representante.cnpj = '{$login_cnpj}'
                WHERE tbl_os.os = {$os}
                AND tbl_representante.fabrica = {$login_fabrica}";
        }else{
            $sql = "SELECT os FROM tbl_os WHERE os = $os AND {$cond_pesquisa_fabrica} AND posto = $login_posto";
        }
        $res = pg_exec($con, $sql);

        if (pg_numrows($res) == 0) {

            $sql_exc = "SELECT * FROM tbl_os_excluida WHERE os = $os";
            $res_exc = pg_exec($con, $sql_exc);

            if (pg_numrows($res_exc) > 0) {
                $motivo_exclusao = pg_fetch_result($res_exc, 0, motivo_exclusao);
                $sql_exc = "SELECT observacao FROM tbl_os_status WHERE os = $os and status_os = 15";
                $res_exc = pg_exec($con, $sql_exc);

                if (pg_num_rows($res_exc)) {
                    echo "<br />". pg_result($res_exc, 0, 'observacao');
                } else {
                    if ($login_fabrica == 157) {
                        echo "<br />". $motivo_exclusao ;
                    } else {
                        echo traduz("existe.um.registro.de.exclusao.para.esta.os");
                    }
                }

            } else {

                echo '<center>'.traduz("os.nao.encontrada").'</center>';

            }

            include 'rodape.php';
            exit;

        }
    }else{
        echo '<center>'.traduz("os.nao.encontrada").'</center>';
        include 'rodape.php';
        exit;
    }

}
if (in_array($login_fabrica, array(151))) {
    $sql_serie_controle = "SELECT motivo FROM tbl_serie_controle WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND lower(serie) = lower('{$serie}')";
    $res_serie_controle = pg_query($con, $sql_serie_controle);
    if (pg_num_rows($res_serie_controle)) {
        echo "<table class='msg_erro' align='center' width='700'>
                <tr>
                    <td align='center'>
                        ".traduz("numero.de.serie.bloqueado.motivo").": ".pg_fetch_result($res_serie_controle, 0, 'motivo')."
                    </td>
                </tr>
            </table>";
    }

    $sqlStatus = "SELECT tbl_os.status_checkpoint
                  FROM tbl_os
                  JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
                  WHERE tbl_os.os = {$os}
                  AND tbl_status_checkpoint.descricao = 'Aguardando Analise Helpdesk'";
    $resStatus = pg_query($con, $sqlStatus);

    if (pg_num_rows($resStatus) > 0) {

        echo "<table class='msg_erro' align='center' width='700'>
                <tr>
                    <td align='center'>
                        " . traduz("OS AGUARDANDO ANÁLISE DO HELP-DESK")."
                    </td>
                </tr>
            </table>";

    }

}
if (in_array($login_fabrica, array(81, 114))) {//HD 307124 - inicio - OS CANCELADA

    $sql = "SELECT cancelada from tbl_os where fabrica=$login_fabrica and os=$os";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {

        $cancelada = pg_result($res, 0, 0);

        if ($cancelada == 't') {?>
            <table class='msg_erro' align='center' width='700'>
                <tr>
                    <td align='center'> <?= traduz("os.cancelada") ?> </td>
                </tr>
            </table><?php

        }

    }

}//HD 307124 - fim - OS CANCELADA

if (in_array($login_fabrica, [157])) { 

$sqlAuditoriaReprovada = "SELECT os FROM tbl_auditoria_os 
                          WHERE os = {$os} AND reprovada IS NOT NULL";
$resAuditoriaReprovada = pg_query($con, $sqlAuditoriaReprovada);

    if (pg_num_rows($resAuditoriaReprovada) > 0) {
    ?>
        <TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" >
            <tr class="msg_erro" >
                <td colspan="4">OS reprovada na auditoria</td>
            </tr>
        </table>
    <?php
    }
}


if(in_array($login_fabrica, array(52, 81, 114))) {
    $os=$_GET['os'];
    $sql_atr = "SELECT  motivo_atraso
            FROM tbl_os
            WHERE os = $os
            AND fabrica = $login_fabrica
            and finalizada is null";
    $res_atr = pg_query($con,$sql_atr);
    if (pg_num_rows($res_atr) > 0 and strlen(pg_result($res_atr, 0, motivo_atraso))>0) {
        echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
        echo "<tr>";
        echo "<td align='center'><b><font size='1'>";
        echo strtoupper(traduz("MOTIVO DO ATRASO",$con,$cook_idioma));
        echo "</font></b></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td align='center'><b><font size='1'>";

        if($login_fabrica == 52){

            $motivo_atraso = pg_result($res_atr, 0, "motivo_atraso");

            $sql = "SELECT descricao FROM tbl_motivo_atraso_fechamento WHERE motivo_atraso_fechamento = {$motivo_atraso}";
            $res = pg_query($con, $sql);

            $motivo_atraso = pg_fetch_result($res, 0, "descricao");

        }else{

            $motivo_atraso = pg_result($res_atr, 0, "motivo_atraso");

        }

        echo $motivo_atraso;
        echo "</font></b></td>";
        echo "</tr>";
        echo "</table>";
    }
}

$mostraReincidencia = true;
if (in_array($login_fabrica, [173])) { 
    ?><center>
        <a class='btn btn-success' href='admin/imprimir_etiqueta.php?imprimir=true&os=<?=$_GET['os']?>' target='_blank'>Imprimir Etiqueta</a>
    </center>
    </br><?php
} 
if (in_array($login_fabrica, array(163))) {
    $sql_r = "SELECT  osr.os,
                    osr.sua_os
                FROM tbl_os_extra
                    INNER JOIN tbl_os AS osr ON osr.os = tbl_os_extra.os_reincidente
                WHERE tbl_os_extra.os = {$os}
                    AND osr.posto = {$login_posto}";
    $res_r = pg_query($con,$sql_r);

    if (pg_num_rows($res_r) == 0 ) {
        $mostraReincidencia = false;
    }
}

if ( (strlen($os_reincidente) > 0 OR $reincidencia =='t') AND $mostraReincidencia ) {
    $sql = "SELECT  tbl_os_status.status_os,
                    tbl_os_status.observacao,
                    (select posto from tbl_os WHERE tbl_os.os = tbl_os_extra.os_reincidente) as posto_reincidente
            FROM  tbl_os_extra JOIN tbl_os_status USING(os)
            WHERE tbl_os_extra.os = $os
            AND   tbl_os_status.status_os IN (67,68,70,86,239)";
    $res1 = pg_query ($con,$sql);

    if (pg_num_rows ($res1) > 0) {
        $status_os         = trim(pg_fetch_result($res1,0,status_os));
        $observacao        = trim(pg_fetch_result($res1,0,observacao));
        $posto_reincidente = trim(pg_fetch_result($res1,0,posto_reincidente));
    } else {
        $posto_reincidente = $login_posto;
    }

    if (($login_fabrica == 50) and ($login_posto <> $posto_reincidente)) {
        $mostra_reincidencia = false;
    }

    if ($login_fabrica == 1) {
        $mostra_reincidencia = true;
    }

    //HD3646
    if((($login_fabrica==3 && $login_fabrica == 74 and $login_posto==$posto_reincidente) or ($login_fabrica != 74 && $login_fabrica<>3 and $login_fabrica<> 6 and $login_fabrica <> 30) or ($login_fabrica == 30 && !empty($extrato))) and $status_os <> '239' and true === $mostra_reincidencia){
        echo "<table style=' text-transform: uppercase; border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
        echo "<tr>";
        echo "<td align='center'><b><font size='1'>";
        echo strtoupper(traduz("atencao",$con,$cook_idioma));
        echo "</font></b></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td align='center'><font size='1'>";

        if ($login_fabrica == 30) {
            $limit_reincidencia = "LIMIT 5";
        }

        if(strlen($os_reincidente)>0  && !isset($novaTelaOs)){

            $sql = "SELECT  tbl_os.sua_os,
                            tbl_os.serie
                    FROM    tbl_os
                    WHERE   tbl_os.os = $os_reincidente;";
            $res1 = pg_query ($con,$sql);
            $sos   =   trim(pg_fetch_result($res1,0,sua_os));
            $serie_r = trim(pg_fetch_result($res1,0,serie));

            if($login_fabrica==1) $sos=$posto_codigo.$sos;
        } else if(isset($novaTelaOs)) {

            $sql = "SELECT osr.os, osr.sua_os, osr.fabrica FROM tbl_os_extra INNER JOIN tbl_os AS osr ON osr.os = tbl_os_extra.os_reincidente WHERE tbl_os_extra.os = {$os}";

            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $osr        = pg_fetch_result($res, 0, "os");
		$osr_sua_os = pg_fetch_result($res, 0, "sua_os");
		$osr_fabrica = pg_fetch_result($res,0,"fabrica");

		if($login_fabrica == $osr_fabrica){
			echo traduz("ordem.de.servico.reincidente.da.ordem.de.servico").": <a href='os_press.php?os={$osr}' target='_blank'>{$osr_sua_os}</a>";
		}else{
			echo traduz("ordem.de.servico.reincidente.da.ordem.de.servico").": {$osr_sua_os}";
		}
            }
        } else {
            //CASO NÃO TENHA A REINCIDENCIA NÃO TENHA SIDO APONTADA, PROCURA PELA REINCIDENCIA NA SERIE
            $sql = "SELECT os,sua_os,posto
                    FROM tbl_os
                    JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
                    WHERE   serie   =  '$serie_r'
                    AND     os      <> $os
                    AND     fabrica =  $login_fabrica
                    AND     tbl_produto.numero_serie_obrigatorio IS TRUE
                    AND     tbl_os.posto=$login_posto
                    ORDER BY tbl_os.os
                    $limit_reincidencia";
            $res2 = pg_query ($con,$sql);

            echo strtoupper(traduz("ordem.de.servico.com.numero.de.serie.%.reincidente.ordem.de.servico.anterior",$con,$cook_idioma,array($serie_r))).":<br>";

            if (pg_num_rows ($res2) > 0) {
                for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
                    $sos_reinc  = trim(pg_fetch_result($res2,$i,sua_os));
                    $os_reinc   = trim(pg_fetch_result($res2,$i,os));
                    $posto_reinc   = trim(pg_fetch_result($res2,$i,posto));
                    if($posto_reinc == $login_posto){
                        echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";
                    } else {
                        ### Não pode mostrar o número da OS reincidente de outro posto!!!!
                        ### Alterado Samuel 26/04/2010 - HD 228477
                        ### echo "» $sos_reinc<br>";
                    }

                }
            }

        }
        if($status_os==67 && !isset($novaTelaOs)){

            echo strtoupper(traduz("ordem.de.servico.com.numero.de.serie.%.reincidente.ordem.de.servico.anterior",$con,$cook_idioma,array($serie_r))).":<br>";

            if ( in_array($login_fabrica, array(11,94,96,172)) ) {
                $sql = "SELECT os_reincidente
                        FROM tbl_os_extra
                        WHERE os= $os";
                $res2 = pg_query($con,$sql);

                $osrein = pg_fetch_result($res2,0,os_reincidente);

                if (pg_num_rows($res2) > 0) {
                    $sql = "SELECT os,sua_os
                            FROM tbl_os
                            WHERE   serie   = '$serie'
                            AND     os      = $osrein
                            AND     fabrica = $login_fabrica";
                }
                $res2 = pg_query($con,$sql);

                if (pg_num_rows($res2) > 0) {
                    $sua_osrein = pg_fetch_result($res2,0,sua_os);
                    echo "<a href='os_press.php?os=$osrein' target='_blank'>» $sua_osrein</a>";
                }
            } else {

                if ($login_fabrica == 74 or $login_fabrica == 52) { // HD 708057

                    $sql = "SELECT os_reincidente
                            FROM tbl_os_extra
                            WHERE os = $os";
                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res)) {

                        $os_reinc = pg_result($res,0,0);
                        echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $os_reinc</a><br>";

                    }

                }
                else {

                    if ($login_fabrica != 122) {
                        $reincidente_ns_obrigatorio = " AND     tbl_produto.numero_serie_obrigatorio IS TRUE ";
                    }

                    $sql = "SELECT os,sua_os,posto
                    FROM tbl_os
                    JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
                    WHERE   serie   = '$serie'
                    AND     os     <> $os
                    AND     fabrica = $login_fabrica
                    $reincidente_ns_obrigatorio
                    AND     tbl_os.posto=$login_posto
                    ORDER BY tbl_os.os DESC
                    $limit_reincidencia";

                    $res2 = pg_query ($con,$sql);

                    if (pg_num_rows ($res2) > 0) {
                        for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
                            $sos_reinc  = trim(pg_fetch_result($res2,$i,sua_os));
                            $os_reinc   = trim(pg_fetch_result($res2,$i,os));
                            $posto_reinc   = trim(pg_fetch_result($res2,$i,posto));
                            if($posto_reinc == $login_posto){
                                echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";
                            } else {
                                ### Não pode mostrar o número da OS reincidente de outro posto!!!!
                                ### Alterado Samuel 26/04/2010 - HD 228477
                                ### echo "» $sos_reinc<br>";
                            }
                        }
                    }
                }
            }
        }elseif($status_os==68){
            echo strtoupper(traduz("ordem.de.servico.com.mesma.revenda.e.nota.fiscal.reincidente.ordem.de.servico.anterior",$con,$cook_idioma)).": <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
        }elseif($status_os==70 && !isset($novaTelaOs)){
            echo strtoupper(traduz("ordem.de.servico.com.mesma.revenda.nota.fiscal.e.produto.reincidente.ordem.de.servico.anterior",$con,$cook_idioma)).": <a style='color:#63798D; font-weight: bold;' href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
        }elseif($status_os==95){
            echo strtoupper(traduz("ordem.de.servico.com.mesma.nota.fiscal.e.produto.reincidente.ordem.de.servico.anterior",$con,$cook_idioma)).": <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
        } else if (!isset($novaTelaOs)) {
            echo traduz("os.reincidente",$con,$cook_idioma).":<a href='os_press.php?os=$os_reincidente' target = '_blank'>$sos</a>";
        }
        echo "";
        echo "</font></td>";
        echo "</tr>";
        echo "</table>";
    }


}

if ($login_fabrica == 94 && $login_posto == 114768 ) {

    $specialChar = array("(",")",".");
    $os = str_replace($specialChar, "", $os);

    $sql = "SELECT tbl_os.sua_os
                FROM tbl_os_campo_extra
                JOIN tbl_os ON tbl_os.os = tbl_os_campo_extra.os_troca_origem AND tbl_os.fabrica = tbl_os_campo_extra.fabrica
                WHERE tbl_os_campo_extra.os = $os
                AND tbl_os_campo_extra.fabrica = $login_fabrica";

    $res = pg_query($con,$sql);

    if ( pg_num_rows($res) ) {

        echo '<table style="border: #D3BE96 1px solid; background-color: #FCF0D8" align="center" width="700">
                    <tr>
                        <td align="center"><font size="1">OS de Origem: &nbsp;'.pg_result($res,0,0).'</font></td>
                    </tr>
                </table>';

    }

}

if ($login_fabrica == 178 AND $consumidor_revenda == "S"){
    $consumidor_revenda = "CONSTRUTORA";
}else if ($login_fabrica == 178 AND $consumidor_revenda == "A"){
    $consumidor_revenda = "Arquiteto/Engenheiro";
}else if ($login_fabrica == 178 AND $consumidor_revenda == "E"){
    $consumidor_revenda = "Equipe Comercial";
}else if ($login_fabrica == 178 AND $consumidor_revenda == "I"){
    $consumidor_revenda = "Instalador";
}else if ($login_fabrica == 178 AND $consumidor_revenda == "P"){
    $consumidor_revenda = "Posto Autorizado";
}

if ($consumidor_revenda == 'R')
    $consumidor_revenda = 'REVENDA';
else
    if ($consumidor_revenda == 'C')
        $consumidor_revenda = 'CONSUMIDOR';
 ##############################################
# se é um distribuidor da Britania consultando #
# exibe o posto                                #
 ##############################################
if (in_array($login_fabrica, [174]) && $posto_interno && $codigo_status_checkpoint != 9) {

    $disabled_nf_entrada = "disabled";
    $disabled_consertar = "disabled";
    $disabled_analise = "disabled";
    $disabled_nf_saida = "disabled";
    $disabled_rastreio = "disabled";
    $disabled_fechar = "disabled";

    switch ($codigo_status_checkpoint) {
        case 40:
            $disabled_nf_entrada = "";
            break;
        case 3:
            $disabled_consertar  = "";
            break;
        case 1:
            $disabled_analise    = "";
            break;
        case 41:
        case 39:
            $disabled_nf_saida   = "";
            break;
        case 42:
            $disabled_rastreio   = "";
            break;
        case 43:
            $disabled_fechar     = "";
            break;
    }

?>
<table width=900 border="0" cellspacing="1" align='center' cellpadding="0" >
    <tr>
        <td>
            <button style="font-size: 13px;height: 46px;" data-motivo='nf_entrada' data-os='<?=$os?>' <?= $disabled_nf_entrada ?>>Informar NF de Entrada</button>
        </td>

        <td>
            <a href="cadastro_os.php?os_id=<?= $os ?>">
                <button style="font-size: 13px;height: 46px;" <?= $disabled_analise ?>>Informar o defeito do Produto</button>
            </a>
        </td>

        <td>
            <button style="font-size: 13px;height: 46px;" data-os="<?= $os ?>" id="consertar_os_ajax" <?= $disabled_consertar ?>>Consertar OS</button>
        </td>

         <td>
            <button style="font-size: 13px;height: 46px;" data-motivo='nf_saida' data-os='<?=$os?>' <?= $disabled_nf_saida ?>>Informar NF de Saída</button>
        </td>

         <td>
            <button style="font-size: 13px;height: 46px;" data-motivo='rastreio' data-os='<?= $os ?>' <?= $disabled_rastreio ?>>Informar Rastreio</button>
        </td>

         <td>
            <button style="font-size: 13px;height: 46px;" data-os="<?= $os ?>" id="fechar_os_ajax" <?= $disabled_fechar ?>>Fechar OS</button>
        </td>
    </tr>
</table>
<br />
<?php
}

if ((strlen($tipo_atendimento)>0) and (in_array($login_fabrica,array(1,3,91,94,96,40,129)))) {
?>
<center>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='tabela'>
<TR>
<?php
//     echo $tipo_os;
    if (($tipo_os==13 OR in_array($login_fabrica,array(3,91,94,96,129))) || ($tipo_atendimento == 334 && $login_fabrica == 1)) {
        if (mb_check_encoding($nome_atendimento, 'UTF-8')) {
            $nome_atendimento = utf8_decode($nome_atendimento);
        }
?>
    <TD class="titulo" height='20' width='210' nowrap>&nbsp;&nbsp;<?  fecho("tipo.de.atendimento",$con,$cook_idioma); ?>: </TD>
    <TD class="conteudo" height='20' width='400' nowrap><?=" &nbsp;&nbsp;$nome_atendimento";?></TD>
<?php
    } else {
?>
    <TD class="titulo" height='20' width='110' nowrap>&nbsp;&nbsp;<?  fecho("troca.de.produto",$con,$cook_idioma);?>: </TD>
    <?php if ($login_fabrica == 1) { ?>
            <TD class="conteudo" height='20' width='550' nowrap><? echo " &nbsp;&nbsp;$nome_atendimento"; ?></TD>
    <?php } else { ?>
        <TD class="conteudo" height='20' width='130' nowrap><? echo " &nbsp;&nbsp;$nome_atendimento"; ?></TD>
   <?php } ?>
   <?php if ($login_fabrica == 1) {?>
        </TR>
        <TR>
   <?php } ?>
        <TD class="titulo" height='20' width='150' >&nbsp;&nbsp;<? fecho("motivo",$con,$cook_idioma);?>: </TD>
<?php
        $sql_2 = "SELECT tbl_os_status.observacao FROM tbl_os_status JOIN tbl_os_troca using(os) where os = '$os' and (tbl_os_troca.status_os notnull or tbl_os_status.status_os = 13) ; ";
        $res_2 = pg_query($con,$sql_2);
        if(pg_num_rows($res_2) > 0) $obs_status = pg_fetch_result($res_2,0,observacao);
?>
    <TD class="conteudo" height='20'><? echo " &nbsp;&nbsp;$obs_status"; ?></TD>
    <?php
        if($login_fabrica == 1){
            $sql_os_interna = "SELECT tbl_os.os_posto
                                    FROM tbl_os
                                    WHERE tbl_os.os = $os
                                    AND tbl_os.fabrica = $login_fabrica";
            $res_os_interna = pg_query($con, $sql_os_interna);

            if(pg_num_rows($res_os_interna) > 0){
                $os_interna_posto = pg_fetch_result($res_os_interna, 0, 'os_posto');
                echo "<TR>";
                    echo "<TD class='titulo' height='20' width='150'>&nbsp;&nbsp;";
                        fecho("os.interna.posto",$con,$cook_idioma);
                        //echo "OS interna posto:";
                    echo "</TD>";
                    echo "<TD class='conteudo' colspan='3' >&nbsp;&nbsp;$os_interna_posto</TD>";
                echo "</TR>";
            }
        }
    }

?>
</TR><?php

if ($login_fabrica == 1) { #HD 274932

    $sql_3 = "SELECT tbl_os_troca.observacao FROM tbl_os_troca JOIN tbl_os_status USING(os) WHERE os = $os and (tbl_os_troca.status_os notnull or tbl_os_status.status_os = 13) ; ";
    $res_3 = pg_query($con,$sql_3);

    if (pg_num_rows($res_3) > 0) {
        $obs_troca = pg_fetch_result($res_3,0,'observacao');?>
        <TR>
            <TD class="inicio" height='20' width='50' >&nbsp;&nbsp;<? fecho("obs",$con,$cook_idioma);?>: </TD>
            <TD class="conteudo" height='20' colspan='3'><? echo " &nbsp;&nbsp;$obs_troca"; ?></TD>
        </TR><?php
    }

}?>

</TABLE>
</center>
<?
}
if($login_fabrica ==1 AND strlen($os) > 0){ // HD 17284
    $sql2="SELECT TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') as data,
                  descricao,
                  tbl_os_status.observacao,
                  tbl_os_status.campos_adicionais->>'oculta_historio_posto' AS oculta_historio_posto
            FROM tbl_os_status
            JOIN tbl_status_os USING(status_os)
            JOIN    tbl_os_troca    ON  tbl_os_status.os    = tbl_os_troca.os
       LEFT JOIN    tbl_admin       ON  tbl_os_troca.admin  = tbl_admin.admin
                                    AND tbl_admin.fabrica   = $login_fabrica
            WHERE tbl_os_status.os=$os
            AND tbl_os_troca.status_os  IS NOT NULL";
       
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){
        echo "<TABLE width='700' border='0' align='center' cellspacing='0' cellpadding='0' class='Tabela'>";
        echo "<TR>";
        echo "<TD class='inicio' colspan='2' align='center'>".traduz("historico",$con,$cook_idioma)."</TD>";
        echo "</TR>";
        for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
            $data             = pg_fetch_result($res2,$i,data);
            $descricao_status = pg_fetch_result($res2,$i,descricao);
            $observacao_status = pg_fetch_result($res2,$i,observacao);
            $oculta_historio_posto = pg_fetch_result($res2,$i,'oculta_historio_posto');

            if ($observacao_status == 'OS Aprovada pelo Fabricante')
                $bloqueia_excluir_anexo = true;

            echo "<TR>";
            echo "<TD class='conteudo' colspan='2' align='center'>$data - $descricao_status</TD>";
            echo "</tr>";
            echo "<TR>";
            if (!$oculta_historio_posto) {
                echo "<TD class='conteudo2' colspan='2' align='center'>".traduz("motivo",$con,$cook_idioma).": $observacao_status</TD>";
            }
            echo "</TR>";
        }
        echo "</TABLE></center>";
    }
}
//OR $login_fabrica ==50

if($login_fabrica ==30 AND strlen($os) > 0){ // HD 209166
    $sql2="SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
                  tbl_admin.login
            FROM tbl_os_status
            JOIN tbl_os USING(os)
            JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
            WHERE os=$os
            AND   tbl_os.os_reincidente IS TRUE
            AND   tbl_os_status.extrato IS NULL
            AND status_os IN (132,19)
            AND status_os_ultimo = 19
            ORDER BY os_status DESC
            LIMIT 1";
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){
        $data        = pg_fetch_result($res2,0,'data');
        $login       = pg_fetch_result($res2,0,'login');

        echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
        echo "<TR>";
        echo "<TD class='inicio' width='60%'>Admin(".traduz("aprovou.reincidencia").")</TD>";
        echo "<TD class='inicio'>".traduz("data")."</TD>";
        echo "</TR>";
        echo "<TR>";
        echo "<TD class='conteudo'>$login</TD>";
        echo "<TD class='conteudo'>$data</TD>";
        echo "</tr>";
        echo "</TABLE>";
    }
}

if($login_fabrica ==30 AND strlen($os) > 0){ // HD 209166
    $sql2="SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
                  tbl_admin.login,
                  status_os
            FROM tbl_os_status
            JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
            WHERE os=$os
            AND   tbl_os_status.extrato IS NULL
            AND status_os IN (103,104)
            ORDER BY os_status desc
            limit 1";
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){
        $data       = pg_fetch_result($res2,0,'data');
        $login      = pg_fetch_result($res2,0,'login');
        $status_os  = pg_fetch_result($res2,0,'status_os');
        $resposta   = ($status_os == 103) ? "APROVOU" : "REPROVOU";

        echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
        echo "<TR>";
        echo "<TD class='inicio' width='60%'>Admin (".$resposta." NÚMERO DE SÉRIE)</TD>";
        echo "<TD class='inicio'>".traduz("data")."</TD>";
        echo "</TR>";
        echo "<TR>";
        echo "<TD class='conteudo'>$login</TD>";
        echo "<TD class='conteudo'>$data</TD>";
        echo "</tr>";
        echo "</TABLE>";
    }
}

if($login_fabrica ==30 AND strlen($os) > 0){ // HD 209166
    $sql2="SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
                  tbl_admin.login,
				  tbl_os_status.status_os
            FROM tbl_os_status
            JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
            WHERE os=$os
            AND   tbl_os_status.extrato IS NULL
            AND status_os IN (99,100,101)
            ORDER BY os_status desc
            limit 1";
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){
        $data        = pg_fetch_result($res2,0,'data');
        $login       = pg_fetch_result($res2,0,'login');
        $status_os   = pg_fetch_result($res2,0,'status_os');

        echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
        echo "<TR>";
		echo "<TD class='inicio' width='60%'>Admin(";
		echo ($status_os == 100) ? traduz("aprovou") : traduz("reprovou");
		echo " KM)</TD>";
        echo "<TD class='inicio'>".traduz("data")."</TD>";
        echo "</TR>";
        echo "<TR>";
        echo "<TD class='conteudo'>$login</TD>";
        echo "<TD class='conteudo'>$data</TD>";
        echo "</tr>";
        echo "</TABLE>";
    }
}

if($login_fabrica ==35 AND strlen($os) > 0){ // HD 56418
    $sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
                  descricao,
                  observacao
            FROM tbl_os_status
            JOIN tbl_status_os using(status_os)
            WHERE os=$os
            AND status_os IN (13,19,127)
            ORDER BY os_status desc
            limit 1";
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){
        echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
        echo "<TR>";
        echo "<TD class='inicio' colspan='2' align='center'>".traduz("historico",$con,$cook_idioma)."</TD>";
        echo "</TR>";
        for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
            $data             = pg_fetch_result($res2,$i,data);
            $descricao_status = pg_fetch_result($res2,$i,descricao);
            $observacao_status = pg_fetch_result($res2,$i,observacao);
            echo "<TR>";
            echo "<TD class='conteudo2' colspan='2' align='center'>$data - $descricao_status</TD>";
            echo "</tr>";
        }
        echo "</TABLE></center>";
    }
}

if(($login_fabrica ==14 or $login_fabrica == 52) AND strlen($os) > 0){ // HD 65661
    $sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
                  descricao,
                  tbl_os_status.observacao,
                  status_os
            FROM tbl_os_status
            JOIN tbl_status_os using(status_os)
            JOIN tbl_os using(os)
            WHERE os=$os
            AND   tbl_os.os_reincidente IS TRUE
            AND   tbl_os_status.extrato IS NULL
            AND status_os IN (13,19)
            ORDER BY os_status desc
            limit 1";
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){
        echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
        echo "<TR>";
        echo "<TD class='inicio' colspan='2' align='center'>".traduz("historico",$con,$cook_idioma)."</TD>";
        echo "</TR>";
        for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
            $data             = pg_fetch_result($res2,$i,data);
            $status_os       = pg_fetch_result($res2,$i,status_os);
            $descricao_status = pg_fetch_result($res2,$i,descricao);
            $observacao_status = pg_fetch_result($res2,$i,observacao);
            echo "<TR>";
            echo "<TD class='conteudo2' colspan='2' align='center'>$data - $descricao_status";
            if($status_os == 13) {
                echo "- Motivo: $observacao_status";
            }
            if($status_os == 19) {
                echo " da reincidência";
            }
            echo "</TD>";
            echo "</tr>";
        }
        echo "</TABLE></center>";
    }

}

if ($excluida == "t") {
?>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela' >
<TR>
    <TD  bgcolor="#FFE1E1" height='20'>

    <h1 style="text-align: center;color: NAVY;font-size: 14px;">
    <?
    if ($login_fabrica==20 AND $os_recusada =='t'){
        #HD 13940
        echo strtoupper(traduz("os.recusada",$con,$cook_idioma ))." - ".$status_recusa_observacao;
    } else {
        echo (!in_array($login_fabrica, [30,193]) && !$cancelaOS) ? strtoupper(traduz("ordem.de.servico.excluida",$con,$cook_idioma)) : traduz("ordem.de.servico.cancelada");
    }
    ?>
    </h1>
    </TD>
    <?

        $specialChar = array("(",")",".");
        $os = str_replace($specialChar, "", $os);

        if($login_fabrica==3 AND strlen($os)>0){
            $sqlE = "SELECT tbl_admin.login
                     FROM tbl_os
                     JOIN tbl_admin on tbl_admin.admin = tbl_os.admin_excluida
                     WHERE tbl_os.os = $os";
            $resE = pg_exec($con,$sqlE);

            if(pg_numrows($resE)>0){
                $admin_nome = pg_result($resE,0,login);
                echo "<TD bgcolor='#FFE1E1' height='20'>";
                    echo "<h1>".traduz("admin.exclusao").": $admin_nome</h1>";
                echo "</TD>";
            }
        }
    ?>
</TR>
</TABLE>
</center>
<?
}

//HD 211825: Novo status de OS de Troca criado: Autorização para Troca pela Revenda, somente Salton
if (in_array($login_fabrica, array(81, 114))) {
    $sql = "SELECT troca_revenda FROM tbl_os_troca WHERE os=$os ";
    $res_troca_revenda = pg_query($con, $sql);

    if (pg_num_rows($res_troca_revenda)) {
        $troca_revenda = pg_result($res_troca_revenda, 0, troca_revenda);
    }
}

if ($troca_revenda == "t") {
    echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
    echo "<TR height='30'>";
    echo "<TD align='left' colspan='3'>";
    echo "<font family='arial' size='2' color='#ffffff'><b>";
    echo traduz("autorizacao.de.troca.pela.revenda");
    echo "</b></font>";
    echo "</TD>";
    echo "</TR>";
    echo "<TR>";
    echo "<TD class='titulo3' style='text-align:left' height='15' >".traduz("responsavel")."</TD>";
    echo "<TD class='titulo3' style='text-align:left' height='15' >".traduz("data")."</TD>";
    echo "</TR>";
    echo "<TR>";
    echo "<TD class='conteudo' height='15'>";
    echo "&nbsp;&nbsp;&nbsp;";
    echo $troca_admin;
    echo "&nbsp;&nbsp;&nbsp;";
    echo "</TD>";
    echo "<TD class='conteudo' height='15' nowrap>";
    echo "&nbsp;&nbsp;&nbsp;";
    echo $data_fechamento ;
    echo "&nbsp;&nbsp;&nbsp;";
    echo "</TD>";
    echo "</TR>";
    echo "</TABLE>";
}
// HD 11068 8/1/2008
############################################################################
elseif ($ressarcimento == "t") {
    echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
    echo "<TR height='30'>";
    echo "<TD align='left' colspan='3'>";
    echo "<font family='arial' size='2' color='#ffffff'><b> ";
    fecho ("ressarcimento.financeiro",$con,$cook_idioma);
    echo "</b></font>";
    echo "</TD>";
    echo "</TR>";

    //4/1/2008 HD 11068
    if(in_array($login_fabrica,array(11,45,101,172))){
        $sql = "SELECT
                    observacao,descricao
                FROM tbl_os_troca
                LEFT JOIN tbl_causa_troca USING (causa_troca)
                WHERE tbl_os_troca.os = $os";
        $resY = pg_query ($con,$sql);

        if (pg_num_rows ($resY) > 0) {
            $troca_observacao = pg_fetch_result ($resY,0,observacao);
            $troca_causa      = pg_fetch_result ($resY,0,descricao);
        }
    }
    echo "<tr>";
    echo "<TD class='titulo2'  height='15' >".traduz("responsavel",$con,$cook_idioma)."</TD>";
    echo "<TD class='titulo2'  height='15' >".traduz("data",$con,$cook_idioma)."</TD>";
    if(in_array($login_fabrica,array(45,101))){
        echo "<TD class='titulo2'  height='15' >".traduz("observacao",$con,$cook_idioma)."</TD>";
    }elseif( in_array($login_fabrica, array(11,172)) ){
        echo "<TD class='titulo2'  height='15' >".traduz("causa",$con,$cook_idioma)."</TD>";
    } else {
        echo "<TD class='titulo3'  height='15' >&nbsp;</TD>";
    }
    echo "</tr>";

    echo "<tr>";
    echo "<TD class='conteudo' height='15'>";
    echo "&nbsp;&nbsp;&nbsp;";
    echo $troca_admin;
    echo "&nbsp;&nbsp;&nbsp;";
    echo "</td>";
    echo "<TD class='conteudo' height='15'>";
    echo "&nbsp;&nbsp;&nbsp;";
    if( in_array($login_fabrica, array(11,172)) ) { // HD 56237
        echo $data_ressarcimento;
    } else {
        echo $data_fechamento ;
    }
    echo "&nbsp;&nbsp;&nbsp;";
    echo "</td>";

    if(in_array($login_fabrica,array(45,101))){
        echo "<TD class='conteudo' height='15' width='80%'>$troca_observacao</td>";
    }elseif( in_array($login_fabrica, array(11,172)) ){
        echo "<TD class='conteudo'  height='15' >$troca_causa</TD>";
    } else {
        echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
    }
    echo "</tr>";

    if( in_array($login_fabrica, array(11,172)) ) { // hd 56237
        echo "<tr>";
        echo "<TD class='conteudo' height='15' colspan='100%'>OBS: $troca_observacao</td>";
        echo "</tr>";
    }

    echo "</table>";
}

############################################################################


##########################################################################################
####################### INFORMÇÕES DE TROCA TECTOY HD 311414 24/03/2011 ##################
##########################################################################################

if ( $login_fabrica == 6 ){
	$sql = "SELECT os FROM tbl_os WHERE status_os_ultimo = 191 and os = $os ";
	$resxx = pg_query($con,$sql);
	if(pg_num_rows($resxx) > 0) {
		$bloqueia_excluir_anexo = true;
	}

    $sql_bloqueio = "SELECT os FROM tbl_os_status WHERE os = $os and status_os in (190, 191) order by data desc limit 1 ";
    $res_bloqueio = pg_query($con, $sql_bloqueio);
    if(pg_num_rows($res_bloqueio)>0){
        $bloqueia_excluir_anexo = true;
    }

    $sql_peca_tectoy = "
                            SELECT
                                tbl_os_troca.peca
                            from tbl_os_troca
                            where os = $os

                        ";

    $res_peca_tectoy = pg_query ($con,$sql_peca_tectoy);
    if ( pg_num_rows($res_peca_tectoy)>0 ) {

        $peca_troca_tectoy = pg_result($res_peca_tectoy,0,"peca");

        if ( strlen($peca_troca_tectoy)>0){
            $sql_peca_desc_tectoy = "SELECT descricao from tbl_peca where peca=$peca_troca_tectoy";

            $res_peca_desc_tectoy = pg_query($con,$sql_peca_desc_tectoy);

            if ( pg_num_rows($res_peca_desc_tectoy)>0 ){
                $peca_desc_troca_tectoy = pg_result($res_peca_desc_tectoy,0,'descricao');
            }
        }


    }


    if ($peca_desc_troca_tectoy):
        ?>
        <TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>
            <tr>
                <td class='inicio' align='center' colspan='100%'>
                    <?= traduz("informacoes.de.troca") ?>
                </td>
            </tr>
            <tr>
                <td class='conteudo' align='center' colspan='100%'>
                    <?= traduz("trocado.para.o.produto") ?>
                    <?php echo $peca_desc_troca_tectoy ?>
                </td>
            </tr>
            <?php
                $osTroca = getPedidoTroca($os);
                if($osTroca['pedido']):
            ?>
            <tr>
                <td class='conteudo' align='center' colspan='100%'>
                    Pedido: <a href="pedido_finalizado.php?pedido=<?php echo $osTroca['pedido'] ?>" target="_blank"><?php echo $osTroca['pedido']?></a>
                <td/>
            </tr>
            <?php
                endif;
            ?>
        </table>
        <?php
    endif;


}

########################### INFORMÇÕES DE TROCA TECTOY - FIM #############################

// Verifica se o pedido de peça foi cancelado ou autorizado caso a peça esteja bloqueada para garantia
#Fabrica 25 - HD 14830
# HD 13618 - NKS
# HD 12657 - Dynacom
if ($historico_intervencao && !in_array($login_fabrica,array(138))) {
    if(in_array($login_fabrica, array(40))){
        $sql_status = "SELECT
                        status_os,
                        observacao,
                        tbl_admin.login,
                        to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
                    FROM tbl_os_status
                        LEFT JOIN tbl_admin USING(admin)
                    WHERE os=$os
                    ORDER BY tbl_os_status.data DESC LIMIT 1";
    }else{
        $sql_status = "SELECT    status_os,
                    observacao,
                    tbl_admin.login,
                    to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data,
                    tbl_os_status.data as date
                    FROM tbl_os_status
                    LEFT JOIN tbl_admin USING(admin)
                    WHERE os=$os
                    AND status_os IN (13,19,72,73,62,64,65,67,81,87,88,98,99,100,101,102,103,104,116,117,118,147,167,168,171,172,173,179)
                    ORDER BY date DESC LIMIT 1";
    }    
    $res_status = pg_query($con,$sql_status);
    $resultado = pg_num_rows($res_status);

    if ($resultado > 0){
        $status_os          = trim(pg_fetch_result($res_status,0,status_os));
        $status_observacao  = trim(pg_fetch_result($res_status,0,observacao));
        $data_status        = trim(pg_fetch_result($res_status,0,data));
        $intervencao_admin  = trim(pg_fetch_result($res_status,0,login));
        $xobs_st = "";
        $xobs_st = substr($status_observacao,0,13);

        if (in_array($login_fabrica, [194])) {
            $status_observacao = utf8_decode($status_observacao);
        }

        if(($status_os == 73 && $login_fabrica == 3) || ($login_fabrica == 3 && $xobs_st != "Justificativa" && $status_os == 64)) {
            echo "<br />
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>Peça Autorizada Pela Fábrica</b><br>
                </div>
                </center><br />";
        } else if ($login_fabrica == 3 && $status_os == 64 && $xobs_st == "Justificativa") {
            echo "<br />
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>Peça Cancelada Pela Fábrica</b><br>
                </div>
                </center><br />";
        }        

        if ($status_os==88){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }
        if ($status_os==87){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
                    <b style='font-size:11px'>".traduz("a.peca.solicitada.necessita.de.autorizacao",$con,$cook_idioma).". ";
                if ($login_fabrica==1){
                    echo "<br>".traduz("entrar.em.contato.com.o.suporte.de.sua.regiao",$con,$cook_idioma)."</b>";
                } else {
                    echo "<br>".traduz("aguarde.a.fabrica.analisar.seu.pedido",$con,$cook_idioma)."</b>";
                }
            echo "</div>
                </center><br>
            ";
            if ($login_fabrica==1){
                echo "<script language='JavaScript'>alert('".traduz("os.em.intervencao.gentileza.entre.em.contato.com.o.suporte.de.sua.regiao",$con,$cook_idioma)."');</script>";
            }
        }
        if ($status_os==72 or $status_os==116){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
                    <b style='font-size:11px'>".traduz("a.peca.solicitada.necessita.de.autorizacao",$con,$cook_idioma).". <br>".traduz("aguarde.a.fabrica.analisar.seu.pedido",$con,$cook_idioma).".</b>
                </div>
                </center><br>
            ";
        }
        if ($status_os==73 or $status_os==117){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }
        /*
         * Adicionado para mostrar intervenções da Cortag (149)
         * Aprovação KM e Intervenção NS
         * Data: 24/11/2015
         *
         */
        if (in_array($status_os, array(98, 102))){
            if(in_array($login_fabrica, array(149))){
                echo "<br />
                    <center>
                    <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                        <b style='font-size:12px;color:red;width:100%'>".traduz("ordem.de.servico.sob.intervencao.do.fabricante")."<br /></b>
                        <b style='font-size:11px'>$status_observacao</b>
                    </div>
                    </center><br />
                ";
            }
        }
        if ($status_os==62){
            if($login_fabrica == 106){
                echo "<br>
                    <center>
                    <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                        <b style='font-size:12px;color:red;width:100%'>Ordem de Serviço sob intervenção do fabricante,<br>
                        favor aguardar a liberação para proceder com o reparo do produto, <br> qualquer dúvida favor entrar em contato pelo e-mail sac@houston.com.br ou pelo 0800 979 3434 </b><br>
                        <b style='font-size:11px'>$status_observacao</b>
                    </div>
                    </center><br>
                ";
            }
            if ($login_fabrica == 6){
                $sql = "SELECT  troca_obrigatoria,
                                    intervencao_tecnica,
                                    produto_critico
                            FROM    tbl_produto
                            WHERE   produto = $produto";
                $res = @pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {
                    $troca_obrigatoria   = trim(pg_fetch_result($res,0,troca_obrigatoria));
                }
                if ($troca_obrigatoria == 't'){
                    echo "<br>
                        <center>
                            <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                                <b style='font-size:14px;color:red;width:100%'>".traduz("os.sob.intervencao.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
                                <b style='font-size:11px'>Favor entrar em contato com a Fábrica para envio do produto</b>
                            </div>
                        </center><br>
                        ";
                }else{
                    echo "<br>
                        <center>
                        <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                            <b style='font-size:14px;color:red;width:100%'>".traduz("os.sob.intervencao.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
                            <b style='font-size:11px'>$status_observacao</b>
                        </div>
                        </center><br>
                    ";
                }

            }else{
                if($login_fabrica != 124 && $login_fabrica != 128 && $login_fabrica != 6 && $login_fabrica != 114 ){
                    echo "<br>
                        <center>
                        <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                            <b style='font-size:14px;color:red;width:100%'>".traduz("os.sob.intervencao.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
                            <b style='font-size:11px'>$status_observacao</b>
                        </div>
                        </center><br>
                    ";
                }else{
                    echo "<br />";
                }
            }
        }

        if ($status_os == 147) {
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("os.sob.intervencao.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }

        if ($status_os==118 or $status_os == 167){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("os.sob.intervencao.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }

        if ($login_fabrica == 3 && $status_os==64) {
            if ($xobs_st == "Justificativa") {
                echo "<br>
                    <center>
                    <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                        <b style='font-size:14px;color:red;width:100%'>".traduz("os.com.peça.cancelada.pela.fabrica",$con,$cook_idioma)."</b><br>
                        <b style='font-size:11px'>$status_observacao</b>
                    </div>
                    </center><br>
                ";
            } else {
                echo "<br>
                    <center>
                    <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                        <b style='font-size:14px;color:red;width:100%'>".traduz("os.liberada.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
                        <b style='font-size:11px'>$status_observacao</b>
                    </div>
                    </center><br>
                ";
            }
        } else {
            if ($status_os==64){
                echo "<br>
                    <center>
                    <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                        <b style='font-size:14px;color:red;width:100%'>".traduz("os.liberada.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
                        <b style='font-size:11px'>$status_observacao</b>
                    </div>
                    </center><br>
                ";
            }
        }


        if ($status_os==81){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("os.reprovada.da.intervencao.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }

        if ($status_os==168){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("os.sob.intervencao.de.custos.extras",$con,$cook_idioma)."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }

        if ($status_os==171){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("os.sob.intervencao.de.custos.adicionais",$con,$cook_idioma)."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }

        if ($status_os==172){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("os.liberada.da.intervencao.de.custos.adicionais",$con,$cook_idioma)."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }

        if ($status_os==173){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("os.reprovada.da.intervencao.de.custos.adicionais",$con,$cook_idioma)."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }

        if ($login_fabrica == 24) {
            $msg_bloqueio = "";
            
            if (verifica_msg_os_7_dias($os, 'Alerta Procon')) {
                $msg_bloqueio = "Alerta Procon";
            }

            if (!empty($msg_bloqueio)) {
            ?>
                <CENTER>
                    <TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
                        <TR>
                            <TD  bgcolor="#FFE1E1" height='20' colspan='2' style="text-align:center;">
                            <h1><?=traduz("ordem.de.servico.bloqueada:")." ".$msg_bloqueio?></h1>
                            </TD>
                        </TR>
                    </TABLE>
                </CENTER>
            <?php
            }
        }

        if($login_fabrica == 50 and $os_bloqueada == 't'){

            ?>
            <CENTER>
            <TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
            <TR>
                <TD  bgcolor="#FFE1E1" height='20' colspan='2' style="text-align:center;">
                <h1><?= traduz("ordem.de.servico.bloqueada") ?></h1>
                </TD>
            </TR>
            <tr>
                <td class='conteudo' >Observação:<?=utf8_decode($os_campos_adicionais['obs']) ?></td>
                <td class='conteudo' >Data: <?=mostra_data($os_campos_adicionais['data']) ?></td>
            </tr>
            </TABLE>
            </CENTER>
            <?php
        }

        if($login_fabrica == 157 && !empty($auditoria_reprovada)){ ?>
            <CENTER>
            <TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
            <TR>
                <TD  bgcolor="#FFE1E1" height='20' colspan='2' style="text-align:center;">
                <h1><?= traduz("ordem.de.servico.reprovada.em.auditoria") ?></h1>
                </TD>
            </TR>
            </TABLE>
            </CENTER>
        <?php
        }

        if (in_array($login_fabrica, array(50, 74))) {
            # HD 42933 - Alterei para a Colormaq, não estava mostrando a
            #   última intervenção na OS
            /*if ($status_os==98 or $status_os==99 or $status_os==100 or $status_os==101 or $status_os==102 or $status_os==103 or $status_os==104){*/

            $cond_reinc = "";

            if (false === $mostra_reincidencia) {
                $cond_reinc = "AND tbl_status_os.status_os <> 67";
            }
                $sql_status = #"select descricao from tbl_status_os where status_os = $status_os";
                            "SELECT
                                tbl_os_status.status_os,
                                tbl_os_status.observacao,
                                tbl_admin.login,
                                tbl_status_os.descricao,
                                to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
                            FROM tbl_os_status
                            JOIN tbl_status_os USING (status_os)
                            LEFT JOIN tbl_admin USING (admin)
                            WHERE os = $os
                            $cond_reinc
                            ORDER BY tbl_os_status.data DESC LIMIT 1";
                $res_status = pg_query($con, $sql_status );
                if(pg_num_rows($res_status)>0){
                    $data_status = pg_fetch_result($res_status, 0, data);;
                    $descricao_status = pg_fetch_result($res_status, 0, descricao);
                    $intervencao_admin = pg_fetch_result($res_status, 0, login);
                    $descricao_status = pg_fetch_result($res_status, 0, descricao);
                    $status_observacao = pg_fetch_result($res_status, 0, observacao);

                    echo "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
                            echo "<TR>";
                                echo "<TD class='inicio' background='imagens_admin/azul.gif' height='19px' colspan='4'>&nbsp;".traduz("status.os",$con,$cook_idioma )."</TD>";
                            echo "</TR>";
                            echo "<TR>";
                                echo "<TD class='inicio'>".traduz("data",$con,$cook_idioma )."</TD>";
                                echo "<TD class='inicio'>".traduz("admin",$con,$cook_idioma)."</TD>";
                                echo "<TD class='inicio'>".traduz("status",$con,$cook_idioma)."</TD>";
                                echo "<TD class='inicio'>".traduz("motivo",$con,$cook_idioma)."</TD>";
                            echo "</TR>";
                            echo "<TR>";
                                echo "<TD class='conteudo' width='10%'>&nbsp; $data_status </TD>";
                                echo "<TD class='conteudo'>&nbsp;$intervencao_admin </TD>";
                                echo "<TD class='conteudo'>&nbsp;$descricao_status </TD>";
                                echo "<TD class='conteudo'>&nbsp;$status_observacao </TD>";
                            echo "</TR>";
                    echo "</TABLE>";
                }
        #}
        } else if ($status_os == 179 && $login_fabrica == 91) {
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("os.com.intervencao.de.produto.critico")."</b><br>
                </div>
                </center><br>
            ";
        } else if ($status_os == 13 && $login_fabrica == 91) {
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>{$status_observacao}</b><br>
                </div>
                </center><br>
            ";
        }

    }
}
/*
 * Adicionado para mostrar intervenções da Cortag (149)
 * Peças Excedentes e Intervenção da Fábrica
 * Data: 24/11/2015
 *
 */
if(in_array($login_fabrica,array(138, 149))){
	$status_os = array();
	$sql_status = "SELECT status_os, observacao
		       FROM tbl_os_status
		       WHERE os = $os
			AND status_os in(62,64)
			ORDER BY status_os DESC LIMIT 1";
	$res_status = pg_query($con,$sql_status);
	if(pg_num_rows($res_status) > 0){
		$status_os[pg_fetch_result($res_status,0,'status_os')]  = pg_fetch_result($res_status,0,'observacao');

	}

	$sql_status = "SELECT status_os, observacao
		       FROM tbl_os_status
		       WHERE os = $os
			AND status_os in(118,185,187)
			ORDER BY status_os DESC LIMIT 1";
	$res_status = pg_query($con,$sql_status);
	if(pg_num_rows($res_status) > 0){
		$status_os[pg_fetch_result($res_status,0,'status_os')]  = pg_fetch_result($res_status,0,'observacao');

	}

	if(count($status_os) > 0){

		$status_auditoria = array(62,118);
		$keys = array_keys($status_os);

		foreach($keys as $value){
			if(in_array($value,$status_auditoria)){
				$em_auditoria = $value;
				break;
			}
		}

		if(!empty($em_auditoria)){
			echo "<br>
				<center>
					<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
						<b style='font-size:14px;color:red;width:100%'>".traduz("os.sob.intervencao.da.fabrica")."</b><br>
						<b style='font-size:11px'>$status_os[$em_auditoria]</b>
					</div>
				</center><br>
				";
		}else{

			echo "<br>
				<center>
					<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
						<b style='font-size:14px;color:red;width:100%'>".traduz("os.liberada.da.assistencia.tecnica.da.fabrica")."</b><br>
						<b style='font-size:11px'>".traduz("pedido.de.pecas.autorizado.pela.fabrica")."</b>
					</div>
				</center><br>
				";

		}
	}

}
//HD 2981957 - fputti
$tem_nota = false;
if ($login_fabrica == 91 AND strlen($extrato) > 0) {
    $sqlConf = "SELECT  tbl_extrato_lgr.qtde_nf
                    FROM tbl_extrato
                    JOIN tbl_extrato_lgr USING(extrato)
                    WHERE tbl_extrato.fabrica = $login_fabrica
                    AND tbl_extrato.posto = $login_posto
                    AND tbl_extrato_lgr.extrato = $extrato";
    $resConf = pg_query ($con,$sqlConf);
    if (pg_num_rows($resConf) > 0) {
        $qtdeNF = pg_fetch_result($resConf, 0, qtde_nf);

        if ($qtdeNF > 0) {
            $tem_nota = true;
        }
    }
}

if(strlen($extrato)>0 and strlen($liberado) > 0 and $login_fabrica != 91 AND !$tem_nota){ //HD 61132
   echo "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
        echo "<TR>";
            echo "<TD class='inicio'>".traduz("extrato",$con,$cook_idioma)."</TD>";
            echo "<TD class='inicio'>".traduz("previsao",$con,$cook_idioma)."</TD>";
            echo "<TD class='inicio'>".traduz("pagamento",$con,$cook_idioma)."</TD>";
        echo "</TR>";
        echo "<TR>";
            echo "<TD class='conteudo' width='33%'>&nbsp;<a href='os_extrato_detalhe.php?extrato=$extrato&posto=$login_posto' target='_blank'>$extrato_link</a> </TD>";
            echo "<TD class='conteudo' width='33%'>&nbsp;$data_pagamento </TD>";
            echo "<TD class='conteudo' width='33%'>&nbsp;$data_previsao </TD>";
        echo "</TR>";
    echo "</TABLE>";
}

if(strlen($extrato)>0 and strlen($liberado) > 0 and $login_fabrica == 91 AND $tem_nota){ //HD 2981957
   echo "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
        echo "<TR>";
            echo "<TD class='inicio'>".traduz("extrato",$con,$cook_idioma)."</TD>";
            echo "<TD class='inicio'>".traduz("previsao",$con,$cook_idioma)."</TD>";
            echo "<TD class='inicio'>".traduz("pagamento",$con,$cook_idioma)."</TD>";
        echo "</TR>";
        echo "<TR>";
            echo "<TD class='conteudo' width='33%'>&nbsp;<a href='os_extrato_detalhe.php?extrato=$extrato&posto=$login_posto' target='_blank'>$extrato_link</a> </TD>";
            echo "<TD class='conteudo' width='33%'>&nbsp;$data_pagamento </TD>";
            echo "<TD class='conteudo' width='33%'>&nbsp;$data_previsao </TD>";
        echo "</TR>";
    echo "</TABLE>";
}

if($login_fabrica ==50 AND strlen($os) > 0){ // HD 37276
    # HD 42933 - Retirado o resultado da tela, deixado apenas um link
    #   que abre um pop-up mostrando todo o histórico da OS
    /*$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
                  descricao,
                  observacao
            FROM tbl_os_status
            JOIN tbl_status_os using(status_os)
            WHERE os=$os
            AND status_os IN (98,99,100, 101,102,103,104,116,117)
            ORDER BY os_status desc
            limit 1";
    $res2=pg_query($con,$sql2);
    if(pg_num_rows($res2) > 0){*/
        echo "<TABLE width='700' border='0' align='center' cellspacing='0' cellpadding='0' class='Tabela'>";
        echo "<TR>";
        echo "<TD class='inicio' colspan='2' align='center'>";
        ?>
        <a style='cursor:pointer;' onclick="javascript:window.open('historico_os.php?os=<? echo $os ?>','mywindow','menubar=1,resizable=1,width=500,height=350')">&nbsp;<?php
        fecho("ver.historico.da.os",$con,$cook_idioma);?></a>
        <?php
        echo "</TD>";
        echo "</TR>";
        /*for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
            $data             = pg_fetch_result($res2,$i,data);
            $descricao_status = pg_fetch_result($res2,$i,descricao);
            $observacao_status = pg_fetch_result($res2,$i,observacao);
            echo "<TR>";
            echo "<TD class='conteudo' colspan='2' align='center'>$data - $descricao_status</TD>";
            echo "</tr>";
            echo "<TR>";
            echo "<TD class='conteudo2' colspan='2' align='center'>$observacao_status</TD>";
            echo "</TR>";
        }*/
        echo "</TABLE></center>";
    #}
}

////////////////////////////// OS RETORNO  - FABIO 10/01/2007  - INICIO //////////////////////////////
// informações de postagem para envio do produto para a Fábrica
// ADICIONADO POR FABIO 03/01/2007
// Dynacom - HD 12657

if ( in_array($login_fabrica, array(2,3,11,14,172)) ){
    $sql = "SELECT  nota_fiscal_envio,
                TO_CHAR(data_nf_envio,'DD/MM/YYYY')  AS data_nf_envio,
                numero_rastreamento_envio,
                TO_CHAR(envio_chegada,'DD/MM/YYYY')  AS envio_chegada,
                nota_fiscal_retorno,
                TO_CHAR(data_nf_retorno,'DD/MM/YYYY')  AS data_nf_retorno,
                numero_rastreamento_retorno,
                TO_CHAR(retorno_chegada,'DD/MM/YYYY')  AS retorno_chegada
            FROM tbl_os_retorno
            WHERE   os = $os;";
    $res = pg_query ($con,$sql);
    if (@pg_num_rows($res)==1){
        $retorno=1;
        $nota_fiscal_envio            = trim(pg_fetch_result($res,0,nota_fiscal_envio));
        $data_nf_envio                = trim(pg_fetch_result($res,0,data_nf_envio));
        $numero_rastreamento_envio    = trim(pg_fetch_result($res,0,numero_rastreamento_envio));
        $envio_chegada                = trim(pg_fetch_result($res,0,envio_chegada));
        $nota_fiscal_retorno          = trim(pg_fetch_result($res,0,nota_fiscal_retorno));
        $data_nf_retorno              = trim(pg_fetch_result($res,0,data_nf_retorno));
        $numero_rastreamento_retorno  = trim(pg_fetch_result($res,0,numero_rastreamento_retorno));
        $retorno_chegada              = trim(pg_fetch_result($res,0,retorno_chegada));
    } else{
        $retorno=0;
    }
}

if ($retorno==1 AND strlen($nota_fiscal_envio)==0){
    $sql_status = "SELECT status_os, observacao
                    FROM tbl_os_status
                    WHERE os=$os
                    AND status_os IN (72,73,62,64,65,87,88)
                    ORDER BY data DESC LIMIT 1";
    $res_status = pg_query($con,$sql_status);
    $resultado = pg_num_rows($res_status);
    if ($resultado==1){
        $status_os          = trim(pg_fetch_result($res_status,0,status_os));
        $status_observacao  = trim(pg_fetch_result($res_status,0,observacao));
        if ($status_os==65){
            if ($login_fabrica==3){
                echo "<br>
                    <center>
                    <b style='font-size:'15px''>".traduz("este.produto.deve.ser.enviado.para.a.assistencia.tecnica.da.fabrica",$con,$cook_idioma).".</b><br>
                    <div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:400px;align:center' align='center'>
                        <b style='font-size:14px;color:red'>".strtoupper(traduz("urgente.produto.para.reparo",$con,$cook_idioma))."</b><br><br>
                        <b style='font-size:14px'>BRITÂNIA ELETRODOMÉSTICOS LTDA</b>.<br>
                        <b style='font-size:12px'>Rua Dona Francisca, 8300 Mod 4 e 5 Bloco A<br>
                        Cep 89.239-270 - Joinville - SC<br>
                        A/C ASSISTÊNCIA TÉCNICA</b>
                    </div></center><br>
                ";
            } else {
                echo "<br>
                    <center>
                    <b style='font-size:'15px''>".traduz("este.produto.deve.ser.enviado.para.a.assistencia.tecnica.da.fabrica",$con,$cook_idioma).".</b><br></center><br>
                ";
            }
        }
        if ($status_os==72){
            echo "<br>
                <center>
                <b style='font-size:'15px''>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
                <div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:400px;align:center' align='center'>
                    <b style='font-size:12px'>".traduz("a.peca.solicitada.necessita.de.autorizacao",$con,$cook_idioma).". <br>".traduz("aguarde.a.fabrica.analisar.seu.pedido",$con,$cook_idioma).".</b>
                </div></center><br>
            ";
        }
        if ($status_os==73){
            echo "<br>
                <center>
                <b style='font-size:'15px''>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
                <div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:400px;align:center' align='center'>
                    <b style='font-size:12px'>$status_observacao</b>
                </div></center><br>
            ";
        }
    }
}

if($auditoria_unica == true || in_array($login_fabrica, array(1,30,123))){
    // $sqlAuditoria = "SELECT tbl_auditoria_os.liberada,
    //         tbl_auditoria_os.cancelada,
    //         tbl_auditoria_os.reprovada
    //     FROM tbl_auditoria_os
    //         JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = $login_fabrica
    //         JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
    //     WHERE tbl_auditoria_os.os = $os
    //     AND tbl_auditoria_os.liberada IS NULL
    //     AND tbl_auditoria_os.cancelada IS NULL
    //     AND tbl_auditoria_os.reprovada IS NULL
    //     ORDER BY data_input DESC";
    // $resAuditoria = pg_query($con,$sqlAuditoria);

    $sqlAuditoria = "
        SELECT tbl_auditoria_status.descricao,
            tbl_auditoria_os.observacao,
            tbl_auditoria_os.auditoria_os,
            to_char(tbl_auditoria_os.data_input,'DD/MM/YYYY') AS data_input,
            $coluna_liberada_aud
            to_char(tbl_auditoria_os.cancelada,'DD/MM/YYYY') AS cancelada,
            to_char(tbl_auditoria_os.reprovada,'DD/MM/YYYY') AS reprovada,
            tbl_auditoria_os.justificativa,
            NULL AS os_revenda,
            tbl_auditoria_os.admin
        FROM tbl_auditoria_os
        JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = $login_fabrica
        JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
        WHERE tbl_auditoria_os.os = $os
        AND tbl_auditoria_os.liberada IS NULL
        AND tbl_auditoria_os.cancelada IS NULL
        AND tbl_auditoria_os.reprovada IS NULL
        UNION
        SELECT tbl_auditoria_status.descricao,
            tbl_auditoria_os_revenda.observacao,
            tbl_auditoria_os_revenda.auditoria_os,
            to_char(tbl_auditoria_os_revenda.data_input,'DD/MM/YYYY') AS data_input,
            $coluna_liberada_aud_revenda
            to_char(tbl_auditoria_os_revenda.cancelada,'DD/MM/YYYY') AS cancelada,
            to_char(tbl_auditoria_os_revenda.reprovada,'DD/MM/YYYY') AS reprovada,
            tbl_auditoria_os_revenda.justificativa,
            tbl_os_campo_extra.os_revenda,
            tbl_auditoria_os_revenda.admin
        FROM tbl_auditoria_os_revenda
        JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os_revenda.auditoria_status
        JOIN tbl_os ON tbl_os.os = $os
        JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
        WHERE tbl_auditoria_os_revenda.os_revenda = tbl_os_campo_extra.os_revenda
        AND tbl_auditoria_os_revenda.liberada IS NULL
        AND tbl_auditoria_os_revenda.cancelada IS NULL
        AND tbl_auditoria_os_revenda.reprovada IS NULL
        ORDER BY data_input DESC ";
    $resAuditoria = pg_query($con,$sqlAuditoria);

    if(pg_num_rows($resAuditoria) > 0){

        $info_auditoria = (in_array($login_fabrica, [35])) ? "Essa OS está em Auditoria e será analisada. <br /> Aguarde retorno com aprovação ou reprovação." : "Essa OS está em Auditoria.";
        if ($login_fabrica == 183) {
             $observacao = pg_fetch_result($resAuditoria, 0, "observacao");
             if ($observacao == "OS em Auditoria de Número de Série bloqueado") {
                $info_auditoria = "";
             }
        }
        
    ?>
        <center>
            <div style='font-family:verdana;width:700px;align:center;background-color:#FF0000' align='center'>
                
                <?php 
                if ($login_fabrica == 178){
                    $osRevenda = pg_fetch_result($resAuditoria, 0, "os_revenda");
                    if (empty($osRevenda)){
                        echo "<b style='font-size:14px;color:white'>Essa OS está em Auditoria.</b>";
                    }else {
                        echo "<b style='font-size:14px;color:white'>OS PRINCIPAL está em Auditoria.</b>";
                    }
				}else{
                    echo "<b style='font-size:14px;color:white'>$info_auditoria</b>";
                }
				?>
		</div>
        </center>
        <br />
    <?php
    }
}

if(in_array($login_fabrica, array(35,175)) ) {

    $sql = "SELECT osr.os, osr.sua_os FROM tbl_os_extra INNER JOIN tbl_os AS osr ON osr.os = tbl_os_extra.os_reincidente WHERE tbl_os_extra.os = {$os}";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $osr        = pg_fetch_result($res, 0, "os");
        $osr_sua_os = pg_fetch_result($res, 0, "sua_os");

        echo "<div style='font-family:verdana;font-weight:bold;width:700px;align:center;background-color:#FF0000;color:white' align='center'>
                ".traduz("ordem.de.servico.reincidente.de").": <a style='font-size:14px;color:white' href='os_press.php?os={$osr}' target='_blank'>{$osr_sua_os}</a>
            </div>";
    }
}

if ($reparoNaFabrica and !$posto_interno) {

    $sql = "SELECT recolhimento FROM tbl_os_extra WHERE os = {$os}";
    $resRec = pg_query($con,$sql);
    if(pg_num_rows($resRec) > 0){
        $aux_reparo_produto = pg_fetch_result($resRec,0,"recolhimento");

        if ($login_fabrica == 156) {
            $sql = "SELECT finalizada FROM tbl_os WHERE os = {$os_numero};";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $osPostoInternoFinalizada = pg_fetch_result($res, 0, finalizada);
            }
        }

        if ($aux_reparo_produto == "t") {
            $sql_os_ext = "SELECT os_numero FROM tbl_os WHERE os = $os";
            $qry_os_ext = pg_query($con, $sql_os_ext);

            $os_externa = pg_fetch_result($qry_os_ext, 0, 'os_numero');
            ?>
            <center>
                <div style='font-family:verdana;width:700px;align:center;background-color:<?= (!empty($osPostoInternoFinalizada) ? "#1C5F1C" : "#FF4500"); ?>' align='center'>
                    <b style='font-size:14px;color:white'><?= (!empty($osPostoInternoFinalizada) ? traduz("os.reparada.na.fabrica") : traduz("essa.os.sera.reparada.na.fabrica")); ?></b>
                </div>
            </center>
        <? }
    }
}

if ($retorno == 1 AND strlen($msg_erro) > 0) {
    if (strpos($msg_erro,'date')){
        //$msg_erro = "Data de envio incorreto!";
    }
    echo "<center>
            <div style='font-family:verdana;width:400px;align:center;background-color:#FF0000' align='center'>
                <b style='font-size:14px;color:white'>".strtoupper(traduz("erro",$con,$cook_idioma))."<br>$msg_erro </b>
            </div></center>";
} else {
    if (strlen($msg)>0){
        echo "<center>
            <div style='font-family:verdana;width:400px;align:center;' align='center'>
                <b style='font-size:14px;color:black'>".strtoupper(traduz("erro",$con,$cook_idioma))."<br>$msg</b>
            </div></center>";
    }
}
if (strlen($msg_erro)>0 && $retorno != 1){
    if (in_array($login_fabrica, array(169,170)) && $_POST["fechar_os"]) {
        $titulo_msg_erro = traduz("ERRO AO FECHAR ORDEM DE SERVIÇO", $con, $cook_idioma);;
    } else {
        $titulo_msg_erro = traduz("ERRO", $con, $cook_idioma);
    }
    echo "
        <center>
            <br />
            <div style='font-family:verdana;width:700px;align:center;background-color:#FF0000' align='center'>
                <b style='font-size:14px;color:white'>".strtoupper($titulo_msg_erro)."<br>$msg_erro</b>
            </div>
            <br />
        </center>
    ";
}

if (in_array($login_fabrica, array(169,170)) && !empty($msg_sucesso)) {
    echo "
        <center>
            <br />
            <div style='font-family:verdana;width:700px;align:center;background-color:#008800' align='center'>
                <b style='font-size:14px;color:white'>{$msg_sucesso}</b>
            </div>
            <br />
        </center>
    ";
}

if ($retorno==1 AND !$nota_fiscal_envio AND !$data_nf_envio AND (!$numero_rastreamento_envio OR $login_fabrica==6 OR $login_fabrica == 14)) {
?>
<br>
<form name="frm_consulta" method="post" action="<?echo "$PHP_SELF?os=$os"?>">
    <TABLE width='400' border="1" cellspacing="2" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
            <TR>
                <TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px'> &nbsp;<? echo traduz("envio.do.produto.a.fabrica",$con,$cook_idioma);?></TD>
            </TR>
            <TR>
                <TD class="subtitulo" height='19px'><? echo strtoupper(traduz("preencha.os.dados.do.envio.do.produto.a.fabrica",$con,$cook_idioma));?></TD>
            </TR>
            <TR>
                <TD class="titulo3"><br>
                <? echo traduz("numero.da.nota.fiscal",$con,$cook_idioma);?>&nbsp;<input class="inpu" type="text" name="txt_nota_fiscal" size="25" maxlength="6" value="<? echo     $nota_fiscal_envio_p ?>">
                <br>
                <? echo  traduz("data.da.nota.fiscal.do.envio",$con,$cook_idioma);?> &nbsp;<input class="inpu" type="text" name="txt_data_envio" size="25" maxlength="10" value="<? echo $data_envio_p ?>">
                <br>

                <?  if ($login_fabrica <> 6){ ?>
                    <? echo traduz("numero.o.objeto.pac",$con,$cook_idioma);?> &nbsp;<input class="inpu" type="text" name="txt_rastreio" size="25" maxlength="13" value="<? echo $numero_rastreio_p ?>"> <br>
                    Ex.: SS987654321
                    <br>
                <? } ?>

                <center><input type="hidden" name="btn_acao" value="">
                <img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_consulta.btn_acao.value == '' ) { document.frm_consulta.btn_acao.value='gravar' ; document.frm_consulta.submit() } else { alert ('<?fecho ("aguarde.submissao",$con,$cook_idioma);?>') }" ALT="<?fecho("gravar.dados",$con,$cook_idioma);?>" id='botao_gravar' border='0' style="cursor:pointer;"></center><br>
                </TD>
            </TR>
    </TABLE>
</form><br><br>
<?
}

/***************************************************************************************************/
if ($login_fabrica==51){ //HD 48003
    $sql_status = "SELECT
                status_os,
                observacao,
                tbl_admin.login,
                to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
                FROM tbl_os_status
                LEFT JOIN tbl_admin USING(admin)
                WHERE os=$os
                AND status_os IN (72,73,62,64,65,87,88,98,99,100,101,102,103,104,116,117,128)
                ORDER BY tbl_os_status.data DESC LIMIT 1";

    $res_status = pg_query($con,$sql_status);
    $resultado = pg_num_rows($res_status);
    if ($resultado==1){
        $data_status        = trim(pg_fetch_result($res_status,0,data));
        $status_os          = trim(pg_fetch_result($res_status,0,status_os));
        $status_observacao  = trim(pg_fetch_result($res_status,0,observacao));
        $intervencao_admin  = trim(pg_fetch_result($res_status,0,login));

        if (strlen($intervencao_admin)>0 AND $login_fabrica<>50){
            $intervencao_admin = "<br><b>".traduz("os.em.intervencao.colocada.pela.fabrica")." ($intervencao_admin)</b>";
        }

        if ($status_os==72 or $status_os==116) {
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao")."</b><br>
                    <b style='font-size:11px'>".traduz("a.peca.solicitada.necessita.de.autorizacao").". <br>".traduz("a.peca.solicitada.necessita.de.autorizacao.o.pa.aguarda.a.fabrica.analisar.o.pedido")."</b>
                    $intervencao_admin
                </div>
                </center><br>
            ";
        }
        if ($status_os==73 or $status_os==117) {
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao")."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }
        if ($status_os==62){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("os.sob.intervencao.da.assistencia.tecnica.da.fabrica")."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                    <br>entrar em contato com a GAMA ITALY pelo telefone (11) 2940-7400
                </div>
                </center><br>";
        }
        if ($status_os==64){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("os.liberada.da.assistencia.tecnica.da.fabrica")."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }
        if ($status_os==88){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao")."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
        }
        if ($status_os==87){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao")."</b><br>
                    <b style='font-size:11px'>".traduz("a.peca.solicitada.necessita.de.autorizacao");
                if ($login_fabrica==1){
                    echo "<br>".traduz("entrar.em.contato.com.o.suporte.de.sua.regiao")."</b>";
                } else {
                    echo "<br>".traduz("aguarde.a.fabrica.analisar.seu.pedido")."</b>";
                }
            echo "</div>
                </center><br>
            ";
        }

    }
}
/**********************************************************************************************/

#HD 44202 - intervenção OS aberta
if (strlen($status_os_aberta)>0 AND ($login_fabrica==3 or $login_fabrica==14) ) {
    $status_os_aberta_inter= "";
    if ($status_os_aberta == 122 ||$status_os_aberta == 141) {
        $status_os_aberta_inter = "<br><b style='font-size:11px'>". traduz("os.com.intervencao.da.fabrica.aguardando.liberacao",$con,$cook_idioma). "</b>";
    }
    echo "<br>
        <center>
        <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
            <b style='font-size:14px;color:red;width:100%'>".traduz("status.os",$con,$cook_idioma)."</b>
             $status_os_aberta_inter <br>
            <b style='font-size:11px'>$status_os_aberta_obs </b>
        </div>
        </center><br>";

    if ($status_os_aberta == 120 || $status_os_aberta == 140) {
    ?>
    <form name="frm_os_aberta" method="post" action="<?echo "$PHP_SELF?os=$os"?>">
        <TABLE width='400' border="1" cellspacing="2" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
            <TR>
                <TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px'> &nbsp;<? echo traduz("os.em.intervencao",$con,$cook_idioma);?></TD>
            </TR>
            <TR>
                <TD class="subtitulo" height='19px'><? echo traduz("digite.a.justificativa",$con,$cook_idioma).":";?></TD>
            </TR>
            <TR>
                <TD class="titulo3"><br>
                <? echo traduz("justificativa",$con,$cook_idioma);?>&nbsp;<input class="inpu" type="text" name="txt_justificativa_os_aberta" size="60" maxlength="60" value="">
                <br>
                <center><input type="hidden" name="btn_acao" value="">
                <img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os_aberta.btn_acao.value == '' ) { document.frm_os_aberta.btn_acao.value='gravar_justificativa' ; document.frm_os_aberta.submit() } else { alert ('<?fecho ("aguarde.submissao",$con,$cook_idioma);?>') }" ALT="<?fecho("gravar.dados",$con,$cook_idioma);?>" id='botao_gravar_justificativa' border='0' style="cursor:pointer;"></center><br>
                </TD>
            </TR>
        </TABLE>
    </form>
    <?
    }
}

if ($retorno==1 AND $nota_fiscal_envio AND $data_nf_envio AND ($numero_rastreamento_envio OR $login_fabrica==6 or $login_fabrica == 14)) {
    if (strlen($envio_chegada)==0){
        echo "<BR><b style='font-size:14px;color:#990033'>".traduz("o.produto.foi.enviado.mas.a.fabrica.ainda.nao.confirmou.seu.recebimento",$con,$cook_idioma).".<br> .".traduz("aguarde.a.fabrica.confirmar.o.recebimento.efetuar.o.reparo.e.retornar.o.produto.ao.seu.posto",$con,$cook_idioma).".</b><BR>";
    }else {
        if (strlen($data_nf_retorno)==0){
            echo "<BR><b style='font-size:14px;color:#990033'>".traduz("o.produto.foi.recebido.pela.fabrica.em.%",$con,$cook_idioma,array($envio_chegada))."<br> ".traduz("aguarde.a.fabrica.efetuar.o.reparo.e.enviar.ao.seu.posto",$con,$cook_idioma).".</b><BR>";
        }
        else{
            if (strlen($retorno_chegada)==0){
                echo "<BR><b style='font-size:14px;color:#990033'>".traduz("o.reparo.do.produto.foi.feito.pela.fabrica.e.foi.enviado.ao.seu.posto.em.%",$con,$cook_idioma,array($data_nf_retorno))."<br>".traduz("confirme.apos.o.recebimento",$con,$cook_idioma)."</b><BR>";
            }
            else {
                #echo "<BR><b style='font-size:14px;color:#990033'>O REPARO DO PRODUTO FOI FEITO PELA FÁBRICA.</b><BR>";
            }
        }
    }

    if ($nota_fiscal_retorno AND $retorno_chegada=="") {
?>
    <form name="frm_confirm" method="post" action="<?echo "$PHP_SELF?os=$os&chegada=$os"?>">
        <TABLE width='420' border="1" cellspacing="0" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
                <TR>
                    <TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px'> <?echo traduz("confirme.a.data.do.recebimento",$con,$cook_idioma);?></TD>
                </TR>
            <TR>
                <TD class="subtitulo" height='19px' colspan='2'><?echo traduz("o.produto.foi.enviado.para.seu.posto.confirme.seu.recebimento",$con,$cook_idioma);?></TD>
            </TR>
                    <TD class="titulo3"><br>
                    <?echo traduz("data.da.chegada.do.produto",$con,$cook_idioma);?>&nbsp;<input class="inpu" id='dataChegada' type="text" name="txt_data_chegada_posto" size="20" maxlength="10" value=""> <br><br>
                    <center>
                    <input type="hidden" name="btn_acao" value="">
                    <img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_confirm.btn_acao.value == '' ) { document.frm_confirm.btn_acao.value='confirmar' ; document.frm_confirm.submit() } else { alert ('<?fecho ("aguarde.submissao",$con,$cook_idioma);?>') }" ALT="<?fecho("gravar.dados",$con,$cook_idioma);?>" id='botao_gravar' border='0' style="cursor:pointer;"></center><br>
                    </TD>
                </TR>
        </TABLE>
    </form>
    <?}?>

    <br>
    <TABLE width='420' border="1" cellspacing="0" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
            <TR>
                <TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px' colspan='2'> &nbsp;<?echo traduz("envio.do.produto.a.fabrica",$con,$cook_idioma);?></TD>
            </TR>
            <TR>
                <TD class="subtitulo" height='19px' colspan='2'><?echo traduz("informacoes.do.envio.do.produto.a.fabrica",$con,$cook_idioma);?></TD>
            </TR>
            <TR>
                <TD class="titulo3"><?echo traduz("numero.da.nota.fiscal.de.envio",$con,$cook_idioma);?> </TD>
                <TD class="conteudo" >&nbsp;<? echo $nota_fiscal_envio ?></TD>
            </TR>
            <TR>
                <TD class="titulo3"><?echo traduz("data.da.nota.fiscal.do.envio",$con,$cook_idioma);?> </TD>
                <TD class="conteudo" >&nbsp;<? echo $data_nf_envio ?></TD>
            </TR>
            <?  if ($login_fabrica <> 6){ ?>
            <TR>
                <TD class="titulo3"><?echo traduz("numero.o.objeto.pac",$con,$cook_idioma);?> </TD>
                <TD class="conteudo" >&nbsp;<? echo "<a href='#' class='correios' rel='$numero_rastreamento_envio'>$numero_rastreamento_envio</a>" ?></TD>
            </TR>
            <? } ?>
            <TR>
                <TD class="titulo3"><?echo traduz("data.da.chegada.a.fabrica",$con,$cook_idioma);?> </TD>
                <TD class="conteudo" >&nbsp;<? echo $envio_chegada; ?></TD>
            </TR>
            <TR>
                <TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px' colspan='2'> &nbsp;<?echo traduz("retorno.do.produto.da.fabrica.ao.posto",$con,$cook_idioma);?></TD>
            </TR>
            <TR>
                <TD class="subtitulo" height='19px' colspan='2'><?echo traduz("informacoes.do.retorno.do.produto.ao.posto",$con,$cook_idioma);?></TD>
            </TR>
            <TR>
                <TD class="titulo3"><?echo traduz("numero.da.nota.fiscal.do.retorno",$con,$cook_idioma);?> </TD>
                <TD class="conteudo" >&nbsp;<? echo $nota_fiscal_retorno ?></TD>
            </TR>
            <TR>
                <TD class="titulo3"><?echo traduz("data.do.retorno",$con,$cook_idioma);?> </TD>
                <TD class="conteudo" >&nbsp;<? echo $data_nf_retorno ?></TD>
            </TR>
            <?  if ($login_fabrica <> 6){ ?>
            <TR>
                <TD class="titulo3"><?echo traduz("numero.o.objeto.pac.de.retorno",$con,$cook_idioma);?> </TD>
                <TD class="conteudo" >&nbsp;<? echo ($numero_rastreamento_retorno)?"<a href='#' class='correios' rel='$numero_rastreamento_retorno'>$numero_rastreamento_retorno</a>":""; ?></TD>
            </TR>
            <? } ?>
            <TR>
                <TD class="titulo3" ><?echo traduz("data.da.chegada.ao.posto",$con,$cook_idioma);?></TD>
                <TD class="conteudo" >&nbsp;<? echo $retorno_chegada ?></TD>
            </TR>
    </TABLE>
<br><br>
<?
}

//////////////// OS RETORNO - FABIO 10/01/2007  - FIM  ///////////////////////////////////

##########################################################################################
####################### INFORMÇÕES DE TROCA LENOXX HD 20774 04/06/2008 ###################
##########################################################################################

if( in_array($login_fabrica,array(3,11,91,172)) ){//HD 69245
    if ($ressarcimento <> "t") {
        if ($troca_garantia == "t") {
            echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
            echo "<TR height='30'>";
            echo "<TD align='left' colspan='4'>";
            echo "<font family='arial' size='2' color='#ffffff'><b>";
            echo "&nbsp;".traduz("produto.trocado",$con,$cook_idioma);
            echo "</b></font>";
            echo "</TD>";
            echo "</TR>";

            echo "<tr>";
            if($login_fabrica<>3) echo "<TD align='left' class='titulo4'  height='15' >".traduz("responsavel",$con,$cook_idioma)."</TD>";
            echo "<TD align='left' class='titulo4'  height='15' >".traduz("data",$con,$cook_idioma)."</TD>";
            echo "<TD align='left' class='titulo4'  height='15' colspan='2'>".traduz("trocado.por",$con,$cook_idioma)."</TD>";
        #   echo "<TD class='titulo'  height='15' >&nbsp;</TD>";
            echo "</tr>";
            $sql = "SELECT TO_CHAR(data,'dd/mm/yyyy hh:mm') AS data            ,
                            setor                                              ,
                            situacao_atendimento                               ,
                            tbl_os_troca.observacao                            ,
                            tbl_peca.referencia             AS peca_referencia ,
                            tbl_peca.descricao              AS peca_descricao  ,
                            tbl_causa_troca.descricao       AS causa
                    FROM tbl_os_troca
                    JOIN tbl_peca        USING(peca)
                    JOIN tbl_causa_troca USING(causa_troca)
                    JOIN tbl_os          ON tbl_os_troca.os = tbl_os.os
                    WHERE tbl_os_troca.os = $os
                    AND  tbl_os.fabrica = $login_fabrica; ";
            $resX = pg_query ($con,$sql);
            if (pg_num_rows ($resX) > 0) {
                $troca_data           = pg_fetch_result ($resX,0,data);
                $troca_setor          = pg_fetch_result ($resX,0,setor);
                $troca_situacao       = pg_fetch_result ($resX,0,situacao_atendimento);
                $troca_observacao     = pg_fetch_result ($resX,0,observacao);
                $troca_peca_ref       = pg_fetch_result ($resX,0,peca_referencia);
                $troca_peca_des       = pg_fetch_result ($resX,0,peca_descricao);
                $troca_causa          = pg_fetch_result ($resX,0,causa);

                if($troca_situacao == 0) $troca_situacao = "Garantia";
                else                     $troca_situacao .= "% Faturado";

                echo "<tr>";
                if($login_fabrica<>3){
                    echo "<TD class='conteudo' align='left' height='15' nowrap>";
                    echo "&nbsp;&nbsp;&nbsp;";
                    echo $troca_admin;
                    echo "&nbsp;&nbsp;&nbsp;";
                    echo "</td>";
                }

                echo "<TD class='conteudo' align='left' height='15' nowrap>";
                echo "&nbsp;&nbsp;&nbsp;";
                echo $troca_data;
                echo "&nbsp;&nbsp;&nbsp;";
                echo "</td>";
                echo "<TD colspan='2' class='conteudo' align='left' height='15' nowrap >";
                echo $troca_peca_ref . " - " . $troca_peca_des;
                echo "</td>";
                echo "</tr>";
                if($login_fabrica<>3){
                    echo "<tr>";
                    echo "<TD align='left' class='titulo4'  height='15' >".traduz("setor",$con,$cook_idioma)."</TD>";
                    echo "<TD align='left' class='titulo4'  height='15' >".traduz("situacao.do.atendimento",$con,$cook_idioma)."</TD>";
                    if( in_array($login_fabrica, array(11,172)) ) {
                        echo "<TD align='left' class='titulo4'  height='15' colspan='2'>".traduz("causa",$con,$cook_idioma)."</TD>";
                    } else {
                        echo "<TD align='left' class='titulo4'  height='15' colspan='2'>".traduz("causa.da.troca",$con,$cook_idioma)."</TD>";
                    }
                    echo "</tr>";
                    echo "<tr>";
                    echo "<TD class='conteudo' align='left' height='15' nowrap>";
                    echo "&nbsp;&nbsp;&nbsp;";
                    echo $troca_setor;
                    echo "&nbsp;&nbsp;&nbsp;";
                    echo "</td>";
                    echo "<TD class='conteudo' align='left' height='15' nowrap>";
                    echo "&nbsp;&nbsp;&nbsp;";
                    echo $troca_situacao;
                    echo "&nbsp;&nbsp;&nbsp;";
                    echo "<TD class='conteudo' align='left' height='15' nowrap colspan='2'>";
                    echo $troca_causa;
                    echo "</td>";
                    echo "</tr>";

                    echo "<tr>";
                    echo "<TD class='conteudo' align='left' height='15'  colspan='4'><b>OBS:</b>";
                    echo $troca_observacao;
                    echo "</td>";
                    echo "</tr>";
            #       echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
                    echo "</tr>";
                }

            }else if($login_fabrica<>3) {
                $sql = "SELECT tbl_peca.referencia , tbl_peca.descricao
                        FROM tbl_peca
                        JOIN tbl_os_item USING (peca)
                        JOIN tbl_os_produto USING (os_produto)
                        JOIN tbl_os_extra USING (os)
                        WHERE tbl_os_produto.os = $os
                        AND   tbl_peca.produto_acabado IS TRUE ";
                $resX = pg_query ($con,$sql);
                if (pg_num_rows ($resX) > 0) {
                    $troca_por_referencia = pg_fetch_result ($resX,0,referencia);
                    $troca_por_descricao  = pg_fetch_result ($resX,0,descricao);
                }


                echo "<tr>";
                echo "<TD class='conteudo' align='left' height='15' nowrap>";
                echo "&nbsp;&nbsp;&nbsp;";
                echo $troca_admin;
                echo "&nbsp;&nbsp;&nbsp;";
                echo "</td>";
                echo "<TD class='conteudo' align='left' height='15' nowrap>";
                echo "&nbsp;&nbsp;&nbsp;";
                echo $data_fechamento;
                echo "&nbsp;&nbsp;&nbsp;";
                echo "</td>";
                echo "<TD class='conteudo' align='left' height='15' nowrap >";
                echo $troca_por_referencia . " - " . $troca_por_descricao;
                echo "</td>";

        #       echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
                echo "</tr>";
            }
        }
    }

    if( in_array($login_fabrica, array(11,172)) ){

        $sql_os_troca = "SELECT os FROM tbl_os_troca WHERE fabric = $login_fabrica AND os = $os";
        $res_os_troca = pg_query($con, $sql_os_troca);

        if(pg_num_rows($res_os_troca) > 0){

            echo "
            <table width=700px align=center cellpadding=0 cellspacing=0 style='margin-top: 20px;'>
                <tr>
                    <td style='color: #000; font: 12px verdana; text-align: center; padding: 15px; border: 1px solid #D3BE96; width: 700px; align: center; background-color: #FCF0D8;'>
                        <strong style='color: #ff0000; font-size: 14px;'>".traduz("o.produto.desta.o.s.sera.negociado")."</strong> <br />
                        ".traduz("favor.orientar.o.consumidor.a.contatar.o.nosso.sac")." - fones: 0800 772 9209 ou (11) 3339 9955
                    </td>
                </tr>
            </table>
            <br />";

        }

    }

}
########################### INFORMÇÕES DE TROCA LENOXX - FIM #############################

?>



<?
// Mostra número do Extrato que esta OS's está - A pedido da Edina
// Fabio
// 29/12/2006
if ($login_fabrica==2){
    if (strlen(trim($data_finalizada))>0){

        $specialChar = array("(",")",".");
        $os = str_replace($specialChar, "", $os);

        $query = "SELECT extrato,
                    to_char(data_pagamento,'DD/MM/YYYY')  AS data_pagamento,
                        data_vencimento
                FROM tbl_os
                JOIN tbl_os_extra using(os)
                JOIN tbl_extrato using(extrato)
                LEFT JOIN tbl_extrato_pagamento using(extrato)
                WHERE tbl_os.os = $os
                AND tbl_os.fabrica = 2;";
        $result = pg_query ($con,$query);
        if (pg_num_rows ($result) > 0) {
            $extrato = pg_fetch_result ($result,0,extrato);
            $data_pg = pg_fetch_result ($result,0,data_pagamento);
            $data_vcto = pg_fetch_result ($result,0,data_vencimento);
            ?>
            <TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
                    <TR ><TD class='inicio' style='text-align:center;'  colspan='4'><?echo traduz("extrato",$con,$cook_idioma);?></td></tr>
                    <tr>
                        <TD class='titulo' style='padding:0px 5px;' width='120' ><?echo traduz("n.extrato",$con,$cook_idioma);?></td>
                        <td    class='conteudo' style='padding:0px 5px;' width='226' >
                            <a href='os_extrato_detalhe.php?extrato=<? echo $extrato; ?>&posto=<? echo $login_posto; ?>' ><? echo $extrato; ?></a>
                        </td>
                        <td class='titulo' style='padding:0px 5px;' width='120' ><? echo traduz("data.do.pagamento",$con,$cook_idioma);?></td>
                        <td class='conteudo' style='padding:0px 5px;' width='226' > &nbsp;<b><? echo $data_pg; ?></b>
                        </TD>
                    </TR>
            </TABLE><br>
            <?
        }
    }
}// fim mostra número do Extrato

if ($login_fabrica == 3 AND $login_e_distribuidor == "t"){
?>
    <center>
    <TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
            <TR>
                <TD class="titulo" colspan="4"><?echo traduz("posto",$con,$cook_idioma);?></TD>
            </TR>
            <TR>
                <TD class="conteudo" colspan="4"><? echo "$posto_codigo - $posto_nome"; ?></TD>
            </TR>
    </TABLE>
    </center>
<?
}
if($login_fabrica == 96 and !empty($motivo)){
    $linhas = 5;
}
else{
    $linhas = 3;
}
$td_os_rowspan = $linhas + usaDataConserto($login_posto, $login_fabrica);


//hd-744257
if($login_fabrica == 80){

    $sql = "SELECT os, status_os, observacao, extrato FROM tbl_os_status WHERE os = $os AND status_os = 13 AND extrato notnull;";

    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){
        $os         = pg_fetch_result($res, 0, os);
        $status_os  = pg_fetch_result($res, 0, status_os);
        $observacao = pg_fetch_result($res, 0, observacao);
        $extrato    = pg_fetch_result($res, 0, extrato);

        echo "<table align='center' width='700' cellspacing='1' class='tabela'>";
            echo "<tr class='titulo_coluna'>";
                echo "<td>";
                    echo traduz("ordem.de.servico.recusada.do.extrato").": ".$extrato;
                echo "</td>";
            echo "</tr>";
            echo "<tr>";
                echo "<td>";
                    echo "<b>".traduz("motivo.da.recusa").": <font color='red'>".$observacao."</font></b>";
                echo "</td>";
            echo "</tr>";
        echo "</table>";

    }
}

//if ($login_fabrica == 30) {
if (in_array($login_fabrica, [30,35,72,175,203])) { ?>
<table width='700' border="0" cellspacing="1" cellpadding="0" align='center'>
    <tr>
        <td style="text-align:right;">
            <button id="abreHelp"><?= traduz("abrir.atendimento.help.desk") ?></button>
            &nbsp;&nbsp;&nbsp;
            <button id="histHelp"><?= traduz("historico.help.desk") ?></button>
        </td>
    </tr>
</table>
<?php
}
if ($login_fabrica == 162 && $login_unico_master == 't') {
    $qry_tec = pg_query($con, "SELECT tecnico, nome FROM tbl_tecnico WHERE fabrica = $login_fabrica order by nome");
    $res_tec = pg_query($con,$sql_tec);
?>
<table width='700' border="0" cellspacing="1" cellpadding="0" align='center'>
    <tr>
        <td style="text-align:right;">
            <select name="tecnico" class="frm">
                <option value=""></option>
<?php
    while ($fetch = pg_fetch_assoc($qry_tec)) {
?>
                <option value="<?=$fetch["tecnico"]?>" <?=($fetch["tecnico"] == $login_unico_tecnico) ? "selected='selected'" : ""?> ><?=$fetch["nome"]?></option>
<?php
    }
?>
            </select>
        </td>
        <td>
            <button id="transferirTec"><?= traduz("transferir") ?></button>
        </td>
    </tr>
</table>

<?php
}
?>

<?php
if ($login_fabrica == 156 and $posto_interno == true) {
    $sql_os_ext = "SELECT os_numero FROM tbl_os WHERE os = $os";
    $qry_os_ext = pg_query($con, $sql_os_ext);

    if (pg_num_rows($qry_os_ext) > 0) {
        $os_externa = pg_fetch_result($qry_os_ext, 0, 'os_numero');

        $sql_posto_externo = "SELECT codigo_posto, nome
            FROM tbl_posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
            AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_os ON tbl_os.posto = tbl_posto.posto
            WHERE tbl_os.os = $os_externa";
        $qry_posto_externo = pg_query($con, $sql_posto_externo);

        if (pg_num_rows($qry_posto_externo) > 0) {
            echo '
                <table width="700" border="0" cellspacing="1" class="Tabela" cellpadding="0" align="center">
                <TR>
                <TD class="inicio">&nbsp;&nbsp;'.traduz("posto.externo").'</TD>
                </TR>
                <TR>
                <TD class="conteudo">&nbsp;' .
                pg_fetch_result($qry_posto_externo, 0, 'codigo_posto') . ' - ' . pg_fetch_result($qry_posto_externo, 0, 'nome')
                . '</TD>
                </TR>
                </table>';
        }
    }
}

if (in_array($login_fabrica, array(169, 170))) {
    if (!empty($os_numero)) {
        $sqlOsNumero = "SELECT sua_os, os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os_numero}";
        $resOsNumero = pg_query($con, $sqlOsNumero);

        $os_numero_sua_os = pg_fetch_result($resOSNumero, 0, "sua_os");

        if (empty($os_numero_sua_os)) {
            $os_numero_sua_os = $os_numero;
        }

        $os_conjunto = true;
    } else {
        $sqlOsNumero = "SELECT sua_os, os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os_numero = {$os}";
        $resOsNumero = pg_query($con, $sqlOsNumero);

        $os_numero_sua_os = pg_fetch_result($resOSNumero, 0, "sua_os");
        $os_numero        = pg_fetch_result($resOsNumero, 0, "os");

        if (empty($os_numero_sua_os)) {
            $os_numero_sua_os = $os_numero;
            $os_conjunto = false;
        }
    }

    if (!empty($os_numero_sua_os)) {
    ?>
        <table width="700" border="0" cellspacing="1" class="Tabela" cellpadding="0" align="center">
            <TR>
                <TD class="inicio">&nbsp;&nbsp;<?= traduz("os.do.conjunto") ?></TD>
            </TR>
            <TR>
                <TD class="conteudo">&nbsp;<a href="os_press.php?os=<?=$os_numero?>" target="_blank" style="text-decoration: underline;" ><?=$os_numero_sua_os?></a></TD>
            </TR>
        </table>
    <?php
    }
}

if(in_array($login_fabrica, array(101))){
    $td_os_rowspan = 4;
}

if ($login_fabrica == 72) {
    $sql_obs_adic = "SELECT obs_adicionais FROM tbl_os_extra WHERE os = $os AND extrato = 0";
    $qry_obs_adic = pg_query($con, $sql_obs_adic);

    if (pg_num_rows($qry_obs_adic)) {
        $obs_adicionais = pg_fetch_result($qry_obs_adic, 0, 'obs_adicionais');

        if (!empty($obs_adicionais)) {
            echo "<center>
                <div style='font-family:verdana;align:center; margin-top: 10px; margin-bottom: 5px;' align='center'>
                    <b style='font-size:14px;color:#e0123f'>$obs_adicionais</b>
                </div></center>";
        }
    }

}

if ($login_fabrica == 42) {

    $sqlAud = "SELECT tbl_auditoria_os.os
                 FROM tbl_os
                 JOIN tbl_auditoria_os USING(os)
                WHERE tbl_auditoria_os.os = $os
                  AND tbl_auditoria_os.liberada IS NULL
                  AND tbl_os.fabrica = {$login_fabrica}
                  AND tbl_auditoria_os.auditoria_status = 6
                  AND tbl_auditoria_os.observacao='Quantidade de OSs abertas no mês atual é maior que o dobro da média.'";
    $resAud = pg_query($con, $sqlAud);

    if (pg_num_rows($resAud) > 0) {
        echo "
        <div style='width:700px;background-color:#ff0000;font-family:Arial;margin:0 auto; padding-top: 5px; padding-bottom: 5px;' align='center'>
            <b style='font-size:14px;color:#ffffff;'>".traduz("aguardando.aprovacao.da.fabrica")."</b>
        </div>";
    }

}

?>

<?php

if((in_array($login_fabrica, array(164,191)) && $posto_interno == true) || in_array($login_fabrica,array(101))){
// echo "hey!";
    echo "<br />";

    $sql = "
        SELECT  TO_CHAR(tbl_os_troca.data, 'DD/MM/YYYY HH:MI')                      AS data                             ,
                tbl_os_troca.ressarcimento                                                                              ,
                (produto_trocado.referencia || ' - ' || produto_trocado.descricao)  AS produto_trocado                  ,
                (produto_troca.referencia   || ' - ' || produto_troca.descricao)    AS produto_troca                    ,
                tbl_os_troca.setor                                                                                      ,
                tbl_os_troca.situacao_atendimento                                                                       ,
                tbl_causa_troca.descricao                                           AS causa_troca                      ,
                tbl_os_troca.gerar_pedido                                                                               ,
                tbl_os_troca.distribuidor                                                                               ,
                tbl_os_troca.observacao                                                                                 ,
                tbl_admin.nome_completo                                             AS admin                            ,
                tbl_os_troca.ri                                                     AS numero_registro                  ,
                tbl_os_troca.admin_autoriza                                         AS admin_autoriza                   ,
                tbl_ressarcimento.nome                                              AS ressarcimento_nome               ,
                tbl_ressarcimento.cpf                                               AS ressarcimento_cpf                ,
                tbl_banco.codigo                                                    AS ressarcimento_banco              ,
                tbl_ressarcimento.agencia                                           AS ressarcimento_agencia            ,
                tbl_ressarcimento.conta                                             AS ressarcimento_conta              ,
                tbl_ressarcimento.tipo_conta                                        AS ressarcimento_tipo_conta         ,
                TO_CHAR(tbl_ressarcimento.previsao_pagamento, 'DD/MM/YYYY')         AS ressarcimento_previsao_pagamento ,
                tbl_ressarcimento.valor_original                                    AS ressarcimento_valor              ,
                TO_CHAR(tbl_ressarcimento.liberado,'DD/MM/YYYY')                    AS ressarcimento_data_pagamento
        FROM    tbl_os_troca
   LEFT JOIN    tbl_produto AS produto_trocado  ON  produto_trocado.produto     = tbl_os_troca.produto
                                                AND produto_trocado.fabrica_i   = {$login_fabrica}
   LEFT JOIN    tbl_peca    AS produto_troca    ON  produto_troca.peca          = tbl_os_troca.peca
                                                AND produto_troca.fabrica       = {$login_fabrica}
   LEFT JOIN    tbl_causa_troca                 ON  tbl_causa_troca.causa_troca = tbl_os_troca.causa_troca
                                                AND tbl_causa_troca.fabrica     = {$login_fabrica}
   LEFT JOIN    tbl_admin                       ON  tbl_admin.admin             = tbl_os_troca.admin
                                                AND tbl_admin.fabrica           = {$login_fabrica}
   LEFT JOIN    tbl_ressarcimento               ON  tbl_ressarcimento.os_troca  = tbl_os_troca.os_troca
                                                AND tbl_ressarcimento.fabrica   = {$login_fabrica}
   LEFT JOIN    tbl_banco                       ON  tbl_banco.banco             = tbl_ressarcimento.banco
        WHERE   tbl_os_troca.fabric = {$login_fabrica}
        AND     tbl_os_troca.os     = {$os}";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $k = 0;
        while ($os_troca = pg_fetch_object($res)) {

            $link_formulario = ($os_troca->ressarcimento == "t") ? "formulario_troca_recompra.php?os=$os&acao=ressarcimento" : "formulario_troca_recompra.php?os=$os&acao=troca";

            $titulo_tabela_troca = ($os_troca->ressarcimento == "t") ? "Produto Ressarcido" : "Produto Trocado";

            if ($telecontrol_distrib)
                $atende_troca = is_null($os_troca->distribuidor) ? 'FÁBRICA' : 'DISTRIBUIDOR';

            if ($k == 0 && $login_fabrica == 164) { ?>
            <a href="<?php echo $link_formulario;?>" target="_BLANK">Imprimir formulário de <?php echo $titulo_tabela_troca; ?></a>
        <?php } ?>

            <table class="Tabela" style="width: 700px; margin: 0 auto; table-layout: fixed;" cellspacing="1" cellpadding="0" border="0" >
                <thead>
                    <tr>
                        <td style="color: #FFFFFF; font-weight: bold; text-align: center;" colspan="4" ><?=$titulo_tabela_troca?></td>
                    </tr>
                </thead>
                <tbody>
<?php
            if ($login_fabrica == 164) {
?>
                    <tr>
                        <td class="titulo"><?=$titulo_tabela_troca?></td>
                        <td class="conteudo" colspan="3"><?=$os_troca->produto_trocado?></td>
                    </tr>
<?php
            }
            if ($os_troca->ressarcimento == "f") {
?>
                        <tr>
                            <td class="titulo">Trocado por</td>
                            <td class="conteudo" colspan="3"><?=$os_troca->produto_troca?></td>
                        </tr>
                    <?php
                    }

                    if ($os_troca->ressarcimento == "t") {
                    ?>
                        <tr>
                            <td class="titulo" ><?= traduz("nome.do.cliente") ?></td>
                            <td class="conteudo" ><?=$os_troca->ressarcimento_nome?></td>
                            <td class="titulo" ><?= traduz("cpf.do.cliente") ?></td>
                            <td class="conteudo" ><?=$os_troca->ressarcimento_cpf?></td>
                        </tr>
                        <tr>
                            <td class="titulo" ><?= traduz("valor") ?></td>
                            <td class="conteudo" ><?=number_format($os_troca->ressarcimento_valor, 2, ",", ".")?></td>
                            <td class="titulo" ><?= traduz("previsao.de.pagamento") ?></td>
                            <td class="conteudo" ><?=$os_troca->ressarcimento_previsao_pagamento?></td>
                        </tr>
                        <tr>
                            <td class="titulo" ><?= traduz("banco") ?></td>
                            <td class="conteudo" ><?=$os_troca->ressarcimento_banco?></td>
                            <td class="titulo" ><?= traduz("agencia") ?></td>
                            <td class="conteudo" ><?=$os_troca->ressarcimento_agencia?></td>
                        </tr>
                        <tr>
                            <td class="titulo" ><?= traduz("conta") ?></td>
                            <td class="conteudo" ><?=$os_troca->ressarcimento_conta?></td>
                            <td class="titulo" ><?= traduz("tipo.de.conta") ?></td>
                            <td class="conteudo" ><?=($os_troca->ressarcimento_tipo_conta == "C") ? "Conta Corrente" : "Conta Poupança"?></td>
                        </tr>
                    <?php
                    }
                    ?>
                    <tr>
                        <td class="titulo"><?= traduz("setor") ?></td>
                        <td class="conteudo">
                            <?php

                            switch ($os_troca->setor) {
                                case "revenda":
                                    echo "Revenda";
                                    break;

                                case "carteira":
                                    echo "Carteira";
                                    break;

                                case "sac":
                                    echo "SAC";
                                    break;

                                case "procon":
                                    echo "Procon";
                                    break;

                                case "sap":
                                    echo "SAP";
                                    break;

                                case "suporte_tecnico":
                                    echo "Suporte Técnico";
                                    break;
                            }

                            ?>
                        </td>
                        <td class="titulo"><?= traduz("situacao.atendimento") ?></td>
                        <td class="conteudo">
                            <?php

                            switch ($os_troca->situacao_atendimento) {
                                case 0:
                                    echo "Produto em Garantia";
                                    break;

                                case 50:
                                    echo "Faturado 50%";
                                    break;

                                case 100:
                                    echo "Faturado 100%";
                                    break;
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="titulo"><?= traduz("causa.troca") ?></td>
                        <td class="conteudo"><?=$os_troca->causa_troca?></td>
                        <td class="titulo">Admin</td>
                        <td class="conteudo"><?=$os_troca->admin?></td>
                    </tr>
                    <tr>
                        <td class="titulo"><?= traduz("numero.de.registro") ?></td>
                        <td class="conteudo"><?=$os_troca->numero_registro?></td>
                        <td class="titulo"><?= traduz("gera.pedido") ?></td>
                        <td class="conteudo"><?=($os_troca->gerar_pedido == "t") ? "Sim" : "Não"?></td>
                    </tr>
                    <?php
                    $colSpanObs = isset($atende_troca) ? '' : 'colspan="3"';
                    ?>
                    <tr>
                        <td class="titulo"><?= traduz("data") ?></td>
                        <td class="conteudo" <?=$colSpanObs?>><?=$os_troca->data?></td>
<?php
                    if ($atende_troca) {
?>
                        <td class="titulo"><?= traduz("atendido.por") ?>:</td>
                        <td class="conteudo"><?=$atende_troca?></td>
<?php
                    }
                    if ($os_troca->ressarcimento == "t") {
?>
                        <td class="titulo"><?= traduz("data.pagamento") ?>:</td>
                        <td class="conteudo"><?=$os_troca->ressarcimento_data_pagamento?></td>
<?php
                    }
?>
                    </tr>
<?php
                    if ($login_fabrica == 101) {
                        $sqlPedidoTroca = "
                            SELECT  TO_CHAR(tbl_faturamento.saida,'DD/MM/YYYY') AS data_saida,
                                    UPPER(tbl_faturamento.conhecimento)         AS rastreio
                            FROM    tbl_faturamento
                            JOIN    tbl_faturamento_item USING(faturamento)
                            WHERE   tbl_faturamento_item.os_item IN (
                                SELECT  os_item
                                FROM    tbl_os_item
                                JOIN    tbl_os_produto USING(os_produto)
                                JOIN    tbl_os USING(os)
                                WHERE   os      = $os
                                AND     fabrica = $login_fabrica
                            )
                            AND     tbl_faturamento.fabrica = $login_fabrica
                        ";
                        $resPedidoTroca = pg_query($con,$sqlPedidoTroca);
                        while ($resultado = pg_fetch_object($resPedidoTroca)) {
?>
                    <tr>
                        <td class='titulo' align='left' height='15' nowrap><?= traduz("data.saida") ?></td>
                        <td class='conteudo'><?=$resultado->data_saida?></td>
                        <td class='titulo'><?= traduz("rastreio") ?></td>
                        <td class='conteudo'><?=$resultado->rastreio?></td>
                    </tr>
<?php
                        }

                        $sqlEnvio = "
                            SELECT  envio_consumidor
                            FROM    tbl_os_troca
                            WHERE   os=$os
                        ";
                        $resEnvio = pg_query($con,$sqlEnvio);

                        while ($envio = pg_fetch_object($resEnvio)) {
?>
                    <tr>
                        <td class='titulo' align='left' height='15' nowrap><?= traduz("destinatario") ?></td>
                        <td class='conteudo' colspan="3"><?=($envio->envio_consumidor == 't') ? "Direto para o consumidor" : "Envio para o posto"?></td>
                    </tr>
<?php
                        }
                    }
?>
                    <tr>
                        <td class="titulo"><?= traduz("observacao") ?></td>
                        <td class="conteudo" colspan="3"><?=$os_troca->observacao?></td>
                    </tr>
                </tbody>
            </table>
            <br />
<?php
            $k++;
        }

    }

}

?>

<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
    <tr >
        <td rowspan='<?=$td_os_rowspan?>' class='conteudo' width='300' >
            <center>
                <?echo traduz("os.fabricante",$con,$cook_idioma);?><br>&nbsp;
                <b><FONT SIZE='6' COLOR='#C67700'>
                <?
                if ($login_fabrica == 1) echo $posto_codigo;

                if (strlen($consumidor_revenda) > 0 AND $login_fabrica <> 87)
                    echo $sua_os ."</FONT> - ". $consumidor_revenda;
                else
                    echo $sua_os;

                if($login_fabrica==3 OR $login_fabrica==86 or $multimarca == 't'){ echo "<BR><font color='#D81005' SIZE='4' ><strong>$marca_nome</strong></font>";}

                 if($login_fabrica==104){
                     $marca_nome = ($marca_nome == "DWT") ? $marca_nome : "OVD";
                     echo "<BR><font color='#D81005' SIZE='4' ><strong>$marca_nome</strong></font>";
                }

                if($login_fabrica == 186 and $consumidor_revenda == 'REVENDA'){

                    $id_os_revenda = explode("-", $sua_os);

                    $sqlqtdeosrevenda = "SELECT sum(qtde) as qtde_os_revenda FROM tbl_os_revenda_item where os_revenda = ".$id_os_revenda[0] . "  ";
                    $resqtdeosrevenda = pg_query($con, $sqlqtdeosrevenda);

                    $qtde_os_revenda = pg_fetch_result($resqtdeosrevenda, 0, 'qtde_os_revenda');

                    echo "<BR><font color='#D81005' SIZE='2' ><strong>".$id_os_revenda[1]." de ".$qtde_os_revenda." </strong></font>";
                }

                if($login_fabrica==117 or $login_fabrica == 128 or $login_fabrica == 153){
                     echo ($certificado_garantia <> "null" and strlen($certificado_garantia) > 0) ? "<BR><font color='#D81005' SIZE='4' ><strong>".traduz("garantia.estendida")."</strong></font>" : "";
                }
                if($login_fabrica == 148 and $tipo_os == 17){
                    echo '<br /><span style="color: #FF0000;">('.traduz("os.fora.de.garantia").')</span>';
                }

                if(in_array($login_fabrica, array(86,176))){
                    $sql = "SELECT os FROM tbl_cliente_garantia_estendida WHERE fabrica = $login_fabrica AND os = $os AND garantia_mes > 0";
                    $res = pg_query($con, $sql);

                    if (pg_num_rows($res) > 0) {
                        echo "<BR><font color='#D81005' SIZE='4' ><strong>".traduz("garantia.estendida")."</strong></font>";
                    }
                }

                if($login_fabrica == 6 AND $recolhimento == "t"){
                     echo "<BR><font color='#D81005' SIZE='4' ><strong>".traduz("produto.de.coleta")."</strong></font>";
                }

                if ($cortesia == "t") {
                    echo "<BR><font color='#D81005' SIZE='4' ><strong>".traduz("cortesia")."</strong></font>";
                }

                if(strlen($sua_os_offline)>0 AND $login_fabrica != 175){
                    echo "<table width='300' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
                        echo "<tr >";
                            echo "<td class='conteudo' width='300' height='25' align='center'><BR><center>";
                                if($login_fabrica==20) fecho ("os.interna",$con,$cook_idioma);
                                else                   fecho ("os.off.line",$con,$cook_idioma);
                                echo " - $sua_os_offline";
                            echo "</center></td>";
                        echo "</tr>";
                    echo "</table>";
                }?>

                <? if (in_array($login_fabrica,array(35,151,157,169,170)) && !empty($os_posto)) { ?>
                <br/><?= (in_array($login_fabrica, array(169,170))) ? "OS SAP:" : "OS INTERNA:"; ?> <span style="font-size: 14px; color: #C67700;"><?= $os_posto ?></span>&nbsp;</td>
                <? } ?>
            </b>

            <?php

            if(in_array($login_fabrica,array(30,162)) && strlen($os) > 0){

                $sql_hd_chamado = "SELECT hd_chamado FROM tbl_os WHERE os = {$os}";
                $res_hd_chamado = pg_query($con, $sql_hd_chamado);

                $hd_chamado = pg_fetch_result($res_hd_chamado, 0, "hd_chamado");

                if(strlen($hd_chamado) > 0){

                    $sql_hd_classificacao = "SELECT tbl_hd_chamado.hd_classificacao,
                                                    tbl_hd_chamado.analise                  AS orientacao_posto,
                                                    tbl_hd_chamado_extra.numero_processo    AS os_revenda,
                                                    tbl_hd_classificacao.descricao          AS hd_classificacao_descricao
                                            FROM    tbl_hd_chamado
                                            JOIN    tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                                            JOIN    tbl_hd_classificacao USING(hd_classificacao)
                                            WHERE   tbl_hd_chamado.hd_chamado = {$hd_chamado}";
                    $res_hd_classificacao = pg_query($con, $sql_hd_classificacao);

                    $hd_classificacao           = pg_fetch_result($res_hd_classificacao, 0, "hd_classificacao");
                    $orientacao_posto           = pg_fetch_result($res_hd_classificacao, 0, "orientacao_posto");
                    $os_revenda                 = pg_fetch_result($res_hd_classificacao, 0, "os_revenda");
                    $hd_classificacao_descricao = pg_fetch_result($res_hd_classificacao, 0, "hd_classificacao_descricao");

                    if($hd_classificacao == 47){
                        echo "<br /> <strong style='color: #ff0000;'>OS Revenda: {$os_revenda}</strong>";
                    }

                    if ($login_fabrica == 162) {
?>
                        <br /> <strong style='color: #ff0000;'><?= traduz("classificacao.atendimento") ?>: <?=$hd_classificacao_descricao?></strong>
<?php
                    }
                }

            }

            if(in_array($login_fabrica, [167, 203])){
                if($suprimento == "t"){
                    echo "<br /> <strong style='color: #ff0000;'>".traduz("suprimento")."</strong>";
                }else{
                    echo "<br /> <strong style='color: #ff0000;'>".traduz("produto.principal")."</strong>";
                }
            }

            ?>

            </center>
        </td>
        <td class='inicio' height='15' colspan='4'>&nbsp;<?echo traduz("datas.da.os",$con,$cook_idioma);?></td>
    </TR>

    <?php
    if (in_array($login_fabrica, array(11, 172))) {
        ?>
        <tr>
            <td class='titulo' width='100' height='15'><?= traduz("abertura") ?>&nbsp;</td>
            <td class='conteudo' width='100' height='15'>&nbsp;<?echo $data_abertura?></td>
        </tr>
        <tr>
            <td class='titulo' width='100' height='15'><?= traduz("consertado") ?> &nbsp;</td>
            <td class='conteudo' width='100' height='15' colspan ='1'>
            <?php
                $sql_data_conserto = "SELECT to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' ) as data_conserto FROM tbl_os WHERE os = $os";
                $resdc = pg_query ($con,$sql_data_conserto);
                if (pg_num_rows ($resdc) > 0) {
                    $data_conserto= pg_fetch_result ($resdc,0,data_conserto);
                }
                if(strlen($data_conserto)>0){
                    if ($login_fabrica == 1) {
                        echo "&nbsp;" . substr($data_conserto, 0,10);
                    }else{
                        echo "&nbsp;" . $data_conserto;
                    }
                }else{
                    echo "&nbsp;";
                }
            ?>
            </td>
        </tr>
        <tr>
            <td class='titulo' width='100' height='15'><?= traduz("fechamento") ?>&nbsp;</td>
            <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_fechamento ?></td>
        </tr>
        <tr>
            <td class='titulo' width='100' height='15'><center><b><?=strtoupper($status_checkpoint)?></b></center></td>
        </tr>
        <?
    } elseif (in_array($login_fabrica, array(101))) {

        $sql_data_entrada = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os} AND fabrica = {$login_fabrica}";
        $res_data_entrada = pg_query($con, $sql_data_entrada);

        $campos_adicionais = pg_fetch_result($res_data_entrada, 0, "campos_adicionais");

        $data_entrada = "";

        if(strlen($campos_adicionais) > 0){
            $json_campos_adicionais = json_decode($campos_adicionais, true);
            if(isset($json_campos_adicionais["data_recebimento_produto"])){
                $data_entrada = $json_campos_adicionais["data_recebimento_produto"];
            }
        }

    ?>

    <tr>
        <td class='titulo' width='100' height='15'><?= traduz("entrada") ?>&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<?echo $data_entrada?></td>
        <td class='titulo' width='100' height='15'><?= traduz("digitacao") ?>&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_digitacao ?></td>
    </tr>

    <tr>
        <td class='titulo' width='100' height='15'><?= traduz("abertura") ?>&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<?echo $data_abertura?></td> 
        <td class='titulo' width='100' height='15'><?= traduz("fechamento") ?>&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_fechamento ?></td>
    </tr>

    <tr>
        <td class="titulo"  height='15'><?= traduz("data.da.nf") ?>&nbsp;</td>
        <td class="conteudo"  height='15'>&nbsp;<? echo $data_nf ?></td>
        <td class='titulo' width='100' height='15'><?= traduz("finalizada") ?>&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_finalizada ?></td>
    </tr>

    <tr>
        <td class='titulo'><b><center><?=strtoupper($status_checkpoint)?></b></center></td>
        <td class='titulo' width='100' height='15'><?= traduz("consertado") ?> &nbsp;</td>
        <td class='conteudo' width='100' height='15' colspan ='1'>
        <?php
            $sql_data_conserto = "SELECT to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' ) as data_conserto FROM tbl_os WHERE os = $os";
            $resdc = pg_query ($con,$sql_data_conserto);
            if (pg_num_rows ($resdc) > 0) {
                $data_conserto= pg_fetch_result ($resdc,0,data_conserto);
            }
            if(strlen($data_conserto)>0){
                if ($login_fabrica == 1) {
                    echo substr($data_conserto, 0,10);
                }else{
                    echo $data_conserto;
                }
            }else{
                echo "&nbsp;";
            }
        ?>
        </td>
        <td class='titulo' width='100' height='15'><?= traduz("fechado.em") ?>&nbsp;</td>
        <td class='conteudo' width='100' height='15'>&nbsp;
            <?php
            if(strlen($data_fechamento) > 0 && strlen($data_abertura) > 0){

                if ($sinalizador == 18) {
                    echo "<font color='#FF0000'>".traduz("fechamento.automatico")."</font>";
                } else {
                    $sql_data = "SELECT SUM(data_fechamento - data_abertura) as final FROM tbl_os WHERE os = $os";
                    $resD = pg_query ($con,$sql_data);
                    if (pg_num_rows ($resD) > 0) {
                        $total_de_dias_do_conserto = pg_fetch_result ($resD, 0, 'final');
                    }

                    if($total_de_dias_do_conserto==0) echo 'no mesmo dia' ;
                    else echo $total_de_dias_do_conserto;
                    if($total_de_dias_do_conserto==1) echo ' dia' ;
                    if($total_de_dias_do_conserto>1)  echo ' dias' ;
                    if($login_fabrica == 1){
                        $sql_extrato = "SELECT to_char(tbl_extrato_financeiro.data_envio,'DD/MM/YYYY') AS data_envio
                                        FROM tbl_os_extra
                                        LEFT JOIN tbl_extrato_financeiro ON tbl_os_extra.extrato = tbl_extrato_financeiro.extrato
                                        WHERE tbl_os_extra.os = $os LIMIT 1";
                        $res_extrato = pg_query($con,$sql_extrato);
                        if(pg_num_rows($res_extrato)>0){
                            $data_envio = pg_fetch_result ($res_extrato,0,data_envio);
                            echo " ";
                            echo "<acronym title='Data de envio para o Financeiro'>$data_envio</acronym>" ;
                        }
                    }
                }
            }else{
                echo traduz("nao.finalizado");
            }
            ?>
        </td>
    </tr>

    <?php

    }else{
        if($login_fabrica == 157){
            $abertura_x = 'data.entrada.prod.assist';
        } else {
            $abertura_x = 'abertura';
        }
    ?>

    <TR>
		<td class="titulo" width="100" height="15">
			<?php echo getValorFabrica([traduz($abertura_x), 3 => 'DATA DE ENTRADA DO PRODUTO NO POSTO']);?>&nbsp;
		</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<?= (strlen($data_hora_abertura) > 0) ? $data_hora_abertura : $data_abertura ?>
            <input type="hidden" name="input_data_abertura" id="input_data_abertura" value="<?=$data_abertura?>">
        </td>
	<? if (isFabrica(158)) { ?>
		<td class='titulo' width='100' height='15'><?= traduz("data.fabricacao") ?></td>
		<td class='conteudo' width='100' height='15'><?=$data_fabricacao?></td>
	<? } else { ?>
		<td class='titulo' width='100' height='15'><?echo traduz("digitacao",$con,$cook_idioma);?></TD>
		<td class='conteudo' width='100' height='15'>&nbsp;<?= $data_digitacao ?></td>
	<? } ?>
    </tr>
    <tr>
		<td class='titulo' width='100' height='15'><?=getValorFabrica([traduz('fechamento'), 3 => 'DATA DE RETIRADA DO PRODUTO PELO CONSUMIDOR'])?>&nbsp;</td>
        <td class='conteudo' width='100' height='15' id='data_fechamento'>
            &nbsp;<?= (in_array($login_fabrica, [169, 170]) && strlen($data_hora_fechamento)) > 0 ? 
                $data_hora_fechamento :
                $data_fechamento ?>
        </td>
        <td class='titulo' width='100' height='15'><?echo traduz("finalizada",$con,$cook_idioma);?></TD>
        <td class='conteudo' width='100' height='15' id='finalizada'>&nbsp;<? echo $data_finalizada ?></td>

    </tr>
    <tr>
        <TD class="titulo"  height='15'><?echo traduz("data.da.nf",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15'>&nbsp;<? echo $data_nf ?></TD>
        <td class='titulo' width='100' height='15'><?echo traduz("fechado.em",$con,$cook_idioma);?></TD>
        <td class='conteudo' id="fechado_em" width='100' height='15'>&nbsp;
        <?
        if(strlen($data_fechamento)>0 AND strlen($data_abertura)>0){
            //HD 204146: Fechamento automático de OS
            if ($login_fabrica == 3) {
                $sql = "SELECT sinalizador FROM tbl_os WHERE os=$os";
                $res_sinalizador = pg_query($con, $sql);
                $sinalizador = pg_result($res_sinalizador, 0, sinalizador);
            }

            if ($sinalizador == 18) {
                echo "<font color='#FF0000'>".traduz("fechamento.automatico")."</font>";
            }
            else {
                $sql_data = "SELECT SUM(data_fechamento - data_abertura)as final FROM tbl_os WHERE os=$os";
                $resD = pg_query ($con,$sql_data);

                if (pg_num_rows ($resD) > 0) {
                    $total_de_dias_do_conserto = pg_fetch_result ($resD,0,'final');
                }
                if($total_de_dias_do_conserto==0) {
                    fecho("no.mesmo.dia",$con,$cook_idioma) ;
                }
                else echo $total_de_dias_do_conserto;
                if($total_de_dias_do_conserto==1) {
                    echo " ".traduz("dia",$con,$cook_idioma) ;
                }
                if($total_de_dias_do_conserto>1) {
                    echo " ".traduz("dias",$con,$cook_idioma);
                }
            }
        } else {
            echo strtoupper(traduz("nao.finalizado"));
        }
        ?>
        </td>
    </tr>
    <? if (usaDataConserto($login_posto, $login_fabrica)) { /*HD 13239 HD 14121 56101*/ ?>
	<tr>
    	<td class='titulo'>
            <b><center>
                <?php

				if(!empty($tipo_atendimento)) {
					$sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
					$res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);

					$desc_tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');

					if (in_array($login_fabrica, [186,190,191,195]) && $desc_tipo_atendimento == "Orçamento") { 

						$sql_orcamento = "SELECT os,tbl_status_os.descricao
							FROM tbl_os
							JOIN tbl_status_os ON tbl_os.status_os_ultimo = tbl_status_os.status_os
							WHERE tbl_os.os = $os";

						$res_orcamento = pg_query($con, $sql_orcamento);

						$desc_status_orcamento = pg_fetch_result($res_orcamento,0,'descricao');

						echo $desc_status_orcamento;

					} else {


						if ($telecontrol_distrib && (!isset($novaTelaOs) || in_array($login_fabrica, [160]))) {

							if (strtoupper($status_checkpoint) == 'AGUARD. ABASTECIMENTO ESTOQUE') {
								echo 'AGUARDANDO PEÇAS';
							} else {
								echo $status_checkpoint;
							}

						} else {

							// Verifica se OS está em AUD
							if (in_array($login_fabrica, [167, 203])) {
								$sql_aud = "SELECT os FROM tbl_auditoria_os where os = $os AND liberada IS NULL AND cancelada IS NULL AND reprovada IS NULL";
								$res_aud = pg_query($con, $sql_aud);
								if (pg_num_rows($res_aud) > 0) {
									$status_checkpoint = "Em Auditoria";
								}
							}

							echo strtoupper($status_checkpoint);

						} 
					}
				}?>
            </b></center>

        </td>
        <td class='titulo' width='100' height='15'><?echo traduz("consertado",$con,$cook_idioma);?>&nbsp; </td>
        <td class='conteudo' width='100' height='15' colspan ='1' id='consertado'>&nbsp;
        <?
                $sql_data_conserto = "SELECT to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' ) as data_conserto
                                        FROM tbl_os
                                        WHERE os=$os";
                $resdc = pg_query ($con,$sql_data_conserto);
                if (pg_num_rows ($resdc) > 0) {
                    $data_conserto= pg_fetch_result ($resdc,0,data_conserto);
                }
                if(strlen($data_conserto)>0){
                    if ($login_fabrica == 1) {
                        echo substr($data_conserto, 0,10);
                    }else{
                        echo $data_conserto;
                    }
                } else {
                    echo "&nbsp;";
                }
            echo "</td>";

            if($login_fabrica == 1){
                $titulo_extrato = "Extrato";

                $sql = "SELECT tbl_extrato.protocolo FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $os";
                $res2 = pg_query ($con,$sql);
                $protocolo = @pg_fetch_result($res2, 0, 'protocolo');

                if (!empty($extrato)) {
                    $sql = "SELECT baixado FROM tbl_extrato_extra WHERE extrato = {$extrato}";
                    $res = pg_query($con, $sql);

                    $confere = "SELECT aprovado from tbl_extrato WHERE extrato = {$extrato} AND aprovado notnull";
                    $confere_res = pg_query($con, $confere);

                    if(pg_num_rows($confere_res)==0){
                        $baixado = pg_fetch_result($res, 0, "baixado");
                        $protocolo = (strlen($baixado) > 0) ? "" : $protocolo;
                    }
                }

            }
            if (in_array($login_fabrica, array(30, 50))) {
                $titulo_extrato = "Fechado por";
                $sql_es = "SELECT UPPER(tbl_os_extra.obs_fechamento) FROM tbl_os_extra WHERE os = $os;";
                $res_es = pg_query($con,$sql_es);

                $protocolo = pg_fetch_result($res_es, 0, 0);
            }

            if ($login_fabrica == 156) {
                $titulo_extrato = 'DATA DA LIBERAÇÃO DO PRODUTO';
                $protocolo = $data_nf_saida;

                if (!empty($os_externa) and !$posto_interno) {
                    $sql_data_liberacao_produto = "SELECT TO_CHAR(tbl_os.data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida FROM tbl_os WHERE os = $os_externa";
                    $qry_data_liberacao_produto = pg_query($con, $sql_data_liberacao_produto);

                    $protocolo = pg_fetch_result($qry_data_liberacao_produto, 0, 'data_nf_saida');
                }
            }

            if (in_array($login_fabrica, [164])) {
                $data_entrada     = "";
                $sql_data_entrada = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os} AND fabrica = {$login_fabrica}";
                $res_data_entrada  = pg_query($con, $sql_data_entrada);

                if (pg_num_rows($res_data_entrada) > 0) {
                    $campos_adicionais = pg_fetch_result($res_data_entrada, 0, "campos_adicionais");
                
                    if (strlen($campos_adicionais) > 0) {
                        $json_campos_adicionais = json_decode($campos_adicionais, true);
                        
                        if (isset($json_campos_adicionais["data_entrada"])) {
                            $data_entrada = $json_campos_adicionais["data_entrada"];
                        }
                    }
                }
            }

            if(in_array($login_fabrica, [167, 203])){
                if($nome_atendimento == "Garantia Recusada" AND strlen(trim($os_numero)) > 0){
                    $texto_td = "OS Orçamento";
                }else if($nome_atendimento == "Orçamento" AND strlen(trim($os_numero)) > 0){
                    $texto_td = "OS Garantia Recusada";
                }
            ?>
                <td class='titulo' width='100'height='15'><?= $texto_td ?> </td>
                <td class='conteudo' width='100' height='15'><?= $os_numero ?></tr>
            <?php
            } elseif (in_array($login_fabrica, [35, 169, 170])) {
                $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = $login_fabrica AND os = $os";
                $aux_res = pg_query($con, $aux_sql);

                $adicional         = (array) json_decode(pg_fetch_result($aux_res, 0, 0));
                $admin_finaliza_os = $adicional["admin_finaliza_os"];

                if (!empty($admin_finaliza_os)) {
                    $aux_sql = "SELECT nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $admin_finaliza_os LIMIT 1";
                    $aux_res = pg_query($con, $aux_sql);
                    $nome_admin = pg_fetch_result($aux_res, 0, 0);
                } elseif (!empty($data_fechamento)) {
                    $nome_admin = $posto_codigo;
                } else {
                    $nome_admin = "";
                }
            ?>
                <td class='titulo' width='100'height='15'><?= traduz("finalizado.por") ?></td>
                <td class='conteudo' width='100'height='15'><?= $nome_admin ?></td>
            <?php
            } elseif (in_array($login_fabrica, [164])) { ?>
                <td class='titulo' width='100'height='15'><?= traduz("entrada") ?></td>
                <td class='conteudo' width='100'height='15'><?= $data_entrada ?></td>               
            <?php
            } else if (!in_array($login_fabrica, [148])) {
            ?>
                <td class='titulo' width='100'height='15'><?= $titulo_extrato ?></td>
                <td class='conteudo' width='100' height='15'><?= $protocolo ?></tr>
            <?php
            }
        }

        if (in_array($login_fabrica, [148]) && !empty($data_falha)) { ?>
                
            <td class='titulo' width='100'height='15'><?= traduz("Data Falha") ?></td>
            <td class='conteudo' width='100' height='15'><?= $data_falha ?></td>
        
        <?php
        }

        if(strlen(trim($obs_reincidencia))>0 and $login_fabrica != 3) {
        ?>
            <tr><td colspan='5' bgcolor='#FF0000' size='2' align='center'><b><font color='#FFFF00'>Justificativa: <?=$obs_reincidencia?></font></b></td></tr>
        <?}

        if(strlen(trim($obs_reincidencia)) >0 and $login_fabrica == 3 and $status_os != 67){?>
            <tr><td colspan='5' bgcolor='#FF0000' size='2' align='center'><b><font color='#FFFF00'>Justificativa: <?=$obs_reincidencia?></font></b></td></tr>

        <?}


        if(strlen($motivo_reincidencia_desc)>0 and $login_fabrica == 52){
        ?>
            <tr><td colspan='5' bgcolor='#FF0000' size='2' align='center'><b><font color='#FFFF00'>Motivo da Reincidência: <?=$motivo_reincidencia_desc?></font></b></td></tr>
        <?}

        if($login_fabrica == 96 and !empty($motivo)){ //HD 399700 Mosta motivo caso OS for fora de Garantia?>
            <tr>
                <td class='titulo'><?= traduz("motivo") ?></td>
                <td colspan='3' class='conteudo'><?php echo $motivo; ?></td>
            </tr>
        <?
        }
    ?>

    <?php } ?>

</table>
<?
// CAMPOS ADICIONAIS SOMENTE PARA LORENZETTI
// adicionado para ibbl (90) HD#316365
if(in_array($login_fabrica,array(19,90,140,141))){
    if(strlen($tipo_os)>0){
        $sqll = "SELECT descricao from tbl_tipo_os where tipo_os=$tipo_os";
        $ress = pg_query($con,$sqll);
        $tipo_os_descricao = pg_fetch_result($ress,0,0);
    }
?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
    <TD class="titulo"  height='15' width='90'>
        <?echo traduz("atendimento",$con,$cook_idioma);?>
    </TD>
    <TD class="conteudo" height='15'>&nbsp;<? echo $codigo_atendimento.' - '.$nome_atendimento ?></TD>
    <?php if(!in_array($login_fabrica,array(90,140,141,144))) { ?>
        <TD class="titulo"  height='15' width='90'><? echo traduz("motivo",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $tipo_os_descricao; ?></TD>
        <TD class="titulo" height='15' width='90'><?echo traduz("nome.do.tecnico",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<?= $tecnico_nome ?></TD>
    <?php } else if(!in_array($login_fabrica,array(140,141,144))) { ?>
        <TD class="titulo"  height='15' width='90'>Recolhimento</TD>
        <TD class="conteudo" height='15'>
            &nbsp;<? echo $recolhimento == 'f' ? 'NÃO' : 'SIM'; ?>
        </TD>
        <?if (strlen($reoperacao_gas) > 0 and $login_fabrica==90){?>
            <TD class="titulo" height="15" width="110" nowrap>Reoperação de Gás</TD>
            <td class="conteudo" height="15">&nbsp;<?= $reoperacao_gas == 'f' ? "NÃO" : "SIM";?></td>
        <?  }
     } ?>
</TR>
</TABLE>
<?
}//FIM DA PARTE EXCLUSIVA DA LORENZETTI



// CAMPOS ADICIONAIS SOMENTE PARA BOSCH
if($login_fabrica==20 OR ($login_fabrica==15 and strlen($tipo_atendimento)>0)){
    if($login_fabrica==20 AND $tipo_atendimento==13 AND $tipo_troca==1){
        $tipo_atendimento = 00;
        $nome_atendimento = "Troca em Cortesia Comercial";
    }

    //HD 275256
    if($login_fabrica ==  15 and ($tipo_atendimento == 21 || $tipo_atendimento == 22) ){
        $sql = "select qtde_km from tbl_os where os=$os";
        $res = pg_query($con,$sql);

        $qtde_km = pg_result($res,0,0);
    }

    if ($login_fabrica == 15) {

        function get_status_img ($id) {

            switch ($id) {

                case 98 : $cor = 'status_amarelo.gif'; break;
                case 99 : $cor = 'status_verde.gif'; break;
                case 100: $cor = 'status_azul.gif'; break;
                case 101: $cor = 'status_vermelho.gif'; break;

                default : '';

            }

            return $cor;

        }

        $sql = "SELECT status_os
                    FROM tbl_os_status
                    WHERE os = $os
                    AND status_os IN (98,99,100,101)
                    ORDER BY os_status DESC
                    LIMIT 1";
        $res = pg_query($con,$sql);
        $status = @pg_result($res,0,0);

        $img         = get_status_img($status);
        $img = empty($img) ? 'status_verde.gif' : $img;

    }

?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
    <TD class="titulo"  height='15' width='90'><? echo traduz("atendimento",$con,$cook_idioma);?></TD>
    <TD class="conteudo" height='15'>&nbsp;<? echo $codigo_atendimento.' - '.$nome_atendimento ?></TD>

    <?php if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){ ?>
        <TD class="titulo"  height='15' width='90'><? echo traduz("motivo.ordem",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $motivo_ordem ?></TD>
    <?php } ?>
    <? if ($tecnico_nome) { ?>
        <td class="titulo" height='15'width='90'><?echo traduz("nome.do.tecnico",$con,$cook_idioma);?></td>
        <td class="conteudo" height='15'>&nbsp;<? echo $tecnico_nome ?></td>
    <? } ?>
    <?if($tipo_atendimento=='15' or $tipo_atendimento=='16'){?>
            <TD class="titulo"  height='15' width='90'><?echo traduz("promotor",$con,$cook_idioma);?></TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $promotor_treinamento ?></TD>
    <?}?>
</TR>
<?//HD 275256
if($login_fabrica ==  15 and ($tipo_atendimento == 21 || $tipo_atendimento == 22) ){
?>
<TR>
    <TD class="titulo" height="15" width="90">Qtde. KM</TD>
    <TD class="conteudo" height="15">&nbsp;<?echo $qtde_km."km"; if ($login_fabrica == 15) echo '&nbsp;<img src="admin/imagens_admin/'. $img .'" />'; ?></TD>
</TR>
<?
}
?>
</TABLE>
<?
}//FIM DA PARTE EXCLUSIVA DA BOSCH

/*
            <TD class="titulo"  height='15' width='90'>AUTORIZAÇÃO&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $autorizacao_cortesia ?></TD>
*/

// hd_chamado=2704100 adicionada fabrica 124
    if (in_array($login_fabrica, array(42,52,87,94,115,116,117,120,124,125,138,141,142,145)) || (isset($novaTelaOs) && $login_fabrica <> 139)) {?>
    <table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
        <tr>

            <? if (in_array($login_fabrica, array(156,158,162,165)) OR $usaPostoTecnico === true) { ?>
                <td class="titulo" height='15'><?= traduz("tecnico") ?></td>
                <td class="conteudo" height='15' width="170"><?= $tecnico_nome; ?></td>
            <?
            }
            ?>

            <?php if (in_array($login_fabrica, array(87,115,116,117,120,124,125,138,141,142,145)) || isset($novaTelaOs)) { ?>

            <td class="titulo" height='15' ><?= traduz("tipo.de.atendimento") ?></td>
            <td class="conteudo"  height='15' >&nbsp;
            <?
                if (intval($tipo_atendimento) > 0) {
                    $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                    $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);

		            $desc_tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');

                    if (isset($moduloTraducao)) {
                        $sqlIdiomaAtendimento = "SELECT descricao 
                                                 FROM tbl_tipo_atendimento_idioma 
                                                 WHERE tipo_atendimento = {$tipo_atendimento}
                                                 AND LOWER(idioma) = LOWER('$cook_idioma')";
                        $resIdiomaAtendimento = pg_query($con, $sqlIdiomaAtendimento);

                        if (pg_num_rows($resIdiomaAtendimento) > 0) {
                            $desc_tipo_atendimento = utf8_decode(pg_fetch_result($resIdiomaAtendimento, 0, 'descricao'));
                        }

                    }

                    echo $desc_tipo_atendimento;
                }
            ?>
            </td>
            <?
            if (in_array($login_fabrica, [167,177,203]) && $desc_tipo_atendimento == "Orçamento") { ?>

                <td class="titulo" height='15' ><?= traduz("status.orcamento") ?></td>
                <td class="conteudo"  height='15' >&nbsp;
                <?
                    $sql_orcamento = "SELECT os,tbl_status_os.descricao
                             FROM tbl_os
                             JOIN tbl_status_os ON tbl_os.status_os_ultimo = tbl_status_os.status_os
                             WHERE tbl_os.os = $os";

                    $res_orcamento = pg_query($con, $sql_orcamento);

                    $desc_status_orcamento = pg_fetch_result($res_orcamento,0,'descricao');

                    echo $desc_status_orcamento;
            ?>
            </td>
        <?php
            }

        } 

        if (in_array($login_fabrica, [144]) && $posto_interno) { ?>
            <td class="titulo" height='15' ><?= traduz("Código Rastreio") ?></td>
                <td class="conteudo"  height='15' >&nbsp;
                <?
                    $sql_pac = "SELECT tbl_os_extra.pac
                                FROM tbl_os_extra
                                WHERE tbl_os_extra.os = {$os}";
                    $res_pac = pg_query($con, $sql_pac);

                    echo pg_fetch_result($res_pac,0,'pac');
            ?>
            </td>
        <?php
        }

        ?>

            <? if (!in_array($login_fabrica, array(42,52,115,116,117,120,123,124,125,126,127,128,134,136,138,141,142,144,145)) && !isset($novaTelaOs)) { ?>

            <?php  ?>
            <td class="titulo" height='15' ><?= traduz("horas.trabalhadas") ?></td>
            <td class="conteudo" height='15' width='40' >&nbsp;<? echo $hora_tecnica; ?></td>
            <td class="titulo" height='15' ><?= traduz("horas.tecnicas") ?></td>
            <td class="conteudo" height='15' width='40'  >&nbsp;<? echo $qtde_horas; ?></td>

            <? } ?>

            <? if (!in_array($login_fabrica, array(42,115,116,117,120,123,124,125,126,127,128,134,136)) || isset($novaTelaOs)) {
                if(!in_array($login_fabrica, array(150,147,174))){ ?>

                    <?php if ($login_fabrica == 178){ ?>                
                        <td class="titulo" height='15' width='100' ><?= traduz("instalação.publica") ?></td>
                        <td class="conteudo" height='15' nowrap>&nbsp;
                        <?php
                            if ($os_campos_adicionais["instalacao_publica"] == "t"){
                                echo "SIM";
                            }else{
                                echo "NÃO";
                            }
                        ?>
                        </td>
                    <?php } ?>
                    <?if($login_fabrica == 153){
                        $descricao_tipo_atendimento =  pg_fetch_result($res_tipo_atendimento,0,'descricao');
                        if($descricao_tipo_atendimento == "Laudo Zero Hora"){ ?>
                            <td class="titulo" height='15' width='100' ><?= traduz("codigo.do.lacre") ?></td>
                            <td class="conteudo" height='15' nowrap>&nbsp;<? echo $codigo_lacre; ?></td>
                        <?php }
                    } else if (!in_array($login_fabrica, array(171,175,177,179,186))) { ?>
                        <td class="titulo" height='15' width='100' ><?= traduz("quantidade.de.km") ?></td>
                        <td class="conteudo" height='15' nowrap>&nbsp;<?=($login_fabrica == 178) ? $qtde_km_revenda : $qtde_km; ?> KM</td>
                    <?php
                    }

                    if (in_array($login_fabrica, [198])) {
                        $sql = "SELECT posto
                                FROM tbl_posto_fabrica
                                JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.posto_interno
                                WHERE tbl_posto_fabrica.fabrica = " . $login_fabrica . "
                                AND tbl_posto_fabrica.posto = " . $login_posto;
                        $res = pg_query($con,$sql);

                        if(pg_num_rows($res)) {
                            $posto_interno = true;
                        }

                        if (empty($tecnico_nome)) {
                            $sqltec = "SELECT nome FROM tbl_tecnico WHERE tecnico = $tecnico";
                            $restec = pg_query($con, $sqltec);

                            if (pg_num_rows($restec) > 0) {
                                $tecnico_nome = (!empty(pg_fetch_result($restec, 0, 'nome'))) ? pg_fetch_result($restec, 0, 'nome') : $tecnico_nome;
                            }
                        }

                        if ($posto_interno === true && !empty($tecnico_nome)) { ?>
                            <td class="titulo" height='15' width='100' ><?= traduz("tecnico") ?></td>
                            <td class="conteudo" height='15' nowrap>&nbsp;<?=$tecnico_nome?></td>
                    <?php 
                        }
                    }
                    
                    if ($login_fabrica == 178 AND !empty($hd_chamado)){
                    ?>
                        <td class="titulo" height='15' width='100' ><?= traduz("atendimento.callcenter") ?></td>
                        <td class="conteudo" height='15' nowrap>&nbsp;<?=$hd_chamado?></td>
                    <?php
                    }

                    if ($login_fabrica == 171 && $desc_tipo_atendimento != "Fora de Garantia") {
                    ?>
                    <td class="titulo" height='15' width='100' ><?= traduz("quantidade.de.km") ?></td>
                    <td class="conteudo" height='15' nowrap>&nbsp;<? echo $qtde_km; ?> KM</td>
                    <td class="titulo" height='15' width='100' ><?= traduz("km.ida") ?></td>
                    <td class="conteudo" height='15' nowrap>&nbsp;<? echo $os_campos_adicionais['qtde_km_ida']; ?> KM</td>
                    <td class="titulo" height='15' width='100' ><?= traduz("km.volta") ?></td>
                    <td class="conteudo" height='15' nowrap>&nbsp;<? echo $os_campos_adicionais['qtde_km_volta']; ?> KM</td>
                    <td class="titulo" height='15' width='100' ><?= traduz("quantidade.de.visitas") ?></td>
                    <td class="conteudo" height='15' nowrap>&nbsp;<? echo $qtde_diaria; ?></td>

            <?      }
                }
            }

            if($login_fabrica == 52){
                $sql = "SELECT pedagio FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
                $res = pg_query($con, $sql);
                $pedagio = (strlen(pg_fetch_result($res, 0, "pedagio")) == 0) ? 0 : pg_fetch_result($res, 0, "pedagio");
            ?>
                <td class="titulo" height='15' width='100' ><?= traduz("pedagio") ?></td>
                <td class="conteudo" height='15' nowrap>R$ <?php echo number_format($pedagio, 2, ",", "."); ?></td>
            <?php
            }

            if (in_array($login_fabrica, array(35,142,156,169,170))) {
            ?>
                <td class="titulo" height='15' width='100' ><?= traduz("visita") ?></td>
                <td class="conteudo" height='15' >&nbsp;<? echo $qtde_diaria; ?></td>
            <?php
            }

            if ($login_fabrica == 35) {
?>
                <td class="titulo" height='15' width='100' >TOTAL KM&nbsp;</td>
                <td class="conteudo" height='15' >&nbsp;<?= number_format($qtde_km * $qtde_diaria,2,',','.')?></td>
<?
            }
            if (in_array($login_fabrica, array(164)) && $posto_interno == true) {

                $sql_destinacao = "SELECT segmento_atuacao FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
                $res_destinacao = pg_query($con, $sql_destinacao);

                if(pg_num_rows($res_destinacao) > 0){

                    $segmento_atuacao = pg_fetch_result($res_destinacao, 0, "segmento_atuacao");

                    if(strlen($segmento_atuacao) > 0){

                        $sql_desc = "SELECT descricao FROM tbl_segmento_atuacao WHERE segmento_atuacao = {$segmento_atuacao} AND fabrica = {$login_fabrica}";
                        $res_desc = pg_query($con, $sql_desc);

                        if(pg_num_rows($res_desc) > 0){
                            $destinacao = pg_fetch_result($res_desc, 0, "descricao");
                        }

                    }

                }

            ?>
                <td class="titulo" height='15' width='100' ><?= traduz("destinacao") ?></td>
                <td class="conteudo" height='15' >&nbsp;<? echo $destinacao; ?></td>
            <?php
            }

            if( in_array($login_fabrica, array(152,180,181,182)) ) {
            ?>
                <td class="titulo" height='15' width='100' ><?= traduz("tempo.de.deslocamento") ?> (<?= traduz("horas") ?>)</td>
                <td class="conteudo" height='15' >&nbsp;<? echo $tempo_deslocamento; ?></td>
            <?php
            }

            if (in_array($login_fabrica, array(141)) && in_array($login_tipo_posto, array(452,453))) {
                $select_os_remanufatura = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
                $res_os_remanufatura = pg_query($con, $select_os_remanufatura);

                if (pg_num_rows($res_os_remanufatura) > 0) {
                    $json_os_remanufatura = json_decode(pg_fetch_result($res_os_remanufatura, 0, "campos_adicionais"), true);
                    $os_remanufatura      = $json_os_remanufatura["os_remanufatura"];
                }
                ?>
                <td class="titulo" height='15' width='100' ><?= traduz("remanufatura") ?></td>
                <td class="conteudo" height='15' ><?=($os_remanufatura == "t") ? "Sim" : "Não"?></td>
            <?php
            }

            if (!isset($novaTelaOs)) {
                if($login_fabrica <> 124){ //hd_chamado=2704100
                $sql = "SELECT tbl_solucao.descricao FROM tbl_solucao INNER JOIN tbl_os ON tbl_os.solucao_os = tbl_solucao.solucao WHERE tbl_os.fabrica = {$login_fabrica} AND tbl_os.os = {$os}";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $solucao_descricao = pg_fetch_result($res, 0, "descricao");
                }

            ?>
                <td class="titulo" height='15' width='100' ><?= traduz("solucao") ?></td>
                <td class="conteudo" height='15' >&nbsp;<? echo $solucao_descricao; ?></td>
            <?php
                }
            }
            ?>
            <?php if (in_array($login_fabrica, array(184,191,200)) && $posto_interno == true) {?>
                <td class="titulo" height='15' width='100' ><?echo traduz("nome.do.tecnico",$con,$cook_idioma);?></td>
                <td class="conteudo" height='15' >&nbsp;<? echo $tecnico_nome; ?></td>
            <?php }?>

            <?php 
            if (in_array($login_fabrica, array(183))) {
                $sqlCX = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
                $resCX = pg_query($con, $sqlCX);

                if (pg_num_rows($resCX) > 0) {
                    $os_campos_adicionais = json_decode(pg_fetch_result($resCX, 0, "campos_adicionais"), true);
                    $tipo_frete  = $os_campos_adicionais["tipo_frete"];

                }

                ?>
                <td class="titulo" height='15' width='100' ><?echo traduz("tipo.de.frete",$con,$cook_idioma);?></td>
                <td class="conteudo" height='15' >&nbsp;<? echo $tipo_frete; ?></td>
            <?php }?>
        </tr>
        <? if (in_array($login_fabrica, array(169,170)) AND strlen($motivo_visita) > 0) {?>
                <tr>
                    <td class="titulo"><?= traduz("motivo.da.s.visita.s") ?></td>
                    <td class="conteudo" colspan="5"><?= $motivo_visita; ?></td>
                </tr>
        <? }

        if (in_array($login_fabrica, array(156))) {
            $select_os_campos_adicionais = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
            $res_os_campos_adicionais = pg_query($con, $select_os_campos_adicionais);

            if (pg_num_rows($res_os_campos_adicionais) > 0) {
                $json_os_campos_adicionais = json_decode(pg_fetch_result($res_os_campos_adicionais, 0, "campos_adicionais"), true);
                $nf_envio       = $json_os_campos_adicionais["nf_envio"];
                $data_nf_envio  = $json_os_campos_adicionais["data_nf_envio"];
                $valor_nf_envio = $json_os_campos_adicionais["valor_nf_envio"];

                $nf_mo       = $json_os_campos_adicionais["nota_fiscal_mo"];
                $data_nf_mo  = $json_os_campos_adicionais["data_nota_fiscal_mo"];
                $valor_nf_mo = $json_os_campos_adicionais["valor_nota_fiscal_mo"];

                $nf_peca       = $json_os_campos_adicionais["nota_fiscal_peca"];
                $data_nf_peca  = $json_os_campos_adicionais["data_nota_fiscal_peca"];
                $valor_nf_peca = $json_os_campos_adicionais["valor_nota_fiscal_peca"];

                $nf_retorno       = $json_os_campos_adicionais["nf_retorno"];
                $data_nf_retorno  = $json_os_campos_adicionais["data_nf_retorno"];
                $valor_nf_retorno = $json_os_campos_adicionais["valor_nf_retorno"];
            }

            if ($login_posto_interno == true) {
            ?>
                <tr>
                    <td class="titulo"><?= traduz("natureza.de.operacao") ?></td>
                    <td class="conteudo"><?=$natureza_operacao?></td>
                    <td class="titulo"><?= traduz("tipo.de.os") ?></td>
                    <td class="conteudo"><?=$tipo_os_descricao?></td>
                </tr>
                <tr>
                    <td class="titulo" height='15' width='100' >NF MO</td>
                    <td class="conteudo" height='15' ><?=$nf_mo?></td>

                    <td class="titulo" height='15' width='100' ><?= traduz("data.nf.mo") ?></td>
                    <td class="conteudo" height='15' colspan="3"><?=$data_nf_mo?></td>

                    <td class="titulo" height='15' width='100' ><?= traduz("valor.nf.mo") ?></td>
                    <td class="conteudo" height='15' colspan="3"><?=$valor_nf_mo?></td>
                </tr>
                <tr>
                    <td class="titulo" height='15' width='100' ><?= traduz("nf.peca") ?></td>
                    <td class="conteudo" height='15' ><?=$nf_peca?></td>

                    <td class="titulo" height='15' width='100' ><?= traduz("data.nf.peca") ?></td>
                    <td class="conteudo" height='15' colspan="3"><?=$data_nf_peca?></td>

                    <td class="titulo" height='15' width='100' ><?= traduz("valor.nf.peca") ?></td>
                    <td class="conteudo" height='15' colspan="3"><?=$valor_nf_peca?></td>
                </tr>
                <tr>
                    <td class="titulo" height='15' width='100' ><?= traduz("valor.nf.retorno") ?></td>
                    <td class="conteudo" height='15' ><?=$nf_retorno?></td>

                    <td class="titulo" height='15' width='100' ><?= traduz("data.nf.retorno") ?></td>
                    <td class="conteudo" height='15' colspan="3"><?=$data_nf_retorno?></td>

                    <td class="titulo" height='15' width='100' ><?= traduz("valor.nf.retorno") ?></td>
                    <td class="conteudo" height='15' colspan="3"><?=$valor_nf_retorno?></td>
                </tr>
            <?php
            }

            if ($login_posto_interno == true || $aux_reparo_produto == "t") {
            ?>
                <tr>
                    <td class="titulo" height='15' width='100' >NF <?=($login_posto_interno == true) ? "Recebimento" : "Envio"?></td>
                    <td class="conteudo" height='15' ><?=$nf_envio?></td>

                    <td class="titulo" height='15' width='100' >Data NF <?=($login_posto_interno == true) ? "Recebimento" : "Envio"?></td>
                    <td class="conteudo" height='15' colspan="3"><?=$data_nf_envio?></td>

                    <td class="titulo" height='15' width='100' ><?= traduz("valor.nf") ?> <?=($login_posto_interno == true) ? "Recebimento" : "Envio"?></td>
                    <td class="conteudo" height='15' colspan="3"><?=$valor_nf_envio?></td>
                </tr>
            <?php
            }
        }

        if ($login_fabrica == 158) {
        ?>
            <tr>
                <td class="titulo" ><?= traduz("inicio.do.atendimento") ?></td>
                <td class="conteudo" ><?=$inicio_atendimento?></td>
                <td class="titulo" ><?= traduz("fim.do.atendimento") ?></td>
                <td class="conteudo" ><?=$fim_atendimento?></td>
                <td class="titulo" ><?= traduz("amperagem") ?></td>
                <td class="conteudo" ><?=$amperagem?></td>
            </tr>
        <?php
        }
        ?>
    </table>
<?php }?>

<?php if (in_array($login_fabrica, array(169,170))) {
    $obs_adicionais = json_decode($obs_adicionais, true);
    $xobs_adicionais = $obs_adicionais['solicitacao_postagem_posto'];

    if(strlen(trim($xobs_adicionais)) > 0 OR strlen(trim($numero_pac)) > 0){
?>
        <table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
            <tr class='inicio'>
                <td><?= traduz("informacoes.da.devolucao.do.produto.para.o.consumidor") ?></td>
            </tr>
            <tr>
                <td class='titulo' colspan="2"><?= traduz("numero.de.postagem") ?></td>
                <td class='conteudo'><?=$xobs_adicionais; ?></td>
                <td class='titulo' ><?= traduz("codigo.de.rastreio") ?></td>
                <td class='conteudo'><?=$numero_pac; ?></td>
            </tr>
        </table>
<?php
    }
}

if (in_array($login_fabrica, array(80))) {

	if(strlen(trim($numero_pac)) > 0){
?>
		<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
			<tr class='inicio'>
				<td colspan='5'>INFORMAÇÕES RETORNO PRODUTO</td>
			</tr>
			<tr>
				<td class='titulo' width='130'>CÓDIGO DE RASTREIO</td>
				<td class='conteudo' colspan='4'><a href='./relatorio_faturamento_correios.php?conhecimento=<?=$numero_pac?>' rel='shadowbox'><?=$numero_pac?></a></td>
			</tr>
		</table>
<?php
	}
}
?>

<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>

    <?
    #######CONTEUDO ADICIONAL LENOXX - SÓ PARA O POSTO: 14254 - JUNDSERVICE    ###############
    if((($login_posto==14254)and( in_array($login_fabrica, array(11,172)) )) OR $login_fabrica == 96){?>
        <tr >
            <TD class="titulo" colspan='2' height='15' ><?echo traduz("nota.fiscal.saida",$con,$cook_idioma);?></TD>
            <TD class="conteudo" colspan='1' height='15' >&nbsp;<? echo $nota_fiscal_saida; ?></TD>
            <TD class="titulo" height='15' ><? echo traduz("data.nf.saida",$con,$cook_idioma);?></TD>
            <TD class="conteudo" colspan='3' height='15' >&nbsp;<? echo $data_nf_saida; ?></TD>
        </tr>
    <?}
    ################  FIM CONTEUDO LENOXX ##################

    ################## CONTEUDO LENOXX ##################
    if( in_array($login_fabrica, array(11,172)) ){
        if(strlen($troca_garantia_admin)>0){
            $sql = "SELECT login,nome_completo
                    FROM tbl_admin
                    WHERE admin = $troca_garantia_admin";
            $res2 = pg_query ($con,$sql);

            if (pg_num_rows($res2) > 0) {
                $login                = pg_fetch_result ($res2,0,login);
                $nome_completo        = pg_fetch_result ($res2,0,nome_completo);
                ?>
                    <TR>
                        <TD class="titulo"  height='15' ><?fecho("usuarios",$con,$cook_idioma);?></TD>
                        <TD class="conteudo" height='15' colspan='3'>&nbsp;<? if($nome_completo )echo $nome_completo; else echo $login;  ?></TD>
                        <TD class="titulo" height='15'><?fecho("data",$con,$cook_idioma);?></TD>
                        <TD class="conteudo" height='15' colspan="3">
                        <? echo $troca_garantia_data ?></TD>
                    </TR>
                    <TR>
                        <TD class="conteudo"  height='15' colspan='8'>
                        <?
                        if($troca_garantia=='t')
                            echo '<b><center>'.traduz("troca.direta",$con,$cook_idioma).'</center></b>';
                        else
                            echo '<b><center>'.traduz("troca.via.distribuidor",$con,$cook_idioma).'</center></b>';
                        ?>
                        </TD>
                    </TR>
        <?
            }
        }
    }
    ################ FIM CONTEUDO LENOXX ##################
    if(in_array($login_fabrica,array(50,74,165)) AND strlen($os) > 0){ // HD 79844
        if($login_fabrica == 50){ //HD-3263360
            $sql2="SELECT CASE
                         WHEN tbl_os_extra.data_fabricacao IS NOT NULL THEN
                             TO_CHAR(tbl_os_extra.data_fabricacao, 'DD/MM/YYYY')
                         ELSE
                             TO_CHAR(tbl_numero_serie.data_fabricacao, 'DD/MM/YYYY')
                         END AS data_fabricacao
                    FROM tbl_os
                    JOIN tbl_numero_serie USING (serie,produto)
                    JOIN tbl_os_extra USING (os)
                    WHERE os=$os ";
            $res2=pg_query($con,$sql2);
        }else{
            $sql2="SELECT to_char(data_fabricacao,'DD/MM/YYYY') as data_fabricacao
                    FROM tbl_os
                    JOIN tbl_numero_serie USING (serie,produto)
                    WHERE os=$os ";
            $res2=pg_query($con,$sql2);
        }

        if(pg_num_rows($res2) > 0){
            $data_fabricacao = pg_fetch_result($res2,0,data_fabricacao);
    	}else{
    		$sql2="SELECT to_char(data_fabricacao,'DD/MM/YYYY') as data_fabricacao
    			FROM tbl_os
    			JOIN tbl_numero_serie ON tbl_os.produto = tbl_numero_serie.produto and tbl_os.serie = substr(tbl_numero_serie.serie,1,length(tbl_numero_serie.serie) -1)
    			WHERE os=$os
    			AND   tbl_numero_serie.fabrica = $login_fabrica
    			AND   tbl_os.fabrica = $login_fabrica
    			AND data_fabricacao between '2013-07-25' and '2013-09-13'";
    		$res2=pg_query($con,$sql2);
    		if(pg_num_rows($res2) > 0){
    		    $data_fabricacao = pg_fetch_result($res2,0,data_fabricacao);
    		}
    	}
    }

    //HD 671828
    if ($login_fabrica == 91  or in_array($login_fabrica,[120,201]) or $login_fabrica == 131) {
	    $formato_data = ($login_fabrica == 131) ? "MM/YYYY" : "DD/MM/YYYY";
        $sql = "SELECT TO_CHAR(data_fabricacao, '$formato_data') AS data_fabricacao FROM tbl_os_extra WHERE os=$os";
        $res2 = pg_query($con, $sql);
        $data_fabricacao = pg_result($res2, 0, 'data_fabricacao');
    }

    if( in_array($login_fabrica, array(11,59,172)) && strlen($os) > 0){ // HD 79844
        $sql2="SELECT versao
                FROM tbl_os
                WHERE os=$os ";
        $res2=pg_query($con,$sql2);
        if(pg_num_rows($res2) > 0){
            $versao = pg_fetch_result($res2,0,versao);
        }
    }

    if($login_fabrica == 1 and $tipo_atendimento ==18) {
        $sql = " SELECT total_troca
                FROM tbl_os_troca
                WHERE os = $os";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){
            $total_troca = pg_fetch_result($res,0,total_troca);
        }
    }
    if (in_array($login_fabrica, array(30,50,162,190))) {

        $sqlV = "SELECT to_char(data,'DD/MM/YYYY') AS data_agendamento FROM tbl_os_visita WHERE os = {$os} ORDER BY data";
        $resV = pg_query($con,$sqlV);

        $sqlBuscaAdmin = "SELECT  tbl_os.cliente_admin                                    AS cliente_admin        ,
                    tbl_cliente_admin.nome                                  AS cliente_admin_nome   ,
                    TO_CHAR(tbl_os.visita_agendada,'DD/MM/YYYY')            AS data_agendamento     ,
                    tbl_os.observacao                                       AS cliente_admin_obs    ,
                    tbl_hd_chamado_extra.array_campos_adicionais
            FROM    tbl_os
            JOIN    tbl_os_extra USING(os)
            JOIN    tbl_hd_chamado_extra USING(os)
            LEFT JOIN    tbl_cliente_admin USING(cliente_admin)
            WHERE   tbl_os.os = $os
        ";
        $resBuscaAdmin = pg_query($con,$sqlBuscaAdmin);
        $cliente_admin      = pg_fetch_result($resBuscaAdmin,0,cliente_admin);
        $cliente_admin_nome = pg_fetch_result($resBuscaAdmin,0,cliente_admin_nome);
        $data_agendamento   = pg_fetch_result($resBuscaAdmin,0,data_agendamento);
        $cliente_admin_obs  = pg_fetch_result($resBuscaAdmin,0,cliente_admin_obs);
        $data_limite        = pg_fetch_result($resBuscaAdmin,0,array_campos_adicionais);
        $data_limite        = json_decode($data_limite);

        if (!empty($cliente_admin) && $login_fabrica != 50) {
?>
        <tr>
            <td class='inicio' height='15' colspan="6">&nbsp;<?= traduz("atendimento.centralizado") ?>&nbsp;</td>
        </tr>
        <tr>
            <td class="titulo"><?= traduz("cliente") ?></td>
            <td class="conteudo" colspan="3"><?=$cliente_admin_nome?></td>
            <td class="titulo"><?= traduz("data.limite") ?></td>
            <td class="conteudo vermelho" colspan="3"><?=$data_limite->data_limite?></td>
        </tr>
        <?php
        }
        if(pg_num_rows($resV) > 0 && !in_array($login_fabrica, array(30))){
            for($j = 0; $j < pg_num_rows($resV); $j++){
                $data_agendamento = pg_fetch_result($resV, $j, 'data_agendamento');
                $ln = $j + 1;
        ?>
                <tr>
                    <td class="titulo" nowrap><?= traduz("data.agendamento") ?> <?=$ln?></td>
                    <td class="conteudo" colspan="7"><?=$data_agendamento?></td>
                </tr>
        <?php
            }
        }
        if (strlen($cliente_admin_obs) > 0) { ?>
            <tr>
                <td class="titulo"><?= traduz("observacao.do.callcenter") ?></td>
                <td class="conteudo" colspan="7"><?=$cliente_admin_obs?></td>
            </tr>
        <? }
    }
    if (in_array($login_fabrica, array(158))) { ?>
        <tr>
            <?
            $sqlInteg = "SELECT *
                        FROM tbl_hd_chamado_cockpit
                        LEFT JOIN tbl_routine_schedule_log USING(routine_schedule_log)
                        WHERE fabrica = {$login_fabrica}
                        AND hd_chamado = (
                            SELECT hd_chamado
                            FROM tbl_os
                            WHERE os = {$os}
                        )";
            $resInteg = pg_query($con, $sqlInteg);

            if (pg_num_rows($resInteg) > 0) {
                $dadosIntegracao = pg_fetch_all($resInteg);
                $dadosJSON = json_decode($dadosIntegracao[0]['dados'], true); 

                $empresa = mb_strtoupper($dadosJSON['empresa']); 
                $empr = "CLIENTE";

                ?>
               
                    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
                        <thead class="Tabela inicio">
                            <th colspan="10"><?= traduz("informacoes.da.integracao") ?></th>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="titulo2" style="text-align: right;"><?= traduz("arquivo") ?>:</td>
                                <td class="conteudo"><?= $dadosIntegracao[0]['file_name']; ?></td>
                                <td class="titulo2" style="text-align: right;"><?= traduz("centro.distribuidor") ?>:</td>
                                <td class="conteudo"><?= $dadosJSON['centroDistribuidor']; ?></td>
                            </tr>
                            <tr>
                                <td class="titulo2" style="text-align: right;">OS <?=$empr?>:</td>
                                <td class="conteudo"><?= $dadosJSON['osKof']; ?></td>
                                <td class="titulo2" style="text-align: right;"><?= traduz("data.abertura") ?> <?=$empr?>:</td>
                                <td class="conteudo"><?= $dadosJSON['dataAbertura']; ?></td>
                            </tr>
                            <tr>
                                <td class="titulo2" style="text-align: right;"><?= traduz("data.processamento") ?>:</td>
                                <td class="conteudo"><?= date("d/m/Y H:i:s", strtotime($dadosIntegracao[0]['create_at'])); ?></td>
                                <td class="titulo2" style="text-align: right;"><?= traduz("numero.da.matricula.do.cliente") ?>:</td>
                                <td class="conteudo"><?= $dadosJSON['idCliente']; ?></td>
                            </tr>
                            <?php if (array_key_exists('longitude', $dadosJSON) and array_key_exists('latitude', $dadosJSON)): ?>
                            <tr>
                                <td class="titulo2" style="text-align: right;">LONGITUDE</td>
                                <td class="conteudo"><?= $dadosJSON['longitude'] ?></td>
                                <td class="titulo2" style="text-align: right;">LATITUDE</td>
                                <td class="conteudo"><?= $dadosJSON['latitude'] ?></td>
                            </tr>
                            <?php endif ?>
                            <tr>
                                <td class="titulo2" style="text-align: right;"><?= traduz("comentario") ?> <?=$empr?>:</td>
                                <td class="conteudo" colspan="3"><?= utf8_decode($dadosJSON['comentario']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                
            <?  } ?>
        </tr>
        <tr>
            <? if (!empty($os_campos_adicionais['unidadeNegocio'])) {
                $unidade_negocio = $os_campos_adicionais['unidadeNegocio'];
				if(is_array($unidade_negocio)) $unidade_negocio = $unidade_negocio[0];

				if(strpos($unidade_negocio,'-') === false) {	
					$sql = "
						SELECT DISTINCT
							tbl_unidade_negocio.nome   AS cidade,
							tbl_unidade_negocio.codigo
						FROM tbl_distribuidor_sla
						JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo = tbl_distribuidor_sla.unidade_negocio
						WHERE tbl_distribuidor_sla.fabrica = {$login_fabrica}
						AND tbl_distribuidor_sla.unidade_negocio = '{$unidade_negocio}';
					";
					$res = pg_query($con,$sql);

					$unidade_negocio_cidade =  pg_fetch_result($res, 0, codigo) . " - " . pg_fetch_result($res, 0, cidade);
               	}else{
					$unidade_negocio_cidade = $unidade_negocio;
				}

                ?>
            <table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
                <td class='titulo' height='15'><?= traduz("unidade.de.negocio") ?></td>
                <td class="conteudo" colspan="7"><?= strtoupper($unidade_negocio_cidade); ?></td>
            </table>
            <? } ?>
        </tr>
    <? } ?>

    <?php if ($login_fabrica == 190) {
        $sqlContrato = "SELECT tbl_contrato_os.contrato FROM tbl_contrato JOIN tbl_contrato_os ON tbl_contrato_os.contrato = tbl_contrato.contrato
        WHERE tbl_contrato.fabrica={$login_fabrica} 
        AND tbl_contrato_os.os = {$os}";
        $resContrato = pg_query($con, $sqlContrato);
        if (pg_num_rows($resContrato) > 0) {
            $xxcontrato = pg_fetch_result($resContrato, 0, 'contrato');
    ?>
        <tr>
            <td class="titulo"><?= traduz("Nº Contrato") ?></td>
            <td class="conteudo" colspan="7"><?=$xxcontrato?></td>
        </tr>
    <?php }?>
    <?php }?>
</table>
<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
    <tr>
        <td class='inicio' height='15' colspan='8'>&nbsp;<?= traduz("informacoes.do.produto",$con,$cook_idioma);?></td>
    </tr>

    <?php
        if(in_array($login_fabrica, array(138,142,143,145,146,158))){

            $sql_cont = "SELECT COUNT(*) AS cont FROM tbl_os_produto WHERE os = {$os}";
            $res_cont = pg_query($con, $sql_cont);

            $num_os = pg_fetch_result($res_cont, 0, 'cont');

            if($num_os > 1){
                $cond_order_by = "ORDER BY tbl_os_produto.os_produto ASC LIMIT 1";
            }

            $sql_produto = "SELECT
                                        tbl_produto.referencia,
                                        tbl_produto.descricao,
                                        tbl_os_produto.serie,
                                        tbl_produto.produto
                                    FROM tbl_produto
                                    JOIN tbl_os_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_os_produto.os = {$os}
                                    $cond_order_by";
            $res_produto = pg_query($con, $sql_produto);
            $produto_referencia = pg_fetch_result($res_produto, 0, 'referencia');
            $produto_descricao = pg_fetch_result($res_produto, 0, 'descricao');
            $serie = pg_fetch_result($res_produto, 0, 'serie');
            $produto = pg_fetch_result($res_produto, 0, 'produto');

            if(in_array($login_fabrica,array(138)) AND strlen($serie) > 0){
                $sql = "SELECT
                                tbl_numero_serie.cnpj,
                                tbl_revenda.nome,
                                to_char(data_venda,'DD/MM/YYYY') AS data_venda
                            FROM tbl_numero_serie
                            JOIN tbl_os_produto ON tbl_os_produto.produto = tbl_numero_serie.produto
                            LEFT JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_numero_serie.cnpj
                            WHERE tbl_os_produto.os = {$os}
                            AND tbl_numero_serie.fabrica = {$login_fabrica}
                            AND tbl_numero_serie.serie = '{$serie}'";
                $resS = pg_query($con,$sql);

                $data_venda_serie = pg_fetch_result($resS,0,'data_venda');
                $cnpj_serie       = pg_fetch_result($resS,0,'cnpj');
                $nome_revenda_serie = pg_fetch_result($resS,0,'nome');
            }
        }

        ?>

    <tr>
        <? //MLG - 03/06/2011 - HD 675023
        if ($login_fabrica == 96) { ?>
            <TD class="titulo" height='15' width='90'><?echo traduz("modelo",$con,$cook_idioma);?></TD>
            <TD class="conteudo" height='15' >&nbsp;<? echo $produto_modelo ?></TD>
            <TD class="titulo" height='15' width='90'><? echo traduz("n.de.serie",$con,$cook_idioma);?>&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $serie ?>&nbsp;</TD>
            <TD class="titulo" height='15' width='100'><?echo traduz("referencia",$con,$cook_idioma);?></TD>
            <TD class="conteudo" height='15' width='100'>&nbsp;<? echo $produto_referencia ?></TD>
        </tr>
        <tr>
            <TD class="titulo"   colspan='1' height='15' width='90'><?echo traduz("descricao",$con,$cook_idioma);?></TD>
            <TD class="conteudo" colspan='5' height='15' >&nbsp;<? echo $produto_descricao ?></TD>
        <?} else {?>
            <TD class="titulo" height='15' width='90'><?echo traduz("referencia",$con,$cook_idioma);?></TD>
            <TD class="conteudo" height='15' >&nbsp;<? echo $produto_referencia ?> <? echo ($login_fabrica == 171) ? " / " . $produto_referencia_fabrica : ""; ?></TD>
            <TD class="titulo" height='15' width='90'><?echo traduz("descricao",$con,$cook_idioma);?></TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $produto_descricao ?></TD>
            <? if (!in_array($login_fabrica, array(127,145,162,171))) { ?>
                <td class="titulo" height='15' width='90' >
                    <?php
                    if ($login_fabrica == 35) {
                        echo "PO#";
                    } else if (in_array($login_fabrica ,array(137,160))) {
                        echo traduz("n.de.lote",$con,$cook_idioma );
                    } else {
                        echo traduz("n.de.serie",$con,$cook_idioma);
                    }
                    ?>
                </td>
		<?php
                $colspan = ($login_fabrica == 148) ? "colspan='3'" : "";
                $colspan = ($login_fabrica == 178) ? "colspan='6'" : $colspan;
            	?>
                <td class="conteudo" height='15' <?=$colspan?>>
                    <? if (in_array($login_fabrica, array(156,161)) && $sem_ns == "t") {
                            $serie = "Sem número de série";
                    }
                    echo $serie; ?>
                </td>
            <? }

            if ($login_fabrica == 177){
            ?>
                <TD class="titulo" height='15' width='90'><?echo traduz("lote",$con,$cook_idioma);?></TD>
                <td class="conteudo" height='15'>&nbsp;<?=$codigo_fabricacao?></td>
            <?php
            }
            if ($login_fabrica == 175){
            ?>
                <td class="titulo" height='15' width='90' >
                    <?=traduz("qtde.disparos",$con,$cook_idioma);?>&nbsp;
                </td>
                <td class="conteudo" height='15'>
                    <?=$qtde_disparos?>
                </td>
            <?php    
            }
            if ($login_fabrica == 162) {
                switch ($nome_linha) {
                    case "Smartphone":
?>
                <td  class='titulo' height='15' width='90' >IMEI</td>
                <td  class='conteudo'><?=$rg_produto?></td>
<?php
                        break;
                    case "Informatica":
?>
                <td  class='titulo' height='15' width='90' >Nº Série Item Agregado</td>
                <td  class='conteudo'><?=$key_code?></td>
<?php
                        break;
                    default:
?>
                <td class="titulo" height='15' width='90' >
                    <?= traduz("n.de.serie",$con,$cook_idioma) ?>&nbsp;
                </td>
                <td class="conteudo" height='15'>
                    <? echo $serie; ?>
                </td>
<?php
                        break;
                }
            }
            if ($login_fabrica == 171) {
            ?>
                <td class='titulo' height='15' width='50'><?= traduz("pressao.da.agua") ?> (MCA)</TD>
                <td class="conteudo">&nbsp;<? echo $regulagem_peso_padrao; ?></td>
                <td class='titulo' height='15' width='50'><?= traduz("tempo.de.uso") ?> (MÊS)</TD>
                <td class="conteudo">&nbsp;<? echo $qtde_horas; ?></td>
            <?
            }
        }
        if (in_array($login_fabrica, array(156))) { ?>
            <td class="titulo" height="15">VOID</td>
            <td class="conteudo" height="15" colspan="3"><?= $void; ?></td>
        <? } ?>
    <?if ($login_fabrica != 158 and strlen($data_fabricacao) > 0) {?>
        <td class="titulo" height='15' width='90'><?=traduz('data fabricação')?></td>
        <td class="conteudo" height='15'>&nbsp;<? echo $data_fabricacao ?>&nbsp;</td>
    <?}?>

    <?php if(in_array($login_fabrica, [167, 203])){
        if(strstr($descricao_familia, "Impressora") == true || strstr($descricao_familia, "Multifunciona") == true || $login_fabrica == 203){
    ?>
            <td class='titulo' height='15' width='50'><?= ($login_fabrica == 203) ? "Contador/Horas Trabalhadas" : traduz("contador",$con,$cook_idioma);?></TD>
            <td class="conteudo">&nbsp;<? echo $contador ?></td>
    <?php
        }
    }
    ?>
    <?if($login_fabrica==19){?>
        <TD class="titulo" height='15' width='90'><?echo traduz("qtde",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $qtde ?>&nbsp;</TD>
    <?}

    if (in_array($login_fabrica, array(143,190))) {
    ?>
        <TD class="titulo" height='15' width='90'><?=traduz('Horimetro')?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $rg_produto ?>&nbsp;</TD>
    <?php
    }
    if($login_fabrica == 160 or $replica_einhell){
    ?>
        <TD class="titulo" height='15' width='90'><?echo traduz("versão.do.produto",$con,$cook_idioma);?></TD>
        <TD class="conteudo" nowrap>
                <?php
                echo $type;
                ?>
        </TD>
    <?php
    }

    if ((!in_array($login_fabrica,array(14,20,46,52,74,87,91,104,114,115,116,117,120,121,122,123,124,125,126,127,128,129,131,134,136,138,140,141,144,145,143,142,146,147,148,149,150,151,152,153,154,156,157,160,164,165,175,180,181,182,186,190,191,195)) AND ($login_posto==6359 OR $login_posto == 4311) OR ($login_fabrica==6 AND $login_posto==4262)) || in_array($login_fabrica, array(11,172)) and !$replica_einhell) {//HD 317527 - ADICIONEI PARA A FABRICA 11 ?>
        <TD class="titulo" height='15' width='90'><?echo traduz(($login_fabrica == 137) ? "dados.do.produto" : "rg.produto",$con,$cook_idioma);?></TD>
        <TD class="conteudo" nowrap>
            <?php
                if($login_fabrica == 137){
                    $dados = json_decode($rg_produto);
                    echo (!empty($dados->cfop)) ? " CFOP: ".$dados->cfop."<br />" : "-";
                    echo (!empty($dados->vu)) ? " Valor Unitário: R$ ".$dados->vu."<br />" : " -";
                    echo (!empty($dados->vt)) ? " Total Nota: R$ ".$dados->vt : " -";
                }else{
                    echo (strlen(trim($rg_produto)) > 0) ? " &nbsp; ".$rg_produto : '- - -';
                }
                ?>
        </TD>
    <?php } else if ($login_fabrica == 52) { ?>
        <TD class='titulo' height='15'>&nbsp;MARCA</TD>
        <TD class="conteudo" >
            <?
            if (strlen($marca_os) > 0) {
                $sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica AND marca = $marca_os order by nome";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res)>0){
                    for($i=0;pg_num_rows($res)>$i;$i++){
                        $xmarca = pg_fetch_result($res,$i,marca);
                        $xnome = pg_fetch_result($res,$i,nome);
                        echo $xnome;
                   }
                }
            }
        ?>
        </TD>
    <?
    }
        if ($login_fabrica == 176)
        {
?>
            <TD class="titulo"><?= traduz("indice") ?></TD>
            <TD class="conteudo"><?php echo $type; ?></TD>
<?php
        }


    if (in_array($login_fabrica, array(141,144))) { ?>
        <TD class="titulo" height='15' width='90'><?= traduz("valor.unitario") ?> R$</TD>
        <TD class="conteudo" nowrap>
            <?php
                echo number_format($rg_produto, 2, ",", ".");
            ?>
        </TD>
    <? }
    if($login_fabrica == 86 and $serie_justificativa != 'null'){  // HD 328591 ?>
        </tr>
        <tr>
            <td class="titulo" height='15' width='90'>
                <? echo traduz("justificativa.numero.serie",$con,$cook_idioma);?>
            </td>
            <td colspan='7' class="conteudo" height='15'>&nbsp; <? echo $serie_justificativa; ?> </td>
        </tr>
        <tr>
    <?
    }
    if($login_fabrica == 15){ ?>
        <tr>
            <TD class="titulo" height='15' width='90'><?echo traduz("preco.do.produto",$con,$cook_idioma);?></TD>
            <TD colspan='7' class="conteudo" height='15'>&nbsp;<? echo "R$ ".number_format($preco_produto,2,',','.'); ?>&nbsp;</TD>
        </tr>
    <?php
    }

    if ($login_fabrica == 158 && !empty($produto)) {
        $sqlFamilia = "
            SELECT
                f.descricao AS familia_descricao
            FROM tbl_familia f
            JOIN tbl_produto p ON p.familia = f.familia
                AND p.fabrica_i = {$login_fabrica}
            WHERE f.fabrica = {$login_fabrica}
            AND p.produto = {$produto}
        ";
        $resFamilia = pg_query($con, $sqlFamilia);

        $familia_descricao = pg_fetch_result($resFamilia, 0, familia_descricao);
        ?>
        <tr>
            <td class="titulo" ><?=traduz('PATRIMÔNIO')?></td>
            <td class="conteudo" colspan="3" ><?= $serie_justificativa; ?></td>
            <td class="titulo" height="15"><?=traduz('FAMÍLIA')?></td>
            <td class="conteudo" height="15" colspan="4"><?= $familia_descricao; ?></td>
        </tr>
    <?php 
        $sqlPdv = " SELECT JSON_FIELD('pdv_chegada', campos_adicionais) AS pdv_chegada, 
                           JSON_FIELD('pdv_saida', campos_adicionais) AS pdv_saida 
                    FROM tbl_os_campo_extra 
                    WHERE os = $os 
                    AND fabrica = $login_fabrica";
        $resPdv = pg_query($con, $sqlPdv);
        if (pg_num_rows($resPdv) > 0) {
            $pdv_chegada = pg_fetch_result($resPdv, 0, 'pdv_chegada');
            $pdv_saida   = pg_fetch_result($resPdv, 0, 'pdv_saida');

            if (!empty(trim($pdv_chegada)) && !empty(trim($pdv_saida))) {
            ?>
                <tr>
                    <td class="titulo" colspan="3" ><?php echo traduz("Programação no Ato da Chegada PDV: "); ?></td>
                    <td class="conteudo"><?=$pdv_chegada?></td>
                    <td class="titulo" colspan="3"><?php echo traduz("Programação na Saída PDV: "); ?></td>
                    <td class="conteudo"><?=$pdv_saida?></td>
                    
                </tr>
            <?php
            }
        }
    }
    if (in_array($login_fabrica, array(169,170))) {
        if (strlen($recolhimento) > 0 && strlen($produto_emprestimo) > 0) {
            $colspanRetEmp = 2;
        } else {
            $colspanRetEmp = 5;
        } ?>
        <tr>
            <? if (strlen($recolhimento) > 0) { ?>
                <td class="titulo" colspan="<?= $colspanRetEmp; ?>"><?= traduz("produto.retirado.para.a.oficina") ?></td>
                <td class="conteudo"><?= ($recolhimento == "t") ? "Sim" : "Não"; ?></td>
            <? }
            if (strlen($produto_emprestimo) > 0) { ?>
                <td class="titulo" colspan="<?= $colspanRetEmp; ?>"><?= traduz("emprestimo.de.produto.para.o.consumidor") ?></td>
                <td class="conteudo"><?= ($produto_emprestimo == "t") ? "Sim" : "Não"; ?></td>
            <? } ?>
        </tr>
    <? }

    if($login_fabrica == 164){

        $sql_va = "
            SELECT
                JSON_FIELD('numero_serie_calefator',campos_adicionais) AS numero_serie_calefator,
                JSON_FIELD('numero_serie_interno_placa_motor',campos_adicionais) AS numero_serie_interno_placa_motor,
                JSON_FIELD('cor_indicativa_carcaca',campos_adicionais) AS cor_indicativa_carcaca
            FROM tbl_os_campo_extra
            WHERE
                fabrica = {$login_fabrica}
                AND os = {$os}";

        $res_va = pg_query($con, $sql_va);

        if(pg_num_rows($res_va) > 0){

            $numero_serie_calefator           = pg_fetch_result($res_va,0,"numero_serie_calefator");
            $cor_indicativa_carcaca           = pg_fetch_result($res_va,0,"cor_indicativa_carcaca");
            $numero_serie_interno_placa_motor = pg_fetch_result($res_va,0,"numero_serie_interno_placa_motor");

?>

            <tr>
                <td class="titulo" colspan="3" height="15" ><?= traduz("n.de.serie.do.calefator./.motor") ?></td>
                <td class="conteudo" colspan="3"><?= $numero_serie_calefator; ?></td>
            </tr>
            <tr>
                <td class="titulo" colspan="3" height="15" ><?= traduz("cor.indicada.na.carcaca") ?></td>
                <td class="conteudo" colspan="3" ><?= $cor_indicativa_carcaca; ?></td>
            </tr>
            <tr>
                <td class="titulo" colspan="3" height="15" style="text-transform: uppercase;"><?= traduz("numero.de.serie.interno.placa/motor") ?></td>
                <td class="conteudo" colspan="3" ><?= $numero_serie_interno_placa_motor; ?></td>
            </tr>

<?php
        }
    }

    if($login_fabrica==14 AND ($login_posto==6359 OR $login_posto == 7214)){
    ?>
        <TD class="titulo" height='15' width='90'><?echo traduz("numero.controle",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<?= $numero_controle; ?>&nbsp;</TD>
    <?}
    if(in_array($login_fabrica, array(59))){?>
        <TD class="titulo" height='15' width='90'><?= traduz("versao") ?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $versao ?>&nbsp;</TD>
    <?}?>
    </tr>
    <? if ($login_fabrica == 1) { ?>
    <tr>
        <TD class="titulo" height='15' width='90'><?echo traduz("voltagem",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $produto_voltagem ?></TD>
        <TD class="titulo" height='15' width='110'><?echo traduz("codigo.fabricacao",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $codigo_fabricacao ?></TD>

        <?if($tipo_atendimento == 18 and strlen($total_troca) > 0) { ?>
        <TD class="titulo" height='15' width='110' style='font-weight:bold;' nowrap><?= traduz("valor.da.troca.faturada") ?>&nbsp;</TD>
        <TD class="conteudo" height='15' style='font-weight:bold; color:red'>R$&nbsp;<? echo number_format($total_troca,2,",","."); ?></TD>
        <? } else { ?>
        <TD class="conteudo" height='15' colspan='2'></TD>
        <? } ?>
    </tr>
    <? } ?>

   <? if(in_array($login_fabrica,array(138))){ ?>
	<tr>
		<td class="titulo"><?echo traduz("cnpj.venda",$con,$cook_idioma);?></td>
		<td class="conteudo"><?=$cnpj_serie?></td>
		<td class="titulo"><?echo traduz("nome.revenda",$con,$cook_idioma);?></td>
        <td class="conteudo"><?=$nome_revenda_serie?></td>
		<td class="titulo"><?echo traduz("data.venda",$con,$cook_idioma);?></td>
		<td class="conteudo"><?=$data_venda_serie?></td>
	</tr>
   <?
    }

    if ($login_fabrica == 148) {
    ?>
        <tr>
            <td class="titulo" ><?= traduz("n.de.serie.motor") ?></td>
            <td class="conteudo" ><?=$serie_motor?></td>
            <td class="titulo" ><?= traduz("n.de.serie.transmissao") ?></td>
            <td class="conteudo" ><?=$serie_transmissao?></td>
            <td class="titulo" ><?= traduz("horimetro") ?></td>
            <td class="conteudo" ><?=$os_horimetro?></td>
            <td class="titulo" ><?= traduz("revisao") ?></td>
            <td class="conteudo" ><?=$os_revisao?></td>
        </tr>
    <?php
    }

     if($login_fabrica == 178){
?>
                <tr>
                        <td class="titulo" colspan="2">TROCA DE PRODUTO SOLICITADA:</td>
                        <td class="conteudo" colspan="1"><?= ($troca_garantia == "t") ? "Sim" : "Não" ?></td>
                        <td class="titulo" colspan="2">MARCA DO PRODUTO:</td>
                        <td class="conteudo" colspan="2"><?= $marca_troca ?></td>
                        <td class="titulo" colspan="2">ENVIAR PARA:</td>
                        <td class="conteudo" colspan="4">
                        <?php

                                switch($enviar_para){
                                        case 'C': echo "Consumidor"; break;
                                        case 'P': echo "Posto Autorizado"; break;
                                        case 'R': echo "Revenda"; break;
                                }
                        ?>
                        </td>
                </tr>

<?php
        }

    ?>
</table>
    
<?php
    if (!in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
        <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
            <TR>
                <TD class='titulo' height='15' width='150'>BOX / PRATELEIRA</TD>
                <TD class="conteudo">&nbsp;<? echo $box_prateleira; ?></TD>
            </TR>
        </TABLE>
    <?php } 
if(in_array($login_fabrica, array(164,165)) && $posto_interno == true){

    $sql_nf = "SELECT nota_fiscal_saida, data_nf_saida FROM tbl_os WHERE os = {$os}";
    $res_nf = pg_query($con, $sql_nf);

    if(pg_num_rows($res_nf) > 0){

        $nota_fiscal_saida = pg_fetch_result($res_nf, 0, "nota_fiscal_saida");
        $data_nf_saida     = pg_fetch_result($res_nf, 0, "data_nf_saida");

        if(strlen($nota_fiscal_saida) > 0 && strlen($data_nf_saida) > 0){

            list($ano, $mes, $dia) = explode("-", $data_nf_saida);
            $data_nf_saida = $dia."/".$mes."/".$ano;

    ?>

            <table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
                <tr>
                    <td class='inicio' height='15' colspan='4'> &nbsp; <?= traduz("informacoes.de.faturamento.produto.consertado") ?>  </td>
                </tr>
                <tr>
                    <td class="titulo" style="width: 25%;" ><?= traduz("nota.fiscal.de.saida") ?></td>
                    <td class="conteudo" style="width: 25%;" ><?=$nota_fiscal_saida?></td>
                    <td class="titulo" style="width: 25%;" ><?= traduz("data.da.nf.de.saida") ?></td>
                    <td class="conteudo" style="width: 25%;" ><?=$data_nf_saida?></td>
                </tr>
            </table>

    <?php

        }

    }

}

?>

<? if ((strlen($aparencia_produto) > 0 AND $login_fabrica <> 20) OR $login_fabrica == 148 ) {# HD-2843341 ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<TR>
    <td class='titulo' height='15' width='150'><?echo traduz("aparencia.do.produto",$con,$cook_idioma);?></TD>
    <td class="conteudo">&nbsp;<? echo $aparencia_produto ?></td>

    <?php 
        if($login_fabrica == 148){
            $sqlns = "SELECT ordem from tbl_numero_serie where referencia_produto  = '$produto_referencia' and serie = '$serie'  and fabrica = $login_fabrica"; 
            $resns = pg_query($con, $sqlns);
            if(pg_num_rows($resns)>0){
                $ordem =pg_fetch_result($resns, 0, 'ordem');
                echo "<td class='titulo' height='15' width='50' >PIN</td>";
                echo "<td class='conteudo'>$ordem</td>";
            }
        }
    ?>

</TABLE>
<? } ?>
<? if (strlen($acessorios) > 0) { 
	$var_label_acessorio = ($login_fabrica == 148) ? "Implemento(s)" : "acessorios.do.aparelho";
?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
    <TD class='titulo' height='15' width='150'><?echo traduz("$var_label_acessorio",$con,$cook_idioma);?></TD>
    <TD class="conteudo">&nbsp;<? echo $acessorios; ?></TD>
</TR>
</TABLE>
<? } ?>

<?php
    $os = $_REQUEST['os'];
	if(in_array($login_fabrica,array(138)) ){
		$model = ModelHolder::init('OsProduto');
		$osProduto = $model->find(array('os'=>$os));
		if($login_fabrica == 138 && count($osProduto) > 1 ){
			foreach($osProduto as $key => $value) {
				if($serie == $value['serie']) {
					unset($osProduto[$key]);
				}
			}
			$sub_array = (array_keys($osProduto));
			$produtoModel = ModelHolder::init('Produto');
			$produto = $produtoModel->select($osProduto[$sub_array[0]]['produto']);
			$htmlBuilder = HtmlBuilder::getInstance();
			$html = array();
			$html[] = array(
				'renderer' => 'table[width=700][border=0][cellspacing=1][cellpadding=0][align=center].Tabela>tbody',
				'content' => array(
					array(
						'renderer' => 'tr>td[height=15][colspan=4].inicio',
						'content' => '&nbsp;INFORMAÇÕES DO SUBCONJUNTO'
					),
					array(
						'renderer' => 'tr',
						'content' => array(
							array(
								'renderer' => 'td[height=15][width=90].titulo',
								'content' => 'REFERÊNCIA',
							),
							array(
								'renderer' => 'td[height=15].conteudo',
								'content' => $produto['referencia'],
							),
							array(
								'renderer' => 'td[height=15][width=90].titulo',
								'content' => 'DESCRIÇÃO;',
							),
							array(
								'renderer' => 'td[height=15].conteudo',
								'content' => $produto['descricao'],
							),
							array(
								'renderer' => 'td[height=15][width=90].titulo',
								'content' => "N. DE SÉRIE",
							),
							array(
								'renderer' => 'td[height=15].conteudo',
								'content' => $osProduto[$sub_array[0]]['serie'],
							),
						)
					)
				)
			);
			$html = $htmlBuilder->build($html);
			$html->render();

		}


		if(strlen($osProduto[$sub_array[0]]['serie']) > 0){
			$sql = "SELECT 	tbl_numero_serie.cnpj,
					tbl_revenda.nome,
					TO_CHAR(data_venda,'DD/MM/YYYY') AS data_venda
				FROM tbl_numero_serie
				JOIN tbl_os_produto ON tbl_os_produto.produto = tbl_numero_serie.produto
				LEFT JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_numero_serie.cnpj
				WHERE tbl_os_produto.os = {$os}
				AND tbl_numero_serie.fabrica = $login_fabrica
				AND tbl_numero_serie.serie = '{$osProduto[$sub_array[0]]['serie']}'";
			$resS = pg_query($con,$sql);

			$data_venda_serie = pg_fetch_result($resS,0,'data_venda');
			$cnpj_serie       = pg_fetch_result($resS,0,'cnpj');
			$nome_revenda_serie       = pg_fetch_result($resS,0,'nome');
		}
		 ?>
		<table width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
			<tr>
				<td class="titulo"><?echo traduz("cnpj.venda",$con,$cook_idioma);?></td>
				<td class="conteudo"><?=$cnpj_serie?></td>
				<td class="titulo"><?echo traduz("nome.revenda",$con,$cook_idioma);?></td>
                                <td class="conteudo"><?=$nome_revenda_serie?></td>
				<td class="titulo"><?echo traduz("data.venda",$con,$cook_idioma);?></td>
				<td class="conteudo"><?=$data_venda_serie?></td>
			</tr>
		</table>
		<?

	}
?>
<? if (strlen($defeito_reclamado) > 0 || in_array($login_fabrica, array(11,172))) {
    $sql = "
        SELECT
            tbl_defeito_reclamado.codigo,
            tbl_defeito_reclamado.descricao
        FROM tbl_defeito_reclamado
        WHERE tbl_defeito_reclamado.defeito_reclamado = {$defeito_reclamado};
    ";
    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {
        //HD 172561 - mostrar o defeito_reclamado_descricao em um campo e o
        $codigo_defeito_reclamado = pg_fetch_result($res,0,codigo);
        $descricao_defeito = trim(pg_fetch_result($res,0,descricao));
    }
    if (($login_fabrica < 91 AND !in_array($login_fabrica, array(20,50))) || in_array($login_fabrica, array(172))) { #HD-2843341 ?>
        <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
            <tr>
                <td class='titulo' height='15'width='300'>&nbsp;<?echo traduz("informacoes.sobre.o.defeito",$con,$cook_idioma);?></TD>
                <td class="conteudo" >&nbsp;
                    <? if ($login_fabrica == 3) {
                        echo $defeito_reclamado_descricao;
                    } else if ($login_fabrica == 19) {
                        $sql = "
                            SELECT DISTINCT tbl_defeito_reclamado.codigo,tbl_defeito_reclamado.descricao
                            FROM tbl_os_defeito_reclamado_constatado
                            JOIN tbl_defeito_reclamado USING(defeito_reclamado)
                            WHERE os = {$os}
                        ";
                        $res = pg_query ($con,$sql);

                        $array_integridade_reclamado = array();

                        if(@pg_num_rows($res)>0){
                            for ($i=0;$i<pg_num_rows($res);$i++){
                                $aux_defeito_reclamado = pg_fetch_result($res,$i,1);
                                array_push($array_integridade_reclamado,$aux_defeito_reclamado);
                            }
                        }
                        $lista_defeitos_reclamados = implode(", ",$array_integridade_reclamado);
                        echo "$lista_defeitos_reclamados";
                    } else {
                        if ($login_fabrica == 52) {
                            echo $descricao_defeito;
                        } else {
                            echo $descricao_defeito;
                            if(!empty($defeito_reclamado_descricao_os) AND $defeito_reclamado_descricao_os != 'null'){
                                echo " - ".$defeito_reclamado_descricao_os;
                            }
                        }
                    } ?>
                </td>
                <?php if(in_array($login_fabrica, array(11,172))){ ?>
                    <TD class="titulo" height='15' width='110'><?= traduz("codigo.interno") ?></TD>
                    <TD class="conteudo" height='15'>&nbsp;<?php echo $versao; ?>&nbsp;</TD>
                <?php } ?>
            </tr>
        </table>
    <? }
}
if ($login_fabrica == 19 && (strlen($fabricacao_produto) > 0 || strlen($qtde_km) > 0)) { ?>
    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
        <tr>
            <td class='titulo' height='15' width='300'><?= traduz("mes.e.ano.de.fabricacao.do.produto") ?></td>
            <td class="conteudo" >&nbsp;<?echo $fabricacao_produto;?></td>
            <td class='titulo' height='15' width='100'><?= traduz("quilometragem") ?> </td>
            <td class="conteudo" >&nbsp;<?echo $qtde_km;?></td>
        </tr>
    </table>
<? }
if (in_array($login_fabrica, array(2,6,30,59,94,144))) { ?>
    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
        <tr>
            <? if (!in_array($login_fabrica, array(59,94))) { ?>
                    <td class="titulo" height='15' width='300' align='right'><?
                        if (in_array($login_fabrica, [144])) echo traduz("Número Único");
                        else echo traduz("os.revendedor",$con,$cook_idioma);?></td>
                    <td class="conteudo"><?= $os_posto ?></td>
            <? }
            if (in_array($login_fabrica, array(30,59,94))) {
                // HD 415550
                if ($login_fabrica == 94) {
                    $sql = "SELECT posto
                            FROM tbl_posto_fabrica
                            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.posto_interno
                            WHERE tbl_posto_fabrica.fabrica = " . $login_fabrica . "
                            AND tbl_posto_fabrica.posto = " . $login_posto;
                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res)) {
                        $posto_interno = true;
                    }
                }
                $width_tecnico = ($posto_interno === true ) ? 300 : 100;
                $width_tecnico2 = ($posto_interno === true) ? 217 : 'auto';

                if(($login_fabrica == 94 && $posto_interno === true) || $login_fabrica != 94) { ?>
                    <td class="titulo" height="15" width="<?=$width_tecnico;?>" align="right">Técnico</td>
                    <td class="conteudo" width='<?=$width_tecnico2;?>'>&nbsp;<?= $tecnico_nome ?>&nbsp;</td>
                <? }
                // HD 415550
                if($login_fabrica == 94 && $posto_interno === true) {

                    $sql = "SELECT mao_de_obra FROM tbl_os WHERE os = $os";
                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res)) {

                        echo '<td class="titulo">MÃO-DE-OBRA&nbsp;</td>
                              <td class="conteudo" align="right">&nbsp;R$ '.number_format( pg_result($res,0,0),2,',','.' ).'</td>';

                    }

                }

            ?>
            <?}?>
            <?php if($login_fabrica == 6 AND strtoupper($nome_linha) == "TABLET"){ ?>
            <TD class="titulo" height='15' width='150' align='right'>
                <? echo traduz("numero.correios",$con,$cook_idioma);?>
            </TD>
            <TD class="conteudo" >&nbsp;<? echo $obs_adicionais; ?>&nbsp;</TD>
            <?php } ?>
        </TR>
    </table>
<?}?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <TR>
        <TD  height='15' class='inicio' colspan='4'>&nbsp;<?echo traduz($tema_titulo,$con,$cook_idioma);?></TD>
    </TR>
    <TR>
        <?php if($login_fabrica <> 20 AND !$defeitoReclamadoCadastroDefeitoReclamadoCliente){ #HD-2843341 ?>
        <TD class="titulo" height='15' width='90'><?echo traduz("reclamado",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15' width='140' <?if (in_array($login_fabrica,array(30,43,85,114,152,157,180,181,182,190,195)) || (isset($usaDefeitoReclamadoCadastro) && isset($novaTelaOs))) echo "colspan=4"; else if ($login_fabrica == 160 or $replica_einhell) echo "colspan='3'";?>>
            <?
            // HD 22820
            if ($login_fabrica == 1) {
                if($troca_garantia == 't' or $troca_faturada == 't')
                    echo $descricao_defeito;
                else
                    echo $descricao_defeito;

                if(!empty($defeito_reclamado_descricao) AND $defeito_reclamado_descricao != 'null')
                    echo " - ".$defeito_reclamado_descricao;

            }elseif($login_fabrica == 19){
                $sql = "SELECT DISTINCT tbl_defeito_reclamado.codigo,tbl_defeito_reclamado.descricao

                    FROM tbl_os_defeito_reclamado_constatado
                    JOIN tbl_defeito_reclamado USING(defeito_reclamado)
                    WHERE os = {$os}";
                $res = pg_query ($con,$sql);
                $array_integridade_reclamado = array();

                if (@pg_num_rows($res) > 0) {
                    for ($i=0;$i<pg_num_rows($res);$i++){
                        $aux_defeito_reclamado = pg_fetch_result($res,$i,1);
                        array_push($array_integridade_reclamado,$aux_defeito_reclamado);
                    }
                }
                $lista_defeitos_reclamados = implode(", ", $array_integridade_reclamado);
                echo "$lista_defeitos_reclamados";

            } else {
                if ($login_fabrica == 158) {
                    echo $codigo_defeito_reclamado." - ".$descricao_defeito;
                } else {

                    if (isset($moduloTraducao) and !empty($defeito_reclamado)) {
                        $sqlIdiomaReclamado = "SELECT descricao 
                                                 FROM tbl_defeito_reclamado_idioma 
                                                 WHERE defeito_reclamado = {$defeito_reclamado}
                                                 AND LOWER(idioma) = LOWER('$cook_idioma')";
                        $resIdiomaReclamado = pg_query($con, $sqlIdiomaReclamado);

                        if (pg_num_rows($resIdiomaReclamado) > 0) {
                            $descricao_defeito = utf8_decode(pg_fetch_result($resIdiomaReclamado, 0, 'descricao'));
                        }

                    }

                    echo $descricao_defeito;
                }

                if($defeito_reclamado_descricao) {
                    //HD 172561 - Cliente solicitou para mostrar o defeito_reclamado_descricao em um campo e o
                    //tbl_defeito_reclamado.descricao em outro
                    //HD-3331834 Adicinada fabrica 50 no in_array
                    if (!in_array($login_fabrica,array(3,50,52,158,165,169,170)) && !empty($defeito_reclamado_descricao_os)) {
                        echo (!empty($descricao_defeito)) ? " - " : "";
                        echo $defeito_reclamado_descricao_os;
					}elseif($login_fabrica == 50) {
						echo (empty($descricao_defeito)) ? $defeito_reclamado_descricao_os : "";
					}
                }
            } ?>
        </TD>
        <? if($login_fabrica == 95 || $login_fabrica == 94 ){
            echo "<td class='titulo'>&nbsp;</td>";
            echo "<td class='conteudo'>&nbsp;</td>";
        }
    }
    ?>
    <?php 
    if ($defeitoReclamadoCadastroDefeitoReclamadoCliente){ 
        if ($login_fabrica == 177){
            $sql = "SELECT tbl_causa_defeito.descricao FROM tbl_causa_defeito INNER JOIN tbl_os_produto ON tbl_os_produto.causa_defeito = tbl_causa_defeito.causa_defeito WHERE tbl_os_produto.os = {$os}";
        }else{
            $sql = "SELECT tbl_defeito_constatado.descricao FROM tbl_defeito_constatado INNER JOIN tbl_os_produto ON tbl_os_produto.defeito_constatado = tbl_defeito_constatado.defeito_constatado WHERE tbl_os_produto.os = {$os}";
        }
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $defeito_constatado_descricao = pg_fetch_result($res, 0, "descricao");
        }
        ?>
	<?php if($login_fabrica <> 194){ ?>
        <TD class="titulo" height='15' width='90'><?=traduz($tema_coluna,$con,$cook_idioma)?></TD>
        <TD class="conteudo" height='15'>&nbsp; <?=$defeito_constatado_descricao?> </TD>
        <?php } ?>
	<TD class="titulo" height='15' width='90'><?echo traduz("reclamado",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15' width='110' colspan="3"><?=$descricao_defeito;?></TD>
        <?php
        if ($login_fabrica != 175) {
        ?>
            <TD class="titulo" height='15' width='140'><?echo traduz("defeito.reclamado.cliente",$con,$cook_idioma);?></TD>
            <TD class="conteudo" height='15' width='110' colspan="4"><?=$defeito_reclamado_descricao_os;?></TD>
        <?php 
        }
    } 
    ?>
    <?php if ($login_fabrica == 178){?>   
        <TD class="titulo" height='15' width='90'><?echo traduz("Grupo Defeito Constatado",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15' width='110' colspan="3"><?=$defeito_constatado_grupo;?></TD>
    <?php }?>


    <?php if (!in_array($login_fabrica, array(20,30,43,59,94,95,114,131,143,138,152,180,181,182)) && !isset($defeitoConstatadoMultiplo) AND !$defeitoReclamadoCadastroDefeitoReclamadoCliente) {

        if($login_fabrica != 85){ ?>
    
        <TD class="titulo" height='15' width='90'><? if($login_fabrica==20){echo traduz("reparo",$con,$cook_idioma);}else echo traduz($tema_coluna,$con,$cook_idioma);?> </TD>
        <td class="conteudo" height='15'>&nbsp;
<?php
            //HD 17683 - VÁRIOS DEFEITOS CONSTATADOS
            if( $login_fabrica==1){
                if($troca_garantia=='t' or $troca_faturada=='t'){
                    echo $defeito_reclamado_descricao;
                } else {
                    echo $defeito_constatado_descricao;
                }
            } else if ($login_fabrica == 19) {
                $sql = "SELECT  DISTINCT
                                tbl_defeito_constatado.codigo,
                                tbl_defeito_constatado.descricao
                        FROM    tbl_os_defeito_reclamado_constatado
                        JOIN    tbl_defeito_constatado USING(defeito_constatado)
                        WHERE   os=$os
                ";
                $res = pg_query ($con,$sql);

                $array_integridade = array();

                if (pg_num_rows($res)>0) {
                    for ($i=0;$i<pg_num_rows($res);$i++){
                        $aux_defeito_constatado = pg_fetch_result($res,$i,0).'-'.pg_fetch_result($res,$i,1);
                        array_push($array_integridade,$aux_defeito_constatado);
                    }
                }

                if (empty($aux_defeito_constatado)) {
                    $sql_defeito = "SELECT tbl_os.defeito_constatado, tbl_defeito_constatado.descricao
                                    FROM tbl_os
                                    JOIN tbl_defeito_constatado USING(defeito_constatado)
                                    WHERE tbl_os.os = $os
                                    AND tbl_os.fabrica = $login_fabrica";
                    $res_defeito = pg_query($con, $sql_defeito);

                    if(pg_num_rows($res_defeito)>0){
                        for ($i=0;$i<pg_num_rows($res_defeito);$i++){
                            $aux_defeito_constatado = pg_fetch_result($res_defeito,$i,0).'-'.pg_fetch_result($res_defeito,$i,1);
                            array_push($array_integridade,$aux_defeito_constatado);
                        }
                    }
                }

                $lista_defeitos = implode(", ", $array_integridade);
                echo "$lista_defeitos";
            } else {
                if($login_fabrica == 134){
                    $sql = "select tbl_defeito_constatado.codigo,tbl_defeito_constatado.descricao from tbl_os_defeito_reclamado_constatado join tbl_defeito_constatado using(defeito_constatado) where os = $os ";
                    $res_list = pg_query($con,$sql);
                    if(pg_num_rows($res_list) > 0){
                        echo "<ul>";
                        for ($line=0; $line < pg_num_rows($res_list) ; $line++) {
                            echo "<li>".pg_result($res_list,$line,codigo)." - ". pg_result($res_list,$line,descricao)."</li>";
                        }
                        echo "</ul>";
                    }
                }else{
                    if($login_fabrica==20){
                        echo $defeito_constatado_codigo.' - ';
                    }
                    if (isset($novaTelaOs)) {
                        $sql = "SELECT tbl_defeito_constatado.descricao FROM tbl_defeito_constatado INNER JOIN tbl_os_produto ON tbl_os_produto.defeito_constatado = tbl_defeito_constatado.defeito_constatado WHERE tbl_os_produto.os = {$os}";
                        $res = pg_query($con, $sql);

                        if (pg_num_rows($res) > 0) {
                            $defeito_constatado_descricao = pg_fetch_result($res, 0, "descricao");
                        }
                    }

                    if (isset($moduloTraducao)) {
                        $sqlIdiomaConstatado = "SELECT descricao 
                                                 FROM tbl_defeito_constatado_idioma 
                                                 WHERE defeito_constatado = {$defeito_constatado}
                                                 AND LOWER(idioma) = LOWER('$cook_idioma')";
                        $resIdiomaConstatado = pg_query($con, $sqlIdiomaConstatado);

                        if (pg_num_rows($resIdiomaConstatado) > 0) {
                            $defeito_constatado_descricao = utf8_decode(pg_fetch_result($resIdiomaConstatado, 0, 'descricao'));
                        }

                    }

                    echo $defeito_constatado_descricao;
                }

            }
?>
	</TD>
<?php
        }

        if (in_array($login_fabrica, array(35,145))) {
?>
        <TD class="titulo" height='15' width='90'><?= traduz("solucao") ?></TD>
        <TD class="conteudo" height='15'>&nbsp;
<?php
            $sql = "SELECT tbl_solucao.descricao 
		              FROM tbl_solucao 
		        INNER JOIN tbl_os ON tbl_os.solucao_os = tbl_solucao.solucao 
		             WHERE tbl_os.fabrica = {$login_fabrica} 
		               AND tbl_os.os = {$os}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $solucao_descricao = pg_fetch_result($res, 0, "descricao");
                echo $solucao_descricao;
            }
?>
        </TD>
<?php
        }
?>
    </TR>
<?php
        if($login_fabrica == 153 and $defeito_constatado_descricao == 'Mau Uso'){
?>
    <tr>
        <td height='15' class='inicio' colspan="4">&nbsp;<?= traduz("descricao.do.mau.uso") ?></td>
    </tr>
    <tr>
        <td  class="conteudo" height='15' colspan="4"><?=$obs_adicionais?></td>
    </tr>
<?php
        }
        if (!empty($tecnico_nome)) {
?>
        <tr>
            <td class="titulo"><? echo traduz("tecnico",$con,$cook_idioma);?></td>
            <td class="conteudo" colspan='3'>&nbsp;<?= $tecnico_nome;?></td>
        </tr>
<?php
        }
        if ($login_fabrica == 52) {

            // HD-896985

            $sqlTecnico = "SELECT tecnico FROM tbl_os_extra WHERE os = ".$os."";
            $resTecnico = pg_query($con,$sqlTecnico);

            $tecnicoData = pg_fetch_result ($resTecnico,0,tecnico);
            $explodeTecnico = explode("|", $tecnicoData);
            $tecnicoNome = $explodeTecnico[0];
            $tecnicoRg = $explodeTecnico[1];

?>
        <tr>
            <td class="titulo"><?= traduz("rg.do.tecnico") ?></td>
            <td class="conteudo" colspan='1'>&nbsp;<?php echo $tecnicoRg;?></td>
            <td class="titulo"><?php echo traduz("tecnico",$con,$cook_idioma);?></td>
            <td class="conteudo" colspan='1'>&nbsp;<?php echo $tecnicoNome;?></td>
        </tr>
<?php
        }
        if(!isset($novaTelaOs) && (!in_array($login_fabrica,array(46,50,87,115,116,120,121,122,123,124,125,126,127,128, 129,131,134,136,138,140,141,142,144,145,146,148)) || !$login_fabrica >= 141)) {
?>
	<TR>
<?php
		if($login_fabrica == 85){

			echo "<td class='titulo'>".traduz("constatado")."</td>";
			echo "<td class='conteudo'>";
			$sql = "select tbl_defeito_constatado.codigo,tbl_defeito_constatado.descricao from tbl_os_defeito_reclamado_constatado join tbl_defeito_constatado using(defeito_constatado) where os = $os ";
			$res_list = pg_query($con,$sql);
			if(pg_num_rows($res_list) > 0){
				  echo "<ul>";
				    for ($line=0; $line < pg_num_rows($res_list) ; $line++) {
					        echo "<li>".pg_result($res_list,$line,codigo)." - ". pg_result($res_list,$line,descricao)."</li>";
						  }
			  	echo "</ul>";
			}
			echo "</td>";
		}
	    ?>
            <TD class="titulo" height='15' width='90'>
                <?
                if((in_array($login_fabrica, array(1,3,6,11,15,24,40,43,45,46,50,59,72,74)) || $login_fabrica >= 80) AND !in_array($login_fabrica, array(87,131,140,141,142,144,145)))
                    echo traduz("solucao",$con,$cook_idioma);
                elseif($login_fabrica==20)
                    echo traduz("defeito",$con,$cook_idioma);
                elseif($login_fabrica==52)
                    echo "GRUPO DEFEITO";
                else
                    echo traduz("causa",$con,$cook_idioma);
                ?>
            </td>
            <td class="conteudo" colspan='3' height='15'>&nbsp;

<?php
                if ($login_fabrica != 85) {
                    if((in_array($login_fabrica, array(1,24, 40, 43, 45, 59,72,74)) or $login_fabrica >= 80) and strlen($solucao_os)>0) { //takashi 30-11
                    $sql="SELECT descricao FROM tbl_solucao WHERE solucao=$solucao_os AND fabrica=$login_fabrica LIMIT 1";
                    $xres = pg_query($con, $sql);
                    $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                    echo "$xsolucao";
                    }
                } else {

                    $sql = "select tbl_solucao.codigo,tbl_solucao.descricao from tbl_os_defeito_reclamado_constatado join tbl_solucao using(solucao) where os = $os ";
                    $res_list = pg_query($con,$sql);
                    if(pg_num_rows($res_list) > 0){
                        echo "<ul>";
                        for ($line=0; $line < pg_num_rows($res_list) ; $line++) {
                            echo "<li>".pg_result($res_list,$line,codigo)." - ". pg_result($res_list,$line,descricao)."</li>";
                        }
                        echo "</ul>";
                    }
                }

                if(in_array($login_fabrica, array(3,6,11,15,50,172))) {
                    if (strlen($solucao_os)>0){
                        //chamado 1451 - não estava validando a data...
                        $sql_data = "SELECT SUM(validada - '2006-11-05') AS total_dias FROM tbl_os WHERE os=$os";
                        $resD = pg_query ($con,$sql_data);
                        if (pg_num_rows ($resD) > 0) {
                            $total_dias = pg_fetch_result ($resD,0,total_dias);
                        }

                        if ( ($total_dias > 0 AND $login_fabrica==6) OR in_array($login_fabrica, array(3,11,15,50,172)) ){
                            $sql="select descricao from tbl_solucao where solucao=$solucao_os and fabrica=$login_fabrica limit 1";
                            $xres = pg_query($con, $sql);
                            if (pg_num_rows($xres)>0){
                                $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                                echo "$xsolucao";
                            } else {
                                $xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
                                $xres = pg_query($con, $xsql);
                                $xsolucao = trim(@pg_fetch_result($xres,0,descricao));
                                echo "$xsolucao";
                            }
                        //if($ip=="201.27.30.194") echo $sql;
                        } else {
                            $xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
                            $xres = pg_query($con, $xsql);
                            if (pg_num_rows($xres)>0){
                                $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                                echo "$xsolucao  - $data_digitacao";
                            } else {
                                $sql="select descricao from tbl_solucao where solucao=$solucao_os and     fabrica=$login_fabrica limit 1";
                                $xres = pg_query($con, $sql);
                                $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                                echo "$xsolucao";
                            }
                        }
                    }
                } else {
                    if($login_fabrica==52){
                        echo $defeito_constatado_grupo;
                    } else if(in_array($login_fabrica,array(20,131))) {
                        echo $causa_defeito_codigo.' - ' ;
                        echo $causa_defeito_descricao;
                }
            }
        }
?>
            </TD>
         </TR>
<?php
    } else if( in_array($login_fabrica, array(152,180,181,182)) ) {
        $sqlDef = "SELECT
                    tbl_defeito_constatado.descricao AS defeito_constatado,
                    tbl_os_defeito_reclamado_constatado.tempo_reparo
                    FROM tbl_os_defeito_reclamado_constatado
                    INNER JOIN tbl_defeito_constatado
                    ON tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado
                    AND tbl_defeito_constatado.fabrica = {$login_fabrica}
                    WHERE tbl_os_defeito_reclamado_constatado.os = {$os}";
        $resDef = pg_query($con, $sqlDef);

        if (pg_num_rows($resDef) > 0) {
            while ($defeito_constatado = pg_fetch_object($resDef)) {
                echo "
                    <tr>
                        <td class='titulo'>Constatado</td>
                        <td class='conteudo'>{$defeito_constatado->defeito_constatado}</td>
                        <td class='titulo'>TEMPO DE REPARO (MINUTOS)</td>
                        <td class='conteudo'>{$defeito_constatado->tempo_reparo}</td>
                    </tr>
                ";
            }
        }
    } else {
        if ($login_fabrica == 131) {
?>

           <TD class="titulo" height='15' width='90'><? if($login_fabrica==20){echo traduz("reparo",$con,$cook_idioma);}else echo traduz("causa",$con,$cook_idioma);?> </TD>
            <td class="conteudo" height='15'>
                &nbsp;
<?php
                $sql = "SELECT tbl_causa_defeito.descricao FROM tbl_os JOIN tbl_causa_defeito USING(causa_defeito) WHERE os = $os;";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res) > 0){
                    echo pg_result($res,0,descricao);
                }

?>
            </td>
<?php
        }
    }

	if ($login_fabrica == 138) {
		$sql_defeito_constatado = "SELECT tbl_produto.referencia AS produto, tbl_defeito_constatado.descricao AS defeito_constatado
					   FROM tbl_os_produto
					   INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
					   INNER JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
					   WHERE tbl_os_produto.os = {$os}";
		$res_defeito_constatado = pg_query($con, $sql_defeito_constatado);

		if (pg_num_rows($res_defeito_constatado) > 0) {
			while ($result = pg_fetch_object($res_defeito_constatado)) {
				echo "
					<tr>
						<td class='titulo'>
							".traduz("defeito.constatado.produto").": {$result->produto}
						</td>
						<td class='conteudo'>
							&nbsp; {$result->defeito_constatado}
						</td>
					</tr>
				";
			}
		}
	}

    if (in_array($login_fabrica, array(158))) {
        $sqlReclamadoOS = "SELECT codigo, descricao FROM tbl_os_defeito_reclamado_constatado INNER JOIN tbl_defeito_reclamado USING(defeito_reclamado) WHERE os = {$os}";;
        $resReclamadoOS = pg_query($con, $sqlReclamadoOS);

        if (pg_num_rows($resReclamadoOS) > 0) {
            while ($reclamado = pg_fetch_object($resReclamadoOS)) {
            ?>
                <tr>
                    <td class="titulo"><?php echo traduz("Reclamado"); ?></td>
                    <td class="conteudo" colspan="4"><?=$reclamado->codigo. " - ".$reclamado->descricao?></td>
                </tr>
            <?php
            }
        }
    }

    if (in_array($login_fabrica, array(30,43,59,94,95,114,131,138,143,144,148,149)) || isset($defeitoConstatadoMultiplo)) {

        if(in_array($login_fabrica, array(138,149))){
            $condLeft = "LEFT JOIN tbl_defeito_constatado USING(defeito_constatado)
                        JOIN tbl_solucao USING(solucao)
                        LEFT JOIN tbl_defeito USING(defeito)";
        }else if($login_fabrica == 148){
            $condLeft = "LEFT JOIN tbl_defeito_constatado USING(defeito_constatado)
                        LEFT JOIN tbl_solucao USING(solucao)
                        LEFT JOIN tbl_defeito USING(defeito)";
        }else{
            $condLeft = "JOIN tbl_defeito_constatado USING(defeito_constatado)
                        LEFT JOIN tbl_solucao USING(solucao)
                        LEFT JOIN tbl_defeito USING(defeito)";
        }

        $sql_cons = "SELECT
                tbl_defeito_constatado.defeito_constatado,
                tbl_defeito_constatado.descricao         ,
                tbl_defeito_constatado.codigo,
                tbl_solucao.solucao,
                tbl_solucao.codigo AS cod_solucao,
                tbl_solucao.descricao as solucao_descricao,
                tbl_defeito.defeito,
                tbl_defeito.descricao AS defeito_descricao
        FROM tbl_os_defeito_reclamado_constatado
        $condLeft
        WHERE os = $os";
        $res_dc = pg_query($con, $sql_cons);

        if ($login_fabrica == 158 && pg_num_rows($res_dc) == 0) {
            $sql_cons = "SELECT
                                tbl_defeito_constatado.defeito_constatado,
                                tbl_defeito_constatado.descricao,
                                tbl_defeito_constatado.codigo,
                                '' AS solucao,
                                '' AS solucao_descricao,
                                '' AS defeito,
                                '' AS defeito_descricao
                        FROM tbl_os 
                        JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                        WHERE os = {$os};";

            $res_dc = pg_query($con, $sql_cons);            
        }

        if(pg_num_rows($res_dc) > 0){
            if(!in_array($login_fabrica, array(148,191,193))){
                if (in_array($login_fabrica, array(30,94,131,143,144,158,169,170,191))) {
                    $rowspan_defeito = pg_num_rows($res_dc) + 1;
                    echo "<tr>";
                    echo "<td class='titulo' rowspan='".$rowspan_defeito."' height='15'>".$tema."</td>";
                    echo "</tr>";
                }

                for($x=0;$x<pg_num_rows($res_dc);$x++){
                    $dc_defeito_constatado = pg_fetch_result($res_dc,$x,defeito_constatado);
                    $dc_solucao = pg_fetch_result($res_dc,$x,solucao);

                    $dc_descricao = pg_fetch_result($res_dc,$x,descricao);
                    $dc_codigo    = pg_fetch_result($res_dc,$x,codigo);
                    $dc_solucao_descricao = pg_fetch_result($res_dc,$x,solucao_descricao);

                    $dc_defeito    = pg_fetch_result($res_dc,$x,defeito);
                    $dc_defeito_descricao = pg_fetch_result($res_dc,$x,defeito_descricao);

                    echo "<tr>";


                    if (!in_array($login_fabrica,array(138,148,149,191))){
                        if (!in_array($login_fabrica, array(30,94,131,143,144,158,169,170))) {
                            echo "<td class='titulo' height='15'>".$tema."</td>";
                        }

                        if (in_array($login_fabrica, array(30,94,131,143,144,158))) {
                            echo "<td class='conteudo' colspan=4>$dc_codigo - $dc_descricao</td>";
                        } else if (!in_array($login_fabrica, array(169,170))) {
                            if ($login_fabrica == 160 or $replica_einhell) $aux_colspan = " colspan='3' ";
                            echo "<td class='conteudo'{$aux_colspan}>&nbsp; $dc_descricao</td>";
                        }

                        if (in_array($login_fabrica, array(169,170)) && !empty($dc_defeito)) {
                            echo "<td class='conteudo'>&nbsp; $dc_descricao</td>";
                            echo "<td class='titulo' height='15'>".traduz("defeito.da.peca")."</td>";
                            echo "<td class='conteudo'>&nbsp; $dc_defeito_descricao</td>";
                        } else if (in_array($login_fabrica, array(169,170))) {
                            $colspanDC = (in_array($login_fabrica, array(169,170))) ? "colspan='100%'" : "";
                            echo "<td class='conteudo' {$colspanDC}>&nbsp; $dc_descricao</td>";
                        }
                    }

                    if (!in_array($login_fabrica,array(30,94,114,131,144,142,143,156,158,160,169,170,183,184,186,190,194,195,198,200)) and !$replica_einhell){
                        $colspan = (in_array($login_fabrica, array(138,149,191))) ? "colspan='100%'" : "";
                        echo "<td class='titulo' height='15'>".traduz("solucao")."</td>";
                        echo "<td class='conteudo' $colspan>$dc_solucao_descricao</td>";
                    }

                    echo "</tr>";

				}

				if (in_array($login_fabrica, array(30,183))) {
                        $sql = "SELECT descricao
                                FROM tbl_os
                                    JOIN tbl_solucao ON(tbl_os.solucao_os = tbl_solucao.solucao)
                                WHERE tbl_os.os = $os
                                    AND tbl_os.fabrica = $login_fabrica;";

                        $res = pg_query($con, $sql);
                        if (pg_num_rows($res) > 0) {
                            $solucao_descricao = pg_fetch_result($res, 0, "descricao");
                            if (mb_check_encoding($solucao_descricao, 'UTF-8')) {
                                $solucao_descricao = utf8_decode($solucao_descricao);
                            }
                            echo "<tr>";
                            echo "<td class='titulo' height='15'>".traduz("solucao")."</td>";
                            echo "<td class='conteudo'>$solucao_descricao</td>";
                            echo "</tr>";
                        }
                    }
            }else{
                $dados_defeito_solucao = pg_fetch_all($res_dc);

                foreach ($dados_defeito_solucao as $key => $value) {
                    if(strlen(trim($value["descricao"])) > 0){
                        echo "<tr>";
                            echo "<td class='titulo' style='width: 50px;' height='15'>".traduz("defeito.constatado")."</td>";
                            echo "<td class='conteudo'>&nbsp;".$value["descricao"]."</td>";
                        echo "</tr>";
                    }
                    if(strlen(trim($value["solucao_descricao"])) > 0){
                        echo "<tr>";
                            echo "<td class='titulo' style='width: 50px;' height='15'>SOLUÇÃO</td>";
                            echo "<td class='conteudo'>&nbsp;".$value["solucao_descricao"]."</td>";
                        echo "</tr>";
                    }
                }
            }
        } else if ($login_fabrica == 160 or $replica_einhell) { /*HD - 4394208*/
            $aux_sql = "
                SELECT tbl_defeito_constatado.descricao AS defeito_constatado
                FROM tbl_os
                JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
                WHERE tbl_os.os = $os
            ";
            $aux_res = pg_query($con, $aux_sql);

            if (pg_num_rows($aux_res) > 0) {
                unset($aux_defeito_constatado_label);

                $aux_defeito_constatado_label = pg_fetch_result($aux_res, 0, 'defeito_constatado');
                echo "
                    <tr>
                        <td class='titulo' height='15'>DEFEITO CONSTATADO</td>
                        <td class='conteudo' colspan='3'>$aux_defeito_constatado_label</td>
                    </tr>
                ";
            }
        }

    }

    if (in_array($login_fabrica, array(158,191))) {
            $sqlSolucaoOS = "SELECT codigo, descricao FROM tbl_os_defeito_reclamado_constatado INNER JOIN tbl_solucao USING(solucao) WHERE os = {$os}";;
            $resSolucaoOS = pg_query($con, $sqlSolucaoOS);

            if (pg_num_rows($resSolucaoOS) > 0) {
                while ($solucao = pg_fetch_object($resSolucaoOS)) {
                ?>
                    <tr>
                        <td class="titulo"><?= traduz("solucao") ?></td>
                        <td class="conteudo" colspan="4"><?=$solucao->codigo. " - ".$solucao->descricao?></td>
                    </tr>
                <?php
                }
            }
            ?>
    <? }

    if($login_fabrica==20){
        if($solucao_os){
            $xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
            $xres = pg_query($con, $xsql);

            $xsolucao = trim(pg_fetch_result($xres,0,descricao));

            $sql_idioma = " SELECT * FROM tbl_servico_realizado_idioma
                            WHERE servico_realizado = $solucao_os
                            AND upper(idioma)   = '$sistema_lingua'";
            $res_idioma = @pg_query($con,$sql_idioma);
            if (@pg_num_rows($res_idioma) >0) $xsolucao  = trim(@pg_fetch_result($res_idioma,0,descricao));

            echo "<tr>";
            echo "<td class='titulo' height='15' width='90'>".traduz("identificacao",$con,$cook_idioma)."</TD>";
            echo "<td class='conteudo'colspan='3' height='15'>&nbsp;$xsolucao</TD>";
            echo "</tr>";
        }
    }
    ?>
</TABLE>
<?php 

if ($login_fabrica == 19) {

 $sql_list="SELECT tbl_checklist_fabrica.codigo,tbl_defeito_constatado.codigo AS  codigo_dc,
                    tbl_checklist_fabrica.descricao
                    FROM tbl_os_defeito_reclamado_constatado
                    JOIN tbl_checklist_fabrica USING(checklist_fabrica)
                    JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
                    WHERE tbl_os_defeito_reclamado_constatado.os = $os
                    AND tbl_checklist_fabrica.fabrica = $login_fabrica
                    AND tbl_os_defeito_reclamado_constatado.checklist_fabrica is not null ";
    $res_list = pg_query($con, $sql_list);

    if(pg_num_rows($res_list) > 0){
        $rows = pg_num_rows($res_list);


    ?>
    <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
        <tr>
            <td class='inicio' colspan='4' height='15'>&nbsp;<?echo traduz("Checklist preenchido",$con,$cook_idioma);?></TD>
        </tr>
        <?php
            for ($i=0; $i < $rows; $i++) {
                $checklist_fabrica = pg_fetch_result($res_list, $i, 'checklist_fabrica');
                $codigo = pg_fetch_result($res_list, $i, 'codigo');
                $descricao = pg_fetch_result($res_list, $i, 'descricao');
                $codigo_dc = pg_fetch_result($res_list, $i, 'codigo_dc');
        ?>        
        <TR>
            <TD style="<?php echo ($codigo_dc == "554") ? 'background: #f3cece;' : '';?>" class="conteudo" height='15' nowrap>
                <input type="checkbox" checked disabled>
                 <?php echo $codigo;?> - <?php echo $descricao;?>
                    
            </TD>
        </TR>
      <?php } ?>
    </TABLE>    
<?php
    }
}
?>


<?php
    if($consumidor_revenda == "REVENDA" and in_array($login_fabrica,array(145))){
?>
    <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
        <tr>
            <td class='inicio' colspan='4' height='15'>&nbsp;<?= traduz("informacoes.da.revenda") ?>&nbsp;</td>
        </tr>
        <TR>
            <TD class="titulo" height='15'><?= traduz("nome") ?></TD>
            <TD class="conteudo" height='15' width='300'>&nbsp;<? echo $revenda_nome ?></TD>
            <TD class="titulo"><?= traduz("telefone") ?>&nbsp;</TD>
            <TD class="conteudo"height='15'>&nbsp;<? echo $revenda_fone ?></TD>
        </TR>

        <TR>
            <?php
            if (isset($novaTelaOs)) {
            ?>
                <TD class="titulo" height='15'><?=(strtoupper($consumidor_revenda) == 'REVENDA') ? "CNPJ" : "CPF";?> REVENDA&nbsp;</TD>
            <?php
            } else {
            ?>
                <TD class="titulo" height='15'><?=($login_fabrica == 85 && $campos_adicionais->consumidor_cpf_cnpj == 'R') ? "CNPJ" : "CPF";?> REVENDA&nbsp;</TD>
            <?php
            }
            ?>
            <TD class="conteudo" height='15'>&nbsp;<? echo $revenda_cnpj ?></TD>
            <TD class="titulo" height='15'><?= traduz("cep") ?>&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $revenda_cep ?></TD>
        </TR>
        <TR>
            <TD class="titulo" height='15'><?= traduz("endereco") ?>&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $revenda_endereco ?></TD>
            <TD class="titulo" height='15'><?= traduz("numero") ?>&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $revenda_numero ?></TD>
        </TR>
        <TR>
            <TD class="titulo" height='15'><?= traduz("complemento") ?>&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $revenda_complemento ?></TD>
            <TD class="titulo" height='15'><?= traduz("bairro") ?>&nbsp;</TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $revenda_bairro ?></TD>
        </TR>

        <TR>
            <TD class="titulo"><?= traduz("cidade") ?>&nbsp;</TD>
            <TD class="conteudo">&nbsp;<? echo $nome_cidade_revenda ?></TD>
            <TD class="titulo"><?= traduz("estado") ?>&nbsp;</TD>
            <TD class="conteudo">&nbsp;<? echo $sigla_estado_revenda ?></TD>
        </TR>
        <TR>
            <TD class="titulo">E-MAIL&nbsp;</TD>
            <TD class="conteudo">&nbsp;<? echo $revenda_email ?></TD>
            <?if($login_fabrica==1){?>
                <TD class="titulo"><?= traduz("tipo.consumidor") ?></TD>
                <TD class="conteudo">&nbsp;<? echo $fisica_juridica; ?></TD>
            <?}elseif( in_array($login_fabrica, array(11,172)) ){?>
                <TD class="titulo">FONE REC</TD>
                <TD class="conteudo"><? echo $consumidor_fone_recado; ?></TD>
            <?
            } else if ($login_fabrica == 122) {
            ?>
                <TD class="titulo">CPD DO CLIENTE</TD>
                <TD class="conteudo"><? echo $consumidor_cpd; ?></TD>
            <?php
            }elseif ($login_fabrica==59) {
                $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

                    foreach ($campos_adicionais as $key => $value) {
                        $$key = $value;
                    }
                    if (strlen($origem)>0) {
                        $origem = ($origem == "recepcao") ? "Recepção" : "Sedex Reverso";
                    }
                    ?>
                    <TD class="titulo" width="80">ORIGEM&nbsp;</TD>
                    <TD class="conteudo">&nbsp;<?=$origem?> </TD>
                    <?php
                }
            }else{
                    ?>
                        <TD class="titulo">&nbsp;</TD>
                        <TD class="conteudo">&nbsp;</TD>
                    <?
            }
            ?>
        </TR>
    </TABLE>

<?php
}else{

    if (in_array($login_fabrica, array(158))) {
        if (isset($dadosIntegracao)) {
            $cockpitPrioridade = $dadosIntegracao[0]['descricao'];

            $verifKA = strrpos($cockpitPrioridade, "KA");

            if ($verifKA === false){
                $keyAccount = "";
            } else {
                $keyAccount = " (KEY ACCOUNT)";
            }
        }
    }

    if($login_fabrica == 169 AND $consumidor_revenda == "REVENDA" AND in_array($tipo_atendimento,array(304,305,315))){
	$displayConsumidor = "style='display:none;'";
    }
?>
	<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela' <?=$displayConsumidor?>>
    <tr>
        <td class='inicio' colspan='4' height='15'>&nbsp;<?=($login_fabrica == 178)? traduz("informacoes.sobre.o.cliente",$con,$cook_idioma):traduz("informacoes.sobre.o.consumidor",$con,$cook_idioma);?></TD>
    </tr>
    <TR>
        <TD class="titulo" height='15'><?=($login_fabrica == 85 && $campos_adicionais->consumidor_cpf_cnpj == 'R') ? "NOME FANTASIA" : traduz("nome",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15' width='300'>&nbsp;<?= (in_array($login_fabrica, array(158)) && strlen($keyAccount) > 0) ? $consumidor_nome.$keyAccount : $consumidor_nome; ?></TD>
        <TD class="titulo"><?echo traduz("telefone.residencial",$con,$cook_idioma);?></TD>
        <TD class="conteudo fones" height='15'>&nbsp;<? echo $consumidor_fone ?></TD>
    </TR>

    <?php if(in_array($login_fabrica,array(1,3,11,20,30,35,46,50,59,74,80,85,87,101,104,114,115,116,117,120,121,122,123,124,125,126,127,128,129,131,134,136,147,145)) OR $login_fabrica >= 148){?>
        <TR>
            <TD class="titulo" height='15'><?= traduz("celular",$con,$cook_idioma);?></TD>
            <TD class="conteudo fones" height='15' width='300'>&nbsp;<?= $consumidor_celular ?></TD>
            <?php
            if(!in_array($login_fabrica,array(1,20,35,147,167,171,203))){ ?>
                <TD class="titulo"><?= traduz("telefone.comercial",$con,$cook_idioma);?></TD>
                <TD class="conteudo fones" height='15'>&nbsp;<?= $consumidor_fone_comercial ?></TD>
            <?php
            }elseif($login_fabrica == 20){ ?>
                <TD class="titulo" height='15' nowrap><?= traduz("cpf/cnpj.consumidor") ?></TD>
                <TD class="conteudo" height='15' >&nbsp;<? echo $consumidor_cpf ?></TD>
            <?php
            }elseif(in_array($login_fabrica, [167, 203])){ ?>
                <td class="titulo"><?echo traduz("contato",$con,$cook_idioma);?></td>
                <td class="conteudo"><?=$contato_consumidor?></td>
            <?php
            }elseif($login_fabrica == 171){
            ?>
                <td class="titulo"><?echo traduz("edificio",$con,$cook_idioma);?></td>
                <td class="conteudo"><?=($os_campos_adicionais['edificio'] == 't') ? 'Sim' : 'Não'; ?></td>
            <?
            } else{
                echo "<td class='titulo'> </td><td class='conteudo'> </td>" ;
            } ?>
        </TR>
    <?php }?>
    <?php if($login_fabrica <> 20){ ?>
        <?php if ($login_fabrica == 178 AND strtoupper($consumidor_revenda) != "CONSUMIDOR"){ ?>
            <tr>
                <td class="titulo" height='15' nowrap><?=traduz("Inscrição Estadual",$con,$cook_idioma);?></TD>
                <td class="conteudo" height='15'> &nbsp;<? echo $os_campos_adicionais["inscricao_estadual"] ?></td>
                <td class="titulo" height='15' nowrap>&nbsp;</TD>
                <td class="conteudo" height='15'> &nbsp;</td>
            </tr>
        <?php } ?>
    <TR>
        <?php
        if (isset($novaTelaOs)) {
        ?>
            <TD class="titulo" height='15' nowrap><?=(strtoupper($consumidor_revenda) == "REVENDA") ? "CNPJ CONSUMIDOR" : traduz("cpf.consumidor",$con,$cook_idioma);?></TD>
        <?php
        } else {
            if ($login_fabrica == 1 || $login_fabrica == 72) {
                ?> <TD class="titulo" height='15' nowrap><?= traduz("cpf/cnpj.consumidor") ?></TD> <?php
            } else {?>
                <TD class="titulo" height='15' nowrap><?=($login_fabrica == 85 && $campos_adicionais->consumidor_cpf_cnpj == 'R') ? "CNPJ CONSUMIDOR" : traduz("cpf.consumidor",$con,$cook_idioma);?></TD>
            <?php }
        }
        ?>
        <TD class="conteudo" height='15'> &nbsp;<? echo $consumidor_cpf ?></TD>
        <TD class="titulo" height='15'><?echo traduz("cep",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cep ?></TD>
    </TR>
    <TR>
        <TD class="titulo" height='15'><? echo traduz("endereco",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_endereco ?></TD>
        <TD class="titulo" height='15'><?echo traduz("numero",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_numero ?></TD>
    </TR>
    <TR>
        <TD class="titulo" height='15'><?echo traduz("complemento",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_complemento ?></TD>
        <TD class="titulo" height='15'><?echo traduz("bairro",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15' nowrap>&nbsp;<? echo $consumidor_bairro ?></TD>
    </TR>
    <?php }
    if (in_array($login_fabrica, array(52,183))){
        $sql_pr = "SELECT obs from tbl_os_extra where os=$os";
        $res_pr = pg_query($con,$sql_pr);
        $ponto_referencia = (pg_num_rows($res_pr)>0) ? pg_fetch_result($res_pr, 0, 0) : "" ;
    ?>
        <TD class="titulo" height='15'>Ponto de Referência</TD>
        <TD class="conteudo"  style='white-space:pre-line'>&nbsp;<? echo $ponto_referencia ?></TD>
        <TD class="titulo" height='15'>&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;</TD>
    <?php }
    if($login_fabrica <> 20){
        if($consumidor_estado == "EX"){
            $consumidor_estado = getEstadoDoConsumidor($os);
        }
        ?>
    <?php }
    if($login_fabrica <> 20){//hd_chamado=2843341 ?>
        <TR>
            <TD class="titulo"><?echo traduz("cidade",$con,$cook_idioma);?></TD>
            <TD class="conteudo">&nbsp;<? echo $consumidor_cidade ?></TD>
            <TD class="titulo"><?echo traduz("estado",$con,$cook_idioma);?></TD>
            <?
            if(in_array($login_fabrica, array(152, 180, 181, 182))){
                $pgResource = pg_query($con, "SELECT pais FROM tbl_posto WHERE posto = {$login_posto}");    
                $pais_posto = pg_fetch_assoc($pgResource)['pais'] ?? 'BR';

                if(!in_array($pais_posto, array('AR', 'BR', 'CO', 'PE'))){
                    $sql_consumidor_estado = "SELECT campos_adicionais 
                                                FROM tbl_os_campo_extra 
                                                WHERE fabrica = {$login_fabrica}
                                                AND os = {$os}";
                    
                    $res_consumidor_estado   = pg_query($con, $sql_consumidor_estado);
                    $valor_consumidor_estado = json_decode(pg_fetch_result($res_consumidor_estado, 0, campos_adicionais), true);
                    echo "<TD class='conteudo'>&nbsp;" . utf8_decode($valor_consumidor_estado["estado"]) . "</TD>";
                } else {
                    echo "<TD class='conteudo'>&nbsp;" . $consumidor_estado . "</TD>";
                }
            } else {
                    echo "<TD class='conteudo'>&nbsp;" . $consumidor_estado . "</TD>";
            }
            ?>                       
        </TR>


       <TR>
            <TD class="titulo"><?echo traduz("email",$con,$cook_idioma);?></TD>

            <?php if($login_fabrica == 35){ ?>
                <TD class="conteudo">&nbsp;<? echo (strlen($consumidor_email) > 0) ? "$consumidor_email" : $obs_adicionais ?></TD>
            <?php }else{?>
                <TD class="conteudo">&nbsp;<? echo $consumidor_email ?></TD>
            <?php }?>
        

            <?if($login_fabrica==1){?>
                <TD class="titulo"><?echo traduz("tipo.consumidor",$con,$cook_idioma);?></TD>
                <TD class="conteudo">&nbsp;<? echo $fisica_juridica ?></TD>
                <TR>
                  <TD class="titulo"><?= traduz("profissao") ?></TD>
                  <TD class="conteudo" colspan="3">&nbsp;
                    <?php
                        if (array_key_exists("consumidor_profissao", $os_campos_adicionais)) {
                            echo utf8_decode($os_campos_adicionais["consumidor_profissao"]);
                        }
                    ?>
                  </TD>
                </TR>
            <?}elseif( in_array($login_fabrica, array(11,172)) ){?>
                <TD class="titulo"><? echo traduz("fone.rec",$con,$cook_idioma);?></TD>
                <TD class="conteudo">&nbsp;<? echo $consumidor_fone_recado ?></TD>
            <?
            } else if ($login_fabrica == 122) {
            ?>
                <TD class="titulo">CPD DO CLIENTE</TD>
                <TD class="conteudo">&nbsp;<? echo $consumidor_cpd ?></TD>
            <?php
            }elseif ($login_fabrica==59) {
                $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

                    foreach ($campos_adicionais as $key => $value) {
                        $$key = $value;
                    }
                    if (strlen($origem)>0) {
                        $origem = ($origem == "recepcao") ? "Recepção" : "Sedex Reverso";
                    }
                    ?>
                    <TD class="titulo" width="80">ORIGEM&nbsp;</TD>
                    <TD class="conteudo">&nbsp;<?=$origem?> </TD>
                    <?php
                }
            } elseif (in_array($login_fabrica, [74,203])) {
                $qry_c_adicionais = pg_query($con, "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os");
                $consumidor_data_nascimento = '';

                if (pg_num_rows($qry_c_adicionais)) {
                    $os_c_adicionais = json_decode(pg_fetch_result($qry_c_adicionais, 0, 'campos_adicionais'), true);

                    if (array_key_exists("data_nascimento", $os_c_adicionais)) {
                        $consumidor_data_nascimento = $os_c_adicionais["data_nascimento"];
                    } elseif (array_key_exists("consumidor_nascimento", $os_c_adicionais)) {
                        $consumidor_data_nascimento = $os_c_adicionais["consumidor_nascimento"];
                    }
                }
                ?>
                <TD class="titulo"><?= traduz("data.de.nascimento") ?></TD>
                <TD class="conteudo"><?php echo $consumidor_data_nascimento ?></TD>
                <?php
            } elseif ($login_fabrica == 171) {
            ?>
                <TD class="titulo"><?= traduz("quantidade.de.andares") ?></TD>
                <TD class="conteudo"><?=$os_campos_adicionais['edificio_total_andares']; ?></TD>
            <?
            } elseif ($login_fabrica == 52) {
                $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
                $aux_res = pg_query($con, $aux_sql);
                $aux_arr = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);

                if (!empty($aux_arr["pais"])) {
                    $consumidor_pais = $aux_arr["pais"];
                } else {
                    $consumidor_pais = "";
                }
                unset($aux_sql, $aux_res, $aux_arr);
            ?>
                <TD class="titulo">PAÍS&nbsp;</TD>
                <TD class="conteudo"><?=$consumidor_pais;?>&nbsp;</TD>
            <?
            }else {
            ?>
                <TD class="titulo">&nbsp;</TD>
                <TD class="conteudo">&nbsp;</TD>
            <?
            }
            ?>
        </TR>
    <?php }
    if($login_fabrica == 20){ ?>
        <tr>
            <TD class="titulo"><?echo traduz("email",$con,$cook_idioma);?></TD>
            <TD class="conteudo" colspan='3'>&nbsp;<? echo $consumidor_email ?></TD>
        </tr>
    <?php } ?>
</TABLE>
<?php }

/*COLORMAQ TEM 2 REVENDAS*/
if($login_fabrica==50){

    $sql = "SELECT
                cnpj,
                to_char(data_venda, 'dd/mm/yyyy') as data_venda
            FROM tbl_numero_serie
            WHERE serie = trim('$serie')";

    $res_serie = pg_query ($con,$sql);

    if (pg_num_rows ($res_serie) > 0) {


        $txt_cnpj       = trim(pg_fetch_result($res_serie,0,cnpj));
        $data_venda = trim(pg_fetch_result($res_serie,0,data_venda));

        $sql = "SELECT      tbl_revenda.nome              ,
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

        $res_revenda = pg_query ($con,$sql);

        # HD 31184 - Francisco Ambrozio (06/08/08) - detectei que pode haver
        #   casos em que o SELECT acima não retorna resultado nenhum.
        #   Acrescentei o if para que não dê erros na página.
        $msg_revenda_info = "";
        if (pg_num_rows ($res_revenda) > 0) {
            $revenda_nome_1       = trim(pg_fetch_result($res_revenda,0,nome));
            $revenda_cnpj_1       = trim(pg_fetch_result($res_revenda,0,cnpj));

            $revenda_bairro_1     = trim(pg_fetch_result($res_revenda,0,bairro));
            $revenda_cidade_1     = trim(pg_fetch_result($res_revenda,0,cidade));
            $revenda_fone_1       = trim(pg_fetch_result($res_revenda,0,fone));
        } else {
            $msg_revenda_info = traduz("nao.foi.possivel.obter.informacoes.da.revenda.cliente.colormaq.nome.cnpj.e.telefone",$con,$cook_idioma);
        }

?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <tr>
        <td class='inicio' colspan='4' height='15'>&nbsp;<?if($sistema_lingua=='ES')echo traduz("informacoes.da.revenda",$con,$cook_idioma);else echo traduz("informacoes.da.revenda",$con,$cook_idioma )."(CLIENTE COLORMAQ)";?></td>
    </tr>
    <? if (strlen($msg_revenda_info) > 0){
                    echo "<tr>";
                    echo "<td class='conteudo' colspan= '4' height='15'><center>$msg_revenda_info</center></td>";
                    echo "</tr>";
                } ?>

    <TR>
        <TD class="titulo"  height='15' ><?echo traduz("nome",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_nome_1 ?></TD>
        <TD class="titulo"  height='15' width='80'><?echo traduz("cnpj.revenda",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj_1 ?></TD>
    </TR>
    <TR>
    <?//HD 6701 15529 Para posto 4260 Ivo Cardoso mostra a nota fiscal?>
        <TD class="titulo"  height='15'><?echo traduz("fone",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15'>&nbsp;<?=$revenda_fone_1?></TD>
        <TD class="titulo"  height='15'>&nbsp;<?echo traduz("data.da.nf",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15'>&nbsp;<?=$data_venda; ?></TD>
    </TR>
</TABLE>
<?
    }
}
/*COLORMAQ TEM 2 REVENDAS - FIM*/
?>

<? // hd 45748
if($login_fabrica == 20){

	$sql = "SELECT os
		FROM tbl_os_troca_motivo
		WHERE os = $os ";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res)>0) {
	    $motivo1 = "Não são fornecidas peças de reposição para este produto";
	    $motivo2 = "Há peça de reposição, mas está em falta";
	    $motivo3 = "Vicio do produto";
	    $motivo4 = "Divergência de voltagem entre embalagem e produto";
	    $motivo5 = "Informações adicionais";
	    $motivo6 = "Informações complementares";
	    $troca = true;
	?>

	<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	    <tr>
		<td class='inicio' colspan='4' height='15'>
	<?
	if($sistema_lingua=='ES')echo "Informaciones sobre la RAZÓN DE CAMBIO";
	else {
	    echo "Informações sobre o MOTIVO DA TROCA";
	}
	?>

		</td>
	    </tr>
	    <tr>
		<td class="conteudo">
	<div>
	    <div>
	<?

		$sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
				tbl_causa_defeito.codigo        AS causa_codigo     ,
				tbl_causa_defeito.descricao     AS causa_defeito
			FROM   tbl_os_troca_motivo
			JOIN   tbl_servico_realizado USING(servico_realizado)
			JOIN   tbl_causa_defeito     USING(causa_defeito)
			WHERE os     = $os
			AND   motivo = '$motivo1'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)==1){
		    echo "OK";
		    $identificacao1 = pg_fetch_result($res,0,servico_realizado);
		    $causa_defeito1 = pg_fetch_result($res,0,causa_codigo)." - ".pg_fetch_result($res,0,causa_defeito);
		?>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 200px; " nowrap>
			    Data de entrada do produto na assistência técnica
			</div>
		    </div>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 200px;font:75%">
			    <? echo $data_abertura; ?>
			</div>
		    </div>

		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 200px; " nowrap>
			    <br><? echo $motivo1; ?>
			</div>
		    </div>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 200px; " nowrap>
			    Identificação do defeito
			</div>
			<div id="contentleft2" style="width: 250px; ">
			    Defeito
			</div>
		    </div>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 200px;font:75%">
			    <? echo $identificacao1; ?>
			</div>
			<div id="contentleft" style="width: 250px;font:75%">
			    <? echo $causa_defeito1; ?>
			</div>
		    </div>
		    <?
		    }
		    $sql = "SELECT
				    TO_CHAR(data_pedido,'DD/MM/YYYY') AS data_pedido    ,
				    pedido                                              ,
				    PE.referencia                     AS peca_referencia,
				    PE.descricao                      AS peca_descricao
			    FROM   tbl_os_troca_motivo
			    JOIN   tbl_peca            PE USING(peca)
			    WHERE os     = $os
			    AND   motivo = '$motivo2'";
		    $res = pg_query($con,$sql);
		    if(pg_num_rows($res)==1){
			$peca_referencia = pg_fetch_result($res,0,peca_referencia);
			$peca_descricao  = pg_fetch_result($res,0,peca_descricao);
			$data_pedido     = pg_fetch_result($res,0,data_pedido);
			$pedido          = pg_fetch_result($res,0,pedido);



		    ?>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 200px; " nowrap>
			    <br><? echo $motivo2?>
			</div>
		    </div>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 200px; " nowrap>
			    Código da Peça
			</div>
			<div id="contentleft2" style="width: 200px; ">
			    Data do Pedido
			</div>
			<div id="contentleft2" style="width: 200px; ">
			    Número do Pedido
			</div>
		    </div>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 200px;font:75%">
			    <? echo $peca_referencia."-".$peca_descricao; ?>
			</div>
			<div id="contentleft" style="width: 200px;font:75%">
			    <? echo $data_pedido; ?>
			</div>
			<div id="contentleft" style="width: 200px;font:75%">
			    <? echo $pedido; ?>
			</div>
		    </div>
		    <?
		    }

		    $sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
				    tbl_causa_defeito.codigo        AS causa_codigo     ,
				    tbl_causa_defeito.descricao     AS causa_defeito    ,
				    observacao
			    FROM   tbl_os_troca_motivo
			    JOIN   tbl_servico_realizado USING(servico_realizado)
			    JOIN   tbl_causa_defeito     USING(causa_defeito)
			    WHERE os     = $os
			    AND   motivo = '$motivo3'";
		    $res = pg_query($con,$sql);
		    if(pg_num_rows($res)==1){
			$identificacao2 = pg_fetch_result($res,0,servico_realizado);
			$causa_defeito2 =  pg_fetch_result($res,0,causa_codigo)." - ".pg_fetch_result($res,0,causa_defeito);
			$observacao1    = pg_fetch_result($res,0,observacao);

		    ?>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 200px; " nowrap>
			    <br><? echo $motivo3?>
			</div>
		    </div>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 200px; " nowrap>
			    Identificação do Defeito
			</div>
			<div id="contentleft2" style="width: 200px; ">
			    Defeito
			</div>
			<div id="contentleft2" style="width: 200px; ">
			    Quais as OSs deste produto:
			</div>
		    </div>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 200px;font:75%">
			    <? echo $identificacao2; ?>
			</div>
			<div id="contentleft" style="width: 200px;font:75%">
			    <? echo $causa_defeito2; ?>
			</div>
			<div id="contentleft" style="width: 200px;font:75%">
			    <? echo $observacao1; ?>
			</div>
		    </div>
		    <?
		    }

		    $sql = "SELECT observacao
			    FROM   tbl_os_troca_motivo
			    WHERE os     = $os
			    AND   motivo = '$motivo4'";
		    $res = pg_query($con,$sql);
		    if(pg_num_rows($res)==1){
			$observacao2    = pg_fetch_result($res,0,observacao);
		    ?>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 200px; " nowrap>
			    <br><? echo $motivo4; ?>
			</div>
		    </div>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 650px; " nowrap>
			    Qual a divergência:
			</div>
		    </div>
		    <div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 200px;font:75%">
			    <? echo $observacao2; ?>
			</div>
		    </div>
		    <?
		    }
		    ?>
		</h2>
	    </div>
	</div>
	<?
	    $sql = "SELECT observacao
		    FROM   tbl_os_troca_motivo
		    WHERE os     = $os
		    AND   motivo = '$motivo5'";
	    $res = pg_query($con,$sql);
	    if(pg_num_rows($res)==1){
		$observacao3    = pg_fetch_result($res,0,observacao);


		?>
		<div>
		    <div>
			<h2><?=$motivo5?>
			<div id="contentcenter" style="width: 650px;">
			    <div id="contentleft" style="width: 650px;font:75%"><? echo $observacao3;?></div>
			</div>
			</h2>
		    </div>
		</div>
		<?
	    }
	    /* HD 43302 - 26/9/2008 */
	    $sql = "SELECT observacao
		    FROM   tbl_os_troca_motivo
		    WHERE os     = $os
		    AND   motivo = '$motivo6'";
	    $res = pg_query($con,$sql);
	    if(pg_num_rows($res)==1){
		    $observacao4    = pg_fetch_result($res,0,observacao);
		    $observacao4 = wordwrap($observacao4, 76, '<br/>', true);
		?>
		<div>
		    <div>
			<h2><?fecho("informacoes.complementares",$con,$cook_idioma);?>
			<div id="contentcenter" style="width: 650px;">
			    <div id="contentleft" style="width: 650px;font:75%"><? echo $observacao4;?></div>
			</div>
			</h2>
		    </div>
		</div>
		<?
	    }
	}
}
?>
        </td>
    </tr>
</table>
<?php 
if($moduloGestaoContrato && strlen($representante) > 0){

    $sqlRepresentante = "SELECT * FROM tbl_representante WHERE representante={$representante} AND fabrica={$login_fabrica}";
    $resRepresentante = pg_query($con, $sqlRepresentante);
    $representante_codigo = pg_fetch_result($resRepresentante, 0, 'codigo');
    $representante_nome = pg_fetch_result($resRepresentante, 0, 'nome');
    $cpf_cnpj_representante = pg_fetch_result($resRepresentante, 0, 'cnpj');
    $fone_representante = pg_fetch_result($resRepresentante, 0, 'fone');
    $email_representante = pg_fetch_result($resRepresentante, 0, 'email');


?>
  
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <TR>
        <TD class='inicio' colspan='4' height='15'>&nbsp;INFORMAÇÕES DO REPRESENTANTE</TD>
    </TR>
    <TR>
        <TD class="titulo"  height='15'>CÓDIGO&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<?php echo $representante_codigo;?></TD>
        <TD class="titulo"  height='15'>NOME&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<?php echo $representante_nome; ?></TD>
    </TR>
    <TR>
        <TD class="titulo"  height='15'>CPF/CNPJ&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<?php echo $cpf_cnpj_representante;?></TD>
        <TD class="titulo"  height='15'>FONE&nbsp;</TD>
        <TD class="conteudo"  height='15'>&nbsp;<?php echo $fone_representante; ?></TD>
    </TR>
    <TR>
        <TD class="titulo"  height='15'>EMAIL&nbsp;</TD>
        <TD class="conteudo"  height='15' colspan="3">&nbsp;<?php echo $email_representante;?></TD>
    </TR>
</TABLE>
<?php } ?>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <? if(!in_array($login_fabrica,array(122,143,156))){ ?>
    <tr>
        <?php
        if ($login_fabrica == 145) {
        ?>
            <td class='inicio' colspan='4' height='15'>&nbsp;<?= traduz("informacoes.da.revenda/construtora") ?></td>
        <?php
        } else {
        ?>
            <td class='inicio' colspan='4' height='15'>&nbsp;<?echo traduz("informacoes.da.revenda",$con,$cook_idioma); if($login_fabrica==50){ echo " (".traduz("consumidor",$con,$cook_idioma).")";}?></td>
        <?php
        }
        ?>
    </tr>
    <? }else{ ?>
    <tr>
        <td class='inicio' colspan='4' height='15'>&nbsp;<?echo traduz("informacoes.da.nota.fiscal",$con,$cook_idioma); if($login_fabrica==50){ echo " (".traduz("consumidor",$con,$cook_idioma).")";}?></td>
    </tr>
    <? } ?>

    <? if(!in_array($login_fabrica,array(20,122,143,156))){
	if (isset($novaTelaOs)) {
		$colspan = 1;
	}
    
	?>
        <TR>
            <TD class="titulo"  height='15' ><?echo traduz("nome",$con,$cook_idioma);?></TD>
            <TD class="conteudo"  height='15' width='300' <?=$colspan?>>&nbsp;<? echo $revenda_nome ?></TD>
            <? if($consumidor_revenda == 'REVENDA') { ?>
                <TD class="titulo"  height='15' width='80'><?echo traduz("cnpj.revenda",$con,$cook_idioma);?></TD>
                <TD class="conteudo"  height='15' <?php if (in_array($login_fabrica,array(174,191))) echo "colspan='3'";?>>&nbsp;<? echo $revenda_cnpj ?></TD>
            <? } else if($consumidor_revenda == 'CONSUMIDOR') { 
                    $sql_revenda_cnpj = "SELECT
                                        rev.cnpj
                                        from tbl_os tos
                                        join tbl_revenda rev on rev.revenda = tos.revenda
                                        where tos.os = {$os}
                                        and tos.fabrica = {$login_fabrica}";
                    
                    $res_revenda_cnpj = pg_query($con, $sql_revenda_cnpj);
                    $revenda_cpf_cnpj = pg_fetch_result($res_revenda_cnpj, 0, cnpj);

                    if(strlen($revenda_cpf_cnpj) == 11){
                        ?> <TD class="titulo"  height='15' width='80'><?echo traduz("cpf.revenda",$con,$cook_idioma);?></TD> <?
                        $revenda_cpf_cnpj = substr($revenda_cpf_cnpj,0,3) .".". substr($revenda_cpf_cnpj,3,3) .".". substr($revenda_cpf_cnpj,6,3) ."-". substr($revenda_cpf_cnpj,9,2);
                    }elseif (strlen($revenda_cpf_cnpj) == 14){
                        ?> <TD class="titulo"  height='15' width='80'><?echo traduz("cnpj.revenda",$con,$cook_idioma);?></TD> <?
                        $revenda_cpf_cnpj = substr($revenda_cpf_cnpj,0,2) .".". substr($revenda_cpf_cnpj,2,3) .".". substr($revenda_cpf_cnpj,5,3) ."/". substr($revenda_cpf_cnpj,8,4) ."-". substr($revenda_cpf_cnpj,12,2);
                    } else {
                        ?> <TD class="titulo"  height='15' width='80'><?echo traduz("cnpj.revenda",$con,$cook_idioma);?></TD> <?
                    }
            ?>                
                <TD class="conteudo"  height='15' <?php if (in_array($login_fabrica,array(174,191))) echo "colspan='3'";?>>&nbsp;<? echo $revenda_cpf_cnpj ?></TD>
            <? } ?>
        </TR>
    <? }
    if (in_array($login_fabrica, array(169,170))) { ?>
        <tr>
            <td class="titulo"><?= traduz("contato") ?></td>
            <td class="conteudo"><?= $revenda_contato; ?></td>
            <td class="titulo"></td>
            <td class="conteudo"></td>
        </tr>
    <? } 
       if ($login_fabrica == 1) { 
        $sql_r = "SELECT tbl_cidade.nome AS rev_cidade, tbl_cidade.estado AS rev_estado FROM tbl_revenda JOIN tbl_cidade USING(cidade) WHERE revenda = $revenda";
        $res_r = pg_query($con, $sql_r);
    ?>
        <tr>
            <td class="titulo"><?= traduz("cidade") ?></td>
            <td class="conteudo"><?=pg_fetch_result($res_r,0,'rev_cidade')?></td>
            <td class="titulo"><?= traduz("estado") ?></td>
            <td class="conteudo"><?=pg_fetch_result($res_r,0,'rev_estado')?></td>
        </tr>
    <? } ?>
    <TR>
    <?php
    if($login_fabrica == 141 && $posto_interno == true){
        $codigo_rastreio = "Código Rastreio";
    }
    ?>
    <?//HD 6701 15529 Para posto 4260 Ivo Cardoso mostra a nota fiscal?>
        <?php if($login_fabrica == 186 and $desc_tipo_atendimento == "Garantia Certificado"){?>
            <TD class="titulo"  height='15'>Nº Certificado</TD>
        <?php }else{?>
            <TD class="titulo"  height='15'><?echo traduz("nf.numero",$con,$cook_idioma);?></TD>
        <?php } ?>


        <TD class="conteudo vermelho"  height='15'>&nbsp;<? if($login_fabrica==6 and $login_posto==4260 and strlen($nota_fiscal_saida)>0) echo $nota_fiscal_saida ; else echo $nota_fiscal ?></FONT></TD>
        <TD class="titulo"  height='15'><?echo traduz("data.da.nf",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15'>&nbsp;<? if($login_fabrica==6 and $login_posto==4260 and strlen($data_nf_saida)>0) echo $data_nf_saida ; else echo $data_nf; ?></TD>
        
        <?php if ($login_fabrica == 174) { /*HD - 6015269*/
            $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
            $aux_res = pg_query($con, $aux_sql);
            $aux_arr = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);

            if (empty($aux_arr["valor_nf"])) {
                $valor_nf = "";
            } else {
                $valor_nf = $aux_arr["valor_nf"];
            }?>
            <TD class="titulo"  height='15'>VALOR NF&nbsp;</TD>
            <TD class="conteudo"  height='15'><?=$valor_nf;?>&nbsp;</TD>
        <?PHP } ?>

        <?php if ($login_fabrica == 191) { /*HD - 6015269*/
            $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
            $aux_res = pg_query($con, $aux_sql);
            $aux_arr = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);
            ?>
            <TD class="titulo"  height='15'>NF DE REMESSA&nbsp;</TD>
            <TD class="conteudo"  height='15'><?=$aux_arr['numero_nf_remessa'];?>&nbsp;</TD>
        <?PHP } ?>
    </TR>
     <? if (($login_fabrica <> 15 AND $login_fabrica <> 122 AND $login_fabrica <> 20 && !isset($novaTelaOs)) || in_array($login_fabrica, [139,165])) { ?>
        <TR>
            <?php
            if ($login_fabrica != 165) {
            ?>
                <TD class="titulo"  height='15' ><?echo traduz("fone",$con,$cook_idioma);?></TD>
                <TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_fone?></TD>
            <?php
            }
            ?>
            <TD class="titulo"  height='15'>
                <?
                if( in_array($login_fabrica, array(11,172)) ) {
                    echo traduz("email",$con,$cook_idioma);
                }

                if ($login_fabrica == 165) {
                    $codigo_rastreio = "Tipo Entrega";
                }

                echo $codigo_rastreio
                ?>
            </TD>
            <TD class="conteudo"  height='15'>&nbsp;
                <?
                if( in_array($login_fabrica, array(11,172)) ) {
                    echo $revenda_email;
                } else {
                    if (in_array($login_fabrica, array(141,165)) && !empty($cod_rastreio) && $cod_rastreio != "balcão") {

                        if (in_array($login_fabrica, array(165)) && $cod_rastreio == "sem_rastreio") {

                        } else {
                            echo "<a href='#' class='correios' rel='$cod_rastreio' >$cod_rastreio</a>";
                        }
                    } else {
                        echo $cod_rastreio;
                    }
                }
                ?>
            </TD>
        </TR>
    <? } ?>


<? //////ADICIONA OS DOIS NOVOS CAMPOS NO RELATÓRIO PARA LENOXX

    if( in_array($login_fabrica, array(11,81,96,172)) ){

        $sql = "SELECT nota_fiscal_saida,
            to_char(data_nf_saida, 'DD/MM/YYYY') as data_nf_saida
            FROM   tbl_os
            WHERE os     = $os
            ";

        $res = pg_query($con,$sql);

        if(pg_num_rows($res)==1){

            $nota_fiscal_saida    = pg_fetch_result($res,0,nota_fiscal_saida);
            $data_nf_saida        = pg_fetch_result($res,0,data_nf_saida);

            ?>
             <TR>
                <TD class="titulo"  height='15' ><?= traduz("nf.de.saida") ?></TD>
                <TD class="conteudo"  height='15' width='300'><? echo $nota_fiscal_saida;?></TD>
                <TD class="titulo"  height='15'>DATA&nbsp;NF&nbsp;DE&nbsp;SAIDA</TD>
                <TD class="conteudo"  height='15'><? echo $data_nf_saida;?></TD>
            </TR>
        <?
        }

    }
    if ($login_fabrica == 3){

        $specialChar = array("(",")",".");
        $os = str_replace($specialChar, "", $os);

        $sql = "SELECT
                    tbl_revenda_fabrica.contato_endereco,
                    tbl_revenda_fabrica.contato_numero,
                    tbl_revenda_fabrica.contato_complemento,
                    tbl_revenda_fabrica.contato_bairro,
                    tbl_ibge.cidade,
                    tbl_ibge.estado
                FROM tbl_os
                JOIN tbl_revenda on (tbl_os.revenda_cnpj = tbl_revenda.cnpj)
                JOIN tbl_revenda_fabrica on (tbl_revenda.cnpj = tbl_revenda_fabrica.cnpj and tbl_revenda.revenda = tbl_revenda_fabrica.revenda)
                JOIN tbl_fabrica on (tbl_revenda_fabrica.fabrica = tbl_fabrica.fabrica and tbl_fabrica.fabrica=$login_fabrica)
                LEFT JOIN tbl_ibge on (tbl_revenda_fabrica.contato_cidade = tbl_ibge.cod_ibge)

                WHERE tbl_os.os = $os
                AND tbl_os.fabrica=$login_fabrica
        ";

        $res = pg_query($con,$sql);

        $contato_endereco    = pg_result($res,0,0);
        $contato_numero      = pg_result($res,0,1);
        $contato_complemento = pg_result($res,0,2);
        $contato_bairro      = pg_result($res,0,3);
        $cidade              = pg_result($res,0,4);
        $estado              = pg_result($res,0,5);

        if (empty($cidade)) {
            $sql_cidade = "SELECT tbl_ibge.cidade, tbl_ibge.estado
                           FROM tbl_os
                           JOIN tbl_revenda ON tbl_os.revenda_cnpj = tbl_revenda.cnpj
                           JOIN tbl_revenda_fabrica ON tbl_revenda.cnpj = tbl_revenda_fabrica.cnpj AND tbl_revenda.revenda = tbl_revenda_fabrica.revenda
                           JOIN tbl_fabrica ON tbl_revenda_fabrica.fabrica = tbl_fabrica.fabrica AND tbl_fabrica.fabrica = $login_fabrica
                           JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade
                           LEFT JOIN tbl_ibge ON tbl_cidade.cod_ibge = tbl_ibge.cod_ibge
                           WHERE tbl_os.os = $os
                           AND tbl_os.fabrica = $login_fabrica";
                           
            $res_cidade = pg_query($con, $sql_cidade);

            $cidade = pg_result($res_cidade,0,'cidade');
            $estado = pg_result($res_cidade,0,'estado');
        }

    ?>

        <TR>
            <TD class="titulo"  height='15'><?= traduz("endereco") ?>&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<?echo $contato_endereco;?></TD>
            <TD class="titulo"  height='15'><?= traduz("numero") ?>&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<? echo $contato_numero; ?></TD>
        </TR>

        <TR>
            <TD class="titulo"  height='15'><?= traduz("complemento") ?>&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<?echo $contato_complemento;?></TD>
            <TD class="titulo"  height='15'><?= traduz("bairro") ?>&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<? echo $contato_bairro; ?></TD>
        </TR>

        <TR>
            <TD class="titulo"  height='15'><?= traduz("cidade") ?>&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<?echo $cidade;?></TD>
            <TD class="titulo"  height='15'><?= traduz("estado") ?>&nbsp;</TD>
            <TD class="conteudo"  height='15'>&nbsp;<? echo $estado; ?></TD>
        </TR>
    <?
    }

    if ($login_fabrica == 145) {
    ?>
        <TR>
            <TD class="titulo"  height='15'>CONSTRUTORA&nbsp;</TD>
            <TD class="conteudo"  height='15' colspan="3">&nbsp;<?echo $os_construtora;?></TD>
        </TR>
    <?php
    }
    ?>
</TABLE>

<?
    /* HD 26244 */
    //echo $certificado_garantia."asdfasdfasdfasd";
    if ($login_fabrica==30 AND strlen($certificado_garantia)>0){

        $sql_status = " SELECT  status_os,
                                observacao,
                                to_char(data, 'DD/MM/YYYY')   as data_status
                        FROM tbl_os_status
                        WHERE os = $os
                        AND status_os IN (105,106,107)
                        ORDER BY tbl_os_status.data DESC
                        LIMIT 1 ";
        $res_status = pg_query($con,$sql_status);
        $resultado = pg_num_rows($res_status);
        if ($resultado>0){
                $estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
                $estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
                $estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

                if ($estendida_status_os == 105){
                    $estendida_observacao = "OS em auditoria";
                }
                if ($estendida_status_os == 106){
                    $estendida_observacao = "OS Aprovada na Auditoria";
                }
                if ($estendida_status_os == 107){
                    $estendida_observacao = "OS Recusada na Auditoria";
                }
            ?>

        <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <tr>
                <td class='inicio' colspan='4' height='15'>&nbsp;<?= traduz("garantia.estendida") ?> </td>
            </tr>
            <TR>
                <TD class="titulo"  height='15' width='90'>LGI</TD>
                <TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $certificado_garantia ?></TD>
                <TD class="titulo"  height='15' width='80'><?= traduz("status.atual") ?></TD>
                <TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
            </TR>
        </TABLE>
<?
        }
    }
?>

<?
    /* HD 209166 */
    if ($login_fabrica==30){

        $sql_status = " SELECT  status_os,
                                observacao,
                                to_char(data, 'DD/MM/YYYY')   as data_status
                        FROM tbl_os_status
                        WHERE os = $os
                        AND status_os IN (132,19)
                        ORDER BY tbl_os_status.data DESC
                        LIMIT 1 ";
        $res_status = pg_query($con,$sql_status);
        $resultado = pg_num_rows($res_status);
        if ($resultado>0){
                $estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
                $estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
                $estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

                if ($estendida_status_os == 132){
                    $estendida_observacao = "OS em auditoria de reincidência";
                }
            ?>

        <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <tr>
                <td class='inicio' colspan='4' height='15'>&nbsp;<?= traduz("auditoria.de.reincidencia") ?></td>
            </tr>
            <TR>
                <TD class="titulo"  height='15' width='80'><?= traduz("status.atual") ?>&nbsp;</TD>
                <TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
            </TR>
        </TABLE>
<?
        }
    }
?>

<?
    /* HD 209166 */
    if ($login_fabrica==30){

        $sql_status = " SELECT  status_os,
                                observacao,
                                to_char(data, 'DD/MM/YYYY')   as data_status
                        FROM tbl_os_status
                        WHERE os = $os
                        AND status_os IN (102,103,104)
                        ORDER BY tbl_os_status.data DESC
                        LIMIT 1 ";
        $res_status = pg_query($con,$sql_status);
        $resultado = pg_num_rows($res_status);
        if ($resultado>0){
                $estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
                $estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
                $estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

                if ($estendida_status_os == 102){
                    $estendida_observacao = "OS em auditoria de número de série";
                }
            ?>

        <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <tr>
                <td class='inicio' colspan='4' height='15'>&nbsp;<?= traduz("auditoria.de.numero.de.serie") ?></td>
            </tr>
            <TR>
                <TD class="titulo"  height='15' width='80'><?= traduz("status.atual") ?>&nbsp;</TD>
                <TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
            </TR>
        </TABLE>
<?
        }
    }
?>

<?
    /* HD 209166 */
    if ($login_fabrica==30){
        $sql_status = " SELECT  status_os,
                                observacao,
                                to_char(data, 'DD/MM/YYYY')   as data_status
                        FROM tbl_os_status
                        WHERE os = $os
                        AND status_os IN (98,99,100,101)
                        ORDER BY tbl_os_status.data DESC
                        LIMIT 1 ";
                        //echo $sql_status;
        $res_status = pg_query($con,$sql_status);
        $resultado = pg_num_rows($res_status);
        if ($resultado>0){
                $estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
                $estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
                $estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

                if ($estendida_status_os == 98){
                    $estendida_observacao = "OS em auditoria de ". number_format($qtde_km, 2, ',', '.'). " Km";
                }
            ?>

        <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <tr>
                <td class='inicio' colspan='4' height='15'>&nbsp;<?= traduz("auditoria.de.km") ?></td>
            </tr>
            <TR>
                <TD class="titulo"  height='15' width='80'><?= traduz("status.atual") ?>&nbsp;</TD>
                <TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
            </TR>
        </TABLE>
<?
        }
    }
?>

<style>
    .Tabela td {
        height: 15px; 
    }
    .Postagem td {
        width: auto !important;
        text-align: center !important;
    }
</style>

<?
/*takashi compressores*/
if($login_fabrica==1){
    if($tipo_os == 13){
        $where_visita= " os_revenda=$os_numero";
    } else {
        $where_visita= "os=$os";
    }
    $sql = "SELECT     os                                  ,
                    to_char(data, 'DD/MM/YYYY') as  data,
                    to_char(hora_chegada_cliente, 'HH24:MI') as inicio      ,
                    to_char(hora_saida_cliente, 'HH24:MI')   as fim         ,
                    km_chegada_cliente   as km          ,
                    valor_adicional                     ,
                    justificativa_valor_adicional,
                    qtde_produto_atendido
            FROM tbl_os_visita
            WHERE $where_visita";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){

        echo "<table border='0' cellpadding='0' cellspacing='1' width='700px' align='center' class='Tabela'>";
        echo "<tr class='inicio'>";
        if($tipo_os == 13){
            echo "<td width='100%' colspan='6'>&nbsp;".traduz("despesas.da.os.geo.metal").": $os_numero</td>";
        } else {
            echo "<td width='100%' colspan='6'>&nbsp;".traduz("despesas.de.compressores",$con,$cook_idioma)."</td>";
        }
        echo "</tr>";
        echo "<tr>";
        echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("data.da.visita",$con,$cook_idioma)."</font></td>";
        echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("hora.inicio",$con,$cook_idioma)."</font></td>";
        echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("hora.fim",$con,$cook_idioma)."</font></td>";
        echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("km",$con,$cook_idioma)."</font></td>";
        if($tipo_os ==13){
            echo "<td nowrap class='titulo2' rowspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("qtde.produto.atendido",$con,$cook_idioma)."</font></td>";
        }

        echo "<td nowrap class='titulo2' colspan='2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("despesas.adicionais",$con,$cook_idioma)."</font></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td nowrap class='titulo2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("valor",$con,$cook_idioma)."</font></td>";
        echo "<td nowrap class='titulo2'>
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("justificativa",$con,$cook_idioma)."</font></td>";
        echo "</tr>";

        for($i=0;$i<pg_num_rows($res);$i++){

            $data                          = pg_fetch_result($res,$i,data);
            $inicio                        = pg_fetch_result($res,$i,inicio);
            $fim                           = pg_fetch_result($res,$i,fim);
            $km                            = pg_fetch_result($res,$i,km);
            $valor_adicional               = pg_fetch_result($res,$i,valor_adicional);
            $justificativa_valor_adicional = pg_fetch_result($res,$i,justificativa_valor_adicional);
            $qtde_produto_atendido         = pg_fetch_result($res,$i,qtde_produto_atendido);

            echo "<tr class='conteudo'>";
            echo "<td align='center'>&nbsp;$data                         </td>";
            echo "<td align='center'>&nbsp;$inicio                       </td>";
            echo "<td align='center'>&nbsp;$fim                          </td>";
            echo "<td align='center'>&nbsp;$km                           </td>";
            if($tipo_os ==13){
                echo "<td align='center'>&nbsp;$qtde_produto_atendido    </td>";
            }
            echo "<td align='center'>&nbsp;".number_format($valor_adicional,2,",",".")."         </td>";
            echo "<td align='center'>&nbsp;$justificativa_valor_adicional</td>";
            echo "</tr>";
        }

        echo "</table>";

    }
}
 ?>

<?

    $specialChar = array("(",")",".");
    $os = str_replace($specialChar, "", $os);

    $campos_black = "";
    $cond_black   = "";
    if ($login_fabrica == 1) {

        $campos_black = "tbl_depara.para                       ,
                         tbl_peca_fora_linha.peca_fora_linha   ,
                        ";

        $cond_black   = " LEFT JOIN tbl_depara ON tbl_depara.peca_de = tbl_peca.peca AND tbl_depara.fabrica = $login_fabrica
                          LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = tbl_peca.peca AND tbl_peca_fora_linha.fabrica = $login_fabrica
                        ";
    }
    $sql = "SELECT  tbl_produto.referencia                                        ,
                    tbl_produto.descricao                                         ,
                    tbl_os_produto.serie                                          ,
                    tbl_os_produto.versao                                         ,
                    tbl_os_item.serigrafia                                        ,
                    tbl_os_item.pedido    AS pedido                               ,
                    tbl_os_item.peca                                              ,
                    TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item,
                    tbl_defeito.descricao AS defeito                              ,
                    tbl_peca.referencia   AS referencia_peca                      ,
                    tbl_peca.referencia_fabrica     AS peca_referencia_fabrica             ,

                    tbl_os_item_nf.nota_fiscal                                    ,
                    tbl_peca.descricao    AS descricao_peca                       ,
                    tbl_servico_realizado.descricao AS servico_realizado_descricao,
                    tbl_status_pedido.descricao     AS status_pedido              ,
                    tbl_produto.referencia          AS subproduto_referencia      ,
                    tbl_produto.descricao           AS subproduto_descricao       ,
                    tbl_os_item.parametros_adicionais                             ,
                    tbl_lista_basica.posicao
            FROM    tbl_os_produto
            JOIN    tbl_os_item USING (os_produto)
            JOIN    tbl_produto USING (produto)
            JOIN    tbl_peca    USING (peca)
            JOIN    tbl_lista_basica       ON  tbl_lista_basica.produto = tbl_os_produto.produto
                                           AND tbl_lista_basica.peca    = tbl_peca.peca
            LEFT JOIN    tbl_defeito USING (defeito)
            LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
            LEFT JOIN    tbl_os_item_nf    ON  tbl_os_item.os_item      = tbl_os_item_nf.os_item
            LEFT JOIN    tbl_pedido        ON  tbl_os_item.pedido       = tbl_pedido.pedido
            LEFT JOIN    tbl_status_pedido ON  tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
            WHERE   tbl_os_produto.os = $os
            ORDER BY tbl_peca.descricao";

    # HD 153693
    $ordem = ( in_array($login_fabrica, array(11,172)) ) ? " ORDER BY tbl_os_item.digitacao_item ,tbl_peca.referencia  " : " ORDER BY tbl_os_item.os_item DESC ";

    if ($login_fabrica == 161) {
        $campo_ext = 'tbl_peca.ipi,';
    }

    if (in_array($login_fabrica, [169,170,183])) {
        $campoCausaDefeito = "tbl_causa_defeito.descricao AS causa_defeito,";
        $joinCausaDefeito = "LEFT JOIN tbl_causa_defeito ON tbl_causa_defeito.causa_defeito = tbl_os_item.causa_defeito AND tbl_causa_defeito.fabrica = {$login_fabrica}";
    }

    $cond_bloqueada_auditoria = '';

    if (in_array($login_fabrica, [24])) {
        $cond_bloqueada_auditoria = "AND tbl_servico_realizado.servico_realizado NOT IN (11417,11421)";
    }

    $sql = "/*( */
            SELECT  tbl_produto.referencia                                         ,
                    tbl_produto.descricao                                          ,
                    tbl_os_produto.serie                                           ,
                    tbl_os_produto.versao                                          ,
                    tbl_os_item.os_item                                            ,
                    tbl_os_item.serigrafia                                         ,
                    tbl_os_item.pedido                                             ,
                    tbl_os_item.pedido_item                                        ,
                    tbl_os_item.peca                                               ,
                    tbl_os_item.porcentagem_garantia                               ,
                    tbl_os_item.os_por_defeito                                     ,
                    tbl_os_item.peca_causadora                                     ,
                    tbl_os_item.soaf                                               ,
                    tbl_os_item.posicao                                            ,
                    tbl_os_item.obs                                                ,
                    tbl_os_item.custo_peca                                         ,
                    tbl_os_item.servico_realizado AS servico_realizado_peca        ,
                    tbl_os_item.peca_serie                                         ,
                    tbl_os_item.peca_serie_trocada                                 ,
		    {$campoCausaDefeito}
                    tbl_os_item.parametros_adicionais                              ,
                    tbl_peca.promocao_site                                         ,
                    $campo_ext
                    tbl_peca.parametros_adicionais AS parametros_adicionais_pecas  ,
                    {$campos_black}
                    TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item ,
                    case
                        when tbl_pedido.pedido_blackedecker > 499999 then
                            lpad ((tbl_pedido.pedido_blackedecker-500000)::text,5,'0')
                        when tbl_pedido.pedido_blackedecker > 399999 then
                            lpad ((tbl_pedido.pedido_blackedecker-400000)::text,5,'0')
                        when tbl_pedido.pedido_blackedecker > 299999 then
                            lpad ((tbl_pedido.pedido_blackedecker-300000)::text,5,'0')
                        when tbl_pedido.pedido_blackedecker > 199999 then
                            lpad ((tbl_pedido.pedido_blackedecker-200000)::text,5,'0')
                        when tbl_pedido.pedido_blackedecker > 99999 then
                            lpad ((tbl_pedido.pedido_blackedecker-100000)::text,5,'0')
                    else
                        lpad(tbl_pedido.pedido_blackedecker::text,5,'0')
                    end                                      AS pedido_blackedecker,
                    tbl_pedido.seu_pedido                    AS seu_pedido         ,
                    tbl_pedido.distribuidor                                        ,
                    tbl_pedido.status_pedido                 AS status_pedido_id   ,
                    TO_CHAR(tbl_pedido.previsao_entrega, 'DD/MM/YYYY') AS data_previsao_chegada,
                    tbl_defeito.descricao           AS defeito                     ,
                    tbl_peca.referencia             AS referencia_peca             ,
                    tbl_peca.referencia_fabrica     AS peca_referencia_fabrica             ,

                    tbl_peca.referencia             AS referencia_peca             ,
                    tbl_peca.bloqueada_garantia     AS bloqueada_pc                ,
                    tbl_peca.peca_critica           AS peca_critica                ,
                    tbl_peca.retorna_conserto       AS retorna_conserto            ,
                    tbl_os_item.peca_obrigatoria  AS devolucao_obrigatoria       ,
                    tbl_os_item_nf.nota_fiscal                                     ,
                    TO_CHAR(tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf        ,
                    tbl_peca.descricao              AS descricao_peca              ,";
                    //28/08/2010 MLG HD 237471  Fábricas que mostra o valor da peça, baseado na tbl_os_item
                    if($mostrar_valor_pecas){
                        if(in_array($login_fabrica,array(1))){
                            $sql .= "tbl_os_item.custo_peca  AS preco_peca                  ,
                                    tbl_os_item.custo_peca*qtde AS total_peca, ";
                        }
                    }

                    $sql.= "
                    tbl_servico_realizado.descricao AS servico_realizado_descricao ,
                    tbl_servico_realizado.peca_estoque  AS servico_realizado_estoque    ,
                    tbl_status_pedido.descricao     AS status_pedido               ,
                    tbl_produto.referencia          AS subproduto_referencia       ,
                    tbl_produto.descricao           AS subproduto_descricao        ,
                    tbl_os_item.preco                                              ,
                    tbl_os_item.qtde                                               ,
                    tbl_os_item.custo_peca                                         ,
                    tbl_os_item.faturamento_item    AS faturamento_item
            FROM    tbl_os_produto
            JOIN    tbl_os_item USING (os_produto)
            JOIN    tbl_produto USING (produto)
            JOIN    tbl_peca    USING (peca)
            LEFT JOIN tbl_defeito ON tbl_os_item.defeito = tbl_defeito.defeito
            LEFT JOIN tbl_servico_realizado USING (servico_realizado)
            LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
            LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
            LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
	    {$joinCausaDefeito}
            {$cond_black}
            WHERE   tbl_os_produto.os = $os
            $cond_bloqueada_auditoria
            $ordem
            ";
            /*echo nl2br($sql);
            die;*/
    $res = pg_query($con,$sql); 
    $total = pg_num_rows($res);

    if ($login_fabrica == 45 or $login_fabrica == 24) {

        $specialChar = array("(",")",".");
        $os = str_replace($specialChar, "", $os);

        $sql_orcamento = "SELECT
                            tbl_orcamento_item.pedido                                      ,
                            tbl_orcamento_item.pedido_item                                 ,
                            tbl_orcamento_item.peca                                        ,
                            tbl_orcamento_item.pedido                                      ,
                            tbl_peca.referencia_fabrica     AS peca_referencia_fabrica             ,
                            tbl_peca.referencia             AS referencia_peca             ,
                            tbl_peca.descricao              AS descricao_peca              ,
                            tbl_orcamento_item.servico_realizado AS servico_realizado_peca ,
                            tbl_servico_realizado.descricao AS servico_realizado_descricao ,
                            tbl_defeito.descricao           AS defeito                       ,
                            tbl_orcamento_item.preco                                       ,
                            tbl_orcamento_item.preco_venda                                 ,
                            tbl_orcamento.aprovado                                         ,
                            TO_CHAR (tbl_orcamento.data_digitacao,'DD/MM') AS data_digitacao,
                            tbl_orcamento_item.qtde
                            FROM
                            tbl_os
                            JOIN    tbl_orcamento ON tbl_orcamento.os = tbl_os.os
                            JOIN    tbl_orcamento_item ON tbl_orcamento_item.orcamento = tbl_orcamento.orcamento
                            JOIN    tbl_peca    USING (peca)
                            LEFT JOIN tbl_defeito USING (defeito)
                            LEFT JOIN tbl_servico_realizado USING (servico_realizado)
                            WHERE tbl_os.os = $os
                            ORDER BY tbl_peca.descricao";
        $res_orcamento = pg_query($con,$sql_orcamento);
    }

    ?>

    <?php if (in_array($login_fabrica,array(24,30,46,50,52,74,90,91,114,115,116,117,120,125,128,129,131,140))) {
        if ($login_fabrica == 52) {
            $descKm = "DADOS DO KM";
        } else {
            $descKm = "QUANTIDADE DE KM";
        }
        if ($login_fabrica == 90) { 
            if (!empty($qtde_km)) { ?>
                <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
                    <TR>
                        <TD class='inicio' colspan='2'><?=$descKm?></TD>
                    </TR>
                    <TR>
                        <TD class="titulo" width='100'>DESLOCAMENTO&nbsp;</TD>
                        <TD class="conteudo">&nbsp;
                        <?php echo number_format($qtde_km,2,',','.'); ?> KM </TD>
                    </TR>
                </TABLE>
<?php       }
        } else {
    ?><!-- Qtde de KM Wanke HD 375933 -->
        <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
            <TR>
                <TD class='inicio' colspan='2'> <?=$descKm?></TD>
            </TR>
            <TR>
                <TD class="titulo" width='100'><?= traduz("deslocamento") ?>&nbsp;</TD>
                <TD class="conteudo">&nbsp;
                    <?php echo number_format($qtde_km,2,',','.'); ?> KM
                    <?php

                        if (strlen($obs_adicionais)) {
                            if ($login_fabrica == 30) {
                                echo " - ({$obs_adicionais})";
                            } else if ($login_fabrica == 52) {
                                echo "<br>Dados sobre KM: ".$obs_adicionais;
                            }
                        }

                    ?>
                </TD>
            </TR>
        </TABLE><?php
        }
    }

    if ($login_fabrica == 1) {//HD 235182

        $sql = "SELECT codigo,
                       TO_CHAR(garantia_inicio, 'DD/MM/YYYY') as garantia_inicio,
                       TO_CHAR(garantia_fim, 'DD/MM/YYYY') as garantia_fim,
                       motivo
                  FROM tbl_certificado
                 WHERE os = $os
                   AND fabrica = $login_fabrica";

        $res_certificado = pg_query($con, $sql);
        $tot = pg_num_rows($res_certificado);

        if ($tot > 0) {?>

            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='tabela'>
                <tr>
                    <td class='inicio' colspan="4">&nbsp;<?= traduz("certificado.de.garantia") ?></td>
                </tr>
                <tr>
                    <th class="titulo2"><?= traduz("codigo") ?></th>
                    <th class="titulo2"><?= traduz("data.inicio") ?></th>
                    <th class="titulo2"><?= traduz("data.termino") ?></th>
                    <th class="titulo2"><?= traduz("motivo") ?></th>
                </tr><?php
                for ($i = 0; $i < $tot; $i++) {
                    echo '<tr>';
                        echo '<td class="conteudo" style="text-align:center;">'.pg_fetch_result($res_certificado, $i, 'codigo').'</td>';
                        echo '<td class="conteudo" style="text-align:center;">'.pg_fetch_result($res_certificado, $i, 'garantia_inicio').'</td>';
                        echo '<td class="conteudo" style="text-align:center;">'.pg_fetch_result($res_certificado, $i, 'garantia_fim').'</td>';
                        echo '<td class="conteudo" style="text-align:center;">'.pg_fetch_result($res_certificado, $i, 'motivo').'</td>';
                    echo '</tr>';
                }?>
            </table><?php

        }

    }

    if ($login_fabrica == 42 && $cortesia == 't') {
        $sqlGarantia = "
            SELECT  tbl_os.justificativa_adicionais
            FROM    tbl_os
            JOIN    tbl_auditoria_os USING (os)
            WHERE   os = $os
        ";
        $resGarantia = pg_query($con,$sqlGarantia);
        $justificativa_adicionais = pg_fetch_result($resGarantia,0,justificativa_adicionais);

        if (!empty($justificativa_adicionais)) {
?>
             <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
                <tbody>
                <tr>
                    <td class='inicio' colspan="2">&nbsp;<?= traduz("informacoes.sobre.garantia.em.cortesia") ?></td>
                </tr>
                <tr>
                    <td class="titulo" style="width:25%;"><?= traduz("garantia") ?></td>
                    <td class='conteudo'>&nbsp;<?=$justificativa_adicionais?></td>
                </tr>
                </tbody>
            </table>
<?
        }
    }
    if ($login_fabrica == 131) {

        $query_adicionais = "SELECT campos_adicionais 
               FROM tbl_os_campo_extra 
               WHERE os = {$os}";

        $res_adicionais = pg_query($con, $query_adicionais);

        $campos_adicionais = pg_fetch_result($res_adicionais, 0, campos_adicionais);

        $campos_adicionais = json_decode($campos_adicionais);
    ?>
        <table width="700px" border="0" cellspacing="1" cellpadding="0" align="center" class="Tabela">
            <tbody><tr>
                <td class="inicio" colspan="2"> Informaçoes sobre peça(s)</td>
            </tr>
            <tr>
                <td class="titulo" width="100">Estado da peça&nbsp;</td>

                <?php if ($campos_adicionais->tipo_envio_peca == "utilizar_estoque") { ?>

                    <td class="conteudo">&nbsp;Utilizar as peças do estoque da assistência</td>
                    <tr>
                        <td class="titulo" width="100">Prazo de entrega estimado&nbsp;</td>
                        <td class="conteudo">&nbsp;<?php echo date("d-m-Y", strtotime($campos_adicionais->previsao_entrega)); ?></td>
                    </tr>

                <?php } else { ?>

                     <td class="conteudo">&nbsp;Aguardar as peças serem enviadas pela fábrica</td>

                <?php } ?>
            </tr>

        </tbody></table>
    <?php } ?>

    <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
    <TR>
        <TD colspan="<? echo ($login_fabrica == 1)?"9":"7"?>" class='inicio'>
<? echo "&nbsp;".traduz("diagnosticos.componentes.manutencoes.executadas",$con,$cook_idioma);?>

</TD>
    </TR>
    <TR>
        <?php
            if($login_fabrica == 138):
        ?>
            <td class="titulo2"><?= traduz("produto") ?></td>
        <?php
            endif;
        ?>
    <!--     <TD class="titulo">EQUIPAMENTO</TD> -->
        <?
        if($os_item_subconjunto == 't') {
            echo "<TD class='titulo2'>".traduz("subconjunto",$con,$cook_idioma)."</TD>";
            echo "<TD class='titulo2'>".traduz("posicao",$con,$cook_idioma)."</TD>";
        }
        ?>
        <TD class="titulo2">
        <? echo  traduz("componente",$con,$cook_idioma); ?>
        </TD>

        <?php if ($login_fabrica == 175){ ?>
        <td class="titulo2"><?=traduz("serie", $con, $cook_idioma)?></td>
        <td class="titulo2"><?=traduz("qtde.disparos", $con, $cook_idioma)?></td>
        <td class="titulo2"><?=traduz("componente.raiz", $con, $cook_idioma)?></td>
        <?php } ?>

        <?php if ($login_fabrica == 177){ ?>
        <td class="titulo2"><?=traduz("lote", $con, $cook_idioma)?></td>
        <td class="titulo2"><?=traduz("lote.nova.peca", $con, $cook_idioma)?></td>
        <?php } ?>

        <?if ($login_fabrica == 50) {
            echo "<TD class='titulo2'>".traduz("fornecedor",$con,$cook_idioma)."</TD>";
        }?>
        <TD class="titulo2">
        <? echo traduz("qtd",$con,$cook_idioma); ?></TD>

        <?php


        if( ($login_fabrica == 148 || (in_array($login_fabrica, array(156,161,167,177,186,190,191,195,203)) && $desc_tipo_atendimento == "Orçamento") )  OR (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia') )  {

            echo "<td class='titulo2'>";
                echo traduz("preco.unitario",$con,$cook_idioma);
            echo "</td>";
            echo "<td class='titulo2'>";
                echo traduz("preco.total",$con,$cook_idioma);
            echo "</td>";
        }

        ?>

    <? if ($mostrar_valor_pecas) { //28/08/2010 MLG HD 237471  Fábricas que mostra o valor da peça, baseado na tbl_os_item?>
    <TD class='titulo2'><?fecho('preco', $con, $cook_idioma)?></TD>
    <TD class='titulo2'><?fecho('total', $con, $cook_idioma)?></TD>
    <?}?>
        <TD class="titulo2"><?echo traduz("data",$con,$cook_idioma);?></TD>


        <? if(!in_array($login_fabrica,array(114,115,116,117,121,122,123,124,125,126,127,128,131,134,136,138,141,142,144,145)) && !isset($novaTelaOs)){ ?>

            <TD class="titulo2">
            <? if(in_array($login_fabrica, array(20,42))) {
                    echo traduz("preco.bruto",$con,$cook_idioma);
                } else {
                    if($login_fabrica == 87)
                        echo "Causa Falha";
                    else{

                            echo traduz("defeito",$con,$cook_idioma);
                        }

                }?>
            </TD>
        <? }

        if(in_array($login_fabrica, array(151,169,170))) {
            echo "<td class='titulo2'>".traduz("defeito",$con,$cook_idioma)."</td>";
        }

        if ($login_fabrica == 183){
            echo "<td class='titulo2'>".traduz("defeito",$con,$cook_idioma)."</td>";

            echo "<td class='titulo2'>".traduz("código utilização",$con,$cook_idioma)."</td>";
        }

        if($login_fabrica == 87 AND 1 == 2){?>
            <TD class="titulo2">Item Causador</TD>
        <? } ?>
        <?php if ($login_fabrica == 157) {?>
            <TD class="titulo2"><?php echo traduz("motivo",$con,$cook_idioma);?></TD>
        <?php }?>
        <TD class="titulo2">
            <? if (in_array($login_fabrica, array(20,42))) {
                echo traduz("preco.liquido",$con,$cook_idioma);
            } else if($login_fabrica == 96){
                echo "Free of charge";
            } else {
                echo traduz("servico",$con,$cook_idioma);
            }?>
        </TD>

        <?php if($login_fabrica == 87){?>
            <TD class="titulo2">SOAF</TD>
        <?php }?>

        <?php if (in_array($login_fabrica, array(169,170))){ ?>
            <TD class="titulo2"><?echo traduz("previsao.de.entrega",$con,$cook_idioma);?></TD>
        <?php } ?>
        <?php
        if (in_array($login_fabrica, [148])) { 
            echo "<td class='titulo2'>".traduz("Nota Fiscal Estoque")."</td>";
        }
        ?>
        <TD class="titulo2"><?echo traduz("pedido",$con,$cook_idioma);?></TD>

        <?//chamado 141 - exibir nf do fabricante para distrib apenas britania?>
        <?if ($login_fabrica == 3) { /* ALTERADO TODA A ROTINA DE NF - HD 8973 */?>
            <TD class="titulo2" colspan='2' nowrap><?echo traduz("n.f.fabricante",$con,$cook_idioma);?></TD>
        <?}?>

        <TD class="titulo2">
            <?php
                if($login_fabrica == 87){
                    echo "NF";
            }else{
                    echo traduz("nota.fiscal",$con,$cook_idioma);
            }?>
        </TD>
        <? if ($login_fabrica == 3) { ?>
            <td class="titulo2">
                <?= traduz("anexos") ?>
            </td>
        <? } ?>

        <?php if ($login_fabrica == 164) { ?>
            <TD class="titulo2"><?echo traduz("data.de.recebimento",$con,$cook_idioma);?></TD>
        <?php } ?>

        <?if ($login_fabrica <> 3) {?>
            <TD class="titulo2"><?echo traduz("emissao",$con,$cook_idioma);?></TD>
        <?}
        if ($login_fabrica == 1 AND $reembolso_peca_estoque == 't' and empty($tipo_atendimento)) {
            echo "<td class='titulo2'>".traduz("estoque")."</td>";
            echo "<td class='titulo2'>".traduz("previsao")."</td>";
        }

        if ($login_fabrica == 156) {?>
            <TD class="titulo2"><?echo traduz("void",$con,$cook_idioma);?></TD>
        <?}

        if($login_fabrica == 125) {?>
            <TD class="titulo2"><span data-title="Lembrando que este prazo oscila conforme o fluxo de entregas e em função de fins de semana e feriados." class="previsao_entrega"><?echo traduz("previsão de entrega",$con,$cook_idioma);?><strong style="font-size:13px;">*</strong></span></TD>
        <?}

        //Gustavo 12/12/2007 HD 9095
        if (in_array($login_fabrica,array(3,11,35,45,74,80,86,157,160,172)) or $telecontrol_distrib) {?>
            <TD class="titulo2"><?echo traduz("conhecimento",$con,$cook_idioma);echo "</TD>";
        }

        if (in_array($login_fabrica, array(147,151,162,175,186))) {
        ?>
            <TD class="titulo2"><?= traduz("codigo.de.rastreio") ?></TD>
        <?php
        }
        if (in_array($login_fabrica, array(151))) {
                    echo '<TD class="titulo2">'.traduz("data.de.saida").'</TD>';
        }

        if (in_array($login_fabrica,array(35,169,170))) {
            echo '<TD class="titulo2">'.traduz("data.entrega",$con,$cook_idioma).'</TD>';
            if($login_fabrica == 35){
            	echo '<TD class="titulo2">'.traduz("po-peça",$con,$cook_idioma).'</TD>';
            }
        }

        //linha de informatica da Britania

        $specialChar = array("(",")",".");
        $os = str_replace($specialChar, "", $os);

        $sqllinha = "SELECT tbl_linha.informatica
                    FROM    tbl_os
                    JOIN    tbl_produto USING (produto)
                    JOIN    tbl_linha USING (linha)
                    WHERE   tbl_os.fabrica = $login_fabrica
                    AND     tbl_linha.informatica = 't'
                    AND     tbl_os.os = $os";
        $reslinha = pg_query($con,$sqllinha);

        if (pg_num_rows($reslinha) > 0) {
            $linhainf = trim(pg_fetch_result($reslinha,0,informatica)); //linha informatica para britania
        }
        if ($linhainf == 't' AND $login_fabrica != 127 AND  $login_fabrica !=134 AND  $login_fabrica !=136 and $login_fabrica != 162) {
            echo "<TD class='titulo2'>".traduz("serie.peca",$con,$cook_idioma)."</TD>";
            echo "<TD class='titulo2'>".traduz("serie.peca.trocada",$con,$cook_idioma)."</TD>";
        }

        if(in_array($login_fabrica, [167, 203])){
            echo "<TD class='titulo2'>".traduz("serie.peca",$con,$cook_idioma)."</TD>";
        }
        ?>
    </TR>
    <?
    # Exibe legenda de Peças de Retorno Obrigatório para a Gama
    $exibe_legenda = 0;
    $manual_ja_imprimiu = 0;


    if( (in_array($login_fabrica, array(156,161,167,177,186,190,191,195,203)) && $desc_tipo_atendimento == "Orçamento") OR (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia') ){
        $valor_total_pecas = 0;
    }
    if(in_array($login_fabrica, [167,177,186,190,191,195,203])){

        $dados_orcamento_email = array();
    }

    if(in_array($login_fabrica, array(101))){

        $sql_os_troca = "SELECT os_troca FROM tbl_os_troca WHERE os = {$os} AND fabric = {$login_fabrica}";
        $res_os_troca = pg_query($con, $sql_os_troca);

        $os_trocada = false;

        if(pg_num_rows($res_os_troca) > 0){

            $os_trocada = true;

        }

    }

    for ($i = 0 ; $i < $total ; $i++) {
        $pedido                  = trim(pg_fetch_result($res,$i,pedido));
        $pedido_item             = trim(pg_fetch_result($res,$i,pedido_item));
        $pedido_blackedecker     = trim(pg_fetch_result($res,$i,pedido_blackedecker));
        $parametros_adicionais_pecas   = json_decode(pg_fetch_result($res, $i, parametros_adicionais_pecas), true);

        $data_previsao_chegada = pg_fetch_result($res, $i, 'data_previsao_chegada');

	    if (in_array($login_fabrica, [169, 170, 183])) {
            $causa_defeito_desc = pg_fetch_result($res, $i, 'causa_defeito');
        }

        if ($login_fabrica == 1) {
            $para            = trim(pg_fetch_result($res,$i,para));
            $peca_fora_linha = trim(pg_fetch_result($res,$i,peca_fora_linha));
        }

        $usa_po_peca             = trim(pg_fetch_result($res,$i,promocao_site));
        $xobs = trim(pg_fetch_result($res,$i,'obs'));

        if(in_array($login_fabrica, [35, 167,203]) && ($usa_po_peca == "t" || strlen($xobs) > 0)){
            $parametros_adicionais   = trim(pg_fetch_result($res,$i,parametros_adicionais));
            $xparametros_adicionais   = json_decode($parametros_adicionais, true);
            $po_peca = $xparametros_adicionais["po_pecas"];
            $parametrosAdd = $xparametros_adicionais;
        }

        if($login_fabrica == 153){
            $parametros_adicionais   = trim(pg_fetch_result($res,$i,parametros_adicionais));
            $parametros_adicionais   = json_decode($parametros_adicionais, true);
        }

        $parametros_adicionais   = trim(pg_fetch_result($res,$i,parametros_adicionais));
        $seu_pedido              = trim(pg_fetch_result($res,$i,seu_pedido));
        $os_item                 = trim(pg_fetch_result($res,$i,os_item));
        $peca                    = trim(pg_fetch_result($res,$i,peca));
        $peca_causadora          = trim(pg_fetch_result($res,$i,peca_causadora));
        $soaf                    = trim(pg_fetch_result($res,$i,'soaf'));
        $faturamento_item        = trim(pg_fetch_result($res,$i,faturamento_item));
        //chamado 141 - britania - pega nota fiscal do distribuidor
        if ($login_fabrica == 3) {
            $nota_fiscal_distrib = trim(pg_fetch_result($res,$i,nota_fiscal));
            $data_nf_distrib     = trim(pg_fetch_result($res,$i,data_nf));
            $nota_fiscal         = "";
            $data_nf             = "";
            $link_distrib        = 0;
        } else {
            $nota_fiscal         = trim(pg_fetch_result($res,$i,nota_fiscal));
            $data_nf             = trim(pg_fetch_result($res,$i,data_nf));
        }

        $status_pedido_id        = trim(pg_fetch_result($res,$i,"status_pedido_id"));

        if ($login_fabrica == 164) {
            $sqlOS = "SELECT tbl_os_produto.os_produto FROM tbl_os_produto WHERE os = {$os}";
            $resOS = pg_query($con, $sqlOS);

            $osProduto = pg_fetch_result($resOS, 0, os_produto);

            $sqlDataEntrada = "SELECT sr.descricao, oi.servico_realizado, oi.parametros_adicionais::jsonb->>'data_recebimento' as data_recebimento
                    FROM tbl_os_item AS oi
                    INNER JOIN tbl_servico_realizado AS sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = 164
                    WHERE oi.os_produto = {$osProduto} AND oi.fabrica_i = 164 AND oi.peca = {$peca}";
            $resDataEntrada = pg_query($con, $sqlDataEntrada); 
            
            if (pg_num_rows($resDataEntrada) > 0) {
                $os_data_entrada           = pg_fetch_result($resDataEntrada, 0, data_recebimento);    
                $os_servico_realizado      = pg_fetch_result($resDataEntrada, 0, servico_realizado);
                $os_servico_realizado_desc = strtolower(pg_fetch_result($resDataEntrada, 0, descricao));
                $servicoIsAjuste           = ($os_servico_realizado == 11233 || $os_servico_realizado_desc == 'ajuste') ? true : false;
            }
            
            $sqlTdocs_anexo = "SELECT obs AS tipo_anexo FROM tbl_tdocs 
                                WHERE contexto = 'os' AND referencia_id = {$os} AND fabrica = 164 AND situacao = 'ativo'";
            $resTdocs_anexo = pg_query($con, $sqlTdocs_anexo); 
            
            if (pg_num_rows($resTdocs_anexo) > 0) {
                $arr_tipo_anexo = [];

                for ($i_anexo = 0; $i_anexo < pg_num_rows($resTdocs_anexo); $i_anexo++) {
                    $obs = json_decode(pg_fetch_result($resTdocs_anexo, $i_anexo, 'tipo_anexo'), true);    
                    
                    for ($j_anexo = 0; $j_anexo < count($obs); $j_anexo++) {
                        $arr_tipo_anexo[] = $obs[$j_anexo]['typeId'];
                    }
                }
            }
    }

         //conversado com o Analista sobre essa query.

        if(strlen($nota_fiscal)==0 and (in_array($login_fabrica, [122,160]) or $replica_einhell)) {
		if (strlen($pedido_item) > 0){
	            $sql_peca_alternativa = "SELECT peca_alternativa FROM tbl_pedido_item WHERE pedido_item = $pedido_item";
        	    $res_peca_alternativa = pg_query($con, $sql_peca_alternativa);
	            if(pg_num_rows($res_peca_alternativa)>0){
        	        $peca_alternativa = pg_fetch_result($res_peca_alternativa, 0, peca_alternativa);
                    }
		}
        }

        $status_pedido           = trim(pg_fetch_result($res,$i,status_pedido));
        $obs_os_item             = (mb_check_encoding(pg_fetch_result($res,$i,obs), "UTF-8")) ? trim(utf8_decode(pg_fetch_result($res,$i,obs))) : trim(pg_fetch_result($res,$i,obs));
        $distribuidor            = trim(pg_fetch_result($res,$i,distribuidor));
        $digitacao               = trim(pg_fetch_result($res,$i,digitacao_item));
        $preco                   = trim(pg_fetch_result($res,$i,preco));
        $descricao_peca          = trim(pg_fetch_result($res,$i,descricao_peca));
        $preco                   = (!empty($preco)) ? number_format($preco,2,',','.') : "";

        if ($login_fabrica == 175){
            $qtde_disparos_peca = trim(pg_fetch_result($res, $i, "porcentagem_garantia"));
            $componente_raiz = trim(pg_fetch_result($res, $i, "os_por_defeito"));
        }

        $peca_serie              = trim(pg_fetch_result($res,$i,peca_serie));
        $peca_serie_trocada      = trim(pg_fetch_result($res,$i,peca_serie_trocada));
        $servico_realizado_descricao      = trim(pg_fetch_result($res,$i,servico_realizado_descricao));

        if ($login_fabrica == 165) {
            $servico_realizado_estoque = pg_fetch_result($res,$i,servico_realizado_estoque);
//             echo $servico_realizado_estoque."--";
        }


        /*Nova forma de pegar o número do Pedido - SEU PEDIDO  HD 34403 */
        if (strlen($seu_pedido)>0){
            $pedido_blackedecker = fnc_so_numeros($seu_pedido);
        }

        //--=== Tradução para outras linguas ============================= Raphael HD:1212
        $sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";

        $res_idioma = @pg_query($con,$sql_idioma);
        if (@pg_num_rows($res_idioma) >0) {
            $descricao_peca  = trim(@pg_fetch_result($res_idioma,0,descricao));
        }
        //--=== Tradução para outras linguas ===================================================================

        /*====--------- INICIO DAS NOTAS FISCAIS ----------===== */
        /* ALTERADO TODA A ROTINA DE NF - HD 8973 */
        /*############ BLACKEDECKER ############*/
        if ($login_fabrica == 1 OR $login_fabrica == 96){
            if (strlen ($nota_fiscal) == 0) {
                if (strlen($pedido) > 0) {
                    $sql  = "SELECT trim(nota_fiscal) As nota_fiscal ,
                            TO_CHAR(data, 'DD/MM/YYYY') AS emissao
                            FROM    tbl_pendencia_bd_novo_nf
                            WHERE   posto        = $login_posto
                            AND     pedido_banco = $pedido
                            AND     peca         = $peca";
                    $resx = pg_query ($con,$sql);
                    // HD22338
                    if (pg_num_rows ($resx) > 0 AND 1==2) {
                        $nf   = trim(pg_fetch_result($resx,0,nota_fiscal));
                        $link = 0;
                        $data_nf = trim(pg_fetch_result($resx,0,emissao));
                    } else {
                        // HD 30781
                        $sql  = "SELECT trim(nota_fiscal_saida) As nota_fiscal_saida ,
                            TO_CHAR(data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida
                            FROM    tbl_os
                            JOIN    tbl_os_produto USING (os)
                            JOIN    tbl_os_item USING (os_produto)
                            JOIN    tbl_peca USING(peca)
                            WHERE   posto        = $login_posto
                            AND     tbl_os_item.pedido= $pedido
                            AND     tbl_os_item.peca         = $peca
                            AND     tbl_peca.produto_acabado IS TRUE ";
                        $resnf = pg_query ($con,$sql);
                        if(pg_num_rows($resnf) >0){
                            $nf   = trim(pg_fetch_result($resnf,0,nota_fiscal_saida));
                            $link = 0;
                            $data_nf = trim(pg_fetch_result($resnf,0,data_nf_saida));
                        } else {
                            $nf      = "Pendente";
                            $data_nf = "";
                            $link    = 1;
                        }
                    }
                } else {
                    $nf = "";
                    $data_nf = "";
                    $link = 0;
                }
            } else {
                $nf = $nota_fiscal;
            }

        /*############ BRITANIA ############*/
        }elseif ($login_fabrica == 3){

            //Nota do fabricante para distribuidor
            //NF para BRITANIA (DISTRIBUIDORES E FABRICANTES chamado 141) =============================

            if (strlen($pedido) > 0) {
                if(strlen($distribuidor) > 0){

                    $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
                            FROM    tbl_faturamento
                            JOIN    tbl_faturamento_item USING (faturamento)
                            WHERE   tbl_faturamento_item.pedido  = $pedido
                            AND     tbl_faturamento_item.peca    = $peca
                            AND     tbl_faturamento.posto = $distribuidor";

                    $resx = pg_query ($con,$sql);
                    if (pg_num_rows ($resx) > 0) {
                        $nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
                        $data_nf = trim(pg_fetch_result($resx,0,emissao));
                        $link    = 0;
                    } else {
                        $nf      = 'Pendente'; #HD 16354
                        $data_nf = '';
                        $link    = 0;
                    }

                    if ($distribuidor == 4311) {
                        $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                        TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                WHERE   tbl_faturamento_item.pedido  = $pedido
                                /*AND     tbl_faturamento_item.peca  = $peca*/
                                AND     tbl_faturamento_item.os_item = $os_item
                                ";
                                //retirado por Samuel 4/12/2007 - Um nf do distrib atendendo 2 os não tem como gravar 2 os_item.
                                // Coloquei AND     tbl_faturamento_item.os_item = $os_item - Fabio - HD 7591

                        if ($login_posto != 4311) {
                        if ($login_e_distribuidor == "t"){
                            $sql .= "AND     tbl_faturamento.posto        = $posto_verificado ";
                        } else {
                            $sql .= "AND     tbl_faturamento.posto        = $login_posto ";
                            }
                        }

                        $sql .= "AND     tbl_faturamento.distribuidor = 4311";

                        $resx = pg_query ($con,$sql);
                        if (pg_num_rows ($resx) > 0) {
                            $nota_fiscal_distrib = trim(pg_fetch_result($resx,0,nota_fiscal));
                            $data_nf_distrib     = trim(pg_fetch_result($resx,0,emissao));
                            $link_distrib        = 1;
                        } else {
                            $nota_fiscal_distrib = "";
                            $data_nf_distrib     = "";
                            $link_distrib        = 0;
                        }
                    }

                    if($distribuidor != 4311) {
                        $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                        TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                WHERE   tbl_faturamento_item.pedido = $pedido
                                AND     tbl_faturamento_item.peca   = $peca
                                AND     tbl_faturamento.posto       <> $distribuidor;";
                        $resx = pg_query ($con,$sql);

                        if (pg_num_rows ($resx) > 0) {
                            $nota_fiscal_distrib = trim(pg_fetch_result($resx,0,nota_fiscal));
                            $data_nf_distrib     = trim(pg_fetch_result($resx,0,emissao));
                            $link_distrib        = 1;
                        } else {
                            $nota_fiscal_distrib = "";
                            $data_nf_distrib     = "";
                            $link_distrib        = 0;
                        }
                    }
                } else {
                    //(tbl_faturamento_item.os = $os) --> HD3709
                    /*HD 72977*/
                        $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
                                tbl_faturamento.conhecimento AS conhecimento
                        FROM    tbl_faturamento
                        JOIN    tbl_faturamento_item USING (faturamento)
                        WHERE   tbl_faturamento_item.pedido = $pedido
                        AND     tbl_faturamento_item.peca   = $peca
                        AND     (tbl_faturamento_item.os ISNULL OR tbl_faturamento_item.os = $os";

                        if($gambiara=='t'){
                            $sql .= "OR tbl_faturamento_item.os_item = $os_item )
                            AND     tbl_faturamento.posto       = $xlogin_posto";
                        } else {
                            $sql  .=  ")
                            AND     tbl_faturamento.posto       = $login_posto";
                        }
                        $resx = pg_query ($con,$sql);

                    if (pg_num_rows ($resx) > 0){
                        $nf                  = trim(pg_fetch_result($resx,0,nota_fiscal));
                        $data_nf             = trim(pg_fetch_result($resx,0,emissao));
                        //se fabrica atende direto posto seta a mesma nota

                        //hd 22576
                        if ($login_posto <> 4311) {
                            $nota_fiscal_distrib = trim(pg_fetch_result($resx,0,nota_fiscal));
                            $data_nf_distrib     = trim(pg_fetch_result($resx,0,emissao));
                            $link = 1;
                            $conhecimento = trim(pg_fetch_result($resx,0,conhecimento));
                        } else {
                            $nota_fiscal_distrib = "";
                            $data_nf_distrib     = "";
                            $link                = 0;
                        }
                    } else {
                        //HD 77790 HD 125880
                        $sqly = "SELECT tbl_faturamento.nota_fiscal                                         ,
                                                    to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao
                                    FROM tbl_faturamento_item
                                    JOIN   tbl_faturamento  ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = $login_fabrica
                                    JOIN   tbl_peca             ON tbl_faturamento_item.peca = tbl_peca.peca
                                    JOIN tbl_os_troca ON tbl_faturamento_item.os = tbl_os_troca.os AND tbl_os_troca.pedido = $pedido
                                    WHERE tbl_faturamento_item.pedido = $pedido
                                    AND     (
                                                    (tbl_faturamento_item.os ISNULL OR tbl_faturamento_item.os IS NULL)  OR tbl_faturamento_item.os = $os";
                        if($gambiara=='t'){
                            $sqly .= "OR tbl_faturamento_item.os_item = $os_item )
                            AND     tbl_faturamento.posto       = $xlogin_posto";
                        } else {
                            $sqly  .=  ")
                            AND     tbl_faturamento.posto       = $login_posto";
                        }
                        $resy = pg_query ($con,$sqly);

                        if (pg_num_rows ($resy) > 0){
                            $nf                  = trim(pg_fetch_result($resy,0,nota_fiscal));
                            $data_nf             = trim(pg_fetch_result($resy,0,emissao));
                            //se fabrica atende direto posto seta a mesma nota

                            //hd 22576
                            if ($login_posto <> 4311) {
                                $nota_fiscal_distrib = trim(pg_fetch_result($resy,0,nota_fiscal));
                                $data_nf_distrib     = trim(pg_fetch_result($resy,0,emissao));
                                $link = 1;
                            } else {
                                $nota_fiscal_distrib = "";
                                $data_nf_distrib     = "";
                                $link                = 0;
                            }
                        } else {
                            $nf                  = "Pendente";
                            $data_nf             = "";
                            $nota_fiscal_distrib = "";
                            $data_nf_distrib     = "";
                            $link                = 0;
                        }
                    }
                }
            } else {
                $nf                  = "";
                $data_nf             = "";
                $nota_fiscal_distrib = "";
                $data_nf_distrib     = "";
                $link = 0;
            }

        /*############ LENOXX ############*/
        }elseif ( in_array($login_fabrica, array(11,172))  and 1==2){
                 # Agora o pedido da peça ta amarrado no faturamento item: Fabio 09/08/2007
                if (strlen($faturamento_item)>0){
                        $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                        TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao ,
										tbl_faturamento.posto,
                                        tbl_faturamento.conhecimento
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                WHERE   tbl_faturamento.fabrica=$login_fabrica
                                AND     tbl_faturamento_item.faturamento_item = $faturamento_item";
                        $resx = pg_query ($con,$sql);
                        #echo nl2br($sql);
                        if (pg_num_rows ($resx) > 0) {
                            $nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
                            $data_nf = trim(pg_fetch_result($resx,0,emissao));
                            $conhecimento = trim(pg_fetch_result($resx,0,conhecimento));
                            $link = 1;
                        } else {
                            $nf ="Pendente";
                            $data_nf="";
                            $conhecimento = "";
                            $link = 0;
                        }
                } else {
                    if (strlen($pedido) > 0) {
                            $nf ="Pendente";
                            $data_nf="";
							$conhecimento = "";
                            $link = 0;
                    } else {
                            $nf ="";
                            $data_nf="";
							$conhecimento = "";
                            $link = 0;
                    }
                }

        /*############ CADENCE ############*/

        }elseif (in_array($login_fabrica,array(11,35,45,74,80,86,147,151,160,162,172)) or $replica_einhell) {
            if (in_array($login_fabrica, array(151))) {
                $whereFaturamento = " AND tbl_faturamento.cancelada IS NULL";
            }
            if (strlen ($nota_fiscal) == 0){
                if (strlen($pedido) > 0 ) {
                    $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao ,
                                    TO_CHAR(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY') AS previsao_chegada,
                                    tbl_faturamento.conhecimento,
                                    tbl_faturamento.faturamento
                            FROM    tbl_faturamento
                            JOIN    tbl_faturamento_item USING (faturamento)
                            WHERE   tbl_faturamento.pedido    = $pedido
                            AND     tbl_faturamento_item.peca = $peca
							AND     (tbl_faturamento_item.pedido_item = $pedido_item or tbl_faturamento_item.pedido_item isnull)
                            {$whereFaturamento};";
                    $resx = pg_query ($con,$sql);

                    if (pg_num_rows ($resx) > 0) {
                        $nf           = trim(pg_fetch_result($resx,0,nota_fiscal));
                        $xdata_entrega  = trim(pg_fetch_result($resx,0,previsao_chegada));
                        $data_nf      = trim(pg_fetch_result($resx,0,emissao));
                        $saida        = trim(pg_fetch_result($resx,0,saida));
                        $conhecimento = trim(pg_fetch_result($resx,0,conhecimento));
                        $faturamento  = trim(pg_fetch_result($resx,0,faturamento));
                        $link = 1;
                    } else {
                        //cadence relaciona pedido_item na os_item
                        $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
                                        TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
                                        TO_CHAR(tbl_faturamento.saida, 'DD/MM/YYYY') AS saida,
                                        TO_CHAR(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY') AS previsao_chegada,
                                        tbl_faturamento.posto,
                                        tbl_faturamento.conhecimento,
                                        tbl_faturamento.faturamento
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                WHERE   tbl_faturamento_item.pedido      = $pedido
								AND		(tbl_faturamento_item.os = $os or tbl_faturamento_item.os isnull)
								AND     (tbl_faturamento_item.pedido_item = $pedido_item or (tbl_faturamento_item.pedido_item isnull and tbl_faturamento_item.peca = $peca))
                                {$whereFaturamento};";
                        $resx = pg_query ($con,$sql);

                        if((in_array($login_fabrica, [160]) or $replica_einhell) and pg_num_rows($resx) == 0 ){

                             $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
                                        TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
                                        TO_CHAR(tbl_faturamento.saida, 'DD/MM/YYYY') AS saida,
                                        tbl_faturamento.posto,
                                        tbl_faturamento.conhecimento,
                                        tbl_faturamento.faturamento,
                                        tbl_pedido_item.obs
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                join    tbl_os_item on tbl_os_item.os_item =  tbl_faturamento_item.os_item
                                join    tbl_pedido_item  on tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                                WHERE   tbl_faturamento_item.pedido      = $pedido
								AND		(tbl_faturamento_item.os = $os or tbl_faturamento_item.os isnull)
                                AND     tbl_faturamento_item.peca        = $peca_alternativa";
                            $resx = pg_query ($con,$sql);
                        }

                        if((in_array($login_fabrica, [160]) or $replica_einhell) && pg_num_rows($resx) == 0 ) {
                            $sql_obs = "SELECT tbl_os_item.obs 
                                        FROM tbl_os_produto 
                                        JOIN tbl_os_item using(os_produto) 
                                        WHERE tbl_os_produto.os = $os
                                        AND tbl_os_item.fabrica_i = $login_fabrica
                                        AND tbl_os_item.peca = $peca";
                            $res_obs = pg_query($sql_obs);
                        }

                        if (pg_num_rows ($resx) > 0) {
                            $nf           = trim(pg_fetch_result($resx,0,nota_fiscal));
                            $xdata_entrega   = trim(pg_fetch_result($resx,0,previsao_chegada));
                            $data_nf      = trim(pg_fetch_result($resx,0,emissao));
                            $saida        = trim(pg_fetch_result($resx,0,saida));
                            $conhecimento = trim(pg_fetch_result($resx,0,conhecimento));
                            $faturamento  = trim(pg_fetch_result($resx,0,faturamento));

                            if(in_array($login_fabrica, [160]) or $replica_einhell){
                                $obs_alternativa = pg_fetch_result($resx, 0, obs);
                            }

                            $link         = 1;
                        } else {
                            $nf      = "Pendente";

                            if (in_array($login_fabrica, array(11, 172))) {
                                $aux_sql = "SELECT status_pedido FROM tbl_pedido WHERE pedido = $pedido LIMIT 1";
                                $aux_res = pg_query($con, $aux_sql);
                                $aux_stp = pg_fetch_result($aux_res, 0, 'status_pedido');

                                if ($aux_stp == "14") {
                                    $nf = "Cancelada";
                                }
                            }

                            $data_nf = "";
                            $xdata_entrega = "";
                            $saida = "";
                            $conhecimento = "";
                            $link    = 1;
                            if ($login_fabrica == 160 or $replica_einhell) {
                                $obs_alternativa = pg_fetch_result($res_obs, 0, obs);
                            }
                        }
                    }
                } else {
                    $nf = "";
                    $data_nf = "";
                    $link = 0;
                }
            } else {
                $nf = $nota_fiscal;
            }
        /*############ DEMAIS FABRICANTES ############*/
        } else {
            if ($login_fabrica == 122) {
                $obs_alternativa = "";
                $sql  = "SELECT tbl_pedido_item.obs
                                FROM    tbl_os_produto
                                JOIN    tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                JOIN    tbl_pedido_item  on tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                                WHERE   tbl_pedido_item.pedido      = $pedido
                                AND     tbl_os_produto.os = $os
                                AND     tbl_os_item.fabrica_i = $login_fabrica
                                AND     tbl_pedido_item.peca_alternativa  = $peca_alternativa";
                $resObs = pg_query ($con,$sql);
                if (pg_num_rows($resObs) > 0) {
                    $obs_alternativa = pg_fetch_result($resObs, 0, 'obs');
                }
            }

            if (strlen ($nota_fiscal) == 0){
                if (strlen($pedido) > 0) {
		    $cond_pedido_item = (empty($pedido_item)) ? " 1=2 " : " tbl_faturamento_item.pedido_item = $pedido_item ";
                    if ($login_fabrica == 91 && empty($pedido_item)) {
                        $cond_pedido_item = "tbl_faturamento_item.pedido_item is not null";
                    }
                    if($login_fabrica == 106){
                        $join_os_item = "
			    JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_faturamento_item.pedido_item
			    JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_produto.os = $os
			";
                    }

		    $leftAlternativa = "";
                    $condPeca = "";
		    $distinct = "";
                    if (in_array($login_fabrica, array(169,170))) {
			$distinct = "DISTINCT";
                        $leftAlternativa = "
			    LEFT JOIN tbl_peca_alternativa pa_de ON pa_de.peca_de = {$peca} AND pa_de.fabrica = {$login_fabrica} 
			    LEFT JOIN tbl_peca_alternativa pa_para ON pa_para.peca_para = {$peca} AND pa_para.fabrica = {$login_fabrica} 
			";
                        $condPeca = "AND (tbl_faturamento_item.peca = {$peca} OR tbl_faturamento_item.peca = pa_de.peca_para OR tbl_faturamento_item.peca = pa_para.peca_de OR tbl_faturamento_item.peca IN (SELECT DISTINCT peca_para FROM tbl_peca_alternativa WHERE fabrica = {$login_fabrica} AND peca_de = pa_para.peca_de OR peca_de = pa_de.peca_para))";
                    } else {
                        $condPeca = "AND (tbl_faturamento_item.peca = {$peca} OR tbl_faturamento_item.peca_pedida = {$peca} )";
                    }

                    $sql  = "SELECT {$distinct}
				    trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
                                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
				    TO_CHAR(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY') AS previsao_chegada,
									TRIM(tbl_faturamento.conhecimento) AS conhecimento,
									tbl_faturamento.faturamento,
                                    tbl_faturamento.transp
                            FROM    tbl_faturamento
                            JOIN    tbl_faturamento_item USING (faturamento)
                            $join_os_item
			    {$leftAlternativa}
                            WHERE   tbl_faturamento_item.pedido    = $pedido
							AND     ($cond_pedido_item or tbl_faturamento_item.pedido_item isnull)
                            {$condPeca}";
                    if(in_array($login_fabrica, array(51, 81, 114,101)) or !empty($telecontrol_distrib)) $sql.=" AND   ( tbl_faturamento_item.os_item = $os_item or tbl_faturamento_item.os_item isnull) ";
                    if(in_array($login_fabrica,array(88,99,117))) $sql.="AND     (tbl_faturamento_item.os = $os or tbl_faturamento_item.os isnull)";
                    if ($login_fabrica == 2) {
                        $sql = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal     ,
                                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
                            FROM (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
                            JOIN tbl_pedido_item_faturamento_item on tbl_pedido_item.pedido_item = tbl_pedido_item_faturamento_item.pedido_item
                            JOIN tbl_faturamento_item ON tbl_pedido_item_faturamento_item.faturamento_item= tbl_faturamento_item.faturamento_item
                            JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                            AND tbl_faturamento.fabrica = $login_fabrica
                            WHERE    tbl_faturamento_item.peca = $peca";
                    }

                    $resx = pg_query ($con,$sql);

                    if (pg_num_rows ($resx) > 0) {
                        $nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
                        $data_nf = trim(pg_fetch_result($resx,0,emissao));
                        $faturamento = trim(pg_fetch_result($resx,0,faturamento));
                        $conhecimento = trim(pg_fetch_result($resx,0,conhecimento));
                        $transp = trim(pg_fetch_result($resx,0,transp));
			$xdata_entrega = pg_fetch_result($resx,0,previsao_chegada);
                        $link = 1;
                    } else {
                        $condicao_01 = "";
                        if (strlen ($distribuidor) > 0) {
                            $condicao_01 = " AND tbl_faturamento.distribuidor = $distribuidor ";
                        }
                        $sql  = "SELECT
                                    trim(tbl_faturamento.nota_fiscal)                AS nota_fiscal ,
                                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')   AS emissao,
                                    tbl_faturamento.posto                            AS posto
                                FROM    tbl_faturamento
                                JOIN    tbl_faturamento_item USING (faturamento)
                                $join_os_item
                                WHERE   tbl_faturamento_item.pedido = $pedido
                                AND     (tbl_faturamento_item.peca   = $peca OR tbl_faturamento_item.peca_pedida = $peca)
								AND     ($cond_pedido_item or tbl_faturamento_item.pedido_item isnull)
                                $condicao_01 ";
                        if(in_array($login_fabrica, array(51,81,114,101)) or !empty($telecontrol_distrib) ) $sql.=" AND     tbl_faturamento_item.os_item = $os_item ";
                        if(in_array($login_fabrica,array(88,99,117))) $sql.="AND     (tbl_faturamento_item.os = $os or tbl_faturamento_item.os isnull)";

						if (isset($novaTelaOs)) {
							$sql  = "SELECT
												trim(tbl_faturamento.nota_fiscal)                AS nota_fiscal ,
												TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')   AS emissao,
												tbl_faturamento.posto                            AS posto,
                                                tbl_faturamento.transp
											FROM    tbl_faturamento
											JOIN    tbl_faturamento_item USING (faturamento)
											INNER JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_faturamento_item.pedido_item
											INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
											WHERE   tbl_faturamento_item.pedido = $pedido
											AND tbl_faturamento.cancelada IS NULL
											AND     (tbl_faturamento_item.peca = $peca OR tbl_faturamento_item.peca_pedida = $peca)
											and $cond_pedido_item
											AND     tbl_os_produto.os = {$os}
											$condicao_01 ";
						}
                        $resx = pg_query ($con,$sql);

                        if (pg_num_rows ($resx) > 0) {
                            $nf           = trim(pg_fetch_result($resx,0,nota_fiscal));
                            $data_nf      = trim(pg_fetch_result($resx,0,emissao));
                            $link         = 1;
                        } else {

			    if (!empty($pedido)) {
                                $wherePedido = "AND tbl_faturamento_item.pedido = {$pedido}";
                            }

                            //Faturamento manual do distrib
                            $sqlm = "SELECT tbl_faturamento.nota_fiscal              AS nota_fiscal ,
											TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')   AS emissao,
											tbl_faturamento.faturamento,
											conhecimento
                                    FROM    tbl_faturamento
                                    JOIN    tbl_faturamento_item USING (faturamento)
                                    WHERE   tbl_faturamento.fabrica = $login_fabrica
                                    AND     tbl_faturamento_item.os = $os
				    {$wherePedido}
                                    AND     tbl_faturamento_item.peca = $peca ";
							if(in_array($login_fabrica, array(51,81,114,101)) or !empty($telecontrol_distrib) ) $sqlm .=" AND     tbl_faturamento_item.os_item = $os_item ";
                            $resm = pg_query ($con,$sqlm);
                            if (pg_num_rows ($resm) > 0) {
                                $nf           = pg_fetch_result($resm,0,nota_fiscal);
				                $data_nf      = trim(pg_fetch_result($resm,0,emissao));
				                $faturamento  = trim(pg_fetch_result($resm,0,faturamento));
                                $conhecimento = trim(pg_fetch_result($resm,0,conhecimento));

                                $link    = 1;
                                $manual_ja_imprimiu = 1;
                                if($nf == '000000'){
                                    $data_nf = "--";
                                }
                            }else{

                                $nf      = "Pendente";

                                if($login_fabrica == 123 AND $login_posto == 20682 AND $consumidor_revenda == "REVENDA" AND strlen($nota_fiscal_saida) > 0){
                                    $nf = $nota_fiscal_saida;
                                }

                                $data_nf = "";
                                $link    = 1;
                                if($login_fabrica==6 and strlen($data_finalizada)>0){ //hd 3437
                                    $nf = "Atendido";
                                } else if ($status_pedido_id == 14) {
                                    $nf = "Cancelada";
                                    $data_nf = "";
                                }

                            }
                        }
                    }
                } else {
                    $nf = "";
                    $data_nf = "";
                    $link = 0;
                }
            } else {
                $nf = $nota_fiscal;
            }
        }

        //HD 18479
        if($login_fabrica==3){
            if((strlen($pedido)>0 AND strlen($peca)>0) AND $nf=="Pendente"){
                $sql = "SELECT motivo
                        FROM   tbl_pedido_cancelado
                        WHERE  pedido = $pedido
                        AND    peca   = $peca
                        AND    posto  = $login_posto;";
                $resx = pg_query ($con,$sql);
                if (pg_num_rows ($resx) > 0) {
                    $motivo = pg_fetch_result($resx,0,motivo);
                    $nf           = "<a href='#' title='$motivo'>".traduz("cancelada",$con,$cook_idioma)."</a>";
                    $data_nf      = "-";
                    $link         = 1;
                }
            }
            //HD 20787
            if(strlen(trim($nota_fiscal_distrib))==0 AND $nf<>'Pendente' and strlen($pedido) > 0){
                $sql = "SELECT motivo
                        FROM   tbl_pedido_cancelado
                        WHERE  pedido = $pedido
                        AND    peca   = $peca
                        AND    posto  = $login_posto;";
                $resx = pg_query ($con,$sql);
                if (pg_num_rows ($resx) > 0) {
                    $motivo = pg_fetch_result($resx,0,motivo);
                    $nota_fiscal_distrib = "<a href='#' title='$motivo'>".traduz("cancelada",$con,$cook_idioma)."</a>";
                }
            }
        }

        if(in_array($login_fabrica,array(52,88,99,101,127)) AND !empty($pedido) and strlen($pedido_item)>0 ){

            $campo_os = ($login_fabrica != 52) ? " AND os = $os " : "";
            $sql = "SELECT tbl_pedido_cancelado.peca
		    FROM   tbl_pedido_cancelado
			JOIN tbl_pedido_item USING(pedido_item)
                        WHERE  tbl_pedido_cancelado.pedido = $pedido
                        AND    tbl_pedido_cancelado.peca   = $peca
                        AND    tbl_pedido_cancelado.posto  = $login_posto
			AND (
				(tbl_pedido_cancelado.pedido_item = $pedido_item AND (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) = 0 )
				OR pedido_item isnull
			)
			{$campo_os}";
            $resx = pg_query ($con,$sql);
            if (pg_num_rows ($resx) > 0) {
                $nf = "Cancelada";
                $data_nf = "";
                $link = 0;
            }
		}
        /*====--------- FIM DAS NOTAS FISCAIS ----------===== */

        // $status_os -> variavel pegada lá em cima
        $msg_peca_intervencao="";

        $bloqueada_pc           = pg_fetch_result($res,$i,bloqueada_pc);
        $peca_critica           = pg_fetch_result($res,$i,peca_critica);
        $servico_realizado_peca = pg_fetch_result($res,$i,servico_realizado_peca);
        $retorna_conserto       = pg_fetch_result($res,$i,retorna_conserto);


        $devolucao_obrigatoria  = pg_fetch_result($res,$i,devolucao_obrigatoria);

        if (( in_array($login_fabrica, array(1,3,6,11,172)) ) AND ( $bloqueada_pc=='t' OR $retorna_conserto=='t' OR $peca_critica=='t')){

            if ( in_array($login_fabrica, array(11,172)) ) {
                $id_servico_realizado           = 61;
                $id_servico_realizado_ajuste    = 498;
            }
            if ($login_fabrica==6) {
                $id_servico_realizado           = 1;
                $id_servico_realizado_ajuste    = 35;
            }
            if ($login_fabrica==3) {
                $id_servico_realizado           = 20;
                $id_servico_realizado_ajuste    = 96;
            }
            if ($login_fabrica==1) {
                $id_servico_realizado           = 62;
                $id_servico_realizado_ajuste    = 64;
            }

            $cor_intervencao = "#FF6666";

            if ($login_fabrica==1 AND $status_os=='87' AND $peca_critica=='t'){
                $cor_intervencao = "#FFFFFF";
            }

            if (($status_os=='62' OR $status_os=='87' OR $status_os=='72' OR $status_os=='116') AND $servico_realizado_peca==$id_servico_realizado){
                $msg_peca_intervencao=" <b style='font-weight:normal;color:$cor_intervencao;font-size:10px'>(".traduz("aguardando.autorizacao.da.fabrica",$con,$cook_idioma).")</b>";
            }

            if (($status_os=='64' OR $status_os=='73' OR $status_os=='88' OR $status_os=='117') AND $servico_realizado_peca==$id_servico_realizado){
                $msg_peca_intervencao=" <b style='font-weight:normal;color:#333333;font-size:10px'>(".traduz("autorizado.pela.fabrica",$con,$cook_idioma).")</b>";
            }

            if (($status_os=='64' OR $status_os=='73' OR $status_os=='88' OR $status_os=='117') AND $servico_realizado_peca==$id_servico_realizado_ajuste){
                $msg_peca_intervencao=" <b style='font-weight:normal;color:#CC0000;font-size:10px'>(".traduz("pedido.cancelado.pela.fabrica",$con,$cook_idioma);
				if ($login_fabrica == 3) {
					$msg_peca_intervencao .= " - $data_status";
				}
				$msg_peca_intervencao .= ")</b>";
                $cancelou_peca = "sim";
            }

            if (($status_os=='62' OR $status_os=='73' OR $status_os=='87' OR $status_os=='116') AND strlen($pedido) > 0 AND $servico_realizado_peca==$id_servico_realizado) {
                $msg_peca_intervencao=" <b style='font-weight:normal;color:#333333;font-size:10px'>(".traduz("autorizado.pela.fabrica",$con,$cook_idioma).")</b>";
            }
        }

        $cor_linha_peca = "";
        if ($login_fabrica==1 AND $status_os=='87' AND $peca_critica=='t'){
            $cor_linha_peca = " ;background-color:#FF2D2D";
        }

        ?>

        <TR class="conteudo"
        <?php

            if ($login_fabrica == 35 && isset($parametrosAdd["pecaReenviada"]) && $parametrosAdd["pecaReenviada"]) {
                $bg_cadence = "background-color:#fbe2e2 !important;";
                echo "style='{$bg_cadence}'";
            }


            if ($devolucao_obrigatoria == "t" and in_array($login_fabrica, array(50, 51, 81, 114))) {
                $exibe_legenda++;
                echo " style='background-color:#FFC0D0'";
            }

            if($parametros_adicionais['recall'] == 1){
                echo " style='background-color:#98FB98'";
                $recall = true;
            }

        ?>
        >
        <?php
            if($login_fabrica == 138){
                $model = ModelHolder::init('OsItem');
                $osItem = pg_fetch_result($res,$i,'os_item');
                $osItem = $model->select($osItem);
                $content = '';
                if($osItem['servicoRealizado'] != 11120){
                    $sql = 'SELECT tbl_produto.referencia
                            FROM tbl_produto
                            INNER JOIN tbl_os_produto
                                ON (tbl_produto.produto = tbl_os_produto.produto)
                            INNER JOIN tbl_os_item
                                ON (tbl_os_item.os_produto = tbl_os_produto.os_produto)
                            WHERE tbl_os_item.os_item = :osItem';
                    $params = array(':osItem'=>$osItem['osItem']);
                    $result = $model->executeSql($sql,$params);
                    $content = $result[0]['referencia'];
                }
                $td = HtmlHelper::inlineBuild('td',array('style'=>'text-align:left;padding:5px;'),$content);
                $td->render();
            }
        ?>
        <?
        if($os_item_subconjunto == 't') {
            echo "<TD style=\"text-align:left;\">".pg_fetch_result($res,$i,subproduto_referencia) . " - " . pg_fetch_result($res,$i,subproduto_descricao)."</TD>";
            echo "<TD style=\"text-align:center;\">".pg_fetch_result($res,$i,posicao)."</TD>";
        }
        ?>
        <TD
        <?php
            if ($login_fabrica == 51){
                echo " nowrap ";
        }?>
        style="text-align:left;<?=$cor_linha_peca?>">
        <?PHP
        if ($login_fabrica == 171) {
            echo trim(pg_fetch_result($res,$i,'peca_referencia_fabrica')). " / ";
        }
        ?>

        <?
        echo pg_fetch_result($res,$i,referencia_peca) . " - " . $descricao_peca; echo $msg_peca_intervencao?></TD>

        <?php if ($login_fabrica == 177){ ?>
            <TD style="text-align:center;<?=$cor_linha_peca?>"><?=$peca_serie_trocada?></TD>
            <TD style="text-align:center;<?=$cor_linha_peca?>"><?=$peca_serie?></TD>
        <?php } ?>
        <?php if ($login_fabrica == 175){ ?>
            <TD style="text-align:center;<?=$cor_linha_peca?>"><?=$peca_serie?></TD>
            <TD style="text-align:center;<?=$cor_linha_peca?>"><?=$qtde_disparos_peca?></TD>
            <TD style="text-align:center;<?=$cor_linha_peca?>"><?=(($componente_raiz == "t")? "SIM":"")?></TD>
        <?php } ?>

        <?if ($login_fabrica == 50) {
             $nome_fornecedor = '';
            $sql_f = "SELECT nome_fornecedor FROM tbl_ns_fornecedor WHERE numero_serie = (SELECT  numero_serie FROM tbl_numero_serie WHERE serie = '$serie') and peca = $peca ";
            $res_f = pg_query($con,$sql_f);
            //echo $sql_f;

            if (pg_num_rows($res_f)>0) {
                $nome_fornecedor = pg_fetch_result($res_f, 0, 'nome_fornecedor');

            }
            ?>
             <TD style="text-align:center;<?=$cor_linha_peca?>"><? echo $nome_fornecedor ?></TD>
            <?
        }?>
        <TD style="text-align:center;<?=$cor_linha_peca?>"><? echo pg_fetch_result($res,$i,qtde) ?></TD>

        <?php

        if($login_fabrica == 148 || (in_array($login_fabrica, array(156,161,167,177,186,190,191,195,203)) && $desc_tipo_atendimento == "Orçamento") OR (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia')) {

            if(in_array($login_fabrica,[167,177,186,190,191,195,203])) {
                $qtde_pecas     = (strlen(pg_fetch_result($res, $i, "qtde")) > 0) ? pg_fetch_result($res, $i, "qtde") : 0;
                $preco_total     = (strlen(pg_fetch_result($res, $i, "preco")) > 0) ? pg_fetch_result($res, $i, "preco") : 0;
                $preco_unitario = number_format($preco_total , 2,",",".");
                $preco_total_aux = $preco_total*$qtde_pecas;
                $valor_total_pecas += $preco_total_aux;

                $dados_orcamento_email[$i]["descricao_peca"]                = utf8_encode(pg_fetch_result($res,$i,'referencia_peca'))." - ".utf8_encode($descricao_peca);
                $dados_orcamento_email[$i]["qtde_pecas"]                    = $qtde_pecas;
                $dados_orcamento_email[$i]["preco_total"]                   = $preco_total;
                $dados_orcamento_email[$i]["preco_unitario"]                = $preco_unitario;
                $dados_orcamento_email[$i]["preco_total_aux"]               = $preco_total_aux;
                $dados_orcamento_email[$i]["servico_realizado_descricao"]   = utf8_encode($servico_realizado_descricao);
            }else{
                $preco_unitario = (strlen(pg_fetch_result($res, $i, "preco")) > 0) ? pg_fetch_result($res, $i, "preco") : 0;
                $qtde_pecas     = (strlen(pg_fetch_result($res, $i, "qtde")) > 0) ? pg_fetch_result($res, $i, "qtde") : 0;
                $preco_total    = (strlen(pg_fetch_result($res, $i, "custo_peca")) > 0) ? pg_fetch_result($res, $i, "custo_peca") : 0;
                $ipi            = (strlen(pg_fetch_result($res, $i, "ipi")) > 0) ? pg_fetch_result($res, $i, "ipi") : 0;

                if($login_fabrica == 161){
                    $preco_total = $preco_total + ($preco_total*($ipi/100));
                }


                $preco_unitario = number_format($preco_unitario , 2,",",".");
                $preco_total_aux = $preco_total;
                //$preco_total_aux = number_format($preco_total*$qtde_pecas, 2,",",".");


                if ($login_fabrica == 163) {
                    $preco_total    = (strlen(pg_fetch_result($res, $i, "custo_peca")) > 0) ? pg_fetch_result($res, $i, "custo_peca") : 0;
                    $preco_unitario = (strlen(pg_fetch_result($res, $i, "preco")) > 0) ? pg_fetch_result($res, $i, "preco") : 0;

                    $preco_total_aux =$preco_unitario*$qtde_pecas;
                    $valor_total_pecas += $preco_total_aux;

                    $preco_unitario = number_format($preco_unitario , 2,",",".");
                    $preco_total_aux = number_format($preco_total_aux , 2,",",".");
                    //$valor_total_pecas = number_format($valor_total_pecas , 2,",",".");
                } else {
                    $valor_total_pecas += $preco_total_aux;
                }
            }

            if ($login_fabrica == 148) {
                $preco_total_aux = $preco_unitario * $qtde_pecas;
            }

            echo "<td style='text-align: center;'>";
                echo $preco_unitario;
            echo "</td>";
            echo "<td style='text-align: center;'>";
                echo $preco_total_aux = number_format($preco_total_aux , 2,",",".");
            echo "</td>";
        }

        ?>

        <?
        if ($mostrar_valor_pecas/* and ($nf != 'Cancelada' and $nf != ''*/) { //28/08/2010 MLG HD 237471  Fábricas que mostra o valor da peça, baseado na tbl_os_item?>
        <TD style='text-align:right;'><?=number_format(pg_fetch_result($res,$i,preco_peca),2,",",".")?></TD>
        <TD style='text-align:right;'><?=number_format(pg_fetch_result($res,$i,total_peca),2,",",".")?></TD>
        <?}
        if($login_fabrica==20){

            $specialChar = array("(",")",".");
            $os = str_replace($specialChar, "", $os);

            $sql = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca AND tabela = (select tbl_posto_fabrica.tabela from tbl_posto_fabrica JOIN tbl_os USING (posto) WHERE tbl_os.os = $os AND tbl_posto_fabrica.fabrica = $login_fabrica)";
            $res2 = pg_query ($con,$sql);
            $preco_bruto = number_format (@pg_fetch_result($res2,0,preco),2,",",".");
        }

        if($login_fabrica==42){ // HD 341053

            $produto_referencia = pg_fetch_result($res,$i,referencia_peca);
            // se nao tiver extrato, zerar valores
            $sql_ex = "select extrato from tbl_os_extra where os = $os AND extrato is not null;";
            $res_ex = pg_query($con,$sql_ex);
            if(pg_num_rows($res_ex) == 0) {
                $preco_bruto = 0.00;
                $preco       = 0.00;
            }
            else {

                $sql_preco = "SELECT tbl_os_item.custo_peca,
                                     tbl_os_item.preco
                              FROM tbl_os_item
                              JOIN tbl_os_produto USING (os_produto)
                              JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
                              AND tbl_peca.referencia = '$produto_referencia'
                              AND tbl_peca.fabrica = $login_fabrica
                              WHERE os = $os ";
                //echo nl2br($sql_preco);
                $res_preco = pg_query($con,$sql_preco);
                if(pg_num_rows($res_preco)>0)
                    $makita_preco_bruto = number_format (pg_result($res_preco,0,preco),2,".",".");
                    $preco = number_format (pg_result($res_preco,0,custo_peca),2,".",".");
                /*
                $sql_ipi = "SELECT ipi from tbl_peca where peca = $peca";
                $res_ipi = pg_exec($con,$sql_ipi);
                if(pg_num_rows($res_ipi)>0) {

                    $ipi            = pg_result($res_ipi,0,0);
                    $preco_liq_val  = $makita_preco_bruto * ($ipi/100);
                    $preco_liq      = $preco_liq_val + $makita_preco_bruto;
                    $preco          = $preco_liq * 1.2;
                    $preco          = number_format($preco,2,'.','');

                }
                */
                $preco_bruto = $makita_preco_bruto ;
                unset($makita_preco);

            }

        } // fim hd 341053
        ?>
        <TD style="text-align:center;<?=$cor_linha_peca?>"><?= pg_fetch_result($res,$i,digitacao_item) ?></TD>

        <? if(!in_array($login_fabrica,array(114,115,116,117,121,122,123,124,125,126,127,128,131,134,136,141,142,144,145)) && !isset($novaTelaOs)){ ?>

        <TD
        <?php
            if ($login_fabrica == 51){
                echo " nowrap ";
        }?>
        style="<?=$cor_linha_peca?>"><?= (in_array($login_fabrica, array(20,42))) ? $preco_bruto : pg_fetch_result($res,$i,defeito); ?></TD>
        <? } ?>
        <?php if ($login_fabrica == 157) {//motivo?>
            <td><?php echo pg_fetch_result($res,$i,defeito);?></td>
        <?php }?>
        <?php

        if(in_array($login_fabrica, array(151,169,170,183))) {

            $sql_defeito_constatado = "SELECT tbl_defeito.descricao
                                       FROM tbl_os_item
                                       INNER JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_item.defeito
                                       WHERE tbl_os_item.os_item = {$os_item}";
            $res_defeito_constatado = pg_query($con, $sql_defeito_constatado);

            $defeito_constatado = pg_fetch_result($res_defeito_constatado, 0, "descricao");

            echo "<td>".$defeito_constatado."</td>";

            if ($login_fabrica == 183){
                echo "<td>".$causa_defeito_desc."</td>";
            }
        }

        if ($login_fabrica == 87) {
            $sql_peca_causadora = "SELECT referencia, descricao FROM tbl_peca WHERE peca = {$peca_causadora}";
            $res_peca_causadora = pg_query( $con, $sql_peca_causadora);
        ?>

        <td><?= $servico_realizado_descricao; ?></td>
        <td>
            <?php
                if(!empty($soaf)){
                    $sql_soaf = "SELECT descricao from tbl_tipo_soaf WHERE fabrica = $login_fabrica  AND tipo_soaf = $soaf;";
                    $res_soaf = pg_query($con, $sql_soaf);
                    if(pg_num_rows($res_soaf)){
                        echo pg_fetch_result($res_soaf, 0, 'descricao');
                    }else echo "&nbsp;";
                }else echo "&nbsp;";
            ?>
        </td>
        <? }
        if (!in_array($login_fabrica, array(30,87))) { ?>
            <TD
            <?php
                if ($login_fabrica == 51){
                    echo " nowrap ";
            }
?>
                style="text-align:left;<?=$cor_linha_peca?>">
<?
            if (in_array($login_fabrica, array(20,42))) {
                echo $preco;
            } else {

                if (isset($moduloTraducao)) {
                    $sqlIdiomaServico = "SELECT descricao 
                                             FROM tbl_servico_realizado_idioma 
                                             WHERE servico_realizado = {$servico_realizado_peca}
                                             AND LOWER(idioma) = LOWER('$cook_idioma')";
                    $resIdiomaServico = pg_query($con, $sqlIdiomaServico);

                    if (pg_num_rows($resIdiomaServico) > 0) {
                        $servico_realizado_descricao = utf8_decode(pg_fetch_result($resIdiomaServico, 0, 'descricao'));
                    }

                }

                echo $servico_realizado_descricao;
                if ($servico_realizado_estoque) {
                    echo " (usa estoque)";
                }
            }
?>
            </TD>
        <?php
            }
            if($login_fabrica == 30){

                $servico_peca = pg_fetch_result($res,$i,'servico_realizado_descricao');

                if($servico_peca == "Troca de Produto"){
                    if($admin_autoriza > 0){

                        $sql_ressarcimento = "SELECT ressarcimento FROM tbl_servico_realizado WHERE servico_realizado = {$servico_realizado_peca}";
                        $res_ressarcimento = pg_query($con, $sql_ressarcimento);

                        $ressarcimento_status = pg_fetch_result($res_ressarcimento, 0, "ressarcimento");

                        if($ressarcimento_status == "t"){
                            $servico_peca = "&nbsp; Ressarcimento ";
                        }else{

                            $sqlAd = "
                                SELECT  nome_completo
                                FROM    tbl_admin
                                WHERE   admin = $admin_autoriza
                            ";

                            $resAd = pg_query($con,$sqlAd);
                            $nomeAdminAutoriza = pg_fetch_result($resAd,0,nome_completo);

                            $servico_peca .= " <b style='font-weight:normal;color:#000000;font-size:10px'>(Aprovado por $nomeAdminAutoriza)</b>";

                        }

                    } else {
                        $sqlRecusaTroca = "
                            SELECT  os
                            FROM    tbl_os_status
                            WHERE   os = $os
                            AND     status_os = 194
                        ";
                        $resRecusaTroca = pg_query($con,$sqlRecusaTroca);
                        if (pg_fetch_result($resRecusaTroca,0,os) == $os) {
                            $servico_peca = "Troca Recusada";
                        } else {
                            $servico_peca = "Aguardando aprovação";
                        }
                    }
                }

                echo "<TD style='text-align:left'>$servico_peca</TD>";
            }

		if (in_array($login_fabrica, array(169,170))){ ?>
		<td style="text-align: center">
			<?=$data_previsao_chegada?>
		</td>
		<?php } 

        if (in_array($login_fabrica, [148])) {

            $sqlNF = "
                SELECT tbl_os_item.parametros_adicionais
                FROM tbl_os_item
                WHERE os_item = {$os_item}
            ";
            $resNf = pg_query($con, $sqlNF);

            $parametrosAdicionais = json_decode(pg_fetch_result($resNf, 0, "parametros_adicionais"), true);

            echo "<td>{$parametrosAdicionais['nf_estoque_fabrica']}</td>";

        }
        ?>
        <TD

        <?php
            if ($login_fabrica == 51){
                echo " nowrap ";
        }?>
        style="text-align:CENTER;<?=$cor_linha_peca?>">
        <? if(in_array($login_fabrica , array(142)) and strtolower($nf) <> 'atendido') {
            echo " <a href='print_pedido.php?pedido=$pedido' target='_blank'>";
        }else if(strtolower($nf) <> 'atendido'){?>
            <a href='pedido_finalizado.php?pedido=<? echo $pedido ?>' target='_blank'>
        <?}

            switch ($login_fabrica){
                case 1:
                    echo $pedido_blackedecker;
                break;
                case 88:
                    echo empty($seu_pedido)?$pedido:$seu_pedido;
                break;
                default:
                    echo $pedido;
            }

        ?>
        <? if(strtolower($nf) <> 'atendido'){?>
            </a>
            <?}?>
        </TD>
        <TD style="text-align:CENTER;<?=$cor_linha_peca?>" nowrap <? if (strlen($data_nf)==0) echo "colspan='1'"; ?>>
        <?php
        $temCredito = false;
        if ($login_fabrica == 148) {
            $sqlCredito = "SELECT os
                             FROM tbl_extrato_lancamento
                            WHERE tbl_extrato_lancamento.os = {$os}
                              AND tbl_extrato_lancamento.fabrica = {$login_fabrica}
                              AND tbl_extrato_lancamento.posto = {$login_posto}
                              /*AND tbl_extrato_lancamento.lancamento = 493;*/
                              AND tbl_extrato_lancamento.lancamento = 486";
            $resCredito = pg_query($con, $sqlCredito);
            if (pg_num_rows($resCredito) > 0) {
                $temCredito = true;
            }
        }
        if (strtolower($nf) <> 'pendente' and strtolower($nf) <> 'atendido') {

            if($telecontrol_distrib == true and $nf == '000000'){
                $nf = "";
                $data_nf = "";
            }

            if($login_fabrica == 81 AND $login_posto == 20682 AND $consumidor_revenda == "REVENDA" AND strlen($nota_fiscal_saida) > 0 AND strlen($nf) == ''){ //hd_chamado=2788473
                echo $nota_fiscal_saida;
            }else{
                if ($link == 1) {
		#	echo "<a href='nota_fiscal_detalhe.php?pedido={$pedido}&nota={$nf}&peca={$peca}' target='_blank'> $nf </a>";
                    if ($temCredito && $login_fabrica == 148) {
                        echo "<a href='#'>Crédito Gerado &nbsp;</a>";
                    } else if ($login_fabrica == 171) {
                        echo "$nf";
                    } else {
                        echo "<a href='#'>$nf</a>";
                    }
                } else {
                    if ($temCredito && $login_fabrica == 148) {
                        echo "<acronym title='Nota Fiscal do fabricante.' style='cursor:help;'> Crédito Gerado </acronym>";
                    } else if ($login_fabrica == 101 && $os_trocada === true && $servico_realizado_descricao != "Troca de Produto") {
                            echo "Cancelada";
                    } else {
                        echo "<acronym title='Nota Fiscal do fabricante.' style='cursor:help;'> $nf </acronym>";
                    }
                }
            }
        } else {
            if (in_array($login_fabrica, array(51,81,114))) {
                if ($login_posto == 4311) { // HD 52445

                    $sql  = "SELECT tbl_embarque.embarque,
                                to_char(liberado ,'DD/MM/YYYY') as liberado,
                                to_char(embarcado ,'DD/MM/YYYY') as embarcado,
                                faturar
                        FROM tbl_embarque
                        JOIN tbl_embarque_item USING (embarque)
                        WHERE tbl_embarque_item.os_item = $os_item ";

                    $resX = pg_query ($con,$sql);
                    if (pg_num_rows ($resX) > 0) {
                        $liberado  = pg_fetch_result($resX,0,liberado);
                        $embarcado = pg_fetch_result($resX,0,embarcado);
                        $faturar   = pg_fetch_result($resX,0,faturar);

                        if (strlen($embarcado) > 0 and strlen($faturar) == 0){
                            echo traduz("embarque",$con,$cook_idioma)." " . pg_fetch_result ($resX,0,embarque);
                        } else {
                            echo traduz("embarcada",$con,$cook_idioma)." ". pg_fetch_result($resX,0,liberado);
                        }
                    } else {
                        $sql  = "SELECT * FROM tbl_pedido_cancelado WHERE os=$os AND peca=$peca and pedido=$pedido";
                        $resY = pg_query ($con,$sql);

                        if (pg_num_rows ($resY) > 0) {
                            echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
                        } else {
                            if( strtolower($nf) <> 'atendido'){
                            echo "<acronym title='".traduz("pendente.com.o.fabricante",$con,$cook_idioma).".' style='cursor:help;'>";
                            }
                            echo "$nf &nbsp;";
                        }
                    }

                } else {
                    if(!empty($pedido)){
                    	$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE os=$os AND peca=$peca and pedido=$pedido";
						$resY = pg_query ($con,$sql);

						if (pg_num_rows ($resY) > 0) {
							echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
						} else {
                            if($login_fabrica == 81 AND $login_posto ==  20682 AND $consumidor_revenda == "REVENDA" AND strlen($nota_fiscal_saida) > 0){ //hd_chamado=2788473
                                echo $nota_fiscal_saida;
                            }else{
                                if( strtolower($nf) <> 'atendido'){
                                    echo "<acronym title='".traduz("pendente.com.o.fabricante",$con,$cook_idioma).".' style='cursor:help;'>";
                                }
                                echo "$nf &nbsp;";
                            }
						}
				   }

				}
            }elseif(in_array($login_fabrica,array(94,80,91,104,35,88)) or $login_fabrica > 100){
                    if(strlen($peca)>0 AND strlen($pedido)>0){
                        $sql  = "SELECT SUM(qtde) AS qtde
                                    FROM tbl_pedido_cancelado
                                    WHERE peca=$peca
                                    and pedido=$pedido
        							AND (os = $os or os isnull)";
						$sql .= (!empty($pedido_item)) ? " AND (pedido_item = $pedido_item or pedido_item isnull) " : "";
                        $resY = pg_query ($con,$sql);

                        #hd_chamado=2895822
                            $qtde_pecas_pedidas = pg_fetch_result($res,$i,'qtde');
                            $qtde_pecas_canceladas = pg_fetch_result($resY, 0, 'qtde');
                        # fim - hd_chamado=2895822
                        if (pg_num_rows ($resY) > 0 AND $qtde_pecas_canceladas >= $qtde_pecas_pedidas) {
                            echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
                        }else{
                            echo "$nf &nbsp;";
                        }
                    }
            } else {
				if(!empty($pedido) ){
                    if($login_fabrica == 101){
                        $sql = "SELECT *
                                FROM tbl_pedido_cancelado
                                JOIN tbl_pedido_item ON tbl_pedido_cancelado.pedido = tbl_pedido_item.pedido
                                JOIN tbl_os_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item AND tbl_os_item.os_item = $os_item
                                WHERE tbl_pedido_cancelado.peca=$peca
                                AND tbl_pedido_cancelado.pedido=$pedido
                                AND (tbl_pedido_cancelado.os=$os or tbl_pedido_cancelado.os  isnull)
                                AND tbl_pedido_item.qtde_cancelada > 0";
                    }else{
                        $pedido_item = (empty($pedido_item)) ? '0': $pedido_item;
                        $sql  = "SELECT tbl_pedido_cancelado.pedido,
                                        tbl_pedido_cancelado.motivo
                            FROM tbl_pedido_cancelado
                            LEFT JOIN tbl_pedido_item USING(pedido_item)
                                WHERE (tbl_pedido_cancelado.os=$os or os isnull)
                                AND tbl_pedido_cancelado.peca=$peca
                                AND tbl_pedido_cancelado.pedido=$pedido
                                AND (
                                (tbl_pedido_cancelado.pedido_item = $pedido_item AND (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) = 0)
                                OR pedido_item isnull
                            )";
                    }
                    $resY = pg_query ($con,$sql);

                    if (pg_num_rows ($resY) > 0) {
                        $motivo = pg_fetch_result ($resY,0,motivo);
                        echo "<acronym title='".$motivo."'>Cancelado</acronym>" ;
                    } else {

                        if( strtolower($nf) <> 'atendido'){
                            echo "<acronym title='".traduz("pendente.com.o.fabricante",$con,$cook_idioma).".' style='cursor:help;'>";
                        }

                        echo "$nf &nbsp;";

                    }

				}
            }
        }
?>
        </TD>

        <?php if ($login_fabrica == 164) { ?>
            <td><?=$os_data_entrada?> </td>
        <?php } ?>

        <?//incluido data de emissao por Wellington chamado 141 help-desk
        if (strlen($data_nf) > 0){
            echo "<TD style='text-align:CENTER;' nowrap>";
            echo "$data_nf";
            echo "</TD>";

            if($login_fabrica == 125){
                 $prazo_entrega = '<span data-title="Lembrando que este prazo oscila conforme o fluxo de entregas e em função de fins de semana e feriados." class="previsao_entrega">10 dias a partir da emissão da Nota Fiscal</span>';
                 echo "<td style='text-align:center;'>$prazo_entrega &nbsp;</td>";

            }
        }elseif(in_array($login_fabrica, array(81, 123)) AND $login_posto ==  20682 AND $consumidor_revenda == "REVENDA" AND strlen($nota_fiscal_saida) > 0){ //hd_chamado=2788473
            echo "<td style='text-align:CENTER;' nowrap>$data_nf_saida</td>";
        }elseif(!$controle_distrib_telecontrol){
            echo "<td></td>";
        }

        if ($login_fabrica == 1 AND $reembolso_peca_estoque == 't' and empty($tipo_atendimento)) {
            $estoque    = ucfirst($parametros_adicionais_pecas["estoque"]);
            $previsao   = mostra_data($parametros_adicionais_pecas["previsao"]);

            // regra para o obsoleto
            if (strlen($peca_fora_linha) > 0) {
                $estoque = "OBSOLETO";
                $previsao = " - ";
            }
            // regra para o subst
            if (strlen($para) > 0) {
                $estoque = "SUBST";
                $previsao = " - ";
            }
            echo "<td>".$estoque."</td>";
            echo "<td>".$previsao."</td>";
        }

        if (strlen($faturamento) > 0){
                $sql_verifica_conhecimento = "SELECT conhecimento AS conhecimento FROM tbl_faturamento_correio
                                                WHERE fabrica = $login_fabrica and faturamento = $faturamento";
                $res_verifica_conhecimento = pg_query($con, $sql_verifica_conhecimento);
        }

        //Gustavo 12/12/2007 HD 9095
        if (in_array($login_fabrica,array(11,35,45,74,80,86,157,162,172,175))) {
            echo "<TD class='conteudo' style='text-align:CENTER;".$bg_cadence."'>";

            if (strlen($faturamento) > 0 && !empty($data_nf)){

		if(strpos($conhecimento,"http") !== false){
			echo "<A HREF='{$conhecimento}' target='_blank'>Ratreio Pedido</A>";
		}else if(pg_num_rows($res_verifica_conhecimento)>0){
				if (in_array($login_fabrica, array(157))) {
					if (preg_match("/^\[.+\]$/", $conhecimento)) {
						$conhecimento = json_decode($conhecimento, true);
						$codigos_rastreio = array();
						foreach ($conhecimento as $key => $codigo_rastreio) {
							if(pg_num_rows($res_verifica_conhecimento)>0){
								$codigos_rastreio[] = "<A HREF='./relatorio_faturamento_correios.php?conhecimento=$codigo_rastreio' rel='shadowbox'>$codigo_rastreio</A>";
							}else{
								$codigos_rastreio[] = "<A HREF='#' rel='$codigo_rastreio' class='correios'>$codigo_rastreio</A>";
							}
						}

						echo implode(", ", $codigos_rastreio);
					} else {
						if(pg_num_rows($res_verifica_conhecimento)>0){
							echo "<A HREF='./relatorio_faturamento_correios.php?conhecimento=$conhecimento' rel='shadowbox'>";
                            echo $conhecimento;
                            echo "</A>";
						}else{
							echo "<A HREF='#' rel='$conhecimento' class='correios'>";
						  echo $conhecimento;
                        }
						echo "</A>";
					}
				}else{
					echo "<A HREF='./relatorio_faturamento_correios.php?conhecimento=$conhecimento' rel='shadowbox'>{$conhecimento}</A>";
				}
                }else{
		
					if (preg_match("/^\[.+\]$/", $conhecimento)) {
						$conhecimento = json_decode($conhecimento, true);
						$codigos_rastreio = array();

						foreach ($conhecimento as $key => $codigo_rastreio) {
							if (!empty($codigos_rastreio)) {
								$codigos_rastreio[] = "<A HREF='#' rel='$codigo_rastreio' class='correios'>$codigo_rastreio</A>";
							}
						}

						echo (!empty($codigos_rastreio)) ? implode(", ", $codigos_rastreio) : '';
					} else {
						if($login_fabrica == 80){
						    if(empty($conhecimento) and !empty($numero_pac)){
							echo "{$numero_pac}";
						    }else{
							echo "{$conhecimento}";
						    }
						}else if($login_fabrica == 175 AND strlen($transp) > 0){
							echo "<span title='$transp'>".substr($transp,0,14)."</span>";
						}else{
						    echo "{$conhecimento}";
						}						
					}
                }
            }
            echo "</TD>";
        } else {
            if ($login_fabrica == 186 && strlen($pedido) > 0) {


                $sqlEti = "SELECT tbl_etiqueta_servico.etiqueta 
                             FROM tbl_etiqueta_servico 
                             JOIN tbl_pedido USING(etiqueta_servico)
                            WHERE tbl_pedido.pedido = {$pedido}";
                $resEti = pg_query($con, $sqlEti);
                if (pg_num_rows($resEti)> 0) {
                    $codRastreio = pg_fetch_result($resEti, 0, 'etiqueta');
                    echo "<TD class='conteudo' style='text-align:CENTER; $bg_cadence'><p style='color:#63798D;cursor:pointer' class='correios' rel='{$codRastreio}'><b>{$codRastreio}</b></p></TD>";

                }  else {
                    echo "<TD class='conteudo' style='text-align:CENTER; $bg_cadence'></TD>";
                } 
            // Ajusta o colspan da tabela DIAGNÓSTICOS - COMPONENTES - MANUTENÇÕES EXECUTADAS
            } else if (!in_array($login_fabrica, [1,20,24,91,139,148,152,158,169,170,178,180,181,182,183,195])) {
                echo "<TD class='conteudo' style='text-align:CENTER; $bg_cadence'></TD>";
            } 
        }

        if($telecontrol_distrib and !$controle_distrib_telecontrol){

			$sqlConhecimento = "SELECT tbl_faturamento.conhecimento, tbl_faturamento_correio.faturamento_correio FROM tbl_faturamento_item join tbl_faturamento using(faturamento) left join tbl_faturamento_correio using(faturamento)  WHERE os = $os and os_item = $os_item ";
            $resConhecimento = pg_query($con, $sqlConhecimento);
            if(pg_num_rows($resConhecimento)){
                $conhecimento = pg_fetch_result($resConhecimento, 0, 'conhecimento');
                $faturamento_correio = pg_fetch_result($resConhecimento, 0, 'faturamento_correio');
			}else{
				$sqlConhecimento = "SELECT tbl_os_item.os_item, tbl_etiqueta_servico.etiqueta
                                FROM tbl_os_item
                                INNER JOIN tbl_embarque_item ON tbl_embarque_item.os_item = tbl_os_item.os_item
                                INNER JOIN tbl_etiqueta_servico ON tbl_embarque_item.embarque = tbl_etiqueta_servico.embarque
                                INNER JOIN tbl_embarque on tbl_embarque_item.embarque = tbl_embarque.embarque
                                WHERE tbl_os_item.os_item = $os_item";

				$resConhecimento = pg_query($con, $sqlConhecimento);
				if(pg_num_rows($resConhecimento)){
					$conhecimento = pg_fetch_result($resConhecimento, 0, 'etiqueta');
				}else{
					$conhecimento = "";
				}
            }

            echo "<TD style='text-align:CENTER;'>";
				if(!empty($faturamento_correio)) {
					echo "<A HREF='./relatorio_faturamento_correios.php?conhecimento=$conhecimento' rel='shadowbox'>";
					echo "$conhecimento";
					echo "</a>";

				}else{
					echo "<a href='#' class='correios' rel='$conhecimento'>";
						echo "$conhecimento";
					echo "</a>";
				}
            echo "</TD>";
        }

        if (in_array($login_fabrica, array(147,151))) {
            echo "<TD style='text-align:CENTER;' nowrap>";

            if (preg_match("/^\[.+\]$/", $conhecimento)) {
                $conhecimento = json_decode($conhecimento, true);

                $codigos_rastreio = array();

                foreach ($conhecimento as $key => $codigo_rastreio) {
                    if(pg_num_rows($res_verifica_conhecimento)>0){
                        $codigos_rastreio[] = "<A HREF='./relatorio_faturamento_correios.php?conhecimento=$codigo_rastreio' rel='shadowbox'>$codigo_rastreio</A>";
                    }else{
                        $codigos_rastreio[] = "<A HREF='#' class='correios' rel='$codigo_rastreio'>$codigo_rastreio</A>";
                    }
                }

                echo implode(", ", $codigos_rastreio);
            } else {
                if(pg_num_rows($res_verifica_conhecimento)>0){
                    echo "<A HREF='./relatorio_faturamento_correios.php?conhecimento=$conhecimento' rel='shadowbox'>";
                }else{
                    echo "<A HREF='#' class='correios' rel='$conhecimento'>";
                }
                    echo $conhecimento;
                echo "</A>";
            }
            echo "</TD>";
        }

        if (in_array($login_fabrica,array(151))) {
            echo "<TD style='text-align:CENTER;' nowrap>";
		if (!empty($conhecimento)) {
	                echo $saida;
		}
            echo "</TD>";
        }

        if (in_array($login_fabrica,array(35,169,170))) {
            echo "<TD style='text-align:CENTER;' nowrap>";
                echo $xdata_entrega;
            echo "</TD>";
            if($login_fabrica == 35){
		    echo "<TD style='text-align:CENTER;' nowrap>";
			echo $po_peca;
		    echo "</TD>";
	    }
        }

        if (in_array($login_fabrica,array(156))) {
            echo "<TD style='text-align:CENTER;' nowrap>";
                echo $parametros_adicionais;
            echo "</TD>";
        }

        //nf do distribuidor - chamado 141
        if ($login_fabrica==3) {
            echo "<TD style='text-align:CENTER;' nowrap>";

            if (strlen($nota_fiscal_distrib) > 0) {

                if ($link_distrib == 1) {
                    echo "<acronym title='".traduz("nota.fiscal.do.distribuidor",$con,$cook_idioma).".' style='cursor:help;'><a href='nota_fiscal_detalhe.php?nota_fiscal=".$nota_fiscal_distrib."&peca=$peca' target='_blank'>$nota_fiscal_distrib  - $data_nf_distrib</a>";
                } else {
                    echo "<acronym title='".traduz("nota.fiscal.do.distribuidor",$con,$cook_idioma).".' style='cursor:help;'> $nota_fiscal_distrib"." - ".$data_nf_distrib;
                }
            } else {
//              echo "a $nota_fiscal_distrib";
                //se não tiver nota do distrib verifica se está em embarque e exibe numero do embarque
                $sql  = "SELECT tbl_embarque.embarque,
                                to_char(liberado ,'DD/MM/YYYY') as liberado,
                                to_char(embarcado ,'DD/MM/YYYY') as embarcado,
                                faturar
                        FROM tbl_embarque
                        JOIN tbl_embarque_item USING (embarque)
                        WHERE tbl_embarque_item.os_item = $os_item ";

                // HD 7319 Paulo alterou para mostrar dia que liberou o embarque
                $resX = pg_query ($con,$sql);
                if (pg_num_rows ($resX) > 0) {
                    $liberado  = pg_fetch_result($resX,0,liberado);
                    $embarcado = pg_fetch_result($resX,0,embarcado);
                    $faturar   = pg_fetch_result($resX,0,faturar);

                    if(strlen($embarcado) > 0 and strlen($faturar) == 0){
                        echo traduz("embarque",$con,$cook_idioma)." " . pg_fetch_result ($resX,0,embarque);
                    } else {
                        echo traduz("embarcada",$con,$cook_idioma)." ". pg_fetch_result($resX,0,liberado);
                    }
                } else {
                    //HD 20787
                    if(strlen(trim($nota_fiscal_distrib))==0 and $nf<>'Pendente' and !empty($pedido) and !empty($peca)){
                        if(!is_int($peca) OR !is_int($pedido)){
                            $peca   = (int) $peca;
                            $pedido = (int) $pedido;
                        }
                        $sql = "SELECT motivo
                                FROM   tbl_pedido_cancelado
                                WHERE  pedido = $pedido
                                AND    peca   = $peca
                                ;";
                        $resx = @pg_query ($con,$sql);
                        if (@pg_num_rows ($resx) > 0) {
                            $motivo = pg_fetch_result($resx,0,motivo);
                            echo  "<a href='#' title='$motivo'>".traduz("cancelada",$con,$cook_idioma)."</a>";
                        }
                    }
                    // HD 7319 Fim
                }
            }
            echo "</TD>";
            echo "</TD>";

        echo "<td style='text-align:CENTER;' nowrap>";
            $sqlPF = "SELECT
                        tbl_os_item.os_item
                    FROM tbl_os_item
                    JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                    WHERE tbl_os.os = {$os}
                    AND tbl_os_item.os_item = {$os_item}
                    AND tbl_os_item.servico_realizado = 20
                    AND tbl_os_item.parametros_adicionais ILIKE '%\"item_foto_upload\":\"t\"%'";
            $resPF = pg_query($con, $sqlPF);

            if (pg_num_rows($resPF) > 0) {
                echo "<span style='color: #63798D; font-weight: bold; text-decoration: none; cursor: pointer;' onclick='window.open(\"mostra_upload.php?os={$os}&os_item={$os_item}\", \"_blank\");' title='OS possui peças com Upload de fotos obrigatório'>Ver anexos</span>";
            }
        echo "</td>";

        //$conhecimento = "DM168150394BR";

            $regex = '/(([A-Z]{2})[0-9]{9}([A-Z]{2}))/';
            if (preg_match($regex, $conhecimento)) {
                $var_correios = "correios";
            }else{
                $var_correios = '';
            }

            if($var_correios == "correios"){
                echo "<TD class='conteudo' >";
                    echo "<A HREF='#' class='correios' rel='$conhecimento'>";
                     echo $conhecimento;
                    echo "</A>";
                echo "</TD>";
            }else{
                echo "<TD class='conteudo' >";
                     echo $conhecimento;
                echo "</TD>";
            }

        }
        //linha de informatica da Britania
        if ($linhainf == 't' AND $login_fabrica != 127 AND $login_fabrica !=134 AND  $login_fabrica !=136 and $login_fabrica != 162) {
            echo "<TD style='text-align:CENTER;' nowrap>";
            echo "$peca_serie";
            echo "</TD>";
            echo "<TD style='text-align:CENTER;' nowrap>";
            echo "$peca_serie_trocada";
            echo "</TD>";
        }

        if(in_array($login_fabrica, [167, 203])){
            echo "<TD style='text-align:CENTER;' nowrap>";
            echo "$peca_serie";
            echo "</TD>";
        }
        //linha de informatica da Britania?>
    </TR>
    <?php if($login_fabrica == 123 and $servico_realizado_descricao == 'Cancelado'){ ?>
        <tr>
            <td colspan="8" class='conteudo' style='color:red; font-size: 9px'><?= utf8_decode($obs_os_item)?></td>
            
        </tr>
    <?php } ?>
    <?php if( in_array($login_fabrica, [173]) ) {
        $sqlPostoInterno = "SELECT posto
                FROM tbl_posto_fabrica
                JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.posto_interno
                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND tbl_posto_fabrica.posto =  {$login_posto} ";
        $resPostoInterno = pg_query($con,$sqlPostoInterno);
        if(pg_num_rows($resPostoInterno)) {
            $posto_interno = true;
        }
        $sqlNvSerie = "SELECT serie_justificativa FROM tbl_os_extra WHERE os = {$os} ";
        $resNvSerie = pg_query($con, $sqlNvSerie);
        $nvNumeroSerie = pg_fetch_result($resNvSerie, 0, "serie_justificativa");
            if ($nvNumeroSerie != '' && $nvNumeroSerie != null && $posto_interno == true) { 
                ;
                ?>
                <tr>
                    <td colspan="8" class='conteudo'>Novo Número de Serie: 
                        <?php 
                        if ($status_checkpoint == 'Finalizada') {
                           echo  "<label>$nvNumeroSerie</label>";
                        } else {
                            echo "<input type='text' readonly name='nv_numero_serie' value='$nvNumeroSerie'/>";
                        }
                        ?>                        
                    </td>
                    
                </tr>
        <?php }
        } ?>
<?php
	if(!empty($pedido_item)) {	
        $sqlPecaFaturamento = "SELECT 
            tbl_faturamento_item.peca,
            tbl_peca.referencia,
            tbl_peca.descricao
        FROM tbl_faturamento_item 
            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
        where pedido_item = {$pedido_item}";
        $resPecaFaturamento = pg_query($con, $sqlPecaFaturamento);
        $faturmaneto_peca = pg_fetch_result($resPecaFaturamento,0,'peca');
        $faturmaneto_descricao = pg_fetch_result($resPecaFaturamento,0,'descricao');
        $faturmaneto_referencia = pg_fetch_result($resPecaFaturamento,0,'referencia');

		if ($login_fabrica == 157 && $faturmaneto_peca > 0 ) {
			echo "<tr class='conteudo'><td colspan='8'>";
			echo "<label style='color: red'>";
			echo "Peça substituida pelo fábricante: " . $faturamento_referencia . " - " . $faturmaneto_descricao;
			echo "<label>";
			echo "</td></tr>";
		}
	}?>

<?php
    if (in_array($login_fabrica, [1,3]) && !empty($motivo)) {

?>
    <tr>
        <td class = "conteudo" colspan="100%"> <?= traduz("motivo.cancelamento") ?>: <?=utf8_decode($motivo)?> </td>
    </tr>
<?php
    $motivo = "";
    }
    if (in_array($login_fabrica,array(169,170)) && !empty($transp)) { ?>
        <tr>
            <td class="conteudo" colspan="9"><?= traduz("transportadora") ?>: <?= utf8_decode($transp); ?></td>
        </tr>
    <? }

    if((in_array($login_fabrica, [122,160]) or $replica_einhell) and strlen(trim($obs_alternativa))>0){ ?>
        <tr>
            <td class='conteudo' colspan="8" style="color:red"><img src='imagens/setinha_linha.gif' border='0' />  <?= $obs_alternativa?></td>
        </tr>
    <?php
    }

        // HD 8412
        /**
         * @since HD 749085 - Black
         */
        $mostra_obs = array(1,3,14,35,167,169,170,203);
        if (in_array($login_fabrica, $mostra_obs) and strlen($obs_os_item) >0) {

            $obs_dez_percento = null;

            if ($login_fabrica == 14) {//HD 212179

                $sql_dez = "SELECT tbl_os_item.obs as obs
                              FROM tbl_os_item
                             WHERE tbl_os_item.os_item = $os_item
                               AND tbl_os_item.obs     = '### PEÇA INFERIOR A 10% DO VALOR DE MÃO-DE-OBRA ###'";

                $res_dez = pg_exec($con, $sql_dez);

                if (pg_numrows($res_dez) > 0) {
                    $obs_dez_percento = '<font color="red"><b>'.traduz("esta.peca.nao.sera.reposta.em.garantia.conforme.regra.de.reposicao.de.pecas").'</b></font>';
                }

            }

	    if (in_array($login_fabrica, [169, 170])) {
                if (!empty($causa_defeito_desc)) {
                    echo "<tr>";
                        echo "<td class='conteudo' colspan='100%'>";
                            echo "Motivo 2ª Solicitação: ".$causa_defeito_desc;
                        echo "</td>";
                    echo "</tr>";        
                }
            }

            if (in_array($login_fabrica, array(35, 167,203))) {
                if (isset($parametrosAdd["pecaReenviada"]) && $parametrosAdd["pecaReenviada"]) {
                    echo "<tr><td class='conteudo' colspan='100%'  style='{$bg_cadence}'><img src='imagens/setinha_linha.gif' border='0' /> Motivo solicitação: $obs_os_item</td></tr>";
                } else {
                     echo "<tr><td class='conteudo' colspan='100%'>Obs: $obs_os_item</td></tr>";
                }            
            } else {

                echo "<tr>";
                    echo "<td class='conteudo' colspan='100%'>";
                        echo "Obs: ". ($obs_dez_percento != null ? $obs_dez_percento : $obs_os_item);
                    echo "</td>";
                echo "</tr>";
           }
        }
    }


    if( (in_array($login_fabrica, array(156,161,167,177,186,190,191,195,203)) && $desc_tipo_atendimento == "Orçamento") OR (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia') ){
        // Bloco de: Valor Adicional e Desconto
        if(in_array($login_fabrica, [161,167,177,186,190,191,195,203])){
            $colspan_total = 3;
            if($login_fabrica == 161){
                $condAdicionais = "AND valores_adicionais notnull";
            }else{
                $condAdicionais = "";
            }

            if (in_array($login_fabrica, [177,186,190,191,195])) {
                $colspan_total = 5;
                if (in_array($login_fabrica, [186,190,191,195])) {
                    $colspan_total = 3;
                }

                $sql_adicionais = "SELECT coalesce(mao_de_obra_adicional, 0) as mao_de_obra_adicional, coalesce(desconto, 0) as desconto
                                   FROM tbl_os_extra
                                   WHERE tbl_os_extra.os = {$os}";
            } else {
                $sql_adicionais = "SELECT valores_adicionais, campos_adicionais FROM tbl_os_campo_extra WHERE os = $os $condAdicionais";
            }

            $res_adicionais = pg_query($con, $sql_adicionais);

            if(pg_num_rows($res_adicionais) > 0){

                if(in_array($login_fabrica,[167,203])) {
                    $valores_adicionais = pg_fetch_result($res_adicionais, 0, "campos_adicionais");
                    $valores_adicionais = json_decode($valores_adicionais, true);

                    $valor_adicional = $valores_adicionais["valor_adicional_peca_produto"];

                    if(strlen(trim($valor_adicional)) > 0){
                        $valor_adicional = $valores_adicionais["valor_adicional_peca_produto"];
                        $valor_adicional = str_replace(",",".",$valor_adicional);
                    }else{
                        $valor_adicional = 0;
                    }

                    $total_geral = $valor_total_pecas + $valor_adicional;

                }else if (in_array($login_fabrica, [177,186,190,191,195])) {

                    $valor_adicional = pg_fetch_result($res_adicionais, 0, 'mao_de_obra_adicional');
                    $desconto        = pg_fetch_result($res_adicionais, 0, 'desconto');

                    $total_geral = $valor_total_pecas + $valor_adicional - $desconto;

                } else {
                    $valores_adicionais = pg_fetch_result($res_adicionais, 0, "valores_adicionais");
                    $valores_adicionais = json_decode($valores_adicionais, true);

                    $valor_adicional = $valores_adicionais["Valor Adicional"];
                    $desconto        = $valores_adicionais["Desconto"];

                    $total_geral = $valor_total_pecas + $valor_adicional - $desconto;

                }
            }

            if (in_array($login_fabrica, [167,186,190,191,195,203])) {
                $labelMO = traduz("valor.de.mao.de.obra");
            } else {
                $labelMO = traduz("valor.adicional");
            }

            ?>
            <?php if ($login_fabrica != 177){ ?>
            <tr>
                <td class='conteudo' colspan="3" align="right"><?= $labelMO ?></td>
                <td class='conteudo' style="text-align: center;"><?=number_format($valor_adicional, 2, ",", ".")?></td>
            </tr>
            <?php } ?>

            <?php if(!in_array($login_fabrica,[167,177,186,190,191,195,203])) { ?>
            <tr>
                <td class='conteudo' colspan="3" align="right"><?= traduz("valor.de.desconto") ?></td>
                <td class='conteudo' style="text-align: center;"><?=number_format($desconto, 2, ",", ".")?></td>
            </tr>
            <?php } ?>
            <?php if ($login_fabrica != 177){ ?>
            <tr>
                <td class='conteudo' colspan="3" align="right"><?= traduz("valor.total.pecas") ?></td>
                <td class='conteudo' style="text-align: center;"><?=number_format($valor_total_pecas, 2, ",", ".")?></td>
            </tr>
            <?php } ?>
            <tr>
                <td class='conteudo' colspan="<?=$colspan_total?>" align="right" style="font-size: 15px;"><?= traduz("valor.total.geral") ?></td>
                <td class='conteudo' style="text-align: center; font-size: 15px;"><?=number_format($total_geral, 2, ",", ".")?></td>
            </tr>

            <?php

        }elseif ($login_fabrica == 163) {

            $sql_adicionais = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = $os AND valores_adicionais notnull";
            $res_adicionais = pg_query($con, $sql_adicionais);

            if(pg_num_rows($res_adicionais) > 0){

                $valores_adicionais = pg_fetch_result($res_adicionais, 0, "valores_adicionais");
                $valores_adicionais = json_decode($valores_adicionais, true);

                $valor_adicional = $valores_adicionais["Valor Adicional"];
                $desconto        = $valores_adicionais["Desconto"];

                $total_geral = $valor_total_pecas + $valor_adicional - $desconto;
            }

            ?>
            <tr>
                <td class='conteudo' colspan="3" align="right"><?= traduz("valor.total.pecas") ?></td>
                <td class='conteudo' style="text-align: center;"><?=number_format($valor_total_pecas, 2, ",", ".")?></td>
            </tr>
            <tr>
                <td class='conteudo' colspan="3" align="right"><?= traduz("valor.adicional") ?></td>
                <td class='conteudo' style="text-align: center;"><?=number_format($valor_adicional, 2, ",", ".")?></td>
            </tr>
            <tr>
                <td class='conteudo' colspan="3" align="right"><?= traduz("valor.de.desconto") ?></td>
                <td class='conteudo' style="text-align: center;"><?=number_format($desconto, 2, ",", ".")?></td>
            </tr>
            <tr>
                <td class='conteudo' colspan="3" align="right" style="font-size: 15px;"><?= traduz("valor.total.geral") ?></td>
                <td class='conteudo' style="text-align: center; font-size: 15px;"><?=number_format($total_geral, 2, ",", ".")?></td>
            </tr>

            <?php
        } else{ ?>
        <tr>
            <td class='conteudo' colspan="3"><?= traduz("valor.total.pecas") ?></td>
            <td class='conteudo' ><?=$valor_total_pecas?></td>
        </tr>
        <?php
        }
    }

    //NOTA FISCAL MANUAL - Se já achou e imprimiu no item, não precisa emitir
    if (in_array($login_fabrica, array(51, 81, 114)) and $manual_ja_imprimiu == 0){
        $sqlm = "SELECT tbl_peca.referencia,
                    tbl_peca.descricao,
                    tbl_faturamento_item.qtde,
                    tbl_faturamento.nota_fiscal              AS nota_fiscal ,
                    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')   AS emissao
                    FROM    tbl_faturamento
                    JOIN    tbl_faturamento_item USING (faturamento)
                    JOIN    tbl_peca on tbl_peca.peca = tbl_faturamento_item.peca
                    WHERE   tbl_faturamento.fabrica = 10
                    AND     tbl_faturamento_item.os = $os
                    LIMIT 1";
        $resm = pg_query ($con,$sqlm);
        if (pg_num_rows ($resm) > 0) {
            $referenciam  = pg_fetch_result($resm,0,referencia);
            $descricaom   = pg_fetch_result($resm,0,descricao);
            $qtdem        = pg_fetch_result($resm,0,qtde);
            $nf           = pg_fetch_result($resm,0,nota_fiscal);
            $data_nf      = trim(pg_fetch_result($resm,0,emissao));
            echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
            echo "<tr><td align='center' class='inicio'>";
            echo "NOTA FISCAL MANUAL DO DISTRIBUIDOR";
            echo "</td></tr>";
            echo "<tr>";
                echo "<td align='center' class='conteudo_sac'>$referenciam</td>";
                echo "<td align='center' class='conteudo_sac'>$descricaom</td>";
                echo "<td align='center' class='conteudo_sac'>$qtdem</td>";
                echo "<td align='center' class='conteudo_sac'><a href='nota_fiscal_detalhe.php?nota_fiscal=".$nf."' target='_blank'>$nf</a></td>";
                echo "<td align='center' class='conteudo_sac'>$data_nf</td>";
            echo "</tr>";
            echo "</table>";
        }
    }

    //ORCAMENTO - WALDIR

    if ($login_fabrica == 45 or $login_fabrica == 24) {
        $num_pecas_orcamento = pg_num_rows($res_orcamento);

        echo "<input type='hidden' value='$num_pecas_orcamento' id='qtde_pecas_orcamento' name='qtde_pecas_orcamento'>";

        for ($f=0;$f<pg_num_rows($res_orcamento);$f++) {

            $peca_descricao_orcamento   = pg_fetch_result($res_orcamento,$f,descricao_peca);
            $peca_referencia_orcamento  = pg_fetch_result($res_orcamento,$f,referencia_peca);
            $qtde_orcamento             = pg_fetch_result($res_orcamento,$f,qtde);
            $pedido_orcamento           = pg_fetch_result($res_orcamento,$f,pedido);
            $data_digitacao_orcamento   = pg_fetch_result($res_orcamento,$f,data_digitacao);
            $defeito_descricao_orcamento= pg_fetch_result($res_orcamento,$f,defeito);
            $preco_orcamento            = pg_fetch_result($res_orcamento,$f,preco);
            $preco_venda_orcamento      = pg_fetch_result($res_orcamento,$f,preco_venda);
            $aprovado_orcamento         = pg_fetch_result($res_orcamento,$f,aprovado);
            $servico_descricao_orcamento= pg_fetch_result($res_orcamento,$f,servico_realizado_descricao);
            $preco_orcamento            = number_format($preco_orcamento,2,",",".");
            $preco_venda_orcamento      = number_format($preco_venda_orcamento,2,",",".");

            echo "<input type='hidden' value='$peca_referencia_orcamento-$peca_descricao_orcamento' id='peca_orcamento_$f' name='peca_orcamento_$f'>";
            echo "<input type='hidden' value='$preco_orcamento' id='preco_orcamento_$f' name='preco_orcamento_$f'>";

            if ($aprovado_orcamento == 'f') {
                $cor = '#FF6633';
            }

            if ($aprovado_orcamento == 't') {
                $cor = '#3399FF';
            }

            echo "<tr class='conteudo' style=background-color:$cor>";
            echo "<td  nowrap>$peca_referencia_orcamento - $peca_descricao_orcamento - R$ $preco_venda_orcamento</td>";
            echo "<td style='text-align:CENTER;' nowrap>$qtde_orcamento</td>";
            echo "<td style='text-align:CENTER;' nowrap>$data_digitacao_orcamento</td>";
            echo "<td style='text-align:RIGHT;' nowrap>$defeito_descricao_orcamento</td>";
            echo "<td style='text-align:RIGHT;' nowrap>$servico_descricao_orcamento</td>";
            echo "<td style='text-align:CENTER;' nowrap><a href='pedido_finalizado.php?pedido=$pedido_orcamento' target='_blank'>$pedido_orcamento</td>";
            echo "<td colspan=2 style='text-align:CENTER;' nowrap></td>";
            echo "<tr>";
        }
    }

    //HD 145639:    ALTERADA A SQL PARA PUXAR O TIPO DE ATENDIMENTO DA tbl_os E NÃO DA tbl_os_troca, POIS COM ESTE
    //              CHAMADO A tbl_os_troca PODE TER MAIS DE UM ITEM, MAS O CAMPO situacao_atendimento

    //Chamado 2365
    /* HD 145639: ALTERANDO PARA MOSTRAR MAIS DE UM PRODUTO */
    if($login_fabrica == 1 && (in_array($tipo_atendimento,array(17,18,35,64,65,69)))) {
        #HD 15198
        $sql  = "
        SELECT
        tbl_os_troca.ri AS pedido,
        tbl_os.nota_fiscal_saida AS nota_fiscal,
        TO_CHAR(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf,
        tbl_produto.descricao

        FROM
        tbl_os_troca
        JOIN tbl_os USING(os)
        JOIN tbl_produto ON tbl_os_troca.produto=tbl_produto.produto

        WHERE
        tbl_os.os = $os
        AND tbl_os.fabrica = $login_fabrica
        AND tbl_os.posto = $login_posto
        ";
        $resX = pg_query ($con,$sql);
        if(pg_num_rows($resX) > 0){
            for ($p = 0; $p < pg_num_rows($resX); $p++) {
                $Xpedido      = pg_fetch_result($resX, $p, pedido);
                $Xnota_fiscal = pg_fetch_result($resX, $p, nota_fiscal);
                $Xdata_nf     = pg_fetch_result($resX, $p, data_nf);
                $Xdescricao   = pg_fetch_result($resX, $p, descricao);

                echo "<tr align='center'>";
                    echo "<td class='conteudo' align='center'><center>$Xdescricao</center></td>";
                    echo "<td class='conteudo'></td>";
                    echo "<td class='conteudo'></td>";
                    echo "<td class='conteudo'></td>";
                    echo "<td class='conteudo'></td>";
                    echo "<td class='conteudo' align='center'><center>$Xpedido</center></td>";
                    echo "<td class='conteudo' align='center'><center>$Xnota_fiscal</center></td>";
                    echo "<td class='conteudo' align='center'><center>$Xdata_nf</center></td>";
                    echo "<td class='conteudo' align='center'><center></center></td>";
                    echo "<td class='conteudo' align='center'><center></center></td>";
                echo "<tr>";
            }
        }

    }

    ?>
</TABLE>
<?php if($login_fabrica == 148) : 
    $sql_adicionais_148 = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = $os";
    $res_adicionais_148 = pg_query($con, $sql_adicionais_148);

    $campos_adicionais = json_decode(pg_fetch_result($res_adicionais_148, 'valores_adicionais'), true);
?>
    <table width='700px' border='0'  cellspacing='1' cellpadding='2' align='center' class='Tabela'>
        <tr>
            <td colspan="7" class='inicio'>Descrição da Falha</td>
        </tr>
        
        <tr>
            <td class='conteudo'><?= (mb_detect_encoding($campos_adicionais['descricao_falha'], "UTF-8")) ? utf8_decode($campos_adicionais['descricao_falha']) : $campos_adicionais['descricao_falha'] ?></td>
        </tr>
    </table>
    <table width='700px' border='0'  cellspacing='1' cellpadding='2' align='center' class='Tabela'>
        <tr>
            <td colspan="7" class='inicio'>Detalhes da Solução</td>
        </tr>
        
        <tr>
            <td class='conteudo' style="width:700px"><?= (mb_detect_encoding($campos_adicionais['detalhe_solucao'], "UTF-8")) ? utf8_decode($campos_adicionais['detalhe_solucao']) : $campos_adicionais['detalhe_solucao'] ?></td>
        </tr>
    </table>
<?php endif;?>
<?php

    if($login_fabrica == 96){
        $sql = "SELECT
                    status.descricao as status_descricao
                FROM
                    tbl_os as os
                    JOIN tbl_status_checkpoint as status USING (status_checkpoint)
                WHERE
                    os.os = $os AND
                    os.fabrica = $login_fabrica AND
                    os.status_checkpoint in (5,6,7) AND
                    os.tipo_atendimento = 93
                    ";
        $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $status_descricao = pg_fetch_result($res,0,'status_descricao');

                $sql = "SELECT
                            total,
                            total_horas
                        FROM
                            tbl_orcamento_os_fabrica
                        WHERE
                            os = $os AND
                            fabrica = $login_fabrica";
                $res = pg_query($con, $sql);

                $total = strlen(pg_fetch_result($res,0,'total')) > 0 ? pg_fetch_result($res,0,'total') : "00";
                $total_horas = strlen(pg_fetch_result($res,0,'total_horas')) > 0 ? pg_fetch_result($res,0,'total_horas') : "&nbsp;";

                echo "<table width='700px' border='0'  cellspacing='1' cellpadding='2' align='center' class='Tabela'>";
                    echo "<tr>";
                        echo "<td class='inicio'>".traduz("orcamento")."</td>";
                    echo "</tr>";
                    echo "<tr>";
                        echo "<td class='titulo2'>".traduz("descricao.do.status")."</td>";
                        echo "<td class='titulo2' style='text-align: right'>".traduz("total")."</td>";
                        echo "<td class='titulo2' style='text-align: right'>".traduz("total.de.horas")."</td>";
                    echo "<tr>";
                    echo "<tr>";
                        echo "<td class='conteudo'>$status_descricao</td>";
                        echo "<td class='conteudo' style='text-align: right'> R$ ".number_format($total, 2, ',', ' ')."</td>";
                        echo "<td class='conteudo' style='text-align: right'>$total_horas</td>";
                    echo "<tr>";
                echo "</table>";
            }
    }

if( in_array($login_fabrica, array(11,172)) ){
	$sql = "SELECT 	tbl_os_item.peca ,
					tbl_os_item.pedido
				FROM tbl_os_produto
				JOIN tbl_os_item USING (os_produto)
				JOIN tbl_pedido_cancelado ON tbl_pedido_cancelado.peca = tbl_os_item.peca AND tbl_pedido_cancelado.pedido = tbl_os_item.pedido
				WHERE tbl_os_produto.os = $os
				AND  tbl_os_item.pedido is not null";
				//echo nl2br($sql); exit;
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0){
			echo "</br> <TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>
						<TR>
							<TD class='inicio' style='text-align:center;' colspan='4' width='700'>".traduz("pecas.canceladas")."</TD>
						</TR>
						<TR align='center'>
							<TD class='titulo2' align='center' width='70'>".traduz("peca")."</TD>
							<TD class='titulo2' align='center' width='70'>".traduz("pedido")."</TD>
							<TD class='titulo2' align='center' width='490'>".traduz("motivo")."</TD>
						</TR>";
			for($i = 0 ; $i < pg_num_rows($res) ; $i++){
				$peca = pg_fetch_result($res,$i,"peca");
				$pedido = pg_fetch_result($res,$i,"pedido");
				$sqlW = "SELECT *
						FROM tbl_pedido_cancelado
						WHERE os=$os
						AND peca=$peca
						AND pedido=$pedido";
				$resW = pg_query($con,$sqlW);
				$nW = pg_num_rows($resW);
			   if ($nW > 0) {
					$sql1 = "Select referencia from tbl_peca where peca=$peca";
				$res1 = pg_query($con,$sql1);
						echo "
						<TR align='center' style='background-color ;'>
							<TD class='conteudo' style='background-color: $cor_status; text-align:center;'>".pg_fetch_result($res1,0,referencia)."</TD>
							<TD class='conteudo' style='background-color: $cor_status; text-align:center;'>".pg_fetch_result($resW,0,pedido)."</TD>
							<TD class='conteudo' style='background-color: $cor_status; text-align:center;'>".pg_fetch_result($resW,0,motivo)."</TD>
						</TR>";


				}
			}
			echo "</TABLE>";
		}
	}

	//HD 214236: Auditoria Prévia de OS, mostrando status
if ($login_fabrica == 14 || $login_fabrica == 43) {

    $sql = "
    SELECT
    tbl_os_auditar.os_auditar,
    tbl_os_auditar.cancelada,
    tbl_os_auditar.liberado,
    TO_CHAR(tbl_os_auditar.data, 'DD/MM/YYYY HH24:MI') AS data ,
    TO_CHAR(CASE
        WHEN tbl_os_auditar.liberado_data IS NOT NULL THEN tbl_os_auditar.liberado_data
        WHEN tbl_os_auditar.cancelada_data IS NOT NULL THEN tbl_os_auditar.cancelada_data
        ELSE null
    END, 'DD/MM/YYYY HH24:MI') AS data_saida,
    tbl_os_auditar.justificativa

    FROM
    tbl_os_auditar

    WHERE
    tbl_os_auditar.os=$os
    ";
    $res = pg_query($con, $sql);
    $n = pg_numrows($res);

    if ($n > 0) {
        echo "
        <TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>
            <TR>
                <TD class='inicio' style='text-align:center;' colspan='4' width='700'>
                AUDITORIA PRÉVIA
                </TD>
            </TR>
            <TR align='center'>
                <TD class='titulo2' align='center' width='70'>Status</TD>
                <TD class='titulo2' align='center' width='70'>Data Entrada</TD>
                <TD class='titulo2' align='center' width='70'>Data Saída</TD>
                <TD class='titulo2' align='center' width='490'>Justificativa</TD>
            </TR>";

        for ($i = 0; $i < $n; $i++) {
            //Recupera os valores do resultado da consulta
            $valores_linha = pg_fetch_array($res, $i);

            //Transforma os resultados recuperados de array para variáveis
            extract($valores_linha);

            if ($liberado == 'f') {
                if ($cancelada == 'f') {
                    $legenda_status = "em análise";
                    $cor_status = "#FFFF44";
                }
                elseif ($cancelada == 't') {
                    $legenda_status = "reprovada";
                    $cor_status = "#FF7744";
                }
                else {
                    $legenda_status = "";
                    $cor_status = "";
                }
            }
            elseif ($liberado == 't') {
                $legenda_status = "aprovada";
                $cor_status = "#44FF44";
            }
            else {
                $legenda_status = "";
                $cor_status = "";
            }

            echo "
            <TR align='center' style='background-color: $cor_status;'>
                <TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$legenda_status</TD>
                <TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$data</TD>
                <TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$data_saida</TD>
                <TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$justificativa</TD>
            </TR>";
        }

        echo "
        </TABLE>";
    }
}

if($login_fabrica == 74){

    if($fechamentoOS->isOsVinculada($os)){
        $arrOS = $fechamentoOS->getArrOS();
        echo "<br>";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
        echo "<TR class='inicio'>";
        echo "<TD>";
        echo "OS Vinculada:";
        echo "</TD>";
        echo "</TR>";
        echo "<TR class='conteudo'>";
        echo "<TD>";
        echo  "OS ".$arrOS[0] . " Vinculada à OS ".$arrOS[1];
        echo "</TD>";
        echo "</TR>";
        echo "</TABLE>";
    }

}

if($login_fabrica == 30){

    if($hd_classificacao == 47){

        echo "<br />";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
        echo "<TR class='inicio'>";
            echo "<td>".traduz("orientacao.ao.posto")."</td>";
        echo "</TR>";
        echo "<TR>";
            echo "<td class='justificativa' align='left'> {$orientacao_posto} </td>";
        echo "</TR>";
        echo "</TABLE>";


    }

}

if(in_array($login_fabrica, array(50, 153))){
    echo "<TABLE width='700px' align='center' style='font-size:10px'>";
         if($login_fabrica == 50){
            echo "<tr>";
                echo "<TD colspan='4' align='left'>".traduz("legenda").":</TD>";
            echo "</tr>";
            echo "<TR>";
                echo "<TD width=20 bgcolor='#FFC0D0' >&nbsp;</TD>";
                echo "<TD width=130>".traduz("devolucao.obrigatoria")."</TD>";
        }
        if($login_fabrica == 153 and $recall == true){
              echo "<tr>";
                echo "<TD colspan='4' align='left'>".traduz("legenda").":</TD>";
            echo "</tr>";
            echo "<TR>";
                echo "<TD width=20 bgcolor='#98FB98' >&nbsp;</TD>";
                echo "<TD>Recall (".traduz("pecas.de.substituicao.obrigatoria").")</TD>";
        }
        echo "</TR>";
    echo "</TABLE>";
}
?>
<!-- fputti hd-2892486-->
<?php
    if (in_array($login_fabrica, array(50))) {
        $flag_dec = false;
        $sqlOSDec = "SELECT A.consumidor_nome_assinatura, to_char(B.termino_atendimento, 'DD/MM/YYYY')  termino_atendimento
                       FROM tbl_os A
                       JOIN tbl_os_extra B ON B.os=A.os
                      WHERE A.os={$os}";
        $resOSDec = pg_query($con, $sqlOSDec);
        $dataRecebimento = pg_fetch_result($resOSDec, 0, 'consumidor_nome_assinatura');
        $recebidoPor     = pg_fetch_result($resOSDec, 0, 'termino_atendimento');
        if (strlen($dataRecebimento) > 0 && strlen($recebidoPor) > 0) {
            $flag_dec = TRUE;
        }
?>
    <table width='700px' border='0' cellspacing='1' style="margin-top: 15px;" cellpadding='0' align='center' class='Tabela'>
        <tr class='inicio'>
            <td colspan='4' align='center'><?= traduz("declaracao.de.atendimento") ?></TD>
        </tr>
        <tr>
            <td colspan='4' style="font-size: 15px;padding:5px;" class='justificativa' align='left'>
                <p>
                "<?= traduz("declaro.que.houve.o.devido.atendimento.do.posto.autorizado.dentro.do.prazo.legal.sendo.realizado.o.conserto.do.produto.e.apos.a.realizacao.dos.testes.ficou.em.perfeitas.condicoes.de.uso.e.funcionamento.deixando.me.plenamente.satisfeito.a") ?>"
                <br /><br />
                </p>
                <p>
                    <div style="float:left">
                        <?= traduz("produto.entregue.em") ?>:
                        <?php if ($flag_dec) {echo $recebidoPor;} else {?>
                        <input type="text" size="10" name="data_recebimento" id="data_recebimento">
                        <?php }?>
                    </div>
                    <div style="float:right">
                        <?= traduz("recebido.por") ?>:
                        <?php if ($flag_dec) {echo $dataRecebimento;} else {?>
                        <input type="text" size="40" name="recebido_por" id="recebido_por">
                        <?php }?>
                    </div>
                </p>
                <?php if (!$flag_dec) {?>
                <div align="center" style="display:block;margin-top: 50px;"><button type="button" data-os="<?php echo $os;?>" class="btn_grava_declaracao"><?= traduz("gravar") ?></button></div>
                <?php }?>
            </td>
        </tr>
    </table>
<?php
}//fim fputti hd-2892486

# Quando for efetivar HD 3264913 alterar o if abaixo
#if (strlen($obs) > 0) && !in_array($login_fabrica, array(30))) {

if ($login_fabrica == 178){
    $sql_obs_revenda = "
        SELECT tbl_os_revenda.obs_causa 
        FROM tbl_os_campo_extra
        JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_campo_extra.os_revenda AND tbl_os_revenda.fabrica = $login_fabrica
        WHERE tbl_os_campo_extra.os = $os
        AND tbl_os_campo_extra.fabrica = $login_fabrica ";
    $res_obs_revenda = pg_query($con, $sql_obs_revenda);

    if (pg_num_rows($res_obs_revenda) > 0){
        $observacao_sac = pg_fetch_result($res_obs_revenda, 0, "obs_causa");

        if (!empty($observacao_sac)){
        ?>
            <table width='700px' border='0' cellspacing='1' style="margin-top: 15px;" cellpadding='0' align='center' class='Tabela'>
                <tr class='inicio'>
                    <td colspan='4' align='left'><?= traduz("observação callcenter") ?></TD>
                </tr>
                <tr>
                    <td class='justificativa'>
                       <?=$observacao_sac?> 
                    </td>
                </tr>
            </table>
        <?php  
        }
    }
}

if (strlen($obs) > 0) {
    if (in_array($login_fabrica, array(169,170,183))){
                 $sql_script = "
                        SELECT historico_resolucao
                        FROM tbl_os
                        JOIN tbl_hd_chamado_script_falha ON tbl_hd_chamado_script_falha.hd_chamado = tbl_os.hd_chamado
                            AND tbl_hd_chamado_script_falha.fabrica = {$login_fabrica}
                        WHERE tbl_os.os = {$os}
                        AND tbl_os.fabrica = {$login_fabrica}
                        ";
                 $res_script = pg_query($con, $sql_script);
                 if(pg_num_rows($res_script) > 0){
                           $resolution_script = pg_fetch_result($res_script, 0, 'historico_resolucao');
                          if (preg_match("/\<br\/\>|\<br\s\/\>|<br>/", $script_resolution)) {
                                $script_resolution = preg_replace("/\<br\/\>|\<br\s\/\>|<br>/", "\n", $script_resolution);
                                $script_resolution = preg_replace("/\s{2,}/", "\n", $script_resolution);
                         }
                 }
    }

    if($obs == 'null' OR $obs == "NULL"){
        $obs = "";
    }
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
        echo "<TR class='inicio'>";
        echo "<TD>";
        $colspan_obs = "";
        if (in_array($login_fabrica, array(152,180,181,182))) {
            echo traduz("descricao.detalhada.do.problema");
        } else if(in_array($login_fabrica,array(156))){
            echo "<TD>".traduz("laudo.de.analise.tecnica").":&nbsp;</TD>";
        }else if(in_array($login_fabrica,array(163))){
            echo traduz("causa.do.defeito");
        }else if(in_array($login_fabrica,array(171))){
            echo "<td>".traduz("comentario.sobre.a.visita").":</td>";
            $colspan_obs = "colspan='2'";
        }else if($login_fabrica == 175){
            echo "<td>".traduz("servico.executado")."</td>";
            $colspan_obs = "colspan='2'";
        }else if(in_array($login_fabrica, array(184,200))){
            echo "<td>".traduz("Descrição detalhada do defeito")."</td>";
            $colspan_obs = "colspan='2'";
        }else {
            fecho ("observacao",$con,$cook_idioma);
        }
        $obs = wordwrap($obs, 104, '<br/>', true);
        echo "</TD>";
        echo "</TR>";
        echo "<TR>";
        if (in_array($login_fabrica, array(169,170,183))){
            echo "<TD class='justificativa' align='left' $colspan_obs>".nl2br($obs)."<br/><br/>";

            if(strlen(trim($resolution_script)) > 0){
                echo "<b>".traduz("script.de.falha.executado.no.callcenter").": </b><br/>
                <textarea style='font-family: Arial; font-size: 10px; width: 690px;' rows='6' readonly='true'>$resolution_script</textarea>";
            }
            echo "</TD>";
        }else{
            echo "<TD class='justificativa' align='left' $colspan_obs>".nl2br($obs)."</TD>";
        }

        echo "</TR>";
        echo "</TABLE>";
}

if (in_array($login_fabrica, array(11,172))) {
    $aux_sql = "SELECT observacao
                FROM tbl_os
                WHERE os = $os;
    ";
    $aux_res = pg_query($con, $aux_sql);
    $aux_observacao = pg_fetch_result($aux_res, 0, 0);

    if (strlen($aux_observacao) > 0) {
?>
    <br>
    <table width="700px" border="0" cellspacing="1" cellpadding="0" align="center" class="Tabela">
        <tr class="inicio">
            <td><?= traduz("reclamacao.do.cliente") ?></td>
        </tr>
        <tr>
            <td class="justificativa" align="left"><?=$aux_observacao;?></td>
        </tr>
    </table>
<?php }
}

if ($login_fabrica == 30) {
    echo "<br />";
    echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
    echo "<TR class='inicio'>";
    echo "<TD colspan='3'>";
    echo traduz("agendamento.de.visita");
    echo "</TD>";
    echo "</TR>";
    echo "<TR>";
    echo "<TD class='titulo2'>";
    echo "Ordem";
    echo "</TD>";
    echo "<TD class='titulo2'>";
    echo traduz("data");
    echo "</TD>";
    echo "<TD class='titulo2'>";
    echo traduz("justificativa");
    echo "</TD>";
    echo "<TD class='titulo2'>";
    echo traduz("visita.realizada");
    echo "</TD>";
    echo "</TR>";
    $sql = "SELECT
                tbl_os_visita.os_visita,
                tbl_os_visita.data,
                tbl_os_visita.justificativa_valor_adicional,
                tbl_justificativa.descricao AS justificativa,
                tbl_os_visita.hora_chegada_cliente
            FROM tbl_os_visita
                JOIN tbl_justificativa USING(justificativa)
            WHERE tbl_os_visita.os = {$os}
            ORDER BY data; ";

    $res = pg_query($con, $sql);
    for ($i = 0; $i < pg_num_rows($res); $i++) {
        $contador = $i + 1;
        $checked = (!empty(pg_fetch_result($res, $i, "hora_chegada_cliente"))) ? 'checked' : '';
        $data = substr(pg_fetch_result($res, $i, "data"), 0, 10);
        $justificativa_valor_adicional = pg_fetch_result($res, $i, 'justificativa_valor_adicional');

        if(strlen($justificativa_valor_adicional)>0){
            $checkedNao = ' checked ';
        }else{
            $checkedNao = '';
        }

        if($i < (pg_num_rows($res) - 1) ){
            $disabled = "  disabled ";
        }else{
            $disabled = "  ";
        }

        $display = (date('Y-m-d') >= date('Y-m-d', strtotime("+1 days",strtotime($data)))) ? "<INPUT TYPE='radio' NAME='visita_realizada_{$contador}' data-os-visita=". pg_fetch_result($res, $i, "os_visita") ." value='nao' $checkedNao $disabled >Não
        <INPUT TYPE='radio' NAME='visita_realizada_{$contador}' data-os-visita=". pg_fetch_result($res, $i, "os_visita") ." value='sim' {$checked}  $disabled >Sim" : '';

        $data = explode('-', $data);
        $data = $data[2]."/".$data[1]."/".$data[0];

        echo "<TR class='conteudo'>";
        echo "<TD align='center'> ".str_pad($contador,5,'0',STR_PAD_LEFT)."</TD>";
        echo "<TD align='center'> {$data}</TD>";
        echo "<TD align='left'> ".pg_fetch_result($res, $i, "justificativa")."</TD>";
        echo "<TD align='center'>{$display}</TD>";
        echo "</TR>";
    }
    echo "</TABLE>";
}

if($login_fabrica == 156 && $posto_interno == true){
    if (isset($obs_adicionais)) {

        echo "<br />";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
        echo "<TR class='inicio'>";
        echo "<TD>";
        echo traduz("observacoes.administrativas");
        echo "</TD>";
        echo "</TR>";
        echo "<TR>";
        echo "<TD class='justificativa' align='left'>".nl2br($obs_adicionais)."</TD>";
        echo "</TR>";
        echo "</TABLE>";

    }

}

if($login_fabrica == 173){
    if (isset($obs_adicionais)) {

        echo "<br />";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
        echo "<TR class='inicio'>";
        echo "<TD>";
        echo traduz("posicao.componentes");
        echo "</TD>";
        echo "</TR>";
        echo "<TR>";
        echo "<TD class='justificativa' align='left'>".nl2br($obs_adicionais)."</TD>";
        echo "</TR>";
        echo "</TABLE>";

    }

}
if ($login_fabrica == 51 and $exibe_legenda > 0){
    echo "<BR>\n";
    echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'>\n";
    echo "<TR style='line-height: 12px'>\n";
    echo "<TD width='5' bgcolor='#FFC0D0'>&nbsp;</TD>\n";
    echo "<TD style='padding-left: 10px; font-size: 14px;'><strong>".traduz("pecas.para.vistoria")."</strong></TD>\n";
    echo "</TR></TABLE>\n";
}

#Mostra os valores de custos adicionais existentes na OS
if($inf_valores_adicionais OR $fabrica_usa_valor_adicional){
    include "valores_adicionais_inc.php";
}

if (isset($os_press_mostra_avulso)) {
    include "valores_avulsos_inc.php";
}

if (in_array($login_fabrica,array(50,88))) {
    $sql = "SELECT descricao as pergunta, txt_resposta as resposta from tbl_pergunta join tbl_resposta using(pergunta) where os = $os";
    $qry = pg_query($con, $sql);

    if (pg_num_rows($qry) > 0) {
        echo '<br/>';
        echo "<table width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";

        while ($fetch = pg_fetch_assoc($qry)) {
            echo '<tr><td class="conteudo">' . $fetch['pergunta'] . ': ' . utf8_decode($fetch['resposta']) . '</td></tr>';
        }

        echo '</table>';
    }
}

if ($login_fabrica == 156) {
    $sql = "SELECT descricao
        FROM tbl_status_os
        JOIN tbl_os ON tbl_os.status_os_ultimo = tbl_status_os.status_os
        WHERE os = $os";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        echo '<br><table class="Tabela" width="700px" cellspacing="1">
                <tr>
                   <td class="inicio">'.traduz("status.da.os").'</td>
                </tr>
                <tr>
                    <td class="justificativa">' . pg_fetch_result($res, 0, 'descricao') . '</td>
                </tr>
              </table>';
    }
}

# adicionado por Fabio - 26/03/2007 - hd chamado 1392
# HD 14830 - HBTech
# HD 13618 - NKS
# HD 12657 - Dynacom
#HD 283928- Nova

if ($historico_intervencao) {
            /* HD 233857 Samuel alterou para mostrar todas as interações */
    $troca = "";
    if($login_fabrica == 1){
        $sqlTroca = "
                    SELECT  tbl_os_troca.status_os
                    FROM    tbl_os_troca
                    WHERE   os = $os
        ";
        $resTroca = pg_query($con,$sqlTroca);
        $status_troca = pg_fetch_result($resTroca,0,status_os);

        if($status_troca == 19 || $status_troca == 13){
            $troca = 1;
        }
    }else{
        $troca = 1;
    }

    if($troca == 1 and !isset($auditoria_unica) and !in_array($login_fabrica, array(1,104,114,123,131)) AND !isset($union_auditoria_os) ){
        $britania_status_os_not = '';
        if(($login_fabrica == 3) or ($login_fabrica == 50 and false === $mostra_reincidencia)){
            $britania_status_os_not = " and status_os <> 67 ";
        }

        $sql_status = " SELECT  status_os,
                                observacao,
                                to_char(data, 'DD/MM/YYYY')   as data_status,
                                tbl_os_status.admin,
                                tbl_os_status.extrato,
                                tbl_admin.login,
                                tbl_extrato.protocolo,
                                tbl_status_os.descricao
                        FROM    tbl_os_status
                        JOIN    tbl_status_os using(status_os)
                LEFT JOIN    tbl_admin ON tbl_admin.admin = tbl_os_status.admin
                LEFT JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_status.extrato
                        WHERE   os=$os $britania_status_os_not
                ORDER BY      data DESC
        ";
        $res_status = pg_query($con,$sql_status);
        $resultado = pg_num_rows($res_status);
        if ($resultado>0){
            echo "<BR>\n";
            echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>\n";
            echo "<TR>\n";
            if ($login_fabrica==25){
                echo "<TD colspan='7' class='inicio'>&nbsp;".traduz("justificativa.do.pedido.de.peca",$con,$cook_idioma)."</TD>\n";
            } else {
                echo "<TD colspan='7' class='inicio' style='text-align: center;'>&nbsp;".traduz("historico.de.intervencao",$con,$cook_idioma)."</TD>\n";
            }
            echo "</TR>\n";

            /* HD 233857 Samuel alterou para mostrar todas as interações */
            echo "<TR>\n";
            echo "<TD class='titulo2'>".traduz("data")."</TD>\n";
            echo "<TD class='titulo2'>".traduz("tipo/status")."</TD>\n";
            echo "<TD class='titulo2'>".traduz("justificativa")."</TD>\n";
            echo "<TD class='titulo2'>Admin</TD>\n";
            echo "</TR>\n";
            for ($j=0;$j<$resultado;$j++){
                $extrato_intervencao =  trim(pg_fetch_result($res_status,$j,'extrato'));
                $status_os          = trim(pg_fetch_result($res_status,$j,'status_os'));
                $status_observacao  = trim(pg_fetch_result($res_status,$j,'observacao'));
                $status_data        = trim(pg_fetch_result($res_status,$j,'data_status'));
                $status_admin       = trim(pg_fetch_result($res_status,$j,'admin'));
                $descricao          = trim(pg_fetch_result($res_status,$j,'descricao'));
                $login              = trim(pg_fetch_result($res_status,$j,'login'));
                $protocolo_intervencao  = trim(pg_fetch_result($res_status,$j,protocolo));
                $xobs_status        = "";

                if($login_fabrica == 1){
                    if(strlen($protocolo_intervencao) > 0){
                        $status_observacao = "Ação tomada no Protocolo $protocolo_intervencao: ".$status_observacao;
                    }
                }else{
                    if(strlen($extrato_intervencao) > 0){
                        $status_observacao = "Ação tomada no Extrato $extrato_intervencao: ".$status_observacao;
                    }
                }

                if (($status_os==72 OR  $status_os==64) AND strlen($status_observacao)>0 and !in_array($login_fabrica, array(30,114,141,142,144,145))){
                    $xobs_status = $status_observacao;
                    $xstatus_observacao = strstr($status_observacao,"Justificativa:");
                    $status_observacao = str_replace("Justificativa:","",$xstatus_observacao);
                }

                $status_observacao = trim($status_observacao);


                if (strlen($status_observacao)==0 AND $status_os==73) $status_observacao="Autorizado";
                if (strlen($status_observacao)==0 AND $status_os==72) $status_observacao="-";

                if($login_fabrica == 6){
                    $status_observacao  = trim(pg_fetch_result($res_status,$j,'observacao'));
                }

                if ( in_array($login_fabrica, array(11,172)) AND strlen($status_admin)>0){
                    $status_observacao = trim(pg_fetch_result($res_status,$j,'observacao'));
                }

                if($login_fabrica == 131){
                    $status_observacao  = trim(pg_fetch_result($res_status,$j,'observacao'));
                }

                if($login_fabrica == 1 AND $status_os == 70){
                    continue;
                }

                $xobs_status = substr($xobs_status,0,13);

                echo "<TR>\n";
				echo "<TD  class='justificativa' width='100px'  align='center'><b>$status_data</b></TD>\n";
				if($login_fabrica == 3 and $cancelou_peca == 'sim' and !empty($login) and strpos($status_observacao,'Autorizado Pela') === false) {
					echo "<TD class='justificativa' width='140px'  align='left' nowrap>Peça Cancelada pela Fábrica</TD>\n";
				}elseif($login_fabrica == 3 AND ($descricao == "Liberada Inspetoria Técn./SAP" OR $descricao == "Liberado Intervenção Fábrica") && $xobs_status != "Justificativa") {
					echo "<TD class='justificativa' width='140px'  align='left' nowrap>Peça Autorizada pela Fábrica</TD>\n";
				} elseif($login_fabrica == 3 AND ($descricao == "Bloqueio Inspetoria Técn./SAP" || $xobs_status == "Justificativa")) {
					echo "<TD class='justificativa' width='140px'  align='left' nowrap>Peça Cancelada pela Fábrica</TD>\n";
				} else {
					echo "<TD class='justificativa' width='140px'  align='left' nowrap>$descricao</TD>\n";
				}

                if ( strlen($login) == 0){
                    $login = "Automático";
                }

                if($login_fabrica == 149 && $status_os == 64){
                    $status_observacao = "Liberado peça critica ".(empty($status_observacao) ? "": " - ".$status_observacao);
                }

                echo "<TD  class='justificativa' width='450px' align='left' >&nbsp;$status_observacao</TD>\n";
                echo "<TD  class='justificativa' align='left' >$login</TD>\n";
                echo "</TR>\n";
            }
            echo "</TABLE>\n";
        }
    }

    //Mostrar Histórico Pressure
    if($troca == 1 AND $login_fabrica == 131 AND !isset($auditoria_unica)){

        $sql_status = "SELECT  DISTINCT status_os,
                                observacao,
                                os_status,
                                to_char(data, 'DD/MM/YYYY')   as data_status,
                                tbl_os_status.admin,
                                tbl_admin.login,
                                tbl_status_os.descricao
                            FROM    tbl_os_status
                                JOIN    tbl_status_os using(status_os)
                                LEFT JOIN    tbl_admin USING(admin)
                            WHERE   os=$os
                            GROUP BY  status_os,
                                observacao,
                                data_status,
                                tbl_os_status.admin,
                                os_status,
                                tbl_admin.login,
                                tbl_status_os.descricao
                            ORDER BY os_status DESC;";

        $res_status = pg_query($con,$sql_status);
        $resultado = pg_num_rows($res_status);
        if ($resultado>0){
            echo "<BR>\n";
            echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>\n";
            echo "<TR>\n";
            if ($login_fabrica==25){
                echo "<TD colspan='7' class='inicio'>&nbsp;".traduz("justificativa.do.pedido.de.peca",$con,$cook_idioma)."</TD>\n";
            } else {
                echo "<TD colspan='7' class='inicio' style='text-align: center;'>&nbsp;".traduz("historico.de.intervencao",$con,$cook_idioma)."</TD>\n";
            }
            echo "</TR>\n";

            /* HD 233857 Samuel alterou para mostrar todas as interações */
            echo "<TR>\n";
            echo "<TD class='titulo2'>".traduz("data")."</TD>\n";
            echo "<TD class='titulo2'>".traduz("tipo/status")."</TD>\n";
            echo "<TD class='titulo2'>".traduz("justificativa")."</TD>\n";
            echo "<TD class='titulo2'>Admin</TD>\n";
            echo "</TR>\n";
            for ($j=0;$j<$resultado;$j++){
                $status_os          = trim(pg_fetch_result($res_status,$j,'status_os'));
                $status_observacao  = trim(pg_fetch_result($res_status,$j,'observacao'));
                $status_data        = trim(pg_fetch_result($res_status,$j,'data_status'));
                $status_admin       = trim(pg_fetch_result($res_status,$j,'admin'));
                $descricao          = trim(pg_fetch_result($res_status,$j,'descricao'));
                $login              = trim(pg_fetch_result($res_status,$j,'login'));

                if (($status_os==72 OR  $status_os==64) AND strlen($status_observacao)>0){
                    $xstatus_observacao = strstr($status_observacao,"Justificativa:");
                    $status_observacao = str_replace("Justificativa:","",$xstatus_observacao);
                }

                if (strlen($status_observacao)==0 AND $status_os==73) $status_observacao="Autorizado";
                if (strlen($status_observacao)==0 AND $status_os==72) $status_observacao="-";

                echo "<TR>\n";
                echo "<TD  class='justificativa' width='100px'  align='center'><b>$status_data</b></TD>\n";
                echo "<TD  class='justificativa' width='140px'  align='left' nowrap>$descricao<b></b></TD>\n";

                if ( strlen($login) == 0){
                    $login = "Automático";
                }
                echo "<TD  class='justificativa' width='450px' align='left' >&nbsp;$status_observacao</TD>\n";
                echo "<TD  class='justificativa' align='left' >$login</TD>\n";
                echo "</TR>\n";
            }
            echo "</TABLE>\n";
        }
    }


    if($troca == 1 AND $login_fabrica == 114){
        $sql_status = " SELECT  status_os,
                                observacao,
                                to_char(data, 'DD/MM/YYYY')   as data_status,
                                tbl_os_status.admin,
                                tbl_admin.login,
                                tbl_status_os.descricao
                        FROM    tbl_os_status
                        JOIN    tbl_status_os using(status_os)
                LEFT JOIN    tbl_admin USING(admin)
                        WHERE   os=$os
                        AND status_os <> 62
                ORDER BY      data DESC
        ";
        $res_status = pg_query($con,$sql_status);
        $resultado = pg_num_rows($res_status);
        if ($resultado>0){
            echo "<BR>\n";
            echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>\n";
            echo "<TR>\n";
            echo "<TD colspan='7' class='inicio' style='text-align: center;'>&nbsp;".traduz("historico.de.intervencao",$con,$cook_idioma)."</TD>\n";
            echo "</TR>\n";

            /* HD 233857 Samuel alterou para mostrar todas as interações */
            echo "<TR>\n";
            echo "<TD class='titulo2'>".traduz("data")."</TD>\n";
            echo "<TD class='titulo2'>".traduz("tipo/status")."</TD>\n";
            echo "<TD class='titulo2'>".traduz("justificativa")."</TD>\n";
            echo "<TD class='titulo2'>Admin</TD>\n";
            echo "</TR>\n";
            for ($j=0;$j<$resultado;$j++){
                $status_os          = trim(pg_fetch_result($res_status,$j,'status_os'));
                $status_observacao  = trim(pg_fetch_result($res_status,$j,'observacao'));
                $status_data        = trim(pg_fetch_result($res_status,$j,'data_status'));
                $status_admin       = trim(pg_fetch_result($res_status,$j,'admin'));
                $descricao          = trim(pg_fetch_result($res_status,$j,'descricao'));
                $login              = trim(pg_fetch_result($res_status,$j,'login'));

                if (($status_os==72 OR  $status_os==64) AND strlen($status_observacao)>0 and !in_array($login_fabrica, array(114,141,142,144,145))){
                    $xstatus_observacao = strstr($status_observacao,"Justificativa:");
                    $status_observacao = str_replace("Justificativa:","",$xstatus_observacao);
                }

                $status_observacao = trim($status_observacao);


                if (strlen($status_observacao)==0 AND $status_os==73) $status_observacao="Autorizado";
                if (strlen($status_observacao)==0 AND $status_os==72) $status_observacao="-";

                if($login_fabrica == 6){
                    $status_observacao  = trim(pg_fetch_result($res_status,$j,'observacao'));
                }

                if ( in_array($login_fabrica, array(11,172)) AND strlen($status_admin)>0){
                    $status_observacao = trim(pg_fetch_result($res_status,$j,'observacao'));
                }

                if($login_fabrica == 131){
                    $status_observacao  = trim(pg_fetch_result($res_status,$j,'observacao'));
                }

                if($login_fabrica == 145){
                    $descricao = utf8_decode($descricao);
                }

                echo "<TR>\n";
                echo "<TD  class='justificativa' width='100px'  align='center'><b>$status_data</b></TD>\n";
                echo "<TD  class='justificativa' width='140px'  align='left' nowrap>$descricao<b></b></TD>\n";

                if ( strlen($login) == 0){
                    $login = "Automático";
                }
                echo "<TD  class='justificativa' width='450px' align='left' >&nbsp;$status_observacao</TD>\n";
                echo "<TD  class='justificativa' align='left' >$login</TD>\n";
                echo "</TR>\n";
            }
            echo "</TABLE>\n";
        }
    }
}

// Implantação Imbera - Adição de laudo técnico
if ($login_fabrica == 158) {
    $sql = "SELECT * FROM tbl_laudo_tecnico_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $laudos = pg_fetch_all($res);
        foreach ($laudos as $laudo) {
            $procedimentos = array();
            $procedimentos = json_decode($laudo['observacao'], true); ?>
            <br />

            <table width="700px" border="0" cellspacing="1" cellpadding="0" align="center" class="Tabela">
                <thead>
                    <tr class="inicio">
                        <th><?= strtoupper(Utf8_ansi($laudo['titulo'])); ?>:</th>
                    </tr>
                </thead>
                <tbody>
                <? foreach($procedimentos as $pr) { ?>
                    <tr>
                        <table width="700px" border="0" cellspacing="1" cellpadding="0" align="center" class="Tabela">
                            <tr>
                                <td colspan="<?= count($pr['campos']); ?>" class="titulo2">
                                    <?= Utf8_ansi($pr['nome']); ?>
                                </td>
                            </tr>
                            <? foreach($pr['campos'] as $campos_laudo) { ?>
                                <tr>
                                    <td class="titulo">
                                        <?= Utf8_ansi($campos_laudo['nome']); ?>
                                    </td>
                                    <td class="conteudo">
                                        <?= (strpos(strtoupper($campos_laudo['nome']), 'DATA') !== false) ? date('d/m/Y H:i:s', $campos_laudo['valor'] / 1000) : Utf8_ansi($campos_laudo['valor']); ?>
                                    </td>
                                </tr>
                            <? } ?>
                        </table>
                    </tr>
                <? } ?>
                </tbody>
            </table>
        <? } ?>
    <? }
}

if ($login_fabrica == 116) {
    $sql = "SELECT tbl_tipo_atendimento.entrega_tecnica FROM tbl_os JOIN tbL_tipo_atendimento USING(tipo_atendimento) WHERE tbl_os.fabrica = $login_fabrica AND os = $os";
    $res = pg_query($con, $sql);

    if (pg_fetch_result($res, 0, "entrega_tecnica") == "t") {
        $sql = "SELECT os FROM tbl_laudo_tecnico_os WHERE fabrica = $login_fabrica AND os = $os";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) == 0) {
            ?>
            <br />
            <table border="0" style="margin: auto;">
                <tr>
                    <td>
                        <a href="checklist_entrega_tecnica.php?os=<?=$os?>" target="_blank"><img src='logos/toyama4.png' style='width: 80px;' /></a>
                    </td>
                    <td valign="center" >
                        <a href="checklist_entrega_tecnica.php?os=<?=$os?>" target="_blank"><button type='button' style="cursor: pointer;">Preencher Check List</button></a>
                    </td>
                </tr>
            </table>
        <?php
        } else {
        ?>
            <br />
            <table border="0" style="margin: auto;">
                <tr>
                    <td>
                        <a href="checklist_entrega_tecnica.php?imprimir=<?=$os?>" target="_blank"><img src='logos/toyama4.png' style='width: 80px;' /></a>
                    </td>
                    <td valign="center" >
                        <a href="checklist_entrega_tecnica.php?imprimir=<?=$os?>" target="_blank"><button type='button' style="cursor: pointer;">Imprimir o Check List</button></a>
                    </td>
                </tr>
            </table>
        <?php
        }
    }
}

if($login_fabrica == 114){
    $sql = "SELECT os FROM tbl_laudo_tecnico_os WHERE fabrica = $login_fabrica AND os = $os";
    $res = pg_query($con, $sql);

        $sql_linha = "SELECT tbl_produto.linha,to_char(data_digitacao,'YYYY-MM-DD') as data_digitacao FROM tbl_produto JOIN tbl_os ON tbl_os.produto = tbl_produto.produto AND tbl_os.fabrica = $login_fabrica WHERE tbl_os.os = $os AND tbl_produto.fabrica_i = $login_fabrica";
    $res_linha = pg_query($con, $sql_linha);

    $linha = pg_fetch_result($res_linha,0,"linha");
    $data_digitacao = pg_fetch_result($res_linha,0,"data_digitacao");

    if ($linha == 691 and $data_digitacao > date('2014-08-13')) {
    if (pg_num_rows($res) == 0) {
?>
        <br />
        <table border="0" style="margin: auto;">
            <tr>
                <td>
                    <a href="checklist_os_item.php?os=<?=$os?>" target="_blank"><img src='logos/cobimex_admin2.jpg' style='width: 120px;' /></a>
                </td>
                <td valign="center" >
                    <a href="checklist_os_item.php?os=<?=$os?>" target="_blank"><button type='button' style="cursor: pointer;">Preencher Check List</button></a>
                </td>
            </tr>
        </table>
<?php
    } else {
?>
        <br />
        <table border="0" style="margin: auto;">
            <tr>
                <td>
                    <a href="checklist_os_item.php?imprimir=<?=$os?>" target="_blank"><img src='logos/cobimex_admin2.jpg' style='width: 120px;' /></a>
                </td>
                <td valign="center" >
                    <a href="checklist_os_item.php?imprimir=<?=$os?>" target="_blank"><button type='button' style="cursor: pointer;">Imprimir o Check List</button></a>
                </td>
            </tr>
        </table>
<?php
    }
    }
}
if (in_array($login_fabrica, array(129, 145))) {
    $sql = "SELECT titulo, observacao
            FROM tbl_laudo_tecnico_os
            WHERE fabrica = $login_fabrica
            AND os = $os
            ORDER BY ordem ASC";
    $res = pg_query($con, $sql);

    $rows = pg_num_rows($res);

    unset($laudo_tecnico);

    if($login_fabrica == 145 && $rows > 0){

        $titulo = pg_fetch_result($res, 0, "titulo");
        $obs    = pg_fetch_result($res, 0, "observacao");

        $obs = json_decode($obs);

        $observacao = utf8_decode($obs->observacao);
        $conclusao= utf8_decode($obs->conclusao);

        ?>

        <br />

        <table class="Tabela" width="700px" style="table-layout: fixed;">
            <tr class="inicio">
                <td style="text-align: center;" colspan="2"><?php echo $titulo; ?></td>
            </tr>
            <tr class="titulo2">
                <td><?= traduz("observacao") ?></td>
                <td><?= traduz("conclusao") ?></td>
            </tr>
            <tr class="conteudo">
                <td><?php echo $observacao; ?></td>
                <td><?php echo $conclusao; ?></td>
            </tr>
            <tr class="titulo2">
                <td colspan="2" ><?= traduz("anexos") ?></td>
            </tr>
            <tr class="conteudo">
                <td style="text-align: center; padding: 5px;" colspan="2">
                    <?php
                        $s3 = new AmazonTC("inspecao", $login_fabrica);
                        $laudo_tecnico = $s3->getObjectList($os."-laudo-tecnico-");

                        if (count($laudo_tecnico)) {
                            foreach ($laudo_tecnico as $key => $value) {
                                $arquivo = basename($value);
                                $arquivo = $s3->getLink($arquivo);

                                $thumb = basename($value);
                                $thumb = $s3->getLink("thumb_".$thumb);

                                echo "<a href='{$arquivo}' target='_blank'><img src='{$thumb}' style='max-width: 150px; max-height: 150px;_height:150px;*height:150px;' /></a>&nbsp;&nbsp;&nbsp;";
                            }
                        } else {
                            echo "Sem laudo técnico anexado";
                        }
                    ?>
                </td>
            </tr>
        </table>
        <?php

    }else{
        if ($rows > 0) {
        ?>
            <style>
            #table_laudo_tecnico {
                width: 700px;
                margin: 0 auto;
                border-collapse: collapse;
            }

            #table_laudo_tecnico .titulo {
                text-align: right;
                max-width: 90px;
            }

            </style>
            <br />
            <table id="table_laudo_tecnico" class="Tabela" border="1" >
                <tr>
                    <td class="inicio" style="text-align: center;" colspan="2">
                        <?= traduz("laudo.tecnico") ?>
                    </td>
                </tr>
                <?php
                for ($i = 0; $i < $rows; $i++) {
                    $laudo_tecnico[pg_fetch_result($res, $i, "titulo")] = pg_fetch_result($res, $i, "observacao");
                }
                ?>
                <tr>
                    <td class="titulo">
                        Nº DA ASSISTÊNCIA
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_posto_numero']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("instalado.em") ?>
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_data_instalado']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("nome.da.instaladora") ?>
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_instaladora_nome']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("agua.utilizada") ?>
                    </td>
                    <td class="conteudo">
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_agua_utilizada"]) {
                            case 'direto_da_rua':
                                echo "DIRETO DA RUA/REDE DE ABASTECIMENTO";
                                break;

                            case 'caixa':
                                echo "CAIXA/REDE DE ABASTECIMENTO";
                                break;

                            case 'poco':
                                echo "POÇO";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("pressurizador") ?>
                    </td>
                    <td class="conteudo">
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_pressurizador"]) {
                            case 'true':
                                echo "SIM";
                                break;

                            case 'false':
                                echo "NÃO";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        TENSÃO
                    </td>
                    <td class="conteudo">
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_tensao"]) {
                            case '110v':
                                echo "110V";
                                break;

                            case '220v':
                                echo "220V";
                                break;

                            case 'pilha':
                                echo "PILHA";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("tipo.de.gas") ?>
                    </td>
                    <td class="conteudo">
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_tipo_gas"]) {
                            case 'gn':
                                echo "GN";
                                break;

                            case 'glp':
                                switch ($laudo_tecnico["laudo_tecnico_gas_glp"]) {
                                    case 'estagio_unico':
                                        $estagio = "ESTÁGIO ÚNICO";
                                        break;

                                    case 'dois_estagios':
                                        $estagio = "DOIS ESTÁGIOS";
                                        break;
                                }

                                echo "GLP, $estagio";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                       <?= traduz("pressao.de.gas") ?>
                    </td>
                    <td class="conteudo">
                        DINÂMICA: <?=$laudo_tecnico['laudo_tecnico_pressao_gas_dinamica']?> (consumo máx.)<br />
                        ESTÁTICA: <?=$laudo_tecnico['laudo_tecnico_pressao_gas_estatica']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("pressao.da.agua") ?>
                    </td>
                    <td class="conteudo">
                        DINÂMICA: <?=$laudo_tecnico['laudo_tecnico_pressao_agua_dinamica']?> (consumo máx.)<br />
                        ESTÁTICA: <?=$laudo_tecnico['laudo_tecnico_pressao_agua_estatica']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("diametro.do.duto") ?>
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_diametro_duto']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("comprimento.total.do.duto") ?>
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_comprimento_total_duto']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("quant.de.curvas") ?>
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_quantidade_curvas']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("caracteristicas.do.local.de.instalacao") ?>
                    </td>
                    <td class="conteudo">
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_caracteristica_local_instalacao"]) {
                            case 'externo':
                                echo "EXTERNO";
                                break;

                            case 'interno':
                                echo "INTERNO";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("se.interno.qual.o.ambiente") ?>
                    </td>
                    <td class="conteudo" nowrap >
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_local_instalacao_interno_ambiente"]) {
                            case 'area_servico':
                                echo "ÁREA DE SERVIÇO";
                                break;

                            case 'outro':
                                echo "OUTRO: {$laudo_tecnico['laudo_tecnico_local_instalacao_interno_ambiente_outro']}";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        INSTALAÇÃO DE ACORDO COM O NBR 13.103
                    </td>
                    <td class="conteudo">
                        <?php
                        switch ($laudo_tecnico["laudo_tecnico_instalacao_nbr"]) {
                            case 'true':
                                echo "SIM";
                                break;

                            case 'false':
                                echo "NÃO";
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("problema.diagnosticado") ?>
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_problema_diagnosticado']?>
                    </td>
                </tr>
                <tr>
                    <td class="titulo">
                        <?= traduz("providencias.adotadas") ?>
                    </td>
                    <td class="conteudo">
                        <?=$laudo_tecnico['laudo_tecnico_providencias_adotadas']?>
                    </td>
                </tr>
            </table>
        <?php
        }

    }

}

if ($login_fabrica == 86) {
    $sql = "SELECT laudo_tecnico FROM tbl_os_extra WHERE os = $os";
    $res = pg_query($con, $sql);
    $laudo = pg_fetch_result($res, 0, "laudo_tecnico");

    if (!empty($laudo)) {
        $sql = "SELECT tbl_marca.logo
                FROM tbl_os
                JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                AND tbl_produto.fabrica_i = $login_fabrica
                JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca
                AND tbl_marca.fabrica = $login_fabrica
                WHERE tbl_os.os = {$_GET['os']}";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){
            $logo = pg_fetch_result($res, 0, 'logo');
            $logo = (empty($logo)) ? "logos/famastil.png" : $logo;
        }
        ?>
            <br />
            <table border="0" style="margin: auto;">
                <tr>
                    <td>
                        <a href="laudo_tecnico_famastil.php?imprimir=ok&os=<?=$os?>" target="_blank"><img src='<?=$logo?>' style='width: 80px;' /></a>
                    </td>
                    <td valign="center" >
                        <a href="laudo_tecnico_famastil.php?imprimir=ok&os=<?=$os?>" target="_blank"><button type='button' style="cursor: pointer;"><?= traduz("imprimir.o.laudo.tenico") ?></button></a>
                    </td>
                </tr>
            </table>
        <?php

    }
}

if ($login_fabrica==3) {
        $sql_status = "SELECT  tbl_os.os                            ,
                                (SELECT tbl_status_os.descricao FROM tbl_status_os where tbl_status_os.status_os = tbl_os_status.status_os) AS status_os ,
                                tbl_os_status.observacao              ,
                                to_char(tbl_os_status.data, 'dd/mm/yyy') AS data
                                FROM tbl_os
                        LEFT JOIN tbl_os_status USING(os)
                        WHERE tbl_os.os    = $os
                        AND   tbl_os.posto = $login_posto
                        AND   tbl_os_status.status_os IN(
                                SELECT status_os
                                FROM tbl_os_status
                                WHERE tbl_os.os = tbl_os_status.os
                                AND status_os IN (98,99,101) ORDER BY tbl_os_status.data DESC
                        )";
    $res_km = pg_query($con,$sql_status);

    if(pg_num_rows($res_km)>0){
        echo "<BR>\n";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
        echo "<TR>\n";
        echo "<TD colspan='7' class='inicio'>&nbsp;".traduz("historico.atendimento.domicilio",$con,$cook_idioma)."</TD>\n";
        echo "</TR>\n";

        for($x=0; $x<pg_num_rows($res_km); $x++){
            $status_os    = pg_fetch_result($res_km, $x, status_os);
            $observacao   = pg_fetch_result($res_km, $x, observacao);
            $data         = pg_fetch_result($res_km, $x, data);

            echo "<tr>";
                echo "<td class='justificativa'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$status_os</font></td>";
                echo "<td class='justificativa'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$observacao</font></td>";
                echo "<td class='justificativa' align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$data</font></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
?>
<?

# adicionado por Fabio
# HD 13940 - Bosch
if ($login_fabrica==20) {

    if($tipo_atendimento == 13 or $tipo_atendimento == 66){
        //HD-3200578
            $obs_motivo_ordem = array();
            if($motivo_ordem == 'PROCON (XLR)'){
                $obs_motivo_ordem[] = 'Protocolo:';
                $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['protocolo']);
            }
            if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
                $obs_motivo_ordem[] = 'CI ou Solicitante:';
                $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['ci_solicitante']);
            }

            if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
                $obs_motivo_ordem[] = "Descrição Peças:";
                if(strlen(trim($os_campos_adicionais['descricao_peca_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['descricao_peca_1']);
                }
                if(strlen(trim($os_campos_adicionais['descricao_peca_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['descricao_peca_2']);
                }
                if(strlen(trim($os_campos_adicionais['descricao_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['descricao_peca_3']);
                }
            }

            if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
                if(strlen(trim($os_campos_adicionais['codigo_peca_1'])) > 0 OR strlen(trim(utf8_decode($os_campos_adicionais['codigo_peca_2']))) > 0 OR strlen(trim($os_campos_adicionais['codigo_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= 'Código Peças:';
                }
                if(strlen(trim($os_campos_adicionais['codigo_peca_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['codigo_peca_1']);
                }
                if(strlen(trim($os_campos_adicionais['codigo_peca_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['codigo_peca_2']);
                }
                if(strlen(trim($os_campos_adicionais['codigo_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['codigo_peca_3']);
                }

                if(strlen(trim($os_campos_adicionais['numero_pedido_1'])) > 0 OR strlen(trim($os_campos_adicionais['numero_pedido_2'])) > 0 OR strlen(trim($os_campos_adicionais['numero_pedido_3'])) > 0){
                    $obs_motivo_ordem[] .= 'Número Pedidos:';
                }
                if(strlen(trim($os_campos_adicionais['numero_pedido_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['numero_pedido_1']);
                }
                if(strlen(trim($os_campos_adicionais['numero_pedido_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['numero_pedido_2']);
                }
                if(strlen(trim($os_campos_adicionais['numero_pedido_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['numero_pedido_3']);
                }
            }

            if($motivo_ordem == "Linha de Medicao (XSD)"){
                $obs_motivo_ordem[] .= 'Linha de Medição(XSD):';
                $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['linha_medicao']);
            }
            if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
                $obs_motivo_ordem[] .= 'Pedido não fornecido - Valor Mínimo(XSS):';
                $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['pedido_nao_fornecido']);
            }

            if($motivo_ordem == 'Contato SAC (XLR)'){
                $obs_motivo_ordem[] .= 'N° do Chamado:';
                $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['contato_sac']);
            }

            if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem == 'Defeito reincidente (XQR)'){
                $obs_motivo_ordem[] .= "Detalhes:";
                $obs_motivo_ordem[] .= utf8_decode($os_campos_adicionais['detalhe']);
            }
            echo"<table width='700px' class='Tabela' border='0' cellspacing='1' cellpadding='2' align='center'>";
                echo "<tr class='inicio'>";
                    echo "<TD><b>Observações Motivo Ordem</b></TD>";
                echo "</tr >";
                echo "<tr  class='conteudo'><td>".implode('<br/>', $obs_motivo_ordem)."</td></tr>";
            echo "</table>";
        // FIM HD-3200578 //
    }
    $sql_status = "SELECT
                    tbl_os_status.status_os                                    ,
                    tbl_os_status.observacao                                   ,
                    to_char(tbl_os_status.data, 'DD/MM/YYYY')   as data_status ,
                    tbl_os_status.admin                                        ,
                    tbl_status_os.descricao                                    ,
                    tbl_admin.nome_completo AS nome                            ,
                    tbl_admin.email                                            ,
                    tbl_promotor_treinamento.nome  AS nome_promotor            ,
                    tbl_promotor_treinamento.email AS email_promotor
                FROM tbl_os
                JOIN tbl_os_status USING(os)
                LEFT JOIN tbl_status_os USING(status_os)
                LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin
                LEFT JOIN tbl_promotor_treinamento ON tbl_os.promotor_treinamento = tbl_promotor_treinamento.promotor_treinamento
                WHERE os = $os
                AND status_os IN (92,93,94)
                ORDER BY data ASC";
    $res_status = pg_query($con,$sql_status);
    $resultado = pg_num_rows($res_status);
    if ($resultado>0){
        echo "<BR>\n";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
        echo "<TR>\n";
        echo "<TD colspan='4' class='inicio'>&nbsp;">traduz("historico",$con,$cook_idioma)."</TD>\n";
        echo "</TR>\n";
        echo "<TR>\n";
        echo "<TD  class='titulo2' width='100px' align='center'><b>".traduz("data",$con,$cook_idioma)."</b></TD>\n";
        echo "<TD  class='titulo2' width='170px' align='left'><b>".traduz("status",$con,$cook_idioma)."</b></TD>\n";
        echo "<TD  class='titulo2' width='260px' align='left'><b>".traduz("observacao",$con,$cook_idioma)."</b></TD>\n";
        echo "<TD  class='titulo2' width='170px' align='left'><b>".traduz("promotor",$con,$cook_idioma)."</b></TD>\n";
        echo "</TR>\n";
        for ($j=0;$j<$resultado;$j++){
            $status_os          = trim(pg_fetch_result($res_status,$j,status_os));
            $status_observacao  = trim(pg_fetch_result($res_status,$j,observacao));
            $status_data        = trim(pg_fetch_result($res_status,$j,data_status));
            $status_admin       = trim(pg_fetch_result($res_status,$j,admin));
            $descricao          = trim(pg_fetch_result($res_status,$j,descricao));
            $nome               = trim(strtoupper(pg_fetch_result($res_status,$j,nome)));
            $email              = trim(pg_fetch_result($res_status,$j,email));
            $nome_promotor      = trim(strtoupper(pg_fetch_result($res_status,$j,nome_promotor)));
            $email_promotor     = trim(pg_fetch_result($res_status,$j,email_promotor));

            echo "<TR>\n";
            echo "<TD  class='justificativa' align='center'><b>".$status_data."</b></TD>\n";
            echo "<TD  class='justificativa' align='left' nowrap>".$descricao."</TD>\n";
            echo "<TD  class='justificativa' align='left'>".$status_observacao."</TD>\n";
            echo "<TD  class='justificativa' align='left' nowrap>";
            if($status_os == 92) { // HD 55196
                echo "<acronym title='".traduz("nome",$con,$cook_idioma).": ".$nome_promotor." - \nEmail:".$email_promotor."'>".$nome_promotor;
            } else {
                echo "<acronym title='".traduz("nome",$con,$cook_idioma).": ".$nome." - \nEmail:".$email."'>".$nome;
            }
            echo "</TD>\n";
            echo "</TR>\n";
        }
        echo "</TABLE>\n";
    }
}
?>


<?// adicionado por Fabio - 05/11/2007 - HD chamado 6525
if (($login_fabrica == 3 or $login_fabrica==24 or $login_fabrica == 45) AND $login_posto == 6359) {
    $sql = "SELECT orcamento
            FROM tbl_orcamento
            WHERE os = $os";
    $res_orca = pg_query($con,$sql);
    $resultado = pg_num_rows($res_orca);
    if ($resultado>0){
        $orcamento = trim(pg_fetch_result($res_orca,0,orcamento));
        $sql = "SELECT  tbl_hd_chamado_item.hd_chamado_item,
                        TO_CHAR(tbl_hd_chamado_item.data,'DD/MM/YYYY') as data,
                        tbl_hd_chamado_item.comentario
                FROM tbl_hd_chamado
                JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado =  tbl_hd_chamado.hd_chamado
                WHERE tbl_hd_chamado.orcamento = $orcamento
                ORDER BY tbl_hd_chamado_item.data ASC";
        $res_orca = pg_query($con,$sql);
        $resultado = pg_num_rows($res_orca);
        if ($resultado>0){
            echo "<BR>\n";
            echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
            echo "<TR>\n";
            echo "<TD colspan='2' class='inicio'>".traduz("historico.de.orcamento",$con,$cook_idioma)."</TD>\n";
            echo "</TR>\n";
            for ($j=0;$j<$resultado;$j++){
                $orca_hd_chamado_item = trim(pg_fetch_result($res_orca,$j,hd_chamado_item));
                $orca_data            = trim(pg_fetch_result($res_orca,$j,data));
                $orca_comentario      = trim(pg_fetch_result($res_orca,$j,comentario));

                echo "<TR>\n";
                echo "<TD  class='justificativa' width='100px' align='center'><b>$orca_data</b></TD>\n";
                echo "<TD  class='justificativa' width='450px' align='left'>$orca_comentario</TD>\n";
                echo "</TR>\n";
            }
            echo "</TABLE>\n";
        }
    }
}
?>

<?
/* Fabio - 09/11/2007 - HD Chamado 7452 */
$sql="SELECT orcamento,
            total_mao_de_obra,
            total_pecas,
            aprovado,
            TO_CHAR(data_aprovacao,'DD/MM/YYYY') AS data_aprovacao,
            TO_CHAR(data_reprovacao,'DD/MM/YYYY') AS data_reprovacao,
            motivo_reprovacao
        FROM tbl_orcamento
        WHERE empresa = $login_fabrica
        AND   os      = $os";
$resOrca = pg_query ($con,$sql);
if (pg_num_rows($resOrca)>0){
    $orcamento         = pg_fetch_result($resOrca,0,orcamento);
    $total_mao_de_obra = pg_fetch_result($resOrca,0,total_mao_de_obra);
    $total_pecas       = pg_fetch_result($resOrca,0,total_pecas);
    $aprovado          = pg_fetch_result($resOrca,0,aprovado);
    $data_aprovacao    = pg_fetch_result($resOrca,0,data_aprovacao);
    $data_reprovacao   = pg_fetch_result($resOrca,0,data_reprovacao);
    $motivo_reprovacao = pg_fetch_result($resOrca,0,motivo_reprovacao);

    $total_mao_de_obra = number_format($total_mao_de_obra,2,",",".");
    $total_pecas       = number_format($total_pecas,2,",",".");

    if ($aprovado=='t'){
        $msg_orcamento = traduz("orcamento.aprovado",$con,$cook_idioma).". ( ".traduz("data",$con,$cook_idioma).": $data_aprovacao )";
    }elseif ($aprovado=='f'){
        $msg_orcamento = traduz("orcamento",$con,$cook_idioma)." <b style='color:red'>".strtoupper(traduz("reprovado",$con,$cook_idioma))."</b>. ".traduz("motivo",$con,$cook_idioma).": $motivo_reprovacao ( ".traduz("data",$con,$cook_idioma).": $data_reprovacao )";
    } else {
        $msg_orcamento = traduz("orcamento.aguardando.aprovacao",$con,$cook_idioma).".";
    }
    echo "<BR>\n";
    echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
    echo "<TR>\n";
    echo "<TD colspan='2' class='inicio'>".traduz("orcamento",$con,$cook_idioma)."</TD>\n";
    echo "</TR>\n";
    echo "<TR>\n";
    echo "<TD  class='titulo' align='left'><b>".traduz("valor.mao.de.obra",$con,$cook_idioma)."</b></TD>\n";
    echo "<TD  class='justificativa' width='450px' align='left' style='padding-left:10px'>$total_mao_de_obra</TD>\n";
    echo "</TR>\n";
    echo "<TR>\n";
    echo "<TD  class='titulo' align='left'><b>".traduz("valor.pecas",$con,$cook_idioma)."</b></TD>\n";
    echo "<TD  class='justificativa' align='left' style='padding-left:10px'>$total_pecas</TD>\n";
    echo "</TR>\n";
    echo "<TR>\n";
    echo "<TD  class='titulo' align='left'><b>".traduz("aprovacao",$con,$cook_idioma)."</b></TD>\n";
    echo "<TD  class='justificativa' align='left' style='padding-left:10px'><div id='msg_orcamento'>$msg_orcamento</div></TD>\n";
    echo "</TR>\n";
    if (strlen($aprovado)==0) {
    echo "<TR>\n";
    echo "<TD  colspan='2' class='justificativa' align='center' style='padding-left:10px'><div id='aprova_reprova'><input type='button' name='aprovar_orcamento' value='Aprovar' onclick='aprovaOrcamento($orcamento)'> <input type='button' name='reprova_orcamento' value='Reprovar' onclick='reprovaOrcamento($orcamento)'></div></TD>\n";
    echo "</TR>\n";
    }
    echo "</TABLE>\n";
}
?>

<?php

/* Transportadora */
if(in_array($login_fabrica, array(157))){

    $sql_os_item = "SELECT os_item FROM tbl_os_item WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = {$os})";
    $res_os_item = pg_query($con, $sql_os_item);

    $os_item_arr = array();

    if(pg_num_rows($res_os_item)){

        for ($i = 0; $i < pg_num_rows($res_os_item); $i++) {

            $os_item_arr[] = pg_fetch_result($res_os_item, $i, "os_item");

        }

    }

    if(count($os_item_arr) > 0){

        $in_os_item = implode(",", $os_item_arr);

        $sql = "SELECT
                    tbl_transportadora.nome,
                    tbl_transportadora.cnpj,
                    tbl_faturamento.nota_fiscal
                FROM tbl_faturamento_item
                INNER JOIN tbl_faturamento USING(faturamento)
                INNER JOIN tbl_transportadora USING(transportadora)
                WHERE
                    tbl_faturamento.fabrica = $login_fabrica
                    AND tbl_faturamento_item.os_item IN ({$in_os_item})";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){

        ?>

        <br/>

        <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <tr>
                <td class='inicio' colspan='3' style="font-size:11pt; text-align: center;"><?= traduz("informacoes.da.transportadora") ?></td>
            </tr>
            <tr>
                <td class="titulo2" width="50%"><?= traduz("nome") ?></td>
                <td class="titulo2" width="25%">CPNJ</td>
                <td class="titulo2" width="25%"><?= traduz("nota.fiscal") ?></td>
            </tr>

            <?php

            for ($i = 0; $i < pg_num_rows($res); $i++) {

                $nome_transportadora = pg_fetch_result($res, 0, "nome");
                $cnpj_transportadora = pg_fetch_result($res, 0, "cnpj");
                $nota_fiscal_transportadora = pg_fetch_result($res, 0, "nota_fiscal");

                ?>

                <tr>
                    <td class="conteudo" style="text-align: center;"><?php echo $nome_transportadora; ?></td>
                    <td class="conteudo" style="text-align: center;"><?php echo $cnpj_transportadora; ?></td>
                    <td class="conteudo" style="text-align: center;"><?php echo $nota_fiscal_transportadora; ?></td>
                </tr>

                <?php

            }

            ?>

        </table>

        <?php

        }

    }

}

?>

<?
//incluido por Welligton 29/09/2006 - Fabricio chamado 472

if (strlen($orientacao_sac) > 0 and $orientacao_sac != "null" and !in_array($login_fabrica, array(11,172)) ){
    echo "<BR>";
    echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
    echo "<TR>";
    echo "<TD colspan=7 class='inicio'>&nbsp;".traduz("orientacoes.do.sac.ao.posto.autorizado",$con,$cook_idioma)."</TD>";
    echo "</TR>";
    echo "<TR>";
    echo "<TD class='conteudo_sac'>Obs: ".nl2br(trim(str_replace("|","<br/>",str_replace("<p>","<br/>",str_replace("</p>","<br/>",str_replace("</p><p>","<br/>",str_replace("null","<br />",$orientacao_sac)))))))."</TD>";
    echo "</TR>";
    echo "</TABLE>";
}

if (in_array($login_fabrica, [169,170])) {

    $sqlCampoExtra = "SELECT tbl_os.sua_os, tbl_os.os
                      FROM tbl_os_campo_extra
                      JOIN tbl_os ON tbl_os.os = tbl_os_campo_extra.os
                      AND tbl_os.fabrica = {$login_fabrica}
                      WHERE campos_adicionais::jsonb->>'os_reoperacao' = '{$os}'
                      AND tbl_os_campo_extra.fabrica = {$login_fabrica}";
    $resCampoExtra = pg_query($con, $sqlCampoExtra);

    if (pg_num_rows($resCampoExtra) > 0) {

        $osGerou   = pg_fetch_result($resCampoExtra, 0, 'sua_os');
        $osGerouId = pg_fetch_result($resCampoExtra, 0, 'os');

        echo "<br />";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
        echo "<TR>";
            echo "<td class='inicio' width='300'><strong>" . strtoupper(traduz("OS Gerada a partir da OS:")) . "</strong></td>";
            echo "<td class='justificativa' width='400' align='left'> &nbsp;<strong><a href='os_press.php?os={$osGerouId}' target='_blank'>{$osGerou}</a></strong></td>";
        echo "</TR>";
        echo "</TABLE>";

    }

}
?>
<?
//incluido por takashi 19/10/2007 - hd4536
//qdo OS é fechada com peças ainda pedente o posto tem que informar o motivo, o motivo a gente mostra aqui
if ($login_fabrica == 3){
    $sql = "SELECT obs_fechamento from tbl_os_extra where os=$os";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){
        $motivo_fechamento = pg_fetch_result($res,0,0);
        if(strlen($motivo_fechamento)>0){
            echo "<BR>";
            echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
            echo "<TR>";
            echo "<TD colspan=7 class='inicio'>&nbsp;".traduz("justificativa.fechamento.de.os.com.peca.ainda.pendente",$con,$cook_idioma)."</TD>";
            echo "</TR>";
            echo "<TR>";
            echo "<TD class='conteudo'>$motivo_fechamento</TD>";
            echo "</TR>";
            echo "</TABLE>";
        }
    }
}

//Colocado por Fabio - HD 14344
//mostra o status da OS: acumulada ou resucasa
if ($login_fabrica == 25){
    $sql = "SELECT  TO_CHAR(data,'DD/MM/YYYY') AS data,
                    tbl_os_status.status_os    AS status_os,
                    tbl_os_status.observacao   AS observacao
            FROM tbl_os_extra
            JOIN tbl_os_status USING(os)
            WHERE os = $os
            AND tbl_os_status.status_os IN (13,14)
            AND tbl_os_extra.extrato IS NULL
            ORDER BY tbl_os_status.data DESC
            LIMIT 1";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){
        echo "<BR>";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
        echo "<TR>";
        echo "<TD colspan=7 class='inicio'>".traduz("extrato",$con,$cook_idioma)." - ".traduz("status.da.os",$con,$cook_idioma)."</TD>";
        echo "</TR>";
        for ($i=0; $i<pg_num_rows($res); $i++){
            $status_data       = pg_fetch_result($res,0,data);
            $status_status_os  = pg_fetch_result($res,0,status_os);
            $status_observacao = pg_fetch_result($res,0,observacao);

            if ($status_status_os==13){
                $status_status_os = "Recusada";
            }elseif ($status_status_os==14){
                $status_status_os = "Acumulada";
            } else {
                $status_status_os = "-";
            }

            echo "<TR>";
            echo "<TD class='conteudo'>$status_data</TD>";
            echo "<TD class='conteudo'>$status_status_os</TD>";
            echo "<TD class='conteudo' colspan=5>$status_observacao</TD>";
            echo "</TR>";
        }
        echo "</TABLE>";
    }
}
if(in_array($login_fabrica,array(3,11,35,42,126,151,172))){
	//verifica se tem imagem na OS
	$amazonTC->getObjectList("anexo_os_{$login_fabrica}_{$os}_img_os_");
	$files_anexo_os = $amazonTC->files;

	//verifica se tem imagem no OS Item
	$amazonTC->getObjectList("anexo_os_item_{$login_fabrica}_{$os}_img_os_item_");
	$files_anexo_os_item = $amazonTC->files;

	//verifica se tem imagem no OS Revenda
	$sqlSuaOS = "   SELECT sua_os
		FROM tbl_os
		WHERE tbl_os.os = {$os} AND
		fabrica = {$login_fabrica}";
	$resSuaOs = pg_query($con,$sqlSuaOS);
	$suaOs = pg_fetch_result($resSuaOs, 0, "sua_os");
	list($suaOs,$digito) = explode("-", $suaOs);

	$sqlOsRevenda = "   SELECT os_revenda
		FROM tbl_os_revenda
		WHERE sua_os = '{$suaOs}' AND
		fabrica = {$login_fabrica}";

	$resOsRevenda = pg_query($con,$sqlOsRevenda);
	if(pg_num_rows($resOsRevenda) > 0){
		$osRevenda = pg_fetch_result($resOsRevenda, 0, "os_revenda");

		$amazonTC->getObjectList("anexo_os_revenda_{$login_fabrica}_{$osRevenda}_img_os_revenda_");
		$files_anexo_os_revenda = $amazonTC->files;
	}
	if((count($files_anexo_os_item) > 0 ) ||  (count($files_anexo_os) > 0 ) || (count($files_anexo_os_revenda) > 0)){
        if($login_fabrica != 3){
?>
<br/>
		<table width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>
			<thead>
				<tr>
					<th class='inicio' style="text-align: center !important;" colspan="4" ><?= traduz("anexos") ?></th>
				</tr>
			</thead>
			<tr>

<? if(count($files_anexo_os) > 0){
	foreach ($files_anexo_os as $path) {
		$basename = basename($path);
		$thumb = $amazonTC->getLink("thumb_".$basename, false, "", "");
		$full  = $amazonTC->getLink($basename, false, "", "");
		$pathinfo = pathinfo($full);
		list($ext,$params) = explode("?", $pathinfo["extension"]);
?>

						   <td  align="center" class='conteudo' style="text-align: center !important;">
								<a href="<?=$full?>" >
									<? if($ext == "pdf"){ ?>
										<img src="imagens/adobe.JPG" title="Clique para ver a imagem em uma escala maior" alt="Baixar Anexo" style="width: 100px; height: 90px;" />
									<?}else{ ?>
										<img src="<?=$thumb?>" title="Clique para ver a imagem em uma escala maior" alt="Baixar Anexo" style="width: 100px; height: 90px;" />
									<? } ?>
								</a>
								</td><?
	}

}
if(count($files_anexo_os_item) > 0){
	foreach ($files_anexo_os_item as $path) {
		$basename = basename($path);
		$thumb = $amazonTC->getLink($basename, false, "", "");
		$full  = $amazonTC->getLink($basename, false, "", "");
		$pathinfo = pathinfo($full);
		list($ext,$params) = explode("?", $pathinfo["extension"]);
?>
						  <td  align="center" class='conteudo' style="text-align: center !important;">
							<a href="<?=$full?>" >
								<? if($ext == "pdf"){ ?>
									<img src="imagens/adobe.JPG" title="Clique para ver a imagem em uma escala maior" alt="Baixar Anexo" style="width: 32px; height: 32px;" />
								<?}else{ ?>
									<img src="<?=$thumb?>" title="Clique para ver a imagem em uma escala maior" alt="Baixar Anexo" style="width: 100px; height: 90px;" />
								<? } ?>
							</a>
							</td><?
	}
}

if(count($files_anexo_os_revenda) > 0){
	foreach ($files_anexo_os_revenda as $path) {
		$basename = basename($path);
		$thumb = $amazonTC->getLink($basename, false, "", "");
		$full  = $amazonTC->getLink($basename, false, "", "");
		$pathinfo = pathinfo($full);
		list($ext,$params) = explode("?", $pathinfo["extension"]);
?>
						  <td  align="center" class='conteudo' style="text-align: center !important;">
							<a href="<?=$full?>" >
								<? if($ext == "pdf"){ ?>
									<img src="imagens/adobe.JPG" title="Clique para ver a imagem em uma escala maior" alt="Baixar Anexo" style="width: 32px; height: 32px;" />
								<?}else{ ?>
									<img src="<?=$thumb?>" title="Clique para ver a imagem em uma escala maior" alt="Baixar Anexo" style="width: 100px; height: 90px;" />
								<? } ?>
							</a>
							</td><?
	}
} ?>


			</tr>
			<tr>


			</tr>

		</table><br/>
<?}
}
}
# 53003 - mostrar todas as ocorrências e o admin
if ($login_fabrica == 45){
    $sql = "SELECT  TO_CHAR(data,'DD/MM/YYYY') AS data,
                    tbl_os_status.status_os    AS status_os,
                    tbl_os_status.observacao   AS observacao,
                    tbl_os_status.extrato,
                    tbl_admin.nome_completo
            FROM tbl_os_extra
            JOIN tbl_os_status USING(os)
            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin
            WHERE os = $os
            AND tbl_os_status.status_os IN (13,14)
            AND tbl_os_extra.extrato IS NULL
            ORDER BY tbl_os_status.data DESC";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){
        echo "<BR>";
        echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
        echo "<TR>";
        echo "<TD colspan=7 class='inicio'>EXTRATO - STATUS DA OS</TD>";
        echo "</TR>";
        echo "<TR>";
        echo "<TD class='titulo2' align='center'>".traduz("data")."</TD>";
        echo "<TD class='titulo2' align='center'>ADMIN</TD>";
        echo "<TD class='titulo2' align='center'>".traduz("extrato")."</TD>";
        echo "<TD class='titulo2' align='center'>STATUS</TD>";
        echo "<TD class='titulo2' align='center'>".traduz("observacao")."</TD>";
        echo "</TR>";

        for ($i=0; $i<pg_num_rows($res); $i++){
            $status_data       = pg_fetch_result($res,$i,data);
            $status_status_os  = pg_fetch_result($res,$i,status_os);
            $status_observacao = pg_fetch_result($res,$i,observacao);
            $zextrato          = pg_fetch_result($res,$i,extrato);
            $admin_nome        = pg_fetch_result($res,$i,nome_completo);

            if ($status_status_os==13){
                $status_status_os = "Recusada";
            }elseif ($status_status_os==14){
                $status_status_os = "Acumulada";
            } else {
                $status_status_os = "-";
            }

            echo "<TR>";
            echo "<TD class='conteudo' style='text-align: center'>$status_data</TD>";
            echo "<TD class='conteudo' style='text-align: center'>$admin_nome</TD>";
            echo "<TD class='conteudo' style='text-align: center'>$zextrato</TD>";
            echo "<TD class='conteudo' style='padding-left: 5px'>$status_status_os</TD>";
            echo "<TD class='conteudo' style='padding-left: 5px'>$status_observacao</TD>";
            echo "</TR>";
        }
        echo "</TABLE>";
    }
}

if (strlen($recomendacoes) > 0 and $login_fabrica == 50) {
    echo "<br />";
    echo"<table width='700px' class='Tabela' border='0' cellspacing='1' cellpadding='2' align='center'>";
        echo "<tr class='inicio'>";
            echo "<TD><b>".traduz("dados.da.revenda.da.nota.fiscal").":</b></TD>";
        echo "</tr >";
        echo "<tr  class='conteudo'><td>".nl2br($recomendacoes)."</td></tr>";
    echo "</table>";
}

if ($auditoria_unica == true || in_array($login_fabrica, array(42))) { ?>
    <br/>
    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
        <tr>
            <td class='inicio' colspan='6' style="font-size:11pt; text-align: center;"><?= traduz("historico.de.intervencao") ?></td>
        </tr>
        <tr>
            <?php if($login_fabrica == 134){ ?>
            <TD class="titulo2"><?= traduz("data.auditoria") ?></TD>
            <TD class="titulo2"><?= traduz("data.auditado") ?></TD>
            <TD class="titulo2"><?= traduz("descricao") ?></TD>
            <TD class="titulo2">Status</TD>
            <TD class="titulo2"><?= traduz("justificativa") ?></TD>
            <TD class="titulo2">Admin</TD>
            <?php }else{ ?>
            <TD class="titulo2"><?= traduz("data") ?></TD>
            <TD class="titulo2"><?= traduz("descricao") ?></TD>
            <TD class="titulo2">Status</TD>
            <TD class="titulo2"><?= traduz("justificativa") ?></TD>
                <?php if ($login_fabrica == 178){ ?>
                    <TD class="titulo2"><?= traduz("admin") ?></TD>
                <?php } ?>
            <?php } ?>
        </tr>
        <?php
            if($login_fabrica == 1){
                $coluna_liberada_aud = " tbl_auditoria_os.liberada AS liberada,";
                $coluna_liberada_aud_revenda = " tbl_auditoria_os_revenda.liberada AS liberada, ";
            }else{
                $coluna_liberada_aud = " to_char(tbl_auditoria_os.liberada,'DD/MM/YYYY') AS liberada, ";
                $coluna_liberada_aud_revenda = " to_char(tbl_auditoria_os_revenda.liberada,'DD/MM/YYYY') AS liberada, ";
            }
		    if (in_array($login_fabrica, array(24,42))) {
				$sqlAuditoria = "SELECT
								tbl_os_status.os_status AS os_status,
								tbl_os_status.status_os AS status_os,
								tbl_os_status.observacao AS observacao,
								tbl_admin.login AS nome_completo,
								to_char(tbl_os_status.data,'DD/MM/YYYY') AS data_input,
								tbl_os_status.admin AS admin,
								tbl_status_os.descricao AS descricao,
								'' AS justificativa,
								null as liberada,
								null AS reprovada,
								null AS cancelada,
								null as paga_mao_obra
							FROM tbl_os_status
							JOIN tbl_status_os using(status_os)
							LEFT JOIN tbl_admin USING(admin)
							WHERE tbl_os_status.os = $os
							AND tbl_os_status.fabrica_status = $login_fabrica
								UNION
							SELECT
								null AS os_status,
								null AS status_os,
								tbl_auditoria_os.observacao AS observacao,
								tbl_admin.nome_completo,
								to_char(tbl_auditoria_os.data_input,'DD/MM/YYYY') AS data_input,
								tbl_auditoria_os.admin AS admin,
								tbl_auditoria_status.descricao AS descricao,
								tbl_auditoria_os.justificativa AS justificativa,
								to_char(tbl_auditoria_os.liberada,'DD/MM/YYYY') AS liberada,
								to_char(tbl_auditoria_os.reprovada,'DD/MM/YYYY') AS reprovada,
								to_char(tbl_auditoria_os.cancelada,'DD/MM/YYYY') AS cancelada,
								tbl_auditoria_os.paga_mao_obra
							FROM tbl_auditoria_os
							JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = $login_fabrica
							LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_auditoria_os.admin
							JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
							WHERE tbl_auditoria_os.os = $os";
			}else{
           
				$sqlAuditoria = "
                SELECT tbl_auditoria_status.descricao,
                    tbl_auditoria_os.observacao,
                    tbl_auditoria_os.auditoria_os,
                    to_char(tbl_auditoria_os.data_input,'DD/MM/YYYY') AS data_input,
                    $coluna_liberada_aud
                    to_char(tbl_auditoria_os.cancelada,'DD/MM/YYYY') AS cancelada,
                    to_char(tbl_auditoria_os.reprovada,'DD/MM/YYYY') AS reprovada,
                    tbl_auditoria_os.justificativa,
                    NULL AS os_revenda,
                    tbl_auditoria_os.admin
                FROM tbl_auditoria_os
                JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = $login_fabrica
                JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                WHERE tbl_auditoria_os.os = $os
                UNION
                SELECT tbl_auditoria_status.descricao,
                    tbl_auditoria_os_revenda.observacao,
                    tbl_auditoria_os_revenda.auditoria_os,
                    to_char(tbl_auditoria_os_revenda.data_input,'DD/MM/YYYY') AS data_input,
                    $coluna_liberada_aud_revenda
                    to_char(tbl_auditoria_os_revenda.cancelada,'DD/MM/YYYY') AS cancelada,
                    to_char(tbl_auditoria_os_revenda.reprovada,'DD/MM/YYYY') AS reprovada,
                    tbl_auditoria_os_revenda.justificativa,
                    tbl_os_campo_extra.os_revenda,
                    tbl_auditoria_os_revenda.admin
                FROM tbl_auditoria_os_revenda
                JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os_revenda.auditoria_status
                JOIN tbl_os ON tbl_os.os = $os
                JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
                WHERE tbl_auditoria_os_revenda.os_revenda = tbl_os_campo_extra.os_revenda
                ORDER BY data_input DESC ";
			}
            $resAuditoria = pg_query($con,$sqlAuditoria);
            $count = pg_num_rows($resAuditoria);

            if($count > 0){
                for($i=0; $i < $count; $i++){
                    unset($liberada, $cancelada_auditoria, $reprovada, $data_auditado, $status_auditoria);
                    $descricao_auditoria     = pg_fetch_result($resAuditoria, $i, "descricao");
                    $observacao_auditoria    = pg_fetch_result($resAuditoria, $i, "observacao");
					$observacao_auditoria =  mb_detect_encoding($observacao_auditoria, 'UTF-8', true) ? utf8_decode($observacao_auditoria) : $observacao_auditoria;
                    $data_auditoria          = pg_fetch_result($resAuditoria, $i, "data_input");

                    if ($login_fabrica == 183 && $observacao_auditoria == "OS em Auditoria de Número de Série bloqueado") {
                        continue;
                    }

                    if($login_fabrica == 1){
                        $liberada                = mostra_data(substr(pg_fetch_result($resAuditoria, $i, "liberada"),0, 16));
                    }else{
                        $liberada                = pg_fetch_result($resAuditoria, $i, "liberada");
                    }

                    $cancelada_auditoria     = pg_fetch_result($resAuditoria, $i, "cancelada");
                    $justificativa_auditoria = pg_fetch_result($resAuditoria, $i, "justificativa");
                    $reprovada               = pg_fetch_result($resAuditoria, $i, "reprovada");
                    $admin_auditor = pg_fetch_result($resAuditoria, $i, "admin");
                    $id_auditoria = pg_fetch_result($resAuditoria, $i, "auditoria_os");
					$status_os = pg_fetch_result($resAuditoria, $i, "status_os");
					if($status_os == 19) $liberada = $data_auditoria;

                    if ($login_fabrica == 178){
                        $os_revenda_id = pg_fetch_result($resAuditoria, $i, "os_revenda");

                        if (!empty($os_revenda_id)){
                            $text_os_revenda = "OS principal: $os_revenda_id ";
                        }

						if(!empty($admin_auditor)) {
							$sqlAdmin = "SELECT nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $admin_auditor";
							$resAdmin = pg_query($con, $sqlAdmin);

							if(pg_num_rows($resAdmin) > 0){
								$nome_completo_admin = pg_fetch_result($resAdmin, 0, 'nome_completo');
							}
						}
                    }

                    if($login_fabrica == 134){
                        if($liberada != ""){
                            $status_auditoria = "Liberado";
                            $data_auditado = $liberada;
                        }
                        if($cancelada_auditoria != ""){
                            $status_auditoria = "Cancelado";
                            $data_auditado = $cancelada_auditoria;
                        }
                        if($reprovada != ""){
                            $status_auditoria = "Reprovada";
                            $data_auditado = $reprovada;
                        }
                        if (empty($status_auditoria)) {
                            $status_auditoria = "Aguardando Admin";
                        }
                        $sqlAdmin = "SELECT nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $admin_auditor";
                        $resAdmin = pg_query($con, $sqlAdmin);

                        if(pg_num_rows($resAdmin) > 0){
                            $nome_completo_admin = pg_fetch_result($resAdmin, 0, 'nome_completo');
                        }

                    }else{
                        if($liberada != ""){
                            $status_auditoria = "Liberado em $liberada";
                        }else if($cancelada_auditoria != ""){
                            $status_auditoria = "Cancelado em $cancelada_auditoria";
                        }else if($reprovada != ""){
                            $status_auditoria = "Reprovada em $reprovada ";
                        }
                        if (empty($status_auditoria)) {
                            $status_auditoria = "Aguardando Admin";
                        }
                    }

        ?>
        <tr>
            <td class="conteudo"><?= $data_auditoria; ?></td>
            <? if($login_fabrica == 134) { ?>
                <td class="conteudo"><?= $data_auditado; ?></td>
                <td class="conteudo"><?= $observacao_auditoria; ?></td>
            <? } else { ?>
                <td class="conteudo"><?=$text_os_revenda?> <?= $descricao_auditoria; ?> - <?= $observacao_auditoria; ?></td>
            <? } ?>
            <td class="conteudo" nowrap>
<?php
                echo $status_auditoria;

                if(in_array($login_fabrica, [177]) && $descricao_auditoria == "Auditoria da Fábrica") {
                    $sql = "SELECT observacao FROM tbl_laudo_tecnico_os WHERE os = $os AND fabrica = $login_fabrica ORDER BY data DESC LIMIT 1;";    
                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res)){
                        $res = pg_fetch_array($res);
                        $res['observacao'] = json_decode($res['observacao'],1);
                        if($res['observacao']['auditoria'] != ""){
                        ?>
                            <input id="btn_imprimi_laudo"  type="button" value="Ver laudo" onclick="window.open('laudo_anauger.php?print=true&auditoria=<?=$res['observacao']['auditoria']?>&os=<? echo $os ?>','imprimir');">
                        <?php
                        }
                    }
                }

                if(in_array($login_fabrica, [167, 203]) && $descricao_auditoria == "Auditoria da Fábrica"){
                    if($observacao_auditoria == "Auditoria de Suprimento" OR $observacao_auditoria == "Auditoria de Garantia"){
                        $sql_laudo_brother = "SELECT laudo_tecnico_os FROM tbl_laudo_tecnico_os WHERE os = $os AND fabrica = $login_fabrica";
                        $res_laudo_brother = pg_query($con, $sql_laudo_brother);
                        if(pg_num_rows($res_laudo_brother) > 0 && (!isset($prenchido) && $prenchido != true)){
                            $prenchido = true;

?>
                            <button><a href="consulta_laudo_brother.php?os=<?=$os?>&auditoria=<?=$id_auditoria?>" target="_blank">Ver laudo</a></button>
<?php
                        }
                    }
                }
?>
            </td>
            <? if (in_array($login_fabrica,array(42,134))) { ?>
                <td class="conteudo"><?=utf8_decode($justificativa_auditoria)?></td>
            <? } else {?>
                <td class="conteudo"><?= $justificativa_auditoria; ?></td>
            <? } ?>
            <? if ($login_fabrica == 134 OR $login_fabrica == 178) { ?>
                <td class="conteudo"><?= $nome_completo_admin; ?></td>
            <? } ?>
        </tr>
        <? }
        } ?>
    </table>
<? }
 
if (in_array($login_fabrica, [174]) && $posto_interno) {
     $sql_interno_aquarius = " SELECT posto_interno
                            FROM tbl_posto_fabrica
                                JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                            WHERE posto = $login_posto
                                AND tbl_posto_fabrica.fabrica = $login_fabrica";
    $res_interno_aquarius = pg_query($con, $sql_interno_aquarius);
    $interno_aquarius = pg_fetch_result($res_interno_aquarius, 0, posto_interno);
    if ( $interno_aquarius == 't') {
        $sqlCamposAdicionais = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os} ";
        $resCamposAdicionais = pg_query($con, $sqlCamposAdicionais);
        $camposAdicionais = json_decode(pg_fetch_result($resCamposAdicionais, 0, 'campos_adicionais'));
        ?>
        </br>
        <style type="text/css">
            .correios-rastreio-interno:hover {
                cursor: pointer;
                color: #666;
            }

            .correios-status tr:hover {
                background-color: #F1F1F1;
            }
        </style>
        <table width="700px" border="0" cellspacing="1" cellpadding="0" align="center" class="Tabela">
            <tbody>
                <tr>
                    <td class="inicio" colspan="4" height="15">&nbsp;INFORMAÇÕES ADICIONAIS&nbsp;</td>
                </tr>
                <tr>
                    <td class="titulo" height="15" width="70">NF Entrada &nbsp;</td>
                    <td class="conteudo" height="15" width="250">&nbsp;<?=$camposAdicionais->nf_entrada?></td>
                    <td class="titulo" height="15" width="70">NF Saída&nbsp;</td>
                    <td class="conteudo" height="15">&nbsp;<?=$camposAdicionais->nf_saida?></td>
                </tr>
                <tr>
                    <td class="titulo" height="15">RASTREIO &nbsp;</td>
					<td class="conteudo" colspan="3">&nbsp;
						<?php if (preg_match("/BR/", $codigoRastreamento)): ?>
						<span class="correios-rastreio-interno"><?=$camposAdicionais->rastreio?></span>
						<?php else: ?>
						<a href="busca_tracking_te.php?rastreio=<?=$camposAdicionais->rastreio?>" target="_blank"><strong><?=$camposAdicionais->rastreio?></strong></a>
						<?php endif ?>
					</td>
            </tbody>
        </table>
        <script type="text/javascript">
        $(function () {
            Shadowbox.init();
                $(".correios-rastreio-interno").on("click", function () {
                    var codigoRastreamento = '<?= $camposAdicionais->rastreio ?>';
                    var templateRastreamento = $("#correios-container").html();
                    var rows = [];

                    Shadowbox.open({
                        content: "<div id='sb-player'></div>",
                        player: 'html',
                        height: 400,
                        width: 800,
                        options: {
                            modal: true,
                            enableKeys: false,
                            onFinish: function() {
                                $.ajax({
                                    url: window.location,
                                    type: 'POST',
                                    async: true,
                                    data: {
                                        ajax: 'faturamentoCorreios',
                                        codigoRastreamento: codigoRastreamento
                                    }
                                }).fail(function (response) {
                                    alert("Falha ao buscar informações do rastreamento.");
                                    Shadowbox.close()
                                }).done(function (response) {
                                    response = JSON.parse(response);

                                    $.each(response, function (index, element) {
                                        let line = $("<tr></tr>", {
                                            css: {
                                                "text-align": "center",
                                            }
                                        });

                                        let dataCol = $("<td></td>", {
                                            text: element.data || ""
                                        });

                                        let situacaoCol = $("<td></td>", {
                                            text: element.situacao || ""
                                        });

                                        let obsCol = $("<td></td>", {
                                            text: element.obs || ""
                                        });

                                        $(line).prepend(obsCol);
                                        $(line).prepend(situacaoCol);
                                        $(line).prepend(dataCol);

                                        rows.push(line);
                                    });

                                    var player = $('#sb-player');
                                    $(player).html(templateRastreamento);
                                    $(player).find("#correios-rastreamento").find("tbody").append(rows);
                                });
                            }
                        }
                    });
                });
            });
        </script>
        <script type=text/html" id="correios-container">
            <div id="correios-rastreamento" style="background-color:#FFF;width:100%;height:100%;">
                <table style="font-size:14px;width:100%;padding:5px;background-color:#FFF;line-height:25px" cellspacing="1">
                    <thead style="background-color:#596D9B;color:#FFF;font-weight:bold;text-align:center">
                        <tr>
                            <th colspan="3" style="border-top-left-radius:5px;border-top-right-radius: 5px;padding:5px">RASTREAMENTO DE OBJETO</th>
                        </tr>
                        <tr>
                            <th style="padding:3px;">Data</th>
                            <th style="padding:3px;">Situação</th>
                            <th style="padding:3px;">Observação</th>
                        </tr>
                    </thead>
                    <tbody class="correios-status" style="padding:2px;" cellspacing="0">
                        
                    </tbody>
                </table>
            </div>
        </script>

        <?php
    }
}
if($troca == 1 AND in_array($login_fabrica,array(11,81,104,114,123,125,134,172)) OR $union_auditoria_os == true or $login_fabrica == 1){
	if ($login_fabrica == 1) {
		$protocolo_tab_a = ' tbl_extrato.protocolo, ';
		$protocolo_tab_b = ' NULL as protocolo, ';
		$protocolo_join = ' LEFT JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_status.extrato ';
	} else {
		$protocolo_tab_a = '';
		$protocolo_tab_b = '';
		$protocolo_join = '';
	}

        $sqlAudit = "SELECT
                        tbl_os_status.os_status AS os_status,
                        tbl_os_status.status_os AS status_os,
                        tbl_os_status.observacao AS observacao,
                        tbl_os_status.campos_adicionais AS campos_adicionais_status,
                        tbl_admin.login AS login,
                        tbl_os_status.data AS data_status,
                        tbl_os_status.admin AS admin,
                        tbl_status_os.descricao AS descricao,
						'' AS justificativa,
						$protocolo_tab_a
						null as liberada,
						null as reprovada
                    FROM tbl_os_status
                    JOIN tbl_status_os using(status_os)
                    LEFT JOIN tbl_admin USING(admin)
					$protocolo_join
                    WHERE tbl_os_status.os = $os
                    AND tbl_os_status.fabrica_status = $login_fabrica
                        UNION
                    SELECT
                        null AS os_status,
                        null AS status_os,
                        tbl_auditoria_os.observacao AS observacao,
                        '{}' AS campos_adicionais_status,
                        tbl_admin.nome_completo AS login,
                        tbl_auditoria_os.data_input AS data_status,
                        tbl_auditoria_os.admin AS admin,
                        tbl_auditoria_status.descricao AS descricao,
						tbl_auditoria_os.justificativa AS justificativa,
						$protocolo_tab_b
						to_char(tbl_auditoria_os.liberada,'DD/MM/YYYY') AS liberada,
						to_char(tbl_auditoria_os.reprovada,'DD/MM/YYYY') AS reprovada
                    FROM tbl_auditoria_os
                    JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = $login_fabrica
                    LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_auditoria_os.admin
                    JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                    WHERE tbl_auditoria_os.os = $os
                    ORDER BY data_status DESC";
        $resAudit = pg_query($con, $sqlAudit);

        if(pg_num_rows($resAudit) > 0){

		?>
			<br>
            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
                <tr>
                    <td class='inicio' colspan='6' style="font-size:11pt; text-align: center;">Histórico de Intervenção</td>
                </tr>
                <tr>
                    <TD class="titulo2"><?= traduz("data") ?></TD>
                    <TD class="titulo2"><?= traduz("tipo/status") ?></TD>
                    <TD class="titulo2"><?= traduz("justificativa") ?></TD>
                    <TD class="titulo2">Admin</TD>
                </tr>
        <?php
            for ($t=0; $t < pg_num_rows($resAudit) ; $t++) {

                $os_status = pg_fetch_result($resAudit, $t, 'os_status');
                $status_os = pg_fetch_result($resAudit, $t, 'status_os');
                $observacao = pg_fetch_result($resAudit, $t, 'observacao');
                $login = pg_fetch_result($resAudit, $t, 'login');
                $data_status = pg_fetch_result($resAudit, $t, 'data_status');
                $liberada = pg_fetch_result($resAudit, $t, 'liberada');
                $reprovada = pg_fetch_result($resAudit, $t, 'reprovada');
                $admin = pg_fetch_result($resAudit, $t, 'admin');
                $descricao = pg_fetch_result($resAudit, $t, 'descricao');
                $justificativa = pg_fetch_result($resAudit, $t, 'justificativa');

                $newDate = date("d-m-Y", strtotime($data_status));

                $data_status = str_replace("-", "/", $newDate);

                if(empty($justificativa)){
                    $justificativa = $observacao;
                }else{
                    $justificativa = $justificativa;
                }

				if ($login_fabrica == 1) {
                    $protocolo_intervencao = pg_fetch_result($resAudit, $t, 'protocolo');

					if (!empty($protocolo_intervencao)) {
						$justificativa = "Ação tomada no Extrato $protocolo_intervencao: ".$justificativa;
					}

                    $campos_adicionais_status = json_decode(pg_fetch_result($resAudit, $t, 'campos_adicionais_status'), true);
                    if ($campos_adicionais_status["oculta_historio_posto"]) {
                        $justificativa = '';   
                    }
				}


				if(!empty($liberada)) {
					$descricao = $descricao . " - Liberado em $liberada";
				}elseif(!empty($reprovada)) {
					$descricao = $descricao . " - Reprovado em $reprovada";
				}

                if(empty($login)){
                    $login = "Automático";
                }else{
                    $login = $login;
                }

        ?>
            <tr>
                <td class="conteudo"><?=$data_status?></td>
                <td class="conteudo"><?=$descricao?></td>
                <td class="conteudo"><?=$justificativa?></td>
                <td class="conteudo"><?=$login?></td>
            </tr>
        <?php
            }
        ?>
            </table>
        <?


        }
    }


          
            if (in_array($login_fabrica, array(148,166))) {
                    $order_by = (in_array($login_fabrica, [148])) ? 'ORDER BY tbl_tecnico_agenda.tecnico_agenda ASC' : '';

                    if(in_array($login_posto, array(6359,390306))){

                         $sql = "SELECT  tbl_tecnico_agenda.data_agendamento,
                                         tbl_tecnico.nome,
                                         tbl_tecnico_agenda.justificativa,
                                         tbl_tecnico_agenda.obs,
                                         tbl_tecnico_agenda.data_cancelado,
                                         tbl_tecnico_agenda.justificativa_cancelado,
                                         tbl_tecnico_agenda.hora_inicio_trabalho,
                                         tbl_tecnico_agenda.hora_fim_trabalho,
                                         tbl_tecnico_agenda.data_conclusao
                                 FROM    tbl_tecnico_agenda
                                 JOIN    tbl_tecnico ON tbl_tecnico_agenda.tecnico = tbl_tecnico.tecnico
                                 AND     tbl_tecnico.fabrica = $login_fabrica
                                 WHERE   tbl_tecnico_agenda.os = $os
                                 $order_by";
                         $resAg = pg_query($con, $sql);
                       

                    if(pg_num_rows($resAg) > 0) {
             
            ?>
                        <br>
                        <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
                            <tr>
                                <td class='inicio' colspan='7' style="font-size:11pt; text-align: center;">Histórico de Agendamentos - Aplicativo</td>
                            </tr>
                            <tr>
                                <TD class="titulo2"><?= traduz("#") ?></TD>
                                <TD class="titulo2"><?= traduz("Agendado Para") ?></TD>
                                <TD class="titulo2"><?= traduz("Nome Técnico") ?></TD>
                                <TD class="titulo2"><?= traduz("Obs Agendamento") ?></TD>
                                <TD class="titulo2"><?= traduz("Data Cancelamento") ?></TD>
                                <TD class="titulo2"><?= traduz("Justificativa") ?></TD>
                                <TD class="titulo2"><?= traduz("Concluído") ?></TD>
                            </tr>


                    <?php
                        for ($t=0; $t < pg_num_rows($resAg) ; $t++) {
                            $data_agendamento     = pg_fetch_result($resAg, $t, 'data_agendamento');
                            $nome_tecnico         = pg_fetch_result($resAg, $t, 'nome');
                            $obs_agendamento      = pg_fetch_result($resAg, $t, 'obs');
                            $data_cancelamento    = pg_fetch_result($resAg, $t, data_cancelado);
                            $motivo_cancelamento  = utf8_decode(pg_fetch_result($resAg, $t, 
                                justificativa_cancelado));
                            $hora_inicio_trabalho = pg_fetch_result($resAg, $t, 'hora_inicio_trabalho');
                            $hora_fim_trabalho    = pg_fetch_result($resAg, $t, 'hora_fim_trabalho');
                            $data_conclusao       = pg_fetch_result($resAg, $t, data_conclusao);
                          
                            $newDataAgendamento = date("d-m-Y", strtotime($data_agendamento));
                            $data_agendamentoformat = str_replace("-", "/", $newDataAgendamento);

                            $data_cancelamentoformat = '';
                            if(strlen($data_cancelamento)>0) {
                                $newDataCancelamento = date("d-m-Y H:i", strtotime($data_cancelamento));
                                $data_cancelamentoformat = str_replace("-", "/", $newDataCancelamento);
                            }

                            $newHoraInicio        = date("d-m-Y H:i", strtotime($hora_inicio_trabalho));
                            $newHoraFim           = date("d-m-Y H:i", strtotime($hora_fim_trabalho));
                            $hora_inicio_trabalho = str_replace("-", "/", $newHoraInicio);
                            $hora_fim_trabalho    = str_replace("-", "/", $newHoraFim);


                            if (strlen($data_conclusao) > 0):
                                $newDataConclusao = date("d-m-Y", strtotime($data_conclusao));   
                                $data_conclusao   = str_replace("-", "/", $newDataConclusao);
                            endif;
                           
                    ?>
                        <tr>
                            <td class="conteudo"><?=($t+1)?></td>
                            <td class="conteudo" style="width: 30% !important;"><?=$hora_inicio_trabalho?> ~ <?=$hora_fim_trabalho?></td>
                            <td class="conteudo"><?=$nome_tecnico?></td>
                            <td class="conteudo"><?=$obs_agendamento?></td>
                            <td class="conteudo"><?=$data_cancelamentoformat?></td>
                            <td class="conteudo"><?=$motivo_cancelamento?></td>
                            <td class="conteudo"><?=$data_conclusao?></td>
                        </tr>
                    <?php
                        }
                    ?>
                        </table>
                    <?
                 }   
            }
        }
    //} 


/** 
 * Histórico de Extratos HD-4421141 
 */

if ($novaTelaOs == 't') { 
    
    $sqlExtrato = "SELECT   tbl_status_os.descricao,
                            tbl_os_status.data, 
                            tbl_os_status.extrato,
                            tbl_os_status.observacao,
                            tbl_admin.nome_completo
                   FROM tbl_os_status 
                   JOIN tbl_status_os ON tbl_os_status.status_os = tbl_status_os.status_os
                   JOIN tbl_admin     ON tbl_os_status.admin     = tbl_admin.admin 
                   WHERE tbl_os_status.os = $os
                   AND tbl_os_status.fabrica_status = $login_fabrica
                   AND tbl_status_os.status_os in (13,14,15) 
                   ORDER BY tbl_os_status.data DESC";

    $resExtrato = pg_query($con,$sqlExtrato);
    if(pg_num_rows($resExtrato) > 0) {
 ?>
 <br/>
    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
        <tr>
            <td class='inicio' colspan='6' style="font-size:11pt; text-align: center;">Histórico de Extratos</td>
        </tr>
        <tr>
            <TD class="titulo2">Data</TD>
            <TD class="titulo2">Status</TD>
            <TD class="titulo2">Extrato</TD>
            <TD class="titulo2">Justificativa</TD>
            <TD class="titulo2">Admin</TD>
        </tr>

 <?php 
        $count = pg_num_rows($resExtrato);

            for($i=0; $i < $count; $i++) {
                $data_extrato      = pg_fetch_result($resExtrato, $i, "data");
                $data_extrato      = (new DateTime($data_extrato))->format('d/m/Y H:i:s');
                $desc_extrato      = pg_fetch_result($resExtrato, $i, "descricao");
                $ex_extrato        = pg_fetch_result($resExtrato, $i, "extrato");
                $obs_extrato       = pg_fetch_result($resExtrato, $i, "observacao");
                $nome_adm_extrato  = pg_fetch_result($resExtrato, $i, "nome_completo");
        ?>
        <tr>
            <td class="conteudo"><?=$data_extrato;?></td>
            <td class="conteudo"><?=$desc_extrato;?></td>
            <td class="conteudo"><?=$ex_extrato;?></td>
            <td class="conteudo"><?=$obs_extrato;?></td>
            <td class="conteudo"><?=$nome_adm_extrato;?></td>
        </tr>
    <?php } 
    } ?>
    </table>

<?php } ?>

<?php
    if (in_array($login_fabrica, array(35,169,170,171,178,183,190,195))) {
        $sqlTecnico = "SELECT * FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND ativo IS TRUE;";
        $resTecnico = pg_query($con,$sqlTecnico);
        $countTecnico = pg_num_rows($resTecnico);

        if ($login_fabrica == 178){
            $cond_os_osr = " AND tbl_tecnico_agenda.os_revenda = {$id_os_revenda} ";
        }else{
            $cond_os_osr = " AND tbl_tecnico_agenda.os = {$os} ";
        }

        $sql_agendamento = "
            SELECT  TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY') AS data_agendamento,
                    TO_CHAR(tbl_tecnico_agenda.confirmado, 'DD/MM/YYYY')       AS data_confirmacao,
                    TO_CHAR(tbl_tecnico_agenda.data_cancelado, 'DD/MM/YYYY')   AS data_cancelado,
                    tbl_tecnico.nome AS nome_tecnico,
                    tbl_tecnico_agenda.tecnico_agenda,
                    tbl_tecnico_agenda.periodo,
                    tbl_tecnico_agenda.obs,
                    tbl_justificativa.descricao AS justificativa,
                    tbl_tecnico_agenda.justificativa_cancelado AS motivo_cancelamento
            FROM    tbl_tecnico_agenda
            LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico AND tbl_tecnico.fabrica = {$login_fabrica}
            LEFT JOIN tbl_justificativa USING(justificativa)
            WHERE tbl_tecnico_agenda.fabrica = {$login_fabrica}
            $cond_os_osr
            ORDER BY tbl_tecnico_agenda.tecnico_agenda ASC";
        $res_agendamento = pg_query($con, $sql_agendamento);
       
        $count_agendamento    = pg_num_rows($res_agendamento);
        $tecnico_agenda       = pg_fetch_result($res_agendamento, 0, 'tecnico_agenda');
        $xdata_agendamento    = pg_fetch_result($res_agendamento, 0, 'data_agendamento');
        $confirmado_cancelado = pg_fetch_all($res_agendamento);
        
        if ($count_agendamento > 0 || ($login_fabrica == 35 && $tipo_atendimento == 100)) {
?>
            <br/>
            <table width="700px" border="0" cellspacing="1" id="agendamentos" cellpadding="0" align='center' class='Tabela'>
                <thead>
                    <tr>
                        <td class='inicio' colspan='6' style="font-size:11pt; text-align: center;">Histórico de agendamento</td>
                    </tr>
                    <tr>
                        <TD class="titulo2">#</TD>
                        <td class="titulo2"><?=traduz("data.agendamento")?></td>
                        <td class="titulo2"><?=traduz("periodo")?></td>
                        <td class="titulo2"><?=traduz("data.confirmacao")?></td>
                        <?php if (in_array($login_fabrica,array(169,170,171,178,183,190,195))) {?>
                        <td class="titulo2"><?=traduz("nome.tecnico")?></td>
                            <?php if ($login_fabrica == 183){ ?>
                                <td class="titulo2"><?=traduz("motivo reagendamento")?></td>
                            <?php }else{ ?>
                                <td class="titulo2"><?=traduz("motivo")?></td>
                            <?php } ?>
                        <?php } ?>
                       <?php if (in_array($login_fabrica, array(178,183))){ ?>
                        <td class="titulo2"><?=traduz("data.cancelamento")?></td>
                        <td class="titulo2"><?=traduz("motivo.cancelamento")?></td>
                        <?php } ?>
                        <?php if ($login_fabrica == 35) { ?>
                        <td class="titulo2">Justificativa</td>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $agendamento_confirmado = false;
                $reagendamento = false;

            for ($x = ($count_agendamento - 1); $x >= 0; $x--) {

                $data_agendamento    = pg_fetch_result($res_agendamento, $x, 'data_agendamento');
                $data_confirmacao    = pg_fetch_result($res_agendamento, $x, 'data_confirmacao');
                $nome_tecnico        = pg_fetch_result($res_agendamento, $x, 'nome_tecnico');
                $periodo             = pg_fetch_result($res_agendamento, $x, 'periodo');
                $obs                 = pg_fetch_result($res_agendamento, $x, 'obs');
                $justificativa       = pg_fetch_result($res_agendamento, $x, 'justificativa');
                $xtecnico_agenda     = pg_fetch_result($res_agendamento, $x, 'tecnico_agenda');
                $motivo_cancelamento = pg_fetch_result($res_agendamento, $x, 'motivo_cancelamento');
                $data_cancelado      = pg_fetch_result($res_agendamento, $x, 'data_cancelado');
                
                if (in_array($login_fabrica, array(178,183))){
                    if (!empty($motivo_cancelamento)){
                        $td_color = "style='background-color: #ff6159 !important;'";
                    }else{
                        $td_color = "";
                    }
                }

                if ($periodo == "manha"){
                    $txt_periodo = "Manhã";
                } else if ($periodo == "tarde") {
                    $txt_periodo = "Tarde";
                } else {
                    $txt_periodo = "";
                }

                if ($agendamento_confirmado) {
                    if (in_array($login_fabrica, array(35,178,183)) && !empty($data_confirmacao)) {
                        $confirmacao = $data_confirmacao;
                    } else {
                        $confirmacao = "Agendamento Alterado";
                    }
                } else {
                    if (strlen(trim($data_confirmacao)) > 0) {
                        $confirmacao = $data_confirmacao;
                        $agendamento_confirmado = true;
                    }else if (in_array($login_fabrica, array(178,183))){
                        $confirmacao = "";
                        $reagendamento = true;
                    } else {
                        $confirmacao = "Não confirmado";
                        $reagendamento = true;
                    }
                }
?>
                    <tr>
                        <td <?=$td_color?> class='conteudo'><?=$x + 1?></td>
                        <td <?=$td_color?> class='conteudo'><?=$data_agendamento?></td>
                        <td <?=$td_color?> class='conteudo'><?=$txt_periodo?></td>
<?php                   if (in_array($login_fabrica, [178,183]) AND (empty($data_confirmacao) OR !empty($data_confirmacao) AND !empty($motivo_cancelamento))){ ?>
                            <td <?=$td_color?> class='conteudo' style="text-align: left !important;">
                                
<?php                       if (empty($motivo_cancelamento)){ ?>
                                <div style="width: 235px !important">
                                    <input class="dc_agendamento" style="width: 120px; text-align: center;" type="text" name="dc_agendamento_<?=$x?>" value="">
                                    <button class="agendamento_realizado btn_agendamento_primay" style="margin-top: 5px;" type="button" data-os_revenda="<?=$id_os_revenda?>" data-data_agendada='<?=$data_agendamento?>' data-tecnico_agenda='<?=$xtecnico_agenda?>' > 
                                        <?=traduz("Confirmar.visita")?>
                                    </button>
                                    <br/>
                                    <input placeholder="Motivo do cancelamento" class="motivo_cancelamento" style="width: 125px; text-align: left; margin-top: 10px;" type="text" name="motivo_cancelamento<?=$x?>" value="">
                                    <button class="cancelar_agendamento btn_agendamento_danger" style="margin-top: 5px;" type="button" data-os="<?=$os?>" data-os_revenda="<?=$id_os_revenda?>" data-data_agendada='<?=$data_agendamento?>' data-tecnico_agenda='<?=$xtecnico_agenda?>' > 
                                        <?=traduz("Cancelar.visita")?>
                                    </button>
                                </div>
<?php                   
                            }else{
                                echo traduz("Visita cancelada");
?>
                            </td>
<?php
                            }
                        }else{ 
?>
                        <td <?=$td_color?> class='conteudo'><?=$confirmacao?></td>
<?php
                        }
                if (in_array($login_fabrica,array(169,170,171,190,195))) {
?>
                        <td <?=$td_color?> class='conteudo'><?=$nome_tecnico?></td>
                        <td <?=$td_color?> class='conteudo'><?=utf8_decode($obs)?></td>
<?php
                }
                if (in_array($login_fabrica, array(178,183))){
?>          
                    <td <?=$td_color?> class='conteudo'><?=$nome_tecnico?></td>
                    
<?php               if (strlen($data_confirmacao) > 0  AND empty($motivo_cancelamento)) {  ?>              
                        <td class='conteudo' colspan="3">
                            <input placeholder="Motivo do cancelamento" class="motivo_cancelamento" style="width: 110px; text-align: left; margin-top: 10px;" type="text" name="motivo_cancelamento<?=$x?>" value="">
                            <button class="cancelar_agendamento btn_agendamento_danger" type="button" data-os="<?=$os?>" data-os_revenda="<?=$id_os_revenda?>" data-data_agendada='<?=$data_agendamento?>' data-tecnico_agenda='<?=$xtecnico_agenda?>' > 
                                <?=traduz("Cancelar.visita")?>
                            </button> 
                        </td>
<?php               } else { ?>                    
                        <td <?=$td_color?> class='conteudo'><?=utf8_decode($obs)?></td>
                        <td <?=$td_color?> class='conteudo'><?=$data_cancelado?></td>
                        <td <?=$td_color?> class='conteudo'><?=utf8_decode($motivo_cancelamento)?></td>
<?php               } 
                }

                if ($login_fabrica == 35) {
?>
                        <td class="conteudo"><?=$justificativa?></td>
<?php
                }
?>
                    </tr>
<?php
            }
?>
                </tbody>
<?php
            if (empty($data_fechamento)) {
                $sqlOsTroca = "
                    SELECT os_troca
                    FROM tbl_os_troca
                    WHERE os = {$os}
                ";
                $resOsTroca = pg_query($con, $sqlOsTroca);
                if (in_array($login_fabrica, array(169,170)) AND !empty($os_item)){
                    $mostra_reagendamento = "style='display:none;'";
                }else{
                    if (in_array($login_fabrica, array(178,183))){
                        $confirmado_cancelado = end($confirmado_cancelado);
                        if (empty($confirmado_cancelado["data_confirmacao"]) AND empty($confirmado_cancelado["data_cancelado"])){
                            $mostra_reagendamento = "style='display:none;'";
                        }else{
                            $mostra_reagendamento = "";
                        }
                    }else{
                        $mostra_reagendamento = "";
                    }
                }
                if (pg_num_rows($resOsTroca) == 0) {
                    if (empty($os_numero) || !$os_conjunto) {

?>
                            <tfoot <?=$mostra_reagendamento?> >
                                <tr>
                                    <td colspan="8" align="center">
                                        <button id="reagendar_os" type="button"> <?=(in_array($login_fabrica, [35,178]) && !$reagendamento) ? "Nova Visita" : traduz("reagendar.os")?></button>
                                    </td>
                                </tr>
                            </tfoot>
<?php
                    } else { ?>
                            <tfoot <?=$mostra_reagendamento?> >
                                <tr>
                                    <td colspan="6" align="center" style="color: #FFF; font-weight: bold;">
                                        <?= traduz("o.agendamento.deve.ser.efetuado.atraves.da.ordem.de.servico.de.origem") ?>
                                    </td>
                                </tr>
                            </tfoot>
                        <?php
                    }
                }
            }
?>
            </table>
<?php
        }
?>
        <table class="tabela_agenda" style="min-width:700px;margin:auto; display: none;" cellspacing="1" id="rel_agenda">
            <input type="hidden" id="posto" name="posto" value="<?= $login_posto; ?>" />
            <thead>
                <tr class="titulo_tabela">
                    <th colspan="6">Agenda de Visitas</th>
                </tr>
                <tr class="titulo_coluna">
                    <th>OS</th>
                    <th><?=traduz("data.agendamento")?></th>
                    <th><?=traduz("periodo")?></th>
<?php
            if (in_array($login_fabrica,array(169,170,171,178,183,190,195))) {
?>
                    <th><?=traduz("tecnico")?></th>
                    <th><?=traduz("motivo.reagendamento")?></th>
<?php
            }
            if ($login_fabrica == 35) {
?>
                    <th>Justificativa</th>
<?php
            }
?>
                    <th><?=traduz("opcoes")?></th>
                </tr>
            </thead>
            <tbody>
                    <tr class="texto_avulso linha_agenda_<?= $tecnico_agenda; ?>">
                        <td class='conteudo' style="text-align: center;"><a href="os_press.php?os=<?= $os; ?>" target="_blank"><?= $os; ?></a></td>
                        <td class='conteudo' style="text-align: center;"><input readonly type="text" id="data_agendamento_novo" name="data_agendamento_novo" max-length="10" size="10" /></td>
                        <td class="conteudo" style="text-align: center;">
                            <select id="periodo" name='periodo' class="frm">
                                <option value=""><?= traduz("selecione") ?></option>
                                <option value="manha">Manhã</option>
                                <option value="tarde">Tarde</option>
                            </select>
                        </td>
<?php
            if (in_array($login_fabrica,array(169,170,171,178,183,190,195))) {
?>
                        <td class='conteudo' style="text-align: center;">
                            <select id="tecnico" name="tecnico" class="frm">
                                <option value=""><?= traduz("selecione") ?></option>
                                <? for ($t = 0; $t < $countTecnico; $t++) {
                                    $resIdTecnico = pg_fetch_result($resTecnico, $t, tecnico);
                                    $resNome = pg_fetch_result($resTecnico, $t, nome);
                                    $select = ($tecnico == $resIdTecnico) ? "SELECTED" : ""; ?>
                                    <option value="<?= $resIdTecnico; ?>"><?= $resNome; ?></option>
                                <? } ?>
                            </select>
                        </td>
                        <td class='conteudo' style="text-align: center;">
                            <textarea id='obs_motivo_agendamento' name='obs_motivo_agendamento'></textarea>
                        </td>
<?php
            }
            if ($login_fabrica == 35) {
?>
                        <td>
                            <select value="justificativa" id="justificativa" class="frm">
                                <option value="">Selecione</option>
<?php
                $sqlJust = "
                    SELECT  tbl_justificativa.justificativa,
                            tbl_justificativa.descricao
                    FROM    tbl_justificativa
                    WHERE   tbl_justificativa.fabrica = $login_fabrica
                    AND     tbl_justificativa.ativa IS TRUE
              ORDER BY      descricao
                ";
                $resJust = pg_query($con,$sqlJust);

                while ($just = pg_fetch_object($resJust)) {
?>
                                <option value="<?=$just->justificativa?>"><?=$just->descricao?></option>
<?php
                }
?>
                            </select>
                        </td>
<?php
            }
?>
                        <td class='conteudo' style="text-align: center;">
                            <input type="hidden" id="hd_chamado" name="hd_chamado" value="<?=$hd_chamado?>" />
                            <input type="hidden" id="data_agendamento" name="data_agendamento" value="<?=$xdata_agendamento?>" />
                            <button type="button" class="frm btn_confirmar" data-tecnico-agenda="<?=$tecnico_agenda?>" data-os_revenda="<?=$id_os_revenda?>" data-os="<?=$os?>"><?=traduz("confirmar")?></button>
                        </td>
                    </tr>
            </tbody>
        </table>
<?php

        if (in_array($login_fabrica, array(169,170))){
            if (strlen(trim($hd_chamado)) > 0){
                $sql_lgr_correios = "
                    SELECT hd_chamado_postagem, tbl_hd_chamado_postagem.admin
                    FROM tbl_hd_chamado_postagem
                    WHERE fabrica = $login_fabrica
                    AND hd_chamado = $hd_chamado
                    ORDER BY hd_chamado_postagem DESC LIMIT 1";
                $res_lgr_correios = pg_query($con, $sql_lgr_correios);

                if (pg_num_rows($res_lgr_correios) > 0){
                    $admin_postagem = pg_fetch_result($res_lgr_correios, 0, 'admin');

                    if(strlen($admin_postagem) > 0){
                        $postagem_coleta = "true";
                    }
                }
            }
        }
    }
?>

<!--            Valores da OS           -->
<?php
if (in_array($login_fabrica,array(20,30,50,15,94,120))  or ($login_fabrica == 42 && !empty($extrato))) {

    $pecas              = 0;
    $mao_de_obra        = 0;
    $tabela             = 0;
    $desconto           = 0;
    $desconto_acessorio = 0;

    $sql = "SELECT mao_de_obra
            FROM tbl_produto_defeito_constatado
            WHERE produto = (
                SELECT produto
                FROM tbl_os
                WHERE os = $os
            )
            AND defeito_constatado = (
                SELECT defeito_constatado
                FROM tbl_os
                WHERE os = $os
            )";
    /* HD 19054 */
    if ($login_fabrica==50 || $login_fabrica == 20 or $login_fabrica == '120' ){
        $sql = "SELECT mao_de_obra, valores_adicionais, qtde_km_calculada
                FROM tbl_os
                WHERE os = $os
                AND fabrica = $login_fabrica";
    }

    $res = pg_query ($con,$sql);

    $valor_adicional = 0;
    $valor_km_calc = 0;
    if (pg_num_rows ($res) == 1) {
        $mao_de_obra = pg_fetch_result ($res,0,mao_de_obra);
        $valor_adicional = pg_fetch_result($res, 0, 'valores_adicionais');
        $valor_km_calc = pg_fetch_result($res, 0, 'qtde_km_calculada');

        if ($login_fabrica == '120' and empty($mao_de_obra)) {
            $sql_prod_mo = "SELECT mao_de_obra, valores_adicionais FROM tbl_produto WHERE produto = $produto";
            $qry_prod_mo = pg_query($con, $sql_prod_mo);

            if (pg_num_rows($qry_prod_mo) > 0) {
                $mao_de_obra = pg_fetch_result($qry_prod_mo, 0, 'mao_de_obra');
                $valores_adicionais_prod = pg_fetch_result($qry_prod_mo, 0, 'valores_adicionais');

                $arr_vals = json_decode(str_replace(',', '.', $valores_adicionais_prod), TRUE);

                $valor_adicional = 0;

                foreach ($arr_vals as $val) {
                    $valor_adicional+= (float) $val;
                }
            }
        }
    }

    $sql = "SELECT  tabela,
                    desconto,
                    desconto_acessorio
            FROM  tbl_posto_fabrica
            WHERE posto = $login_posto
            AND   fabrica = $login_fabrica";
    $res = pg_query ($con,$sql);

    if (pg_num_rows ($res) == 1) {
        $tabela             = pg_fetch_result ($res,0,tabela)            ;
        $desconto           = pg_fetch_result ($res,0,desconto)          ;
        $desconto_acessorio = pg_fetch_result ($res,0,desconto_acessorio);
    }

    if (strlen ($desconto) == 0) $desconto = "0";

    if (strlen ($tabela) > 0) {

        $specialChar = array("(",")",".");
        $os = str_replace($specialChar, "", $os);

        $sql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
                FROM tbl_os
                JOIN tbl_os_produto USING (os)
                JOIN tbl_os_item    USING (os_produto)
                JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
                WHERE tbl_os.os = $os";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1) {
            $pecas = pg_fetch_result ($res,0,0);
        }
    } else {
        $pecas = "0";
    }

    echo "<br><table cellpadding='10' cellspacing='0' border='1' align='center' style='border-collapse: collapse' bordercolor='#485989'>";
    echo "<tr style='font-size: 12px ; color:#53607F ' >";


    if (in_array($login_fabrica,array(15,30,50,94))){
        /* HD 24461 - Francisco Ambrozio - ocultar campo Valor Deslocamento,
            caso este for igual a 0*/

        $specialChar = array("(",")",".");
        $os = str_replace($specialChar, "", $os);

        $sql = "SELECT tbl_os.qtde_km_calculada
                FROM tbl_os
                LEFT JOIN tbl_os_extra USING(os)
                WHERE tbl_os.os = $os
                    AND tbl_os.fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);
        $qte_km_vd = pg_fetch_result ($res,0,qtde_km_calculada);
        if ($qte_km_vd<>0){
            echo "<td align='center' bgcolor='#E1EAF1'><b>";
            fecho ("valor.deslocamento",$con,$cook_idioma);
            echo "</b></td>";
        }
        if($login_fabrica == 30){
            echo "<td align='center' bgcolor='#E1EAF1'><b>";
            fecho ("valor.das.pecas",$con,$cook_idioma);
            echo "</b></td>";
	/*		// 2018-06-06: O chamado 3264913 que implementou os campos abaixo não era pra ter ido pra produção ainda
			//unset($os_campos_adicionais['avaliacao']);
			//unset($os_campos_adicionais['taxa_entrega']);
            if(strlen(trim($os_campos_adicionais['avaliacao']))>0){
                echo "<td align='center' bgcolor='#E1EAF1'><b>";
                fecho ("avaliacao",$con,$cook_idioma);
                echo "</b></td>";
            }
            if(strlen(trim($os_campos_adicionais['taxa_entrega']))>0){
                echo "<td align='center' bgcolor='#E1EAF1'><b>";
                fecho ("taxa.entrega",$con,$cook_idioma);
                echo "</b></td>";
            }*/
        }

        echo "<td align='center' colspan='2' bgcolor='#E1EAF1'><b>".traduz("mao.de.obra",$con,$cook_idioma)."</b></td>";
        echo "<td align='center' bgcolor='#E1EAF1'><b>".traduz("total",$con,$cook_idioma)."</b></td>";

    } else {
        echo "<td align='center' bgcolor='#E1EAF1'><b>";

        if ($login_fabrica == '120') {
            fecho ("valor.adicional");
        } else {
            fecho ("valor.das.pecas",$con,$cook_idioma);
        }
        echo "</b></td>";
        if ($login_fabrica == '120') {
            echo "<td align='center' bgcolor='#E1EAF1'><b>Total KM</b></td>";
        }
        echo "<td align='center' bgcolor='#E1EAF1'><b>";
        fecho ("mao.de.obra",$con,$cook_idioma);
        echo "</b></td>";
        if($sistema_lingua=='ES'){
            echo "<td align='center' bgcolor='#E1EAF1'><b>";
            fecho ("desconto.iva",$con,$cook_idioma);
            echo "</b></td>";
        }
        echo "<td align='center' bgcolor='#E1EAF1'><b>".traduz("total",$con,$cook_idioma)."</b></td>";
    }
    echo "</tr>";

    $valor_liquido = 0;

    if ($desconto > 0 and $pecas <> 0) {
        $sql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);
        if (pg_num_rows ($res) == 1) {
            $produto = pg_fetch_result ($res,0,0);
        }
        //echo 'peca'.$pecas;
        if( $produto == '20567' ){
            $desconto_acessorio = '0.2238';
            $valor_desconto = round ( (round ($pecas,2) * $desconto_acessorio ) ,2);

        } else {
            $valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
        }

        $valor_liquido = $pecas - $valor_desconto ;

    }

    if($login_fabrica==20 ){
        $sql = "select pais from tbl_os join tbl_posto on tbl_os.posto = tbl_posto.posto where os = $os;";
        $res = pg_query ($con,$sql);
        if (pg_num_rows ($res) >0) {
            $sigla_pais = pg_fetch_result ($res,0,pais);
        }
    }

    $acrescimo = 0;

    if(strlen($sigla_pais)>0 and $sigla_pais <> "BR") {
        $sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1) {
            $valor_liquido = pg_fetch_result ($res,0,pecas);
            $mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
        }
        $sql = "select imposto_al  from tbl_posto_fabrica where posto=$login_posto and fabrica=$login_fabrica";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1) {
            $imposto_al   = pg_fetch_result ($res,0,imposto_al);
            $imposto_al   = $imposto_al / 100;
            $acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
        }
    }

    //Foi comentado HD chamado 17175 4/4/2008

    //HD 9469 - Alteração no cálculo da BOSCH do Brasil
    if($login_pais=="BR") {
        $sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
        $res = pg_query ($con,$sql);
        if (pg_num_rows ($res) == 1) {
            $valor_liquido = pg_fetch_result ($res,0,pecas);
            //$mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
        }
    }

    if($login_fabrica == 30 || $login_fabrica == 42){ // makita hd 341053
        $sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1) {
            $valor_liquido = pg_fetch_result ($res,0,pecas);
            $mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
        }
	}elseif($login_fabrica <> 20){
		$valor_liquido = 0 ;
	}

    /* HD 19054 */
    $valor_km = 0;
    if(in_array($login_fabrica,array(15,30,50,94))){

        $specialChar = array("(",")",".");
        $os = str_replace($specialChar, "", $os);

        $sql = "SELECT  tbl_os.mao_de_obra,
                        tbl_os.qtde_km_calculada,
                        tbl_os_extra.extrato
                FROM tbl_os
                LEFT JOIN tbl_os_extra USING(os)
                WHERE tbl_os.os = $os
                AND   tbl_os.fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1) {
            $mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
            $valor_km      = pg_fetch_result ($res,0,qtde_km_calculada);
            $extrato       = pg_fetch_result ($res,0,extrato);
        }
    }

    $total = $valor_liquido + $mao_de_obra + $acrescimo + $valor_km;

    if ($login_fabrica == '120') {
        if (empty($valor_km_calc)) {
            $sql_km_posto = "SELECT valor_km from tbl_posto_fabrica where posto = $login_posto and fabrica = $login_fabrica";
            $qry_km_posto = pg_query($con, $sql_km_posto);
            $val_km_posto = 0;

            if (pg_num_rows($qry_km_posto) > 0) {
                $val_km_posto = (float) pg_fetch_result($qry_km_posto, 0, 'valor_km');
            }

            if ($val_km_posto == 0) {
                $sql_km_posto = "SELECT valor_km from tbl_fabrica where fabrica = $login_fabrica";
                $qry_km_posto = pg_query($con, $sql_km_posto);

                if (pg_num_rows($qry_km_posto) > 0) {
                    $val_km_posto = (float) pg_fetch_result($qry_km_posto, 0, 'valor_km');
                }
            }

            $valor_km_calc = $val_km_posto * $qtde_km;
        }

        $total = $valor_adicional + $mao_de_obra + $valor_km_calc;
    }

    $total          = number_format ($total,2,",",".")         ;
    $mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
    $acrescimo      = number_format ($acrescimo ,2,",",".")    ;
    $valor_desconto = number_format ($valor_desconto,2,",",".");
    $valor_liquido  = number_format ($valor_liquido ,2,",",".");
    $valor_km       = number_format ($valor_km ,2,",",".");
    $valor_adicional = number_format($valor_adicional, 2, ',', '.');
    $valor_km_calc = number_format($valor_km_calc, 2, ',', '.');

    echo "<tr style='font-size: 12px ; background:white;'>";
    /* HD 19054 */
    if (in_array($login_fabrica,array(15,30,50,94))){
        /* HD 24461 - Francisco Ambrozio - ocultar campo Valor Deslocamento,
            caso este for igual a 0*/
        if ($valor_km<>0){
            echo "<td align='right'><font color='#333377'><b>$valor_km</b></td>";
        }
        if($login_fabrica == 30){
            echo "<td align='center'><font color='#333377'><b>$valor_liquido</b></td>" ;

            if(strlen(trim($os_campos_adicionais['avaliacao']))>0){
                echo "<td align='center'><font color='#333377'><b>".number_format ($os_campos_adicionais['avaliacao'] ,2,",",".") ."</b></td>" ;
                $total += $os_campos_adicionais['avaliacao'];
            }
            if(strlen(trim($os_campos_adicionais['taxa_entrega']))>0){
                echo "<td align='center'><font color='#333377'><b>".number_format ($os_campos_adicionais['taxa_entrega'] ,2,",",".")."</b></td>" ;
                $total += $os_campos_adicionais['taxa_entrega'];
            }
        }
        echo "<td align='center' colspan='2'>$mao_de_obra</td>";
        echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>".$total ."</b></font></td>";

    } else {
        if ($login_fabrica == '120') {
            echo "<td align='right'><font color='#333377'><b>$valor_adicional</b></td>" ;
            echo "<td align='right'><font color='#333377'><b>$valor_km_calc</b></td>" ;
        } else {
            echo "<td align='right'><font color='#333377'><b>$valor_liquido</b></td>" ;
        }
        echo "<td align='center'>$mao_de_obra</td>";
        if($sistema_lingua=='ES')echo "<td align='center'>+ $acrescimo</td>";
        echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
    }
    echo "</tr>";

    /* HD 19054 */
    if ($login_fabrica==50 and strlen($extrato)==0){
        echo "<tr style='font-size: 12px ; color:#000000 '>";
        echo "<td colspan='3'>";
        echo "<font color='#757575'>".traduz("valores.sujeito.a.alteracao.ate.fechamento.do.extrato",$con,$cook_idioma) ;
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";

}

if ($login_fabrica==2 and strlen($data_finalizada)==0 and $login_posto==6359){
    $status_os = "";
    $sql_status = "SELECT status_os
                    FROM tbl_os_status
                    WHERE os = $os
                    AND status_os IN (72,73,62,64,65,87,88)
                    ORDER BY data DESC
                    LIMIT 1";
    $res_status = pg_query($con,$sql_status);
    if (pg_num_rows($res_status) >0) {
        $status_os = pg_fetch_result ($res_status,0,status_os);
    }
    if ($status_os != "65"){
        echo "<br>";
        echo "<a href='".$PHP_SELF."?os=$os&inter=1'>".strtoupper(traduz("enviar.produto.para.centro.de.reparo",$con,$cook_idioma))."</a>";
        echo "<br>";
    }
}

    if ($login_fabrica == 3) {
?>
        <br />

        <table style="width: 700px; margin: 0 auto; cursor: pointer;" class="Tabela" border="0" cellspacing="1" cellpadding="0" onclick="window.open('helpdesk_cadastrar.php?os=<?php echo $sua_os; ?>', '_blank');" >
            <tr>
                <th style='color:white'><?= traduz("enviar.duvidas.tecnicas./.administrativas.para.a.fabrica") ?></th>
            </tr>
        </table>

        <?php
        $sql_hd_chamado = "SELECT
                                tbl_hd_chamado_posto.seu_hd,
                                tbl_hd_chamado.hd_chamado,
                                tbl_hd_chamado.categoria,
                                tbl_hd_chamado.status
                           FROM tbl_hd_chamado
                           JOIN tbl_hd_chamado_extra USING (hd_chamado)
                           LEFT JOIN tbl_hd_chamado_posto USING (hd_chamado)
                           WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
                           AND tbl_hd_chamado_extra.os = {$os}";
        $res_hd_chamado = pg_query($con, $sql_hd_chamado);

        if (pg_num_rows($res_hd_chamado) > 0) {
        ?>
            <br />
            <table style="width: 700px; margin: 0 auto;" class="Tabela" border="0" cellspacing="1" cellpadding="0">
                <tr>
                    <td class="inicio" style="text-align: center;" colspan="5"><?= traduz("historico.de.chamados") ?></td>
                </tr>
                <tr>
                    <td class="titulo2">Nº Chamado</td>
                    <td class="titulo2"><?= traduz("tipo.de.solicitacao") ?></td>
                    <td class="titulo2">Status</td>
                    <td class="titulo2"><?= traduz("data.da.ultima.interacao.do.posto") ?></td>
                    <td class="titulo2"><?= traduz("data.da.ultima.interacao.do.fabrica") ?></td>
                </tr>
                <?php
                for ($k = 0; $k < pg_num_rows($res_hd_chamado); $k++) {
                    $seu_hd     = pg_fetch_result($res_hd_chamado, $k, "seu_hd");
                    $hd_chamado = pg_fetch_result($res_hd_chamado, $k, "hd_chamado");
                    $categoria  = pg_fetch_result($res_hd_chamado, $k, "categoria");
                    $status     = pg_fetch_result($res_hd_chamado, $k, "status");


                    $categoria = $categorias[$categoria]['descricao'];

                    unset($data_ultima_interacao_posto, $data_ultima_interacao_admin);

                    $sql_hd_chamado_item_posto = "SELECT TO_CHAR(data, 'DD/MM/YYYY HH24:MI') AS data_ultima_interacao_posto
                                                  FROM tbl_hd_chamado_item
                                                  WHERE hd_chamado = {$hd_chamado}
                                                  AND admin IS NULL
                                                  ORDER BY hd_chamado_item DESC
                                                  LIMIT 1";
                    $res_hd_chamado_item_posto = pg_query($con, $sql_hd_chamado_item_posto);

                    if (pg_num_rows($res_hd_chamado_item_posto) > 0) {
                        $data_ultima_interacao_posto = pg_fetch_result($res_hd_chamado_item_posto, 0, "data_ultima_interacao_posto");
                    }

                    $sql_hd_chamado_item_admin = "SELECT TO_CHAR(data, 'DD/MM/YYYY HH24:MI') AS data_ultima_interacao_admin
                                                  FROM tbl_hd_chamado_item
                                                  WHERE hd_chamado = {$hd_chamado}
                                                  AND admin IS NOT NULL
                                                  ORDER BY hd_chamado_item DESC
                                                  LIMIT 1";
                    $res_hd_chamado_item_admin = pg_query($con, $sql_hd_chamado_item_admin);

                    if (pg_num_rows($res_hd_chamado_item_admin) > 0) {
                        $data_ultima_interacao_admin = pg_fetch_result($res_hd_chamado_item_admin, 0, "data_ultima_interacao_admin");
                    }

                    echo "<tr>
                        <td class='justificativa'><a href='helpdesk_cadastrar.php?hd_chamado={$hd_chamado}' target='_blank'>".((!empty($seu_hd)) ? $seu_hd : $hd_chamado)."</a></td>
                        <td class='justificativa'>{$categoria}</td>
                        <td class='justificativa'>{$status}</td>
                        <td class='justificativa'>{$data_ultima_interacao_posto}</td>
                        <td class='justificativa'>{$data_ultima_interacao_admin}</td>
                    </tr>";
                }
                ?>
            </table>
        <?php
        }
    }

    if($login_fabrica == 141 && $posto_interno == true){

        include_once "class/aws/s3_config.php";
        include_once S3CLASS;

        $s3 = new AmazonTC("os", $login_fabrica);

        $nota_fiscal_saida = $s3->getObjectList("nota-fiscal-saida-{$os}");
        $nota_fiscal_saida = basename($nota_fiscal_saida[0]);

        $ext = explode(".", $nota_fiscal_saida);

        $nota_fiscal_saida = ($ext[1] == "pdf") ? "imagens/pdf_icone.png" : $s3->getLink($nota_fiscal_saida);

        if(strlen($nota_fiscal_saida) > 0){

            echo "<br />";

            echo "
            <table width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";

                echo "
                    <tr>
                        <td align='center'>
                            <font size='2' color='#fff'><center><b>".traduz("anexo.de.nota.fiscal.de.saida")."</b></font>
                        </td>
                    </tr>";

            echo "<tr>
                    <td class='conteudo' style='text-align: center !important;'>";
                        echo "
                            <br />
                                <a href='{$nota_fiscal_saida}' target='_blank'><img src='{$nota_fiscal_saida}' style='max-width: 150px; max-height: 150px;_height:150px;*height:150px;' /></a>
                            <br /> <br />";
                    echo "
                    </td>
                </tr>
            </table>";

        }

    }

    if (in_array($login_fabrica,array(11,35,104,172))){ // HD-2357100

        $sql_postagem = "
            SELECT  tbl_os.os,
                    tbl_os.data_fechamento,
                    tbl_os.hd_chamado,
                    tbl_hd_chamado_postagem.numero_postagem,
                    tbl_hd_chamado_extra.hd_chamado AS solicitacao
            FROM    tbl_os
            JOIN    tbl_hd_chamado_extra    ON (tbl_hd_chamado_extra.os          = tbl_os.os or tbl_hd_chamado_extra.hd_chamado = tbl_os.hd_chamado)
            JOIN    tbl_hd_chamado_postagem ON tbl_hd_chamado_extra.hd_chamado  = tbl_hd_chamado_postagem.hd_chamado
            WHERE   tbl_os.os                               = $os
            AND     tbl_os.fabrica                          = $login_fabrica
            AND     tbl_hd_chamado_postagem.numero_postagem IS NOT NULL
            AND     tbl_os.data_fechamento                  IS NOT NULL";
        $res_postagem = pg_query($con, $sql_postagem);

        if (pg_num_rows($res_postagem) > 0) {

            $solicitacao = pg_fetch_result($res_postagem, 0, 'solicitacao');
            $valueBotao = "Solicitar Logistica Reversa Correios";
?>
            <br>
            <table width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
            <tr>
                <td><font size='2' color='#FFFFFF'><center><b><? echo strtoupper(traduz("solicitar.postagem",$con,$cook_idioma)); ?></b></center></font></td>
            </tr>
            <tr>
                <td class="conteudo">
                    <table>
                        <td align='left'><strong><?= traduz("tipo.postagem") ?></strong></td>
                         <td align='left'>
                            <select name='tipo_postagem' id='tipo_postagem'>
                                <option value=''>Escolha</option>
                                <option value='A' <?=($tipo_postagem == 'A') ? "selected" : ""?>>Autorização de Postagem</option>
                            </select>
                        </td>
                        <td style="padding-left: 190px;">
                            <input name="btn_lgr" id="btn_lgr" value='<?=$valueBotao?>' type='button' onclick='solicitaPostagemPosto(<?=$solicitacao?>,<?=$os?>)'>
                        </td>
                    </table>
                </td>
            </tr>
            </table>
<?php
        }
    }

     /**
     * Interação na Ordem de Serviço
     */

    $array_sms = array(104);
    /**
     * Interação na Ordem de Serviço
     */

    $array_interacao_os = array(3,11,14,19,24,30,35,40,45,50,51,52,72,74,80,81,85,86,90,91,96,101,104,114,122,123,125,126,127,131,132,134,136,172);

    if ($login_fabrica >= 137) {
        $array_interacao_os = array($login_fabrica);
    }



    if(in_array($login_fabrica, array(50, 151))){
        $sqlHelpDesk = "SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado.status
            FROM tbl_hd_chamado
                INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
            WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
            AND tbl_hd_chamado.titulo = 'Help-Desk Posto'
            AND tbl_hd_chamado_extra.os = {$os}";
        $resHelpDesk = pg_query($con,$sqlHelpDesk);

        if(pg_num_rows($resHelpDesk) > 0){
        ?>
        <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
            <thead class="Tabela inicio">
                <tr>
                    <th style="text-align:center; font-size:12px;"><?= traduz("historico.de.helpdesk") ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="titulo2" style="width:200px;"><?= traduz('atendimento') ?></td>
                    <td class="titulo2">STATUS</td>
                </tr>
                <?php
                while($objeto_hd = pg_fetch_object($resHelpDesk)){
                    ?>
                    <tr class="conteudo">
                        <td align="center"><a target="_blank" href="helpdesk_posto_autorizado_atendimento.php?hd_chamado=<?=$objeto_hd->hd_chamado?>"><?=$objeto_hd->hd_chamado?></a></td>
                        <td align="center"><?=$objeto_hd->status?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <?php
        }
    }

    if (in_array($login_fabrica,array(1,104))) {
        if ($login_fabrica == 104) {
            $sql = "
            SELECT
            tbl_hd_chamado.hd_chamado,
            TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YY') AS data

            FROM
            tbl_hd_chamado
            JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado

            WHERE
            tbl_hd_chamado_extra.os=$os
            AND tbl_hd_chamado.posto ISNULL
            ORDER BY
            tbl_hd_chamado.data ASC
            ";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res)) {
                echo "
                <TABLE width='300px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>
                <br>
                <TR>
                    <TD colspan='2' class='inicio'>ATIVIDADE DE CHAMADOS NO CALLCENTER</TD>

                </TR>
                <TR>
                    <TD class='titulo2'>CHAMADO</TD>
                    <TD class='titulo2'>DATA</TD>
                </TR>";

                for($h = 0; $h < pg_num_rows($res); $h++) {
                    $hd_chamado = pg_fetch_result($res, $h, hd_chamado);
                    $data = pg_fetch_result($res, $h, data);

                    echo "
                    <TR>
                        <TD class=conteudo style='text-align:center'>
                            $hd_chamado
                        </TD>
                        <TD class=conteudo style='text-align:center'>
                            $data
                        </TD>
                    </TR>";
                }

                echo "
                </TABLE>";
            }
        }

        if ($login_fabrica == 104) {
            $join .= "
                FROM    tbl_hd_chamado_postagem
                JOIN    tbl_hd_chamado          ON  tbl_hd_chamado.hd_chamado               = tbl_hd_chamado_postagem.hd_chamado
                                                AND tbl_hd_chamado_postagem.fabrica         = $login_fabrica
                JOIN    tbl_hd_chamado_extra    ON  tbl_hd_chamado_extra.hd_chamado         = tbl_hd_chamado.hd_chamado
                JOIN    tbl_os                  ON  tbl_os.os                               = tbl_hd_chamado_extra.os
                JOIN    tbl_faturamento_correio ON  (
                                                    tbl_faturamento_correio.numero_postagem = tbl_hd_chamado_postagem.numero_postagem
                                                OR  tbl_faturamento_correio.numero_postagem = tbl_os.autorizacao_domicilio
                                                    )
                                                AND tbl_faturamento_correio.fabrica         = $login_fabrica
            ";
        } else if ($login_fabrica == 1) {
            $join .= "
                FROM    tbl_faturamento_correio
                JOIN    tbl_os_campo_extra  ON JSON_FIELD('numero_postagem',tbl_os_campo_extra.campos_adicionais)   = tbl_faturamento_correio.numero_postagem
                JOIN    tbl_os              ON tbl_os.os                                                            = tbl_os_campo_extra.os
            ";
        }

        $sql = "SELECT  tbl_faturamento_correio.conhecimento,
                        tbl_faturamento_correio.numero_postagem

                $join
                WHERE   tbl_os.os       = $os
                AND     tbl_os.fabrica  = $login_fabrica
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res)) {
            echo "
            <TABLE width='300px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>
            <br>
            <TR>
                <TD colspan='2' class='inicio'>HISTÓRICO DE POSTAGEM</TD>

            </TR>
            <TR>
                <TD class='titulo2'>NÚMERO DA POSTAGEM</TD>
                <TD class='titulo2'>CONHECIMENTO</TD>
            </TR>";

            for($h = 0; $h < pg_num_rows($res); $h++) {
                $conhecimento = pg_fetch_result($res, $h, conhecimento);
                $numero_postagem = pg_fetch_result($res, $h, numero_postagem);

                echo "
                <TR>
                    <TD class=conteudo style='text-align:center'>
                        $numero_postagem
                    </TD>
                    <TD class=conteudo style='text-align:center'>
                    <a HREF='./relatorio_faturamento_correios.php?conhecimento=$conhecimento' rel='shadowbox'>
                        $conhecimento</a>
                    </TD>
                </TR>";
            }

            echo "
            </TABLE>";
        }
    }

    if ((in_array($login_fabrica, $array_interacao_os) || $interacaoOsPosto) && !isset($_GET["verifica_distrib_geral"])) {
        if ($excluida == "t") {
            $cancelada = "&cancelada=true";
        }
        ?>
        <br />
        <center>
            <iframe id="iframe_interacao_os" src="interacao_os.php?os=<?=$os?>&iframe=true<?=$cancelada?>" style="width: 700px;" frameborder="0" scrolling="no"></iframe>
        </center>
        <br />
    <?php
    }

    if (in_array($login_fabrica, $array_sms) and file_exists('sms_consumidor.php') ) { ?>
        <br />
        <iframe id="iframe_enviar_sms" src="sms_consumidor.php?os=<?=$os?>&iframe=true&consumidor_celular=<?=$consumidor_celular?>" style="width: 700px;" frameborder="0" scrolling="no" ></iframe>
        <br />
    <?php
    } 

    /**
     * FIM Interação na Ordem de Serviço
     */


if ($login_fabrica == 35) {
        $sqlPostagem = "SELECT x.numero_postagem,to_char(x.data,'DD/MM/YYYY') AS data_postagem,
                        (SELECT (conhecimento,to_char(data,'DD/MM/YYYY'),situacao,obs)
                        FROM tbl_faturamento_correio
                        WHERE fabrica = $login_fabrica
                        AND tbl_faturamento_correio.numero_postagem = x.numero_postagem
                        ORDER BY data DESC LIMIT 1) as fat_correio
                        FROM tbl_hd_chamado_postagem x
                        WHERE x.os = $os;";

        $resPostagem = pg_query($con, $sqlPostagem);

        if (pg_num_rows($resPostagem) > 0) {
            echo "<br>";
            echo "<table width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela Postagem'>";
            echo "<tr>";
            echo "<td class='titulo_tabela' width='10%' colspan='6' align='center'>".traduz("historico.de.postagem",$con,$cook_idioma)."</td>";
            echo "</tr>";
            for ($i = 0 ; $i < pg_num_rows ($resPostagem) ; $i++) {
                $numero_postagem = pg_fetch_result($resPostagem, $i, numero_postagem);
                $data_postagem = pg_fetch_result($resPostagem, $i, data_postagem);
                $fat_correio = explode(',', pg_fetch_result($resPostagem, $i, fat_correio));
                $conhecimento = explode('(', $fat_correio[0])[1];
                $situacao = str_replace('"', ' ', $fat_correio[2]);
                $data_situacao = $fat_correio[1];
                $observacao = str_replace('"', ' ', $fat_correio[3]);
                echo "<tr>";
            }
            echo "</tr>";
                echo "<td class='titulo'>".traduz("numero.postagem",$con,$cook_idioma )."</td>";
                echo "<td class='titulo'>".traduz("data.postagem",$con,$cook_idioma)."</td>";
                echo "<td class='titulo'>".traduz("numero objeto",$con,$cook_idioma)."</td>";
                echo "<td class='titulo'>".traduz("situacao",$con,$cook_idioma)."</td>";
                echo "<td class='titulo'>".traduz("data.situacao",$con,$cook_idioma)."</td>";
                echo "<td class='titulo'>".traduz("observacao",$con,$cook_idioma)."</td>";
            echo "</tr>";
            echo "<tr>";
                echo "<td class='conteudo'>$numero_postagem </td>";
                echo "<td class='conteudo'>&nbsp;$data_postagem </td>";
                echo "<td class='conteudo'><a href='#' rel='$conhecimento' class='correios'>$conhecimento</a></td>";
                echo "<td class='conteudo'>&nbsp;$situacao </td>";
                echo "<td class='conteudo'>&nbsp;$data_situacao </td>";
                echo "<td class='conteudo'>&nbsp;$observacao </td>";
            echo "</tr>";
            echo "</table>";
            echo "<br>";
        }
    }
/**
 * Auditor Log
 */
if ($login_fabrica == 156){

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'http://api2.telecontrol.com.br/auditor/auditor/aplication/da82d339d0552bcfcf10188a36125270/table/tbl_os/primaryKey/'.$login_fabrica.'*'.$os.'/limit/1');

    //echo 'http://proxy2.telecontrol.com.br/auditor/auditor/aplication/02b970c30fa7b8748d426f9b9ec5fe70/table/tbl_posto_fabrica/primarykey/'.$login_fabrica.'*'.$posto.'/limit/1';exit;
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $res = curl_exec($ch);
    // print_r($res);
    $res = json_decode($res,true);
    //$alteracoes = array_diff($res['content']['antes'], $res['content']['depois']);
    curl_close($ch);

    $data_alteracao_api = $res['content']['depois']['os']['data_alteracao'];

    if($res['user'] == $res['content']['depois']['posto']['id']) {
        $admin_posto = $res['content']['depois']['posto']['id'];
        $sql = "SELECT nome FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto and fabrica = $login_fabrica WHERE tbl_posto_fabrica.posto = $admin_posto";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res)>0){
                $admin_posto = pg_fetch_result($res,0,nome);

        }else{
            $admin_posto = $res['content']['depois']['posto']['id'];
            $sql = "SELECT nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = ".$res['user'];
            $res = pg_query($con,$sql);
            $admin_posto = pg_fetch_result($res,0,nome);
        }
    }else{
            $sql = "SELECT nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = ".$res['user'];
            $res = pg_query($con,$sql);
            $admin_posto = pg_fetch_result($res,0,nome);
    }

    if(empty($res['exception'])){

        ?>

        <table class='inicio' colspan='3' style="font-size:11pt; text-align: center;">
        <tr  class='titulo2'  colspan='3' >
            <td colspan='3' >Log da OS</td>
        </tr>
        <?
        if(!empty($admin_posto)) { ?>
            <tr>
                <td class='conteudo'><?= traduz("ultima.alteracao.em") ?>: <? echo $data_alteracao_api ?></td>
                <td class='conteudo'><?= traduz("usuario") ?>:  <?
                    echo $admin_posto;
                ?></td>
            </tr>
        <? } ?>
        <td>
            <?php if(isset($novaTelaOs)){ ?>
                <a target='_BLANK' href='relatorio_log_cadastro_os.php?parametro=tbl_os&id=<?php echo $os; ?>'><?= traduz("visualizar.log.auditor") ?></a>
            <?php }else{?>
                <a target='_BLANK' href='relatorio_log_os.php?parametro=tbl_os&id=<?php echo $os; ?>'><?= traduz("visualizar.log.auditor") ?></a>
            <?php } ?>

        </td>
        </table>

    <?
    }
}
/**
 * Fim - Auditor Log
 */
if (in_array($login_fabrica, array(120))) {
?>
    <div id="msg-alt-extrato"><?= traduz("valores.sujeitos.a.alteracao.ate.o.fechamento.do.extrato") ?></div>
    <br />
<?php
}


    $tipo_tabela = ($data_fechamento or $data_finalizada or $bloqueia_excluir_anexo) ? 'link' : 'linkEx';

	if($login_fabrica == 20){
		$select_campo_extra = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$res_campo_extra    = pg_query($con, $select_campo_extra);

		$campos_adicionais = pg_fetch_result($res_campo_extra, 0, 'campos_adicionais');

		$aux_campos = json_decode($campos_adicionais, true);
		if(array_key_exists("os_anexo",$aux_campos)){
			$os_anexo = $aux_campos['os_anexo'];
		}

	}

    if (in_array($login_fabrica, [123,160]) && data_corte_termo($os)) {
        
        $termo_entrega_link  = "<td class='conteudo' style='text-align: center;'><a href='termo_entrega.php?os={$os}' target='_blank'>Abrir Termo</a></TD>";
        $termo_retirada_link = "<td class='conteudo' style='text-align: center;'><a href='termo_retirada.php?os={$os}' target='_blank'>Abrir Termo</a></TD>";            
        if (!empty($termo_entrega_link) || !empty($termo_retirada_link)) {
            echo "<table width='620px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
                <tr>
                    <td class='inicio' colspan='6' style='font-size:11pt; text-align: center;'>Link(s)  do(s)  Anexo(s)</td>
                </tr> 
                <tr>";
            if (!empty($termo_entrega_link)) {
                echo "<TD class='titulo2'>Termo de Entrega</td>";
            }
            if (!empty($termo_retirada_link)) {
                echo "<TD class='titulo2'>Termo de Retirada</td>";   
            }
            echo "</tr>";    
            echo "<tr>";

            if (!empty($termo_entrega_link)) {
                echo "$termo_entrega_link";
            }
            if (!empty($termo_retirada_link)) {
                echo "$termo_retirada_link";
            }
            echo "</tr>";
            echo "<br />";

            $sql_tem_termo = "SELECT JSON_FIELD('termo_entrega', obs) AS termo_entrega, 
                                     JSON_FIELD('termo_devolucao', obs) AS termo_retirada 
                              FROM tbl_tdocs 
                              WHERE referencia_id = $os 
                              AND fabrica = $login_fabrica
                              AND situacao = 'ativo'";
            $res_tem_termo = pg_query($con, $sql_tem_termo);

            $conteudo_termo_entrega = "";
            $conteudo_termo_retirada = "";

            foreach (pg_fetch_all($res_tem_termo) as $p => $vv) {
                if ($vv['termo_entrega'] == 'ok' && empty($conteudo_termo_entrega)) {
                    $conteudo_termo_entrega = "<button class='excluir_termo_entrega'  data-os_termo='$os'>Excluir</button>";
                }

                if ($vv['termo_retirada'] == 'ok' && empty($conteudo_termo_retirada)) {
                    $conteudo_termo_retirada = "<button class='excluir_termo_retirada' data-os_termo='$os'>Excluir</button>";
                }        
            }
            
            echo "<tr>";
            echo "<td class='conteudo' style='text-align: center;'>";
            echo $conteudo_termo_entrega;  
            echo "</td>";
            echo "<td class='conteudo' style='text-align: center;'>";
            echo $conteudo_termo_retirada;   
            echo "</td>";
            echo "</tr>";

            echo "</table>";
        }
    }
    if (!in_array($login_fabrica, [144,1])) {
        if($login_fabrica == 20 && strlen($os_anexo) > 0){
            if ($anexaNotaFiscal and temNF($os_anexo, 'bool')) {
                echo "<div id='DIVanexos'>" . temNF($os_anexo, 'link', '', false) . $include_imgZoom . '</div>';

            }
    	}else{
            if ($anexaNotaFiscal and temNF($os, 'bool') && !isset($novaTelaOs)) {
                if (in_array($login_fabrica, [6,20])) {
                    $tipo_tabela = 'link';
                }
                if($login_fabrica != 88 && !$fabricaFileUploadOS){                    
                    echo "<div id='DIVanexos'>" . temNF($os, $tipo_tabela, '', false) . $include_imgZoom . '</div>';
                }

                if (temNF($os, 'count') >= 3 && $login_fabrica == 1) {
                    $mais_anexos = temNF($os, 'linkEx', '', true, true, 1);
                    if ($mais_anexos) {
                        echo '<div id="DIVanexos">' . temNF($os, 'linkEx', '', true, true, 1) .  '</div>';
                    }
                }

            }
        }
    }
    if (in_array($login_fabrica, array(141,144))) {
        $sql = "SELECT
                    DATE_PART('MONTH', data_abertura) AS mes,
                    DATE_PART('YEAR', data_abertura) AS ano
                FROM tbl_os
                WHERE fabrica = {$login_fabrica}
                AND os = {$os}
                AND troca_garantia IS TRUE
                AND ressarcimento IS TRUE";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $amazonTC = new AmazonTC("os", $login_fabrica);

            $mes = pg_fetch_result($res, 0, "mes");
            $ano = pg_fetch_result($res, 0, "ano");

            $comprovante = $amazonTC->getObjectList("comprovante_ressarcimento_{$os}", false, $ano, $mes);

            if (count($comprovante) > 0) {
                $comprovante_basename = basename($comprovante[0]);
                $comprovante_ext = preg_replace("/.+\./", "", $comprovante_basename);

                $comprovante = $amazonTC->getLink($comprovante_basename, false, $ano, $mes);

                if ($comprovante_ext != "pdf") {
                    $comprovante_thumb = $amazonTC->getLink("thumb_{$comprovante_basename}", false, $ano, $mes);
                } else {
                    $comprovante_thumb = "imagens/icone_PDF.png";
                }

                ?>
                <table style="margin: 0 auto; width: 698px;" class="tabela">
                    <thead class="Tabela inicio">
                        <tr>
                            <th><?= traduz("comprovante.de.ressarcimento") ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="conteudo">
                            <td style="vertical-align: middle; text-align: center;">
                                <img src="<?=$comprovante_thumb?>" style="cursor: pointer;" onclick="window.open('<?=$comprovante?>');" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php
            }
        }
    }

    if($login_fabrica == 140){

        $sql_ta = "SELECT descricao FROM tbL_tipo_atendimento WHERE tipo_atendimento = (SELECT tipo_atendimento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) AND fabrica = $login_fabrica";
        $res_ta = pg_query($con, $sql_ta);

        if(pg_num_rows($res_ta) > 0){
            $desc_tipo_atendimento = pg_fetch_result($res_ta, 0, 'descricao');
        }

        if($desc_tipo_atendimento == "Entrega t&eacute;cnica"){

        ?>

            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
                <thead class="Tabela inicio">
                    <tr>
                        <th align="center"><?= traduz("laudo.tecnico") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="conteudo">
                        <td align="center">
                            <?php
                            $s3 = new AmazonTC("inspecao", $login_fabrica);
                            $laudo_tecnico = $s3->getObjectList($os);
                            $laudo_tecnico = basename($laudo_tecnico[0]);
                            $laudo_tecnico = $s3->getLink($laudo_tecnico);

                            echo "<a href='{$laudo_tecnico}' target='_blank'><img src='{$laudo_tecnico}' style='max-width: 150px; max-height: 150px;_height:150px;*height:150px;' /></a>";

                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

        <?php

        }

    }

    if($login_fabrica == 141 && $posto_interno == true){

        include_once "class/aws/s3_config.php";
        include_once S3CLASS;

        $s3 = new AmazonTC("os", $login_fabrica);

        $nota_fiscal_saida = $s3->getObjectList("nota-fiscal-saida-{$os}");
        $nota_fiscal_saida = basename($nota_fiscal_saida[0]);

        $ext = explode(".", $nota_fiscal_saida);

        $nota_fiscal_saida = ($ext[1] == "pdf") ? "imagens/pdf_icone.png" : $s3->getLink($nota_fiscal_saida);

        if(strlen($nota_fiscal_saida) > 0){

            echo "<br />";
            echo "
            <table width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";

                echo "
                    <tr>
                        <td align='center'>
                            <font size='2' color='#fff'><center><b>".traduz("anexo.de.nota.fiscal.de.saida")."</b></font>
                        </td>
                    </tr>";

            echo "<tr>
                    <td class='conteudo' style='text-align: center !important;'>";
                        echo "
                            <br />
                                <a href='{$nota_fiscal_saida}' target='_blank'><img src='{$nota_fiscal_saida}' style='max-width: 150px; max-height: 150px;_height:150px;*height:150px;' /></a>
                            <br /> <br />";
                    echo "
                    </td>
                </tr>
            </table>";
        }
    }

    if(in_array($login_fabrica, array(88))){
        $fabrica_qtde_anexos = $limite_anexos_nf;
        $dadosAnexos = temNF($os, 'array', '', false);

        $anexos = array_fill(0, 5, null);
        foreach ($dadosAnexos['arquivos'] as $i =>  $link) {
            $name = pathinfo($link, PATHINFO_FILENAME);
            $pos = preg_replace("/\w+\-(\d)$/", "$1", $name);
            $pos = strlen($pos) == 1 ? $pos-1 : 0;
            $anexos[$pos]['full'] = $link;
            $anexos[$pos]['thumb'] = $dadosAnexos['thumbs'][$i];
        }

        /*for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
            unset($full);

            if (count($dadosAnexos['arquivos'][$i]) > 0) {
                $extensao = preg_replace("/.+\./", "", basename($dadosAnexos['arquivos'][$i]));

                $full = $dadosAnexos['arquivos'][$i];
                $thumb = $dadosAnexos['thumbs'][$i];
            }else{
                $thumb = "imagens/imagem_upload.png";
            }

            $anexos[$i] = array(
                "full"  => $full,
                "thumb" => $thumb
            );
        }*/

        ?>

            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
                <thead class="Tabela inicio">
                    <tr>
                        <th align="center"><?= traduz("anexo.s") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="conteudo">
                        <td align="center">
                        <?php
                            foreach ($anexos as $key => $value) {
                                $dados_anexo = explode("?", $value['full']);
                                $nome_arquivo = basename($dados_anexo[0]);
                                echo "<span name='anexo_{$key}' style='margin: 10px; display: inline-block; vertical-align: top;'>";
                                    if ($value["full"] == null) {
                                        $style = " display:block; ";
                                        $value['thumb'] = "imagens/imagem_upload.png";
                                    }else{
                                        $style = " display:none; ";
                                    }

                                        //if ($value["full"] == null && $excluida != "t") {
                                            echo "<img src='{$value['thumb']}' name='img_anexo_$key' style='width: 100px; height: 90px; $style' class='anexo_nf_$key' /><br />";
                                            ?>
                                            <form name="form_anexo"  method="post" action="os_press.php" enctype="multipart/form-data" style="display: none;" >
                                                <input type="file" name="anexo_upload" value="" />
                                                <input type="hidden" name="ajax_anexo" value="t" />
                                                <input type='hidden' name='os' value='<?=$os?>' />
                                                <input type='hidden' name='posicao' value='<?=$key?>' />
                                                <input type='hidden' name='ano' value='<?=$ano?>' />
                                                <input type='hidden' name='mes' value='<?=$mes?>' />
                                            </form>

                                            <button type='button' class='anexo_nf_<?=$key?>' name='anexar_arquivo' style="<?=$style?>" >Anexar</button>
                                        <?php
                                        //} else {
                                            if ($value["full"] != null) {
                                                $style_excluir = " display:block; ";
                                            }else{
                                                $style_excluir = " display:none; ";
                                            }

                                            if(strlen(trim($data_fechamento))==0){
                                                $btn_excluir = "<button type='button' name='excluir_anexo_nf'  class='ex_anexo_$key' style='$style_excluir' >Excluir</button>";
                                            }
                                            echo "<a href='{$value['full']}' class='ex_anexo_$key' target='_blank'><img src='{$value['thumb']}' style='max-width: 100px; height: 78px; $style_excluir' /></a>
                                                <br>";

                                            if(strlen(trim($data_fechamento))==0 && $excluida != "t"){
                                                echo "
                                                <input type='hidden' name='ano' value='$ano' />
                                                <input type='hidden' name='mes' value='$mes' />
                                                <input type='hidden' name='posicao_ex' value='$key' />
                                                <input type='hidden' name='anexo' value='".$nome_arquivo."' />
                                                {$btn_excluir}
                                                ";
                                            }

                                        //}
                                    echo "</span>";
                            }
                            ?>


                        </td>
                    </tr>
                </tbody>
            </table>
        <?php
    }
    if (in_array($login_fabrica, [174]) && $posto_interno) {

        include_once "class/tdocs.class.php";

        $tDocs = new TDocs($con, $login_fabrica);
        ?>
        <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
            <thead class="Tabela inicio">
                <tr>
                    <th align="center">NF de entrada</th>
                    <th align="center">NF de saída</th>
                </tr>
            </thead>
            <tbody>
                    
                <?php
                $sqlTdocs = "SELECT tdocs,referencia FROM tbl_tdocs 
                                    WHERE contexto = 'os' 
                                    AND referencia_id = $os
                                    AND referencia IN ('nf_entrada','nf_saida')
                                    AND situacao = 'ativo'
                                    ";
                $resTdocs = pg_query($con, $sqlTdocs);

                while ($dadosTdocs = pg_fetch_object($resTdocs)) {

                    $dadosNf = $tDocs->getDocumentById($dadosTdocs->tdocs);

                    if (count($dadosNf) > 0) {
                        foreach ($dadosNf as $chave => $valor) {

                            $linkUrl = $valor[$dadosTdocs->tdocs]['link'];

                            if (!empty($linkUrl)) {
                                $extensaoFile = preg_replace("/.+\./", "", $valor[$dadosTdocs->tdocs]['filename']);

                                if($extensaoFile == "pdf"){
                                    $link = "imagens/pdf_icone.png";
                                } else if(in_array($extensaoFile, array("doc", "docx"))){
                                    $link = "imagens/docx_icone.png";
                                } else {
                                    $link = $valor[$dadosTdocs->tdocs]['link'];;
                                }

                            ?>
                            <td align="center">
                                <a href="<?=$linkUrl?>" target="_blank" >
                                    <img src="<?=$link?>" style="width: 100px; height: 90px;" />
                                </a>
                            </td>
                            <?php
                            }
                            
                        }
                    }
                }

                ?>
                </tr>
            </tbody>
        </table>
    <?php
    }
    
    if((isset($novaTelaOs) || in_array($login_fabrica, array(1,3,52)) || $anexo_os_revenda) AND !$fabricaFileUploadOS){

        list($dia,$mes,$ano) = explode("/", $data_abertura);

        $s3 = new AmazonTC("os", $login_fabrica);

        $anexos = array();

        for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
            unset($full);

            $thumb = "imagens/imagem_upload.png";

            $anexo_s3 = $s3->getObjectList("{$os}_{$i}.", false, $ano, $mes);

            if (count($anexo_s3) > 0) {
                $extensao = preg_replace("/.+\./", "", basename($anexo_s3[0]));

                $full = $s3->getLink(basename($anexo_s3[0]), false, $ano, $mes);

                if($extensao == "pdf"){
                    $thumb = "imagens/pdf_icone.png";
                }else if(in_array($extensao, array("doc", "docx"))){
                    $thumb = "imagens/docx_icone.png";
                }else{
                    $thumb = $s3->getLink("thumb_".basename($anexo_s3[0]), false, $ano, $mes);
                }

            }

            $anexos[$i] = array(
                "full"  => $full,
                "thumb" => $thumb
            );
        }

        if ($login_fabrica == 160 && data_corte_termo($os)) {
            unset($array_thumb);
            $full = '';
            $thumb = ''; 
            $count_anexo_s3 = count($anexos); 
            $links_tdocs = temNF($os, 'array', '', true, true, 1);
            $count_tdocs = count($links_tdocs['thumbs']);
            for ($t=0; $t < $count_tdocs; $t++) { 
                $full = $links_tdocs['thumbs'][$t];
                $link_valido = explode('/', $links_tdocs['thumbs'][$t]);
                if ($link_valido[2] != 'api2.telecontrol.com.br') {
                    continue;
                } 
                $thumb = $full;
                $array_thumb = explode('.', $full);
                foreach ($array_thumb as $thum => $ex) {
                    if ($ex == 'pdf') {
                       $thumb = "imagens/pdf_icone.png"; 
                    }
                }
                $anexos[$count_anexo_s3] = array(
                "full"  => $full,
                "thumb" => $thumb
            );    
                $count_anexo_s3++; 
            }
        }

        if(in_array($login_fabrica, array(164,165)) && $posto_interno == true){
            $anexoNota = $s3->getObjectList("nota-fiscal-saida-".$os, false);

            if (count($anexoNota) > 0) {
                $extensao = preg_replace("/.+\./", "", basename($anexoNota[0]));

                $full = $s3->getLink(basename($anexoNota[0]), false);

                if($extensao == "pdf"){
                    $thumb = "imagens/pdf_icone.png";
                }else if(in_array($extensao, array("doc", "docx"))){
                    $thumb = "imagens/docx_icone.png";
                }else{
                    $thumb = $s3->getLink("thumb_".basename($anexoNota[0]), false);
                }

            }
            $anexos[] = array(
                "full"  => $full,
                "thumb" => $thumb
            );
        }

        if (count($anexos) > 0) {
        ?>

            <br />

            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
                <thead class="Tabela inicio">
                    <tr>
                        <th align="center"><?= traduz("anexo.s") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="conteudo">
                        <td align="center">
                            <?php
                            foreach ($anexos as $key => $value) {
                                $dados_anexo = explode("?", $value['full']);
                                $nome_arquivo = basename($dados_anexo[0]);

                                echo "<span name='anexo_{$key}' style='margin: 10px; display: inline-block; vertical-align: top;'>";

                                    if ($value["full"] == null && $excluida != "t") {
                                        echo "<img src='{$value['thumb']}' style='max-width: 100px; max-height: 90px;' /><br />";
                                        ?>
                                        <form name="form_anexo" method="post" action="os_press.php" enctype="multipart/form-data" style="display: none;" >
                                            <input type="file" name="anexo_upload" value="" />
                                            <input type="hidden" name="ajax_anexo_upload" value="t" />
                                            <input type='hidden' name='os' value='<?=$os?>' />
                                            <input type='hidden' name='posicao' value='<?=$key?>' />
                                            <input type='hidden' name='ano' value='<?=$ano?>' />
                                            <input type='hidden' name='mes' value='<?=$mes?>' />
                                        </form>

                                        <button type='button' name='anexar_arquivo' >Anexar</button>
                                    <?php
                                    } else {

                                        if(strlen(trim($data_fechamento))==0){
                                            $btn_excluir = "<button type='button' name='excluir_anexo' >Excluir</button>";
                                        }
                                        echo "<a href='{$value['full']}' target='_blank'><img src='{$value['thumb']}' style='max-width: 100px; max-height: 90px;' /></a>
                                            <br>";

                                        if(strlen(trim($data_fechamento))==0 && $excluida != "t"){
                                            if ($login_fabrica == 160 && data_corte_termo($os)) {
                                                unset($array_link);
                                                $array_link = explode('/', $value['full']);
                                                foreach ($array_link as $ps => $link) {
                                                    if ($link == 'api2.telecontrol.com.br') {
                                                        $btn_excluir = "<a href='{$value['full']}' target='_blank'><button type='button' name='ver_termo' >Termo</button></a>";
                                                    }
                                                }
                                            }
                                            echo "
                                            <input type='hidden' name='ano' value='$ano' />
                                            <input type='hidden' name='mes' value='$mes' />
                                            <input type='hidden' name='anexo' value='".$nome_arquivo."' />
                                            {$btn_excluir}
                                            ";
                                        }

                                    }
                                echo "</span>";
                            }

                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

        <?php
        }
            if (in_array($login_fabrica, array(1,3,173,176)) or $anexo_os_revenda) {

				$sem_anexo = true;
				if(in_array($consumidor_revenda, ['R','REVENDA']) and $login_fabrica == 1) {
					$sqlTdocs = "SELECT tdocs FROM tbl_tdocs JOIN tbl_os_revenda_item ON tbl_tdocs.referencia_id = tbl_os_revenda_item.os_revenda JOIN tbl_os ON tbl_os.os = tbl_os_revenda_item.os_lote WHERE tbl_tdocs.fabrica = $login_fabrica and tbl_os.fabrica = $login_fabrica AND tbl_os.os = $os AND referencia != 'laudotecnico' ORDER BY tdocs DESC LIMIT 1";
					$resTdocs = pg_query($con, $sqlTdocs);
					$sem_anexo = (pg_num_rows($resTdocs) == 0) ? true : false;
				}

				if($sem_anexo) {
					$sqlTdocs = "SELECT tdocs FROM tbl_tdocs WHERE fabrica = $login_fabrica AND referencia_id = $os AND referencia != 'laudotecnico' ORDER BY tdocs DESC LIMIT 1";
					$resTdocs = pg_query($con, $sqlTdocs);
				}

                if(pg_num_rows($resTdocs) > 0 AND $login_fabrica != 1){
                    $tdocs_id = pg_fetch_result($resTdocs, 0, 'tdocs');
                    $link_tdocs = $s3_tdocs->getDocumentLocation($tdocs_id);
                        $extensao = preg_replace("/.+\./", "", basename($link_tdocs));

                        if($extensao == "pdf"){
                            $thumb = "imagens/pdf_icone.png";
                        }else if(in_array($extensao, array("doc", "docx"))){
                            $thumb = "imagens/docx_icone.png";
                        }else{
                            $thumb = $link_tdocs;
                        } ?>
                    <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
                        <thead class="Tabela inicio">
                            <tr>
                                <th align="center"><?= (in_array($login_fabrica,[1,3]) or $anexo_os_revenda) ? traduz("anexo.s") : traduz("anexo.fechamento.os") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="conteudo">
                                <td align="center">
                                    <a href='<?=$link_tdocs?>' target='_blank'><img src='<?=$thumb?>' style='max-width: 100px; max-height: 90px;' /></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
            <?php
                }
            }

        if($login_fabrica == 153){

            $s3 = new AmazonTC("os_produto_serie", $login_fabrica);

            $anexos = array();

                unset($full);
                $thumb = "imagens/imagem_upload.png";
                $anexo_s3 = $s3->getObjectList("{$os}.", false);
                if (count($anexo_s3) > 0) {
                    $montrarAnexoNS = true;
                    $extensao = preg_replace("/.+\./", "", basename($anexo_s3[0]));
                    $full = $s3->getLink(basename($anexo_s3[0]), false);

                    if($extensao == "pdf"){
                        $thumb = "imagens/pdf_icone.png";
                    }else if(in_array($extensao, array("doc", "docx"))){
                        $thumb = "imagens/docx_icone.png";
                    }else{
                        $thumb = $s3->getLink("thumb_".basename($anexo_s3[0]), false);
                    }
                }else{
                    $montrarAnexoNS = false;
                }

                $anexos = array(
                    "full"  => $full,
                    "thumb" => $thumb
                );
        }


        /*if ($montrarAnexoNS == true and $login_fabrica == 153) {
        ?>

            <br />

            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
                <thead class="Tabela inicio">
                    <tr>
                        <th align="center">Anexo Produto sem N/S</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="conteudo">
                        <td align="center">
                            <?php
                                echo "<span name='anexo_{$key}' style='margin: 10px; display: inline-block; vertical-align: top;'>";

                                    if ($anexos["full"] == null) {
                                        echo "<img src='{$anexos['thumb']}' style='max-width: 100px; max-height: 90px;' /><br />";
                                        ?>
                                        <form name="form_anexo" method="post" action="os_press.php" enctype="multipart/form-data" style="display: none;" >
                                            <input type="file" name="anexo_upload" value="" />
                                            <input type="hidden" name="ajax_anexo_upload" value="t" />
                                            <input type='hidden' name='os' value='<?=$os?>' />
                                            <input type='hidden' name='posicao' value='<?=$key?>' />
                                            <input type='hidden' name='ano' value='<?=$ano?>' />
                                            <input type='hidden' name='mes' value='<?=$mes?>' />
                                        </form>

                                        <button type='button' name='anexar_arquivo' >Anexar</button>
                                    <?php
                                    } else {
                                        echo "<a href='{$anexos['full']}' target='_blank'><img src='{$anexos['thumb']}' style='max-width: 100px; max-height: 90px;' /></a>";
                                    }
                                echo "</span>";


                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

        <?php
        }*/
    }

    if (in_array($login_fabrica, array(167,173,203))) {
        $link_tdocs = $s3_tdocs->getDocumentsByRef($os, 'os')->url;
        // $sqlTdocs = "SELECT tdocs
        //     FROM tbl_tdocs
        //     WHERE fabrica = $login_fabrica
        //     AND referencia_id = $os
        //     ORDER BY tdocs
        //     DESC LIMIT 1";
        // $resTdocs = pg_query($con, $sqlTdocs);

        // if(pg_num_rows($resTdocs) > 0){
            // $tdocs_id = pg_fetch_result($resTdocs, 0, 'tdocs');
            // $link_tdocs = $s3_tdocs->getDocumentLocation($tdocs_id);
        if ($link_tdocs AND !$fabricaFileUploadOS) {
    ?>
            <br/>
            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
                <thead class="Tabela inicio">
                    <tr>
                        <th align="center"><?= traduz("anexo.fechamento.os") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="conteudo">
                        <td align="center">
                            <a href='<?=$link_tdocs?>' target='_blank'><img src='<?=$link_tdocs?>' style='max-width: 100px; max-height: 90px;' /></a>
                            <?php
                                if(strlen(trim($data_fechamento))==0){
                                    echo "<br/> <button type='button' name='alterar_anexo_fechamento' onclick='alterar_anexo_fechamento();' >Alterar</button>";
                                }
                            ?>
                            <form name="form_anexo_fechamento" method="post" id='alterar_anexo_fechamento' action="os_press.php" enctype="multipart/form-data" style="display: none;" >
                                <input type="file" name="anexo_upload_fechamento" value="" />
                                <input type='hidden' name='os' value='<?=$os?>' />
                                <button type='submit' name='anexo_fechamento' value='anexo_fechamento_os' id='anexo_fechamento' style='display: none;' ><?= traduz("anexar") ?></button>
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>
    <?php
        }
    }

    if (isset($anexo_peca_os)) {
        $s3_item = new AmazonTC("os_item", $login_fabrica);

        $os_item_anexo = array();

		$sql_os_item = "SELECT tbl_os_produto.os_produto, tbl_os_item.os_item, tbl_peca.referencia,
							   tbl_peca.parametros_adicionais AS param_ad_peca /* evita sobrescrever a da fábrica */
                          FROM tbl_os_item
                          JOIN tbl_peca       ON tbl_peca.peca    = tbl_os_item.peca
                                             AND tbl_peca.fabrica = $login_fabrica
                          JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                          JOIN tbl_os         ON tbl_os.os        = tbl_os_produto.os
                                             AND tbl_os.fabrica   = $login_fabrica
                                             AND tbl_os.posto     = $login_posto
                         WHERE tbl_os.os = $os
                           AND tbl_peca.parametros_adicionais ~ '.anexo_os.:.?t.'";
        $res_os_item = pg_query($con, $sql_os_item);

        if (pg_num_rows($res_os_item) > 0) {
			$info_anexo_pecas = pg_fetch_all($res_os_item);

			$tem_anexo_pecas = false;
			ob_start();
			// echo array2table($info_anexo_pecas);

			foreach($info_anexo_pecas as $info_peca) {
				extract($info_peca);
				extract(json_decode($param_ad_peca, true));

				if (!isset($qtde_anexos))
					$qtde_anexos = 1;

				$anexos_pecas  = $s3_item->getObjectList(implode('_', array($os, $os_produto, $os_item)));
				// $anexos_pecas  = $s3_item->getObjectList($os.'_');

				if (count($anexos_pecas) > 0) {
					$tem_anexo_pecas = true;
        ?>
                    <tr class="conteudo">
                        <td align="center">
				<?php
					for ($j=0; $j < $qtde_anexos; $j++) {
						$indice_anexo_peca = implode('_', array_filter(array('anexo_peca', $os_produto, $os_item, $j)));
						if (isset($anexos_pecas[$j])) {
							$value    = $anexos_pecas[$j];
							$anexo    = basename($value);
							$extensao = pathinfo($anexo, PATHINFO_EXTENSION);

							$anexo = $s3_item->getLink($anexo);

							if($extensao == "pdf"){
								$thumb_anexo = "imagens/pdf_icone.png";
							}else if(in_array($extensao, array("doc", "docx"))){
								$thumb_anexo = "imagens/docx_icone.png";
							}else{
								$thumb_anexo = "thumb_".basename($value);
								$thumb_anexo = $s3_item->getLink($thumb_anexo);
							}

							echo "<span style='margin: 10px; display: inline-block; vertical-align: top;'>
								{$referencia}<br />
								<a href='{$anexo}' target='_blank'><img src='{$thumb_anexo}' style='width: 100px; height: 90px;' /></a>
								<br />
								</span>";
						} else {

                            ?>
							<span name='<?=$indice_anexo_peca?>' style='margin: 10px; display: inline-block; vertical-align: top;'>
								<?=$referencia?><br />
								<img src='imagens/imagem_upload.png' style='width: 100px; height: 90px;' /><br />
								<form name="form_anexo_peca" method="post" enctype="multipart/form-data">
									<input type="file"  name="anexo_peca_upload" value="" style='display:none' />
									<input type="hidden"  name="os" value="<?=$os?>" />
									<input type="hidden"  name="os_produto" value="<?=$os_produto?>" />
									<input type="hidden"  name="os_item" value="<?=$os_item?>" />
									<input type="hidden"  name="posicao" value="<?=$j?>" />
									<input type="hidden"  name="ajax_anexo_peca_upload" value="t" />
									<button type='button' name='anexar_arquivo_peca'><?= traduz("anexar") ?></button>
								</form>

							</span>
					<?php
						}
					}
				}
			} // fim foreach pecas (os_item)

                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php
        }
		if ($tem_anexo_pecas) {
			$tabela_anexos_pecas = ob_get_clean(); ?>
            <br />

            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
                <thead class="Tabela inicio">
                    <tr>
                        <th align="center"><?= traduz("anexo.das.pecas") ?></th>
                    </tr>
                </thead>
                <tbody>
<?			echo $tabela_anexos_pecas;
		}
    }
    echo '<br>';
    
    if (in_array($login_fabrica,array(153,157,165))) {        
		$anexaNotaFiscal = true;
        if ($consumidor_revenda == "REVENDA" || $login_fabrica == 35) {
            $sql = "SELECT tbl_os_revenda.os_revenda
                        FROM tbl_os
                        JOIN tbl_os_revenda ON tbl_os.fabrica = tbl_os_revenda.fabrica and tbl_os.posto = tbl_os_revenda.posto
                        JOIN tbl_os_revenda_item USING(os_revenda)
                        WHERE tbl_os.fabrica = $login_fabrica
                        AND os = $os
                        AND (os_lote = $os or tbl_os_revenda.sua_os ~ tbl_os.os_numero::text )";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res)> 0 ) {
                $os_revenda = pg_fetch_result($res, 0, "os_revenda");


                if ($anexaNotaFiscal and temNF($os_revenda, 'bool')) {
                    echo '<div id="DIVanexos">' . temNF($os_revenda, 'linkEx') .  '</div>';
                } else {
                    if ($anexaNotaFiscal and temNF($os, 'bool')) {
                        echo '<div id="DIVanexos">' . temNF($os, 'linkEx') .  '</div>';
                    }
                }

            }else{
                if ($anexaNotaFiscal and temNF($os, 'bool')) {
                    echo '<div id="DIVanexos">' . temNF($os, 'linkEx') .  '</div>';
                }

            }
        } else if(!isset($novaTelaOs)) {
            if ($anexaNotaFiscal and temNF($os, 'bool')) {
                if ($login_fabrica == 1) {
                    $attTblAttrs['tableAttrs'] = " id='anexos0' class='tabela anexos' align='center'";
                }

                echo '<div id="DIVanexos">' . temNF($os, 'linkEx', '', false, false, 0) .  '</div>';

                if (temNF($os, 'count') >= 3) {
                    $mais_anexos = true;

                    if ($login_fabrica == 1) {
                        $attTblAttrs['tableAttrs'] = " id='anexos1' class='tabela anexos' align='center'";

                        //$anexos0 = temNF($os, 'linkEx', '', false, false, 0);
                        //$anexos1 = temNF($os, 'linkEx', '', false, false, 1);

                        //if ($anexos0 == $anexos1) {
                        //    $mais_anexos = false;
                        //}
                    }

                    if ($mais_anexos) {
                        echo '<div id="DIVanexos">' . temNF($os, 'linkEx', '', true, true, 1) .  '</div>';
                    }
                }
            }
        }

    }
    
    if($login_fabrica == 1){
        echo temNF($os, 'link');        
    }  

    if (in_array($login_fabrica, array(169, 170))) {
        if (empty($data_fechamento)) {
            if (!empty($_POST["data_conserto"])) {
                $data_conserto = $_POST["data_conserto"];
            }
            ?>
            <br /><br />
            <form action="os_press.php?os=<?=$os?>" method="post" style="display: inline;" >
                <table class="Tabela" style="width: 700px; margin: 0 auto;" border="0" cellspacing="1" cellpadding="0" >
                    <thead>
                        <tr>
                            <th class="inicio" style="font-size: 11pt; text-align: center;" colspan="2"  ><?= traduz("fechar.ordem.de.servico") ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="titulo_coluna" >
                            <td class="titulo2" colspan="2" style="padding-top: 5px; padding-bottom: 5px;"  ><?= traduz("data.de.conserto") ?></th>
                        </tr>
                        <tr>
                            <td class="conteudo" style="text-align: center;" colspan="2"  ><input type="text" id="fechamento_data_conserto" name="data_conserto" value="<?=$data_conserto?>" placeholder="DD/MM/AAAA" style="margin-top: 5px; margin-bottom: 5px;" /></td>
                        </tr>
                        <?php
                        if ($postagem_coleta == "true"){
                        ?>
                            <tr>
                                <td class="titulo2" colspan="2" id="lgr_correios" style="display: none; text-align: center;" >
                                    <button style="cursor: pointer;" type="button" onclick="javascript: solicitaPostagem('<?=$hd_chamado?>','<?=$posto_codigo;?>');" style="cursor: pointer; font-weight: bold; margin-top: 5px; margin-bottom: 5px; border-width: 2px;"><?= traduz("solicitacao.postagem/coleta") ?></button>
                                </tr>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <th>
                            <input type="submit" value="Informar Data de Conserto" name="consertar_os" style="cursor: pointer; font-weight: bold; margin-top: 5px; margin-bottom: 5px; border-width: 2px;" />
                        </th>
                        <th>
                            <input type="submit" value="Fechar Ordem de Serviço" name="fechar_os" style="cursor: pointer; font-weight: bold; margin-top: 5px; margin-bottom: 5px; border-width: 2px;" />
                        </th>
                    </tfoot>
                </table>
            </form>

            <script>

            $(function() {
                $("#fechamento_data_conserto").mask("99/99/9999").datepick({startDate:'01/01/2000'});

                <?php
                if ($postagem_coleta == "true"){
                ?>
                    $("#fechamento_data_conserto").on("blur", function() {
                        var data = $(this).val();

                        if (data.length > 0 && data.match(/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/)) {
                            $("#lgr_correios").show();
                        } else {
                            $("#lgr_correios").hide();
                        }
                    });

                    $("#fechamento_data_conserto").on("keyup", function(e) {
                        var data = $(this).val();

                        if ($.inArray(e.key, ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"]) != -1) {
                            if (data.match(/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/)) {
                                $(this).trigger("blur");
                            }
                        }
                    });

                    $("#fechamento_data_conserto").trigger("blur");
                <?php
                }
                ?>
            });

            </script>
        <?php
        }
    }

	if(in_array($login_fabrica, array(131))){
        include_once "class/tdocs.class.php";
		$s3_tdocs = new TDocs($con, $login_fabrica);

        $sqlTdocs = "SELECT tdocs FROM tbl_tdocs WHERE fabrica = $login_fabrica AND referencia_id = $os AND referencia = 'oscancela' ORDER BY tdocs DESC LIMIT 1";
        $resTdocs = pg_query($con, $sqlTdocs);

        if(pg_num_rows($resTdocs) > 0){
            $tdocs_id = pg_fetch_result($resTdocs, 0, 'tdocs');
            $link_tdocs = $s3_tdocs->getDocumentLocation($tdocs_id);

            $extensao = preg_replace("/.+\./", "", basename($link_tdocs));

            if($extensao == "pdf"){
                $thumb = "imagens/pdf_icone.png";
            }else if(in_array($extensao, array("doc", "docx"))){
                $thumb = "imagens/docx_icone.png";
            }else{
                $thumb = $link_tdocs;
            }

    ?>
            <br/>
            <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class="tabela">
                <thead class="Tabela inicio">
                    <tr>
					<th align="center"><?="Anexo de Cancelamento da OS"?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="conteudo">
                        <td align="center">
                            <a href='<?=$link_tdocs?>' target='_blank'><img src='<?=$thumb?>' style='max-width: 100px; max-height: 90px;' /></a>
                        </td>
                    </tr>
                </tbody>
            </table>
    <?php
        }
    }

// hd 21896 - Francisco Ambrozio - inclusão do laudo técnico
if ($login_fabrica == 1 or $login_fabrica == 19){
    if($login_fabrica == 1){
        $tiraPesquisa = " AND titulo NOT ILIKE 'Pesquisa %'";
    }
    $sql = "SELECT tbl_laudo_tecnico_os.*
            FROM tbl_laudo_tecnico_os
            WHERE os = $os
            $tiraPesquisa
            ORDER BY ordem;";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){
?>
        <BR>
        <TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
        <TR>
        <TD colspan="9" class='inicio'>&nbsp;<?echo traduz("laudo.tecnico",$con,$cook_idioma);?></TD>
<?
        echo "<tr>";
        if ($login_fabrica==19) {
            echo "<td class='titulo' style='width: 30%'><CENTER>".traduz("questao",$con,$cook_idioma)."</CENTER></td>";
        } else {
            echo "<td class='titulo' style='width: 30%'><CENTER>".traduz("titulo",$con,$cook_idioma)."</CENTER></td>";
        }
        echo "</CENTER></td>";
        echo "<td class='titulo' style='width: 10%'><CENTER>".traduz("afirmativa",$con,$cook_idioma)."</CENTER></td>";
	$labelObs = ($login_fabrica == 175) ? "servico.executado" : "observacao";
        echo "<td class='titulo' style='width: 60%'><CENTER>".traduz($labelObs,$con,$cook_idioma)."</CENTER></td>";
        echo "</tr>";

        for($i=0;$i<pg_num_rows($res);$i++){
            $laudo       = pg_fetch_result($res,$i,laudo_tecnico_os);
            $titulo      = pg_fetch_result($res,$i,titulo);
            $afirmativa  = pg_fetch_result($res,$i,afirmativa);
            $observacao  = pg_fetch_result($res,$i,observacao);

            if($observacao == 'null' OR $obs == "NULL"){
                $observacao = "";
            }
            echo "<tr>";
            echo "<td class='conteudo' align='left' style='width: 30%'>&nbsp;$titulo</td>";
            if(strlen($afirmativa) > 0){
                echo "<td class='conteudo' style='width: 10%'><CENTER>"; if($afirmativa == 't'){ echo traduz("sim",$con,$cook_idioma)."</CENTER></td>";} else { echo traduz("nao",$con,$cook_idioma)."</CENTER></td>";}
            } else {
                echo "<td class='conteudo' style='width: 10%'>&nbsp;</td>";
            }
            if(strlen($observacao) > 0){
                echo "<td class='conteudo' style='width: 60%'><CENTER>$observacao</CENTER></td>";
            } else {
                echo "<td class='conteudo' style='width: 60%'>&nbsp;</td>";
            }
            echo "</tr>";
        } ?>
</TR>

</TABLE> <?
    }
}



if($login_fabrica == 1) {
    $sql = " SELECT
                trim(laudo_tecnico)
            FROM tbl_os
            WHERE os = $os
            AND fabrica= $login_fabrica
            AND length(laudo_tecnico) > 0";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){
        echo "<br/>";
        echo "<center>";
        echo "<table width='500' align='center' class='Tabela'>";
        echo "<tr class='inicio'>";
        echo "<td  align='center'>Laudo Técnico</td>";
        echo "</tr>";

        $laudo = pg_fetch_result($res,0,laudo_tecnico);

        echo "<tr>";
        echo "<td class='conteudo' align='left' >&nbsp;$laudo</td>";
        echo "</tr>";
        echo "</table></center>";
    }else{
        $sql = "SELECT laudo_tecnico_os
                FROM tbl_laudo_tecnico_os
                WHERE os = $os
                AND fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){
            $laudo = pg_fetch_result($res, 0, 'laudo_tecnico_os');
            echo "<br/>";
            echo "<table width='500' align='center'>";
            echo "<tr class='inicio'>";
            echo "<td  align='center'><input type='button' value='".traduz("clique.aqui.para.imprimir.laudo.tecnico.deste.atendimento")."' onclick=\"javascript:window.open('gerar_laudo_tecnico.php?os=$os&laudo=$laudo');\"></td>";
            echo "</tr>";
            echo "</table>";
        }
    }
}

?>

<?php 
if ($fabricaFileUploadOS) {
?>
<table width="700px" border="0" cellspacing="1" cellpadding="0" align="center">
    <tr>
        <td>
            <?php
                $hidden_button_upload = false;

                if (in_array($login_fabrica, [164])) {
                    $hidden_button_upload = true; 

                    $anexo_prepend = '<a href="cadastro_os.php?os_id='.$os.'"  class="btn btn-primary"><i class="fa fa-upload"></i> Anexar arquivos</a>';
                }

                $tempUniqueId = $os;
                $boxUploader = array(
                    "div_id" => "div_anexos",
                    "prepend" => $anexo_prepend,
                    "context" => "os",
                    "unique_id" => $tempUniqueId,
                    "hash_temp" => $anexoNoHash,
                    "bootstrap" => false,
                    "hidden_button" =>  $hidden_button_upload
                );
                include "box_uploader.php";
            ?>
        </td>
    </tr>
</table>
<?php 
} 
?>

<?php 
if (in_array($login_fabrica, [177])) {
    $tipo_posto_interno = 'f';
    $osId = $_GET['os'];
    $postoId = $login_posto;

    if (empty($login_posto)) {
        $qPostoId = "SELECT tos.posto
                     FROM tbl_os tos
                     JOIN tbl_fabrica tf ON tf.fabrica = tos.fabrica
                     JOIN tbl_posto_fabrica tpf ON tpf.fabrica = tf.fabrica AND tpf.posto = tos.posto
                     WHERE tos.os = {$osId}
                     AND tf.fabrica = {$login_fabrica}";
        $rPostoId = pg_query($con, $qPostoId);
        $postoId  = pg_fetch_result($rPostoId, 0, 'posto');
    }

    $qTipoPosto = "SELECT ttp.tipo_posto,
                          ttp.ativo,
                          ttp.posto_interno
                   FROM tbl_tipo_posto ttp
                   JOIN tbl_posto_fabrica tpf ON tpf.tipo_posto = ttp.tipo_posto
                   JOIN tbl_posto tp ON tp.posto = tpf.posto
                   WHERE tp.posto = {$postoId}
                   AND tpf.fabrica = {$login_fabrica}
                   AND ttp.posto_interno IS TRUE";
    $rTipoPosto = pg_query($con, $qTipoPosto);

    if (strlen(pg_last_error()) == 0 AND pg_num_rows($rTipoPosto) > 0)
        $tipo_posto_interno = 't';

    if ($tipo_posto_interno == 't') { 
        $dataSaidaProduto = "";
        $dataChegadaProduto = "";
        $qCamposAdicionais = "SELECT oce.campos_adicionais::jsonb->>'dataSaidaProduto' data_saida,
                                     oce.campos_adicionais::jsonb->>'dataChegadaProduto' data_chegada
                              FROM tbl_os_campo_extra oce
                              JOIN tbl_os o ON o.os = oce.os AND o.fabrica = {$login_fabrica}
                              WHERE o.os = {$osId}
                              AND o.fabrica = {$login_fabrica}";
        $rCamposAdicionais = pg_query($con, $qCamposAdicionais);
        if (pg_num_rows($rCamposAdicionais) > 0) {
            $dataSaidaProduto = DateTime::createFromFormat('Y-m-d H:i:s', pg_fetch_result($rCamposAdicionais, 0, 'data_saida'));
            $dataChegadaProduto = DateTime::createFromFormat('Y-m-d H:i:s', pg_fetch_result($rCamposAdicionais, 0, 'data_chegada'));
        }
    ?>
        <style type="text/css">
            .data-produto-container {
                background-color:#CED7E7;
                height:120px;
                width:695px;
                margin:10px auto;
                padding:20px 0
            }

            .data-produto-block {
                width:25%;
                height:inherit;
                float:left;
            }

            .data-produto-block label {
                font-size:12px;
            }

            .data-produto-block input {
                width:75%;
                margin:0 auto;
            }

            .data-produto-container button {
                margin-top: 10px;
            }
        </style>
        <script type="text/javascript">
            $(function () {
                $("input[name=saida_produto").datepick({startDate:'01/01/2000'});
                $("input[name=chegada_produto").datepick({startDate:'01/01/2000'});

                $("#gravar-saida-chegada").on("click", function () {
                    var dataSaida = $("#saida-produto").val();
                    var dataChegada = $("#chegada-produto").val();

                    $.ajax({
                        url: window.location,
                        type: 'POST',
                        async: true,
                        data: {
                            ajax: 'gravaSaidaChegada',
                            dataSaida: dataSaida,
                            dataChegada: dataChegada
                        }
                    }).done(function (response) {
                        try {
                            response = JSON.parse(response);
                        } catch (err) {
                            alert('Falha ao atualizar informações');
                        }

                        if (response.exception) {
                            alert(response.exception);
                        } else {
                            alert(response.message);
                        }
                    });
                });
            });
        </script>
        <div class="row data-produto-container">
            <form class="form" style="height:50px">
                <div class="data-produto-block" style="width: 20% !important;"></div>
                <div class="data-produto-block" style="width: 30% !important;">
                    <label for="saida-produto">Data de saída do produto:</label>
                    <input id="saida-produto" value="<?= (!empty($dataSaidaProduto)) ? $dataSaidaProduto->format('d/m/Y') : "" ?>" name="saida_produto" type="text">
                </div>
                <div class="data-produto-block" style="width: 30% !important;">
                    <label for="chegada-produto">Data de chegada do produto:</label>
                    <input id="chegada-produto" value="<?= (!empty($dataChegadaProduto)) ? $dataChegadaProduto->format('d/m/Y') : "" ?>" name="chegada_produto" type="text">
                </div>
                <div class="data-produto-block" style="width: 20% !important;"></div>
                <button type="button" id="gravar-saida-chegada" name="gravar_saida_chegada">Gravar</button>
            </form>
        </div>
<?php 
    }
} 
?>


<?php
if (!empty($data_conserto) && $usaLaudoTecnicoOs && $LU_tecnico_posto === true) {
    $sqlLaudoTecnico = "
        SELECT ordem FROM tbl_laudo_tecnico_os WHERE fabrica = {$login_fabrica} AND os = {$os}
    ";
    $resLaudoTecnico = pg_query($con, $sqlLaudoTecnico);
    
    if (pg_num_rows($resLaudoTecnico) > 0) {
        ?>
        <br /><br />
        <button type='button' class='btn-visualizar-laudo-tecnico' data-os='<?=$os?>' style='cursor: pointer;' >Laudo Técnico</button><br />
        <?php
        if ($login_fabrica == 175 && pg_fetch_result($resLaudoTecnico, 0, 'ordem') == $login_unico) {
        ?>
            <br />
            <button type='button' class='btn-certificado-calibracao' data-os='<?=$os?>' style='cursor: pointer;' >Certificado de Calibração</button><br />
        <?php
        }
        ?>
        <script>
            
        $(function() {
            Shadowbox.init();
            
            $(document).on('click', '.btn-visualizar-laudo-tecnico', function() {
                let os = $(this).data('os');
            
                Shadowbox.open({
                    content: '<div style=\'text-align: center; background-color: #FFF;\' ><h1><i class=\'fa fa-spinner fa-pulse\'></i> Gerando Laudo Técnico</h1></div>',
                    player: 'html',
                    height: 48,
                    options: {
                        modal: true,
                        enableKeys: false,
                        displayNav: false
                    }
                });
                
                setTimeout(function() {
                    $.ajax({
                        url: 'os_consulta_lite.php',
                        type: 'get',
                        data: {
                            ajax: 'busca_laudo_tecnico_os',
                            os: os,
                            readonly: true
                        },
                        async: true,
                        timeout: 60000
                    }).fail(function(res) {
                        alert('Erro ao gerar laudo técnico');
                        Shadowbox.close();
                    }).done(function(res, req){
                        if (req == 'success') {
                            res = JSON.parse(res);
                            
                            if (res.erro) {
                                alert(res.erro);
                                Shadowbox.close();
                            } else {
                                Shadowbox.close();
                                
                                setTimeout(function() {
                                    Shadowbox.open({
                                        content: '<div id=\'sb-player\' ></div>',
                                        player: 'html',
                                        height: window.innerHeight,
                                        width: window.innerWidth,
                                        options: {
                                            modal: true,
                                            enableKeys: false,
                                            onFinish: function() {
                                                let player = $('#sb-player');
                                                let iframe = $('<iframe></iframe>', { 
                                                    src: 'os_laudo_tecnico.php?os='+os+'&readonly=true', 
                                                    css: {
                                                        height: '100%',
                                                        width: '100%'
                                                    }
                                                });
                                                
                                                $(iframe).on('load', function(e) {
                                                    e.target.contentWindow.postMessage('setFbData|'+res.titulo, '*');
                                                    
                                                    let data = {
                                                        edit: false,
                                                        title: 'Laudo Técnico - OS '+res.sua_os,
                                                        logo: $('#logo_fabrica').attr('src'),
                                                        formData: res.observacao,
                                                        noActions: true
                                                    };
                                                    
                                                    e.target.contentWindow.postMessage('toggleFbEdit|'+JSON.stringify(data), '*');
                                                });
                                                
                                                $(player).html(iframe);
                                                $(player).css({ overflow: 'hidden' });
                                            }
                                        }
                                    });
                                }, 1000);
                            }
                        } else {
                            alert('Erro ao gerar laudo técnico');
                            Shadowbox.close();
                        }
                    });
                }, 1000);
            });
            
            <?php
            if (in_array($login_fabrica, array(175))) {
            ?>
                $('.btn-certificado-calibracao').on('click', function() {
                    let os = $(this).data('os');
                    
                    window.open('certificado_calibracao.php?os='+os);
                });
            <?php
            }
            ?>
        });
            
        </script>
    <?php
    }
}
?>

<!-- Finaliza inclusão do laudo técnico -->
<?
if(strlen(trim($_GET['lu_fabrica'])) == 0) { # HD 153966
    if (in_array($login_fabrica, array(51, 81)) and $status_os==62) { ?>
    <form name='frm_intervencao' method=post action="<? echo "$PHP_SELF?os=$os"; ?>">
        <br>
        <table width='700px' border='0' cellspacing='1' cellpadding='2' align='center' class='tabela'>
            <tr>
                <td class='inicio'> <?= traduz("retirar.a.intervencao.da.fabrica") ?></td>
            </tr>
            <tr>
                <td class='conteudo'><center>
                    <input type="hidden" name="btn_acao" value="">
                    <input type="button" name="btn_ajuste1" style='cursor:pointer;background:#C0C0C0;' value="Ajuste elétrico"
                    onclick="
                        if(document.frm_intervencao.btn_acao.value == ''){
                            document.frm_intervencao.btn_acao.value='670';
                            document.frm_intervencao.submit()
                        } else {
                            alert('Aguarde submissao.')
                        }">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="submit" name="btn_ajuste2" style='cursor:pointer;background:#C0C0C0;' value="Ajuste mecânico"
                    onclick="
                        if(document.frm_intervencao.btn_acao.value == ''){
                            document.frm_intervencao.btn_acao.value='671';
                            document.frm_intervencao.submit()
                        } else {
                            alert('Aguarde submissao.')
                        }">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="submit" name="btn_sem_peca" style='cursor:pointer;background:#C0C0C0;' value="Conserto sem peça"
                    onclick="
                        if(document.frm_intervencao.btn_acao.value == ''){
                            document.frm_intervencao.btn_acao.value='733';
                            document.frm_intervencao.submit()
                        } else {
                            alert('Aguarde submissao.')
                        }">
                </td>
            </tr>
        </table>
    </form>
<?}?>

<?php

    if($login_fabrica == 30){
        if ($S3_sdk_OK) {
            include_once S3CLASS;

            $s3tj = new anexaS3('tj', (int) $login_fabrica);
            $S3_online = is_object($s3tj);

            if ($s3tj->temAnexos($os)) {
                $link = getAttachLink($s3tj->url, '', true);
                echo "<br>";
                echo "<table align='center' class='tabela'>
                        <tr class='titulo_tabela'>
                            <td>".traduz("comprovante.troca.produto./.processo.judicial")."</td>
                        </tr>
                        <tr>
                            <td align='center'>";
                                echo createHTMLLink($link['url'],"<img width='64' src='imagens/{$link['ico']}' />", "target='_blank'");
                echo "      </td>
                        </tr>
                      </table>";
            }
        }
    }
    if($login_fabrica == 117){
        if ($S3_sdk_OK) {
            include_once S3CLASS;

            $s3ge = new anexaS3('ge', (int) $login_fabrica);
            $S3_online = is_object($s3ge);

            if ($s3ge->temAnexos($os)) {
                $link = getAttachLink($s3ge->url, '', true);
                if($link['ico'] == 'image.ico'){
                    $link['ico'] = $link['url'];
                }else{
                    $link['ico'] = "imagens/image.ico";
                }
                echo "<br>";
                echo "<table align='center' class='tabela'>
                        <tr class='titulo_tabela'>
                            <td style='color: #fff; font-family: Arial; font-size: 8pt; background-color: #485989;'>".traduz("comprovante.garantia.estendida")."</td>
                        </tr>
                        <tr>
                            <td align='center'>";
                                echo createHTMLLink($link['url'],"<img width='100' src='{$link['ico']}' />", "target='_blank'");
                echo "      </td>
                        </tr>
                      </table>";
            }
        }
    }

    if($login_fabrica == 114){

        include_once "class/aws/s3_config.php";
        include_once S3CLASS;

        $s3 = new AmazonTC("os", $login_fabrica);
        $selo_obrigatorio = $s3->getObjectList("selo_{$login_fabrica}_{$os}");
        $selo_obrigatorio = basename($selo_obrigatorio[0]);
        $selo_obrigatorio = $s3->getLink($selo_obrigatorio);

        if(strlen($selo_obrigatorio) > 0){

        ?>
        <br />

        <TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
            <TR>
                <TD>
                    <font size='2' color='#FFFFFF'>
                        <center><b><?= traduz("imagem.selo.obrigatorio") ?></b></center>
                    </font>
                </TD>
            </TR>
            <TR>
                <TD class='conteudo' style="text-align: center; padding: 10px;">
                    <?php
                        echo "<a href='{$selo_obrigatorio}' target='_blank'><img src='{$selo_obrigatorio}' style='max-width: 150px; max-height: 150px;_height:150px;*height:150px;' /></a>";
                    ?>
                </TD>
            </TR>
        </TABLE>

        <?php
        }

    }

        //HD 367384 - INICIO
        //EBANO: não funciona fora do Brasil
        $sql = "select pais from tbl_os join tbl_posto on tbl_os.posto = tbl_posto.posto where os = $os;";
        $res = pg_query ($con,$sql);
        if (pg_num_rows ($res) >0) {
            $sigla_pais = pg_fetch_result ($res,0,pais);
        }

        /* Google Maps */

        //HD 367384 - INICIO
        if ($consumidor_revenda[0] == 'C' && $sigla_pais == "BR" && in_array($login_fabrica, array(11,114,120,115,35,91,116,94,117,74,172)) and 1==2){

            $sql_end_posto = "select contato_endereco, contato_numero, contato_cidade, contato_estado, contato_cep
            from tbl_posto_fabrica join tbl_posto using(posto)
            where posto = $login_posto and fabrica = $login_fabrica";
            $res_end_posto = pg_query($con, $sql_end_posto);

            if(pg_num_rows($res_end_posto) > 0){

                /* Endereço do Posto */
                while($end = pg_fetch_object($res_end_posto)){
                    if($end->contato_endereco != "" or $end->contato_cidade != "" or $end->contato_estado != ""){
                        if($end->contato_numero != ""){ $end->contato_numero = ", ".$end->contato_numero; }
                        $end_posto = $end->contato_endereco."".$end->contato_numero.", ".$end->contato_cidade.", ".$end->contato_estado.", Brasil";
                    }else{
                        $end_posto = $end->contato_cep;
					}
					$posto_cep = $end->contato_cep;
					$posto_cep_mapa = "cep: ".preg_replace('/(\d{5})(\d{3})/','${1}-${2}',$posto_cep);

                }

                /* Endereço do Consumidor */
                if($consumidor_endereco != "" or $consumidor_cidade != "" or $consumidor_estado != ""){
                    if($consumidor_numero != ""){ $consumidor_numero = ", ".$consumidor_numero; }
                    $end_cons = $consumidor_endereco."".$consumidor_numero.", ".$consumidor_cidade.", ".$consumidor_estado.", Brasil";
                }else{
                    $end_cons = $consumidor_cep;
                }

				$consumidor_cep_mapa = "cep: ".preg_replace('/(\d{5})(\d{3})/','${1}-${2}',$consumidor_cep);
                ?>

                    <!-- CSS e JavaScript Google Maps -->
                    <link href="https://developers.google.com/maps/documentation/javascript/examples/default.css" rel="stylesheet">
                    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"></script>

                    <style type="text/css">
                        #GoogleMapsContainer{
                            width: 698px;
                            height: 400px;
                            border: 1px solid #000;
                            margin: 0 auto;
                            margin-bottom: 20px;
                        }
                        #DirectionPanel{
                            width: 249px;
                            height: 400px;
                            float: right;
                            background-color: #fff;
                            overflow: auto;
                        }
                        #GoogleMaps{
                            width: 449px;
                            height: 400px;
                            float: left;
                            background-color: #fff;
                        }

                        @media print {
                            #mostraMapa, #GoogleMapsContainer {
                                display: none;
                            }
                        }

                        button{
                            width: 110px;
                        }
                    </style>

                    <script type="text/javascript">

                        var directionsDisplay = new google.maps.DirectionsRenderer();
                        var directionsService = new google.maps.DirectionsService();
                        var map;

                        function initialize() {
                            var mapOptions = {
                              zoom: 7,
                              mapTypeId: google.maps.MapTypeId.ROADMAP,
                              center: new google.maps.LatLng(41.850033, -87.6500523)
                            };
                            map = new google.maps.Map(document.getElementById('GoogleMaps'),
                                mapOptions);

                            directionsDisplay.setMap(map);

                            directionsDisplay.setPanel(document.getElementById('DirectionPanel'));

                            var request = {
                                  origin: '<?=$end_posto;?>',
                                  destination: '<?=$end_cons;?>',
                                  travelMode: google.maps.DirectionsTravelMode.DRIVING
                            };
							directionsService.route(request, function(response, status) {
								var km = response.routes[0].legs[0].distance.value;
								km = parseFloat(km);
								km = km /1000;
								var distancia_km = parseFloat(<?=$qtde_km;?>);
								if(km*2 - distancia_km > 300) {
									status = 'no';
								}

                                if (status == google.maps.DirectionsStatus.OK) {
                                    directionsDisplay.setDirections(response);
								}else{
										var posto =  '<?=$posto_cep_mapa;?>,Brasil';
										var consumidor =  '<?=$consumidor_cep_mapa;?>,Brasil';
										var request = {
										  origin: posto,
										  destination: consumidor,
										  travelMode: google.maps.DirectionsTravelMode.DRIVING
									  };
										directionsService.route(request, function(response, status) {
												if (status == google.maps.DirectionsStatus.OK) {
													directionsDisplay.setDirections(response);
												}
										});
								}
                            });
                        }

                        function opcaomapa(){
                            $('#GoogleMapsContainer').toggle();
                            if(!$('#GoogleMapsContainer').is(':visible')){
                                $('button[name=opcaomapa]').html('Ver Mapa');
                            }else{
                                $('button[name=opcaomapa]').html('Ocultar Mapa');
                            }
                        }

                        google.maps.event.addDomListener(window, 'load', initialize);

                    </script>

                    <p id="mostraMapa"><br /> <b>Mapa entre o Posto e o Consumidor</b> <button name='opcaomapa' onclick='opcaomapa()'>Ocultar Mapa</button> </p> <br />

                    <div id="GoogleMapsContainer">
                        <div id="GoogleMaps"></div>
                        <div id="DirectionPanel"></div>
                    </div>

                <?php

            }else{
                echo '<br /> <p id="mostraMapa"><b>Mapa entre o Posto e o Consumidor</b></p>';
                echo "<p style='color: red;'>Endereço do Posto não localizado, por favor verifique os dados informados.</p>";
            }

        }
    //HD 367384 - FIM - Fim Google Maps
if ($login_fabrica==125){

?>
<br/><br/>

<div name="container_img" id='container_img' style="display: block;">

     <?
        //verifica se tem imagens de peças criticas
        $sqlPecaCritica = " SELECT  referencia,
                                    servico_realizado
                            FROM tbl_os_item
                            JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                            JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
                            JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
                            WHERE fabrica_i = $login_fabrica AND
                                  tbl_os.os = $os AND
                                  tbl_peca.peca_critica is true AND
                                  servico_realizado in (10740,10741,10742)";



        $res = pg_query($con,$sqlPecaCritica);
        $qtde_pecas_criticas = pg_num_rows($res);
        if($qtde_pecas_criticas > 0){ ?>
        <span>Fotos das Peças Críticas</span><br/><br/>
        <? }
        for ($i = 0; $i < $qtde_pecas_criticas; $i++) {

            $referencia_peca_critica = pg_fetch_result($res, $i, "referencia");
            $servico_realizado = pg_fetch_result($res, $i, "servico_realizado");

            $amazonTC->getObjectList("peca_critica-{$os}-{$referencia_peca_critica}-",false,"","");
            $pathinfo = pathinfo($amazonTC->files[0]);
            $dadosPeca = explode("-", $pathinfo["filename"] );
            $linhaPeca = $dadosPeca[3];
            $link = $amazonTC->getLink($pathinfo["basename"]);
            $linkMini = $amazonTC->getLink("thumb_".$pathinfo["basename"]);
            ?>

            <span>Referência Peça: <?=$referencia_peca_critica?></span>
            <a href="<?=$link?>"><img id="mini_anexar_img_<?=$linhaPeca?>" name="mini_anexar_img_<?=$linhaPeca?>" src="<?=$linkMini?>" title="Clique aqui para inserir a imagem" alt="Clique aqui para inserir a imagem" onclick='javascript: $("#img_peca_critica_<?=$linhaPeca?>").click();'></img></a>
            <input type="hidden" value="true" id="peca_critica_<?=$linhaPeca?>" name="peca_critica_<?=$linhaPeca?>" />
            <input type="hidden" value="<?=$pathinfo["extension"]?>" id="peca_critica_ext_<?=$linhaPeca?>" name="peca_critica_ext_<?=$linhaPeca?>" />

            <br/>

    <? }?>

</div>
<br/>
<?
}

if (in_array($login_fabrica, array(141))) {
    $sql = "SELECT laudo_tecnico FROM tbl_os WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND os = {$os}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $laudo   = pg_fetch_result($res, 0, "laudo_tecnico");

        if (in_array($laudo, array("descarga_eletrica", "nao_atende_requisitos_garantia", "nao_comercializado"))) {
            echo "<br /><button type='button' onclick=\"window.open('laudo_produto_fora_garantia.php?os={$os}');\" style='cursor: pointer;' >Imprimir laudo de produto fora de garantia</button><br />";
        }
    }
}

?>

<BR>
<!-- =========== FINALIZA TELA NOVA============== -->

<?
    $origem = $_GET['origem'];
?>
<?php 
if($login_fabrica == 24){
    $sql_auditoria_suggar = "SELECT tdocs, tdocs_id, obs 
                                FROM tbl_tdocs
                                WHERE contexto = 'comprovante_retirada'
                                AND referencia_id = {$os} AND situacao = 'ativo' and tbl_tdocs.fabrica = $login_fabrica order by tdocs desc limit 1";

                $res_auditoria_suggar = pg_query($con, $sql_auditoria_suggar); 
                
                 ?>
                
                <table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
					<thead>
                        <tr class='inicio'>
                                <td colspan='4'>
                                    <center>Comprovante de Retirada</center>
                                </td>
                        </tr>
                        </thead>
                <?php if(pg_num_rows($res_auditoria_suggar) > 0) { ?>
                        <tr class='titulo2'>
                            
                            <th width="50%" class='titulo2'><?= traduz("Descrição")  ?></th>
                            <th width="22%" class='titulo2'><?= traduz("Ações") ?></th>
                        </tr>
					
                
					<tbody>
                    <?php 
                        $referencia_tdocs  = pg_fetch_result($res_auditoria_suggar, 'tdocs');				
                        $link		       = pg_fetch_result($res_auditoria_suggar, 'tdocs_id');
                    ?>

                        <tr>
                            <td align="center" class='justificativa'>Comprovante de Retirada do Produto</td>
                            <td align="center" class='justificativa'><button class="btn btn-success" onclick="visualizaranexo('<?=$link ?>')"> Visualizar </button></td>
                        </tr>
                    </tbody>
                <?php } else { echo "<tbody><tr><td  align='center' class='justificativa'>Não anexado</td></tr></tbody>"; } ?>
                </table>
        <br>
        <br>
        <br>
        <br>
        <br>
<?php 
} ?>
<table cellpadding='10' cellspacing='0' border='0' align='center'>
<? if($login_fabrica == 20) {?>
    <tr>
        <td id='mensagem'></td>
    </tr>
<?}?>
<tr>
<? if($sistema_lingua == "ES"){ ?>
    <td><a href="os_cadastro.php"><input type='button' value="Abrir nueva Orden de Servicio"></a></td>
<? }elseif ($origem == 'troca'){ ?>
    <td><a href="os_cadastro_troca.php"><input type='button' value="Lan&ccedil;ar nova Ordem de Servi&ccedil;o"></a></td>
<? } elseif($login_fabrica <> 52) {

    $url_os = (in_array($login_fabrica, array(138,142,143,145))) ? "cadastro_os.php" : "os_cadastro.php";
    
    if ($login_fabrica == 178) {
        $url_os = "cadastro_os_revenda.php";
    }
    
    if($login_fabrica == 30){
                    $sql_estado = "
                        SELECT  tbl_posto_fabrica.contato_estado, tbl_posto_fabrica.parametros_adicionais
                        FROM    tbl_posto_fabrica
                        WHERE   tbl_posto_fabrica.posto     = $login_posto
                        AND     tbl_posto_fabrica.fabrica   = $login_fabrica
                    ";

                    $res_estado = pg_query($con,$sql_estado);
                    $resultContatoEstado = pg_fetch_result($res_estado,0,contato_estado);

                    $json_parametros_adicionais = pg_fetch_result($res_estado,0,parametros_adicionais);
                    $array_parametros_adicionais = json_decode($json_parametros_adicionais);

                    $posto_digita_os_consumidor = $array_parametros_adicionais->digita_os_consumidor;

        $display = ($posto_digita_os_consumidor && $posto_digita_os_consumidor != 't') ? "style='display:none;'" : "";
    }
    ?>
    <td <?=$display?>><a href="<?=$url_os?>"><input type='button' value="Lan&ccedil;ar nova Ordem de Servi&ccedil;o"></a></td>
<? }
    /* hd_chamado=2843341
    if($login_fabrica == 20){
        echo "<TD><a href='os_comprovante_servico_print.php?os=$os'><img src='imagens/";
        if($sistema_lingua=="ES")echo "es_";
        echo "btn_comprovante.gif'></a></TD>";
    }
    */

    if (in_array($login_fabrica, array(169, 170))) {
        $sql_familia = "
            SELECT tbl_familia.deslocamento
            FROM tbl_os_produto
            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
            WHERE tbl_os_produto.os = {$os}
            AND tbl_produto.produto_principal IS TRUE
        ";
        $res_familia = pg_query($con, $sql_familia);

        if (pg_num_rows($res_familia) > 0) {
            $x_familia_deslocamento = pg_fetch_result($res_familia, 0, "deslocamento");

            $sql_os_numero = "SELECT os_numero FROM tbl_os WHERE fabrica = {$login_fabrica} AND os_numero = {$os}";
            $res_os_numero = pg_query($con, $sql_os_numero);

            if (pg_num_rows($res_os_numero) > 0) {
                $x_os_numero = true;
            } else {
                $x_os_numero = false;
            }

            if (strtolower($x_familia_deslocamento) == "t" && !empty($hd_chamado) && empty($os_numero) && !$x_os_numero) {
            ?>
                <td>
                    <span style='display:inline-block;_zoom:1;width: 3em'>&nbsp;</span>
                    <button type="button" onclick="window.open('cadastro_os.php?chave_os_conjunto=<?=sha1($os.$login_fabrica)?>&os=<?=$os?>');" ><?= traduz("abrir.ordem.de.servico.para.o.produto.do.conjunto") ?></button>
                </td>
            <?php
            }
        }
    }

    if(in_array($login_fabrica, [167,177,191,203]) AND $nome_atendimento == "Orçamento"){
        $dados_orcamento_email['geral']["os"]                   = $os;
        $dados_orcamento_email['geral']["produto_referencia"]   = utf8_encode($produto_referencia);
        $dados_orcamento_email['geral']["produto_descricao"]    = utf8_encode($produto_descricao);
        $dados_orcamento_email['geral']["produto_referencia"]   = $produto_referencia;
        $dados_orcamento_email['geral']["produto_descricao"]    = utf8_encode($produto_descricao);
        $dados_orcamento_email['geral']["numero_serie"]         = ($numero_serie) ? $numero_serie : '' ;
        $dados_orcamento_email['geral']["mao_de_obra"]          = ($mao_de_obra)  ? $mao_de_obra : '' ;
        $dados_orcamento_email['geral']["valor_total_pecas"]    = number_format($valor_total_pecas, 2, ",", ".");
        $dados_orcamento_email['geral']["valor_adicional"]      = number_format($valor_adicional, 2, ",", ".");
        $dados_orcamento_email['geral']["total_geral"]          = number_format($total_geral, 2, ",", ".");

        $dados_orcamento_email['geral']["email_consumidor"]     = $consumidor_email;
        $dados_orcamento_email['geral']["posto_nome"]           = utf8_encode($posto_nome);

        $dados_orcamento_email = json_encode($dados_orcamento_email,true);
                #$dados_orcamento_email = str_replace("\\", "\\\\", json_encode($dados_orcamento_email));
    ?>
        <input type="hidden" id="dados_email" value='<?=$dados_orcamento_email?>'>
        <button onclick="enviar_orcamento();" ><?= traduz("enviar.orcamento.para.o.consumidor") ?></button>
    <?php
    }
?>
    <td><span style='display:inline-block;_zoom:1;width: 3em'>&nbsp;</span>
        <?php 
            if ($login_fabrica == 178){ 
                $os_link = explode("-", $sua_os);
        ?>
            <a href="os_revenda_print.php?os_revenda=<? echo $os_link[0] ?>" target="_blank">
                <input type='button' value="Imprimir">
            </a>
        <?php } else { ?>
            <a href="os_print.php?os=<? echo $os ?>" target="_blank">
                <input type='button' value="Imprimir">
            </a>
        <?php }
            if (in_array($login_fabrica, [80])) { ?>
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;<a class='btn btn-success' href='imprimir_etiqueta_unica.php?imprimir=true&os=<?=$_GET['os']?>' target='_blank'>Imprimir Etiqueta</a>
            <?php
            }
         ?>
    </td>
    <td>

    <?php
    // CHAMADO: 6641566

    if ($login_fabrica == 144) {
        if (($login_posto_interno == true && $consumidor_revenda == 'REVENDA') || ($login_posto == 376421)) {
        $j_sua_os = (explode('-', $sua_os))[0];
    ?>
        <td> 
            <a target="_blank" href="etiqueta_hikari.php?os=<?=$j_sua_os?>">
                <button style='margin-left: 30px'>Etiqueta Hikari</button>    
            </a>
        </td>        
    <?php } 
    }?>

    <?
    if($login_fabrica == 20) {
        if(strlen($data_fechamento) == 0) { // HD 61323
            echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,fechar) ; }\"><img id='fechar' src='imagens/btn_fechar_azul.gif'></a>";
        }
    }
    ?>
    </td>
</tr>
<?php if(in_array($login_fabrica,array(165)) && $posto_interno == true) {?>
    <tr>
        <td colspan="10" align="center"><br /><br />
            <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_os&id=<?php echo $os; ?>' name="btnAuditorLog"><?= traduz("visualizar.log.auditor") ?></a>
        </td>
      </tr>
<?php }?>


<?php

    if ($login_fabrica == 15) {

        $sql = "SELECT os
                FROM tbl_os
                WHERE os = $os
                AND tipo_atendimento IN (20,21)";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res)) {

?>

            <table style="font-size:11px;" width="700px">
                <tr>
                    <td align="left"><img src="admin/imagens_admin/status_verde.gif" />&nbsp; <?= traduz("aprovadas.automaticamente.e.da.auditoria.de.km") ?></td>
                </tr>
                <tr>
                    <td align="left"><img src="admin/imagens_admin/status_azul.gif" />&nbsp;<?= traduz("aprovadas.com.alteracao.da.auditoria.de.km") ?></td>
                </tr>
                <tr>
                    <td align="left"><img src="admin/imagens_admin/status_amarelo.gif" />&nbsp;<?= traduz("em.analise.de.km") ?></td>
                </tr>
                <tr>
                    <td align="left"><img src="admin/imagens_admin/status_vermelho.gif" />&nbsp;<?= traduz("reprovadas.da.auditoria.de.km") ?></td>
                </tr>
            </table>

<?php

            $sql = "SELECT
                        status_os,
                        observacao,
                        tbl_status_os.descricao
                    FROM tbl_os_status
                    JOIN tbl_status_os using(status_os)
                    WHERE os=$os
                    ORDER BY data DESC";
            $res = pg_query($con,$sql);

            echo '<table class="tabela" width="700px" cellspacing="1" style="">
                    <tr>
                       <td colspan="2" class="titulo_tabela">STATUS DA OS</td>
                   </tr>
                   <tr class="titulo_coluna">
                       <td>Status</td>
                       <td>OBS</td>
                   </tr>';

           for ($i=0; $i< pg_num_rows($res); $i++) {

               $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

               $desc_status = pg_result($res,$i,'descricao');
               $obs         = pg_result($res,$i,'observacao');
               $status      = pg_result($res,$i,'status_os');

               $img         = get_status_img($status);

               $img_src     = !empty($img) ? '<img src="admin/imagens_admin/'.$img.'" />' : '';

               echo '  <tr bgcolor="'.$cor.'">
                           <td align="left"> '.$img_src.' &nbsp;'.$desc_status.'</td>
                           <td align="left">'.$obs.'</td>
                       </tr>';

           }

           if (pg_num_rows($res) == 0) {

               echo '  <tr bgcolor="'.$cor.'">
                           <td align="left" colspan="2">
                               &nbsp;<img src="admin/imagens_admin/status_verde.gif" /> &nbsp;OS Aprovada Automaticamente
                           </td>
                       </tr>';

           }

           echo '</table>';
        }
    }
?>


</table>

<?
}

if($login_fabrica == 52){
    $sql = "SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
                       tbl_os_status.observacao,
                       tbl_status_os.descricao,
                       tbl_admin.nome_completo
                    FROM tbl_os_status
                    JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os_status.status_os
                    LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin
                    WHERE tbl_os_status.os = $os
                    AND tbl_os_status.fabrica_status = $login_fabrica
                    ORDER BY tbl_os_status.data DESC";

        $res   = pg_query($con, $sql);
        $total = pg_num_rows($res);

        if (pg_num_rows($res) > 0) {?>

            <table align='center' width='700px' class='Tabela' cellpadding='1' cellspacing='1' border='0'>
                <tr class='inicio'>
                    <td colspan='4' align='center'>STATUS DA OS</td>
                </tr>
                <tr class='titulo2'>
                    <td><?= traduz("data") ?></td>
                    <td>STATUS</td>
                    <td>OBS</td>
                    <td>ADMIN</td>
                </tr><?php

        }

        for ($i = 0; $i < $total; $i++) {

            $data      = pg_fetch_result($res, $i, 'data');
            $obs       = pg_fetch_result($res, $i, 'observacao');
            $descricao = pg_fetch_result($res, $i, 'descricao');
            $admin     = pg_fetch_result($res, $i, 'nome_completo');

            /**
             *
             * HD 739078 - Latinatec prazo é 60 dias para entrar em auditoria.
             *
             */
            if ($login_fabrica == 15) {
                $obs       = str_replace("90", "60", $obs);
                $descricao = str_replace("90", "60", $descricao);
            }

            $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

            echo "<tr bgcolor='$cor'>";
                echo "<td class='justificativa'><b>$data &nbsp;</b></td>";
                echo "<td class='justificativa' align='left'>$descricao &nbsp;</td>";
                echo "<td class='justificativa' align='left'>$obs &nbsp;</td>";
                echo "<td class='justificativa' align='left'>$admin</td>";
            echo "</tr>";

        }?>
    </table><?php
}
//HD 150981 - Mostra a imagem da nota fiscal se disponíl
/*
 echo "CAMINHO DA IMAGEM =".$imagem_nota = "nf_digitalizada/" . $os . ".jpg";

 if (file_exists($imagem_nota))
 {
         echo "
         <div align=center>
         <br>
         <font style='font-size:12pt'>Nota fiscal do produto:</font><br>
    <img src='$imagem_nota'>
    </div>
    ";
 }
*/

 echo "<div id='historicoCorreios' style='display:none;'></div>";

include ($_GET['lu_os'] == 'sim') ? "login_unico_rodape.php" :"rodape.php"; ?>
<?php

$mostra_valor_faturada = trim($_GET['mostra_valor_faturada']);

if($mostra_valor_faturada =='sim' and !empty($os)) { // HD 181964
    echo "<script>window.open('produto_valor_faturada.php?os=$os','','height=300, width=650, top=20, left=20, scrollbars=yes')</script>";
}

function getPedidoTroca($os){
    global $con;
    global $login_fabrica;
    $sql = 'SELECT * FROM tbl_os_troca WHERE os = $1 AND fabric = $2 ORDER BY os_troca DESC LIMIT 1';
    $params = array($os,$login_fabrica);
    $result = pg_query_params($con,$sql,$params);
    if(!$result)
        throw new Exception(pg_last_error($con));
    $osTroca = pg_fetch_assoc($result);
    pg_free_result($result);
    return $osTroca;
}
