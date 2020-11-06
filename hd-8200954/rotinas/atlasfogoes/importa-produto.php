<?php 

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

	$fabrica  = "74" ;
	$login_fabrica  = $fabrica ;
	$arquivos = "/tmp/atlas";
	$origem   = "/home/atlas/atlas-telecontrol";

	function limpa_string($dados){
		$retirar = array("-",".", "/", "*");
		$dados = str_replace($retirar, "", $dados);
		return $dados;
	}

/* Inicio Processo */ 

$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();

if(file_exists("$origem/produto.txt")){

	$conteudo_produto = file("$origem/produto.txt");
	$num_linha = 1;

	foreach($conteudo_produto as $linha){
		$valores = explode("\t",$linha);

		$referencia 					= trim(limpa_string($valores[0]));
		$descricao 						= trim(limpa_string($valores[1]));
		$linha 							= trim(limpa_string($valores[2]));
		$familia 						= trim(limpa_string($valores[3]));
		$voltagem 						= trim(limpa_string($valores[4]));
		$numero_serie_obrigatorio		= trim(limpa_string($valores[5]));

		if(strlen(trim($numero_serie_obrigatorio))==0){
			$numero_serie_obrigatorio= 'f';
		}

		$sql = "SELECT linha
				FROM   tbl_linha
				WHERE  trim(upper(tbl_linha.nome)) = trim(upper('$linha'))";
		$result = pg_query($con, $sql);

		$sql2 ="SELECT familia
				FROM   tbl_familia
				WHERE  trim(upper(tbl_familia.codigo_familia)) = trim(upper('$familia'))";
		$result2 = pg_query($con, $sql2);

		if (pg_num_rows($result) == 0 || pg_num_rows($result2) == 0 ) {
			if (pg_num_rows($result) == 0){
				$log .= "Linha do Arquivo $num_linha - Código de Linha $linha não encontrada. \n";	
			}
			if (pg_num_rows($result2) == 0){
				$log .= "Linha do Arquivo $num_linha - Código de Familia $familia não encontrada. \n";
			}
			continue;
		}else{
			$linha = pg_fetch_result($result, 0, 'linha');
			$familia = pg_fetch_result($result2, 0, 'familia');
		}

		### VERIFICA EXISTÊNCIA DO PRODUTO
		$sql = "SELECT tbl_produto.produto
				FROM   tbl_produto
				JOIN   tbl_linha USING (linha)
				WHERE  tbl_produto.referencia = '$referencia'
				AND    tbl_linha.fabrica = $login_fabrica";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) == 0 ){
			### INCLUI PRODUTO QUE NÃO EXISTE
			
				$sql = "INSERT INTO tbl_produto (
							linha                ,
							referencia           ,
							descricao            ,
							familia              ,
							voltagem             ,
							garantia             ,
							mao_de_obra          ,
							mao_de_obra_admin    ,
							numero_serie_obrigatorio
						)VALUES(
							$linha               ,
							'$referencia'        ,
							'$descricao'         ,
							$familia             ,
							'$voltagem'          ,
							0                    ,
							0                    ,
							0                    ,
							'$numero_serie_obrigatorio'
						)";
				$res = pg_query($con, $sql); 
				if(strlen(trim(pg_last_error($con)))>0){
					$msg_erro_interno .= "erro ao inserir. ". pg_last_error($con) ."\n\n";
				}else{
					$sql = "SELECT currval ('seq_produto')";
					$result = pg_query($con, $sql);
					$produto = pg_fetch_result($res, 0, $produto);
				}
			}else{
				$produto = pg_fetch_result($res, 0, 'produto');

				$sql = "UPDATE tbl_produto SET
						descricao   = '$descricao',
						numero_serie_obrigatorio = '$numero_serie_obrigatorio'
					WHERE tbl_produto.produto = $produto ";
				$result = pg_query($con, $sql);
					
				if (strlen(trim(pg_last_error($con))) > 0) {
					$msg_erro_interno .= $sql;
					$msg_erro_interno .= "\n";
					$msg_erro_interno .= pg_last_error($con);
					$produto = "";
				}
			}
			$atlas = "";
			$familia_descricao = "";
			$num_linha ++;
	}	

	$sql = "select email_cadastros from tbl_fabrica where fabrica = $fabrica ";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$para = pg_fetch_result($res, 0, 'email_cadastros');
	}

	$sql = "select email_cadastros from tbl_fabrica where fabrica = $fabrica ";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$para = pg_fetch_result($res, 0, 'email_cadastros');
	}	

	if (!empty($msg_erro_interno)) {
		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    
	   	//$para = "paulos@atlas.ind.br, cicero@atlas.ind.br, alaelcio@atlas.ind.br, helpdesk@telecontrol.com.br";
	    
	    $assunto   = "ATLAS - Erros na importação de produtos da Atlas";
		$mensagem  = "Segue dados da importação de produtos da Atlas. \n\n ";
		$mensagem  .= "$msg_erro_interno";
		mail($para, $assunto, $mensagem, $headers);	
	} 

	if(!empty($log)) {
		
		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    
	   	//$para = "paulos@atlas.ind.br, cicero@atlas.ind.br, alaelcio@atlas.ind.br, helpdesk@telecontrol.com.br";
	    
	    $assunto   = "ATLAS - Log de importação de produtos da Atlas";
		$mensagem  = "Segue dados da importação de produtos da Atlas. \n\n ";
		$mensagem  .= "$log \n";
		mail($para, $assunto, $mensagem, $headers);	
	}

	$phpCron->termino();

	$data = date('Y-m-d-h-s');

	if (file_exists("/home/atlas/atlas-telecontrol/produto.txt")) {
		system("mv /home/atlas/atlas-telecontrol/produto.txt  /tmp/atlas/produto_$data.txt");
	}

}else{
	$phpCron->termino('Arquivo não existe no ftp');
}

?>
