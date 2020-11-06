<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

if (!empty($_GET['extrato'])) {
	if(is_array($_GET['extrato'])){
		$extratos = $_GET['extrato'];
	}
	else{
		$extratos = array($_GET['extrato']);
	}
	foreach($extratos as $key => $value){
		$extratos[$key] = trim($value);
	}
}

function GeraTabelaEntregaTecnica($res_yanmar, $sem_cabecalho = 0){
	global $login_fabrica_logo, $login_fabrica, $con, $novaTelaOs, $total_entrega_tecnica;

	if (pg_num_rows($res_yanmar) == 0)
		return;

	$colspan = 2;
	$colspan2 = 7;
	$colspan3 = 4;

	if ($sem_cabecalho == 1) {
		echo "<br /><br /><br />";
	}
	echo "<TABLE width='665' border='0' cellspacing='0' cellpadding='1'>";
	if ($sem_cabecalho == 0) {
		echo "<TR>";
		echo "<TD class='menu_top' align='center' colspan='$colspan' rowspan='2'><IMG SRC='logos/$login_fabrica_logo' border='0' width='150'></TD>";
		echo "<TD class='menu_top' align='left' colspan='$colspan2'> Posto: <B>".pg_result ($res_yanmar,0,codigo_posto)." - ".pg_result($res_yanmar,0,nome_posto)."</B>&nbsp;</TD>";
		echo "</TR>";

		echo "<TR>";
		echo "<TD class='menu_top' align='left' nowrap colspan='$colspan_extrato' > Extrato: <B>".pg_result($res_yanmar,0,extrato)."</B> </TD>";
		echo "<TD class='menu_top' align='left' nowrap colspan='$colspan_extrato' > Data: <B>".pg_result($res_yanmar,0,data_geracao)."</B>&nbsp;</TD>";
		echo "<TD class='menu_top' align='left' nowrap> Qtde de OS: <B>". pg_num_rows($res_yanmar) ."</B>&nbsp;</TD>";
		echo "<TD class='menu_top' align='right' nowrap colspan='$colspan3'>Total: <B>R$ ".number_format(pg_result ($res_yanmar,0,total),2,',','.')."</B>&nbsp;</TD>";
		echo "</TR>";
	}else{
		echo "<TR>";
		echo "<TD class='menu_top' align='center' colspan='7'> VALORES DE ENTREGA TÉCNICA&nbsp;</TD>";
		echo "</TR>";
	}
	echo "<TR>\n";
	echo "<TD class='menu_top' align='center' width='75'><B>OS</B></TD>\n";
	echo "<TD class='menu_top' align='center' width='75'><B>SÉRIE</B></TD>\n";
	echo "<TD class='menu_top' align='center' width='75'><B>ABERTURA</B></TD>\n";
	echo "<TD class='menu_top' align='center'><B>CONSUMIDOR</B></TD>\n";
	echo "<TD class='menu_top' align='center'><B>PRODUTO</B></TD>\n";
	echo "<TD class='menu_top' align='center'><B>VALOR ENTREGA TÉCNICA</B></TD>\n";

	if(!in_array($login_fabrica,array(106,108,111,121,123,125)) && !isset($novaTelaOs)){
		echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
	} else if (isset($novaTelaOs)) {
        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);

        if (!$nao_calcula_peca) {
			echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
        }

        if (!$nao_calcula_km) {
			echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
        }

		if($inf_valores_adicionais || in_array($login_fabrica, array(145))){
			echo "<td class='menu_top' align='center'><strong>VALOR ADICIONAL</strong></td>";
		}
	}

	echo "</TR>\n";

	$total_qtde_km_calculada = 0;
	$total_qtde_km = 0;
	$total_valor_km = 0;
	$total_pedagio = 0;
	$total_pecas = 0;
	$total_valores_adicionais = 0;
	$t_taxa_visita_os = 0;

	for ($i = 0 ; $i < pg_numrows ($res_yanmar) ; $i++){
		flush();
		$os = trim(pg_result ($res_yanmar,$i,os));
		$data = trim(pg_result ($res_yanmar,$i,data));
		$abertura = trim(pg_result ($res_yanmar,$i,abertura));
		$fechamento = trim(pg_result ($res_yanmar,$i,fechamento));
		$finalizada = trim(pg_result ($res_yanmar,$i,finalizada));
		$sua_os = trim(pg_result ($res_yanmar,$i,sua_os));
		$serie = trim(pg_result ($res_yanmar,$i,serie));
		$consumidor_nome = trim(pg_result ($res_yanmar,$i,consumidor_nome));
		$consumidor_cidade = trim(pg_result ($res_yanmar,$i,consumidor_cidade));
		$produto_nome = trim(pg_result ($res_yanmar,$i,descricao));
		$produto_referencia = trim(pg_result ($res_yanmar,$i,referencia));
		$data_fechamento = trim(pg_result ($res_yanmar,$i,data_fechamento));
		$mao_de_obra = trim(pg_result ($res_yanmar,$i,mao_de_obra));
		$mo_extra = trim(pg_result ($res_yanmar,$i,mo_extra));
		$pecas = trim(pg_result ($res_yanmar,$i,pecas));
		$qtde_km = trim(pg_result ($res_yanmar,$i,qtde_km));
		$valor_km = trim(pg_result ($res_yanmar,$i,valor_km));
		$valores_adicionais = trim(pg_result ($res_yanmar,$i,valores_adicionais));
		$qtde_km_calculada = trim(pg_result ($res_yanmar,$i,qtde_km_calculada));
		$total_entrega_tecnica += trim(pg_result ($res_yanmar,$i,total_mo));
		$t_pecas = trim(pg_result ($res_yanmar,$i,total_pecas));
		$t_deslocamento = trim(pg_result ($res_yanmar,$i,t_deslocamento));
		$extrato_pagamento = trim(pg_result ($res_yanmar,0,extrato_pagamento)) ;
		$valor_total = trim(pg_result ($res_yanmar,0,valor_total)) ;
		$acrescimo = trim(pg_result ($res_yanmar,0,acrescimo)) ;
		$desconto = trim(pg_result ($res_yanmar,0,desconto)) ;
		$valor_liquido = trim(pg_result ($res_yanmar,0,valor_liquido)) ;
		$nf_autorizacao = trim(pg_result ($res_yanmar,0,nf_autorizacao)) ;
		$data_vencimento = trim(pg_result ($res_yanmar,0,data_vencimento)) ;
		$autorizacao_pagto = trim(pg_result ($res_yanmar,0,autorizacao_pagto)) ;
		$revenda_nome = trim(pg_result ($res_yanmar,$i,revenda_nome)) ;
		$consumidor_revenda = trim(pg_result ($res_yanmar,$i,consumidor_revenda)) ;
		$nota_fiscal = trim(pg_result ($res_yanmar,$i,nota_fiscal)) ;
		$data_nf = trim(pg_result ($res_yanmar,$i,data_nf)) ;
		$taxa_visita = trim(pg_result ($res_yanmar,$i,taxa_visita)) ;
		$taxa_visita_os = trim(pg_result ($res_yanmar,$i,taxa_visita_os)) ;
		$os_reincidente = trim(pg_result ($res_yanmar,$i,os_reincidente)) ;
		$data_conserto = pg_fetch_result($res_yanmar, $i, 'data_conserto'); //hd_chamado=2598225
		$total_valor_km = $valor_km;
		$total_qtde_km_calculada += $qtde_km_calculada;
		$total_qtde_km += $qtde_km;

		if($login_fabrica == 42 || isset($novaTelaOs)){
			$total_pecas += $pecas;
		}else{
			$total_pecas += $t_pecas;
		}

		$valores_adicionais = (empty($valores_adicionais)) ? 0 : $valores_adicionais;

		$total_os = 0;
		if($inf_valores_adicionais){
			$total_os = $qtde_km_calculada + $mao_de_obra + $valores_adicionais + $pecas;
			$total_valores_adicionais += $valores_adicionais;
		} else {
			$total_os = $qtde_km_calculada + $pecas + $mao_de_obra;
		}

		$mao_de_obra_reduzida="";

		if (strlen($qtde_km_calculada)==0){
			$qtde_km_calculada = "0.00";
		}

		if(strlen($pecas)==0){
			$pecas = '0.00';
		}

		if(strlen($qtde_km) == 0){
			$qtde_km = 0;
		}

		$pecas	= number_format($pecas, 2, ',', '.');
		$valor_km = number_format($valor_km, 2, ',', '.');
		$qtde_km_calculada = number_format($qtde_km_calculada, 2, ',', '.');
		$mao_de_obra 	= (empty($mao_de_obra)) ? 0 : $mao_de_obra;
		$mao_de_obra	= number_format($mao_de_obra, 2, ',', '.');

		// FAZ A QUEBRA DE PAGINAS DO RELATORIO, PODE-SE MUDAR O NUMERO DE LINHAS
		if (($i % 42 == 0) && ($i != 0)) {

			$col = "4";
            $colspan_extrato = 2;

		   //MONTA O CABEÇALHO DEPOIS DA QUEBRA DE PAGINA
 		    echo "<TR class='quebrapagina'>\n";
			echo "<TD class='menu_top' align='center' colspan='$col' height='30'><B>POSTO: ".pg_result ($res,0,codigo_posto)." - ".pg_result ($res,0,nome_posto)."</B></TD>\n";
			echo "<TD class='menu_top' align='center' ><B>Data: ".pg_result ($res,0,data_geracao)."</B></TD>\n";
			echo "<TD class='menu_top' align='center' valign='center' colspan='$colspan_extrato'><B>Extrato:$extrato</B></TD>\n";
			echo "</TR>\n";
			//FIM DO CABEÇALHO

			//INICIO
			echo "<TR>\n";
			echo "<TD class='menu_top' align='center' width='75'><B>OS</B></TD>\n";
			echo "<TD class='menu_top' align='center' width='75'><B>SÉRIE</B></TD>\n";
			echo "<TD class='menu_top' align='center' width='75'><B>ABERTURA</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>CONSUMIDOR</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>PRODUTO</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>MÃO OBRA</B></TD>\n";
			if(!in_array($login_fabrica,array(106,108,111,121,123,125)) && !isset($novaTelaOs)){
				echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
			}else if (isset($novaTelaOs)) {
		        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
		        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);

		        if (!$nao_calcula_peca) {
					echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
		        }

		        if (!$nao_calcula_km) {
					echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
		        }

				if($inf_valores_adicionais || in_array($login_fabrica, array(145))){
					echo "<td class='menu_top' align='center'><strong>VALOR ADICIONAL</strong></td>";
				}

				if(in_array($login_fabrica, array(145))){
					echo "<TD class='menu_top' align='center'><B>TOTAL</B></TD>\n";
					echo "<TD class='menu_top' align='center'><B>TIPO ATENDIMENTO</B></TD>\n";
				}
			}

			echo "</TR>\n";
			echo "<TR>\n";
			echo "<TD class='table_line' nowrap>$sua_os&nbsp;</TD>\n";
			echo "<TD class='table_line' nowrap>$serie&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center'>$abertura&nbsp;</TD>\n";
			echo "<TD class='table_line' nowrap>".substr($consumidor_nome,0,18)." &nbsp;</TD>\n";
			echo "<TD class='table_line' nowrap>".$produto_referencia." - ".substr($produto_nome,0,17)."&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>$mao_de_obra $mao_de_obra_reduzida</TD>\n";

			if(!in_array($login_fabrica,array(106,108,111,121,123,125)) && !isset($novaTelaOs)){
				echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
			}else if (isset($novaTelaOs)) {
		        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
		        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);
		        if (!$nao_calcula_peca) {
					echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
		        }
		        if (!$nao_calcula_km) {
					echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada&nbsp;</TD>\n";
		        }
				if($inf_valores_adicionais || in_array($login_fabrica, array(145))){
					echo "<td class='menu_top' align='center'>".number_format($valores_adicionais,2,',','.')."</td>";
				}
			}

			echo "</TR>\n";
		} else {
			echo "<TR>\n";
			echo "<TD class='table_line' nowrap>$sua_os&nbsp;</TD>\n";
			echo "<TD class='table_line' nowrap>$serie&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center'>$abertura&nbsp;</TD>\n";
			echo "<TD class='table_line' nowrap>".substr($consumidor_nome,0,18)."&nbsp;</TD>\n";
			echo "<TD class='table_line' nowrap>".$produto_referencia." - ".substr($produto_nome,0,17)."&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>$mao_de_obra $mao_de_obra_reduzida</TD>\n";
			if(!in_array($login_fabrica,array(106, 108, 111, 121, 123, 125)) && !isset($novaTelaOs)){
				echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
			} else if (isset($novaTelaOs)) {
		        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
		        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);

		        if (!$nao_calcula_peca) {
					echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
		        }

		        if (!$nao_calcula_km) {
					echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada&nbsp;</TD>\n";
		        }

				if($inf_valores_adicionais || in_array($login_fabrica, array(145))){
					echo "<td class='menu_top' align='center'>".number_format($valores_adicionais,2,',','.')."</td>";
				}
			}
			echo "</TR>\n";

            if (pg_num_rows($resPecas) > 0) {
                echo '<tr>
                        <th class="menu_top" colspan="5"><b>Peça</b></th>
                        <th class="menu_top"><b>Qtde</b></th>
                        <th class="menu_top"><b>Valor</b></th>
                    </tr>';

                $pecas = pg_fetch_all($resPecas);
                foreach ($pecas as $c=>$peca) {
	                echo '<tr>
	                        <td class="table_line" colspan="5">'.$peca['peca_nome'].'</td>
	                        <td class="table_line" align="right">'.$peca['qtde'].'</td>
	                        <td class="table_line" align="right">'.number_format($peca['valor_peca'],2,',','.').'</td>
	                    </tr>';
                }
                echo '<tr>
                        <td colspan="7" class="table_line">&nbsp;</td>
                    </tr>';
            }
		}
	}//FIM FOR
	echo "</TR>\n";
	echo "<TR>\n";
	echo "<TD class='table_line' align='right' colspan='5'>Subtotal:</TD>\n";
	echo "<TD class='table_line' align='center'>".number_format($total_entrega_tecnica,2,',','.')."&nbsp;</TD>\n";
	echo "<TD class='table_line' align='center'>".number_format($total_qtde_km,2,',','.')."&nbsp;</TD>\n";
	echo "</TR>\n";
	echo "</TABLE>";
}


