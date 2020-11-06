<?php

namespace Posvenda\Cockpit;

use Posvenda\Cockpit\Api;

$no_global = true;

include_once __DIR__.'/../../../dbconfig.php';

if (!class_exists("TcComm")) {
    include_once __DIR__.'/../../../class/communicator.class.php';
}

class Os
{
    /**
     * @var mixed
     */
    private $fabrica;

    /**
     * @var object
     */
    private $model;

    /**
     * @var integer
     */
    private $os;

    private $_serverEnvironment;
    private $_persysAuthorizationKey;
    private $_postMixLaudoId;
    private $_chopeiraLaudoId;

    public function __construct($fabrica, \Posvenda\Model\GenericModel $model)
    {
        date_default_timezone_set("America/Sao_Paulo");
        
        $this->fabrica = $fabrica;
        $this->model = $model;

        include "/etc/telecontrol.cfg";

        $this->_serverEnvironment = $_serverEnvironment;

        if ($this->_serverEnvironment == "production") {
            $this->_persysAuthorizationKey = "12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9";
	    $this->_postMixLaudoId = 37;
	    $this->_chopeiraLaudoId = 38;
        } else {
            $this->_persysAuthorizationKey = "4716427000141-dc3442c4774e4edc44dfcc7bf4d90447";
	    $this->_postMixLaudoId = 13;
	    $this->_chopeiraLaudoId = 14;
        }
    }

    /**
     * @param integer $hdChamado
     * @return array
     */
    public function abreOS($hdChamado, $patrimonio = null, $os_kof = null, $cliente_latitude = null, $cliente_longitude = null, $routine_schedule_log_id = null)
    {

        $this->model->select("tbl_hd_chamado_extra")
            ->setCampos(array(
                "hd_chamado",
                "tbl_hd_chamado_extra.posto",
                "tbl_posto_fabrica.contato_nome",
                "tbl_posto_fabrica.nome_fantasia",
                "tbl_tipo_posto.posto_interno",
                "tbl_tipo_posto.tecnico_proprio",
                "tbl_hd_chamado_extra.nome",
                "tbl_hd_chamado_extra.consumidor_final_nome",
                "tbl_hd_chamado_extra.endereco",
                "tbl_hd_chamado_extra.bairro",
                "tbl_hd_chamado_extra.cpf",
                "tbl_hd_chamado_extra.complemento",
                "tbl_hd_chamado_extra.numero",
                "tbl_hd_chamado_extra.cep",
                "tbl_hd_chamado_extra.fone",
                array("cidade" => "tbl_cidade.nome"),
                "tbl_cidade.estado",
                "tbl_hd_chamado_extra.reclamado",
                "tbl_hd_chamado_extra.defeito_reclamado",
                "tbl_hd_chamado_extra.tipo_atendimento",
                "tbl_hd_chamado_extra.produto",
                "tbl_hd_chamado_extra.serie",
                "tbl_hd_chamado_extra.qtde_km",
                "tbl_hd_chamado_extra.array_campos_adicionais"
            ))
            ->addJoin(array("tbl_cidade" => "USING(cidade)"))
            ->addJoin(array("tbl_posto_fabrica" => "USING(posto)"))
            ->addJoin(array("tbl_tipo_posto" => "USING(tipo_posto)"))
            ->addWhere(array("hd_chamado" => $hdChamado));

        if (!$this->model->prepare()->execute()) {
            return array('error' => '001 Erro interno');
        }

        if ($this->model->getPDOStatement()->rowCount() == 0) {
            return array('error' => 'Não encontrou hd_chamado_extra');
        }

        $dados = $this->model->getPDOStatement()->fetch(\PDO::FETCH_ASSOC);

        $pdo = $this->model->getPDO();

        $pdo->beginTransaction();

        $array_columns = array(
            "fabrica",
            "posto",
            "data_abertura",
            "consumidor_nome",
            "consumidor_endereco",
            "consumidor_bairro",
            "consumidor_cpf",
            "consumidor_complemento",
            "consumidor_numero",
            "consumidor_cidade",
            "consumidor_estado",
            "consumidor_cep",
            "consumidor_fone",
            "defeito_reclamado",
            "defeito_reclamado_descricao",
            "tipo_atendimento",
            "hd_chamado",
            "validada",
            "qtde_km",
            "os_posto",
            "data_hora_abertura",
            "obs",
            "versao"
        );

        $qtde_km = $dados["qtde_km"];

        if (empty($qtde_km)) {
            $qtde_km = 'NULL';
        }   

        $array_values = array(
            $this->fabrica,
            $dados["posto"],
            "current_date",
            "'".addslashes($dados["nome"])."'",
            "'".addslashes($dados["endereco"])."'",
            "E'".addslashes($dados["bairro"])."'",
            "'".addslashes($dados["cpf"])."'", 
            "'".addslashes($dados["complemento"])."'", 
            "'".addslashes($dados["numero"])."'", 
            "'".addslashes($dados["cidade"])."'",
            "'{$dados["estado"]}'",
            "'{$dados["cep"]}'",
            "'{$dados["fone"]}'",
            "{$dados["defeito_reclamado"]}",
            "'{$dados["reclamado"]}'",
            $dados["tipo_atendimento"],
            $dados["hd_chamado"],
            'now()',
            $qtde_km,
            "'{$os_kof}'",
            "'".date('Y-m-d H:i:s')."'",
            "'".$dados['obs']."'",
            "'{$routine_schedule_log_id}'"
        );

        $sql = "INSERT INTO tbl_os (
                    ".implode(", ", $array_columns)."
                ) VALUES (
                    ".implode(", ", $array_values)."
                ) RETURNING os";

        $query = $pdo->query($sql);

        if (empty($query)) {
            $pdo->rollBack();
            return array('error' => '002 Erro interno');
        }

        $os = $query->fetch(\PDO::FETCH_ASSOC);

        $sua_os = "UPDATE tbl_os SET sua_os = {$os["os"]} WHERE os = {$os["os"]}";
        $query = $pdo->query($sua_os);

        $os_extra_columns = array('os', 'garantia', 'obs_adicionais');
        $os_extra_values = array($os["os"], 'false', "'".$_SERVER['SCRIPT_FILENAME']."'");

