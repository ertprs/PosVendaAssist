<?php
require_once AWS_SDK; // const. definida nos autentica_*

class AnexaTC extends AmazonS3 {
    private $authtime = "+10 minutes";
    private $bucket_list = array(
        "os"                => array("bucket" => "br.com.telecontrol.os-anexo"),
        "os_item"           => array("bucket" => "br.com.telecontrol.os-anexo",           "path" => "item/"),
        "co"                => array("bucket" => "br.com.telecontrol.posvenda-downloads", "path" => "comunicados/"),
        "ve"                => array("bucket" => "br.com.telecontrol.posvenda-downloads", "path" => "vista_explodida/"),
        "callcenter"        => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "admin/callcenter_digitalizados/"),
        "inspecao"          => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "laudo/"),
        "pa_ce"             => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "posto_comprovante_endereco/"),
        "pa_co"             => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "posto_contrato/"),
        "pedido"            => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "comprovante_pagamento/"),
        "produto"           => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "produto/"),
        "requisitos"        => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "requisitos/"),
        "motivos"           => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "motivos/"),
        "processos"         => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "processos/"),
        "devolucao"         => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "devolucao/"),
        "analise_peca"      => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "analise_peca/"),
        "laudo_tecnico"     => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "laudo_tecnico/"),
        "laudo_anexo"       => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "laudo_anexo/"),
        "extrato"           => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "extrato_nota_fiscal_servico/"),
        "helpdesk_pa"       => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "helpdesk_posto_autorizado/"),
        "os_produto_serie"  => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "os_produto_serie/"),
        "procedimento"     => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "procedimento/"),
        "nf_bateria"        => array("bucket" => "br.com.telecontrol.webuploads",         "path" => "nf_baterias/")
    );

	public $bucket;
	private $fabrica;
	private $img_exts = array("png", "jpg", "jpeg", "bmp");
	private $path_adc;
	private $count = 0;

	public $files;
	public $result;


	public function __construct ($bucket, $login_fabrica, $mural = false) {

		try {
			parent::__construct();
		} catch (Exception $e) {
			die ($e->getMessage());
		}

		if (strlen(S3TEST) > 0) {
			$this->testes = "testes/";
		}

		$this->bucket = $this->bucket_list[$bucket]["bucket"];

		if (isset($this->bucket_list[$bucket]["path"])) {
			$this->path_adc = $this->bucket_list[$bucket]["path"];
		}

		if ($bucket != "pa_ce") {
			if ($login_fabrica == 42 && $bucket == "co" && $mural === true) {
				$this->fabrica = str_pad($login_fabrica, 3, 0, STR_PAD_LEFT);
			} else {
				if ($bucket == "ve") {
					$this->fabrica = str_pad($login_fabrica, 3, 0, STR_PAD_LEFT);
				} else {
					$this->fabrica = str_pad($login_fabrica, 4, 0, STR_PAD_LEFT);
				}
			}
		}
	}

	public function getFileInfo($name){
		$objectMetadata = $this->get_object_metadata($this->bucket, $name, $this->authtime);


		$time = str_replace('000Z',"", $objectMetadata["LastModified"]);
		$time = str_replace('T'," ", $time);
		$links[2][$idx] = "Modificado em ".date("d/m/Y H:i:s",strtotime($time."-3 hour"));

		$dados['LastModified'] = date("d/m/Y H:i:s",strtotime($time."-3 hour"));
		$dados['Size'] = $objectMetadata['Size'];


		return $dados;

	}

	public function ifObjectExists($name, $temp, $year, $month){
		if (strlen($name) > 0) {
			if (strlen(S3TEST) > 0 || $temp === true) {
				$teste = "testes/";
			}

			$path = $this->path_adc.$teste.$this->fabrica."/";

			if (!empty($year)) {
				$path .= $year."/";
			}

			if (!empty($month)) {
				$path .= str_pad($month, 2, 0, STR_PAD_LEFT)."/";
			}

			try {
				if ($this->if_object_exists($this->bucket, $path.$name)) {
					return true;
				}else{
					return false;
				}
			} catch (Exception $e) {
				return false;
			}
		}
	}

	public function copyObject($files, $year, $month) {
		if (count($files) > 0) {
			$path      = $this->path_adc.S3TEST.$this->fabrica."/";

			if (!empty($year)) {
				$path      .= $year."/";
			}

			if (!empty($month)) {
				$m = str_pad($month, 2, 0, STR_PAD_LEFT)."/";
				$path .= $m;
			}

			try {
				foreach ($files as $key => $file) {
					$file_orig = $file["file_orig"];
					$file_new  = $file["file_new"];

					if (strlen($file_orig) > 0 && strlen($file_new) > 0) {

						if ($this->if_object_exists($this->bucket, $path.$file_orig)) {


							$ext    = $this->get_ext($file_orig);
							$source = array("bucket" => $this->bucket,    "filename" => $path.$file_orig);
							$dest   = array("bucket" => $this->bucket,    "filename" => $path.$file_new);
							$opt    = array("acl" => static::ACL_PRIVATE, "storage"  => static::STORAGE_STANDARD);

							$this->getObjectList(preg_replace("/\.+.*/", "", $file_new), false, $year, $month);

							if (count($this->files) > 0) {
								foreach ($this->files as $del_file) {
									$this->deleteObject($del_file, false, $year, $month);
								}
							}

							$this->copy_object($source, $dest, $opt);

							if (in_array($ext, $this->img_exts)) {
								$source = array("bucket" => $this->bucket, "filename" => $path."thumb_".$file_orig);
								$dest   = array("bucket" => $this->bucket, "filename" => $path."thumb_".$file_new);

								$this->copy_object($source, $dest, $opt);

								if (!$this->if_object_exists($this->bucket, $path."thumb_".$file_new.".".$ext)) {
									$link_file_new = $this->getLink ($file_new, false, $year, $month) ;

									system("rm -rf /tmp/s3_file_new");
									system("wget '$link_file_new' -O /tmp/s3_file_new");

									$thumb = $this->img_resize("/tmp/s3_file_new", $ext, 100, 90, true);
									$this->send_s3($thumb, $path."thumb_".$file_new);
								}
							}

							unset($files[$key]);
							$this->count = 0;
						}
					}
				}

				return true;

			} catch (Exception $e) {

				if ($this->count == 3) {
					$this->count = 0;
					return false;
				}

				sleep(3);
				$this->count++;

				$this->copyObject($files, $year, $month);
			}
		}
	}

	public function moveTempToBucket ($files, $year, $month, $temp_diretorio_data = true) {

		if (count($files) > 0) {
			$path_temp = $this->path_adc."testes/".$this->fabrica."/";
			$path      = $this->path_adc.S3TEST.$this->fabrica."/";

			if (!empty($year)) {
				if ($temp_diretorio_data === true) {
					$path_temp .= $year."/";
				}

				$path      .= $year."/";
			}

			if (!empty($month)) {
				$m = str_pad($month, 2, 0, STR_PAD_LEFT)."/";

				if ($temp_diretorio_data === true) {
					$path_temp .= $m;
				}
				$path      .= $m;
			}

			try {

				foreach ($files as $key => $file) {
					$file_temp = $file["file_temp"];
					$file_new  = $file["file_new"];

					if (strlen($file_temp) > 0 && strlen($file_new) > 0) {
						if ($this->if_object_exists($this->bucket, $path_temp.$file_temp)) {
							$ext    = $this->get_ext($file_temp);
							$source = array("bucket" => $this->bucket,    "filename" => $path_temp.$file_temp);
							$dest   = array("bucket" => $this->bucket,    "filename" => $path.$file_new);
							$opt    = array("acl" => static::ACL_PRIVATE, "storage"  => static::STORAGE_STANDARD);

							$this->getObjectList(preg_replace("/\.+.*/", "", $file_new), false, $year, $month);

							if (count($this->files) > 0) {
								foreach ($this->files as $del_file) {
									$this->deleteObject($del_file, false, $year, $month);
								}
							}

							$this->copy_object($source, $dest, $opt);

							if (in_array($ext, $this->img_exts)) {
								$source = array("bucket" => $this->bucket, "filename" => $path_temp."thumb_".$file_temp);
								$dest   = array("bucket" => $this->bucket, "filename" => $path."thumb_".$file_new);

								$this->copy_object($source, $dest, $opt);

							}
							unset($files[$key]);
							$this->count = 0;
						}
					}
				}

				return true;

			} catch (Exception $e) {

				if ($this->count == 3) {
					$this->count = 0;
					return false;
				}

				sleep(3);
				$this->count++;

				$this->moveTempToBucket($files, $year, $month);
			}
		}
	}

	public function upload ($name, $file, $year = null, $month = null) {
		if (file_exists($file["tmp_name"]) && strlen($name) > 0) {

			$path = $this->path_adc.S3TEST.$this->fabrica."/";
			$ext  = $this->get_ext($file["name"]);

			if (in_array($ext, $this->img_exts)) {
				$file  = $this->img_resize($file["tmp_name"], $ext, 1280, 1280);
				$thumb = $this->img_resize($file, $ext, 100, 90, true);
			}

			if (!empty($year)) {
				$path .= $year."/";
			}

			if (!empty($month)) {
				$path .= str_pad($month, 2, 0, STR_PAD_LEFT)."/";
			}

			try {
				$this->getObjectList(str_replace(".{$ext}", ".", $name), false, $year, $month);

				if (count($this->files) > 0) {
					foreach ($this->files as $del_file) {
						$this->deleteObject(basename($del_file), false, $year, $month);
					}
				}
			} catch(Exception $e) {
				return false;
			}

			if(strstr($name, ".png") || strstr($name, ".bmp") || strstr($name, ".jpg") || strstr($name, ".jpeg")){
				$this->send_s3($file, $path.$name);
			}else{
				$this->send_s3($file, $path.$name.".".$ext);
			}

			if (in_array($ext, $this->img_exts)) {

				try {
					$this->getObjectList("thumb_".str_replace(".{$ext}", ".", $name), false, $year, $month);

					if (count($this->files) > 0) {
						foreach ($this->files as $del_file) {
							$this->deleteObject(basename($del_file), false, $year, $month);
						}
					}
				} catch(Exception $e) {
					return false;
				}

				$this->send_s3($thumb, $path."thumb_".$name.".".$ext);
			}
		}
	}

	public function tempUpload ($name, $file, $year, $month) {
		if (file_exists($file["tmp_name"]) && strlen($name) > 0) {
			$path = $this->path_adc."testes/".$this->fabrica."/";
			$ext  = $this->get_ext($file["name"]);

			if (in_array($ext, $this->img_exts)) {
				$file  = $this->img_resize($file["tmp_name"], $ext, 1280, 1280);
				$thumb = $this->img_resize($file, $ext, 100, 90, true);
			}

			if (!empty($year)) {
				$path .= $year."/";
			}

			if (!empty($month)) {
				$path .= str_pad($month, 2, 0, STR_PAD_LEFT)."/";
			}

			$this->send_s3($file, $path.$name.".".$ext);

			if (in_array($ext, $this->img_exts)) {
				$this->send_s3($thumb, $path."thumb_".$name.".".$ext);
			}
		}
	}

	public function deleteObject ($name, $temp, $year, $month) {
		if (strlen($name) > 0) {
			if (strlen(S3TEST) > 0 || $temp === true) {
				$teste = "testes/";
			}

			$path = $this->path_adc.$teste.$this->fabrica."/";

			if (!empty($year)) {
				$path .= $year."/";
			}

			if (!empty($month)) {
				$path .= str_pad($month, 2, 0, STR_PAD_LEFT)."/";
			}

			try {
				$this->result = $this->delete_object($this->bucket, $path.$name);

				$ext  = $this->get_ext($name);

				if (in_array($ext, $this->img_exts)) {
					$this->result = $this->delete_object($this->bucket, $path."thumb_".$name);
				}
			} catch (Exception $e) {
				return false;
			}

			return $this->result->isOK();
		}
	}

	public function getObject ($name, $temp, $year, $month) {
		if (strlen($name) > 0) {
			if (strlen(S3TEST) > 0 || $temp === true) {
				$teste = "testes/";
			}

			$path = $this->path_adc.$teste.$this->fabrica."/";

			if (!empty($year)) {
				$path .= $year."/";
			}

			if (!empty($month)) {
				$path .= str_pad($month, 2, 0, STR_PAD_LEFT)."/";
			}

			try {
				$response = $this->get_object($this->bucket, $path.$name);
			} catch (Exception $e) {
				return false;
			}

			if ($response->isOK()) {
				$this->files = $response;
			} else {
				$this->files = false;
			}
			return $this->files;
		}
	}
	/*
	* O RESULTADO É COLOCADO NA VARIAVEL $this->files
	*/
	public function getObjectList ($prefix, $temp = false, $year = null, $month = null) {

		if (strlen($prefix) > 0) {
			if (strlen(S3TEST) > 0 || $temp === true) {
				$teste = "testes/";
			}

			$path = $this->path_adc.$teste.$this->fabrica."/";

			if (!empty($year)) {
				$path .= $year."/";
			}

			if (!empty($month)) {
				$path .= str_pad($month, 2, 0, STR_PAD_LEFT)."/";
			}

			try {
				$this->files = $this->get_object_list($this->bucket, array(
					"prefix" => $path.$prefix
				));
			} catch (Exception $e) {
				$this->files = array();
			}

			return $this->files;
		}
	}

	public function getLink ($file = null, $temp = null, $year = null, $month = null) {
		if (strlen(S3TEST) > 0 || $temp === true) {
			$teste = "testes/";
		}

		$path = $this->path_adc.$teste.$this->fabrica."/";

		if (!empty($year)) {
			$path .= $year."/";
		}

		if (!empty($month)) {
			$path .= str_pad($month, 2, 0, STR_PAD_LEFT)."/";
		}

		try {
			if ($this->if_object_exists($this->bucket, $path.$file)) {
				$link = $this->get_object_url($this->bucket, $path.$file, $this->authtime,array('https'=>true));
			}else{
				$link=false;
			}
		} catch (Exception $e) {
			$link = false;
		}
		return $link;
	}

	public function getLinkList ($arrFiles) {
		$arrLinks = array();

		try {
			foreach ($arrFiles as $key => $file) {

				if($this->if_object_exists($this->bucket, $file)){
					$arrLinks[$key] = $this->get_object_url($this->bucket, $file, $this->authtime,array('https'=>true));
				}else{
					$arrLinks[$key] = false;
				}
			}
		} catch (Exception $e) {
			return array(); // houve erro, então retorna vazio para recomeçar
		}
		return $arrLinks;
    }

	private function send_s3 ($file, $path) {
		if (!is_array($file)) {
			$file_upload = $file;
		} else {
			$file_upload = $file["tmp_name"];
		}

		$opts = array("acl" => static::ACL_PRIVATE, "fileUpload" => $file_upload, "storage" => static::STORAGE_STANDARD);

		try {
			$this->result = $this->create_object($this->bucket, $path, $opts);
		} catch (Exception $e) {
			$this->result = false;
			return false;
		}
	}

	private function get_ext ($file) {
		return strtolower(preg_replace("/.+\./", "", basename($file)));
	}

	private function img_resize ($file, $ext, $width_new, $height_new, $thumb = false) {
		list($width, $height) = getimagesize($file);

		if ($width < $width_new) {
		    $width_new = $width;
		}

		if ($height < $height_new) {
		    $height_new = $height;
		}

		$f = imagecreatetruecolor($width_new, $height_new);

		switch ($ext) {
		    case "jpeg":
		    case "jpg":
		        $source = imagecreatefromjpeg($file);
		        break;

		    case "png":
		        $source = imagecreatefrompng($file);
		        break;

		    case "bmp":
		    	$source = imagecreatefromwbmp($file);
		    	break;
		}


		imagecopyresampled($f, $source, 0, 0, 0, 0, $width_new, $height_new, $width, $height);

		if ($thumb === true) {

			system("rm -rf /tmp/s3_tmp/thumb_".basename($file));
			system ("mkdir /tmp/s3_tmp/ 2> /dev/null  ; chmod 777 /tmp/opt/" );
			$file = "/tmp/s3_tmp/thumb_".basename($file);

		}

		switch ($ext) {
		    case "jpeg":
		    case "jpg":
		    		imagejpeg($f, $file);
		        break;

		    case "png":
		    		imagepng($f, $file);
		        break;

		    case "bmp":
		    		imagewbmp($f, $file);
		    	break;
		}

		return $file;
	}
}

