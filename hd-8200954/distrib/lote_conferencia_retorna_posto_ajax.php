<?
//adicionado Igor
header("Content-Type: text/html; charset=ISO-8859-1",true);
//tutorial ajax
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$codigo_posto = $_GET["codigo_posto"];

if(strlen($codigo_posto) == 0){
	echo "ok|<table width='100%' cellpadding='2' cellspacing='0'>
			<tr>
				<td bgcolor='yellow'>Posto não encontrado!</td>
			</tr>
		</table>";
}else{

	$sql="select tbl_posto.posto,nome  
			from tbl_posto_fabrica 
			join tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto 
			where codigo_posto='$codigo_posto';";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		$posto = trim(pg_result($res, 0, posto));
		$nome  = trim(pg_result($res, 0, nome));

		$sql="	SELECT tbl_distrib_lote_posto.*, lote 
				FROM tbl_distrib_lote_posto 
				JOIN tbl_distrib_lote using(distrib_lote)
				WHERE posto =$posto;";
		$res = pg_exec($con,$sql);

		echo "ok|";
		//exit;
	?>
		<font color='#0066FF'>Conferência por Posto</font>
		<br>
		<font color='#000033'>Posto:<b><? echo $nome;?></b></font>
		<table class ='table_line' width="100%" border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#d2e4fc'>
		<thead>
		<tr bgcolor='#aaaadd'  background='../admin/imagens_admin/azul.gif'>
			<td width='20'>LOTE</td>
			<td width='20'>N.F. M.OBRA</td>
			<td width='20'>Val. M.OBRA</td>
			<td width='20'>N.F. Devolução</td>
			<td width='20'>Valor DEV</td>
			<td width='20'>ICMS DEV</td>
		</tr>
		</thead>

		<?
		for ($i = 0; $i < pg_numrows($res); $i++){
			$lote				   = trim(pg_result($res, $i, lote));
			$nf_mobra              = trim(pg_result($res, $i, nf_mobra));
			$valor_mobra           = trim(pg_result($res, $i, valor_mobra));
			$nf_devolucao          = trim(pg_result($res, $i, nf_devolucao));
			$valor_devolucao       = trim(pg_result($res, $i, valor_devolucao));
			$icms_devolucao        = trim(pg_result($res, $i, icms_devolucao));

			echo "<tr>";
			echo "<td nowrap align='center'><b>$lote</b></td>";
			echo "<td nowrap><b> $nf_mobra</b></td>";
			echo "<td nowrap align='right'>R$ ". number_format($valor_mobra, 2, ',', '') ."</td>";
			echo "<td nowrap><b> $nf_devolucao</b></td>";
			echo "<td nowrap align='right'><b>R$ ". number_format($valor_devolucao, 2, ',', '') ."</b></td>";
			echo "<td nowrap align='right'>R$ ". number_format(($icms_devolucao), 2, ',', '') ."</td>";
			echo "</tr>";
		}
		?>
	
		</table>
		<?
	}else{
		echo "ok|";
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