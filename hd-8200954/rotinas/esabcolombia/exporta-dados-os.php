<?php

require_once __DIR__ . '/../../dbconfig.php';
require_once __DIR__ . '/../../includes/dbconnect-inc.php';
require_once __DIR__ . "/../../classes/AWS/Translate/TranslateText.php";

/**
 * @author William Castro <william.castro@telecontrol.com.br>
 * Rotina de Relatorio de OS - ESAB
 */

$fabrica = 181;
$qtde_palavras_enviadas  = 0;
$qtde_palavras_recebidas = 0;

function traduzir($texto) { 

	global $qtde_palavras_recebidas, $qtde_palavras_enviadas;
	
	$qtde_palavras_enviadas += count(explode(" ", $texto));
	$traduzido =  \TranslateText::traduzir("es", "en", $texto);
	$qtde_palavras_recebidas += count(explode(" ", $traduzido->get('TranslatedText')));

	return (!empty($traduzido->get('TranslatedText'))) ? $traduzido->get('TranslatedText') : '';
}

try  { 
	$query = "SELECT tbl_os.sua_os,
			  CASE
			  WHEN tbl_tipo_atendimento.entrega_tecnica IS TRUE THEN
				'ENTREGA TÉCNICA'
			  ELSE
	    		'REPARO'
		 	  END AS tipo_atendimento,
				tbl_os.serie,
			  to_char(tbl_os.data_abertura,'MM-DD-YYYY') AS data_abertura,
			  to_char(tbl_os.data_fechamento,'MM-DD-YYYY') AS data_fechamento,
			CASE
			WHEN tbl_os.consumidor_revenda = 'C' THEN
			'CONS'
			ELSE
			'REV'
			END AS tipo_os,
			tbl_os.data_abertura - tbl_os.data_nf AS tempo_para_defeito,
			tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome,
			CASE
				WHEN tbl_os.consumidor_revenda = 'C' THEN
				tbl_os.consumidor_cidade
				ELSE
				tbl_cidade.nome
			END AS cidade,
			CASE
				WHEN tbl_os.consumidor_revenda = 'C' THEN
				tbl_os.consumidor_estado
				ELSE
				tbl_cidade.estado
			END AS estado,
			CASE
				WHEN tbl_os.consumidor_revenda = 'C' THEN
					tbl_os.consumidor_nome
				ELSE
					tbl_os.revenda_nome
			END AS consumidor,
			tbl_os.nota_fiscal,
			tbl_produto.referencia AS referencia_produto,
			tbl_produto.descricao AS descricao_produto,
			tbl_peca.referencia AS referencia_peca,
			tbl_peca.descricao AS descricao_peca,
			tbl_os_item.qtde,
			tbl_status_checkpoint.descricao AS status_checkpoint,
			tbl_os.status_checkpoint        AS status,
			tbl_os_campo_extra.campos_adicionais::jsonb->'classificacao' AS classificacao,
			regexp_replace(tbl_os.obs,'\\r|\\n','','g') AS descricao_detalhada
			FROM tbl_os
			INNER JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			INNER JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
			INNER JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
			INNER JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			INNER JOIN tbl_tipo_atendimento ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
			INNER JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os_campo_extra.fabrica = {$fabrica}
			INNEr JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$fabrica}
			INNER JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = {$fabrica}
			INNER JOIN tbl_status_checkpoint ON tbl_os.status_checkpoint = tbl_status_checkpoint.status_checkpoint
			LEFT JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
			LEFT JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade
			WHERE tbl_os.fabrica = {$fabrica}
			AND tbl_os.data_digitacao >= CURRENT_TIMESTAMP - INTERVAL '2 WEEK'
			ORDER BY tbl_os.data_abertura";


		$res = pg_query($con, $query);

		$data = date ("d-m-Y");

		system("mkdir -p -m 777 /tmp/esab");

        $arquivo_nome = "consulta_os_esab_colombia_" . $data . ".csv";
        $path = "/tmp/esab";
        $arquivo_completo = $path . $arquivo_nome;
		$dest = "/home/esab/telecontrol-esab/{$arquivo_nome}";
		
		#$dest =  __DIR__ . "/../../tmpFiles/{$arquivo_nome}";
		#$arquivo_completo = __DIR__ . "/../../tmpFiles/{$arquivo_nome}";

 		$fp = fopen ($arquivo_completo, "w+");

       /* fwrite($fp, utf8_encode(" SUA OS;TIPO ATENDIMENTO;SERIE;DATA ABERTURA;DATA FECHAMENTO;TIPO OS;TEMPO PARA DEFEITO; CODIGO POSTO;NOME POSTO;CIDADE;ESTADO;CONSUMIDOR;NOTA FISCAL;REFERENCIA PRODUTO;DESCRICAO PRODUTO;REFERENCIA PECA; DESCRICAO PECA;QUANTIDADE;STATUS;CASSIFICACAO;DESCRICAO DETALHADA; \r\n"));*/

        fwrite($fp, utf8_encode(" YOUR SO; ATTENDANCE TYPE; SERIES; OPEN DATE; CLOSING DATE; SO TYPE; DEFECT TIME; POST CODE; POST NAME; CITY; STATE; CONSUMER; INVOICE; PRODUCT REFERENCE; PRODUCT DESCRIPTION; PART REFERENCE; PART DESCRIPTION; QUANTITY; STATUS; CLASSIFICATION; DETAILED DESCRIPTION \r\n"));

        for ($i = 0; pg_num_rows($res) > $i; $i++) {


			$sua_os               = pg_fetch_result($res, $i, sua_os);
			$tipo_atendimento     = pg_fetch_result($res, $i, tipo_atendimento);

			$arrayAtendimento = [ "ENTREGA TÉCNICA" => "TECHNICAL DELIVERY",
								  "REPARO" => "REPAIR" ];

			$tipo_atendimento = $arrayAtendimento[$tipo_atendimento];

			$serie                = pg_fetch_result($res, $i, serie);
			$data_abertura        = pg_fetch_result($res, $i, data_abertura);
			$data_fechamento      = pg_fetch_result($res, $i, data_fechamento);
			$tipo_os              = pg_fetch_result($res, $i, tipo_os);

			$arrayTipoOs = [ "CONS" => "CONSUMER",
						     "REV"  => "RESALE" ];

			$tipo_os = $arrayTipoOs[$tipo_os];

			$tempo_para_defeito   = pg_fetch_result($res, $i, tempo_para_defeito);
			$codigo_posto         = pg_fetch_result($res, $i, codigo_posto);
			$nome                 = pg_fetch_result($res, $i, nome);
			$cidade               = pg_fetch_result($res, $i, cidade);
			$estado               = pg_fetch_result($res, $i, estado);
			$consumidor           = pg_fetch_result($res, $i, consumidor);
			$nota_fiscal          = pg_fetch_result($res, $i, nota_fiscal);
			$referencia_produto   = pg_fetch_result($res, $i, referencia_produto);
			$descricao_produto    = pg_fetch_result($res, $i, descricao_produto);
			$referencia_peca      = pg_fetch_result($res, $i, referencia_peca);
			$descricao_peca       = pg_fetch_result($res, $i, descricao_peca);
			$qtde                 = pg_fetch_result($res, $i, qtde);
			$status               = pg_fetch_result($res, $i, status);

			/**
			 *	0 - Aberto Call-Center
			 *	1 - Aguardando Analise
			 *	2 - Aguardando Peças
			 *	3 - Aguardando Conserto
			 *	4 - Aguardando Retirada
			 *	8 - Aguardando Produto
			 *	9 - Finalizada
			 */

			$arrayStatus =  [ 
								0 => "Call-Center Open",
							 	1 => "Waiting Analysis",
							 	2 => "Waiting Parts",
							 	3 => "Waiting Repair",
							 	4 => "Waiting Withdrawal",
							 	8 => "Waiting Product",
							 	9 => "Finished" 
						    ];

			$status = $arrayStatus[$status];

			$classificacao  = pg_fetch_result($res, $i, classificacao);
			$classificacao = json_decode($classificacao);

			$arrayClassificacao = [ "tecnico"   => "Technician",
						     		"comercial" => "Commercial",
						     		"logistico" => "Logistic" ];

			$classificacao = $arrayClassificacao[$classificacao[0]];

			$descricao_detalhada  = pg_fetch_result($res, $i, descricao_detalhada);

			if (strlen($descricao_detalhada) > 0) { 				
				$descricao_detalhada = str_replace(array('<br />', '<br>', '<br/>', ';', "\r"), ' - ', $descricao_detalhada);
				$descricao_detalhada = preg_replace('~[[:cntrl:]]~', '', $descricao_detalhada);
				$descricao_detalhada = traduzir($descricao_detalhada);
			}

			fwrite($fp, $sua_os               . ";");
			fwrite($fp, $tipo_atendimento     . ";"); 
			fwrite($fp, $serie                . ";");
			fwrite($fp, $data_abertura        . ";");
			fwrite($fp, $data_fechamento      . ";");
			fwrite($fp, $tipo_os              . ";");
			fwrite($fp, $tempo_para_defeito   . ";");
			fwrite($fp, $codigo_posto         . ";");
			fwrite($fp, $nome                 . ";");
			fwrite($fp, $cidade               . ";");
			fwrite($fp, $estado               . ";");
			fwrite($fp, $consumidor           . ";");
			fwrite($fp, $nota_fiscal          . ";");
			fwrite($fp, $referencia_produto   . ";");
			fwrite($fp, $descricao_produto    . ";");
			fwrite($fp, $referencia_peca      . ";");
			fwrite($fp, $descricao_peca       . ";");
			fwrite($fp, $qtde                 . ";");
			fwrite($fp, $status               . ";");
			fwrite($fp, $classificacao        . ";");
			fwrite($fp, $descricao_detalhada  . ";");
			fwrite($fp, "\r\n");

        }

	    fclose($fp);

	    system("cp $arquivo_completo $dest && chown esab:clientes $dest");	    
	    
} catch (Exception $e) {

	echo $e->getMessage();
}
