<?php
/**
 *
 * rotinas/colormaq/exporta-pedido-garantia.php
 *
 * @author  Thiago Tobias
 * @version 2016.02.02
 *
*/

error_reporting(E_ALL ^ E_NOTICE);

try{
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$fabrica = 50;
	$fabrica_nome  = 'colormaq';

	$vet['fabrica'] = $fabrica_nome;
	$vet['tipo']    = 'exporta-pedido-garantia';
	$vet['dest']    = array('otavio.arruda@telecontrol.com.br','joao.junior@telecontrol.com.br');
	$vet['log']     = 1;

	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	$log = new Log2();

	$data_arquivo = date('Y-m-d-H-i');
    
  	$sql = "SELECT	TO_CHAR(tbl_pedido.data,'DD/MM/YYYY')               AS data_pedido      ,
					tbl_posto.cnpj                                      AS cnpj_posto       ,
                    tbl_posto.posto,
					CASE WHEN tbl_peca.devolucao_obrigatoria IS TRUE
					    THEN 'G'::char(1)
					    ELSE 'D'::char(1)
					END                                                 AS tipo_pedido      ,
					tbl_pedido.pedido                                                       ,
					tbl_condicao.codigo_condicao                                            ,
					tbl_peca.referencia                                 AS peca_referencia  ,
					sum(tbl_pedido_item.qtde)                           AS peca_quantidade  ,
					tbl_pedido_item.preco                                                   ,
					tbl_pedido.pedido_cliente                                               ,
					tbl_pedido.obs                                                          ,
					tbl_pedido_item.peca                                                    ,
					(
					    SELECT DISTINCT linha
							FROM tbl_lista_basica
								JOIN tbl_produto using(produto)
							WHERE tbl_lista_basica.peca = tbl_pedido_item.peca
							AND tbl_produto.ativo
					        ORDER BY linha
					        LIMIT 1
					) AS linha
					INTO TEMP    tmp_pedido_colormaq
					FROM tbl_pedido
						JOIN    tbl_condicao     ON tbl_pedido.condicao  = tbl_condicao.condicao
					    JOIN    tbl_pedido_item  ON tbl_pedido.pedido    = tbl_pedido_item.pedido
					    JOIN    tbl_posto        ON tbl_pedido.posto     = tbl_posto.posto
					    JOIN    tbl_peca         ON tbl_pedido_item.peca = tbl_peca.peca
					    WHERE   tbl_pedido.fabrica                      = $fabrica
					    AND tbl_pedido.pedido = 26717645
					AND     tbl_pedido.posto                    <> 6359
						AND     tbl_pedido.status_pedido            = 1
					    AND     tbl_pedido.exportado                IS NULL
					    AND     tbl_pedido.tipo_pedido              = 129
					    AND     tbl_pedido.finalizado               IS NOT NULL
                        AND     tbl_pedido.troca                    IS NOT TRUE
					GROUP BY    tbl_peca.devolucao_obrigatoria          ,
					        	TO_CHAR(tbl_pedido.data,'DD/MM/YYYY')   ,
					            tbl_posto.cnpj                          ,
                                tbl_posto.posto,
					            tbl_pedido.pedido                       ,
					            tbl_condicao.codigo_condicao            ,
					            tbl_peca.referencia                     ,
					            tbl_pedido_item.preco                   ,
					            tbl_pedido.pedido_cliente               ,
					            tbl_pedido.obs                          ,
					            tbl_pedido_item.peca
					  ORDER BY  tbl_pedido.pedido                       ,
					            tbl_peca.referencia;";
	$res      = pg_query($con, $sql);
	$msg_erro = pg_last_error($con);
	if (!empty($msg_erro)) {
		throw new Exception($msg_erro);
	}

    $conds = array(
        'ALL' => "WHERE peca_referencia <> '661.1.205' AND linha <> 545",
        'DF' => "WHERE peca_referencia = '661.1.205' AND linha <> 545",
        'AUT' => "WHERE linha = 545"
    );

    foreach ($conds as $idx => $cond) {
        $sql = "SELECT * FROM tmp_pedido_colormaq $cond";
        $res = pg_query($con,$sql);
        $numrows  = pg_num_rows($res);

        if ($numrows) {
            $dir = "/tmp/$fabrica_nome";

            $fpg = null;
            $fpig = null;
            $fpd = null;
            $fpid = null;
            $fpf = null;
            $fpif = null;
            $fpga = null;
            $fpiga = null;
            $fpda = null;
            $fpida = null;

            switch ($idx) {
                case 'ALL':
                    $file_pedido_g = $dir.'/pedido_G.txt';
                    $file_pedido_item_g = $dir.'/item_G.txt';
                    $file_pedido_d = $dir.'/pedido_D.txt';
                    $file_pedido_item_d = $dir.'/item_D.txt';

                    $fpg = fopen($file_pedido_g,'w');
                    $fpig = fopen($file_pedido_item_g, 'w');
                    $fpd = fopen($file_pedido_d,'w');
                    $fpid = fopen($file_pedido_item_d, 'w');

                    break;
                case 'DF':
                    $file_pedido_f = $dir.'/pedido_D_F.txt';
                    $file_pedido_item_f = $dir.'/item_D_F.txt';

                    $fpf = fopen($file_pedido_f,'w');
                    $fpif = fopen($file_pedido_item_f, 'w');

                    break;
                case 'AUT':
                    //Arquivos de exportação da linha Automática linha = 545
                    $file_pedido_g_a = $dir.'/pedido_G_A.txt';
                    $file_pedido_item_g_a = $dir.'/item_G_A.txt';
                    $file_pedido_d_a = $dir.'/pedido_D_A.txt';
                    $file_pedido_item_d_a = $dir.'/item_D_A.txt';

                    $fpga = fopen($file_pedido_g_a,'w');
                    $fpiga = fopen($file_pedido_item_g_a, 'w');
                    $fpda = fopen($file_pedido_d_a,'w');
                    $fpida = fopen($file_pedido_item_d_a, 'w');

                    break;
            }
            $arrPedidosExportados = array();
            for ($i = 0; $i < $numrows; $i++) {
                $posto = pg_fetch_result($res, $i, 'posto');
                $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = {$fabrica} AND posto = {$posto}";
                $resParametrosAdicionais =  pg_query($con, $sqlParametrosAdicionais);
                $parametros_adicionais = pg_fetch_result($resParametrosAdicionais, 0, 'parametros_adicionais');
                $posto_isento = null;
                if(!empty($parametros_adicionais)){
                    $parametros_adicionais = json_decode($parametros_adicionais,true);
                }
                if(is_array($parametros_adicionais) && array_key_exists('posto_isento', $parametros_adicionais)){
                    $posto_isento = $parametros_adicionais['posto_isento']; 
                }
                if($posto_isento == 't'){
                    continue;
                } 
                $linha 				= pg_fetch_result($res, $i, 'linha');
                $tipo_pedido 		= pg_fetch_result($res, $i, 'tipo_pedido');
                $pedido 			= pg_fetch_result($res, $i, 'pedido');
                $data_pedido 		= pg_fetch_result($res, $i, 'data_pedido');
                $cnpj_posto 		= pg_fetch_result($res, $i, 'cnpj_posto');
                $codigo_condicao 	= pg_fetch_result($res, $i, 'codigo_condicao');
                $peca_referencia 	= pg_fetch_result($res, $i, 'peca_referencia');
                $peca_quantidade 	= pg_fetch_result($res, $i, 'peca_quantidade');
                $preco 				= pg_fetch_result($res, $i, 'preco');
                $pedido_cliente 	= pg_fetch_result($res, $i, 'pedido_cliente');
                $obs 				= pg_fetch_result($res, $i, 'obs');
                $peca 				= pg_fetch_result($res, $i, 'peca');

                #CABEÇALHO DO PEDIDO
                if($pedido_anterior != $pedido or empty($pedido_anterior)){
                    $pedido_ga = "";
                    $pedido_g = "";
                    $pedido_da = "";
                    $pedido_d = "";
                    $sql_p = "	SELECT tbl_os.sua_os
                        FROM  tbl_os_item
                        JOIN  tbl_os_produto USING(os_produto)
                        JOIN  tbl_os         USING(os)
                        WHERE tbl_os_item.pedido = $pedido;";
                    $res_p = pg_query($con,$sql_p);

                    $msg_erro = pg_last_error($con);

                    if (!empty($msg_erro)) {
                        throw new Exception($msg_erro);
                    }

                    $numrows_p  = pg_num_rows($res_p); 

                    if($tipo_pedido == "G"){
                        if ($linha == 545 ) {
                            fwrite($fpga, $data_pedido."|");
                            fwrite($fpga, $cnpj_posto."|");
                            fwrite($fpga, $tipo_pedido."|");
                            fwrite($fpga, $pedido."|");
                            fwrite($fpga, $codigo_condicao."|");
                            for ($y=0; $y < $numrows_p ; $y++) {
                                $sua_os = pg_fetch_result($res_p, $y, sua_os);
                                #OBS DO PEDIDO
                                if ($sua_os != $sua_os_anterior or empty($sua_os_anterior)){
                                    fwrite($fpga, $sua_os."|");
                                    $sua_os_anterior = $sua_os;
                                }						
                            }
                            fwrite($fpga, $pedido_cliente."|");
                            fwrite($fpga, $obs.";");
                            fwrite($fpga, "\r\n");
                            $pedido_ga = 'sim';
                        }else{
                            if (!empty($fpg)) {
                                fwrite($fpg, $data_pedido."|");
                                fwrite($fpg, $cnpj_posto."|");
                                fwrite($fpg, $tipo_pedido."|");
                                fwrite($fpg, $pedido."|");
                                fwrite($fpg, $codigo_condicao."|");
                                for ($y=0; $y < $numrows_p ; $y++) {
                                    $sua_os = pg_fetch_result($res_p, $y, sua_os);
                                    #OBS DO PEDIDO
                                    if ($sua_os != $sua_os_anterior or empty($sua_os_anterior)){
                                        fwrite($fpg, $sua_os."|");
                                        $sua_os_anterior = $sua_os;
                                    }						
                                }
                                fwrite($fpg, $pedido_cliente."|");
                                fwrite($fpg, $obs.";");
                                fwrite($fpg, "\r\n");
                                $pedido_g = 'sim';
                            }
                        }					
                    }else{
                        if ($linha == 545 ) {
                            fwrite($fpda, $data_pedido."|");
                            fwrite($fpda, $cnpj_posto."|");
                            fwrite($fpda, $tipo_pedido."|");
                            fwrite($fpda, $pedido."|");
                            fwrite($fpda, $codigo_condicao."|");
                            for ($y=0; $y < $numrows_p ; $y++) {
                                $sua_os = pg_fetch_result($res_p, $y, sua_os);
                                #OBS DO PEDIDO
                                if ($sua_os != $sua_os_anterior or empty($sua_os_anterior)){
                                    fwrite($fpda, $sua_os."|");
                                    $sua_os_anterior = $sua_os;
                                }						
                            }
                            fwrite($fpda, $pedido_cliente."|");
                            fwrite($fpda, $obs.";");
                            fwrite($fpda, "\r\n");
                            $pedido_da = 'sim';
                        }else{
                            if (!empty($fpd)) {
                                fwrite($fpd, $data_pedido."|");
                                fwrite($fpd, $cnpj_posto."|");
                                fwrite($fpd, $tipo_pedido."|");
                                fwrite($fpd, $pedido."|");
                                fwrite($fpd, $codigo_condicao."|");
                                for ($y=0; $y < $numrows_p ; $y++) {
                                    $sua_os = pg_fetch_result($res_p, $y, sua_os);
                                    #OBS DO PEDIDO
                                    if ($sua_os != $sua_os_anterior or empty($sua_os_anterior)){
                                        fwrite($fpd, $sua_os."|");
                                        $sua_os_anterior = $sua_os;
                                    }						
                                }
                                fwrite($fpd, $pedido_cliente."|");
                                fwrite($fpd, $obs.";");
                                fwrite($fpd, "\r\n");
                                $pedido_d = 'sim';
                            }
                        }					
                    }
                    $pedido_anterior = $pedido;
                }

                #ITENS DO PEDIDO
                #fixo para indicar pedido_item
                if($peca_referencia == "661.1.205"){
                    fwrite($fpf, $data_pedido."|");
                    fwrite($fpf, $cnpj_posto."|");
                    fwrite($fpf, $tipo_pedido."|");
                    fwrite($fpf, $pedido."|");
                    fwrite($fpf, $codigo_condicao."|");
                    for ($y=0; $y < $numrows_p ; $y++) {
                        $sua_os = pg_fetch_result($res_p, $y, sua_os);
                        #OBS DO PEDIDO
                        if ($sua_os != $sua_os_anterior or empty($sua_os_anterior)){
                            fwrite($fpf, $sua_os."|");
                            $sua_os_anterior = $sua_os;
                        }						
                    }
                    fwrite($fpf, $pedido_cliente."|");
                    fwrite($fpf, $obs.";");
                    fwrite($fpf, "\r\n");
                    fwrite($fpif, $pedido."|");
                    fwrite($fpif, $peca_referencia."|");
                    fwrite($fpif, $peca_quantidade."|");
                    fwrite($fpif, $preco."");
                    fwrite($fpif, "\r\n");
                    $arquivo_D_F = true;
                }else if($tipo_pedido == "G"){
                    if ($linha == 545 ) {
                        fwrite($fpiga, $pedido."|");
                        fwrite($fpiga, $peca_referencia."|");
                        fwrite($fpiga, $peca_quantidade."|");
                        fwrite($fpiga, $preco."");
                        fwrite($fpiga, "\r\n");
                        if(empty($pedido_ga)) {
                            fwrite($fpga, $data_pedido."|");
                            fwrite($fpga, $cnpj_posto."|");
                            fwrite($fpga, $tipo_pedido."|");
                            fwrite($fpga, $pedido."|");
                            fwrite($fpga, $codigo_condicao."|");
                            for ($y=0; $y < $numrows_p ; $y++) {
                                $sua_os = pg_fetch_result($res_p, $y, sua_os);
                                #OBS DO PEDIDO
                                if ($sua_os != $sua_os_anterior or empty($sua_os_anterior)){
                                    fwrite($fpga, $sua_os."|");
                                    $sua_os_anterior = $sua_os;
                                }						
                            }
                            fwrite($fpga, $pedido_cliente."|");
                            fwrite($fpga, $obs.";");
                            fwrite($fpga, "\r\n");

                            $pedido_ga = 'sim';	
                        }
                    }else{
                        if (!empty($fpig)) {
                            fwrite($fpig, $pedido."|");
                            fwrite($fpig, $peca_referencia."|");
                            fwrite($fpig, $peca_quantidade."|");
                            fwrite($fpig, $preco."");
                            fwrite($fpig, "\r\n");
                        }
                        if(empty($pedido_g)) {
                            fwrite($fpg, $data_pedido."|");
                            fwrite($fpg, $cnpj_posto."|");
                            fwrite($fpg, $tipo_pedido."|");
                            fwrite($fpg, $pedido."|");
                            fwrite($fpg, $codigo_condicao."|");
                            for ($y=0; $y < $numrows_p ; $y++) {
                                $sua_os = pg_fetch_result($res_p, $y, sua_os);
                                #OBS DO PEDIDO
                                if ($sua_os != $sua_os_anterior or empty($sua_os_anterior)){
                                    fwrite($fpg, $sua_os."|");
                                    $sua_os_anterior = $sua_os;
                                }						
                            }
                            fwrite($fpg, $pedido_cliente."|");
                            fwrite($fpg, $obs.";");
                            fwrite($fpg, "\r\n");

                            $pedido_g = 'sim';	
                        }

                    }				
                }else{
                    if ($linha == 545 ) {
                        fwrite($fpida, $pedido."|");
                        fwrite($fpida, $peca_referencia."|");
                        fwrite($fpida, $peca_quantidade."|");
                        fwrite($fpida, $preco."");				
                        fwrite($fpida, "\r\n");
                        if(empty($pedido_da)) {
                            fwrite($fpda, $data_pedido."|");
                            fwrite($fpda, $cnpj_posto."|");
                            fwrite($fpda, $tipo_pedido."|");
                            fwrite($fpda, $pedido."|");
                            fwrite($fpda, $codigo_condicao."|");
                            for ($y=0; $y < $numrows_p ; $y++) {
                                $sua_os = pg_fetch_result($res_p, $y, sua_os);
                                #OBS DO PEDIDO
                                if ($sua_os != $sua_os_anterior or empty($sua_os_anterior)){
                                    fwrite($fpda, $sua_os."|");
                                    $sua_os_anterior = $sua_os;
                                }						
                            }
                            fwrite($fpda, $pedido_cliente."|");
                            fwrite($fpda, $obs.";");
                            fwrite($fpda, "\r\n");

                            $pedido_da = 'sim';	
                        }

                    }else{
                        if (!empty($fpid)) {
                            fwrite($fpid, $pedido."|");
                            fwrite($fpid, $peca_referencia."|");
                            fwrite($fpid, $peca_quantidade."|");
                            fwrite($fpid, $preco."");				
                            fwrite($fpid, "\r\n");
                        }
                        if(empty($pedido_d)) {
                            fwrite($fpd, $data_pedido."|");
                            fwrite($fpd, $cnpj_posto."|");
                            fwrite($fpd, $tipo_pedido."|");
                            fwrite($fpd, $pedido."|");
                            fwrite($fpd, $codigo_condicao."|");
                            for ($y=0; $y < $numrows_p ; $y++) {
                                $sua_os = pg_fetch_result($res_p, $y, sua_os);
                                #OBS DO PEDIDO
                                if ($sua_os != $sua_os_anterior or empty($sua_os_anterior)){
                                    fwrite($fpd, $sua_os."|");
                                    $sua_os_anterior = $sua_os;
                                }						
                            }
                            fwrite($fpd, $pedido_cliente."|");
                            fwrite($fpd, $obs.";");
                            fwrite($fpd, "\r\n");

                            $pedido_d = 'sim';	
                        }


                    }		  	
                }
                unset($obs);
                unset($sua_os);
                unset($linha);
                unset($sua_os_anterior);
                $arrPedidosExportados[] = $pedido;
            }
            @fclose($fpg);
            @fclose($fpig);
            @fclose($fpd);
            @fclose($fpid);
            @fclose($fpf);
            @fclose($fpif);

            @fclose($fpga);
            @fclose($fpiga);
            @fclose($fpda);
            @fclose($fpida);

            $strPedidosExportados = implode(',', $arrPedidosExportados);
            $condPedidosExportados = ' AND tbl_pedido.pedido IN ('. $strPedidosExportados . ')';
            if (file_exists($file_pedido_g)) {

                $sql_pg = "UPDATE tbl_pedido
                    SET exportado = current_timestamp,
                    status_pedido = 2
                    WHERE tbl_pedido.pedido IN (SELECT pedido::numeric FROM tmp_pedido_colormaq WHERE tipo_pedido = 'G' AND linha <> 545 $condPedidosExportados)
                    AND   tbl_pedido.exportado IS NULL ";
                $res_pg = pg_query($con, $sql_pg);

                $msg_erro = pg_last_error($con);

                if (!empty($msg_erro)) {
                    throw new Exception($msg_erro);
                }

                $destino  = '/home/colormaq/telecontrol-'.$fabrica_nome.'/pedido_G-'.$data_arquivo.'.txt';
                $destino2 = '/home/colormaq/telecontrol-'.$fabrica_nome.'/item_G-'.$data_arquivo.'.txt';
                $dirbkp   = '/home/colormaq/telecontrol-'.$fabrica_nome.'/bkp';

                #system("cp $file_pedido_g $dirbkp" );
                #system("mv $file_pedido_g $destino");
                #system("cp $file_pedido_item_g $dirbkp");
                #system("mv $file_pedido_item_g $destino2");
            }

            if(file_exists($file_pedido_d)){

                $sql_pd = "UPDATE tbl_pedido
                    SET exportado = current_timestamp,
                    status_pedido = 2
                    WHERE tbl_pedido.pedido
                    IN (SELECT pedido::numeric FROM tmp_pedido_colormaq WHERE tipo_pedido = 'D' AND linha <> 545 $condPedidosExportados)
                    AND   tbl_pedido.exportado IS NULL ";
                 $res_pd = pg_query($con, $sql_pd);

                $msg_erro = pg_last_error($con);

                if (!empty($msg_erro)) {
                    throw new Exception($msg_erro);
                }

                $destino  = '/home/colormaq/telecontrol-'.$fabrica_nome.'/pedido_D-'.$data_arquivo.'.txt';
                $destino2 = '/home/colormaq/telecontrol-'.$fabrica_nome.'/item_D-'.$data_arquivo.'.txt';
                $dirbkp   = '/home/colormaq/telecontrol-'.$fabrica_nome.'/bkp';

                #system("cp $file_pedido_d $dirbkp" );
                #system("mv $file_pedido_d $destino");
                #system("cp $file_pedido_item_d $dirbkp");
                #system("mv $file_pedido_item_d $destino2");
            }

            //Arquivos da Linha Automático
            if (file_exists($file_pedido_g_a)) {

                $sql_pg = "UPDATE tbl_pedido
                    SET exportado = current_timestamp,
                    status_pedido = 2
                    WHERE tbl_pedido.pedido IN (SELECT pedido::numeric FROM tmp_pedido_colormaq WHERE tipo_pedido = 'G' AND linha = 545 $condPedidosExportados)
                    AND   tbl_pedido.exportado IS NULL ";
                $res_pg = pg_query($con, $sql_pg);

                $msg_erro = pg_last_error($con);

                if (!empty($msg_erro)) {
                    throw new Exception($msg_erro);
                }

                $destino  = '/home/colormaq/telecontrol-'.$fabrica_nome.'/pedido_G_A-'.$data_arquivo.'.txt';
                $destino2 = '/home/colormaq/telecontrol-'.$fabrica_nome.'/item_G_A-'.$data_arquivo.'.txt';
                $dirbkp   = '/home/colormaq/telecontrol-'.$fabrica_nome.'/bkp';

                #system("cp $file_pedido_g_a $dirbkp" );
                #system("mv $file_pedido_g_a $destino");
                #system("cp $file_pedido_item_g_a $dirbkp");
                #system("mv $file_pedido_item_g_a $destino2");
            }

            if(file_exists($file_pedido_d_a)){

                $sql_pd = "UPDATE tbl_pedido
                    SET exportado = current_timestamp,
                    status_pedido = 2
                    WHERE tbl_pedido.pedido
                    IN (SELECT pedido::numeric FROM tmp_pedido_colormaq WHERE tipo_pedido = 'D' AND linha = 545 $condPedidosExportados)
                    AND   tbl_pedido.exportado IS NULL ";
                 $res_pd = pg_query($con, $sql_pd);

                $msg_erro = pg_last_error($con);

                if (!empty($msg_erro)) {
                    throw new Exception($msg_erro);
                }

                $destino  = '/home/colormaq/telecontrol-'.$fabrica_nome.'/pedido_D_A-'.$data_arquivo.'.txt';
                $destino2 = '/home/colormaq/telecontrol-'.$fabrica_nome.'/item_D_A-'.$data_arquivo.'.txt';
                $dirbkp   = '/home/colormaq/telecontrol-'.$fabrica_nome.'/bkp';

                #system("cp $file_pedido_d_a $dirbkp" );
                #system("mv $file_pedido_d_a $destino");
                #system("cp $file_pedido_item_d_a $dirbkp");
                #system("mv $file_pedido_item_d_a $destino2");
            }

            if(file_exists($file_pedido_f) and $arquivo_D_F){

                $destino  = '/home/colormaq/telecontrol-'.$fabrica_nome.'/pedido_D_F-'.$data_arquivo.'.txt';
                $destino2 = '/home/colormaq/telecontrol-'.$fabrica_nome.'/item_D_F-'.$data_arquivo.'.txt';
                $dirbkp   = '/home/colormaq/telecontrol-'.$fabrica_nome.'/bkp';

                #system("cp $file_pedido_f $dirbkp" );
                #system("mv $file_pedido_f $destino");
                #system("cp $file_pedido_item_f $dirbkp");
                #system("mv $file_pedido_item_f $destino2");
            }

        }
    }

  	

	$phpCron->termino();
}catch (Exception $e) {
   $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
   Log::envia_email($vet, APP, $msg);
}
?>
