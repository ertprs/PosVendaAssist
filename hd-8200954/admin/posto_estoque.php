<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia, call_center";
$min_maximo_null = array(163,175,178);

include "autentica_admin.php";
include "funcoes.php";

function lanca_movimentacao_estoque_posto($posto, $peca, $qtde, $movimento, $nota_fiscal = null, $data = null, $observacao = null){

    global $login_fabrica, $con, $login_admin;

    if(!empty($data)){
        list($dia, $mes, $ano) = explode("/", $data);
        $data = $ano."-".$mes."-".$dia;
    }

    /**
     * Validações
     */
    if (strlen($qtde) == 0  || $qtde < 0) {
        $retorno = array("erro" => utf8_encode("Quantidade não informada"));
    } else if (empty($movimento)) {
        $retorno = array("erro" => utf8_encode("Tipo de movimento não informado"));
    } else {
        if (empty($posto)) {
            $retorno = array("erro" => utf8_encode("Posto Autorizado não informado"));
        } else if (empty($peca)) {
            $retorno = array("erro" => utf8_encode("Peça não informada"));
        } else if (strlen($qtde) == 0 || $qtde < 0) {
            $retorno = array("erro" => utf8_encode("Quantidade não informada"));
        } else if (empty($movimento)) {
            $retorno = array("erro" => utf8_encode("Tipo de movimento não informado"));
        } else {
	    $sql_posto = "
		SELECT
		    posto,
		    posto_interno,
		    tecnico_proprio
                FROM tbl_posto_fabrica
                JOIN tbl_tipo_posto USING(tipo_posto,fabrica)
        	WHERE fabrica = {$login_fabrica}
                AND posto = {$posto};
	    ";
            $res_posto = pg_query($con, $sql_posto);

            $sql_peca = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca}";
            $res_peca = pg_query($con, $sql_peca);

            if (!pg_num_rows($res_posto)) {
                $retorno = array("erro" => utf8_encode("Posto Autorizado não encontrado"));
            } else if (!pg_num_rows($res_peca)) {
                $retorno = array("erro" => utf8_encode("Peça não encontrada"));
            }

            if (!isset($retorno["erro"]) && $login_fabrica == 158) {
                $tecnico_proprio = pg_fetch_result($res_posto, 0, "tecnico_proprio");
                $posto_interno = pg_fetch_result($res_posto, 0, "posto_interno");

                if ($posto_interno == 'f' && empty($nota_fiscal)) {
                    $retorno = array("erro" => utf8_encode("Nota fiscal Obrigatória para o lançamento de saída"));
                } else if (!empty($nota_fiscal)) {
                    $sql_nota_fiscal = "
                    SELECT  nf,
                            SUM(coalesce(qtde_entrada,0)) AS qtde_entrada,
                            SUM(coalesce(qtde_usada,0)) AS qtde_usada,
                            SUM(coalesce(qtde_usada_estoque,0)) AS qtde_usada_estoque
                        FROM tbl_estoque_posto_movimento
                        WHERE fabrica = {$login_fabrica}
                            AND posto = {$posto}
                            AND peca = {$peca}
                            AND nf = '{$nota_fiscal}'
                            AND qtde_entrada IS NOT NULL
                            AND qtde_saida IS NULL
                        GROUP BY nf; ";

                    $res_nota_fiscal = pg_query($con, $sql_nota_fiscal);

                    if (!pg_num_rows($res_nota_fiscal)) {
                        $retorno = array("erro" => utf8_encode("Nota Fiscal já lançada para essa peça"));
                    } else {
                        $xqtde_entrada = pg_fetch_result($res_nota_fiscal, 0, qtde_entrada);
                        $xqtde_usada = pg_fetch_result($res_nota_fiscal, 0, qtde_usada);
                        $xqtde_usada_estoque = pg_fetch_result($res_nota_fiscal, 0, qtde_usada_estoque);

                        if ($xqtde_entrada < ($qtde + $xqtde_usada) /*|| $xqtde_entrada < ($qtde + $xqtde_usada_estoque)*/) {
                            $retorno = array("erro" => utf8_encode("A quantidade de saída referente a essa nota fiscal não pode ser maior que o saldo"));
                        }
                    }
                }
            }
        }
    }
    /**
     * Fim validação
     */

    if (!isset($retorno["erro"])) {
        $sql = "SELECT qtde FROM tbl_estoque_posto WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND peca = {$peca}";
        $res = pg_query($con, $sql);


        pg_query($con, "BEGIN");

        if (pg_num_rows($res) > 0) {
            $estoque = pg_fetch_result($res, 0, "qtde");
        } else {
            $sql = "INSERT INTO tbl_estoque_posto
                    (fabrica, posto, peca, qtde, estoque_minimo, tipo)
                    VALUES
                    ({$login_fabrica}, {$posto}, {$peca}, 0, 0, 'estoque')";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                $retorno = array("erro" => utf8_encode("Erro ao lançar movimentação"));
                exit(json_encode($retorno));
            }

            $estoque = 0;
        }

        if ($movimento == "saida") {
            $estoque -= $qtde;
        } else {
            $estoque += $qtde;
        }

        if ($estoque < 0) {
            $retorno = array("erro" => utf8_encode("Quantidade lançada maior que a disponível"));
        } else {

            $campo_movimento = ($movimento == "entrada") ? "qtde_entrada" : "qtde_saida";

            $data = (!empty($data)) ? "'{$data}'" : "null";
            $nota_fiscal = (!empty($nota_fiscal)) ? "'{$nota_fiscal}'" : "null";

            if(in_array($login_fabrica, array(50))){
                $campo_tipo = ", tipo";
                $valor_tipo = ", 'pulmao'";
            }

            $observacao = utf8_decode($observacao);

            $sql = "INSERT INTO tbl_estoque_posto_movimento
                (fabrica, posto, peca, {$campo_movimento}, admin, nf, data, obs {$campo_tipo})
                VALUES
                ({$login_fabrica}, {$posto}, {$peca}, {$qtde}, {$login_admin}, {$nota_fiscal}, {$data}, '{$observacao}' {$valor_tipo});";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                $retorno = array("erro" => utf8_encode("Erro ao lançar movimentação #1"));
            } else {
                $sql = "UPDATE tbl_estoque_posto
                        SET qtde = {$estoque}
                        WHERE fabrica = {$login_fabrica}
                        AND posto = {$posto}
                        AND peca = {$peca}";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    $retorno = array("erro" => utf8_encode("Erro ao lançar movimentação #2"));
                } else {
                    if ($movimento == "saida" && $login_fabrica == 158 && $posto_interno != "t") {
                        $qtde_usada_total = $qtde;

                        while ($qtde_usada_total > 0) {
                            $xsql = "
                                SELECT qtde_entrada, COALESCE(qtde_usada, 0) AS qtde_usada, nf, data_digitacao, data, faturamento, pedido, os_item, obs
                                FROM tbl_estoque_posto_movimento
                                WHERE fabrica = {$login_fabrica}
                                AND posto = {$posto}
                                AND peca = {$peca}
                                AND nf = {$nota_fiscal}
                                AND qtde_entrada IS NOT NULL
                                AND COALESCE(qtde_usada, 0) < qtde_entrada
                                AND qtde_saida IS NULL
                                ORDER BY data_digitacao ASC
                                LIMIT 1
                            ";
                            $xres = pg_query($con, $xsql);

                            if (pg_num_rows($xres) > 0) {
                                $xres = pg_fetch_assoc($xres);

                                $saldo = $xres["qtde_entrada"] - $xres["qtde_usada"];

                                if ($qtde_usada_total > $saldo) {
                                    $qtde_update = $saldo;
                                } else {
                                    $qtde_update = $qtde_usada_total;
                                }

                                $whereObs = (empty($xres["obs"])) ? "AND obs IS NULL" : "AND obs = '{$xres['obs']}'";
                                $wherePedido = (empty($xres["pedido"])) ? "AND pedido IS NULL": "AND pedido = {$xres['pedido']}";
                                $whereFaturamento = (empty($xres["faturamento"])) ? "AND faturamento IS NULL" : "AND faturamento = {$xres['faturamento']}";
                                $whereOsItem = (empty($xres["os_item"])) ? "AND os_item IS NULL" : "AND os_item = {$xres['os_item']}";

                                $up_entrada = "
                                    UPDATE tbl_estoque_posto_movimento SET
                                        qtde_usada = COALESCE(qtde_usada, 0) + {$qtde_update}
                                    WHERE fabrica = {$login_fabrica}
                                    AND posto = {$posto}
                                    AND peca = {$peca}
                                    AND nf = {$nota_fiscal}
                                    AND qtde_entrada IS NOT NULL
                                    AND qtde_saida IS NULL
                                    AND data_digitacao = '{$xres['data_digitacao']}'
                                    {$whereObs}
                                    {$wherePedido}
                                    {$whereFaturamento}
                                    {$whereOsItem}
                                ";
                                $res_up = pg_query($con, $up_entrada);

                                if (strlen(pg_last_error()) > 0) {
                                    $retorno = array("erro" => utf8_encode("Erro ao lançar movimentação #3"));
                                    break;
                                }

                                $qtde_usada_total -= $qtde_update;
                            } else {
                                $retorno = array("erro" => utf8_encode("Erro ao lançar movimentação #4"));
                                break;
                            }
                        }

                        if ($tecnico_proprio != "t") {
                            $qtde_usada_total = $qtde;

                            while ($qtde_usada_total > 0) {
                                $xsql = "
                                    SELECT qtde_entrada, COALESCE(qtde_usada_estoque, 0) AS qtde_usada_estoque, nf, data_digitacao, data, faturamento, pedido, os_item, obs
                                    FROM tbl_estoque_posto_movimento
                                    WHERE fabrica = {$login_fabrica}
                                    AND posto = {$posto}
                                    AND peca = {$peca}
                                    AND nf = {$nota_fiscal}
                                    AND qtde_entrada IS NOT NULL
                                    AND COALESCE(qtde_usada_estoque, 0) < qtde_entrada
                                    AND qtde_saida IS NULL
                                    ORDER BY data_digitacao ASC
                                    LIMIT 1;
                                ";
                                $xres = pg_query($con, $xsql);

                                if (pg_num_rows($xres) > 0) {
                                    $xres = pg_fetch_assoc($xres);

                                    $saldo = $xres["qtde_entrada"] - $xres["qtde_usada_estoque"];

                                    if ($qtde_usada_total > $saldo) {
                                        $qtde_update = $saldo;
                                    } else {
                                        $qtde_update = $qtde_usada_total;
                                    }

                                    $whereObs = (empty($xres["obs"])) ? "AND obs IS NULL" : "AND obs = '{$xres['obs']}'";
                                    $wherePedido = (empty($xres["pedido"])) ? "AND pedido IS NULL": "AND pedido = {$xres['pedido']}";
                                    $whereFaturamento = (empty($xres["faturamento"])) ? "AND faturamento IS NULL" : "AND faturamento = {$xres['faturamento']}";
                                    $whereOsItem = (empty($xres["os_item"])) ? "AND os_item IS NULL" : "AND os_item = {$xres['os_item']}";

                                    $up_entrada = "
                                        UPDATE tbl_estoque_posto_movimento SET
                                            qtde_usada_estoque = COALESCE(qtde_usada_estoque, 0) + {$qtde_update}
                                        WHERE fabrica = {$login_fabrica}
                                        AND posto = {$posto}
                                        AND peca = {$peca}
                                        AND nf = {$nota_fiscal}
                                        AND qtde_entrada IS NOT NULL
                                        AND qtde_saida IS NULL
                                        AND data_digitacao = '{$xres['data_digitacao']}'
                                        {$whereObs}
                                        {$wherePedido}
                                        {$whereFaturamento}
                                        {$whereOsItem}
                                    ";
                                    $res_up = pg_query($con, $up_entrada);

                                    if (strlen(pg_last_error()) > 0) {
                                        $retorno = array("erro" => utf8_encode("Erro ao lançar movimentação #4"));
                                        break;
                                    }

                                    $qtde_usada_total -= $qtde_update;
                                } else {
                                    $retorno = array("erro" => utf8_encode("Erro ao lançar movimentação #5"));
                                    break;
                                }
                            }
                        }

                        if (!$retorno["erro"]) {
                            $retorno = array("ok" => true, "estoque" => $estoque);
                        }
                    } else {
                        $retorno = array("ok" => true, "estoque" => $estoque);
                    }

                }
            }

            if ($retorno["ok"]) {
                pg_query($con, "COMMIT");
            } else {
                pg_query($con, "ROLLBACK");
            }
        }
    }
    return $retorno;
}

