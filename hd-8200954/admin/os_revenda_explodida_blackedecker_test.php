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

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<br>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center"  class="formulario">
	<tr class='titulo_tabela'><td colspan='3'>Informações da OS Explodida - Revenda</td></tr>
	<tr>
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="subtitulo">
					<td nowrap>
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
				<tr class="subtitulo">
					<td>
						CNPJ Revenda
					</td>
					<td>
						Nome Revenda
					</td>
				</tr>
				<tr>
					<td align='center'>
						<? echo $revenda_cnpj ?>
					</td>
					<td align='center'>
						<? echo $revenda_nome ?>
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="subtitulo">
					<td>
						CNPJ do Posto
					</td>
					<td>
						Nome do Posto
					</td>
				</tr>
				<tr>
					<td align='center'>
						<? echo $posto_cnpj ?>
					</td>
					<td align='center'>
						<? echo $posto_nome ?>
					</td>
				</tr>
			</table>

		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>
<br />
<table width="700" border="0" cellpadding="2" cellspacing="1" align="center" class='tabela'>

	<tr class="titulo_coluna">
		<td align="center" nowrap>OS</td>
		<td align="center" nowrap>Código Fabricação</td>
		<td align="center" nowrap>Referência do Produto</td>
		<td align="center" nowrap>Descrição do Produto</td>
		<td align="center" nowrap>Número de série</td>
		<td align="center" nowrap>Ações</td>
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

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
?>
	<tr bgcolor="<?php echo $cor; ?>">
		<td align="center" nowrap>
			<? echo $codigo_posto.$sua_os; ?>
		</td>
		<td align="center" nowrap>
			<? echo $codigo_fabricacao; ?>
		</td>
		<td align="center" nowrap>
			<? echo $referencia_produto ?>
		</td>
		<td align="left" nowrap>
			<? echo $produto_descricao ?>
		</td>
		<td align="center" nowrap>
			<? echo $produto_serie ?>
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