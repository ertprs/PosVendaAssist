<?php
/**
 *
 * rotinas/blackedecker/gera-peca-item-revenda-etiqueta.php
 *
 * @author  Thiago Tobias
 * @version 2016.04.28
 *
*/

error_reporting(E_ALL ^ E_NOTICE);

try{
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $fabrica = 1;
    $fabrica_nome  = 'blackedecker';

    $vet['fabrica'] = $fabrica_nome;
    $vet['tipo']    = 'gera-peca-item-revenda-etiqueta';
    $vet['dest']    = array('thiago.tobias@telecontrol.com.br');
    $vet['log']     = 1;

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    $log = new Log2();

    $data_arquivo = date('Y-m-d-H-i');

    $sql = "SELECT tbl_peca.referencia,tbl_produto.referencia as descricao
                FROM tbl_peca 
                    JOIN tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca
                    JOIN tbl_produto ON tbl_lista_basica.produto = tbl_produto.produto
                WHERE tbl_peca.parametros_adicionais ilike '%\"item_revenda\":\"t\"%' 
                    AND tbl_lista_basica.parametros_adicionais ilike '%\"item_revenda\":\"t\"%' 
                    AND tbl_peca.fabrica = $fabrica
                    GROUP BY tbl_peca.referencia,tbl_produto.referencia 
                    ORDER BY tbl_peca.referencia,tbl_produto.referencia;";
    $res      = pg_query($con, $sql);
    $msg_erro = pg_last_error($con);

    if (!empty($msg_erro)) {
        throw new Exception($msg_erro);
    }

    $result_lb = pg_fetch_all($res);
    
    $dir = "/home/blackedecker/telecontrol-black/"; //produção
    //$dir = "/tmp/$fabrica_nome/"; //devel teste

    $arquivo = $dir.'item-revenda-etiqueta.etq';
    $file = fopen($arquivo,'w');

    $ref_peca = '*';
    foreach ($result_lb as $key_ex => $value_ex) {
        if ($ref_peca != $value_ex[referencia]) {
            if ($ref_peca != '*') {
                fwrite($file, ";\r\n");
            }
            $ref_peca = $value_ex[referencia];
            
            fwrite($file, "R".$value_ex[referencia].";");

            $primeiro_produto = true;
        }
        if ($primeiro_produto == true) {
            fwrite($file, $value_ex[descricao]);
            $primeiro_produto = false;
        }else{
            fwrite($file, "|".$value_ex[descricao]);
        }        
    }
    fwrite($file, ';');
    @fclose($file);


    $phpCron->termino();
}catch (Exception $e) {
   $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
   Log::envia_email($vet, APP, $msg);
}
