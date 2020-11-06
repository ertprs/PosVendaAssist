<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj         = trim(pg_fetch_result($res,$i,cnpj));
				$nome         = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

if($_GET['ajax_audita']){

	$status  = $_GET['status'];
	$pedidos = str_replace("\\","",$_GET['pedidos']);
	$pedidos = utf8_encode($pedidos);
	$pedidos = json_decode($pedidos,true);
	$motivo  = (!empty($_GET['motivo'])) ? $_GET['motivo'] : "Aprovado em Auditoria Admin";
	$motivo = pg_escape_string($motivo);

	foreach ($pedidos as $key => $pedido) {
		$sql = "UPDATE tbl_pedido SET status_pedido = $status, valores_adicionais = valores_adicionais::jsonb - 'pendencia_aprovacao_admin'  WHERE pedido = $pedido AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);

		if(empty($msg_erro)){

			if($status == 14){
				$sql = "UPDATE tbl_pedido_item
					set qtde_cancelada = tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada
					where pedido = $pedido;";
				$res = pg_exec($con,$sql);
			}

			$sql = "INSERT INTO tbl_pedido_status(pedido,status,observacao,admin) VALUES($pedido,$status,'$motivo',$login_admin)";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
			if(!empty($msg_erro)){
				echo $msg_erro; exit;
			}

			$vl_add = json_encode(["aprovado"=>true]);

			$sql_libera_demanda = " UPDATE tbl_pedido_item SET valores_adicionais = coalesce(valores_adicionais::jsonb, '$vl_add') || '$vl_add' WHERE pedido_item IN (SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido AND valores_adicionais::jsonb->>'aprovado' = 'false' AND valores_adicionais::jsonb->>'demanda' = 'true')";
			$res_libera_demanda = pg_query($con, $sql_libera_demanda);
			if (pg_last_error()) {
				$msg_erro .= "<br /> ".pg_last_error();
			}

		}else{
			echo $msg_erro; exit;
		}
	}

	if(in_array($login_fabrica, array(1))){

		$status_desc = ($status == "14") ? "Reprovado" : "Aprovado";
		$pedidos_admin_arr = array();

		foreach ($pedidos as $pedido) {

			$sql_admin_pedidos = "
								SELECT
									tbl_pedido.admin,
									tbl_admin.nome_completo AS nome,
									tbl_admin.email,
									tbl_pedido.seu_pedido,
									tbl_pedido.posto
								FROM tbl_pedido
								INNER JOIN tbl_admin ON tbl_admin.admin = tbl_pedido.admin
								WHERE
									tbl_pedido.pedido = {$pedido}
									AND tbl_pedido.admin NOTNULL";
									
			$res_admin_pedidos = pg_query($con, $sql_admin_pedidos);

			if(pg_num_rows($res_admin_pedidos) > 0){

				$admin     = pg_fetch_result($res_admin_pedidos, 0, "admin");
				$nome      = pg_fetch_result($res_admin_pedidos, 0, "nome");
				$email     = pg_fetch_result($res_admin_pedidos, 0, "email");
				$pedido_bd = pg_fetch_result($res_admin_pedidos, 0, "seu_pedido");
				$posto     = pg_fetch_result($res_admin_pedidos, 0, "posto");

				if(count($pedidos_admin_arr) == 0){

					$pedidos_admin_arr[$admin] = array("nome" => $nome, "email" => $email, "pedidos" => array($pedido), "pedidos_bd" => array($pedido_bd), "posto" => array($posto));
				}else{

					$existe_pos = false;

					foreach ($pedidos_admin_arr as $key => $value) {
						if($admin == $key){
							$existe_pos = true;
							continue;
						}
					}

					if($existe_pos){
						$pedidos_admin_arr[$admin]["pedidos"][] = $pedido;
						$pedidos_admin_arr[$admin]["pedidos_bd"][] = $pedido_bd;
						$pedidos_admin_arr[$admin]["posto"][] = $posto;
					}else{
						$pedidos_admin_arr[$admin] = array("nome" => $nome, "email" => $email, "pedidos" => array($pedido), "pedidos_bd" => array($pedido_bd), "posto" => array($posto));
					}

				}

			}

		}

		/* Send Mail */
		include_once '../class/communicator.class.php';

		$mailTc = new TcComm($externalId);

		foreach ($pedidos_admin_arr as $dados_admin) {
			unset($xemail);
			$nome    = $dados_admin["nome"];
			$email   = $dados_admin["email"];
			$posto   = $dados_admin["posto"];

			$mensagem_posto = "";

			if (strlen($posto) > 0) {
				$aux_sql = "SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto
							FROM tbl_posto
							JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							WHERE tbl_posto.posto = $posto";
				$aux_res = pg_query($con, $aux_sql);
				$aux_row = pg_num_rows($aux_res);

				if ($aux_row > 0) {
					$posto_nome   = pg_fetch_result($aux_res, 0, 'nome');
					$posto_codigo = pg_fetch_result($aux_res, 0, 'codigo_posto');

					$mensagem_posto = "<br><br> Referente ao Posto: <strong>$posto_codigo</strong> - <strong>$posto_nome</strong>";
				}
			}

			if(count($pedidos) > 0){

				if($_serverEnvironment == "development"){
					$xemail[] = "joao.junior@telecontrol.com.br";
				}else{
					$xemail[] = $email;
					$xemail[] = "joao.junior@telecontrol.com.br";
				}

				$pedidos_desc = (count($dados_admin["pedidos_bd"]) > 1) ? implode("<br />", $dados_admin["pedidos_bd"]) : $dados_admin["pedidos_bd"][0];

				$assunto = (count($dados_admin["pedidos"]) > 1) ? "Pedidos {$status_desc}s - StanleyBlack&Decker" : "Pedido {$status_desc} - StanleyBlack&Decker";

				$frase_email = (count($dados_admin["pedidos"]) > 1) ? "Os seguintes pedidos foram <strong>{$status_desc}s</strong>" : "O pedido foi <strong>{$status_desc}</strong>";

				$mensagem = "Prezado(a) {$nome}, <br /> {$frase_email} no sistema da Telecontrol: <br /> <br /> <strong>{$pedidos_desc}</strong>{$mensagem_posto}";

				$mensagem .= (strlen($_GET["motivo"]) > 0) ? "<br /> <br /> Motivo: {$motivo}" : "";

		        $res_send_mail = $mailTc->sendMail(
					$xemail,
					$assunto,
					$mensagem,
					$externalEmail
				);

			}

		}

		/* Fim - Send Mail */

	}

	echo "ok";

	exit;
}
$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);

