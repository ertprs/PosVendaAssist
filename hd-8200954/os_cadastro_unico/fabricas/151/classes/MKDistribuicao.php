<?php
class DadosSend{

	private $_fabrica;

	public function __construct($fabrica){
	
		$this->_fabrica = $fabrica;
	}

	public function urlServidor ($pedido = null, $servidor = null, $tipo_pedido = null){
		global $con, $_serverEnvironment; 

		if(!empty($pedido) AND !empty($tipo_pedido)){
			$sql = "SELECT 	tbl_peca.referencia, 
					tbl_produto.parametros_adicionais::jsonb->>'centro_distribuicao' AS centro_distribuicao, 
					tbl_produto.parametros_adicionais::jsonb->>'consultar_estoque' AS estoque,
					tbl_peca.produto_acabado 
				FROM tbl_pedido
				INNER JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido 
				INNER JOIN tbl_os_item ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
				INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
				INNER JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
				INNER JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os_produto.os
				INNER JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca 
				WHERE tbl_pedido.pedido = {$pedido}
				AND tbl_pedido.fabrica = $this->_fabrica 
				AND tbl_pedido.tipo_pedido = {$tipo_pedido}";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){

				for($i=0; $i<count($res); $i++){
			
					$referencia = pg_fetch_result($res, $i, "referencia");
					$parametros_adicionais = pg_fetch_result($res, $i, "centro_distribuicao");
					$estoqueProduto = pg_fetch_result($res, $i, "estoque");
					$produto_acabado = pg_fetch_result($res, $i, "produto_acabado");

					if($produto_acabado != "t"){
						if($_serverEnvironment == "development"){
							$url = "http://sisweb-melhoria.mondialline.com.br/SIS_SND/rest/";
						} else {
							$url = "http://sisweb.mondialline.com.br/SIS_SND/rest/";
						}
						continue;
					}
					//if($parametros_adicionais == 'mk_nordeste' OR $parametros_adicionais == ""){
					if(strlen(trim($referencia)) == 7){
						if($_serverEnvironment == "development"){
							$url = "http://sisweb-melhoria.mondialline.com.br/SIS_SND/rest/";
						} else {
							$url = "http://sisweb.mondialline.com.br/SIS_SND/rest/";
						}									
					}

					//if($parametros_adicionais == 'mk_sul'){
					if(strlen(trim($referencia)) == 8){
						if($_serverEnvironment == "development"){
							$url = "http://sisweb-hml.mksul.com/sis_snd/rest/";
						} else {
							$url = "http://sisweb.mksul.com/sis_snd/rest/";
						}									
					}
				}
			}else{
				if($_serverEnvironment == "development"){
					$url = "http://sisweb-melhoria.mondialline.com.br/SIS_SND/rest/";
				} else {
					$url = "http://sisweb.mondialline.com.br/SIS_SND/rest/";
				}	
			}
		}else if(strlen($servidor) > 0){
				if($servidor == 'mk_nordeste'){
						if($_serverEnvironment == "development"){
							$url = "http://sisweb-melhoria.mondialline.com.br/SIS_SND/rest/";
						} else {
							$url = "http://sisweb.mondialline.com.br/SIS_SND/rest/";
						}									
					}

					if($servidor == 'mk_sul'){
						if($_serverEnvironment == "development"){
							$url = "http://sisweb-hml.mksul.com/sis_snd/rest/";
						} else {
							$url = "http://sisweb.mksul.com/sis_snd/rest/";
						}									
					}

		} else { // Padrao Enviar para MK Nordeste 		
			if($_serverEnvironment == "development"){
				$url = "http://sisweb-melhoria.mondialline.com.br/SIS_SND/rest/";
			} else {
				$url = "http://sisweb.mondialline.com.br/SIS_SND/rest/";
			}									
		}
		
				
		return array("url" => $url, "estoque" => $estoqueProduto);
	}

	public function getKey($servidor = null){

		global $con;

		if(strlen($servidor) == 0){
			$servidor = "mk_nordeste";
		}

		$sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = {$this->_fabrica}";
		$res = pg_query($con, $sql);

		$dados = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"), true);
		return $dados["dados_api_send"][$servidor];

	}
}
?>
