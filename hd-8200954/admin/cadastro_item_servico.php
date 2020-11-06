<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

include 'funcoes.php';

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if (strlen($_GET["item"]) > 0) {
	$item_codigo = trim($_GET["item"]);
}

if ($btnacao == "deletar" and strlen($item_codigo) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_esmaltec_item_servico
			WHERE  tbl_esmaltec_item_servico.esmaltec_item_servico = $item_codigo";
	$res = pg_exec ($con,$sql);

	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF?msg=Excluído com Sucesso");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$codigo       = $_POST["codigo_item"];
		$descricao    = $_POST["descricao_item"];
		$ativo        = $_POST["ativo"];
		$valor        = $_POST["valor_item"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {
	
	if (strlen($_POST["codigo_item"]) > 0) {
		$codigo = "'". trim($_POST["codigo_item"]) ."'";
	}else{
		$msg_erro .= "Informe o Código do Item <br />";
	}

	if (strlen($_POST["descricao_item"]) > 0) {
		$descricao = "'". trim($_POST["descricao_item"]) ."'";
	}else{
		$msg_erro .= "Informe a Descrição do Item <br />";
	}
	
	if (strlen($_POST["valor_item"]) > 0) {
		$valor = "'". trim($_POST["valor_item"]) ."'";
	}else{
		$msg_erro .= "Informe o Valor do Item";
	}
	
	if (strlen($_POST["ativo"]) > 0) {
		$ativo = 't';
	}else{
		$ativo = 'f';
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($item_codigo) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_esmaltec_item_servico (
						codigo  ,
						descricao    ,
						ativo  ,
						valor
					) VALUES (
						$codigo,
						$descricao,
						'$ativo',
						$valor
					);";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		$msg = "Gravado com Sucesso!";

		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_esmaltec_item_servico SET
					codigo      = $codigo,
					descricao   = $descricao,
					ativo       = '$ativo',
					valor       = $valor
			WHERE  tbl_esmaltec_item_servico.esmaltec_item_servico = $item_codigo";

			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			$msg = "Atualizado com Sucesso!";
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF?msg=".$msg."");
		exit;
	}else{
		$codigo       = $_POST["codigo_item"];
		$descricao    = $_POST["descricao_item"];
		$ativo        = $_POST["ativo"];
		$valor        = $_POST["valor_item"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($item_codigo) > 0) {

	$sql = "SELECT  tbl_esmaltec_item_servico.codigo    ,
					tbl_esmaltec_item_servico.descricao  ,
					tbl_esmaltec_item_servico.ativo   ,
					tbl_esmaltec_item_servico.valor
			FROM    tbl_esmaltec_item_servico
			WHERE  tbl_esmaltec_item_servico.esmaltec_item_servico = $item_codigo";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$codigo    = trim(pg_result($res,0,codigo));
		$descricao = trim(pg_result($res,0,descricao));
		$ativo     = trim(pg_result($res,0,ativo));
		$valor     = trim(pg_result($res,0,valor));
		
	}
}

$msg = $_GET['msg'];
$layout_menu = "cadastro";
$title = "CADASTRO DE ITEM DE SERVIÇO";
include 'cabecalho.php';
	
?>

<style type='text/css'>
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
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

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

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

.espaco{
	padding:0 0 0 50px;
}
</style>

<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type='text/javascript'>
	$().ready(function(){
		$('#valor_item').numeric({allow:".,"});
	});
</script>
<form name="frm_cad_item" method="post" action="<? $PHP_SELF ?>">

<table width='700' border='0'  align='center' cellpadding='3' cellspacing='0' class='formulario'>
	<?php if (strlen($msg_erro) > 0) { ?>
		<tr class='msg_erro'>
			<td colspan='4'><?php echo $msg_erro; ?></td>
		</tr>
	<?php } ?>

	<?php if (strlen($msg) > 0) { ?>
		<tr class='sucesso'>
			<td colspan='4'><?php echo $msg; ?></td>
		</tr>
	<?php } ?>

	<?
	$labelBtnGravar = strlen($item_codigo) > 0 ? "Alterar" : "Gravar";
	$checkAtivoDisable = in_array($item_codigo, array(35, 36)) ? "DISABLED" : "ENABLED";
	?>
	<tr class='titulo_tabela'>
		<td colspan='4'> Cadastro </td>
	</tr>
	<tr><td colspan='4'>&nbsp;</td></tr>
	<tr>
		<td class='espaco' width='100'>
			Código <br />
			<input type='text' name='codigo_item' id='codigo_item' size='12' class='frm' value='<? echo $codigo; ?>'  maxlength='25'>
		</td>
		<td width='350'>
			Descrição <br />
			<input type='text' name='descricao_item' id='descricao_item' size='50' class='frm' value='<? echo $descricao; ?>' maxlength='100'>
		</td>

		<td width='100'>
			Valor <br />
			<input type='text' name='valor_item' id='valor_item' size='10' class='frm' value='<? echo $valor; ?>'>
		</td>

		<td >
			Ativo <br />
			<input type='checkbox' value='t' name='ativo' <?=$checkAtivoDisable?> class='frm' <? if($ativo == 't') echo 'checked';?>>
		</td>
	</tr>
	
	<tr><td colspan='4'>&nbsp;</td></tr>
	<tr>
		<td colspan='4' align='center'>

			<input type='hidden' name='btnacao' value=''>
			<input type='hidden' name='item_codigo' value='<? echo $item_codigo; ?>'>

			<input type="button" value="<?=$labelBtnGravar?>" ONCLICK="javascript: if (document.frm_cad_item.btnacao.value == '' ) { document.frm_cad_item.btnacao.value='gravar' ; document.frm_cad_item.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>

<!--			<input type="button" value="Apagar" ONCLICK="javascript: if (document.frm_cad_item.btnacao.value == '' ) { document.frm_cad_item.btnacao.value='deletar' ; document.frm_cad_item.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Informação" border='0' >-->

			<input type="button" value="Limpar" ONCLICK="javascript: if (document.frm_cad_item.btnacao.value == '' ) { document.frm_cad_item.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' >
		</td>
	</tr>
	<tr><td colspan='4'>&nbsp;</td></tr>
</table>
</form>

<br>



<?php
if (strlen ($item_codigo) == 0) {
	

	$sql = "SELECT  tbl_esmaltec_item_servico.esmaltec_item_servico,
					tbl_esmaltec_item_servico.codigo    ,
					tbl_esmaltec_item_servico.descricao  ,
					tbl_esmaltec_item_servico.ativo   ,
					tbl_esmaltec_item_servico.valor
			FROM    tbl_esmaltec_item_servico ORDER BY descricao";

	$res = pg_exec ($con,$sql);
	$total = pg_numrows($res);
	if ($total > 0) { ?>
		<table  align='center' width='700' border='0' cellpadding='2' cellspacing='1' class='tabela'>
		<tr class='titulo_coluna'>
		<td nowrap>Código</td>
		<td nowrap>Descrição</td>
		<td nowrap align='right'>Valor</td>
		<td nowrap>Status</td>

		
		</tr>
		<?
		for ($x = 0 ; $x < $total; $x++){
			$codigo_item          = trim(pg_result($res,$x,esmaltec_item_servico));
			$descricao            = trim(pg_result($res,$x,descricao));
			$codigo               = trim(pg_result($res,$x,codigo));
			$ativo                = trim(pg_result($res,$x,ativo));
			$valor                = trim(pg_result($res,$x,valor));

			$cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
		
			echo "<tr bgcolor='$cor'>";
			echo "<td nowrap><a href='$PHP_SELF?item=$codigo_item'>$codigo</a></td>";
			echo "<td nowrap align='left'><a href='$PHP_SELF?item=$codigo_item'>$descricao</a></td>";
			echo "<td nowrap align='right'>$valor</td>";
			if($ativo == 't'){
				echo "<td nowrap>Ativo</td>";
			}
			else{
				echo "<td nowrap>Inativo</td>";
			}
			
			echo "</tr>";
		}
		echo "</table>";
	}
}

echo "<br>";

include "rodape.php";
?>
