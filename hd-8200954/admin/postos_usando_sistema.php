<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="auditoria";
include "autentica_admin.php";

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
		<td colspan="4" style="text-align: center;">
			<input type="hidden" name="btn_acao" value="">
			<img border="0" src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde a submissão...'); }" style="cursor: pointer;" alt='Clique AQUI para pesquisar'></td>
	</tr>
</table>

</form>

<?
if ($btn_acao == "pesquisar" AND strlen($msg_erro) == 0) {

	$data_inicial = date("Y-m-d", mktime(0, 0, 0, $pesquisa_mes, 1, $pesquisa_ano));
	$data_final = date("Y-m-t", mktime(0, 0, 0, $pesquisa_mes, 1, $pesquisa_ano));
	$sql =	"SELECT tbl_posto_fabrica.codigo_posto   ,
					tbl_posto_fabrica.credenciamento ,
					tbl_posto.posto                  ,
					tbl_posto.nome                   ,
					tbl_posto.estado ";

	$sqlX =	"SELECT linha FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY linha";
	$resX = pg_exec($con,$sqlX);
	if (pg_numrows($resX) > 0) {
		$sql .= " , ";
		$ultima_linha = pg_numrows($resX) - 1;
		for ($j = 0 ; $j < pg_numrows($resX) ; $j++) {
			$sql .= " COUNT(CASE WHEN os.linha = " . pg_result($resX,$j,0) . "   THEN 1 ELSE null END) AS qtde" . pg_result($resX,$j,0);
			if ($j != $ultima_linha) $sql .= " , ";
		}
	}
	
	$sql .=	" FROM tbl_posto
			JOIN tbl_posto_fabrica  ON tbl_posto.posto = tbl_posto_fabrica.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN (
						SELECT  tbl_produto.linha ,
								tbl_os.posto      
						FROM tbl_os
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
					) os ON tbl_posto.posto = os.posto
			GROUP BY tbl_posto_fabrica.codigo_posto  ,
					 tbl_posto.posto                 ,
					 tbl_posto_fabrica.credenciamento,
					 tbl_posto.nome                  ,
					 tbl_posto.estado               
			ORDER BY tbl_posto.estado , tbl_posto.nome ";

#		WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'

	$res1 = pg_exec($con,$sql);

	if (pg_numrows($res1) > 0) {

		echo "<h3>Para localizar uma palavra na página, tecle CTRL + F.</h2>";

		echo "<table align='center' border='0' cellspacing='1' cellpadding='2'>";
		echo "<tr bgcolor='#596D9B'>";
		echo "<td class='menu_top' nowrap>Posto</td>";
		echo "<td class='menu_top' nowrap>Estado</td>";

		$sql =	"SELECT linha, codigo_linha, nome
				FROM tbl_linha
				WHERE fabrica = $login_fabrica
				ORDER BY linha";
		$res2 = pg_exec($con,$sql);
		for ($i = 0 ; $i < pg_numrows($res2) ; $i++) {
			echo "<td class='menu_top' nowrap>" . pg_result($res2, $i, nome) . "</td>";
		}

		echo "</tr>";

		$usaram = 0;
		$nao_usaram = 0;

		$total         = array();
		$atende        = array();
		$postos        = array();
		$postos_atende = array();
		$qtde          = array();
		
		$sqlX =	"SELECT linha FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY linha";
		$resX = pg_exec($con,$sqlX);
		if (pg_numrows($resX) > 0) {
			for ($k = 0 ; $k < pg_numrows($resX) ; $k++) {
				$linha = @pg_result($resX,$k,linha);
				$total[$linha]         = 0;
				$postos[$linha]        = 0;
				$postos_atende[$linha] = 0;
				$qtde[$linha]          = 0;
				$atende[$linha]        = false;
			}
		}

		$l = 0;

		for ($j = 0 ; $j < pg_numrows($res1) ; $j++) {
			$posto_codigo   = pg_result($res1, $j, codigo_posto);
			$posto          = pg_result($res1, $j, posto);
			$credenciamento = pg_result($res1, $j, credenciamento);
			$posto_nome     = pg_result($res1, $j, nome);
			$posto_estado   = pg_result($res1, $j, estado);
			
			$sqlX =	"SELECT linha FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY linha";
			$resX = pg_exec($con,$sqlX);
			if (pg_numrows($resX) > 0) {
				for ($k = 0 ; $k < pg_numrows($resX) ; $k++) {
					$linha = @pg_result($resX,$k,linha);
					$qtde_linha = "qtde" . @pg_result($resX,$k,linha);
					$qtde[$linha] = @pg_result($res1,$j,$qtde_linha);
				}
			}

			if (array_sum($qtde) > 0 OR $credenciamento == 'CREDENCIADO') {
				$sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND linha IN ( ";
				$k = 1;
				foreach ($atende as $chave => $valor) {
					$sql .= $chave;
					if ($k != count($atende)) $sql .= ",";
					$k++;
				}
				$sql .= " )";
				$resZ = pg_exec ($con,$sql);
				
				foreach ($atende as $chave => $valor) {
					$atende[$chave] = false;
				}
				
				for ($z = 0 ; $z < pg_numrows ($resZ) ; $z++) {
					foreach ($atende as $chave => $valor) {
						if (pg_result($resZ,$z,linha) == $chave) $atende[$chave] = true;
					}
				}

				if ($l % 2 == 0) $cor = "#F7F5F0";
				else             $cor = "#F1F4FA";

				echo "<tr class='table_line' bgcolor='$cor'>";
				echo "<td nowrap><acronym title='Código: $posto_codigo | Razão Social: $posto_nome'>" . $posto_codigo . " - " . substr($posto_nome, 0, 20) ." </acronym></td>";
				echo "<td nowrap align='center'>" . $posto_estado   . "</td>";
				
				foreach ($atende as $chave => $valor) {
					if ($atende[$chave]) {
						echo "<td nowrap align='right'>" . $qtde[$chave] . "</td>";
						$postos_atende[$chave]++;
					}else{
						echo "<td nowrap align='right'> - </td>";
					}

					$total[$chave] += $qtde[$chave];

					if ($qtde[$chave] > 0) $postos[$chave]++;
				}

				echo "</tr>";

				if (array_sum($qtde) > 0) $usaram++ ;
				else                      $nao_usaram++ ;

				$l++;
			}
		}
		echo "<tr bgcolor='#596D9B'>";
		$total_postos = $usaram + $nao_usaram;
		echo "<td class='menu_top' colspan='2'>Total de Postos - " . $total_postos . "<br>Usaram o site - $usaram <br> Não usaram - $nao_usaram" . "</td>";
		foreach ($atende as $chave => $valor) {
			if ($postos_atende[$chave]<>0)echo "<td class='menu_top' nowrap align='right'>" . $total[$chave]    . " OS<br>" . $postos[$chave]    . " PAs (" . number_format ($postos[$chave] / $postos_atende[$chave] * 100,0) . "%) </td>";
		}
		echo "</tr>";

		echo "</table>";
	}else{
		echo "<h3>Resultado não encontrado para esta consulta</h2>";
	}
}
?>

<br>

<? include "rodape.php" ?>
