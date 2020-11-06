<?php
/**
 *
 * rotinas/colormaq/exporta-estoque-pulmao.php
 *
 * @author  Guilherme Monteiro
 * @version 2015.09.14
 *
*/

error_reporting(E_ALL ^ E_NOTICE);

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
   include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
   require dirname(__FILE__) . '/../funcoes.php';

   $login_fabrica = 50;
   $fabrica_nome  = 'colormaq';

   $vet['fabrica'] = $fabrica_nome;
   $vet['tipo']    = 'exporta-estoque-pulmao';
   $vet['dest']    = array('helpdesk@telecontrol.com.br');
   $vet['log']     = 1;

   $phpCron = new PHPCron($login_fabrica, __FILE__);
   $phpCron->inicio();

   $log = new Log2();

	$data_arquivo = date('Y-m-d-H-i');

   $sql = "SELECT	TO_CHAR(tbl_pedido.data,'DD/MM/YYYY')         AS data_pedido    ,
			tbl_posto.cnpj                                AS cnpj_posto     ,
			'G'::char(1)                                  AS tipo_pedido    ,
			tbl_pedido.pedido                                               ,
			tbl_condicao.codigo_condicao                                    ,
			tbl_peca.referencia                           AS peca_referencia,
			tbl_pedido_item.qtde                          AS peca_quantidade,
			tbl_pedido_item.pedido_item                                     ,
			tbl_pedido_item.preco                                           ,
			tbl_pedido.pedido_cliente                                       ,
			tbl_pedido.obs                                                  ,
			tbl_pedido_item.peca,
			(
			SELECT distinct linha
				FROM tbl_lista_basica
				JOIN tbl_produto using(produto)
				WHERE tbl_lista_basica.peca = tbl_pedido_item.peca
					ORDER BY linha limit 1
			) AS linha
		INTO TEMP tmp_pedido_colormaq
		FROM tbl_pedido
		JOIN tbl_condicao     ON tbl_pedido.condicao  = tbl_condicao.condicao
		JOIN tbl_pedido_item  ON tbl_pedido.pedido    = tbl_pedido_item.pedido
		JOIN tbl_posto        ON tbl_pedido.posto     = tbl_posto.posto
		JOIN tbl_peca         ON tbl_pedido_item.peca = tbl_peca.peca
		WHERE     tbl_pedido.fabrica                  = $login_fabrica
		AND       tbl_pedido.data > '2016-12-01 00:00'
		AND       tbl_pedido.posto                    <> 6359
		AND       tbl_pedido.status_pedido            = 1 
		AND       tbl_pedido.exportado                IS NULL
		AND       tbl_pedido.tipo_pedido              = 313
		AND       tbl_pedido.finalizado               IS NOT NULL
		AND       tbl_pedido.troca                    IS NOT TRUE
		ORDER BY  tbl_pedido.pedido, tbl_peca.referencia";
	$res      = pg_query($con, $sql);
   $msg_erro = pg_errormessage($con);

   $sql = "SELECT * FROM tmp_pedido_colormaq";
   $res = pg_query($con,$sql);
   $numrows  = pg_num_rows($res);

   $data = date('Y-m-d');

   if (!empty($msg_erro)) {
      throw new Exception($msg_erro);
   }

   if ($numrows) {
   	$dir = "/tmp/$fabrica_nome";

      $file_pedido = $dir.'/pedido_G_P-'.$data_arquivo.'.txt';
      $file_pedido_item = $dir.'/item_G_P-'.$data_arquivo.'.txt';

      $fi   = fopen($file_pedido_item,'w');
      $fp   = fopen($file_pedido, 'w');

      for ($i = 0; $i < $numrows; $i++) {

      	$data_pedido 		= pg_fetch_result($res, $i, 'data_pedido');
      	$cnpj_posto 		= pg_fetch_result($res, $i, 'cnpj_posto');
      	$tipo_pedido 		= pg_fetch_result($res, $i, 'tipo_pedido');
      	$pedido 		= pg_fetch_result($res, $i, 'pedido');
      	$condicao 		= pg_fetch_result($res, $i, 'codigo_condicao');
      	$peca_referencia 	= pg_fetch_result($res, $i, 'peca_referencia');
      	$peca_quantidade 	= pg_fetch_result($res, $i, 'peca_quantidade');
      	$pedido_item 		= pg_fetch_result($res, $i, 'pedido_item');
      	$preco 			= pg_fetch_result($res, $i, 'preco');
      	$peca 			= pg_fetch_result($res, $i, 'peca');
	$linha 			= pg_fetch_result($res, $i, 'linha');
	$obs 			= pg_fetch_result($res, $i, 'obs');
	$pedido_cliente 	= pg_fetch_result($res, $i, 'pedido_cliente');

      	if ($condicao == "001") {
				$condicao  = "007";
			}

			if ($condicao == "002") {
				$condicao  = "015";
			}

			if ($condicao == "004") {
				$condicao  = "008";
			}

			if($pedido_anterior <> $pedido or empty($pedido_anterior)){
				fwrite($fp, $data_pedido."|");
				fwrite($fp, $cnpj_posto."|");
				fwrite($fp, $pedido."|");
				fwrite($fp, $condicao);

				fwrite($fp, "\r\n");

				$pedido_anterior = $pedido;
			}

			fwrite($fi, $pedido."|");
			fwrite($fi, $peca_referencia."|");
			fwrite($fi, $peca_quantidade."|");
			fwrite($fi, $preco."|");
			fwrite($fi, "G_P");
			fwrite($fi, "\r\n");
		}

		  fclose($fp);
	      fclose($fi);

		if(file_exists($file_pedido) and (filesize($file_pedido) > 0)){
			$sql = "UPDATE tbl_pedido
							SET exportado = current_timestamp,
								status_pedido = 2
						WHERE tbl_pedido.pedido IN (SELECT pedido::numeric FROM tmp_pedido_colormaq)
						AND   tbl_pedido.exportado IS NULL ";
			$result = pg_query($con, $sql);

			$destino  = '/home/colormaq/telecontrol-'  . $fabrica_nome . '/pedido_G_P-'.$data_arquivo.'.txt';
			$destino2 = '/home/colormaq/telecontrol-' . $fabrica_nome . '/item_G_P-'.$data_arquivo.'.txt';
			$dirbkp   = '/home/colormaq/telecontrol-' . $fabrica_nome . '/bkp';

			system("cp $file_pedido $dirbkp" );
			system("mv $file_pedido $destino");
			system("cp $file_pedido_item $dirbkp");
			system("mv $file_pedido_item $destino2");
        }

   }

   $phpCron->termino();

}catch (Exception $e) {
   $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
   Log::envia_email($vet, APP, $msg);
}
?>
