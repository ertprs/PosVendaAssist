<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

/*ini_set("display_errors", 1);
error_reporting(E_ALL);*/

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';


$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
    $data_inicial = $_POST['data_inicial'];
    $data_final   = $_POST['data_final'];
    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    $numero_devolucao  = $_POST['numero_devolucao'];
    $numero_serie  = $_POST['numero_serie'];
    $nota_fiscal  = $_POST['nota_fiscal'];
    $nome_cliente  = $_POST['nome_cliente'];
    $cpf_cnpj  = $_POST['cpf_cnpj'];
    $chk = $_POST['chk_opt'];

if ($chk == "1") {
    // data do dia
    $sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
    $resX = pg_exec ($con,$sqlX);
    $dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
    $dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

    $sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
    $resX = pg_exec ($con,$sqlX);

    $cond_chk_periodo =" AND (tbl_os_laudo.data_digitacao BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";

}

if ($chk == "2") {
    // dia anterior
    $sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
    $resX = pg_exec ($con,$sqlX);
    $dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
    $dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

    $cond_chk_periodo =" AND (tbl_os_laudo.data_digitacao BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";

}

if ($chk == "3") {
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

    $cond_chk_periodo =" AND (tbl_os_laudo.data_digitacao BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";

}

if ($chk == "4") {
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

    $cond_chk_periodo = " AND (tbl_os_laudo.data_digitacao BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";

}

if ($chk == "5")
{
    $mes_inicial = trim(date("Y")."-".date("m")."-01");
    $mes_final   = trim(date("Y")."-".date("m")."-".date("d"));
    $cond_chk_periodo = " AND (tbl_os_laudo.data_digitacao BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";

}

    if ((empty($data_inicial) || empty($data_final)) && (empty($chk)) && empty($numero_devolucao)) {
        $msg_erro['msg'] ="Selecione um período ou informe o número da devolução";
        $msg_erro['campos'][] = "periodo";
    }

    if(count($msg_erro)==0){
        $xdata_inicial = dateFormat($data_inicial, 'dmy', 'y-m-d');
        $xdata_final   = dateFormat($data_final,   'dmy', 'y-m-d');

        $xdata_inicial .= " 00:00:00";
        $xdata_final .= " 23:59:59";
    }

    if(strlen($produto_referencia)>0){
        $sql = "SELECT produto
                FROM tbl_produto
                WHERE referencia='$produto_referencia'
                AND fabrica_i = $login_fabrica
                LIMIT 1";
        $res = pg_exec($con,$sql);
        if(pg_numrows($res)>0){
            $produto = pg_result($res,0,'produto');
            $cond_referencia = "AND tbl_os_laudo.produto = $produto ";
        } else {
            $msg_erro['msg'] ="Produto não encontrado";
            $msg_erro['campos'][] = "produto_referencia";
        }
    }

    if (!empty($data_inicial) && !empty($data_final)) {
        $cond_periodo = "AND (tbl_os_laudo.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final') ";
    }

    if(!empty($numero_devolucao)){
        $cond_devolucao = "AND tbl_os_laudo.os_laudo = {$numero_devolucao} ";
    }

    if(!empty($numero_serie)){
        $cond_serie = "AND tbl_os_laudo.serie = '{$numero_serie}' ";
    }

    if(!empty($nome_cliente)){
        $cond_cliente = "AND tbl_os_laudo.nome_cliente ilike '%$nome_cliente%' ";
    }

    if(!empty($cpf_cnpj)){
        $cond_cnpj_cpf = "AND tbl_os_laudo.cpf_cliente = '{$cpf_cnpj}' ";
    }

    if(!empty($nota_fiscal)){
        $cond_nota = "AND tbl_os_laudo.nota_fical = '{$nota_fiscal}' ";
    }

    if (count($msg_erro)==0) {

    $sql_laudo = "
            SELECT  os_laudo,
                    to_char(data_digitacao, 'DD/MM/YYYY HH24:MI') AS data_digitacao,
                    nome_cliente,
                    tbl_produto.referencia,
                    tbl_produto.descricao,
                    tbl_analise_produto.descricao as descricao_analise_produto,
                    to_char(tbl_os_laudo.data_recebimento, 'DD/MM/YYYY HH24:MI') AS data_recebimento,
                    tbl_os_laudo.nome_cliente,
                    tbl_os_laudo.nota_fiscal,
                    tbl_os_laudo.serie,
                    tbl_defeito_constatado.descricao as descricao_defeito_constatado,
                    tbl_motivo_analitico.descricao as motivo_analitico_descricao,
                    tbl_motivo_sintetico.descricao as motivo_sintetico_descricao
            FROM    tbl_os_laudo
            JOIN    tbl_produto ON tbl_produto.produto = tbl_os_laudo.produto
            left join    tbl_analise_produto on tbl_analise_produto.analise_produto = tbl_os_laudo.analise_produto 
            left join tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os_laudo.defeito_constatado and tbl_defeito_constatado.fabrica = $login_fabrica 
            join tbl_motivo_analitico on tbl_motivo_analitico.motivo_analitico = tbl_os_laudo.motivo_analitico
            join tbl_motivo_sintetico on tbl_motivo_sintetico.motivo_sintetico = tbl_os_laudo.motivo_sintetico
            WHERE   tbl_os_laudo.fabrica = $login_fabrica
            $cond_periodo
            $cond_devolucao
            $cond_serie
            $cond_cliente
            $cond_cnpj_cpf
            $cond_referencia
            $cond_nota
            $cond_chk_periodo
      ORDER BY      data_digitacao";

    $res_laudo = pg_query($con, $sql_laudo);

    }

}
$layout_menu = "callcenter";
$title = "CONSULTA DE DEVOLUÇÕES";