$layout_menu = "financeiro";
$title = "Relação de Ordens de Serviços";

?>

<style type="text/css">

.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1px solid;
	color:#000000;
	background-color: #ffffff;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1px solid;
	background-color: #ffffff;
}

.quebrapagina {
   page-break-before: always;
}

.tabela tr td{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 14px;
	font-weight: bold;
	border: 1px solid;
}

.tabela tr th{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 15px;
	font-weight: bold;
	border: 1px solid;
}
</style>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">

<?php foreach($extratos as $extrato): ?>
<div style="page-break-after: always; page-break-inside: avoid;">
<?

	if($login_fabrica == 74 ){
		$campo_cancelada = " tbl_os.cancelada,  ";
	}

$sql = "SELECT lpad (tbl_os.sua_os,10,'0') AS ordem,
		tbl_os.os,
		tbl_os.sua_os,
		to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS data,
		to_char (tbl_os.data_abertura ,'DD/MM/YYYY') AS abertura,
		to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
		to_char (tbl_os.finalizada,'DD/MM/YYYY') AS finalizada,
		tbl_os.consumidor_revenda,
		tbl_os.serie,
		tbl_os.codigo_fabricacao,
		tbl_os.consumidor_nome,
		tbl_os.consumidor_cidade,
		tbl_os.consumidor_fone,
		tbl_os.revenda_nome,
		tbl_os.troca_garantia,
		tbl_os.data_fechamento,
		(SELECT SUM (tbl_os_item.qtde * COALESCE(tbl_os_item.custo_peca,tbl_os_item.preco)) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto)JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica WHERE tbl_os_produto.os = tbl_os.os AND tbl_servico_realizado.troca_de_peca IS TRUE) AS total_pecas,
		tbl_os.mao_de_obra AS total_mo,
		tbl_os.qtde_km AS qtde_km,
		tbl_os.cortesia,
		tbl_os.nota_fiscal,
		tbl_os.nota_fiscal_saida,
		tbl_os.pedagio,
		to_char (tbl_os.data_nf,'DD/MM/YYYY') AS data_nf,
		tbl_os.posto,
		tbl_produto.referencia,
		tbl_produto.descricao,
		tbl_os_extra.extrato,
		tbl_os_extra.os_reincidente,
		tbl_os_extra.mao_de_obra_desconto,
		tbl_os_extra.valor_total_hora_tecnica,
		tbl_os_extra.obs_adicionais,
		tbl_os.observacao,
		tbl_os.motivo_atraso,
		tbl_os_extra.motivo_atraso2,
		tbl_os.obs_reincidencia,
		$campo_cancelada
		to_char (tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
		tbl_extrato.total AS total,
		to_char (tbl_extrato.mao_de_obra,'9,999,990.00') AS t_mao_de_obra,
		to_char (tbl_extrato.pecas,'9,999,990.00') AS t_pecas,
		to_char (tbl_extrato.deslocamento,'9,999,990.00') AS t_deslocamento,
		tbl_os.mao_de_obra AS mao_de_obra,
		to_char (tbl_os_extra.mao_de_obra,'9,999,990.00') AS mo_extra,
		to_char (tbl_os.pecas,'9,999,990.00') AS pecas,
		tbl_os.qtde_km_calculada,
		tbl_os.valores_adicionais,
		to_char (tbl_os.data_conserto, 'DD/MM/YYYY') as data_conserto,
		tbl_extrato.admin AS admin_aprovou,
		lpad (tbl_extrato.protocolo::text,5,'0') AS protocolo,
		tbl_posto.nome AS nome_posto,
		tbl_posto_fabrica.codigo_posto AS codigo_posto,
		tbl_extrato_pagamento.valor_total,
		tbl_extrato_pagamento.acrescimo,
		tbl_extrato_pagamento.desconto,
		tbl_extrato_pagamento.valor_liquido,
		tbl_extrato_pagamento.nf_autorizacao,
		to_char (tbl_extrato.previsao_pagamento,'DD/MM/YYYY') AS previsao_pagamento,
		to_char (tbl_extrato.data_recebimento_nf,'DD/MM/YYYY') AS data_recebimento_nf,
		to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento,
		to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento,
		tbl_extrato_pagamento.autorizacao_pagto,
		tbl_extrato_pagamento.obs,
		tbl_extrato_pagamento.extrato_pagamento,
		(SELECT COUNT(*) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) JOIN tbl_servico_realizado USING (servico_realizado) WHERE tbl_os_produto.os = tbl_os.os AND tbl_os_item.custo_peca = 0 AND tbl_servico_realizado.troca_de_peca IS TRUE) AS peca_sem_preco,
		(SELECT peca_sem_estoque FROM tbl_os_item JOIN tbl_os_produto using(os_produto) WHERE tbl_os_produto.os = tbl_os.os and peca_sem_estoque is true limit 1) AS peca_sem_estoque,
		$case_log
		tbl_os.data_fechamento - tbl_os.data_abertura AS intervalo,
		(SELECT login FROM tbl_admin WHERE tbl_admin.admin = tbl_os.admin AND tbl_admin.fabrica = $login_fabrica) AS admin,
		tbl_familia.descricao AS familia_descr,
		tbl_familia.familia AS familia_id,
		tbl_familia.codigo_familia AS familia_cod,
		case
			when tbl_os.fabrica in(52) then
				tbl_os_extra.valor_por_km
			else
				tbl_posto_fabrica.valor_km
		end AS valor_km,
		tbl_os.taxa_visita as taxa_visita_os,
		tbl_os_extra.taxa_visita
		FROM tbl_extrato
		LEFT JOIN tbl_extrato_pagamento ON  tbl_extrato_pagamento.extrato = tbl_extrato.extrato
		LEFT JOIN tbl_os_extra ON  tbl_os_extra.extrato = tbl_extrato.extrato
		LEFT JOIN tbl_os ON  tbl_os.os = tbl_os_extra.os
		$join_log
		LEFT JOIN tbl_produto ON  tbl_produto.produto = tbl_os.produto
		JOIN tbl_posto ON  tbl_posto.posto = tbl_extrato.posto
		JOIN tbl_posto_fabrica ON  tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_familia ON  tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND tbl_extrato.extrato = $extrato";

		if($login_fabrica == 45){ //HD 39933
			$sql .= " AND tbl_os.mao_de_obra not null AND tbl_os.pecas not null
				AND ((SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) IS NULL OR (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) NOT IN (15))";
		}
        if ($login_fabrica == 148) {
            $sql_yanmar = $sql." AND tbl_os.tipo_atendimento = 217 ";
            $sql .= " AND tbl_os.tipo_atendimento <> 217 ";
        }
		if(!in_array($login_fabrica, array(2, 50))){
			$sql .= " ORDER BY tbl_os_extra.os_reincidente,lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0') ASC,
				replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC;";
	        if ($login_fabrica == 148) {
	            $sql_yanmar .= "ORDER BY tbl_os_extra.os_reincidente, lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
	                        replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
	        }
		} else if ($login_fabrica == 50 ) { // HD 107642 (augusto)
			$sql .= " ORDER BY tbl_familia.descricao ASC,
				tbl_os_extra.os_reincidente,lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0') ASC,
				replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC;";
		} else {
			$sql .= " ORDER BY replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC;";
		}

$res = pg_query($con,$sql);
if ($login_fabrica == 148) {
    $res_yanmar = pg_query($con,$sql_yanmar);
}

