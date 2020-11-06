<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$natureza_chamado   = $_POST['natureza_chamado'];
	$status             = $_POST['status'];

    $cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";

	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro["msg"][]    =traduz("Data Inválida");
        $msg_erro["campos"][] = traduz("data_inicial");
	}

	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		$msg_erro["msg"][]    =traduz("Data Inválida");
        $msg_erro["campos"][] = "data_final";
	}

    if(empty($msg_erro)){
        $dat = explode ("/", $data_inicial );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)) {
            $msg_erro["msg"][]    =traduz("Data Inválida");
            $msg_erro["campos"][] = "data_inicial";
        }
    }
	if(empty($msg_erro)){
		$dat = explode ("/", $data_final );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)) {
            $msg_erro["msg"][]    =traduz("Data Inválida");
            $msg_erro["campos"][] = "data_final";
        }
	}

    if(in_array($login_fabrica, array(169,170))){
        $familia = $_POST['familia'];
        $join_familia = " JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                                   JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}";
        if (strlen($familia)) {
            $sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND familia = {$familia}";
            $res = pg_query($con ,$sql);

            if (!pg_num_rows($res)) {
                $msg_erro["msg"][]    = traduz("Familia não encontrada");
                $msg_erro["campos"][] = "familia";
            }else{
                $cond_familia = " AND tbl_produto.familia = {$familia} ";
            }
        }
    }

	if($xdata_inicial > $xdata_final){
		$msg_erro["msg"][]    ="Data Inválida";
        $msg_erro["campos"][] = "data_inicial";
        $msg_erro["campos"][] = "data_final";
    }
	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
		}
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}

    if(strlen($status)>0){
        $cond_3 = " tbl_hd_chamado.status = '$status'  ";
    }

	if($login_fabrica==6){
		$cond_4 = " tbl_hd_chamado.status <> 'Cancelado'  ";
	}

	if($login_fabrica==2){
		$condicoes = $produto . ";" . $natureza_chamado . ";" . $status . ";" . $posto . ";" . $xdata_inicial . ";" .$xdata_final;
	}

    if($login_fabrica == 74){
        $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
    }

    if(empty($msg_erro)){
        $sql = "SELECT tbl_hd_chamado_extra.defeito_reclamado,
                        tbl_defeito_reclamado.descricao,
                        count(tbl_hd_chamado.hd_chamado) as qtde
                from tbl_hd_chamado
                join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                $join_familia
                LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
                where tbl_hd_chamado.fabrica_responsavel = $login_fabrica
                and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
                and tbl_hd_chamado.status <> 'Cancelado'
                and $cond_1
                and $cond_2
                and $cond_3
                and $cond_4
                and tbl_hd_chamado.posto is null
                $cond_familia
                $cond_admin_fale_conosco
          GROUP BY  tbl_hd_chamado_extra.defeito_reclamado,
                    tbl_defeito_reclamado.descricao
          ORDER BY  qtde DESC
            ";
        $resSubmit = pg_exec($con,$sql);

    }

    /*
    if ($_POST["gerar_excel"]) {
        if (pg_num_rows($resSubmit) > 0) {
            $data = date("d-m-Y-H:i");
            $fileName = "callcenter_relatorio_defeito-{$data}.xls";

            $file = fopen("/tmp/{$fileName}", "w");

            $thead = "
            <table border='1'>
                    <thead>
                        <tr>
                            <th colspan='2' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
                                RELATÓRIO DE RECLAMAÇÃO
                            </th>
                        </tr>
                        <tr>
                        </tr>
                    </thead>
                    <tbody>
            ";
            fwrite($file, $thead);


             $body .= "<TR class='titulo_coluna'>
                        <td align='left'>Status</TD>
                        <TD>Qtde</TD>
                        </TR >\n";
            for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
                $defeito_reclamado  = pg_result($resSubmit,$i,defeito_reclamado);
                $descricao          = pg_result($resSubmit,$i,descricao);
                $qtde               = pg_result($resSubmit,$i,qtde);
                $total              = $total + $qtde;
                if ($i % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
                $body .= "
                <TR bgcolor='$cor'>
                <TD align='left' nowrap>$descricao</TD>
                <TD align='center' nowrap>$qtde</TD>
                </TR >
                ";
            }
            $body .= "</tbody>";
            fwrite($file,$body);

            $foot .= "
            <tfoot>
            <TR class='titulo_coluna'>
                <TD align='center' nowrap><B>Total</B></TD>
                <TD align='center' nowrap>$total</TD>
            </TR >
            </tfoot>
            </table>";
            fwrite($file,$foot);
            fclose($file);

            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");

                echo "xls/{$fileName}";
            }
        }
        exit;
    }
    */
}

$layout_menu = "callcenter";

if(in_array($login_fabrica, array(169,170))){
    $title = traduz("RELATÓRIO DE DEFEITOS RECLAMADOS");
}else{
    $title = traduz("RELATÓRIO DE RECLAMAÇÃO");
}

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

<script type="text/javascript" charset="utf-8">
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("produto"));
    Shadowbox.init();
    $.dataTableLoad();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

});


function retorna_produto(retorno){
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);



}

