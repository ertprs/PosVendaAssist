<?php
namespace Posvenda;

use Posvenda\Model\Os as OsModel;
use Posvenda\ExcecaoMobra;
use Posvenda\Regras;

class MaoDeObra
{
    /**
     * @var string
     */
    private $_os;

    /**
     * @var integer
     */
    private $_fabrica;

    /**
     * @var \Posvenda\Model\Os
     */
    private $_os_model;

    /**
     * @var float
     */
    private $_mao_de_obra;


    public function __construct($os, $fabrica, $conn = null)
    {
        $this->_os               = $os;
        $this->_fabrica          = $fabrica;
        $this->_os_model         = new OsModel($this->_fabrica, $this->_os, $conn);
        $this->_mao_de_obra      = 0;
    }

    public function setOs($os) {
        $this->_os = $os;
    }

    /**
     * Pega a mão-de-obra da OS
     *
     * @return float
     */
    public function getMaoDeObra()
    {
        if (empty($this->_mao_de_obra)) {
            $this->_os_model->select()
                 ->setCampos(array('mao_de_obra'))
                 ->addWhere(array('os' => $this->_os));

            if (!$this->_os_model->prepare()->execute()) {
                throw new \Exception("Erro ao selecionar a mão de obra da OS : {$this->_os}");
            }

            $res = $this->_os_model->getPDOStatement()->fetch();

            if (!empty($res["mao_de_obra"])) {
                $this->_mao_de_obra = $res["mao_de_obra"];
            }
        }

        return $this->_mao_de_obra;
    }


    public function verificaLinhaProduto($os){

        $this->_os_model->select("tbl_os_produto")
             ->setCampos(array("tbl_produto.linha"))
             ->addJoin(array("tbl_produto" => "ON tbl_os_produto.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$this->_fabrica}"))
             ->addWhere("tbl_os_produto.os = {$os}");

        if (!$this->_os_model->prepare()->execute()) {
            throw new \Exception("Erro ao verificar linha do produto da OS : {$os} /* SQL 2 */");
        }

        $res = $this->_os_model->getPDOStatement()->fetch();

        return $res["linha"];
    }

    public function verificaOsSemTrocaPeca($os) {

        $this->_os_model->select("tbl_os_item")
        ->setCampos(array("tbl_os_item.os_item"))
        ->addJoin(array("tbl_os_produto" => "ON tbl_os_produto.os_produto = tbl_os_item.os_produto"))
	->addJoin(array("tbl_servico_realizado" => "ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}"))
	->addJoin(array("tbl_peca" => "ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.acessorio IS NOT TRUE"))
	->addJoin(array("tbl_pedido" => "ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_pedido.status_pedido <> 14"))
        ->addWhere("tbl_os_produto.os = {$os}")
        ->addWhere("tbl_servico_realizado.gera_pedido IS TRUE");       
        
        if (!$this->_os_model->prepare()->execute()) {

            throw new \Exception("Erro ao verificar itens da OS : {$os} /* SQL 2 */");
        }

        $res = $this->_os_model->getPDOStatement()->fetch();

        if($res == False) {

            return true;

        }else{

            return false;
        }
    }

