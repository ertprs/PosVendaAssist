<?php

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

//$caminho = "../rotinas/inbrasil/entrada/num_serie.txt";
$caminho = "/var/www/cgi-bin/inbrasil/entrada/num_serie.txt";

if(!file_exists($caminho)){
	$erro .= "Arquivo não encontrado. ";
}

$arquivo = file($caminho);

$login_fabrica = 150;//somente para fabrica inBrasil

foreach ($arquivo as $valores) {
	$dados = "";
	$dados = explode("\t", $valores);

	$aux = $dados[0];

	$caracteres 	= array(".", ",", "-",";", "*", "/", "'", "!");
	$dados[3] 		= str_replace($caracteres, "", $dados[3]);
	
	//list($dnf, $mnf, $ynf) = explode("/", $dados[4]);
	//$dados[4] = $ynf."-".$mnf."-".$dnf;

	$sql_pesquisa_produto = "select produto from tbl_produto where referencia_pesquisa = '$dados[3]'";
	$res_pesquisa_produto = pg_query($con, $sql_pesquisa_produto);
		$produto_id = (int)pg_fetch_result($res_pesquisa_produto, 0, 'produto');

		if($produto_id == 0){
			$erro .= "Produto de Referência $dados[3] não foi encontrado. <Br>";
		}else{
			while($aux <= $dados[1]){

				$caracteres 	= array(".", ",", "-",";", "*", "/", "'", "!");
				$dados[2] 		= str_replace($caracteres, "", $dados[2]);
				$dados[3] 		= str_replace($caracteres, "", $dados[3]);
				
				$qtde = strlen(trim($dados[1]));

				$serie_aux  = str_pad($aux, $qtde, "0", STR_PAD_LEFT);

				$serie 			= $serie_aux.$dados[2];

				$sql_verifica = "SELECT serie from tbl_numero_serie where serie = '$serie' and produto = $produto_id and fabrica = $login_fabrica ";
				$res_verifica = pg_query($con, $sql_verifica);
					if(pg_num_rows($res_verifica) >0){
						$erro .= "Número de série $serie já cadastrado. <br>";
					}else{						
						$sql = "insert into tbl_numero_serie(fabrica, serie, referencia_produto, data_fabricacao, produto)
								values($login_fabrica, '$serie', '$dados[3]', '$dados[4]', $produto_id)";
						$res = pg_query($con, $sql);

						if(strlen(trim(pg_last_error($con)))>0){
							$erro .= "Falha ao gravar o número de série - $serie. ".pg_last_error($con)." <br>";
						}
					}				
				$aux++;	
			}
		}
}

	if(strlen(trim($erro))>0){
		echo $erro;
	}

?>
