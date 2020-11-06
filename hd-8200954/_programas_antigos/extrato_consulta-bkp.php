<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"]) > 0) $posto = $_GET["posto"];

$title = "Consulta e Manutenção de Extratos";

$layout_menu = 'os';

include "cabecalho.php";
?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<?

$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
$data_final   = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];
$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

// INICIO DA SQL
$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
$data_final   = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];

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


if (strlen ($data_inicial) > 0 and strlen ($data_final) > 0) {
	$sql =	"SELECT tbl_posto.posto               ,
					tbl_posto.nome                ,
					tbl_posto.cnpj                ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.distribuidor,
					tbl_extrato.extrato           ,
					tbl_extrato.liberado          ,
					to_char (tbl_extrato.data_geracao,'dd/mm/yyyy') as data_geracao,
					tbl_extrato.total,
					(
						SELECT	count (tbl_os.os) 
						FROM	tbl_os JOIN tbl_os_extra USING (os) 
						WHERE tbl_os_extra.extrato = tbl_extrato.extrato
					) AS qtde_os,
					to_char (tbl_extrato_pagamento.data_pagamento,'dd/mm/yyyy') as baixado
			FROM    tbl_extrato
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_extrato.posto          = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_extrato_pagamento ON tbl_extrato.extrato  = tbl_extrato_pagamento.extrato
			WHERE    tbl_extrato.fabrica = $login_fabrica
			AND      tbl_posto.posto     = $login_posto
			AND      tbl_posto_fabrica.distribuidor IS NULL
			GROUP BY tbl_posto.posto ,
					tbl_posto.nome ,
					tbl_posto.cnpj ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.distribuidor,
					tbl_extrato.extrato ,
					tbl_extrato.liberado ,
					tbl_extrato.total,
					tbl_extrato.data_geracao,
					tbl_extrato_pagamento.data_pagamento
			ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";
	echo nl2br($sql);
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		echo "<center><h2>Nenhum extrato encontrado</h2></center>";
	}
	
	if (pg_numrows ($res) > 0) {
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$posto   = trim(pg_result($res,$i,posto));
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$nome           = trim(pg_result($res,$i,nome));
			$extrato        = trim(pg_result($res,$i,extrato));
			$data_geracao   = trim(pg_result($res,$i,data_geracao));
			$qtde_os        = trim(pg_result($res,$i,qtde_os));
			$total          = trim(pg_result($res,$i,total));
			$baixado        = trim(pg_result($res,$i,baixado));
			$extrato        = trim(pg_result($res,$i,extrato));
			$distribuidor   = trim(pg_result($res,$i,distribuidor));
			$total	        = number_format ($total,2,',','.');
//			$liberado       = trim(pg_result($res,$i,liberado));

			if ($i == 0) {
				echo "<form name='Selecionar' method='post' action='$PHP_SELF'>\n";
				echo "<input type='hidden' name='btnacao' value=''>";
				echo "<table width='700' align='center' border='0' cellspacing='2'>\n";
				echo "<tr class = 'menu_top'>\n";
				echo "<td align='center'>Código</td>\n";
				echo "<td align='center' nowrap>Nome do Posto</td>\n";
				echo "<td align='center'>Extrato</td>\n";
				echo "<td align='center'>Data</td>\n";
				echo "<td align='center' nowrap>Qtde. OS</td>\n";
				echo "<td align='center'>Total</td>\n";
				echo "<td align='center'>Baixado em</td>\n";

				echo "</tr>\n";
			}
			
			echo "<tr>\n";
			
			echo "<td align='left'>$codigo_posto</td>\n";
			echo "<td align='left' nowrap>$nome</td>\n";
			echo "<td align='center'><a href = 'extrato_consulta_os.php?extrato=$extrato&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo'>$extrato</a></td>\n";
			echo "<td align='left'>$data_geracao</td>\n";
			echo "<td align='center'>$qtde_os</td>\n";
			echo "<td align='right' nowrap>R$ $total</td>\n";
			echo "<td align='left'>$baixado</td>\n";

			echo "</tr>\n";
		}
		echo "<tr>\n";
		echo "<td colspan='7'>&nbsp;</td>\n";

		echo "</tr>\n";
		echo "</table>\n";
		echo "</form>\n";
	}
	
	
	############################## DISTRIBUIDORES
	
	echo "<br><br>";
	
	$sql = "SELECT  tbl_posto.posto               ,
					tbl_posto.nome                ,
					tbl_posto.cnpj                ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.distribuidor,
					tbl_extrato.extrato           ,
					to_char (tbl_extrato.data_geracao,'dd/mm/yyyy') as data_geracao,
					tbl_extrato.total,
					(SELECT count (tbl_os.os) FROM tbl_os JOIN tbl_os_extra USING (os) WHERE tbl_os_extra.extrato = tbl_extrato.extrato) AS qtde_os,
					to_char (tbl_extrato_pagamento.data_pagamento,'dd/mm/yyyy') as baixado
			FROM    tbl_extrato
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			left JOIN    tbl_extrato_pagamento ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
			WHERE   tbl_extrato.fabrica = $login_fabrica
			AND     tbl_posto_fabrica.distribuidor NOTNULL ";
	
	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
	
	if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
	
	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0) 
	$sql .= " AND      tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	
	$xposto_codigo = str_replace (" " , "" , $posto_codigo);
	$xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("." , "" , $xposto_codigo);
	
	if (strlen ($posto_codigo) > 0 ) $sql .= " AND tbl_posto.cnpj = '$xposto_codigo' ";
	
	$sql .= " GROUP BY tbl_posto.posto ,
					tbl_posto.nome ,
					tbl_posto.cnpj ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.distribuidor,
					tbl_extrato.extrato ,
					tbl_extrato.liberado ,
					tbl_extrato.total,
					tbl_extrato.data_geracao,
					tbl_extrato_pagamento.data_pagamento
				ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		echo "<center><h2>Nenhum extrato encontrado</h2></center>";
	}
	
	if (pg_numrows ($res) > 0) {
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$posto   = trim(pg_result($res,$i,posto));
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$nome           = trim(pg_result($res,$i,nome));
			$extrato        = trim(pg_result($res,$i,extrato));
			$data_geracao   = trim(pg_result($res,$i,data_geracao));
			$qtde_os        = trim(pg_result($res,$i,qtde_os));
			$total          = trim(pg_result($res,$i,total));
			$baixado        = trim(pg_result($res,$i,baixado));
			$extrato        = trim(pg_result($res,$i,extrato));
			$distribuidor   = trim(pg_result($res,$i,distribuidor));
			$total	        = number_format ($total,2,',','.');
			
			if (strlen($distribuidor) > 0) {
				$sql = "SELECT  tbl_posto.nome                ,
								tbl_posto_fabrica.codigo_posto
						FROM    tbl_posto_fabrica
						JOIN    tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
						WHERE   tbl_posto_fabrica.posto   = $distribuidor
						AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
				$resx = pg_exec ($con,$sql);
				
				if (pg_numrows($resx) > 0) {
					$distribuidor_codigo = trim(pg_result($resx,0,codigo_posto));
					$distribuidor_nome   = trim(pg_result($resx,0,nome));
				}
			}
			
			if ($i == 0) {
				echo "<table width='700' align='center' border='0' cellspacing='2'>";
				echo "<tr class = 'menu_top'>";
				echo "<td align='center'>Código</td>";
				echo "<td align='center' nowrap>Nome do Posto</td>";
				echo "<td align='center'>Extrato</td>";
				echo "<td align='center'>Data</td>";
				echo "<td align='center' nowrap>Qtde. OS</td>";
				echo "<td align='center'>Total</td>";
				echo "<td align='center' colspan='2'>Extrato Vinculado a um Distribuidor</td>";
				echo "</tr>";
			}
			
			echo "<tr>";
			
			echo "<td align='left'>$codigo_posto</td>";
			echo "<td align='left' nowrap>$nome</td>";
			echo "<td align='center'>$extrato</td>";
			
			echo "<td align='left'>$data_geracao</td>";
			echo "<td align='center'>$qtde_os</td>";
			echo "<td align='right' nowrap>R$ $total</td>";
			echo "<td align='left' nowrap><font face='verdana' color='#FF0000' size='-2'>$distribuidor_codigo - $distribuidor_nome</font></td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}
?>
<p>
<p>
<? include "rodape.php"; ?>