function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,defeito_reclamado,familia){
janela = window.open("callcenter_relatorio_defeito_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&defeito_reclamado="+defeito_reclamado+"&familia="+familia, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}

/* POP-UP IMPRIMIR */
	function abrir(URL) {
		var width = 700;
		var height = 600;
		var left = 90;
		var top = 90;

		window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
	}

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios ')?></b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>

    <div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
    <br/>
	<div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
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
                <label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
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
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='natureza'><?=traduz('Natureza')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="natureza_chamado" id="natureza_chamado">
                            <option value=""></option>
                            <?php
                            $sqlx = "SELECT nome,
                                            descricao
                                    FROM    tbl_natureza
                                    WHERE fabrica=$login_fabrica
                                    AND ativo = 't'
                                    ORDER BY nome";

                            $resx = pg_exec($con,$sqlx);

                            foreach (pg_fetch_all($resx) as $key) {
                                $selected_natureza = ( isset($natureza_chamado) and ($natureza_chamado == $key['nome']) ) ? "SELECTED" : '' ;

                            ?>
                                <option value="<?php echo $key['nome']?>" <?php echo $selected_natureza ?> >

                                    <?php echo $key['descricao']?>

                                </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
<?
if ($login_fabrica == 114) {
    ?>
    <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='status'><?=traduz('Status')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="status" id="status">
                            <option value=""></option>
                            <?php

                                $sql = "select status, status AS status_desc from tbl_hd_status where fabrica = $login_fabrica order by status";
                                $res = pg_exec($con,$sql);
                                foreach (pg_fetch_all($res) as $key) {

                                    $selected_status = ( isset($status) and ($status== $key['status']) ) ? "SELECTED" : '' ;

                                ?>
                                    <option value="<?php echo $key['status']?>" <?php echo $selected_status ?> >
                                        <?php echo $key['status_desc']?>
                                    </option>


                                <?php
                                }

                            ?>
                        </select>
                    </div>
                    <div class='span2'></div>
                </div>
            </div>
        </div>
 <?
}elseif (in_array($login_fabrica, array(50,74,15,117)) OR ($login_fabrica >= 129 and $login_fabrica <> 172)){ //HD-3282875 Adicionada fábrica 50
?>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='status'><?=traduz('Status')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="status" id="status">
                            <option value=""></option>
                            <?php

                                $campo_status = (in_array($login_fabrica, array(136))) ? "status" : "fn_retira_especiais(status)";

                                $sql = "select $campo_status AS status, status AS status_desc from tbl_hd_status where fabrica = $login_fabrica order by status";
                                $res = pg_exec($con,$sql);
                                foreach (pg_fetch_all($res) as $key) {

                                    $key['status_desc'] = $key['status_desc'];
                                    $key['status'] = $key['status'];

                                    $selected_status = ( isset($status) and ($status== $key['status']) ) ? "SELECTED" : '' ;

                                ?>
                                    <option value="<?php echo $key['status']?>" <?php echo $selected_status ?> >
                                        <?php echo $key['status_desc']?>
                                    </option>


                                <?php
                                }

                            ?>
                        </select>
                    </div>
                    <div class='span2'></div>
                </div>
            </div>
        </div>
<?
}else{
?>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='status'><?=traduz('Status')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="status" id="status">
                            <option value=""></option>
                            <option value="informacao" <?php if($status == 'informacao'){ echo " selected ";} ?>><?=traduz('Informações')?></option>
                            <option value="reclamacao" <?php if($status == 'reclamacao'){ echo " selected ";} ?>><?=traduz('Reclamações')?></option>
                        </select>
                    </div>
                    <div class='span2'></div>
                </div>
            </div>
        </div>
<?
}
?>
    </div>
<?php if(in_array($login_fabrica, array(169,170))){ ?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='familia'><?=traduz('Familia')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="familia" id="familia">
                            <option value=""></option>
                            <?php
                                $sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica and ativo order by descricao";
                                $res = pg_query($con,$sql);
                                foreach (pg_fetch_all($res) as $key) {
                                    $selected_familia = ( isset($familia) and ($familia == $key['familia']) ) ? "SELECTED" : '' ;
                                ?>
                                    <option value="<?php echo $key['familia']?>" <?php echo $selected_familia ?> >
                                        <?php echo $key['descricao']?>
                                    </option>
                                <?php
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span6"></div>
    </div>
<?php } ?>
        <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
<br />

<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);
?>
<table id="callcenter_relatorio_defeito" class='table table-striped table-bordered table-hover table-fixed' >
	<thead>
        <TR class='titulo_coluna'>
            <th><?=traduz('Defeito Reclamado')?></th>
	    <th><?=traduz('Quantidade')?></th>
        </TR >
    </thead>
    <tbody>
<?
        $grafico_conteudo = "";
        $count = pg_num_rows($resSubmit);
        $total_soma = 0;

        for($y=0; $y < $count; $y++){
            $qtde = pg_result($resSubmit,$y,qtde);
            $total_soma += $qtde;
        }

        for($y=0; $y < $count; $y++){

            $defeito_reclamado = pg_result($resSubmit,$y,defeito_reclamado);
            $descricao         = pg_result($resSubmit,$y,descricao);
            $qtde              = pg_result($resSubmit,$y,qtde);
            $total_qtde += $qtde;
            if(strlen($descricao)==0){$descricao = "Sem defeito reclamado";}
            $grafico_status[] = $descricao;
            $grafico_qtde[] = $qtde;

            if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}

            if(empty($defeito_reclamado)){ //hd_chamado=2710901 acerto o relatorio da tela admin/callcenter_relatorio_defeito_callcenter.php
                $defeito_reclamado = "null";
            }

            $virgula = ($y < ($count - 1)) ? "," : "";

            $resultato_porc = ($qtde / $total_soma) * 100;
            $resultato_porc = number_format($resultato_porc, 2);

            $descricao         = str_replace("'","",$descricao);

            $grafico_conteudo .= "['$descricao: $qtde',$resultato_porc]$virgula";

?>
        <TR bgcolor='$cor'>
            <TD class="tal">
                <a href="javascript: AbreCallcenter('<?=$xdata_inicial?>','<?=$xdata_final?>','<?=$produto?>','<?=$natureza_chamado?>','<?=$status?>','<?=$xperiodo?>','<?=$defeito_reclamado?>','<?=$familia?>')"><?=$descricao?></a>
            </TD>
            <TD class="tac"><?=$qtde?></TD>
        </TR >
<?
        }
?>
    </tbody>
    <tfoot>
        <TR class='titulo_coluna'>
            <TD class="tac"><?=traduz('Total')?></TD>
			<TD class="tac"><?=$total_qtde?></TD>
        </TR >
    </tfoot>
</table>

<br /> <br />

<script type="text/javascript" src="js/novo_highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

<script>
    var chart;
    $(document).ready(function() {
        chart = new Highcharts.Chart({
            chart: {
                renderTo: 'container',
                plotBackgroundColor: 0,
                plotBorderWidth: 0,
                plotShadow: true,
                margin: [-600, 0, 0, 0]
            },
            title: {
                text: ''
            },
            tooltip: {
                formatter: function() {
                    return '<b>'+ this.point.name +'</b>: '+ this.y +' %';
                }
            },
            plotOptions: {
                pie: {
                    size: 300,
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: false
                    },
                    showInLegend: true
                }
            },
            dataLabels: {
            enabled: true
            },
            legend: {
                layout: 'vertical',
                align: 'center',
                x: 0,
                verticalAlign: 'top',
                y: 480,
                floating: false,
                backgroundColor: '#FFFFFF',
                borderColor: '#CCC',
                borderWidth: 1,
                shadow: false
            },
            series: [{
                type: 'pie',
                name: 'Browser share',
                data: [
                        <?php echo $grafico_conteudo; ?>
                    ],
                dataLabels: {
                    enabled: true,
                    color: '#000000',
                    connectorColor: '#000000'
                }
            }]
        });
    });
    </script>

