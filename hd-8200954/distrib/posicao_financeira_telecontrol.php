<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$title = "Posição Financeira TELECONTROL";
$layout_menu = 'pedido';
?>

<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->

<p>

<?
$fabrica_pa = 3;
include "cabecalho.php";
$login_pa = trim($_GET['posto']);

#HD 217672
#---------------- Ainda não cobrados -----------------------

$sql = "SELECT tbl_faturamento.faturamento, tbl_faturamento.nota_fiscal, TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao, tbl_faturamento.total_nota, tbl_faturamento.transp 
		FROM tbl_faturamento
		WHERE tbl_faturamento.posto = $login_pa AND tbl_faturamento.distribuidor = 4311
		AND   tbl_faturamento.fabrica     IN (3,10,51,81)
		AND   tbl_faturamento.tipo_pedido IN (76, 77, 2,131, 153)
		AND   tbl_faturamento.faturamento_fatura IS NULL
		ORDER BY tbl_faturamento.nota_fiscal";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
	echo "<table width='500' border='1' cellpadding='2' cellspacing='0' align='center'>";
	echo "<tr bgcolor='#006600' style='color: #ffffff ; font-weight: bold ; font-size: 10px ' align='center'>";
	echo "<td colspan='4'>Boleto ainda não emitido</td>";
	echo "</tr>";

	echo "<tr bgcolor='#006600' style='color: #ffffff ; font-weight: bold ; font-size: 10px ' align='center'>";
	echo "<td>Nota Fiscal</td>";
	echo "<td>Emissão</td>";
	echo "<td>Transportadora</td>";
	echo "<td>Total da Nota</td>";
	echo "</tr>";
	
	$total_nao_cobrado = 0;
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		echo "<tr style='font-size:10px' >";

		echo "<td>";
		$faturamento = pg_result ($res,$i,faturamento);
		$nota_fiscal = pg_result ($res,$i,nota_fiscal);
		echo "<a href=nf_detalhe.php?faturamento=$faturamento target='_blank'>$nota_fiscal</a>";
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,emissao);
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,transp);
		echo "</td>";

		echo "<td align='right'>";
		echo number_format (pg_result ($res,$i,total_nota),2,",",".");
		echo "</td>";

		echo "</tr>";

		$total_nao_cobrado += pg_result ($res,$i,total_nota);

	}

	echo "<tr bgcolor='#006600' style='color: #ffffff ; font-weight: bold ; font-size: 10px ' align='center'>";
	echo "<td colspan='3'>Total ainda não cobrado</td>";
	echo "<td>" . number_format ($total_nao_cobrado,2,",",".") . "</td>";
	echo "</tr>";
	

	echo "</table>";
}


echo "<p>";

flush();

#---------------- Em Atraso -----------------------

$sql = "SELECT  tbl_faturamento.nota_fiscal                              ,
				TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
				tbl_faturamento.total_nota                               ,
				tbl_faturamento.transp                                   ,
				tbl_faturamento.faturamento_fatura                       ,
				tbl_faturamento.faturamento
		FROM     tbl_faturamento
		WHERE    tbl_faturamento.posto = $login_pa AND tbl_faturamento.distribuidor = 4311
		AND      tbl_faturamento.fabrica     IN (3,10,51,81)
		AND      tbl_faturamento.tipo_pedido IN (76, 77, 2,131, 153)
		AND      tbl_faturamento.faturamento_fatura IS NOT NULL
		AND      tbl_faturamento.faturamento_fatura IN (
			SELECT tbl_faturamento.faturamento_fatura 
			FROM   tbl_contas_receber
			JOIN   tbl_faturamento USING (faturamento_fatura)
			WHERE  (tbl_contas_receber.recebimento IS NULL OR tbl_contas_receber.recebimento > current_date - INTERVAL '90 days')
			AND    tbl_faturamento.posto       =  $login_pa
			AND    tbl_faturamento.fabrica     IN (3,10,51,81)
			AND    tbl_faturamento.tipo_pedido IN (76, 77, 2,131, 153)
			AND    tbl_faturamento.distribuidor = 4311
		)
		ORDER BY tbl_faturamento.faturamento_fatura , tbl_faturamento.nota_fiscal";

