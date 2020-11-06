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
		$msg_erro .= " Favor preencher o campo Mês. ";
	}

	if (strlen($pesquisa_ano) == 0) {
		$msg_erro .= " Favor preencher o campo Ano. ";
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
</style>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<? if (strlen($msg_erro) > 0){ ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'><?echo $msg_erro?></td>
</tr>
</table>
<? } ?>

<br>
<?
	$data_filtro = $_POST['data_filtro'];
?>
<table width="400" align="center" border="0" cellspacing="0" cellpadding="3">
	<tr bgcolor="#596D9B">
		<td colspan="4" class="menu_top"><b>Preencha os campos para efetuar a pesquisa</b></td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td class="table_line">&nbsp;</td>
		<td class="table_line">
			Mês<br>
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
		<td class="table_line">
			Ano<br>
			<input type="text" size="5" maxlength="4" name="pesquisa_ano" value="<?echo $pesquisa_ano?>" class="frm">
		</td>
		<td class="table_line">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td class="table_line">&nbsp;</td>
		<td class="table_line">Data para filtrar:</td>
		<td class="table_line"><input type="radio" name="data_filtro" value="ABERTURA" <? if($data_filtro == 'ABERTURA' OR $data_filtro == 0){?>checked<?}?>> Abertura da OS<br><input type="radio" name="data_filtro" value="DIGITACAO" <? if($data_filtro=='DIGITACAO'){?>checked<?}?>> Digitação da OS<br><input type="radio" name="data_filtro" value="FINALIZADA" <? if($data_filtro=='FINALIZADA'){?>checked<?}?>> Finalização da OS</td>
		<td class="table_line">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4" style="text-align: center;">
			<input type="hidden" name="btn_acao" value="">
			<img border="0" src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde a submissão...'); }" style="cursor: pointer;" alt='Clique AQUI para pesquisar'></td>
	</tr>
</table>

</form>

<?

if ($btn_acao == "pesquisar" AND strlen($msg_erro) == 0) {

	$data_filtro = $_POST['data_filtro'];

	if (strlen($data_filtro) == 0) $data_filtro = "data_digitacao";

	$data_inicial = date("Y-m-d", mktime(0, 0, 0, $pesquisa_mes, 1, $pesquisa_ano));
	$data_final = date("Y-m-t", mktime(0, 0, 0, $pesquisa_mes, 1, $pesquisa_ano));

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
			WHERE   tbl_pecas_por_os.mes = '$pesquisa_mes'
			AND     tbl_pecas_por_os.ano = '$pesquisa_ano'
			AND     tbl_posto_fabrica.fabrica = $login_fabrica 
			AND     tbl_pecas_por_os.criterio = '$data_filtro'
			ORDER BY estado, tbl_posto.nome ";
//echo $sql;
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<h3>Para localizar uma palavra na página, tecle CTRL + F.</h2>";

//data da ultima geração do relatório - pega diretamente do arquivo que é gerado com o relatorio
//Fernando 15/09
		$arquivo = "pecas_por_os_atualizado.txt";
		$conteudo = fopen($arquivo, "r");
		$data = fread($conteudo, filesize($arquivo));
		echo " <FONT SIZE=\"2\"><B>Relatório gerado: $data</B></FONT> <br><br>";
//==============================================================================================

		echo "<table align='center' border='0' cellspacing='1' cellpadding='2'>";
		echo "<tr bgcolor='#596D9B'>";
		echo "<td class='menu_top' nowrap rowspan='2'>Posto</td>";
		echo "<td class='menu_top' nowrap rowspan='2'>Nome do Posto</td>";
		echo "<td class='menu_top' nowrap rowspan='2'>Estado</td>";

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
						$cor = "#fafafa";
						if ($cor_linha % 2 == 0) $cor = "#eeeeff";

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
						echo "</tr>";
						echo "<tr>";
						echo "<td nowrap>TOTAL DE OSs= <b>$total_os</b></td>";
						echo "<td nowrap>TOTAL DE PEÇAS= <b>$total_pecas</b></td>";
						$usaram++;
						echo "</tr>";

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