if($btn_acao == "Pesquisar"){
    $posto_codigo       = $_POST['posto_codigo'];
    $posto_nome         = $_POST['posto_nome'];
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $status             = $_POST['status'];
    $categoria_pedido   = $_POST['categoria_pedido'];

	if ((empty($data_final) || empty($data_final)) && (($login_fabrica != 1) || ($login_fabrica == 1 && $status != "aguardando"))) {
		$msg_erro = "Informe um intervalo entre datas";
	} else {
        if ($login_fabrica == 1 && $status != "aguardando") {
            list($di, $mi, $yi) = explode("/", $data_inicial);

            if (!checkdate($mi,$di,$yi)) {
                $msg_erro = "Data Inválida";
            }

            list($df, $mf, $yf) = explode("/", $data_final);

            if (!checkdate($mf,$df,$yf)){
                $msg_erro = "Data Inválida";
            }

            if (strlen($msg_erro) == 0) {

                $aux_data_inicial = "$yi-$mi-$di";
                $aux_data_final = "$yf-$mf-$df";

                if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                    $msg_erro = "Data Inválida";
                }else{
                    $cond = " AND tbl_pedido.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
                }
            }
        }
	}

	if(empty($msg_erro)){
		if(!empty($posto_codigo)){
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$posto_codigo'";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$posto = pg_fetch_result($res, 0, 'posto');
				$cond .= " AND tbl_pedido.posto = $posto ";
			}else{
				$msg_erro = "Posto não encontrado";
			}
		}
		if(!empty($status)){
			switch ($status) {
				case 'aprovados':
					$cond .= " AND tbl_pedido.status_pedido not in (14,18) ";
					$status_pedido = 1;
					break;
				case 'aguardando':
					$cond .= " AND tbl_pedido.status_pedido in(18,33) ";
					$status_pedido = "18,33";
					break;
				case 'recusados':
					$cond .= " AND tbl_pedido.status_pedido = 14 ";
					$status_pedido = 14;
					break;
			}
		}

		if(!empty($tipo)){
			switch ($tipo) {
				case 'garantia':
					$cond .= " AND tbl_pedido.tipo_pedido = 87 ";
					break;
				case 'faturado':
					$cond .= " AND tbl_pedido.tipo_pedido = 86 ";
					break;
			}
		}

		if(in_array($login_fabrica, array(1))) {
            if (!empty($categoria_pedido)) {
                $cond .= " AND JSON_FIELD('categoria_pedido',tbl_pedido.valores_adicionais) = '$categoria_pedido'";
            }
			$cond .= " AND tbl_pedido.finalizado IS NOT NULL ";
		}
	}
}

