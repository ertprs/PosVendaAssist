<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";
//HD 15879
if (strlen($_GET['sua_os']) > 0)  $os_revenda = trim($_GET['sua_os']);
if (strlen($_POST['sua_os']) > 0) $os_revenda = trim($_POST['sua_os']);
if (strlen($_GET['posto']) > 0)  $posto = trim($_GET['posto']);
if (strlen($_POST['posto']) > 0) $posto = trim($_POST['posto']);

if(strlen($os_revenda) > 0){
	// seleciona do banco de dados
	$sql = "SELECT   to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY')  AS data_abertura  ,
					 to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY') AS data_digitacao ,
					 tbl_revenda.nome                         AS revenda_nome   ,
					 tbl_revenda.cnpj                         AS revenda_cnpj   ,
					 tbl_posto.nome                              AS posto_nome     ,
					 tbl_posto.cnpj                              AS posto_cnpj     
			FROM	 tbl_os_revenda
			JOIN	 tbl_posto
			ON		 tbl_os_revenda.posto = tbl_posto.posto
			JOIN	 tbl_revenda ON tbl_os_revenda.revenda=tbl_revenda.revenda
			WHERE	 tbl_os_revenda.sua_os ='$os_revenda'
			AND		 tbl_os_revenda.fabrica = $login_fabrica 
			AND		 tbl_os_revenda.posto=$posto";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$data_abertura  = pg_result($res,0,data_abertura);
		$data_digitacao = pg_result($res,0,data_digitacao);
		$revenda_nome   = pg_result($res,0,revenda_nome);
		$revenda_cnpj   = pg_result($res,0,revenda_cnpj);
		$posto_nome     = pg_result($res,0,posto_nome);
		$posto_cnpj     = pg_result($res,0,posto_cnpj);
	}else{
		header('Location: os_revenda.php');
		exit;
	}
}


$title			= "Ordem de Serviço Explodida - Revenda"; 
$layout_menu	= "callcenter";

include "cabecalho.php";

?>

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

<br>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
					</td>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Digitação</font>
					</td>
				</tr>
				<tr>
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
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_cnpj ?></font>
					</td>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_nome ?></font>
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ do Posto</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Posto</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $posto_cnpj ?></font>
					</td>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $posto_nome ?></font>
					</td>
				</tr>
			</table>

		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>

<table width="600" border="0" cellpadding="2" cellspacing="3" align="center" bgcolor="#ffffff">
	<TR>
		<TD colspan="4"><br></TD>
	</TR>
	<tr class="menu_top">
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font></td>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Fabricação</font></td>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Referência do Produto</font></td>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Descrição do Produto</font></td>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número de série</font></td>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Ações</font></td>
	</tr>
<?
	if (strlen($os_revenda)>0){
		// seleciona do banco de dados
		$sql = "SELECT   tbl_os.os                      AS os         ,
						 tbl_os.sua_os                  AS sua_os     ,
						 tbl_os.codigo_fabricacao                     ,
						 tbl_os.serie                   AS serie      ,
						 tbl_produto.referencia         AS referencia ,
						 tbl_produto.descricao          AS descricao  ,
						 tbl_posto_fabrica.codigo_posto                       ,
						 tbl_os.tipo_atendimento
				FROM	 tbl_os
				JOIN	 tbl_produto ON tbl_produto.produto = tbl_os.produto
				JOIN	 tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_os.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE	 tbl_os.sua_os LIKE '$os_revenda-%'
				AND		 tbl_os.posto=$posto
				AND		 tbl_os.consumidor_revenda ='R'";

		$res = pg_exec($con, $sql);
		for ($i=0; $i<pg_numrows($res); $i++){
			$os                 = pg_result($res,$i,os);
			$sua_os             = pg_result($res,$i,sua_os);
			$codigo_fabricacao  = pg_result($res,$i,codigo_fabricacao);
			$referencia_produto = pg_result($res,$i,referencia);
			$produto_descricao  = pg_result($res,$i,descricao);
			$produto_serie      = pg_result($res,$i,serie);
			$codigo_posto       = pg_result($res,$i,codigo_posto);
			$tipo_atendimento   = pg_result($res,$i,tipo_atendimento);
?>
	<tr>
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $codigo_posto.$sua_os; ?></font>
		</td>
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $codigo_fabricacao; ?></font>
		</td>
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $referencia_produto ?></font>
		</td>
		<td align="left" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_descricao ?></font>
		</td>
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_serie ?></font>
		</td>
		<td align="center" nowrap>
		<? if(strlen($tipo_atendimento) == 0){ ?>
			<a href='os_item.php?os=<? echo $os ?>' target='_blank'><img border='0' src='imagens/btn_lanca.gif'></a>
		<? } ?>
		</td>
	</tr>
<?
		}
	}
?>
<?//HD 19901 14/5/2008 adicionado botão?>

<?
$sql = "SELECT   tipo_atendimento
		FROM	 tbl_os_revenda
		JOIN	 tbl_posto
		ON		 tbl_os_revenda.posto = tbl_posto.posto
		JOIN	 tbl_revenda ON tbl_os_revenda.revenda=tbl_revenda.revenda
		WHERE	 tbl_os_revenda.sua_os ='$os_revenda'
		AND		 tbl_os_revenda.fabrica = $login_fabrica 
		AND		 tbl_os_revenda.posto=$posto
		AND   tipo_atendimento IN (17,18,35)";
$res = pg_exec ($con,$sql) ;

if(pg_numrows($res)>0){
?>
<TR>
	<TD colspan="4">
		<br>
		<a href="os_revenda_troca.php"><img src="imagens/btn_lancanovaos.gif"></a>
	</TD>
</TR>
<?}?>
<!--
	<TR>
		<TD colspan="4">
			<br>
			<input type='hidden' name='btn_acao' value=''>
			<img src='imagens/btn_imprimir.gif' onclick="javascript: window.open('os_revenda_print.php?os_revenda=<? echo $os_revenda; ?>','osrevenda');" ALT="Imprimir" border='0' style="cursor:pointer;">
		</TD>
	</TR>
-->
</table>
</form>
<br>

<? include 'rodape.php'; ?>