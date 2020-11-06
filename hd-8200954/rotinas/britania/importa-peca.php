<?php
include dirname(__FILE__).'/../../dbconfig.php';
include dirname(__FILE__).'/../../includes/dbconnect-inc.php';
require_once dirname(__FILE__).'/../../class/communicator.class.php';
require_once dirname(__FILE__).'/../funcoes.php';

$fabrica  = 3;
$data     = date('Y-m-d-H');

$vet['fabrica'] = 'britania';
$vet['tipo'] = 'importa-peca';
$vet['log'] = 2;

define("ENV", "production");
if (ENV === "production") {
    $origens        = "/www/cgi-bin/britania/entrada/";
    $arquivo_origem = $origens."telecontrol-pecas.txt";
    $arquivo_backup = "/tmp/britania/telecontrol-peca-" . $data . ".txt";
    $caminho_ftp    = "/tmp/britania/telecontrol-peca-" . $data . ".txt";
    $arquivo_erro   = "/tmp/britania/importa-pecas.err";
    $emails = array("helpdesk@telecontrol.com.br");
}


$array_erro = array();

try{

    $phpCron = new PHPCron($fabrica, __FILE__); 
    $phpCron->inicio();

    if(file_exists($arquivo_origem)) {
        if (ENV != "production") {
            echo "arquivo existe\n";
        }
		$fp = fopen( $arquivo_origem, "r+" );

		if ( !is_resource($fp) ) {	
			throw new Exception ('Arquivo ' . $dir . '/' . $file . ' nao Encontrado !');
		}

		$sql = "DROP TABLE IF EXISTS ".$vet['fabrica']."_peca;

				CREATE TABLE ".$vet['fabrica']."_peca 
				(referencia text, descricao text, unidade text, origem text, ipi text);

				COPY ".$vet['fabrica']. "_peca FROM stdin";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
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


			if(count(explode("\t",$linha)) <>5) continue;
			pg_put_line($con, $linha);

			$i++;
		}

	    if ( !empty($msg_erro) ) {
			throw new Exception($msg_erro);
		}

	    pg_put_line($con, "\\." . PHP_EOL);
		$msg_erro .= pg_last_error($con);
  		pg_end_copy($con);
		$msg_erro .= pg_last_error($con);

		$sql = "select count(1) from  ".$vet['fabrica']."_peca;";
		$res = pg_query($con,$sql);

		if(pg_fetch_result($res,0, 0) == 0) {
			throw new Exception ('Arquivo ' . $dir . '/' . $file . ' com erro no conteudo');
		}

		$sql = "ALTER TABLE ".$vet['fabrica']."_peca  ADD peca int4; 
				UPDATE ".$vet['fabrica']."_peca set peca = tbl_peca.peca FROM tbl_peca
				where fabrica = $fabrica 
				and tbl_peca.referencia = regexp_replace(".$vet['fabrica']."_peca.referencia, '-|\.|\s|\/','','g');

				UPDATE ".$vet['fabrica']."_peca
					set referencia = regexp_replace(".$vet['fabrica']."_peca.referencia, '-|\.|\s|\/','','g'),
						descricao = substr(regexp_replace(descricao,E'\'|\/', '','g'), 1, 50), 
						unidade = regexp_replace(".$vet['fabrica']."_peca.unidade, '-|\.|\s|\/','','g'),
						origem = substr(regexp_replace(origem,E'\'|\/', '','g'), 1,10), 
						ipi = replace(regexp_replace(ipi,E'\'|\/', '','g'), ',','.');";
		$res  = pg_query($con, $sql);
		$array_erro[] = pg_last_error();

		if(empty(pg_last_error())) {
			pg_query($con, "BEGIN");
			$sql = "UPDATE tbl_peca SET
				descricao = ".$vet['fabrica']."_peca.descricao,
				unidade = ".$vet['fabrica']."_peca.unidade,
				origem = ".$vet['fabrica']."_peca.origem,
				ipi = ".$vet['fabrica']."_peca.ipi::float
				FROM ".$vet['fabrica']."_peca
				WHERE ".$vet['fabrica']."_peca.peca = tbl_peca.peca and fabrica = $fabrica; ";
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
				ipi )
				SELECT distinct $fabrica,
				referencia,
				descricao,
				unidade,
				origem,
				ipi::float
				FROM ".$vet['fabrica']."_peca
				WHERE peca ISNULL  ";
			$res = pg_query($con, $sql);
			$array_erro[] = pg_last_error();
			pg_query($con, "COMMIT");
		}else{
			throw new Exception ('Erro ao atualizar os dados');
		}

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
                    $email->sendMail($emails,"Telecontrol - britania: LOG da Importação dos Peças",file_get_contents($arquivo_erro), 'helpdesk@telecontrol.com.br');
                }
          }
			system("mv {$arquivo_origem} {$arquivo_backup}");
        }
    $phpCron->termino();
}catch(Exception $e){
    pg_query($con,"ROLLBACK");

    if(file_exists($arquivo_origem)) {
		system("mv {$arquivo_origem} {$arquivo_backup}");
		$ae = fopen($arquivo_erro, "w");
		fwrite($ae, "<h1>Erro na execução na importação de peças</h1><br /><br />");
		fwrite($ae, "<h4>O arquivo de backup pode ser encontrado no ftp no seguinte caminho {$caminho_ftp}</h4><br />");
		fwrite($ae, $e->getMessage());
		fclose($ae);

		if (!empty($emails)) {
			$email = new TcComm('noreply@tc');
			$email->sendMail($emails,"Telecontrol - britania - Erros na Importação de Peças",file_get_contents($arquivo_erro), 'helpdesk@telecontrol.com.br');
		}
	}
}
