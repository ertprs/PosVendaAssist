<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
/*------------------*/
include 'funcoes.php';
// Opcional
include '../helpdesk/mlg_funciones.php'; //Admin
if (count(array_filter($_POST)) > 0) { //Se recebeu o formulário..
	extract(array_filter($_POST, 'anti_injection')); // cria as variáveis com os campos do formulário

	if (!empty($data_inicial) || !empty($data_final)) {

		if (strlen($msg_erro)==0 and !empty($data_inicial)) {
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi))
			$msg_erro = "Data Inválida!";
		}

		if (strlen($msg_erro)==0 and !empty($data_final)) {
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf))
			$msg_erro = "Data Inválida!";
		}

		if (strlen($msg_erro)==0) {
			if(strtotime("$mf/$df/$yf") < strtotime("$mi/$di/$yi")
			or strtotime("$mf/$df/$yf") > strtotime('today')) {
				$msg_erro = "Data final não pode ser superior a data atual!";
			}
		}

		if(strlen($msg_erro)==0){
			if (pg_fetch_result(pg_query($con, "SELECT '$yi-$mi-$di'::date < '$yf-$mf-$df'::date + INTERVAL '-1 month' "), 0) == 't') {
				$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês.';
			}
		}
		if(strlen($msg_erro)==0){
			$data_inicial = "$yi-$mi-$di 00:00:00";
			$data_final   = "$yf-$mf-$df 23:59:59";
		}
	} else {
		$msg_erro = 'Informe o período para a Consulta';
	}

	if ($login_fabrica ==30) {


		$status_os="";
		$status_os = $_POST['status_os'];
		if (strlen($status_os)==0 && $tipo_data!="geracao") {
			$msg_erro = "Selecione o status da OS";
	    }
	}

/* INI Debug:

p_echo ( "Data inicial $data_inicial, <b>Data final</b>: $data_final");
$msg_erro .= "Data final - 1 mês com SQL:<br><code>SELECT '$yi-$mi-$di'::date < '$yf-$mf-$df'::date + INTERVAL '-1 month'</code>";
if (count($msg_erro) and $login_admin == 1375) echo "<h2>$msg_erro</h2>\n<textarea style='width:693px;height:220px'>".
	 preg_replace(array('/^\s+/', '/\s+,/', '/\s+/'),
				  array('', ', ', ' '), $sql_rel).
	 "</textarea>\n";**/
