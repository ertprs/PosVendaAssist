<?php 

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','teste');  // production Alterar para produção ou algo assim

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    date_default_timezone_set('America/Sao_Paulo');
    $data_atual = Date('d-m-Y-H:i:s');

    /* Dados iniciais */
    $fabrica      = 1;
    $fabrica_nome = "Black & Decker";
    $arquivo      = "atualiza_cr.txt";
    $arquivo_erro = "/tmp/blackedecker/atualiza_cr_erro_$data_atual.txt";
    $erro         = "";  
    $env          = "producao"; // test | producao

    if (ENV == 'teste' ) {
        $origem = "entrada/";
        $data['dest'] = 'ronald.santos@telecontrol.com.br';

    } else {
        $origem = "/home/blackedecker/black-telecontrol/";
        $data['dest'] = 'helpdesk@telecontrol.com.br';
    }

    if(file_exists($origem.$arquivo)){

        $dados = file_get_contents($origem.$arquivo);
        $dados = explode("\n", $dados);

        foreach ($dados as $linha) {
            $dados_linha = explode(";", $linha); 

            $res = pg_query($con,"BEGIN");

            $sql = "SELECT valores_adicionais, produto FROM tbl_produto WHERE referencia_fabrica = '$dados_linha[0]' AND fabrica_i = $fabrica ";
            $res = pg_query($con, $sql);
            if(strlen(pg_last_error($con))>0){
                $msg_erro = pg_last_error($con). "- $sql \n\n";
            }

            for($i=0; $i<pg_num_rows($res); $i++){
                $valores_adicionais = json_decode(pg_fetch_result($res, $i, valores_adicionais), true);
                $produto                        = pg_fetch_result($res, $i, produto);
                

                $medioCR = str_replace(".", "", $dados_linha[1]);
		$medioCR = str_replace(",", ".", $medioCR);
		$medioCR = str_replace(" ", "", $medioCR);

                $valores_adicionais['medioCR']  = $medioCR;
                $valores_adicionais = json_encode($valores_adicionais);

                $sql_produto = "UPDATE tbl_produto SET valores_adicionais = '$valores_adicionais' WHERE produto = $produto ";
                $res_produto = pg_query($con, $sql_produto);
                if(strlen(pg_last_error($con))>0){
                    $msg_erro .= pg_last_error($con). "- $sql \n\n";
                }
            }

            if(!empty($msg_erro)){
                $res = pg_query($con,"ROLLBACK");
                $erro .= $msg_erro;
            } else {
                $res = pg_query($con,"COMMIT");
            }
        }
    }

    if(strlen($erro)> 0){
        $fp = fopen("$arquivo_erro", "w+");
        fwrite($fp, "$erro");
        fclose($fp);
        Log::envia_email($data,Date('d/m/Y H:i:s')." - BlackeDecker - Erro na Importação de Médio CR", $erro);
    }

?>
