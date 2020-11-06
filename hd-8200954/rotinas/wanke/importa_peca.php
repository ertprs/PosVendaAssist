<?php 
/** 
 * 
 * importa-peca.php 
 * 
 * Importacao de pecas Thermosystem 
 */ 

error_reporting(E_ALL ^ E_NOTICE); 

//define('ENV', 'teste'); 
 define('ENV', 'producao'); 
// define('DEV_EMAIL', 'marisa.silvana@telecontrol.com.br'); 
define('DEV_EMAIL', 'william.lopes@telecontrol.com.br'); 

try {

	//include dirname(__FILE__) . '/../../dbconfig_bc_teste.php'; 
	include dirname(__FILE__) . '/../../dbconfig.php'; 
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php'; 
	include dirname(__FILE__) . '/../../class/log/log.class.php'; 

	// ARGE 
	$fabrica = 91; 
	$fabrica_nome = 'wanke'; 

	function strtim($var) 
	{ 
		if (!empty($var)) { 
			$var = trim($var); 
			$var = str_replace("'", "\'", $var); 
			$var = str_replace(".", "\.", $var); 
		} 

		return $var; 
	} 
	$diretorio_origem = '/www/cgi-bin/' . $fabrica_nome . '/entrada'; 
	$arquivo_origem = 'telecontrol-peca.txt'; 
	 
	if (ENV == 'teste') { 
		$ftp = '/tmp/tmpwanke/'.$fabrica_nome; 
	}else{ 
		$ftp = '/tmp/wanke/telecontrol-' . $fabrica_nome; 
	} 

	$arquivo = $diretorio_origem . '/' . $arquivo_origem; 

	if (ENV == 'teste') { 
		$arquivo = '/tmp/tmp' . $fabrica_nome . '/' . $arquivo_origem; 
	} 

	if (file_exists($arquivo) and (filesize($arquivo) > 0)) { 
		$conteudo = file_get_contents($arquivo); 
		$conteudo = explode("\n", $conteudo); 
		
		foreach ($conteudo as $linha) { 
			if (!empty($linha)) { 
				list ($peca_cod, $peca_descricao, $defeito_cod, $defeito_descricao) = explode ("\t",$linha); 
				$original = array($peca_cod, $peca_descricao, $defeito_cod, $defeito_descricao); 
				
				if ($defeito_cod == 0) {
					echo "erro";
				}else{
					$sql = "SELECT descricao,codigo_defeito,defeito
							FROM tbl_defeito 
							WHERE codigo_defeito = '$defeito_cod'
							AND fabrica = $fabrica";
					$res = pg_query($con,$sql);
					
					if (pg_num_rows($res) > 0){
						$defeito = pg_fetch_result($res,0,'defeito');
						
						$sql = "SELECT peca
								FROM tbl_peca
								WHERE referencia = '$peca_cod' 
								AND fabrica = $fabrica";
						$res = pg_query($con,$sql);
					
						if (pg_num_rows($res) > 0){
							$peca = pg_fetch_result($res, 0, 'peca');
							
							$sql = "SELECT peca,defeito 
									FROM tbl_peca_defeito 
									WHERE peca=$peca 
									AND defeito = $defeito ";
							$res = pg_query($con,$sql);
							
							if (pg_num_rows($res) == 0){
									
								$sql = "INSERT INTO tbl_peca_defeito (
											peca,
											defeito, 
											ativo
										) VALUES ( 
											$peca, 
											'$defeito', 
											true
										)"; 
							} else {
								$sql = "UPDATE tbl_peca_defeito 
											SET ativo = true
											WHERE peca=$peca 
											AND defeito = $defeito  "; 
							}
							echo $sql;
							$res = pg_query($con, $sql); 

							if (pg_last_error($con)) {

								echo "Erro ao Atualizar / Inserir a Peca $peca_cod"; 
								
							} 
						}else{
							echo "PECA NAO ENCONTRADA $peca_cod  ";
						}
					}else{
						echo " defeito nao cadastrado $defeito_cod descricao : $defeito_descricao ";
					}
				}
			}
		}
	}
} catch (Exception $e) {
	echo $e->getMessage(); 
}