//  Definindo algumas constantes...
if (!defined('BASE_DIR'))
	define('BASE_DIR', substr(__DIR__, 0, strpos(__DIR__, 'class')));
//  Funções auxiliares
if (!function_exists('open_image')) {
	function open_image($file) {
		global $imgType;
		$img_data = getimagesize($file);
		switch($img_data["mime"]){
			case "image/jpeg":
				$im = imagecreatefromjpeg($file); //jpeg file
				$imgType = 'jpg';
			break;
			case "image/gif":
				$im = imagecreatefromgif($file); //gif file
				$imgType = 'gif';
			break;
			case "image/png":
				$im = imagecreatefrompng($file); //png file
				$imgType = 'png';
			break;
			case "image/bmp":
				$im = imagecreatefromwbmp($file); //png file
				$imgType = 'bmp';
			break;
			default:
			$im=false;
			break;
		}
		return $im;
	}
}

if (!function_exists('save_image')) {
	function save_image($file, $dest_path, $format='jpeg') {
		// Grava a imagem no $dest_path, no formato $format
		switch($format){
			case "bmp":
			case "jpg":
			case "jpeg":
				$ret = imagejpeg($file, $dest_path, 80); // Salva em formato JPeG
			break;
			case "gif":
				$ret = imagegif($file, $dest_path); // Salva como GIF
			break;
			case "png":
				$ret = imagepng($file, $dest_path, 2); // Salva como PNG, compressão nível 2
			break;
			default:
			$ret = false;
			break;
		}
		return $ret;
	}
}

