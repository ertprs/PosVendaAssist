<?php

namespace Posvenda;

class ExcecaoMobra extends \Posvenda\Model\AbstractModel
{
	/**
	 * ID da Fábrica
	 * @var int
	 */
	protected $fabrica;

	/**
	 * Período da OS
	 * @var int
	 */
	protected $periodo_conserto;

	/**
	 * Usa  regra de eficiência
	 * @var int
	 */
	protected $usa_eficiencia;

	/**
	 * ID da OS que será usado para fazer a exceção de mao de obra
	 * @var int
	 */
	protected $os;

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

	private $pdo;

	public function __construct($os, $fabrica, $skip_adicional = false)
	{

        if ((false === $skip_adicional) and (file_exists(__DIR__ . '/./Fabricas/_' . $fabrica . '/ExcecaoMobraAdicional.php'))) {
            $excecaoAdicional = '\\Posvenda\\Fabricas\\_' . $fabrica . '\\ExcecaoMobraAdicional';
            return new $excecaoAdicional($os, $fabrica);
        }

        if ((file_exists(__DIR__ . '/regras/mao_de_obra/' . $fabrica . '.json'))) {
    		$this->usa_eficiencia = \Posvenda\Regras::get("usa_eficiencia", "mao_de_obra", $fabrica);
        }

        parent::__construct('tbl_excecao_mobra');

		$this->os               = $os;
		$this->fabrica          = $fabrica;
		$this->pdo              = $this->getPDO();

		if($this->usa_eficiencia){
			$sql = "SELECT COUNT(num_dia) AS periodo_conserto
					FROM tbl_os, fn_calendario(tbl_os.data_digitacao::DATE, tbl_os.data_conserto::DATE) 
					WHERE tbl_os.os = {$os}
						AND tbl_os.fabrica = {$fabrica}
						AND fn_calendario.num_dia NOT IN (0,6)
						AND fn_calendario.data NOT IN (
							SELECT tbl_feriado.data FROM tbl_feriado 
							WHERE tbl_feriado.fabrica = {$fabrica}
								AND tbl_feriado.ativo IS TRUE
								AND DATE_PART('year',tbl_feriado.data) = DATE_PART('year', tbl_os.data_conserto)
						);";
			$query = $this->pdo->query($sql);

			$res = $query->fetchAll(\PDO::FETCH_ASSOC);

			if (count($res) > 0) {
				$periodo_conserto = $res[0]["periodo_conserto"];

			    if($periodo_conserto < 2){
			        $periodo_conserto = 2;
			    }
			} else {
				$periodo_conserto = 2;
			}

			$this->periodo_conserto = $periodo_conserto;
		}
	}

