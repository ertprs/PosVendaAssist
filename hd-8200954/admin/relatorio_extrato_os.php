<?php
/**
 *
 *  relatorio_extrato_os.php
 *
 *  HD 739823
 *
 *  Cópia de admin/extrato_consulta_os.php sem a opção de imprimir 
 *  e com um link para consultar as OS.
 *
 */

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";

include_once 'autentica_admin.php';

if (strlen($_GET["extrato"]) > 0)  $extrato = trim($_GET["extrato"]);

$layout_menu = "callcenter";
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
</style>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">

<?
/*

				to_char (tbl_extrato_extra.baixado,'DD/MM/YYYY') AS baixado    ,
				tbl_extrato_extra.obs                                          ,

*/
	$sql = "/* Programa: $PHP_SELF ### Fabrica: $login_fabrica ### Admin: $login_admin */
			SELECT      lpad (tbl_os.sua_os,10,'0')                                  AS ordem           ,
						tbl_os.os                                                                       ,
						tbl_os.sua_os                                                                   ,
						to_char (tbl_os.data_digitacao,'DD/MM/YYYY')                 AS data            ,
						to_char (tbl_os.data_abertura ,'DD/MM/YYYY')                 AS abertura        ,
						to_char (tbl_os.data_fechamento,'DD/MM/YYYY')                AS fechamento       ,
						to_char (tbl_os.finalizada    ,'DD/MM/YYYY')                 AS finalizada      ,
						tbl_os.consumidor_revenda                                                       ,
						tbl_os.serie                                                                    ,
						tbl_os.codigo_fabricacao                                                        ,
						tbl_os.consumidor_nome                                                          ,
						tbl_os.consumidor_fone                                                          ,
						tbl_os.revenda_nome                                                             ,
						tbl_os.troca_garantia                                                           ,
						tbl_os.data_fechamento                                                          ,
						(SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os) AS total_pecas  ,
						tbl_os.mao_de_obra                                           AS total_mo        ,
						tbl_os.qtde_km                                               AS qtde_km         ,
						tbl_os.qtde_km_calculada                                     AS qtde_km_calculada,
						tbl_os.cortesia                                                                 ,
						tbl_os.nota_fiscal                                                              ,
						tbl_os.nota_fiscal_saida                                                        ,
						to_char (tbl_os.data_nf    ,'DD/MM/YYYY')                    AS data_nf         ,
						tbl_os.posto                                                                    ,
						tbl_produto.referencia                                                          ,
						tbl_produto.descricao                                                           ,
						tbl_os_extra.extrato                                                            ,
						tbl_os_extra.os_reincidente                                                     ,
						tbl_os.observacao                                                               ,
						tbl_os.motivo_atraso                                                            ,
						tbl_os_extra.motivo_atraso2                                                     ,
						tbl_os.obs_reincidencia                                                         ,
						to_char (tbl_extrato.data_geracao,'DD/MM/YYYY')              AS data_geracao    ,
						tbl_extrato.total                                            AS total           ,
				to_char (tbl_extrato.mao_de_obra,'9,999,990.00') AS t_mao_de_obra,
				to_char (tbl_extrato.pecas,'9,999,990.00') AS t_pecas            ,
				to_char (tbl_extrato.deslocamento,'9,999,990.00') AS t_deslocamento,

						to_char (tbl_os.mao_de_obra,'9,999,990.00') AS mao_de_obra       ,
						to_char (tbl_os_extra.mao_de_obra,'9,999,990.00') AS mo_extra    ,
						to_char (tbl_os.pecas,'9,999,990.00') AS pecas                   ,
						to_char (tbl_os.qtde_km_calculada,'9,999,990.00') AS qtde_km_calculada,
						tbl_extrato.admin                                            AS admin_aprovou   ,
						lpad (tbl_extrato.protocolo::text,5,'0')                     AS protocolo       ,
						tbl_posto.nome                                               AS nome_posto      ,
						tbl_posto_fabrica.codigo_posto                               AS codigo_posto    ,
						tbl_extrato_pagamento.valor_total                                               ,
						tbl_extrato_pagamento.acrescimo                                                 ,
						tbl_extrato_pagamento.desconto                                                  ,
						tbl_extrato_pagamento.valor_liquido                                             ,
						tbl_extrato_pagamento.nf_autorizacao                                            ,
						to_char (tbl_extrato.previsao_pagamento,'DD/MM/YYYY') AS previsao_pagamento ,
						to_char (tbl_extrato.data_recebimento_nf,'DD/MM/YYYY') AS data_recebimento_nf ,
						to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
						to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
						tbl_extrato_pagamento.autorizacao_pagto                                         ,
						tbl_extrato_pagamento.obs                                                       ,
						tbl_extrato_pagamento.extrato_pagamento                                         ,
						(SELECT COUNT(*) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) JOIN tbl_servico_realizado USING (servico_realizado) WHERE tbl_os_produto.os = tbl_os.os AND tbl_os_item.custo_peca = 0 AND tbl_servico_realizado.troca_de_peca IS TRUE) AS peca_sem_preco,
						(SELECT peca_sem_estoque FROM tbl_os_item JOIN tbl_os_produto using(os_produto) WHERE tbl_os_produto.os = tbl_os.os and peca_sem_estoque is true limit 1) AS peca_sem_estoque ,
						$case_log
						tbl_os.data_fechamento - tbl_os.data_abertura  as intervalo                     ,
						(SELECT login FROM tbl_admin WHERE tbl_admin.admin = tbl_os.admin AND tbl_admin.fabrica = $login_fabrica) AS admin,
						tbl_familia.descricao 		as familia_descr,
						tbl_familia.familia	  		as familia_id,
						tbl_familia.codigo_familia 	as familia_cod,
						tbl_posto_fabrica.valor_km
			FROM        tbl_extrato
			LEFT JOIN tbl_extrato_pagamento ON  tbl_extrato_pagamento.extrato  = tbl_extrato.extrato
			LEFT JOIN tbl_os_extra          ON  tbl_os_extra.extrato           = tbl_extrato.extrato
			LEFT JOIN tbl_os                ON  tbl_os.os                      = tbl_os_extra.os
			$join_log
			LEFT JOIN      tbl_produto           ON  tbl_produto.produto            = tbl_os.produto
			JOIN      tbl_posto             ON  tbl_posto.posto                = tbl_extrato.posto
			JOIN      tbl_posto_fabrica     ON  tbl_posto.posto                = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica      = $login_fabrica
			LEFT JOIN tbl_familia			ON  tbl_produto.familia			   = tbl_familia.familia
											AND tbl_familia.fabrica			   = $login_fabrica
			WHERE		tbl_extrato.fabrica = $login_fabrica
			AND         tbl_extrato.extrato = $extrato ";
			if($login_fabrica==45){ //HD 39933
			$sql .= "
				AND    tbl_os.mao_de_obra notnull
				AND    tbl_os.pecas       notnull
				AND    ((SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) IS NULL OR (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) NOT IN (15)) ";
			}

	if($login_fabrica <> 2 && $login_fabrica != 50 ){
		$sql .= "ORDER BY    tbl_os_extra.os_reincidente,lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
						replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
	} else if ( $login_fabrica == 50 ) { // HD 107642 (augusto)
		$sql .= "ORDER BY   tbl_familia.descricao ASC,
							tbl_os_extra.os_reincidente,lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0') ASC,
							replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
	} else {
		$sql .= " ORDER BY replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC ";
	}