if (!function_exists('reduz_imagem')) {
	function reduz_imagem($img, $max_x, $max_y, $nome_foto, $formato = 'jpg') {
		list($original_x, $original_y) = getimagesize($img);    //pega o tamanho da imagem

		// se a largura for maior que altura
		$porcentagem = ($original_x > $original_y) ? (100 * $max_x) / $original_x : $porcentagem = (100 * $max_y) / $original_y;

		$tamanho_x = $original_x * ($porcentagem / 100);
		$tamanho_y = $original_y * ($porcentagem / 100);
		$image_p   = imagecreatetruecolor($tamanho_x, $tamanho_y);
		$image     = open_image($img);

		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $original_x, $original_y);
		return save_image($image_p, $nome_foto, $formato);
	}
}

if (!function_exists('cmpFileName')) {
	function cmpFileName($a,$b) {
		 if (pathinfo($a, PATHINFO_FILENAME) == pathinfo($b, PATHINFO_FILENAME)) return 0;
		 return (pathinfo($a, PATHINFO_FILENAME) < pathinfo($b, PATHINFO_FILENAME)) ? -1 : 1;
	}
}

/**
 * @name	anexaS3
 * Uso:
 * Ao instanciar um objeto, é obrigatório informar o Tipo de Anexo e o ID da fábrica:
 * 	Tipos de anexo:
 *    ve, vista_explodida   Anexos de vista explodida. São comunicados, mas ao ser muito específicos, tem seu próprio diretório
 *    lt, laudo_tecnico     Cópias dos laudos técnicos que alguma fábricas exigem
 *		[e vem mais...]
 *
 *	Exemplo: $anexo = new anexaS3('ve', 114)
 *		Para ter acesso às vistas explodidas da fárica 114 (Cobimex)
 *		Uma vez inicializado, em anexo->attachList terá um array com todas as vistas explodidas da fábrica 114
 *
 *	Exemplo: $anexo = new anexaS3('ve', 114, 916754)
 *		Neste caso, teríamos o segunte objeto:
 *		$anexo(
 *			temAnexo   => [false|1]   // Dependendo de se existe ou não o arquivo, false ou a quantidade de arquivos
 *			attachList => array()     // Array com a lista de arquivos que coincidem. Neste caso provavelmente apenas um
 *			url        => string	  // URL de acesso ao arquivo. Pode ser do CDN ou do bucket, com ou sem pré-autenticação,
 *									  // dependendo da configuração dos CDNs e das permissões do arquivo
 *			max_files  => int	      // Número máximo de arquivos para este tipo de anexo
 *			accepted_types => string  // Sting com a RegExp pronta para validar os tipos de arquivo aceitos
 *		)
 *
 *		Métodos:
 *		$anexo->temAnexos(id)		// Retorna false se não existe arquivo para esse ID, ou a quantidade. Também popula o atributo attachList
 *										e o url, se só tiver um arquivo
 *		$anexo->getS3Link(path)		// Retorna a URL do arquivo $path, que é o que veio dentro de attachList[]
 *										false e _erro se o $path não existe
 *		$anexo->uploadFileS3($id, $_FILES[])	// Sobe para o S3 o arquivo do $_FILES (poderá ter vários?) no bucket correspondente ao tipo
 *												   para o id $id
 *		Ex.:
 *		$anexo = new anexaS3('ve', 81);
 *		$anexo->uploadFileS3(987456, $_FILES['anexo_vista']);
 *
 *		if ($anexo->_erro) $msg_erro .= $anexo->_erro;
 *
 *
 **/
