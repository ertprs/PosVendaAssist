<?php
/**
 *	@description Relatorio Pesquisa de Satisfação - HD 1764897
 *  @author Guilherme Monteiro.
 **/
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";
include 'funcoes.php';


// $ajax = $_GET['ajax'];
// $os   = $_GET['os'];

// if($ajax == 'verifica_troca'){
// 	$sql = "SELECT COUNT(*)
//               FROM tbl_os_item
//               JOIN tbl_os_produto  ON tbl_os_item.os_produto  = tbl_os_produto.os_produto
//               JOIN tbl_os          ON tbl_os_produto.os       = tbl_os.os
//               JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
//               JOIN tbl_pedido      ON tbl_pedido_item.pedido  = tbl_pedido.pedido
//               WHERE tbl_os.os           = $os
//               AND tbl_os.fabrica       = $login_fabrica
//               AND tbl_pedido_item.qtde = tbl_pedido_item.qtde_cancelada";

//     #$res_item_cancelado = pg_query($con, $sql);

//     $sql = "SELECT COUNT(tbl_os_item.os_item)
//               FROM tbl_os
//               JOIN tbl_os_produto ON tbl_os.os                 = tbl_os_produto.os
//               JOIN tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
//               WHERE tbl_os.os               = $os
//                AND tbl_os_item.pedido_item IS NOT NULL
//                AND tbl_os.fabrica          = $login_fabrica";

//     #$res_total_item = pg_query($con, $sql);

//     $sql = "SELECT COUNT(tbl_os_troca.os)
//               FROM tbl_os_troca
//              WHERE tbl_os_troca.os     = $os
//                AND tbl_os_troca.fabric = $login_fabrica";

//     $res_total_troca = pg_query($con, $sql);

//     #$total_cancelados = pg_result($res_item_cancelado, 0, 0);
//     #$total_itens_os   = pg_result($res_total_item, 0, 0);
//     $total_troca      = pg_result($res_total_troca, 0, 0);

//     if ($total_troca == 0) {
//         echo 1;
//     } else {
//         echo 0;
//     }

//     exit;
// }

