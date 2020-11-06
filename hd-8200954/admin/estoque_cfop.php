<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_GET["estoque_cfop"]) > 0) {
	$estoque_cfop = trim($_GET["estoque_cfop"]);
}
if (strlen($_POST["estoque_cfop"]) > 0) {
	$estoque_cfop = trim($_POST["estoque_cfop"]);
} 



if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "deletar" and strlen($estoque_cfop) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_estoque_cfop
			WHERE  fabrica = $login_fabrica
			AND    estoque_cfop = $estoque_cfop";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen ($msg_erro) == 0) {

		# CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");

	}else{

		# ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$estoque_cfop 	= $_POST["estoque_cfop"];
		$cfop      		= $_POST["cfop"];
		$tipo   		= $_POST["tipo"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {

	$aux_cfop = trim($_POST['cfop']);
	$aux_tipo = $_POST['tipo'];
	
	
	if ($aux_cfop and strlen($estoque_cfop) == 0){
		$sql_confere_cfop = "SELECT * from tbl_estoque_cfop where fabrica=$login_fabrica and cfop='$aux_cfop'";
		
		$res_confere_cfop = pg_query($con,$sql_confere_cfop);
		
		if ( pg_num_rows($res_confere_cfop)>0 ){
			$msg_erro = "CFOP '$aux_cfop' ja está cadastrado";
		}
	}

	if (empty($aux_cfop)) {
		$msg_erro = "Favor informar a Natureza de Operação (CFOP)";
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($estoque_cfop) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_estoque_cfop (
						fabrica,
						cfop,
						tipo
					) VALUES (
						$login_fabrica,
						'$aux_cfop'   ,
						'$aux_tipo'
					)";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_estoque_cfop SET
							cfop    =  '$aux_cfop',
							tipo 	=  '$aux_tipo'
					WHERE  fabrica     = $login_fabrica
					AND    estoque_cfop = $estoque_cfop";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	
	if (strlen ($msg_erro) == 0) {

		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");

	}else{

		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$estoque_cfop  = $_POST["estoque_cfop"];
		$cfop          = $_POST["cfop"];
		$tipo     	   = $_POST["tipo"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");

	}
}

###CARREGA REGISTRO
if (strlen($estoque_cfop) > 0 AND strlen ($msg_erro) == 0) {
	$sql = "SELECT  cfop,
					tipo
			FROM    tbl_estoque_cfop
			WHERE   fabrica     = $login_fabrica
			AND     estoque_cfop = $estoque_cfop;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$cfop = trim(pg_result($res,0,'cfop'));
		$tipo = trim(pg_result($res,0,'tipo'));
	}
}

$msg = $_GET['msg'];
$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE ESTOQUE CFOP";
include 'cabecalho.php';
?>

<style type="text/css">
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
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
.subtitulo{
	color: #7092BE
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.div_principal{
	margin:20px 0;
}
</style>
<script>
function enviaCFOP(acao){
	if(document.frm_cfop.btnacao.value == ''){ 
		document.frm_cfop.btnacao.value=acao;
		document.frm_cfop.submit();
	}else{
		alert ('Aguarde submissão');
	}
}
</script>

<form name="frm_cfop" method="post" action="<? $PHP_SELF ?>">

	<input type="hidden" name="estoque_cfop" value="<? echo $estoque_cfop ?>">

	<table width="700" border='0' cellspacing='0' cellpadding='2' align='center' class='formulario'>

		<? if (strlen($msg_erro) > 0) { ?>
			<tr class="msg_erro">
				<td colspan='4'>
				<?
				if (strpos ($msg_erro,'duplicate key') > 0) {
					$msg_erro = "CFOP duplicado não permitido";
				}
				echo $msg_erro; ?>
				</td>
			</tr>

		<? } ?>

		<? if(strlen($msg)>0){ ?>
			<tr class='sucesso'><td colspan='4'><? echo $msg; ?></td></tr>
		<? } ?>

		<tr class="titulo_tabela">
			<td colspan="4">Cadastrar Tipo de Pedido</td>
		</tr>
		<tr>
			<td width='50px'>&nbsp;</td>
			<td align='left'><strong>CFOP</strong></td>
			<td align='left'><strong>Tipo</strong></td>
			<td width='50px'>&nbsp;</td>
		</tr>
		<tr>
			<td width='50px'>&nbsp;</td>
			<td align='left'>
				<input class='frm' type="text" name="cfop" value="<? echo $cfop ?>" size="30" maxlength="50">
			</td>
			<td align='left'>
				<select name="tipo" id="tipo" class="frm" >
					<?php $frase_venda = ($login_fabrica == 3) ? "Remessa em Consignação" : "Faturada (Venda)" ;?>
					<option value="venda" <?php echo ($tipo == 'venda') ? 'selected="selected"' : null;?> > <?php echo $frase_venda?> </option>
					<?php if ($login_fabrica != 15 and $login_fabrica != 3){?>
						<option value="antecipada" <?php echo ($tipo == 'antecipada') ? 'selected="selected"' : null;?> >Remessa em Garantia (Antecipada)</option>
				  <?php }?>
				</select> 
			</td>
			<td width='50px'>&nbsp;</td>
		</tr>
		
		<tr><td>&nbsp;</td></tr>

		<tr>
			<td colspan='4' align='center'>
				<input type="hidden" name="btnacao" value="" />
				<input type="button" value="Gravar" onclick="enviaCFOP('gravar');" alt="Gravar formulário" border='0' style="cursor:pointer;">
				<input type="button" value="Apagar" onclick="enviaCFOP('deletar');" ALT="Apagar Informação" border='0' style="cursor:pointer;">
				<input type="button" value="Limpar" onclick="window.location='<? echo $PHP_SELF ?>';" alt="Limpar campos" border='0' style="cursor:pointer;">
			</td>
		</tr>
		
		<tr><td>&nbsp;</td></tr>
	</table>

</form>

<div id="wrapper">
	<strong>Para efetuar alterações, clique no CFOP.</strong>
</div>

<?php
$sql = "SELECT  estoque_cfop,
				cfop,
				tipo
		FROM    tbl_estoque_cfop
		WHERE   fabrica = $login_fabrica
		ORDER BY tipo DESC";

$res0 = pg_exec ($con,$sql);
$total_cfop = pg_numrows($res0);
?>
<div id="wrapper" class="div_principal">

	<table width='700' border='0' align='center' class='tabela' cellpadding='2' cellspacing='1'>	
		<tr align='center' bgcolor='#596D9B'> 
			<td colspan='2' style='font:bold 14px Arial; color:#FFFFFF;'>Relação dos Tipos de CFOP</td>
		</tr>
		
		<tr bgcolor='#596D9B' style='font:bold 12px Arial; color:#FFFFFF;'>
			<td>CFOP</td>
			<td>Tipo</td>
		</tr>

		<?php
		for($i=0;$i<$total_cfop;$i++){
			
			$cor =  ($i % 2 == 0) ? '#F1F4FA' : '#F7F5F0'; 

			$estoque_cfop 	= trim(pg_result($res0,$i,'estoque_cfop'));
			$cfop 		= trim(pg_result($res0,$i,'cfop'));
			$tipo      = trim(pg_result($res0,$i,'tipo'));
			
			if ($login_fabrica==3){
				$descricao_tipo = ($tipo == 'venda') ? 'Remessa em Consignação' : null;
			}else{
				$descricao_tipo = ($tipo == 'venda') ? 'Faturada (Venda)' : 'Remessa em Garantia (Antecipada)';
			}
			

			echo "<tr bgcolor='$cor'>";
			echo "	<td width='150' align=\"left\">";
			echo "		<a href='$PHP_SELF?estoque_cfop=$estoque_cfop'>$cfop</a>";
			echo "	</td>";
			echo "	<td width=\"150\" align=\"left\">";
			echo "		<a href='$PHP_SELF?estoque_cfop=$estoque_cfop'>$descricao_tipo</a>";
			echo '	</td>';
			echo '</tr>';

		}
		?>
	</table>
</div>

<?php include "rodape.php"; ?>
