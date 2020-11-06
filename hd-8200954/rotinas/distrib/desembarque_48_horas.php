<?php 


include dirname(__FILE__) . '/../dbconfig_pg.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';


	$fabrica_distrib = 63;

	/*
	* Cron Class
	*/
	$phpCron = new PHPCron($fabrica_distrib, __FILE__);
	$phpCron->inicio();

	$data_atual = date('Y-m-d H:i:s');

	$sql = "SELECT fabrica FROM tbl_fabrica WHERE parametros_adicionais ilike '%telecontrol_distrib%'";
	$resX = pg_query($con, $sql);
	while ($fabrica = pg_fetch_array($resX)) {
	    $a_fabricas[] = $fabrica['fabrica'];
	}
	$fabricas = implode(',', $a_fabricas);

	$sqlStatusTemp = "SELECT distinct tbl_embarque.data, 
					tbl_embarque_item.embarque, 
					tbl_pedido.pedido,   
					(select status from tbl_pedido_status WHERE tbl_pedido_status.pedido = tbl_pedido.pedido ORDER BY tbl_pedido_status.data DESC limit 1 ) as status, 
					(select observacao from tbl_pedido_status WHERE tbl_pedido_status.pedido = tbl_pedido.pedido ORDER BY tbl_pedido_status.data DESC limit 1 ) as observacao 
				 into temp embarque_nao_faturado
                 from tbl_embarque_item 
                 join tbl_embarque on tbl_embarque.embarque = tbl_embarque_item.embarque 
                 join tbl_pedido_item on tbl_pedido_item.pedido_item  = tbl_embarque_item.pedido_item 
                 join tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido 
                 where tbl_embarque.fabrica in ($fabricas) 
                 and faturar is null and liberado is null ";
    $resStatusTemp = pg_query($con, $sqlStatusTemp);

    $sqlStatus = "SELECT embarque_nao_faturado.data, embarque_nao_faturado.embarque, tbl_embarque_item.embarque_item, embarque_nao_faturado.pedido FROM embarque_nao_faturado join tbl_embarque_item on embarque_nao_faturado.embarque = tbl_embarque_item.embarque where embarque_nao_faturado.status = 11 and embarque_nao_faturado.observacao = 'Venda Direta' ";
    $resStatus = pg_query($con, $sqlStatus);	
    if(pg_num_rows($resStatus)>0){
	    for($i=0; $i<pg_num_rows($resStatus); $i++){
	    	$data 			= substr(pg_fetch_result($resStatus, $i, 'data'),0,19);
	    	$embarque 		= pg_fetch_result($resStatus, $i, 'embarque');
	    	$pedido 		= pg_fetch_result($resStatus, $i, 'pedido');
	    	$embarque_item 	= pg_fetch_result($resStatus, $i, "embarque_item");

			$datetime1 = new DateTime($data);
			$datetime1->add(new DateInterval('PT48H'));
			$datetime2 = new DateTime($data_atual);

			if($datetime1 < $datetime2){
				$res = @pg_query($con,"BEGIN TRANSACTION");
			
				$sqlDesembarque = "SELECT fn_delete_embarque_item($embarque_item)";
				$resDesembarque = pg_query($con, $sqlDesembarque);
				$msg_erro = pg_last_error($con); 

				if(strlen($msg_erro)>0){
					$res = pg_query ($con,"ROLLBACK TRANSACTION");	
				}else{
					$res = @pg_query ($con,"COMMIT TRANSACTION");	
				}			
			}
		}
		$sqlInsertStatus = "INSERT INTO  tbl_pedido_status (pedido, data, status, observacao) VALUES ($pedido, now(), 32, 'Cancelado rotina desembarque_48_horas')";
		$resInsertStatus = pg_query($con, $sqlInsertStatus) ;
	}

	/*
	* Cron Término
	*/
	$phpCron->termino();

?>