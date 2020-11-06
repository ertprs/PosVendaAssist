<?php

namespace Posvenda\Cockpit;

use Posvenda\Regras;
use Posvenda\Cockpit\Api;
use Posvenda\Cockpit;

class Hd
{
    /**
     * @var mixed
     */
    private $fabrica;
    private $dePara = array(
        "158" => array(
            "KOF" => array(
               //"campo-arquivo"  => "campo-apiCallcenter"
               "nomeFantasia"     => "nome",
               "enderecoCliente"  => "endereco",
               "bairroCliente"    => "bairro",
               "cepCliente"       => "cep",
               "cidadeCliente"    => "cidade",
               "estadoCliente"    => "estado",
               "telefoneCliente"  => "fone",
               "telefoneCliente2" => "fone2",
               "modeloKof"        => "produto",
               "osKof"            => "sua_os",
               "defeito"          => "defeitoReclamado",
               "dataAbertura"     => "dataAbertura",
               "nomeContato"      => "contatoNome",
               "comentario"       => "obs",
               "numeroSerie"      => "serie",
               "distancia"        => "qtde_km",
               "tipoOrdem"        => "tipoAtendimento",
               "patrimonioKof"    => "patrimonioKof"
            )
        )
    );
    private $camposAdicionais = array(
        "158" => array(
            "KOF" => array(
                "admin"                 => null,
                "atendente"             => null,
                "categoria"             => "reclamacao_produto",
                "titulo"                => "Atendimento Interativo",
                "fabricaResponsavel"    => null,
                "fabrica"               => null,
                "estaAgendado"          => false,
                "diasAberto"            => 0,
                "diasUltimaInteracao"   => 0,
                "receberInfoFabrica"    => false,
                "origem"                => "Telefone",
                "consumidorRevenda"     => "C",
                "garantia"              => false,
                "abreOs"                => false,
                "atendimentoCallcenter" => false,
                "status"                => "Aberto"
            )
        )
    );

    /**
     * @var object
     */
    private $model;

    public function __construct($fabrica, \Posvenda\Model\GenericModel $model)
    {
        $this->fabrica = $fabrica;
        $this->model = $model;
    }

    /**
     * @return array
     */
    public function getCockpitSemHD($prioridade_alta = true, $prioridade_baixa = true, $erro = false)
    {

        $pdo = $this->model->getPDO();

        if ($prioridade_alta == true) {
            $pesos = "4,5";
        }

        if ($prioridade_baixa == true) {
            if (!empty($pesos)) {
                $pesos .=",";
            }
            $pesos .= "0,1,2,3";
        }

        if ($erro == true) {
            $whereMotivoErro = "AND (tbl_hd_chamado_cockpit.motivo_erro IS NOT NULL OR LENGTH(tbl_hd_chamado_cockpit.motivo_erro) > 0)";
        } else {
            $whereMotivoErro = "AND (tbl_hd_chamado_cockpit.motivo_erro IS NULL OR LENGTH(tbl_hd_chamado_cockpit.motivo_erro) = 0)";
        }

        //adicionado left em tbl_hd_chamado_cockpit_prioridade e tbl_routine_schedule_log por conta do projeto ambev 

        $sql = "
            SELECT
                tbl_hd_chamado_cockpit.hd_chamado_cockpit,
                tbl_hd_chamado_cockpit.dados,
                tbl_hd_chamado_cockpit.hd_chamado_cockpit_prioridade,
                tbl_hd_chamado_cockpit.motivo_erro,
                tbl_hd_chamado_cockpit.geolocalizacao,
                tbl_routine_schedule_log.file_name
            FROM tbl_hd_chamado_cockpit
            LEFT JOIN tbl_routine_schedule_log USING(routine_schedule_log)
            LEFT JOIN tbl_hd_chamado_cockpit_prioridade USING(hd_chamado_cockpit_prioridade)
            WHERE tbl_hd_chamado_cockpit.fabrica = {$this->fabrica}
            AND tbl_hd_chamado_cockpit.hd_chamado IS NULL
            AND tbl_hd_chamado_cockpit_prioridade.peso IN ({$pesos})
            {$whereMotivoErro}
            ORDER BY tbl_hd_chamado_cockpit.motivo_erro DESC;
        ";
        $qry = $pdo->prepare($sql);
        
        if ($qry->execute()) {
            return $qry->fetchAll(\PDO::FETCH_ASSOC);
        }

        return array();

    }