$layout_menu = "auditoria";
$title = "APROVAÇÃO PEDIDO SEDEX";

include "cabecalho_new.php";

?>

<style type="text/css">

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
width: 700px;
margin: 0 auto;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
text-align: left;
}

.subtitulo {
background-color: #7092BE;
font:bold 11px Arial;
color: #FFFFFF;
}

.sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}

.espaco{
	padding-left: 80px;
}

</style>
<?php 
	  
	  $plugins = array(
		    "autocomplete",
		    "datepicker",
		    "shadowbox",
		    "mask",
		    "dataTable"
		);

	  include("plugin_loader.php");
?>

<!-- <script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<link rel="stylesheet" href="../plugins/bootstrap3/css/bootstrap.min.css"> -->


<script language="JavaScript">

$().ready(function() {

	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("posto"));
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[2];
	}

	$("select[name='select_acao']").change(function(){
		var status = $(this).val();
		if(status == 14){
			$("#col_motivo").show();
		}else{
			$("#col_motivo").hide();
		}
	});

	$("input[name^=check_], input[name='todos']").change(function(){
		var check = $(this).attr("name");
		var json = {};

		if(check == "todos"){
			if( $(this).is(":checked") ){
				$("input[name^=check_]").each(function(){
					 $(this).attr("checked",true);
				});
			}else{
				$("input[name^=check_]").each(function(){
					$(this).attr("checked",false);
				});

        		$("input[name=pedidos_json]").val("");
        		return;
			}
		}

        $("input[name^=check_]").each(function(){
            if( $(this).is(":checked") ){
            	var rel = $(this).val().toString();
               json[rel] = $(this).val();
            }
        });

        console.log(JSON.stringify(json));
        $("input[name=pedidos_json]").val(JSON.stringify(json));
   });

	$("input[name=auditar]").click(function(){
		var status = $("select[name='select_acao']").val();
		var motivo = $("#motivo").val();
		var pedidos = $("input[name=pedidos_json]").val();

		if(status == 1){
			var msg = "Pedidos aprovados com sucesso";
		}else{
			var msg = "Pedidos recusados com sucesso";
		}

		if(pedidos == "" || pedidos == "{}"){
			alert("Informe pelo menos um pedido para auditar");
			return;
		}else if(status == ""){
			alert("Informe a ação que deseja realizar");
			return;
		}else if(status == 14 && motivo == ""){
			alert("Informe o motivo da recusa");
			return;
		}else{
			$.ajax({
				url: "aprova_pedido_sedex.php",
				dataType: "GET",
				data: "ajax_audita=sim&status="+status+"&pedidos="+pedidos+"&motivo="+motivo,
				beforeSend: function(){
					$(".load").text("enviando, por favor aguarde...");
				},
				complete: function(retorno){
					$(".load").text("");
					var retorno = retorno.responseText;
					if(retorno == "ok"){
						console.log(retorno);
						$("input[name^=check_]").each(function(){
				            if( $(this).is(":checked") ){
						$("input[name=pedidos_json]").val("");
						$(this).parent("td").parent("tr").remove();
				            }
				        });

				        $("#msg").addClass("sucesso").html(msg).show();
				        setTimeout(function(){
		            							$("#msg").hide()
		            						 },1000);
					}else{
						$("#msg").addClass("sucesso").html(retorno).show();
						setTimeout(function(){
		            							$("#msg").hide()
		            						 },1000);
					}
				}
			});
		}
	});

});