if (pg_num_rows($res) == 0) {
	if ($login_fabrica == 148) {
		if (pg_num_rows($res_yanmar) == 0) {
			echo "<TABLE width='665' height='50'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
		}else{
			$Monta_Tabela_Yanmar = 1;
			GeraTabelaEntregaTecnica($res_yanmar);
		}
	}else{
		echo "<TABLE width='665' height='50'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
	}
}else{
	if ($login_fabrica == 11){
		$os_array = array();
		$sql =  "SELECT intervencao.os
			FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os = 65 ) intervencao
			JOIN tbl_os ON tbl_os.os = intervencao.os AND tbl_os.fabrica=$login_fabrica
			JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_os_extra.extrato=$extrato
			WHERE tbl_os.fabrica =$login_fabrica";
		$res_status = pg_exec($con,$sql);
		$os_array = array();
		$total=pg_numrows($res_status);
		for ($t = 0 ; $t < $total ; $t++) {
			array_push($os_array,pg_result($res_status,$t,os));
		}
	}

	if($login_fabrica == 52){
		$colspan = 4;
	}else{
		$colspan = 2;
	}

	if (in_array($login_fabrica, array(52, 72))) {
		$colspan2 = 11;
	}else if ($login_fabrica == 15) {
        		$colspan2 = 8;
	}else if ($login_fabrica == 145) {
		$colspan2 = 9;
		$colspan_extrato = 2;
	} else if ($login_fabrica == 151) {
        $colspan_extrato = "";
        $colspan2 = 5;
	} elseif(in_array($login_fabrica, array(74,85))){
		$colspan2 = 8;
	} elseif($login_fabrica == 158){
		$colspan2 = 10;
	} elseif($login_fabrica == 125){
		$colspan2 = 8;
	} else {
		$colspan2 = 7;
	}

	if(in_array($login_fabrica, array(52, 72))){
		$colspan3 = 8;
	} else if (in_array($login_fabrica, array(15, 85))) {
        		$colspan3 = 5;
	} else if ($login_fabrica == 151) {
        $colspan3 = 2;
	} else if ($login_fabrica == 158) {
        $colspan3 = 7;
	} else if ($login_fabrica == 125) {
        $colspan3 = 5;
	} else {
		$colspan3 = 4;
	}

	if($login_fabrica == 1){
		$login_fabrica_logo = "logo_black_2016.png";
	}

	if($login_fabrica == 85){
        $sqlOS = "
        SELECT  tbl_os_extra.os as os_comentario,
                tbl_os_extra.obs_adicionais,
                tbl_os.mao_de_obra,
                tbl_os.qtde_km_calculada,
                tbl_os.pedagio,
                tbl_extrato_lancamento.valor,
                to_char(tbl_extrato_lancamento.data_lancamento,'DD/MM/YYYY') AS data_lancamento,
                tbl_admin.login
        FROM    tbl_os_extra
        JOIN    tbl_os                  ON  tbl_os_extra.os = tbl_os.os
                                        AND tbl_os.fabrica = $login_fabrica
   LEFT JOIN    tbl_extrato_lancamento  ON  tbl_os_extra.extrato = tbl_extrato_lancamento.extrato
                                        AND tbl_extrato_lancamento.fabrica = $login_fabrica
                                        AND tbl_extrato_lancamento.os = tbl_os_extra.os
   LEFT JOIN    tbl_admin               ON  tbl_admin.admin = tbl_extrato_lancamento.admin
                                        AND tbl_admin.fabrica = $login_fabrica
        WHERE   tbl_os_extra.extrato = $extrato
        AND     obs_adicionais <> 'null'
        AND     obs_adicionais IS NOT NULL
        AND     (tbl_extrato_lancamento.descricao NOT ILIKE '%diferenciado' OR tbl_extrato_lancamento.descricao IS NULL)";
        $resOS = pg_query($con, $sqlOS);
        if(pg_num_rows($resOS) > 0){

            $countOS = pg_num_rows($resOS);

            $comentario_os  = pg_fetch_result($resOS, $p, 'obs_adicionais');

            if(strlen($comentario_os) > 0){

                echo "<br /><table id='tabela_obs_ad' width='700' border='0' cellspacing='1' cellpadding='1'>\n";
                echo "<caption class='menu_top' style='background-color: #596D9B;color: white;'><b>VALORES ADICIONAIS LANÇADOS NO EXTRATO</b></caption>\n";
                echo "<thead><tr class='menu_top' style='background-color: #596D9B;color: white;'>\n";
                echo "<th width='18%'>DATA LANÇAMENTO</th>\n";
                echo "<th width='18%'>ORDEM DE SERVIÇO</th>\n";
                echo "<th>HISTÓRICO</th>\n";
                echo "<th width='10%'>VALOR</th>\n";
                echo "<th width='18%'>ADMIN</th>\n";
                echo "</tr></thead>\n";

                for ($p=0; $p < $countOS; $p++) {

                    $osExtrato        = pg_fetch_result($resOS, $p, 'os_comentario');
                    $comentario_os    = pg_fetch_result($resOS, $p, 'obs_adicionais');
                    $valor_pedagio    = pg_fetch_result($resOS, $p, 'pedagio');
                    $valor_km         = pg_fetch_result($resOS, $p, 'qtde_km_calculada');
                    $valor_mao_obra   = pg_fetch_result($resOS, $p, 'mao_de_obra');
                    $valor_avulso     = pg_fetch_result($resOS, $p, 'valor');
                    $data_lancamento  = pg_fetch_result($resOS, $p, 'data_lancamento');
                    $admin            = pg_fetch_result($resOS, $p, 'login');
                    $comentario_os    = utf8_decode($comentario_os);

                    $colunaValorComentario = '';
                    $comentario_os = json_decode($comentario_os, true);

                    foreach ($comentario_os as $key => $value) {
                        if(!in_array($key,array('mao_de_obra','km','pedagio','avulso'))){
                            unset($comentario_os[$key]);
                        }
                    }

                    if (count($comentario_os)>1)
                        $spanrows = 'rowspan="'.count($comentario_os).'"';
                    else $spanrows = ' ';

                    // HD 2416981 - suporte: deixar sinalizado como lançamento avulso, para não
                    // confundir o Posto Autorizado.
                    //$cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';
                    $cor =  '#FFE1E1';

                    foreach ($comentario_os as $key => $value) {
                        $value = utf8_decode($value);
                        switch ($key) {
                            case 'mao_de_obra':
                            $key = "Mão de Obra";
                            $valorComentario = number_format($valor_mao_obra, 2, ',', '.');
                            break;
                            case 'km':
                            $key = "KM";
                            $valorComentario = number_format($valor_km, 2, ',', '.');
                            break;
                            case 'pedagio':
                            $key = "Pedágio";
                            $valorComentario = number_format($valor_pedagio, 2, ',', '.');
                            break;
                            case 'avulso':
                            $key = "Avulso";
                            $valorComentario = number_format($valor_avulso, 2, ',', '.');
                            break;
                        }

                        if (strlen($spanrows)) {
                            echo "<tr class='table_line' style='background-color: $cor;'>\n";
                            echo "<td align='right' $spanrows>$data_lancamento</td>";
                            echo "<td align='right' $spanrows>$osExtrato</td>";
                            $spanrows = ''; // exclui a primeira TD para o resto do TR
                        } else {
                            echo "<tr class='table_line' style='background-color: $cor;'>";
                        }
                        echo "<td><p class='servico'><span class='servico'>$key</span>$value</p></td>";
                        echo "<td align='right'>$valorComentario</td>";
                        echo "<td align='center'>$admin</td>";
                        echo '</tr>';
                    }
                }
                echo "</table><BR /><br />";
            }
        }
    }

	echo "<TABLE width='665' border='0' cellspacing='0' cellpadding='1'>";
	echo "<TR>";
	echo "<TD class='menu_top' align='center' colspan='$colspan' rowspan='2'><IMG SRC='logos/$login_fabrica_logo' border='0' width='150'></TD>";
	echo "<TD class='menu_top' align='left' colspan='$colspan2'> Posto: <B>".pg_result ($res,0,codigo_posto)." - ".pg_result($res,0,nome_posto)."</B>&nbsp;</TD>";
	echo "</TR>";

	echo "<TR>";
	echo "<TD class='menu_top' align='left' nowrap colspan='$colspan_extrato' > Extrato: <B>".(($login_fabrica == 1) ? pg_result($res,0,protocolo) : $extrato)."</B> </TD>";
	echo "<TD class='menu_top' align='left' nowrap colspan='$colspan_extrato' > Data: <B>".pg_result($res,0,data_geracao)."</B>&nbsp;</TD>";
	echo "<TD class='menu_top' align='left' nowrap> Qtde de OS: <B>". pg_num_rows($res) ."</B>&nbsp;</TD>";
	if($login_fabrica == 74){
		echo "<TD class='menu_top' align='right' nowrap colspan='5'>Total: <B>R$ ".pg_result($res,0,total)."</B>&nbsp;</TD>";
	}else{
		echo "<TD class='menu_top' align='right' nowrap colspan='$colspan3'>Total: <B>R$ ".number_format(pg_result ($res,0,total),2,',','.')."</B>&nbsp;</TD>";
	}
	echo "</TR>";

	echo "<TR>\n";
	echo "<TD class='menu_top' align='center' width='75'><B>OS</B></TD>\n";
	if ($login_fabrica == 158) {
		echo "<TD class='menu_top' align='center' width='75'><B>TIPO OS</B></TD>\n";
	}
	echo "<TD class='menu_top' align='center' width='75'><B>SÉRIE</B></TD>\n";

	echo "<TD class='menu_top' align='center' width='75'><B>ABERTURA</B></TD>\n";

	if ($login_fabrica == 183){
		echo "<TD class='menu_top' align='center' width='75'><B>FECHAMENTO</B></TD>\n";
	}

	if($login_fabrica == 52){ //hd_chamado=2598225
		echo "<TD class='menu_top' align='center' width='75'><B>DATA CONSERTO</B></TD>\n";
	}

	if(in_array($login_fabrica, array(11,51,158))) echo "<TD class='menu_top' align='center' width='75'><B>FECHAMENTO</B></TD>\n";

	if($login_fabrica == 2) echo "<TD class='menu_top' align='center' width='75'><B>FINALIZADA</B></TD>\n";

    if ($login_fabrica == 151)  echo "<TD class='menu_top' align='center' width='75'><B>C / R</B></TD>\n";

    if (in_array($login_fabrica, array(6,51,151,165))) {
		echo "<TD class='menu_top' align='center'><B>CONSUMIDOR / REVENDA</B></TD>\n";
    }else{
		echo "<TD class='menu_top' align='center'><B>CONSUMIDOR</B></TD>\n";
    }

	echo ($login_fabrica == 158) ? "<TD class='menu_top' align='center'><B>CIDADE</B></TD>\n" : "";

	echo "<TD class='menu_top' align='center'><B>PRODUTO</B></TD>\n";

	if ($login_fabrica == 125) {
		echo "<TD class='menu_top' align='center'><B>Valor Adicional</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>Total KM</B></TD>\n";
	}

	if($login_fabrica == 52){
		echo "<TD class='menu_top' align='center'><B>NOTA FISCAL</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>DATA NF</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>PEDÁGIO</B></TD>\n";
	}

	# HD 936143
	if($login_fabrica == 80){
		echo "<TD class='menu_top' align='center'><B>REVENDA</B></TD>\n";
	}

	if (!in_array($login_fabrica, array(169,170))) {
		echo "<TD class='menu_top' align='center'><B>MÃO OBRA</B></TD>\n";
	}

	if(in_array($login_fabrica, array(52,72))){
		echo "<TD class='menu_top' align='center'><B>QTDE KM</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>VALOR POR KM</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>TOTAL KM</B></TD>\n";
	}

	if (in_array($login_fabrica, array(50,85))){
		echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
		if ($login_fabrica == 85){
			echo "<TD class='menu_top' align='center'><B>PEDÁGIO</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>BONIFICAÇÃO</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>AVULSO</B></TD>\n";
		}

	} else if (in_array($login_fabrica, array(15,74))){
		echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";

		if($login_fabrica == 74){
			echo "<TD class='menu_top' align='center'><B>Situação</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>Observação</B></TD>\n";
		}

	}elseif($login_fabrica == 90){
       	echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
       	echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>VISITA</B></TD>\n";
	}elseif(!in_array($login_fabrica,array(106,108,111,121,123,125)) && !isset($novaTelaOs)){
		echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
	} elseif ( in_array($login_fabrica, array(125)) ) {
		echo "<TD class='menu_top' align='center'><B>Taxa Visita</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>Total KM + MO + TAXA V + VA</TD>\n";
	} else if (in_array($login_fabrica, array(169,170))) { ?>
		<td class="menu_top" align="center"><b>Valor OS SAP</b></td>
	<? } else if (isset($novaTelaOs)) {
        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);

        if (!$nao_calcula_peca) {
			echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
        }

        if (!$nao_calcula_km) {
			echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
        }

		if($inf_valores_adicionais || in_array($login_fabrica, array(145))){
			echo "<td class='menu_top' align='center'><strong>VALOR ADICIONAL</strong></td>";
		}

		if(in_array($login_fabrica, array(145))){
			echo "<TD class='menu_top' align='center'><B>TOTAL</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>TIPO ATENDIMENTO</B></TD>\n";
		}
	}

	if($login_fabrica == 52){
		echo "<TD class='menu_top' align='center'><B>TOTAL + KM + MO + PEÇAS</B></TD>\n";
	}

	echo "</TR>\n";

	$total_qtde_km_calculada = 0;
	$total_qtde_km = 0;
	$total_valor_km = 0;
	$total_pedagio = 0;
	$total_pecas = 0;
	$total_valores_adicionais = 0;
	$t_taxa_visita_os = 0;

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		flush();
		$os = trim(pg_result ($res,$i,os));
		$data = trim(pg_result ($res,$i,data));
		$abertura = trim(pg_result ($res,$i,abertura));
		$fechamento = trim(pg_result ($res,$i,fechamento));
		$finalizada = trim(pg_result ($res,$i,finalizada));
		$sua_os = trim(pg_result ($res,$i,sua_os));
		$serie = trim(pg_result ($res,$i,serie));
		$consumidor_nome = trim(pg_result ($res,$i,consumidor_nome));
		$consumidor_cidade = trim(pg_result ($res,$i,consumidor_cidade));
		$produto_nome = trim(pg_result ($res,$i,descricao));
		$produto_referencia = trim(pg_result ($res,$i,referencia));
		$data_fechamento = trim(pg_result ($res,$i,data_fechamento));
		$mao_de_obra = trim(pg_result ($res,$i,mao_de_obra));
		$mo_extra = trim(pg_result ($res,$i,mo_extra));
		$pecas = trim(pg_result ($res,$i,pecas));
		$qtde_km = trim(pg_result ($res,$i,qtde_km));
		$valor_km = trim(pg_result ($res,$i,valor_km));
		$valores_adicionais = trim(pg_result ($res,$i,valores_adicionais));
		$qtde_km_calculada = trim(pg_result ($res,$i,qtde_km_calculada));
		if ($login_fabrica == 148) {
			$t_mao_de_obra += trim(pg_result ($res,$i,total_mo));
		}else{
			$t_mao_de_obra = trim(pg_result ($res,$i,t_mao_de_obra));
		}
		$t_pecas = trim(pg_result ($res,$i,total_pecas));
		$t_deslocamento = trim(pg_result ($res,$i,t_deslocamento));
		$extrato_pagamento = trim(pg_result ($res,0,extrato_pagamento)) ;
		$valor_total = trim(pg_result ($res,0,valor_total)) ;
		$acrescimo = trim(pg_result ($res,0,acrescimo)) ;
		$desconto = trim(pg_result ($res,0,desconto)) ;
		$valor_liquido = trim(pg_result ($res,0,valor_liquido)) ;
		$nf_autorizacao = trim(pg_result ($res,0,nf_autorizacao)) ;
		$data_vencimento = trim(pg_result ($res,0,data_vencimento)) ;
		$autorizacao_pagto = trim(pg_result ($res,0,autorizacao_pagto)) ;
		$revenda_nome = trim(pg_result ($res,$i,revenda_nome)) ;
		$consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda)) ;
		$nota_fiscal = trim(pg_result ($res,$i,nota_fiscal)) ;
		$data_nf = trim(pg_result ($res,$i,data_nf)) ;
		$taxa_visita = trim(pg_result ($res,$i,taxa_visita)) ;
		$taxa_visita_os = trim(pg_result ($res,$i,taxa_visita_os)) ;
		$os_reincidente = trim(pg_result ($res,$i,os_reincidente)) ;
		$data_conserto = pg_fetch_result($res, $i, 'data_conserto'); //hd_chamado=2598225
		$valor_total_hora_tecnica = pg_fetch_result($res, $i, "valor_total_hora_tecnica");
		$total_valor_km = $valor_km;
		$total_qtde_km_calculada += $qtde_km_calculada;
		$total_qtde_km += $qtde_km;

		if(isset($novaTelaOs)){
			$total_pecas += $pecas;
		}elseif (!in_array($login_fabrica,array(42,106,108,111,121,123,125)) && !isset($novaTelaOs)) {
			$total_pecas += $pecas;
		}else{
			$total_pecas += $t_pecas;
		}

		

		$valores_adicionais = (empty($valores_adicionais)) ? 0 : $valores_adicionais;

		$total_os = 0;

		if (in_array($login_fabrica, array(52, 85))){
			$obs_adicionais = pg_fetch_result($res, $i, obs_adicionais);
			$pedagio = trim(pg_result($res,$i,pedagio));
			$pedagio = ($pedagio == 0 or strlen($pedagio) == 0) ? "0.00" : $pedagio;
			$total_pedagio += $pedagio;
		}

		if($inf_valores_adicionais){
			$total_os = $qtde_km_calculada + $mao_de_obra + $valores_adicionais + $pecas;
			$total_valores_adicionais += $valores_adicionais;
		} else if (in_array($login_fabrica, array(52))) {
			$total_os = $qtde_km_calculada + $pecas + $mao_de_obra + $pedagio;
			$total_geral_os += $total_os;
		} else {
			$total_os = $qtde_km_calculada + $pecas + $mao_de_obra;
		}
		if($login_fabrica == 90) {
			$pecas = 0;
			$total_taxa_visita    = $total_taxa_visita+$taxa_visita;
		}
		if (in_array($login_fabrica, array(125))) {
			$t_taxa_visita_os = $t_taxa_visita_os + $taxa_visita_os;
		}



		if ($login_fabrica == 80){
			$mao_de_obra_desconto = trim(pg_result($res,$i, mao_de_obra_desconto));
			$mao_de_obra = ( $mao_de_obra_desconto > 0 and strlen($mao_de_obra_desconto)>0 ) ? $mao_de_obra - $mao_de_obra_desconto : $mao_de_obra;
			$t_mao_de_obra = ( $mao_de_obra_desconto > 0 and strlen($mao_de_obra_desconto)>0 ) ? $t_mao_de_obra - $mao_de_obra_desconto : $t_mao_de_obra;
		}

		if($login_fabrica == 3){ //HD 78666
			$mao_de_obra = $mo_extra;
		}
		if($consumidor_revenda == "R" && (in_array($login_fabrica, array(6, 51, 151, 165)))) {
			$consumidor_nome = $revenda_nome;
		}

        if ($login_fabrica == 151) {
            $sqlPecas = "
                SELECT  tbl_peca.peca,
                        tbl_peca.referencia || ' - ' || tbl_peca.descricao AS peca_nome,
                        tbl_os_item.qtde,
                        (tbl_os_item.qtde * tbl_os_item.custo_peca) AS valor_peca
                FROM    tbl_peca
                JOIN    tbl_os_item     USING (peca)
                JOIN    tbl_os_produto  USING (os_produto)
                WHERE   tbl_os_produto.os = $os
            ";
//             exit(nl2br($sqlPecas));
            $resPecas = pg_query($con,$sqlPecas);
        }

		$mao_de_obra_reduzida="";
		if ($login_fabrica == 11 AND count($os_array)>0){
			if (in_array($os,$os_array)){
				$mao_de_obra_reduzida = " <b>*</b>";
			}
		}

		if (strlen($qtde_km_calculada)==0){
			$qtde_km_calculada = "0.00";
		}

		if(strlen($pecas)==0){
			$pecas = '0.00';
		}

		if(strlen($qtde_km) == 0){
			$qtde_km = 0;
		}

		if(in_array($login_fabrica, array(145))){
			$sql_ta = "SELECT tbl_tipo_atendimento.descricao, tbl_os.data_fechamento - tbl_os.data_abertura AS dias
					FROM tbl_os
					JOIN tbl_os_extra USING(os)
					JOIN tbl_tipo_atendimento USING(tipo_atendimento)
					WHERE tbl_os.os = $os AND tbl_os.fabrica = $login_fabrica";
			$res_ta = pg_query($con,$sql_ta);
			$tipo_atendimento   = pg_result($res_ta,0,'descricao');
		}

		$pecas	= number_format($pecas, 2, ',', '.');
		$valor_km = number_format($valor_km, 2, ',', '.');
		$qtde_km_calculada = number_format($qtde_km_calculada, 2, ',', '.');
		$mao_de_obra	= number_format($mao_de_obra, 2, ',', '.');

		if($login_fabrica == 138) {
			$sql_ta = "SELECT descricao,referencia, os_produto
					FROM tbl_os
					JOIN tbl_os_produto USING(os)
					JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
					WHERE tbl_os.os = $os AND tbl_os.fabrica = $login_fabrica
					order by os_produto limit 1";
			$res_ta = pg_query($con,$sql_ta);
			$produto_nome   = pg_result($res_ta,0,'descricao');
			$produto_referencia   = pg_result($res_ta,0,'referencia');
		}
		// FAZ A QUEBRA DE PAGINAS DO RELATORIO, PODE-SE MUDAR O NUMERO DE LINHAS
		if (($i % 42 == 0) && ($i != 0)) {
			if($login_fabrica == 52 AND strlen($os)>0){

				$sql_def_const = "SELECT tbl_defeito_constatado.defeito_constatado,
					                            tbl_defeito_constatado.descricao AS desc_def_constatado,
					                            tbl_defeito_constatado_grupo.descricao AS desc_def_constatado_grupo
					                            FROM tbl_os
					                            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
					                            JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo
					                            WHERE tbl_os.os = $os;";
                			$res_def_const = pg_query($con, $sql_def_const);
				if(pg_num_rows($res_def_const)>0){
					for($z = 0; $z < pg_num_rows($res_def_const); $z++){
						$desc_def_constatado            = pg_result($res_def_const,$z,"desc_def_constatado");
                        				$desc_def_constatado_grupo      = pg_result($res_def_const,$z,"desc_def_constatado_grupo");

						echo "<TR>";
						echo "<TD colspan='2' class='table_line'>&nbsp;</TD>";
						echo "<TD colspan='3' class='table_line' nowrap>GRUPO DEFEITO CONSTATADO: $desc_def_constatado_grupo</TD>";
						echo "<TD colspan='9' class='table_line' nowrap>DEFEITO CONSTATADO: $desc_def_constatado</TD>";
						echo "</TR>";
					}
				}

                			if (strlen($os_reincidente) > 0){ ?>
			                        <tr>
			                            <td colspan="15" style="text-align:center;font-weight:bold;" class='table_line'>DADOS DA OS REINCIDENTE</td>
			                        </tr>

				<?	$sqlReinc = "SELECT  tbl_os.os AS reinc_os,
					                            tbl_os.sua_os AS reinc_sua_os,
					                            tbl_defeito_constatado.descricao AS reinc_def_constatado,
					                            tbl_defeito_constatado_grupo.descricao AS reinc_grupo_def_constatado
					                    FROM tbl_os
					                    JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
					                    JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo
					                    WHERE tbl_os.fabrica = $login_fabrica
					                    AND tbl_os.os = $os_reincidente;";
        					$resReinc = pg_query($con,$sqlReinc);

					$reinc_os = pg_fetch_result($resReinc,0,reinc_os);
					$reinc_sua_os = pg_fetch_result($resReinc,0,reinc_sua_os);
					$reinc_def_constatado = pg_fetch_result($resReinc,0,reinc_def_constatado);
					$reinc_grupo_def_constatado = pg_fetch_result($resReinc,0,reinc_grupo_def_constatado);

				?>
					<tr>
						<td colspan="2" class='table_line' style="text-align:right"><?=$reinc_sua_os?></td>
						<td class='table_line'>&nbsp;</td>
						<td colspan="3" class='table_line'>GRUPO DEFEITO: <?=$reinc_grupo_def_constatado?></td>
						<td colspan="9" class='table_line'>DEF. CONSTATADO: <?=$reinc_def_constatado?></td>
					</tr>
					<tr>
						<td colspan="14" class='table_line'>&nbsp;</td>
					</tr>
				<?
                			}

			}

			if(in_array($login_fabrica, array(11, 51,151,158))){
				$col = "5";
			}else if($login_fabrica == 52){
				$col = "8";
			}elseif(in_array($login_fabrica, array(142))){
				$col = "9";
			}elseif(in_array($login_fabrica, array(145))){
				$col = "8";
			}else{
				$col = "4";
			}

			if ($login_fabrica == 151) {
                $colspan_extrato = "";
			} else {
                $colspan_extrato = 2;
			}

		   //MONTA O CABEÇALHO DEPOIS DA QUEBRA DE PAGINA
 		    echo "<TR class='quebrapagina'>\n";
			echo "<TD class='menu_top' align='center' colspan='$col' height='30'><B>POSTO: ".pg_result ($res,0,codigo_posto)." - ".pg_result ($res,0,nome_posto)."</B></TD>\n";
			echo "<TD class='menu_top' align='center' ><B>Data: ".pg_result ($res,0,data_geracao)."</B></TD>\n";
			echo "<TD class='menu_top' align='center' valign='center' colspan='$colspan_extrato'><B>Extrato:$extrato</B></TD>\n";
			echo "</TR>\n";
			//FIM DO CABEÇALHO

			//INICIO
			echo "<TR>\n";
			echo "<TD class='menu_top' align='center' width='75'><B>OS</B></TD>\n";
			if ($login_fabrica == 158) {
				echo "<TD class='menu_top' align='center' width='75'><B>TIPO OS</B></TD>\n";
			}
			echo "<TD class='menu_top' align='center' width='75'><B>SÉRIE</B></TD>\n";

			echo "<TD class='menu_top' align='center' width='75'><B>ABERTURA</B></TD>\n";

			if($login_fabrica == 52){
				echo "<TD class='menu_top' align='center' width='75'><B>DATA CONSERTO</B></TD>\n";
			}

			if(in_array($login_fabrica, array(11, 51, 158))) echo "<TD class='menu_top' align='center' width='75'><B>FECHAMENTO</B></TD>\n";

			if($login_fabrica == 2) echo "<TD class='menu_top' align='center' width='75'><B>FINALIZADA</B></TD>\n";

            if ($login_fabrica == 151)  echo "<TD class='menu_top' align='center' width='75'><B>C / R</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>CONSUMIDOR</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>PRODUTO</B></TD>\n";

			if($login_fabrica == 52){
				echo "<TD class='menu_top' align='center'><B>NOTA FISCAL</B></TD>\n";
				echo "<TD class='menu_top' align='center'><B>DATA NF</B></TD>\n";
				echo "<TD class='menu_top' align='center'><B>PEDÁGIO</B></TD>\n";
			}

			if($login_fabrica == 80){
				echo "<TD class='menu_top' align='center'><B>REVENDA</B></TD>\n";
			}

			if (!in_array($login_fabrica, array(169,170))) {
				echo "<TD class='menu_top' align='center'><B>MÃO OBRA</B></TD>\n";
			}

			if (in_array($login_fabrica, array(52,72))) {
				echo "<TD class='menu_top' align='center'><B>QTDE KM</B></TD>\n";
				echo "<TD class='menu_top' align='center'><B>VALOR POR KM</B></TD>\n";
				echo "<TD class='menu_top' align='center'><B>TOTAL KM</B></TD>\n";
			}

			if (in_array($login_fabrica, array(50,85))) {
				echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
				if ($login_fabrica == 85){
					echo "<TD class='menu_top' align='center'><B>PEDÁGIO</B></TD>\n";
					echo "<TD class='menu_top' align='center'><B>BONIFICAÇÃO</B></TD>\n";
				}

			} else if (in_array($login_fabrica, array(15,74))){
				echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
				echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
			} else if ($login_fabrica == 158) {
				echo "<td class='menu_top' align='center'><strong>VALOR ADICIONAL</strong></td>";
				echo "<TD class='menu_top' align='center'><B>TOTAL KM</B></TD>\n";
				echo "<TD class='menu_top' align='center'><B>TOTAL</B></TD>\n";
			} elseif ($login_fabrica == 90) {
		                	echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
		                	echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
				echo "<TD class='menu_top' align='center'><B>VISITA</B></TD>\n";
			}elseif(!in_array($login_fabrica,array(106,108,111,121,123,125)) && !isset($novaTelaOs)){
				echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
			} elseif(in_array($login_fabrica, array(125))) {
				echo "<TD class='menu_top' align='center'><B>Taxa Visita</B></TD>\n";
			} else if (in_array($login_fabrica, array(169,170))) { ?>
				<td class="menu_top" align="center"><b>Valor OS SAP</b></td>
			<? } else if (isset($novaTelaOs)) {
		        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
		        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);

		        if (!$nao_calcula_peca) {
					echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
		        }

		        if (!$nao_calcula_km) {
					echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
		        }

				if($inf_valores_adicionais || in_array($login_fabrica, array(145))){
					echo "<td class='menu_top' align='center'><strong>VALOR ADICIONAL</strong></td>";
				}

				if(in_array($login_fabrica, array(145))){
					echo "<TD class='menu_top' align='center'><B>TOTAL</B></TD>\n";
					echo "<TD class='menu_top' align='center'><B>TIPO ATENDIMENTO</B></TD>\n";
				}
			}

			if($login_fabrica == 52){
				echo "<TD class='menu_top' align='center'><B>TOTAL + KM + MO + PEÇAS</B></TD>\n";
			}

			echo "</TR>\n";

			echo "<TR>\n";

			echo "<TD class='table_line' nowrap>$sua_os&nbsp;</TD>\n";
			if ($login_fabrica == 158) {
				echo "<TD class='table_line' align='center'>$tipo_de_os&nbsp;</TD>\n";
			}

			echo "<TD class='table_line' nowrap>$serie&nbsp;</TD>\n";

			echo "<TD class='table_line' align='center'>$abertura&nbsp;</TD>\n";
			if($login_fabrica == 52){
				echo "<td class='table_line' nowrap>$data_conserto</td>\n"; //hd_chamado=2598225
			}
			if(in_array($login_fabrica, array(11, 51, 158))) echo "<TD class='table_line' align='center'>$fechamento</TD>\n";

			if($login_fabrica == 2) echo "<TD class='table_line' align='center'>$finalizada</TD>\n";


			if ($login_fabrica == 151) {
                echo "<TD class='table_line' align='center'>";
                echo ($consumidor_revenda == 'C') ? "CONSUMIDOR" : "REVENDA";
                echo "</TD>\n";
            }
			echo "<TD class='table_line' nowrap>".substr($consumidor_nome,0,18)." &nbsp;</TD>\n";
		    echo (($login_fabrica == 158)) ? "<TD class='table_line' nowrap>$consumidor_cidade</TD>\n" : "";

			if($login_fabrica == 2){
				echo "<TD class='table_line' nowrap>$produto_referencia&nbsp;</TD>\n";
			}else{
				echo "<TD class='table_line' nowrap>".$produto_referencia." - ".substr($produto_nome,0,17)."&nbsp;</TD>\n";
			}

			if($login_fabrica == 52){
				echo "<TD class='table_line' align='center' nowrap>$nota_fiscal &nbsp;</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$data_nf &nbsp;</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>".number_format($pedagio, 2, ',', '.')." &nbsp;</TD>\n";
			}

			# HD 936143
			if($login_fabrica == 80){
				echo "<TD class='table_line' align='left' nowrap>$revenda_nome &nbsp;</TD>\n";
			}

			if (!in_array($login_fabrica, array(169,170))) {
				echo "<TD class='table_line' align='center' nowrap>$mao_de_obra $mao_de_obra_reduzida</TD>\n";
			}

			if(in_array($login_fabrica, array(52, 72))){
				echo "<TD class='table_line' align='center' nowrap>$qtde_km &nbsp;</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$valor_km &nbsp;</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada&nbsp;</TD>\n";
			}

			if (in_array($login_fabrica, array(50,85))){
				echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada&nbsp;</TD>\n";
				if ($login_fabrica == 85){
					echo "<TD class='table_line' align='center' nowrap>".number_format($pedagio,2,',','.')." &nbsp;</TD>\n";
					echo "<TD class='table_line' align='center' nowrap>".number_format($valores_adicionais,2,',','.')." &nbsp;</TD>\n";
				}

			}elseif(in_array($login_fabrica, array(15,74))){
				echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada</TD>\n";
			}elseif($login_fabrica == 90){
 				echo "<TD class='table_line' align='center' nowrap>".number_format($pecas,2,',','.')."</TD>\n";
           		echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>".number_format($taxa_visita,2,',','.')."</TD>\n";

			}elseif(!in_array($login_fabrica,array(106,108,111,121,123,125)) && !isset($novaTelaOs)){
				echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
			} elseif ( in_array($login_fabrica, array(125)) ) {
				echo "<TD class='table_line' align='center' nowrap>".number_format($taxa_visita_os,2,',','.')."</TD>\n";
			} else if (in_array($login_fabrica, array(169,170))) { ?>
				<td class="table_line" align="center" nowrap><?= number_format($valor_total_hora_tecnica, 2, ",", "."); ?></td>
			<? } else if (isset($novaTelaOs)) {
		        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
		        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);
		        if (!$nao_calcula_peca) {
					echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
		        }
		        if (!$nao_calcula_km) {
					echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada&nbsp;</TD>\n";
		        }
				if($inf_valores_adicionais || in_array($login_fabrica, array(145))){
					echo "<td class='menu_top' align='center'>".number_format($valores_adicionais,2,',','.')."</td>";
				}
				if(in_array($login_fabrica, array(145))){
					$total_fabrimar = $valores_adicionais + str_replace(",", ".", $qtde_km_calculada) +$mao_de_obra;
					$total_fabrimar = $total_fabrimar +  $pecas ;
					echo "<TD class='menu_top' align='center'><B>".number_format($total_fabrimar,2,',','.')."</B></TD>\n";
					echo "<TD class='menu_top' align='center'><B>$tipo_atendimento</B></TD>\n";
				}
			}

			if($login_fabrica == 52){
				echo "<TD class='table_line' align='center' nowrap>".number_format($total_os,2,',','.')."</TD>\n";
			}

			echo "</TR>\n";
		} else {
			echo "<TR>\n";
			echo "<TD class='table_line' nowrap>$sua_os&nbsp;</TD>\n";
			if ($login_fabrica == 158) {
				echo "<TD class='table_line' align='center'>$tipo_de_os&nbsp;</TD>\n";
			}

			echo "<TD class='table_line' nowrap>$serie&nbsp;</TD>\n";

			echo "<TD class='table_line' align='center'>$abertura&nbsp;</TD>\n";

			if ($login_fabrica == 183) {
				echo "<TD class='table_line' align='center'>$fechamento&nbsp;</TD>\n";
			}

			if($login_fabrica == 52){
				echo "<td class='table_line' nowrap>$data_conserto</td>\n"; //hd_chamado=2598225
			}
			if(in_array($login_fabrica, array(11, 51, 158))) echo "<TD class='table_line' align='center'>$fechamento</TD>\n";

			if($login_fabrica == 2) echo "<TD class='table_line' align='center'>$finalizada</TD>\n";
            if ($login_fabrica == 151) {
                echo "<TD class='table_line' align='center'>";
                echo ($consumidor_revenda == 'C') ? "CONSUMIDOR" : "REVENDA";
                echo "</TD>\n";
            }
			echo "<TD class='table_line' nowrap>".substr($consumidor_nome,0,18)."&nbsp;</TD>\n";
		    echo (($login_fabrica == 158)) ? "<TD class='table_line' nowrap>$consumidor_cidade</TD>\n" : "";

			if($login_fabrica == 2){
				echo "<TD class='table_line' nowrap>$produto_referencia&nbsp;</TD>\n";
			}else{
				echo "<TD class='table_line' nowrap>".$produto_referencia." - ".substr($produto_nome,0,17)."&nbsp;</TD>\n";
			}

			if ($login_fabrica == 125) {
				echo "<TD class='table_line' align='center' nowrap>".number_format($valores_adicionais,2,",",".")."&nbsp;</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>".number_format($qtde_km_calculada,2,",",".")."&nbsp;</TD>\n";
				$ttl_km += $qtde_km_calculada;
			}

			if($login_fabrica == 52){
				echo "<TD class='table_line' align='center' nowrap>$nota_fiscal&nbsp;</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$data_nf&nbsp;</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>".number_format($pedagio, 2, ',', '.')."&nbsp;</TD>\n";
			}

			# HD 936143
			if($login_fabrica == 80){
				echo "<TD class='table_line' align='left' nowrap>$revenda_nome&nbsp;</TD>\n";
			}

			if (!in_array($login_fabrica, array(169,170))) {
				echo "<TD class='table_line' align='center' nowrap>$mao_de_obra $mao_de_obra_reduzida</TD>\n";
			}

			if(in_array($login_fabrica, array(52, 72))){
				echo "<TD class='table_line' align='center' nowrap>$qtde_km&nbsp;</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$valor_km&nbsp;</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada&nbsp;</TD>\n";
			}

			if (in_array($login_fabrica, array(50,85))){
				echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada&nbsp;</TD>\n";
				if ($login_fabrica == 85){
					echo "<TD class='table_line' align='center' nowrap>".number_format($pedagio,2,',','.')."</TD>\n";
                    $sqlValorBonificacao = "
                            SELECT  tbl_extrato_lancamento.valor
                            FROM    tbl_extrato_lancamento
                            WHERE   fabrica = $login_fabrica
                            AND     os = $os
                            AND     extrato = $extrato
                            AND     tbl_extrato_lancamento.descricao ILIKE '%diferenciado'
                            LIMIT   1
                        ";
                    $resValorBonificacao = pg_query($con,$sqlValorBonificacao);
                    $valores_adicionais = pg_fetch_result($resValorBonificacao,0,valor);

                    $total_bonificacao += $valores_adicionais;
					echo "<TD class='table_line' align='center' nowrap>".number_format($valores_adicionais,2,',','.')."</TD>\n";

                    $sqlValorAvulso = "
                            SELECT  SUM(tbl_extrato_lancamento.valor) as valor_total_avulso
                            FROM    tbl_extrato_lancamento
                            WHERE   fabrica = $login_fabrica
                            AND     os = $os
                            AND     (tbl_extrato_lancamento.descricao NOT ILIKE '%diferenciado' OR tbl_extrato_lancamento.descricao IS NULL)
                            LIMIT 1
                        ";
                    $resValorAvulso = pg_query($con,$sqlValorAvulso);
                    $valores_avulsos = pg_fetch_result($resValorAvulso,0,'valor_total_avulso');

                    $total_avulso += $valores_avulsos;
					echo "<TD class='table_line' align='center' nowrap>".number_format($valores_avulsos,2,',','.')."</TD>\n";

				}
			}elseif(in_array($login_fabrica, array(15,74))){
				echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada</TD>\n";
				if($login_fabrica == 74){
					echo "<TD class='table_line' align='center' nowrap>$descricao_cancelada</TD>\n";
					echo "<TD class='table_line' align='center' nowrap>$justificativa_canceladas</TD>\n";
				}
			}elseif($login_fabrica == 90){
 				echo "<TD class='table_line' align='center' nowrap>".number_format($pecas,2,',','.')."</TD>\n";
                                		echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>".number_format($taxa_visita,2,',','.')."</TD>\n";
			}elseif(!in_array($login_fabrica,array(106, 108, 111, 121, 123, 125)) && !isset($novaTelaOs)){
				$t_pecas = (strlen($t_pecas) == 0) ? 0 : $t_pecas;
				$pecas = ($login_fabrica == 42) ? $t_pecas : $pecas;
				echo "<TD class='table_line' align='center' nowrap>".number_format($pecas,2,',','.')."</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada</TD>\n";
			} elseif ( in_array($login_fabrica, array(125)) ) {
				echo "<TD class='table_line' align='center' nowrap>".number_format($taxa_visita_os,2,',','.')."</TD>\n";
				$ttl_km_mo_taxa = $mao_de_obra + $taxa_visita_os + $valores_adicionais + $qtde_km_calculada;
				$ttl_ttl_km_mo_taxa += $ttl_km_mo_taxa;
				echo "<TD class='table_line' align='center' nowrap>".number_format($ttl_km_mo_taxa,2,',','.')."</TD>\n";
			} else if (in_array($login_fabrica, array(169,170))) { ?>
				<td class="table_line" align="center" nowrap><?= number_format($valor_total_hora_tecnica, 2, ",", "."); ?></td>
			<? } else if (isset($novaTelaOs)) {
		        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
		        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);

		        if (!$nao_calcula_peca) {
					echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
		        }

		        if (!$nao_calcula_km) {
					echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada&nbsp;</TD>\n";
		        }

				if($inf_valores_adicionais || in_array($login_fabrica, array(145))){
					echo "<td class='menu_top' align='center'>".number_format($valores_adicionais,2,',','.')."</td>";
				}

				if(in_array($login_fabrica, array(145))){
					$os_valor_adicional   = str_replace(",", ".", str_replace(".", "", $valores_adicionais));
					$os_qtde_km_calculada = str_replace(",", ".", str_replace(".", "", $qtde_km_calculada));
					$os_mao_de_obra       = str_replace(",", ".", str_replace(".", "", $mao_de_obra));
					$os_pecas             = str_replace(",", ".", str_replace(".", "", $pecas));

					$total_fabrimar = $os_valor_adicional + $os_qtde_km_calculada + $os_mao_de_obra + $os_pecas;

					echo "<TD class='menu_top' align='center'><B>".number_format($total_fabrimar,2,',','.')."</B></TD>\n";
					echo "<TD class='menu_top' align='center'><B>$tipo_atendimento</B></TD>\n";
				}
			}

			if($login_fabrica == 52){
				echo "<TD class='table_line' align='center' nowrap>".number_format($total_os,2,',','.')."</TD>\n";
			}

			echo "</TR>\n";

            if (pg_num_rows($resPecas) > 0) {
?>
                    <tr>
                        <th class='menu_top' colspan="5"><b>Peça</b></th>
                        <th class='menu_top'><b>Qtde</b></th>
                        <th class='menu_top'><b>Valor</b></th>
                    </tr>
<?php
                $pecas = pg_fetch_all($resPecas);
                foreach ($pecas as $c=>$peca) {
?>
                    <tr>
                        <td class='table_line' colspan="5"><?=$peca['peca_nome']?></td>
                        <td class='table_line' align="right"><?=$peca['qtde']?></td>
                        <td class='table_line' align="right"><?=number_format($peca['valor_peca'],2,',','.')?></td>
                    </tr>
<?php
                }
?>
                    <tr>
                        <td colspan="7" class='table_line'>&nbsp;</td>
                    </tr>
<?php
            }

			if($login_fabrica == 52 AND strlen($os)>0){
				$sql_def_const = "SELECT tbl_defeito_constatado.defeito_constatado,
					                            tbl_defeito_constatado.descricao AS desc_def_constatado,
					                            tbl_defeito_constatado_grupo.descricao AS desc_def_constatado_grupo
					                            FROM tbl_os
					                            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
					                            JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo
					                            WHERE tbl_os.os = $os;";
                			$res_def_const = pg_query($con, $sql_def_const);
				if(pg_num_rows($res_def_const)>0){
					for($z = 0; $z < pg_num_rows($res_def_const); $z++){
						$desc_def_constatado            = pg_result($res_def_const,$z,"desc_def_constatado");
                        				$desc_def_constatado_grupo      = pg_result($res_def_const,$z,"desc_def_constatado_grupo");
                        $num_cols = 8;
                        $observacao = (strlen($obs_adicionais ) > 0) ? 'SOBRE KM: '.$obs_adicionais : '&nbsp;';
						echo "<TR>";
						echo "<TD colspan='4' class='table_line'>$observacao</TD>";
						echo "<TD colspan='3' class='table_line' nowrap>GRUPO DEFEITO CONSTATADO: $desc_def_constatado_grupo</TD>";
						echo "<TD colspan='$num_cols' class='table_line' nowrap>DEFEITO CONSTATADO: $desc_def_constatado</TD>";
						echo "</TR>";
					}
				}

                if (strlen($os_reincidente) > 0) {
?>
			                        <tr>
			                            <td colspan="15" style="text-align:center;font-weight:bold;" class='table_line'>DADOS DA OS REINCIDENTE</td>
			                        </tr>

				<?	$sqlReinc = "SELECT  tbl_os.os AS reinc_os,
					                            tbl_os.sua_os AS reinc_sua_os,
					                            tbl_defeito_constatado.descricao AS reinc_def_constatado,
					                            tbl_defeito_constatado_grupo.descricao AS reinc_grupo_def_constatado
					                    FROM tbl_os
					                    JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
					                    JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo
					                    WHERE tbl_os.fabrica = $login_fabrica
					                    AND tbl_os.os = $os_reincidente;";
        					$resReinc = pg_query($con,$sqlReinc);

					$reinc_os = pg_fetch_result($resReinc,0,reinc_os);
					$reinc_sua_os = pg_fetch_result($resReinc,0,reinc_sua_os);
					$reinc_def_constatado = pg_fetch_result($resReinc,0,reinc_def_constatado);
					$reinc_grupo_def_constatado = pg_fetch_result($resReinc,0,reinc_grupo_def_constatado);

?>
					<tr>
						<td colspan="2" class='table_line' style="text-align:right"><?=$reinc_sua_os?></td>
						<td class='table_line'>&nbsp;</td>
						<td colspan="3" class='table_line'>GRUPO DEFEITO: <?=$reinc_grupo_def_constatado?></td>
						<td colspan="9" class='table_line'>DEF. CONSTATADO: <?=$reinc_def_constatado?></td>
					</tr>
					<tr>
						<td colspan="15" class='table_line'>&nbsp;</td>
					</tr>
<?
                }

            }
		}
	}//FIM FOR

	echo "<TR>\n";

	if(in_array($login_fabrica, array(11, 51))) echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";

	if($login_fabrica == 2) echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";

	if($login_fabrica == 52){
		echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
		echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
	}

	# HD 936143
	if($login_fabrica == 80){
		echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
	}

	if (!in_array($login_fabrica, array(145,169,170))) {
		echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
		echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
		echo "<TD class='table_line' align='center'>&nbsp;</TD>\n";
		echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
		if(in_array($login_fabrica, array(52,183))){ //hd_chamado=2598225
			echo "<TD class='table_line' align='center'>&nbsp;</TD>\n";
		}
		if ($login_fabrica == 158) {
			echo "<TD class='table_line' align='center'>&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center'>&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center'>&nbsp;</TD>\n";
		}
		echo "<TD class='menu_top' align='right' nowrap>Subtotal:&nbsp;</TD>\n";
	}

	if ($login_fabrica == 52) {
		echo "<TD class='table_line' align='center' nowrap>".number_format($total_pedagio,2,',','.')."&nbsp;</TD>\n";
	}

	if ($login_fabrica == 158) {
		$total_mao_obra	= str_replace(',','',$t_mao_de_obra);
	}

	$t_mao_de_obra	= str_replace(',','',$t_mao_de_obra);
	$t_mao_de_obra	= number_format($t_mao_de_obra, 2, ',', ' ');
	$t_mao_de_obra	= str_replace(' ','.',$t_mao_de_obra);
	$t_mao_de_obra	= str_replace(' ','',$t_mao_de_obra);

	if (!in_array($login_fabrica, array(145,169,170))) {
		if ($login_fabrica != 125) {
			echo "<TD class='table_line' align='center' nowrap>$t_mao_de_obra &nbsp;</TD>\n";
		}
		if (in_array($login_fabrica, array(125))) {
			$t_taxa_visita_os	= str_replace(',','',$t_taxa_visita_os);
			$t_taxa_visita_os	= number_format($t_taxa_visita_os, 2, ',', ' ');
			$t_taxa_visita_os	= str_replace(' ','.',$t_taxa_visita_os);
			$t_taxa_visita_os	= str_replace(' ','',$t_taxa_visita_os);
			echo "<TD class='table_line' align='center' nowrap>".number_format($total_valores_adicionais,2 , ',', '.')."&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>".number_format($ttl_km,2 , ',', '.')."&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>".number_format($t_mao_de_obra,2 , ',', '.')."&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>".number_format($t_taxa_visita_os,2 , ',', '.')."&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>".number_format($ttl_ttl_km_mo_taxa,2 , ',', '.')."&nbsp;</TD>\n";
		}
	}

	if(in_array($login_fabrica, array(52, 72))){
		echo "<TD class='table_line' align='center' nowrap> $total_qtde_km&nbsp;</TD>\n";
		echo "<TD class='table_line' align='center' nowrap>".number_format($total_valor_km,2 , ',', '.')."&nbsp;</TD>\n";
		echo "<TD class='table_line' align='center' nowrap>".number_format($total_qtde_km_calculada, 2, ',', '.')."&nbsp;</TD>\n";
	}

	if (in_array($login_fabrica, array(50,85))){
		echo "<TD class='table_line' align='center' nowrap>".number_format($total_qtde_km_calculada, 2, ',', '.')."&nbsp;</TD>\n";
		if ($login_fabrica == 85){
			echo "<TD class='table_line' align='center' nowrap>".number_format($total_pedagio,2,',','.')."&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>".number_format($total_bonificacao,2,',','.')."&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>".number_format($total_avulso,2,',','.')."&nbsp;</TD>\n";
		}

	}elseif(in_array($login_fabrica, array(15,74))){
		echo "<TD class='table_line' align='center' nowrap>".number_format($total_pecas,2,',','.')."&nbsp;</TD>\n";
		echo "<TD class='table_line' align='center' nowrap>".number_format($total_qtde_km_calculada,2,',','.')."&nbsp;</TD>\n";
	}elseif($login_fabrica == 90){
		echo "<TD class='table_line' align='center' nowrap>".number_format($total_pecas,2,',','.')."&nbsp;</TD>\n";
		echo "<TD class='table_line' align='center' nowrap>".number_format($total_qtde_km_calculada,2,',','.')."&nbsp;</TD>\n";
		echo "<TD class='table_line' align='center' nowrap>".number_format($total_taxa_visita, 2, ',', '.')."&nbsp;</TD>\n";

	}elseif(!in_array($login_fabrica,array(106,108,111,121,123,125)) && !isset($novaTelaOs)){
		echo "<TD class='table_line' align='center' nowrap>".number_format($total_pecas,2,',','.')."&nbsp;</TD>\n";
		echo "<TD class='table_line' align='center' nowrap>".number_format($total_qtde_km_calculada,2,',','.')."&nbsp;</TD>\n";
	} else if (isset($novaTelaOs) && !in_array($login_fabrica, array(145,169,170))) {
        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);

        if (!$nao_calcula_peca) {
			echo "<TD class='table_line' align='center' nowrap>".number_format($total_pecas,2,',','.')."&nbsp;</TD>\n";
        }

        if (!$nao_calcula_km) {
			echo "<TD class='table_line' align='center' nowrap>".number_format($total_qtde_km_calculada, 2, ',', '.')."&nbsp;</TD>\n";
        }

		if($inf_valores_adicionais || in_array($login_fabrica, array(145))){
			echo "<td class='menu_top' align='center'>".number_format($total_valores_adicionais,2,',','.')."</td>";
		}
	}

	if(in_array($login_fabrica, array(52))){
		echo "<TD class='table_line' align='center' nowrap>".number_format($total_geral_os, 2, ',', '.')."&nbsp;</TD>\n";
	}

	echo "</TR>\n";
	echo "</TABLE>\n";
}//FIM ELSE

