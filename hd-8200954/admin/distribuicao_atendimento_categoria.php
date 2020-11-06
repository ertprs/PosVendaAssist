<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include '../helpdesk.inc.php';

$layout_menu = "callcenter";

$title = traduz("DISTRIBUIÇÃO DE ATENDIMENTOS POR CATEGORIA");

$btn_acao = $_POST['btn_acao'];

if ($_POST["btn_acao"] == "submit") {
    $origem = $_POST['origem'];
    $xdata_inicial  = implode("-", array_reverse(explode("/", $_POST["data_inicial"]))) . " 00:00:00";
    $xdata_final    = implode("-", array_reverse(explode("/", $_POST["data_final"]))) . " 23:59:59";

    //VALIDANDO AS DATASe

    $sql = "SELECT '$xdata_inicial'::timestamp, '$xdata_final'::timestamp";
    @$res = pg_query($sql);
    if (!$res)
    {
        $msg_erro = traduz("Preencha os campos obrigatórios");
        $btn_acao = "";
    }
    if($xdata_inicial > $xdata_final)
        $msg_erro = traduz("Preencha os campos obrigatórios");

    if(strtotime(substr($xdata_inicial,0,10)) < strtotime(date('2018-01-03'))){
        $msg_erro = traduz("Relatório válido a partir de 04/01/2018");
    }    
}

include "cabecalho_new.php";
$plugins = array(
    "datepicker",
    "mask"
);

include("plugin_loader.php");
?>

<!-- ******************************** JAVASCRIPT ******************************** -->

<script type="text/javascript">
    $(function(){
        $("#data_inicial").datepicker().mask("99/99/9999");
        $("#data_final").datepicker().mask("99/99/9999");

        var total_cat_tecnica = parseFloat($('.total_cat_tecnica').text());
        var total_geral = parseFloat($('.total_geral').text());
        
        var soma = ((total_cat_tecnica / total_geral)*100).toFixed(2);

        if(total_geral == 0){
            soma = 0;
        }

        $('.percentual_cat').text(soma+'%');
    });


</script>

<script language='javascript' src='../ajax.js'></script>
<style>
    .subtotal{
        background: silver !important;
    }
    .cor_azul{
        background-color:#33CCFF !important;
    }
</style>

<!-- ******************************** FIM JAVASCRIPT ******************************** -->

    <? if(strlen($msg_erro)>0){ ?>
        <div class='alert alert-danger'><h4><? echo $msg_erro; ?></h4></div>
    <? } ?>
<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<FORM class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
    <div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
    <br />
        <div class="row-fluid">
            <div class="span3"></div>
                <div class='span3'>
                    <div class='control-group <?= (strlen($msg_erro) > 0) ? 'error' : '' ?>'>
                        <label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
                            <div class='controls controls-row'>
                                <div class='span9'>
                                    <h5 class='asteristico'>*</h5>
                                    <input class="span12" type="text" id="data_inicial" name="data_inicial"  maxlength="10" value="<?=$data_inicial?>">
                                </div>
                            </div>
                    </div>
                </div>
                <div class='span3'>
                    <div class='control-group <?= (strlen($msg_erro) > 0) ? 'error' : '' ?>'>
                        <label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
                            <div class='controls controls-row'>
                                <div class='span9'>
                                    <h5 class='asteristico'>*</h5>
                                    <input class="span12" type="text" id="data_final" name="data_final" size="12" maxlength="10" value="<?=$data_final?>">
                                </div>
                            </div>
                    </div>
                </div>
            <div class="span3"></div>
        </div>
        <p><br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
        <br /><br />
</FORM>
</div>
<?

