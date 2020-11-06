<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

if (strlen($_GET['os']) > 0)   $os = trim ($_GET['os']);
if (strlen($_POST['os']) > 0)  $os = trim ($_POST['os']);

if ($btn_acao == "gravar") {
	if (strlen ($os) > 0) {
		$motivo_atraso = $_POST ['motivo_atraso'];
		$sql = "UPDATE tbl_os SET motivo_atraso = '$motivo_atraso'
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $login_posto;";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	if (strlen($msg_erro) == 0) {
//		header("Location: os_parametros.php");
		header("Location: os_consulta_lite.php");
		exit;
	}
}

if (strlen($os) > 0) {
	#----------------- Le dados da OS --------------
	$sql = "SELECT  tbl_os.*                       ,
					tbl_produto.produto            ,
					tbl_produto.referencia         ,
					tbl_produto.descricao          ,
					tbl_produto.linha              ,
					tbl_linha.nome AS linha_nome   ,
					tbl_posto_fabrica.codigo_posto ,
					tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
					tbl_causa_defeito.descricao      AS causa_defeito_descricao,
					tbl_os.motivo_atraso           ,
					tbl_os_extra.os_reincidente      AS reincidente_os
			FROM    tbl_os
			JOIN    tbl_os_extra USING (os)
			LEFT JOIN    tbl_produto USING (produto)
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN    tbl_linha   ON tbl_produto.linha = tbl_linha.linha
			LEFT JOIN    tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
			LEFT JOIN    tbl_causa_defeito      ON tbl_causa_defeito.causa_defeito           = tbl_os.causa_defeito
			WHERE   tbl_os.os = $os";
	$res = pg_exec ($con,$sql) ;
	
	$defeito_constatado = pg_result ($res,0,defeito_constatado);
	$causa_defeito      = pg_result ($res,0,causa_defeito);
	$linha              = pg_result ($res,0,linha);
	$linha_nome         = pg_result ($res,0,linha_nome);
	$consumidor_nome    = pg_result ($res,0,consumidor_nome);
	$sua_os             = pg_result ($res,0,sua_os);
	$produto_os         = pg_result ($res,0,produto);
	$produto_referencia = pg_result ($res,0,referencia);
	$produto_descricao  = pg_result ($res,0,descricao);
	$produto_serie      = pg_result ($res,0,serie);
	$motivo_atraso      = pg_result ($res,0,motivo_atraso);
	$obs                = pg_result ($res,0,obs);
	$codigo_posto       = pg_result ($res,0,codigo_posto);
	$os_reincidente     = pg_result ($res,0,reincidente_os);
	$defeito_constatado_descricao = pg_result ($res,0,defeito_constatado_descricao);
	$causa_defeito_descricao = pg_result ($res,0,causa_defeito_descricao);
	
	if (strlen($os_reincidente) > 0) {
		$sql = "SELECT tbl_os.sua_os
				FROM   tbl_os
				WHERE  tbl_os.os      = $os_reincidente
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.posto   = $login_posto;";
		$res = @pg_exec ($con,$sql) ;
		
		if (pg_numrows($res) > 0) $sua_os_reincidente = trim(pg_result($res,0,sua_os));
	}
}

$title = "Telecontrol - Assistência Técnica - Motivo Atraso da Ordem de Serviço";

$layout_menu = 'os';
include "cabecalho.php";

if (strlen ($msg_erro) > 0){
?>
<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
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

<? } ?>


<table width="750" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type="hidden" name="os" value="<?echo $os?>">
		
		<p>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $consumidor_nome ?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_referencia . " - " . $produto_descricao?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_serie ?></b>
				</font>
			</td>
		</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<? if ($login_fabrica <> 5) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Constatado</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<B><? echo $defeito_constatado_descricao; ?></B>
				</font>
			</td>
			<? } ?>
			
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Causa do Defeito</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<B><? echo $causa_defeito_descricao; ?></B>
				</font>
			</td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="2"><B>Motivo do atraso:</B></FONT>
		<br>
		<textarea NAME="motivo_atraso" cols="70" rows="5" class="frm"><? echo $motivo_atraso; ?></textarea>
	</td>
</tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar" border='0' style="cursor:pointer;">

	</td>
</tr>

</form>

</table>

<p>

<? include "rodape.php";?>