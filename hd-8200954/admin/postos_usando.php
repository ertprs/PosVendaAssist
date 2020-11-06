<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

#if ($login_fabrica == 5){
#	header("Location: postos_usando-mondial.php");
#	exit;
#}

//if ($login_fabrica <> 3){
//	header("Location: postos_usando_sistema.php");
//	exit;
//}

$msg_erro = "";

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = $_POST["btn_acao"];

if ($btn_acao == "pesquisar") {
	$pesquisa_mes = trim($_POST["pesquisa_mes"]);
	$pesquisa_ano = trim($_POST["pesquisa_ano"]);

	if (strlen($pesquisa_mes) == 0) {
		$msg_erro = " Selecione o Período para a Pesquisa ";
	}

	if (strlen($pesquisa_ano) == 0) {
		$msg_erro = " Selecione o Período para a Pesquisa ";
	}
}

$layout_menu = "auditoria";
$title = "RELATÓRIO DE POSTOS UTILIZANDO O SISTEMA";

include "cabecalho.php";
?>

<style type="text/css">
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

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

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}
</style>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<?
	$data_filtro = $_POST['data_filtro'];
?>
<table width="700" align="center" border="0" cellspacing="0" cellpadding="3" class="formulario">
	<? if (strlen($msg_erro) > 0){ ?>
			<tr class="msg_erro">
				<td colspan="4"><?echo $msg_erro?></td>
			</tr>
	<? } ?>
	<tr class="titulo_tabela">
		<td colspan="4" >Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td width="120">&nbsp;</td>
		<td width="130">
			Mês&nbsp;
			<select name="pesquisa_mes" size="1" class="frm">
				<option value=""></option>
				<?
				$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
				for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='" . str_pad($i, 2, "0", STR_PAD_LEFT) . "'";
					if ( $pesquisa_mes == str_pad($i, "0", STR_PAD_LEFT) ) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
			</select>
		</td>
		<td>
			Ano&nbsp;
			<input type="text" size="5" maxlength="4" name="pesquisa_ano" value="<?echo $pesquisa_ano?>" class="frm">
		</td>
		<td width="120">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="3">
			<fieldset style="width:350px;">
				<legend>Filtrar por data de</legend>
				<input type="radio" name="data_filtro" value="ABERTURA" <? if($data_filtro == 'ABERTURA' OR $data_filtro == 0){?>checked<?}?>> Abertura da OS&nbsp;&nbsp;<input type="radio" name="data_filtro" value="DIGITACAO" <? if($data_filtro=='DIGITACAO'){?>checked<?}?>> Digitação da OS&nbsp;&nbsp;<input type="radio" name="data_filtro" value="FINALIZADA" <? if($data_filtro=='FINALIZADA'){?>checked<?}?>> Finalização da OS
			</fieldset>
		</td>
		
	</tr>
	<tr>
		<td colspan="4" style="text-align: center;">
			<input type="hidden" name="btn_acao" value="">
			<input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde a submissão...'); }" alt='Clique AQUI para pesquisar'></td>
	</tr>
</table>

</form>

<?

