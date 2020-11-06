<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "call_center";
include 'autentica_admin.php';

if(!empty($_POST['ajax'])){
	if($_POST['ajax'] == 'familiaLinha'){

		$referencia = trim($_POST['referencia']);
		$sql = "
			SELECT
				tbl_produto.linha,
				tbl_produto.familia
			FROM tbl_produto
				JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE tbl_produto.referencia = '{$referencia}'
				AND tbl_linha.fabrica = {$login_fabrica}
			LIMIT 2;";

		$res = pg_query($con,$sql);
		if($res){
			if (pg_num_rows($res) == 1){
				exit(pg_result($res,0,'linha')."|".pg_result($res,0,'familia'));
			}
		}

		exit();
	}
}

if ($telecontrol_distrib) {
	if ($login_fabrica == 122 ){
		$array_origem_reclamacao = array(
		"0800"         		=> "0800",
		"reclame_aqui"		=> "Reclame Aqui",
		"facebook"     		=> "Facebook",
		"twitter"      		=> "Twitter",
		"procon"       		=> "Procon",
		"fora_garantia"     => traduz("Produto fora de Garantia"),
		"outros"      		=> traduz("Outros")
		);
	}else{
		$array_origem_reclamacao = array(
		"0800"         		=> "0800",
		"reclame_aqui"		=> "Reclame Aqui",
		"facebook"     		=> "Facebook",
		"twitter"      		=> "Twitter",
		"procon"       		=> "Procon",
		"outros"      		=> traduz("Outros")
		);
	}

}

unset($msg_erro);
$msg_erro = array();

$btn_acao = $_REQUEST['btn_acao'];