class AnexaSSS extends AmazonS3
{

	const TIPOS_VE = "Ajuda Suporte Tecnico,Alterações Técnicas,Análise Garantia,Apresentação do Produto,Atualização de Software,Boletim Técnico,Diagrama de Serviços,Esquema Elétrico,Estrutura do Produto,Foto,Informativos,Manual,Manual De Produto,Manual Técnico,Manual de Serviço,Manual de Trabalho,Manual do Usuário,Peças Alternativas,Peças de Reposição,Politica de Manutenção,Procedimento de Manutenção,Procedimento de manutenção,Procedimentos,Produtos,Promocao,Promoções,Teste Rede Autorizada,Treinamento de Produto,treinamento de Produto,Troca pendente,Versões EPROM,Versões Eprom,Vista Explodida,Vídeo,Video,Árvore de Falhas,Arvore de Falhas";

// THiago: retirado "Lançamentos" do tipos CO
	const TIPOS_CO = "Com. Unico Posto,Boletim,Extrato,Comunicado,Informativo,Comunicado administrativo,Foto,Descritivo técnico,Informativo tecnico,Recall,Manual,Manual de Serviço,Orientação de Serviço,Procedimentos,Promocao,Estrutura do Produto,tipo de Produto,treinamento de Produto,Informativo tecnico,Informativo administrativo,Procedimento de manutenção,Análise Garantia,Lançamentos,Análise garantia,Informativo Promocional,Comunicado de não conformidade,Acessório,Comunicado por tela,Tabela de precos";