</div>

    <div id="container" style="width: 900px; height: 1000px; margin: 0 auto"></div>

<?php
    		// include ("../jpgraph/jpgraph.php");
    		// include ("../jpgraph/jpgraph_pie.php");
    		// include ("../jpgraph/jpgraph_pie3d.php");
    		// $img = time();
    		// $image_graph = "png/4_call$img.png";

    		// seleciona os dados das médias
    		// setlocale (LC_ALL, 'et_EE.ISO-8859-1');


    		// $graph = new PieGraph(550,350,"auto");
    		// $graph->SetShadow();

    		// $graph->title->Set("Relatório de Reclamação $data_inicial - $data_final");
    //		$graph->title->Set("");
    		// $p1 = new PiePlot3D($grafico_qtde);
    		// $p1->SetAngle(35);
    		// $p1->SetSize(0.4);
    		// $p1->SetCenter(0.4,0.7); // x.y
    		//$p1->SetLegends($gDateLocale->GetShortMonth());
    		// $p1->SetLegends($grafico_status);
    		//$p1->SetSliceColors(array('blue','red'));
    		// $graph->Add($p1);
    		// $graph->Stroke($image_graph);
    		// echo "\n\n<img src='$image_graph'>\n\n";
    		//	echo "<BR><a href='callcenter_relatorio_atendimento_xls.php?data_inicial=$xdata_inicial&data_final=$xdata_final&produto=$produto&natureza_chamado=$natureza_chamado&status=$status&imagem=$image_graph' target='blank'>Gerar Excel</a>";


    		if($login_fabrica==2){//hd 36906 3/10/2008
    			$title = traduz("RELATORIO DE RECLAMACAO");
    			echo "<BR><BR>";
    			echo "<A HREF=\"javascript:abrir('impressao_callcenter.php?condicoes=$condicoes;$title')\">";
    			echo "<IMG SRC=\"imagens/btn_imprimir_azul.gif\" BORDER='0' ALT=''>";
    			echo "</A>";
    		}

		}else{
			echo "<div class='container'>
            <div class='alert'>
                    <h4>".traduz('Nenhum resultado encontrado')."</h4>
            </div>
            </div>";
		}
	}
?>

<p>

<? include "rodape.php" ?>
