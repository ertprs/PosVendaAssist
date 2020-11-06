<?php

#namespace Posvenda;

use Posvenda\Model\Extrato as ExtratoModel;

class Extrato {

    public $_model;
    public $_erro;

    protected $_fabrica;

    private $_extrato;
    private $_qtde_oss;
    private $_qtde_peca;
    private $_imposto_al;
    private $_total_os;
    private $_avulso = 0;
    private $_extrato_lancamento;
    private $_posto;
    private $_gera_extrato;

    public function __construct($fabrica, $extrato) {
        if (!empty($extrato)) {
            $this->_extrato = $extrato;
        }

        $this->_fabrica = $fabrica;

        if(!empty($this->_fabrica)){
            $this->_model = new ExtratoModel($this->_fabrica);
        }

    }

    public function getExtrato(){
        return $this->_extrato;
    }

    public function verificaGeraExtrato() {
        $posto_interno_nao_gera = \Posvenda\Regras::get("posto_interno_nao_gera", "extrato", $this->_fabrica);
        if ($posto_interno_nao_gera) {
            $this->_gera_extrato = "AND tbl_tipo_posto.posto_interno IS NOT TRUE";
        }
    }

    public function calcula($extrato = "", $adicional_avulso = 0, $debito_avulso = 0) {

        if (!empty($extrato)) {
            $this->_extrato = $extrato;
        }

        $pdo = $this->_model->getPDO();

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
        $query  = $pdo->query($sql);
        $res    = $query->fetch(\PDO::FETCH_ASSOC);

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
			$sql = "SELECT round(sum(valor)::numeric,2) as avulso from tbl_extrato_lancamento where extrato = {$this->_extrato} ";
	        $query  = $pdo->query($sql);
		    $res    = $query->fetch(\PDO::FETCH_ASSOC);
			if(!(false === $res)) {
				$avulso           = (strlen($res['avulso']) > 0)       ? $res['avulso'] : 0;
			}

        }
        
    	if(in_array($this->_fabrica, array(153,161,164))){
    		$total = $total_mo + $total_km + $total_adicionais + $avulso;
    	}else{
    		$total = ($total_mo + $total_km + $total_pecas + $total_adicionais + $avulso ) ;
    	}

        $sql = "UPDATE
                tbl_extrato
            SET
                total           = {$total},
                mao_de_obra     = {$total_mo},
                pecas           = {$total_pecas},
                deslocamento    = {$total_km},
				valor_adicional = {$total_adicionais},
				avulso = $avulso
            WHERE
                extrato = {$this->_extrato}
        ";

        $query  = $pdo->query($sql);