if(isset($_POST["saida_estoque_csv"])){

    $arquivo = $_FILES["upload_saida_estoque"];

    if($arquivo["size"] == 0){
        $msg_erro["msg"][]    = "Insira o arquivo CSV";
        $msg_erro["campos"][] = "upload_saida_estoque";
    }else{
        $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));
        if (!in_array($ext, array("csv","txt"))) {
            $msg_erro["msg"][] = "O formato do arquivo deve ser CSV ou TXT";
            $msg_erro["campos"][] = "upload_saida_estoque";
        } else {
            $arquivo = file_get_contents($arquivo["tmp_name"]);

            $arquivo = explode("\n", $arquivo);
            $arquivo = array_filter($arquivo);

            pg_query($con,"BEGIN TRANSACTION");

            foreach ($arquivo as $key => $value) {
                
                list($cnpj, $ref_peca, $qtde, $nota_fiscal, $data, $observacao) = explode(";", $value);
                $cnpj           = trim($cnpj);
                $ref_peca       = trim($ref_peca);
                $qtde           = trim($qtde);
                $nota_fiscal    = (int)$nota_fiscal;
                $data           = trim($data);
                $observacao     = trim($observacao);

                $sql_cnpj = "SELECT
                            tbl_posto.posto,
                            tbl_posto_fabrica.tipo_posto
                        FROM tbl_posto_fabrica
                        JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
                        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                        AND tbl_posto.cnpj = '{$cnpj}'";
                $res_cnpj = pg_query($con, $sql_cnpj);

                if (pg_num_rows($res_cnpj) > 0) {

                    $posto      = pg_fetch_result($res_cnpj, 0, posto);
                    $tipo_posto = pg_fetch_result($res_cnpj, 0, tipo_posto);                   

                    $sqlPostoIntCSV = "SELECT posto_interno, tipo_posto FROM tbl_tipo_posto WHERE fabrica = {$login_fabrica} AND tipo_posto = {$tipo_posto};";
                    $resPostoIntCSV = pg_query($con, $sqlPostoIntCSV);

                    $tipo_posto     = pg_fetch_result($resPostoIntCSV, 0, tipo_posto);
                    $posto_interno  = pg_fetch_result($resPostoIntCSV, 0, posto_interno);

                    if(strlen(trim($nota_fiscal))==0 AND $posto_interno == 'f'){
                        $msg_erro["msg"][] = "Por favor informar a nota fiscal da peça $ref_peca do posto $cnpj. ";
                        $msg_erro["campos"][] = "upload_saida_estoque";
                    }
                    
                    $sql_saldo = "SELECT tbl_estoque_posto.qtde, tbl_peca.peca 
                                FROM tbl_estoque_posto 
                                INNER JOIN tbl_peca ON tbl_peca.peca = tbl_estoque_posto.peca AND tbl_peca.fabrica = $login_fabrica
                                WHERE 
                                tbl_estoque_posto.fabrica = $login_fabrica 
                                AND tbl_peca.referencia = '$ref_peca'
                                AND tbl_estoque_posto.posto = $posto ;
                                ";
                    $res_saldo  = pg_query($con, $sql_saldo);
                    $qtde_saldo = pg_fetch_result($res_saldo, 0, 'qtde');
                    $idPeca     = pg_fetch_result($res_saldo, 0, 'peca');

                    if($posto_interno == 'f'){
                        $sql_nf = "SELECT nf from tbl_estoque_posto_movimento where fabrica = $login_fabrica and  nf::int = $nota_fiscal  and qtde_entrada is not null and nf is not null and nf <> 'ETMN001' and posto = $posto and peca = $idPeca"; 
                        $res_nf = pg_query($con, $sql_nf);

                        if(pg_num_rows($res_nf) == 0){
                            $msg_erro["msg"][] = "Nota fiscal não encontrada.";
                            $msg_erro["campos"][] = "upload_saida_estoque";
                        }
                    }

                    if($posto_interno == 't' AND $nota_fiscal != 0){
                        $sql_nf = "SELECT nf from tbl_estoque_posto_movimento where fabrica = $login_fabrica and  nf::int = $nota_fiscal  and qtde_entrada is not null and nf is not null and nf <> 'ETMN001' and posto = $posto and peca = $idPeca"; 
                        $res_nf = pg_query($con, $sql_nf);

                        if(pg_num_rows($res_nf) == 0){
                            $msg_erro["msg"][] = "Nota fiscal não encontrada.";
                            $msg_erro["campos"][] = "upload_saida_estoque";
                        }
                    }

                    if($tipo_posto == 570){
                        if($qtde_saldo < $qtde){
                            $msg_erro["msg"][] = " A peça $ref_peca de NF $nota_fiscal está com saldo insuficiente.";
                            $msg_erro["campos"][] = "upload_saida_estoque";
                        }
                    }

                    if(count($msg_erro["msg"]) == 0){

                        $sql_estoque_posto_movimento = "INSERT INTO tbl_estoque_posto_movimento (posto, peca, fabrica, qtde_saida, obs, nf) VALUES ($posto, $idPeca, $login_fabrica, $qtde, '$observacao', '$nota_fiscal')";

                        $res_estoque_posto_movimento = pg_query($con, $sql_estoque_posto_movimento);

                        if(strlen(pg_last_error($con))>0){
                            $msg_erro["msg"][] = "Erro ao gravar estoque movimento";
                        }

                        $sql_atualiza_estoque_posto = "UPDATE tbl_estoque_posto SET qtde = qtde - $qtde  WHERE peca = $idPeca and posto = $posto AND fabrica = $login_fabrica ";
                        $res_atualiza_estoque_posto = pg_query($con, $sql_atualiza_estoque_posto);
                        if(strlen(pg_last_error($con))>0){
                            $msg_erro["msg"][] = "Erro ao gravar ao atualizar estoque do posto. ";
                        }
                    }                   
                }else{
                    $msg_erro["msg"][] = "Posto não encontrado.";
                }
            }
            
            if(count($msg_erro["msg"]) > 0){
                pg_query($con,"ROLLBACK");
            } else {
                pg_query($con,"COMMIT");
                $msg_ok = "ok";
            }
        }
    }
}

