<?php

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'funcoes.php';
include_once('anexaNF_inc.php');    // Dentro do include estão definidas as fábricas que anexam imagem da NF e os parâmetros.

$msg_erro = "";

$btn_acao = trim (strtolower ($_POST['btn_acao']));
if (strlen($_GET['btn_acao']) > 0) $btn_acao = trim (strtolower ($_GET['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);

/**
 * Rotina para a exclusão de anexo da OS
 **/
if ($_POST['ajax'] == 'excluir_nf') {
	$img_nf = anti_injection($_POST['excluir_nf']);
	//$img_nf = basename($img_nf);

	$excluiu = (excluirNF($img_nf));
	$nome_anexo = preg_replace("/.*\/([rexs]_)?(\d+)([_-]\d)?\..*/", "$1$2", $img_nf);

	if ($excluiu)  $ret = "ok|" . temNF($nome_anexo, 'linkEx') . "|$img_nf|$nome_anexo";
	if (!$excluiu) $ret = 'ko|Não foi possível excluir o arquivo solicitado.';

	exit($ret);
}//	FIM	Excluir	imagem

if ($btn_acao == "explodir") {
	// executa funcao de explosao
	$sql = "SELECT fn_explode_os_revenda($os_revenda,$login_fabrica)";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if(strpos($msg_erro,"data_abertura_muito_antiga")) {
		$msg_erro = "Data de abertura muito antiga";
	}
	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT  sua_os
				FROM	tbl_os_revenda
				WHERE	os_revenda = $os_revenda
				AND		fabrica = $login_fabrica";
		$res = pg_exec($con, $sql);
		$sua_os = pg_result($res,0,0);

		// redireciona para os_revenda_explodida.php
		header("Location: os_revenda_explodida.php?sua_os=$sua_os");
		exit;
	}
}

if(strlen($os_revenda) > 0 and strlen ($msg_erro) == 0 ){
	// seleciona do banco de dados
	$sql = "SELECT   tbl_os_revenda.sua_os                                                ,
					 tbl_os_revenda.obs                                                   ,
					 tbl_os_revenda.motivo                                                ,
					 tbl_os_revenda.tipo_atendimento                                      ,
					 to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					 to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
					 tbl_revenda.nome  AS revenda_nome                                    ,
					 tbl_revenda.cnpj  AS revenda_cnpj                                    ,
					 tbl_revenda.fone  AS revenda_fone                                    ,
					 tbl_revenda.email AS revenda_email                                   ,
					 tbl_posto_fabrica.codigo_posto
			FROM	 tbl_os_revenda
			JOIN	 tbl_revenda
			ON		 tbl_os_revenda.revenda = tbl_revenda.revenda
			JOIN	 tbl_fabrica USING (fabrica)
			LEFT JOIN tbl_posto USING (posto)
			LEFT JOIN tbl_posto_fabrica
			ON		 tbl_posto_fabrica.posto = tbl_posto.posto
			AND		 tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			WHERE	 tbl_os_revenda.os_revenda = $os_revenda
			AND		 tbl_os_revenda.posto      = $login_posto
			AND		 tbl_os_revenda.fabrica    = $login_fabrica ";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$sua_os           = pg_result($res,0,sua_os);
		$data_abertura    = pg_result($res,0,data_abertura);
		$data_digitacao   = pg_result($res,0,data_digitacao);
		$revenda_nome     = pg_result($res,0,revenda_nome);
		$revenda_cnpj     = pg_result($res,0,revenda_cnpj);
		$revenda_fone     = pg_result($res,0,revenda_fone);
		$revenda_email    = pg_result($res,0,revenda_email);
		$obs              = pg_result($res,0,obs);
		$motivo           = pg_result($res,0,motivo);
		$codigo_posto     = pg_result($res,0,codigo_posto);
		$tipo_atendimento = pg_result($res,0,tipo_atendimento);
	}else{
		header('Location: os_revenda.php');
		exit;
	}
}

$title			= "Cadastro de Ordem de Serviço - Revenda";
$layout_menu	= 'os';

include "cabecalho.php";

?>
<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src="js/anexaNF_excluiAnexo.js"></script>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
}

</style>

<?
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#FF0000" width="700px">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" style="font-size: 12pt" color="#FFFFFF">
<?
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro;
?>
		</font></b>
	</td>
</tr>
</table>
<?
}
?>

<br>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
					</td>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
					</td>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Digitação</font>
					</td>
				</tr>
				<tr>
					<td nowrap align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">
						<? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?>
						</font>
					</td>
					<td nowrap align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $data_abertura ?></font>
					</td>
					<td nowrap align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $data_digitacao ?></font>
					</td>
				</tr>
				<tr>
					<td colspan='3' class="table_line2" height='20'></td>
				</tr>
			</table>
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone Revenda</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">e-Mail Revenda</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_nome ?></font>
					</td>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_cnpj ?></font>
					</td>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_fone ?></font>
					</td>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_email ?></font>
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $obs ?></font>
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Motivo da Troca</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $motivo ?></font>
					</td>
				</tr>
			</table>

		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>

<table width="650" border="0" cellpadding="2" cellspacing="3" align="center" bgcolor="#ffffff">
	<TR>
		<TD colspan="4"><br></TD>
	</TR>
	<tr class="menu_top">
		<? if ($login_fabrica == 1) { ?>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cód. Fabric.</font></td>
		<? } ?>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font></td>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Descrição do produto</font></td>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número de série</font></td>
		<? if ($login_fabrica == 1 and $tipo_atendimento == '18') { ?>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Vlr da Troca</font></td>
		<?}?>
		<? if ($login_fabrica == 1) { ?>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Type</font></td>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Embalagem Original</font></td>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Sinal de Uso</font></td>
		<? } ?>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número da NF</font></td>
	</tr>
