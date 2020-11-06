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


$title = "Menu de Ordens de Serviço";
$layout_menu = "os";
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








<?
	if ($login_fabrica == 11) {
		##### OS SEM DATA DE FECHAMENTO HÁ 15 DIAS OU MAIS DA DATA DE ABERTURA #####
		$sql =	"SELECT tbl_os.os                                                  ,
						tbl_os.sua_os                                              ,
						LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
						tbl_produto.referencia                                     ,
						tbl_produto.descricao                                      ,
						tbl_produto.voltagem
				FROM tbl_os
				JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.posto   = $login_posto
				AND   (tbl_os.data_abertura + INTERVAL '15 days') <= current_date
				AND   tbl_os.data_fechamento IS NULL
				ORDER BY os_ordem";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<BR>";
			echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align = 'center'>";
			echo "<tr class='Titulo' height='15' bgcolor='#FF3300'>";
			echo "<td colspan='3' ><B>
			&nbsp;OS SEM DATA DE FECHAMENTO A 15 DIAS OU MAIS DA DATA DE ABERTURA&nbsp;
			&nbsp;Perigo de PROCON conforme artigo 18 do C.D.C.</B></td>";
			echo "</tr>";
			echo "<tr class='Titulo' height='15' bgcolor='#FF3300'>";
			echo "<td>OS</td>";
			echo "<td>ABERTURA</td>";
			echo "<td>PRODUTO</td>";
			echo "</tr>";
			for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
				$os               = trim(pg_result($res,$a,os));
				$sua_os           = trim(pg_result($res,$a,sua_os));
				$abertura         = trim(pg_result($res,$a,abertura));
				$referencia       = trim(pg_result($res,$a,referencia));
				$descricao        = trim(pg_result($res,$a,descricao));
				$produto_completo = $referencia . " - " . $descricao;

				$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
				echo "<td align='center'>" . $sua_os . "</td>";
				echo "<td align='center'>" . $abertura . "</td>";
				echo "<td nowrap><acronym title='Referência: $referencia\nDescrição: $descricao' style='cursor:help;'>" . substr($produto_completo,0,30) . "</acronym></td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br>";
		}
		##### OS SEM DATA DE FECHAMENTO HÁ 15 DIAS OU MAIS DA DATA DE ABERTURA #####
	}
?>










<? 
	if ($login_fabrica == 3) {
		echo "<td>";
		#------------------------ Média de Peças por OS   e  Custo Médio por OS --------------
		include "custo_medio_include.php";
		echo "</td>";
	}
?>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align='center'>
<!-- ================================================================== -->
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='os_cadastro.php' class='menu'>Abertura de Ordem de Serviço</a></td>
	<td nowrap class='descricao'>Clique aqui para incluir uma nova ordem de serviço</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#F0F0F0'>
	<td><img src='imagens/tela25.gif'></td>
<!--<td><a href='os_parametros.php' class='menu'>Consulta de Ordem de Serviço</a></td>-->
	<td><a href='os_consulta_lite.php' class='menu'>Consulta de Ordem de Serviço</a></td>
	<td class='descricao'>Ordens de serviços para consulta, impressão ou lançamento de ítens</td>
</tr>
<tr bgcolor='#FAFAFA'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='os_fechamento.php' class='menu'>Fechamento de Ordem de Serviço</a></td>
	<td class='descricao'>Fechamento das Ordens de Serviços</td>
</tr>
<!-- ================================================================== -->
<? if ($login_fabrica == 1){ ?>
<tr bgcolor='#FAFAFA'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='os_cortesia_parametros.php' class='menu'>Consulta de Ordem de Serviço Cortesia</a></td>
	<td class='descricao'>Ordens de serviços cortesia para consulta, impressão ou lançamento de ítens</td>
</tr>
<? } ?>
<tr bgcolor='#F0F0F0'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='os_relatorio.php' class='menu'>Status da Ordem de Serviço</a></td>
	<td class='descricao'>Status das ordens de serviços</td>
