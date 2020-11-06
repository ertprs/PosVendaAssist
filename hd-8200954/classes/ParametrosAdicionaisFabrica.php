<?php 
class ParametrosAdicionaisFabrica{
  
  private $parametrosAdicionaisObject;

  protected $con;

  protected $fabrica;

  public function __construct($fabrica){
    global $con;
    $this->con = $con;
    $this->fabrica = $fabrica;
    $json = $this->getParametrosAdicionaisDb();
    $this->parametrosAdicionaisObject = json_decode($json);

  }
  
  private function getParametrosAdicionaisDb(){
    $sql = "SELECT parametros_adicionais
            FROM tbl_fabrica
            WHERE fabrica = {$this->fabrica}";
    $res = pg_query($this->con, $sql);

    return pg_fetch_result($res, 0, "parametros_adicionais");

  }
  public function getParametrosAdicionaisObject(){
    return $this->parametrosAdicionaisObject;

  }

}

