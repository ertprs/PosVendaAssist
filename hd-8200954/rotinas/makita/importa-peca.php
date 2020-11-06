<?php
include dirname(__FILE__).'/../../dbconfig.php';
include dirname(__FILE__).'/../../includes/dbconnect-inc.php';
require_once dirname(__FILE__).'/../../class/communicator.class.php';
require_once dirname(__FILE__).'/../funcoes.php';

$fabrica  = 42;
$data     = date('Y-m-d-H');

$vet['fabrica'] = 'makita';
$vet['tmp'] = 'tmp_makita';
$vet['tipo'] = 'importa-peca';
$vet['log'] = 2;

define("ENV", "production");
//define("ENV", "development");
if (ENV === "production") {
    $origens        = "/www/cgi-bin/makita/entrada/";
    $arquivo_origem = $origens."telecontrol-peca.txt";
    $arquivo_backup = "/tmp/makita/telecontrol-peca-" . $data . ".txt";
    $caminho_ftp    = "/tmp/makita/telecontrol-peca-" . $data . ".txt";
    $arquivo_erro   = "/tmp/makita/importa-pecas.err";
    //$arquivo_log    = "/tmp/makita/importa-pecas.log");
    $emails = array("helpdesk@telecontrol.com.br");
}else{
    $origens        = "/home/ronald/public_html/txt/makita/entrada/";
    $arquivo_origem = $origens."telecontrol-peca.txt";
    $arquivo_backup = "/home/ronald/public_html/txt/makita/telecontrol-peca-" . $data . ".txt.bkp";
    $caminho_ftp    = "/home/ronald/public_html/txt/makita/telecontrol-peca-" . $data . ".txt.bkp";
    $arquivo_erro   = "/home/ronald/public_html/txt/makita/importa-pecas.err";
    //$arquivo_log    = "/tmp/makita/importa-pecas.log");
    $emails = array("ronald.santos@telecontrol.com.br","joao.junior@telecontrol.com.br");
    echo "inicio rotina\n";
}


$array_erro = array();