	/**
	 * Metodo que irá retornar as exceções de mão de obra da fabrica
	 * @return $this->excecao_mobra array
	 * @author Gabriel <gabriel.silveira@telecontrol.com.br>
	 * @return array pg_fetch_all($res)
	 * @param array $searchBy parametro passado para definir as condições da pesquisa da exceção de mão de obra, para pegar pela OS.
	 * Array contents: produto, familia, solucao_os, tipo_atendimento, peca_lancada.
	 */
	public function getExcecaoMobra($searchBy = array())
	{
		$conds   = "";
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

					case 'qtde_dias':
						if ($value > 0) {
							$conds .= "AND ( (tbl_excecao_mobra.qtde_dias is NOT NULL and tbl_excecao_mobra.qtde_dias = $value ) OR tbl_excecao_mobra.qtde_dias IS NULL )
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.qtde_dias is NULL
							";
						}
						break;

					case 'troca_produto':
						if ($value == "t") {
							$conds .= "AND tbl_excecao_mobra.troca_produto is TRUE
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.troca_produto is FALSE
							";
						}
						break;

					case 'tipo_posto':
						if ($value > 0) {
							$conds .= "AND ( (tbl_excecao_mobra.tipo_posto is NOT NULL and tbl_excecao_mobra.tipo_posto = $value ) OR tbl_excecao_mobra.tipo_posto IS NULL )
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.tipo_posto is NULL
							";
						}
						break;

					case 'revenda':
						if ($value == 't') {
							$conds .= "AND tbl_excecao_mobra.revenda is TRUE
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.revenda is FALSE
							";
						}
						break;

					case 'id_revenda':
						if ($value > 0) {
							$conds .= "AND ( (tbl_excecao_mobra.id_revenda is NOT NULL and tbl_excecao_mobra.id_revenda = $value ) OR tbl_excecao_mobra.id_revenda IS NULL )
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.id_revenda is NULL
							";
						}
						break;

					case 'defeito_constatado':
						if ($value > 0) {
							$conds .= "AND ( (tbl_excecao_mobra.defeito_constatado is NOT NULL and tbl_excecao_mobra.defeito_constatado = $value ) OR tbl_excecao_mobra.defeito_constatado IS NULL )
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.defeito_constatado is NULL
							";
						}
						break;

					case 'classificacao_solucao':
						if (is_array($value) && count($value) > 0) {
							$value = implode(",", $value);

							$conds .= "AND ( (tbl_excecao_mobra.classificacao is NOT NULL and tbl_excecao_mobra.classificacao IN($value) ) OR tbl_excecao_mobra.classificacao IS NULL )
							";

							$order_by = "tbl_excecao_mobra.mao_de_obra DESC, tbl_excecao_mobra.adicional_mao_de_obra DESC, tbl_excecao_mobra.percentual_mao_de_obra DESC, ";
						} else if (!is_array($value) && strlen($value) > 0 && $value != "null") {
							$conds .= "AND ( (tbl_excecao_mobra.classificacao is NOT NULL and tbl_excecao_mobra.classificacao = $value ) OR tbl_excecao_mobra.classificacao IS NULL )
							";
						}else{
							$conds .= "AND tbl_excecao_mobra.classificacao is NULL
							";
						}
						break;
					case 'unidade_negocio' :
						if (!empty($value)) {
							$conds .= " AND (tbl_distribuidor_sla.unidade_negocio='{$value}' or tbl_excecao_mobra.distribuidor_sla isnull)";
							$joinUN = "left JOIN tbl_distribuidor_sla on tbl_distribuidor_sla.distribuidor_sla = tbl_excecao_mobra.distribuidor_sla";
						} else {
							$joinUN = "";
						}
						break;
					case 'eficiencia' :
							if((int) $value > 0){
								if($value < 2){
									$value = 2;
								} else if($value > 3){
									$value = 4;
								}
								$conds .= " AND tbl_excecao_mobra.eficiencia = ".$value." ";
							}
						break;
				}
			}
		}

		$sql = "SELECT tbl_excecao_mobra.posto,
				tbl_excecao_mobra.produto,
				tbl_excecao_mobra.familia,
				tbl_excecao_mobra.linha,
				tbl_excecao_mobra.solucao,
				tbl_excecao_mobra.tipo_atendimento,
				tbl_excecao_mobra.peca_lancada,
				tbl_excecao_mobra.mao_de_obra,
				tbl_excecao_mobra.adicional_mao_de_obra,
				tbl_excecao_mobra.percentual_mao_de_obra,
				tbl_excecao_mobra.qtde_dias,
				tbl_excecao_mobra.troca_produto,
				tbl_excecao_mobra.revenda,
				tbl_excecao_mobra.id_revenda,
				tbl_excecao_mobra.tipo_posto,
				tbl_excecao_mobra.defeito_constatado,
				tbl_excecao_mobra.eficiencia,
				tbl_excecao_mobra.classificacao
			FROM tbl_excecao_mobra
			$joinUN
			WHERE tbl_excecao_mobra.fabrica = $fabrica
			$conds
			ORDER BY {$order_by} 
			tbl_excecao_mobra.posto,
			tbl_excecao_mobra.produto,
			tbl_excecao_mobra.solucao,
			tbl_excecao_mobra.linha,
			tbl_excecao_mobra.familia,
			tbl_excecao_mobra.tipo_atendimento,
			tbl_excecao_mobra.peca_lancada,
			tbl_excecao_mobra.qtde_dias,
			tbl_excecao_mobra.troca_produto,
			tbl_excecao_mobra.revenda,	
			tbl_excecao_mobra.id_revenda,
			tbl_excecao_mobra.tipo_posto,
			tbl_excecao_mobra.defeito_constatado,
			tbl_excecao_mobra.classificacao;";
		// echo nl2br($sql); exit;
		$query = $this->pdo->query($sql);

		return $query->fetchAll(\PDO::FETCH_ASSOC);

	}

	public function getQtdeDias(){
		$sql = "SELECT DISTINCT qtde_dias FROM tbl_excecao_mobra WHERE fabrica = $this->fabrica AND qtde_dias IS NOT NULL";
		$query = $this->pdo->query($sql);

		$res = $query->fetchAll();

		if (count($res) > 0) {
			return $res;
		} else {
			return false;
		}
	}

	/**
	 * Metodo que retorna dados da OS.
	 * @return array pg_fetch_all($res)
	 */
	public function getEficiencia(){
		$os      = $this->os;
		$fabrica = $this->fabrica;

		$sql = "SELECT tbl_excecao_mobra.eficiencia FROM tbl_excecao_mobra 
				JOIN tbl_os ON tbl_os.posto = tbl_excecao_mobra.posto
					AND tbl_os.fabrica = {$fabrica}
					AND tbl_os.os = {$os}
				JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
					AND tbl_os_campo_extra.fabrica = {$fabrica}
				JOIN tbl_distribuidor_sla ON tbl_distribuidor_sla.distribuidor_sla = tbl_excecao_mobra.distribuidor_sla
					AND tbl_distribuidor_sla.fabrica = {$fabrica}
					AND tbl_distribuidor_sla.unidade_negocio = json_field('unidadeNegocio',tbl_os_campo_extra.campos_adicionais::text)
			WHERE tbl_excecao_mobra.fabrica = {$fabrica}
				AND tbl_excecao_mobra.eficiencia IS NOT NULL";
		$query = $this->pdo->query($sql);

		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		if (count($res) > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Metodo que retorna dados da OS.
	 * @return array pg_fetch_all($res)
	 */
	public function getOs()
	{
		
		$os      = $this->os;
		$fabrica = $this->fabrica;

		$dada_verificacao = (in_array($fabrica, array(141,186))) ? "tbl_os.data_conserto::date" : "tbl_os.finalizada::date";

		$campo_data_abertura = (in_array($fabrica, array(148))) ? " tbl_os.data_abertura, " : "";

		$qtde_dias = $this->getQtdeDias();

		$calcula_qtde_dias = Regras::get("calcula_qtde_dias", "mao_de_obra", $fabrica);

		if($calcula_qtde_dias == true){

			$cond = " (tbl_os.data_conserto::date - tbl_os.data_abertura::date) AS qtde_dias ";

		}else{

			if(is_array($qtde_dias)){
				$cond = "CASE ";

				foreach ($qtde_dias as $key => $value) {
					$cond .=" WHEN ($dada_verificacao - tbl_os.data_digitacao::date) <= {$value['qtde_dias']}  THEN {$value['qtde_dias']}";
				}

				$cond .= " END AS qtde_dias ";
			}else{
				$cond = "NULL AS qtde_dias ";
			}

		}

		$sql = "SELECT tbl_os.os,
				tbl_os.posto,
				tbl_os.produto,
				tbl_produto.familia,
				tbl_produto.linha,
				tbl_os.tipo_atendimento,
				tbl_os.consumidor_revenda,
				tbl_os.revenda,
				$campo_data_abertura
				CASE WHEN tbl_os.solucao_os IS NOT NULL THEN tbl_os.solucao_os ELSE tbl_os_defeito_reclamado_constatado.solucao END AS solucao_os,
				CASE
					WHEN tbl_os.troca_garantia IS TRUE THEN
						't'
					ELSE
						'f'
				END AS troca_produto,
				tbl_posto_fabrica.tipo_posto,
				tbl_os.defeito_constatado,
				$cond
				FROM tbl_os
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $fabrica
				LEFT JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os
				WHERE tbl_os.os =  $os
				AND tbl_os.fabrica = $fabrica
				";
				// echo nl2br($sql);
		$query = $this->pdo->query($sql);

		return $query->fetchAll();
	}

	/**
	 * Metodo que irá fazer o calculo
	 * @return
	 */
	public function calculaExcecaoMobra()
	{
		$searchFields = array();

		try {
			$this->dadosOs = $this->getOs();
			$os = $this->os;

			if (!empty($this->dadosOs)) {
				$i = 0;

				foreach ($this->dadosOs as $key) {
					$os                 = $key['os'];
					$produto            = $key['produto'];
					$linha              = $key['linha'];
					$familia            = $key['familia'];
					$posto              = $key['posto'];
					$solucao_os         = $key['solucao_os'];
					$tipo_atendimento   = $key['tipo_atendimento'];
					$revenda            = ($key['consumidor_revenda'] == "R") ? "t" : "";
					$consumidor_revenda = $key['consumidor_revenda'];
					$id_revenda         = $key['revenda'];
					$troca_produto      = $key['troca_produto'];
					$tipo_posto         = $key['tipo_posto'];
					$qtde_dias          = $key['qtde_dias'];
					$defeito_constatado = $key["defeito_constatado"];
					$fabrica            = $this->fabrica; 
					$unidade_negocio    = null;

					if(!in_array($fabrica, array(140,141,144,157))) {
						$revenda = "";
					}
					if($this->fabrica == 148){
						$data_abertura = $key['data_abertura'];
					}

					$sql = "SELECT count(*) AS qtde
							FROM tbl_os_item
							INNER JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
							INNER JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
							WHERE tbl_os.os = {$os};";
					$query = $this->pdo->query($sql);
					$res   = $query->fetch();

					$peca_lancada = $res["qtde"];

					$solucao_multiplo = Regras::get("solucao_os_multiplo", "ordem_de_servico", $this->fabrica);

					if ($solucao_multiplo == true) {
						$sql = "
							SELECT tbl_classificacao.classificacao 
							FROM tbl_os_defeito_reclamado_constatado
							INNER JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os_defeito_reclamado_constatado.solucao 
								AND tbl_solucao.fabrica = {$this->fabrica}
							INNER JOIN tbl_classificacao ON tbl_classificacao.classificacao = tbl_solucao.classificacao 
								AND tbl_classificacao.fabrica = {$this->fabrica}
							WHERE tbl_os_defeito_reclamado_constatado.os = {$os}";
						$query = $this->pdo->query($sql);

						if ($query->rowCount() > 0) {
							$res = $query->fetchAll();

							$classificacao_solucao = array();

							foreach ($res as $row => $classificacao) {
								$classificacao_solucao[] = $classificacao["classificacao"];
							}
						} else {
							$classificacao_solucao = 'null';
						}
					} else {
						if (!empty($solucao_os)) {
							$sql = "SELECT classificacao AS classificacao_solucao FROM tbl_solucao WHERE tbl_solucao.solucao = {$solucao_os};";
							$query = $this->pdo->query($sql);
							$res = $query->fetch();

							if (empty($res['classificacao_solucao'])) {
								$classificacao_solucao = 'null';
							} else {
								$classificacao_solucao = $res['classificacao_solucao'];
							}
						} else {
							$classificacao_solucao = 'null';
						}
					}

					$unidade_negocio_os = Regras::get("unidade_negocio", "mao_de_obra", $this->fabrica);

					if ($unidade_negocio_os == true) {
						$sql = "SELECT json_field('unidadeNegocio',campos_adicionais::text) as unidade_negocio from tbl_os_campo_extra WHERE os = {$os}";
						$query = $this->pdo->query($sql);

						if ($query->rowCount() > 0) {
							$res   = $query->fetch();
							$unidade_negocio = $res["unidade_negocio"];
						} else {
							$unidade_negocio = 'null';
						}
					}

					if($this->usa_eficiencia){
						if(!$this->getEficiencia()){
							$eficiencia = "";
						} else {
							$eficiencia = $this->periodo_conserto;
						}
					} else {
						$eficiencia = "";
					}

					$searchFields = array(
						'produto'               => $produto,
						"linha"                 => $linha,
						"familia"               => $familia,
						"solucao_os"            => $solucao_os,
						"tipo_atendimento"      => $tipo_atendimento,
						"peca_lancada"          => $peca_lancada,
						"posto"                 => $posto,
						"revenda"               => $revenda,
						"id_revenda"            => $id_revenda,
						"troca_produto"         => $troca_produto,
						"tipo_posto"            => $tipo_posto,
						"qtde_dias"             => $qtde_dias,
						"defeito_constatado"    => $defeito_constatado,
						"classificacao_solucao" => $classificacao_solucao,
						"unidade_negocio"       => $unidade_negocio,
						"eficiencia"            => $eficiencia
					);

					$this->dadosExcecaoMobra = $this->getExcecaoMobra($searchFields);

					$visita_mobra = Regras::get("visita_mobra", "mao_de_obra", $this->fabrica);
					$calcula_qtde_dias = Regras::get("calcula_qtde_dias", "mao_de_obra", $this->fabrica);

					$nao_paga_entrega_tecnica_antes_2017 = Regras::get("nao_paga_entrega_tecnica_antes_2017", "mao_de_obra", $this->fabrica);

					if($visita_mobra == true && !empty($this->dadosExcecaoMobra)){

						$sql = "SELECT qtde_diaria FROM tbl_os WHERE os = ".$os." and fabrica = ".$this->fabrica;

						$res = pg_query($this->con,$sql);

						if (strlen(pg_last_error($this->con))>0) {
							throw new \Exception("Erro ao calcular Visitas : {$os}");
						}

						$qtde_visitas = pg_fetch_result($res, 0, "qtde_diaria");

						if(empty($qtde_visitas) || $qtde_visitas==0){
							$qtde_visitas = 1;
						}
						if(strlen($this->dadosExcecaoMobra[0]['mao_de_obra']) > 0) {
							$this->dadosExcecaoMobra[0]['mao_de_obra'] = $this->dadosExcecaoMobra[0]['mao_de_obra'] * $qtde_visitas;
						}
					}

					$existe_valor_mo = false;

					/*Yanmar não paga entrega tecnica para os antes de 2017 */
					if($nao_paga_entrega_tecnica_antes_2017 == true){
						if($data_abertura >= '2017-01-01' and strlen($this->dadosExcecaoMobra[0]['mao_de_obra']) > 0){
							$existe_valor_mo = true;
						}
					} else {
						if(strlen($this->dadosExcecaoMobra[0]['mao_de_obra']) > 0){
							$existe_valor_mo = true;
						}
					}

					if($existe_valor_mo){
						if ($this->setNewMobra("mao_de_obra",$this->dadosExcecaoMobra[0]['mao_de_obra'],$os) == false){
							throw new \Exception($this->mensagemErro('1'));
						}
					}

					if (strlen($this->dadosExcecaoMobra[0]['adicional_mao_de_obra']) > 0 and (($fabrica == 157 and $consumidor_revenda == 'C') or $fabrica != 157)) {
						if ($this->setNewMobra("adicional_mao_de_obra",$this->dadosExcecaoMobra[0]['adicional_mao_de_obra'],$os) == false){
							throw new \Exception($this->mensagemErro('2'));
						}
					}

					if (strlen($this->dadosExcecaoMobra[0]['percentual_mao_de_obra']) > 0) {
						if ($this->setNewMobra("percentual_mao_de_obra",$this->dadosExcecaoMobra[0]['percentual_mao_de_obra'],$os) == false){
							throw new \Exception($this->mensagemErro('3'));
						}
					}

					if($calcula_qtde_dias == true){

						$sql = "SELECT os_troca FROM tbl_os_troca WHERE os = {$os}";
						$res = $this->pdo->query($sql);

						if ($res->rowCount() > 0) {

							$sql = "UPDATE tbl_os SET mao_de_obra = 10 WHERE os = {$os}";
							$res = $this->pdo->query($sql);

						}else{

							$sql = "SELECT DISTINCT qtde_dias FROM tbl_excecao_mobra WHERE fabrica = {$this->fabrica} AND qtde_dias IS NOT NULL ORDER BY qtde_dias ASC";
							$query = $this->pdo->query($sql);

							if ($query->rowCount() > 0) {
								$array = $query->fetchAll();

								for($i = 0; $i < count($array); $i++){

									$qtde_dias = $array[$i]["qtde_dias"];

								  	if($i == 0){
								    	$cond = " (tbl_excecao_mobra.qtde_dias = {$qtde_dias} AND (tbl_os.data_conserto::date - tbl_os.data_abertura::date)::numeric <= {$qtde_dias}) ";
								  	}else if($i == count($array) - 1){
								    	$qtde_dias_anterior = $array[$i - 1]["qtde_dias"] + 1;
								    	$cond .= " OR (tbl_excecao_mobra.qtde_dias = {$qtde_dias} AND (tbl_os.data_conserto::date - tbl_os.data_abertura::date)::numeric BETWEEN {$qtde_dias_anterior} AND {$qtde_dias}) ";
								    	$cond .= " OR (tbl_excecao_mobra.qtde_dias = {$qtde_dias} AND (tbl_os.data_conserto::date - tbl_os.data_abertura::date)::numeric >= {$qtde_dias}) ";
								  	}else{
								  		$qtde_dias_anterior = $array[$i - 1]["qtde_dias"] + 1;
								    	$cond .= " OR (tbl_excecao_mobra.qtde_dias = {$qtde_dias} AND (tbl_os.data_conserto::date - tbl_os.data_abertura::date)::numeric BETWEEN {$qtde_dias_anterior} AND {$qtde_dias}) ";
								  	}

								}

								$sql = "UPDATE tbl_os 
											SET mao_de_obra = ROUND(x.mao_de_obra::numeric,2)
										FROM (
											SELECT 
												tbl_os.os, 
												tbl_excecao_mobra.mao_de_obra, 
												tbl_excecao_mobra.qtde_dias
											FROM tbl_os
											JOIN tbl_os_extra USING(os)
											JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica AND (tbl_excecao_mobra.posto ISNULL OR tbl_os.posto = tbl_excecao_mobra.posto)
											JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->fabrica}
											WHERE 
												tbl_os.os = {$os} 
												AND tbl_os.fabrica = {$this->fabrica} 
												AND tbl_excecao_mobra.mao_de_obra IS NOT NULL
												AND tbl_excecao_mobra.produto IS NULL 
												AND tbl_excecao_mobra.troca_produto IS NOT TRUE
												AND ( $cond )
										) AS x 
										WHere tbl_os.os = {$os}";
								$query = $this->pdo->query($sql);

							}	

						}

					}

				}

			}

		} catch (Exception $e) {
			throw new \Exception($e->getMessage());
		}
	}

	public function mensagemErro($n){
		return "$n - Erro ao atualizar mão de obra da OS:  ".$this->os;
	}

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
		}

		$sql = "UPDATE tbl_os SET $updateValue WHERE os = $os";
		$query = $this->pdo->query($sql);

		if (!$query) {
			return false;
		} else {
			return true;
		}
	}
}
