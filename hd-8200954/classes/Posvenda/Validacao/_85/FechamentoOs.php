<?php 

namespace Posvenda\Validacao\_85;

class FechamentoOs
{
	private $os;
	private $fabrica;
	private $con;
	private $erros;

	public function __construct($os, $con)
	{
		$this->os = $os;
		$this->fabrica = 85;
		$this->con = $con;
		$this->erro = '';
	}

	public function validaFechamento()
	{
		$sql = "SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE os = {$this->os}";
		$res = pg_query($this->con,$sql);

		if (pg_num_rows($res) == 0 ){
			$this->erros = "Prezados, por gentileza, abrir chamado junto a Gelopar através do assistente responsável pela sua região, tendo em mãos a nota fiscal de compra e o número da ordem de serviço. Dúvidas, não hesitem em contatar a fábrica. Centro de Assessoria Técnica Fone: +55 41 3607-9000 ou 0800 645 6550.";
			return false;
		} 

		$sql = "SELECT posto,defeito_constatado,serie,sua_os
				FROM   tbl_os                                                                           
				WHERE  tbl_os.os      = {$this->os}
				AND    tbl_os.fabrica = {$this->fabrica}";                                                                        
		$res = pg_query($this->con,$sql);

		if (pg_num_rows($res) == 0) {
			$this->erros = "OS {$this->os} não encontrada para finalizar";                                             
			return false;
		}

		$posto = pg_fetch_result($res, 0, "posto");                                                                             
		$defeito_constatado = pg_fetch_result($res, 0, "defeito_constatado");                                                                             
		$serie = pg_fetch_result($res, 0, "serie");                                                                             
		$sua_os = pg_fetch_result($res, 0, "sua_os");

		if (strlen($defeito_constatado) == 0 ){
			$this->erros = "Favor informar o defeito constatado para a ordem de serviço";
			return false;
		} 

		if (strlen($serie) == 0 ){
			$this->erros = "Favor digitar o Número de Série do produto na Ordem de serviço";
			return false;
		}

		$sql = "SELECT interv_reinc.os                                                                  
		FROM (                                                                          
			SELECT                                                                  
			ultima_reinc.os,(
				SELECT status_os 
				FROM tbl_os_status 
				WHERE fabrica_status= {$this->fabrica} 
				AND tbl_os_status.os = ultima_reinc.os 
				AND status_os IN (62,64) 
				ORDER BY data 
				DESC LIMIT 1) AS ultimo_reinc_status   
				FROM (
					SELECT DISTINCT os FROM tbl_os_status WHERE fabrica_status= {$this->fabrica} AND status_os IN (62,64) 
					) ultima_reinc 
				) interv_reinc                                                                  
				WHERE interv_reinc.ultimo_reinc_status IN (62) 
				AND interv_reinc.os ={$this->os}";
				$res = pg_query($this->con,$sql);

		if (pg_num_rows($res)> 0){
			$this->erro = "OS {$this->os} em intervenção de peça, aguarde autorização da fábrica para finalizar";
			return false;
		}

		$sql = "SELECT fn_valida_os_gelopar_serie({$this->os}, {$this->fabrica})";
		$res = pg_query($this->con,$sql);

		$this->erro = pg_errormessage($this->con);
		if (strlen($this->erro)>0 ){
			return false;
		}

		return true;
		

	}

	public function getErros()
	{
		return $this->erros;
	}
}
