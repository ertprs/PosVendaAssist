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
		$msg_erro .= " Favor llenar el campo Mês. ";
	}

	if (strlen($pesquisa_ano) == 0) {
		$msg_erro .= " Favor llenar el campo año ";
	}
}

$layout_menu = "auditoria";
$title = "REPORTES DE SERVICIOS UTILIZANDO";

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
		<td colspan="4" class="menu_top"><b>Llenar los campos para efectuar la consulta</b></td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td class="table_line">&nbsp;</td>
		<td class="table_line">
			Mes<br>
			<select name="pesquisa_mes" size="1" class="frm">
				<option value=""></option>
				<?

				$meses = array (1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
				for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='" . str_pad($i, 2, "0", STR_PAD_LEFT) . "'";
					if ( $pesquisa_mes == str_pad($i, "0", STR_PAD_LEFT) ) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
			</select>
		</td>
		<td class="table_line">
			Año<br>
			<input type="text" size="5" maxlength="4" name="pesquisa_ano" value="<?echo $pesquisa_ano?>" class="frm">
		</td>
		<td class="table_line">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td class="table_line">&nbsp;</td>
		<td class="table_line">Fecha para filtrar:</td>
		<td class="table_line"><input type="radio" name="data_filtro" value="data_abertura" <? if($data_filtro == 'data_abertura' OR $data_filtro == 0){?>checked<?}?>> Abertura da OS<br><input type="radio" name="data_filtro" value="data_digitacao" <? if($data_filtro=='data_digitacao'){?>checked<?}?>> Digitación de OS<br><input type="radio" name="data_filtro" value="data_fechamento" <? if($data_filtro=='data_fechamento'){?>checked<?}?>> Finalización de OS</td>
		<td class="table_line">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4" style="text-align: center;">
			<input type="hidden" name="btn_acao" value="">
			<img border="0" src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Espera'); }" style="cursor: pointer;" alt='Click aquí para buscar'></td>
	</tr>
</table>

</form>

<?

if ($btn_acao == "pesquisar" AND strlen($msg_erro) == 0) {

	$data_filtro = $_POST['data_filtro'];

	if (strlen($data_filtro) == 0) {
		$data_filtro = "data_digitacao";
	}

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
						tbl_pecas_por_os.qtde_pecas       ";

	$sql = "SELECT  PF.posto                              ,
					PO.cnpj                               ,
					PF.codigo_posto        AS posto_codigo,
					PO.nome                AS posto_nome  ,
					PO.estado              AS posto_estado,
					PF.credenciamento                     ,
					LI.linha                              ,
					LI.nome                AS linha_nome  ,
					COUNT(BI.os)           AS qtde_os     ,
					SUM(BI.mao_de_obra)    AS mao_de_obra ,
					SUM(BI.qtde_pecas)     AS qtde_pecas
		FROM      bi_os BI
		JOIN      tbl_posto         PO ON PO.posto   = BI.posto
		JOIN      tbl_posto_fabrica PF ON PF.posto   = BI.posto
		JOIN      tbl_produto       PR ON PR.produto = BI.produto
		JOIN      tbl_linha         LI ON LI.linha   = BI.linha
		JOIN      tbl_familia       FA ON FA.familia = BI.familia
		LEFT JOIN tbl_marca         MA ON MA.marca   = BI.marca
		WHERE BI.fabrica = $login_fabrica
		AND   PF.fabrica = $login_fabrica
		AND   BI.$data_filtro BETWEEN '$data_inicial' AND '$data_final'
		AND   PO.pais    = '$login_pais'
		GROUP BY    posto_codigo     ,
					posto_nome       ,
					posto_estado     ,
					linha_nome       ,
					cnpj             ,
					li.linha         ,
					PF.posto         ,
					PF.credenciamento
		ORDER BY posto_nome,linha_nome DESC ";
	#echo nl2br($sql);
	#exit;
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<h3>Para buscar una palabra en la pantalla, tecle CTRL + F.</h2>";

		echo "<table align='center' border='0' cellspacing='1' cellpadding='2'>";
		echo "<tr bgcolor='#596D9B'>";
		echo "<td class='menu_top' nowrap rowspan='2'>Servicio</td>";
		echo "<td class='menu_top' nowrap rowspan='2'>Nombre del Servicio</td>";
		echo "<td class='menu_top' nowrap rowspan='2'>Provincia</td>";

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
		echo "<td class='menu_top' nowrap align='center' rowspan='2'>Total OS</td>";
		echo "<td class='menu_top' nowrap align='center' rowspan='2'>Total Piezas</td>";
		echo "</tr>";
		$qtde_linhas = $i ;

		echo "<tr bgcolor='#596D9B'>";
		for ($i = 0 ; $i < $qtde_linhas ; $i++) {
			echo "<td class='menu_top' nowrap align='center'>Ctd OS</td>";
			echo "<td class='menu_top' nowrap align='center'>Ctd Piezas</td>";
		}

		echo "</tr>";

		$cor_linha = 0 ;
		$usaram = 0;
		$nao_usaram = 0;
		$posto_ant = "*";

		for ($i = 0 ; $i < pg_numrows($res) + 1 ; $i++) {

			$posto = "#";
			if ($i < pg_numrows ($res) ) {
				$posto = pg_result ($res,$i,posto);
			}

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
						echo "<td>$total_os</td>";
						echo "<td>$total_pecas</td>";
						$usaram++;
						echo "</tr>";

					}

					if ($total == 0 AND $credenciamento == "CREDENCIADO") $nao_usaram++ ;
				}
			}

			if ($i == pg_numrows ($res) ) break ;

			$posto_codigo   = pg_result($res, $i, posto_codigo);
			$posto          = pg_result($res, $i, posto);
			$posto_ant      = pg_result($res, $i, posto);
			$credenciamento = pg_result($res, $i, credenciamento);
			$posto_nome     = pg_result($res, $i, posto_nome);
			$posto_estado   = pg_result($res, $i, posto_estado);
			$linha          = pg_result($res, $i, linha);
			$linha_nome     = pg_result($res, $i, linha_nome);
			$qtde           = pg_result($res, $i, qtde_os);
			$pecas          = pg_result($res, $i, qtde_pecas);
			$cnpj           = pg_result($res, $i, cnpj);

			for ($z = 0 ; $z < $qtde_linhas ; $z++) {
				if ($array_linhas[$z][0] == $linha_nome) {
					$array_linhas [$z][1] = $qtde ;
					$array_linhas [$z][2] = $pecas ;
				}
			}

		}

		echo "</table>";
	}else{
		echo "<h3>Resultado no encuentrado para esta busca</h2>";
	}
}
?>

<br>

<? include "rodape.php" ?>
