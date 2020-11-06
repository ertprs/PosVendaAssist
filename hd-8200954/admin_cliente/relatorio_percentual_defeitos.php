<?php

include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once '../funcoes.php';

$layout_menu = "gerencia";
$title 		 = "RELATÓRIO DE PERCENTUAL DOS DEFEITOS POR PRODUTOS";

include('carrega_macro_familia.php');

if(isset($_POST["get_produto"])){

	$familia = $_POST["familia"];
	$linha = $_POST["linha"];

	$dados = "<option value=''></option>";

	$sql_produto = "SELECT produto, referencia, descricao FROM tbl_produto WHERE ativo IS TRUE AND fabrica_i = {$login_fabrica} /*AND linha = {$linha}*/ AND familia = {$familia} ORDER BY descricao ASC";
	$res_produto = pg_query($con, $sql_produto);
	if(pg_num_rows($res_produto) > 0){
		for($i = 0; $i < pg_num_rows($res_produto); $i++){

			$produto = pg_fetch_result($res_produto, $i, "produto");
			$referencia = pg_fetch_result($res_produto, $i, "referencia");
			$descricao = pg_fetch_result($res_produto, $i, "descricao");

			$dados .= "<option value='".$produto."'>".$referencia." - ".$descricao."</option>";

		}
	}

	echo $dados;
	exit;

}

if(isset($_POST["get_macro_familia"])){

        $macro_linha = $_POST["macro_linha"];
        $macro_linha = implode(",", $macro_linha);

        $dados = "<option value=''></option>";

        $sql_mf = "SELECT DISTINCT
                                                tbl_linha.linha,
                                                tbl_linha.nome
                                  FROM tbl_linha
                                  JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                  JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
                                  WHERE tbl_linha.fabrica = $login_fabrica
                                  AND tbl_macro_linha_fabrica.macro_linha in ({$macro_linha}) AND tbl_linha.ativo IS TRUE
                                  ORDER BY tbl_linha.nome ;";
        $res_mf = pg_query($con, $sql_mf);
        //echo nl2br($sql_mf);
        if(pg_num_rows($res_mf) > 0){
                for($i = 0; $i < pg_num_rows($res_mf); $i++){

                        $mf_linha = pg_fetch_result($res_mf, $i, "linha");
                        $mf_nome = pg_fetch_result($res_mf, $i, "nome");

                        $dados .= "<option value='".$mf_linha."'>".$mf_nome."</option>";

                }
        }

        echo $dados;
        exit;

}

if(isset($_POST["get_familia"])){

	$linha = $_POST["linha"];

	if (empty($linha)) {
		$linha = '697, 698, 699, 700';
	}else{
		$linha = implode(",", $linha);
	}


	$dados = "<option value=''></option>";

	/*$sql_familia = "SELECT tbl_familia.familia,
							 tbl_familia.codigo_familia,
							 tbl_familia.descricao
						-- FROM tbl_macro_linha_fabrica
						-- 	JOIN tbl_linha ON tbl_macro_linha_fabrica.linha = tbl_linha.linha
						FROM tbl_linha
							JOIN tbl_diagnostico ON tbl_linha.linha = tbl_diagnostico.linha
							JOIN tbl_familia ON tbl_diagnostico.familia = tbl_familia.familia
						WHERE tbl_diagnostico.fabrica = 117
							AND tbl_diagnostico.defeito_constatado is NULL
							AND tbl_diagnostico.defeito_reclamado is NULL
							AND tbl_diagnostico.solucao is NULL
							AND tbl_diagnostico.linha in ({$linha})
							-- AND tbl_macro_linha_fabrica.macro_linha in ({$linha})
						GROUP BY tbl_familia.familia,tbl_familia.codigo_familia, tbl_familia.descricao
						ORDER BY descricao ASC;";*/
	$sql_familia = "SELECT
                        familia,
                        descricao,
                        codigo_familia
                    FROM tbl_familia
                    WHERE fabrica = {$login_fabrica}
                        AND linha IN({$linha})";

	$res_familia = pg_query($con, $sql_familia);
	//echo nl2br($sql_familia);
	if(pg_num_rows($res_familia) > 0){
		for($i = 0; $i < pg_num_rows($res_familia); $i++){

			$familia = pg_fetch_result($res_familia, $i, "familia");
			$referencia = pg_fetch_result($res_familia, $i, "codigo_familia");
			$descricao = pg_fetch_result($res_familia, $i, "descricao");

			$dados .= "<option value='".$familia."'>".$descricao."</option>";

		}
	}

	echo $dados;
	exit;

}

//solicitada a troca da data padrão da Elgin
switch ($login_fabrica)
{
	case 8:	 $data_padrao = "data_fechamento";	break;
	case 117:	 $data_padrao = "data_fechamento";	break;
	//case 117:	 $data_padrao = "data_digitacao";	break;
	default: $data_padrao = "data_abertura";	break;
}

