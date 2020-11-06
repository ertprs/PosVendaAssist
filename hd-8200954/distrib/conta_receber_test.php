<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$btn_acao     = $_POST['btn_acao'];
$posto_codigo = trim($_POST['posto_codigo']);
$posto_nome   = trim($_POST['posto_nome']);
$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final'];
$mostrar      = $_POST['mostrar'];


?>

<html>
<head>
<title>Contas a Receber</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>


<? include 'menu.php' ?>

<style>

	.tabela caption{
		border-bottom:5px solid #0099CC;
		margin:0;
		padding:0;
	}
	.legenda{
		display:block;
		font-size:10px;
		color:#6C6C6C;
	}
	.mostrar{
		padding:3px;
		text-align:center;

	}
</style>

<center><h1>Contas a Receber</h1></center>

<p>
	<form name='frm_pesquisa' action='<?=$PHP_SELF?>' method='POST'>
	<table class='tabela'>
		<caption>Pesquisa</caption>
		<tr>
			<td>Código do Posto</td>
			<td>Nome do Posto</td>
		</tr>
		<tr>
			<td><input type='text' name='posto_codigo' size='15' value='<?=$posto_codigo?>'></td>
			<td><input type='text' name='posto_nome'   size='30' value='<?=$posto_nome?>'></td>
		</tr>
		<tr>
			<td>Data Inicial</td>
			<td>Data Final</td>
		</tr>
		<tr>
			<td><input type='text' name='data_inicial' size='12' maxlength='10' value='<?=$data_inicial?>'>
			<span class='legenda'>(Vencimento)</span></td>
			<td><input type='text' name='data_final' size='12' maxlength='10' value='<?=$data_final?>'
			><span class='legenda'>(Vencimento)</span></td>
		</tr>
		<tr>
			<td colspan='2'>
				<div class='mostrar'>
				Todas     <input type="radio" name="mostrar" value='todas' <?if($mostrar=='todas') echo " CHECKED ";?>>&nbsp;&nbsp;&nbsp;
				Pendentes <input type="radio" name="mostrar" value='pendentes' <?if($mostrar=='pendentes'  OR $mostrar=='') echo " CHECKED ";?>>&nbsp;&nbsp;&nbsp;
				Vencidas  <input type="radio" name="mostrar" value='vencidas' <?if($mostrar=='vencidas') echo " CHECKED ";?>>&nbsp;&nbsp;&nbsp;
				</div>
			</td>
		</tr>
		<tr>
			<td></td>
			<td><input type='submit' name='btn_acao' value='Pesquisar'></td>
		</tr>
	</table>
	</form>
</p>

<p>
<?

