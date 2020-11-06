<?php

define('ENV', 'testes');

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include_once '../../class/communicator.class.php';
	$mailer = new TcComm("noreply@tc");

	$tipo 			= "Comunicado";	
	$pais 			= "BR";
	$login_fabrica 	= 91;

	$estilo = "<style>table.tab_cabeca{border:1px solid #3e83c9;font-family:Verdana;font-size:13px;font-weight:bold;}table.relatorio {font-family: Verdana;font-size:11px;border-collapse:collapse;width:720px;font-size:1.1em;border-left:1px solid #8BA4EB;border-right:1px solid #8BA4EB;}table.relatorio th {font-family:Verdana;font-size:11px;background:#3e83c9;color:#fff;font-weight:bold;padding:2px 2px;text-align: left;border-right:1px solid #fff;line-height:1.2;padding-top:5px;padding-bottom:5px;}table.relatorio td {font-family:Verdana;font-size: 11px;padding:1px 5px 5px 5px;border-bottom:1px solid #95bce2;line-height:15px;}</style>";

	$cabecalho_tabela = "<table name=relatorio id=relatorio class=relatorio><thead><tr bgcolor=#3e83c9><td colspan=3 style=text-align:center;color:#ffd700;font-size:13px;font-weight:bold;>Perigo de PROCON conforme artigo 18 do C.D.C.</td></tr><tr bgcolor=#3e83c9><th style=text-align:center;>OS</th><th style=text-align:center>Abertura</th><th style=text-align:center;>Produto</th></tr></thead><tbody>";

	// OS aberta 25 dias ou mais
	$sql = "SELECT tbl_os.os,
				tbl_os.sua_os,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YY') AS abertura,
				tbl_os.data_abertura,
				tbl_produto.referencia,
				tbl_produto.descricao,
				tbl_os.posto
				INTO TEMP tmp_os_25_dias
				FROM tbl_os
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '25 days'
				AND tbl_os.data_fechamento IS NULL
				AND tbl_os.excluida IS NOT TRUE;";


	

	//die(nl2br($sql));

	$res = pg_query($con, $sql);

	$sql_tmp = "SELECT DISTINCT posto FROM tmp_os_25_dias";

	$res_tmp = pg_query($con, $sql_tmp);

	$contador_res = pg_num_rows($res_tmp);
	
	if($contador_res > 0){
		for($x=0; $x<$contador_res; $x++){
			$posto 	= pg_fetch_result($res_tmp, $x, 'posto');	
			$sql_posto = "SELECT contato_email FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
			$res_posto = pg_query($con, $sql_posto);
			$contato_email = pg_fetch_result($res_posto, 'contato_email');

				// OS aberta entre 25 e 30 dias
			$sql_25_30 = "SELECT os,
							sua_os,
							abertura,
							referencia,
							descricao
							FROM tmp_os_25_dias
							WHERE data_abertura BETWEEN CURRENT_DATE - INTERVAL '30 days' and CURRENT_DATE - INTERVAL '25 days'
							AND posto = {$posto}";

			//die(nl2br($sql_25_30));
			$res_25_30 = pg_query($con, $sql_25_30);
			$contador_25_30 = pg_num_rows($res_25_30);

			if($contador_25_30 > 0){			
				$msg_aviso = "OS's ABERTAS A MAIS DE 25 DIAS<br/>Favor finalizar as OS\'s com abertura acima de 25 dias que j&aacute; conclu&iacute;ram o conserto. Caso n&atilde;o tenha recebido as pe&ccedil;as para que o conserto seja conclu&iacute;do e a OS finalizada, favor contatar a f&aacute;brica.";
			
				for ($i=0; $i < $contador_25_30; $i++) { 
					$os_i 			= pg_fetch_result($res_25_30, $i, 'os');
					$aberura_i 		= pg_fetch_result($res_25_30, $i, 'abertura');
					$referencia_i 	= pg_fetch_result($res_25_30, $i, 'referencia');
					$descricao_i 	= pg_fetch_result($res_25_30, $i, 'descricao');

					$linha_i  .= "<tr><td style=text-align:center;>" . $os_i . "</td><td style=text-align:center;>" . $aberura_i . "</td><td>" . $referencia_i . " - " . $descricao_i . "</td></tr>";				
				}

				$rodape_tabela = "</tbody></table>";

				$tabela = $estilo . $msg_aviso . $cabecalho_tabela . $linha_i . $rodape_tabela;	

				$sql_aviso = "INSERT INTO tbl_comunicado (mensagem, posto, fabrica, tipo, pais, ativo, obrigatorio_site) VALUES (E'{$tabela}', {$posto}, {$login_fabrica}, '{$tipo}', '{$pais}', 't', 't')";
				
				$res_aviso = pg_query($con, $sql_aviso);

				$assunto = "OS's ABERTAS A MAIS DE 25 DIAS";
				$res = $mailer->sendMail(
					$contato_email,
					$assunto,
					utf8_encode($tabela),
					'noreply@tc.id'
				);
				$linha_i = '';
			}

			// OS aberta acima de 30 dias
			$sql_30 = "SELECT os,
						sua_os,
						abertura,
						referencia,
						descricao
						FROM tmp_os_25_dias
						WHERE data_abertura <= CURRENT_DATE - INTERVAL '31 days'
						AND posto = {$posto}";

			$res_30 = pg_query($con, $sql_30);
			$contador_30 = pg_num_rows($res_30);

			if($contador_30 > 0){
				$msg_aviso = "OS's ABERTA ACIMA DO PRAZO LEGAL COM RISCO DE PROCON.<br>AS MESMAS SER&Atilde;O FINALIZADAS SEM RESSARCIMENTO. FAVOR EFETUAR O REPARO COM ABERTURA E FINALIZA&Ccedil;&Atilde;O DE SUAS OSs DENTRO DE 30 DIAS.";
				
				for ($i=0; $i < $contador_30; $i++) { 
					$os_i 			= pg_fetch_result($res_30, $i, 'os');
					$aberura_i 		= pg_fetch_result($res_30, $i, 'abertura');
					$referencia_i 	= pg_fetch_result($res_30, $i, 'referencia');
					$descricao_i 	= pg_fetch_result($res_30, $i, 'descricao');

					$linha_i  .= "<tr><td style=text-align:center;>" . $os_i . "</td><td style=text-align:center;>" . $aberura_i . "</td><td>" . $referencia_i . " - " . $descricao_i . "</td></tr>";				
				}
			
				$rodape_tabela = "</tbody></table>";

				$tabela = $estilo . $msg_aviso . $cabecalho_tabela . $linha_i . $rodape_tabela;	

				$sql_aviso = "INSERT INTO tbl_comunicado (mensagem, posto, fabrica, tipo, pais, ativo, obrigatorio_site) VALUES ('{$tabela}', {$posto}, {$login_fabrica}, '{$tipo}', '{$pais}', 't', 't')";

				//die(nl2br($sql_aviso));

				$res_aviso = pg_query($con, $sql_aviso);

				$assunto = "OS's ABERTAS A MAIS DE 25 DIAS";
				$res = $mailer->sendMail(
					$contato_email,
					$assunto,
					utf8_encode($tabela),
					'noreply@tc.id'
				);
				$linha_i = '';
			}
		}
	}
} catch (Exception $e) {
	echo $e->getMessage();
}
?>
