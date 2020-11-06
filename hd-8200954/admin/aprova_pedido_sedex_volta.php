<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
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
	$motivo  = (!empty($_GET['motivo'])) ? $_GET['motivo'] : "null";

	foreach ($pedidos as $key => $pedido) {
		$sql = "UPDATE tbl_pedido SET status_pedido = $status WHERE pedido = $pedido AND fabrica = $login_fabrica";
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
		}else{
			echo $msg_erro; exit;
		}
	}
	echo "ok";

	exit;
}
$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);

if($btn_acao == "Pesquisar"){
	$posto_codigo 	= $_POST['posto_codigo'];
	$posto_nome 	= $_POST['posto_nome'];
	$data_inicial 	= $_POST['data_inicial'];
	$data_final 	= $_POST['data_final'];
	$status 		= $_POST['status'];

	if(empty($data_final) OR empty($data_final)){
		$msg_erro = "Informe um intervalo entre datas";
	}else{
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
	}

	if(empty($msg_erro)){
		if(!empty($status)){
			switch ($status) {
				case 'aprovados':
					$cond .= " AND tbl_pedido.status_pedido not in (14,18) ";
					$status_pedido = 1;
					break;
				case 'aguardando':
					$cond .= " AND tbl_pedido.status_pedido = 18 ";
					$status_pedido = 18;
					break;
				case 'recusados':
					$cond .= " AND tbl_pedido.status_pedido = 14 ";
					$status_pedido = 14;
					break;
			}
		}
	}

	if(empty($msg_erro)){
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
	}

	if (empty($msg_erro)) {
		if(in_array($login_fabrica, array(1))) {
			$cond .= " AND tbl_pedido.finalizado IS NOT NULL ";
		}
	}
}

$layout_menu = "auditoria";
$title = "APROVAÇÃO PEDIDO SEDEX";

