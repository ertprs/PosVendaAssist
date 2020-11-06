<?php

namespace Posvenda\Cockpit;

use Posvenda\Cockpit;

class Tecnico
{
    /**
     * @var mixed
     */
    private $fabrica;

    /**
     * @var object
     */
    private $model;

    private $_serverEnvironment;
    private $_persysAuthorizationKey;
    private $_persysMonitoringAuthorizationKey;

    public function __construct($fabrica, \Posvenda\Model\GenericModel $model)
    {
        $this->fabrica = $fabrica;
        $this->model = $model;

        include "/etc/telecontrol.cfg";

        $this->_serverEnvironment = $_serverEnvironment;

        if ($this->_serverEnvironment == "production") {
            $this->_persysAuthorizationKey           = "12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9";
            $this->_persysMonitoringAuthorizationKey = "12984374000259-ec361e58328e410b6601ffd5ef1cd673";
        } else {
            $this->_persysAuthorizationKey           = "4716427000141-dc3442c4774e4edc44dfcc7bf4d90447";
            $this->_persysMonitoringAuthorizationKey = "4716427000141-7af522538c684aad74bc8881cb786f06";
        }
    }

    /**
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param string $cep cep do cliente
     * @param integer $kmLimit limitar busca por uma distÃ¢ncia mÃ¡xima em km
     * @param array $produto array com as informaÃ§Ãµes do produto
     * @param integer $prioridade prioridade do ticket
     * @return array
     */
    public function getTecnicoMaisProximo($lat, $lng, $cep, $kmLimit = 0, $produto, $prioridade = 0, $tipo_atendimento, $garantia, $cliente_id, $centro_distribuidor, $data_abertura, $empresa = null)
    {
        if (empty($produto)) {
            return false;
        }

        if (empty($cep)) {
            return false;
        }

        $cockpit = new Cockpit($this->fabrica);

        $linha_produto = $cockpit->getProdutoById($produto["produto"], true);

        if ($linha_produto["linha_nome"] != "REFRIGERADOR") {
            $garantia = "";
            $disponivel = false;
        } else {
            $disponivel = true;
        }

        $tipo_ordem       = $tipo_atendimento;
        $tipo_atendimento = $cockpit->getTipoAtendimentoKOF($tipo_atendimento, $garantia);

        if (empty($tipo_atendimento)) {
            return false;
        }

        $cliente = $cockpit->getClienteKOF($cliente_id);

        if ($cliente && strlen($cliente["grupo_cliente"]) > 0) {
            $join_grupo_cliente = "
                INNER JOIN tbl_posto_grupo_cliente ON tbl_posto_grupo_cliente.posto = tbl_posto_fabrica.posto AND tbl_posto_grupo_cliente.fabrica = {$this->fabrica}
            ";
            $where_grupo_cliente = "
                AND tbl_posto_grupo_cliente.grupo_cliente = {$cliente['grupo_cliente']}
            ";
        }

        $join =  "
            INNER JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_tecnico.posto
            INNER JOIN tbl_produto ON tbl_produto.linha = tbl_posto_linha.linha
            INNER JOIN tbl_posto_cep_atendimento ON tbl_posto_cep_atendimento.posto = tbl_posto_fabrica.posto AND tbl_posto_cep_atendimento.fabrica = {$this->fabrica}
            INNER JOIN tbl_posto_tipo_atendimento ON tbl_posto_tipo_atendimento.posto = tbl_posto_fabrica.posto AND tbl_posto_tipo_atendimento.fabrica = {$this->fabrica}
            INNER JOIN tbl_distribuidor_sla_posto unidade_negocio_posto ON unidade_negocio_posto.posto = tbl_posto_fabrica.posto AND unidade_negocio_posto.fabrica = {$this->fabrica}
            INNER JOIN tbl_distribuidor_sla unidade_negocio ON unidade_negocio.distribuidor_sla =unidade_negocio_posto.distribuidor_sla AND unidade_negocio.fabrica = {$this->fabrica}
            INNER JOIN tbl_distribuidor_sla centro_distribuidor ON centro_distribuidor.unidade_negocio = unidade_negocio.unidade_negocio AND centro_distribuidor.fabrica = {$this->fabrica}
            {$join_grupo_cliente}
        ";

        $where = "
            AND tbl_produto.produto = {$produto['produto']}
            AND tbl_posto_cep_atendimento.cep_inicial = '{$cep}'
            AND tbl_posto_tipo_atendimento.tipo_atendimento = {$tipo_atendimento['tipo_atendimento']}
            AND centro_distribuidor.centro = '{$centro_distribuidor}'
            {$where_grupo_cliente}
        ";

        $pdo = $this->model->getPDO();

        $dropTemp = $pdo->query("DROP TABLE IF EXISTS tmp_tec_mais_prox");

        $sub = $this->getQueryMaisProximos($lat, $lng, $join, $where);

        $sql = "
            SELECT * 
            INTO TEMP tmp_tec_mais_prox 
            FROM ({$sub}) sub
            ORDER BY distance ASC
            LIMIT 5
        ";
        $query = $pdo->query($sql);

        if (!$query) {
                return false;
        }

        $sql = "SELECT 
                    tecnico,
                    posto,
                    nome,
                    qtde_atendimento,
                    latitude,
                    longitude,
                    disponibilidade_d0
                FROM tmp_tec_mais_prox
                ORDER BY distance ASC";
        $qryTec = $pdo->query($sql);

        $terceiro = $this->getTerceiroMaisProximo($lat, $lng, $join, $where);

        if ($tipo_ordem == "ZKR6") {
            list($data, $hora)     = explode(" ", $data_abertura);
            list($dia, $mes, $ano) = explode("/", $data);

            $data        = "{$ano}-{$mes}-{$dia}";
            $proximaData = "{$ano}-{$mes}-{$dia}";
        } else {
            $data        = date("Y-m-d");
            $proximaData = date("Y-m-d", strtotime("+1 day"));
        }

        if ($qryTec->rowCount() == 0) {
            if (!$terceiro) {
                return false;
            }

            $tecnico         = $terceiro;
            $tecnico["data"] = $data;
        } else {
            $tecnicos = $qryTec->fetchAll(\PDO::FETCH_ASSOC);
            $dia      = 0;

            $tecnico  = $this->getDisponivel($tecnicos, $dia, $disponivel, $data);

            if (!$tecnico && !$terceiro) {
                return false;
            } else if (!$tecnico) {
                $tecnico         = $terceiro;
                $tecnico["data"] = $data;
            }
        }

        $d1 = date("Y-m-d H:i");
        $d2 = date("Y-m-d 22:00");

        if ($tipo_ordem != "ZKR6" && strtotime($d1) >= strtotime($d2)) {
            $tecnico["data"] = $proximaData;
        }

        return $tecnico;
    }

