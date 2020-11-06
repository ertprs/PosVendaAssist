<?php
/**
 *
 * rotinas/colormaq/exporta-pedido-garantia.php
 *
 * @author  Guilherme Silva
 * @version 2016.10.21
 *
*/

error_reporting(E_ALL ^ E_NOTICE);

try{
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $fabrica        = 50;
    $fabrica_nome   = 'colormaq';
    $vet['fabrica'] = $fabrica_nome;
    $vet['tipo']    = 'exporta-pedido-garantia';
    $vet['log']     = 1;

    $env = ($_serverEnvironment == 'development') ? 'teste' : 'producao';

    if($env == "teste"){
        $vet['dest'] = array('guilherme.silva@telecontrol.com.br');
    }else{
        $vet['dest'] = array('helpdesk@telecontrol.com.br');
    }

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    $log = new Log2();

    $data_arquivo = date('Y-m-d-H-i');
    $cond_posto = ($env != "teste") ? " AND tbl_pedido.posto != 6359 " : "";

    $sql_tipo_pulmao = "SELECT tipo_pedido FROM tbl_tipo_pedido WHERE garantia_antecipada IS TRUE and fabrica = $fabrica";
    $res_tipo_pulmao = pg_query($con, $sql_tipo_pulmao);

    $tipo_pulmao = pg_fetch_result($res_tipo_pulmao, 0, "tipo_pedido");
    
    $sql = "SELECT 
                TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
                tbl_posto.cnpj AS cnpj_posto,
                tbl_posto.posto,
                tbl_peca.devolucao_obrigatoria,
                tbl_pedido.pedido,
                tbl_condicao.codigo_condicao,
                tbl_peca.referencia AS peca_referencia ,
                SUM(tbl_pedido_item.qtde) AS peca_quantidade ,
                tbl_pedido_item.preco,
                tbl_pedido.pedido_cliente,
                tbl_pedido.tipo_pedido,
                tbl_pedido.obs,
                tbl_pedido_item.peca,
                (
                    SELECT DISTINCT linha
                        FROM tbl_lista_basica
                            JOIN tbl_produto using(produto)
                        WHERE tbl_lista_basica.peca = tbl_pedido_item.peca
                        AND tbl_produto.ativo
                        ORDER BY linha
                        LIMIT 1
                ) AS linha
                INTO TEMP tmp_pedido_colormaq
                FROM tbl_pedido
                JOIN tbl_condicao ON tbl_pedido.condicao = tbl_condicao.condicao
                JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
                JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
                JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
                WHERE 
                    tbl_pedido.fabrica = $fabrica
                    $cond_posto
                    AND tbl_pedido.status_pedido = 1
                    AND tbl_pedido.exportado IS NULL
                    AND tbl_pedido.tipo_pedido IN (129, {$tipo_pulmao}) /* Garantia | Pulmão */
                    AND tbl_pedido.finalizado IS NOT NULL
                    AND tbl_pedido.troca IS NOT TRUE
                GROUP BY 
                    tbl_peca.devolucao_obrigatoria,
                    TO_CHAR(tbl_pedido.data,'DD/MM/YYYY'),
                    tbl_posto.cnpj,
                    tbl_posto.posto,
                    tbl_pedido.pedido,
                    tbl_condicao.codigo_condicao,
                    tbl_peca.referencia,
                    tbl_pedido_item.preco,
                    tbl_pedido.pedido_cliente,
                    tbl_pedido.tipo_pedido,
                    tbl_pedido.obs,
                    tbl_pedido_item.peca 
                  ORDER BY 
                    tbl_pedido.pedido,
                    tbl_peca.referencia;";
    $res = pg_query($con, $sql);

    $msg_erro = pg_last_error($con);
    if (!empty($msg_erro)) {
        throw new Exception($msg_erro);
    }

    $sql     = "SELECT * FROM tmp_pedido_colormaq";
    $res     = pg_query($con,$sql);
    $numrows = pg_num_rows($res);

    if ($numrows) {

        $dir              = ($env == "teste") ? "entrada" : "/tmp/$fabrica_nome";
        $file_pedido      = $dir.'/pedido.txt';
        $file_pedido_item = $dir.'/pedido_item.txt';
        
        $fp               = fopen($file_pedido,'w');
        $fpi              = fopen($file_pedido_item, 'w');

        $arrPedidosExportados = array();

        for ($i = 0; $i < $numrows; $i++) {

            $posto = pg_fetch_result($res, $i, 'posto');

            /* Verifica Posto Isento */
            $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = {$fabrica} AND posto = {$posto}";
            $resParametrosAdicionais = pg_query($con, $sqlParametrosAdicionais);
            $parametros_adicionais   = pg_fetch_result($resParametrosAdicionais, 0, 'parametros_adicionais');
            $posto_isento            = 'f';

            if(!empty($parametros_adicionais)){
                $parametros_adicionais = json_decode($parametros_adicionais,true);
            }

            if(is_array($parametros_adicionais) && array_key_exists('posto_isento', $parametros_adicionais)){
                $posto_isento = $parametros_adicionais['posto_isento']; 
            }
            /* Verifica Posto Isento */

            $linha                 = pg_fetch_result($res, $i, 'linha');
            $devolucao_obrigatoria = pg_fetch_result($res, $i, 'devolucao_obrigatoria');
            $pedido                = pg_fetch_result($res, $i, 'pedido');
            $data_pedido           = pg_fetch_result($res, $i, 'data_pedido');
            $cnpj_posto            = pg_fetch_result($res, $i, 'cnpj_posto');
            $codigo_condicao       = pg_fetch_result($res, $i, 'codigo_condicao');
            $peca_referencia       = pg_fetch_result($res, $i, 'peca_referencia');
            $peca_quantidade       = pg_fetch_result($res, $i, 'peca_quantidade');
            $preco                 = pg_fetch_result($res, $i, 'preco');
            $pedido_cliente        = pg_fetch_result($res, $i, 'pedido_cliente');
            $tipo_pedido           = pg_fetch_result($res, $i, 'tipo_pedido');
            $obs                   = pg_fetch_result($res, $i, 'obs');
            $peca                  = pg_fetch_result($res, $i, 'peca');

            /* echo "Linha: ".$linha."\n";
            echo "Devolução Obrigatoria: ".$devolucao_obrigatoria."\n";
            echo "Pedido: ".$pedido."\n";
            echo "Data Pedido: ".$data_pedido."\n";
            echo "Linha: ".$linha."\n";
            echo "CNPJ Posto: ".$cnpj_posto."\n";
            echo "Cód Condição: ".$codigo_condicao."\n";
            echo "Peça Referência: ".$peca_referencia."\n";
            echo "Peça Quantidade: ".$peca_quantidade."\n";
            echo "Preço: ".$preço."\n";
            echo "Pedido Cliente: ".$pedido_cliente."\n";
            echo "Obs: ".$obs."\n";
            echo "Peça: ".$peca."\n";
            echo "\n \n"; */

            if($tipo_pedido != 129){

                $desc_tipo_pedido = "G_P";

            }else if($posto_isento == 't'){

                /* Posto Isento */

                if($linha != 545 && $peca_referencia != "661.1.205"){

                    if($devolucao_obrigatoria == "t"){
                        $desc_tipo_pedido = "G_SIE";
                    }else{
                        $desc_tipo_pedido = "D_SIE";
                    }

                }else if($linha == 545 && $peca_referencia != "661.1.205"){

                    if($devolucao_obrigatoria == "t"){
                        $desc_tipo_pedido = "G_A_SIE";
                    }else{
                        $desc_tipo_pedido = "D_A_SIE";
                    }

                }else if($peca_referencia == "661.1.205"){
                    $desc_tipo_pedido = "D_F_SIE";
                }

            }else{

                /* Posto Não Isento */

                if($linha != 545 && $peca_referencia != "661.1.205"){

                    if($devolucao_obrigatoria == "t"){
                        $desc_tipo_pedido = "G";
                    }else{
                        $desc_tipo_pedido = "D";
                    }

                }else if($linha == 545 && $peca_referencia != "661.1.205"){

                    if($devolucao_obrigatoria == "t"){
                        $desc_tipo_pedido = "G_A";
                    }else{
                        $desc_tipo_pedido = "D_A";
                    }

                }else if($peca_referencia == "661.1.205"){
                    $desc_tipo_pedido = "D_F";
                }

            }

            #Cabeçalho do Pedido
            if($pedido_anterior != $pedido || empty($pedido_anterior)){

                $sql_p = "SELECT tbl_os.sua_os
                    FROM tbl_os_item
                    JOIN tbl_os_produto USING(os_produto)
                    JOIN tbl_os USING(os)
                    WHERE tbl_os_item.pedido = $pedido;";
                $res_p = pg_query($con, $sql_p);

                $msg_erro = pg_last_error($con);

                if (!empty($msg_erro)) {
                    throw new Exception($msg_erro);
                }

                $numrows_p = pg_num_rows($res_p); 

                fwrite($fp, $data_pedido."|");
                fwrite($fp, $cnpj_posto."|");
                fwrite($fp, $pedido."|");
                fwrite($fp, $codigo_condicao."|");

                for ($y = 0; $y < $numrows_p ; $y++) {

                    $sua_os = pg_fetch_result($res_p, $y, "sua_os");

                    #Obs do pedido
                    if ($sua_os != $sua_os_anterior or empty($sua_os_anterior)){
                        fwrite($fp, $sua_os."|");
                        $sua_os_anterior = $sua_os;
                    }

                }

                fwrite($fp, $pedido_cliente."|");
                fwrite($fp, $obs);
                fwrite($fp, "\r\n");

                $pedido_anterior = $pedido;

            }

            #Itens do pedido
            fwrite($fpi, $pedido."|");
            fwrite($fpi, $peca_referencia."|");
            fwrite($fpi, $peca_quantidade."|");
            fwrite($fpi, $preco."|");
            fwrite($fpi, $desc_tipo_pedido);
            fwrite($fpi, "\r\n");        

            unset($obs);
            unset($sua_os);
            unset($linha);
            unset($sua_os_anterior);

            $arrPedidosExportados[] = $pedido;

        }

        fclose($fp);
        fclose($fpi);

        $strPedidosExportados = implode(',', $arrPedidosExportados);
        $condPedidosExportados = ' AND tbl_pedido.pedido IN ('.$strPedidosExportados.') ';

        if($env == "teste"){
            exit;
        }

        if (file_exists($file_pedido)) {

	    $sql_pg = "UPDATE tbl_pedido SET exportado = current_timestamp, status_pedido = 2 where pedido in(select pedido FROM tmp_pedido_colormaq)";
            $res_pg = pg_query($con, $sql_pg);

            $msg_erro = pg_last_error($con);

            if (!empty($msg_erro)) {
                throw new Exception($msg_erro);
            }

            if($env != "teste"){

                $destino  = '/home/colormaq/telecontrol-'.$fabrica_nome.'/pedido_G-'.$data_arquivo.'.txt';
                $destino2 = '/home/colormaq/telecontrol-'.$fabrica_nome.'/item_G-'.$data_arquivo.'.txt';
                $dirbkp   = '/home/colormaq/telecontrol-'.$fabrica_nome.'/bkp';

                system("cp $file_pedido $dirbkp" );
                system("mv $file_pedido $destino");
                system("cp $file_pedido_item $dirbkp");
                system("mv $file_pedido_item $destino2");

            }

        }

    }

    $phpCron->termino();

} catch (Exception $e) {
   $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
   Log::envia_email($vet, APP, $msg);
}
?>
