<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';

$post = true;

$fabrica = $_GET['fabrica'];
$dadosOS = $_GET['os'];
$dadosPedidos = $_GET['pedidos'];

$gerado = [];



$caminho = dirname(__DIR__);
$caminhoPerl = ($_serverEnvironment == 'development')
  ? "/home/breno/public_html/Perl"
  : '/var/www/cgi-bin';

$sql_fabrica = "SELECT nome from tbl_fabrica WHERE fabrica = {$fabrica}";
$res_fabrica = pg_query($con, $sql_fabrica);

if(pg_num_rows($res_fabrica)>0){
	$nome_fabrica = strtolower(pg_fetch_result($res_fabrica, 0, nome));	
	$nome_fabrica = str_replace(" ","",$nome_fabrica);	
	$nome_fabrica = str_replace("-","",$nome_fabrica);	
}  

$validacao = false;


  if(!empty($dadosOS)){

      	$sql_troca = "SELECT os from tbl_os_troca where fabric = $fabrica and os = ".$dadosOS;
        	$res_troca = pg_query($con, $sql_troca);
        	if(pg_num_rows($res_troca)>0){	          
          	exec("php $caminho/rotinas/$nome_fabrica/gera-pedido-troca.php $dadosOS" );
        	}else{
          	exec("php $caminho/rotinas/$nome_fabrica/gera-pedido.php $dadosOS" );
  	    }    
  	    
  	    $sqlPedido = " SELECT tbl_os_item.pedido from tbl_os_item 
                  inner join tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
                  where tbl_os_produto.os = $dadosOS ";
      	
      	$resPedido = pg_query($con, $sqlPedido);

        	if(pg_num_rows($resPedido)>0){
				$pedido_gerado = pg_fetch_result($resPedido, 0, pedido);

				$embarque_gerado_os = exec("perl $caminhoPerl/distrib/embarque_novo.pl $dadosOS" );

				$sql_num_embarque = " SELECT tbl_pedido_item.pedido, tbl_embarque_item.embarque 
					FROM tbl_pedido_item 
					JOIN tbl_embarque_item on tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item 
					JOin tbl_os_item using(pedido_item)
					join tbl_os_produto using(os_produto)
					WHERE tbl_pedido_item.pedido = $pedido_gerado  and os = $dadosOS";

				$res_num_embarque = pg_query($con, $sql_num_embarque);
			}
  	    if(pg_num_rows($res_num_embarque)>0){
            $embarque_gerado_os = "Embarque ";
  	        $embarque_gerado_os .= pg_fetch_result($res_num_embarque, 0, embarque);
            $embarque_gerado_os .= " gerado";
  	    }else{
  	    	$embarque_gerado_os = "";
  	    }
  }

  if(!empty($dadosPedidos)){
    	$sql_estoque = "SELECT tbl_posto_estoque.peca as estoque from tbl_pedido_item 
                    inner join tbl_posto_estoque on tbl_posto_estoque.peca = tbl_pedido_item.peca
                    where tbl_pedido_item.pedido = $dadosPedidos 
                    AND tbl_posto_estoque.qtde >= tbl_pedido_item.qtde";    
    	
    	$res_estoque = pg_query($con, $sql_estoque);
    
    	if(pg_num_rows($res_estoque)>0){
      	$validacao = true;        
    	}else{
        if (in_array($fabrica, [11,172])) {
          $fab_peca = ($fabrica == 11) ? 172 : 11;
          
          $sql_peca = " SELECT peca 
                        FROM tbl_peca
                        WHERE fabrica = $fab_peca
                        AND referencia = (
                                            SELECT referencia 
                                            FROM tbl_peca
                                            JOIN tbl_pedido_item ON tbl_peca.peca = tbl_pedido_item.peca
                                            WHERE pedido = $dadosPedidos
                                         )";
          $res_peca = pg_query($con, $sql_peca);
          if (pg_num_rows($res_peca) > 0) {
            $peca_fab = pg_fetch_result($res_peca, 0, 'peca');

            $sql_estoque = "SELECT tbl_posto_estoque.peca as estoque 
                            FROM tbl_pedido_item 
                            JOIN tbl_posto_estoque on tbl_posto_estoque.peca = tbl_pedido_item.peca
                            WHERE tbl_posto_estoque.peca = $peca_fab
                            AND tbl_posto_estoque.qtde >= tbl_pedido_item.qtde";    
            $res_estoque = pg_query($con, $sql_estoque);
            if (pg_num_rows($res_estoque) > 0) {
              $validacao = true;
            } else {
              $embarque_gerado_pedido = "Peça sem estoque";
            }
          }
	} else {

		$sql_peca_alt = "SELECT tbl_posto_estoque.qtde, peca_para AS peca
			FROM tbl_peca_alternativa
			JOIN tbl_pedido_item ON tbl_pedido_item.peca = tbl_peca_alternativa.peca_de
			JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_peca_alternativa.peca_para
			WHERE tbl_peca_alternativa.fabrica = $login_fabrica
			AND  tbl_pedido_item.pedido = $dadosPedidos 
			AND tbl_posto_estoque.qtde >= tbl_pedido_item.qtde";
		$res_peca_alt = pg_query($con, $sql_peca_alt);
		
		if(pg_num_rows($res_peca_alt)>0){
			$validacao = true;
		}else{
			$embarque_gerado_pedido = "Peça sem estoque";
		}
        }
    	}

    	if($validacao == true){

			exec("php $caminho/rotinas/distrib/embarque_novo_faturado.php $dadosPedidos" );
			sleep(2);
			exec("perl $caminhoPerl/distrib/embarque_faturado_atendimento.pl $dadosPedidos" );
			sleep(2);
			exec("perl $caminhoPerl/distrib/embarque_novo_garantia_manual.pl $dadosPedidos" );
			$sql_num_embarque = " SELECT tbl_pedido_item.pedido, tbl_embarque_item.embarque 
				FROM tbl_pedido_item 
				JOIN tbl_embarque_item on tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item 
				WHERE tbl_pedido_item.pedido = $dadosPedidos and embarcado::date=current_date ";
			$res_num_embarque = pg_query($con, $sql_num_embarque);

			if(pg_num_rows($res_num_embarque)>0){
				$embarque_gerado_pedido = "Embarque ";
				$embarque_gerado_pedido .= pg_fetch_result($res_num_embarque, 0, embarque);
				$embarque_gerado_pedido .= " gerado";
			}else{
				$embarque_gerado_pedido = "";
			}

		  }  
  }

if(!empty($embarque_gerado_os) || !empty($embarque_gerado_pedido)) {
?>
  <div>
    <div>
      <font style="color:#FFFFFF; background-color:#FF0000; font-size:15px;"><b>&nbsp;<?=$embarque_gerado_os?>&nbsp;</b></font><br> 
      <font style="color:#FFFFFF; background-color:#FF0000; font-size:15px;"><b><?=$embarque_gerado_pedido?></b></font><br>
    </div>
  </div>  
<?
} else {
?>
  <div>
    <div>
      <font style="color:#FFFFFF; background-color:#FF0000; font-size:15px;"><b>&nbsp;Não foi possível gerar embarque&nbsp;</b></font><br>     
    </div>
  </div>  
<?
}
?>
