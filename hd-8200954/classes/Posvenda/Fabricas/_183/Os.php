<?php

namespace Posvenda\Fabricas\_183;

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

    public function validaInformacoesOs($os, $sua_os, $data_fechamento, $tipo_revenda = 'f', $os_produto = true){
        $pdo = $this->_model->getPDO();
        
        if (!empty($os)) {
            $sql = "SELECT os_produto FROM tbl_os_produto WHERE os = $os AND serie IS NULL";
            $query = $pdo->query($sql);
            
            if(!$query) {
                throw new \Exception("Falha ao buscar série do produto");
            }
            
            $os_produto = $query->fetch(\PDO::FETCH_ASSOC);
            
            if ($os_produto){
                throw new \Exception("Os: $sua_os sem número de série");
            }

            $sql = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $this->_fabrica";
            $query = $pdo->query($sql);
            $valores_adicionais = $query->fetch(\PDO::FETCH_ASSOC);

            $xvalores_adicionais = $valores_adicionais["valores_adicionais"];
            $valor_adicional = json_decode($xvalores_adicionais, true);

            $sql_tdocs = "
                SELECT json_field('typeId',obs) AS typeId 
                FROM tbl_tdocs 
                WHERE tbl_tdocs.fabrica = $this->_fabrica
                AND tbl_tdocs.situacao = 'ativo'
                AND tbl_tdocs.referencia_id = $os";
            $res_tdocs = $pdo->query($sql_tdocs);
            
            if(!$res_tdocs) {
                throw new \Exception("Falha ao buscar anexos");
            }
            $xtypeId = $res_tdocs->fetchAll(\PDO::FETCH_ASSOC);

            if (count($xtypeId)){

                foreach ($xtypeId as $key => $typeId) {
                    if (in_array('assinatura', $typeId)) {
                        $assinatura = $typeId['typeid'];
                    }
                    
                    if(in_array('valoradicional', $typeId)){
                        $valoradicional = $typeId['typeid'];
                    }
                }

                if (empty($assinatura)) {
                    throw new \Exception(traduz("Obrigatório anexar: O.S. Assinada"));
                }
                
                if (count($valor_adicional) > 0 AND empty($valoradicional)) {
                    throw new \Exception(traduz("Obrigatório anexar: Comprovante de Valores Adicionais"));
                }      
                
            }else{
                throw new \Exception(traduz("Obrigatório o seguinte anexo: OS Assinada"));
            }
        }
        return false;
    }

    public function finalizaZendesk($os){
        $pdo = $this->_model->getPDO();
        $sql = "
            SELECT 
                campos_adicionais->>'id_zendesk' AS id_zendesk,
                tbl_os.hd_chamado
            FROM tbl_os
            JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado.fabrica = {$this->_fabrica}
            WHERE tbl_os.os = {$os}";
        $query = $pdo->query($sql);

        if(!$query) {
            throw new \Exception("Falha ao fechar ordem de serviço #1");
        }
        
        $result = $query->fetchAll(\PDO::FETCH_ASSOC);

        $id_zendesk = $result[0]["id_zendesk"];
        $hd_chamado = $result[0]["hd_chamado"];

        if (strlen(trim($id_zendesk)) > 0){
            $sql = "INSERT INTO tbl_hd_chamado_item(
                            hd_chamado,
                            data,
                            comentario,
                            interno,
                            status_item
                        ) values (
                            $hd_chamado,
                            current_timestamp,
                            E'Ordem de serviço finalizado no Telecontrol, atendimento Zendesk está sendo Resolvido',
                            't'  ,
                            'Resolvido'
                        )";
            $query = $pdo->query($sql);
            
            if(!$query) {
                throw new \Exception("Falha ao fechar ordem de serviço #2");
            }

            $sql = "UPDATE tbl_hd_chamado SET status = 'Resolvido' WHERE hd_chamado = {$hd_chamado} AND fabrica = {$this->_fabrica}";
            $query = $pdo->query($sql);
            
            if(!$query) {
                throw new \Exception("Falha ao fechar ordem de serviço #3");
            }
        }
    }

    // public function finaliza($con, $troca_produto_api = false, $login_admin = null, $origem = null)
    // {
    //     parent::finaliza($con, $troca_produto_api, $login_admin, $origem);
    // }

    // public function finalizaOsRevenda($con, $data_fechamento)
    // {
    //     $pdo = $this->_model->getPDO();

    //     $sqlOsProduto = "
    //         SELECT
    //             tbl_os.os,
    //             tbl_os.sua_os,
    //             tbl_os.data_fechamento
    //         FROM tbl_os_campo_extra
    //         JOIN tbl_os USING(os,fabrica)
    //         WHERE tbl_os.fabrica = {$this->_fabrica}
    //         AND tbl_os.excluida IS NOT TRUE
    //         AND tbl_os.data_fechamento IS NULL
    //         AND os_revenda = {$this->_os};
    //     ";

    //     $queryOsProduto = $pdo->query($sqlOsProduto);
    //     $resOsProduto = $queryOsProduto->fetchAll(\PDO::FETCH_ASSOC);

    //     $erroOsProduto = array();
    //     foreach($resOsProduto as $osProduto) {

    //         if (empty($osProduto["data_fechamento"])){
    //             $erroOsProduto[] = traduz("OS {$this->_os} não pode ser fechada: OS {$osProduto['sua_os']} ainda esta em aberto");
    //         }

    //         $intervencao = $this->_model->verificaOsIntervencao($osProduto['os'], $this->_fabrica);
    //         if ($intervencao != false) {
    //             $erroOsProduto[] = traduz("OS {$osProduto['sua_os']} em intervenção da fábrica");
    //         }
    //         $validacao = $this->validaInformacoesOs($osProduto['os'], $osProduto['sua_os'], $data_fechamento, 'f', false);
    //         if ($validacao != false) {
    //             $erroOsProduto[] = $validacao;
    //         }
    //         $os_revisao = $this->verificaRevisaoTipo($osProduto['os']);
    //         $os_troca = $this->verificaOsTroca($osProduto['os']);
    //         if ($this->_model->verificaDefeitoConstatado($con, $osProduto['os']) === false && $os_revisao == false && $os_troca == false) {
    //             $erroOsProduto[] = "A OS ".$osProduto['sua_os']." está sem Defeito Constatado";
    //         }
    //         $text_error = implode("<br/>", $erroOsProduto);
    //     }

    //     if (count($erroOsProduto) > 0) {
    //         throw new \Exception("$text_error");
    //     }

    //     $updateOsRevenda = "UPDATE tbl_os_revenda SET data_fechamento = '{$data_fechamento}', finalizada = '{$data_fechamento}' WHERE os_revenda = {$this->_os} AND fabrica = {$this->_fabrica};";
    //     $resUpdate = $pdo->query($updateOsRevenda);
    //     if (!$resUpdate) {
    //         throw new \Exception("Ocorreu um erro atualizando as informações da OS {$this->_os}");
    //     }
    // }
}