if ( $_POST["btn_acao"] == "submit") {

	$data_inicial 			= $_POST["data_inicial"];
	$data_final 				= $_POST["data_final"];
	$status_os 					= $_POST["status_os"];
	$tipo_os 						= $_POST["tipo_os"];
	$descricao_posto 		= $_POST["descricao_posto"];
	$codigo_posto 			= $_POST["codigo_posto"];
	$produto_referencia = $_POST["produto_referencia"];
	$produto_descricao 	= $_POST["produto_descricao"];
	$peca_referencia 		= $_POST["peca_referencia"];
	$peca_descricao 		= $_POST["peca_descricao"];
	$linha 							= $_POST["linha"];
	$familia 						= $_POST["familia"];
	$os_aberto 					= $_POST["os_aberto"];		

//echo $os_aberto;exit;
	### DATA INICIAL / DATA FINAL
	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
			// else{
			// 	if (strtotime("$aux_data_inicial + 1 month" ) < strtotime($aux_data_final)){
			// 	 	$msg_erro["msg"][] = 'O intervalo entre as datas não pode ser maior que um mês.';
			// 	 	$msg_erro["campos"][] = "data";
			// 	}
			// }
		}
	}

	### POSTO
	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	### PRODUTO
	if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				AND (
                  	(UPPER(referencia) = UPPER('{$produto_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$produto_descricao}'))
                )";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto";
		} else {
			$produto = pg_fetch_result($res, 0, "produto");
		}
	}

	### PEÇA
	if (strlen($peca_referencia) > 0 or strlen($peca_descricao) > 0){
		$sql = "SELECT peca
				FROM tbl_peca
				WHERE fabrica = {$login_fabrica}
				AND (
                    (UPPER(referencia) = UPPER('{$peca_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$peca_descricao}'))
                )";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Peça não encontrada";
			$msg_erro["campos"][] = "peca";
		} else {
			$peca = pg_fetch_result($res, 0, "peca");
		}
	}

	### LINHA
	if (strlen($linha) > 0) {
		$sql = "SELECT linha FROM tbl_linha WHERE fabrica = {$login_fabrica} AND linha = {$linha}";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Linha não encontrada";
			$msg_erro["campos"][] = "linha";
		}
	}

	### FAMILIA
	if (strlen($familia)) {
		$sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND familia = {$familia}";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Familia não encontrada";
			$msg_erro["campos"][] = "familia";
		}
	}

	if(!count($msg_erro["msg"])){

		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}

		if (!empty($produto)){
			$cond_produto = " AND tbl_produto.produto = '{$produto}' ";
		}

		if (!empty($peca)){
			$cond_peca = " AND tbl_os.os IN (SELECT tbl_os_produto.os
						  FROM tbl_os_item
						  JOIN tbl_os_produto USING(os_produto)
						  JOIN tbl_peca USING(peca)
						  WHERE fabrica_i = {$login_fabrica}
						  AND tbl_os_produto.os = tbl_os.os
						  AND tbl_peca.peca = '{$peca}' LIMIT 1) ";
		}

		if ($linha) {
			$cond_linha = " AND tbl_produto.linha = {$linha} ";
		}

		if ($familia) {
			$cond_familia = " AND tbl_produto.familia = {$familia} ";
		}

		if(!empty($status_os)){
			$cond_status_os = "AND tbl_os.status_checkpoint = '$status_os'";
		}

		if(!empty($tipo_os)){
		 	$cond_tipo_os = "AND tbl_os.consumidor_revenda = UPPER('$tipo_os')";
		}

		if(!empty($os_aberto)){
		 	$cond_os_aberto = "AND tbl_os.data_fechamento is null AND tbl_os.finalizada is null";
		}

		$sql = " SELECT
							tbl_os.os,
							tbl_os.sua_os,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome AS nome_posto,
							tbl_produto.referencia AS codigo_produto, 
							tbl_produto.descricao AS desc_produto,
							tbl_marca.nome AS marca
					FROM tbl_os 
							JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
							JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = tbl_os.fabrica AND tbl_os.fabrica = $login_fabrica
							LEFT JOIN tbl_marca  ON tbl_produto.marca = tbl_marca.marca AND tbl_marca.fabrica = $login_fabrica
					WHERE tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
							$cond_posto
							$cond_produto
							$cond_peca
							$cond_linha
							$cond_familia
							$cond_status_os
							$cond_tipo_os
							$cond_os_aberto";
		$resSubmit = pg_query($con, $sql);

		//echo nl2br($sql);exit;
	}
}


$layout_menu = "callcenter";
$title = "TROCA EM MASSA";
include "cabecalho_new.php";

$plugins = array(
"autocomplete",
"datepicker",
"shadowbox",
"mask"
);

include("plugin_loader.php");

?>

<script language="javascript">
var hora = new Date();
var engana = hora.getTime();

