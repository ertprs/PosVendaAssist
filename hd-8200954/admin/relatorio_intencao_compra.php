<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
require_once 'autentica_admin.php';

include_once 'funcoes.php';
include_once "../helpdesk/mlg_funciones.php";


if($_POST['btn_acao']){

	$data_inicial = getPost('data_inicial');
	$data_final   = getPost('data_final');

	$posto_id     = trim($_POST["posto_id"]);
	$peca_id     = trim($_POST["peca_id"]);

	if (strlen($_POST['data_inicial']) > 0) $data_inicial = $_POST['data_inicial'];
	if (strlen($_POST['data_final']) > 0)   $data_final   = $_POST['data_final']  ;

	if(!$data_inicial OR !$data_final){
		$msg_erro = "Informe uma Data.";
	}else{
	//Início Validação de Datas
		if($data_inicial) {
			list($di, $mi, $yi) = explode("/", $data_inicial); //tira a barra
			if(!checkdate($mi, $di, $yi)) $msg_erro = traduz("Data Inválida");
		}
		if($data_final) {
			list($df, $mf, $yf) = explode ("/", $data_final );//tira a barra
			if(!checkdate($mf, $df, $yf)) $msg_erro = traduz("Data Inválida");
		}
		if(!$msg_erro) {
			$nova_data_inicial = strtotime("$yi-$mi-$di");
			$nova_data_final   = strtotime("$yf-$mf-$df");

			if($nova_data_final < $nova_data_inicial){
				$msg_erro = traduz("Data Inválida.");
			}
			//Fim Validação de Datas
		}
	}

	if(strlen($msg_erro)==0){
		if(strlen($data_inicial) > 0 AND strlen($data_final)>0){		

			if(strlen($data_inicial)>0 and strlen($data_final)>0){
				list($di, $mi, $yi) = explode("/", $data_inicial);
				$xdata_inicial = "$yi-$mi-$di"; 

				list($df, $mf, $yf) = explode("/", $data_final);
				$xdata_final = "$yf-$mf-$df";
				$condData = " and tbl_intencao_compra_peca.data_input BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";
			}

			if(strlen($peca_id)>0 and strlen($peca_referencia)>0){
				$condPosto = " and tbl_intencao_compra_peca.peca = $peca_id";
			}

			if(strlen($posto_id)>0 and strlen($codigo_posto)){
				$condPeca = " and tbl_intencao_compra_peca.posto = $posto_id";
			}		
		}

		$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, TO_CHAR(tbl_intencao_compra_peca.data_input, 'DD/MM/YYYY') AS data, tbl_intencao_compra_peca.pedido, tbl_intencao_compra_peca.qtde, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_intencao_compra_peca 
				join tbl_peca on tbl_peca.peca = tbl_intencao_compra_peca.peca and tbl_peca.fabrica = $login_fabrica 
				join tbl_posto on tbl_posto.posto = tbl_intencao_compra_peca.posto 
				join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica 
				WHERE tbl_intencao_compra_peca.fabrica = $login_fabrica 
				$condPosto 
				$condPeca
				$condData 
				order by tbl_intencao_compra_peca.pedido, data, tbl_intencao_compra_peca.posto";
		$resint = pg_query($con, $sql);

		$qtdeRegistro = pg_num_rows($resint); 

		if(isset($_POST['gerar_excel'])){

			$filename = "relatorio-intencao_compra-".date('Ydm').".csv";
			$file     = fopen("/tmp/{$filename}", "w");

			$thead = 'Posto; '.'Peça'.';Qtde;Pedido;'.'Data Intenção de Compra'.";\n\r";

			fwrite($file, "$thead");


			for($i=0; $i<$qtdeRegistro; $i++){
				$peca 		= pg_fetch_result($resint, $i, 'peca');
				$referencia = pg_fetch_result($resint, $i, 'referencia');
				$descricao 	= pg_fetch_result($resint, $i, 'descricao');
				$data 		= pg_fetch_result($resint, $i, 'data');
				$pedido		= pg_fetch_result($resint, $i, 'pedido');
				$qtde		= pg_fetch_result($resint, $i, 'qtde');

				$posto_nome		= pg_fetch_result($resint, $i, 'nome');
				$posto_codigo		= pg_fetch_result($resint, $i, 'codigo_posto');

				$tbody .= "$posto_codigo - ".utf8_encode("$posto_nome").";$referencia - ".utf8_encode("$descricao").";$qtde;$pedido;$data;\n\r";
			}

			fwrite($file, "$tbody");
			fclose($file);

			if (file_exists("/tmp/{$filename}")) {
				system("mv /tmp/{$filename} xls/{$filename}");

				echo "xls/{$filename}";
			}
			exit;
	}

	}
}




$layout_menu = "callcenter";
$title = traduz("RELATÓRIO DE INTENÇÃO DE COMPRA DE PEÇA");

include 'cabecalho_new.php';
	$plugins = array(
		"mask",
		"datepicker",
		"shadowbox",
		"dataTable"
	);

	include "plugin_loader.php";