function retorna_posto(retorno){
        $("#posto_codigo").val(retorno.codigo);
		$("#posto_nome").val(retorno.nome);
    }

</script>

<?php include "javascript_pesquisas.php"; ?>

	<div id='msg' class='container alert alert-warning' style="display: none;" ></div>
	<div  align='center' style='margin:auto;width:700px;'></div>
	<?if(!empty($msg_erro)) { ?>
	<div id='msg_erro' class='container alert alert-warning' class='msg_erro' ><h4><?=$msg_erro?></h4></div>
	<? } ?>

	<form class="form-search form-inline tc_formulario" name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

		<input type="hidden" name="acao">

		<div class="titulo_tabela">Parâmetros de Pesquisa</div>
		<br />
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<label class='control-label' for="posto_codigo">Código do Posto</label>
				 <div class='controls controls-row'>
				 	<div class="span7 input-append">
				 		<input type="text" name="posto_codigo" id="posto_codigo" class="span12" value="<?echo $posto_codigo?>">
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                		<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
				 	</div>
				 </div>
			</div>
			<div class="span4">
				<label class='control-label' for="posto_nome">Razão Social do Posto</label>
				<div class='controls controls-row'>
				 	<div class="span12 input-append">
				 		<input type="text" name="posto_nome" id="posto_nome" class="span12" value="<?echo $posto_nome?>">
				 		<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
				 	</div>
				 </div>
			</div>	
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			<div class='span4'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