if ($login_fabrica == 11 AND count($os_array)>0){
	echo "<br><p>(*) Produtos que foram consertadas pela Fábrica terão a mão de obra reduzida.</p>";
}

	if($login_fabrica == 85){
        $sql = "SELECT
                tbl_posto_fabrica.banco,
                tbl_posto_fabrica.agencia,
                tbl_posto_fabrica.conta,
                tbl_posto_fabrica.nomebanco,
                tbl_posto_fabrica.favorecido_conta,
                tbl_posto_fabrica.cpf_conta,
                tbl_posto_fabrica.obs_conta,
                tbl_posto_fabrica.tipo_conta
            FROM tbl_extrato
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            WHERE tbl_extrato.fabrica = {$login_fabrica} AND extrato = {$extrato}";
        $resBanco = pg_query($con,$sql);

        if(pg_num_rows($resBanco) > 0){
            while($objeto_banco = pg_fetch_object($resBanco)){
                ?>
                <p></p>
                <table width='665' align='left' border='0' cellspacing='0' cellpadding='2' border="1" style="position:absolute;">
                    <tr>
                        <td height='20' colspan='3' style="border=1px; text-align:center; border: 1px solid black;"><b>Informações Bancárias</b></td>
                    </tr>
					<tr>
						<td class='menu_top' align='left'> CPF/CNPJ Favorecido: <b><?=$objeto_banco->cpf_conta?></b></td>
						<td class='menu_top' align='left' colspan='2'> Nome Favorecido: <b><?=$objeto_banco->favorecido_conta?></b></td>
					</tr>
					<tr>
						<td class='menu_top' align='left' colspan='3'> Banco: <b><?=$objeto_banco->banco?> - <?=$objeto_banco->nomebanco?></b></td>
					</tr>
					<tr>
						<td class='menu_top' align='left'> Tipo de Conta: <b><?=$objeto_banco->tipo_conta?></b></td>
						<td class='menu_top' align='left'> Agência: <b><?=$objeto_banco->agencia?></b></td>
						<td class='menu_top' align='left'> Conta: <b><?=$objeto_banco->conta?></b></td>
					</tr>
					<tr>
						<td class='menu_top' colspan="3" align='left'> Observações: <b><?=$objeto_banco->obs_conta?></b></td>
					</tr>
                </table>
                <?php
            }
        }
    } //FIM ELSE DO LOGIN_FABRICA 85

    if ($login_fabrica == 148 && $Monta_Tabela_Yanmar !== 1) {
    	$total_entrega_tecnica = 0;
    	GeraTabelaEntregaTecnica($res_yanmar, 1);
    }

    if ($login_fabrica == 85) {
        $cond = "\nAND (tbl_extrato_lancamento.descricao NOT ILIKE '%diferenciado' OR tbl_extrato_lancamento.descricao IS NULL)\n";
    }

	##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
	$sql =	"SELECT
			tbl_lancamento.descricao         ,
			tbl_lancamento.debito_credito,
			tbl_extrato_lancamento.historico ,
			tbl_extrato_lancamento.valor,
			to_char(tbl_extrato_lancamento.data_lancamento,'DD/MM/YYYY') AS data_lancamento,
			tbl_extrato_lancamento.descricao AS descricao_lancamento   ,
			tbl_extrato_lancamento.os AS os_avulso
		FROM tbl_extrato_lancamento
        JOIN tbl_lancamento USING (lancamento)
		WHERE tbl_extrato_lancamento.extrato = $extrato
		$cond
        AND   tbl_lancamento.fabrica = $login_fabrica";
	$res_avulso = pg_query($con,$sql);

	if (pg_num_rows($res_avulso) > 0) {
		?>
        <p></p>
        <?php
		echo "<table width='665' align='left' border='0' cellspacing='0' cellpadding='1' style='padding-top: 15%;' >\n";
		echo "<tr class='menu_top'>\n";
		echo "<td style='text-align=center;' class='table_line' nowrap colspan='5'><B>LANÇAMENTO DE EXTRATO AVULSO<B></td>\n";
		echo "</tr>\n";
		echo "<tr class='menu_top'>\n";
		if($login_fabrica == 85){
			echo "<td class='menu_top' nowrap nowrap><B>OS</B></td>\n";
			echo "<td class='menu_top' nowrap nowrap><B>DATA LANÇAMENTO</B></td>\n";
		}
		echo "<td class='menu_top' nowrap nowrap><B>DESCRIÇÃO</B></td>\n";
		echo "<td class='menu_top' align='center' nowrap><B>HISTÓRICO</B></td>\n";
		echo "<td class='menu_top' align='center' nowrap><B>VALOR</B></td>\n";
		echo "</tr>\n";
		for ($j = 0 ; $j < pg_num_rows($res_avulso) ; $j++) {
			$debito_credito  = pg_result($res_avulso,$j,debito_credito);
			$os_avulso       = pg_result($res_avulso,$j,os_avulso);
			$data_lancamento = pg_result($res_avulso,$j,data_lancamento);

			if($debito_credito == "C"){
				$total_credito += pg_result($res_avulso,$j,valor);
			}else{
				$total_debito += pg_result($res_avulso,$j,valor);
			}
			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			if($login_fabrica == 85){
				echo "<td class='table_line' width='10%'>$os_avulso</td>";
				echo "<td class='table_line' width='10%'>" . $data_lancamento . "</td>";
		    }

		    $historico = pg_result($res_avulso, $j, historico);

		    if(strlen($historico) == 0 || empty($historico)){
		    	$historico = pg_fetch_result($res_avulso, $j, "descricao_lancamento");
		    }

		    $historico = mb_check_encoding($historico, "UTF-8") ? utf8_decode($historico) : $historico;

			echo "<td class='table_line' width='45%'>".pg_result($res_avulso, $j, descricao). "&nbsp;</td>";
			echo "<td class='table_line' width='45%'>".$historico. "&nbsp;</td>";
			echo "<td class='table_line' width='10%' align='right'> ".number_format(pg_result($res_avulso, $j, valor), 2, ',', '.')."&nbsp;</td>";
			echo "</tr>";
		}
			echo "</table>\n";
	}
	##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####


	if($login_fabrica == 11){
		echo "<BR /><BR /><BR /><BR /><BR /><BR />\n";
		echo "<table class='quebrapagina'><TR><TD>&nbsp;</TD></TR></table>";
	   	//MONTA O CABEÇALHO DEPOIS DA QUEBRA DE PAGINA
		echo "<table width='665' align='left' border='0' cellspacing='0' cellpadding='0'>\n";
		echo "<TR>\n";
		echo "<TD class='menu_top' align='center' colspan='4' height='30'><B>POSTO: ".pg_result ($res,0,codigo_posto)." - ".pg_result ($res,0,nome_posto)."</B></TD>\n";
		echo "<TD class='menu_top' align='center' ><B>Data: ".pg_result ($res,0,data_geracao)."</B></TD>\n";
		echo "<TD class='menu_top' align='center' valign='center' colspan='2'><B>Extrato: $extrato</B></TD>\n";
		echo "</TR>\n";
		echo "</table>";
		//FIM DO CABEÇALHO
		echo "<BR /><BR /><BR />\n";
	} else {
		echo "<BR /><BR /><BR /><BR /><BR />\n";
	}


	##### RESUMO DO EXTRATO - INÍCIO #####
	$sql =	"SELECT tbl_extrato.mao_de_obra ,
					tbl_extrato.pecas ,
					tbl_extrato.avulso,
					tbl_extrato.total,
					tbl_extrato.deslocamento,
					tbl_extrato.valor_adicional
			FROM tbl_extrato
			WHERE tbl_extrato.extrato = $extrato
			AND   tbl_extrato.fabrica = $login_fabrica";
	$res_extrato = pg_exec($con,$sql);


	if (pg_num_rows($res_extrato) > 0 && !in_array($login_fabrica, array(151,169,170))) {
		echo "<br clear='all'>";
		echo "<br>";
		echo "<table width='665' align='left' border='0' cellspacing='0' cellpadding='0'>\n";
		if ($login_fabrica == 125) {
			echo "<tr><td align='left' nowrap><B>Valor Adicional:</B></td>\n";
			echo "<td align='right' nowrap><B>".number_format($total_valores_adicionais, 2, ',', '.')."</B></td></tr>\n";
			echo "<tr><td align='left' nowrap><B>Total KM:</B></td>\n";	
			echo "<td align='right' nowrap><B>".number_format($ttl_km, 2, ',', '.')."</B></td></tr>\n";
		}
		echo "<tr>\n";
		echo "<td align='left' nowrap><B>Total de Mão de Obra:</B></td>\n";
		if ($login_fabrica == 148) {
			echo "<td align='right' nowrap><B>$t_mao_de_obra</B></td>\n";
		}else{
			echo "<td align='right' nowrap><B>".number_format(pg_result($res_extrato, 0, mao_de_obra), 2, ',', '.')."</B></td>\n";
		}
		echo "</tr>\n";

		if($login_fabrica == 52){
			echo "<tr>";
			echo "<td><b>Total de Pedágio:</b></td>";
			echo "<td align='right' nowrap><b>$total_pedagio</b></td>";
			echo "</tr>";
		}

		if ($login_fabrica == 148) {
			echo "<tr>\n";
			echo "<td align='left' nowrap><B>Total de Entrega Técnica:</B></td>\n";
			echo "<td align='right' nowrap><b>".number_format($total_entrega_tecnica, 2, ',', '.')."</b></td>";
			echo "</tr>\n";
		}

		if($login_fabrica == 50){
			$sqlF = "SELECT tbl_familia.codigo_familia,
							tbl_familia.descricao,
							SUM(tbl_os.mao_de_obra) AS total
						FROM tbl_os
						JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.i_fabrica = $login_fabrica
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
						JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica
						WHERE tbl_os_extra.extrato = $extrato
						AND   tbl_os.fabrica = $login_fabrica
						GROUP BY tbl_familia.descricao,
						tbl_familia.codigo_familia
						ORDER BY tbl_familia.descricao";
			$resF = pg_exec($con,$sqlF);
			if(pg_num_rows($resF) > 0){
				echo "<tr><td colspan='2'>&nbsp;</td></tr>";
				for($k = 0; $k < pg_num_rows($resF); $k++){
					$codigo_familia = pg_result($resF,$k,'codigo_familia');
					$descricao_familia = pg_result($resF,$k,'descricao');
					$total = pg_result($resF,$k,'total');

					echo "<tr>\n";
					echo "<td align='left' nowrap><B>$codigo_familia -  $descricao_familia:</B></td>\n";
					echo "<td align='right' nowrap><B>".number_format($total, 2, ',', '.')."</B></td>\n";
					echo "</tr>\n";
				}
				echo "<tr><td colspan='2'>&nbsp;</td></tr>";
			}
		}

		if(in_array($login_fabrica, array(15,74,85))){
			echo "<tr>\n";
			echo "<td align='left' nowrap><b>Total de KM:</b></td>\n";
			echo "<td align='right' nowrap><b>".number_format($total_qtde_km_calculada,2,',','.')."</b></td>\n";
			echo "</tr>\n";

			if($login_fabrica == 85){

				echo "<tr>\n";
					echo "<td align='left' nowrap><b>Total de Pedágio:</b></td>\n";
					echo "<td align='right' nowrap><b>".number_format($total_pedagio,2,',','.')."</b></td>\n";
				echo "</tr>\n";
				echo "<tr>\n";
					echo "<td align='left' nowrap><b>Total de Bonificação:</b></td>\n";
					echo "<td align='right' nowrap><b>".number_format($total_bonificacao,2,',','.')."</b></td>\n";
				echo "</tr>\n";

			}
		}

		if(in_array($login_fabrica, array(50,52,72,120,201))){
			echo "<tr>\n";
			echo "<td align='left' nowrap><B>Total de Deslocamento:</B></td>\n";
			echo "<td align='right' nowrap><B>".number_format(pg_result($res_extrato, 0, deslocamento), 2, ',', '.')."</B></td>\n";
			echo "</tr>\n";
		}

		if(!in_array($login_fabrica,array(106,108,111,121,123,125)) && !isset($novaTelaOs)){
			echo "<tr>\n";
			echo "<td align='left' nowrap><B>Total de Peças:</B></td>\n";
			echo "<td align='right' nowrap><B>".number_format( pg_result($res_extrato, 0, pecas), 2, ',', '.')."</B></td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<td align='left' nowrap><B>Total de KM:</B></td>\n";
			echo "<td align='right' nowrap><B>".number_format($total_qtde_km_calculada, 2, ',', '.')."</B></td>\n";
			echo "</tr>\n";
		}

 		if($login_fabrica == 90){
  			echo "<tr>\n";
            echo "<td align='left' nowrap><b>Total de KM:</b></td>\n";
            echo "<td align='right' nowrap><b>".number_format($total_qtde_km_calculada,2,',','.')."</b></td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
            echo "<td align='left' nowrap><b>Total de Visita:</b></td>\n";
            echo "<td align='right' nowrap><b>".number_format($total_taxa_visita,2,',','.')."</b></td>\n";
            echo "</tr>\n";
        }

		if($login_fabrica == 51){
			echo "<tr>\n";
			echo "<td align='left' nowrap><B>Total de Crédito:</B></td>\n";
			echo "<td align='right' nowrap><B>".number_format($total_credito, 2, ',', '.')."</B></td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<td align='left' nowrap><B>Total de Débito:</B></td>\n";
			echo "<td align='right' nowrap><B>".number_format($total_debito, 2, ',', '.')."</B></td>\n";
			echo "</tr>\n";
		}

		if(!isset($novaTelaOs)){
			if (in_array($login_fabrica, array(125))) {
				echo "<tr>\n";
				echo "<td align='left' nowrap><B>Total Taxa Visita:</B></td>\n";
				echo "<td align='right' nowrap><B>$t_taxa_visita_os</B></td>\n";
				echo "</tr>\n";
			}

			if ($login_fabrica == 85) {

				$sqlTotalAvulso = "
                            SELECT  SUM(tbl_extrato_lancamento.valor) as valor_total_avulso
                            FROM    tbl_extrato_lancamento
                            WHERE   fabrica = $login_fabrica
                            AND     extrato = $extrato
                            AND     (tbl_extrato_lancamento.descricao NOT ILIKE '%diferenciado' OR tbl_extrato_lancamento.descricao IS NULL)
                            LIMIT 1
                        ";
                		
				$resTotalAvulso = pg_query($con,$sqlTotalAvulso);
				$totalAvulso = pg_fetch_result($resTotalAvulso,0,'valor_total_avulso');
				$totalAdicional = pg_fetch_result($res_extrato,0,'valor_adicional');

				echo "<tr>\n";
				echo "<td align='left' nowrap><B>Total Avulsos:</B></td>\n";
				echo "<td align='right' nowrap><B>".number_format($totalAvulso, 2, ',', '.')."</B></td>\n";
				echo "</tr>\n";
				
			} elseif ($login_fabrica != 125) {

				echo "<tr>\n";
				echo "<td align='left' nowrap><B>Total Avulsos:</B></td>\n";
				echo "<td align='right' nowrap><B>".number_format( pg_result($res_extrato, 0, avulso), 2, ',', '.')."</B></td>\n";
				echo "</tr>\n";
			}

			echo "<tr>\n";
			echo "<td align='left' nowrap><B>TOTAL GERAL:</B></td>\n";
			echo "<td align='right' nowrap><B>".number_format(pg_result($res_extrato, 0, total), 2, ',', '.')."</B></td>\n";
			echo "</tr>\n";
		}

		if (isset($novaTelaOs)) {
	        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
	        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);

	        if (!$nao_calcula_peca) {
				echo "<tr>\n";
				echo "<td align='left' nowrap><B>Total de Peças:</B></td>\n";
				echo "<td align='right' nowrap><B>".number_format( pg_result($res_extrato, 0, pecas), 2, ',', '.')."</B></td>\n";
				echo "</tr>\n";
	        }

	        if (!$nao_calcula_km) {
				echo "<tr>\n";
				echo "<td align='left' nowrap><b>Total de KM:</b></td>\n";
				echo "<td align='right' nowrap><b>".number_format($total_qtde_km_calculada,2,',','.')."</b></td>\n";
				echo "</tr>\n";
	        }

			if($inf_valores_adicionais || in_array($login_fabrica, array(145))){
				echo "<tr>\n";
				echo "<td align='left' nowrap><B>Total Adicional:</B></td>\n";
				echo "<td align='right' nowrap><B>".number_format($total_valores_adicionais, 2, ',', '.')."</B></td>\n";
				echo "</tr>\n";
			}

			echo "<tr>\n";
			echo "<td align='left' nowrap><B>Total Avulsos:</B></td>\n";
			echo "<td align='right' nowrap><B>".number_format( pg_result($res_extrato, 0, avulso), 2, ',', '.')."</B></td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<td align='left' nowrap><B>TOTAL GERAL:</B></td>\n";
			echo "<td align='right' nowrap><B>".number_format(pg_result($res_extrato, 0, total), 2, ',', '.')."</B></td>\n";
			echo "</tr>\n";
		}

		echo "</table>\n";
		echo "<br></br>\n";

	}

	if (pg_num_rows($res_extrato) > 0 && in_array($login_fabrica, array(169,170))) {
                echo "<br clear='all'>";
                echo "<br>";
                echo "<table width='665' align='left' border='0' cellspacing='0' cellpadding='0'>\n";
		echo "<tr>\n";
                echo "<td align='left' nowrap><B>TOTAL GERAL:</B></td>\n";
                echo "<td align='right' nowrap><B>".number_format(pg_result($res_extrato, 0, total), 2, ',', '.')."</B></td>\n";
                echo "</tr>\n";
		echo "</table>\n";
                echo "<br></br>\n";
	}

	##### RESUMO DO EXTRATO - FIM #####

	if($login_fabrica == 151){
		$sql = "SELECT * FROM ((SELECT 0 AS ordem, 'CONSUMIDOR' AS revenda_nome,
			(SELECT SUM(tbl_os.mao_de_obra) FROM tbl_os JOIN tbl_os_extra USING(os) WHERE extrato = $extrato AND consumidor_revenda = 'C') AS mobra,
			SUM(COALESCE(tbl_os_item.custo_peca,tbl_os_item.preco) * tbl_os_item.qtde) AS pecas,
			count(distinct tbl_os.os) AS qtde_os,
			sum(tbl_os_item.qtde) AS qtde_pecas
			FROM tbl_os
			JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
			LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			WHERE tbl_os_extra.extrato = $extrato
			AND tbl_os.consumidor_revenda = 'C')
			UNION
			(SELECT 1 AS ordem,upper(tbl_os.revenda_nome) AS revenda_nome,
				(SELECT SUM(OS.mao_de_obra) FROM tbl_os OS JOIN tbl_os_extra USING(os) WHERE extrato = $extrato AND consumidor_revenda = 'R' AND OS.revenda_nome = tbl_os.revenda_nome) AS mobra,
				SUM(COALESCE(tbl_os_item.custo_peca,tbl_os_item.preco) * tbl_os_item.qtde) AS pecas,
				count(distinct tbl_os.os) AS qtde_os,
				sum(tbl_os_item.qtde) AS qtde_pecas
				FROM tbl_os
				JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
				LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
				LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE tbl_os_extra.extrato = $extrato
				AND tbl_os.consumidor_revenda = 'R'
				GROUP BY tbl_os.revenda_nome
				ORDER BY qtde_os DESC
				LIMIT 5)) AS x ORDER BY ordem ,qtde_os DESC";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
		?>
			<table width='800' class='tabela' align='left' border='0' cellspacing='0' cellpadding='0'>
				<tr style='font-size:14px;'>
					<th nowrap>C/R</th>
					<th nowrap>QUANT. DE OS</th>
					<th nowrap>MÃO DE OBRA</th>
					<th nowrap>QUANT. DE PEÇAS</th>
					<th nowrap>VALOR DAS PEÇAS</th>
				<tr>
