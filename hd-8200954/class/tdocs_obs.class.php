<?php 
	
class TDocs_obs {
	
	static private $conn; // recurso de conexуo ao banco de dados
	private $fabrica;
	private $contexto;
	private $obs;
	//contexto credenciamento
	//referencia tbl_credenciamento
	//referencia_id id da tbl_credenciamento  

	public function __construct($conn, $fabrica, $contexto) {
		$this->conn = $conn; 

		if (!is_resource($conn))
			throw new Exception ('Sem conexуo ao banco de dados!');

		$this->fabrica = $fabrica;

		if (!empty($contexto))
			$this->contexto = $contexto;
		if (!is_numeric($fabrica))
			throw new Exception("Valor '$fabrica' invсlido para o fabricante!");
	}


	public function gravaObservacao($obs, $referencia, $referencia_id){
		$tdocs_id = md5(time().$this->fabrica.$referencia);

		if(strpos($obs, 'img') !== false OR strpos($obs, 'base64') !== false){
			return array('retorno'=> 'erro', 'msg' => 'informaчуo invсlida');
			exit;
		}
		$sql = "INSERT INTO tbl_tdocs (tdocs_id, fabrica, contexto, situacao, obs, referencia, referencia_id) VALUES ('$tdocs_id', ".$this->fabrica.", '".$this->contexto."', 'ativo', '$obs', '$referencia', '$referencia_id')";
		$res = pg_query($this->conn, $sql);

		if(strlen(pg_last_error($this->conn))==0){
			return true;
		}else{
			return false;
		}
	}

	public function atualizaObservacao($obs, $referencia_id){
		$sql = "UPDATE tbl_tdocs SET obs = '$obs' WHERE referencia_id = $referencia_id and fabrica = ".$this->fabrica ;
		$res = pg_query($this->conn, $sql);

		if(strlen(pg_last_error($this->conn))==0){
			return true;
		}else{
			return false;
		}
	}

	public function getObservacao($referencia_id){
		$sql = "SELECT obs FROM tbl_tdocs WHERE fabrica = ".$this->fabrica." AND referencia_id = $referencia_id and contexto = '".$this->contexto."'";
		$res = pg_query($this->conn, $sql);
		if(pg_num_rows($res)>0){
			$obs = pg_fetch_result($res, 0, obs);
			return array('observacao' => "$obs");
		}else{
			return false;
		}
	}
}


?>