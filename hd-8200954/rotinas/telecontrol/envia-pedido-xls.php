<?php
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	class PedidoCSV{

		private $fabrica;
		private $con;
		private $csv;

		public function __construct($con) {

			$this->con = $con;

		}

		public function setFabrica($f){
			$this->fabrica = $f;
		}

		public function insereDadosCSV($d){

			$this->csv .= $d;

		}

		public function gravaCSV(){

			if(!is_dir("/tmp/csv/{$this->fabrica}")){
				mkdir("/tmp/csv/{$this->fabrica}", 0777, true);
			}

			$data = date("d")."-".date("m")."-".date("Y");
			$fp = fopen("/tmp/csv/{$this->fabrica}/pedido-csv-{$this->fabrica}-{$data}.csv", "w");
			fwrite($fp, $this->csv);
			fclose($fp);

			return "true";

		}

		public function getPedidos(){

			/* Seleciona os Postos para gravar o CSV */

			$sql_postos = "SELECT DISTINCT posto FROM tbl_pedido WHERE fabrica = {$this->fabrica} AND exportado::date = current_date";
			$res_postos = pg_query($this->con, $sql_postos);

			if(pg_num_rows($res_postos) > 0){

				for($i = 0; $i < pg_num_rows($res_postos); $i++){

					$postos[] = pg_fetch_result($res_postos, $i, 'posto');

				}

			}

			if(count($postos) > 0){

				for($i = 0; $i < count($postos); $i++) {

					$posto = $postos[$i];

					/* Busca os dados dos Postos */
					
					$sql_dados_posto = "SELECT 
											tbl_posto_fabrica.codigo_posto,
											tbl_posto.nome 
										FROM tbl_posto 
										JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$this->fabrica} 
										WHERE tbl_posto.posto = {$posto}";
					$res_dados_postos = pg_query($this->con, $sql_dados_posto);

					$codigo_posto 	= pg_fetch_result($res_dados_postos, 0, 'codigo_posto');
					$nome_posto 	= pg_fetch_result($res_dados_postos, 0, 'nome');

					/* Busca os Pedidos do Posto */

					$sql_pedidos = "SELECT pedido FROM tbl_pedido WHERE posto = {$posto} AND fabrica = {$this->fabrica}";
					$res_pedidos = pg_query($this->con, $sql_pedidos);

					if(pg_num_rows($res_pedidos) > 0){

						for($j = 0; $j < pg_num_rows($res_pedidos); $j++){ 
																	
							$pedido = pg_fetch_result($res_pedidos, $j, 'pedido');

							$d = "\n \n Pedido {$pedido} - {$codigo_posto} {$nome_posto} \n";

							$this->insereDadosCSV($d);

							$sql_pedido_item = "SELECT 
													tbl_peca.referencia, 
													tbl_peca.descricao, 
													tbl_pedido_item.qtde 
												FROM tbl_pedido_item 
												JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca 
												WHERE tbl_pedido_item.pedido = {$pedido}";
							$res_pedido_item = pg_query($this->con, $sql_pedido_item);

							for($k = 0; $k < pg_num_rows($res_pedido_item); $k++){

								$referencia = pg_fetch_result($res_pedido_item, $k, 'referencia');
								$peca 		= pg_fetch_result($res_pedido_item, $k, 'descricao');
								$qtde 		= pg_fetch_result($res_pedido_item, $k, 'qtde');

								$d = "{$referencia};{$peca};{$qtde} \n";

								$this->insereDadosCSV($d);

							}

						}																		

					}

				}

			}

			return ($this->gravaCSV()) ? "ok" : "fail";

		}


	}


?>