	private $tipos_de_anexo; // Array com as configurações de buckets, formatos, tamanho, etc.
	private $tipos_ve;
	private $tipos_co;

	public $_erro;

	private $temAnexo;   // Irá conter a quantidade de arquivos que batem com a busca. false, 1, 2, ..
	private $attachList; // Array com a lista de arquivos. Usada pelo s3glob()
	private $objID;      // ID do objeto a ser pesquisado ou subido, p.e. tbl_comunicado.comunicado ou tbl_peca.peca

	public $_fabrica;   // O ID da fábrica já com os '0', ex. fábrica 81 será '081'
	private $tipo_anexo;

	private $bucket;
	private $root_dir;
	private $local_dir;
	private $cdn;
	private $auth_time;
	private $accepted_types;
	private $max_file_size;
	private $max_files;

	function __construct($tipoAnexo, $login_fabrica, $idx=null) {

		try {
			parent::__construct();
		} catch (Exception $e) {
			die (__LINE__ . $e->getMessage());
		}

		if (!is_int($login_fabrica) or !$tipoAnexo) {
			$this->_erro = 'Erro C4E1 ao inicializar o acesso ao S3';
			if (isCLI and DEBUG===true) die('Erro C4E1 ao inicializar o acesso ao S3');
			return $this;
		}

		$this->tipos_de_anexo = include(__DIR__ . '/s3_tipos_anexo.php');

		if (isCLI and DEBUG===true)
			var_export($this->tipos_de_anexo);

		if ($this->set_tipo_anexoS3($tipoAnexo)===false)
			throw new Exception($this->_erro);

		// Formatando o codigo das fabricas para 4 digitos
		if ($this->tipo_anexo == 'os') {
			$this->_fabrica = self::formata_fabrica($login_fabrica, 4);
		} else {
			$this->_fabrica = self::formata_fabrica($login_fabrica, 3);
		}

		if (!is_null($idx)) {
			$this->temAnexo = $this->temAnexos($idx);
		}
	}