<?
	// monta o FOR
	$qtde_item = 20;

		if ($os_revenda){
			// seleciona do banco de dados
			$sql = "SELECT   tbl_os_revenda_item.os_revenda_item    ,
							 tbl_os_revenda_item.produto            ,
							 tbl_os_revenda_item.serie              ,
							 tbl_os_revenda_item.codigo_fabricacao  ,
							 tbl_os_revenda_item.nota_fiscal        ,
							 tbl_os_revenda_item.capacidade         ,
							 tbl_os_revenda_item.type               ,
							 tbl_os_revenda_item.embalagem_original ,
							 tbl_os_revenda_item.sinal_de_uso       ,
							 tbl_produto.referencia                 ,
							 tbl_produto.descricao                  ,
							 tbl_produto.voltagem                   ,
							 tbl_produto.valor_troca
					FROM	 tbl_os_revenda
					JOIN	 tbl_os_revenda_item
					ON		 tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
					JOIN	 tbl_produto
					ON		 tbl_produto.produto = tbl_os_revenda_item.produto
					WHERE	 tbl_os_revenda.os_revenda = $os_revenda
					AND		 tbl_os_revenda.posto      = $login_posto
					AND		 tbl_os_revenda.fabrica    = $login_fabrica ";
			$res = pg_exec($con, $sql);

			for ($i=0; $i<pg_numrows($res); $i++)
			{
				$referencia_produto = pg_result($res,$i,referencia);
				$produto_descricao  = pg_result($res,$i,descricao);
				$produto_voltagem   = pg_result($res,$i,voltagem);
				$produto_serie      = pg_result($res,$i,serie);
				$codigo_fabricacao  = pg_result($res,$i,codigo_fabricacao);
				$nota_fiscal        = pg_result($res,$i,nota_fiscal);
				$capacidade         = pg_result($res,$i,capacidade);
				$type               = pg_result($res,$i,type);
				$embalagem_original = pg_result($res,$i,embalagem_original);
				$sinal_de_uso       = pg_result($res,$i,sinal_de_uso);
				$valor_troca        = pg_result($res,$i,valor_troca);
?>
	<tr>
		<? if ($login_fabrica == 1) { ?>
			<td align="center">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $codigo_fabricacao ?></font>
			</td>
		<? } ?>
		<td align="center">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $referencia_produto ?></font>
		</td>
		<td align="left" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">
			<?
			echo $produto_descricao;
			if (strlen($produto_voltagem) > 0) echo " - ".$produto_voltagem;
			?>
			</font>
		</td>
		<td align="center">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_serie ?></font>
		</td>
		<? if ($login_fabrica == 1 and $tipo_atendimento =='18') { ?>
			<td align='center' nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $valor_troca ?></font>
			</td>
		<?}?>
		<? if ($login_fabrica == 1) { ?>
			<td align='center' nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $type ?></font>
			</td>
			<td align='center' nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if ($embalagem_original == 't') echo "Sim"; else echo "Não"; ?></font>
			</td>
			<td align='center' nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if ($sinal_de_uso == 't') echo "Sim"; else echo "Não"; ?></font>
			</td>
		<? } ?>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $nota_fiscal ?></font>
		</td>
	</tr>
<?
			}
		}
?>
</table>
<? //HD 11419
if ($login_fabrica==1) {
	if(strlen($os_revenda) > 0 ){
		$sql="SELECT tipo_atendimento
				from tbl_os_revenda
				where os_revenda=$os_revenda";
		$res=pg_exec($con,$sql);
		$tipo_atendimento=pg_result($res,0,tipo_atendimento);
		if (strlen($tipo_atendimento) > 0 and ($tipo_atendimento == '17' or $tipo_atendimento == '18')) {
		echo "<br>";
		echo "<table width='650' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffffff' class='table'>";
		echo "<tr>";
		echo "<td nowrap><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='red'><b>Para <u>OS de troca de revenda</u> ser encaminhada para aprovação e posterior fechamento, é necessário explodir a OS.</b></font></td>";
		echo "</tr>";
		echo "</table>";
		}
	}
}
?>
<br>

<?	if ($anexaNotaFiscal and temNF('r_' . $os_revenda, 'bool')) {
		echo "<div>" . temNF('r_' . $os_revenda, 'linkEx') . "</div>\n" . $include_imgZoom;
	} // HD 321132 - FIM
?>

<input type='hidden' name='btn_acao' value=''>
<center>

<? //HD 11419
	if($tipo_atendimento=='17' or $tipo_atendimento=='18'){
?>
<img src='imagens/btn_alterarcinza.gif'  onclick="javascript: document.location='os_revenda_troca.php?os_revenda=<? echo $os_revenda; ?>'" ALT="Alterar" border='0' style="cursor:pointer;">
<? } else {?>
<img src='imagens/btn_alterarcinza.gif'  onclick="javascript: document.location='os_revenda.php?os_revenda=<? echo $os_revenda; ?>'" ALT="Alterar" border='0' style="cursor:pointer;">
<?}?>
<img src='imagens/btn_explodir.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='explodir' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Explodir" border='0' style="cursor:pointer;">
<img src='imagens/btn_imprimir.gif' onclick="javascript: window.open('os_revenda_print.php?os_revenda=<? echo $os_revenda; ?>','osrevenda');" ALT="Imprimir" border='0' style="cursor:pointer;">
</center>

<br>

<center><a href="os_revenda_consulta.php?<?echo $_COOKIE['cookget']; ?>"><img src="imagens/btn_voltarparaconsulta.gif"></a></center>

</form>
<br>

<? include 'rodape.php'; ?>
