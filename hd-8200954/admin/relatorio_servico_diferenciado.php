<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE BONIFICAÇÃO";

if (filter_input(INPUT_POST,"btn_acao")) {
    $data_inicial   = filter_input(INPUT_POST,"data_inicial");
    $data_final     = filter_input(INPUT_POST,"data_final");
    $resolvido      = filter_input(INPUT_POST,"resolvido");

    if (strlen($data_inicial) > 0) {
        $xdata_inicial =  formata_data(trim($data_inicial));
    } else {
        $msg_erro["msg"][]    ="Data Inválida";
        $msg_erro["campos"][] = "data";
    }

    if (strlen($data_final) > 0) {
        $xdata_final =  formata_data(trim($data_final));
    } else {
        $msg_erro["msg"][]    ="Data Inválida";
        $msg_erro["campos"][] = "data";
    }

    list($di, $mi, $yi) = explode("/", $data_inicial);
    list($df, $mf, $yf) = explode("/", $data_final);

    if( !checkdate($mf,$df,$yf) || !checkdate($mi,$di,$yi) ) {

        $msg_erro["msg"][] = 'Data Inválida';
        $msg_erro["campos"][] = "datal";

    }

    if (strtotime($xdata_inicial.'+ 3 months') < strtotime($xdata_final) && count($msg_erro) == 0) {
        $msg_erro["msg"][]      = 'O intervalo entre as datas não pode ser maior que 3 meses';
        $msg_erro["campos"][]   = "data";
    }

    if ($resolvido == 't') {
        $sqlResolvido = " AND tbl_hd_chamado.status = 'Resolvido'";
    }

    $sqlResp = "
        SELECT  tbl_hd_chamado.hd_chamado,
                tbl_os.os,
                tbl_os.sua_os,
                TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY')   AS data_abertura,
                TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')     AS data_fechamento,
                tbl_produto.referencia                      AS produto_referencia,
                tbl_produto.descricao                       AS produto_descricao,
                tbl_os.serie,
                tbl_posto.nome                              AS posto_nome,
                tbl_posto.nome_fantasia                     AS posto_nome_fantasia,
                tbl_posto.cidade                            AS posto_cidade,
                tbl_posto.estado                            AS posto_estado,
                tbl_posto_fabrica.codigo_posto              AS posto_codigo,
                tbl_hd_chamado_extra.array_campos_adicionais
        FROM    tbl_hd_chamado
        JOIN    tbl_hd_chamado_extra    USING(hd_chamado)
        JOIN    tbl_os                  USING(os)
        JOIN    tbl_produto             ON  tbl_produto.produto         = tbl_os.produto
        JOIN    tbl_posto               ON  tbl_posto.posto             = tbl_os.posto
                                        AND tbl_posto.posto             = tbl_hd_chamado_extra.posto
        JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                        AND tbl_posto_fabrica.fabrica   = $login_fabrica
        WHERE   tbl_hd_chamado.fabrica  = $login_fabrica
        AND     tbl_os.fabrica          = $login_fabrica
        AND     JSON_FIELD('bonificacao',tbl_hd_chamado_extra.array_campos_adicionais) = 'true'
        AND     tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
        $sqlResolvido
    ";
    $resResp = pg_query($con,$sqlResp);
}


include "cabecalho_new.php";

$plugins = array(
    "datepicker",
    "datatable",
    "mask"
);

include ("plugin_loader.php");
?>
<script type="text/javascript">
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
});
</script>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
    <div class="titulo_tabela">Parâmetros de Pesquisa</div>
    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group'>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <input type="checkbox" name="resolvido" id="resolvido" class='span1' value= "t" <?=($resolvido == 't')? "checked='checked'" : ""?>>
                        Resolvido
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <p>
        <br/>
        <input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
    </p>
    <br/>
</form>
<br />
</div>
<?php

