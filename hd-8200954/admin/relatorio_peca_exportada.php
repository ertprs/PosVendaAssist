<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

####### HD 24493 - Francisco Ambrozio #######################################################
#		Filtros:																			#
#			- período (exportação do pedido); * obrigatório									#
#			- posto; * não obrigatório														#
#																							#
#		Pesquisar todos os pedidos exportados no período, destes pedidos, agrupar as peças	#
#			e suas quantidades, mostrando o seguinte resultado: peça, quantidade;			#
#																							#
#		O código da peça deve ser gerado como um link, e ao ser clicado deve ser aberta		#
#			uma nova janela mostrando:														#
#			- pedido, código do posto, nome do posto, data de exportado, peça, qtde;		#
#																							#
#		Estes dados devem ser baseados nos parâmetros iniciais de pesquisa.					#
####### 11/07/2008 ##########################################################################

# Apenas para Suggar
if ($login_fabrica<>24){
	include("acesso_restrito.php");
	exit;
}



# Verificar campos obrigatórios
if ($_POST["btn_acao"] == "submit") {

    $data_inicial       = filter_input(INPUT_POST,'data_inicial');
    $data_final         = filter_input(INPUT_POST,'data_final');
    $codigo_posto       = filter_input(INPUT_POST,'codigo_posto');
    $descricao_posto    = filter_input(INPUT_POST,'descricao_posto');

    if (strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa") {
        $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
        $xdata_inicial = str_replace("'","",$xdata_inicial);
    } else {
        $msg_erro["msg"][]    ="Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data_inicial";
    }

    if (strlen($data_final)>0 and $data_final <> "dd/mm/aaaa") {
        $xdata_final =  fnc_formata_data_pg(trim($data_final));
        $xdata_final = str_replace("'","",$xdata_final);
    } else {
        $msg_erro["msg"][]    ="Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data_final";
    }

    if(!count($msg_erro["msg"])){
        $dat = explode ("/", $data_inicial );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];

        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data";
        }
    }
    if(!count($msg_erro["msg"])){
        $dat = explode ("/", $data_final );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data";
        }
    }

    if($xdata_inicial > $xdata_final) {
        $msg_erro["msg"][]    ="Data Inicial maior que final";
        $msg_erro["campos"][] = "data";
    }

    $sql_data_intervalo = "SELECT ('$xdata_final'::date - interval '31 days' > '$xdata_inicial'::date)::BOOLEAN AS extrapola";
    $res = pg_query($con,$sql_data_intervalo);
    $extrapola = pg_fetch_result($res,0,extrapola);
// echo $sql_data_intervalo;
    if ($extrapola == 't') {
        $msg_erro["msg"][]    ="Intervalo deve ser menor que 31 dias";
        $msg_erro["campos"][] = "data";
    }

    if (count($msg_erro) == 0) {
        if (!empty($codigo_posto)) {
            $sqlPosto = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
            $resPosto = pg_query($con,$sqlPosto);
            $posto = pg_fetch_result($resPosto,0,posto);

            if (!empty($posto)) {
                $condPosto = " AND tbl_pedido.posto = $posto\n";
            }
        }
        $sqlRes = "
            SELECT  tbl_peca.peca  ,
                    tbl_peca.referencia AS peca_referencia,
                    tbl_peca.descricao  AS peca_descricao,
                    SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) AS qtde
            FROM    tbl_pedido
            JOIN    tbl_pedido_item USING (pedido)
            JOIN    tbl_peca        USING (peca)
            WHERE   tbl_pedido.fabrica          =$login_fabrica
            AND     tbl_pedido.exportado        BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:59'
            AND     tbl_pedido.status_pedido    NOT IN (14)
            $condPosto
            AND     tbl_pedido_item.qtde > tbl_pedido_item.qtde_cancelada
      GROUP BY      tbl_peca.peca,
                    tbl_peca.referencia,
                    tbl_peca.descricao
      ORDER BY      tbl_peca.descricao";
        $resRes = pg_query($con,$sqlRes);
    }
}

