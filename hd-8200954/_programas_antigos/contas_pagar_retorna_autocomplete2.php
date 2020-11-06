<?
//adicionado Igor
header("Content-Type: text/html; charset=ISO-8859-1",true);
//tutorial ajax
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
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
$atual= $page * $pageSize;

	$fornID = 4311;
	$post_ = 756 ;
if(strlen($fornID) == 0){
?>	<table width="100%" cellpadding="2" cellspacing="0">
			<tr>
				<td bgcolor='yellow'>Selecione o Fornecedor!</td>
			</tr>
		</table>
<?
}else{
	$erro="";


/*	$sql="SELECT count(faturamento) as cont
		FROM TBL_FATURAMENTO
		WHERE distribuidor=$fornID and posto = $login_posto";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0)  $total  = trim(pg_result($res, 0, cont));
	else $erro   = "true";
*/
	$sql="SELECT tbl_faturamento.vencimento             ,
						tbl_faturamento.faturamento             ,
						tbl_faturamento.nota_fiscal             ,
						tbl_faturamento.total_nota              ,
						SUM(tbl_pagar.valor) AS total_pago      ,
						TO_CHAR(emissao,'DD/MM/YYYY') as emissao
				FROM tbl_faturamento
				LEFT JOIN tbl_pagar using(faturamento)
				WHERE tbl_faturamento.posto = '$login_posto'
				AND   tbl_faturamento.distribuidor = '$fornID'
				AND   tbl_faturamento.faturamento > 700 
				GROUP BY tbl_faturamento.vencimento             ,
					tbl_faturamento.faturamento             ,
					tbl_faturamento.nota_fiscal             ,
					tbl_faturamento.total_nota              ,
					tbl_faturamento.emissao                
				HAVING COALESCE(SUM(tbl_pagar.valor),0) < COALESCE(tbl_faturamento.total_nota,0)
				ORDER BY faturamento limit 12 OFFSET $atual; ";
//echo $sql;
/*		$sql="SELECT faturamento,
				nota_fiscal	,
				TO_CHAR(emissao,'DD/MM/YYYY') as emissao,
				REPLACE(CAST(CAST(total_nota AS NUMERIC(12,2)) AS VARCHAR(14)),'.', ',') AS total_nota
		FROM TBL_FATURAMENTO
		WHERE distribuidor=$fornID and posto = $login_posto limit 10 OFFSET $atual";
*/			
		//WHERE fabrica = $fornID and posto = $login_posto limit 20";

	$res = pg_exec($con,$sql);

	if(pg_numrows($res) > 0) 
		$total  = pg_numrows($res);
	else 
		$erro   = "true";

	if(pg_numrows($res)==0)
		$erro = "true";

	if(!$erro){
		//$faturamento="<SELECT 'NAME='fat' id='fat' SIZE='7' MULTIPLE onKeyPress='selecionar(event);'>";
		?>
		<table width="100%" border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#d2e4fc'>
		<thead>
		<tr bgcolor='#aaaadd'  background='admin/imagens_admin/azul.gif'>

			<td>Núm.</td>
			<td nowrap><b>Nota Fiscal</b></td>
			<td nowrap>Total da Nota</td>
			<td>Emissão</td>
			<td nowrap>Total Pago</td>
			<td nowrap>A Pagar</td>
		</tr>
		</thead>
		<tbody>
		<?

if($total < $pageSize)
	$cont_reg =$total;
else
	$cont_reg= $pageSize;
		//echo "<tr><td>cont_reg: $cont_reg - total:$total - page: $page - pageSize:$pageSize </td></tr>";
		for ($i = 0; $i < $cont_reg; $i++){
			$fat = trim(pg_result($res, $i, faturamento));
			$nota_fiscal   = trim(pg_result($res, $i, nota_fiscal));
			$emissao       = trim(pg_result($res, $i, emissao));
			$total_nota    = trim(pg_result($res, $i, total_nota));
			$total_pago    = trim(pg_result($res, $i, total_pago));

			?>
			<tr onselect="this.text.value = '<?= $nota_fiscal?>';document.getElementById('nf').value = '<?= $nota_fiscal?>'; document.getElementById('faturamentoID').value='<?= $fat?>';  set_focus('nf', 0);">
				<?			
					//echo "<tr onselect='this.text.value = 'Documento: $nota_fiscal';document.getElementById('documento').value = '$nota_fiscal -'; set_focus('documento', 0); document.getElementById('faturamentoID').value=' $fat''>";
					echo "<td nowrap>No.".  ($page * $pageSize + $i) ."</td>";
					echo "<td nowrap><b> $nota_fiscal</b></td>";
					echo "<td nowrap align='right'>R$ ". number_format($total_nota, 2, ',', '') ."</td>";
					echo "<td nowrap><b> $emissao</b></td>";
					echo "<td nowrap align='right'><b>R$ ". number_format($total_pago, 2, ',', '') ."</b></td>";
					echo "<td nowrap align='right'>R$ ". number_format(($total_nota - $total_pago), 2, ',', '') ."</td>";
				echo "</tr>";
		}
		?>
		<tr>
			<td colspan='6'>
			<table width='100%' border='0' >
		<?	
		$qt_pg = round($total /$pageSize);
		
		$um = 1;
		if(!$total)
			$um = 0;
		
		// ############################## PAGINAÇÃO ##############################//
		//PRIMEIRO LINK
		/*echo "<td width='25%' align='left' nowrap><a href='?page=0' style='float:left' class='page_up'><u class='paginacao'>Prim.</u></a></td>";	
		*/

		if ($page > 0){
			echo "<td width='25%' align='center' nowrap><a href='?page=" . ($page - 1) . "' style='float:left' class='page_up'><u>Ant.</u></a></td>";
		}else{
			echo "<td width='25%' align='center' nowrap>&nbsp;</td>";
		}

		//MOSTRA A QUANTIDADE DE PAGINAS
		echo "<td width='50%' align='center' nowrap>página $page de $qt_pg </td>";

		//PRÓXIMO LINK
		if(($atual+$pageSize) < $total){
			echo "<td width='25%' align='center' nowrap><a href='?page=" . ($page + 1) .  "' style='float:right'  class='page_down'><u>Prox.</u></a></td>";
		}else{
			echo "<td width='25%' align='center' nowrap>&nbsp;</td>";
		}
		//ULTIMO LINK
		/*
		if(($qt_pg * 10) < $total){
			echo "<td width='25%' align='right' nowrap><a href='?page=" . ($qt_pg) . "' style='float:left' class='page_up'><u>Ult.</u></a></td>";							
		}else{
			echo "<td width='25%' align='right' nowrap><a href='?page=" . ($qt_pg-1) . "' style='float:left' class='page_up'><u>Ult.</u></a></td>";							
		}*/
		//############################### FIM PAGINAÇÃO ####################################//
		?>
		</tr>
		</table>
			</td>
		  </tr>
		<tbody>
		</table>
		<?
	}else{
		?>
		<table width="100%" cellpadding="2" cellspacing="0">
			<tr>
				<td bgcolor='yellow'>						
					<font style='font-size:10px;'>Sem faturamento cadastrado para o fornecedor selecionado! Digite o número da Nota Fiscal para cadastrar!
					</font>
				</td>
			</tr>
		</table>
	<?
	}
}

?>