/* Gerar Excel */
if(isset($_POST["gerar_excel"])){

	$mes_inicial = $_POST["mes_inicial"];
	$ano_inicial = $_POST["ano_inicial"];

	$mes_final 	= $_POST["mes_final"];
	$ano_final 	= $_POST["ano_final"];

	$linha 		= $_POST["linha"];
	// // 	$linhas = implode(","$linha);
	$familia2 	= $_POST["familia2"];
	$produto 	= $_POST["produto"];
	// echo "<pre>";
	// print_r($_POST);
	// echo "</pre>";

	// 	print_r ($linha);exit;
	// 	$linha = strstr("")
	/* Datas */
	$ultimo_dia_mes = date("t", mktime(0, 0, 0, $mes_final, "01", $ano_final));

	$data_inicial = $ano_inicial."-".$mes_inicial."-01";
	$data_final = $ano_final."-".$mes_final."-".$ultimo_dia_mes;

	if ($login_fabrica == 117) {

		$join_os_extra = " JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.i_fabrica= $login_fabrica ";
		$cond_os_extra = " AND tbl_os_extra.garantia IS NOT FALSE ";

		//$linhas = implode(",",$linha);
		$linhas = $linha;
		$join_linha = (strlen($linhas) > 0) ? " JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica AND tbl_produto.linha IN ($linhas)" : "";

		if(strlen($join_linha) > 0 && strlen($familia2) > 0){
			$join_linha .= " AND tbl_produto.familia = {$familia2} ";
		}else if(strlen($familia2) > 0){
			$join_linha = " JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica  AND tbl_produto.familia = {$familia2} ";
		}

		if(strlen($produto) > 0){
			$cond_produto = " AND tbl_os.produto = {$produto} ";
		}


		$cond_os_finalizada_cortesia = " AND tbl_os.finalizada IS NOT NULL AND tbl_os.cortesia IS FALSE AND tbl_os.status_checkpoint = 9 ";

		$sql = "SELECT tbl_os.os,
                          		to_char(tbl_os.$data_padrao,'MM') as mes,
						to_char(tbl_os.$data_padrao,'YYYY') as ano,
						tbl_os.produto,
						tbl_os.defeito_constatado
			INTO TEMP rpd_0_$login_admin
			FROM tbl_os
			$join_os_extra
			$join_linha
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.excluida IS NOT TRUE
			AND   tbl_os.$data_padrao BETWEEN '$data_inicial' AND '$data_final'
			$cond_os_finalizada_cortesia
			$cond_os_extra
			$cond_produto;

			CREATE INDEX rpd_0_os_$login_admin      ON rpd_0_$login_admin(os);
			CREATE INDEX rpd_0_dc_$login_admin      ON rpd_0_$login_admin(defeito_constatado);
			CREATE INDEX rpd_0_produto_$login_admin ON rpd_0_$login_admin(produto);";
			// echo nl2br($sql); exit;
		$res = pg_exec($con,$sql);

		$sql = "SELECT tbl_produto.produto,
						tbl_produto.descricao     ,
						tbl_produto.nome_comercial,
						tbl_produto.referencia    ,
						COUNT(*) AS conta
			FROM    rpd_0_$login_admin OS
			JOIN    tbl_produto            ON tbl_produto.produto  = OS.produto AND tbl_produto.fabrica_i=$login_fabrica
			GROUP BY tbl_produto.descricao    ,
				 tbl_produto.nome_comercial,
				 tbl_produto.referencia    ,
				 tbl_produto.produto
			ORDER BY tbl_produto.referencia;";
		$res = pg_exec($con,$sql);
		//flush();

		if (pg_num_rows($res) > 0){

			$file     = "xls/relatorio-percentual-defeitos-garantia-{$login_fabrica}-{$data_inicial}-{$data_final}.xls";
			$fileTemp = "/tmp/relatorio-percentual-defeitos-garantia-{$login_fabrica}-{$data_inicial}-{$data_final}.xls";
	        	$fp       = fopen($fileTemp,"w");

	        	$colspan = pg_num_rows($res) + 2;

	        	$head = "<table  border='1'>";
	        	### monta linha de nome dos produtos
	        	$head .=    "<thead>
	        					<tr>
	        						<th bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' colspan='{$colspan}' >
	        							RELATÓRIO DE PERCENTUAL DOS DEFEITOS POR PRODUTOS - $mes_inicial/$ano_inicial à $mes_final/$ano_final
	        						</th>
	        					</tr>
	        					<tr>
	        						<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>#</th>";


			for ($i=0; $i<pg_num_rows($res); $i++){
				$head .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".pg_fetch_result($res,$i,'descricao')." - ".pg_fetch_result($res,$i,'referencia')."</th>";

				// if(pg_result($res,$i,'nome_comercial')) {
				// 	$head .= pg_result($res,$i,'nome_comercial');
    				// }else {
    				//	$head .= pg_result($res,$i,'referencia');
    				// }

				//$head .= "</acronym></b></th>";
				//flush();
				$produto = pg_result($res,$i,'produto');

				# ------------------------------------ 2 ------------------------------------ #

				$sql = "SELECT COUNT(*) AS contaano
					FROM tbl_os
					$join_os_extra
					$join_linha
					WHERE tbl_produto.produto = $produto
					$cond_os_finalizada_cortesia
					$cond_os_extra
					$cond_produto
					AND tbl_os.data_digitacao >= '".date('Y-m-01')." 00:00:00';";
				$res2 = pg_exec($con,$sql);
				$contaano[$i] = pg_result($res2,0,0);
				//flush();
			}

			 $head .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total</th>
		                        </tr>
		                    </thead>
		                    <tbody>";

		       fwrite($fp, $head);

			### MONTA LINHA EM BRANCO, PQ GARANTIA
			$body .= "<tr>
						<td bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>PQ Garantia</td>";
			$total_pq_garantia = 0;
			for ($i=0; $i<pg_num_rows($res); $i++){

				$produto = pg_result($res,$i,'produto');
				//$ultimo_dia_mes_inicial = date("t", mktime(0, 0, 0, $mes_inicial, "01", $ano_inicial));
				$ultimo_dia_mes_final = date("t", mktime(0, 0, 0, $mes_final, "01", $ano_final));

				$sql_pq_garantia = "SELECT SUM(qtde_venda) AS pq_garantia
											FROM tbl_producao
											WHERE produto = $produto
											AND (ano || '-' || mes|| '-01')::date BETWEEN '{$ano_inicial}-{$mes_inicial}-01'::date
											AND '{$ano_final}-{$mes_final}-{$ultimo_dia_mes_final}'::date ;";
				$res_pq_garantia = pg_query($con, $sql_pq_garantia);
				//echo nl2br($sql_pq_garantia);
				if(pg_num_rows($res_pq_garantia) > 0){
					$pq_garantia = pg_fetch_result($res_pq_garantia, 0, "pq_garantia");
				}else{
					$pq_garantia = 0;
				}

				//$pq_qarantia = (strlen($pq_garantia) > 0) ? $pq_garantia : 0;
				$array_pq_garantia[$i] = $pq_garantia;

				$total_pq_garantia += $pq_garantia;
                          			$pq_texto = ($pq_garantia > 0) ? $pq_garantia : "#PQZero";


				$body .= "<td>{$pq_texto}</td>";
			}
			$body .= "<td><b>{$total_pq_garantia}</b></td>";
			$body .= "</tr>";

				### MONTA LINHA COM TOTAL DE OS DO ANO
				$texto_ano = "Atend. Período";
				$body .= "<tr>
	        					<td bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Atend. Período</td>";
	        		$acumula_ano = 0;
				for ($i=0; $i<pg_numrows($res); $i++){
					$body .= "<td>".pg_result($res,$i,'conta')."</td>";
					$acumula_ano += pg_result($res,$i,'conta');
				}
				$body .= "<td><b>{$acumula_ano}</b></td>";
				$body .= "</tr>";

				### MONTA LINHA COM TOTAL DE OS DO MÊS
				$body .= "<tr>
							<td bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Atend. Mês</td>";
				$acumula_mes = 0;
				for ($i=0; $i<count($contaano); $i++){
					$body .= "<td>".$contaano[$i]."</td>";
					$acumula_mes += $contaano[$i];
				}
				$body .= "<td><b>{$acumula_mes}</b></td>";
				$body .= "</tr>";

				### % NO ANO
				$body .= "<tr>
	        					<td bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>% No Período</td>";
				$total_porcen_ano = $acumula_ano / $total_pq_garantia * 100;
				for ($i=0; $i<pg_numrows($res); $i++){
					$porc_ano = (pg_result($res,$i,'conta') / $array_pq_garantia[$i]) * 100;
					$body .= "<td>".round($porc_ano,2)."</td>";
				}
				$body .= "<td><b>".round($total_porcen_ano,2)."%</b></td>";
				$body .= "</tr>";

				### % NO MÊS
                  		$sqlCada = "SELECT  DISTINCT ano, mes
				                                FROM    rpd_0_$login_admin
				                                ORDER BY ano, mes";
                  		$resCada = pg_query($con,$sqlCada);
                  		$cadaProduto = pg_fetch_all($resCada);
                  		foreach($cadaProduto as $anomes){
                      			$body .= "  <tr>
                          					<td bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>".$anomes['mes']."/".$anomes['ano']."</td>";
						for ($i=0; $i<pg_numrows($res); $i++){
							$produto = pg_fetch_result($res,$i,produto);
							$sql = "SELECT  COUNT(*) AS contaCada
										FROM    rpd_0_$login_admin  OS
										JOIN    tbl_produto ON tbl_produto.produto  = OS.produto
										WHERE   tbl_produto.produto = $produto
											AND     OS.mes = '".$anomes['mes']."'
											AND     OS.ano = '".$anomes['ano']."'";

							$resCadaProduto = pg_exec($con,$sql);
							$mesAMes = pg_fetch_result($resCadaProduto,0,contaCada);
							$todaConta = pg_fetch_result($res,$i,conta);
							$percento = (($mesAMes * 100) / $todaConta);
							$body .= "<td>".number_format($percento,2,',',"")."% (".$mesAMes.")</td>";
						}
                      			//$body .= "<td>&nbsp;</td></tr>";
						$body .= "</tr>";
                  		}
			}
			$body .= "</tbody></table >";

			fwrite($fp, $body);
	        	fclose($fp);
	        	if(file_exists($fileTemp)){
	        		system("mv $fileTemp $file");
	        		if(file_exists($file)){
	        			echo $file;
	        		}
	        	}

	}else{
		/* Consulta */

		$join_linha = (strlen($linha) > 0) ? " JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.linha IN ($linha) " : "";

		if(strlen($join_linha) > 0 && strlen($familia2) > 0){

			$join_linha .= " AND tbl_produto.familia = {$familia2} ";

		}else if(strlen($familia2) > 0){

			$join_linha = " JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.familia = {$familia2} ";

		}

		if(strlen($produto) > 0){
			$cond_produto = " AND tbl_os.produto = {$produto} ";
		}

		$join_os_extra = " JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os ";
		$cond_os_extra = " AND tbl_os_extra.garantia IS NOT false ";

		//solicitada a pesquisa pela data_fechamento que consta na data_padão
		// if ($login_fabrica == 117) {
		// 	$queryData = "AND   tbl_os.data_fechamento BETWEEN '$data_inicial' AND '$data_final'";
		// }else{
			$queryData = "AND   tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'";
		// }

		if($login_fabrica == 138) {
				$join_os_produto = "JOIN tbl_os_produto using(os) ";
				$campos = "tbl_os_produto.produto, tbl_os_produto.defeito_constatado";
			}else{
				$campos = "tbl_os.produto,tbl_os.defeito_constatado";
			}

		$sql = "SELECT tbl_os.os,
					to_char(tbl_os.data_abertura,'MM') as mes,
					$campos
			INTO TEMP rpd_1_$login_admin
			FROM tbl_os
			$join_os_extra
			$join_linha
			WHERE tbl_os.fabrica = $login_fabrica
			$queryData

			$cond_os_extra
			$cond_produto;

			CREATE INDEX rpd_1_os_$login_admin      ON rpd_1_$login_admin(os);
			CREATE INDEX rpd_1_dc_$login_admin      ON rpd_1_$login_admin(defeito_constatado);
			CREATE INDEX rpd_1_produto_$login_admin ON rpd_1_$login_admin(produto);";
		$res = pg_query($con, $sql);

		$sql = "SELECT  tbl_produto.produto,
				tbl_produto.descricao     ,
				tbl_produto.nome_comercial,
				tbl_produto.referencia    ,
				COUNT(*) AS conta
			FROM    rpd_1_$login_admin OS
			JOIN    tbl_produto            ON tbl_produto.produto  = OS.produto
			GROUP BY tbl_produto.descricao    ,
				 tbl_produto.nome_comercial,
				 tbl_produto.referencia    ,
				 tbl_produto.produto
			ORDER BY tbl_produto.referencia;";

		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0){
			$file     = "xls/relatorio-percentual-defeitos-garantia-{$login_fabrica}-{$data_inicial}-{$data_final}.xls";
			$fileTemp = "/tmp/relatorio-percentual-defeitos-garantia-{$login_fabrica}-{$data_inicial}-{$data_final}.xls";
	        	$fp       = fopen($fileTemp,"w");

	        	$colspan = pg_num_rows($res) + 2;

	        	$head = "<table border='1'>
	        				<thead>
		                        <tr>
		                            <th bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' colspan='{$colspan}' >RELATÓRIO DE PERCENTUAL DOS DEFEITOS POR PRODUTOS - $mes_inicial/$ano_inicial à $mes_final/$ano_final</th>
		                        </tr>
		                        <tr>
		                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>#</th>";

	                        		for ($i=0; $i < pg_num_rows($res); $i++){
										$head .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".pg_result($res,$i,'descricao')." - ".pg_result($res,$i,'referencia')."</th>";

										$produto = pg_fetch_result($res,$i,'produto');

										# ------------------------------------ 2 ------------------------------------ #
										$sql = "SELECT COUNT(*) AS contaano
											      FROM rpd_1_$login_admin  OS
											      JOIN tbl_produto ON tbl_produto.produto  = OS.produto
											     WHERE tbl_produto.produto = $produto
											       AND OS.mes = TO_CHAR ('$data_inicial'::date,'MM');";

										$res2 = pg_query($con,$sql);
										$contaano[$i] = pg_fetch_result($res2,0,0);
						}

		       $head .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total</th>
		                        </tr>
		                    </thead>
		                    <tbody>";

		    	fwrite($fp, $head);

		    	/* 1 */

	        	$body .= "<tr>
	        		<td bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>PQ Garantia</td>";

	        		$total_pq_garantia = 0;
					for ($i = 0; $i < pg_num_rows($res); $i++){

						$produto = pg_fetch_result($res,$i,'produto');

						$ultimo_dia_mes_final = date("t", mktime(0, 0, 0, $mes_final, "01", $ano_final));

						$sql_pq_garantia = "SELECT SUM(qtde_venda) AS pq_garantia
												FROM tbl_producao
												WHERE produto = $produto
												AND (ano || '-' || mes|| '-01')::date BETWEEN '{$ano_inicial}-{$mes_inicial}-01'::date
												AND '{$ano_final}-{$mes_final}-{$ultimo_dia_mes_final}'::date ;";
						$res_pq_garantia = pg_query($con, $sql_pq_garantia);

						if(pg_num_rows($res_pq_garantia) > 0){
							$pq_garantia = pg_fetch_result($res_pq_garantia, 0, "pq_garantia");
						}

						$pq_qarantia = (strlen($pq_garantia) > 0) ? $pq_garantia : 0;

						$array_pq_garantia[$i] = $pq_garantia;

						$total_pq_garantia += $pq_garantia;
	                    		$pq_texto = ($pq_garantia > 0) ? $pq_garantia : "#PQZero";
						$body .= "<td>{$pq_texto}</td>";
					}

				$body .= "<td><b>{$total_pq_garantia}</b></td>";
	        		$body .= "</tr>";

	        		/* 2 */

		      		$body .= "<tr>
	        		<td bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Atend. Período</td>";

	        		$acumula_ano = 0;
	        		for ($i = 0; $i < count($contaano); $i++){
						$body .= "<td>".pg_fetch_result($res,$i,'conta')."</td>";
						$acumula_ano += pg_fetch_result($res,$i,'conta');
					}

					$body .= "<td><b>{$acumula_ano}</b></td>";
	       		 $body .= "</tr>";

	       		 /* 3 */

	        		$body .= "
	        			<tr>
	        			<td bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>Atend. Mês</td>";

	        		$acumula_mes = 0;
					for ($i = 0; $i < count($contaano); $i++){
						$body .= "<td>".$contaano[$i]."</td>";
						$acumula_mes += $contaano[$i];
					}

					$body .= "<td><b>{$acumula_mes}</b></td>";
	        	$body .= "</tr>";

	        	/* 4 */

	        	$body .= "
	        	<tr>
	        		<td bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>% No Período</td>";

	        		$total_porcen_ano = 0;
					for ($i = 0; $i < pg_num_rows($res); $i++){
						$porc_ano = (pg_fetch_result($res,$i,'conta') / $array_pq_garantia[$i]) * 100;
						$total_porcen_ano += $porc_ano;
						$body .= "<td>".round($porc_ano,2)."</td>";
					}

					$body .= "<td><b>".number_format($total_porcen_ano,2,',',"")."</b></td>";

	        	/* 5 */

	        	$body .= "
	        	<tr>
	        		<td bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>% No Mês</td>";

	        		$total_porcen_mes = 0;
					for ($i = 0; $i < pg_num_rows($res); $i++){
						@$porc_mes = ($contaano[$i] / $acumula_mes) * 100;
						$total_porcen_mes += $porc_mes;
						$body .= "<td>".round($porc_mes,2)."</td>";
					}

					$body .= "<td><b>{$total_porcen_mes}</b></td>";
	        	$body .= "</tr>";

	        	fwrite($fp, $body);

	        	fwrite($fp, "</tbody></table>");
	       	fclose($fp);
	        	if(file_exists($fileTemp)){
	            		system("mv $fileTemp $file");
	            		if(file_exists($file)){
	                		echo $file;
	            		}
	        	}

		}//if (pg_num_rows($res) > 0)
	}

	exit;
	/* Fim Excel */
}

