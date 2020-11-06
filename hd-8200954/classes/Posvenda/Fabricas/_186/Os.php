<?php
namespace Posvenda\Fabricas\_186;

use Posvenda\Os as OsPosvenda;

class Os extends OsPosvenda{

	protected $_fabrica;
	protected $_os;

    public function __construct($fabrica, $os=null){
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
                --INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                INNER JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
                INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
                JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$this->_fabrica}
		LEFT  JOIN tbl_status_os ON tbl_os.status_os_ultimo = tbl_status_os.status_os
                WHERE tbl_os.fabrica = {$this->_fabrica}
                AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
                --AND tbl_servico_realizado.gera_pedido IS TRUE
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_peca.produto_acabado IS NOT TRUE
                AND tbl_os_item.pedido IS NULL
                AND fn_retira_especiais(LOWER(tbl_tipo_atendimento.descricao)) = 'orcamento'                        
                AND tbl_tipo_posto.posto_interno IS TRUE
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
                                --INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                                INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os_produto.os
                                WHERE tbl_os_produto.os = {$os}
                                AND tbl_peca.produto_acabado IS NOT TRUE
                                --AND tbl_os_item.pedido IS NULL
                                --AND tbl_servico_realizado.gera_pedido IS TRUE
                                ";
                $query = $pdo->query($sql);
                $res = $query->fetchAll(\PDO::FETCH_ASSOC);

                return $res;

        }


    public function getCondicaoBoleto(){

	$pdo = $this->_model->getPDO();
	$sql = "SELECT condicao FROM tbl_condicao WHERE fabrica = {$this->_fabrica} AND descricao ~* 'boleto' and visivel limit 1 ";
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

    public function getTabelaVenda(){

        $pdo = $this->_model->getPDO();
        $sql = "SELECT tabela FROM tbl_tabela WHERE fabrica = {$this->_fabrica} AND tabela_garantia IS NOT TRUE AND lower(descricao) = 'venda' AND ativa IS TRUE";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res[0]['tabela'];
    }

    public function finaliza($con){
	    
	    parent::finaliza($con);
    }

    public function calculaOs(){
	parent::calculaOs();
    }
}
