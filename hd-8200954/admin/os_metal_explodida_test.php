<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';


$msg_erro = "";

if (strlen($_GET['os_numero']) > 0)  $os_numero = trim($_GET['os_numero']);
if (strlen($_POST['os_numero']) > 0) $os_numero = trim($_POST['os_numero']);

if (strlen($os_numero)==0 ){
	header("Location: os_cadastro_metais_sanitario_cortesia.php");
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
		header('Location: os_cadastro_metais_sanitario_cortesia.php');
		exit;
	}
}


$title			= "ORDEM DE SERVIÇO EXPLODIDA - REVENDA"; 
$layout_menu	= "callcenter";

include "cabecalho.php";

?>

<style type="text/css">

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>

<br>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" class="formulario">
	<tr class='titulo_tabela' height='25'>
		<td colspan="5">
			Explosão de OS
		</td>
	</tr>
	<tr>
		<td>
			&nbsp;
		</td>
	</tr>

	<tr>
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">
			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
				
				<tr class="subtitulo" >
					<td nowrap  width="300px">
						Data Abertura
					</td>
					<td nowrap>
						Data Digitação
					</td>
				</tr>
				
				<tr>
					<td nowrap align='center'>
						<? echo $data_abertura ?>
					</td>
					<td nowrap align='center'>
						<? echo $data_digitacao ?>
					</td>
				</tr>
			</table>
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="subtitulo" width="300px">
					<td>
						CPF / CNPJ Cliente
					</td>
					<td>
						Nome Cliente
					</td>
				</tr>
				<tr>
					<td align='center'>
						<? echo $consumidor_cpf ?>
					</td>
					<td align='center'>
						<? echo $consumidor_nome ?>
					</td>
				</tr>
			</table>
<!--
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						CNPJ do Posto</font>
					</td>
					<td>
						Nome do Posto</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<? echo $posto_cnpj ?></font>
					</td>
					<td align='center'>
						<? echo $posto_nome ?></font>
					</td>
				</tr>
			</table>
-->
		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>
<br>
<table width="700" border="0" cellpadding="3" cellspacing="1" align="center" class='tabela'>

	<tr class='titulo_coluna'>
		<td align="center">OS</font></td>
		<td align="center">Referência do Produto</font></td>
		<td align="center">Descrição do Produto</font></td>
		<td align="center">Defeito<br>Reclamado</font></td>
		<td align="center">Ação</td>
	</tr>
<?
	$qtde_item = 20;

	if (strlen($os_numero)>0){
		$sql = "SELECT   tbl_os.os                             AS os,
						 tbl_os.sua_os                         AS sua_os,
						 tbl_produto.referencia                AS referencia,
						 tbl_produto.descricao                 AS descricao ,
						 tbl_os.tipo_atendimento                            ,
						 tbl_defeito_reclamado.descricao       AS defeito_reclamado
				FROM	 tbl_os
				LEFT JOIN tbl_os_extra USING(os)
				JOIN tbl_produto ON		 tbl_produto.produto = tbl_os.produto
				JOIN tbl_defeito_reclamado USING(defeito_reclamado)
				WHERE	 tbl_os.fabrica = $login_fabrica
				AND      tbl_os.os_numero = ".$os_numero;
		$res = pg_exec($con, $sql);

		for ($i=0; $i<pg_numrows($res); $i++) {
			$os                          = pg_result($res,$i,os);
			$sua_os                      = pg_result($res,$i,sua_os);
			$referencia_produto          = pg_result($res,$i,referencia);
			$produto_descricao           = pg_result($res,$i,descricao);
			$defeito_reclamado           = pg_result($res,$i,defeito_reclamado);
			$tipo_atendimento   = pg_result($res,$i,tipo_atendimento);
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
?>
	<tr bgcolor="<?echo $cor?>">
		<td align="center" nowrap >
			<? echo $sua_os ?></font>
		</td>
		<td align="center" nowrap >
			<? echo $referencia_produto ?></font>
		</td>
		<td align="left">
			<? echo $produto_descricao ?></font>
		</td>
		<td align="left">
			<? echo $defeito_reclamado ?></font>
		</td>
		<td align="center" nowrap>
			<input type='button' value='Lançar Itens' onclick="window.location='os_cortesia_item.php?os=<? echo $os ?>'">
		</td>
	</tr>
<?
			}
		}
?>
	<table align='center' width='700px'>
	<TR align='center' valign='middle' border='0'>
		<TD colspan="8">
			<br>
			
				<input type='button' value='Imprimir'  onclick="javascript: window.open('os_print_metal.php?os_revenda=<? echo $os_numero; ?>','os_metal');">				
		</TD>
	</TR>
	</table>
</table>
</form>
<br>

<? include 'rodape.php'; ?>