</tr>
<!-- ================================================================== -->
<? if ($login_fabrica == 7) { ?>
<tr bgcolor='#F0F0F0'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='os_manutencao_parametros.php' class='menu'>Consulta de OS de Manutenção</a></td>
	<td class='descricao'>Ordens de serviços de Manutenção para consulta, impressão ou lançamento.</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<?if($login_fabrica<>20){?>
<tr bgcolor='#FAFAFA'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='os_revenda.php' class='menu'>Abertura de OS de Revenda</a></td>
	<td class='descricao'>Clique aqui para incluir uma nova ordem de serviço de revenda</td>
</tr>
<?}?>
<!-- ================================================================== -->
<?if($login_fabrica<>20){?>
<tr bgcolor='#F0F0F0'>
	<td><img src='imagens/tela25.gif'></td>
<!--<td><a href='os_revenda_parametros.php' class='menu'>Consulta de OS de Revenda</a></td>-->
	<td><a href='os_revenda_consulta_lite.php' class='menu'>Consulta de OS de Revenda</a></td>
	<td class='descricao'>Ordens de serviços de revenda para consulta, impressão ou alteração</td>
</tr>
<?}?>
<!-- ================================================================== -->
<?if($login_fabrica==19){?>
<tr bgcolor='#FAFAFA'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='os_revenda_fechamento.php' class='menu'>Fechamento de OS Revenda</a></td>
	<td class='descricao'>Fechamento das Ordens de Serviços de Revenda</td>
</tr>
<?}?>
<!-- ================================================================== -->
<? if ($login_fabrica == 1) { ?>
<tr bgcolor='#F0F0F0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='sedex_parametros.php' class='menu'>Consulta OS SEDEX</a></td>
	<td class='descricao'>Consulta OS Sedex Lançadas</td>
</tr>
<!-- ================================================================== -->
<?
/*
$sql =	"SELECT posto
		FROM tbl_posto_fabrica
		WHERE fabrica = $login_fabrica
		AND posto = $login_posto
		AND reembolso_peca_estoque IS TRUE
		AND coleta_peca IS TRUE;";
$res = pg_exec($con,$sql);
//if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res);
if (pg_numrows($res) > 0) {
*/

/*
$array_postos = array("5080", "5074", "5367", "5252", "5077", "5311", "5258", "5219", "5335", "5082", 
					"814", "5097", "5239", "5162", "5328", "1254", "5184", "5342", "5449", "5361",
					"5157", "5312", "5433", "5237", "5137", "5256", "5242", "5351", "5287", "580",
					"1844", "5289", "5368", "5053", "5214", "5087", "5447", "5348", "5355", "836",
					"5436", "5223", "5132", "5297", "5310", "891", "2312", "5236");

if (in_array($login_posto, $array_postos)) {
*/
$sql = "SELECT posto 
		FROM   tbl_posto_fabrica 
		WHERE  posto   = $login_posto 
		AND    fabrica = $login_fabrica
		AND    coleta_peca is true";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0){
?>
<tr bgcolor='#FAFAFA'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_devolucao_pecas.php' class='menu'>Devolução de Peças</a></td>
	<td class='descricao'>Consulta Devolução de Peças</td>
</tr>
<?
}
}
?>
<!-- ================================================================== -->
<? if(($login_fabrica==1) and ($login_posto==6359)){ ?>
<tr bgcolor='#F0F0F0'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='os_cadastro_troca.php' class='menu'>Abertura de OS de Troca</a></td>
	<td class='descricao'>Abre Ordem de Serviço de Troca</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<tr bgcolor='#F0F0F0'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='os_extrato.php' class='menu'>Extrato</a></td>
	<td class='descricao'>Consulta de Extratos</td>
</tr>
<!-- ================================================================== -->
<?if($login_fabrica<>20){?>
<tr bgcolor='#FAFAFA'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='os_upload.php' class='menu'>UPLOAD de OS </a></td>
	<td class='descricao'>Envio de arquivo para o site contendo suas Ordens de Serviço em formato TEXTO</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<? if ($login_fabrica == 1) { ?>
<tr bgcolor='#F0F0F0'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='produto_maodeobra.php' class='menu'>Tabela de Mão de Obra</a></td>
	<td class='descricao'>Tabela de peços da Mão de Obra do Produto</td>
</tr>
<? } ?>
<!-- ================================================================== -->
<?
	if($login_fabrica == "xx"){
?>
<tr bgcolor='#F0F0F0'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='britania_posicao_extrato.php' class='menu'>Extrato (site antigo)</a></td>
	<td class='descricao'>Consulta posição dos Extratos</td>
</tr>

<!-- ================================================================== -->
<?	
		if($login_tipo_posto == 2 AND $pedido_via_distribuidor == 't'){ 
?>

<tr bgcolor='#FAFAFA'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='relatorio_saldo_pecas.php' class='menu'>Relatório de Saldo</a></td>
	<td class='descricao'>Relatório de Saldo de Peças em OS</td>
</tr>
<!-- ================================================================== -->
<?
		}
	}
?>


<?
	if($login_fabrica == 7){
?>

<!-- ================================================================== -->
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a  href="os_print_filizola.php?branco=sim" target='_blank' class='menu'>Imprime OS em Branco</a></td>
	<td nowrap class='descricao'>Impressão de Ordens de serviços para técnicos, em branco</td>
</tr>
<!-- ==================================================================
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='os_filizola_relatorio.php' class='menu'>Faturamento - Valores da OS</a></td>
	<td class='descricao'>Consulta as OS com valores</td>
</tr>
-->

<!-- ////////////// retirado provisóriamente
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/tela25.gif'></td>
	<td><a href='os_preventiva.php' class='menu'>Preventiva</a></td>
	<td class='descricao'>Ordens de serviços de manutenções preventivas</td>
</tr>
================================================================== -->


<?
	}
?>


<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>

</table>


<? include "rodape.php" ?>

</body>
</html>