        return $total;

    }

    public function getPeriodoDias($qtde_dias = 0, $dia_extrato = ""){
        global $_serverEnvironment;

        if($_serverEnvironment == "production"){
            if(empty($dia_extrato)){
                throw new \Exception("Dia de geração do Extrato não informado");
            }

            $pdo = $this->_model->getPDO();

            $sql = "SELECT ('$dia_extrato'::date - INTERVAL '1 month' + INTERVAL '{$qtde_dias} days')::date AS data";

            $query = $pdo->query($sql);
            $res   = $query->fetch(\PDO::FETCH_ASSOC);
            $res   = $res['data'];
        }else{
            $res = date("Y-m-d");
        }

        return $res;

    }

    public function getOsPostoGarantia($dia_extrato, $fabrica, $extraJoin, $extraWhere) {
        $pdo = $this->_model->getPDO();

        $sql = "
            SELECT
                tbl_posto_fabrica.posto,
                CASE WHEN tbl_hd_chamado_cockpit.hd_chamado notnull THEN 't' ELSE 'f' END AS fora_garantia
            FROM tbl_os
            INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
			LEFT JOIN tbl_hd_chamado_extra ON tbl_os.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			LEFT JOIN tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_hd_chamado_extra.hd_chamado
            {$extraJoin}
            WHERE tbl_os.fabrica = {$fabrica}
            AND tbl_os_extra.extrato IS NULL
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_os.os NOT IN (SELECT os FROM tbl_auditoria_os WHERE os = tbl_os.os AND liberada IS NULL)
            AND tbl_os.finalizada <= '{$dia_extrato}'
            {$extraWhere}
            GROUP BY tbl_posto_fabrica.posto, fora_garantia
        ";
        $query  = $pdo->query($sql);

        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getOsPosto($dia_extrato = "", $fabrica = "", $marca = false, $query_cond = '', $posto = null, $agrupa_tipo_atendimento = false){
        global $_serverEnvironment;

        if ($_serverEnvironment == 'development') {
//            $condPostoTestes = "AND tbl_os.posto = 6359";
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

        if ($this->_fabrica == 160) {
            $cond_nao_gera_os_auditoria_termo = " AND tbl_os.os NOT IN( SELECT ao.os FROM tbl_auditoria_os ao WHERE ao.os = tbl_os.os AND ao.liberada IS NULL AND ao.reprovada IS NULL ) ";    
        }

        if ($this->_fabrica == 161) {
            $cond_nao_gera_extrato = " JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto.posto JOIN tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_linha.fabrica = {$this->_fabrica} AND tbl_linha.codigo_linha <> '03' ";
        }

        // if ($this->_fabrica == 157){ Retirei HD-7098250. Se for voltar fizeram errado a condição fazer igual as acimas linha +- 190
        //     $join_nao_gera_extrato_reprovada = " JOIN tbl_auditoria_os ON tbl_auditoria_os.os = tbl_os.os ";
        //     $cond_nao_gera_extrato_reprovada = " AND tbl_auditoria_os.reprovada IS NULL ";
        // }

        if (!empty($tipo_atendimento_nao_gera)) {
            $condTipoAtendimento = " AND tbl_os.tipo_atendimento <> $tipo_atendimento_nao_gera ";
        }

        if(!empty($fora_garantia_nao_gera)){
            if (is_bool($fora_garantia_nao_gera))
                $fora_garantia_nao_gera = ($fora_garantia_nao_gera == 1) ? 'true' : 'false';

		if(in_array($this->_fabrica, [167,203])){
			$sqlAtendimento = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = $this->_fabrica AND fora_garantia = {$fora_garantia_nao_gera} AND lower(descricao) <> 'garantia recusada'";
		}else{
	            	$sqlAtendimento = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = $this->_fabrica AND fora_garantia = {$fora_garantia_nao_gera}";
		}

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
        if ($agrupa_tipo_atendimento) {
            $campoTipoAtendimento = ",tbl_os.tipo_atendimento";
        }
        if(in_array($this->_fabrica, [203])){
            $this->verificaGeraExtratoRevenda();
        }

        $this->verificaGeraExtrato();

        if ($marca === false) {

            $sql = "SELECT  tbl_os.posto,
                            COUNT(*) AS qtde,
                            tbl_posto.nome,
                            tbl_posto_fabrica.codigo_posto,
                            tbl_posto_fabrica.contato_email
                            {$campoTipoAtendimento}
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
                    WHERE   tbl_os.fabrica = {$fabrica}
                    AND     tbl_os_extra.extrato IS NULL
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
                    GROUP BY  tbl_os.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_posto_fabrica.contato_email {$campoTipoAtendimento}
                    ORDER BY tbl_os.posto";
        } else {
            $sql = "SELECT  tbl_os.posto, COUNT(*) AS qtde, tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_marca.marca
                    FROM tbl_os
                    JOIN tbl_os_extra USING (os)
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$fabrica}
                    INNER JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = {$fabrica}
                    JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                    JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
                    LEFT JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$fabrica}
                    $joinOsCampoExtra
                    $join_nao_gera_extrato_reprovada
                    {$cond_nao_gera_extrato}
                    WHERE tbl_os.fabrica = {$fabrica}
                    AND tbl_os_extra.extrato IS NULL
                    AND tbl_os.excluida IS NOT TRUE
                    AND tbl_os.posto <> 6359
                    AND tbl_os.finalizada::date <= '{$dia_extrato}'
                    {$cond_nao_gera_os_auditoria}
                    {$cond_nao_gera_os_auditoria_termo}
                    {$cond_nao_gera_extrato_reprovada}
                    {$this->_gera_extrato}
                    $query_cond
                    $condOsCampoExtra
					$cond_posto
                    {$condPostoTestes}
                    GROUP BY tbl_os.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_marca.marca
                    ORDER BY tbl_os.posto";
        }
        $query  = $pdo->query($sql);
        $res    = $query->fetchAll(\PDO::FETCH_ASSOC);
//exit($sql);
        return $res;

    }

    public function insereExtratoPosto($fabrica, $posto, $dia_extrato, $mao_de_obra = 0, $pecas = 0, $total = 0, $avulso = 0, $fora_garantia = null){

        $fabricaLiberaExtrato = \Posvenda\Regras::get("fabrica_libera_extrato", "extrato", $this->_fabrica);
        
        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }
        
        $pdo = $this->_model->getPDO();

        $campos = array(
            "fabrica",
            "posto",
            "data_geracao",
            "mao_de_obra",
            "pecas",
            "total",
            "avulso"
        );

        if ($fabrica == 177 || $fabricaLiberaExtrato){
            $campos[] = "liberado";
        }

        $valores = array(
            $fabrica,
            $posto,
            "'$dia_extrato'",
            $mao_de_obra,
            $pecas,
            $total,
            $avulso
        );

        if ($fabrica == 177 || $fabricaLiberaExtrato){
            $valores[] = "current_date";
        }

        $sql = "
            INSERT INTO tbl_extrato
            (".implode(", ", $campos).")
            VALUES
            (".implode(", ", $valores).")
        ";
        $query  = $pdo->query($sql);
        if($query){
            $this->_extrato = $this->_model->getPDO()->lastInsertId("seq_extrato");

            if (!is_null($fora_garantia)) {
                $sql = "
                    UPDATE tbl_extrato SET
                        protocolo = '$fora_garantia'
                    WHERE fabrica = {$this->_fabrica}
                    AND extrato = {$this->_extrato}
                ";
                $query = $pdo->query($sql);

                if (!$query) {
                    $this->_erro = $pdo->errorInfo();
                    throw new \Exception("Erro ao inserir o Extrato para o posto : {$posto}");
                }
            }

            if ($fabrica == 177 || $fabricaLiberaExtrato){
                $sql = "
                    UPDATE tbl_extrato SET
                        aprovado = current_timestamp
                    WHERE fabrica = {$this->_fabrica}
                    AND extrato = {$this->_extrato}
                ";
                $query = $pdo->query($sql);
            
                if (!$query) {
                    $this->_erro = $pdo->errorInfo();
                    throw new \Exception("Erro ao aprovar extrato para o posto : {$posto}");
                }
            }

        }else{
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao inserir o Extrato para o posto : {$posto}");
        }

        $sqlParametrosFabrica = "SELECT parametros_adicionais
                                 FROM tbl_fabrica
                                 WHERE fabrica = {$this->_fabrica}";
        $queryParametros = $pdo->query($sqlParametrosFabrica);

        $result = $queryParametros->fetchAll();

        $jsonParametros = json_decode($result[0]["parametros_adicionais"], true);

        if (isset($jsonParametros["usaNotaFiscalServico"]) && $jsonParametros["usaNotaFiscalServico"] == true) {

            $sql = "INSERT INTO tbl_extrato_status (extrato, data, obs, fabrica)
                    VALUES ({$this->_extrato}, current_timestamp, 'Aguardando Envio da Nota Fiscal', {$this->_fabrica})";
            $query  = $pdo->query($sql);

            if (!$query) {
                throw new \Exception("Erro ao inserir o status no extrato");
            }

        }

        return true;

    }


    public function verificaGeraExtratoRevenda() {
        $tipo_revenda_nao_gera = \Posvenda\Regras::get("tipo_revenda_nao_gera", "extrato", $this->_fabrica);
        if ($tipo_revenda_nao_gera) {
            $this->_gera_extrato = "AND tbl_tipo_posto.tipo_revenda IS NOT TRUE";
        }
    }


    public function atualizaAvulsosPosto($fabrica, $posto, $extrato = "", $fora_garantia = null){

        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }elseif(empty($extrato)){
            throw new Exception("Extrato não informado para a atualizar o valor avulso para o posto : {$posto}");
        }

        if ($fabrica == 158) {
            $whereTipoExtrato = ($fora_garantia == "t") ? "AND conta_garantia != 't'" : "AND conta_garantia = 't'";
        }

        if ($fabrica == 160) {
            $whereDesc = " AND tbl_extrato_lancamento.descricao != 'bonificacao'";
        }

        $pdo = $this->_model->getPDO();

        $sql = "UPDATE tbl_extrato_lancamento SET extrato = $extrato
                WHERE tbl_extrato_lancamento.fabrica = $fabrica
                AND tbl_extrato_lancamento.extrato IS NULL
                {$whereTipoExtrato}
                {$whereDesc}
                AND tbl_extrato_lancamento.posto = $posto";
        $query  = $pdo->query($sql);

        if(!$query){
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao atualizar o valor avulso para o posto : {$posto}");
        }

        return true;

    }

    /* hd-4367465 */
    public function relacionaOsExtrato90($fabrica, $posto, $extrato, $dia_extrato, $os) 
    {
        if (empty($fabrica)) {
            $fabrica = $this->fabrica;
        }

        $pdo = $this->_model->getPDO();

        $sql = "UPDATE tbl_os_extra SET extrato = {$extrato} WHERE os = {$os}";
        $query = $pdo->query($sql);

        if(!$query){
            throw new \Exception("Erro ao relacionar OS com Extrato para o posto : {$posto};");
        }

        return true;
    }
    /* fim hd-4367465 */

    public function relacionaExtratoOS($fabrica, $posto, $extrato = "", $dia_extrato = "", $marca = null, $fora_garantia = null, $tipo_atendimento = null){

        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }elseif(empty($extrato)){
            throw new Exception("Extrato não informado para relacionar as OSs com o extrato para o posto : {$posto}");
        }elseif (empty($dia_extrato)) {
            throw new Exception("Dia de Geração de Extrato não informado para relacionar as OSs com o extrato para o posto : {$posto}");
        }

        $pdo = $this->_model->getPDO();

        if ($marca == null) {
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

            if ($this->_fabrica == 151) {
                $whereSerieControle = ' AND tbl_os.serie NOT IN(SELECT tbl_serie_controle.serie FROM tbl_serie_controle WHERE tbl_serie_controle.produto = tbl_os.produto AND tbl_serie_controle.fabrica = 151 AND tbl_serie_controle.serie = tbl_os.serie)';
                $whereSerieControle = ""; /* HD-3967545 */
            }

            $fora_garantia_nao_gera = \Posvenda\Regras::get("fora_garantia_nao_gera", "extrato", $this->_fabrica);

            if(!empty($fora_garantia_nao_gera)){
                if (is_bool($fora_garantia_nao_gera))
                    $fora_garantia_nao_gera = ($fora_garantia_nao_gera == 1) ? 'true' : 'false';

                if(in_array($this->_fabrica, [167,203])){
                        $sqlAtendimento = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = $this->_fabrica AND fora_garantia = {$fora_garantia_nao_gera} AND lower(descricao) <> 'garantia recusada'";
                }else{
                        $sqlAtendimento = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = $this->_fabrica AND fora_garantia = {$fora_garantia_nao_gera}";
                }

		$query  = $pdo->query($sqlAtendimento);
                $id_atendimento = $query->fetchAll(\PDO::FETCH_ASSOC);

                $whereTipoAtendimento = " AND tbl_os.tipo_atendimento NOT IN (";
                foreach ($id_atendimento as $value) {
                    $whereTipoAtendimento .= $value['tipo_atendimento'].',';
                }
                $whereTipoAtendimento = substr($whereTipoAtendimento, 0, strlen($whereTipoAtendimento) - 1).')';
            }

            $os_garantia = \Posvenda\Regras::get("os_garantia", "extrato", $this->_fabrica);

            if ($os_garantia) {
                $whereOsGarantia = "AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE";
            }

	    $nao_gera_os_auditoria = \Posvenda\Regras::get("nao_gera_os_auditoria", "extrato", $this->_fabrica);

	    if (!empty($nao_gera_os_auditoria) && $nao_gera_os_auditoria == true) {
            	$cond_nao_gera_os_auditoria = " AND tbl_os.os NOT IN( SELECT ao.os FROM tbl_auditoria_os ao WHERE ao.os = tbl_os.os AND ao.liberada IS NULL AND ao.reprovada IS NULL ) ";
            }

            if($this->_fabrica == 148){
                $whereTipoAtendimento = " and tbl_tipo_atendimento.tipo_atendimento in (".implode(",", $tipo_atendimento).") 
					 --AND (tbl_os.mao_de_obra + tbl_os.qtde_km_calculada + tbl_os.pecas + tbl_os.valores_adicionais) > 0
					 AND tbl_os.data_digitacao >= '2020-01-01 00:00'
                                         AND tbl_os.cancelada IS NOT TRUE";
            }

            $sql = "
                SELECT tbl_os.os
                FROM tbl_os
                INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$fabrica}
                {$innerJoinTipoAtendimento}
                WHERE tbl_os.fabrica = $fabrica
                AND tbl_os.posto = $posto
                AND tbl_os_extra.extrato IS NULL
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.finalizada::DATE <= '$dia_extrato'
                {$whereSerieControle}
                {$whereOsGarantia}
		{$cond_nao_gera_os_auditoria}
                {$whereTipoAtendimento}
            ";
        } else {
            $sql = "
                SELECT DISTINCT tbl_os.os
                FROM tbl_os
                INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
                WHERE tbl_os.fabrica = $fabrica
                AND tbl_os.posto = $posto
                AND tbl_os_extra.extrato IS NULL
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.finalizada::DATE <= '$dia_extrato'
                AND tbl_produto.marca = $marca
            ";
        }
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

        $calcula_os_sem_valor = \Posvenda\Regras::get("calcula_os_sem_valor", "extrato", $this->_fabrica);

        if ($calcula_os_sem_valor == true) {
            $nao_calcula_posto_interno = \Posvenda\Regras::get("nao_calcula_posto_interno", "extrato", $this->_fabrica);

            if ($nao_calcula_posto_interno == true) {
                $wherePostoInterno = "AND tbl_tipo_posto.posto_interno IS NOT TRUE";
            }

            $sql = "
                SELECT DISTINCT tbl_os.os
                FROM tbl_os
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
                INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$fabrica}
                INNER JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os_troca.fabric = {$fabrica} AND tbl_os_troca.ressarcimento IS NOT TRUE
                WHERE tbl_os.fabrica = {$fabrica}
                AND tbl_os_extra.extrato = {$extrato}
                {$wherePostoInterno}
            ";
            $query = $pdo->query($sql);

            if (!$query) {
                throw new Exception("Erro ao calcular OS");
            }

            $res = $query->fetchAll();

            if (count($res) > 0) {
                $classOs = new \Posvenda\Os($fabrica);

                foreach ($res as $os) {
                    $classOs->calculaOs($os["os"]);
                }
            }
        }

        return true;

    }

    public function atualizaValoresAvulsos($fabrica, $extrato){

        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }

        if (empty($extrato)) {
            $extrato = $this->_extrato;
        }

        $pdo = $this->_model->getPDO();

        /* 1 */

        $sql = "UPDATE tbl_extrato
                    SET avulso = (
                        SELECT SUM (valor)
                        FROM tbl_extrato_lancamento
                        WHERE tbl_extrato_lancamento.extrato = {$extrato}
                    )
                WHERE tbl_extrato.fabrica = {$fabrica}
                AND tbl_extrato.extrato = {$extrato}";
        $query  = $pdo->query($sql);

        if(!$query){
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao atualizar os valores dos lançamentos avulsos - Extrato : {$extrato}");
        }
        return true;

    }

    public function verificaLGR($extrato = "", $posto = "", $data_15 = "", $fabrica = "", $lgr_troca_produto = false)
    {

        if (empty($extrato)) {
            $desc_posto = (!empty($posto)) ? "- Posto : {$posto}" : "";
            throw new \Exception("Extrato não informado para a verificação de LGR {$desc_posto}");
        }

        if (empty($posto)) {
            throw new \Exception("Posto não informado para a verificação de LGR - Extrato : {$extrato}");
        }

        if (empty($data_15)) {
            throw new \Exception("Período de geração não informado para a verificação de LGR - Extrato : {$extrato}");
        }

        if (empty($fabrica)) {
            $fabrica = $this->_fabrica;
        }

        if($fabrica == 158){
            $condGarantia = " AND tbl_faturamento.garantia IS TRUE ";
            $camposIntLGR =  " , faturamento, faturamento_item ";
            $camposfaturamento = " , tbl_faturamento_item.faturamento, tbl_faturamento_item.faturamento_item ";            
            $join_pedido=  " JOIN tbl_pedido on tbl_pedido.pedido = tbl_faturamento_item.pedido and tbl_pedido.fabrica = $fabrica ";
            $wheretipopedido = " and tbl_pedido.tipo_pedido = 343 ";
        }

        $condCFOP = '';

        if (!in_array($fabrica, [193])) {
            $condCFOP = "AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')";
        }

        $pdo = $this->_model->getPDO();

        /* 1 */

        if ($lgr_troca_produto == true) {
             $sql = "
                UPDATE tbl_faturamento_item SET
                    extrato_devolucao = $extrato,
                    devolucao_obrig = tbl_os_item.peca_obrigatoria
                FROM tbl_os_item, tbl_os_produto, tbl_os_extra, tbl_faturamento, tbl_peca
                WHERE (
                    tbl_os_item.os_item = tbl_faturamento_item.os_item
                    OR ( tbl_os_item.peca = tbl_faturamento_item.peca AND tbl_os_item.pedido_item = tbl_faturamento_item.pedido_item AND tbl_faturamento_item.os_item IS NULL )
                    OR ( tbl_os_item.peca = tbl_faturamento_item.peca AND tbl_os_item.pedido = tbl_faturamento_item.pedido AND tbl_faturamento_item.os_item IS NULL )
                )
                AND tbl_peca.peca = tbl_os_item.peca
                AND tbl_os_item.os_produto = tbl_os_produto.os_produto
                AND tbl_os_produto.os = tbl_os_extra.os
                AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                AND tbl_faturamento.posto = $posto
                AND tbl_faturamento.fabrica = $fabrica
                AND tbl_faturamento.emissao <='$data_15'
                AND tbl_faturamento.cancelada IS NULL
                AND tbl_faturamento_item.extrato_devolucao IS NULL
                AND (tbl_os_item.peca_obrigatoria OR tbl_peca.produto_acabado IS TRUE)
                AND tbl_peca.aguarda_inspecao IS NOT TRUE
                $condCFOP
                $condGarantia
            ";
        } else {
            $sql = "
                UPDATE tbl_faturamento_item SET
                    extrato_devolucao = $extrato,
                    devolucao_obrig = tbl_os_item.peca_obrigatoria
                FROM tbl_os_item, tbl_os_produto, tbl_os_extra, tbl_faturamento, tbl_peca
                WHERE (
                    tbl_os_item.os_item = tbl_faturamento_item.os_item
                    OR ( tbl_os_item.peca = tbl_faturamento_item.peca AND tbl_os_item.pedido_item = tbl_faturamento_item.pedido_item AND tbl_faturamento_item.os_item IS NULL )
                    OR ( tbl_os_item.peca = tbl_faturamento_item.peca AND tbl_os_item.pedido = tbl_faturamento_item.pedido AND tbl_faturamento_item.os_item IS NULL )
                )
                AND tbl_peca.peca = tbl_os_item.peca
                AND tbl_os_item.os_produto = tbl_os_produto.os_produto
                AND tbl_os_produto.os = tbl_os_extra.os
                AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                AND tbl_faturamento.posto = $posto
                AND tbl_faturamento.fabrica = $fabrica
                AND tbl_faturamento.emissao <='$data_15'
                AND tbl_faturamento.cancelada IS NULL
                AND tbl_faturamento_item.extrato_devolucao IS NULL
                AND tbl_os_item.peca_obrigatoria IS TRUE
                AND tbl_peca.aguarda_inspecao IS NOT TRUE
                $condCFOP
                $condGarantia
            ";
        }

        $query  = $pdo->query($sql);

        if(!$query){
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 1 */" .var_dump($this->_erro));
        }

        /* 3 */


        $sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde $camposIntLGR)
                SELECT
                tbl_extrato.extrato,
                tbl_extrato.posto,
                tbl_faturamento_item.peca,
                SUM (tbl_faturamento_item.qtde)
                $camposfaturamento
                FROM tbl_extrato
                JOIN tbl_faturamento_item ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao
                $join_pedido
                WHERE tbl_extrato.fabrica = $fabrica
                AND tbl_extrato.extrato = $extrato
                $wheretipopedido 
                GROUP BY tbl_extrato.extrato,
                tbl_extrato.posto,
                tbl_faturamento_item.peca 
                $camposfaturamento ";
        $query  = $pdo->query($sql);

        if(!$query){
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 3 */");
        }

    }
    public function liberaExtrato($extrato = ""){

        if(empty($extrato)){
            throw new \Exception("Extrato não informado para a liberação automatica");
        }

        $pdo = $this->_model->getPDO();

        $sql = "UPDATE tbl_extrato SET aprovado = CURRENT_DATE, liberado = CURRENT_DATE WHERE extrato = {$extrato}";
        $query  = $pdo->query($sql);

        if(!$query){
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao liberar automaticamente o extrato : {$extrato}");
        }

    }

    public function verificaValorMinimoExtrato($valor_minimo = 0, $total_extrato = 0){

        if($total_extrato < $valor_minimo){
            $valor_minimo = number_format($valor_minimo, 2, ",", ".");
            throw new \Exception("Total do Extrato menor que o valor mínimo - Valor mínimo : R$ {$valor_minimo} e Total: $total ");
        }

        return true;

    }
    
    public function recusaOs( $extrato, $os, $observacao){
        $pdo = $this->_model->getPDO();

        $sql ="INSERT INTO tbl_os_status (
                            os        ,
                            status_os ,
                            observacao,
                            extrato
                    ) VALUES (
                            {$os} ,
                            13   ,
                            E'{$observacao}',
                            {$extrato}
                    )";
        $query  = $pdo->query($sql);

        if(!$query){
            throw new \Exception("Erro ao incluir status na OS: {$os} ");
        }

        $sql =" UPDATE tbl_os SET data_fechamento = NULL, finalizada = NULL
                    WHERE  tbl_os.os = {$os} and fabrica = {$this->_fabrica};
                ";
        $query  = $pdo->query($sql);

        if(!$query){
            throw new \Exception("Erro ao reabrir OS : {$os}");
        }

    }

    public function valorTotalOs($os) {
        /*
         *    Traz valor total da OS
         */
        $pdo = $this->_model->getPDO();

        $sql = "SELECT
                    SUM(tbl_os.mao_de_obra) as total_mo,
                    SUM(tbl_os.qtde_km_calculada) as total_km,
                    SUM(tbl_os.pecas) as total_pecas,
                    SUM(tbl_os.valores_adicionais) as total_adicionais
                FROM tbl_os
                WHERE tbl_os.os = {$os} ";

        $query  = $pdo->query($sql);
        $res    = $query->fetchAll(\PDO::FETCH_ASSOC);

        if(pg_num_rows($res) > 0){

            $total_mo               = (pg_fetch_result($res, 0, 'total_mo'))         ? pg_fetch_result($res, 0, 'total_mo')    : 0;
            $total_km               = (pg_fetch_result($res, 0, 'total_km'))         ? pg_fetch_result($res, 0, 'total_km')    : 0;
            $total_pecas            = (pg_fetch_result($res, 0, 'total_pecas'))      ? pg_fetch_result($res, 0, 'total_pecas') : 0;
            $total_adicionais       = (pg_fetch_result($res, 0, 'total_adicionais')) ? pg_fetch_result($res, 0, 'total_mo')    : 0;

            $total = $total_mo + $total_km + $total_pecas + $total_adicionais;

        }else{
            $total = 0;
        }

        return $total;

    }

    public function lancaAvulsoValorOs($os, $extrato){
        $valor_os = $this->valorTotalOs;

        $pdo = $this->_model->getPDO();

        $sql =" INSERT INTO tbl_extrato_lancamento (
                             posto           ,
                             fabrica         ,
                             lancamento      ,
                             historico       ,
                             debito_credito  ,
                             valor           ,
                             automatico
                         ) SELECT   posto,
                                    $fabrica,
                                    197,
                                    'Débito da OS  ".$os." recusada do extrato ".$extrato."',
                                    'D',
                                    -$valor_os,
                                    true
                            FROM tbl_os
                            WHERE os = $os
                            AND fabrica = $this->_fabrica ";

        $query  = $pdo->query($sql);

        if(!$query){
            throw new \Exception("Erro ao incluir débito para o extrato : $extrato ");
        }

        $sql =" INSERT INTO tbl_extrato_lancamento (
                             posto           ,
                             fabrica         ,
                             lancamento      ,
                             historico       ,
                             debito_credito  ,
                             valor           ,
                             automatico      ,
                             extrato
                         ) SELECT   posto,
                                    $fabrica,
                                    198,
                                    'Crédito da OS  ".$os." recusada do extrato ".$extrato."',
                                    'C',
                                    $valor_os,
                                    true ,
                                    {$extrato}
                            FROM tbl_os
                            WHERE os = $os
                            AND fabrica = $fabrica ";

        $query  = $pdo->query($sql);

        if(!$query){
            throw new \Exception("Erro ao incluir crédito para proxímo extrato");
        }
    }

    public function removeOsExtrato($os){

        $pdo = $this->_model->getPDO();
        $sql ="UPDATE tbl_os_extra SET extrato = NULL WHERE tbl_os_extra.os = $os ";
        $query  = $pdo->query($sql);

        if(!$query){
            throw new \Exception("Erro ao remover a OS {$os} do extrato {$extrato}");
        }
    }

    public function verificaTotalAvulsos($posto,$extrato){
        /*
        *   somar todos os avulsos a serem lancado dps total do extrato
        *   se o (total do extrat - total avulsos)<= 0 erro === rollback
        *   se for maior q zero pegar todos os avulsos para lancar  e fazer o atuluzaavulso para linkar o avulso com o extrato que vai ser gerado
        *   valor tootal do avulso e colocar no valor avulso no extrato
        *   depos calcular o extrato novamente
        */

        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }elseif(empty($extrato)){
            throw new Exception("Extrato não informado para a atualizar o avulso para o posto {$posto}");
        }elseif(empty($posto)){
            throw new Exception("Extrato não informado para a atualizar o avulso para o extrato {$extrato}");
        }

        $pdo = $this->_model->getPDO();

        $sql = "SELECT SUM(valor) from tbl_extrato_lancamento
                WHERE tbl_extrato_lancamento.fabrica = $fabrica
                AND tbl_extrato_lancamento.extrato IS NULL
                AND tbl_extrato_lancamento.posto = $posto";

        $query  = $pdo->query($sql);
        if(!$query){
            throw new \Exception("Erro ao atualizar o avulso para o posto {$posto}");
        }
        $res    = $query->fetch(\PDO::FETCH_ASSOC);
        $total_avulsos = pg_fetch_result($res,0,0);

        return $total_avulsos;

    }

    /**
     *  - LGRNovo($extrato, $posto, $fabrica)
     *  Realiza a geração das notas de devolução
     * de peças envolvidas em movimentação de estoque
     *
     * @param $extrato ID do extrato
     * @param $posto ID do posto que devolverá as peças
     * @param $fabrica ID da fábrica que receberá as peças danificadas
     *
     * @return void
     */
    public function LGRNovo($extrato, $posto, $fabrica){

        $pdo = $this->_model->getPDO();

        $sql = "SELECT  DISTINCT
                        tbl_os_extra.extrato,
                        tbl_os_extra.os
                FROM    tbl_os_extra
                JOIN    tbl_os_produto          ON  tbl_os_produto.os                       = tbl_os_extra.os
                JOIN    tbl_os_item             ON  tbl_os_item.os_produto                  = tbl_os_produto.os_produto
                JOIN    tbl_servico_realizado   ON  tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                                                AND tbl_servico_realizado.peca_estoque      IS TRUE
                WHERE   tbl_os_extra.extrato            = $extrato
                AND     tbl_os_item.peca_obrigatoria    IS TRUE
                ";
        $query  = $pdo->query($sql);
        $res    = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($res as $dadosLinha) {
            $os = $dadosLinha['os'];

            $sqlfaturamento = "
                INSERT INTO tbl_faturamento (
                    fabrica,
                    cfop,
                    extrato_devolucao,
                    emissao,
                    saida,
                    total_nota,
                    posto
                ) VALUES (
                    $fabrica,
                    '5949',
                    $extrato,
                    now(),
                    now(),
                    '0' ,
                    $posto
                ) RETURNING faturamento ";
            $resfaturamento = $pdo->query($sqlfaturamento);

            $faturamento_id = $resfaturamento->fetch(\PDO::FETCH_ASSOC);

            $faturamento = $faturamento_id['faturamento'];

            $sql_os = "
                SELECT  tbl_os_item.peca,
                        tbl_os_item.qtde,
                        tbl_os_item.custo_peca,
                        tbl_os_item.os_item
                FROM    tbl_os_produto
                JOIN    tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
                WHERE   tbl_os_produto.os       = $dadosLinha[os]
                AND     tbl_os_item.fabrica_i   = $fabrica ";
            $res_os = $pdo->query($sql_os);
            $dadosItens    = $res_os->fetchAll(\PDO::FETCH_ASSOC);

            foreach($dadosItens as $dados){
                $peca       = $dados['peca'];
                $qtde       = $dados['qtde'];
                $custo_peca = ($dados['custo_peca'] == '')? '0': $dados['custo_peca'] ;
                $os_item    = $dados['os_item'];

                $sql_fat_item_existente = "SELECT faturamento_item FROM tbl_faturamento_item WHERE os = {$os} AND os_item = {$os_item}";
                $query = $pdo->query($sql_fat_item_existente);
                $res_fat_item_existente = $query->fetchAll(\PDO::FETCH_ASSOC);

                if(pg_num_rows($res_fat_item_existente) == 0){

                    $sql_fat_item = "
                        INSERT INTO tbl_faturamento_item (
                            faturamento,
                            peca,
                            devolucao_obrig,
                            extrato_devolucao,
                            qtde,
                            preco,
                            os,
                            os_item
                        ) VALUES (
                            $faturamento,
                            $peca,
                            't',
                            $extrato,
                            '$qtde',
                            '$custo_peca',
                            $os,
                            $os_item
                        )";
                    $res_fat_item = $pdo->query($sql_fat_item);

                    $sql_ext_lgr = "
                        INSERT INTO tbl_extrato_lgr (
                            peca,
                            qtde,
                            faturamento,
                            posto,
                            extrato
                        ) VALUES (
                            $peca,
                            $qtde,
                            $faturamento,
                            $posto,
                            $extrato
                        )";
                    $res_ext_lgr = $pdo->query($sql_ext_lgr);

                }
            }
        }
    }

    public function removeKmDuplicado($extrato = ''){
    	
        if (!empty($extrato)) {
            $this->_extrato = $extrato;
        }

        $pdo = $this->_model->getPDO();

        $campos_itatiaia   = " tbl_os.data_fechamento ";
        $join_itatiaia     = "";
        $group_by_itatiaia = " tbl_os.data_fechamento ";
        $cond_data         = " AND tbl_os.data_fechamento = tbl_os.data_abertura ";
        $campos_city       = " UPPER(TRIM(tbl_os.consumidor_cidade)) AS consumidor_cidade,
                               UPPER(TRIM(tbl_os.consumidor_estado)) AS consumidor_estado,";
        $group_by_city     = " UPPER(TRIM(tbl_os.consumidor_cidade)),
                               UPPER(TRIM(tbl_os.consumidor_estado)),";

        if ($this->_fabrica == 183) {
            $campos_itatiaia   = "  tbl_os.data_conserto::date,
                                    tbl_tecnico_agenda.tecnico ";
            $join_itatiaia     = "  JOIN tbl_tecnico_agenda ON tbl_os.os = tbl_tecnico_agenda.os AND tbl_tecnico_agenda.fabrica = ".$this->_fabrica;
            $group_by_itatiaia = "  tbl_os.data_conserto::date,
                                    tbl_tecnico_agenda.tecnico ";

            $cond_data = "";
            $campos_city = "";
            $group_by_city = "";
        }

        if ($this->_fabrica == 178) {
            $campos_itatiaia   = "  tbl_tecnico_agenda.data_agendamento,
                                    tbl_tecnico_agenda.tecnico ";
					$join_itatiaia     = " 	join tbl_os_campo_extra using(os)
											join tbl_tecnico_agenda using(os_revenda)
											join tbl_os_revenda using(os_revenda)
";
            $group_by_itatiaia = "  tbl_tecnico_agenda.data_agendamento,
                                    tbl_tecnico_agenda.tecnico ";
			$cond_km = " or tbl_os_revenda.qtde_km > 0  ";
            $cond_data = " and tbl_tecnico_agenda.confirmado notnull";
        }


        $sql = "
            SELECT count(tbl_os.os) as qtde,
                tbl_os.posto,
                {$campos_city}
                {$campos_itatiaia}
            FROM tbl_os
            JOIN tbl_os_extra USING(os)
            {$join_itatiaia}
            WHERE (tbl_os.qtde_km > 0 $cond_km)
            AND tbl_os.fabrica = {$this->_fabrica}
            AND tbl_os.finalizada IS NOT NULL
            {$cond_data}
            AND tbl_os_extra.extrato = {$this->_extrato}
            GROUP BY {$group_by_city}
            tbl_os.posto,
            {$group_by_itatiaia}
            HAVING  count(tbl_os.os) > 1";

        $res_posto = $pdo->query($sql);

        if(count($res_posto) > 0){
		$postos  = $res_posto->fetchAll(\PDO::FETCH_ASSOC);
    		foreach($postos AS $posto){

				$cond_itatiaia = " AND tbl_os.data_fechamento  = tbl_tecnico_agenda.data_agendamento::date ";
				$cond_city     = " AND UPPER(TRIM(tbl_os.consumidor_cidade))= '".$posto['consumidor_cidade']."'
					AND UPPER(TRIM(tbl_os.consumidor_estado))= '".$posto['consumidor_estado']."'";

				$campo_os = " tbl_os.os";
				$join_revenda  = " JOIN    tbl_tecnico_agenda ON tbl_os.os = tbl_tecnico_agenda.os "; 
				if ($this->_fabrica == 183) {
					$cond_itatiaia = "  AND tbl_os.data_conserto::date   = '".$posto['data_conserto']."'
						AND tbl_tecnico_agenda.tecnico   = ".$posto['tecnico'];
					$cond_city     = "";
				}

				if ($this->_fabrica == 178) {
					$campo_os = " tbl_os_revenda.os_revenda ";
					$cond_itatiaia = "  AND tbl_tecnico_agenda.data_agendamento   = '".$posto['data_agendamento']."'
						AND tbl_tecnico_agenda.tecnico   = ".$posto['tecnico'];
					$join_revenda = " 	join tbl_os_campo_extra using(os)
						join tbl_tecnico_agenda using(os_revenda)
						join tbl_os_revenda using(os_revenda)
";
		$cond_km = " or tbl_os_revenda.qtde_km > 0 ";
		$order_revenda = "_revenda";
				}
				$sql = "
					SELECT  $campo_os,
						tbl_os$order_revenda.qtde_km
					FROM    tbl_os 
					JOIN    tbl_os_extra using(os)
					$join_revenda
					WHERE   tbl_os.fabrica = {$this->_fabrica}
					AND tbl_os.finalizada is not null
					AND tbl_os_extra.extrato = {$this->_extrato}
					AND (tbl_os.qtde_km                > 0 $cond_km) 
					AND tbl_os.posto                  = {$posto['posto']}
					{$cond_city}
					{$cond_itatiaia}
					ORDER BY tbl_os$order_revenda.qtde_km DESC";

				$res_os = $pdo->query($sql);
				$conta = $res_os->fetchAll();
				if(count($conta) > 0){
					$dadosOs  = $res_os->fetchAll(\PDO::FETCH_ASSOC);

					$dadosOs = array_filter($dadosOs);
					$qtde_km_anterior = 0;
					$os_zera_km = array();

					if (count($dadosOs) > 0){
						foreach ($dadosOs AS $dados) { 

							if($dados['qtde_km'] <= $qtde_km_anterior){
								if ($this->_fabrica == 178) {
									$os_zera_km[] = $dados['os_revenda'];
								}else{
									$os_zera_km[] = $dados['os'];
								}
							}

							$qtde_km_anterior = $dados['qtde_km'];
						}

						if (count($os_zera_km)){
							if ($this->_fabrica == 178) {
								$sql = "UPDATE tbl_os_revenda SET qtde_km = 0 WHERE os_revenda IN(".implode(',',$os_zera_km).") ; ";
								$resUp = $pdo->query($sql);

								$sql = "UPDATE tbl_os set qtde_km_calculada = 0 from tbl_os_extra join tbl_os_campo_extra using(os) where extrato = {$this->_extrato} and os_revenda in (".implode(',',$os_zera_km).") and tbl_os.os = tbl_os_extra.os ;";
								$resUp = $pdo->query($sql);


								$sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = jsonb_set(campos_adicionais::jsonb,'{taxa_extra_km}','\"0\"') from tbl_os_extra WHERE tbl_os_extra.os = tbl_os_campo_extra.os and tbl_os_extra.extrato = {$this->_extrato} and os_revenda IN(".implode(',',$os_zera_km).")";	
								$resUp = $pdo->query($sql);
							}else{
								$sql = "UPDATE tbl_os SET qtde_km = 0, qtde_km_calculada = 0 WHERE os IN(".implode(',',$os_zera_km).")";
								$resUp = $pdo->query($sql);
var_dump($pdo); exit;
								$sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = jsonb_set(campos_adicionais::jsonb,'{taxa_extra_km}','\"0\"') WHERE os IN(".implode(',',$os_zera_km).")";	
								$resUp = $pdo->query($sql);
							}
						}
					}
				}
			}
    	}
    }

    public function encontroContas($extrato = ''){
        if (!empty($extrato)) {
            $this->_extrato = $extrato;
        }

        $pdo = $this->_model->getPDO();

        $sql_intervalo = "
            WITH INTERVALO_DATAS AS(
                SELECT 
                    CAST(EXTRACT(YEAR FROM (SELECT data_geracao FROM tbl_extrato WHERE extrato = {$this->_extrato})- INTERVAL '1 MONTH')||'-'||EXTRACT(MONTH FROM (SELECT data_geracao FROM tbl_extrato WHERE extrato = {$this->_extrato})-INTERVAL '1 MONTH')||'-01' AS DATE) AS data_inicial,
                    (DATE_TRUNC('MONTH', (SELECT data_geracao FROM tbl_extrato WHERE extrato = {$this->_extrato})-INTERVAL '1 MONTH') + INTERVAL '1 MONTH - 1 DAY')::DATE AS data_final
                )
            SELECT data_inicial, data_final
            FROM INTERVALO_DATAS"; 
        $res_intervalo = $pdo->query($sql_intervalo);
        $datas = $res_intervalo->fetchAll(\PDO::FETCH_ASSOC);

        $data_inicial = $datas[0]["data_inicial"];
        $data_final = $datas[0]["data_final"];

        $sql = "
            SELECT 
                SUM(tbl_pedido_item.total_item) AS valor_faturado
            FROM tbl_extrato
            JOIN tbl_pedido ON tbl_pedido.posto = tbl_extrato.posto AND tbl_pedido.fabrica = {$this->_fabrica}
            JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
            JOIN tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao AND tbl_condicao.fabrica = {$this->_fabrica}
            LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido
            WHERE tbl_extrato.extrato = $this->_extrato 
            AND tbl_condicao.visivel_acessorio IS TRUE
            AND tbl_pedido.data BETWEEN '$data_inicial' AND '$data_final'";
        $res = $pdo->query($sql);
        $encontro_contas  = $res->fetchAll(\PDO::FETCH_ASSOC);
        echo "<pre>".print_r($encontro_contas,1)."</pre>";exit;
    }

    public function getErro(){

        if(is_array($this->_erro)){
            return $this->_erro["2"];
        }else{
            return $this->_erro;
        }
    }

}

