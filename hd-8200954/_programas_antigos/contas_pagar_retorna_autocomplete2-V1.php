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


		$sql="SELECT count(faturamento) as cont
			FROM TBL_FATURAMENTO
			WHERE distribuidor=$fornID and posto = $login_posto";

		$res	= pg_exec($con,$sql);

		if(pg_numrows($res)>0)  $total  = trim(pg_result($res, 0, cont));
		else					$erro	= "true";

		$sql="SELECT faturamento,
					nota_fiscal	,
					TO_CHAR(emissao,'DD/MM/YYYY') as emissao,
					REPLACE(CAST(CAST(total_nota AS NUMERIC(12,2)) AS VARCHAR(14)),'.', ',') AS total_nota
			FROM TBL_FATURAMENTO
			WHERE distribuidor=$fornID and posto = $login_posto limit 10 OFFSET $atual";
			
			//WHERE fabrica = $fornID and posto = $login_posto limit 20";

			$res	= pg_exec($con,$sql);

	if(pg_numrows($res)==0)
		$erro = "true";	

	if(!$erro){
		//$faturamento="<SELECT 'NAME='fat' id='fat' SIZE='7' MULTIPLE onKeyPress='selecionar(event);'>";
		?>
<style type="text/css">
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}
.paginacao{
	font-family: Verdana;
	font-size: 8px;
	color: #aaaadd;
}

</style>
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
		$cont_reg= ($total - ($page * $pageSize));
		if($cont_reg > 10){
			//echo "cont_reg eh maior que 10 c:$cont_reg";
			$cont_reg=10;
		}

		for ($i = 0; $i < $cont_reg; $i++){
			$fat			= trim(pg_result($res, $i, faturamento));
			$nota_fiscal	= trim(pg_result($res, $i, nota_fiscal));
			$emissao		= trim(pg_result($res, $i, emissao));
			$total_nota		= trim(pg_result($res, $i, total_nota));
			?>
			<tr onselect="this.text.value = '<?= $nota_fiscal?>';document.getElementById('nf').value = '<?= $nota_fiscal?>'; document.getElementById('faturamentoID').value='<?= $fat?>';  set_focus('nf', 0);">
				<?
				echo "<td>No.".  ($page * $pageSize + $i) ."</td>";
				echo "<td align='center'><b> $nota_fiscal</b></td>";
				echo "<td align='right'>$total_nota</td>";
				echo "<td><b> $emissao</b></td>";
				?>
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