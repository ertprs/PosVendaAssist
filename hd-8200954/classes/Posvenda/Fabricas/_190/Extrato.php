<?php
use Posvenda\Model\Extrato as ExtratoModel;
/**
* Class Extrato Nilfisk
*/
class ExtratoNilfisk
{
    public $_model;
    public $_erro;
    protected $_fabrica;
    private $extrato;

    public function __construct($fabrica) {

        $this->_fabrica = $fabrica;

        if(!empty($this->_fabrica)){
            $this->_model = new ExtratoModel($this->_fabrica);
        }

    }
    
//GERAR UM EXTRATO PARA CADA CONTRATO ATIVO, POREM A MO, PECAS E KM, SO DEVERAR SER CACULADO QDO 
//O SERVICO REALIZADO SERA DE MAU USO
    public function buscaExtratoPostoHj($dia_extrato, $fabrica)
    {
       
       $pdo = $this->_model->getPDO();
       $sql = "SELECT tbl_os.posto,
                    COUNT(*) AS qtde,
                    tbl_posto.nome,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto_fabrica.contato_email,
                    tbl_extrato.extrato
            FROM    tbl_os
            JOIN    tbl_os_extra USING (os)
            JOIN    tbl_posto           ON  tbl_os.posto                = tbl_posto.posto
            JOIN    tbl_posto_fabrica   ON  tbl_posto.posto             = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica   = {$fabrica}
       LEFT JOIN    tbl_tipo_posto      ON  tbl_tipo_posto.tipo_posto   = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica      = {$fabrica}
            JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_extrato.data_geracao::date >= '2020-02-05' AND tbl_extrato.fabrica = {$fabrica}
            WHERE   tbl_os.fabrica = {$fabrica}
            AND tbl_os_extra.extrato IS NOT NULL
            AND tbl_os_extra.extrato_recebimento IS NULL
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_os.finalizada::date <= '{$dia_extrato}'
            GROUP BY tbl_os.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_posto_fabrica.contato_email,tbl_extrato.extrato
            ORDER BY tbl_os.posto";
        $query = $pdo->query($sql);
        $res   = $query->fetchAll(\PDO::FETCH_ASSOC);

        $retorno = [];
        foreach ($res as $key => $value) {
            $retorno[$value["posto"]][] = $value["extrato"];
        }
        return $retorno;

    }    
    public function buscaContratoExtrato($extrato, $fabrica)
    {
       
       $pdo = $this->_model->getPDO();
       $sql = "SELECT tbl_os.os,
                      tbl_contrato_os.contrato
                 FROM tbl_os
                 JOIN tbl_os_extra USING (os)
                 JOIN tbl_contrato_os USING (os)
                WHERE tbl_os.fabrica = {$fabrica}
                  AND tbl_os_extra.extrato IN (".implode(',',$extrato).")
                  AND tbl_os_extra.extrato_recebimento IS NULL
                  AND tbl_os.excluida IS NOT TRUE";
        $query = $pdo->query($sql);
        $res   = $query->fetchAll(\PDO::FETCH_ASSOC);
        
        $retorno = [];
        foreach ($res as $key => $value) {
            $retorno[$value["contrato"]][] = $value["os"];
        }
        return $retorno;

    }