$(function() {

	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("produto", "peca", "posto"));
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this), Array("produtoAcao","pecaAcao","posicao","pecaPara","listaTroca","sem-de-para"));
	});


	if($("#troca_produto").attr('checked',true)){
		$('#troca_de_pecas input').val("");
		$('#pecas_para input').val("");
		$('#troca_de_produto input').val("");
		$("#btn_trocar_peca").hide();
		$("#ref_peca_carteira").attr('disabled',true);
		$("#desc_peca_carteira").attr('disabled',true);
		$("#ref_produto_carteira").attr('disabled',false);
		$("#desc_produto_carteira").attr('disabled',false);
		$("#pecas_para").hide();
	}

	$("#troca_produto").click(function(){
		$('#troca_de_pecas input').val("");
		$('#pecas_para input').val("");
		$("#btn_trocar_peca").hide();
		$("#btn_trocar_produto").show();
		$("#ref_peca_carteira").attr('disabled',true);
		$("#desc_peca_carteira").attr('disabled',true);
		$("#ref_produto_carteira").attr('disabled',false);
		$("#desc_produto_carteira").attr('disabled',false);
		$("#pecas_para").hide();
		$("#opcoes_troca_produto").show();

	});

	$("#troca_peca").click(function(){
		$('#troca_de_produto input').val("");
		$("#btn_trocar_produto").hide();
		$("#btn_trocar_peca").show();
		$("#troca_produto").attr('checked',false)
		$("#ref_peca_carteira").attr('disabled',false);
		$("#desc_peca_carteira").attr('disabled',false);
		$("#ref_produto_carteira").attr('disabled',true);
		$("#desc_produto_carteira").attr('disabled',true);
		$("#pecas_para").show();
		$("#opcoes_troca_produto").hide();
	});

	$("input[name=checar_todos]").change(function () {
		if ($(this).is(":checked")) {
			$("input[name='check[]']").each(function () {
				$(this)[0].checked = true;
			});
		} else {
			if ($("input[name='check[]']:checked").length > 0) {
				$("input[name='check[]']").each(function () {
					$(this)[0].checked = false;
				});
			}
		}
	});

});

function retorna_produto (retorno) {

	if(retorno.produtoAcao){
		$("#ref_produto_carteira").val(retorno.referencia);
		$("#desc_produto_carteira").val(retorno.descricao);
	}else{
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}
}

