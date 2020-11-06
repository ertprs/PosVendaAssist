<?php
use Posvenda\Model\Extrato as ExtratoModel;
/**
* Class Extrato Imbera
*/
class ExtratoImbera
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
    
    public function calcula($extrato = "", $posto = null, $unidade_negocio = null, $con = null, $garantia = null) {
        if (is_null($con)) {
            $pdo = $this->_model->getPDO();
        }

        if (!empty($extrato)) {
            $this->_extrato = $extrato;
        }

		if (!empty($posto) and !empty($unidade_negocio)) {
			$preco_fixo_extrato = 0 ;
            if(empty($garantia)) {
				$preco_fixo_extrato = $this->verificaPostoPrecoFixoExtrato($posto, $unidade_negocio, $con, $extrato);
			}

            if ($preco_fixo_extrato > 0) {

                $sql = "UPDATE tbl_os SET mao_de_obra = 0
                                     FROM tbl_os_extra
                                    WHERE extrato = {$this->_extrato}
                                      AND tbl_os.os = tbl_os_extra.os
                                      AND tbl_os.fabrica = {$this->_fabrica};";
                if (is_null($con)) {
                    $query  = $pdo->query($sql);
                } else {
                    $query  = pg_query($con, $sql);
                }
                if (!$query) {
                    if (is_null($con)) {
                        $this->_erro = $pdo->errorInfo();
                    } else {
                         $this->_erro = pg_last_error();
                    }
                    throw new \Exception("Erro ao executar {$query}: {$this->_erro}");
                }
            }

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


    public function extratoUnidadeNegocio($extrato, $unidade_negocio) {
        $pdo = $this->_model->getPDO();

        if (!$extrato) {
            throw new \Exception("Extrato não informado.");
        }

        $sql = "INSERT INTO tbl_extrato_agrupado (
                                                    extrato,
                                                    codigo
                                                 ) VALUES (
                                                    {$extrato},
                                                    '{$unidade_negocio}'
                                                 );";
        $query  = $pdo->query($sql);

        if (!$query) {
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao executar {$query}: {$this->_erro}");
        }

    }

    public function verificaPostoPrecoFixoExtrato($posto = null, $unidade_negocio, $con = null, $extrato = null) {
        if (is_null($con)) {
            $pdo = $this->_model->getPDO();
        }

        if (!$posto) {
            throw new \Exception("Posto não informado.");
        }

		if($extrato) {
			$sql = " SELECT extrato
					FROM tbl_extrato
					WHERE extrato = {$extrato}
					and fabrica = {$this->_fabrica}
					AND protocolo ~* '^garantia';
			";
			if (is_null($con)) {
				$query  = $pdo->query($sql);
				$res    = $query->fetch(\PDO::FETCH_ASSOC);
			} else {
				$query  = pg_query($con, $sql);
				$res    = pg_fetch_assoc($query);
			}

			if($res) {
				return 0 ;
			}
		}
        $sql = "SELECT preco AS valor_mao_obra
                FROM tbl_posto_preco_unidade
                JOIN tbl_distribuidor_sla USING(distribuidor_sla)
                WHERE tbl_posto_preco_unidade.fabrica = {$this->_fabrica}
                AND tbl_posto_preco_unidade.posto = {$posto}
                AND tbl_distribuidor_sla.unidade_negocio = '{$unidade_negocio}'";
        if (is_null($con)) {
            $query  = $pdo->query($sql);
            $res    = $query->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $query  = pg_query($con, $sql);
            $res    = pg_fetch_all($query);
        }

        $valor_mao_obra = 0;

        if (!empty($res)) {
            $valor_mao_obra = $res[0]['valor_mao_obra'];
        }

        return $valor_mao_obra;

    }

   public function relacionaExtratoOS($fabrica, $posto, $extrato = "", $dia_extrato = "", $marca = null, $fora_garantia = null, $unidade_negocio){

        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }elseif(empty($extrato)){
            throw new Exception("Extrato não informado para relacionar as OSs com o extrato para o posto : {$posto}");
        }elseif (empty($dia_extrato)) {
            throw new Exception("Dia de Geração de Extrato não informado para relacionar as OSs com o extrato para o posto : {$posto}");
        }

        $pdo = $this->_model->getPDO();

        if (!is_null($fora_garantia)) {
            $innerJoinTipoAtendimento = "
                LEFT JOIN tbl_hd_chamado_extra ON tbl_os.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                LEFT JOIN tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_hd_chamado_extra.hd_chamado
            ";
            if($fora_garantia == 't') {
                $whereTipoAtendimento = " AND (tbl_hd_chamado_cockpit.hd_chamado IS NOT NULL OR tbl_tipo_atendimento.fora_garantia IS TRUE) ";
            }else{
                $whereTipoAtendimento = " AND (tbl_hd_chamado_cockpit.hd_chamado IS NULL AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE) ";
            }
        }

        $os_garantia = \Posvenda\Regras::get("os_garantia", "extrato", $this->_fabrica);

        if ($os_garantia) {
            $whereOsGarantia = "AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE";
        }

        $unidadesGeraExtrato = \Posvenda\Regras::getUnidades("geraExtrato", $this->_fabrica);

        if (in_array($unidade_negocio, $unidadesGeraExtrato)) {
            $where = " AND JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais) = '$unidade_negocio'";
        } else {

            $unidadesMinasGerais = \Posvenda\Regras::getUnidades("unidadesMinasGerais", $this->_fabrica);

            $condUnidadesMinas = implode("','", $unidadesMinasGerais);

            $where = " AND JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais) IN('{$condUnidadesMinas}')";

        }
        $sql = "
            SELECT tbl_os.os
            FROM tbl_os
            INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
            INNER JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
            LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$fabrica}
            {$innerJoinTipoAtendimento}
            WHERE tbl_os.fabrica = $fabrica
            AND tbl_os.posto = $posto
            AND tbl_os_extra.extrato IS NULL
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_os.finalizada <= '$dia_extrato'
            {$where}
            {$whereOsGarantia}
            {$whereTipoAtendimento}
        ";

        $query  = $pdo->query($sql);

        if(!$query){
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao relacionar OS com Extrato para o posto : {$posto}");
        }

        $res = $query->fetchAll();

        $nao_gera_extrato_os_auditoria = \Posvenda\Regras::get("nao_gera_extrato_os_auditoria", "extrato", $this->_fabrica);

        foreach ($res as $os) {
            if ($nao_gera_extrato_os_auditoria == true) {
                $osClass = new \Posvenda\Os($fabrica, $os['os']);

                $intervencao = $osClass->_model->verificaOsIntervencao();

                if($intervencao != false){
                    continue;
                }
            }

            $sql = "UPDATE tbl_os_extra SET extrato = $extrato WHERE os = {$os['os']}";
            $query  = $pdo->query($sql);

            if(!$query){
                throw new \Exception("Erro ao relacionar OS com Extrato para o posto : {$posto}");
            }
        }

        return true;

    }
}

