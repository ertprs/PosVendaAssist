<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
$arrThumb = array(
				"foto_fachada" => "",
				"foto_balcao"  => "",
				"foto_oficina" => "",
				"foto_estoque" => ""
			);
$arrFiles = array(
				"foto_fachada" => "",
				"foto_balcao"  => "",
				"foto_oficina" => "",
				"foto_estoque" => ""
			);
if ($S3_sdk_OK) {
	include_once S3CLASS;
	$s3 = new AmazonTC('co', (int) $login_fabrica);
	
	$S3_online = is_object($s3);
}

function verificaTipoImg($tipo){
	if(in_array($tipo, array("jpg","jpeg", "png", "pjpeg", "pjpg"))){
		return true;
	}else{
		return false;
	}

}
function verificaTamanhoImg($tamanho){
	if($tamanho>0){
		if($tamanho<2000000){
			return true;
		}else{
			return false;
		}
	}else{
		return false;
	}
}

function validaImg($val){
	$respJson = "";

	if(empty($val['error'])){

		$tipoImg = explode('/', $val['type']);
		
		if(!verificaTipoImg($tipoImg[1])){
			$respJson = json_encode(array("success" =>"false" , "msg" =>"O arquivo deve ser uma imagem jpeg ou png."));
			return $respJson;

		}else if(!verificaTamanhoImg($val["size"])){
			$respJson = json_encode(array("success" =>"false" , "msg" =>"O tamanho do arquivo deve ser menor ou igual a 2MB."));
			return $respJson;

		}
		return $respJson; 	
	}else{
		$respJson = json_encode(array("success" =>"false" , "msg" =>"Erro ao enviar o arquivo."));
		return $respJson;
	}
}
if($_POST['get_foto']){
	// pega miniaturas
	$posto = $_POST["posto"];
	$s3->getObjectList("thumb_pesquisa_black_{$posto}");
	$arrLinks = $s3->getLinkList($s3->files);
	foreach ($arrLinks as $link) {

		if(strpos($link, "foto_fachada") !== false){
			$arrThumb["foto_fachada"] = $link;
		}else if (strpos($link, "foto_balcao") !== false){
			$arrThumb["foto_balcao"] = $link;
		}else if(strpos($link, "foto_oficina") !== false){
			$arrThumb["foto_oficina"] = $link;
		}else if(strpos($link, "foto_estoque") !== false){
			$arrThumb["foto_estoque"] = $link;
		}
	}

	// pega imagens tamanho normal
	$s3->getObjectList("pesquisa_black_{$login_posto}");
	$arrLinks = $s3->getLinkList($s3->files);
	foreach ($arrLinks as $link) {

		if(strpos($link, "foto_fachada") !== false){
			$arrFiles["foto_fachada"] = $link;
		}else if (strpos($link, "foto_balcao") !== false){
			$arrFiles["foto_balcao"] = $link;
		}else if(strpos($link, "foto_oficina") !== false){
			$arrFiles["foto_oficina"] = $link;
		}else if(strpos($link, "foto_estoque") !== false){
			$arrFiles["foto_estoque"] = $link;
		}
	}


	// var_dump($s3->get_object_list("br.com.telecontrol.posvenda-downloads",  array("prefix" => "comunicados/testes/0001/pesquisa_black_{$login_posto}")));
	echo $respJson = json_encode(array("success" => "true", "thumbs"=>$arrThumb, "files" => $arrFiles));
}else if($_POST['submit_foto']){
	
	foreach ($_FILES as $key => $val) {
		if(strlen($val['name'])==0){
			continue;
		}
		switch ($key) {
			case 'foto_fachada':
			
				$msg = validaImg($val);

				if( strlen($msg) == 0 ){
					$type = strtolower(preg_replace("/.+\./", "", basename($val['name'])));

					if(isset($login_posto) && !empty($login_posto)){
						$nomeArquivo = "pesquisa_black_{$login_posto}_{$key}";

					}else if(isset($login_fabrica) && !empty($login_fabrica)){
						$nomeArquivo = "pesquisa_black_{$login_fabrica}_{$key}";

					}
					
					$s3->upload($nomeArquivo, $val);

					if(!$s3->result->isOK()){
						$respJson = json_encode(array("success" => "false", "msg"=>"erro"));
						echo $respJson;
					}else{

					
						$thumbLink = $s3->getLink("thumb_".$nomeArquivo.".".$type);
						$fileLink = $s3->getLink($nomeArquivo.".".$type);
						if($thumbLink == false){
							$respJson = json_encode(array("success" => "false", "msg"=>"erro"));
						}else{
							$arrThumb[$key]=$thumbLink;
							$arrFiles[$key]=$fileLink;
						}
					
					}
				}else{
					echo $msg;
					die;
				}

			break;

			case 'foto_balcao':
				$msg = validaImg($val);
				if( strlen($msg) == 0 ){
					$type = strtolower(preg_replace("/.+\./", "", basename($val['name'])));

					if(isset($login_posto) && !empty($login_posto)){
						$nomeArquivo = "pesquisa_black_{$login_posto}_{$key}";

					}else if(isset($login_fabrica) && !empty($login_fabrica)){
						$nomeArquivo = "pesquisa_black_{$key}_$login_fabrica";

					}

					$s3->upload($nomeArquivo, $val);

					if(!$s3->result->isOK()){
						$respJson = json_encode(array("success" => "false", "msg"=>"erro"));
						echo $respJson;
					}else{

						$thumbLink = $s3->getLink("thumb_".$nomeArquivo.".".$type);
						$fileLink = $s3->getLink($nomeArquivo.".".$type);
						if($thumbLink == false){
							$respJson = json_encode(array("success" => "false", "msg"=>"erro"));
						}else{
							$arrThumb[$key]=$thumbLink;
							$arrFiles[$key]=$fileLink;
						}
					}
				}else{
					echo $msg;
					die;
				}

			break;

			case 'foto_oficina':
				$msg = validaImg($val);
				if( strlen($msg) == 0 ){
					$type = strtolower(preg_replace("/.+\./", "", basename($val['name'])));

					if(isset($login_posto) && !empty($login_posto)){
						$nomeArquivo = "pesquisa_black_{$login_posto}_{$key}";

					}else if(isset($login_fabrica) && !empty($login_fabrica)){
						$nomeArquivo = "pesquisa_black_{$key}_$login_fabrica";

					}

					$s3->upload($nomeArquivo, $val);

					if(!$s3->result->isOK()){
						$respJson = json_encode(array("success" => "false", "msg"=>"erro"));
						echo $respJson;
					}else{

						$thumbLink = $s3->getLink("thumb_".$nomeArquivo.".".$type);
						$fileLink = $s3->getLink($nomeArquivo.".".$type);
						if($thumbLink == false){
							$respJson = json_encode(array("success" => "false", "msg"=>"erro"));
						}else{
							$arrThumb[$key]=$thumbLink;
							$arrFiles[$key]=$fileLink;
						}
					}
				}else{
					echo $msg;
					die;
				}

			break;

			case 'foto_estoque':
				$msg = validaImg($val);
				if( strlen($msg) == 0 ){
					$type = strtolower(preg_replace("/.+\./", "", basename($val['name'])));

					if(isset($login_posto) && !empty($login_posto)){
						$nomeArquivo = "pesquisa_black_{$login_posto}_{$key}";

					}else if(isset($login_fabrica) && !empty($login_fabrica)){
						$nomeArquivo = "pesquisa_black_{$key}_$login_fabrica";

					}

					$s3->upload($nomeArquivo, $val);

					if(!$s3->result->isOK()){
						$respJson = json_encode(array("success" => "false", "msg"=>"erro"));
						echo $respJson;
					}else{

						$thumbLink = $s3->getLink("thumb_".$nomeArquivo.".".$type);
						$fileLink = $s3->getLink($nomeArquivo.".".$type);
						if($thumbLink == false){
							$respJson = json_encode(array("success" => "false", "msg"=>"erro"));
						}else{
							$arrThumb[$key]=$thumbLink;
							$arrFiles[$key]=$fileLink;
						}
					}
				}else{
					echo $msg;
					die;
				}

			break;

			default:
				$respJson = json_encode(array("success" =>"false" , "msg" =>"Erro ao enviar o arquivo."));

				break;
		}	
	}

	$respJson = json_encode(array("success" => "true", "thumbs"=>$arrThumb, "files"=>$arrFiles));
	echo $respJson;
}


?>
