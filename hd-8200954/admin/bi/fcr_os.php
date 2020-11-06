<?
$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../admin/':'../');

include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';

if ($areaAdminCliente == true) {
	include 'autentica_admin.php';
	include_once '../funcoes.php';
} else {
	$admin_privilegios = "gerencia";
	include_once '../includes/funcoes.php';
	include '../autentica_admin.php';
	include "../monitora.php";
	
}
include_once '../../fn_traducao.php';

$layout_menu = "gerencia";
$title = traduz("BI - FIELD CALL RATE - PRODUTOS");

/* Para a inclusão dos arquivos do menu */
$bi = "sim";

if ($areaAdminCliente == true) {
	require_once("cabecalho_new.php");
} else {
	if ($login_fabrica == 117) {
		include_once('../carrega_macro_familia.php');
	}
	require_once("../cabecalho_new.php");
}

function convertem($term, $tp = 1) {
	if ($tp == "1")
		$palavra = strtr(strtoupper($term),"àáâãäåæçèéêëìíîïðñòóôõö÷øùüúþÿ","ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÜÚÞß");
	if ($tp == "0")
		$palavra = strtr(strtolower($term),"ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÜÚÞß","àáâãäåæçèéêëìíîïðñòóôõö÷øùüúþÿ");

	return $palavra;
}

$array_mes = array(
	1 => 'A',
	2 => 'B',
	3 => 'C',
	4 => 'D',
	5 => 'E',
	6 => 'F',
	7 => 'G',
	8 => 'H',
	9 => 'I',
	10 => 'J',
	11 => 'K',
	12 => 'L',
);

$array_ano = array(
	1995 => 'A',
	1996 => 'B',
	1997 => 'C',
	1998 => 'D',
	1999 => 'E',
	2000 => 'F',
	2001 => 'G',
	2002 => 'H',
	2003 => 'I',
	2004 => 'J',
	2005 => 'K',
	2006 => 'L',
	2007 => 'M',
	2008 => 'N',
	2009 => 'O',
	2010 => 'P',
);

$qtde_meses_leadrship = 15;

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

/* -------- Pesquisar -------- */
if ($_POST["btn_acao"] == "pesquisar") {

	if($login_fabrica == 24){
		$matriz_filial = $_POST['matriz_filial'];

		$cond_matriz_filial = " AND substr(bi_os.serie,length(bi_os.serie) - 1, 2) = '$matriz_filial' ";

	}

	if(strlen($_POST["classificacao"]) > 0) $classificacao = trim($_POST["classificacao"]);

	if(isset($_POST["linha"])){
				if(count($linha)>0){
					$linha = $_POST["linha"];
				}
		}

	if(strlen($_POST["estado"]) > 0){
		$estado_filtro = trim($_POST["estado"]);
		if(!in_array($login_fabrica, array(152,180,181,182)) && !isset($estado_filtro)){
			$mostraMsgEstado = traduz("<br>no ESTADO") .   $estado_filtro;
		}
		$mostraMsgEstado = traduz("<br>no ESTADO") .  $estado_filtro;
	}

	if($login_fabrica == 20 and $pais != 'BR'){
		if(strlen($_POST["pais"]) > 0) $pais = trim($_POST["pais"]);
	}

	$tipo_os = trim($_POST['tipo_os']);

	$codigo_posto = "";

	if(strlen($_POST["codigo_posto"]) > 0) $codigo_posto = trim($_POST["codigo_posto"]);
	$xtipo_posto = (count($_POST['tipo_posto'])) ? $_POST['tipo_posto'] : "";

	$xdefeito_constatado_grupo = $_POST['defeito_constatado_grupo'];

	$exceto_posto = $_POST["exceto_posto"];

	$produto_referencia 	  	 = trim($_POST['produto_referencia']);
	$produto_descricao  		 = trim($_POST['produto_descricao']) ;
	$multiplo           		 = trim($_POST['radio_qtde_produtos']);
	$status_checkpoint_pesquisa  = $_POST['status_os'];
	$situacao_os                 = trim($_POST['situacao_os']);
	$centro_distribuicao 		 = $_POST['centro_distribuicao'];

	if(strlen($produto_referencia) > 0 and strlen($produto_descricao) > 0){
		if ($login_fabrica == 14) {
			$sql = "SELECT  tbl_produto.produto,
							tbl_produto.referencia_fabrica,
							tbl_produto.referencia,
							tbl_produto.descricao
					from tbl_produto
					join tbl_linha using(linha)
					where tbl_linha.fabrica = $login_fabrica
					and tbl_produto.referencia = '$produto_referencia'";
		}else{
			$sql = "SELECT produto
					from tbl_produto
					join tbl_familia using(familia)
					where tbl_familia.fabrica = $login_fabrica
					and tbl_produto.referencia = '$produto_referencia'";
		}

		$res = pg_query($con,$sql);

		if(pg_num_rows($res)>0){
			$produto = pg_fetch_result($res,0 ,produto );
		}
	}

	if(empty($data_inicial) || empty($data_final)){
		$msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][] = "data_inicial";
		$msg_erro["campos"][] = "data_final";
	}

	if (count($msg_erro["msg"]) == 0) {

		$fnc = @pg_query($con,"SELECT fnc_formata_data('$data_inicial')");
		if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
		if (count($msg_erro["msg"]) == 0)          $aux_data_inicial = @pg_fetch_result ($fnc,0,0);
		else									   $erro=traduz("Data Inválida");

	}

	if (count($msg_erro["msg"]) == 0) {
		$fnc = @pg_query($con,"SELECT fnc_formata_data('$data_final')");
		if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
		if (strlen($erro) == 0)                    $aux_data_final = @pg_fetch_result ($fnc,0,0);
		else									   $erro = traduz("Data Inválida");
	}

	if($login_fabrica == 42){
		if(strtotime("+1 Year",strtotime($aux_data_inicial)) < strtotime($aux_data_final)){
			$msg_erro["msg"][] = traduz("Busca por data aceitável no limite de um ano");
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
		}

		$os_cortesia = filter_input(INPUT_POST,"os_cortesia");
	}

	$replicar 	= $_POST['PickList'];

	if (count($replicar) > 0 and $multiplo == 'muitos'){ // HD 71431
		$array_produto = array();
		$produto_lista = array();
		for ($i=0;$i<count($replicar);$i++){
			$p = trim($replicar[$i]);
			if (strlen($p) > 0) {
				$sql = "SELECT  tbl_produto.produto,
								tbl_produto.referencia,
								tbl_produto.referencia_fabrica,
								tbl_produto.descricao
					from tbl_produto
					join tbl_familia using(familia)
					where tbl_familia.fabrica = $login_fabrica
					and tbl_produto.referencia = '$p'";

				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					$multi_produto    = trim(pg_fetch_result($res,0,produto));
					$multi_referencia = trim(pg_fetch_result($res,0,referencia));
					$multi_descricao  = trim(pg_fetch_result($res,0,descricao));
					array_push($array_produto,$multi_produto);
					array_push($produto_lista,array($multi_produto,$multi_referencia,$multi_descricao));
				}
			}
		}
		$lista_produtos = implode($array_produto,",");
	}

	if (count($msg_erro["msg"]) == 0) $listar = "ok";

	if(!empty($exceto_posto)) {
		$checked = " checked ";
	}

	if (count($msg_erro["msg"]) > 0) {
		$data_inicial       = trim($_POST["data_inicial_01"]);
		$data_final         = trim($_POST["data_final_01"]);

		if(isset($_POST["linha"])){
			if(count($linha)>0){
				$linha = $_POST["linha"];
			}
		}
		$estado             = trim($_POST["estado"]);
		$tipo_pesquisa      = trim($_POST["tipo_pesquisa"]);
		$pais               = trim($_POST["pais"]);
		$origem             = trim($_POST["origem"]);
		$criterio           = trim($_POST["criterio"]);
		$produto_referencia = trim($_POST['produto_referencia']); // HD 2003 TAKASHI
		$produto_descricao  = trim($_POST['produto_descricao']); // HD 2003 TAKASHI
		$tipo_os            = trim($_POST['tipo_os']);
		$classificacao      = trim($_POST['classificacao']);
		$exceto_posto       = $_POST["exceto_posto"];
		$marca              = $_POST["marca"];
	}
}

$plugins = array(
	"mask",
	"datepicker",
	"dataTable",
	"autocomplete",
	"shadowbox",
	"multiselect"
);

include ADMCLI_BACK."plugin_loader.php";


$column_sorter = ($login_fabrica == 3) ? "5":"4";
?>

