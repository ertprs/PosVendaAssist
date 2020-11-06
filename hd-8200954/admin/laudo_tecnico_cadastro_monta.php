<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';


$visual_black = "manutencao-admin";
$layout_menu = "cadastro";
$title = "Cadastro de Laudo Técnico";
if(!isset($semcab))include 'cabecalho.php';
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

<form name="frm_laudo_tecnico" method="post" action="<? echo $PHP_SELF;if(isset($semcab))echo "?semcab=yes"; ?>">

<? if (strlen($msg_erro) > 0) { ?>
<table width="600" border="0" cellpadding="2" cellspacing="1" class="error" align='center'>
	<tr>
		<td><?echo $msg_erro;?></td>
	</tr>
</table>
<? } ?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<tr>
		<td valign="top" align="left">
			<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
				<tr  bgcolor="#596D9B" >
					<td align='left' colspan='4'><font size='2' color='#ffffff'>Laudo Técnico</font></td>
				</tr>
				<tr class='Label'>
					<td>&nbsp;</td>
				</tr>
				<tr class='Label'>
					<td>
						<input type="text" id="txt_titulo" name="txt_titulo" value="<?=$aux_titulo;?>" size="66" maxlength="250" class="frm">
					</td>
					<td colspan='3'><input type='checkbox' name='chk_afirmativa' id='chk_afirmativa' 
						<? if (!empty($aux_afirmativa)){ echo ' <INPUT TYPE="radio" NAME="laudo_afirmativa_$i" value="S">Sim <INPUT TYPE="radio" NAME="laudo_afirmativa_$i" value="N">Não '; } ?>>
					</td>
					<td colspan='3'><input type='checkbox' name='chk_observacao' id='chk_observacao'
						<? if (!empty($aux_observacao)){ echo ' checked="checked"'; } ?>>
					</td>
				</tr>
				<tr class='Label'>
					<td>&nbsp;</td>
				</tr>
			</table>

		<input type='hidden' name='btnacao' value=''>

		<div style="height:20px"></div>

		<center>

			<img src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_laudo_tecnico.btnacao.value == '' ) { document.frm_laudo_tecnico.btnacao.value='gravar' ; document.frm_laudo_tecnico.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">

			<img src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_laudo_tecnico.btnacao.value == '' ) { document.frm_laudo_tecnico.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">

		</center>

	</td>
</tr>
</table>

<div style="height:100px"></div>

<? if(!isset($semcab))include "rodape.php"; ?>