if ($btn_acao == "pesquisar" AND strlen($msg_erro) == 0) {

	$data_filtro = $_POST['data_filtro'];

	if (strlen($data_filtro) == 0) $data_filtro = "data_digitacao";

	$data_inicial = date("Y-m-d", mktime(0, 0, 0, $pesquisa_mes, 1, $pesquisa_ano));
	$data_final = date("Y-m-t", mktime(0, 0, 0, $pesquisa_mes, 1, $pesquisa_ano));

/*	if($login_fabrica == 3){
		if($pesquisa_mes == '05' AND $pesquisa_ano = '2007'){
			echo "<p style='font-size: 14px; font-family: verdana;'>Base de dados sendo atualizada. <br>Tente novamente mais tarde.</p>";
			echo "<br><br><br>";
			include "rodape.php";
			exit;
		}
	}
*/
	$sql=	"SELECT distinct		tbl_pecas_por_os.posto            ,
						tbl_pecas_por_os.mes              ,
						tbl_pecas_por_os.ano              ,
						tbl_pecas_por_os.pecas_por_os     ,
						tbl_pecas_por_os.qtde_os          ,
						tbl_posto_fabrica.credenciamento  ,
						tbl_posto.cnpj                    ,
						tbl_posto_fabrica.codigo_posto    ,
						tbl_linha.nome          AS linha  ,
						tbl_posto.estado                  ,
						tbl_posto.nome                    ,
						tbl_pecas_por_os.qtde_pecas       
			FROM tbl_pecas_por_os
			JOIN    tbl_posto using (posto)
			JOIN    tbl_posto_fabrica using (posto)
			JOIN    tbl_posto_linha   using (posto) 
			JOIN    tbl_linha  on tbl_pecas_por_os.linha = tbl_linha.linha 
					and tbl_linha.fabrica = $login_fabrica
			WHERE   tbl_pecas_por_os.mes = '$pesquisa_mes'
			AND     tbl_pecas_por_os.ano = '$pesquisa_ano'
			AND     tbl_posto_fabrica.fabrica = $login_fabrica 
			AND     tbl_pecas_por_os.criterio = '$data_filtro'
			ORDER BY estado, tbl_posto.nome ";
//if($ip=="189.18.99.251"){echo $sql;exit;}
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		//echo "<h3>Para localizar uma palavra na página, tecle CTRL + F.</h2>";

//data da ultima geração do relatório - pega diretamente do arquivo que é gerado com o relatorio
//Fernando 15/09
		$arquivo = "pecas_por_os_atualizado.txt";
		$conteudo = fopen($arquivo, "r");
		$data = fread($conteudo, filesize($arquivo));
		echo " <FONT SIZE=\"2\"><B>Relatório gerado: $data</B></FONT> <br><br>";
//==============================================================================================

		echo "<table align='center' border='0' cellspacing='1' cellpadding='2' class='tabela' width='700'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td nowrap rowspan='2'>Posto</td>";
		echo "<td nowrap rowspan='2'>Nome do Posto</td>";
		echo "<td nowrap rowspan='2'>Estado</td>";

		$sql =	"SELECT linha, codigo_linha, nome
				FROM tbl_linha
				WHERE fabrica = $login_fabrica
				ORDER BY linha";
		$res2 = pg_exec($con,$sql);

		$array_linhas = array();
		for ($i = 0 ; $i < pg_numrows($res2) ; $i++) {
			echo "<td class='menu_top' nowrap colspan='2' width='100' align='center' >" . pg_result($res2, $i, nome) . "</td>";
			$array_linhas [$i][0] = pg_result($res2, $i, nome) ;
			$array_linhas [$i][1] = 0;  # Qtde OS
			$array_linhas [$i][2] = 0;  # Qtde Peças
			$array_linhas [$i][3] = 0;  # Total OS
			$array_linhas [$i][4] = 0;  # Total Peças
		}
				echo "<td nowrap align='center' rowspan='2'>Total OS</td>";
		echo "<td nowrap align='center' rowspan='2'>Total Peças</td>";
		echo "</tr>";
		$qtde_linhas = $i ;

		echo "<tr bgcolor='#596D9B'>";
		for ($i = 0 ; $i < $qtde_linhas ; $i++) {
			echo "<td class='menu_top' nowrap align='center'>Qtde OS</td>";
			echo "<td class='menu_top' nowrap align='center'>Qtde Peças</td>";
		}

		echo "</tr>";

		$cor_linha = 0 ;
		$usaram = 0;
		$nao_usaram = 0;
		$posto_ant = "*";

		for ($i = 0 ; $i < pg_numrows($res) + 1 ; $i++) {
			$posto = "#";
			if ($i < pg_numrows ($res) ) $posto = pg_result ($res,$i,posto);

			if ($posto_ant <> $posto) {
				if ($posto_ant <> "*") {
					$total = 0 ;
					for ($z = 0 ; $z < $qtde_linhas ; $z++) {
						$qtde = $array_linhas[$z][1];
						$total += $qtde;
					}
					if (($total < 1) AND ($credenciamento == "CREDENCIADO") AND ($login_fabrica == 19)) {
						$credenciamento = "DESCREDENCIADO";
					}
					if (($total > 0 )OR $credenciamento == "CREDENCIADO") {
						$cor_linha++ ;
						$cor = "#F1F4FA";
						if ($cor_linha % 2 == 0) $cor = "#F7F5F0";

						echo "<tr bgcolor='$cor' style='font-size: 10px'>";
						echo "<td align='left' nowrap>";
						if($login_fabrica == 19){echo $cnpj; }else { echo $posto_codigo ; } 
						echo "</td>";

						echo "<td align='left' nowrap>";
						echo $posto_nome ;
						echo "</td>";

						echo "<td align='left' nowrap>";
						echo $posto_estado ;
						echo "</td>";
						$total_os = 0;
						$total_pecas = 0;
						for ($z = 0 ; $z < $qtde_linhas ; $z++) {
							$qtde  = $array_linhas [$z][1] ;
							$pecas = $array_linhas [$z][2] ;

							$array_linhas [$z][3] += $qtde  ;
							$array_linhas [$z][4] += $pecas ;

							echo "<td align='right' nowrap >";
							echo $array_linhas[$z][1];
							echo "</td>";

							echo "<td align='right' nowrap >";
							echo $array_linhas[$z][2];
							echo "</td>";
							
							$total_os    = $total_os + $array_linhas[$z][1];
							$total_pecas = $total_pecas + $array_linhas[$z][2];

							$array_linhas [$z][1] = 0 ;
							$array_linhas [$z][2] = 0 ;

						}
						echo "<td>$total_os</td>";
						echo "<td>$total_pecas</td>";
						$usaram++;
						echo "</tr>";

					}

					if ($total == 0 AND $credenciamento == "CREDENCIADO") $nao_usaram++ ;
				}
			}

			if ($i == pg_numrows ($res) ) break ;

			$posto_codigo   = pg_result($res, $i, 'codigo_posto');
			$posto          = pg_result($res, $i, 'posto');
			$posto_ant      = pg_result($res, $i, 'posto');
			$credenciamento = pg_result($res, $i, 'credenciamento');
			$posto_nome     = pg_result($res, $i, 'nome');
			$posto_estado   = pg_result($res, $i, 'estado');
			$linha          = pg_result($res, $i, 'linha');
			$qtde           = pg_result($res, $i, 'qtde_os');
			$pecas          = pg_result($res, $i, 'qtde_pecas');
			$cnpj           = pg_result($res, $i, 'cnpj');

			for ($z = 0 ; $z < $qtde_linhas ; $z++) {
				if ($array_linhas[$z][0] == $linha) {
					$array_linhas [$z][1] = $qtde ;
					$array_linhas [$z][2] = $pecas ;
				}
			}

		}
		

		flush();
		
		echo "<br><br>";
		/*echo "<table width='700' border='0' cellspacing='1' cellpadding='2' align='center' class='tabela'>";
		echo "<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";*/
		flush();
		
		$data = date ("d/m/Y H:i:s");

		echo `rm /tmp/assist/relatorio-de-postos-utilizando-sistema-$login_fabrica.xls`;


		$fp = fopen ("/tmp/assist/relatorio-de-postos-utilizando-sistema-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATóRIO DE POSTOS UTILIZANDO SISTEMA- $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		
		fputs ($fp,"<table align='center' border='1' cellspacing='1' cellpadding='1'>\n");
		
		fputs ($fp, "<tr bgcolor='#FFCC00' align='center'>\n");
		fputs ($fp, "<td class='menu_top' nowrap rowspan='2'>Posto</td>\n");
		fputs ($fp, "<td class='menu_top' nowrap rowspan='2'>Nome do Posto</td>\n");
		fputs ($fp, "<td class='menu_top' nowrap rowspan='2'>Estado</td>\n");
		
		$sql =	"SELECT linha, codigo_linha, nome
				FROM tbl_linha
				WHERE fabrica = $login_fabrica
				ORDER BY linha";
		$res2 = pg_exec($con,$sql);

		$array_linhas = array();
		for ($i = 0 ; $i < pg_numrows($res2) ; $i++) {
			$nome=  pg_result($res2, $i, nome);
			fputs ($fp,"<td class='menu_top' nowrap colspan='2' width='100' align='center' >$nome</td>\n");
			$array_linhas [$i][0] = pg_result($res2, $i, nome) ;
			$array_linhas [$i][1] = 0;  # Qtde OS
			$array_linhas [$i][2] = 0;  # Qtde Peças
			$array_linhas [$i][3] = 0;  # Total OS
			$array_linhas [$i][4] = 0;  # Total Peças
			}

		fputs ($fp, "<td class='menu_top' nowrap align='center' rowspan='2'>Total OS</td>\n");
		fputs ($fp, "<td class='menu_top' nowrap align='center' rowspan='2'>Total Peças</td>\n");
		fputs ($fp, "</tr>\n");
		$qtde_linhas = $i ;

		fputs ($fp,"<tr bgcolor='#596D9B'>\n");
		for ($i = 0 ; $i < $qtde_linhas ; $i++) {
			fputs ($fp, "<td class='menu_top' nowrap align='center'>Qtde OS</td>\n");
			fputs ($fp, "<td class='menu_top' nowrap align='center'>Qtde Peças</td>\n");
		}

		fputs ($fp, "</tr>\n");

		$cor_linha = 0 ;
		$usaram = 0;
		$nao_usaram = 0;
		$posto_ant = "*";

		for ($i = 0 ; $i < pg_numrows($res) + 1 ; $i++) {
			$posto = "#";
			if ($i < pg_numrows ($res) ) $posto = pg_result ($res,$i,posto);

			if ($posto_ant <> $posto) {
				if ($posto_ant <> "*") {
					$total = 0 ;
					for ($z = 0 ; $z < $qtde_linhas ; $z++) {
						$qtde = $array_linhas[$z][1];
						$total += $qtde;
					}
					if (($total < 1) AND ($credenciamento == "CREDENCIADO") AND ($login_fabrica == 19)) {
						$credenciamento = "DESCREDENCIADO";
					}
					if (($total > 0 )OR $credenciamento == "CREDENCIADO") {
						$cor_linha++ ;
						$cor = "#F1F4FA";
						if ($cor_linha % 2 == 0) $cor = "#F7F5F0";

						fputs ($fp, "<tr bgcolor='$cor' style='font-size: 10px'>\n");
						if($login_fabrica == 19){
							fputs ($fp, "<td align='left' nowrap>$cnpj</td>\n");
						}else{
							fputs ($fp, "<td align='left' nowrap>$posto_codigo</td>\n");
						} 
						

						fputs ($fp, "<td align='left' nowrap>$posto_nome</td>\n");

						fputs ($fp, "<td align='left' nowrap>$posto_estado</td>\n");
						$total_os = 0;
						$total_pecas = 0;
						for ($z = 0 ; $z < $qtde_linhas ; $z++) {
							$qtde  = $array_linhas [$z][1] ;
							$pecas = $array_linhas [$z][2] ;
							
							$array_linhas [$z][3] += $qtde  ;
							$array_linhas [$z][4] += $pecas ;

							fputs ($fp, "<td align='right' nowrap >\n");
							fputs ($fp, "$qtde\n");
							fputs ($fp, "</td>\n");

							fputs ($fp, "<td align='right' nowrap >\n");
							fputs ($fp, "$pecas\n");
							fputs ($fp, "</td>\n");
							
							$total_os    = $total_os + $array_linhas[$z][1];
							$total_pecas = $total_pecas + $array_linhas[$z][2];

							$array_linhas [$z][1] = 0 ;
							$array_linhas [$z][2] = 0 ;

						}
						fputs ($fp, "<td>$total_os</td>\n");
						fputs ($fp, "<td>$total_pecas</td>\n");
						$usaram++;
						fputs ($fp, "</tr>\n");

					}

					if ($total == 0 AND $credenciamento == "CREDENCIADO") $nao_usaram++ ;
				}
			}

			if ($i == pg_numrows ($res) ) break ;

			$posto_codigo   = pg_result($res, $i, codigo_posto);
			$posto          = pg_result($res, $i, posto);
			$posto_ant      = pg_result($res, $i, posto);
			$credenciamento = pg_result($res, $i, credenciamento);
			$posto_nome     = pg_result($res, $i, nome);
			$posto_estado   = pg_result($res, $i, estado);
			$linha          = pg_result($res, $i, linha);
			$qtde           = pg_result($res, $i, qtde_os);
			$pecas          = pg_result($res, $i, qtde_pecas);
			$cnpj           = pg_result($res, $i, cnpj);

			for ($z = 0 ; $z < $qtde_linhas ; $z++) {
				if ($array_linhas[$z][0] == $linha) {
					$array_linhas [$z][1] = $qtde ;
					$array_linhas [$z][2] = $pecas ;
				}
			}
		
		}
		fputs ($fp, "</table>\n");
		fputs ($fp, "<br>");
		fputs ($fp, "<table height='20'><tr class='menu_top'><td align='center'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");

		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);


		$data = date("Y-m-d").".".date("H-i-s");

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-de-postos-utilizando-sistema-$login_fabrica.$data.xls /tmp/assist/relatorio-de-postos-utilizando-sistema-$login_fabrica.html`;
		
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		?>
		<td align="center">
			<input type="button" onclick="window.location='xls/relatorio-de-postos-utilizando-sistema-<?php echo $login_fabrica.'.'.$data;?>.xls'" value="Download para Excel">
		</td>
		<?
		echo "</tr>";
		echo "</table>";
	
		

#		echo "<tr bgcolor='#596D9B'>";
#		$total_postos = $usaram + $nao_usaram;
#		echo "<td class='menu_top' colspan='2'>Total de Postos - " . $total_postos . "<br>Usaram o site - $usaram <br> Não usaram - $nao_usaram" . "</td>";
#		echo "<td class='menu_top' nowrap align='right'>" . $total_eletro    . " OS<br>" . $postos_eletro    . " PAs (" . number_format ($postos_eletro / $postos_atende_eletro * 100,0) . "%) </td>";
#		echo "<td class='menu_top' nowrap align='right'>" . $total_audio     . " OS<br>" . $postos_audio     . " PAs (" . number_format ($postos_audio  / $postos_atende_audio * 100,0) . "%) </td>";
#		echo "<td class='menu_top' nowrap align='right'>" . $total_branca    . " OS<br>" . $postos_branca    . " PAs (" . number_format ($postos_branca / $postos_atende_branca * 100,0) . "%) </td>";
#		echo "<td class='menu_top' nowrap align='right'>" . $total_autoradio . " OS<br>" . $postos_autoradio . " PAs (" . number_format ($postos_autoradio / $postos_atende_autoradio * 100,0) . "%) </td>";
#		echo "</tr>";

		echo "</table>";
	}else{
		echo "<h3>Resultado não encontrado para esta consulta</h2>";
	}
}
?>

<br>

<? include "rodape.php" ?>
