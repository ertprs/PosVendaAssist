<?php

namespace Posvenda\Fabricas\_190;

use Posvenda\Os as OsPosvenda;

class Os extends OsPosvenda
{

    public $_erro;
    protected $_fabrica;

    public function __construct($fabrica, $os)
    {
        $this->_fabrica = $fabrica;
        parent::__construct($fabrica, $os);
    }

    public function finaliza($con)
    {
        if (empty($this->_os)) {
            throw new \Exception("Ordem de Serviço não informada");
        }
        
        parent::finaliza($con);
    }

    public function validaInformacoesOs($os, $sua_os, $data_fechamento, $tipo_revenda = 'f', $os_produto = true){
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

            $sql = "
                SELECT
                    tbl_contrato.campo_extra ->> 'mao_obra_fixa' AS mao_obra_fixa,
                    tbl_os_campo_extra.campos_adicionais::jsonb ->> 'horas_trabalhadas' AS horas_trabalhadas,
                    tbl_contrato_os.os AS os
                FROM tbl_contrato
                JOIN tbl_contrato_os ON tbl_contrato_os.contrato = tbl_contrato.contrato AND tbl_contrato_os.os = {$os}
                LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_contrato_os.os AND tbl_os_campo_extra.fabrica = {$this->_fabrica}
                WHERE tbl_contrato.fabrica = {$this->_fabrica}";
            $query = $pdo->query($sql);
            if (!$query) {
                throw new \Exception("Erro ao fechar Ordem de Serviço #020");
            }else{
                $result = $query->fetch(\PDO::FETCH_ASSOC);
                
                if (is_array($result) AND count($result) > 0){
                    $mao_obra_fixa = $result["mao_obra_fixa"];
                    $horas_trabalhadas = $result["horas_trabalhadas"];

                    if ($mao_obra_fixa != "sim" AND strlen(trim($horas_trabalhadas)) == 0){
                        throw new \Exception(traduz("Informe a quantidade de horas trabalhadas para a OS: {$sua_os}"));
                    }
                }
            }
        }
        return false;
    }

    public function calculaOs($os = null)
    {
        if (empty($os)) {
            $os = $this->_os;
        }

        try {

            if ($this->_model->osAprovadaSemValor($os) || $this->_model->osReprovadaSemValor($os)) {
                $this->_model->zerarValores($os);
                return $this;
            }

            // MO
            $nao_calcula_mo = \Posvenda\Regras::get("nao_calcula_mo", "mao_de_obra", $this->_fabrica);
            $nao_calcula_mo_distribuidor = \Posvenda\Regras::get("nao_calcula_mo_distribuidor", "mao_de_obra", $this->_fabrica);


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

                //QDO TIVER VALO FIXO NO CONTRATO, JOGAR ZERO NA MO DA OS
                if ($this->verificaContratoOs($os)) {
                    $mao_de_obra = $this->getMaoDeObraContrato($os);
                } else {

                    ///QDO A O.S. NAO TEM CONTRATO VINCULADO DEVE PEGAR O VALOR DA MO DO POSTO NO PRODUTO X TEMPO DE REPARO
                    $mao_de_obra  = $this->calculaMoHr($os);
                   
                }

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


    public function verificaTipoAtendimentoNilfisk($os, $codigo_tipo) {
        $pdo = $this->_model->getPDO();
        $sql = "SELECT tbl_os.os
                  FROM tbl_os
                  JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$this->_fabrica}
                 WHERE tbl_os.fabrica = {$this->_fabrica}
                   AND tbl_os.os = {$os}
                   AND tbl_tipo_atendimento.codigo = '{$codigo_tipo}'                        
                ";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0){
            return true;
        }
        return false;
    }

    public function getMobraEntrega($os) {
        $pdo = $this->_model->getPDO();
        $sql = "SELECT tbl_produto.mao_de_obra_admin AS mao_de_obra
                  FROM tbl_os
                  JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$this->_fabrica}
                 WHERE tbl_os.fabrica = {$this->_fabrica}
                   AND tbl_os.os = {$os}
                ";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0){
            return $res[0];
        }
        return false;
    }

    public function verificaContratoOs($os) {
        $pdo = $this->_model->getPDO();

        $sql = "SELECT DISTINCT tbl_contrato.contrato,tbl_contrato.campo_extra->>'valor_mao_obra_fixa' as valor_mao_obra_fixa
                  FROM tbl_contrato_os 
                  JOIN tbl_contrato ON tbl_contrato_os.contrato = tbl_contrato.contrato AND tbl_contrato.fabrica = {$this->_fabrica}
                 WHERE tbl_contrato_os.os = {$os}
                  AND tbl_contrato.campo_extra->>'valor_mao_obra_fixa' <> ''
                ";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0 && $res[0]["valor_mao_obra_fixa"] > 0){
            return true;
        }
        return false;

    }

    public function calculaMoHr($os, $mo = false)
    {
        $pdo = $this->_model->getPDO();
        
        $sql = "SELECT tbl_os_campo_extra.campos_adicionais , tbl_produto.mao_de_obra
                  FROM tbl_os 
                  JOIN tbl_os_campo_extra USING(os)
                  JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto  AND tbl_produto.fabrica_i = {$this->_fabrica}
                 WHERE tbl_os.os = {$os} 
                  AND tbl_os.fabrica = {$this->_fabrica}
                  
                ";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0){
            $campos_adicionais = $res[0]["campos_adicionais"];
            $campos_adicionais = json_decode($campos_adicionais, true);
            if (!$mo && isset($campos_adicionais["horas_trabalhadas"]) && $campos_adicionais["horas_trabalhadas"] <> '') {
                $horas_trabalhadas = $campos_adicionais["horas_trabalhadas"];
		list($hora, $minuto) = explode(":",$horas_trabalhadas);                
		$valor_minuto = $res[0]["mao_de_obra"]/60;
		$total_minuto = ($hora*60)+$minuto;

		$xmaoObra = $total_minuto*$valor_minuto;


            } elseif ($mo) {
                $xmaoObra = $res[0]["mao_de_obra"];
            }

		
	    $upMO = "UPDATE tbl_os SET mao_de_obra='$xmaoObra' WHERE os = $os AND fabrica = $this->_fabrica ";
            $xquery = $pdo->query($upMO);

	    if ($xquery) {
		return $xmaoObra;
	    }

        }
    }

    public function getMaoDeObraContrato($os)
    {
        $pdo = $this->_model->getPDO();
       $sql = "SELECT DISTINCT tbl_contrato.contrato,tbl_contrato.campo_extra
                  FROM tbl_contrato_os 
                  JOIN tbl_contrato ON tbl_contrato_os.contrato = tbl_contrato.contrato AND tbl_contrato.fabrica = {$this->_fabrica}
                 WHERE tbl_contrato_os.os = {$os}
                  AND tbl_contrato.campo_extra->>'valor_mao_obra_fixa' <> ''
                ";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0){
            $retorno = json_decode($res[0]["campo_extra"],1);
            if (isset($retorno["valor_mao_obra_fixa"])) {
                return $retorno["valor_mao_obra_fixa"];
            }
        }
        return 0;
    }

    public function finalizaAtendimento($hd_chamado, $justificativa)
    {
        if (empty($hd_chamado)) {
            return false;
        }

        $pdo = $this->_model->getPDO();
        $comentario = 'A OS ' . $this->_os . ' aberta para este atendimento foi finalizada. Justificativa: '.$justificativa;

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

        $sql = "UPDATE tbl_hd_chamado SET status = 'Resolvido' WHERE hd_chamado = $hd_chamado";
        $query = $pdo->query($sql);

        if (empty($query)) {
            return false;
        }

        return true;
    }
}
