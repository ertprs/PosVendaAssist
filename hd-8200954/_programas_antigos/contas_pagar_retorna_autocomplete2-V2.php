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
$atual= $page * $pageSize;

if(strlen($fornID) == 0){
?>	<table width="100%" cellpadding="2" cellspacing="0">
			<tr>
				<td bgcolor='yellow'>Selecione o Fornecedor!</td>
			</tr>
		</table>
<?
}else{
	$erro="";

/*		$sql="SELECT count(faturamento) as cont
			FROM TBL_FATURAMENTO
			WHERE distribuidor=$fornID and posto = $login_posto";
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
				AND   tbl_faturamento.faturamento > 704391 
				GROUP BY tbl_faturamento.vencimento             ,
					tbl_faturamento.faturamento             ,
					tbl_faturamento.nota_fiscal             ,
					tbl_faturamento.total_nota              ,
					tbl_faturamento.emissao                
				HAVING COALESCE(SUM(tbl_pagar.valor),0) < COALESCE(tbl_faturamento.total_nota,0)
				ORDER BY faturamento limit 10 OFFSET $atual; ";
		
		echo "$sql";
		$res	= pg_exec($con,$sql);

		if(pg_numrows($res)>0)  $total  = pg_numrows($res);
		else					$erro	= "true";



	if(!$erro){
		//$faturamento="<SELECT 'NAME='fat' id='fat' SIZE='7' MULTIPLE onKeyPress='selecionar(event);'>";
		?>
		<table width="100%" border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#d2e4fc'>
		<thead>
		<tr bgcolor='#aaaadd'  background='../admin/imagens_admin/azul.gif'>
			<td>Núm.</td>
			<td nowrap><b>Nota Fiscal</b></td>
			<td>Total</td>
			<td nowrap>Dt Emissão</td>
		</tr>
		</thead>
		<tbody>
		<?
if($total < $pageSize)
	$cont_reg =$total;
else
	$cont_reg= $pageSize;

		for ($i = 0; $i < $cont_reg; $i++){
			$fat = trim(pg_result($res, $i, faturamento));
			$nota_fiscal   = trim(pg_result($res, $i, nota_fiscal));
			$emissao       = trim(pg_result($res, $i, emissao));
			$total_nota    = trim(pg_result($res, $i, total_nota));
			$total_pago    = trim(pg_result($res, $i, total_pago));
			?>
			<tr onselect="this.text.value = '<?= $nota_fiscal?>';document.getElementById('nf').value = '<?= $nota_fiscal?>'; document.getElementById('faturamentoID').value='<?= $fat?>';  set_focus('nf', 0);">
	
				<?
					echo "<tr onselect='this.text.value = 'Documento: $nota_fiscal';document.getElementById('documento').value = '$nota_fiscal -'; set_focus('documento', 0); document.getElementById('faturamentoID').value=' $fat''>";
					echo "<td nowrap>No.".  ($page * $pageSize + $i) ."</td>";
					echo "<td nowrap><b> $nota_fiscal</b></td>";
					echo "<td nowrap align='right'>R$ ". number_format($total_nota, 2, ',', '') ."</td>";
					echo "<td nowrap><b> $emissao</b></td>";
					echo "<td nowrap align='right'><b>R$ ". number_format($total_pago, 2, ',', '') ."</b></td>";
					echo "<td nowrap align='right'>R$ ". number_format(($total_nota - $total_pago), 2, ',', '') ."</td>";
				echo "</tr>";
/*
				echo "<td>No.".  ($page * $pageSize + $i) ."</td>";
				echo "<td align='center'><b> $nota_fiscal</b></td>";
				echo "<td align='right'>$total_nota</td>";
				echo "<td><b> $emissao</b></td>";
*/				?>
			</tr>

			<?
				}
			?>
			<tr>
				<td colspan='4'>
				<table width='100%' border='0' >
			<?	
			$qt_pg = round($total /10);
			
			$um = 1;
			if(!$total)
				$um = 0;

			echo "<td width='25%' align='left' nowrap><a href='?page=0' style='float:left' class='page_up'><u class='paginacao'>Prim.</u></a></td>";	

			if ($page > 0){
				echo "<td width='25%' align='center' nowrap><a href='?page=" . ($page - 1) . "' style='float:left' class='page_up'><u>Ant.</u></a></td>";
			}else{
				echo "<td width='25%' align='center' nowrap>&nbsp;</td>";
			}
			echo "<td width='25%' align='center' nowrap>$page de $qt_pg</td>";
			//PRÓXIMO LINK
			if(($atual+10) < $total){
				echo "<td width='25%' align='center' nowrap><a href='?page=" . ($page + 1) .  "' style='float:right'  class='page_down'><u>Prox.</u></a></td>";
			}else{
				echo "<td width='25%' align='center' nowrap>&nbsp;</td>";
			}

			if(($qt_pg * 10) < $total){
				echo "<td width='25%' align='right' nowrap><a href='?page=" . ($qt_pg) . "' style='float:left' class='page_up'><u>Ult.</u></a></td>";							
			}else{
				echo "<td width='25%' align='right' nowrap><a href='?page=" . ($qt_pg-1) . "' style='float:left' class='page_up'><u>Ult.</u></a></td>";							
			}

			//(($atual+10) < $total) ? $tot = $atual+15 :	$tot = $total;
			
/*			if(($cont) > ($page * $pageSize)){
				echo "<td><a href='?page=" . ($page + 1) .  "' style='float:right'  class='page_down'><u>Prox.</u></a></td>";
			}
*/
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