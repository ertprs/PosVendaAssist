<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

$linha_id  = $_GET['linha'];

if($_POST) {
	$linha = $_POST['linha'];
	$qtde_linha = $_POST['qtde_linha'];
	$valor_visita = $_POST['valor_visita'];
	for($i=1;$i<=$qtde_linha;$i++){
		$qtde_min  = $_POST['qtde_min_'.$i];
		$qtde_max  = $_POST['qtde_max_'.$i];
		$valor     = $_POST['valor_'.$i];
		$valor = str_replace(".", "", $valor); 
		$valor = str_replace(",", ".", $valor); 

		if(is_numeric($valor) and $qtde_min >= $qtde_max and $qtde_min > 0) {
			$msg_erro = "Valor de qtde mínima maior ou igual a qtde máxima $qtde_min  $qtde_max $valor";
		}
		$qtde_min_array[$i] = $qtde_min;
		$qtde_max_array[$i] = $qtde_max;
		$m = $i-1;
		if(in_array($qtde_min, range($qtde_min_array[$m],$qtde_max_array[$m])) or in_array($qtde_max, range($qtde_min_array[$m],$qtde_max_array[$m]))) {
			$msg_erro = "Intervalo de quantidade de OS errado ou menor que o anterior";
		}
		if($qtde_min > 0 && isset($valor)) {
			$valores_array[$i] = array('qtde_min'=>$qtde_min, 'qtde_max'=>$qtde_max, 'valor'=>$valor);
		}
	}
	if($valor_visita > 0) {
		$valor_visita = str_replace(".", "", $valor_visita); 
		$valor_visita = str_replace(",", ".", $valor_visita); 

		$valores_array['valor_visita'] = $valor_visita;
	}
	if(empty($msg_erro)) {
		$campos_adicionais = json_encode($valores_array);

		$res = pg_query ($con,"BEGIN TRANSACTION");
		$sql = "UPDATE tbl_linha set campos_adicionais = '$campos_adicionais' where linha = $linha";
		$res = pg_query($con, $sql); 
		if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
			header("Location: $PHP_SELF");
		}else{
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		}

	}
}

if(isset($linha_id)) {
	$sql = "select campos_adicionais from tbl_linha where fabrica = $login_fabrica and linha = $linha_id "; 
	$res = pg_query($con, $sql);
	$campos_adicionais = pg_fetch_result($res,0 ,'campos_adicionais');
	$campos_adicionais = json_decode($campos_adicionais , true);
	$valor_visita = $campos_adicionais['valor_visita'];
	unset($campos_adicionais['valor_visita']);

	foreach($campos_adicionais as $key => $value) {
		$conta_qtde = $key;
		foreach($value as $chaves => $valores) {
			$var =  $chaves."_".$key;
			$$var = $valores;
		}
	}
}
$qtde_linha = (isset($_POST['qtde_linha'])) ? $_POST['qtde_linha'] : ($conta_qtde > 1) ? $conta_qtde : 1 ;
$layout_menu = "cadastro";
$title = "CADASTRO DE VALORES MO POR LINHA";

include "cabecalho_new.php";

$plugins = array(
   "price_format",
   "alphanumeric",
   "ajaxform"
);

include "plugin_loader.php";
?>
<script type="text/javascript">

	$(function(){
		$(".valor").priceFormat({
			prefix: '',
			thousandsSeparator: '',
			centsSeparator: ",",
			centsLimit: 2
		});
	});

	function addLine(){
		var novaLinha = $("div[name=linha_1]").clone();
		var posicao   = $("div[name^=linha_]").length;

		$(novaLinha).find("label").remove();
		$(novaLinha).find("select").remove();
		$(novaLinha).find("button").remove();

		var linha_seguinte = posicao+1;

		$(novaLinha).attr('name','linha_'+linha_seguinte);
		var newHtml = $(novaLinha)
			.html()
			.replace(/(_|,)\d\d?/g,'\$1'+linha_seguinte);

		$(novaLinha)
			.html(newHtml);

		$(novaLinha)
			.find("input").val("")

		$("div[name=linha_"+posicao+"]").after(novaLinha);


		$(".valor").priceFormat({
			prefix: '',
			thousandsSeparator: '',
			centsSeparator: ",",
			centsLimit: 2
		});
		$("#qtde_linha").val(++posicao);
	}
</script>
<? $display = (strlen($msg_erro) > 0) ? 'block' : 'none'; ?>
<div class="alert alert-error" style="display: <?=$display; ?>">
	<h4><?=$msg_erro?></h4>
</div>

<div style="background-color: green; color: #fff; font-size: 18px; padding-top: 5px; padding-bottom: 5px; display: none; width: 700px; margin: 0 auto; margin-bottom: 5px;" id="msg_success">Valor Adicional Cadastrado com Sucesso!</div>