    /**
     * @param integer $tecnico
     * @return integer
     */
    public function getPostoTecnico($tecnico)
    {
        if (!strlen($tecnico)) {
            throw new \Exception("Técnico não informado");
        }

        $this->model->select("tbl_tecnico")
             ->setCampos(array("posto"))
             ->addWhere(array("tecnico" => $tecnico))
             ->addWhere(array("fabrica" => $this->fabrica));

        if (!$this->model->prepare()->execute()) {
            throw new \Exception("Erro ao buscar o posto do técnico");
        }

        if ($this->model->getPDOStatement()->rowCount() == 0) {
            throw new \Exception("Técnico não encontrado");
        } else {
            $res = $this->model->getPDOStatement()->fetch();

            return $res["posto"];
        }
    }

    /**
     * @param float $client_lat
     * @param float $client_lng
     * @param array $technicals_locations
     * @return object
     */
    public function distance($client_lat, $client_lng, $technicals_locations, $tcMaps)
    {
        return $tcMaps->route($client_lat.",".$client_lng, $technicals_locations);
    }

    private function getDisponivel($tecnicos, $d = 0, $disponivel = false, $data) {
        /*if ($d > 0) {
            return false;
        }*/

        $tecnico_selecionado = null;
       
        $tecnicos_disponibilidade = array_map(function($t) use($d) {
                return $t["disponibilidade_d{$d}"];
        }, $tecnicos);

        sort($tecnicos_disponibilidade);

        $disponibilidade = $tecnicos_disponibilidade[0] - 1;

        foreach ($tecnicos as $tecnico) {
            if ((($disponivel && $tecnico["disponibilidade_d{$d}"] > 0) || !$disponivel) && $tecnico["disponibilidade_d{$d}"] > $disponibilidade) {
                $tecnico["data"]            = date($data, strtotime("+{$d} day"));
                $tecnico["tecnico_proprio"] = true;
                $disponibilidade            = $tecnico["disponibilidade_d{$d}"];
                $tecnico_selecionado        = $tecnico;
            }
        }

        if ($tecnico_selecionado == null) {
            //$this->getDisponivel($tecnicos, $d++);
            return false;
        } else {
            return $tecnico_selecionado;
        }
    }

