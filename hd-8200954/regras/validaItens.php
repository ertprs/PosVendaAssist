<?php

include_once 'validaRegras.php';
include_once 'iValida.php';

abstract class ValidaItens extends ValidaRegras implements iValida{

	private $tbl_os_item = 'tbl_os_item';

	public function __construct () {
	
		parent::__construct();
	
	}
	
	public function setTblOSItem ($tbl) {
	
		$this->tbl_os_item = $tbl;
	
	}
	
	public function pecasObrigatorias($msg = 'É necessário lançar pelo menos um item na OS') {
	
		if ( $this->tbl_os == 'tbl_os' ) {
		
			$join_os_produto 	= ' JOIN tbl_os_produto USING(os) ';
			$join_os_item		= ' JOIN tbl_os_item USING(os_produto) ';
		
		}
		else {
		
			$join_os_item		= ' JOIN tbl_os_externa_item USING(os_produto) ';
		
		}
		
		$sql = "SELECT {$this->campoOS} FROM {$this->tbl_os} $join_os_produto $join_os_item WHERE os = {$this->os} AND fabrica = {$this->fabrica} AND qtde > 0";
		$res = pg_query($this->con,$sql);
		
		if ( pg_num_rows($res) ) {
		
			$this->msg_erro[] = $msg;
		
		}
		
		return;
		
	}

}