<script>
	$(function() {

		$.datepickerLoad(["data_ini", "data_fim"]);

		$.autocompleteLoad(Array("produto", "posto"), Array("produto", "posto"), null, "../");

		Shadowbox.init();
		$("#linha").multiselect({
			selectedText: "selecionados # de #",
			afterSelect: function(values){
				carrega_familia();
			}
		});
		$("#tipo_posto").multiselect({
			selectedText: "selecionados # de #"
		});

		$("#defeito_constatado_grupo").multiselect({
			selectedText: "selecionados # de #"
		});

		$("#status_os").multiselect({
			selectedText: "selecionados # de #"
		});
		$(document).on("click", "span[rel=lupa]", function () {
			$.lupa($(this),Array('posicao'), "../");
		});

		$("#produtos").click(function(){
			$( "#form_produtos" ).submit();
		});

	});

	$(function() {
		var table = new Object();
		table['table'] = '#relatorio_fcr';
	table['type'] = 'full';
	<?php if ($login_fabrica == 117) { ?>
		table['aoColumns'] = [
			{"sType": 'string'},
			{"sType": 'string'},
			{"sType": 'string'},
			{"sType": 'string'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'numeric'}
			];
	<?php } else if ($login_fabrica == 3) { ?>
		table['aoColumns'] = [
			{"sType": 'string'},
			{"sType": 'string'},
			{"sType": 'string'},
			{"sType": 'string'},
			{"sType": 'string'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'string'}
			];
	<?php } else { ?>
		table['aoColumns'] = [
			{"sType": 'string'},
			{"sType": 'string'},
			{"sType": 'string'},
			{"sType": 'string'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'numeric'},
			{"sType": 'string'}
			];
	<?php } ?>

		$.dataTableLoad(table);
	});

	function toggleProd(e){
		$("#muitos").toggle();
	}

	function retorna_produto (retorno) {
		$("#produto").val(retorno.produto);
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	function AbrePeca(produto,data_inicial,data_final,linha,estado,posto,pais,marca,tipo_data,aux_data_inicial,aux_data_final,lista_produtos,exceto_posto,familia,tipo_atendimento,produto_trocado = "false", tipo_posto, matriz_filial){
		matriz_filial = matriz_filial || '0';
		janela = window.open("fcr_os_item.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado +"&posto=" + posto +"&pais=" + pais +"&marca=" + marca + "&tipo_data=" + tipo_data +"&aux_data_inicial="+aux_data_inicial+"&aux_data_final="+aux_data_final+"&lista_produtos="+lista_produtos+"&exceto_posto="+exceto_posto+"&familia="+familia+"&tipo_atendimento="+tipo_atendimento+"&produto_trocado="+produto_trocado+"&tipo_posto="+tipo_posto+"&matriz_filial="+matriz_filial,"produto",'resizable=1,scrollbars=yes,width=880,height=550,top=0,left=0');
		janela.focus();
	}

	/* Lista de Produtos */

	var singleSelect 	= true;
	var sortSelect 		= true;
	var sortPick 		= true;

	function initIt() {
		var pickList = document.getElementById("PickList");
		var pickOptions = pickList.options;
		pickOptions[0] = null;
	}

	function addIt() {
		if ($('#produto_referencia').val() == '')
			return false;
		if ($('#produto_descricao').val() == '')
			return false;

		var pickList 				= document.getElementById("PickList");
		var pickOptions 			= pickList.options;
		var pickOLength 			= pickOptions.length;
		pickOptions[pickOLength] 	= new Option($('#produto_referencia').val()+" - "+ $('#produto_descricao').val());
		pickOptions[pickOLength].value = $('#produto_referencia').val();

		$('#produto_referencia').val("");
		$('#produto_descricao').val("");

		if (sortPick) {
			var tempText;
			var tempValue;
			while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
				tempText = pickOptions[pickOLength-1].text;
				tempValue = pickOptions[pickOLength-1].value;
				pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
				pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
				pickOptions[pickOLength].text = tempText;
				pickOptions[pickOLength].value = tempValue;
				pickOLength = pickOLength - 1;
			}
		}

		pickOLength = pickOptions.length;
		$('#produto_referencia').focus();
	}

	function delIt() {
		var pickList = document.getElementById("PickList");
		var pickIndex = pickList.selectedIndex;
		var pickOptions = pickList.options;
		while (pickIndex > -1) {
			pickOptions[pickIndex] = null;
			pickIndex = pickList.selectedIndex;
		}
	}

	function selIt(btn) {
		var pickList = document.getElementById("PickList");
		var pickOptions = pickList.options;
		var pickOLength = pickOptions.length;
		for (var i = 0; i < pickOLength; i++) {
			pickOptions[i].selected = true;
		}
	}

	/* Fim Lista de Produtos */

	var map = {"â":"a","Â":"A","à":"a","À":"A","á":"a","Á":"A","ã":"a","Ã":"A","ê":"e","Ê":"E","è":"e","È":"E","é":"e","É":"E","î":"i","Î":"I","ì":"i","Ì":"I","í":"i","Í":"I","õ":"o","Õ":"O","ô":"o","Ô":"O","ò":"o","Ò":"O","ó":"o","Ó":"O","ü":"u","Ü":"U","û":"u","Û":"U","ú":"u","Ú":"U","ù":"u","Ù":"U","ç":"c","Ç":"C","ñ":"n"};

	function removerAcentos(string) { 
		return string.replace(/[\W\[\] ]/g,function(a) {
			return map[a]||a}) 
	};

	/** select de provincias/estados */
	$(function() {

		$("#pais").change(function() {
			
			var pais = this.value;

			$("#estado option").remove();
			$("#estado optgroup").remove();

			$("#estado").append("<option value=''>TODOS OS ESTADOS</option>");

		<?php if (in_array($login_fabrica,[152,180,181,182])) { ?>

			if (pais == "CO") { 

                $("#estado").append('<optgroup label="Provincias">');
                
                <?php 

                $provincias_CO = getProvinciasExterior("CO");

                foreach ($provincias_CO as $provincia) { ?>

                    var provincia = '<?= $provincia ?>';

                    var semAcento = removerAcentos(provincia);

                    var option = "<option value='" + semAcento + "'>" + provincia + "</option>";

                    $("#estado").append(option);

                <?php } ?>

                $("#estado").append('</optgroup>');
			}

			if (pais == "PE") { 

                $("#estado").append('<optgroup label="Provincias">');
                              
                <?php 

                $provincias_PE = getProvinciasExterior("PE");
                
                foreach ($provincias_PE as $provincia) { ?>

                    var provincia = '<?= $provincia ?>';

                    var semAcento = removerAcentos(provincia);

                    var option = "<option value='" + semAcento + "'>" + provincia + "</option>";

                    $("#estado").append(option);

                <?php } ?>

                $("#estado").append('</optgroup>');
			}

			if (pais == "AR") { 

                $("#estado").append('<optgroup label="Provincias">');
                
                <?php 

                	$provincias_AR = getProvinciasExterior("AR");
                	
                	foreach ($provincias_AR as $provincia) { ?>

                    var provincia = '<?= $provincia ?>';

                    var semAcento = removerAcentos(provincia);

                    var option = "<option value='" + semAcento + "'>" + provincia + "</option>";

                    $("#estado").append(option);

                <?php } ?>

                $("#estado").append('</optgroup>');
			}

			if (pais == "BR") { 

				var array_regioes = [
					"BA,SE,AL,PE,PB,RN,CE,PI,MA,SP",
					"MG,DF,GO,MT,RO,AC,AM,RR,PA,AP,TO",
					"MS,PR,SC,RS,RJ,ES"];

				$("#estado").append('<optgroup label="Regioes">');

				$.each(array_regioes, function( index, regioes ) {
			
					var opRegiao = new Option("option text", regioes);
					$(opRegiao).html(regioes, regioes);
					$("#estado").append(opRegiao);
				});	

				$("#estado").append('</optgroup>');


                $("#estado").append('<optgroup label="Estados">');
                
                <?php foreach ($estados_BR as $sigla => $estado) { ?>

                    var estado = '<?= $estado ?>';
                    var sigla = '<?= $sigla ?>';

                    var option = "<option value='" + sigla + "'>" + estado + "</option>";

                    $("#estado").append(option);

                <?php } ?>

                $("#estado").append('</optgroup>');
			}


		<?php } else { ?>
	      		
			$("#estado").append('<optgroup label="Estados">');
                
            <?php foreach ($array_estados() as $sigla => $estado) { ?>

	            var estado = '<?= $estado ?>';
	            var sigla = '<?= $sigla ?>';

	            var option = "<option value='" + sigla + "'>" + estado + "</option>";

                $("#estado").append(option);

            <?php } ?>

                $("#estado").append('</optgroup>');

		<?php } ?>

		});
	});

</script>

<!-- FORM NOVO -->

<div class="container">

	<?php if($login_fabrica == 95){ ?>
		<div class="alert alert-block"><?php echo traduz("Ao selecionar data de fabricação, todas as OS serão baseadas na data de fabricação somado ao periodo de garantia");?></div><?php } ?>

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
		<b class="obrigatorio pull-right">  *  <?php echo traduz("Campos obrigatórios");?></b>
	</div>

	<form name='frm_lbm' MEthOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '> <?php echo traduz("Parâmetros de Pesquisa");?></div>

		<br />

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'> <?php echo traduz("Data Inicial");?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" id="data_ini" name="data_inicial" class='span12' maxlength="20" value="<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'> <?php echo traduz("Data Final");?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" id="data_fim" name="data_final" class='span12' value="<?=$data_final?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				 <?php echo traduz("Data de Referência");?>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span2'>
				<label class="radio">
					<input type="radio" name="tipo_data" id="optionsRadios1" value="data_digitacao" <?if($tipo_data=="data_digitacao") echo "checked";?>>
					 <?php echo traduz("Digitação");?>
				</label>
			</div>
			<div class='span3'>
				<label class="radio">
					<input type="radio" name="tipo_data" id="optionsRadios1" value="data_abertura" <?if($tipo_data=="data_abertura") echo "checked";?>>
					<?php echo traduz("Abertura");?>
				</label>
			</div>
			<div class='span3'>
					<label class="radio">
					<input type="radio" name="tipo_data" id="optionsRadios1" value="data_fechamento" <?if($tipo_data=="data_fechamento" or $tipo_data=="") echo "checked";?> >
					<?php echo traduz("Fechamento");?>
				</label>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span2'>
				<label class="radio">
					<input type="radio" name="tipo_data" id="optionsRadios1" value="data_finalizada" <?if($tipo_data=="data_finalizada") echo "checked";?> >
					<?php echo traduz("Finalizada");?>
				</label>
			</div>
			<div class='span3'>
				<label class="radio">
					<input type="radio" name="tipo_data" id="optionsRadios1" value="extrato_geracao" <?if($tipo_data=="extrato_geracao") echo "checked";?>>
					<?php echo traduz("Geração de Extrato");?>
				</label>
			</div>
			<div class='span3'>
				<label class="radio">
					<input type="radio" name="tipo_data" id="optionsRadios1" value="extrato_aprovacao" <?if($tipo_data=="extrato_aprovacao") echo "checked";?>>
					<?php echo traduz("Aprovação do Extrato");?>
				</label>
			</div>
			<div class='span2'></div>
		</div>
		<?php if($login_fabrica == 24){ ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span2'>
					<label class="radio">
				        <input type="radio" name="matriz_filial" id="matriz_filial" value="02" <?if($matriz_filial=="02" OR ($matriz_filial) == 0 ) echo "checked";?> >
				         <?php echo traduz("Matriz - 02");?>
				    </label>
				</div>
				<div class='span3'>
					<label class="radio">
				        <input type="radio" name="matriz_filial" id="matriz_filial" value="04" <?if($matriz_filial=="04") echo "checked";?>>
				         <?php echo traduz("Filial - 04");?>
				    </label>
				</div>
				<div class='span2'></div>
			</div>
		<?php } ?>
		<?php
		if($login_fabrica == 20){
		?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<label class="radio">
					<input type="radio" name="tipo_data" id="optionsRadios1" value="extrato_exportacao" <?if($tipo_data=="extrato_exportacao") echo "checked";?> >
					<?php echo traduz("Data Pagamento");?>
				</label>
			</div>
			<div class='span2'></div>
		</div>
		<?php
		}
		?>

		<?php
		if($login_fabrica == 95){
		?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<label class="radio">
					<input type="radio" name="tipo_data" id="optionsRadios1" value="data_fabricacao" <?if($tipo_data=="data_fabricacao") echo "checked";?> >
					 <?php echo traduz("Data Fabricacao");?>
				</label>
			</div>
			<div class='span2'></div>
		</div>
<?php
		}
		if ($login_fabrica == 42) {
?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<label class="checkbox">
					<input type="checkbox" name="os_cortesia" id="os_cortesia" value="t" <?=($os_cortesia == 't') ? "checked" : ""?> >
					 <?php echo traduz("Solicitação de Cortesia Comercial");?>
				</label>
			</div>
			<div class='span2'></div>
		</div>

<?php
		}
		if(in_array($login_fabrica, array(1,164))){

			$sqlMarca = "
				SELECT  marca,
						nome
				FROM    tbl_marca
				WHERE   fabrica = $login_fabrica;
			";
			$resMarca = pg_query($con,$sqlMarca);
			$marcas = pg_fetch_all($resMarca);
?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='marca'> <?php echo traduz("Marca");?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<select name="marca" id="marca">
								<option value="">&nbsp;</option>
<?
							foreach($marcas as $chave => $valor){
?>
								<option value="<?=$valor['marca']?>" <?=($valor['marca'] == $marca) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
							}
?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<?php
			if ($login_fabrica == 164) {
			?>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for='origem_produto'><?php echo traduz("Origem do Produto");?></label>
						<div class='controls controls-row'>
							<div class='span12'>
								<select name="origem_produto" id="origem_produto">
									<option value="">&nbsp;</option>
									<option value="NAC" <?=($_POST["origem_produto"] == "NAC") ? "selected" : ""?> ><?php echo traduz("Nacional");?></option>
									<option value="IMP" <?=($_POST["origem_produto"] == "IMP") ? "selected" : ""?> ><?php echo traduz("Importado");?></option>
									<option value="USA" <?=($_POST["origem_produto"] == "USA") ? "selected" : ""?> ><?php echo traduz("Importado USA");?></option>
									<option value="ASI" <?=($_POST["origem_produto"] == "ASI") ? "selected" : ""?> ><?php echo traduz("Importado Asia");?></option>
								</select>
							</div>
						</div>
					</div>
				</div>
			<?php
			}
			?>
		</div>
<?
		}

		if($login_fabrica == 158){
?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span8'>
					<?php echo traduz("Tipo de Atendimento");?>
				</div>
				<div class='span2'></div>
			</div>

			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span3'>
					<label class="radio">
					<input type="radio" name="tipo_atendimento" id="optionsRadios1" value="dentro_garantia" <?if($tipo_atendimento=="dentro_garantia") echo "checked";?>>
					<?php echo traduz("Dentro de Garantia");?>
					</label>
				</div>
				<div class='span3'>
					<label class="radio">
					<input type="radio" name="tipo_atendimento" id="optionsRadios1" value="fora_garantia" <?if($tipo_atendimento=="fora_garantia") echo "checked";?>>
					<?php echo traduz("Fora de Garantia");?>
					</label>
				</div>
				<div class='span2'></div>
			</div>
		<?php
		}

		if ($login_fabrica == 117) { ?>
			<div class='row-fluid'>
					<div class='span2'></div>
					<div class='span4'>
							<div class='control-group'>
									<label class='control-label' for='macroLinha'><?php echo traduz("Linha");?></label>
									<div class='controls controls-row'>
											<div class='span12 input-append'>
													<select name="macro_linha" id="macro_linha">
															<option value=""><?php echo traduz("Escolha");?></option>
															<?php
										$sql = "SELECT
													DISTINCT tbl_macro_linha.macro_linha,
													tbl_macro_linha.descricao
												FROM tbl_macro_linha
													JOIN tbl_macro_linha_fabrica ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
												WHERE  tbl_macro_linha_fabrica.fabrica = {$login_fabrica}
													AND     tbl_macro_linha.ativo = TRUE
												ORDER BY tbl_macro_linha.descricao;";
										$res = pg_query ($con,$sql);

										if(pg_num_rows($res)>0){
											for($i=0;pg_num_rows($res)>$i;$i++){
												$xmacroLinha = pg_fetch_result($res,$i,macro_linha);
												$xmacroLinhaNome = pg_fetch_result($res,$i,descricao);

												$selected = ($_REQUEST["macro_linha"] == $xmacroLinha) ? "selected" : ""; ?>
												<option value="<?echo $xmacroLinha;?>" <?=$selected?> ><?echo $xmacroLinhaNome;?></option>
											<?php }
										} ?>
													</select>
											</div>
									</div>
							</div>
					</div>
					<div class='span4'>
						<div class='control-group'>
								<label class='control-label' for='linha'><?php echo traduz("Macro - Família");?></label>
								<div class='controls controls-row'>
										<div class='span12'>
											<input type="hidden" name="linha_aux" id="linha_aux" value="<?=implode(',', $_REQUEST['linha']); ?>">
												<select name="linha[]" id="linha" multiple="multiple" class='span12'>
								</SELECT>
										</div>
								</div>
						</div>
				</div>
				<div class='span2'></div>
			</div>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
						<div class='control-group'>
								<label class='control-label' for='familia'><?php echo traduz("Família");?></label>
								<div class='controls controls-row'>
										<div class='span12 input-append'>
												<input type="hidden" name="familia_aux" id="familia_aux" value="<?=$_REQUEST['familia']; ?>">
												<select name="familia" id="familia">
														<option value=""><?php echo traduz("Escolha");?></option>
														<?php
									if (!in_array($login_fabrica, array(117))) {
										$sql_macro = "SELECT tbl_familia.familia,
															 tbl_familia.descricao
														FROM tbl_familia
														WHERE tbl_familia.fabrica = $login_fabrica
															AND     tbl_familia.ativo = TRUE
															ORDER BY tbl_familia.descricao;";
										$res_macro = pg_query($con,$sql_macro);
										if(pg_num_rows($res_macro)>0){
											for($i=0;pg_num_rows($res_macro)>$i;$i++){
												$xfamilia = pg_fetch_result($res_macro,$i,familia);
												$xdescricao = pg_fetch_result($res_macro,$i,descricao);

												$selected = ($_REQUEST["familia"] == $xfamilia) ? "selected" : ""; ?>
												<option value="<?echo $xfamilia;?>" <?=$selected?> ><?echo $xdescricao;?></option>
											<?php }
										}
									} ?>
												</select>
										</div>
								</div>
						</div>
				</div>
				<div class='span6'></div>
		</div>
		<?php
		} else { ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='linha'><?php echo traduz("Linha");?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<?
							$sql_linha = "SELECT
												linha,
												nome
										  FROM tbl_linha
										  WHERE tbl_linha.fabrica = $login_fabrica
										  ORDER BY tbl_linha.nome ";
							$res_linha = pg_query($con, $sql_linha); ?>
							<select name="linha[]" id="linha" multiple="multiple" class='span12'>
									<?php
									if($login_fabrica == 15){
										echo "<option value='LAVADORAS LE'>";
										echo "LAVADORAS LE</option>";
										echo "<option value='LAVADORAS LS'>";
										echo "LAVADORAS LS</option>";
										echo "<option value='LAVADORAS LX'>";
										echo "LAVADORAS LX</option>";
										echo "<option value='IMPORTAÇÃO DIRETA WAL-MART'>";
										echo "IMPORTAÇÃO DIRETA WAL-MART</option>";
										echo "<option value='Purificadores / Bebedouros - Eletrônicos'>";
										echo "Purificadores / Bebedouros - Eletrônicos</option>";
									}

									$selected_linha = array();
									foreach (pg_fetch_all($res_linha) as $key) {
										if(isset($linha)){
											foreach ($linha as $id) {
												if ( isset($linha) && ($id == $key['linha']) ){
													$selected_linha[] = $id;
												}
											}
										} ?>

										<option value="<?php echo $key['linha']?>" <?php if( in_array($key['linha'], $selected_linha)) echo "SELECTED"; ?> >

											<?php echo $key['nome']?>

										</option>
							  <?php } ?>
								</select>

						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='familia'><?php echo traduz("Família");?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<select name="familia" id="familia">
								<?
								$sql = "SELECT  *
										FROM    tbl_familia
										WHERE   tbl_familia.fabrica = $login_fabrica
										ORDER BY tbl_familia.descricao;";
								$res = pg_query ($con,$sql);

								if (pg_num_rows($res) > 0) {
									echo "<option value=''>ESCOLHA</option>\n";
									for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
										$aux_familia   = trim(pg_fetch_result($res,$x,familia));
										$aux_descricao = trim(pg_fetch_result($res,$x,descricao));

										echo "<option value='$aux_familia'";
										if ($familia == $aux_familia){
											echo " SELECTED ";
											$mostraMsgLinha = "<br> da FAMÍLIA $aux_descricao";
										}
										echo ">$aux_descricao</option>\n";
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<?php
		}
		if(in_array($login_fabrica, array(35,50))){

			if (count($lista_produtos) > 0){
				$display_multi_produto = "";
				$display_um            = "";
				$display_multi         = " checked ";
			}else{
				$display_um_produto    = "";
				$display_multi_produto = "style='display:none';";
				$display_um            = " checked ";
				$display_multi         = "";
			}

		?>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span2'>
				<strong> <?php echo traduz("Selecione:");?></strong>
			</div>
			<div class='span2'>
				<label class="radio">
					<input type="radio" name="radio_qtde_produtos" id="optionsRadios1" value="um" onclick="toggleProd(this.value)" <?=$display_um?> >
					<?php echo traduz("Um Produto");?>
				</label>
			</div>
			<div class='span4'>
				<label class="radio">
					<input type="radio" name="radio_qtde_produtos" id="optionsRadios1" value="muitos" onclick="toggleProd(this.value)" <?=$display_multi?> >
					 <?php echo traduz("Vários Produtos");?>
				</label>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='produto_referencia'><?php echo traduz("Ref. Produto");?></label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='produto_descricao'><?php echo traduz("Descrição Produto");?></label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>


		<!-- Multiplod Produtos  -->

		<div id="muitos" <?=$display_multi_produto;?>>

			<!-- Botão de Adicionar Produtos -->
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span8' style="text-align: right;">
					(<?php echo traduz("Selecione o produto e clique em Adicionar");?> )&nbsp;
					<button type="button" class="btn" name="adicionar_produto" id="adicionar_produto" onclick="addIt()" style="margin-right: 20px; margin-bottom: 10px;"><i class="icon-plus"></i> Adicionar Produto</button>
				</div>
				<div class='span2'></div>
			</div>

			<!-- Lista de Produtos -->
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span8'>
					<select multiple size="5" style="width: 96%;" id="PickList" name="PickList[]">
					<?php
						if (count($produto_lista) > 0){
							for ($i = 0; $i < count($produto_lista); $i++){
								$linha_prod = $produto_lista[$i];
								echo "<option value='".$linha_prod[1]."'>".$linha_prod[1]." - ".$linha_prod[2]."</option>";
							}
						}
					?>
					</select>
				</div>
				<div class='span2'></div>
			</div>

			<!-- Botão de Remover Produtos -->
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span8' style="text-align: right;">
					<button type="button" onclick="delIt()" class="btn" style="margin-right: 20px; margin-bottom: 10px; margin-top: 10px;"><i class="icon-minus"></i>  <?php echo traduz("Remover Produto");?></button>
				</div>
				<div class='span2'></div>
			</div>

		</div>

		<?php
		}else{
		?>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='produto_referencia'><?php echo traduz("Ref. Produto");?></label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='produto_descricao'><?php echo traduz("Descrição Produto");?></label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<?php } ?>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='pais'><?php echo traduz("País");?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<select name="pais" id="pais">
								<option value="" selected>TODOS OS PAÍSES</option>	
								<?
								$sql = "SELECT  *
										FROM    tbl_pais
										where america_latina is TRUE
										ORDER BY tbl_pais.nome;";
								$res = pg_query ($con,$sql);

								if (pg_num_rows($res) > 0) {
									//if(strlen($pais) == 0 ) $pais = 'BR';

									for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
										$aux_pais  = trim(pg_fetch_result($res,$x,pais));
										$aux_nome  = trim(pg_fetch_result($res,$x,nome));

										echo "<option value='$aux_pais'";
										if ($pais == $aux_pais){
											echo " SELECTED ";
											$mostraMsgPais = "<br> do PAÍS $aux_nome";
										}
										echo ">$aux_nome</option>\n";
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='estado'> <?php echo traduz("Por Região");?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<select name="estado" id="estado">
								<option value""></option>
								<?php
								$sigla = $_POST['estado'];

						 		if (!empty($_POST['pais'])) {
									$paisForm = $_POST['pais'];
								} else {
									$paisForm = $pais;
								}

								$estados = $array_estados($paisForm);

								foreach ($estados as $uf => $estado) {
									$selectedEstado = ($sigla == $uf) ? "selected" : ""; ?>
								 	<option value="<?= $uf ?>" <?= $selectedEstado; ?>><?= $estado ?></option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<?php if (in_array($login_fabrica, array(169,170))) {?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='status_os'><?php echo traduz("Status OS");?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<select name="status_os[]" id="status_os" multiple="multiple" class='span12'>
							<?php
								$sql_status   = "SELECT status_checkpoint,descricao,cor FROm tbl_status_checkpoint WHERE status_checkpoint IN (0,1,2,3,4,9,14,30) ORDER BY descricao ASC";
								$res_status   = pg_query($con,$sql_status);
								$total_status = pg_num_rows($res_status);
								for ($i=0; $i < $total_status; $i++) {

									$id_status        = pg_fetch_result($res_status,$i,'status_checkpoint');
									$cor_status       = pg_fetch_result($res_status,$i,'cor');
									$descricao_status = pg_fetch_result($res_status,$i,'descricao');

									$selected = (in_array($id_status, $status_checkpoint_pesquisa)) ? " selected ": " ";

									echo "<option value='$id_status' $selected > $descricao_status</option>";
								}
							?>
							</select>

						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='situacao_os'><?php echo traduz("Situação OS");?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<select name="situacao_os" id="situacao_os" class='span12'>
								<option value=''>Escolha ...</option>
								<option value='Aberta' <?php echo ($situacao_os == "Aberta") ? " selected ": " ";?>><?php echo traduz("Aberta");?></option>
								<option value='Finalizada' <?php echo ($situacao_os == "Finalizada") ? " selected ": " ";?>><?php echo traduz("Finalizada");?></option>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<?php }?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'> <?php echo traduz("Cod. Posto");?></label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="codigo_posto" name="codigo_posto" class='span12' maxlength="20" value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='descricao_posto'>
						<?php
						echo traduz("Nome Posto");
						if ($login_fabrica == 40) { ?>
							&nbsp; &nbsp;
							<label class="checkbox" for="exceto_posto">
								( <input type='checkbox' name='exceto_posto' value='exceto_posto' <?php if(strlen($exceto_posto) > 0) echo "checked"; ?> >  <?php echo traduz("Exceto este Posto");?>)
							</label>
						<?php } ?>
					</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="descricao_posto" name="posto_nome" class='span12' value="<? echo $posto_nome; ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='tipo_posto'> <?php echo traduz("Tipo Posto");?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<select name="tipo_posto[]" id="tipo_posto" multiple="multiple" class='span12'>
								<?php
								$sql = "SELECT
											tipo_posto,
											descricao
										FROM tbl_tipo_posto
										WHERE fabrica = {$login_fabrica}";

								$res = pg_query($con, $sql);
								$qtd = pg_num_rows($res);
								if ($qtd > 0)
									for ($count=0; $count < $qtd; $count++) {
										$tipo_posto = pg_fetch_result($res, $count, tipo_posto);
										$descricao  = pg_fetch_result($res, $count, descricao);

										$selected = (in_array($tipo_posto, $xtipo_posto)) ? "selected" : "";
										echo "<option value='{$tipo_posto}' $selected>{$descricao}</option>";
									}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<?php if($login_fabrica == 151){ ?>			
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for='centro_distribuicao'>Centro Distribuição</label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<select name="centro_distribuicao" id="centro_distribuicao">
									<option value="mk_vazio" name="mk_vazio" <?php echo ($centro_distribuicao == "mk_vazio") ? "SELECTED" : ""; ?>>ESCOLHA</option>
									<option value="mk_nordeste" name="mk_nordeste" <?php echo ($centro_distribuicao == "mk_nordeste") ? "SELECTED" : ""; ?>>MK Nordeste</option>
									<option value="mk_sul" name="mk_sul" <?php echo ($centro_distribuicao == "mk_sul") ? "SELECTED" : ""; ?>>MK Sul</option>	
								</select>
							</div>							
						</div>						
					</div>					
				</div>				
			<?php } 
			if ($login_fabrica == 175){ ?>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='defeito_constatado_grupo'> <?php echo traduz("Grupo Defeito Constatado");?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<select name="defeito_constatado_grupo[]" multiple="multiple" id="defeito_constatado_grupo" class='span12'>
								<?php
								$sql = "SELECT
											tbl_defeito_constatado_grupo.defeito_constatado_grupo,
											tbl_defeito_constatado_grupo.descricao
										FROM tbl_defeito_constatado_grupo
										WHERE tbl_defeito_constatado_grupo.fabrica = {$login_fabrica}";
								$res = pg_query($con, $sql);
								$qtd = pg_num_rows($res);
								if ($qtd > 0)
									for ($count=0; $count < $qtd; $count++) {
										$defeito_constatado_grupo = pg_fetch_result($res, $count, defeito_constatado_grupo);
										$descricao  = pg_fetch_result($res, $count, descricao);

										$selected = (in_array($defeito_constatado_grupo, $xdefeito_constatado_grupo)) ? "selected" : "";
										echo "<option value='{$defeito_constatado_grupo}' $selected>{$descricao}</option>";
									}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<?php } ?>
		</div>

		<?php
		if($login_fabrica == 7){
			?>

			<div class='row-fluid'>
				<div class='span2'></div>

				<div class='span8'>
					<div class='control-group'>
						<label class='control-label' for='classificacao'> <?php echo traduz("Classificação de OS");?></label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<select name="classificacao" id="classificacao">
									<?
										$sql = "SELECT  *
												FROM    tbl_classificacao_os
												WHERE   fabrica = $login_fabrica
												AND ativo is true;";
										$res = pg_query ($con,$sql);

										if (pg_num_rows($res) > 0) {
											echo "<option></option>";
											for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
												$aux_classificacao   = trim(pg_fetch_result($res,$x,classificacao_os));
												$aux_descricao = trim(pg_fetch_result($res,$x,descricao));

												echo "<option value='$aux_classificacao'";
												if ($classificacao == $aux_classificacao){
													echo " SELECTED ";
													$mostraMsgLinha .= "<br> da CLASSIFICAÇÃO $aux_descricao";
												}
												echo ">$aux_descricao</option>\n";
											}
										}
									?>
								</select>
							</div>
						</div>
					</div>
				</div>

				<div class='span2'></div>
			</div>

			<?php 
		}
		?>

		<br />

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<strong> <?php echo traduz("Tipo do Arquivo para Download");?></strong>
			</div>
			<div class='span2'>
				<label class="radio">
					<input type="radio" name="formato_arquivo" id="optionsRadios1" value="XLS" <?if($formato_arquivo == 'XLS')echo "checked";?>>
					XLS
				</label>
			</div>
			<div class='span2'>
				<label class="radio">
					<input type="radio" name="formato_arquivo" id="optionsRadios1" value="CSV" <?if($formato_arquivo != 'XLS')echo "checked";?>>
					CSV
				</label>
			</div>
			<div class='span2'></div>
		</div>

		<?php
		if($login_fabrica == 117){
			?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<strong> <?php echo traduz("Produtos Trocados");?></strong>
				</div>
				<div class='span2'>
					<label class="checkbox" for="filtro_produto_trocado">
						<input type='checkbox' name='filtro_produto_trocado' value='true' <?php if(strlen($filtro_produto_trocado) > 0) echo "checked"; ?>>
					</label>
				</div>
				<div class='span2'></div>
			</div>
			<?php
		}
		?>

			<p>
				<br/><!--  -->
				<button class='btn' id="btn_acao" type="button"  onclick="<?php if(in_array($login_fabrica, array(35,50))){ echo 'selIt();'; }  ?>  submitForm($(this).parents('form'),'pesquisar'); "><?php echo traduz("Pesquisar");?></button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
			</p>

			<br/>
	</form>

<p>

<?php

	if($listar == "ok"){

		if(strlen($codigo_posto) > 0){
			$sql = "SELECT  posto
					FROM    tbl_posto_fabrica
					WHERE   fabrica      = $login_fabrica
					AND     codigo_posto = '$codigo_posto';";
			$res = pg_query ($con,$sql);
			if (pg_num_rows($res) > 0) $posto = trim(pg_fetch_result($res,0,posto));
		}

		/* Condições */
		if (strlen($linha) > 0 || count($linha) > 0 ) {

			$condJoinLinha = " IN (";
			for($i = 0; $i < count($linha); $i++){
				if($i == count($linha)-1 ){
					$condJoinLinha .= $linha[$i].")";
				}else {
					$condJoinLinha .= $linha[$i].", ";
				}
			}
			$cond_1 = " AND bi_os.linha {$condJoinLinha} ";

		}
			if (strlen($estado_filtro) > 0) $cond_2 = " AND   bi_os.estado  = '$estado_filtro' ";
		if(in_array($login_fabrica, array(152,180,181,182)) and strlen($estado_filtro) > 1){
					$estado_filtro = str_replace(",", "','",$estado_filtro);
					$cond_2 = " AND tbl_posto_fabrica.contato_estado IN ('$estado_filtro')";
					$join_estado= "join tbl_posto_fabrica on tbl_posto_fabrica.posto = bi_os.posto and tbl_posto_fabrica.fabrica = bi_os.fabrica";
			}
			if (strlen($posto) > 0) $cond_3 = " AND   bi_os.posto   = $posto ";
		if (strlen($posto) > 0 AND !empty($exceto_posto)) 	$cond_3 = " AND   NOT (bi_os.posto   = $posto) ";
		if (strlen($produto) > 0) $cond_4 = " AND   bi_os.produto = $produto "; // HD 2003
		if (strlen($pais) > 0) $cond_6 = " AND   bi_os.pais    = '$pais' ";
		if (strlen($marca) > 0){
			$cond_7 = (in_array($login_fabrica, array(1,164))) ? " AND   tbl_produto.marca   = $marca " : " AND   bi_os.marca   = $marca ";
		}
		if (strlen($familia) > 0) $cond_8 = " AND   bi_os.familia  = $familia ";
		if (strlen($lista_produtos) > 0){
			$cond_10= " AND   bi_os.produto in ( $lista_produtos) ";
			$cond_4 = "";
		}
		if (strlen($tipo_data) == 0) $tipo_data = 'data_fechamento';
		if (strlen($aux_data_inicial) > 0 AND strlen($aux_data_final) > 0 AND $tipo_data != "data_fabricacao"){
			$cond_9 = "AND   bi_os.$tipo_data BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
		}

		if($login_fabrica == 20 and $pais != 'BR'){
			$produto_descricao   = "tbl_produto_idioma.descricao ";
			$join_produto_idioma = " LEFT JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto and tbl_produto_idioma.idioma = 'ES' ";
		}else{
			$produto_descricao   = "tbl_produto.descricao ";
			$join_produto_idioma = " ";
		}

		if($login_fabrica == 3 /*OR $login_fabrica == 15*/){ #HD49228
			$produto_marca = " tbl_marca.nome                AS m_nome     , ";
			$join_marca    = " LEFT JOIN tbl_marca   ON tbl_marca.marca   = bi_os.marca";
			$order_marca   = ", m_nome ";
		}

		$cond_13 = "";
		if (in_array($login_fabrica, array(169,170))) {
			if (!empty($status_checkpoint_pesquisa)) {
				$cond_13 .= " AND bi_os.status_checkpoint IN (".implode(",", $status_checkpoint_pesquisa).")";
			}
			if (strlen($situacao_os) > 0) {
				if ($situacao_os == "Finalizada") {
					$cond_13 .= " AND bi_os.data_finalizada IS NOT NULL AND bi_os.excluida = 'f'";
				}
				if ($situacao_os == "Aberta") {
					$cond_13 .= " AND bi_os.data_finalizada IS NULL AND bi_os.data_fechamento IS NULL AND bi_os.excluida = 'f'";
				}
			}
		}

		/* Join tipo de posto */
		if (is_array($xtipo_posto) AND count($xtipo_posto) > 0) {
			if (empty($join_estado)) {
				$joinTipoPosto = "LEFT JOIN tbl_posto_fabrica USING(posto)";
			}
			$joinTipoPosto .= " LEFT JOIN tbl_tipo_posto USING(tipo_posto) ";
			$condTipoPosto  = " AND tbl_tipo_posto.tipo_posto IN(".implode(',', $xtipo_posto).")";
		}

		if ($login_fabrica == 175){
			if (!empty($xdefeito_constatado_grupo)){
				$cond_defeito_contatado_grupo = " AND tbl_defeito_constatado_grupo.defeito_constatado_grupo IN (".implode(',', $xdefeito_constatado_grupo).")";
			}
			
			$campo_defeito_contatado_grupo_descricao  = ", tbl_defeito_constatado_grupo.descricao AS defeito_contatado_grupo_descricao";
			$join_defeito_contatado_grupo = "
					LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = bi_os.defeito_constatado
						AND tbl_defeito_constatado.fabrica = $login_fabrica
					LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo
						AND tbl_defeito_constatado_grupo.fabrica = $login_fabrica
				";
			$grupo_defeito_constatado_grupo = ",defeito_contatado_grupo_descricao";
		}

		/* 15 */
		if($login_fabrica == 15){

			if(strlen($msg_erro) == 0){
				list($di, $mi, $yi) = explode("/", $data_inicial);
				if(!checkdate($mi ,$di, $yi))
					$msg_erro = traduz("Data Inválida");
			}

			if(strlen($msg_erro) == 0){
				list($df, $mf, $yf) = explode("/", $data_final);
				if(!checkdate($mf, $df, $yf))
					$msg_erro = traduz("Data Inválida");
			}

			if(strlen($msg_erro) == 0){
				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";
			}

			$sqldata   = "SELECT '$aux_data_final'::date - '$aux_data_inicial'::date as qtde_dias";
			$resdata   = pg_query($con, $sqldata);
			$qtde_dias = pg_fetch_result($resdata,0,qtde_dias);
			$mes_final = substr($data_final, 3, 2);
			$mes_inicial = substr($data_inicial, 3, 2);
		}

		/* 95 */
		if($login_fabrica == 95 AND $tipo_data == "data_fabricacao"){
			$condFacricadoCount 	= " ,
									(SELECT COUNT(1)
										FROM tbl_numero_serie AS tns
										WHERE tns.fabrica = $login_fabrica
										AND tns.data_fabricacao BETWEEN '$aux_data_inicial' AND '$aux_data_final'
										AND tns.produto = tbl_produto.produto) AS total_fabricado
									";
			$condFacricadoJoin  	= " JOIN tbl_numero_serie ON (tbl_produto.produto = tbl_numero_serie.produto AND bi_os.serie = tbl_numero_serie.serie) ";
			$cond_11  				= " AND tbl_numero_serie.data_fabricacao BETWEEN '$aux_data_inicial' AND '$aux_data_final' ";
			$cond_12 				= " AND bi_os.data_digitacao <= tbl_numero_serie.data_fabricacao + interval '$qtde_meses_leadrship month'";
			$group_produto 			= ", tbl_numero_serie.produto";
		}

		/* 15 */
		/* gera relatório detalhado */
		if(($login_fabrica == 15 || $login_fabrica == 147) AND $qtde_dias < 31){
			$sql_base = "SELECT  bi_os.os AS ocorrencia
						FROM bi_os
						JOIN tbl_produto ON tbl_produto.produto = bi_os.produto
						JOIN tbl_linha   ON tbl_linha.linha   = bi_os.linha
						AND tbl_linha.fabrica = bi_os.fabrica
						JOIN tbl_familia ON tbl_familia.familia = bi_os.familia
						$join_marca
						WHERE bi_os.fabrica = $login_fabrica
						AND bi_os.excluida IS NOT TRUE
						$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10
						ORDER BY ocorrencia DESC";
			$res_base = pg_query ($con,$sql_base);

			if(pg_num_rows($res_base) > 0){
				$relatorio_detalhado = 't';

				// echo "<div class='alert alert-block'>Resultado de pesquisa entre os dias $data_inicial e $data_final #$mostraMsgLinha $mostraMsgEstado $mostraMsgPais</div>";

				$data = date ("d-m-Y-H-i");

				$arquivo_nome_c     = "relatorio_detalhado_os-$login_fabrica-$data.xls";

						$path             = "/www/assist/www/admin/xls/";
						$path_tmp         = "/tmp/assist/";

				$arquivo_completo     = $path.$arquivo_nome_c;
				$arquivo_completo_tmp = $path_tmp.$arquivo_nome_c;

				echo `rm $arquivo_completo_tmp `;
				echo `rm $arquivo_completo_tmp.zip `;
				echo `rm $arquivo_completo.zip `;
				echo `rm $arquivo_completo `;

				$fp = fopen ($arquivo_completo_tmp,"w");

				$sql2 = "SELECT  qtde_item_os
						FROM    tbl_fabrica
						WHERE   fabrica = $login_fabrica;";
				$res2 = pg_query($con,$sql2);

				$qtde_item = '';

				if (pg_num_rows($res2) > 0) $qtde_item = pg_fetch_result ($res2,0,qtde_item_os);
				if (strlen ($qtde_item) == 0) $qtde_item = 5;

				$itens = "";

				for ($i=0; $i < $qtde_item; $i++){
					$itens .= "Peça \t Qtde \t Defeito \t Serviço Realizado \t";
				}
				/* fputs ($fp, "Linha \t Familia \t Produto Referência \t OS \t Série \t Fábrica \t Versão \t Mês Fabricação \t Ano Fabricação \t Número Sequêncial \t Mês NF Compra \t Ano NF Compra \t Diferença entre fabricação e compra (meses) \t Mês abertura OS  \t Ano abertura OS \t Diferença entre compra e OS (meses) \t Mes Digitação \t Ano Digitação \t Mes Fechamento \t Ano Fechamento \t Diferenca entre abertura e fechamento \t Consumidor Revenda \t Nome Revenda \t Nome Posto \t Defeito Reclamado \t Defeito Constatado \t Solução\t $itens \r\n"); */

				set_time_limit(900);

				fputs ($fp,"<html>");
				fputs ($fp,"<head>");
				fputs ($fp,"<title>".traduz("RELATORIO ENGENHARIA OS (BI) - ")." $data");
				fputs ($fp,"</title>");
				fputs ($fp,"<meta name='Author' content='TELECONtrOL NETWORKING LtdA'>");
				fputs ($fp,"</head>");
				fputs ($fp,"<body>");

				fputs ($fp,"<table width='100%' align='left' border='1' class='tabela' cellpadding='2' cellspacing='1'>");
				fputs ($fp,"<tr >");

				fputs ($fp,"<td bgcolor=#C0C0C0 align=center><b>".traduz("Linha")."</b></td>");
				fputs ($fp,"<td bgcolor=#C0C0C0 align=center><b>".traduz("Família")."</b></td>");


				if ($login_fabrica == 171) {
					fputs ($fp,"<td bgcolor=#C0C0C0><b>".traduz("Referência FN")."</b></td>");
				}


				fputs ($fp,"<td bgcolor=#C0C0C0 align=center><b>".traduz("Código do Produto")."</b></td>");

				if($login_fabrica == 15) {
					fputs ($fp,"<td bgcolor=#C0C0C0><b>".traduz("Descrição do Produto")."</b></td>");
				}

				fputs ($fp,"<td bgcolor=#C0C0C0 align=center><b>".traduz("Nº da OS")."</b></td>");
				fputs ($fp,"<td bgcolor=#FF0000><b>".traduz("Nº de Série")."</b></td>");

				if($login_fabrica == 15) {
					fputs ($fp,"<td bgcolor=#FF0000><b>".traduz("Valor do Produto")."</b></td>");
				}

				if ($login_fabrica != 147) {
					fputs ($fp,"<td bgcolor=#FF0000><b>".traduz("Fábrica")."</b></td>");
					fputs ($fp,"<td bgcolor=#FF0000><b>".traduz("Versão")."</b></td>");
				}
				fputs ($fp,"<td bgcolor=#FF0000 align=center><b>".traduz("Mês fabricação")."</b></td>");
				fputs ($fp,"<td bgcolor=#FF0000 align=center><b>".traduz("Ano fabricação")."</b></td>");
				if ($login_fabrica != 147) {
					fputs ($fp,"<td bgcolor=#FF0000 align=center><b>".traduz("Número sequêncial")."</b></td>");
				}
				if($login_fabrica == 15){ #HD 409707
					fputs ($fp,"<td bgcolor=#FFFF00 align=center><b>".traduz("Dia NF compra")."</b></td>");
				}

				fputs ($fp,"<td bgcolor=#FFFF00 align=center><b>".traduz("Mês NF compra")."</b></td>");
				fputs ($fp,"<td bgcolor=#FFFF00 align=center><b>".traduz("Ano NF compra")."</b></td>");

				if($login_fabrica == 15){ #HD 409707
					fputs ($fp,"<td nowrap bgcolor=#FFFF00 align=center><b>".traduz("Diferença entre Fabricação e Compra (dias)")."</b></td>");
				}

				fputs ($fp,"<td nowrap bgcolor=#FFFF00 align=center><b>".traduz("Diferença entre Fabricação e Compra (meses)")."</b></td>");

				if($login_fabrica == 15){ #HD 409707
					fputs ($fp,"<td bgcolor=#00FF40 align=center><b>".traduz("Dia abertura OS")."</b></td>");
				}
				fputs ($fp,"<td bgcolor=#00FF40 align=center><b>".traduz("Mês abertura OS")."</b></td>");
				fputs ($fp,"<td bgcolor=#00FF40 align=center><b>".traduz("Ano abertura OS")."</b></td>");

				if($login_fabrica == 15){ #HD 409707
					fputs ($fp,"<td bgcolor=#00FF40 align=center><b>".traduz("Diferença entre compra e OS (dias)")."</b></td>");
				}

				fputs ($fp,"<td bgcolor=#00FF40 align=center><b>".traduz("Diferença entre compra e OS (meses)")."</b></td>");

				if($login_fabrica == 15){ #HD 409707
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Dia Digitação")."</b></td>");
				}

				fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Mes Digitação")."</b></td>");
				fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Ano Digitação")."</b></td>");

				if($login_fabrica == 15){ #HD 409707
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Dia Fechamento")."</b></td>");
				}
				fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Mes Fechamento")."</b></td>");
				fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Ano Fechamento")."</b></td>");

				if($login_fabrica == 15){ #HD 409707
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Diferença entre abertura e fechamento (dias)")."</b></td>");
				}

				fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Diferença entre abertura e fechamento (meses)")."</b></td>");

				if ($login_fabrica == 15){ #HD 409707
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Dia Finalização")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Mês Finalização")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Ano Finalização")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Diferença entre digitação e finalização (dias)")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Diferença entre digitação e finalização (meses)")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Valor pago na OS")."</b></td>");
					fputs ($fp,"<td bgcolor=#F7F700 align=center><b>".traduz("Km rodado")."</b></td>");
					fputs ($fp,"<td bgcolor=#F7F700 align=center><b>".traduz("Valor pago Km total")."</b></td>");
				}

				fputs ($fp,"<td bgcolor=#FFC68C align=center><b>".traduz("Consumidor Revenda")."</b></td>");
				fputs ($fp,"<td bgcolor=#FFC68C align=center><b>".traduz("Revenda Nome")."</b></td>");

				if($login_fabrica == 15) {
					fputs ($fp,"<td bgcolor=#FFC68C align=center><b>".traduz("Revenda CNPJ")."</b></td>");
					fputs ($fp,"<td bgcolor=#FFC68C align=center><b>".traduz("Base CNPJ")."</b></td>");
				}

				fputs ($fp,"<td bgcolor=#FFC68C align=center><b>".traduz("Posto Autorizado")."</b></td>");
				fputs ($fp,"<td bgcolor=#FFC68C><b>".traduz("Cidade")."</b></td>");
				fputs ($fp,"<td bgcolor=#FFC68C><b>".traduz("Estado")."</b></td>");
				fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Defeito Reclamado")."</b></td>");
				fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Defeito Constatado")."</b></td>");
				if ($login_fabrica != 147) {
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Solução")."</b></td>");
				}

				if($login_fabrica == 15){

					$qtde_max_pecas_os = 0;

					for ($aa=0; $aa<pg_num_rows($res_base); $aa++){
						$os_base_a   =  trim(pg_fetch_result($res_base,$aa,'ocorrencia'));
						$sql_peca_a = " SELECT  count(tbl_peca.referencia)
										FROM    tbl_peca
										JOIN    tbl_os_item     ON  tbl_peca.peca           = tbl_os_item.peca
																AND tbl_os_item.fabrica_i   = tbl_peca.fabrica
										JOIN    tbl_os_produto  ON  tbl_os_item.os_produto  = tbl_os_produto.os_produto
										WHERE   tbl_peca.fabrica    = $login_fabrica
										AND     tbl_os_produto.os   = $os_base_a
										LIMIT   65";

						$res_peca_a = pg_exec($con,$sql_peca_a);
						$qtde_ref = pg_result($res_peca_a, 0, 0);

						$qtde_max_pecas_os = 65;
					}
					$total = $qtde_max_pecas_os;

					for($i = 1; $i <= $qtde_max_pecas_os; $i++){
						fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Código da peça trocada")." $i</b></td>");
					}

					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Custo Peças")."</b></td>");

				}else{

					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Código da peça trocada")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Código da peça trocada")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Código da peça trocada")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Código da peça trocada")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Código da peça trocada")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Código da peça trocada")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Código da peça trocada")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Código da peça trocada")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Código da peça trocada")."</b></td>");
					fputs ($fp,"<td bgcolor=#C4FFFF align=center><b>".traduz("Código da peça trocada")."</b></td>");
					fputs ($fp,"<td nowrap bgcolor=#C4FFFF align=center><b>".traduz("Descricao da peça trocada / manutenção")."</b></td>");
					fputs ($fp,"<td nowrap bgcolor=#C4FFFF align=center><b>".traduz("Descricao da peça trocada / manutenção")."</b></td>");
					fputs ($fp,"<td nowrap bgcolor=#C4FFFF align=center><b>".traduz("Descricao da peça trocada / manutenção")."</b></td>");
					fputs ($fp,"<td nowrap bgcolor=#C4FFFF align=center><b>".traduz("Descricao da peça trocada / manutenção")."</b></td>");
					fputs ($fp,"<td nowrap bgcolor=#C4FFFF align=center><b>".traduz("Descricao da peça trocada / manutenção")."</b></td>");
					fputs ($fp,"<td nowrap bgcolor=#C4FFFF align=center><b>".traduz("Descricao da peça trocada / manutenção")."</b></td>");
					fputs ($fp,"<td nowrap bgcolor=#C4FFFF align=center><b>".traduz("Descricao da peça trocada / manutenção")."</b></td>");
					fputs ($fp,"<td nowrap bgcolor=#C4FFFF align=center><b>".traduz("Descricao da peça trocada / manutenção")."</b></td>");
					fputs ($fp,"<td nowrap bgcolor=#C4FFFF align=center><b>".traduz("Descricao da peça trocada / manutenção")."</b></td>");
					fputs ($fp,"<td nowrap bgcolor=#C4FFFF align=center><b>".traduz("Descricao da peça trocada / manutenção")."</b></td>");

				}

				fputs ($fp,"</tr>");

				for ($ii=0; $ii<pg_num_rows($res_base); $ii++){
					$os_base   =  trim(pg_fetch_result($res_base,$ii,ocorrencia));

					$sql="  SELECT  UPPER(tbl_linha.nome)                       AS nome_linha,
									UPPER(tbl_familia.descricao)                AS descricao_familia,
									tbl_posto_fabrica.codigo_posto::text        AS posto_codigo,
									UPPER(tbl_posto.nome)                       AS posto_nome,
									UPPER(tbl_posto.cidade)                     AS cidade,
									UPPER(tbl_posto.estado)                     AS estado,
									tbl_os.sua_os,
									tbl_os.serie,
									to_char(tbl_os.data_nf,'MM')                AS mes_nota,
									to_char(tbl_os.data_nf,'YYYY')              AS ano_nota,
									to_char(tbl_os.data_abertura,'MM')          AS mes_abertura,
									to_char(tbl_os.data_abertura,'YYYY')        AS ano_abertura,
									to_char(tbl_os.data_fechamento,'MM')        AS mes_fechamento,
									to_char(tbl_os.data_fechamento,'YYYY')      AS ano_fechamento,
									to_char(tbl_os.data_digitacao,'MM')         AS mes_digitacao,
									to_char(tbl_os.data_digitacao,'YYYY')       AS ano_digitacao,
									UPPER(tbl_produto.referencia)               AS referencia,
									UPPER(tbl_produto.referencia_fabrica)       AS produto_referencia_fabrica,
									UPPER(tbl_produto.descricao)                AS descricao,
									UPPER(tbl_produto.nome_comercial)           AS nome_comercial,
									UPPER(tbl_os.consumidor_revenda)            AS consumidor_revenda,
									UPPER(tbl_os.defeito_reclamado_descricao)   AS defeito_reclamado,
									UPPER(tbl_defeito_constatado.descricao)     AS defeito_constatado,
									UPPER(tbl_solucao.descricao)                AS solucao,";
						if($login_fabrica <> 15){
							$sql .= "UPPER(tbl_os.revenda_nome)		     AS revenda_nome,
									tbl_os.revenda_cnpj AS revenda_cnpj,";
						}else{
							$sql .= "   CASE WHEN length(tbl_revenda_fabrica.contato_razao_social)>0
											 thEN tbl_revenda_fabrica.contato_razao_social
											 WHEN length(tbl_revenda_fabrica.contato_nome_fantasia) > 0
											 thEN tbl_revenda_fabrica.contato_nome_fantasia
											 ELSE tbl_os.revenda_nome
										END                                     AS revenda_nome,
										CASE WHEN tbl_revenda_fabrica.cnpj notnull
											 thEN tbl_revenda_fabrica.cnpj
											 ELSE tbl_os.revenda_cnpj
										END                                     AS revenda_cnpj,";
						}

					$sql .= "UPPER(tbl_peca.referencia) AS peca_referencia,
							UPPER(tbl_peca.descricao) AS peca_descricao,
							tbl_os_item.peca,
							tbl_os_item.qtde                                  AS peca_qtde,
							to_char(tbl_os_item.digitacao_item, 'dd/mm/yyyy') AS digitacao_item,
							UPPER(tbl_defeito.descricao)                             AS defeito_descricao,
							UPPER(tbl_servico_realizado.descricao)                   AS servico_realizado ";

					if ($login_fabrica == 15) {#HD 409707
						$sql .= "
							, 	tbl_os.mao_de_obra,
								tbl_os.qtde_km,
								tbl_os.valores_adicionais,
								tbl_os.qtde_km_calculada,
								to_char(tbl_os.data_nf,'DD')          as dia_nota         ,
								to_char(tbl_os.data_abertura,'DD')    as dia_abertura     ,
								to_char(tbl_os.data_fechamento,'DD')  as dia_fechamento   ,
								to_char(tbl_os.finalizada,'DD')       as dia_finalizada   ,
								to_char(tbl_os.finalizada,'MM')       as mes_finalizada   ,
								to_char(tbl_os.finalizada,'YYYY')     as ano_finalizada   ,
								to_char(tbl_os.data_digitacao,'DD')   as dia_digitacao    ,
								(tbl_os.data_fechamento - tbl_os.data_abertura) as dif_dias_fechamento_abertura     ,
								extract(day from(tbl_os.finalizada - tbl_os.data_digitacao))   as dif_dias_finalizada_digitacao,
								(tbl_os.data_abertura - tbl_os.data_nf )  as dif_dias_compra_abertura,
								(tbl_os.data_nf - (
									to_date((((ASCII(UPPER(SUBStr(tbl_os.serie, 4, 1)))::integer + 1995 - 65) || '-' ||  case when (ASCII(UPPER(SUBStr(tbl_os.serie, 3, 1)))::integer - 64) between 1 and 12 then (ASCII(UPPER(SUBStr(tbl_os.serie, 3, 1)))::integer - 64) else '01' end || '-01')),'YYYY-MM-DD')
								)
							)   as dif_dias_fabricacao_compra ,
							extract( month from age(tbl_os.finalizada, tbl_os.data_digitacao) ) as dif_meses_finalizada_digitacao,
							extract( month from age(tbl_os.data_fechamento, tbl_os.data_abertura) ) as dif_meses_fechamento_abertura,
							extract( month from age(tbl_os.data_abertura, tbl_os.data_nf) ) as dif_meses_compra_abertura
						";
					}
					if($login_fabrica == 85){
						$complemento_gelopar 		.= " left join tbl_os_extra on tbl_os_extra.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica";
						$complemento_gelopar_where 	.= " and tbl_os_extra.classificacao_os isnull ";
					}


					$sql .="
						FROM tbl_os
						$complemento_gelopar
						LEFT JOIN tbl_posto         ON tbl_posto.posto         = tbl_os.posto
						LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
						LEFT JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
						LEFT JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
						AND  tbl_os.fabrica = tbl_os_item.fabrica_i
						LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
						LEFT JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_item.defeito AND tbl_defeito.fabrica = $login_fabrica
						LEFT JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
						LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
						LEFT JOIN tbl_revenda_fabrica ON tbl_os.revenda_cnpj =  tbl_revenda_fabrica.cnpj and tbl_revenda_fabrica.fabrica = $login_fabrica
						JOIN tbl_linha              ON tbl_linha.linha = tbl_produto.linha and tbl_linha.fabrica = $login_fabrica
						LEFT JOIN tbl_familia            ON tbl_familia.familia = tbl_produto.familia
						LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os.solucao_os AND tbl_solucao.fabrica = $login_fabrica
						WHERE tbl_os.os=$os_base and tbl_os.fabrica = $login_fabrica
						$complemento_gelopar_where
						limit 1";

					$res = pg_query($con, $sql);

					$sua_os = "";
					$item = 0;
					for($i=0; $i<pg_num_rows($res); $i++){
						$proxima_os = pg_fetch_result($res, $i, sua_os);
						if($sua_os <> $proxima_os){
							if($item>0){
								for ($j=$item;$j<$qtde_item;$j++){
									fputs($fp,"<td nowrap> </td>");
									fputs($fp,"<td nowrap> </td>");
									fputs($fp,"<td nowrap> </td>");
									fputs($fp,"<td nowrap> </td>");
								}
								$item = 0;
							}
							$nome_linha             = pg_fetch_result($res, $i, 'nome_linha');
							$descricao_familia      = pg_fetch_result($res, $i, 'descricao_familia');
							$referencia             = pg_fetch_result($res, $i, 'referencia');
							$produto_referencia_fabrica             = pg_fetch_result($res, $i, 'produto_referencia_fabrica');
							$sua_os                 = pg_fetch_result($res, $i, 'sua_os');
							$serie                  = pg_fetch_result($res, $i, 'serie');
							$mes_nota               = pg_fetch_result($res, $i, 'mes_nota');
							$ano_nota               = pg_fetch_result($res, $i, 'ano_nota');
							$mes_abertura           = pg_fetch_result($res, $i, 'mes_abertura');
							$ano_abertura           = pg_fetch_result($res, $i, 'ano_abertura');
							$mes_fechamento         = pg_fetch_result($res, $i, 'mes_fechamento');
							$ano_fechamento         = pg_fetch_result($res, $i, 'ano_fechamento');
							$mes_digitacao          = pg_fetch_result($res, $i, 'mes_digitacao');
							$ano_digitacao          = pg_fetch_result($res, $i, 'ano_digitacao');
							$posto_codigo           = pg_fetch_result($res, $i, 'posto_codigo');
							$posto_nome             = pg_fetch_result($res, $i, 'posto_nome');
							$posto_cidade           = pg_fetch_result($res, $i, 'cidade');
							$posto_estado           = pg_fetch_result($res, $i, 'estado');
							$descricao              = pg_fetch_result($res, $i, 'descricao');
							$consumidor_revenda     = pg_fetch_result($res, $i, 'consumidor_revenda');
							$revenda_nome           = pg_fetch_result($res, $i, 'revenda_nome');
							$revenda_cnpj           = pg_fetch_result($res, $i, 'revenda_cnpj');
							$defeito_reclamado      = pg_fetch_result($res, $i, 'defeito_reclamado');
							$defeito_constatado     = pg_fetch_result($res, $i, 'defeito_constatado');
							$solucao                = pg_fetch_result($res, $i, 'solucao');
							$peca_referencia        = pg_fetch_result($res, $i, 'peca_referencia');
							$peca_descricao         = pg_fetch_result($res, $i, 'peca_descricao');
							$peca_qtde              = pg_fetch_result($res, $i, 'peca_qtde');
							$defeito_descricao      = pg_fetch_result($res, $i, 'defeito_descricao');
							$servico_realizado      = pg_fetch_result($res, $i, 'servico_realizado');
							$nome_comercial		    = pg_fetch_result($res, $i, 'nome_comercial');
							$fabrica            	= substr($serie, 0, 1);
							$versao             	= substr($serie, 1, 1);
							$parametros_adicionais  = pg_fetch_result($res, $i, 'centro_distribuicao');

						if ($login_fabrica == 15){ #HD 409707

							$dia_nota                       = pg_fetch_result($res, $i, 'dia_nota');
							$dia_abertura                   = pg_fetch_result($res, $i, 'dia_abertura');
							$dia_fechamento                 = pg_fetch_result($res, $i, 'dia_fechamento');
							$dia_finalizada 				= pg_fetch_result($res, $i, 'dia_finalizada');
							$mes_finalizada 				= pg_fetch_result($res, $i, 'mes_finalizada');
							$ano_finalizada 				= pg_fetch_result($res, $i, 'ano_finalizada');
							$dia_digitacao  				= pg_fetch_result($res, $i, 'dia_digitacao');
							$dif_dias_fechamento_abertura   = pg_fetch_result($res, $i, 'dif_dias_fechamento_abertura');
							$dif_meses_fechamento_abertura  = pg_fetch_result($res, $i, 'dif_meses_fechamento_abertura');
							$dif_dias_finalizada_digitacao  = pg_fetch_result($res, $i, 'dif_dias_finalizada_digitacao');
							$dif_meses_finalizada_digitacao = pg_fetch_result($res, $i, 'dif_meses_finalizada_digitacao');
							$dif_dias_compra_abertura       = pg_fetch_result($res, $i, 'dif_dias_compra_abertura');
							$dif_dias_fabricacao_compra     = pg_fetch_result($res, $i, 'dif_dias_fabricacao_compra');
							$valores_adicionais             = number_format(pg_fetch_result($res, $i, 'valores_adicionais'),2,",","");
							$mao_de_obra                    = number_format(pg_fetch_result($res, $i, 'mao_de_obra'),2,",","");
							$qtde_km                     	= number_format(pg_fetch_result($res, $i, 'qtde_km'),3,",","");
							$qtde_km_calculada              = number_format(pg_fetch_result($res, $i, 'qtde_km_calculada'),2,",","");

						}
							$serie = trim(str_replace( ' ', '', $serie));

							$posto_codigo = " ".$posto_codigo." ";

							fputs($fp,"<tr>");
							fputs($fp,"<td nowrap>".convertem($nome_linha)."</td>");
							fputs($fp,"<td nowrap>".convertem($descricao_familia)."</td>");
							fputs($fp,"<td nowrap>".convertem($referencia)."</td>");
							if ($login_fabrica == 171) {
							fputs($fp,"<td nowrap>".convertem($produto_referencia_fabrica)."</td>");
							}

							if ($login_fabrica == 15) {
								fputs($fp,"<td nowrap>$descricao</td>");
							}
							fputs($fp,"<td nowrap>$sua_os</td>");
							fputs($fp,"<td nowrap>$serie</td>");
							if ($login_fabrica == 15) {
								fputs($fp,"<td nowrap>$valores_adicionais</td>");
							}

							if ($login_fabrica != 147) {
								fputs($fp,"<td nowrap align=center>$fabrica</td>");
								fputs($fp,"<td nowrap align=center>$versao</td>");
							}

							if ($serie){
								$fabricao_mes = array_search(substr($serie, 2, 1), $array_mes);
								if($fabricao_mes < 10) $fabricao_mes = "0".$fabricao_mes;
							}else{
								$fabricao_mes = "&nbsp;";
							}

							fputs ($fp,"<td nowrap align=center>$fabricao_mes</td>");

							#HD 415870 INICIO
							if ($serie){
								$letra = substr($serie, 3, 1);
								$ano = ord($letra) + 1930;
								$fabricao_ano = $ano;
							}else{
								$fabricao_ano = "&nbsp;";
							}

							#HD 415870 FIM
							fputs ($fp,"<td nowrap align=center>$fabricao_ano</td>");

							if ($login_fabrica != 147) {
								$sequencial = substr($serie, 4, strlen($serie));

								fputs ($fp,"<td nowrap align=center>$sequencial</td>");
							}
							if ($login_fabrica == 15){ #HD 409707
								fputs ($fp,"<td nowrap align=center>$dia_nota</td>");#HD 409707
							}
							fputs ($fp,"<td nowrap align=center>$mes_nota</td>");
							fputs ($fp,"<td nowrap align=center>$ano_nota</td>");

							if ($login_fabrica == 15){ #HD 409707
								fputs ($fp,"<td nowrap align=center>$dif_dias_fabricacao_compra</td>");#HD 409707
							}

							$data_nota = $ano_nota."-".$mes_nota."-01";
							$data_fabricacao=$fabricao_ano."-".$fabricao_mes."-01";

							$sql2="SELECT ('$data_nota'::date)-('$data_fabricacao'::date) as dias1";
							$res2 = @pg_query($con,$sql2);
							$dias1 = @pg_fetch_result($res2,0,dias1);
							$mes_dif=$dias1/30;
							$mes_dif= number_format(str_replace( ',', '', $mes_dif), 0, ',','');

							fputs ($fp,"<td nowrap align=center>$mes_dif</td>");
							if ($login_fabrica == 15){ #HD 409707
								fputs ($fp,"<td nowrap align=center>$dia_abertura</td>");
							}
							fputs ($fp,"<td nowrap align=center>$mes_abertura</td>");
							fputs ($fp,"<td nowrap align=center>$ano_abertura</td>");

							if ($login_fabrica == 15){ #HD 409707
								fputs ($fp,"<td nowrap align=center>$dif_dias_compra_abertura</td>");
							}
							$data_abertura = $ano_abertura."-".$mes_abertura."-01";
							$sql3="SELECT ('$data_abertura'::date)-('$data_nota'::date) as dias2;";
							$res3 = pg_query($con,$sql3);
							$dias2 = pg_fetch_result($res3,0,dias2);
							$mes_dif2 = $dias2/30;
							$mes_dif2 = (number_format(str_replace( ',', '', $mes_dif2), 0, ',','') + 1);

							fputs ($fp,"<td nowrap align=center>$mes_dif2</td>");

							if ($login_fabrica == 15){ #HD 409707
								fputs ($fp,"<td nowrap align=center>$dia_digitacao</td>");
							}
							fputs ($fp,"<td nowrap align=center>$mes_digitacao</td>");
							fputs ($fp,"<td nowrap align=center>$ano_digitacao</td>");

							if ($login_fabrica == 15){ #HD 409707
								fputs ($fp,"<td nowrap align=center>$dia_fechamento</td>");
							}

							fputs ($fp,"<td nowrap align=center>$mes_fechamento</td>");
							fputs ($fp,"<td nowrap align=center>$ano_fechamento</td>");

							if ($login_fabrica == 15){ #HD 409707
								fputs ($fp,"<td nowrap align=center>$dif_dias_fechamento_abertura</td>");
								fputs ($fp,"<td nowrap align=center>$dif_meses_fechamento_abertura</td>");
								fputs ($fp,"<td nowrap align=center>$dia_finalizada</td>");
								fputs ($fp,"<td nowrap align=center>$mes_finalizada</td>");
								fputs ($fp,"<td nowrap align=center>$ano_finalizada</td>");

								fputs ($fp,"<td nowrap align=center>$dif_dias_finalizada_digitacao</td>");
								fputs ($fp,"<td nowrap align=center>$dif_meses_finalizada_digitacao</td>");
							}

							if ($login_fabrica == 15){ #HD 409707
								fputs($fp,"<td align='left'>$mao_de_obra</td>"); #HD 409707
								fputs($fp,"<td align='left'>$qtde_km</td>");
								fputs($fp,"<td align='left'>$qtde_km_calculada</td>");
							}
							fputs($fp,"<td nowrap>".convertem($consumidor_revenda)."</td>");
							fputs($fp,"<td nowrap>".convertem($revenda_nome)."</td>");
							if ($login_fabrica == 15) {
								$revenda_cnpj = "$revenda_cnpj";
								fputs($fp,"<td><label>".$revenda_cnpj." &nbsp;</label></td>");
								fputs($fp,"<td nowrap>".substr($revenda_cnpj, 0,8)."</td>");
							}
							fputs($fp,"<td nowrap>".convertem($posto_nome)."</td>");
							fputs($fp,"<td nowrap>".convertem($posto_cidade)."</td>");
							fputs($fp,"<td nowrap>".convertem($posto_estado)."</td>");
							fputs($fp,"<td nowrap>".convertem($defeito_reclamado)."</td>");
							fputs($fp,"<td nowrap>".convertem($defeito_constatado)."</td>");
							if ($login_fabrica != 147) {
								fputs($fp,"<td nowrap>".convertem($solucao)."</td>");
							}
						}

						if ($login_fabrica == 15 || $login_fabrica == 147){
							$sql_peca = "SELECT tbl_peca.referencia ,
												tbl_tabela_item.preco
										 FROM   tbl_peca
										 JOIN   tbl_os_item     ON  tbl_peca.peca           = tbl_os_item.peca
																AND tbl_os_item.fabrica_i   = tbl_peca.fabrica
										 JOIN   tbl_os_produto  ON  tbl_os_item.os_produto  = tbl_os_produto.os_produto
										 JOIN   tbl_tabela_item ON  tbl_tabela_item.peca    = tbl_peca.peca
										 JOIN   tbl_tabela      ON  tbl_tabela.tabela       = tbl_tabela_item.tabela AND tbl_tabela.ativa IS trUE
																AND tbl_tabela.fabrica      = tbl_peca.fabrica
										 WHERE  tbl_peca.fabrica    = $login_fabrica
										 AND    tbl_os_produto.os   = $os_base
								   ORDER BY     tbl_tabela_item.preco DESC
										 LIMIT  65
										";

							$res_peca = pg_exec($con,$sql_peca);

							$vet = array();
							$y   = 0;

							while ($row = pg_fetch_assoc($res_peca)) {
								$vet['referencia'][$y] = trim($row['referencia']);
								$y++;
							}

							if($y < $total){
								for($j = $y; $j < $total; $j++){
									$vet['referencia'][$j] = "";
								}
							}

							foreach($vet['referencia'] AS $xreferencia){
								if(strlen($xreferencia)>0){
									fputs ($fp,"<td nowrap>".convertem($xreferencia)."</td>");
								}
								else{
									fputs ($fp,"<td nowrap>&nbsp;</td>");
								}
							}

							$sql_peca_preco = " SELECT  (tbl_tabela_item.preco * tbl_os_item.qtde) AS preco
												FROM    tbl_peca
												JOIN    tbl_os_item     ON  tbl_peca.peca           = tbl_os_item.peca
																		AND tbl_os_item.fabrica_i   = tbl_peca.fabrica
												JOIN    tbl_os_produto  ON  tbl_os_item.os_produto  = tbl_os_produto.os_produto
												JOIN    tbl_tabela_item ON  tbl_tabela_item.peca    = tbl_peca.peca
												JOIN    tbl_tabela      ON  tbl_tabela.tabela       = tbl_tabela_item.tabela AND tbl_tabela.ativa IS trUE
																		AND  tbl_tabela.fabrica     = tbl_peca.fabrica
												WHERE   tbl_peca.fabrica    = $login_fabrica
												AND     tbl_os_produto.os   = $os_base
										  ORDER BY      tbl_tabela_item.preco DESC
							";
							$res_peca_preco = pg_query($con,$sql_peca_preco);

							$valores_pecas = 0;
							for ($u=0; $u < pg_num_rows($res_peca_preco); $u++) {
								$valores_pecas += pg_fetch_result($res_peca_preco, $u, 'preco');
							}

							$valores_pecas = number_format($valores_pecas,2,',','');
							fputs ($fp,"<td>$valores_pecas</td>");
						}else{

							$peca_referencia   = pg_fetch_result($res, $i, peca_referencia);
							$peca_descricao    = pg_fetch_result($res, $i, peca_descricao);
							$peca_qtde         = pg_fetch_result($res, $i, peca_qtde);
							$defeito_descricao = pg_fetch_result($res, $i, defeito_descricao);
							$servico_realizado = pg_fetch_result($res, $i, servico_realizado);
							if(strlen($peca_referencia)<>0){
								fputs($fp,"<td nowrap>$peca_referencia</td>");
								fputs($fp,"<td nowrap>$peca_descricao</td>");
								fputs($fp,"<td nowrap>$peca_qtde</td>");
								fputs($fp,"<td nowrap>$defeito_descricao</td>");
								fputs($fp,"<td nowrap>$servico_realizado</td>");
							}else{
								fputs($fp,"<td nowrap> </td>");
								fputs($fp,"<td nowrap> </td>");
							}

						}

						$item++;
					}
					if($item>0 and $login_fabrica != 15){
						for ($j=$item;$j<$qtde_item;$j++){
							fputs($fp,"<td nowrap> </td>");
							fputs($fp,"<td nowrap> </td>");

						}
						fputs ($fp,"</tr>");
					}
				}
				fputs ($fp,"</table><br><br>");
				fputs ($fp,"</body>");
				fputs ($fp,"</html>");
				fclose ($fp);

				#system("mv $arquivo_completo_tmp $arquivo_completo");
				echo `cd $path_tmp; rm -rf $arquivo_nome_c.zip; zip -o $arquivo_nome_c.zip $arquivo_nome_c > /dev/null ; mv  $arquivo_nome_c.zip $path `;
				#exec("cd $path_tmp; rm -rf $arquivo_nome_c.zip; zip -o $arquivo_nome_c.zip $arquivo_nome_c > /dev/null ; mv  $arquivo_nome_c.zip $path");

			}else{
				echo "<br><br>";
				echo traduz("Nenhum resultado encontrado.");
			}

		}else{

			$relatorio_detalhado = "";
			if ($login_fabrica == 15) {
				echo "<div class='alert alert-block text-center'>".traduz("Atenção")."<br> ".traduz("O Relatório de engenharia detalhado não foi processado motivo:")."<br> ".traduz("Período superior a 30 dias")." <br> $data_inicial e $data_final - $qtde_dias ".traduz("dias")."</div>";
			}
		}
		/* fim gera relatório detalhado */

		/* 7 */

		/* HD 65558 - não mostrar OSs excluidas */

		if ($login_fabrica == 7 and strlen($classificacao) > 0) {

			$sql_tmp = "select count(*) as qtde, tbl_produto.produto
							INTO TEMP tmp_qtde_$login_admin
							from bi_os
							JOIN      tbl_produto ON tbl_produto.produto = bi_os.produto
							JOIN      tbl_linha   ON tbl_linha.linha   = bi_os.linha
							JOIN      tbl_familia ON tbl_familia.familia = bi_os.familia
							where  bi_os.fabrica = $login_fabrica
							AND    bi_os.excluida IS NOT trUE
							$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10
							and classificacao_os = $classificacao
							GROUP BY tbl_produto.produto";

			$res_tmp = pg_query($con,$sql_tmp);
			$join_classificacao = "JOIN      tmp_qtde_$login_admin ON tmp_qtde_$login_admin.produto = bi_os.produto";
			$campo_classificacao = "tmp_qtde_$login_admin.qtde as classificacao,";
			$group_classificacao = "tmp_qtde_$login_admin.qtde           ,";
		}
		if ($login_fabrica == 131){
			$join_bi_os_item = "LEFT JOIN bi_os_item ON bi_os_item.os = bi_os.os";
			$campo_pecas = " SUM(bi_os_item.qtde)           AS qtde_pecas ";
		}else{
			$campo_pecas = " SUM(bi_os.qtde_pecas)           AS qtde_pecas ";

		}
		if($login_fabrica == 85){
			$condicao_gelopar = " and bi_os.classificacao_os is null ";
		}

		if (in_array($login_fabrica, array(11,172))) {
			$condicao_lenoxx = " AND bi_os.cortesia IS FALSE";
		}
		if (in_array($login_fabrica, array(42))) {
			if ($os_cortesia == 't') {
				$condicao_cortesia = " AND bi_os.cortesia IS TRUE";
			} else {
				$condicao_cortesia = " AND bi_os.cortesia IS NOT TRUE";
			}
		}

		if($login_fabrica == 158 AND strlen($_POST['tipo_atendimento']) > 0 && $areaAdminCliente == false ){
			$join_tipo_atendimento = " JOIN tbl_os ON tbl_os.os = bi_os.os JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica} ";

			if($_POST['tipo_atendimento'] == 'fora_garantia'){
				$join_tipo_atendimento .= " AND tbl_tipo_atendimento.fora_garantia IS TRUE ";
			}else{
				$join_tipo_atendimento .= " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
			}
		}
	   
		if (in_array($login_fabrica, [158]) && $areaAdminCliente == true) {
			$join_tipo_atendimento = " JOIN tbl_os ON tbl_os.os = bi_os.os";
			if (strlen($_POST['tipo_atendimento']) > 0) {
				$join_tipo_atendimento = " JOIN tbl_os ON tbl_os.os = bi_os.os JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica} ";
				if($_POST['tipo_atendimento'] == 'fora_garantia'){
					$join_tipo_atendimento .= " AND tbl_tipo_atendimento.fora_garantia IS TRUE ";
				}else{
					$join_tipo_atendimento .= " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
				}
			}

	        if ($areaAdminCliente) {
	            $cond_cliente_admin = "AND   tbl_os.cliente_admin = $login_cliente_admin";
	        }
	    }

		/*
			FABRICA 148 => hd_chamado=3049906
			Marisa vai criar o campo cancelada na tabela bi_os
			assim que criado o campo remover o JOIN
		*/
		if(in_array($login_fabrica, array(74,148))){ //hd_chamado=3049906
			$join_cancelada = " JOIN tbl_os ON tbl_os.os = bi_os.os";
			$cond_cancelada = " AND tbl_os.cancelada IS NOT TRUE ";
		}


		$join_troca_produto = "";

		if($login_fabrica == 117){
			$filtro_produto_trocado = $_POST["filtro_produto_trocado"];
			$macroLinha = $_POST['macro_linha'];

			if($filtro_produto_trocado == "true"){
				$join_troca_produto = " JOIN bi_os_item ON bi_os.os = bi_os_item.os
				JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado
					AND tbl_servico_realizado.troca_produto is true  ";
			}
			if (!empty($macroLinha)) {
					$andMacroLinha = "AND tbl_macro_linha_fabrica.macro_linha = $macroLinha ";
			}
			$condJoinMacroLinha = " JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha $andMacroLinha
					  JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha  ";
		}

		if ($login_fabrica == 164 && !empty($origem_produto)) {
			$condOrigemProduto = "AND tbl_produto.origem = '{$origem_produto}'";
		}

		if ($login_fabrica == 164) {
			$arrayOs = "
				, ARRAY_TO_STRING(ARRAY_AGG(bi_os.os), ',') AS array_os
			";
		}

		if ($telecontrol_distrib || $interno_telecontrol) {
			$sql = " SELECT xx.*,
					((xx.os_produto_acabado::NUMERIC * 100) / xx.ocorrencia::NUMERIC) AS percentual_produto,
					CASE WHEN 
							xx.qtde_pecas > 0 
						THEN 
							((xx.os_peca_trocada::NUMERIC * 100) / xx.qtde_pecas::NUMERIC) 
						ELSE 
							0
					END 
						AS percentual_peca,
					CASE WHEN 
							xx.qtde_pecas > 0 
						THEN 
							((xx.os_peca_ajustada::NUMERIC * 100) / xx.qtde_pecas::NUMERIC) 
						ELSE 
							0
					END 
						AS percentual_peca_ajustada,
					CASE WHEN 
							xx.os_sem_peca > 0 AND xx.qtde_pecas > 0 
						THEN 
							((xx.os_sem_peca::NUMERIC * 100) / xx.qtde_pecas::NUMERIC) 
						ELSE 
							0
					END 
						AS percentual_os_sem_peca
				FROM (
					SELECT
						x.produto,
						x.ativo,
						x.referencia,
						x.referencia_fabrica,
						x.descricao,						
						x.parametros_adicionais::json->>'centro_distribuicao' AS centro_distribuicao,
						x.f_nome,
						x.familia,
						x.l_nome,
						x.linha,						
						COUNT(x.os) AS ocorrencia,
						SUM(x.mao_de_obra) AS mao_de_obra,
						SUM(x.qtde_pecas) AS qtde_pecas,
						SUM(x.os_produto_acabado) AS os_produto_acabado,
						SUM(x.os_peca_trocada) AS os_peca_trocada,
						SUM(x.os_peca_ajustada) AS os_peca_ajustada,
						SUM(x.os_sem_peca) AS os_sem_peca 
					FROM (
						SELECT DISTINCT ON (bi_os.os)
							tbl_produto.produto,
							tbl_produto.ativo,
							tbl_produto.referencia,
							tbl_produto.referencia_fabrica,
							tbl_produto.descricao,		
							tbl_produto.parametros_adicionais,
							tbl_familia.descricao AS f_nome,
							tbl_familia.familia,
							tbl_linha.nome                  AS l_nome     ,
						  	tbl_linha.linha                               ,
							bi_os.os,
							bi_os.mao_de_obra,
						 	bi_os.qtde_pecas,
						 	(	SELECT DISTINCT ON (boi.os)
									CASE WHEN p.produto_acabado IS TRUE THEN 1 ELSE 0 END
								FROM bi_os_item boi
								LEFT JOIN tbl_peca p ON p.peca = boi.peca AND p.fabrica = $login_fabrica
								WHERE boi.os = bi_os.os
							) AS os_produto_acabado,
							(	SELECT DISTINCT ON (boi.os)
									CASE WHEN sr.troca_de_peca IS TRUE THEN 1 ELSE 0 END
								FROM bi_os_item boi
								LEFT JOIN tbl_peca p ON p.peca = boi.peca AND p.fabrica = $login_fabrica
								JOIN tbl_servico_realizado sr ON sr.servico_realizado = boi.servico_realizado  
								WHERE boi.os = bi_os.os
							) AS os_peca_trocada,
							(	SELECT DISTINCT ON (boi.os)
									CASE WHEN sr.gera_pedido IS FALSE THEN 1 ELSE 0 END
								FROM bi_os_item boi
								LEFT JOIN tbl_peca p ON p.peca = boi.peca AND p.fabrica = $login_fabrica
								JOIN tbl_servico_realizado sr ON sr.servico_realizado = boi.servico_realizado  
								WHERE boi.os = bi_os.os
							) AS os_peca_ajustada,
							CASE WHEN bi_os.qtde_pecas = 0 
								THEN
									1
								ELSE
									0
							END AS os_sem_peca
						FROM bi_os
						JOIN tbl_produto ON tbl_produto.produto = bi_os.produto AND tbl_produto.fabrica_i = bi_os.fabrica AND tbl_produto.fabrica_i = $login_fabrica
						JOIN tbl_linha ON tbl_linha.linha = bi_os.linha AND tbl_linha.fabrica = bi_os.fabrica AND tbl_linha.fabrica = $login_fabrica
						JOIN tbl_familia ON tbl_familia.familia = bi_os.familia AND tbl_familia.fabrica = bi_os.fabrica AND tbl_familia.fabrica = $login_fabrica
						$join_marca
						$join_venda
						$join_estado
						$join_tipo_atendimento
						$join_troca_produto
						$join_cancelada
						$joinTipoPosto
						$join_defeito_contatado_grupo
						WHERE bi_os.fabrica = $login_fabrica
						AND bi_os.excluida IS NOT TRUE
							$condOrigemProduto
							$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11 $cond_12 $cond_13
							$cond_cliente_admin
							$condicao_gelopar
							$condicao_lenoxx
							$cond_defeito_contatado_grupo
							$condicao_cortesia
							$cond_cancelada
							$condTipoPosto
							$cond_matriz_filial
					) x
					GROUP BY    x.produto                           ,
										x.ativo                             ,
										x.referencia                        ,
										x.referencia_fabrica                        ,
										x.descricao                         ,
										x.f_nome                               ,
										x.linha,
										x.familia,
								     	x.linha,
										x.l_nome,
										x.parametros_adicionais
										$campo_venda
							ORDER BY ocorrencia DESC 
				) xx ";
		} else {
			$sql = "SELECT  tbl_produto.produto                   ,
					tbl_produto.ativo                             ,
					tbl_produto.referencia                        ,
					tbl_produto.referencia_fabrica                ,					
					tbl_produto.descricao                         ,					
					tbl_produto.parametros_adicionais::json->>'centro_distribuicao' AS centro_distribuicao,
					tbl_familia.descricao           AS f_nome     ,
					tbl_familia.familia                           ,";
			if ($login_fabrica == 117 ) {
					$sql .= " tbl_macro_linha.descricao                  AS l_nome     ,
									  tbl_macro_linha_fabrica.macro_linha        AS linha      ,
									  tbl_linha.nome                  AS macro_familia_nome    ,
									  tbl_linha.linha                 AS macro_familia         ,";
			}else{
					$sql .= " tbl_linha.nome                  AS l_nome     ,
									  tbl_linha.linha                               ,";
			}
			if($login_fabrica == 151){
				if($centro_distribuicao != 'mk_vazio') {
					$p_adicionais = " AND tbl_produto.parametros_adicionais::json->>'centro_distribuicao' = '$centro_distribuicao'";
				}
			}

			$sql .= "
					$produto_marca
					count(bi_os.os)                 AS ocorrencia ,
					$campo_classificacao
					SUM(bi_os.mao_de_obra)          AS mao_de_obra,
					$campo_pecas
					$campo_venda
					$campo_defeito_contatado_grupo_descricao
					$condFacricadoCount
					{$arrayOs}
				FROM      bi_os
				JOIN      tbl_produto ON tbl_produto.produto = bi_os.produto AND tbl_produto.fabrica_i = bi_os.fabrica AND tbl_produto.fabrica_i = $login_fabrica
				JOIN      tbl_linha   ON tbl_linha.linha   = bi_os.linha AND tbl_linha.fabrica = bi_os.fabrica AND tbl_linha.fabrica = $login_fabrica
				$condJoinMacroLinha
				$join_classificacao
				$join_bi_os_item
				JOIN      tbl_familia ON tbl_familia.familia = bi_os.familia AND tbl_familia.fabrica = bi_os.fabrica AND tbl_familia.fabrica = $login_fabrica
				$join_marca
				$join_venda
				$condFacricadoJoin
				$join_estado
				$join_tipo_atendimento
				$join_troca_produto
				$join_cancelada
				$joinTipoPosto
				$join_defeito_contatado_grupo
				WHERE bi_os.fabrica = $login_fabrica
				AND bi_os.excluida IS NOT TRUE
				$condOrigemProduto
				$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11 $cond_12 $cond_13
				$cond_cliente_admin
				$condicao_gelopar
				$condicao_lenoxx
				$cond_defeito_contatado_grupo
				$condicao_cortesia
				$cond_cancelada
				$condTipoPosto
				$cond_matriz_filial
				$p_adicionais
				GROUP BY    tbl_produto.produto                           ,
							tbl_produto.ativo                             ,
							tbl_produto.referencia                        ,
							tbl_produto.referencia_fabrica                        ,
							tbl_produto.descricao                         ,
							$group_classificacao
							f_nome                               ,
							tbl_linha.linha,
							tbl_familia.familia,";
				if ($login_fabrica == 117 ) {
						$sql .= " tbl_macro_linha.descricao ,
										  tbl_macro_linha_fabrica.macro_linha ,
										  tbl_linha.nome   ,
									  tbl_linha.linha  ";
				}else{
						$sql .= "       tbl_linha.linha,
												l_nome";
				}
				$sql .= "
							$campo_venda
							$order_marca
							$grupo_defeito_constatado_grupo
				ORDER BY ocorrencia DESC ";
		}	

		$res = pg_query ($con,$sql);
		
		if (pg_num_rows($res) > 0) {
			$total = 0;

			echo "<div class='alert alert-block text-center'>".traduz("Resultado de pesquisa entre os dias")." $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais</div>";

			$data = date("Y-m-d").".".date("H-i-s");

			$arquivo_nome     = "bi-os-produtos-$login_fabrica.$login_admin.".$formato_arquivo;
			$path             = "../xls/";
			$path_tmp         = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			$fp = fopen ($arquivo_completo_tmp,"w");

			if ($login_fabrica == 50) { // HD 41116
				echo "<span id='logo'><img src='../imagens_admin/colormaq_.gif' border='0' width='160' height='55'></span> <br /> <br />";
			}

			if($login_fabrica == 117){
				?>
				</div></div>
				<?php
			}

			$border_gama = ($login_fabrica == 51) ? '1' : '0' ; //HD 728995

			$conteudo .= "<table id='relatorio_fcr' class='table table-striped table-bordered table-hover table-large'>";
			$conteudo .= "<thead>";
			$conteudo .= "<tr class='titulo_coluna'>";

			if ($login_fabrica == 171){
				$conteudo .= "<th>".traduz("Referência FN")."</th>";
				$conteudo .= "<th >".traduz("Referência Grohe")."</th>";
			}else{
				$conteudo .= "<th >".traduz("Referência")."</th>";
			}
			
			if ($login_fabrica == 96){
				$conteudo .= "<th>".traduz("Modelo")."</th>";
			}
			$conteudo .= "<th >".traduz("Produto")."</th>";
			if($login_fabrica==3 /*OR $login_fabrica == 15*/) $conteudo .="<th>".traduz("Marca")."</th>";
			$conteudo .= "<th >".traduz("Linha")."</th>";
			if($login_fabrica == 117) $conteudo .="<th>".traduz("Macro - Família")."</th>";
			$conteudo .= "<th >".traduz("Família")."</th>";

			if($login_fabrica == 175){
				$conteudo .="<th>".traduz("Grupo Defeito Constatado")."</th>";
			} 

			if ($login_fabrica == 117) {
				$conteudo .= "<th >".traduz("Ocorrência")."</th>";
				$conteudo .= "<th > % </th>";
				$conteudo .= "<th >".traduz("Qtde. Peças Trocadas")."</th>";
				$conteudo .= "<th >".traduz("Valor Total das Peças")."</th>";
				$conteudo .= "<th >".traduz("Quantidade Produto Trocado")."</th>";
				$conteudo .= "<th >".traduz("Valor Produto")."</th>";

			}

			if($login_fabrica == 95 AND $tipo_data=="data_fabricacao") $conteudo .="<th>".traduz("Total Fabricado")."</th>";
			if ($login_fabrica <> 7) {
				if($login_fabrica == 42){
					$array_datas            = "";
					$meses_subs             = array("Jan/","Fev/","Mar/","Abr/","Mai/","Jun/","Jul/","Ago/","Set/","Out/","Nov/","Dez/");
					$dia_meses_subs         = array("01-","02-","03-","04-","05-","06-","07-","08-","09-","10-","11-","12-");
					$data_dia_primeiro      = explode("-",$aux_data_inicial);
					$pega_mes_final         = explode("-",$aux_data_final);
					if($data_dia_primeiro[1] == $pega_mes_final[1]){
						$array_datas[$mes_ano]  =  "'".$aux_data_inicial."' AND '".$aux_data_final."'";
					}else{

						$aux_data_intervalo     = date('Y-m-d',strtotime("+1 month",mktime(0,0,0,$data_dia_primeiro[1],1,$data_dia_primeiro[0])));
						$mes_ano                = date('m-Y',strtotime($aux_data_inicial));
						$array_datas[$mes_ano]  =  "'".$aux_data_inicial."' AND '".$aux_data_intervalo."'::date - interval '1 day'";

						$conteudo .= "<th >".str_replace($dia_meses_subs,$meses_subs,$mes_ano)."</th>";

						while(strtotime($aux_data_intervalo) < strtotime($aux_data_final)){
							$pega_mes_intervalo = "";
							$mes_ano = date('m-Y',strtotime($aux_data_intervalo));
							$aux_data_intervalo_ant = $aux_data_intervalo;
							$aux_data_intervalo = date('Y-m-d',strtotime("+1 month",strtotime($aux_data_intervalo)));
							$pega_mes_intervalo = explode("-",$aux_data_intervalo);

							if($aux_data_intervalo <= $aux_data_final){
								$array_datas[$mes_ano] = "'".$aux_data_intervalo_ant."' AND '".$aux_data_intervalo."'::date - interval '1 day'";
							}else{
								$array_datas[$mes_ano] = "'".$aux_data_intervalo_ant."' AND '".$aux_data_final."'";
							}

							$conteudo .= "<th >".str_replace($dia_meses_subs,$meses_subs,$mes_ano)."</th>";
						}
					}
					$conteudo .= "<th >".traduz("Total Ocorrências")."</th>";
				}else{
					if ($login_fabrica != 117) {
						$conteudo .= "<th >".traduz("Ocorrência")."</th>";
					}
				}
			} else {
				$conteudo .="<th>".traduz("Total de Os")."</th>";
			}
			if ($login_fabrica == 7 and strlen($classificacao)>0) {
				$conteudo .= "<th>".traduz("Classificação")."</th>";
			}

			if ($login_fabrica != 117) {
				$conteudo .= "<th > % </th>";
				$conteudo .= "<th >".traduz("Qtde. Peças Trocadas")."</th>";
			}

			if ($areaAdminCliente != true) {

				$conteudo .= "<th >".traduz("M.O")."</th>";

				if($login_fabrica == 151){
					$conteudo .= "<th >".traduz("Centro Distribuição")."</th>";
				}
			}

			if($login_fabrica == 117){
				$conteudo .= "<th >".traduz("Total")."</th>";
			}

			$conteudo .= "</tr>";
			$conteudo .= "</thead>";
			$conteudo .= "<tbody>";

			echo $conteudo;

			/* ----------------------------------------------------------------- */

			/* Cabeçalho */

			if ($formato_arquivo == 'CSV'){
				/* CSV */
				$conteudo = "";
				//$conteudo .= "REFERÊNCIA;PRODUTO;LINHA;FAMÍLIA;";

				if($login_fabrica == 117){
					$conteudo .= traduz("REFERÊNCIA;PRODUTO;LINHA;MACRO-FAMÍLIA;FAMÍLIA;");
					$conteudo .= traduz("OCORRÊNCIA;%;QTDE. PEÇAS TROCADAS;VALOR TOTAL DAS PEÇAS;QUANTIDADE PRODUTO TROCADO;VALOR PRODUTO;M.O.;TOTAL");
				}else if ($login_fabrica == 171){
					$conteudo .= traduz("REFERÊNCIA FN;REFERÊNCIA GROHE;PRODUTO;LINHA;FAMÍLIA;");
				}else{
					$conteudo .= traduz("REFERÊNCIA;PRODUTO;LINHA;FAMÍLIA;");
				}

				if ($login_fabrica == 175){
					$conteudo .= traduz("GRUPO DEFEITO CONSTATADO;");
				}

				if($login_fabrica == 104){
					$conteudo .= "Referência Peça;Descrição Peça;Ocorrencia das Peças;";
				}

				if($login_fabrica == 42){
					if($data_dia_primeiro[1] != $pega_mes_final[1]){
						foreach($array_datas as $key => $value){
							$conteudo .= $key.";";
						}
					}
					$conteudo .= traduz("TOTAL OCORRENCIA;"); 
				}elseif($login_fabrica != 117){
					$conteudo .= traduz("OCORRENCIA;");
				}

				if($login_fabrica != 117){

					$conteudo .= ";".traduz("%;QtdE. PECAS;M.O");

					if($login_fabrica == 151){
						$conteudo .= ";".traduz("CENTRO DISTRIBUIÇÃO");
					}

				}

				if ($telecontrol_distrib || $interno_telecontrol) {
					$conteudo .= "; OS COM PEÇA TROCADA; % OS COM PEÇA TROCADA; OS COM PRODUTO TROCADO; % OS COM PRODUTO TROCADO; OS COM PEÇA AJUSTADA; % OS COM PEÇA AJUSTADA; OS SEM PEÇA; % OS SEM PEÇA";
				}

				if ($login_fabrica == 164) {

					$conteudo .= traduz(";Ordem de Serviço;Defeito Reclamado;Defeito Constatado;Número de Série;Número de Série do Calefator/Motor;Cor Indicativa da Carcaça;Número de série interno Placa/Motor;Referência da Peça;Descrição da Peça;Defeito da Peça;Serviço Realizado");

				} elseif ($login_fabrica == 158 and $areaAdminCliente) {
					$conteudo = traduz("Referência;Produto;Linha;Família;Ocorrência;%;Qtde. Peças")."\n";
				}else{
					$conteudo .= "\n";
				}
			}else{
				/* XLS */
				$conteudo = "";

				if ($areaAdminCliente) {
					$conteudo = '
					<table>
						<caption bgcolor="#D9E2EF" color="#333333" style="color: #333333 !important;">'.traduz("FIELD-CALL RATE - PRODUTOS").'</caption>
						<thead>
							<tr>
								<th bgcolor="#596D9B" color="#FFFFFF" style="color:white!important">'.traduz("Referência").'</th>
								<th bgcolor="#596D9B" color="#FFFFFF" style="color:white!important">'.traduz("Produto").'</th>
								<th bgcolor="#596D9B" color="#FFFFFF" style="color:white!important">'.traduz("Linha").'</th>
								<th bgcolor="#596D9B" color="#FFFFFF" style="color:white!important">'.traduz("Família").'</th>
								<th bgcolor="#596D9B" color="#FFFFFF" style="color:white!important">'.traduz("Ocorrência").'</th>
								<th bgcolor="#596D9B" color="#FFFFFF" style="color:white!important">%</th>
								<th bgcolor="#596D9B" color="#FFFFFF" style="color:white!important">'.traduz("Qtde. Peças Trocadas").'</th>
							</tr>
						</thead>
						<tbody>
					';
				} else if ($login_fabrica == 117) {
					$conteudo .= "
					<table border='1'>
						<thead>
							<tr>
								<th colspan='12' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>
									 ".traduz("RELATORIO : BI - FIELD CALL RATE - PRODUTOS")."
								</th>
							</tr>
							<tr>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("REFERÊNCIA")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("PRODUTO")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("LINHA")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("MACRO-FAMÍLIA")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("FAMÍLIA")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("OCORRÊNCIA")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>%</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("QTDE. PECAS")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("VALOR TOTAL DAS PEÇAS")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("QUANTIDADE PRODUTO TROCADO")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("VALOR PRODUTO")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("M.O.")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("TOTAL")."</th>
							</tr>
						</thead>
						<tbody>";
				}else{

					$colspan_xls = 8;
					if ($login_fabrica == 164) {
						$colspan_xls = 19;
					} else if ($telecontrol_distrib || $interno_telecontrol) {
						$colspan_xls = 16;
					}
					
					$conteudo .= "
					<table border='1'>
						<thead>
							<tr>
								<th colspan='".$colspan_xls."' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>
									".traduz("RELATORIO : BI - FIELD CALL RATE - PRODUTOS")."
								</th>
							</tr>
							<tr>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("REFERÊNCIA")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("PRODUTO")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("LINHA")."</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("FAMÍLIA")."</th>
					";
					
					if($login_fabrica == 104){
						$conteudo .= "
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Refêrencia Peça</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição Peça</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Ocorrência Peça</th>
					";
					}

					if($login_fabrica == 42){
						if($data_dia_primeiro[1] != $pega_mes_final[1]){
							foreach($array_datas as $key => $value){
								$conteudo .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>$key</th>";
							}
						}
						$conteudo .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>TOTAL OCORRENCIA</th>";
					}else{
						$conteudo .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OCORRÊNCIA</th>";
					}

					$conteudo .= "
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>%</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>QtdE. PECAS</th>
						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>M.O</th>
					";

					if ($telecontrol_distrib || $interno_telecontrol) {
						$conteudo .= "
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS COM PEÇA TROCADA</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>% OS COM PEÇA TROCADA</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS COM PRODUTO TROCADO</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>% OS COM PRODUTO TROCADO</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS COM PEÇA AJUSTADA</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>% OS COM PEÇA AJUSTADA</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS SEM PEÇA</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>% OS SEM PEÇA</th>
									";
					}

					if ($login_fabrica == 164) {
						$conteudo .= "
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Ordem de Serviço</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito Reclamado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito Constatado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número de Série</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número de Série do Calefator/Motor</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cor Indicativa da Carcaça</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número de série interno Placa/Motor</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Referência da Peça</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição da Peça</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito da Peça</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Serviço Realizado</th>
						";
					}

					$conteudo .= "
							</tr>
						</thead>
						<tbody>
					";
				}

			}

			fputs ($fp,$conteudo);

			/* ----------------------------------------------------------------- */

			for ($x = 0; $x < pg_num_rows($res); $x++) {
				$total_ocorrencia = $total_ocorrencia + pg_fetch_result($res,$x,ocorrencia);
			}

			if($login_fabrica == 117){
				$total_os_troca_peca         = 0;
				$total_qtde_produto_trocado  = 0;
				$total_valor_produto_trocado = 0;
				$total_geral_produto         = 0;
			}

			for ($i=0; $i < pg_num_rows($res); $i++){

				$conteudo = "";
				$parametros_adicionais = pg_fetch_result($res, $i, 'centro_distribuicao');
				$referencia   = trim(pg_fetch_result($res,$i,referencia));
				if (in_array($login_fabrica, array(96,171))){
					$referencia_fabrica   = trim(pg_fetch_result($res,$i,'referencia_fabrica'));

				}
				$ativo        = trim(pg_fetch_result($res,$i,ativo));
				$descricao    = trim(pg_fetch_result($res,$i,descricao));
				if($login_fabrica == 20 and $pais !='BR' and strlen($descricao)==0){
					$descricao    = "<font color = 'red'>tradução não cadastrada.</font>";
				}
				$produto      = trim(pg_fetch_result($res,$i,produto));
				$familia_nome = trim(pg_fetch_result($res,$i,f_nome));
				$linha_nome   = trim(pg_fetch_result($res,$i,l_nome));
				$linha        = trim(pg_fetch_result($res,$i,'linha'));
				$macro_familia = trim(pg_fetch_result($res,$i,macro_familia));
				$familia      = trim(pg_fetch_result($res,$i,'familia'));
				if($login_fabrica == 3 /*OR $login_fabrica == 15*/){
					$marca_nome   = trim(pg_fetch_result($res,$i,m_nome));
				}

				if ($login_fabrica == 7 and strlen($classificacao)>0) {
					$classificacao = pg_fetch_result($res,$i,classificacao);
				}

				if($login_fabrica == 35){
					$produtos[] = $produto;	
				}				

                if ($login_fabrica == 117) {
                        $macro_familia_nome        = trim(pg_fetch_result($res,$i,'macro_familia_nome'));
                }

                if ($login_fabrica == 175){
                	$defeito_contatado_grupo_descricao = pg_fetch_result($res, $i, 'defeito_contatado_grupo_descricao');
                }

				$ocorrencia   = trim(pg_fetch_result($res,$i,ocorrencia));
				$mao_de_obra  = trim(pg_fetch_result($res,$i,mao_de_obra));

				$qtde_pecas   = trim(pg_fetch_result($res,$i,qtde_pecas));

				if ($login_fabrica == 164) {
					$array_os = pg_fetch_result($res, $i, "array_os");
					$array_os = explode(",", $array_os);
				}

				if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);

				if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';}

				if($login_fabrica == 117){
					$total_produto_peca_mo = 0;
					$total_produto_peca_mo = $mao_de_obra;
				}

				$total_mo    += $mao_de_obra;
				$total_peca  += $qtde_pecas ;
				$total       += $ocorrencia ;

				$porcentagem = number_format($porcentagem,2,",",".");
				$mao_de_obra = number_format($mao_de_obra,2,",",".");

				if ($telecontrol_distrib || $interno_telecontrol) {
					$os_peca_trocada          = (empty(pg_fetch_result($res, $i, 'os_peca_trocada'))) ? 0 : pg_fetch_result($res, $i, 'os_peca_trocada');
					$percentual_peca          = number_format(pg_fetch_result($res, $i, 'percentual_peca'),2,",","."); 
					$os_produto_acabado       = (empty(pg_fetch_result($res, $i, 'os_produto_acabado'))) ? 0 : pg_fetch_result($res, $i, 'os_produto_acabado');
					$percentual_produto       = number_format(pg_fetch_result($res, $i, 'percentual_produto'),2,",",".");
					$os_peca_ajustada         = (empty(pg_fetch_result($res, $i, 'os_peca_ajustada'))) ? 0 : pg_fetch_result($res, $i, 'os_peca_ajustada');
					$percentual_peca_ajustada = number_format(pg_fetch_result($res, $i, 'percentual_peca_ajustada'),2,",",".");
					$os_sem_peca              = (empty(pg_fetch_result($res, $i, 'os_sem_peca'))) ? 0 : pg_fetch_result($res, $i, 'os_sem_peca');
					$percentual_os_sem_peca   = number_format(pg_fetch_result($res, $i, 'percentual_os_sem_peca'),2,",",".");
				}

				if($login_fabrica == 95 AND $tipo_data=="data_fabricacao"){
					$total_fabricado   = trim(pg_fetch_result($res,$i,total_fabricado));

					if(!empty($total_fabricado)) {
						$porcentagem_fabricado_ocorrencia = round(($ocorrencia/$total_fabricado)*100,2);
					}else{
						$porcentagem_fabricado_ocorrencia = 0;
						$total_fabricado = 0;
					}

					$porcentagem = $porcentagem_fabricado_ocorrencia;

					$valor_total_fabricado += $total_fabricado;
				}

				if( in_array($login_fabrica, array(11,50,147,172,104)) && !empty($produto)) {
					$sql_item = "SELECT	tbl_peca.referencia,
							tbl_peca.descricao,
							tbl_peca.peca,
							bi.qtde			AS ocorrencia
						FROM   (
							SELECT bi_os.peca, SUM (bi_os.qtde) AS qtde, SUM (bi_os.custo_peca) AS custo_peca
							FROM bi_os_item bi_os
							WHERE bi_os.fabrica = $login_fabrica
							AND bi_os.excluida IS NOT trUE
							AND bi_os.produto = $produto
							$cond_1 $cond_2 $cond_3 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10
							GROUP BY bi_os.peca
						) bi
						JOIN tbl_peca ON bi.peca = tbl_peca.peca
						ORDER BY ocorrencia DESC";
					$res_item = pg_exec ($con,$sql_item);

					if($i == 0) {
						$arquivo_nome_item     = "bi-os-explodido-$login_fabrica.$login_admin.xls";
						$path_item             = "../xls/";
						$path_tmp_item         = "/tmp/";

						$arquivo_completo_item     = $path_item.$arquivo_nome_item;
						$arquivo_completo_tmp_item = $path_tmp_item.$arquivo_nome_item;

						$fp2 = fopen ($arquivo_completo_tmp_item,"w");
						fputs ($fp2,"<html>");
						fputs ($fp2,"<body>");
						$conteudo2 .= "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' >";
						$conteudo2 .= "<thead>";
						$conteudo2 .= "<tr>";
						$conteudo2 .= "<td width='100' height='15'><b>Produto</b></td>";
						$conteudo2 .= "<td height='15'><b>Modelo Peça</b></td>";
						$conteudo2 .= "<td height='15'><b>Peça</b></td>";
						$conteudo2 .= "<td width='120' height='15'><b>Ocorrência</b></td>";
						$conteudo2 .= "<td width='50' height='15'><b>%</b></td>";
						$conteudo2 .= "</tr>";
						$conteudo2 .= "</thead>";
						$conteudo2 .= "<tbody>";

					}

					if(pg_num_rows($res_item) > 0) {
						$total_ocorrencia_peca = 0 ;
						for ($it = 0; $it < pg_num_rows($res_item); $it++) {
							$total_ocorrencia_peca = $total_ocorrencia_peca + pg_fetch_result($res_item,$it,'ocorrencia');
						}
						for ($it=0; $it<pg_num_rows($res_item); $it++){
							$referencia_peca   = trim(pg_fetch_result($res_item,$it,'referencia'));
							$descricao_peca    = trim(pg_fetch_result($res_item,$it,'descricao'));
							$ocorrencia_peca   = trim(pg_fetch_result($res_item,$it,'ocorrencia'));

							if ($total_ocorrencia_peca > 0) $porcentagem_peca = (($ocorrencia_peca * 100) / $total_ocorrencia_peca);
							$porcentagem_peca = number_format($porcentagem_peca,2,",",".");
							if($porcentagem_peca < 5) continue;


							$conteudo2 .= "<tr>";
							$conteudo2 .= "<td align='left' nowrap>$referencia</td>";
							$conteudo2 .= "<td align='left' nowrap>$referencia_peca</td>";
							$conteudo2 .= "<td align='left' nowrap>$descricao_peca</td>";
							$conteudo2 .= "<td align='center' nowrap>$ocorrencia_peca</td>";
							$conteudo2 .= "<td align='right' nowrap title=''>$porcentagem_peca</td>";
							$conteudo2 .= "</tr>";
						}

						$conteudo2 .= "<tr><td></td><td></td><td>Total de Ocorrencia</td><td>$total_ocorrencia_peca</td></tr>"	;
						$conteudo2 .= "<tr><td colspan='100%'><br/></td></tr>"	;
					}
				}

				$conteudo .= "<tr>";

				if (in_array($login_fabrica, array(96,171))){
					$conteudo .= "<td align='left' nowrap>$referencia_fabrica</td>";
				}
				
				$conteudo .= "<td align='left' nowrap>";

				$produto_trocado = "false";

				if($login_fabrica == 117){
					if($filtro_produto_trocado == "true"){
						$produto_trocado = $filtro_produto_trocado;
					}
					/*$conteudo .= "<a href=\"javascript:AbrePeca('$produto','$data_inicial','$data_final','$macro_familia','$linha','$estado','$posto','$pais','$marca','$tipo_data','$aux_data_inicial','$aux_data_final','','$exceto_posto','$familia','$tipo_atendimento','$produto_trocado');\">$referencia</td>";*/
				}/*else{
					$conteudo .= "<a href=\"javascript:AbrePeca('$produto','$data_inicial','$data_final','$linha','$estado','$posto','$pais','$marca','$tipo_data','$aux_data_inicial','$aux_data_final','','$exceto_posto','$familia','$tipo_atendimento','$produto_trocado');\">$referencia</td>";
				}*/

				$conteudo .= "<a href=\"javascript:AbrePeca('$produto','$data_inicial','$data_final','$linha','$estado_filtro','$posto','$pais','$marca','$tipo_data','$aux_data_inicial','$aux_data_final','','$exceto_posto','$familia','$tipo_atendimento','$produto_trocado','".implode('|', $xtipo_posto)."', '$matriz_filial');\">$referencia</td>";


				$conteudo .= "<td align='left'>$descricao</td>";
				if($login_fabrica == 3 /*OR $login_fabrica == 15*/) $conteudo .="<td align='left'>$marca_nome</td>";
				$conteudo .= "<td align='left'>$linha_nome</td>";
				if ($login_fabrica == 117) {
					$conteudo .= "<td align='left'>$macro_familia_nome</td>";
				}
				$conteudo .= "<td align='left'>$familia_nome</td>";

				if ($login_fabrica == 175){
					$conteudo .= "<td align='left'>$defeito_contatado_grupo_descricao</td>";
				}

				if($login_fabrica == 95 AND $tipo_data=="data_fabricacao"){
					$conteudo .= "<td align='center'>$total_fabricado</td>";
				}
				if($login_fabrica == 42){
					if($data_dia_primeiro[1] != $pega_mes_final[1]){
						foreach($array_datas as $intervalo=>$between){
							$sql_parcial = "SELECT  COUNT(bi_os.os) AS ocorrencia_mes
								FROM    bi_os
								JOIN    tbl_produto ON  tbl_produto.produto = bi_os.produto
								JOIN    tbl_linha   ON  tbl_linha.linha     = bi_os.linha
								AND tbl_linha.fabrica   = $login_fabrica
								$join_classificacao
								JOIN    tbl_familia ON  tbl_familia.familia = bi_os.familia
								$join_marca
								$join_venda
								$condFacricadoJoin
								WHERE   bi_os.fabrica       = $login_fabrica
								AND     bi_os.excluida      IS NOT TRUE
								AND     bi_os.$tipo_data    BETWEEN $between
								AND     tbl_produto.produto = $produto ";
							$res_parcial = pg_query($con,$sql_parcial);
							$conteudo .= "<td align='right'>".pg_fetch_result($res_parcial,0,ocorrencia_mes)."</td>";
						}
					}
				}

				if($login_fabrica == 117){
					pg_query($con,"DROP TABLE IF EXISTS tmp_bi_os;");
					pg_query($con,"DROP TABLE IF EXISTS tmp_bi_os_troca_produto;");

					if($_POST['filtro_produto_trocado']){
						$join_servico = " JOIN bi_os_item ON bi_os.os = bi_os_item.os
							JOIN tbl_servico_realizado ON bi_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
							AND tbl_servico_realizado.troca_produto IS TRUE";
					}else{
						$join_servico = " LEFT JOIN bi_os_item ON bi_os.os = bi_os_item.os
							LEFT JOIN tbl_servico_realizado ON bi_os_item.servico_realizado = tbl_servico_realizado.servico_realizado";
					}

					$sqlTempBiOs = "SELECT DISTINCT bi_os.os, bi_os.produto,tbl_servico_realizado.troca_de_peca,
								troca_produto
							INTO temp tmp_bi_os
						FROM bi_os
						$facricadoJoin
						$join_servico
						WHERE bi_os.fabrica = $login_fabrica
							$cond_lenoxx
							$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10
							AND bi_os.produto = $produto ;";
					pg_query($con,$sqlTempBiOs);

					$notTrue = "";

						/* VALOR TOTAL DE PEÇAS TROCADAS */
						$sqlTotalVrPeca = "SELECT count( distinct bi_os_item.os) AS com_peca,
								SUM(bi_os_item.custo_peca) as total_preco
							FROM tmp_bi_os TBI
								JOIN bi_os_item ON TBI.os = bi_os_item.os
								JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado
									AND tbl_servico_realizado.troca_de_peca is true
								AND tbl_servico_realizado.gera_pedido IS TRUE
								JOIN tbl_peca ON bi_os_item.peca = tbl_peca.peca
									AND tbl_peca.produto_acabado IS NOT TRUE
							WHERE bi_os_item.fabrica = $login_fabrica;";

						$resTotalVrPeca          = pg_query($con,$sqlTotalVrPeca);
						$total_preco_os_com_peca = pg_result($resTotalVrPeca,0,1);

					/* QTDE DE PRODUTOS TROCADOS */
					$sqlTrocaProduto = "SELECT DISTINCT os,produto INTO TEMP tmp_bi_os_troca_produto
						FROM tmp_bi_os
						WHERE troca_produto is TRUE;

						SELECT count( distinct bi_os_item.os) AS com_peca,
							SUM(tbl_produto.preco) as total_preco
						FROM tmp_bi_os_troca_produto TBI
							JOIN bi_os_item ON TBI.os = bi_os_item.os
							JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado
								AND tbl_servico_realizado.troca_produto is true
							JOIN tbl_produto ON tbl_produto.produto = TBI.produto AND tbl_produto.fabrica_i = $login_fabrica
						WHERE bi_os_item.fabrica = $login_fabrica
								AND bi_os_item.excluida IS NOT TRUE;";
					$resTrocaProduto = pg_query($con,$sqlTrocaProduto);

					$os_produto_trocado       = pg_result($resTrocaProduto,0,0);
					$valor_os_produto_trocado = pg_result($resTrocaProduto,0,"total_preco");

					if(strlen($os_produto_trocado) == 0){
						$os_produto_trocado = 0;
					}

					$total_os_troca_peca         += $total_preco_os_com_peca;
					$total_qtde_produto_trocado  += $os_produto_trocado;
					$total_valor_produto_trocado += $valor_os_produto_trocado;

					$total_produto_peca_mo += $total_preco_os_com_peca + $valor_os_produto_trocado;

					$total_geral_produto += $total_produto_peca_mo;

					$total_preco_os_com_peca  = number_format($total_preco_os_com_peca,2,",",".");
					$valor_os_produto_trocado = number_format($valor_os_produto_trocado,2,",",".");
					$total_produto_peca_mo    = number_format($total_produto_peca_mo,2,",",".");

					$conteudo .= "<td align='center'>$ocorrencia</td>";
					$conteudo .= "<td align='right'>$porcentagem</td>";
					$conteudo .= "<td align='center'> $qtde_pecas</td>";

					$conteudo .= "<td align='center'>$total_preco_os_com_peca</td>";
					$conteudo .= "<td align='center'>$os_produto_trocado</td>";
					$conteudo .= "<td align='center'>$valor_os_produto_trocado</td>";
				}

				if ($login_fabrica != 117) {
					$conteudo .= "<td align='center'>$ocorrencia</td>";
				}

				if ($login_fabrica == 7 and strlen($classificacao)>0) {
					$conteudo .= "<td align='center'>$classificacao</td>";
				}

				if ($login_fabrica != 117) {
					$conteudo .= "<td align='right'>$porcentagem</td>";
					$conteudo .= "<td align='center'> $qtde_pecas</td>";
				}

				if ($areaAdminCliente != true) {
					$conteudo .= "<td align='center'>$mao_de_obra</td>";
					if($login_fabrica == 151){						
						if($parametros_adicionais == "mk_nordeste"){
							$conteudo .= "<td>MK Nordeste</td>";	
						}else if($parametros_adicionais == "mk_sul") {
							$conteudo .= "<td>MK Sul</td>";	
						} else{
							$conteudo .= "<td>{$cd->centro_distribuicao}</td>";	
						}						
					}
				}

				if ($login_fabrica == 117) {
					$conteudo .= "<td align='center'>$total_produto_peca_mo</td>";
				}
				$conteudo .= "</tr>";

				echo $conteudo;

				/* ----------------------------------------------------------------- */

				/* Corpo */

				if ($formato_arquivo == 'CSV'){
					/* CSV */

					if ($login_fabrica == 117) {
						$conteudo = "".$referencia.";".$descricao.";".$linha_nome.";".$macro_familia_nome.";".$familia_nome.";".$ocorrencia.";".$porcentagem.";".$qtde_pecas.";".$total_preco_os_com_peca.";".$os_produto_trocado.";".$valor_os_produto_trocado.";".$mao_de_obra.";".$total_produto_peca_mo.";\n";
					}
					elseif($login_fabrica == 104){
						$conteudo = "";
						$descricao = str_replace(',', ' ', $descricao);
						$linha_nome = str_replace(',', ' ', $linha_nome);
						$familia_nome = str_replace(',', ' ', $familia_nome);
						$porcentagem = str_replace(',', '.', $porcentagem);

						for ($it=0; $it<pg_num_rows($res_item); $it++){
							$peca_ref   = trim(pg_fetch_result($res_item,$it,'referencia'));
							$desc_peca    = trim(pg_fetch_result($res_item,$it,'descricao'));
							$ocor_peca   = trim(pg_fetch_result($res_item,$it,'ocorrencia'));
							$desc_peca    = str_replace(',', ' ', $desc_peca);;

							$conteudo .= "".$referencia.";".$descricao.";".$linha_nome.";".$familia_nome.";".$peca_ref.";".$desc_peca.";".$ocor_peca.";".$ocorrencia.";".$porcentagem.";".$qtde_pecas.";".$mao_de_obra.";".";\n";
						}
					} /*else if ($clienteAdmin) {
					// 	$conteudo = "$referencia;$produto_descricao;$linha_nome;$familia_nome;$ocorrencia;$porcentagem;$qtde_pecas\n";
					// }*/
					else{
						unset($conteudo);
						/*HD-4187560*/
						$referencia = '"'.$referencia.'"';

						if ($login_fabrica == 171){
							$conteudo .= $referencia.";".$referencia_fabrica.";".$descricao.";".$linha_nome.";".$familia_nome.";";
						}else
						{
							$conteudo .= $referencia.";".$descricao.";".$linha_nome.";".$familia_nome.";";
						}

						if ($login_fabrica == 175){
							$conteudo .= $defeito_contatado_grupo_descricao.";";
						}

						if($login_fabrica == 42){
							if($data_dia_primeiro[1] != $pega_mes_final[1]){
								foreach($array_datas as $intervalo=>$between){
									$sql_parcial = "SELECT  COUNT(bi_os.os) AS ocorrencia_mes
													FROM    bi_os
													JOIN    tbl_produto ON  tbl_produto.produto = bi_os.produto
													JOIN    tbl_linha   ON  tbl_linha.linha     = bi_os.linha
																		AND tbl_linha.fabrica   = $login_fabrica
													$join_classificacao
													JOIN    tbl_familia ON  tbl_familia.familia = bi_os.familia
													$join_marca
													$join_venda
													$condFacricadoJoin
													WHERE   bi_os.fabrica       = $login_fabrica
													AND     bi_os.excluida      IS NOT TRUE
													AND     bi_os.$tipo_data    BETWEEN $between
													AND     tbl_produto.produto = $produto
									";
									$res_parcial = pg_query($con,$sql_parcial);
									$conteudo .= pg_fetch_result($res_parcial,0,ocorrencia_mes).";";
								}
							}
						}

						if ($areaAdminCliente == true) {
							$conteudo .= $ocorrencia.";".$porcentagem.";".$qtde_pecas;
						} else {
							$conteudo .= $ocorrencia.";".$porcentagem.";".$qtde_pecas.";".$mao_de_obra;
							if($login_fabrica == 151){
								//$cd = (object) $parametros_adicionais;
								if($parametros_adicionais == "mk_nordeste"){
									$conteudo .= ";MK Nordeste";	
								}else if($parametros_adicionais == "mk_sul") {
									$conteudo .= ";MK Sul";	
								} else {
									$conteudo .= ";";	
								}
							}
						}
						
						if ($telecontrol_distrib || $interno_telecontrol) {
							$conteudo .= ";".$os_peca_trocada.";".$percentual_peca.";".$os_produto_acabado.";".$percentual_produto.";".$os_peca_ajustada.";".$percentual_peca_ajustada.";".$os_sem_peca.";".$percentual_os_sem_peca;
						}

						if ($login_fabrica == 164) {
							foreach ($array_os as $k_os => $os) {
								$sqlOs = "

									SELECT tbl_defeito_reclamado.descricao as descricao_defeito, serie, serie_calefator,serie_interno_motor_placa, cor_indicativa,
										tbl_defeito_constatado.descricao AS defeito_constatado
									FROM bi_os
									JOIN  tbl_defeito_reclamado ON  bi_os.defeito_reclamado=tbl_defeito_reclamado.defeito_reclamado
									JOIN tbl_defeito_constatado ON bi_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
									WHERE bi_os.fabrica = {$login_fabrica}
									AND os = {$os}
								";

								$resOs = pg_query($con, $sqlOs);

								$resOs = pg_fetch_assoc($resOs);

								if ($k_os != 0) {
									$conteudo .= ";;;;;;;";
								}

								$conteudo .= ";{$os};{$resOs['descricao_defeito']};{$resOs['defeito_constatado']};{$resOs['serie']};{$resOs['serie_calefator']};".mb_strtoupper($resOs['cor_indicativa']).";{$resOs['serie_interno_motor_placa']};";

								$sqlPeca = "
									SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_defeito.descricao AS defeito, tbl_servico_realizado.descricao AS servico_realizado
									FROM bi_os_item
									INNER JOIN tbl_peca ON tbl_peca.peca = bi_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
									LEFT JOIN tbl_defeito ON tbl_defeito.defeito = bi_os_item.defeito AND tbl_defeito.fabrica = {$login_fabrica}
									LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
									WHERE bi_os_item.os = {$os}
									AND tbl_servico_realizado.servico_realizado <> 11236
								";
								$resPeca = pg_query($con, $sqlPeca);

								if (!pg_num_rows($resPeca)) {
									$conteudo .= ";\n";
								} else {
									foreach (pg_fetch_all($resPeca) as $k_peca => $peca) {
										if ($k_peca != 0) {
											$conteudo .= ";;;;;;;;;;;;;;;";
										}

										$conteudo .= "{$peca['referencia']};{$peca['descricao']};{$peca['defeito']};{$peca['servico_realizado']};\n";
									}
								}
							}
						} else {
							$conteudo .= ";\n";
						}
					}
				}else{
					/* XLS */

					if($login_fabrica == 117){
						$conteudo = "";
						$conteudo .= "<tr>";
						$conteudo .= "<td>".$referencia."</td>";
						$conteudo .= "<td>".$descricao."</td>";
						$conteudo .= "<td>".$linha_nome."</td>";
						$conteudo .= "<td>".$macro_familia_nome."</td>";
						$conteudo .= "<td>".$familia_nome."</td>";
						$conteudo .= "<td>".$ocorrencia."</td>";
						$conteudo .= "<td>".$porcentagem."</td>";
						$conteudo .= "<td>".$qtde_pecas."</td>";
						$conteudo .= "<td>".$total_preco_os_com_peca."</td>";
						$conteudo .= "<td>".$os_produto_trocado."</td>";
						$conteudo .= "<td>".$valor_os_produto_trocado."</td>";
						$conteudo .= "<td>".$mao_de_obra."</td>";
						$conteudo .= "<td>".$total_produto_peca_mo."</td>";
						$conteudo .= "</tr>";
					} elseif ($areaAdminCliente) {
						$conteudo = "";
						$conteudo .= "<tr>";
						$conteudo .= "<td>".$referencia."</td>";
						$conteudo .= "<td>".$descricao."</td>";
						$conteudo .= "<td>".$linha_nome."</td>";
						$conteudo .= "<td>".$familia_nome."</td>";
						$conteudo .= "<td>".$ocorrencia."</td>";
						$conteudo .= "<td>".$porcentagem."</td>";
						$conteudo .= "<td>".$qtde_pecas."</td>";
						$conteudo .= "</tr>";
					}elseif($login_fabrica == 104){
						$conteudo = "";
						for ($it=0; $it<pg_num_rows($res_item); $it++){
							$referencia_peca   = trim(pg_fetch_result($res_item,$it,'referencia'));
							$descricao_peca    = trim(pg_fetch_result($res_item,$it,'descricao'));
							$ocorrencia_peca   = trim(pg_fetch_result($res_item,$it,'ocorrencia'));

							$conteudo .= "<tr>";
							$conteudo .= "<td>".$referencia."</td>";
							$conteudo .= "<td>".$descricao."</td>";
							$conteudo .= "<td>".$linha_nome."</td>";
							$conteudo .= "<td>".$familia_nome."</td>";
							$conteudo .= "<td align='left' nowrap>$referencia_peca</td>";
							$conteudo .= "<td align='left' nowrap>$descricao_peca</td>";
							$conteudo .= "<td align='center' nowrap>$ocorrencia_peca</td>";
							$conteudo .= "<td>".$ocorrencia."</td>";
							$conteudo .= "<td>".$porcentagem."</td>";
							$conteudo .= "<td>".$qtde_pecas."</td>";
							$conteudo .= "<td>".$mao_de_obra."</td>";	
							$conteudo .= "</tr>";
						}
					}else{
						$conteudo = "";
						$conteudo .= "<tr>";
						$conteudo .= "<td>".$referencia."</td>";
						$conteudo .= "<td>".$descricao."</td>";
						$conteudo .= "<td>".$linha_nome."</td>";
						$conteudo .= "<td>".$familia_nome."</td>";

						if($login_fabrica == 42){
							if($data_dia_primeiro[1] != $pega_mes_final[1]){
								foreach($array_datas as $intervalo=>$between){
									$sql_parcial = "SELECT  COUNT(bi_os.os) AS ocorrencia_mes
										FROM    bi_os
										JOIN    tbl_produto ON  tbl_produto.produto = bi_os.produto
										JOIN    tbl_linha   ON  tbl_linha.linha     = bi_os.linha
										AND tbl_linha.fabrica   = $login_fabrica
										$join_classificacao
										JOIN    tbl_familia ON  tbl_familia.familia = bi_os.familia
										$join_marca
										$join_venda
										$condFacricadoJoin
										WHERE   bi_os.fabrica       = $login_fabrica
										AND     bi_os.excluida      IS NOT TRUE
										AND     bi_os.$tipo_data    BETWEEN $between
										AND     tbl_produto.produto = $produto
";
									$res_parcial = pg_query($con,$sql_parcial);
									$conteudo .= "<td>".pg_fetch_result($res_parcial,0,ocorrencia_mes)."</td>";
								}
							}
						}

						

						$conteudo .= "<td>".$ocorrencia."</td>";
						$conteudo .= "<td>".$porcentagem."</td>";
						$conteudo .= "<td>".$qtde_pecas."</td>";
						$conteudo .= "<td>".$mao_de_obra."</td>";						

						if ($telecontrol_distrib || $interno_telecontrol) {
							$conteudo .= "<td>".$os_peca_trocada."</td>";
							$conteudo .= "<td>".$percentual_peca."</td>";
							$conteudo .= "<td>".$os_produto_acabado."</td>";
							$conteudo .= "<td>".$percentual_produto."</td>";
							$conteudo .= "<td>".$os_peca_ajustada."</td>";
							$conteudo .= "<td>".$percentual_peca_ajustada."</td>";
							$conteudo .= "<td>".$os_sem_peca."</td>";
							$conteudo .= "<td>".$percentual_os_sem_peca."</td>";
						}

						if ($login_fabrica == 164) {
							foreach ($array_os as $k_os => $os) {
								$sqlOs = "

									SELECT tbl_defeito_reclamado.descricao as descricao_defeito, serie, serie_calefator,serie_interno_motor_placa, cor_indicativa,
										tbl_defeito_constatado.descricao AS defeito_constatado
									FROM bi_os
									JOIN  tbl_defeito_reclamado ON  bi_os.defeito_reclamado=tbl_defeito_reclamado.defeito_reclamado
									JOIN tbl_defeito_constatado ON bi_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
									WHERE bi_os.fabrica = {$login_fabrica}
									AND os = {$os}
								";

								$resOs = pg_query($con, $sqlOs);

								$resOs = pg_fetch_assoc($resOs);

								if ($k_os != 0) {
									$conteudo .= "
										<tr>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
									";
								}


								$conteudo .= "
									<td>{$os}</td>
									<td>{$resOs['descricao_defeito']}</td>
									<td>{$resOs['defeito_constatado']}</td>
									<td>{$resOs['serie']}</td>
									<td>{$resOs['serie_calefator']}</td>
									<td>".mb_strtoupper($resOs['cor_indicativa'])."</td>
									<td>{$resOs['serie_interno_motor_placa']}</td>
								";

								$sqlPeca = "
									SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_defeito.descricao AS defeito, tbl_servico_realizado.descricao AS servico_realizado
									FROM bi_os_item
									INNER JOIN tbl_peca ON tbl_peca.peca = bi_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
									LEFT JOIN tbl_defeito ON tbl_defeito.defeito = bi_os_item.defeito AND tbl_defeito.fabrica = {$login_fabrica}
									LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
									WHERE bi_os_item.os = {$os}
									AND tbl_servico_realizado.servico_realizado <> 11236
								";
								$resPeca = pg_query($con, $sqlPeca);

								if (!pg_num_rows($resPeca)) {
									$conteudo .= "</tr>";
								} else {
									foreach (pg_fetch_all($resPeca) as $k_peca => $peca) {
										if ($k_peca != 0) {
											$conteudo .= "
												<tr>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
													<td>&nbsp;</td>
											";
										}

										$conteudo .= "
												<td>{$peca['referencia']}</td>
												<td>{$peca['descricao']}</td>
												<td>{$peca['defeito']}</td>
												<td>{$peca['servico_realizado']}</td>
											</tr>
										";
									}
								}
							}
						} else {
							$conteudo .= "</tr>";
						}
					}
				}

				fputs($fp,$conteudo);

				/* ----------------------------------------------------------------- */

			}

			$conteudo .= "</tbody>";

			$conteudo = "";

			$conteudo .= "<tfoot>";

			$total       = number_format($total,0,",",".");
			$total_mo    = number_format($total_mo,2,",",".");
			$total_pecas = number_format($total_pecas,2,",",".");

			if($valor_total_fabricado > 0){
				$total_porcentagem = round(($total/$valor_total_fabricado)*100,2);
				$total_porcentagem = number_format($total_porcentagem,2,",",".");
			}else{
				$total_porcentagem = 0;
			}

			$valor_total_fabricados = number_format($valor_total_fabricado,2,",",".");

			$conteudo .= "<tr><td colspan='";
			if($login_fabrica == 3 OR $login_fabrica == 5 OR $login_fabrica == 96)
				$conteudo .= "5'";
			elseif (in_array($login_fabrica, array(117,175))) {
				$conteudo .= "5'";
			} /*elseif ($areaAdminCliente) {
				$conteudo .= '2';
			}*/
			else $conteudo .= "4'";

			$conteudo .= "><font size='2'><b><CENTER>";

			if ($login_fabrica == 50 and strlen($lista_produtos) > 0) { // HD 74309
				$conteudo .= "<a href='javascript:AbrePeca(\"$produto\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$posto\",\"$pais\",\"$marca\",\"$tipo_data\",\"$aux_data_inicial\",\"$aux_data_final\",\"$lista_produtos\",\"\",\"$familia\",\"$tipo_atendimento\",,'".implode('|', $xtipo_posto)."');'>";
			}

			$conteudo .= "Total</b></td>";

			if($login_fabrica == 95){
				$conteudo .= "<td align='center'><font size='2' color='009900'><b>$valor_total_fabricado</b></td>";
			}
			if($login_fabrica == 42){
				if($data_dia_primeiro[1] != $pega_mes_final[1]){
					$colspan = count($array_datas) + 1;
				}
			}

            if($login_fabrica == 35){

            	$conteudo .="<td colspan='$colspan' align='center'><font size='2' color='009900'><b>";
            	$conteudo .="<span id='produtos' style='color:#0088cc; cursor:pointer;'>$total</span></b>";

            	$jsonPOST = excelPostToJson($_POST);
				$conteudo .= "<form id='form_produtos' method='POST' target='_blank' action='fcr_os_item_new.php'>
						<input type='hidden' name='produtos' value='".implode(",", $produtos)."'>
						<input type='hidden' name='dados' id='jsonPOST' value='$jsonPOST'/>
					</form></td>";
            }else{
            	$conteudo .="<td colspan='$colspan' align='center'><font size='2' color='009900'><b>$total</b></td>";	
            }

			

			if($login_fabrica == 117){
				$total_os_troca_peca         = number_format($total_os_troca_peca,2,",",".");
				$total_valor_produto_trocado = number_format($total_valor_produto_trocado,2,",",".");

				$conteudo .= '<td align="center"> --- </td>';
				$conteudo .= "<td align='center'><font size='2' color='009900'><b>$total_peca</b></td>";

				$conteudo .="<td colspan='$colspan' align='center'><font size='2' color='009900'><b>$real $total_os_troca_peca</b></td>";
				$conteudo .="<td colspan='$colspan' align='center'><font size='2' color='009900'><b>$real $total_qtde_produto_trocado</b></td>";
				$conteudo .="<td colspan='$colspan' align='center'><font size='2' color='009900'><b>$real $total_valor_produto_trocado</b></td>";
			}

			if($login_fabrica == 95){
				$conteudo .= "<td align='center'><font size='2' color='009900'><b>$total_porcentagem</b></td>";
			}elseif ($login_fabrica != 117) {
				$conteudo .= '<td align="center"> --- </td>';
			}

			if ($login_fabrica != 117) {
				$conteudo .= "<td align='center'><font size='2' color='009900'><b>$total_peca</b></td>";
			}
			// $conteudo .= "<td align='center'><font size='2' color='009900'><b>$total_peca</b></td>";
			if ($areaAdminCliente != true and !in_array($login_fabrica, [180,181,182])) {
				$conteudo .= "<td align='center'><font size='2' color='009900'><b>$real $total_mo</b></td>";
				if($login_fabrica == 151){
					$conteudo .= "<td align='center'>&nbsp;</td>";
				}

			}

			if($login_fabrica == 117){
				$total_geral_produto = number_format($total_geral_produto,2,",",".");
				$conteudo .="<td colspan='$colspan' align='center'><font size='2' color='009900'><b>$real $total_geral_produto</b></td>";
			}

			if (in_array($login_fabrica, [180,181,182])) {
				$conteudo .= "<td align='center'><font size='2' color='009900'><b>$ $total_mo</b></td>";
			}

			$conteudo .= "</tr>";
			$conteudo .= "</tfoot>";
			$conteudo .= "</table></div>";

			if($login_fabrica == 117){
				$conteudo .= '<div class="container">';
			}

			if ( in_array($login_fabrica, array(11,172)) ) {
				$conteudo2 .= "</tbody>";
				$conteudo2 .= "</table>";
				fputs($fp2,$conteudo2);
				fputs ($fp2, "</body>");
				fputs ($fp2, "</html>");
				fclose($fp2);
				echo `mv $path_tmp_item$arquivo_nome_item $path_item `;

				echo "<br/><p id='id_download2' style='display:none'><a href='../xls/$arquivo_nome_item' target='_blank'><img src='/assist/imagens/excel.gif'><br><font color='#3300CC'>Fazer download do relatório de engenharia explodido em XLS</font></a></p>";
			}

			echo $conteudo;

			/* ----------------------------------------------------------------- */

			/* Rodapé */

			if ($formato_arquivo == 'CSV'){
				/* CSV */
				$conteudo = "";
				if($login_fabrica == 117){
					$conteudo .= "Total: ;;;;;{$total};---;{$total_peca};{$total_os_troca_peca};{$total_qtde_produto_trocado};{$total_valor_produto_trocado};{$total_mo};{$total_geral_produto}";
				} else if ($areaAdminCliente) {
					$conteudo .= "Total:;;;;$total;;$total_peca\n";
				} else if($login_fabrica == 164) {
					$conteudo .= "Total: ;;;;$total;;$total_peca;$total_mo;\n";
				} else if ($login_fabrica == 175){
					$conteudo .= "Total: ;;;;;".$total.";;".$total_peca.";".$total_mo.";\n";
				}else{
					$conteudo .= "Total:;;;;".$total.";".$total_peca.";".$total_mo.";\n";
				}
			}else{
				/* XLS */

				if ($login_fabrica == 117) {
					$conteudo = "";
					$conteudo .= "<tr bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >";
					$conteudo .= "<td colspan='5' align='center'>TOTAL</td>";
					$conteudo .= "<td align='right'>".$total."</td>";
					$conteudo .= "<td align='center'>---</td>";
					$conteudo .= "<td>".$total_peca."</td>";
					$conteudo .= "<td align='right'>".$total_os_troca_peca."</td>";
					$conteudo .= "<td align='right'>".$total_qtde_produto_trocado."</td>";
					$conteudo .= "<td align='right'>".$total_valor_produto_trocado."</td>";
					$conteudo .= "<td align='right'>".$total_mo."</td>";
					$conteudo .= "<td align='right'>".$total_geral_produto."</td>";
				} else if($login_fabrica == 164) {
					$conteudo = "";
					$conteudo .= "
								<tr bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >
									<td colspan='4' align='center'>TOTAL</td>
									<td align='left'>".$total."</td>
									<td>&nbsp;</td>
									<td align='left'>".$total_peca."</td>
									<td align='left'>".$total_mo."</td>
									<td colspan='11' >&nbsp;</td>
					";
				}else if($login_fabrica == 104) {
					$conteudo = "";
					$conteudo .= "
								<tr bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >
									<td colspan='7' align='center'>TOTAL</td>
									<td align='left'>".$total."</td>
									<td>&nbsp;</td>
									<td align='left'>".$total_peca."</td>
									<td align='left'>".$total_mo."</td>
					";
				} else{
					$conteudo = "";
					$conteudo .= "<tr bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >";
					$conteudo .= "<td colspan='4' align='center'>TOTAL</td>";
					$conteudo .= "<td align='right'>".$total."</td>";
					$conteudo .= "<td align='center'>---</td>";
					$conteudo .= "<td align='right'>".$total_peca."</td>";
					if ($areaAdminCliente != true) {
						$conteudo .= "<td align='right'>".$total_mo."</td>";
					}
				}
                $conteudo .= "  </tr>
                            </tbody>
                        </table>";

			}

			fputs ($fp,$conteudo);

			/* ----------------------------------------------------------------- */

			fclose($fp);

			echo ` cp $arquivo_completo_tmp $path `;

			echo "<script language='javascript'>";
			//echo "document.getElementById('id_download').style.display='block';";
			if(in_array($login_fabrica, array(11,15,172))){
				echo "document.getElementById('id_download2').style.display='block';";
			}
			echo "</script>";
			echo "<br>";

			if ($relatorio_detalhado == 't'){
				?>
				<a href="../xls/<?php echo $arquivo_nome_c.".zip"; ?>" target='_blank'>
					<div id='gerar_excel' class="btn_excel" style="width: 220px; text-align: center;">
						<!-- <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' /> -->
						<span><img src='imagens/excel.png' /></span>
						<span class="txt" style="width: 180px; text-align: center;">Engenharia Detalhado</span>
					</div>
				</a>

				<br />

				<?php
				// echo "<br><p id='id_download2' style='display:none'><a href='../xls/$arquivo_nome_c.zip'><img src='/assist/imagens/excel.gif'><br><font color='#3300CC'>Fazer download do relatório de engenharia detalhado</font></a></p><br>";
			}

			if ($login_fabrica != 147) {
			?>

			<a href='../xls/<?php echo $arquivo_nome; ?>' target='_blank'>
				<div id='gerar_excel' class="btn_excel" style="width: 220px; text-align: center;">
					<!-- <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' /> -->
					<span><img src='imagens/excel.png' /></span>
					<span class="txt" style="width: 180px; text-align: center;">Realizar Download</span>
				</div>
			</a>

			<?php
			}
		}else{
			echo "<div class='alert alert-block text-center'><h4>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais</h4></div>";
		}

		/* 51 */
		/* gera relatório defeito por produto Gama Italy */
		if($login_fabrica == 51){

			$sql = "SELECT
					COUNT(bi_os.os)				AS ocorrencia,
					bi_os.produto				AS produto_defeito,
					bi_os.defeito_constatado		AS defeito,
					tbl_defeito_constatado.descricao	AS defeito_descricao
				FROM bi_os
					LEFT JOIN tbl_defeito_constatado ON bi_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
				WHERE bi_os.fabrica = $login_fabrica
					AND bi_os.excluida IS NOT trUE
					$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10
				GROUP BY bi_os.produto,tbl_defeito_constatado.defeito_constatado,bi_os.defeito_constatado,tbl_defeito_constatado.descricao
				ORDER BY bi_os.produto";
			$res = pg_query ($con,$sql);

			if (pg_num_rows($res) > 0) {

				$data = date ("d-m-Y-H-i");

				$arquivo_nome3     = "relatorio_defeito_produto-$login_fabrica-$ano-$mes-$data.html";
				$arquivo_nome4     = "relatorio_defeito_produto-$login_fabrica-$ano-$mes-$data.xls";
				$path             = "../xls";
				$path_tmp         = "/tmp/assist/";
				$arquivo_completo     = $path.$arquivo_nome3;
				$arquivo_completo_tmp = $path_tmp.$arquivo_nome3;

				echo `rm $arquivo_completo_tmp `;
				echo `rm $arquivo_completo_tmp.zip `;
				echo `rm $arquivo_completo.zip `;
				echo `rm $arquivo_completo `;

				$fp = fopen ($arquivo_completo_tmp,"w");

				$style = 'background-color:#596d9b; font: bold 11px "Arial"; color:#FFFFFF; text-align:center;';
				$header = "<table border='1' cellspacing='1' cellspading='0' style='background-color:transparent;'>";
				fputs ($fp, $header);

				$conteudo2 .= "<table class='table table-striped table-bordered table-hover table-large' name='relatorio2' id='relatorio2'>";

				$conteudo2 .= "<thead><tr class='titulo_coluna'>";
					$conteudo2 .= "<th width='100'>Referência</th>";
					$conteudo2 .= "<th height='15'>Produto</th>";
					$conteudo2 .= "<th><b>Defeito</b></th>";
					$conteudo2 .= "<th><b>Linha</b></th>";
					$conteudo2 .= "<th><b>Família</b></th>";
					$conteudo2 .= "<th width='120'>Ocorrência</th>";
					$conteudo2 .= "<th width='50'>%</th>";
				$conteudo2 .= "</tr></thead>\n";

				$conteudo2 .="<tbody>";

				$style = 'background-color:#596d9b; font: bold 11px "Arial"; color:#FFFFFF; text-align:center;';
				$header_produto = "";

				$header_produto .= "<tr>";

					$header_produto .= "<th style='$style' nowrap> Referência </th>";
					$header_produto .= "<th style='$style' nowrap> Produto    </th>";
					$header_produto .= "<th style='$style' nowrap> Defeito    </th>";
					$header_produto .= "<th style='$style' nowrap> Linha      </th>";
					$header_produto .= "<th style='$style' nowrap> Família    </th>";
					$header_produto .= "<th style='$style' nowrap> Ocorrência </th>";
					$header_produto .= "<th style='$style' nowrap> %          </th>";

					$style_pecas 	 = 'background-color:#C0CAE0; font: bold 11px "Arial"; color:#333; text-align:center;';

					$header_produto .= "<th style='$style_pecas' NOWRAP >OS</th>";
					$header_produto .= "<th style='$style_pecas' NOWRAP >Referência da Peça</th>";
					$header_produto .= "<th style='$style_pecas' NOWRAP >Descrição da Peça</th>";
					$header_produto .= "<th style='$style_pecas' NOWRAP >Defeito Reclamado</th>";
					$header_produto .= "<th style='$style_pecas' NOWRAP >Defeito da Peça</th>";
					$header_produto .= "<th style='$style_pecas' NOWRAP >Serviço</th>";
					$header_produto .= "<th style='$style_pecas' NOWRAP >Ocorrência</th>";

				$header_produto .= "</tr>\n";
				fputs($fp,$header_produto);

				for ($i = 0; $i < pg_num_rows($res); $i++) {
					$total_ocorrencia_defeito = $total_ocorrencia_defeito + pg_fetch_result($res,$i,ocorrencia);
				}

				for ($x = 0 ; $x < pg_num_rows($res) ; $x++){

					$ocorrencia  		= trim(pg_fetch_result($res,$x,ocorrencia));
					$produto_defeito  	= trim(pg_fetch_result($res,$x,produto_defeito));
					$defeito_descricao  = trim(pg_fetch_result($res,$x,defeito_descricao));
					$defeito  			= trim(pg_fetch_result($res,$x,defeito));

					if ($total_ocorrencia_defeito > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia_defeito);

					$porcentagem = number_format($porcentagem,2,",",".");

					$sql_produtos2="SELECT
							tbl_produto.descricao	AS produto_descricao		,
							tbl_produto.referencia	AS produto_referencia		,
							tbl_linha.nome		AS linha				,
							tbl_familia.descricao	AS familia
						FROM tbl_produto
							JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica
							JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = $login_fabrica
						WHERE
							tbl_produto.produto = $produto_defeito
					";

					$res_produtos2 = pg_query ($con,$sql_produtos2);

					if (pg_num_rows($res_produtos2) > 0) {
						$produto_descricao	= trim(pg_fetch_result($res_produtos2,0,produto_descricao));
						$produto_referencia = trim(pg_fetch_result($res_produtos2,0,produto_referencia));
						$linha  			= trim(pg_fetch_result($res_produtos2,0,linha));
						$familia  			= trim(pg_fetch_result($res_produtos2,0,familia));
					}

					$dados_table = $dados_table."<tr><td>$produto_referencia</td><td>$produto_descricao</td><td>$defeito_descricao</td><td>$linha</td><td>$familia</td><td>$ocorrencia</td><td>$porcentagem</td></tr>";

					$style = 'background-color:#95A2BF; font: bold 11px "Arial"; color:#FFFFFF; text-align:center;';

					$xls_produto = "";
					$xls_produto .= "<tr>";
						$xls_produto .= "<td style='$style' NOWRAP>$produto_referencia</td>";
						$xls_produto .= "<td style='$style width:350px' NOWRAP >$produto_descricao</td>";
						$xls_produto .= "<td style='$style' NOWRAP>$defeito_descricao</td>";
						$xls_produto .= "<td style='$style' NOWRAP>$linha</td>";
						$xls_produto .= "<td style='$style' NOWRAP>$familia</td>";
						$xls_produto .= "<td style='$style' NOWRAP>$ocorrencia</td>";
						$xls_produto .= "<td style='$style' NOWRAP>$porcentagem</td>";

					fputs($fp,$xls_produto);

					$sql_peca2="
						SELECT
							bi_os.sua_os AS peca_os,
							tbl_peca.referencia AS peca_referencia,
							tbl_peca.descricao AS peca_descricao,
							bi_os.defeito_reclamado_descricao AS defeito_reclamado,
							tbl_defeito.descricao AS defeito_peca ,
							tbl_servico_realizado.descricao AS servico,
							bi_os_item.qtde AS qtde,
							tbl_defeito_constatado.descricao AS defeito_constatado,
							tbl_solucao.descricao AS solucao
						FROM bi_os
							JOIN tbl_defeito_constatado ON bi_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
							LEFT JOIN tbl_solucao ON bi_os.solucao_os = tbl_solucao.solucao
							LEFT JOIN bi_os_item ON bi_os_item.os = bi_os.os
							LEFT JOIN tbl_peca ON tbl_peca.peca = bi_os_item.peca
							LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado
							LEFT JOIN tbl_defeito ON tbl_defeito.defeito = bi_os_item.defeito
						WHERE bi_os.fabrica = $login_fabrica AND bi_os.defeito_constatado = $defeito  AND bi_os.produto = $produto_defeito
							AND bi_os.excluida IS NOT trUE
							$cond_1
							$cond_2
							$cond_3
							$cond_4
							$cond_5
							$cond_6
							$cond_7
							$cond_8
							$cond_9
							$cond_10
						ORDER BY bi_os.produto;";

					$res_peca2 = pg_query ($con,$sql_peca2);
					if (pg_num_rows($res_peca2) > 0) {
						$xls_peca = "";
						for($y = 0; $y < pg_num_rows($res_peca2); $y++){
							$peca_os			= pg_fetch_result($res_peca2,$y,'peca_os');
							$peca_referencia	= pg_fetch_result($res_peca2,$y,'peca_referencia');
							$peca_descricao 	= pg_fetch_result($res_peca2,$y,'peca_descricao');
							$defeito_reclamado 	= pg_fetch_result($res_peca2,$y,'defeito_reclamado');
							$defeito_peca 		= pg_fetch_result($res_peca2,$y,'defeito_peca');
							$servico 			= pg_fetch_result($res_peca2,$y,'servico');
							$qtde 				= pg_fetch_result($res_peca2,$y,'qtde');
							$defeito_constatado = pg_fetch_result($res_peca2,$y,'qtdedefeito_constatado');
							$solucao			= pg_fetch_result($res_peca2,$y,'solucao');

							if ($y % 2){
								$style_das_pecas = 'background-color:#D8D8D8 !important; font: bold 11px Arial; color: #333; text-align: center;';
							}else{
								$style_das_pecas = 'background-color:#C0C0C0 !important;font: bold 11px Arial; color: #333; text-align: center;';
							}

							$defeito_peca = (empty($defeito_peca)) ? $defeito_constatado : $defeito_peca;
							$servico      = (empty($servico)) ? $solucao : $servico;
								$xls_peca .= ($y == 0) ? '' : "<td colspan='7'>&nbsp;</td>";
								$xls_peca .= "<td style='$style_das_pecas' NOWRAP >$peca_os</td>";
								$xls_peca .= "<td style='$style_das_pecas' NOWRAP >$peca_referencia</td>";
								$xls_peca .= "<td style='$style_das_pecas' NOWRAP width='400'>$peca_descricao</td>";
								$xls_peca .= "<td style='$style_das_pecas' NOWRAP >$defeito_reclamado</td>";
								$xls_peca .= "<td style='$style_das_pecas' NOWRAP >$defeito_peca</td>";
								$xls_peca .= "<td style='$style_das_pecas' NOWRAP >$servico</td>";
								$xls_peca .= "<td style='$style_das_pecas' NOWRAP >$qtde</td>";
							$xls_peca .= "</tr>\n";

						}
						fputs($fp,$xls_peca);

					}
					$xls_space = "<tr>
								<td colspan='14' >&nbsp;</td>
							</tr>
							<tr>
								<td colspan='14' >&nbsp;</td>
							</tr>\n";
					fputs($fp,$xls_space);

				}

				fputs($fp,"</table>\n");
				fclose ($fp);

				rename ($path_tmp.$arquivo_nome3,$path_tmp.$arquivo_nome4);
				exec("zip -jmo $path/$arquivo_nome4.zip $path_tmp$arquivo_nome4 > /dev/null");

				echo "<br />";

				$conteudo2 .= "
				<a href='../xls/$arquivo_nome4' target='_blank'>
					<div id='gerar_excel' class='btn_excel'>
						<input type='hidden' id='jsonPOST' value='<?=$jsonPOST?>' />
						<span><img src='imagens/excel.png' /></span>
						<span class='txt'>Realizar Download</span>
					</div>
				</a> <br />
				";

				echo $conteudo2.$dados_table;

			}
		}
		/* fim gera relatório defeito por produto Gama Italy */

	}

flush();

?>

</div>

<? include "../rodape.php" ?>
