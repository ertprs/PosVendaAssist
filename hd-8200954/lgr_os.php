<?php

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {  
    include __DIR__.'/admin/autentica_admin.php'; 
} else { 
    include __DIR__.'/autentica_usuario.php';
} 

include __DIR__.'/funcoes.php';

if ($_POST["ajax_atualizar_peca_lgr"]) {
    try { 
        $extrato = $_POST["extrato"];
        $lgr_id  = $_POST["lgr_id"];

        if (empty($extrato)) { 
            throw new Exception("Extrato não informado");
        }

        if (empty($lgr_id)) {
            throw new Exception("Peça não informada");
        }

        $sql = "
            SELECT el.extrato_lgr, el.extrato, el.posto, el.peca, el.qtde, ea.codigo AS unidade_negocio
            FROM tbl_extrato_lgr el
            INNER JOIN tbl_extrato e ON e.extrato = el.extrato AND e.fabrica = {$login_fabrica}
            INNER JOIN tbl_extrato_agrupado ea ON ea.extrato = e.extrato
            WHERE el.extrato = {$extrato}
            AND el.extrato_lgr = {$lgr_id}
        ";
        $qry = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0 || pg_num_rows($qry) == 0) {
            throw new Exception("LGR não encontrado");
        }

	$lgr = pg_fetch_assoc($qry);

        $sql = "
            SELECT epm.qtde_entrada, epm.qtde_usada_estoque, epm.pedido, epm.faturamento AS estoque_faturamento, epm.nf, epm.os_item, epm.obs, epm.data_digitacao, f.faturamento, fi.faturamento_item, f.nota_fiscal, fi.preco, fi.aliq_icms AS icms, fi.aliq_ipi AS ipi
	    FROM tbl_estoque_posto_movimento epm
	    INNER JOIN tbl_faturamento f ON (f.faturamento = epm.faturamento OR f.nota_fiscal = epm.nf) AND f.fabrica = {$login_fabrica}
	    INNER JOIN tbl_faturamento_item fi ON fi.faturamento = f.faturamento AND fi.peca = epm.peca AND (fi.pedido = epm.pedido OR epm.pedido IS NULL)
            LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = fi.pedido_item
            LEFT JOIN tbl_pedido p ON p.pedido = pi.pedido AND p.fabrica = {$login_fabrica}
            LEFT JOIN tbl_tipo_pedido tp ON tp.tipo_pedido = p.tipo_pedido AND tp.fabrica = {$login_fabrica}
            WHERE epm.fabrica = {$login_fabrica}
            AND (tp.pedido_em_garantia IS NOT TRUE OR epm.tipo IS NULL)
            AND epm.posto = {$lgr['posto']}
            AND epm.peca = {$lgr['peca']}
            AND json_field('unidadeNegocio',epm.parametros_adicionais) = '{$lgr['unidade_negocio']}'
            AND (epm.qtde_entrada IS NOT NULL AND epm.qtde_entrada >= {$lgr['qtde']})
            AND ((epm.qtde_entrada - COALESCE(epm.qtde_usada_estoque, 0)) >= {$lgr['qtde']})
            ORDER BY epm.data_digitacao ASC
            LIMIT 1
        ";
        $qry = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0 || pg_num_rows($qry) == 0) {
            throw new Exception("Não foi encontrado movimentação para atender o LGR");
        }

        $movimentacao = pg_fetch_assoc($qry);

	pg_query($con, "BEGIN");

	$whereObs = (empty($movimentacao["obs"])) ? "AND obs IS NULL" : "AND obs = '{$movimentacao['obs']}'";
	$wherePedido = (empty($movimentacao["pedido"])) ? "AND pedido IS NULL": "AND pedido = {$movimentacao['pedido']}";
	$whereFaturamento = (empty($movimentacao["estoque_faturamento"])) ? "AND faturamento IS NULL" : "AND faturamento = {$movimentacao['estoque_faturamento']}";

        $upd = "
            UPDATE tbl_estoque_posto_movimento SET
              qtde_usada_estoque = COALESCE(qtde_usada_estoque, 0) + {$lgr['qtde']}
            WHERE fabrica = {$login_fabrica}
            AND posto = {$lgr['posto']}
	    AND peca = {$lgr['peca']}
	    AND nf = '{$movimentacao['nf']}'
        AND json_field('unidadeNegocio',tbl_estoque_posto_movimento.parametros_adicionais) = '{$lgr['unidade_negocio']}'
	    AND data_digitacao = '{$movimentacao['data_digitacao']}'
	{$whereFaturamento}
	{$whereObs}	
	{$wherePedido}
	AND qtde_entrada = {$movimentacao['qtde_entrada']}
        ";
        $qry = pg_query($con, $upd);

        if (strlen(pg_last_error()) > 0 || pg_affected_rows($qry) != 1) {
            throw new Exception("Erro interno #0");
        }

        $upd = "
            UPDATE tbl_extrato_lgr SET
                faturamento_item = {$movimentacao['faturamento_item']}
            WHERE extrato_lgr = {$lgr['extrato_lgr']}
        ";
        $qry = pg_query($con, $upd);

        if (strlen(pg_last_error()) > 0 || pg_affected_rows($qry) != 1) {
            throw new Exception("Erro interno #1");
        }

        pg_query($con, "COMMIT");

        exit(json_encode(array(
            "sucesso" => true, 
            "dados"   => array(
                "nota_fiscal_origem" => $movimentacao["nota_fiscal"],
                "preco"              => number_format($movimentacao["preco"], 2, ".", ""),
                "icms"               => number_format($movimentacao["icms"], 2, ".", ""),
                "ipi"                => number_format($movimentacao["ipi"], 2, ".", "")
            )
        )));
    } catch(Exception $e) {
        pg_query($con, "ROLLBACK");

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($_POST["ajax_excluir_peca_lgr"]) {
    try {
        $extrato = $_POST["extrato"];
        $lgr_id  = $_POST["lgr_id"];

        if (empty($extrato)) {
            throw new Exception("Extrato não informado");
        }

        if (empty($lgr_id)) {
            throw new Exception("Peça não informada");
        }

        pg_query($con, "BEGIN");

        $del = "
            DELETE FROM tbl_extrato_lgr 
            USING tbl_extrato 
            WHERE tbl_extrato_lgr.extrato = tbl_extrato.extrato
            AND tbl_extrato.fabrica = {$login_fabrica}
            AND tbl_extrato_lgr.extrato = {$extrato}
            AND tbl_extrato_lgr.extrato_lgr = {$lgr_id}
        ";
        $qryDel = pg_query($con, $del);

        if (strlen(pg_last_error()) > 0 || pg_affected_rows($qryDel) != 1) {
            throw new Exception("Erro interno #0");
        }

        pg_query($con, "COMMIT");

        exit(json_encode(array("sucesso" => true)));
    } catch(Exception $e) {
        pg_query($con, "ROLLBACK");

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

function getCFOP($uf) {
    global $login_fabrica;
    
    if (($login_fabrica == 3 && $uf == "SC") || ($login_fabrica == 158 && $uf == "SP")) {
        $cfop = 5949;
    } else {
        $cfop = 6949;
    }

    if (($login_fabrica == 177 && $uf == "SP")) {
        $cfop = 5915;
    } else {
        $cfop = 6915;
    }


    return $cfop;

}

$extrato = $_REQUEST["extrato"];

$sql_extrato = "";
//veririfcar se extrato eh de garantia se for não deixar fazer header abaixo. 


if (!$_POST && $areaAdmin != true && $extrato_garantia != 't' && !in_array($login_fabrica, array(158,177))) {
    $sql = "SELECT extrato_lgr FROM tbl_extrato_lgr WHERE extrato = {$extrato} AND faturamento IS NOT NULL";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        header("Location: extrato_posto_lgr_itens_novo.php?extrato={$extrato}");
        exit;
    }
}

if ($_POST) {
    $msg_erro = array(
        "msg"    => array(),
        "campos" => array()
    );

    $nf_array = array();

    if (isset($_POST["nf_obg"])) {
        $nf_array["nf_obg"] = $_POST["nf_obg"];
    }

    if (isset($_POST["nf_nobg"])) {
        $nf_array["nf_nobg"] = $_POST["nf_nobg"];
    }

    $uf_posto = $_REQUEST['posto_estado'];

    foreach ($nf_array as $key => $nf) {
        $nota_fiscal  = trim($nf["nota_fiscal"]);
        $data_emissao = $nf["data_emissao"];
        $pecas        = $nf["pecas"];
        $nf["chave"]  = trim($nf["chave"]);

        if (empty($nota_fiscal)) {
            $msg_erro["msg"]["nf"] = "Informe o número da nota fiscal";
            $msg_erro["campos"][]  = "{$key}[nota_fiscal]";
        }

        if (empty($data_emissao)) {
            $msg_erro["msg"]["data"] = "Informe a data de emissão da nota fiscal";
            $msg_erro["campos"][]    = "{$key}[data_emissao]";
        } else {
            list($dia, $mes, $ano) = explode("/", $data_emissao);

            if (!strtotime("{$ano}-{$mes}-{$dia}")) {
                $msg_erro["msg"]["data_i"] = "Data de emissão inválida";
                $msg_erro["campos"][]      = "{$key}[data_emissao]";
            } else {
                $nf_array[$key]["data_emissao"] = "{$ano}-{$mes}-{$dia}";
            }
        }

        if ($login_fabrica != 177){
			$qtde_volume = 0 ;
            if (strlen($nf["chave"]) != 44) {
                $msg_erro["msg"]["chave"] = "A chave da nota fiscal deve ter 44 caracteres";
                $msg_erro["campos"][]  = "{$key}[chave]";
            }

            if (strlen($nf["n_log"]) != 15) {
                $msg_erro["msg"]["n_log"] = "O número de log da nota fiscal deve ter 15 digitos";
                $msg_erro["campos"][]  = "{$key}[n_log]";
            }
		}else{
			$qtde_volume = $nf["qtde_volume"] ;
            $transportadora = $_POST['transportadora'];  

            if(strlen(trim($transportadora))==0){
                $msg_erro["msg"]["n_log"] = "Informe a transportadora.";
                $msg_erro["campos"][]  = "transportadora";
			}            
			if(strlen(trim($qtde_volume))==0){
                $msg_erro["msg"]["n_log"] = "Informe o volume.";
                $msg_erro["campos"][]  = "qtde_volume";
            }            

		}

        foreach ($pecas as $id => $dados) {
            if (!strlen($dados["qtde"])) {
                $msg_erro["msg"]["qtde_obg"] = "É necessário informar a quantidade a ser devolvidade de todas as peças listadas";
                $msg_erro["campos"][] = "peca_{$id}";
            } else if ($dados["qtde"] > $dados["qtde_real"]) {
                $msg_erro["msg"]["qtde_maior"] = "Qtde a ser devolvida não pode ser superior a Qtde ";
                $msg_erro["campos"][] = "peca_{$id}";
            }
        }
    }

    if (empty($msg_erro["msg"])) {
        try {
            pg_query($con, "BEGIN");

            $sql = "SELECT posto_fabrica FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);

            $posto_fabrica = pg_fetch_result($res, 0, "posto_fabrica");
            $cfop = getCFOP($uf_posto);

            if (empty($posto_fabrica)) {
                throw new Exception("LGR não configurado");
            }

            foreach ($nf_array as $key => $nf) {
                $colunas = array(
                    "fabrica",
                    "posto",
                    "distribuidor",
                    "nota_fiscal",
                    "serie",
                    "emissao",
                    "saida",
                    "total_nota",
                    "cfop",
                    "natureza",
                    "base_icms",
                    "valor_icms",
                    "base_ipi",
                    "valor_ipi",
                    "movimento",
                    "obs",
                    "chave_nfe",
					"status_nfe",
					"qtde_volume"
                );

                if ($login_fabrica == 177){
                    $colunas[] = "extrato_devolucao";

                    if($transportadora == 'conta'){
                        $colunas[] = "transp";
                    }else{
                        $colunas[] = "transportadora";    
                    }                    
                }

                $valores = array(
                    $login_fabrica,
                    $posto_fabrica,
                    $login_posto,
                    "'{$nf["nota_fiscal"]}'",
                    "'2'",
                    "'{$nf["data_emissao"]}'",
                    "'{$nf["data_emissao"]}'",
                    $nf["total_nota"],
                    "'{$cfop}'",
                    "'Remessa Para Garantia'",
                    $nf["base_icms"],
                    $nf["valor_icms"],
                    $nf["base_ipi"],
                    $nf["valor_ipi"],
                    "'RETORNAVEL'",
                    "'{$nf["observacao"]}'",
                    "'{$nf["chave"]}'",
					$nf["n_log"],
					$qtde_volume
                );
                
                if ($login_fabrica == 177){
                    $valores[] = $extrato;

                    if($transportadora == 'conta'){
                        $valores[] = "'$transportadora'";
                    }else{
                        $valores[] = $transportadora;
                    }
                    
                    $dados = array();
                    foreach ($valores as $key_f => $value_f) {
                        if (empty($value_f)){
                            if ($key_f == 7){
                                $dados[] = 0;
                            }else{
                                $dados[] = "null";
                            }
                        }else{
                            $dados[] = $value_f;
                        }
                    }
                    $valores = $dados;
                }
                
                $sql = "
                    INSERT INTO tbl_faturamento
                    (".implode(", ", $colunas).")
                    VALUES
                    (".implode(", ", $valores).")
                    RETURNING faturamento
                ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao gravar nota fiscal de devolução");
                }

                $faturamento = pg_fetch_result($res, 0, "faturamento");

                if($login_fabrica == 177){
                    $hash_temp = $_POST['hashBox'];

                    $sqlUpdAnexo = "UPDATE tbl_tdocs set referencia_id = $faturamento WHERE hash_temp = '$hash_temp'";
                    $resUpdAnexo = pg_query($con, $sqlUpdAnexo);

                    if(strlen(trim(pg_last_error($resUpdAnexo)))>0){
                        throw new Exception("Erro ao gravar anexo");
                    }
                }

                $devolucao_obrigatoria = ($key == "nf_obg") ? "true" : "false";

                foreach ($nf["pecas"] as $extrato_lgr => $peca) {
                    $sql = "
                        UPDATE tbl_extrato_lgr SET
                            qtde_nf = {$peca['qtde']},
                            faturamento = {$faturamento}
                        WHERE extrato_lgr = {$extrato_lgr}
                    ";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao atualizar peças da devolução");
                    }

                    $colunas = array(
                        "faturamento",
                        "peca",
                        "qtde",
                        "preco",
                        "aliq_icms",
                        "base_icms",
                        "valor_icms",
                        "aliq_ipi",
                        "base_ipi",
                        "valor_ipi",
                        "extrato_devolucao",
                        "devolucao_obrig",
                        "devolucao_origem"
                    );

                    $valores = array(
                        $faturamento,
                        $peca["peca"],
                        $peca["qtde"],
                        (!strlen($peca["preco"])) ? 0 : $peca["preco"],
                        (!strlen($peca["icms"])) ? 0 : $peca["icms"],
                        (!strlen($peca["base_icms"]) or $peca["base_icms"] == 'NaN') ? 0 : $peca["base_icms"],
                        (!strlen($peca["valor_icms"])) ? 0 : $peca["valor_icms"],
                        (!strlen($peca["ipi"]) or $peca["ipi"] == 'NaN') ? 0 : $peca["ipi"],
                        (!strlen($peca["base_ipi"]) or $peca["base_ipi"] == 'NaN') ? 0 : $peca["base_ipi"],
                        (!strlen($peca["valor_ipi"])) ? 0 : $peca["valor_ipi"],
                        $extrato,
                        $devolucao_obrigatoria,
                        $peca["faturamento"]
                    );

                    if ($login_fabrica == 177){
                        $dados_i = array();
                        foreach ($valores as $key_fi => $value_fi) {
                            if (empty($value_fi)){
                                if ($key_fi == 3){
                                    $dados_i[] = 0;
                                }else{
                                    $dados_i[] = "null";
                                }
                            }else{
                                $dados_i[] = $value_fi;
                            }
                        }
                        $valores = $dados_i;
                    }

                    $sql = "
                        INSERT INTO tbl_faturamento_item
                        (".implode(", ", $colunas).")
                        VALUES
                        (".implode(", ", $valores).")
                    ";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao gravar peças da nota fiscal de devolução");
                    }
                }
            }

            pg_query($con, "COMMIT");

            header("Location: extrato_posto_lgr_itens_novo.php?extrato={$extrato}");
            exit;
        } catch(Exception $e) {
            pg_query($con, "ROLLBACK");

            $msg_erro["msg"][] = $e->getMessage();
        }
    }
}