<form class='form-search form-inline tc_formulario' name='frm_cadastro' method='post'>
	<div class='titulo_tabela'>Cadastro Valores Mao de Obra</div>
	<br />
	<input type='hidden' name='qtde_linha' id='qtde_linha' value='<?=$qtde_linha?>'>
	<div class="row-fluid" name='linha_1'>
		<div class="span1"></div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=''>Linha</label>
				<div class='controls controls-row'>
					<select name='linha' class='frm'>
						<?php
							$sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY nome";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) > 0){
								for($i = 0; $i < pg_num_rows($res); $i++){
									$linha = pg_fetch_result($res, $i, 'linha');
									$nome = pg_fetch_result($res, $i, 'nome');

									echo "<option ";
									echo ($linha_id == $linha) ? " selected " : "" ; 
									echo " value='$linha'>$nome</option>";
								}

							}
						?>
					</select>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="control-group">
					<label class="control-label" for=''>Qtde Minima</label>
				<div class='controls controls-row'>
				<input type='number' name='qtde_min_1' class='inptc5' size='10' value='<?=$qtde_min_1?>' >
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="control-group">
					<label class="control-label" for=''>Qtde Maxima</label>
				<div class='controls controls-row'>
				<input type='number' name='qtde_max_1' class='inptc5' value='<?=$qtde_max_1?>'>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="control-group">
				<label class="control-label" for=''>Valor</label>
				<div class='controls controls-row'>
				<input class='span8 valor' type='text' name='valor_1' value='<?=$valor_1?>'>
					<button type='button' class='btn' onclick='javascript: addLine()'>+</button>
				</div>
			</div>
		</div>
	</div>
<?

if(!empty($linha_id) and $conta_qtde > 1) {
	for($i=2;$i<=$conta_qtde;$i++) { 
		$min  = "qtde_min_".$i;
		$max = "qtde_max_".$i;
		$valor = "valor_".$i;
	?>
	<div class="row-fluid" name='linha_<?=$i?>'>
		<div class="span1"></div>
		<div class="span4">
			<div class="control-group">
			</div>
		</div>
		<div class="span2">
			<div class="control-group">
				<div class='controls controls-row'>
				<input type='number' name='<?=$min?>' class='inptc5' size='10' value='<?=$$min?>' >
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="control-group">
				<div class='controls controls-row'>
				<input type='number' name='<?=$max?>' class='inptc5' value='<?=$$max?>'>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="control-group">
				<div class='controls controls-row'>
				<input class='span8 valor' type='text' name='<?=$valor?>' value='<?=$$valor?>'>
				</div>
			</div>
		</div>
	</div>
<? }
}
?>	
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=''>Valor Visita</label>
				<div class='controls controls-row'>
				<input class='span8 valor' type='text' name='valor_visita' value='<?=$valor_visita?>'>
				</div>
			</div>
		</div>
	</div>
	<br/>
	<button class="btn btn-primary">Gravar</button>
	<br /><br />
</form>

<div>
<?
	$sql = "SELECT linha, nome, campos_adicionais FROM tbl_linha where fabrica = $login_fabrica and campos_adicionais notnull order by 2"; 
	$res = pg_query($con, $sql);

	for($i=0;$i<pg_num_rows($res);$i++) {
		$nome = pg_fetch_result($res,$i, 'nome');
		$linha = pg_fetch_result($res,$i, 'linha');
		$campos_adicionais = pg_fetch_result($res, $i, 'campos_adicionais');

		$campos_adicionais = json_decode($campos_adicionais , true);
		$valor_visita = $campos_adicionais['valor_visita'];
		unset($campos_adicionais['valor_visita']);
		ksort($campos_adicionais);
		if($i == 0) {
			echo "<table class='table table-striped table-bordered table-hover table-fixed'>";
			echo "<thead><tr class='titulo_coluna'>";
			echo "<th>Linha</th>";
			echo "<th>Valores</th>";
			echo "</tr></thead><tbody>";
		}

		echo "<tr>";
		echo "<td class='tac'><a href='cadastro_valor_qtde_os.php?linha=$linha'>$nome</a></td>";
		echo "<td nowrap class='tac'>";
		foreach($campos_adicionais as $key => $valor) {
			echo "<h4>Quantidade de OS entre " . $valor['qtde_min'] . " a ". $valor['qtde_max'] . " com Valor de MO: R$" .number_format($valor['valor'],2,",",".") . "</h4><br>";
		}

		if($valor_visita > 0) {
			echo "<br> <h4>Valor Visita: " . number_format($valor_visita, 2, ",",".") ."</h4>";
		}
		echo "</td></tr>";
		if($i+1 == pg_num_rows($res)) {
			echo "</tbody></table>";
		}
	}

?>
</div>
<?php
include "rodape.php";
?>