	/**
	 * Com este método é possível alterar o tipo de anexo, p.e., de vista explodida para comunicados.
	 * Também é usado durante a construção do objeto. retorna o próprio objeto, para poder
	 * encadear chamadas: $s3->set_tipo_anexoS3('ve')->uploadFileS3($arquivo);
	 **/
	public function set_tipo_anexoS3($tipoAnexo) {
		$tipoAnexo = strtolower(str_replace(' ', '_', self::utf8_ascii7($tipoAnexo, mb_check_encoding($tipoAnexo, 'UTF8'))));

		$tipos_comunicado = explode(
			',', str_replace(
				' ', '_',
				strtolower(self::utf8_ascii7(self::TIPOS_CO))
			)
		);

		$tipos_vista = explode(
			',', str_replace(
				' ', '_',
				strtolower(self::utf8_ascii7(self::TIPOS_VE))
			)
		);

		if (in_array($tipoAnexo, $tipos_vista)) {
			$tipoAnexo = 've';
		}

		if (in_array($tipoAnexo, $tipos_comunicado)) {
			$tipoAnexo = 'co';
		}

		switch ($tipoAnexo) {
			case 'vista':
			case 'vista_explodida':
			case 'vista.explodida':
			case 'vista-explodida':
				$tipoAnexo = 've';
			break;

			case 'sedex':
			case 'nf':
			case 'nota-fiscal':
			case 'nota.fiscal':
			case 'nota_fiscal':
			case 'o.s.':
				$tipoAnexo = 'os';
			break;

			case 'comunicado': case 'com':
			case 'comunicado_inicial': case 'comunicado.inicial':
			case 'comunicado-inicial': case 'comunicadoinicial':
			case 'comini':  case 'com.ini':
			case 'com-ini': case 'com_ini':
				$tipoAnexo = 'co';
			break;

			case 'peca':
			case 'peça':
				$tipoAnexo = 'pc';
			break;

			case 'prod':
			case 'pr':
			case 'produto':
				$tipoAnexo = 'pd';
			break;

			case 'laudo':
			case 'laudo_tecnico':
			case 'laudo_técnico':
			case 'laudotecnico':
			case 'lt':
				$tipoAnexo = 'lt';
			break;

			case 'contrato_posto':
			case 'contratoposto':
			case 'contrato posto':
				$tipoAnexo = 'pa_co';
			break;
		}

		if (!array_key_exists($tipoAnexo, $this->tipos_de_anexo)) {
			$this->_erro = 'Erro AD3B: tipo de anexo desconhecido!';
			return $this;
		}

		if (isset($this->tipos_de_anexo[$tipoAnexo]['bucket']))
			$this->bucket         = $this->tipos_de_anexo[$tipoAnexo]['bucket'];

		if (isset($this->tipos_de_anexo[$tipoAnexo]['acl']))
			$this->access         = $this->tipos_de_anexo[$tipoAnexo]['acl'];

		if (isset($this->tipos_de_anexo[$tipoAnexo]['cdn']))
			$this->cdn            = $this->tipos_de_anexo[$tipoAnexo]['cdn'];

		if (isset($this->tipos_de_anexo[$tipoAnexo]['authtime']))
			$this->auth_time      = $this->tipos_de_anexo[$tipoAnexo]['authtime'];

		if (isset($this->tipos_de_anexo[$tipoAnexo]['accepts']))
			$this->accepted_types = $this->tipos_de_anexo[$tipoAnexo]['accepts'];

		if (isset($this->tipos_de_anexo[$tipoAnexo]['max_size']))
			$this->max_file_size  = $this->tipos_de_anexo[$tipoAnexo]['max_size'];

		if (isset($this->tipos_de_anexo[$tipoAnexo]['max_files']))
			$this->max_files      = $this->tipos_de_anexo[$tipoAnexo]['max_files'];

		if (isset($this->tipos_de_anexo[$tipoAnexo]['validaID']))
			$this->validaID       = $this->tipos_de_anexo[$tipoAnexo]['validaID'];

		if (isset($this->tipos_de_anexo[$tipoAnexo]['root_dir']))
			$this->root_dir       = $this->tipos_de_anexo[$tipoAnexo]['root_dir'];

		if (isset($this->tipos_de_anexo[$tipoAnexo]['localdir']))
			$this->local_dir      = $this->tipos_de_anexo[$tipoAnexo]['localdir'];

		$this->tipo_anexo     = $tipoAnexo;

		return $this;
	}

	// private function __set($prop,$value){
	// 	$this->$prop = $value;
	// }

	// Para 'ler' o valor de algumas variáveis protegidas ou privadas.
	// Ex.: if ($s3->temAnexo) {...}
	public function __get($var) {
		switch ($var) {
		case '_erro':
		case 'temAnexo':
		case 'attachList':
		case 'url':
		case 'max_file_size':
		case 'max_files':
		//case 'accepts_mime':
		case 'accepted_types':
		case 'bucket':
		case 'tipo_anexo':
			return $this->$var;

		default:
			# Generate an error, throw an exception, or ...
			return null;
		}
	}

	static function formata_fabrica($fabrica, $len=3) {
		if (!is_int($fabrica))
			return false;

		return str_pad($fabrica, $len, '0', STR_PAD_LEFT);
	}

	static public function utf8_ascii7($str) {
		$dict = array (
			'a' => '/[áàãâä]/', 'e' => '/[éèêë]/', 'i' => '/[íìïî]/', 'o' => '/[óòôõö]/', 'u' => '/[úùüû]/',
			'A' => '/[ÁÀÃÂÄ]/', 'E' => '/[ÉÈÊË]/', 'I' => '/[ÍÌÏÎ]/', 'O' => '/[ÓÒÔÕÖ]/', 'U' => '/[ÚÙÜÛ]/',
			'n' => '/ñ/', 'c' => '/ç/', 'N' => '/Ñ/', 'C' => '/Ç/',
		);

		if (mb_check_encoding($str, 'UTF8'))
			foreach($dict as $k=>$v)
				$dict[$k] .= 'u';
		else
			foreach($dict as $k=>$v)
				$dict[$k] = utf8_decode($dict[$k]);

		return preg_replace(
			array_values($dict),
			array_keys($dict),
			$str
		);
	}

	private	function s3glob($params, $type = "prefix") {
		if (!is_array($params))
			return false;

		$path = $params['path'];
		$mask = $params['mask'];

		if ($type == "pcre") {
			$pcre = $params['pcre'];
		}

		if (!$path and !$mask) {
			return false;
		}

		$root = $this->root_dir;

		if ($type == "pcre") {
			$opts['prefix'] = str_replace('//', '/', $root . S3TEST . $path . '/');
			$opts['pcre'] = "/{$pcre}/i";
		} else {
			$opts['prefix'] = str_replace('//', '/', $root. S3TEST . $path . '/' . $mask);
		}

		try {
			$a = $this->get_object_list($this->bucket, $opts);
		} catch (Exception $e) {
			$a = array();
		}

		if (count($a)>1)
			usort($a, 'cmpFileName'); // Reordena os nomes dos arquivos em sequência "natural"

		return $a;
	}