if(isset($_POST["arquivo_csv"])){

    $arquivo = $_FILES["upload"];

    if($arquivo["size"] == 0){
        $msg_erro["msg"][]    = "Insira o arquivo CSV";
        $msg_erro["campos"][] = "upload";

    }else{
        $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

        if (!in_array($ext, array("csv","txt"))) {
            $msg_erro["msg"][] = "O formato do arquivo deve ser CSV ou TXT";
            $msg_erro["campos"][] = "upload";
        } else {

            $arquivo = file_get_contents($arquivo["tmp_name"]);

            $arquivo = explode("\n", $arquivo);
            $arquivo = array_filter($arquivo);

            foreach ($arquivo as $key => $value) {

                if (in_array($login_fabrica, array(163,175))) {

                   list($cnpj, $ref_peca, $qtdeCarga) = explode(";", $value);

                    $cnpj = trim($cnpj);
                    $ref_peca = trim($ref_peca);
                    $qtdeCarga = trim($qtdeCarga);
                    $qtde_min = 0;
                    $qtde_max = 0;
                } else {

                    list($cnpj, $ref_peca, $qtde_min, $qtde_max) = explode(";", $value);

                    $cnpj = trim($cnpj);
                    if ($login_fabrica == 151){
                        $ref_peca = strtoupper(trim($ref_peca));
                    }else{
                        $ref_peca = trim($ref_peca);
                    }
                    $qtde_min = trim($qtde_min);
                    $qtde_max = trim($qtde_max);
                }

                $sql_cnpj = "SELECT
                            tbl_posto.posto,
                            tbl_posto_fabrica.tipo_posto
                        FROM tbl_posto_fabrica
                        JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
                        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                        AND tbl_posto.cnpj = '{$cnpj}'";
                $res_cnpj = pg_query($con, $sql_cnpj);

                if (pg_num_rows($res_cnpj) > 0) {

                    $posto = pg_fetch_result($res_cnpj, 0, posto);

                    if (in_array($login_fabrica, array(158))) {
                        $tipo_posto = pg_fetch_result($res_cnpj, 0, tipo_posto);

                        $sqlPostoIntCSV = "SELECT posto_interno FROM tbl_tipo_posto WHERE fabrica = {$login_fabrica} AND tipo_posto = {$tipo_posto};";
                        $resPostoIntCSV = pg_query($con, $sqlPostoIntCSV);

                        $posto_interno = pg_fetch_result($resPostoIntCSV, 0, posto_interno);

                        if ($posto_interno == "t") {
                            $qtde_min = 0;
                        }
                    }

                    if ($qtde_min == $qtde_max && (!in_array($login_fabrica, array(158,163,175)))) {

                        $msg_erro["msg"][] = "A Qtde Mínima não pode ser igual a Qtde Máxima";

                    } else if($qtde_min > $qtde_max) {

                        $msg_erro["msg"][] = "A Qtde Mínima não pode ser maior a Qtde Máxima";

                    } else {

                        $sql_peca = "SELECT peca, referencia FROM tbl_peca WHERE referencia = '{$ref_peca}' AND fabrica = {$login_fabrica}";
                        $res_peca = pg_query($con, $sql_peca);

                        if(pg_num_rows($res_peca) > 0){

                            $peca       = pg_fetch_result($res_peca, 0, "peca");
                            $referencia = pg_fetch_result($res_peca, 0, "referencia");

                            if(in_array($login_fabrica, array(50))){
                                $campo_tipo = ", tipo";
                                $valor_tipo = ", 'pulmao'";
                            }

                            $sql_estoque_posto = "
                                SELECT  posto
                                FROM    tbl_estoque_posto
                                WHERE   fabrica = {$login_fabrica}
                                AND     posto   = {$posto}
                                AND     peca    = {$peca}";
                            $res_estoque_posto = pg_query($con, $sql_estoque_posto);

                            if(pg_num_rows($res_estoque_posto) > 0){

                                $sql_update = "UPDATE
                                            tbl_estoque_posto
                                        SET
                                            estoque_minimo = {$qtde_min},
                                            estoque_maximo = {$qtde_max}
                                        WHERE fabrica = {$login_fabrica}
                                        AND posto = {$posto}
                                        AND peca = {$peca}";
                                $res_update = pg_query($con, $sql_update);

                            } else {

                                $sql_insert = "INSERT INTO tbl_estoque_posto (fabrica, posto, peca, estoque_minimo, estoque_maximo, qtde $campo_tipo)
                                        VALUES ({$login_fabrica}, {$posto}, {$peca}, {$qtde_min}, {$qtde_max}, 0 $valor_tipo)";
                                $res_insert = pg_query($con, $sql_insert);

                            }

                        } else {

                            unset($estoque);
                            $msg_erro["msg"][] = "Peça $ref_peca não localizada";

                        }

                    }

                } else {

                    $msg_erro["msg"][] = "CPNJ $cnpj não localizado";

                }

                if ($login_fabrica == 163) {
                    $movimento = "entrada";
                    $nota_fiscal = "NAO INFORMADA";
                    $data = date("d/m/Y");
                    $observacao = "Carga via upload de arquivo";
                    $retorno = lanca_movimentacao_estoque_posto($posto, $peca, $qtdeCarga, $movimento, $nota_fiscal, $data, $observacao);
                }
            }
        }

        if(count($msg_erro["msg"]) == 0){

            if ($login_fabrica == 151 OR $login_fabrica == 74) { // Adicionada fabrica 74 no hd_chamado=2782600

                include "../classes/Posvenda/Pedido.php";

                $pedidoClass = new \Posvenda\Pedido($login_fabrica);

                $postos_controlam_estoque = $pedidoClass->getPostosControlamEstoque(true);

                if ($postos_controlam_estoque != false) {

                    foreach ($postos_controlam_estoque as $value) {
                        try {
			    $estoque = array();
			    $pecasStatus = array();
			    $novoEstoque = array();
			    $entrou = 0 ; 

                            $posto = $value["posto"];

                            $estoque = $pedidoClass->verificaEstoquePosto($posto);

                            if (count($estoque) > 0) {

                                foreach ($estoque as $key => $value) {
                                    $pecas[] = $value["peca"];
                                }
                            } else {
                                continue;
                            }

                            $status_pedido = $pedidoClass->pedidoBonificadoNaoFaturado($posto);
							$sem_pedido = false;
                            if (count($status_pedido) > 0) {
                                foreach ($status_pedido as $key => $result) {
                                    $pecasStatus[] = $result["peca"];
                                }

                                $pecasPedido = array_diff($pecas,$pecasStatus);
							} else {
								$sem_pedido = true;
                            }

                            if (count($pecasPedido) > 0 or $sem_pedido) {
                                foreach ($estoque as $key => $value) {
                                    if (array_search($value["peca"],$pecasPedido) or count($pecasPedido) == 0) {
                                        $entrou = 1;
                                        $novoEstoque[$key]["peca"]          = $value["peca"];
                                        $novoEstoque[$key]["referencia"]    = $value["referencia"];
                                        $novoEstoque[$key]["qtde_pedido"]   = $value["qtde_pedido"];
                                    }
                                }
                            }

                            if ($entrou == 1) {
                                $pedidoClass->pedidoBonificado($posto, $novoEstoque);
                            }
                        } catch (Exception $e) {
                            $msg_erro["msg"][] = $e->getMessage();

                            continue;
                        }
                    }
                }
            }

            $msg_ok = "ok";
        }
    }
}