include "cabecalho_new.php";

$plugins = array(
    "mask",
    "datepicker",
    "shadowbox",
    "dataTable",
    "autocomplete",
    "multiselect"
 );

include "plugin_loader.php";
?>
<script type="text/javascript" charset="utf-8">
    $(function(){

        $(".data").datepicker().mask("99/99/9999");

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

    });

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }
</script>

    <? if(count($msg_erro)>0){ ?>
        <div class='alert alert-danger'><h4><? echo $msg_erro['msg']; ?></h4></div>
    <? } ?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<FORM class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
        <div class="titulo_tabela">Parâmetros de Pesquisa</div>
        <br />
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for=''>Data Inicial</label>
                        <div class='controls controls-row'>
                            <h5 class='asteristico'>*</h5>
                            <input class="span4 data" type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<?= $_POST['data_inicial'] ?>">
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for=''>Data Final</label>
                        <div class='controls controls-row'>
                            <h5 class='asteristico'>*</h5>
                            <input class="span4 data" type="text" name="data_final" id="data_final" size="12" maxlength="10" value="<?= $_POST['data_final'] ?>">
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
            <div class="row-fluid">
            <div class="span2"></div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for='produto_referencia'>Ref. Produto</label>
                        <div class='controls-row input-append'>
                            <input type="text" id="produto_referencia" name="produto_referencia" size="12" maxlength="20" value="<?= $_POST['produto_referencia'] ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for='produto_descricao'>Descrição Produto</label>
                        <div class='controls-row input-append'>
                            <input type="text" id="produto_descricao" name="produto_descricao" size="30" value="<?= $_POST['produto_descricao'] ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
            <div class="span2"></div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for='numero_devolucao'>Número Devolução</label>
                        <div class='controls-row'>
                            <input type="text" id="numero_devolucao" name="numero_devolucao" size="12" maxlength="20" value="<?= $_POST['numero_devolucao'] ?>" >
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for='serie'>Número de Série</label>
                        <div class='controls-row'>
                            <input type="text" id="numero_serie" name="numero_serie" size="30" value="<?= $_POST['numero_serie'] ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
            <div class="span2"></div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for='nota_fiscal'>Número da Nota Fiscal</label>
                        <div class='controls-row'>
                            <input type="text" id="nota_fiscal" name="nota_fiscal" size="12" maxlength="20" value="<?= $_POST['nota_fiscal'] ?>" >
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for='nome_cliente'>Nome do Cliente</label>
                        <div class='controls-row'>
                            <input type="text" id="nome_cliente" name="nome_cliente" size="30" value="<?= $_POST['nome_cliente'] ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
            <div class="span2"></div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for='nota_fiscal'>CPF/CNPJ do Cliente</label>
                        <div class='controls-row'>
                            <input type="text" id="cpf_cnpj" name="cpf_cnpj" size="12" maxlength="20" value="<?= $_POST['cpf_cnpj'] ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                    <div class="span4">
                        <label class="radio">
                                <INPUT TYPE="radio" class="checkbox_periodo" NAME="chk_opt" value="1" id='chk_opt1' rel="1" <?= $required ?> <?= ($_POST["chk_opt"] == "1") ? "checked" : ""; ?>>Devoluções Lançadas Hoje
                        </label>
                    </div>
                    <div class="span4">
                        <label class="radio">
                                <INPUT TYPE="radio" class="checkbox_periodo" NAME="chk_opt" value="2" rel="2" <?= $required ?> <?= ($_POST["chk_opt"] == "2") ? "checked" : ""; ?>>Devoluções Lançadas Ontem
                        </label>
                    </div>
                <div class="span2"></div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                    <div class="span4">
                        <label class="radio">
                                <INPUT TYPE="radio" class="checkbox_periodo" NAME="chk_opt" value="3" rel="3" <?= $required ?> <?= ($_POST["chk_opt"] == "3") ? "checked" : ""; ?>>Devoluções Lançadas Nesta Semana
                        </label>
                    </div>
                    <div class="span4">
                        <label class="radio">
                                <INPUT TYPE="radio" class="checkbox_periodo" NAME="chk_opt" value="4" rel="4" <?= $required ?> <?= ($_POST["chk_opt"] == "4") ? "checked" : ""; ?>>Devoluções Lançadas Na Semana Anterior
                        </label>
                    </div>
                <div class="span2"></div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                    <div class="span4">
                        <label class="radio">
                                <INPUT TYPE="radio" class="checkbox_periodo" NAME="chk_opt" value="5" rel="5" <?= $required ?> <?= ($_POST["chk_opt"] == "5") ? "checked" : ""; ?>>Devoluções Lançadas Neste Mês
                        </label>
                    </div>
                <div class="span2"></div>
            </div>
            <br />
            <div class="row-fluid">
                <div class="span5"></div>
                <div class="span2">
                    <input class="btn" type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
                </div>
                <div class="span5"></div>
            </div>
