<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


$sql1 = "SELECT count (*) AS total_novo
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'novo'";


$res1 = @pg_exec ($con,$sql1);

if (@pg_numrows($res1) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_novo           = pg_result($res1,0,total_novo);
	}


$sql2 = "SELECT	 COUNT (*) AS total_analise
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'análise' ";

$res2 = @pg_exec ($con,$sql2);

if (@pg_numrows($res2) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_analise           = pg_result($res2,0,total_analise);
	}

$sql3 = "SELECT	 COUNT (*) AS total_aprovacao
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'aprovação'";

$res3 = @pg_exec ($con,$sql3);

if (@pg_numrows($res3) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_aprovacao           = pg_result($res3,0,total_aprovacao);
	}



$sql4 = "SELECT	 COUNT (*) AS total_resolvido
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'resolvido'";

$res4 = @pg_exec ($con,$sql4);

if (@pg_numrows($res4) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_resolvido           = pg_result($res4,0,total_resolvido);
	}


?>

<table width = '500' align = 'center' cellpadding='0' cellspacing='0'>
<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Estatística de Chamadas</b></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>
	<td nowrap colspan="9"></td>
	<td nowrap></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap ><CENTER>Novo: <B><? echo $total_novo ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap ><CENTER>Análise: <B><? echo $total_analise ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap><CENTER>Aprovação: <B><? echo $total_aprovacao ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap><CENTER>Resolvido: <B><? echo $total_resolvido ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap colspan="4"><B>Inserir Chamado</B></td>
	<td nowrap align="right" colspan="4"><B>Listar de Chamados</B></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</TR>
<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
</table>
<!-- ====================FIM DA TABELA DE ESTATISTICAS DE CHAMADAS=========================================   -->