<?php
			$t_pecas = 0;
			for($i = 0; $i < pg_num_rows($res); $i++){
				$cr         = pg_fetch_result($res,$i,'revenda_nome');
				$mobra      = pg_fetch_result($res,$i,'mobra');
				$pecas      = pg_fetch_result($res,$i,'pecas');
				$qtde_os    = pg_fetch_result($res,$i,'qtde_os');
				$qtde_pecas = pg_fetch_result($res,$i,'qtde_pecas');
				$qtde_pecas = (strlen($qtde_pecas) == 0) ? 0 : $qtde_pecas;

				$t_qtde_os += $qtde_os;
				$t_qtde_pecas += $qtde_pecas;
				$t_mobra += $mobra;
				$t_pecas += $pecas;
				$revendas[] = "'{$cr}'";
				$mobra = number_format($mobra,2,',','.');
				$pecas = number_format($pecas,2,',','.');

				echo "<tr>
					<td align='left'>{$cr}</td>
					<td align='center'>{$qtde_os}</td>
					<td align='center'>R$ {$mobra}</td>
					<td align='center'>{$qtde_pecas}</td>
					<td align='center'>R$ {$pecas}</td>
				      </tr>";
			}

			$sql = "SELECT 	SUM(COALESCE(tbl_os_item.custo_peca,tbl_os_item.preco) * tbl_os_item.qtde) AS pecas,
					(SELECT SUM(tbl_os.mao_de_obra) FROM tbl_os JOIN tbl_os_extra USING(os) WHERE extrato = $extrato AND consumidor_revenda = 'R' AND tbl_os.revenda_nome NOT IN(".implode(",",$revendas).")) AS mobra,
					count(distinct tbl_os.os) AS qtde_os,
					sum(tbl_os_item.qtde) AS qtde_pecas
				FROM tbl_os
				JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
				LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
				LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE tbl_os_extra.extrato = $extrato
				AND tbl_os.consumidor_revenda = 'R'
				AND tbl_os.revenda_nome NOT IN(".implode(",",$revendas).")";
			$res = pg_query($con,$sql);