    private function getTerceiroMaisProximo($lat, $lng, $extraJoin, $extraWhere) {
        $pdo = $this->model->getPDO();

        $sql = "
            SELECT DISTINCT
                tbl_tecnico.tecnico,
                tbl_tecnico.posto,
                tbl_tecnico.nome,
                tbl_tecnico.qtde_atendimento,
                tbl_tecnico.latitude,
                tbl_tecnico.longitude,
                (
                    111.045 * DEGREES(
                        ACOS(
                            COS(RADIANS({$lat}))
                            * COS(RADIANS(tbl_tecnico.latitude))
                            * COS(RADIANS(tbl_tecnico.longitude) - RADIANS({$lng}))
                            + SIN(RADIANS({$lat}))
                            * SIN(RADIANS(tbl_tecnico.latitude))
                        )
                    )
                ) AS distance
            FROM tbl_tecnico
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_tecnico.posto
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$this->fabrica}
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
            {$extraJoin}
            WHERE tbl_tecnico.fabrica = {$this->fabrica}
            AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
            AND tbl_tipo_posto.posto_interno IS NOT TRUE
            AND tbl_tipo_posto.tecnico_proprio IS NOT TRUE
            {$extraWhere}
            AND (
                tbl_tecnico.latitude IS NOT NULL
                AND tbl_tecnico.longitude IS NOT NULL
                AND tbl_tecnico.nome IS NOT NULL
                AND tbl_tecnico.endereco IS NOT NULL
                AND tbl_tecnico.cidade IS NOT NULL
                AND tbl_tecnico.estado IS NOT NULL
            )
            ORDER BY distance ASC
            LIMIT 1
        ";
        $query = $pdo->query($sql);