if ($btn_acao == 'Pesquisar'){

	if (strlen ($data_inicial) == 10) {
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
	}
	if (strlen ($data_final)   == 10) {
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
	}

	$sql = "SELECT  
					tbl_posto.nome, 
					tbl_posto.fone, 
					tbl_posto.email, 
					tbl_faturamento.nota_fiscal,
					TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') as emissao,
					tbl_contas_receber.documento, 
					tbl_faturamento.total_nota AS valor,
					(current_date - tbl_contas_receber.vencimento::date)::int4 AS dias_atraso,
					TO_CHAR(tbl_contas_receber.vencimento,'DD/MM/YYYY') as vencimento,
					valor_dias_atraso
			FROM tbl_posto
			JOIN tbl_contas_receber USING (posto)
			/* Tulio pediu para Samuel colocar left join 01/02/2010 */
			LEFT JOIN tbl_faturamento USING (faturamento_fatura)";

	if (strlen($posto_codigo)>0){
		$sql .= "JOIN (
					SELECT posto
					FROM tbl_posto_fabrica
					WHERE codigo_posto = '$posto_codigo'
				) post ON post.posto = tbl_contas_receber.posto";
	}

	$sql .= " WHERE tbl_contas_receber.distribuidor  = $login_posto";

	if ($mostrar=='vencidas'){
		$sql .=" AND tbl_contas_receber.recebimento IS NULL 
				 AND (current_date - tbl_contas_receber.vencimento::date)::int4 > 0";
	}
	if ($mostrar=='pendentes'){
		$sql .=" AND tbl_contas_receber.recebimento IS NULL ";
	}

	if (strlen($posto_nome)>0 AND strlen($posto_codigo)==0){
		$sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%'";
	}

	if (strlen($x_data_inicial)>0 AND strlen($x_data_final)>0){
		$sql .= " AND tbl_contas_receber.vencimento BETWEEN '$x_data_inicial' AND '$x_data_final'";
	}

	$sql .= "GROUP BY tbl_posto.nome, 
					tbl_posto.fone, 
					tbl_posto.email, 
					tbl_faturamento.nota_fiscal,
					tbl_faturamento.emissao,
					tbl_contas_receber.documento, 
					tbl_faturamento.total_nota,
					tbl_contas_receber.vencimento,
					valor_dias_atraso
			ORDER BY tbl_contas_receber.vencimento";
	#echo nl2br($sql);
	$res = pg_exec ($con,$sql);


	echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1' width='90%'>";
	echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
	echo "<td>Posto</td>";
	echo "<td>Fone</td>";
	echo "<td>EMail</td>";
	echo "<td>Nota Fiscal</td>";
	echo "<td>Emissão</td>";
	echo "<td>Documento</td>";
	echo "<td>Vencimento</td>";
	echo "<td>Valor</td>";
	echo "<td>Juros</td>";
	echo "<td>Total</td>";
	echo "</tr>";

	$total = 0 ;

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$dias_atraso         = pg_result($res,$i,dias_atraso);
		$valor_dias_atraso   = pg_result($res,$i,valor_dias_atraso);
		$juros_dias_atraso   = $dias_atraso * $valor_dias_atraso;
		$juros               = pg_result ($res,$i,valor) * 2 / 100;
		$tarifa_cancelamento = 6;
		if($dias_atraso<1) {
			$juros = 0;
			$juros_dias_atraso = 0;
		}
		$total_juros = $juros_dias_atraso + $juros + $tarifa_cancelamento;

		$nome        = pg_result ($res,$i,nome);
		$fone        = pg_result ($res,$i,fone);
		$email       = pg_result ($res,$i,email);
		$nota_fiscal = pg_result ($res,$i,nota_fiscal);
		$emissao     = pg_result ($res,$i,emissao);
		$documento   = pg_result ($res,$i,documento);
		$vencimento  = pg_result ($res,$i,vencimento);
		$valor       = pg_result ($res,$i,valor);

		$cor = "#cccccc";
		if ($i % 2 == 0) $cor = '#eeeeee';
		
		echo "<tr bgcolor='$cor'>";

		echo "<td>";
		echo $nome;
		echo "</td>";

		echo "<td>";
		echo $fone;
		echo "</td>";

		echo "<td>";
		echo str_replace(array(";"),"<br>",$email);
		echo "</td>";

		echo "<td>";
		echo $nota_fiscal;
		echo "</td>";

		echo "<td>";
		echo $emissao;
		echo "</td>";

		echo "<td>";
		echo $documento;
		echo "</td>";

		echo "<td>";
		echo $vencimento;
		echo "</td>";

		echo "<td align='right' width='100'>";
		echo number_format ($valor,2,",",".");
		echo "</td>";

		echo "<td align='right' width='100'>";
		echo number_format ($total_juros,2,",",".");
		echo "</td>";

		echo "<td align='right' width='100'>";
		$total_docto = $total_juros + $valor;
		echo number_format ($total_docto,2,",",".");
		echo "</td>";

		echo "</tr></a>";

		$total += $total_docto;

	}

	$total = number_format ($total,2,",",".");

	echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
	echo "<td colspan='9'>TOTAL EM ABERTO (Tarifa de cancelamento R$ ".number_format ($tarifa_cancelamento,2,",",".").")</td>";
	echo "<td align='right'>$total</td>";
	echo "</tr>";

	echo "</table>";

	echo "<P>";
}
?>


<? #include "rodape.php"; ?>

</body>
</html>