#TRATAMENTO DA MESANGEM DE ERRO
$msg_erro = array();
$msgErrorPattern01 = "Preencha os campos obrigatórios.";
$msgErrorPattern02 = "O intervalo entre as datas não pode ser maior que 1 mês.";
$msgErrorPattern03 = "Não foram encontrados resultados para esta pesquisa.";


##DATA COM MES E ANO
if(!empty($_POST))
{

	if($login_fabrica == 117){

		$mes_inicial = $_POST["mes_inicial"];
		$ano_inicial = $_POST["ano_inicial"];

		$mes_final 	= $_POST["mes_final"];
		$ano_final 	= $_POST["ano_final"];

		$linha 		= $_POST["linha"];
		$macro_familia = $_POST["macro_familia"];
		$familia2 	= $_POST["familia2"];
		$produto 	= $_POST["produto"];
		$produto_pesq = (strlen($produto)) ? $produto : "";

		if(empty($mes_inicial)){
			$msg_erro["msg"][]    = "Preencha o Mês Inicial";
			$msg_erro["campos"][] = "mes_inicial";
		}

		if(empty($ano_inicial)){
			$msg_erro["msg"][]    = "Preencha o Ano Inicial";
			$msg_erro["campos"][] = "ano_inicial";
		}

		if(empty($mes_final)){
			$msg_erro["msg"][]    = "Preencha o Mês Final";
			$msg_erro["campos"][] = "mes_final";
		}

		if(empty($ano_final)){
			$msg_erro["msg"][]    = "Preencha o Ano Final";
			$msg_erro["campos"][] = "ano_final";
		}

		if((int)$ano_inicial == (int)$ano_final && (int)$mes_inicial > (int)$mes_final){
			$msg_erro["msg"][]    = "O Mês Inicial não pode ser maior que Mês Final";
			$msg_erro["campos"][] = "mes_inicial";
		}

		if((int)$ano_inicial > (int)$ano_final){
			$msg_erro["msg"][]    = "O Ano Inicial não pode ser maior que Ano Final";
			$msg_erro["campos"][] = "ano_inicial";
		}

		$ultimo_dia_mes = date("t", mktime(0, 0, 0, $mes_final, "01", $ano_final));

		$data_inicial = $ano_inicial."-".$mes_inicial."-01";
		$data_final = $ano_final."-".$mes_final."-".$ultimo_dia_mes;

		if(strtotime($data_final) > strtotime($data_inicial . ' +36 month')){
			$msg_erro["msg"][]    = "O período não pode maior que 36 meses";
		}
		if (empty($linha)) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "linha";
		}

	}else{

		$mes = (int) $_POST['mes'];
		$ano = (int) $_POST['ano'];

		if ((empty($mes) || empty($ano))){
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}else{
			if (in_array($login_fabrica, [167, 203])) {
				$data_inicial_corte = "01/$mes/$ano";
				$data_corte = "01/04/2019";
				
				if (!verifica_data_corte($data_corte, $data_inicial_corte)) {
					$msg_erro["msg"][]    = "Data informada inferior a data limite para pesquisa";
					$msg_erro["campos"][] = "data";	
				}			
			}

			$data_ini = date("$ano-$mes-01");
			$data_fim = date('Y-m-t', strtotime($data_ini));

			list($yi, $mi, $di) = explode("-", $data_ini);
			list($yf, $mf, $df) = explode("-", $data_fim);

			if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf) || !is_int($ano) || !is_int($mes)) {
				$msg_erro["msg"][]    = $msgErrorPattern01;
				$msg_erro["campos"][] = "data";
			}
		}
	}
}

/* Cabeçalho */
include "cabecalho_new.php";

?>


<?php
/*--------------------------------------------------------------------------------
selectMesSimples()
Cria ComboBox com meses de 1 a 12
--------------------------------------------------------------------------------*/
function selectMesSimples($selectedMes){
	for($dtMes=1; $dtMes <= 12; $dtMes++){
		$dtMesTrue = ($dtMes < 10) ? "0".$dtMes : $dtMes;

		echo "<option value=$dtMesTrue ";
		if ($selectedMes == $dtMesTrue) echo "selected";
		echo ">$dtMesTrue</option>\n";
	}
}
/*--------------------------------------------------------------------------------
	selectAnoSimples($ant,$pos,$dif,$selectedAno)
	// $ant = qtdade de anos retroceder
	// $pos = qtdade de anos posteriores
	// $dif = ve qdo ano termina
	// $selectedAno = ano já setado
	Cria ComboBox com Anos
--------------------------------------------------------------------------------*/
function selectAnoSimples($ant,$pos,$dif=0,$selectedAno){
	$startAno = date("Y"); // ano atual
	for($dtAno = $startAno - $ant; $dtAno <= $startAno + ($pos - $dif); $dtAno++){
		echo "<option value=$dtAno ";
		if ($selectedAno == $dtAno) echo "selected";
		echo ">$dtAno</option>\n";
	}
}
?>

<script>

    $(function(){
        $("select[name='familia2']").change(function(){
        	var linha = [];
            $("#linha option:selected").each(function(i, selected){
            	linha[i] = $(selected).val();
            });
            var familia = $(this).val();

//                      if(linha == ""){
//                              alert("Por favor selecione a Linha para realizar o filtro de Produtos");
//                              $("select[name='linha']").focus();
//                              return;
//                      }

            $.ajax({
                url : "<?php echo $_SERVER['PHP_SELF']; ?>",
                type : "POST",
                data : {
                        get_produto : true,
                        familia : familia
//                                      linha : linha
                },
                complete: function(data){
                        $("select[name='produto']").html(data.responseText);
                }
            });

        });

        $("#macro_familia").change(function(){
        	Seleciona_Familia();
        });

        function Seleciona_Macro_Familia(){
            var macro_linha = [];
            $("#linha option:selected").each(function(i, selected){
                    macro_linha[i] = $(selected).val();
            });

            $.ajax({
                url:"<?php echo $_SERVER['PHP_SELF']; ?>",
                type: "POST",
                data:{
                        get_macro_familia : true,
                        macro_linha : macro_linha
                },
                complete: function(data){
                        $("select[name='macro_familia']").html(data.responseText);
                        $("select[name='familia2']").html('');
                        var macro_familia = <?=(isset($_REQUEST['macro_familia']) && !empty($_REQUEST['macro_familia'])) ? $_REQUEST['macro_familia'] : 'null'; ?>;
                        $("select[name='macro_familia']").val(macro_familia);
                        Seleciona_Familia();
                }
            });
        }

        function Seleciona_Familia(){
	        var linha = [];
	        $("#macro_familia option:selected").each(function(i, selected){
	            linha[i] = $(selected).val();
	        });

	        $.ajax({
                url:"<?php echo $_SERVER['PHP_SELF']; ?>",
                type: "POST",
                data:{
                        get_familia : true,
                        linha : linha
                },
                complete: function(data){
                        $("select[name='familia2']").html(data.responseText);
                        var familia2 = <?=(isset($_REQUEST['familia2']) && !empty($_REQUEST['familia2'])) ? $_REQUEST['familia2'] : 'null'; ?>;
                        $("select[name='familia2']").val(familia2);
                }
	        });        	
        }

        Seleciona_Macro_Familia();

        $("#linha").change(function(){
			Seleciona_Macro_Familia();
        });

        if ($("select[name='familia2']").val() !== '') {
			var familia = $("select[name='familia2']").val();

            $.ajax({
                url : "<?php echo $_SERVER['PHP_SELF']; ?>",
                type : "POST",
                data : { get_produto : true, familia : familia },
                complete: function(data){
					$("select[name='produto']").html(data.responseText);
					var produto = <?=(isset($_REQUEST['produto']) && !empty($_REQUEST['produto'])) ? $_REQUEST['produto'] : 'null'; ?>;
					$("select[name='produto']").val(produto);
                }
            });
        }
	})

</script>

<!-- MENSAGEM DE ERRO -->
<?php
	if (count($msg_erro["msg"]) > 0) {
	?>
	    <div class="alert alert-error">
	        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	    </div>
	<?php
	}
?>

<div class="alert alert-warning">
	<h4>Data mínima para pesquisa 01/04/2019</h4>
