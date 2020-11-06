<?php
namespace Posvenda\Model;

class Os extends AbstractModel
{

    private $_os;
    private $_fabrica;
    public $_conn;

    public function __construct($fabrica, $os = null, $conn=null)
    {
        parent::__construct('tbl_os');

        $this->_fabrica = $fabrica;

        $this->_conn = $conn;

        if (!empty($os)) {
            $this->_os = $os;

            if (!empty($conn)) {

                $sql = "SELECT sua_os FROM tbl_os WHERE os = {$this->_os} AND fabrica = {$this->_fabrica}";
                $query = pg_query($conn, $sql);
                $res = pg_fetch_all($query);

            } else {

                $pdo = $this->getPDO();
                $query = $pdo->query("SELECT sua_os FROM tbl_os WHERE os = {$os} AND fabrica = {$fabrica}");
                $res = $query->fetch(\PDO::FETCH_ASSOC);

            }

            if (!empty($res)) {
                $this->_sua_os = $res['sua_os'];
            }

         }

    }

    /**
     * Verifica se a OS est&aacute; fechada
     *
     * @param mixed $os Número da OS
     * @return boolean
     */
    public function isClosed($os = null)
    {
        if (empty($os)) {
            $os = $this->_os;
        }

        $this->select()
             ->setCampos(array('os'))
             ->addWhere(array('os' => $os))
             ->addWhere(array('fabrica' => $this->_fabrica))
             ->addWhere('data_fechamento IS NOT NULL');

        $this->prepare()->execute();

        $res = $this->getPDOStatement()->fetch();

        if (empty($res)) {
            return false;
        }

        return true;
    }

    /**
     * Zera m&atilde;o-de-obra da OS
     *
     * @param integer $os
     * @return Os
     */
    public function zeraMaoDeObra($os = null)
    {

        if (empty($os)) {
            $os = $this->_os;
        }

        $this->update("tbl_os")
             ->setCampos(array('mao_de_obra' => 0))
             ->addWhere(array('os' => $os))
             ->addWhere(array('fabrica' => $this->_fabrica));

        if (!$this->prepare()->execute()) {
            throw new Exception("Erro ao zerar mão de obra da OS : {$this->_sua_os}");
        }

        return $this;
    }

    /**
     * Atualiza m&atilde;o-de-obra da OS
     *
     * @param float $mobra
     * @param integer $os
     * @return Os
     */
    public function updateMaoDeObra($mobra, $os = null)
    {
        if (empty($mobra)) {
            $this->zeraMaoDeObra($os);
            return $this;
        }

        if (empty($os)) {
            $os = $this->_os;
        }
        $this->update("tbl_os")
             ->setCampos(array('mao_de_obra' => $mobra))
             ->addWhere(array('os' => $os));


        if (!$this->prepare()->execute()) {
            throw new \Exception("Erro ao atualizar mão de obra da OS : {$this->_sua_os} ");
        }

        return $this;
    }

    public function atualizaCustoItem($pedidos)
    {
        if (is_array($pedidos)) {
            foreach ($pedidos as $pedido) {
                $this->select("tbl_pedido_item")
                    ->setCampos(array("tbl_pedido_item.preco", "tbl_os_item.os_item", 'tbl_os_item.os_produto'))
                    ->addJoin(array("tbl_os_item" => "ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item"))
                    ->addWhere(array("tbl_pedido_item.pedido" => $pedido));

                if (!$this->prepare()->execute()) {
                    throw new \Exception("Falha ao buscar itens do pedido $pedido");
                }
                
                $rPedido = $this->getPDOStatement()->fetchAll();
                foreach ($rPedido as $item) {
                    $precoItem = $item['preco'];

		    $this->select('tbl_os_produto')
			->setCampos(['os'])
			->addWhere(['os_produto' => $item['os_produto']]);

		    if (!$this->prepare()->execute()) {
			throw new \Exception('Falha ao buscar produto da OS '.$pedido);
		    }

		    $osProduto = $this->getPDOStatement()->fetch();

		    $this->select('tbl_os')
			->setCampos(['status_checkpoint', 'data_fechamento', 'finalizada'])
			->addWhere(['os' => $osProduto['os']]);

		    if (!$this->prepare()->execute()) {
			throw new \Exception('Falha ao buscar OS '.$pedido);
		    }

		    $os = $this->getPDOStatement()->fetch();

		    if ($os['status_checkpoint'] == 9) {
			$this->update('tbl_os')
				->setCampos(['data_fechamento' => null, 'finalizada' => null])
				->addWhere(['os' => $osProduto['os']]);

			if (!$this->prepare()->execute()) {
				throw new \Exception('Falha ao atualizar OS #1 '.$pedido);
			}
		    }

                    $osItem = $item['os_item'];

                    $this->update("tbl_os_item")
                        ->setCampos(array("custo_peca" => $precoItem))
                        ->addWhere(array("os_item" => $osItem));

                    if (!$this->prepare()->execute()) {
                        throw new \Exception("Falha ao atualizar preço do item $osItem");
                    }

		    if ($os['status_checkpoint'] == 9) {
			$this->update('tbl_os')
				->setCampos([
					'data_fechamento' => $os['data_fechamento'], 
					'finalizada' => $os['finalizada']
				])
				->addWhere(['os' => $osProduto['os']]);

			if (!$this->prepare()->execute()) {
				throw new \Exception('Falha ao atualizar OS #2 '.$pedido);
			}
		    }
                }
            }
        }
    }

    /**
     * Atualiza m&atilde;o-de-obra da OS
     *
     * @param float $total_km
     * @param integer $os
     * @return Os
     */
    public function updateQtdeKmCalculada($total_km, $os = null)
    {
        if (empty($total_km)) {
            return $this;
        }

        if (empty($os)) {
            $os = $this->_os;
        }

        $this->update("tbl_os")
             ->setCampos(array('qtde_km_calculada' => $total_km))
             ->addWhere(array('os' => $os));

        if (!$this->prepare()->execute()) {
            throw new \Exception("Erro ao atualizar a quantidade de KM calculada para a OS : {$this->_sua_os}");
        }

        return $this;
    }

    public function atualizaValorAdicional($os, $valor){

        $this->update("tbl_os")
             ->setCampos(array("valores_adicionais" => number_format($valor, 2, ".", "")))
             ->addWhere(array("os" => $os))
             ->addWhere(array("consumidor_revenda" => "R"));

        if (!$this->prepare()->execute()) {
            throw new \Exception("Erro ao atualizar os valores adicionais para a OS : {$os}");
        }

        return true;

    }

    /**
     * Calcula o Valor Adicional da OS
     *
     * @param integer $os
     * @return Os
     */
    public function calculaValorAdicional($os, $con = null)
    {

        $valor_adicional = 0;

        if (is_null($con)) {
            $this->select("tbl_os_campo_extra")
                            ->setCampos(array('valores_adicionais'))
                            ->addWhere(array('os' => $os));

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao selecionar os valores adicionais da OS : {$this->_sua_os}");
            }

            $res = $this->getPDOStatement()->fetch();
        } else {
            $sql = "
                SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = {$os}
            ";
            $res = pg_query($con, $sql);

            if (!pg_num_rows($res)) {
                throw new \Exception("Erro ao selecionar os valores adicionais da OS : {$this->_sua_os}");
            }

            $res = pg_fetch_assoc($res);
        }