function retorna_peca(retorno){
	if(retorno.pecaAcao){
		$("#ref_peca_carteira").val(retorno.referencia);
		$("#desc_peca_carteira").val(retorno.descricao);
	}else if(retorno.pecaPara){
		$("#ref_peca_carteira_"+retorno.posicao).val(retorno.referencia);
	 	$("#desc_peca_carteira_"+retorno.posicao).val(retorno.descricao);
	}else{
  	$("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
	}
}

function retorna_posto(retorno){
  $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

// TROCAR PRODUTO
function trocar_produto(){
	if(confirm('Confirma a troca do(s) produto(s) desta(s) OS(s) nas condições estabelecidas?')){

		var os = "";
		var array_os = new Array();
		var json = {};

    	var ref_produto_carteira 		= $("#ref_produto_carteira").val();
		
		$("input[name='check[]']:checked").each(function(){	

      		var linha = $(this);
	    	os = $(this).val();
	    	array_os.push(os);
      
		});

		json = array_os;
	  	json = JSON.stringify(json);

	  	<?php 
	  		$campos = array(
	  			"setor" 				=> "Setor Responsável",
	  			"situacao_atendimento" 	=> "Situação do Atendimento",
	  			"envio_consumidor"		=> "Destino",
	  			"modalidade_transporte" => "Modalidade de Transporte",
	  			"gerar_pedido"			=> "Gerar Pedido",
	  			"causa_troca"			=> "Causa da troca/Ressarcimento",
	  		);

	  		foreach ($campos as $key => $value) {
	  			echo "var {$key} = $('select[name={$key}]').val(); \n";
	  		}

	  		foreach ($campos as $key => $value) {
	  			echo "
	  				if({$key} == ''){ \n
	  					alert('O campo {$value} é obrigatório'); \n
	  					$('select[name={$key}]').focus(); \n
	  					return; \n
	  				} \n
	  			";
	  		}

	  		$json = array();
	  		foreach ($campos as $key => $value) {
	  			$json[] = $key." : ".$key;
	  		}

	  		$json = implode(",", $json);

	  	?>
	  
	  	$.ajax({
		  	url: "troca_em_massa_britania.php",
		  	async:false,
		  	type: "POST",
		  	data: {
		  			osacao			     :"trocar_produto", 
		  			os                   :json,
		  			ref_produto_carteira :ref_produto_carteira,
		  			<?php echo $json; ?>
		  		},
		  	beforeSend: function(){
		  		$("#carregando_produto").html('<em>Carregando...</em>');
		  	},
		  	complete: function(retorno){
	  			var resposta = JSON.parse(retorno.responseText);
	  			var statuss = resposta.statuss;
	  			var msg = resposta.mensagem;

	  			$("#carregando_produto").html('');

	  			if(statuss == 'error'){
	  				$('#mensagem').html("<div class='alert alert-error'><h4>"+msg+"</h4></div>");
	  			}

	  			if(statuss == 'ok'){
	  				$(document).find("input[name^=check]:checked").each(function(){
	            $(this).parents("tr").remove();
		        });
					$('#mensagem').html("<div class='alert alert-success'><h4>"+msg+"</h4></div>");
	  			}
		  	}
	  	});
	}
}
// ---- //

function trocar_peca(){
	if(confirm('Confirma a troca da(s) peça(s) desta(s) OS(s) nas condições estabelecidas?')){

		var os = "";
		var array_os = new Array();
		var peca = "";
		var array_pecas = new Array();
		var json_pecas = {};
    var json = {};
	  var ref_peca_carteira 			=	$("#ref_peca_carteira").val();
		
		$("input[name='check[]']:checked").each(function(){			
      var linha = $(this);
	    os = $(this).val();
	    array_os.push(os);
    });

		$("input[name='ref_peca_carteira[]']").each(function(){
  		var linha_peca = $(this);
  		peca = $(this).val();
  		if(peca != ''){
  			array_pecas.push(peca);
  		}
  	});

		json_pecas = array_pecas;
		json_pecas = JSON.stringify(json_pecas);
		json = array_os;
	  json = JSON.stringify(json);
	  $.ajax({
	  	url: "troca_em_massa_britania.php",
	  	async:false,
	  	type: "POST",
	  	data: {osacao:"trocar_peca", os:json,peca:json_pecas,ref_peca_carteira:ref_peca_carteira},
	  	beforeSend: function(){
	  		$("#carregando_peca").html('<em>Carregando...</em>');
	  	},
	  	complete: function(retorno){
  			var resposta = JSON.parse(retorno.responseText);
  			var statuss = resposta.statuss;
  			var msg = resposta.mensagem;

  			$("#carregando_peca").html('');

  			if(statuss == 'error'){
					$('#mensagem').html("<div class='alert alert-error'><h4>"+msg+"</h4></div>");
				}

  			if(statuss == 'ok'){
  				$(document).find("input[name^=check]:checked").each(function(){
            $(this).parents("tr").remove();
	        });
					$('#mensagem').html("<div class='alert alert-success'><h4>"+msg+"</h4></div>");
				}
	  	}
	  });
	}
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
<!-- Monteiro Inicio -->
<div class="row">
<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br />
	<!-- Campos Data -->
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>">
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
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=isset($_POST['data_final'])?$_POST['data_final'] : ''?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- /// -->
	<!-- Campo Posto -->
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
						<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- /// -->
	<!-- Campo produto -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_referencia'>Ref. Produto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?php echo $produto_referencia;?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" produtoPesquisa="true" tipo="produto" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_descricao'>Descrição Produto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<?php echo $produto_descricao;?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- /// -->
	<!-- Campo peça -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_referencia'>Ref. Peças</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" sem-de-para="true" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_descricao'>Descrição Peça</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" sem-de-para="true" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- /// -->
	<!-- Campo linha / Familia -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='linha'>Linha</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="linha" id="linha">
							<option value=""></option>
							<?php
							$sql = "SELECT linha, nome
									FROM tbl_linha
									WHERE fabrica = $login_fabrica
									AND ativo";
							$res = pg_query($con,$sql);

							foreach (pg_fetch_all($res) as $key) {
								$selected_linha = ( isset($linha) and ($linha == $key['linha']) ) ? "SELECTED" : '' ;

							?>
							<option value="<?php echo $key['linha']?>" <?php echo $selected_linha ?> >
							<?php echo $key['nome']?>
							</option>
							<?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='familia'>Familia</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="familia" id="familia">
							<option value=""></option>
							<?php
							$sql = "SELECT familia, descricao 
									FROM tbl_familia 
									WHERE fabrica = $login_fabrica 
									AND ativo 
									ORDER BY descricao";
							$res = pg_query($con,$sql);
							foreach (pg_fetch_all($res) as $key) {
								$selected_familia = ( isset($familia) and ($familia == $key['familia']) ) ? "SELECTED" : '' ;
							?>
							<option value="<?php echo $key['familia']?>" <?php echo $selected_familia; ?> >
							<?php echo $key['descricao']; ?>
							</option>
							<?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- /// -->
		
	<!-- Campo Status OS / Tipo OS -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("status_os", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='status_os'>Status OS</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="status_os" id="status_os">
							<option value=""></option>

							<?php
							$sql = "SELECT  status_checkpoint,
															descricao
											FROM 		tbl_status_checkpoint
											
							";
							$res_status = pg_query($con,$sql);
							
							foreach (pg_fetch_all($res_status) as $key) {
								$selected_status_os = ( isset($status_os) and ($status_os == $key['status_checkpoint']) ) ? "SELECTED" : '' ;
							?>
							<option value="<?php echo $key['status_checkpoint']; ?>" <?php echo $selected_status_os; ?> >
							<?php echo $key['descricao']; ?>
							</option>
							<?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("tipo_os", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='tipo_os'>Tipo OS</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="tipo_os" id="tipo_os">
							<option value=""></option>
							<option value="C" <? if($tipo_os == 'C'){echo "SELECTED";} ?> >Consumidor</option>
							<option value="R" <? if($tipo_os == 'R'){echo "SELECTED";} ?> >Revenda</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- /// -->
	<!-- OS em Aberto / OS não atendida -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
	  	<label class="checkbox">
	      <input type="checkbox" name="os_aberto" value="os_aberto">
	        Apenas OS em Aberto
	    </label>
		</div>
	</div>
	<!-- /// -->
	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

<?php 
if (isset($resSubmit)) {

 	if (pg_num_rows($resSubmit) > 0) {

 		$rows = pg_num_rows($resSubmit);

?>
	
		<!-- ACÃO CARTEIRA -->
		<form name='frm_relatorio' METHOD='POST' align='center' class='form-search form-inline tc_formulario' >
			<div class='titulo_tabela '>Ação de Carteira</div>
			<br />
			<div class='row-fluid' style="height: 30px;">
				<div class='span2'></div>
				<div class='span8'>
			  		<label class="radio">
			      	<input type="radio" name="troca_produto" id="troca_produto" value="troca_produto" checked>
			        	<strong>Trocar produto</strong>
			    	</label>
				</div>
				<div class='span2'></div>
			</div>

			<!-- Campo produto -->
			<div id="troca_de_produto">
				<div class='row-fluid'>
					<div class='span2'></div>
					<div class='span4'>
						<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='ref_produto_carteira'>Ref. Produto</label>
							<div class='controls controls-row'>
								<div class='span7 input-append'>
									<input type="text" id="ref_produto_carteira" name="ref_produto_carteira" class='span12' maxlength="20" value="<?php echo $produto_referencia;?>" >
									<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
									<input type="hidden" name="lupa_config" tipo="produto" produtoAcao="true" listaTroca="true" parametro="referencia" />
								</div>
							</div>
						</div>
					</div>
					<div class='span4'>
						<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='desc_produto_carteira'>Descrição Produto</label>
							<div class='controls controls-row'>
								<div class='span12 input-append'>
									<input type="text" id="desc_produto_carteira" name="desc_produto_carteira" class='span12' value="<?php echo $produto_descricao;?>" >
									<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
									<input type="hidden" name="lupa_config" tipo="produto" produtoAcao="true" listaTroca="true" parametro="descricao" />
								</div>
							</div>
						</div>
					</div>
					<div class='span2'></div>
				</div>
			</div>

			<br />

			<div id="opcoes_troca_produto">

				<div class="row-fluid">
					<div class="span2"></div>
					<div class="span4">
						Setor Responsável <br />
						<select name="setor">
							<option value=""></option>
							<option value="Revenda">Revenda</option>
							<option value="Carteira">Carteira</option>
							<option value="SAC">SAC</option>
							<option value="Procon">Procon</option>
							<option value="SAP">SAP</option>
							<option value="SAP">Suporte Técnico</option>
						</select>
					</div>
					<div class="span4">
						Situação do Atendimento <br />
						<select name="situacao_atendimento">
							<option value=""></option>
							<option value="0">Produto em Garantia</option>
							<option value="50">Faturado 50%</option>
							<option value="100">Faturado 100%</option>
						</select>
					</div>
					<div class="span2"></div>
				</div>

				<div class="row-fluid">
					<div class="span2"></div>
					<div class="span4">
						Destino <br />
						<select name="envio_consumidor">
							<option value=""></option>
							<option value="t">Direto ao Consumidor</option>
							<option value="f">Para o Posto</option>
						</select>
					</div>
					<div class="span4">
						Modalidade de Transporte <br />
						<select name="modalidade_transporte">
							<option value=""></option>
							<option value="urgente">RI Urgente</option>
							<option value="normal">RI Normal</option>
						</select>
					</div>
					<div class="span2"></div>
				</div>

				<div class="row-fluid">
					<div class="span2"></div>
					<div class="span4">
						Gerar Pedido <br />
						<select name="gerar_pedido" style="width: 100px;">
							<option value=""></option>
							<option value="t">Sim</option>
							<option value="f">Não</option>
						</select>
					</div>
					<div class="span4">
						Selecinar causa da troca/Ressarcimento  <br />
						<select name="causa_troca">3
							<option value=""></option>
						<?php			 
						$sql = "SELECT  tbl_causa_troca.causa_troca,
							tbl_causa_troca.codigo     ,
							tbl_causa_troca.descricao
							FROM tbl_causa_troca
							WHERE tbl_causa_troca.fabrica = $login_fabrica
							AND tbl_causa_troca.ativo     IS TRUE
							ORDER BY tbl_causa_troca.codigo,tbl_causa_troca.descricao";
                                    $resTroca = pg_query ($con,$sql);
                                        for ($i = 0 ; $i < pg_num_rows($resTroca) ; $i++) {
                                            $aux_causa_troca = pg_fetch_result ($resTroca,$i,'causa_troca');

                                            if ($causa_troca == $aux_causa_troca) {
                                                $selected = "selected";
                                            }
                                            else {
                                                $selected = "";
                                            }

                                            echo "<option $selected value='" . $aux_causa_troca . "'";
                                            echo ">" . pg_fetch_result ($resTroca,$i,codigo) . " - " . pg_fetch_result ($resTroca,$i,descricao) . "</option>";
                                        }
                                ?>
						</select>
					</div>
					<div class="span2"></div>
				</div>				

			</div>

			<!-- /// -->
			<br />
			<!-- De -->
			<div class='row-fluid' style="height: 30px;">
				<div class='span2'></div>
				<div class='span4'>
		  		<label class="radio">
		      	<input type="radio" name="troca_produto" id="troca_peca" value="troca_peca">
		        	<strong>Trocar peças (de:)</strong>
		    	</label>
				</div>
			</div>
			<!-- /// -->
			
			<!-- Campo peça -->
			<div id="troca_de_pecas">
				<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='ref_peca_carteira'>Ref. Peças</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<input type="text" id="ref_peca_carteira" name="ref_peca_carteira" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" display="none" >
								<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
								<input type="hidden" name="lupa_config" tipo="peca" pecaAcao="true" parametro="referencia" />
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='desc_peca_carteira'>Descrição Peça</label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<input type="text" id="desc_peca_carteira" name="desc_peca_carteira" class='span12' value="<? echo $peca_descricao ?>" >
								<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" tipo="peca" pecaAcao="true" parametro="descricao" />
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
				</div>
			</div>
			<!-- /// -->
			<!-- Para -->
			<div id="pecas_para" hidden><br />
			<div class='row-fluid'style="height: 30px;">
				<div class='span2'></div>
				<div class='span4'>
		  		<label>
		      		<strong>Para</strong>
		    	</label>
				</div>
			</div>
			<!-- /// -->
			<!-- Peças para -->
			<?
				$qtde_pecas = 5;
				for ($i=0; $i <$qtde_pecas ; $i++) { 
			?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='ref_peca_carteira_<? echo $i; ?>'>Ref. Peças</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<input type="text" id="ref_peca_carteira_<? echo $i; ?>" name="ref_peca_carteira[]" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
								<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
								<input type="hidden" name="lupa_config" tipo="peca" posicao="<? echo $i; ?>" pecaPara="true" parametro="referencia" />
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='desc_peca_carteira_<? echo $i; ?>'>Descrição Peça</label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<input type="text" id="desc_peca_carteira_<? echo $i; ?>" name="desc_peca_carteira[]" class='span12' value="<? echo $peca_descricao ?>" >
								<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" tipo="peca" posicao="<? echo $i; ?>" pecaPara="true" parametro="descricao" />
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
			<?		
				}
			?>
			</div>
			<!-- -->
			<!-- Trocar Produto -->
			
				<p id="btn_trocar_produto"><br/>
					<input type='button' class="btn" value='Gerar Troca em Lote' onclick='trocar_produto()' />
					<input type="hidden" name="btn_troca" id="btn_troca" value="trocar_produto" />
					<span id="carregando_produto"></span>
				</p><br/>
			
				
				<p id="btn_trocar_peca"><br/>
					<input type='button' class="btn" value='Gerar Troca em Lote' onclick='trocar_peca()' />
					<input type="hidden" name="btn_troca" id="btn_troca" value="trocar_peca" />
					<span id="carregando_produto"></span>
				</p><br/>
			
			<!-- /// -->
		</form>
		<!-- /// -->
		<div class="container" id="mensagem">

		</div>

		<!-- Resultado da Pesquisa -->
		<table id="resultado_troca_em_massa" class="table table-bordered table-striped table-hover table-full-large">
	  	<thead>
	  		<tr class="titulo_coluna">
					<th><input name="checar_todos" type="checkbox"></th>
					<th>OS</th>
					<th>Cód. Posto</th>
					<th>Nome Posto</th>
					<th>Marca</th>
					<th>Cód. Produto</th>
					<th>Produto</th>
				</tr>
			</thead>
			<tbody>
<?							  
		for ($i = 0; $i < $rows; $i++) {
			$os 						= pg_fetch_result($resSubmit, $i, 'os');
			$sua_os 	 			= pg_fetch_result($resSubmit, $i, 'sua_os');
			$cod_posto 			= pg_fetch_result($resSubmit, $i, 'codigo_posto');
			$nome_posto     = pg_fetch_result($resSubmit, $i, 'nome_posto');
			$marca 					= pg_fetch_result($resSubmit, $i, 'marca');
			$cod_produto 		= pg_fetch_result($resSubmit, $i, 'codigo_produto');
			$desc_produto        = pg_fetch_result($resSubmit, $i, 'desc_produto');
		
			echo '<tr>
				  		<td class="tac"><input name="check[]" type="checkbox" value="'.$os.'"/></td>
				  		<td><a href="os_press.php?os='.$os.'" target="_blank">'.$sua_os.'</a></td>
							<td>'.$cod_posto.'</td>
							<td>'.$nome_posto.'</td>
							<td>'.$marca.'</td>
							<td>'.$cod_produto.'</td>
							<td>'.$desc_produto.'</td>
			  	</tr>';
		}
echo "</tbody> </table>";
?>
<?
 }else{
 		echo '
 			<div class="container">
 			<div class="alert">
 				    <h4>Nenhum resultado encontrado</h4>
 			</div>
 			</div>';
 	}
}
?>

<?php include 'rodape.php'; ?>
