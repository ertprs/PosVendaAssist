<?php
namespace model;


require_once S3CLASS;

class Produto extends Model{

	private $amazonClient;
	private $explodeViewBucket;
	private $explodeViewPath;

	public function __construct($connection=null){
		parent::__construct($connection);
		$this->amazonClient = new \AmazonS3();
		$this->explodeViewBucket = "br.com.telecontrol.posvenda-downloads";
		$this->explodeViewPath = S3TEST?"":"testes/"."vista_explodida/";
	}

	protected function explodeViewPrefix($product){
		return $this->explodeViewPath.$this->getFactory().'_'.$product.'_';
	}

	public function listExplodeViews($product,$viewId=''){
		$bucket = $this->explodeViewBucket;
		$prefix = $this->explodeViewPrefix($product).$viewId;
		$list = $this->amazonClient->get_object_list($bucket,array('prefix'=>$prefix));
		return $list;
	}

	public function getExplodeViewImages($product){
		$imgUrls = array();
		$bucket = $this->explodeViewBucket;
		foreach ($this->listExplodeViews($product) as $explodeView) {
			list($factory,$product,$id) = explode('_',$explodeView);
			$file = $this->explodeViewPath.$explodeView;
			$imgUrls[$id] = $this->amazonClient->get_object_url($bucket,$file,'+10 minutes');
		}
		return $imgUrls;
	}

	public function getExplodeView($product,$viewId){
		$bucket = $this->explodeViewBucket;
		$views = $this->listExplodeViews($product,$viewId);
		if(count($views) != 1)
			return false;
		$file = $this->explodeViewPath.$views[0];
		return $this->amazonClient->get_object_url($bucket,$file,'+10 minutes');
	}

	private function generateExplodeViewNextId($product){
		$views = $this->listExplodeViews($product);
		if(empty($views))
			return 1;
		$maxId = 1;
		foreach($views as $view){
			list($factory,$product,$id) = explode('_',$view);
			$maxId =  ($id > $maxId)?$id:$maxId;
		}
		return $maxId + 1;
	}

	public function addExplodeView($product,$imageFile){
		$bucket = $this->explodeViewBucket;
		$nextId = $this->generateExplodeViewNextId($product);
		$path = $this->explodeViewPrefix($product).$nextId;
		$opts = array("acl" => \AmazonS3::ACL_PRIVATE, "fileUpload" => $imageFile, "storage" => \AmazonS3::STORAGE_STANDARD);
		$response = $this->amazonClient->create_object($bucket,$path,$opts);
		if($response->isOK()){
			return $nextId;
		}
		return false;
	}

	public function removeExplodeView($product,$viewId){
		$bucket = $this->explodeViewBucket;
		$views = $this->listExplodeViews($product,$viewId);
		if(count($views) != 1){
			return false;
		}
		$response = $this->amazonClient->delete_object($bucket,$views[0]);
		return $response->isOK();
	}


}