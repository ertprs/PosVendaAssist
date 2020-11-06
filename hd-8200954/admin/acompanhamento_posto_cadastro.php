<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

$pesquisa = $_GET['pesquisa_posto'];
if(strlen($pesquisa) == 0){
	$pesquisa = $_POST['pesquisa_posto'];
}

$btn_acao = $_POST['btn_acao'];

if ($btn_acao == 'gravar'){

	$reclamacao = trim($_POST['reclamacao']);
	if(strlen($reclamacao) == 0){
		$msg_erro = "Digite a reclamação";
	}

	if (strlen($msg_erro) == 0) {
		if(strlen($pesquisa) == 0){
		###INSERE NOVO REGISTRO (Caso produto)
			$sql = "INSERT INTO tbl_pesquisa_posto (
					fabrica       ,
					seleciona     ,
					titulo        ,
					data_cadastro ,
					admin
				) VALUES (
					$login_fabrica        ,
					'$campo_descricao'    ,
					'$reclamacao'         ,
					current_timestamp     ,
					$login_admin
				);";

		}else{
		###ALTERA REGISTRO
			$sql = "UPDATE  tbl_pesquisa_posto SET
					titulo          = '$reclamacao'  ,
					admin           = '$login_admin'
				WHERE pesquisa_posto  = $pesquisa";
		}
#echo "$sql AQUI";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
}

$excluir_pesquisa = $_GET['excluir'];

if(strlen($excluir_pesquisa) > 0){
	if(pg_numrows($res) > 0 AND strlen($msg_erro) == 0){
		$sql2 = "DELETE FROM tbl_pesquisa_posto WHERE pesquisa_posto = $excluir_pesquisa;";
		$res2 = pg_exec($con,$sql2);
		$msg_erro = pg_errormessage($con);
	}
#	echo "$sql2";
}


if(strlen($pesquisa) > 0){

	$sql = "SELECT *
			FROM tbl_pesquisa_posto
			WHERE pesquisa_posto = $pesquisa;";
	$res      = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	if(pg_numrows($res) > 0){
		$aux_reclamacao = pg_result($res,0,titulo);
	}
}

$visual_black = "manutencao-admin";
$layout_menu = "cadastro";
$title = "Cadastro de Reclamações";
include 'cabecalho.php';
?>

<script language="javascript" type="text/javascript" src="js/js_jean.js"></script>

<style type="text/css">
	.Label{
	font-family: Verdana;
	font-size: 10px;
	}
	.Titulo{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	}
	.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
	}
</style>

<form name="frm_pesquisa" method="post" action="<? echo $PHP_SELF ?>">

<? if (strlen($msg_erro) > 0) { ?>
<table width="600" border="0" cellpadding="2" cellspacing="1" class="error" align='center'>
	<tr>
		<td><?echo $msg_erro;?></td>
	</tr>
</table>
<? } ?>

<BR>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<tr>
		<td valign="top" align="left">
			<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='600' border='0'>
				<tr  bgcolor="#596D9B" >
					<INPUT TYPE="hidden" name='pesquisa_posto' value='<? echo "$pesquisa_posto"; ?>'>
					<td align='left' colspan='5'><font size='2' color='#ffffff'>Cadastra reclamação </font></td>
				</tr>
				<tr class='Label'>
					<td>&nbsp;</td>
				</tr>
				<tr class='Label'>
					<td nowrap ><label for="txt_titulo" style="cursor:pointer">Reclamação</label></td>
					<td>
						<input type="text" name="reclamacao" value="<?=$aux_reclamacao;?>" size="66" maxlength="250" class="frm">
					</td>
				</tr>
<!--			<tr class='Label'>
					<td><label for="txt_titulo" style="cursor:pointer">Campo Descrição?</label></td>
					<td>
						<INPUT TYPE="radio" NAME="campo_descricao" value="t"> Sim <INPUT TYPE="radio" NAME="campo_descricao" value="f"> Não
					</td>
				</tr>
-->				<tr class='Label'>
					<td>&nbsp;</td>
				</tr>
			</table>

		<input type='hidden' name='btn_acao' value=''>

		<div style="height:20px"></div>

		<center>
			<img src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='gravar' ; document.frm_pesquisa.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
			<img src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">
		</center>

	</td>
</tr>
</table>
</form>
<?
//hd 46079

$sql = "SELECT * FROM tbl_pesquisa_posto WHERE fabrica = $login_fabrica;";
$res = pg_exec ($con,$sql);

echo "<br><br>";
echo "<table width='800' border='0' cellpadding='2' cellspacing='1' class='titulo' align='center' style=' border:#485989 1px solid; background-color: #e6eef7 '>";
echo "<tr bgcolor='#D9E2EF'>";
echo "<td colspan='3'><b>RECLAMAÇÕES CADASTRADAS</b></td>";
echo "</tr>";
echo "<tr bgcolor='#D9E2EF'>";
echo "<td><b>Reclamação</b></td>";
/* echo "<td><b>Campo descrição?</b></td>";	*/
echo "<td><b>Excluir</b></td>";
echo "</tr>";

for ($i = 0 ; $i < pg_numrows($res) ; $i++){
	$pesquisa_posto   = trim(pg_result($res,$i,pesquisa_posto));
/*	$seleciona        = trim(pg_result($res,$i,seleciona)); */
	$titulo           = trim(pg_result($res,$i,titulo));


	$cor = ($i % 2 == 0) ? "#FFFFFF" : "#F1F4FA";

	echo "<tr bgcolor='$cor' class='Label'>";

	echo "<td align='left' nowrap><b><a href='$PHP_SELF?pesquisa_posto=$pesquisa_posto'>$titulo</b></a></td>";
/*	echo "<td>$seleciona</td>";	*/
	echo "<td align='left' nowrap><b><a href='$PHP_SELF?excluir=$pesquisa_posto'>Excluir</b></a></td>";

	echo "</tr>";
}
echo "</table>";
?>


<div style="height:100px"></div>

<? if(!isset($semcab))include "rodape.php"; ?>