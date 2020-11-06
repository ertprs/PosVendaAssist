<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
?>
<html>
<head>
<title>Consulta Devolução de Peças Telecontrol</title>
</head>
<body>
<? include 'menu.php' ?>

<center><h1>Consulta Devolução de Peças Telecontrol</h1></center>

<center><h3>Últimos 3 Meses</h3></center>
<span style='background-color:#F76B5B;color:#F76B5B'>__</span> Não consta devolução de Peças<br><br>
<center>

<?

$btn_acao = $_POST['btn_acao'];
if ($btn_acao == "gravar_conferencia"){
	$qtde_linhas = $_POST['qtde_linhas'];
	for($w=0; $w<$qtde_linhas; $w++){
		$ext = $_POST['extrato_'.$w];
		$fab = $_POST['fabrica_'.$w];
		if (strlen($ext)>0){
			$sql = "UPDATE tbl_faturamento
					SET conferencia = CURRENT_TIMESTAMP
					WHERE posto           = $login_posto
					AND fabrica           = $fab
					AND extrato_devolucao = $ext";
			$res = pg_exec ($con,$sql);
		}
	}
}

?>
<?

$sql = "SELECT  DISTINCT 
				tbl_faturamento.fabrica,
				tbl_fabrica.nome as nome_fabrica,
				tbl_faturamento.posto,
				tbl_posto.nome,
				tbl_posto_fabrica.codigo_posto,
				tbl_faturamento.extrato_devolucao,
				to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') as data_geracao
		FROM    tbl_faturamento 
		JOIN    tbl_posto USING(posto)
		JOIN    tbl_extrato ON tbl_extrato.extrato = tbl_faturamento.extrato_devolucao
		JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = tbl_faturamento.fabrica
		JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica
		WHERE   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
		AND     tbl_faturamento.distribuidor = $login_posto
		AND     tbl_faturamento.extrato_devolucao IS NOT NULL
		AND     tbl_faturamento.emissao > CURRENT_DATE - interval ' 5 month'
		AND     tbl_extrato.extrato NOT IN (147310,147267,147253,147214,147421,147404,146887,147564,147525,146896,157051,157012,156691,157100,156615,156975,156974,156878,156854,156827,156801,156585,156741,156705,156939,156922,156369,156361,156756,156908,156889)
		ORDER BY extrato_devolucao DESC, posto ASC";
		//echo $sql;
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {

	$qtde_for = pg_numrows ($res);

	echo "<form name='frm_conferencia' method='post' action='$PHP_SELF'>";
	echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650px' >";
	echo "<tr>";
	echo "<td><b>Fábrica</b></td>";
	echo "<td><b>Posto</b> </td>";
	echo "<td><b>Nome</b></td>";
	echo "<td><b>Extrato</b></td>";
	echo "<td><b>Data do Extrato</b></td>";
	echo "<td><b>Total Nota</b></td>";
	echo "<td><b>NF</b></td>";
	echo "<td><b>Conferência</b></td>";
	echo "</tr>";
flush();
	for ($i=0; $i < $qtde_for; $i++) {
			$id_fabrica           = pg_result ($res,$i,fabrica);
			$nome_fabrica         = pg_result ($res,$i,nome_fabrica);
			$posto                = pg_result ($res,$i,posto);
			$nome                 = pg_result ($res,$i,nome);
			$codigo_posto         = pg_result ($res,$i,codigo_posto);
			$data_geracao         = pg_result ($res,$i,data_geracao);
			$extrato_devolucao    = pg_result ($res,$i,extrato_devolucao);
			
			$sql = "SELECT  tbl_faturamento.faturamento, 
							tbl_faturamento.nota_fiscal, 
							tbl_peca.peca, 
							tbl_peca.referencia, 
							tbl_peca.descricao, 
							tbl_peca.ipi, 
							tbl_faturamento_item.aliq_icms,
							tbl_faturamento_item.aliq_ipi,
							SUM (tbl_faturamento_item.qtde) AS qtde, 
							SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco ) AS total_item, 
							SUM (tbl_faturamento_item.base_icms) AS base_icms, 
							SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
							SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi,
							SUM (tbl_faturamento_item.base_ipi) AS base_ipi
					FROM tbl_peca
					JOIN tbl_faturamento_item USING (peca)
					JOIN tbl_faturamento      USING (faturamento)
					WHERE tbl_faturamento.fabrica = $id_fabrica
					AND   tbl_faturamento.posto   = $posto
					AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
					AND     tbl_faturamento_item.aliq_icms > 0 ";
					if($id_fabrica == 51){
						$sql .= "AND     tbl_peca.devolucao_obrigatoria = 't' ";
					}
					$sql .= "AND     tbl_faturamento.distribuidor = $login_posto
					AND     tbl_faturamento.extrato_devolucao IS NOT NULL
					AND     tbl_faturamento.extrato_devolucao>140927
					AND     tbl_faturamento.extrato_devolucao=$extrato_devolucao
					GROUP BY tbl_faturamento.faturamento, tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms, tbl_faturamento.nota_fiscal,tbl_faturamento_item.aliq_ipi
					ORDER BY tbl_peca.referencia ";

			$resX = pg_exec ($con,$sql);
			$qtde_itens = @pg_numrows ($resX);

			$total_base_icms  = 0;
			$total_valor_icms = 0;
			$total_base_ipi  = 0;
			$total_valor_ipi = 0;
			$total_nota       = 0;
			$aliq_final       = 0;

			for ($x = 0 ; $x < $qtde_itens ; $x++) {

				$peca        = pg_result ($resX,$x,peca);
				$qtde        = pg_result ($resX,$x,qtde);
				$total_item  = pg_result ($resX,$x,total_item);
				$base_icms   = pg_result ($resX,$x,base_icms);
				$valor_icms  = pg_result ($resX,$x,valor_icms);
				$aliq_icms   = pg_result ($resX,$x,aliq_icms);
				$base_ipi    = pg_result ($resX,$x,base_ipi);
				$aliq_ipi    = pg_result ($resX,$x,aliq_ipi);
				$valor_ipi   = pg_result ($resX,$x,valor_ipi);
				$ipi         = pg_result ($resX,$x,ipi);
				$preco       = round ($total_item / $qtde,2);
				$total_item  = $preco * $qtde;
				$nota_fiscal = pg_result ($resX,$x,nota_fiscal);
				$faturamento = pg_result ($resX,$x,faturamento);

				if (strlen ($base_icms)  == 0) $base_icms = $total_item ;
				if (strlen ($valor_icms) == 0) $valor_icms = round ($total_item * $aliq_icms / 100,2);


				if (strlen($aliq_ipi)==0) $aliq_ipi=0;
				if ($aliq_ipi==0) 	{
					$base_ipi=0;
					$valor_ipi=0;
				}else {
					$base_ipi=$total_item;
					$valor_ipi = $total_item*$aliq_ipi/100;
				}
				
				if ($base_icms > $total_item) $base_icms = $total_item;
				if ($aliq_final == 0) $aliq_final = $aliq_icms;
				if ($aliq_final <> $aliq_icms) $aliq_final = -1;

				$total_base_icms  += $base_icms;
				$total_valor_icms += $valor_icms;
				$total_base_ipi	  += $base_ipi;
				$total_valor_ipi  += $valor_ipi;
				$total_nota       += $total_item;
			}

			if ($aliq_final > 0) {
				$total_valor_icms = round ($total_base_icms * $aliq_final / 100,2);
			}

			$total = number_format ($total_nota+$total_valor_ipi,2,",",".");

			$sql = "SELECT  faturamento,
							nota_fiscal, 
							TO_CHAR(emissao,'DD/MM/YYYY') AS emissao,
							TO_CHAR(conferencia,'DD/MM/YYYY') AS conferencia
					FROM tbl_faturamento
					WHERE posto           = $login_posto
					AND distribuidor      = $posto
					AND fabrica           = $id_fabrica
					AND extrato_devolucao = $extrato_devolucao
					ORDER BY tbl_faturamento.conferencia DESC ";
			$res3 = pg_exec ($con,$sql);
			$qtde_devolvida=pg_numrows ($res3);
			$notas    = array();
			$emissoes = array();
	
			for ($j=0; $j<$qtde_devolvida; $j++){
				array_push($notas,pg_result ($res3,$j,nota_fiscal));
				array_push($emissoes,pg_result ($res3,$j,emissao));
				$conferencia = pg_result ($res3,$j,conferencia);
			}

			$cor = "white";
			if ($qtde_devolvida == 0){
				$cor = "#F76B5B";
			}
			if (strlen($conferencia)>0){
				$cor = "#AAD9FF";
			}
			if($total == '0,00') continue;
			echo "<tr bgcolor='$cor'>\n";
			echo "<td>$nome_fabrica</td>\n";
			echo "<td>$codigo_posto</td>\n";
			echo "<td>$nome</td>\n";
			echo "<td>$extrato_devolucao</td>\n";
			echo "<td><acronym title='Notas: ".implode(",",$notas)." - Emissão: ".$emissoes[0]."'> $data_geracao </acronym> </td>\n";
			echo "<td><acronym title='Notas: ".implode(",",$notas)." - Emissão: ".$emissoes[0]."'>".substr(implode(",",$notas),0,40)."..</td>";
			echo "<td align='right'>$total</td>";
			if (strlen($conferencia)>0){
				echo "<td align='center'>$conferencia</td>";
			}elseif ($qtde_devolvida == 0){
				echo "<td align='center'>-</td>";
			}else{
				echo "<td align='center'><input type='checkbox' name='extrato_$i' value='$extrato_devolucao'>
				<input type='hidden' name='fabrica_$i' value='$id_fabrica'>
				</td>";
			}
			echo "</tr>\n";
			flush();
		}
	echo "</table>";
	echo "<br>";
	echo "<br>";
	echo "<input type='hidden' name='qtde_linhas' value='$i'>";
	echo "<input type='hidden' name='btn_acao' value='gravar_conferencia'>";
	echo "<input type='button' name='btn_gravar' value='Gravar' onClick='this.form.submit()'>";
	echo "</form>";
	}

?>

<? include "rodape.php"; ?>