<?php
if ($login_fabrica == 1) {
?>
        <div class="row-fluid">
        	<div class='span2'></div>
			<div class="span4">
				<label class='control-label' for="categoria_pedido">Categoria Pedido</label>
                <select name="categoria_pedido" id="categoria_pedido">
                    <option value="">SELECIONE</option>
                    <option <?=($categoria_pedido == "cortesia") ? "selected" : ""?> value="cortesia">CORTESIA</option>
                    <option <?=($categoria_pedido == "credito_bloqueado") ? "selected" : ""?> value="credito_bloqueado">CRÉDITO BLOQUEADO</option>
                    <option <?=($categoria_pedido == "erro_pedido") ? "selected" : ""?> value="erro_pedido">ERRO DE PEDIDO</option>
                    <option <?=($categoria_pedido == "kit") ? "selected" : ""?> value="kit">KIT DE REPARO</option>
                    <option <?=($categoria_pedido == "midias") ? "selected" : ""?> value="midias">MÍDIAS</option>
                    <option <?=($categoria_pedido == "outros") ? "selected" : ""?> value="outros">OUTROS</option>
                    <option <?=($categoria_pedido == "valor_minimo") ? "selected" : ""?> value="valor_minimo">VALOR MÍNIMO</option>
                    <option <?=($categoria_pedido == "vsg") ? "selected" : ""?> value="vsg">VSG</option>
                    <option <?=($categoria_pedido == "divergencia") ? "selected" : ""?> value="divergencia">DIVERGÊNCIAS LOGÍSTICA/ESTOQUE</option>
		            <option <?=($categoria_pedido == "problema_distribuidor") ? "selected" : ""?> value="problema_distribuidor">PROBLEMAS COM DISTRIBUIDOR</option>
		            <option <?=($categoria_pedido == "acessorios") ? "selected" : ""?> value="acessorios">ACESSÓRIOS</option>
		            <option <?=($categoria_pedido == "item_similar") ? "selected" : ""?> value="item_similar">ITEM SIMILAR </option>
                </select>
            </div>
        </div>
<?php
}
?>
		<br />
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span10">
				<label class='control-label'><b>Status do Pedido:</b></label>
				<table width="100%">
					<tr>
						<td>
							<INPUT TYPE="radio" NAME="status" value='aguardando' checked='checked' />&nbsp;Aguardando Aprovação
							<INPUT TYPE="radio" NAME="status" value='aprovados'   <?php if(trim($status) == 'aprovados') echo "checked='checked'"; ?> />&nbsp;Pedidos Aprovados
							<INPUT TYPE="radio" NAME="status" value='recusados'  <?php if(trim($status) == 'recusados')  echo "checked='checked'"; ?> />&nbsp;Pedidos Recusados
						</td>
					</tr>
				</table>
			</div>
		</div>
		<br />
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span10">
				<label class='control-label'><b>Tipo do Pedido:</b></label>
				<table width="100%">
					<tr>
						<td>
							<INPUT TYPE="radio" NAME="tipo" value=''      	   <?php if(trim($tipo) == '')      	 echo "checked='checked'"; ?> />&nbsp;Todos
							<INPUT TYPE="radio" NAME="tipo" value='faturado'   <?php if(trim($tipo) == 'faturado')   echo "checked='checked'"; ?> />&nbsp;Faturados
							<INPUT TYPE="radio" NAME="tipo" value='garantia'   <?php if(trim($tipo) == 'garantia')   echo "checked='checked'"; ?> />&nbsp;Garantia
						</td>
					</tr>
				</table>
			</div>
		</div>
		<br />
		<div class="row-fluid" style="text-align: center;">
			<button class="btn" type="submit" name="btn_acao" value="Pesquisar">Pesquisar</button>
		</div>
	</form>