# Layout página e estilo
$layout_menu = "gerencia";
$title = "RELATÓRIO DE PEÇAS EXPORTADAS";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");
?>

<!-- Pesquisa posto -->
<script type="text/javascript">
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });
});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

function abreDetalhe(data_inicial,data_final,codigo_posto,peca_referencia)
{
    Shadowbox.open({
        content: "relatorio_peca_exportada_detalhe.php?data_inicial="+data_inicial+"&data_final="+data_final+"&codigo_posto="+codigo_posto+"&peca_referencia="+peca_referencia,
        player: "iframe",
        height: 600,
        width: 800
    })

}
</script>
<style type="text/css">
    .shadowbox {
        cursor:pointer;
        text-decoration:none;
    }
</style>
<!-- Fim pesquisa posto -->

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", array_unique($msg_erro["msg"]))?></h4>
    </div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<!-- Formulário para pesquisa -->

<form name="frm_pexporta" method="post" action="<?echo $PHP_SELF?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
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
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
<!-- Finaliza formulário pesquisa -->

<?php

# Só vai pesquisar se estiver tudo OK
if (strlen($btn_acao) > 0 and count($msg_erro) == 0) {
    if (pg_num_rows($resRes) > 0){
        $fp = fopen ("/tmp/relatorio_peca_exportada-$login_fabrica.html","w");

        fputs($fp,"<table id='relatorio_peca_exportada' class='table table-striped table-bordered table-hover table-fixed' >");
        fputs($fp,"<thead>");


        fputs($fp,"    <tr>");
        if (!empty($codigo_posto)) {
            fputs($fp,"<th colspan='2'>Relatório de exportação de peças de $data_inicial a $data_final<br />do posto $codigo_posto - $descricao_posto</th>");
            fputs($fp,"</tr>");
            fputs($fp,"<tr>");
        }
        fputs($fp,"        <th>Peça</th>");
        fputs($fp,"        <th>Qtde</th>");
        fputs($fp,"    </tr>");
        fputs($fp,"</thead>");
        fputs($fp,"<tbody>");
?>
<table id="relatorio_peca_exportada" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
<?php
        if (!empty($codigo_posto)) {
?>
            <th colspan='2'>Relatório de exportação de peças de <?=$data_inicial?> a <?=$data_final?><br />do posto <?=$codigo_posto ." - " .$descricao_posto?></th>
        </tr>
        <tr class='titulo_coluna'>
<?php
        }
?>
            <th>Peça</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
<?php

        while ($result = pg_fetch_object($resRes)) {

            fputs($fp,"<tr>");
            fputs($fp,"<td>".$result->peca_referencia." - ".$result->peca_descricao."</td>");
            fputs($fp,"<td>".$result->qtde."</td>");
            fputs($fp,"</tr>");
?>
        <tr>
            <td><a class="shadowbox" onclick="javascript:abreDetalhe('<?=$data_inicial?>','<?=$data_final?>','<?=$codigo_posto?>','<?=$result->peca_referencia?>');"><?=$result->peca_referencia." - ".$result->peca_descricao?></a></td>
            <td><?=$result->qtde?></td>
        </tr>
<?php
        }
        fputs($fp,"</tbody>");
        fputs($fp,"</table>");

        fclose($fp);
        $data = date("Y-m-d").".".date("H-i-s");
        rename("/tmp/relatorio_peca_exportada-$login_fabrica.html", "xls/relatorio_peca_exportada_$login_fabrica.$data.xls");

?>
    </tbody>
</table>
<br />
<p style="text-align:center">
<a href="xls/relatorio_peca_exportada_<?=$login_fabrica.".".$data?>.xls" class="btn btn-success" target="_blank" role="button"><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle' />&nbsp;&nbsp;&nbsp;Gerar Arquivo Excel</a>
</p>
<script type="text/javascript">
$.dataTableLoad({
    table: "#relatorio_peca_exportada",
    type: "basic"
});
</script>
<?php
    } else{
?>
<div class='container'>
    <div class='alert'>
        <h4>Nenhum resultado encontrado</h4>
    </div>
</div>
<?php
    }
}

include "rodape.php";
# Fim do relatório
?>