#echo nl2br($sql);
			if(pg_num_rows($res) > 0){

				$mobra      = pg_fetch_result($res,0,'mobra');
				$pecas      = pg_fetch_result($res,0,'pecas');
				$qtde_os    = pg_fetch_result($res,0,'qtde_os');
				$qtde_pecas = pg_fetch_result($res,0,'qtde_pecas');
				$qtde_pecas = (strlen($qtde_pecas) == 0) ? 0 : $qtde_pecas;

				$t_qtde_os += $qtde_os;
				$t_qtde_pecas += $qtde_pecas;
				$t_mobra += $mobra;
				$t_pecas += $pecas;
				$mobra = number_format($mobra,2,',','.');
				$pecas = number_format($pecas,2,',','.');

				echo "<tr>
					<td align='left'>OUTROS</td>
					<td align='center'>{$qtde_os}</td>
					<td align='center'>R$ {$mobra}</td>
					<td align='center'>{$qtde_pecas}</td>
					<td align='center'>R$ {$pecas}</td>
				      </tr>";

			}

			echo "<tr>
				<td align='left'>TOTAL</td>
				<td align='center'>{$t_qtde_os}</td>
				<td align='center'>R$ ".number_format($t_mobra,2,',','.')."</td>
				<td align='center'>{$t_qtde_pecas}</td>
				<td align='center'>R$ ".number_format($t_pecas,2,',','.')."</td>
			      </tr>";

			echo "</table>";


		}
	}



	//SE FOR FÁBRICA 11, ENTAO DEVE IMPRIMIR UM AVISO
	if($login_fabrica==11){
		echo "<BR /><BR /><BR />\n";
		echo "<table width='665' align='left' border='0' cellspacing='0' cellpadding='0'>";
		echo "<TR>";
		echo "<td align='left' ><br><br><B>EMITIR NOTA FISCAL:</B><BR>
				Aulik  Industria e Comercio Ltda.<BR>
				Rua Carlos Alberto Santos, s/nr. - QD. “D” – LT. 20/21 - Miragem<BR>
				Lauro de Freitas / BA.<BR>
				CNPJ: 05.256.426/0001-24 <BR>
				INSCR.EST. : 62.942.325
				<BR><BR>
				<B>ENVIAR PARA:</b><BR>
				Aulik  Industria e Comercio Ltda.<BR>
				Rua Bela Cintra, 986 – 3 andar – Bela Vista<BR>
				São Paulo / SP. – CEP: 01415-000</td>
			</tr>";
		echo "</TABLE>";
	}else{
		echo "<br></br>";
	}

	echo "</TD>\n";
	echo "</TR>\n";
	echo "</TABLE>\n";
?>
<br />
<br />
<br />
<br />
</div>
<?php endforeach; ?>

</body>

<script>
	window.print();
</script>