        if (!$query || $query->rowCount() == 0) {
            return false;
        } else {
            $res = $query->fetch(\PDO::FETCH_ASSOC);

            return $res;
        }
    }

    private function getQueryMaisProximos($lat, $lng, $extraJoin, $extraWhere)
    {
        return "SELECT DISTINCT
                    tbl_tecnico.tecnico,
                    tbl_tecnico.posto,
                    tbl_tecnico.nome,
                    tbl_tecnico.qtde_atendimento,
                    tbl_tecnico.latitude,
                    tbl_tecnico.longitude,
                    (
                        111.045 * DEGREES(
                            ACOS(
                                COS(RADIANS({$lat}))
                                * COS(RADIANS(tbl_tecnico.latitude))
                                * COS(RADIANS(tbl_tecnico.longitude) - RADIANS({$lng}))
                                + SIN(RADIANS({$lat}))
                                * SIN(RADIANS(tbl_tecnico.latitude))
                            )
                        )
                    ) AS distance,
                    (
                        tbl_tecnico.qtde_atendimento - (
                            SELECT COUNT(*) 
                            FROM tbl_tecnico_agenda AS tta 
                            INNER JOIN tbl_os AS tos ON tos.os = tta.os AND tos.fabrica = {$this->fabrica}
                            WHERE tta.fabrica = {$this->fabrica} 
                            AND tta.tecnico = tbl_tecnico.tecnico 
                            AND tos.finalizada IS NULL
                            AND tos.excluida IS NOT TRUE
                        )
                    ) AS disponibilidade_d0
                FROM tbl_tecnico
                INNER JOIN tbl_posto ON tbl_posto.posto = tbl_tecnico.posto
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$this->fabrica}
                INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                {$extraJoin}
                WHERE tbl_tecnico.fabrica = {$this->fabrica}
                AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                AND tbl_tipo_posto.posto_interno IS NOT TRUE
                AND tbl_tipo_posto.tecnico_proprio IS TRUE
                {$extraWhere}
                AND (
                    tbl_tecnico.latitude IS NOT NULL
                    AND tbl_tecnico.longitude IS NOT NULL
                    AND tbl_tecnico.nome IS NOT NULL
                    AND tbl_tecnico.endereco IS NOT NULL
                    AND tbl_tecnico.cidade IS NOT NULL
                    AND tbl_tecnico.estado IS NOT NULL
                )";
    }

    /**
     * @param integer $tecnico
     * @param string  $data
     * @param integer $qtdeAtendimentos
     * @return boolean
     */
    private function verificaDisponibilidade($tecnico, $data, $qtdeAtendimentos)
    {
        $this->model->select("tbl_tecnico_agenda")
            ->setCampos(array("tecnico_agenda", "ordem"))
            ->addWhere(array("tecnico" => $tecnico))
            ->addWhere(
                $this->model->between('data_agendamento', array("$data 00:00:00", "$data 23:59:59"))
            );

        if (!$this->model->prepare()->execute()) {
            return false;
        }

        if ($this->model->getPDOStatement()->rowCount() == 0) {
            return true;
        }

        $res = $this->model->getPDOStatement()->fetchAll(\PDO::FETCH_ASSOC);

        if ($qtdeAtendimentos > count($res)) {
            return true;
        }

        return false;
    }

    /**
     * @param integer $prioridade
     * @return boolean
     */
    private function verificaPrioridade($prioridade)
    {
        $this->model->select("tbl_hd_chamado_cockpit_prioridade")
            ->setCampos(array(
                "descricao"
            ))
            ->addWhere(array(
                "hd_chamado_cockpit_prioridade" => $prioridade
            ));

        if (!$this->model->prepare()->execute()) {
            return false;
        }

        if ($this->model->getPDOStatement()->rowCount() == 0) {
            return false;
        }

        $res = $this->model->getPDOStatement()->fetch(\PDO::FETCH_ASSOC);

        $prios = array(
            "Alta", "Alta KA"
        );

        if (in_array(trim($res["descricao"]), $prios)) {
            return true;
        }

        return false;
    }

    private function getUnidade() {
        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new \Posvenda\Cockpit\Api();

        $get = $api->curlGet(
            "http://telecontrol.eprodutiva.com.br/api/unidade",
            $headers
        );

        if (count($get["data"]) > 0) {
            return $get["data"][0]["id"];
        } else {
            return false;
        }
    }

    private function getDepartamento() {
        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new \Posvenda\Cockpit\Api();

        $get = $api->curlGet(
            "http://telecontrol.eprodutiva.com.br/api/departamento",
            $headers
        );

        if (count($get["data"]) > 0) {
            return $get["data"][0]["id"];
        } else {
            return false;
        }
    }

    private function retira_acentos($texto) {
        $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
        $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
        return str_replace($array1, $array2, $texto);
    }

    public function gravaTecnicoMobile($nome, $nome_fantasia, $cnpj_cpf, $ie_rg, $email, $codigo) {
        $unidade      = $this->getUnidade();

        if (!$unidade) {
            throw new \Exception("Erro ao buscar unidade");
        }

        $departamento = $this->getDepartamento();

        if (!$departamento) {
            throw new \Exception("Erro ao buscar departamento");
        }

        if (empty($nome)) {
            throw new \Exception("Nome do técnico não informado");
        }

        if (empty($cnpj_cpf)) {
            throw new \Exception("CPF/CNPJ do técnico não informado");
        }

        $nome_usuario = $codigo;

        $body = array(
            "nomeUsuario"       => $nome_usuario,
            "razaoNome"         => utf8_encode($nome),
            "fantasiaSobrenome" => utf8_encode($nome_fantasia),
            "cnpjCpf"           => preg_replace("/\D/", "", $cnpj_cpf),
            "ieRg"              => preg_replace("/\D/", "", $ie_rg),
            "email"             => utf8_encode($email),
            "departamento"      => array(
                "id"      => $departamento,
                "unidade" => array(
                    "id" => $unidade
                )
            ),
            "externalAuthorization" => true,
            "monitoramento" => array(
                "id" => 11
            )
        );

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new \Posvenda\Cockpit\Api();

        $post = $api->curlPost(
            "http://telecontrol.eprodutiva.com.br/api/agente",
            $headers,
            json_encode($body)
        );

		if(strlen($post['error'] > 0)) {
			throw new \Exception(utf8_decode($post['error']['message']));
		}
        if (!$post["codigo"]) {
            throw new \Exception("Erro ao gravar técnico");
        }

        return $post["codigo"];
    }

    public function atualizaTecnicoMobile($codigo_externo, $nome, $nome_fantasia, $cnpj_cpf, $ie_rg, $email, $codigo) {
        if (empty($nome)) {
            throw new \Exception("Nome do técnico não informado");
        }

        if (empty($cnpj_cpf)) {
            throw new \Exception("CPF/CNPJ do técnico não informado");
        }

        $nome_usuario = $codigo;

        $body = array(
            "nomeUsuario"       => $nome_usuario,
            "razaoNome"         => utf8_encode($nome),
            "fantasiaSobrenome" => utf8_encode($nome_fantasia),
            "cnpjCpf"           => preg_replace("/\D/", "", $cnpj_cpf),
            "ieRg"              => preg_replace("/\D/", "", $ie_rg),
            "email"             => utf8_encode($email)
        );

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new \Posvenda\Cockpit\Api();

        $put = $api->curlPut(
            "http://telecontrol.eprodutiva.com.br/api/agente/codigo/{$codigo_externo}",
            $headers,
            json_encode($body)
        );

        if (!$put["nomeUsuario"]) {
            throw new \Exception("Erro ao atualizar informações do técnico");
        }

        return true;
    }

    public function getIdExterno($codigo_externo) {
        if (empty($codigo_externo)) {
            throw new \Exception("Erro ao buscar id externo do técnico, código externo não informado");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new \Posvenda\Cockpit\Api();

        $get = $api->curlGet(
            "http://telecontrol.eprodutiva.com.br/api/agente/codigo/{$codigo_externo}",
            $headers
        );

        if (isset($get["id"])) {
            return $get["id"];
        } else {
            return false;
        }
    }

    public function getTecnicos($tecnico = null) {
        $pdo = $this->model->getPDO();

        if (!is_null($tecnico)) {
            $whereTecnico = "AND t.tecnico = {$tecnico}";
        }

        $sql = "
            SELECT t.tecnico, t.codigo_externo, t.latitude, t.longitude, t.posto
            FROM tbl_tecnico AS t
            INNER JOIN tbl_posto_fabrica AS pf ON pf.posto = t.posto AND pf.fabrica = {$this->fabrica}
            INNER JOIN tbl_tipo_posto AS tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$this->fabrica}
            WHERE t.fabrica = {$this->fabrica}
            AND tp.tecnico_proprio IS TRUE
            {$whereTecnico}
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar técnicos");
        }

        return $qry->fetchAll();
    }

    public function getTecnicoHistoricoDeslocamento($id_externo, $data_inicial, $data_final) {
        if (!strlen($id_externo)) {
            throw new \Exception("Erro ao buscar histórico de deslocamento do técnico, ID externo não informado");
        }

        if (empty($data_inicial)) {
            throw new \Exception("Erro ao buscar histórico de deslocamento do técnico, data inicial não informada");
        }

        if (empty($data_final)) {
            throw new \Exception("Erro ao buscar histórico de deslocamento do técnico, data final não informada");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysMonitoringAuthorizationKey}"
        );

        $api = new \Posvenda\Cockpit\Api();

        $get = $api->curlGet(
            "http://service.eprodutiva.com.br/monitor/event/{$id_externo}/history?start={$data_inicial}&end={$data_final}",
            $headers
        );

        return $get;
    }

    public function insertTecnicoMonitoramento($fabrica, $tecnico, $latitude, $longitude, $tipo_envio, $data) {
        $pdo = $this->model->getPDO();

        $aux_tipo_envio = pg_escape_literal($tipo_envio);
        $sql = "
            INSERT INTO tbl_tecnico_monitoramento
            (fabrica, tecnico, latitude, longitude, tipo_envio, data_input)
            VALUES
            ({$fabrica}, {$tecnico}, {$latitude}, {$longitude}, E$aux_tipo_envio, '{$data}'::timestamp)
        ";
        /*HD-3966437*/
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao inserir monitoramento do técnico");
        }
    }

    public function getAtendimentos($tecnico, $data) {
        $pdo = $this->model->getPDO();

        $sql = "
            SELECT *
            FROM tbl_tecnico_agenda
            WHERE fabrica = {$this->fabrica}
            AND tecnico = {$tecnico}
            AND data_agendamento::date = '{$data}'
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar atendimentos do técnico");
        }

        if ($qry->rowCount() == 0) {
            return array();
        }

        return $qry->fetchAll();
    }

    public function atualizaOrdenacaoOSMobile($tecnico, $data) {
        if (empty($tecnico)) {
            throw new \Exception("Erro ao reordenar OSs no dispositivo móvel, técnico não informado");
        }

        if (!strtotime($data)) {
            throw new \Exception("Erro ao reordenar OSs no dispositivo móvel, data inválida");
        }

        $id_externo = $this->getIdExterno($tecnico["codigo_externo"]);

        if (empty($id_externo)) {
            throw new \Exception("Erro ao reordenar OSs no dispositivo móvel, não foi possí­vel buscar o id externo do técnico");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new \Posvenda\Cockpit\Api();

        $get = $api->curlGet(
            "http://telecontrol.eprodutiva.com.br/api/ordem/router/{$id_externo}?date={$data}",
            $headers
        );

        if (empty($get)) {
            return false;
        }

        $pdo = $this->model->getPDO();

        foreach ($get["data"] as $os) {
            $update = "
                UPDATE tbl_tecnico_agenda SET
                    ordem = {$os['orderSequence']}
                WHERE fabrica = {$this->fabrica}
                AND os = {$os['ordem']['codigo']}
            ";
            $qry = $pdo->query($update);

            if (!$qry) {
                throw new \Exception("Erro ao atualuzar Ordem das OSs");
            }
        }
    }

    public function reordenaOSMobile($tecnico, $data, $manual = false) {
        if (empty($tecnico)) {
            throw new \Exception("Erro ao reordenar OSs no dispositivo móvel, técnico não informado");
        }

        if (!strtotime($data)) {
            throw new \Exception("Erro ao reordenar OSs no dispositivo móvel, data inválida");
        }

        $id_externo = $this->getIdExterno($tecnico["codigo_externo"]);

        if (empty($id_externo)) {
            throw new \Exception("Erro ao reordenar OSs no dispositivo móvel, não foi possí­vel buscar o id externo do técnico");
        }

        $pdo = $this->model->getPDO();

        if (!$manual) {
            $tabela = "tbl_posto_fabrica";
        } else {
            $tabela = "tbl_tecnico";
        }

        $sql = "
            SELECT 
                {$tabela}.latitude, 
                {$tabela}.longitude 
            FROM tbl_tecnico
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_tecnico.posto AND tbl_posto_fabrica.fabrica = {$this->fabrica}
            WHERE tbl_tecnico.fabrica = {$this->fabrica}
            AND tbl_tecnico.tecnico = {$tecnico['tecnico']}
        ";
        $qry = $pdo->query($sql);

        if (!$qry || $qry->rowCount() == 0) {
            throw new \Exception("Erro ao reordenar OSs no dispositivo móvel, não foi possí­vel buscar a latitude e longitude do técnico");
        }

        $latLng = $qry->fetch();

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new \Posvenda\Cockpit\Api();

        $put = $api->curlPut(
            "http://telecontrol.eprodutiva.com.br/api/ordem/router/{$id_externo}",
            $headers,
            json_encode(array(
                "scheduledDate" => $data,
                "startingPointLat" => $latLng["latitude"],
                "startingPointLong" => $latLng["longitude"]
            ))
        );

        if (empty($put)) {
            throw new \Exception("Erro ao reordenar OSs no dispositivo móvel");
        }

        foreach ($put["data"] as $os) {
            $sql = "
                UPDATE tbl_tecnico_agenda SET 
                    ordem = {$os['orderSequence']}
                WHERE fabrica = {$this->fabrica}
                AND tecnico = {$tecnico['tecnico']}
                AND os = {$os['ordem']['codigo']}
            ";
            $qry = $pdo->query($sql);

            if (!$qry) {
                throw new \Exception("Erro ao reordenar OSs");
            }
        }
    }
}
