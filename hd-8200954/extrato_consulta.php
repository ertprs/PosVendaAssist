<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

include "cabecalho.php";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<?
$periodo = trim($_POST['periodo']);
if (strlen($_GET['periodo']) > 0) $periodo = trim($_GET['periodo']);

# ----------------------------------------- #
# -- VERIFICA SE É POSTO OU DISTRIBUIDOR -- #
# ----------------------------------------- #
$sql = "SELECT  DISTINCT
				tbl_tipo_posto.tipo_posto ,
				tbl_posto.estado
		FROM    tbl_tipo_posto
		JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
									AND tbl_posto_fabrica.posto      = $login_posto
									AND tbl_posto_fabrica.fabrica    = $login_fabrica
		JOIN    tbl_posto            ON tbl_posto.posto = tbl_posto_fabrica.posto
		WHERE   tbl_tipo_posto.distribuidor IS TRUE
		AND     tbl_posto_fabrica.fabrica = $login_fabrica
		AND     tbl_tipo_posto.fabrica    = $login_fabrica
		AND     tbl_posto_fabrica.posto   = $login_posto ";
$res = pg_exec ($con,$sql);
if (pg_numrows($res) == 0) $tipo_posto = "P"; else $tipo_posto = "D";

if ($login_fabrica == 3) {
	#if (substr($ip,0,10) <> '192.168.0.') {
	#	echo "<h1><center>Extratos sendo recalculados pela TELECONTROL</center></h1>";
	#	exit;
	#}

	# --------------------------------------- #
	# -- MONTA COMBO COM DATAS DE EXTRATOS -- #
	# --------------------------------------- #
	$sql = "SELECT      DISTINCT
						date_trunc('day',tbl_extrato.data_geracao)      AS data_extrato,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data        ,
						to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo
			FROM        tbl_extrato
			JOIN        tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_extrato.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica ";

	if ($tipo_posto == "D") $sql .= " WHERE (tbl_posto_fabrica.posto = $login_posto OR tbl_posto_fabrica.distribuidor = $login_posto) ";
	else                    $sql .= " WHERE tbl_posto_fabrica.posto  = $login_posto ";

	$sql .="AND      tbl_extrato.fabrica = $login_fabrica
			AND      tbl_extrato.aprovado IS NOT NULL
			ORDER BY to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') DESC";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<form name=\"frm_periodo\" method=\"get\" action=\"$PHP_SELF\">";
		echo "<input type=\"hidden\" name=\"exibir\" value=\"acumulado\">";
		
		echo "<table width='80%' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' align='center'>";
		echo "<select name='periodo' onchange='javascript:frm_periodo.submit()'>\n";
		echo "<option value=''>INFORME O PERÍODO PARA CONSULTA</option>\n";
		
		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$aux_data  = trim(pg_result($res,$x,data));
			$aux_extr  = trim(pg_result($res,$x,data_extrato));
			$aux_peri  = trim(pg_result($res,$x,periodo));
			
			echo "<option value='$aux_peri'"; if ($periodo == $aux_peri) echo " SELECTED "; echo ">$aux_data</option>\n";
		}
		
		echo "</select>\n";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
		
		echo "</form>";
	}

	# ----------------------------------------- #
	# -- SE FOI SELECIONADO PERÍODO NO COMBO -- #
	# ----------------------------------------- #
	if (strlen($periodo) > 0) {
		$exibir = $_POST['exibir'];
		if (strlen($_GET['exibir']) > 0) $exibir = $_GET['exibir'];
		
		if ($exibir == 'acumulado') {
			# -- EXIBE VALORES ACUMULADOS DOS EXTRATOS -- #
			# -- SELECIONA EXTRATOS DOS POSTOS -- #
			$sql = "SELECT      tbl_linha.linha                                                    ,
								tbl_linha.nome                                       AS linha_nome ,
								count(tbl_os.os)                                     AS qtde_os    ,
								tbl_os.mao_de_obra                                   AS mo_unit    ,
								sum (tbl_os.mao_de_obra)                             AS mo_posto   ,
								sum (tbl_familia.mao_de_obra_adicional_distribuidor) AS mo_adicional
					FROM        tbl_os
					JOIN        tbl_os_extra         ON tbl_os_extra.os           = tbl_os.os
													AND tbl_os.fabrica            = $login_fabrica
					JOIN        tbl_extrato          ON tbl_extrato.extrato       = tbl_os_extra.extrato
													AND tbl_extrato.fabrica       = $login_fabrica
					JOIN        tbl_produto          ON tbl_produto.produto       = tbl_os.produto
					JOIN        tbl_linha            ON tbl_produto.linha         = tbl_linha.linha
													AND tbl_linha.fabrica         = $login_fabrica
					LEFT JOIN   tbl_familia          ON tbl_produto.familia       = tbl_familia.familia
					JOIN        tbl_posto_fabrica    ON tbl_os.posto              = tbl_posto_fabrica.posto
													AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
					WHERE       tbl_posto_fabrica.fabrica = $login_fabrica ";
			
			if ($tipo_posto == "D") $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";
			else                    $sql .= "AND tbl_os.posto = $login_posto ";
			
			$sql .="AND         tbl_extrato.data_geracao BETWEEN '$periodo 00:00:00' AND '$periodo 23:59:59'
					GROUP BY    tbl_linha.linha    ,
								tbl_linha.nome     ,
								tbl_os.mao_de_obra
					ORDER BY    linha_nome         ,
								tbl_os.mao_de_obra ";
			$res = pg_exec($con,$sql);
			
			echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
			
			if (pg_numrows($res) > 0) {
				$qtde_linhas     = pg_numrows($res);
				$qtde_os         = 0;
				$mo_posto        = 0;
				$mo_adicional    = 0;
				$pecas_total     = 0;
				$adicional_pecas = 0;
				$total           = 0;
				
				echo "<form method=post name=frm_extrato action=\"$PHP_SELF\">";
				
				echo "<tr class='table_line2' style='background-color: #D9E2EF;'>";
				echo "<td nowrap align='center'><b>LINHA</b></td>";
				echo "<td nowrap align='center'><b>M.O.<br>UNIT.</b></td>";
				echo "<td nowrap align='center'><b>QTDE</b></td>";
				echo "<td nowrap align='center'><b>M.O.<br>POSTOS</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='center'><b>M.O.<br>ADICIONAL</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='center'><b>PEÇAS<br>TOTAL</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='center'><b>ADICIONAL<br>PEÇAS</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='center'><b>N.F.<br>SERVIÇO</b></td>";
				echo "<td nowrap align='center'>&nbsp;</td>";
				echo "</tr>";
				
				for ($y=0; $y < pg_numrows($res); $y++) {
					$linha        = trim(pg_result($res,$y,linha));
					$nome_linha   = trim(pg_result($res,$y,linha_nome));
					$mo_unit      = trim(pg_result($res,$y,mo_unit));
					$qtde_os      = trim(pg_result($res,$y,qtde_os));
					$mo_posto     = trim(pg_result($res,$y,mo_posto));
					$mo_adicional = trim(pg_result($res,$y,mo_adicional));
					
					//////////////////////////////////////////////
					$btn = 'azul';
					
					$cor = "#F7F5F0"; 
					if ($y % 2 == 0) $cor = '#F1F4FA';
					
					echo "<tr class='table_line2' style='background-color: $cor;'>\n";
					echo "<td align='left'>$nome_linha</td>\n";
					echo "<td align='right'>". number_format($mo_unit,2,",",".") ."</td>\n";
					echo "<td align='right'>$qtde_os</td>\n";
					echo "<td align='right'>". number_format($mo_posto,2,",",".") ."</td>\n";
					
					if ($tipo_posto == "D") {
						echo "<td align='right'>". number_format($mo_adicional,2,",",".") ."</td>\n";
						
						$sql = "SELECT ROUND (SUM (tbl_os_item.qtde * tbl_tabela_item.preco)::numeric, 2) AS preco
								FROM    tbl_os
								JOIN    tbl_os_produto       ON tbl_os.os                 = tbl_os_produto.os
								JOIN    tbl_os_item          ON tbl_os_produto.os_produto = tbl_os_item.os_produto
								JOIN    tbl_os_extra         ON tbl_os.os                 = tbl_os_extra.os
								JOIN    tbl_extrato          ON tbl_extrato.extrato       = tbl_os_extra.extrato
								JOIN	tbl_produto          ON tbl_os.produto            = tbl_produto.produto
								JOIN    tbl_linha            ON tbl_produto.linha         = tbl_linha.linha
								JOIN	tbl_familia          ON tbl_produto.familia       = tbl_familia.familia
								JOIN    tbl_posto_fabrica    ON tbl_os.posto              = tbl_posto_fabrica.posto
															AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
								JOIN    tbl_posto_linha      ON tbl_posto_linha.posto     = $login_posto
															AND tbl_posto_linha.linha     = $linha
								JOIN    tbl_tabela_item      ON tbl_tabela_item.tabela    = tbl_posto_linha.tabela
															AND tbl_tabela_item.peca      = tbl_os_item.peca
								WHERE   (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_os.posto = $login_posto)
								AND     tbl_extrato.data_geracao BETWEEN '$periodo 00:00:00' AND '$periodo 23:59:59'
								AND     tbl_os.fabrica     = $login_fabrica
								AND     tbl_linha.linha    = $linha
								AND     tbl_os.mao_de_obra = $mo_unit ";
						$resX = pg_exec ($con,$sql);
						
						if (pg_numrows($resX) > 0) {
							$pecas_preco    = pg_result ($resX,0,preco);
							$adicional      = $pecas_preco * 0.5385;
							$nf_servico     = $mo_posto + $mo_adicional + $adicional;
							$t_pecas_total += $pecas_preco;
							
							echo "<td align='right'>". number_format($adicional,2,",",".")    ."</td>\n";
							echo "<td align='right'>". number_format($mo_adicional,2,",",".") ."</td>\n";
							echo "<td align='right'>". number_format($nf_servico,2,",",".")   ."</td>\n";
						}
					}
					
					if ($y == 0) {
						echo "<td width='85' rowspan='$qtde_linhas' valign='center'><a href='$PHP_SELF?periodo=$periodo&exibir=detalhado'><img src='imagens/btn_detalhar_".$btn.".gif'></a></td>\n";
					}
					
					$t_qtde_os         += $qtde_os;
					$t_mo_posto        += $mo_posto;
					$t_mo_adicional    += $mo_adicional;
					$t_adicional_pecas += $adicional;
					$total             += $nf_servico;
					
					echo "</tr>\n";
				}
				
				echo "<tr class='table_line2' style='background-color: #D9E2EF;'>\n";
				echo "<td align='center' colspan='2' nowrap><b>TOTAIS</b></td>\n";
				echo "<td nowrap align='right'><b>$t_qtde_os</b></td>";
				echo "<td nowrap align='right'><b>" . number_format ($t_mo_posto,2,",",".") . "</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='right'><b>" . number_format ($t_mo_adicional,2,",",".")    . "</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='right'><b>" . number_format ($t_pecas_total,2,",",".")     . "</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='right'><b>" . number_format ($t_adicional_pecas,2,",",".") . "</b></td>";
				if ($tipo_posto == "D") echo "<td nowrap align='right'><b>" . number_format ($total,2,",",".") . "</b></td>";
				echo "<td align='right' colspan='2' nowrap>&nbsp;</td>\n";
				echo "</tr>\n";
				
				echo "</form>";
			}else{
				echo "<tr class='table_line'>\n";
				echo "<td align=\"center\">NENHUM EXTRATO FOI ENCONTRADO</td>\n";
				echo "</tr>\n";
				
				echo "<tr>\n";
				echo "<td align=\"center\">\n";
				echo "<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
				echo "</td>\n";
				echo "</tr>\n";
			}
			echo "</table>\n";
		}else{
			# -- EXIBE VALORES DETALHADOS DOS EXTRATOS -- #
			$sql = "SELECT      tbl_posto_fabrica.codigo_posto                                                                 ,
								tbl_posto.nome                                                                                 ,
								tbl_extrato.posto                                                                              ,
								tbl_extrato.extrato                                                                            ,
								to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD')                                 AS data_extrato,
								to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')                                 AS data_geracao,
								tbl_extrato.mao_de_obra                                                                        ,
								tbl_extrato.mao_de_obra_postos                                                                 ,
								sum(tbl_familia.mao_de_obra_adicional_distribuidor)                             AS adicional   ,
								tbl_extrato.pecas                                                                              ,
								tbl_posto.estado                                                                               ,
								tbl_posto_fabrica.pedido_via_distribuidor
					FROM        tbl_extrato
					JOIN        tbl_posto_fabrica    ON tbl_extrato.posto         = tbl_posto_fabrica.posto
													AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN        tbl_posto            ON tbl_extrato.posto         = tbl_posto.posto
					JOIN        tbl_os_extra         ON tbl_os_extra.extrato      = tbl_extrato.extrato
					JOIN        tbl_os               ON tbl_os.os                 = tbl_os_extra.os
					JOIN        tbl_produto          ON tbl_produto.produto       = tbl_os.produto
					LEFT JOIN   tbl_familia          ON tbl_familia.familia       = tbl_produto.familia
					WHERE       tbl_extrato.fabrica = $login_fabrica ";
			
			if ($tipo_posto == "D") $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";
			else                    $sql .= "AND tbl_extrato.posto   = $login_posto ";
			
			$sql .="AND         tbl_extrato.data_geracao BETWEEN '$periodo 00:00:00' AND '$periodo 23:59:59'
					AND         tbl_extrato.aprovado IS NOT NULL
					GROUP BY    tbl_posto_fabrica.codigo_posto            ,
								tbl_posto.nome                            ,
								tbl_extrato.posto                         ,
								tbl_extrato.extrato                       ,
								tbl_extrato.data_geracao                  ,
								tbl_posto_fabrica.pedido_via_distribuidor ,
								tbl_extrato.mao_de_obra                   ,
								tbl_extrato.mao_de_obra_postos            ,
								tbl_extrato.pecas                         ,
								tbl_posto.estado
					ORDER BY tbl_extrato.data_geracao DESC";
			$res = pg_exec ($con,$sql);
			
			echo "<table width='580' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
			if (pg_numrows($res) > 0) {
				echo "<tr class='table_line'>";
				
				echo "<td colspan=8 align='center'>\n";
				echo "&nbsp;";
				echo "</td>\n";
				
				echo "</tr>\n";
				
				echo "<form method=post name=frm_extrato action=\"$PHP_SELF\">";
				
				echo "<tr class='menu_top'>\n";
				
				echo "<td align=\"center\">EXTRATO Nº</td>\n";
				echo "<td align=\"center\">POSTO</td>\n";
				echo "<td align=\"center\">DATA GERAÇÃO</td>\n";
				echo "<td align=\"center\">MO</td>\n";
				echo "<td align=\"center\">PEÇAS</td>\n";
				echo "<td align=\"center\">TOTAL</td>\n";
				echo "<td align=\"center\">&nbsp;</td>\n";
				echo "<td align=\"center\">&nbsp;</td>\n";
				
				echo "</tr>\n";
				
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
					$xmao_de_obra            = 0;
					$posto                   = trim(pg_result($res,$i,posto));
					$posto_codigo            = trim(pg_result($res,$i,codigo_posto));
					$posto_nome              = trim(substr(pg_result($res,$i,nome),0,25));
					$extrato                 = trim(pg_result($res,$i,extrato));
					$data_geracao            = trim(pg_result($res,$i,data_geracao));
					$pedido_via_distribuidor = trim(pg_result($res,$i,pedido_via_distribuidor));
					$data_extrato            = trim(pg_result($res,$i,data_extrato));
					$mao_de_obra             = trim(pg_result($res,$i,mao_de_obra));
					$mao_de_obra_postos      = trim(pg_result($res,$i,mao_de_obra_postos));
					$adicional               = trim(pg_result($res,$i,adicional));
					$pecas                   = trim(pg_result($res,$i,pecas));
					$extrato                 = trim(pg_result($res,$i,extrato));
					$estado                  = trim(pg_result($res,$i,estado));
					
					if (strlen($adicional) == 0) $adicional = 0;
					
					# soma valores
					if ($tipo_posto == "P") {
						$xmao_de_obra += $mao_de_obra_postos;
						$xvrmao_obra   = $mao_de_obra_postos;
					}else{
						$xmao_de_obra += $mao_de_obra;
						$xvrmao_obra   = $mao_de_obra;
					}
					
					if ($xvrmao_obra == 0)  $xvrmao_obra   = $mao_de_obra;
					if ($xmao_de_obra == 0) $xmao_de_obra += $mao_de_obra;
					
					$total = $xmao_de_obra + $pecas;
					
					$data_geracao;
					
					//////////////////////////////////////////////
					$cor = "#F7F5F0"; 
					$btn = 'amarelo';
					if ($i % 2 == 0) 
					{
						$cor = '#F1F4FA';
						$btn = 'azul';
					}
					
					echo "<tr class='table_line' style='background-color: $cor;'>\n";
					
					echo "<td align='left' style='padding-left:7px;'>$extrato</td>\n";
					echo "<td align='left' nowrap>$posto_codigo - $posto_nome</td>\n";
					
					if ($tipo_posto == "D"){
						echo "<td align='center'><a href='extrato_distribuidor.php?data=$data_extrato'>$data_geracao</a></td>\n";
					}else{
						echo "<td align='center'>$data_geracao</td>\n";
					}
					
					echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($xvrmao_obra,2,",",".") ."</td>\n";
					echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($pecas,2,",",".") ."</td>\n";
					echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($total,2,",",".") ."</td>\n";
					echo "<td><a href='os_extrato_detalhe.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_detalhar_".$btn.".gif'></a></td>\n";
					echo "<td><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
					
					echo "</tr>\n";
				}
				echo "<input type='hidden' name='total' value='$i'>";
				
				echo "</form>";
			}else{
				echo "<tr class='table_line'>\n";
				echo "<td align=\"center\">NENHUM EXTRATO FOI ENCONTRADO</td>\n";
				echo "</tr>\n";
				
				echo "<tr>\n";
				echo "<td align=\"center\">\n";
				echo "<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
				echo "</td>\n";
				echo "</tr>\n";
			}
			
			echo "</table>\n";
		}
	}
}else{ // OUTROS FABRICANTES
	# -- EXIBE VALORES DETALHADOS DOS EXTRATOS -- #
	$sql = "SELECT      tbl_posto_fabrica.codigo_posto                                                                 ,
						tbl_posto.nome                                                                                 ,
						tbl_extrato.posto                                                                              ,
						tbl_extrato.extrato                                                                            ,
						to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD')                                 AS data_extrato,
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')                                 AS data_geracao,
						tbl_extrato.mao_de_obra                                                                        ,
						tbl_extrato.mao_de_obra_postos                                                                 ,
						0                                                                               AS adicional   ,
						tbl_extrato.pecas                                                                              ,
						tbl_posto.estado                                                                               ,
						tbl_posto_fabrica.pedido_via_distribuidor
			FROM        tbl_extrato
			JOIN        tbl_posto_fabrica    ON tbl_extrato.posto         = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN        tbl_posto            ON tbl_extrato.posto         = tbl_posto.posto
			JOIN        tbl_os_extra         ON tbl_os_extra.extrato      = tbl_extrato.extrato
			JOIN        tbl_os               ON tbl_os.os                 = tbl_os_extra.os
			JOIN        tbl_produto          ON tbl_produto.produto       = tbl_os.produto
			LEFT JOIN   tbl_familia          ON tbl_familia.familia       = tbl_produto.familia
			WHERE       tbl_extrato.fabrica = $login_fabrica ";
	
	if ($tipo_posto == "P") $sql .= "AND tbl_extrato.posto   = $login_posto ";
	else                    $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";
	
	$sql .="AND         tbl_extrato.posto   = $login_posto
			AND         tbl_extrato.aprovado IS NOT NULL ";
	
	if ($login_fabrica == 6) {
		$sql .= "AND tbl_extrato.liberado IS NOT NULL ";
	}
	
	$sql .= "GROUP BY   tbl_posto_fabrica.codigo_posto            ,
						tbl_posto.nome                            ,
						tbl_extrato.posto                         ,
						tbl_extrato.extrato                       ,
						tbl_extrato.data_geracao                  ,
						tbl_posto_fabrica.pedido_via_distribuidor ,
						tbl_extrato.mao_de_obra                   ,
						tbl_extrato.mao_de_obra_postos            ,
						tbl_extrato.pecas                         ,
						tbl_posto.estado
			ORDER BY tbl_extrato.data_geracao DESC";
	$res = pg_exec ($con,$sql);
	
	echo "<table width='580' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	if (pg_numrows($res) > 0) {
		echo "<tr class='table_line'>";
		
		echo "<td colspan=8 align='center'>\n";
		if ($login_fabrica == 2){
			echo "<br><b>ENVIAR PARA A DYNACOM A NOTA FISCAL DE PRESTAÇÃO DE SERVIÇO E AS ORDENS DE SERVIÇO REFERENTE AO ABAIXO. <br><font color='#FF0000'>É OBRIGATÓRIO O ENVIO DAS O.S.</font></b><br><br>(Clique no número do extrato para abrir os dados da Nota Fiscal de devolução)<br><br>\n";
		}else{
			echo "&nbsp;";
		}
		echo "</td>\n";
		
		echo "</tr>\n";
		
		echo "<form method=post name=frm_extrato action=\"$PHP_SELF\">";
		
		echo "<tr class='menu_top'>\n";
		
		echo "<td align=\"center\">EXTRATO Nº</td>\n";
		echo "<td align=\"center\">POSTO</td>\n";
		echo "<td align=\"center\">DATA GERAÇÃO</td>\n";
		echo "<td align=\"center\">MO</td>\n";
		echo "<td align=\"center\">PEÇAS</td>\n";
		echo "<td align=\"center\">TOTAL</td>\n";
		echo "<td align=\"center\">&nbsp;</td>\n";
		echo "<td align=\"center\">&nbsp;</td>\n";
		
		echo "</tr>\n";
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			$xmao_de_obra            = 0;
			$posto                   = trim(pg_result($res,$i,posto));
			$posto_codigo            = trim(pg_result($res,$i,codigo_posto));
			$posto_nome              = trim(substr(pg_result($res,$i,nome),0,25));
			$extrato                 = trim(pg_result($res,$i,extrato));
			$data_geracao            = trim(pg_result($res,$i,data_geracao));
			$pedido_via_distribuidor = trim(pg_result($res,$i,pedido_via_distribuidor));
			$data_extrato            = trim(pg_result($res,$i,data_extrato));
			$mao_de_obra             = trim(pg_result($res,$i,mao_de_obra));
			$mao_de_obra_postos      = trim(pg_result($res,$i,mao_de_obra_postos));
			$adicional               = trim(pg_result($res,$i,adicional));
			$pecas                   = trim(pg_result($res,$i,pecas));
			$extrato                 = trim(pg_result($res,$i,extrato));
			$estado                  = trim(pg_result($res,$i,estado));
			
			if (strlen($adicional) == 0) $adicional = 0;
			
			# soma valores
			if ($tipo_posto == "P") {
				$xmao_de_obra += $mao_de_obra_postos;
				$xvrmao_obra   = $mao_de_obra_postos;
			}else{
				$xmao_de_obra += $mao_de_obra;
				$xvrmao_obra   = $mao_de_obra;
			}
			
			if ($xvrmao_obra == 0)  $xvrmao_obra   = $mao_de_obra;
			if ($xmao_de_obra == 0) $xmao_de_obra += $mao_de_obra;
			
			$total = $xmao_de_obra + $pecas;
			
			$data_geracao;
			
			//////////////////////////////////////////////
			$cor = "#F7F5F0"; 
			$btn = 'amarelo';
			if ($i % 2 == 0) 
			{
				$cor = '#F1F4FA';
				$btn = 'azul';
			}
			
			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			
			if ($login_fabrica == 2){
				echo "<td align='left' style='padding-left:7px;'>\n";
				echo "<a href='nf_dynacom_consulta.php?extrato=$extrato' target='_blank'>$extrato</a>\n";
				echo "</td>\n";
			}else{
				echo "<td align='left' style='padding-left:7px;'>$extrato</td>\n";
			}
			
			echo "<td align='left' nowrap>$posto_codigo - $posto_nome</td>\n";
			
			if ($login_fabrica == 3 AND $tipo_posto == "D"){
				echo "<td align='center'><a href='extrato_distribuidor.php?data=$data_extrato'>$data_geracao</a></td>\n";
			}else{
				echo "<td align='center'>$data_geracao</td>\n";
			}
			
			echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($xvrmao_obra,2,",",".") ."</td>\n";
			echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($pecas,2,",",".") ."</td>\n";
			echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($total,2,",",".") ."</td>\n";
			echo "<td><a href='os_extrato_detalhe.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_detalhar_".$btn.".gif'></a></td>\n";
			echo "<td><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
			
			echo "</tr>\n";
		}
		echo "<input type='hidden' name='total' value='$i'>";
		
		echo "</form>";
	}else{
		echo "<tr class='table_line'>\n";
		echo "<td align=\"center\">NENHUM EXTRATO FOI ENCONTRADO</td>\n";
		echo "</tr>\n";
		
		echo "<tr>\n";
		echo "<td align=\"center\">\n";
		echo "<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
		echo "</td>\n";
		echo "</tr>\n";
	}
	
	echo "</table>\n";
}

?>

<p><p>

<? include "rodape.php"; ?>