<?php

require_once('validaRegras.php'); // Classe generica de validação de OS
require_once('validaItens.php'); // Classe generica de validação de Itens da OS

class ValidaRegrasBosch extends ValidaRegras{

	/**
	  *	@description Instancia o objeto e o construtor de ValidaRegras, pegando seus métodos e propriedades.
	  **/
	public function __construct() {
		
		parent::__construct();
		
	}
	
	/**
	  * @description Validar OS da fabrica. Chama nesse método os métodos da classe extendida ValidaRegras que a fabrica irá utilizar, e os métodos criados aqui.
	  * @author Brayan L. Rastelli
	  */
	public function valida() {

		$this->setFabrica(20);
		
		$this->validaDefeitoConstatado();
		$this->validaDefeitoReclamado();
		$this->validaTipoAtendimento(); // @todo descomentar ao criar campo
		$this->validaCausaDefeito();
		$this->validaSolucao('Informe a identifica��o para a OS');
		
		$this->verificaConsumidor();
		
		$dados = array (
			'data_nf'	=>	'Data de Compra',
			'data_abertura'	=>	'Data de Abertura'
		);
		
		$this->verificaDatas($dados);
	
	}

}
	
class ValidaItensBosch extends ValidaItens{

	public function __construct() {
	
		parent::__construct();
	
	}
	
	public function valida () {	}
	
	public function validaPeca($ref, $msg = 'Pe�a %s n�o Encontrada') {

		$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$ref' AND fabrica = {$this->fabrica}";
		$res = pg_query($this->con,$sql);
		
		if ( pg_num_rows($res) == 0 ) {
		
			$this->msg_erro[] = sprintf ($msg, $ref);
		
		}
	
		return;

	}

}
	
/* Exemplos:
	$object = new ValidaRegrasBosch();
	
	$object->setOS(1234);

	$object->valida();
	
	$msg_erro = $object->getErrors();

	echo '<pre>'; print_r($msg_erro); echo '</pre>';
*/