</div>
<script type="text/javascript" src="../admin/plugins/jquery_multiselect/js/jquery.multi-select.js"></script>
<link rel="stylesheet" type="text/css" href="../admin/plugins/jquery_multiselect/css/multi-select.css" />
<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<form name='frm_percentual' action='<? echo $PHP_SELF ?>' class="form-search form-inline tc_formulario" method="POST">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class="container tc_container">

		<?php
		if($login_fabrica == 117){
			?>

				<div class='row-fluid'>
			        <div class='span2'></div>

			        <div class='span2'>
			        	<div class='control-group <?=(in_array("mes_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
		                    <label class='control-label' for='mes_inicial'>Data Inicial</label>
		                    <div class='controls controls-row'>
		                        <div class='span10'>
		                            <h5 class='asteristico'>*</h5>
		                            <select name="mes_inicial" id="mes_inicial" class="span12">
		                            	<option value=""></option>
		                            	<?php
		                            		for($i = 1; $i <= 12; $i++){
		                            			$mes = ($i < 10) ? "0".$i : $i;
		                            			$selected = ($mes_inicial == $i) ? "selected" : "";
		                            			echo "<option value='".$mes."' {$selected}>".$mes."</option>";
		                            		}
		                            	?>
		                            </select>
		                        </div>
		                    </div>
		                </div>
			        </div>
			        <?php
			         $ano_atual = date(Y);
			         $ano_anterior =  date('Y',strtotime("-3 years"));
			        ?>
			        <div class='span2'>
			        	<div class='control-group <?=(in_array("ano_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
		                    <label class='control-label' for='ano_inicial'>&nbsp;</label>
		                    <div class='controls controls-row'>
		                        <div class='span10'>
		                        	<h5 class='asteristico'>*</h5>
		                            <select name="ano_inicial" id="ano_inicial" class="span12">
		                            	<option value=""></option>
		                            	<?php
		                            		for($i = $ano_atual; $i >= $ano_anterior; $i--){
		                            			$selected = ($ano_inicial == $i) ? "selected" : "";
		                            			echo "<option value='".$i."' {$selected}>".$i."</option>";
		                            		}
		                            	?>
		                            </select>
		                        </div>
		                    </div>
		                </div>
			        </div>

			        <div class='span2'>
			        	<div class='control-group <?=(in_array("mes_final", $msg_erro["campos"])) ? "error" : ""?>'>
		                    <label class='control-label' for='mes_final'>Data Final</label>
		                    <div class='controls controls-row'>
		                        <div class='span10'>
		                            <h5 class='asteristico'>*</h5>
		                            <select name="mes_final" id="mes_final" class="span12">
		                            	<option value=""></option>
		                            	<?php
		                            		for($i = 1; $i <= 12; $i++){
		                            			$mes = ($i < 10) ? "0".$i : $i;
		                            			$selected = ($mes_final == $i) ? "selected" : "";
		                            			echo "<option value='".$mes."' {$selected}>".$mes."</option>";
		                            		}
		                            	?>
		                            </select>
		                        </div>
		                    </div>
		                </div>
			        </div>

			        <div class='span2'>
			        	<div class='control-group <?=(in_array("ano_final", $msg_erro["campos"])) ? "error" : ""?>'>
		                    <label class='control-label' for='ano_final'>&nbsp;</label>
		                    <div class='controls controls-row'>
		                        <div class='span10'>
		                        	<h5 class='asteristico'>*</h5>
		                            <select name="ano_final" id="ano_final" class="span12">
		                            	<option value=""></option>
		                            	<?php
		                            		for($i = $ano_atual; $i >= $ano_anterior; $i--){
		                            			$selected = ($ano_final == $i) ? "selected" : "";
		                            			echo "<option value='".$i."' {$selected}>".$i."</option>";
		                            		}
		                            	?>
		                            </select>
		                        </div>
		                    </div>
		                </div>
			        </div>

			        <div class='span2'></div>
			    </div>

			    <div class="row-fluid">
			    	<div class='span2'></div>

			    	<!-- Linha -->
			    	<div class='span8'>
			        	<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
		                    <label class='control-label' for='linha'>Linha</label>
		                    <div class='controls controls-row'>
		                        <div class='span11'>
		                            <h5 class='asteristico'>*</h5>
		                            <select multiple="multiple" name="linha[]" id="linha" class="span12">
                                        <?php
                                                // $sql_linha = "SELECT linha, nome FROM tbl_linha WHERE ativo IS TRUE AND fabrica = {$login_fabrica} AND linha IN (697, 698, 699, 700) ORDER BY nome ASC";
                                                $sql_linha = "SELECT distinct tbl_macro_linha.descricao as nome,tbl_macro_linha.macro_linha as linha FROM tbl_macro_linha
                                                            JOIN tbl_macro_linha_fabrica ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
                                                    WHERE fabrica = {$login_fabrica}
                                                    ORDER BY tbl_macro_linha.descricao;";
                                                $res_linha = pg_query($con, $sql_linha);
                                                if(pg_num_rows($res_linha) > 0){
                                                        for($i = 0; $i < pg_num_rows($res_linha); $i++){

                                                                $lin = pg_fetch_result($res_linha, $i, "linha");
                                                                $nome = pg_fetch_result($res_linha, $i, "nome");

                                                                $selected = (in_array($lin,$linha)) ? "SELECTED" : "";

                                                                echo "<option value='".$lin."' {$selected}>". $nome."</option>";

                                                        }
                                                }
                                        ?>
		                            </select>
		                        </div>
		                    </div>
		                </div>
			        </div>

			        <div class='span2'></div>
                </div>
                <div class="row-fluid">
					<div class='span2'></div>
                    <!-- Macro - Família -->
                    <div class='span4'>
                        <div class='control-group'>
                        <label class='control-label' for='macro_familia'>Macro - Família</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <select name="macro_familia" id="macro_familia" class="span12">
                                    <option></option>
                                    <?php
                                        $sql_mf = "SELECT DISTINCT
                                                                tbl_linha.linha,
                                                                tbl_linha.nome
                                                  FROM tbl_linha
                                                  JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                        		  JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
                                                  JOIN tbl_produto USING(linha)
                                                  WHERE tbl_linha.fabrica = $login_fabrica
                                                  ORDER BY tbl_linha.nome ";

                                        $res_mf = pg_query($con, $sql_mf);
                                        if(pg_num_rows($res_mf) > 0){
                                                for($i = 0; $i < pg_num_rows($res_mf); $i++){

                                                        $lin_mf = pg_fetch_result($res_mf, $i, "linha");
                                                        $nome_mf = pg_fetch_result($res_mf, $i, "nome");

                                                        $selected_mf = ($lin_mf == $macro_familia)? "SELECTED" : "";

                                                        echo "<option value='".$lin_mf."' {$selected_mf}>". $nome_mf."</option>";

                                                }
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
					<!-- Familia -->
			        <div class='span4'>
			        	<div class='control-group <?=(in_array("familia2", $msg_erro["campos"])) ? "error" : ""?>'>
		                    <label class='control-label' for='familia2'>Família</label>
		                    <div class='controls controls-row'>
		                        <div class='span11'>
		                            <select name="familia2" id="familia2" class="span12">
		                            	<option></option>
		                            	<?php
		                            		$sql_familia = "SELECT  DISTINCT tbl_familia.familia,
                                                                    tbl_familia.descricao
                                                            FROM tbl_familia 
                                                                    JOIN tbl_linha ON tbl_familia.linha = tbl_linha.linha
                                                                    JOIN tbl_produto ON tbl_linha.linha = tbl_produto.linha AND tbl_produto.fabrica_i = {$login_fabrica}
                                                            WHERE tbl_familia.fabrica = {$login_fabrica} 
                                                            ORDER BY descricao ASC";
		                            		$res_familia = pg_query($con, $sql_familia);
		                            		if(pg_num_rows($res_familia) > 0){
		                            			for($i = 0; $i < pg_num_rows($res_familia); $i++){

		                            				$fam = pg_fetch_result($res_familia, $i, "familia");
		                            				$descricao = pg_fetch_result($res_familia, $i, "descricao");

		                            				$selected = ($fam == $familia2) ? "SELECTED" : "";

		                            				echo "<option value='".$fam."' {$selected}>".$descricao."</option>";

		                            			}
		                            		}
		                            	?>
		                            </select>
		                        </div>
		                    </div>
		                </div>
			        </div>
			        <div class='span2'></div>
			    </div>
			    <div class="row-fluid">
			    	<div class='span2'></div>
					<!-- Produto -->
			    	<div class='span4'>
			        	<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
		                    <label class='control-label' for='produto'>Produto</label>
		                    <div class='controls controls-row'>
		                        <div class='span11'>
		                            <select name="produto" id="produto" class="span12">
		                            	<?php
		                            		if(array_count_values($linha) > 0 && strlen($familia2) > 0){
                                                $linhas = implode(",",$linha);
		                            			$sql_produto = "SELECT produto, referencia, descricao FROM tbl_produto WHERE ativo IS TRUE AND fabrica_i = {$login_fabrica} AND linha IN ($linhas) AND familia = {$familia2} ORDER BY descricao ASC";
												$res_produto = pg_query($con, $sql_produto);
												if(pg_num_rows($res_produto) > 0){
													$dados = "<option value=''></option>";
													for($i = 0; $i < pg_num_rows($res_produto); $i++){

														$prod = pg_fetch_result($res_produto, $i, "produto");
														$descricao = pg_fetch_result($res_produto, $i, "descricao");
														$referencia = pg_fetch_result($res_produto, $i, "referencia");

														$selected = ($prod == $produto) ? "SELECTED" : "";

														$dados .= "<option value='".$prod."' {$selected}>".$descricao." - ".$referencia."</option>";

													}
													echo $dados;
												}

		                            		}
		                            	?>
		                            </select>
		                        </div>
		                    </div>
		                </div>
			        </div>					
					<div class='span2'></div>
			    </div>
			<?php
		}else{
			?>

				<div class='row-fluid'>
					<div class='span2'></div>

					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='Mes'>Mês</label>
							<div class='controls controls-row'>
								<div class='span4'>
									<h5 class='asteristico'>*</h5>
									<select name='mes' class="frm">
										<option value=''></option>
										<?php selectMesSimples($mes); ?>
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class='span4'>
						<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='Ano'>Ano</label>
							<div class='controls controls-row'>
								<div class='span4'>
								<h5 class='asteristico'>*</h5>
									<select name='ano' class="frm">
										<option value=''></option>
										<?php selectAnoSimples(2,0,'',$ano) ?>
									</select>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="container tc_container">
					<div class='row-fluid'>
						<div class='span2'></div>
						<div class='span4'>
							<div class='control-group'>
								<label class='control-label' for='familia'>Familia</label>
								<div class='controls controls-row'>
									<div class='span4'>
										<label class="checkbox" for="familia">
											<INPUT type="checkbox" name="familia" value='t' <? if($familia == 't') echo " checked"?>>
										</label>
									</div>
								</div>
							</div>
						</div>
						<div class='span4'>
							<div class='control-group'>
								<label class='control-label' for='posto'>Por posto</label>
								<div class='controls controls-row'>
									<div class='span4'>
										<label class="checkbox" for="posto">
											<input type="checkbox" name="posto" value='t' <? if($posto == 't') echo " checked"?>>
										</label>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

			<?php
		}
		?>

	</div>
	<br />
		<center>
			<input class="btn" type="button" onclick="frm_percentual.submit();" value="Pesquisar">
		</center>
	<br />
</form>
<br />

<?php
flush();
if (((strlen($mes) > 0 AND strlen($ano) > 0) OR (strlen($data_inicial) > 0 && strlen($data_final) > 0)) && count($msg_erro["msg"]) == 0){

	if($_POST["familia"] != "t" && $_POST["posto"] != "t"){
		echo "<div class='alert alert-warning'>Para ver a descrição do Produto passe o mouse sobre o código</div>";
	}

	if($login_fabrica != 117){

		$data_ano = "$ano-01-01";
		$data     = "$ano-$mes-01";

		$sql 		  = "SELECT fn_dias_mes('$data',0)";
		$resX 		  = pg_exec($con,$sql);
		$data_inicial = pg_result($resX,0,0);

		$sql 		= "SELECT fn_dias_mes('$data',1)";
		$resX 		= pg_exec($con,$sql);
		$data_final = pg_result($resX,0,0);

		$sql 			  = "SELECT fn_dias_mes('$data_ano',0)";
		$resX 			  = pg_exec($con,$sql);
		$data_inicial_ano = pg_result($resX,0,0);

	}else if($login_fabrica == 117){

		$data_ano = "$ano_inicial-01-01";

		$sql 			  = "SELECT fn_dias_mes('$data_ano',0)";
		$resX 			  = pg_exec($con,$sql);
		$data_inicial_ano = pg_result($resX,0,0);

	}

	// INICIO DO XLS
	if (strlen($posto) > 0)
	{
		$data = date ("dmY-hsi");

		echo `rm /var/www/assist/www/admin/xls/defeitos_produtos-$data-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/defeitos_produtos-$data-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>DEFEITOS POR PRODUTOS - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
	}

	$join_os_extra = '';
	$cond_os_extra = '';
	if ($login_fabrica == 117){
		$join_os_extra = ' JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os ';
		$cond_os_extra = ' AND tbl_os_extra.garantia IS NOT FALSE ';
	}

	##Se não é por FAMILIA
	if (strlen($familia) == 0){
		if(strlen($posto) == 0){
			# -- 1 - Familia == 0  :: Posto == 0 -- #

			$datas = relatorio_data("$data_inicial_ano","$data_final");
			
			if($login_fabrica == 117){
                if (!empty($linha) AND empty($macro_familia) ) {
                        $linhas_ml = implode(",",$linha);
                        $sql_ml = "SELECT tbl_macro_linha_fabrica.linha FROM tbl_macro_linha_fabrica WHERE tbl_macro_linha_fabrica.macro_linha in ($linhas_ml)";
                        $res_ml = pg_query($con,$sql_ml);

                        if (pg_num_rows($res_ml) > 0) {
                                $linhas = array();
                                for ($i=0; $i < pg_num_rows($res_ml) ; $i++) {
                                        $linhas[] = pg_fetch_result($res_ml, $i, linha);
                                }
                                $linhas = implode(",", $linhas);
                        }else{
                                $linhas = "";
                        }
                }

                if (!empty($macro_familia)) {
                        $linhas = $macro_familia;
                }

				$join_linha = (array_count_values($linha) > 0) ? " JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.linha IN ($linhas) " : "";

				if(strlen($join_linha) > 0 && strlen($familia2) > 0){

					$join_linha .= " AND tbl_produto.familia = {$familia2} ";

				}else if(strlen($familia2) > 0){

					$join_linha = " JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.familia = {$familia2} ";

				}

				if(strlen($produto) > 0){
					$cond_produto = " AND tbl_os.produto = {$produto} ";
				}

			}

			if($login_fabrica == 117){
				$cond_os_finalizada_cortesia = " AND tbl_os.finalizada IS NOT NULL AND tbl_os.cortesia IS FALSE AND tbl_os.status_checkpoint = 9 ";
			}

			if($login_fabrica == 138) {
				$join_os_produto = "JOIN tbl_os_produto using(os) ";
				$campos = "tbl_os_produto.produto, tbl_os_produto.defeito_constatado";
			}else{
				$campos = "tbl_os.produto,tbl_os.defeito_constatado";
			}

			if($login_fabrica == 163){
				$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
				$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
			}

			$cont = 0;
			foreach($datas as $data_pesquisa){
				$data_inicial = $data_pesquisa[0];
				$data_final = $data_pesquisa[1];
				$data_final = str_replace(' 23:59:59', '', $data_final);
				if($cont == 0){

					$sql = "SELECT tbl_os.os,
							to_char(tbl_os.$data_padrao,'MM') as mes,
							to_char(tbl_os.$data_padrao,'YYYY') as ano,
							$campos
							INTO TEMP rpd_0_$login_admin
							FROM tbl_os
							$join_os_extra
							$join_os_produto
							$join_linha
							$join_163
							WHERE tbl_os.fabrica = $login_fabrica
							AND   tbl_os.$data_padrao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
							$cond_os_finalizada_cortesia
							$cond_os_extra
							$cond_produto
							$cond_163
							";
				}else{
					$sql = "INSERT INTO rpd_0_$login_admin (os, mes, ano, produto, defeito_constatado) SELECT tbl_os.os,
                            to_char(tbl_os.$data_padrao,'MM') as mes,
							to_char(tbl_os.$data_padrao,'YYYY') as ano,
							$campos
							FROM tbl_os
							$join_os_extra
							$join_os_produto
							$join_linha
							$join_163
							WHERE tbl_os.fabrica = $login_fabrica
							AND   tbl_os.$data_padrao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
							$cond_os_finalizada_cortesia
							$cond_os_extra
							$cond_produto
							$cond_163";

				}
				$res = pg_query($con,$sql);
				$cont++;

			}
			$sql = "

				CREATE INDEX rpd_0_os_$login_admin      ON rpd_0_$login_admin(os);
				CREATE INDEX rpd_0_dc_$login_admin      ON rpd_0_$login_admin(defeito_constatado);
				CREATE INDEX rpd_0_produto_$login_admin ON rpd_0_$login_admin(produto);";
			$res = pg_exec($con,$sql);

			$sql = "SELECT tbl_produto.produto,
							tbl_produto.descricao     ,
							tbl_produto.nome_comercial,
							tbl_produto.referencia    ,
							COUNT(*) AS conta
				FROM    rpd_0_$login_admin OS
				JOIN    tbl_produto            ON tbl_produto.produto  = OS.produto
				GROUP BY tbl_produto.descricao    ,
					 tbl_produto.nome_comercial,
					 tbl_produto.referencia    ,
					 tbl_produto.produto
				ORDER BY tbl_produto.referencia;";

			$res = pg_exec($con,$sql);
			flush();

			if (pg_numrows($res) == 0){
				echo "	<p><div class='alert'><h4>".$msgErrorPattern03."</h4></div></p>";
				$showMsg = 01;
			}else{
				echo "</div><table align='center' class='table table-striped table-bordered table-hover table-large' >";

				### monta linha de nome dos produtos
				echo "	<thead>
						<tr class='titulo_coluna'>
						<th>#</th>";

				for ($i=0; $i<pg_num_rows($res); $i++){
					echo "<th><acronym title='".pg_result($res,$i,'descricao')."'>";

					if($login_fabrica<>20 and $login_fabrica<>2){
						if(pg_result($res,$i,'nome_comercial')) {
							echo pg_result($res,$i,'nome_comercial');
                        			}else {
                            			echo pg_result($res,$i,'referencia');
                        			}
					}else{
                        			echo pg_result($res,$i,'referencia');
					}
					echo "</acronym></b></th>";
					flush();
					$produto = pg_result($res,$i,'produto');

					# ------------------------------------ 2 ------------------------------------ #
					if($login_fabrica == 117){
						$sql = "SELECT COUNT(*) AS contaano
							FROM tbl_os
							$join_os_extra
							$join_linha
							WHERE tbl_produto.produto = $produto
							$cond_os_finalizada_cortesia
							$cond_os_extra
							$cond_produto
							AND tbl_os.data_digitacao >= '".date('Y-m-01')." 00:00:00';";
					}else{
						$sql = "SELECT COUNT(*) AS contaano
						      FROM rpd_0_$login_admin  OS
						      JOIN tbl_produto ON tbl_produto.produto  = OS.produto
						     WHERE tbl_produto.produto = $produto
						       AND OS.mes = TO_CHAR ('$data_inicial'::date,'MM');";
					}
					$res2 = pg_exec($con,$sql);
					$contaano[$i] = pg_result($res2,0,0);
					flush();
				}

				if($login_fabrica == 117){
					echo "<th>Total</th>";
				}

				echo "	</thead>
						</tr>";

				### MONTA LINHA EM BRANCO, PQ GARANTIA
				echo "	<tbody>
						<tr class='table_line' bgcolor='#F7F7F7'>
							<td class='menu_top'>PQ Garantia</td>";
							$total_pq_garantia = 0;
							for ($i=0; $i<pg_numrows($res); $i++){

								if($login_fabrica == 117){

									$produto = pg_result($res,$i,'produto');
									//$ultimo_dia_mes_inicial = date("t", mktime(0, 0, 0, $mes_inicial, "01", $ano_inicial));
									$ultimo_dia_mes_final = date("t", mktime(0, 0, 0, $mes_final, "01", $ano_final));

									$sql_pq_garantia = "SELECT SUM(qtde_venda) AS pq_garantia
																FROM tbl_producao
																WHERE produto = $produto
																AND (ano || '-' || mes|| '-01')::date BETWEEN '{$ano_inicial}-{$mes_inicial}-01'::date
																AND '{$ano_final}-{$mes_final}-{$ultimo_dia_mes_final}'::date ;";
									$res_pq_garantia = pg_query($con, $sql_pq_garantia);
									//echo nl2br($sql_pq_garantia);
									if(pg_num_rows($res_pq_garantia) > 0){
										$pq_garantia = pg_fetch_result($res_pq_garantia, 0, "pq_garantia");
									}else{
										$pq_garantia = 0;
									}

									//$pq_qarantia = (strlen($pq_garantia) > 0) ? $pq_garantia : 0;
									$array_pq_garantia[$i] = $pq_garantia;

									$total_pq_garantia += $pq_garantia;
                                    				$pq_texto = ($pq_garantia > 0) ? $pq_garantia : "#PQZero";
								}else{
									$pq_qarantia = "&nbsp;";
								}

								echo "<td>{$pq_texto}</td>";
							}
							if($login_fabrica == 117){
								echo "<td><strong>{$total_pq_garantia}</strong></td>";
							}
				echo "	</tr>";

				### MONTA LINHA COM TOTAL DE OS DO ANO
				$acumula_ano = 0;
				$texto_ano = ($login_fabrica == 117) ? "Atend. Período" : "Atend. Ano";
				echo "	<tr class='table_line' bgcolor='#F1F4FA'>
							<td class='menu_top'>$texto_ano</td>";
							for ($i=0; $i<pg_numrows($res); $i++){
								echo "<td align='right' style='padding-right:5px;'>".pg_result($res,$i,'conta')."</td>";
								$acumula_ano += pg_result($res,$i,'conta');
							}
							if($login_fabrica == 117){
								echo "<td><strong>{$acumula_ano}</strong></td>";
							}
				echo "</tr>";

				### MONTA LINHA COM TOTAL DE OS DO MÊS
				$acumula_mes = 0;
				echo "	<tr class='table_line' BGCOLOR='#F7F7F7'>
							<td class='menu_top'>Atend. Mês</td>";
							for ($i=0; $i<count($contaano); $i++){
								echo "<td align='right' style='padding-right:5px;'>".$contaano[$i]."</td>\n";
								$acumula_mes += $contaano[$i];
							}
							if($login_fabrica == 117){
								echo "<td><strong>{$acumula_mes}</strong></td>";
							}
				echo "	</tr>";

				### % NO ANO
				$texto_porc_ano = ($login_fabrica == 117) ? "% No Período" : "% No Ano";
				echo "	<tr class='table_line'BGCOLOR='#F7F7F7'>
							<td class='menu_top' bgcolor='#F1F4FA'>$texto_porc_ano</td>";
							$total_porcen_ano = $acumula_ano / $total_pq_garantia * 100;
							for ($i=0; $i<pg_numrows($res); $i++){
								if($login_fabrica == 117){
									$porc_ano = (pg_result($res,$i,'conta') / $array_pq_garantia[$i]) * 100;
								}else{
									$porc_ano = (pg_result($res,$i,'conta') / $acumula_ano) * 100;
								}
								echo "<td align='right' style='padding-right:5px;'>".round($porc_ano,2)."</td>\n";
							}
							if($login_fabrica == 117){
								echo "<td><strong>".round($total_porcen_ano,2)."%</strong></td>";
							}
				echo "	</tr>";

				### % NO MÊS
				if($login_fabrica != 117){
				echo "	<tr class='table_line' bgcolor='#F1F4FA'>
							<td class='menu_top'>% No Mês</td>";
							$total_porcen_mes = 0;
							for ($i=0; $i<pg_numrows($res); $i++){
								@$porc_mes = ($contaano[$i] / $acumula_mes) * 100;
								$total_porcen_mes += $porc_mes;
								echo "<td align='right' style='padding-right:5px;'>".round($porc_mes,2)."</td>";
							}
							// if($login_fabrica == 117){
							// 	echo "<td><strong>{$total_porcen_mes} %</strong></td>";
							// }
				echo "	</tr>";
                		}
				if($login_fabrica == 117){
                    		$sqlCada = "SELECT  DISTINCT ano, mes
					                                FROM    rpd_0_$login_admin
					                                ORDER BY ano, mes";
                    		$resCada = pg_query($con,$sqlCada);
                    		$cadaProduto = pg_fetch_all($resCada);
                    		foreach($cadaProduto as $anomes){
                        			echo "  <tr class='table_line' bgcolor='#F1F4FA'>
                            					<td class='menu_top'>".$anomes['mes']."/".$anomes['ano']."</td>";
                        						for ($i=0; $i<pg_numrows($res); $i++){
                            						$produto = pg_fetch_result($res,$i,produto);
                            						$sql = "SELECT  COUNT(*) AS contaCada
                                    								FROM    rpd_0_$login_admin  OS
                                    								JOIN    tbl_produto ON tbl_produto.produto  = OS.produto
                                    								WHERE   tbl_produto.produto = $produto
                                    								AND     OS.mes = '".$anomes['mes']."'
                                    								AND     OS.ano = '".$anomes['ano']."'";
										//echo nl2br($sql);
										$resCadaProduto = pg_exec($con,$sql);
										$mesAMes = pg_fetch_result($resCadaProduto,0,contaCada);
										$todaConta = pg_fetch_result($res,$i,conta);
										$percento = (($mesAMes * 100) / $todaConta);
										echo "<td align='right' style='padding-right:5px;'>".number_format($percento,2,',',"")."% (".$mesAMes.")</td>";
                            						flush();
                        						}
                        			echo "<td>&nbsp;</td></tr>";
                    		}
				}

				### % MÉDIA
				if($login_fabrica != 117){
					echo "<tr class='table_line' bgcolor='#F1F4FA'>
					<td class='menu_top'>% Média</td>";
					for ($i=0; $i<pg_numrows($res); $i++){
						echo "<td>&nbsp;</td>\n";
					}
					if($login_fabrica == 117){
						echo "<td>&nbsp;</td>";
					}
					echo "</tr>";
				}

				echo "</table>";
			}

			if($login_fabrica == 117){

				$dados = array(
					"mes_inicial" 	=> $mes_inicial,
					"ano_inicial" 	=> $ano_inicial,
					"mes_final" 	=> $mes_final,
					"ano_final" 	=> $ano_final,
					"linha" 		=> $linhas,
					"familia2" 		=> $familia2,
					"produto" 		=> $produto_pesq,
				);

				?>
				<br />

				<div id='gerar_excel' class="btn_excel">
			        <input type="hidden" id="jsonPOST" value='<?php echo json_encode($dados); ?>' />
			        <span><img src="imagens/excel.png" /></span>
			        <span class="txt">Gerar Arquivo Excel</span>
			    </div>
				<?php
			}

		}else{
			# -- 2 - Familia == 0  :: Posto > 0 -- #

			if($login_fabrica == 117){

				if(array_count_values($linha) > 0){
                    $linhas = implode(",",$linha);
					$join_linha = "
						JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
						JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.linha IN ($linhas) ";
						$cond_linha = " AND tbl_linha.linha IN ($linhas) ";
				}

				if(strlen($join_linha) > 0 && strlen($familia2) > 0){
					$join_linha .= " JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.familia = {$familia2} ";
					$join_familia = " JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.familia = {$familia2} ";
				}

				if(strlen($join_linha) == 0 && strlen($familia2) > 0){
					$join_linha = "
						JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
						JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.familia = {$familia2} ";
				}

				if(strlen($join_linha) > 0 && strlen($produto) > 0){
					$cond_produto = " AND tbl_produto.produto = {$produto} ";
				}

				if(strlen($join_linha) == 0 && strlen($produto) > 0){
					$join_linha = " JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_produto.produto = {$produto} ";
					$cond_produto = "";
				}

				$cond_os_finalizada_cortesia = " AND tbl_os.finalizada IS NOT NULL AND tbl_os.cortesia IS FALSE ";

			}

			if($login_fabrica == 138) {
				$join_os_produto = "JOIN tbl_os_produto using(os) ";
				$campos = "tbl_os_produto.produto, tbl_os_produto.defeito_constatado";
			}else{
				$campos = "tbl_os.produto,tbl_os.defeito_constatado";
			}

			$sql = "
				SELECT tbl_os.os,
						to_char(tbl_os.$data_padrao,'MM') as mes,
						tbl_os.posto,
						$campos
				INTO TEMP rpd_0_$login_admin
				FROM tbl_os
				$join_linha
				$join_os_extra
				$join_os_produto
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.$data_padrao BETWEEN '$data_inicial' AND '$data_final'
				$cond_produto
				$cond_os_finalizada_cortesia
				$cond_os_extra;

				CREATE INDEX rpd_0_os_$login_admin      ON rpd_0_$login_admin(os);
				CREATE INDEX rpd_0_dc_$login_admin      ON rpd_0_$login_admin(defeito_constatado);
				CREATE INDEX rpd_0_produto_$login_admin ON rpd_0_$login_admin(produto);
				CREATE INDEX rpd_0_posto_$login_admin   ON rpd_0_$login_admin(posto);

				SELECT  DISTINCT
					tbl_posto.posto  ,
					tbl_posto.nome   ,
					tbl_posto.estado
				FROM    tbl_posto
				JOIN    tbl_posto_fabrica         ON tbl_posto_fabrica.posto = tbl_posto.posto
				JOIN    rpd_0_$login_admin tbl_os ON tbl_os.posto            = tbl_posto.posto
				JOIN    tbl_produto               ON tbl_produto.produto     = tbl_os.produto
				JOIN    tbl_linha                 ON tbl_linha.linha     = tbl_produto.linha
				JOIN    tbl_fabrica               ON tbl_fabrica.fabrica = tbl_linha.fabrica
				WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
				AND     tbl_linha.fabrica         = $login_fabrica
				AND     tbl_produto.ativo
				ORDER BY tbl_posto.nome;";
			$resX = pg_query($con,$sql);

			if (pg_num_rows($resX) > 0) {
				echo "<table align='center' class='table table-striped table-bordered table-hover table-large'>
						<thead>
							<tr class='titulo_coluna'>
								<th>Posto</th>
								<th>UF</th>";
									flush();
									$sql_produto = "SELECT  tbl_produto.produto,
											tbl_produto.descricao,
											tbl_produto.nome_comercial,
											tbl_produto.referencia
										FROM    tbl_produto
										JOIN    tbl_linha   ON tbl_linha.linha     = tbl_produto.linha $cond_linha
										$join_familia
										JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_linha.fabrica
										WHERE   tbl_linha.fabrica = $login_fabrica
										AND     tbl_produto.ativo = 't'
										$cond_produto";
									$res_produto = pg_query($con, $sql_produto);

									// echo nl2br($sql_produto);

									for ($i = 0; $i < pg_num_rows($res_produto); $i++)
									{
										echo "<th><acronym title='".pg_result($res_produto,$i,descricao)."'>";
										if(strlen(pg_result($res_produto,$i,nome_comercial)) > 0)
											echo pg_result($res_produto,$i,nome_comercial);
										else
											echo pg_result($res_produto,$i,referencia);
										echo "</acronym></th>\n";
										$produto[$i] = pg_result($res_produto,$i,produto);
									}
					echo "	</tr>
						<thead>
						<tbody>";

				//*********************************************************************
				// XLS
				fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='1' class='tabela'>");
				fputs ($fp,"<tr>");
				fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>POSTO</td>");
				fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>UF</td>");

				for ($i=0; $i<pg_numrows($res); $i++){
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>".pg_fetch_result($res,$i,descricao)."</td>");
				}

				fputs ($fp,"</tr>");
				// XLS
				//*********************************************************************/

				for ($k = 0; $k < pg_num_rows($resX); $k++){

					# ------------------------------------ 2 ------------------------------------ #
					$posto  = pg_fetch_result($resX ,$k, "posto");
					$nome   = pg_fetch_result($resX ,$k, "nome");
					$estado = pg_fetch_result($resX ,$k, "estado");

					### MONTA LINHA COM TOTAL DE OS DO Mes
					echo "<tr>
							<td nowrap>$nome</td>
							<td>$estado</td>";

					//*********************************************************************
					// XLS
					fputs ($fp,"<tr>");
					fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>$nome</td>");
					fputs ($fp,"<td bgcolor='$cor' align='center'>$estado</td>");
					// XLS
					//*********************************************************************/

					for($x = 0; $x < pg_num_rows($res_produto); $x++){
						$sql_produto2 = "SELECT COUNT(*) AS conta
							FROM    rpd_0_$login_admin tbl_os
							JOIN    tbl_produto    ON tbl_produto.produto  = tbl_os.produto
							JOIN    tbl_linha      ON tbl_linha.linha      = tbl_produto.linha
							JOIN    tbl_fabrica    ON tbl_fabrica.fabrica  = tbl_linha.fabrica
							WHERE   tbl_linha.fabrica   = $login_fabrica
							AND     tbl_os.posto        = $posto
							AND     tbl_produto.produto = $produto[$x]";
						$resMes = pg_query($con, $sql_produto2);

						echo "<td  bgcolor='$cor' align='right' style='padding-right:5px;'>".pg_fetch_result($resMes,0,"conta")."</td>\n";

						//*********************************************************************
						// XLS
						fputs ($fp,"<td bgcolor='$cor' align='center'>".pg_fetch_result($resMes,0,"conta")."</td>");
						//*********************************************************************/
					}
					echo "</tr>";
					#fputs ($fp,"</tr>");
				}

				echo "</tbody>
				</table>";
			}
			flush();
		}

	}else{
		// POR FAMILIA
		if (strlen($posto) == 0){
			# -- 3 - Familia > 0  :: Posto == 0  -- #

			if($login_fabrica == 117){

				if(array_count_values($linha) > 0){
                    			$linhas = implode(",",$linha);
					$join_linha = "
						JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
						JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.linha IN ($linhas) ";
				}

				if(strlen($join_linha) > 0 && strlen($familia2) > 0){
					$join_linha .= " JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.familia = {$familia2} ";
				}

				if(strlen($join_linha) == 0 && strlen($familia2) > 0){
					$join_linha = "
						JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
						JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.familia = {$familia2} ";
				}

				if(strlen($join_linha) > 0 && strlen($produto) > 0){
					$cond_produto = " AND tbl_produto.produto = {$produto} ";
				}

				if(strlen($join_linha) == 0 && strlen($produto) > 0){
					$join_linha = " JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_produto.produto = {$produto} ";
					$cond_produto = "";
				}

				$cond_os_finalizada_cortesia = " AND tbl_os.finalizada IS NOT NULL AND tbl_os.cortesia IS FALSE ";

			}

			if($login_fabrica == 138) {
				$join_os_produto = "JOIN tbl_os_produto using(os) ";
				$campos = "tbl_os_produto.produto, tbl_os_produto.defeito_constatado";
			}else{
				$campos = "tbl_os.produto,tbl_os.defeito_constatado";
			}

			if($login_fabrica == 163){
				$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
				$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
			}


			$sql = "
				SELECT tbl_os.os,
					to_char(tbl_os.$data_padrao,'MM') as mes,
					$campos
				INTO TEMP rpd_0_$login_admin
				FROM tbl_os
				$join_os_extra
				$join_linha
				$join_163
				$join_os_produto
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.$data_padrao BETWEEN '$data_inicial_ano' AND '$data_final'
				$join_produto
				$cond_os_finalizada_cortesia
				$cond_163
				$cond_os_extra;

				CREATE INDEX rpd_0_os_$login_admin      ON rpd_0_$login_admin(os);
				CREATE INDEX rpd_0_dc_$login_admin      ON rpd_0_$login_admin(defeito_constatado);
				CREATE INDEX rpd_0_produto_$login_admin ON rpd_0_$login_admin(produto);

				SELECT  F.familia   ,
					F.descricao ,
					COUNT(*) AS conta
				FROM    rpd_0_$login_admin X
				JOIN    tbl_produto        P ON P.produto = X.produto
				JOIN    tbl_familia        F ON F.familia = P.familia
				GROUP BY F.familia, F.descricao
				ORDER BY F.descricao;";

			$res = pg_exec($con,$sql);

			if (pg_numrows($res) == 0){
				echo "	<p>	<div class='alert'><h4>".$msgErrorPattern03."</h4></div></p>";
				$showMsg = 01;
				flush();
			}else{
				echo "	<table align='center'class='table table-striped table-bordered table-hover table-large'>";

				### monta linha de nome dos produtos
				echo "	<thead>
						<tr class='titulo_coluna'>
							<th>#</th>";

							for ($i=0; $i<pg_numrows($res); $i++)
							{
								$familia = trim(pg_result($res,$i,familia));

								echo "<th><acronym title='".pg_result($res,$i,descricao)."'>";
								echo pg_result($res,$i,descricao);
								echo "</acronym></th>\n";

								# ------------------------------------ 4 ------------------------------------ #
								if($login_fabrica == 117){
								$sql = "SELECT COUNT(*) AS contaano
									FROM rpd_0_$login_admin X
									JOIN tbl_produto P ON P.produto = X.produto
									JOIN tbl_familia F ON F.familia = P.familia
									WHERE F.familia = $familia
									AND X.mes  = TO_CHAR ('".date('Y-m-01 00:00:00')."'::date,'MM');";
								}else{
									$sql = "SELECT COUNT(*) AS contaano
									      FROM rpd_0_$login_admin X
									      JOIN tbl_produto P ON P.produto = X.produto
									      JOIN tbl_familia F ON F.familia = P.familia
									     WHERE F.familia = $familia
									       AND X.mes     = TO_CHAR ('$data_inicial'::date,'MM');";
								}
								$res2 = pg_exec($con,$sql);
								$contaano[$i] = pg_result($res2,0,0);
							}
					if($login_fabrica == 117){
						echo "<th>Total</th>";
					}
				echo "	</tr>
						</thead>
						<tbody>";

				### MONTA LINHA EM BRANCO, PQ GARANTIA
				echo "	<tr class='table_line' BGCOLOR='#F7F7F7'>
							<td class='menu_top'>PQ Garantia</td>";

							$total_pq_garantia = 0;
							for ($i=0; $i<pg_numrows($res); $i++)
							{

								if($login_fabrica == 117){

									$familia = pg_result($res,$i,'familia');

									$ultimo_dia_mes_final = date("t", mktime(0, 0, 0, $mes_final, "01", $ano_final));

									$sql_pq_garantia = "SELECT SUM(tbl_producao.qtde_venda) AS pq_garantia
														FROM tbl_producao
														JOIN tbl_produto ON tbl_produto.produto = tbl_producao.produto
														JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
														WHERE tbl_produto.familia = $familia
														AND (ano || '-' || mes|| '-01')::date BETWEEN '{$ano_inicial}-{$mes_inicial}-01'::date
														AND '{$ano_final}-{$mes_final}-{$ultimo_dia_mes_final}'::date ;";

									$res_pq_garantia = pg_query($con, $sql_pq_garantia);

									if(pg_num_rows($res_pq_garantia) > 0){
										$pq_garantia = pg_fetch_result($res_pq_garantia, 0, "pq_garantia");
									}

									$pq_qarantia = (strlen($pq_garantia) > 0) ? $pq_garantia : 0;

									$total_pq_garantia += $pq_garantia;

								}else{
									$pq_qarantia = "&nbsp;";
								}

								echo "<td>{$pq_qarantia}</td>";
							}

							if($login_fabrica == 117){
								echo "<td><strong>{$total_pq_garantia}</strong></td>";
							}

				echo "	</tr>";

				### MONTA LINHA COM TOTAL DE OS DO MES
				echo "	<tr class='table_line' bgcolor='#F1F4FA'>
							<td class='menu_top'>Atend. Ano</td>";
							$total_conta = 0;
							for ($i=0; $i<pg_numrows($res); $i++){
								echo "<td align='right' style='padding-right:5px;'>".pg_result($res,$i,conta)."</td>\n";
								$total_conta += pg_result($res,$i,"conta");
							}
							if($login_fabrica == 117){
								echo "<td><strong>{$total_conta}</strong></td>";
							}
				echo "	</tr>";

				### MONTA LINHA COM TOTAL DE OS DO ANO
				echo "	<tr class='table_line' BGCOLOR='#F7F7F7'>
							<td class='menu_top'>Atend. Mês</td>";
							$total_conta_ano = 0;
							for ($i=0; $i<count($contaano); $i++)
							{
								echo "<td align='right' style='padding-right:5px;'>".$contaano[$i]."</td>\n";
								$total_conta_ano += $contaano[$i];
							}
							if($login_fabrica == 117){
								echo "<td><strong>{$total_conta_ano}</strong></td>";
							}
				echo "	</tr>";

				### % NO ANO
				echo "	<tr class='table_line' bgcolor='#F1F4FA'>
							<td class='menu_top'>% No Ano</td>";
							$total_porcen_mes = 0;
							for ($i=0; $i<pg_num_rows($res); $i++){
								@$porc_mes = ($contaano[$i] / $total_conta) * 100;
								$total_porcen_mes += $porc_mes;
								echo "<td align='right' style='padding-right:5px;'>".round($porc_mes,2)."</td>";
							}
							if($login_fabrica == 117){
								echo "<td><strong>{$total_porcen_mes} %</strong></td>";
							}
				echo "	</tr>";

				### % NO MÊS
				echo "	<tr class='table_line'BGCOLOR='#F7F7F7'>
							<td class='menu_top' bgcolor='#F1F4FA'>% No Mês</td>";
							$total_porcen_mes = 0;
							for ($i=0; $i<pg_numrows($res); $i++){
								@$porc_mes = ($contaano[$i] / $total_conta) * 100;
								$total_porcen_mes += $porc_mes;
								echo "<td align='right' style='padding-right:5px;'>".round($porc_mes,2)."</td>";
							}
							if($login_fabrica == 117){
								echo "<td><strong>{$total_porcen_mes} %</strong></td>";
							}
				echo "	</tr>";

				### % MÉDIA
				if($login_fabrica != 117){
				echo "	<tr class='table_line' bgcolor='#F1F4FA'>
							<td class='menu_top'>% Média</td>";
							for ($i=0; $i<pg_numrows($res); $i++){
								echo "<td>&nbsp;</td>\n";
							}
					echo "</tr>";
				}
				echo "</tboby>
				</table>";
				flush();
			}

		}else{
			# -- 4 - Familia > 0  :: Posto > 0  -- #

			if($login_fabrica == 117){

				if(strlen($linha) > 0){
					$join_linha = "
						JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
						JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.linha = {$linha} ";
				}

				if(strlen($join_linha) > 0 && strlen($familia2) > 0){
					$join_linha .= " JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.familia = {$familia2} ";
				}

				if(strlen($join_linha) == 0 && strlen($familia2) > 0){
					$join_linha = "
						JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
						JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.familia = {$familia2} ";
				}

				if(strlen($join_linha) > 0 && strlen($produto) > 0){
					$cond_produto = " AND tbl_produto.produto = {$produto} ";
				}

				if(strlen($join_linha) == 0 && strlen($produto) > 0){
					$join_linha = " JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_produto.produto = {$produto} ";
					$cond_produto = "";
				}

				$cond_os_finalizada_cortesia = " AND tbl_os.finalizada IS NOT NULL AND tbl_os.cortesia IS FALSE ";

			}

			if($login_fabrica == 138) {
				$join_os_produto = "JOIN tbl_os_produto using(os) ";
				$campos = "tbl_os_produto.produto, tbl_os_produto.defeito_constatado";
			}else{
				$campos = "tbl_os.produto,tbl_os.defeito_constatado";
			}

			if($login_fabrica == 163){
				$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
				$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
			}

			$sql = "

				SELECT tbl_os.os,
						to_char(tbl_os.$data_padrao,'MM') as mes,
						tbl_os.posto,
						$campos
				INTO TEMP rpd_0_$login_admin
				FROM tbl_os
				$join_linha
				$join_os_extra
				$join_163
				$join_os_produto
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.$data_padrao BETWEEN '$data_inicial_ano' AND '$data_final'
				$cond_produto
				$cond_os_finalizada_cortesia
				$cond_163
				$cond_os_extra;

				CREATE INDEX rpd_0_os_$login_admin      ON rpd_0_$login_admin(os);
				CREATE INDEX rpd_0_dc_$login_admin      ON rpd_0_$login_admin(defeito_constatado);
				CREATE INDEX rpd_0_produto_$login_admin ON rpd_0_$login_admin(produto);
				CREATE INDEX rpd_0_posto_$login_admin   ON rpd_0_$login_admin(posto);

				SELECT	distinct
					A.posto  ,
					A.nome   ,
					A.estado
				FROM    tbl_posto          A
				JOIN    tbl_posto_fabrica  F ON F.posto   = A.posto AND F.fabrica = $login_fabrica
				JOIN    rpd_0_$login_admin X ON X.posto   = A.posto
				JOIN    tbl_produto        P ON P.produto = X.produto
				WHERE   X.mes    = TO_CHAR ('$data_inicial'::date,'MM')
				AND     P.ativo  IS TRUE
				ORDER BY A.nome;";

			$resX = pg_exec($con,$sql);

			if (pg_numrows($resX) > 0) {

				echo "	<table align='center' class='table table-striped table-bordered table-hover table-large'>
						<thead>
						<tr class='titulo_coluna'>
							<th>Posto</th>
							<th>UF</th>";

							$sql = "SELECT  F.familia  ,
									F.descricao
								FROM    rpd_0_$login_admin X
								JOIN    tbl_produto        P ON P.produto = X.produto
								JOIN    tbl_familia        F ON F.familia = P.familia
								GROUP BY F.familia, F.descricao
								ORDER BY F.descricao;";
							$res = pg_exec($con,$sql);

							for ($i=0; $i<pg_numrows($res); $i++)
							{
								$familias[$i] = trim(pg_result($res,$i,familia));
								echo "<th><acronym title='".pg_result($res,$i,descricao)."'>";
								echo pg_result($res,$i,descricao);
								echo "</acronym></th>";
							}
				echo "	</tr>
						</thead>
						<tbody>";

				//*********************************************************************
				// XLS
				fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='1' class='tabela'>");
				fputs ($fp,"<tr>");
				fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>Posto</td>");
				fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>UF</td>");

				for ($i=0; $i<pg_numrows($res); $i++){
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>".pg_result($res,$i,descricao)."</td>");
				}

				fputs ($fp,"</tr>");
				// XLS
				//*********************************************************************

				for ($k=0; $k<pg_numrows($resX); $k++){

					$cor = ($k%2 == 0) ? '#ffffff' : '#fafafa';

					# ------------------------------------ 2 ------------------------------------ #
					$posto  = pg_result($resX,$k,posto);
					$nome   = pg_result($resX,$k,nome);
					$estado = pg_result($resX,$k,estado);


					//*********************************************************************
					// XLS
					fputs ($fp,"<tr>");
					fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>$nome</td>");
					fputs ($fp,"<td bgcolor='$cor' align='center'>$estado</td>");
					// XLS
					//*********************************************************************

					### MONTA LINHA COM TOTAL DE OS DO Mes
					echo "	<tr class='table_line'>
								<td bgcolor='$cor' nowrap>$nome</td>
								<td bgcolor='$cor'>$estado</td>";
								for($x=0; $x<pg_numrows($res); $x++)
								{
									$sql = "SELECT  COUNT(*) AS conta
										FROM    rpd_0_$login_admin X
										JOIN    tbl_posto          A ON A.posto   = X.posto
										JOIN    tbl_produto        P ON P.produto = X.produto
										JOIN    tbl_familia        F ON F.familia = P.familia
										WHERE   X.mes     = TO_CHAR ('$data_inicial'::date,'MM')
										AND     X.posto   = $posto
										AND     F.familia = $familias[$x]";
									$resMes = pg_exec($con,$sql);
									echo "<td  bgcolor='$cor' align='right' style='padding-right:5px;'>".pg_result($resMes,0,conta)."</td>\n";

									//*********************************************************************
									// XLS
									fputs ($fp,"<td bgcolor='$cor' align='center'>".pg_result($resMes,0,conta)."</td>");
									//*********************************************************************
								}
					echo "</tr>";
					fputs ($fp,"</tr>"); // XLS
				}
				echo "	<tbody>
						</table>";
				flush();
			}
		}
	}

	//////////////////////////////////////
	// xls
	//////////////////////////////////////
	if (strlen($posto) > 0){ // só exibe XLS para relatorios com exibicao dos postos
		fputs ($fp,"</table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);
		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /var/www/assist/www/admin/xls/defeitos_produtos-$data-$login_fabrica.xls /tmp/assist/defeitos_produtos-$data-$login_fabrica.html`;
		echo "	<br />
				<center>
					<div id='gerar_excel' class='btn_excel'>
					<span><img src='http://posvenda.telecontrol.com.br/assist/admin/imagens/excel.png' /></span>
					<a href='xls/defeitos_produtos-$data-$login_fabrica.xls'>
						<span class='txt'>Gerar Arquivo Excel</span>
					</a>
					</div>
				</center>
				<br />";
		echo `rm /tmp/assist/defeitos_produtos-$data-$login_fabrica.html`;
	}

	//////////////////////////////////////
	// xls
	//////////////////////////////////////

	#Cinco maiores defeitos
	if ($showMsg != 1){
		echo "	<br /><br />
		<div class='container'>
				<table class='table table-striped table-bordered table-hover table-large' width='700'>
				<thead>
				<tr class='titulo_coluna'>
					<th align='center' colspan=5>Cinco Maiores Defeitos (Convencionais)</th>
				</tr>
				</thead>
				<tbody>";
				if($login_fabrica == 138) {
					$join = "JOIN tbl_os_produto using(os)  JOIN	tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado";
					$group = " tbl_os_produto.defeito_constatado " ;
				}else{
					$join = "JOIN	tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado";
					$group = "tbl_os.defeito_constatado ";

					if($login_fabrica == 163){
						$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
						$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
					}
				}
				$sql = "SELECT	tbl_defeito_constatado.descricao,
								COUNT(*) AS defeito
						  FROM	tbl_os
						  $join
						  $join_163
						 WHERE	tbl_os.fabrica = $login_fabrica
						   AND	tbl_os.$data_padrao BETWEEN '$data_inicial' AND '$data_final'
						   $cond_163
					  GROUP BY  $group,
								tbl_defeito_constatado.descricao
								ORDER BY  defeito DESC, tbl_defeito_constatado.descricao ASC LIMIT 5";
				$res2 = pg_exec($con,$sql);

		echo "	<tr class='menu_top'>";
				for ($i=0; $i<pg_numrows($res2); $i++){
					echo "<td align='center'>".pg_result($res2,$i,0)."</td>";
				}
		echo "	</tr>
				<tr class='table_line'>";
					for ($i=0; $i<pg_numrows($res2); $i++)
					{
						echo "<td align='center' bgcolor='#F7F5F0'>".pg_result($res2,$i,1)."</td>";
					}
		echo "	</tr>
				</tbody>
				</table>
				<br /><br />";
	}

	#Cinco maiores pecas com defeito
	if ($showMsg != 1)
	{

		$sql = "SELECT	P.descricao,
						COUNT(*) AS qtde
				  FROM 	tbl_peca           P
				  JOIN	tbl_os_item        I   ON I.peca       = P.peca
				  JOIN	tbl_os_produto     O   ON O.os_produto = I.os_produto
				  JOIN  rpd_0_$login_admin X   ON X.os         = O.os
			     WHERE	X.mes = TO_CHAR ('$data_inicial'::date,'MM')
			     AND P.produto_acabado IS NOT TRUE
			  GROUP BY  P.peca, P.descricao
			  ORDER BY  qtde DESC, P.descricao ASC LIMIT 5";
		$res2 = pg_exec($con,$sql);

		if(pg_num_rows($res2) > 0){

			echo "
			<table class='table table-striped table-bordered table-hover table-large' width='700'>
				<thead>
					<tr class='titulo_coluna'>
						<th align='center' colspan=5>Cinco Peças que mais Deram Defeitos</th>
					</tr>
				</thead>
				<tbody>";
			# ------------------------------------ 6 ------------------------------------ #

				echo "<tr class='menu_top'>";
						for ($i=0; $i<pg_numrows($res2); $i++){
							echo "<td align='center' >".pg_result($res2,$i,0)."</td>";
						}
				echo "	</tr>
						<tr class='table_line'>";
						for ($i=0; $i<pg_numrows($res2); $i++){
							echo "<td align='center' bgcolor='#F7F5F0'>".pg_result($res2,$i,1)."</td>";
						}
				echo "</tr>
				</tbody>
			</table>
			<br/> <br/>";

		}

	}

	if($login_fabrica == 117){
        if(strlen($familia2) > 0){
            $join_familia = " AND tbl_familia.familia = $familia2";
        }
        //trocado a data de desquisa de data_abertura para a data_padrão
		// $sql_bi = "SELECT  tbl_familia.descricao,
		// 					bi_os.familia,
		// 					count(*) AS qtde
		// 				FROM bi_os join tbl_familia ON  bi_os.familia = tbl_familia.familia
  		//                         	$join_familia
		//				WHERE bi_os.fabrica = {$login_fabrica}
		//					AND bi_os.$data_padrao BETWEEN '{$data_inicial}' AND '{$data_final}'
		//  					AND bi_os.data_finalizada IS NOT NULL
		//  					AND
		// 				GROUP BY bi_os.familia, tbl_familia.descricao
		// 				ORDER BY tbl_familia.descricao ASC";

		$sql_bi = "SELECT  tbl_familia.familia,
							tbl_familia.descricao,
							count(*) AS qtde
						FROM rpd_0_$login_admin OS
						JOIN tbl_produto ON tbl_produto.produto = OS.produto
						JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
						GROUP BY  tbl_familia.familia,tbl_familia.descricao;";

		$res_bi = pg_query($con, $sql_bi);
		//echo nl2br($sql_bi);
		//echo pg_last_error();

		if(pg_num_rows($res_bi) > 0){

			echo "
				<table class='table table-striped table-bordered table-hover table-large' width='700px'>
					<thead class='titulo_coluna'>
						<tr>
							<th align='center' colspan='2'>BI - Familia x QTDE</th>
						</tr>
						<tr>
							<th>Familia</th>
							<th>Quantidade</th>
						</tr>
					</thead>
					<tbody>";

					for($i = 0; $i < pg_num_rows($res_bi); $i++){

						$familia_desc = pg_fetch_result($res_bi, $i, "descricao");
						$qtde = pg_fetch_result($res_bi, $i, "qtde");

						echo "<tr>";

							echo "<td>$familia_desc</td>";
							echo "<td>$qtde</td>";

						echo "</tr>";

					}

			echo "	</tbody>
				</table>
			";

		}

	}

}
?>
<script type="text/javascript">
$('#linha').multiSelect();
</script>
<?
include "../admin/rodape.php";

?>
