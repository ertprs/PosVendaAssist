<?php

namespace Posvenda;
 
include_once __DIR__.'/../../class/communicator.class.php';

use Posvenda\Model\GenericModel as Model;
use Posvenda\Regras;
use Posvenda\TcMaps;

class Cockpit
{
    /**
     * @var mixed
     */
    private $fabrica;

    /**
     * @var object
     */
    public $model;

    /**
     * @var object
     */
    private $hd;

    /**
     * @var object
     */
    private $os;

    /**
     * @var object
     */
    private $tecnico;

    /**
     * @var object
     */
    private $agenda;

    /**
     * @param integer $fabrica
     */
    public function __construct($fabrica)
    {
        $this->fabrica = $fabrica;
        $this->model = new Model();
        $this->hd = new \Posvenda\Cockpit\Hd($this->fabrica, $this->model);
        $this->os = new \Posvenda\Cockpit\Os($this->fabrica, $this->model);
        $this->tecnico = new \Posvenda\Cockpit\Tecnico($this->fabrica, $this->model);
        $this->agenda = new \Posvenda\Cockpit\Agenda($this->fabrica, $this->model);
    }

    /**
     * @param string $endereco
     * @return array
     */
    public function geocoding($endereco, $components = null)
    {
	    sleep(3);
        $tcMaps = new TcMaps($this->fabrica);
        
        return $tcMaps->geocode($endereco['endereco'], null, $endereco['bairro'], $endereco['cidade'], $endereco['estado'], $endereco['pais']);
    }