        if (empty($res)) {
            return false;
        } else {

            $valores = json_decode($res['valores_adicionais'],true);

			foreach ($valores as $chave => $valor) {
				 if(is_array($valor)) {
                    foreach($valor as $chave2 => $valor2) {
                        if (preg_match("/,/", $valor2)) {
                            $valor2 = str_replace(".", "", $valor2);
                            $valor2 = str_replace(",", ".", $valor2);
                        }

                        $valor_adicional += $valor2;
                    }
                }else{
                    if (preg_match("/,/", $valor2)) {
                        $valor = str_replace(".", "", $valor);
                        $valor = str_replace(",", ".", $valor);
                    }

                    $valor_adicional += $valor;
                }
            }

            if (is_null($con)) {
                $this->update("tbl_os")
                 ->setCampos(array('valores_adicionais' => $valor_adicional))
                 ->addWhere(array('os' => $os));

                if (!$this->prepare()->execute()) {
                    throw new \Exception("Erro ao atualizar os valores adicionais da OS : {$this->_sua_os}");
                }
            } else {
                $sql = "
                    UPDATE tbl_os SET
                        valores_adicionais = $valor_adicional
                    WHERE os = {$os}
                ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new \Exception("Erro ao atualizar os valores adicionais da OS : {$this->_sua_os}");
                }
            }
        }

        return $this;
    }

    /**
     * Calcula o KM da OS
     *
     * @param integer $os
     * @return Os
     */
    public function calculaKM($os = null, $con = null)
    {
        if (empty($os)) {
            $os = $this->_os;
        }

        if (is_null($con)) {
            $this->select("tbl_os")
                 ->setCampos(
                        array(
                                "tbl_os.qtde_km",
                                "tbl_os.qtde_diaria",
                                "tbl_posto_fabrica.valor_km AS valor_posto",
                                "tbl_fabrica.valor_km AS valor_fabrica",
                                "UPPER(fn_retira_especiais(tbl_os.consumidor_cidade)) AS cidade_os",
                                "UPPER(fn_retira_especiais(tbl_posto_fabrica.contato_cidade)) AS cidade_posto",
                                "JSON_FIELD('qtde_km_ida',tbl_os_campo_extra.campos_adicionais::TEXT) AS qtde_km_ida",
                                "JSON_FIELD('qtde_km_volta',tbl_os_campo_extra.campos_adicionais::TEXT) AS qtde_km_volta",
                                "tbl_os_campo_extra.valores_adicionais",
                                "tbl_tipo_atendimento.fora_garantia"
                            )
                    )
                 ->addJoin(array(
                    "tbl_posto_fabrica" => "ON tbl_posto_fabrica.posto = tbl_os.posto",
                    "tbl_fabrica" => "ON tbl_fabrica.fabrica = tbl_os.fabrica",
                    "tbl_tipo_atendimento" => "ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento"
                 ))
                 ->addJoin(array(
                    "tbl_os_campo_extra" => "ON tbl_os_campo_extra.os = tbl_os.os"
                 ), true)
                 ->addWhere(array('tbl_os.os' => $os))
                 ->addWhere(array('tbl_os.fabrica' => $this->_fabrica))
                 ->addWhere(array('tbl_posto_fabrica.fabrica' => $this->_fabrica));

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao selecionar as informações de KM da OS : {$this->_sua_os}");
            }
            $res = $this->getPDOStatement()->fetch();
        } else {
            $sql = "
                SELECT
                    tbl_os.qtde_km,
                    tbl_os.qtde_diaria,
                    tbl_posto_fabrica.valor_km AS valor_posto,
                    tbl_fabrica.valor_km AS valor_fabrica,
                    UPPER(fn_retira_especiais(tbl_os.consumidor_cidade)) AS cidade_os,
                    UPPER(fn_retira_especiais(tbl_posto_fabrica.contato_cidade)) AS cidade_posto,
                    JSON_FIELD('qtde_km_ida',tbl_os_campo_extra.campos_adicionais::TEXT) AS qtde_km_ida,
                    JSON_FIELD('qtde_km_volta',tbl_os_campo_extra.campos_adicionais::TEXT) AS qtde_km_volta,
                    tbl_os_campo_extra.valores_adicionais,
		            tbl_tipo_atendimento.fora_garantia
                FROM tbl_os
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
                INNER JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica
                LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
		        INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                WHERE tbl_os.os = {$os}
                AND tbl_os.fabrica = {$this->_fabrica}
                AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
            ";

            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new \Exception("Erro ao selecionar as informações de KM da OS : {$this->_sua_os}");
            }

            $res = pg_fetch_assoc($res);
        }

        $qtde_km       = $res["qtde_km"];
        $valor_posto   = $res["valor_posto"];
        $valor_fabrica = $res["valor_fabrica"];
        $qtde_diaria   = $res["qtde_diaria"];

        $calcula_excedente_km = \Posvenda\Regras::get("calcula_excedente_km", "mao_de_obra", $this->_fabrica);

        if (!empty($calcula_excedente_km) && $calcula_excedente_km !== 0) {
            $total   = ($res["qtde_km_ida"] + $res["qtde_km_volta"]) - $calcula_excedente_km;
            $qtde_km = ($total > 0) ? $total : $qtde_km;
	    }

        if (in_array($this->_fabrica, array(171))) {
            $ida_volta = $res["qtde_km_ida"] + $res["qtde_km_volta"];
            if ($ida_volta <= 30) {
                $qtde_km = 0;
            }
        }


        if (in_array($this->_fabrica, array(195))) {
            if ($qtde_km <= 50) {
                $qtde_km = 0;
            } 
        }


        if (in_array($this->_fabrica, array(169,170))) {
            $qtde_km_aux = (int)$qtde_km;

            if (($qtde_km_aux <= 60 || $res['cidade_os'] == $res['cidade_posto']) && $res['fora_garantia'] != "t") {
                $qtde_km = 0;
            } else {
                $qtde_km -= 60;

                if ($qtde_km < 0) {
                    $qtde_km = 0;
                }
            }
        }
    	
        if(in_array($this->_fabrica, array(183))){
    		if ($qtde_km <= 30) {
    			$qtde_km = 0;
    		}

    		if($qtde_km >= 101 && $qtde_km <= 200){
    			$taxa_extra_km = 31;
    		}else if($qtde_km > 200){
    			$taxa_extra_km = 62;
    		}

    		if($taxa_extra_km > 0){
                if (is_null($con)) {
                    $pdo = $this->getPDO();
                    $sql = "SELECT os, campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";
                    $query = $pdo->query($sql);
                    $res = $query->fetch(\PDO::FETCH_ASSOC);
                    $res = array_filter($res);

                    if(count($res) > 0){
                        $campos_adicionais = $res['campos_adicionais'];
                        if(strlen($campos_adicionais) == 0){
                            $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{}' WHERE os = {$os}";
                            $query = $pdo->query($sql);
                        }
                        $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = jsonb_set(campos_adicionais::jsonb,'{taxa_extra_km}','\"{$taxa_extra_km}\"') WHERE os = {$os}";
                        $query = $pdo->query($sql);
                    }else{
                        $info = json_encode(array("taxa_extra_km" => $taxa_extra_km));
                        $sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,campos_adicionais) VALUES({$os},{$this->_fabrica},'{$info}')";
                        $query = $pdo->query($sql);
                    }
                }else{
                    $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){

                        $campos_adicionais = pg_fetch_result($res,0,'campos_adicionais');

                        if(strlen($campos_adicionais) == 0){
                            $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{}' WHERE os = {$os}";
                            $res = pg_query($con,$sql);
                        }

                        $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = jsonb_set(campos_adicionais::jsonb,'{taxa_extra_km}','\"{$taxa_extra_km}\"') WHERE os = {$os}";
                        $res = pg_query($con,$sql);
                    }else{
                        $info = json_encode(array("taxa_extra_km" => $taxa_extra_km));
                        $sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,campos_adicionais) VALUES({$os},{$this->_fabrica},'{$info}')";
                        $res = pg_query($con,$sql);
                    }
                }
    		}
    	}

        $valor_km = ($valor_posto > 0) ? $valor_posto : $valor_fabrica;

        if ($qtde_km > 0) {
            $calcula_visita_km = \Posvenda\Regras::get("calcula_visita_km", "mao_de_obra", $this->_fabrica);

            if ($calcula_visita_km === true) {
                $qtde_km = $qtde_km * $qtde_diaria;
            }

            $limite_km = \Posvenda\Regras::get("limite_km", "mao_de_obra", $this->_fabrica);

            if ($limite_km == true) {
                $limite_km_qtde  = \Posvenda\Regras::get("limite_km_qtde", "mao_de_obra", $this->_fabrica);
                $limite_km_valor = \Posvenda\Regras::get("limite_km_valor", "mao_de_obra", $this->_fabrica);

                if ($qtde_km <= $limite_km_qtde) {
                    $km_calculado = $limite_km_valor;
                } else {
                    $km_calculado = $qtde_km * $valor_km;
                }
            } else {
                $km_urbano_interurbano = \Posvenda\Regras::get("km_urbano_interurbano", "mao_de_obra", $this->_fabrica);
                $km_valor_tabela = \Posvenda\Regras::get("km_valor_tabela", "mao_de_obra", $this->_fabrica);

                if($km_urbano_interurbano == true){
                    $valores_km           = \Posvenda\Regras::get("valores_km", "mao_de_obra", $this->_fabrica);
                    $valor_km_urbano      = $valores_km[0];
                    $valor_km_interurbano = $valores_km[1];
                    $cidade_posto         = $res["cidade_posto"];
                    $cidade_os            = $res["cidade_os"];
                    $km_calculado         = ($cidade_posto == $cidade_os) ? $qtde_km * $valor_km_urbano : $qtde_km * $valor_km_interurbano;
                } elseif ( is_array($km_valor_tabela) ) {
                    $break_for = false;
                    foreach ($km_valor_tabela as $itens => $dados) {
                        switch ($dados['condicao']) {
                            case '<':
                                if ($qtde_km < $dados['qtde']) {
                                    $km_calculado = $qtde_km * $dados['valor'];
                                    $break_for = true;
                                }
                                break;
                            case '>=':
                                if ($qtde_km >= $dados['qtde']) {
                                    $km_calculado = $qtde_km * $dados['valor'];
                                    $break_for = true;
                                }
                                break;
                            default:
                                break;
                        }

                        if ($break_for == true) {
                            break;
                        }
                    }
                } else{
                    $km_calculado = $qtde_km * $valor_km;
                    
                    if ($this->_fabrica == 183 AND !empty($taxa_extra_km)){
                        $km_calculado = $km_calculado + $taxa_extra_km;
                    }
                }
            }
        } else {
            $km_calculado = 0;
        }

        if (in_array($this->_fabrica, array(195))) {
            $xvalores_adicionais  = json_decode($res["valores_adicionais"],1);
            $pdo = $this->getPDO();
            $sqlAgenda = "SELECT COUNT(*) AS total FROM tbl_tecnico_agenda WHERE confirmado IS NOT NULL AND data_cancelado IS NULL AND os = {$os}";
            $queryAgenda = $pdo->query($sqlAgenda);
            $resAgenda = $queryAgenda->fetch(\PDO::FETCH_ASSOC);
           
            $TOTAL_VISITA = $resAgenda["total"];

            if ($TOTAL_VISITA > 1) {
                $valor = [];
                for ($i=1; $i <= $TOTAL_VISITA; $i++) { 
                    if ($i == 1) {
                        $auxValor = $valor_km*$qtde_km;
                        $valor[]  = ($auxValor < 0) ? 0 : $auxValor;
                    } else {
                        $auxValor = $valor_km*($qtde_km-50);
                        $valor[] = ($auxValor < 0) ? 0 : $auxValor;
                    }


                }

                $km_calculado = array_sum($valor);

            } else {
                $km_calculado = $valor_km*$qtde_km;
            }

        }


        if(in_array($this->_fabrica, array(152,180,181,182))) {
            if (is_null($con)) {
                $this->select("tbl_os_produto")
                      ->setCampos(array("tbl_produto.code_convention,tbl_produto.entrega_tecnica,tbl_produto.linha,tbl_os_produto.capacidade"))
                     ->addJoin(array( 'tbl_produto' => 'ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = '.$this->_fabrica ))
                     ->addWhere(array('tbl_os_produto.os' => $this->_os));

                if (!$this->prepare()->execute()) {
                    throw new \Exception("Erro ao calcular KM da OS #1") ;
                }

                $result_prod =$this->getPDOStatement()->fetchAll();

                $this->select("tbl_fabrica")
                        ->setCampos(array("tbl_fabrica.parametros_adicionais"))
                        ->addWhere(array('tbl_fabrica.fabrica' => $this->_fabrica));

                if (!$this->prepare()->execute()) {
                    throw new \Exception("Erro ao calcular KM da OS #2") ;
                }

                $parametros_adicionais =$this->getPDOStatement()->fetch();
            } else {
                $sql = "
                    SELECT
                        tbl_produto.code_convention,
                        tbl_produto.entrega_tecnica,
                        tbl_produto.linha,
                        tbl_os_produto.capacidade
                    FROM tbl_os_produto
                    INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$this->_fabrica}
                    WHERE tbl_os_produto.os = {$this->_os}
                ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new \Exception("Erro ao calcular KM da OS #1") ;
                }

                $result_prod = pg_fetch_all($res);

                $sql = "
                    SELECT
                        tbl_fabrica.parametros_adicionais
                    FROM tbl_fabrica
                    WHERE tbl_fabrica.fabrica = {$this->_fabrica}
                ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new \Exception("Erro ao calcular KM da OS #2") ;
                }

                $parametros_adicionais = pg_fetch_assoc($res);
            }

            $parametros_adicionais = json_decode($parametros_adicionais["parametros_adicionais"],true);

            $entrega_tecnica = array();
            $assistencia_tecnica = array();

            $entrega_tecnica = $parametros_adicionais["valores_mao_de_obra"]["entrega_tecnica"];
            $assistencia_tecnica= $parametros_adicionais["valores_mao_de_obra"]["assistencia_tecnica"];

            foreach ($result_prod as $key => $produto) {
                if (is_null($con)) {
                    $this->select("tbl_tipo_atendimento")
                                    ->setCampos(array("tbl_tipo_atendimento.entrega_tecnica"))
                                    ->addJoin(array( 'tbl_os' => 'ON tbl_tipo_atendimento.tipo_atendimento =  tbl_os.tipo_atendimento '))
                                    ->addWhere("tbl_os.os = ". $this->_os);

                    if (!$this->prepare()->execute()) {
                        throw new \Exception("Erro ao calcular KM da OS #3") ;
                    }

                    $tipo_atendimento =$this->getPDOStatement()->fetch();
                } else {
                    $sql = "
                        SELECT tbl_tipo_atendimento.entrega_tecnica
                        FROM tbl_tipo_atendimento
                        INNER JOIN tbl_os ON tbl_os.os = tbl_tipo_atendimento.os
                        WHERE tbl_os.os = {$this->_os}
                    ";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new \Exception("Erro ao calcular KM da OS #3") ;
                    }

                    $tipo_atendimento = pg_fetch_assoc($res);
                }

                if($tipo_atendimento["entrega_tecnica"]=="t") {
                    // DESLOCAMENTO - apartir x horas x
                    if (is_null($con)) {
                        $this->select("tbl_os")->setCampos(array("qtde_hora"))->addWhere("os = {$os}");

                        if (!$this->prepare()->execute()) {
                            throw new \Exception("Erro ao calcular KM da OS #4") ;
                        }

                        $hora_deslocamento = $this->getPDOStatement()->fetch();
                    } else {
                        $sql = "SELECT qtde_hora FROM tbl_os WHERE os = {$os}";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new \Exception("Erro ao calcular KM da OS #4") ;
                        }

                        $hora_deslocamento = pg_fetch_assoc($res);
                    }

                    if(empty($hora_deslocamento["qtde_hora"]) || strlen($hora_deslocamento["qtde_hora"])==0){
                        throw new \Exception("Necessario preencher tempo de deslocamento da OS: {$this->_os}") ;
                    }

                    $valor_km_esab = (int)(($hora_deslocamento["qtde_hora"] - $entrega_tecnica["apartir"] ) + 1 );

                    if( $valor_km_esab > 0 ){
                        $km_calculado += $valor_km_esab * $entrega_tecnica["deslocamento"] ;
                    }

                    break;
                }else{
                    foreach ($assistencia_tecnica as $key => $value) {
                       // *os normal com deloscamento: se o tipo de pagamento for hora tecnica ir&aacute; pagar o tempo de deslocamento * valor deslocamento,
                        // tipo de pagamento hora corrida n&atilde;o ir&aacute; pagar esse valor adicional por km
                        if ($value["linha"] == $produto["linha"] ) {
                            /*
                            * apartir == paga km
                            * deslocamento == nao paga
                            */
                            if($value["tipo"] == "apartir" ){
                                if (is_null($con)) {
                                    $this->select("tbl_os")
                                        ->setCampos(array("qtde_hora"))
                                        ->addWhere("os = {$os}");

                                    if (!$this->prepare()->execute()) {
                                        throw new \Exception("Erro ao calcular KM da OS #5") ;
                                    }

                                    $hora_deslocamento =$this->getPDOStatement()->fetch();
                                } else {
                                    $sql = "SELECT qtde_hora FROM tbl_os WHERE os = {$os}";
                                    $res = pg_query($con, $sql);

                                    if (strlen(pg_last_error()) > 0) {
                                        throw new \Exception("Erro ao calcular KM da OS #5") ;
                                    }

                                    $hora_deslocamento = pg_fetch_assoc($res);
                                }

                                $qntd_hora = $hora_deslocamento["qtde_hora"] - 1;

                                if ($qntd_hora > 0) {
                                    $qntd_hora = $qntd_hora * $value["valor_deslocamento"];
                                    $km_calculado += $qntd_hora;
                                }

                                break;
                            }
                        }
                    }
                }
            }
        }

        if (is_null($con)) {
            $this->update("tbl_os")
                 ->setCampos(array('qtde_km_calculada' => $km_calculado))
                 ->addWhere(array('os' => $os));

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao calcular KM da OS: {$this->_sua_os}" );
            }
        } else {
            $sql = "UPDATE tbl_os SET qtde_km_calculada = {$km_calculado} WHERE os = {$os}";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new \Exception("Erro ao calcular KM da OS: {$this->_sua_os}" );
            }
        }

        return $this;
    }

    /**
     * Pega o KM da OS
     *
     * @param integer $os
     * @return float
     */
    public function getKM($os = null)
    {
        if (empty($os)) {
            $os = $this->_os;
        }

        $this->select()
             ->setCampos(array("qtde_km_calculada"))
             ->addWhere(array('os' => $os))
             ->addWhere(array('fabrica' => $this->_fabrica));

        if (!$this->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar o valor de KM da OS : {$this->_sua_os}");
        }

        $res = $this->getPDOStatement()->fetch();

        return $res["qtde_km_calculada"];
    }

    /**
     * Calcula o valor das pe&ccedil;as da OS
     *
     * @param integer $os
     * @return Os
     */
    public function calculaValorPecas($os = null)
    {
        if (empty($os)) {
            $os = $this->_os;
        }

        $this->select("tbl_os_item")
             ->setCampos(array("tbl_os_item.os_item", "tbl_os_item.peca", "tbl_os_item.qtde", "tbl_peca.referencia"))
             ->addJoin(array(
                "tbl_os_produto" => "ON tbl_os_produto.os_produto = tbl_os_item.os_produto",
                "tbl_servico_realizado" => "ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado",
                "tbl_peca" => "ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}"
             ))
             ->addWhere(array("tbl_os_produto.os" => $os))
             ->addWhere("tbl_servico_realizado.gera_pedido IS NOT TRUE")
             ->addWhere("tbl_servico_realizado.troca_de_peca IS TRUE")
             ->addWhere("tbl_servico_realizado.peca_estoque IS TRUE");

        if (!$this->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar as peças da OS : {$this->_sua_os}");
        }

        $res = $this->getPDOStatement()->fetchAll();

        $total = 0;

        foreach ($res as $key => $peca) {
            $this->select("tbl_tabela_item")
                 ->setCampos(array("tbl_tabela_item.preco"))
                 ->addJoin(array(
                    "tbl_tabela" => "ON tbl_tabela.fabrica = {$this->_fabrica} AND tbl_tabela.tabela = tbl_tabela_item.tabela"
                 ))
                 ->addWhere(array("tbl_tabela_item.peca" => $peca["peca"]))
                 ->addWhere("tbl_tabela.tabela_garantia IS TRUE");

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao selecionar o preço da peça : ".$peca["peca"]);
            }

            $tabelaItem = $this->getPDOStatement()->fetch();

            $pedidoClass = new \Posvenda\Pedido($this->_fabrica);

            try {
                $preco = $pedidoClass->getPrecoPecaRecompra($peca["peca"], $os);
            } catch (\Exception $e) {
                throw new \Exception("Erro ao selecionar o preço de recompra para a Peça : {$peca['referencia']} e OS : {$this->_sua_os}");
            }

            $preco = $preco * $peca["qtde"];

            $adicional_preco_peca = \Posvenda\Regras::get("adicional_preco_peca", "mao_de_obra", $this->_fabrica);

            if (!empty($adicional_preco_peca)) {
                switch ($adicional_preco_peca["formula"]) {
                    case "+%":
                        if (!empty($adicional_preco_peca["valor"])) {
                            $preco_porcentagem = $preco / 100;
                            $preco = $preco + ($preco_porcentagem * $adicional_preco_peca["valor"]);
                        }
                        break;
                }
            }

            $this->update("tbl_os_item")
                 ->setCampos(array("preco" => number_format($preco, 2, ".", "")))
                 ->addWhere(array("os_item" => $peca["os_item"]));

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao gravar preço da peça na OS Item : ".$peca["os_item"]);
            } else {
                $total += $preco;
            }
        }

        $this->update("tbl_os")
             ->setCampos(array("pecas" => number_format($total, 2, ".", "")))
             ->addWhere(array("os" => $os));

        if (!$this->prepare()->execute()) {
            throw new \Exception("Erro ao gravar o total das peças na OS : {$this->_sua_os}");
        }

        return $this;
    }


    /**
     * Pega o valor das pe&ccedil;as da OS
     *
     * @param integer $os
     * @return float
     */
    public function getValorPecas($os = null) {
        if (empty($os)) {
            $os = $this->_os;
        }

        $this->select()
             ->setCampos(array("pecas AS valor_pecas"))
             ->addWhere(array('tbl_os.os' => $os))
             ->addWhere(array('tbl_os.fabrica' => $this->_fabrica));

        if (!$this->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar o valor das peças da OS : {$this->_sua_os}");
        }

        $res = $this->getPDOStatement()->fetch();

        return $res["valor_pecas"];
    }

    public function verificaSolucao($os = null) {

        if (empty($os)) {
            $os = $this->_os;
        }

        $sql = "SELECT defeito_constatado_reclamado FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND solucao IS NOT NULL";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            if($this->_fabrica <> 148){
                return false;
            }else{
                return true;
            }
        } else {
            return false;
        }

    }

    public function verificaOsTecnica($os = null) {

        if (empty($os)) {
            $os = $this->_os;
        }

        $this->select("tbl_os")
                ->setCampos(array("tbl_os.os"))
                ->addJoin(array("tbl_tipo_atendimento" => "ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento"))
                ->addWhere(array("tbl_os.os" => $os ))
                ->addWhere(("tbl_tipo_atendimento.entrega_tecnica IS TRUE"))
                ->addWhere(array("tbl_os.fabrica" => $this->_fabrica ));

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao verificar se a OS é de Entrega Técnica - OS : {$this->_sua_os}");
            }

            if ($this->getPDOStatement()->rowCount() == 0) {
                return false;
            } else {
                if($this->_fabrica <> 148){
                    return true;
                }else{
                    return false;
                }
            }
    }

	public function verificaOsFabrica($con)
    {
        $sql = "SELECT os FROM tbl_os WHERE os = {$this->_os} AND fabrica = {$this->_fabrica}";

        if (!empty($con)) {
            $res = pg_query($con, $sql);
        } else {
            $pdo = $this->getPDO();

            $query = $pdo->query($sql);
            $res = $query->fetch(\PDO::FETCH_ASSOC);
        }

        return ($res) ? true : false;

    }

    public function verficaOsRevisaoProduto($con){
        $sql = "SELECT COUNT(os_produto) AS produtos FROM tbl_os_produto WHERE os = {$this->_os}";
        $res = pg_query($con, $sql);

        $produtos = pg_fetch_result($res, 0, "produtos");

        if ($produtos > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function verificaOsIntervencao($os = null, $fabrica = null, $fechamento = false, $admin = 0){

        $pdo = $this->getPDO();

        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }

        if(empty($os)){
            $os = $this->_os;
        }

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
            $query = $pdo->query($sql);
        
            if ($query->rowCount() > 0) {
                $res = $query->fetchAll(\PDO::FETCH_ASSOC);

                for($i = 0; $i < count($res); $i++) {
                    $auditoria_status = $res[$i]["auditoria_status"];
                    $observacao = $res[$i]["observacao"];

                    $cancelaOS = \Posvenda\Regras::get("cancelaOS", "pedido_garantia", $this->_fabrica);

                    $sqlAuditOS = "
                        SELECT
                            tbl_auditoria_os.auditoria_os,
                            tbl_auditoria_os.liberada,
                            tbl_auditoria_os.reprovada,
                            tbl_auditoria_os.observacao,
                            tbl_auditoria_os.cancelada
                        FROM tbl_auditoria_os
                        WHERE tbl_auditoria_os.os = {$os}
                        AND tbl_auditoria_os.auditoria_status = {$auditoria_status}
                        AND fn_retira_especiais(tbl_auditoria_os.observacao) = fn_retira_especiais('{$observacao}')
                        ORDER BY tbl_auditoria_os.data_input DESC
                        LIMIT 1
                    ";

                    $queryAuditOS = $pdo->query($sqlAuditOS);
                    $resAuditOS = $queryAuditOS->fetch(\PDO::FETCH_ASSOC);

                    if ($this->_fabrica == 156) {
                        if (empty($resAuditOS['liberada']) and empty($resAuditOS['reprovada'])) {
                            return utf8_encode("OS {$this->_sua_os} em Auditoria: {$observacao}");
                        }
                    } else {

                        if($this->_fabrica == 167 && $fechamento == true ){
                            if ($resAuditOS['reprovada'] == "") {
                                if ($resAuditOS['liberada'] == "") {
                                    return utf8_encode("OS {$this->_sua_os} em Auditoria: {$observacao}");
                                }
                            }
                        }else{
                            if ((in_array($this->_fabrica, array(158)) || $cancelaOS) && $resAuditOS["cancelada"] != "") {
                                continue;
                            }

                            if($this->_fabrica == 171 && strtolower($observacao) == 'auditoria de fechamento') {
                                return;
                            } else {
                                if ($resAuditOS['liberada'] == "") {
									if (in_array($this->_fabrica, [35,160,177,184,200])) {
										if (!empty($resAuditOS['reprovada'])) {
											return;
										}
									}

                                    return utf8_encode("OS {$this->_sua_os} em Auditoria: {$observacao}");
                                }
                            }

                            if ($resAuditOS['reprovada'] != "" and $resAuditOS['liberada'] == "") {
                                return utf8_encode("OS {$this->_sua_os} reprovada da Auditoria: {$observacao}");
                            }
                        }
                    }
                }
            }
        }else{
        	/* KM */
            $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status=$fabrica AND os = {$os} AND status_os IN(98,99,100,101) ORDER BY data DESC LIMIT 1";
            $query = $pdo->query($sql);

            $res = $query->fetch(\PDO::FETCH_ASSOC);
            $status_os = $res['status_os'];

            if($status_os == 98){
                return "OS {$this->_sua_os} em auditoria de KM";
            }

            /* Reincidente newmaq */
            if ($this->_fabrica == 120) {
				$sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status=$fabrica AND os = {$os} AND status_os IN (67,19,139,155) ORDER BY data DESC LIMIT 1";
				$query = $pdo->query($sql);

				$res = $query->fetch(\PDO::FETCH_ASSOC);
				$status_os = $res['status_os'];

				if($status_os == 67){
					return "OS {$this->_sua_os} em auditoria de Reincidencia";
				}

            }

            /* Valores Adicionais */
            $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status=$fabrica AND os = {$os} AND status_os IN(171,172,173) ORDER BY data DESC LIMIT 1";
            $query = $pdo->query($sql);

            $res = $query->fetch(\PDO::FETCH_ASSOC);
            $status_os = $res['status_os'];

            if($status_os == 171){
                return "OS {$this->_sua_os} em auditoria de Valores Adicionais";
            }

            /* Pe&ccedil;as Excedentes */
            $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status=$fabrica AND os = {$os} AND status_os IN(118,185,187) ORDER BY data DESC LIMIT 1";
        	$query = $pdo->query($sql);

        	$res = $query->fetch(\PDO::FETCH_ASSOC);
        	$status_os = $res['status_os'];

        	if($status_os == 118){
        	    return "OS {$this->_sua_os} em auditoria de Peça Excedentes";
        	}

        	/* Pe&ccedil;a Crítica */
            $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status=$fabrica AND os = {$os} AND status_os IN(62,64) ORDER BY data DESC LIMIT 1";
        	$query = $pdo->query($sql);

        	$res = $query->fetch(\PDO::FETCH_ASSOC);
        	$status_os = $res['status_os'];

        	if($status_os == 62){
        	    return "OS {$this->_sua_os} em auditoria de Peça Crítica";
        	}

        	$auditorias_adicionais = \Posvenda\Regras::get("auditorias", "pedido_garantia", $this->_fabrica);

        	if (count($auditorias_adicionais) > 0) {
        	    foreach ($auditorias_adicionais as $key => $auditoria) {
        	        $status_auditoria = $auditoria["status"];
                	$status_aprovacao = $auditoria["status_aprovacao"];
        	        $auditoria_nome   = $auditoria["nome"];

			        $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status=$fabrica AND os = {$os} AND status_os IN(".implode(",", $status_auditoria).") ORDER BY data DESC LIMIT 1";

        	        $query = $pdo->query($sql);
                    $res = $query->fetch(\PDO::FETCH_ASSOC);

        	        if (is_array($res) && count($res) > 0) {
                	    $status_os = $res['status_os'];

    			        if(is_array($status_aprovacao)){
        				    if(!in_array($status_os,$status_aprovacao)){
        					   return "OS {$this->_sua_os} em auditoria de {$auditoria_nome}";
        				    }
    			        }else{
            				if($status_os != $status_aprovacao){
                                if($fabrica == 145 AND $status_os == 201){
                                    return utf8_encode("OS {$this->_sua_os} reprovada da auditoria, não poderá ser fechada.");
                                }else{
                                    return "OS {$this->_sua_os} em auditoria de {$auditoria_nome}";
                                }
            				}
    			        }
        	        }
        	    }
        	}
        }
    	return false;
    }

    public function verificaPedidoPecasNaoFaturadasOS($con,$limite=null,$area_admin=false)
    {

        $campo_aux = "";

        if($area_admin == true){ //hd_chamado=2902321
            return false;
        }

        if($limite !== null){
            $auxSql = " AND (tbl_os_item.digitacao_item > (CURRENT_TIMESTAMP - INTERVAL '$limite days') AND (tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada+tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor)) > 0 )";
        }elseif($this->_fabrica != 151){
			$auxSql = " AND (tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada+tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor)) > 0 ";
		}

        if ($this->_fabrica == 158) {
            $whereTipoPedido = "AND tbl_tipo_pedido.codigo NOT IN ('BON', 'BON-GAR')";
        }

        if (in_array($this->_fabrica, [164,193])) {
            $campo_aux = ", tbl_servico_realizado.descricao ";
            $joinServicoRealizado = " JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado ";
        }

        $sql = "
            SELECT
		tbl_os.os,
		tbl_os.consumidor_revenda
                $campo_aux
            FROM    tbl_os
            JOIN    tbl_os_produto USING(os)
            JOIN    tbl_os_item USING(os_produto)
            {$joinServicoRealizado}
            LEFT JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
            LEFT JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
            LEFT JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
            WHERE   tbl_os.os = {$this->_os}
            $auxSql
            AND     tbl_os.fabrica = {$this->_fabrica}
            {$whereTipoPedido}
            ";
        $res = pg_query($con, $sql);
		$resultado = pg_num_rows($res);

        if(pg_num_rows($res) > 0 && $this->_fabrica != 161){

            if(in_array($this->_fabrica, array(164,193))){

                $desc_servico_realizado = pg_fetch_result($res, 0, "descricao");

                if($desc_servico_realizado == "Troca de Peça (Usando Estoque)" || $desc_servico_realizado == "Troca de Peça (usando estoque)"){
                    $tem_pedido = true;
                }else{
                    $status = "A OS {$this->_sua_os} não pode ser fechada, pois o pedido de peça está pendente 1#";
                    return $status;
                }

            }else{
		
		    $tipo_os = trim(pg_fetch_result($res, 0, "consumidor_revenda"));

		    if(($this->_fabrica == 178 AND !in_array($tipo_os,array('R','S'))) || $this->_fabrica != 178){

                	$status = "A OS {$this->_sua_os} não pode ser fechada, pois o pedido de peça está pendente 2#";
			return $status;
		    }

            }

        }else if($this->_fabrica != 151){
            if (in_array($this->_fabrica,[161,178,190])) {
                $campos = ", tbl_servico_realizado.descricao, tbl_os_item.pedido";
            }
            if ($this->_fabrica != 161) {
				$where = " AND tbl_os_item.pedido_item IS NULL";
				$tem_pedido = true;
            }
	    if ($this->_fabrica == 190) {

		$where = " AND tbl_servico_realizado.peca_estoque IS NOT TRUE";
	    }
	    $sql = "SELECT tbl_os.os,
		    tbl_os.consumidor_revenda
                    $campos
                    FROM tbl_os
                    JOIN tbl_os_produto USING(os)
                    JOIN tbl_os_item USING(os_produto)
                    LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
                    JOIN tbl_servico_realizado ON (tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado)
                    WHERE tbl_os.os = {$this->_os}
                    AND tbl_servico_realizado.gera_pedido IS TRUE
                    $where
                    AND tbl_os_troca.os_troca IS NULL
                    AND tbl_os.fabrica = {$this->_fabrica}";
            $res = pg_query($con, $sql);
            
            if(pg_num_rows($res) > 0){

		$tem_pedido = false;

		if (in_array($this->_fabrica,[161,178,190])) {
		   $tipo_os = pg_fetch_result($res,0,'consumidor_revenda');
                    $descricao = pg_fetch_all_columns($res,2);
                    $pedidos = pg_fetch_all_columns($res,3);
                    $aux = array_unique($descricao);

		    if(strlen($pedidos[0]) > 0) {
		    	$tem_pedido = true;
		    }

                    if (in_array($this->_fabrica,[178,190])) {
                        if (count($aux) == 1 && strpos($aux[0],'estoque Posto')) {
                            return false;
                        }
                    }else{
                        if (count($aux) == 1 && strpos($aux[0],'Usando Estoque')) {
                            return false;
                        }
                    }
		}

		if($resultado == 0 and !$tem_pedido) {

                    if(in_array($this->_fabrica, array(164,193))){

                        $sql = "SELECT
                                    tbl_servico_realizado.descricao, tbl_servico_realizado.codigo_servico
                                FROM tbl_os
                                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                                INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                                WHERE
                                    tbl_os.os = {$this->_os}
                                    AND tbl_os.fabrica = {$this->_fabrica}";
                        $res = pg_query($con, $sql);

                        if(pg_num_rows($res) > 0){

                            $desc_servico_realizado = pg_fetch_result($res, 0, "descricao");
                            $codi_servico_realizado = pg_fetch_result($res, 0, "codigo_servico");

                            if($desc_servico_realizado == "Troca de Peça (Usando Estoque)" || $codi_servico_realizado == "troca_peca_estoque"){
                                $tem_pedido = true;
                            }else{
                                $status = "A OS {$this->_sua_os} não pode ser fechada, pois o pedido de peça está pendente 3#";
                                return $status;
                            }

                        }else{
                            $status = "A OS {$this->_sua_os} não pode ser fechada, pois o pedido de peça está pendente 4#";
                            return $status;
                        }

                    }else{

			    if(($this->_fabrica == 178 AND !in_array($tipo_os,array('R','S'))) || $this->_fabrica != 178){
                        	$status = "A OS {$this->_sua_os} não pode ser fechada, pois o pedido de peça está pendente 5#";
				return $status;
			    }

                    }

		}else{
		   return false;
		}

            }
        }

        if($this->_fabrica == 151){ //hd_chamado=2787856
            $sql_m = "SELECT  tbl_os.os
                        FROM    tbl_os
                        JOIN    tbl_os_produto USING(os)
                        JOIN    tbl_os_item USING(os_produto)
                        LEFT JOIN    tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                        WHERE   tbl_os.os = {$this->_os}
                        AND (tbl_os_item.digitacao_item < (CURRENT_TIMESTAMP - INTERVAL '$limite days'))
						AND tbl_pedido_item.pedido isnull
                        AND     tbl_os.fabrica = {$this->_fabrica}
                    ";
            $res_m = pg_query($con, $sql_m);

            if(pg_num_rows($res_m) > 0){
                $insert_m = "INSERT INTO tbl_os_status (
                                os,
                                status_os,
                                data,
                                observacao
                            ) values (
                                {$this->_os},
                                240,
                                current_timestamp,
                                'Fechada sem reparo'
                            )";
                $res_m = pg_query ($con, $insert_m);
            }
        }
        return false;
    }

    public function verificaPedidoOS($con, $os)
    {
        $sql = "SELECT peca
                    FROM tbl_os
                        JOIN tbl_os_produto USING(os)
                        JOIN tbl_os_item USING(os_produto)
                    WHERE fabrica = {$this->_fabrica}
                        AND tbl_os.os = {$os}";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            return true;
        } else {
            return utf8_encode("OS {$os} não possui peças lançadas!");
        }
    }

    public function verificaPedidoPedentesTroca($con,$area_admin = false)
    {

        if($area_admin == true){ //hd_chamado=2902321
            return false;
        }

        $sql = "SELECT tbl_os_troca.os_troca
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                INNER JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os_troca.peca = tbl_os_item.peca
                INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
				LEFT JOIN tbl_auditoria_os ON tbl_os.os = tbl_auditoria_os.os and tbl_auditoria_os.auditoria_status = 3
                WHERE tbl_os.fabrica = {$this->_fabrica}
                AND tbl_os.os = {$this->_os}
                AND tbl_os_troca.gerar_pedido IS TRUE
                AND tbl_os_item.pedido IS NULL
                AND tbl_os_item.pedido_item IS NULL
                AND tbl_servico_realizado.gera_pedido IS TRUE
				AND tbl_servico_realizado.troca_produto IS TRUE
				AND (tbl_auditoria_os.bloqueio_pedido is not true or tbl_auditoria_os.os isnull)";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            $status = "A OS {$this->_sua_os} não pode ser fechada, pois o pedido de troca está pendente";
            return $status;
        }

        return false;

    }

    public function verificaDefeitoConstatado($con, $os = null){
        $finaliza_os_sem_defeito_constatado = \Posvenda\Regras::get("finaliza_sem_defeito_constatado", "ordem_de_servico", $this->_fabrica);
        $os_defeito_reclamado_constatado = \Posvenda\Regras::get("os_defeito_reclamado_constatado", "ordem_de_servico", $this->_fabrica);

        if (empty($os)) {
            $os = $this->_os;
        }

        if(isset($finaliza_os_sem_defeito_constatado)){

            $sqlTipoAtendimento = "SELECT tipo_atendimento FROM tbl_os WHERE os = {$os};";
            $res = pg_query($con, $sqlTipoAtendimento);

            $tipo_atendimento = pg_result($res, 0,'tipo_atendimento');

            if(in_array($tipo_atendimento, $finaliza_os_sem_defeito_constatado['tipo_atendimento'])){
                return true;
            }
        }
        
        if(in_array($this->_fabrica , array(131,143,148,152,156,158,160,169,170,180,181,182,183,191,193,194,195,198)) or $os_defeito_reclamado_constatado){

            $sql = "SELECT defeito_constatado FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} and defeito_constatado notnull";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                return true;
            } else {
	            $sql = "SELECT defeito_constatado FROM tbl_os WHERE os = {$os} and defeito_constatado NOTNULL";
				$res = pg_query($con, $sql);
				if (pg_num_rows($res) > 0) {
	                return true;
		        } else {
	                return false;
				}
            }

        }else if(in_array($this->_fabrica,array(138, 142, 145, 177, 178))){

	    if($this->_fabrica == 177){
		$condCausa = " AND causa_defeito IS NULL";
	    }

            $sql = "SELECT os_produto FROM tbl_os_produto WHERE os = {$os} AND defeito_constatado IS NULL $condCausa";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) == 0) {
                return true;
            } else {
                return false;
            }

        } elseif (in_array($this->_fabrica, [186])) {
            $sql = "SELECT o.os
            FROM tbl_os o
            JOIN tbl_os_defeito_reclamado_constatado odrc ON odrc.os = o.os
            WHERE o.os = {$os}
            AND o.fabrica = {$this->_fabrica}";

            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0 && strlen(pg_last_error()) === 0) {
                return true;
            } else {
                return false;
            }
        } else{

            $sql = "SELECT os FROM tbl_os WHERE fabrica = {$this->_fabrica} AND os = {$os} AND defeito_constatado IS NOT NULL";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function verificaTipoAtendimento($con, $os = null){
        
        if (empty($os)) {
            $os = $this->_os;
        }

        $sql = "SELECT os FROM tbl_os WHERE fabrica = {$this->_fabrica} AND os = {$os} AND tipo_atendimento IS NOT NULL";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            return true;
        } else {
            return false;
        }        
    }

    public function verificaPecaLote($con){

        $sql = "
            SELECT tbl_peca.referencia,
                tbl_peca.descricao
            FROM tbl_os
            JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                AND tbl_os_item.fabrica_i = {$this->_fabrica}
            JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
            WHERE tbl_os.os = {$this->_os}
            AND tbl_os.fabrica = {$this->_fabrica}
            AND tbl_os_item.servico_realizado IS NULL ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0){
            return "Erro ao finalizar a OS: ".$this->_sua_os." . Favor informar serviço realizado da peça";
        }

        $sql = "
            SELECT tbl_peca.referencia,
                tbl_peca.descricao
            FROM tbl_os
            JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                AND tbl_os_item.fabrica_i = {$this->_fabrica}
            JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica} and tbl_peca.produto_acabado is not true
            JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_os_item.servico_realizado
            AND tbl_servico_realizado.troca_de_peca = 't'
            WHERE tbl_os.os = {$this->_os}
            AND tbl_os.fabrica = {$this->_fabrica}
            AND JSON_FIELD('lote', tbl_peca.parametros_adicionais) = 't'
            AND tbl_os_item.peca_serie IS NULL ";

        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0){
            return "Erro ao finalizar a OS: ".$this->_sua_os." . Favor preencher o campo LOTE NOVA PEÇA";
        }else{
            return true;
        }
    }

    public function verificaDefeitoPecaSemDefeito($con){
	$sql = "
		SELECT oi.os_item
		FROM tbl_os o
		INNER JOIN tbl_os_produto op ON op.os = o.os
		INNER JOIN tbl_os_item oi ON oi.os_produto = op.os_produto
		INNER JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = {$this->_fabrica}
		WHERE o.fabrica = {$this->_fabrica}
		AND o.os = {$this->_os}
		AND (
			sr.gera_pedido IS TRUE
			OR (
				sr.ativo IS TRUE
				AND sr.gera_pedido IS NOT TRUE
			)
		)
	";

    $res = pg_query($con, $sql);
	if (!pg_num_rows($res)) {
        	$sql = "
            		SELECT
                		tbl_os_defeito_reclamado_constatado.defeito_constatado,
                		tbl_os_defeito_reclamado_constatado.defeito,
                        tbl_tipo_atendimento.grupo_atendimento
            		FROM tbl_os_defeito_reclamado_constatado
            		JOIN tbl_defeito_constatado USING(defeito_constatado)
            		JOIN tbl_os_produto USING(os)
            		LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.fabrica = {$this->_fabrica}
            		LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            		AND tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado 
            		AND (
                		tbl_servico_realizado.gera_pedido IS TRUE 
                		OR (
                    			tbl_servico_realizado.ativo IS TRUE 
                    			AND tbl_servico_realizado.gera_pedido IS NOT TRUE
                		)
            		) 

                     JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
                     JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento 
           		WHERE tbl_os_defeito_reclamado_constatado.os = {$this->_os}
        		AND tbl_os_defeito_reclamado_constatado.fabrica = {$this->_fabrica}
                AND 
                 (tbl_defeito_constatado.lista_garantia = 'sem_defeito' OR tbl_tipo_atendimento.grupo_atendimento = 'R')

            		AND tbl_os_item.os_item IS NULL
        	";
            $res = pg_query($con, $sql);
     

        	if (pg_num_rows($res) > 0) {
			$contItens = pg_num_rows($res);

			$contErro = 0;

			for ($i = 0; $i < $contItens; $i++) {
				$defeito = pg_fetch_result($res, $i, 'defeito');
                    
                    		if (empty($defeito)){
                        		$contErro++;
                    		}
                	}

                	if ($contItens == $contErro) {
                    		$retorno = "Erro ao Finalizar a OS ".$this->_os." É necessário lançar defeito da Peça.";
                	} else {
                    		$retorno = true;
                	}

			return $retorno;
		} else {
			return "Erro ao finalizar a OS ".$this->_os." é necessário lançar defeito da peça.";
		}
        } else {
            $sql = "
                SELECT
                    tbl_os_item.defeito AS defeito_peca
                FROM tbl_os
                JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = 169
                WHERE tbl_os.fabrica = {$this->_fabrica}
                AND tbl_os.os = {$this->_os}
                AND (
                    tbl_servico_realizado.gera_pedido IS TRUE
                    OR (
                        tbl_servico_realizado.ativo IS TRUE
                        AND tbl_servico_realizado.gera_pedido IS NOT TRUE
                    )
                )
            ";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0){
                $contItens = pg_num_rows($res);
                $contErro = 0;
                
                for ($i = 0; $i < $contItens; $i++) {
                    $defeito_peca = pg_fetch_result($res, $i, 'defeito_peca');
                    if (empty($defeito_peca)){
                        $contErro++;
                    }
                }

                if ($contItens == $contErro) {
                    $retorno = "Erro ao Finalizar a OS ".$this->_os." É necessário lançar defeito da Peça.";
                } else {
                    $retorno = true;
                } 

                return $retorno;
            } else {
                return "Erro ao finalizar a OS ".$this->_os.", para OS sem peça é necessário lançar um defeito constatado sem defeito ou uma peça como ajuste.";
            }	
        }
    }

    /*
    * Einhell
    * Fun&ccedil;&atilde;o de Recompra
    */
    public function RecompraOS($con){

        $sql_tipo_posto = "select tbl_os.posto, tbl_posto_fabrica.tipo_posto, tbl_tipo_posto.descricao from tbl_os inner join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = $this->_fabrica inner join tbl_tipo_posto on tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto  where os = $this->_os and tbl_os.fabrica = $this->_fabrica ";
        $res = pg_query($con, $sql_tipo_posto);

        if($this->_fabrica == 200 || pg_num_rows($res)>0){

            $descricao = pg_fetch_result($res, 0, "descricao");

            if($this->_fabrica == 200 || $descricao == "Distribuidor"){

                $sql_item =  "select os_item, custo_peca, tbl_os_item.peca from  tbl_os_item
                              inner join tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
                              inner join tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                              where tbl_os_produto.os = $this->_os
                              AND tbl_servico_realizado.peca_estoque = 't' ";
                $res_item = pg_query($con, $sql_item);
                if(pg_num_rows($res_item)>0){
					$total = 0 ;
                    for($i=0; $i<pg_num_rows($res_item); $i++){
                        $peca = pg_fetch_result($res_item, $i, peca);
                        $os_item = pg_fetch_result($res_item, $i, "os_item");

                        $sql_tabela_item = "SELECT preco FROM tbl_tabela_item
                                        inner join tbl_tabela on tbl_tabela.tabela = tbl_tabela_item.tabela
                                        where tbl_tabela.fabrica = $this->_fabrica
                                         and tbl_tabela_item.peca = $peca
                                         and UPPER(tbl_tabela.descricao) = 'RECOMPRA' ";

                        $res2 = pg_query($con, $sql_tabela_item);
                        if(pg_num_rows($res2) > 0){
                            $preco = pg_fetch_result($res2, 0, "preco");
                            $sql_update = "UPDATE tbl_os_item SET custo_peca = '$preco' WHERE os_item = $os_item";
                            $res_update = pg_query($con, $sql_update);
							$total += $preco;

                        }
					}
					$sql_update2 = "UPDATE tbl_os SET pecas = '$total' WHERE os = $this->_os";
                    $res_update2 = pg_query($con, $sql_update2);
                }
            }
        }
    }

    public function finalizaOS($con, $origem = null)
    {

        $sql = "SELECT  tbl_os_item.os_item ,
                                tbl_os_item.os_produto,
                                tbl_os_item.qtde,
                                tbl_os_item.peca,
								tbl_os_item.pedido,
                                tbl_servico_realizado.gera_pedido ,
                                tbl_servico_realizado.troca_de_peca ,
                                tbl_pedido_item.preco,
								tbl_pedido_item.tabela,
								tbl_pedido_item.qtde_cancelada,
                                tbl_peca.referencia
                            FROM tbl_os_item
                            INNER JOIN tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
                            INNER JOIN tbl_os on tbl_os_produto.os = tbl_os.os
                            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                            LEFT JOIN tbl_pedido_item on tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                            INNER JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                            WHERE tbl_os.os = {$this->_os}
                                AND tbl_os.fabrica = {$this->_fabrica} ";
        $res = pg_query($con, $sql);
        $itens = pg_fetch_all($res);

        $preco_peca = \Posvenda\Regras::get("preco_peca", "mao_de_obra", $this->_fabrica);

        if (!empty($preco_peca)) {
            $tabela_preco_peca = \Posvenda\Regras::get("tabela_preco_peca", "mao_de_obra", $this->_fabrica);

            if (!empty($tabela_preco_peca)) {
                $sqlTabela = "
                    SELECT tabela FROM tbl_tabela WHERE fabrica = {$this->_fabrica} AND LOWER(descricao) = LOWER('{$tabela_preco_peca}')
                ";
                $resTabela = pg_query($con, $sqlTabela);

                if (pg_num_rows($resTabela) > 0) {
                    $tabela_preco_peca = pg_fetch_result($resTabela, 0, "tabela");
                } else {
                    throw new \Exception("Erro ao atualizar valores das peças da OS {$this->_sua_os}");
                }
            }
        }

        foreach ($itens as $key => $item) {
            if (!empty($tabela_preco_peca)) {
                unset($updatePrecoPeca);

                $sqlPrecoPeca = "
                    SELECT preco FROM tbl_tabela_item WHERE tabela = {$tabela_preco_peca} AND peca = {$item['peca']}
                ";
                $resPrecoPeca = pg_query($con, $sqlPrecoPeca);

				if (!pg_num_rows($resPrecoPeca)) {
					if($item['qtde'] > $item['qtde_cancelada'] and !empty($item['pedido'])) {
						throw new \Exception(("Erro ao finalizar a OS {$this->_sua_os}, a peça {$item['referencia']} está sem preço"));
					}
                } else {
                    $preco_peca = pg_fetch_result($resPrecoPeca, 0, "preco");
                    $updatePrecoPeca = ", preco = {$preco_peca}";
                }
            }

            if($item["gera_pedido"] == "t" and $item["troca_de_peca"] == "t"){
                $adicional_preco_peca = \Posvenda\Regras::get("adicional_preco_peca", "mao_de_obra", $this->_fabrica);

                if (!empty($adicional_preco_peca)) {
                    switch ($adicional_preco_peca["formula"]) {
                        case "+%":
                            if (!empty($adicional_preco_peca["valor"])) {
                                $preco_porcentagem = $item['total_item'] / 100;
                                $preco = $item['preco'] + ($preco_porcentagem * $adicional_preco_peca["valor"]);
                            }
                        break;
                    }
                }else{
                    $preco = $item['preco'] ;
                }

                if(empty($preco)) {
                    $preco = 0 ;
                }

                $sql = "UPDATE tbl_os_item  SET custo_peca = {$preco} {$updatePrecoPeca} WHERE os_item = {$item['os_item']}";
                $res = pg_query($con,$sql);
            } elseif ($item["troca_de_peca"] == "t" and $item["gera_pedido"] == "f") {

                $sql = " SELECT tbl_produto.linha ,tbl_os.posto
                from tbl_os_produto
                INNER JOIN tbl_os on tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$this->_fabrica}
                INNER JOIN tbl_produto on tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i = $this->_fabrica
                WHERE tbl_os_produto.os_produto = {$item['os_produto']} ";

                $res = pg_query($con,$sql);

                $linha = pg_fetch_result($res, 0, "linha");
                $posto = pg_fetch_result($res, 0, "posto");

                $sql = "SELECT tbl_posto_linha.tabela
                            from tbl_posto_linha
                            INNER JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
                            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                            WHERE tbl_posto_linha.linha= {$linha} AND tbl_posto_fabrica.posto = {$posto}";
                $res = pg_query($con,$sql);
                $tabela = pg_fetch_result($res, 0, "tabela");

                $sql = "SELECT tbl_tabela_item.preco
                        FROM tbl_tabela_item
                        INNER JOIN tbl_tabela on tbl_tabela.tabela = tbl_tabela_item.tabela and tbl_tabela.fabrica = {$this->_fabrica}
                        WHERE tbl_tabela_item.peca = {$item['peca']}
                        AND tbl_tabela.tabela = {$tabela} ";
                $res = pg_query($con,$sql);
                $preco = pg_fetch_result($res, 0, "preco");

                if(!empty($preco)) {

                    $adicional_preco_peca = \Posvenda\Regras::get("adicional_preco_peca", "mao_de_obra", $this->_fabrica);

                    if (!empty($adicional_preco_peca)) {
                        switch ($adicional_preco_peca["formula"]) {
                            case "+%":
                                if (!empty($adicional_preco_peca["valor"])) {
                                    $preco_porcentagem = $preco / 100;
                                    $preco = $preco + ($preco_porcentagem * $adicional_preco_peca["valor"]);
                                }
                            break;
                        }
                    }
                    $total = $preco;
                    if(empty($total)) {
                        $total = 0 ;
                    }
                    $sql = "UPDATE tbl_os_item set custo_peca = {$total} {$updatePrecoPeca} where os_item ={$item['os_item']} ";
                    $res = pg_query($con,$sql);

                }
            }

            if(strlen(pg_last_error()) > 0){
                throw new \Exception("Erro ao atualizar mão de obra da OS : {$this->_sua_os} -- ".pg_last_error());
            }
        }

        $updateOrigem = null;
        if (!is_null($origem)) {
            $updateOrigem = "UPDATE tbl_os_campo_extra SET origem_fechamento = '$origem' WHERE os = {$this->_os};";
        }
        $sqlConserto = "SELECT data_conserto, data_fechamento
                        FROM tbl_os
                        WHERE tbl_os.os = {$this->_os}
                        AND tbl_os.fabrica = {$this->_fabrica} ";
        $resConserto = pg_query($con, $sqlConserto);

        if (pg_num_rows($resConserto) > 0){
            $xdata_conserto   = pg_fetch_result($resConserto, 0, 'data_conserto');
            $xdata_fechamento = pg_fetch_result($resConserto, 0, 'data_fechamento');
        }

        if (empty($xdata_conserto)){
            $data_conserto = ",data_conserto = CURRENT_TIMESTAMP";
        }

        if (empty($xdata_fechamento)){
            $data_fechamento = ", data_fechamento = CURRENT_TIMESTAMP";
        }

        $integracao = \Posvenda\Regras::get("integracao", "ordem_de_servico", $this->_fabrica);

        $updateBaixada = null;
        if ($integracao === true) {
            $updateBaixada = "
                UPDATE tbl_os_extra SET
                    baixada = NULL
                WHERE os = {$this->_os};
            ";
        }

        $sql = "
            UPDATE tbl_os
            SET finalizada = CURRENT_TIMESTAMP {$data_conserto} {$data_fechamento}
            WHERE os = {$this->_os} AND fabrica = {$this->_fabrica};

            {$updateOrigem}

            {$updateBaixada}
            ";
        $res = pg_query($con, $sql);

        if($res){
            return true;
        }else{
            return false;
        }
    }

    public function verificaAuditoriaEstoqueReprovada($os) {

        $pdo = $this->getPDO();

        if ($this->verificaAuditoriaEstoque($os)) {

            $sql = "SELECT tbl_pedido.pedido
                    FROM tbl_os_produto
                    JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                    JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
                    AND tbl_pedido.status_pedido <> 14
                    WHERE tbl_os_produto.os = {$os}";
            $query = $pdo->query($sql);

            if ($query->rowCount() == 0) {
                return false;
            }

        }

        return true;

    }

    public function verificaAuditoriaEstoque($os) {

        $pdo = $this->getPDO();

        $sql = "SELECT auditoria_os
                FROM tbl_auditoria_os
                WHERE observacao ILIKE '%usando estoque'
                AND os = {$os}
                AND reprovada IS NOT NULL";
        $query = $pdo->query($sql);

        if ($query->rowCount() > 0) {

            return true;

        }

        return false;

    }

    public function selectOsGarantia($param, $os = null, $estados = null, $porPeca = false)
    {   
        $pdo = $this->getPDO();

    	if (!$nao_verifica_estoque) {
    		$wherePecaEstoque = "AND tbl_servico_realizado.peca_estoque IS NOT TRUE";
    	}

        if (in_array($this->_fabrica, array(171,176))) {
            $where_peca_referencia = "AND tbl_peca.referencia_fabrica IS NOT NULL";
        }

        if (in_array($this->_fabrica, array(151))) {
            $where_status = " AND tbl_os.status_checkpoint != 54";
        }

        if ($verifica_os_pedido_obrigatorio == true) {
            $whereServicoRealizado = "AND (tbl_servico_realizado.gera_pedido IS TRUE OR tbl_os_extra.obs_adicionais::jsonb->>'gera_pedido_obrigatorio' = 'true')";
        } else {
            $whereServicoRealizado = 'AND tbl_servico_realizado.gera_pedido IS TRUE';
        }

        if ($porPeca == true) {
            $sql = "SELECT
                            tbl_os.os,
                            tbl_os.posto,
                            tbl_produto.linha,
                            tbl_os_item.peca,
                            tbl_os_item.qtde,
                            tbl_os_item.os_item
                        FROM tbl_os
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                        INNER JOIN tbl_produto ON (tbl_produto.produto = tbl_os.produto or tbl_os_produto.produto = tbl_produto.produto) AND tbl_produto.fabrica_i = {$this->_fabrica}
                        INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                        INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                        JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
                        {$join_tbl_os_troca}
                        {$join_tbl_extrato_lancamento}
                        {$join_tipo_atendimento}
                        WHERE tbl_os.fabrica = {$this->_fabrica}
                        AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
                        AND tbl_servico_realizado.gera_pedido IS TRUE
                        {$wherePecaEstoque}
                        AND tbl_os.excluida IS NOT TRUE
                        AND tbl_peca.produto_acabado IS NOT TRUE
                        AND tbl_os_item.pedido IS NULL
                        /*AND tbl_posto_fabrica.posto NOT IN (6359)*/
                        {$where_tbl_os_troca}
                        {$where_tbl_os_numero}
                        {$wherePostoInterno}
                        {$where_tipo_atendimento}
                        {$where_tbl_extrato_lancamento}
                        {$where_peca_referencia}
                        {$where_status}";
        } elseif($param == "posto"){

             if (count($estados) > 0) {

                $estadosPostos = implode("','", $estados);
                $whereEstados  = " AND tbl_posto.estado IN ('$estadosPostos')";

             }   

             $sql = "SELECT DISTINCT 
                            tbl_os.posto
                        FROM tbl_os
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                        INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                        INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
                        INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                        JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
                        {$join_tbl_os_troca}
                        {$join_tbl_extrato_lancamento}
                        WHERE tbl_os.fabrica = {$this->_fabrica}
                        AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
                        {$whereServicoRealizado}
			            {$wherePecaEstoque}
                        AND tbl_os.excluida IS NOT TRUE
                        AND tbl_peca.produto_acabado IS NOT TRUE
                        AND tbl_os_item.pedido IS NULL
                        /*AND tbl_posto_fabrica.posto NOT IN (6359)*/
                        {$where_tbl_os_troca}
                        {$where_tbl_os_numero}
                        {$wherePostoInterno}
                        {$where_tbl_extrato_lancamento}
                        {$whereEstados}";
        } elseif($param == "marca") {

            $sql = "SELECT
                            DISTINCT tbl_os.posto, tbl_produto.marca
                        FROM tbl_os
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                        INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                        INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                        JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$this->_fabrica}
                        {$join_tbl_os_troca}
                        {$join_tbl_extrato_lancamento}
                        WHERE tbl_os.fabrica = {$this->_fabrica}
                        AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
                        AND tbl_servico_realizado.gera_pedido IS TRUE
			            {$wherePecaEstoque}
                        AND tbl_os.excluida IS NOT TRUE
                        AND tbl_peca.produto_acabado IS NOT TRUE
                        AND tbl_os_item.pedido IS NULL
                        /*AND tbl_posto_fabrica.posto NOT IN (6359)*/
                        {$where_tbl_os_troca}
                        {$where_tbl_os_numero}
                        {$wherePostoInterno}
                        {$where_tbl_extrato_lancamento}";
        } elseif(empty($param) or $param == "os"){
            $sql = "SELECT
                            DISTINCT tbl_os.os,
                            tbl_os.posto,
                            tbl_produto.linha
                        FROM tbl_os
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                        INNER JOIN tbl_produto ON (tbl_produto.produto = tbl_os.produto or tbl_os_produto.produto = tbl_produto.produto) AND tbl_produto.fabrica_i = {$this->_fabrica}
                        INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                        INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                        JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
                        {$join_tbl_os_troca}
                        {$join_tbl_extrato_lancamento}
                        {$join_tipo_atendimento}
                        WHERE tbl_os.fabrica = {$this->_fabrica}
                        AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
			            AND tbl_servico_realizado.gera_pedido IS TRUE
			            {$wherePecaEstoque}
                        AND tbl_os.excluida IS NOT TRUE
                        AND tbl_peca.produto_acabado IS NOT TRUE
                        AND tbl_os_item.pedido IS NULL
                        /*AND tbl_posto_fabrica.posto NOT IN (6359)*/
                        {$where_tbl_os_troca}
                        {$where_tbl_os_numero}
                        {$wherePostoInterno}
                        {$where_tipo_atendimento}
                        {$where_tbl_extrato_lancamento}
                        {$where_peca_referencia}
                        {$where_status}";
			$sql .= (!empty($os)) ? " and tbl_os.os = $os " : "";
        } else if($param == "troca-produto" || $param == "troca-produto-posto"){
            $sql = "SELECT
                    DISTINCT tbl_os.os,
                    tbl_os.posto,
		    tbl_os_item.digitacao_item,
                    tbl_os_campo_extra.campos_adicionais,
		    tbl_produto.linha
            FROM tbl_os
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
	    INNER JOIN tbl_produto ON (tbl_produto.produto = tbl_os.produto or tbl_os_produto.produto = tbl_produto.produto) AND tbl_produto.fabrica_i = {$this->_fabrica}
            INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
            INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
            LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os_troca.produto = tbl_os_produto.produto
            LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = {$this->_fabrica}
            {$join_tbl_extrato_lancamento}
            WHERE tbl_os.fabrica = {$this->_fabrica}
            AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
            AND tbl_servico_realizado.gera_pedido IS TRUE
            AND tbl_servico_realizado.peca_estoque IS NOT TRUE
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_peca.produto_acabado IS TRUE
            AND tbl_os_item.pedido IS NULL
            AND tbl_os_troca.os_troca IS NOT NULL
            AND tbl_os_troca.gerar_pedido IS TRUE
            {$where_tbl_os_numero}
            {$wherePostoInterno}
            {$where_tbl_extrato_lancamento}";
        } elseif ($param == "estoque") {
            $sql = "SELECT DISTINCT tbl_os.posto
                        FROM tbl_os
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                        INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                            AND tbl_os_item.pedido IS NULL
                            AND tbl_os_item.pedido_item IS NULL
                        INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                            AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
                            AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                            AND tbl_tipo_posto.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$this->_fabrica}
                        WHERE tbl_os.fabrica = {$this->_fabrica}
                            AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
                            AND tbl_servico_realizado.peca_estoque IS TRUE
                            AND tbl_os.excluida IS NOT TRUE
                            AND tbl_os.validada IS NOT NULL
                            AND tbl_peca.produto_acabado IS NOT TRUE
                            AND tbl_os_item.pedido IS NULL
                            AND tbl_tipo_posto.posto_interno IS NOT TRUE
                            AND tbl_tipo_posto.tipo_revenda IS NOT TRUE
                            AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
                            {$where_tbl_os_numero}";
        }
        
        $query = $pdo->query($sql);
	    $res = $query->fetchAll(\PDO::FETCH_ASSOC);

	    $auditoria_unica = \Posvenda\Regras::get("auditoria_unica", "pedido_garantia", $this->_fabrica);

        if(count($res) > 0){

            $os_pedido = array();

            for($i = 0; $i < count($res); $i++){

                if ($porPeca == true) {
                    $posto   = $res[$i]["posto"];
                    $os      = $res[$i]["os"];
                    $peca    = $res[$i]["peca"];
                    $qtde    = $res[$i]["qtde"];
                    $os_item = $res[$i]["os_item"];
                    $os_pedido[] = array("posto" => $posto, "os" => $os, "peca" => $peca, "qtde" => $qtde, "os_item" => $os_item);

                }elseif($param == "posto"){
                    $posto = $res[$i]["posto"];
                    $os    = $res[$i]["os"];
                    $os_pedido[] = array("posto" => $posto, "os" => $os);

                }elseif($param == "marca") {
                    $posto = $res[$i]["posto"];
                    $marca = $res[$i]["marca"];

                    $os_pedido[] = array("posto" => $posto, "marca" => $marca);
                }elseif(empty($param) or $param == "os"){

                    $os    = $res[$i]["os"];
                    $posto = $res[$i]["posto"];
                    $linha = $res[$i]["linha"];
                    if($auditoria_unica == true){

                        if($this->consultaAuditoriaOS($os)){
                            $os_pedido[] = array(
                                "os"    => $os,
                                "posto" => $posto,
                                "linha" => $linha
                            );
                        }
                    }else if($this->verificaOsIntervencaoGarantia($os)){
                        $os_pedido[] = array(
                            "os"    => $os,
                            "posto" => $posto,
                            "linha" => $linha
                        );
                    }
                }else if($param == "troca-produto"){
					$os    = $res[$i]["os"];
					$posto = $res[$i]["posto"];
					$linha = $res[$i]["linha"];
					$digitacao_item = $res[$i]["digitacao_item"];

					if ($auditoria_unica == true) {
						if($this->consultaAuditoriaOS($os,$digitacao_item)){
							$os_pedido[] = array(
								"os"    => $os,
								"posto" => $posto,
								"linha" => $linha
							);
						}
					} else if($this->verificaOsIntervencaoGarantia($os)){
						$os_pedido[] = array(
							"os"    => $os,
							"posto" => $posto,
							"linha" => $linha
						);
					}
                } elseif ($param == "troca-produto-posto") {
                    $os    = $res[$i]["os"];
                    $posto = $res[$i]["posto"];
                    $digitacao_item = $res[$i]["digitacao_item"];
                    $campos_adicionais= $res[$i]["campos_adicionais"];

                    if ($auditoria_unica == true) {
                        if($this->consultaAuditoriaOS($os,$digitacao_item)){
                            $os_pedido[$posto] = ["os" => $os, "campos_adicionais" => $campos_adicionais];
                        }
                    } else if($this->verificaOsIntervencaoGarantia($os)){
                        $os_pedido[$posto][] = ["os" => $os, "campos_adicionais" => $campos_adicionais];
                    }
                } elseif($param == "estoque"){

                    $posto = $res[$i]["posto"];

                    $os_pedido[] = array("posto" => $posto);

                    // $os_pedido[] = array(
                    //     "os"    => $os,
                    //     "posto" => $posto
                    // );
                }

            }
            return $os_pedido;

        }else{
            return false;
        }

    }

    public function selectOsGarantiaPostoInterno($param, $os = null,$estados = null, $manual = false)
    {

        $pdo = $this->getPDO();
        $verifica_tbl_os_troca = \Posvenda\Regras::get("verifica_tbl_os_troca", "pedido_garantia", $this->_fabrica);
        $nao_verifica_estoque = \Posvenda\Regras::get("nao_verifica_estoque", "pedido_garantia", $this->_fabrica);
        $fora_garantia_nao_gera = \Posvenda\Regras::get("fora_garantia_nao_gera", "pedido_garantia", $this->_fabrica);
        $os_com_credito_nao_gera_pedido = \Posvenda\Regras::get("os_com_credito_nao_gera_pedido", "ordem_de_servico", $this->_fabrica);
        $verifica_os_pedido_obrigatorio = \Posvenda\Regras::get("verifica_os_pedido_obrigatorio", "pedido_garantia", $this->_fabrica);

        if ($os_com_credito_nao_gera_pedido == true) {
            $join_tbl_extrato_lancamento = " LEFT JOIN tbl_extrato_lancamento ON tbl_os.os = tbl_extrato_lancamento.os AND tbl_extrato_lancamento.debito_credito = 'C' ";
            $where_tbl_extrato_lancamento = " AND tbl_extrato_lancamento.os IS NULL ";

        }

        if ($fora_garantia_nao_gera == true){
            $join_tipo_atendimento = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$this->_fabrica}";
            $where_tipo_atendimento = " AND LOWER(tbl_tipo_atendimento.descricao) != LOWER('Fora de Garantia') ";
        }

        if ($verifica_tbl_os_troca == true) {
            $join_tbl_os_troca  = "LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os_troca.produto = tbl_os_produto.produto";
            $where_tbl_os_troca = "AND tbl_os_troca.os_troca IS NULL";
        } else {
            $where_tbl_os_troca = "AND tbl_os.troca_garantia IS NULL AND tbl_os.troca_garantia_admin IS NULL";
        }

        $wherePostoInterno = "AND tbl_tipo_posto.posto_interno IS TRUE";

        if ($os != null) {
            $where_tbl_os_numero = "AND tbl_os.os = {$os}";
        }

        if (!$nao_verifica_estoque) {
            $wherePecaEstoque = "AND tbl_servico_realizado.peca_estoque IS NOT TRUE";
        }

        if (in_array($this->_fabrica, array(171,176))) {
            $where_peca_referencia = "AND tbl_peca.referencia_fabrica IS NOT NULL";
        }

        if ($verifica_os_pedido_obrigatorio == true) {
            $whereServicoRealizado = "AND (tbl_servico_realizado.gera_pedido IS TRUE OR tbl_os_extra.obs_adicionais::jsonb->>'gera_pedido_obrigatorio' = 'true')";
        } else {
            $whereServicoRealizado = 'AND tbl_servico_realizado.gera_pedido IS TRUE';
        }
        $condPA = "AND tbl_peca.produto_acabado IS NOT TRUE 
                   AND tbl_servico_realizado.gera_pedido IS TRUE";
        if ($manual) {
            $condPA = "";
        }
        
        $sql = "SELECT
                        DISTINCT tbl_os.os,
                        tbl_os.posto
                    FROM tbl_os
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                    INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                    INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                    JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
                    {$join_tbl_os_troca}
                    {$join_tbl_extrato_lancamento}
                    {$join_tipo_atendimento}
                    WHERE tbl_os.fabrica = {$this->_fabrica}
                    AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
                    
                    {$wherePecaEstoque}
                    AND tbl_os.excluida IS NOT TRUE
                    {$condPA}
                    AND tbl_os_item.pedido IS NULL
                    /*AND tbl_posto_fabrica.posto NOT IN (6359)*/
                    {$where_tbl_os_troca}
                    {$where_tbl_os_numero}
                    {$wherePostoInterno}
                    {$where_tipo_atendimento}
                    {$where_tbl_extrato_lancamento}
                    {$where_peca_referencia}";

        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        $auditoria_unica = \Posvenda\Regras::get("auditoria_unica", "pedido_garantia", $this->_fabrica);

        if(count($res) > 0){

            $os_pedido = array();

            for($i = 0; $i < count($res); $i++){
                $os    = $res[$i]["os"];
                $posto = $res[$i]["posto"];

                if($auditoria_unica == true){

                    if($this->consultaAuditoriaOS($os)){
                        $os_pedido[] = array(
                            "os"    => $os,
                            "posto" => $posto
                        );
                    }
                }else if($this->verificaOsIntervencaoGarantia($os)){
                    $os_pedido[] = array(
                        "os"    => $os,
                        "posto" => $posto
                    );
                }
            }

            return $os_pedido;

        }else{
            return false;
        }

    }

    public function selectOsPosto($posto, $marca = null, $os = null){

        $pdo = $this->getPDO();

        if ($marca != null) {
            $whereMarca = "AND tbl_produto.marca = {$marca}";
        }

    	if ($os != null) {
    		$whereOsNumero = "AND tbl_os.os = {$os}";
    	}

	$auditoria_unica = \Posvenda\Regras::get("auditoria_unica", "pedido_garantia", $this->_fabrica);
    
    $nao_verifica_estoque = \Posvenda\Regras::get("nao_verifica_estoque", "pedido_garantia", $this->_fabrica);

    $data_corte_posto_interno_gera_pedido = \Posvenda\Regras::get("data_corte_posto_interno_gera_pedido", "pedido_garantia", $this->_fabrica);

    $posto_interno = \Posvenda\Regras::get("posto_interno", "pedido_garantia", $this->_fabrica);

    $verifica_os_pedido_obrigatorio = \Posvenda\Regras::get("verifica_os_pedido_obrigatorio", "pedido_garantia", $this->_fabrica);

    $nao_gera_pedido_os_orcamento = \Posvenda\Regras::get("nao_gera_pedido_os_orcamento", "pedido_garantia", $this->_fabrica);

    $fabrica_nao_valida_os = \Posvenda\Regras::get("ordem_de_servico", "fabrica_nao_valida_os", $this->_fabrica);

    if ($nao_gera_pedido_os_orcamento == true) {
        $whereOrcamento = " AND UPPER(tbl_tipo_atendimento.descricao) <> 'ORÇAMENTO' ";
    }

    if(!empty($data_corte_posto_interno_gera_pedido) and ($posto_interno == $posto)){
        $where_data_corte = " and tbl_os.data_digitacao > '$data_corte_posto_interno_gera_pedido' ";
    }

	if (!$nao_verifica_estoque) {
		$wherePecaEstoque = "AND tbl_servico_realizado.peca_estoque IS NOT TRUE";
	}

        if ($verifica_os_pedido_obrigatorio == true) {
            $whereServicoRealizado = "AND (tbl_servico_realizado.gera_pedido IS TRUE OR tbl_os_extra.obs_adicionais::jsonb->>'gera_pedido_obrigatorio' = 'true')";
        } else {
            $whereServicoRealizado = 'AND tbl_servico_realizado.gera_pedido IS TRUE';
        }

	if (!$fabrica_nao_valida_os) {
		$whereOsValidada = "AND tbl_os.validada IS NOT NULL";
	}

        $sql = "SELECT
                    DISTINCT tbl_os.os
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$this->_fabrica}
                INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$this->_fabrica}
                WHERE tbl_os.fabrica = {$this->_fabrica}
                AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
                {$whereServicoRealizado}
		        {$wherePecaEstoque}
        	AND tbl_servico_realizado.troca_produto IS NOT TRUE
                AND tbl_os.excluida IS NOT TRUE
                {$whereOsValidada}
                AND tbl_peca.produto_acabado IS NOT TRUE
                AND tbl_os_item.pedido IS NULL
                AND tbl_os_item.pedido_item IS NULL
                AND tbl_posto_fabrica.posto = {$posto}
                {$whereMarca}
                {$whereOrcamento}
		{$whereOsNumero}
        {$where_data_corte} ";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if(count($res) > 0){

            $os_pedido_pedido = array();

            for($i = 0; $i < count($res); $i++){

                $os = $res[$i]["os"];

                if($auditoria_unica == true){
                    if($this->consultaAuditoriaOS($os)){
                        $os_pedido_pedido[] = array("os" => $os);
                    }
                }else if($this->verificaOsIntervencaoGarantia($os)){
                    $os_pedido_pedido[] = array("os" => $os);
                }
            }

            return $os_pedido_pedido;

        }else{
            return false;
        }

    }

    public function selectOsPostoEstoque($posto, $os = null){

        $pdo = $this->getPDO();

        if ($os != null) {
            $whereOsNumero = "AND tbl_os.os = {$os}";
        }

        $auditoria_unica = \Posvenda\Regras::get("auditoria_unica", "pedido_garantia", $this->_fabrica);

        $os_garantia = \Posvenda\Regras::get("os_garantia", "pedido_bonificacao", $this->_fabrica);

        if ($os_garantia) {
            $whereTipoAtendimento = "AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE";
        }

        $sql = "SELECT DISTINCT tbl_os.os
                        FROM tbl_os
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                        INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                            AND tbl_os_item.pedido IS NULL
                            AND tbl_os_item.pedido_item IS NULL
                        INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                            AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
                            AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                            AND tbl_tipo_posto.fabrica = {$this->_fabrica}
                        INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$this->_fabrica}
                        INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$this->_fabrica}
                        WHERE tbl_os.fabrica = {$this->_fabrica}
                            AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
                            AND tbl_servico_realizado.peca_estoque IS TRUE
                            AND tbl_os.excluida IS NOT TRUE
                            AND tbl_os.validada IS NOT NULL
                            AND tbl_peca.produto_acabado IS NOT TRUE
                            AND tbl_os_item.pedido IS NULL
                            AND tbl_tipo_posto.posto_interno IS NOT TRUE
                            AND tbl_tipo_posto.tipo_revenda IS NOT TRUE
                            AND tbl_posto_fabrica.posto = {$posto}
                            {$whereTipoAtendimento}
                            {$whereOsNumero}";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if(count($res) > 0){

            $os_pedido_pedido = array();

            for($i = 0; $i < count($res); $i++){

                $os = $res[$i]["os"];

                if($auditoria_unica == true){
                    if($this->consultaAuditoriaOS($os)){
                        $os_pedido_pedido[] = array("os" => $os);
                    }
                }else if($this->verificaOsIntervencaoGarantia($os)){
                    $os_pedido_pedido[] = array("os" => $os);
                }
            }

            return $os_pedido_pedido;

        }else{
            return false;
        }

    }

    public function selectOsTrocaPosto($posto, $marca = null, $os = null){

        $pdo = $this->getPDO();

        $auditoria_unica = \Posvenda\Regras::get("auditoria_unica", "pedido_garantia", $this->_fabrica);

        $posto_interno_nao_gera = \Posvenda\Regras::get("posto_interno_nao_gera", "pedido_garantia", $this->_fabrica);

        if ($posto_interno_nao_gera == true) {
            $wherePostoInterno = "AND tbl_tipo_posto.posto_interno IS NOT TRUE";
        }

	$posto_interno_gera_troca = \Posvenda\Regras::get("posto_interno_gera_troca", "pedido_garantia", $this->_fabrica);

	if($posto_interno_gera_troca == true){
		$wherePostoInterno = "";
	}

        if ($marca != null) {
            $whereMarca = "AND tbl_produto.marca = {$marca}";
        }

        if ($os != null) {
            $whereOsNumero = "AND tbl_os.os = {$os}";
        }

        $cond_posto = "AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')";
        if ($this->_fabrica == 151) {
            $cond_posto = "AND ((tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')) OR (tbl_posto_fabrica.credenciamento = 'DESCREDENCIADO' AND tbl_os.troca_garantia IS TRUE))";
        }

        $sql = "SELECT DISTINCT tbl_os.os
                    FROM tbl_os
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                    INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                    INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                    JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
                    INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$this->_fabrica}
                    INNER JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.gerar_pedido IS TRUE
                    WHERE tbl_os.fabrica = {$this->_fabrica}
                    {$cond_posto}
                    AND tbl_servico_realizado.gera_pedido IS TRUE
                    AND tbl_servico_realizado.peca_estoque IS NOT TRUE
        	        AND tbl_servico_realizado.troca_produto IS TRUE
                    AND tbl_os.excluida IS NOT TRUE
                    AND tbl_os.validada IS NOT NULL
                    AND tbl_peca.produto_acabado IS TRUE
                    AND tbl_os_item.pedido IS NULL
                    AND tbl_os_item.pedido_item IS NULL
                    AND tbl_posto_fabrica.posto = {$posto}
                    {$whereMarca}
                    {$whereOsNumero}
                    {$wherePostoInterno}";
        $query = $pdo->query($sql);

        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if(count($res) > 0){

            $os_pedido_pedido = array();

            for($i = 0; $i < count($res); $i++){

                $os = $res[$i]["os"];

                if($auditoria_unica == true){
                    if($this->consultaAuditoriaOS($os)){
                        $os_pedido_pedido[] = array("os" => $os);
                    }
                }else if($this->verificaOsIntervencaoGarantia($os)){
                    $os_pedido_pedido[] = array("os" => $os);
                }
            }

            return $os_pedido_pedido;

        }else{
            return false;
        }

    }

    public function verificaOsIntervencaoGarantia($os = "", $fabrica = ""){
        if(!empty($fabrica)){
            $this->_fabrica = $fabrica;
        }

        if(!empty($os)){
            $this->_os = $os;
        }

        /* PDO */

        $pdo = $this->getPDO();

        /* KM */

        $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status = $this->_fabrica AND os = {$this->_os} AND status_os IN(98,99,100,101) ORDER BY data DESC LIMIT 1";
        $query = $pdo->query($sql);

        $res = $query->fetch(\PDO::FETCH_ASSOC);
        $status_os = $res['status_os'];

        if($status_os == 98){
            return false;
        }

        /* Pe&ccedil;as Excedentes */

        $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status = $this->_fabrica AND os = {$this->_os} AND status_os IN(118,185,187,19) ORDER BY data DESC LIMIT 1";
        $query = $pdo->query($sql);

        $res = $query->fetch(\PDO::FETCH_ASSOC);
        $status_os = $res['status_os'];

        if($status_os == 118){
            return false;
        }

        /* Pe&ccedil;a Crí­tica */

        $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status = $this->_fabrica AND os = {$this->_os} AND status_os IN(62,64) ORDER BY data DESC LIMIT 1";
        $query = $pdo->query($sql);

        $res = $query->fetch(\PDO::FETCH_ASSOC);
        $status_os = $res['status_os'];

        if($status_os == 62){
            return false;
        }

        $auditorias_adicionais = \Posvenda\Regras::get("auditorias", "pedido_garantia", $this->_fabrica);

        if (count($auditorias_adicionais) > 0) {
            foreach ($auditorias_adicionais as $key => $auditoria) {
                $status_auditoria = $auditoria["status"];
                $status_aprovacao = $auditoria["status_aprovacao"];

                $sql = "SELECT status_os FROM tbl_os_status WHERE fabrica_status = $this->_fabrica AND os = {$this->_os} AND status_os IN(".implode(",", $status_auditoria).") ORDER BY data DESC LIMIT 1";
                $query = $pdo->query($sql);

                $res = $query->fetch(\PDO::FETCH_ASSOC);

                if (is_array($res) && count($res) > 0) {
					$status_os = $res['status_os'];
					if(is_array($status_aprovacao)) {
						if(!in_array($status_os,$status_aprovacao)) {
							return false;
						}
					}else{
						if($status_os != $status_aprovacao){
							return false;
						}
					}
                }
            }
        }

        return true;

    }

    public function selectOsTroca($param, $marca = null, $os = null){

        $pdo = $this->getPDO();

	if ($marca != null) {
            $whereMarca = "AND tbl_produto.marca = {$marca}";
        }

        if ($os != null) {
                $whereOsNumero = "AND tbl_os.os = {$os}";
        }

        $cond_posto = "AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')";
        if ($this->_fabrica == 151) {
            $cond_posto = "AND ((tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')) OR (tbl_posto_fabrica.credenciamento = 'DESCREDENCIADO' AND tbl_os.troca_garantia IS TRUE))";
        }

        if($param == "posto"){

            $sql = "SELECT
                DISTINCT tbl_os.posto
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
        INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$this->_fabrica}
        INNER JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.gerar_pedido IS TRUE
                WHERE tbl_os.fabrica = {$this->_fabrica}
                {$cond_posto}
                AND tbl_servico_realizado.gera_pedido IS TRUE
                AND tbl_servico_realizado.peca_estoque IS NOT TRUE
        AND tbl_servico_realizado.troca_produto IS TRUE
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.validada IS NOT NULL
                AND tbl_peca.produto_acabado IS TRUE
                AND tbl_os_item.pedido IS NULL
                AND tbl_os_item.pedido_item IS NULL
		{$whereMarca}
		{$whereOsNumero}";

        }elseif($param == "os"){

            $sql = "SELECT
                    DISTINCT tbl_os.os,
                    tbl_os.posto
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
        INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$this->_fabrica}
        INNER JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.gerar_pedido IS TRUE
                WHERE tbl_os.fabrica = {$this->_fabrica}
                {$cond_posto}
                AND tbl_servico_realizado.gera_pedido IS TRUE
                AND tbl_servico_realizado.peca_estoque IS NOT TRUE
        AND tbl_servico_realizado.troca_produto IS TRUE
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.validada IS NOT NULL
                AND tbl_peca.produto_acabado IS TRUE
                AND tbl_os_item.pedido IS NULL
                AND tbl_os_item.pedido_item IS NULL
        {$whereMarca}
        {$whereOsNumero}";

        }

        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);
        // $res = pg_fetch_all($query);

        if(count($res) > 0){

            $os_pedido = array();

            for($i = 0; $i < count($res); $i++){

                if($param == "posto"){
                    $posto = $res[$i]["posto"];

                    $os_pedido[] = array("posto" => $posto);

                }elseif(empty($param) or $param == "os"){

                    $os    = $res[$i]["os"];
                    $posto = $res[$i]["posto"];

                    $os_pedido[] = array(
                        "os"    => $os,
                        "posto" => $posto
                    );
                }
            }

            return $os_pedido;

        }else{
            return false;
        }

    }

    public function consultaAuditoriaOS($os, $digitacao_item = null) {
        $pdo = $this->getPDO();

        if(!empty($os)){
            $sql   = "SELECT DISTINCT auditoria_status, observacao FROM tbl_auditoria_os WHERE os = {$os};";
            $query = $pdo->query($sql);
            $res   = $query->fetchAll();

            $bloqueio_pedido = false;

            $cancelaOS = \Posvenda\Regras::get("cancelaOS", "pedido_garantia", $this->_fabrica);

            for($i = 0; $i < count($res); $i++) {

                $auditoria_status = $res[$i]["auditoria_status"];
                $observacao       = $res[$i]["observacao"];

                $sqlBloqPedido = "
                    SELECT auditoria_os, liberada, bloqueio_pedido, cancelada, reprovada
                    FROM tbl_auditoria_os
                    WHERE os = {$os}
                    AND auditoria_status = {$auditoria_status}
                    AND fn_retira_especiais(observacao) = fn_retira_especiais('{$observacao}')
                    ORDER BY data_input DESC
                    LIMIT 1
                ";
                $queryBloqPedido = $pdo->query($sqlBloqPedido);
    	        $resBloqPedido   = $queryBloqPedido->fetchAll();

                if ($cancelaOS && $resBloqPedido[0]["cancelada"] != "") {
                    continue;
                }

            	if ($resBloqPedido[0]['bloqueio_pedido'] == 't' && $resBloqPedido[0]['liberada'] == "") {
					if(!empty($digitacao_item) and (!empty($resBloqPedido[0]['cancelada']) or !empty($resBloqPedido[0]['reprovada']))){
						if(!empty($resBloqPedido[0]['cancelada']) and strtotime($digitacao_item) < strtotime($resBloqPedido[0]['cancelada'])){
							$bloqueia_pedido = true;
						}

						if(!empty($resBloqPedido[0]['reprovada']) and strtotime($digitacao_item) < strtotime($resBloqPedido[0]['reprovada'])){
							$bloqueia_pedido = true;
						}
					}else{
	                    $bloqueia_pedido = true;
					}
    	        }
            }

            if($bloqueia_pedido){
                return false;
            }else{
                return true;
            }
        }else{
            return false;
        }

    }

	public function verificaSolucaoOS($con) {

        $multiplo = \Posvenda\Regras::get("solucao_os_multiplo", "ordem_de_servico", $this->_fabrica);

        if ($multiplo) {
            $sql = "SELECT solucao FROM tbl_os_defeito_reclamado_constatado WHERE os = {$this->_os} AND solucao IS NOT NULL";
            $query = pg_query($con, $sql);

            if (pg_num_rows($query) > 0) {
               $solucao_os = pg_fetch_all($query); 
            }

        } else {
            $sql = "SELECT solucao_os FROM tbl_os WHERE os = {$this->_os} AND solucao_os notnull";
            $query = pg_query($con, $sql);
            $solucao_os = pg_fetch_result($query, 0, 'solucao_os');
        }

        if(!empty($solucao_os)){
            return true;
        }else{
            return false;
        }
    }

    public function osAprovadaSemValor($os = null) {
        if (empty($os)) {
            $os = $this->_os;
        }

        $pdo = $this->getPDO();

        $sql = "
            SELECT os FROM tbl_auditoria_os WHERE os = {$os} AND liberada IS NOT NULL AND paga_mao_obra IS FALSE
        ";
        $query = $pdo->query($sql);

        if ($query && $query->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function osReprovadaSemValor($os = null) {
        if (empty($os)) {
            $os = $this->_os;
        }

        $pdo = $this->getPDO();

        $sql = "
            SELECT os FROM tbl_auditoria_os WHERE os = {$os} AND reprovada IS NOT NULL AND paga_mao_obra IS FALSE
        ";
        $query = $pdo->query($sql);
        
        if ($query && $query->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

	public function osRecusadaMo($os = null) {
        if (empty($os)) {
            $os = $this->_os;
        }

        $pdo = $this->getPDO();

        $sql = "
            SELECT os FROM tbl_os_status WHERE os = {$os} AND status_os = 13 and extrato notnull 
        ";
        $query = $pdo->query($sql);
        
        if ($query && $query->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }


    public function zerarValores($os = null)
    {
        if (empty($os)) {
            $os = $this->_os;
        }

        $this->update("tbl_os")
             ->setCampos(array('mao_de_obra' => 0, 'qtde_km_calculada' => 0, 'valores_adicionais' => 0))
             ->addWhere(array('os' => $os))
             ->addWhere(array('fabrica' => $this->_fabrica));

        if (!$this->prepare()->execute()) {
            throw new Exception("Erro ao zerar valores da OS : {$this->_sua_os}");
        }

        return $this;
    }

    public function verificaPedidoOsGeradoFaturado($sua_os) 
    {
        $pdo = $this->getPDO();

        if (!empty($sua_os)) {
            $sql = "SELECT tbl_os_item.pedido, tbl_os_item.pedido_item, tbl_pedido_item.qtde_faturada
                    FROM tbl_os_item
                        INNER JOIN tbl_os_produto  ON tbl_os_produto.os_produto   = tbl_os_item.os_produto
                        INNER JOIN tbl_os          ON tbl_os.os                   = tbl_os_produto.os
                        LEFT JOIN  tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                    WHERE tbl_os_produto.os = tbl_os.os
                        AND tbl_os.sua_os   = '{$sua_os}'";
            $queryOs  = $pdo->query($sql);
            $resultOs = $queryOs->fetchAll(\PDO::FETCH_ASSOC);

             if (!$resultOs){
                if (!$queryOs) {
                   throw new \Exception("Ordem de serviço não encontrada");
                }
            }

            $pedido        = $resultOs[0]['pedido'];
            $qtde_faturada = $resultOs[0]['qtde_faturada'];
            
            if (empty($pedido) || empty($qtde_faturada)) {
                return false;
            }

            return true;
        }
    }

    public function getConsumidorRevendaSuaOS($sua_os) {
        $pdo = $this->getPDO();

        if (!empty($sua_os)){
            $sql   = "SELECT consumidor_revenda FROM tbl_os WHERE sua_os = '{$sua_os}'";
            $query = $pdo->query($sql);
            $res   = $query->fetchAll(\PDO::FETCH_ASSOC);
            return (count($res) > 0) ? $res[0]["consumidor_revenda"] : false;
        }                

        return false;
    } 

    public function atualizaStatusCheckpoint($os, $descricao) {

        if (!is_resource($this->_conn)) {
            $pdo = $this->_model->getPDO();
        }

        $busca_status = "SELECT status_checkpoint
                         FROM tbl_status_checkpoint
                         WHERE UPPER(descricao) = UPPER('".$descricao."')
                         LIMIT 1";

        $sql = "UPDATE tbl_os
                SET status_checkpoint = (
                    {$busca_status}
                )
                WHERE os = {$os}
                AND status_checkpoint NOT IN (
                    {$busca_status}
                )";

        $query = (is_resource($this->_conn)) ? pg_query($this->_conn, $sql) : $pdo->query($sql);

        return true;

    }

    public function entrouEmAuditoria($os, $observacao, $auditoria_status) {

        $sql = "SELECT auditoria_os
                FROM tbl_auditoria_os
                WHERE os = {$os}
                AND UPPER(observacao) = UPPER('{$observacao}')
                AND cancelada IS NULL";

        $dados = [];
        if (!is_resource($this->_conn)) {

            $pdo = $this->_model->getPDO();

            $query = $pdo->query($sql);

            $dados = $query->fetch();

        } else {

            $query = pg_query($this->_conn, $sql);

            if (pg_num_rows($query) > 0) {
                $dados = pg_fetch_array($query);
            }

        }
        
        return count($dados) > 0;

    }

}
