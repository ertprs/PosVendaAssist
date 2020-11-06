<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros,call_center,gerencia";
include "autentica_admin.php";

$sql = "
	SELECT admin
	 FROM tbl_admin
	 WHERE admin = '$login_admin'
	AND responsavel_postos = 't';
	   ";
	// echo nl2br($sql);
	// exit;
$res = pg_query($con, $sql);
if (pg_num_rows($res) == 0){

	if($login_fabrica == 189 AND $login_privilegios == "*"){
		header('Location: acompanhamento_atendimentos.php');
	}else{
		header('Location: menu_cadastro.php');
	}
	exit;

}else {

	$sql_r = "SELECT tbl_posto_fabrica.posto

				FROM
					tbl_posto_fabrica
					JOIN tbl_posto USING(posto)
					JOIN tbl_credenciamento ON tbl_posto_fabrica.posto=tbl_credenciamento.posto
					AND tbl_posto_fabrica.fabrica=tbl_credenciamento.fabrica

				WHERE
					tbl_posto_fabrica.fabrica=$login_fabrica
					AND tbl_posto_fabrica.credenciamento='EM DESCREDENCIAMENTO'
					AND tbl_credenciamento.status='EM DESCREDENCIAMENTO'
				";
	//echo nl2br($sql_r);exit;
	$res_r = pg_exec($con,$sql_r);

	if (pg_num_rows($res_r) == 0){
		?>
			<script>
				window.location.href="hd_aguarda_aprovacao.php";
			</script>
		<?php
	}

	$layout_menu = "cadastro";
	$title = traduz("VENCIMENTO DO PRAZO EM DESCREDENCIAMENTO");


#Relatório de Extratos não Baixados - INICIO

	if ($_GET['listaExtrato'])
	{
		$posto = $_GET['codPosto'];

		if($login_fabrica == 1){
			$sql = "SELECT  tbl_extrato.extrato ,
							to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
							tbl_extrato.posto ,
							tbl_posto_fabrica.codigo_posto ,
							tbl_extrato.fabrica ,
							tbl_extrato.protocolo ,
							tbl_extrato.total,
							to_char(tbl_extrato_financeiro.pagamento, 'DD/MM/YYYY') AS pagamento,
							to_char(tbl_extrato.aprovado, 'DD/MM/YYYY') AS aprovado
					FROM tbl_extrato
					JOIN tbl_posto_fabrica ON tbl_extrato.posto=tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_extrato_financeiro ON tbl_extrato.extrato=tbl_extrato_financeiro.extrato
					WHERE tbl_extrato.fabrica=$login_fabrica
						  AND       tbl_extrato.aprovado              NOTNULL
						  AND       tbl_extrato_financeiro.data_envio IS NULL
						  AND tbl_posto_fabrica.codigo_posto = '$posto'
					ORDER BY tbl_extrato.data_geracao DESC;";

			$res = pg_query ($con,$sql);
			//echo nl2br($sql);

			$qtde_extratos = pg_num_rows ($res);

			if ($qtde_extratos == 0) {
				echo "<center><div id ='extrato_$posto' class='alert alert-error' style='display:none;'>".traduz("Não foram encontrados extratos não baixados para este Posto.")." <br /> <input type='button' class='btn' value='Fechar' onclick=\"fechaDiv('extrato_$posto')\"></div></center>";
				exit();
			} else{

				echo "<table class='table table-striped' id='extrato_$posto' style='display:none;'>";
					echo "<thead><tr class='titulo_coluna'>";
						echo "<th>".traduz("Cod. <br> Posto")."</th>";
						echo "<th>".traduz("Extrato")."</th>";
						echo "<th>".traduz("Data")."</th>";
						echo "<th>".traduz("Total <br> Geral")."</th>";
					echo "</tr></thead>";

					for ($i=0; $i < pg_numrows($res); $i++){

						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

						$extrato        = pg_result($res,$i,extrato);
						$data_geracao   = pg_result($res,$i,data_geracao);
						$protocolo      = pg_fetch_result($res,$i,protocolo);
						$total          = trim(pg_fetch_result($res,$i,total));
						$total	        = number_format ($total,2,',','.');



						echo "<tr>";
							echo "<td class='tac'>$posto</td>";
							echo "<td  class='tac'><a href='os_extrato_detalhe_print_blackedecker_new.php?extrato=$extrato' target='_blank'>$protocolo</a></td>";
							echo "<td  class='tac'>$data_geracao</td>";

							echo "<td   class='tar' style='font: bold 11px Arial'>R$ $total</td>";
						echo "</tr>";

					}
				echo "<tr><td colspan='4' class='tac'><input type='button' class='btn' value='Fechar' onclick=\"fechaDiv('extrato_$posto')\"></td></tr>";
				echo "</table>";
				exit();
			}
	}
	else{
		$sql = "SELECT  DISTINCT tbl_extrato.extrato,
                      	to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
                      	tbl_extrato.total
                      	FROM tbl_extrato
                      	LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato  = tbl_extrato.extrato
                      		 JOIN tbl_posto_fabrica 	ON tbl_posto_fabrica.codigo_posto = '$posto'
                      	WHERE tbl_extrato.fabrica = $login_fabrica AND
                      		  tbl_extrato.posto   = tbl_posto_fabrica.posto AND
						      tbl_extrato_pagamento.data_pagamento IS NULL;";

		//echo nl2br($sql); exit;
		$res = pg_query ($con,$sql);

			$qtde_extratos = pg_num_rows ($res);

			if ($qtde_extratos == 0) {
				echo "<center><div class='alert alert-error'>".traduz("Não Foram Encontrados Extratos Não Baixados Deste Posto.")."</div></center>";
				exit();
			} else{

				echo "<table  class='table'>";
					echo "<thead><tr class='titulo_coluna'>";
						$coluna_extrato = ($login_fabrica == 19) ? traduz('Protocolo') : traduz('Extrato');
						echo "<th>".traduz("Posto")."</th>";
						echo "<th>". $coluna_extrato . "</th>";
						echo "<th>".traduz("Data")."</th>";
						echo "<th>".traduz("Total <br> Geral")."</th>";

					echo "</tr></thead>";

					for ($i=0; $i < pg_numrows($res); $i++){

						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

						$extrato        = pg_result($res,$i,extrato);
						$data_geracao   = pg_result($res,$i,data_geracao);
						$total          = trim(pg_fetch_result($res,$i,total));
						$total	        = number_format ($total,2,',','.');

						echo "<tr>";
							echo "<td  class='tac'>$posto</td>";
							echo "<td  class='tac'>$extrato</td>";
							echo "<td  class='tac'>$data_geracao</td>";
							echo "<td  class='tar' style='font: bold 11px Arial'>R$ $total</td>";
						echo "</tr>";

					}
					if($login_fabrica == 1 OR $login_fabrica >= 131){
						echo "<tr><td colspan='4' align='center'><input type='button' class='btn' value='Fechar' onclick=\"fechaDiv('$posto')\"></td></tr>";
					}
				echo "</table>";
				exit();
			}
	}
}

#Relatório de Extratos não Baixados - FIM



#Relatório de OS abertas - INÍCIO
if ($_GET['listaOS'])
{
	$posto = $_GET['codPosto'];

	$sql = "SELECT ";
	if($login_fabrica == 1){
		$sql .= "tbl_os.sua_os, ";
	}
	else{
		$sql .= "os, ";
	}
	$sql .="  TO_CHAR(data_abertura,'DD/MM/YYYY') as data
				FROM tbl_os
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_os_extra USING(os)
			   WHERE tbl_os.fabrica = $login_fabrica
			   AND tbl_os.excluida IS NOT TRUE
			   AND tbl_posto_fabrica.codigo_posto = '$posto'
			   AND (tbl_os.finalizada IS NULL OR  tbl_os_extra.extrato IS NULL)";

	$res = pg_query ($con,$sql);
	//echo nl2br($sql);

	$qtde_os = pg_num_rows ($res);

	if ($qtde_os == 0) {
		if($login_fabrica == 1){
			echo "<center><div id='os_$posto' class='alert alert-error' style='display:none;'>Não há OS abertas para este Posto. <br /> <input type='button' class='btn' value='Fechar' onclick=\"fechaDiv('os_$posto')\"></div></center>";
		}else{
			echo "<center><div class='alert alert-error'>".traduz("Não há OS abertas para este Posto.")."</div></center>";
		}
		exit();
	} else{
		if($login_fabrica == 1){
			echo "<table class='table' id='os_$posto' style='display:none;'>";
		}else{
			echo "<table class='table' id='os_$posto'>";
		}
			echo "<thead><tr class='titulo_coluna'>";
				echo "<th>".traduz("Posto")."</th>";
				echo "<th>OS</th>";
				echo "<th>".traduz("Data Abertura")."</th>";
			echo "</tr></thead>";

			for ($i=0; $i < pg_numrows($res); $i++){

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				if($login_fabrica == 1){
					$os              = $posto.pg_result($res,$i,sua_os);
				}else{
					$os              = pg_result($res,$i,os);
				}
				$data_abertura   = pg_result($res,$i,data);

				echo "<tr>";
					echo "<td class='tac'>$posto</td>";
					echo "<td class='tac'>$os</td>";
					echo "<td class='tac'>$data_abertura</td>";
				echo "</tr>";

			}
			if($login_fabrica == 1 OR $login_fabrica >= 131){
				echo "<tr><td colspan='3' class='tac'><input type='button' class='btn' value='".traduz("Fechar")."' onclick=\"fechaDiv('os_$posto')\"></td></tr>";
			}
		echo "</table>";
		exit();
	}
}
#Relatório de OS abertas - FIM

#Relatório de Pedidos não Atendidos - INÍCIO
if ($_GET['listaPedidos'])
{
	$posto = $_GET['codPosto'];

	$sql = "SELECT pedido,
				   TO_CHAR(data,'DD/MM/YYYY') as data,
				   tbl_status_pedido.descricao
				FROM tbl_pedido
				JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			   WHERE tbl_pedido.fabrica = $login_fabrica
			   AND tbl_posto_fabrica.codigo_posto = '$posto'
			   AND tbl_pedido.status_pedido in(1,2)";

	$res = pg_query ($con,$sql);
	//echo nl2br($sql);

	$qtde_pedidos = pg_num_rows ($res);

	if ($qtde_pedidos == 0) {
		echo "<center><div class='alert alert-error'>".traduz("Não há Pedidos para este Posto.")."</div></center>";
		exit();
	} else{

		echo "<table class='table table-normal table-striped'>";
			echo "<thead><tr class='titulo_coluna'>";
				echo "<th>".("Posto")."</th>";
				echo "<th>".("Pedido")."</th>";
				echo "<th>".("Data Abertura")."</th>";
				echo "<th>".("Status")."</th>";
			echo "</tr></thead>";

			for ($i=0; $i < pg_numrows($res); $i++){

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$pedido          = pg_result($res,$i,pedido);
				$data            = pg_result($res,$i,data);
				$status          = pg_result($res,$i,descricao);

				echo "<tr>";
					echo "<td class='tac'>$posto</td>";
					echo "<td class='tac'>$pedido</td>";
					echo "<td class='tac'>$data</td>";
					echo "<td class='tac'>$status</td>";
				echo "</tr>";

			}
		echo "</table>";
		exit();
	}
}
#Relatório de Pedidos não Atendidos - FIM

	if($_GET['idPosto']){
		$posto = $_GET['idPosto'];

		#Botão de CONFIRMAR DESCREDENCIAMENTO UPDATE - INICIO
		if($login_fabrica == 74){
			$limit = "LIMIT 1";
		}
			#Seleciona detalhes de postos em descredenciamento.
			$sql = "SELECT
						 tbl_posto_fabrica.codigo_posto AS codigo_posto       ,
						 tbl_posto.nome                 AS nome               ,
						 tbl_credenciamento.dias                              ,
						 tbl_credenciamento.data ,
						 to_char (tbl_credenciamento.data, 'DD/MM/YYYY') as data_formatada,
						 tbl_credenciamento.texto       AS texto                          ,
						 tbl_credenciamento.status

					FROM
						 tbl_posto_fabrica
					JOIN tbl_posto ON tbl_posto_fabrica.posto=tbl_posto.posto
					JOIN tbl_credenciamento ON tbl_posto_fabrica.posto=tbl_credenciamento.posto
						 AND tbl_posto_fabrica.fabrica=tbl_credenciamento.fabrica

					WHERE
						 tbl_posto_fabrica.fabrica= $login_fabrica
						 AND tbl_posto_fabrica.credenciamento='EM DESCREDENCIAMENTO' ";
					if($login_fabrica == 19 or $login_fabrica == 74){
						 $sql .= "AND tbl_credenciamento.status='EM DESCREDENCIAMENTO' ";
					}
					$sql .= "
						 AND tbl_posto_fabrica.codigo_posto = '$posto'
					ORDER BY tbl_credenciamento.data DESC $limit;";
			$res = pg_exec($con,$sql);

			if (pg_numrows($res) >0){

				#table que exibe detalhes do posto
				echo "<table class='table table-striped' id='table_listagem_$posto'>";
					echo "<thead><tr class='titulo_coluna'>";
						if ($login_fabrica != 177) {
							echo "<th>".traduz("Qtde de dias para Descredenciar")."</th>";
						}
						echo "<th>".traduz("Observações")."</th>";
						echo "<th>".traduz("Data da Inclusão")."</th>";

					if($login_fabrica == 19){
						echo "<th>".traduz("Pendência de Peças")."</th>";
					}
					if($login_fabrica == 1){
						echo "<th>".traduz("Status")."</th>";
					}
					echo "</tr></thead>";
				$hoje = date('Y-m-d');
				for ($i=0; $i < pg_numrows($res); $i++){

					$texto          = (pg_result($res,$i,texto));
					$data           = utf8_encode(pg_result($res,$i,data));
					$data_formatada = utf8_encode(pg_result($res,$i,data_formatada));
					$dias           = utf8_encode(pg_result($res,$i,dias));
					$status         = utf8_encode(pg_result($res,$i,status));


					if($login_fabrica == 19 or $login_fabrica == 35 or $login_fabrica == 74 or ($login_fabrica == 1 and !empty($dias))){

						$sqlX = "SELECT '$data':: date + interval '$dias days';";
						$resX = pg_exec ($con,$sqlX);
						$dt_expira = pg_result ($resX,0,0);
						//echo $sqlX;
						$sqlX = "SELECT '$dt_expira'::date - current_date;";
						$resX = pg_exec ($con,$sqlX);

						$dt_expira = substr ($dt_expira,8,2) . "-" . substr ($dt_expira,5,2) . "-" . substr ($dt_expira,0,4);
						$dia_hoje= pg_result ($resX,0,0);
					}
				$cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';

					if($login_fabrica == 1){
						if($dia_hoje < 0){
							$dia_hoje = 0;
						}
					}

					echo "<tr>";

					if($login_fabrica != 35){
						if ($login_fabrica != 177) {
							if($dia_hoje <=3){
								echo "<td style='border:1px solid #D9E2EF;text-align:center; color:#F00;'>$dias</td>";
							}
							else{
								echo "<td style='border:1px solid #D9E2EF;text-align:center;'>$dia_hoje</td>";
							}
						}
					}else{

						if($dia_hoje <=3){
							echo "<td style='border:1px solid #D9E2EF;text-align:center; color:#F00;'>".$dias."</td>";
						}else{
							echo "<td style='border:1px solid #D9E2EF;text-align:center;'>".$dia_hoje." (".traduz("Data do descredenciamento % ", null,null, [$dt_expira]).")</td>";
						}
					}

						echo "<td style='border:1px solid #D9E2EF;text-align:center;font:10px Arial;'>$texto</td>";
						echo "<td style='border:1px solid #D9E2EF;text-align:center;'>$data_formatada</td>";
					if($login_fabrica == 19){
						$sql_peca1 = "
							SELECT
								tbl_os_item.os_item

							FROM
								 tbl_os_item
							JOIN tbl_os_produto USING (os_produto)
							JOIN tbl_os USING (os)
							JOIN tbl_posto USING (posto)

							WHERE
								 tbl_os_item.servico_realizado IN (62)
							 AND tbl_os_item.pedido IS NULL
							 AND tbl_os.validada IS NOT NULL
							 AND tbl_os.fabrica = $login_fabrica
							 AND tbl_os.posto = '$posto'
							 LIMIT 1;";
						$res_peca1 = pg_exec($con,$sql_peca1);

							if (pg_numrows($res_peca1) >0){
								$pendencia_peca = "SIM";
							}else{
							//$pendencia_peca = "NAO";

								$sql_peca2 = "SELECT
												tbl_os_item.os_item

												FROM
												tbl_os_item
												JOIN tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
												JOIN tbl_os on tbl_os.os = tbl_os_produto.os
												JOIN tbl_os_troca on tbl_os_troca.os = tbl_os.os
												JOIN tbl_posto on tbl_posto.posto = tbl_os.posto
												JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica

												WHERE
												tbl_os_item.servico_realizado IN (120)
												AND tbl_os_item.pedido IS NULL
												AND tbl_os.validada IS NOT NULL
												AND (tbl_os.troca_garantia IS TRUE OR tbl_os.troca_faturada IS TRUE)
												AND tbl_os.fabrica = $login_fabrica
												AND tbl_os.posto = $posto
												AND tbl_os_troca.ri IS NULL
												AND tbl_os.nota_fiscal_saida IS NULL
												AND tbl_os_troca.status_os = 19

												LIMIT 1; ";
								$res_peca2 = pg_exec($con,$sql_peca2);
								// echo nl2br($sql_peca2);
								if (pg_numrows($res_peca2) >0){
									$pendencia_peca = "SIM";
								}else{
										$sql_peca3 = "
										SELECT
										tbl_pedido_item.pedido_item

										FROM
										tbl_pedido_item
										JOIN tbl_pedido using(pedido)

										WHERE
										fabrica=$login_fabrica
										AND posto=$posto
										AND qtde>qtde_faturada+qtde_cancelada

										LIMIT 1;
										";
									$res_peca3 = pg_exec($con,$sql_peca3);
									// echo nl2br($sql_peca2);
									if (pg_numrows($res_peca3) >0){
										$pendencia_peca = "SIM";
									}else{
										$pendencia_peca = "NÃO";
									}
								}
							}
							echo "<td style='border:1px solid #D9E2EF;text-align:center;font:10px Arial;'>";
								echo $pendencia_peca;
							echo "</td>";
						}
						if($login_fabrica == 1){
							echo "<td style='border:1px solid #D9E2EF;text-align:center;'>$status</td>";
						}
					echo "</tr>";
				}

					echo "<tr>";
						echo "<td class='titulo_tabela' colspan='4'> ";
						if($login_fabrica == 19 or $login_fabrica == 74){
							echo "Pendências do Posto";
						}
					echo "</td></tr>";

					echo "<tr>";
						echo "<td style='border:1px solid #D9E2EF' colspan='4' align='center'>";

						if($login_fabrica != 35){
							echo"&nbsp; &nbsp; <INPUT TYPE='button' class='btn'  VALUE='".traduz("Extratos em aberto")."' title='".traduz("Clique para Pesquisar")."' ONCLICK=\"abreRelatorio('$posto')\" ALT='Continuar busca de Extratos' id='botao_$posto'>";
						}

						if($login_fabrica == 1 or $login_fabrica == 74 or $login_fabrica == 86 OR $login_fabrica >= 131 OR $login_fabrica == 11){
							echo "&nbsp; &nbsp; <INPUT TYPE='button' class='btn' VALUE=\"OS&acute;s ".traduz("em aberto")."\" title='".traduz("Clique para Pesquisar")."' ONCLICK=\"consultaOS('$posto')\" ALT='Continuar busca de Extratos'  id='botao_os_$posto'>";
						}

						if($login_fabrica==74 OR $login_fabrica >= 131 OR $login_fabrica == 11){
							echo "&nbsp; &nbsp; <INPUT TYPE='button' class='btn' VALUE='".traduz("Pesquisar Pedidos")."' title='".traduz("Clique para Pesquisar")."' ONCLICK=\"consultaPedidos('$posto')\" ALT='Continuar busca de Extratos'  id='botao_pedido_$posto'>";

						}

						if($login_fabrica==1 or $login_fabrica == 86 OR $login_fabrica >= 131 OR $login_fabrica == 11){
							echo "&nbsp; &nbsp; <INPUT TYPE='button' class='btn' VALUE='".traduz("Pe&ccedil;as Pendentes")."' title='".traduz("Clique para Pesquisar")."' ONCLICK=\"consultaPeca('$posto')\" ALT='Continuar busca de Extratos'  id='botao_peca_$posto'>";

						}
					echo "</td>";
					echo "</tr>";


				echo "</table>";

				#Botão de CONFIRMAR EM DESCREDENCIAMENTO
				echo "<table class='table'>";
					echo "<tr>";
						echo "<td colspan='4' class='tac'>";
							if($login_fabrica == 1){
								echo "<input type='button' class='btn' value='Confirmar Descredenciamento' onclick='descredenciaPosto(\"$posto\")' >";
							}
							else{
								echo "<input type='button' class='btn' value='".traduz("Confirmar Descredenciamento")."' onclick='descredenciaPosto(\"$posto\")' >";
							}
						echo "</td>";
					echo "</tr>";
				echo "</table>";

			}

		exit;
	}

	if($_GET['listaPeca']){
		  $posto = $_GET['codPosto'];

		  $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$posto'";
		  $res = pg_query($con,$sql);
		  $login_posto = pg_result($res,0,0);

		  $sql = "SELECT SUM(tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada) as total,
                       tbl_pedido_item.peca
                  FROM tbl_pedido_item
                  JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
                  JOIN tbl_peca   ON tbl_pedido_item.peca   = tbl_peca.peca
                 WHERE tbl_pedido.fabrica = $login_fabrica
                   AND tbl_pedido.posto   = $login_posto
                   AND tbl_pedido.status_pedido NOT IN(1,2,4,14)
                   AND tbl_peca.ativo IS TRUE
                   AND tbl_pedido.data BETWEEN '2010-07-01' AND current_date
                   AND (tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada) > 0
                 GROUP BY tbl_pedido_item.peca
                 ORDER BY total desc;";
        $res   = @pg_query($con,$sql);
        $total = @pg_num_rows($res);

		if($total > 0){
			echo "<table  class='table' align='center' id='peca_$posto' style='display:none;'>
				<thead>
                <tr class='titulo_coluna'>
                    <th>Referência</th>
                    <th>Descrição</th>
					<th>Qtde</th>
				</tr></thead>";
			for ($i = 0; $i < $total; $i++) {

				$cor  = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';
				$peca = @pg_fetch_result($res, $i, 'peca');

				$sql_peca = "SELECT tbl_peca.referencia,
									tbl_peca.descricao
							   FROM tbl_pedido_item
							   JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
							   JOIN tbl_peca   ON tbl_peca.peca          = tbl_pedido_item.peca
							  WHERE tbl_pedido_item.peca = $peca
								AND (tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada) > 0
								AND tbl_pedido.posto     = $login_posto
								AND tbl_pedido.status_pedido NOT IN(1,2,4,14)
								AND tbl_peca.ativo IS TRUE
								$where_data
								$where_peca
								$where_pedido
							  ORDER BY tbl_pedido_item.data_item
							  LIMIT 1;";

				$res_peca = pg_query($con, $sql_peca);
				$referencia = @pg_fetch_result($res_peca, 0, 'referencia');
				$descricao  = @pg_fetch_result($res_peca, 0, 'descricao');

				echo '<tr>';
                        echo '<td class="tac">&nbsp;'.$referencia.'</td>';
                        echo '<td class="tac">&nbsp;'.$descricao.'</td>';
                        echo '<td class="tac">&nbsp;'.@pg_fetch_result($res, $i, 'total').'</td>';
				echo '</tr>';
			}
			echo "<tr><td colspan='3' class='tac' ><input type='button' class='btn' value='Fechar' onclick=\"fechaDiv('peca_$posto')\"></td></tr>";
			echo "</table>";
		}
		else{
			if ($login_fabrica == 11) {
				echo "<center><div id='peca_$posto' class='alert alert-error' style='display:none;'>".traduz("Não há Peças pendentes para este Posto.")." </div></center>";
			}else{
				echo "<center><div id='peca_$posto' class='alert alert-error' style='display:none;'>".traduz("Não há Peças pendentes para este Posto.")." <br /> <input type='button' value='".traduz("Fechar")."' class='btn' onclick=\"fechaDiv('peca_$posto')\"></div></center>";
			}
			
		}

		exit;
	}

	#DESCREDENCIAR POSTOS - INÍCIO
	if($_GET['descPosto']){
		$codigo_posto = $_GET["codPosto"];

		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
		$res = pg_exec($con,$sql);
		$posto = pg_result($res,0,0);

		$sql = "INSERT INTO tbl_credenciamento (
						posto             ,
						fabrica           ,
						data              ,
						status            ,
						confirmacao       ,
						confirmacao_admin )
						VALUES (
						$posto            ,
						$login_fabrica    ,
						current_timestamp ,
						'DESCREDENCIADO'  ,
						NOW()             ,
						$login_admin);";
						$res = pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro) == 0){
			$sql = "UPDATE  tbl_posto_fabrica SET
							credenciamento = 'DESCREDENCIADO'
					WHERE   fabrica = $login_fabrica
					AND     posto   = $posto;";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
		}

		if(strlen($msg_erro)==0){
			echo "ok";
		}

		exit;
	}

	#DESCREDENCIAR POSTO - FIM

	include 'cabecalho_new.php';


	?>