	public function temAnexos($objArr, $type = "prefix") {

		/***
		 * Vai dar opção de passar  ID do objeto como scalar... p.e.:
		 *
		 * $anexo = new anexaS3('peca', $login_fabrica) ;
		 *
		 * $anexo->temAnexos($peca);
		 *
		 * seria o mesmo que passar
		 * $anexo->temAnexos(array(
		 * 		'mask' => $peca,
		 * 		'path' => '030/'
		 * ));
		 *
		 * De fato, o array será a opção menos utilizada, pois implica saber como é formado o path
		 **/
		if ($this->tipo_anexo == 'fa'):
			$opts = array(
				'mask' => "tbl_admin.$objArr",
				'path' => ''
			);
		elseif ($this->tipo_anexo == 'os'):
			if (is_array($objArr)) {
				$opts = $objArr;
			} else {
				$opts = array(
					'mask' => $objArr,
					'path' => $this->_fabrica
				);

				if ($type == "pcre") {
					$opts["pcre"] = $objArr;
				}
			}
		else:
			$opts = array(
				'mask' => $objArr,
				'path' => $this->_fabrica
			);
		endif;

		$anexos = $this->s3glob($opts, $type); //Processa o array e devolve 'false' se não existe ou array com os nomes dos arquivos que coincidem com os parâmetros
		$this->temAnexo = (count($anexos))?count($anexos):false; //'false' se não existe ou a qtde. que coincide com os parâmetros

		if ($this->temAnexo !== false) {
			$this->attachList = $anexos;

			if ($this->temAnexo == 1) {
				$this->url      = $this->getS3Link2($anexos[0]);
			}else{
				foreach($this->attachList as $link){
					if(preg_match("/$objArr\./",$link)) $this->url = $this->getS3Link2($link);
				}
			}
		} else { //Sem anexos
			$this->attachList = null;
			$this->url        = null;
		}

		return  $this->temAnexo;
	}

	public function getS3Link2($fileS3path) {

		/*if ($this->cdn) {
			// Primeiro confere se o cache do Brasil está OK.
			$link = $this->cdn . $fileS3path;
			if ($fs=@fopen($link, 'r')):
				fclose($fs);
				return $link;
				endif;
		}*/

		if (!$this->auth_time) {
			// Se não têm CDN ou conseguiu abrir, e não precisa de autenticação, tentar com o link do próprio S3...
			try {
				$linkS3 = $this->get_object_url($this->bucket, $fileS3path,0,array('https'=>true));
			} catch (Exception $e) {
				return null;
			}

			if ($fs3 = @fopen($linkS3, 'r')) {
				if (strpos('?xml', fread($fs3, 16))):
					fclose($fs3);
				else:
					fclose($fs3);
					return $linkS3;
				endif;
			}
		}

		// caso não tenha permissão de acesso público, usar a autenticação temporária
		try {
			$linkS3 = $this->get_object_url($this->bucket, $fileS3path, $this->auth_time,array('https'=>true)); // Se for private, usar: , '+3 minutes'
		} catch (Exception $e) {
			return null;
		}

		if ($fs3 = @fopen($linkS3, 'r')) {
			if (strpos('?xml', fread($fs3, 16))):
				fclose($fs3);
			else:
				fclose($fs3);
				return $linkS3;
			endif;
		}
		return false;
	}

