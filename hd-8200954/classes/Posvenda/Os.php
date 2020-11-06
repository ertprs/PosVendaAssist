<?php
namespace Posvenda;

use Posvenda\Model\Os as OsModel;
use Posvenda\MaoDeObra;

class Os
{

	protected $_os;
	private $_fabrica;
	private $_dias_em_aberto;
	public $_model;
    public $_conn;

	public function __construct($fabrica, $os = null, $conn = null)
	{
		if (!empty($os)) {
			$this->_os = $os;
		}

        $this->_conn = $conn;

		$this->_fabrica = $fabrica;
		$this->_model = new OsModel($this->_fabrica, $this->_os, $conn);

		$this->_model->select('tbl_os')
			->setCampos(array('sua_os'))
			->addWhere(array('os' => $this->_os))
			->addWhere(array('fabrica' => $this->_fabrica));

		$this->_model->prepare()->execute();

		$res = $this->_model->getPDOStatement()->fetch();
		if (!empty($res)) {
			$this->_sua_os = $res['sua_os'];
		}

		$this->_dias_em_aberto = null;
	}

	public function setOs($os)
	{
			$this->_os = $os;
	}
	/**
	 * Calcula os valores da OS
	 *
	 * @param integer $os
	 * @return float
	 */
	public function calculaOs($os = null)
	{
		if (empty($os)) {
			$os = $this->_os;
		}

		try {
			if ($this->_model->osAprovadaSemValor($os) || $this->_model->osReprovadaSemValor($os) || $this->_model->osRecusadaMo($os)) {
				$this->_model->zerarValores($os);
				return $this;
			}

			if($this->_fabrica == 177){
				$calcula_tipo_atendimento = \Posvenda\Regras::get("calcula_mo_tipo_atendimento", "mao_de_obra", $this->_fabrica);
				if(count(array_filter($calcula_tipo_atendimento)) > 0) {
					$informacoesOs     = $this->getInformacoesOs($os);
					$osTipoAtendimento = $informacoesOs["tipo_atendimento"];
					if(!in_array($osTipoAtendimento, $calcula_tipo_atendimento)){			
						return true;
					}	
				}
			}
			
			if(in_array($this->_fabrica, [167])){
				$getInformacoesPostoFabrica = $this->getInformacoesPostoFabrica($os);

				$tipoDePosto = $getInformacoesPostoFabrica['descricao'];
				$tipoPostoRevenda = $getInformacoesPostoFabrica['tipo_revenda'];

				if($tipoPostoRevenda != true){
					$mo          = new MaoDeObra($os, $this->_fabrica, $this->_conn);
					$mao_de_obra = $mo->calculaMaoDeObra()->getMaoDeObra();
				}

				if($tipoDePosto == 'Autorizada Premium'){
					$km = $this->_model->calculaKM($os)->getKM($os);
				}
			}

			// MO
			$nao_calcula_mo = \Posvenda\Regras::get("nao_calcula_mo", "mao_de_obra", $this->_fabrica);
            $nao_calcula_mo_distribuidor = \Posvenda\Regras::get("nao_calcula_mo_distribuidor", "mao_de_obra", $this->_fabrica);

			if (in_array($this->_fabrica, array(169,170))){
				$informacoesOs  = $this->getInformacoesOs($os);
				$osSubconjunto 	= $informacoesOs['os_numero'];

				if(!empty($osSubconjunto)){
					$nao_calcula_mo = true;
					$nao_calcula_km = true;
				}

				$informacoesPostoFabrica = $this->getInformacoesPostoFabrica($os);

				if ($informacoesPostoFabrica['tipo_revenda'] == "t") {
					$informacoesPostoFabrica['tipo_revenda'] = true;
				}

				if ($informacoesPostoFabrica['tipo_revenda'] == true) {
					$nao_calcula_mo = false;
					$nao_calcula_km = true;
					$nao_calcula_valor_adicional = false;

					if ($informacoesPostoFabrica['prestacao_servico_sem_mo'] == "t") {
					    $informacoesPostoFabrica['prestacao_servico_sem_mo'] = true;
					}

					if ($informacoesPostoFabrica['prestacao_servico_sem_mo'] == true) {
						$nao_calcula_mo = true;
						$nao_calcula_valor_adicional = true;
					}
				}
			}

            if ($nao_calcula_mo_distribuidor == true)
            {
                $pdo = $this->_model->getPDO();
                $sql_posto = "
                    SELECT 
                        tbl_tipo_posto.distribuidor
                    FROM tbl_os
                    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $this->_fabrica
                    INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = $this->_fabrica
                    WHERE tbl_os.fabrica = $this->_fabrica
                    AND tbl_os.os = $os";
                $res_posto     = $pdo->query($sql_posto);
                $retorno_posto = $res_posto->fetch();

                if ($retorno_posto['distribuidor'] == 't')
                {
                    $nao_calcula_mo = true;
                }
            }
        
			if ($nao_calcula_mo != true) {
				$mo = new MaoDeObra($os, $this->_fabrica, $this->_conn);
                $mao_de_obra = $mo->calculaMaoDeObra()->getMaoDeObra();
			}


			// KM
			if (empty($nao_calcula_km)) {
				$nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $this->_fabrica);
			}
			
			if ($nao_calcula_km != true) {
				$km = $this->_model->calculaKM($os)->getKM($os);
			}
			
			// PEÇAS
			$nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $this->_fabrica);

			if ($nao_calcula_peca != true) {
				$nao_calcula_peca_tipo_atendimento = \Posvenda\Regras::get("nao_calcula_peca_tipo_atendimento", "mao_de_obra", $this->_fabrica);

				if (count($nao_calcula_peca_tipo_atendimento) > 0) {
					$informacoesOs     = $this->getInformacoesOs($os);
					$osTipoAtendimento = $informacoesOs["tipo_atendimento"];

					if (!in_array($osTipoAtendimento, $nao_calcula_peca_tipo_atendimento)) {
						$pecas = $this->_model->calculaValorPecas($os)->getValorPecas($os);
					}
				} else {
					$pecas = $this->_model->calculaValorPecas($os)->getValorPecas($os);
				}
			}

			$verifica_os_pedido_obrigatorio = \Posvenda\Regras::get("verifica_os_pedido_obrigatorio", "pedido_garantia", $this->_fabrica);

			if ($verifica_os_pedido_obrigatorio == true) {

				$pdo = $this->_model->getPDO();

				$sql = "SELECT auditoria_os
		                FROM tbl_auditoria_os
		                WHERE LOWER(observacao) = 'troca de peça usando estoque'
		                AND os = {$os}
		                AND reprovada IS NOT NULL";
        		$query = $pdo->query($sql);

        		if ($query->rowCount() > 0) {

        			$pecas = 0;

        			$sql = "UPDATE tbl_os SET pecas = 0 WHERE os = {$os}";
        			$query = $pdo->query($sql);

        		}

			}

			$calcula_mo_servico_realizado = \Posvenda\Regras::get("calcula_mo_servico_realizado", "mao_de_obra", $this->_fabrica);

			if($calcula_mo_servico_realizado == true){
				$mo          = new MaoDeObra($os, $this->_fabrica, $this->_conn);
				$mao_de_obra = $mo->calculaMaoDeObraServicoRealizado()->getMaoDeObra();
			}

			$calcula_mo_defeito_constatado = \Posvenda\Regras::get("calcula_mo_defeito_constatado", "mao_de_obra", $this->_fabrica);

			if($calcula_mo_defeito_constatado == true AND empty($mao_de_obra)){
				$moDefeito          = new MaoDeObra($os, $this->_fabrica, $this->_conn);
				$mao_de_obra = $moDefeito->calculaMaoDeObraDefeitoConstatado()->getMaoDeObra();
			}

			$calcula_mo_troca = \Posvenda\Regras::get("calcula_mo_troca", "mao_de_obra", $this->_fabrica);

			if($calcula_mo_troca == true){
				$moDefeito          = new MaoDeObra($os, $this->_fabrica, $this->_conn);
				$mao_de_obra = $moDefeito->calculaMaoDeObraTroca()->getMaoDeObra();
			}

	        $excecao_revenda = \Posvenda\Regras::get("excecao_revenda", "mao_de_obra", $this->_fabrica);

            if ($retorno_posto['distribuidor'] == true)
            {
                $nao_calcula_valor_adicional = true;
            }