$res = pg_exec ($con,$sql);
//if($ip=="201.68.13.36"){ echo $sql; exit; }

if (@pg_numrows($res) == 0) {
	echo "<TABLE width='665' height='50'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
}else{
/*	echo "<TABLE width='666' border='1' cellspacing='0' cellpadding='0'>";
	echo "<TR>";
	echo "<TD>";
*/
	if ($login_fabrica==11){
		$os_array = array();
		$sql =  "SELECT
					intervencao.os
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
	
	if($login_fabrica==52){
		$colspan = 3;
	}else{
		$colspan = 2;
	}

	if($login_fabrica==52  or $login_fabrica==72){
		$colspan2 = 9;
	}else{
		$colspan2 = 7;
	}

	if($login_fabrica==52 or $login_fabrica==72){
		$colspan3 = 6;
	}else{
		$colspan3 = 4;
	}

	echo "<TABLE width='665' border='0' cellspacing='0' cellpadding='1'>";
	echo "<TR>";
	echo "<TD class='menu_top' align='center' colspan='$colspan' rowspan='2'><IMG SRC='/assist/logos/$login_fabrica_logo' border='0'></TD>";
	echo "<TD class='menu_top' align='left' colspan='$colspan2'> Posto: <B>" . pg_result ($res,0,codigo_posto) . " - " . pg_result($res,0,nome_posto) . "</B>&nbsp;</TD>";
	echo "</TR>";

	echo "<TR>";
	echo "<TD class='menu_top' align='left' nowrap> Extrato: <B>$extrato</B> </TD>";
	echo "<TD class='menu_top' align='left' nowrap> Data: <B>" . pg_result ($res,0,data_geracao) . "</B>&nbsp;</TD>";
	echo "<TD class='menu_top' align='left' nowrap> Qtde de OS: <B>". pg_numrows ($res) ."</B>&nbsp;</TD>";
	echo "<TD class='menu_top' align='right' nowrap colspan='$colspan3'> Total: <B>R$ " . pg_result ($res,0,total) . "</B>&nbsp;</TD>";
	echo "</TR>";
	/*echo "</TABLE>";
	echo "<TABLE width='665' border='0' cellspacing='0' cellpadding='1'>\n";*/
	echo "<TR>\n";
	echo "<TD class='menu_top' align='center' width='75'><B>OS</B></TD>\n";
	echo "<TD class='menu_top' align='center' width='75'><B>SÉRIE</B></TD>\n";
	echo "<TD class='menu_top' align='center' width='75'><B>ABERTURA</B></TD>\n";
	if($login_fabrica==11 or $login_fabrica == 51)echo "<TD class='menu_top' align='center' width='75'><B>FECHAMENTO</B></TD>\n";
	if($login_fabrica==2)echo "<TD class='menu_top' align='center' width='75'><B>FINALIZADA</B></TD>\n";
	echo "<TD class='menu_top' align='center'><B>CONSUMIDOR</B></TD>\n";
	echo "<TD class='menu_top' align='center'><B>PRODUTO</B></TD>\n";
	if($login_fabrica==52){
		echo "<TD class='menu_top' align='center'><B>NOTA FISCAL</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>DATA NF</B></TD>\n";
	}
	echo "<TD class='menu_top' align='center'><B>MAO OBRA</B></TD>\n";
	if($login_fabrica==52 or $login_fabrica==72){
		echo "<TD class='menu_top' align='center'><B>QTDE KM</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>VALOR POR KM</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>TOTAL KM</B></TD>\n";
	}
	if ($login_fabrica==50 or $login_fabrica == 85){
		echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
	}elseif($login_fabrica == 74){
		echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
		echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
	}else{
		echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
	}
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		flush();
		$os                 = trim(pg_result ($res,$i,os));
		$data               = trim(pg_result ($res,$i,data));
		$abertura           = trim(pg_result ($res,$i,abertura));
		$fechamento         = trim(pg_result ($res,$i,fechamento));
		$finalizada         = trim(pg_result ($res,$i,finalizada));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$serie              = trim(pg_result ($res,$i,serie));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$produto_nome       = trim(pg_result ($res,$i,descricao));
		$produto_referencia = trim(pg_result ($res,$i,referencia));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$mao_de_obra        = trim(pg_result ($res,$i,mao_de_obra));
		$mo_extra           = trim(pg_result ($res,$i,mo_extra));
		$pecas              = trim(pg_result ($res,$i,pecas));
		$qtde_km            = trim(pg_result ($res,$i,qtde_km));
		$valor_km           = trim(pg_result ($res,$i,valor_km));
		$qtde_km_calculada  = trim(pg_result ($res,$i,qtde_km_calculada));
		$t_mao_de_obra      = trim(pg_result ($res,$i,t_mao_de_obra));
		$t_pecas            = trim(pg_result ($res,$i,total_pecas));
		$t_deslocamento     = trim(pg_result ($res,$i,t_deslocamento));
		$extrato_pagamento  = trim(pg_result ($res,0,extrato_pagamento)) ;
		$valor_total        = trim(pg_result ($res,0,valor_total)) ;
		$acrescimo          = trim(pg_result ($res,0,acrescimo)) ;
		$desconto           = trim(pg_result ($res,0,desconto)) ;
		$valor_liquido      = trim(pg_result ($res,0,valor_liquido)) ;
		$nf_autorizacao     = trim(pg_result ($res,0,nf_autorizacao)) ;
		$data_vencimento    = trim(pg_result ($res,0,data_vencimento)) ;
		$autorizacao_pagto  = trim(pg_result ($res,0,autorizacao_pagto)) ;
		$revenda_nome       = trim(pg_result ($res,$i,revenda_nome)) ;
		$consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda)) ;
		$nota_fiscal        = trim(pg_result ($res,$i,nota_fiscal)) ;
		$data_nf            = trim(pg_result ($res,$i,data_nf)) ;

		$total_qtde_km           = $total_qtde_km + $qtde_km;
		$total_valor_km          = $valor_km;
		$total_qtde_km_calculada = $total_qtde_km_calculada + $qtde_km_calculada;
		$total_pecas += $t_pecas;

		if($login_fabrica==3){//HD 78666
			$mao_de_obra = $mo_extra;
		}
		if($consumidor_revenda=="R" and $login_fabrica==6) { $consumidor_nome=$revenda_nome;}
		#115239 quando for fabrica 51 mostrar o nome da revenda
		if($consumidor_revenda=="R" and $login_fabrica==51) {
			$consumidor_nome=$revenda_nome;
		}
		// FAZ A QUEBRA DE PAGINAS DO RELATORIO, PODE-SE MUDAR O NUMERO DE LINHAS
		if (($i%42==0)&&($i!=0)){

			echo "<TR>\n";
			//echo "<TD class='table_line' nowrap>$sua_os&nbsp;</TD>\n";
			echo '<td class="table_line" nowrap>';
				echo '<a href="os_press.php?os=' . $os . '" target="_blank">';
					echo $sua_os;
				echo '</a>';
			echo '</td>';
			echo "<TD class='table_line' nowrap>$serie&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center'>$abertura&nbsp;</TD>\n";
			if($login_fabrica==11 or $login_fabrica == 51)echo "<TD class='table_line' align='center'>$fechamento</TD>\n";
			if($login_fabrica==2)echo "<TD class='table_line' align='center'>$finalizada</TD>\n";
			echo "<TD class='table_line' nowrap>".substr($consumidor_nome,0,18)."&nbsp;</TD>\n";
			if($login_fabrica==2){
			echo "<TD class='table_line' nowrap>$produto_referencia&nbsp;</TD>\n";
			}else{
			echo "<TD class='table_line' nowrap>".$produto_referencia	." - ".substr($produto_nome,0,17)."&nbsp;</TD>\n";
			}
			if($login_fabrica==52){
				echo "<TD class='table_line' align='center' nowrap>$nota_fiscal</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$data_nf</TD>\n";
			}
			echo "<TD class='table_line' align='center' nowrap>$mao_de_obra</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
			echo "</TR>\n";

			if($login_fabrica==11 or $login_fabrica == 51){
				$col = "5";
			}else if($login_fabrica==52){
				$col = "7";
			}else{
				$col = "4";
			}

		   //MONTA O CABEÇALHO DEPOIS DA QUEBRA DE PAGINA
 		    echo "<TR class='quebrapagina'>\n";
			echo "<TD class='menu_top' align='center' colspan='$col' height='30'><B>POSTO: " . pg_result ($res,0,codigo_posto) . " - " . pg_result ($res,0,nome_posto) . "</B></TD>\n";
			echo "<TD class='menu_top' align='center' ><B>Data:" . pg_result ($res,0,data_geracao) . "</B></TD>\n";
			echo "<TD class='menu_top' align='center' valign='center' colspan='2'><B>Extrato:$extrato</B></TD>\n";
			echo "</TR>\n";
			//FIM DO CABEÇALHO

			//INICIO
			echo "<TR>\n";
			echo "<TD class='menu_top' align='center' width='75'><B>OS</B></TD>\n";
			echo "<TD class='menu_top' align='center' width='75'><B>SÉRIE</B></TD>\n";
			echo "<TD class='menu_top' align='center' width='75'><B>ABERTURA</B></TD>\n";
			if($login_fabrica==11 or $login_fabrica == 51) echo "<TD class='menu_top' align='center' width='75'><B>FECHAMENTO</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>CONSUMIDOR</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>PRODUTO</B></TD>\n";
			if($login_fabrica==52 or $login_fabrica==72){
				echo "<TD class='menu_top' align='center'><B>NOTA FISCAL</B></TD>\n";
				echo "<TD class='menu_top' align='center'><B>DATA NF</B></TD>\n";
			}
			echo "<TD class='menu_top' align='center'><B>MAO OBRA</B></TD>\n";
			if($login_fabrica==52 OR $login_fabrica==72){
				echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
			//	echo "<TD class='menu_top' align='center'><B>DATA NF</B></TD>\n";
			}

			if ($login_fabrica==50 or $login_fabrica == 85){
				echo "<TD class='menu_top' align='center'><B>VALOR KM</B></TD>\n";
			}else{
				echo "<TD class='menu_top' align='center'><B>VALOR PEÇAS</B></TD>\n";
			}
			echo "</TR>\n";

			if($login_fabrica == 52 AND strlen($os)>0){
				$sql_peca = "SELECT tbl_peca.descricao              AS peca_descricao   ,
									tbl_peca.referencia             AS peca_referencia  ,
									tbl_servico_realizado.descricao AS servico_descricao
							FROM tbl_os
							JOIN tbl_os_produto USING(os)
							JOIN tbl_os_item    USING(os_produto)
							JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
							JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
							WHERE tbl_os.fabrica = $login_fabrica
							AND   tbl_os.os      = $os";
				$res_peca = pg_query($con,$sql_peca);
				if(pg_numrows($res_peca)>0){
					for($z=0; $z<pg_numrows($res_peca); $z++){
						$peca_descricao    = pg_result($res_peca,$z,peca_descricao);
						$peca_referencia   = pg_result($res_peca,$z,peca_referencia);
						$servico_descricao = pg_result($res_peca,$z,servico_descricao);

						echo "<TR>";
							echo "<TD colspan='4' class='table_line'>&nbsp;</TD>";
							echo "<TD colspan='4' class='table_line' nowrap>$peca_referencia - $peca_descricao</TD>";
							echo "<TD colspan='4' class='table_line' nowrap>$servico_descricao</TD>";
						echo "</TR>";
					}
				}
			}
		}else{
			echo "<TR>\n";
			//echo "<TD class='table_line' nowrap>$sua_os&nbsp;</TD>\n";
			echo '<td class="table_line" nowrap>';
				echo '<a href="os_press.php?os=' . $os . '" target="_blank">';
					echo $sua_os;
				echo '</a>';
			echo '</td>';
			echo "<TD class='table_line' nowrap>$serie&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center'>$abertura&nbsp;</TD>\n";
			if($login_fabrica==11 or $login_fabrica == 51)echo "<TD class='table_line' align='center'>$fechamento</TD>\n";
			if($login_fabrica==2)echo "<TD class='table_line' align='center'>$finalizada</TD>\n";
			echo "<TD class='table_line' nowrap>".substr($consumidor_nome,0,18)." &nbsp;</TD>\n";
			if($login_fabrica==2){
			echo "<TD class='table_line' nowrap>$produto_referencia&nbsp;</TD>\n";
			}else{
			echo "<TD class='table_line' nowrap>".$produto_referencia	." - ".substr($produto_nome,0,17)."&nbsp;</TD>\n";
			}
			if($login_fabrica==52){
				echo "<TD class='table_line' align='center' nowrap>$nota_fiscal &nbsp;</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$data_nf &nbsp;</TD>\n";
			}
			$mao_de_obra_reduzida="";
			if ($login_fabrica==11 AND count($os_array)>0){
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

			echo "<TD class='table_line' align='center' nowrap>$mao_de_obra $mao_de_obra_reduzida</TD>\n";
			if ($login_fabrica==52 or $login_fabrica==72){
				echo "<TD class='table_line' align='center' nowrap>$qtde_km &nbsp;</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$valor_km &nbsp;</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada &nbsp;</TD>\n";
			}
			if ($login_fabrica==50 or $login_fabrica == 85){
				echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada &nbsp;</TD>\n";
			}elseif($login_fabrica == 74){
				echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
				echo "<TD class='table_line' align='center' nowrap>$qtde_km_calculada &nbsp;</TD>\n";
			}else{
				echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
			}
			echo "</TR>\n";

			if($login_fabrica == 52 AND strlen($os)>0){
				$sql_peca = "SELECT tbl_peca.descricao              AS peca_descricao   ,
									tbl_peca.referencia             AS peca_referencia  ,
									tbl_servico_realizado.descricao AS servico_descricao
							FROM tbl_os
							JOIN tbl_os_produto USING(os)
							JOIN tbl_os_item    USING(os_produto)
							JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
							JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
							WHERE tbl_os.fabrica = $login_fabrica
							AND   tbl_os.os      = $os";
				$res_peca = pg_query($con,$sql_peca);
				if(pg_numrows($res_peca)>0){
					for($z=0; $z<pg_numrows($res_peca); $z++){
						$peca_descricao    = pg_result($res_peca,$z,peca_descricao);
						$peca_referencia   = pg_result($res_peca,$z,peca_referencia);
						$servico_descricao = pg_result($res_peca,$z,servico_descricao);

						echo "<TR>";
							echo "<TD colspan='4' class='table_line'>&nbsp;</TD>";
							echo "<TD colspan='4' class='table_line' nowrap>$peca_referencia - $peca_descricao</TD>";
							echo "<TD colspan='4' class='table_line' nowrap>$servico_descricao</TD>";
						echo "</TR>";
					}
				}
			}
		}
	}//FIM FOR

	
	echo "<TR >\n";
	if($login_fabrica==11 or $login_fabrica == 51)echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
	if($login_fabrica==2)echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
	if($login_fabrica==52){
		echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
		echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
	}
	echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
	echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
	echo "<TD class='table_line' align='center'>&nbsp;</TD>\n";
	echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
	echo "<TD class='menu_top' align='right' nowrap>Subtotal:&nbsp;</TD>\n";
	echo "<TD class='table_line' align='center' nowrap>$t_mao_de_obra&nbsp;</TD>\n";
	if ($login_fabrica==52 or $login_fabrica==72){
		echo "<TD class='table_line' align='center' nowrap> $total_qtde_km &nbsp;</TD>\n";
		echo "<TD class='table_line' align='center' nowrap> $total_valor_km &nbsp;</TD>\n";
	}
	if ($login_fabrica==50 or $login_fabrica==52 or $login_fabrica==72){
		echo "<TD class='table_line' align='center' nowrap> $t_deslocamento&nbsp;</TD>\n";
	}else{
		if($login_fabrica==85){
			echo "<TD class='table_line' align='center' nowrap>$total_qtde_km_calculada &nbsp;</TD>\n";
		}elseif($login_fabrica==74){
			echo "<TD class='table_line' align='center' nowrap>$total_pecas &nbsp;</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>$total_qtde_km_calculada &nbsp;</TD>\n";
		}else{
			echo "<TD class='table_line' align='center' nowrap>$t_pecas&nbsp;</TD>\n";
		}
	}
	if ($login_fabrica==52 or $login_fabrica==72){
		echo "<TD class='table_line' align='center' nowrap>$t_pecas&nbsp;</TD>\n";
	}

	echo "</TR>\n";
	echo "</TABLE>\n";
