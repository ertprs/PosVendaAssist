<?php
/**
  *		@description Classe abstrata para validação de regras de OS. Precisa ser extendida por uma classe da fabrica que irá utilizá-la - HD 220549
  *		@author Brayan L. Rastelli.
  */
  
include_once 'iValida.php';
  
abstract class ValidaRegras  implements iValida {

	protected $os;
	protected $fabrica;
	protected $msg_erro = array();
	protected $con;
	protected $tbl_os = 'tbl_os';
	protected $constatado = 'defeito_constatado';
	protected $reclamado = 'defeito_reclamado';
	protected $campoOS			=	'os';

	/**
	  *	@description Classe construtora, seta a propriedade $this->con pegando a global $con
	  *	@author Brayan L. Rastelli
	  */
	public function __construct() {
	
		global $con;
		global $login_fabrica;
		
		$this->con = $con;
		
		if ( !empty ($login_fabrica) ) {
			$this->setFabrica( $login_fabrica );
		}
		
		return;
	
	}
	
	public function setFabrica($fabrica) {
	
		$this->fabrica = $fabrica;
	
	}
	
	/**
	  *	@description Setar a propriedade OS
	  *	@param int $os
	  *	@author Brayan L. Rastelli
	  */
	public function setOS($os) {
		
			return $this->os = $os;
		
	}
	
	/**
	  *	@description Seta valor na propriedade $this->tbl, pois no webservice bosch é usada a tbl_os_externa
	  *	@param string $tbl - Default tbl_os
	  */
	public function setTable ( $tbl ) {
	
		if ( $tbl == 'tbl_os_externa' )
			$this->campoOS = 'os_externa';
			
		$this->tbl_os	=	$tbl;
		
		return;
	
	}
	
	/**
	  *	@description Setar campos.. pois tbl_os_externa usa campo defeito_constatado_descricao e defeito_reclamado_descricao
	  *	@param string $reclamado, string $constatado
	  *	@author Brayan L. Rastelli
	  */
	public function setCamposDefeitos($reclamado, $constatado) {
	
		$this->constatado	=	$constatado;
		$this->reclamado	=	$reclamado;
		
		return;
	
	}
	
	/**
	  *	@description Retorna os erros na validação
	  *	@return array $msg_erro
	  */
	public function getErrors() {
		
		return $this->msg_erro;
		
	}
	
	/**
	  *	@description Validar se existe defeito constatado
	  *	@params $msg - Mensagem retornada de erro (opcional)
	  *	@author Brayan L. Rastelli
	  */
	public function validaDefeitoConstatado($msg =  'Preencha o Defeito Constatado') {
	
		if ( $this->tbl_os == 'tbl_os_externa' ) {
		
			$sql = "SELECT os FROM {$this->tbl_os} WHERE {$this->campoOS} = {$this->os} AND {$this->constatado} IS NOT NULL AND LENGTH( TRIM( {$this->constatado})) > 0";
		
		}
		else {
		
			$sql = "SELECT os FROM {$this->tbl_os} WHERE {$this->campoOS} = {$this->os} AND {$this->constatado} IS NOT NULL";
		
		}
		$res = pg_query($this->con, $sql);
			
		if ( pg_num_rows($res) == 0 ) {
			$this->msg_erro[] = $msg;
		}
		
		return;
		
	}
	
	 /**
	   *	@description Validar se existe defeito reclamado
	   *	@params $msg - Mensagem retornada de erro (opcional)
	   *	@author Brayan L. Rastelli
	   */
	public function validaDefeitoReclamado($msg =  'Preencha o Defeito Reclamado') {
		
		if ( $this->tbl_os == 'tbl_os_externa' ) {
			$sql = "SELECT os FROM {$this->tbl_os} WHERE {$this->campoOS} = {$this->os} AND {$this->reclamado} IS NOT NULL AND LENGTH( TRIM ({$this->reclamado}) ) > 0";
		}
		else {
		
			$sql = "SELECT os FROM {$this->tbl_os} WHERE {$this->campoOS} = {$this->os} AND {$this->reclamado} IS NOT NULL";
		
		}
		$res = pg_query($this->con, $sql);
			
		if ( pg_num_rows($res) == 0 ) {
			$this->msg_erro[] = $msg;
		}
		
		return;
		
	}
	
	/**
	  *	@description Valida se existe tipo de atendimento
	  *	@params string $msg
	  *	@author Brayan L. Rastelli
	  */
	 public function validaTipoAtendimento($msg = 'O campo Tipo de Atendimento é Obrigatório') {
	 
		$sql = "SELECT os FROM {$this->tbl_os} WHERE {$this->campoOS} = {$this->os} AND tipo_atendimento IS NOT NULL";
		$res = pg_query($this->con, $sql);
			
		if ( pg_num_rows($res) == 0 ) {
			$this->msg_erro[] = $msg;
		}
		
		return;
	 
	 }
	 