$layout_menu ='os';
$title = "PREENCHIMENTO DA DEVOLUÇÃO DE PEÇAS";

if ($areaAdmin === true) {
    include __DIR__.'/admin/cabecalho_new.php';
} else {
    include __DIR__.'/cabecalho_new.php';
}

$plugins = array(
   "datepicker",
   "maskedinput",
   "shadowbox",
   "alphanumeric"
);

include __DIR__.'/admin/plugin_loader.php';

if (!$areaAdmin) {
    $wherePosto = "AND tbl_extrato_lgr.posto = {$login_posto}";
    $subqueryWherePosto = "AND tbl_os.posto = {$login_posto}";
}

if ($login_fabrica == 177){
    $sql = "
        SELECT DISTINCT ON(id)
            tbl_extrato_lgr.extrato_lgr AS id,
            tbl_peca.referencia || ' - ' || tbl_peca.descricao AS peca,
            tbl_peca.peca AS peca_id,
            tbl_peca.peso, 
            tbl_extrato_lgr.qtde,
            tbl_extrato_lgr.devolucao_obrigatoria,
            0 AS aliq_icms,
            0 AS aliq_ipi,
            tbl_os_item.preco AS preco,
            NULL AS nota_fiscal_origem,
            NULL AS faturamento,
            0 AS valor_impostos,
            NULL AS tipo_pedido_codigo,
            NULL AS qtde_faturamento_item
            FROM tbl_extrato_lgr
            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_extrato_lgr.peca AND tbl_peca.fabrica = {$login_fabrica}
            INNER JOIN tbl_os_item ON tbl_os_item.os_item = tbl_extrato_lgr.os_item AND tbl_os_item.peca = tbl_peca.peca
            WHERE tbl_extrato_lgr.extrato = {$extrato}
            {$wherePosto}
            ORDER BY id
    ";
}else{
    $sql = "
        SELECT DISTINCT ON(id)
            tbl_extrato_lgr.extrato_lgr AS id,
            tbl_peca.referencia || ' - ' || tbl_peca.descricao AS peca,
            tbl_peca.peca AS peca_id,
            tbl_extrato_lgr.qtde,
            tbl_extrato_lgr.devolucao_obrigatoria,
            tbl_faturamento_item.aliq_icms,
            tbl_faturamento_item.aliq_ipi,
            tbl_faturamento_item.preco,
            tbl_faturamento.nota_fiscal AS nota_fiscal_origem,
            tbl_faturamento.faturamento,
            tbl_faturamento_item.valor_impostos,
            tbl_tipo_pedido.codigo as tipo_pedido_codigo,
            tbl_faturamento_item.qtde as qtde_faturamento_item
        FROM tbl_extrato_lgr
        INNER JOIN tbl_peca ON tbl_peca.peca = tbl_extrato_lgr.peca AND tbl_peca.fabrica = {$login_fabrica}
        INNER JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato_lgr.extrato
        INNER JOIN tbl_os ON tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = {$login_fabrica}
        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
        INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.peca = tbl_peca.peca
        LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento_item = tbl_extrato_lgr.faturamento_item
        LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = {$login_fabrica}

        left join tbl_pedido on tbl_faturamento_item.pedido = tbl_pedido.pedido and tbl_pedido.fabrica = $login_fabrica
        left join tbl_tipo_pedido on tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido and tbl_tipo_pedido.fabrica = $login_fabrica

        WHERE tbl_extrato_lgr.extrato = {$extrato}
        {$wherePosto}
        ORDER BY id
    ";
}
$qry = pg_query($con, $sql);