    /**
     * @param integer $osProtKof
     * @return boolean
     */
    public function cockpitExists($osProtKof = null)
    {
        if ($osProtKof != null) {
            $this->model->select("tbl_hd_chamado_cockpit")
                 ->setCampos(array("*"))
                 ->addWhere("json_field('osKof',dados) LIKE '%$osProtKof%'")
                 ->addWhere(array("fabrica" => $this->fabrica));

            $this->model->prepare()->execute();

            if ($this->model->getPDOStatement()->rowCount() == 0) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * @param string $dir
     * @param string $data
     * @return integer
     */
    public function validaQtdeArquivosRecebidos($dir, $data)
    {
        $arquivos = glob($dir . '/./KOF' . $data . '_*.txt');

        return count($arquivos);
    }

    /**
     * @return array
     */
    public function getCockpitSemHD($prioridade_alta, $prioridade_baixa, $erro)
    {
        return $this->hd->getCockpitSemHD($prioridade_alta, $prioridade_baixa, $erro);
    }

    /**
     * @param integer $cockpit ID da tbl_hd_chamado_cockpit
     * @param array   $dados
     * @return array
     */
    public function abreHD($cockpit, $dados)
    {
        return $this->hd->abreHD($cockpit, $dados);
    }

    /**
     * @param integer $hdChamado
     * @param integer $os
     * @return boolean
     */
    public function updateHDChamadoOs($hdChamado, $os)
    {
        return $this->hd->updateHDChamadoOs($hdChamado, $os);
    }

    /**
     * @param integer $hdChamado
     * @param integer $posto
     * @return boolean
     */
    public function setPostoHD($hdChamado, $posto)
    {
        return $this->hd->setPostoHD($hdChamado, $posto);
    }

    public function gravaErro($hdChamadoCockpit, $erro)
    {
        return $this->hd->gravaErro($hdChamadoCockpit, $erro);
    }

    public function gravaGeolocalizacao($hdChamadoCockpit, $dados)
    {
        return $this->hd->gravaGeolocalizacao($hdChamadoCockpit, $dados);
    }

    /**
     * @param integer $hdChamado
     * @return array
     */
    public function abreOS($hdChamado, $patrimonio = null, $os_kof = null, $cliente_latitude = null, $cliente_longitude = null, $routine_schedule_log_id = null)
    {
        return $this->os->abreOS($hdChamado, $patrimonio, $os_kof, $cliente_latitude, $cliente_longitude, $routine_schedule_log_id);
    }

    /**
     * @param integer $os
     * @param integer $tecnico
     * @return boolean
     */
    public function updateOsTecnico($os, $tecnico)
    {
        return $this->os->updateOsTecnico($os, $tecnico);
    }

    /**
     * @param integer $os
     * @param integer $posto
     * @return boolean
     */
    public function setPostoOS($os, $posto)
    {
        return $this->os->setPostoOS($os, $posto);
    }

    /**
     * @param integer $os
     * @return array
     */
    public function exportaOs($os)
    {
        return $this->os->exportaOs($os);
    }

    public function desagendarOs($id_externo) {
        return $this->os->desagendarOs($id_externo);
    }

    public function reagendaOs($os, $data)
    {
        return $this->os->reagendaOs($os, $data);
    }

    public function transferirOs($os, $tecnico, $data, $antigo_tecnico)
    {
        return $this->os->transferirOs($os, $tecnico, $data, $antigo_tecnico);
    }

    /**
     * @return Os
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @param float   $lat     Latitude
     * @param float   $lng     Longitude
     * @param integer $kmLimit Limite de distância para trazer os mais próximos
     * @return array
     */
    public function getTecnicoMaisProximo($lat, $lng, $cep, $kmLimit, $hdChamado = null, $prioridade = 0, $tipo_atendimento, $garantia, $cliente_id, $centro_distribuidor, $data_abertura)
    {
        return $this->tecnico->getTecnicoMaisProximo(
            $lat,
            $lng,
            $cep,
            $kmLimit,
            $hdChamado,
            $prioridade,
            $tipo_atendimento,
            $garantia,
            $cliente_id,
            $centro_distribuidor,
            $data_abertura
        );
    }

    /**
     * @param integer $tecnico
     * @return integer
     */
    public function getPostoTecnico($tecnico)
    {
        return $this->tecnico->getPostoTecnico($tecnico);
    }

    /**
     * @param float $client_lat
     * @param float $client_lng
     * @param array $technicals_locations
     * @return object
     */
    public function getTecnicoDistance($client_lat, $client_lng, $technicals_locations)
    {
        $tcMaps = new TcMaps($this->fabrica);
        return $this->tecnico->distance($client_lat, $client_lng, $technicals_locations, $tcMaps);
    }

    /**
     * @param integer $tecnico
     * @param string $data
     * @param integer $os
     * @return array
     */
    public function verificaAgenda($tecnico, $data = null, $os = null)
    {
        return $this->agenda->verificaAgenda($tecnico, $data, $os);
    }

    /**
     * @param integer $os
     * @return array
     */
    public function verificaAgendaByOS($os)
    {
        return $this->agenda->verificaAgendaByOS($os);
    }

    /**
     * @param integer $tecnico
     * @param integer $os
     * @param string $data
     * @return boolean
     */
    public function insereAgenda($tecnico, $os, $data, $ordem,$con)
    {
        return $this->agenda->insereAgenda($tecnico, $os, $data, $ordem, $con);
    }

    /**
     * @param integer $id
     * @param integer $tecnico
     * @param string $data
     * @param integer $os
     * @return boolean
     */
    public function atualizarAgenda($id, $tecnico, $data, $ordem, $os)
    {
        return $this->agenda->atualizarAgenda($id, $tecnico, $data, $ordem, $os);
    }

    /**
     * @param integer $id
     * @return boolean
     */
    public function deletarAgenda($id)
    {
        return $this->agenda->deletarAgenda($id);
    }

    /**
     * @param integer $ticket
     * @param string $param
     * @return array
     */
    public function getDadosTicket($ticket, $param = null)
    {
        return $this->hd->getDadosTicket($ticket, $param);
    }

    public function getClienteAdmin($codigo) {
        return $this->hd->getClienteAdmin($codigo);
    }

    public function getProdutoById($id, $join_linha = false) {
        if (empty($id)) {
            return false;
        }

        $pdo = $this->model->getPDO();

        $colunas = array(
            "tbl_produto.produto",
            "tbl_produto.referencia",
            "tbl_produto.descricao",
            "tbl_produto.linha",
            "tbl_produto.familia"
        );

        if ($join_linha == true) {
            $colunas[]        = "tbl_linha.nome AS linha_nome";
            $inner_join_linha = "INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$this->fabrica}";
        }

        $sql = "
            SELECT ".implode(", ", $colunas)."
            FROM tbl_produto
            {$inner_join_linha}
            WHERE tbl_produto.fabrica_i = {$this->fabrica}
            AND tbl_produto.produto = {$id}
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar o produto");
        }

        if ($qry->rowCount() == 0) {
            return false;
        }

        return $qry->fetch();
    }

    public function getProdutoBySerie($serie, $join_linha = false){

        $pdo = $this->model->getPDO();
        $sql = " SELECT referencia_produto from tbl_numero_serie where serie = '$serie' and fabrica = ". $this->fabrica;
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar o produto");
        }
        if ($qry->rowCount() == 0) {
            return false;
        }
        return $qry->fetch();
    }