	  public function validaCausaDefeito($msg = 'Informe o defeito para a OS') {

	  	$cond = '';
	 
		if ( $this->tbl_os == 'tbl_os_externa' ) {
			$cond = "AND LENGTH( TRIM (causa_defeito) ) > 0";
		}
		$sql = "SELECT os FROM {$this->tbl_os} WHERE {$this->campoOS} = {$this->os} AND causa_defeito IS NOT NULL $cond";
		$res = pg_query($this->con, $sql);
			
		if ( pg_num_rows($res) == 0 ) {
			$this->msg_erro[] = $msg;
		}
		
		return;
	 
	 }
	 
	 public function validaSolucao($msg = 'Informe a Solução para a OS') {
	 
	 	$cond = '';

		if ( $this->tbl_os == 'tbl_os_externa' ) {
			$cond = "AND LENGTH( TRIM (solucao_os) ) > 0";
		}
		$sql = "SELECT os FROM {$this->tbl_os} WHERE {$this->campoOS} = {$this->os} AND solucao_os IS NOT NULL $cond";
		$res = pg_query($this->con, $sql);
			
		if ( pg_num_rows($res) == 0 ) {
			$this->msg_erro[] = $msg;
		}
		
		return;
	 
	 }
	 
	 /**
	   *	@description Classe de validação de datas.
	   *	@author Brayan L.Rastelli
	   *	@params array $datas (opcional) , string $msg (opcional)
	   */
	 public function verificaDatas ( $datas = array(), $msg = 'O campo %s é obrigatório.' ) {

		if ( empty ($datas) ) {

			$datas = array (

				'data_digitacao'	=>	'Data de Digitação',
				'data_nf'			=>	'Data de Compra',
				'data_abertura'	=>	'Data de Abertura'

			);

		}

		foreach ($datas as $key => $value) {

				$sql = "SELECT os FROM {$this->tbl_os} WHERE {$this->campoOS} = {$this->os} AND {$key} IS NOT NULL";
				$res = pg_query($this->con, $sql);
					
				if ( pg_num_rows($res) == 0 ) {
									
					$this->msg_erro[] = sprintf ( $msg, $value);
					
				}

		}
		
		return;
	 
	 }
	 
	 /**
	   *	@description Valida dados do consumidor
	   *	@params  array $campos (Opcional), string $msg
	   *	@author Brayan L. Rastelli
	   */
	 public function verificaConsumidor( $campos = array(), $msg = 'O campo %s é obrigatório.' ) { 
	 
		if ( empty ( $campos ) ) {
		
			// @todo Adicionar campos obrigatórios padrões para as fabricas aqui.
			
			if ( $this->tbl_os == 'tbl_os' ) {

				$campos = array (
				
					'consumidor_nome'	=>	'Nome do Consumidor'
				
				);

			} else {

				$campos = array (
				
					'cliente_nome'	=>	'Nome do Consumidor'
				
				);

			}
		
		}
		
		foreach ($campos as $key => $value) {

				$sql = "SELECT os FROM {$this->tbl_os} WHERE {$this->campoOS} = {$this->os} AND {$key} IS NOT NULL AND LENGTH( TRIM ({$key}) ) > 0";
				$res = pg_query($this->con, $sql);
					
				if ( pg_num_rows($res) == 0 ) {
					
					$this->msg_erro[] = sprintf ( $msg, $value) ;
					
				}

		}
	 
	}
	 
	 /**
	   *	@description Valida se o produto existe
	   *	@params string $referencia, string $msg
	   *	@author Brayan L. Rastelli
	   */
	 public function validaProduto ($referencia, $msg = 'Produto %s Inválido') {
	 
		$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha USING(linha) WHERE referencia = '$referencia' AND fabrica = {$this->fabrica}";
		$res = pg_query($this->con,$sql);
		
		if ( pg_num_rows($res) == 0 ) {
			
			$this->msg_erro[] = sprintf ( $msg, $referencia) ;
		
		}
	 
	 }
	 
	 /**
	   *	@description Método de validação de OS, se o parametro $1 for true, irá validar no banco ( fn_valida_os )
	   *	@params bool $valida, string $msg
	   *	@author Brayan L. Rastelli
	   */
	 public function validaSerieObrigatoria($valida = FALSE, $msg = 'Digite um número de série válido') {
	 
		$sql = "SELECT os FROM {$this->tbl_os} WHERE {$this->campoOS} = {$this->os} AND serie IS NOT NULL AND LENGTH( TRIM (serie) ) > 0";
		$res = pg_query($this->con, $sql);
			
		if ( pg_num_rows($res) == 0 ) {
			$this->msg_erro[] = $msg;
		}
		else if ($valida === TRUE) {
		
			$sql = "SELECT fn_valida_os($this->os, $this->fabrica)";
			$res = @pg_query($this->con,$sql);
			$erro = pg_errormessage($this->con);
			if ( !empty($erro) ) {
				
				$this->msg_erro[]	=	$erro;
			
			}
		
		}
		
		return;
	 
	 }

}
