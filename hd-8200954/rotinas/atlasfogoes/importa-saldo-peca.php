<?php
/**
 *
 * importa-peca.php
 *
 * Importação de peças Atlas
 *
 * @author  Ronald Santos
 * @version 2012.04.17
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

	$fabrica = 74;
	$fabrica_nome = 'atlas';

	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	function strtim($var)
	{
		if (!empty($var)) {
			$var = trim($var);
			$var = str_replace("'", "\'", $var);
		}

		return $var;
	}

	$origem = "/home/atlas/atlas-telecontrol";

	$tmp     = "/tmp/atlas";

	if (ENV == 'teste') {
		$origem = "/home/ronald/public_html/posvenda/rotinas/atlasfogoes/entrada";
	}

	$arquivo = "$origem/saldo_pecas.txt";

	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	if (file_exists($arquivo) and (filesize($arquivo) > 0)) {

		$fp = fopen ("$origem/saldo_pecas.txt","r");
		$conteudo_arquivo = fread($fp, filesize($arquivo));
		$linhas = explode("\n",$conteudo_arquivo);
		fclose($fp);
#var_dump($linhas);

		$sql = "DROP TABLE IF EXISTS atlas_saldo_pecas";
		$result = pg_query($con,$sql);

		$sql = "CREATE TABLE atlas_saldo_pecas (
					referencia_posto   		varchar(20),
					referencia_peca      	varchar(20),
					qtde                 	text
				)";
		$result = pg_query($con,$sql);
		$erro.= pg_last_error();

		$res = pg_query($con,"COPY atlas_saldo_pecas FROM stdin");
		foreach ($linhas AS $linha) {
			$linha = str_replace("\r","",$linha);
			if(!empty($linha)){
				pg_put_line($con,"$linha\n");
				$erro.= pg_last_error();
			}

		}
		pg_put_line($con,"\\.\n");
		$erro.= pg_last_error();
		pg_end_copy($con);
		$erro.= pg_last_error();

		$sql = "UPDATE atlas_saldo_pecas SET referencia_posto=TRIM(referencia_posto), referencia_peca=TRIM(referencia_peca), qtde=TRIM(qtde);";
		$result = pg_query($con,$sql);
		$erro.= pg_last_error();

		#Altera a tabela
		$sql = "ALTER TABLE atlas_saldo_pecas ADD column posto int4";
		$result = pg_query($con,$sql);
		$erro.= pg_last_error();

		$sql = "ALTER TABLE atlas_saldo_pecas ADD column peca int4";
		$result = pg_query($con,$sql);
		$erro.= pg_last_error();

		#Atualiza os postos
		$sql = "UPDATE atlas_saldo_pecas SET posto = tbl_posto.posto
				FROM   tbl_posto
				WHERE  cnpj = atlas_saldo_pecas.referencia_posto";
		$result = pg_query($con,$sql);
		$erro.= pg_last_error();

		#atualiza as peças
		$sql = "UPDATE atlas_saldo_pecas SET peca = tbl_peca.peca
				FROM   tbl_peca
				WHERE tbl_peca.fabrica = $fabrica
				AND  upper(trim(atlas_saldo_pecas.referencia_peca)) = upper(trim(tbl_peca.referencia))";

		$result = pg_query($con,$sql);
		$erro.= pg_last_error();


		$sql = "SELECT atlas_saldo_pecas.posto,atlas_saldo_pecas.peca
			FROM	atlas_saldo_pecas
			JOIN    tbl_peca ON atlas_saldo_pecas.peca = tbl_peca.peca AND tbl_peca.fabrica = $fabrica
			WHERE   atlas_saldo_pecas.peca notnull
			AND     posto notnull
			AND     controla_saldo IS TRUE";
		$resultx = pg_query($con,$sql);
		$erro.= pg_last_error();

		if (pg_num_rows($resultx) > 0) {
			for ($i = 0; $i < pg_num_rows($resultx); $i++) {
				$posto             = pg_result($resultx,$i,'posto');
				$peca              = pg_result($resultx,$i,'peca');
				$valida = "";
				pg_query("BEGIN");

				$sql = "SELECT posto
						FROM tbl_estoque_posto
						WHERE posto = $posto
						AND   peca = $peca
						AND   fabrica = $fabrica
						AND   tipo = 'pulmao'";
				$xresult = pg_query($con,$sql);
				$erro.= pg_last_error();
				$valida.= pg_errormessage($con);
				if (pg_num_rows($xresult) > 0) {
					### ALTERA REGISTROS JÁ CADASTRADOS
					#$sql = "UPDATE  tbl_estoque_posto SET
					#				qtde    = (tbl_estoque_posto.qtde + atlas_saldo_pecas.qtde::double precision)
					#		FROM    atlas_saldo_pecas
					#		WHERE   tbl_estoque_posto.posto = atlas_saldo_pecas.posto
					#		AND     tbl_estoque_posto.peca    = atlas_saldo_pecas.peca
					#		AND     tbl_estoque_posto.peca = $peca
					#		AND     tbl_estoque_posto.posto = $posto
					#		AND     tbl_estoque_posto.fabrica = $fabrica;";
					 $sql = "UPDATE  tbl_estoque_posto SET
                                                                        qtde    = qtde + (atlas_saldo_pecas.qtde::double precision)
                                                        FROM    atlas_saldo_pecas
                                                        WHERE   tbl_estoque_posto.posto = atlas_saldo_pecas.posto
                                                        AND     tbl_estoque_posto.peca    = atlas_saldo_pecas.peca
                                                        AND     tbl_estoque_posto.peca = $peca
                                                        AND     tbl_estoque_posto.posto = $posto
                                                        AND     tbl_estoque_posto.fabrica = $fabrica
                                                        AND 	tbl_estoque_posto.fabrica = 'pulmao';";

					$result = pg_query($con,$sql);
					$erro.= pg_last_error();
					$valida.= pg_errormessage($con);

					$sql = "INSERT INTO tbl_estoque_posto_movimento(
                                                fabrica,
                                                posto,
                                                peca,
                                                qtde_entrada,
                                                data,
                                                obs,
                                                tipo
                                                )SELECT  DISTINCT
                                                                $fabrica                 ,
                                                                atlas_saldo_pecas.posto  ,
                                                                atlas_saldo_pecas.peca   ,
                                                                atlas_saldo_pecas.qtde::double precision,
                                                                current_date,
                                                                'Inserido rotina automática de saldo',
                                                                'pulmao'
                                                FROM    atlas_saldo_pecas
                                                WHERE peca IS NOT NULL
                                                AND posto IS NOT NULL
                                                AND   peca = $peca
                                                AND   posto = $posto;";
                                        $result = pg_query($con,$sql);
                                        $erro.= pg_last_error();
                                        $valida.= pg_errormessage($con);

				}else{
					# INSERE NOVOS ESTOQUES
					$sql = "INSERT INTO tbl_estoque_posto (
									fabrica  ,
									posto  ,
									peca     ,
									qtde    ,
									tipo
							)
							SELECT  DISTINCT
									$fabrica                 ,
									atlas_saldo_pecas.posto  ,
									atlas_saldo_pecas.peca   ,
									atlas_saldo_pecas.qtde::double precision,
									'pulmao'
							FROM    atlas_saldo_pecas
							WHERE peca IS NOT NULL
							AND posto IS NOT NULL
							AND   peca = $peca
							AND   posto = $posto;";
					$result = pg_query($con,$sql);
					$erro.= pg_last_error();
					$valida.= pg_errormessage($con);

					$sql = "INSERT INTO tbl_estoque_posto_movimento(
						fabrica,
						posto,
						peca,
						qtde_entrada,
						data,
						obs,
						tipo
						)SELECT  DISTINCT
								$fabrica                 ,
								atlas_saldo_pecas.posto  ,
								atlas_saldo_pecas.peca   ,
								atlas_saldo_pecas.qtde::double precision,
								current_date,
								'Inserido rotina autómatica de saldo',
								'pulmao'
						FROM    atlas_saldo_pecas
						WHERE peca IS NOT NULL
						AND posto IS NOT NULL
						AND   peca = $peca
						AND   posto = $posto;";
					$result = pg_query($con,$sql);
					$erro.= pg_last_error();
					$valida.= pg_errormessage($con);
				}
				if(strlen($valida) == 0){
					pg_query("COMMIT");
				} else {
					pg_query("ROLLBACK");
				}
			}
		}

		$data = date("Y-m-d");
		system ("cp $origem/saldo_pecas.txt $origem/bkp/saldo_pecas-$data.txt;");
		system ("mv $origem/saldo_pecas.txt $tmp/saldo_pecas-$data.txt;");

		if(!empty($erro)){
			$erro .= "\n Erros importação $data";
			$fp = fopen("$tmp/saldo_pecas.err","w");
			fwrite($fp,$erro);
			fclose($fp);
		}
	}

	$phpCron->termino();

} catch (Exception $e) {
	echo $e->getMessage();
}