    public function getProdutoByRef($referencia, $join_linha = false) {
        if (empty($referencia)) {
            return false;
        }

        $pdo = $this->model->getPDO();

        $colunas = array(
            "tbl_produto.produto",
            "tbl_produto.referencia",
            "tbl_produto.descricao",
            "tbl_produto.linha",
            "tbl_produto.familia"
        );

        if ($join_linha == true) {
            $colunas[]        = "tbl_linha.nome AS linha_nome";
            $inner_join_linha = "INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$this->fabrica}";
        }

        $sql = "
            SELECT ".implode(", ", $colunas)."
            FROM tbl_produto
            {$inner_join_linha}
            WHERE tbl_produto.fabrica_i = {$this->fabrica}
            AND LOWER(fn_retira_especiais(tbl_produto.referencia)) = LOWER(fn_retira_especiais('{$referencia}'))
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar o produto");
        }

        if ($qry->rowCount() == 0) {
            return false;
        }

        return $qry->fetch();
    }

    public function getClienteKOF($cliente_id) {
        if (empty($cliente_id)) {
            return false;
        }

        $pdo = $this->model->getPDO();

        $sql = "
            SELECT tbl_cliente.*
            FROM tbl_fabrica_cliente
            INNER JOIN tbl_cliente ON tbl_cliente.cliente = tbl_fabrica_cliente.cliente
            WHERE tbl_fabrica_cliente.fabrica = {$this->fabrica}
            AND tbl_cliente.codigo_cliente = '{$cliente_id}'
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar o cliente");
        }

        if ($qry->rowCount() == 0) {
            return false;
        }

        $res = $qry->fetch();

        if (!strlen($res["grupo_cliente"])) {
            return false;
        }

        return $res;
    }

    /**
     * @param string $descricao tipo de atendimento da KOF
     * @return array
     */
    public function getTipoAtendimentoKOF($descricao, $garantia) {
        $de_para = array(
            "ZKR5" => "preventiva",
            "ZKR6" => "sanitizacao",
            "ZKR3" => "corretiva",
            "ZKR9" => "piso",
            "ZKR1" => "movimentacao",
            "ZKR2" => "movimentacao",
            "AMBV-GAR" => "garantia corretiva"
        );

        $tipo_atendimento = $de_para[$descricao];

        if($descricao == "AMBV-GAR"){
            $whereGarantia = "AND fora_garantia IS NOT TRUE";
        }else{
            $whereGarantia = "AND fora_garantia IS TRUE";    
        }        

        if (empty($tipo_atendimento)) {
            return array();
        }

        $pdo = $this->model->getPDO();

        $sql = "
            SELECT tipo_atendimento,
                    fora_garantia
            FROM tbl_tipo_atendimento
            WHERE fabrica = {$this->fabrica}
            {$whereGarantia}
            AND LOWER(fn_retira_especiais(descricao)) = '{$tipo_atendimento}'
        ";

        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar tipo de atendimento");
        }

        if ($qry->rowCount() > 0) {
            return $qry->fetch();
        } else {
            return array();
        }
    }

    /**
     * @param string $descricao tipo de atendimento
     * @return array
     */
    public function getTipoAtendimento($descricao, $garantia) {
        $de_para = array(
            "ZKR5" => "preventiva",
            "ZKR6" => "sanitizacao",
            "ZKR3" => "corretiva",
            "ZKR9" => "piso",
            "ZKR1" => "movimentacao",
            "ZKR2" => "movimentacao"
        );

        $tipo_atendimento = $de_para[$descricao];

        if ($descricao != "ZKR6" && !empty($garantia)) {
            $tipo_atendimento = "garantia {$tipo_atendimento}";
            $whereGarantia = "AND fora_garantia IS NOT TRUE";
	} else {
            $whereGarantia = "AND fora_garantia IS TRUE";
        }

        if (empty($tipo_atendimento)) {
            return array();
        }

        $pdo = $this->model->getPDO();

        $sql = "
            SELECT tipo_atendimento,
                    fora_garantia
            FROM tbl_tipo_atendimento
            WHERE fabrica = {$this->fabrica}
            {$whereGarantia}
            AND LOWER(fn_retira_especiais(descricao)) = '{$tipo_atendimento}'
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar tipo de atendimento");
        }

        if ($qry->rowCount() > 0) {
            return $qry->fetch();
        } else {
            return array();
        }
    }

    public function validaHD($dados) {
        return $this->hd->validaHD($dados);
    }

    public function osAgendadaNaoFinalizada($reagendar_os) {
        return $this->agenda->osAgendadaNaoFinalizada($reagendar_os);
    }

    public function getPrioridadeByDescricao($descricao) {
        $descricao = strtolower($descricao);

        $pdo = $this->model->getPDO();

        $sql = "
            SELECT
                hd_chamado_cockpit_prioridade,
                descricao,
                cor,
                peso,
                ativo,
                codigo
            FROM tbl_hd_chamado_cockpit_prioridade
            WHERE fabrica = {$this->fabrica}
            AND LOWER(descricao) = '{$descricao}'
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar prioridade");
        }

        if ($qry->rowCount() == 0) {
            return false;
        } else {
            return $qry->fetch();
        }
    }

    public function getOsIdExterno($os, $con) {
        return $this->os->getOsIdExterno($os, $con);
    }

    public function getServicosOs($os_id_externo) {
        return $this->os->getServicosOs($os_id_externo);
    }

    public function getServico($servico)  {
        return $this->os->getServico($servico);
    }

    public function vincularServicoOs($os_id_externo, $servico_id_externo) {
        return $this->os->vincularServicoOs($os_id_externo, $servico_id_externo);
    }

    public function getPecaIdExterno($peca_referencia) {
        return $this->os->getPecaIdExterno($peca_referencia);
    }

    public function vincularPecaServico($os_id_externo, $peca_id_externo, $servico_id_externo) {
        return $this->os->vincularPecaServico($os_id_externo, $peca_id_externo, $servico_id_externo);
    }

    public function statusVinculoPecaServico($os_id_externo, $peca_id_externo, $servico_id_externo, $status) {
        return $this->os->statusVinculoPecaServico($os_id_externo, $peca_id_externo, $servico_id_externo, $status);
    }

    public function getTecnicos($tecnico) {
        return $this->tecnico->getTecnicos($tecnico);
    }

    public function getTecnicoIdExterno($tecnico_codigo_externo) {
        return $this->tecnico->getIdExterno($tecnico_codigo_externo);
    }

    public function getTecnicoHistoricoDeslocamento($id_externo, $data_inicial, $data_final) {
        return $this->tecnico->getTecnicoHistoricoDeslocamento($id_externo, $data_inicial, $data_final);
    }

    public function insertTecnicoMonitoramento($fabrica, $tecnico, $latitude, $longitude, $tipo_envio, $data) {
        return $this->tecnico->insertTecnicoMonitoramento($fabrica, $tecnico, $latitude, $longitude, $tipo_envio, $data);
    }

    public function getUnidadeNegocio($centroDistribuidor)
    {
        $pdo = $this->model->getPDO();
        $sql = "SELECT DISTINCT ds.unidade_negocio FROM tbl_distribuidor_sla ds WHERE ds.centro = '{$centroDistribuidor}' AND ds.fabrica = {$this->fabrica};";

        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar unidade de negócio");
        }

        if ($qry->rowCount() == 0) {
            return false;
        } else {
            return $qry->fetch(\PDO::FETCH_ASSOC);
        }
    }

    public function getAtendimentos($tecnico, $data) {
        return $this->tecnico->getAtendimentos($tecnico, $data);
    }

    public function reordenaOSMobile($tecnico, $data, $manual = false) {
        return $this->tecnico->reordenaOSMobile($tecnico, $data, $manual);
    }

    public function atualizaOrdenacaoOSMobile($tecnico, $data) {
        return $this->tecnico->atualizaOrdenacaoOSMobile($tecnico, $data);
    }

    public function validaDefeitoReclamadoFamiliaGarantia($defeito_reclamado, $familia, $garantia) {
        $pdo = $this->model->getPDO();

        $sql = "
            SELECT d.diagnostico
            FROM tbl_diagnostico AS d
            INNER JOIN tbl_defeito_reclamado AS dr ON dr.defeito_reclamado = d.defeito_reclamado AND dr.fabrica = {$this->fabrica}
            INNER JOIN tbl_familia AS f ON f.familia = d.familia AND f.fabrica = {$this->fabrica}
            WHERE d.fabrica = {$this->fabrica}
            AND d.ativo IS TRUE
            AND d.defeito_reclamado = {$defeito_reclamado}
            AND d.familia = {$familia}
            AND d.garantia = ".(($garantia == true) ? "true" : "false")."
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            return false;
        }

        if ($qry->rowCount() == 0) {
            return false;
        }

        return true;
    }

    public function getDefeitoReclamado($where, $familia, $garantia) {
        $pdo = $this->model->getPDO();
        
        if (is_null($familia) && is_null($garantia)) {
            $sql = "
                SELECT defeito_reclamado, descricao, codigo
                FROM tbl_defeito_reclamado
                WHERE ".key($where)." = ".$where[key($where)]."
                AND fabrica = {$this->fabrica}
            ";
        } else {
            $sql = "
                SELECT dr.defeito_reclamado, dr.descricao, dr.codigo
                FROM tbl_diagnostico AS d
                INNER JOIN tbl_defeito_reclamado AS dr ON dr.defeito_reclamado = d.defeito_reclamado AND dr.fabrica = {$this->fabrica}
                INNER JOIN tbl_familia AS f ON f.familia = d.familia AND f.fabrica = {$this->fabrica}
                WHERE ".key($where)." = ".$where[key($where)]."
                AND d.fabrica = {$this->fabrica}
                AND d.ativo IS TRUE
                AND d.familia = {$familia}
                AND d.garantia = ".(($garantia == true) ? "true" : "false")."
            ";
        }

        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar o defeito reclamado");
        }

        if ($qry->rowCount() == 0) {
            throw new \Exception("Defeito reclamado não encontrado");
        }

        return $qry->fetch();
    }

    public function getDefeitoConstatado($where) {
        $pdo = $this->model->getPDO();

        $sql = "
            SELECT defeito_constatado, descricao, codigo
            FROM tbl_defeito_constatado
            WHERE fabrica = {$this->fabrica}
            AND ".key($where)." = ".$where[key($where)]."
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar o defeito constatado");
        }

        if ($qry->rowCount() == 0) {
            throw new \Exception("Defeito constatado não encontrado");
        }

        return $qry->fetch();
    }

    public function getSolucao($where) {
        $pdo = $this->model->getPDO();

        $sql = "
            SELECT solucao, descricao, codigo
            FROM tbl_solucao
            WHERE fabrica = {$this->fabrica}
            AND ".key($where)." = ".$where[key($where)]."
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar o solução");
        }

        if ($qry->rowCount() == 0) {
            throw new \Exception("Solução não encontrado");
        }

        return $qry->fetch();
    }

    public function finalizaOsMobile($id_externo) {
        return $this->os->finalizaOsMobile($id_externo);
    }

    public function cancelaOsMobile($id_externo) {
        return $this->os->cancelaOsMobile($id_externo);
    }
    
    public function gravaTecnicoMobile($nome, $nome_fantasia, $cnpj_cpf, $ie_rg, $email, $codigo) {
        return $this->tecnico->gravaTecnicoMobile($nome, $nome_fantasia, $cnpj_cpf, $ie_rg, $email, $codigo);
    }

    public function atualizaTecnicoMobile($codigo_externo, $nome, $nome_fantasia, $cnpj_cpf, $ie_rg, $email, $codigo) {
        return $this->tecnico->atualizaTecnicoMobile($codigo_externo, $nome, $nome_fantasia, $cnpj_cpf, $ie_rg, $email, $codigo);
    }

    public function updateTicket($id_externo, $informacoes) {
        return $this->os->updateTicket($id_externo, $informacoes);
    }

    public function updateSituacao($id_externo, $informacoes) {
	return $this->os->updateSituacao($id_externo, $informacoes);
    }

    public function getMobileStatus($status) {
        return $this->os->getMobileStatus($status);
    }

    public function cancelaServico($os, $servico_realizado) {
        return $this->os->cancelaServico($os, $servico_realizado);
    }

    public function verificaOsAgendada($ticket) {
        return $this->os->verificaOsAgendada($ticket);
    }

    public function getPecasOsMobile($os_mobile) {
        return $this->os->getPecasOsMobile($os_mobile);
    }

    public function inativaProdutoOsMobile($os, $produto_os_mobile) {
        return $this->os->inativaProdutoOsMobile($os, $produto_os_mobile);
    }

    public function gravaProdutoOsMobile($os, $produto_lancado, $serie, $patrimonio) {
        return $this->os->gravaProdutoOsMobile($os, $produto_lancado, $serie, $patrimonio);
    }

    public function confirmaServicoAlteradoPersys($os_mobile, $peca_referencia) {
        return $this->os->confirmaServicoAlteradoPersys($os_mobile, $peca_referencia);
    }

    public function atualizaJson($hd_chamado_cockpit, $dados){
        $pdo = $this->model->getPDO();

        if(strlen(trim($dados)) == 0){
             throw new \Exception("Erro ao atualizar Json");
        }

        $sql = " UPDATE tbl_hd_chamado_cockpit set dados = '$dados' where hd_chamado_cockpit = $hd_chamado_cockpit ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao atualizar Json");
        }        

        return $qry->fetch();
    }

    public function formataDadosCockpit($dados){

        if(!isset($dados['os'])){
            return false; 
        }

        foreach($dados['openReasons'] as $open_reasons){  
            $dados['defReclamadoAdicional'][] =  str_pad($open_reasons, 4, "0", STR_PAD_LEFT); 
        }

        $referencia_produto = $this->getProdutoBySerie($dados['device']['rgCode']);        

        $endereco = explode(",", $dados['client']['serviceAddress']['address']);
       
        $dadosf["centroDistribuidor"] =  "AMBV";
        $dadosf["branco"] =  "";
        $dadosf["idCliente"] =  $dados['client']['clientCode'];
        $dadosf["nomeCliente"] =  utf8_decode($dados['client']['businessRelationshipCode'])." - ".utf8_decode($dados['client']['businessRelationshipName']);
        $dadosf["enderecoCliente"] =  utf8_decode($endereco[0]);
        $dadosf["bairroCliente"] =  utf8_decode($endereco[1]);
        $dadosf["cepCliente"] =  $dados['client']['serviceAddress']['zip'];
        $dadosf["cidadeCliente"] =  utf8_decode($dados['client']['serviceAddress']['city']);
        $dadosf["estadoCliente"] =  $dados['client']['serviceAddress']['state'];
        $dadosf["paisCliente"] =  "BR";
        $dadosf["telefoneCliente"] =  $dados['client']['phoneNumber'];
        //$dadosf["telefoneCliente2"] =  "";
        //$dadosf["numeroAtivo"] =  "";
        $dadosf["modeloKof"] =  (strlen(trim($referencia_produto['referencia_produto']))>0) ? $referencia_produto['referencia_produto'] : "0";
        //$dadosf["patrimonioKof"] =  "null";
        $dadosf["osKof"] =  $dados['os'];
        $dadosf["patrimonioKof"] =  $dados['device']['rgCode']; //serie
        //$dadosf["grupoCatalogoKof"] =  "";
        // $dadosf["categoriaDefeito"] =  "CS-PM100",
        //foreach($dados['open_reasons'] as $open_reasons){  
            $dadosf["codDefeito"] =  $dados['defReclamadoAdicional'][0];    
            unset($dados['defReclamadoAdicional'][0]);
        //}
        //$dadosf["defeito"] =  "";
        $dadosf["dataAbertura"] =  mostra_data(substr($dados['creationDate'],0,10));
        $dadosf["nomeContato"] =  utf8_decode($dados['client']['contactName']);
        $dadosf["comentario"] =  utf8_decode($dados['serviceAdditionalInstructions']);
        $dadosf["nomeFantasia"] = utf8_decode($dados['client']['name']); 
        $dadosf["descricaoTipo"] =  "Garantia";

        // $dadosf["classeAtividade"] =  "",
        // $dadosf["categoriaEquipamento"] =  "CS_POSTMIX",
        
        $dadosf["numeroSerie"] =  $dados['device']['rgCode'];

        //Somente Ambev
        //$dadosf['Id_Icebev'] = $dados['idIcebev'];
        $dadosf['device'] = $dados['device']['deviceType'];
        
        $dadosf['emailCliente'] = $dados['client']['email'];
        $dadosf['pontos_referencia'] = utf8_decode($dados['client']['serviceAddress']['referencePoint']);
        $dadosf['document_type'] = $dados['client']['documentType'];
        $dadosf['document_number'] = $dados['client']['documentNumber'];
        $dadosf['complemento'] = utf8_decode($dados['client']['serviceAddress']['complement']);
        $dadosf['numero_endereco'] = $dados['client']['serviceAddress']['addressNumber'];
        $dadosf['defReclamadoAdicional'] = implode(";",$dados['defReclamadoAdicional']); 
        $dadosf['empresa'] = 'AMBEV';
        $dadosf['obs'] = $dados['serviceAdditionalInstructions']; 

        //verificar tipo atendimento e garantia
        $dadosf["tipoOrdem"] =  "AMBV-GAR";  
        $dadosf["garantia"] =  "sim";

        foreach($dados['serviceTimePeriod'] as $periodo){
            if(count(array_filter($periodo))==0){
                continue; 
            }
            $periodo_atendimento[] = $periodo['dayOfWeek']."|".$periodo['hourIni']."|".$periodo['hourEnd'];
        }
        $dadosf['periodo_atendimento'] = $periodo_atendimento; 
     
        return $dadosf; 
    }
}