if (!pg_num_rows($qry)) {
    echo "<div class='alert alert-danger' ><strong>Não foram encontradas peças para devolução no extrato {$extrato}</strong></div>";
    exit;
}

$devolucao_obrigatoria     = array();
$devolucao_nao_obrigatoria = array();
$origem_completa           = true;

while ($row = pg_fetch_object($qry)) {
    
    if ($origem_completa && empty($row->nota_fiscal_origem)) {
        $origem_completa = false;
    }

    if ($row->devolucao_obrigatoria == "t") {
        $devolucao_obrigatoria[] = $row;
    } else {
        $devolucao_nao_obrigatoria[] = $row;
    }
}
$sqlPosto = "
    SELECT
        p.nome AS razao_social,
        p.cnpj,
        p.ie AS inscricao_estadual,
        pf.transportadora, 
        pf.contato_endereco,
        pf.contato_numero,
        pf.contato_complemento,
        pf.contato_cidade AS cidade,
        pf.contato_estado AS estado,
        pf.contato_cep AS cep
    FROM tbl_fabrica AS f
    INNER JOIN tbl_posto_fabrica AS pf ON pf.posto = f.posto_fabrica AND pf.fabrica = {$login_fabrica}
    INNER JOIN tbl_posto AS p ON p.posto = pf.posto
    WHERE f.fabrica = {$login_fabrica}
";

$resPosto = pg_query($con, $sqlPosto);

$posto_razao_social       = pg_fetch_result($resPosto, 0, "razao_social");
$posto_cnpj               = pg_fetch_result($resPosto, 0, "cnpj");
$posto_inscricao_estadual = pg_fetch_result($resPosto, 0, "inscricao_estadual");
$posto_cidade             = pg_fetch_result($resPosto, 0, "cidade");
$posto_estado             = pg_fetch_result($resPosto, 0, "estado");
$posto_cep                = pg_fetch_result($resPosto, 0, "cep");
$contato_endereco         = pg_fetch_result($resPosto, 0, "contato_endereco");
$contato_numero           = pg_fetch_result($resPosto, 0, "contato_numero");
$contato_complemento      = pg_fetch_result($resPosto, 0, "contato_complemento");

$posto_endereco = "$contato_endereco $contato_numero $contato_complemento "; 


?>

<br />

