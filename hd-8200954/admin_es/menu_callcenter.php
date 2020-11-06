<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

$layout_menu = "callcenter";
$title = "Menu Call-Center";
include 'cabecalho.php';
echo "DESATIVADO";EXIT;
?>

<style type="text/css">

body {
	text-align: center;
}

.cabecalho {
	color: black;
	border-bottom: 2px dotted WHITE;
	font-size: 12px;
	font-weight: bold;
}

.descricao {
	padding: 5px;
	color: black;
	font-size: 12px;
	font-weight: normal;
	text-align: justify;
}

a:link.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:visited.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:hover.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: black;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
	background-color: #ced7e7;
}

</style>

<br>

<? if ($login_fabrica != 14) { ?>

<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<tr>
	<td width='10'><img border="0" src="imagens/corner_se_laranja.gif"></td>
	<td class="cabecalho">CALL-CENTER</TD>
	<td width='10'><img border="0" src="imagens/corner_sd_laranja.gif"></td>
</tr>
</table>
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='callcenter_cadastro_1.php' class='menu'>Cadastra Atendimento Call-Center</a></td>
	<td nowrap class='descricao'>Cadastro de atendimento do Call-Center</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='callcenter_parametros.php' class='menu'>Consulta Atendimentos Call-Center</a></td>
	<td nowrap class='descricao'>Consulta atendimentos j� lan�ados</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='callcenter_pendencias.php' class='menu'>Consulta Atendimentos Pendentes</a></td>
	<td nowrap class='descricao'>Exibe todos os atendimentos do Call-Center com pend�ncia.</td>
</tr>
<!-- ================================================================== -->
<? if ($login_fabrica == 6) { ?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='callcenter_manutencao.php' class='menu'>Manuten��o de Call-Center</a></td>
	<td nowrap class='descricao'>Altere o produto ou defeito reclamado cadastrado no Call-Center.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan="3"><img src="imagens/spacer.gif" height="3"></td>
</tr>
</table>
<br>

<? } ?>

<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF'  align = 'center'>
<tr>
	<td width="10"><img border="0" src="imagens/corner_se_laranja.gif"></td>
	<td class="cabecalho">ORDENS DE SERVI�O</td>
	<td width="10"><img border="0" src="imagens/corner_sd_laranja.gif"></td>
</tr>
</table>
<table border="0" width="700px" border="0" cellpadding="0" cellspacing="0" align = 'center'>
<? if ($login_fabrica != 14 OR ($login_admin == '260' OR $login_admin == '261' OR $login_admin == '262' OR $login_admin == '263')) {  // usuarios da Intelbras q podem digitar OS e Pedido?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='os_cadastro.php' class='menu'>Cadastra Ordens de Servi�o</a></td>
	<td nowrap class='descricao'>Cadastro de Ordem de Servi�os, no modo ADMIN</td>
</tr>
<? } 
if($login_fabrica==20){
?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='aprova_os_troca.php' class='menu'>Troca de Produto na OS</a></td>
	<td nowrap class='descricao'>Cadastro da troca de produto na OS</td>
</tr>
<?}?>
<!-- ================================================================== -->
<? if ($login_fabrica == 3 AND 1==2) { ?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='<? if ($login_fabrica == 1) echo "os_consumidor_consulta.php"; else echo "os_parametros.php"; ?>' class='menu'>Consulta ANTIGA</a></td>
	<td class='descricao'>Liberado at� �s 15 horas de hoje. Problemas de performance no site est�o relacionados com pesquisas muito extensas.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='<? if ($login_fabrica == 1) echo "os_consumidor_consulta.php"; else echo "os_consulta_lite.php"; ?>' class='menu'>Consulta Ordens de Servi�o</a></td>
	<td nowrap class='descricao'>Consulta OS Lan�adas</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_parametros_excluida.php' class='menu'>Consulta OS Exclu�da</a></td>
	<td nowrap class='descricao'>Consulta Ordens de Servido exclu�das do sistema</td>
</tr>
<!-- ================================================================== -->

<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_consulta_procon.php' class='menu'>Consulta OS Procom</a></td>
	<td nowrap class='descricao'>Consulta Ordens de Servido do Procon</td>
</tr>

<!-- ================================================================== -->
<? if ($login_fabrica == 19) { ?>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_consulta_sac.php' class='menu'>Consulta OS SAC</a></td>
	<td nowrap class='descricao'>Consulta Ordens de Servido do SAC</td>
</tr>
<tr bgcolor="#FAFAFA">
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='defeito_os_parametros.php' class='menu'>Relat�rio de Ordens de Servi�o</a></td>
	<td class='descricao'>Relat�rio de Ordens de Servi�o lan�adas no sistema.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 1) { ?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_cortesia_cadastro.php' class='menu'>Cadastro Cortesia Ordens de Servi�o</a></td>
	<td nowrap class='descricao'>Cadastro de Cortesia de Ordem de Servi�os, no modo ADMIN</td>
</tr>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_cortesia_parametros.php' class='menu'>Consulta Cortesia Ordens de Servi�o</a></td>
	<td nowrap class='descricao'>Consulta OS Cortesia Lan�adas</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<?
if ($login_fabrica == 6){
?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_relatorio_aberta.php' class='menu'>Consulta OS Aberta</a></td>
	<td nowrap class='descricao'>Consulta OS aberta a mais de 10 dias</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<?
if ($login_fabrica == 6){
?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_fechamento.php' class='menu'>Fechamento de Ordem de Servi�o</a></td>
	<td nowrap class='descricao'>Fechamento das Ordens de Servi�os</td>
</tr>
<!-- ================================================================== -->
<?
}
if ($login_fabrica == 7){
?>
<!-- ================================================================== -->
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='os_manutencao.php' class='menu'>OS de Manuten��o</a></td>
	<td class='descricao'>Lan�amento de OS de Manuten��o, com v�rios equipamentos por OS.</td>
</tr>
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='os_filizola_relatorio.php' class='menu'>Faturamento - Valores da OS</a></td>
	<td class='descricao'>Consulta as OS com valores</td>
</tr>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='os_faturamento_filizola.php' class='menu'>Lotes de OS</a></td>
	<td class='descricao'>Lan�amento de Lotes de OS</td>
</tr>
<?
}
?>
<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<br>

<? if ($login_fabrica != 14) { ?>
<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<tr>
	<td width='10'><img border="0" src="imagens/corner_se_laranja.gif"></td>
	<td class="cabecalho">REVENDAS - ORDENS DE SERVI�O</TD>
	<td width='10'><img border="0" src="imagens/corner_sd_laranja.gif"></td>
</tr>
</table>
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='os_revenda.php' class='menu'>Cadastra OS - REVENDA</a></td>
	<td nowrap class='descricao'>Cadastro de Ordem de Servi�os de revenda</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_revenda_parametros.php' class='menu'>Consulta OS - REVENDA</a></td>
	<td nowrap class='descricao'>Consulta OS Revenda Lan�adas</td>
</tr>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<br>
<? } ?>

<? if ($login_fabrica == 1) { ?>
<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<tr> 
	<td width='10'><img border="0" src="imagens/corner_se_laranja.gif"></td>
	<td class="cabecalho">SEDEX - ORDENS DE SERVI�O</TD>
	<td width='10'><img border="0" src="imagens/corner_sd_laranja.gif"></td>
</tr>
</table>
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='sedex_cadastro.php' class='menu'>Cadastra OS SEDEX</a></td>
	<td nowrap class='descricao'>Cadastro de Ordem de Servi�os de Sedex</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='sedex_parametros.php' class='menu'>Consulta OS SEDEX</a></td>
	<td nowrap class='descricao'>Consulta OS Sedex Lan�adas</td>
</tr>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<br>
<? } ?>

<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<tr>
	<td width='10'><img border="0" src="imagens/corner_se_laranja.gif"></td>
	<td class="cabecalho">PEDIDOS DE PE�AS</td>
	<td width='10'><img border="0" src="imagens/corner_sd_laranja.gif"></td>
</tr>
</table>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<? 
	if ($login_fabrica <> 1 OR ($login_admin == 232 OR $login_admin == 245)) {  // duas usuarias da Black&Decker
		if ($login_fabrica != 14 OR ($login_admin == '260' OR $login_admin == '261' OR $login_admin == '262' OR $login_admin == '263')) { ?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='pedido_cadastro.php' class='menu'>Cadastro de Pedidos</a></td>
	<td nowrap class='descricao'>Cadastra pedidos de pe�as</td>
</tr>
<? if($login_fabrica==3){?>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='nf_relacao_britania.php' class='menu'>NF's de Pedidos</a></td>
	<td nowrap class='descricao'>Listar as Notas Fiscais dos Postos Autorizados</td>
</tr>
<?
			}
		}
	}
?>

<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='pedido_parametros.php' class='menu'>Consulta Pedidos de Pe�as</a></td>
	<td nowrap class='descricao'>Consulta pedidos efetuados por postos autorizados.</td>
</tr>

<!-- ================================================================== -->

<? if ($login_fabrica == 1) { ?>
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='pedido_parametros_blackedecker_acessorio.php' class='menu'>Consulta Pedidos de Acess�rios</a></td>
	<td nowrap class='descricao'>Consulta pedidos de Acess�rios efetuados por PA autorizados.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='faturamento_importa_blackedecker.php' class='menu'>Importar Faturamento</a></td>
	<td nowrap class='descricao'>Importa��o dos arquivos de faturamento (retorno).</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<br>

<? if($login_fabrica == 14) {?>
<br>
<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<tr>
	<td width='10'><img border="0" src="imagens/corner_se_laranja.gif"></td>
	<td class="cabecalho">INFORMA��ES SOBRE PE�AS </td>
	<td width='10'><img border="0" src="imagens/corner_sd_laranja.gif"></td>
</tr>
</table>
<!-- ================================================================== -->
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='peca_consulta_dados.php' class='menu'>Dados Cadastrais da Pe�a</a></td>
	<td nowrap class='descricao'>Consulta todos os dados cadastrais da pe�a.</td>
</tr>
</table>
<?}?>


<? if ($login_fabrica != 14) { ?>
<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<tr>
	<td width='10'><img src="imagens/corner_se_laranja.gif"></td>
	<td class="cabecalho">DIVERSOS</TD>
	<td width='10'><img src="imagens/corner_sd_laranja.gif"></td>
</tr>
</table>
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<? if ($login_fabrica <> 2){ ?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='posto_login.php' class='menu'>Logar como Posto</a></td>
	<td nowrap class='descricao'>Acesse o sistema como se fosse o posto autorizado</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='posto_consulta.php' class='menu'>Consulta Postos</a></td>
	<td nowrap class='descricao'>Consulta cadastro de postos autorizados.</td>
</tr>
<!-- ================================================================== -->
<?if ($login_fabrica <> 1) {?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='preco_consulta.php' class='menu'>Tabela de Pre�os</a></td>
	<td nowrap class='descricao'>Consulta tabela de pre�os de pe�as</td>
</tr>
<? }else{ ?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='tabela_precos_blackedecker_consulta.php' class='menu'>Tabela de Pre�os</a></td>
	<td nowrap class='descricao'>Consulta tabela de pre�os de pe�as</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='lbm_consulta.php' class='menu'>Lista B�sica</a></td>
	<td nowrap class='descricao'>Consulta lista b�sica de pe�as por produto.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='linha_consulta.php' class='menu'>Linhas de produtos</a></td>
	<td nowrap class='descricao'>Consulta as linhas de produtos</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='produto_consulta.php' class='menu'>Produtos</a></td>
	<td nowrap class='descricao'>Consulta os produtos cadastrados.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='depara_consulta.php' class='menu'>DE->PARA</a></td>
	<td nowrap class='descricao'>Consulta PE�AS com DE->PARA</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='peca_fora_linha_consulta.php' class='menu'>Pe�as fora de linha</a></td>
	<td nowrap class='descricao'>Consulta as PE�AS que est�o fora de linha.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='comunicado_produto_consulta.php' class='menu'>Vista Explodida e Comunicados</a></td>
	<td nowrap class='descricao'>Consulta vista explodida, diagramas, esquemas e comunicados.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='peca_consulta_dados.php' class='menu'>Dados Cadastrais da Pe�a</a></td>
	<td nowrap class='descricao'>Consulta todos os dados cadastrais da pe�a.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_sem_pedido.php' class='menu'>OS n�o geraram pedidos</a></td>
	<td nowrap class='descricao'>Ordens de Servi�os que n�o geraram pedidos de pe�as.</td>
</tr>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<br>
<? } ?>

<? if ($login_fabrica != 14) { ?>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
	<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
	<TD class=cabecalho>RELAT�RIOS CALL-CENTER</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_callcenter_reclamacao_por_estado.php' class='menu'>Reclama��es por estado</a></td>
	<td nowrap class='descricao'>Hist�rico de atendimentos por estado.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_callcenter_produto.php' class='menu'>Relat�rio de Produtos</a></td>
	<td nowrap class='descricao'>Hist�rico de atendimentos por m�s.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<br>
<? } ?>

<? if ($login_fabrica == 3) { ?>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
	<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
	<TD class='cabecalho'>GERENCIAMENTO DE REVENDAS</TD>
	<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor="#FAFAFA">
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='os_revenda_pesquisa.php' class='menu'>Pesquisa de OS Revenda</a></td>
	<td class='descricao'>Pesquisa as OS em aberto em uma revenda, pelo seu CNPJ.</td>
</tr>
<tr bgcolor="#f0f0f0">
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_os_revenda.php'
class='menu'>OS em Aberto por Revenda</a></td>
	<td class='descricao'>Relat�rio com Ordens de Servi�os em aberto, listando
pelas 20 maiores revendas que abriram Ordens de Servi�os.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<br>
<? } ?>

<? include "rodape.php" ?>
