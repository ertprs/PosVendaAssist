<?php

/**
 * Helper para funções gerais de manipulação de arquivos.
 * @author Brayan
 * @version 1.0
 */
class FileHelper {

	/**
	 * @var Array $types contendo mime types validos pré-definidos pelo sistema
	 */
	private $types = array(
		'image' => array('image/jpeg', 'image/png', 'image/x-png', 'image/pjpeg'),
		'doc'	=> array('application/pdf', 'application/msword')
	);

	/**
	 * @var string Diretorio para ser usado para leitura/gravacao de arquivos
	 */
	private $directory;

	/**
	 * @var bool true para gravar arquivos dentro de pastas de acordo com fabrica/ano/mes
	 */
	private $timestamp = false;

	/**
	 * @var string nome do arquivo.
	 */
	public $filename;

	/**
	 * Default 1mb
	 * @var integer $size tamanho maximo dos anexos
	 */
	public $size = 1000000;

	/**
	 * Apenas verifica se é um diretorio valido, antes de setar
	 * @param string $dir path do diretório, pode ser relativo (ao arquivo da instancia), ou absoluto.
	 */
	public function setDirectory($dir) {

		if (!is_dir($dir)) {

			throw new Exception("Diretório $dir não encontrado");			

		}

		$this->directory = rtrim($dir, '/');

	}

	public function setTimestamp($value) {

		//@todo função para criar pasta: $this->directory/fabrica/ano/mes caso não exista

	}

	/**
	 * Validar arquivos enviados por MIME TYPE
	 * @param Array $files podendo ser um array de um arquivo, ou array contendo vários arquivos
	 * @param string $type definindo se será um documento, imagem, etc, para verificar os tipos permitidos por padrão
	 * @param Array $types caso queira especificar os tipos permitidos na validação
	 * @example validando anexo de imagem com as extensões padrões: $helper->file->validate($_FILES['foto'], 'image');
	 * @example validando anexo de imagem para somente imagens jpg: $helper->file->validate($_FILES['foto'], 'image', array('image/jpg'));
	 * @return Mixed true caso valide ou não teha arquivo, exception quando der erro. Para usar, colocar sempre num bloco try.. catch
	 */
	public function validate ($files, $type, $types = array() ) {
	
		if (empty($files)) {
			throw new Exception('Passe um array de $_FILE');
		}

		if ( !empty($types) ) {

			$this->types[$type] = $types;

		}

		if ( !is_array($files['type']) ) {

			if ( !empty($files['type']) && !in_array( $files['type'], $this->types[$type]) ){
				throw new InvalidArgumentException("Arquivo {$files['name']} Inválido");
			}

			if ( $files['size'] > $this->size ) {

				throw new InvalidArgumentException("Tamanho do arquivo {$files['name']} não permitido");

			} //@todo testar

			return true;

		}

		foreach($files['type'] as $k => $v) {

			if (empty($v))
				continue;

			if ( !in_array($v, $this->types[$type]) ) {
				
				throw new InvalidArgumentException("Arquivo {$files['name'][$k]} inválido");

			}

			if ( $files['size'][$k] > $this->size ) {

                throw new InvalidArgumentException("Tamanho do arquivo {$files['name'][$k]} não permitido");

            } // @todo testar

		}

		return true;

	}

	/**
	 * Efetua upload de arquivos, antes de chama-la executar o metodo validate (opcional) e setDirectory (recomendado).
	 * @param Array $files contendo um ou mais arquivos(padrão $_FILE)
	 * @param String $id podendo ser nome de arquivo ou um id, para usar como nome do arquivo salvo, p.e. 1234.jpg, 1234-2.jpg
	 * @return Mixed true caso sucesso; exception caso falha
	 */
	public function upload( $files, $id ) {

		if (empty($files) || !isset($files['name'])) {

			throw new Exception("Falha nos parametros do arquivo");

		}

		if (!is_writable($this->directory)) {
		    
		    throw new Exception("Permissão negada no ".$this->directory);

		}

		if (!is_array($files['name'])) {

			if (empty($files['name'])) 
				throw new Exception("Arquivo não encontrado");

			$ext = pathinfo($files['name'][$k], PATHINFO_EXTENSION);

			if ( !move_uploaded_file($files['tmp_name'], $this->directory . '/' . $id . '.' . $ext) )
				throw new Exception("Falha ao gravar arquivo");

			return true;

		}

		foreach($files['tmp_name'] as $k => $file) {

			if ( empty($file) )
				continue;

			$ext = pathinfo($files['name'][$k], PATHINFO_EXTENSION);

			$filename = $k == 0 ? $id : $id . '-' . $k;
			
			if ( !move_uploaded_file($file, $this->directory . '/' . $filename . '.' . $ext) ) {

				throw new Exception("Falha ao enviar arquivo {$files['name'][$k]}");

			}

		}

	}

}
