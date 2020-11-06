<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

if($login_fabrica <> 1){
	include("menu_os.php");
	exit;
}

$msg_erro = "";

if (strlen($_GET['sua_os']) > 0)  $os_revenda = trim($_GET['sua_os']);
if (strlen($_POST['sua_os']) > 0) $os_revenda = trim($_POST['sua_os']);

session_start();
$_SESSION["sua_os_explodida"] = $_GET['sua_os'];

if(strlen($sua_os) > 0){
	// seleciona do banco de dados
	$sql =	"SELECT  to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao ,
					tbl_os.revenda_nome                         AS revenda_nome   ,
					tbl_os.revenda_cnpj                         AS revenda_cnpj   
			FROM	tbl_os
			WHERE	 tbl_os.sua_os LIKE '".$sua_os."-%'
			AND		 tbl_os.fabrica = $login_fabrica
			AND		 tbl_os.posto   = $login_posto
			LIMIT	1 ";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$data_abertura  = pg_result($res,0,data_abertura);
		$data_digitacao = pg_result($res,0,data_digitacao);
		$revenda_nome   = pg_result($res,0,revenda_nome);
		$revenda_cnpj   = pg_result($res,0,revenda_cnpj);
	}else{
		header('Location: os_revenda.php');
		exit;
	}
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
		<TD colspan="4"><br></TD>
	</TR>
	<tr class="menu_top">
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Fabricação</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Referência</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Descrição</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número de Série</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Ações</font></td>
	</tr>
<?
	// monta o FOR
	$qtde_item = 20;

		if ($sua_os){
			// seleciona do banco de dados
			$sql =	"SELECT tbl_os.os                      ,
							tbl_os.sua_os                  ,
							tbl_os.serie                   ,
							tbl_os.codigo_fabricacao       ,
							tbl_produto.referencia         ,
							tbl_produto.descricao          ,
							tbl_produto.voltagem           ,
							tbl_posto_fabrica.codigo_posto ,
							tbl_os.tipo_atendimento        
					FROM	tbl_os
					JOIN	tbl_posto USING (posto)
					JOIN	tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
											  AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN	tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE	tbl_os.fabrica = $login_fabrica
					AND		tbl_os.posto   = $login_posto
					AND		tbl_os.sua_os ILIKE '".$sua_os."-%' 
					ORDER BY 1";
			$res = pg_exec($con, $sql);

			for ($i = 0 ; $i < pg_numrows($res) ; $i++)
			{
				$os                 = pg_result($res,$i,os);
				$sua_os             = pg_result($res,$i,sua_os);
				$referencia_produto = pg_result($res,$i,referencia);
				$produto_descricao  = pg_result($res,$i,descricao);
				$produto_voltagem   = pg_result($res,$i,voltagem);
				$produto_serie      = pg_result($res,$i,serie);

				$codigo_fabricacao  = pg_result($res,$i,codigo_fabricacao);
				$codigo_posto       = pg_result($res,0,codigo_posto);
				//HD 11419
				$tipo_atendimento   = pg_result($res,$i,tipo_atendimento);
?>
	<tr>
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $codigo_posto.$sua_os; ?></font>
		</td>
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $codigo_fabricacao ?></font>
		</td>
		<td align="center" nowrap>
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
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_serie ?></font>
		</td>
		<? if (strlen($tipo_atendimento) == 0) {?>
		<td align="center" nowrap>
			<a href='os_item.php?os=<? echo $os ?>' target='_blank'><img border='0' src='imagens/btn_lanca.gif'></a>
		</td>
		<? } ?>
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