if ($_POST["ajax_atualiza_movimentacao"] == true) {
    $posto = $_POST["posto"];
    $peca  = $_POST["peca"];
    if ($login_fabrica == 158) {
        $distinct = "DISTINCT";
        $colunaTipoPedido = ", tbl_tipo_pedido.pedido_em_garantia, tbl_pedido.pedido, tbl_estoque_posto_movimento.data_digitacao";
        $leftJoinFaturamento = "
            LEFT JOIN tbl_pedido ON tbl_pedido.pedido = tbl_estoque_posto_movimento.pedido AND tbl_pedido.fabrica = {$login_fabrica}
            LEFT JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$login_fabrica}
        ";
    }
    $sql = "
        SELECT {$distinct}
            CASE WHEN tbl_estoque_posto_movimento.qtde_entrada IS NOT NULL THEN 'Entrada' ELSE 'Saída' END AS movimento,
            COALESCE(tbl_estoque_posto_movimento.qtde_usada,0) AS qtde_usada,
            tbl_estoque_posto_movimento.qtde_entrada,
            tbl_estoque_posto_movimento.qtde_saida,
            tbl_estoque_posto_movimento.nf,
            tbl_estoque_posto_movimento.obs,
            TO_CHAR(tbl_estoque_posto_movimento.data_digitacao, 'DD/MM/YYYY HH:MM') AS data,
            TO_CHAR(tbl_estoque_posto_movimento.data, 'DD/MM/YYYY HH:MM') AS data_nf,
            tbl_admin.nome_completo AS admin
            {$colunaTipoPedido}
        FROM tbl_estoque_posto_movimento
        LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_estoque_posto_movimento.admin AND tbl_admin.fabrica = {$login_fabrica}
        {$leftJoinFaturamento}
        WHERE tbl_estoque_posto_movimento.fabrica = {$login_fabrica}
        AND tbl_estoque_posto_movimento.posto = {$posto}
        AND tbl_estoque_posto_movimento.peca = {$peca}
        ORDER BY tbl_estoque_posto_movimento.data_digitacao DESC;
    ";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        $rows = pg_num_rows($res);
        $movimentacoes = array();
        for ($i = 0; $i < $rows; $i++) {
            $movimento    = pg_fetch_result($res, $i, "movimento");
            $qtde_usada   = pg_fetch_result($res, $i, "qtde_usada");
            $qtde_entrada = pg_fetch_result($res, $i, "qtde_entrada");
            $qtde_saida   = pg_fetch_result($res, $i, "qtde_saida");
            $data         = pg_fetch_result($res, $i, "data");
            $admin        = pg_fetch_result($res, $i, "admin");
            $nf           = pg_fetch_result($res, $i, "nf");
            $data_nf      = pg_fetch_result($res, $i, "data_nf");
            $obs          = pg_fetch_result($res, $i, "obs");
            if ($login_fabrica == 158) {
                $pedido_em_garantia = pg_fetch_result($res, $i, "pedido_em_garantia");
                $pedido             = pg_fetch_result($res, $i, "pedido");
                $qtde_usada         = pg_fetch_result($res, $i, "qtde_usada");
                if (empty($pedido)) {
                    $tipo_pedido = ($tipo == "GARANTIA") ? "<span class='label label-success'>Garantia</span>" : "<span class='label label-important'>Fora de Garantia</span>";
                } else {
                    $tipo_pedido = ($pedido_em_garantia == "t") ? "<span class='label label-success'>Garantia</span>" : "<span class='label label-important'>Fora de Garantia</span>";
                }
                if ($movimento != "Entrada") {
                    unset($tipo_pedido);
                }
            }
            $class_movimento = ($movimento == "Entrada") ? "success" : "danger";
            $bg_movimento    = ($movimento == "Entrada") ? "#dff0d8" : "#f2dede";
            $qtde            = ($movimento == "Entrada") ? $qtde_entrada : $qtde_saida;
            $movimentacoes[$i] = array(
                "movimento" => utf8_encode($movimento),
                "data"      => $data,
                "qtde"      => $qtde,
                "qtde_usada" => $qtde_usada,
                "nf"        => $nf,
                "data_nf"   => $data_nf,
                "tipo"      => $tipo_pedido,
                "obs"       => utf8_encode($obs),
                "admin"     => utf8_encode($admin),
                "class"     => $class_movimento,
                "bg"        => utf8_encode($bg_movimento)
            );
        }
        $retorno = array(
            "movimentacoes" => $movimentacoes
        );
    } else {
        $retorno = array("erro" => true);
    }
    exit(json_encode($retorno));
}

if ($_POST["gerar_excel"]) {
    $data = date("d-m-Y-H:i");
    $cod_posto = $_POST['codigo_posto'];
    $peca_referencia = $_POST['peca_referencia'];

    if(!empty($peca_referencia)){
        $condPeca = " and tbl_peca.referencia = '$peca_referencia' ";
    }

    if(!empty($cod_posto)){
	$condPosto = " AND tbl_posto_fabrica.codigo_posto = '$cod_posto' ";
    }

    $arquivo_nome       = "relatorio-posto-estoque-peca-$data.xls";
    $path               = "xls/";
    $path_tmp           = "/tmp/";

    $arquivo_completo       = $path.$arquivo_nome;
    $arquivo_completo_tmp   = $path_tmp.$arquivo_nome;

    $fp = fopen($arquivo_completo_tmp,"w");

    $thead = "<table border='1'>";
    $thead .= "<thead>";
    $thead .= "<tr bgcolor='lightgrey'>";
    $thead .= "<th rowspan='2'>Codigo Posto</th>";
    $thead .= "<th rowspan='2'>Nome Posto</th>";
    $thead .= "<th rowspan='2'>Referencia Peca</th>";
    $thead .= "<th rowspan='2'>Descricao Peca</th>";
    $thead .= "<th rowspan='2'>Estoque Atual</th>";
    $thead .= "<th rowspan='2'>Estoque Minimo</th>";
    $thead .= "<th rowspan='2'>Estoque Maximo</th>";
    if($login_fabrica == 158 && !empty($cod_posto)){
        $thead .= "<th rowspan='2'>Saldo em O.S</th>";
    }
    $thead .= "<tr>";
    $thead .= "</thead>";

    fwrite($fp, $thead);

    $tbody .= "<tbody>";

    $sql = "SELECT tbl_peca.peca, tbl_posto_fabrica.posto, 
                tbl_peca.referencia AS peca_referencia,
                tbl_peca.descricao AS peca_descricao,
                tbl_estoque_posto.qtde,
                tbl_estoque_posto.estoque_minimo AS estoque_minimo,
                tbl_estoque_posto.estoque_maximo AS estoque_maximo,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto.nome
            FROM tbl_estoque_posto
            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_estoque_posto.peca AND tbl_peca.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_estoque_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            WHERE tbl_estoque_posto.fabrica = {$login_fabrica}  
	    $condPosto
            $condPeca ";
    $res = pg_query($con, $sql);

    for ($i = 0; $i < pg_num_rows($res); $i++) {
        $codigo_posto        = pg_fetch_result($res, $i, 'codigo_posto');
        $nome_posto          = pg_fetch_result($res, $i, 'nome');
        $referencia_peca     = pg_fetch_result($res, $i, 'peca_referencia');
        $descricao_peca      = pg_fetch_result($res, $i, 'peca_descricao');
        $estoque             = pg_fetch_result($res, $i, 'qtde');
        $estoque_minimo      = pg_fetch_result($res, $i, 'estoque_minimo');
        $estoque_maximo      = pg_fetch_result($res, $i, 'estoque_maximo');
        $peca                = pg_fetch_result($res, $i, 'peca');
        $posto               = pg_fetch_result($res, $i, 'posto');


	if($login_fabrica == 158 && !empty($cod_posto)){
		$sqlSaldoPeca = "SELECT sum(tbl_os_item.qtde) as saldopecaos FROM tbl_os 
				join tbl_os_produto on tbl_os_produto.os = tbl_os.os 
				join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
				join tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.fabrica = $login_fabrica 
				where tbl_os_item.peca = $peca 
				and tbl_os.fabrica = $login_fabrica 
				and tbl_os.finalizada is null 
				and tbl_os.data_fechamento is null 
				and tbl_os.excluida IS NOT TRUE
				and tbl_os.cancelada IS NOT TRUE
				and  tbl_servico_realizado.troca_de_peca is true
				and tbl_os.posto = ".$posto;

		$resSaldoPeca = pg_query($con, $sqlSaldoPeca);
		if(pg_last_error($resSaldoPeca)==0){
			$saldo_peca_os = pg_fetch_result($resSaldoPeca, 0, saldopecaos);
			$saldo_peca_os = (strlen(trim($saldo_peca_os)) == 0) ? 0 : $saldo_peca_os;
		}
	}

        $tbody .= "<tr>";
        $tbody .= "<td>".$codigo_posto."</td>";
        $tbody .= "<td>".$nome_posto."</td>";
        $tbody .= "<td>".$referencia_peca."</td>";
        $tbody .= "<td>".$descricao_peca."</td>";
        $tbody .= "<td>".$estoque."</td>";
        $tbody .= "<td>".$estoque_minimo."</td>";
        $tbody .= "<td>".$estoque_maximo."</td>";
        if($login_fabrica == 158 && !empty($cod_posto)){
            $tbody .= "<td>".$saldo_peca_os."</td>";
        }
        $tbody .= "</tr>";
    }
    $tbody .= "</tbody>";
    $tbody .= "</table>";

    fwrite($fp, $tbody);

    fclose($fp);

    if (file_exists($arquivo_completo_tmp)) {
        system("mv ".$arquivo_completo_tmp." ".$arquivo_completo."");
        echo $arquivo_completo;
    }

    exit;
}

if($_POST["ajax_estoque_minimo_maximo"] == true){

    $posto          = $_POST["posto"];
    $peca           = $_POST["peca"];
    $estoque_minimo = $_POST["estoque_minimo"];
    $estoque_maximo = $_POST["estoque_maximo"];

    if (empty($posto)) {
        $retorno = array("erro" => utf8_encode("Posto Autorizado não informado"));
    } else if (empty($peca)) {
        $retorno = array("erro" => utf8_encode("Peça não informada"));
    } else if ((strlen($estoque_minimo) == 0 || $estoque_minimo < 0) && !in_array($login_fabrica, array(158))) {
        $retorno = array("erro" => utf8_encode("Quantidade do Estoque Mínimo não informada"));
    } else if ((strlen($estoque_maximo) == 0 || $estoque_maximo < 0) && !in_array($login_fabrica, array(158))) {
        $retorno = array("erro" => utf8_encode("Quantidade do Estoque Máximo não informada"));
    }else{
        $sql = "SELECT * FROM tbl_estoque_posto
            WHERE fabrica = {$login_fabrica} AND posto = {$posto}
            AND peca = {$peca}";
        $resEstoque = pg_query($con,$sql);

        pg_query($con,"BEGIN TRANSACTION");
        $insert = false;

        if(pg_num_rows($resEstoque) == 0){
            $sql = "INSERT INTO tbl_estoque_posto (fabrica, posto, peca, estoque_minimo, estoque_maximo) VALUES
                ($login_fabrica, $posto, $peca, $estoque_minimo, $estoque_maximo)";
                $insert = true;
        }else{
            $sql = "UPDATE tbl_estoque_posto SET
                    estoque_minimo = {$estoque_minimo},
                    estoque_maximo = {$estoque_maximo}
                WHERE fabrica = {$login_fabrica} AND posto = {$posto}
                AND peca = {$peca}";
        }

        pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            pg_query($con,"ROLLBACK");
            $retorno = array("erro" => utf8_encode("Erro ao lançar estoque Mínimo / Máximo"));
        } else {
            pg_query($con,"COMMIT");
            $retorno = array("ok" => true, "insert" => $insert);
        }
    }

    exit(json_encode($retorno));

}