    /**
     * Calcula a mão-de-obra da OS Por serviço realizado
     * Desenvolvido para Qbex
     * @return MaoDeObra
     */
    public function calculaMaoDeObraServicoRealizado(){

        try {
            $this->_os_model->zeraMaoDeObra();

            $this->_os_model->select("tbl_os")
             ->setCampos(array("MAX(tbl_mao_obra_servico_realizado.mao_de_obra) as mao_de_obra"))
             ->addJoin(array(
                            'tbl_os_produto' => 'on tbl_os_produto.os = tbl_os.os',
                            'tbl_os_item' => 'on tbl_os_item.os_produto = tbl_os_produto.os_produto',
                            'tbl_mao_obra_servico_realizado'=> 'on tbl_mao_obra_servico_realizado.servico_realizado = tbl_os_item.servico_realizado'))
             ->addWhere(array('tbl_os.os' => $this->_os, 'tbl_os.fabrica' => $this->_fabrica));

            if (!$this->_os_model->prepare()->execute()) {
                throw new \Exception("Erro ao pegar mão de obra do produto da OS") ;
            }

            $res = $this->_os_model->getPDOStatement()->fetch();
            $this->_mao_de_obra = $res["mao_de_obra"];

            $this->_os_model->updateMaoDeObra($this->_mao_de_obra);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $this;

    }

    /**
     * Calcula a mão-de-obra da OS Por defeito constatado
     * Desenvolvido para Qbex
     * @return MaoDeObra
     */
    public function calculaMaoDeObraDefeitoConstatado(){

        try {
            $this->_os_model->zeraMaoDeObra();

            $this->_os_model->select("tbl_os")
             ->setCampos(array("tbl_defeito_constatado.mao_de_obra"))
             ->addJoin(array(
                            'tbl_os_produto' => 'on tbl_os_produto.os = tbl_os.os',
                            'tbl_defeito_constatado' => 'on tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado'))
             ->addWhere(array('tbl_os.os' => $this->_os, 'tbl_os.fabrica' => $this->_fabrica));

            if (!$this->_os_model->prepare()->execute()) {
                throw new \Exception("Erro ao pegar mão de obra do defeito constatado da OS") ;
            }

            $res = $this->_os_model->getPDOStatement()->fetch();
            $this->_mao_de_obra = $res["mao_de_obra"];

            $this->_os_model->updateMaoDeObra($this->_mao_de_obra);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $this;

    }

    /**
     * * Calcula a mão-de-obra OS de Troca
     * * Desenvolvido para Qbex
     *
     * * @return MaoDeObra
     */
    public function calculaMaoDeObraTroca(){

	    try {
			//$this->_os_model->zeraMaoDeObra();

			$this->_os_model->select("tbl_os")
				->setCampos(array("MAX(tbl_mao_obra_servico_realizado.mao_de_obra) as mao_de_obra"))
				->addJoin(array(
					'tbl_os_produto' => 'on tbl_os_produto.os = tbl_os.os',
					'tbl_os_item' => 'on tbl_os_item.os_produto = tbl_os_produto.os_produto',
					'tbl_mao_obra_servico_realizado'=> 'on tbl_mao_obra_servico_realizado.servico_realizado = tbl_os_item.servico_realizado',
					'tbl_servico_realizado' => 'on tbl_servico_realizado.servico_realizado = tbl_mao_obra_servico_realizado.servico_realizado and tbl_servico_realizado.troca_produto IS TRUE'))
					->addWhere(array('tbl_os.os' => $this->_os, 'tbl_os.fabrica' => $this->_fabrica));
			#echo $this->_os_model->prepare()->getQuery(); exit;
			if (!$this->_os_model->prepare()->execute()) {
				throw new \Exception("Erro ao pegar mão de obra de Troca") ;
			}

			$res = $this->_os_model->getPDOStatement()->fetch();
			$this->_mao_de_obra = $res["mao_de_obra"];

			if(!empty($res["mao_de_obra"])) {
				$this->_os_model->updateMaoDeObra($this->_mao_de_obra);
			}

		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}

		return $this;

    }



    /**
     * Calcula a mão-de-obra da OS
     *
     * @return MaoDeObra
     */
    public function calculaMaoDeObra()
    {
        try {

            $this->_os_model->zeraMaoDeObra();

            if(in_array($this->_fabrica,array(148))){

                $pdo = $this->_os_model->getPDO();

                $sqlMoRevenda = "SELECT DISTINCT tbl_os.os,
                                                 tbl_produto.linha,
                                                 tbl_tipo_posto.tipo_posto,
                                                 tbl_posto_linha.categoria_posto
                                 FROM tbl_os
                                 JOIN tbl_posto_tipo_posto ON tbl_posto_tipo_posto.posto = tbl_os.posto
                                 AND tbl_posto_tipo_posto.fabrica = {$this->_fabrica}
                                 JOIN tbl_tipo_posto ON tbl_posto_tipo_posto.tipo_posto = tbl_tipo_posto.tipo_posto
                                 AND tbl_tipo_posto.fabrica = {$this->_fabrica}
                                 AND tbl_tipo_posto.tipo_revenda IS TRUE
								AND tbl_os.tipo_atendimento <>217
                                 JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                                 AND fabrica_i = {$this->_fabrica}
                                 JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha
                                 AND tbl_posto_linha.posto = tbl_os.posto
                                 AND tbl_posto_linha.ativo IS TRUE
                                 WHERE tbl_os.os = {$this->_os}
                                 AND tbl_produto.linha IN (875,876)
                                 AND tbl_os.data_abertura > '2019-01-01'
                           ";
                $resMoRevenda = $pdo->query($sqlMoRevenda);

                $dadosMoRevenda = $resMoRevenda->fetchAll();

                if (count($dadosMoRevenda) > 0) {

                    $tipoPostoDiagnostico = $dadosMoRevenda[0]["tipo_posto"];
                    $linhaDiagnostico = $dadosMoRevenda[0]["linha"];
                    $categoriaPostoDiagnostico = $dadosMoRevenda[0]["categoria_posto"];

                    if (!empty($categoriaPostoDiagnostico)) {
                        $condCat = "AND categoria_posto = {$categoriaPostoDiagnostico}";
                    }

                    $sqlBuscaDiagnostico = "SELECT mao_de_obra
                                            FROM tbl_diagnostico
                                            WHERE fabrica = {$this->_fabrica}
                                            AND linha = {$linhaDiagnostico}
                                            AND tipo_posto = {$tipoPostoDiagnostico}
                                            {$condCat}
                                            AND ativo IS TRUE";
                    $resBuscaDiagnostico = $pdo->query($sqlBuscaDiagnostico);

                    $dadosMoDiag = $resBuscaDiagnostico->fetchAll();

                    if (count($dadosMoDiag) > 0) {

                        $valorMO = $dadosMoDiag[0]["mao_de_obra"];
                        $valorMinuto = $valorMO/60;
                        $valoresMOCalcular = 0;
                        $temSolucao = false;

                        $sqlDigProd = "SELECT tbl_produto.familia,
                                               tbl_produto.produto,
                                               tbl_os_defeito_reclamado_constatado.solucao
                                        FROM tbl_os
                                        JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND fabrica_i = {$this->_fabrica}
                                        LEFT JOIN tbl_os_defeito_reclamado_constatado ON tbl_os.os = tbl_os_defeito_reclamado_constatado.os 
                                                 AND tbl_os_defeito_reclamado_constatado.fabrica = {$this->_fabrica}
                                        WHERE tbl_os.os = {$this->_os}
                                        AND tbl_os.fabrica = {$this->_fabrica}";
                        $resDigProd = $pdo->query($sqlDigProd);

                        $dadosDigProd = $resDigProd->fetchAll();

                        if (count($dadosDigProd) > 0) {

                            foreach ($dadosDigProd as $inf) {
                                
                                $familaProduto  = $inf["familia"];
                                $produtoOS      = $inf["produto"];
                                $solucaoOS      = $inf["solucao"];
        
                                if (!empty($familaProduto) && !empty($solucaoOS)) {

                                    $temSolucao = true;

                                    $tempoEstimado = 0;

                                    if (!empty($produtoOS)) {
                                        $sqlBuscaDiagnosticoTempo = "   SELECT d.tempo_estimado
                                                                        FROM tbl_diagnostico d
                                                                        JOIN tbl_diagnostico_produto dp ON d.diagnostico = dp.diagnostico
                                                                        WHERE d.fabrica = {$this->_fabrica}
                                                                        AND d.familia = $familaProduto
                                                                        AND d.solucao = $solucaoOS
                                                                        AND dp.produto = $produtoOS
                                                                        AND d.ativo";
                                        $resBuscaDiagnosticoTempo = $pdo->query($sqlBuscaDiagnosticoTempo);

                                        $dadosMoDiagTempo = $resBuscaDiagnosticoTempo->fetchAll();

                                        if (count($dadosMoDiagTempo) > 0) {
                                            $tempoEstimado = $dadosMoDiagTempo[0]["tempo_estimado"];

                                            $valoresMOCalcular += $tempoEstimado * $valorMinuto;

                                            continue;
                                        }
                                    }

                                    $sqlBuscaDiagnosticoTempo = "   SELECT d.tempo_estimado
                                                                    FROM tbl_diagnostico d
                                                                    LEFT JOIN tbl_diagnostico_produto dp ON d.diagnostico = dp.diagnostico
                                                                    WHERE d.fabrica = {$this->_fabrica}
                                                                    AND d.familia = $familaProduto
                                                                    AND d.solucao = $solucaoOS
                                                                    AND dp.produto IS NULL 
                                                                    AND d.ativo";
                                    $resBuscaDiagnosticoTempo = $pdo->query($sqlBuscaDiagnosticoTempo);

                                    $dadosMoDiagTempo = $resBuscaDiagnosticoTempo->fetchAll();

                                    if (count($dadosMoDiagTempo) > 0) {
                                        $tempoEstimado = $dadosMoDiagTempo[0]["tempo_estimado"];

                                        $valoresMOCalcular += $tempoEstimado * $valorMinuto;
                                        
                                    } 
                                }   
                            }
                        }

                        if (!$temSolucao) {
                            $sqlSol = "SELECT tbl_os_defeito_reclamado_constatado.solucao
                                        FROM tbl_os
                                        LEFT JOIN tbl_os_defeito_reclamado_constatado ON tbl_os.os = tbl_os_defeito_reclamado_constatado.os 
                                                 AND tbl_os_defeito_reclamado_constatado.fabrica = {$this->_fabrica}
                                        WHERE tbl_os.os = {$this->_os}
                                        AND tbl_os.fabrica = {$this->_fabrica}
                                        AND tbl_os_defeito_reclamado_constatado.solucao NOTNULL";
                            $resSol = $pdo->query($sqlSol);

                            $dadosSol = $resSol->fetchAll();

                            if (count($dadosSol) > 0) {
                                $temSolucao = true;
                            }
                        }

                        if ($temSolucao) {
                            $valorMO = ($valoresMOCalcular > 0) ? $valoresMOCalcular : $valorMO; 
                        } else {
                            $valorMO = 0;
                        }

                        $this->_mao_de_obra = $valorMO;
                        $this->_os_model->updateMaoDeObra($this->_mao_de_obra);

                        return $this;

                    } else {
                        throw new \Exception("Não encontramos mão de obra cadastrada para posto revenda. Favor entrar em contato com o fabricante.");
                    }

                } else {

                    $this->_os_model->select("tbl_os")
                        ->setCampos(array("tbl_diagnostico.mao_de_obra","tbl_diagnostico.solucao"))
                        ->addJoin(
                            array(
                                'tbl_os_defeito_reclamado_constatado' => 'ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os',
                                'tbl_diagnostico' => 'ON tbl_os_defeito_reclamado_constatado.solucao = tbl_diagnostico.solucao and tbl_diagnostico.ativo',
                                'tbl_diagnostico_produto' => 'ON tbl_diagnostico.diagnostico = tbl_diagnostico_produto.diagnostico AND tbl_diagnostico_produto.produto = tbl_os.produto'
                            )
                        )->addWhere(
                            array(
                                'tbl_os.os' => $this->_os,
                                'tbl_os.fabrica' => $this->_fabrica,
                                'tbl_diagnostico_produto.fabrica' => $this->_fabrica,
                                'tbl_diagnostico.fabrica' => $this->_fabrica
                                )
                    );

                    if (!$this->_os_model->prepare()->execute()) {
                        throw new \Exception("Erro ao selecionar a mão de obra do produto da OS : {$this->_os}");
                    }

                    $result = $this->_os_model->getPDOStatement()->fetchAll();
    				$soma = 0;
    				foreach($result as $res) {
    					foreach ($res as $key => $value) {
    						if ($key === 'mao_de_obra') {
    							$soma += $value;
    						}elseif($key === 'solucao'){
    							if (isset($solucao)) {
    								$solucao .= ",{$value}";
    							}else{
    								$solucao = $value;
    							}
    						}
    					}
    				}

                    $where_solucao = '';
                    if (isset($solucao) && $solucao != 0) {
                        $where_solucao = "AND tbl_diagnostico.solucao NOT IN({$solucao})";
                    }

                        $this->_os_model->select("tbl_os_produto")
                         ->setCampos(array("SUM(tbl_diagnostico.mao_de_obra) AS mao_de_obra"))
                         ->addJoin(array(
                                        'tbl_produto' => 'ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = '.$this->_fabrica,
                                        'tbl_os_defeito_reclamado_constatado' => 'ON tbl_os_defeito_reclamado_constatado.os = tbl_os_produto.os',
                                        'tbl_diagnostico'=> 'ON tbl_diagnostico.solucao = tbl_os_defeito_reclamado_constatado.solucao '.$where_solucao.'
                                        AND tbl_diagnostico.familia = tbl_produto.familia and tbl_diagnostico.ativo AND tbl_diagnostico.diagnostico not in (select diagnostico from tbl_diagnostico_produto where fabrica ='.$this->_fabrica.')'))
                         ->addWhere(array('tbl_os_produto.os' => $this->_os))
                         ->setLimit(1);
                        if (!$this->_os_model->prepare()->execute()) {
                            throw new \Exception("Erro ao selecionar a mão de obra do produto da OS : {$this->_os}");
                        }

                        $res2 = $this->_os_model->getPDOStatement()->fetch();
                        unset($res);
                        $res['mao_de_obra'] = $soma += $res2['mao_de_obra'];

                }

            }else if(in_array($this->_fabrica,array(138))){
                $this->_os_model->select("tbl_os_produto")
                     ->setCampos(array("MAX(tbl_diagnostico.mao_de_obra) AS mao_de_obra"))
                     ->addJoin(array(
                                    'tbl_produto' => 'ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = '.$this->_fabrica,
                                    'tbl_os_defeito_reclamado_constatado' => 'ON tbl_os_defeito_reclamado_constatado.os = tbl_os_produto.os',
                                    'tbl_diagnostico'=> 'ON tbl_diagnostico.solucao = tbl_os_defeito_reclamado_constatado.solucao
                                    AND tbl_diagnostico.familia = tbl_produto.familia'))
                     ->addWhere(array('tbl_os_produto.os' => $this->_os))
                     ->setLimit(1);

            }else if(in_array($this->_fabrica,array(152,180,181,182))){

                /*
                *-Ordem de Serviço de Entrega Técnica
                *por equipamento: valor da mão de obra será: qtde de produtos * valor por equipamento da entrega técnica
                *por entrega técnica: valor da mão de obra será: valor por entrega técnica da entrega técnica
                *por hora: valor informado no campo hora técnica * valor por hora da entrega técnica
                */

                $this->_os_model->select("tbl_os_produto")
                     ->setCampos(array("tbl_produto.code_convention,tbl_produto.entrega_tecnica,tbl_produto.linha,tbl_os_produto.capacidade"))
                     ->addJoin(array( 'tbl_produto' => 'ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = '.$this->_fabrica ))
                     ->addWhere(array('tbl_os_produto.os' => $this->_os));

                if (!$this->_os_model->prepare()->execute()) {
                    throw new \Exception("Erro ao pegar mão de obra do produto da OS #1") ;
                }

                $result_prod =$this->_os_model->getPDOStatement()->fetchAll();

                $this->_os_model->select("tbl_fabrica")
                        ->setCampos(array("tbl_fabrica.parametros_adicionais"))
                        ->addWhere(array('tbl_fabrica.fabrica' => $this->_fabrica));

                if (!$this->_os_model->prepare()->execute()) {
                    throw new \Exception("Erro ao pegar mão de obra do produto da OS #2") ;
                }

                $parametros_adicionais =$this->_os_model->getPDOStatement()->fetch();
                $parametros_adicionais = json_decode($parametros_adicionais["parametros_adicionais"],true);
                $entrega_tecnica = array();
                $assistencia_tecnica = array();
                $entrega_tecnica = $parametros_adicionais["valores_mao_de_obra"]["entrega_tecnica"];
                $assistencia_tecnica= $parametros_adicionais["valores_mao_de_obra"]["assistencia_tecnica"];

                // $this->_model->select("tbl_os_defeito_reclamado_constatado")->setCampos("SUM(tempo_reparo) AS tempo_reparo")->addWhere("os = {$os}");
                $valor_mao_obra= 0 ;

                foreach ($result_prod as $key => $produto) {


                    $this->_os_model->select("tbl_tipo_atendimento")
                    ->setCampos(array("tbl_tipo_atendimento.entrega_tecnica"))
                    ->addJoin(array( 'tbl_os' => 'ON tbl_tipo_atendimento.tipo_atendimento =  tbl_os.tipo_atendimento '))
                    ->addWhere("tbl_os.os = ". $this->_os);


                    if (!$this->_os_model->prepare()->execute()) {
                        throw new \Exception("Erro ao calcular KM da OS #3") ;
                    }

                    $tipo_atendimento =$this->_os_model->getPDOStatement()->fetch();

                    if($tipo_atendimento["entrega_tecnica"]=="t") {

                        if( $produto["code_convention"] == "hora" ) {

                            $this->_os_model->select("tbl_os")->setCampos(array("hora_tecnica"))->addWhere("os = {$this->_os}");

                            if (!$this->_os_model->prepare()->execute()) {
                                throw new \Exception("Erro ao pegar mão de obra do produto da OS #3") ;
                            }

                            $hora_tecnica =$this->_os_model->getPDOStatement()->fetch();

                            if(empty($hora_tecnica["hora_tecnica"]) || strlen($hora_tecnica["hora_tecnica"])==0 ){
                                throw new \Exception("É necessario preencher hora técnica") ;
                            }

                            $valor_mao_obra = ($hora_tecnica["hora_tecnica"] / 60) * $entrega_tecnica["hora"];
                            break;

                        }elseif( $produto["code_convention"] == "equip" ) {


                            $this->_os_model->select("tbl_os_produto")->setCampos(array("SUM(capacidade) as total_pecas"))->addWhere("os = {$this->_os}");

                            if (!$this->_os_model->prepare()->execute()) {
                                throw new \Exception("Erro ao pegar mão de obra do produto da OS #4") ;
                            }

                            $total_pecas = $this->_os_model->getPDOStatement()->fetch();
                            $total_pecas = $total_pecas["total_pecas"];
                            $valor_mao_obra = $total_pecas * $entrega_tecnica["equipamento"];

                            break;

						            }elseif( $produto["code_convention"] == "entrega" || $produto["code_convention"] == "os" ) {
                            $this->_os_model->select("tbl_os_produto")->setCampos(array("SUM(capacidade) as total_pecas"))->addWhere("os = {$this->_os}");

                            if (!$this->_os_model->prepare()->execute()) {
                                throw new \Exception("Erro ao pegar mão de obra do produto da OS #4") ;
                            }

                            $total_pecas = $this->_os_model->getPDOStatement()->fetch();
                            $total_pecas = $total_pecas["total_pecas"];
                            $valor_mao_obra = $total_pecas * $entrega_tecnica["entrega"];

                            break;

                        }

                    }else{

                        foreach ($assistencia_tecnica as $key => $value) {


                            $this->_os_model->select("tbl_os_defeito_reclamado_constatado")
                                          ->setCampos(array("SUM(tempo_reparo) AS defeito_total"))
                                         ->addWhere(array('tbl_os_defeito_reclamado_constatado.os' => $this->_os));

                            if (!$this->_os_model->prepare()->execute()) {
                                throw new \Exception("Erro ao pegar mão de obra do produto da OS #5") ;
                            }

                            $tempo =$this->_os_model->getPDOStatement()->fetch();

                            if(empty($tempo["defeito_total"]) || strlen($tempo["defeito_total"])==0) {
                                throw new \Exception("Necessario preencher tempo de reparo da OS: {$this->_os}") ;
                            }

                            if ($value["linha"] == $produto["linha"] ) {
                                /*
                                * apartir == paga km
                                * deslocamento == nao paga
                                */

                                if($value["tipo"] == "apartir" ){

                                    $valor_mao_obra = ($tempo["defeito_total"]/60);
                                    $valor_mao_obra = $valor_mao_obra * $value["valor_hora"];

                                    break;
                                }else{

                                    $this->_os_model->select("tbl_os")
                                          ->setCampos(array("SUM(tbl_os_defeito_reclamado_constatado.tempo_reparo) AS defeito_total"))
                                          ->addJoin(array( 'tbl_os_defeito_reclamado_constatado' => 'ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os '))
                                         ->addWhere(array('tbl_os_defeito_reclamado_constatado.os' => $this->_os));


                                    if (!$this->_os_model->prepare()->execute()) {
                                        throw new \Exception("Erro ao pegar mão de obra do produto da OS #6") ;
                                    }

                                    $tempo =$this->_os_model->getPDOStatement()->fetch();


                                     $this->_os_model->select("tbl_os")
                                      ->setCampos(array("tbl_os.qtde_hora"))
                                     ->addWhere(array('tbl_os.os' => $this->_os));

                                   if (!$this->_os_model->prepare()->execute()) {
                                        throw new \Exception("Erro ao pegar mão de obra do produto da OS #6") ;
                                    }

                                    $qtde_hora =$this->_os_model->getPDOStatement()->fetch();

                                    $tempo_reparo_int = ($tempo["defeito_total"]/60);

                                    $valor_mao_obra = ($tempo_reparo_int  + $qtde_hora["qtde_hora"] ) *  $value["valor_hora"];

                                    break;
                                }

                            }

                        }

                    }

                }
                // throw new \Exception("william teste fim dos ifs ") ;
                $this->_mao_de_obra = $valor_mao_obra  ;
                $this->_os_model->updateMaoDeObra($this->_mao_de_obra);
                $this->calculaExcecaoMaoDeObra();

                return $this;

            }else if(in_array($this->_fabrica,array(167,203))){

                $pdo = $this->_os_model->getPDO();

                $sql_os = "SELECT   tbl_tipo_atendimento.tipo_atendimento       AS tipo_atendimento,
                                    tbl_tipo_atendimento.descricao              AS tipo_atendimento_descricao,
                                    tbl_tipo_atendimento.fora_garantia          AS fora_garantia,
                                    tbl_servico_realizado.servico_realizado     AS servico_realizado,
                                    tbl_servico_realizado.descricao             AS servico_realizado_descricao,
                                    tbl_posto_fabrica.tipo_posto                AS tipo_posto,
                                    tbl_produto.familia                         AS familia,
                                    tbl_produto.produto                         AS produto
                            FROM tbl_os
                            JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                                AND tbl_tipo_atendimento.fabrica = $this->_fabrica
                            JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                            JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $this->_fabrica
                            JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $this->_fabrica
                            LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                AND tbl_os_item.fabrica_i = $this->_fabrica
                            LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                                AND tbl_servico_realizado.fabrica = $this->_fabrica
                            WHERE tbl_os.os = $this->_os
                            AND tbl_os.fabrica = $this->_fabrica";
                $qry = $pdo->query($sql_os);
                $dados_os = $qry->fetchAll();

                $conds = array();
                $valoresMo = array();
                $valor_mao_obra = 0 ;
                $atendimento_orcamento = "false";

                $sql_servico = "SELECT servico_realizado,descricao FROM tbl_servico_realizado
                                    WHERE fabrica = $this->_fabrica
                                    AND descricao IN ('Troca de Peça (gera pedido)','Ajuste')
                                    ORDER BY servico_realizado";
                $qry = $pdo->query($sql_servico);
                $servicos_realizados = $qry->fetchAll();

                $id_servico = array();
                foreach ($servicos_realizados as $key => $value) {
                    if($value['descricao'] == "Ajuste"){
                        $id_servico['ajuste'] = $value['servico_realizado'];
                    }else{
                        $id_servico['troca_peca'] = $value['servico_realizado'];
                    }
                }

                foreach ($dados_os as $key => $value) {
                    $tipo_atendimento               = $value['tipo_atendimento'];
                    $tipo_atendimento_descricao     = $value['tipo_atendimento_descricao'];
                    $servico_realizado              = $value['servico_realizado'];
                    $servico_realizado_descricao    = $value['servico_realizado_descricao'];
                    $tipo_posto                     = $value['tipo_posto'];
                    $familia                        = $value['familia'];
                    $produto                        = $value['produto'];
                    $fora_garantia                  = $value['fora_garantia'];

                    if($tipo_atendimento_descricao == "Garantia Recusada"){
                        $conds[1] = " AND tipo_atendimento = {$tipo_atendimento} ";
                    }

                    if($servico_realizado_descricao == "Troca de Produto"){
                        $conds[2] = " AND tipo_posto = {$tipo_posto} AND produto = {$produto} AND servico_realizado = {$servico_realizado} ";
                        $conds[3] = " AND tipo_posto = {$tipo_posto} AND familia = {$familia} AND servico_realizado = {$servico_realizado} ";
                    }

                    if($servico_realizado_descricao == "Troca de Peça (gera pedido)" OR $servico_realizado_descricao == "Troca de Peça (estoque)"){
                        $servico_realizado = $id_servico['troca_peca'];
                        $conds[4] = " AND tipo_posto = {$tipo_posto} AND produto = {$produto} AND servico_realizado = {$servico_realizado} ";
                        $conds[5] = " AND tipo_posto = {$tipo_posto} AND familia = {$familia} AND servico_realizado = {$servico_realizado} ";
                    }

                    if(empty($servico_realizado_descricao) OR $servico_realizado_descricao == "Ajuste"){
                        $servico_realizado = $id_servico['ajuste'];
                        $conds[6] = " AND tipo_posto = {$tipo_posto} AND produto = {$produto} AND servico_realizado = {$servico_realizado} ";
                        $conds[7] = " AND tipo_posto = {$tipo_posto} AND familia = {$familia} AND servico_realizado = {$servico_realizado} ";
                    }

                    if($tipo_atendimento_descricao == "Orçamento"){
                        $atendimento_orcamento = "true";
                    }
                }

                foreach ($conds as $key => $value) {
                    $sql_obra = "SELECT mao_de_obra
                                    FROM tbl_mao_obra_servico_realizado
                                    WHERE fabrica = $this->_fabrica
                                    $value";
                    $qry = $pdo->query($sql_obra);
                    if ($qry->rowCount() > 0) {
                       $dados_m_obra = $qry->fetch();
                       $valor_mao_obra = $dados_m_obra["mao_de_obra"];
                       break;
                    }
                }

                if($valor_mao_obra == 0){
                    if($atendimento_orcamento == "true"){
                        $res["mao_de_obra"] = 0;
                    }else{
                        throw new \Exception("Erro ao fechar a OS: {$this->_os} #MO");
                    }
                }else{
                    $res["mao_de_obra"] = $valor_mao_obra;
                    // $valor_mao_obra = max($valoresMo);
                    // $res["mao_de_obra"] = $valor_mao_obra;
                }

            }else{

                $mao_de_obra_familia            = Regras::get("mao_de_obra_familia", "mao_de_obra", $this->_fabrica);
                $calcula_mao_de_obra_defeito    = Regras::get("calcula_mao_de_obra_defeito", "mao_de_obra", $this->_fabrica);
	        $multiplos_defeitos             = Regras::get("multiplos_defeitos", "mao_de_obra", $this->_fabrica);
		$mao_de_obra_solucao            = Regras::get("mao_de_obra_solucao", "mao_de_obra", $this->_fabrica);
        $mao_de_obra_solucao_familia            = Regras::get("mao_de_obra_solucao_familia", "mao_de_obra", $this->_fabrica);

    		    if ($mao_de_obra_familia == true) {
                    $this->_os_model->select(Regras::get("tabela", "mao_de_obra", $this->_fabrica))
                        ->setCampos(array('tbl_familia.mao_de_obra_familia AS mao_de_obra'))
                        ->addJoin(array('tbl_produto' => "ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$this->_fabrica}"))
                        ->addJoin(array("tbl_familia" => "ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$this->_fabrica}"))
                        ->addWhere(array('os' => $this->_os));

    		    } else if($calcula_mao_de_obra_defeito == true && $multiplos_defeitos == true){

                    $this->_os_model->select("tbl_os_produto")
                        ->setCampos(array("MAX(tbl_diagnostico.mao_de_obra) AS mao_de_obra"))
                        ->addJoin(array(
                            'tbl_produto'                           => 'ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = '.$this->_fabrica,
                            'tbl_os_defeito_reclamado_constatado'   => 'ON tbl_os_defeito_reclamado_constatado.os = tbl_os_produto.os',
                            'tbl_diagnostico'                       => 'ON tbl_diagnostico.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado AND tbl_diagnostico.familia = tbl_produto.familia'))
                        ->addWhere(array('tbl_os_produto.os' => $this->_os))
                        ->setLimit(1);

                } else if ($mao_de_obra_solucao == true){
			$this->_os_model->select(Regras::get("tabela", "mao_de_obra", $this->_fabrica))
                        	->setCampos(array("tbl_solucao.parametros_adicionais::jsonb->>'mao_de_obra' AS mao_de_obra"))
                        	->addJoin(array("tbl_solucao" => "ON tbl_solucao.solucao = tbl_os.solucao_os AND tbl_solucao.fabrica = {$this->_fabrica}"))
                        	->addWhere(array('os' => $this->_os));

		        } else if($mao_de_obra_solucao_familia == true){
                        $this->_os_model->select("tbl_os_produto")
                        ->setCampos(array("SUM(tbl_diagnostico.mao_de_obra) AS mao_de_obra"))
                        ->addJoin(array(
                            'tbl_produto'                           => 'ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = '.$this->_fabrica,
                            'tbl_os_defeito_reclamado_constatado'   => 'ON tbl_os_defeito_reclamado_constatado.os = tbl_os_produto.os',
                            'tbl_diagnostico'                       => 'ON tbl_diagnostico.solucao = tbl_os_defeito_reclamado_constatado.solucao AND tbl_diagnostico.familia = tbl_produto.familia'))
                        ->addWhere(array('tbl_os_produto.os' => $this->_os))
                        ->setLimit(1);
                }else {
    	                $this->_os_model->select(Regras::get("tabela", "mao_de_obra", $this->_fabrica))
            	                        ->setCampos(array('tbl_produto.mao_de_obra'))
                    	                ->addJoin(array('tbl_produto' => 'USING (produto)'))
    	                                ->addWhere(array('os' => $this->_os));
    		    }
            }

            if (!in_array($this->_fabrica,array(148,167,203))) {

                if (!$this->_os_model->prepare()->execute()) {
                    throw new \Exception("Erro ao selecionar a mão de obra do produto da OS : {$this->_os}");
                }

                $res = $this->_os_model->getPDOStatement()->fetch();

            }

            $visita_mobra = Regras::get("visita_mobra", "mao_de_obra", $this->_fabrica);

            if($visita_mobra == true){

                  $this->_os_model->select("tbl_os")
                        ->setCampos(array("tbl_os.qtde_diaria"))
                        ->addWhere(array('tbl_os.os' => $this->_os))
                        ->addWhere(array('tbl_os.fabrica' => $this->_fabrica));

                    if (!$this->_os_model->prepare()->execute()) {
                        throw new \Exception("Erro ao calcular Visitas : {$os}");
                    }

                    $res_visitas = $this->_os_model->getPDOStatement()->fetch();

                    $qtde_visitas  = $res_visitas['qtde_diaria'];

                    if(empty($qtde_visitas) || $qtde_visitas==0){
                        $qtde_visitas = 1;
                    }
                    $res["mao_de_obra"] = $qtde_visitas * $res["mao_de_obra"];
            }
            
            $this->_mao_de_obra = $res["mao_de_obra"];

            $revenda_mobra = \Posvenda\Regras::get("os_revenda_mobra", "mao_de_obra", $this->_fabrica);

	    if (strlen($revenda_mobra) > 0) {
		if ($this->_fabrica == 157) {
			$this->_os_model->select("tbl_os")
			    ->setCampos(array('tbl_os.consumidor_revenda'))
			    ->addJoin(array('tbl_produto' => 'USING (produto)'))
			    ->addWhere(array('os'      => $this->_os))
			    ->addWhere(array('fabrica' => $this->_fabrica))
			    ->addWhere('tbl_produto.familia = 5390');
		}else{
			$this->_os_model->select("tbl_os")
			    ->setCampos(array('tbl_os.consumidor_revenda'))
			    ->addWhere(array('os'      => $this->_os))
			    ->addWhere(array('fabrica' => $this->_fabrica));

		}

                if (!$this->_os_model->prepare()->execute()) {
                    throw new \Exception("Erro ao  : {$this->_os}");
                }

                $res = $this->_os_model->getPDOStatement()->fetch();
                $consumidor_revenda = $res["consumidor_revenda"];

                if($consumidor_revenda == "R"){
                    $this->_mao_de_obra = $revenda_mobra;
                }
            }

            $tipoAtendimento = '';
            if ($this->_fabrica == 156) {
                $this->_os_model->select("tbl_os")
                    ->setCampos(array('tbl_os.tipo_atendimento'))
                    ->addWhere(array('os'      => $this->_os))
                    ->addWhere(array('fabrica' => $this->_fabrica));

                if (!$this->_os_model->prepare()->execute()) {
                    throw new \Exception("Erro ao  : {$this->_os}");
                }

                $res = $this->_os_model->getPDOStatement()->fetch();
                $tipoAtendimento = $res["tipo_atendimento"];
            }

            if ($this->_fabrica == 157) {

                $linha_produto = $this->verificaLinhaProduto($this->_os);
 
                if (in_array($linha_produto,array(942,943,944))) {

                    $os_sem_troca_peca = $this->verificaOsSemTrocaPeca($this->_os);

                    if($os_sem_troca_peca == true) {

                        $this->_mao_de_obra = 10;
                    }
                }
            }

            if ($this->_fabrica == 163) {
                $this->_os_model->select("tbl_os")
                                ->setCampos(array('tbl_tipo_atendimento.fora_garantia'))
                                ->addJoin(array('tbl_tipo_atendimento' => 'USING (tipo_atendimento)'))
                                ->addWhere(array('tbl_os.os'      => $this->_os))
                                ->addWhere(array('tbl_os.fabrica' => $this->_fabrica));

                if (!$this->_os_model->prepare()->execute()) {
                    throw new \Exception("Erro ao  : {$this->_os}");
                }

                $res = $this->_os_model->getPDOStatement()->fetch();
                $tipoAtendimento = $res["fora_garantia"];

                if ($tipoAtendimento) {
                  $this->_mao_de_obra = 0;
                }
            }

            if (in_array($this->_fabrica, [169,170])) {

                $this->_os_model->select("tbl_os")
                    ->setCampos(array('tbl_tipo_atendimento.descricao, tbl_posto_fabrica.parametros_adicionais'))
                    ->addJoin(array('tbl_tipo_atendimento' => 'USING (tipo_atendimento)'))
                    ->addJoin(array('tbl_posto_fabrica' => 'ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica ='.$this->_fabrica))
                    ->addWhere(array('tbl_os.os'      => $this->_os))
                    ->addWhere(array('tbl_os.fabrica' => $this->_fabrica));

                if (!$this->_os_model->prepare()->execute()) {
                    throw new \Exception("Erro ao buscar tipo de atendimento : {$this->_os}");
                }
        
                $res = $this->_os_model->getPDOStatement()->fetch();
                
                $descTipoAtendimento = $res["descricao"];

                if ($descTipoAtendimento == "Triagem") {

                    $arrParametrosAdicionais = json_decode($res["parametros_adicionais"], true);

                    if ((float) $arrParametrosAdicionais['mo_triagem'] > 0) {

                        $this->_mao_de_obra = $arrParametrosAdicionais['mo_triagem'];

                    }

                }

            }

            if ($this->_fabrica != 156 && $tipoAtendimento != 261) {
                $this->_os_model->updateMaoDeObra($this->_mao_de_obra);
                $this->calculaExcecaoMaoDeObra();
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $this;
    }

    /**
     * Pega as exceções de mão-de-obra da OS
     *
     * @return MaoDeObra
     */
    protected function calculaExcecaoMaoDeObra()
    {
        $excecaoMobra = new ExcecaoMobra($this->_os, $this->_fabrica, "");

        try {
            $excecaoMobra->calculaExcecaoMobra();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $this->_os_model->select("tbl_os")
                        ->setCampos(array('mao_de_obra'))
                        ->addWhere(array('os' => $this->_os));

        if (!$this->_os_model->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar a mão de obra da após o calculo da exceção de mão de obra da OS : {$this->_os}");
        }

        $res = $this->_os_model->getPDOStatement()->fetch();

        if (empty($res)) {
            return false;
        } else {
            $this->_mao_de_obra = $res['mao_de_obra'];
        }

        return $this;
    }

    public function calculaMaoDeObraRevisao($os){
        //KM
        $this->_os_model->select("tbl_os")
                        ->setCampos(array("tbl_os.qtde_km", "tbl_os.qtde_diaria", "tbl_posto_fabrica.valor_km AS valor_posto", "tbl_fabrica.valor_km AS valor_fabrica"))
                        ->addJoin(array(
                            "tbl_posto_fabrica" => "ON tbl_posto_fabrica.posto = tbl_os.posto",
                            "tbl_fabrica" => "ON tbl_fabrica.fabrica = tbl_os.fabrica"
                        ))
                        ->addWhere(array('tbl_os.os' => $os))
                        ->addWhere(array('tbl_os.fabrica' => $this->_fabrica))
                        ->addWhere(array('tbl_posto_fabrica.fabrica' => $this->_fabrica));

        if (!$this->_os_model->prepare()->execute()) {
            throw new \Exception("Erro ao calcular OS : {$os} /* SQL 1 */");
        }

        $res = $this->_os_model->getPDOStatement()->fetch();

        $qtde_km       = $res["qtde_km"];
        $qtde_visitas  = $res['qtde_diaria'];
        $valor_posto   = $res["valor_posto"];
        $valor_fabrica = $res["valor_fabrica"];

        $valor_km = ($valor_posto > 0) ? $valor_posto : $valor_fabrica;

        if (empty($qtde_km)) {
            $qtde_km = 0;
        }

        if (empty($qtde_visitas)) {
            $qtde_visitas = 0;
        }

        $limite_km       = \Posvenda\Regras::get("limite_km", "mao_de_obra", $this->_fabrica);
        $limite_km_qtde  = \Posvenda\Regras::get("limite_km_qtde", "mao_de_obra", $this->_fabrica);
        $limite_km_valor = \Posvenda\Regras::get("limite_km_valor", "mao_de_obra", $this->_fabrica);

        if ($limite_km === true) {
            if ($qtde_km <= $limite_km) {
                $total_km = $limite_km_valor;
            } else {
                $total_km = $qtde_km * $valor_km;
            }
        } else {
            $total_km = $qtde_km * $valor_km;
        }

        $total_km = $total_km * $qtde_visitas;

        $this->_os_model->updateQtdeKmCalculada($total_km, $os);

        //Mão de Obra
        $this->_os_model->select("tbl_os_produto")
                        ->setCampos(array("MAX(tbl_produto.mao_de_obra) AS taxa_servico"))
                        ->addJoin(array("tbl_produto" => "ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$this->_fabrica}"))
                        ->addWhere(array("tbl_os_produto.os" => $os));

        if (!$this->_os_model->prepare()->execute()) {
            throw new \Exception("Erro ao calcular OS : {$os} /* SQL 2 */");
        }

        $res = $this->_os_model->getPDOStatement()->fetch();

        $taxa_servico  = $res["taxa_servico"];

        $mao_de_obra = $taxa_servico * $qtde_visitas;

        $this->_os_model->updateMaoDeObra($mao_de_obra, $os);

        //Valor Adicional Revisão
        $this->_os_model->select("tbl_os_produto")
                        ->setCampos(array("SUM(capacidade) AS qtde_produto"))
                        ->addWhere(array("os" => $os));

        if (!$this->_os_model->prepare()->execute()) {
            throw new \Exception("Erro ao calcular OS : {$os} /* SQL 3 */");
        }

        $res = $this->_os_model->getPDOStatement()->fetch();

        $qtde_produto  = $res["qtde_produto"] - $qtde_visitas;

        $this->_os_model->select("tbl_os_campo_extra")
                     ->setCampos(array("valores_adicionais"))
                     ->addWhere(array("os" => $os));

        if (!$this->_os_model->prepare()->execute()) {
            throw new \Exception("Erro ao calcular OS : {$os} /* SQL 4 */");
        }

        if ($this->_os_model->getPDOStatement()->rowCount() == 0) {
            $this->_os_model->insert("tbl_os_campo_extra")
                            ->setCampos(array(
                                "fabrica" => $this->_fabrica,
                                "os" => $os
                            ));

            if (!$this->_os_model->prepare()->execute()) {
                throw new \Exception("Erro ao calcular OS : {$os} /* SQL 5 */");
            }

            $valores_adicionais = array();
        } else {
            $res = $this->_os_model->getPDOStatement()->fetch();

            $valores_adicionais = json_decode($res["qtde_produto"], true);
        }


        $valores_adicionais["revisao"] = $qtde_produto;
        $valores_adicionais            = json_encode($valores_adicionais);

        $this->_os_model->update("tbl_os_campo_extra")
                        ->setCampos(array('valores_adicionais' => $valores_adicionais))
                        ->addWhere(array("os" => $os));

        if (!$this->_os_model->prepare()->execute()) {
            throw new \Exception("Erro ao calcular OS - {$os} /* SQL 6 */");
        }

        try {
            $this->_os_model->calculaValorAdicional($os);
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }


        return $this;
    }

    protected function getOsModel()
    {
        return $this->_os_model;
    }
}


