<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = traduz("RELATÓRIO ATENDIMENTO POR PRODUTO");

$btn_acao = $_REQUEST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial = $_REQUEST['data_inicial'];
	$data_final   = $_REQUEST['data_final'];
	$produto_referencia = $_REQUEST['produto_referencia'];
	$produto_descricao  = $_REQUEST['produto_descricao'];
	$natureza_chamado   = $_REQUEST['natureza_chamado'];
	$status             = $_REQUEST['status'];
    $hd_chamado_origem  = $_REQUEST['hd_chamado_origem'];
    $linha              = $_REQUEST['linha'];

	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	if(strlen($data_inicial) > 0){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro["msg"][]    ="Data Inválida";
        $msg_erro["campos"][] = "data_inicial";
	}

	if(strlen($data_final) > 0){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		$msg_erro["msg"][]    = traduz("Data Inválida");
        $msg_erro["campos"][] = "data_final";
	}

	if(!count($msg_erro["msg"])){
		$dat = explode ("/", $data_inicial );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    = traduz("Data Inválida");
            $msg_erro["campos"][] = "data_inicial";
        }
	}
	if(!count($msg_erro["msg"])){
		$dat = explode ("/", $data_final );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    = traduz("Data Inválida");
            $msg_erro["campos"][] = "data_final";
        }
	}

	if($xdata_inicial > $xdata_final){
		$msg_erro["msg"][]    = traduz("Data Inicial maior que final");
        $msg_erro["campos"][] = "data_inicial";
    }

	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND fabrica = {$login_fabrica} where referencia='$produto_referencia' limit 1";
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

	if($login_fabrica==2){
		$condicoes = $produto . ";" . $natureza_chamado . ";" . $status . ";" . $posto . ";" . $xdata_inicial . ";" .$xdata_final;
	}
}

include "cabecalho_new.php";

include_once("callcenter_suggar_assuntos.php");

$plugins = array(
    "datepicker",
    "shadowbox",
    "mask"
);

include ("plugin_loader.php");
?>
<script type="text/javascript" src="js/highcharts_4.0.3.js"></script>
<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status,origem='', linha=''){
janela = window.open("callcenter_relatorio_produto_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+ "&origem=" +origem+ "&linha=" +linha, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("produto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

});