</FORM>
<br />
<?php
if (!empty($_POST['btn_acao'])) {
    if (pg_num_rows($res_laudo) > 0) {
                ?>
                   <table id="devolucoes" class="table table-bordered table-hover table-striped table-large">
                    <thead>
                        <tr class="titulo_tabela">
                            <th colspan="<?=($login_fabrica == 24) ? 6 : 5?>">Lista de Devoluções</th>
                        </tr>
                        <tr class="titulo_coluna">
                            <th>Nº da Devolução</th>
                            <th>Data de Digitação</th>
                            <th>Cliente</th>
                            <th>Referência Produto</th>
                            <th>Descrição Produto</th>
<?php
        if ($login_fabrica == 24 AND $areaAdmin !== true) {
?>
                            <th>Ações</th>
<?php
        }
?>
                        </tr>
                    </thead>
                    <tbody>
<?php
        for ($i=0;$i < pg_num_rows($res_laudo);$i++) {
            $os_laudo           = pg_fetch_result($res_laudo, $i, "os_laudo");
            $data_digitacao     = pg_fetch_result($res_laudo, $i, "data_digitacao");
            $cliente            = pg_fetch_result($res_laudo, $i, "nome_cliente");
            $produto_referencia = pg_fetch_result($res_laudo, $i, "referencia");
            $produto_descricao  = pg_fetch_result($res_laudo, $i, "descricao");
?>
                <tr>
                    <td class="tac"><a href="informacao_devolucao.php?os_laudo=<?= $os_laudo ?>&consulta=true" target="_blank"><?= $os_laudo ?></a></td>
                    <td class="tac"><?= $data_digitacao ?></td>
                    <td><?= $cliente  ?></td>
                    <td class="tac"><?= $produto_referencia ?></td>
                    <td><?= $produto_descricao ?></td>
<?php
        if ($login_fabrica == 24 AND $areaAdmin !== true) {
?>
                            <td>
                                <a target="_blank" class="btn btn-info btn-small" role="button" href="devolucao_cadastro.php?os_laudo=<?=$os_laudo?>">Lançar Itens</a>
                            </td>
<?php
        }
?>
                </tr>
<?php
        }
?>
        </tbody>
    </table>
<?php
        flush();

        $xlsdata = date ("d/m/Y H:i:s");

        $fp = fopen ("xls/consulta-devolucoes-$login_fabrica.csv","w");

        fputs ($fp,"Lista de Devoluções\n");

        $cabecalho = array();

        $cabecalho[] = "Nº Devolução";
        $cabecalho[] = "Data de Digitação";
        $cabecalho[] = "Nome do Cliente";
        $cabecalho[] = "Produto";
        $cabecalho[] = "Análise do Produto";

        $cabecalho[] = "Data Recebimento";
        $cabecalho[] = "Nota Fiscal";
        $cabecalho[] = "Motivo Sintético";
        $cabecalho[] = "Motivo Analítico";
        $cabecalho[] = "Número de Série";
        $cabecalho[] = "Defeito Constatado";
        $cabecalho[] = "Peça";
        $cabecalho[] = "Qtde";
        $cabecalho[] = "Defeito";
        $cabecalho[] = "Serviço Realizado";

        fputs ($fp, implode(";", $cabecalho)."\n");

        for ($i = 0; $i < pg_num_rows($res_laudo); $i++){
            $os_laudo               = pg_fetch_result($res_laudo, $i, "os_laudo");
            $data_digitacao     = pg_fetch_result($res_laudo, $i, "data_digitacao");
            $cliente      = pg_fetch_result($res_laudo, $i, "nome_cliente");
            $produto_referencia       = pg_fetch_result($res_laudo, $i, "referencia");
            $produto_descricao        = pg_fetch_result($res_laudo, $i, "descricao");
            $descricao_analise_produto = pg_fetch_result($res_laudo, $i, 'descricao_analise_produto');
            $nome_cliente = pg_fetch_result($res_laudo, $i, 'nome_cliente');
            $data_recebimento = substr(pg_fetch_result($res_laudo, $i, 'data_recebimento'), 0, 10);
            $nota_fiscal = pg_fetch_result($res_laudo, $i, 'nota_fiscal');
            $serie = pg_fetch_result($res_laudo, $i, 'serie');
            $descricao_defeito_constatado = pg_fetch_result($res_laudo, $i, 'descricao_defeito_constatado');
            $motivo_analitico_descricao = pg_fetch_result($res_laudo, $i, 'motivo_analitico_descricao');
            $motivo_sintetico_descricao =pg_fetch_result($res_laudo, $i, 'motivo_sintetico_descricao');

            $linha = array();

            $body .= "$os_laudo;$data_digitacao;$cliente;$produto_referencia - $produto_descricao;$descricao_analise_produto;$data_recebimento;$nota_fiscal;$motivo_sintetico_descricao;$motivo_analitico_descricao;$serie;$descricao_defeito_constatado;";

            
            $sqlPecas = "SELECT  tbl_servico_realizado.descricao as servico_realizado_descricao, tbl_defeito.descricao as defeito_descricao, os_laudo_peca, tbl_peca.referencia, tbl_peca.descricao, qtde 
                        FROM tbl_os_laudo_peca 
                        JOIN tbl_peca ON tbl_peca.peca = tbl_os_laudo_peca.peca and tbl_peca.fabrica = $login_fabrica
                        left JOIN tbl_defeito on tbl_os_laudo_peca.defeito = tbl_defeito.defeito 
                        left JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_laudo_peca.servico_realizado
                        WHERE os_laudo = $os_laudo                       ";

            $resPeca = pg_query($con, $sqlPecas);

            if(pg_num_rows($resPeca)==0){
                $body .= "\r\n";
            }
            for($a = 0; $a<pg_num_rows($resPeca); $a++){
                $os_laudo_peca = pg_fetch_result($resPeca, $a, os_laudo_peca);
                $referencia_peca = pg_fetch_result($resPeca, $a, referencia);
                $descricao_peca = str_replace(",",".", pg_fetch_result($resPeca, $a, descricao));
                $qtde_peca = pg_fetch_result($resPeca, $a, qtde);
                $defeito_descricao = pg_fetch_result($resPeca, $a, defeito_descricao);
                $servico_realizado_descricao = pg_fetch_result($resPeca, $a, servico_realizado_descricao);

                if($a == 0){
                    $body .= "$referencia_peca - $descricao_peca;$qtde_peca;$defeito_descricao;$servico_realizado_descricao; \r\n";    
                }else{
                    $body .= ";;;;;;;;;;;$referencia_peca - $descricao_peca;$qtde_peca;$defeito_descricao;$servico_realizado_descricao; \r\n";
                }                
            }            
        }

        fputs($fp, $body);
        fclose ($fp);

        $data = date("Y-m-d").".".date("H-i-s");

        rename("xls/consulta-devolucoes-$login_fabrica.csv", "xls/consulta-devolucoes-$login_fabrica.$data.csv");

            echo "<br />
                <table width='300' border='0' cellspacing='2' cellpadding='2' align='center' style='cursor: pointer; font-size: 12px;'>
                    <tr>
                        <td align='left' valign='absmiddle'>
                            <a href='xls/consulta-devolucoes-$login_fabrica.$data.csv' target='_blank'>
                                <img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>    Download do Arquivo CSV
                            </a>
                        </td>
                    </tr>
                </table>
            ";

     } else { ?>
        <div class="alert alert-warning"><h4>Nenhum resultado encontrado</h4></div>
    <?
    }
}
?>
<script>$.dataTableLoad({ table: "#devolucoes" });</script>
<p>

<? include "rodape.php" ?>
