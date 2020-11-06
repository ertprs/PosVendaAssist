<?
//adicionado Igor
header("Content-Type: text/html; charset=ISO-8859-1",true);

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario.php';
//include 'autentica_admin.php';

	$fornID	= $_GET["fornID"];
	$acao	= $_GET["acao"];
	$typing = $_GET["typing"];

	if (!isset($_GET["page"]))
		$page = 0;
	else 
		$page = $_GET["page"];

    $pageSize = 10;
$fornID = 4311;
$posto_ = 756;
	if(strlen($fornID) == 0){
	?>	<table width="100%" cellpadding="2" cellspacing="0">
				<tr>
					<td bgcolor='yellow'>Selecione o Fornecedor!</td>
				</tr>
			</table>
	<?
	}else{
		$sql="SELECT tbl_faturamento.vencimento             ,
					tbl_faturamento.faturamento             ,
					tbl_faturamento.nota_fiscal             ,
					tbl_faturamento.total_nota              ,
					SUM(tbl_pagar.valor) AS total_pago      ,
					TO_CHAR(emissao,'DD/MM/YYYY') as emissao
			FROM tbl_faturamento
			LEFT JOIN tbl_pagar using(faturamento)
			WHERE tbl_faturamento.posto = '$posto_'
			AND   tbl_faturamento.distribuidor = '$fornID'
			AND   tbl_faturamento.faturamento > 704391 
			GROUP BY tbl_faturamento.vencimento             ,
					tbl_faturamento.faturamento             ,
					tbl_faturamento.nota_fiscal             ,
					tbl_faturamento.total_nota              ,
					tbl_faturamento.emissao                
			ORDER BY faturamento DESC 
			LIMIT 10; ";
		echo $sql;
		$res = pg_exec($con,$sql);

		$cont = pg_numrows($res);

		if(pg_numrows($res)>0){
				//$faturamento="<SELECT 'NAME='fat' id='fat' SIZE='7' MULTIPLE onKeyPress='selecionar(event);'>";
			?>
				<table width="100%" border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#d2e4fc'>
				<tr bgcolor='#aaaadd'  background='../admin/imagens_admin/azul.gif'>
					<td>Núm.</td>
					<td><b>Nota Fiscal</b></td>
					<td>Total da Nota</td>
					<td>Vencimento</td>
					<td>Total Pago</td>
					<td>A Pagar</td>
				</tr>
			<?
				for ($i = 0; $i <pg_numrows($res); $i++)
				{
					$fat = trim(pg_result($res, $i, faturamento));
					$nota_fiscal   = trim(pg_result($res, $i, nota_fiscal));
					$emissao       = trim(pg_result($res, $i, emissao));
					$total_nota    = trim(pg_result($res, $i, total_nota));
					$total_pago    = trim(pg_result($res,$i, total_pago));
//					$total_pagar   = trim(pg_result($res, $i, total_pagar));

					if($total_nota > $total_pago){
						echo "<tr onselect='this.text.value = 'Documento: $nota_fiscal';document.getElementById('documento').value = '$nota_fiscal -'; set_focus('documento', 0); document.getElementById('faturamentoID').value=' $fat''>";
						echo "<td>No.".  ($page * $pageSize + $i) ."</td>";
						echo "<td><b> $nota_fiscal</b></td>";
						echo "<td>R$ ". number_format($total_nota, 2, ',', '') ."</td>";
						echo "<td><b> $emissao</b></td>";
						echo "<td><b>R$ ". number_format($total_pago, 2, ',', '') ."</b></td>";
						echo "<td>R$ ". number_format(($total_nota - $total_pago), 2, ',', '') ."</td>";
					}
					echo "</tr>";
				}
				echo "</table>";
			if ($page > 0)
			{
			   echo "<a href='?page=" . ($page - 1) . "' style='float:left' class='page_up'><u>Ant.</u></a>";
			}

			if(($cont / $pageSize) > ($page * $pageSize)){
				echo "<a href='?page=" . ($page + 1) .  "' style='float:right'  class='page_down'><u>Prox.</u></a>";
			}
		}else{
			?>
			<table width="100%" cellpadding="2" cellspacing="0">
				<tr>
					<td>Núm.</td>
					<td><b>Nota Fiscal</b></td>
					<td>Total da Nota</td>
				</tr>
			</table>
				<?
		}
	}

?>