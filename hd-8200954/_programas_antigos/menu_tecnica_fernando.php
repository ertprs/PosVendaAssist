<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$sql =	"SELECT tbl_posto_fabrica.tipo_posto
		FROM    tbl_posto_fabrica
		WHERE   tbl_posto_fabrica.posto = $login_posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	$tipo_posto = trim(pg_result($res,0,tipo_posto));
}

if ($tipo_posto == "36" or $tipo_posto == 82 or $tipo_posto == 83 or $tipo_posto == 84) {
	header("Location: login.php");
	exit;
}


$title = "Menu de Comunicados e Informações Técnicas";
$layout_menu = "tecnica";
include 'cabecalho.php';


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
	font-size: 10px;
	font-weight: normal;
	text-align: justify;
}


/*========================== MENU ===================================*/

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



<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align='center'>
<!-- ================================================================== -->
<? if ($login_fabrica == 1) { ?>
<!--
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='http://www.blackdecker.com.br/eventos_bd.php' class='menu' target="_blank">Eventos e Treinamentos</a></td>
	<td class='descricao'>Centro de Treinamentos e Eventos</td>
</tr>
-->
<!-- ================================================================== -->
<!--
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='agendamento_blackedecker.php' class='menu'>Programação do Treinamento</a></td>
	<td class='descricao'>Agende e programe treinamentos</td>
</tr>
-->
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='comunicado_vista_explodida.php' class='menu'>Vista Explodida</a></td>
	<td class='descricao'>Mostra relação de peças e desenho da vista explodida dos produtos</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='peca_faltante.php' class='menu'>Informe a Black & Decker</a></td>
	<td class='descricao'>Informe a Black & Decker quais equipamentos estão parados em sua oficina por falta de peças</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 19) { ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='vistas_lorenzetti.php' class='menu'>Vista Explodida</a></td>
	<td class='descricao'>Mostra relação de peças e desenho da vista explodida dos produtos</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/marca25.gif'></td>
<?
	if ($login_fabrica == 1) {
?>
	<td nowrap width='260'><a href='comunicado_mostra_blackedecker.php' class='menu'>Comunicados / boletins / informativos</a><a href='comunicado_produto_consulta.php' class='menu'>Comunicados</a></td>
	<td nowrap class='descricao'>Apresenta os comunicados, boletins e informativos do Fabricante <br><br> Consulta dos comunicados cadastrados pela fábrica.</td>
<?
	}else{
		if($login_fabrica == 14){
	?>
			<td nowrap width='260'><a <? if($login_fabrica != 19){ ?>href='comunicado_mostra.php?tipo=Comunicado'<?}else{?> href='comunicado_mostra.php'<?}?> class='menu'>Comunicados </a><a href='comunicado_mostra.php?tipo=Boletim' class='menu'>Boletim  </a><a href='comunicado_mostra.php?tipo=Informativo' class='menu'>Informativo</a>
			<a href='comunicado_mostra.php?tipo=Lançamentos' class='menu'>Lançamentos </a></td>
			
	<?
		}else{
		?>
			<td nowrap width='260'> <a href='comunicado_produto_consulta.php' class='menu'>Comunicados</a> </td>
			<td nowrap class='descricao'>Consulta dos comunicados cadastrados pela fábrica.</td>
		<? } 
	}?>

</tr>
<?if( $login_fabrica == 14 ){?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='comunicado_mostra.php?tipo=Esquema Elétrico' class='menu'>Esquemas elétricos </a><a href='comunicado_mostra.php?tipo=Descritivo técnico' class='menu'>Descritivo técnico </a><a href='comunicado_mostra.php?tipo=Manual' class='menu'>Manual</a><a href='comunicado_mostra.php?tipo=Vista+Explodida' class='menu'>Vistas Explodidas</a>
	</td>
	<td nowrap class='descricao'>Exibição dos esquemas elétricos dos produtos</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<!-- 
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='http://www.telecontrol.com.br/assist/comunicado_mostra.php?tipo=Esquema+El%E9trico' class='menu'>Produtos</a></td>
	<td nowrap class='descricao'>Guia do usuário / caracteristicas técnicas dos produtos</td>
</tr>
 -->
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='procedimento_mostra.php' class='menu'>Procedimentos</a></td>
	<td nowrap class='descricao'>Apresenta os procedimentos do Fabricante</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='relatorio_peca.php' class='menu'>Relatório de Peças</a></td>
	<td class='descricao'>Relatório de 'De - Para', 'Peças Alternativas' e 'Peças Fora de Linha'</td>
</tr>

<?
$res = pg_exec ($con,"SELECT vista_explodida_automatica FROM tbl_fabrica WHERE fabrica = $login_fabrica");
if (pg_result ($res,0,0) == 't') {
?>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='vista_explodida_relatorio.php' class='menu'>Vista Explodida</a></td>
	<td nowrap class='descricao'>Mostra relação de peças e desenho da vista explodida dos produtos</td>
</tr>
<? } ?>

<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='forum.php' class='menu'>Fórum de interação</a></td>
	<td class='descricao'>Espaço reservado para enviar sua dúvidas e comentários para outros postos</td>
</tr>

<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='opiniao_posto.php' class='menu'>Pesquisa de Satisfação</a></td>
	<td class='descricao'>Responda a pesquisa de satisfação dos postos autorizados </td>
</tr>

<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='javascript:;' onclick="window.open('configuracao.php','janela','toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0')" class='menu'>Requisitos do sistema</a></td>
	<td class='descricao'>
		Para um melhor aproveitamento dos recursos do sistema, recomendamos o uso dos navegadores (browsers) : <br>
		<a href='javascript:;' onclick="window.open('configuracao.php','janela','toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0')">Internet Explorer 5.0 ou superior</a> ou 
		<a href='javascript:;' onclick="window.open('configuracao_ns.php','janela','toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0')">Netscape 4.0 ou superior.</a>
	</td>
</tr>

<!-- ================================================================== -->
<? if ($login_fabrica == 1) { ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='treinamento.php' class='menu'>Treinamentos</a></td>
	<td class='descricao'>Linhas de ferramentas Elétricas DeWalt , Hammer´s e Compressores</td>
</tr>
<? } ?>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>

</table>


<? include "rodape.php" ?>

</body>
</html>