    private function deParaKOF($dados) {
        $deParaKOF = $this->dePara[$this->fabrica]["KOF"];

        $array = array();

        foreach($deParaKOF as $kofName => $callcenterName){
            $array[$callcenterName] = trim($dados[$kofName]);
        }

        if (array_key_exists("codDefeito", $dados)) {
            $array["defeitoReclamado"] = $dados["codDefeito"];
        }

        return $array;
    }

    private function camposAdicionaisKOF() {
        return $this->camposAdicionais[$this->fabrica]["KOF"];
    }

    private function getAdminMaster() {
        $pdo = $this->model->getPDO();

        $sql = "SELECT admin FROM tbl_admin WHERE fabrica = {$this->fabrica} AND privilegios = '*' AND ativo IS TRUE";
        $qry = $pdo->query($sql);

        if (!$qry) {
            return null;
        }

        if ($qry->rowCount() == 0) {
            return null;
        }

        $res = $qry->fetch();

        return $res["admin"];
    }

    /**
     * Valida as informações do ticket para saber se será possível a abertura de um HD
     * @param array $dados json de dados do ticket
     * @return array
     */
    public function validaHD($dados) {
        $cockpitClass = new Cockpit($this->fabrica);

        $produto = $cockpitClass->getProdutoByRef($dados["modeloKof"], true);

        if ($produto["linha_nome"] != "REFRIGERADOR") {
            $garantia = "";
        } else {
            $garantia = $dados["garantia"];
        }

        $tipo_atendimento = $cockpitClass->getTipoAtendimentoKOF($dados["tipoOrdem"], $garantia);
        
        if ($tipo_atendimento['fora_garantia'] == 't') {
            $res_clienteAdmin = $this->getClienteAdmin($this->fabrica."-KOF");
        } else {
            $res_clienteAdmin = $this->getClienteAdmin($this->fabrica."-Alpunto");
        }

        $admin            = $this->getAdminMaster();

        $dadosReq                       = array_merge($this->deParaKOF($dados), $this->camposAdicionaisKOF());

        $dadosReq['cep']                = str_replace('-', '',$dadosReq["cep"]);
        $dadosReq["fabrica"]            = $this->fabrica;
        $dadosReq["fabricaResponsavel"] = $this->fabrica;
        $dadosReq["admin"]              = $admin;
        $dadosReq["atendente"]          = $admin;
        $dadosReq["tipoAtendimento"]    = $tipo_atendimento["tipo_atendimento"];
        $dadosReq["tipoOrdem"]          = $dados["tipoOrdem"];
        $dadosReq["clienteAdmin"]       = $res_clienteAdmin["cliente_admin"];

        $apiClass = new Api();
        $apiClass->setApiResource('callcenter');

        $api = $apiClass->getApi();

        $headers = array(
            'Content-Type: application/json',
            'access-application-key: ' . $api["app-key"],
            'access-env: ' . $apiClass->getEnv()
        );

        $array = $apiClass->curlPost($api["url"] . '/ValidateCallcenterData', $headers, json_encode($dadosReq));

        return $array;
    }