</div>
<br/>
<?php
	if($btn_acao == "Pesquisar" AND empty($msg_erro)){
		$sql = "SELECT  tbl_pedido.pedido,
						tbl_pedido.seu_pedido,
						tbl_pedido.status_pedido,
						to_char(tbl_pedido.data,'DD/MM/YYYY') AS data_digitacao,
						JSON_FIELD('categoria_pedido',tbl_pedido.valores_adicionais)     AS categoria_pedido,
						tbl_pedido.total,
						to_char(tbl_pedido.data_aprovacao,'DD/MM/YYYY') AS data_aprovacao,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto_fabrica.contato_cidade,
						tbl_posto_fabrica.contato_estado,
						tbl_posto.nome AS nome_posto,
						tbl_tipo_posto.descricao AS tipo_posto,
						tbl_admin.login,
						stad.login AS admin_status,
						tbl_status_pedido.descricao AS status_pedido_desc,
						to_char(tbl_pedido_status.data,'DD/MM/YYYY') AS data_status,
						tbl_pedido_status.observacao,
						CASE
							WHEN upper(tbl_tipo_pedido.descricao) = upper('GARANTIA') THEN
								'G'
							ELSE 'F'
						END AS tipo_pedido,
						tbl_condicao.descricao AS condicao_pagamento
						FROM tbl_pedido
						JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
						JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = $login_fabrica
						JOIN tbl_admin ON tbl_pedido.admin = tbl_admin.admin
						LEFT JOIN tbl_pedido_status ON tbl_pedido.pedido = tbl_pedido_status.pedido AND tbl_pedido_status.status in ($status_pedido)
						LEFT JOIN tbl_admin stad ON tbl_pedido_status.admin = stad.admin
						JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
						JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = $login_fabrica
						JOIN tbl_condicao ON tbl_pedido.condicao = tbl_condicao.condicao AND tbl_condicao.fabrica = $login_fabrica
						WHERE tbl_pedido.fabrica = $login_fabrica
						AND tbl_pedido.pedido_sedex IS TRUE
						$cond
						";						

		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){

			$mostra_aprovados = ($status == "aprovados" OR $status == "") ? "sim" : "";
			$mostra_recusados = ($status == "recusados" OR $status == "") ? "sim" : "";
?>
			<div class="container-fluid">
			<table id='resultados_pesquisa' class='table table-striped table-bordered table-hover table-large'>
				<thead>
					<tr class='titulo_coluna'>
						<? if($status == "aguardando"){ ?>
						<th>
							<span style='color:#FFFFFF;'>Todos</span><br/>
							<input type='checkbox' name='todos' id='todos' >
						</th>
						<? } ?>
						<th>CÓDIGO</th>
						<th>POSTO</th>
						<th>CIDADE</th>
						<th>ESTADO</th>
						<th>TIPO</th>
						<th>PEDIDO</th>
						<th>CATEGORIA</th>
						<th>VALOR</th>
						<th>CLASSIFICAÇÃO</th>
						<th>STATUS</th>
						<th>DIGITADO POR</th>
						<th>DIGITAÇÃO</th>
					<? if($mostra_aprovados){ ?>
						<th>APROVAÇÃO</th>
						<th>ADMIN APROVOU</th>
					<? } ?>
					<? if($mostra_recusados){ ?>
						<th>RECUSA</th>
						<th>ADMIN RECUSOU</th>
						<th>MOTIVO RECUSA</th>
					<? } ?>
					</tr>
				</thead>
				<tbody>
<?php
					for($i = 0; $i < pg_num_rows($res); $i++){
						$pedido 			= pg_fetch_result($res, $i, 'pedido');
						$seu_pedido 		= fnc_so_numeros(pg_fetch_result($res, $i, 'seu_pedido'));
						$data_digitacao 	= pg_fetch_result($res, $i, 'data_digitacao');
						$total 				= pg_fetch_result($res, $i, 'total');
						$data_aprovacao 	= pg_fetch_result($res, $i, 'data_aprovacao');
						$codigo_posto 		= pg_fetch_result($res, $i, 'codigo_posto');
						$categoria_pedido 		= pg_fetch_result($res, $i, 'categoria_pedido');
						$contato_cidade 	= pg_fetch_result($res, $i, 'contato_cidade');
						$contato_estado 	= pg_fetch_result($res, $i, 'contato_estado');
						$nome_posto 		= pg_fetch_result($res, $i, 'nome_posto');
						$tipo_posto 		= pg_fetch_result($res, $i, 'tipo_posto');
						$tipo_pedido 		= pg_fetch_result($res, $i, 'tipo_pedido');
						$admin_status 		= pg_fetch_result($res, $i, 'admin_status');
						$login 		 		= pg_fetch_result($res, $i, 'login');
						$data_status 		= pg_fetch_result($res, $i, 'data_status');
						$observacao 		= pg_fetch_result($res, $i, 'observacao');
						$status_pedido 		= pg_fetch_result($res, $i, 'status_pedido');
						$status_pedido_desc	= pg_fetch_result($res, $i, 'status_pedido_desc');
						$condicao_pagamento	= pg_fetch_result($res, $i, 'condicao_pagamento');

						$admin_aprovou = (!in_array($status_pedido,array(14,18))) ? $admin_status : "";
						$admin_recusou = ($status_pedido == 14) ? $admin_status : "";

						$data_aprovacao = (!in_array($status_pedido,array(14,18))) ? $data_status : "";
						$data_recusa 	= ($status_pedido == 14) ? $data_status : "";

						$observacao = ($observacao != "null") ? $observacao : "";

		                switch($categoria_pedido) {
		                    case "cortesia":
		                        $categoria_pedido_descricao = "CORTESIA";
		                        break;
		                    case "credito_bloqueado":
		                        $categoria_pedido_descricao = "CRÉDITO BLOQUEADO";
		                        break;
		                    case "erro_pedido":
		                        $categoria_pedido_descricao = "ERRO DE PEDIDO";
		                        break;
		                    case "kit":
		                        $categoria_pedido_descricao = "KIT DE REPARO";
		                        break;
		                    case "midias":
		                        $categoria_pedido_descricao = "MÍDIAS";
		                        break;
		                    case "outros":
		                        $categoria_pedido_descricao = "OUTROS";
		                        break;
		                    case "valor_minimo":
		                        $categoria_pedido_descricao = "VALOR MÍNIMO";
		                        break;
		                    case "vsg":
		                        $categoria_pedido_descricao = "VSG";
		                        break;
		                    case "divergencia":
		                    	$categoria_pedido_descricao = "DIVERGÊNCIAS LOGÍSTICA/ESTOQUE";
		                    break;
			                case "problema_distribuidor":
			                    $categoria_pedido_descricao = "PROBLEMAS COM DISTRIBUIDOR";
			                    break;
			                case "acessorios":
			                    $categoria_pedido_descricao = "ACESSÓRIOS";
			                    break;
			                case "item_similar":
		                    	$categoria_pedido_descricao = "ITEM SIMILAR";
		                    break;
		                    default:
		                        $categoria_pedido_descricao = "";
		                        break;
		                }

						$cor = ($x%2) ? "#F7F5F0": '#F1F4FA';
		?>

						<tr bgcolor='<?=$cor?>'>
							<? if($status == "aguardando"){ ?>
							<td style="text-align: center;"><input type='checkbox' name='check_<?=$i?>' id='check_<?=$i?>' value='<?=$pedido?>' class='frm'></td>
							<? } ?>
							<td><?=$codigo_posto?></td>
							<td nowrap><?=$nome_posto?></td>
							<td nowrap><?=$contato_cidade?></td>
							<td><?=$contato_estado?></td>
							<td><?=$tipo_posto?></td>
							<td><a href='pedido_admin_consulta.php?pedido=<?=$pedido?>' target='_blank'><?=$seu_pedido?></a></td>
							<td><?=$categoria_pedido_descricao?></td>
							<td><?php echo number_format($total,2,",","."); ?></td>
							<td><?=$condicao_pagamento?></td>
							<td><?=$status_pedido_desc?></td>
							<td><?=$login?></td>
							<td><?=$data_digitacao?></td>
						<? if($mostra_aprovados){ ?>
							<td><?=$data_aprovacao?></td>
							<td><?=$admin_aprovou?></td>
						<? } ?>
						<? if($mostra_recusados){ ?>
							<td><?=$data_recusa?></td>
							<td><?=$admin_recusou?></td>
							<td><?=$observacao?></td>
						<? } ?>
						</tr>
		<?php
					}
		?>
			</tbody>
			<tfoot>
			 	<? if($status == "aguardando"){ ?>
					<tr class='titulo_coluna'>
						<td colspan='13'>
							<div class="row-fluid">
								<div class="span3">
									AÇÃO:
									<select name='select_acao' class='frm'>
										<option value=""></option>
										<option value='1'>APROVAR</option>
										<option value='14'>REPROVAR</option>
									</select>
								</div>
								<div class="span4" id='col_motivo' style='display:none;'>
									MOTIVO:
									<input type='text' name='motivo' id='motivo' size='35' class='frm'>
								</div>
								<div class="span4">
									<input type='button' value='GRAVAR' name='auditar'> &nbsp; <span class="load"></span>
								</div>
							</div>
							<input type='hidden' name='pedidos_json' value=''>
						</td>
					</tr>
				<? } ?> 
			</tfoot> 
		</table>
	</div>

		<script>
			$.dataTableLoad({ table: "#resultados_pesquisa" });
		</script>
<?php
		}else{
			echo "<div class='container alert alert-warning' class='msg_erro' ><h4>Nenhum resultado encontrado</h4></div>";
		}
	}

include "rodape.php" ?>