if ($_POST["btn_acao"] == "submit" and strlen($msg_erro)==0) {
    
	$tipo_atendimento_consumidor = array("C","R","S","W");

	foreach($tipo_atendimento_consumidor AS $value){
	    $categorias_dist[$value]['Técnica Equipamento'] = array('codigo_pecas_manuais' => 0, 'Manuais' => 0, 'Codigo de Pecas' => 0, 'Manutencao de Equipamento' => 0, 'Selecao de Produto' => 0, 'Utilizacao Produto' => 0);    
	    $categorias_dist[$value]['Técnica Consumivel']  = array('Consumo Consumiveis' => 0, 'Selecao de Produtos' => 0, "Duvidas gerais" => 0, 'Utilizacao do Produto (Processo)'=>0, 'Certificados' => 0, 'FISPQ' => 0 );
	    $categorias_dist[$value]['Reclamacao']          = array('item' => 0);
	    $categorias_dist[$value]['Comercial']           = array('item' => 0);
	}
    if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0){

        $sqlTem = "SELECT count(tbl_hd_chamado.hd_chamado) as qtde_hd_chamado, 
                    tbl_hd_chamado_extra.tipo_registro, JSON_FIELD('equipamentoeconsumivel', tbl_hd_chamado_extra.array_campos_adicionais) as item, JSON_FIELD('tipo_atendimento_consumidor', tbl_hd_chamado_extra.array_campos_adicionais) as tipo_atendimento_consumidor
                      into temp dados_atendimento
                    from tbl_hd_chamado 
                    join tbl_hd_chamado_extra using(hd_chamado)
                    where tbl_hd_chamado.data::date between '$xdata_inicial' and '$xdata_final' 
                    and fabrica = $login_fabrica 
                    and ( length(trim(JSON_FIELD('equipamentoeconsumivel', tbl_hd_chamado_extra.array_campos_adicionais))) > 0  OR tbl_hd_chamado_extra.tipo_registro = 'Comercial' OR tbl_hd_chamado_extra.tipo_registro = 'Reclamacao')
                    group by tbl_hd_chamado_extra.tipo_registro, JSON_FIELD('equipamentoeconsumivel', tbl_hd_chamado_extra.array_campos_adicionais), JSON_FIELD('tipo_atendimento_consumidor', tbl_hd_chamado_extra.array_campos_adicionais)
                    order by tipo_registro desc, item ASC ; ";
        $resTem = pg_query($con, $sqlTem);

	foreach($categorias_dist as $key => $valores){

		foreach($valores as $chave => $values){
				if($chave == 'Técnica Equipamento') {
					$categorias_dist["$key"]["$chave"]["Codigo de Peças / Manuais"] = 0;
				}
		    foreach ($values as $item => $qtde) {
			if($item != 'item'){
			    $condItem = " and item = '$item'  ";
			}else{
			    $condItem = "";
			}

			$sql = " SELECT * from dados_atendimento WHERE length(trim(tipo_registro)) > 0 and (length(trim(item)) > 0  OR tipo_registro = 'Comercial' OR tipo_registro = 'Reclamacao') and tipo_registro = '$chave' AND tipo_atendimento_consumidor = '{$key}'$condItem";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res)>0){
				for($j = 0; $j < pg_num_rows($res); $j++){
				    $qtde  = pg_fetch_result($res, $j, qtde_hd_chamado);
						       
				    if($item == 'Codigo de Pecas' OR $item == 'Manuais' OR $item == "codigo_pecas_manuais"){
					$categorias_dist["$key"]["$chave"]["Codigo de Peças / Manuais"] += $qtde;
					continue;    
				    }else{
					$categorias_dist["$key"]["$chave"]["$item"] = $qtde;
				    }
				}
			}
		    }
		    unset($categorias_dist["$key"]["$chave"]["Codigo de Pecas"]);
		    unset($categorias_dist["$key"]["$chave"]["Manuais"]);
		    unset($categorias_dist["$key"]["$chave"]["codigo_pecas_manuais"]);
		}
        }
	krsort($categorias_dist);
	foreach($categorias_dist as $key => $value){
		foreach($value as $k => $v){
            		$valores = (array_values($v));
            		$totalGeral += array_sum($valores);
		}
        }
	
	if(count($categorias_dist)){
?>
            <br><div class='container-fluid'>
            <table class='table table-striped table-bordered table-fixed'>
            <tr class="titulo_coluna">
                <th width="500px"><?=traduz('Categoria')?></th>
                <th><?=traduz('Item')?></th>
                <?php if(in_array($login_fabrica, array(152,180,181,182))) { ?>
                    <th><?=traduz('Tipo Atendimento')?></th>
                <?php } ?>
                <th width="80px" ><?=traduz('Quantidade de')?> <br> <?=traduz('Atendimentos')?></th>
                <th width="80px"><?=traduz('Percentual Relativo')?></th>
            </tr>
<?php
		$cont =0;
		#echo "<pre>";print_r($categorias_dist); exit;
		foreach($categorias_dist as $key => $value){
			foreach($value as $tipo_registro => $valor){
				foreach($valor as $item => $qtde_hd_chamado){
					$tipos[$tipo_registro][$item][$key] = $qtde_hd_chamado;
				}
			}
		}

		foreach($tipos as $tipo_registro => $value){
		#	print_r($value); exit;
		    foreach($value as $item => $valor){

			ksort($valor);
			foreach($valor as $key => $qtde_hd_chamado){

			    if($item == 'item'){
				$item = '';
			    }


			    if($tipo_registro != $tipo_registro_anterior AND in_array($tipo_registro_anterior, array('Técnica Equipamento', 'Técnica Consumivel'))){    
				$total_cat_tecnica += $total_categoria;                
				echo "<tr>";
				    echo "<td class='subtotal' colspan='2' style='text-align:right;'><b>".traduz("Subtotal de % ", null.null, [$tipo_registro_anterior])."</b></td>";
				    if(in_array($login_fabrica, array(152,180,181,182))) {
					echo "<td class='subtotal' style='text-align:right;'>&nbsp;</td>";
				    }
				    echo "<td class='subtotal' style='text-align:right;'><b>$total_categoria</b></td>";
				    echo "<td class='tac subtotal'><b>".number_format($percentual_cat, 2, ',', ' ')."% </b></td>";
				echo "</tr>";

				if(in_array($tipo_registro_anterior, array('Técnica Consumivel'))) {
				    echo "<tr>";
					echo "<td colspan='2' style='text-align:right;'><b>".traduz("Subtotal da Categoria Técnicas")."</b></td>";
					if(in_array($login_fabrica, array(152,180,181,182))) {                     
					    echo "<td>&nbsp;</td>";
					}
					echo "<td style='text-align:right;' class='total_cat_tecnica'><b>$total_cat_tecnica</b></td>";
					echo "<td class='tac percentual_cat'><b> 100% </b></td>";
				    echo "</tr>";

				}
				$total_categoria = 0;    
				$percentual_cat = 0;                
			    }

			    if($item == ''){
				if($totalGeral == 0){
				    $percentual = 0;    
				}else{
				    $percentual = ($qtde_hd_chamado / $totalGeral) *100;
				}
			    }else{
				if(strlen(trim($qtde_hd_chamado))==0){
				    $percentual = 0;    
				}else{
				    $percentual = ($qtde_hd_chamado / $totalGeral ) *100;             
				}
			    }   
	       
			    $percentual_cat +=  $percentual;
			    $percentual = number_format($percentual, 2, ',', ' ');                     

			    if($tipo_registro == 'Reclamacao'){
				$tipo_registro = "Reclamação";
				$cor = 'cor_azul';
			    }elseif($tipo_registro == 'Comercial'){
				$cor = 'cor_azul';
			    }else{
				$cor = '';
			    }

			    echo "<tr>";
				echo "<td>$tipo_registro</td>";
				echo "<td>$item</td>";
				if(in_array($login_fabrica, array(152,180,181,182))) {
				    $tipo_atendimento_consumidor = $key;
				    switch($tipo_atendimento_consumidor){                                        
					case "S": $tipo_atendimento = 'SAE'; break;
					case "C": $tipo_atendimento = 'Cliente Final'; break;
					case "R": $tipo_atendimento = 'Revenda'; break;
					case "W": $tipo_atendimento = 'WhatsApp'; break;                                
				    }               
				    echo "<td style='text-align:center;'>$tipo_atendimento</td>";
				    //echo "<td style='text-align:center;'>TESTE</td>";
				}
				echo "<td style='text-align:right;'>$qtde_hd_chamado</td>";
				echo "<td class='tac $cor'>$percentual%</td>";
			    echo "</tr>";
			    
			    $tipo_registro_anterior = $tipo_registro;
			    $total_categoria += $qtde_hd_chamado; 
			    $total_geral += $qtde_hd_chamado;
			    $cont++;
			}
		    }
            }

            echo "<tr >";
                if($total_geral >0){
                    $percentual_final = 100;
                }else{
                    $percentual_final = 0;
                }
                        echo "<td colspan='2' class='cor_azul'><b>".traduz("Total Geral")." </b></td>";
                        if(in_array($login_fabrica, array(152,180,181,182))) {
                            echo "<td class='cor_azul'>&nbsp;</td>";
                        }
                        echo "<td style='text-align:right;' class='cor_azul total_geral'><b>$total_geral</b></td>";
                        echo "<td class='tac cor_azul'><b> ".number_format($percentual_final, 2, ',', ' ')."% </b></td>";
                    echo "</tr>";
            echo "</table><br>";       

        }else{
            echo "<div class='alert alert-warning container'><h4>".traduz("Não foram encontrados resultados para esta pesquisa!")."</h4></div>";
        }
    }
}

?>
<? include "rodape.php" ?>
