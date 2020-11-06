<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica == 1){
	include("os_revenda_explodida_blackedecker.php");
	exit;
}

include 'funcoes.php';

include_once '../anexaNF_inc.php';

$msg_erro = "";

if (strlen($_GET['sua_os']) > 0)  $sua_os = trim($_GET['sua_os']);
if (strlen($_POST['sua_os']) > 0) $sua_os = trim($_POST['sua_os']);

if (strlen($_GET['posto']) > 0)  $posto = trim($_GET['posto']);
if (strlen($_POST['posto']) > 0) $posto = trim($_POST['posto']);



if(strlen($sua_os) > 0){
	// seleciona do banco de dados
	$sql = "SELECT   to_char(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura  ,
					 to_char(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao ,
					 tbl_os.revenda_nome                         AS revenda_nome   ,
					 tbl_os.revenda_cnpj                         AS revenda_cnpj   ,
					 tbl_os.posto                                AS posto_codigo   ,
					 tbl_posto.nome                              AS posto_nome     ,
					 tbl_posto.cnpj                              AS posto_cnpj     ,
					 tbl_os.os                                   AS os_id
			FROM	 tbl_os
			JOIN	 tbl_posto
			ON		 tbl_os.posto = tbl_posto.posto
			WHERE	 tbl_os.sua_os LIKE '".$sua_os."-%'
			AND		 tbl_os.fabrica = $login_fabrica
			AND		 tbl_os.posto = $posto
			LIMIT	 1 ";
// 			exit(nl2br($sql));
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0){
		$data_abertura  = pg_fetch_result($res,0,'data_abertura');
		$data_digitacao = pg_fetch_result($res,0,'data_digitacao');
		$revenda_nome   = pg_fetch_result($res,0,'revenda_nome');
		$revenda_cnpj   = pg_fetch_result($res,0,'revenda_cnpj');
		$posto_nome     = pg_fetch_result($res,0,'posto_nome');
		$posto_cnpj     = pg_fetch_result($res,0,'posto_cnpj');
		$os_id          = pg_fetch_result($res,0,'os_id');
	}else{
		header('Location: os_revenda.php');
		exit;
	}
}


$title			= traduz("Ordem de Serviço Explodida - Revenda");
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
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz('Data Abertura')?></font>
					</td>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz('Data Digitação')?></font>
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
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz('CNPJ Revenda')?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz('Nome Revenda')?></font>
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
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz('CNPJ do Posto')?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz('Nome do Posto')?></font>
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
				<tr class="menu_top">
					<td colspan="2"><?=traduz('Anexos da OS')?></td>
				</tr>
				<tr>
					<td colspan="2"><?=temNF("$os_id", 'link')?></td>
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
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz('OS')?></font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz('Referência do Produto')?></font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz('Descrição do Produto')?></font></td>
		<? if(!in_array($login_fabrica, array(151))){ ?>
			<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz('Número de série')?></font></td>
		<? } if($login_fabrica == 162){ ?>
			<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif"> <?=traduz('IMEI ')?></font></td>
		<?php } if($login_fabrica == 94){ //hd_chamado=2705567 ?>
			<td align="center">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz('Defeito Reclamado')?></font>
			</td>
		<?php } ?>
	</tr>
<?
	// monta o FOR
	$qtde_item = 20;

		if ($sua_os){
			// seleciona do banco de dados
			$sql = "SELECT   tbl_os.os              AS os        ,
							 tbl_os.sua_os          AS sua_os    ,
							 tbl_os.serie           AS serie     ,
							 tbl_os.rg_produto,
							 tbl_produto.referencia AS referencia,
							 tbl_produto.descricao  AS descricao,
							 tbl_produto.produto 	AS produto
					FROM	 tbl_os
					JOIN	 tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE	 tbl_os.fabrica = $login_fabrica
					AND      tbl_os.posto   = $posto
					AND      tbl_os.sua_os LIKE '".$sua_os."-%' ";
			$res = pg_exec($con, $sql);

			for ($i=0; $i<pg_numrows($res); $i++){
				$os                 = pg_fetch_result($res,$i,'os');
				$sua_os             = pg_fetch_result($res,$i,'sua_os');
				$rg_produto         = pg_fetch_result($res,$i,'rg_produto');
				$referencia_produto = pg_fetch_result($res,$i,'referencia');
				$produto_descricao  = pg_fetch_result($res,$i,'descricao');
				$produto_serie      = pg_fetch_result($res,$i,'serie');
				$produto = pg_fetch_result($res, $i, 'produto');

				if($login_fabrica == 94){ //hd_chamado=2705567
					$sql_defeito = "SELECT tbl_os.defeito_reclamado_descricao
									FROM tbl_os
									WHERE tbl_os.produto = $produto
									AND tbl_os.fabrica = $login_fabrica
									AND tbl_os.posto   = $posto
									AND tbl_os.sua_os LIKE '".$sua_os."%' ";
					$res_defeito = pg_query($con, $sql_defeito);

					if(pg_last_error($con) > 0){ $msg_erro.="Erro na consulta do defeito"; }
					if(pg_num_rows($res_defeito) > 0){
						$defeito_reclamado = pg_fetch_result($res_defeito, 0, 'defeito_reclamado_descricao');
					}
				}
?>
	<tr>
		<td align="center">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><a href="os_press.php?os=<?=$os?>" target="_blank"><?=$sua_os?></a></font>
		</td>
		<td align="center">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $referencia_produto ?></font>
		</td>
		<td align="left">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_descricao ?></font>
		</td>
		<? if(!in_array($login_fabrica, array(151))){ ?>
		<td align="center">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_serie ?></font>
		</td>
		<? } if($login_fabrica == 162) { ?>
			<td align="center">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $rg_produto ?></font>
			</td>
		<?php }if($login_fabrica == 94){ //hd_chamado=2705567 ?>
			<td align="center">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					<?php echo $defeito_reclamado ?>
				</font>
			</td>
		<?php } ?>
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
