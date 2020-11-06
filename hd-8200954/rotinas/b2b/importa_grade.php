<?php 
    #include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/../funcoes.php';
     $logClass = new Log2();
    $logClass->adicionaEmail("felipe.marttos@telecontrol.com.br");
    $logClass->adicionaLog(array("titulo" => "Log de Erro - Importação de Grade B2B")); 

    $tamanhoGrade = ["P","M","G","GG","EXG"];



    $sqlB2B = "SELECT loja_b2b
                 FROM tbl_loja_b2b 
                WHERE fabrica= 42";
    $resB2B  = pg_query($con, $sqlB2B);

    if (pg_num_rows($resB2B) == 0) {
        $msg_erro .= "Loja não encontrada\n";
    } else {
        $row = pg_fetch_assoc($resB2B);
        $loja = $row["loja_b2b"];
    }


    $sql  = "SELECT loja_b2b_peca 
                FROM tbl_loja_b2b_peca 
               WHERE loja_b2b = {$loja}";
    $res  = pg_query($con, $sql);

    if (pg_num_rows($res) == 0) {
        $msg_erro .= "tbl_loja_b2b_peca não cadastrada\n";
    }

    foreach (pg_fetch_all($res) as $key => $linha) {

        foreach ($tamanhoGrade as $tm) {
            $sql = "INSERT INTO tbl_loja_b2b_peca_grade (
                                            loja_b2b_peca,
                                            tamanho,
                                            ativo
                                        ) VALUES (
                                            '".$linha['loja_b2b_peca']."',
                                            '".$tm."',
                                            'f'
                                        )";
            $res = pg_query($con, $sql);
            if (pg_last_error()) {
                $msg_erro .= pg_last_error()." - peca: ". $linha['loja_b2b_peca'];
            }
        
        }

    }

    if (strlen($msg_erro) > 0) {
        $corpo = "<table width='100%' border='1' cellpadding='0' cellspacing='0' width='100%' style='border: solid 1px #d90000;'>
                        <tr>
                            <th style='background:#d90000;color:#ffffff;padding:5px;'>Descrição</th>
                        </tr>
                        <tr>
                        <td>".$msg_erro."</td>
                        </tr>
                  </table>
                ";
        $logClass->adicionaLog($corpo);

        if ($logClass->enviaEmails() == "200") {
            echo "Log de Erro enviado com Sucesso!";
        }
    }