        if (!empty($patrimonio)) {
            $os_extra_columns[] = 'serie_justificativa';
            $os_extra_values[] = "'{$patrimonio}'";
        }

        $os_extra = "INSERT INTO tbl_os_extra (
                        " . implode(", ", $os_extra_columns) . "
                    ) VALUES (
                        " . implode(", ", $os_extra_values) . ")";
        $query = $pdo->query($os_extra);

        $sql = "INSERT INTO tbl_os_produto (os, produto, serie)
                VALUES ({$os["os"]}, {$dados["produto"]}, '{$dados["serie"]}')";
        $query = $pdo->query($sql);

        if (empty($query)) {
            $pdo->rollBack();
            return array('error' => '003 Erro interno');
        }

        if (($cliente_latitude != null && $cliente_longitude != null) || !empty($dados['array_campos_adicionais'])) {

            $json_campos_adicionais = array();

            if ($cliente_latitude != null && $cliente_longitude != null) {
                $json_campos_adicionais = array(
                    "cliente_latitude"  => $cliente_latitude,
                    "cliente_longitude" => $cliente_longitude
                );
            }

            if (!empty($dados['array_campos_adicionais'])) {
                $json_campos_adicionais = array_merge($json_campos_adicionais, json_decode(stripslashes($dados['array_campos_adicionais']), true));
            }

            $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$this->fabrica} AND os = {$os['os']}";
            $query = $pdo->query($sql);

            if ($query->rowCount() == 0) {
                $json_campos_adicionais = json_encode($json_campos_adicionais);

                $sql = "
                    INSERT INTO tbl_os_campo_extra
                    (os, fabrica, campos_adicionais)
                    VALUES
                    ({$os['os']}, {$this->fabrica}, '{$json_campos_adicionais}')
                ";
            } else {
                $res = $query->fetch();

                $campos_adicionais = $res["campos_adicionais"];

                if (!empty($campos_adicionais)) {
                    $json_campos_adicionais = array_merge($json_campos_adicionais, json_decode($campos_adicionais, true));
                }

                $json_campos_adicionais = json_encode($json_campos_adicionais);

                $sql = "
                    UPDATE tbl_os_campo_extra SET
                        campos_adicionais = '{$json_campos_adicionais}'
                    WHERE fabrica = {$this->fabrica}
                    AND os = {$os['os']}
                ";
            }
            $query = $pdo->query($sql);

            if (empty($query)) {
                $pdo->rollBack();
                return array('error' => '003 Erro interno');
            }
        }

        $sql = "UPDATE tbl_hd_chamado_cockpit SET motivo_erro = NULL WHERE fabrica = {$this->fabrica} AND hd_chamado = {$hdChamado}";
        $query = $pdo->query($sql);

        if (empty($query)) {
            $pdo->rollBack();
            return array('error' => '004 Erro interno');
        }

        $sql = "
            SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$this->fabrica} AND grupo_atendimento = 'S' AND tipo_atendimento = {$dados["tipo_atendimento"]}
        ";
        $query = $pdo->query($sql);

        if (empty($query)) {
            $pdo->rollBack();
            return array('error' => '005 Erro interno');
        }

        if ($query->rowCount() > 0) {
            $cockpitClass = new \Posvenda\Cockpit($this->fabrica);

            $defeito_constatado = $cockpitClass->getDefeitoConstatado(array(
                "LOWER(fn_retira_especiais(descricao))" => "'sanitizacao'"
            ));
            $solucao            = $cockpitClass->getSolucao(array(
                "LOWER(fn_retira_especiais(descricao))" => "'sanitizacao'"
            ));

            if (empty($defeito_constatado) || empty($solucao)) {
                $pdo->rollBack();
                return array('error' => '006 Erro interno');
            }
            
            $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, solucao,fabrica) VALUES ({$os['os']}, {$solucao['solucao']},{$this->fabrica})";
            $qry = $pdo->query($sql);

            if (!$qry) {
                $pdo->rollBack();
                return array('error' => '007 Erro interno');
            }

            $sql = "
                INSERT INTO tbl_os_defeito_reclamado_constatado 
                (os, defeito_constatado,fabrica) 
                VALUES 
                ({$os['os']}, {$defeito_constatado['defeito_constatado']},{$this->fabrica})
            ";
            $qry = $pdo->query($sql);

            if (!$qry) {
                $pdo->rollBack();
                return array('error' => '008 Erro interno');
            }
	}

    $sqlReclamado = "INSERT INTO tbl_os_defeito_reclamado_constatado (fabrica, defeito_reclamado, os ) SELECT {$this->fabrica}, defeito_reclamado, {$os['os']} from tbl_hd_chamado_item  where tbl_hd_chamado_item.campos_adicionais::JSON->>'defeito_reclamado_adicional' = 'true'  and  tbl_hd_chamado_item.hd_chamado = $hdChamado";  
    $defeitosR = $pdo->query($sqlReclamado);

    if (!$defeitosR) {
        $pdo->rollBack();
        return array('error' => '007 Erro interno');
    }   

	$sql = "
		SELECT
			o.os,
		 	(SELECT
				xo.os
			FROM tbl_os xo
			JOIN tbl_os_extra xoe USING(os)
			WHERE xo.fabrica = {$this->fabrica} 
			AND xo.os != o.os
		        AND xo.defeito_reclamado = o.defeito_reclamado
			AND xoe.serie_justificativa = oe.serie_justificativa
			AND xo.finalizada IS NOT NULL
			AND xo.data_digitacao BETWEEN o.data_digitacao - INTERVAL '90 day' AND o.data_digitacao
			ORDER BY xo.data_digitacao ASC
			LIMIT 1) AS os_reincidente
		FROM tbl_os o
		JOIN tbl_os_extra oe USING(os)
		WHERE o.fabrica = {$this->fabrica}
		AND o.os = {$os['os']};
	";

        $qry = $pdo->query($sql);

        if (!$qry) {
        	$pdo->rollBack();
                return array('error' => '009 Erro interno');
        }
	
	$reincidencia = $qry->fetch();

	if (!empty($reincidencia['os_reincidente'])) {

		$justificativa_adicionais = array('reincidencia_reclamado' => $reincidencia['os_reincidente']);
		$justificativa_adicionais = json_encode($justificativa_adicionais);
		$upd = "
			UPDATE tbl_os
			SET justificativa_adicionais = '{$justificativa_adicionais}'
			WHERE fabrica = {$this->fabrica}
			AND os = {$os['os']};
		";

		$qry = $pdo->query($upd);

		if (!$qry) {
			$pdo->rollBack();
			return array('error' => '010 Erro interno');
		}
	}

        if ($dados['posto_interno'] != 't' && $dados['tecnico_proprio'] != 't') {
            $comunicado = "INSERT INTO tbl_comunicado (mensagem, tipo, fabrica, descricao, posto, obrigatorio_site, ativo)
                           VALUES ('Existe uma nova OS {$os["os"]} gerada através da integração KOF', 'Comunicado Inicial', $this->fabrica, 'Nova OS KOF aberta', {$dados["posto"]}, 't', 't');";
              $query = $pdo->query($comunicado);

            if (empty($query)) {
                $pdo->rollBack();
                return array('error' => '011 Erro interno');
            }

            if (!empty($dados['contato_email'])) {

                if(empty($externalId)) {
                    $externalId = "smtp@posvenda";
                    $remetente = "noreply@telecontrol.com.br";
                }else{
                    $remetente = $externalEmail;
                }

                $mailer = new \TcComm($externalId);

                $assunto = "Nova OS {$os['os']} KOF aberta";
                $mensagem = "Olá, Posto Autorizado {$dados['nome_fantasia']},<br /><br />\n";
                $mensagem .= "Existe uma nova OS {$os['os']} gerada através da integração KOF.<br />\n";
                $mensagem .= "Para visualizar a OS, faça login no sistema e <a href='http://posvenda.telecontrol.com.br/assist/os_press.php?os={$os['os']}' target='_blank'>clique aqui</a>.";

                $res = $mailer->sendMail(
                    trim($dados['contato_email']),
                    $assunto,
                    utf8_encode($mensagem),
                    $remetente
                );

            }

        }

        $pdo->commit();

        $this->os = (int) $os["os"];

        return $os;
        
    }

    /**
     * @param integer $os
     * @param integer $tecnico
     * @return boolean
     */
    public function updateOsTecnico($os, $tecnico)
    {
        if (empty($tecnico) || empty($os)) {
            return false;
        }

        $pdo = $this->model->getPDO();

        $this->model->update("tbl_os")
             ->setCampos(array("tecnico" => (int) $tecnico))
             ->addWhere(array("os" => (int) $os))
             ->addWhere(array("fabrica" => $this->fabrica));
        if (!$this->model->prepare()->execute()) {
            return false;
        }

        $sql = "SELECT os FROM tbl_os_extra WHERE os = {$os}";
        $query = $pdo->query($sql);

        if ($query->rowCount() == 0) {
            $sql = "INSERT INTO tbl_os_extra (os, tecnico) VALUES ({$os}, {$tecnico})";
            $query = $pdo->query($sql);

            if (empty($query)) {
                return false;
            }
        } else {
            $this->model->update("tbl_os_extra")
                 ->setCampos(array("tecnico" => (int) $tecnico))
                 ->addWhere(array("os" => (int) $os));
            if (!$this->model->prepare()->execute()) {
                return false;
            }
        }

        return true;
    }

    public function atribuirTecnico($os_id_externo, $tecnico_id_externo, $antigo_tecnico) {
        if (!strlen($os_id_externo)) {
            throw new \Exception("Erro ao atribuir técnico, ID externo da OS não informado");
        }

        if (!strlen($tecnico_id_externo)) {
            throw new \Exception("Erro ao atribuir técnico, ID externo do Técnico não informado");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $get = $api->curlGet(
            "http://telecontrol.eprodutiva.com.br/api/ordem/{$os_id_externo}/agente/atribuicao",
            $headers
        );

        $atribuiu = false;

        if (count($get["data"]) > 0) {
            foreach ($get["data"] as $atribuicao) {
                if ($atribuicao["agente"]["id"] == $tecnico_id_externo) {
                    $put = $api->curlPut(
                        "http://telecontrol.eprodutiva.com.br/api/ordem/agente/atribuicao/{$atribuicao['id']}",
                        $headers,
                        json_encode(array(
                            "statusModel" => 1
                        ))
                    );

                    if (empty($put) || $put["error"]) {
                        throw new \Exception($put["error"]["message"]);
                    }

                    $atribuiu = true;
                }
            }
        }

        if (!$atribuiu) {
            $post = $api->curlPost(
                "http://telecontrol.eprodutiva.com.br/api/ordem/{$os_id_externo}/agente",
                $headers,
                json_encode(array(
                    "agente" => array(
                        "id" => $tecnico_id_externo
                    )
                ))
            );

            if (empty($post) || $post["error"]) {
                throw new \Exception($post["error"]["message"]);
            }
        }
    }

    public function cancelaServico($os, $servico_realizado) {
        if (!strlen($os)) {
            throw new \Exception("Erro ao cancelar serviço da OS, número da OS não informado");
        }

        if (!strlen($servico_realizado)) {
            throw new \Exception("Erro ao cancelar serviço da OS, serviço realizado não informado");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $put = $api->curlPut(
            "http://telecontrol.eprodutiva.com.br/api/ordem/codigo/{$os}/servico/codigo/{$servico_realizado}",
            $headers,
            json_encode(array(
                "statusModel" => 0
            ))
        );

        file_put_contents("/mnt/webuploads/imbera/logs/os-mobile-class.log", "
            \n
            {$_SERVER['SCRIPT_FILENAME']}\n
            ".date("Y-m-d H:i:s")."\n
            inativa serviço da OS\n
            url: PUT http://telecontrol.eprodutiva.com.br/api/ordem/codigo/{$os}/servico/codigo/{$servico_realizado}\n
            corpo: ".json_encode(array("statusModel" => 0))."\n
            retorno: {$put->response}\n
        ");

        if (empty($put) || $put["error"]) {
            throw new \Exception($put["error"]["message"]);
        }
    }

    public function cancelaAtribuicoes($id_externo) {
        if (!strlen($id_externo)) {
            throw new \Exception("Erro ao cancelar atribuições do técnico, ID externo não informado");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $get = $api->curlGet(
            "http://telecontrol.eprodutiva.com.br/api/ordem/{$id_externo}/agente/atribuicao",
            $headers
        );

        if (!isset($get["data"]) || !count($get["data"])) {
            throw new \Exception("Erro na transferência de técnico, não foi possível buscar informações do agendamento");
        }

        foreach ($get["data"] as $atribuicao) {
            if ($atribuicao["statusModel"] != 0) {
                $put = $api->curlPut(
                    "http://telecontrol.eprodutiva.com.br/api/ordem/agente/atribuicao/{$atribuicao['id']}",
                    $headers,
                    json_encode(array(
                        "statusModel" => 0
                    ))
                );

                if (empty($put) || $put["error"]) {
                    throw new \Exception($put["error"]["message"]);
                }
            }
        }
    }

    public function transferirOs($os, $tecnico, $data, $antigo_tecnico = false)
    {
        if (!strlen($os)) {
            throw new \Exception("Erro na transferência de técnico, Ordem de Serviço não informada");
        }

        if (!strlen($tecnico)) {
            throw new \Exception("Erro na transferência de técnico, técnico não informado");
        }

        list($dia, $mes, $ano) = explode("-", $data);

        $data_aux = strtotime("{$ano}-{$mes}-{$dia}");

        if (!$data_aux) {
            throw new \Exception("Erro na transferência de técnico, data inválida");
        }

        $pdo = $this->model->getPDO();

        $sql = "
            SELECT os_numero
            FROM tbl_os
            WHERE fabrica = {$this->fabrica}
            AND os = {$os}
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro na transferência de técnico, não foi possível buscar a Ordem de Serviço");
        }

        if ($qry->rowCount() == 0) {
            throw new \Exception("Erro na transferência de técnico, Ordem de Serviço não encontrada");
        }

        $res = $qry->fetch();
        $id_externo = $res["os_numero"];

        $sql = "
            SELECT codigo_externo
            FROM tbl_tecnico
            WHERE fabrica = {$this->fabrica}
            AND tecnico = {$tecnico}
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro na transferência de técnico, não foi possível buscar o Técnico");
        }

        if ($qry->rowCount() == 0) {
            throw new \Exception("Erro na tranferência de técnico, Técnico não encontrado");
        }

        $res = $qry->fetch();
        $tecnicoClass = new \Posvenda\Cockpit\Tecnico($this->fabrica, $this->model);
        $tecnico_id_externo = $tecnicoClass->getIdExterno($res["codigo_externo"]);

        if (!$tecnico_id_externo) {
            throw new \Exception("Erro na transferência de técnico, não foi possível buscar o id externo do técnico");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        if ($antigo_tecnico == true) {
            $this->cancelaAtribuicoes($id_externo);
        }

        $this->atribuirTecnico($id_externo, $tecnico_id_externo, $antigo_tecnico);

        try {
            $reagenda = $this->reagendaOs($os, $data);

            if (empty($reagenda) || $reagenda["error"]) {
                throw new \Exception($reagenda["error"]["message"]);
            }
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return true;
    }

    public function desagendarOs($id_externo) {
        if (!strlen($id_externo)) {
            throw new \Exception("Erro ao desagendar OS, ID externo não informado");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $this->cancelaAtribuicoes($id_externo);

        $delete = $api->curlDelete(
            "http://telecontrol.eprodutiva.com.br/api/ordem/{$id_externo}/agendamento",
            $headers
        );

        return $delete;
    }

    public function reagendaOs($os, $data) {
        if (!strlen($os)) {
            throw new \Exception("Erro no reagendamento, Ordem de Serviço não informada");
        }

        list($dia, $mes, $ano) = explode("-", $data);

        $data = strtotime("{$ano}-{$mes}-{$dia} 12:00:00");

        if (!$data) {
            throw new \Exception("Erro no reagendamento, data inválida");
        }
        

        $pdo = $this->model->getPDO();

        $sql = "
            SELECT os_numero
            FROM tbl_os
            WHERE fabrica = {$this->fabrica}
            AND os = {$os}
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro no reagendamento, não foi possível buscar a Ordem de Serviço");
        }

        if ($qry->rowCount() == 0) {
            throw new \Exception("Erro no reagendamento, Ordem de Serviço não encontrada");
        }

        $res = $qry->fetch();
        $id_externo = $res["os_numero"];

        $data = $data * 1000;

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $put = $api->curlPut(
            "http://telecontrol.eprodutiva.com.br/api/ordem/{$id_externo}/agendamento",
            $headers,
            json_encode(array(
                "dataAgendamentoInicio" => $data,
                "dataAgendamentoFim"    => $data + 1000
            ))
        );

        return $put;
    }

    public function updateTicket($id_externo, $informacoes) {
        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $put = $api->curlPut(
            "http://telecontrol.eprodutiva.com.br/api/ordem/{$id_externo}",
            $headers,
            json_encode($informacoes)
        );

        return $put;
    }

    public function updateSituacao($id_externo, $informacoes) {
	$headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $put = $api->curlPut(
            "http://telecontrol.eprodutiva.com.br/api/ordem/{$id_externo}/situacao",
            $headers,
            json_encode($informacoes)
        );

        return $put;
    }

    /**
     * @param integer $os
     * @return array
     */
    public function exportaOs($os)
    {
        $pdo = $this->model->getPDO();

        $sql = "SELECT 
                    tbl_os.os as codigo,
                    tbl_os.data_abertura,
                    tbl_tecnico_agenda.data_agendamento as \"dataAgendamento\",
                    tbl_defeito_reclamado.descricao as assunto,
                    (tbl_defeito_reclamado.codigo || '_' || substring(tbl_familia.descricao from 1 for 3)) AS defeito_reclamado_codigo,
                    'Telecontrol' as fonte,
                    'PS2' as \"situacaoOrdem\",
                    tbl_os.consumidor_cep as cep,
                    tbl_os.consumidor_endereco as logradouro,
                    tbl_os.consumidor_numero as numero,
                    tbl_os.consumidor_bairro as bairro,
                    tbl_os.consumidor_cidade as cidade,
                    tbl_os.consumidor_estado as estado,
                    tbl_os.consumidor_fone as fone,
                    tbl_tipo_atendimento.codigo_externo as \"ordemTipo\",
                    tbl_tipo_atendimento.grupo_atendimento,
                    tbl_familia.descricao AS familia,
                    tbl_os.consumidor_nome as \"razaoNome\",
                    tbl_tecnico.codigo_externo as \"agenteCodigo\",
                    tbl_produto.referencia as \"equipamentoCodigo\",
                    tbl_os.hd_chamado,
                    tbl_os.os_numero as id_externo,
                    tbl_tecnico_agenda.tecnico,
                    tbl_os_produto.serie,
                    tbl_os_extra.serie_justificativa AS patrimonio
                FROM tbl_os
                INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
                INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
                INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
                LEFT JOIN tbl_tecnico_agenda ON tbl_tecnico_agenda.os = tbl_os.os
                LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico
                WHERE tbl_os.os = $os
                ORDER BY data_agendamento DESC LIMIT 1";
        $query = $pdo->query($sql);

        if (empty($query)) {
            return array();
        }

        $os = $query->fetch(\PDO::FETCH_ASSOC);

        //Caso já exista a OS na persys apenas agenda a OS novamente
        if (strlen($os["id_externo"]) > 0) {
            list($data, $hora)     = explode(" ", $os["dataAgendamento"]);
            list($ano, $mes, $dia) = explode("-", $data);

            try {
                $this->transferirOs($os["codigo"], $os["tecnico"], "{$dia}-{$mes}-{$ano}");

                return array(
                    "agendado" => true
                );
            } catch(\Exception $e) {
                return array(
                    "error" => array(
                        "message" => $e->getMessage()
                    )
                );
            }
        }

        $this->model->select('tbl_hd_chamado_cockpit')
            ->setCampos(array('dados', 'codigo', 'create_at'))
            ->addJoin(array(
                'tbl_hd_chamado_cockpit_prioridade' => 'USING (hd_chamado_cockpit_prioridade)',
                'tbl_routine_schedule_log' => 'USING(routine_schedule_log)'
            ))
            ->addWhere(array('hd_chamado' => $os["hd_chamado"]));

        $dadosCockpit = array('idCliente' => null);

        if ($this->model->prepare()->execute()) {
            $res = $this->model->getPDOStatement()->fetch(\PDO::FETCH_ASSOC);

            if (!empty($res)) {
                $dadosCockpit = json_decode($res["dados"], true);
                $prioridade = $res["codigo"];

                //Data Criação KOF
                $data_criacao_kof = $dadosCockpit["dataAbertura"];
		
                list($data_kof, $hora_kof) = explode(" ", $data_criacao_kof);
                list($dia, $mes, $ano) = explode("/", $data_kof);

                $data_criacao_kof = strtotime("$ano-$mes-$dia $hora_kof") * 1000;

                //Data Processamento Telecontrol
                $data_processamento_telecontrol = strtotime($res["create_at"]) * 1000;
            }
        }

        $dataAgendamento = strtotime($os["dataAgendamento"]) * 1000;

        $dados = array(
            "dataCriacao" => $data_criacao_kof,
            "dataAlteracao" => $data_processamento_telecontrol,
            "codigo" => "{$os['codigo']}",
            "dataAgendamentoInicio" => $dataAgendamento,
            "dataAgendamentoFim" => $dataAgendamento + 1000,
            "assunto" => utf8_encode($os["assunto"]),
            "fonte" => $os["fonte"],
            "cliente" => array(
                "razaoNome" => $os["razaoNome"],
                "codigo" => $dadosCockpit["idCliente"]
            ),
            "endereco" => array(
                "logradouro" => $os["logradouro"],
                "cep" => $os["cep"],
                "numero" => $os["numero"],
                "bairro" => $os["bairro"],
                "cidade" => $os["cidade"],
                "estado" => $os["estado"]
            ),
            "agendada" => true,
            "atribuida" => true,
            "agendaOrdemAgente" => array(
                array(
                    "agente" => array(
                        "codigo" => $os["agenteCodigo"]
                    )
                )
            ),
            "recursoEquipamento" => array(
                array(
                    "equipamento" => array(
                        "codigo" => $os["equipamentoCodigo"]
                    ),
                    "equipamentoNumeroSerie" => array(
                        "numeroSerie" => $os["serie"],
                        "patrimony" => $os["patrimonio"]
                    )
                )
            ),
            "ordemTipo" => array(
                "codigo" => $os["ordemTipo"]
            ),
            "situacaoOrdem" => array(
                "codigo" => $os["situacaoOrdem"]
            ),
            "informacaoAdicional" => trim($dadosCockpit["comentario"].' OS KOF: '. $dadosCockpit['osKof']),
            "contato" => array(
                "nome" => $os["razaoNome"],
                "telefoneFixo" => $os["fone"]
            ),
            "prioridade" => array(
                "codigo" => $prioridade
            )
        );

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        file_put_contents("/mnt/webuploads/imbera/logs/{$os["codigo"]}.log", json_encode($dados));

        $api = new Api();

        $post = $api->curlPost(
            "http://telecontrol.eprodutiva.com.br/api/ordem",
            $headers,
            json_encode($dados)
        );

        unset($id_externo);

        if (array_key_exists("id", $post)) {
            $id_externo = $post["id"];
        } else {
            $get = $api->curlGet(
                "http://telecontrol.eprodutiva.com.br/api/ordem/codigo/{$os['codigo']}",
                $headers
            );

            $id_externo = $get["id"];
        }

        if ($id_externo) {
            $dados = json_encode(array(
                "os" => $os["codigo"],
                "exportada" => date('Y-m-d H:i:s')
            ));

            $pdo = $this->model->getPDO();

            $sql = "INSERT INTO tbl_os_mobile (
                        fabrica,
                        os,
                        dados,
                        conferido,
                        data_input
                    ) VALUES (
                        {$this->fabrica},
                        {$os["codigo"]},
                        '{$dados}',
                        't',
                        '".date('Y-m-d H:i:s')."'
                    )";

            $query = $pdo->query($sql);

            $sql = "SELECT admin FROM tbl_admin WHERE fabrica = {$this->fabrica} AND login = 'rotinaautomatica'";
            $query = $pdo->query($sql);

            $res = $query->fetch();

            $admin = $res["admin"];

            $sql = "
                UPDATE tbl_os SET
                    os_numero = {$id_externo}
                WHERE os = {$os['codigo']}
                AND fabrica = {$this->fabrica}
            ";
            $query = $pdo->query($sql);

	    $sql = "
		UPDATE tbl_hd_chamado_cockpit SET
			motivo_erro = NULL
		WHERE fabrica = {$this->fabrica}
		hd_chamado = {$os['hd_chamado']}
	    ";
	    $query = $pdo->query($sql);

            $sql = "INSERT INTO tbl_os_interacao (os,data,admin,comentario,interno,fabrica) values ({$os['codigo']},now(),{$admin},'OS exportada para aplicativo móvel',false,158)";
            $query = $pdo->query($sql);


            $os_base_conhecimento = $api->curlPost(
                "http://telecontrol.eprodutiva.com.br/api/ordem/codigo/{$os['codigo']}/baseconhecimento",
                $headers,
                json_encode(array(
                    "baseConhecimento" => array(
                        "codigo" => $os["defeito_reclamado_codigo"]
                    )
                ))
            );

            if ($os["grupo_atendimento"] == "S") {
                if ($os["familia"] == "POST MIX") {
                    $laudo = $api->curlPost(
                        "http://telecontrol.eprodutiva.com.br/api/ordem/{$id_externo}/servico",
                        $headers,
                        json_encode(array(
                            "id" => $this->_postMixLaudoId
                        ))
                    );
                } else if ($os["familia"] == "CHOPEIRA") {
                    $laudo = $api->curlPost(
                        "http://telecontrol.eprodutiva.com.br/api/ordem/{$id_externo}/servico",
                        $headers,
                        json_encode(array(
                            "id" => $this->_chopeiraLaudoId
                        ))
                    );
                }
            }
        }

        return $post;
    }

    /**
     * @param integer $os
     * @param integer $posto
     * @return boolean
     */
    public function setPostoOS($os, $posto)
    {
        $this->model->update("tbl_os")
                    ->setCampos(array("posto" => (int) $posto))
                    ->addWhere(array("os" => (int) $os));

        if (!$this->model->prepare()->execute()) {
            return false;
        }

        return true;
    }

    /**
     * @param object $distance Objeto retornado de uma consulta a API do Google Maps
     * @return boolean
     */
    public function gravaKm($distance)
    {
        if (empty($distance->rows[0]->elements[0]->distance->value)) {
            return false;
        }

        $value = $distance->rows[0]->elements[0]->distance->value;

        $km = $value / 1000;

        $this->model->update("tbl_os")
            ->setCampos(array("qtde_km" => $km))
            ->addWhere(array("os" => $this->os));

        if (!$this->model->prepare()->execute()) {
            return false;
        }

        $this->model->update("tbl_hd_chamado_extra")
            ->setCampos(array("qtde_km" => $km))
            ->addWhere(array("os" => $this->os));

        if (!$this->model->prepare()->execute()) {
            return false;
        }

        return true;
    }

    /**
     * @param object $distanceGoing Objeto retornado de uma consulta a API do Google Maps
     * @param object $distanceComeBack Objeto retornado de uma consulta a API do Google Maps
     * @return boolean
     */
    public function gravaKmTerceiro($distanceGoing, $distanceComeBack)
    {
        if (empty($distanceGoing->rows[0]->elements[0]->distance->value)) {
            return false;
        }

        $valueGoing = $distanceGoing->rows[0]->elements[0]->distance->value;

        if (empty($distanceComeBack->rows[0]->elements[0]->distance->value)) {
            return false;
        }

        $valueComeBack = $distanceComeBack->rows[0]->elements[0]->distance->value;

        $km = ($valueGoing + $valueComeBack) / 1000;

        $this->model->update("tbl_os")
            ->setCampos(array("qtde_km" => $km))
            ->addWhere(array("os" => $this->os));

        if (!$this->model->prepare()->execute()) {
            return false;
        }

        $this->model->update("tbl_hd_chamado_extra")
            ->setCampos(array("qtde_km" => $km))
            ->addWhere(array("os" => $this->os));

        if (!$this->model->prepare()->execute()) {
            return false;
        }

        return true;
        
    }

    public function getOsIdExterno($os, $con = null) {
        if (empty($os)) {
            throw new \Exception("Erro ao buscar ID externo, número da OS não informado");
        }

        if (is_null($con)) {
            $pdo = $this->model->getPDO();
        }

        $sql = "
            SELECT os_numero
            FROM tbl_os
            WHERE fabrica = {$this->fabrica}
            AND os = {$os}
        ";
        if (is_null($con)) {
            $qry = $pdo->query($sql);

            if ($qry->rowCount() == 0) {
                throw new \Exception("OS {$os} não encontrada");
            }

            $res = $qry->fetch();
        } else {
            $qry = pg_query($con, $sql);

            if (!pg_num_rows($qry)) {
                throw new \Exception("OS {$os} não encontrada");
            }

            $res = pg_fetch_assoc($qry);
        }

        if (!$qry) {
            throw new \Exception("Erro ao buscar o ID externo da OS {$os}");
        }

        return $res["os_numero"];
    }

    public function getServicosOs($os_id_externo) {
        if (empty($os_id_externo)) {
            throw new \Exception("Erro ao buscar serviços da OS, ID Externo não informado");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $get = $api->curlGet(
            "http://telecontrol.eprodutiva.com.br/api/ordem/{$os_id_externo}/servico",
            $headers
        );

        file_put_contents("/mnt/webuploads/imbera/logs/os-mobile-class.log", "
            \n
            {$_SERVER['SCRIPT_FILENAME']}\n
            ".date("Y-m-d H:i:s")."\n
            busca de serviços da OS\n
            url: GET http://telecontrol.eprodutiva.com.br/api/ordem/{$os_id_externo}/servico\n
            retorno: {$get->response}\n
        ");

        if (empty($get)) {
            throw new \Exception("Erro ao buscar serviços da OS");
        }

        return $get["data"];
    }

    public function getServico($servico) {
        if (empty($servico)) {
            throw new \Exception("Erro ao buscar serviço realizado");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $get = $api->curlGet(
            "http://telecontrol.eprodutiva.com.br/api/servico/codigo/{$servico}",
            $headers
        );

        file_put_contents("/mnt/webuploads/imbera/logs/os-mobile-class.log", "
            \n
            {$_SERVER['SCRIPT_FILENAME']}\n
            ".date("Y-m-d H:i:s")."\n
            busca de serviço realizado\n
            url: GET http://telecontrol.eprodutiva.com.br/api/servico/codigo/{$servico}\n
            retorno: {$get->response}\n
        ");

        if (empty($get)) {
            throw new \Exception("Erro ao buscar serviço realizado");
        }

        return $get;
    }

    public function statusVinculoPecaServico($os_id_externo, $peca_id_externo, $servico_id_externo, $status) {
        //$status deve ser 0 ou 1

        if (empty($os_id_externo) || empty($peca_id_externo) || empty($servico_id_externo)) {
            throw new \Exception("Erro ao vincular Serviço a Peça da Ordem de Serviço");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $put = $api->curlPut(
            "http://telecontrol.eprodutiva.com.br/api/ordem/{$os_id_externo}/material/{$peca_id_externo}/servico/{$servico_id_externo}",
            $headers,
            json_encode(array(
                "statusModel" => $status
            ))
        );

        file_put_contents("/mnt/webuploads/imbera/logs/os-mobile-class.log", "
            \n
            {$_SERVER['SCRIPT_FILENAME']}\n
            ".date("Y-m-d H:i:s")."\n
            ativa/inativa status de um vincluco de peça x serviço em uma OS\n
            url: PUT http://telecontrol.eprodutiva.com.br/api/ordem/{$os_id_externo}/material/{$peca_id_externo}/servico/{$servico_id_externo}\n
            corpo: ".json_encode(array("statusModel" => $status))."\n
            retorno: {$put->response}\n
        ");

        if (empty($put) || $put["error"]) {
            if (empty($put["error"]["message"])) {
                $error = "Erro ao alterar o status do vinculo do Serviço com a Peça";
            } else {
                $error = $put["error"]["message"];
            }

            throw new \Exception($error);
        }

        return $put;
    }

    public function vincularPecaServico($os_id_externo, $peca_id_externo, $servico_id_externo) {
        if (empty($os_id_externo) || empty($peca_id_externo) || empty($servico_id_externo)) {
            throw new \Exception("Erro ao vincular Serviço a Peça da Ordem de Serviço");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $post = $api->curlPost(
            "http://telecontrol.eprodutiva.com.br/api/ordem/{$os_id_externo}/material/{$peca_id_externo}/servico",
            $headers,
            json_encode(array(
                "servico" => array(
                    "id" => $servico_id_externo
                )
            ))
        );

        file_put_contents("/mnt/webuploads/imbera/logs/os-mobile-class.log", "
            \n
            {$_SERVER['SCRIPT_FILENAME']}\n
            ".date("Y-m-d H:i:s")."\n
            vincular peça a um serviço em uma OS\n
            url: POST http://telecontrol.eprodutiva.com.br/api/ordem/{$os_id_externo}/material/{$peca_id_externo}/servico\n
            corpo: ".json_encode(array("servico" => array("id" => $servico_id_externo)))."\n
            retorno: {$post->response}\n
        ");

        return $post;
    }

    public function vincularServicoOs($os_id_externo, $servico_id_externo) {
        if (empty($os_id_externo) || empty($servico_id_externo)) {
            throw new \Exception("Erro ao vincular Serviço a Ordem de Serviço");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $post = $api->curlPost(
            "http://telecontrol.eprodutiva.com.br/api/ordem/{$os_id_externo}/servico",
            $headers,
            json_encode(array(
                "id" => $servico_id_externo
            ))
        );

        file_put_contents("/mnt/webuploads/imbera/logs/os-mobile-class.log", "
            \n
            {$_SERVER['SCRIPT_FILENAME']}\n
            ".date("Y-m-d H:i:s")."\n
            vincular serviço a OS\n
            url: POST http://telecontrol.eprodutiva.com.br/api/ordem/{$os_id_externo}/servico\n
            corpo: ".json_encode(array("id" => $servico_id_externo))."\n
            retorno: {$post->response}\n
        ");

        if (empty($post) || $post["error"]) {
            if (empty($post["error"]["message"])) {
                $error = "Erro ao vincular Serviço com OS";
            } else {
                $error = $post["error"]["message"];
            }

            throw new \Exception($error);
        }

        return $post;
    }

    public function getMobileStatus($status) {
        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $get = $api->curlGet(
            "http://telecontrol.eprodutiva.com.br/api/ordem/situacao/codigo/{$status}",
            $headers
        );

        return $get;
    }

    public function getPecaIdExterno($peca_referencia) {
        if (empty($peca_referencia)) {
            throw new \Exception("Erro ao buscar id externo da peça, referência não informada");
        }

        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $get = $api->curlGet(
            "http://telecontrol.eprodutiva.com.br/api/recurso/material/codigo/{$peca_referencia}",
            $headers
        );

        file_put_contents("/mnt/webuploads/imbera/logs/os-mobile-class.log", "
            \n
            {$_SERVER['SCRIPT_FILENAME']}\n
            ".date("Y-m-d H:i:s")."\n
            busca id externo da peça\n
            url: GET http://telecontrol.eprodutiva.com.br/api/recurso/material/codigo/{$peca_referencia}\n
            retorno: {$get->response}\n
        ");

        if (empty($get)) {
            throw new \Exception("Erro ao buscar peça");
        }

        return $get;   
    }

    public function getPecasOsMobile($os_mobile) {
        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $get = $api->curlGet(
            "http://telecontrol.eprodutiva.com.br/api/ordem/{$os_mobile}/recurso/material",
            $headers
        );

        if (!$get["data"]) {
            return array();
        }

        return $get["data"];
    }

    public function finalizaOsMobile($id_externo) {
        if (empty($id_externo)) {
            throw new \Exception("ID externo da OS não informado");
        }

        $status_finalizada = $this->getMobileStatus("PS5");

        if (!isset($status_finalizada["id"])) {
            throw new \Exception("Erro ao buscar status da OS");
        }

        $dados = array(
            "situacaoOrdem" => array(
                "id" => $status_finalizada["id"]
            )
        );

        return $res = $this->updateSituacao($id_externo, $dados);
    }

    public function cancelaOsMobile($os, $con = null) {
        /*if (empty($id_externo)) {
            throw new \Exception("ID externo da OS não informado");
        }

        $status_finalizada = $this->getMobileStatus("PS7");

        if (!isset($status_finalizada["id"])) {
            throw new \Exception("Erro ao buscar status da OS");
        }

        $dados = array(
            "situacaoOrdem" => array(
                "id" => $status_finalizada["id"]
            )
        );

        return $res = $this->updateSituacao($id_externo, $dados);*/

        if (empty($os)) {
            throw new \Exception("Ordem de Serivço não informada");
        }

        if (is_null($con)) {
            $pdo = $this->model->getPDO();
        }

        $sql = "
            SELECT o.exportado, t.codigo_externo AS tecnico
            FROM tbl_os o
            LEFT JOIN tbl_tecnico t ON t.tecnico = o.tecnico AND t.posto = o.posto AND t.codigo_externo IS NOT NULL
            WHERE o.os = {$os} 
            AND o.fabrica = {$this->fabrica}";
        if (is_null($con)) {
            $qry  = $pdo->query($sql);
            $rows = $qry->rowCount();
        } else {
            $res  = pg_query($con, $sql);
            $rows = pg_num_rows($res);
        }

        if ($rows == 0) {
            throw new \Exception("Ordem de Serviço não encontrada");
        }

        if (is_null($con)) {
            $res = $qry->fetch();
        } else {
            $res = pg_fetch_assoc($res);
        }

        if (!empty($res["exportado"]) && !empty($res["tecnico"])) {
            $sql =  "SELECT token_dispositivo FROM tbl_login_unico WHERE login_unico = {$res['tecnico']}";
            if (is_null($con)) {
                $qry = $pdo->query($sql);
                $rows = $qry->rowCount();
            } else {
                $res = pg_query($con, $sql);
                $rows = pg_num_rows($res);
            }

            if ($rows == 0) {
                throw new \Exception("Erro ao buscar informações do técnico");
            }

            if (is_null($con)) {
                $res = $qry->fetch();
            } else {
                $res = pg_fetch_assoc($res);
            }

            if (!empty($res["token_dispositivo"])) {
                $notificacao = $this->enviaNotificacao($res["token_dispositivo"], array(
                    "titulo" => utf8_encode("Ordem de Serviço cancelada"),
                    "os" => $os,
                    "status" => array( "id" => 28, "descricao" => "OS Cancelada")
                ));

                if (!$notificacao) {
                    throw new \Exception("Erro ao enviar notificação de cancelamento");
                }
            }
        }
    }

    public function enviaNotificacao($dispositivo, $data) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://fcm.googleapis.com/fcm/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => json_encode(array(
                "to" => $dispositivo,
                "data" => $data
            )),
            CURLOPT_HTTPHEADER => array(
                "authorization: key=AIzaSyBl599pvrGFEhCfS9uLtpnhQKIxStBsmZ8",
                "cache-control: no-cache",
                "content-type: application/json"
            )
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            return false;
        } else {
            $response = json_decode($response, true);

            if ($httpcode != 200 || ($response["failure"] > 0 && $response["results"][0]["error"] != "NotRegistered")) {
                return false;
            }

            return true;
        }
    }

    public function verificaOsAgendada($ticket) {
        $pdo = $this->model->getPDO();

        $sql = "
            SELECT os.os_numero, hdc.motivo_erro, os.os, ta.tecnico_agenda, tp.tecnico_proprio
            FROM tbl_hd_chamado_cockpit hdc
            LEFT JOIN tbl_hd_chamado hd ON hd.hd_chamado = hdc.hd_chamado AND hd.fabrica = {$this->fabrica}
            LEFT JOIN tbl_os os ON os.hd_chamado = hd.hd_chamado AND os.fabrica = {$this->fabrica}
            LEFT JOIN tbl_posto_fabrica pf ON pf.posto = os.posto AND pf.fabrica = {$this->fabrica}
            LEFT JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$this->fabrica}
            LEFT JOIN tbl_tecnico_agenda ta ON ta.os = os.os AND ta.fabrica = {$this->fabrica}
            WHERE hdc.fabrica = {$this->fabrica}
            AND hdc.hd_chamado_cockpit = {$ticket}
        ";
        $qry = $pdo->query($sql);

        if ($qry->rowCount() == 0) {
            return false;
        }

        $res = $qry->fetch();

        if (!empty($res["os"])) {
            return true;
        }

        return false;
    }

    public function inativaProdutoOsMobile($os, $produto_os_mobile) {
        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $put = $api->curlPut(
            "http://telecontrol.eprodutiva.com.br/api/ordem/codigo/{$os}/recurso/equipamento/codigo/{$produto_os_mobile}",
            $headers,
            json_encode(array(
                "statusModel" => 0
            ))
        );

        if (empty($put) || $put["error"]) {
            throw new \Exception($put["error"]["message"]);
        }

        return $put;
    }

    public function gravaProdutoOsMobile($os, $produto_lancado, $serie, $patrimonio) {
        $headers = array(
            "Content-Type: application/json",
            "Authorizationv2: {$this->_persysAuthorizationKey}"
        );

        $api = new Api();

        $post = $api->curlPost(
            "http://telecontrol.eprodutiva.com.br/api/ordem/codigo/{$os}/recurso/equipamento",
            $headers,
            json_encode(array(
                "equipamento" => array(
                    "codigo" => $produto_lancado
                ),
                "equipamentoNumeroSerie" => array(
                    "numeroSerie" => $serie,
                    "patrimony" => $patrimonio
                )
            ))
        );

        if (empty($post) || $post["error"]) {
            throw new \Exception($post["error"]["message"]);
        }

        return $post;
    }

    public function confirmaServicoAlteradoPersys($os_mobile, $peca_referencia) {
        $apiClass = new Api();
        $apiClass->setApiResource('callcenter');

        $api = $apiClass->getApi();

        $headers = array(
            'Content-Type: application/json',
            'access-application-key: ' . $api["app-key"],
            'access-env: ' . $apiClass->getEnv()
        );

        $res = $apiClass->curlPost(
            $api["url"] . '/alteraServicoPersys',
            $headers,
            json_encode(array(
                "osMobile"  => $os_mobile,
                "referencia" => $peca_referencia
            ))
        );

        if (empty($res) || isset($res["exception"])) {
            throw new \Exception("Erro ao confirmar alteração de serviço no mobile");
        }

        return true;
    }
}