function retorna_produto (retorno) {
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
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


<br/>
<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios ')?></b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
    <div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
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
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
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
        <?php if ($login_fabrica <> 189) {?>
        <div class='span8'>
			<label class='control-label' for='natureza_chamado'><?=traduz('Natureza')?></label>
			<div class='controls controls-row'>
				<div class='span4'>
                    <select name="natureza_chamado" id="natureza_chamado">
                        <option value=''></option>
                        <?PHP
                            if ($login_fabrica == 24) {
                                foreach($assuntos as $topico => $itens)
                                    foreach($itens AS $label => $valor) {
                                        if ($valor == $natureza_chamado) {
                                            $selected = "selected";
                                        }
                                        else {
                                            $selected = "";
                                        }

                                        echo "
                                        <option value='$valor' $selected>$topico >> $label</option>";
                                    }
                            }else {
                                //HD39566
                                $sqlx = "SELECT nome            ,
                                                descricao
                                        FROM tbl_natureza
                                        WHERE fabrica=$login_fabrica
                                        AND ativo = 't'
                                        ORDER BY nome";

                                $resx = pg_exec($con,$sqlx);
                                if(pg_numrows($resx)>0){
                                    for($y=0;pg_numrows($resx)>$y;$y++){
                                        $nome     = trim(pg_result($resx,$y,nome));
                                        $descricao     = trim(pg_result($resx,$y,descricao));

                                        echo "<option value='$nome'";
                                            if($natureza_chamado == $nome) {
                                                echo "selected";
                                            }
                                        echo ">$descricao</option>";
                                    }

                                }
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <?php }?>
        <?php if ($login_fabrica == 189) {?>
        <div class='span4'>
            <label class='control-label' for='hd_chamado_origem'>Depto. Gerador da RRC</label>
            <div class='controls controls-row'>
                <div class='span4'>
                    <select name="hd_chamado_origem" id="hd_chamado_origem">
                        <option value="">Escolha</option>
                        <?php 

                             $sqlOrigem = "
                                SELECT hd_chamado_origem, descricao
                                FROM tbl_hd_chamado_origem
                                WHERE fabrica = $login_fabrica
                                ORDER BY descricao
                            ";
                            $resOrigem = pg_query($con, $sqlOrigem);
                            foreach (pg_fetch_all($resOrigem) as $key => $rows) {
                                $selected = ($hd_chamado_origem == $rows["hd_chamado_origem"]) ? "selected" : "";
                                echo '<option '.$selected.' value="'.$rows["hd_chamado_origem"].'">'.$rows["descricao"].'</option>';
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class='span4'>
            <label class='control-label' for='linha'>Linha</label>
            <div class='controls controls-row'>
                <div class='span4'>
                    <select name="linha" id="linha">
                        <option value=''>Escolha</option>
                        <?PHP
                            $sqlx = "SELECT linha, nome
                                       FROM tbl_linha
                                      WHERE fabrica=$login_fabrica
                                        AND ativo = 't'
                                    ORDER BY nome";

                            $resx = pg_query($con,$sqlx);
                            if(pg_num_rows($resx)>0){
                                for($y=0;pg_numrows($resx)>$y;$y++){
                                    $id_linha = pg_fetch_result($resx,$y,'linha');
                                    $nome     = pg_fetch_result($resx,$y,'nome');
                                    $selected = ($linha == $id_linha) ? "selected" : "";
                                    echo "<option $selected  value='".$id_linha."'>$nome</option>";
                                }

                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <?php }?>

        <div class='span2'></div>
    </div>
    <p><br/>
			<input type='submit' style="cursor:pointer" name='btn_acao' value='<?=traduz("Consultar")?>'>
	</p><br/>
</FORM>
<br />

<?

if(strlen($btn_acao)>0){

	if(count($msg_erro)==0){

		if($login_fabrica == 74){
		    $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
		}

		$join_hd = " JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					LEFT JOIN tbl_produto ON tbl_produto.produto= tbl_hd_chamado_extra.produto ";
		if($login_fabrica == 151) {
			if($xdata_inicial > '2015-12-04') {
				$join_hd = " Join tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado 
								JOIN tbl_produto ON tbl_produto.produto= tbl_hd_chamado_item.produto ";
			}
		}

        $campo_origem = "";
        $join_linha = "";

        if($login_fabrica == 189) {
            $join_hd = "";
            $join_linha .= " JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                            JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado 
                            JOIN tbl_produto ON tbl_produto.produto= tbl_hd_chamado_item.produto ";
            $campo_origem = "tbl_hd_chamado_extra.hd_chamado_origem,";
            
            if(strlen($linha) > 0) {
                $join_linha .= " JOIN tbl_linha ON tbl_linha.linha= tbl_produto.linha  AND tbl_linha.fabrica={$login_fabrica} AND tbl_linha.linha={$linha}";
            }
            if(strlen($hd_chamado_origem) > 0) {
                $join_linha .= " JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem= tbl_hd_chamado_extra.hd_chamado_origem  AND tbl_hd_chamado_origem.fabrica={$login_fabrica} AND tbl_hd_chamado_origem.hd_chamado_origem={$hd_chamado_origem}";
            }
        }

		$sql = "SELECT	tbl_produto.produto        ,
						tbl_produto.referencia     ,
                        tbl_produto.descricao      ,
						tbl_produto.linha      ,
                        {$campo_origem}
						tbl_produto.ativo          ,
						count(distinct tbl_hd_chamado.hd_chamado) as qtde
				FROM tbl_hd_chamado
                $join_hd
				$join_linha
				WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
					AND tbl_hd_chamado.data between '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
					AND  tbl_hd_chamado.status <> 'Cancelado'
                    and tbl_hd_chamado.posto is null
					AND $cond_1
					AND $cond_2
					AND $cond_3
					$cond_admin_fale_conosco
				GROUP BY	tbl_produto.produto    ,
							tbl_produto.referencia ,
                            tbl_produto.descricao  ,
							tbl_produto.linha  ,
                            {$campo_origem}
							tbl_produto.ativo
				ORDER BY qtde desc
				;
		";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			if($login_fabrica == 24){
				for($y=0;pg_numrows($res)>$y;$y++)
					$total_calcula_porcentagem += pg_result($res,$y,qtde);
			}
?>
<table id="callcenter_relatorio_produto" class='table table-striped table-bordered table-hover table-large' >
    <thead>
        <TR class='titulo_coluna'>
            <th>Produto</th>
            <th>Qtde</TD>
<?
			if($login_fabrica == 24){
?>
            <th>&nbsp;%&nbsp;</th>
<?
            }
?>
        </tr>
    </thead>
    <tbody>
<?
			for($y=0;pg_numrows($res)>$y;$y++){
				$produto    = pg_result($res,$y,produto);
				$referencia = pg_result($res,$y,referencia);
				$descricao  = pg_result($res,$y,descricao);
				$ativo      = pg_result($res,$y,ativo);
                $qtde       = pg_result($res,$y,qtde);
                $xxlinha       = pg_result($res,$y,linha);
				$xxhd_chamado_origem= pg_result($res,$y,hd_chamado_origem);

				if(strlen($produto)==0){
					$descricao  = traduz("Chamado sem produto");
				}

				if($ativo<>"t"){$ativo="*";}else{$ativo="";}
				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
?>
        <tr bgcolor='<?=$cor?>'>
            <td align='left' nowrap>
                <a href="javascript: AbreCallcenter('<?=$xdata_inicial?>','<?=$xdata_final?>','<?=$produto?>','<?=$natureza_chamado?>','<?=$status?>','<?=$xxhd_chamado_origem?>','<?=$xxlinha?>');"><?=$ativo?> <?=$referencia?> - <?=$descricao?></a>
            </td>
			<td align='center' nowrap><?=$qtde?></td>
<?
				if($login_fabrica == 24){

				$grafico_porcentagem[] = (float)(($qtde/$total_calcula_porcentagem)*100);
?>
            <td align='center' nowrap><?=number_format((($qtde/$total_calcula_porcentagem)*100),2)?> % </td>
<?
                }
?>
        </TR >
<?
				$total_qtde += $qtde;
				$registro += 1;

				$grafico_descricao[]    = utf8_encode(trim($descricao));
				$grafico_qtde[]         = (int)trim($qtde);
			}
			$height_grafico         = (count($grafico_descricao) / 3);
            $height_grafico         = (ceil($height_grafico) * 200);
            $highcharts_descricao   = json_encode($grafico_descricao);
            $highcharts_qtde        = json_encode($grafico_qtde);
            if ($login_fabrica == 189) {
                $highcharts_porcentagem = 0;
            }
            if($login_fabrica == 24){
                $highcharts_porcentagem = json_encode($grafico_porcentagem);
                $colspan = "colspan='2'";
            }
?>
    </tbody>
    <tfoot>
        <tr class='titulo_coluna'>
            <td <?=$colspan?>><?=traduz('Total')?></td>
            <td align='center'><?=$total_qtde?></td>
        </tr>
    </tfoot>
</table>
<center><font size='1'><?=traduz('* produto(s) inativo(s)')?></font></center>
<BR><BR>

<?
			$media = $total_qtde / $registro;
?>

<script type="text/javascript">
$(function(){
    var login_fabrica = <?=$login_fabrica?>;
    if(login_fabrica == 24){
        var valor = true;
    }else{
        var valor = false;
    }
    $("#grafico").highcharts({
        chart: {
            type: 'bar',
            height: <?=$height_grafico?>
        },
        credits: {
            enabled: false
        },
        title:{
            text: '<?=traduz('Relatório de Atendimento por Produto')?>'
        },
        subtitle:{
             text:'<?=traduz('Período: ')?>' '<?=$data_inicial?> - <?=$data_final?>'
        },
        xAxis: {
            categories: <?=$highcharts_descricao?>
        },
        yAxis:[{
            min: 0,
            title: {
                enabled: true,
                text: '<?=traduz('Nº de Atendimentos')?>',
                style: {
                    fontWeight: 'normal'
                }
            },
        },{
            title: {
                enabled: true,
                text: '<?=traduz('Nº de Atendimentos')?>',
                style: {
                    fontWeight: 'normal'
                }
            },
            opposite:true
        }],
        legend: {
            enabled:false
        },
        tooltip: {
            formatter: function() {
                return '<?=traduz('<b>Atendimentos</b>: ')?>'+ this.y;
            }
        },
        series: [{
            data: <?=$highcharts_qtde?>
        }],
        plotOptions: {
            series: {
                dataLabels: {
                    enabled: valor,
                    formatter: function(){
                        var porcento = <?=$highcharts_porcentagem?>;
                        var correto = parseFloat(porcento[this.point.x]);
                        var formato = Highcharts.numberFormat(correto,2,',','.');
                        return formato+'%';
                    }
                }
            }
        },
    });
});
</script>
<div id="grafico" style="width: 800px; height: <?=$height_grafico?>px; margin: 0 auto"></div>
<?
			if($login_fabrica==2){//hd 36906 9/10/2008
				$title = traduz("RELATORIO ATENDIMENTO POR PRODUTO");
				echo "<BR><BR>";
				echo "<A HREF=\"javascript:abrir('impressao_callcenter.php?condicoes=$condicoes;$title')\">";
				echo "<IMG SRC=\"imagens/btn_imprimir_azul.gif\" BORDER='0' ALT=''>";
				echo "</A>";
			}
		}else{
?>
<h4 class="alert alert-warning"><?=traduz('Nenhum Resultado Encontrado')?></h4>
<?php
        }
	}
}

?>

<p>

<? include "rodape.php" ?>