    public function getOsPostoRecebimento($dia_extrato = "", $fabrica = "", $marca = false, $query_cond = '', $posto = null){
        global $_serverEnvironment;

        if ($_serverEnvironment == 'development') {
            $condPostoTestes = "AND tbl_os.posto = 6359";
        }

        $tipo_atendimento_nao_gera = \Posvenda\Regras::get("tipo_atendimento_nao_gera", "extrato", $this->_fabrica);
        $fora_garantia_nao_gera = \Posvenda\Regras::get("fora_garantia_nao_gera", "extrato", $this->_fabrica);
        $nao_gera_os_auditoria = \Posvenda\Regras::get("nao_gera_os_auditoria", "extrato", $this->_fabrica);
        $nao_os_bloqueada = \Posvenda\Regras::get("nao_os_bloqueada","extrato",$this->_fabrica);

        $condTipoAtendimento = '';
        $cond_nao_gera_os_auditoria = '';

        $pdo = $this->_model->getPDO();

        if (!empty($nao_os_bloqueada) && $nao_os_bloqueada == true) {
            $joinOsCampoExtra = " LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os ";
            $condOsCampoExtra = " AND tbl_os_campo_extra.os_bloqueada IS NOT TRUE";
        }

        if (!empty($nao_gera_os_auditoria) && $nao_gera_os_auditoria == true) {
            $cond_nao_gera_os_auditoria = " AND tbl_os.os NOT IN( SELECT ao.os FROM tbl_auditoria_os ao WHERE ao.os = tbl_os.os AND ao.liberada IS NULL AND ao.reprovada IS NULL ) ";
        }


        if (!empty($tipo_atendimento_nao_gera)) {
            $condTipoAtendimento = " AND tbl_os.tipo_atendimento <> $tipo_atendimento_nao_gera ";
        }

        if(!empty($fora_garantia_nao_gera)){
            if (is_bool($fora_garantia_nao_gera))
                $fora_garantia_nao_gera = ($fora_garantia_nao_gera == 1) ? 'true' : 'false';

            $sqlAtendimento = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = $this->_fabrica AND fora_garantia = {$fora_garantia_nao_gera}";
            $query  = $pdo->query($sqlAtendimento);
            $id_atendimento = $query->fetchAll(\PDO::FETCH_ASSOC);

            $condTipoAtendimento = " AND tbl_os.tipo_atendimento NOT IN (";
            foreach ($id_atendimento as $value) {
                $condTipoAtendimento .= $value['tipo_atendimento'].',';
            }
            $condTipoAtendimento = substr($condTipoAtendimento, 0, strlen($condTipoAtendimento) - 1).')';
        }

        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }

        if(!empty($posto)) {
            $cond_posto = " AND tbl_os.posto = $posto ";
        }
        $this->verificaGeraExtrato();

            $sql = "SELECT  tbl_os.posto,
                            COUNT(*) AS qtde,
                            tbl_posto.nome,
                            tbl_posto_fabrica.codigo_posto,
                            tbl_posto_fabrica.contato_email
                    FROM    tbl_os
                    JOIN    tbl_os_extra USING (os)
                    JOIN    tbl_posto           ON  tbl_os.posto                = tbl_posto.posto
                    $joinOsCampoExtra
                    $join_nao_gera_extrato_reprovada
                    JOIN    tbl_posto_fabrica   ON  tbl_posto.posto             = tbl_posto_fabrica.posto
                                                AND tbl_posto_fabrica.fabrica   = {$fabrica}
               LEFT JOIN    tbl_tipo_posto      ON  tbl_tipo_posto.tipo_posto   = tbl_posto_fabrica.tipo_posto
                                                AND tbl_tipo_posto.fabrica      = {$fabrica}
                    {$cond_nao_gera_extrato}
                    JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_extrato.data_geracao = current_date AND tbl_extrato.fabrica = {$fabrica}
                    WHERE   tbl_os.fabrica = {$fabrica}
                    AND     tbl_os_extra.extrato IS NOT NULL
                    AND     tbl_os_extra.extrato_recebimento IS NULL
                    AND     tbl_os.excluida IS NOT TRUE
                    AND     tbl_os.finalizada::date <= '{$dia_extrato}'
                    $condTipoAtendimento
                    {$cond_nao_gera_os_auditoria}
                    {$cond_nao_gera_os_auditoria_termo}
                    {$cond_nao_gera_extrato_reprovada}
                    {$this->_gera_extrato}
                    $query_cond
                    $condOsCampoExtra
                    $cond_posto
                    {$condPostoTestes}
                    GROUP BY tbl_os.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_posto_fabrica.contato_email
                    ORDER BY tbl_os.posto";
       
