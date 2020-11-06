<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if($login_fabrica == 1){
	include("os_revenda_explodida_blackedecker.php");
	exit;
}

if($login_fabrica == 15){
	header("Location:os_revenda_consulta_lite_teste.php?acao=PESQUISAR&opcao4=4&numero_os=$sua_os");
	exit;
}

include 'funcoes.php';

$msg_erro = "";

if (strlen($_GET['sua_os']) > 0)  $os_revenda = trim($_GET['sua_os']);
if (strlen($_POST['sua_os']) > 0) $os_revenda = trim($_POST['sua_os']);

if (strlen($_GET['sua_os']) > 0)  $sua_os = trim($_GET['sua_os']);
if (strlen($_POST['sua_os']) > 0) $sua_os = trim($_POST['sua_os']);

if(strlen($sua_os) > 0){
	// seleciona do banco de dados
	/* HD 14504 - ALterei algumas coisas aqui */
	$sql = "SELECT   to_char(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura  ,
					 to_char(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao ,
					 tbl_os.revenda_nome                         AS revenda_nome   ,
					 tbl_os.revenda_cnpj                         AS revenda_cnpj   
			FROM	 tbl_os
			JOIN	 tbl_posto
			ON		 tbl_os.posto = tbl_posto.posto
			WHERE	 ( tbl_os.sua_os ='".$sua_os."-1' OR tbl_os.sua_os ='".$sua_os."-1'  )
			AND		 tbl_os.fabrica = $login_fabrica 
			AND		 tbl_os.consumidor_revenda = 'R'
			AND		 tbl_os.posto   = $login_posto 
			LIMIT 1 ";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$data_abertura  = pg_result($res,0,data_abertura);
		$data_digitacao = pg_result($res,0,data_digitacao);
		$revenda_nome   = pg_result($res,0,revenda_nome);
		$revenda_cnpj   = pg_result($res,0,revenda_cnpj);
	}else{
		header('Location: os_revenda_teste.php');
		exit;
	}
}else{
	header('Location: os_revenda_teste.php');
	exit;
}


$title			= "Ordem de Serviço Explodida - Revenda"; 
$layout_menu	= "os";

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

		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>

<table width="600" border="0" cellpadding="2" cellspacing="3" align="center" bgcolor="#ffffff">
	<TR>
		<? if($login_fabrica==51){?>
			<td align='left' colspan="4">
				<TABLE>
					<TR>
						<TD width='12' height='10' bgcolor='#FFCCCC'>&nbsp;</TD>
						<TD colspan="3" style='font-size: 10px;'>OS com intervenção da fábrica. O Produto desta O.S. necessita de troca.</TD>
					</TR>
				</TABLE>
			</td>
		<?}else{?>
			<TD colspan="4"><br></TD>
		<? } ?>
	</TR>
	<tr class="menu_top">
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Referência do Produto</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Descrição do Produto</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número de série</font></td>
	</tr>
<?
	// monta o FOR
	$qtde_item = 20;

		if ($sua_os){
			// seleciona do banco de dados
			/* HD 14504 - ALterei algumas coisas aqui */
			$sql = "SELECT   tbl_os.os              AS os        ,
							 tbl_os.sua_os          AS sua_os    ,
							 tbl_os.serie           AS serie     ,
							 tbl_produto.referencia AS referencia,
							 tbl_produto.descricao  AS descricao 
					FROM	 tbl_os
					JOIN	 tbl_produto
					ON		 tbl_produto.produto = tbl_os.produto
					WHERE	 tbl_os.fabrica = $login_fabrica
					AND      tbl_os.posto   = $login_posto
					AND      tbl_os.consumidor_revenda = 'R'
					AND      tbl_os.sua_os LIKE '".$sua_os."-%' ";
			$res = pg_exec($con, $sql);

			for ($i=0; $i<pg_numrows($res); $i++)
			{
				$os                 = pg_result($res,$i,os);
				$sua_os             = pg_result($res,$i,sua_os);
				$referencia_produto = pg_result($res,$i,referencia);
				$produto_descricao  = pg_result($res,$i,descricao);
				$produto_serie      = pg_result($res,$i,serie);

				$sqlI = "SELECT status_os
						FROM    tbl_os_status
						WHERE   os = $os
						ORDER BY data DESC LIMIT 1";
				$resI = pg_exec ($con,$sqlI);
				#echo nl2br($sqlI);
				if(pg_numrows($resI) > 0){
					$status_os = pg_result($resI,0,status_os);
					if($status_os == 62) $cor="#FFCCCC";
					else                 $cor="#FFFFFF";
				}else $cor="#FFFFFF";
?>
	<tr bgcolor='<? echo $cor; ?>'>
		<td align="center">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">
			<?
			if ($login_fabrica == 45){
				echo "<A HREF='os_item_new.php?os=$os' target='_blank'>".$sua_os."</A>";
			}else{
				echo $sua_os;
			}
			?>
			</font>
		</td>
		<td align="center">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $referencia_produto ?></font>
		</td>
		<td align="left">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_descricao ?></font>
		</td>
		<td align="center">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_serie ?></font>
		</td>
	</tr>
<?
			}
		}
?>
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