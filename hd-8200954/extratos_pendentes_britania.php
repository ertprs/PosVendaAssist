<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';

	$sql = "SELECT   extrato
					FROM  tbl_extrato 
					WHERE tbl_extrato.fabrica = $login_fabrica 
					AND   tbl_extrato.posto = $login_posto  
					ORDER BY  tbl_extrato.extrato DESC LIMIT 1";
	$res = pg_query($con,$sql);

	if(pg_numrows($res) > 0){
		
		$extrato = pg_result($res,0,extrato);

		if($login_fabrica == 3){
			$data_corte_britania = " and tbl_extrato.data_geracao > '2017-10-01 00:00:00' ";
			$join_extrato = " join tbl_extrato on tbl_extrato.extrato = tbl_faturamento.extrato_devolucao ";
		}

		$sqls = "SELECT  DISTINCT tbl_faturamento.extrato_devolucao
					FROM    tbl_faturamento 
					JOIN    tbl_faturamento_item USING (faturamento) 
					JOIN    tbl_peca             USING (peca)
					$join_extrato  
					WHERE   tbl_faturamento.extrato_devolucao <= $extrato
					$data_corte_britania
					AND     tbl_faturamento.fabrica = $login_fabrica
					AND     tbl_faturamento.posto             = $login_posto
					AND     tbl_faturamento.distribuidor IS NULL
					AND     (tbl_faturamento_item.devolucao_obrig IS TRUE or tbl_peca.produto_acabado IS TRUE)
					AND     tbl_faturamento.cfop IN ('694921','694922','694923','594919','594920','594921','594922','594923') 
					ORDER BY  tbl_faturamento.extrato_devolucao DESC ";
		$ress = pg_query ($con,$sqls);
		$res_qtdes = pg_num_rows ($ress);
		$resultados = pg_fetch_all($ress);
		if ($res_qtdes == 0){
			$msg_erro = "";
		}else{
			$extratos = array();
			foreach($resultados as $chave => $valor) {
				$sqlD = "SELECT extrato_devolucao
					FROM   tbl_faturamento
					WHERE  distribuidor = $login_posto 
					AND    extrato_devolucao in( $valor[extrato_devolucao])
					AND    fabrica = $login_fabrica";
				$resD = pg_query($con,$sqlD);
				
				if(pg_num_rows($resD) == 0){
					$sqld = " SELECT tbl_extrato.extrato,to_char(data_geracao,'DD/MM/YYYY') as data_extrato, tbl_extrato_agrupado.aprovado
							FROM tbl_extrato
							LEFT JOIN tbl_extrato_agrupado USING(extrato)
							WHERE extrato IN ($valor[extrato_devolucao])
							AND   fabrica = $login_fabrica
							AND   posto   = $login_posto
							AND   data_geracao > '2010-01-01 00:00:00'
							ORDER BY extrato DESC limit 1;";
					$resd = pg_query($con,$sqld);
					if(pg_num_rows($resd) > 0){
						$extrato_aux = pg_fetch_result($resd,0,'extrato');
						$data_extrato = pg_fetch_result($resd,0,'data_extrato');
						$aprovado = pg_fetch_result($resd,0,'aprovado');
						if(!empty($aprovado)) continue;
						
						$links .= "<tr>
									<td align='center'>
										<a href='extrato_posto_devolucao_lgr.php?extrato=$extrato_aux'>$extrato_aux</a>
									</td>
									<td align='center'>
										<a href='extrato_posto_devolucao_lgr.php?extrato=$extrato_aux'>$data_extrato</a>
									</td>
									</tr>";
					}

				}
			}
		}
	}

$layout_menu = 'os';
$title       = "EXTRATOS";

include "cabecalho.php";
?>

<style type="text/css">

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
font:13px Arial;
text-align:left;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 13px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>


<?php

	
	if(!empty($links)){
		$msg_erro="Devem ser preenchidas as Notas Fiscais de devolução de Produtos e peças dos extratos anteriores para liberar a tela de consulta de valores de mão-de-obra - extrato";
	?>
		<br />
		<table align="center" width="700">
			<tr>
				<td align="center"><b>Extratos sem o preenchimento de produtos ou peças - LGR</b></td>
			</tr>
		</table>
		<br />
		<table align="center" width="700" cellspacing="0" cellpadding="0" class="formulario">
			<tr class="msg_erro"><td><?php echo $msg_erro; ?></td></tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td align="center">
					<table width="500" class="tabela">
						<tr class="titulo_coluna"><td>Extrato</td><td>Data</td></tr>
						<?php echo $links; ?>
					</table>
				</td>
			</tr>
		</table>

		<br />

	<?php
		
	} else {
		echo "<meta http-equiv='Refresh' content='0 ; url=extrato_agrupado.php' />";
		exit;
	}

	include "rodape.php";
?>