if ($_POST["ajax_lanca_movimentacao"] == true) {

	$posto     = $_POST["posto"];
	$peca      = $_POST["peca"];
	$qtde      = $_POST["qtde"];
	$movimento = $_POST["movimento"];
	$nota_fiscal 	= $_POST["nota_fiscal"];
	$data 			= $_POST["data"];
	$observacao 	= $_POST["observacao"];

    $retorno = lanca_movimentacao_estoque_posto($posto, $peca, $qtde, $movimento, $nota_fiscal, $data, $observacao);

	exit(json_encode($retorno));
}

if ($_POST["btn_acao"] == "submit") {
    $codigo_posto    = $_POST["codigo_posto"];
    $descricao_posto = $_POST["descricao_posto"];
    $peca_referencia = $_POST["peca_referencia"];
    $peca_descricao  = $_POST["peca_descricao"];

    if (strlen($codigo_posto) > 0 && strlen($descricao_posto) > 0) {
        $sql = "SELECT tbl_posto_fabrica.posto
            FROM tbl_posto
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            WHERE UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}')
            ";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][]    = "Posto Autorizado não encontrado";
            $msg_erro["campos"][] = "posto";
        } else {
            $posto = pg_fetch_result($res, 0, "posto");
        }
    } else {
        $msg_erro["msg"][]    = "Informe o Posto Autorizado";
        $msg_erro["campos"][] = "posto";
    }

            /* Gera arquivo CSV */        


    if (strlen($peca_referencia) > 0 && strlen($peca_descricao) > 0) {
        /*$sql = "SELECT tbl_peca.peca
            FROM tbl_peca
            WHERE fabrica = $login_fabrica AND (
                UPPER(tbl_peca.referencia) = UPPER('{$peca_referencia}')
                AND
                TO_ASCII(UPPER(tbl_peca.descricao), 'LATIN-9') = TO_ASCII(UPPER('{$peca_descricao}'), 'LATIN-9')
            )";*/
        $sql = "SELECT tbl_peca.peca
            FROM tbl_peca
            WHERE fabrica = $login_fabrica AND UPPER(tbl_peca.referencia) = UPPER('{$peca_referencia}')";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][]    = "Peça não encontrada";
            $msg_erro["campos"][] = "peca";
        } else {
             $peca = pg_fetch_result($res, 0, "peca");
        }
    } else {
        unset($peca_referencia, $peca_descricao);
    }
}

$layout_menu = "callcenter";
$title = "ESTOQUE DO POSTO AUTORIZADO";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "maskedinput",
    "shadowbox",
    "alphanumeric"
);

include "plugin_loader.php";

?>

<script>

$(function() {
    Shadowbox.init();
    $.autocompleteLoad(["posto", "peca"]);

    $("span[rel=lupa]").click(function() {
        $.lupa($(this));
    });

    $("#data").datepicker().mask("99/99/9999");

    $("#estoque_minimo").numeric();
    $("#estoque_maximo").numeric();

    $('.btn_estoque_minimo_maximo').click(function(){
        var posto          = $("input[name=posto]").val();
        var peca           = $("input[name=peca]").val();
        var estoque_minimo = $("input[name=estoque_minimo]").val();
        var estoque_maximo = $("input[name=estoque_maximo]").val();

        if(estoque_minimo == ""){
            alert("Por favor insira o Estoque Mínima");
            $("input[name=estoque_minimo]").focus();
            return;
        }

        if(estoque_maximo == ""){
            alert("Por favor insira o Estoque Máximo");
            $("input[name=estoque_maximo]").focus();
            return;
        }

        <? if ($login_fabrica != 158) { ?>
            if(parseInt(estoque_minimo) >= parseInt(estoque_maximo) && parseInt(estoque_maximo) > 0){
                alert("O Estoque Mínimo não pode ser maior ou igual ao Estoque Máximo");
                $("input[name=estoque_maximo]").focus();
                return;
            }
        <? } ?>

        $.ajax({
            url: "posto_estoque.php",
            type: "post",
            data: {
                ajax_estoque_minimo_maximo  : true,
                posto                       : posto,
                peca                        : peca,
                estoque_minimo              : estoque_minimo,
                estoque_maximo              : estoque_maximo
            },
            beforeSend: function() {
                $("button.btn_estoque_minimo_maximo").button("loading");
            }
        }).always(function(data) {
            data = JSON.parse(data);
            if (data.erro) {
                alert(data.erro);
            }else{
                $("#estoque_atual_minimo").text(estoque_minimo);
                $("#estoque_atual_maximo").text(estoque_maximo);
                if(data.insert == true){
                    location.reload();
                }

            }
            $("button.btn_estoque_minimo_maximo").button("reset");
        });

    });

    $("a[id^=peca_]").on("click",function(){
        var linha = this.id.replace(/\D/g, "");
        $("#peca_referencia").val($("#peca_referencia_"+linha).val());
        $("#peca_descricao").val($("#peca_descricao_"+linha).val());
        $("#btn_acao").click();
    });

    $("button.btn_lanca_movimentacao").click(function() {

        var posto       = $("input[name=posto]").val();
        var peca        = $("input[name=peca]").val();
        var qtde        = $("input[name=quantidade]").val();
        var nota_fiscal = $("input[name=nota_fiscal]").val();
        var data        = $("input[name=data]").val();
        var observacao  = $("input[name=observacao]").val();
        var movimento   = $(this).attr("rel");

        if (typeof qtde != "undefined" && qtde > 0) {
            $.ajax({
                url: "posto_estoque.php",
                type: "post",
                data: {
                    ajax_lanca_movimentacao : true,
                    posto                   : posto,
                    peca                    : peca,
                    qtde                    : qtde,
                    movimento               : movimento,
                    nota_fiscal             : nota_fiscal,
                    data                    : data,
                    observacao              : observacao
                },
                beforeSend: function() {
                    $("button.btn_lanca_movimentacao").hide();
                    $("button.btn_lanca_movimentacao").first().before("<div class='alert alert-info'><h4>Lançando movimentação, aguarde...</h4></div>");
                }
	    }).always(function(data) {
		data = $.parseJSON(data);

                if (data.erro) {
                    alert(data.erro);
                } else {
                    $("#estoque_atual").text(data.estoque);
                    $("input[name=quantidade]").val("");
                    $("input[name=nota_fiscal]").val("");
                    $("input[name=data]").val("");
                    $("input[name=observacao]").val("");

                    var bg_movimento = (movimento == "entrada") ? "#DFF0D8" : "#F2DEDE";

                    $("#estoque_atual").css({ "background-color": bg_movimento });

                    setTimeout(function() {
                        $("#estoque_atual").css({ "background-color": "#FFFFFF" });
                    }, 3000);

                    atualizaMovimentacao(posto, peca);
                }

                $("button.btn_lanca_movimentacao").first().prev("div.alert-info").remove();
                $("button.btn_lanca_movimentacao").show();
            });
        } else {
            alert("Informe a quantidade a ser lançada");
        }
    });

});

