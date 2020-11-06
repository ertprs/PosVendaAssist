<?php                                                                                                                                                 
error_reporting(E_ALL ^ E_NOTICE);
define('APP','Importa Número de Série - Yanmar'); // Nome da rotina, para ser enviado por e-mail
define('ENV','producao'); // Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$fabrica     = 148; 
	$data        = date('d-m-Y');

	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	$vet['fabrica'] = 'yanmar';
	$vet['tipo']    = 'importa-serie';
	$vet['dest']    = ENV == 'testes' ? 'ronald.santos@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
	$vet['log']     = 1;
	$logs           = array();

	$file = 'telecontrol-numero-serie.txt';

	if ( ENV == 'testes' ) { 
		$dir    = '/home/ronald/public_html/rotinas/yanmar/entrada/';
	} else {
		$dir    = '/home/' . $vet['fabrica'] . '/' . $vet['fabrica'] . '-telecontrol/';
	} 

	if ( !file_exists($dir.$file) ) { 
		$logs[] = "ARQUIVO DE NÚMERO DE SÉRIE NÃO ENCONTRADO";
	}else{
	
		$sql = "DROP TABLE if exists tmp_".$vet['fabrica']."_numero_serie";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if(strlen($msg_erro) > 0){ 
			$logs[] = "ERRO AO DELETAR A TABELA tmp_".$vet['fabrica']."_numero_serie";
		}else{
			$sql = "CREATE TABLE tmp_".$vet['fabrica']."_numero_serie (cod_item text,num_serie text,data_fabricacao text, pin text)";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			if(strlen($msg_erro) > 0){
				$logs[] = "ERRO AO CRIAR A TABELA tmp_".$vet['fabrica']."_numero_serie";
			}else{
				$arquivo_conteudo = explode("\n", file_get_contents($dir.$file));
				$arquivo_conteudo = array_filter($arquivo_conteudo);

				foreach($arquivo_conteudo as $linha_numero => $linha_conteudo) {

					list(
						$cod_item,
						$num_serie,
						$data_fabricacao,
						$pin
					) = explode(";", $linha_conteudo);

					$sql = "INSERT INTO tmp_".$vet['fabrica']."_numero_serie
						(
							cod_item,
							num_serie,
							data_fabricacao,
							pin
						) VALUES(
							'{$cod_item}',
							'{$num_serie}',
							'{$data_fabricacao}',
							'{$pin}'
						)";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
						$logs[] = "NÚMERO DE SÉRIE ".($linha_numero + 1).": - ERRO AO INSERIR REGISTRO NA TABELA ".$vet['fabrica']."_numero_serie";
					}
				}

				$sql = "ALTER TABLE tmp_".$vet['fabrica']."_numero_serie ADD COLUMN produto INT";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);

				if(strlen($msg_erro) > 0){
					$logs[] = "ERRO AO CRIAR CAMPO PRODUTO NA TABELA tmp_".$vet['fabrica']."_numero_serie";
				}else{
					$sql = "ALTER TABLE tmp_".$vet['fabrica']."_numero_serie ADD COLUMN existe BOOLEAN default false";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
						$logs[] = "ERRO AO CRIAR CAMPO EXISTE NA TABELA tmp_".$vet['fabrica']."_numero_serie";
					}else{

						$sql = "UPDATE tmp_".$vet['fabrica']."_numero_serie
							SET produto = tbl_produto.produto
							FROM tbl_produto
							JOIN tbl_linha using(linha)
							WHERE tbl_linha.fabrica = {$fabrica}
							AND tbl_produto.referencia = tmp_".$vet['fabrica']."_numero_serie.cod_item";
						$res = pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);

						if(strlen($msg_erro) > 0){
							$logs[] = "ERRO AO ATUALIZAR O CAMPO PRODUTO NA TABELA tmp_".$vet['fabrica']."_numero_serie";
						}else{
							$sql = "update tmp_".$vet['fabrica']."_numero_serie
								set existe = 't'   
								from tbl_numero_serie
								where tbl_numero_serie.fabrica=$fabrica
								and tbl_numero_serie.produto = tmp_".$vet['fabrica']."_numero_serie.produto::integer
								and tbl_numero_serie.serie = tmp_".$vet['fabrica']."_numero_serie.num_serie";

							$res = pg_query($con,$sql);
							$msg_erro = pg_errormessage($con);

							if(strlen($msg_erro) > 0){
								$logs[] = "ERRO AO ATUALIZAR O CAMPO EXISTE NA TABELA tmp_".$vet['fabrica']."_numero_serie";
							}else{

								$sql = "SELECT
									cod_item,
									num_serie,
									produto,
									data_fabricacao,
									existe,
									pin
									FROM tmp_".$vet['fabrica']."_numero_serie
									WHERE produto is not null";
								$res = pg_query($con,$sql);
								$msg_erro = pg_errormessage($con);

								if(strlen($msg_erro) > 0){
									$logs[] = "ERRO AO LISTAR NÚMEROS DE SÉRIE A SEREM IMPORTADOS NA TABELA tmp_".$vet['fabrica']."_numero_serie";
								}else{

									$confirmacao = "/tmp/".$vet['fabrica']."/confirma-serie-".$data.".txt";
									$fp = fopen("$confirmacao","w");

									for ($i=0; $i < pg_num_rows($res); $i++) { 
										$cod_item = pg_fetch_result($res, $i, 'cod_item');
										$num_serie = pg_fetch_result($res, $i, 'num_serie');
										$produto = pg_fetch_result($res, $i, 'produto');
										$pin = pg_fetch_result($res, $i, 'pin');
										$data_fabricacao = pg_fetch_result($res, $i, 'data_fabricacao');
										$existe = pg_fetch_result($res, $i, 'existe');

										if ($existe <> 't' and strlen($num_serie) > 0 ){
											$sql = "INSERT INTO tbl_numero_serie (
												fabrica,
												serie,
												referencia_produto,
												data_carga,
												produto,
												ordem,
												data_fabricacao
											)VALUES (
												$fabrica,
												'$num_serie',
												'$cod_item',
												current_timestamp,
												$produto,
												'$pin',
												'$data_fabricacao'
											)";
											$resI = pg_query($con,$sql);
											$msg_erro = pg_errormessage($con);
										}
										if(strlen($msg_erro) > 0){
											$logs[] = "ERRO AO IMPORTAR O NÚMERO DE SÉRIE $num_serie";
										}elseif(strlen($num_serie) > 0 ){
											$linha = $cod_item.";".$num_serie."\n";
											fwrite($fp, $linha);
										}
									}

									fclose($fp);
									system("cp $confirmacao {$dir}confirma-serie.txt");
								}
							}
						}
						
					}
				}
			}
		}

		system ("mv ".$dir.$file." /tmp/".$vet['fabrica']."/telecontrol-numero-serie-$data.txt");

		if(count($logs) > 0){

			$header  = "MIME-Version: 1.0\n";
			$header .= "Content-type: text/html; charset=iso-8859-1\n";
			$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";       

			mail("ronald.santos@telecontrol.com.br,marisa.silvana@telecontrol.com.br", "TELECONTROL / ".strtoupper($vet['fabrica'])." ({$data}) - IMPORTA NÚMERO SÉRIE", implode("<br />", $logs), $header);

			$fp = fopen("/tmp/".$vet['fabrica']."/telecontrol-numero-serie-$data.err","w");
			fwrite($fp,implode("<br />", $logs));
			fclose($fp);

		}

		$phpCron->termino();
	}
}catch (Exception $e){
	echo $e->getMessage() , "\n\n";
	$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
	Log::envia_email($vet,APP, $msg );
}