//exit();
/* FIM Debug */
	if (strlen($msg_erro)==0) { // Gerar o relatório

        if($tipo_data == 'geracao'){

            $where_tipo_data = " AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
            $from_tipo_data  = " FROM tbl_extrato ";
            $join_tipo_data  = "

                                    JOIN tbl_os_extra ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_extrato.fabrica = tbl_os_extra.i_fabrica
                                    JOIN tbl_os ON tbl_os_extra.os = tbl_os.os AND tbl_extrato.fabrica = tbl_os.fabrica
            ";
        }else{
        	if($login_fabrica ==30){

        		$joinPostoFabrica = "";
        		$condStatusOs = "";

				if (($tipo_data=="analitico_defeito") || ($tipo_data=="analitico_defeito_pecas") ) {
					// verifica qual opcao do combo foi selecionada
					if (isset($status_os)) {


				    	if ($status_os == "os_aberto") {
				    		$where_tipo_data = " AND data_abertura BETWEEN '$data_inicial'::date AND '$data_final'::date
				    							 and data_fechamento is null";
				    	}else if ($status_os == "os_fechada") {
								$where_tipo_data = " AND data_fechamento BETWEEN '$data_inicial'::date AND '$data_final'::date
														AND finalizada notnull	";
				    	}
				    }

				}else{
					$where_tipo_data = " AND tbl_os.data_fechamento BETWEEN '$data_inicial' AND '$data_final' ";
				}

					/* $joinPostoFabrica = " JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica
        														 AND tbl_posto_fabrica.posto = tbl_os.posto"; */
        		if($tipo_data=="analitico_defeito_pecas") {
        			$joinDefeitoPecas = " LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
        									LEFT JOIN tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
        									LEFT JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca";
        		}
        	}


            $from_tipo_data  = " FROM tbl_os ";
            $join_tipo_data  = "
                                 JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.i_fabrica = $login_fabrica
                                 LEFT JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato AND tbl_extrato.fabrica = tbl_os.fabrica

            ";
        }

       // $sql_adicional = $login_fabrica == 30 ? "tbl_os.defeito_reclamado_descricao AS DEFEITO," : '';

		$sql_rel = "
			SELECT
				tbl_familia.descricao                                     AS LINHA_PRODUTO,
				tbl_esmaltec_categoria_produto.segmento                   AS SEGMENTO,
				tbl_extrato.data_geracao::date                            AS DAT_GERACAO_EXTRATO,
				tbl_os.sua_os                                             AS ORDEM_SERVICO,
				tbl_esmaltec_categoria_produto.linha_txt                  AS LINHA,
				tbl_os.data_abertura                                      AS DAT_ORDEM_SERVICO,
				tbl_os.data_fechamento                                    AS DAT_FECHAMENTO,
				tbl_os.data_fechamento - tbl_os.data_abertura             AS TEMPO_ATENDIMENTO,
				tbl_os.data_nf                                            AS DAT_COMPRA,
				SUBSTR(tbl_esmaltec_item_servico.codigo, 9, 2)            AS LST_GARANTIA,
			" ;

		if ($login_fabrica == 30) {
			$sql_rel .= " tbl_os.defeito_reclamado_descricao AS DEFEITO_RECLAMADO,
						date(tbl_os.data_conserto) as data_conserto, ";

			// campos para analitico_defeito_pecas
			if($tipo_data=="analitico_defeito_pecas"){
				$sql_rel .= "tbl_peca.referencia as referencia_peca,
							 tbl_peca.descricao as descricao_peca,
							 tbl_os_item.preco as preco_peca, ";
			}

		}

		if($login_fabrica == 30){
			$campos_esmaltec = " (select cnpj from tbl_fornecedor join tbl_fornecedor_fabrica using(fornecedor) where tbl_fornecedor_fabrica.fabrica = 30 and tbl_fornecedor.fornecedor = ((tbl_os_defeito_reclamado_constatado.campos_adicionais )::json->>'cor_etiqueta_fornecedor')::int ) 
				else ' ' end as fornecedor, ";
		}

		$sql_rel .= "
		RPAD(SUBSTR(tbl_defeito_constatado.codigo, 1, 2), 6, '0') AS COD_DEF_PRODUTO,
		(SELECT descricao FROM tbl_defeito_constatado AS tbl_defeito_constatado_interno WHERE fabrica=$login_fabrica AND codigo=RPAD(SUBSTR(tbl_defeito_constatado.codigo, 1, 2), 6, '0')) AS DES_DEF_PRODUTO,
		RPAD(SUBSTR(tbl_defeito_constatado.codigo, 1, 4), 6, '0') AS COD_GRUP_DEFEITO,
		(SELECT descricao FROM tbl_defeito_constatado AS tbl_defeito_constatado_interno WHERE fabrica=$login_fabrica AND codigo=RPAD(SUBSTR(tbl_defeito_constatado.codigo, 1, 4), 6, '0') LIMIT 1) AS DES_GRUP_DEFEITO,
		tbl_defeito_constatado.codigo    AS COD_DEFEITO,
		tbl_defeito_constatado.descricao AS DES_DEFEITO,
		case when tbl_os_defeito_reclamado_constatado.campos_adicionais->>'cor_etiqueta_fornecedor' NOTNULL AND tbl_os_defeito_reclamado_constatado.campos_adicionais->>'cor_etiqueta_fornecedor' NOT IN ('null', 'Selecione') then 
	
		$campos_esmaltec 

		"
		.
		(($login_fabrica == 30) ? "tbl_os.tecnico_nome AS TECNICO," : '')
		.
		"
		tbl_os.pecas                     AS valor_pecas,

		CASE WHEN tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_os.defeito_constatado
			 THEN tbl_os.mao_de_obra
             ELSE 0
		 END AS mao_de_obra,
		CASE WHEN tbl_os.qtde_km_calculada IS NULL
			 THEN 0
             ELSE tbl_os.qtde_km_calculada
		 END as valor_do_km,
		CASE WHEN tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_os.defeito_constatado
			 THEN tbl_os.mao_de_obra + coalesce(tbl_os.qtde_km_calculada,0) + tbl_os.pecas
             ELSE coalesce(tbl_os.qtde_km_calculada,0) + tbl_os.pecas
		END AS valor_da_os,
		CASE WHEN tbl_os.certificado_garantia IS NULL
			 THEN '0'
			 ELSE tbl_os.certificado_garantia
		END AS LGI,
		tbl_produto.referencia AS COD_ITEM,
		tbl_produto.descricao  AS DESC_ITEM,
		tbl_os.serie           AS NUM_SERIE_ITEM,
		(SUBSTR(tbl_os.serie, 5, 2) || '/' || SUBSTR(tbl_os.serie, 3, 2) || '/' || SUBSTR(tbl_os.serie, 1, 2)) AS DATA_FABRICACAO,
		tbl_os.consumidor_revenda,
		tbl_os.consumidor_nome,";
		if ($login_fabrica == 30) {
			$sql_rel .= "tbl_os.consumidor_cidade, tbl_os.consumidor_estado, ";
		}
		$sql_rel .= "tbl_os.revenda_cnpj,
		tbl_os.revenda_nome, ";
		if($login_fabrica == 30 and (($tipo_data=="analitico_defeito") || ($tipo_data=="analitico_defeito_pecas"))){
			$sql_rel .= " tbl_posto_fabrica.codigo_posto, ";
		}
		$sql_rel .= "UPPER(tbl_posto.nome)   AS POSTO_AUTORIZADO,
		UPPER(tbl_posto_fabrica.contato_cidade) AS CIDADE,
		UPPER(tbl_posto_fabrica.contato_estado) AS UF

		$from_tipo_data
	    $join_tipo_data
		JOIN tbl_produto  ON tbl_os.produto      = tbl_produto.produto AND tbl_produto.fabrica_i=tbl_os.fabrica
		LEFT JOIN tbl_esmaltec_categoria_produto ON tbl_produto.produto = tbl_esmaltec_categoria_produto.produto
		JOIN tbl_familia  ON tbl_produto.familia = tbl_familia.familia
		JOIN tbl_posto    ON tbl_os.posto        = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica ";
		if ($login_fabrica==30) {

			$sql_rel .= "LEFT JOIN tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os "; 

			if (($tipo_data=="analitico_defeito") || ($tipo_data=="analitico_defeito_pecas") ) {
				$sql_rel .= $joinPostoFabrica;

				if($tipo_data=="analitico_defeito_pecas") {
					$sql_rel .= $joinDefeitoPecas;
				}
			}
		}

		$sql_rel .= " JOIN tbl_os_defeito_reclamado_constatado ON tbl_os.os                                              = tbl_os_defeito_reclamado_constatado.os
		JOIN tbl_defeito_constatado              ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado
		JOIN tbl_esmaltec_item_servico           ON tbl_defeito_constatado.esmaltec_item_servico           = tbl_esmaltec_item_servico.esmaltec_item_servico

		WHERE tbl_os.fabrica =  $login_fabrica
		AND tbl_os.posto        <> 6359
		AND tbl_os.excluida IS NOT TRUE
		  $where_tipo_data
		  ORDER BY ORDEM_SERVICO, mao_de_obra desc, valor_pecas desc
		";


/* INI Debug:

p_echo ( "Data inicial $data_inicial, Data final: $data_final");
//if (count($msg_erro) and $login_admin == 1375)
	echo "<h2>$msg_erro</h2>\n<textarea style='width:693px;height:220px'>".
	 preg_replace(array('/^\s+/', '/\s+,/', '/\s+/'),
				  array('', ', ', ' '), $sql_rel).
	 "</textarea>\n";
exit();

/* FIM Debug */
		// HD-959196
		if($login_fabrica == 30) {
			$formato_arquivo = 'xls';
		}

		$res = pg_query($con, $sql_rel);

		if (is_resource($res)) {
			if ($formato_arquivo == 'xls') {
				define('XLS_FMT', TRUE);
			} else {
				define('XLS_FMT', FALSE);
			}

			if (pg_num_rows($res) > 0) { //Tem resultados...
				$hoje = date('Y-m-d');
				$total= pg_num_rows($res);
				$nomeFile = "";
				if (XLS_FMT) {

					$downloadFile    = "xls/dados_atualizados_postos_$hoje.xls";
					$tmpFile 		 = "/tmp/dados_atualizados_postos_$hoje.xls";

					$file = fopen($tmpFile,'w');
					// header('Content-type: application/msexcel');
					// header("Content-Disposition: attachment; filename=dados_atualizados_postos_$hoje.xls");
				} else {

					$downloadFile    = "xls/dados_atualizados_postos_$hoje.csv";
					$tmpFile 		 = "/tmp/dados_atualizados_postos_$hoje.csv";

					$file = fopen($tmpFile,'w');
					// header('Content-type: text/csv');
					// header("Content-Disposition: attachment; filename=dados_atualizados_postos_$hoje.csv");
				}

				$row = pg_fetch_assoc($res, 0);
				$campos = array_keys($row);

				$old_os = '';

				$csv_campos[] = "<table>";


				foreach($campos as $campo) {
					$campo = str_replace('_', ' ', $campo); //------ ERRADO - HD-959196
					$xls_header  .= "<th>" . ucwords($campo) . "</th>";
					$csv_campos[] = $campo;
				}

				$csv_campos[] = "</table>";

				if (XLS_FMT) {  // Monta o cabeçalho com os nomes dos campos, XLS-fake ou CSV
					fwrite($file, "<table style='font: 14px \"Arial\";;'><thead><tr>$xls_header</tr></thead><tbody>");
					// echo "<table style='font: 14px \"Arial\";;'><thead><tr>$xls_header</tr></thead><tbody>";
				} else {
					fwrite($file, implode(";", $csv_campos));
					// echo implode(";", $csv_campos); //CSV
				}

				fwrite($file, "\n");
				//echo "\n";

				// quando for analitico_defeito_pecas e tiver mais de um codigo de defeito, irá duplicar os registros
				//$print_primeiro serve como uma variavel de controle;
				if($login_fabrica == 30){
					$print_primeiro = false;
				}

				for ($i=0; $i < $total; $i++) {
					if($i % 2 == 0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";

		        	$row = pg_fetch_assoc($res, $i);

		        	if ($login_fabrica == "30") {


		        		if ($old_os == $row['ordem_servico']) {
			        		$row['valor_pecas'] = '0';
			        		$row['valor_da_os'] = '0';
			        		$row['mao_de_obra'] = '0';
			        		$row['valor_do_km'] = '0';
			        	}else{
			        		$print_primeiro = false;
			        	}

			        	if(!$print_primeiro){
				        	if(($old_os == $row['ordem_servico']) && ($row['cod_defeito'] != $old_cod_defeito))	{
				        		$print_primeiro = true;
				        		$row['preco_peca']=0;
				        	}
			        	} else {
			        		if(($old_os == $row['ordem_servico']) && ($row['cod_defeito'] == $old_cod_defeito))	{
				        		$row['preco_peca']=0;
				        	}
			        	}

			        	$old_cod_defeito = $row['cod_defeito'];
			        	$old_os = $row['ordem_servico'];

		        	}

					$xls_linha = "\t\t<tr bgcolor='$cor'>\n";
					unset($csv_linha); //array



					foreach($row as $key => $campo) {

						$campo = str_replace("\t", ' ', $campo); //Retira a tabulação
						if ($formato != 'xls') $campo = str_replace("\n", '|', $campo); //Retira a quebra de linha, substinui ela por '|'

						//Formata alguns campos antes de imprimir
						if (stripos($key, 'cpf') !== false or stripos($key, 'cnpj') !== false) {
							$campo = (strlen($campo) == 14) ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', "$1.$2.$3/$4-$5", $campo) : // CNPJ
															  preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', "$1.$2.$3-$4", $campo); // CPF, vai que um dia precisa...
						}
						if (in_array($key, array('valor_pecas','mao_de_obra','valor_do_km','valor_da_os'))) { // Formata valores de moeda
							$campo =number_format($campo, 2, ',', '.');
						}
						if (stripos($key, 'num_serie_item') !== false AND XLS_FMT) {
							$campo = "&nbsp;" . $campo;
						}

						// Adiciona o campo
						$xls_linha  .= "\t\t\t<td>$campo</td>\n";
						$csv_linha[] = (preg_match('/(\s|\n|\r|;)/', $campo) or //Entre aspas se tiver aglum tipo de espaço ou dígito grande, tipo nº série
										in_array($key, array('referencia','nota_fiscal','serie','peca_referencia'))) ? "\"$campo\"" : $campo;

					}
					// echo (XLS_FMT) ? ($login_fabrica == 30 ? $xls_linha : $linha) . "\t\t</tr>" : implode(";", $csv_linha);
					// echo "\n";
					$writeLinha = (XLS_FMT) ? ($login_fabrica == 30 ? $xls_linha : $linha) . "\t\t</tr>" : implode(";", $csv_linha);
					fwrite($file, $writeLinha."\n");

				}
				if (XLS_FMT){
					// echo "\t</tbody>\n</table>";
					fwrite($file, "\t</tbody>\n</table>");
				}

				fclose($file);

				if(file_exists($tmpFile)){
					if(file_exists($downloadFile)){
						system("rm $downloadFile");
					}

					system("mv $tmpFile $downloadFile");

				}else{
					echo "{\"erro\":\"true\", \"msg\":\"Erro ao gerar o arquivo\"}";
					exit;
				}

				if(file_exists($downloadFile)){
					echo "{\"erro\":\"false\", \"msg\":\"$downloadFile\"}";
				}else{
					echo "{\"erro\":\"true\", \"msg\":\"Arquivo não Encontrado.\"}";
				}
				exit; // FIM do arquivo 'Excel'
			} else {
				echo "{\"erro\":\"true\", \"msg\":\"Sem dados para o período selecionado.\"}";
			}
		} else { // Não deu erro no banco...
			echo "{\"erro\":\"true\", \"msg\":\"Erro ao recuperar os dados\"}";
		}
	}else{
		$msg_erro = utf8_decode($msg_erro);
		echo "{\"erro\":\"true\", \"msg\":\"$msg_erro\"}";
	}
}
?>
