<?php

try 
{
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	$fabrica        = 20;
	$vet['fabrica'] = 'bosch';
	$vet['tipo']    = 'posto';
	$vet['log']     = 2;

	list ($caminho, $id_posto, $cred_posto, $cadastro) = $argv;

	$origem = "/tmp/bosch";

	$sql  = "SELECT TO_CHAR(CURRENT_DATE - INTERVAL '1 MONTH','YYYY-MM-01')";
    $res  = pg_query($con, $sql);
    $data = pg_fetch_result($res, 0, 0);

    if (strlen($id_posto) > 0) {
		$cond_posto = " AND tbl_posto_fabrica.posto = {$id_posto} ";
	}

	if ($cred_posto == 'C' || $cred_posto == 'D') {
		$tipo	  = ($cred_posto == 'C') ? 'CREDENCIADO' : 'DESCREDENCIADO';
		$file	  = array("atualizacao-excel_$tipo.xls");
		$pesquisa = array("$tipo");
	} else {
		$file     = array("atualizacao-excel_credenciado.xls", "atualizacao-excel_descredenciado.xls");
		$pesquisa = array("CREDENCIADO", "DESCREDENCIADO");
	}

	for ($i = 0; $i < count($file); $i++) { 
		$arquivo = "{$origem}/{$file[$i]}";

		if (file_exists($arquivo)) {
			system("rm -rf {$arquivo}");
		}

		$sql = "SELECT posto AS id
				FROM tbl_posto_fabrica
			 	WHERE credenciamento = '{$pesquisa[$i]}'
			 	AND tbl_posto_fabrica.fabrica = {$fabrica}
			 	{$cond_posto}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$fp = fopen("{$arquivo}", "a+");

			fwrite($fp, "
				<table border='1'>
					<thead>
						<tr>
							<td align='center'>0COUNTRY</td>
					        <td align='center'>EW03BRAND</td>
					        <td align='center'>EW03SC</td>
					        <td align='center'>0DATETO</td>
					        <td align='center'>0DATEFRM</td>
					        <td align='center'>NavServiceCenter</td>
					        <td align='center'>EW03REIMB</td>
					        <td align='center'>EW03WRNTY</td>
					        <td align='center'>EWRESP</td>
					        <td align='center'>EWEMAIL</td>
					        <td align='center'>0PHONE</td>
					        <td align='center'>EW03LC_SC</td>
					        <td align='center'>EW03FC_SC</td>
					        <td align='center'>0CURRENCY</td>
					        <td align='center'>EW03PDSC</td>
					        <td align='center'>EW03SPDSC</td>
					        <td align='center'>EW03ADSC</td>
					        <td align='center'>EW03FFF</td>
					        <td align='center'>EW03SO</td>
					        <td align='center'>0COMP_CODE </td>
					        <td align='center'>EWDATEFRM</td>
					        <td align='center'>EWDATETO</td>
					        <td align='center'>0CH_ON</td>
					        <td align='center'>0CHANGEDBY</td>
					        <td align='center'>IdServiceCenter</td>
						</tr>
					</thead>
					<tbody>
			");

			$x = 0;

			while ($posto = pg_fetch_object($res)) {
				//echo $posto->id." - ".$pesquisa[$i]."<br />";exit;

				if (strlen($id_posto) > 0) {
					$where = " WHERE tbl_posto.posto = {$posto->id} AND tbl_posto_fabrica.fabrica = {$fabrica} ";
				} else {
					$where = "WHERE tbl_credenciamento.fabrica = {$fabrica}
                           	  AND tbl_credenciamento.status = '{$pesquisa[$i]}'
                           	  AND tbl_credenciamento.fabrica = tbl_posto_fabrica.fabrica
                           	  AND tbl_credenciamento.posto = {$posto->id}
                           	  /*AND tbl_credenciamento.data BETWEEN '{$data}'::timestamp AND '{$data}'::timestamp + interval '1 month' - interval '1 s'*/
                         	  ORDER BY tbl_credenciamento.credenciamento ASC 
                         	  LIMIT 1";
				}

				$Xsql = "SELECT 
							CASE WHEN tbl_credenciamento.data IS NOT NULL 
							THEN
                                TO_CHAR(tbl_credenciamento.data, 'YYYYMMDD')
                            ELSE 
                            	'20070101' 
                           	END AS primeiro_acesso,
                            codigo_posto,
                            desconto,
                            desconto_acessorio,
                            CASE WHEN tbl_posto_fabrica.data_alteracao IS NOT NULL 
                            THEN
                            	TO_CHAR(tbl_posto_fabrica.data_alteracao, 'YYYYMMDD')
                            ELSE 
                            	'20080401' 
                            END AS data_alteracao,
                            UPPER(tbl_posto.pais) AS pais,
                            unidade_trabalho
                        FROM tbl_posto_fabrica
                        JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                        JOIN tbl_pais ON tbl_pais.pais = tbl_posto.pais
                        LEFT JOIN tbl_credenciamento ON tbl_credenciamento.posto = tbl_posto_fabrica.posto
                        {$where}";

                $Xres = pg_query($con, $Xsql);

                if (pg_num_rows($Xres) > 0) {
                	$x++;

                	$result = pg_fetch_array($Xres);
                	$primeiro_acesso = $result["primeiro_acesso"];

                	$ut = $result["unidade_trabalho"] * 10;

			$linha = array(1 => "BO", 2 => "SK", 3 => "DR", 4 => "SV", 5 => "PL");

                	$col_U = ($cadastro == "novo") ? $primeiro_acesso : "";

                	if (!strlen($result["desconto"])) {
						$result["desconto"] = 0;
					}

					if (!strlen($result["desconto_acessorio"])) {
						$result["desconto_acessorio"] = 0;
					}

					$garantia = array(1 => 12, 2 => 12, 3 => 12, 4 => 12, 5 => 12);

					if ($result["pais"] == "PY") {
						$garantia[1] = 6;
						$garantia[4] = 6;
					}

					if ($result["pais"] == "CR") {
						$garantia[2] = 24;
					} else {
						if (in_array($result["pais"], array("HN", "PY", "DO"))) {
							$garantia[2] = 6;
						}
					}

					if (in_array($result["pais"], array("BR", "AR", "MX","SK","DR"))) {
						$garantia[3] = 24;
					}

					if ($result["pais"] == "CO") {
						$garantia[3] = 36;
					}

					if ($result["pais"] == "HN") {
						$garantia[3] = 6;
					}

					if (in_array($result["pais"], array("PA", "DO", "PY"))) {
						$garantia[3] = 0;
					}

					$codigo_moeda = ($result["pais"] == "BR") ? "BRL" : "EUR";

					fwrite($fp, "
						<tr>
							<td>{$result['pais']}</td>
							<td>{$linha[1]}</td>
							<td align='right'>{$result['codigo_posto']}</td>
							<td>99991231</td>
							<td>{$result['primeiro_acesso']}</td>
							<td align='right'>{$result['codigo_posto']}</td>
							<td align='right'>Y</td>
							<td>{$garantia[1]}</td>
							<td>Casado,Ricardo</td>
							<td>ricardo.casado@br.bosch.com</td>
							<td>55 19 2103 1450</td>
							<td align='right'>{$ut}</td>
							<td>0</td>
							<td align='right'>{$codigo_moeda}</td>
							<td>0</td>
							<td>{$result['desconto']}</td>
							<td>{$result['desconto_acessorio']}</td>
							<td>0</td>
							<td>{$result['pais']}</td>
							<td>9080</td>
							<td>{$col_U}</td>
							<td>99991231</td>
							<td>{$result['data_alteracao']}</td>
							<td align='center'>G21</td>
							<td align='center'>EOL</td>
						</tr>
						<tr>
							<td>{$result['pais']}</td>
							<td>{$linha[2]}</td>
							<td align='right'>{$result['codigo_posto']}</td>
							<td>99991231</td>
							<td>{$result['primeiro_acesso']}</td>
							<td align='right'>{$result['codigo_posto']}</td>
							<td align='right'>Y</td>
							<td>{$garantia[2]}</td>
							<td>Casado,Ricardo</td>
							<td>ricardo.casado@br.bosch.com</td>
							<td>55 19 2103 1450</td>
							<td align='right'>{$ut}</td>
							<td>0</td>
							<td align='right'>{$codigo_moeda}</td>
							<td>0</td>
							<td>{$result['desconto']}</td>
							<td>{$result['desconto_acessorio']}</td>
							<td>0</td>
							<td>{$result['pais']}</td>
							<td>9080</td>
							<td>{$col_U}</td>
							<td>99991231</td>
							<td>{$result['data_alteracao']}</td>
							<td align='center'>G21</td>
							<td align='center'>EOL</td>
						</tr>
						<tr>
							<td>{$result['pais']}</td>
							<td>{$linha[3]}</td>
							<td align='right'>{$result['codigo_posto']}</td>
							<td>99991231</td>
							<td>{$result['primeiro_acesso']}</td>
							<td align='right'>{$result['codigo_posto']}</td>
							<td align='right'>Y</td>
							<td>{$garantia[3]}</td>
							<td>Casado,Ricardo</td>
							<td>ricardo.casado@br.bosch.com</td>
							<td>55 19 2103 1450</td>
							<td align='right'>{$ut}</td>
							<td>0</td>
							<td align='right'>{$codigo_moeda}</td>
							<td>0</td>
							<td>{$result['desconto']}</td>
							<td>{$result['desconto_acessorio']}</td>
							<td>0</td>
							<td>{$result['pais']}</td>
							<td>9080</td>
							<td>{$col_U}</td>
							<td>99991231</td>
							<td>{$result['data_alteracao']}</td>
							<td align='center'>G21</td>
							<td align='center'>EOL</td>
						</tr>
						<tr>
							<td>{$result['pais']}</td>
							<td>{$linha[4]}</td>
							<td align='right'>{$result['codigo_posto']}</td>
							<td>99991231</td>
							<td>{$result['primeiro_acesso']}</td>
							<td align='right'>{$result['codigo_posto']}</td>
							<td align='right'>Y</td>
							<td>{$garantia[4]}</td>
							<td>Casado,Ricardo</td>
							<td>ricardo.casado@br.bosch.com</td>
							<td>55 19 2103 1450</td>
							<td align='right'>{$ut}</td>
							<td>0</td>
							<td align='right'>{$codigo_moeda}</td>
							<td>0</td>
							<td>{$result['desconto']}</td>
							<td>{$result['desconto_acessorio']}</td>
							<td>0</td>
							<td>{$result['pais']}</td>
							<td>9080</td>
							<td>{$col_U}</td>
							<td>99991231</td>
							<td>{$result['data_alteracao']}</td>
							<td align='center'>G21</td>
							<td align='center'>EOL</td>
						</tr>
						<tr>
							<td>{$result['pais']}</td>
							<td>{$linha[5]}</td>
							<td align='right'>{$result['codigo_posto']}</td>
							<td>99991231</td>
							<td>{$result['primeiro_acesso']}</td>
							<td align='right'>{$result['codigo_posto']}</td>
							<td align='right'>Y</td>
							<td>{$garantia[5]}</td>
							<td>Casado,Ricardo</td>
							<td>ricardo.casado@br.bosch.com</td>
							<td>55 19 2103 1450</td>
							<td align='right'>{$ut}</td>
							<td>0</td>
							<td align='right'>{$codigo_moeda}</td>
							<td>0</td>
							<td>{$result['desconto']}</td>
							<td>{$result['desconto_acessorio']}</td>
							<td>0</td>
							<td>{$result['pais']}</td>
							<td>9080</td>
							<td>{$col_U}</td>
							<td>99991231</td>
							<td>{$result['data_alteracao']}</td>
							<td align='center'>G21</td>
							<td align='center'>EOL</td>
						</tr>
					");
                }
			}

			if ($x == 0) {
				fwrite($fp, "
            		<tr>
            			<td colspan='25'>Nenhum posto encontrado</td>
            		</tr>
            	");
			}

			fwrite($fp, "
					</tbody>
				</table>
			");

			fclose($fp);

			if (strlen($id_posto) > 0) {
				if (pg_num_rows($Xres) > 0 && file_exists($arquivo)) {

					$hoje = date("Y-m-d");
					$nome_link = "atualizacao_posto_".strtolower($tipo)."_{$hoje}_{$id_posto}.xls";
					
					// system("cp $arquivo /var/www/assist/www/admin/xls/{$nome_link}");
					
					system("cp $arquivo /www/assist/www/admin/xls/$nome_link", $ret);

					//echo $ret;

					echo $nome_link;

					#exit;
				}

			}
		}
	}

	if (file_exists("{$origem}/{$file[0]}") || file_exists("{$origem}/{$file[1]}")) {
		$fileAtt  = "{$origem}/";
		$fileType = "application/vnd.ms_excel";

		$mailTo      = "ricardo.casado@br.bosch.com,suporte.fabricantes@telecontrol.com.br";
		$mailSubject = "ATUALIZAÇÃO DOS POSTOS EM EXCEL";
		$semiRand     = md5(time());
		$mimeBoundary = "==Multipart_Boundary_x{$semiRand}x";

		$headers .= "From: helpdesk@telecontrol.com.br";
		$headers .= "\nMIME-Version: 1.0\n" .
					"Content-Type: multipart/mixed;\n" .
					" boundary=\"{$mimeBoundary}\"";

		$message = "This is a multi-part message in MIME format.\n\n" .
				   "--{$mimeBoundary}\n" .
				   "Content-Type: text/html; charset=\"iso-8859-1\"\n" .
				   "Content-Transfer-Encoding: 7bit\n\n" .
				   $message . "\n\n
				   Segue anexo o arquivo em formato EXCEL referente as atualizações dos postos.\n";
		if($cadastro =='novo') {
				$mailSubject = "New LAM Service Center";
				$message = "This is a multi-part message in MIME format.\n\n" .
				   "--{$mimeBoundary}\n" .
				   "Content-Type: text/html; charset=\"iso-8859-1\"\n" .
				   "Content-Transfer-Encoding: 7bit\n\n" .
				   "Dear Colleague,<br>\n\n
                    A new Service Center was created. Please include it in your system. The warranty claim file will be sent in the next interface<br>\n

Thanks in advance.\n";

		}

		foreach ($file as $key => $value) {
			$file = $fileAtt.$value;

			if (file_exists($file) && filesize($file) > 0) {
				$fopen = fopen($file, "rb");
				$fdata = fread($fopen, filesize($file));
				fclose($fopen);

				$fdata = chunk_split(base64_encode($fdata));

				$message .= "--{$mimeBoundary}\n" .
							"Content-Type: {$fileType};\n" .
							" name=\"{$value}\"\n" .
							"Content-Disposition: attachment;\n" .
							" filename=\"{$value}\"\n" .
							"Content-Transfer-Encoding: base64\n\n" .
							$fdata . "\n\n";
			}
		}
		
		$message .= "--{$mimeBoundary}--\n";
		mail($mailTo, $mailSubject, $message, $headers);
	}
}
catch (Exception $e)
{

	echo $e->getMessage();
}