if (filter_input(INPUT_POST,"btn_acao")) {
    if (pg_num_rows($resResp) > 0) {
        $data = date("d-m-Y-H:i");
        $fileName = "relatorio_servico_diferenciado-{$data}.xls";

        $file = fopen("/tmp/{$fileName}", "w");
        $thead = "
            <table border = '1'>
                <thead>
                    <tr>
                        <th>Protocolo</th>
                        <th>OS</th>
                        <th>Abertura Protocolo</th>
                        <th>Encerramento</th>
                        <th>Produto</th>
                        <th>Série</th>
                        <th>Posto</th>
                        <th nowrap>Defeitos / Soluções</th>
                        <th>Peças</th>
                    </tr>
                </thead>
                <tbody>
        ";
        fwrite($file, $thead);
?>
<table id="relatorio_servico_diferenciado" class='table table-striped table-bordered table-hover table-large' >
    <thead>
        <TR class='titulo_coluna'>
            <th>Protocolo</th>
            <th>OS</th>
            <th>Abertura Protocolo</th>
            <th>Encerramento</th>
            <th>Produto</th>
            <th>Série</th>
            <th>Posto</th>
            <th>Nome Fantasia</th>
            <th>Cidade</th>
            <th>UF</th>
            <th nowrap>Defeitos / Soluções</th>
            <th>Peças</th>
        </tr>
    </thead>
    <tbody>
<?php
        while ($results = pg_fetch_object($resResp)) {
            $tbody .= "
                <tr>
                    <td>".$results->hd_chamado."</td>
                    <td>".$results->sua_os."</td>
                    <td>".$results->data_abertura."</td>
                    <td>".$results->data_fechamento."</td>
                    <td>".$results->produto_referencia." - ".$results->produto_descricao."</td>
                    <td>".$results->serie."</td>
                    <td>".$results->posto_codigo." - ".$results->posto_nome."</td>
                    <td>".$results->posto_nome_fantasia."</td>
                    <td>".$results->posto_cidade."</td>
                    <td>".$results->posto_estado."</td>
            ";
?>
            <tr>
                <td class="tac">
                    <a href="callcenter_interativo_new.php?callcenter=<?=$results->hd_chamado?>" target="_blank"><?=$results->hd_chamado?></a>
                </td>
                <td class="tac">
                    <a href="os_press.php?os=<?=$results->os?>" target="_blank"><?=$results->sua_os?></a>
                </td>
                <td class="tar"><?=$results->data_abertura?></td>
                <td class="tar"><?=$results->data_fechamento?></td>
                <td nowrap><?=$results->produto_referencia." - ".$results->produto_descricao?></td>
                <td><?=$results->serie?></td>
                <td nowrap><?=$results->posto_codigo." - ".$results->posto_nome?></td>
                <td nowrap><?=$results->posto_nome_fantasia?></td>
                <td nowrap><?=$results->posto_cidade?></td>
                <td><?=$results->posto_estado?></td>
                <td nowrap>
                    <ul>
<?php
            $sql_cons = "
                SELECT  tbl_defeito_constatado.defeito_constatado,
                        tbl_defeito_constatado.descricao         ,
                        tbl_defeito_constatado.codigo,
                        tbl_solucao.solucao,
                        tbl_solucao.descricao as solucao_descricao
                FROM    tbl_os_defeito_reclamado_constatado
                JOIN    tbl_defeito_constatado  USING(defeito_constatado)
           LEFT JOIN    tbl_solucao             USING(solucao)
                WHERE   os = ".$results->os;

            $res_cons = pg_query($con,$sql_cons);

            $tbody .= "
                <td>
                    <ul>
            ";
            while ($defeitos = pg_fetch_object($res_cons)) {
                $tbody .= "<li>".$defeitos->descricao." / ".$defeitos->solucao_descricao."</li>";
?>
                        <li><?=$defeitos->descricao?> / <?=$defeitos->solucao_descricao?></li>
<?php
            }

            $tbody .= "
                    </ul>
                </td>
            ";
?>

                    </ul>
                </td>
                <td nowrap>
                    <ul>
<?php
            $sqlPecas = "
                SELECT  tbl_peca.referencia,
                        tbl_peca.descricao
                FROM    tbl_peca
                JOIN    tbl_os_item     USING(peca)
                JOIN    tbl_os_produto  USING(os_produto)
                JOIN    tbl_os          USING(os)
                WHERE   tbl_os.os = ".$results->os;
            $resPeca = pg_query($con,$sqlPecas);

            $tbody .= "
                <td>
                    <ul>
            ";
            while ($pecas = pg_fetch_object($resPeca)) {
                $tbody .= "<li>".$pecas->referencia. " - ".$pecas->descricao."</li>";
?>
                        <li><?=$pecas->referencia. " - ".$pecas->descricao?></li>
<?
            }
             $tbody .= "
                    </ul>
                </td>
            ";
?>
                    </ul>
                </td>
            </tr>
<?php
        }
        $tbody .= "
            </tbody>
        </table>
        ";
        fwrite($file, $tbody);

        fclose($file);

        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");
        }
?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="100%">
                Total: <?=pg_num_rows($resResp)?> Resultados
            </td>
        </tr>
    </tfoot>
</table>
<?php
?>
    <center>
    <a class="btn btn-success" role="button" href="xls/<?=$fileName?>" target="_blank">
        <img src='imagens/excel.png' style="width: 20px;height: 20px;border: 0px;vertical-align: middle;" />
        Gerar Arquivo Excel
    </a>
    </center>
<?php
    } else {
?>
<h4 class="alert alert-warning">Nenhum Resultado Encontrado</h4>
<?php
    }
}

include "rodape.php";
?>
