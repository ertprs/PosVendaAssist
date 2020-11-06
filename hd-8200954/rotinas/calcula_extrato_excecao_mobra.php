<?php 


/**
* Class que vai fazer todo o calculo de excecao da mão de obra nos extratos da masterfrio
* @author Gabriel Silveira
*/
class ExcecaoMobra
{

	/**
	 * ID da Fábrica
	 * @var int
	 */
	private $fabrica;

	/**
	 * ID do extrato que será usado para fazer a exceção de mao de obra
	 * @var int
	 */	
	private $extrato;


	/**
	 * Valor da mão de obra da exceção
	 * @var float
	 */
	private $valorMobra;

	/**
	 * Valor da mão de obra adicional (mao de obra + adicional)
	 * @var float
	 */
	private $valorMobraAdicional;

	/**
	 * Valor do percentual de mão de obra que será incluido na mao_de_obra (mao  de obra + percentual)
	 * @var float
	 */
	private $valorMobraPercentual;

	/**
	 * Dados da OS do extrato
	 * @var array
	 */
	private $dadosOs = array();

	/**
	 * ID da excecao de mão de obra que será usada para fazer o update
	 * @var array
	 */
	private $dadosExcecaoMobra = array();

	/**
	 * conexão com o banco
	 * @var resource
	 */
	private $con;

	/**
	 * Array com erros que serão disparados pelas exceptions.
	 * @var array
	 */
	private $erros = array();
	
	public function __construct($extrato,$fabrica)
	{

		global $con;
		try {
			
			$this->con = $con;
			$this->fabrica = $fabrica;
			$this->extrato = $extrato;


			$this->checkExtrato($this->extrato);

			$this->dadosOs = $this->getOsDoExtrato();
			// $this->dadosExcecaoMobra = $this->getExcecaoMobra();

		} catch (Exception $e) {
			$this->setErro($e);
		}

	}

	/**
	* Seta o erro para o array. Joga para o array todo o conteudo da exception.
	* @param Object Exception $e
	*/
	public function setErro(Exception $e)
	{

		$this->erros[] = "Descrição do erro: ".$e->getMessage()."<br />Arquivo: ".$e->getFile()."<br />Linha: ".$e->getLine();

	}

	/**
	* Devolve o array com erros
	* @return array $this->erros
	* @author Gabriel
	*/
	public function getErros()
	{

		return $this->erros;

	}

	public function checkExtrato($extratoProcurar)
	{

		$query = pg_query($this->con, "SELECT extrato FROM tbl_extrato WHERE extrato = $extratoProcurar");

		if (pg_num_rows($query) == 0) {
			throw new Exception('Extrato inexistente: ' . $extratoProcurar . "\n");
		}else{
			return true;
		}
		
	}

	/**
	 * Metodo que irá retornar as exceções de mão de obra da fabrica
	 * @return $this->excecao_mobra array
	 * @author Gabriel <gabriel.silveira@telecontrol.com.br>
	 * @param array $searchBy parametro passado para definir as condições da pesquisa da exceção de mão de obra, para pegar pela OS.
	 * Array contents: produto, familia, solucao_os, tipo_atendimento, peca_lancada.
	 */
	function getExcecaoMobra($searchBy = array())
	{

		$conds = "";
		$fabrica = $this->fabrica;
		if (!empty($searchBy)) {
			
			foreach ($searchBy as $key => $value) {
			
				switch ($key) {

					case 'produto':
						if (!empty($value)) {
							$conds .= "AND ( (tbl_excecao_mobra.produto is NOT NULL and tbl_excecao_mobra.produto = $value ) OR tbl_excecao_mobra.produto IS NULL ) 
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.produto is NULL 
							";
						}
						break;

					case 'posto':
						if (!empty($value)) {
							$conds .= "AND ( (tbl_excecao_mobra.posto is NOT NULL and tbl_excecao_mobra.posto = $value ) OR tbl_excecao_mobra.posto IS NULL ) 
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.posto is NULL 
							";
						}
						break;

					case 'linha':
						if (!empty($value)) {
							$conds .= "AND ( (tbl_excecao_mobra.linha is NOT NULL and tbl_excecao_mobra.linha = $value ) OR tbl_excecao_mobra.linha IS NULL ) 
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.linha is NULL 
							";
						}
						break;

					case 'familia':
						if (!empty($value)) {
							$conds .= "AND ( (tbl_excecao_mobra.familia is NOT NULL and tbl_excecao_mobra.familia = $value ) OR tbl_excecao_mobra.familia IS NULL ) 
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.familia is NULL 
							";
						}
						break;

					case 'solucao_os':
						if (!empty($value)) {
							$conds .= "AND ( (tbl_excecao_mobra.solucao is NOT NULL and tbl_excecao_mobra.solucao = $value ) OR tbl_excecao_mobra.solucao IS NULL ) 
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.solucao is NULL 
							";
						}
						break;

					case 'tipo_atendimento':
						if (!empty($value)) {
							$conds .= "AND ( (tbl_excecao_mobra.tipo_atendimento is NOT NULL and tbl_excecao_mobra.tipo_atendimento = $value ) OR tbl_excecao_mobra.tipo_atendimento IS NULL ) 
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.tipo_atendimento is NULL 
							";
						}
						break;

					case 'peca_lancada':
						if ($value > 0) {
							$conds .= "AND tbl_excecao_mobra.peca_lancada is TRUE 
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.peca_lancada is FALSE 
							";
						}
						break;

					default:
						# code...
						break;
				
				}

			}

		}
		
		$sql = "SELECT posto, 
				produto, 
				familia, 
				linha,
				solucao, 
				tipo_atendimento, 
				peca_lancada, 
				mao_de_obra, 
				adicional_mao_de_obra, 
				percentual_mao_de_obra 
			FROM tbl_excecao_mobra 
			WHERE fabrica = $fabrica 
			$conds
			ORDER BY posto,produto,solucao,linha,familia,tipo_atendimento,peca_lancada";

		$res = pg_query($this->con, $sql);

		$return = pg_fetch_all($res);
		return $return;

	}