function retorna_posto(retorno) {
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

function retorna_peca(retorno) {
    $("#peca_referencia").val(retorno.referencia);
    $("#peca_descricao").val(retorno.descricao);
}

function atualizaMovimentacao(posto, peca) {
	$.ajax({
		url: "posto_estoque.php",
		type: "POST",
		data: { ajax_atualiza_movimentacao: true, posto: posto, peca: peca },
		beforeSend: function() {
			$("#estoque_movimentacao > tbody").html("<tr><th colspan='4'><div class='alert alert-info'><h4>Atualizando movimentação</h4></div></th></tr>");
		}
	}).always(function(data) {
		data = $.parseJSON(data);

		if (data.erro) {
			$("#estoque_movimentacao > tbody").html("<tr><th colspan='4'><div class='alert alert-danger'><h4>Erro ao atualizar movimentação</h4></div></th></tr>");
		} else {
			var coluna_tipo;
			var coluna_qtde_usada;
			$("#estoque_movimentacao > tbody").html("");
			$.each(data.movimentacoes, function(key, movimentacao) {
				<? if ($login_fabrica == 158) { ?>
					if (movimentacao.tipo == null) {
						movimentacao.tipo = "";
					}
					coluna_tipo = "<td class='tac' >"+movimentacao.tipo+"</td>";
					coluna_qtde_usada = "<td class='tac'>"+movimentacao.qtde_usada+"</td>";
				<? } ?>

				if (movimentacao.data_nf == null) {
					movimentacao.data_nf = "";
				}

				if (movimentacao.nf == null) {
					movimentacao.nf = "";
				}

				$("#estoque_movimentacao > tbody").append("\
					<tr>\
						<td class='alert alert-"+movimentacao.class+" tac' style='background-color: "+movimentacao.bg+";' >\
							<b>"+movimentacao.movimento+"</b>\
						</td>\
						<td class='tac' >"+movimentacao.data+"</td>\
						<td class='tac' >"+movimentacao.qtde+"</td>\
						"+coluna_qtde_usada+"\
						<td class='tac' >"+movimentacao.nf+"</td>\
						<td class='tac' >"+movimentacao.data_nf+"</td>\
						"+coluna_tipo+"\
						<td class='tac' >"+movimentacao.obs+"</td>\
						<td>"+movimentacao.admin+"</td>\
					</tr>\
				");
			});
		}
	});
}

</script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error" >
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<?php if (count($msg_erro["msg"]) == 0 && strlen($msg_ok) > 0) { ?>
    <div class="alert alert-success" >
        <h4>Arquivo Enviado com Sucesso</h4>
    </div>
<?php } ?>

<div class="row" >
    <b class="obrigatorio pull-right" >  * Campos obrigatórios </b>
</div>

<form name="frm_relatorio_oss_em_aberto" method="POST" class="form-search form-inline tc_formulario" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>
    <br />

    <div class="row-fluid" >
        <div class="span2" ></div>

        <div class="span4" >
            <div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
                <label class="control-label" for="codigo_posto" >Código Posto</label>

                <div class="controls controls-row" >
                    <div class="span7 input-append" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="codigo_posto" id="codigo_posto" class="span12" value="<?= $codigo_posto ?>" />
                        <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>

        <div class="span4" >
            <div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
                <label class="control-label" for="descricao_posto" >Nome Posto</label>

                <div class="controls controls-row" >
                    <div class="span12 input-append" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="descricao_posto" id="descricao_posto" class="span12" value="<?= $descricao_posto ?>" />
                        <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>

        <div class="span2" ></div>
    </div>

    <div class="row-fluid" >
        <div class="span2" ></div>

        <div class="span4" >
            <div class="control-group <?=(in_array('peca', $msg_erro['campos'])) ? 'error' : ''?>" >
                <label class="control-label" for="peca_referencia" >Referência Peça</label>

                <div class="controls controls-row" >
                    <div class="span7 input-append" >
                        <input type="text" name="peca_referencia" id="peca_referencia" class="span12" value="<?= $peca_referencia ?>" />
                        <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>

        <div class="span4" >
            <div class="control-group <?=(in_array('peca', $msg_erro['campos'])) ? 'error' : ''?>" >
                <label class="control-label" for="peca_descricao" >Descrição Peça</label>

                <div class="controls controls-row" >
                    <div class="span12 input-append" >
                        <input type="text" name="peca_descricao" id="peca_descricao" class="span12" value="<?= $peca_descricao ?>" />
                        <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>

        <div class="span2" ></div>
    </div>

    <p>
        <br/>
        <button class="btn" id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));" >Pesquisar</button>
        <input type="hidden" id="btn_click" name="btn_acao" />
    </p>

    <br />

</form>
    <? if ($login_fabrica == 158 ) { ?>
    <div class="tac">
        <?php 
            $jsonPOST = excelPostToJson($_POST); 
            ?>
            <div id='gerar_excel' class="btn_excel" style="width: 325px;">
                <div class="tac">
                    <input type="hidden" id="jsonPOST" value='<?= $jsonPOST; ?>' />
                    <span><img src="imagens/excel.png" /></span>
                    <span class="txt">Excel Detalhado de Postos</span>
                </div>
            </div>
            <br />
        <?php
        } ?>
    </div>
<?php
$titulo = "Realizar Upload de Arquivo do Estoque Mínimo/Máximo";
if (in_array($login_fabrica, $min_maximo_null)) {
	$titulo = "Realizar Upload de Arquivo do Estoque";
}
?>
<div class="container">
<?php if ($login_fabrica != 175){ ?>
<form name="frm_relatorio_oss_em_aberto" method="post" class="form-search form-inline tc_formulario" action="<?= $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">

<div class="titulo_tabela" ><?=$titulo?></div>

    <input type="hidden" name="arquivo_csv" value="sim" />

    <br />

    <?php
    if ($login_fabrica == 163) { ?>
        <span class="label label-important">Layout do arquivo: CNPJ do posto, Referencia da Peça, Quantidade, separados por ponto e virgula (;)</span>
    <?php
    } else { ?>
        <span class="label label-important">Layout do arquivo: CNPJ do posto, Referencia da Peça, Qtde Mínima e Qtde Máxima, separados por ponto e virgula (;)</span>
    <?php
    }
    ?>

    <br /><br />

    <div class="row-fluid" >
        <div class="span2" ></div>

        <div class="span5" >
            <div class="control-group <?=(in_array('upload', $msg_erro['campos'])) ? 'error' : ''?>" >
                <label class="control-label" for="upload" >Arquivo CSV / TXT</label>

                <div class="controls controls-row" >
                    <div class="span12" >
                        <h5 class='asteristico'>*</h5>
                        <input type="file" name="upload" id="upload" class="span12" />
                    </div>
                </div>

            </div>
        </div>

        <div class="span2">
            <div class="controls controls-row" >
                <div class="span8" >
                    <br />
                    <input type="submit" class="btn btn-default" value="Realizar Upload" />
                </div>
            </div>

        </div>

    </div>

</form>
<?php } ?>


<?php 
    if($login_fabrica == 158){ 
        $titulo2 = "Saída de Estoque em Massa";
?>

<form name="frm_relatorio_oss_em_aberto" method="post" class="form-search form-inline tc_formulario" action="<?= $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">

<div class="titulo_tabela" ><?=$titulo2?></div>

    <input type="hidden" name="saida_estoque_csv" value="sim" />

    <br />

    <span class="label label-important">Layout do arquivo: CNPJ do posto, Referencia da Peça, Qtde, Nota fiscal, Data, Observação separados por ponto e virgula (;)</span>

    <br /><br />

    <div class="row-fluid" >
        <div class="span2" ></div>

        <div class="span5" >
            <div class="control-group <?=(in_array('upload', $msg_erro['campos'])) ? 'error' : ''?>" >
                <label class="control-label" for="upload" >Arquivo CSV / TXT</label>

                <div class="controls controls-row" >
                    <div class="span12" >
                        <h5 class='asteristico'>*</h5>
                        <input type="file" name="upload_saida_estoque" id="upload_saida_estoque" class="span12" />
                    </div>
                </div>

            </div>
        </div>

        <div class="span2">
            <div class="controls controls-row" >
                <div class="span8" >
                    <br />
                    <input type="submit" class="btn btn-default" value="Realizar Upload" />
                </div>
            </div>

        </div>

    </div>

</form>
<?php } 

