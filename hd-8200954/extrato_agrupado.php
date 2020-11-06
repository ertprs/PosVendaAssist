<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
if($login_fabrica<>1 and $login_fabrica<>20) include "autentica_usuario_financeiro.php";


$msg_erro = "";
$layout_menu = "os";
$title = "Extratos";

include "cabecalho.php";
?>

<? include "javascript_calendario_new.php";?>
<script>
function verExtrato(codigo) {
	var codigo = document.getElementById(codigo);
	if (codigo.style.display){
		codigo.style.display = "";
	}else{
		codigo.style.display = "block";
	}
}

function verEncontroContas(nf) {
	var nf = document.getElementById(nf);
	if (nf.style.display){
		nf.style.display = "";
	}else{
		nf.style.display = "block";
	}
}

</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.esconde{
	display:none;
	text-align: center;
	font-family: Verdana, Tahoma, Arial, Geneva, Helvetica, sans-serif;
	font-size: 10 px;
}

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

<br/>
<form method=post action="">

	<? 
		$sql = " SELECT DISTINCT tbl_extrato_agrupado.codigo,
						(select to_char(tbl_extrato_conferencia.data_conferencia,'DD/MM/YYYY') 
						FROM  tbl_extrato_conferencia
						join tbl_extrato_agrupado ex_a using(extrato)
						WHERE cancelada IS NOT TRUE
						AND   ex_a.codigo = tbl_extrato_agrupado.codigo
						GROUP BY ex_a.codigo,
						tbl_extrato_conferencia.data_conferencia
						ORDER BY data_conferencia DESC LIMIT 1) as data_conferencia,
						(select tbl_extrato_conferencia.data_conferencia
						FROM  tbl_extrato_conferencia
						join tbl_extrato_agrupado ex_a using(extrato)
						WHERE cancelada IS NOT TRUE
						AND   ex_a.codigo = tbl_extrato_agrupado.codigo
						GROUP BY ex_a.codigo,
						tbl_extrato_conferencia.data_conferencia
						ORDER BY data_conferencia DESC LIMIT 1) as dt_conferencia,
						to_char(tbl_extrato_conferencia.previsao_pagamento,'DD/MM/YYYY') as previsao_pg
				FROM tbl_extrato
				JOIN tbl_extrato_conferencia USING(extrato)
				JOIN tbl_extrato_agrupado ON tbl_extrato_conferencia.extrato=tbl_extrato_agrupado.extrato
				WHERE posto = $login_posto
				AND   fabrica = $login_fabrica
				AND   cancelada IS NOT TRUE
				AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
				GROUP BY tbl_extrato_agrupado.codigo,
						tbl_extrato_conferencia.data_conferencia,
						tbl_extrato_conferencia.previsao_pagamento
				ORDER BY dt_conferencia DESC
				LIMIT 12";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			echo "<table width='850' border='1' cellspacing='2' Cellpadding='3'>";
			echo "<caption nowrap><b>Relação de conferência(s) / Previsão de Pagamento</b><br>Obs: No corpo da sua NF de MO. Colocar somente \"Código agrupador\"<br/>
			</caption>";
			echo "<thead>";
				echo "<tr class='menu_top'>";
					echo "<th>DT CONFERÊNCIA</th>";
					echo "<th>CÓDIGO AGRUPADOR</th>";
					echo "<th>EXTRATOS AGRUPADOS</th>";
					if($login_fabrica != 3){
						echo "<th>ENCONTRO DE CONTAS</th>";
					}
					echo "<th>NOTA FISCAL</th>";
					echo "<th>TOTAL DA NOTA FISCAL</th>";
					if($login_fabrica != 3){
						echo "<th>SALDO A PAGAR</th>";
					}
					echo "<th>PREVISÃO DE PGTO</th>";
				echo "</tr>";
			echo "</thead>";
			echo "<tbody>";
			for($i =0;$i<pg_num_rows($res);$i++) {
				$codigo           = pg_fetch_result($res,$i,codigo);
				$data_conferencia = pg_fetch_result($res,$i,data_conferencia);
				$previsao_pg      = pg_fetch_result($res,$i,previsao_pg);

				$extratos = "";
				$total="";
				$notas= "";
				$sqle = " SELECT DISTINCT to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') as extrato
							from tbl_extrato_conferencia
							JOIN tbl_extrato_agrupado USING(extrato)
							JOIN tbl_extrato ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
							WHERE cancelada IS NOT TRUE
							AND   codigo='$codigo'
							AND   posto = $login_posto
							AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
							AND   fabrica = $login_fabrica ";
				$rese = pg_query($con,$sqle);
				if(pg_num_rows($rese) > 0){
					for($j =0;$j<pg_num_rows($rese);$j++) {
						$extratos.= ($j > 0) ? "<br>" : "";
						$extratos.= pg_fetch_result($rese,$j,extrato);
					}
				}

				$sqle = " SELECT DISTINCT nota_fiscal
							from tbl_extrato_conferencia
							JOIN tbl_extrato_agrupado USING(extrato)
							JOIN tbl_extrato ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
							WHERE cancelada IS NOT TRUE
							AND   codigo='$codigo'
							AND   posto = $login_posto
							AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
							AND   fabrica = $login_fabrica ";
				$rese = pg_query($con,$sqle);
				$saldo = 0;
				if(pg_num_rows($rese) > 0){
					$notas = pg_fetch_result($rese,0,nota_fiscal);
					if(!empty($notas)) {
						$sqlnf = "SELECT	DISTINCT
											to_char(posto_data_transacao,'DD/MM/YYYY') as posto_data_transacao,
											nf_numero_nf                    ,
											nf_valor_do_encontro_contas     ,
											encontro_titulo_a_pagar         ,
											encontro_valor_liquido          
									FROM    tbl_encontro_contas
									WHERE   fabrica = $login_fabrica
									AND     posto = $login_posto
									AND     nf_numero_nf = '$notas' 
									AND     posto_data_transacao >current_date - interval '1 year' ";
						$resnf = pg_query($con,$sqlnf);
						$ver_nf_conta = "";
						if(pg_num_rows($resnf) > 0){
							$ver_nf_conta = "<a href='javascript:verEncontroContas(\"$notas\")'><u>ABATIMENTO DA MO</u></a>";
							$nf_contas  ="<table width='95%' border='1' cellspacing='2' Cellpadding='3'>";
							$nf_contas .= "<thead>";
							$nf_contas .= "<tr>";
							$nf_contas .= "<th colspan='3'>NOTA DE MÃO DE OBRA</th>";
							$nf_contas .= "<th colspan='2'>NOTAS DE COMPRA ABATIDAS DA MÃO DE OBRA</th>";
							$nf_contas .= "</tr>";
							$nf_contas .= "<tr>";
							$nf_contas .= "<th>Data Do Abatimento</th>";
							$nf_contas .= "<th>Nota de mão de obra</th>";
							$nf_contas .= "<th>Valor do encontro</th>";
							$nf_contas .= "<th>Nota de Compra</th>";
							$nf_contas .= "<th>Valor Abatido</th>";
							$nf_contas .= "</tr>";
							$nf_contas .= "</thead>";
							$nf_contas .= "<tbody>";

							for($n =0;$n<pg_num_rows($resnf);$n++) {
								$posto_data_transacao            = pg_fetch_result($resnf,$n,'posto_data_transacao');
								$nf_numero_nf                    = pg_fetch_result($resnf,$n,'nf_numero_nf');
								$nf_valor_do_encontro_contas     = pg_fetch_result($resnf,$n,'nf_valor_do_encontro_contas');
								$encontro_titulo_a_pagar         = pg_fetch_result($resnf,$n,'encontro_titulo_a_pagar');
								$encontro_valor_liquido          = pg_fetch_result($resnf,$n,'encontro_valor_liquido');
								
								$nf_contas .= "<tr style='text-align:center'>";
								$nf_contas .= "<td>$posto_data_transacao</td>";
								$nf_contas .= "<td>$nf_numero_nf</td>";
								$nf_contas .= "<td>".number_format($nf_valor_do_encontro_contas,2,",",".")."</td>";
								$nf_contas .= "<td>$encontro_titulo_a_pagar</td>";
								$nf_contas .= "<td>".number_format($encontro_valor_liquido,2,",",".")."</td>";
								$nf_contas .= "</tr>";
								$saldo = $nf_valor_do_encontro_contas;
							}
							$nf_contas .= "</tbody></table>";
						}
					}
				}

				$sqlt = "SELECT sum(tbl_extrato_conferencia_item.mao_de_obra)  as total
								FROM tbl_extrato
								JOIN tbl_extrato_agrupado USING(extrato)
								JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
								JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
								WHERE tbl_extrato_agrupado.codigo ='$codigo'
								AND   tbl_extrato.fabrica = $login_fabrica
								AND   tbl_extrato.posto  = $login_posto
								AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
								and   cancelada IS NOT TRUE";
				$rest = pg_query($con,$sqlt);
				$total = pg_fetch_result($rest,0,total);

				$sql_av = " SELECT
								extrato,
								historico,
								valor,
								tbl_extrato_lancamento.admin,
								debito_credito,
								lancamento
							FROM tbl_extrato_lancamento
							JOIN tbl_extrato_agrupado USING(extrato)
							WHERE tbl_extrato_agrupado.codigo='$codigo'
							AND fabrica = $login_fabrica
							AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
							AND (tbl_extrato_lancamento.admin IS NOT NULL OR lancamento in (103,104))";
				$res_av = pg_query ($con,$sql_av);

				$total_avulso = 0;

				if(pg_num_rows($res_av) > 0){
					for($k=0; $k < pg_num_rows($res_av); $k++){
						$valor           = trim(pg_fetch_result($res_av, $k, valor));
						$debito_credito  = trim(pg_fetch_result($res_av, $k, debito_credito));
						$lancamento      = trim(pg_fetch_result($res_av, $k, lancamento));
						
						if($debito_credito == 'D'){ 
							if ($valor>0){
								$valor = $valor * -1;
							}
						}
						$total_avulso = $valor + $total_avulso;
					}
				}

				$total +=$total_avulso;
				if(!empty($saldo)) {
					$saldo  = $total - $saldo;
				}
				if($total<0) {
					//HD 283715: O usuário questionou divergência no agrupamento. O problema é que quando o total é negativo não estava mostrando na tela, mas na hora de totalizar ele considera o valor negativo
					//$total = 0;
				}
				


				$cor = ($i%2) ? "#CCCCFF" : "#FFFFFF";
				echo "<tr class='table_line' bgcolor='$cor'>";
				echo "<td>$data_conferencia</td>";
				echo "<td><font color='red' size='2'><b>$codigo</b></font></td>";
				echo "<td><a href='javascript:verExtrato(\"$codigo\")'><u>VER EXTRATOS</u></a></td>";
				if($login_fabrica != 3){
					echo "<td nowrap>$ver_nf_conta</td>";
				}
				echo "<td>$notas</td>";
				echo "<td><b>",number_format($total,2,",","."),"</b></td>";
				if($login_fabrica != 3){
					echo "<td><b>",number_format($saldo,2,",","."),"</b></td>";
				}
				echo "<td>$previsao_pg</td>";
				echo "</tr>";

				echo "<tr class='table_line' bgcolor='#FFFFFF'>";
				echo "<td colspan='100%' align='center'>";
				echo "<div id='$codigo' class='esconde'>";
				$sqle = " SELECT DISTINCT to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') as				data_geracao,
							tbl_extrato.extrato
							from tbl_extrato_conferencia
							JOIN tbl_extrato_agrupado USING(extrato)
							JOIN tbl_extrato ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
							WHERE cancelada IS NOT TRUE
							AND   codigo='$codigo'
							AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
							AND   posto = $login_posto
							AND   fabrica = $login_fabrica
							ORDER BY tbl_extrato.extrato";
				$rese = pg_query($con,$sqle);
				if(pg_num_rows($rese) > 0){
					echo "<table width='95%' border='1' cellspacing='2' Cellpadding='3'>";
					echo "<thead>";
					echo "<tr>";
					echo "<th>Extrato</th>";
					echo "<th>Total</th>";
					echo "</tr>";
					echo "</thead>";
					echo "<tbody>";
					for($j =0;$j<pg_num_rows($rese);$j++) {

						$extrato = pg_fetch_result($rese,$j,extrato);

						$sql_av = " SELECT
								extrato,
								historico,
								valor,
								admin,
								debito_credito,
								lancamento
							FROM tbl_extrato_lancamento
							WHERE extrato = $extrato
							AND fabrica = $login_fabrica
							AND (admin IS NOT NULL OR lancamento in (103,104))";
						$res_av = pg_query ($con,$sql_av);

						$total_avulso = 0;

						if(pg_num_rows($res_av) > 0){
							for($k=0; $k < pg_num_rows($res_av); $k++){
								$valor           = trim(pg_fetch_result($res_av, $k, valor));
								$debito_credito  = trim(pg_fetch_result($res_av, $k, debito_credito));
								$lancamento      = trim(pg_fetch_result($res_av, $k, lancamento));
								
								if($debito_credito == 'D'){ 
									if ($lancamento == 78 AND $valor>0){
										$valor = $valor * -1;
									}
								}
								$total_avulso = $valor + $total_avulso;
							}
						}

						
						$sqlt = "SELECT sum(tbl_extrato_conferencia_item.mao_de_obra)  as total
								FROM tbl_extrato
								JOIN tbl_extrato_agrupado USING(extrato)
								JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
								JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
								WHERE tbl_extrato.extrato = $extrato
								AND   tbl_extrato.fabrica = $login_fabrica
								AND   tbl_extrato.posto  = $login_posto 
								AND   tbl_extrato_agrupado.aprovado   IS NOT NULL
								and   cancelada IS NOT TRUE";
						$rest = pg_query($con,$sqlt);
						$total = pg_fetch_result($rest,0,total);

						$total +=$total_avulso;
						if($total<0) {
							//HD 283715: O usuário questionou divergência no agrupamento. O problema é que quando o total é negativo não estava mostrando na tela, mas na hora de totalizar ele considera o valor negativo
							//$total = 0;
						}
						echo "<tr style='text-align:center'>";
						echo "<td>";
						echo pg_fetch_result($rese,$j,data_geracao);
						echo "</td>";
						echo "<td>";
						echo number_format($total,2,",",".");
						echo "</td>";
						echo "</tr>";
					}
					echo "</tbody>";
					echo "</table>";
				}
				echo "</div>";
				echo "<div id='$notas' class='esconde'>";
				echo $nf_contas;
				echo "</div>";
				echo "</td>";
				echo "</tr>";
			}
			echo "</tbody>";
			echo "</table>";
		}

		$sql = " SELECT  
					extrato_nota_avulsa,
					tbl_extrato_nota_avulsa.nota_fiscal   ,
					valor_original,
					to_char(data_geracao,'DD/MM/YYYY') as data_geracao,
					to_char(data_lancamento,'DD/MM/YYYY') as data_lancamento,
					to_char(data_emissao,'DD/MM/YYYY') as data_emissao,
					to_char(tbl_extrato_nota_avulsa.previsao_pagamento,'DD/MM/YYYY') as previsao_pagamento
				FROM tbl_extrato_nota_avulsa
				JOIN tbl_extrato USING(extrato)
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   posto   = $login_posto
				ORDER BY previsao_pagamento DESC limit 12";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			echo "<br/>";
			echo "<table width='850' border='1' cellspacing='2' Cellpadding='3'>";
			echo "<caption>Nota Avulsa do extrato</caption>";
			echo "<tr class='menu_top'>";
			echo "<td align='center'>Extrato</td>";
			echo "<td align='center'>NF</td>";
			echo "<td align='center'>Valor (R$)</td>";
			echo "<td align='center'>Previsão Pagamento</td>";
			echo "</tr>";
			for($i =0;$i<pg_num_rows($res);$i++) {
				$extrato_nota_avulsa= pg_fetch_result($res,$i,extrato_nota_avulsa);
				$data_lancamento   = pg_fetch_result($res,$i,data_lancamento);
				$nota_fiscal       = pg_fetch_result($res,$i,nota_fiscal);
				$data_emissao      = pg_fetch_result($res,$i,data_emissao);
				$data_geracao      = pg_fetch_result($res,$i,data_geracao);
				$valor_original    = number_format(pg_fetch_result($res,$i,valor_original),2,",","."); 
				$previsao_pagamento= pg_fetch_result($res,$i,previsao_pagamento);
				$cor = ($i%2) ? "#CCCCFF" : "#FFFFFF";

				echo "<tr style='font-size: 10px;text-align:center; background-color:$cor' >";
				echo "<td>$data_geracao</td>";
				echo "<td>$nota_fiscal</td>";
				echo "<td>$valor_original</td>";
				echo "<td nowrap>$previsao_pagamento</td>";
				echo "</tr>";
			}
			echo "</table>";
			
		}
	?>

	<br/>
	<center>
	<?php if($login_fabrica == 3){ ?>
		Para ter acesso ao extrato <a href="extrato_posto_novo.php">Clique Aqui</a> 
	<?php }else{ ?>
		<a href="extrato_posto_novo.php"><img border="0" src="imagens/btn_continuar.gif" align="absmiddle" style="cursor: hand" alt="Clique aqui continuar"></a>
	<?php } ?>
	</center>
</form>

<? include "rodape.php"; ?>
