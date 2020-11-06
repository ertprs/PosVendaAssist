<? 
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$menu_cor_fundo="EEEEEE";
$menu_cor_linha="BBBBBB";


if($_GET['conteudo'])  $conteudo  = $_GET['conteudo']; 
//echo $conteudo."<br>".$ajuda; 

//SELECT DA TABELA DE ESTATISTICAS DE CHAMADAS---------------------------

$sql1 = "SELECT count (*) AS total_novo
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'novo'
	AND admin=$login_admin";
//echo "$sql1";

$res1 = @pg_exec ($con,$sql1);

if (@pg_numrows($res1) > 0) {//PEGA OS DADOS DE QUEM EST� ABRINDO O CHAMADO
	$total_novo           = pg_result($res1,0,total_novo);
	}


$sql2 = "SELECT	 COUNT (*) AS total_analise
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'an�lise' 
	AND admin=$login_admin";

$res2 = @pg_exec ($con,$sql2);

if (@pg_numrows($res2) > 0) {//PEGA OS DADOS DE QUEM EST� ABRINDO O CHAMADO
	$total_analise           = pg_result($res2,0,total_analise);
	}



$sql3 = "SELECT	 COUNT (*) AS total_aprovacao
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'aprova��o'
	AND admin=$login_admin";

$res3 = @pg_exec ($con,$sql3);

if (@pg_numrows($res3) > 0) {//PEGA OS DADOS DE QUEM EST� ABRINDO O CHAMADO
	$total_aprovacao           = pg_result($res3,0,total_aprovacao);
	}



$sql4 = "SELECT	 COUNT (*) AS total_resolvido
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'resolvido'
	AND admin=$login_admin";

$res4 = @pg_exec ($con,$sql4);

if (@pg_numrows($res4) > 0) {//PEGA OS DADOS DE QUEM EST� ABRINDO O CHAMADO
	$total_resolvido           = pg_result($res4,0,total_resolvido);
	}

//FIM DO SELECT DA TABELA ESTATISTICAS DE CHAMADAS---------------------------------

?>

<html>
<head>
<title>Telecontrol - Help Desk</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<body>
<?
include "menu.php";
?>
		<table width="98%" align="center">
			<tr>
				<td colspan="2" bgcolor="<?=$menu_cor_linha?>" width="1" height="1"></td>
			</tr>
			<tr>
				<td class="Titulo"><img src="imagem/help.png" width="32" height="32"border='0'align='absmiddle'> HOME</td>
			</tr>
			<tr>
				<td colspan="2" bgcolor="<?=$menu_cor_linha?>" width="1" height="1"></td>
			</tr>
			<tr>
				<td class="Titulo_sub" align="center">Seja bem-vindo ao sistema de Help Desk. Esta � uma ferramenta de suporte, exclusiva para clientes da Telecontrol Assist</td>
			</tr>
			
			<tr>
				<td><div align="justify">
					
					<br>
					<dd>O <b>Help Desk</b> � uma de ferramenta de atendimento ao usu�rio do sistema em que h� uma Equipe de Suporte T�cnico especializada no esclarecimento de d�vidas, solicita��es de servi�os, tais como cria��es e altera��es de telas do Sistema Assist. Atua no levantamento de problemas referentes ao sistema Assist, abrindo chamados e encaminhando � Equipe de Tecnologia para resolu��o dos mesmos.<br> 
					<dd>Estes chamados encaminhados para a Equipe de Tecnologia possuem um tempo determinado para resolu��o e uma prioridade, tendo com isso a inten��o de organizar e resolver os chamados da melhor maneira poss�vel.<br> 
					<dd>Sua estrutura � composta por atendentes qualificados para esclarecer qualquer tipo de d�vida referente ao sistema Assist.
					
					</div>
				</td>
			</tr>
			<tr>
				<td><br></td>
<!-- ====================INICIO DA TABELA DE ESTATISTICAS DE CHAMADAS=========================================   -->
<?
$sql1 = "SELECT count (*) AS total_novo
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'novo'";


$res1 = @pg_exec ($con,$sql1);

if (@pg_numrows($res1) > 0) {//PEGA OS DADOS DE QUEM EST� ABRINDO O CHAMADO
	$total_novo           = pg_result($res1,0,total_novo);
	}


$sql2 = "SELECT	 COUNT (*) AS total_analise
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'an�lise' ";

$res2 = @pg_exec ($con,$sql2);

if (@pg_numrows($res2) > 0) {//PEGA OS DADOS DE QUEM EST� ABRINDO O CHAMADO
	$total_analise           = pg_result($res2,0,total_analise);
	}

$sql3 = "SELECT	 COUNT (*) AS total_aprovacao
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'aprova��o'";

$res3 = @pg_exec ($con,$sql3);

if (@pg_numrows($res3) > 0) {//PEGA OS DADOS DE QUEM EST� ABRINDO O CHAMADO
	$total_aprovacao           = pg_result($res3,0,total_aprovacao);
	}



$sql4 = "SELECT	 COUNT (*) AS total_resolvido
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'resolvido'";

$res4 = @pg_exec ($con,$sql4);

if (@pg_numrows($res4) > 0) {//PEGA OS DADOS DE QUEM EST� ABRINDO O CHAMADO
	$total_resolvido           = pg_result($res4,0,total_resolvido);
	}


$sql5 = "SELECT	 COUNT (*) AS total_cancelado
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'cancelado'";

$res5 = @pg_exec ($con,$sql5);

if (@pg_numrows($res5) > 0) {//PEGA OS DADOS DE QUEM EST� ABRINDO O CHAMADO
	$total_cancelado           = pg_result($res5,0,total_cancelado);
	}


$sql6 = "SELECT	 COUNT (*) AS total_Execu��o
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'Execu��o'";

$res6 = @pg_exec ($con,$sql6);

if (@pg_numrows($res6) > 0) {//PEGA OS DADOS DE QUEM EST� ABRINDO O CHAMADO
	$total_Execu��o           = pg_result($res6,0,total_Execu��o);
	}



?>

<table width = '700' align = 'center' cellpadding='0' cellspacing='0'>
<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Estat�stica de Chamadas</b></td>
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
	<td nowrap ><CENTER>An�lise: <B><? echo $total_analise ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap><CENTER>Aprova��o: <B><? echo $total_aprovacao ?></B></CENTER></td>
	<td nowrap><CENTER>Resolvido: <B><? echo $total_resolvido ?></B></CENTER></td>
	<td nowrap><CENTER>Execu��o: <B><? echo $total_Execu��o ?></B></CENTER></td>
	<td nowrap><CENTER>Cancelado: <B><? echo $total_cancelado ?></B></CENTER></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap colspan="4"><a href='chamado_detalhe.php'><img src="imagem/01.jpg" width="32" height="32"border='0'><B>INSERIR CHAMADO</B></a></td>
	<td nowrap align="right" colspan="4"><a href='chamado_lista.php'><img src="imagem/01.jpg" width="32" height="32"border='0'><B>LISTAR CHAMADO</B></a></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</TR>
<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
</table>

<!-- ====================FIM DA TABELA DE ESTATISTICAS DE CHAMADAS=========================================   -->
			</tr>
		</table>
		<BR><BR><BR><BR>
<? include "rodape.php" ?>
</body>
</html>