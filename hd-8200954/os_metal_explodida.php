<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


include 'funcoes.php';

$msg_erro = "";

if (strlen($_GET['os_numero']) > 0)  $os_numero = trim($_GET['os_numero']);
if (strlen($_POST['os_numero']) > 0) $os_numero = trim($_POST['os_numero']);

if (strlen($os_numero)==0 OR strlen($posto)==0){
	header("Location: os_cadastro_metais_sanitarios_new.php");
	exit;
}

if(strlen($os_numero) > 0){
	// seleciona do banco de dados
	$sql = "SELECT  to_char(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura  ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao ,
					tbl_os.revenda_nome                         AS revenda_nome   ,
					tbl_os.revenda_cnpj                         AS revenda_cnpj   ,
					tbl_os.consumidor_cpf                       AS consumidor_cpf ,
					tbl_os.consumidor_nome                      AS consumidor_nome,
					tbl_os.posto                                AS posto_codigo   ,
					tbl_posto.nome                              AS posto_nome     ,
					tbl_posto.cnpj                              AS posto_cnpj     
			FROM	tbl_os
			JOIN	tbl_posto
			ON		tbl_os.posto = tbl_posto.posto
			WHERE	tbl_os.os_numero = ".$os_numero."
			AND		tbl_os.fabrica = $login_fabrica 
			AND		tbl_os.posto   = $login_posto 
			LIMIT	1 ";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$data_abertura  = pg_result($res,0,data_abertura);
		$data_digitacao = pg_result($res,0,data_digitacao);
		$revenda_nome   = pg_result($res,0,revenda_nome);
		$revenda_cnpj   = pg_result($res,0,revenda_cnpj);
		$consumidor_cpf = pg_result($res,0,consumidor_cpf);
		$consumidor_nome= pg_result($res,0,consumidor_nome);
		$posto_nome     = pg_result($res,0,posto_nome);
		$posto_cnpj     = pg_result($res,0,posto_cnpj);
	}else{
		header('Location: os_cadastro_metais_sanitarios_new.php');
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
			</table>
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF / CNPJ Cliente</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Cliente</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $consumidor_cpf ?></font>
					</td>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $consumidor_nome ?></font>
					</td>
				</tr>
			</table>
<!--
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
-->
		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>
<br>
<table width="700" border="0" cellpadding="2" cellspacing="3" align="center" bgcolor="#ffffff">
	<tr class="menu_top">
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Referência do Produto</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Descrição do Produto</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número de série</font></td>
		<?
		if($login_fabrica <> 1){
		?>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Capacidadee</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Regulagem <br>Peso Padrão</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Certificado<br>Conformidade</font></td>
		<?
		}
		?>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito<br>Reclamado</font></td>
		<?
		if($login_fabrica ==1){
		?>
			<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Ações</font></td>
		<?
		}
		?>
	</tr>
<?
	$qtde_item = 20;

	if (strlen($os_numero)>0){
		$sql = "SELECT   tbl_os.os                             AS os,
						 tbl_os.sua_os                         AS sua_os,
						 tbl_os.serie                          AS serie,
						 tbl_os.capacidade                     AS capacidade,
						 tbl_os_extra.regulagem_peso_padrao    AS regulagem_peso_padrao,
						 tbl_os_extra.certificado_conformidade AS certificado_conformidade,
						 tbl_os.defeito_reclamado              AS defeito_reclamado,
						 tbl_os.defeito_reclamado_descricao    AS defeito_reclamado_descricao,
						 tbl_produto.referencia                AS referencia,
						 tbl_produto.descricao                 AS descricao ,
						 tbl_os.tipo_atendimento        
				FROM	 tbl_os
				LEFT JOIN tbl_os_extra USING(os)
				JOIN	 tbl_produto
				ON		 tbl_produto.produto = tbl_os.produto
				WHERE	 tbl_os.fabrica = $login_fabrica
				AND      tbl_os.posto   = $login_posto
				AND      tbl_os.os_numero = ".$os_numero;
		$res = pg_exec($con, $sql);

		for ($i=0; $i<pg_numrows($res); $i++) {
			$os                          = pg_result($res,$i,os);
			$sua_os                      = pg_result($res,$i,sua_os);
			$referencia_produto          = pg_result($res,$i,referencia);
			$produto_descricao           = pg_result($res,$i,descricao);
			$produto_serie               = pg_result($res,$i,serie);
			$capacidade                  = pg_result($res,$i,capacidade);
			$regulagem_peso_padrao       = pg_result($res,$i,regulagem_peso_padrao);
			$certificado_conformidade    = pg_result($res,$i,certificado_conformidade);
			$defeito_reclamado           = pg_result($res,$i,defeito_reclamado);
			$defeito_reclamado_descricao = pg_result($res,$i,defeito_reclamado_descricao);
			$tipo_atendimento   = pg_result($res,$i,tipo_atendimento);
?>
	<tr>
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $sua_os ?></font>
		</td>
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $referencia_produto ?></font>
		</td>
		<td align="left">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_descricao ?></font>
		</td>
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_serie ?></font>
		</td>
		<?
		if($login_fabrica <> 1){	
		?>
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $capacidade ?></font>
		</td>
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $regulagem_peso_padrao ?></font>
		</td>
		<td align="center" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $certificado_conformidade ?></font>
		</td>
		<?
		}
		?>
		<td align="left">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $defeito_reclamado_descricao ?></font>
		</td>
		<? 
		if ($login_fabrica ==1) {?>
		<td align="center" nowrap>
			<a href='os_item.php?os=<? echo $os ?>' target='_blank'><img border='0' src='imagens/btn_lanca.gif'></a>
		</td>
		<? } ?>
	</tr>
<?
			}
		}
?>
	<TR>
		<TD colspan="8">
			<br>
			<center>
				<img src='imagens/btn_imprimir.gif' onclick="javascript: window.open('os_print_metal.php?os_revenda=<? echo $os_numero; ?>','os_metal');" ALT="Imprimir" border='0' style="cursor:pointer;">
				
			</center>
		</TD>
	</TR>
</table>
</form>
<br>

<? include 'rodape.php'; ?>