        $query  = $pdo->query($sql);
        $res    = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res;

    }

    public function relacionaExtratoRecebimentoOS($fabrica, $posto, $extrato_recebimento, $array_os){

        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }elseif(empty($extrato_recebimento)){
            throw new Exception("Extrato não informado para relacionar as OSs com o extrato para o posto : {$posto}");
        }   
        $pdo = $this->_model->getPDO();
    
        $sql = "UPDATE tbl_os_extra SET extrato_recebimento = $extrato_recebimento WHERE os IN (".implode(',',$array_os).")";
        $query  = $pdo->query($sql);

        $sql2 = "UPDATE tbl_extrato SET protocolo = 'extrato_recebimento' WHERE extrato = {$extrato_recebimento}";
        $query2  = $pdo->query($sql2);

        if(!$query2){
            throw new \Exception("Erro ao relacionar OS com Extrato para o posto : {$posto}");
        }

        return true;

    }


    public function calcula($extrato = "", $posto = null, $con = null, $garantia = null) {
        if (is_null($con)) {
            $pdo = $this->_model->getPDO();
        }

        if (!empty($extrato)) {
            $this->_extrato = $extrato;
        }

        /* Calcula OS e seus Itens */
        $sql = " SELECT
                ROUND(SUM(tbl_os.mao_de_obra)::numeric, 2) as total_mo,
                ROUND(SUM(tbl_os.qtde_km_calculada)::numeric, 2) as total_km,
                ROUND(SUM(tbl_os.pecas)::numeric, 2) as total_pecas,
                ROUND(SUM(tbl_os.valores_adicionais)::numeric, 2) as total_adicionais,
                ROUND(tbl_extrato.avulso::numeric, 2) as avulso
            FROM tbl_os
            INNER JOIN tbl_os_extra USING(os)
            INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
            WHERE tbl_os_extra.extrato = {$this->_extrato}
        GROUP BY tbl_extrato.avulso
        ";
        if (is_null($con)) {
            $query  = $pdo->query($sql);
            $res    = $query->fetch(\PDO::FETCH_ASSOC);
        } else {
            $query  = pg_query($con, $sql);
            $res    = pg_fetch_assoc($query);
        }

//echo "<pre>".print_r($res,1)."</pre>";exit;
        if (false === $res) {
            $total = 0;
            $total_mo = 0;
            $total_km = 0;
            $total_pecas = 0;
            $total_adicionais = 0;
            $avulso = 0;
        } else {
            $total_mo         = (!empty($res['total_mo']))         ? $res['total_mo']         : 0;
            $total_km         = (!empty($res['total_km']))         ? $res['total_km']         : 0;
            $total_pecas      = ($res['total_pecas'] != "0")       ? $res['total_pecas']      : 0;
            $total_adicionais = (!empty($res['total_adicionais'])) ? $res['total_adicionais'] : 0;
            $avulso           = (strlen($res['avulso']) > 0)       ? $res['avulso'] : 0;
        }
     
        if ($preco_fixo_extrato > 0) {
            $total_mo = $preco_fixo_extrato;
        }

        $total = $total_mo + $total_km + $total_pecas + $total_adicionais + $avulso;

        $sql = "UPDATE
                tbl_extrato
            SET
                total           = {$total},
                mao_de_obra     = {$total_mo},
                pecas           = {$total_pecas},
                deslocamento    = {$total_km},
                valor_adicional = {$total_adicionais}
            WHERE
                extrato = {$this->_extrato}
        ";
        if (is_null($con)) {
            $query  = $pdo->query($sql);
        } else {
            $query  = pg_query($con, $sql);
        }
        return $total;

    }


    public function calculaExtratoRecebimento($extrato, $fabrica, $oSs) {
        if (is_null($con)) {
            $pdo = $this->_model->getPDO();
        }


            //verifica se tem item com servico de mau uso
            $sql = " SELECT
                ROUND(SUM(tbl_os.qtde_km_calculada)::numeric, 2) as total_km,
                ROUND(SUM(tbl_os.mao_de_obra)::numeric, 2) as total_mo,
                ROUND(SUM(tbl_os_item.preco)::numeric, 2) as total_pecas,
                ROUND(SUM(tbl_os.valores_adicionais)::numeric, 2) as total_adicionais
            FROM tbl_os
            INNER JOIN tbl_os_extra USING(os)
            INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato_recebimento
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$fabrica}
            WHERE tbl_extrato.fabrica = $fabrica
              AND tbl_os_extra.extrato_recebimento = $extrato
              AND tbl_os.os in (".implode(',',$oSs).")
              AND tbl_servico_realizado.descricao = 'Mau Uso'";
