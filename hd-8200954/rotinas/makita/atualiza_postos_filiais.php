<?php

try{
    require_once dirname(__FILE__).'/../../dbconfig.php';
    require_once dirname(__FILE__).'/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__).'/../funcoes.php';
    require_once dirname(__FILE__).'/../../class/email/mailer/class.phpmailer.php';

    date_default_timezone_set('America/Sao_Paulo');
    $configuracao = array(
        'login_fabrica'   => 42,
        'arquivo'         => 'importa_filial.csv',
        'caminho_arquivo' => '/www/cgi-bin/makita/entrada',
        'email_contato'   => array('william.brandino@telecontrol.com.br', 'helpdesk@telecontrol.com.br'),
        'ENV'             => ''
    );
    extract($configuracao);
echo $arquivo;
    $phpCron = new PHPCron($login_fabrica, __FILE__);
    $phpCron->inicio();

    if (file_exists("{$caminho_arquivo}/{$arquivo}") and (filesize("{$caminho_arquivo}/{$arquivo}") > 0)) {
        $conteudo = file_get_contents("{$caminho_arquivo}/{$arquivo}");
        $conteudo = explode("\n", $conteudo);

        foreach ($conteudo as $linha) {
            $Array_linha = explode (";",$linha);

            list($filial,$codigo_posto) = $Array_linha;
echo "Filial: $filial - ";
            if (!empty($filial)) {
                $sql = "
                    SELECT  posto
                    FROM    tbl_posto_fabrica
                    WHERE   fabrica = $login_fabrica
                    AND     codigo_posto = '$codigo_posto'
                ";
                $res = pg_query($con,$sql);

                print("Posto $codigo_posto\n");

                $posto = pg_fetch_result($res,0,posto);
                pg_query($con,"BEGIN TRANSACTION");

                $sqlFilialGar = "
                    UPDATE  tbl_posto_filial
                    SET     garantia = TRUE
                    WHERE   posto = $posto
                    AND     fabrica = $login_fabrica
                    AND     filial_posto = $filial
                ";
                $resFilialGar = pg_query($con,$sqlFilialGar);

                $sqlFilialVenda = "
                    UPDATE  tbl_posto_filial
                    SET     faturado = TRUE
                    WHERE   posto = $posto
                    AND     fabrica = $login_fabrica
                    AND     filial_posto <> $filial
                ";
                $resFilialVenda = pg_query($con,$sqlFilialVenda);

                pg_query($con,"COMMIT TRANSACTION");
            }
        }

    } else {
        echo "não subiu o arquivo\n";
    }
} catch(Exception $e) {
    pg_query($con,"ROLLBACK TRANSACTION");
    print("Erro.");
}