    public function gravaDefeitoReclamadoAdicional($hd_chamado, $defeitos, $garantia, $familia){

        $pdo = $this->model->getPDO();

        $defeito_reclamado_adicional['defeito_reclamado_adicional'] = true;
        $defeito_reclamado_adicional = json_encode($defeito_reclamado_adicional);

        foreach($defeitos as $defReclamado){
            $cockpitClass = new Cockpit($this->fabrica);
            $defeito_reclamado = $cockpitClass->getDefeitoReclamado(array("codigo" => "'{$defReclamado}'"), $familia, $garantia);
            $sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, defeito_reclamado, campos_adicionais) 
        values ($hd_chamado, ".$defeito_reclamado['defeito_reclamado'].", '$defeito_reclamado_adicional')";
            $qry = $pdo->query($sql);

        }        
    }

    /**
     * @param integer $cockpit ID da tbl_hd_chamado_cockpit
     * @param array   $dados
     * @return array
     */
    public function abreHD($cockpit, $dados)
    {

        $pdo = $this->model->getPDO();

        $sql = "SELECT hd_chamado FROM tbl_hd_chamado_cockpit WHERE fabrica = {$this->fabrica} AND hd_chamado_cockpit = {$cockpit} AND hd_chamado IS NOT NULL";
        $qry = $pdo->query();

        if ($qry && $qry->rowCount() > 0) {
            $res = $qry->fetch();
            return array("hd_chamado" => $res["hd_chamado"]);
        }

        $regras = Regras::get('cockpit_abre_hd', 'hd_chamado', $this->fabrica);

        $cockpitClass = new Cockpit($this->fabrica);

        $produto = $cockpitClass->getProdutoByRef($dados["modeloKof"], true);

        if ($produto["linha_nome"] != "REFRIGERADOR") {
            $garantia = "";
        } else {
            $garantia = $dados["garantia"];
        }

        $tipo_atendimento = $cockpitClass->getTipoAtendimentoKOF($dados["tipoOrdem"], $garantia);
        $unidade_negocio = $cockpitClass->getUnidadeNegocio($dados['centroDistribuidor']);

        if ($dados["tipoOrdem"] == "ZKR6") {
            unset($produto["familia"], $garantia);
        }

        if (empty($dados["codDefeito"]) && is_numeric($dados["defeito"]) && !preg_match("/^0/", $dados["defeito"])) {
            $defeito_reclamado = $cockpitClass->getDefeitoReclamado(array("defeito_reclamado" => $dados["defeito"]), $produto["familia"], $garantia);
        } else {
            $defeito = (empty($dados["codDefeito"])) ? $dados["defeito"] : $dados["codDefeito"];

            $defeito_reclamado = $cockpitClass->getDefeitoReclamado(array("codigo" => "'{$defeito}'"), $produto["familia"], $garantia);
        }

        $dados["codDefeito"] = $defeito_reclamado["codigo"];
        $dados["defeito"]    = $defeito_reclamado["defeito_reclamado"];

        if ($dados["tipoOrdem"] != "ZKR6" && !$cockpitClass->validaDefeitoReclamadoFamiliaGarantia($defeito_reclamado["defeito_reclamado"], $produto["familia"], false)) {
            throw new \Exception("Defeito Reclamado não encontrado para a Família do Produto");
        }

        if (empty($tipo_atendimento)) {
            throw new \Exception("Tipo de Atendimento não encontrado");
        }

        if (!$unidade_negocio) {
            throw new \Exception("Unidade de negócio não encontrada");
        }

        $tipo_atendimento = $tipo_atendimento["tipo_atendimento"];

        if ($tipo_atendimento['fora_garantia'] == 't') {
            $res_clienteAdmin = $this->getClienteAdmin($this->fabrica."-KOF");
        } else {
            $res_clienteAdmin = $this->getClienteAdmin($this->fabrica."-Alpunto");
        }

        if (!$res_clienteAdmin) {
            $clienteAdmin = 'null';
        } else {
            $clienteAdmin = $res_clienteAdmin['cliente_admin'];
        }

        $dadosReq = array(
            "admin" => $regras["admin"],
            "status" => "Aberto",
            "titulo" => $regras["titulo"],
            "atendente" => $regras["atendente"],
            "fabricaResponsavel" => $this->fabrica,
            "fabrica" => $this->fabrica,
            "clienteAdmin" => $clienteAdmin,
            "categoria" => $regras["categoria"],
            "estaAgendado" => 'f',
            "produto" => $dados["modeloKof"],
            "serie" => $dados["numeroSerie"],
            "reclamado" => $dados["defeito"],
            "defeitoReclamado" => $dados["codDefeito"],
            "diasAberto" => 0,
            "diasUltimaInteracao" => 0,
            "receberInfoFabrica" => 'f',
            "origem" => 'Cockpit',
            "consumidorRevenda" => 'C',
            "nome" => $dados["nomeFantasia"],
            "endereco" => $dados["enderecoCliente"],
            "bairro" => $dados["bairroCliente"],
            "cep" => preg_replace('/[-.]/', '', $dados["cepCliente"]),
            "fone" => $dados["telefoneCliente"],
            "cidade" => $dados["cidadeCliente"],
            "estado" => $dados["estadoCliente"],
            "cpf" => $dados['document_number'],
            "complemento" => $dados['complemento'],
            "numero" => $dados['numero_endereco'], 
            "abreOs" => 'f',
            "garantia" => (!empty($dados["garantia"])) ? 't' : 'f',
            "atendimentoCallcenter" => 'f',
            "tipoAtendimento" => $tipo_atendimento,
            "tipoOrdem" => $dados["tipoOrdem"],
            "qtde_km" => $dados["km"],
            "arrayCamposAdicionais" => $unidade_negocio
        );

        $apiClass = new Api();

        $apiClass->setApiResource('callcenter');

        $api = $apiClass->getApi();

        $headers = array(
            'Content-Type: application/json',
            'access-application-key: ' . $api["app-key"],
            'access-env: ' . $apiClass->getEnv()
        );

        $array = $apiClass->curlPost($api["url"] . '/callcenter', $headers, json_encode($dadosReq));

        if (!array_key_exists("hd_chamado", $array)) {
            return $array;
        }

        $this->model->update("tbl_hd_chamado_cockpit")
            ->setCampos(array("hd_chamado" => $array["hd_chamado"]))
            ->addWhere(array("hd_chamado_cockpit" => $cockpit));

        if(count(array_filter($dados['defReclamadoAdicional']))>0){
            echo "\n\n\n defeito reclamado adicional \n\n\n\n"; 
           $this->gravaDefeitoReclamadoAdicional($array["hd_chamado"], $dados['defReclamadoAdicional'], $dados["garantia"], $produto["familia"]);
        }

        if (!$this->model->prepare()->execute()) {
            return array();
        }

        return array("hd_chamado" => $array["hd_chamado"]);
    }

    /**
     * @param integer $hdChamado
     * @param integer $os
     * @return boolean
     */
    public function updateHDChamadoOs($hdChamado, $os)
    {
        $this->model->update('tbl_hd_chamado_extra')
            ->setCampos(array("os" => (int) $os))
            ->addWhere(array("hd_chamado" => (int) $hdChamado));

        if (!$this->model->prepare()->execute()) {
            return false;
        }

        return true;
    }

    /**
     * @param integer $hdChamado
     * @param integer $posto
     * @return boolean
     */
    public function setPostoHD($hdChamado, $posto)
    {
        $this->model->update('tbl_hd_chamado_extra')
                ->setCampos(array("posto" => (int) $posto))
                ->addWhere(array("hd_chamado" => (int) $hdChamado));

        if (!$this->model->prepare()->execute()) {
            return false;
        }

        return true;
    }

    /**
     * @param string $codigo
     * @return array
     */
    public function getClienteAdmin($codigo)
    {
        if (!empty($codigo)) {
            $this->model->select('tbl_cliente_admin')
                ->setCampos(
                    array(
                        'cliente_admin',
                        'nome'
                    )
                )
                ->addWhere(array('fabrica' => $this->fabrica))
                ->addWhere("codigo = '{$codigo}'");

            if (!$this->model->prepare()->execute()) {
                return false;
            } else if ($this->model->getPDOStatement()->rowCount() == 0) {
                return false;
            } else {
                return $this->model->getPDOStatement()->fetch(\PDO::FETCH_ASSOC);
            }
        } else {
            return false;
        }

    }

    /**
     * @param integer $hdChamadoCockpit
     * @param string  $erro
     * @return boolean
     */
    public function gravaErro($hdChamadoCockpit, $erro)
    {
        $this->model->update('tbl_hd_chamado_cockpit')
                ->setCampos(array(
                    'motivo_erro' => $erro
                ))
                ->addWhere(array(
                    'hd_chamado_cockpit' => $hdChamadoCockpit
                ));

        if (!$this->model->prepare()->execute()) {
            return false;
        }

        return true;
    }

    public function gravaGeolocalizacao($hdChamadoCockpit, $dados)
    {
        $dados = array_map(function($r) {
            return utf8_encode($r);
        }, $dados);

        $dados = json_encode($dados);

        $this->model->update('tbl_hd_chamado_cockpit')
        ->setCampos(array(
            'geolocalizacao' => $dados
        ))
        ->addWhere(array(
            'hd_chamado_cockpit' => $hdChamadoCockpit
        ));

        if (!$this->model->prepare()->execute()) {
            return false;
        }

        return true;
    }

    /**
     * @param integer $hdChamado
     * @param string $param
     * @return array
     */
    public function getDadosTicket($ticket, $param = null) {
        if (empty($ticket)) {
            throw new \Exception("Ticket não informado");
        }

        $pdo = $this->model->getPDO();

        $sql = "SELECT dados FROM tbl_hd_chamado_cockpit WHERE fabrica = {$this->fabrica} AND hd_chamado_cockpit = {$ticket}";
        $qry = $pdo->query($sql);

        if ($qry->rowCount() == 0) {
            throw new \Exception("Ticket {$ticket} não encontrado");
        } else {
            $json = $qry->fetch();
            $json = json_decode($json["dados"], true);

            if (empty($param)) {
                return $json;
            } else {
                return array($param => $json[$param]);
            }
        }
    }
}