$form = array(
		"data_inicial" => array(
			"span"      => 4,
			"label"     => traduz("Data Inícial"),
			"type"      => "input/text",
			"width"     => 10,
			"required"  => true,
			"maxlength" => 10
		),
		"data_final" => array(
			"span"      => 4,
			"label"     => traduz("Data Final"),
			"type"      => "input/text",
			"width"     => 10,
			"required"  => true,
			"maxlength" => 10
		),
		"codigo_posto" => array(
			"span"      => 4,
			"label"     => traduz("Código Posto"),
			"type"      => "input/text",
			"width"     => 10,
			"lupa"		=> array(
					"name"   	=> "codigo_posto",
					"tipo"   	=> "posto",
					"parametro" => "codigo"
				)
		),
		"descricao_posto" => array(
			"span"      => 4,
			"label"     => traduz("Posto"),
			"type"      => "input/text",
			"width"     => 10,
			"lupa"		=> array(
					"name"   	=> "descricao_posto",
					"tipo"   	=> "posto",
					"parametro" => "nome"
				)
		),
		"peca_referencia" => array(
			"span"      => 4,
			"label"     => traduz("Referência"),
			"type"      => "input/text",
			"width"     => 10,
			"lupa"		=> array(
					"name"   	=> "peca_referencia",
					"tipo"   	=> "peca",
					"parametro" => "referencia"
				)
		),
		"peca_descricao" => array(
			"span"      => 4,
			"label"     => traduz("Descrição"),
			"type"      => "input/text",
			"width"     => 10,
			"lupa"		=> array(
					"name"   	=> "peca_descricao",
					"tipo"   	=> "peca",
					"parametro" => "descricao"
				)
		)

);

?>


<script>
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		Shadowbox.init();

		$("span[rel=descricao_posto]").click(function () {
			$.lupa($(this));
		});

		$("span[rel=codigo_posto]").click(function () {
			$.lupa($(this));
		});
		$("span[rel=peca_referencia]").click(function () {
			$.lupa($(this));
		});
		$("span[rel=peca_descricao]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_posto(retorno){
		console.log(retorno);
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
		$("#posto_id").val(retorno.posto);
	}

	function retorna_peca(retorno){
		console.log(retorno);
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
		$("#peca_id").val(retorno.peca);
    }

</script>

	<?php if(strlen($msg_erro)>0){ ?>
	<div class="container">
		<div class="row">
			<div class='alert alert-error'><h4><?=$msg_erro?></h4></div>
		</div>
	</div>
	<?php } ?>

	<div class="row"> <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b> </div>

		<form name='frm_relatorio' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>

			<div class='titulo_tabela'><?=traduz('Parâmetros de Pesquisa')?></div> <br/>

			<? echo montaForm($form,null);?>
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
			<input type='hidden' id="peca_id" name='peca_id' value='<?=$peca_id?>' />
			<input type='hidden' id="posto_id" name='posto_id' value='<?=$posto_id?>' />
		</p><br/><br>
</form>
<?

		
		
		if($qtdeRegistro > 0){
			echo "<table id='resultado' class=\"table table-striped table-bordered table-hover table-fixed\" style='margin: 0 auto;' >";
			echo "<thead><tr class='titulo_coluna'>";
			echo "<th >".traduz('Posto')."</th>";
			echo "<th >".traduz('Peça')."</th>";
			echo "<th >".traduz('Qtde')."</th>";
			echo "<th >".traduz('Pedido')."</th>";
			echo "<th >".traduz('Data Intenção de Compra')."</th>";
			echo "</tr></thead><tbody>";
		}elseif(isset($_POST['btn_acao']) and $qtdeRegistro == 0 ){
			echo '<div class="row">
				<div class="alert"><h4>'.traduz("Nenhum registro encontrado").'</h4></div>
			</div>';
		}

		for($i=0; $i<$qtdeRegistro; $i++){
			$peca 		= pg_fetch_result($resint, $i, 'peca');
			$referencia = pg_fetch_result($resint, $i, 'referencia');
			$descricao 	= pg_fetch_result($resint, $i, 'descricao');
			$data 		= pg_fetch_result($resint, $i, 'data');
			$pedido		= pg_fetch_result($resint, $i, 'pedido');
			$qtde		= pg_fetch_result($resint, $i, 'qtde');

			$posto_nome		= pg_fetch_result($resint, $i, 'nome');
			$posto_codigo		= pg_fetch_result($resint, $i, 'codigo_posto');


			echo "<tr>";	
				echo "<td nowrap>$posto_codigo - $posto_nome</td>";
				echo "<td nowrap>$referencia - $descricao</td>";
				echo "<td class='tac'>$qtde</td>";
				echo "<td class='tac'>$pedido</td>";
				echo "<td class='tac'>$data</td>";
			echo "</tr>";


		}

		echo "</tbody></table>";		
	
if(isset($_POST['btn_acao']) and $qtdeRegistro > 0 ){
	$jsonPOST = excelPostToJson($_POST);
	?>
	<br />
	<div id='gerar_excel' class="btn_excel">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
	</div>
<?php
}

include 'rodape.php';
?>
