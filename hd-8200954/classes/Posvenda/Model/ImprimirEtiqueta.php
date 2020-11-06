<?php
namespace Posvenda\Model;

class ImprimirEtiqueta extends AbstractModel{
	private $_fabrica;
    public $_conn;
    private $_url;

    public function __construct($fabrica){

		parent::__construct('tbl_fabrica_correio');
		$this->_fabrica = $fabrica;
        $this->_conn = $this->getPDO();
        $this->_url = "https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";
        // $this->_url = "https://apphom.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";

	}

	public function buscaEtiquetasPedidos($dados){
		$pedido_etiqueta = trim($dados["pedido"]);
		$codigo_posto    = $dados["codigo_posto"];
		$descricao_posto = $dados["descricao_posto"];

		$sql = "SELECT tbl_etiqueta_servico.etiqueta, tbl_pedido.pedido FROM tbl_etiqueta_servico 
				JOIN tbl_pedido ON tbl_pedido.etiqueta_servico = tbl_etiqueta_servico.etiqueta_servico
					AND tbl_pedido.fabrica = {$this->_fabrica}
			WHERE tbl_etiqueta_servico.etiqueta = '{$pedido_etiqueta}'
				AND tbl_etiqueta_servico.fabrica = {$this->_fabrica}
			ORDER BY tbl_pedido.pedido";
		$query = $this->_conn->query($sql);
		$res   = $query->fetchAll(\PDO::FETCH_ASSOC);
		$res   = array_filter($res);

		if(count($res) == 0){
			$sql = "SELECT tbl_etiqueta_servico.etiqueta, tbl_pedido.pedido 
				FROM tbl_pedido 
					JOIN tbl_etiqueta_servico ON tbl_etiqueta_servico.etiqueta_servico = tbl_pedido.etiqueta_servico
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto
						AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
						AND tbl_posto_fabrica.codigo_posto = '{$codigo_posto}'
					JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
						AND tbl_posto.nome = '{$descricao_posto}'
				WHERE tbl_pedido.etiqueta_servico IN (
						SELECT etiqueta_servico FROM tbl_pedido WHERE tbl_pedido.pedido = {$pedido_etiqueta}
							AND tbl_pedido.fabrica = {$this->_fabrica})
				ORDER BY tbl_pedido.pedido";
			$query = $this->_conn->query($sql);
			$res   = $query->fetchAll(\PDO::FETCH_ASSOC);
			$res   = array_filter($res);
		}

		if(count($res) > 0){
			$lista_pedidos = "";

			foreach ($res as $key => $pedido) {
				if($lista_pedidos != ""){
					$lista_pedidos .= ",";
				}
				$lista_pedidos .= $pedido["pedido"];
			}

			$response = array(
				"resultado" => true,
				"etiqueta"  => $res[0]["etiqueta"],
				"pedido"    => $lista_pedidos
			);
		} else {
			$response = array(
				"resultado" => false,
				"mensagem"  => utf8_encode("Etiqueta não encontrada!")
			);
		}
		return $response;
	}
}
?>