	/**
	 * Metodo que retorna todas as OS's do extrato passado.
	 * @return array pg_fetch_all($res)
	 */
	public function getOsDoExtrato()
	{
		$extrato = $this->extrato;
		$fabrica = $this->fabrica;

		$sql = "SELECT 	tbl_os.os, 
				tbl_os.produto, 
				tbl_produto.familia, 
				tbl_os.solucao_os,
				tbl_os.posto,
				tbl_os.tipo_atendimento 
			FROM tbl_os 
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os
			JOIN tbl_produto on tbl_os.produto = tbl_produto.produto 
			WHERE tbl_os_extra.extrato = $extrato
			AND tbl_os.fabrica = $fabrica
			";

		$res = pg_query($this->con,$sql);
		
		$return = pg_fetch_all($res);
		
		return $return;

	}

	/**
	 * Metodo que irá fazer o calculo 
	 * @return 
	 */
	public function calculaExcecaoMobra()
	{
		$searchFields = array();
		try {
			
			if (!empty($this->dadosOs)) {
				$i = 0;
				foreach ($this->dadosOs as $key) {
					
					$os 				= $key['os'];
					$produto 			= $key['produto'];
					$linha 				= $key['linha'];
					$familia 			= $key['familia'];
					$posto  			= $key['posto'];
					$solucao_os 		= $key['solucao_os'];
					$tipo_atendimento 	= $key['tipo_atendimento'];

					$sql = "SELECT count(*)
						FROM tbl_os_item
						INNER JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
						INNER JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
						WHERE tbl_os.os = $os";

					$res = pg_query($this->con, $sql);
					
					$peca_lancada = pg_fetch_result($res, 0, 0);

					$searchFields = array(
								'produto'          => $produto,
								"linha"            => $linha,
								"familia"          => $familia, 
								"solucao_os"       => $solucao_os, 
								"tipo_atendimento" => $tipo_atendimento, 
								"peca_lancada"     => $peca_lancada,
								"posto"			   => $posto
							);
					
					$this->dadosExcecaoMobra = $this->getExcecaoMobra($searchFields);
					
					if(!empty($this->dadosExcecaoMobra[0]['mao_de_obra'])){

						if ($this->setNewMobra("mao_de_obra",$this->dadosExcecaoMobra[0]['mao_de_obra'],$os) != "ok" ){
							throw new Exception("Erro ao atualizar mão de obra da OS: $os");
							echo $this->setNewMobra("mao_de_obra",$this->dadosExcecaoMobra[0]['mao_de_obra'],$os);
						}

					}

					if (!empty($this->dadosExcecaoMobra[0]['adicional_mao_de_obra'])) {
						if ($this->setNewMobra("adicional_mao_de_obra",$this->dadosExcecaoMobra[0]['adicional_mao_de_obra'],$os) != "ok"){
							throw new Exception("Erro ao atualizar mão de obra da OS: $os");
						}
					}

					if (!empty($this->dadosExcecaoMobra[0]['percentual_mao_de_obra'])) {
						if ($this->setNewMobra("percentual_mao_de_obra",$this->dadosExcecaoMobra[0]['percentual_mao_de_obra'],$os) != 'ok'){
							throw new Exception("Erro ao atualizar mão de obra da OS: $os");
						}
					}

				}

			}

		} catch (Exception $e) {
			$this->setErro($e);
		}

	}

	/**
	 * [setNewMobra description]
	 * Metodo que vai fazer o update da mao de obra na tbl_os
	 * @param [string] $tipo  tipo do update que vai fazer, mao_de_obra, adicional_mao_de_obra, percentual_mao_de_obra;
	 * @param [float] $valor valor que vai ser calculado
	 * @param [string] $os    os que vai ser usada
	 */
	public function setNewMobra($tipo,$valor,$os)
	{
		switch ($tipo) {

			case 'mao_de_obra':
				$updateValue = "mao_de_obra = $valor";
				break;
			
			case 'adicional_mao_de_obra':
				$updateValue = "mao_de_obra = mao_de_obra + $valor";
				break;

			case 'percentual_mao_de_obra':
				$updateValue = "mao_de_obra = mao_de_obra * (1 + ($valor / 100::float))";
				break;

			default:
				# code...
				break;

		}

		pg_query($this->con,"BEGIN TRANSACTION");

		$sql = "UPDATE tbl_os SET 
				$updateValue
			WHERE os = $os
			";

		$res = pg_query($this->con,$sql);
		
		$erros = pg_last_error($this->con);

		$sql = "SELECT fn_totaliza_extrato($this->fabrica,$this->extrato)";

		$res = pg_query($this->con,$sql);
		
		$erros = pg_last_error($this->con);

		if (!empty($erros)) {
			pg_query($this->con,"ROLLBACK TRANSACTION");
			return "erro|$erros";
		}else{
			pg_query($this->con,"COMMIT TRANSACTION");
			return "ok";
		}

	}

}

$cem = new ExcecaoMobra(1644492,40);
$cem->calculaExcecaoMobra();


