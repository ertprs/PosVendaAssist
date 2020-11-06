<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";
?>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10PX	;
	font-weight: bold;
	border: 1px solid;
	background-color: #D9E2EF;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #FFFFFF
}
.quebrapagina {
   page-break-before: always;
}
</style>


<?

$btnacao = trim($_GET["btnacao"]);
$data_inicial = $_GET['inicio'];
$data_final = $_GET['fim'];


if ($btnacao=='pesquisar') {
	$data_inicial = str_replace (" " , "" , $data_inicial);
	$data_inicial = str_replace ("-" , "" , $data_inicial);
	$data_inicial = str_replace ("/" , "" , $data_inicial);
	$data_inicial = str_replace ("." , "" , $data_inicial);

	$data_final = str_replace (" " , "" , $data_final);
	$data_final = str_replace ("-" , "" , $data_final);
	$data_final = str_replace ("/" , "" , $data_final);
	$data_final = str_replace ("." , "" , $data_final);

	if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
	if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

	if (strlen ($data_inicial) > 0) $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
	if (strlen ($data_final)   > 0) $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);

	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
		$data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
	$sql = "SELECT COUNT(tbl_os_extra.os) AS qtde_os
			FROM tbl_extrato
			JOIN tbl_os_extra USING(extrato)
			WHERE tbl_extrato.fabrica     = $login_fabrica 
			AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'
			AND tbl_extrato.liberado NOTNULL
			AND tbl_extrato.posto NOT IN ('6359','14301','20321')";

	$res = pg_exec($con,$sql);
	$qtde_os_total = trim(pg_result($res,0,qtde_os));
	
	$sql =	"SELECT tbl_posto.cnpj,
					tbl_posto.nome as posto,
					tbl_extrato.mao_de_obra,
					tbl_extrato.avulso,
					tbl_extrato.total,
					tbl_banco.nome as banco,
					COUNT(tbl_os_extra.os) AS qtde_os
			FROM tbl_extrato
			LEFT JOIN tbl_os_extra USING(extrato)
			JOIN tbl_posto using(posto)
			JOIN tbl_posto_fabrica using(posto)
			LEFT join tbl_banco on tbl_posto_fabrica.banco = tbl_banco.codigo
			WHERE tbl_extrato.fabrica = $login_fabrica 
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
			AND tbl_extrato.liberado NOTNULL
			AND tbl_extrato.posto NOT IN ('6359','14301','20321')
			GROUP BY tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_extrato.mao_de_obra,
					tbl_extrato.avulso,
					tbl_extrato.total,
					tbl_banco.nome
			ORDER BY tbl_posto.nome;";
	$res = pg_exec($con,$sql);

	if (pg_numrows ($res) > 0) {
		$data = date ("d-m-Y");

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$posto         = trim(pg_result($res,$i,cnpj)).' - '.trim(pg_result($res,$i,posto));
			$mao_de_obra   = trim(pg_result($res,$i,mao_de_obra));
			$avulso        = trim(pg_result($res,$i,avulso));
			$total         = trim(pg_result($res,$i,total));
			$banco         = trim(pg_result($res,$i,banco));

			$mao_de_obra   = number_format ($mao_de_obra,2,',','.');
			$pecas         = number_format ($pecas,2,',','.');
			$avulso        = number_format ($avulso,2,',','.');
			$total         = number_format ($total,2,',','.');
			$qtde_os       = trim(pg_result($res,$i,qtde_os));

			$porcentagem   = ($qtde_os_total == 0) ? '0,00' : number_format (($qtde_os / $qtde_os_total)*100,2,',','.');


			if ($i == 0) {
				echo "<table width='665' align='center' border='0'>";
				echo "<tr>";
				echo "<td colspan='5'>AULIK IND. COM LT-MATRIZ(10)</td>";
				echo "</tr>";
				echo "<tr>";


				$data_inicialx = substr ($data_inicial,8,2) . "/" . substr ($data_inicial,5,2) . "/" . substr ($data_inicial,0,4);
				$data_finalx = substr ($data_final,8,2) . "/" . substr ($data_final,5,2) . "/" . substr ($data_final,0,4);



				echo "<td colspan='5'>MOVIMENTAÇÃO DO POSTO AUTORIZADO - $data_inicialx até $data_finalx<BR><BR></td>";
				echo "</tr>";

				echo "<tr class = 'menu_top'>";
				echo "<td align='left' nowrap>Posto</td>";
				echo "<td align='left' nowrap>Garantia</td>";
				echo "<td align='left' nowrap>Lançamentos</td>";
				echo "<td align='left' nowrap>Saldo</td>";
				echo "<td align='left' nowrap>Banco</td>";
				echo "<td align='left' nowrap>Qtde OS</td>"; # HD 23195
				echo "<td align='left' nowrap>% OS</td>";    # HD 23195
				echo "</tr>";
			}


			if (($i%45==0)&&($i!=0)){

				echo "<tr class = 'table_line'>";
				echo "<td align='left' nowrap>".substr($posto,0,45)."&nbsp;</td>";
				echo "<td align='right' nowrap>$mao_de_obra&nbsp;</td>";
				echo "<td align='right' nowrap>$avulso&nbsp;</td>";
				echo "<td align='right' nowrap>$total&nbsp;</td>";
				echo "<td align='left' nowrap>".substr($banco,0,18)."</td>\n";
				echo "<td align='right' nowrap>$qtde_os</td>\n";
				echo "<td align='right' nowrap>$porcentagem</td>\n";
				echo "</tr>";

 			    //MONTA O CABEÇALHO DEPOIS DA QUEBRA DE PAGINA
	 		    echo "<TR class='quebrapagina'>\n";
				echo "<td colspan='5' >AULIK IND. COM LT-MATRIZ(10)</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td colspan='5' >MOVIMENTAÇÃO DO POSTO AUTORIZADO - $data_inicialx até $data_finalx<BR><BR></td>";
				echo "</tr>";

				echo "<tr class = 'menu_top'>";
				echo "<td align='left' nowrap>Posto</td>";
				echo "<td align='left' nowrap>Garantia</td>";
				echo "<td align='left' nowrap>Lançamentos</td>";
				echo "<td align='left' nowrap>Saldo</td>";
				echo "<td align='left' nowrap>Banco</td>";
				echo "<td align='left' nowrap>Qtde OS</td>"; # HD 23195
				echo "<td align='left' nowrap>% OS</td>";    # HD 23195
				echo "</tr>";


			}else{
				echo "<tr class = 'table_line'>";

				echo "<td align='left' nowrap>".substr($posto,0,45)."&nbsp;</td>";
				echo "<td align='right' nowrap>$mao_de_obra&nbsp;</td>";
				echo "<td align='right' nowrap>$avulso&nbsp;</td>";
				echo "<td align='right' nowrap>$total&nbsp;</td>";
				echo "<td align='left' nowrap>".substr($banco,0,18)."</td>\n";
				echo "<td align='right' nowrap>$qtde_os</td>\n";
				echo "<td align='right' nowrap>$porcentagem</td>\n";
				echo "</tr>";
			}
		}
		echo "</table>";
		
		
		$sql =	"SELECT count(tbl_posto.cnpj||' '||tbl_posto.nome) as posto,
						sum(tbl_extrato.mao_de_obra) as mao_de_obra,
						sum(tbl_extrato.pecas) as pecas,
						sum(tbl_extrato.avulso) as avulso,
						sum(tbl_extrato.total) as total
				FROM tbl_extrato
				JOIN tbl_posto using(posto)
				JOIN tbl_posto_fabrica using(posto)
				LEFT join tbl_banco on tbl_posto_fabrica.banco = tbl_banco.codigo
				WHERE tbl_extrato.fabrica = 11 and tbl_posto_fabrica.fabrica=11
				AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
				AND tbl_extrato.liberado NOTNULL
				AND tbl_extrato.posto NOT IN ('6359','14301','20321')";
		$res = pg_exec($con,$sql);

		$posto         = trim(pg_result($res,0,posto));
		$mao_de_obra   = trim(pg_result($res,0,mao_de_obra));
		$avulso        = trim(pg_result($res,0,avulso));
		$total         = trim(pg_result($res,0,total));

		$mao_de_obra   = number_format ($mao_de_obra,2,',','.');
		$avulso        = number_format ($avulso,2,',','.');
		$total         = number_format ($total,2,',','.');
		$qtde_os_total       = number_format ($qtde_os_total,0,',','.');


		echo "<BR><table width='665' align='center' border='0' cellspacing='2'>";
		echo "<tr class = 'menu_top'>";
		echo "<td align='center' valign='bottom' nowrap rowspan=2 width='15%'>TOTAL GERAL</td>";
		echo "<td align='center' nowrap wisth='16'>Posto</td>";
		echo "<td align='center' nowrap wisth='16'>Garantia</td>";
		echo "<td align='center' nowrap wisth='16'>Lançamentos</td>";
		echo "<td align='center' nowrap wisth='16'>Saldo</td>";
		echo "<td align='center' nowrap>Total OS</td>";

		echo "</tr>";
		echo "<tr class = table_line>";
		echo "<td align='center' nowrap wisth='16'>$posto</td>";
		echo "<td align='center' nowrap wisth='16'>$mao_de_obra</td>";
		echo "<td align='center' nowrap wisth='16'>$avulso</td>";
		echo "<td align='center' nowrap wisth='16'>$total</td>";
		echo "<td align='right' nowrap>$qtde_os_total</td>";# HD 23195

		echo "</tr>";
		echo "</table>";

		echo "<script> \n window.print(); \n</script>\n";

	}else 
		echo "<center>NENHUM EXTRATO ENCONTRADO</center>";
}

?>