//	echo "</TD>";
//	echo "</TR>";
}//FIM ELSE

if ($login_fabrica==11 AND count($os_array)>0){
	echo "<br><p>(*) Produtos que foram consertadas pela Fábrica terão a mão de obra reduzida.</p>";
}

	##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
	$sql =	"SELECT tbl_lancamento.descricao         ,
					tbl_lancamento.debito_credito,
					tbl_extrato_lancamento.historico ,
					tbl_extrato_lancamento.valor
			FROM tbl_extrato_lancamento
			JOIN tbl_lancamento USING (lancamento)
			WHERE tbl_extrato_lancamento.extrato = $extrato
			AND   tbl_lancamento.fabrica = $login_fabrica";
	$res_avulso = pg_exec($con,$sql);

	if (pg_numrows($res_avulso) > 0) {
		echo "<br></br>";
		echo "<table width='665' align='left' border='0' cellspacing='0' cellpadding='1'>\n";
		echo "<tr class='menu_top'>\n";
		echo "<td class='table_line' nowrap colspan='3'><B>LANÇAMENTO DE EXTRATO AVULSO<B></td>\n";
		echo "</tr>\n";
		echo "<tr class='menu_top'>\n";
		echo "<td class='menu_top' nowrap nowrap><B>DESCRIÇÃO</B></td>\n";
		echo "<td class='menu_top' align='center' nowrap><B>HISTÓRICO</B></td>\n";
		echo "<td class='menu_top' align='center' nowrap><B>VALOR</B></td>\n";
		echo "</tr>\n";
		for ($j = 0 ; $j < pg_numrows($res_avulso) ; $j++) {
			$debito_credito = pg_result($res_avulso,$j,debito_credito);
			if($debito_credito == "C"){
				$total_credito += pg_result($res_avulso,$j,valor);
			}else{
				$total_debito += pg_result($res_avulso,$j,valor);
			}
			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			echo "<td class='table_line' width='45%'>" . pg_result($res_avulso, $j, descricao) . "&nbsp;</td>";
			echo "<td class='table_line' width='45%'>" . pg_result($res_avulso, $j, historico) . "&nbsp;</td>";
			echo "<td class='table_line' width='10%' align='right'> " . number_format( pg_result($res_avulso, $j, valor), 2, ',', '.') . "&nbsp;</td>";
			echo "</tr>";
		}
			echo "</table>\n";
	}
	##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####


	if($login_fabrica==11){
		echo "<BR></BR><BR></BR><BR></BR>\n";
		echo "<table class='quebrapagina'><TR><TD>&nbsp;</TD></TR></table>";
	   //MONTA O CABEÇALHO DEPOIS DA QUEBRA DE PAGINA
		echo "<table width='665' align='left' border='0' cellspacing='0' cellpadding='0'>\n";
		echo "<TR>\n";
		echo "<TD class='menu_top' align='center' colspan='4' height='30'><B>POSTO: " . pg_result ($res,0,codigo_posto) . " - " . pg_result ($res,0,nome_posto) . "</B></TD>\n";
		echo "<TD class='menu_top' align='center' ><B>Data:" . pg_result ($res,0,data_geracao) . "</B></TD>\n";
		echo "<TD class='menu_top' align='center' valign='center' colspan='2'><B>Extrato:$extrato</B></TD>\n";
		echo "</TR>\n";
		echo "</table>";
			//FIM DO CABEÇALHO
		echo "<BR><BR><BR>\n";

	}else{
			echo "<BR><BR>\n";
	}

	##### RESUMO DO EXTRATO - INÍCIO #####
	$sql =	"SELECT tbl_extrato.mao_de_obra ,
					tbl_extrato.pecas ,
					tbl_extrato.avulso,
					tbl_extrato.total,
					tbl_extrato.deslocamento
			FROM tbl_extrato
			WHERE tbl_extrato.extrato = $extrato
			AND   tbl_extrato.fabrica = $login_fabrica";
	$res_extrato = pg_exec($con,$sql);


	if (pg_numrows($res_extrato) > 0) {
		echo "<br clear='all'>";
		echo "<br>";
		echo "<table width='665' align='left' border='0' cellspacing='0' cellpadding='0'>\n";
		echo "<tr>\n";
		echo "<td align='left' nowrap><B>Total de Mão de Obra:</B></td>\n";
		echo "<td align='right' nowrap><B>".number_format( pg_result($res_extrato, 0, mao_de_obra), 2, ',', '.')."</B></td>\n";
		echo "</tr>\n";

		if($login_fabrica==85 or $login_fabrica == 74){
			echo "<tr>\n";
			echo "<td align='left' nowrap><b>Total de KM:</b></td>\n";
			echo "<td align='right' nowrap><b>$total_qtde_km_calculada</b></td>\n";
			echo "</tr>\n";
		}

		if ($login_fabrica==50 or $login_fabrica == 52 or $login_fabrica == 72){
			echo "<tr>\n";
			echo "<td align='left' nowrap><B>Total de Deslocamento:</B></td>\n";
			echo "<td align='right' nowrap><B>".
					number_format( pg_result($res_extrato, 0, deslocamento), 2, ',', '.')
				 ."</B></td>\n";
			echo "</tr>\n";
		}
			echo "<tr>\n";
			echo "<td align='left' nowrap><B>Total de Peças:</B></td>\n";
			echo "<td align='right' nowrap><B>".
					number_format( pg_result($res_extrato, 0, pecas), 2, ',', '.')
				 ."</B></td>\n";
			echo "</tr>\n";
		if($login_fabrica == 51){
			echo "<tr>\n";
			echo "<td align='left' nowrap><B>Total de Crédito:</B></td>\n";
			echo "<td align='right' nowrap><B>".
					number_format($total_credito, 2, ',', '.')
				 ."</B></td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<td align='left' nowrap><B>Total de Débito:</B></td>\n";
			echo "<td align='right' nowrap><B>".
					number_format($total_debito, 2, ',', '.')
				 ."</B></td>\n";
			echo "</tr>\n";
		}

		echo "<tr>\n";
		echo "<td align='left' nowrap><B>Total Avulsos:</B></td>\n";
		echo "<td align='right' nowrap><B>".
				number_format( pg_result($res_extrato, 0, avulso), 2, ',', '.')
			 ."</B></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td align='left' nowrap><B>TOTAL GERAL:</B></td>\n";
		echo "<td align='right' nowrap><B>".
				number_format( pg_result($res_extrato, 0, total), 2, ',', '.')
			 ."</B></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<br></br>\n";
	}
	##### RESUMO DO EXTRATO - FIM #####



	//SE FOR FÁBRICA 11, ENTAO DEVE IMPRIMIR UM AVISO
	if($login_fabrica==11){
		echo "<BR></BR><BR>\n";
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

</body>