</style>
	<script type="text/javascript" charset="utf-8">

		function consultaPosto(codigo_posto){
			loading = '<div style="width:500px; margin:auto">Carregando...</div>';
			$("#cod_"+codigo_posto).html(loading).show();
			$.get("<?php echo $_SERVER['PHP_SELF']; ?>",
				{idPosto : codigo_posto },
				function(resposta){
					$('#cod_'+codigo_posto).html(resposta);
				}
			);

			$('#posto_'+codigo_posto).toggle();

		}

		function fechaDiv(codigo_posto){
			$('#'+codigo_posto).toggle();
		}

		function atualizaPosto(codigo_posto)
		{
			$.get("<?php echo $_SERVER['PHP_SELF']; ?>",
				{
					idPosto : codigo_posto,
					descredenciamento : true
				},
				function(resposta){
					$('#l_'+codigo_posto).hide();
					$('#posto_'+codigo_posto).hide();
					$('#linha_'+codigo_posto).hide();
				}
			);

		}

		function abreRelatorio(codigo_posto)
		{
			$.get("<?php echo $_SERVER['PHP_SELF']; ?>",
				{
					codPosto : codigo_posto,
					listaExtrato : true
				},
				function(resposta){
					$('#table_listagem_'+codigo_posto).after(resposta);
					$('#botao_'+codigo_posto).hide();
					<?php
						if($login_fabrica == 1){
					?>
							$('#extrato_'+codigo_posto).toggle();
					<?php
						}
					?>
				}
			);
		}

		function consultaOS(codigo_posto)
		{
			$('#botao_os_'+codigo_posto).hide();
			$.get("<?php echo $_SERVER['PHP_SELF']; ?>",
				{
					codPosto : codigo_posto,
					listaOS : true
				},
				function(resposta){
					$('#table_listagem_'+codigo_posto).after(resposta);
					<?php
						if($login_fabrica == 1){
					?>
							$('#os_'+codigo_posto).toggle();
					<?php
						}
					?>
				}
			);
		}

		function consultaPedidos(codigo_posto)
		{
			$('#botao_pedido_'+codigo_posto).hide();
			$.get("<?php echo $_SERVER['PHP_SELF']; ?>",
				{
					codPosto : codigo_posto,
					listaPedidos : true
				},
				function(resposta){
					$('#table_listagem_'+codigo_posto).after(resposta);
				}
			);
		}

		function consultaPeca(codigo_posto){
			$('#botao_peca_'+codigo_posto).hide();
			$.get("<?php echo $_SERVER['PHP_SELF']; ?>",
				{
					codPosto : codigo_posto,
					listaPeca : true
				},
				function(resposta){
					$('#table_listagem_'+codigo_posto).after(resposta);
					$('#peca_'+codigo_posto).toggle();
				}
			);


		}

		function descredenciaPosto(codigo_posto)
		{
			$.get("<?php echo $_SERVER['PHP_SELF']; ?>",
				{
					codPosto : codigo_posto,
					descPosto : true
				},
				function(resposta){
					<?php if($login_fabrica == 1){ ?>
							$('#l_'+codigo_posto).hide();
							$('#posto_'+codigo_posto).hide();
							$('#linha_'+codigo_posto).hide();
					<? } else{ ?>
							$('#table_listagem_'+codigo_posto).remove();
							$('#l_'+codigo_posto).remove();
					<? } ?>
					alert('<?=traduz("Posto Descredenciado com Sucesso!")?>');

					var variavel = $('#postos tr').length;

					if (variavel <= 5) {//não tem TRs}
						$("#leio_depois").attr("value","Continuar");
					}

					$("#l_" + codigo_posto).remove();
					$("#cod_" + codigo_posto).remove();
					$("#table_listagem_" + codigo_posto).remove();
				}
			);
		}
	</script>
	<?php

	if (!empty($_GET) && strlen($msg_erro) == 0) {
		//include "gera_relatorio_pararelo.php";
	}

	if (strlen($codigo_posto) == 0) {
		if ($gera_automatico != 'automatico' and strlen($msg_erro)== 0) {
			//include "gera_relatorio_pararelo_verifica.php";
		}
	}?>

	<?php
		$sql = "
			SELECT max(tbl_credenciamento.credenciamento) as credenciamento, tbl_credenciamento.posto
					   INTO TEMP tmp_credenciamento_posto_$login_admin
					  FROM tbl_credenciamento
					WHERE tbl_credenciamento.status='EM DESCREDENCIAMENTO'
					AND tbl_credenciamento.fabrica = $login_fabrica
					GROUP BY tbl_credenciamento.posto;

				CREATE INDEX tmp_credenciamento_posto_posto ON tmp_credenciamento_posto_$login_admin(credenciamento);

				SELECT tbl_posto_fabrica.codigo_posto,
					   tbl_posto.nome,
					   tbl_credenciamento.dias ,
					   tbl_credenciamento.data
					FROM tbl_posto_fabrica
					JOIN tbl_posto ON tbl_posto_fabrica.posto=tbl_posto.posto
					JOIN tbl_credenciamento ON tbl_posto_fabrica.posto=tbl_credenciamento.posto
					JOIN tmp_credenciamento_posto_$login_admin ON tmp_credenciamento_posto_$login_admin.credenciamento = tbl_credenciamento.credenciamento
					AND tbl_posto_fabrica.fabrica=tbl_credenciamento.fabrica
				   WHERE tbl_posto_fabrica.fabrica= $login_fabrica
				   AND tbl_posto_fabrica.credenciamento='EM DESCREDENCIAMENTO'
				   ORDER BY tbl_posto.nome;
				";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 0){
		
			if($login_fabrica == 189 AND $login_privilegios == "*"){
				header('Location: acompanhamento_atendimentos.php');
			}else{

				if ($login_privilegios != "*") {
					$arrPrivilegios = explode(",", $login_privilegios);

		        	if (in_array('cadastros', $arrPrivilegios)) {

						header("Location: menu_cadastro.php");

					} else {

						header("Location: menu_callcenter.php");

					}
					
				} else {

					header("Location: menu_cadastro.php");

				}
			}
				#INICIO TABELA PRINCIPAL
		}else{

			echo "<form name='frm_lista' method='POST' action='<? echo $PHP_SELF; ?>'";
echo "<table width='100%'>";
			echo "<table class='table table-normal table-striped' id='postos'>";
				if($login_fabrica == 1 OR $login_fabrica == 11){
					$colspan = "colspan='2'";
				}
				echo "<caption class='titulo_tabela' >";
				echo traduz("Postos em Descredenciamento");
				echo "</caption>";

				echo "<thead>";
				echo "<tr class='titulo_coluna'>";
					if($login_fabrica == 1 OR $login_fabrica == 11){
						echo "<th >";
						echo traduz("Código Posto");
						echo "</th>";
					}
					echo "<th>";
						echo traduz("Nome");
					echo "</th>";
				echo "</tr></thead>";

			for ($i=0; $i < pg_numrows($res); $i++){
					$codigo_posto = pg_result($res,$i,codigo_posto);
					$posto_nome   = pg_result($res,$i,nome);
					if($login_fabrica == 1 OR $login_fabrica == 11){
						$data   = pg_result($res,$i,data);
						$dias   = pg_result($res,$i,dias);

						$sqlX = "SELECT '$data':: date + interval '$dias days';";
						$resX = pg_exec ($con,$sqlX);
						$dt_expira = pg_result ($resX,0,0);

						$sqlX = "SELECT '$dt_expira'::date - current_date;";
						$resX = pg_exec ($con,$sqlX);

						$dt_expira = substr ($dt_expira,8,2) . "-" . substr ($dt_expira,5,2) . "-" . substr ($dt_expira,0,4);
						$dia_hoje= pg_result ($resX,0,0);

						$cor_borda = "";
						if($dia_hoje < 0){
							$cor_borda = "style='border:1px solid #FF0000'";
						}
					}

				$cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';

				echo "<tr id='l_".$codigo_posto."' onclick=\"consultaPosto('$codigo_posto')\">";
				if($login_fabrica == 1 OR $login_fabrica == 11 ){
					echo "<td $cor_borda>$codigo_posto</td>";
				}

				echo "<td align='left'><a href='javascript:void(0);' id='linha_".$codigo_posto."'> $posto_nome </a></td>";
				echo "</tr>";
				?>
				<tr id="posto_<?=$codigo_posto?>" style="display:none;" align='center'>
					<td width='690px' style='border:1px solid #D9E2EF' <? echo $colspan;?>
						<div style='border:1px solid #D9E2EF' id="cod_<?=$codigo_posto?>"></div>
					</td>
				</tr>
				<tr style="display:none;" align='center'>
					<td style='border:0px'>
						<div style='border:0px'></div>
					</td>
				</tr>

				<?php
			}
			echo "</table>";
			echo "</form>";
			#FIM TABELA PRINCIPAL

			$link = ($login_fabrica == 189 AND $login_privilegios == "*") ? "acompanhamento_atendimentos.php" : "menu_cadastro.php";

			echo "<table class='table table-normal'>";
				echo "<tr>";
					echo "<td class='tac'>";
						?>
							<br>
							<input type='button' class='btn' value='<?=traduz("Leio Depois")?>' onclick="window.location='<?=$link;?>'" style='cursor:pointer;' id='leio_depois'>
						<?php
					echo "</td>";
				echo "<tr>";
			echo "</table>";
		}
}

?>


<?php
include "rodape.php";

?>
