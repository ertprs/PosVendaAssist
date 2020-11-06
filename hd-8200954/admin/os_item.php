<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
require "../class/AuditorLog.php";

$admin_privilegios = "call_center";
include 'autentica_admin.php';
if($login_fabrica == 1){
    require "../classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);

    require "../classes/form/GeraComboType.php";
}

if($_POST["verifica_def_const_cor_etiqueta"] == true){

    $defeito_constatado = $_POST['defeito_constatado'];

    $sql = "select defeito_constatado, campos_adicionais from tbl_defeito_constatado where defeito_constatado = $defeito_constatado  ";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res)>0){
        $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'),true);
        $pedir_cor_etiqueta = $campos_adicionais['pedir_cor_etiqueta'];
    }

    if($pedir_cor_etiqueta == true){
        echo "sim";
    }else{
        echo "nao";
    }

    exit;
}

if (isset($_GET['ajax_carrega_checklist'])) {
    $familia            = $_GET['familia'];
    $defeito_constatado = $_GET['defeito_constatado'];
    $defeito_reclamado = $_GET['defeito_reclamado'];
    $tipo_atendimento   = $_GET['tipo_atendimento'];
    $check   = $_GET['check'];
    
    if ($check == "instalacao_sem_defeito") {
        $condCheck = "AND codigo='52'";
    } 
    if ($check == "nao_avaliado") {
        $condCheck = "AND codigo='51'";
    } 
     $sql = "SELECT checklist_fabrica,
                    codigo,
                    descricao
                FROM tbl_checklist_fabrica
                WHERE fabrica = $login_fabrica
                AND familia = $familia
                {$condCheck}
                AND tipo_atendimento = $tipo_atendimento
                --AND defeito_constatado = $defeito_constatado";

    $res = pg_query($con, $sql);
    
    if (pg_num_rows($res) > 0) {

        for ($i=0; $i < pg_num_rows($res); $i++) {
            $checklist_fabrica = pg_fetch_result($res, $i, 'checklist_fabrica');
            $codigo = pg_fetch_result($res, $i, 'codigo');
            $descricao = pg_fetch_result($res, $i, 'descricao');

            if (mb_check_encoding($descricao, "UTF-8")) {
                $descricao = $descricao;
            }
            
            echo  "<tr>
                        <td class='txt_checklist'>
                            <input type='checkbox' disabled='disabled' checked='checked' > $codigo - $descricao
                            <input type='hidden' id='$checklist_fabrica'  name='check_list_fabrica[".$defeito_reclamado."][".$defeito_constatado."][]' value='$checklist_fabrica'>
                        </td>
                        <td class='txt_checklist'></td>
                    </tr>";
        }
    }
    exit;
}

if (isset($_POST['ajax_cancela_troca'])) {

    $os_troca   = $_POST['os_troca'];
    $peca       = $_POST['peca'];
    $os_produto = $_POST['os_produto'];

    try {

        pg_query($con, "BEGIN");

        $sql = "DELETE FROM tbl_os_troca WHERE os_troca = {$os_troca}";
        $res = pg_query($con, $sql);

        if (pg_last_error()) {
            throw new Exception("Erro ao excluir a troca");
        }

        $sql = "DELETE FROM tbl_os_item WHERE peca = {$peca} AND os_produto = {$os_produto}";
        $res = pg_query($con, $sql);

        if (pg_last_error()) {
            throw new Exception("Erro ao remover peça da OS");
        }

        $retorno = ["erro" => false,
                    "msg" => "Troca cancelada com sucesso"];

        pg_query($con, "COMMIT");

    } catch (Exception $e) {

        pg_query($con, "ROLLBACK");

        $retorno = [
            "erro" => true, 
            "msg" => utf8_decode($e->getMessage())
        ];

    }

    exit(json_encode($retorno));

}

if (isset($_GET['ajax_exclui_checklist_os']) && $_GET['ajax_exclui_checklist_os'] == true) {
    $checklist_fabrica = implode(",", $_GET['check_list_fabrica']);

    $sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
                  WHERE fabrica = $login_fabrica
                    AND os = $os AND checklist_fabrica IN({$checklist_fabrica})";
    $res = pg_query($con, $sql);


    $sqlEXT = "SELECT campos_adicionais  FROM tbl_os_campo_extra WHERE os=$os";
    $resEXT = pg_query($con,$sqlEXT);
    if (pg_num_rows($resEXT) > 0) {
        $campos_adicionais = json_decode(pg_fetch_result($resEXT, 0, 'campos_adicionais'),1);
        unset($campos_adicionais["condicao_instalacao"]);
        $campos_adicionaisENC = json_encode($campos_adicionais);
        $sqlENC = "UPDATE tbl_os_campo_extra SET campos_adicionais = '".$campos_adicionaisENC."' WHERE os=$os";
        $resENC = pg_query($con, $sqlENC);
    }


    if (pg_last_error()) {
        exit(json_encode(["erro" => true, "msg" => "Erro ao excluir checklist"]));
    }
    exit(json_encode(["erro" => false, "msg" => "Checklist excluido com sucesso"]));
}

if (isset($_GET['ajax_exclui_item_checklist']) && $_GET['ajax_exclui_item_checklist'] == true) {

    $checklist_fabrica            = $_GET['checklist_fabrica'];
    $os = $_GET['os'];
    $constatado = $_GET['constatado'];
    $reclamado   = $_GET['reclamado'];
    $sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
                  WHERE fabrica = $login_fabrica
                    AND checklist_fabrica  = $checklist_fabrica
                    AND os                 = $os
                    AND defeito_reclamado  = $reclamado
                    AND defeito_constatado = $constatado";
    $res = pg_query($con, $sql);
    if (pg_last_error()) {
        exit(json_encode(["erro" => true, "msg" => "Erro ao excluir checklist"]));
    }
    exit(json_encode(["erro" => false, "msg" => "Checklist excluido com sucesso"]));
}

if (isset($_REQUEST['ajax_defeito_reclamado_constatado'])) {
    $xoss = $_REQUEST['os'];
    $defeito_constatado = $_REQUEST['defeito_constatado'];
    $solucao = $_REQUEST['solucao'];

    if (!empty($defeito_constatado) && !empty($solucao)) {

	$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado WHERE fabrica = {$login_fabrica} AND os = {$xoss} AND defeito_constatado = {$defeito_constatado} AND solucao = {$solucao};";
	$res = pg_query($con, $sql);

	if (pg_last_error()) {
	    exit(json_encode(["erro" => true, "msg" => "Erro ao excluir Defeito"]));
	}

	exit(json_encode(["erro" => false, "msg" => "Defeito excluido com sucesso"]));
    }

}

$programa_insert = $_SERVER['PHP_SELF'];

if($login_fabrica == 88){
    $limite_anexos_nf = 5;
}

if($login_fabrica == 134){
    $tema = "Serviço Realizado";
    $temaPlural = "Serviços Realizados";
    $temaMPlural = "SERVIÇOS REALIZADOS";
    $temaMaiusculo = "SERVIÇO REALIZADO";
}else{
    $tema = "Defeito Constatado";
    $temaPlural = "Defeitos Constatados";
    $temaMPlural = "DEFEITOS CONSTATADOS";
    $temaMaiusculo = "DEFEITO CONSTATADO";
}
include_once __DIR__ . '/../class/AuditorLog.php';


if (isset($_POST['defeito_constatado_solucao'])) {

    $defeito_constatado = $_POST['defeito_constatado_solucao'];

    $querySolucao = "SELECT DISTINCT d.solucao, s.descricao
                     FROM tbl_diagnostico as d
                     JOIN tbl_solucao as s 
                        ON (s.solucao = d.solucao)
                     WHERE d.defeito_constatado = $defeito_constatado  
                     AND s.ativo = 't' 
                     AND d.fabrica = $login_fabrica";

    $res = pg_query($con, $querySolucao);

    $solucoes = [];

    for ($i = 0; pg_num_rows($res) > $i; $i++) {

        $id   = pg_fetch_result($res, $i, solucao);
        $desc = pg_fetch_result($res, $i, descricao);

        $solucoes[$id] = utf8_encode($desc);
        
    }

    exit(json_encode($solucoes));    
}

if ($login_fabrica == 3 && isset($_POST['buscaServicoRealizado'])) {

    header('Content-Type: text/html; charset=ISO-8859-1');
    $buscaServicoRealizado = trim($_POST['buscaServicoRealizado']);
    $os                    = trim($_POST['os']);

    if (strlen($buscaServicoRealizado) > 0) {

        if (strlen($os) > 0) {

            if($buscaServicoRealizado == "073894" ||  $buscaServicoRealizado == "073897" ||  $buscaServicoRealizado == "073373" || $buscaServicoRealizado == "771889"  || $buscaServicoRealizado == "074957"  || $buscaServicoRealizado == "773936") {
                $regarca_gas = 1;
            }

        } else {
            //RODRIGO PEDROSO
            $sql = "SELECT  bloqueada_garantia
                    FROM    tbl_peca
                    WHERE   fabrica = $login_fabrica
                    AND referencia  = '$buscaServicoRealizado'
                    AND bloqueada_garantia IS TRUE";

            $res = pg_query($con,$sql) ;

            if (pg_num_rows($res)>0){
                $bloqueada_garantia = pg_fetch_result($res,0,'bloqueada_garantia');
            }

        }

        if (strlen($os) > 0) {

            $sql = "SELECT *
                    FROM   tbl_servico_realizado
                    WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

            if ($regarca_gas == 1) {
                $sql .= "AND tbl_servico_realizado.servico_realizado = 692";
            } else {
                $sql .= "AND tbl_servico_realizado.servico_realizado <> 692";
                $sql .= "AND tbl_servico_realizado.ativo IS TRUE ";
            }

        } else {

            $sql = "SELECT *
                    FROM   tbl_servico_realizado
                    WHERE  tbl_servico_realizado.fabrica = $login_fabrica
                    AND   (tbl_servico_realizado.ativo IS TRUE OR (tbl_servico_realizado.servico_realizado=643 or tbl_servico_realizado.servico_realizado=644) )";

            if ($login_pede_peca_garantia == 't') {
                $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
            }

            if ($bloqueada_garantia != 't') {
                $sql .= " AND tbl_servico_realizado.descricao NOT ILIKE '%pedido Faturado%'";
                $sql .= ($login_fabrica <> 3) ? "AND tbl_servico_realizado.descricao NOT ILIKE '%peça do Estoque%'" : "" ;
                $bloqueada_garantia = "f";
            }
        }

        $sql .= " ORDER BY descricao ";

        $res = pg_query($con,$sql) ;
        echo $bloqueada_garantia."||";

        for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
            $serv = pg_fetch_result($res,$i,'servico_realizado');
            $desc = pg_fetch_result($res,$i,'descricao');
            if ($login_fabrica == 3 and $controla_estoque == 'f' and $serv == 43){ continue; } //HD 171607 - Pular o loop quando acha o serviço de troca de estoque e o posto não está com o controla_estoque true.
            echo "<option value='$serv'>";
            echo $desc;
            echo "</option>";
        }

    } else {

        echo "<option value=''>Selecione a peça</option>";

    }

    exit;

}

 /**
  * @author William Castro <william.castro@telecontrol.com.br>
  * hd-6639553 -> Box Uploader
  *
  */  

if ($fabricaFileUploadOS) {
    
    if (!empty($os)) {
        $tempUniqueId = $os;
        $anexoNoHash = null;
    } else if (strlen($_POST['anexo_chave']) > 0) {
        $tempUniqueId = $_POST['anexo_chave'];
        $anexoNoHash = true;
    } else {
        if ($areaAdmin === true) {
            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
        } else {
            $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
        }

        $anexoNoHash = true;
    }
}


if(in_array($login_fabrica, array(101))){

    $os = $_REQUEST["os"];
    $reabrir = $_REQUEST["reabrir"];

    if (strlen($reabrir) > 0) {

        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect("select * from tbl_os join tbl_os_produto using(os) join tbl_os_item using(os_produto) where os = {$os}");

        $sql = "UPDATE tbl_os SET data_fechamento = null, cancelada = null, finalizada = null
                WHERE  tbl_os.os      = $os
                AND    tbl_os.fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);
        $msg_erro .= pg_last_error($con);
        if (!pg_last_error($con) > 0) {
            $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_os", $login_fabrica."*".$os);
        }
    }

}

if ($login_fabrica == 42) {
    $os      = $_REQUEST["os"];
    $xx_sql = "SELECT cancelada FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
    $xx_res = pg_query($con, $xx_sql);
    $xx_cat = pg_fetch_result($xx_res, 0, 'cancelada');
    if ($xx_cat) {
        header("Location: os_press.php?os=$os");
    }
}

if ($login_fabrica == 5) {
	$os = $_GET['os'];
	header("Location: os_item_new_mondial.php?os=$os&reabrir=$reabrir");
	exit;
}

include_once 'funcoes.php';

/*  MLG 26/10/2010 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
	Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
	Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
	Para mostrar a imagem: echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb'></a>
	Para saber se tem anexo: temNF($os, 'bool');
*/
include_once('../anexaNF_inc.php');

$linhas_itens = 0;

if ($S3_sdk_OK) {
	include_once S3CLASS; // é independente do anexaNF!!
	if ($S3_online)
		$s3ve = new anexaS3('ve', (int) $login_fabrica);
}

if ( in_array($login_fabrica, array(3,11,125,126,137,172)) ) {
    # A class AmazonTC está no arquivo assist/class/aws/anexaS3.class.php
    $amazonTC = new AmazonTC("os", $login_fabrica);
}

if($_GET['ajax_causa_defeito']){

    $defeitos = $_GET['defeitos'];
    $defeitos = str_replace('\\', '', $defeitos);
    $defeitos = json_decode($defeitos,true);

    foreach($defeitos as $value){
        if (!empty($value)) {
            $auxDefeitos[] = "'".$value."'";
        }
    }

    $defeitos = implode(',', $auxDefeitos);
// echo $defeitos;
    $sql = "
        SELECT  DISTINCT
                tbl_causa_defeito.causa_defeito,
                tbl_causa_defeito.codigo,
                tbl_causa_defeito.descricao
        FROM    tbl_defeito
        JOIN    tbl_defeito_causa_defeito   USING(defeito)
        JOIN    tbl_causa_defeito           USING(causa_defeito)
        WHERE   codigo_defeito              IN($defeitos)
        AND     tbl_causa_defeito.fabrica   = $login_fabrica;";

    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){
        while ($causas = pg_fetch_object($res)) {
            $causa_defeito  = $causas->causa_defeito;
            $codigo         = $causas->codigo;
            $descricao      = utf8_encode($causas->descricao);
            $json[] = array(
                "causa_defeito" => $causa_defeito,
                "codigo"        => $codigo,
                "descricao"     => $descricao
            );
        }
        $causasJson = json_encode($json);
    }else{
        $causasJson = json_encode(array("erro" => "Nenhuma Causa relacionada aos defeitos"));
    }

    echo $causasJson;
    exit;

}

if ($_POST['ajax_carrega_falha_potencial']) {//fputti hd-3130038

    $defeito = $_POST['defeito'];

    $sql = "SELECT
                    tbl_servico.descricao AS nome_falha,
                    tbl_defeito_constatado.descricao AS nome_defeito,
                    tbl_diagnostico.diagnostico,
                    tbl_diagnostico.defeito_constatado,
                    tbl_diagnostico.servico
            FROM    tbl_diagnostico
            JOIN tbl_servico ON tbl_servico.servico=tbl_diagnostico.servico
            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado=tbl_diagnostico.defeito_constatado
            WHERE tbl_diagnostico.fabrica = $login_fabrica
            AND  tbl_diagnostico.defeito_constatado = $defeito
            ORDER BY tbl_diagnostico.diagnostico DESC;";

    $res   = pg_query($con,$sql);
    $qtd   = pg_num_rows($res);

    if ($qtd > 0) {
        $retorno       .= "<option value=''> Selecione ...</option>";
        for ($i=0; $i < $qtd; $i++) {
            $servico    = pg_fetch_result($res, $i, servico);
            $nome_falha = pg_fetch_result($res, $i, nome_falha);
            $retorno   .= "<option value='".$servico."'>".utf8_encode(trim($nome_falha))."</option>";
        }
    } else {
        $retorno = "<option value=''> Nenhuma Falha em Potencial encontrada.</option>";
    }
    echo $retorno;
    exit();
}


### HD-2181938 - INICIO ###

if($login_fabrica == 131){
    $os = $_GET["os"];

    $select = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
    $result = pg_query($con, $select);

    if(pg_num_rows($result) > 0){
        $sql = "SELECT auditoria.os
            INTO TEMP tmp_auditoria_pedido_peca_produto
            FROM (
              SELECT ultimo_status.os, (
                  SELECT status_os
                    FROM tbl_os_status
                   WHERE tbl_os_status.os             = ultimo_status.os
                     AND tbl_os_status.fabrica_status = $login_fabrica
                     AND status_os IN (203,204,205)
               ORDER BY os_status DESC LIMIT 1) AS ultima_os_status
                FROM (
                  SELECT DISTINCT os
                    FROM tbl_os_status
                   WHERE tbl_os_status.fabrica_status = $login_fabrica
                     AND status_os IN (203,204,205)
                ) ultimo_status) auditoria
           WHERE auditoria.ultima_os_status IN (205);";
        $res = pg_query($con, $sql);

        $sqlOs = "SELECT
              tbl_os.os AS os_interv
            FROM tbl_os
            WHERE tbl_os.fabrica = $login_fabrica
            AND tbl_os.os IN ( SELECT os FROM tmp_auditoria_pedido_peca_produto );";
        $resOs = pg_query($con, $sqlOs);

        if(pg_num_rows($resOs) > 0){

            $cont = pg_num_rows($resOs);

            for ($i=0; $i < $cont ; $i++) {
                $osInterv = pg_fetch_result($resOs, $i, 'os_interv');
                if($osInterv === $os){
                    header("Location: os_press.php?os=$os");
                    exit;
                }
            }
        }
    }
}
### HD-2181938 - FIM ###


//  Exclui a imagem da NF
if ($_POST['ajax'] == 'excluir_nf') {
	$img_nf = anti_injection($_POST['excluir_nf']);
	//$img_nf = basename($img_nf);

	$excluiu = (excluirNF($img_nf));
	$nome_anexo = preg_replace("/.*\/([rexs]_)?(\d+)([_-]\d)?\..*/", "$1$2", $img_nf);

	if ($excluiu)  $ret = "ok|" . temNF($nome_anexo, 'linkEx') . "|$img_nf|$nome_anexo";
	if (!$excluiu) $ret = 'ko|Não foi possível excluir o arquivo solicitado.';

	exit($ret);
}//	FIM	Excluir	imagem


if($_POST['ajax'] == "verifica_recompra"){

    $posto_id       = $_POST['posto_id'];
    $referencia     = $_POST['ajax_peca'];

	if(!empty($posto_id)) {
		$sql = "SELECT controla_estoque, posto, fabrica
			FROM tbl_posto_fabrica
			WHERE posto = $posto_id
			AND fabrica = $login_fabrica";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)>0){
			$controla_estoque = pg_fetch_result($res, 0, controla_estoque);
		}

		if($controla_estoque == 't'){
			$sql_estoque = "SELECT tbl_estoque_posto.qtde, tbl_estoque_posto.peca
				FROM tbl_estoque_posto
				INNER JOIN tbl_peca ON tbl_peca.peca = tbl_estoque_posto.peca
				AND tbl_peca.fabrica = $login_fabrica
				WHERE tbl_estoque_posto.posto = $posto_id
				AND tbl_estoque_posto.fabrica = $login_fabrica
				AND tbl_peca.referencia = '$referencia'; ";
			$res_estoque = pg_query($con, $sql_estoque);
			if(pg_num_rows($res_estoque)>0){
				$qtde_estoque = pg_fetch_result($res_estoque, 0, 'qtde');
			}

			if($qtde_estoque > 0 ){
				$sql_servico = "SELECT servico_realizado, descricao from tbl_servico_realizado where fabrica = $login_fabrica and gera_pedido = 'f' and ativo = 't'";
				$res_servico = pg_query($con, $sql_servico);
				$campos = "<option value=''>SELECIONE</option>";
				for($i=0; $i<pg_num_rows($res_servico); $i++){
					$servico_realizado  = pg_fetch_result($res_servico, $i, 'servico_realizado');
					$descricao          = pg_fetch_result($res_servico, $i, 'descricao');

					$campos .= "<option value='$servico_realizado'>$descricao</option>";

				}

				echo $campos;
			}
		}
	}
    exit;
}

if($login_fabrica == 19){ //hd_chamado=2881143
    if(isset($_GET['defeito_constatado_pecas'])) {
        $defeito_pecas    = $_GET['defeito_constatado_pecas'];
        $pecas_lancadas_os  = $_GET['pecas_lancadas'];

        $pecas_lancadas_os = explode(',', $pecas_lancadas_os);

        $deletar = array();
        foreach ($pecas_lancadas_os as $key => $value) {
            $sql_valida_pecas = "SELECT tbl_peca.peca,
                        tbl_peca.referencia
                    FROM tbl_peca
                    JOIN tbl_peca_familia ON tbl_peca_familia.peca = tbl_peca.peca AND tbl_peca_familia.fabrica = $login_fabrica
                    JOIN tbl_defeito_constatado_familia_peca ON tbl_defeito_constatado_familia_peca.familia_peca = tbl_peca_familia.familia_peca
                        AND tbl_defeito_constatado_familia_peca.fabrica = $login_fabrica
                    WHERE tbl_peca.referencia = '$value'
                    AND tbl_defeito_constatado_familia_peca.defeito_constatado IN ($defeito_pecas)";
            $res_valida_pecas = pg_query($con, $sql_valida_pecas);

            if(pg_num_rows($res_valida_pecas) == 0){
                $deletar[] = $value;
            }
        }
        exit(json_encode($deletar));
    }
}

/**
* AJAX para carregar select de
* fornecedores por peça
* @author William Ap. Brandino
*/
if ($_POST['ajax'] == "fornecedor_peca") {
    $ajax_peca      = $_POST['ajax_peca'];
    $ajax_kit_peca  = $_POST['ajax_kit_peca'];


   /**
    * - Entra nesse IF somente se
    * na requisição do AJAX, vier um KIT
    *
    * @param Integer $ajax_kit_peca
    */
    if (!empty($ajax_kit_peca)) {
        $sqlKit = " SELECT tbl_kit_peca_peca.peca
                    FROM    tbl_kit_peca_peca
                    JOIN    tbl_kit_peca USING (kit_peca)
                    WHERE   tbl_kit_peca.kit_peca = $ajax_kit_peca
        ";
        $resKit = pg_query($con,$sqlKit);
        $contaKit = pg_num_rows($resKit);

        if($contaKit == 1){
           /**
            * - Caso a busca pelo kit traga
            * somente UMA peça
            * Retorna a busca de fornecedores da peça
            */
            $ajax_peca = pg_fetch_result($resKit,0,peca);

            $sqlAjax = "
                SELECT  tbl_fornecedor_peca.fornecedor  AS retorno_fornecedor_peca      ,
                        tbl_fornecedor.nome             AS retorno_fornecedor_peca_nome
                FROM    tbl_fornecedor_peca
                JOIN    tbl_fornecedor  ON  tbl_fornecedor.fornecedor   = tbl_fornecedor_peca.fornecedor
                                        AND tbl_fornecedor_peca.peca    = $ajax_peca
                WHERE   tbl_fornecedor_peca.fabrica = $login_fabrica
            ";
        } else if($contaKit > 1) {
           /**
            * - Caso a busca pelo kit traga
            * MAIS DE UMA peça
            * Retorna a INTERSECÇÃO de fornecedores
            * das peças presentes no kit
            */

            $sqlAjax = "
                SELECT * FROM (
                    (
            ";

            for($c = 0; $c < $contaKit; $c++){
                $ajax_peca = pg_fetch_result($resKit,$c,peca);
                if($c > 0){
                    $sqlAjax .= "
                        ) INTERSECT (
                    ";
                }
                $sqlAjax .= "
                    SELECT  tbl_fornecedor_peca.fornecedor  AS retorno_fornecedor_peca      ,
                            tbl_fornecedor.nome             AS retorno_fornecedor_peca_nome
                    FROM    tbl_fornecedor_peca
                    JOIN    tbl_fornecedor  ON  tbl_fornecedor.fornecedor   = tbl_fornecedor_peca.fornecedor
                                            AND tbl_fornecedor_peca.peca    = $ajax_peca
                    WHERE   tbl_fornecedor_peca.fabrica = $login_fabrica
                ";
            }

            $sqlAjax .= "
                    )
                ) AS forn
            ";
        }
    }else{
       /**
        * - No ELSE, somente quando não vier
        * requisição do ajax por kit
        * @param Integer $ajax_peca
        */
        $sqlAjax = "
            SELECT  tbl_fornecedor_peca.fornecedor  AS retorno_fornecedor_peca      ,
                    tbl_fornecedor.nome             AS retorno_fornecedor_peca_nome
            FROM    tbl_fornecedor_peca
            JOIN    tbl_fornecedor  ON  tbl_fornecedor.fornecedor   = tbl_fornecedor_peca.fornecedor
                                    AND tbl_fornecedor_peca.peca    = $ajax_peca
            WHERE   tbl_fornecedor_peca.fabrica = $login_fabrica
        ";
    }
    //     echo $sqlAjax;exit;
    $resAjax = pg_query($con,$sqlAjax);

    if(pg_num_rows($resAjax) > 0){
        $retorno = pg_fetch_all($resAjax);
        foreach($retorno as $k => $v){
            $montaSelect[(int)$v['retorno_fornecedor_peca']] = utf8_encode($v['retorno_fornecedor_peca_nome']);
        }
        echo json_encode($montaSelect);
    }else{
        echo "erro";
    }
    exit;
}

/**
 * AJAX - Carrega os defeitos por peça
 */

if (filter_input(INPUT_POST,'ajax') == "defeito_peca") {
    $ajax_peca      = filter_input(INPUT_POST,'ajax_peca');

    $sqlDef = "
        SELECT  DISTINCT
                tbl_defeito.descricao,
                tbl_defeito.defeito
        FROM    tbl_defeito
        JOIN    tbl_peca_defeito    USING(defeito)
        WHERE   tbl_peca_defeito.peca   = $ajax_peca
        AND     tbl_peca_defeito.ativo  IS TRUE
    ";
//     echo $sqlDef;
    $resDef = pg_query($con,$sqlDef);

    while ($defeitos = pg_fetch_object($resDef)) {
        $montaSelect[(int)$defeitos->defeito] = utf8_encode($defeitos->descricao);
    }

    echo json_encode($montaSelect);
    exit;
}

/**
 * AJAX - Carrega os serviços por peça / defeito
 */

if (filter_input(INPUT_POST,'ajax') == "servico_peca") {
    $ajax_peca      = filter_input(INPUT_POST,'ajax_peca');
    $ajax_defeito   = filter_input(INPUT_POST,'ajax_defeito');

    $sqlServ = "
        SELECT  tbl_servico_realizado.descricao,
                tbl_servico_realizado.servico_realizado
        FROM    tbl_servico_realizado
        JOIN    tbl_peca_defeito USING(servico_realizado)
        JOIN    tbl_peca USING(peca)
        WHERE   defeito             = $ajax_defeito
        AND     tbl_peca.referencia = '$ajax_peca'
        AND     tbl_peca.fabrica    = $login_fabrica
    ";
    $resServ = pg_query($con,$sqlServ);

    while ($servicos = pg_fetch_object($resServ)) {
        $montaSelect[(int)$servicos->servico_realizado] = utf8_encode($servicos->descricao);
    }

    echo json_encode($montaSelect);
    exit;
}

#HD 670814 - INICIO

$fabricas_padrao_itens = array(94,98,99,108,111);

#HD 670814 - FIM

// Unificando 3 SELECTs num só... MLG - 20/08/2012
// Fabricas que tem o campo defeito reclamado como texto livre
$sql = "SELECT pedir_defeito_reclamado_descricao,
               defeito_constatado_por_familia,
			   defeito_constatado_por_linha,
               pergunta_qtde_os_item
          FROM tbl_fabrica
         WHERE fabrica = $login_fabrica";

$res = pg_query ($con,$sql);
extract(pg_fetch_assoc($res,0));


if (strlen($_GET['os']) > 0)  $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen($_GET['os_item']) > 0) $item_os = trim($_GET['os_item']);
if (strlen($_GET['liberar']) > 0) $liberar = $_GET['liberar'];

if (strlen($_GET['imprimir']) > 0) $imprimir = $_GET['imprimir'];

/* Recuperando o id do Posto da OS - # HD 925803 */
$sql = "SELECT
            tbl_os.posto
		FROM
		     tbl_os
		WHERE  tbl_os.os       = $os
		    AND tbl_os.fabrica = $login_fabrica;";
$res = pg_query($con, $sql);
$posto = pg_fetch_result($res, 0, 0);

#HD 418875 - Alert quando o peça estiver com peça critica true
#acesso somente via AJAX
$referencia_peca_critica = $_POST['referencia_peca_critica'];
if(strlen($referencia_peca_critica) > 0){
	$sql = "
		SELECT
			peca
		FROM
			tbl_peca
		WHERE
			fabrica = $login_fabrica
			AND referencia = '$referencia_peca_critica'
			AND peca_critica IS NOT NULL;";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res)>0)
		echo 1;

	exit;
}

$troca_faturada = trim($_POST['troca_faturada']);

if (strlen($item_os) > 0) {
	$sql = "SELECT *
			FROM   tbl_os_item
			WHERE  tbl_os_item.os_item = $item_os;";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$os_pedido  = trim(pg_fetch_result($res,0,pedido));

		$sql      = "SELECT fn_exclui_item_os($os_pedido, $item_os, $login_fabrica)";
		$res      = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			header ("Location: $PHP_SELF?os=$os");
			exit;
		}
	}
}

if($login_fabrica == 74 && isset($_POST['limpa_movimento_estoque']) && isset($_POST['servico'])){

    $os         = $_POST['os'];
    $servico    = $_POST['servico'];
    $qtde       = $_POST['qtde'];
    $referencia = $_POST['referencia'];

    if (strlen($servico)>0){
	    $sql_tipo_estoque = "SELECT ressarcimento, troca_de_peca,
				FROM tbl_servico_realizado
				WHERE fabrica = $login_fabrica AND servico_realizado = $servico";
	    // echo nl2br($sql_tipo_estoque); exit;
	    $res_tipo_estoque = pg_query($con, $sql_tipo_estoque);

	    if(pg_num_rows($res_tipo_estoque) > 0){
        	$ressarcimento = pg_fetch_result($res_tipo_estoque, 0, 'ressarcimento');
            $troca_de_peca = pg_fetch_result($res_tipo_estoque, 0, 'troca_de_peca');
	        $tipo_estoque = ($ressarcimento == 't') ? "estoque" : "pulmao";
	    }
    }

    $sql_qtde_saida = "SELECT qtde_saida, peca FROM tbl_estoque_posto_movimento WHERE os = $os AND fabrica = $login_fabrica AND posto = $login_posto";
    // echo nl2br($sql_qtde_saida); exit;
    $res_qtde_saida = pg_query($con, $sql_qtde_saida);

    if(pg_num_rows($res_qtde_saida) > 0){

        $qtde_saida = pg_fetch_result($res_qtde_saida, 0, 'qtde_saida');
        $peca       = pg_fetch_result($res_qtde_saida, 0, 'peca');

        $sql_servico_update = "UPDATE tbl_estoque_posto
                               SET qtde = qtde + $qtde_saida
                               WHERE
                               fabrica = $login_fabrica
                               AND posto = $login_posto
                               AND tipo = '$tipo_estoque'
                               AND peca = $peca";
        // echo nl2br($sql_servico_update); exit;
        $res_servico_update = pg_query($con, $sql_servico_update);

        $sql_delete_movimentacao = "DELETE FROM tbl_estoque_posto_movimento WHERE os = $os AND fabrica = $login_fabrica AND posto = $login_posto AND tipo = '$tipo_estoque'";
        // echo nl2br($sql_delete_movimentacao); exit;
        $res_delete_movimentacao = pg_query($con, $sql_delete_movimentacao);

    }

    exit;

}

if(in_array($login_fabrica, array(35,74)) && isset($_POST['limpa_movimento_estoque2'])){

    $os         = $_POST['os'];
    $servico    = $_POST['servico'];
    $qtde       = $_POST['qtde'];
    $referencia = $_POST['referencia'];

    /*
        Verifica se o campo ressarcimento do servico realizao é true
        se for true estoque antigo se não estoque pulmão
    */
    if (strlen($servico)>0){
	    $sql_tipo_estoque = "SELECT ressarcimento, troca_de_peca
        	                FROM tbl_servico_realizado
                	        WHERE fabrica = $login_fabrica AND servico_realizado = $servico";
	    $res_tipo_estoque = pg_query($con, $sql_tipo_estoque);

	    if(pg_num_rows($res_tipo_estoque) > 0){
        	$ressarcimento = pg_fetch_result($res_tipo_estoque, 0, 'ressarcimento');
            $troca_de_peca = pg_fetch_result($res_tipo_estoque, 0, 'troca_de_peca');
	        $tipo_estoque = ($ressarcimento == 't') ? "estoque" : "pulmao";
    	}
    }

    // Pega o id da peça
    $sql_peca = "SELECT peca FROM tbl_peca WHERE referencia = '$referencia'";
    $res_peca = pg_query($con, $sql_peca);

    if (pg_num_rows($res_peca) > 0) {
        $peca = pg_fetch_result($res_peca, 0, 'peca');

        if($tipo_estoque == "pulmao"){
            $sql_vm = "SELECT fabrica
                       FROM tbl_estoque_posto_movimento
                       WHERE os = $os
                       AND fabrica = $login_fabrica
                       AND posto = $posto
                       AND peca = $peca
                       AND tipo = 'estoque'";
            $res_vm = pg_query($con, $sql_vm);

            if(pg_num_rows($res_vm) > 0){
                $sql_update = "UPDATE tbl_estoque_posto
                               SET qtde = qtde + $qtde
                               WHERE fabrica = $login_fabrica
                               AND posto = $posto
                               AND tipo = 'estoque'
                               AND peca = $peca";
                $res_update = pg_query($con, $sql_update);

                $sql_dm = "DELETE FROM tbl_estoque_posto_movimento
                            WHERE os = $os
                            AND fabrica = $login_fabrica
                            AND posto = $posto
                            AND peca = $peca
                            AND tipo = 'estoque'";
                $res_dm = pg_query($con, $sql_dm);
            }
        }else{
            $sql_vm = "SELECT fabrica
                       FROM tbl_estoque_posto_movimento
                       WHERE os = $os
                       AND fabrica = $login_fabrica
                       AND posto = $posto
                       AND peca = $peca
                       AND tipo = 'pulmao'";
            $res_vm = pg_query($con, $sql_vm);

            if(pg_num_rows($res_vm) > 0){
                $sql_update = "UPDATE tbl_estoque_posto
                               SET qtde = qtde + $qtde
                               WHERE fabrica = $login_fabrica
                               AND posto = $posto
                               AND tipo = 'pulmao'
                               AND peca = $peca";
                $res_update = pg_query($con, $sql_update);

                $sql_dm = "DELETE FROM tbl_estoque_posto_movimento
                            WHERE os = $os
                            AND fabrica = $login_fabrica
                            AND posto = $posto
                            AND peca = $peca
                            AND tipo = 'pulmao'";
                $res_dm = pg_query($con, $sql_dm);
            }
        }
    }
    exit;

}

if ($login_fabrica == 7) {
	header("Location: os_filizola_valores.php?os=$os&imprimir=$imprimir");
	exit;
}

if (strlen($os) > 0 AND strlen($defeito_reclamado) > 0 AND $login_fabrica == 19) {
    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect("select * from tbl_os join tbl_os_produto using(os) join tbl_os_item using(os_produto) where os = {$os}");

	$sql = "UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_query ($con,$sql) ;

    if (!pg_last_error($con) > 0) {
        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_os", $login_fabrica."*".$os);
    }
}

# HD 925803
$nome_login = ucfirst($login_login); // Recuperando nome do usuário logado

/**
 * @author Fabiano Souza <fabiano.souza@telecontrol.com.br>
 * @abstract Alterações contidas na HD 925803
 * @since 16/08/2012 10:20
 */
function log_os_admin($nome_login, $desc_acao, $os, $login_fabrica){
	global $con, $posto, $login_admin; // variáveis de uso externo à função

	$res = pg_query($con, "BEGIN TRANSACTION");

	$separador = '';
	$count     = count($desc_acao);
	$obs       = '<b>'.$nome_login.'</b> -- ';

	foreach( $desc_acao as $chave => $value ){
		if($count > 1) {
			$separador = ' <b>|</b> ';
		}
		$valor .= $value['desc_acao'].$separador;
	}

	$obs .= $valor.'<br><hr style="height:2px; background-color: #485989; border:0px; padding:0px;" />';

	/* Verificando se já tem interaçoes de LOG gravadas */
	$sql0 = "SELECT
	            os_interacao
			FROM
			    tbl_os_interacao
			WHERE tbl_os_interacao.os        = $os
			    AND tbl_os_interacao.posto   = $posto
			    AND tbl_os_interacao.fabrica = $login_fabrica;";

	$res0 = pg_query($con, $sql0);

	if( pg_num_rows($res0) >= 1 ){

		$os_interacao = pg_fetch_result($res0, 0, os_interacao);

		/* Atualizando o campo "comentario", seja a 1ª vez que estiver vazio ou concatenando seu conteúdo com o que já tem gravado */
		$sql = "UPDATE
		            tbl_os_interacao
				SET
					comentario = CASE WHEN comentario IS NULL
									 THEN E'$obs'
								 ELSE
									 comentario || E'$obs'
								 END
				WHERE tbl_os_interacao.os        	  = $os
				    AND tbl_os_interacao.posto   	  = $posto
				    AND tbl_os_interacao.fabrica 	  = $login_fabrica
					AND tbl_os_interacao.os_interacao = $os_interacao;";
	}else{
		/* Inserindo no campo "comentario", o seu conteúdo do 1º LOG */
		$sql = "INSERT INTO tbl_os_interacao
		        (
                    programa,
					os,
					admin,
					comentario,
					fabrica,
					posto,
					interno
				)
				VALUES
				(
                    '$programa_insert',
					$os,
					$login_admin,
					E'$obs',
					$login_fabrica,
					$posto,
					't'
				);";
	}

	$res = pg_query($con, $sql);

	if( pg_affected_rows($res) >= 1 ){

		$res = pg_query($con, "COMMIT TRANSACTION");

		/* RecuperanHEADdo o nome da Fábrica e qual comentário */
		$sql = "SELECT
		            tbl_os_interacao.comentario AS comentario_log,
					tbl_fabrica.nome AS nome_fabrica
		        FROM
				    tbl_os_interacao
				JOIN tbl_fabrica
				    ON(
						tbl_fabrica.fabrica = tbl_os_interacao.fabrica
					)
				WHERE tbl_os_interacao.os        = $os
				    AND tbl_os_interacao.posto   = $posto
					AND tbl_os_interacao.fabrica = $login_fabrica;";

		$res = pg_query($con, $sql);

		if( pg_num_rows($res) > 0 ){
			$nome_fabrica   = pg_fetch_result($res, 0, nome_fabrica);
			$comentario_log = pg_fetch_result($res, 0, comentario_log);
		}

		/* Envio de e-mail do LOG */
		require_once dirname(__FILE__).'../../class/email/mailer/class.phpmailer.php';

		$mail = new PHPMailer();
		$mail->IsHTML(true);
		$mail->From 	= 'suporte@telecontrol.com.br';
		$mail->FromName = 'Telecontrol';
		$mail->AddAddress('apoio.sac@fricon.com.br'); /* E-mail da empresa */

		$mail->Subject 	= "OS $os - LOG de Alterações de Usuários";
		$mail->Body 	= "A/C de ".ucfirst($nome_fabrica).", segue abaixo o LOG da OS $os:<br /><br />".$comentario_log;

		if( !$mail->Send() ){
			echo 'Erro ao enviar o e-mail: ', $mail->ErrorInfo;
			return 0;
		}else{
			return 1;
		}

	}else{
		$res = pg_query($con, "ROLLBACK TRANSACTION");
		return 0;
	}
}
# HD 925803 - fim



if (strlen($liberar) > 0) {
    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect("select * from tbl_os join tbl_os_produto using(os) join tbl_os_item using(os_produto) where os = {$os}");

	$sql = "UPDATE tbl_os_item SET
				liberacao_pedido      = 't'              ,
				data_liberacao_pedido = CURRENT_TIMESTAMP,
				admin                 = $login_admin     ,
				obs                   = '### PEÇA INFERIOR A 30% DO VALOR DE MÃO-DE-OBRA. LIBERADA PELO ADMIN. ###'
			FROM  tbl_os, tbl_os_produto
			where tbl_os_item.os_produto = tbl_os_produto.os_produto
			and   tbl_os_produto.os      = tbl_os.os
			and   tbl_os.os              = $os
			and   tbl_os.fabrica         = $login_fabrica
			and   tbl_os_item.os_item    = $liberar
			and   tbl_os_item.admin      IS NULL;";

	$res = pg_query ($con,$sql);
    if (!pg_last_error($con) > 0) {
        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_os", $login_fabrica."*".$os);
    }

	header ("Location: $PHP_SELF?os=$os");
	exit;
}

if (strlen($os) > 0) {

	$sql = "SELECT fabrica, posto, tipo_os FROM tbl_os WHERE tbl_os.os = $os";
	$res = @pg_query($con,$sql);

	$tipo_os     = pg_fetch_result($res, 0, 'tipo_os');
	$login_posto = pg_fetch_result($res, 0, 'posto');

	if (pg_fetch_result($res, 0, 'fabrica') <> $login_fabrica ) {
		header ("Location: os_cadastro.php");
		exit;
	}

}


if ($login_fabrica == 1) {
	$sql =	"SELECT tipo_os_cortesia, tipo_os, os_numero, tipo_atendimento
			FROM  tbl_os
			WHERE fabrica = $login_fabrica
			AND   os = $os;";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 1) {
        $tipo_os_cortesia   = pg_fetch_result($res,0,tipo_os_cortesia);
        $tipo_os            = pg_fetch_result($res,0,tipo_os);
        $os_numero          = pg_fetch_result($res,0,os_numero);
        $tipo_atendimento   = pg_fetch_result($res,0,tipo_atendimento);

		if ($tipo_os_cortesia == "Compressor" OR $tipo_os==10) {
			$compressor='t';
		}
		/*PARA OS GEO, USA A OS REVENDA NA GRAVAÇÃO DE OS VISITA*/
		if($tipo_os == 13){
			$sql_aux_os = " os_revenda ";
			$aux_os = $os_numero;
		}else{
			$sql_aux_os = " os ";
			$aux_os = $os;
		}
	}
	#HD 11906
	$sql = "SELECT os FROM tbl_os_troca WHERE os=$os";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {
		header ("Location: os_press.php?os=$os");
		exit;
	}
	$qtde_visita=4;
}

/*IGOR HD: 44202 - 16/10/2008*/
if ($login_fabrica == 3) {
	$xos = $_GET['os'];
	if (strlen($xos) == 0) {
		$xos = $_POST['os'];
	}
	if (strlen($xos) > 0) {
		$status_os = "";
		$sql = "SELECT status_os
				FROM  tbl_os_status
				WHERE os=$xos
				AND status_os IN (120, 122, 123, 126, 140, 141, 142, 143)
				ORDER BY data DESC LIMIT 1";
		$res_intervencao = pg_query($con, $sql);
		$msg_erro        = pg_errormessage($con);

		if (pg_num_rows ($res_intervencao) > 0 ){
			$status_os = pg_fetch_result($res_intervencao,0,status_os);
			#if ($status_os=="120" OR $status_os=="122" OR $status_os=="126"){ HD 56464
			if ($status_os == "122" || $status_os == "141") {
				header ("Location: os_press.php?os=$xos");
				exit;
			}
		}
	}
}

$sql_controla_estoque = "SELECT controla_estoque FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto;";
$res_controla_estoque = pg_query($con,$sql_controla_estoque);
$controla_estoque     = pg_fetch_result($res_controla_estoque,0,'controla_estoque');

$sql_ta = "SELECT descricao FROM tbL_tipo_atendimento WHERE tipo_atendimento = (SELECT tipo_atendimento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) AND fabrica = $login_fabrica";
$res_ta = pg_query($con, $sql_ta);

if(pg_num_rows($res_ta) > 0){
    $desc_tipo_atendimento = pg_fetch_result($res_ta, 0, 'descricao');
}

$btn_acao = strtolower($_POST['btn_acao']);

if ($btn_acao == "gravar") {   

    $sql_distrib = "SELECT fabrica FROM tbl_fabrica WHERE parametros_adicionais ilike '%telecontrol_distrib%' and fabrica = $login_fabrica";
    $res_distrib = pg_query($con, $sql_distrib);
    $telecontrol_distrib = false;
    $pecas_os_item = array();

    if (pg_num_rows($res_distrib) > 0) {
        $telecontrol_distrib = true;

        $sql_peca_os_item = "SELECT peca, qtde FROM tbl_os_item JOIN tbl_os_produto USING(os_produto) where os = $os";
        $res_peca_os_item = pg_query($con, $sql_peca_os_item);

        while ($fetch = pg_fetch_assoc($res_peca_os_item)) {
            $pecas_os_item[$fetch['peca']] = (int) $fetch['qtde'];
        }
    }

    $tecnico                = $_POST["tecnico"];
    $qtde_horas             = intval($_POST["qtde_horas"]);
    $qtde_km                = intval($_POST["qtde_km"]);
    $defeito_constatado     = $_POST["defeito_constatado"];
    $falha_em_potencial     = $_POST["falha_em_potencial"];
    $ajusterealizado        = $_POST["ajusterealizado"];
    $causa_defeito          = $_POST["causa_defeito"];

	if ($anexaNotaFiscal and !$msg_erro) {
		if (is_array($_FILES['foto_nf'])) {

            $qt_anexo = 0;
            foreach($_FILES['foto_nf'] as $files){
              if(strlen($_FILES['foto_nf']['name'][$qt_anexo])==0){
                continue;
              }
              $dados_anexo['name']      = $_FILES['foto_nf']['name'][$qt_anexo];
              $dados_anexo['type']      = $_FILES['foto_nf']['type'][$qt_anexo];
              $dados_anexo['tmp_name']  = $_FILES['foto_nf']['tmp_name'][$qt_anexo];
              $dados_anexo['error']     = $_FILES['foto_nf']['error'][$qt_anexo];
              $dados_anexo['size']      = $_FILES['foto_nf']['size'][$qt_anexo];

              $anexou = anexaNF($os, $dados_anexo);

              if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK

              $qt_anexo++;
            }

		}
	}//  FIM Anexa imagem NF

    $audProd    = new AuditorLog();
    $audItem    = new AuditorLog();
    $audOs      = new AuditorLog();

    $query_action = "INSERT";

    $audOs->retornaDadosSelect("SELECT os,
            defeito_reclamado,
            defeito_constatado,
            solucao_os as solucao,
            obs
            from tbl_os
            where os = {$os}");

    $audProd->retornaDadosSelect("SELECT DISTINCT  tbl_os_produto.os_produto,
                                                tbl_os.os,
                                                tbl_os.causa_defeito,
                                                tbl_os_produto.produto,
                                                tbl_os.serie
                                        FROM    tbl_os_produto
                                        JOIN    tbl_os USING(os)
                                        WHERE   tbl_os.os = {$os}");


    $audItem->retornaDadosSelect("SELECT tbl_os_item.os_item, tbl_os_item.peca,
                                            tbl_os_item.qtde,
                                            tbl_os_item.servico_realizado,
                                            tbl_os_item.defeito,
                                            tbl_os_item.parametros_adicionais,
                                            tbl_os_item.admin,
                                            tbl_os_item.peca_obrigatoria as devolucao_obrigatoria,
                                            tbl_os_item.fornecedor
                                    FROM    tbl_os_item
                                    JOIN    tbl_os_produto  USING(os_produto)
                                    JOIN    tbl_os          USING(OS)
                                    WHERE   tbl_os.os = $os");


    //valida campos para jacto
    if($login_fabrica == 87){
        if($tecnico == 0){
            $msg_erro = "Selecione um técnico";
        }

        if($defeito_constatado == 0){
            $msg_erro = "Selecione um ".$tema;
        }

        /*
        if($qtde_horas == 0){
            $msg_erro = "Informe a quantidade de horas";
        }

        if($qtde_km == 0){
            $msg_erro = "Informe a distância de KM";
        }
        */
    }

	$data_fechamento = $_POST['data_fechamento'];
	if (strlen($data_fechamento) > 0) {
		$xdata_fechamento = fnc_formata_data_pg($data_fechamento);
		if ($xdata_fechamento > "'".date("Y-m-d")."'") $msg_erro .= "Data de fechamento maior que a data de hoje.";
	}


    //HD 697198
	if (empty($msg_erro) and ($login_fabrica == 90) ){
		$msg_erro .= ( empty($_POST['defeito_constatado']) ) 	? "Informe o $tema <br />" 	: null;
		if (!in_array($login_fabrica,array(104,105,120,201,122))){
			$msg_erro .= ( empty($_POST['solucao_os'])  ) 			? "Informe a solução <br />" 				: null;
		}
	}
	if (( strlen($causa_defeito)== 0 OR $causa_defeito == 'Selecione uma opção')AND ($login_fabrica == 131) ){
			$msg_erro .= "Informe a causa do $tema <br />";
		}
	if($login_fabrica == 94){
		$msg_erro .= ( empty($_POST['defeito_constatado_codigo']) ) 	? "Informe o $tema <br />" 	: null;
	}

    if(in_array($login_fabrica, array(101))){
        if(strlen($obs) == 0){
            $msg_erro .= "Por favor preencher o campo Descrição detalhada do problema <br />";
        }
    }

	if (empty($msg_erro)){
        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect("select * from tbl_os join tbl_os_produto using(os) join tbl_os_item using(os_produto) where os = {$os}");

		$res = pg_query($con,"BEGIN TRANSACTION");
		//hd17966
		if ($login_fabrica == 45) {
			$sql = "SELECT finalizada,data_fechamento
					FROM   tbl_os
					JOIN   tbl_os_extra USING(os)
					WHERE  fabrica = $login_fabrica
					AND    os      = $os
					AND    extrato         IS     NULL
					AND    finalizada      IS NOT NULL
					AND    data_fechamento IS NOT NULL";
			$res = pg_query ($con,$sql);
			if(pg_num_rows($res)>0){
				$voltar_finalizada = pg_fetch_result($res,0,0);
				$voltar_fechamento = pg_fetch_result($res,0,1);
				$sql = "UPDATE tbl_os SET data_fechamento = NULL , finalizada = NULL
						WHERE os      = $os
						AND   fabrica = $login_fabrica";
				$res = pg_query ($con,$sql);
			}
		}



		// HD-896985
		if($login_fabrica == 52){
			if(!empty($_POST['rg_tecnico'])){

			    $sqlTecnico = "UPDATE tbl_os_extra SET tecnico = '".$_POST['nome_tecnico']."|".$_POST['rg_tecnico']."' WHERE os = ".$os." ";
			    $res = pg_query($con,$sqlTecnico);

			}else{
			    $msg_erro .= "Você deve preencher os campos \"RG do técnico\"<br />";
			}


			if(!empty($_POST['nome_tecnico']) ){

			    $sqlTecnico = "UPDATE tbl_os_extra SET tecnico = '".$_POST['nome_tecnico']."|".$_POST['rg_tecnico']."' WHERE os = ".$os." ";
			    $res = pg_query($con,$sqlTecnico);

			}else{
			    $msg_erro .= "Você deve preencher os campos \"Nome do técnico\"<br />";
			}

            	}
		// HD-896985


		// HD 415550 - Inicio
		if ( isset ( $_POST ['valor_mao_de_obra'] ) ) {

			$valor = str_replace(',','.', $_POST ['valor_mao_de_obra']);
			$int_valor = (int) $valor;
			$valor = ( !empty($int_valor) ) ? $valor : 0;
			$sql = "UPDATE tbl_os
					SET mao_de_obra = " . $valor . "
					WHERE os = " . $os ;
			$res = pg_query($con,$sql);

		}
		// HD 415550 - FIM

		$sql = "SELECT tbl_os.posto
				FROM   tbl_os
				WHERE  tbl_os.os      = $os
				AND    tbl_os.fabrica = $login_fabrica;";
		$res = pg_query ($con,$sql);
		$posto = pg_fetch_result ($res,0,0);


        //hd-2795821
        $sql_posto_fabrica = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = $posto and fabrica = $login_fabrica";
        $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);
        if(strlen(trim(pg_last_error($con)))== 0 ){
            if(pg_num_rows($res_posto_fabrica)>0){
                $parametros_adicionais = pg_fetch_result($res_posto_fabrica, 0, parametros_adicionais);
                $parametros_adicionais = json_decode($parametros_adicionais, true);

                $gera_pedido = (strlen(trim($parametros_adicionais['gera_pedido']))>0 )? $parametros_adicionais['gera_pedido'] : 'f';

            }
        }else{
            $msg_erro .= pg_last_error($con);
        }

		if ($login_fabrica == 1) {
			$x_produto_type = $_POST['produto_type'];
			if (strlen ($x_produto_type) > 0) $x_produto_type = "'" . $x_produto_type . "'";
			else                              $x_produto_type = "null";

			$sql = "UPDATE tbl_os SET type = $x_produto_type
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $posto;";
			$res = @pg_query ($con,$sql);
		}

		$defeito_reclamado_descricao = trim($_POST['defeito_reclamado_descricao']);

		if(strlen($os)>0 AND strlen($defeito_reclamado_descricao)>0 and $pedir_defeito_reclamado_descricao == 't') {
			$sql = "UPDATE tbl_os SET defeito_reclamado_descricao = '$defeito_reclamado_descricao' WHERE os = $os AND fabrica = $login_fabrica AND defeito_reclamado_descricao IS NULL";
			$res = pg_query ($con,$sql) ;
			$msg_erro .= pg_errormessage($con);
		}

        $defeito_constatado = $_POST ['defeito_constatado'];

        if (strlen ($defeito_constatado) > 0  or $login_fabrica == 94) {
			//hd 17863 Rotina de vários defeitos para uma única OS.
			if(in_array($login_fabrica,array(2, 30, 59,94,131,134,144))){
				$numero_vezes = 100;
				$array_integridade = array();
				$todosDefeitos      = array();

                $defeito_cor_etiqueta_fornecedor = false; 

				for ($i=0;$i<$numero_vezes;$i++) {        
                    $pedir_cor_etiqueta = false;             

					$int_constatado = trim($_POST["integridade_defeito_constatado_$i"]);
					$int_solucao    = trim($_POST["integridade_solucao_$i"]);

                    if (!isset($_POST["integridade_defeito_constatado_$i"])) continue;

                    if($login_fabrica == 30){
                        $cor_etiqueta_fornecedor   = trim($_POST['integridade_cor_fornecedor_'.$i]);

                        $sql_def = "SELECT defeito_constatado, campos_adicionais from tbl_defeito_constatado where defeito_constatado = $int_constatado and fabrica = $login_fabrica ";
                        $res_def = pg_query($con, $sql_def);

                        if(pg_num_rows($res_def)>0){
                            $campos_adicionais_dc = json_decode( pg_fetch_result($res_def, 0, 'campos_adicionais')   ,true);
                            $pedir_cor_etiqueta = (isset($campos_adicionais_dc['pedir_cor_etiqueta'])) ? true : false; 
                        }
                    }               

					if (strlen($int_constatado)==0) {
						$msg_erro ="Favor adicionar o ".$tema;
						break;
					}

                    if(isset($_POST["integridade_cor_etiqueta_$i"])){
                        $cor_etiqueta_fornecedor = $_POST["integridade_cor_etiqueta_$i"];
                        $cores_defeitos[] = array("fornecedor" => $cor_etiqueta_fornecedor, "defeito" => $int_constatado); 
                        $defeito_cor_etiqueta_fornecedor = true; 
                    }

					$aux_defeito_constatado = $int_constatado;
					$aux_solucao            = $int_solucao;

					array_push($array_integridade,$aux_defeito_constatado);

					$sql = "SELECT defeito_constatado_reclamado
							FROM tbl_os_defeito_reclamado_constatado
							WHERE os = $os
							AND   defeito_constatado = $aux_defeito_constatado";

					$res = pg_query ($con,$sql);
				
                    $msg_erro .= pg_errormessage($con);

					if (@pg_num_rows($res)==0) {

                        if(in_array($login_fabrica,array(94,131,134,144))){

							$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
										os,
										defeito_constatado,
										fabrica
									)VALUES(
										$os,
										$aux_defeito_constatado,
										$login_fabrica
									)	";
						} else {

                            $campos_adicionais = null;

                            if($login_fabrica == 30 and strlen($cor_etiqueta_fornecedor) > 0 and  $pedir_cor_etiqueta == true){
                                $campos_adicionais['cor_etiqueta_fornecedor'] = $cor_etiqueta_fornecedor; 

                                $campos_adicionais = json_encode($campos_adicionais);                     

                                $cmp_campos_adicionais = ", campos_adicionais  "; 
                                $vl_campos_adicionais = ", '$campos_adicionais' "; 
                            }else{
                                $cmp_campos_adicionais = " "; 
                                $vl_campos_adicionais = " "; 
                            }


							$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
										os,
										defeito_constatado,
										solucao,
										fabrica
                                        $cmp_campos_adicionais
									)VALUES(
										$os,
										$aux_defeito_constatado,
										$aux_solucao,
										$login_fabrica
                                        $vl_campos_adicionais
									)";

						}
    					$res = pg_query ($con,$sql);

						$msg_erro .= pg_errormessage($con);

                        if ($login_fabrica == 30) {
                            $sqlDef = "
                                SELECT  descricao
                                FROM    tbl_defeito_constatado
                                WHERE   defeito_constatado = $aux_defeito_constatado
                            ";
                            $resDef = pg_query($con,$sqlDef);
                            array_push($todosDefeitos,pg_fetch_result($resDef,0,descricao));
                        }
					}
				}

                $cores_defeitos = json_encode($cores_defeitos);                

				if ($login_fabrica == 30 && strlen($todosDefeitos) > 0) {
                    $defeitos = implode(", ",$todosDefeitos);
                    $msg = "Defeitos cadastrados na OS: $defeitos";

                    $sql = "
                        INSERT INTO tbl_os_interacao (
                            programa,
                            os,
                            data,
                            comentario,
                            interno,
                            fabrica,
                            admin
                        ) VALUES (
                            '$programa_insert',
                            $os,
                            CURRENT_TIMESTAMP,
                            '$msg',
                            TRUE,
                            $login_fabrica,
                            $login_admin
                        );
                    ";
                    $res = pg_query($con,$sql);
                }

				$lista_defeitos = implode(",",$array_integridade);
				if(!empty($lista_defeitos)){
					$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
							WHERE os = $os
							AND   defeito_constatado NOT IN ($lista_defeitos) ";
					$res = @pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
					//o defeito constatado recebe o primeiro defeito constatado.
				}
				$defeito_constatado = $aux_defeito_constatado;

			}

            

			if(empty($defeito_constatado)) {
				$msg_erro ="Favor selecionar o $tema e clicar em Adicionar ";

                if ($login_fabrica == 30 && empty($solucao)) {
                    $msg_erro ="Favor Adicionar o $tema e Solução";
                }

			}else{
				$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
						WHERE  os    = $os
						AND    posto = $posto;";
				$res = @pg_query ($con,$sql);

            }
			// aqui insere o processo para gravar automaticamente
			$sqlenvio =  "UPDATE tbl_os_retorno
					SET nota_fiscal_retorno				= '1000',
						data_nf_retorno					= current_date,
						numero_rastreamento_retorno		= '1000',
						retorno_chegada					= current_date,
						envio_chegada					= current_date,
						admin_recebeu					= $login_admin,
						admin_enviou					= $login_admin
					WHERE os=$os";
			$resenvio = pg_query($con,$sqlenvio);

			/*
			$sqllibera = "INSERT INTO tbl_os_status
					(os,status_os,data,observacao,admin)
					VALUES ($os,64,current_timestamp,'OS Liberada da Intervenção',$login_admin)";
			$reslibera = pg_query($con,$sqllibera);
			*/

		}

           //HD-6856662
            if ($login_fabrica == 30) {
                 $produto_serie = $_POST['produto_serie'];                    
                if ($produto_serie == '00000000000000') {
                   if($fabricaFileUploadOS){
                        $sql = "SELECT tdocs
                                FROM tbl_tdocs 
                                WHERE referencia_id = $os
                                AND fabrica =   $login_fabrica
                                AND situacao = 'ativo'";
                                $res = pg_query($con, $sql);//exit($sql);
					   if(pg_num_rows($res) == 0){
							$msg_erro = 'É necessário informar anexos de nota ou fotos de produto <br>';
					   }    
                    }  
                 }       
             }

       if (strlen ($msg_erro) == 0) {

			if($login_fabrica == 86){
				$sqlDef = "SELECT defeito_constatado FROM tbl_os WHERE os=$os";
				$resDef = pg_query ($con,$sqlDef);
				$defeito_constatado = pg_fetch_result($resDef,0,'defeito_constatado');
			}

			if (strlen($defeito_constatado) == 0) $defeito_constatado = 'null';

            $sqlTipoAt = "SELECT tipo_atendimento
                          FROM tbl_os
                          WHERE os = {$os}";
            $resTipoAt = pg_query($con, $sqlTipoAt);

            $tipoAtendimentoOs = pg_fetch_result($resTipoAt, 0, 'tipo_atendimento');

			if($login_fabrica==19) {

                $defeito_post = $_POST['defeitos_lorenzetti_hidden'];
                $defeito_post = preg_replace('/[\[\]]/', '', $defeito_post);
                $defeito_post = str_replace('"', '', $defeito_post);
                $defeito_post = str_replace('\\', '', $defeito_post);
        
                $condicao_instalacao = $_POST['condicao_instalacao'];
				if(verifica_checklist_tipo_atendimento($tipoAtendimentoOs)) {
					if (strlen($condicao_instalacao) == 0) {
						$msg_erro .= "Informe a condição de instalação.<br>";
					}

					if (count($_POST['check_list_fabrica']) == 0) {
						$msg_erro .= "Selecione um checklist.<br>";
					}
				}

                if (empty($msg_erro)) {
                    #Apaga todos os defeitos reclamados e defeitos_constatados
                    $sql = "DELETE FROM tbl_os_defeito_reclamado_constatado WHERE os=$os";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }

                if (strlen($msg_erro) == 0) {
                    $count_defeito_reclamado  = count($_POST["defeito_reclamado"]);

                    for ($o = 0; $o < $count_defeito_reclamado; $o++) {
                        
                        $aux_defeito_reclamado    = trim($_POST["defeito_reclamado"][$o]);

                        if (strlen($aux_defeito_reclamado) == 0) {
                             continue;
                        }

                        $count_defeito_constatado = count($_POST["i_defeito_constatado"][$aux_defeito_reclamado]);
                        $post_defeito_constatado  = $_POST["i_defeito_constatado"][$aux_defeito_reclamado];



                        for ($j = 0; $j < $count_defeito_constatado; $j++) {

                            $aux_defeito_constatado = trim($post_defeito_constatado[$j]);
                            if (strlen($aux_defeito_constatado) == 0) {
                                continue;
                            }

                            $sql = "SELECT defeito_constatado_reclamado
                                      FROM tbl_os_defeito_reclamado_constatado
                                     WHERE os                 = $os
                                       AND defeito_reclamado  = $aux_defeito_reclamado
                                       AND defeito_constatado = $aux_defeito_constatado";
                            $res = pg_query($con,$sql);

                            $msg_erro .= pg_errormessage($con);
                            if (pg_num_rows($res) == 0) {

                                if (isset($_POST["check_list_fabrica"]) && count($_POST["check_list_fabrica"]) > 0) {

                                    $defeitos_checklist       = $_POST['check_list_fabrica'][$aux_defeito_reclamado][$aux_defeito_constatado];
                                    $count_defeitos_checklist = count($defeitos_checklist);

                                    if ($count_defeitos_checklist > 0) {
                                        for ($d=0; $d < $count_defeitos_checklist; $d++) {
                                            $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
                                                        os,
                                                        checklist_fabrica,
                                                        defeito_reclamado,
                                                        defeito_constatado,
                                                        fabrica
                                                    )VALUES(
                                                        {$os},
                                                        {$defeitos_checklist[$d]},
                                                        {$aux_defeito_reclamado},
                                                        {$aux_defeito_constatado},
                                                        {$login_fabrica}
                                                    )";

                                            $res = pg_query($con,$sql);
                                        }
                                    } else {
                                        $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
                                                    os,
                                                    defeito_reclamado,
                                                    defeito_constatado,
                                                    fabrica
                                                )VALUES(
                                                    {$os},
                                                    {$aux_defeito_reclamado},
                                                    {$aux_defeito_constatado},
                                                    {$login_fabrica}
                                                )";

                                        $res = pg_query($con,$sql);
                                    }

                                } else {
                                    $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
                                            os,
                                            defeito_reclamado,
                                            defeito_constatado,
                                            fabrica
                                        )VALUES(
                                            $os,
                                            $aux_defeito_reclamado,
                                            $aux_defeito_constatado,
                                            $login_fabrica
                                        )";
                                    $res = pg_query($con,$sql);
                                }

                                $msg_erro .= pg_errormessage($con);

                                $sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
                                         WHERE os                 = $os
                                           AND defeito_reclamado  = $aux_defeito_reclamado
                                           AND defeito_constatado IS NULL
                                           AND checklist_fabrica IS NULL";

                                $res = pg_query($con,$sql);
                                $msg_erro .= pg_errormessage($con);

                            }

                        }//count_defeito_constatado

                    }//count_defeito_reclamado

                }

                             
			}

            if(in_array($login_fabrica, [19])){

                 if (strlen(trim($msg_erro)) == 0) {

                    $sqlEXT = "SELECT campos_adicionais  FROM tbl_os_campo_extra WHERE os=$os";
                    $resEXT = pg_query($con,$sqlEXT);
                    if (pg_num_rows($resEXT) > 0) {
                       
                        $campos_adicionais = json_decode(pg_fetch_result($resEXT, 0, 'campos_adicionais'),true);
                        $campos_adicionais["condicao_instalacao"] = utf8_encode($condicao_instalacao);

                        if($defeito_cor_etiqueta_fornecedor == true){
                            $campos_adicionais['defeito_cor_etiqueta_fornecedor'] = 'sim';
                        }else{
                            if(isset($campos_adicionais['defeito_cor_etiqueta_fornecedor'])){
                                unset($campos_adicionais['defeito_cor_etiqueta_fornecedor']);
                            }                            
                        }

                        $campos_adicionaisENC = json_encode($campos_adicionais);

			if(strlen(trim($campos_adicionaisENC))>0){
                            $campos_adicionaisENC = "'".$campos_adicionaisENC."'"; 
                        }else{
			    $campos_adicionaisENC = 'null';
			}

                        $sqlENC = "UPDATE tbl_os_campo_extra SET campos_adicionais = ".$campos_adicionaisENC." WHERE os=$os";
                        $resENC = pg_query($con, $sqlENC);
//echo nl2br($sqlENC);
//exit;
                        $msg_erro .= pg_errormessage($con);

                    } else {

                        $campos_adicionais["condicao_instalacao"] = utf8_encode($condicao_instalacao);

                        if(strlen($cor_etiqueta)>0){
                            $campos_adicionais['cor_etiqueta_fornecedor'] = $cor_etiqueta;
                        }

                        $campos_adicionaisENC = json_encode($campos_adicionais);

                        $sqlENC = "INSERT INTO tbl_os_campo_extra (fabrica, os, campos_adicionais) VALUES ($login_fabrica, $os, '".$campos_adicionaisENC."')";
                        $resENC = pg_query($con, $sqlENC);
                        $msg_erro .= pg_errormessage($con);
                    }
                }  
            }          

			if($login_fabrica==19){
				$sqlta = "SELECT tipo_atendimento FROM tbl_os WHERE os = $os";
				$resta = pg_query ($con,$sqlta);
				if (pg_num_rows($resta)>0){
					$tipo_atendimento = pg_fetch_result($resta,0,0);
				}
				# HD 28155
				if ($tipo_atendimento <> 6){
					$sql = "SELECT defeito_constatado
							FROM tbl_os_defeito_reclamado_constatado
							WHERE os                 = $os LIMIT 1";
					$res = @pg_query ($con,$sql);
						if(pg_num_rows($res)>0){
							$defeito_constatado = pg_fetch_result($res,0,0);
						}else $msg_erro.= "É necessário informar o ".$tema;
				}
			}
		}
		if (strlen ($defeito_constatado) > 0) {
			$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $posto;";
            $res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		$defeito_reclamado = $_POST ['defeito_reclamado'];
		if (strlen ($defeito_reclamado) > 0) {
			$sql = "UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $posto;";
			$res = pg_query ($con,$sql);
		}

        if ($login_fabrica == 72 &&  strlen($msg_erro) == 0 && in_array($_POST['solucao_os'], array(3042, 3043, 3044, 3045, 3046))) {//fputti HD-3130038

            if (strlen($ajusterealizado) == 0) {
                $msg_erro .= "O campo Ajuste Realizado é obrigatório.<br />";
            }
            if (strlen($msg_erro) == 0) {
                $sql = " UPDATE tbl_os_extra SET obs_adicionais = '$ajusterealizado' WHERE os = $os";
                $res = pg_query ($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }
        }

        if ($login_fabrica == 72 && strlen($msg_erro) == 0 && strlen($falha_em_potencial) > 0) {//fputti HD-3130038
            $sql = "SELECT tbl_os.os, tbl_os_produto.servico, tbl_os_produto.os_produto
                      FROM tbl_os_produto
                      JOIN tbl_os ON tbl_os.os=tbl_os_produto.os
                     WHERE tbl_os.os=$os
                       AND tbl_os.fabrica=$login_fabrica
                       AND tbl_os_produto.servico IS NOT NULL";
            $res = pg_query($con,$sql);
            if (pg_num_rows($res) > 0) {
                $xoss       = pg_fetch_result($res, 0, os);
                $os_produto = pg_fetch_result($res, 0, os_produto);
                $sqlUp      = "UPDATE tbl_os_produto
                                  SET servico=$falha_em_potencial
                                WHERE os=$xoss
                                  AND os_produto=$os_produto";
                $resUp     = @pg_query($con,$sqlUp);
                $msg_erro .= pg_errormessage($con);
            } else {
                $prod_referencia = $_POST["produto_referencia"];
               if (strlen($prod_referencia) > 0) {
                    $sqlProduto = "SELECT tbl_produto.produto
                          FROM tbl_produto
                          JOIN tbl_os ON tbl_os.produto=tbl_produto.produto
                         WHERE tbl_os.os=$os
                           AND tbl_os.fabrica=$login_fabrica
                           AND tbl_produto.referencia='$prod_referencia'";
                    $resProduto = pg_query($con, $sqlProduto);
                    if (pg_num_rows($resProduto) > 0) {
                        $produto = pg_fetch_result($resProduto, 0, produto);
                        $sqlIns = "INSERT INTO tbl_os_produto (
                                os     ,
                                produto,
                                servico
                            )VALUES(
                                $os     ,
                                $produto,
                                $falha_em_potencial
                        );";

                        $resIns    = @pg_query($con,$sqlIns);
                        $msg_erro .= pg_errormessage($con);
                    }
                }
            }
        }

		if(isset($_POST ['tipo_atendimento'])) $tipo_atendimento = $_POST ['tipo_atendimento'];
		if (strlen ($tipo_atendimento) > 0) {
			$sql = "UPDATE tbl_os SET tipo_atendimento = $tipo_atendimento
					WHERE  tbl_os.os    = $os ";
			$res = pg_query ($con,$sql);
		}

		$causa_defeito = $_POST['causa_defeito'];
		if (strlen($causa_defeito) == 0) $causa_defeito = "null";
		else                             $causa_defeito = $causa_defeito;

		$sql = "UPDATE tbl_os SET causa_defeito = $causa_defeito
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $posto;";
		$res = @pg_query ($con,$sql);

		$x_solucao_os = $_POST['solucao_os'];

		if (strlen($x_solucao_os) > 0) {
            if ($login_fabrica == 72) {//fputti HD-3130038
                $temPeca = array();
                for ($i=0; $i < $_POST['qtde_item']; $i++) {
                    if (empty($_POST['peca_'.$i])) {
                        continue;
                    }
                    $temPeca[] = $_POST['peca_'.$i];
                }
                if (count($temPeca) == 0) {
                    $sqlSolucao = "SELECT solucao
                              FROM tbl_solucao
                             WHERE tbl_solucao.solucao = $x_solucao_os
                               AND tbl_solucao.fabrica = $login_fabrica
                               AND tbl_solucao.ativo = 't'
                               AND tbl_solucao.troca_peca = 't'";

                    $resSolucao = pg_query($con, $sqlSolucao);
                    if (pg_num_rows($resSolucao) > 0) {
                        $msg_erro.= "É obrigatório lançamento de peça.";
                    }
                }
            }

			$sql = "UPDATE tbl_os SET solucao_os = '$x_solucao_os'
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $posto;";
			$res = @pg_query($con,$sql);
			$msg_erro.= pg_errormessage($con);
		}
		$os_troca = $_POST['os_troca'];
		$x_solucao_os2 = trim($_POST['solucao_os2']);
		if(strlen($x_solucao_os2) > 0) {
			$sql = "INSERT INTO tbl_servico_realizado(fabrica,descricao,ativo,linha)values($login_fabrica,'$x_solucao_os2','f',549)";
			$res = pg_query($con,$sql);
			$sql = "SELECT currval ('seq_servico_realizado')";
			$res = pg_query($con,$sql);
			$x_solucao_os = pg_fetch_result($res,0,0);
			$sql = "UPDATE tbl_os SET solucao_os = $x_solucao_os
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $posto;";
			$res = @pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}


		$obs  = trim(str_replace("'","''",$_POST['obs']));

		//HD 238556
    	if ($login_fabrica == 30) {
	        $sql_obs = "SELECT
	                        tbl_os.obs
	                    FROM tbl_os
	                    WHERE tbl_os.os = $os
	                    and tbl_os.posto = $login_posto";

	        $res_obs = pg_query($con,$sql_obs);
	        $obs_os = pg_fetch_result($res_obs, 0, 0);

       		if (!empty($obs)){

            $sql_admin = "SELECT nome_completo from tbl_admin where admin=$login_admin";
            $res_admin = pg_query($con,$sql_admin);
            $nome_completo_admin = pg_fetch_result($res_admin, 0, 0);
            $data_insercao_obs = date("d/m/y h:i:s");

            if (!empty($obs_os)) {
                $obs = "$obs_os
                -----------------------------------------
                $obs
                Gravado pelo admin: $nome_completo_admin
                Em: $data_insercao_obs";
                       		}else{
                			$obs = "$obs
                Gravado pelo admin: $nome_completo_admin
                Em: $data_insercao_obs";
        	}
            // echo $obs;
            // exit;
	        }else{
	            if (!empty($obs_os)){
	                $obs = $obs_os;
	            }else{
	                $obs = '';
	            }
	        }

	    } else {

	        if (strlen($obs) > 0) $obs = "$obs";
	        else                  $obs = '';

	    }

		$tecnico_nome = trim($_POST["tecnico_nome"]);
		if (strlen($tecnico_nome) > 0) $tecnico_nome = "'".$tecnico_nome."'";
		else                   $tecnico_nome = "null";

		if($login_fabrica == 1 and ($compressor=='t' or $tipo_os == 13)){
			$sql = "SELECT  tbl_os.data_abertura   AS data_abertura,
							os_numero,
							tipo_atendimento
					FROM    tbl_os
					WHERE   tbl_os.os = $os";
			$res = pg_query ($con,$sql) ;

			$data_abertura = pg_fetch_result($res,0,data_abertura);
			$os_numero     = pg_fetch_result($res,0,os_numero);
			$tipo_atendimento= pg_fetch_result($res,0,tipo_atendimento);

			for ( $i = 0 ; $i < $qtde_visita ; $i++ ) {
				$xos_visita                 = trim($_POST['os_visita_'. $i]);
				$xdata                      = fnc_formata_data_pg(trim($_POST['visita_data_'. $i]));
				$xxdata                     = str_replace("'","",$xdata);
				$xhora_chegada_cliente      = trim($_POST['visita_hr_inicio_'. $i]);
				$xhora_saida_cliente        = trim($_POST['visita_hr_fim_'. $i]);
				$xkm_chegada_cliente        = trim($_POST['visita_km_'. $i]);
				$xkm_chegada_cliente        = str_replace (",",".",$xkm_chegada_cliente);
				$xqtde_produto_atendido     = trim($_POST['qtde_produto_atendido_'. $i]);
				$valores_adicionais         = trim($_POST['valores_adicionais_'. $i]);
				$justificativa_adicionais   = trim($_POST['justificativa_adicionais_'. $i]);

				$xxkm_chegada_cliente = number_format($xkm_chegada_cliente,1,'.','');
				$xkm_chegada_cliente = number_format($xkm_chegada_cliente,2,'.','');
				$km_conferencia = number_format($_POST['km_conferencia_'.$i],1,'.','') ;
				if($xxkm_chegada_cliente <> $km_conferencia and $xxkm_chegada_cliente > ($km_conferencia* 1.1) and $km_conferencia > 0) {
					$msg_erro .= "Fizemos a verificação de deslocamento ida e volta (endereço do posto até o cliente) e encontramos ". str_replace (".",",",$km_conferencia) ."KM de deslocamento. Por isso faremos a correção para prosseguir com a conclusão da OS. Em caso de dúvida gentileza entrar em contato com o seu suporte.";
					$visita_km_erro= $km_conferencia * 1.1;
				}

				if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";

				$valores_adicionais = str_replace (",",".",$valores_adicionais);

				if (strlen($justificativa_adicionais) > 0) $justificativa_adicionais = "'".$justificativa_adicionais."'";
				else                   $justificativa_adicionais = "null";

				if($tipo_os == 13){
					$sql = "SELECT  count(*) as count_visita
							FROM    tbl_os_visita
							WHERE   tbl_os_visita.os_revenda= $os_numero";
					$res_vis = @pg_query ($con,$sql) ;

					$count_visita= pg_fetch_result($res_vis,0,count_visita);
					if(strlen($count_visita)>0 and $count_visita>4){
						$msg_erro .= "Quantidade de visitas maior que o permitido: $count_visita.<BR>";
					}

					if($tipo_atendimento ==64 and $xkm_chegada_cliente > 0){
						$msg_erro .= "Não é permitido a digitação de quilometragem para OS Metais Sanitários Balcão.<BR>";
					}elseif($tipo_atendimento ==69 and $xkm_chegada_cliente > 100){
						$msg_erro .= "Tipo de atendimento incorreto, pois nesse caso trata-se de deslocamento superior a 100 Km, ou seja, fora da área de atuação. Gentileza corrigir";
					}elseif($tipo_atendimento ==65 and $xkm_chegada_cliente < 100 and $xkm_chegada_cliente > 0) {
						$msg_erro .= "Tipo de atendimento incorreto, pois nesse caso trata-se de deslocamento inferior a 100 Km, ou seja, dentro da área de atuação. Gentileza corrigir";
					}


					$sql = "
						SELECT  count(os_revenda_item) as qtde_itens_geo
						FROM    tbl_os_revenda_item
						WHERE   os_revenda= $os_numero ";
					$res = pg_query ($con,$sql) ;

					$qtde_itens_geo = pg_fetch_result($res,0,qtde_itens_geo);
					if($xqtde_produto_atendido > $qtde_itens_geo){
						$msg_erro .= "Quantidade de produtos digitados está maior que a quantidade de produtos da OS.<BR>";
					}
				}elseif($xkm_chegada_cliente > 100){
					$msg_erro .= "Quilometragem máxima permitida é de 100 Km.<BR>";
				}


				if($xxdata < $data_abertura){
					$msg_erro .= "Data de abertura é maior que a data da visita.<BR>";
				}

				if($xxdata <> "null" and $xxdata > date('Y-m-d')) {
					$msg_erro .= "Data de visita futura (maior que a data de hoje).<BR> ";
				}


				# HD 165538
				if($compressor=='t') {
					$hora_permitida = $xhora_saida_cliente - $xhora_chegada_cliente;
					if($hora_permitida > 4) {
						$msg_erro.= "De acordo com nossa engenharia o prazo para conserto (desmontagem e montagem) de um compressor desse modelo é de 2 a 4 horas. Sendo que, em serviços menos complexos o prazo é menor. Para os casos em que utilizar mais de 4 horas para conserto entre em contato com o seu suporte para avaliação da situação.";
					}
				}

				if(strlen($xos_visita) > 0){
					$cond_os_visita = " AND os_visita< $xos_visita ";
				}

				if(strlen($xhora_chegada_cliente)>0 and strlen($xhora_saida_cliente)>0){
					$xhora_chegada_cliente = "'$xxdata ".$xhora_chegada_cliente."'";
					$xhora_saida_cliente   = "'$xxdata ".$xhora_saida_cliente."'";
					$sql = " SELECT $xhora_chegada_cliente::timestamp > $xhora_saida_cliente::timestamp";
					$res = pg_query($con,$sql);
					if(pg_fetch_result($res,0,0) == 't') {
						$msg_erro .= "Hora de início é maior que a hora de fim na visita técnica.<BR> ";
					}
				}

				if(strlen($xqtde_produto_atendido)== 0 ) {
					$xqtde_produto_atendido = " 1 ";
				}


				if($xxdata <>'null' and (strlen($xkm_chegada_cliente)>0) and (strlen($xos_visita)==0) and (strlen($msg_erro)==0)){

					$sql = "INSERT INTO tbl_os_visita (
										$sql_aux_os          ,
										data                 ,
										km_chegada_cliente   ,
										valor_adicional      ,
										justificativa_valor_adicional,
										qtde_produto_atendido
									) VALUES (
										$aux_os                ,
										$xdata                 ,
										$xkm_chegada_cliente   ,
										$valores_adicionais    ,
										$justificativa_adicionais,
										$xqtde_produto_atendido
									)";
					$res = @pg_query ($con,$sql);
					//echo "inseriu $sql<BR>";
				}

				if((strlen($xxdata)>0) and (strlen($xkm_chegada_cliente)>0) and (strlen($xos_visita)>0) and (strlen($msg_erro)==0)){
					$sql = "UPDATE tbl_os_visita set
									data                 = $xdata                 ,
									km_chegada_cliente   = $xkm_chegada_cliente   ,
									valor_adicional      = $valores_adicionais    ,
									justificativa_valor_adicional = $justificativa_adicionais,
									qtde_produto_atendido= $xqtde_produto_atendido
								WHERE $sql_aux_os = $aux_os
								AND   os_visita = $xos_visita";
					//echo "atualiza $sql";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				if((strlen($xos_visita)>0) and ($xxdata=="null")){
					$sql = "DELETE FROM tbl_os_visita
									WHERE  $sql_aux_os      = $aux_os
									AND    tbl_os_visita.os_visita = $xos_visita;";
				//	echo "apaga: $sql";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

				}
			}
			/*hd: 83010*/
			if($tipo_os ==13){
				$sql = "
					SELECT  distinct km_chegada_cliente
					FROM    tbl_os_visita
					WHERE   os_revenda= $os_numero ";
				$res_visita = pg_query ($con,$sql) ;

				if(pg_num_rows($res_visita)> 1 ){
					$msg_erro .= "Não é permitido que cadastre km diferente para as visitas.<BR> ";
				}
			}

			//*coloquei 24-01*//
			$tecnico = trim($_POST['tecnico']);
			if (strlen ($tecnico) > 0) $tecnico = "'".$tecnico."'";
				else   $msg_erro .= "Relatório técnico obrigatório";
			if(strlen($msg_erro)==0){
				$sql = "UPDATE tbl_os_extra set
								valor_por_km = 0.65,
								valor_total_hora_tecnica = 0.4
			                       WHERE os=$os";
                #echo $sql;
                #exit;
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}

		$valores_adicionais = trim($_POST["valores_adicionais"]);
		$valores_adicionais = str_replace (",",".",$valores_adicionais);
		if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";

		$justificativa_adicionais = trim($_POST["justificativa_adicionais"]);
		if (strlen($justificativa_adicionais) > 0) $justificativa_adicionais = "'".$justificativa_adicionais."'";
		else                   $justificativa_adicionais = "null";

		$qtde_km = trim($_POST["qtde_km"]);
		$qtde_km = str_replace (",",".",$qtde_km);

		$peca_alterada = "";
		if (strlen($qtde_km) == 0){
			$update_km = "";
		} else {
			$update_km = " qtde_km      = $qtde_km     ,";
		}

		if (strlen ($obs) > 0) {
			$sql = "UPDATE  tbl_os SET obs = '$obs',
					tecnico_nome = $tecnico_nome,
					$update_km
					valores_adicionais = $valores_adicionais,
					justificativa_adicionais = $justificativa_adicionais
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $posto";

			$res = @pg_query ($con,$sql);
		}

		#HD 14504
		/*#HD 14504
		$sql = "DELETE FROM tbl_os_produto
				WHERE  tbl_os_produto.os         = tbl_os.os
				AND    tbl_os_produto.os_produto = tbl_os_item.os_produto
				AND    tbl_os_item.pedido           IS NULL
				AND    tbl_os_item.liberacao_pedido IS false
				AND    tbl_os_produto.os = $os
				AND    tbl_os.fabrica    = $login_fabrica
				AND    tbl_os.posto      = $posto;";
		#$res = @pg_query ($con,$sql);
		*/

		##### É TROCA FATURADA #####
		if (strlen($troca_faturada) > 0) {
			$x_motivo_troca = trim($_POST['motivo_troca']);
			if (strlen($x_motivo_troca) == 0) $x_motivo_troca = "null";
	            if (strlen($tecnico) == 0) $tecnico = "null";

			$resX = pg_query ($con,"BEGIN TRANSACTION");

		    $sql =	"UPDATE tbl_os SET
							motivo_troca  = $x_motivo_troca
					WHERE  tbl_os.os      = $os
					and    tbl_os.fabrica = $login_fabrica;";
			$res = @pg_query ($con,$sql);

		  ##### NÃO É TROCA FATURADA #####
		} else {

            //echo '<pre>'; print_r($_POST); echo '</pre>';exit;

			$qtde_item = $_POST['qtde_item'];
            $linhas_itens = $qtde_item;

            if ($login_fabrica == 6)  $qtde_item = $qtde_item + 5;//Mais itens para a Tectoy
			if ($login_fabrica == 45) $qtde_item = $qtde_item + 7;

			$array_pecas_kit = array();

			$po_peca = array();
        	$po_peca_usa = array();

        	if ($login_fabrica == 50) {
	            $peca_unica = array();
	            $xproduto = $_POST["produto_referencia"];

	            for ($i = 0; $i < $qtde_item; $i++) {
	                $xpeca_ref = $_POST["peca_".$i];

	                if (strlen($xpeca_ref) > 0) {
	                    $sql = "SELECT peca_unica_os FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = '$xpeca_ref' AND peca_unica_os IS TRUE";
	                    $res = pg_query($con, $sql);

	                    if (pg_num_rows($res) > 0) {
	                        $peca_unica[] = $xpeca_ref;
	                    }
	                }
	            }

	            if (count($peca_unica) > 1) {
	                $msg_erro .= "As seguintes peças não podem ser lançadas juntas: ".implode(", ", $peca_unica);
	            }
	        }

            ### hd_chamado=2727786 ######
            $qtd_peca = 0;
            //$audDel = new AuditorLog();
            $os_produto_del = "";

            for ($i=0; $i < $qtde_item; $i++) {
                if(strlen($_POST["peca_".$i]) > 0){
                    $qtd_peca++;
                }

                $xos_produto        = trim($_POST['os_produto_'. $i]);
                $xpeca              = trim($_POST["peca_". $i]);
				$xxpeca_anterior     = trim($_POST["peca_".$i."_anterior"]);

                if ($i == 0 && strlen($xos_produto) > 0) {
                    //$audDel->retornaDadosSelect("SELECT peca,qtde,servico_realizado,peca_obrigatoria FROM tbl_os_item WHERE os_produto={$xos_produto}");
                }

                if (strlen($xos_produto) > 0 and strlen($xpeca) == 0 ){
                    $os_produto_del = $xos_produto;
                    $antesDel = '';

                    if ($login_fabrica == 91) {
                        //Informações para o Auditor
                        $sqlAntesDel = "SELECT os_produto, posicao, peca, qtde, defeito, causa_defeito, servico_realizado, admin, peca_causadora, parametros_adicionais,peca_obrigatoria, fornecedor
                                FROM tbl_os_item WHERE os_produto = $xos_produto";
                        $resAntesDel = pg_query($con,$sqlAntesDel);
                        $rowAntesDel    = pg_fetch_all($resAntesDel);
                    }
                    $sql = "UPDATE tbl_os_produto SET
                                os = 4836000
                            FROM   tbl_os_item
                            WHERE  tbl_os_produto.os            = $os
                            AND    tbl_os_produto.os_produto    = tbl_os_item.os_produto
                            AND    tbl_os_produto.os_produto    = $xos_produto
                            AND    tbl_os_item.pedido           IS NULL
                            AND    tbl_os_item.liberacao_pedido IS false";

                    $res = pg_query ($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    if (strlen($msg_erro) == 0 && $login_fabrica == 125) {

                        $amazonTC->getObjectList("peca_critica-{$os}-{$xxpeca_anterior}-{$i}");
                        $pathinfo = pathinfo($amazonTC->files[0]);
                        $file = $pathinfo["basename"];
                        if (strlen($file) > 0) {
                            $amazonTC->deleteObject($file,false,"","");
                        }

                    }
				}
            }

            if (!empty($os_produto_del)) {
                //$audDel->retornaDadosSelect()->enviarLog('DELETE','tbl_os_item',$os_produto_del);
            }

            ### hd_chamado=2727786 ######
            #for ($i = 0; $i < $qtd_peca; $i++) {

            $pecas_lancadas         = array();
            $pecas_lancadas_os_item = array();
            $alterou_pecas          = false;

            if(in_array($login_fabrica, array(104))){

                for ($i = 0 ; $i < $qtde_item ; $i++) {

                    $xos_item  = $_POST['os_item_'.$i];
                    $xpeca_ref = $_POST['peca_'.$i];

                    if(strlen($xos_item) == 0 && strlen($xpeca_ref) == 0){
                        continue;
                    }

                    if(strlen($xos_item) == 0 && strlen($xpeca_ref) > 0){
                        $alterou_pecas = true;
                        continue;
                    }

                    $sql_peca_id = "SELECT peca FROM tbl_peca WHERE referencia = '{$xpeca_ref}' AND fabrica = {$login_fabrica}";
                    $res_peca_id = pg_query($con, $sql_peca_id);

                    if(pg_num_rows($res_peca_id) > 0){

                        $xpeca = pg_fetch_result($res_peca_id, 0, "peca");

                        $sql_verifica_peca = "SELECT os_item FROM tbl_os_item WHERE fabrica_i = {$login_fabrica} AND os_item = {$xos_item} AND peca = {$xpeca}";
                        $res_verifica_peca = pg_query($con, $sql_verifica_peca);

                        if(pg_num_rows($res_verifica_peca) == 0) {
                            $alterou_pecas = true;
                        }else{
                            $pecas_lancadas_os_item[] = $xpeca_ref;
                        }

                    }

                }

            }

            // HD-6574162
            /*for ($p = 0; $p < $qtde_item; $p++) {
                if(strlen(trim($_POST["peca_". $p])) == 0){
                    continue;
                }
                $ref_peca   = trim($_POST["peca_" . $p]);
                $desc_peca  = trim($_POST["descricao_". $p]);

                $sql_peca_bloqueada = "SELECT bloqueada_garantia FROM tbl_peca WHERE referencia = '$ref_peca' AND fabrica = $login_fabrica ";
                $res_peca_bloqueada = pg_query($con, $sql_peca_bloqueada);
                if (pg_num_rows($res_peca_bloqueada) > 0) {
                    if (pg_fetch_result($res_peca_bloqueada, 0, 'bloqueada_garantia') == "t" && !in_array($login_fabrica, [3,72])) {
                        $msg_erro .= "<br>Peça: $ref_peca - $desc_peca bloqueada para garantia. <br>";
                    }
                } 
            }*/

            $array_peca_de_gas = array('073894','073897','073373','771889','074957','773936');
            $array_pecas_lancamento = [];
            
            for ($i = 0; $i < $qtde_item; $i++){

               //HD-6930547
                if($login_fabrica == 85) {  
             
                       if ($qtde_item > 0) {
                              $sqlVerAud = "SELECT os FROM  tbl_os_status WHERE os = $os AND status_os IN (62,64)";
                              $resVerAud = pg_query($con,$sqlVerAud);

                            if (pg_num_rows($resVerAud) == 0) {
                              $sqlGravaAud = "INSERT INTO tbl_os_status(os,
                                                                        status_os,
                                                                        observacao
                                                           ) VALUES (   $os,
                                                                        62,
                                                                        'Intervenção de Entrada de Peças na OS')";
                              $resGravaAud = pg_query($con,$sqlGravaAud);
                            }
                       } 
                }        

                if(strlen(trim($_POST["peca_". $i])) == 0){
                    continue;
                }
                $xos_item           = trim($_POST["os_item_"        . $i]);
				$xos_produto        = trim($_POST['os_produto_'     . $i]);
				$xpeca              = trim($_POST["peca_"           . $i]);
                $xpeca_anterior     = trim($_POST["peca_"           . $i."_anterior"]);
                $xposicao           = trim($_POST["posicao_"        . $i]);
                $xqtde              = trim($_POST["qtde_"           . $i]);
                $xdefeito           = trim($_POST["defeito_"        . $i]);
                $xdefeito_anterior  = trim($_POST["defeito_"        . $i."_anterior"]);
                $xpcausa_defeito    = trim($_POST["pcausa_defeito_" . $i]);
                $xservico           = trim($_POST["servico_"        . $i]);
                $xservico_anterior  = trim($_POST["servico_"        . $i."_anterior"]);
                $xkit_peca          = $_POST['kit_kit_peca_'        . $i];
                $depara_auditoria   = $_POST['depara_auditoria_'    . $i];
                $xfornecedor        = $_POST['fornecedor_'          . $i];

                if($login_fabrica == 134 AND strlen(trim($xqtde)) == 0 ){
                    $msg_erro .= " Informar a quantidade da $xpeca. <Br> ";
                }

                if(empty($xfornecedor)) {
                    $xfornecedor = "null";
				}

                if (in_array($login_fabrica, [3])) {

                    if(in_array($xpeca, $array_peca_de_gas)) {
                        if($xservico <> 692){
                            $msg_erro .= "O serviço para peça $xpeca deve ser Recarga de Gás.";
                        }
                    }

                }

                if ($login_fabrica == 1 && !empty($xpeca)) {
                    if (in_array($xpeca, $array_pecas_lancamento)) {
                        $msg_erro .= "A peça $xpeca não pode ser lançada mais de uma vez, favor deixar somente uma e acrescentar na quantidade .";
                    }

                    $array_pecas_lancamento[] = $xpeca; 
                }

				if($login_fabrica == 35){
	                $po_peca[$i] = $_POST['po_peca_'.$i];

	                $sql_po = "SELECT promocao_site FROM tbl_peca WHERE referencia = '$xpeca' AND fabrica = $login_fabrica";
	                $res_po = pg_query($con, $sql_po);

	                if(pg_num_rows($res_po) > 0){
	                    $promocao_site = pg_fetch_result($res_po, 0, promocao_site);

	                    if ($promocao_site == "t") {
	                        $po_peca_usa[$i] = "t";
	                    }

	                    if($promocao_site == 't' && strlen($po_peca[$i]) == 0){
	                        $msg_erro .= "O PO Peça da peça $xpeca é obrigatório. <br />";
	                    }
	                }

	            }
	            if ($login_fabrica == 50 && strlen($xpeca) > 0) {
	                $sql_peca = "SELECT peca FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = '$xpeca'";
	                $res_peca = pg_query($con, $sql_peca);

	                if (pg_num_rows($res_peca) > 0) {
	                    $peca_aux = pg_fetch_result($res_peca, 0, "peca");

	                    $sql_produto = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND referencia = '$xproduto'";
	                    $res_produto = pg_query($con, $sql_produto);

	                    if (pg_num_rows($res_produto)) {
	                        $produto_aux = pg_fetch_result($res_produto, 0, "produto");

	                        if (in_array($produto_aux, array(34333, 212885, 212884, 34914, 214793)) && in_array($peca_aux, array(1466423, 864884, 854989, 1501803, 1539430, 1539441, 1547942, 1547941, 1547940))) {
	                            $sql_os_serie = "SELECT data_fabricacao
	                                             FROM tbl_os
	                                             JOIN tbl_numero_serie ON tbl_numero_serie.produto = tbl_os.produto AND tbl_numero_serie.serie = tbl_os.serie AND tbl_numero_serie.fabrica = $login_fabrica
	                                             WHERE tbl_os.os = $os
	                                             AND tbl_os.fabrica = $login_fabrica";
	                            $res_os_serie = pg_query($con, $sql_os_serie);

	                            if (pg_num_rows($res_os_serie) > 0) {
	                                $serie_data_fabricacao = pg_fetch_result($res_os_serie, 0, "data_fabricacao");

	                                if (strtotime($serie_data_fabricacao) < strtotime('2014-03-01')) {
	                                    if (in_array($peca_aux, array(1501803, 1539430, 1539441))) {
	                                        $msg_erro .= "A peça $xpeca não pode ser lançada na OS<br />";
	                                    } else if (in_array($peca_aux, array(1466423, 864884, 854989))) {
	                                        switch ($peca_aux) {
	                                            case 1466423:
	                                                $msg_erro .= "Por favor troque a peça $xpeca pela peça 077.1.602<br />";
	                                                break;

	                                            case 864884:
	                                                $msg_erro .= "Por favor troque a peça $xpeca pela peça 077.1.601<br />";
	                                                break;

	                                            case 854989:
	                                                $msg_erro .= "Por favor troque a peça $xpeca pela peça 077.1.600<br />";
	                                                break;
	                                        }
	                                    }
	                                } else {
	                                    if (in_array($peca_aux, array(1466423, 864884, 854989, 1547942, 1547941, 1547940))) {
	                                        $msg_erro .= "A peça $xpeca não pode ser lançada na OS<br />";
	                                    }
	                                }
	                            }
	                        }
	                    }
	                }
	            }

        # HD 925803

        //echo '<pre>'; print_r($_POST); echo '</pre>'; exit();
        //exit('OS item: '.$_POST["os_item_2"].' Peça Atual: '.$_POST["peca_2"].' Peça Anterior: '.$_POST["peca_2_anterior"]);

		$xdescricao        = trim($_POST["descricao_".$i]);
		$xdescricao_anterior = trim($_POST["descricao_".$i."_anterior"]);


        if(in_array($login_fabrica, array(104))){

            if(strlen($xservico) > 0){

                $servico_gera_troca = false;

                $sql_servico_troca = "SELECT troca_de_peca FROM tbl_servico_realizado WHERE servico_realizado = {$xservico} AND fabrica = {$login_fabrica}";
                $res_servico_troca = pg_query($con, $sql_servico_troca);

                if(pg_num_rows($res_servico_troca) > 0){

                    $troca_peca = pg_fetch_result($res_servico_troca, 0, "troca_de_peca");
                    $servico_gera_troca = ($troca_peca == "t") ? true : false;

                }

                if(!in_array($xpeca_ref, $pecas_lancadas_os_item)){
                    if (!empty($xpeca_ref) && !empty($descricao) && $servico_gera_troca === true) {
                        $pecas_lancadas[] = $xpeca_ref . ' - ' . $descricao;
                    }
                }

            }

        }


	/* # HD 925803
	 * Somente grava LOG na segunda vez em que for inserir alguma peça, por ex.:
	 * Se a peça anterior está vazia e a atual preenchida significa que é o 1º insert e não grava LOG
	 */
	if( $login_fabrica == 52 ){

		/* Recuperando a Descrição do Defeito para a Fábrica de código 52 - Fricon */
		if( empty($xdefeito) ){
			$id_xdefeito = $xdefeito_anterior; // Caso o usuário excluiu a peça então pegamos o id da defeito anterior que é o mesmo no hidden
		}else{
			$id_xdefeito = $xdefeito;
		}


		if( !empty($id_xdefeito) ){
			/* Recuperando a Descrição do Defeito para a Fábrica de código 52 - Fricon */
			$sql_tbl_defeito = "SELECT descricao AS descricao_defeito_atual FROM tbl_defeito WHERE defeito = ".$id_xdefeito." AND fabrica = ".$login_fabrica.";";
			$res_tbl_defeito = pg_query($con, $sql_tbl_defeito);
			//echo pg_last_error($con);
			if( pg_num_rows($res_tbl_defeito) > 0 ){
				$descricao_xdefeito = pg_fetch_result($res_tbl_defeito, 0, descricao_defeito_atual);
			}else{
				$descricao_xdefeito = 'Defeito NÃO encontrado!';
			}

			/* Descrição do Defeito anterior */
			if( !empty($xdefeito_anterior) ){
				$sql_tbl_defeito_anterior = "SELECT descricao AS descricao_defeito_anterior FROM tbl_defeito WHERE defeito = '".$xdefeito_anterior."' AND fabrica = ".$login_fabrica.";";
				$res_tbl_defeito_anterior = pg_query($con, $sql_tbl_defeito_anterior);

				if( pg_num_rows($res_tbl_defeito_anterior) > 0 ){
					$descricao_xdefeito_anterior = pg_fetch_result($res_tbl_defeito_anterior, 0, descricao_defeito_anterior);
				}else{
					$descricao_xdefeito_anterior = 'Defeito anterior NÃO encontrado!';
				}
			}
		}else{
			$descricao_xdefeito = 'Defeito anterior NÃO encontrado!';
		}

		/* Recuperando a Descrição do Serviço para a Fábrica de código 52 - Fricon */
		if( empty($xservico) ){
			$id_xservico = $xservico_anterior; // Caso o usuário excluiu a peça então pegamos o id da serviço anterior que é o mesmo no hidden
		}else{
			$id_xservico = $xservico;
		}

		if( !empty($id_xservico) ){
			/* Recuperando a Descrição do Serviço para a Fábrica de código 52 - Fricon */
			$sql_tbl_servico = "SELECT descricao AS descricao_servico_atual, troca_de_peca FROM tbl_servico_realizado WHERE servico_realizado = ".$id_xservico." AND fabrica = ".$login_fabrica.";";
			$res_tbl_servico = pg_query($con, $sql_tbl_servico);

			if( pg_num_rows($res_tbl_servico) > 0 ){
				$descricao_xservico = pg_fetch_result($res_tbl_servico, 0, descricao_servico_atual);
                $troca_de_peca      = pg_fetch_result($res_tbl_servico, 0, troca_de_peca);
			}else{
				$descricao_xservico = 'Serviço NÃO encontrado!';
			}

			/* Descrição da Serviço anterior */
			if( !empty($xservico_anterior) ){
				$sql_tbl_servico_anterior = "SELECT descricao AS descricao_servico_anterior, troca_de_peca FROM tbl_servico_realizado WHERE servico_realizado = ".$xservico_anterior." AND fabrica = ".$login_fabrica.";";
				$res_tbl_servico_anterior = pg_query($con, $sql_tbl_servico_anterior);

				if( pg_num_rows($res_tbl_servico_anterior) > 0 ){
					$descricao_xservico_anterior = pg_fetch_result($res_tbl_servico_anterior, 0, descricao_servico_anterior);
                    $troca_de_peca = pg_fetch_result($res_tbl_servico_anterior, 0, troca_de_peca);
				}else{
					$descricao_xservico_anterior = 'Serviço NÃO encontrado!';
				}
			}
		}else{
			$descricao_xservico = 'Serviço NÃO encontrado!';
		}

		/* Recuperando a Descrição da Peça para a Fábrica de código 52 - Fricon */
		if( empty($xpeca) ){
			$id_xpeca = $xpeca_anterior; // Caso o usuário excluiu a peça então pegamos o id da peça anterior que é o mesmo no hidden
		}else{
			$id_xpeca = $xpeca;
		}

		if( !empty($id_xpeca) ){
			$sql_tbl_peca = "SELECT descricao AS descricao_peca_atual FROM tbl_peca WHERE referencia = '".$id_xpeca."' AND fabrica = ".$login_fabrica.";";
			$res_tbl_peca = pg_query($con, $sql_tbl_peca);

			if( pg_num_rows($res_tbl_peca) > 0 ){
				$descricao_xpeca = pg_fetch_result($res_tbl_peca, 0, descricao_peca_atual);
			}else{
				$descricao_xpeca = 'Peça NÃO encontrada!';
			}

			/* Descrição da Peça anterior */
			if( !empty($xpeca_anterior) ){
				$sql_tbl_peca_anterior = "SELECT descricao AS descricao_peca_anterior FROM tbl_peca WHERE referencia = '".$xpeca_anterior."' AND fabrica = ".$login_fabrica.";";
				$res_tbl_peca_anterior = pg_query($con, $sql_tbl_peca_anterior);

				if( pg_num_rows($res_tbl_peca_anterior) > 0 ){
					$descricao_xpeca_anterior = pg_fetch_result($res_tbl_peca_anterior, 0, descricao_peca_anterior);
				}else{
					$descricao_xpeca_anterior = 'Peça NÃO encontrada!';
				}
			}
		}else{
			$descricao_xpeca = 'Peça NÃO encontrada!';
		}

		/*  Definição básica  da ação do usuário:
			os_item		peca_1	  peca_1_anterior
			N			OK		  N				   INSERT
			OK			N		  OK			   DELETE
			OK			OK		  OK			   UPDATE
		*/

		$data_hoje = date('d/m/Y');

		if( empty($xpeca) and !empty($xpeca_anterior) ){

			if( !empty($xos_item) and empty($xpeca) and !empty($xpeca_anterior) ){
				$desc_acao = "<b>Excluiu a Peça:</b> ".$xpeca_anterior." ";
				// Excluiu a Peça
				$array[$i] = array('desc_acao' => $desc_acao);
			}
		}
		else if( !empty($xpeca) and !empty($xpeca_anterior) ){

			if( (!empty($xos_item) and !empty($xpeca) and !empty($xpeca_anterior) and $xpeca != $xpeca_anterior) and ($xservico == $xservico_anterior and $xdefeito == $xdefeito_anterior) ){
				$desc_acao = "<b>Alterou a Peça</b> de ".$xpeca_anterior." para ".$xpeca." ";
				// Alterou a Peça
				$array[$i] = array('desc_acao' => $desc_acao);
			}
			else if( (!empty($xos_item) and !empty($xpeca) and !empty($xpeca_anterior) and $xpeca != $xpeca_anterior)  and  (!empty($xos_item) and !empty($xdefeito) and !empty($xdefeito_anterior) and $xdefeito != $xdefeito_anterior) and (!empty($xos_item) and !empty($xservico) and !empty($xservico_anterior) and $xservico == $xservico_anterior) ){
				$desc_acao = "<b>Alterou a Peça</b> de ".$xpeca_anterior." para ".$xpeca." <b>e o Defeito</b> de ".$descricao_xdefeito_anterior." para ".$descricao_xdefeito."";
				// Alterou a Peça e o Defeito
				$array[$i] = array('desc_acao' => $desc_acao);
			}
			else if( (!empty($xos_item) and !empty($xpeca) and !empty($xpeca_anterior) and $xpeca != $xpeca_anterior)  and  (!empty($xos_item) and !empty($xservico) and !empty($xservico_anterior) and $xservico != $xservico_anterior) and (!empty($xos_item) and !empty($xdefeito) and !empty($xdefeito_anterior) and $xdefeito == $xdefeito_anterior) ){
				$desc_acao = "<b>Alterou a Peça</b> de ".$xpeca_anterior." para ".$xpeca." <b>e o Serviço</b> de ".$descricao_xservico_anterior." para ".$descricao_xservico."";
				// Alterou a Peça e o Serviço
				$array[$i] = array('desc_acao' => $desc_acao);
			}
			else if( (!empty($xos_item) and !empty($xpeca) and !empty($xpeca_anterior) and $xpeca != $xpeca_anterior)  and  (!empty($xos_item) and !empty($xservico) and !empty($xservico_anterior) and $xservico != $xservico_anterior) and (!empty($xos_item) and !empty($xdefeito) and !empty($xdefeito_anterior) and $xdefeito != $xdefeito_anterior) ){
				$desc_acao = "<b>Alterou a Peça</b> de ".$xpeca_anterior." para ".$xpeca." <b>e o Serviço</b> de ".$descricao_xservico_anterior." para ".$descricao_xservico." e o Defeito de ".$descricao_xdefeito_anterior." para ".$descricao_xdefeito."";
				// Alterou a Peça, Serviço e o Defeito
				$array[$i] = array('desc_acao' => $desc_acao);
			}
			else if( !empty($xos_item) and !empty($xservico) and !empty($xservico_anterior) and $xservico != $xservico_anterior and (!empty($xos_item) and !empty($xdefeito) and !empty($xdefeito_anterior) and $xdefeito == $xdefeito_anterior) ){
				$desc_acao = "<b>Alterou o Serviço</b> na peça: ".$xpeca." de ".$descricao_xservico_anterior." para ".$descricao_xservico."";
				// Alterou o Serviço
				$array[$i] = array('desc_acao' => $desc_acao);
			}
			else if( !empty($xos_item) and !empty($xdefeito) and !empty($xdefeito_anterior) and $xdefeito != $xdefeito_anterior and (!empty($xos_item) and !empty($xpeca) and !empty($xpeca_anterior) and $xpeca == $xpeca_anterior) and ( !empty($xos_item) and !empty($xservico) and !empty($xservico_anterior) and $xservico == $xservico_anterior ) ){
				$desc_acao = "<b>Alterou o Defeito</b> na peça: ".$xpeca." de ".$descricao_xdefeito_anterior." para ".$descricao_xdefeito."";
				// Alterou o Defeito
				$array[$i] = array('desc_acao' => $desc_acao);
			}
			else if( (!empty($xos_item) and !empty($xdefeito) and !empty($xdefeito_anterior) and $xdefeito != $xdefeito_anterior)  and  (!empty($xos_item) and !empty($xservico) and !empty($xservico_anterior) and $xservico != $xservico_anterior ) ){
				$desc_acao = "<b>Alterou o Defeito</b> na peça: ".$xpeca." de ".$descricao_xdefeito_anterior." para ".$descricao_xdefeito." <b>e o Serviço</b> de ".$descricao_xservico_anterior." para ".$descricao_xservico."";
				// Alterou o Defeito e o Serviço
				$array[$i] = array('desc_acao' => $desc_acao);
			}
			else{
				//$desc_acao = ' Aqui - Sem Ação !!! - i='.$i; // só pra teste de validação...
			}


		}else if( /*( empty($xos_item) and empty($xos_produto) and !empty($xpeca) and empty($xpeca_anterior) ) and*/ ( $xdescricao_anterior == '' and ($xdescricao_anterior != $xdescricao) )  ){
				$desc_acao = '<b>Cadastrou a Peça:</b> '.$xpeca;
				// Cadastrou a Peça
				$array[$i] = array('desc_acao' => $desc_acao);
		}
		else{
			//$desc_acao = null; // só pra teste de validação...
		}

	}
        # HD 925803 - fim


                if($login_fabrica == 87){
                    $xpeca_causadora = trim($_POST["peca_causadora_".$i]);
                    $xdescricao_causadora = trim($_POST["descricao_causadora_".$i]);
                }

				if($login_fabrica == 95)
					$xdefeito = 'null';

				$xadmin_peca      = $_POST["admin_peca_"     . $i]; //aqui
				if(strlen($xadmin_peca)==0) $xadmin_peca ="$login_admin"; //aqui
				if($xadmin_peca=="P" OR strlen($xadmin_peca) == 0)$xadmin_peca ="null"; //aqui

				#HD 670814 - INICIO

				if((in_array($login_fabrica,$fabricas_padrao_itens) || $login_fabrica > 99) && !in_array($login_fabrica, array(91,120,201,172)) )
				{
					$xdefeito = 'null';
				}

				#HD 670814 - FIM


                if($login_fabrica == 19 and strlen($_POST['peca_'           . $i]) > 0){ //hd_chamado=2881143
                    $defeitoL = $_POST["defeitos_lorenzetti_hidden"];
                    $defeitosL = str_replace('[', '', $defeitoL);
                    $defeitosIN = str_replace(']', '', $defeitosL);
                    $defeitosIN = str_replace('"', '', $defeitosIN);

                    $sql_valida_pecas = "SELECT DISTINCT tbl_peca.peca
                            FROM tbl_peca
                            JOIN tbl_peca_defeito_constatado ON tbl_peca.peca = tbl_peca_defeito_constatado.peca
                            WHERE tbl_peca.referencia = '$xpeca'
                            AND tbl_peca_defeito_constatado.defeito_constatado IN ($defeitosIN)";

                    $res_valida_pecas = pg_query($con, $sql_valida_pecas);
                    if(pg_num_rows($res_valida_pecas) == 0){
                        $msg_erro = "Peça $xpeca não pertence a nenhum defeito lançado";
                    }
                }

                /* HD 20065 15/5/2008*/
				if(strlen($xos_item)>0 AND strlen($msg_erro)==0){
					$sqlP = "SELECT tbl_peca.peca
							FROM   tbl_peca
							WHERE  upper(tbl_peca.referencia_pesquisa) = upper('$xpeca')
							AND    tbl_peca.fabrica             = $login_fabrica;";
					$resP = @pg_query ($con,$sqlP);
					$msg_erro .= pg_errormessage($con);
					if (@pg_num_rows ($resP) > 0) {
						$xpeca_admin = pg_fetch_result ($resP,0,peca);
					}

                    $sqlA = "SELECT peca AS peca_admin                  ,
									defeito AS defeito_admin            ,
									causa_defeito AS causa_defeito_admin,
									servico_realizado AS servico_admin
							FROM tbl_os_item
							WHERE os_item = $xos_item";

					$resA = pg_query($con, $sqlA);

					$msg_erro .= pg_errormessage($con);

                    if(@pg_num_rows($resA)>0){

						$peca_admin          = pg_fetch_result($resA,0,peca_admin);
						$defeito_admin       = pg_fetch_result($resA,0,defeito_admin);
						$causa_defeito_admin = pg_fetch_result($resA,0,causa_defeito_admin);
						$servico_admin       = pg_fetch_result($resA,0,servico_admin);


						if($peca_admin<>$xpeca_admin OR $xdefeito<>$defeito_admin OR $xpcausa_defeito<>$causa_defeito_admin OR $xservico<>$servico_admin){

                        	$xadmin_peca ="$login_admin";
                        }
                    }
				}
				if (strlen($xposicao) > 0) $xposicao = "'" . $xposicao . "'";
				else                       $xposicao = "null";

				if ($login_fabrica == 91 && !empty($xpeca)) {
                    $sql_depara = "SELECT de, para, peca_de, peca_para
								   FROM tbl_depara
								   WHERE fabrica = $login_fabrica
								   AND (expira IS NULL OR CURRENT_TIMESTAMP < expira)
								   AND peca_de = $xpeca";
					$res_depara = pg_query($con, $sql_depara);

					if (pg_num_rows($res_depara) > 0) {
						$msg_erro = "Peça ".pg_fetch_result($res_depara, 0, "de")." não disponível, modificada para ".pg_fetch_result($res_depara, 0, "para");
					}
				}

				if($pergunta_qtde_os_item == 't') {
					if ((int)$xqtde<1 && !empty($xpeca) && $login_fabrica <> 140) {
						$msg_erro = "Informe a quantidade para a peça $xpeca";
					}
				}else{
					if (strlen($xqtde) == 0) $xqtde = "1";
				}

				if($login_fabrica == 140){
                    $xqtde = str_replace(",", ".", $xqtde);
                }
				$xpeca_original  = $xpeca;
                $xpeca    = str_replace ("." , "" , $xpeca);
				$xpeca    = str_replace ("-" , "" , $xpeca);
				$xpeca    = str_replace ("/" , "" , $xpeca);
				$xpeca    = str_replace (" " , "" , $xpeca);

                if($login_fabrica == 3){ // HD-2117072
                    $sqlLinha = "SELECT tbl_produto.linha
                                    FROM tbl_produto
                                    JOIN tbl_os ON tbl_os.produto = tbl_produto.produto
                                    WHERE tbl_os.os = $os";
                    $resLinha = pg_query($con, $sqlLinha);

                    if(pg_num_rows($resLinha) > 0){
                        $id_linha = pg_fetch_result($resLinha, 0, 'linha');
                    }

                    if($xservico == 692 AND $id_linha == 623){
                        $sql_aud =" INSERT INTO
                            tbl_os_status(
                                os,
                                status_os,
                                observacao,
                                fabrica_status
                            )VALUES(
                                $os,
                                199,
                                'Intervenção referente ao Serviço: RECARGA DE GÁS',
                                $login_fabrica
                            )";
                        $res_aud = pg_query($con, $sql_aud);
                    }
                }

            ### HD-2181938 / MONTEIRO ###
            if($login_fabrica == 131){
//             echo "Já veio com erro?" .pg_last_error($con);exit;
                if (!empty($xservico) and strlen($xpeca) > 0) {
                    $sql_gera_pedido = "SELECT
                                    tbl_servico_realizado.servico_realizado,
                                    tbl_servico_realizado.troca_de_peca
                                    FROM tbl_servico_realizado
                                    WHERE servico_realizado = $xservico
                                    AND gera_pedido is true
                                    AND fabrica = $login_fabrica";
//                                     exit(nl2br($sql_gera_pedido));
                    $res_gera_pedido = pg_query($con, $sql_gera_pedido);
// echo "Só pra Pressure: ".pg_last_error($con);exit;
                    if(pg_num_rows($res_gera_pedido) > 0){
                        $sql =" INSERT INTO
                            tbl_os_status(
                                os,
                                status_os,
                                observacao,
                                fabrica_status
                            )VALUES(
                                $os,
                                205,
                                'Aguardando aprovação para gerar pedido',
                                $login_fabrica
                            )";
                        $res = pg_query($con, $sql);
                    }
                }
            }

				/*(HD 159888 - esmaltec) (HD 384011 - atlas) controle de estoque - Verifica estoque antes de salvar*/
				if (in_array($login_fabrica,array(30,74)) and  empty($msg_erro) and strlen($xpeca) > 0 and $controla_estoque == 't') {

					$qtde_total_estoque = 0;
					if($login_fabrica == 30 && strlen($xkit_peca) > 0){
                        $xsql_peca = "
                            SELECT  peca                    AS peca_kit             ,
                                    tbl_peca.referencia     AS peca_kit_referencia  ,
                                    tbl_kit_peca_peca.qtde  AS peca_kit_qtde
                            FROM    tbl_peca
                            JOIN    tbl_kit_peca_peca   USING(peca)
                            JOIN    tbl_kit_peca        USING (kit_peca)
                            WHERE   tbl_kit_peca_peca.kit_peca  = $xkit_peca
                            AND     tbl_kit_peca.fabrica        = $login_fabrica
                        ";
                        $xres_peca = pg_query($con, $xsql_peca);
                        if(pg_num_rows($xres_peca) > 0){
                            for($px = 0; $px < pg_num_rows($xres_peca); $px++){
                                $peca_kit               = pg_fetch_result($xres_peca,$px,peca_kit);
                                $peca_kit_referencia    = pg_fetch_result($xres_peca,$px,peca_kit_referencia);
                                $peca_kit_qtde          = pg_fetch_result($xres_peca,$px,peca_kit_qtde);

                                $sqlAN = "  SELECT  SUM(qtde) AS qtde
                                            FROM    tbl_estoque_posto
                                            WHERE   posto   = $login_posto
                                            AND     peca    = $peca_kit
                                            AND     fabrica = $login_fabrica";
                                $resAN = pg_query($con, $sqlAN);
                                if (pg_num_rows($resAN) > 0) {
                                    $qtde_total_estoque  = pg_fetch_result($resAN,0,'qtde');
                                }
                                if ($qtde_total_estoque < ($peca_kit_qtde * $xqtde)) {

                                    $sqls = "   SELECT  servico_realizado
                                                FROM    tbl_servico_realizado
                                                WHERE   servico_realizado = $xservico
                                                AND     troca_de_peca IS TRUE";
                                    $ress = pg_query($con, $sqls);
                                    if(pg_num_rows($ress) > 0 ){
                                        $msg_erro = "Quantidade da peça $peca_kit_referencia no kit é maior do que o estoque atual <br />";
                                    }
                                }
                            }
                        }
					}else{
                        $xsql_peca = "SELECT peca FROM tbl_peca where referencia = '$xpeca' and fabrica = $login_fabrica";
                        $xres_peca = pg_query($con, $xsql_peca);

                        $xerro = pg_last_error();

                        if (pg_num_rows($xres_peca) > 0) {

                            $peca_estoque = pg_fetch_result($xres_peca,0,'peca');

                            if($login_fabrica == 74){
                                $cond_servico = " AND peca_estoque IS TRUE ";
                                $cond_estoque = " AND tipo = 'pulmao' ";
                            }
                            $sqlAN = "SELECT SUM(qtde) AS qtde
                                        FROM tbl_estoque_posto
                                        WHERE posto = $login_posto
                                        AND peca    = $peca_estoque
                                        AND fabrica = $login_fabrica";
                            $resAN = pg_query($con, $sqlAN);

                            if (pg_num_rows($resAN) > 0) {
                                $qtde_total_estoque  = pg_fetch_result($resAN,0,'qtde');
                            }


                            if ($qtde_total_estoque < $xqtde) {

                                $sqls = "SELECT servico_realizado
                                    FROM tbl_servico_realizado
                                    WHERE servico_realizado = $xservico
                                    AND troca_de_peca $cond_servico";
                                $ress = pg_query($con, $sqls);
                                if(pg_num_rows($ress) > 0 ){
                                    $msg_erro = "Quantidade inserida para a peça $xpeca é maior do que o estoque atual <br />";
                                }
                            }

                        } else {
                            $msg_erro = 'Peça não encontrada! <br />';
                        }
                    }
				}
				#HD 14504

                if (strlen ($xos_produto) > 0 AND strlen($xpeca) == 0 AND strlen($msg_erro) == 0) {

                    if (strlen ($xos_produto) > 0){
						// $sql = "DELETE FROM tbl_os_produto
								// USING  tbl_os_item
								// WHERE  tbl_os_produto.os         = $os
								// AND    tbl_os_produto.os_produto = tbl_os_item.os_produto
								// AND    tbl_os_item.pedido           IS NULL
								// AND    tbl_os_item.liberacao_pedido IS false
								// ";
						// $sql = "DELETE FROM tbl_os_produto
								// USING  tbl_os_item
								// WHERE  tbl_os_produto.os            = $os
								// AND    tbl_os_produto.os_produto    = tbl_os_item.os_produto
								// AND    tbl_os_produto.os_produto    = $xos_produto
								// AND    tbl_os_item.pedido           IS NULL
								// AND    tbl_os_item.liberacao_pedido IS false
								// ;";
						// flush();
						// #HD 15489
						$sql = "UPDATE tbl_os_produto SET
									os = 4836000
								FROM   tbl_os_item
								WHERE  tbl_os_produto.os            = $os
								AND    tbl_os_produto.os_produto    = tbl_os_item.os_produto
								AND    tbl_os_produto.os_produto    = $xos_produto
								AND    tbl_os_item.pedido           IS NULL
								AND    tbl_os_item.liberacao_pedido IS false";


						$res = pg_query ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}

				} else {

					if (strlen($xpeca) > 0 AND strlen($msg_erro) == 0) {

						$xpeca = strtoupper($xpeca);

						if (strlen ($produto) == 0) {

							$sql = "SELECT tbl_os.produto
									FROM   tbl_os
									WHERE  tbl_os.os      = $os
									AND    tbl_os.fabrica = $login_fabrica;";
							$res = pg_query ($con,$sql);
// echo "Antes do erro: ".pg_last_error($con);exit;
							$msg_erro .= pg_errormessage($con);

							if (pg_num_rows($res) > 0) {
								$produto = pg_fetch_result ($res,0,0);
							}
						}else{

							$sqlPr = "SELECT tbl_produto.produto
										FROM  tbl_produto
										JOIN  tbl_linha USING (linha)
										JOIN  tbl_os    USING (produto)
										WHERE tbl_os.os = $os
										AND   tbl_linha.fabrica = $login_fabrica;";

                            $resPr     = @pg_query($con,$sqlPr);
							$msg_erro .= pg_errormessage($con);

							if(@pg_num_rows($resPr)>0){
								$produto = pg_fetch_result($resPr,0,produto);
							} else {
								$msg_erro  .= "Produto $produto não cadastrado";
								$linha_erro = $i;
							}
						}

                        if ($login_fabrica == 1 && intval($xqtde) > 0 && strlen($msg_erro) == 0) {
                            $sql_ativo = "SELECT ativo
                                            FROM   tbl_peca
                                            WHERE  UPPER(tbl_peca.referencia_pesquisa) = UPPER('$xpeca')
                                            AND    tbl_peca.fabrica = $login_fabrica;";

                            $res_ativo = pg_query($con, $sql_ativo);
                            $ativo_p = pg_fetch_result($res_ativo, 0, ativo);

                            if ($ativo_p == 'f') {
                                if (!pecaInativaBlack($xpeca,$xqtde)) {
                                    $msg_erro .= " O item $xpeca não contém estoque ou o estoque é inferior o solicitado! ";
                                }

                            }
                        }


						if (in_array($login_fabrica,array(3,24,30,91))) {

		                    $xsqlKitPeca = "SELECT tbl_lista_basica.somente_kit
		                            FROM tbl_peca
		                            JOIN tbl_lista_basica on tbl_peca.peca = tbl_lista_basica.peca and tbl_lista_basica.produto = $produto
		                            JOIN tbl_kit_peca_peca  on tbl_peca.peca = tbl_kit_peca_peca.peca
		                            JOIN tbl_kit_peca_produto on tbl_lista_basica.produto = tbl_kit_peca_produto.produto and tbl_kit_peca_produto.produto = $produto
		                            JOIN tbl_kit_peca ON tbl_kit_peca.kit_peca = tbl_kit_peca_produto.kit_peca
		                            WHERE tbl_peca.referencia = '".$xpeca."'
		                            AND tbl_peca.fabrica = $login_fabrica
		                            AND tbl_lista_basica.fabrica = $login_fabrica
		                            ";

		                    $xresKitPeca = pg_query($con,$xsqlKitPeca);

		                    if (strlen($xkit_peca) == 0) {
                                if (pg_fetch_result($xresKitPeca, 0, 0) == 't') {
                                    # verifica se a peça do post ja foi gravada

                                    $verificaPecaGravada = "SELECT count(tbl_os_item.peca) as qtde_item_os,
                                                                    tbl_lista_basica.qtde as qtde_permitida
                                                            FROM tbl_os_item
                                                            JOIN tbl_os_produto using(os_produto)
                                                            JOIN tbl_os using(os)
                                                            JOIN tbl_peca using(peca)
                                                            JOIN tbl_lista_basica on tbl_os_produto.produto = tbl_lista_basica.produto AND
                                                                    tbl_peca.peca = tbl_lista_basica.peca where os = {$os} AND
                                                                    tbl_os.fabrica = {$login_fabrica} AND tbl_peca.referencia = '{$xpeca}'
                                                            GROUP by tbl_lista_basica.qtde ";
                                    $resVerificaPecaGravada = pg_query($verificaPecaGravada);

                                    if(pg_num_rows($resVerificaPecaGravada) > 0){
                                        $qtd_item_os = pg_fetch_result($resVerificaPecaGravada,0,"qtde_item_os");
                                        $qtd_permitida = pg_fetch_result($resVerificaPecaGravada,0,"qtde_permitida");

                                        if($qtd_item_os != $qtd_permitida){
                                            $msg_erro = "Existem peças obrigatórias de kit lançados na OS fora do KIT, por favor utilize a lupa. Referência: $xpeca";
                                        }
                                    }else{
                                        $msg_erro = "Existem peças obrigatórias de kit lançados na OS fora do KIT, por favor utilize a lupa. Referência: $xpeca";
                                    }
                                }
                            }
		                }
// 		                echo $msg_erro;exit;
		                if (empty($msg_erro)) {
                            if (in_array($login_fabrica,array(3,24,30,91)) and strlen($xkit_peca) > 0 and empty($msg_erro) and !pg_num_rows($xresKitPeca)) {//HD 258901
                                if ($login_fabrica != 91) {
                                    if (strlen($xdefeito) == 0) $msg_erro .= "Favor informar o defeito da peça<br />";
                                    if (strlen($xservico) == 0) $msg_erro .= "Favor informar o serviço realizado<br />";
                                }
	                                if (strlen($xos_produto) >  0) {

	                                    $sql = "UPDATE tbl_os_produto SET
	                                                os = 4836000
	                                            WHERE os         = $os
	                                            AND   os_produto = $xos_produto";
	                                    $res = pg_query($con, $sql);
	                                    if (pg_last_error()) {
	                                        $msg_erro .= pg_errormessage($con);//echo $msg_erro;
	                                    }

	                                }

	                                if (strlen($msg_erro) == 0) {


	                                    $sql = "SELECT  tbl_peca.peca,
                                                        tbl_peca.referencia ,
                                                        tbl_peca.descricao
	                                            FROM    tbl_kit_peca_peca
	                                            JOIN   tbl_peca USING(peca)
	                                            WHERE  fabrica = $login_fabrica
	                                             AND   kit_peca = $xkit_peca
	                                             ORDER BY tbl_peca.peca";

	                                    $res = pg_query($con, $sql);
	                                    if (pg_num_rows($res) > 0) {

	                                       $sqlx = "INSERT INTO tbl_os_produto (
	                                                        os     ,
	                                                        produto
	                                                    )VALUES(
	                                                        $os     ,
	                                                        $produto
	                                                );";
	                                        $resx      = pg_query($con, $sqlx);
	                                        if (pg_last_error()) {
	                                            $msg_erro .= pg_errormessage($con);//echo $msg_erro;
	                                        }

	                                        $resx        = pg_query($con, "SELECT CURRVAL('seq_os_produto')");
	                                        $xos_produto = pg_fetch_result($resx,0,0);

	                                        for ($xx = 0; $xx < pg_num_rows($res); $xx++) {

	                                            $xxpeca            = pg_fetch_result($res, $xx, 'peca');
	                                            $pecaKitRef[$xx]   = pg_fetch_result($res, $xx, referencia);
                                                $pecaKitDesc[$xx]  = pg_fetch_result($res, $xx, descricao);

	                                            $kit_peca_peca = $_POST['kit_peca_'.$xxpeca];
	                                            $kit_peca_qtde = $_POST['kit_peca_qtde_'.$xxpeca];

	                                            if (strlen($kit_peca_peca) > 0) {

	                                            	$pa = array("kit_peca" => $xkit_peca);
	                                            	$pa = json_encode($pa);
                                                    $paLogKit = "kit_peca: $xkit_peca";

                                                    $campo_devolucao_obrigatoria = '';
                                                    $valor_devolucao_obrigatoria = '';
                                                    for ($kit_n = 0; $kit_n < $kit_peca_qtde; $kit_n++) {

                                                        if ($login_fabrica == 91){

                                                            $sqlOBG = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE peca = $xxpeca AND fabrica = $login_fabrica";
                                                            $resOBG = pg_query($con,$sqlOBG);
                                                            $devolucao_obrigatoria = pg_fetch_result($resOBG, 0, 'devolucao_obrigatoria');
                                                            $devolucao_obrigatoria = ($devolucao_obrigatoria == "t" AND $troca_de_peca == 't') ? $devolucao_obrigatoria : "f";
                                                            $campo_devolucao_obrigatoria = ", peca_obrigatoria ";
                                                            $valor_devolucao_obrigatoria = ", '$devolucao_obrigatoria' ";

                                                            $kit_fornecedor = $_POST['kit_fornecedor_'.$xxpeca];
                                                            $kit_defeito    = $_POST['kit_defeito_'.$xxpeca];
                                                            $xdefeito       = (empty($kit_defeito)) ? $xdefeito : $kit_defeito;
                                                            $xfornecedor    = (empty($kit_fornecedor)) ? $xfornecedor : $kit_fornecedor;
                                                        }

	                                                    $sqlx = "INSERT INTO tbl_os_item (
	                                                                    os_produto            ,
	                                                                    peca                  ,
	                                                                    qtde                  ,
	                                                                    defeito               ,
                                                                        fornecedor            ,
                                                                        servico_realizado     ,
                                                                        admin                 ,
                                                                        liberacao_pedido ,
                                                                        parametros_adicionais

                                                                        $campo_devolucao_obrigatoria
	                                                                )VALUES(
	                                                                    $xos_produto          ,
	                                                                    $xxpeca               ,
	                                                                    1                     ,
	                                                                    $xdefeito             ,
                                                                        $xfornecedor          ,
                                                                        $xservico             ,
                                                                        $xadmin_peca,
                                                                        '$gera_pedido',
                                                                        '$pa'
                                                                        $valor_devolucao_obrigatoria
	                                                            )";

	                                                    $resx      = pg_query($con,$sqlx);

	                                                    if (pg_last_error()) {
	                                                        $msg_erro .= pg_errormessage($con);//echo $msg_erro;
	                                                    }

                                                        if (!pg_last_error() && $login_fabrica == 91) {
                                                            $auditorLogSaved["ANTES_INSERT"][] = array(
                                                                    "os_produto" => '',
                                                                    "posicao" => '',
                                                                    "peca" => '',
                                                                    "qtde" => '',
                                                                    "defeito" =>  '',
                                                                    "causa_defeito" => '',
                                                                    "servico_realizado" =>  '',
                                                                    "admin" => '',
                                                                    "peca_causadora" =>  '',
                                                                    "parametros_adicionais" => '',
                                                                    "peca_obrigatoria" => '',
                                                                    "fornecedor" => '');
                                                            $auditorLogSaved["OS"][] = $os;
                                                            $auditorLogSaved["INSERT"][] = array(
                                                                    "os_produto" => $xos_produto,
                                                                    "posicao" => '',
                                                                    "peca" => $xxpeca,
                                                                    "qtde" => $kit_peca_qtde,
                                                                    "defeito" => $xdefeito,
                                                                    "causa_defeito" => '',
                                                                    "servico_realizado" => $xservico,
                                                                    "admin" => $xadmin_peca,
                                                                    "peca_causadora" => '',
                                                                    "parametros_adicionais" => $paLogKit,
                                                                    "peca_obrigatoria" => $devolucao_obrigatoria,
                                                                    "fornecedor" => $xfornecedor,
                                                            );
                                                        }
	                                                }

	                                                if($login_fabrica == 30){
                                                        $msg = "Foram adicionadas as peças na OS: <br />";
                                                        for ($xx = 0; $xx < pg_num_rows($resPecaKit); $xx++) {
                                                            $msg .= $pecaKitRef[$xx]." - ".$pecaKitDesc[$xx];
                                                        }

                                                        $sql = "
                                                            INSERT INTO tbl_os_interacao (
                                                                programa,
                                                                os,
                                                                data,
                                                                comentario,
                                                                interno,
                                                                fabrica,
                                                                admin
                                                            ) VALUES (
                                                                '$programa_insert',
                                                                $os,
                                                                CURRENT_TIMESTAMP,
                                                                '$msg',
                                                                TRUE,
                                                                $login_fabrica,
                                                                $login_admin
                                                            );
                                                        ";
                                                        $res = pg_query($con,$sql);
                                                    }

	                                            }
                                            }

	                                    }

	                                }

                                } else {

                                    if (strlen($xos_produto) == 0) {

                                        $sql = "INSERT INTO tbl_os_produto (
                                                    os     ,
                                                    produto,
                                                    serie
                                                ) VALUES (
                                                    $os     ,
                                                    $produto,
                                                    '$serie'
                                                );";
                                        //     exit($sql);
                                        $res = @pg_query($con,$sql);
                                        $msg_erro .= pg_errormessage($con);
                                        // exit($msg_erro);
                                        // $actionProduto = "INSERT";

                                        $res = @pg_query($con,"SELECT CURRVAL ('seq_os_produto')");
                                        $xos_produto  = @pg_fetch_result($res,0,0);

                                    } else {

                                        $sql = "UPDATE tbl_os_produto SET
                                                    produto = $produto,
                                                    serie   = '$serie'
                                                WHERE os_produto = $xos_produto;";

                                        $res = @pg_query($con,$sql);
                                        $msg_erro .= pg_errormessage($con);

                                        // if ($i == 0) {
                                        //     $audProd->retornaDadosSelect(
                                        //         "SELECT DISTINCT
                                        //                 tbl_os.os,
                                        //                 tbl_os.causa_defeito,
                                        //                 tbl_os_produto.produto,
                                        //                 tbl_os.serie
                                        //         FROM    tbl_os_produto
                                        //         JOIN    tbl_os USING(os)
                                        //         WHERE   tbl_os.os = {$os}"
                                        //     );
                                        // }
                                        // $actionProduto = "UPDATE";

                                    }

                                    $sql = "SELECT  tbl_peca.peca,
                                                    tbl_peca.descricao,
                                                    tbl_peca.bloqueada_garantia
                                            FROM    tbl_peca
                                            WHERE  (upper(tbl_peca.referencia_pesquisa) = upper('$xpeca') and
                                                upper(tbl_peca.referencia) = upper('$xpeca_original'))
                                            AND    tbl_peca.fabrica                    = $login_fabrica;";

                                    $res = @pg_query($con,$sql);

                                    if (@pg_num_rows($res) == 0) {
                                        $msg_erro.= "Peça $xpeca não cadastrada";
                                        $linha_erro = $i;
                                    } else {
                                        $xpeca = pg_fetch_result ($res,0,peca);
                                        $bloqueada_garantia = pg_fetch_result($res, 0, "bloqueada_garantia");

                                    }

                                    if($login_fabrica == 87 AND !empty($xpeca_causadora)){
                                        $sql = "SELECT
                                                    tbl_peca.peca
                                                FROM
                                                    tbl_peca
                                                WHERE upper(tbl_peca.referencia_pesquisa) = upper('$xpeca_causadora')
                                                    AND tbl_peca.fabrica = $login_fabrica;";

                                        $res = @pg_query($con,$sql);

                                        if (@pg_num_rows($res) == 0) {
                                            $msg_erro.= "Peça causadora $xpeca_causadora não cadastrada";
                                            $linha_erro = $i;
                                        } else {
                                            $xpeca_causadora = pg_fetch_result ($res,0,peca);
                                        }
                                    }else{
                                        $xpeca_causadora = 'null';
                                    }

								#HD 13433 - Só para ver se a peça gera pedido e atualizar o status de intervenção
								if (strlen($xservico)>0 AND $login_fabrica == 3) {

									$sql = "SELECT servico_realizado
											FROM tbl_servico_realizado
											WHERE fabrica         = $login_fabrica
											AND servico_realizado = $xservico
											AND gera_pedido       IS TRUE
											AND troca_de_peca     IS TRUE;";

									$res = @pg_query ($con,$sql);
									$msg_erro .= pg_errormessage($con);

									if (@pg_num_rows ($res) > 0) {
										$peca_alterada = "sim";
									}

								}

                                if (strlen($xdefeito) == 0 AND $login_fabrica <> 87) {

									if ($login_fabrica == 50 or $login_fabrica == 91) {
										$msg_erro .= "Sem defeito cadastrado";
									} else {
										$msg_erro .= "Favor informar o defeito da peça";

									}

									if($login_fabrica == 52){
	                                    if(!empty($_POST['nome_tecnico']) && !empty($_POST['rg_tecnico'])){

	                                        $sqlTecnico = "UPDATE tbl_os_extra SET tecnico = '".$_POST['nome_tecnico']."|".$_POST['rg_tecnico']."' WHERE os = ".$os." ";
	                                        $res = pg_query($con,$sqlTecnico);

	                                    }else{
	                                        $msg_erro .= "Você deve preencher os campos \"Nome do técnico\" e \"RG do técnico\"";
	                                    }
	                                }


								}else{
	                                if($login_fabrica == 87)
	                                    $xdefeito = 'null';
	                            }

								if (strlen($xservico) == 0 AND !in_array($login_fabrica, array(20,87)) and empty($os_troca))
	                                 $msg_erro.= "Favor informar o serviço realizado"; #$servico = "null";
	                            else{
	                                if(in_array($login_fabrica, array(20,87)) or !empty($os_troca))
	                                     $xservico = 'null';
	                            }

								if (strlen($xpcausa_defeito) == 0)
	                                $xpcausa_defeito = "null";

								if (strlen ($msg_erro) == 0) {

                                    if($login_fabrica == 125){
                                        $sql = "SELECT devolucao_obrigatoria
                                                FROM tbl_peca_servico
                                                INNER JOIN tbl_peca ON tbl_peca_servico.peca = tbl_peca.peca
                                                WHERE tbl_peca.peca = $xpeca AND tbl_peca.fabrica = $login_fabrica and tbl_peca_servico.servico_realizado = 10740 ";
                                    }elseif ($login_fabrica == 120 or $login_fabrica == 201) {
                                        $sql = "SELECT
                                                    CASE WHEN lower(tbl_linha.nome) <> 'lavadora'
                                                        THEN 'f'
                                                    ELSE
                                                        (SELECT devolucao_obrigatoria from tbl_peca WHERE peca = $xpeca AND fabrica = $login_fabrica)
                                                    END AS devolucao_obrigatoria
                                                FROM tbl_produto
                                                    JOIN tbl_linha USING(linha)
                                                WHERE tbl_produto.produto = {$produto}";
                                    }else{
                                        $sql = "SELECT devolucao_obrigatoria from tbl_peca WHERE peca = $xpeca AND fabrica = $login_fabrica";
                                    }

                                    $res = pg_query($con,$sql);
									$devolucao_obrigatoria = pg_fetch_result($res, 0, 'devolucao_obrigatoria');
									if(strlen($troca_de_peca) > 0) {
										$devolucao_obrigatoria = ($devolucao_obrigatoria == "t" AND $troca_de_peca == 't') ? $devolucao_obrigatoria : "f";
									}
									$devolucao_obrigatoria = (empty($devolucao_obrigatoria)) ? "f" : $devolucao_obrigatoria ;

                                    if (strlen($xos_item) == 0) {
                                        if($login_fabrica == 30){
                                            // $descricao = pg_fetch_result($res,0,descricao);
                                            $peca_desc_interacao[] = $xdescricao;
                                        }
					                    if ($login_fabrica == 35) {
											$pa = array(
												"po_peca" => utf8_encode($po_peca[$i])
											);

											$pa = json_encode($pa);
										}

                                        if($login_fabrica == 134){
                                            $sql = "SELECT descricao, peca_estoque, ativo
                                                    FROM tbl_servico_realizado
                                                        WHERE servico_realizado = $xservico and fabrica = $login_fabrica ";
                                            $res = pg_query($con, $sql);
                                            $peca_estoque = pg_fetch_result($res, 0, peca_estoque);
                                        }

                                        if($login_fabrica == 134 AND $peca_estoque == 't'){
                                            //Movimentação de estoque
                                            $sqlIns = "INSERT INTO tbl_estoque_posto_movimento(fabrica,posto,os, tipo,peca,data,qtde_saida,obs)
                                                values($login_fabrica,$login_posto,$os,'garantia',$xpeca,CURRENT_DATE,$qtde,'Peça utilizada em Ordem de Serviço')";
                                            $sqlIns = pg_query($con, $sqlIns);
                                            if(strlen(pg_last_error($con))>0){
                                                $msg_erro .= pg_last_error($con);
                                            }

                                            $sqlUpd = "UPDATE tbl_estoque_posto set qtde = qtde - $qtde where fabrica = $login_fabrica AND posto = $login_posto and peca = $xpeca ";
                                            $resUpd = pg_query($con, $sqlUpd);
                                            if(strlen(pg_last_error($con))>0){
                                                $msg_erro .= pg_last_error($con);
                                            }
                                        }

                                        $query_action = "INSERT";

										$sql = "INSERT INTO tbl_os_item (
													os_produto,
													peca,
													peca_causadora,
													posicao,
													qtde,
													defeito,
													causa_defeito,
													servico_realizado,
													parametros_adicionais,
													admin,
                                                    liberacao_pedido,
													peca_obrigatoria,
													fornecedor
												) VALUES (
													$xos_produto,
													$xpeca,
													$xpeca_causadora,
													$xposicao,
													$xqtde,
													$xdefeito,
													$xpcausa_defeito,
													$xservico,
													'{$pa}',
													$xadmin_peca,
                                                    '$gera_pedido',
													'$devolucao_obrigatoria',
													$xfornecedor
												) RETURNING os_item";


										$res = @pg_query ($con,$sql);
										$msg_erro.= pg_errormessage($con);

										$xos_item = pg_fetch_result($res, 0, "os_item");
									} elseif(empty($os_troca)) {

										if($login_fabrica == 35){
                                            $pa = array("po_peca" => utf8_encode($po_peca[$i]));
                                            $pa = json_encode($pa);
										}

                                        //Informações para o Auditor
                                        $sqls = "select os_produto,posicao,peca,qtde,defeito,causa_defeito,
                                                        servico_realizado,admin,peca_causadora,
                                                        peca_obrigatoria,fornecedor
                                                from tbl_os_item where os_item = $xos_item";
                                        $res = @pg_query ($con,$sqls);

                                        $res = pg_fetch_all($res);
                                        $antes = $res[0];

                                        $query_action = "UPDATE";
										$sql = "UPDATE tbl_os_item SET
													os_produto              = $xos_produto    ,
                                                    posicao                 = $xposicao       ,
													peca                    = $xpeca          ,
													qtde                    = $xqtde          ,
													defeito                 = $xdefeito       ,
													causa_defeito           = $xpcausa_defeito,
													servico_realizado       = $xservico       ,
	                                                peca_causadora          = $xpeca_causadora,
													parametros_adicionais   = '{$pa}',
													peca_obrigatoria        = '$devolucao_obrigatoria',
													fornecedor              = $xfornecedor
												WHERE os_item = $xos_item;";

                                        $res = @pg_query ($con,$sql);
										$msg_erro .= pg_errormessage($con);
									}


                                    if (strlen ($msg_erro) > 0) {
										break ;
									}
                                }
							}
						}
					}
				}

				if($login_fabrica == 134){
					include "../regra_controle_estoque_colormaq.php";
				}

                /*
                Fabrica: 72 /  Mallory
                CASO O PEDIDO SERA TROCA DE PEÇA ABATE DO ESTOQUE PULMÃO
                */

                if($login_fabrica == 72){
                    include_once "../regra_controle_estoque_mallory.php";
                }
                /* Fim 72 */

                if($login_fabrica == 35){
                    if(strlen($xos_item) > 0){
                        include "../regra_controle_estoque_cadence.php";
                    }
                }

				if($login_fabrica == 74){
					$login_posto = $posto;
					include "../regra_controle_estoque_atlas.php";

					if(strlen($depara_auditoria) > 0){
                        $sqlPro = " SELECT  os
                                    FROM    tbl_os_status
                                    WHERE   os = $os
                                    AND     status_os = 196
                        ";
                        $resPro = pg_query($con,$sqlPro);
                        if(pg_num_rows($resPro) == 0){
                            $sqlIns = "
                                INSERT INTO tbl_os_status(
                                    os              ,
                                    status_os       ,
                                    observacao      ,
                                    fabrica_status
                                ) VALUES (
                                    $os                                                                 ,
                                    196                                                                 ,
                                    'OS em auditoria de peças com datas limite de substituição ATÉ-A PARTIR' ,
                                    $login_fabrica
                                );
                            ";
                            $resIns = pg_query($con,$sqlIns);
                            if(!pg_last_error($con)){
                                require "../class/email/PHPMailer/class.phpmailer.php";
                                require "../class/email/PHPMailer/PHPMailerAutoload.php";

                                $mail = new PHPMailer;

                                $mail->isSMTP();

                                $mail->From         = "suporte@telecontrol.com.br";
                                $mail->FromName     = "Suporte Telecontrol";
                                $mail->AddAddress('aujor@atlas.ind.br','Atlas Fogões');
                                 #$mail->addAddress('william.brandino@telecontrol.com.br', 'Atlas Fogões');

                                $mail->isHTML(true);
                                $mail->Subject      = "OS entrando em auditoria de PEÇAS - $os";
                                $mensagem = "
                                    Prezado(a);
                                    <br /><br />Foi cadastrado uma nova OS - <a href='/assist/os_press.php?os=$os' target='_blank'>{$os}</a>
                                    <br />Onde a mesma entrou em auditoria por ter uma peça cadastrada como ATÉ-A PARTIR com limite de data
                                    <br />Atenciosamente,
                                    <br />Suporte Telecontrol
                                    <br />www.telecontrol.com.br
                                    <br /><b><em>Esta é uma mensagem automática, não responda este e-mail.</em></b>
                                ";

                                $mail->Body = $mensagem;

                                #if(!$mail->send()){
                                #    echo "Erro ao enviar: ".$mail->ErrorInfo;
                                #}
                            }
                        }
                    }
				}
                // Array para Inserir na tbl_auditoria_os, posto aqui para pegar o ID das peças.
                // Foi feito uma CAST int, porque na hora de dar um encode estava adicionando aspas nos valores.  
                if (!empty($xpeca) && $xpeca != 0) {
                    $array_campos_adicionais[] = (int)$xpeca;
                }
			} // end for que grava peças na OS

	if($login_fabrica == 30 && count($peca_desc_interacao) > 0){
                $descPecas = implode(", ",$peca_desc_interacao);
                $msg = "Foram adicionadas as peças: $descPecas";

                $sql = "
                    INSERT INTO tbl_os_interacao (
                        programa,
                        os,
                        data,
                        comentario,
                        interno,
                        fabrica,
                        admin
                    ) VALUES (
                        '$programa_insert',
                        $os,
                        CURRENT_TIMESTAMP,
                        '$msg',
                        TRUE,
                        $login_fabrica,
                        $login_admin
                    );
                ";
                $res = pg_query($con,$sql);
        }
        #echo "FIM ";exit;
        if(strlen($msg_erro) ==0 && $login_fabrica == 15){
            $sql = 'SELECT fn_valida_nova_mascara_ns_latinatec($1,$2,$3);';
            $params = array($os,$login_fabrica,$produto_serie);
            pg_query_params($con,$sql,$params);
            $msg_erro = pg_errormessage($con);
        }

			if($login_fabrica == 134 and strlen(trim($msg_erro))==0){
				$sqlx = "SELECT fn_calcula_os_thermosystem($os,$login_fabrica);";
				$res = pg_query($con,$sqlx);
				$msg_erro .= pg_errormessage($con);
			}

			# HD 925803 - após o FOR executa a ação de LOG para a Fricon
			if( $desc_acao != null and $login_fabrica == 52 ){
				log_os_admin($data_hoje.' - Admin: '.$nome_login, $array, $os, $login_fabrica);
			}


			if ($login_fabrica == 6) { //HD 2599

				$pre_total = $_POST['pre_total'];

				for ($i = 0; $i < $pre_total; $i++) {

					$pre_peca = $_POST['pre_peca_'.$i];

					if (strlen($pre_peca) > 0) {

						$pre_defeito = $_POST['pre_defeito_'.$i];
						$pre_servico = $_POST['pre_servico_'.$i];
						$pre_qtde    = $_POST['pre_qtde_'   .$i];

                        if($login_fabrica <> 87){
                            if (strlen($pre_defeito)== 0) $msg_erro .= "Favor informar o defeito da peça<br />";
						    if (strlen($pre_servico)== 0) $msg_erro .= "Favor informar o serviço realizado<br />";
                        }

						$sql = "select produto from tbl_os where os=$os and fabrica = $login_fabrica";
						$res = pg_query($con,$sql);

						if (pg_num_rows($res)>0){
							$pre_produto = pg_fetch_result($res,0,0);
						}

						if (strlen($msg_erro) == 0) {

								$sql = "INSERT INTO tbl_os_produto (
												os     ,
												produto
											)VALUES(
												$os     ,
												$pre_produto
										);";

									$res = @pg_query($con,$sql);
									$msg_erro .= pg_errormessage($con);

									$res = pg_query($con,"SELECT CURRVAL ('seq_os_produto')");
									$xos_produto  = pg_fetch_result ($res,0,0);

						}

						if (strlen ($msg_erro) == 0) {

								$sql = "INSERT INTO tbl_os_item (
											os_produto        ,
											peca              ,
											qtde              ,
											defeito           ,
											servico_realizado ,
                                            liberacao_pedido,
											admin
										) VALUES (
											$xos_produto    ,
											$pre_peca       ,
											$pre_qtde       ,
											$pre_defeito    ,
											$pre_servico    ,
                                            '$gera_pedido',
											$xadmin_peca
									);";


								$res = @pg_query ($con,$sql);
								$msg_erro .= pg_errormessage($con);

						}

					}

				}

			}//HD 2599

		}

		/* HD 35521 */
		if ($login_fabrica == 3 AND $peca_alterada == 'sim') {

			$sql = "SELECT status_os, observacao
					FROM tbl_os_status
					WHERE os = $os
					AND status_os IN (62,64,65,72,73,116,117)
					ORDER BY data DESC
					LIMIT 1";

			$res = pg_query ($con,$sql);

			if (pg_num_rows ($res) > 0) {

				$ultimo_status_os     = pg_fetch_result($res, 0, 'status_os');
				$ultimo_status_os_obs = pg_fetch_result($res, 0, 'observacao');

				if ($ultimo_status_os == "62" OR $ultimo_status_os == "72" OR $ultimo_status_os == "116") {

					$proximo_status_intervencao = "64";

					if ($ultimo_status_os == "72"){
						$proximo_status_intervencao = "73";
					}

					if ($ultimo_status_os == "116"){
						$proximo_status_intervencao = "117";
					}

					$sql = "INSERT INTO tbl_os_status
							(os,status_os,observacao,admin)
							VALUES
							($os,$proximo_status_intervencao,'Pedido das Peças Autorizado Pela Fábrica',$login_admin)";

					$res = pg_query ($con,$sql);

				}

			}

		}


		if (strlen ($msg_erro) == 0) {
			$valida_os_item = true;

			if (true === $telecontrol_distrib) {
			    $arr_pecas_lancadas = array();

			     for ($i=0; $i<$qtde_item; $i++) {
				if (!empty($_POST['peca_' . $i])) {
				    $peca_x = $_POST['peca_' . $i];
				    $qtde_x = (!empty($_POST['qtde_' . $i])) ? $_POST['qtde_' . $i] : '1';

				    $sql_peca_x = "SELECT peca FROM tbl_peca WHERE referencia = '$peca_x' AND fabrica = $login_fabrica";
				    $res_peca_x = pg_query($con, $sql_peca_x);
				    $peca_id_x = pg_fetch_result($res_peca_x, 0, 'peca');

				    if (!array_key_exists($peca_id_x, $arr_pecas_lancadas)) {
					$arr_pecas_lancadas[$peca_id_x] = (int) $qtde_x;
				    } else {
					$arr_pecas_lancadas[$peca_id_x] += (int) $qtde_x;
				    }
				}
			    }

			    if (!array_diff_assoc($arr_pecas_lancadas, $pecas_os_item)) {
				$valida_os_item = false;
			    }
			}

			if (true === $valida_os_item) {
				$res       = @pg_query($con,"SELECT fn_valida_os_item($os, $login_fabrica)");
				$msg_erro .= pg_errormessage($con);
			}
		}

        if ($login_fabrica == 30) {//HD 27561

			$fogao              = strtoupper(trim($_POST['fogao']));
			$marca_fogao        = strtoupper(trim($_POST['marca_fogao']));

			$refrigerador       = strtoupper(trim($_POST['refrigerador']));
			$marca_refrigerador = strtoupper(trim($_POST['marca_refrigerador']));

			$bebedouro          = strtoupper(trim($_POST['bebedouro']));
			$marca_bebedouro    = strtoupper(trim($_POST['marca_bebedouro']));

			$microondas         = strtoupper(trim($_POST['microondas']));
			$marca_microondas   = strtoupper(trim($_POST['marca_microondas']));

			$lavadoura          = strtoupper(trim($_POST['lavadoura']));
			$marca_lavadoura    = strtoupper(trim($_POST['marca_lavadoura']));

			$escolheu=0;

			if (strlen($fogao) > 0 AND strlen($marca_fogao) == 0) {
				$msg_erro .= "Escolha a marca do fogão";
			}
			if (strlen($fogao) > 0 AND strlen($marca_fogao) > 0) {$escolheu++;}

			if (strlen($refrigerador) > 0 AND strlen($marca_refrigerador) == 0) {
				$msg_erro .= "Escolha a marca do refrigerador";
			}

			if(strlen($refrigerador)>0 AND strlen($marca_refrigerador)>0){$escolheu++;}


			if(strlen($bebedouro)>0 AND strlen($marca_bebedouro)==0){
				$msg_erro .= "Escolha a marca do bebedouro";
			}
			if(strlen($bebedouro)>0 AND strlen($marca_bebedouro)>0){$escolheu++;}


			if(strlen($microondas)>0 AND strlen($marca_microondas)==0){
				$msg_erro .= "Escolha a marca do microondas";
			}
			if(strlen($microondas)>0 AND strlen($marca_microondas)>0){$escolheu++;}


			if(strlen($lavadoura)>0 AND strlen($marca_lavadoura)==0){
				$msg_erro .= "Escolha a marca da lavadoura";
			}
			if(strlen($lavadoura)>0 AND strlen($marca_lavadoura)>0){$escolheu++;}


			if(strlen($msg_erro)==0 AND $escolheu > 0){
				$marcas = $fogao . ";" . $marca_fogao . ";" . $refrigerador . ";" . $marca_refrigerador . ";" . $bebedouro . ";" . $marca_bebedouro . ";" . $microondas . ";" . $marca_microondas . ";" . $lavadoura . ";" . $marca_lavadoura;

				$sqlm = " UPDATE tbl_os_extra SET
								 obs_adicionais = '$marcas'
							WHERE os = $os";
				$resm = pg_query ($con,$sqlm);
				$msg_erro .= pg_errormessage($con);
			}
		}

		//hd 17966
		if ($login_fabrica == 45 and strlen($voltar_fechamento) > 0 AND strlen($voltar_finalizada) > 0) {

			$sql = "UPDATE tbl_os SET data_fechamento = '$voltar_fechamento' , finalizada = '$voltar_finalizada'
					WHERE os      = $os
					AND   fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);

		}

		if($login_fabrica == 19 AND strlen($msg_erro) == 0){
			$produto_serie = trim($_POST['produto_serie']);

			if(strlen($produto_serie) > 0){
				$sql = "UPDATE tbl_os SET serie = '$produto_serie'  WHERE os = $os AND fabrica = $login_fabrica";
				$res = pg_query ($con,$sql);
			}else{
				$sql = "SELECT 	tbl_produto.linha
					FROM 	tbl_os
					JOIN 	tbl_produto on (tbl_os.produto = tbl_produto.produto)
					WHERE 	tbl_os.fabrica = $login_fabrica
					AND     tbl_os.os = $os";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res)>0){
					$produto_linha_lorenzetti = pg_fetch_result($res,0,0);

					if ($produto_linha_lorenzetti == 265){
						$msg_erro = "Número de série obrigatório para este produto";
					}
				}
			}
		}

        if(strlen($msg_erro)==0 AND in_array($login_fabrica, array(3))){
        	if(intval($tecnico) == 0)
        		$tecnico = "null";

            $sql = " UPDATE tbl_os SET tecnico = $tecnico WHERE os = $os";
            $res = pg_query ($con,$sql);
            $msg_erro .= pg_errormessage($con);
        }

        if(strlen($msg_erro)==0 AND in_array($login_fabrica, array(87))){
            $sql = " UPDATE tbl_os_extra SET tecnico = '$tecnico',  qtde_horas = $qtde_horas  WHERE os = $os";
            $res = pg_query ($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = " UPDATE tbl_os SET tecnico = '$tecnico' WHERE os = $os";
            $res = pg_query ($con,$sql);
            $msg_erro .= pg_errormessage($con);
        }

        if(strlen($_POST['custo_extra']) > 0 AND $inf_valores_adicionais){

	        $sql = "SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = $os AND status_os IN(171,172,173) ORDER BY data DESC LIMIT 1";
	        $res = pg_query($con, $sql);
	        if(pg_num_rows($res) > 0){
	            $status_os = pg_fetch_result($res, 0, 'status_os');
	        }
	      $outros_custos = $_POST['outros_custos'];
	      $custo_extra = $_POST['custo_extra'];

	        $valor_adicional_formatado = utf8_encode($_POST['valor_adicional_formatado']);
	        $valor_adicional_formatado = str_replace("\\","",$valor_adicional_formatado);
	        $valor_format = json_decode($valor_adicional_formatado,true);
	        if(count($valor_format[0]) == 0 AND count($valor_format[1]) == 0){
	            $valor_adicional_formatado = "";
	        }

	        if($login_fabrica == 35){

		        $totalValoresAdicionais = 0;

		        foreach($valor_format[1] as $v){
		        	if(strstr($v, ",")){
		        		$v = str_replace(",", ".", $v);
		        	}
		        	$totalValoresAdicionais += $v;
		        }

		        if(!strstr($totalValoresAdicionais, ".")){
	                $totalValoresAdicionais = $totalValoresAdicionais.".00";
	            }

		        $sql = "UPDATE tbl_os SET valores_adicionais = '$totalValoresAdicionais' WHERE os = $os AND fabrica = $login_fabrica";
		        $res = pg_query($con, $sql);

		        $totalValoresAdicionais = "";

		    }

            if ($telecontrol_distrib == "t") {

                if(!empty($valor_adicional_formatado)) {

                    \Posvenda\Helpers\Auditoria::gravar($os, 6, "OS em auditoria de Valores Adicionais", "Em auditoria", $con);

                    $sql = "select * from tbl_os_campo_extra where os = $os";
                    $res = pg_query ($con,$sql);

                    if(pg_num_rows($res) > 0){
                        $sql = "UPDATE tbl_os_campo_extra SET valores_adicionais = '$valor_adicional_formatado' WHERE os = $os";
                    }else{
                        $sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,valores_adicionais) VALUES($os,$login_fabrica,'$valor_adicional_formatado')";
                    }

                    $res = pg_query ($con,$sql);

                }

            } else {

                if(!empty($valor_adicional_formatado) AND $status_os != 171){

                    if($login_fabrica != 35){
                        $sql = "INSERT INTO tbl_os_status (os,status_os,observacao,automatico) VALUES ($os,171,'OS em intervenção de custos adicionais','t')";
                        $res = @pg_query ($con,$sql);
                    }

                    $sql = "select * from tbl_os_campo_extra where os = $os";
                    $res = pg_query ($con,$sql);
                    if(pg_num_rows($res) > 0){
                        $sql = "UPDATE tbl_os_campo_extra SET valores_adicionais = '$valor_adicional_formatado' WHERE os = $os";
                    }else{
                        $sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,valores_adicionais) VALUES($os,$login_fabrica,'$valor_adicional_formatado')";
                    }

                    $res = pg_query ($con,$sql);
                }

            }

	    }

	    if(strlen($msg_erro) == 0){

		if( in_array($login_fabrica, array(3,11,126,137,172)) ){

                    $types = array("png", "jpg", "jpeg", "bmp", "pdf", 'doc', 'docx', 'odt');

                    if($login_fabrica == 126 AND (strlen($_FILES["img_os_1"]["name"]) == 0 AND strlen($_FILES["img_os_2"]["name"]) == 0) ){
                        //$msg_erro .= "Por favor inserir anexo da Nota Fiscal <br />";
                    }

		    foreach ($_FILES as $key => $imagem) {
                      if((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)){
                        if($key == "img_os_item_1" || $key == "img_os_item_2"){
                          $type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
                          if(!in_array($type, $types)){
                            $pathinfo = pathinfo($imagem["name"]);
                            $type = $pathinfo["extension"];
                          }
                          if (!in_array($type, $types)) {

                            $msg_erro .= "Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, doc e pdf";
                            break;

                          } else {

                            if(strlen($os) > 0 ){
                              $fileName = "anexo_os_{$login_fabrica}_{$os}_{$key}";
                            }else{
                              $os_upload = pg_fetch_result($sql, 0, 'os');
                              $fileName = "anexo_os_{$login_fabrica}_{$os_upload}_{$key}";
                            }

                            $amazonTC->upload($fileName, $imagem, "", "");

                            $link = $amazonTC->getLink("$fileName.{$type}", false, "", "");
                          }
                        }
                      }
                    }
                  }

                // FIM Anexa imagem NF

	   }

	    if($login_fabrica == 140){

            if($desc_tipo_atendimento == "Entrega t&eacute;cnica"){

                if($_FILES['laudo_tecnico']['size'] > 0){

                    $s3 = new AmazonTC('inspecao', $login_fabrica);
                    $laudo_tecnico = $_FILES['laudo_tecnico'];

                    $types = array("png", "jpg", "jpeg", "bmp", "pdf");
                    $type  = strtolower(preg_replace("/.+\//", "", $laudo_tecnico["type"]));

                    if (!in_array($type, $types)) {
                        $msg_erro .=  "Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf <br />";
                    }else{
                        $name = $os;
                        $file = $laudo_tecnico;
                        $s3->upload ($name, $file);
                    }

                }/* else{
                    $msg_erro .=  "Por favor, insira o Laudo <br />";
                } */

            }

        }

		if (strlen($msg_erro) == 0) {
			$valida_os_item = true;

			if (true === $telecontrol_distrib) {
			    $arr_pecas_lancadas = array();

			     for ($i=0; $i<$qtde_item; $i++) {
				if (!empty($_POST['peca_' . $i])) {
				    $peca_x = $_POST['peca_' . $i];
				    $qtde_x = (!empty($_POST['qtde_' . $i])) ? $_POST['qtde_' . $i] : '1';

				    $sql_peca_x = "SELECT peca FROM tbl_peca WHERE referencia = '$peca_x' AND fabrica = $login_fabrica";
				    $res_peca_x = pg_query($con, $sql_peca_x);
				    $peca_id_x = pg_fetch_result($res_peca_x, 0, 'peca');

				    if (!array_key_exists($peca_id_x, $arr_pecas_lancadas)) {
					$arr_pecas_lancadas[$peca_id_x] = (int) $qtde_x;
				    } else {
					$arr_pecas_lancadas[$peca_id_x] += (int) $qtde_x;
				    }
				}
			    }

			    if (!array_diff_assoc($arr_pecas_lancadas, $pecas_os_item)) {
				$valida_os_item = false;
			    }
			}

			if (true === $valida_os_item) {
				$sqlv      = "SELECT fn_valida_os_item($os, $login_fabrica)";
				$resv      = @pg_query($con,$sqlv);
				$msg_erro = pg_last_error($con);
			}

               if (strlen($msg_erro) == 0) {
					$sqlB = "UPDATE tbl_os SET status_checkpoint=fn_os_status_checkpoint_os(os)
							WHERE os = $os
							AND	status_checkpoint<>fn_os_status_checkpoint_os(os)";
					$res = pg_query($con,$sqlB);
					$msg_erro .= pg_errormessage($con);
			   }

				if (strlen($data_fechamento) > 0) {
					if (strlen($msg_erro) == 0) {

						$sql = "UPDATE tbl_os SET data_fechamento   = $xdata_fechamento
								WHERE  tbl_os.os    = $os
								AND    tbl_os.posto = $posto;";
						$res = @pg_query ($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
						$res = @pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

					}
				}

                $Posto_SAC = '';
                if ($login_fabrica == 30) {
                    $sql = "SELECT tbl_tipo_posto.descricao FROM tbl_posto_fabrica JOIN tbl_tipo_posto ON(tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto) WHERE tbl_posto_fabrica.fabrica = {$login_fabrica} AND tbl_posto_fabrica.posto = {$login_posto}";
                    $res = pg_query ($con,$sql);

                    $Posto_SAC = pg_fetch_result ($res,0,descricao);
                    if ($Posto_SAC == 'SAC') {
                        $sql = "UPDATE tbl_os SET mao_de_obra = 0 WHERE os = {$os}";
                        $res = pg_query($con,$sql);
                    }
                }
                if ($login_fabrica == 1 && $tipo_atendimento == 334 && $msg_erro == "") {
                    $sqlVerAud = "
                        SELECT  COUNT(1) AS conta_aud
                        FROM    tbl_auditoria_os
                        WHERE   os                  = $os
                        AND     auditoria_status    = 4
                        AND     liberada            IS NOT NULL
                        AND     observacao ILIKE 'Auditoria de Devolu%o de Pe%as'
                    ";
                    $resVerAud = pg_query($con,$sqlVerAud);

                    if (pg_fetch_result($resVerAud,0,conta_aud) == 0) {
                        $gravaAud = true;
                        $sqlReAud = "
                            SELECT  COUNT(1) AS reauditoria_os
                            FROM    tbl_auditoria_os
                            WHERE   os = $os
                            AND     auditoria_status    = 4
                            AND     liberada            IS NULL
                            AND     (
                                        reprovada       IS NOT NULL
                                    OR  auditoria_os    IS NULL
                                    )
                            AND     observacao          ILIKE 'Auditoria de Devolu%o de Pe%as'
                        ";
                        $resReAud       = pg_query($con,$sqlReAud);

                        if (pg_fetch_result($resVerAud,0,reauditoria_os) == 0) {
                            $grava_auditoria = false;
                            $sql_campos_adicionais = "SELECT DISTINCT jsonb_array_elements(campos_adicionais->'peca') AS peca 
                                                      FROM tbl_auditoria_os WHERE os = $os LIMIT 1";
                            $res_campos_adicionais = pg_query($con, $sql_campos_adicionais);
                            if (pg_num_rows($res_campos_adicionais) > 0) { 
                                for ($s=0; $s < pg_num_rows($res_campos_adicionais); $s++) { 
                                    $array_campos_adicionais_auditoria[] = pg_fetch_result($res_campos_adicionais, $s, 'peca');
                                }
                                foreach ($array_campos_adicionais as $peca_array) {
                                    if (!in_array($peca_array,$array_campos_adicionais_auditoria)) {
                                        $grava_auditoria = true;
                                    }
                                }
                            } else {
                                $grava_auditoria = true;
                            }

                            if ($grava_auditoria) {
                                $pecas['peca'] = $array_campos_adicionais;
                                $campos_adicionais_peca = json_encode($pecas);
                                $sqlAud = "
                                    INSERT INTO tbl_auditoria_os (
                                        os,
                                        auditoria_status,
                                        observacao,
                                        paga_mao_obra,
                                        campos_adicionais
                                    ) VALUES (
                                        $os,
                                        4,
                                        'Auditoria de Devolução de Peças',
                                        FALSE,
                                        '$campos_adicionais_peca'
                                    )
                                ";
                                $resAud = pg_query($con,$sqlAud);
                            }
                        }
                    }
                }

				if (strlen($msg_erro) == 0) {
                    $res = pg_query($con,"COMMIT TRANSACTION");

                    if ($telecontrol_distrib) {

                        $sql = "SELECT tbl_os_item.os_item
                                FROM tbl_os
                                JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                                JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                WHERE tbl_os.os = {$os}";
                        $res = pg_query($con, $sql);

                        if (pg_num_rows($res) == 0) {
                            atualiza_status_checkpoint($os, 'Aguardando Analise');
                        }
                        
                    }

                    if ($login_fabrica == 104 && count($pecas_lancadas) > 0) {

                        $helper = new \Posvenda\Helpers\Os();

                        $sql_contatos_consumidor = "
                            SELECT consumidor_email,
                                consumidor_celular,
                                referencia,
                                descricao,
                                tbl_posto.nome
                            FROM tbl_os
                            JOIN tbl_produto USING(produto)
                            JOIN tbl_posto USING(posto)
                            WHERE os = $os";
                        $qry_contatos_consumidor = pg_query($con, $sql_contatos_consumidor);

                        $consumidor_email = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_email');
                        $consumidor_celular = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_celular');
                        $produto_os = pg_fetch_result($qry_contatos_consumidor, 0, 'referencia') . ' - ' . pg_fetch_result($qry_contatos_consumidor, 0, 'descricao');
                        $posto_os = pg_fetch_result($qry_contatos_consumidor, 0, 'nome');

                        $msg_pecas_os = "Produto Vonder. Informamos que para a OS {$os} foi solicitado a(s) peça(s) ";
                        $msg_pecas_os .= implode(", ", $pecas_lancadas);
                        $msg_pecas_os .= " para o conserto do produto $produto_os. Favor Aguardar";

                        if (!empty($consumidor_email)) {
                            $helper->comunicaConsumidor($consumidor_email, $msg_pecas_os);
                        }

                        if (!empty($consumidor_celular)) {
                            $helper->comunicaConsumidor($consumidor_celular, $msg_pecas_os, $login_fabrica, $os);
                        }
                    }

                    $audProd->retornaDadosSelect("SELECT DISTINCT tbl_os_produto.os_produto,
                                                tbl_os.os,
                                                tbl_os.causa_defeito,
                                                tbl_os_produto.produto,
                                                tbl_os.serie
                                        FROM    tbl_os_produto
                                        JOIN    tbl_os USING(os)
                                        WHERE   tbl_os.os = {$os}")
                        ->enviarLog($query_action,'tbl_os_produto',$login_fabrica."*".$os);

                    $audItem->retornaDadosSelect("SELECT tbl_os_item.os_item, tbl_os_item.peca,
                                                        tbl_os_item.qtde,
                                                        tbl_os_item.servico_realizado,
                                                        tbl_os_item.defeito,
                                                        tbl_os_item.parametros_adicionais,
                                                        tbl_os_item.admin,
                                                        tbl_os_item.peca_obrigatoria as devolucao_obrigatoria,
                                                        tbl_os_item.fornecedor
                                                FROM    tbl_os_item
                                                JOIN    tbl_os_produto  USING(os_produto)
                                                JOIN    tbl_os          USING(OS)
                                                WHERE   tbl_os.os = $os")
                        ->enviarLog($query_action,'tbl_os_item',$login_fabrica."*".$os);

                    $audOs->retornaDadosSelect("SELECT os,
                                defeito_reclamado,
                                defeito_constatado,
                                solucao_os as solucao,
                                obs
                                from tbl_os
                                where os = {$os}")->enviarLog($query_action,'tbl_os',$login_fabrica."*".$os);


                    if (!empty($auditorLogSaved["INSERT"])) {
                        foreach ($auditorLogSaved['ANTES_INSERT'] as $key => $value) {
                            auditorLog($auditorLogSaved["OS"][$key],$value,$auditorLogSaved["INSERT"][$key], 'tbl_os_item', 'admin/os_item.php', "INSERT");
                        }
                    }

                    if (!empty($auditorLogSaved["UPDATE"])) {
                        foreach ($auditorLogSaved['ANTES_UPDATE'] as $key => $value) {
                            auditorLog($auditorLogSaved["OS"][$key],$value,$auditorLogSaved["UPDATE"][$key], 'tbl_os_item', 'admin/os_item.php', "UPDATE");
                        }
                    }

                    if (!empty($auditorLogSavedDelete)) {
                        foreach ($auditorLogSavedDelete['ANTES'] as $key => $value) {
                            auditorLog($auditorLogSavedDelete["OS"][$key],$value,$auditorLogSavedDelete["DELETE"][$key], 'tbl_os_item', '/admin/os_item.php', "DELETE");
                        }
                    }

				}else {
					$res = pg_query ($con,"ROLLBACK TRANSACTION");
				}

                if (strlen($os) > 0) {
                    $sql_os_depois = "select * from tbl_os where os = $os and fabrica = $login_fabrica";
                    $res_os_depois = pg_query($con, $sql_os_depois);
                    if(pg_num_rows($res_os_depois)>0){
                        $dados_os_depois = pg_fetch_assoc($res_os_depois);
                    }

                    $sql_item_depois = "select tbl_os_item.* from tbl_os_produto
                    join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.produto
                    where os = $os and fabrica_i = $login_fabrica";
                    $res_item_depois = pg_query($con, $sql_item_depois);
                    if(pg_num_rows($res_item_depois)>0){
                        $dados_item_depois = pg_fetch_assoc($res_item_depois);
                    }
                }

                 if(in_array($login_fabrica,array(30,72))){
                    $msg .= "Houve alteração nas peças da O.S $os: \n";

                    foreach($_POST as $indice => $valor){

                        $procurado   = '_anterior';
                        $pos = strpos($indice, $procurado);

                        if((int)$pos > 0){

                            $anterior       = $_POST["$indice"];
                            $indice_atual   = str_replace("_anterior", "", $indice);
                            $atual          = $_POST["$indice_atual"];
                            //echo "indice_anterior = $indice --". $_POST["$indice"]."<Br>";
                            //echo "indice_atual = $indice_atual --". $_POST["$indice_atual"]."<Br>";

                            if($indice == "defeito_reclamado_descricao_anterior"){
                                $msg .= "DEFEITO RECLAMADO DESCRICAO: "." De ".$_POST["$indice"]." para ".$_POST["$indice_atual"]."\n";
                            }

                            if(substr($indice, 0, 9) == "descricao"){
                                $item_descricao = strtoupper(str_replace("_", " ", $indice_atual));

                                if($_POST["$indice"] != $_POST["$indice_atual"]){
                                    $msg .= $item_descricao." De ".$_POST["$indice"]." para ".$_POST["$indice_atual"]."\n";
                                }
                            }
                        }
                    }

                    $diferenca_os = array_diff($dados_os_antes, $dados_os_depois);

                    foreach($diferenca_os as $indice=>$valor){
                        $valor_de       = $dados_os_antes["$indice"];
                        $valor_para     = $dados_os_depois["$indice"];

                        $retirar = array('defeito_constatado');

                        if (in_array($indice, $retirar)){
                            continue;
                        }

                        if($indice == "data_modificacao"){
                            $valor_de       = mostra_data_hora($dados_os_antes["$indice"]);
                            $valor_para     = mostra_data_hora($dados_os_depois["$indice"]);
                        }

                        if($valor_de == "t"){
                            $valor_de = "Sim";
                        }elseif($valor_de == "f"){
                            $valor_de = "Não";
                        }

                        if($valor_para == "t"){
                            $valor_para = "Sim";
                        }elseif($valor_para == "f"){
                            $valor_para = "Não";
                        }

                        $indice_limpo = str_replace("_", " ", $indice);
                        $msg .= strtoupper($indice_limpo) . " de ". $valor_de . " para " . $valor_para . "\n";
                    }

                    $posto_emails = $dados_os_depois['posto'];

					$sql_email = "select contato_email,tbl_fabrica.nome
								from tbl_posto_fabrica
								join tbl_fabrica using(fabrica)
                                where posto = $posto_emails and tbl_posto_fabrica.fabrica = $login_fabrica";
                    $res_email = pg_query($con, $sql_email);
                    if(pg_num_rows($res_email)>0){
                        $contato_email = pg_fetch_result($res_email, 0, 'contato_email');
                        $nome = pg_fetch_result($res_email, 0, 'nome');
                        $nome = strtoupper($nome);
                    }

                    //Envia email para o posto.
                    $assunto   = 'Alteração Cadastro de Os '. $nome;
                    $headers = 'From: helpdesk@telecontrol.com.br' . "\r\n" .
                    'Reply-To: helpdesk@telecontrol.com.br' . "\r\n" .
                    'X-Mailer: PHP/';

                    mail($contato_email, $assunto, $msg, $headers);
                }
                //funcao de auditor -- todas as empresas
                auditorLog($os,$dados_os_antes,$dados_os_depois,"tbl_os",$PHP_SELF,'update');

                if ($login_fabrica == 1 && $_REQUEST['shadowbox'] == 't') { ?>
                    <script>
                        alert("OS alterada com sucesso!");
                        window.parent.location.reload();
                        window.parent.Shadowbox.close();
                    </script>
                <?php
                } else {

                    echo "<meta http-equiv='refresh' content='0;url=os_press.php?os=$os'>";# ("Location: os_finalizada.php?os=$os");
                }
			exit;
		} else {
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if (strlen($os) > 0) {

	#----------------- Le dados da OS --------------
	$sql = "SELECT  tbl_os.*,
			tbl_produto.referencia,
			tbl_produto.descricao ,
			tbl_produto.linha, tbl_os_extra.tecnico as info_tecnico
		FROM    tbl_os
		LEFT JOIN tbl_produto USING (produto)
		JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
		WHERE   tbl_os.os = $os";

	$res = pg_query ($con,$sql) ;

	$defeito_constatado = pg_fetch_result ($res,0,defeito_constatado);
	$causa_defeito      = pg_fetch_result ($res,0,causa_defeito);
	$linha              = pg_fetch_result ($res,0,linha);
	$consumidor_nome    = pg_fetch_result ($res,0,consumidor_nome);
	$sua_os             = pg_fetch_result ($res,0,sua_os);
	$produto_os         = pg_fetch_result ($res,0,produto);
	$produto_referencia = pg_fetch_result ($res,0,referencia);
	$produto_descricao  = pg_fetch_result ($res,0,descricao);
	$produto_serie      = pg_fetch_result ($res,0,serie);
	$qtde_produtos      = pg_fetch_result ($res,0,qtde_produtos);
	$posto              = pg_fetch_result ($res,0,posto);
	$obs                = pg_fetch_result ($res,0,obs);
	$solucao_os         = pg_fetch_result ($res,0,solucao_os);

	//HD-896985
	if($login_fabrica == 52){

		$tecnico        = pg_fetch_result($res,0,info_tecnico);

		$explodeTecnico = explode("|", $tecnico);

		$nome_tecnico = $explodeTecnico[0];

		$rg_tecnico = $explodeTecnico[1];

	}
	//HD-896985

    $Posto_SAC = '';
    if ($login_fabrica == 30) {
        $sql = "SELECT tbl_tipo_posto.descricao FROM tbl_posto_fabrica JOIN tbl_tipo_posto ON(tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto) WHERE tbl_posto_fabrica.fabrica = {$login_fabrica} AND tbl_posto_fabrica.posto = {$login_posto}";
        $res = pg_query ($con,$sql);

        $Posto_SAC = pg_fetch_result ($res,0,descricao);
    }
}

#---------------- Carrega campos de configuração da Fabrica -------------
$sql = "SELECT  tbl_fabrica.os_item_subconjunto   ,
				tbl_fabrica.pergunta_qtde_os_item ,
				tbl_fabrica.os_item_serie         ,
				tbl_fabrica.os_item_aparencia     ,
				tbl_fabrica.qtde_item_os
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica;";
$resX = pg_query ($con,$sql);

if (pg_num_rows($resX) > 0) {
	$os_item_subconjunto = pg_fetch_result($resX,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';

	$pergunta_qtde_os_item = pg_fetch_result($resX,0,pergunta_qtde_os_item);
	if (strlen ($pergunta_qtde_os_item) == 0) $pergunta_qtde_os_item = 'f';

	$os_item_serie = pg_fetch_result($resX,0,os_item_serie);
	if (strlen ($os_item_serie) == 0) $os_item_serie = 'f';

	$os_item_aparencia = pg_fetch_result($resX,0,os_item_aparencia);
	if (strlen ($os_item_aparencia) == 0) $os_item_aparencia = 'f';

	if ($login_fabrica == 74) {
		if (!isset($_POST["qtde_item"])) {
			$sql_os_item = "SELECT COUNT(*) AS total FROM tbl_os_item JOIN tbl_os_produto USING(os_produto) WHERE tbl_os_item.fabrica_i = $login_fabrica AND tbl_os_produto.os = $os";
			$res_os_item = pg_query($con, $sql_os_item);

			if (pg_fetch_result($res_os_item, 0, "total") < 10) {
				$qtde_item = 10;
			} else {
				$qtde_item = pg_fetch_result($res_os_item, 0, "total");
				$qtde_item += 10;
			}
		}
	} else {
		$qtde_item = pg_fetch_result($resX,0,qtde_item_os);
		if (strlen ($qtde_item) == 0) $qtde_item = 5;

		$sql_os_item = "SELECT COUNT(*) AS total FROM tbl_os_item JOIN tbl_os_produto USING(os_produto) WHERE tbl_os_item.fabrica_i = $login_fabrica AND tbl_os_produto.os = $os";
		$res_os_item = pg_query($con, $sql_os_item);

		if (pg_num_rows($res_os_item) > 0) {
			$qtde_item_os = pg_fetch_result($res_os_item, 0, 'total');
			if($qtde_item < $qtde_item_os){
				$qtde_item += $qtde_item_os;
			}
		}

		if($login_fabrica == 24){
			if($linhas_itens > 0){
				$qtde_item = $linhas_itens;
			}
		}

	}
}

if (strlen($posto) > 0 ) {
	$resX = pg_query ($con,"SELECT item_aparencia FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica");
} else {
	$msg_erro.= 'Favor informe o código do posto!';
}
if (pg_num_rows($resX) > 0) $posto_item_aparencia = pg_fetch_result($resX,0,0);

$title = "Telecontrol - Assistência Técnica - Ordem de Serviço";
$body_onload = "javascript: document.frm_os.defeito_constatado.focus()";

$layout_menu = 'callcenter';
include "cabecalho.php"; ?>

<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->
<?
#----------------- Le dados da OS --------------
if (strlen($os) > 0) {
    $campoFalha = "";
    $condFalha  = "";
    if ($login_fabrica == 72) {
        $campoFalha = "tbl_os_produto.servico,";
        $condFalha  = "LEFT JOIN tbl_os_produto ON tbl_os_produto.os=tbl_os.os";
    }
	$sql = "SELECT  tbl_os.*                              ,
			tbl_produto.referencia                        ,
			tbl_produto.descricao                         ,
			tbl_produto.voltagem                          ,
			tbl_produto.linha                             ,
			tbl_produto.familia                           ,
			tbl_os_extra.os_reincidente AS reincidente_os ,
            tbl_os_extra.qtde_horas AS qtde_horas_os_extra      ,
			tbl_posto_fabrica.codigo_posto                ,
			tbl_posto_fabrica.reembolso_peca_estoque      ,
			tbl_os_extra.obs_adicionais                   ,
            $campoFalha
			TO_CHAR(tbl_os_extra.data_fabricacao,'DD/MM/YYYY') as data_fabricacao
		FROM    tbl_os
		JOIN    tbl_os_extra USING (os)
		JOIN    tbl_produto  USING (produto)
		JOIN    tbl_posto         USING (posto)
		JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
			  AND tbl_posto_fabrica.fabrica = $login_fabrica
        $condFalha
		WHERE   tbl_os.os = $os";
	$res = @pg_query ($con,$sql) ;

	if (@pg_num_rows($res) > 0) {
		$login_posto            = pg_fetch_result($res,0,posto);
		$linha                  = pg_fetch_result($res,0,linha);
		$familia                = pg_fetch_result($res,0,familia);
		$consumidor_nome        = pg_fetch_result($res,0,consumidor_nome);
		$sua_os                 = pg_fetch_result($res,0,sua_os);
		$produto_os             = pg_fetch_result($res,0,produto);
		$produto_referencia     = pg_fetch_result($res,0,referencia);
		$produto_descricao      = pg_fetch_result($res,0,descricao);
		$produto_voltagem       = pg_fetch_result($res,0,voltagem);
		$produto_serie          = pg_fetch_result($res,0,serie);
		$qtde_produtos          = pg_fetch_result($res,0,qtde_produtos);
		$produto_type           = pg_fetch_result($res,0,type);
		$defeito_reclamado      = pg_fetch_result($res,0,defeito_reclamado);
		$defeito_constatado     = pg_fetch_result($res,0,defeito_constatado);
		$causa_defeito          = pg_fetch_result($res,0,causa_defeito);
		$posto                  = pg_fetch_result($res,0,posto);
		$obs                    = pg_fetch_result($res,0,obs);
		$tecnico                = pg_fetch_result($res,0,tecnico);
        $qtde_horas             = pg_fetch_result($res,0,qtde_horas_os_extra);
        $os_reincidente         = pg_fetch_result($res,0,reincidente_os);
		$codigo_posto           = pg_fetch_result($res,0,codigo_posto);
		$reembolso_peca_estoque = pg_fetch_result($res,0,reembolso_peca_estoque);
		$consumidor_revenda     = pg_fetch_result($res,0,consumidor_revenda);
		$troca_faturada         = pg_fetch_result($res,0,troca_faturada);
		$motivo_troca           = pg_fetch_result($res,0,motivo_troca);
		$defeito_reclamado_descricao = pg_fetch_result ($res,0,defeito_reclamado_descricao);
		$tecnico_nome       	= pg_fetch_result ($res,0,tecnico_nome);
		#$codigo_fabricacao      = pg_fetch_result ($res,0,codigo_fabricacao);
		$valores_adicionais 	= pg_fetch_result ($res,0,valores_adicionais);
		$justificativa_adicionais = pg_fetch_result ($res,0,justificativa_adicionais);
		$qtde_km            	= pg_fetch_result ($res,0,qtde_km);
		$produto_linha          = pg_fetch_result ($res,0,linha);
		$produto_familia        = pg_fetch_result ($res,0,familia);
        $data_fabricacao        = pg_fetch_result ($res,0,data_fabricacao);
		$xtipo_atendimento        = pg_fetch_result ($res,0,tipo_atendimento);
        if($login_fabrica==19){//HD 48818
			$data_fechamento     = pg_fetch_result ($res,0,data_fechamento);
			if(strlen($data_fechamento)>0){
				$data_fechamento = explode("-", $data_fechamento);
				$data_fechamento = $data_fechamento[2]."/".$data_fechamento[1]."/".$data_fechamento[0];
			}
		}
		if($login_fabrica==30){//HD 27561
			$obs_adicionais = pg_fetch_result($res,0, obs_adicionais);

			$obs_adicionais = explode(";", $obs_adicionais);

			$fogao               = $obs_adicionais[0];
			$marca_fogao         = $obs_adicionais[1];
			$refrigerador        = $obs_adicionais[2];
			$marca_refrigerador  = $obs_adicionais[3];
			$bebedouro           = $obs_adicionais[4];
			$marca_bebedouro     = $obs_adicionais[5];
			$microondas          = $obs_adicionais[6];
			$marca_microondas    = $obs_adicionais[7];
			$lavadoura           = $obs_adicionais[8];
			$marca_lavadoura     = $obs_adicionais[9];
		}
        if ($login_fabrica == 72) {
            $falha =  pg_fetch_result ($res,0,servico);
        }


		/*$sequencia = substr($codigo_fabricacao,6,2);
		$mes_ano = substr($codigo_fabricacao,0,6);
		$mes_ano = substr_replace($mes_ano,"/",2,0);*/

	}

	if (strlen($os_reincidente) > 0) {
		$sql = "SELECT tbl_os.sua_os
				FROM   tbl_os
				WHERE  tbl_os.os      = $os_reincidente
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.posto   = $login_posto;";
		$res = @pg_query ($con,$sql) ;

		if (pg_num_rows($res) > 0) $sua_os_reincidente = trim(pg_fetch_result($res,0,sua_os));
	}

	if($inf_valores_adicionais){
        $sql = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = $os";
        $res = pg_query($con,$sql) ;
        if(pg_num_rows($res) > 0){
            $valor_adicional_formatado = pg_fetch_result($res, 0, 'valores_adicionais');
            $json_format = json_decode($valor_adicional_formatado,true);
            $custo_extra = "t";
            if(count($json_format[1]) > 0){
                $outros_custos = "t";
            }
        }

        if ($telecontrol_distrib == "t") {

            $sql = "SELECT auditoria_os
                    FROM tbl_auditoria_os
                    WHERE os = {$os}
                    AND observacao = 'OS em auditoria de Valores Adicionais'
                    AND reprovada IS NULL
                    AND cancelada IS NULL";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $bloqueia_valores = "disabled";
            }

        } else {
            $sql = "SELECT status_os FROM tbl_os_status WHERE os = $os AND status_os IN(171,172,173) ORDER BY os_status DESC LIMIT 1";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) > 0){
                $status_adicional = pg_fetch_result($res, 0, 'status_os');
                if($status_adicional == 171 OR $status_adicional == 172){
                    $bloqueia_valores = "disabled";
                }
            }
        }
    }
}

?>

<? include "javascript_pesquisas.php" ?>
<?
//include "javascript_calendario.php"
if($login_fabrica != 91 && $login_fabrica != 19){
?>
<script src="js/jquery-1.6.1.min.js" ></script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<?
}else{
?>
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
<?
}
?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<?php if ($login_fabrica == 94) : // HD 415550 ?>
	<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
	<script type="text/javascript">
		$().ready(function(){

			$("#valor_mao_de_obra").numeric( {'allow' : ','} );

		});
	</script>
<?php endif; // HD 415550 - FIM ?>

<?php if ($login_fabrica == 72) : // HD 415550 ?>
    <script type="text/javascript">
        $().ready(function(){
            $("#defeito_constatado_descricao_anterior").val($(".defeito_constatado option:selected").text());
            $("#solucao_descricao_anterior").val($(".solucao option:selected").text());
            $("#defeito_constatado_descricao").val($(".defeito_constatado option:selected").text());
            $("#solucao_descricao").val($(".solucao option:selected").text());


            $(".defeito_constatado").change(function(){
                    $("#defeito_constatado_descricao").val($(".defeito_constatado option:selected").text());
            });

            $(".solucao").change(function(){
                    $("#solucao_descricao").val($(".solucao option:selected").text());
            });

        });
    </script>
<?php endif; // HD 415550 - FIM ?>

<script src="../plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript"    src="js/jquery.price_format.1.7.min.js"></script>
<script language="JavaScript">
var JanelaCfg = function (width, height, top, left, extra) {
    var winParams = {
        toolbar: 'no',
        location: 'yes',
        status: 'yes',
        scrollbars: 'yes',
        directories: 'no',
    };

    if (typeof extra === 'object') {
        var xKeys = Object.keys(extra); // lista de chaves
        xKeys.forEach(function(k) {winParams[k] = extra[k];});
    }

    winParams.width  = width || 500;
    winParams.height = height || 400;
    winParams.top    = top || 18;
    winParams.left   = left || 0;

    return toQueryString(winParams, ', ');
};

/**
* @method
* @name:    toQueryString
* @author:  Manuel López
* @return:  String
* @desc:    Converte um Objeto simples para queryString
*   obj = {peca:'ABC123', serie:'987654', ajax: true, descricao: null};
*   toQueryString(obj) // "peca=ABC123&serie=987654&ajax=true&descricao=null"
*/
function toQueryString(obj, sep) {
	"use strict";
	sep = sep || '&';
	if (typeof obj !== 'object')
		return obj;
	var p    = [],
		idx  = Object.keys(obj),
		self = obj;
	idx.forEach(function(i) {
		p.push(i+'='+obj[i]);
	});
	return p.join(sep);
}
    var layoutCheckList = '\
                    <table width="700" align="center" id="checklist" border="1" cellspacing="0" cellpadding="1" class="formulario">\
                        <tbody>\
                            <tr>\
                                <td class="titulo_tabela" valign="middle" colspan="5">\
                                    <label style="margin:auto;font:14px Arial">Checklist:</label>\
                                </td>\
                            </tr>\
                            <tr class="titulo_tabela_2"><td class="tal">Checklist</td><td width="10%">Ação</td></tr>\
                            <tbody id="show">\
                            </tbody>\
                        </tbody>\
                    </table>\
                    <br/>';

    function retornaCheckList(selecionadosIDS, selecionadosDESC, defeitoReclamado, defeitoConstatado, ja_preenchido = false, xos) {
        Shadowbox.close();
        var selecionadosID   = $.parseJSON(selecionadosIDS);
        var selecionadosDES  = $.parseJSON(selecionadosDESC);
        var codigo_defeito = "";

        $("input[name^=i_defeito_constatado]").closest("td").each(function(){

            var codDef = $(this).text().split("-");

            if ($.trim($(this).text()) == "PRODUTO SEM DEFEITO" || $.trim(codDef[0]) == "554") {
                codigo_defeito = "554";
            }

        });

        if (selecionadosID.length > 0) {

            $("#t_checklist").show();
            $("#t_checklist").html(layoutCheckList);
            
            for (var i = 0; i < selecionadosID.length; i++) {
                $("#show").append('\
                    <tr class="t_checklist tr_check trchk-'+selecionadosID[i]+'">\
                        <td class="tal">\
                            <input type="hidden" class="check_list_fabrica" name="check_list_fabrica['+defeitoReclamado+']['+defeitoConstatado+'][]" value="'+selecionadosID[i]+'"> \
                            <input type="checkbox" checked disabled name="check_list_fabrica_'+defeitoReclamado+'_'+defeitoConstatado+'_'+selecionadosID[i]+'" > '+selecionadosDES[i]+'\
                        </td>\
                        <td>\
                            <button type="button" data-constatado="'+defeitoConstatado+'" data-reclamado="'+defeitoReclamado+'" data-os="'+xos+'" data-posicao="'+selecionadosID[i]+'" class="btn btn-delete btn-remove-item-checklist">Excluir</button>\
                        </td>\
                    </tr>');
            }
            if (codigo_defeito == "554") {

                $("#show").append('\
                    <tr class="t_checklist">\
                        <td colspan="100%" class="tac">\
                            <button type="button"  data-reclamado="'+defeitoReclamado+'" data-checklist="'+selecionadosIDS+'"  data-os="'+xos+'" data-constatado="'+defeitoConstatado+'" class="btn btn-info btn-visualizar-checklist btn-add-defeito-constatado"> Visualizar Checklist </button>\
                        </td>\
                    </tr>');
            }

        }

        $.each($("input[name^='i_defeito_constatado"), function(index, val) {
           if ($(val).data('codigo') == "554") {
                $.each($(".tr_check"), function(i, ele) {
                    $(ele).css('background-color', '#ffd9d8');
                });
           }
        });

    }

    function abreCheckList(ja_preenchido = false, defeito_reclamado = '', tipo_atendimento = '', defeito_constatado = '', id_familia = '', checklist = '') {
        Shadowbox.init();
        Shadowbox.open({
            content: '../checklist_iframe.php?area_admin=true&ja_preenchido='+ja_preenchido+'&defeito_reclamado='+defeito_reclamado+'&tipo_atendimento='+tipo_atendimento+'&defeitos_checklist=true&defeito_constatado='+defeito_constatado+'&id_familia='+id_familia+'&checklist='+checklist,
            player: "iframe",
            title: "Checklist",
            width: 800,
            height: 325,
            options: {
                modal: true
                /*enableKeys: false,
                displayNav: false*/
            }
           
        });
    }

	$(function(){
		Shadowbox.init();

        <?php if($login_fabrica == 30){ ?>
            $("#defeito_constatado").change(function(){
                var defeito_constatado = $(this).val();

                $.ajax({
                    url: "<?php echo $_SERVER['PHP_SELF'] ?>",
                    type: "POST",
                    dataType: "JSON",
                    data: {
                        verifica_def_const_cor_etiqueta: 'true',
                        defeito_constatado : defeito_constatado
                    },
                    complete: function(data){
                        data = data.responseText;
                        console.log(data);

                        if(data == 'sim'){
                            $("#td_cor_etiqueta").show();
                            $("#cor_etiqueta").show();
                            $("#pedir_cor_etiqueta").val('sim');
                            
                        }else{
                            $("#td_cor_etiqueta").hide();
                            $("#cor_etiqueta").hide();
                            $("#pedir_cor_etiqueta").val('');
                        }
                    }
                });
                
            });
        <?php } ?>


            $(document).on("click", ".btn-remove-item-checklist" , function(){
                var posicao = $(this).data('posicao');
                var os = $(this).data('os');
                var constatado = $(this).data('constatado');
                var reclamado = $(this).data('reclamado');
                var selecionadosIDS = [];


                $.ajax({
                    url: "<?php echo $_SERVER['PHP_SELF'] ?>",
                    type: "GET",
                    dataType: "JSON",
                    data: {
                        ajax_exclui_item_checklist: 'true',
                        checklist_fabrica : posicao,
                        os : os,
                        constatado : constatado,
                        reclamado : reclamado
                    },
                    complete: function(data){
                        data = data.responseText;
                        if(data.erro == "true") {
                            alert('Erro ao excluir checklist');
                        } else { 
                            
                            $(".trchk-"+posicao).remove();
                            $.each($("input[name^='check_list_fabrica"), function(index, val) {
                                selecionadosIDS.push($(val).val())
                            });
                            $(".btn-visualizar-checklist").attr('data-checklist', selecionadosIDS)
                            alert("Checklist excluido com sucesso");
                        }

                    }
                });

            });

            $(document).on("change, click", "input[name=condicao_instalacao]" , function(){
                var tipo = $(this).data('tipo');
               
                var tipo_atendimento   = $("input[name=xtipo_atendimento]").val();
                var id_familia         = $("input[name=xxproduto_familia]").val();
                var defeito_constatado = "";
                var defeito_reclamado = "";

                defeito_reclamado = $("#defeito_reclamado_1").val();

                defeito_constatado = $('input[name="i_defeito_constatado['+defeito_reclamado+'][]"]:first').val();

                var URL = 'os_item.php?ajax_carrega_checklist=true&check='+tipo+'&defeito_reclamado='+defeito_reclamado+'&familia='+id_familia+'&tipo_atendimento='+tipo_atendimento+'&defeito_constatado='+defeito_constatado;
                


                if (tipo == "instalacao_sem_defeito" || tipo == "nao_avaliado") {
                    $("#t_checklist").show();
                    $("#t_checklist").html(layoutCheckList);
                    $("#show").load(URL);
                } else {
                    abreCheckList(false, defeito_reclamado, tipo_atendimento, defeito_constatado, id_familia, '');
                     $("#t_checklist").html("");
                }

            });

            $(document).on("click", ".btn-visualizar-checklist" , function(){

                var constatado       = $(this).data("constatado");
                var os               = $(this).data("os");
                var reclamado        = $(this).data("reclamado");
                var tipo_atendimento = $("input[name=xtipo_atendimento]").val();
                var id_familia       = $("input[name=xxproduto_familia]").val();

                var selecionadosIDS = [];
                $.each($(".check_list_fabrica"), function(index, val) {
                    selecionadosIDS.push($(val).val())
                });

                abreCheckList(true, reclamado, tipo_atendimento, constatado, id_familia, selecionadosIDS)
                $(".btn-visualizar-checklist").attr('data-checklist', selecionadosIDS)


            });
             <?php
            if (in_array($login_fabrica, [19]) && verifica_checklist_tipo_atendimento($xtipo_atendimento)) { ?>

                $(document).on("click", ".btn-add-defeito-constatado" , function(){
                    if ($(this).data("checklist") != "") {
                        var checklist = $(this).data("checklist");
                    } else {
                        var checklist = "";
                    }
                    var id_defeito        = $("select[name=defeito_constatado_1] option:selected").val();
                    var familia_produto   = $("input[name=xxproduto_familia]").val();
                    var defeito           = $("input[name^=i_defeito_constatado]").data('codigo');
                    var tipo              = $("select[name=defeito_constatado_1] option:selected").data('tipo');
                    var tipo_atendimento  = $("input[name=xtipo_atendimento]").val();
                    var posicao           = $(this).data("posicao");
                    var defeito_reclamado = $("input[id=defeito_reclamado_"+posicao+"]").val();

                    if (defeito !== undefined && defeito !== "undefined" && defeito != "") {
                        var codigoDefeito     = defeito;
                    } else {
                        var text_defeito      = $("select[name=defeito_constatado_"+posicao+"] option:selected").text();
                        var xcodigoDefeito    = text_defeito.split("-");
                        var codigoDefeito     = $.trim(xcodigoDefeito[0]);
                        var id_defeito        = $("select[name=defeito_constatado_"+posicao+"] option:selected").val();
                    }

                    $(".mostra_condicao").show();
                    if (codigoDefeito == 554 || codigoDefeito == '554') {
                        $("input[id=instalacao_sem_defeito]").attr("disabled", true);
                        $("input[id=nao_avaliado]").attr("disabled", true);
                        $("input[id=defeito_na_instalacao]").prop("checked", true);
                        $("input[name=btn_adicionar]").hide();
                    } else {

                        $("input[id=instalacao_sem_defeito]").removeAttr("disabled");
                        $("input[id=nao_avaliado]").removeAttr("disabled");
                        //$("input[id=defeito_na_instalacao]").removeAttr("checked");
                        //$("input[name='condicao_instalacao']").removeAttr('checked');

                    }

                    if (codigoDefeito == 554 || codigoDefeito == '554') {
                        
                        abreCheckList(false, defeito_reclamado, tipo_atendimento, id_defeito, familia_produto, []);
                        
                    } 
                });

        <?php
            }

            if($login_fabrica == 131){ // HD-2181938
        ?>
            $("input[name=btn_gravar]").click(function(){
                if (confirm('Tem certeza que deseja gravar? Após gravar não será possível alterar ou incluir itens!')) {
                    return;
                }else{
                    location.reload();
                }
            });

        <?php
            }
        ?>

		$(".novo_valor_adicional").priceFormat({
	        prefix: '',
	        centsSeparator: ',',
	        thousandsSeparator: '.'
	    });

		<?php
		if($login_fabrica == 140){

			?>
/*
			$("table#lancamento_itens > tbody > tr > td > input[name^=qtde_]").priceFormat({
		        prefix: '',
		        centsSeparator: ',',
		        thousandsSeparator: '.'
		    });*/

			<?php

		}
		?>

        <?php
        if (in_array($login_fabrica, [3])) { ?>

                $(".select_servico_realizado").focus(function(){
                    var campo = $(this);
                    var posicao = $(campo).data("posicao");
                    var peca    = $("#peca_"+posicao).val();
                    console.log(peca+" - "+posicao);
                    if  (peca != ''){
                        $(campo).html('<option value="">Aguarde . . . . . . . .</option>');

                        $.post(window.location,
                            { 
                              buscaServicoRealizado : peca,
                              os: '<?= $os ?>'
                            },
                            function(resposta){
                                retorno = resposta.split("||");
                                //alert(retorno);
                                $(campo).html(retorno[1]);
                            }
                        );

                        $(campo).change();
                    }
                });

        <?php
        } ?>

	});

	function atualizaCausas(){

        var codigo_defeitos = $("#defeito_constatado_hidden").val();
        var auxOptions = "";

        $.ajax({
            url: "<?php echo $php_self ?>",
            dataType:"JSON",
            type:"GET",
            data: {
                "defeitos": codigo_defeitos,
                "ajax_causa_defeito": "1"
            }
        })
        .done(function(data){

            document.getElementById('causa_defeito').innerHTML = "";
            for(var i = 0 ; i < data.length ; i++) {
                var item = data[i];
                var codigo    =  data[i]['causa_defeito'];
                var nome =  data[i]['descricao'];

                var novo = document.createElement("option");

                novo.value = codigo;
                novo.text = nome;

                eval("document.forms[0].causa_defeito.options.add(novo);");
            }
        });
	}

	function adicionaIntegridade4(){
	    if(document.getElementById('defeito_constatado').value =="") {
	        alert('Selecione o <?=$tema?>');
	        return false;
	    }

	    var tbl = document.getElementById('tbl_integridade');
	    var lastRow = tbl.rows.length;
	    var iteration = lastRow;

		if (iteration>0){
	            document.getElementById('tbl_integridade').style.display = "inline";
	        }

	        var linha = document.createElement('tr');
	        linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

	    var defeito_constatado = $("#defeito_constatado").attr('value');
	    var defeito_constatado_codigo = $("#defeito_constatado option:selected").attr('codigo');
	    var defeito_descricao = $("#defeito_constatado option:selected").text();

            var codigoRepetido = false;
            $("#tbl_integridade tbody td").each(function(){
                var conteudoTd = $(this).text();
                var arrDescricao = conteudoTd.split("-");

                if ($.trim(arrDescricao[0]) == defeito_constatado_codigo) {
                    codigoRepetido = true;
                }

            });

            if (codigoRepetido) {
                alert("Defeito já inserido");
                return;
            }

			var celula =
	        criaCelula(document.getElementById('defeito_constatado').value + '-'+document.frm_os.defeito_constatado.options[document.frm_os.defeito_constatado.selectedIndex].text);
	        celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

	        var el = document.createElement('input');
	        el.setAttribute('type', 'hidden');
	        el.setAttribute('name', 'integridade_defeito_constatado_' + iteration);
	        el.setAttribute('id', 'integridade_defeito_constatado_' + iteration);
	        el.setAttribute('value',document.getElementById('defeito_constatado').value);
	        celula.appendChild(el);
	        var tbody =tbl.getElementsByTagName("tbody")[0];
	        tbody.appendChild(el);


	    var el = document.createElement('img');
	        el.setAttribute('src','imagens/ico_excluir.gif');
	        el.setAttribute('width','13');
	        el.onclick=function(){
	            var codigo_aux = defeito_constatado_codigo;
	            removerIntegridade(this,'nao');

	            var arrayAux = Array();


	            jsonDefeitosAux = $("#defeito_constatado_hidden").val();
	            jsonDefeitosAux = JSON.parse(jsonDefeitosAux);

	            jsonDefeitosAux.forEach(function(elem){
	                if(elem != codigo_aux){
	                    arrayAux.push(elem);
	                }
	            });

	            $("#defeito_constatado_hidden").val(JSON.stringify(arrayAux));
	        };

	    var tr = document.createElement('tr');
	    var td1 = document.createElement('td');
	    td1.innerHTML = defeito_descricao;

	    var td2 = document.createElement('td');
	    td2.appendChild(el);

	    tr.appendChild(td1);
	    tr.appendChild(td2);

	    $("#tbl_integridade > tbody").append(tr);

	    if($("#defeito_constatado_hidden").val() != ""){
	        jsonDefeitos = $("#defeito_constatado_hidden").val();
	        jsonDefeitos = JSON.parse(jsonDefeitos);
	    }else{
	        jsonDefeitos = new Array();
	    }

	    jsonDefeitos.push(defeito_constatado_codigo);

	    console.log(jsonDefeitos);
	    $("#defeito_constatado_hidden").val(JSON.stringify(jsonDefeitos));

	    // console.log(JSON.stringify(json));
	    // $("input[name=valor_adicional_formatado]").val(JSON.stringify(json));

	}

	var peca_critica = '';
	function fnc_pesquisa_peca_critica(referencia, posicao){
		var p_peca = eval ("document.frm_os.peca_"+posicao);
		var p_descricao = eval ("document.frm_os.descricao_"+posicao);
		var servico = eval ("document.frm_os.servico_"+posicao);

		if(p_peca.value.length > 0 && p_descricao.value.length > 0 && peca_critica != p_peca.value){
			$.ajax({
				type: "POST",
				url: "<?=$PHP_SELF?>",
				data: "referencia_peca_critica=" + p_peca.value,
				success: function(retorno) {
					if(retorno == 1){
						peca_critica = p_peca.value;
						var pergunta = confirm("Atenção!\nPeça Critica. Deseja continuar?");
						if (pergunta){
							servico.focus();
							return false;
						}else{
							peca_critica = '';
							p_peca.value = '';
							p_descricao.value = '';
							servico.value = '';
							p_peca.focus();
							return false;
						}
					}
				}
			});
		}
		return false;
	}

function verificaNumero(e) {
	if (e.which != 8 && e.which != 0 && ((e.which < 48 && e.which != 44 && e.which != 46) || e.which > 57)) {
		return false;
	}
}

function verificaPOPeca(po, linha){

    if(po == 't'){
        $('#po_peca_'+linha).show();
        $('#po_peca_'+linha).find('input').val('');
    }else{
        $('#po_peca_'+linha).hide();
        $('#po_peca_'+linha).find('input').val('');
    }

}

$(document).ready(function() {
	$("input[name^=qtde_]").keypress(verificaNumero);
});

/*$(function(){
		$("#mes_ano").maskedinput("99/9999");
	}); */

/* FUNÇÃO PARA INTELBRAS POIS TEM POSIÇÃO PARA SER PESQUISADA */

function fnc_pesquisa_peca_lista_intel (produto_referencia, peca_referencia, peca_descricao, peca_posicao, tipo) {
	var url = "";
	if (tipo == "tudo") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo;
	}

	<?php
	 if($login_fabrica == 134){
	 	?>
	 	if(url != ""){
			codigo_defeito = $("#defeito_constatado_hidden").val();
	 		url = url+"&codigo_defeitos="+codigo_defeito+"&posicao="+peca_posicao;

	 	}

	 	<?php
	 }
	?>

	<?php if($login_fabrica == 134){ ?>

		if(codigo_defeito.length <= 1 || codigo_defeito == '[""]'){
			alert("Informe um <?=$tema?> ao menos!");
		}else{
			if (peca_referencia.value.length >= 2 || peca_descricao.value.length >= 4) {
				janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
				janela.produto		= produto_referencia;
				janela.referencia	= peca_referencia;
				janela.descricao	= peca_descricao;
				janela.posicao		= peca_posicao;
				janela.focus();
			}else{
				alert("Digite pelo menos 2 caracteres!");
			}
		}
	<?php }else{ ?>

		if (peca_referencia.value.length >= 4 || peca_descricao.value.length >= 4) {
			janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
			janela.produto		= produto_referencia;
			janela.referencia	= peca_referencia;
			janela.descricao	= peca_descricao;
			janela.posicao		= peca_posicao;
			janela.focus();
		}else{
			alert("Digite pelo menos 4 caracteres!");
		}

	<?php } ?>

}
function listaDefeitos(valor) {
//verifica se o browser tem suporte a ajax
	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) { try {ajax = new XMLHttpRequest();}
				catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
		}
	}
//se tiver suporte ajax
	if(ajax) {
	//deixa apenas o elemento 1 no option, os outros são excluídos
	document.forms[0].defeito_reclamado.options.length = 1;
	//opcoes é o nome do campo combo
	idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_produto.php?produto_referencia="+valor, true);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaCombo(ajax.responseXML);//após ser processado-chama fun
			} else {idOpcao.innerHTML = "Selecione o produto";//caso não seja um arquivo XML emite a mensagem abaixo
					}
		}
	}
	//passa o código do produto escolhido
	var params = "produto_referencia="+valor;
	ajax.send(null);
	}
}

function montaCombo(obj){

	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
	for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
		 var item = dataArray[i];
		//contéudo dos campos no arquivo XML
		var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
		var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
		idOpcao.innerHTML = "Selecione o defeito";
		//cria um novo option dinamicamente
		var novo = document.createElement("option");
		novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
		novo.value = codigo;		//atribui um valor
		novo.text  = nome;//atribui um texto
		document.forms[0].defeito_reclamado.options.add(novo);//adiciona o novo elemento
		}
	} else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
	}
}

$(function () {

    $("#defeito_constatado").change(function() {

        let defeito_constatado = $("#defeito_constatado").val();

        adicionarSolucoes(defeito_constatado);
    });

});

function adicionarSolucoes(defeito_constatado) {

    $.ajax({
        url: "<?=$php_self?>",
        type:"POST",
        data:{ defeito_constatado_solucao : defeito_constatado },
        beforeSend:function(){
            $("#solucao").html('');
            $("#solucao").append("<option value=''>Carregando...</option");
        },
        success: function(data) {

            if (data.length > 0) {
                
                $("#solucao").html('');
                
                data = $.parseJSON(data);

                $("#solucao").append("<option value=''></option");
                $.each(data, function(cod, desc) {
                    $("#solucao").append("<option value='"+ cod +"'>"+ desc +"</option");
                });

            }  else {

                $("#solucao").html('');
                $("#solucao").append("<option value=''>DEFEITO SEM SOLUÇÃO CADASTRADA</option");
            }
        },
        error: function() { 

            $("#solucao").html('');
            $("#solucao").append("<option value=''>DEFEITO SEM SOLUÇÃO CADASTRADA</option");
        },    
    });
}

function verificaDuplicidade(defeito_constatado, solucao, xxproduto_linha, defeito_reclamado, xxproduto_familia) {

    var tabela_defeitos = $("#tbl_integridade tbody tr");
    
    var array_defeitos = [];
    
    tabela_defeitos.each(function (index, tr) {

        let tds = $(tr).find("td");
        
        var i = 1; 
        
        var string; 

        tds.each(function(idx, td) {
 
            if (i == 1) {

                string = $(td).find("input").val();
            }

            if (i == 2) {

                string = string + " - " + $(td).find("input").val();
            }

            if (i == 3) {

                array_defeitos.push(string);

                i = 1;

            } else {

                i++;
            }


        });

    });

    var ok = true; 

    $.each(array_defeitos, function(indx, def_sol) {

        console.log(def_sol + " != " + defeito_constatado + " - " +solucao);

        let defeito_solucao = def_sol.split(" - ");
        
        if (defeito_solucao[0] == defeito_constatado) {

            //if (defeito_solucao[1] == solucao) {

                ok = false;
            //}
        }

    });

    if (ok) {

        var fornecedor_select = parseInt($("#cor_etiqueta").val());
        var pedir_cor_etiqueta = $("#pedir_cor_etiqueta").val(); 

        if(!Number.isInteger(fornecedor_select) && pedir_cor_etiqueta == 'sim'){
            alert("Informe uma cor da etiqueta"); 
            return false; 
        }     


        adicionaIntegridade();

<?php if ($login_fabrica == 30) { ?>
        //$('#solucao').empty().append('<option value="">SELECIONE DEFEITO CONSTATADO</option>');
<?php } else { ?>
        listaSolucao(defeito_constatado, xxproduto_linha, defeito_reclamado, xxproduto_familia);
        $("#solucao").html("");
<?php } ?>

    } else {

        alert("Defeito Constatado já adicionado com essa Solução");
    }
 
}


function listaSolucao(defeito_constatado, produto_linha,defeito_reclamado, produto_familia) {
//alert(defeito_reclamado);
//verifica se o browser tem suporte a ajax
    
	try {
		ajax = new ActiveXObject("Microsoft.XMLHTTP");
	}
	catch(e) {
		try {
			ajax = new ActiveXObject("Msxml2.XMLHTTP");
		}

		catch(ex) {
			try {
				ajax = new XMLHttpRequest();
			}
			catch(exc) {
				alert("Esse browser não tem recursos para uso do Ajax");
				ajax = null;
			}
		}

	}

    <?php 

    /**
     *  @author William Castro
     *  hd-6807955
     *  Mostrar todas as soluções
     */

    if ($login_fabrica == 30) { ?>
    
        var defeitos = "";

        var tabl = document.getElementById("tbl_integridade");

        for (var i = 1, row; row = tabl.rows[i]; i++) {

            for (var j = 0, col; col = row.cells[j]; j++) {
            
                var input = col.innerHTML;
                
                input = input.split('value="');

                input = input[1].split('">');

                if (input[0] != "Excluir") {

                    if (defeitos.length != 0) {
                        defeitos = defeitos + "_" + input[0];
                    } else {
                        defeitos = input[0];
                    } 
                }

            }
        }

        defeito_constatado = defeitos;

    <?php } ?>
    //se tiver suporte ajax
	if(ajax) {
		//deixa apenas o elemento 1 no option, os outros são excluídos
		document.forms[0].solucao_os.options.length = 1;
		//opcoes é o nome do campo combo
		idOpcao  = document.getElementById("opcoes");
		//	 ajax.open("POST", "ajax_produto.php", true);

		ajax.open("GET", "ajax_solucao.php?defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia);
		ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

		ajax.onreadystatechange = function() {
			if(ajax.readyState == 1) {
				idOpcao.innerHTML = "Carregando...!";
			}//enquanto estiver processando...emite a msg

			if(ajax.readyState == 4 ) {
                
                console.log(ajax.responseXML);

				if(ajax.responseXML) {
					montaComboSolucao(ajax.responseXML);//após ser processado-chama fun
                } else {
                    idOpcao.innerHTML = "Selecione o <?=$tema?>";//caso não seja um arquivo XML emite a mensagem abaixo
				}
			}
		}

		//passa o código do produto escolhido
		var params = "defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia;
		ajax.send(null);
	}
}

function montaComboSolucao(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto

			if(dataArray.length > 0) {//total de elementos contidos na tag cidade
				for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
					var item = dataArray[i];
		//contéudo dos campos no arquivo XML

				var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
					var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
					//idOpcao.innerHTML = " ";
		//cria um novo option dinamicamente
				var novo = document.createElement("option");
					novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
							novo.value = codigo;		//atribui um valor
									novo.text  = nome;//atribui um texto
											document.forms[0].solucao_os.options.add(novo);//adiciona o novo elemento
				}
			} else { idOpcao.innerHTML = "Nenhuma solução encontrada";//caso o XML volte vazio, printa a mensagem abaixo
			}
}
function listaConstatado(linha,familia, defeito_reclamado,defeito_constatado) {
//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
	catch(ex) { try {ajax = new XMLHttpRequest();}
		catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
	}
	}

//se tiver suporte ajax
		if(ajax) {
	//deixa apenas o elemento 1 no option, os outros são excluídos

	defeito_constatado.options.length = 1;
	idOpcao  = document.getElementById("opcoes2");
	ajax.open("GET","ajax_defeito_constatado.php?defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha);

	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {
			if(ajax.responseXML) {
					montaComboConstatado(ajax.responseXML,defeito_constatado);
			//apï¿½ ser processado-chama fun
			}
			else {
				idOpcao.innerHTML = "Selecione o defeito reclamado";//caso nï¿½ seja um arquivo XML emite a mensagem abaixo
			}
		}
	}
	//passa o cï¿½igo do produto escolhido
	//var params ="defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha";
	ajax.send(null);
		}
}

function montaComboConstatado(obj,defeito_constatado){
	var dataArray   = obj.getElementsByTagName("produto");
	var idOpcao  = document.getElementById("opcoes2");

	if(dataArray.length > 0) {
		for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
			var item = dataArray[i];

			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "Selecione o defeito";

			var novo = document.createElement("option");
			novo.setAttribute("id", "opcoes2");
			novo.value = codigo;
			novo.text  = nome  ;
			defeito_constatado.options.add(novo);//adiciona
		}
	} else {
		idOpcao.innerHTML = "Selecione o defeito";
	}
}

function defeitoLista(peca,linha,os) {
//verifica se o browser tem suporte a ajax
	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
	catch(ex) { try {ajax = new XMLHttpRequest();}
		catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
	}
	}
//se tiver suporte ajax
	if(peca.length > 0) {
		if(ajax) {
			var kit = '';

            if ($('input[name=kit_kit_peca_'+linha+']')) {
                kit = $('input[name=kit_kit_peca_'+linha+']').val();
            }
			var defeito = "defeito_"+linha;
			var op = "op_"+linha;
	//alert(defeito);
		//deixa apenas o elemento 1 no option, os outros sï¿½ excluï¿½os
				eval("document.forms[0]."+defeito+".options.length = 1;");
		//opcoes ï¿½o nome do campo combo
				idOpcao  = document.getElementById(op);
		//	 ajax.open("POST", "ajax_produto.php", true);
	//alert("tas "+idOpcao);
		ajax.open("GET","ajax_defeito2.php?peca="+peca+"&os="+os+"&kit_peca="+kit);
		ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

		ajax.onreadystatechange = function() {
			if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
			if(ajax.readyState == 4 ) {
				if(ajax.responseXML) {
					montaComboDefeito(ajax.responseXML,linha);
				//apï¿½ ser processado-chama fun
				}else {
					idOpcao.innerHTML = "Selecione a peça";//caso nï¿½ seja um arquivo XML emite a mensagem abaixo
				}
			}
		}
		//passa o cï¿½igo do produto escolhido
		//var params ="defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha";
		ajax.send(null);
		}
	}
}

function defeitoListaNova(peca,linha,os){
    if(peca.length > 0){
        var kit = "";
        var defeito = "defeito_"+linha;
        var op = "op_"+linha;
        if ($('input[name=kit_kit_peca_'+linha+']')) {
            kit = $('input[name=kit_kit_peca_'+linha+']').val();
        }
        $.ajax({
            url:"ajax_defeito2.php",
            type:"GET",
            dataType:"xml",
            data:{
                peca:peca,
                os:os,
                kit_peca:kit
            },
            beforeSend:function(){
                $("#"+defeito+" #"+op).text("Carregando...");
            },
        })
        .done(function(xml){
            var defeitos = "<option value='"+op+"'></option>";
            $(xml).find('produto').each(function(){
                var codigo  = $(this).find("codigo").text();
                var nome    = $(this).find("nome").text();
                defeitos += "<option value='"+codigo+"'>"+codigo+" - "+nome+"</option>";
            });
            $("#"+defeito).html(defeitos);

        })
        .fail(function(){
            $("#"+defeito+" #"+op).text("ERRO AO CARREGAR DEFEITOS DA PEÇA");
        });
    }
}

function defeitoPeca(peca,linha) {
    try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
    catch(e) {
        try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
        catch(ex) { try {ajax = new XMLHttpRequest();}
            catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
        }
    }

    if(peca.length > 0) {
        if(ajax) {
            var defeito = "defeito_"+linha;
            var op = "op_"+linha;

            eval("document.forms[0]."+defeito+".options.length = 1;");
            idOpcao  = document.getElementById(op);
            ajax.open("GET","ajax_defeito_peca.php?peca="+peca);
            ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            ajax.onreadystatechange = function() {
                if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}
                if(ajax.readyState == 4 ) {
                    if(ajax.responseXML) {
                        montaComboDefeito(ajax.responseXML,linha);
                    }
                    else {
                        idOpcao.innerHTML = "Nenhum defeito";
                    }
                }
            }
            ajax.send(null);

        }
    }
   	<?
   	if(!in_array($login_fabrica,[50,120,201])){
	?>
	 var servico = "servico_"+linha;
	 eval("document.forms[0]."+servico+".options.length = 1;");
	<?
	}
	?>
}

function listaServico(defeito,linha) {
    try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
    catch(e) {
        try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
        catch(ex) { try {ajax = new XMLHttpRequest();}
            catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
        }
    }
    if(defeito.length > 0) {
        if(ajax) {
            var servico = "servico_"+linha;
            var op = "op_"+linha;
            eval("document.forms[0]."+servico+".options.length = 1;");
            idOpcao  = document.getElementById(op);
            ajax.open("GET","ajax_servico_defeito.php?defeito="+defeito);
            ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            ajax.onreadystatechange = function() {
                if(ajax.readyState == 1) {
                	<?php
                	if($login_fabrica ==131){
                		?>
                		eval('document.forms[0].servico_'+linha+'.options[0].innerHTML = "Carregando.....!"');
                		<?php
                	}else{
                		?>
                		idOpcao.innerHTML = "Carregando...!";
                		<?php
                	}
                	?>

                }
                if(ajax.readyState == 4 ) {
                    if(ajax.responseXML) {
                        montaComboServicoDefeito(ajax.responseXML,linha);
                    }
                    else {
                        idOpcao.innerHTML = "Nenhum serviço";
                    }
                }
            }
            ajax.send(null);
        }
    }
}

function montaComboServicoDefeito(obj,linha){
    var servico = "servico_"+linha;
    var op_servico = "op_"+linha;
    var dataArray   = obj.getElementsByTagName("produto");
    if(dataArray.length > 0) {
        for(var i = 0 ; i < dataArray.length ; i++) {
            var item = dataArray[i];
            var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
            var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;

            <?php
        	if($login_fabrica ==131){
        		?>
			eval('document.forms[0].servico_'+linha+'.options[0].innerHTML =  "Selecione o defeito"');
			eval('document.forms[0].servico_'+linha+'.options[0].value =  ""');
        		<?php
        	}else{
        		?>
        		idOpcao.innerHTML = "Selecione o defeito";
        		<?php
        	}
		?>

            var novo = document.createElement("option");

            novo.setAttribute("id_servico", op_servico);
            novo.value = codigo;
            novo.text  = nome;
            eval("document.forms[0]."+servico+".options.add(novo);");
        }
    } else {
    	<?php
    	if($login_fabrica ==131){
    		?>
		eval('document.forms[0].servico_'+linha+'.options[0].innerHTML =  "Selecione o defeito"');
		eval('document.forms[0].servico_'+linha+'.options[0].value =  ""');
    		<?php
    	}else{
    		?>
    		idOpcao.innerHTML = "Selecione o defeito";
    		<?php
    	}
	?>
    }
}

function montaComboDefeito(obj,linha){
	var defeito = "defeito_"+linha;
	var op = "op_"+linha;
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto

	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
		for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
			var item = dataArray[i];

			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "Selecione o defeito";

			var novo = document.createElement("option");
				novo.setAttribute("id", op);//atribui um ID a esse elemento
				novo.value = codigo;		//atribui um valor
				novo.text  = nome;//atribui um texto
				eval("document.forms[0]."+defeito+".options.add(novo);");//adiciona
		}
	} else {
		idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
	}
}

	function adicionaIntegridade() {
		//if(document.getElementById('defeito_reclamado').value=="0") { alert('Selecione o defeito reclamado'); return false}
		if(document.getElementById('defeito_constatado').value==""){
			alert('Selecione o <?=$tema?>');
			return false;
		}

          if(document.getElementById('defeito_constatado').value == 16149 && document.getElementById('cor_etiqueta').value == ''){
            alert('Para esse defeito constatado deve selecionar a cor da etiqueta. ');
            return false;
        }
		
        if(document.getElementById('solucao').value=="0" || document.getElementById('solucao').value=="") { 
            alert('Selecione a solução');           
            return false
        }      

		var tbl = document.getElementById('tbl_integridade');
		var lastRow = tbl.rows.length;
		var iteration = lastRow;


		if (iteration > 0) {
			document.getElementById('tbl_integridade').style.display = "inline";
		}

        var tabela_defeitos = $("#tbl_integridade tr");
       
        tabela_defeitos.each(function (index, tr) {

            let tds = $(tr).find("td");
        
            var solucao;
            var acoes; 
            var id; 
            <?php if($login_fabrica != 30){ ?>
                acoes = $(tr).find('td:last-child');            
                $(tr).find('td:last-child').remove();
                
                solucao = $(tr).find('td:last-child'); 
                $(tr).find('td:last-child').remove();
                
                id = $(tr).find('td:last-child input').attr("id");

                console.log('id '+ id);
                
                id = id.split("_");

                $(tr).append(solucao);
                $(tr).append(acoes);

                iteration = parseInt(id[3]) + 1;
            <?php } else {?>

                iteration = index + 1; 

            <?php } ?>

        });

		var linha = document.createElement('tr');
		linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

		// COLUNA 1 - LINHA
		var celula = criaCelula(document.getElementById('defeito_constatado').options[document.getElementById('defeito_constatado').selectedIndex].text);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'integridade_defeito_constatado_' + iteration);
		el.setAttribute('id', 'integridade_defeito_constatado_' + iteration);
		el.setAttribute('value',document.getElementById('defeito_constatado').value);
		celula.appendChild(el);

		linha.appendChild(celula);

	   <? if(in_array($login_fabrica, [2, 30, 59])) { ?>
            var celula = criaCelula(document.getElementById('solucao').options[document.getElementById('solucao').selectedIndex].text );
            celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

            var el = document.createElement('input');
            el.setAttribute('type', 'hidden');
            el.setAttribute('name', 'integridade_solucao_' + iteration);
            el.setAttribute('id', 'integridade_solucao_' + iteration);
            el.setAttribute('value',document.getElementById('solucao').value);
            celula.appendChild(el);

            linha.appendChild(celula);
        <?}?>


        <?php if($login_fabrica == 30) { ?>

            var pedir_cor_etiqueta = $("#pedir_cor_etiqueta").val();

            if(pedir_cor_etiqueta == 'sim'){
                var celula = criaCelula(document.getElementById('cor_etiqueta').options[document.getElementById('cor_etiqueta').selectedIndex].text);
                celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

                var el = document.createElement('input');
                el.setAttribute('type', 'hidden');
                el.setAttribute('name', 'integridade_cor_etiqueta_' + iteration);
                el.setAttribute('id', 'integridade_cor_etiqueta_' + iteration);
                el.setAttribute('value',document.getElementById('cor_etiqueta').value);
                celula.appendChild(el);

                linha.appendChild(celula);
            }else{
                var celula = criaCelula('');
                celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';                

                linha.appendChild(celula);
            }

        <?php } ?>

		// coluna 6 - botacao
		var celula = document.createElement('td');
		celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'button');
		el.setAttribute('value','Excluir');
		el.onclick=function(){removerIntegridade(this);};
		celula.appendChild(el);
		linha.appendChild(celula);

		// finaliza linha da tabela
		var tbody = tbl.getElementsByTagName("tbody")[0];
		tbody.appendChild(linha);
		/*linha.style.cssText = 'color: #404e2a;';*/
		tbl.appendChild(tbody);

		//document.getElementById('solucao').selectedIndex=0;
	}

	function adicionaIntegridade2(indice,tabela,defeito_reclamado,defeito_reclamado_desc,defeito_constatado) {
		var parar = 0;
		//alert(defeito_reclamado.value);
		//alert(defeito_constatado.value);
		$("input[rel='defeito_constatado_"+indice+"']").each(function (){
			//alert($(this).val() + '-'+ defeito_constatado.value);
			if ($(this).val() == defeito_constatado.value){
				parar++;
			}
		});
        <?php
        if ($login_fabrica == 19) {
        ?>
            var splitDescDefeito = defeito_constatado.options[defeito_constatado.selectedIndex].text.split("-");

            if ($.trim(splitDescDefeito[0]) == '554' && $("input[name^=i_defeito_constatado]").length > 0) {
                alert("Para adicionar esse defeito, exclua os anteriores");
                return false;
            }
            var excluirDefeito = false;
            $("input[name^=i_defeito_constatado]").closest("td").each(function(){

                var splitTextDefeito = $(this).text().split("-");

                if ($.trim(splitTextDefeito[0]) == '554' || $.trim($(this).text()) == 'PRODUTO SEM DEFEITO') {
                    excluirDefeito = true;
                }

            });

            if (excluirDefeito) {
                alert('Exclua o defeito constatado "PRODUTO SEM DEFEITO" para adicionar um novo');
                return false;
            }

        <?php
        }
        ?>
		if (parar>0){
			alert("<?=$tema?> " +defeito_constatado.options[defeito_constatado.selectedIndex].text+' já inserido')
			return false;
		}

        var id_defeito = defeito_constatado.value;

		//if(document.getElementById('defeito_reclamado').value=="0") { alert('Selecione o defeito reclamado'); return false}
		var tbl       = document.getElementById(tabela);
		var lastRow   = tbl.rows.length;
		var iteration = lastRow;

		if (iteration>0){
			document.getElementById(tabela).style.display = "inline";
		}
		//Cria Linha
		var linha = document.createElement('tr');
		linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

		// Cria Coluna/
		var celula = document.createElement('td');
		var celula = criaCelula(defeito_constatado.options[defeito_constatado.selectedIndex].text);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px;border-bottom: thin dotted #FF0000';
		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');

        <?php if ($login_fabrica == 19) {?>
            el.setAttribute('name', 'i_defeito_constatado['+$("#defeito_reclamado_"+indice).val()+'][]');
            el.setAttribute('rel', 'defeito_constatado_' +indice);
            el.setAttribute('id', 'i_defeito_constatado_' +indice+'_'+ iteration);

        <?php } else {?>
            el.setAttribute('name', 'i_defeito_constatado_' +indice+'_'+ iteration);
            el.setAttribute('rel', 'defeito_constatado_' +indice);
            el.setAttribute('id', 'i_defeito_constatado_' +indice+'_'+ iteration);

        <?php }?>


		el.setAttribute('value',defeito_constatado.value);
		celula.appendChild(el);
		linha.appendChild(celula);


		var celula = document.createElement('td');
		celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';
		var el = document.createElement('input');
		el.setAttribute('type', 'button');
		el.setAttribute('value','Excluir');
		el.onclick=function(){removerIntegridade2(this,tabela,id_defeito);};
		celula.appendChild(el);
		linha.appendChild(celula);

		// finaliza linha da tabela
		var tbody = document.createElement('TBODY');
		tbody.appendChild(linha);
		/*linha.style.cssText = 'color: #404e2a;';*/
		tbl.appendChild(tbody);

        var id_defeito = defeito_constatado.value;

        if($("#defeitos_lorenzetti_hidden").val() != ""){
            jsonDefeitos = $("#defeitos_lorenzetti_hidden").val();
            jsonDefeitos = JSON.parse(jsonDefeitos);
        }else{
            jsonDefeitos = new Array();
        }
        jsonDefeitos.push(id_defeito);

        $("#defeitos_lorenzetti_hidden").val(JSON.stringify(jsonDefeitos));

	}

	function adicionaIntegridade3() {

		if(document.getElementById('defeito_constatado_codigo').value =="") {
			alert('Selecione o <?=$tema?>');
			return false;
		}

		var tbl = document.getElementById('tbl_integridade');
		var lastRow = tbl.rows.length;
		var iteration = lastRow;

        if (iteration>0){
            document.getElementById('tbl_integridade').style.display = "inline";
        }

        var linha = document.createElement('tr');
        linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

        // COLUNA 1 - LINHA

        var celula =
        criaCelula(document.getElementById('defeito_constatado_codigo').value + '-'+document.frm_os.defeito_constatado_codigo.options[document.frm_os.defeito_constatado_codigo.selectedIndex].text);
        celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

		celula.setAttribute('colspan','2');

        var el = document.createElement('input');
        el.setAttribute('type', 'hidden');
        el.setAttribute('name', 'integridade_defeito_constatado_' + iteration);
        el.setAttribute('id', 'integridade_defeito_constatado_' + iteration);
        el.setAttribute('value',document.getElementById('defeito_constatado_codigo').value);
        celula.appendChild(el);
        linha.appendChild(celula);


        // coluna 6 - botacao
        var celula = document.createElement('td');
        celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

        var el = document.createElement('img');
        el.setAttribute('src','imagens/ico_excluir.gif');
		el.setAttribute('width','13');
        el.onclick=function(){removerIntegridade(this,'nao');};
        celula.appendChild(el);
        linha.appendChild(celula);


        // finaliza linha da tabela
        var tbody =tbl.getElementsByTagName("tbody")[0];
        tbody.appendChild(linha);
        /*linha.style.cssText = 'color: #404e2a;';*/
        tbl.appendChild(tbody);

        document.getElementById('defeito_constatado_codigo').selectedIndex=0;

    }

	function removerIntegridade(iidd){
		<?php if ($login_fabrica == 30) { ?>
			var linha = $(iidd).attr('rel');
			var defeito_constatado = $("#integridade_defeito_constatado_"+linha).val();
			var solucao = $("#integridade_solucao_"+linha).val();
			var os = '<?= $os; ?>';

			if (defeito_constatado != undefined && solucao != undefined) {
				$.ajax({
					type: "POST",
					url: "<?= $php_self; ?>",
					data: {ajax_defeito_reclamado_constatado : true, os : os, defeito_constatado : defeito_constatado, solucao : solucao},
					complete: function(data) {
						data = JSON.parse(data.responseText);
					}
				});
			}
		<?php } ?>
		var tbl = document.getElementById('tbl_integridade');
		tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);
	}

    function removeChecklistDefeitos(os,check_list_fabrica) {
         $.ajax({
            async: true,
            type: "GET",
            url: "<?=$php_self?>",
            data: {ajax_exclui_checklist_os : true, os:os, check_list_fabrica:check_list_fabrica},
            complete: function(data) {
                
            }
        });
    }
	function removerIntegridade2(iidd,tabela,id_defeito_constatado_reclamado){
		if (confirm('Deseja excluir o Defeito? Excluindo as peças lançandas referente ao defeito serão apagadas.')) {

            var tbl = document.getElementById(tabela);
            tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);
            var os = <?php echo $_GET['os']; ?>
            // hd_chamado=2881143 //
                var defeitos_constatados = "";
                defeitos_constatados = JSON.parse($("#defeitos_lorenzetti_hidden").val());

                len = defeitos_constatados.length;
                defeitos_constatados_aux = new Array();
                for(i=0;i<len;i++){

                    def = defeitos_constatados.pop();
                    if(def != id_defeito_constatado_reclamado){
                        defeitos_constatados_aux.push(def);
                    }

                }



                $("#defeitos_lorenzetti_hidden").val(JSON.stringify(defeitos_constatados_aux));
                if(id_defeito_constatado_reclamado.length > 2){
                    //new
                        jsonDef = new Array();
                        $("input[name^=peca_]").each(function(){
                            var defs = $(this).val();
                            if(defs != ""){
                                jsonDef.push(defs);
                            }
                        });

                        $.ajax({
                            async: true,
                            type: "GET",
                            url: "<?=$php_self?>",
                            data: 'defeito_constatado_pecas='+defeitos_constatados_aux+'&pecas_lancadas='+jsonDef,
                            complete: function(data) {
                                if (data.responseText != "erro") {
                                    data = JSON.parse(data.responseText);
                                    $.each( data, function( key, value ) {
                                        $("input[name^=peca_]").each(function(){
                                            if($(this).val() == value){
                                                $(this).parent('td').parent().find("input[name^=peca_],[name^=descricao_],[name^=qtde_],[name^=defeito_],[name^=servico_]").val('');
                                            }
                                        });
                                    });
                                }
                            }
                        });
                    // fim new
                }else{
                    $("#lancamento_itens").find("input[name^=peca_],[name^=descricao_],[name^=qtde_],[name^=defeito_],[name^=servico_]").val('');
                }

                if ($("input[name^=i_defeito_constatado]").length == 0) {

                    $("input[name=btn_adicionar]").show();

                }

            // FIM - hd_chamado=2881143 //
        }

	}

	function criaCelula(texto) {
		var celula = document.createElement('td');
		var textoNode = document.createTextNode(texto);
		celula.appendChild(textoNode);
		return celula;
	}

function formata_data_visita(cnpj, form, posicao){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "visita_data_" + posicao;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}

}

<?php
	if(in_array($login_fabrica, array(35,72,74))){
        ?>

        var ajax_estoque = {};

        function limpaMovimentacaoEstoque(n){

            var os                      = '<?=$os?>';
            var servico                 = $('#yservico_'+n).val();
            var limpa_movimento_estoque2 = "ok";
            var qtde                    = $('#yqtde_'+n).val();
            var referencia              = $('#peca_'+n).val();
            var os_item                 = $('input[name=os_item_'+n+']').val();

            $.ajax({
                url: '<?php echo $_SERVER[PHP_SELF]; ?>',
                type: 'POST',
                data: {
                    limpa_movimento_estoque2 : limpa_movimento_estoque2,
                    os                      : os,
                    servico                 : servico,
                    qtde                    : qtde,
                    referencia              : referencia,
                    os_item                 : os_item
                },
                beforeSend: function(){
                    ajax_estoque[n] = true;
                    $('#loading_'+n).show();
                },
                complete: function(data){
                    data = data.responseText;

                    ajax_estoque[n] = false;
                    $('#loading_'+n).hide();
                }
            });

        }
        <?php
    }

    ?>

function formata_valor(cnpj, form, posicao){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "valores_adicionais_" + posicao;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '.';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}


}
function FormataValor(campo,tammax,teclapres)
{
    //uso:
    //<input type="Text" name="fat_vr_bruto" maxlength="17" onKeyDown="FormataValor(this,17,event)">

    var tecla = teclapres.keyCode;
    vr = campo.value;
    vr = vr.replace( "/", "" );
    vr = vr.replace( "/", "" );
    vr = vr.replace( ",", "" );
    vr = vr.replace( ".", "" );
    vr = vr.replace( ".", "" );
    vr = vr.replace( ".", "" );
    vr = vr.replace( ".", "" );
    tam = vr.length;

    if (tam<tammax && tecla != 8){ tam = vr.length + 1 ; }

    if (tecla == 8 ){    tam = tam - 1 ; }

    if ( tecla == 8 || tecla >= 48 && tecla <= 57 || tecla >= 96 && tecla <= 105 ){
        if ( tam <= 2 ){
             campo.value = vr ; }
         if ( (tam>2) && (tam <= 5) ){
             campo.value = vr.substr( 0, tam - 2 ) + ',' + vr.substr( tam - 2, tam ) ; }
         if ( (tam >= 6) && (tam <= 8) ){
             campo.value = vr.substr( 0, tam - 5 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ; }
         if ( (tam >= 9) && (tam <= 11) ){
             campo.value = vr.substr( 0, tam - 8 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ; }
         if ( (tam >= 12) && (tam <= 14) ){
             campo.value = vr.substr( 0, tam - 11 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ; }
         if ( (tam >= 15) && (tam <= 17) ){
             campo.value = vr.substr( 0, tam - 14 ) + '.' + vr.substr( tam - 14, 3 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ;}
    }
}

<?php
#HD 307418
?>
function limpaDefeito(i){
	$('#defeito_'+i).html('<option id="op_'+i+'"></option>');
}


function fnc_pesquisa_peca_lista_masterfrio (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, defeito_constatado, tipo, peca_qtde) {
	var url = "";
	if (tipo == "tudo") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&defeito_constatado="+defeito_constatado.value;
	}

	if (tipo == "referencia") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&defeito_constatado="+defeito_constatado.value;
	}

	if (tipo == "descricao") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&defeito_constatado="+defeito_constatado.value;
	}
<? if ($login_fabrica <> 2) { ?>
	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
<? } ?>
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.preco		= peca_preco;
		janela.qtde			= peca_qtde;
		janela.focus();
<? if ($login_fabrica <> 2) { ?>
	}else{
		alert("<? if($sistema_lingua == "ES"){
echo "Digite al minus 3 caracters";
}else{
echo "Digite pelo menos 3 caracteres!";
 } ?>");
	}
<? } ?>
}

function adiciona_linha(valor) {

	<?php
		if($login_fabrica == 24){
			?>
		 		if($('input[name=peca_'+(parseInt(valor)-1)+']').val() == ""){
		            alert("Por favor informe a Peça");
		            $('input[name=peca_'+(parseInt(valor)-1)+']').focus();
		            return;
		        }

		        if($('input[name=descricao_'+(parseInt(valor)-1)+']').val() == ""){
		            alert("Por favor informe a Descrição da Peça");
		            $('input[name=descricao_'+(parseInt(valor)-1)+']').focus();
		            return;
		        }

		        if($('input[name=qtde_'+(parseInt(valor)-1)+']').val() == ""){
		            alert("Por favor informe a Quantidade de Peça");
		            $('input[name=qtde_'+(parseInt(valor)-1)+']').focus();
		            return;
		        }

		        if($('select[name=defeito_'+(parseInt(valor)-1)+']').val() == ""){
		            alert("Por favor informe o Defeito sa Peça");
		            $('select[name=defeito_'+(parseInt(valor)-1)+']').focus();
		            return;
		        }

		        if($('select[name=servico_'+(parseInt(valor)-1)+']').val() == ""){
		            alert("Por favor informe o Serviço realizado na Peça");
		            $('select[name=servico_'+(parseInt(valor)-1)+']').focus();
		            return;
		        }

			<?php
		}
	?>

	var qtde_linha = parseInt($("#qtde_item").val());
	var linha = valor;
	if (valor == qtde_linha) {
		$("#lancamento_itens > tbody").find("tr[name^=linha_]").last("tr").after("<tr name='linha_"+linha+"'>" + $("tr[name=linha_"+(valor-1)+"]").clone().html().replace(/(.)(,|_)\d\d?/g,'$1$2'+linha) + "</tr>");

        // Limpando os campos hiden quando clona a linha hd_chamado=2727786 //
            $("input[name=os_item_"+linha+"]").val("");
            $("input[name=os_produto_"+linha+"]").val("");
        // fim //
		$("select[name=servico_"+linha+"]").attr('alt',parseInt(linha)+1);
		$("#qtde_item").val(qtde_linha + 1);
		$("input[name=peca_"+linha+"]").val("");
		$("input[name=descricao_"+linha+"]").val("");
		$("tr[name=linha_"+linha+"]").find("select").each(function(){$(this).val("")});
	}

}

function valida_itens(){

    var valor = 1;
    var erro_form = "";
    var fabrica = $('.fabrica_hidden').val();


    if(fabrica == "24"){
	    $('#lancamento_itens > tbody > tr').each(function(){

	        valor++;

	        if($('input[name=descricao_'+valor+']').val() != "" || $('input[name=peca_'+valor+']').val() != ""){

	            if($('input[name=peca_'+valor+']').val() == ""){
	                alert("Por favor informe a Peça");
	                $('input[name=peca_'+valor+']').focus();
	                erro_form = "on";
	                return;
	            }

	            if($('input[name=descricao_'+valor+']').val() == ""){
	                alert("Por favor informe a Descrição da Peça");
	                $('input[name=descricao_'+valor+']').focus();
	                erro_form = "on";
	                return;
	            }

	            if($('input[name=qtde_'+valor+']').val() == ""){
	                alert("Por favor informe a Quantidade de Peça");
	                $('input[name=qtde_'+valor+']').focus();
	                erro_form = "on";
	                return;
	            }

	            if($('select[name=defeito_'+valor+']').val() == ""){
	                alert("Por favor informe o Defeito sa Peça");
	                $('select[name=defeito_'+valor+']').focus();
	                erro_form = "on";
	                return;
	            }

	            if($('select[name=servico_'+valor+']').val() == ""){
	                alert("Por favor informe o Serviço realizado na Peça");
	                $('select[name=servico_'+valor+']').focus();
	                erro_form = "on";
	                return;
	            }
	        }
	    });

		if(erro_form != "on"){
			document.frm_os.submit();
		}else{
			document.frm_os.btn_acao.value = "";
		}

	}

}

function gravaDadosLbm(name, valor){
    try {
        $("#"+name).val(valor);
    } catch(err){
        return false;
    }
    Shadowbox.close();
}

function fnc_pesquisa_lista_serie (linha,produto_referencia, peca_referencia, peca_descricao, produto_serie, tipo, peca_qtde) {
    var buca_peca;
    var tipo_buca_peca;
    if(tipo == "referencia"){
        busca_peca = peca_referencia.value;
    }else{
        busca_peca = peca_descricao.value;
    }

    if(tipo == "referencia"){
        tipo_busca_peca = "peca";
    }else{
        tipo_busca_peca = "descricao";
    }

    Shadowbox.open({
        content: "peca_pesquisa_lista_serie.php?linha="+linha+"&produto=" + produto_referencia + "&"+tipo_busca_peca+"="+busca_peca+"&tipo="+tipo+"&produto_serie="+produto_serie+"&os=<?=$os?>&exibe=<? echo $_SERVER['REQUEST_URI']; ?>",
        player: "iframe",
        title: "Pesquisa Lista",
        width: 501,
        height: 400
    });
}

function retorna_pecas_lbm(peca_referencia,peca_descricao,depara_auditoria,linha){
    gravaDadosLbm("peca_"+linha,peca_referencia);
    gravaDadosLbm("descricao_"+linha,peca_descricao);
    gravaDadosLbm("depara_auditoria_"+linha,depara_auditoria);
}

function pesquisaPeca(campo1,campo2,tipo,posicao,kit_peca,os){
    
    var campo1        = $.trim(campo1.value);
    var campo2        = $.trim(campo2.value);
    var kit           = (typeof kit_peca !== 'undefined') ? kit_peca.value : '';
    var login_fabrica = <?=$login_fabrica?>;
	var url_pesquisa  = "peca_pesquisa_lista_nv.php?";
	var params        = {
		produto: campo1,
		tipo:    tipo,
		input_posicao: posicao,
		kit_peca: kit,
		os: os
	};
    params[tipo]  = campo2;

    // Adiciona o valor se existe o elemento input no formulário
    if (typeof document.frm_os.versao_produto !== 'undefined') {
        params.versao_produto = document.frm_os.versao_produto.value;
    }

    if (campo2.length > 2 || tipo == 'tudo') {
		if (login_fabrica == 134) {
			codigo_defeito = $("#defeito_constatado_hidden").val();

			if(codigo_defeito.length <= 1 || codigo_defeito == '[""]'){
				alert("Informe um <?=$tema?> ao menos!"); 
			}else{
                params.defeito_constatado = codigo_defeito;
            }
        }
        Shadowbox.init();
        Shadowbox.open({
            content: url_pesquisa + toQueryString(params),
            player:  "iframe",
            title:   "Pesquisa de peça",
            width:   800,
            height:  500
        });

    }else{
        alert("Informar toda ou parte da informação para realizar a pesquisa");
    }
}
//função para hydra para regra de recompra
function verificaEstoqueRecompra(peca_referencia, posicao){
    var posto_id = $("#posto_id").val();

    $.ajax({
        url:"<?=$PHP_SELF?>",
        type:"POST",
        dataType:"json",
        data:{
            ajax:"verifica_recompra",
            ajax_peca:peca_referencia,
            posto_id: posto_id
        },
        complete: function(data){
            var dados = data.responseText;
            if(dados.length > 0){
                console.log(dados);
                $("#yservico_"+posicao).html(dados);
            }

        }
    });

}

//hd_chamado=2881143
function fnc_pesquisa_lista_basica_lorenzetti(campo1,campo2,tipo,posicao,kit_peca,os,defeito_constatado_hidden){
    var campo1          = $.trim(campo1.value);
    var campo2          = $.trim(campo2.value);
    var kit             = "";
    var login_fabrica   = <?=$login_fabrica?>;
    var defeito_hidden = defeito_constatado_hidden.value;

    if(kit_peca == undefined){
        kit = "";
    }else{
        kit = kit_peca.value;
    }
    if (defeito_hidden.length > 2){
        if (campo2.length > 2 || tipo == 'tudo'){
            Shadowbox.open({
                content :   "peca_pesquisa_lista_nv.php?produto="+campo1+"&"+tipo+"="+campo2+"&tipo="+tipo+"&input_posicao="+posicao+"&kit_peca="+kit+"&os="+os+"&defeito_constatado="+defeito_hidden,
                player  :   "iframe",
                title   :   "Pesquisa de peça",
                width   :   800,
                height  :   500
            });

        }else{
            alert("Informar toda ou parte da informação para realizar a pesquisa");
        }
    }else{
        alert("Adicione os defeitos para continuar");
    }
}


function retorna_lista_peca(referencia_antiga,posicao,codigo_linha,peca_referencia,peca_descricao,preco,peca,type,input_posicao,kit_peca){
    var login_fabrica = <?=$login_fabrica?>;

    $('#peca_'+input_posicao).blur();
    gravaDados("peca_"+input_posicao,peca_referencia);
    gravaDados("descricao_"+input_posicao,peca_descricao);

    verificaEstoqueRecompra(peca_referencia, input_posicao);

    if(login_fabrica == 91){
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"json",
            data:{
                ajax:"fornecedor_peca",
                ajax_peca:peca,
                ajax_kit_peca:kit_peca
            }
        })
        .done(function(data){
            var forn = "<option value=''>Selecione um fornecedor</option>";
            $.each(data,function(key,value){
                forn += "<option value='"+key+"'>"+value+"</option>";
            });
            $("select[name=fornecedor_"+input_posicao+"]").html(forn);
        })
        .fail(function(){
            alert("Não foi possível carregar os fornecedores desta peça");
        });
    }
    if (login_fabrica == 120 || login_fabrica == 201) {
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:"defeito_peca",
                ajax_peca:peca
            }
        })
        .done(function(data){
            var forn = "<option value=''>SELECIONE</option>";
            $.each(data,function(key,value){
                forn += "<option value='"+key+"'>"+value+"</option>";
            });
            $("select[name=defeito_"+input_posicao+"]").html(forn);
        })
        .fail(function(){
            alert("Não foi possível carregar os defeitos desta peça");
        });
    }
}

function gravaDados(name, valor){
     try {
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}

function fnc_pesquisa_lista_basica (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde, defeito_constatado) {
        var url = "";
        if (tipo == "tudo") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&os=<?=$os?>" + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&defeito_constatado="+defeito_constatado.value;
        }

        if (tipo == "referencia") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&os=<?=$os?>" + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "descricao") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&os=<?=$os?>" + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&defeito_constatado="+defeito_constatado.value;
        }
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.produto    = produto_referencia;
        janela.referencia = peca_referencia;
        janela.descricao  = peca_descricao;
        janela.preco      = peca_preco;
        janela.qtde       = peca_qtde;
        janela.focus();

}

$(function(){

	if($("#custo_extra").is(":checked")){
        $("#descricao_custos").show();
        $("#descricao_custos_prod").show();
        $("#outros_custos_span").show();
    }

    $("#custo_extra").change(function(){
        $("#descricao_custos").toggle();
        $("#descricao_custos_prod").toggle();
        $("#outros_custos_span").toggle();
    });

     if($("#outros_custos").is(":checked")){
        $("#table_outros_servicos").show();
    }

    $("#outros_custos").change(function(){
        $("#table_outros_servicos").toggle();
    });

    $("input[name^=custo_adicional]").each(function(){
        if( $(this).is(":checked") ){
            $(this).parent("td").parent("tr").find("input[rel=valor_adicional]").show();
        }else{
        	$(this).parent("td").parent("tr").find("input[rel=valor_adicional]").hide();
        }
    });

    formataValorAdicional();
    $("input[name^=custo_adicional]").change(function(){
        if( $(this).is(":checked") ){
            $(this).parent("td").parent("tr").find("input[rel=valor_adicional]").show();
        }else{
        	$(this).parent("td").parent("tr").find("input[rel=valor_adicional]").hide();
        }
    });

<?php
    if ($login_fabrica == 72) {//fputti hd-3130038
?>
        $("#defeito_constatado").change(function(){
            var defeito = $(this).val();
            $.ajax({
                url: '<?php echo $_SERVER[PHP_SELF]; ?>',
                type: 'POST',
                data: {
                    ajax_carrega_falha_potencial : true,
                    defeito: defeito
                },
                complete: function(data){
                    console.log(data.responseText)
                    $('#falha_em_potencial').html(data.responseText);
                }
            });
        });
        $("#solucao").change(function(){
            var solucoesPermitidas =  new Array(3042, 3043, 3044, 3045, 3046);
            var solucao = $(this).val();
            if (in_array(solucao,solucoesPermitidas)) {
                $('#ajuste_realizado').show();
            } else {
                $('#ajuste_realizado').hide();
            }
        });
        function in_array(search, array) {
            for (i = 0; i < array.length; i++) {
                if (array[i] == search ) {
                    return true;
                }
            }
            return false;
        }
<?php
    }
    if ($login_fabrica == 120 or $login_fabrica == 201) {
?>
        $("select[id^=defeito_]").change(function(){
            var defeito = $(this).val();
            var aux = $(this).attr("id");
            var divide = aux.split("_");
            var linha = divide[1];
            var peca = $("#peca_"+linha).val();

            $.ajax({
                url:"<?=$PHP_SELF?>",
                type:"POST",
                dataType:"JSON",
                data:{
                    ajax:"servico_peca",
                    ajax_defeito:defeito,
                    ajax_peca:peca,
                }
            })
            .done(function(data){
                var forn = "<option value=''>SELECIONE</option>";
                $.each(data,function(key,value){
                    forn += "<option value='"+key+"'>"+value+"</option>";
                });
                $("select[name=servico_"+linha+"]").html(forn);
            })
            .fail(function(){
                alert("Não foi possível carregar os serviços desta peça");
            });
        });
<?php
    }
?>
});

function formataValorAdicional(){

   $("input[name^=custo_adicional],input[name^=valor_adicional]").each(function(){

        var json = {};
        var array = new Array();
        var grupo1 = {};
        var grupo2 = {};

        $("input[name^=custo_adicional]").each(function(){
            if( $(this).is(":checked") ){
                var rel = $(this).attr("rel").toString();
                grupo1[rel] = $(this).val();
                $(this).parent("td").parent("tr").find("input[rel=valor_adicional]").show();
            }else{
                $(this).parent("td").parent("tr").find("input[rel=valor_adicional]").hide();
            }
        });

        array.push(grupo1);

        $("input[name^=custo_adicional_outros]").each(function(){
            if( $.trim($(this).val()).length > 0 ){
                var rel = $(this).val().toString();
                grupo2[rel] = $(this).parent("td").parent("tr").find("input[name^=valor_adicional_outros]").val();
            }
        });
        array.push(grupo2);

        json = array;

        $("input[name=valor_adicional_formatado]").val(JSON.stringify(json));
   });
}

function alteraValorAdicional(campo){
    $(campo).parent("td").parent("tr").find("input[name^=custo_adicional]:checked").val(campo.value);
    $("input[name^=custo_adicional]").change();
}

function adiciona_linha_valor_adicional() {
    var qtde_linha = $("#table_outros_servicos > tbody > tr").length;
    $("#table_outros_servicos > tbody").append("<tr id='mostrar_adicional_"+(parseInt(qtde_linha)+1)+"'>" + $("tr[id=mostrar_adicional_"+(qtde_linha)+"]").clone().html().replace(/_\d\d?/g,'_'+(parseInt(qtde_linha)+1)) + "</tr>");
    $("input[name=custo_adicional_outros_"+(parseInt(qtde_linha)+1)+"]").val("");
    $("input[name=valor_adicional_outros_"+(parseInt(qtde_linha)+1)+"]").val("");
    $(".novo_valor_adicional").maskMoney({showSymbol:"", symbol:"", decimal:",", precision:2, thousands:".",maxlength:10});
}
</script>

<style>
.select_bloqueado {
    background: #eee !important;
    pointer-events: none  !important;
    touch-action: none  !important;
    cursor: not-allowed  !important;
    opacity: 0.3  !important;
}

#box-uploader-app {
    width: 700px;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

table#anexos{
	width: 100% !important;
}

</style>

<?

if (strlen ($msg_erro) > 0) {

##### RECARREGA FORM EM CASO DE ERRO #####
	if (strlen($os) == 0) $os = $_POST["os"];
	$defeito_constatado = $_POST["defeito_constatado"];
	$defeito_reclamado  = $_POST["defeito_reclamado"];
	$causa_defeito      = $_POST["causa_defeito"];
	$obs                = $_POST["obs"];
	$solucao_os         = $_POST["solucao_os"];

	$tecnico_nome       = $_POST["tecnico_nome"];
	
    /* 
        $mes_ano            = $_POST["mes_ano"];
        $sequencia          = $_POST["sequencia"];
    */

	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro.= "Esta ordem de serviço já foi cadastrada";
	if (strpos ($msg_erro,"pedido_fk") > 0) $msg_erro.= "Este item da OS já foi faturado. Não pode ser removido.";
?>

<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center"><font face="Arial, Helvetica, sans-serif" color="#FF3333"><b>
		<?
		// retira palavra ERROR:
		if (strpos($msg_erro,"ERROR: ") !== false) {
			$erro = "Foi detectado o seguinte erro:<br>";
			if($login_fabrica == 1 && strpos($msg_erro,"fora da garantia vencida em") !== false){
                $msg_erro = str_replace("fora da garantia vencida em","é de desgaste natural e a garantia da mesma terminou em",$msg_erro);
			}
			$msg_erro = substr($msg_erro, 6);
		}

			// retira CONTEXT:
		if (strpos($msg_erro,"CONTEXT:")) {
		    $x = explode('CONTEXT:',$msg_erro);
		    $msg_erro = $x[0];
		}
		echo $erro . $msg_erro;
		?>
		</b></font>
	</td>
</tr>
</table>
<?
}

if (strlen($sua_os_reincidente) > 0 and $login_fabrica == 6) {
	echo "<br><br>";
	$sql = "select * from tbl_os_status where os=$os and status_os = 67";
	$res = pg_query($con,$sql);
	$sql = "select * from tbl_os where os=$os and os_reincidente = 't'"; /*Reincidencia de postos diferentes*/
	$res2 = pg_query($con,$sql);
	if(pg_num_rows($res)>0 and pg_num_rows($res2)>0){
		echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffCCCC'>";
		echo "<tr>";

		echo "<td valign='middle' align='center'>";
		echo "<font face='Verdana,Arial, Helvetica, sans-serif' color='#FF3333' size='2'><b>";
		echo "ESTA ORDEM DE SERVIÇO É REINCIDENTE MENOR QUE 90 DIAS.<br>
		ORDEM DE SERVIÇO ANTERIOR: $sua_os_reincidente.<br>
		NÃO SERÁ PAGO O VALOR DE MÃO-DE-OBRA PARA A ORDEM DE SERVIÇO ATUAL.<BR>
		ELA SERVIRÁ APENAS PARA PEDIDO DE PEÇAS.";
		echo "</b></font>";
		echo "</td>";

		echo "</tr>";
		echo "</table>";

		echo "<br><br>";
	}
}

if($login_fabrica == 42){
    $sql_prestacao = "  SELECT  tbl_posto_fabrica.prestacao_servico
                        FROM    tbl_posto_fabrica
                        WHERE   posto = $login_posto
                        AND     fabrica = $login_fabrica
    ";
    $res_prestacao      = pg_query($con,$sql_prestacao);
    $prestacao_servico  = pg_fetch_result($res_prestacao,0,prestacao_servico);

    if($prestacao_servico == 't'){
?>
         <div style='background-color:#FCDB8F;width:600px;margin:0 auto;text-align:center;padding:5px'>
            <p style="font:15px Arial; font-weigth: bold; color: #F00">
                Este lançamento de itens caracterizará um pedidos de peças em garantia ao fabricante, <br />não sendo necessário realizar um novo pedido das peças
            </p>
         </div>
<?
    }
}

if($login_fabrica == 6){
	$sqlQ = "SELECT COUNT(peca)
				FROM tbl_lista_basica
				JOIN tbl_produto USING(produto)
				JOIN tbl_peca USING (peca)
				WHERE tbl_produto.referencia = '$produto_referencia'
				AND tbl_lista_basica.fabrica = $login_fabrica
				AND tbl_peca.item_aparencia = 't'";
	$resQ = pg_query($con,$sqlQ);
	$qtde_item = pg_fetch_result($resQ,0,0);
	$qtde_item = $qtde_item + 5;
}
?>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>?os=<? echo $os ?>" enctype='multipart/form-data'>
    <input type='hidden' name='xtipo_atendimento' id='xtipo_atendimento' value='<?php echo $xtipo_atendimento;?>'>

    <?php
    if ($login_fabrica == 1 && $_REQUEST["shadowbox"] == 't') {
    ?>
        <input type="hidden" name="shadowbox" value="<?= $_REQUEST["shadowbox"] ?>" />
    <?php
    } ?>
<table width="1000" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		<input type="hidden" name="os" value="<?echo $os?>">
		<input type='hidden' name='voltagem' value='<? echo $voltagem ?>'>
		<input type='hidden' name='qtde_item' id='qtde_item' value='<? echo $qtde_item ?>'>
		<input type='hidden' name='produto_referencia' value='<? echo $produto_referencia ?>'>
    <?php if ($usa_versao_produto) {
        $versao_produto = serie_produto_versao($produto_os, $produto_serie);
        echo "<input type='hidden' name='versao_produto' value='$versao_produto'>";
    }?>

		<p>

		<table width="700" class='formulario' border="0" cellspacing="5" cellpadding="0">
		<tr class='titulo_tabela'><td colspan='100%'>Lançamento de itens</td></tr>
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?></b>
				</font>
			</td>
			<td >
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor </font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $consumidor_nome ?></b>
				</font>
			</td>
			<? if ($login_fabrica == 19) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b>
				<?
				echo $qtde_produtos;
				?>
				</b>
				</font>
			</td>
			<? } ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b>
				<?
				echo $produto_referencia . " - " . substr($produto_descricao,0,20);
				if (strlen($produto_voltagem) > 0) echo " - ".$produto_voltagem;
				?>
				</b>
				</font>
		<?	$sqlv = "SELECT tbl_comunicado.comunicado, extensao
					   FROM tbl_comunicado
					   LEFT JOIN tbl_comunicado_produto USING(comunicado)
					  WHERE fabrica = $login_fabrica
						AND tipo = 'Vista Explodida'
						AND (   tbl_comunicado.produto         = $produto_os
                             OR tbl_comunicado_produto.produto = $produto_os)";


			$resv = pg_query($con,$sqlv) ;

			if (pg_num_rows($resv) > 0) {
				$vcomunicado             = pg_fetch_result($resv, 0, 'comunicado');
				$vextensao               = pg_fetch_result($resv, 0, 'extensao');

				if ($S3_online)
					$s3ok = ($s3ve->temAnexos($vcomunicado) > 0);

				$vista_explodida_produto = ($S3_online and $s3ok) ? $s3ve->url : "../comunicados/$vcomunicado.$vextensao";
				//die($vista_explodida_produto);

				if ($s3ok or file_exists($vista_explodida_produto)) {
					echo "&nbsp;<a href='$vista_explodida_produto' target='_blank' style='vertical-align:bottom;font-size:9px;height:32px;line-height:16px;display:inline-block;width:100px;zoom:1'>
									<img src='../imagens/botoes/vista_explodida_icone.png' style='float:left' height='32' alt='Vista Explodida' title='Vista Explodida do Produto' />
									<span >Vista<br />Explodida</span>
								</a>";
				}
			}
			?>
				</td><?

		   	if ($login_fabrica == 1) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Versão/Type</font>
				<br>
				<?
				     GeraComboType::makeComboType($parametrosAdicionaisObject, $produto_type, "produto_type", array("class"=> "frm"));
      				     echo GeraComboType::getElement();
				    ?>

			</td>
			<? } ?>

			<? if ($login_fabrica == 74) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Fabricação</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>
				<?=$data_fabricacao?>
				</b></font>
			</td>
			<? } ?>

			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					<?
					if($login_fabrica==35){
						echo "PO#";
					}else{
						if($login_fabrica <> 127){ // HD-2296739
                            echo "N. Série";
                        }
					}?>
				</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><?php
						if($login_fabrica == 19 && $produto_linha == 265){
							echo "<input class='frm' type='text' name='produto_serie' id='produto_serie' size='18' maxlength='20' value='$produto_serie' />";
						}else{
							echo $produto_serie;

                            if ($login_fabrica == 30) {
                                ?><input class='frm' type='hidden' name='produto_serie' id='produto_serie' size='18' maxlength='20' value='<?=$produto_serie?>' />
                                <?
                           }

						}
					?></b>
				</font>
			</td>
		</tr>
		</table>

<?
if (in_array($login_fabrica, array(2, 3, 6, 11, 19, 24, 30, 35, 45, 46,50, 51, 59, 95,40,90,72,74,99,91) ) or $login_fabrica >= 104 ) {
//relacionamento de integridade comeca aqui....
echo "<INPUT TYPE='hidden' name='xxproduto_linha' value='$produto_linha'>";
echo "<INPUT TYPE='hidden' name='xxproduto_familia' value='$produto_familia'>";
if(($login_fabrica==6 || $login_fabrica==3 || $login_fabrica==24 || $login_fabrica==11 || $login_fabrica == 172 ) and strlen($defeito_reclamado)>0){

//verifica se o defeito reclamado esta ativo, senao ele pede pra escolher de novo...acontece pq houve a mudança de tela.
	$sql = "SELECT ativo from tbl_defeito_reclamado where defeito_reclamado=$defeito_reclamado";

	$res = pg_query($con,$sql);
	$xativo = @pg_fetch_result($res,0, ativo);

	if($xativo=='f'){
		$defeito_reclamado= "";
	}
	$sql = "SELECT defeito_reclamado
			FROM tbl_diagnostico
			WHERE fabrica=$login_fabrica
			AND linha = $produto_linha
			AND defeito_reclamado = $defeito_reclamado
			AND familia = $produto_familia";
	$res = @pg_query($con,$sql);
#if($ip=="201.43.11.131"){echo $sql;}
	$xativo = @pg_fetch_result($res,0, defeito_reclamado);
	if(strlen($xativo)==0){
		$defeito_reclamado= "";
	}
}

//HD 697198 -  Adicionado IBBL - 90
if ((in_array($login_fabrica, array(2, 3, 5, 6,15,24,35,50,51,59,72,91,115,116,117,120,201))) ) { // 95 se tiver integridade reclamado-constatado
	echo "<table width='700' class='formulario' border='0' cellspacing='5' cellpadding='0'>";
	echo "<tr>";

	if(strlen($defeito_reclamado_descricao) > 0 AND ($login_fabrica == 115 OR $login_fabrica == 116 OR $login_fabrica == 120 or $login_fabrica == 201)){
		echo "<td> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado Cliente</font><BR>";
		echo "<div style='size=11px'><b>$defeito_reclamado_descricao</b></div>";
		echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
		echo "<INPUT TYPE='hidden' name='defeito_reclamado_descricao' value='$defeito_reclamado_descricao'>";
		echo "</td>";
	}

	if(!in_array($login_fabrica,array(6,72,120,201,117,35,50,91))){
		echo "<td>";

		echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><BR>";

		if (strlen($defeito_reclamado) > 0)  {
			$sql = "SELECT 	defeito_reclamado,
						descricao as defeito_reclamado_descricao
				FROM tbl_defeito_reclamado
				WHERE defeito_reclamado= $defeito_reclamado";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res)>0){
				$xdefeito_reclamado = pg_fetch_result($res,0,defeito_reclamado);
				$xdefeito_reclamado_descricao = pg_fetch_result($res,0,defeito_reclamado_descricao);
			}
			echo "<div style='size=11px'><b>$xdefeito_reclamado - $xdefeito_reclamado_descricao</b></div>
				<INPUT TYPE='hidden' class='frm' name='xxdefeito_reclamado' size='30' value='$xdefeito_reclamado - $xdefeito_reclamado_descricao' disabled>";

			echo "<INPUT TYPE='hidden' name='defeito_reclamado' value='$xdefeito_reclamado'>";
		}
		echo "</td>";

	if($login_fabrica<>19 and $login_fabrica <> 74 ){

	if ($login_fabrica <> 117) {
		echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$tema</font><BR>";

			$cond_linha = (in_array($login_fabrica,array(115,116,117,120,201))) ? ' 1 = 1 ' : " tbl_diagnostico.linha = $produto_linha ";
			$sql = "SELECT 	distinct(tbl_diagnostico.defeito_constatado),
							tbl_defeito_constatado.descricao
					FROM tbl_diagnostico
					JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
					WHERE $cond_linha";
			if($login_fabrica != 115 AND $login_fabrica != 116 AND $login_fabrica != 117 AND $login_fabrica != 120 and $login_fabrica != 201){
				if (strlen($defeito_reclamado)>0) $sql .= " AND tbl_diagnostico.defeito_reclamado = $defeito_reclamado";
			}
			$sql .= " AND tbl_defeito_constatado.ativo='t' ";
			if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
			$sql.=" ORDER BY tbl_defeito_constatado.descricao";
			$res = pg_query($con,$sql);
			echo "<select name='defeito_constatado' id='defeito_constatado' size='1' class='frm' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);'>";

			echo "<option value=''></option>";
			for ($y = 0 ; $y < pg_num_rows ($res) ; $y++ ) {
				$xxdefeito_constatado = pg_fetch_result ($res,$y,defeito_constatado) ;
				$defeito_constatado_descricao = pg_fetch_result ($res,$y,descricao) ;

				echo "<option value='$xxdefeito_constatado'";
				if($defeito_constatado==$xxdefeito_constatado) echo "selected";
				echo ">$defeito_constatado_descricao</option>";
			}

					echo "</select>";
					echo "</td>";
		}

		if(!in_array($login_fabrica,array(115,116,117,120,201,122))){
                if ($login_fabrica == 59){
                    $id_solucao = "id='solucao'";
                }
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solução</font><BR>";
				echo "<select name='solucao_os' $id_solucao class='frm'  style='width:250px;' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);' >";
				echo "<option id='opcoes' value=''></option>";
				if(empty($solucao_os)){
					$cond_solucao = " AND ativo IS TRUE";
				} else if ($login_fabrica <> 59 ) {
					$cond_solucao = " AND solucao=$solucao_os";
				}

				$sql = "SELECT 	solucao,
								descricao
						FROM tbl_solucao
						WHERE fabrica=$login_fabrica
						$cond_solucao";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){
					for($i = 0; $i < pg_num_rows($res); $i++){
						$solucao_descricao = pg_fetch_result ($res,$i,descricao);
						$solucao_id        = pg_fetch_result ($res,$i,solucao);

						echo "<option id='opcoes' value='$solucao_id'";
						echo ($solucao_id == $solucao_os) ? "selected" : "";
						echo ">$solucao_descricao</option>";
					}
				}
				echo "</select>";

			echo "</td>";
		}
		}
	echo "</tr>";
	}
	if($inf_valores_adicionais){
        $checked_custo = ($custo_extra == 't') ? "checked" : "";
        $checked_outro_custo = ($outros_custos == 't') ? "checked" : "";
        echo "<tr>";
        echo "<td width='150' nowrap>
                <input type='hidden' name='valor_adicional_formatado' value='$valor_adicional_formatado'>
                <input type='checkbox' name='custo_extra' id='custo_extra' value='t' $checked_custo $bloqueia_valores>Valores Adicionais <br>";
         if(!in_array($login_fabrica,array(35,125)) ) { echo "<span id='outros_custos_span' style='display:none;'> <input type='checkbox' name='outros_custos' id='outros_custos' value='t' $checked_outro_custo $bloqueia_valores>Outros valores</span>
              </td>"; }

        $valor_adicional_formatado = json_decode($valor_adicional_formatado,true);

        $sqlAdicionaisProduto = "SELECT valores_adicionais FROM tbl_produto WHERE produto = $produto_os AND fabrica_i = $login_fabrica AND valores_adicionais notnull";
        $resAdicionaisProduto = pg_query($con,$sqlAdicionaisProduto);
        if(pg_num_rows($resAdicionaisProduto) > 0){
            echo "<td id='descricao_custos_prod' style='display:none;' valign='top'>";
            $valores_adicionais_prod = pg_fetch_result($resAdicionaisProduto, 0, 'valores_adicionais');
            $valores_adicionais_prod = json_decode($valores_adicionais_prod,true);

            echo "<table width='200'>";
            echo "<tr><th align='left'>Serviço do Produto</th><th align='left'>Valor</th>";
            foreach ($valores_adicionais_prod as $key => $value) {
                unset($checked_valor);
                if(!empty($valor_adicional_formatado[0][$key])){
                    $value = $valor_adicional_formatado[0][$key];
                    $checked_valor = "checked";
                }

                echo "<tr>";
                echo "<td>";
                echo "<input type='checkbox' name='custo_adicional[]' rel='$key' value='$value' class='frm' $checked_valor $bloqueia_valores>";
                echo utf8_decode($key);
                echo "</td>";
                echo "<td>";
                echo "<b>$value</b>";
                echo "<input type='hidden' name='' value='$value' size='5' rel='valor_adicional'>";
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</td>";
        }
        $sqlAdicionais = "SELECT valores_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica";
        $resAdicionais = pg_query($con,$sqlAdicionais);
        if(pg_num_rows($resAdicionais) > 0){
            $valores_adicionais = pg_fetch_result($resAdicionais, 0, 'valores_adicionais');
            $valores_adicionais = json_decode($valores_adicionais,true);

            echo "<td id='descricao_custos' style='display:none;' valign='top'>";
            echo "<table width='200'>";

            if($login_fabrica == 35){
            	echo (count($valores_adicionais) > 0) ? "<tr><th align='left'>Serviço da OS</th><th align='left'>Valor</th>" : "";
            }else{
	            echo "<tr><th align='left'>Serviço da OS</th><th align='left'>Valor</th>";
	        }

            foreach ($valores_adicionais as $key => $value) {
                $valor = $value['valor'];
                unset($checked_valor);
                if($valor_adicional_formatado[0][$key]){
                    $valor = $valor_adicional_formatado[0][$key];
                    $checked_valor = "checked";
                }

                echo "<tr>";
                echo "<td>";
                echo "<input type='checkbox' name='custo_adicional[]' rel='$key' value='$valor' class='frm' $checked_valor $bloqueia_valores>";
                echo $key;
                echo "</td>";
                echo "<td>";
                if($value['editar'] == 'f'){
                    echo "<input type='hidden' value='$valor' size='5' class='frm $class' rel='valor_adicional'>";
                    echo "<b>$valor</b>";
                }else{
                $class      = ($value['editar'] == 't') ? "novo_valor_adicional" : "";
                    echo "<input type='text'  value='$valor' size='5' class='frm $class' rel='valor_adicional' onblur='alteraValorAdicional(this)' $readonly style='display:none' $bloqueia_valores>";
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</td>";
        }

        echo "</tr>";

    }
	echo "</table>";

	if($inf_valores_adicionais){
	    echo "<table width='300' id='table_outros_servicos' style='display:none;' border='0' class='formulario'>";
	            echo "<thead>";
	            echo "<tr>";
	            echo "<th>Serviço</th>";
	            echo "<th>Valor</th>";
	            echo "<th> <input type='button' value='Adicionar Linha' onclick='javascript: adiciona_linha_valor_adicional();' $bloqueia_valores> </th>";
	            echo "</tr>";
	            echo "</thead>";
	            echo "<tbody>";

	    if(count($valor_adicional_formatado[1]) > 0){
	        $s = 1;
	        foreach ($valor_adicional_formatado[1] as $key => $value) {
	            $key = $key;
	            echo "<tr id='mostrar_adicional_$s'>";
	            echo "<td width='200'>";
	            echo "<input type='text' name='custo_adicional_outros_$s' rel='outros_servicos' value='$key' class='frm' $bloqueia_valores>";
	            echo "</td>";
	            echo "<td>";
	            echo "<input type='text' name='valor_adicional_outros_$s' rel='outros_valores' value='$value' size='5' class='frm novo_valor_adicional' $bloqueia_valores>";
	            echo "</tr>";
	            $s++;
	        }
	    }else{
	        echo "<tr id='mostrar_adicional_1'>";
	        echo "<td>";
	        echo "<input type='text' name='custo_adicional_outros_1' rel='outros_servicos' value='' onkeyup='retiraAcentos(this)' class='frm' $bloqueia_valores>";
	        echo "</td>";
	        echo "<td>";
	        echo "<input type='text' name='valor_adicional_outros_1' rel='outros_valores' value='' size='5' class='frm novo_valor_adicional' $bloqueia_valores>";
	        echo "</tr>";
	    }
	    echo "</tbody>";

	    echo "</table>";
	}
}

if (in_array($login_fabrica, array(139))) {
	echo "<table width='700' class='formulario' border='0' cellspacing='5' cellpadding='0'>";
	if($inf_valores_adicionais){
        $checked_custo = ($custo_extra == 't') ? "checked" : "";
        $checked_outro_custo = ($outros_custos == 't') ? "checked" : "";
        echo "<tr>";
        echo "<td width='150' nowrap>
                <input type='hidden' name='valor_adicional_formatado' value='$valor_adicional_formatado'>
                <input type='checkbox' name='custo_extra' id='custo_extra' value='t' $checked_custo $bloqueia_valores>Valores Adicionais <br>";
         if($login_fabrica != 35) { echo "<span id='outros_custos_span' style='display:none;'> <input type='checkbox' name='outros_custos' id='outros_custos' value='t' $checked_outro_custo $bloqueia_valores>Outros valores</span>
              </td>"; }

        $valor_adicional_formatado = json_decode($valor_adicional_formatado,true);

        $sqlAdicionaisProduto = "SELECT valores_adicionais FROM tbl_produto WHERE produto = $produto_os AND fabrica_i = $login_fabrica AND valores_adicionais notnull";
        $resAdicionaisProduto = pg_query($con,$sqlAdicionaisProduto);
        if(pg_num_rows($resAdicionaisProduto) > 0){
            echo "<td id='descricao_custos_prod' style='display:none;' valign='top'>";
            $valores_adicionais_prod = pg_fetch_result($resAdicionaisProduto, 0, 'valores_adicionais');
            $valores_adicionais_prod = json_decode($valores_adicionais_prod,true);

            echo "<table width='200'>";
            echo "<tr><th align='left'>Serviço do Produto</th><th align='left'>Valor</th>";
            foreach ($valores_adicionais_prod as $key => $value) {
                unset($checked_valor);
                if(!empty($valor_adicional_formatado[0][$key])){
                    $value = $valor_adicional_formatado[0][$key];
                    $checked_valor = "checked";
                }

                echo "<tr>";
                echo "<td>";
                echo "<input type='checkbox' name='custo_adicional[]' rel='$key' value='$value' class='frm' $checked_valor $bloqueia_valores>";
                echo utf8_decode($key);
                echo "</td>";
                echo "<td>";
                echo "<b>$value</b>";
                echo "<input type='hidden' name='' value='$value' size='5' rel='valor_adicional'>";
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</td>";
        }
        $sqlAdicionais = "SELECT valores_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica";
        $resAdicionais = pg_query($con,$sqlAdicionais);
        if(pg_num_rows($resAdicionais) > 0){
            $valores_adicionais = pg_fetch_result($resAdicionais, 0, 'valores_adicionais');
            $valores_adicionais = json_decode($valores_adicionais,true);

            echo "<td id='descricao_custos' style='display:none;' valign='top'>";
            echo "<table width='200'>";

            if($login_fabrica == 35){
            	echo (count($valores_adicionais) > 0) ? "<tr><th align='left'>Serviço da OS</th><th align='left'>Valor</th>" : "";
            }else{
	            echo "<tr><th align='left'>Serviço da OS</th><th align='left'>Valor</th>";
	        }

            foreach ($valores_adicionais as $key => $value) {
                $valor = $value['valor'];
                unset($checked_valor);
                if($valor_adicional_formatado[0][$key]){
                    $valor = $valor_adicional_formatado[0][$key];
                    $checked_valor = "checked";
                }

                echo "<tr>";
                echo "<td>";
                echo "<input type='checkbox' name='custo_adicional[]' rel='$key' value='$valor' class='frm' $checked_valor $bloqueia_valores>";
                echo $key;
                echo "</td>";
                echo "<td>";
                if($value['editar'] == 'f'){
                    echo "<input type='hidden' value='$valor' size='5' class='frm $class' rel='valor_adicional'>";
                    echo "<b>$valor</b>";
                }else{
                $class      = ($value['editar'] == 't') ? "novo_valor_adicional" : "";
                    echo "<input type='text'  value='$valor' size='5' class='frm $class' rel='valor_adicional' onblur='alteraValorAdicional(this)' $readonly style='display:none' $bloqueia_valores>";
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</td>";
        }

        echo "</tr>";

    }
	echo "</table>";

	if($inf_valores_adicionais){
	    echo "<table width='300' id='table_outros_servicos' style='display:none;' border='0' class='formulario'>";
	            echo "<thead>";
	            echo "<tr>";
	            echo "<th>Serviço</th>";
	            echo "<th>Valor</th>";
	            echo "<th> <input type='button' value='Adicionar Linha' onclick='javascript: adiciona_linha_valor_adicional();' $bloqueia_valores> </th>";
	            echo "</tr>";
	            echo "</thead>";
	            echo "<tbody>";

	    if(count($valor_adicional_formatado[1]) > 0){
	        $s = 1;
	        foreach ($valor_adicional_formatado[1] as $key => $value) {
	            $key = $key;
	            echo "<tr id='mostrar_adicional_$s'>";
	            echo "<td width='200'>";
	            echo "<input type='text' name='custo_adicional_outros_$s' rel='outros_servicos' value='$key' class='frm' $bloqueia_valores>";
	            echo "</td>";
	            echo "<td>";
	            echo "<input type='text' name='valor_adicional_outros_$s' rel='outros_valores' value='$value' size='5' class='frm novo_valor_adicional' $bloqueia_valores>";
	            echo "</tr>";
	            $s++;
	        }
	    }else{
	        echo "<tr id='mostrar_adicional_1'>";
	        echo "<td>";
	        echo "<input type='text' name='custo_adicional_outros_1' rel='outros_servicos' value='' onkeyup='retiraAcentos(this)' class='frm' $bloqueia_valores>";
	        echo "</td>";
	        echo "<td>";
	        echo "<input type='text' name='valor_adicional_outros_1' rel='outros_valores' value='' size='5' class='frm novo_valor_adicional' $bloqueia_valores>";
	        echo "</tr>";
	    }
	    echo "</tbody>";

	    echo "</table>";
	}
}
//FIM se tiver o defeito reclamado ativo
?>

<?
//caso nao achar defeito reclamado

if (!in_array($login_fabrica,array(3,19,24,35,59,115,120,201))){
	echo "<table width='700' class='formulario' border='0' cellspacing='5' cellpadding='0'>";
	//HD17683
	if($pedir_defeito_reclamado_descricao == 't'){
		if ($login_fabrica <> 2 and $login_fabrica <> 40 ) {
			echo "<tr>";
			echo "<td valign='top' align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><br>";
			if(strpos($sua_os,'-') == FALSE){//SE FOR DE CONSUMIDOR
				if(strlen($defeito_reclamado_descricao) > 0 ){
					if($defeito_reclamado_descricao == 'null' and !empty($defeito_reclamado)) {
						$sql = "SELECT tbl_defeito_reclamado.defeito_reclamado,
										tbl_defeito_reclamado.descricao
								from tbl_defeito_reclamado
								where tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado";
						$res = pg_query($con, $sql);
						$descricao_defeito_reclamado = pg_fetch_result($res,0,'descricao');
						echo "<div style='size:11px'><b>$descricao_defeito_reclamado</b></div>";
					}else{
						echo "<div style='size:11px'><b>$defeito_reclamado_descricao</b></div>";
					}
					echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
					echo "<INPUT TYPE='hidden' name='defeito_reclamado_descricao' value='$defeito_reclamado_descricao'>";
				}else{
					echo "<INPUT TYPE='text' class='frm'  name='defeito_reclamado_descricao' value='$defeito_reclamado_descricao'>";
					echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";

                    if($login_fabrica == 72){
                        echo "<INPUT TYPE='hidden' name='defeito_reclamado_descricao_anterior' value='$defeito_reclamado_descricao'>";
                    }

				}
			}else{//SE FOR DE REVENDA
				if(strlen($defeito_reclamado_descricao) == 0 ){
					echo "<INPUT TYPE='text'  class='frm' name='defeito_reclamado_descricao' value='$defeito_reclamado_descricao'>";
					echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
				}else{
					echo "<div style='size=11px'><b>$defeito_reclamado_descricao</b></div>";
					echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
					echo "<INPUT TYPE='hidden' name='defeito_reclamado_descricao' value='$defeito_reclamado_descricao'>";
				}
			}
			echo "</td>";
		}else{
			if($login_fabrica <> 40){
				echo "<td valign='top' align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><br>";
				echo "<select name='defeito_reclamado' class='frm' style='width:220px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);'";
				if($login_fabrica==19) echo "onchange='window.location=\"$PHP_SELF?os=$os&defeito_reclamado=\"+this.value'";
				echo ">";
				echo "<option id='opcoes' value=''></option>";
				if( $login_fabrica==6 or $login_fabrica ==2){
					$sql = "SELECT tbl_defeito_reclamado.defeito_reclamado,
									tbl_defeito_reclamado.descricao
							from tbl_defeito_reclamado
							where tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado";
					$res = pg_query($con, $sql);
					for ($y = 0 ; $y < pg_num_rows ($res) ; $y++ ) {
						$hdefeito_reclamado = pg_fetch_result ($res,$y,defeito_reclamado) ;
						$hdescricao         = pg_fetch_result ($res,$y,descricao) ;

						echo "<option value='$hdefeito_reclamado'"; if($defeito_reclamado==$hdefeito_reclamado) echo "selected"; echo ">$hdescricao</option>";
					}
				}
				echo "</select>";
				echo "</td>";
			}
		}
	}
	if( in_array($login_fabrica, array(11,74,172)) ) { // HD 139620
		echo "<td nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><br>";

		$sql = "SELECT 	DISTINCT(tbl_diagnostico.defeito_reclamado),
					tbl_defeito_reclamado.descricao
				FROM tbl_diagnostico
				JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado
				JOIN tbl_produto ON tbl_diagnostico.linha = tbl_produto.linha AND tbl_diagnostico.familia = tbl_produto.familia
				JOIN tbl_os ON tbl_os.produto = tbl_produto.produto
				WHERE tbl_diagnostico.fabrica=$login_fabrica
				AND   tbl_defeito_reclamado.ativo='t' and tbl_diagnostico.ativo='t'
				AND   tbl_os.os = $os
				AND   tbl_os.fabrica = $login_fabrica";
		if ($login_fabrica == 74) {
			$sql = "SELECT defeito_reclamado,
					descricao
				FROM tbl_defeito_reclamado
				WHERE fabrica = $login_fabrica
				AND ativo";

		}
		$resD = pg_query ($con,$sql);

		if (@pg_num_rows ($resD) > 0 ) {
			echo "<select name='defeito_reclamado' size='1' class='frm'>";
			echo "<option value=''></option>";
			for ($i = 0 ; $i < pg_num_rows ($resD) ; $i++ ) {
				echo "<option ";
				if ($defeito_reclamado == pg_fetch_result ($resD,$i,defeito_reclamado) ) echo " selected ";
				echo " value='" . pg_fetch_result ($resD,$i,defeito_reclamado) . "'>" ;
				echo pg_fetch_result ($resD,$i,descricao) ;
				echo "</option>";
			}
			echo "</select>";
		}
		echo "</td>";
	}
	$xdefeito_reclamado;
	//CONSTATADO
	echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$tema</font><BR>";
	echo "<select name='defeito_constatado' id='defeito_constatado'  class='frm defeito_constatado' style='width: 220px;'";
	if(!in_array($login_fabrica, array(30,45,46,74,95,90,99,91)) and $login_fabrica < 104) {
		echo "onfocus='listaConstatado(document.frm_os.xxproduto_linha.value, document.frm_os.xxproduto_familia.value,document.frm_os.defeito_reclamado.value,this);' >";
	}else{
		echo "' >";
	}

	if( in_array($login_fabrica,array(11,30,40,45,46,72,74,90,91,95,99)) or $login_fabrica >= 104 ){
		$sql = "SELECT 	distinct(tbl_diagnostico.defeito_constatado),
						tbl_defeito_constatado.descricao            ,
						tbl_defeito_constatado.codigo
				FROM tbl_diagnostico
				JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.ativo
				WHERE ";

		if ((!in_array($login_fabrica,array(46,74,99,104,105)) and $login_fabrica < 104) OR $login_fabrica == 140){
			$sql .= "
				 tbl_diagnostico.linha = $produto_linha AND ";
		}

		$sql .="
				 tbl_diagnostico.ativo='t' ";
		if (strlen($produto_familia)>0 AND $login_fabrica <> 140) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
		$sql.=" ORDER BY tbl_defeito_constatado.descricao";

		$res = pg_query($con,$sql);

		echo "<option value='' id='opcoes2'></option>";

		for ($y = 0 ; $y < pg_num_rows ($res) ; $y++ ) {
			$xxdefeito_constatado = pg_fetch_result ($res,$y,defeito_constatado) ;
			$defeito_constatado_descricao = pg_fetch_result ($res,$y,descricao) ;
			$defeito_constatado_codigo    = pg_fetch_result ($res,$y,codigo) ;

			$defeito_constatado_desc = ($login_fabrica <> 90 && $login_fabrica <> 45) ? $defeito_constatado_codigo." - ".$defeito_constatado_descricao : $defeito_constatado_descricao;
			$selected = ($defeito_constatado == $xxdefeito_constatado) ? "SELECTED" : null;

			if(in_array($login_fabrica, [131,134,144])) {
				$attr = "codigo='$defeito_constatado_codigo'";
			}else{
				$attr = "";
			}

			echo "<option $attr value='$xxdefeito_constatado' $selected >$defeito_constatado_desc</option>";
		}
	}

	if( $login_fabrica==6){
		$defeito_reclamado = strlen($defeito_reclamado) > 0 ? "AND tbl_diagnostico.defeito_reclamado = $defeito_reclamado " : "";

		$sql = "SELECT 	distinct(tbl_diagnostico.defeito_constatado),
						tbl_defeito_constatado.descricao
				FROM tbl_diagnostico
				JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
				WHERE tbl_diagnostico.linha = $produto_linha
				$defeito_reclamado
				AND tbl_defeito_constatado.ativo='t' ";
		if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
		$sql.=" ORDER BY tbl_defeito_constatado.descricao";

		$res = pg_query($con,$sql);
			for ($y = 0 ; $y < pg_num_rows ($res) ; $y++ ) {
			$xxdefeito_constatado = pg_fetch_result ($res,$y,defeito_constatado) ;
			$defeito_constatado_descricao = pg_fetch_result ($res,$y,descricao) ;

			echo "<option value='$xxdefeito_constatado'"; if($defeito_constatado==$xxdefeito_constatado) echo "selected"; echo ">$defeito_constatado_descricao</option>";
		}
	}

	if($login_fabrica==50 || $login_fabrica==51){
		$sql = "SELECT 	distinct(tbl_diagnostico.defeito_constatado),
						tbl_defeito_constatado.descricao            ,
						tbl_defeito_constatado.codigo
				FROM tbl_diagnostico
				JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
				WHERE tbl_diagnostico.linha = $produto_linha
				AND tbl_diagnostico.ativo='t' ";
		//if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
		$sql.=" ORDER BY tbl_defeito_constatado.descricao";
		$res = pg_query($con,$sql);



		echo "<option value='' id='opcoes2'></option>";
		for ($y = 0 ; $y < pg_num_rows ($res) ; $y++ ) {
			$xxdefeito_constatado = pg_fetch_result ($res,$y,defeito_constatado) ;
			$defeito_constatado_descricao = pg_fetch_result ($res,$y,descricao) ;
			$defeito_constatado_codigo    = pg_fetch_result ($res,$y,codigo) ;

			echo "<option $attr value='$xxdefeito_constatado'"; if($defeito_constatado==$xxdefeito_constatado) echo "selected"; echo ">$defeito_constatado_codigo - $defeito_constatado_descricao</option>";
		}
	}

	/* if( in_array($login_fabrica, array(11,172)) ){
		echo "<option value='' id='opcoes2'></option>";
	} */

	echo "</select>";

	if(in_array($login_fabrica, [131,134,144])) {
		echo "&nbsp;<input type='button' onclick=\"javascript: adicionaIntegridade4()\" value='Adicionar' name='btn_adicionar' style='width:70px; font:bold 9px;'><br>";
	}
    echo "</td>";

    if ($login_fabrica == 72) {//fputti hd-3130038
        if(strlen(trim($defeito_constatado)) > 0){
            $cond = " AND  tbl_diagnostico.defeito_constatado = {$defeito_constatado} ";
        }
        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Falha em Potencial</font><BR>";
        echo "<select name='falha_em_potencial' id='falha_em_potencial'  class='frm' style='width: 220px;'>";
            $sql = "SELECT
                    tbl_servico.descricao AS nome_falha,
                    tbl_defeito_constatado.descricao AS nome_defeito,
                    tbl_diagnostico.diagnostico,
                    tbl_diagnostico.defeito_constatado,
                    tbl_diagnostico.servico
            FROM    tbl_diagnostico
            JOIN tbl_servico ON tbl_servico.servico=tbl_diagnostico.servico
            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado=tbl_diagnostico.defeito_constatado
            WHERE tbl_diagnostico.fabrica = $login_fabrica
            $cond
            ORDER BY tbl_diagnostico.diagnostico DESC;";

        $res   = pg_query($con,$sql);
        $qtd   = pg_num_rows($res);

        if ($qtd > 0) {
            echo "<option value=''> Selecione ...</option>";
            for ($i=0; $i < $qtd; $i++) {
                $servico    = pg_fetch_result($res, $i, servico);
                $nome_falha = pg_fetch_result($res, $i, nome_falha);
                $selected = ($falha == $servico) ? 'selected="selected"' : '';
                echo "<option value='".$servico."' ".$selected.">".trim($nome_falha)."</option>";
            }
        } else {
            echo "<option value=''> Nenhuma Falha em Potencial encontrada.</option>";
        }
        echo "</select>";
        echo "</td>";
    }

	if($login_fabrica == 131){
        ?>
        <td>
            Causa<br />
            <select onfocus="atualizaCausas()" name='causa_defeito' id='causa_defeito' class='frm' >

                <?php

                $sql = "SELECT causa_defeito, tbl_causa_defeito.descricao from tbl_os join tbl_causa_defeito using(causa_defeito) where os = $os";
                $resCausaDefeito = pg_query($con,$sql);

                if(pg_num_rows($resCausaDefeito) > 0){
                    echo "<option value='".pg_fetch_result($resCausaDefeito,0,causa_defeito)."'>".pg_fetch_result($resCausaDefeito,0,descricao)."</option>";
                }else{
                    echo "<option>Selecione uma opção</option>";
                }
                ?>
            </select>
        </td>
        <?php
    }

	if($login_fabrica == 117){
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solução</font><BR>";
				echo "<select name='solucao_os' class='frm'  style='width:250px;' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);' >";
				echo "<option id='opcoes' value=''></option>";
				if(empty($solucao_os)){
					$cond_solucao = " AND ativo IS TRUE";
				} else {
					$cond_solucao = " AND solucao=$solucao_os";
				}

				$sql = "SELECT 	solucao,
								descricao
						FROM tbl_solucao
						WHERE fabrica=$login_fabrica
						$cond_solucao";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){
					for($i = 0; $i < pg_num_rows($res); $i++){
						$solucao_descricao = pg_fetch_result ($res,$i,descricao);
						$solucao_id        = pg_fetch_result ($res,$i,solucao);

						echo "<option id='opcoes' value='$solucao_os'";
						echo ($solucao_id == $solucao_os) ? "selected" : "";
						echo ">$solucao_descricao</option>";
					}
				}
				echo "</select>";

			echo "</td>";
		}
	//CONSTATADO
	//SOLUCAO

	if((!in_array($login_fabrica,array(30,40,46,99)) && $login_fabrica < 104) || in_array($login_fabrica, array(172))){
		echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solução</font><BR>";
		echo "<select name='solucao_os' class='frm solucao'  style='width:250px;' id='solucao'";
		if ($login_fabrica <> 74){
			if ($login_fabrica <> 90){
				echo" onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);' >";
			}else{
				echo" onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, \"\", document.frm_os.xxproduto_familia.value);' >";
			}
		}
		//echo "<option id='opcoes' value=''></option>";

		echo "<option id='opcoes' value=''></option>";

		$sql = "SELECT 	solucao,
						descricao
				FROM tbl_solucao
				WHERE fabrica=$login_fabrica
				$cond_solucao";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){
			for($i = 0; $i < pg_num_rows($res); $i++){
				$solucao_descricao = pg_fetch_result ($res,$i,descricao);
				$solucao_id        = pg_fetch_result ($res,$i,solucao);

				echo "<option id='opcoes' value='$solucao_id'";
				echo ($solucao_id == $solucao_os) ? "selected" : "";
				echo ">$solucao_descricao</option>";
			}
		}

		//takashi 19-06 hd 2814
		echo "</select>";
		echo "</td>";

		//SOLUCAO
		echo "</tr>";
	}

    if ($login_fabrica == 30) {
        $sql = "SELECT solucao, descricao FROM tbl_solucao WHERE fabrica = {$login_fabrica} AND solucao IN(242, 244, 549, 5684) ORDER BY descricao";
        $res = pg_query($con, $sql);

        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solução</font><BR>";
        echo "<select name='solucao_os' class='frm solucao'  style='width:250px;' id='solucao'>";
        echo "<option value=''></option>";
        for ($i=0; $i < pg_num_rows($res); $i++) {
            $descricao_solucao = pg_fetch_result($res, $i, "descricao");
            $solucao   = pg_fetch_result($res, $i, "solucao");
            if(mb_detect_encoding($descricao_solucao, 'utf-8', true)){
                $descricao_solucao = utf8_decode($descricao_solucao);
            }
            $seleted = ($solucao_os == $solucao) ? 'selected' : '';
            echo "<option value='{$solucao}' {$seleted}>{$descricao_solucao}</option>";
        }
        echo "</select>";
        echo "</td >";
        echo "<td id='td_cor_etiqueta' ><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Cor da Etiqueta</font> <br>
            <input type='hidden' name='pedir_cor_etiqueta' id='pedir_cor_etiqueta' value=''>
            <select name='cor_etiqueta' id='cor_etiqueta' class='frm'>
                <option value=''>  </option>";

                $sqlFor = "SELECT tbl_fornecedor.fornecedor,
                    tbl_fornecedor.campos_adicionais,
                    tbl_fornecedor.nome  
                        FROM tbl_fornecedor 
                        JOIN tbl_fornecedor_fabrica using(fornecedor) 
                        WHERE tbl_fornecedor_fabrica.fabrica = $login_fabrica 
                        ";
                $resFor = pg_query($con, $sqlFor); 

                for($f=0; $f<pg_num_rows($resFor); $f++){
                    $nome = pg_fetch_result($resFor, $f, 'nome');
                    $campos_adicionais = json_decode(pg_fetch_result($resFor, $f, 'campos_adicionais'),true);
                    $cor_etiqueta_id = $campos_adicionais['cor_etiqueta'];
                    
                    $sqlCor = "SELECT nome_cor FROM tbl_cor WHERE cor = $cor_etiqueta_id AND fabrica = $login_fabrica";
                    $resCor = pg_query($con, $sqlCor);
                    $cor_etiqueta = pg_fetch_result($resCor, 0, 'nome_cor');

                    $fornecedor = pg_fetch_result($resFor, $f, 'fornecedor');

                    /*$cor_etiqueta = str_replace("_", " ", $cor_etiqueta);
                    $cor_etiqueta = ucwords($cor_etiqueta);*/

                    echo "<option value='$fornecedor'> $cor_etiqueta </option>";
                }
            echo "</select>";

            echo "</td>";
        echo "</tr>";
    }

    if ($login_fabrica == 72) {
        $solucoesPermitidas =  array(3042, 3043, 3044, 3045, 3046);
        $disply = (in_array($solucao_os, $solucoesPermitidas)) ? "" :  "none";
        echo '
            <tr id="ajuste_realizado" style="display:'.$disply.';">
                <td colspan="4" align="center">
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Ajuste Realizado</font><BR>
                    <textarea class="frm" style="width:80%;" name="ajusterealizado" id="ajusterealizado"></textarea>
                </td>
            </tr>
        ';

    }

	if(in_array($login_fabrica, [131,134,144])) {
		$sql = "SELECT  array_to_string(array_agg(codigo), '\",\"')
		    FROM tbl_os_defeito_reclamado_constatado
		    JOIN tbl_defeito_constatado USING (defeito_constatado)
		    WHERE os = $os";
		$resd = pg_query($con,$sql);
		if(pg_num_rows($resd) > 0) {
			$defeito_constatados = pg_fetch_result($resd,0,0);
		}
        echo "<input type='hidden' name='defeito_constatado_hidden' id='defeito_constatado_hidden' value='[\"$defeito_constatados\"]' />";
    }

	if(in_array($login_fabrica, [131,134,144])){
		echo '<tr>
		<td colspan="2"><table style="display: inline;" align="center" width="100%" border="0" id="tbl_integridade" cellspacing="3" cellpadding="3">
            		<thead>
            		<tr bgcolor="#596D9B" style="color:#FFFFFF;">
            			<td align="center"><b>'.$tema.'</b></td>
            			<td align="center"><b>Ações</b></td></tr>
            		<thead>
            		<tbody>';
		$sql_cons = "SELECT
	                defeito_constatado_reclamado,
	                tbl_defeito_constatado.defeito_constatado,
	                tbl_defeito_constatado.descricao         ,
	                tbl_defeito_constatado.codigo,
	                tbl_solucao.solucao,
	                tbl_solucao.descricao as solucao_descricao
	        FROM tbl_os_defeito_reclamado_constatado
	        JOIN tbl_defeito_constatado USING(defeito_constatado)
	        LEFT JOIN tbl_solucao USING(solucao)
	        WHERE os = $os";


	    $res_dc = pg_query($con, $sql_cons);
	    if(pg_num_rows($res_dc) >0){
	    	$aa = 0;
	    	for ($x = 0; $x < pg_num_rows($res_dc); $x++) {
	    		$id_defeito_constatado_reclamado = pg_fetch_result($res_dc, $x, 'defeito_constatado_reclamado');
				$dc_defeito_constatado           = pg_fetch_result($res_dc, $x, 'defeito_constatado');
				$dc_descricao         = pg_fetch_result($res_dc, $x, 'descricao');
	            $dc_codigo            = pg_fetch_result($res_dc, $x, 'codigo');
				echo "<tr>";
				echo "<td><font size='1'><input type='hidden' name='integridade_defeito_constatado_$aa' value='$dc_defeito_constatado'>$dc_codigo-$dc_descricao</font></td>";
				echo "<td align='right'><input type='button' onclick='removerIntegridade(this,$id_defeito_constatado_reclamado);' value='Excluir' ></td>";
				echo "</tr>";
				$aa += 1;
	    	}

	    }

         echo '</tbody>
            	</table>
            	</td>
        		</tr>';
	}

	echo "</table>";
}
//fim caso nao achar defeito reclamado
//HD17683


if (in_array($login_fabrica, [2, 30, 59])) {

/*	echo "<input type='button' onclick=\"javascript: verificaDuplicidade($('#defeito_constatado').val(), $('#solucao').val()); adicionaIntegridade(); listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);
         \" value='Adicionar Defeito' name='btn_adicionar'><br>";*/

    echo "<input type='button' onclick=\"javascript: verificaDuplicidade($('#defeito_constatado').val(), $('#solucao').val(), document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value, document.frm_os.xxproduto_familia.value); \" value='Adicionar Defeito' name='btn_adicionar'><br>";

	echo "
	<table style=' border:#485989 1px solid; background-color: #e6eef7;font-size:12px;display:none' align='center' width='700' border='0' id='tbl_integridade' cellspacing='3' cellpadding='3'>
	<thead>
	<tr bgcolor='#596D9B' style='color:#FFFFFF;'>
	<td align='center'><b>$tema</b></td>";
	
	echo "<td align='center'><b>Solução</b></td>";

    if($login_fabrica == 30){
        echo "<td align='center'><b>Cor Etiqueta</b></td>";    
    }
	
	echo "<td align='center'><b>Ações</b></td>
	</tr>
	</thead>
	<tbody>";
    
    $temDefeitoConstatado = false;
    
    $i = 1;

    while ($i != 200) {

        if (isset($_POST['integridade_defeito_constatado_' . $i])) {

            $temDefeitoConstatado = true;

            break;
        }

        $i++;
    }

    if ($temDefeitoConstatado) {

        $i = 1;

        $sql_cor_etiqueta = "SELECT (select nome from tbl_fornecedor join tbl_fornecedor_fabrica using(fornecedor) where tbl_fornecedor_fabrica.fabrica = 30 and tbl_fornecedor.fornecedor = (tbl_os_campo_extra.campos_adicionais::JSON->>'cor_etiqueta_fornecedor')::int ) as cor_etiqueta 
        FROM tbl_os_campo_extra 
        where os = $os ";
        $res_cor_etiqueta = pg_query($con, $sql_cor_etiqueta);

        if(pg_num_rows($res_cor_etiqueta)>0){
            $cor_etiqueta_fornecedor = pg_fetch_result($res_cor_etiqueta, 0, 'cor_etiqueta');
        }

        while ($i != 200) {


            if (isset($_POST['integridade_defeito_constatado_' . $i])) {

                $post_defeito = $_POST['integridade_defeito_constatado_' . $i]; 


                if($login_fabrica == 30){
                    $cod_cor_etiqueta_fornecedor    = null; 
                    $celular_cor_etiqueta           = null;                
                    if(isset($_POST['integridade_cor_etiqueta_'.$i]) ){   
                        
                        $cod_cor_etiqueta_fornecedor = $_POST['integridade_cor_etiqueta_'.$i]; 
                        $sqlFornecedor = "SELECT tbl_fornecedor.fornecedor,
                                            tbl_fornecedor.campos_adicionais,
                                            tbl_fornecedor.nome  
                                        FROM tbl_fornecedor 
                                        JOIN tbl_fornecedor_fabrica using(fornecedor) 
                                        WHERE tbl_fornecedor_fabrica.fabrica = $login_fabrica
                                        and tbl_fornecedor.fornecedor = ". $cod_cor_etiqueta_fornecedor ;
                        $resFornecedor = pg_query($con, $sqlFornecedor);

                        for($fo=0; $fo<pg_num_rows($resFornecedor); $fo++){
                            $nome = pg_fetch_result($resFornecedor, $fo, 'nome');
                            $campos_adicionais = json_decode(pg_fetch_result($resFornecedor, $fo, 'campos_adicionais'),true);
                            $cor_etiqueta_id = $campos_adicionais['cor_etiqueta'];

                            $sqlCor = "SELECT nome_cor FROM tbl_cor WHERE cor = $cor_etiqueta_id AND fabrica = $login_fabrica";
                            $resCor = pg_query($con, $sqlCor);
                            $cor_etiqueta = pg_fetch_result($resCor, 0, 'nome_cor');

                            $fornecedor = pg_fetch_result($resFornecedor, $fo, 'fornecedor');

                            /*$cor_etiqueta = str_replace("_", " ", $cor_etiqueta);
                            $cor_etiqueta = ucwords($cor_etiqueta);*/

                            $celular_cor_etiqueta  = "$cor_etiqueta";
                        }
                    }
                }


                $query = "SELECT descricao, codigo FROM tbl_defeito_constatado WHERE defeito_constatado = $post_defeito";

                $res = pg_query($con, $query); 
                
                $descricao_solucao = pg_fetch_result($res, 0, descricao);
                $descricao_codigo = pg_fetch_result($res, 0, codigo);
                
                echo "<tr>";
                echo "<td align='left'><font size='1'><input type='hidden' id='integridade_defeito_constatado_$i' name='integridade_defeito_constatado_$i' value='$post_defeito'> $descricao_codigo - $descricao_solucao</font></td>";

                $post_solucao = $_POST["integridade_solucao_" . $i]; 

                $sql_solucao = "SELECT descricao FROM tbl_solucao WHERE solucao = $post_solucao";

                $res_solucao = pg_query($con, $sql_solucao);

                $sl_descricao = pg_fetch_result($res_solucao, 0, descricao);

                echo "<td align='left'><font size='1'><input type='hidden' name='integridade_solucao_$i' value='$post_solucao'>$sl_descricao</font></td>";


                if($login_fabrica == 30){
                    if(strlen($cod_cor_etiqueta_fornecedor)>0){
                        echo "<td align='left'><font size='1'><input type='hidden' id='integridade_cor_etiqueta_{$i}' name='integridade_cor_etiqueta_$i' value='$cod_cor_etiqueta_fornecedor'>$celular_cor_etiqueta</font></td>";                    
                    }else{
                        echo "<td align='left'></td>";                    
                    }                    
                }
                
                echo "<td align='right'><input type='button' onclick='removerIntegridade(this);' rel='{$i}' name='btn_excluir_defeito' value='Excluir'></td>";
                echo "</tr>";
            }

            $i++;
        }
        
        echo "<script>document.getElementById('tbl_integridade').style.display = \"inline\";</script>";

    } else {

        if($login_fabrica == 30){
            $campo_campos_adicionais = " tbl_os_defeito_reclamado_constatado.campos_adicionais ,  "; 
        }

    	$sql_cons = "SELECT
    					tbl_defeito_constatado.defeito_constatado,
    					tbl_defeito_constatado.descricao         ,
    					tbl_defeito_constatado.codigo,
                        $campo_campos_adicionais
    					tbl_os_defeito_reclamado_constatado.solucao
    			FROM tbl_os_defeito_reclamado_constatado
    			JOIN tbl_defeito_constatado USING(defeito_constatado)
    			WHERE os = $os";
    	$res_dc = pg_query($con, $sql_cons);
    	if(pg_num_rows($res_dc) > 0){
    		for($x=0;$x<pg_num_rows($res_dc);$x++){
    			$dc_defeito_constatado = pg_fetch_result($res_dc,$x,defeito_constatado);
    			$dc_descricao = pg_fetch_result($res_dc,$x,descricao);
    			$dc_solucao = pg_fetch_result($res_dc,$x,solucao);
    			$dc_codigo = pg_fetch_result($res_dc,$x,codigo);
                $campos_adicionais = json_decode(pg_fetch_result($res_dc, $x, 'campos_adicionais'), true);

    			$aa = $x+1;

                $cod_cor_etiqueta_fornecedor    = null; 
                $celular_cor_etiqueta           = null;
                if(isset($campos_adicionais['cor_etiqueta_fornecedor'])){                   

                    $cod_cor_etiqueta_fornecedor = $campos_adicionais['cor_etiqueta_fornecedor']; 
                    $sqlFornecedor = "SELECT tbl_fornecedor.fornecedor,
                                        tbl_fornecedor.campos_adicionais,
                                        tbl_fornecedor.nome  
                                    FROM tbl_fornecedor 
                                    JOIN tbl_fornecedor_fabrica using(fornecedor) 
                                    WHERE tbl_fornecedor_fabrica.fabrica = $login_fabrica
                                    and tbl_fornecedor.fornecedor = ". $cod_cor_etiqueta_fornecedor ;
                    $resFornecedor = pg_query($con, $sqlFornecedor);

                    for($fo=0; $fo<pg_num_rows($resFornecedor); $fo++){
                        $nome = pg_fetch_result($resFornecedor, $fo, 'nome');
                        $campos_adicionais = json_decode(pg_fetch_result($resFornecedor, $fo, 'campos_adicionais'),true);
                        $cor_etiqueta_id = $campos_adicionais['cor_etiqueta'];

                        $sqlCor = "SELECT nome_cor FROM tbl_cor WHERE cor = $cor_etiqueta_id AND fabrica = $login_fabrica";
                        $resCor = pg_query($con, $sqlCor);
                        $cor_etiqueta = pg_fetch_result($resCor, 0, 'nome_cor');

                        $fornecedor = pg_fetch_result($resFornecedor, $fo, 'fornecedor');

                        /*$cor_etiqueta = str_replace("_", " ", $cor_etiqueta);
                        $cor_etiqueta = ucwords($cor_etiqueta);*/

                        $celular_cor_etiqueta  = "$cor_etiqueta";
                    }
                }

    			echo "<tr>";
    			echo "<td align='left'><font size='1'><input type='hidden' id='integridade_defeito_constatado_{$aa}' name='integridade_defeito_constatado_$aa' value='$dc_defeito_constatado'>$dc_codigo - $dc_descricao</font></td>";                

    			if(in_array($login_fabrica, [2, 30, 59]) and strlen($dc_solucao) > 0){
    				$sql_solucao = "select descricao from tbl_solucao where solucao=$dc_solucao";
    				$res_solucao = pg_query($con, $sql_solucao);

    				$sl_descricao = pg_fetch_result($res_solucao,0,descricao);

    				echo "<td align='left'><font size='1'><input type='hidden' id='integridade_solucao_{$aa}' name='integridade_solucao_$aa' value='$dc_solucao'>$sl_descricao</font></td>";
    			}

                if($login_fabrica == 30){
                    if(strlen($cod_cor_etiqueta_fornecedor)>0){
                        echo "<td align='left'><font size='1'><input type='hidden' id='integridade_cor_etiqueta_{$aa}' name='integridade_cor_etiqueta_$aa' value='$cod_cor_etiqueta_fornecedor'>$celular_cor_etiqueta</font></td>";                    
                    }else{
                        echo "<td align='left'></td>";                    
                    }                    
                }

    			echo "<td align='right'><input type='button' onclick='removerIntegridade(this);' rel='{$aa}' id='btn_excluir_defeito' value='Excluir'></td>";
    			echo "</tr>";
    		}

    		echo "<script>document.getElementById('tbl_integridade').style.display = \"inline\";</script>";
    	}
    }
	echo "</tbody></table>";
}


//HD 23041
if($login_fabrica==19){

            $exibeCondicao = "none";
            $xchecked1 = "";
            $xchecked2 = "";
            $xchecked3 = "";
            $xdisabled = "";
            $sqlEXT = "SELECT campos_adicionais  FROM tbl_os_campo_extra WHERE os=$os";
            $resEXT = pg_query($con,$sqlEXT);
            if (pg_num_rows($resEXT) > 0) {
                $campos_adicionais = json_decode(pg_fetch_result($resEXT, 0, 'campos_adicionais'),1);
                if (isset($campos_adicionais["condicao_instalacao"]) && $campos_adicionais["condicao_instalacao"] != "null") {
                    
                    $xcondicao_instalacao = utf8_decode($campos_adicionais["condicao_instalacao"]);
                    $exibeCondicao = "block";

                    $sql_cons = "SELECT DISTINCT
                                        DR.defeito_reclamado,
                                        DR.descricao AS dr_descricao, 
                                        RC.defeito_reclamado,
                                        DC.defeito_constatado,
                                        DC.descricao AS dc_descricao,
                                        DC.codigo AS dc_codigo
                                   FROM tbl_os_defeito_reclamado_constatado RC
                                   JOIN tbl_defeito_reclamado DR ON DR.defeito_reclamado  = RC.defeito_reclamado
                                   JOIN tbl_defeito_constatado DC ON DC.defeito_constatado = RC.defeito_constatado
                                  WHERE RC.os = $os";
                    $res_dr = pg_query($con, $sql_cons);
                    if (pg_num_rows($res_dr) > 0) {

                        $codigo_rc = pg_fetch_result($res_dr, 0, 'dc_codigo');
                        if ($codigo_rc == '554') {
                            $xdisabled = "disabled";
                        }
                        if ($xcondicao_instalacao == "Instalação sem Defeito") {
                            $xchecked1 = "checked";
                            $exibeCondicao = "block";
                        } elseif ($xcondicao_instalacao == "Não Avaliado") {
                            $xchecked2 = "checked";
                            $exibeCondicao = "block";
                        } elseif ($xcondicao_instalacao == "Defeito na Instalação") {
                            $xchecked3 = "checked";
                            $exibeCondicao = "block";
                            $xdisabled = "disabled";
                        }

                    }

                }

            }



            if ($_POST["condicao_instalacao"] == "Instalação sem Defeito") {
                $xchecked1 = "checked";
                $exibeCondicao = "block";
            } elseif ($_POST["condicao_instalacao"] == "Não Avaliado") {
                $xchecked2 = "checked";
                $exibeCondicao = "block";
            } elseif ($_POST["condicao_instalacao"] == "Defeito na Instalação") {
                $xchecked3 = "checked";
                $exibeCondicao = "block";
                if ($xcondicao_instalacao == "Defeito na Instalação") {
                    $xdisabled = "disabled";
                }
            } else if (count($_POST["i_defeito_constatado"]) > 0) {
                $exibeCondicao = "block";
            }

    if (verifica_checklist_tipo_atendimento($xtipo_atendimento)) {
        echo "<table style=' border:#485989 1px solid; font-size:12px;' align='center' width='700' border='0' cellspacing='3' cellpadding='3' bgcolor='#e6eef7'>
                <tbody>
                    <tr>
                        <td align='right'>
                            <div class='mostra_condicao' style='text-align:left;font-size: 12px;display:".$exibeCondicao.";'>
                                     Condição de Instalação<br />
                                    <input type='radio' data-tipo='instalacao_sem_defeito' {$xdisabled} {$xchecked1} name='condicao_instalacao' value='Instalação sem Defeito' id='instalacao_sem_defeito' /> <label for='instalacao_sem_defeito'>Instalação sem Defeito</label><br />
                                    <input type='radio' data-tipo='nao_avaliado' {$xdisabled} {$xchecked2} name='condicao_instalacao' value='Não Avaliado' id='nao_avaliado' /> <label for='nao_avaliado'>Não Avaliado</label><br />
                                    <input type='radio' data-tipo='defeito_na_instalacao' {$xchecked3} name='condicao_instalacao' value='Defeito na Instalação' id='defeito_na_instalacao' /> <label for='defeito_na_instalacao'>Defeito na Instalação</label><br />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>";
    }

	$sql = "SELECT defeito_reclamado
				FROM tbl_os_defeito_reclamado_constatado
				WHERE os                 = $os LIMIT 1";
		$res = @pg_query ($con,$sql);
		if(pg_num_rows($res)==0){
			$sql = "SELECT defeito_reclamado FROM tbl_os WHERE os=$os";
			$res = @pg_query ($con,$sql);
			if(pg_num_rows($res)>0){
				$aux_defeito_reclamado = pg_fetch_result($res,0,0);
				$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
					os,
					defeito_reclamado,
					fabrica
				)VALUES(
					$os,
					$aux_defeito_reclamado,
					$login_fabrica
				)";
				$res = @pg_query ($con,$sql);
			}
		}

	echo "<table style=' border:#485989 1px solid; font-size:12px;' align='center' width='700' border='0' cellspacing='3' cellpadding='3' bgcolor='#e6eef7'>";
	echo "<thead>";
	echo "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
	echo "<td align='center'><b>Defeito Reclamado</b></td>";
	echo "<td align='center'><b>$tema</b></td>";
	echo "<td align='center'><b>Adicionar</b></td>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";
	$sql_cons = "SELECT DISTINCT
					DR.defeito_reclamado                  ,
					DR.descricao           AS dr_descricao
			FROM tbl_os_defeito_reclamado_constatado RC
			LEFT JOIN tbl_defeito_reclamado          DR ON DR.defeito_reclamado  = RC.defeito_reclamado
			WHERE RC.os = $os
			AND   RC.defeito_reclamado IS NOT NULL";
	$res_dr = pg_query($con, $sql_cons);
    $defeitosHidden = array(); //hd_chamado=2881143
	if(pg_num_rows($res_dr) > 0){
		for($x=0;$x<pg_num_rows($res_dr);$x++){
			$dr_defeito_reclamado  = pg_fetch_result($res_dr,$x,defeito_reclamado);
			$dr_descricao          = pg_fetch_result($res_dr,$x,dr_descricao);

			$aa = $x+1;

			if($cor=="#FFFFFF") $cor = "#e6eef7";
			else                $cor = "#FFFFFF";

			echo "<tr bgcolor='$cor'>";
			echo "<td valign='top'>";
			echo "<input type='hidden' name='defeito_reclamado[]' id='defeito_reclamado_$aa' value='$dr_defeito_reclamado'>";
			echo "<input type='hidden' name='defeito_reclamado_descricao[]' id='defeito_reclamado_descricao_$aa' value='$dr_descricao'>";
			echo "$dr_descricao";
			echo "</td>";

			echo "<td>";
			//HD 27570 - 21/7/2008
			/*echo "<select name='defeito_constatado_$aa' id='defeito_constatado_$aa' class='frm' style='width: 220px;' onfocus='listaConstatado(document.frm_os.xxproduto_linha.value, document.frm_os.xxproduto_familia.value,document.frm_os.defeito_reclamado_$aa.value,this);' >";
			echo "<option id='opcoes2' value=''></option>";*/
			$sql_consx = "SELECT distinct(tbl_diagnostico.defeito_constatado) AS defeito_constatado,
			tbl_defeito_constatado.descricao                         AS dc_descricao,
			regexp_replace(tbl_defeito_constatado.codigo,'\\D','','g')::integer AS codigo
			FROM tbl_diagnostico
			JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
			WHERE tbl_diagnostico.familia = $familia
			AND tbl_defeito_constatado.ativo='t'
			AND tbl_diagnostico.defeito_reclamado isnull
			ORDER BY regexp_replace(tbl_defeito_constatado.codigo,'\\D','','g')::integer";
			$res_consx = pg_query($con, $sql_consx);
			if(pg_num_rows($res_consx)>0){
			echo "<select name='defeito_constatado_$aa' id='defeito_constatado_$aa' class='frm' style='width: 220px;'>";
				echo "<option value=''></option>";
				for($w=0; $w<pg_num_rows($res_consx); $w++){
					$defeito_constatado = pg_fetch_result($res_consx, $w, defeito_constatado);
					$dc_descricao       = pg_fetch_result($res_consx, $w, dc_descricao);
					$dc_codigo          = pg_fetch_result($res_consx, $w, 'codigo');
					echo "<option value='$defeito_constatado'>$dc_codigo - $dc_descricao</option>";
				}
			echo "</select>";
			}

				echo "<br><table id='tab_defeitos_$aa' name='tab_defeitos_$aa' style='font-size:12px;display:none' width='100%'>";
				echo "<thead><tr><td></td></tr></thead>";
				echo "<tbody>";
				$sql_cons = "SELECT DISTINCT
                                 RC.defeito_reclamado                 ,
								DC.defeito_constatado                 ,
								DC.descricao           AS dc_descricao,
                                DC.codigo AS dc_codigo
						FROM tbl_os_defeito_reclamado_constatado RC
						JOIN tbl_defeito_constatado              DC ON DC.defeito_constatado = RC.defeito_constatado
						WHERE RC.os = $os
						AND   RC.defeito_reclamado = $dr_defeito_reclamado
						AND   RC.defeito_constatado IS NOT NULL";

				$res_dc = pg_query($con, $sql_cons);
                $qtd_result = pg_num_rows($res_dc);
                if (empty($qtd_result) && $login_fabrica == 19) {
                    $sql_defeito = "SELECT tbl_os.defeito_constatado AS defeito_constatado, tbl_defeito_constatado.descricao AS dc_descricao
                                    FROM tbl_os
                                    JOIN tbl_defeito_constatado USING(defeito_constatado)
                                    WHERE tbl_os.os = $os
                                    AND tbl_os.fabrica = $login_fabrica";
                    $res_dc = pg_query($con, $sql_defeito);
                    $qtd_result = pg_num_rows($res_dc);
                }
                if (!isset($_POST['i_defeito_constatado'])) {
    				if($qtd_result > 0){
    					for($y=0;$y<pg_num_rows($res_dc);$y++){
                            $dc_defeito_reclamado = pg_fetch_result($res_dc,$y,defeito_reclamado);
    						$dc_defeito_constatado = pg_fetch_result($res_dc,$y,defeito_constatado);
    						$dc_descricao          = pg_fetch_result($res_dc,$y,dc_descricao);
                            $dc_codigo             = pg_fetch_result($res_dc, $y, 'dc_codigo');
    						$defeitosHidden[] = $dc_defeito_constatado; //hd_chamado=2881143
                            $bb = $y+1;
    						echo "<tr>";
    						echo "<td style='text-align: left; color: #000000;font-size:10px;border-bottom: thin dotted #FF0000'><font size='1'><input type='hidden' data-codigo='{$dc_codigo}' name=\"i_defeito_constatado[$dc_defeito_reclamado][]\" id=\"i_defeito_constatado_".$aa."_".$bb."\" rel=\"defeito_constatado_".$aa."\" value='$dc_defeito_constatado'>$dc_descricao</font></td>";
    						echo "<td align='right'><input type='button' onclick='removerIntegridade2(this,\"tab_defeitos_$aa\",\"$dc_defeito_constatado\");' value='Excluir'></td>";
    						echo "</tr>";
    					}
    					echo "<script>document.getElementById('tab_defeitos_$aa').style.display = \"inline\";</script>";
    				}
                } else {
                                        $bb = 1;
                    foreach ($_POST['i_defeito_constatado'][$dr_defeito_reclamado] as $chaveDefeito => $defeitoConstatadoId) {

                        $sqlDadosConstatado = "SELECT defeito_constatado,
                                                      descricao,
                                                      codigo
                                               FROM tbl_defeito_constatado
                                               WHERE defeito_constatado = {$defeitoConstatadoId}
                                               AND fabrica = {$login_fabrica}";
                        $resDadosConstatado = pg_query($con, $sqlDadosConstatado);

                        $defeitoDescricao = pg_fetch_result($resDadosConstatado, 0, 'descricao');
                        $defeitoCodigo    = pg_fetch_result($resDadosConstatado, 0, 'codigo');
                        
                        echo "<tr>";
                        echo "<td style='text-align: left; color: #000000;font-size:10px;border-bottom: thin dotted #FF0000'>
                                <font size='1'>
                                    <input type='hidden' data-codigo='{$defeitoCodigo}' name=\"i_defeito_constatado[$dr_defeito_reclamado][]\" id=\"i_defeito_constatado_".$aa."_".$bb."\" rel=\"defeito_constatado_".$aa."\" value='{$defeitoConstatadoId}'>{$defeitoDescricao}
                                </font>
                              </td>";
                        echo "<td align='right'><input type='button' onclick='removerIntegridade2(this,\"tab_defeitos_$aa\",\"$defeitoConstatadoId\");' value='Excluir'></td>";
                        echo "</tr>";
                        $bb++;
                    }

                    echo "<script>document.getElementById('tab_defeitos_$aa').style.display = \"inline\";</script>";
                }
				echo "</tbody>";
				echo "</table>";
			echo "</td>";
			echo "<td valign='top'>";
			echo "<input type='button' onclick=\"javascript: adicionaIntegridade2('$aa','tab_defeitos_$aa',
			document.frm_os.defeito_reclamado_$aa,
			document.frm_os.defeito_reclamado_descricao_$aa,
			document.frm_os.defeito_constatado_$aa,
            document.frm_os.xxproduto_familia.value)\" data-posicao='$aa' class='btn-add-defeito-constatado' value='Adicionar Defeito' name='btn_adicionar'>";
			echo "</td>";

			echo "</tr>";
		}
		$aa++;
	}
    $arrayDefeitosLorenzetti = implode(',', $defeitosHidden); //hd_chamado=2881143
    $defeitoLorenzetti = "[".$arrayDefeitosLorenzetti."]";//hd_chamado=2881143
    echo "<input type='hidden' name='defeitos_lorenzetti_hidden' id='defeitos_lorenzetti_hidden' value='$defeitoLorenzetti' />"; //hd_chamado=2881143
	echo "</tbody></table>";


    if(!empty($arrayDefeitosLorenzetti)) {

        if (verifica_checklist_tipo_atendimento($xtipo_atendimento)) {
            $codigoTipo = pg_fetch_result($resTipo, 0, 'codigo');

            $defeito_in =  $arrayDefeitosLorenzetti;
            $sql_list = "
                         SELECT 
                              tbl_checklist_fabrica.codigo,
                              tbl_checklist_fabrica.descricao,
                              tbl_checklist_fabrica.checklist_fabrica,
                              tbl_os_defeito_reclamado_constatado.defeito_constatado,
                              tbl_os_defeito_reclamado_constatado.defeito_reclamado,
                              tbl_os_defeito_reclamado_constatado.checklist_fabrica AS checklist_loren
                         FROM tbl_checklist_fabrica
                         JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.checklist_fabrica = tbl_checklist_fabrica.checklist_fabrica
                          AND tbl_os_defeito_reclamado_constatado.os = $os
                        WHERE tbl_checklist_fabrica.fabrica = $login_fabrica
                          AND tbl_checklist_fabrica.familia = $produto_familia
                          --AND tbl_os_defeito_reclamado_constatado.defeito_constatado IN ($defeito_in)";
            $res_list = pg_query($con, $sql_list);
            if(pg_num_rows($res_list) > 0){
                $rows = pg_num_rows($res_list);

                $selecionadosIDS  = [];
                $selecionadosDESC = [];

                for ($i=0; $i < $rows; $i++) {
                    $xdefeito_constatado = pg_fetch_result($res_list, $i, 'defeito_constatado');
                    $xdefeito_reclamado  = pg_fetch_result($res_list, $i, 'defeito_reclamado');
                    $checklist_fabrica   = pg_fetch_result($res_list, $i, 'checklist_fabrica');
                    $xxxcodigo              = pg_fetch_result($res_list, $i, 'codigo');
                    $xxxdescricao           = pg_fetch_result($res_list, $i, 'descricao');
     
                    $selecionadosIDS[$i]  = $checklist_fabrica;
                    $selecionadosDESC[$i] = $xxxcodigo.' - '. utf8_encode($xxxdescricao);

                }
                if (count($selecionadosIDS) > 0) {
                echo '
                <script>
                    $(window).on("load", function() {
                        retornaCheckList(\''.json_encode($selecionadosIDS).'\', \''.json_encode($selecionadosDESC).'\', \''.$xdefeito_reclamado.'\', \''.$xdefeito_constatado.'\', true, "'.$os.'");
                    });
                </script>
                ';
                }

            }

        }
    }
?>

<div id="t_checklist">
    <?php
    if ($login_fabrica == 19 && isset($_POST['check_list_fabrica']) && verifica_checklist_tipo_atendimento($xtipo_atendimento)) { ?>
            <table width="700" align="center" id="checklist" border="1" cellspacing="0" cellpadding="1" class="formulario">
                <tbody>
                    <tr>
                        <td class="titulo_tabela" valign="middle" colspan="5">
                            <label style="margin:auto;font:14px Arial">Checklist:</label>
                        </td>
                    </tr>
                    <tr class="titulo_tabela_2"><td class="tal">Checklist</td><td width="10%">Ação</td></tr>
                    <tbody id="show">
                    <?php

                        foreach ($_POST['check_list_fabrica'] as $checkReclamado => $arrCheckConstatado) {

                            foreach ($arrCheckConstatado as $checkConstatado => $arrChecklistId) {

                                    foreach ($arrChecklistId as $checklistId) {

                                        $sqlDadosChecklist = "SELECT descricao,
                                                                     codigo
                                                              FROM tbl_checklist_fabrica
                                                              WHERE checklist_fabrica = {$checklistId}
                                                              AND fabrica = {$login_fabrica}";
                                        $resDadosChecklist = pg_query($con, $sqlDadosChecklist);

                                        $descricaoChecklist = pg_fetch_result($resDadosChecklist, 0, 'descricao');
                                        $codigoChecklist    = pg_fetch_result($resDadosChecklist, 0, 'codigo');

                                    ?>

                                        <tr class="t_checklist tr_check trchk-<?= $checklistId ?>">
                                            <td class="tal">
                                                <input type="hidden" class="check_list_fabrica" name="check_list_fabrica[<?= $checkReclamado ?>][<?= $checkConstatado ?>][]" value="<?= $checklistId ?>">
                                                <input type="checkbox" checked disabled name="check_list_fabrica_'<?= $checkReclamado ?>'_'<?= $checkConstatado ?>'_'<?= $checklistId ?>'"> <?= $codigoChecklist." - ".$descricaoChecklist ?>
                                            </td>
                                            <td>
                                                <button type="button" data-os="<?= $os ?>" data-constatado="<?= $checkConstatado ?>" data-reclamado="<?= $checkReclamado ?>" data-posicao="<?= $checklistId ?>" class="btn btn-delete btn-remove-item-checklist">Excluir</button>
                                            </td>
                                        </tr>    

                                <?php
                                }
                            }

                        } ?>
                    </tbody>
            </tbody>
        </table>
    <?php
    }
    ?>    
</div>

<?php
}

//relacionamento de integridade termina aqui....

}

if(in_array($login_fabrica, array(3,87))){?>

    <table width="700" align="center" border="0" cellspacing="5" cellpadding="0" id='tbl_integridade'>
		<tr>
            <td>
                <font face="Geneva, Arial, Helvetica, san-serif" size="1">Técnico</font><br>
                <select name="tecnico" size="1" class="frm">
                <?php
                	if(in_array($login_fabrica, array(3))){
                        $sql = "SELECT
                                    tbl_tecnico.tecnico,
                                    tbl_tecnico.nome
                                FROM tbl_os
                                    JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
                                    JOIN tbl_tecnico  ON  tbl_produto.linha = ANY(tbl_tecnico.linhas)
                                WHERE
                                    tbl_tecnico.ativo
                                    AND tbl_tecnico.fabrica = {$login_fabrica}
                                    AND tbl_tecnico.posto = tbl_os.posto
                                    AND tbl_os.os = $os
                                ORDER BY tbl_tecnico.nome;";
                	}else{
				$sql = "SELECT tecnico, nome FROM tbl_tecnico WHERE fabrica = $login_fabrica AND posto = $login_posto ORDER BY nome ASC;";
                	}

                    $res = pg_query($con, $sql);

                    if(pg_num_rows($res) > 0){
                        echo "<option value='0' selected>selecione um técnico</option>";
                        for($i = 0; $i < pg_num_rows($res); $i++) {
                            $cod_tecnico = pg_fetch_result($res,$i,'tecnico');
                            $nome = pg_fetch_result($res,$i,'nome');

                            $selected = ($tecnico == $cod_tecnico) ? " selected " : "";

                            echo "<option value='$cod_tecnico' label='$nome' $selected>$nome</option>";
                        }
                    }else
                        echo "<option value='0' selected>Nenhum técnico cadastrado</option>";
                ?>
                </select>
            </td>
            <?php if(!in_array($login_fabrica, array(3))){ ?>
	            <td>
	                <font face="Geneva, Arial, Helvetica, san-serif" size="1"><?$tema?> <?=$defeito_constatado?> </font><br>
	                <select name="defeito_constatado" size="1" class="frm">
	                <?php
	                    if(!empty($familia)){

	                       $sql = "
	                                SELECT DISTINCT
	                                    tbl_defeito_constatado.defeito_constatado,
	                                    tbl_defeito_constatado.descricao
	                                FROM tbl_diagnostico
	                                    JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
	                                WHERE
	                                    tbl_defeito_constatado.fabrica = $login_fabrica
	                                    AND tbl_diagnostico.familia = $familia ORDER BY tbl_defeito_constatado.descricao ASC;";
	                        $res = pg_query($con, $sql);

	                        if(pg_num_rows($res) > 0){
	                            echo "<option value='0' selected>selecione um $tema</option>";
	                            for($i = 0; $i < pg_num_rows($res); $i++) {
	                                $cod_defeito_constatado = pg_fetch_result($res,$i,'defeito_constatado');
	                                $descricao_defeito = pg_fetch_result($res,$i,'descricao');

	                                $selected = ($defeito_constatado == $cod_defeito_constatado) ? " selected " : "";

	                                echo "<option value='$cod_defeito_constatado' label='$descricao_defeito' $selected>$descricao_defeito</option>";
	                            }
	                        }else
	                            echo "<option value='0' selected>nenhum $tema encontrado</option>";
	                    }
	                ?>
	                </select>
	            </td>
	           <td>
	                <font face="Geneva, Arial, Helvetica, san-serif" size="1">Horas</font><br>
	                <input type="text" class="frm" name="qtde_horas" size="10" maxlength="10" value="<?php echo $qtde_horas?>" />
	           </td>
	           <td>
	                <font face="Geneva, Arial, Helvetica, san-serif" size="1">Distância KM</font><br>
	                <input type="text" class="frm" name="qtde_km" size="10" maxlength="10" value="<?php echo $qtde_km?>" />
	           </td>
           <?php }?>
           <td width='200px'>&nbsp;</td>
        </tr>
    </table>
<?
}

if((!in_array($login_fabrica,array(2,3,6,11,19,24,30,45,46,50,51,59,74,72,90,99,87)) and $login_fabrica < 104) ) { ?>
		<table width="700" class='formulario' align="center" border="0" cellspacing="5" cellpadding="0" id='tbl_integridade'>
		<tr>
			<? if (!in_array($login_fabrica, array(5,91,101))) { ?>
			<td nowrap style="width:150px; padding-right:10px;">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Reclamado</font>
				<br>
<?
		if ($login_fabrica != 1) {
			$sql = "SELECT *
					FROM   tbl_defeito_reclamado
					JOIN   tbl_linha USING (linha)
					WHERE  tbl_defeito_reclamado.linha = $linha
					AND    tbl_linha.fabrica           = $login_fabrica
					ORDER BY tbl_defeito_reclamado.descricao;";
			$resD = pg_query ($con,$sql) ;

			if ($login_fabrica == 14) {
				$sql = "SELECT *
						FROM   tbl_defeito_reclamado
						JOIN   tbl_familia USING (familia)
						WHERE  tbl_defeito_reclamado.familia = $familia
						AND    tbl_familia.fabrica           = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_query ($con,$sql);
			}

			if ($login_fabrica == 52 or $login_fabrica == 42) {
				if (strlen($defeito_reclamado)>0 ){
					$sql = "SELECT *
							FROM   tbl_defeito_reclamado
							WHERE  tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado
							ORDER BY tbl_defeito_reclamado.descricao;";
					$resD = pg_query ($con,$sql);
				}
			}

			if ($login_fabrica == 86) {
				if (strlen($defeito_reclamado)>0 ){
					$sql = "SELECT *
							FROM tbl_defeito_reclamado
							JOIN tbl_diagnostico USING (defeito_reclamado)
							JOIN tbl_familia ON (tbl_diagnostico.familia = tbl_familia.familia)
							WHERE tbl_diagnostico.familia = $familia
							AND tbl_familia.fabrica = $login_fabrica
							ORDER BY tbl_defeito_reclamado.descricao;";
					$resD = pg_query ($con,$sql);
				}
			}



			if (pg_num_rows ($resD) == 0) {
				$sql = "SELECT *
						FROM   tbl_defeito_reclamado
						JOIN   tbl_linha USING (linha)
						WHERE  tbl_linha.fabrica = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_query ($con,$sql) ;
			}
		}else{
			$sql = "SELECT  tbl_defeito_reclamado.defeito_reclamado ,
							tbl_defeito_reclamado.descricao
					FROM    tbl_defeito_reclamado
					JOIN    tbl_linha   ON tbl_linha.linha     = tbl_defeito_reclamado.linha
					JOIN    tbl_familia ON tbl_familia.familia = tbl_defeito_reclamado.familia
                                        AND tbl_familia.fabrica = tbl_defeito_reclamado.fabrica
					JOIN    tbl_produto ON tbl_produto.familia = tbl_familia.familia
					WHERE   tbl_defeito_reclamado.familia = tbl_familia.familia
					AND     tbl_familia.fabrica           = $login_fabrica
					AND     tbl_produto.produto           = $produto_os
					ORDER BY tbl_defeito_reclamado.descricao";
			$resD = pg_query ($con,$sql);
		}
// echo nl2br($sql);
		if (pg_num_rows ($resD) > 0 AND $login_fabrica <> 5 AND $login_fabrica <> 30 AND $login_fabrica <> 51 AND $login_fabrica <> 15) {
			echo "<select name='defeito_reclamado' size='1' class='frm'>";
			echo "<option value=''></option>";
			for ($i = 0 ; $i < pg_num_rows ($resD) ; $i++ ) {
				echo "<option ";
				if ($defeito_reclamado == pg_fetch_result ($resD,$i,defeito_reclamado) ) echo " selected ";
				echo " value='" . pg_fetch_result ($resD,$i,defeito_reclamado) . "'>" ;
				echo pg_fetch_result ($resD,$i,descricao) ;
				echo "</option>";
			}
			echo "</select>";
		}else{
			echo $defeito_reclamado_descricao;
		}
?>
			</td>
			<? } ?>
			<?php
				// HD 415550
				if($login_fabrica == 94) {
					$sql = "SELECT posto
							FROM tbl_posto_fabrica
							JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.posto_interno
							WHERE tbl_posto_fabrica.fabrica = " . $login_fabrica . "
							AND tbl_posto_fabrica.posto = " . $login_posto;
					$res = pg_query($con,$sql);

					if( pg_num_rows($res) ) {

						$posto_interno = true;
						$sql = "SELECT mao_de_obra FROM tbl_os WHERE os = $os";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res))
							$mao_de_obra = number_format( pg_fetch_result($res,0,0), 2, ',', '' );
						echo "<td width='200px'>
								<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Mão-de-Obra</font><br />
								<input type='text' name='valor_mao_de_obra' id='valor_mao_de_obra' value='".$mao_de_obra."' />
							  </td>";

						echo '<td>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Técnico</font><br />
								<input type="text" name="tecnico_nome" size="20" maxlength="20" value="'.$tecnico_nome.'" />
							  </td>';

					}
				}
				// HD 415550 - FIM
			?>

            <?php if(in_array($login_fabrica, array(101,42)) or in_array($login_fabrica,$fabricas_padrao_itens) ){ ?>

            <td>

                <?php echo $tema; ?> <br />

                <select name="defeito_constatado" id="defeito_constatado" class="frm"  onfocus="<?=$onfocus_def_cons?>" onchange="<?=$onchange_def_cons?>" style='width: 300px;'>

                    <option value="">Selecione o <?=$tema?></option>
                    <?php

                    #HD 670814
                    if(in_array($login_fabrica,$fabricas_padrao_itens)  || $login_fabrica > 99){

                        $cond_order_by = ($login_fabrica == 137) ? " tbl_defeito_constatado.codigo "  : " tbl_defeito_constatado.descricao ";

                        $sql_cons = "   SELECT DISTINCT(tbl_diagnostico.defeito_constatado),
                                                        tbl_defeito_constatado.codigo,
                                                        tbl_defeito_constatado.descricao,
                                                        tbl_defeito_constatado.defeito_constatado_grupo
                                        FROM            tbl_diagnostico
                                        JOIN            tbl_defeito_constatado ON tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.ativo
                                        LEFT JOIN       tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo
                                        WHERE           tbl_diagnostico.familia = $produto_familia
                                        AND             tbl_diagnostico.fabrica = $login_fabrica
                                        ORDER BY        $cond_order_by ";

                        $res_cons = pg_query($con, $sql_cons);

                        if (pg_num_rows($res_cons) > 0) {
                            for ($y = 0; $y < pg_num_rows($res_cons) ; $y++){
                                $defeito_cons = pg_fetch_result($res_cons,$y,'defeito_constatado');
                                $codigo = pg_fetch_result($res_cons,$y,'codigo');
                                $defeito_constatado_desc = pg_fetch_result($res_cons,$y,'descricao');
                                $selected = ($defeito_constatado == $defeito_cons) ? "SELECTED" : null;

                                if($login_fabrica == 137){
                                    $defeito_constatado_desc = $codigo." - ".$defeito_constatado_desc;
                                }

                                if (in_array($login_fabrica, array(141))) {
                                    $produto_fora_de_garantia = pg_fetch_result($res_cons, $y, "defeito_constatado_grupo");

                                    $attr_produto_fora_garantia = "produto_fora_garantia='$produto_fora_de_garantia'";
                                }

                                echo "<option $selected id='opcoes2_$y' value='$defeito_cons' $attr_produto_fora_garantia >$defeito_constatado_desc</option>";
                            }
                        } else {
                            echo "<option id='opcoes2' value=''></option>";
                        }

                    } else if ($pedir_defeito_reclamado_descricao == 't' AND strlen($defeito_constatado) AND  ( in_array($login_fabrica, array(2, 15, 30, 35, 40, 42, 43, 45, 46, 50, 51, 56)) OR $login_fabrica>56) ) {

                        $sql_cons = "SELECT defeito_constatado, descricao
                                        FROM tbl_defeito_constatado
                                        WHERE defeito_constatado = $defeito_constatado
                                        AND fabrica = $login_fabrica; ";
                            $res_cons = pg_query($con, $sql_cons);

                            if (pg_num_rows($res_cons) > 0) {
                                $defeito_constatado_desc = pg_fetch_result($res_cons,0,descricao);
                                echo "<option id='opcoes2' value='$defeito_constatado' selected>$defeito_constatado_desc</option>";
                            } else {
                                echo "<option id='opcoes2' value=''></option>";
                            }

                    } else {
                        echo "<option id='opcoes2' value=''></option>";
                    }

                echo "</select>";

                ?>

            </td>

            <?php } ?>

			<? if ($pedir_defeito_constatado_os_item != "f" and $login_fabrica <> 20) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<? // HD 73709
				if ($login_fabrica == 20) echo "Reparo";
				else echo $tema;
				?></font>
				<br>
				<select name="defeito_constatado" size="1" class="frm">
					<option selected></option>
<?
                // SELECT que estava aqui agora está no começo do arquivo! - MLG 20/08/2012
				if ($defeito_constatado_por_familia == 't') {
					$sql = "SELECT tbl_defeito_constatado.*
							FROM   tbl_familia
							JOIN   tbl_familia_defeito_constatado USING(familia)
							JOIN   tbl_defeito_constatado         USING(defeito_constatado)
							WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
							AND    tbl_familia_defeito_constatado.familia = $familia
							AND    tbl_defeito_constatado.ativo IS TRUE
							";
                    // Coloquei AND    tbl_defeito_constatado.ativo IS TRUE // Fabio - 28-12-2007
					if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> '1' ";

					if ($login_fabrica == 1 && $tipo_atendimento == 334) {
                        $sql = "SELECT  tbl_defeito_constatado.defeito_constatado,
                                        tbl_defeito_constatado.descricao,
                                        tbl_defeito_constatado.codigo
                                FROM    tbl_defeito_constatado
                                WHERE   fabrica = $login_fabrica
                                AND     ativo IS TRUE
                          ORDER BY      codigo
                        ";
                    }
				} else {
					if ($defeito_constatado_por_linha == 't') {
						$sql   = "SELECT linha FROM tbl_produto WHERE produto = $produto_os";
						$res   = pg_query ($con,$sql);
						$linha = pg_fetch_result ($res,0,0) ;

						$sql = "SELECT tbl_defeito_constatado.*
								FROM   tbl_defeito_constatado";
								if ($login_fabrica <> 2) {
								$sql .= " JOIN   tbl_linha USING(linha) ";
								}
								$sql .= " WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica ";
								if ($login_fabrica <> 2) {
								$sql .="AND    tbl_linha.linha = $linha";
								}
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						$sql .= " ORDER BY tbl_defeito_constatado.descricao";
					}else{
						$sql = "SELECT tbl_defeito_constatado.*
								FROM   tbl_defeito_constatado
								WHERE  tbl_defeito_constatado.fabrica = $login_fabrica";
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						$sql .= " ORDER BY tbl_defeito_constatado.codigo;";
					}
				}
                if (in_array($login_fabrica,array(15,35))) {
                    $sql="select * from tbl_defeito_constatado where fabrica=$login_fabrica and ativo is true order by descricao";
                }
                if ($login_fabrica == 20) { // comentado no hd_chamado=2807872 / hd_chamado=2843341
					$sql = "SELECT tbl_defeito_constatado.*
							FROM tbl_defeito_constatado
							JOIN tbl_produto_defeito_constatado
								ON  tbl_defeito_constatado.defeito_constatado = tbl_produto_defeito_constatado.defeito_constatado
								AND tbl_produto_defeito_constatado.produto = $produto_os
							WHERE fabrica = $login_fabrica
							ORDER BY tbl_defeito_constatado.descricao";
                    // $sql = "SELECT tbl_defeito_constatado.*
                    //             FROM   tbl_familia
                    //             JOIN   tbl_familia_defeito_constatado USING(familia)
                    //             JOIN   tbl_defeito_constatado         USING(defeito_constatado)
                    //             WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
                    //             AND    tbl_familia_defeito_constatado.familia = $familia
                    //             AND    tbl_defeito_constatado.ativo IS TRUE
                    //             ORDER BY tbl_defeito_constatado.descricao";

				}

				$res = pg_query ($con,$sql) ;
				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					echo "<option ";
					if ($defeito_constatado == pg_fetch_result ($res,$i,defeito_constatado) ) echo " selected ";
					echo " value='" . pg_fetch_result ($res,$i,defeito_constatado) . "'>" ;
					echo pg_fetch_result ($res,$i,descricao) ." - ". pg_fetch_result ($res,$i,codigo) ;
					echo "</option>";
				}
				?>
				</select>
			</td>

			<? } ?>

			<? if ($pedir_causa_defeito_os_item != "f" && $login_fabrica != 5 and $login_fabrica <> 20) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Causa Defeito</font>
				<br>
				<select name="causa_defeito" size="1" class="frm">
					<option selected></option>
				<?
				$sql = "SELECT tbl_causa_defeito.*
						FROM   tbl_causa_defeito
						WHERE  tbl_causa_defeito.fabrica = $login_fabrica
						ORDER BY tbl_causa_defeito.codigo, tbl_causa_defeito.descricao;";
				$res = pg_query ($con,$sql) ;

				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					$causa_defeitoRes	= pg_fetch_result($res,$i,causa_defeito);
					$descricaoRes = "";
					if (strlen (trim (pg_fetch_result ($res,$i,codigo))) > 0) $descricaoRes = pg_fetch_result ($res,$i,codigo) . " - ";
					$descricaoRes .= pg_fetch_result($res,$i,descricao);
					if ($causa_defeito == $causa_defeitoRes)
						$sel = " selected ";
					else
						$sel = "";
					echo "<option value='$causa_defeitoRes' $sel>$descricaoRes</option>";
				}
				?>
				</select>
			</td>
			<? } ?>
			<?php
				/**
				 *  @author Brayan
				 *  @description HD 415882 - Varios defeitos constatados para a EVEREST, da mesma forma feita na área do posto.
				 */
				if ( $login_fabrica == 94 ) {

					$sql = "SELECT familia,defeito_constatado
							FROM tbl_os
							JOIN tbl_produto USING(produto)
							WHERE os = $os
							AND fabrica = $login_fabrica";
					$res = pg_query($con,$sql);

					if(pg_num_rows($res)) {
						$dc = pg_fetch_result($res,0,1);
						$sql_cons = "SELECT tbl_defeito_constatado.defeito_constatado, tbl_defeito_constatado.descricao
									FROM tbl_defeito_constatado
									JOIN tbl_diagnostico USING(defeito_constatado)
									WHERE tbl_defeito_constatado.fabrica = $login_fabrica
									AND tbl_diagnostico.fabrica = $login_fabrica
									AND tbl_diagnostico.familia = " . pg_fetch_result($res,0,0) . "
									ORDER BY tbl_defeito_constatado.descricao ";
						$res_cons = pg_query($con, $sql_cons);

						echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$tema</font> <br />
								<select name='defeito_constatado_codigo' id='defeito_constatado_codigo' class='frm'>";

						if (pg_num_rows($res_cons) > 0) {
							for($i=0;$i < pg_num_rows($res_cons); $i++){
								$defeito_constatado_desc = pg_fetch_result($res_cons,$i,descricao);
								$defeito_constatado      = pg_fetch_result($res_cons,$i,defeito_constatado);
								echo "<option id='opcoes2' value='$defeito_constatado' ";
								if($dc == $defeito_constatado) echo " selected ";
								echo ">$defeito_constatado_desc</option>";
							}
						}
						else{
								echo "<option id='opcoes2' value=''></option>";
						}
						echo "	</select>
								&nbsp;<input type='button' onclick=\"javascript: adicionaIntegridade3()\" value='Adicionar' name='btn_adicionar' style='width:70px; font:bold 9px;'>
							</td>";

					}

				}

				// FIM HD 415882
			?>
		</tr>

		</table>

		<?
		if ($pedir_solucao_os_item <> 'f') {

		?>
		<table width="700"  class='formulario' border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td align="left" nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Solução</font>
				<br>
				<?if($linha <> 549) { ?>
				<select name="solucao_os" size="1" class="frm">
					<option value=""></option>
<?
				if(in_array($login_fabrica,array(1,35))) {

					$sql = "SELECT 	tbl_solucao.solucao,
							tbl_solucao.descricao
						FROM tbl_solucao";
					if($login_fabrica == 1){
                        if ($tipo_atendimento != 334) {
                            $sql .= " JOIN tbl_linha_solucao ON tbl_solucao.solucao = tbl_linha_solucao.solucao AND tbl_linha_solucao.linha = $linha ";
                        } else {
                            $sql_add1 = "
                                AND descricao ILIKE 'Substitui%o de pe%a gerando pedido'
                            ";
                        }
					}

					$sql.=" WHERE fabrica = $login_fabrica
						AND   ativo IS TRUE
						$sql_add1
						ORDER BY descricao";
					$res = pg_query($con, $sql);

					for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
						$aux_solucao_os    = pg_fetch_result ($res,$x,solucao);
						$solucao_descricao = pg_fetch_result ($res,$x,descricao);
						echo "<option id='opcoes' value='$aux_solucao_os' "; if($aux_solucao_os == $solucao_os) echo " SELECTED"; echo ">$solucao_descricao</option>";
					}

				}else{
					$sql = "SELECT *
							FROM   tbl_servico_realizado
							WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

					if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1) {
						$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
					}

					if ($login_fabrica == 1) {
						if ($reembolso_peca_estoque == 't') {
							//a pedido de Fabiola, bloquear apenas troca de peça
							$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca de peça%' ";
							$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
							if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
						}else{
							$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
							$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
							if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
						}
					}
					if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS NOT TRUE ";
					$sql .= " AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
                    
					$res = pg_query ($con,$sql) ;

					if (pg_num_rows($res) == 0) {
						$sql = "SELECT *
								FROM   tbl_servico_realizado
								WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

						if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1) {
							$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
						}

						if ($login_fabrica == 1) {
							if ($reembolso_peca_estoque == 't') {
								$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
								$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
							}else{
								$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
								$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
							}
						}
						$sql .=	" AND tbl_servico_realizado.linha IS NULL
								AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
						$res = pg_query ($con,$sql) ;
					}

					for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
						echo "<option ";
						if ($solucao_os == pg_fetch_result ($res,$x,servico_realizado)) echo " selected ";
						echo " value='" . pg_fetch_result ($res,$x,servico_realizado) . "'>" ;
						echo pg_fetch_result ($res,$x,descricao) ;
						if (pg_fetch_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
						echo "</option>";
					}
				}
				?>
				</select>
				<?}else{?>
					<input type ='text' name='solucao_os2' maxlength='50' value='' size='30'  class='frm'>
				<?}?>
			</td>

			<?if($login_fabrica == 1 and  $tipo_os == 13) {
				$sql = " SELECT tipo_atendimento FROM tbl_os where os = $os";
				$res = pg_query($con,$sql);
				$tipo_atendimento = pg_fetch_result($res,0,0);
				echo "<td>";
				echo "<select name='tipo_atendimento' id='tipo_atendimento' style='width:230px;'>";

				$sql = "SELECT *
						FROM tbl_tipo_atendimento
						WHERE fabrica = $login_fabrica
						AND   ativo IS TRUE
						AND   tipo_atendimento in(64,65,69)
						ORDER BY tipo_atendimento ";
				$res = pg_query ($con,$sql) ;

				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					echo "<option ";
					if ($tipo_atendimento == pg_fetch_result ($res,$i,tipo_atendimento) ) {
						echo " selected ";
					}
					echo " value='" . pg_fetch_result ($res,$i,tipo_atendimento) . "'>" ;
					echo pg_fetch_result ($res,$i,codigo) . " - " . pg_fetch_result ($res,$i,descricao) ;
					echo "</option>";
				}
				echo "</select>";
				echo "</td>";
			}

			?>

		</tr>

		</table>

		<?
		}
		?>
<?
}

// SOMENTE LORENZETTI
if ($login_fabrica == 19){
    echo "<table width='75%' border='0' cellspacing='5' cellpadding='0'>";
    echo "<tr>";
    echo "<td align='left' nowrap>";
    echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Nome do Técnico</font>";
    echo "<br>";
    echo "<input type='text' name='tecnico_nome' size='20' maxlength='20' value='$tecnico_nome'>";
    echo "</td>";
    /*echo "<td align='left' nowrap>";
    echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Mês e Ano de Fabricação do Produto</font>";
    echo "<br>";
    echo "<input type='text' name='mes_ano' id='mes_ano' size='16' maxlength='20' value='$mes_ano'>";
    echo "</td>";
    echo "<td align='left' nowrap>";
    echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Sequência</font>";
    echo "<br>";
    echo "<input type='text' name='sequencia' size='20' maxlength='2' value='$sequencia'>";
    echo "</td>";*/
    echo "</tr>";
    echo "</table>";
}

if (strlen($troca_faturada) == 0) {

		### LISTA ITENS DA OS QUE POSSUEM PEDIDOS
		if(strlen($os) > 0){
		//aqui
			$sql = "SELECT  tbl_os_item.os_item                                   ,
							tbl_os_item.pedido                                    ,
							tbl_os_item.qtde                                      ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                   ,
							tbl_peca.descricao                                    ,
							tbl_defeito.defeito                                   ,
							tbl_defeito.descricao AS defeito_descricao            ,
							tbl_produto.referencia AS subconjunto                 ,
							tbl_os_produto.produto                                ,
							tbl_os_produto.serie                                  ,
							tbl_servico_realizado.servico_realizado               ,
							tbl_servico_realizado.descricao AS servico_descricao  ,
							tbl_pedido.pedido_blackedecker  AS pedido_blackedecker,
							tbl_pedido.pedido_acessorio     AS pedido_acessorio
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					LEFT JOIN    tbl_pedido            ON tbl_pedido.pedido = tbl_os_item.pedido
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido NOTNULL
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_query ($con,$sql) ;

			if(pg_num_rows($res) > 0) {
				echo "<table width='700' class='formulario' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças que possuem pedidos</b></font></td>\n";

				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";

				echo "</tr>\n";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$faturado      = pg_num_rows($res);
						$fat_item      = pg_fetch_result($res,$i,os_item);
						$fat_pedido    = pg_fetch_result($res,$i,pedido);
						$fat_peca      = pg_fetch_result($res,$i,referencia);
						$fat_descricao = pg_fetch_result($res,$i,descricao);
						$fat_qtde      = pg_fetch_result($res,$i,qtde);

						echo "<tr height='20' bgcolor='#FFFFFF'>";

						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>";

						#------- Bloquado exclusão de item de OS pelo TULIO.... 14/02
						#------- Admin exclui e depois distribuidor fica reclamando que mudou numero do pedido
						#------- Caso do GRALA x BRITANIA
						#------- Se for excluir, temos que mandar email pra todos os envolvidos... Posto, Distribuidor e ADMIN
						#------- Por enquanto, não excluir

						#------- Liberado de novo em 16/02
						#------- Herio disse que vai apagar no EMS os pedidos antigos
						echo "<img src='imagens/btn_x.gif' width='15' height='12' onclick=\"javascript: if(confirm('Deseja realmente excluir o item da OS?') == true){ window.location='$PHP_SELF?os_item=$fat_item&os=$os';}\" style='cursor:pointer;'>&nbsp;&nbsp;";

						if ($login_fabrica == 1) {
							$fat_pedido = trim(pg_fetch_result ($res,$i,pedido_blackedecker));
							$pedido_acessorio    = trim(pg_fetch_result ($res,$i,pedido_acessorio));
							if ($pedido_acessorio == 't') $fat_pedido = intval($pedido_blackedecker + 1000);
						}

						echo "$fat_pedido</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_qtde</font></td>\n";

						echo "</tr>\n";
				}
				echo "</table>\n";
			}
		}

		### LISTA ITENS DA OS QUE ESTÃO COMO NÃO LIBERADAS PARA PEDIDO EM GARANTIA
		if(strlen($os) > 0){
		//aqui
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.obs                                     ,
							tbl_os_item.qtde                                    ,
							tbl_os_item.porcentagem_garantia                    ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.liberacao_pedido IS FALSE
					AND     tbl_os_item.liberacao_pedido           IS FALSE
					AND     tbl_os_item.liberacao_pedido_analisado IS TRUE
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_query ($con,$sql) ;

			if(pg_num_rows($res) > 0) {
				echo "<table width='700' class='formulario' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				if ($login_fabrica == 14) {
					echo "<td align='center' colspan='6'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças que não irão gerar pedido em garantia</b></font></td>\n";
				}else{
					if ($login_fabrica <> 6) {
						echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças que não irão gerar pedido em garantia ou com pedido bloqueado</b></font></td>\n";
					}else{
						echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças pendentes</b></font></td>\n";
					}
				}

				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				if ($login_fabrica == 14) {
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Ação</b></font></td>\n";
				}

				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";

				if ($login_fabrica == 14) {
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Valor</b></font></td>\n";
				}

				echo "</tr>\n";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$recusado      = pg_num_rows($res);
						$rec_item      = pg_fetch_result($res,$i,os_item);
						$rec_obs       = pg_fetch_result($res,$i,obs);
						$rec_peca      = pg_fetch_result($res,$i,referencia);
						$rec_descricao = pg_fetch_result($res,$i,descricao);
						$rec_qtde      = pg_fetch_result($res,$i,qtde);
						$rec_preco     = pg_fetch_result($res,$i,porcentagem_garantia);

						echo "<tr height='20' bgcolor='#FFFFFF'>";

						if ($login_fabrica == 14) {
							echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='$PHP_SELF?liberar=$rec_item&os=$os'>LIBERAR ITEM</a></font></td>\n";
						}

						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";

						if ($login_fabrica == 14) {
							echo "<td align='right'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>". str_replace(".",",",$rec_preco) ."</font></td>\n";
						}

						echo "</tr>\n";
				}
				echo "</table>\n";
			}
		}

		### LISTA ITENS DA OS FORAM LIBERADAS E AINDA NÃO POSSI PEDIDO
		if(strlen($os) > 0){
		//aqui
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.obs                                     ,
							tbl_os_item.qtde                                    ,
							tbl_os_item.porcentagem_garantia                    ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido           ISNULL
					AND     tbl_os_item.liberacao_pedido IS TRUE
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_query ($con,$sql) ;

			if(pg_num_rows($res) > 0) {
				echo "<table width='700' class='formulario' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				if ($login_fabrica == 14) {
					echo "<td align='center' colspan='5'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças aprovadas aguardando pedido</b></font></td>\n";
				}else{
					echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças aprovadas aguardando pedido</b></font></td>\n";
				}

				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";

				if ($login_fabrica == 14) {
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Valor</b></font></td>\n";
				}

				echo "</tr>\n";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$recusado      = pg_num_rows($res);
						$rec_item      = pg_fetch_result($res,$i,os_item);
						$rec_obs       = pg_fetch_result($res,$i,obs);
						$rec_peca      = pg_fetch_result($res,$i,referencia);
						$rec_descricao = pg_fetch_result($res,$i,descricao);
						$rec_qtde      = pg_fetch_result($res,$i,qtde);
						$rec_preco     = pg_fetch_result($res,$i,porcentagem_garantia);

						echo "<tr height='20' bgcolor='#FFFFFF'>";

						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";

						if ($login_fabrica == 14) {
							echo "<td align='right'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>". str_replace(".",",",$rec_preco) ."</font></td>\n";
						}

						echo "</tr>\n";
				}
				echo "</table>\n";
			}
		}
		if(strlen($os) > 0 and $login_fabrica == 6){
		/*HD 2599*/
	$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_lista_basica.qtde
			FROM  tbl_lista_basica
			JOIN  tbl_peca using(peca)
			WHERE tbl_lista_basica.fabrica = $login_fabrica
			AND   tbl_lista_basica.produto = $produto_os
			AND   tbl_peca.item_aparencia  = 'f'
			AND   tbl_peca.pre_selecionada = 't'
			Order by tbl_peca.referencia";
//echo $sql;
	$res = pg_query($con,$sql);

	if(pg_num_rows($res)>0){
	echo "<table width='700' class='formulario' border='0' cellspacing='2' cellpadding='0'>";
	echo "<tr height='20' bgcolor='#666666'>";
	echo "<td align='center' colspan='5'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças Pré-lançadas</b></font></td>";
	echo "</tr>";
	echo "<tr height='20' bgcolor='#666666'>";
	echo "<td align='center' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Código</b></font></td>";
            echo "<td align='center' nowrap><a class='lnk' href='peca_consulta_por_produto";
	if($login_fabrica==6)echo "_tectoy";
	echo ".php?produto=$produto_os";
		if($login_fabrica==6)echo "&os=$os";
		echo "' target='_blank'><font color='#FFFFFF'>Lista Básica</font></a></td>";
	echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>";
	echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>";
	echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Defeito</b></font></td>";
	echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Serviço</b></font></td>";
	echo "</tr>";
		for($x=0;pg_num_rows($res)>$x;$x++){
			$ypeca_referencia = pg_fetch_result($res,$x,referencia);
			$ypeca_descricao  = pg_fetch_result($res,$x,descricao);
			$yqtde            = pg_fetch_result($res,$x,qtde);
			$ypeca            = pg_fetch_result($res,$x,peca);

			echo "<tr>";

			echo "<td align='center'><input class='frm' type='checkbox' name='pre_peca_$x' value='$ypeca'>&nbsp;<font face='arial' size='-2' color='#000000'> $ypeca_referencia </font> </td>";
			echo "<td width='60' align='center'>&nbsp;</TD>";

			echo "<td align='left'><font face='arial' size='-2' color='#000000'>$ypeca_descricao</font></td>\n";

			echo "<td align='center'><font face='arial' size='-2' color='#000000'>$yqtde</font><input type='hidden' name='pre_qtde_$x' value='$yqtde'></td>\n";

			echo "<td>";
			echo "<select name='pre_defeito_$x'  class='frm' style='width:170px;'>";
			echo "<option></option>";
			$sql = "SELECT 	tbl_defeito.defeito,
							tbl_defeito.descricao
					FROM tbl_peca_defeito
					JOIN tbl_defeito using(defeito)
					WHERE peca = $ypeca
					AND tbl_peca_defeito.ativo = 't'
					ORDER BY tbl_defeito.descricao";
			$zres = pg_query($con,$sql);
			if(pg_num_rows($zres)>0){
				for($z=0;pg_num_rows($zres)>$z;$z++){
					$zdefeito   = pg_fetch_result($zres,$z,defeito);
					$zdescricao = pg_fetch_result($zres,$z,descricao);
					echo "<option value='$zdefeito'>$zdescricao</option>";
				}
			}
			echo "</select>";
			echo "</td>";

			echo "<td>";
			echo "<select class='frm' size='1' name='pre_servico_$x'  style='width:150px;'>";
			echo "<option></option>";
			$sql = "
                SELECT  tbl_servico_realizado.servico_realizado,
                        tbl_servico_realizado.descricao
                FROM    tbl_peca_servico join tbl_servico_realizado using(servico_realizado)
                WHERE   tbl_peca_servico.ativo = 't'
                AND     tbl_peca_servico.peca = $ypeca
          ORDER BY      tbl_servico_realizado.descricao";
			$zres = pg_query($con,$sql);
			if(pg_num_rows($zres)>0){
				for($z=0;pg_num_rows($zres)>$z;$z++){
					$zservico_realizado   = pg_fetch_result($zres,$z,servico_realizado);
					$zdescricao = pg_fetch_result($zres,$z,descricao);
					echo "<option value='$zservico_realizado'>$zdescricao</option>";
				}
			}
			echo "</select>";
			echo "</td>";

			echo "</tr>";
		}
		echo "<input type='hidden' name='pre_total' value='$x'>\n";
	echo "</table>";
	}
/*HD 2599*/
		}


		if(strlen($os) > 0 AND strlen ($msg_erro) == 0){

            if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
				$sql = "SELECT  tbl_peca.peca
						FROM    tbl_peca
						JOIN    tbl_lista_basica USING (peca)
						JOIN    tbl_produto      ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.fabrica_i=$login_fabrica
						WHERE   tbl_produto.produto     = $produto_os
						AND     tbl_peca.fabrica        = $login_fabrica
						AND     tbl_peca.item_aparencia = 't'
						ORDER BY tbl_peca.referencia;";
				$resX = @pg_query($con,$sql);
				$inicio_itens = @pg_num_rows($resX);
			}else{
				$inicio_itens = 0;
			}

            if($login_fabrica == 87){
                $select_peca_causador 	= "tbl_os_item.peca_causadora, peca_causadora.referencia AS referencia_causadora, peca_causadora.descricao AS descricao_causadora,";
                $join_peca_casaudor	= "LEFT JOIN tbl_peca AS peca_causadora ON tbl_os_item.peca_causadora=peca_causadora.peca";
            }else{
                $select_peca_causador 	= "";
                $join_peca_casaudor	= "";
            }
            if($login_fabrica == 35){
                $campos_cadence = " tbl_servico_realizado.peca_estoque,
            tbl_servico_realizado.troca_de_peca, ";
            }

			$sql = "SELECT  tbl_os_item.os_item                                                ,
							tbl_os_item.os_produto                                             ,
							tbl_os_item.pedido                                                 ,
							tbl_os_item.qtde                                                   ,
							tbl_os_item.liberacao_pedido                                       ,
							tbl_os_item.obs                                                    ,
							tbl_os_item.posicao                                                ,
							tbl_os_item.causa_defeito                                          ,
                            tbl_os_item.admin  as admin_peca                                    ,
							tbl_os_item.fornecedor                                              ,
							tbl_peca.referencia                                                ,
							tbl_peca.descricao                                                 ,
                            $select_peca_causador
                            $campos_cadence
							tbl_defeito.defeito                                                ,
							tbl_defeito.descricao                   AS defeito_descricao       ,
							tbl_produto.referencia                  AS subconjunto             ,
							tbl_os_produto.produto                                             ,
							tbl_os_produto.serie                                               ,
							tbl_os_extra.tecnico                                               ,
							tbl_servico_realizado.servico_realizado                            ,
							tbl_servico_realizado.descricao         AS servico_descricao       ,
							tbl_causa_defeito.descricao             AS causa_defeito_descricao,
							tbl_peca.promocao_site,
							tbl_os_item.parametros_adicionais
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_os_extra ON tbl_os_extra.os = tbl_os.os
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN tbl_defeito              USING (defeito)
					LEFT JOIN tbl_servico_realizado    USING (servico_realizado)
					LEFT JOIN tbl_causa_defeito ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
                    $join_peca_casaudor
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido                     IS NULL
					AND     tbl_os_item.liberacao_pedido_analisado IS FALSE
					ORDER BY tbl_os_item.os_item;";

			$res = pg_query ($con,$sql) ;

			if (pg_num_rows($res) > 0) {
				$fim_itens = $inicio_itens + pg_num_rows($res);
				$i = 0;
				$qtde = array();
				for ($k = $inicio_itens ; $k < $fim_itens ; $k++) {
                    $os_item[$i]  = "";
                    $os_item[$i]                 = pg_fetch_result($res,$i,os_item);
                 	$os_produto[$i]              = pg_fetch_result($res,$i,os_produto);
					$pedido[$k]                  = pg_fetch_result($res,$i,pedido);
					$peca[$k]                    = pg_fetch_result($res,$i,referencia);
					$qtde[$k]                    = pg_fetch_result($res,$i,qtde);
					$posicao[$k]                 = pg_fetch_result($res,$i,posicao);
					$produto[$k]                 = pg_fetch_result($res,$i,subconjunto);
					$serie[$k]                   = pg_fetch_result($res,$i,serie);
					$descricao[$k]               = pg_fetch_result($res,$i,descricao);

					if (in_array($login_fabrica,array(30,91))) {
						$pa = pg_fetch_result($res, $i, "parametros_adicionais");
						$pa = json_decode($pa, true);

	                	$kit_peca[$i] = $pa["kit_peca"];
	                	if($login_fabrica == 91){
                            $fornecedor[$i] = pg_fetch_result($res, $i, fornecedor);
                        }
	                }

	                if ($login_fabrica == 35) {
                        $po_peca_usa[$i] = pg_fetch_result($res, $i, "promocao_site");

                        if($po_peca_usa[$i] == "t"){
                            $parametros_adicionais = pg_fetch_result($res, $i, parametros_adicionais);
                            $parametros_adicionais = json_decode($parametros_adicionais, true);
                            $po_peca[$i] = $parametros_adicionais['po_peca'];
                        }
                    }

					$defeito[$k]                 = pg_fetch_result($res,$i,defeito);
					$defeito_descricao[$k]       = pg_fetch_result($res,$i,defeito_descricao);
					$pcausa_defeito[$k]          = pg_fetch_result($res,$i,causa_defeito);
					$causa_defeito_descricao[$k] = pg_fetch_result($res,$i,causa_defeito_descricao);
					$servico[$k]                 = pg_fetch_result($res,$i,servico_realizado);

                    if($login_fabrica == 35){
                        $troca_de_peca[$k]                  = pg_fetch_result($res,$i,troca_de_peca);
                        $peca_estoque[$k]                   = pg_fetch_result($res,$i,peca_estoque);
                    }

					$servico_descricao[$k]       = pg_fetch_result($res,$i,servico_descricao);
					$admin_peca[$k]              = pg_fetch_result($res,$i,admin_peca);//aqui
                    if($login_fabrica == 87){
                        $peca_causadora[$k]      = pg_fetch_result($res,$i,referencia_causadora);
                        $descricao_causadora[$k] = pg_fetch_result($res,$i,descricao_causadora);
                    }

					if(strlen($admin_peca[$k])==0) { $admin_peca[$k]="P"; }

					$i++;

				}
			}else{

				for ($i = 0 ; $i < $qtde_item ; $i++) {

                    $os_item[$i]            = $_POST["os_item_"        . $i];
					$os_produto[$i]         = $_POST["os_produto_"     . $i];
					$produto[$i]            = $_POST["produto_"        . $i];
					$serie[$i]              = $_POST["serie_"          . $i];
					$posicao[$i]            = $_POST["posicao_"        . $i];
					$peca[$i]               = $_POST["peca_"           . $i];
					$qtde[$i]               = $_POST["qtde_"           . $i];
					$defeito[$i]            = $_POST["defeito_"        . $i];
					$pcausa_defeito[$i]     = $_POST["pcausa_defeito_" . $i];
					$servico[$i]            = $_POST["servico_"        . $i];
					$admin_peca[$i]         = $_POST["admin_peca_"     . $i]; //aqui
					if (in_array($login_fabrica,array(3,24,30,91))) {
	                	$kit_peca[$i] = $xkit_peca[$i] = $_POST["kit_kit_peca_" . $i];
	                	if($login_fabrica == 91){
                            $fornecedor[$i] = $_POST['fornecedor_'.$i];
                        }
	                }
                    if($login_fabrica == 87){
                        $peca_causadora[$i] = trim($_POST["peca_causadora_".$i]);
                        $descricao_causadora[$i] = trim($_POST["descricao_causadora_".$i]);
                    }

					if (strlen($peca[$i]) > 0 and empty($kit_peca[$i])) {
						$sql = "SELECT  tbl_peca.referencia,
										tbl_peca.descricao
								FROM    tbl_peca
								WHERE   tbl_peca.fabrica    = $login_fabrica
								AND     tbl_peca.referencia = $peca[$i];";
						$resX = @pg_query ($con,$sql) ;

						if (@pg_num_rows($resX) > 0) {
							$descricao[$i] = trim(pg_fetch_result($resX,0,descricao));
						}
					}
					if (!empty($kit_peca[$i])) {
						$sql = "SELECT descricao
								FROM tbl_kit_peca
								WHERE fabrica = $login_fabrica
								AND kit_peca = {$kit_peca[$i]}";
						$resX = pg_query($con, $sql);

						if (pg_num_rows($resX) > 0) {
							$descricao[$i] = pg_fetch_result($resX, 0, "descricao");
						}
					}
				}
			}
		}else{
			$qtde = array();
            for ($i = 0 ; $i < $qtde_item ; $i++) {
            	$os_item[$i]        = $_POST["os_item_"        . $i];
				$os_produto[$i]     = $_POST["os_produto_"     . $i];
				$produto[$i]        = $_POST["produto_"        . $i];
				$serie[$i]          = $_POST["serie_"          . $i];
				$posicao[$i]        = $_POST["posicao_"        . $i];
				$peca[$i]           = $_POST["peca_"           . $i];
				$qtde[$i]           = $_POST["qtde_"           . $i];
				$defeito[$i]        = $_POST["defeito_"        . $i];
				$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
                $servico[$i]        = $_POST["servico_"        . $i];
				$fornecedor[$i]     = $_POST["fornecedor_"     . $i];
				$admin_peca[$i]     = $_POST["admin_peca_"     . $i];

                if($login_fabrica == 35 AND $servico[$i] != "SELECIONE"){
                    $sql_servico_realizado = "SELECT * FROM tbl_servico_realizado WHERE fabrica = $login_fabrica AND servico_realizado = $servico[$i]";
                    $res_servico_realizado = pg_query($con, $sql_servico_realizado);
                    if(pg_num_rows($res_servico_realizado)>0){
                        $troca_de_peca[$i] = pg_fetch_result($res_servico_realizado, 0, troca_de_peca);
                        $peca_estoque[$i]  = pg_fetch_result($res_servico_realizado, 0, peca_estoque);
                    }
                }

                if($login_fabrica == 87){
                    $peca_causadora[$i] = trim($_POST["peca_causadora_".$i]);
                    $descricao_causadora[$i] = trim($_POST["descricao_causadora_".$i]);
                }
                if (in_array($login_fabrica,array(3,24,30,91))) {
                	$kit_peca[$i] = $xkit_peca[$i] = $_POST["kit_kit_peca_" . $i];
                	if($login_fabrica == 91){
                        $fornecedor[$i] = $_POST['fornecedor_'.$i];
                    }
                }
				if (strlen($peca[$i]) > 0 and empty($kit_peca[$i])) {
					$sql = "SELECT  tbl_peca.referencia,
									tbl_peca.descricao
							FROM    tbl_peca
							WHERE   tbl_peca.fabrica    = $login_fabrica
							AND     tbl_peca.referencia = '$peca[$i]';";
					$resX = @pg_query ($con,$sql) ;

					if (pg_num_rows($resX) > 0) {
						$descricao[$i] = trim(pg_fetch_result($resX,0,descricao));
					}
				}
				if (!empty($kit_peca[$i])) {
					$sql = "SELECT descricao
							FROM tbl_kit_peca
							WHERE fabrica = $login_fabrica
							AND kit_peca = {$kit_peca[$i]}";
					$resX = pg_query($con, $sql);

					if (pg_num_rows($resX) > 0) {
						$descricao[$i] = pg_fetch_result($resX, 0, "descricao");
					}
				}
			}
		}

		$width = ($login_fabrica == 35) ? 900 : 700;

        if (in_array($login_fabrica, [123])) {

            $sqlPedidoTroca = " SELECT tbl_faturamento_item.faturamento,
                                       tbl_pedido.status_pedido,
                                       tbl_pedido.pedido,
                                       tbl_os_troca.gerar_pedido,
                                       tbl_os_troca.os_troca,
                                       tbl_os_troca.peca,
                                       tbl_os_produto.os_produto
                                FROM tbl_os_troca
                                JOIN tbl_os_produto USING(os)
                                LEFT JOIN tbl_pedido USING(pedido)
                                LEFT JOIN tbl_pedido_item USING(pedido)
                                LEFT JOIN tbl_faturamento_item ON tbl_pedido_item.pedido_item = tbl_faturamento_item.pedido_item
                                WHERE tbl_os_troca.os = {$os}
                                ORDER BY tbl_os_troca.os_troca DESC
                                LIMIT 1
                                ";
            $resPedidoTroca = pg_query($con, $sqlPedidoTroca);

            if (pg_num_rows($resPedidoTroca) > 0) {

                $statusPedido       = pg_fetch_result($resPedidoTroca, 0, 'status_pedido');
                $faturamentoPedido  = pg_fetch_result($resPedidoTroca, 0, 'faturamento');
                $pedidoTroca        = pg_fetch_result($resPedidoTroca, 0, 'pedido');
                $gerarPedido        = pg_fetch_result($resPedidoTroca, 0, 'gerar_pedido');
                $osTroca            = pg_fetch_result($resPedidoTroca, 0, 'os_troca');
                $pecaTroca          = pg_fetch_result($resPedidoTroca, 0, 'peca');
                $osProdutoTroca     = pg_fetch_result($resPedidoTroca, 0, 'os_produto');

                if (!empty($pedidoTroca) && $statusPedido != 14 && empty($faturamentoPedido)) {

                    $displayItens = "hidden";
                    $msgPedido    = "<br /><div style='background-color: red;color: white;font-size: 15px;' width='700'>
                                        Não é possível lançar itens pois essa OS possui um pedido de troca pendente.<br /> Clique <a href='pedido_admin_consulta.php?pedido={$pedidoTroca}' target='_blank'> aqui </a> e cancele o pedido de troca de produto pendente, ou aguarde o faturamento do mesmo.
                                     </div>";

                } else if (empty($pedidoTroca)) {

                    $displayItens = "hidden";
                    $msgPedido    = "<br /><div style='background-color: red;color: white;font-size: 15px;' width='700'>
                                        Existe uma troca de produto pendente para esta OS. <br /> Para lançar as peças, você pode cancelar a troca do produto clicando <a data-os_produto='{$osProdutoTroca}' data-peca='{$pecaTroca}' data-os_troca='{$osTroca}' href='#' id='cancela_troca'>aqui</a>.
                                     </div>";
				
                }
				$msgPedido .= "<input type='hidden' name='os_troca' value='$osTroca'>";
            }

        }

		echo "
        {$msgPedido}
        <table width='$width' border='0' cellspacing='1' cellpadding='0' id='lancamento_itens' class='tabela' {$displayItens}>";

            if($login_fabrica == 87){
              echo "<tr height='20' class='titulo_coluna'>";
                    echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência Peça</b></font></td>";
                    echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição Peça</b></font></td>";
                    echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>";
                    echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referencia Peça Causadora</b></font></td>";
                    echo "<td>Lista Básica</td>";
                    echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição Peça Causadora</b></font></td>";
                echo "</tr>";
            }else{
                echo "<tr height='20' class='titulo_coluna'>";

                    if ($os_item_subconjunto == 't') {
                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Subconjunto</b></font></td>";
                    }

                    if ($os_item_serie == 't' AND $os_item_subconjunto == 't') {
                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
                            if($login_fabrica==35){
                                echo "PO#";
                            }else{
                                echo "vvvv N. Série";
                            }
                        echo "</b></font></td>";
                    }

                    if ($login_fabrica == 14) echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Posição</b></font></td>";

                    echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Código</b></font>&nbsp;&nbsp;&nbsp;</td>";
                    echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'>Lista Básica</font></td>";

                    echo "<a class='lnk' href='peca_consulta_por_produto";
                    if($login_fabrica==6)echo "_tectoy";
                    echo ".php?produto=$produto_os";
                    if($login_fabrica==6)echo "&os=$os";
                    echo "' target='_blank'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'>Lista Básica</font></a></td>";
                    echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font>";

                    if ($pergunta_qtde_os_item == 't')

                    	echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>";

                    if ($pedir_causa_defeito_os_item == 't')
                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Causa</b></font></td>";

                    if($login_fabrica == 91){
?>
                        <td style="text-align:center;font-family:Geneva, Arial, Helvetica, san-serif;font-size:1;color:#FFF;font-weight:bold">Fornecedor da peça no produto</td>
<?
                    }

                    if (!in_array($login_fabrica,array(20,95)) and !in_array($login_fabrica,$fabricas_padrao_itens) and $login_fabrica < 99 or in_array($login_fabrica, array(120,201,172))){
                    echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Defeito</b></font></td>";
                    }
                    if (in_array($login_fabrica,$fabricas_padrao_itens) or $login_fabrica >= 108){
                        $width_fabricas_padrao_itens = "width='200px'";
                    }

                    echo (in_array($login_fabrica, array(20))) ? "" : "<td align='center' $width_fabricas_padrao_itens ><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Serviço</b></font></td>";

                echo "</tr>";
        }

		$loop = $qtde_item;

#		if (strlen($faturado) > 0) $loop = $qtde_item - $faturado;

		$offset = 0;
		// HD 20655 21313
		if($login_fabrica== 45){
			$loop = $loop+7;
			/*$sql="SELECT qtde_os_item
					FROM tbl_os
					JOIN tbl_posto_fabrica ON tbl_os.posto=tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=$login_fabrica
					where os      = $os
					and   tbl_os.fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);
			$qtde_os_item=pg_fetch_result($res,0,qtde_os_item);
			$loop=$qtde_os_item;*/
		}

		if ($login_fabrica == 74) {
			echo "<tr id='modelo' style='display: none;'>";
			//echo "<tr id='modelo' >";
                            if ($os_item_subconjunto == 'f') {
                                    echo "<input type='hidden' name='produto___modelo__' value='$produto_referencia' >
		                      <input type='hidden' name='descricao'>
		                      <input type='hidden' name='preco'>
		                      <input type='hidden' name='os_item___modelo__' value=''>
		                      <input type='hidden' name='os_produto___modelo__' value=''>
		                      <input type='hidden' name='admin_peca___modelo__' value=''>";
                            } else {
                                    echo "<td align='center'>
		                        <input type='hidden' name='descricao'>
		                        <input type='hidden' name='preco'>
		                        <input type='hidden' name='os_item___modelo__' value=''>
		                        <input type='hidden' name='os_produto___modelo__' value=''>
		                        <input type='hidden' name='admin_peca___modelo__' value=''>

                       			<select class='frm' size='1' name='produto___modelo__'>";
			                        $sql = "SELECT  tbl_produto.produto   ,
			                                        tbl_produto.referencia,
			                                        tbl_produto.descricao
			                                FROM    tbl_subproduto
			                                JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
			                                WHERE   tbl_subproduto.produto_pai = $produto_os
			                                ORDER BY tbl_produto.referencia;";
			                        $resX = pg_query ($con,$sql) ;

                        			echo "<option value='$produto_referencia'>$produto_descricao</option>";

                        			for ($x = 0 ; $x < pg_num_rows ($resX) ; $x++ ) {
                            			echo "<option value='" . pg_fetch_result ($resX,$x,referencia) . "'>" ;
                            					echo pg_fetch_result ($resX,$x,referencia) . " - " . substr(pg_fetch_result ($resX,$x,descricao),0,15) ;
                            			echo "</option>";
                        			}
                        		echo "</select>";
                        echo "</td>";
                    }

                    if ($os_item_subconjunto == 'f') {
                    	echo "<input type='hidden' name='serie___modelo__'>";
                    }else{
                        if ($os_item_serie == 't') {
                        	echo "<td align='center'>
                        			<input class='frm' type='text' name='serie___modelo__' size='9' value=''>
                        		</td>";
                        }
                    }

                    if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
                        $sql = "SELECT  tbl_peca.peca       ,
                                    tbl_peca.referencia     ,
                                    tbl_peca.descricao      ,
                                    tbl_lista_basica.qtde
                                FROM tbl_peca
                                    JOIN tbl_lista_basica USING (peca)
                                    JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.fabrica_i=$login_fabrica
                                WHERE tbl_produto.produto = $produto_os
                                    AND tbl_peca.fabrica = $login_fabrica
                                    AND tbl_peca.item_aparencia = 't'
                                ORDER BY tbl_peca.referencia
                                LIMIT 1 OFFSET $offset;";
                        $resX = @pg_query ($con,$sql) ;

                        if (@pg_num_rows($resX) > 0) {
                            $xpeca       = trim(pg_fetch_result($resX,0,peca));
                            $xreferencia = trim(pg_fetch_result($resX,0,referencia));
                            $xdescricao  = trim(pg_fetch_result($resX,0,descricao));
                            $xqtde       = trim(pg_fetch_result($resX,0,qtde));

                            echo "<td align='left'>
                            		<input class='frm' type='checkbox' name='peca___modelo__' value='$xreferencia' $check>
                            		&nbsp;
                            		<font face='arial' size='-2' color='#000000'>$xreferencia</font>
                            	</td>
                            	<td align='left'>
                            		<font face='arial' size='-2' color='#000000'>$xdescricao</font>
                            	</td>
                            	<td align='center'>
                            		<font face='arial' size='-2' color='#000000'>$xqtde</font>
                            		<input type='hidden' name='qtde___modelo__' value='$xqtde'>
                            	</td>";
                        }else{

                            echo "<td align='center' nowrap>
                            		<input class='frm' type='text' name='peca___modelo__' size='15' value=''>
                            		&nbsp;
                            		<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto___modelo__.value , document.frm_os.peca___modelo__ , document.frm_os.descricao___modelo__, document.frm_os.preco , document.frm_os.voltagem, \"referencia\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>
                            	</td>
                            	<td align='center' nowrap>
                            		<input class='frm' type='text'  name='descricao___modelo__' size='40' value=''>
                            		&nbsp;
                            		<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto___modelo__.value , document.frm_os.peca___modelo__ , document.frm_os.descricao___modelo__ , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>
                            	</td>";
                            if ($pergunta_qtde_os_item == 't') {
                                echo "<td align='center'>
                                		<input class='frm' type='text' name='qtde___modelo__' size='3' value=''>
                                	</td>";
                            }
                        }
                    } else {
                        echo "<td align='center' nowrap>
                        		<input class='frm' type='text' name='peca___modelo__' size='15' value='' >
                        		&nbsp;
                        		<a href='#'>
                        			<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto___modelo__.value , document.frm_os.peca___modelo__, document.frm_os.descricao___modelo__ , document.frm_os.preco , document.frm_os.voltagem,\"referencia\" , document.frm_os.qtde___modelo__)' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>
                        		</a>
                        	</td>
                        	<td align='center' nowrap>
                        		<input class='frm' type='text' name='descricao___modelo__' value='' size='40' >
                        		&nbsp;
                        		<a href='#'>
                        			<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto___modelo__.value , document.frm_os.peca___modelo__, document.frm_os.descricao___modelo__ , document.frm_os.preco , document.frm_os.voltagem,\"descricao\" , document.frm_os.qtde___modelo__)' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>
                        		</a>
                        	</td>";
                        if ($pergunta_qtde_os_item == 't') {
                            echo "<td align='center'>
                            		<input class='frm' type='text' name='qtde___modelo__' size='3' value=''>
                            	</td>";
                        }
                    }
                    if ($pedir_causa_defeito_os_item == 't') {
                        echo "<td align='center'>
                        		<select class='frm' size='1' name='pcausa_defeito___modelo__'>
                        			<option selected></option>";

			                        $sql = "SELECT *
			                                FROM tbl_causa_defeito
			                                WHERE fabrica = $login_fabrica
			                                ORDER BY codigo, descricao";
			                        $res = pg_query ($con,$sql) ;

			                        for ($x = 0 ; $x < pg_num_rows ($res) ; $x++ ) {
			                            echo "<option value='" . pg_fetch_result ($res,$x,causa_defeito) . "'>";
				                            echo pg_fetch_result ($res,$x,codigo) . " - " . pg_fetch_result ($res,$x,descricao) ;
			                            echo "</option>";
			                        }

                       		 echo "</select>
                        	</td>\n";
                    }

                    echo "<td align='center'>
                    	    <select class='frm' size='1' style='width:250px' width='400' name='defeito___modelo__' id='defeito___modelo__' onfocus='defeitoPeca(document.frm_os.peca___modelo__.value,__modelo__);' >
                        		<option selected id='op___modelo__' value='' ></option>";

		                        $sql = "SELECT *
		                                FROM   tbl_defeito
		                                WHERE  tbl_defeito.fabrica = $login_fabrica
		                                $sql_cond
		                                AND    tbl_defeito.ativo IS TRUE
		                                ORDER BY descricao;";
		                        $res = pg_query ($con,$sql) ;

		                        for ($x = 0 ; $x < pg_num_rows ($res) ; $x++ ) {
	                        		if ($login_fabrica == 91 ){
		                    		 	if( $defeito[$i] == pg_fetch_result($res,$x,defeito) ){
			                    		 	echo "<option selected";
			                    		 	$defeito_anterior = pg_fetch_result($res,$x,defeito);
			                    		 	echo " value='".pg_fetch_result($res,$x,defeito)."'>" ;
			                    		 	echo pg_fetch_result($res,$x,descricao);
		                    		 	}
			                    	}else{
			                            echo "<option ";

										if( $defeito[$i] == pg_fetch_result($res,$x,defeito) ){
											echo " selected ";
											# HD 925803
											$defeito_anterior = pg_fetch_result($res,$x,defeito); // recuperando o defeito setado
											//$id = $x;
										}

									echo " value='".pg_fetch_result($res,$x,defeito)."'>" ;
	 			                        if (strlen(trim(pg_fetch_result($res,$x,codigo_defeito))) > 0) {
	 			                            if($login_fabrica != 74){
	 			                                echo pg_fetch_result($res,$x,codigo_defeito);
	 			                                echo " - " ;
	 			                            }
	 			                        }
	 			                        echo pg_fetch_result($res,$x,descricao);
	 			                        echo "</option>";
 		                        	}
 		                    	}

                       	echo "</select>
                    	</td>";
                    echo "<td align='center'>
                    		<select class='frm' size='1' name='servico___modelo__' style='width:340px' onfocus='listaServico(document.frm_os.defeito___modelo__.value,__modelo__);' >
                    			<option selected></option>";


			                    $sql = "SELECT *
			                            FROM   tbl_servico_realizado
			                            WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";
                    			if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha IS NULL) ";
			                    $sql .= "AND tbl_servico_realizado.ativo   IS TRUE ";
			                    $sql .= "ORDER BY gera_pedido DESC, descricao ASC;";
                   				$res = pg_query($con,$sql) ;

			                    if (pg_num_rows($res) == 0 && ( $posto_interno !== true ) ) {
			                        $sql = "SELECT *
			                                FROM   tbl_servico_realizado
			                                WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";
			                        $sql .= "AND tbl_servico_realizado.linha IS NULL ";
			                        $sql .= "AND tbl_servico_realizado.ativo   IS TRUE ";
			                        $sql .= "ORDER BY gera_pedido DESC, descricao ASC;";
			                        $res = pg_query($con,$sql) ;
			                    }

			                    for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
			                        echo "<option value='" . pg_fetch_result ($res,$x,servico_realizado) . "'>" ;
			                        	echo pg_fetch_result ($res,$x,descricao) ;
			                        echo "</option>";
			                    }
                    		echo "</select>";

                    	echo "</td>";
			echo "</tr>";
		}

		if($login_fabrica == 24){
			if($linhas_itens > 0){
				$loop = $linhas_itens;
			}
		}

        if ($login_fabrica == 129){
            $loop = 1; // HD 2471645 - LIMITAR 1 ITEM POR O.S. RINNAI
        }

		for ($i = 0 ; $i < $loop ; $i++) {
			if($x % 2 == 0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";

			echo "<tr name='linha_$i' bgcolor='$cor'>";
				echo "<input type='hidden' name='kit_kit_peca_{$i}' value='{$kit_peca[$i]}'>";

                if($login_fabrica == 87){
                    echo "<input type='hidden' name='descricao'>";
                    echo "<input type='hidden' name='preco'>";
                    echo "<input type='hidden' name='os_item_$i' value='$os_item[$i]'>";
                    echo "<input type='hidden' name='os_produto_$i' value='$os_produto[$i]'>";
                    echo "<input type='hidden' name='admin_peca_$i' value='$admin_peca[$i]'>";
                    echo "<input type='hidden' name='produto_$i' value='$produto_referencia'>";

                    echo "<td align='center' nowrap><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]'>&nbsp;
                             <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i, document.frm_os.preco , document.frm_os.voltagem, \"referencia\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>
                          </td>";
                    echo "<td align='center' nowrap><input class='frm' type='text'  name='descricao_$i' size='40' value='$descricao[$i]'>&nbsp;
                            <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>
                         </td>";

                   echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]'></td>";

                   echo "<td align='center' nowrap><input class='frm' type='text' name='peca_causadora_$i' size='15' value='$peca_causadora[$i]'>&nbsp;
                            <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_causadora_$i , document.frm_os.descricao_causadora_$i, document.frm_os.preco , document.frm_os.voltagem, \"referencia\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>
                         </td>";
                   echo "<td align='center' nowrap><input class='frm' type='text'  name='descricao_causadora_$i' size='25' value='$descricao_causadora[$i]'>&nbsp;
                            <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_causadora_$i , document.frm_os.descricao_causadora_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>
                          </td>";
                }else{
                    if ($os_item_subconjunto == 'f') {
                        echo "<input type='hidden' name='produto_$i' value='$produto_referencia'>";
                        echo "<input type='hidden' name='descricao'>";
                        echo "<input type='hidden' name='preco'>";
                        echo "<input type='hidden' name='os_item_$i' value='$os_item[$i]'>";//aqui
                        echo "<input type='hidden' name='os_produto_$i' value='$os_produto[$i]'>";//aqui
                        echo "<input type='hidden' name='admin_peca_$i' value='$admin_peca[$i]'>";//aqui
                    }else{
                        echo "<td align='center'>";

                        echo "<input type='hidden' name='descricao'>";
                        echo "<input type='hidden' name='preco'>";
                        echo "<input type='hidden' name='os_item_$i' value='$os_item[$i]'>";//aqui
                        echo "<input type='hidden' name='os_produto_$i' value='$os_produto[$i]'>";//aqui
                        echo "<input type='hidden' name='admin_peca_$i' value='$admin_peca[$i]'>";//aqui
                        echo "<select class='frm' size='1' name='produto_$i'>";
                        #echo "<option></option>";

                        $sql = "SELECT  tbl_produto.produto   ,
                                        tbl_produto.referencia,
                                        tbl_produto.descricao
                                FROM    tbl_subproduto
                                JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
                                WHERE   tbl_subproduto.produto_pai = $produto_os
                                ORDER BY tbl_produto.referencia;";
                        $resX = pg_query ($con,$sql) ;

                        echo "<option value='$produto_referencia' ";
                        if ($produto[$i] == $produto_referencia) echo " selected ";
                        echo " >$produto_descricao</option>";

                        for ($x = 0 ; $x < pg_num_rows ($resX) ; $x++ ) {
                            echo "<option ";
                            if (trim ($produto[$i]) == trim (pg_fetch_result ($resX,$x,referencia))) echo " selected ";
                            echo " value='" . pg_fetch_result ($resX,$x,referencia) . "'>" ;
                            echo pg_fetch_result ($resX,$x,referencia) . " - " . substr(pg_fetch_result ($resX,$x,descricao),0,15) ;
                            echo "</option>";
                        }

                        echo "</select>";
                        echo "</td>";
                    }

                    if ($os_item_subconjunto == 'f') {
                        $xproduto = $produto[$i];
                        echo "<input type='hidden' name='serie_$i'>";
                    }else{
                        if ($os_item_serie == 't') {
                            echo "<td align='center'><input class='frm' type='text' name='serie_$i' size='9' value='$serie[$i]'></td>";
                        }
                    }

                    if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
                        $sql = "SELECT  tbl_peca.peca       ,
                                    tbl_peca.referencia     ,
                                    tbl_peca.descricao      ,
                                    tbl_lista_basica.qtde
                                FROM tbl_peca
                                    JOIN tbl_lista_basica USING (peca)
				    JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.fabrica_i=$login_fabrica
                                WHERE tbl_produto.produto = $produto_os
                                    AND tbl_peca.fabrica = $login_fabrica
                                    AND tbl_peca.item_aparencia = 't'
                                ORDER BY tbl_peca.referencia
                                LIMIT 1 OFFSET $offset;";
                        $resX = @pg_query ($con,$sql) ;

                        if (@pg_num_rows($resX) > 0) {
                            $xpeca       = trim(pg_fetch_result($resX,0,peca));
                            $xreferencia = trim(pg_fetch_result($resX,0,referencia));
                            $xdescricao  = trim(pg_fetch_result($resX,0,descricao));
                            $xqtde       = trim(pg_fetch_result($resX,0,qtde));

                            if ($peca[$i] == $xreferencia)
                                $check = " checked ";
                            else
                                $check = "";

                            echo "<td align='left'>
			      <input class='frm' type='checkbox' name='peca_$i' value='$xreferencia' $check>&nbsp;<font face='arial' size='-2' color='#000000'>$xreferencia</font>
			  </td>";
                            echo "<td align='left'><font face='arial' size='-2' color='#000000'>$xdescricao</font></td>";
                            echo "<td align='center'><font face='arial' size='-2' color='#000000'>$xqtde</font><input type='hidden' name='qtde_$i' value='$xqtde'></td>";

                            if ($login_fabrica == 6) {
                                if (strlen ($defeito[$i]) == 0) $defeito[$i] = 78 ;
                                if (strlen ($servico[$i]) == 0) $servico[$i] = 1 ;
                            }
                        }else{

                            echo "<td align='center' nowrap><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i, document.frm_os.preco , document.frm_os.voltagem, \"referencia\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
                            echo "<td align='center'>";
                            echo "<img src='imagens/btn_lista.gif' border='0' align='absmiddle'";
                            if($login_fabrica == 74) echo " onclick='javascript: fnc_pesquisa_lista_serie($i,document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , \"$produto_serie\", \"tudo\", document.frm_os.qtde_$i)'";
                            else echo "onclick='javascript: pesquisaPeca(document.frm_os.produto_$i, document.frm_os.peca_$i ,&quot;tudo&quot;,".($i).",document.frm_os.kit_peca_{$i},&quot;$os&quot;)'";
                            echo "alt='LISTA BÁSICA' style='cursor:pointer;'>";
                            echo "</td>";
                            echo "<td align='center' nowrap><input class='frm' type='text'  name='descricao_$i' size='40' value='$descricao[$i]'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";

                            if ($pergunta_qtde_os_item == 't') {
                                echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]'></td>";
                            }
                        }
                    }else{
                        if ($login_fabrica == 14) echo "<td align='center'><input class='frm' type='text' name='posicao_$i' size='5' maxlength='5' value='$posicao[$i]'></td>\n";

		# HD 925803 // adicionado o hidden da peca_anterior
                        echo "<td align='center' nowrap>";
                        if($login_fabrica == 74){
                            echo "<input type='hidden' id='depara_auditoria_$i' name='depara_auditoria_$i' value='$depara_auditoria' />";
                        }
                        echo "<input type='hidden' name='peca_".$i."_anterior' value='$peca[$i]' />
	                       <input class='frm' type='text' name='peca_$i' id='peca_$i' size='15' value='$peca[$i]'";
                        if (in_array($login_fabrica,array(98,106,108,111))) echo "onblur=\"javascript: fnc_pesquisa_peca_critica (document.frm_os.peca_$i,$i)\"";
                        if ($login_fabrica == 5) echo "onblur=\"javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, 'referencia', document.frm_os.qtde_$i)\"";
                        if($login_fabrica == 50) echo " onkeyup=\"limpaDefeito($i)\"";
                        echo ">&nbsp;<a href='#'><img src='imagens/lupa.png' border='0' align='absmiddle'";
                        if ($login_fabrica==40)	echo " onclick='javascript: fnc_pesquisa_peca_lista_masterfrio (document.frm_os.produto_$i.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, document.frm_os.defeito_constatado, \"referencia\" ,document.frm_os.qtde_$i)'";
                        if ($login_fabrica == 14) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i ,document.frm_os.voltagem, \"referencia\")'";
                        if ($login_fabrica == 134) echo "  onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"referencia\")'";
                        //hd_chamado=2881143
                        if($login_fabrica == 19) echo "onclick='javascript: fnc_pesquisa_lista_basica_lorenzetti (document.frm_os.produto_$i, document.frm_os.peca_$i ,&quot;peca&quot;,".($i).",document.frm_os.kit_peca_{$i},&quot;$os&quot;, document.frm_os.defeitos_lorenzetti_hidden)'";
                        // fim hd_chamado=2881143

                        if($login_fabrica == 74) echo " onclick='javascript: fnc_pesquisa_lista_serie($i,document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , \"$produto_serie\", \"referencia\", document.frm_os.qtde_$i)'";
                        else echo "  onclick='javascript: pesquisaPeca (document.frm_os.produto_$i, document.frm_os.peca_$i ,&quot;peca&quot;,".($i).",document.frm_os.kit_peca_{$i},&quot;$os&quot;)'";
                        echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></a></td>";
                        echo "<td align='center'>";
                        echo "<img src='imagens/btn_lista.gif' class='lista_basica' style='display: none; cursor: pointer;' border='0' align='absmiddle'";
                        //hd_chamado=2881143
                        if($login_fabrica == 19) echo "onclick='javascript: fnc_pesquisa_lista_basica_lorenzetti (document.frm_os.produto_$i, document.frm_os.peca_$i ,&quot;tudo&quot;,".($i).",document.frm_os.kit_peca_{$i},&quot;$os&quot;, document.frm_os.defeitos_lorenzetti_hidden)'";
                        // fim hd_chamado=2881143
                        if($login_fabrica == 74) echo " onclick='javascript: fnc_pesquisa_lista_serie($i,document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , \"$produto_serie\", \"tudo\", document.frm_os.qtde_$i)'";
                        else echo "onclick='javascript: pesquisaPeca (document.frm_os.produto_$i, document.frm_os.peca_$i ,&quot;tudo&quot;,".($i).",document.frm_os.kit_peca_{$i},&quot;$os&quot;)'";
                        echo "alt='LISTA BÁSICA' style='cursor:pointer;'>";
                        echo "</td>";
                        echo "<td align='center' nowrap>
                        		 <input class='frm' type='hidden' name='descricao_{$i}_anterior' value='$descricao[$i]' />
                        		 <input class='frm' type='text' name='descricao_$i' id='descricao_$i' value='{$descricao[$i]}'";

                        if($login_fabrica == 94 or in_array($login_fabrica,$fabricas_padrao_itens)){

                            if($login_fabrica >= 108){
                                echo "size='65'";
                            }else{
                                echo "size='45'";

                            }
                        }else{
                            echo "size='30'";
                        }

                        if (in_array($login_fabrica,array(98,106,108,111))) echo "onblur=\"javascript: fnc_pesquisa_peca_critica (document.frm_os.peca_$i,$i)\"";
                        if ($login_fabrica == 5) echo "onblur=\"javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, 'descricao')\"";
                        echo ">&nbsp;<a href='#'><img src='imagens/lupa.png' border='0' align='absmiddle'";
                        if ($login_fabrica==40) echo " onclick='javascript: fnc_pesquisa_peca_lista_masterfrio (document.frm_os.produto_$i.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, document.frm_os.defeito_constatado, \"descricao\" ,document.frm_os.qtde_$i)'";
                        if ($login_fabrica == 14) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"descricao\")'";
                        //hd_chamado=2881143
                        if($login_fabrica == 19) echo "onclick='javascript: fnc_pesquisa_lista_basica_lorenzetti (document.frm_os.produto_$i, document.frm_os.descricao_$i ,&quot;descricao&quot;,".($i).",document.frm_os.kit_peca_{$i},&quot;$os&quot;, document.frm_os.defeitos_lorenzetti_hidden)'";
                        // fim hd_chamado=2881143
                        if($login_fabrica == 74) echo " onclick='javascript: fnc_pesquisa_lista_serie($i,document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , \"$produto_serie\", \"descricao\", document.frm_os.qtde_$i)'";
                        else echo " onclick='javascript: pesquisaPeca (document.frm_os.produto_$i, document.frm_os.descricao_$i ,&quot;descricao&quot;,".($i).",document.frm_os.kit_peca_{$i},&quot;$os&quot;)'";
                        echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></a></td>";




                        if ($pergunta_qtde_os_item == 't') {

                        	$onclick_limpa_movimentacao = (in_array($login_fabrica, array(35,72,74))) ? "onChange='limpaMovimentacaoEstoque($i)'": "";

                            echo "<td align='center'><input class='frm' type='text' name='qtde_$i' id='yqtde_$i' size='3' value='$qtde[$i]' $onclick_limpa_movimentacao></td>";
                        }
                    }

                   /**
                    * - COLUNA para Fornecedor da peça
                    */

                    if($login_fabrica == 91){
?>
                        <td style="text-align:center">
                             <select class="frm" name="fornecedor_<?=$i?>">
                                 <option id='op_<?=$i?>' value="">Selecione uma peça</option>
<?
                        if($peca[$i] > 0){
                            $sqlF = "
                                SELECT  tbl_fornecedor_peca.fornecedor  AS retorno_fornecedor_peca      ,
                                        tbl_fornecedor.nome             AS retorno_fornecedor_peca_nome
                                FROM    tbl_fornecedor_peca
                                JOIN    tbl_fornecedor  ON  tbl_fornecedor.fornecedor   = tbl_fornecedor_peca.fornecedor
                                JOIN    tbl_peca        ON  tbl_peca.peca               = tbl_fornecedor_peca.peca
                                                        AND tbl_peca.referencia         = '$peca[$i]'
                                WHERE   tbl_fornecedor_peca.fabrica = $login_fabrica
                            ";
                            $resF = pg_query($con,$sqlF);
                            if(pg_num_rows($resF) > 0){
                                for($f = 0; $f < pg_num_rows($resF); $f++){
                                    $retorno_fornecedor_peca = pg_fetch_result($resF,$f,retorno_fornecedor_peca);
                                    if($retorno_fornecedor_peca == $fornecedor[$i]){
                                        $selected = "selected = 'selected'";
                                    }else{
                                        $selected = "";
                                    }
?>
                        <option value="<?=$retorno_fornecedor_peca?>" <?=$selected?>><?=pg_fetch_result($resF,$f,retorno_fornecedor_peca_nome)?></option>
<?
                                }
                            }
                        }
?>
                             </select>
                        </td>
<?
                    }

                    ##### C A U S A   D O   D E F E I T O   D O   I T E M #####
                    if ($pedir_causa_defeito_os_item == 't' AND $login_fabrica<>20) {
                        echo "<td align='center'>";
                        echo "<select class='frm' size='1' name='pcausa_defeito_$i'>";
                        echo "<option selected></option>";

                        $sql =	"SELECT *
                                FROM tbl_causa_defeito
                                WHERE fabrica = $login_fabrica
                                ORDER BY codigo, descricao";
                        $res = pg_query ($con,$sql) ;

                        for ($x = 0 ; $x < pg_num_rows ($res) ; $x++ ) {
                            echo "<option ";
                            if ($pcausa_defeito[$i] == pg_fetch_result ($res,$x,causa_defeito)) echo " selected ";
                            echo " value='" . pg_fetch_result ($res,$x,causa_defeito) . "'>" ;
                            echo pg_fetch_result ($res,$x,codigo) ;
                            echo " - ";
                            echo pg_fetch_result ($res,$x,descricao) ;
                            echo "</option>";
                        }

                        echo "</select>";
                        echo "</td>\n";
                    }



                    if($login_fabrica != 95){
                        ##### D E F E I T O   D O   I T E M #####
                        if(!in_array($login_fabrica,$fabricas_padrao_itens) && $login_fabrica < 99 or in_array($login_fabrica, array(91,120,201,172)) ){

                        echo "<td align='center'>";
                        echo "<select class='frm' size='1' name='defeito_$i' id='defeito_$i'";

						# HD 925803
						$id_defeito_ = $i;

                        if ($login_fabrica == 50 and !empty($defeito[$i])){
                            $sql_cond = "AND tbl_defeito.defeito = $defeito[$i]";
                            echo " style='width:150px;' onfocus='defeitoLista(document.frm_os.peca_$i.value,$i,$os);'";
                        }
                        if(in_array($login_fabrica,array(50,74,91,30))){
                        	if ($login_fabrica == 91 or $login_fabrica == 30){
                        		echo " style='width:150px; ";
                            	echo "' onfocus='defeitoListaNova(document.frm_os.peca_$i.value,$i,$os);'";
                        	}else{
                            	echo "' onfocus='defeitoPeca(document.frm_os.peca_$i.value,$i);'";
                        	}
                    	}
                        echo " >";
                        echo "<option ";
                        if ($login_fabrica == 50 or $login_fabrica == 74 or $login_fabrica == 91){
                            echo " id='op_$i' value=''";
                        }else{
                            echo "value='' selected";
                        }
                        echo " >SELECIONE</option>";

                        $rows = 0;
                        if (in_array($login_fabrica,array(30,120,201))) {
                            if ($login_fabrica == 30) {
                                $join = "
                                    JOIN tbl_familia_peca ON tbl_familia_peca.familia_peca = tbl_peca_defeito.familia_peca
                                    JOIN tbl_peca_familia ON tbl_peca_familia.familia_peca = tbl_familia_peca.familia_peca
                                    JOIN tbl_peca ON tbl_peca.peca = tbl_peca_familia.peca
                                ";
                            } else {
                                $dist = " DISTINCT ";
                                $join  = "
                                    JOIN tbl_peca USING(peca)
                                ";
                                $where = " AND tbl_peca_defeito.ativo IS TRUE ";
                            }
                            $sql = "
                                SELECT  $dist
                                        tbl_defeito.descricao,
                                        tbl_defeito.defeito,
                                        tbl_defeito.codigo_defeito
                                FROM    tbl_defeito
                                JOIN tbl_peca_defeito ON tbl_peca_defeito.defeito = tbl_defeito.defeito
                                $join
                                WHERE tbl_defeito.fabrica = $login_fabrica
                                AND tbl_defeito.ativo = 't'
                                AND tbl_peca.referencia = '$peca[$i]'
                                $where
                                ORDER BY tbl_defeito.descricao
                            ";
                            $res = pg_query($con,$sql);
                            $rows = pg_num_rows($res);
                        }

                        if ($rows == 0 && $login_fabrica <> 120 and $login_fabrica <> 201) {
                            $sql = "SELECT *
                                    FROM   tbl_defeito
                                    WHERE  tbl_defeito.fabrica = $login_fabrica
                                    $sql_cond
                                    AND    tbl_defeito.ativo IS TRUE
                                    ORDER BY descricao;";
                            $res = pg_query ($con,$sql) ;
                        }

                        for ($x = 0 ; $x < pg_num_rows ($res) ; $x++ ) {
                            echo "<option ";

							if( $defeito[$i] == pg_fetch_result($res,$x,defeito) ){
								echo " selected ";
								# HD 925803
								$defeito_anterior = pg_fetch_result($res,$x,defeito); // recuperando o defeito setado
								//$id = $x;
							}

                            echo " value='".pg_fetch_result($res,$x,defeito)."'>" ;
                            if (strlen(trim(pg_fetch_result($res,$x,codigo_defeito))) > 0) {
                                if($login_fabrica != 74){
                                    echo pg_fetch_result($res,$x,codigo_defeito);
                                    echo " - " ;
                                }
                            }
                            echo pg_fetch_result($res,$x,descricao);
                            echo "</option>";
                        }

	                    echo "</select>";

 						# HD 925803
						if( isset($defeito_anterior) )
							echo " <input type='hidden' name='defeito_{$id_defeito_}_anterior' value='".$defeito_anterior."' />";

                        echo "</td>";
                        }
                    }

                     $onclick_limpa_movimentacao = ($login_fabrica == 74) ? "onChange='limpaMovimentacaoEstoque($i)'": "";

                if($login_fabrica != 20){

                    echo "<td align='center'>";

                    # HD 107402 aletrado o tamanho do combo para
                    echo "<select class='frm select_servico_realizado' size='1' name='servico_$i' id='yservico_$i' data-posicao='".$i."' alt='".($i+1)."' $onclick_limpa_movimentacao ";

                    if($login_fabrica == 131){
	                    echo " onfocus='listaServico(document.frm_os.defeito_constatado_hidden.value,$i);'";
	                }

					# HD 925803
					$id_servico_anterior_ = $i;

                    if($login_fabrica != 94){
                        if (in_array($login_fabrica,array('108','111'))){
                        echo "style='width:200px'";
                        }
                    }
                    if($login_fabrica == 74){
                        echo " onfocus='listaServico(document.frm_os.defeito_$i.value,$i);'";
                    }
                    if(in_array($login_fabrica,array(98,106,108,111))){
			    		echo "onchange=\"javascript: fnc_pesquisa_peca_critica (document.frm_os.peca_$i,$i)\" ";
					}else{
                        if ($login_fabrica != 129) { // HD 2471645 - LIMITAR 1 ITEM POR O.S. RINNAI
                        echo "onchange=\"javascript: adiciona_linha($(this).attr('alt'))\" ";
                        }
				    }
                    echo ">";
                    echo "<option selected value=''>SELECIONE</option>";

                    if($login_fabrica == 134 AND $controla_estoque != 't'){
                        $cond_recompra = " AND tbl_servico_realizado.peca_estoque is not true ";
                    }

                    #### SERVIÇO REALIZADO #####
                    $sql = "SELECT *
                            FROM   tbl_servico_realizado
                            WHERE  tbl_servico_realizado.fabrica = $login_fabrica
                            $cond_recompra ";

                    if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha IS NULL) ";
                    //(tbl_servico_realizado.linha = '203' OR tbl_servico_realizado.linha IS NULL)

                    if($login_fabrica == 134 && $controla_estoque == "t"){
                        $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'Troca de peça%' ";
                    }



                    if ($login_fabrica == 1) {
                        if ($tipo_atendimento == 334) {
                            $sql .= " AND tbl_servico_realizado.descricao ILIKE 'Substitui%o%'";
                        } else {
                            if ($reembolso_peca_estoque == 't') {
                                $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
                            } else {
                                $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'subst%' ";
                            }
                        }
                    }
                    if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS TRUE ";
                    // HD 415550
                    if($login_fabrica == 94) {
                        $val_posto_interno = ($posto_interno === true) ? 'TRUE' : 'FALSE';
                        $sql .= " AND posto_interno IS " . $val_posto_interno . " ";
                    }
        		    // HD 415550 - fim
        		    if (!empty($os) AND !empty($os_item[$i]) AND $login_fabrica == 74) {
        			$sql .= "AND (tbl_servico_realizado.ativo OR tbl_servico_realizado.peca_estoque)";
        		    }elseif (in_array($login_fabrica, [24])) {
                        $sql .= "AND (tbl_servico_realizado.ativo IS TRUE OR tbl_servico_realizado.servico_realizado IN (11417,11421)) "; 
                    }elseif($login_fabrica != 35) {
        			    $sql .= "AND tbl_servico_realizado.ativo   IS TRUE ";
        		    }

                    if($login_fabrica == 35 ){
                        if( $troca_de_peca[$i] == 't' AND
                        $peca_estoque[$i] == 't'){
                        $sql .= " AND tbl_servico_realizado.troca_de_peca IS TRUE
                                  AND  tbl_servico_realizado.peca_estoque IS TRUE ";
                        }else{
                            $sql .= " AND tbl_servico_realizado.ativo   IS TRUE ";
                        }
                    }

                    $sql .= "ORDER BY gera_pedido DESC, descricao ASC;";

                    //echo $sql;

                    $res = pg_query($con,$sql) ;

                    if (pg_num_rows($res) == 0 && ( $login_fabrica != 94 && $posto_interno !== true )  ) { // HD 415550
                        $sql = "SELECT *
                                FROM   tbl_servico_realizado
                                WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

                        if ($login_fabrica == 1) {
                            if ($reembolso_peca_estoque == 't')
                                $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
                            else
                                $sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'subst%' ";
                        }

                        $sql .= "AND tbl_servico_realizado.linha IS NULL ";
                        $sql .= "AND tbl_servico_realizado.ativo   IS TRUE ";
                        if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS TRUE ";
                        $sql .= "ORDER BY gera_pedido DESC, descricao ASC;";

                        $res = pg_query($con,$sql) ;
                    }

                    if (($login_fabrica == 120 or $login_fabrica == 201) && !empty($os)) {
                        $sql = "
                            SELECT  tbl_servico_realizado.descricao,
                                    tbl_servico_realizado.servico_realizado
                            FROM    tbl_servico_realizado
                            JOIN    tbl_peca_defeito USING(servico_realizado)
                            JOIN    tbl_peca USING(peca)
                            WHERE   defeito             = ".$defeito[$i]."
                            AND     tbl_peca.referencia = '".$peca[$i]."'
                            AND     tbl_peca.fabrica    = $login_fabrica
                        ";
                    }

                    for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
                        echo "<option ";

                        $isExcluidaAud = false;

						if( $servico[$i] == pg_fetch_result($res,$x,servico_realizado) || (in_array($login_fabrica, [24]) && ($serivico[$i] == 11417 || $servico[$i] == 11421))) {
							echo " selected ";
							# HD 925803
							$servico_anterior = pg_fetch_result($res,$x,servico_realizado); // recuperando o serviço setado
							//$id = $x;

                            if (in_array($login_fabrica, [24])) {
                                $isExcluidaAud = true;
                            }
						}

                        if (in_array($login_fabrica, [24])) {
                            if (in_array($servico[$i], [11417,11421])) {
                                echo "class='disable-select'";
                            }
                        }

                        echo " value='".pg_fetch_result($res,$x,servico_realizado)."'>" ;
                        echo ($isExcluidaAud) ? utf8_decode(pg_fetch_result($res,$x,descricao)) : pg_fetch_result($res,$x,descricao);
                        if (pg_fetch_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
                        echo "</option>";
                    }

                    echo "</select>";

                    if($login_fabrica == 35){
                    	$po_peca_usa[$i];
		                $displayPOPeca = ($po_peca_usa[$i] == "t") ? "block" : "none";

		                echo "
		                    <br />
		                    <div id='po_peca_{$i}' style='display: $displayPOPeca;'>
		                        <strong>PO-Peça:</strong> <input type='text' name='po_peca_{$i}' value='$po_peca[$i]' />
		                    </div>
		                ";
		            }

					# HD 925803
					if( isset($servico) )
						echo " <input type='hidden' name='servico_{$id_servico_anterior_}_anterior' value='".$servico_anterior."' />";

                    echo "</td>";

                }
            }
			echo "</tr>";

			if (in_array($login_fabrica,array(3,24,30,91))) {
                echo "<tr>
                        <td colspan='7'>

                            <div id='kit_peca_$i'><input type='hidden' name='kit_peca_$i' value='kit_peca_$i'>";
                if(!empty($xkit_peca[$i])) {
                   $sql = " SELECT tbl_peca.peca      ,
                                    tbl_peca.referencia,
                                    tbl_peca.descricao,
                                    tbl_kit_peca_peca.qtde
                            FROM    tbl_kit_peca_peca
                            JOIN    tbl_peca USING(peca)
                            WHERE   fabrica = $login_fabrica
                            AND     kit_peca = {$xkit_peca[$i]}
                            ORDER BY tbl_peca.peca";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){
                            echo "<table>";
                        for ($k = 0; $k < pg_num_rows($res); $k++) {
                            $kit_peca_peca = pg_fetch_result($res,$k,'peca');
                            $kit_peca_qtde = pg_fetch_result($res,$k,'qtde');

                            if ($_POST["kit_peca_$kit_peca_peca"]) {
                                $checked = "checked";
                            } else {
                                $checked = "";
                            }

                            echo "<tr style='font-size: 11px'>";
                                echo "<td>";
                                    echo "<input type='hidden' name='kit_peca_$kit_peca_peca' $checked value='$kit_peca_peca'>";
                                    echo "<input type='text' name='kit_peca_qtde_$kit_peca_peca' id='kit_peca_qtde_$kit_peca_peca' value='" . $_POST["kit_peca_qtde_$kit_peca_peca"] . "' size='5' onkeyup=\"re = /\D/g; this.value = this.value.replace(re, '');\" readonly='readonly'> x ";
                                echo "</td>";
                                echo "<td> - ";
                                echo pg_fetch_result($res,$k,'descricao');
                                echo "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                }
                    echo "</div>
                    </td>
                </tr>";
			}else{
			    echo "<div id='kit_peca_$i'><input type='hidden' name='kit_peca_$i'>";
			}

			$offset = $offset + 1;
		}
		if(!empty($os)){
		?>
			<script type='text/javascript'>
				<?php if($login_fabrica != 24 AND $login_fabrica != 129 AND $login_fabrica != 42){ ?>
				$(function(){
					adiciona_linha($("#lancamento_itens > tbody").find("tr[name^=linha_]").length);
				});
				<?php } ?>
			</script>
		<?php
		}
// 		echo "$teste<BR>$teste2";
		echo "</table>";
		?>
	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>
<script type="text/javascript">

$(function(){

    $("#cancela_troca").click(function(){

        let os_troca   = $(this).data("os_troca");
        let peca       = $(this).data("peca");
        let os_produto = $(this).data("os_produto");
        let divMsg     = $(this).closest("div");

        $.ajax({
            url: window.location,
            type: "POST",
            dataType: "JSON",
            data: {
                ajax_cancela_troca: true,
                os_troca: os_troca,
                peca: peca,
                os_produto: os_produto
            },
            beforeSend:function(){
                $(divMsg).html("Cancelando Troca, aguarde...");
            },
            success: function(data){
                
                if (data.erro) {

                    alert(data.msg);

                } else {

                    window.location.href = 'os_item.php?os=<?= $os ?>';

                }
                
            }
        });

    });

    var Posto_SAC = <?="'{$Posto_SAC}'";?>;
    if (Posto_SAC == 'SAC') {
        $('#lancamento_itens').hide();
        for (var i = 0; i < 50; i++) {
            if ($('input[name=peca_'+i+']').length) {
                $('input[name=kit_kit_peca_'+i+']').val('');
                $('input[name=produto_'+i+']').val('');
                $('input[name=os_item_'+i+']').val('');
                $('input[name=os_produto_'+i+']').val('');
                $('input[name=admin_peca_'+i+']').val('');
                $('input[name=serie_'+i+']').val('');
                $('input[name=peca_'+i+'_anterior]').val('');
                $('input[name=peca_'+i+']').val('');
                $('input[name=descricao_'+i+']').val('');
                $('input[name=descricao_'+i+'_anterior]').val('');
                $('input[name=peca_'+i+']').val('');
                $('input[name=qtde_'+i+']').val('');
                $('select[name=defeito_'+i+']').val('');
                $('select[name=servico_'+i+']').val('');
            }
        }
    }

    $(".select_servico_realizado").each(function(index){
        var selectClass = $(this);
        var val         = $(this).children("option:selected").val()

        if (val.length > 0 || val !== null || val !== undefined) {
            var optionSelected = $(this).children("option:selected");

            if (optionSelected.hasClass('disable-select')) {
                selectClass.addClass('select_bloqueado');
                /* selectClass.attr('readonly', true); */   
            }
        }
    });
});
</script>
<?


 /**
  * @author William Castro <william.castro@telecontrol.com.br>
  * hd-6639553 -> Box Uploader
  *
  */  
?>
<center>
<?php
if ($fabricaFileUploadOS) {

    $anexos_obrigatorios = ["notafiscal", "produto", "peca"];

    $boxUploader = array(
     "div_id" => "div_anexos",
      "prepend" => $anexo_prepend,
      "context" => "os",
      "unique_id" => $tempUniqueId,
      "hash_temp" => $anexoNoHash,
      "reference_id" => 0,
      "bootstrap"  => 'f'
    );
    include "box_uploader.php";
} ?>
</center>
<?php
// tecnico para Fricon HD-896985
if($login_fabrica == 52){



	$nome_tecnico = $_POST['nome_tecnico'];
	$rg_tecnico = $_POST['rg_tecnico'];




	echo "<table align=\"center\" style=\"margin-top: 20px;\">";

	echo "<tr height=\"20\" bgcolor=\"#666666\">";


    echo "<td align='center' nowrap><font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\" color=\"#ffffff\"><b>Nome do técnico</b></font>

    </td>";
    echo "<td align='center' nowrap><font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\" color=\"#ffffff\"><b>RG do técnico</b></font>

    </td>";

	echo "</tr>";

	echo "<tr>";

    echo "<td align='center' nowrap><input class='frm' type='text'  name='nome_tecnico' size='25' value='$nome_tecnico'>&nbsp;

    </td>";
    echo "<td align='center' nowrap><input class='frm' type='text'  name='rg_tecnico' size='25' value='$rg_tecnico' maxlength=\"14\">&nbsp;

    </td>";
    echo "</tr></table>";
}
// tecnico para Fricon HD-896985

if($compressor=='t' or ($tipo_os == 13 )){

	$sql = "SELECT  tbl_os.tipo_atendimento
			FROM    tbl_os
			WHERE   tbl_os.os = $os";
	$res = pg_query ($con,$sql) ;

	if (@pg_num_rows($res) > 0) {
		$tipo_atendimento = pg_fetch_result($res,0,tipo_atendimento) ;
	}

	$sql_tec = "SELECT tecnico from tbl_os_extra where os=$os";
	$res_tec = pg_query($con,$sql_tec);
	$tecnico            = trim(@pg_fetch_result($res_tec,0,tecnico));

	if(strlen($msg_erro) >0) {
		$tecnico = $_POST['tecnico'];
	}

	if($compressor=='t' or ($tipo_os == 13 and ($tipo_atendimento == 65 or $tipo_atendimento == 69))) {
		$sql_posto = "SELECT contato_endereco AS endereco,
						contato_numero   AS numero  ,
						contato_bairro   AS bairro  ,
						contato_cidade   AS cidade  ,
						contato_estado   AS estado  ,
						contato_cep      AS cep     ,
						consumidor_endereco         ,
						consumidor_numero           ,
						consumidor_bairro           ,
						consumidor_cidade           ,
						consumidor_estado           ,
						consumidor_cep
					FROM tbl_os
					JOIN tbl_posto_fabrica USING(posto)
					WHERE tbl_os.posto   = $login_posto
					AND   tbl_os.os = $os
					AND   tbl_os.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.fabrica = $login_fabrica";

		$res_posto = pg_query($con,$sql_posto);
		if(pg_num_rows($res_posto)>0){

			$endereco_posto = "";
			$endereco_posto .= (strlen(pg_fetch_result($res_posto,0,endereco)) > 0) ? pg_fetch_result($res_posto,0,endereco) : "";
			$endereco_posto .= (strlen(pg_fetch_result($res_posto,0,numero)) > 0) ? ", ".pg_fetch_result($res_posto,0,numero) : "";
			$endereco_posto .= (strlen(pg_fetch_result($res_posto,0,cidade)) > 0) ? ", ".pg_fetch_result($res_posto,0,cidade) : "";
			$endereco_posto .= (strlen(pg_fetch_result($res_posto,0,estado)) > 0) ? ", ".pg_fetch_result($res_posto,0,estado) : "";

			$endereco_consumidor = "";
			$endereco_consumidor .= (strlen(pg_fetch_result($res_posto,0,consumidor_endereco)) > 0) ? pg_fetch_result($res_posto,0,consumidor_endereco) : "";
			$endereco_consumidor .= (strlen(pg_fetch_result($res_posto,0,consumidor_numero)) > 0) ? ", ".pg_fetch_result($res_posto,0,consumidor_numero) : "";
			$endereco_consumidor .= (strlen(pg_fetch_result($res_posto,0,consumidor_cidade)) > 0) ? ", ".pg_fetch_result($res_posto,0,consumidor_cidade) : "";
			$endereco_consumidor .= (strlen(pg_fetch_result($res_posto,0,consumidor_estado)) > 0) ? ", ".pg_fetch_result($res_posto,0,consumidor_estado) : "";

			$cidadeConsumidor = (strlen(pg_fetch_result($res_posto,0,consumidor_cidade)) > 0) ? pg_fetch_result($res_posto,0,consumidor_cidade) : "";
			$estadoConsumidor = (strlen(pg_fetch_result($res_posto,0,consumidor_estado)) > 0) ? pg_fetch_result($res_posto,0,consumidor_estado) : "";

			if(strlen($distancia_km)==0) $distancia_km = 0;

		}

		echo "<BR>";
		echo "<table width='600' border='1' align='center'  cellpadding='1' cellspacing='3 class='border'>";
			echo "<tr>";
			echo "<td nowrap colspan='100%' class='menu_top'><B><font size='2' face='Geneva, Arial, Helvetica, san-serif'>OUTRAS DESPESAS</font></b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td nowrap class='menu_top' rowspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data da visita</font></td>";
			echo "<td nowrap class='menu_top' rowspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>KM</font></td>";
			if($tipo_os == 13){
				echo "<td nowrap class='menu_top' rowspan='2'>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Qtd. Produto<br>Atendido</font></td>";
			}
			echo "<td nowrap class='menu_top' colspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Despesas Adicionais</font></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td nowrap class='menu_top'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Valor</font></td>";
			echo "<td nowrap class='menu_top'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Justificativa</font></td>";
			echo "</tr>";
			$sql  = "SELECT tbl_os_visita.os_visita ,
					to_char(tbl_os_visita.data,'DD/MM/YYYY')               AS data             ,
					to_char(tbl_os_visita.hora_chegada_cliente, 'HH24:MI') AS hora_chegada_cliente ,
					to_char(tbl_os_visita.hora_saida_cliente, 'HH24:MI')   AS hora_saida_cliente   ,
					tbl_os_visita.km_chegada_cliente                                               ,
					tbl_os_visita.justificativa_valor_adicional                                    ,
					tbl_os_visita.valor_adicional                                                  ,
					tbl_os_visita.qtde_produto_atendido
				FROM    tbl_os_visita
				WHERE   $sql_aux_os      = $aux_os
				ORDER BY tbl_os_visita.os_visita;";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			for ($y=0;$qtde_visita>$y;$y++){
				$os_visita            = trim(@pg_fetch_result($res,$y,os_visita));
				$visita_data          = trim(@pg_fetch_result($res,$y,data));
				$hr_inicio            = trim(@pg_fetch_result($res,$y,hora_chegada_cliente));
				$hr_fim               = trim(@pg_fetch_result($res,$y,hora_saida_cliente));
				$visita_km            = trim(@pg_fetch_result($res,$y,km_chegada_cliente));
				$qtde_produto_atendido= trim(@pg_fetch_result($res,$y,qtde_produto_atendido));
				$justificativa_adicionais = trim(@pg_fetch_result($res,$y,justificativa_valor_adicional));
				$valores_adicionais       = trim(@pg_fetch_result($res,$y,valor_adicional));
				$qtde_produto_atendido= trim(@pg_fetch_result($res,$y,qtde_produto_atendido));

				if(!empty($msg_erro)) {
					$os_visita               = $_POST['os_visita_'.$y];
					$visita_data             = $_POST['visita_data_'.$y];
					$hr_inicio               = $_POST['visita_hr_inicio_'.$y];
					$hr_fim                  = $_POST['visita_hr_fim_'.$y];
					$visita_km               = $_POST['visita_km_'.$y];
					$qtde_produto_atendido   = $_POST['qtde_produto_atendido_'.$y];
					$valores_adicionais      = $_POST['valores_adicionais_'.$y];
					$justificativa_adicionais= $_POST['justificativa_adicionais_'.$y];
				}

				if(strlen($visita_km_erro) > 0) {
					if(strlen($_POST['visita_km_'.$y]) > 0) {
						echo $visita_km = $visita_km_erro;
					}
				}

				echo "<tr>";
				echo "<td nowrap align='center' width='200'>";
				echo "<INPUT TYPE='text' NAME='visita_data_$y' value='$visita_data' size='12' maxlength='10' class='frm' onKeyUp=\"formata_data_visita(this.value, 'frm_os', $y)\";>";
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>dd/mm/aaaa</font>";
				echo "</td>";

				echo "<td nowrap align='center'>";
				echo "<input type='hidden' name='km_conferencia_$y' id='km_conferencia_$y'>";
				echo "<INPUT TYPE='text' NAME='visita_km_$y' id='visita_km_$y' onfocus=\"initialize('','visita_km_$y','km_conferencia_$y')\" value='$visita_km' size='4' maxlength='4' class='frm'>";
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Km</font>";
				echo "</td>";
				if($tipo_os ==13){
					$sql = "
						SELECT  count(os_revenda_item) as qtde_itens_geo
						FROM    tbl_os_revenda_item
						WHERE  $sql_aux_os      = $aux_os ";
					$res_count = pg_query ($con,$sql) ;

					$qtde_itens_geo = pg_fetch_result($res_count,0,qtde_itens_geo);

					if($y == 0 and strlen($qtde_produto_atendido) ==0){
						$qtde_produto_atendido = $qtde_itens_geo;
					}

					echo "<td nowrap align='center'>";
					echo "<INPUT TYPE='text' NAME='qtde_produto_atendido_$y' value='$qtde_produto_atendido' size='4' maxlength='4' class='frm'>";
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'></font>";
					echo "</td>";
				}

				echo "<td nowrap align='center'>";
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>R$ </font>";
				echo "<INPUT TYPE='text' onKeyDown=\"FormataValor(this,11, event)\"; NAME='valores_adicionais_$y' value='$valores_adicionais' size='5' maxlength='5' class='frm'>";
				echo "</td>";

				echo "<td nowrap align='center'>";
				echo "<INPUT TYPE='text' NAME='justificativa_adicionais_$y' value='$justificativa_adicionais' size='10' maxlength='50' class='frm'>";
				echo "<input type='hidden' name='os_visita_$y' value='$os_visita'>";
				echo "</td>";
				echo "</tr>";
			}
		echo "</table> <BR>";

	}
//HD 406478 - MLG - API-Key para o domínio telecontrol.NET.br
//HD 678667 - MLG - Adicionar mais uma Key. Alterado para um include que gerencia as chaves.
// include '../gMapsKeys.inc';
?>

<style type="text/css">
	#GoogleMapsContainer{
		z-index: 888;
		position: relative;
		width: 700px;
		height: 400px;
		border: 2px solid #000;
		margin: 0 auto;
	}
	#DirectionPanel{
		width: 250px;
		height: 400px;
		float: right;
        background-color: #fff;
        overflow: auto;
	}
	#GoogleMaps{
		width: 450px;
		height: 400px;
		float: left;
		background-color: #fff;
	}
	#fechamapa:hover{
		cursor: pointer;
	}
</style>

<!-- CSS e JavaScript Google Maps -->
<link href="https://developers.google.com/maps/documentation/javascript/examples/default.css" rel="stylesheet">
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"></script>

<div id="GoogleMapsContainer">
	<div style="margin-top: 5px; margin-left: 5px; position: absolute; z-index: 889;" id="fechamapa" onclick="fechaMapa();"><img src="../admin/imagens/close_black_opaque.png" /></div>
	<div id="GoogleMaps"></div>
	<div id="DirectionPanel"></div>
</div>

<script language="javascript">

/* Inicio Google Maps */

function siglaEstado(sigla){

	switch(sigla){
		case "AC" : sigla = "Acre"; break;
		case "AL" : sigla = "Alagoas"; break;
		case "AP" : sigla = "Amapá"; break;
		case "AM" : sigla = "Amazonas"; break;
		case "BA" : sigla = "Bahia"; break;
		case "CE" : sigla = "Ceará"; break;
		case "DF" : sigla = "Distrito Federal"; break;
		case "ES" : sigla = "Espírito Santo"; break;
		case "GO" : sigla = "Goiás"; break;
		case "MA" : sigla = "Maranhão"; break;
		case "MT" : sigla = "Mato Grosso"; break;
		case "MS" : sigla = "Mato Grosso do Sul"; break;
		case "MG" : sigla = "Minas Gerais"; break;
		case "PA" : sigla = "Pará"; break;
		case "PB" : sigla = "Paraíba"; break;
		case "PR" : sigla = "Paraná"; break;
		case "PE" : sigla = "Pernambuco"; break;
		case "PI" : sigla = "Piauí"; break;
		case "RJ" : sigla = "Rio de Janeiro"; break;
		case "RN" : sigla = "Rio Grande do Norte"; break;
		case "RS" : sigla = "Rio Grande do Sul"; break;
		case "RO" : sigla = "Rondônia"; break;
		case "RR" : sigla = "Roraima"; break;
		case "SC" : sigla = "Santa Catarina"; break;
		case "SP" : sigla = "São Paulo"; break;
		case "SE" : sigla = "Sergipe"; break;
		case "TO" : sigla = "Tocantins"; break;
	}

	return sigla;

}

function retiraAcentos(palavra){

    var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
    var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
    var newPalavra = "";

    for(i = 0; i < palavra.length; i++) {
    	if (com_acento.search(palavra.substr(i,1)) >= 0) {
      		newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i,1)),1);
      	}
      	else{
       		newPalavra += palavra.substr(i,1);
    	}
    }

    return newPalavra.toUpperCase();
}

$('#GoogleMapsContainer').css({'display' : 'none'});

var directionsDisplay;
var directionsService = new google.maps.DirectionsService();
var map;

function initialize() {
	directionsDisplay = new google.maps.DirectionsRenderer();
    var mapOptions = {
      zoom: 7,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      center: new google.maps.LatLng(41.850033, -87.6500523)
    };
    map = new google.maps.Map(document.getElementById('GoogleMaps'),
        mapOptions);

    directionsDisplay.setMap(map);

    directionsDisplay.setPanel(document.getElementById('DirectionPanel'));

    var control = document.getElementById('control');
    map.controls[google.maps.ControlPosition.TOP].push(control);
}

/* calCRoute */
var qtdVezesRota = 0;

var alterarDistanciaVezes = 0;

var googleNaoEncontrou = 0;

function calcRoute(){

    var textIdaVolta = "";

    $('#ida_volta').html("");

    /* validacoes */
    if($('#consumidor_cidade').val() == ""){
        alert("Por favor insira a cidade do Consumidor!");
        $(this).focus();
        return;
    }

    if($('#consumidor_estado').val() == ""){
        alert("Por favor insira o estado do Consumidor!");
        $(this).focus();
        return;
    }

    var cidadeConsumidor = "";
    var estadoConsumidor = "";

    $('#distancia_km').val('');
    $('#div_end_posto').html('');
    $('#div_mapa_msg').html('');

    var posto = "";
    var consumidor = "";

    google.maps.event.addDomListener(window, 'load', initialize);

    if($('#contato_endereco').val() != "" || $('#contato_cidade').val() != "" || $('#contato_estado').val() == ""){
        if($('#contato_endereco').val() != ""){ posto += " "+document.getElementById("contato_endereco").value; }
        if($('#contato_numero').val() != ""){ posto += " "+document.getElementById("contato_numero").value; }
        // if($('#contato_bairro').val() != ""){ posto += ", "+document.getElementById("contato_bairro").value; }
        posto += ", "+document.getElementById("contato_cidade").value;
        posto += ", "+document.getElementById("contato_estado").value;
        posto += ", Brasil";
    }else if($('#contato_cep').val() != ""){
        posto = $('#contato_cep').val();
    }else{
        alert("Dados insuficientes do Posto para realizar Rota e Calculo da Distância, por favor verificar se há endereço, cidade, estado e CEP.");
        return;
    }

    if($('#consumidor_endereco').val() != "" || $('#consumidor_cidade').val() != "" || $('#consumidor_estado').val() != ""){
        if($('#consumidor_endereco').val() != "") { consumidor += " "+document.getElementById("consumidor_endereco").value; }
        if($('#consumidor_numero').val() != "") { consumidor += " "+document.getElementById("consumidor_numero").value; }
        // if($('#consumidor_bairro').val() != "") { consumidor += ", "+document.getElementById("consumidor_bairro").value; }
        consumidor += ", "+document.getElementById("consumidor_cidade").value;
        consumidor += ", "+document.getElementById("consumidor_estado").value;
        consumidor += ", Brasil";

        /* Cidade e Estado Consumidor */
        cidadeConsumidor = $('#consumidor_cidade').val();
        estadoConsumidor = $('#consumidor_estado').val();

    }else if($('#consumidor_cep').val() != ""){
        consumidor = $('#consumidor_cep').val();
    }else{
        alert("Dados insuficientes do Consumidor para realizar Rota e Calculo da Distância, por favor verificar se há endereço, cidade, estado e CEP.");
        return;
    }

    if(posto == ""){
        alert('Endereço do Posto não localizado! Por favor verifique se os dados es corretos!');
        return;
    }

    if(consumidor == ""){
        if($('#consumidor_cep').val() == ""){
            alert('Por favor insira o Consumidor');
            $('#consumidor_nome').focus();
            return;
        }else{
            consumidor = $('#consumidor_cep').val();
        }
    }

    /* Quilometragem da Rota */

    var service = new google.maps.DistanceMatrixService();
    service.getDistanceMatrix(
    {
        origins: [posto],
        destinations: [consumidor],
        travelMode: google.maps.TravelMode.DRIVING,
        unitSystem: google.maps.UnitSystem.METRIC,
        avoidHighways: false,
        avoidTolls: false
    }, callback);

    function callback(response, status) {
        if (status != google.maps.DistanceMatrixStatus.OK) {
          alert('Error was: ' + status);
        } else {

            var results = response.rows[0].elements;
            var destino = response.destinationAddresses;
            destino = destino.toString();

            if(results[0].status == "OK"){

                var cidadesIguais = 0;
                var estadosIguais = 0;

                /* Reescreve a Sigla do estado para o nome completo */
                estadoConsumidor = siglaEstado(estadoConsumidor);

                var comp1   = new Array();
                var comp2   = new Array();
                var seq     = 0;

                destino = destino.replace(/\d{5}-\d{3},/g,'');
                destino = destino.replace(/-/g,',');
                comp1 = destino.split(",");
                var c1 = comp1.length;

                var cidadeComp = "";
                var estadoComp = "";

                if(comp1[c1-3] !== undefined){ cidadeComp = comp1[c1-3]; }
                if(comp1[c1-2] !== undefined){ estadoComp = comp1[c1-2]; }

                if(cidadeComp.length > 0){
                    cidadeComp = retiraAcentos(cidadeComp);
                    cidadeConsumidor = retiraAcentos(cidadeConsumidor);
                }

                if(estadoComp.length > 0){
                    estadoComp = siglaEstado(estadoComp);
                    estadoComp = retiraAcentos(estadoComp);
                    estadoConsumidor = retiraAcentos(estadoConsumidor);
                }

                /* Compara se a cidade e o estado estão corretos */
                if(estadoComp.length > 0){
                    if(estadoComp.trim() == estadoConsumidor.trim() || cidadeComp.trim() == cidadeConsumidor.trim()){
                        cidadesIguais++;
                        estadosIguais++;
                    }
                }

                if(cidadesIguais == 0 && estadosIguais == 0){
                    if(cidadeComp.trim().length > 0){
                        if(cidadeComp.trim() == cidadeConsumidor.trim()){
                            cidadesIguais++;
                        }
                    }
                    if(estadoComp.trim() == estadoConsumidor.trim()){
                        estadosIguais++;
                    }
                }

                var kmtotal1 = results[0].distance.value;

                // getRouteInverse(kmtotal1, consumidor, posto);

                /* Realiza a rota inversa */
                var service2 = new google.maps.DistanceMatrixService();

                function callback2(response2, status2){

                    if (status2 != google.maps.DistanceMatrixStatus.OK) {
                        var kmtotal = 0;
                        kmtotal = kmtotal1.toFixed(2);
                        kmtotal = kmtotal.toString();
                        kmtotal = kmtotal.replace(".", ",");

                    }else{

                        var results = response2.rows[0].elements;

                        if(results[0].status == "OK"){

                            var kmtotal = 0;
                            var kmtotal2 = results[0].distance.value;
                            kmtotal = (kmtotal1 + kmtotal2) / 1000;

                            kmtotal = kmtotal.toFixed(2);
                            kmtotal = kmtotal.toString();
                            kmtotal = kmtotal.replace(".", ",");

                            kmtotal1 = kmtotal1 / 1000;
                            kmtotal1 = kmtotal1.toFixed(2);
                            kmtotal1 = kmtotal1.toString();
                            kmtotal1 = kmtotal1.replace(".", ",");

                            kmtotal2 = kmtotal2 / 1000;
                            kmtotal2 = kmtotal2.toFixed(2);
                            kmtotal2 = kmtotal2.toString();
                            kmtotal2 = kmtotal2.replace(".", ",");

                            <?php
                                if(strlen($qtde_km) > 0 || strlen($msg_erro) > 0){

                                    if(strlen($msg_erro) > 0){
                                        $distancia_km = $qtd_km;
                                        echo "alterarDistanciaVezes++;";
                                    };

                                    ?>

                                    if(qtdVezesRota == 0){

                                        if(alterarDistanciaVezes > 0){
                                            textIdaVolta = '<strong>Ida:</strong> '+kmtotal1+" &nbsp; <strong>Volta:</strong> "+kmtotal2;
                                            $('#ida_volta').html(textIdaVolta);
                                        }else{
                                            alterarDistanciaVezes++;
                                        }

                                        $('#distancia_km').val("");
                                        $('#distancia_km_conferencia').val("");
                                        $('#distancia_km').val("<?=$qtde_km;?>");
                                        $('#distancia_km_conferencia').val("<?=$qtde_km;?>");

                                        <?php
                                        if(strlen($msg_erro) > 0){
                                            ?>
                                                if(erroCadastro > 0){
                                                    $('#distancia_km').val(kmtotal);
                                                    $('#distancia_km_conferencia').val(kmtotal);
                                                }

                                            <?php
                                        }
                                        ?>
                                        var comp = compara();

                                        if(cidadesIguais != 0 && estadosIguais != 0){

                                            if(comp == 2){
                                                $('#div_mapa_msg').html('Distância calculada <a href= "javascript: vermapa();">Ver mapa</a>');
                                                $('#div_end_posto').html("<strong>Endereço do Posto:</strong> "+posto);
                                            }else{
                                                $('#div_mapa_msg').html('A distância percorrida pelo técnico estará sujeito a auditoria');
                                            }
                                        }else{

                                            if(googleNaoEncontrou != 0){
                                                $('#distancia_km').val("0");
                                                $('#distancia_km_conferencia').val("0");
                                                $('#div_mapa_msg').html("");
                                                $('#div_end_posto').html("");
                                                $('#ida_volta').html("");
                                                if($('#tipo_atendimento').val() == 71){
                                                    alert('Endereço não localizado pelo Google Maps, por favor insira manualmente.');
                                                }
                                            }

                                            googleNaoEncontrou++;

                                        }

                                        qtdVezesRota++;
                                    }else{
                                        $('#distancia_km').val("");
                                        $('#distancia_km').attr('value', kmtotal);

                                        if(alterarDistanciaVezes > 0){
                                            textIdaVolta = '<strong>Ida:</strong> '+kmtotal1+" &nbsp; <strong>Volta:</strong> "+kmtotal2;
                                            $('#ida_volta').html(textIdaVolta);
                                        }else{
                                            alterarDistanciaVezes++;
                                        }

                                        var comp = compara();

                                        if(cidadesIguais != 0 && estadosIguais != 0){
                                            if(comp == 2){
                                                $('#div_mapa_msg').html('Distância calculada <a href= "javascript: vermapa();">Ver mapa</a>');
                                                $('#div_end_posto').html("<strong>Endereço do Posto:</strong> "+posto);
                                            }else{
                                                $('#div_mapa_msg').html('A distância percorrida pelo técnico estará sujeito a auditoria');
                                            }
                                        }else{

                                            if(googleNaoEncontrou != 0){
                                                $('#distancia_km').val("0");
                                                $('#distancia_km_conferencia').val("0");
                                                $('#div_mapa_msg').html("");
                                                $('#div_end_posto').html("");
                                                $('#ida_volta').html("");
                                                if($('#tipo_atendimento').val() == 71){
                                                    alert('Endereço não localizado pelo Google Maps, por favor insira manualmente.');
                                                }
                                            }

                                            googleNaoEncontrou++;

                                        }
                                    }
                                    <?php
                                }else{
                                    ?>
                                    if(cidadesIguais != 0 && estadosIguais != 0){

                                        textIdaVolta = '<strong>Ida:</strong> '+kmtotal1+" &nbsp; <strong>Volta:</strong> "+kmtotal2;
                                        $('#ida_volta').html(textIdaVolta);

                                        $('#distancia_km').val(kmtotal);
                                        $('#distancia_km_conferencia').val(kmtotal);
                                        $('#div_mapa_msg').html('Distância calculada <a href= "javascript: vermapa();">Ver mapa</a>');
                                        $('#div_end_posto').html("<strong>Endereço do Posto:</strong> "+posto);

                                    }else{

                                        $('#ida_volta').html("");

                                        $('#distancia_km').val("0");
                                        $('#distancia_km_conferencia').val("0");
                                        $('#div_mapa_msg').html('');
                                        $('#div_end_posto').html("");

                                        if($('#tipo_atendimento').val() == 71){
                                            alert('Endereço não localizado pelo Google Maps, por favor insira manualmente.');
                                        }
                                    }
                            <?php
                                }
                            ?>

                        }
                    }
                }

                service2.getDistanceMatrix(
                {
                    origins: [consumidor],
                    destinations: [posto],
                    travelMode: google.maps.TravelMode.DRIVING,
                    unitSystem: google.maps.UnitSystem.METRIC,
                    avoidHighways: false,
                    avoidTolls: false
                }, callback2);

            }else{
                $('#ida_volta').html("");
                $('#distancia_km').val('');
                $('#div_mapa_msg').html('');
                $('#div_end_posto').html("<strong>Endereço do Posto:</strong> <em style='color: #ff0000;'>Não localizado</em>");
                if(!$('#posto_nome').val() == ""){
                    alert('Endereço não localizado! Por favor verifique se os dados(Endereço, Cidade, Estado e CEP) do Consumidor e Posto estão corretos.');
                }
            }
        }
    }

}

function vermapa(){

	var posto = "";
	var consumidor = "";

	$("#GoogleMapsContainer").css({'display' : 'block'});

    directionsDisplay = new google.maps.DirectionsRenderer();
    var mapOptions = {
      zoom: 7,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      center: new google.maps.LatLng(41.850033, -87.6500523)
    };
    var map = new google.maps.Map(document.getElementById('GoogleMaps'),
        mapOptions);

    directionsDisplay.setMap(map);
    directionsDisplay.setPanel(document.getElementById('DirectionPanel'));

	posto = '<?php echo $endereco_posto; ?>';
	consumidor = '<?php echo $endereco_consumidor; ?>';

	var request = {
          origin: consumidor,
          destination: posto,
          travelMode: google.maps.DirectionsTravelMode.DRIVING
        };
        directionsService.route(request, function(response, status) {
          	if (status == google.maps.DirectionsStatus.OK) {
          		$('#DirectionPanel').html('');
            	directionsDisplay.setDirections(response);
        	}else{
        		$("#GoogleMapsContainer").css({'display' : 'none'});
        		alert("Não foi possível realizar a Rota com esse endereço, cidade, cidade e/ou CEP, por favor verifique se está tudo correto, ou está faltando informações.");
        	}
    });

}

function fechaMapa(){

	$("#GoogleMapsContainer").css({'display' : 'none'});

}

window.onload = calcRoute();

/* Fim Google Maps */

</script>

<?php

	if($compressor=='t' or ($tipo_os == 13 and ($tipo_atendimento == 65 or $tipo_atendimento == 69))) {
		echo '
			<div id="div_mapa" style="margin: 0 auto; width:590px; background:#efefef;border:#999999 1px solid;font-size:10px;padding:5px;" >
				<b>Distância percorrida pelo técnico para execução do serviço(ida e volta):<br>
				<br />
				<span id="ida_volta"></span> <br />
				<input type="hidden" id="ponto1" value="$endereco_posto" >
				<input type="hidden" id="distancia_km_maps"  value="" >
				<input type="hidden" name="distancia_km_conferencia" id="distancia_km_conferencia" value="$distancia_km_conferencia">
				Distância: <input type="text" name="distancia_km" id="distancia_km" value="" size="5"> KM
				<input  type="button" onclick="calcRoute();" value="Calcular Distância" size="5" >
				<div id="div_mapa_msg" style="color:#FF0000"></div>
				<br>
				<div id="div_end_posto" style="color:#000000">
				<B>Endereço do posto:</b>
				<u>$endereco_posto</u>
				</div>
			</div>
			<br />
		';
	}

?>


<?
	echo "<table class='border' width='620' align='center' border='1' cellpadding='1' cellspacing='3'>";
	echo "<tr>";
		echo "<td class='menu_top'>Relatório do Técnico</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<TD class='table_line'><TEXTAREA NAME='tecnico' ROWS='5' COLS='85'>$tecnico </TEXTAREA></TD>";
	echo "</tr>";
	echo "</table>";
	echo "<br/>";
}
?>
<table width='650' align='center' border='0' cellspacing='0' cellpadding='5' class=''>
<? if ($login_fabrica == 19) { ?>
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Valores Adicionais:</FONT>
		<br>
		<FONT SIZE="1">R$ </FONT>
		<INPUT TYPE="text" NAME="valores_adicionais" value="<? echo $valores_adicionais ?>" size="10" maxlength="10" class="frm">
		<br><br>
	</td>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Justificativa dos Valores Adicionais:</FONT>
		<br>
		<INPUT TYPE="text" NAME="justificativa_adicionais" value="<? echo $justificativa_adicionais ?>" size="30" maxlength="100" class="frm">
		<br><br>
	</td>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Quilometragem:</FONT>
		<br>
		<INPUT TYPE="text" NAME="qtde_km" value="<? echo $qtde_km ?>" size="5" maxlength="10" class="frm">
		<br><br>
	</td>
</tr>
<? } ?>

<?
$nosso_ip = include ('../nosso_ip.php');
if(($ip==$nosso_ip) or ($ip=="201.42.45.29") OR ($ip=="201.76.86.97") OR ($ip=="201.42.147.251") OR ($ip=='201.43.245.148') OR ($login_admin == 665)){
if($login_fabrica==15){ ?>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<table width='40%' align='center' border='0' cellspacing='0' cellpadding='3' bgcolor="#B63434">
			<tr>
			<td valign="middle" align="RIGHT">
			<FONT SIZE="1" color='#FFFFFF'>***Data Fechamento:   </FONT>
			</td>
				<td valign="middle" align="LEFT">
			<INPUT TYPE="text" NAME="data_fechamento" value="<? echo $data_fechamento; ?>" size="12" maxlength="10" class="frm">
			<BR><font size='1' color='#FFFFFF'>dd/mm/aaaa</font>
			</td>
			</tr>
			</table>
	</td>
</tr>
<? } ?>
<? } ?>

<?if($login_fabrica==19){ ?>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<table width='40%' align='center' border='0' cellspacing='0' cellpadding='3' bgcolor="#B63434">
			<tr>
			<td valign="middle" align="RIGHT">
			<FONT SIZE="1" color='#FFFFFF'>***Data Fechamento:   </FONT>
			</td>
				<td valign="middle" align="LEFT">
			<INPUT TYPE="text" NAME="data_fechamento" value="<? echo $data_fechamento; ?>" size="12" maxlength="10" class="frm">
			<BR><font size='1' color='#FFFFFF'>dd/mm/aaaa</font>
			</td>
			</tr>
			</table>
			<font size='1'>***Ao inserir a data a OS será fechada</font>
	</td>
</tr>
<? } ?>

<!-- ********************************************************************************************* -->
<? if($login_fabrica==30 && $Posto_SAC !== 'SAC'){//HD 27561 ?>
<table width='650' align='center' border='0' cellspacing='0' cellpadding='5'>
	<TR>
		<TD style='font-size: 12px;'>Fogão</TD>
		<TD style='font-size: 12px;'>Marca</TD>
		<TD style='font-size: 12px;'>Refrigerador</TD>
		<TD style='font-size: 12px;'>Marca</TD>
		<TD style='font-size: 12px;'>Bebedouro</TD>
		<TD style='font-size: 12px;'>Marca</TD>
	</TR>
	<TR>
		<TD>
			<SELECT NAME='fogao'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='2Q' <? if($fogao == '2Q') echo 'SELECTED'; ?>>2Q</OPTION>
				<OPTION VALUE='4Q' <? if($fogao == '4Q') echo 'SELECTED'; ?>>4Q</OPTION>
				<OPTION VALUE='5Q' <? if($fogao == '5Q') echo 'SELECTED'; ?>>5Q</OPTION>
				<OPTION VALUE='6Q' <? if($fogao == '6Q') echo 'SELECTED'; ?>>6Q</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='marca_fogao'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='ATLAS' <? if($marca_fogao == 'ATLAS') echo 'SELECTED'; ?>>ATLAS</OPTION>
				<OPTION VALUE='BOSCH' <? if($marca_fogao == 'BOSCH') echo 'SELECTED'; ?>>BOSCH</OPTION>
				<OPTION VALUE='BRASTEMP' <? if($marca_fogao == 'BRASTEMP') echo 'SELECTED'; ?>>BRASTEMP</OPTION>
				<OPTION VALUE='CONTINENTAL' <? if($marca_fogao == 'CONTINENTAL') echo 'SELECTED'; ?>>CONTINENTAL</OPTION>
				<OPTION VALUE='CONSUL' <? if($marca_fogao == 'CONSUL') echo 'SELECTED'; ?>>CONSUL</OPTION>
				<OPTION VALUE='DAKO' <? if($marca_fogao == 'DAKO') echo 'SELECTED'; ?>>DAKO</OPTION>
				<OPTION VALUE='ELETROLUX' <? if($marca_fogao == 'ELETROLUX') echo 'SELECTED'; ?>>ELETROLUX</OPTION>
				<OPTION VALUE='ESMALTEC' <? if($marca_fogao == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
				<OPTION VALUE='OUTROS' <? if($marca_fogao == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='refrigerador'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='1 Porta' <? if($refrigerador == '1 PORTA') echo 'SELECTED'; ?>>1 Porta</OPTION>
				<OPTION VALUE='2 Portas' <? if($refrigerador == '2 PORTAS') echo 'SELECTED'; ?>>2 Portas</OPTION>
				<OPTION VALUE='Frost Free' <? if($refrigerador == 'FROST FREE') echo 'SELECTED'; ?>>Frost Free</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='marca_refrigerador'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='BRASTEMP' <? if($marca_refrigerador == 'BRASTEMP') echo 'SELECTED'; ?>>BRASTEMP</OPTION>
				<OPTION VALUE='CONSUL' <? if($marca_refrigerador == 'CONSUL') echo 'SELECTED'; ?>>CONSUL</OPTION>
				<OPTION VALUE='CONTINENTAL' <? if($marca_refrigerador == 'CONTINENTAL') echo 'SELECTED'; ?>>CONTINENTAL</OPTION>
				<OPTION VALUE='DAKO' <? if($marca_refrigerador == 'DAKO') echo 'SELECTED'; ?>>DAKO</OPTION>
				<OPTION VALUE='ELETROLUX' <? if($marca_refrigerador == 'ELETROLUX') echo 'SELECTED'; ?>>ELETROLUX</OPTION>
				<OPTION VALUE='ESMALTEC' <? if($marca_refrigerador == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
				<OPTION VALUE='OUTROS' <? if($marca_refrigerador == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='bebedouro'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='Coluna' <? if($bebedouro == 'COLUNA') echo 'SELECTED'; ?>>Coluna</OPTION>
				<OPTION VALUE='Mesa' <? if($bebedouro == 'MESA') echo 'SELECTED'; ?>>Mesa</OPTION>
				<OPTION VALUE='Suporte' <? if($bebedouro == 'SUPORTE') echo 'SELECTED'; ?>>Suporte</OPTION>
				<OPTION VALUE='Filtro' <? if($bebedouro == 'FILTRO') echo 'SELECTED'; ?>>Filtro</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='marca_bebedouro'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='ESMALTEC' <? if($marca_bebedouro == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
				<OPTION VALUE='OUTROS' <? if($marca_bebedouro == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
			</SELECT>
		</TD>
	</TR>
	<TR>
		<TD style='font-size: 12px;'>Microondas</TD>
		<TD style='font-size: 12px;'>Marca</TD>
		<TD style='font-size: 12px;'>Lavadoura</TD>
		<TD style='font-size: 12px;'>Marca</TD>
	</TR>
	<TR>
		<TD>
			<SELECT NAME='microondas'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='Pequeno' <? if($microondas == 'PEQUENO') echo 'SELECTED'; ?>>Pequeno</OPTION>
				<OPTION VALUE='Medio'  <? if($microondas == 'MEDIO') echo 'SELECTED'; ?>>Médio</OPTION>
				<OPTION VALUE='Grande'  <? if($microondas == 'GRANDE') echo 'SELECTED'; ?>>Grande</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='marca_microondas'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='BOSCH' <? if($marca_microondas == 'BOSCH') echo 'SELECTED'; ?>>BOSCH</OPTION>
				<OPTION VALUE='BRASTEMP' <? if($marca_microondas == 'BRASTEMP') echo 'SELECTED'; ?>>BRASTEMP</OPTION>
				<OPTION VALUE='CCE' <? if($marca_microondas == 'CCE') echo 'SELECTED'; ?>>CCE</OPTION>
				<OPTION VALUE='CONSUL' <? if($marca_microondas == 'CONSUL') echo 'SELECTED'; ?>>CONSUL</OPTION>
				<OPTION VALUE='CONTINENTAL' <? if($marca_microondas == 'CONTINENTAL') echo 'SELECTED'; ?>>CONTINENTAL</OPTION>
				<OPTION VALUE='ELETROLUX' <? if($marca_microondas == 'ELETROLUX') echo 'SELECTED'; ?>>ELETROLUX</OPTION>
				<OPTION VALUE='ESMALTEC' <? if($marca_microondas == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
				<OPTION VALUE='PANASONIC' <? if($marca_microondas == 'PANASONIC') echo 'SELECTED'; ?>>PANASONIC</OPTION>
				<OPTION VALUE='OUTROS' <? if($marca_microondas == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='lavadoura'>
				<OPTION VALUE='' ></OPTION>
				<OPTION VALUE='Sim' <? if($lavadoura == 'SIM') echo 'SELECTED'; ?>>Sim</OPTION>
				<OPTION VALUE='Nao' <? if($lavadoura == 'NAO') echo 'SELECTED'; ?>>Não</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='marca_lavadoura'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='BRASTEMP' <? if($marca_lavadoura == 'BRASTEMP') echo 'SELECTED'; ?>>BRASTEMP</OPTION>
				<OPTION VALUE='CONSUL' <? if($marca_lavadoura == 'CONSUL') echo 'SELECTED'; ?>>CONSUL</OPTION>
				<OPTION VALUE='CONTINENTAL' <? if($marca_lavadoura == 'CONTINENTAL') echo 'SELECTED'; ?>>CONTINENTAL</OPTION>
				<OPTION VALUE='DAKO' <? if($marca_lavadoura == 'DAKO') echo 'SELECTED'; ?>>DAKO</OPTION>
				<OPTION VALUE='ELETROLUX' <? if($marca_lavadoura == 'ELETROLUX') echo 'SELECTED'; ?>>ELETROLUX</OPTION>
				<OPTION VALUE='ESMALTEC' <? if($marca_lavadoura == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
				<OPTION VALUE='OUTROS' <? if($marca_lavadoura == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
			</SELECT>
		</TD>
	</TR>
</TABLE>
<!-- ********************************************************************************************* -->
<?}

if ($anexaNotaFiscal && !$fabricaFileUploadOS) { ?>
	<tr>
		<td>
<?	if ($consumidor_revenda == "R") {
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
		}
	}else{
		if ($anexaNotaFiscal and temNF($os, 'bool')) {
			echo '<div id="DIVanexos">' . temNF($os, 'linkEx') .  '</div>';
		}

	}
} else {
	if ($anexaNotaFiscal and temNF($os, 'bool')) {
		echo '<div id="DIVanexos">' . temNF($os, 'linkEx') .  '</div>';
	}
}

if($login_fabrica == 140){

/* Laudo Técnico - Lavor */

if($desc_tipo_atendimento == "Entrega t&eacute;cnica"){

?>

<table width='700' align='center' border='0' cellspacing='0' cellpadding='5' class="formulario">

    <tr>
        <td class='titulo_tabela' valign="middle" colspan="2">
            <label style="margin:auto;font:14px Arial">Laudo Técnico:</label>
        </td>
    </tr>

    <tr>
        <td align="center" width="50%">

            <?php

                $sqlv = "SELECT
                            tbl_comunicado.comunicado,
                            extensao
                        FROM tbl_comunicado
                        LEFT JOIN tbl_comunicado_produto USING(comunicado)
                        WHERE
                            fabrica = $login_fabrica
                            AND tipo = 'Laudo Tecnico'";

                $resv = pg_query($con,$sqlv) ;

                if (pg_num_rows($resv) > 0) {
                    $vcomunicado             = pg_fetch_result($resv, 0, 'comunicado');
                    $vextensao               = pg_fetch_result($resv, 0, 'extensao');

                    if ($S3_online) {
                        if($s3ve->temAnexos($vcomunicado))
                            $vista_explodida_produto = $s3ve->url;
                        else
                            $vista_explodida_produto = file_exists("comunicados/$vcomunicado.$vextensao") ? "comunicados/$vcomunicado.$vextensao":false;
                    }else{
                        $vista_explodida_produto = file_exists("comunicados/$vcomunicado.$vextensao") ? "comunicados/$vcomunicado.$vextensao":false;
                    }

                    if ($vista_explodida_produto !== false) {
                        echo "
                            <a href='$vista_explodida_produto' target='_blank' style='vertical-align:top;font-size:9px;height:32px;line-height:16px;display:inline-block;width:100px'>
                                <img src='../imagens/botoes/vista_explodida_icone.png' style='float:left' height='32' alt='Vista Explodida' title='Vista Explodida do Produto' />
                                <span >Laudo<br />Técnico</span>
                            </a>";
                    }

                }

            ?>

        </td>

        <td>

            <strong>Upload de Laudo Técnico</strong> <br />
            <input type="file" name="laudo_tecnico" id="laudo_tecnico" />

        </td>

    </tr>
</table>

<br />

<?php
	}
}

echo $include_imgZoom; ?>
<script type='text/javascript' src='../js/anexaNF_excluiAnexo.js'></script>
<?
if ($temNFs < LIMITE_ANEXOS)
	echo "</td></tr>\n<tr><td>";if($login_fabrica <> 137) echo $inputNotaFiscal;
?>		</td>
	</tr>
<?} ?>
<!-- ********************************************************************************************* -->

<tr>
	<td height="27" valign="middle" align="center" colspan="3" >
		<table class='formulario'>
			<tr class='titulo_tabela'><td> <?php echo (in_array($login_fabrica, array(101))) ? "Descrição detalhada do problema:" : "Observações:"; ?> </td></tr>
			<tr>
				<td align="center">
					<?php if ($login_fabrica == 30) {
								$sql_obs_os = "select tbl_os.obs || '\nMotivo: ' || tbl_os.observacao AS obs from tbl_os where os=$os";
								$res_obs_os = pg_query($con,$sql_obs_os);
								$obs_os = pg_fetch_result($res_obs_os, 0, 0);
								echo nl2br($obs_os);
								echo "<br><br>";
							}
							if($obs == 'null' OR $obs == "NULL"){
								$obs = "";
							}
                            if ($login_fabrica == 131) {
                                echo "<TEXTAREA NAME='obs' cols='64' rows='3' class='frm'>" . $obs . "</TEXTAREA>";
                            } else {
                                echo "<INPUT TYPE='text' NAME='obs' value='"; echo ($login_fabrica <> 30) ?  $obs : ''; echo "' size='85' maxlength='255' class='frm'>";
                            }
				?>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>

<? }else{ ?>

	<input type="hidden" name="troca_faturada" value="<?=$troca_faturada?>" />
	<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td align="left" nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Motivo Troca</font>
				<br />
				<select name="motivo_troca" size="1" class="frm">
					<option value=""></option>
					<?
					$sql = "SELECT tbl_defeito_constatado.*
							FROM   tbl_defeito_constatado
							WHERE  tbl_defeito_constatado.fabrica = $login_fabrica";
					if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
					$sql .= " ORDER BY tbl_defeito_constatado.descricao";

					$res = pg_query($con,$sql) ;
					for ($i = 0; $i < pg_num_rows($res); $i++) {
						echo "<option ";
						if ($motivo_troca == pg_fetch_result ($res,$i,defeito_constatado) ) echo " selected ";
						echo " value='" . pg_fetch_result ($res,$i,defeito_constatado) . "'>" ;
						echo pg_fetch_result ($res,$i,descricao) ." - ". pg_fetch_result ($res,$i,codigo) ;
						echo "</option>\n";
					}?>
				</select>
			</td>
		</tr>
	</table><?php

}?>

<?
	if( in_array($login_fabrica, array(3,11,126,137,172)) ){

	    if((strlen($msg_erro) > 0 ) && ($erro_upload != "true")){ ?>
	        <table width="650" align="center"><?

	        foreach ($arrLinks as $key => $values) {?>
	            <tr>
	                <td align="center"  width="50%"><label style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif">Anexo: </label>

	                    <?//se para a $key há um link, abre tag img para imagem
	                    if(strlen($values["link"]) > 0 ){
	                        $pathinfo = pathinfo($values["link"]);
	                        list($ext,$params) = explode("?", $pathinfo["extension"]);
	                        if($ext == "pdf"){ ?>
	                            <a href="<?=$values["link"]?>">
	                                <img id="<?=$key?>" name="<?=$key?>" alt="Baixar Anexo" src="imagens/adobe.JPG"/>
	                                <img id="<?=$key?>" name="<?=$key?>" alt="Baixar Anexo" src="<?=$values["link"]?>"></img>
	                            </a>
	                        <? }else{ ?>
	                            <img id="<?=$key?>" name="<?=$key?>" alt="Baixar Anexo" src="<?=$values["link"]?>"></img>
	                        <? } ?>
	                        <input type="hidden" value="<?=$values['name']?>" name="tmp_<?=$key?>">

	                   <? }else{?>
	                        <input type="file" class="frm" name="<?=$key?>" id="<?=$key?>"/> <?
	                   }?>
	                </td>
	           </tr><?
	        }?>
	        <table> <?
	    }else{ ?>
	        <table width="650" align="center">
	            <tr>
	                <td  align="center" width="50%">
	                <?
	                    $amazonTC->getObjectList("anexo_os_item_{$login_fabrica}_{$os}_img_os_item_1",false,"","");

	                    $link = '';
	                    $file = $amazonTC->files[0];

	                    if (!empty($file)) {
	                        $link  = $amazonTC->getLink(basename($file));
	                        $thumb = $amazonTC->getLink("thumb_".basename($file));
	                    }
	                    $pathinfo = pathinfo($link);
	                    list($ext,$params) = explode("?", $pathinfo["extension"]);

	                    if(strlen($link) > 0){ ?>
	                        <label style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif"> Anexo: </label>
	                        <a href="<?=$link?>">
	                            <? if($ext == "pdf"){ ?>
	                                <img id="img_os_item_1" name="img_os_item_1" alt="Baixar Anexo" src="imagens/adobe.JPG"/>
	                            <? }else{ ?>
	                                <img id="img_os_item_1" name="img_os_item_1" alt="Baixar Anexo" src="<?=$thumb?>"/>
	                            <? } ?>
	                        </a>
	                   <? }else{ 
                            if($login_fabrica != 3) { ?>
	                        <label style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif"> Inserir Anexo: </label>
	                        <input type="file" class="frm" name="img_os_item_1" id="img_os_item_1"/>
	                  <?   }
                          } ?>
	                </td>
	            </tr>
	            <tr>
	                <td  align="center" width="50%">
	                <?
	                    $amazonTC->getObjectList("anexo_os_item_{$login_fabrica}_{$os}_img_os_item_2",false,"","");

	                    $link = '';
	                    $file = $amazonTC->files[0];

	                    if (!empty($file)) {
	                        $link  = $amazonTC->getLink(basename($file));
	                        $thumb = $amazonTC->getLink("thumb_".basename($file));
	                    }
	                    $pathinfo = pathinfo($link);
	                    list($ext,$params) = explode("?", $pathinfo["extension"]);
	                    if(strlen($link) > 0){ ?>
	                        <label style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif"> Anexo: </label>
	                        <a href="<?=$link?>">
	                         <? if($ext == "pdf"){ ?>
	                            <img id="img_os_item_2" name="img_os_item_2" alt="Baixar Anexo" src="imagens/adobe.JPG"/>
	                        <? }else{ ?>
	                            <img id="img_os_item_2" name="img_os_item_2" alt="Baixar Anexo" src="<?=$thumb?>"/>
	                        <? } ?>

	                        </a>
	                   <? }else{
				if($login_fabrica != 126 AND $login_fabrica != 3){
			   ?>
	                        <label style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif"> Inserir Anexo: </label>
	                        <input type="file" class="frm" name="img_os_item_2" id="img_os_item_2"/>
	                  <?    }
			     }
			  ?>

	                </td>
	            </tr>
	        </table>
	<?  }
	}?>

<br />

<input type="hidden" name="btn_acao" value="">
<?php
	if(in_array($login_fabrica, array(35,120,201,139))){
        $onclick = "formataValorAdicional(); ";
    }

    if($login_fabrica == 24){
    	$function = "valida_itens()";
    }else{
    	$function = "document.frm_os.submit()";
    }
?>
<center>
<?php if($login_fabrica == 134){?>
    <input type="hidden" name="posto_id" id="posto_id" value="<?=$login_posto?>">
<?php } ?>

<input type='button' name="btn_gravar" value='Gravar'onclick="javascript: <?=$onclick?> if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; <?=$function?> } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }" ALT="Gravar itens da Ordem de Serviço" border='0' style="cursor:pointer;"></center>
<?
if($login_fabrica == 72){
    echo "<input type='hidden' id='defeito_constatado_descricao_anterior' name='descricao_defeito_constatado_anterior' >";
    echo "<input type='hidden' id='defeito_constatado_descricao' name='descricao_defeito_constatado' >";

    echo "<input type='hidden' id='solucao_descricao_anterior' name='descricao_solucao_anterior' >";
    echo "<input type='hidden' id='solucao_descricao' name='descricao_solucao' >";
}
?>

</form>

    <br>
    <a target="_blank" rel='shadowbox' href="relatorio_log_alteracao_new.php?parametro=tbl_os_item&id=<?=$os?>"><font size="-2">Log de alterações</font></a>


<input type="hidden" name="fabrica_hidden" class="fabrica_hidden" value="<?php echo $login_fabrica; ?>" />

<br />

<script type="text/javascript">
    window.onload = function() {
        $(".lista_basica").show();
    }
</script>

<?php if ($login_fabrica == 30) { ?>
        <script> 
            $(document).ready(function(){
                $("#defeito_constatado").trigger("change");
            });
        </script>
<?php } ?>


<?  include "rodape.php";?>
