<?php

namespace Posvenda\Fabricas\_195;

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

    public function getOsOrcamento($posto = null,$os = null){

        $pdo = $this->_model->getPDO();

        if ($os != null) {
            $where_tbl_os_numero = " AND tbl_os.os = {$os}";
        }

        if($posto != null){
             $where_tbl_os_numero = " AND tbl_os.posto = {$posto}";
        }

        $sql = "SELECT DISTINCT 
                       tbl_os.posto,
               tbl_os.os,
               tbl_status_os.descricao AS status_os
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                INNER JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
                INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
                JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$this->_fabrica}
                JOIN tbl_status_os ON tbl_os.status_os_ultimo = tbl_status_os.status_os
                WHERE tbl_os.fabrica = {$this->_fabrica}
                AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_peca.produto_acabado IS NOT TRUE
                AND tbl_os_item.pedido IS NULL
                AND fn_retira_especiais(LOWER(tbl_status_os.descricao)) = 'aguardando conserto'                        
                AND fn_retira_especiais(LOWER(tbl_tipo_atendimento.descricao)) = 'orcamento'                        
                {$where_tbl_os_numero}";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if(count($res) > 0){

            $os_pedido = array();

            for($i = 0; $i < count($res); $i++){

                    $os     = $res[$i]["os"];
                    $posto  = $res[$i]["posto"];
                    $status = $res[$i]["status_os"];

                    $os_pedido[] = array(
                                    "os"    => $os,
                                    "posto" => $posto,
                                    "status" => $status
                    );
            }

            return $os_pedido;

        }else{
            return false;
        }

    }

     public function getPecasPedidoOrcamento($os) {

        if (empty($os)) {
            return false;
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
                        INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os_produto.os
                        WHERE tbl_os_produto.os = {$os}
                        AND tbl_peca.produto_acabado IS NOT TRUE
                        ";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res;

    }

    public function getCondicaoBoleto(){

        $pdo = $this->_model->getPDO();
        $sql = "SELECT condicao FROM tbl_condicao WHERE fabrica = {$this->_fabrica} AND lower(descricao) ~* 'boleto' limit 1 ";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res[0]['condicao'];
    }

    public function getTipoPedidoOrcamento(){

        $pdo = $this->_model->getPDO();
        $sql = "SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = {$this->_fabrica} AND lower(codigo) = 'orc'";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res[0]['tipo_pedido'];
    }

    public function getTabelaVenda($os){

        $pdo = $this->_model->getPDO();
        $sql = "SELECT data_fabricacao
                  FROM tbl_os_extra
                 WHERE os = {$os}";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);
        $xdata_fabricacao = $res[0]["data_fabricacao"];
        $sql = "SELECT tabela
                  FROM tbl_tabela
                 WHERE data_vigencia::DATE <= '{$xdata_fabricacao}'
                   AND termino_vigencia::DATE >= '{$xdata_fabricacao}'
                   AND fabrica = {$this->_fabrica}
                LIMIT 1";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res[0]['tabela'];
    }

    public function finaliza($con)
    {
        if (empty($this->_os)) {
            throw new \Exception("Ordem de Serviço não informada");
        }
        
        parent::finaliza($con);
    }

    public function verificaOsSemPeca($con, $login_fabrica, $os) {

        $sql = "SELECT os
                FROM   tbl_os_produto
                JOIN   tbl_os_item USING(os_produto)
                WHERE  tbl_os_produto.os = {$os}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) == 0) {
            return true;
        }
        return false;
    }

    public function verificaOsServicoAjuste($con, $login_fabrica, $os) {
        $sql = "SELECT tbl_os_produto.os, tbl_os_item.servico_realizado
                FROM   tbl_os_produto
                JOIN   tbl_os_item USING(os_produto)
                WHERE  tbl_os_produto.os = {$os}";
        $res = pg_query($con, $sql);
        $total_peca_ajuste = [];
        $total_peca = [];

        foreach (pg_fetch_all($res) as $key => $row) {

            $servico_realizado     = $row["servico_realizado"];

            $sqlx = "SELECT descricao
                    FROM   tbl_servico_realizado
                    WHERE  servico_realizado = {$servico_realizado}";
            $resx = pg_query($con, $sqlx);
            $total_peca[] = true;

            $descricao = trim(pg_fetch_result($resx, 0, "descricao"));
            if ($descricao == "Reparo sem Peça (não gera pedido)") {
                $total_peca_ajuste[] = true;
            } 
        }
        if (count($total_peca_ajuste) == count($total_peca)) {
            return true;
        }

        return false;

    }


    public function insereAuditoriaDeFabrica($con, $os) {

        $sqlStatusAud = "SELECT auditoria_status 
                           FROM tbl_auditoria_status 
                          WHERE fabricante = 't'";
        $resStatusAud = pg_query($con, $sqlStatusAud);

        $auditoria_status = pg_fetch_result($resStatusAud, 0, "auditoria_status");

        $sqlAud = "SELECT tbl_auditoria_os.os,
                          tbl_auditoria_os.auditoria_os,
                          tbl_auditoria_os.liberada,
                          tbl_auditoria_os.reprovada
                     FROM tbl_auditoria_os
                     JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = {$this->_fabrica}
                    WHERE tbl_auditoria_os.os = {$os}
                      AND tbl_auditoria_os.auditoria_status = {$auditoria_status}
                      AND tbl_auditoria_os.observacao ILIKE '%Auditoria de F%'";
        $resAud = pg_query($con, $sqlAud);
        if (pg_num_rows($resAud) == 0) {
            $sqlInsertAud = "INSERT INTO tbl_auditoria_os 
                                                        (
                                                            os,
                                                            auditoria_status,
                                                            observacao
                                                        ) VALUES (
                                                            {$os},
                                                            $auditoria_status,
                                                            'Auditoria de Fábrica: Os fechada sem peça'
                                                        )";
                        $resInsertAud = pg_query($con, $sqlInsertAud);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço");
            }
        }
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
                $mao_de_obra = $this->getMaoDeObraTipoAtendimento($os);
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

    public function getMaoDeObraTipoAtendimento($os)
    {
        $pdo = $this->_model->getPDO();
        
        $sql = "SELECT MOSR.*,
                       XOS.consumidor_cidade,
                       XOS.consumidor_estado 
                  FROM tbl_os XOS
                  JOIN tbl_tipo_atendimento TA ON TA.tipo_atendimento = XOS.tipo_atendimento AND TA.fabrica = {$this->_fabrica}
                  JOIN tbl_mao_obra_servico_realizado MOSR ON MOSR.tipo_atendimento = TA.tipo_atendimento AND MOSR.fabrica = {$this->_fabrica}
                 WHERE XOS.os = {$os} 
                   AND XOS.fabrica = {$this->_fabrica}
                  
                ";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0){
            $consumidor_cidade = $res[0]["consumidor_cidade"];
            $consumidor_estado = $res[0]["consumidor_estado"];
            
            foreach ($res as $key => $value) {
                
                $xparametros = json_decode($value["parametros_adicionais"],1);
                
                if (isset($xparametros["cidade"]) && strlen($xparametros["cidade"]) > 0 && isset($xparametros["estado"]) && strlen($xparametros["estado"]) > 0) {
                    $cidade = $xparametros["cidade"];
                    $estado = $xparametros["estado"];
                    if (trim($consumidor_cidade) == trim($cidade) && trim($consumidor_estado) == trim($estado)) {

                        $xmaoObra = $value["mao_de_obra"];
                        break;
                    }
                } else {

                    $xmaoObra = $value["mao_de_obra"];
                }

            }

        }
        $upMO = "UPDATE tbl_os SET mao_de_obra='$xmaoObra' WHERE os = $os AND fabrica = $this->_fabrica ";
        $xquery = $pdo->query($upMO);

        if ($xquery) {
            return $xmaoObra;
        }

    }

}
