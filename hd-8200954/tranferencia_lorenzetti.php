<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$dir = "/www/assist/www/lorenzetti";
$dh  = opendir($dir);

while (false !== ($filename = readdir($dh))) {
	if($filename==".." OR $filename=="."){
		//faz nada se tiver .. ou . (diretórios)
	}else{
		$nome = explode(".", $filename);
//		echo $nome[0].'<br>';
		$descricao =  $nome[0];
		$linha     = 261;
		$extensao  = 'pdf';
		$fabrica   = 19;
		$tipo  = 'Vista Explodida';

		if (strlen($descricao) == 0)  $aux_descricao = "null";
		else                          $aux_descricao = "'". $descricao ."'";

		if (strlen($tipo_posto) == 0 )  $aux_tipo_posto = "null";
		else                            $aux_tipo_posto = "'". $tipo_posto ."'";

		if (strlen($extensao) == 0)   $aux_extensao = "null";
		else                          $aux_extensao = "'". $extensao ."'";

		if (strlen($familia) == 0)    $aux_familia = "null";
		else                          $aux_familia = $familia ;

		if (strlen($linha) == 0)      $aux_linha = "null";
		else                          $aux_linha = $linha ;

		if (strlen($tipo) == 0)       $aux_tipo = "null";
		else                          $aux_tipo = "'". $tipo ."'";

		if (strlen($mensagem) == 0)   $aux_mensagem = "null";
		else                          $aux_mensagem = "'". $mensagem ."'";

		if (strlen($obrigatorio_os_produto) == 0) $aux_obrigatorio_os_produto = "'f'";
		else                                      $aux_obrigatorio_os_produto = "'t'";

		if (strlen($obrigatorio_site) == 0)       $aux_obrigatorio_site = "'f'";
		else                                      $aux_obrigatorio_site = "'t'";

		if (trim($ativo) == 'f')                  $aux_ativo = "'f'";
		else                                      $aux_ativo = "'t'";

		$produto = "null";

		$posto = "null";

			$sql = "INSERT INTO tbl_comunicado (
							produto                ,
							familia                ,
							linha                  ,
							extensao               ,
							descricao              ,
							mensagem               ,
							tipo                   ,
							fabrica                ,
							obrigatorio_os_produto ,
							obrigatorio_site       ,
							posto                  ,
							tipo_posto             ,
							ativo                  ,
							remetente_email
						) VALUES (
							$produto                    ,
							$aux_familia                ,
							$aux_linha                  ,
							$aux_extensao               ,
							$aux_descricao              ,
							$aux_mensagem               ,
							$aux_tipo                   ,
							$fabrica                    ,
							$aux_obrigatorio_os_produto ,
							$aux_obrigatorio_site       ,
							$posto                      ,
							$aux_tipo_posto             ,
							$aux_ativo                  ,
							'$remetente_email'
						);";

		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
			

		$res        = pg_exec ($con,"SELECT currval ('seq_comunicado')");
		$comunicado = pg_result ($res,0,0);
		
		system ("cp /www/assist/www/lorenzetti/$filename /www/assist/www/comunicados/$comunicado.pdf");
		//rename("/www/assist/www/lorenzetti/$filename", "/www/assist/www/lorenzetti/vistas/$comunicado.pdf");
		echo "OK - $filename - $comunicado<br>";



	}
}

/*

system ("cp /www/assist/www/lorenzetti/2020_C61.pdf /www/assist/www/comunicados/teste.pdf");

$dir = "/var/www/assist/www/lorenzetti";
$dh  = opendir($dir);
$count=0;
while (false !== ($filename = readdir($dh))) {
	if($filename==".." OR $filename=="."){
		//faz nada se tiver .. ou . (diretórios)
	}else{
		$nome = explode(".", $filename);
		echo $nome[0].'<br>';
	}
}
*/
?>