if ($btn_acao == 'pesquisar'){
	echo $os 	= $_POST["os"];

	$data_inicial 	= $_REQUEST["data_inicial"];
    $data_final 	= $_REQUEST["data_final"];

    if(empty($data_inicial) AND empty($os)){
        $msg_erro[] = traduz("Informe a Data Inicial");
    }

	if (empty($data_final) AND empty($os)){
		$msg_erro[] = traduz("Informe a Data Final");
	}

    if(count($msg_erro)==0  AND empty($os)){

        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi))
            $msg_erro[] = traduz("Data Inicial Inválida");

        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf))
            $msg_erro[] = traduz("Data Final Inválida");

		$aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";

        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)
        or strtotime($aux_data_final) > strtotime('today')){
            $msg_erro[] = traduz("Data Inválida, Data Inicial maior que Data Final");
        }

        $sql_data = " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";

    }

    if ($_POST['tipo_venda']) {
    	$tipo_venda = $_POST['tipo_venda'];
    	$sql_tipo_venda = " AND tbl_hd_chamado_extra.tipo_venda = '{$tipo_venda}' ";
    }

	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_descricao  = trim($_POST['produto_descricao']);
	if (!empty($produto_referencia)){
		$sql = "SELECT produto from tbl_produto where referencia = '$produto_referencia' and descricao = '$produto_descricao'";
		$res = pg_query($con,$sql);
		$produto = pg_result($res,0,0);
		if (!$msg_erro and !empty($produto)){
			$sql_produto = " AND tbl_produto.produto = $produto ";
		}
	}

	$posto_referencia = trim($_POST['posto_referencia']);
	$posto_descricao  = trim($_POST['posto_descricao']);
	if (!empty($posto_referencia)){
		$sql = "SELECT tbl_posto.posto
				from tbl_posto
				JOIN tbl_posto_fabrica on (tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica)
				where (codigo_posto = '$posto_referencia' or nome = '$posto_descricao')";
		$res = pg_query($con,$sql);
		$posto = pg_result($res,0,'posto');
		if (!$msg_erro and !empty($posto)){
			$sql_posto = " AND tbl_posto.posto = $posto";
		}
	}

	if ($_POST['defeito_reclamado']) {
		$defeito_reclamado = $_POST['defeito_reclamado'];
		$sql_defeito_reclamado = " AND tbl_hd_chamado_extra.defeito_reclamado = $defeito_reclamado ";
		if ($login_fabrica == 186) {
			$sql_defeito_reclamado = " AND tbl_hd_chamado_item.defeito_reclamado = $defeito_reclamado ";
		}
	}

	if ($_POST['atendimento']) {
		$atendimento = $_POST['atendimento'];
		$sql_atendimento = " AND tbl_hd_chamado.hd_chamado = $atendimento ";
	}

	if ($_POST['atendente']) {
		$atendente = $_POST['atendente'];
		$sql_atendente = " AND tbl_hd_chamado.atendente = $atendente ";
	}

	if ($_POST['status']) {
		$status = $_POST['status'];
		if($status == 0)
			$sql_status = " AND tbl_hd_chamado.status = '$status' ";
	}

	if ($_POST['linha']) {
		$linha = (int) $_POST['linha'];
		if($linha > 0)
			$sql_linha = " AND tbl_linha.linha = '$linha' ";
	}

	if ($_POST['familia']) {
		$familia = (int) $_POST['familia'];
		if($familia > 0)
			$sql_familia = " AND tbl_familia.familia = '$familia' ";
	}

	if ($_POST['os']) {
		$os = $_POST['os'];
		if($os > 0)
			$sql_os = " AND (tbl_hd_chamado_extra.os = {$os} OR tbl_hd_chamado_extra.sua_os = '{$os}')";
	}

	if (!empty($_POST['origem'])) {
		$origem = implode("','", $_POST['origem']);

		$cond_origem = " AND tbl_hd_chamado_extra.hd_chamado_origem IN ('$origem') ";
	}

	if (!empty($_POST['hd_classificacao'])) {
		$hd_classificacao    = implode("','",$_POST['hd_classificacao']);

		$cond_classificacao  = " AND tbl_hd_chamado.hd_classificacao IN ('$hd_classificacao')";
	}

	if ($_POST["origem_reclamacao"] && $telecontrol_distrib) {
		$origem_reclamacao = $_POST["origem_reclamacao"];

		if (strlen($origem_reclamacao) > 0) {
			$sql_origem_reclamacao = " AND tbl_hd_chamado_extra.array_campos_adicionais ~* 'origem_reclamacao.+{$origem_reclamacao}' ";
		}
	}

	if (in_array($login_fabrica, [174])) {
		$ref_atendimento = ", tbl_hd_chamado_extra.tipo_venda ";
	}

	$join_item = "";
	$join_prod = " JOIN tbl_produto on (tbl_hd_chamado_extra.produto = tbl_produto.produto) ";
	$join_def  = " LEFT JOIN tbl_defeito_reclamado on (tbl_hd_chamado_extra.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado and tbl_defeito_reclamado.fabrica = $login_fabrica) ";

	if ($login_fabrica == 186) {
		$distinct  = " DISTINCT ";
		$join_item = " JOIN tbl_hd_chamado_item on (tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado) ";
		$join_prod = " JOIN tbl_produto on (tbl_hd_chamado_item.produto = tbl_produto.produto) ";
		$join_def  = " LEFT JOIN tbl_defeito_reclamado on (tbl_hd_chamado_item.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado and tbl_defeito_reclamado.fabrica = $login_fabrica) ";
	}

	if(empty($msg_erro)){
			$sql_pesquisa = " 
				SELECT  $distinct tbl_hd_chamado.hd_chamado,
						TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY') as data_abertura,
						CASE
							WHEN tbl_hd_chamado.resolvido notnull THEN
								TO_CHAR( tbl_hd_chamado.resolvido,'DD/MM/YYYY')
							ELSE
								(select TO_CHAR( h.data,'DD/MM/YYYY') from tbl_hd_chamado_item h where h.hd_chamado = tbl_hd_chamado.hd_chamado and status_item ='Resolvido' order by data desc limit 1)
						END AS data_resolvido,
						tbl_posto.nome AS nome_posto,
						tbl_admin.nome_completo,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_defeito_reclamado.descricao,
						tbl_linha.nome AS nome_linha,
						tbl_familia.descricao AS descricao_familia,
						tbl_cidade.nome AS cidade_consumidor,
						tbl_cidade.estado AS estado_consumidor,
						tbl_hd_chamado_extra.os,
						tbl_hd_chamado_extra.sua_os,
						tbl_hd_chamado_extra.array_campos_adicionais,
						tbl_hd_motivo_ligacao.descricao AS hd_motivo_ligacao,
						tbl_hd_chamado_origem.descricao as descricao_origem,
						tbl_hd_classificacao.descricao AS descricao_classificacao
						$ref_atendimento
				FROM 	tbl_hd_chamado
					JOIN 		tbl_hd_chamado_extra 	on (tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado)
					$join_item
					LEFT JOIN 	tbl_posto 	 			on (tbl_hd_chamado_extra.posto = tbl_posto.posto)
					JOIN 		tbl_admin 			 	on (tbl_hd_chamado.atendente = tbl_admin.admin)
					$join_prod
					$join_def
					JOIN 		tbl_linha 				on (tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = {$login_fabrica})
					JOIN 		tbl_familia 			on (tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica = {$login_fabrica})
					LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
					LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao
					LEFT JOIN tbl_hd_classificacao ON tbl_hd_chamado.hd_classificacao = tbl_hd_classificacao.hd_classificacao AND tbl_hd_chamado.fabrica = $login_fabrica
					LEFT JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_extra.hd_chamado_origem = tbl_hd_chamado_origem.hd_chamado_origem AND tbl_hd_chamado_origem.fabrica = $login_fabrica
				WHERE tbl_hd_chamado.fabrica = $login_fabrica
					$sql_data
					$sql_produto
					$sql_atendente
					$sql_defeito_reclamado
					$sql_atendimento
					$sql_status
					$sql_familia
					$sql_linha
					$sql_os
					$sql_posto
					$sql_origem_reclamacao
					$sql_tipo_venda
					$cond_origem
					$cond_classificacao
				";
		//echo nl2br($sql_pesquisa);  exit();
		$res_pesquisa = pg_query($con,$sql_pesquisa);

		if(pg_num_rows($res_pesquisa) == 0){
			$msg_erro[] = traduz("Nenhum resultado encontrado");
		}
	}


}
$layout_menu = "callcenter";
$title = traduz("Relatório de Produtos por Defeito Reclamado");
include "cabecalho.php";