    		if ($excecao_revenda == true) {
                $informacoesOs = $this->getInformacoesOs($os);
                $posto = $informacoesOs["posto"];

        		$valorExcecao = new ExcecaoMobra($os, $this->_fabrica, "");
        		$retorno      = $valorExcecao->getExcecaoMobra(array("posto"=>$posto, "revenda" => "t"));

        		$valor_adicional = $retorno[0]["adicional_mao_de_obra"];

        		$this->_model->atualizaValorAdicional($os, $valor_adicional);
    		} else if ($nao_calcula_valor_adicional != true) { 
        		$valor_adicional = $this->_model->calculaValorAdicional($os);
    		}
		} catch(\Exception $e) {

			throw new \Exception($e->getMessage());

		}

		return $this;
	}

	public function getInformacoesOs($os = null)
	{
		if (empty($os)) {
			throw new \Exception("OS não informada para selecionar as informações");
		}

		$this->_model->select("tbl_os")
					 ->setCampos(array("*"))
					 ->addWhere(array("fabrica" => $this->_fabrica))
					 ->addWhere(array("os" => $os));

		if (!$this->_model->prepare()->execute()) {
			throw new \Exception("Erro ao selecionar as informações da OS");
		}

		if ($this->_model->getPDOStatement()->rowCount() == 0) {
			throw new \Exception("OS não encontrada para essa Fábrica");
		}

		$res = $this->_model->getPDOStatement()->fetch();

		return $res;
	}

	public function getInformacoesPostoFabrica($os = null)
	{
		$pdo = $this->_model->getPDO();
		if (empty($os)) {
			throw new \Exception("OS não informada para selecionar as informações");
		}

		$query = "
            SELECT
                tbl_posto_fabrica.tipo_posto,
                tbl_posto_fabrica.prestacao_servico_sem_mo,
				tbl_tipo_posto.descricao,
				tbl_tipo_posto.tipo_revenda
			FROM tbl_posto_fabrica
			JOIN tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto
			JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = $this->_fabrica
			WHERE tbl_os.os = $os
			AND tbl_posto_fabrica.fabrica = $this->_fabrica";
		$res = $pdo->query($query);
		$retorno = $res->fetch();

		if (is_array($retorno) && count($retorno) > 0) {
			return $retorno;
		} else {
			throw new \Exception("Erro ao selecionar as informações do Posto da OS {$this->_sua_os}");
		}

	}

	public function getInformacoesOsProduto($os = null) {
		if (empty($os)) {
			throw new \Exception("OS não informada para selecionar as informações do produto");
		}

		$this->_model->select("tbl_os_produto")
					 ->setCampos(array("tbl_os_produto.*"))
					 ->addJoin(array("tbl_os" => "ON tbl_os.os = tbl_os_produto.os"))
					 ->addWhere(array("tbl_os.fabrica" => $this->_fabrica))
					 ->addWhere(array("tbl_os_produto.os" => $os));

		if (!$this->_model->prepare()->execute()) {
			throw new \Exception("Erro ao selecionar as informações dos Produtos da OS {$this->_sua_os}");
		}

		if ($this->_model->getPDOStatement()->rowCount() == 0) {
			throw new \Exception("Produto não encontrado para a OS {$this->_sua_os}");
		}

		$res = $this->_model->getPDOStatement()->fetchAll();

		return $res;
	}

	public function getProdutoLinha($produto = null) {
		if (empty($produto)) {
			throw new \Exception("Produto não informado para selecionar a linha");
		}

		$this->_model->select("tbl_produto")
					 ->setCampos(array("linha"))
					 ->addWhere(array("fabrica_i" => $this->_fabrica))
					 ->addWhere(array("produto" => $produto));

		if (!$this->_model->prepare()->execute()) {
			throw new \Exception("Erro ao pegar linha do produto {$produtosto}");
		}

		if ($this->_model->getPDOStatement()->rowCount() == 0) {
			throw new \Exception("Produto {$produto} não encontrado com a linha informada");
		}

		$res = $this->_model->getPDOStatement()->fetch();

		return $res["linha"];
	}

	public function calculaMaoDeObraRevisao($con = "", $os = null){

		if (empty($os)) {
			$os = $this->_os;
		}

		try {

			$mo = new MaoDeObra($os, $this->_fabrica, $this->_conn);
			return $mo->calculaMaoDeObraRevisao($os);


		} catch(\Exception $e) {

			throw new \Exception($e->getMessage());

		}

	}

	/**
	 * Verifica se a OS é do Tipo Revisão
	 *
	 * @param integer $os
	 * @return boolean
	 */
	public function verificaOsRevisao($os = null){
		if (empty($os)) {
			$os = $this->_os;
		}

		$pdo = $this->_model->getPDO();

		$sql = "SELECT tbl_os.tipo_os FROM tbl_os INNER JOIN tbl_tipo_os ON tbl_tipo_os.tipo_os = tbl_os.tipo_os WHERE tbl_os.fabrica = {$this->_fabrica} AND tbl_os.os = {$os} AND (UPPER(fn_retira_especiais(tbl_tipo_os.descricao)) = 'REVISAO' OR UPPER(TO_ASCII(tbl_tipo_os.descricao, 'LATIN9')) = 'REVISAO')";
		$query = $pdo->query($sql);

		$res = $query->fetch();

		if (is_array($res) && count($res) > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Verifica se a OS é do Tipo Revisão
	 *
	 * @param integer $os
	 * @return boolean
	 */
	public function verificaRevisaoTipo($os = null){
		if (empty($os)) {
			$os = $this->_os;
		}

		$pdo = $this->_model->getPDO();

		$sql = "SELECT tbl_os.tipo_atendimento FROM tbl_os INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento WHERE tbl_os.fabrica = {$this->_fabrica} AND tbl_os.os = {$os} AND (UPPER(fn_retira_especiais(tbl_tipo_atendimento.descricao)) = 'REVISAO' OR UPPER(TO_ASCII(tbl_tipo_atendimento.descricao, 'LATIN9')) = 'REVISAO')";
		$query = $pdo->query($sql);

		$res = $query->fetch();

		if (is_array($res) && count($res) > 0) {
			return true;
		} else {
			return false;
		}
	}
	 /**
	 * Verifica se a OS está vinculada
	 *
	 * @param integer $os
	 * @return boolean
	 */
	public function verificaOsVinculada($os = null){
		if (empty($os)) {
			$os = $this->_os;
		}

		$pdo = $this->_model->getPDO();

	   $sql = "SELECT tbl_os_extra.recolhimento,
							tbl_os.os_numero
						FROM tbl_os
						INNER JOIN tbl_os_extra on tbl_os_extra.os = tbl_os.os
						WHERE tbl_os.os = {$os}
							AND tbl_os.fabrica = {$this->_fabrica}
							AND tbl_os_extra.recolhimento is true";
		$query = $pdo->query($sql);

		$res = $query->fetch();

		if (is_array($res) && count($res) > 0) {

			$os_numero = $res['os_numero'];
			if(!empty($os_numero)){

				$sql = "SELECT data_fechamento FROM tbl_os WHERE tbl_os.os = {$os_numero} and tbl_os.data_fechamento IS NULL";
				$query = $pdo->query($sql);
				$res = $query->fetch();

				if (is_array($res) && count($res) > 0) {
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		} else {
			return false;
		}
	}

	public function enviaComunicadoOSVinculada($os = null)
	{

		if (empty($os)) {
			$os = $this->_os;
		}

        $pdo = $this->_model->getPDO();

        $sql = "
        	SELECT tbl_os.os_numero,
				tbl_os.obs
			FROM tbl_os
			WHERE tbl_os.os = {$os}
			AND tbl_os.fabrica = {$this->_fabrica}
		";
		$query = $pdo->query($sql);

        $res = $query->fetch();

        if (is_array($res) && count($res) > 0) {

            $os_numero = $res['os_numero'];
			$obs = $res['obs'];
            if(!empty($os_numero)){

				$sql = "SELECT posto FROM tbl_os WHERE os = {$os_numero}";
				$query = $pdo->query($sql);
				$res = $query->fetch();
				$posto = $res['posto'];

				$msg = "Sua Ordem de Serviço {$os_numero} já foi reparada na fábrica.<br>Aguarde o envio do produto para poder finalizá-la";
            	$sql = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP, obs = '{$obs}' WHERE os = {$os_numero}";
				$query = $pdo->query($sql);

				$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(os,defeito_reclamado,defeito_constatado,fabrica) SELECT {$os_numero},defeito_reclamado,defeito_constatado,{$this->_fabrica} FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os}";
				$query = $pdo->query($sql);

				$sql = "INSERT INTO tbl_comunicado (mensagem, tipo, fabrica, posto,obrigatorio_site, ativo)
                        VALUES ('{$msg}','Comunicado',{$this->_fabrica},{$posto},TRUE, TRUE)";
                $query = $pdo->query($sql);

            }
        }
	}

	public function VerificaIntervencao($con){
		$os = $this->_sua_os;
		$auditoria_unica = \Posvenda\Regras::get("auditoria_unica", "pedido_garantia", $this->_fabrica);

        if($auditoria_unica == true){
            $nao_bloqueia = \Posvenda\Regras::get("auditoria_nao_bloqueia", "ordem_de_servico", $this->_fabrica);

            if (!empty($nao_bloqueia) && $fechamento == true) {
                $whereNaoBloqueia = "";

                foreach ($nao_bloqueia as $auditoria) {
                    $whereNaoBloqueia .= " AND tbl_auditoria_status.{$auditoria} IS NOT TRUE ";
                }
            }

            $sql = "
                SELECT DISTINCT
                    tbl_auditoria_os.auditoria_status,
                    tbl_auditoria_os.observacao
                FROM tbl_auditoria_os
                INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                WHERE tbl_auditoria_os.os = {$os}
                {$whereNaoBloqueia}
            ";
            $query = pg_query($con, $sql);

            if (pg_num_rows($query) > 0) {
            	$res = pg_fetch_all($query);

                for($i = 0; $i < count($res); $i++) {
                    $auditoria_status = $res[$i]["auditoria_status"];
                    $observacao = $res[$i]["observacao"];

                    $sqlAuditOS = "
                        SELECT
                            tbl_auditoria_os.auditoria_os,
                            tbl_auditoria_os.liberada,
                            tbl_auditoria_os.reprovada,
                            tbl_auditoria_os.observacao
                        FROM tbl_auditoria_os
                        WHERE tbl_auditoria_os.os = {$os}
                        AND tbl_auditoria_os.auditoria_status = {$auditoria_status}
                        AND fn_retira_especiais(tbl_auditoria_os.observacao) = fn_retira_especiais('{$observacao}')
                        ORDER BY tbl_auditoria_os.data_input DESC
                        LIMIT 1
                    ";
                    $queryAuditOS = pg_query($con, $sqlAuditOS);
                    $resAuditOS = pg_fetch_assoc($queryAuditOS);

                    if ($resAuditOS['liberada'] == "") {
                        throw new \Exception("OS {$this->_sua_os} em Auditoria: {$observacao}");
                    }

                    if ($resAuditOS['reprovada'] != "" and $resAuditOS['liberada'] == "") {
                        throw new \Exception("OS {$this->_sua_os} reprovada da Auditoria: {$observacao}");
                    }
                }
            }
        }else{
        	/* KM */
            $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status=$fabrica AND os = {$os} AND status_os IN(98,99,100,101) ORDER BY data DESC LIMIT 1";
            $query = pg_query($con, $sql);

            $res = pg_fetch_assoc($query);
            $status_os = $res['status_os'];

            if($status_os == 98){
                throw new \Exception("OS {$this->_sua_os} em auditoria de KM");
            }

            /* Valores Adicionais */
            $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status=$fabrica AND os = {$os} AND status_os IN(171,172,173) ORDER BY data DESC LIMIT 1";
            $query = pg_query($con, $sql);

            $res = pg_fetch_assoc($query);
            $status_os = $res['status_os'];

            if($status_os == 171){
                throw new \Exception("OS {$this->_sua_os} em auditoria de Valores Adicionais");
            }

            /* Pe&ccedil;as Excedentes */
            $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status=$fabrica AND os = {$os} AND status_os IN(118,185,187) ORDER BY data DESC LIMIT 1";
        	$query = pg_query($con, $sql);

        	$res = pg_fetch_assoc($query);
        	$status_os = $res['status_os'];

        	if($status_os == 118){
        	    throw new \Exception("OS {$this->_sua_os} em auditoria de Peça Excedentes");
        	}

        	/* Pe&ccedil;a Crítica */
            $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status=$fabrica AND os = {$os} AND status_os IN(62,64) ORDER BY data DESC LIMIT 1";
        	$query = pg_query($con, $sql);

        	$res = pg_fetch_assoc($query);
        	$status_os = $res['status_os'];

        	if($status_os == 62){
        	    throw new \Exception("OS {$this->_sua_os} em auditoria de Peça Crítica");
        	}

        	$auditorias_adicionais = \Posvenda\Regras::get("auditorias", "pedido_garantia", $this->_fabrica);

        	if (count($auditorias_adicionais) > 0) {
        	    foreach ($auditorias_adicionais as $key => $auditoria) {
        	        $status_auditoria = $auditoria["status"];
                	$status_aprovacao = $auditoria["status_aprovacao"];
        	        $auditoria_nome   = $auditoria["nome"];

			        $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status=$fabrica AND os = {$os} AND status_os IN(".implode(",", $status_auditoria).") ORDER BY data DESC LIMIT 1";

        	        $query = pg_query($con, $sql);
                    $res = pg_fetch_assoc($query);

        	        if (is_array($res) && count($res) > 0) {
                	    $status_os = $res['status_os'];

    			        if(is_array($status_aprovacao)){
        				    if(!in_array($status_os,$status_aprovacao)){
        					   throw new \Exception("OS {$this->_sua_os} em auditoria de {$auditoria_nome}");
        				    }
    			        }else{
            				if($status_os != $status_aprovacao){
                                if($fabrica == 145 AND $status_os == 201){
                                    throw new \Exception("OS {$this->_sua_os} reprovada da auditoria, não poderá ser fechada.");
                                }else{
                                    throw new \Exception("OS {$this->_sua_os} em auditoria de {$auditoria_nome}");
                                }
            				}
    			        }
        	        }
        	    }
        	}
        }
	}

	public function verificaSolucaoOs($con){

		$solucao_os = \Posvenda\Regras::get("solucao_os", "ordem_de_servico", $this->_fabrica);

		if($solucao_os == true){
	        $multiplo = \Posvenda\Regras::get("solucao_os_multiplo", "ordem_de_servico", $this->_fabrica);

	        if ($multiplo) {
	            $sql = "SELECT solucao FROM tbl_os_defeito_reclamado_constatado WHERE os = {$this->_os} AND solucao IS NOT NULL";
	            $query = pg_query($con, $sql);

	            if (pg_num_rows($query) > 0) {
	                $solucao_os = pg_fetch_all($query);
	            }
	        } else {
	            $sql = "SELECT solucao_os FROM tbl_os WHERE os = {$this->_os} AND solucao_os notnull";
	            $query = pg_query($sql);
	            $res = pg_fetch_assoc($query);
	            $solucao_os = $res['solucao_os'];
	        }

	        if(empty($solucao_os)){
				throw new \Exception("Solução não informada na OS");
	        }
		}
	}

	public function finalizaRevisao($con)
	{
		if(!$this->_model->verificaOsFabrica($con)){
			throw new \Exception("OS ".$this->_sua_os." não encontrada para finalizar a revisão para a Fábrica");
		}

		if(!$this->_model->verficaOsRevisaoProduto($con)){
			throw new \Exception("OS ".$this->_sua_os." não pode ser fechada sem lançar produtos");
		}

		if(!$this->_model->finalizaOS($con)){
			throw new \Exception("Erro ao Finalizar a OS ".$this->_sua_os);
		}
	}


	public function finaliza($con, $troca_produto_api = false, $login_admin = null, $origem = null)
	{

    	if (!$this->_model->verificaOsFabrica($con)) {
    		throw new \Exception("OS ".$this->_sua_os." não encontrada para ser finalizada para a Fábrica");
		}

		$finaliza_os_valida_data_conserto = \Posvenda\Regras::get("finaliza_os_valida_data_conserto", "ordem_de_servico", $this->_fabrica);

		if ($finaliza_os_valida_data_conserto == true) {
			$pdo = $this->_model->getPDO();
			$sql = "
				SELECT data_conserto
				FROM tbl_os
				WHERE fabrica = {$this->_fabrica}
				AND os = {$this->_os}
			";
			$query = $pdo->query($sql);
			$res = $query->fetch();

			if (empty($res["data_conserto"])) {
				throw new \Exception("É necessário informar a data de conserto para fechar a Ordem de Serviço {$this->_sua_os}");
			}
		}

		if ($this->_fabrica == 148) {
			$pdo = $this->_model->getPDO();
			$sql = "SELECT tbl_os.os FROM tbl_os INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento WHERE tbl_os.fabrica = {$this->_fabrica} AND tbl_os.os = {$this->_os} AND tbl_tipo_atendimento.entrega_tecnica IS TRUE";
			$query = $pdo->query($sql);

			if ($query->rowCount() > 0) {
				$intervencao = $this->_model->verificaOsIntervencao();

				if ($intervencao != false) {
					throw new \Exception($intervencao);
				}

				//return $this;
			}
		}

		if (in_array($this->_fabrica, [160,200])) {
			$this->_model->recompraOS($con);
		}

		if($this->_fabrica == 138){
			$this->pagaMaoDeObra($this->_os);
		}

		/**
		 * Mondial - API Faturamento pode fechar OS
		 * SE for Troca de Produto
		 **/

		if ($troca_produto_api === true) {
			if(!$this->_model->finalizaOS($con)){
				die ('false'); // é o retorno para a API! Por isso não joga exceção!
			}
			die ('true'); // é o retorno para a API! Por isso não joga exceção!
		}

		/**
		 * Esab não valida quando OS é tipo entrega técncia
		 *
		 */

		if ($this->verificaTipoAtendimento($this->_os,'entrega_tecnica') == true and in_array($this->_fabrica,array(152,148,180,181,182))) {
			/*
			* Verifica Intervenção
			*/
			$intervencao = $this->_model->verificaOsIntervencao();

			if ($intervencao != false) {
				throw new \Exception($intervencao);
			}

			if (!$this->_model->finalizaOS($con)) {

			}
		} else {

			/*
			 * Verifica Peças pendentes no Pedido
			 */
			$posto_interno_nao_valida = \Posvenda\Regras::get("posto_interno_nao_valida", "ordem_de_servico", $this->_fabrica);

			if ($posto_interno_nao_valida == true) {
				$fabricaClass = new \Posvenda\Fabrica($this->_fabrica);
				$parametros_adicionais = $fabricaClass->getParametroAdicional();

				$pdo = $this->_model->getPDO();

				if ($parametros_adicionais->tipo_posto_multiplo == true) {
					$sql = "
						SELECT tbl_tipo_posto.tipo_posto
						FROM tbl_os
						INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
						INNER JOIN tbl_posto_tipo_posto ON tbl_posto_tipo_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_tipo_posto.fabrica = {$this->_fabrica}
						INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
						WHERE tbl_os.fabrica = {$this->_fabrica}
						AND tbl_os.os = {$this->_os}
						AND tbl_tipo_posto.posto_interno IS TRUE
					";
				} else {
					$sql = "
						SELECT tbl_tipo_posto.tipo_posto
						FROM tbl_os
						INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
						INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
						WHERE tbl_os.fabrica = {$this->_fabrica}
						AND tbl_os.os = {$this->_os}
						AND tbl_tipo_posto.posto_interno IS TRUE
					";
				}

				$query = $pdo->query($sql);

				if ($query->rowCount() > 0) {
					$tipo_posto_interno = true;
				} else {
					$tipo_posto_interno = false;
				}
			}

			//valida tipo atendimento fora de garantia
			if ($this->verificaTipoAtendimento($this->_os,'fora_garantia') == true and in_array($this->_fabrica,array(163))) {
                /*
                 * Verifica se a OS tem peças lançadas
                 */
				$pecaLancada = $this->_model->verificaPedidoOS($con,$this->_os);

				if($pecaLancada !== true){
					throw new \Exception($pecaLancada);
				}
			}
			
			$valida_gerado_faturado              = true;
 
			if ($this->_fabrica == 178) {
				$verificaPedidoOsGeradoFaturado  = $this->_model->verificaPedidoOsGeradoFaturado($this->_sua_os);
				$xconsumidor_revenda             = $this->_model->getConsumidorRevendaSuaOS($this->_sua_os);
			}
			
			if (!($posto_interno_nao_valida == true && $tipo_posto_interno == true) && $valida_gerado_faturado == true) {
				$limite = \Posvenda\Regras::get("limite_dias_peca_pendente_finaliza", "ordem_de_servico", $this->_fabrica);

				if (strlen($login_admin) > 0) {//hd_chamado=2902321
					$area_admin = \Posvenda\Regras::get("area_admin","ordem_de_servico", $this->_fabrica);
				}
				$nao_valida_pedido_nao_faturado = false;
				$nao_valida_pedido_nao_faturado = \Posvenda\Regras::get("nao_valida_pedido_nao_faturado", "ordem_de_servico", $this->_fabrica);

				$verifica_os_pedido_obrigatorio = \Posvenda\Regras::get("verifica_os_pedido_obrigatorio", "pedido_garantia", $this->_fabrica);
				
				if ($verifica_os_pedido_obrigatorio == true) {

					if ($this->_model->verificaAuditoriaEstoque($this->_os)) {

						$nao_valida_pedido_nao_faturado = true;
						
					}

				}

				if ($this->_fabrica == 191 && $tipo_posto_interno == true) {
					$nao_valida_pedido_nao_faturado = true;
				}

				if ($nao_valida_pedido_nao_faturado == false) {
					$pedido_pecas_nao_faturadas = $this->_model->verificaPedidoPecasNaoFaturadasOS($con,$limite,$area_admin);
				}

				if ($pedido_pecas_nao_faturadas != false) {
					throw new \Exception($pedido_pecas_nao_faturadas);
				}

				$pedido_pendente_troca = $this->_model->verificaPedidoPedentesTroca($con,$area_admin);

				if ($pedido_pendente_troca != false) {
					throw new \Exception($pedido_pendente_troca);
				}
			}

			$os_revisao = $this->verificaRevisaoTipo($this->_os);
			$solucao_os = \Posvenda\Regras::get("solucao_os", "ordem_de_servico", $this->_fabrica);

			if($this->_fabrica == 148){

				$informacoesOs  = $this->getInformacoesOs($this->_os);
				$tipo_atendimento_outros = \Posvenda\Regras::get("tipo_atendimento_outros", "ordem_de_servico", $this->_fabrica);

				if($informacoesOs['tipo_atendimento'] == $tipo_atendimento_outros){
					$valida_solucao = false;
					$valida_constatado = false;
				}else{
					$valida_solucao = true;
					$valida_constatado = true;
				}
			}else{
				$valida_solucao = true;
				$valida_constatado = true;
			}

			if ($solucao_os == true and $valida_solucao == true) {
				$tem_solucao = $this->_model->verificaSolucaoOS($con);

				if ($tem_solucao == false and $os_revisao == false) {
					throw new \Exception("Informe solução na OS ".$this->_sua_os);
				}
			}
			

			$finaliza_os_obs_obrigatoria = \Posvenda\Regras::get("finaliza_os_obs_obrigatoria", "ordem_de_servico", $this->_fabrica);
			$finaliza_os_obs_length      = \Posvenda\Regras::get("finaliza_os_obs_length", "ordem_de_servico", $this->_fabrica);

			if ($finaliza_os_obs_obrigatoria == true) {
				$pdo = $this->_model->getPDO();

				$sql = "SELECT obs
						FROM tbl_os
						WHERE fabrica = {$this->_fabrica}
						AND os = {$this->_os}";

				if (!empty($con)) {
					$query = pg_query($con, $sql);
					$obs = pg_fetch_result($query, 0, 'obs');
				} else {
					$query = $pdo->query($sql);
					$res = $query->fetch();
					$obs = $res["obs"];
				}


				if (!empty($finaliza_os_obs_length) && mb_strlen($obs) < $finaliza_os_obs_length) {
					throw new \Exception("A Observação da OS {$this->_sua_os} deve ter no mínimo {$finaliza_os_obs_length} caracteres");
				} else if (!mb_strlen($obs)) {
					throw new \Exception("A Observação da OS {$this->_sua_os} é obrigatória");
				}
			}

			/*
			* Verifica Intervenção
			*/

			$finaliza_nao_verifica_auditoria = \Posvenda\Regras::get("finaliza_nao_verifica_auditoria", "ordem_de_servico", $this->_fabrica);

			if (!$finaliza_nao_verifica_auditoria) {
				if ($this->_fabrica == 35 and !empty($login_admin)) {
					$intervencao = $this->_model->verificaOsIntervencao(null, null, true, $login_admin);
				} else {
					$intervencao = $this->_model->verificaOsIntervencao(null, null, true);
				}

				if($intervencao != false){
					throw new \Exception($intervencao);
				}
			}
			
			$os_troca = $this->verificaOsTroca($this->_os);

			if($this->_model->verificaDefeitoConstatado($con) === false and $os_revisao == false and $os_troca == false and $valida_constatado == true){
				throw new \Exception("A OS ".$this->_sua_os." sem Defeito Constatado preenchido");
			}

			if(in_array($this->_fabrica,array(178))){
				if($this->_model->verificaTipoAtendimento($con) === false){
					throw new \Exception("A OS ".$this->_sua_os." sem Tipo de Atendimento preenchido");
				}
			}
			
			if (in_array($this->_fabrica,array(169,170)) and $os_troca == false){
				$retorno = $this->_model->verificaDefeitoPecaSemDefeito($con);

				if($retorno !== true){
					throw new \Exception($retorno);
				}
			}

			if ($this->_fabrica == 177){
				$retorno = $this->_model->verificaPecaLote($con);
				if($retorno !== true){
					throw new \Exception($retorno);
				}
			}
			$abre_atendimento = \Posvenda\Regras::get("abre_atendimento", "ordem_de_servico", $this->_fabrica);
			if($abre_atendimento){
				$this->abreAtendimento($this->_os,$this->_fabrica);
			}

			if(!$this->_model->finalizaOS($con, $origem)){
				throw new \Exception("Erro ao Finalizar a OS ".$this->_sua_os);
			}

			if (in_array($this->_fabrica,array(120,201))){

				$hd_chamado = $this->verificaAtendimentoCallcenter($this->_os);
				if (strlen($hd_chamado ) > 0) {

					$comentario = "A OS ".$this->_sua_os." relacionada ao atendimento foi finalizada";
					$this->finalizaAtendimentoCallcenter($hd_chamado, $comentario);
				}
			}

			if ($this->_fabrica == 198) {
				$os  = $this->_os; 
				$sql = "SELECT os 
						FROM tbl_os_produto
						JOIN tbl_os_item USING(os_produto)
						WHERE tbl_os_produto.os = {$os}";
				$nQuery = pg_query($con, $sql);

				if ($nQuery !== false) {
					$sql2 = "UPDATE tbl_os 
							SET status_checkpoint = 52
							WHERE os = {$os}
							AND fabrica = {$this->_fabrica}";
					$nQuery2 = pg_query($con, $sql2);

					if ($nQuery2 === false) {
						throw new \Exception("Erro ao alterar Status da OS: $os #1");
					}
				}
			}
			

			if(in_array($this->_fabrica, [169,170])) {
				
				$this->enviaPesquisaTrackSale($this->_os);
			}
		}
	}

	public function getOsGarantia($param, $os = null, $estados = null, $porPeca = false){

		$os_garantia = $this->_model->selectOsGarantia($param, $os, $estados, $porPeca);

		return $os_garantia;

	}

	public function getOsGarantiaPostoInterno($param, $os = null,$estados = null, $manual = false){

		$os_garantia = $this->_model->selectOsGarantiaPostoInterno($param, $os, $estados, $manual);

		return $os_garantia;

	}

	public function getOsPosto($posto = "", $marca = null, $os = null) {

		if(empty($posto)){
			throw new \Exception("Posto não informado");
		}

		$os_pedido_posto = $this->_model->selectOsPosto($posto, $marca, $os);

		return $os_pedido_posto;

	}

	public function getOsPostoEstoque($posto = "", $os = null) {

		if(empty($posto)){
			throw new \Exception("Posto não informado");
		}

		$os_pedido_posto = $this->_model->selectOsPostoEstoque($posto, $marca, $os);

		return $os_pedido_posto;

	}

	public function getOsTrocaPosto($posto = "", $marca = null, $os = null){

		if(empty($posto)){
			throw new \Exception("Posto não informado para selecionar a OS Troca");
		}

		$os_pedido_posto = $this->_model->selectOsTrocaPosto($posto, $marca, $os);

		return $os_pedido_posto;

	}

	public function getTrocaPosto($posto = "", $marca = null, $os = null){

		if(empty($posto)){
			throw new \Exception("Posto não informado para selecionar a OS Troca");
		}

		$os_posto = $this->_model->selectOsTroca($posto, $marca, $os);

		return $os_posto;

	}

	public function getDistribuidorOsTroca($os = null) {
		// default para a OS do objeto se não passou um valor
		$whereOs = ($os) ? : $this->_os;

		$pdo = $this->_model->getPDO();

		$sql = "SELECT distribuidor FROM tbl_os_troca WHERE os = $whereOs";
		$res = $pdo->query($sql);

		$distrib = $res->fetch();
		if (isset($distrib['distribuidor'])) {
			return $distrib['distribuidor'];
		}
		return null;
	}

	public function setPedidoOsTroca($pedido, $os = null) {
		// default para a OS do objeto se não passou um valor
		$whereOs = ($os) ? : $this->_os;

		if (!is_numeric($pedido))
			throw new \Exception ("Pedido inválido para OS de Troca");

		$pdo = $this->_model->getPDO();
		$res = $pdo->query("UPDATE tbl_os_troca SET pedido = $pedido WHERE os = $whereOs");

		return !($res === false);

	}

	public function getPecasPedidoGarantia($os, $manual = false) {

		if (empty($os)) {
			$os = $this->_os;
		}

		if (empty($os)) {
			return false;
		}

		$nao_verifica_estoque = \Posvenda\Regras::get("nao_verifica_estoque", "pedido_garantia", $this->_fabrica);
		if (!$nao_verifica_estoque) {
		    $wherePecaEstoque = "AND tbl_servico_realizado.peca_estoque IS NOT TRUE";
		}

		$verifica_os_pedido_obrigatorio = \Posvenda\Regras::get("verifica_os_pedido_obrigatorio", "pedido_garantia", $this->_fabrica);

		if ($verifica_os_pedido_obrigatorio == true) {
            $whereServicoRealizado = "AND (tbl_servico_realizado.gera_pedido IS TRUE OR tbl_os_extra.obs_adicionais::jsonb->>'gera_pedido_obrigatorio' = 'true')";
        } else {
            $whereServicoRealizado = 'AND tbl_servico_realizado.gera_pedido IS TRUE';
        }

		$pdo = $this->_model->getPDO();
		$condPA = " AND tbl_peca.produto_acabado IS NOT TRUE";
		if ($manual) {
			$condPA = "";
			$whereServicoRealizado = "";
			$wherePecaEstoque = "";
		}

		$posto_interno = $this->verificaPostoInterno($os);

		if ($this->_fabrica == 191 && $posto_interno){
			$condLiberado = " AND tbl_os_item.liberacao_pedido IS TRUE ";
		}

		$sql = "SELECT
					tbl_os_item.os_item,
					tbl_os_item.peca,
					tbl_peca.referencia,
					tbl_os_item.qtde,
					tbl_os_item.causa_defeito
				FROM tbl_os_item
				INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
				INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
				INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os_produto.os
				WHERE tbl_os_produto.os = {$os}
				{$condPA}
				{$condLiberado}
				AND tbl_os_item.pedido IS NULL
				{$whereServicoRealizado}
				{$wherePecaEstoque}";
		$query = $pdo->query($sql);

		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		return $res;

	}

	public function getPecasPedidoGarantiaEstoque($os) {

		if (empty($os)) {
			$os = $this->_os;
		}

		$pdo = $this->_model->getPDO();

		$sql = "SELECT
					tbl_os_item.os_item,
					tbl_os_item.peca,
					tbl_peca.referencia,
					tbl_os_item.qtde
				FROM tbl_os_item
				INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
				INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
				WHERE tbl_os_produto.os = {$os}
				AND tbl_peca.produto_acabado IS NOT TRUE
				AND tbl_os_item.pedido IS NULL
				AND tbl_servico_realizado.peca_estoque IS TRUE";
		$query = $pdo->query($sql);

		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		return $res;

	}

	public function getPecasPedidoGarantiaTroca($os) {

		if (empty($os)) {
			$os = $this->_os;
		}

		$pdo = $this->_model->getPDO();

		$sql = "SELECT
					tbl_os_item.os_item,
					tbl_os_item.peca,
					tbl_os_item.qtde,
					tbl_os_item.causa_defeito
				FROM tbl_os_item
				INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
				INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
				WHERE tbl_os_produto.os = {$os}
				AND tbl_peca.produto_acabado IS TRUE
				AND tbl_os_item.pedido IS NULL
				AND tbl_servico_realizado.gera_pedido IS TRUE
				AND tbl_servico_realizado.peca_estoque IS NOT TRUE";
		$query = $pdo->query($sql);

		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		return $res;

	}

	public function getPecasProdutoAcabado($referencia) {

		$pdo = $this->_model->getPDO();

		$sql = "SELECT peca
				  FROM tbl_peca 
				 WHERE referencia = '{$referencia}'
				   AND fabrica = {$this->_fabrica} 
				   AND produto_acabado IS TRUE";
		$query = $pdo->query($sql);

		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		return $res[0]["peca"];

	}

	public function getServicoRealizado($codigo) {

		$pdo = $this->_model->getPDO();

		$sql = "SELECT servico_realizado
				  FROM tbl_servico_realizado
				 WHERE codigo_servico = '{$codigo}'
				   AND fabrica = {$this->_fabrica}";
		$query = $pdo->query($sql);

		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		return $res[0]["servico_realizado"];

	}

	public function verificaPostoInterno($os=null,$posto=null){

			$pdo = $this->_model->getPDO();

			if(!empty($posto)){

				$sql = "SELECT tbl_tipo_posto.posto_interno
							FROM tbl_posto_fabrica
							INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
				WHERE tbl_posto_fabrica.fabrica = {$this->_fabrica}
				AND tbl_tipo_posto.posto_interno is true
				AND tbl_posto.posto= {$posto}";
				$query = $pdo->query($sql);

				$res = $query->fetchAll(\PDO::FETCH_ASSOC);

				return (count($res) > 0) ? true : false;
			}

			if(!empty($os)){

				$sql = "SELECT tbl_tipo_posto.posto_interno
						  FROM tbl_os
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
				INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
				WHERE tbl_os.fabrica = {$this->_fabrica}
				AND tbl_tipo_posto.posto_interno is true
				AND tbl_os.os = {$os}";
				$query = $pdo->query($sql);

				$res = $query->fetchAll(\PDO::FETCH_ASSOC);

				return (count($res) > 0) ? true : false;
			}

			return false;

	}

	public function gravaIntegridadeComPeca($os){

		$pdo = $this->_model->getPDO();

		 $sql = "SELECT tbl_peca.peca ,
								tbl_produto.produto
							FROM tbl_os
								INNER JOIN tbl_produto on tbl_os.produto = tbl_produto.produto and tbl_produto.fabrica_i = {$this->_fabrica}
								INNER JOIN tbl_peca on tbl_produto.referencia = tbl_peca.referencia and tbl_peca.fabrica = {$this->_fabrica}
								WHERE tbl_os.os = {$os}
							AND tbl_peca.produto_acabado IS TRUE
							AND tbl_os.fabrica = {$this->_fabrica} LIMIT 1";

		$query = $pdo->query($sql);

		if($query == false){
			throw new \Exception("Erro ao fechar OS :$os #1");
		}

		if($query->rowCount() == 0 ){

			$sql = "SELECT tbl_produto.descricao,
								tbl_produto.referencia,
								tbl_produto.origem
					FROM tbl_produto
					INNER JOIN tbl_os on tbl_os.produto = tbl_produto.produto  AND tbl_produto.fabrica_i =  {$this->_fabrica}
					WHERE tbl_os.os = {$os}
					AND tbl_os.fabrica = {$this->_fabrica}";

			$query = $pdo->query($sql);
			if($query == false){
				throw new \Exception("Erro ao fechar OS :$os #2");
			}

			if($query->rowCount()>0){

				$res = $query->fetchAll(\PDO::FETCH_ASSOC);

				$descricao = $res[0]["descricao"];
				$referencia =$res[0]["referencia"];
				$origem =$res[0]["origem"];

				$sql = "INSERT INTO tbl_peca(fabrica,referencia,descricao,origem,ativo,produto_acabado)
									VALUES ({$this->_fabrica} ,  '{$referencia}' ,'{$descricao}' ,'{$origem}' ,true , true)";
				$query = $pdo->query($sql);
				if($query == false){
					throw new \Exception("Erro ao fechar OS :$os #3");
				}
				if($query->rowCount()>0){

				   $sql = "SELECT tbl_peca.peca
							FROM tbl_os
								INNER JOIN tbl_produto on tbl_os.produto = tbl_produto.produto and tbl_produto.fabrica_i = {$this->_fabrica}
								INNER JOIN tbl_peca on tbl_produto.referencia = tbl_peca.referencia and tbl_peca.fabrica = {$this->_fabrica}
								WHERE tbl_os.os = {$os}
							AND tbl_peca.produto_acabado IS TRUE
							AND tbl_os.fabrica = {$this->_fabrica} LIMIT 1";

					$query = $pdo->query($sql);
					if($query == false){
						throw new \Exception("Erro ao fechar OS :$os #4");
					}
				}

			}else{
				throw new \Exception("Erro ao fechar OS :$os #5");
			}

		}
		$res_prod = $query->fetchAll(\PDO::FETCH_ASSOC);

		$peca = $res_prod[0]["peca"];
		$produto = $res_prod[0]["produto"];

		$sql = "SELECT tbl_lista_basica.peca
							FROM tbl_lista_basica
					WHERE tbl_lista_basica.produto = {$produto}
					AND tbl_lista_basica.peca = {$peca}";

		$query = $pdo->query($sql);

		if($query == false){
			throw new \Exception("Erro ao fechar OS :$os #6");
		}

		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		if(count($res) == 0){

			$sql = "INSERT INTO tbl_lista_basica(produto,peca,qtde,fabrica,ativo)
							VALUES ({$produto} , {$peca} , 1 , {$this->_fabrica} , true)";

			$query = $pdo->query($sql);

			if($query == false){
				throw new \Exception("Erro ao fechar OS :$os #7");
			}else{
				return true;
			}
		}else{
			return true;
		}
	}

	public function gravaFaturamento($os, $posto , $data_nf , $nota_fiscal  , $valor_fiscal ){

		$pdo = $this->_model->getPDO();

		if(empty($os) or empty($posto) or empty($data_nf) or empty($nota_fiscal) or empty($valor_fiscal)){

			throw new \Exception("Erro ao finalizar OS #1");

		}else{

			$sql = "SELECT tbl_peca.peca
							FROM tbl_os
								INNER JOIN tbl_produto on tbl_os.produto = tbl_produto.produto and tbl_produto.fabrica_i = {$this->_fabrica}
								INNER JOIN tbl_peca on tbl_produto.referencia = tbl_peca.referencia and tbl_peca.fabrica = {$this->_fabrica}
								WHERE tbl_os.os = {$os}
							AND tbl_peca.produto_acabado IS TRUE
							AND tbl_os.fabrica = {$this->_fabrica} LIMIT 1";

			$query = $pdo->query($sql);

			if($query == false){
				throw new \Exception("Erro ao fechar OS :$os #7");
			}

			$res  = $query->fetchAll(\PDO::FETCH_ASSOC);

			if(count($res) > 0){
				$peca = $res[0]["peca"];
			}else{
				throw new \Exception("Erro ao fechar OS :$os #7");
			}

			$sql = "SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = {$this->_fabrica} and descricao = 'SAÍDA' ";
			$query = $pdo->query($sql);

			if($query == false){
				throw new \Exception("Erro ao fechar OS :$os #7");
			}

			$res = $query->fetchAll(\PDO::FETCH_ASSOC);

			if(count($res) > 0){

				$tipo_pedido = $res[0]["tipo_pedido"];



				$sql = "INSERT INTO tbl_faturamento
								(fabrica ,emissao ,saida ,posto ,total_nota ,nota_fiscal ,tipo_pedido)
								VALUES
								({$this->_fabrica}, '{$data_nf}', '{$data_nf}', {$posto}, {$valor_fiscal}, '{$nota_fiscal}', '{$tipo_pedido}') RETURNING faturamento ";
				$query = $pdo->query($sql);

				if($query != false){

					$res = $query->fetchAll(\PDO::FETCH_ASSOC);

					if(count($res) > 0){

						$faturamento = $res[0]['faturamento'];

						$sql = "INSERT INTO tbl_faturamento_item
								(faturamento,peca,os,qtde,preco)
								VALUES
								( {$faturamento}, {$peca}, {$os}, 1 , {$valor_fiscal} )";
						$query = $pdo->query($sql);

						if($query != false){
							return true;
						}else{
							throw new \Exception("Erro ao fechar OS :$os #7");
						}
					}
				}
			}else{
				throw new \Exception("Erro ao fechar OS :$os #7");
			}
		}
	}

	public function verificaServicoUsaEstoque($servico, $con = null){
		if (is_null($con)) {
			$pdo = $this->_model->getPDO();
		}

		$sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$this->_fabrica} AND servico_realizado = {$servico} AND peca_estoque IS TRUE";

		if (is_null($con)) {
			$query = $pdo->query($sql);
			$res = $query->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			$query = pg_query($con, $sql);
			$res = pg_fetch_all($query);
		}

		return (is_array($res) && count($res) > 0) ? true : false;

	}

	public function verificaServicoGeraPedido($servico, $con = null) {
		if (is_null($con)) {
			$pdo = $this->_model->getPDO();
		}

		$sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$this->_fabrica} AND servico_realizado = {$servico} AND gera_pedido IS TRUE AND troca_de_peca IS TRUE";

		if (is_null($con)) {
			$query = $pdo->query($sql);
			$res = $query->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			$query = pg_query($con, $sql);
			$res = pg_fetch_all($query);
		}

		return (is_array($res) && count($res) > 0) ? true : false;
	}

	public function verificaServicoLancadoEstoque($os_item, $con = null) {
		if (is_null($con)) {
			$pdo = $this->_model->getPDO();
		}

		$sql = "SELECT servico_realizado FROM tbl_os_item JOIN tbl_servico_realizado USING(servico_realizado) WHERE os_item = {$os_item} AND peca_estoque IS TRUE;";

		if (is_null($con)) {
			$query = $pdo->query($sql);
			$res = $query->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			$query = pg_query($con, $sql);
			$res = pg_fetch_all($query);
		}

		return (is_array($res) && count($res) > 0) ? true : false;
	}

	public function verificaServicoAjuste($servico, $con = null) {
		if (is_null($con)) {
			$pdo = $this->_model->getPDO();
		}

		$sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$this->_fabrica} AND servico_realizado = {$servico} AND gera_pedido IS FALSE AND troca_de_peca IS FALSE";

		if (is_null($con)) {
			$query = $pdo->query($sql);
			$res = $query->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			$query = pg_query($con, $sql);
			$res = pg_fetch_all($query);
		}

		return (is_array($res) && count($res) > 0) ? true : false;
	}

	public function verificaEstoquePosto($posto, $peca, $qtde, $con = null){
		if (is_null($con)) {
			$pdo = $this->_model->getPDO();
		}

		$sql = "SELECT qtde FROM tbl_estoque_posto WHERE fabrica = {$this->_fabrica} AND posto = {$posto} AND peca = {$peca}";
		if (is_null($con)) {
			$query = $pdo->query($sql);
			$res = $query->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			$query = pg_query($con, $sql);
			$res = pg_fetch_all($query);
		}

		if(is_array($res) && count($res) > 0){
			$qtde_estoque = $res[0]["qtde"];

			return ((int) $qtde_estoque >= $qtde) ? true : false;

		}else{
			return false;
		}
	}

	public function atualizaEstoquePosto($posto, $peca, $qtde, $operacao, $con = null){
		if (is_null($con)) {
			$pdo = $this->_model->getPDO();
		}

		$operador = (strtoupper($operacao) == "SAIDA") ? "-" : "+";

		$sql = "UPDATE tbl_estoque_posto SET qtde = (qtde $operador {$qtde})
				WHERE
					fabrica = {$this->_fabrica}
					AND posto = {$posto}
					AND peca = {$peca}";

		if (is_null($con)) {
			$query = $pdo->query($sql);
		} else {
			$query = pg_query($con, $sql);
		}

		return ($query == false) ? false : true;

	}

	public function verificaLancamentoPeca($posto, $peca, $qtde, $os, $os_item, $con = null){
		if (is_null($con)) {
			$pdo = $this->_model->getPDO();
		}

		$sql = "SELECT qtde_saida FROM tbl_estoque_posto_movimento
				WHERE
					fabrica = {$this->_fabrica}
					AND posto = {$posto}
					AND peca = {$peca}
					AND os = {$os}
					AND os_item = {$os_item}";

		if (is_null($con)) {
			$query = $pdo->query($sql);
			$res = $query->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			$query = pg_query($con, $sql);
			$res = pg_fetch_all($query);
		}

		if(is_array($res) && count($res) > 0){
			$qtde_estoque = $res[0]["qtde_saida"];

			if((int)$qtde_estoque == $qtde){

				return true;

			}else{

				$sql = "DELETE FROM tbl_estoque_posto_movimento
					WHERE
						fabrica = {$this->_fabrica}
						AND posto = {$posto}
						AND peca = {$peca}
						AND os = {$os}
						AND os_item = {$os_item}";

				if (is_null($con)) {
					$query = $pdo->query($sql);
				} else {
					$query = pg_query($con, $sql);
				}

				$this->atualizaEstoquePosto($posto, $peca, $qtde_estoque, "entrada", $con);

				return false;

			}

		}else{

			return false;

		}
	}

	public function getSuaOs ($os){
		$pdo = $this->_model->getPDO();
		
		$sql = "SELECT sua_os FROM tbl_os WHERE os = {$os} AND fabrica = $this->_fabrica";
		$query = $pdo->query($sql);
	
		$res = $query->fetchAll(\PDO::FETCH_ASSOC);
	
		return $res[0]["sua_os"];
	}

	public function lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, $operacao, $con = null, $unidade_negocio = null){
		if (is_null($con)) {
			$pdo = $this->_model->getPDO();
		}

		$status_lancamento_peca = $this->verificaLancamentoPeca($posto, $peca, $qtde, $os, $os_item, $con);

		$sua_os = $this->getSuaOs($os);
		
		if($status_lancamento_peca == false){
			$campo_operacao = (strtoupper($operacao) == "SAIDA") ? "qtde_saida" : "qtde_entrada";

			$obs = "Peça utilizada na OS <strong>{$sua_os}</strong>";

			if (empty($data_nf)) {
				$insereData = 'NULL';
			} else {
				$insereData = "'{$data_nf}'";
			}

			if (empty($unidade_negocio)) {
				$insereCampoAdicionais = '';
				$insereValorAdicionais = '';
			} else {
				$insereCampoAdicionais = ",parametros_adicionais";
				$insereValorAdicionais = ",'{\"unidadeNegocio\":\"$unidade_negocio\"}'";
			}

			$sql = "INSERT INTO tbl_estoque_posto_movimento
					(fabrica, posto, peca, {$campo_operacao}, os, os_item, nf, obs, data {$insereCampoAdicionais})
					VALUES
					({$this->_fabrica}, {$posto}, {$peca}, {$qtde}, {$os}, {$os_item}, '{$nota_fiscal}', '{$obs}', $insereData {$insereValorAdicionais})";

			if (is_null($con)) {
				$query = $pdo->query($sql);
			} else {
				$query = pg_query($con, $sql);
			}

			if($query != false){
				$this->atualizaEstoquePosto($posto, $peca, $qtde, "saida", $con);
			}
			return ($query == false) ? false : true;
		}else{
			return true;
		}
	}

	public function excluiMovimentacaoEstoque($posto, $peca, $os, $os_item, $con = null){
		if (is_null($con)) {
			$pdo = $this->_model->getPDO();
		}

		$sql = "SELECT qtde_saida FROM tbl_estoque_posto_movimento
				WHERE
					fabrica = {$this->_fabrica}
					AND posto = {$posto}
					AND peca = {$peca}
					AND os = {$os}
					AND os_item = {$os_item}";

		if (is_null($con)) {
			$query = $pdo->query($sql);
			$res = $query->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			$query = pg_query($con, $sql);
			$res = pg_fetch_all($query);
		}

		if (is_array($res) && count($res) > 0) {
			$qtde = $res[0]["qtde_saida"];

			$this->atualizaEstoquePosto($posto, $peca, $qtde, "entrada", $con);

			$sql = "DELETE FROM tbl_estoque_posto_movimento
					WHERE
						fabrica = {$this->_fabrica}
						AND posto = {$posto}
						AND peca = {$peca}
						AND os = {$os}
						AND os_item = {$os_item}";

			if (is_null($con)) {
				$query = $pdo->query($sql);
			} else {
				$query = pg_query($con, $sql);
			}

			return ($query == false) ? false : true;
		} else {
			return false;
		}
	}

	public function verificaLancamentoPecaOsTroca($os){

		$pdo = $this->_model->getPDO();

		$sql = "SELECT
					tbl_estoque_posto_movimento.qtde_saida
				FROM tbl_os
				JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.peca_estoque IS TRUE
				JOIN tbl_estoque_posto_movimento ON tbl_estoque_posto_movimento.peca = tbl_os_item.peca AND tbl_estoque_posto_movimento.os = {$os}
				WHERE
					tbl_os.os = {$os}
					AND tbl_os.fabrica = {$this->_fabrica}";
		$query = $pdo->query($sql);

		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		return (count($res) > 0) ? true : false;

	}

	public function postoControlaEstoque($posto, $con = null){
		if (is_null($con)) {
			$pdo = $this->_model->getPDO();
		}

		$sql = "SELECT controla_estoque FROM tbl_posto_fabrica WHERE fabrica = {$this->_fabrica} AND posto = {$posto}";

		if (is_null($con)) {
			$query = $pdo->query($sql);
			$res = $query->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			$query = pg_query($con, $sql);
			$res = pg_fetch_all($query);
		}

		if(is_array($res) && count($res) > 0){
			return ($res[0]["controla_estoque"] == "t") ? true : false;
		}else{
			return false;
		}

	}

	public function verificaTipoAtendimento($os,$tipoAtendimento){

		$pdo = $this->_model->getPDO();

		$sql = "SELECT {$tipoAtendimento} FROM tbl_os INNER JOIN tbl_tipo_atendimento USING(tipo_atendimento) WHERE tbl_os.fabrica = {$this->_fabrica} AND tbl_os.os = {$os}";
		$query = $pdo->query($sql);

		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		if(count($res) > 0){
			return ($res[0][$tipoAtendimento] == "t") ? true : false;
		}else{
			return false;
		}

	}

	public function verificaAtendimentoCallcenter($os){

		$pdo = $this->_model->getPDO();

		$sql = "SELECT hd_chamado FROM tbl_hd_chamado_extra JOIN tbl_hd_chamado USING(hd_chamado) WHERE os = {$os} and tbl_hd_chamado.posto ISNULL AND titulo <> 'Help-Desk Posto'";
		$query = $pdo->query($sql);

		if($query->rowCount() > 0){
			$res = $query->fetchAll(\PDO::FETCH_ASSOC);

			return $res[0]["hd_chamado"];
		}else{
			$sql = "SELECT hd_chamado FROM tbl_hd_chamado_item WHERE os = {$os}";
			$query = $pdo->query($sql);

			if($query->rowCount() > 0){
				$res = $query->fetchAll(\PDO::FETCH_ASSOC);

				return $res[0]["hd_chamado"];
			}else{
				return false;
			}
		}
	}

	public function verifica_os_revenda(){
		if (empty($os)) {
			$os = $this->_os;
		}

		$pdo = $this->_model->getPDO();

		$sql = "SELECT consumidor_revenda FROM tbl_os WHERE os = {$os}";
		$query = $pdo->query($sql);

		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		if(count($res) > 0 && $res[0]["consumidor_revenda"] == "R"){
			return true;
		}else{
			return false;
		}
	}

	public function verificaOsTroca($os){

		$pdo = $this->_model->getPDO();

		$sql = "SELECT os FROM tbl_os_troca WHERE os = {$os}";
		$query = $pdo->query($sql);

		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		if(count($res) > 0){
			return true;
		}else{
			return false;
		}
	}

	public function verificaPedido($os_item)
	{
		$pdo = $this->_model->getPDO();

		if (empty($os_item)) {
			return false;
		}

		$sql = "
			SELECT
				pedido
			FROM tbl_os_item
			WHERE os_item = {$os_item};
		";

		$query = $pdo->query($sql);

		if (!$query) {
			return false;
		}

		$res = $query->fetch(\PDO::FETCH_ASSOC);

		if ($res) {
			return $res['pedido'];
		} else {
			return false;
		}
	}

	public function getOsPedido($os)
	{
		$pdo = $this->_model->getPDO();

		$sql = "
			SELECT
				pc.peca,
				pc.referencia,
                pc.unidade,
                o.os,
                pf.centro_custo,
                ta.codigo AS codigo_tipo_atendimento,
                oi.os_item,
                p.pedido,
                p.status_pedido,
                TO_CHAR(p.data, 'YYYYMMDD') AS data_pedido,
                '' AS nf,
                oi.qtde AS qtde_pedido,
                '' AS unidade_negocio
			FROM tbl_os o
			JOIN tbl_os_campo_extra oce USING(os,fabrica)
			JOIN tbl_posto_fabrica pf USING(posto,fabrica)
			JOIN tbl_tipo_atendimento ta USING(tipo_atendimento,fabrica)
            JOIN tbl_os_produto op USING(os)
			JOIN tbl_os_item oi USING(os_produto)
			JOIN tbl_peca pc USING(peca,fabrica)
			LEFT JOIN tbl_pedido p USING(pedido,fabrica)
			WHERE o.os = {$os}
			AND o.fabrica = {$this->_fabrica}
		";
		$query = $pdo->query($sql);

		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		return $res;
	}

    public function getFabrica(){
        return $this->_fabrica;
    }

	public function geraSolicitacaoPostagem($os, $tipoPostagem = "A", $remetente = null, $destinatario = null){

		if (empty($os)) {
			$os = $this->_os;
		}

		$pdo = $this->_model->getPDO();

		$sql = "SELECT
						usuario,
						senha,
						codigo as codAdministrativo,
						contrato,
						cartao
			FROM tbl_fabrica_correios
			WHERE fabrica = $login_fabrica";
		$res = $pdo->query($sql);
		$res = $query->fetchAll(\PDO::FETCH_ASSOC);
		if(count($res) == 0) {
			throw new \Exception('Fabrica não liberada para este recurso! consulte nosso suporte');
		}

		$usuario = $res[0]["usuario"];
		$senha = $res[0]["senha"];
		$codAdministrativo = $res[0]["codAdministrativo"];
		$contrato = $res[0]["contrato"];
		$cartao = $res[0]["cartao"];

		if($login_fabrica==157){
			$senha = "edilene.nascimento@elgin.com.br";
		}

		if (1==1){
			$usuario = '60618043';
			$senha = '8o8otn';
			$codAdministrativo='08082650';
			$contrato = '9912208555';
			$cartao = '0057018901';
			$tipoPostagem = '41076';
			// Senha => 8o8otn
			// Cód Administrativo => 08082650
			// Contrato => 9912208555
			// Cód Serv => 41076
			// Cartão => 0057018901
		}


		if (empty($usuario) || empty($senha) || empty($codAdministrativo) || empty($contrato) || empty($cartao)){
			throw new \Exception('Dados incompletos para fazer a Solicitação! consulte nosso suporte');
		}

		$cond_posto = "tbl_os.os = {$os}";
		if(!empty($remetente)){
			$cond_posto = "tbl_posto.posto = {$remetente}";
		}

		$sql = "SELECT
				  tbl_posto.posto as posto_id,
				   tbl_fabrica.nome as fabrica,
				   tbl_produto.referencia || ' - ' ||tbl_produto.descricao AS produto,
				   tbl_posto.nome as remetente_nome,
				   tbl_posto_fabrica.contato_endereco as remetente_endereco,
				   tbl_posto_fabrica.contato_bairro as remetente_bairro,
				   tbl_posto_fabrica.contato_numero as remetente_numero,
				   tbl_posto_fabrica.contato_cidade as remetente_cidade,
				   tbl_posto_fabrica.contato_estado as remetente_estado,
				   tbl_posto_fabrica.contato_cep    as remetente_cep,
				   tbl_posto.email as remetente_email,
				   tbl_posto_fabrica.contato_complemento as remetente_complemento,
				   tbl_posto_fabrica.contato_fone_comercial as remetente_fone
				FROM tbl_posto
				LEFT JOIN tbl_os               ON tbl_posto.posto             = tbl_os.posto AND  tbl_os.fabrica = {$login_fabrica}
				LEFT JOIN tbl_produto                ON tbl_produto.produto      = tbl_os.produto AND  tbl_produto.fabrica_i = {$login_fabrica}
						JOIN tbl_posto_fabrica       ON tbl_posto.posto             = tbl_posto_fabrica.posto AND  tbl_posto_fabrica.fabrica = {$login_fabrica}
						JOIN tbl_fabrica                 ON tbl_os.fabrica                 = tbl_fabrica.fabrica  AND tbl_fabrica.fabrica = {$login_fabrica}
				WHERE
								{$cond_posto}";
		$resRemetente = $pdo->query($sql);
		$resRemetente = $query->fetchAll(\PDO::FETCH_ASSOC);

		if(count($resRemetente) == 0) {
			throw new \Exception('Erro ao buscar dados do remetente.');
		}

		$cond_posto = "tbl_fabrica.posto_interno = tbl_posto.posto ";
		if(!empty($destinatario)){
			$cond_posto = "tbl_posto.posto = {$destinatario}";
		}

	   $sql = "SELECT
					 tbl_posto.posto as posto_id,
					 tbl_fabrica.nome as fabrica,
					 -- tbl_produto.referencia || ' - ' ||tbl_produto.descricao AS produto,
					 tbl_posto.nome as destinatario_nome,
					 tbl_posto_fabrica.contato_endereco as destinatario_endereco,
					 tbl_posto_fabrica.contato_bairro as destinatario_bairro,
					 tbl_posto_fabrica.contato_numero as destinatario_numero,
					 tbl_posto_fabrica.contato_cidade as destinatario_cidade,
					 tbl_posto_fabrica.contato_estado as destinatario_estado,
					 tbl_posto_fabrica.contato_cep    as destinatario_cep,
					 -- tbl_posto.email as destinatario_email,
					 tbl_posto_fabrica.contato_complemento as destinatario_complemento,
					 tbl_posto_fabrica.contato_fone_comercial as destinatario_fone
				FROM tbl_posto
		-- LEFT JOIN tbl_os            ON tbl_posto.posto           = tbl_os.posto            AND tbl_os.fabrica            = {$login_fabrica}
		-- LEFT JOIN tbl_produto       ON tbl_produto.produto       = tbl_os.produto          AND tbl_produto.fabrica_i     = {$login_fabrica}
				JOIN tbl_posto_fabrica ON tbl_posto.posto           = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_fabrica       ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica     AND tbl_fabrica.fabrica       = {$login_fabrica}
				WHERE
				{$cond_posto}";
		$resDestinatario = $pdo->query($sql);
		$resDestinatario = $query->fetchAll(\PDO::FETCH_ASSOC);

		if(count($resRemetente) == 0) {
			throw new \Exception('Erro ao buscar dados do Remetente Entre em contato com suporte Telecontrol.');
		}

		/*
			Codigo do serviço é codigo do mode de envio SEDEX , PAC E_SEDEX
		*/
		$request =  (object) array(
			'usuario'           => $usuario,
			'senha'             => $senha,
			'codAdministrativo' => $codAdministrativo,
			'contrato'          => $contrato,
			'codigo_servico'    => '41076',
			'cartao'            => $cartao ,
			'destinatario'      => (object) array(
				'nome'       => utf8_encode($resDestinatario[0]["destinatario_nome"]),
				'logradouro' => utf8_encode($resDestinatario[0]["destinatario_endereco"]),
				'numero'     => $resDestinatario[0]["destinatario_numero"],
				'cidade'     => utf8_encode($resDestinatario[0]["destinatario_cidade"]),
				'uf'         => $resDestinatario[0]["destinatario_estado"],
				'bairro'     => utf8_encode($resDestinatario[0]["destinatario_bairro"]),
				'cep'        => $resDestinatario[0]["destinatario_cep"],
			),
			'coletas_solicitadas' =>  (object) array(
				'tipo'       => $tipoPostagem,
				'descricao'  => utf8_encode($resDestinatario['produto']),
				'id_cliente' => $os,
				'remetente'  => (object)   array(
					'nome'       => $resRemetente['rementente_nome'],
					'logradouro' => utf8_encode($resRemetente['remetente_endereco']),
					'numero'     => $resRemetente['remetente_numero'],
					'bairro'     => utf8_encode($resRemetente['remetente_bairro']),
					'cidade'     => utf8_encode($resRemetente['remetente_cidade']),
					'uf'         => $resRemetente['remetente_estado'],
					'cep'        => $resRemetente['remetente_cep'],
				),
				// 'valor_declarado' => $array_dados['valor_nf'],
				// 'ag' => '15',
				// 'ar'=>'1',
				'obj_col' => (object) array(
					'item' => 1,
					'id'   => $os.":".$resRemetente['posto_id'],
					'desc' => utf8_encode($resDestinatario['produto'])
				),
				'ag' => 30
			)
		);

		// comentado... ?!?
		// print_r($array_request);
		// exit;

		try{

			// $url = "https://apphom.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";
			$url = "https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";
			$client  = new SoapClient($url, array("trace" => 1, "exception" => 1));
			// $result  = $client->__soapCall("verificaDisponibilidadeServico", array($array_request));

			$result  = $client->__soapCall("SolicitarPostagemReversa", array($array_request));

			if($result->return->cod_erro == '00'){

				$numero_postagem = $result->return->resultado_solicitacao->numero_coleta;
				$tipo = $result->return->resultado_solicitacao->tipo ;
				$comentario = $result->return->resultado_solicitacao;

				foreach($comentario as  $key => $value) {
					$string .= "<b>$key</b>: $value <br>";
				}


				$string_array = explode("<br>", $string);

				$tipo                = explode(":", $string_array[0]);
				$atendimento         = explode(":", $string_array[1]);
				$numero_autorizacao  = explode(":", $string_array[2]);
				$numero_etiqueta     = explode(":", $string_array[3]);
				$status              = explode(":", $string_array[5]);
				$prazo_postagem      = explode(":", $string_array[6]);
				$data_solicitacao    = explode(":", $string_array[7]);
				$horario_solicitacao = explode(" ", $string_array[8]);


				$status_solicitacao = "<strong>Tipo Solicitação:</strong> ".trim($tipo[1])."<br />";
				$status_solicitacao .= "<strong>Atendimento:</strong> ".trim($atendimento[1])."<br />";
				// $status_solicitacao .= "<strong>Número Autorização:</strong> ".trim($numero_autorizacao[1])."<br />";
				$status_solicitacao .= "<strong>Número Etiqueta:</strong> ".trim($numero_etiqueta[1])."<br />";
				$status_solicitacao .= "<strong>Modo de Envio:</strong> ".trim($modo_envio)."<br />";
				$status_solicitacao .= "<strong>Status:</strong> ".trim($status[1])."<br />";
				$status_solicitacao .= "<strong>Prazo de Postagem:</strong> ".trim($prazo_postagem[1])."<br />";
				$status_solicitacao .= "<strong>Data da Solicitação:</strong> ".trim($data_solicitacao[1])."<br />";
				$status_solicitacao .= "<strong>Horário da Solicitação:</strong> ".trim($horario_solicitacao[1])."<br />";

				return true;
			}else{
				 foreach ($result->return as $key => $value) {
					if($key == "msg_erro"){
						$ret .= "<div class='container' style='width: 800px;'>
										<div class='alert alert-danger'>
											<h4>".utf8_decode($value)."</h4>
										</div>
									</div>";
					}
				}
			}
		}catch (Exception $e) {
			$response[] = array("resultado" => "false", array($e));
			return $response;
		}
	}


    public function verificaPecaOs($os){
    	$pdo = $this->_model->getPDO();

        $sql = "SELECT  tbl_os_item.os_item, tbl_os_item.qtde,tbl_os_item.peca, tbl_peca.referencia
            FROM tbl_os_item
            INNER JOIN tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
	    INNER JOIN tbl_os on tbl_os_produto.os = tbl_os.os
	    INNER JOIN tbl_peca on tbl_os_item.peca = tbl_peca.peca AND tbl_peca.produto_acabado IS TRUE
	    WHERE tbl_os.os = $os
	    AND tbl_os_item.pedido IS NULL
            AND tbl_os.fabrica = {$this->_fabrica}";
        $query = $pdo->query($sql);
		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		if(count($res) == 0) {
			return false;
		} else {
			return $res;
		}
    }


    public function inserePecaProdutoAcabadoOsItem($os){
    	$pdo = $this->_model->getPDO();

        $sql = "SELECT  tbl_os_produto.os_produto, tbl_os.produto, tbl_produto.referencia, tbl_produto.descricao
            FROM tbl_os
            JOIN tbl_os_produto on tbl_os_produto.os = tbl_os.os 
            JOIN tbl_produto on tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$this->_fabrica}
            WHERE tbl_os.os = $os
            AND tbl_os.fabrica = {$this->_fabrica}";
        $query = $pdo->query($sql);
		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		if (count($res) > 0) {
			$referencia = $res[0]["referencia"];
			$descricao  = $res[0]["descricao"];
			$os_produto  = $res[0]["os_produto"];
			$peca = $this->getPecasProdutoAcabado($referencia);

			$servico_realizado = $this->getServicoRealizado('DVPC');
			if (empty($peca)) {

				$sql = "INSERT INTO tbl_peca (origem,fabrica,referencia, descricao, produto_acabado, ativo) VALUES ('IMP',$this->_fabrica,'$referencia', '$descricao','t','t') RETURNING peca";
		        $query = $pdo->query($sql);
		        if ($query) {
					$peca = $query->fetchAll(\PDO::FETCH_ASSOC);
					$peca = $peca[0]["peca"];
		        } else {
				    return ["erro" => true, "msg" => "Erro ao gravar produto acabado"];
		        }

		    } 

			$sql = "INSERT INTO tbl_os_item (os_produto,peca, qtde, servico_realizado) VALUES (".$os_produto.",".$peca.", 1,{$servico_realizado})";
        	$query = $pdo->query($sql);
        	if ($query) {
		        return ["erro" => false, "msg" => "Gravado com sucesso"];
        	} else {
		    	return ["erro" => true, "msg" => "Erro ao gravar os item"];
        	}

		        
		} else {
			return ["erro" => true, "msg" => "Produto não encontrado na OS"];
		}

    }


	public function finalizaAtendimentoCallcenter($hd_chamado, $comentario = null) {
        if (empty($hd_chamado)) {
            return false;
        }

        $pdo = $this->_model->getPDO();

        $sql = "INSERT INTO tbl_hd_chamado_item (
            hd_chamado,
            data,
            comentario,
            status_item
        ) VALUES (
            $hd_chamado,
            CURRENT_TIMESTAMP,
            '$comentario',
            'Resolvido'
        )";

        $query = $pdo->query($sql);

        if (empty($query)) {
            return false;
        }

        $sql = "UPDATE tbl_hd_chamado SET status = 'Resolvido', resolvido=now() WHERE hd_chamado = $hd_chamado";
        $query = $pdo->query($sql);

        if (empty($query)) {
            return false;
        }

        return true;
    }

    public function verificaPostoOsPendenteFechamento() {
    	$pdo = $this->_model->getPDO();

    	$sql = "SELECT DISTINCT 
			    	o.posto,
			    	pf.contato_email,
			    	p.nome
				FROM tbl_os AS o
				JOIN tbl_posto_fabrica AS pf ON (o.fabrica = pf.fabrica AND o.posto = pf.posto)
				JOIN tbl_posto p ON (p.posto = o.posto)
				JOIN tbl_faturamento_item ON (tbl_faturamento_item.os = o.os)
				WHERE o.fabrica = {$this->_fabrica}
				AND o.data_fechamento IS NULL
				AND pf.credenciamento <> 'DESCREDENCIADO'
				AND current_date - o.data_abertura > {$this->_dias_em_aberto}
				";

		$query = $pdo->query($sql);
		
		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		if(count($res) == 0) {
			throw new \Exception('Erro ao buscar postos pendentes');
		} else {
			return $res;
		}

    }

    public function getOsPendentePosto($posto) {
    	$pdo = $this->_model->getPDO();

    	$sql = "SELECT DISTINCT ON (o.os)
    				o.os,
    				o.consumidor_fone,
    				o.consumidor_nome,
    				o.consumidor_celular,
    				pr.referencia, 
				    CURRENT_DATE - data_abertura AS dias
				FROM tbl_os AS o
				JOIN tbl_os_produto op ON o.os = op.os
				JOIN tbl_produto pr ON op.produto = pr.produto 
				JOIN tbl_posto_fabrica AS pf ON (o.fabrica = pf.fabrica AND o.posto = pf.posto)
				JOIN tbl_faturamento_item ON (tbl_faturamento_item.os = o.os)
				WHERE o.fabrica = $this->_fabrica
				AND o.data_fechamento IS NULL
				AND o.posto = {$posto}
				AND current_date - data_abertura > $this->_dias_em_aberto

				ORDER BY o.os
		";
		$query = $pdo->query($sql);
		
		$res = $query->fetchAll(\PDO::FETCH_ASSOC);

		if(count($res) == 0) {
			throw new \Exception('Erro ao buscar postos pendentes');
		} else {
			return $res;
		}
    }

    public function setDiasEmAberto($dias_em_aberto) {
    	if (!empty($dias_em_aberto)) {
    		$this->_dias_em_aberto = $dias_em_aberto;
    	}
    }

    public function abreAtendimento($os,$fabrica){

    	if(empty($os)){

    		throw new \Exception("Informe a Ordem de Serviço para abrir o Atendimento");

    	}

    	$pdo = $this->_model->getPDO(); 

    	$sql_dados_os = "
        SELECT
            tbl_os.sua_os,
            tbl_os.posto,
            tbl_os.data_abertura,
            tbl_os.data_nf,
            tbl_os.consumidor_nome,
            tbl_os.consumidor_cpf,
            tbl_os.consumidor_endereco,
            tbl_os.consumidor_numero,
            tbl_os.consumidor_cep,
            tbl_os.consumidor_complemento,
            tbl_os.consumidor_bairro,
            tbl_os.consumidor_cidade,
            tbl_os.consumidor_estado,
			tbl_os.consumidor_fone,
			tbl_os.consumidor_email,
			tbl_os.consumidor_celular,
            tbl_os.revenda_cnpj,
            tbl_os.revenda_nome,
            tbl_os.revenda_fone,
            tbl_os.defeito_reclamado_descricao,
            tbl_os.revenda,
            tbl_os.consumidor_revenda,
            tbl_os.tipo_atendimento,
            tbl_os.nota_fiscal,
            tbl_os.data_nf,
            regexp_replace(tbl_os.obs,'\\s+',' ', 'g') as obs,
            tbl_posto.nome AS posto_nome,
            tbl_posto_fabrica.codigo_posto
            FROM tbl_os
            JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
            WHERE tbl_os.os  = {$os}";    		
    	$query = $pdo->prepare($sql_dados_os);

    	if (!$query->execute()) {
    		throw new \Exception("Ordem de Serviço {$os} não encontrada");
    	}else{

		        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

		        foreach ($res as $key) {
		        	$sua_os                      = $key['sua_os'];
			        $posto                       = $key['posto'];
			        $data_abertura               = $key['data_abertura'];
			        $data_nf                     = $key['data_nf'];
			        $consumidor_nome             = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['consumidor_nome']));
			        $consumidor_cpf              = $key['consumidor_cpf'];
			        $consumidor_endereco         = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['consumidor_endereco']));
			        $consumidor_endereco         = str_replace('\\','' ,$consumidor_endereco);
			        $consumidor_numero           = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['consumidor_numero']));
			        $consumidor_cep              = pg_fetch_result($res_dados_os, 0, 'consumidor_cep');
			        $consumidor_complemento      = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['consumidor_complemento']));
			        $consumidor_bairro           = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['consumidor_bairro']));
			        $consumidor_cidade           = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['consumidor_cidade']));
			        $consumidor_estado           = $key['consumidor_estado'];
			        $consumidor_fone             = $key['consumidor_fone'];
					$consumidor_email            = $key['consumidor_email'];
					$consumidor_celular 		 = $key['consumidor_celular'];
			        $revenda_cnpj                = $key['revenda_cnpj'];
			        $revenda_nome                = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['revenda_nome']));
			        $revenda_fone                = $key['revenda_fone'];
			        $defeito_reclamado_descricao = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['defeito_reclamado_descricao']));
			        $revenda                     = $key['revenda'];
			        $consumidor_revenda          = $key['consumidor_revenda'];
			        $nota_fiscal                 = $key['nota_fiscal'];
			        $data_nf                 	 = $key['data_nf'];
			        $tipo_atendimento            = $key['tipo_atendimento'];
			        $cod_ibge                    = $key['cod_ibge'];
			        $obs                         = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['obs']));
			        $posto_nome                  = preg_replace('/[^a-zA-Z0-9 ]/', '', tira_acentos($key['posto_nome']));
			        $codigo_posto                = $key['codigo_posto'];
		        }

		        if(!empty($consumidor_cidade)){
		            $sql_cidade = "SELECT cidade FROM tbl_cidade WHERE fn_retira_especiais(upper(nome)) = fn_retira_especiais(upper('$consumidor_cidade'));";
		            $query = $pdo->prepare($sql_cidade);

		            if($query->execute()){
			            $res_cidade = $query->fetchAll(\PDO::FETCH_ASSOC);

			            if(count($res_cidade) > 0){
			                $cod_ibge = $res_cidade[0]['cidade'];
			            }else{
			                $cod_ibge = "null";
			            }
			        }else{
			            $cod_ibge = "null";
			        }

		        }else{
		            $cod_ibge = "null";
		        }

				$sql_os = "SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE os = $os";
				$query = $pdo->prepare($sql_os);

				if($query->execute()){

					$res_os = $query->fetchAll(\PDO::FETCH_ASSOC);

					if(count($res_os) == 0) {

						$sql_abre_chamado = "
							INSERT INTO tbl_hd_chamado
							(
								posto,
								titulo,
								status,
								atendente,
								categoria,
								admin,
								fabrica_responsavel,
								fabrica
							)
							VALUES
							(
								$posto,
								'Atendimento interativo',
								'Aberto',
								7759,
								'reclamacao_produto',
								7759,
								{$fabrica},
								{$fabrica}
							) RETURNING hd_chamado
							";
						$query = $pdo->prepare($sql_abre_chamado);

						if(!$query->execute()){
							throw new \Exception("Falha ao abrir atendimento #1");
						}

						$res_abre_chamado = $query->fetchAll(\PDO::FETCH_ASSOC);

						$hd_chamado = $res_abre_chamado[0]['hd_chamado'];

						if(!empty($hd_chamado)){

							$sql = "UPDATE tbl_os SET hd_chamado = $hd_chamado WHERE os = $os";
							$query = $pdo->prepare($sql);

							if(!$query->execute()){
								throw new \Exception("Falha ao abrir atendimento #2");
							}

							$sql_extra = "
								INSERT INTO tbl_hd_chamado_extra
								(
									hd_chamado,
									revenda_nome,
									posto,
									os,
									serie,
									data_nf,
									nota_fiscal,
									defeito_reclamado_descricao,
									nome,
									endereco,
									numero,
									complemento,
									bairro,
									cep,
									fone,
									email,
									celular,
									cpf,
									cidade,
									revenda_cnpj
								)
								VALUES
								(
									$hd_chamado,
									'$revenda_nome',
									$posto,
									$os,
									'$serie',
									'$data_nf',
									'$nota_fiscal',
									'$defeito_reclamado_descricao',
									'$consumidor_nome',
									'$consumidor_endereco',
									'$consumidor_numero',
									'$consumidor_complemento',
									'$consumidor_bairro',
									'$consumidor_cep',
									'$consumidor_fone',
									'$consumidor_email',
									'$consumidor_celular',
									'$consumidor_cpf',
									$cod_ibge,
									'$revenda_cnpj'
								)
								";
							$query = $pdo->prepare($sql_extra);

							if(!$query->execute()){
								throw new \Exception("Falha ao abrir atendimento #3");
							}else{
								$sqlOsProduto = "SELECT produto,serie FROM tbl_os_produto WHERE os = {$os}";
								$query = $pdo->prepare($sqlOsProduto);

								if(!$query->execute()){
									throw new \Exception("Falha ao abrir atendimento #4");
								}

								$resOsProduto = $query->fetchAll(\PDO::FETCH_ASSOC);

								if(count($resOsProduto) > 0){

									foreach ($resOsProduto as $key) {
										
										$produto = $key['produto'];
										$serie   = $key['serie'];

										$sql_hd_item = "INSERT INTO tbl_hd_chamado_item(
								                            hd_chamado          ,
								                            data                ,
								                            interno             ,
								                            status_item         ,
								                            produto             ,
								                            serie               ,
								                            nota_fiscal         ,
								                            data_nf            
								                        ) values (
								                            $hd_chamado         ,
								                            current_timestamp   ,
								                            't'  				,
								                            'Aberto'            ,
								                            {$produto}          ,
								                            '{$serie}'          ,
								                            '{$nota_fiscal}'    ,
								                            '{$data_nf}'
								                        )";
								        $query = $pdo->prepare($sql_hd_item);

								        if(!$query->execute()){
											throw new \Exception("Falha ao abrir atendimento #5");
										}
									}

								}

							}

						}else{
							throw new \Exception("Falha ao abrir atendimento #6");
						}
					}

				}
		    
    	}

    }


    public function getOsPendenteConserto() {

        $pdo = $this->_model->getPDO();

        $sql = "
            SELECT DISTINCT tbl_os.os, tbl_os.posto
            FROM   tbl_os
            JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            AND tbl_os.fabrica = {$this->_fabrica}
            JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
            AND    tbl_posto_fabrica.fabrica = {$this->_fabrica}
            AND    tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
            JOIN tbl_faturamento_item ON tbl_faturamento_item.os = tbl_os.os
            JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
            AND  tbl_faturamento.fabrica = 10
            WHERE  tbl_os.status_checkpoint = 3
            AND tbl_os.fabrica = {$this->_fabrica}
            AND date_part('day', age(current_timestamp,tbl_faturamento.emissao)) >= 7
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_os.cancelada IS NOT TRUE
        ";
        $query = $pdo->query($sql);

        if(!$query->execute()){
			throw new \Exception("Falha ao buscar OSs pendentes");
		}

        $res   = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res;

    }

    public function getOsPendenteRetirada() {

        $pdo = $this->_model->getPDO();

        $sql = "
            SELECT DISTINCT tbl_os.os, tbl_os.posto
            FROM   tbl_os
            JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
            AND    tbl_posto_fabrica.fabrica = {$this->_fabrica}
            AND    tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
            WHERE  tbl_os.status_checkpoint = 4
            AND tbl_os.fabrica = {$this->_fabrica}
            AND date_part('day', age(current_timestamp,(
            	SELECT hc.data_input
            	FROM tbl_os_historico_checkpoint hc
            	WHERE hc.os = tbl_os.os
            	AND  hc.status_checkpoint = 4
            	ORDER BY hc.data_input DESC
            	LIMIT 1
            ))) >= 5
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_os.cancelada IS NOT TRUE
        ";
        $query = $pdo->query($sql);

        if(!$query->execute()){
			throw new \Exception("Falha ao buscar OSs pendentes de retirada");
		}

        $res   = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res;

    }

    public function verificaComunicadoEnviado($os, $posto, $status_anterior) {

    	$pdo = $this->_model->getPDO();

    	$sql = "SELECT tbl_comunicado.comunicado, 
    				   tbl_comunicado.data, 
    				   tbl_os.status_checkpoint,
    				   (
		    				SELECT cb.comunicado
		    				FROM tbl_comunicado_posto_blackedecker cb
		    				WHERE cb.comunicado = tbl_comunicado.comunicado
		    				LIMIT 1
		    			) AS comunicado_lido
    			FROM tbl_comunicado
    			JOIN tbl_os ON tbl_os.os = {$os}
    			AND tbl_os.fabrica = {$this->_fabrica}
    			WHERE tbl_comunicado.posto = {$posto}
    			AND tbl_comunicado.parametros_adicionais->>'os' = '{$os}'
    			ORDER BY tbl_comunicado.comunicado DESC
    			LIMIT 1";
    	$query = $pdo->query($sql);

    	if(!$query) {
			throw new \Exception("Falha ao buscar comunicados");
		}

		$res = $query->fetch(\PDO::FETCH_ASSOC);

		if ($res) {

			$status_checkpoint  = $res['status_checkpoint'];
			$data 				= date_create($res['data']);
			$data_atual 		= date_create(date("Y-m-d H:i:s"));
			$comunicado_lido    = $res['comunicado_lido'];

			$comparacaoDatas = date_diff($data, $data_atual);

			// caso tenha 5 dias desde o envio do último comunicado
			// e o status não tenha se alterado

			if ($comparacaoDatas->d >= 5 && $status_checkpoint == $status_anterior && !empty($comunicado_lido)) {
				return true;
			}

			return false;

		}

		return true;

    }

    public function enviaComunicadoOs(
    	$msg,
    	$tipo,
    	$posto = null, 
    	$obrigatorio_site = 'f', 
    	$parametros_adicionais = null
    ) {

        $pdo = $this->_model->getPDO();

        $sql = "
            INSERT INTO tbl_comunicado (fabrica, posto, ativo, obrigatorio_site, mensagem, parametros_adicionais, tipo) 
            VALUES ({$this->_fabrica}, {$posto}, 't', '{$obrigatorio_site}', '{$msg}', '{$parametros_adicionais}', '$tipo');
        ";
        $query = $pdo->query($sql);

        if(empty($query)){
			throw new \Exception("Falha ao enviar comunicado");
		}

    }

    public function pagaMaoDeObra($os){

	$pdo = $this->_model->getPDO();
    	$sql = "UPDATE tbl_os_extra SET admin_paga_mao_de_obra = TRUE WHERE os = $os";
        $query = $pdo->query($sql);

    }

    public function tira_acentos ($texto) {
        $acentos = array(
            "com" => "áâàãäéêèëíîìïóôòõúùüçñÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇÑ",
            "sem" => "aaaaaeeeeiiiioooouuucnAAAAAEEEEIIIIOOOOUUUCn"
        );
        return strtr($texto,$acentos['com'], $acentos['sem']);
    }

    public function enviaPesquisaTrackSale($os){

		system("php /var/www/assist/www/rotinas/midea/enviar-pesquisas-tracksale.php $os");
    }

    public function validaInformacoesOs($os, $sua_os, $data_fechamento, $tipo_revenda = 'f')
    {
        $pdo = $this->_model->getPDO();
        
        if (!empty($os)) {
            $sql = "SELECT data_conserto FROM tbl_os WHERE os = {$os} AND fabrica = {$this->_fabrica};";
            $query = $pdo->query($sql);
            $data_conserto_array = $query->fetch(\PDO::FETCH_ASSOC);

            $data_conserto_bd = $data_conserto_array['data_conserto'];

            if ($data_fechamento > date("Y-m-d")) {
                if ($os_produto === false) {
                    return traduz("Data de fechamento da OS {$sua_os} não pode ser maior que a atuallllll");
                } else {
                    throw new \Exception(traduz("Data de fechamento da OS {$sua_os} não pode ser maior que a atual"));
                }
            }
            if (!empty($data_conserto_bd) && $tipo_revenda == 'f') {
                if (strtotime($data_fechamento.'23:59:59') < strtotime($data_conserto_bd)) {
                    if ($os_produto === false) {
                        return traduz("Data de fechamento da OS {$sua_os} não pode ser anterior à data de conserto");
                    } else {
                        throw new \Exception(traduz("Data de fechamento da OS {$sua_os} não pode ser anterior à data de conserto"));
                    }
                }
            }
        }

        return false;
    }

    public function getOsExclusaoPeriodo($dias) {

    	$pdo = $this->_model->getPDO();

    	$sql = "SELECT tbl_os.os
			    FROM tbl_os
			    WHERE tbl_os.fabrica = {$this->_fabrica}
			    AND tbl_os.data_fechamento IS NULL
			    AND tbl_os.finalizada IS NULL
			    AND tbl_os.data_digitacao::date < current_date - INTERVAL '{$dias} days'
			    AND tbl_os.excluida IS NOT TRUE
			    AND (
			    	SELECT tbl_os_item.os_item
			    	FROM tbl_os_produto
			    	JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			    	AND tbl_os_item.fabrica_i = {$this->_fabrica}
			    	WHERE tbl_os_produto.os = tbl_os.os
			    	LIMIT 1
			    ) IS NULL
			    AND tbl_os.data_digitacao > '2017-01-01'
			    LIMIT 1000
			    ";
        $res = $pdo->query($sql);

        $dados = $res->fetchAll(\PDO::FETCH_ASSOC);

        return $dados;

    }

    public function insereOsExcluida() {

    	$pdo = $this->_model->getPDO();

    	$sql = "INSERT INTO tbl_os_excluida (
                      fabrica           ,
                      os                ,
                      sua_os            ,
                      posto             ,
                      codigo_posto      ,
                      produto           ,
                      referencia_produto,
                      data_digitacao    ,
                      data_abertura     ,
                      data_fechamento   ,
                      serie             ,
                      nota_fiscal       ,
                      data_nf           ,
                      consumidor_nome   ,
                      consumidor_endereco,
                      consumidor_numero,
                      consumidor_bairro,
                      consumidor_cidade,
                      consumidor_estado,
                      consumidor_fone,
                      defeito_reclamado,
                      defeito_reclamado_descricao,
                      defeito_constatado,
                      revenda_cnpj,
                      revenda_nome 
                )
                SELECT tbl_os.fabrica                ,
                       tbl_os.os                     ,
                       tbl_os.sua_os                 ,
                       tbl_os.posto                  ,
                       tbl_posto_fabrica.codigo_posto,
                       tbl_os.produto                ,
                       tbl_produto.referencia        ,
                       tbl_os.data_digitacao         ,
                       tbl_os.data_abertura          ,
                       tbl_os.data_fechamento        ,
                       tbl_os.serie                  ,
                       tbl_os.nota_fiscal            ,
                       tbl_os.data_nf                ,
                       consumidor_nome               ,
                       consumidor_endereco           ,
                       consumidor_numero             ,
                       consumidor_bairro             ,
                       consumidor_cidade             ,
                       consumidor_estado             ,
                       consumidor_fone               ,
                       defeito_reclamado             ,
                       defeito_reclamado_descricao   ,
                       defeito_constatado            ,
                       revenda_cnpj                  ,
                       revenda_nome
                FROM    tbl_os
                JOIN    tbl_posto_fabrica  ON tbl_posto_fabrica.posto = tbl_os.posto
                AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
                JOIN    tbl_produto        ON tbl_produto.produto     = tbl_os.produto
                WHERE   tbl_os.os          = {$this->_os}
                AND     tbl_os.fabrica     = {$this->_fabrica}";
        $pdo->query($sql);

    }

    public function excluiOs() {

    	$pdo = $this->_model->getPDO();

    	$sql = "UPDATE tbl_os SET excluida = 't' WHERE os = {$this->_os} AND fabrica = {$this->_fabrica}";
        $pdo->query($sql);

    }

}

