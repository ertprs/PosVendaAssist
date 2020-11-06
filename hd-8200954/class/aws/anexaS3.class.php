<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . '../tdocs.class.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'anexatc.class.php';

if (!is_resource($con)) {
	require __DIR__ . '/../../dbconfig.php';
	require __DIR__ . '/../../includes/dbconnect-inc.php';
}
class AmazonTC extends AnexaTC
{
	private
		$tDocs, $tipo,
		$hasTDocs = false;

	protected static
		$con;

	public function __construct($tipo, $fabrica) {
		parent::__construct($tipo, $fabrica);
		$this->tipo = $tipo; // guarda para quando precisar...
	}

}

class AnexaS3 extends anexaSSS
{

	private
		$tDocs, $tipo,
		$hasTDocs = false;

	protected static
		$con;

	public function __construct($tipo, $fabrica, $idx=null) {

		$this->tDocs = new TDocs($GLOBALS['con'], $fabrica);
		$this->con   = $GLOBALS['con'];
		$this->tipo  = $tipo; // guarda para quando precisar...

		parent::__construct($tipo, $fabrica, $idx);

		if ($idx and !$this->temAnexo) {
			$this->tDocs->setContext($tipo);
			$this->temAnexo = $this->temAnexos($idx);
		}

	}

	public function set_tipo_anexoS3($tipoAnexo) {
		$this->tDocs->setContext($tipoAnexo);
		parent::set_tipo_anexoS3($tipoAnexo);

		return $this;
	}

	public function temAnexos($objId, $type = 'prefix') {
		$count = (!is_numeric($objId)) ?
			$this->tDocs->getDocumentsByName($objId)->attachmentCount :
			$this->tDocs->getDocumentsByRef($objId)->attachmentCount;
		if (!$count) {
			return parent::temAnexos((int)$objId);
		}

		$this->temAnexo = $count;
		$this->hasTDocs = true;
		$tID = false;

		foreach ($this->tDocs->attachListInfo as $attID=>$fileInfo) {
			$tID = $tID ? : $attID;
			$a[] = $fileInfo['tdocs_id'];
			$l[] = $fileInfo['link'];
		}

		$this->url = $l[0];
		$this->attachList = $a;
		return $count;
	}

	public function getS3Link($path) {
		if ($this->hasTDocs or !strpos($path, '/')) {
			return $this->tDocs->getDocumentLocation($path);
		}

		return parent::getS3Link($path);
	}

	public function uploadFileS3($id, $file, $replace=true) {
		$ret = $this->tDocs->uploadFileS3($file, $id, $replace);

		if (!$ret and $this->tDocs->erro) {
			$this->_erro = $this->tDocs->erro;
		}
		return $ret;
	}

	public function excluiArquivoS3($path) {
		if (strpos($path, '/')===false and strlen($path) > 40) {
			if ($this->tDocs->getDocumentsByRef($path)->hasAttachment) {
				$ret = $this->tDocs->removeDocumentById($path);
				if ($ret === false and $this->tDocs->error) {
					$this->_erro = $this->tDocs->error;
				}
				return $ret;
			}
		}
		return parent::excluiArquivoS3($path);
	}

}

$S3_online = class_exists('anexaS3');
