<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$data_inicial = trim($_GET["data_inicial"]);
$data_final   = trim($_GET["data_final"]);
$linha        = trim($_GET["linha"]);
$estado       = trim($_GET["estado"]);
$pais         = trim($_GET["pais"]);
$criterio     = trim($_GET["criterio"]);

if (strlen($data_inicial) == 0) $erro .= "Favor informar a data inicial para pesquisa<br>";

if (strlen($erro) == 0) {
	$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
	
	if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
	
	if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
}

if (strlen($erro) == 0) {
	if (strlen($data_final) == 0) $erro .= "Favor informar a data final para pesquisa<br>";
	
	if (strlen($erro) == 0) {
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
		
		if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
		
		if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : LINHA DE PRODUTO";

include "cabecalho.php";

?>

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
if (strlen($aux_data_inicial) > 0 AND strlen($aux_data_final) > 0) {

	$sql = "SELECT  vw_quebra_produto.fabrica   ,
					vw_quebra_produto.produto   ,
					vw_quebra_produto.referencia,
					vw_quebra_produto.descricao , ";
	
	if (strlen($linha) > 0)  $sql .= "vw_quebra_produto.linha , ";
	if (strlen($estado) > 0) $sql .= "vw_quebra_produto.estado, ";
	
	$sql .= "	sum(vw_quebra_produto.ocorrencia) AS ocorrencia,
				sum(vw_quebra_produto.soma_mobra) AS soma_mobra,
				sum(vw_quebra_produto.soma_peca)  AS soma_peca ,
				sum(vw_quebra_produto.soma_total) AS soma_total
			FROM (
				SELECT  tbl_os.fabrica        ,
						tbl_produto.produto   ,
						tbl_produto.referencia,
						tbl_produto.descricao , ";
	
	if (strlen($linha) > 0)  $sql .= "tbl_linha.linha, ";
	if (strlen($estado) > 0) $sql .= "tbl_posto.estado, ";
	
	$sql .= "			count(tbl_os.produto)                    AS ocorrencia,
						sum(tbl_os.mao_de_obra)                  AS soma_mobra,
						sum(tbl_os.pecas)                        AS soma_peca ,
						sum(tbl_os.pecas + tbl_os.mao_de_obra)   AS soma_total,
						date_trunc('day', tbl_os.data_digitacao) AS digitada  ,
						date_trunc('day', tbl_os.finalizada)     AS finalizada
				FROM        tbl_os
				JOIN        tbl_posto   ON tbl_posto.posto     = tbl_os.posto
				JOIN        tbl_produto ON tbl_produto.produto = tbl_os.produto
				JOIN        tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
				WHERE       tbl_os.fabrica = $login_fabrica ";
	if (strlen($linha) > 0)  $sql .= "AND tbl_produto.linha = $linha ";
	if($linha == 'LAVADORA LX') $sql .= " tbl_produto.produto in('21645','21644','21643','21642','21639','21638','11745','12003','11746','11917','11916','11991','11914','11915') ";
	$sql .= "			GROUP BY    tbl_os.fabrica        ,
							tbl_produto.produto   ,
							tbl_produto.referencia,
							tbl_produto.descricao , ";
	
	if (strlen($linha) > 0)  $sql .= "tbl_linha.linha, ";
	if (strlen($estado) > 0) $sql .= "tbl_posto.estado, ";
	
	$sql .= "	date_trunc('day', tbl_os.data_digitacao),
				date_trunc('day', tbl_os.finalizada)
				) AS vw_quebra_produto
			WHERE vw_quebra_produto.digitada BETWEEN '$aux_data_inicial' AND '$aux_data_final'
			AND   vw_quebra_produto.fabrica = $login_fabrica ";
	
	if (strlen($linha) > 0)  $sql .= "AND vw_quebra_produto.linha  = '$linha' ";
	if (strlen($estado) > 0) $sql .= "AND vw_quebra_produto.estado = '$estado' ";
	
	$sql .= "GROUP BY   vw_quebra_produto.fabrica   ,
						vw_quebra_produto.produto   ,
						vw_quebra_produto.referencia,
						vw_quebra_produto.descricao ";
	
	if (strlen($linha) > 0)  $sql .= ", vw_quebra_produto.linha ";
	if (strlen($estado) > 0) $sql .= ", vw_quebra_produto.estado ";
	
	$sql .= "ORDER BY sum(vw_quebra_produto.ocorrencia) DESC";





//relatorio acertado para bosch
if($login_fabrica == 20 OR $login_fabrica == 15){
	$cond_1 = "1=1";
	$cond_2 = "1=1";
	$cond_3 = "1=1";
	$cond_4 = "1=1"; // HD 2003 TAKASHI
	$cond_5 = "1=1";
	$cond_6 = "1=1";
	$cond_7 = "1=1";
	if (strlen ($linha)  > 0) $cond_1 = " tbl_produto.linha = $linha ";
	if (strlen ($estado) > 0) $cond_2 = " tbl_posto.estado  = '$estado' ";
	if (strlen ($posto)  > 0) $cond_3 = " tbl_posto.posto   = $posto ";


	//IGOR - Add pois tinha no relatorio_field_call_rate_produto, mas aqui não tinha
	if (strlen ($produto)  > 0) $cond_4 = " tbl_os.produto    = $produto "; // HD 2003 TAKASHI
	if (strlen ($tipo_os)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";
	//IGOR - Add para consultar os paises da america latina.
	if (strlen ($pais)     > 0) $cond_6 = " tbl_posto.pais	  = '$pais' ";

	if($login_fabrica == 15){
		if ($linha == "IMPORTAÇÃO DIRETA WAL-MART" AND $login_fabrica == 15) $cond_1 = " tbl_produto.linha in('398','344','311','403','390','343','329','400','342','317','401','338','399','346','307','393','395','345','310','375','339','396','330','376','392','341','402') ";
		if ($linha == "LAVADORAS LE" AND $login_fabrica == 15) $cond_1 = " tbl_produto.produto in('21641','21640','11753','11750','11690','11905','11906','11907','11908','11909','11910','11543','11524','11525','11819','11818') ";
		if ($linha == "LAVADORAS LS" AND $login_fabrica == 15) $cond_1 = " tbl_produto.produto in('21639','21638','11529','11820','11863','11552','11553','11530','11531','11521','11522','11532','11533','11838','11984','11821','11911','12015','12008','11519','11520','12002','11854','11528','11542','11912','11511','11913','11523','11526','11527','11510') ";
		if ($linha == "LAVADORAS LX" AND $login_fabrica == 15) $cond_1 = " tbl_produto.produto in('21645','21644','21643','21642','21639','21638','11745','12003','11746','11917','11916','11991','11914','11915') ";
	}

	//Para a Bosch tem a tradução do produto
	if($login_fabrica == 20 and $pais !='BR'){
		$produto_descricao   ="tbl_produto_idioma.descricao ";
		$join_produto_idioma =" LEFT JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto and tbl_produto_idioma.idioma = 'ES' ";
	}else{
		$produto_descricao   ="tbl_produto.descricao ";
		$join_produto_idioma =" ";
	}

	$sql = "SELECT tbl_produto.produto        ,
					tbl_produto.ativo         ,
					tbl_produto.referencia    ,
					$produto_descricao        ,
					fcr1.qtde AS ocorrencia   ,
					tbl_produto.familia       ,
					tbl_produto.linha
			FROM tbl_produto
			$join_produto_idioma
			JOIN (SELECT tbl_os.produto, COUNT(*) AS qtde
					FROM tbl_os
					JOIN (SELECT tbl_os_extra.os , (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
							FROM tbl_os_extra
							JOIN tbl_extrato USING (extrato)
							JOIN tbl_extrato_extra  ON tbl_extrato_extra.extrato = tbl_extrato.extrato
							WHERE tbl_extrato.fabrica = $login_fabrica
							AND ";

			if($login_fabrica == 20 and $pais != 'BR'){
				$sql .=	" tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
			}else{
				if($login_fabrica == 20){ 
					$sql .=	" tbl_extrato_extra.exportado BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
				}else{
					$sql .=	" tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
				}
			}
					
					
					

	if ($login_fabrica == 14) $sql .= " AND   tbl_extrato.liberado IS NOT NULL";

	// Alterado por Paulo através do chamado Samel para Field Call Rate países fora do Brasil
	$sql .= " ) fcr ON tbl_os.os = fcr.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
				AND tbl_os.excluida IS NOT TRUE
				AND $cond_2
				AND $cond_3
				AND $cond_4
				AND $cond_5
				AND $cond_6
				GROUP BY tbl_os.produto
		) fcr1 ON tbl_produto.produto = fcr1.produto
		WHERE $cond_1
		ORDER BY fcr1.qtde DESC " ;
		

}



	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		flush();
		
		echo "<br><br>";
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";
		
		flush();
		
		$data = date ("d/m/Y H:i:s");

		echo `rm /tmp/assist/field-call-rate-serie-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/field-call-rate-serie-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>FIELD CALL-RATE - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		
		fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
		fputs ($fp,"<tr>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>REFERÊNCIA PRODUTO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>DESCRIÇÃO PRODUTO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>REFERÊNCIA PEÇA</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>DESCRIÇÃO PEÇA</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>ORDEM SERVIÇO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>SÉRIE</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>PEDIDO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>LINHA</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>DEFEITO CONSTATADO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>ESTADO</b></td>");
		fputs ($fp,"</tr>");
		
		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia = trim(pg_result($res,$i,referencia));
			if (strlen($referencia) == 12) $referencia = MW_MascaraString($referencia,'999.999.999.999');
			if (strlen($referencia) == 9)  $referencia = MW_MascaraString($referencia,'999.999.999');
			
			$descricao  = trim(pg_result($res,$i,descricao));
			$produto    = trim(pg_result($res,$i,produto));
			if (strlen($linha) > 0) $linha = trim(pg_result($res,$i,linha));
			$ocorrencia = trim(pg_result($res,$i,ocorrencia));
			
			/// SQL INCLUÍDO PARA MELHORAR PERFORMANCE
			$sql = "SELECT  z.os                                   ,
							z.sua_os                               ,
							z.serie                                ,
							z.consumidor_estado                    ,
							z.produto_referencia                   ,
							z.produto_descricao                    ,
							z.linha_nome                           ,
							z.pedido                               ,
							z.posto_estado                         ,
							z.defeito_reclamado_descricao          ,
							tbl_peca.referencia AS peca_referencia ,
							tbl_peca.descricao  AS peca_descricao  
					FROM (
							SELECT  y.os                          ,
									y.sua_os                      ,
									y.serie                       ,
									y.consumidor_estado           ,
									y.produto_referencia          ,
									y.produto_descricao           ,
									y.linha                       ,
									tbl_linha.nome AS linha_nome  ,
									y.pedido                      ,
									y.peca                        ,
									y.posto_estado                ,
									y.defeito_reclamado_descricao 
							FROM (
									SELECT  x.os                                                  ,
											x.sua_os                                              ,
											x.serie                                               ,
											x.consumidor_estado                                   ,
											tbl_produto.referencia          AS produto_referencia ,
											tbl_produto.descricao           AS produto_descricao  ,
											tbl_produto.linha                                     ,
											tbl_os_item.pedido                                    ,
											tbl_os_item.peca                                      ,
											tbl_posto.estado                AS posto_estado       ,
											tbl_defeito_reclamado.descricao AS defeito_reclamado_descricao
									FROM (
											SELECT  tbl_os.os               ,
													tbl_os.sua_os           ,
													tbl_os.serie            ,
													tbl_os.produto          ,
													tbl_os.consumidor_estado,
													tbl_os.posto
											FROM    tbl_os
											WHERE   tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final'
											AND     tbl_os.fabrica = $login_fabrica
									) AS x
									JOIN tbl_os_produto      ON tbl_os_produto.os_produto = x.os
									JOIN tbl_produto         ON tbl_produto.produto       = x.produto
									JOIN tbl_os_item         ON tbl_os_item.os_produto    = tbl_os_produto.os_produto
									JOIN tbl_posto_fabrica   ON tbl_posto_fabrica.posto   = x.posto
															AND tbl_posto_fabrica.fabrica = $login_fabrica
									JOIN tbl_posto           ON tbl_posto.posto = x.posto
									JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = x.defeito_reclamado
							) AS y
							JOIN tbl_linha   ON tbl_linha.linha   = y.linha
											AND tbl_linha.fabrica = $login_fabrica
					) AS z
					JOIN tbl_peca    ON tbl_peca.peca    = z.peca
									AND tbl_peca.fabrica = $login_fabrica
					ORDER BY z.produto_referencia, z.os;";

			if (strlen ($marca)  > 0) $cond_7 = "AND tbl_produto.marca = $marca ";

			// SQL RETIRADO PARA MELHORAR PERFORMANCE
			$sql = "SELECT  tbl_produto.referencia          AS produto_referencia           ,
							tbl_produto.descricao            AS produto_descricao           ,
							tbl_peca.referencia             AS peca_referencia              ,
							tbl_peca.descricao               AS peca_descricao              ,
							tbl_os.sua_os                                                   ,
							tbl_os.os                                                       ,
							tbl_os.serie                                                    ,
							tbl_os_item.pedido                                              ,
							tbl_linha.nome                  AS linha_nome                   ,
							tbl_os.consumidor_estado                                        ,
							tbl_posto.estado                AS posto_estado                 ,
							tbl_defeito_constatado.descricao AS defeito_constatado_descricao
					FROM    tbl_os
					JOIN    tbl_posto      ON tbl_os.posto              = tbl_posto.posto
					LEFT JOIN    tbl_os_produto ON tbl_os.os                 = tbl_os_produto.os
					LEFT JOIN    tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					LEFT JOIN    tbl_peca       ON tbl_os_item.peca          = tbl_peca.peca
					LEFT JOIN    tbl_produto    ON tbl_os.produto            = tbl_produto.produto
					LEFT JOIN    tbl_linha      ON tbl_linha.linha           = tbl_produto.linha
					LEFT JOIN    tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
					WHERE   tbl_os.fabrica = $login_fabrica
					AND     tbl_os.data_abertura::date BETWEEN '$aux_data_inicial' AND '$aux_data_final'
					$cond_7
					ORDER BY tbl_produto.referencia, tbl_os.os ";


			if($login_fabrica == 20){
				$join_produto_idioma            =" ";
				$join_peca_idioma               =" ";
				$join_defeito_constadado_idioma =" ";

				if  ($login_fabrica == 20 and $pais != 'BR'){			
					$produto_descricao            ="tbl_produto_idioma.descricao    AS produto_descricao ";
					$peca_descricao               ="tbl_peca_idioma.descricao       AS peca_descricao ";
					$defeito_constatado_descricao ="tbl_defeito_constatado_idioma.descricao AS defeito_constatado_descricao ";
					$join_produto_idioma            =" LEFT JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto AND tbl_produto_idioma.idioma='ES' ";
					$join_peca_idioma               =" LEFT JOIN tbl_peca_idioma    ON tbl_peca_idioma.peca = tbl_peca.peca AND tbl_peca_idioma.idioma='ES' ";
					$join_defeito_constatado_idioma =" LEFT JOIN tbl_defeito_constatado_idioma ON tbl_defeito_constatado_idioma.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado_idioma.idioma = 'ES' ";
				}else{
					$produto_descricao            ="tbl_produto.descricao            AS produto_descricao";
					$peca_descricao               ="tbl_peca.descricao               AS peca_descricao";
					$defeito_constatado_descricao ="tbl_defeito_constatado.descricao AS defeito_constatado_descricao";
				}
				
				// SQL RETIRADO PARA MELHORAR PERFORMANCE
				$sql = "SELECT  tbl_produto.referencia          AS produto_referencia           ,
								$produto_descricao                                              ,
								tbl_peca.referencia             AS peca_referencia              ,
								$peca_descricao                                                 ,
								tbl_os.sua_os                                                   ,
								tbl_os.os                                                       ,
								tbl_os.serie                                                    ,
								tbl_os_item.pedido                                              ,
								tbl_linha.nome                  AS linha_nome                   ,
								tbl_os.consumidor_estado                                        ,
								tbl_posto.estado                AS posto_estado                 ,
								$defeito_constatado_descricao                                   
						FROM    tbl_os
						JOIN    tbl_posto      ON tbl_os.posto              = tbl_posto.posto
						LEFT JOIN    tbl_os_produto ON tbl_os.os                 = tbl_os_produto.os
						LEFT JOIN    tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						LEFT JOIN    tbl_peca       ON tbl_os_item.peca          = tbl_peca.peca
						$join_peca_idioma
						LEFT JOIN    tbl_produto    ON tbl_os.produto            = tbl_produto.produto
						$join_produto_idioma
						JOIN tbl_os_extra                  ON tbl_os_extra.os      = tbl_os.os
						JOIN tbl_extrato                   ON tbl_os_extra.extrato = tbl_extrato.extrato
						LEFT JOIN    tbl_linha      ON tbl_linha.linha           = tbl_produto.linha
						LEFT JOIN    tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
						$join_defeito_constatado_idioma
						WHERE  tbl_os.fabrica = $login_fabrica
								AND tbl_extrato.data_geracao::date BETWEEN '$aux_data_inicial' AND '$aux_data_final'
								AND tbl_posto.pais = '$pais'
								AND tbl_os_extra.extrato is not null
								AND tbl_os.excluida IS NOT TRUE




						ORDER BY tbl_produto.referencia, tbl_os.os ";
			}

			if($login_fabrica == 15){//chamado 1333
				$sql = "SELECT  tbl_os.os                                               ,
						tbl_produto.referencia          AS produto_referencia           ,
						tbl_produto.descricao           AS produto_descricao            ,
						tbl_peca.referencia             AS peca_referencia              ,
						tbl_peca.descricao              AS peca_descricao               ,
						tbl_os.sua_os                                                   ,
						tbl_os.serie                                                    ,
						tbl_os_item.pedido                                              ,
						tbl_linha.nome                  AS linha_nome                   ,
						tbl_os.consumidor_estado                                        ,
						tbl_posto.estado                AS posto_estado                 ,
						tbl_defeito_constatado.descricao AS defeito_constatado_descricao 
				FROM    tbl_os
				JOIN    tbl_posto      ON tbl_os.posto              = tbl_posto.posto
				JOIN    tbl_os_extra   ON tbl_os_extra.os           = tbl_os.os
				JOIN    tbl_extrato    ON tbl_extrato.extrato       = tbl_os_extra.extrato
				LEFT JOIN    tbl_os_status  ON tbl_os_status.os          = tbl_os.os
				LEFT JOIN    tbl_os_produto ON tbl_os.os                 = tbl_os_produto.os
				LEFT JOIN    tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				LEFT JOIN    tbl_peca       ON tbl_os_item.peca          = tbl_peca.peca
				LEFT JOIN    tbl_produto    ON tbl_os.produto            = tbl_produto.produto
				LEFT JOIN    tbl_linha      ON tbl_linha.linha           = tbl_produto.linha
				LEFT JOIN    tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
				WHERE   tbl_extrato.fabrica = $login_fabrica
				AND     tbl_os.fabrica = $login_fabrica
				AND     tbl_os_extra.extrato is not null
				AND     $cond_1
				AND     tbl_extrato.data_geracao::date BETWEEN '$aux_data_inicial' AND '$aux_data_final'
				ORDER BY tbl_produto.referencia, tbl_os.os ;";
			}

			//echo $sql; exit;
			$res = pg_exec ($con,$sql);


			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$os = pg_result ($res,$i,sua_os) ;
				if (strlen ($os) == 0) $os = pg_result ($res,$i,os) ;

				$estado = pg_result ($res,$i,consumidor_estado) ;
				if (strlen ($estado) == 0) $estado = pg_result ($res,$i,posto_estado) ;

				fputs ($fp,"<tr>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;" . pg_result ($res,$i,produto_referencia) . "&nbsp;</td>");
				fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>" . pg_result ($res,$i,produto_descricao) . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;" . pg_result ($res,$i,peca_referencia) . "&nbsp;</td>");
				fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>" . pg_result ($res,$i,peca_descricao) . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;".$os."&nbsp;</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;" . pg_result ($res,$i,serie) . "&nbsp;</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;" . pg_result ($res,$i,pedido) . "&nbsp;</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>" . pg_result ($res,$i,linha_nome) . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>" . pg_result ($res,$i,defeito_constatado_descricao) . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>$estado</td>");
				fputs ($fp,"</tr>");
			}
			
			fputs ($fp,"</table>");
			
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
			fclose ($fp);
		}
	}

	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/field-call-rate-serie-$login_fabrica.$data.xls /tmp/assist/field-call-rate-serie-$login_fabrica.html`;
	
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/field-call-rate-serie-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";
}

?>

<p>

<? include "rodape.php" ?>