//	AND    tbl_contas_receber.status      IS NULL

//if ($ip == "201.0.9.216") echo $sql;
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
	echo "<table width='500' border='1' cellpadding='2' cellspacing='0' align='center'>";
	echo "<tr bgcolor='#000066' style='color: #ffffff ; font-weight: bold ; font-size: 10px ' align='center'>";
	echo "<td colspan='4'> Boletos </td>";
	echo "</tr>";

	$imprime_cabecalho = 1;
	$total_aberto = 0;
	$faturamento_fatura = "";

	for ($i = 0 ; $i < pg_numrows ($res)+1 ; $i++) {
		$faturamento_fatura_atual = "*";
		
		if ($i < pg_numrows ($res) ) {
			$faturamento_fatura_atual = pg_result ($res,$i,faturamento_fatura) ;
		}
		
		if ($faturamento_fatura <> $faturamento_fatura_atual ) {
			
			if (strlen ($faturamento_fatura) > 0) {
			
				$sql = "SELECT  tbl_distrib_devolucao.nota_fiscal                              ,
								to_char (tbl_distrib_devolucao.emissao,'DD/MM/YYYY') AS emissao,
								tbl_distrib_devolucao.total
						FROM    tbl_distrib_devolucao
						WHERE   tbl_distrib_devolucao.posto              = $login_pa
						AND     tbl_distrib_devolucao.faturamento_fatura = $faturamento_fatura
						AND     tbl_distrib_devolucao.distribuidor       = 4311";
				//if ($ip == "201.0.9.216") echo $sql; 
				$resX = pg_exec ($con,$sql);
				
				for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
					echo "<tr><td colspan='4'><table width='450' align='center'>";
					
					echo "<tr bgcolor='#ffffaa' style='font-size:10px' >";
					echo "<td colspan='3' align='center'>Créditos Lançados</td>";
					echo "</tr>";
					echo "<tr bgcolor='#ffffaa' style='font-size:10px' >";
					echo "<td> NF:<br> " . pg_result ($resX,$x,nota_fiscal) . "</td>";
					echo "<td> Emissão:<br> " . pg_result ($resX,$x,emissao) . "</td>";
					echo "<td> Valor:<br> " . number_format (pg_result ($resX,$x,total),2,",",".") . "</td>";
					echo "</tr>";
					
					echo "</table></td></tr>";
				}
				
				$sql = "SELECT  documento                                         ,
								nosso_numero                                      ,
								status                                            ,
								CASE WHEN vencimento < current_date then
									'vencido'
								ELSE
									'a vencer'
								END                                 AS vencido        ,
								TO_CHAR (vencimento, 'DD/MM/YYYY')  AS vencimento     ,
								TO_CHAR (recebimento, 'DD/MM/YYYY') AS recebimento    ,
								valor                                                 ,
								(current_date - vencimento::date)::int4 AS dias_atraso,
								valor_dias_atraso
						FROM    tbl_contas_receber
						WHERE   faturamento_fatura = $faturamento_fatura
						ORDER BY tbl_contas_receber.vencimento ";
				$resX = pg_exec ($con,$sql);
				
				for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
					$vencido     = pg_result($resX,$x,vencido);
					$dias_atraso = pg_result($resX,$x,dias_atraso);
					
					if ($dias_atraso > 0) {
						$valor_dias_atraso   = pg_result($resX,$x,valor_dias_atraso);
						$juros_dias_atraso   = $dias_atraso * $valor_dias_atraso;
						$juros               = pg_result ($resX,$x,valor) * 2 / 100;
						$tarifa_cancelamento = 6;
						$total_juros         = $juros_dias_atraso + $juros + $tarifa_cancelamento;
						$total_juros_total   = $total_juros_total + $total_juros;
						#echo $juros ."-". $juros_dias_atraso ."-". $total_juros;
					}
					
					$cor = "#ffffaa";
					if ($vencido == "vencido" and strlen(pg_result ($resX,$x,recebimento)) == 0) {
						$cor = "#ff0033";
						$recebimento = "Em aberto";
					}else{
						if ($vencido == "a vencer") {
							$recebimento = "A vencer";
						}else{
							$recebimento = pg_result ($resX,$x,recebimento);
						}
					}
					
					echo "<tr><td colspan='4'><table width='450' align='center'>";
					
					echo "<tr bgcolor='$cor' style='font-size:10px' >";
					echo "<td> Vencimento:<br> " . pg_result ($resX,$x,vencimento) . "</td>";
					echo "<td> Documento:<br> " . pg_result ($resX,$x,documento) . "</td>";
					echo "<td> Nosso Número:<br> " . pg_result ($resX,$x,nosso_numero) . "</td>";
					echo "<td> Pagamento:<br> $recebimento </td>";
					if ($dias_atraso > 0) {
						echo "<td> Valor:<br> " . number_format (pg_result ($resX,$x,valor),2,",",".") . "</td>";
						echo "<td> Acres:<br> " . number_format ($total_juros,2,",",".") . "</td>";
						echo "<td> Total:<br> " . number_format (pg_result ($resX,$x,valor) + $total_juros,2,",",".") . "</td>";
					}else{
						echo "<td> Valor:<br> " . number_format (pg_result ($resX,$x,valor),2,",",".") . "</td>";
					}
					echo "<td> Status:<br> " . pg_result ($resX,$x,status) . "</td>";
					echo "</tr>";
					
					echo "</table></td></tr>";
				}
			}

			if ($faturamento_fatura_atual <> "*") {
				$faturamento_fatura = pg_result ($res,$i,faturamento_fatura);
				$imprime_cabecalho = 1;
			}
		}

		if ($faturamento_fatura_atual <> "*") {
			if ($imprime_cabecalho == 1) {
				echo "<tr bgcolor='#000066' style='color: #ffffff ; font-weight: bold ; font-size: 10px ' align='center'>";
				echo "<td>Nota Fiscal</td>";
				echo "<td>Emissão</td>";
				echo "<td>Transportadora</td>";
				echo "<td>Total da Nota</td>";
				echo "</tr>";
				$imprime_cabecalho = 0 ;
			}
	
			echo "<tr style='font-size:10px' >";

			echo "<td>";
			$faturamento = pg_result ($res,$i,faturamento);
			$nota_fiscal = pg_result ($res,$i,nota_fiscal);
			echo "<a href=nf_detalhe.php?faturamento=$faturamento target='_blank'>$nota_fiscal</a>";
			#echo pg_result ($res,$i,nota_fiscal);
			echo "</td>";

			echo "<td>";
			echo pg_result ($res,$i,emissao);
			echo "</td>";

			echo "<td>";
			echo pg_result ($res,$i,transp);
			echo "</td>";

			echo "<td align='right'>";
			echo number_format (pg_result ($res,$i,total_nota),2,",",".");
			echo "</td>";

			echo "</tr>";

			$total_aberto += pg_result ($res,$i,total_nota);
		}

	}

	echo "<tr bgcolor='#000066' style='color: #ffffff ; font-weight: bold ; font-size: 12px ' align='center'>";
	echo "<td colspan='3'>Total sem juros</td>";
	echo "<td>" . number_format ($total_aberto,2,",",".") . "</td>";
	echo "</tr>";
	echo "<tr bgcolor='#000066' style='color: #ffffff ; font-weight: bold ; font-size: 12px ' align='center'>";
	echo "<td colspan='3'>Total com juros</td>";
	echo "<td>" . number_format ($total_juros_total+$total_aberto,2,",",".") . "</td>";
	echo "</tr>";
	

	echo "</table>";
}
?>

<p>