try{

    $phpCron = new PHPCron($fabrica, __FILE__); 
    $phpCron->inicio();

    if(file_exists($arquivo_origem)) {

		$sql = "DROP TABLE IF EXISTS ".$vet['tmp']."_peca;

				CREATE TABLE ".$vet['tmp']."_peca 
				(referencia text, descricao text, unidade text, origem text, ipi text, fora_linha text, status text, previsao_entrega text, ncm text);

				COPY ".$vet['tmp']. "_peca FROM stdin";

		$res = pg_query($con,$sql);
		
		$fp = fopen( $arquivo_origem, "r+" );
		while (!feof($fp)) {
		//remove quebra de linha do arquivo, para nao dar problema no pg_put_line - HD 824912
		$buffer[] = preg_replace( '/\n|\r/', '', fgets($fp) );
			$msg_erro .= pg_errormessage($con);
		}
		$i = 0;
		foreach($buffer as $linha) {
			// Verifica se nao eh a ultima linha e insere quebra de linha padrao do server
			if ( count($buffer) - ($i +1) > 0 ) {
				$linha .= PHP_EOL;
			}


			if(count(explode("\t",$linha)) <>9) continue;
			pg_put_line($con, $linha);

			$i++;
		}
	    	pg_put_line($con, "\\." . PHP_EOL);
		$msg_erro .= pg_last_error($con);
  		pg_end_copy($con);
		$msg_erro .= pg_last_error($con);

		$sql = "select count(1) from  ".$vet['tmp']."_peca;";
		$res = pg_query($con,$sql);

		if(pg_fetch_result($res,0, 0) == 0) {
			throw new Exception ('Arquivo ' . $dir . '/' . $file . ' com erro no conteudo');
		}

		$sql = "ALTER TABLE ".$vet['tmp']."_peca  ADD peca int4; 
				UPDATE ".$vet['tmp']."_peca set peca = tbl_peca.peca FROM tbl_peca
				where fabrica = $fabrica 
				and upper(tbl_peca.referencia_pesquisa) = upper(regexp_replace(".$vet['tmp']."_peca.referencia, '-|\.|\s|\/|_','','g'));

				UPDATE ".$vet['tmp']."_peca
					set referencia = regexp_replace(".$vet['tmp']."_peca.referencia, '\.|\s|\/','','g'),
						descricao = substr(regexp_replace(descricao,E'\'|\/', '','g'), 1, 50), 
						unidade = regexp_replace(".$vet['tmp']."_peca.unidade, '-|\.|\s|\/','','g'),
						origem = substr(regexp_replace(origem,E'\'|\/', '','g'), 1,10), 
						fora_linha = upper(trim(fora_linha)),
						ipi = replace(regexp_replace(ipi,E'\'|\/', '','g'), ',','.'),
						status = trim(status),
						previsao_entrega = case when length(trim(previsao_entrega)) > 0 then substr(trim(previsao_entrega),7,2)||'/'||substr(trim(previsao_entrega),5,2)||'/'||substr(trim(previsao_entrega),1,4) end,
						ncm = trim(ncm);";
		$res  = pg_query($con, $sql);
		$array_erro[] = pg_last_error();

		if(empty(pg_last_error())) {
			pg_query($con, "BEGIN");
			$sql = "UPDATE tbl_peca SET
				descricao = ".$vet['tmp']."_peca.descricao,
				unidade = ".$vet['tmp']."_peca.unidade,
				origem = ".$vet['tmp']."_peca.origem,
				ipi = ".$vet['tmp']."_peca.ipi::float,
				classificacao_fiscal = ".$vet['tmp']."_peca.ncm,
				parametros_adicionais =  CASE 
					WHEN ".$vet['tmp']."_peca.previsao_entrega IS NOT NULL THEN
						parametros_adicionais::jsonb || ('{\"previsaoEntrega\":\"' || ".$vet['tmp']."_peca.previsao_entrega || '\",\"status\":\"'|| status ||'\"}')::jsonb 
					ELSE
						parametros_adicionais::jsonb || ('{\"status\":\"'|| status ||'\",\"previsaoEntrega\":\"\"}')::jsonb
					END
				FROM ".$vet['tmp']."_peca
				WHERE ".$vet['tmp']."_peca.peca = tbl_peca.peca and fabrica = $fabrica; ";
			$res = pg_query($con, $sql);
			$array_erro[] = pg_last_error();
			pg_query($con, "COMMIT");

			pg_query($con, "BEGIN");
			$sql = "INSERT INTO tbl_peca (
				fabrica    ,
				referencia ,
				descricao  ,
				unidade    ,
				origem     ,
				ipi,
				classificacao_fiscal,
				parametros_adicionais )
				SELECT distinct $fabrica,
				referencia,
				descricao,
				unidade,
				origem,
				ipi::float,
				ncm,
				'{\"previsaoEntrega\":\"' || previsao_entrega || '\",\"status\":\"'|| status ||'\"}'
				FROM ".$vet['tmp']."_peca
				WHERE peca ISNULL  ";
			$res = pg_query($con, $sql);
			$array_erro[] = pg_last_error();
			pg_query($con, "COMMIT");

			pg_query($con, "BEGIN");
			$sql = "UPDATE ".$vet['tmp']."_peca set peca = tbl_peca.peca FROM tbl_peca
				where fabrica = $fabrica 
				and tbl_peca.referencia_pesquisa = upper(regexp_replace(".$vet['tmp']."_peca.referencia, '-|\.|\s|\/','','g'))
				and ".$vet['tmp']."_peca isnull;
			
				DELETE FROM tbl_peca_fora_linha USING ".$vet['tmp']."_peca where ".$vet['tmp']."_peca.peca = tbl_peca_fora_linha.peca and ".$vet['tmp']."_peca.fora_linha <>'F';

				INSERT INTO tbl_peca_fora_linha(fabrica, referencia, peca)
				SELECT $fabrica, referencia, peca from ".$vet['tmp']."_peca where peca notnull and fora_linha='F' ON CONFLICT DO NOTHING;
			";
			$res = pg_query($con, $sql);

			$array_erro[] = pg_last_error();
			pg_query($con, "COMMIT");
		}else{
			throw new Exception ('Erro ao atualizar os dados');
		}

	   $array_erro = array_filter($array_erro);

	   if (count($array_erro) > 0) {
			$ae = fopen($arquivo_erro, "w");

			fwrite($ae, "<h1>Log de erro referente a importação de Peças</h1><br />");
			fwrite($ae, "<h4>O arquivo de backup pode ser encontrado no ftp no seguinte caminho {$caminho_ftp}</h4><br />");

			foreach ($array_erro as $linha => $erros) {
				fwrite($ae, "<strong>Linha {$linha}:</strong><br />");
				fwrite($ae, "<ul>");

				foreach ($erros as $erro) {
					fwrite($ae, "<li>{$erro}</li>");
				}

				fwrite($ae, "</ul><br />");
			}
			fclose($ae);

			if (!empty($emails)) {
				$email = new TcComm('noreply@tc');
				$email->sendMail($emails,"Telecontrol - MAKITA: LOG da Importação dos Peças",file_get_contents($arquivo_erro), 'ronald.santos@telecontrol.com.br');
			}
		}
        system("mv {$arquivo_origem} {$arquivo_backup}");
    }
    $phpCron->termino();
}catch(Exception $e){
    pg_query($con,"ROLLBACK");

    $ae = fopen($arquivo_erro, "w");
    fwrite($ae, "<h1>Erro na execução na importação de peças</h1><br /><br />");
    fwrite($ae, "<h4>O arquivo de backup pode ser encontrado no ftp no seguinte caminho {$caminho_ftp}</h4><br />");
    fwrite($ae, $e->getMessage());
    fclose($ae);

    if (!empty($emails)) {
        $email = new TcComm('noreply@tc');
        $email->sendMail($emails,"Telecontrol - MAKITA - Erros na Importação de Peças",file_get_contents($arquivo_erro), 'ronald.santos@telecontrol.com.br');
    }

    system("mv {$arquivo_origem} {$arquivo_backup}");
}
