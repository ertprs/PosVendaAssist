<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

// Criterio padrão
$criterio = "data_digitacao";

if (strlen($_GET["data_inicial"]) == 0) $erro .= "Favor informar a data inicial para pesquisa<br>";

if (strlen($erro) == 0) {
	$data_inicial = trim($_GET["data_inicial"]);
	$fnc          = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
	if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
	
	if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0) ." 00:00:00";
}

if (strlen($erro) == 0) {
	if (strlen($_GET["data_final"]) == 0) $erro .= "Favor informar a data final para pesquisa<br>";
	
	if (strlen($erro) == 0) {
		$data_final   = trim($_GET["data_final"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
		if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
		
		if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0) ." 23:59:59";
	}
}
	
if(strlen($_GET["linha"]) > 0) $linha = trim($_GET["linha"]);

if(strlen($_GET["estado"]) > 0) $estado = trim($_GET["estado"]);

if (strlen($erro) == 0) $listar = "ok";
	
if (strlen($erro) > 0) {
	$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
	$msg .= $erro;
}

if (strlen($msg) == 0){
/////////////////////////////////////////////////////
// habilitada está área
/////////////////////////////////////////////////////

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
				GROUP BY    tbl_os.fabrica        ,
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
		
		@mkdir ("/tmp/assist",0777);

		$arquivo = "/tmp/assist/field-call-rate-serie=$login_fabrica.html";

		$arquivo_destino = "/www/htdocs/assist/admin/xls/field-call-rate-serie=$login_fabrica.xls";
		@unlink ($arquivo_destino);

		$fp = fopen ($arquivo,"w+");
		
		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>FIELD CALL-RATE - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		
		fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
		fputs ($fp,"<tr>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>REFERÊNCIA PRODUTO</td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>DESCRIÇÃO PRODUTO</td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>REFERÊNCIA PEÇA</td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>DESCRIÇÃO PEÇA</td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>ORDEM SERVIÇO</td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>SÉRIE</td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>PEDIDO</td>");
		fputs ($fp,"</tr>");
		
		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia = trim(pg_result($res,$i,referencia));
			//if (strlen($referencia) == 12) $referencia = TC_MascaraString($referencia,'999.999.999.999');
			//if (strlen($referencia) == 9)  $referencia = TC_MascaraString($referencia,'999.999.999');
			
			$descricao  = trim(pg_result($res,$i,descricao));
			$produto    = trim(pg_result($res,$i,produto));
			if (strlen($linha) > 0) $linha = trim(pg_result($res,$i,linha));
			$ocorrencia = trim(pg_result($res,$i,ocorrencia));
			
			/// SQL RETIRADO PARA MELHORAR PERFORMANCE
			$sql = "SELECT  tbl_produto.referencia AS produto_referencia ,
							tbl_produto.descricao  AS produto_descricao  ,
							tbl_peca.referencia    AS peca_referencia    ,
							tbl_peca.descricao     AS peca_descricao     ,
							tbl_os.sua_os                                ,
							tbl_os.os                                    ,
							tbl_os.serie                                 ,
							tbl_os_item.pedido                           ,
							tbl_linha.nome         AS linha_nome         ,
							tbl_os.consumidor_estado                     ,
							tbl_posto.estado       AS posto_estado
					FROM    tbl_os
					JOIN    tbl_os_produto ON tbl_os.os                 = tbl_os_produto.os
					JOIN    tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN    tbl_peca       ON tbl_os_item.peca          = tbl_peca.peca
					JOIN    tbl_produto    ON tbl_os.produto            = tbl_produto.produto
					JOIN    tbl_linha      ON tbl_linha.linha           = tbl_produto.linha
					JOIN    tbl_posto      ON tbl_os.posto              = tbl_posto.posto
					WHERE   tbl_os.fabrica = $login_fabrica
					AND     tbl_os.data_digitacao BETWEEN '$aux_data_inicial' AND '$aux_data_final'
					ORDER BY tbl_produto.referencia, tbl_os.os ";
			
			/// SQL INCLUÍDO PARA MELHORAR PERFORMANCE
			$sql = "SELECT  z.os                                  ,
							z.sua_os                              ,
							z.serie                               ,
							z.consumidor_estado                   ,
							z.produto_referencia                  ,
							z.produto_descricao                   ,
							z.linha_nome                          ,
							z.pedido                              ,
							z.posto_estado                        ,
							tbl_peca.referencia AS peca_referencia,
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
									y.posto_estado
							FROM (
									SELECT  x.os                                        ,
											x.sua_os                                    ,
											x.serie                                     ,
											x.consumidor_estado                         ,
											tbl_produto.referencia AS produto_referencia,
											tbl_produto.descricao  AS produto_descricao ,
											tbl_produto.linha                           ,
											tbl_os_item.pedido                          ,
											tbl_os_item.peca                            ,
											tbl_posto.estado       AS posto_estado
									FROM (
											SELECT  tbl_os.os               ,
													tbl_os.sua_os           ,
													tbl_os.serie            ,
													tbl_os.produto          ,
													tbl_os.consumidor_estado,
													tbl_os.posto
											FROM    tbl_os
											WHERE   tbl_os.data_digitacao BETWEEN '$aux_data_inicial' AND '$aux_data_final'
											AND     tbl_os.fabrica = $login_fabrica
									) AS x
									JOIN tbl_os_produto      ON tbl_os_produto.os_produto = x.os
									JOIN tbl_produto         ON tbl_produto.produto       = x.produto
									JOIN tbl_os_item         ON tbl_os_item.os_produto    = tbl_os_produto.os_produto
									JOIN tbl_posto_fabrica   ON tbl_posto_fabrica.posto   = x.posto
															AND tbl_posto_fabrica.fabrica = $login_fabrica
									JOIN tbl_posto           ON tbl_posto.posto = x.posto
							) AS y
							JOIN tbl_linha   ON tbl_linha.linha   = y.linha
											AND tbl_linha.fabrica = $login_fabrica
					) AS z
					JOIN tbl_peca    ON tbl_peca.peca    = z.peca
									AND tbl_peca.fabrica = $login_fabrica
					ORDER BY z.produto_referencia, z.os;";
			//if ($ip == "201.0.9.216") echo $sql; exit;
			$res = pg_exec ($con,$sql);
			
			echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo"<tr>";
			echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Gerando os resultados...</font></td>";
			echo "</tr>";
			echo "</table>";

			flush();
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$os = pg_result ($res,$i,sua_os) ;
				if (strlen ($os) == 0) $os = pg_result ($res,$i,os) ;

				$estado = pg_result ($res,$i,consumidor_estado) ;
				if (strlen ($estado) == 0) $estado = pg_result ($res,$i,posto_estado) ;

				fputs ($fp,"<tr>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>" . pg_result ($res,$i,produto_referencia) . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>" . pg_result ($res,$i,produto_descricao) . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>" . pg_result ($res,$i,peca_referencia) . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>" . pg_result ($res,$i,peca_descricao) . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>$os</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>" . pg_result ($res,$i,serie) . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>" . pg_result ($res,$i,pedido) . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>" . pg_result ($res,$i,linha_nome) . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>$estado</td>");
				fputs ($fp,"</tr>");
			}
			
			fputs ($fp,"</table>");
			
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
			fclose ($fp);
			
			echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo"<tr>";
			echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Finalizado PostScript e gerados os resultados - Convertendo XLS</font></td>";
			echo "</tr>";
			echo "</table>";

			flush();

			echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm --outfile $arquivo_destino $arquivo`;

			echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo"<tr>";
			echo "<td align='center'>";
			echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font>";
			echo "<a href='http://www.telecontrol.com.br/assist/admin/xls/field-call-rate-serie=$login_fabrica.xls'>";
			echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo</font>";
			echo "</a>.";
			echo "<br>";
			echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";

			unlink ($arquivo);
		}
	}
}

?>