?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="ISO-8859-1">
<link rel="stylesheet" type="text/css" href="js/blue/style.css" media="all">
<style type="text/css">
.titulo_tabela{
	background-color:#596d9b !important;
	font: bold 14px 'Arial' !important;
	color:#FFFFFF;
	text-align:center;
	padding: 5px;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px 'Arial';
	color:#FFFFFF;
	text-align:center;
}

.titulo_coluna thead th, .header{
	cursor: pointer;
	background-color:#596d9b !important;
	padding-right: 20px !important;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px 'Arial';
	color:#FFFFFF;
	text-align:center;
	margin: 0 auto;
}
.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{
	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela{
	margin: 0 auto;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.sucesso{
	background-color: green;
	font: bold 16px 'Arial';
	color: #FFFFFF;
	text-align:center;
	margin: 0 auto;
}
.frm {
	background-color:#F0F0F0;
	border:1px solid #888888;
	font-family:Verdana;
	font-size:8pt;
	font-weight:bold;
}

</style>

<style type="text/css">
	@import "plugins/jquery/datepick/telecontrol.datepick.css";
</style>

<script src="js/jquery-ui-1.8.23.custom/js/jquery-1.8.0.min.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js"></script>
<link rel="stylesheet" href="css/multiple-select.css" />
<script src="js/jquery.multiple.select.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script>

<style type="text/css">

.ms-parent {
	height:20px !important;
}

.ms-choice {
	height:20px !important;
}

</style>

<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript">
	 $(function(){

		$( "#data_inicial" ).datepick({startDate : "01/01/2000"});
		$( "#data_inicial" ).maskedinput("99/99/9999");

		$( "#data_final" ).datepick({startDate : "01/01/2000"});
		$( "#data_final" ).maskedinput("99/99/9999");
		Shadowbox.init();
		fnVerficaFamiliaLinha();

		<?php
			if ($login_fabrica == 174) { ?>
				$("#origem").multipleSelect();
				$("#hd_classificacao").multipleSelect();
		<?php
			}
		?>
	});

$(document).ready(function(){


	$("#relatorio").tablesorter();

});

	function gravaDados(name, valor){
		try{
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

    function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria, posicao){

            gravaDados('produto_referencia',referencia);
            gravaDados('produto_descricao',descricao);

            fn_familiaLinha(referencia);
    }

	function pesquisaProduto(produto,tipo){

		if (jQuery.trim(produto.value).length > 2){
			Shadowbox.open({
				content:    "produto_pesquisa_2_nv.php?"+tipo+"="+produto.value,
				player: "iframe",
				title:      '<?=traduz("Produto")?>',
				width:  800,
				height: 500
			});
		}else{
			alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
			produto.focus();
		}

	}

	function pesquisaPosto(campo,tipo){
		var campo = campo.value;

		if (jQuery.trim(campo).length > 2){
			Shadowbox.open({
				content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
				player:	"iframe",
				title:		'<?=traduz("Pesquisa Posto")?>',
				width:	800,
				height:	500
			});
		}else
			alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
	}

	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciado,credenciamento){
		gravaDados('posto_referencia',codigo_posto);
		gravaDados('posto_descricao',nome);
	}

	function fn_familiaLinha(referencia){
		try{
			$.ajax({
			  	url: '<?php echo $_SERVER['PHP_SELF']?>',
			  	type: "POST",
			  	data: {'referencia':referencia,'ajax':'familiaLinha'},
			  	cache: true,
			  	success: function(data) {
			    	data = data.split('|');

			    	$('#familia').val(data[1]);
			    	$('#linha').val(data[0]);
			  	}
			});
		} catch(err){
			return false;
		}
	}

	function fnVerficaFamiliaLinha(){
		$("#produto_referencia, #produto_descricao").bind('change ', function() {
			$('#familia').val('');
		    $('#linha').val('');

		    if($("#produto_referencia").val().length > 2)
		    	fn_familiaLinha($("#produto_referencia").val());
		});

		$("#familia, #linha").bind('change ', function() {
  			var valor = $(this).val();

  			if($('#produto_descricao').val().length > 0 || $('#produto_referencia').val().length > 0){
				$('#familia').val('');
			    $('#linha').val('');

			    $('#produto_descricao').val('');
		    	$('#produto_referencia').val('');
			}

		    $(this).val(valor);
		});
	}

</script>
</head>
<body>
	<?
	if ($msg_erro){
		$msg_erro = implode('<br>', array_filter($msg_erro));
	?>
		<table class="msg_erro" width="700px" align="center">
			<tr>
				<td><?=$msg_erro?></td>
			</tr>
		</table>
	<?
	}
	?>
	<form action="<?=$PHP_SELF?>" method="post" name="frm_pesquisa">
		<table class="formulario" width="700px" align="center" cellpadding="4" cellspacing="1" border='0'>
			<caption class="titulo_tabela"><?=$title?></caption>
			<tr>
				<td width='75px'>&nbsp;</td>
				<td width='162px'>&nbsp;</td>
				<td width='162px'>&nbsp;</td>
				<td width='162px'>&nbsp;</td>
				<td width='162px'>&nbsp;</td>
				<td width='75px'>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td align="left">
					<?=traduz('Data Inicial')?><br />
					<input type="text" name="data_inicial" id="data_inicial" class="frm" value="<?=$data_inicial?>" style='width: 95px;' />
				</td>
				<td align="left">
					<?=traduz('Data Final')?>
					<input type="text" name="data_final" id="data_final" class="frm" value="<?=$data_final?>" style='width: 95px;' />
				</td>
				<td align="left">
					<?=traduz('Nº Atendimento')?><br />
					<input type="text" name="atendimento" id="atendimento" class="frm" value="<?=$atendimento?>" style='width: 112px;'   />
				</td>
				<?php if($telecontrol_distrib){?>
					<td align="left">
						OS<br />
						<input type="text" name="os" id="os" class="frm" value="<?php echo $os?>" style='width: 112px;'   />
					</td>
				<?php }?>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td align="left" colspan='2'>
					<?=traduz('Posto Referência')?><br />
					<input type="text" name="posto_referencia" id="posto_referencia" class="frm" style="width:85%" value="<?=$posto_referencia?>" />
					<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="pesquisaPosto(document.frm_pesquisa.posto_referencia,'codigo')" style='cursor: pointer' />
				</td>
				<td align="left" colspan='2'>
					<?=traduz('Posto Descrição')?><br />
					<input type="text" name="posto_descricao" id="posto_descricao" class="frm" style="width:85%" value="<?=$posto_descricao?>" />
					<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="pesquisaPosto(document.frm_pesquisa.posto_descricao,'nome')" style='cursor: pointer' />
				</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td align="left" colspan='2'>
					<?=traduz('Produto Referência')?><br />
					<input type="text" name="produto_referencia" id="produto_referencia" class="frm" style="width:85%" value="<?=$produto_referencia?>" />
					<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="pesquisaProduto(document.frm_pesquisa.produto_referencia,'referencia')" style='cursor: pointer' />
				</td>
				<td align="left" colspan='2'>
					<?=traduz('Produto Descrição')?> <br />
					<input type="text" name="produto_descricao" id="produto_descricao" class="frm" style="width:85%" value="<?=$produto_descricao?>" />
					<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="pesquisaProduto(document.frm_pesquisa.produto_descricao,'descricao')" style='cursor: pointer' />
				</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td align="left" colspan='2'>
					<?=traduz('Defeito Reclamado')?><br />
					<select name="defeito_reclamado" id="defeito_reclamado" class="frm" style="width:90%">
						<option value=""></option>

						<?
						$sql = "SELECT defeito_reclamado,descricao
								FROM tbl_defeito_reclamado
								WHERE fabrica = $login_fabrica
								AND ativo IS TRUE";

						$res = pg_query($con,$sql);

						if (pg_num_rows($res)>0){
							for ($i=0;$i<pg_num_rows($res);$i++){
								$id_def_reclamado        = pg_result($res,$i,0);
								$descricao_def_reclamado = pg_result($res,$i,1);

								$selected = ($defeito_reclamado == $id_def_reclamado) ? "SELECTED" : null;
								?>
								<option value="<?=$id_def_reclamado?>" <?php echo $selected;?>><?=$descricao_def_reclamado?></option>
								<?
							}
						}

						?>
					</select>
				</td>
				<td align="left">
					<?=traduz('Atendente')?><br />
					<select name="atendente" id="atendente" class="frm" style="width: 85%">
						<option value=""></option>
						<?
						$sql = "
							SELECT tbl_admin.admin,
								   tbl_admin.nome_completo

							FROM tbl_admin where ativo is true and
							fabrica = $login_fabrica
						";

						$res = pg_query($con,$sql);

						if (pg_num_rows($res)>0){

							for ($i=0;$i<pg_num_rows($res);$i++){
								$id_admin   = pg_result($res,$i,0);
								$nome_admin = pg_result($res,$i,1);

								$selected = ($atendente == $id_admin) ? "SELECTED" : null;
								?>
								<option value="<?=$id_admin?>" <?php echo $selected; ?>><?=$nome_admin?></option>
								<?
							}
						}
						?>
					</select>
				</td>
				<td align="left" >
					<?php
						if($telecontrol_distrib){
							$status = !empty($_POST['status']) ? $_POST['status'] : '0';

							$_status = array(
								'0' => 'Todos',
								'Aberto' => 'Aberto',
								'Resolvido' => 'Fechado'
							);

							echo traduz("Status <br />");
							echo "<select name='status' id='status' class='frm' style='width: 85%;'>";
								foreach ($_status AS $key => $value) {
									$selected = ($key == $status) ? " selected = 'selected' " : '';

									echo "<option value='{$key}' label='{$value}' {$selected}>{$value}</option>";

								}
							echo "</select>";
						}
					?>
					&nbsp;
				</td>
				<td>&nbsp;</td>
			</tr>
			<?php if (in_array($login_fabrica, [174])) { ?>
				<tr>
					<td>&nbsp;</td>
					<td align="left" colspan="2">
						<?=traduz('Referência do Atendimento')?>
						<select name="tipo_venda" id="tipo_venda" class="frm" style="width:90%;height:20px;">
							<option value=""></option>
							<option value="POS"><?=traduz('Pós-Venda')?></option>
							<option value="PRE"><?=traduz('Pré-Venda')?></option>
						</select>
					</td>
				</tr>
			<?php } ?>
			<?php if($telecontrol_distrib){?>
				<tr>
					<td>&nbsp;</td>

					<td align="left" colspan='2'>
						<?=traduz('Linha')?><br />
						<select name="linha" id="linha" class="frm" style="width:90%">
							<option value="" selected=''></option>
							<?
							$sql = "
								SELECT
									linha,
									nome
								FROM tbl_linha
								WHERE
									fabrica = $login_fabrica
								ORDER BY nome ASC;";

							$res = pg_query($con,$sql);

							if (pg_num_rows($res)>0){
								$linha = $_POST['linha'];

								for ($i=0;$i<pg_num_rows($res);$i++){
									$_nome   = pg_result($res,$i,'nome');
									$_linha = pg_result($res,$i,'linha');

									$selected = ($linha == $_linha) ? " selected = 'selected' " : '';

									echo "<option value='{$_linha}' {$selected}>{$_nome}</option>";

								}
							}
							?>
						</select>
					</td>
					<td align="left" colspan='2'>
						<?=traduz('Família')?><br />
						<select name="familia" id="familia" class="frm" style="width:93%">
							<option value="" selected=''></option>
							<?
							$sql = "
								SELECT
									familia,
									descricao
								FROM tbl_familia
								WHERE
									fabrica = $login_fabrica
								ORDER BY descricao ASC;";

							$res = pg_query($con,$sql);

							if (pg_num_rows($res)>0){
								$familia = $_POST['familia'];

								for ($i=0;$i<pg_num_rows($res);$i++){
									$_descricao   = pg_result($res,$i,'descricao');
									$_familia = pg_result($res,$i,'familia');

									$selected = ($familia == $_familia) ? " selected = 'selected' " : '';

									echo "<option value='{$_familia}' {$selected}>{$_descricao}</option>";

								}
							}
							?>
						</select>
					</td>
					<td>&nbsp;</td>
				</tr>
			<?php
			}

			if ($telecontrol_distrib) {
			?>
				<tr>
					<td>&nbsp;</td>
					<td colspan="4" style="text-align: left;" >
						<?=traduz('Origem da Reclamação')?><br />
						<input type="radio" name="origem_reclamacao" value="0800" <?=($origem_reclamacao == "0800") ? "CHECKED" : ""?> />0800
						<input type="radio" name="origem_reclamacao" value="reclame_aqui" <?=($origem_reclamacao == "reclame_aqui") ? "CHECKED" : ""?> />Reclame Aqui
						<input type="radio" name="origem_reclamacao" value="facebook" <?=($origem_reclamacao == "facebook") ? "CHECKED" : ""?> />Facebook
						<input type="radio" name="origem_reclamacao" value="twitter" <?=($origem_reclamacao == "twitter") ? "CHECKED" : ""?> />Twitter
						<input type="radio" name="origem_reclamacao" value="procon" <?=($origem_reclamacao == "procon") ? "CHECKED" : ""?> />Procon
					<?php if ($login_fabrica == 122 ) { ?>
							<input type="radio" name="origem_reclamacao" value="fora_garantia" <?=($origem_reclamacao == "fora_garantia") ? "CHECKED" : ""?> /><?=traduz('Produto fora de Garantia')?>
					<?php } ?>

						<input type="radio" name="origem_reclamacao" value="outro" <?=($origem_reclamacao == "outro") ? "CHECKED" : ""?> /><?=traduz('Outro')?>
					</td>
					<td>&nbsp;</td>
				</tr>
			<?php
			}

			if ($login_fabrica == 174) {

	            $sqlOrigem = "
	                SELECT hd_chamado_origem, descricao, valida_obrigatorio
	                FROM tbl_hd_chamado_origem
	                WHERE fabrica = $login_fabrica
	                AND ativo IS TRUE
	            ";

	            $resOrigem = pg_query($con, $sqlOrigem);

	    		?>
	    		<tr>
	    			<td>&nbsp;</td>
	    			<td align='left'>
	    				<?=traduz('Origem')?><br />
						<select name="origem[]" id="origem" class="frm" multiple='multiple' style="width:93%;height:20px;">
							<option value="" ><?=traduz('Escolha')?></option>
							<?php		

							if (pg_num_rows($resOrigem)) {
								while ($row = pg_fetch_object($resOrigem)) {
									$selected = (in_array($row->hd_chamado_origem, $_POST['origem'])) ? "selected" : "";
									echo "<option value='{$row->hd_chamado_origem}' {$selected} >{$row->descricao}</option>";
								}
							}                
							?>
						</select>
					</td>
					<td>&nbsp;</td>
					<td align='left'>
						<?=traduz('Classificação do atendimento:')?><br />
						<select name='hd_classificacao[]'  id='hd_classificacao' multiple='multiple' class="frm" style="width:93%">
							<option value=''><?=traduz('Escolha')?></option><?php

							$sqlClassificacao = "SELECT hd_classificacao, descricao 
												FROM tbl_hd_classificacao 
												WHERE fabrica = $login_fabrica 
												AND ativo IS TRUE ORDER BY descricao";
							$resClassificacao = pg_query($con,$sqlClassificacao);

							for ($i = 0; $i < pg_num_rows($resClassificacao); $i++) {

								$hd_classificacao_aux = pg_fetch_result($resClassificacao,$i,'hd_classificacao');
								$classificacao        = pg_fetch_result($resClassificacao,$i,'descricao');
								$selected = (in_array($hd_classificacao_aux, $_POST['hd_classificacao'])) ? "selected" : "";
								
								echo " <option value='".$hd_classificacao_aux."' $selected >$classificacao</option>";

							} ?>

						</select>
					</td>
				</tr>
			<?php
			} ?>
			<tr>
				<td align="center" colspan='6' style='padding: 20px; text-align: center'>
					<input type="hidden" name="btn_acao" value="" />
					<input type="button" value="Pesquisar" style="cursor:pointer" onclick="if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='pesquisar' ; document.frm_pesquisa.submit() } else { alert ('Aguarde ') }"  />
				</td>
			</tr>
		</table>
<?
if (!$msg_erro and $btn_acao == "pesquisar"){

	if (pg_num_rows($res_pesquisa)>0){
		$xls = "";

		$xls .="<table id='relatorio' class='tablesorter tabela' align='center'  cellspacing='1' cellpadding='2' style='margin: 20px 0; font-size: 12px;'>";
			$xls .= "<thead>";
				$xls .="<tr>";

					if ($telecontrol_distrib) {
						$colspan = 14;
					} else if ($login_fabrica == 174) {
						$colspan = 11;
					} else {
						$colspan = 10;
					}

					$xls .="<td class='titulo_tabela' colspan='100%' bgcolor='#99A9CC' align='center'><b>".traduz('Resultado da pesquisa')."</b></td>";
				$xls .="</tr>";
				$xls .="<tr class='titulo_coluna'>";
					$xls .="<th bgcolor='#99A9CC'>".traduz('Atendimento')."</th>";
					if($login_fabrica == 50){
						$xls .="<th bgcolor='#99A9CC'>".traduz('Tipo de Atendimento')."</th> ";
					}
					if (in_array($login_fabrica, [174])) {
						$xls .="<th bgcolor='#99A9CC'>".traduz('Referência do Atendimento')."</th>";
					}
					$xls .="<th bgcolor='#99A9CC'>".traduz('Qtde Interação')."</th>";
					$xls .="<th bgcolor='#99A9CC'>".traduz('Abertura')."</th>";

					if($telecontrol_distrib)
						$xls .="<th bgcolor='#99A9CC'>".traduz('Fechamento')."</th>";
					else
						$xls .="<th bgcolor='#99A9CC'>".traduz('Resolvido')."</th>";

					$xls .="<th bgcolor='#99A9CC'>".traduz('Posto Indicado')."</th>";

					if($telecontrol_distrib)
						$xls .="<th bgcolor='#99A9CC'>OS</th>";

					$xls .="<th bgcolor='#99A9CC'>".traduz('Referência')."</th>";
					$xls .="<th bgcolor='#99A9CC'>".traduz('Descrição')."</th>";

					if($telecontrol_distrib){
						$xls .="<th bgcolor='#99A9CC'>".traduz('Linha')."</th>";
						$xls .="<th bgcolor='#99A9CC'>".traduz('Família')."</th>";
					}

					$xls .="<th bgcolor='#99A9CC'>".traduz('Def. Reclamado')."</th>";

					if($telecontrol_distrib){
						$xls .="<th bgcolor='#99A9CC'>".traduz('Origem da Reclamação')."</th>";
					}

					if (in_array($login_fabrica, [174])) {
						$xls .="<th bgcolor='#99A9CC'>".traduz('Classificação')."</th>";
						$xls .="<th bgcolor='#99A9CC'>".traduz('Origem')."</th>";						
					}

					$xls .="<th bgcolor='#99A9CC'>".traduz('Atendente')."</th>";

					if($telecontrol_distrib){
						$xls .="<th bgcolor='#99A9CC'>".traduz('Estado')."</th>";
					}
				$xls .="</tr>";
			$xls .= "</thead>";

			$xls .= "<tbody>";
				$registros = pg_num_rows($res_pesquisa);
				for ($i=0;$i < $registros;$i++){
					$chamado 			= pg_result($res_pesquisa,$i,0);
					$data_abertura 		= pg_result($res_pesquisa,$i,1);
					$data_resolvido 	= pg_result($res_pesquisa,$i,2);
					$nome_posto			= pg_result($res_pesquisa,$i,'nome_posto');
					$nome_completo 		= pg_result($res_pesquisa,$i,4);
					$referencia 		= pg_result($res_pesquisa,$i,5);
					$descricao 			= pg_result($res_pesquisa,$i,6);
					$defeito_reclamado  = pg_result($res_pesquisa,$i,7);
					$os  				= pg_result($res_pesquisa,$i,'os');
					$linha  			= pg_result($res_pesquisa,$i,'nome_linha');
					$familia  			= pg_result($res_pesquisa,$i,'descricao_familia');
					$cidade_consumidor		= pg_result($res_pesquisa,$i,'cidade_consumidor');
					$estado_consumidor              = pg_result($res_pesquisa,$i,'estado_consumidor');
					$hd_motivo_ligacao = pg_fetch_result($res_pesquisa, $i, 'hd_motivo_ligacao');
					$descricao_classificacao = pg_fetch_result($res_pesquisa, $i, 'descricao_classificacao');
					$descricao_origem = pg_fetch_result($res_pesquisa, $i, 'descricao_origem');					

					if (in_array($login_fabrica, [174])) {
						$tipo_venda = pg_fetch_result($res_pesquisa, $i, 'tipo_venda');
						$tipo_venda == 'POS' ? $tipo_venda = 'Pós-Venda' : ' ';
						$tipo_venda == 'PRE' ? $tipo_venda = 'Pré-Venda' : ' ';
					}

					if($telecontrol_distrib) {

						$array_campos_adicionais = pg_fetch_result($res_pesquisa, $i, "array_campos_adicionais");
						$array_campos_adicionais = json_decode($array_campos_adicionais, true);

						if (count($array_campos_adicionais["origem_reclamacao"]) > 0) {

							foreach ($array_campos_adicionais["origem_reclamacao"] as $key => $value) {

								$trocar[] = $array_origem_reclamacao[$value];
							}


							$origem_reclamacao = implode(" , ", $trocar);
							unset($trocar);
						} else {

							$origem_reclamacao = "";

						}

					}
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					$sqlc = "SELECT count(1) FROM tbl_hd_chamado_item WHERE hd_chamado = $chamado";
					$resc = pg_query($con,$sqlc);
					if(pg_num_rows($resc) > 0) $qtde_item = pg_fetch_result($resc,0,0);

					$xls .="<tr>";
						$xls .="<td bgcolor='{$cor}'>";
							$xls .="<a href='callcenter_interativo_new.php?callcenter={$chamado}' target='_blank'>";
								$xls .="{$chamado}";
							$xls .="</a>&nbsp;";
						$xls .="</td>";
						if($login_fabrica == 50){
							$xls .="<td bgcolor='{$cor}' style='text-align:center'>{$hd_motivo_ligacao}</td>";
						}
						if (in_array($login_fabrica, [174])) {
							$xls .="<td bgcolor='{$cor}' style='text-align:center'>{$tipo_venda}</td>";
						}
						$xls .="<td bgcolor='{$cor}' style='text-align:center'>{$qtde_item}</td>";
						$xls .="<td bgcolor='{$cor}'>{$data_abertura}</td>";
						$xls .="<td bgcolor='{$cor}'>{$data_resolvido}</td>";
						$xls .="<td bgcolor='{$cor}' bg_color='{$cor}' align='left'>{$nome_posto}&nbsp;</td>";

						if($telecontrol_distrib)
							$xls .="<td bgcolor='{$cor}'><a href='os_press.php?os={$os}' target='_blank'>{$os}</a>&nbsp;</td>";

						$xls .="<td bgcolor='{$cor}' align='left'>{$referencia}&nbsp;</td>";
						$xls .="<td bgcolor='{$cor}' align='left'>{$descricao}&nbsp;</td>";

						if($telecontrol_distrib) {
							$xls .="<td bgcolor='{$cor}' align='left'>{$linha}&nbsp;</td>";
							$xls .="<td bgcolor='{$cor}' align='left'>{$familia}&nbsp;</td>";
						}

						$xls .="<td bgcolor='{$cor}' align='left'>{$defeito_reclamado}&nbsp;</td>";

						if($telecontrol_distrib){
							$xls .="<td bgcolor='{$cor}' align='left'>{$origem_reclamacao}&nbsp;</td>";
						}

						if (in_array($login_fabrica,[174])) {
							$xls .="<td bgcolor='{$cor}' align='left'>{$descricao_classificacao}&nbsp;</td>";
							$xls .="<td bgcolor='{$cor}' align='left'>{$descricao_origem}&nbsp;</td>";
						}

						$xls .="<td bgcolor='{$cor}' align='left'>{$nome_completo}&nbsp;</td>";

						if($telecontrol_distrib){
							$xls .="<td bgcolor='{$cor}' align='left'>{$estado_consumidor}&nbsp;</td>";
						}

					$xls .="</tr>";

				}
			$xls .= "</tbody>";
		$xls .="</table>";

		if($telecontrol_distrib || in_array($login_fabrica, [174])) {
			$file    = "xls/relatorio-produto-por-defeito-reclamado-$login_fabrica.xls";
			file_put_contents($file, $xls);


			echo "<div style='margin: 20px; text-align: center'>
						<a href='{$file}' target='_blank'><img src='../imagens/excel.gif'><br>
							<font color='#3300CC'>".traduz('Fazer download do relatório defeito por produto')."</font>
						</a>
					</div>";
		}

		echo $xls;

		echo "<div style='text-align: center;'>";
			echo traduz("Total de <b>{$registros}</b> registros.");
		echo "</div>";

	}
}
?>
<br /><br />
</body>
</html>