//exit($sql);
           $query = $pdo->query($sql);
           $res   = $query->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($res)) {
                $total_mo[]          = (!empty($res['total_mo']))         ? $res['total_mo']         : 0;
                $total_km[]          = (!empty($res['total_km']))         ? $res['total_km']         : 0;
                $total_pecas[]       = ($res['total_pecas'] != "0")       ? $res['total_pecas']      : 0;
                $total_adicionais[]  = (!empty($res['total_adicionais'])) ? $res['total_adicionais'] : 0;
                $avulso[]            = (strlen($res['avulso']) > 0)       ? $res['avulso']           : 0;
            } else {
           
                $total[]             = 0;
                $total_mo[]          = 0;
                $total_km[]          = 0;
                $total_pecas[]       = 0;
                $total_adicionais[]  = 0;
                $avulso[]            = 0;
            }

        $total = array_sum($total_mo) + array_sum($total_km) + array_sum($total_pecas) + array_sum($total_adicionais) + array_sum($avulso);


        $sql = "UPDATE
                tbl_extrato
            SET
                total           = ".array_sum($total).",
                mao_de_obra     = ".array_sum($total_mo).",
                pecas           = ".array_sum($total_pecas).",
                deslocamento    = ".array_sum($total_km).",
                valor_adicional = ".array_sum($total_adicionais)."
            WHERE
                extrato = {$extrato}
        ";
        if (is_null($con)) {
            $query  = $pdo->query($sql);
        } else {
            $query  = pg_query($con, $sql);
        }
        return $total;

    }


    public function verificaMaoObraFixa($extrato) {
        $pdo = $this->_model->getPDO();

        if (!$extrato) {
            throw new \Exception("Extrato não informado.");
        }

        $sql = "SELECT DISTINCT contrato, campo_extra->>'valor_mao_obra_fixa' AS mao_obra_fixa
                  FROM tbl_contrato_os 
                  JOIN tbl_contrato ON tbl_contrato_os.contrato = tbl_contrato.contrato AND tbl_contrato.fabrica = {$this->_fabrica}
                  JOIN tbl_os_extra ON tbl_os_extra.os = tbl_contrato.os 
                 WHERE tbl_os_extra.extrato = {$extrato}
                   ;";
        $query  = $pdo->query($sql);

  
        if (!$query) {
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao executar {$query}: {$this->_erro}");
        }
        $result = $query->fetchAll();

        foreach ($result as $key => $rows) {
            $total_mo += $rows["mao_obra_fixa"];
        }

        $sqlUpdate = "UPDATE tbl_extrato SET mao_de_obra=mao_de_obra+'$total_mo' WHERE extrato = {$extrato} AND fabrica = {$this->_fabrica}";
        $queryUpdate  = $pdo->query($sqlUpdate);
        if (!$queryUpdate) {
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao atualizar a Mão de Obra {$queryUpdate}: {$this->_erro}");
        }

    }

 
}