include "cabecalho.php";

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
.titulo_tabela{
background-color:#596d9b;
font: bold 14px "Arial";
color:#FFFFFF;
text-align:center;
}
.titulo_coluna{
background-color:#596d9b;
font: bold 11px "Arial";
color:#FFFFFF;
text-align:center;
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

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
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
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<script language="JavaScript">

$().ready(function() {

	$('#data_inicial').datePicker({startDate:'01/01/2000'});
	$('#data_final').datePicker({startDate:'01/01/2000'});
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");

	$("input[rel='data_nf']").maskedinput("99/99/9999");
	$("input[rel='data_nf_falta']").maskedinput("99/99/9999");

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[2];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

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
				complete: function(retorno){
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

function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}
</script>

<?php include "javascript_pesquisas.php"; ?>

<div id='msg' align='center' style='margin:auto;width:700px;'></div>
<?if(!empty($msg_erro)) { ?>
<div id='msg_erro' align='center' class='msg_erro' style='margin:auto;width:700px;'><?=$msg_erro?></div>
<? } ?>
<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

	<input type="hidden" name="acao">

	<table width='700' class='formulario' align='center'>
		<tr>
			<td align="center" class='titulo_tabela' colspan='5'>Parâmetros de Pesquisa</td>
		</tr>

		<tr>
			<td colspan='4' style="width: 10px">&nbsp;</td>
		</tr>

		<tr>
			<td nowrap class='espaco'>
				Código do Posto <br />
				<input type="text" name="posto_codigo" id="posto_codigo" size="9" value="<?echo $posto_codigo?>" class="frm">
				<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'codigo')" alt="Clique aqui para pesquisar os postos pelo Código" style="cursor: hand;">
			</td>
			<td nowrap>
				Razão Social do Posto <br />
				<input type="text" name="posto_nome" id="posto_nome" size="31" value="<?echo $posto_nome?>" class="frm">
				<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'nome')" alt="Clique aqui para pesquisar os postos pela Razão Social" style="cursor: hand;">
			</td>
		</tr>
		<tr>
			<td class='espaco'>
				Data Inicial *<br />
				<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm" />
			</td>
			<td colspan='3'>
				Data Final *<br />
				<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm" />
			</td>
		</tr>
		<tr>
			<td class='espaco' valign='bottom' colspan='2'>
				<fieldset style='width:500px;'>
					<legend>Status do Pedido</legend>
					<table width="100%">
						<tr>
							<td>
								<INPUT TYPE="radio" NAME="status" value='aguardando' checked='checked' >Aguardando Aprovação
								<INPUT TYPE="radio" NAME="status" value='aprovados'   <?php if(trim($status) == 'aprovados') echo "checked='checked'"; ?> >Pedidos Aprovados
								<INPUT TYPE="radio" NAME="status" value='recusados'  <?php if(trim($status) == 'recusados')  echo "checked='checked'"; ?> >Pedidos Recusados
							</td>
						</tr>
					</table>
				</fieldset>
			</td>
		</tr>
		<tr>
			<td class='espaco' valign='bottom' colspan='2'>
				<fieldset style='width:200px;'>
					<legend>Tipo do Pedido</legend>
					<INPUT TYPE="radio" NAME="tipo" value=''      	   <?php if(trim($tipo) == '')      	 echo "checked='checked'"; ?> >Todos
					<INPUT TYPE="radio" NAME="tipo" value='faturado'   <?php if(trim($tipo) == 'faturado')   echo "checked='checked'"; ?> >Faturados
					<INPUT TYPE="radio" NAME="tipo" value='garantia'   <?php if(trim($tipo) == 'garantia')   echo "checked='checked'"; ?> >Garantia
				</fieldset>
			</td>
		</tr>
		<tr   align='center'>
			<td colspan='4' align='center'><input type="submit" name="btn_acao" value="Pesquisar">
			</td>
		</tr>
	</table>
</form>
<br/>
<?php
	if($btn_acao == "Pesquisar" AND empty($msg_erro)){
		$sql = "SELECT  tbl_pedido.pedido,
						tbl_pedido.seu_pedido,
						tbl_pedido.status_pedido,
						to_char(tbl_pedido.data,'DD/MM/YYYY') AS data_digitacao,
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
						JOIN tbl_pedido_status ON tbl_pedido.pedido = tbl_pedido_status.pedido AND tbl_pedido_status.status = $status_pedido
						JOIN tbl_admin stad ON tbl_pedido_status.admin = stad.admin
						JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
						JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = $login_fabrica
						JOIN tbl_condicao ON tbl_pedido.condicao = tbl_condicao.condicao AND tbl_condicao.fabrica = $login_fabrica
						WHERE tbl_pedido.fabrica = $login_fabrica
						AND tbl_pedido.pedido_sedex IS TRUE
						$cond
						";

						//echo $sql;

		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){

			$mostra_aprovados = ($status == "aprovados" OR $status == "") ? "sim" : "";
			$mostra_recusados = ($status == "recusados" OR $status == "") ? "sim" : "";
?>
			<table align='center' class='tabela' cellpadding='0' >
				<tr class='titulo_coluna'>
					<? if($status == "aguardando"){ ?>
					<th>
						<span style='color:#FFFFFF;'>Todos</span><br/>
						<input type='checkbox' name='todos' id='todos' class='frm'>
					</th>
					<? } ?>
					<th>CÓDIGO</th>
					<th>POSTO</th>
					<th>CIDADE</th>
					<th>ESTADO</th>
					<th>TIPO</th>
					<th>PEDIDO</th>
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
<?php
			for($i = 0; $i < pg_num_rows($res); $i++){
				$pedido 			= pg_fetch_result($res, $i, 'pedido');
				$seu_pedido 		= fnc_so_numeros(pg_fetch_result($res, $i, 'seu_pedido'));
				$data_digitacao 	= pg_fetch_result($res, $i, 'data_digitacao');
				$total 				= pg_fetch_result($res, $i, 'total');
				$data_aprovacao 	= pg_fetch_result($res, $i, 'data_aprovacao');
				$codigo_posto 		= pg_fetch_result($res, $i, 'codigo_posto');
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

				$cor = ($x%2) ? "#F7F5F0": '#F1F4FA';
?>

				<tr bgcolor='<?=$cor?>'>
					<? if($status == "aguardando"){ ?>
					<td><input type='checkbox' name='check_<?=$i?>' id='check_<?=$i?>' value='<?=$pedido?>' class='frm'></td>
					<? } ?>
					<td><?=$codigo_posto?></td>
					<td nowrap><?=$nome_posto?></td>
					<td nowrap><?=$contato_cidade?></td>
					<td><?=$contato_estado?></td>
					<td><?=$tipo_posto?></td>
					<td><a href='pedido_admin_consulta.php?pedido=<?=$pedido?>' target='_blank'><?=$seu_pedido?></a></td>
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
			<? if($status == "aguardando"){ ?>
			<tr class='titulo_coluna'>
				<td colspan='100%'>
					<table>
						<tr>
							<td>
								AÇÃO:
								<select name='select_acao' class='frm'>
									<option value=""></option>
									<option value='1'>APROVAR</option>
									<option value='14'>REPROVAR</option>
								</select>
							</td>
							<td id='col_motivo' style='display:none;'>
								MOTIVO:
								<input type='text' name='motivo' id='motivo' size='35' class='frm'>
							</td>
							<td>
								<input type='button' value='GRAVAR' name='auditar'>
							</td>
						</tr>
					</table>
					<input type='hidden' name='pedidos_json' value=''>
				</td>
			</tr>
			<? } ?>
		</table>
<?php
		}else{
			echo "<center>Nenhum resultado encontrado</center>";
		}
	}

include "rodape.php" ?>