	public function uploadFileS3($id, $arquivo, $replace_if_exists = true) {

		global $con;

		// if (!is_numeric($id)) {
		// 	$this->_erro = 'Erro 105A: Identificador inválido!';
		// 	return false;
		// }

		$this->checkID($id);
		$this->temAnexos($id);

		// Verifica se já existe um arquivo para este bucket e ID, se é que não fez antes
		if ($this->temAnexo and !$replace_if_exists) {
			$this->_erro = 'Erro FAE1: Já existe um arquivo para este ID';
			return false;
		}

		if ($this->temAnexo == 1 and $replace_if_exists) {

			if (isCLI)
				echo "Excluíndo {$this->attachList[0]} do bucket " . $this->bucket . chr(10);
			$excluir = $this->delete_object($this->bucket, $this->attachList[0]);
		}

		// Ao aceitar um path de arquivo, permite utilizar a class para upload massivo,
		// ou uma imagem processada antes de fazer o upload...
		if (!is_array($arquivo)) {
			if (!file_exists($arquivo)) {
				$this->_erro = 'Erro F010: O arquivo não foi recebido ou é maior que o tamanho máximo permitido.';
				return false;
			}

			$is_normal_file = true;
			$uploadFile = $arquivo;
			unset($arquivo);
			$arquivo = array(
				'tmp_name' => $uploadFile,
				'name'     => $uploadFile,
				'type'     => mime_content_type($uploadFile),
				'size'     => filesize($uploadFile)
			);

		} else {
			if (!isset($arquivo['tmp_name'])) {
				$this->_erro = 'Erro F020: O arquivo não foi recebido ou é maior que o tamanho máximo permitido.';
				return false;
			}

			$uploadFile = $arquivo['tmp_name'];

			// Se não foi marcado como arquivo 'normal', verifica que foi
			// realmente um arquivo enviado pelo  navegador
			if (!$is_normal_file and !is_uploaded_file($uploadFile)) {
				$this->_erro = 'Erro F030: O arquivo não foi recebido.';
				return false;
			}
		}

		$ext  = pathinfo($arquivo['name'], PATHINFO_EXTENSION);

		if (!preg_match('/'.$this->accepted_types.'/', $arquivo['type'])) {
			$this->_erro = 'Erro F01F: O formato do arquivo não é válido.' . $arquivo['type'];
			return false;
		}

		// Prepara o nome do arquivo. Como por enquanto não tem anexos (fora OS)
		// com mais de um arquivo, a coisa fica simples:
		$nome = $this->root_dir . S3TEST . $this->_fabrica . '/' . $id . '.' . $ext;

		// Era para permitir enviar apenas o array do arquivo se já existe o objID,
		// mas por enquanto é melhor obrigar a passar o ID
		// if (is_null($nome)) {
		// 	$nome = $this->_fabrica . $this->objID . '.' . $ext;
		// }

		$meu_bucket = $this->bucket;

		if (isCLI) {
			echo "Tentando subir o arquivo $uploadFile como $nome " .
				"para o bucket $meu_bucket no S3 da Telecontrol\n";
		}

		$meu_anexo = array (
			//'body'        => file_get_contents($uploadFile),
			'fileUpload'  => $uploadFile,
			'acl'         => $this->access,
			'storage'     => static::STORAGE_REDUCED,
			'contentType' => $arquivo['type']
		);

        $i = 0;
		$retry = true;
		while ($retry and $i++ < 5) {
			try {
				$response = $this->create_object(
					$meu_bucket,
					$nome,
					$meu_anexo
				);
				$retry = false;
			}
			catch (Exception $e) {
				echo $this->_erro = $e->getMessage();
				echo "$i - Erro ao criar o objeto S3: $erroS3<br />";
				sleep(6);
				$retry = true;
			}
		}

		//Deu certo?
		if (is_object($response) and $response->isOK()) {
			$this->checkID($this->objID);// Já reconfere e atualiza os atributos do objeto.
			return true;
		} else {
			$this->_erro .= 'Erro F053: Não foi possível salvar o arquivo. Tente novamente.';
			return false;
		}
	}

	private function checkID($id) {

		global $con;

		if (!is_numeric($id))
			return false;

		$this->objID = $id;

		if ($sql = $this->validaID) {
			$sql = sprintf($sql, $this->objID, $this->_fabrica);
			$res = pg_query($con, $sql);

			if (!is_resource($res)) {
				$this->_erro = 'Erro BC0F: Erro ao consultar os dados.';
				return false;
			}

			if (!pg_num_rows($res)) {
				$this->_erro = 'Erro B10F: ID não existe para o fabricante.' . $sql;
				$this->objID = null;
				return false;
			}
			return ($this->temAnexos($id)>0);
		} else {
			return null;
		}
	}

	public function excluiArquivoS3($path) {

		if (is_numeric($path)) {
			$this->checkID($path);
			$this->temAnexo = $this->temAnexos($path);

			if (!$this->temAnexo) {
				$this->_erro = 'Erro 0D0A: ID sem anexos';
				return false;
			} else {
				$path = $this->attachList[0];
			}
		} else {
			// Confere, se foi passado o path do arquivo, que o mesmo existe no S3...
			try {
				if (!$this->if_object_exists($this->bucket, str_replace('//', '/', $root . S3TEST . $this->_fabrica . '/' . $path))) {
					if (!$this->if_object_exists($this->bucket, str_replace('//', '/', $root . S3TEST .  $path))) {
						$this->_erro = 'Erro EF0F - Arquivo não encontrado';
						return false;
					}
				}
			} catch (Exception $e) {
				$this->_erro = 'Internal AW S3 error, connection timed out.';
				return false;
			}
		}

		if (isCLI and DEBUG===true)
			echo "Excluindo $path do bucket " . $this->bucket . chr(10);

		$retry = true;
		while ($retry and $i++ < 3) {
			try {
				if (is_numeric($path)) {
					$response = $this->delete_object($this->bucket, $path);
				} else {
					$response = $this->delete_object($this->bucket, str_replace('//', '/', $root . S3TEST . $this->_fabrica . '/' . $path));
				}

			}
			catch (Exception $e) {
				echo $this->_erro = $e->getMessage();
				if (isCLI and DEBUG===true)
					echo "$i - Erro ao excluir o objeto S3: $erroS3<br />";
				sleep(6);
				$retry = true;
			}
		}
		//Deu certo?
		if (is_object($response) and $response->isOK()) {
			return true;
		} else {
			$this->_erro .= 'Erro 0D3E: Não foi possável excluir o arquivo do S3. Tente novamente.';
			return false;
		}
	}

	public function setTempoExpiracaoLink($minutos) {

		try {

			if (empty((int) $minutos)) {
				$this->_erro = 'Parâmetro "minutos" inválido';
			} else {
				$this->auth_time = '+'.$minutos.' minutes';
				return true;
			}

		} catch (Exception $e) {
			echo $this->_erro = $e->getMessage();
			return false;
		}

	}


}

$S3_online = class_exists('anexaS3');