if ($_POST["btn_acao"] == "submit" && !count($msg_erro["msg"])) {

    if ($login_fabrica == 158) {
        $colunaCentroDistribuidor = "
            ,
            ARRAY_TO_STRING(ARRAY(
                SELECT c.nome
                FROM tbl_distribuidor_sla_posto AS dsp
                INNER JOIN tbl_distribuidor_sla AS ds ON ds.distribuidor_sla = dsp.distribuidor_sla
                INNER JOIN tbl_cidade AS c ON c.cidade = ds.cidade
                WHERE dsp.posto = tbl_posto_fabrica.posto
            ), ', ') AS centro_distribuidor
        ";
    }

    $sql_posto = "SELECT
                tbl_posto_fabrica.posto AS posto_id,
                tbl_posto.nome AS posto_nome,
                tbl_posto_fabrica.tipo_posto
                {$colunaCentroDistribuidor}
            FROM tbl_posto_fabrica
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND tbl_posto_fabrica.posto = {$posto}";
    $res_posto = pg_query($con, $sql_posto);

    $posto = array(
        "id"   => pg_fetch_result($res_posto, 0, "posto_id"),
        "nome" => pg_fetch_result($res_posto, 0, "posto_nome"),
        "tipo_posto" => pg_fetch_result($res_posto, 0, "tipo_posto")
    );

    if ($login_fabrica == 158) {
        $posto["centro_distribuidor"] = pg_fetch_result($res_posto, 0, "centro_distribuidor");
    }

    if (in_array($login_fabrica, array(158))) {
        $sqlPostoInterno = "SELECT posto_interno FROM tbl_tipo_posto WHERE fabrica = {$login_fabrica} AND tipo_posto = {$posto['tipo_posto']};";
        $resPostoInterno = pg_query($con, $sqlPostoInterno);

        $posto["posto_interno"] = pg_fetch_result($resPostoInterno, 0, posto_interno);
    }

    if (!empty($peca)) {
        $sql_peca = "SELECT tbl_peca.peca AS peca_id, tbl_peca.referencia AS peca_referencia, tbl_peca.descricao AS peca_descricao
                FROM tbl_peca
                WHERE tbl_peca.fabrica = {$login_fabrica}
                AND tbl_peca.peca = {$peca}";
        $res_peca = pg_query($con, $sql_peca);

        $peca = array(
            "id"         => pg_fetch_result($res_peca, 0, "peca_id"),
            "referencia" => pg_fetch_result($res_peca, 0, "peca_referencia"),
            "descricao"  => pg_fetch_result($res_peca, 0, "peca_descricao")
        );

        if($login_fabrica == 74){
            $cond_tipo = "AND tipo = 'estoque'";
        }
        $sql_estoque = "SELECT peca,
                    qtde,
                    estoque_minimo,
                    estoque_maximo
                FROM tbl_estoque_posto
                WHERE fabrica = {$login_fabrica}
                AND posto = {$posto['id']}
                AND peca = {$peca['id']}
                $cond_tipo";
        $res_estoque = pg_query($con, $sql_estoque);

        if (pg_num_rows($res_estoque) > 0) {
            $peca_estoque   = pg_fetch_result($res_estoque, 0, "peca");
            $estoque_atual  = pg_fetch_result($res_estoque, 0, "qtde");
            $estoque_minimo = pg_fetch_result($res_estoque, 0, "estoque_minimo");
            $estoque_maximo = pg_fetch_result($res_estoque, 0, "estoque_maximo");
        } else {
            $peca_estoque = 0;
            $estoque_atual = 0;
            $estoque_minimo = 0;
            $estoque_maximo = 0;
        }


	if($login_fabrica == 158){
		$sqlSaldoPeca = "SELECT sum(tbl_os_item.qtde) as saldopecaos FROM tbl_os 
		join tbl_os_produto on tbl_os_produto.os = tbl_os.os 
		join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
		join tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.fabrica = $login_fabrica 
		where tbl_os_item.peca = ".$peca['id']." 
		and tbl_os.fabrica = $login_fabrica 
		and tbl_os.finalizada is null 
		and tbl_os.data_fechamento is null 
		and tbl_os.excluida IS NOT TRUE
		and tbl_os.cancelada IS NOT TRUE
		and tbl_servico_realizado.troca_de_peca is true
		and tbl_os.posto = ".$posto['id'];

		$resSaldoPeca = pg_query($con, $sqlSaldoPeca);
		if(pg_last_error($resSaldoPeca)==0){
			$saldo_peca_os = pg_fetch_result($resSaldoPeca, 0, saldopecaos);
			$saldo_peca_os = (strlen(trim($saldo_peca_os)) == 0) ? 0 : $saldo_peca_os; 
		}
	}

        if (in_array($login_fabrica, array(158)) && $posto['posto_interno'] == "t") {
            $estoque_minimo = 0;
            $readonly_min = "readonly";
        } ?>

        <table class="table table-striped table-bordered" >
            <thead>
                <tr>
                    <th class="titulo_coluna" >Posto Autorizado</th>
                    <th class="tal" ><?=$posto["nome"]?></th>
                </tr>
                <?php
                if ($login_fabrica == 158) {
                ?>
                    <tr>
                        <th class="titulo_coluna" >Unidades de Negócio</th>
                        <th class="tal" ><?=$posto["centro_distribuidor"]?></th>
                    </tr>
                <?php
                }
                ?>
                <tr>
                    <th class="titulo_coluna" >Peça</th>
                    <?php
                    $peca['descricao'] = (mb_check_encoding($peca['descricao'], 'UTF8')) ? $peca['descricao'] = utf8_decode($peca['descricao']) : $peca['descricao'];
                    ?>
                    <th class="tal" ><?="{$peca['referencia']} - {$peca['descricao']}"?></th>
                </tr>
                <tr>
                    <th class="titulo_coluna" >Estoque</th>
                    <th class="tal" id="estoque_atual" ><?=$estoque_atual?></th>
                </tr>
                <?php
                if (!in_array($login_fabrica, $min_maximo_null)) { ?>
                <tr>
                    <th class="titulo_coluna" >Estoque Mínimo</th>
                    <th class="tal" id="estoque_atual_minimo" ><?=$estoque_minimo?></th>
                </tr>
                <tr>
                    <th class="titulo_coluna" >Estoque Máximo</th>
                    <th class="tal" id="estoque_atual_maximo" ><?=$estoque_maximo?></th>
                </tr>
                <?php
                }
                if($login_fabrica == 158){ ?>
                     <tr>
                        <th class="titulo_coluna" >Saldo em O.S</th>
                        <th class="tal" id="estoque_atual" ><?=$saldo_peca_os?></th>
                    </tr>
                <?php }
                ?>
            </thead>
        </table>
        <?php
        if (!in_array($login_fabrica, $min_maximo_null)) { ?>

        <br />

        <div class="titulo_tabela" style="margin-bottom: 10px;">Lançar Estoque Mínimo e Máximo para a Peça</div>

        <div class="row-fluid" >
            <div class="span3"></div>

            <div class="span2" >
                <div class="control-group  <?=(in_array('estoque_minimo', $msg_erro['campos'])) ? 'error' : ''?>">
                    <label class="control-label" for="estoque_minimo" >Quantidade Mínima</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input type="text" name="estoque_minimo" id="estoque_minimo" class="span12" maxlength="5" value="<?= $estoque_minimo; ?>" <?= $readonly_min; ?> />
                        </div>
                    </div>
                </div>
            </div>

            <div class="span2" >
                <div class="control-group <?=(in_array('estoque_maximo', $msg_erro['campos'])) ? 'error' : ''?>" >
                    <label class="control-label" for="estoque_maximo" >Quantidade Máxima</label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <input type="text" name="estoque_maximo" id="estoque_maximo" class="span12" maxlength="5" value="<?= $estoque_maximo; ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="span3" >
                <div class="control-group" >
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <br />
                            <button type="button" rel="entrada" data-loading-text="Salvando..." class="btn btn-success btn_estoque_minimo_maximo">Salvar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="span3"></div>

        </div>
        <?php
        } ?>
        <input type="hidden" name="posto" value="<?=$posto['id']?>" />
        <input type="hidden" name="peca" value="<?=$peca['id']?>" />

        <br />
        <? if ($peca_estoque != 0 OR in_array($login_fabrica, array(163,178))) { ?>
        <div class="titulo_tabela" style="margin-bottom: 10px;">Lançar Movimentação</div>
        <div class="row-fluid" >
            <div class="span2" >
                <div class="control-group" >
                    <label class="control-label" for="quantidade" >Quantidade</label>

                    <div class="controls controls-row" >
                        <div class="span12" >
                            <input type="text" name="quantidade" id="quantidade" class="span12" maxlength="5" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2" >
                <div class="control-group" >
                    <label class="control-label" for="nota_fiscal" >Nota Fiscal</label>

                    <div class="controls controls-row" >
                        <div class="span12" >
                            <input type="text" name="nota_fiscal" id="nota_fiscal" class="span12" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2" >
                <div class="control-group" >
                    <label class="control-label" for="data" >Data</label>

                    <div class="controls controls-row" >
                        <div class="span12" >
                            <input type="text" name="data" id="data" class="span12" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span6" >
                <div class="control-group" >
                    <label class="control-label" for="observacao" >Observação</label>

                    <div class="controls controls-row" >
                        <div class="span12" >
                            <input type="text" name="observacao" id="observacao" class="span12" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid" >
            <div class="span12" >
                <div class="control-group" >
                    <div class="controls controls-row" >
                        <div class="span12 tac" >
                            <? if ($login_fabrica != 158) { ?>
                                <button type="button" rel="entrada" class="btn btn-success btn_lanca_movimentacao" ><i class="icon-plus icon-white" ></i> Lançar Entrada</button>
                            <? } ?>
                            <button type="button" rel="saida" class="btn btn-danger btn_lanca_movimentacao" ><i class="icon-minus icon-white" ></i> Lançar Saída</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <br />

		<table id="estoque_movimentacao" class="table table-striped table-bordered table-large" style="table-layout: fixed; margin: 0 auto;" >
			<thead>
				<tr class="titulo_coluna" >
					<th colspan="<?=($login_fabrica == 158) ? 10 : 7?>" >Movimentação - Estoque do Posto</th>
				</tr>
				<tr class="titulo_coluna" >
					<th>Saida / Entrada</th>
					<th>Data</th>
					<th>Quantidade</th>
					<? if ($login_fabrica == 158) { ?>
						<th>Qtde. Fiscal Usada</th>
					<? } ?>
					<th>Nota Fiscal</th>
					<th>Data NF</th>
					<? if ($login_fabrica == 158) { ?>
						<th>Unidade de Negócio</th>
						<th>Tipo</th>
					<? } ?>
					<th>Observação</th>
					<th>Admin</th>
				</tr>
			</thead>
			<tbody>
				<?php

				if ($login_fabrica == 158) {
					//$distinct = "DISTINCT";

					$colunaTipoPedido = ", tbl_tipo_pedido.pedido_em_garantia, tbl_pedido.pedido, tbl_estoque_posto_movimento.data_digitacao";
					$colunaUnidadeNegocio = ", JSON_FIELD('unidadeNegocio',tbl_estoque_posto_movimento.parametros_adicionais) AS unidade_negocio";
					$leftJoinFaturamento = "
						LEFT JOIN tbl_pedido ON tbl_pedido.pedido = tbl_estoque_posto_movimento.pedido AND tbl_pedido.fabrica = {$login_fabrica}
						LEFT JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$login_fabrica}
					";
				}

				$sql = "SELECT {$distinct}
						CASE WHEN tbl_estoque_posto_movimento.qtde_entrada IS NOT NULL THEN 'Entrada' ELSE 'Saída' END AS movimento,
						tbl_estoque_posto_movimento.qtde_entrada,
						COALESCE(tbl_estoque_posto_movimento.qtde_usada,0) AS qtde_usada,
						COALESCE(tbl_estoque_posto_movimento.qtde_usada_estoque,0) AS qtde_usada_estoque,
						tbl_estoque_posto_movimento.qtde_saida,
						tbl_estoque_posto_movimento.nf AS nota_fiscal,
						TO_CHAR(tbl_estoque_posto_movimento.data, 'DD/MM/YYYY') AS data_nf,
						tbl_estoque_posto_movimento.tipo,
						tbl_estoque_posto_movimento.obs AS observacao,
                        tbl_estoque_posto_movimento.os,
						TO_CHAR(tbl_estoque_posto_movimento.data_digitacao, 'DD/MM/YYYY HH:MM') AS data,
						tbl_admin.nome_completo AS admin
						{$colunaTipoPedido}
						{$colunaUnidadeNegocio}
					FROM tbl_estoque_posto_movimento
					LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_estoque_posto_movimento.admin AND tbl_admin.fabrica = {$login_fabrica}
					{$leftJoinFaturamento}
					WHERE tbl_estoque_posto_movimento.fabrica = {$login_fabrica}
					AND tbl_estoque_posto_movimento.posto = {$posto['id']}
					AND tbl_estoque_posto_movimento.peca = {$peca['id']}
					ORDER BY tbl_estoque_posto_movimento.data_digitacao DESC";
				$res = pg_query($con, $sql);
                
                if (pg_num_rows($res) > 0) {
					$rows = pg_num_rows($res);

					for ($i = 0; $i < $rows; $i++) {
						$movimento    = pg_fetch_result($res, $i, "movimento");
						$qtde_entrada = pg_fetch_result($res, $i, "qtde_entrada");
						$qtde_saida   = pg_fetch_result($res, $i, "qtde_saida");
						$data         = pg_fetch_result($res, $i, "data");
						$nota_fiscal  = pg_fetch_result($res, $i, "nota_fiscal");
						$data_nf      = pg_fetch_result($res, $i, "data_nf");
						$tipo         = pg_fetch_result($res, $i, "tipo");
						$observacao   = pg_fetch_result($res, $i, "observacao");
                        $os_os        = pg_fetch_result($res, $i, "os");
						$admin        = pg_fetch_result($res, $i, "admin");

                        if (mb_check_encoding($observacao, 'UTF8')){
                            $observacao = utf8_decode($observacao);
                        }

                        if (stristr($observacao, '><') && !empty($os_os)) {
                            $observacao .= "<b>$os_os</b>";
                        }

						if ($login_fabrica == 158) {
							$pedido_em_garantia = pg_fetch_result($res, $i, "pedido_em_garantia");
							$pedido             = pg_fetch_result($res, $i, "pedido");
							$qtde_usada	    = pg_fetch_result($res, $i, "qtde_usada");
							$qtde_usada_estoque = pg_fetch_result($res, $i, "qtde_usada_estoque");
							$unidade_negocio    = pg_fetch_result($res, $i, "unidade_negocio");

							if (empty($pedido)) {
								$tipo_pedido = ($tipo == "GARANTIA") ? "<span class='label label-success'>Garantia</span>" : "<span class='label label-important'>Fora<br>de<br> Garantia</span>";
							} else {
								$tipo_pedido = ($pedido_em_garantia == "t") ? "<span class='label label-success'>Garantia</span>" : "<span class='label label-important'>Fora<br>de<br>Garantia</span>";
							}

							if ($movimento != "Entrada") {
								unset($tipo_pedido);
							}
						}

						$class_movimento = ($movimento == "Entrada") ? "success" : "danger";
						$bg_movimento = ($movimento == "Entrada") ? "#dff0d8" : "#f2dede";
						?>

						<tr>
							<td class="alert alert-<?=$class_movimento?> tac" style="background-color: <?=$bg_movimento?>;" >
								<b><?=$movimento?></b>
							</td>
							<td class="tac" ><?=$data?></td>
							<td class="tac" >
								<?= $qtde = ($movimento == "Entrada") ? $qtde_entrada : $qtde_saida; ?>
							</td>
							<? if ($login_fabrica == 158) { ?>
								<td class="tac">
									<?= ($movimento == "Entrada") ? $qtde_usada : ""; ?>
								</td>
							<? } ?>
							<td class="tac"><?=$nota_fiscal?></td>
							<td class="tac"><?=$data_nf?></td>
							<?php
							if ($login_fabrica == 158) {
							?>
								<td class="tac"><?=$unidade_negocio?></td>
								<td class="tac"><?=$tipo_pedido?></td>
							<?php
							}
							?>
							<td class="tac"><?=$observacao?></td>
							<td><?=$admin?></td>
						</tr>

					<?php
					}
				} else {
				?>
					<tr>
						<th colspan="<?=($login_fabrica == 158) ? 9 : 7?>" >
							<div class="alert alert-danger" style="margin-bottom: 0px;" >
								<h4>Nenhuma movimentação registrada</h4>
							</div>
						</th>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>
	<?php }else{ ?>
			<div class="alert alert-danger" style="margin-bottom: 0px;" ><h5>Para habilitar o controle de estoque da peça, grave as informações acima.</h5></div>
		<?php
		}
	} else {
		$sql = "SELECT tbl_peca.peca,
				tbl_peca.referencia AS peca_referencia,
				tbl_peca.descricao AS peca_descricao,
				tbl_estoque_posto.qtde,
                tbl_estoque_posto.estoque_minimo AS estoque_minimo,
                tbl_estoque_posto.estoque_maximo AS estoque_maximo
			FROM tbl_estoque_posto
			INNER JOIN tbl_peca ON tbl_peca.peca = tbl_estoque_posto.peca AND tbl_peca.fabrica = {$login_fabrica}
			WHERE tbl_estoque_posto.fabrica = {$login_fabrica}
			AND tbl_estoque_posto.posto = {$posto['id']}";
		$res = pg_query($con, $sql);

        if ($login_fabrica == 158) { 
            $colspan = 5;
        } else {
            $colspan = 2;
        }
		?>

		<table id="estoque_movimentacao" class="table table-striped table-bordered" >
			<thead>
				<tr class="titulo_coluna" >
					<th colspan="<?= $colspan ?>" ><?=$posto['nome']?></th>
				</tr>
				<tr class="titulo_coluna" >
					<th>Peça</th>
					<th>Estoque</th>
                    <?php 
                    if ($login_fabrica == 158) { ?>
                        <th>Estoque Mínimo</th>
                        <th>Estoque Máximo</th>
                        <th>Saldo em O.S</th>
                    <?php 
                    }
                    ?>
				</tr>
			</thead>
			<tbody>
				<?php
				if (pg_num_rows($res) > 0) {
					$rows = pg_num_rows($res);

					for ($i = 0; $i < $rows; $i++) {
						$peca_referencia = pg_fetch_result($res, $i, "peca_referencia");
						$peca_descricao  = pg_fetch_result($res, $i, "peca_descricao");
						$qtde            = pg_fetch_result($res, $i, "qtde");
                        $peca            = pg_fetch_result($res, $i, "peca");

                        if ($login_fabrica == 158) { 
                            $estoque_maximo  = pg_fetch_result($res, $i, "estoque_maximo");
                            $estoque_minimo  = pg_fetch_result($res, $i, "estoque_minimo");
                        }

                        if (mb_check_encoding($peca_descricao, 'UTF8')) {
                            $peca_descricao = utf8_decode($peca_descricao);
                        }


			if($login_fabrica == 158){
				$sqlSaldoPeca = "SELECT SUM(tbl_os_item.qtde) as saldopecaos FROM tbl_os 
				join tbl_os_produto on tbl_os_produto.os = tbl_os.os 
				join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
				join tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.fabrica = $login_fabrica 
				where tbl_os_item.peca = $peca 
				    and tbl_os.fabrica = $login_fabrica 
				    and tbl_os.finalizada is null 
				    and tbl_os.data_fechamento is null 
				    and tbl_os.excluida IS NOT TRUE
				    and tbl_os.cancelada IS NOT TRUE
				    and tbl_servico_realizado.troca_de_peca is true
				    and tbl_os.posto = ".$posto['id'];
			       

				$resSaldoPeca = pg_query($con, $sqlSaldoPeca);
				if(pg_last_error($resSaldoPeca)==0){
					$saldo_peca_os = pg_fetch_result($resSaldoPeca, 0, saldopecaos);
					$saldo_peca_os = (strlen(trim($saldo_peca_os)) == 0) ? 0 : $saldo_peca_os; 
				}
			}

						// $url = "posto_estoque.php?btn_acao=submit&codigo_posto={$codigo_posto}&descricao_posto={$descricao_posto}&peca_referencia={$peca_referencia}&peca_descricao={$peca_descricao}";
						?>

						<tr>
							<td>
								<a style="cursor:pointer;" id="peca_<?=$i?>"><?="{$peca_referencia} - {$peca_descricao}"?></a>
								<input type="hidden" id="peca_referencia_<?=$i?>" value="<?=$peca_referencia?>">
								<input type="hidden" id="peca_descricao_<?=$i?>" value="<?=$peca_descricao?>">
							</td>
							<td class="tac" ><?=$qtde?></td>
                            <?php if ($login_fabrica == 158) { ?>
                                        <td class="tac" ><?=$estoque_minimo?></td>
                                        <td class="tac" ><?=$estoque_maximo?></td>
                                        <td class="tac" ><?=$saldo_peca_os?></td>
                            <?php } ?>
						</tr>

					<?php
					}
				} else {
				?>
					<tr>
						<th colspan="2" >
							<div class="alert alert-danger" style="margin-bottom: 0px;" >
								<h4>Nenhuma peça com estoque registrada</h4>
							</div>
						</th>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>
	<?php
	}

}
echo "</div>";
include "rodape.php";

?>