<form method="POST" >
    <input type="hidden" name="extrato" value="<?=$extrato?>" />
    <input type="hidden" name="posto_estado" value="<?=$posto_estado?>" />

    <?php
    if (count($msg_erro["msg"])) {
    ?>
        <div class="alert alert-danger" ><?=implode("<br />", $msg_erro["msg"])?></div>
    <?php
    }

    if (!$origem_completa && !$areaAdmin && $login_fabrica != 177) {
    ?>
        <div class="row-fluid">
            <div class="span12">
                <div class="alert alert-warning" >
                    <h5>Não será possível confirmar a Nota Fiscal de Devolução, há itens que estão sem uma Nota Fiscal de Origem, aguarde a correção da Fábrica para seguir com a Devolução.</h5>
                </div>
            </div>
        </div>  
    <?php
    }

    if (!empty($_GET["extrato"])) {
    ?>
        <table class="table table-bordered no-margin" >
            <tr class="titulo_coluna" >
                <td>Extrato <?=$_GET["extrato"]?></td>
            </tr>
        </table>
    <?php
    }

    if (count($devolucao_obrigatoria) > 0) {
    ?>
        <table class="table table-bordered no-margin" >
            <tr class="titulo_coluna" >
                <td>Peças de devolução obrigatória</td>
            </tr>
        </table>

        <?php
        if (!$areaAdmin) {
        ?>
            <table class="table table-bordered no-margin" >
                <tr class="titulo_coluna" >
                    <th>Natureza</th>
                    <th>CFOP</th>
                </tr>
                <tr>
                    <td>Remessa Para Garantia</td>
                    <td><?= getCFOP($posto_estado); ?></td>
                </tr>
            </table>

            <table class="table table-bordered no-margin" >
                <tr class="titulo_coluna" >
                    <th>Razão Social</th>
                    <th>CNPJ</th>
                    <th>Inscrição Estadual</th>
                </tr>
                <tr>
                    <td><?=$posto_razao_social?></td>
                    <td><?=$posto_cnpj?></td>
                    <td><?=$posto_inscricao_estadual?></td>
                </tr>
            </table>

            <table class="table table-bordered no-margin" >
                <tr class="titulo_coluna" >
                    <th>Endereço</th>
                    <th>Cidade</th>
                    <th>Estado</th>
                    <th>CEP</th>
                </tr>
                <tr>
                    <td><?=$posto_endereco?></td>
                    <td><?=$posto_cidade?></td>
                    <td><?=$posto_estado?></td>
                    <td><?=$posto_cep?></td>
                </tr>
            </table>
        <?php
        }
        if($login_fabrica == 177){
            $sqlPF = "SELECT transportadora FROM tbl_posto_fabrica  WHERE posto = $login_posto and fabrica = $login_fabrica";
            $resPF = pg_query($con, $sqlPF);
            if(pg_num_rows($resPF)>0){
                $transportadoraPosto  = pg_fetch_result($resPF, 0, 'transportadora');
            }

            $sqlTransportadora = "SELECT  
                                    tbl_transportadora.cnpj, 
                                    tbl_transportadora.nome,
                                    tbl_transportadora.transportadora,
                                    tbl_transportadora_fabrica.codigo_interno,
                                    tbl_transportadora_fabrica.ativo,
                                    tbl_transportadora_fabrica.contato_email,
                                    tbl_transportadora_fabrica.contato_endereco,
                                    tbl_transportadora_fabrica.contato_cidade,
                                    tbl_transportadora_fabrica.contato_estado,
                                    tbl_transportadora_fabrica.contato_bairro,
                                    tbl_transportadora_fabrica.contato_cep,
                                    tbl_transportadora_fabrica.fone
                                FROM tbl_transportadora 
                                JOIN tbl_transportadora_fabrica ON tbl_transportadora.transportadora = tbl_transportadora_fabrica.transportadora                                
                                WHERE tbl_transportadora.transportadora = $transportadoraPosto
                                AND fabrica = $login_fabrica";
            $resTransportadora = pg_query($con, $sqlTransportadora);
            if(pg_num_rows($resTransportadora)>0){
                $transportadora_cnpj = pg_fetch_result($resTransportadora, 0, 'cnpj');
                $transportadora_nome = pg_fetch_result($resTransportadora, 0, 'nome');
                $transportadora_endereco = pg_fetch_result($resTransportadora, 0, 'contato_endereco');
                $transportadora_cidade = pg_fetch_result($resTransportadora, 0, 'contato_cidade');
                $transportadora_estado = pg_fetch_result($resTransportadora, 0, 'contato_estado');
                $transportadora_bairro = pg_fetch_result($resTransportadora, 0, 'contato_bairro');
                $transportadora_cep = pg_fetch_result($resTransportadora, 0, 'contato_cep');
            }
        ?>
        <input type="hidden" name="transportadora" value="<?=$transportadoraPosto?>"> 
        <table class="table table-bordered table-striped no-margin nf" >
            <thead>
                <tr class="titulo_coluna" >  
                    <td colspan="5">Transportadora</td>
                </tr>
                <tr class="titulo_coluna">
                    <td>Razão Social</td>
                    <td>CNPJ</td>
                    <td>Frete por Conta</td>
                    <td>Peso</td>
                    <td>Volume</td>
                </tr>
                <tr>
                    <td><?=$transportadora_nome?></td>
                    <td><?=$transportadora_cnpj?></td>
                    <td>Destinatário</td>
                    <td class="peso_total"></td>
					<td>                        
						<div class="<?=(in_array('qtde_volume', $msg_erro['campos'])) ? "control-group error" : "" ?>">
						<input style="width: 40px;" id="qtde_volume" name="nf_obg[qtde_volume]" type="numeric" value="<?=getValue('nf_obg[qtde_volume]')?>" maxlength="3" />                        
						</div>
                    </td>
                </tr>
                <tr class="titulo_coluna">
                    <td>Endereço</td>
                    <td>Cidade</td>
                    <td>Estado</td>
                    <td colspan="2">CEP</td>
                    
                </tr>
                <tr>
                    <td><?=$transportadora_endereco?></td>
                    <td><?=$transportadora_cidade?></td>
                    <td><?=$transportadora_estado?></td>
                    <td colspan="2"><?=$transportadora_cep?></td>
                </tr>

            </thead>
        </table>

        <?php } ?>
        <table class="table table-bordered table-striped no-margin nf" >
            <thead>
                <tr class="titulo_coluna" >
                    <th>Peça</th>
                    
                    <?php if ($login_fabrica != 177){ ?>
                        <th>Nota Fiscal de Origem</th>
                    <?php } ?>
                    
                    <th>Qtde</th>

                    <?php if($login_fabrica == 177){ 
                        echo "<th>Peso</th>";
                        echo "<th>Preço</th>";
                        echo "<th>Preço Total</th>";
                        }
                    ?>
                    
                    <?php if (!$areaAdmin) { ?>
                        <th>Qtde a ser devolvida</th>
                    <?php } ?>

                    <?php if ($login_fabrica != 177){ ?>
                        <th>Preço</th>
                        <th>ICMS</th>
                        <th>PREÇO C/ICMS</th>
                        <th>IPI</th>
                        <th>Total</th>         
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!$areaAdmin) {
                    $os_relacao = array();
                    $nf_relacao = array();

                    $total_base_icms  = 0;
                    $total_valor_icms = 0;
                    $total_base_ipi   = 0;
                    $total_valor_ipi  = 0;
                }

                foreach ($devolucao_obrigatoria as $row) {
                    if (!$areaAdmin) {
                        $os_relacao = array_merge($os_relacao, explode(",", $row->array_os));
                        $nf_relacao = array_merge($nf_relacao, explode(",", $row->array_nota_fiscal));

                        $total_base_icms  += $row->base_icms;
                        $total_valor_icms += $row->valor_icms;
                        $total_base_ipi   += $row->base_ipi;
                        $total_valor_ipi  += $row->valor_ipi;

                        unset($class_erro);

                        if (in_array("peca_{$row->id}", $msg_erro["campos"])) {
                            $class_erro = "class='error'";
                        }
                    }

                    echo "
                        <tr {$class_erro} id='row-{$row->id}' >
                            <td>{$row->peca}</td>
                    ";

                    if ($areaAdmin) {
                        if (empty($row->nota_fiscal_origem)) {
                            echo "
                                <td class='nota-fiscal-origem' >
                                    <button type='button' data-lgr-id='{$row->id}' data-peca='{$row->peca}' data-qtde='{$row->qtde}' class='btn btn-success btn-mini atualizar-peca-lgr' title='Atualizar Informações da Nota Fiscal de Origem' ><i class='icon-repeat icon-white'></i></button>
                                    <button type='button' data-lgr-id='{$row->id}' data-peca='{$row->peca}' data-qtde='{$row->qtde}' class='btn btn-danger btn-mini excluir-peca-lgr' title='Excluir Peça do LGR' ><i class='icon-remove icon-white'></i></button>
                                </td>
                            ";
                        } else {
                            echo "<td>{$row->nota_fiscal_origem}</td>";
                        }
                    } else {
                        if ($login_fabrica != 177){
                            echo "<td>{$row->nota_fiscal_origem}</td>";
                        }
                    }

                    echo "<td class='tac' >{$row->qtde}</td>";                    
                    if($login_fabrica == 177){
                        echo "<td class='tac peso' >".$row->peso."</td>";
                        echo "<td class='tac' >". number_format($row->preco, 2, ',', ' ')."</td>";
                        echo "<td class='tac' >". number_format($row->qtde * $row->preco, 2, ',', ' ')."</td>";
                    }

                    if($login_fabrica == 158 and $row->tipo_pedido_codigo == 'BON-GAR' OR $row->tipo_pedido_codigo == "GAR"){
                        $preco_icms = (($row->preco + $row->valor_impostos) / $row->qtde_faturamento_item)  ;

                        $valorPreco = ($row->preco + $row->valor_impostos) / $row->qtde_faturamento_item;
                        $valorPreco = number_format($valorPreco, 2, ".", "");

                        $row->aliq_icms = 0;

                    }else{
                        $percentual_icms = (100- $row->aliq_icms) /100; 
                        $preco_icms = $row->preco / $percentual_icms;
                        $valorPreco = number_format($row->preco, 2, ".", "");
                    }

                    if (!$areaAdmin) {
                        echo "
                            <td class='tac' >
                                <input type='hidden' name='nf_obg[pecas][{$row->id}][peca]' value='{$row->peca_id}' />
                                <input type='hidden' name='nf_obg[pecas][{$row->id}][faturamento]' value='{$row->faturamento}' />
                                <input type='hidden' class='base_icms' name='nf_obg[pecas][{$row->id}][base_icms]' />
                                <input type='hidden' class='valor_icms' name='nf_obg[pecas][{$row->id}][valor_icms]' />
                                <input type='hidden' class='base_ipi' name='nf_obg[pecas][{$row->id}][base_ipi]' />
                                <input type='hidden' class='valor_ipi' name='nf_obg[pecas][{$row->id}][valor_ipi]' />
                                <input type='hidden' class='icms' name='nf_obg[pecas][{$row->id}][icms]' value='{$row->aliq_icms}' />
                                <input type='hidden' class='ipi' name='nf_obg[pecas][{$row->id}][ipi]' value='{$row->aliq_ipi}' />
                                <input type='hidden' class='preco' name='nf_obg[pecas][{$row->id}][preco]' value='{$row->preco}' />
                                <input type='hidden' class='preco_icms' name='nf_obg[pecas][{$row->id}][preco_icms]' value='{$preco_icms}' />
                                <input type='hidden' name='nf_obg[pecas][{$row->id}][qtde_real]' value='{$row->qtde}' />
                                <input type='text' class='qtde' name='nf_obg[pecas][{$row->id}][qtde]' maxlength='3' value='".getValue("nf_obg[pecas][{$row->id}][qtde]")."' />
                            </td>
                        ";
					}

                    if ($login_fabrica != 177){
                        echo "<td class='tar preco' >".$valorPreco."</td>";
                        echo "<td class='tar icms' >".number_format($row->aliq_icms, 2, ".", "")."%</td>";
                        echo "<td class='tar icms' >".number_format($preco_icms, 2, ".", "")."</td>";
                        echo "<td class='tar ipi' >".number_format($row->aliq_ipi, 2, ".", "")."%</td>";
                    }
                    
                    if (!$areaAdmin) {
                        if ($login_fabrica != 177){
                            echo "<td class='total tar' ></td>";
                        }
                        echo "</tr>";
                    }else{
						echo "<td class='tar ipi' >".number_format($preco_icms * $row->qtde, 2, ".", "")."</td>";
					}                }

						if (!$areaAdmin) {
                            if ($login_fabrica == 177){
                                $sqlnf = "
                                    SELECT DISTINCT ON(tbl_os_produto.os)
                                        tbl_os_produto.os AS sua_os
                                    FROM tbl_extrato_lgr
                                    INNER JOIN tbl_os_item ON tbl_os_item.os_item = tbl_extrato_lgr.os_item
                                    INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                    WHERE tbl_extrato_lgr.extrato = {$extrato}
                                    ORDER BY tbl_os_produto.os";
                                $resnf = pg_query($con,$sqlnf);
                                $result = pg_fetch_all($resnf);
                                foreach($result as $nfKey => $nfValue) {
                                    $os_relacao[] = $nfValue['sua_os'];
                                }
                                $os_relacao = array_unique(array_filter($os_relacao));
								$observacao = "Referente as Ordens de Serivço: ".implode(", ", $os_relacao).", que segue para troca em garantia com posterior retorno. <br />";
                            }else{
                                $sqlnf = "SELECT DISTINCT tbl_faturamento.nota_fiscal, tbl_os.sua_os 
                                            FROM tbl_os 
                                            INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                                            JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato = tbl_os_extra.extrato
                                            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                                            INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.peca = tbl_extrato_lgr.peca AND tbl_os_item.peca_obrigatoria = tbl_extrato_lgr.devolucao_obrigatoria
                                            INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento_item = tbl_extrato_lgr.faturamento_item AND tbl_faturamento_item.peca = tbl_os_item.peca
                                            INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = {$login_fabrica}
                                            WHERE tbl_os.fabrica = {$login_fabrica}
                                            {$subqueryWherePosto}
                                            AND tbl_os_extra.extrato = $extrato
                                            AND devolucao_obrigatoria ";
                                $resnf = pg_query($con,$sqlnf);
                                $result = pg_fetch_all($resnf);
                                foreach($result as $nfKey => $nfValue) {
                                    $nf_relacao[] = $nfValue['nota_fiscal'];
                                    $os_relacao[] = $nfValue['sua_os'];
                                }
                                $os_relacao = array_unique(array_filter($os_relacao));
                                $nf_relacao = array_unique(array_filter($nf_relacao));

                                $observacao = "
                                    Referente as Ordens de Serivço: ".implode(", ", $os_relacao)."<br />
                                    Referente as Notas Fiscais: ".implode(", ", $nf_relacao)."<br />
                                    ";
                            }
		        }
                ?>
            </tbody>
        </table>
        <?php
        if (!$areaAdmin) {
        ?>
            <table class="table table-bordered" style="table-layout: fixed;" >
                <tbody>
                    <?php if ($login_fabrica != 177){ ?>
                        <tr class="titulo_coluna">
                            <th>Base ICMS</th>
                            <th>Valor ICMS</th>
                            <th>Base IPI</th>
                            <th>Valor IPI</th>
                            <th>Total da Nota Fiscal</th>
                        </tr>
                        <tr>
                            <td class="tar" >
                                <input type="text" class="total_base_icms" name="nf_obg[base_icms]" readonly />
                            </td>
                            <td class="tar" >
                                <input type="text" class="total_valor_icms" name="nf_obg[valor_icms]" readonly />
                            </td>
                            <td class="tar" >
                                <input type="text" class="total_base_ipi" name="nf_obg[base_ipi]" readonly />
                            </td>
                            <td class="tar" >
                                <input type="text" class="total_valor_ipi" name="nf_obg[valor_ipi]" readonly />
                            </td>
                            <td class="tar" >
                                <input type="text" class="total_nf" name="nf_obg[total_nota]" readonly />
                            </td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td class="titulo_coluna" colspan="5" >Observações</td>
                    </tr>
                    <tr>
                       <td colspan="5" >
							<input type="hidden" name="nf_obg[observacao]" value="<?=$observacao?>" />
							<?=$observacao?>
                       </td> 
                    </tr>
                    <tr>
                        <td colspan="5" >
                            <div class="row-fluid">
                                <div class="span3">
                                    <div class='control-group <?=(in_array('nf_obg[nota_fiscal]', $msg_erro['campos'])) ? "error" : "" ?>' >
                                        <label class="control-label" for="nf_obg_nota_fiscal">Nota Fiscal</label>
                                        <div class="controls controls-row">
                                            <div class="span12">
                                                <h5 class="asteristico">*</h5>
                                                <input id="nf_obg_nota_fiscal" name="nf_obg[nota_fiscal]" class="span12" type="text" value="<?=getValue('nf_obg[nota_fiscal]')?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="span2">
                                    <div class='control-group <?=(in_array('nf_obg[data_emissao]', $msg_erro['campos'])) ? "error" : "" ?>' >
                                        <label class="control-label" for="nf_obg_data_emissao">Data de Emissão</label>
                                        <div class="controls controls-row">
                                            <div class="span12">
                                                <h5 class="asteristico">*</h5>
                                                <input id="nf_obg_data_emissao" name="nf_obg[data_emissao]" class="span12" type="text" value="<?=getValue('nf_obg[data_emissao]')?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($login_fabrica != 177){ ?>
                                <div class="span2">
                                    <div class='control-group' >
                                        <label class="control-label" for="nf_obg_n_log">Número de Log</label>
                                        <div class="controls controls-row">
                                            <div class="span12">
                                                <input id="nf_obg_n_log" name="nf_obg[n_log]" class="span12 n_log" type="text" value="<?=getValue('nf_obg[n_log]')?>" maxlength="15" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
								<?php } ?>
                            </div>
                            <?php if ($login_fabrica != 177){ ?>
                                <div class="row-fluid">
                                    <div class="span12">
                                        <div class='control-group' >
                                            <label class="control-label" for="nf_obg_chave">Chave</label>
                                            <div class="controls controls-row">
                                                <div class="span12">
                                                    <input id="nf_obg_chave" name="nf_obg[chave]" class="span12" type="text" value="<?=getValue('nf_obg[chave]')?>" maxlength="44" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
							<?php } ?>

                        </td>
                    </tr>
                    
                </tbody>
            </table>
            <?php if($login_fabrica == 177){                                
                if (strlen($_GET["faturamento"]) > 0) {
                    $tempUniqueId = $_GET["faturamento"];
                    $anexoNoHash = null;
                } else {
                    $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
                    $anexoNoHash = true;
                }
                $boxUploader = array(
                    "div_id" => "div_anexos",
                    "prepend" => $anexo_prepend,
                    "context" => "lgr",
                    "unique_id" => $tempUniqueId,
                    "hash_temp" => $anexoNoHash
                );
                echo "<input type='hidden' name='hashBox' id='hashBox' value='{$tempUniqueId}'/>";
                include "box_uploader.php";
            } 
            ?>
        <?php
        }
        ?>

        <hr />
    <?php
    }

    if (count($devolucao_nao_obrigatoria) > 0) {
    ?>
        <table class="table table-bordered no-margin" >
            <tr class="titulo_coluna" >
                <td>Peças que não são de devolução obrigatória</td>
            </tr>
        </table>

        <?php
        if (!$areaAdmin) {
        ?>
            <table class="table table-bordered no-margin" >
                <tr class="titulo_coluna" >
                    <th>Natureza</th>
                    <th>CFOP</th>
                </tr>
                <tr>
                    <td>Remessa Para Garantia</td>
                    <td><?= getCFOP($posto_estado); ?></td>
                </tr>
            </table>

            <table class="table table-bordered no-margin" >
                <tr class="titulo_coluna" >
                    <th>Razão Social</th>
                    <th>CNPJ</th>
                    <th>Inscrição Estadual</th>
                </tr>
                <tr>
                    <td><?=$posto_razao_social?></td>
                    <td><?=$posto_cnpj?></td>
                    <td><?=$posto_inscricao_estadual?></td>
                </tr>
            </table>

            <table class="table table-bordered no-margin" >
                <tr class="titulo_coluna" >
                    <th>Endereço</th>
                    <th>Cidade</th>
                    <th>Estado</th>
                    <th>CEP</th>
                </tr>
                <tr>
                    <td><?=$posto_endereco?></td>
                    <td><?=$posto_cidade?></td>
                    <td><?=$posto_estado?></td>
                    <td><?=$posto_cep?></td>
                </tr>
            </table>
        <?php
        }
        ?>

        <table class="table table-bordered table-striped no-margin nf" >
            <thead>
                <tr class="titulo_coluna" >
                    <th>Peça</th>
                    <?php if ($login_fabrica != 177){ ?>
                    <th>Nota Fiscal de Origem</th>
                    <?php } ?>
                    <th>Qtde</th>
                    <?php
                    if (!$areaAdmin) {
                    ?>
                        <th>Qtde a ser devolvida</th>
                    <?php
                    }
                    ?>
                    <?php if ($login_fabrica != 177){ ?>
                    <th>Preço</th>
                    <th>ICMS</th>
                    <th>PREÇO C/ICMS</th>
                    <th>IPI</th>
                    <th>Total</th>
                    <?php } ?>
					</tr>
            </thead>
            <tbody>
                <?php
                if (!$areaAdmin) {
                    $os_relacao = array();
                    $nf_relacao = array();

                    $total_base_icms  = 0;
                    $total_valor_icms = 0;
                    $total_base_ipi   = 0;
                    $total_valor_ipi  = 0;
                }

                foreach ($devolucao_nao_obrigatoria as $row) {
                    if (!$areaAdmin) {
                        $os_relacao = array_merge($os_relacao, explode(",", $row->array_os));
                        $nf_relacao = array_merge($nf_relacao, explode(",", $row->array_nota_fiscal));

                        $total_base_icms  += $row->base_icms;
                        $total_valor_icms += $row->valor_icms;
                        $total_base_ipi   += $row->base_ipi;
                        $total_valor_ipi  += $row->valor_ipi;

                        unset($class_erro);

                        if (in_array("peca_{$row->id}", $msg_erro["campos"])) {
                            $class_erro = "class='error'";
                        }
                    }

                    echo "
                        <tr {$class_erro} id='row-{$row->id}' >
                            <td>{$row->peca}</td>
                    ";
                        
                    if ($areaAdmin) {
                        if (empty($row->nota_fiscal_origem)) {
                            echo "
                                <td class='nota-fiscal-origem' >
                                    <button type='button' data-lgr-id='{$row->id}' data-peca='{$row->peca}' data-qtde='{$row->qtde}' class='btn btn-success btn-mini atualizar-peca-lgr' title='Atualizar Informações da Nota Fiscal de Origem' ><i class='icon-repeat icon-white'></i></button>
                                    <button type='button' data-lgr-id='{$row->id}' data-peca='{$row->peca}' data-qtde='{$row->qtde}' class='btn btn-danger btn-mini excluir-peca-lgr' title='Excluir Peça do LGR' ><i class='icon-remove icon-white'></i></button>
                                </td>
                            ";
                        } else {
                            if ($login_fabrica != 177){
                                echo "<td>{$row->nota_fiscal_origem}</td>";
                            }
                        }
                    } else {
                        echo "<td>{$row->nota_fiscal_origem}</td>";
                    }

                    echo "<td class='tac' >{$row->qtde}</td>";

                    $percentual_icms = (100- $row->aliq_icms) /100; 
                    $preco_icms = $row->preco / $percentual_icms;

                    if (!$areaAdmin) {
                        echo "
                            <td class='tac' >
                                <input type='hidden' name='nf_nobg[pecas][{$row->id}][peca]' value='{$row->peca_id}' />
                                <input type='hidden' name='nf_nobg[pecas][{$row->id}][faturamento]' value='{$row->faturamento}' />
                                <input type='hidden' class='base_icms' name='nf_nobg[pecas][{$row->id}][base_icms]' />
                                <input type='hidden' class='valor_icms' name='nf_nobg[pecas][{$row->id}][valor_icms]' />
                                <input type='hidden' class='base_ipi' name='nf_nobg[pecas][{$row->id}][base_ipi]' />
                                <input type='hidden' class='valor_ipi' name='nf_nobg[pecas][{$row->id}][valor_ipi]' />
                                <input type='hidden' class='icms' name='nf_nobg[pecas][{$row->id}][icms]' value='{$row->aliq_icms}' />
                                <input type='hidden' class='ipi' name='nf_nobg[pecas][{$row->id}][ipi]' value='{$row->aliq_ipi}' />
                                <input type='hidden' class='preco' name='nf_nobg[pecas][{$row->id}][preco]' value='{$row->preco}' />
                                <input type='hidden' class='preco_icms' name='nf_nobg[pecas][{$row->id}][preco_icms]' value='{$preco_icms}' />
                                <input type='hidden' name='nf_nobg[pecas][{$row->id}][qtde_real]' value='{$row->qtde}' />
                                <input type='text' class='qtde' name='nf_nobg[pecas][{$row->id}][qtde]' maxlength='3' value='".getValue("nf_nobg[pecas][{$row->id}][qtde]")."' />
                            </td>
                        ";
                    }

					if ($login_fabrica != 177){
                        echo "
                            <td class='tar preco' >".$valorPreco."</td>
                            <td class='tar icms' >".number_format($row->aliq_icms, 2, ".", "")."%</td>
                            <td class='tar icms' >".number_format($preco_icms, 2, ".", "")."</td>
                            <td class='tar ipi' >".number_format($row->aliq_ipi, 2, ".", "")."%</td>
                        ";
                    }
                    if (!$areaAdmin) {
                        if ($login_fabrica != 177){
                            echo "<td class='total tar' ></td>";
                        }
                        echo "</tr>";
                    }else{
						echo "<td class='tar ipi' >".number_format($preco_icms * $row->qtde, 2, ".", "")."</td>";
					}
                }

				if (!$areaAdmin) {
                    if ($login_fabrica == 177){
                        $sqlnf ="
                            SELECT DISTINCT ON(tbl_os_produto.os)
                                tbl_os_produto.os AS sua_os
                            FROM tbl_extrato_lgr
                            INNER JOIN tbl_os_item ON tbl_os_item.os_item = tbl_extrato_lgr.os_item
                            INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                            WHERE tbl_extrato_lgr.extrato = {$extrato}
                            ORDER BY tbl_os_produto.os";
                        $resnf = pg_query($con,$sqlnf);
                        $result = pg_fetch_all($resnf);
                        foreach($result as $nfKey => $nfValue) {
                            $os_relacao[] = $nfValue['sua_os'];
                        }
                        $os_relacao = array_unique(array_filter($os_relacao));
                        
                        $observacao = "Referente as Ordens de Serivço: ".implode(", ", $os_relacao).", que segue para troca em garantia com posterior retorno. <br />";
                    }else{
                        $sqlnf = "SELECT DISTINCT tbl_faturamento.nota_fiscal, tbl_os.sua_os 
                                    FROM tbl_os 
                                    INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                                    JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato = tbl_os_extra.extrato
                                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                                    INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.peca = tbl_extrato_lgr.peca AND tbl_os_item.peca_obrigatoria = tbl_extrato_lgr.devolucao_obrigatoria
                                    INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento_item = tbl_extrato_lgr.faturamento_item AND tbl_faturamento_item.peca = tbl_os_item.peca
                                    INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = {$login_fabrica}
                                    WHERE tbl_os.fabrica = {$login_fabrica}
                                    {$subqueryWherePosto}
                                    AND tbl_os_extra.extrato = $extrato
                                    AND devolucao_obrigatoria is false";
                        $resnf = pg_query($con,$sqlnf);
                        $result = pg_fetch_all($resnf);
                        foreach($result as $nfKey => $nfValue) {
                            $nf_relacao[] = $nfValue['nota_fiscal'];
                            $os_relacao[] = $nfValue['sua_os'];
                        }
                        $os_relacao = array_unique(array_filter($os_relacao));
                        $nf_relacao = array_unique(array_filter($nf_relacao));

                        $observacao = "
                            Referente as Ordens de Serivço: ".implode(", ", $os_relacao)."<br />
                            Referente as Notas Fiscais: ".implode(", ", $nf_relacao)."<br />
                        ";
                    }
				}
                ?>
            </tbody>
        </table>
        <?php
        if (!$areaAdmin) {
        ?>
            <table class="table table-bordered" style="table-layout: fixed;" >
                <tbody>
                    <?php if ($login_fabrica != 177){ ?>
                    <tr class="titulo_coluna">
                        <th>Base ICMS</th>
                        <th>Valor ICMS</th>
                        <th>Base IPI</th>
                        <th>Valor IPI</th>
                        <th>Total da Nota Fiscal</th>
                    </tr>
                    <tr>
                        <td class="tar" >
                            <input type="text" class="total_base_icms" name="nf_nobg[base_icms]" readonly />
                        </td>
                        <td class="tar" >
                            <input type="text" class="total_valor_icms" name="nf_nobg[valor_icms]" readonly />
                        </td>
                        <td class="tar" >
                            <input type="text" class="total_base_ipi" name="nf_nobg[base_ipi]" readonly />
                        </td>
                        <td class="tar" >
                            <input type="text" class="total_valor_ipi" name="nf_nobg[valor_ipi]" readonly />
                        </td>
                        <td class="tar" >
                            <input type="text" class="total_nf" name="nf_nobg[total_nota]" readonly />
                        </td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <td class="titulo_coluna" colspan="5" >Observações</td>
                    </tr>
                    <tr>
                        <td colspan="5" >
                            <input type="hidden" name="nf_nobg[observacao]" value="<?=$observacao?>" />
							<?=$observacao?>
                        </td> 
                    </tr>
                    <tr>
                        <td colspan="5" >
                            <div class="row-fluid">
                                <div class="span3">
                                    <div class='control-group <?=(in_array('nf_nobg[nota_fiscal]', $msg_erro['campos'])) ? "error" : "" ?>' >
                                        <label class="control-label" for="nf_nobg_nota_fiscal">Nota Fiscal</label>
                                        <div class="controls controls-row">
                                            <div class="span12">
                                                <h5 class="asteristico">*</h5>
                                                <input id="nf_nobg_nota_fiscal" name="nf_nobg[nota_fiscal]" class="span12" type="text" value="<?=getValue('nf_nobg[nota_fiscal]')?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="span2">
                                    <div class='control-group <?=(in_array('nf_nobg[data_emissao]', $msg_erro['campos'])) ? "error" : "" ?>' >
                                        <label class="control-label" for="nf_nobg_data_emissao">Data de Emissão</label>
                                        <div class="controls controls-row">
                                            <div class="span12">
                                                <h5 class="asteristico">*</h5>
                                                <input id="nf_nobg_data_emissao" name="nf_nobg[data_emissao]" class="span12" type="text" value="<?=getValue('nf_nobg[data_emissao]')?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($login_fabrica != 177){ ?>
                                <div class="span2">
                                    <div class='control-group' >
                                        <label class="control-label" for="nf_nobg_n_log">Número de Log</label>
                                        <div class="controls controls-row">
                                            <div class="span12">
                                                <input id="nf_nobg_n_log" name="nf_nobg[n_log]" class="span12 n_log" type="text" value="<?=getValue('nf_nobg[n_log]')?>" maxlength="15" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                            <?php if ($login_fabrica != 177){ ?>
                            <div class="row-fluid">
                                <div class="span12">
                                    <div class='control-group' >
                                        <label class="control-label" for="nf_nobg_chave">Chave</label>
                                        <div class="controls controls-row">
                                            <div class="span12">
                                                <input id="nf_nobg_chave" name="nf_nobg[chave]" class="span12" type="text" value="<?=getValue('nf_nobg[chave]')?>" maxlength="44" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <hr />
        <?php
        }
        ?>
    <?php
    }
    ?>

    <?php
    if (!$areaAdmin) {
        if (!$origem_completa AND $login_fabrica != 177) {
        ?>
            <div class="row-fluid">
                <div class="span12">
                    <div class="alert alert-warning" >
                        <h5>Não será possível confirmar a Nota Fiscal de Devolução, há itens que estão sem uma Nota Fiscal de Origem, aguarde a correção da Fábrica para seguir com a Devolução.</h5>
                    </div>
                </div>
            </div>  
        <?php
        } else {
        ?>
            <div class="row-fluid">
                <div class="span12">
                    <div class='control-group' >
                        <label class="control-label" >&nbsp;</label>
                        <div class="controls controls-row">
                            <div class="span12 tac">
                                <button type="button" class="btn btn-success" id="confirmar_notas_fiscais" name="confirmar_notas_fiscais" >Confirmar Notas Fiscais de Devolução</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }
    }
    ?>
</form>

<br />

<style>

input.qtde {
    width: 30px;
}

input.total_nf, input.total_base_icms, input.total_valor_icms, input.total_base_ipi, input.total_valor_ipi {
    width: 70px;
}

.no-margin {
    margin: 0px;
}

</style>

<?php
if (!$areaAdmin) {
?>
    <script>
    Shadowbox.init();

    $(function(){
        var peso_total = 0;
        $(".peso").each(function(){
            peso_total += parseInt($(this).text());
        });
        $(".peso_total").text(peso_total);
    });

    <?php if ($login_fabrica != 177){ ?>
    function calcula_total(table) {
        var total_nf         = 0;
        var total_base_icms  = 0;
        var total_base_ipi   = 0;
        var total_valor_icms = 0;
        var total_valor_ipi  = 0;

        $(table).find("tbody > tr").each(function() {
            //base icms
            var base_icms = parseFloat($(this).find("input.base_icms").val());
            if (!isNaN(base_icms)) {
                total_base_icms += base_icms;
            }

            //valor icms
            var valor_icms = parseFloat($(this).find("input.valor_icms").val());
            if (!isNaN(valor_icms)) {
                total_valor_icms += valor_icms;
            }

            //base ipi
            var base_ipi = parseFloat($(this).find("input.base_ipi").val());
            if (!isNaN(base_ipi)) {
                total_base_ipi += base_ipi;
            }

            //valor ipi
            var valor_ipi = parseFloat($(this).find("input.valor_ipi").val());
            if (!isNaN(valor_ipi)) {
                total_valor_ipi += valor_ipi;
            }

            //total
            var total = parseFloat($(this).find("td.total").text());
            if (!isNaN(total) && total > 0) {
                total_nf += total;
            }
        });

        $(table).next().find("input.total_base_icms").val(total_base_icms.toFixed(2));
        $(table).next().find("input.total_base_ipi").val(total_base_ipi.toFixed(2));
        $(table).next().find("input.total_valor_icms").val(total_valor_icms.toFixed(2));
        $(table).next().find("input.total_valor_ipi").val(total_valor_ipi.toFixed(2));
        $(table).next().find("input.total_nf").val(total_nf.toFixed(2));
    }
    <?php } ?>
    $("#nf_nobg_data_emissao, #nf_obg_data_emissao").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    $("input.qtde, input.n_log").numeric();

    <?php if ($login_fabrica != 177){ ?>
    $("input.qtde").on("keyup", function() {
        var tr       = $(this).parents("tr");
        var qtde     = parseInt($(this).val());
        var td_total = $(this).parent().nextAll("td.total");

        if (isNaN(qtde) || qtde == 0) {
            $(td_total).text("0,00");
            $(tr).find("input.base_icms").val(0);
            $(tr).find("input.valor_icms").val(0);
            $(tr).find("input.base_ipi").val(0);
            $(tr).find("input.valor_ipi").val(0);
            calcula_total($(this).parents("table.nf"));
            return false;
        }

        var preco     = parseFloat($(tr).find("input.preco").val());
        var aliq_icms = parseFloat($(tr).find("input.icms").val());
        var aliq_ipi  = parseFloat($(tr).find("input.ipi").val());
        var icms      = 0;
        var ipi       = 0;

        if (!isNaN(aliq_icms) && aliq_icms > 0) {
            icms = (preco / 100) * parseFloat(aliq_icms);
            $(tr).find("input.base_icms").val(preco);
            var preco_icms = preco / ((100 - aliq_icms) / 100);
            icms = preco_icms - preco;
            preco = preco_icms;

            $(tr).find("input.valor_icms").val(icms);
        } else {
            $(tr).find("input.base_icms").val(preco);
            $(tr).find("input.valor_icms").val(icms);
        }

        if (!isNaN(aliq_ipi) && aliq_ipi > 0) {
            ipi = (preco / 100) * parseFloat(aliq_ipi);
            $(tr).find("input.base_ipi").val(preco);
            preco += ipi;
            $(tr).find("input.valor_ipi").val(ipi);
        } else {
            $(tr).find("input.base_ipi").val(preco);
            $(tr).find("input.valor_ipi").val(ipi);
        }
        
        var total = preco * qtde;

        $(td_total).text(total.toFixed(2));

        calcula_total($(this).parents("table.nf"));
    });
    <?php } ?>
    $("#confirmar_notas_fiscais").on("click", function(e) {
        if ($(this).prop("disabled") == true) {
            e.preventDefault();
            return false;
        }

        $(this).prop({ disabled: true }).text("Confirmando notas fiscais, aguarde...");

        $(this).parents("form").submit();
    });

    <?php
    if ($_POST) {
    ?>
        $("input.qtde").keyup();
    <?php
    }
    ?>

    </script>

<?php
} else {
?>
    <script>

    var extrato = <?=$_GET["extrato"]?>;

    $("button.atualizar-peca-lgr").on("click", function() {
        var lgr_id           = $(this).data("lgr-id");
        var table_row        = $("#row-"+lgr_id);
        var button_atualizar = $(this);
        var button_excluir   = $(button_atualizar).prev();
        var peca             = $(this).data("peca");
        var qtde             = $(this).data("qtde");

        if (confirm("Deseja prosseguir com a atualização da Nota Fiscal de Origem?")) {
            $(button_atualizar).prop({ disabled: true }).find("i").removeClass("icon-repeat").addClass("icon-time");
            $(button_excluir).prop({ disabled: true });

            var p = new Promise(function(resolve, reject) {
                $.ajax({
                    url: window.location,
                    type: "post",
                    data: {
                        ajax_atualizar_peca_lgr: true,
                        extrato: extrato,
                        lgr_id: lgr_id
                    },
                    timeout: 60000
                }).fail(function(r) {
                    reject("Tempo limite esgotado.");
                }).done(function(r) {
                    r = JSON.parse(r);

                    if (r.erro) {
                        reject(r.erro);
                    } else {
                        resolve(r);
                    }
                });
            }).then(
                function(resposta) {
                    $(table_row).addClass("success");
                    $(table_row).find("td.nota-fiscal-origem").text(resposta.dados.nota_fiscal_origem);
                    $(table_row).find("td.preco").text(resposta.dados.preco);
                    $(table_row).find("td.icms").text(resposta.dados.icms+"%");
                    $(table_row).find("td.ipi").text(resposta.dados.ipi+"%");
                },
                function(erro) {
                    alert("\
                        Erro ao atualizar Nota Fiscal de Origem\n\
                        Peça "+peca+" Quantidade "+qtde+"\n\
                        "+erro+"\
                    ");
                    $(button_atualizar).prop({ disabled: false }).find("i").removeClass("icon-time").addClass("icon-repeat");
                    $(button_excluir).prop({ disabled: false });
                }
            );
        }
    });

    $("button.excluir-peca-lgr").on("click", function() {
        var lgr_id           = $(this).data("lgr-id");
        var peca             = $(this).data("peca");
        var qtde             = $(this).data("qtde");
        var table_row        = $("#row-"+lgr_id);
        var button_excluir   = $(this);
        var button_atualizar = $(button_excluir).prev();

        if (confirm("Deseja realmente excluir a peça do LGR ?\n Essa ação é irreversível.")) {
            $(button_excluir).prop({ disabled: true }).find("i").removeClass("icon-remove").addClass("icon-time");
            $(button_atualizar).prop({ disabled: true });

            var p = new Promise(function(resolve, reject) {
                $.ajax({
                    url: window.location,
                    type: "post",
                    data: {
                        ajax_excluir_peca_lgr: true,
                        extrato: extrato,
                        lgr_id: lgr_id
                    },
                    timeout: 60000
                }).fail(function(r) {
                    reject("Tempo limite esgotado.");
                }).done(function(r) {
                    r = JSON.parse(r);

                    if (r.erro) {
                        reject(r.erro);
                    } else {
                        resolve(r);
                    }
                });
            }).then(
                function(resposta) {
                    $(table_row).addClass("error").html("<td colspan='6'>Peça "+peca+" Quantidade "+qtde+", excluída com sucesso.</td>");
                },
                function(erro) {
                    alert("\
                        Erro ao excluir Peça do LGR\n\
                        Peça "+peca+" Quantidade "+qtde+"\n\
                        "+erro+"\
                    ");
                    $(button_excluir).prop({ disabled: false }).find("i").removeClass("icon-time").addClass("icon-remove");
                    $(button_atualizar).prop({ disabled: false });
                }
            );
        }
    });

    </script>
<?php
}

include "rodape.php";

?>
