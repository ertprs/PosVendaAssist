<?php
    include "/etc/telecontrol.cfg";
    include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include 'autentica_usuario.php';

    $serie      = trim($_REQUEST["serie"]);
    $referencia = trim($_REQUEST["produto"]);
    
    function verificaValorCampo($campo){
        return strlen($campo) > 0 ? $campo : "&nbsp;";
    }
    
?>
<html>
    <head>
        <meta http-equiv=pragma content=no-cache>
        <style type="text/css">
            body {
                margin: 0;
                font-family: Arial, Verdana, Times, Sans;
                background: #fff;
            }
        </style>
        <script type="text/javascript" src="js/jquery-1.4.2.js"></script>
        <script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
        <link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
        <script type='text/javascript'>
            //função para fechar a janela caso a telca ESC seja pressionada!
            $(window).keypress(function(e) { 
                if(e.keyCode == 27) { 
                     window.parent.Shadowbox.close();
                }
            });

            $(document).ready(function() {
                $("#gridRelatorio").tablesorter();
            }); 
        </script>
    </head>

    <body>
        <div class="lp_header">
            <a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
                <img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
            </a>
        </div>
        <div class='lp_nova_pesquisa'>
            <form action='<?=$PHP_SELF?>' method='POST' name='nova_pesquisa'>
                <input type='hidden' name='produto' value='<?=$produto?>' />

                <table cellspacing='1' cellpadding='2' border='0'>
                    <tr>
                        <td>
                            <label><? echo traduz("numero.de.serie",$con,$cook_idioma);?></label>
                            <input type='text' name='serie' value='<?=$serie?>' style='width: 500px' maxlength='20' />
                        </td>
                        <td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>
                    </tr>
                </table>
            </form>
        </div>
<?
        if(strlen($serie) > 2){
?>
        <div class='lp_pesquisando_por'>Pesquisando por número de série do produto: <?=$serie?></div>

<?
            $sql = "SELECT  tbl_cliente_garantia_estendida.numero_serie                                     ,
                            tbl_cliente_garantia_estendida.nome                                             ,
                            tbl_cliente_garantia_estendida.cpf                                              ,
                            tbl_cliente_garantia_estendida.fone                                             ,
                            tbl_cliente_garantia_estendida.email                                            ,
                            tbl_cliente_garantia_estendida.cidade                                           ,
                            tbl_cliente_garantia_estendida.estado                                           ,
                            to_char(tbl_cliente_garantia_estendida.data_compra,'DD/MM/YYYY') AS data_compra
                    FROM    tbl_cliente_garantia_estendida
                    JOIN    tbl_produto USING (produto)
                    WHERE   tbl_cliente_garantia_estendida.fabrica = $login_fabrica
                    AND     tbl_cliente_garantia_estendida.numero_serie ILIKE '%$serie%'
                    AND     tbl_produto.referencia = '$referencia';
            ";
        }
        #echo nl2br($sql);
        $res = pg_exec ($con,$sql);

        if (pg_numrows ($res) == 1) {
            $numero_serie       = pg_fetch_result($res,0,numero_serie);
            $nome_consumidor    = pg_fetch_result($res,0,nome);
            $doc_consumidor     = pg_fetch_result($res,0,cpf);
            $fone_consumidor    = pg_fetch_result($res,0,fone);
            $email_consumidor   = pg_fetch_result($res,0,email);
            $cidade_consumidor  = pg_fetch_result($res,0,cidade);
            $estado_consumidor  = pg_fetch_result($res,0,estado);
            $compra_consumidor  = pg_fetch_result($res,0,data_compra);
?>
                <script type='text/javascript'>
                    window.parent.retorna_consumidor_garantia(<?="'".$numero_serie."','".$nome_consumidor."',".$doc_consumidor.",'".$fone_consumidor."','".$email_consumidor."','".$cidade_consumidor."','".$estado_consumidor."','".$compra_consumidor?>); window.parent.Shadowbox.close();
                </script>
<?
        }elseif(pg_numrows ($res) > 1){
?>
                <table width='100%' border='0' cellspacing='1' cellpadding='0' class='lp_tabela' id='gridRelatorio'>
                    <thead>
                        <tr>
                            <th>Nº Série</th>
                            <th>Consumidor</th>
                            <th>CPF/CNPJ</th>
                            <th>Telefone</th>
                            <th>E-Mail</th>
                            <th>Cidade</th>
                            <th>Estado</th>
                            <th>Data da Compra</th>
                        </tr>
                    </thead>
                    <tbody>
<?
            for ($i = 0 ; $i < pg_num_rows($res); $i++) {
                $numero_serie       = pg_fetch_result($res,$i,numero_serie);
                $nome_consumidor    = pg_fetch_result($res,$i,nome);
                $doc_consumidor     = pg_fetch_result($res,$i,cpf);
                $fone_consumidor    = pg_fetch_result($res,$i,fone);
                $email_consumidor   = pg_fetch_result($res,$i,email);
                $cidade_consumidor  = pg_fetch_result($res,$i,cidade);
                $estado_consumidor  = pg_fetch_result($res,$i,estado);
                $compra_consumidor  = pg_fetch_result($res,$i,data_compra);

                $onclick = "onclick= \"javascript: window.parent.retorna_consumidor_garantia('$numero_serie','$nome_consumidor','$doc_consumidor','$fone_consumidor','$email_consumidor','$cidade_consumidor','$estado_consumidor','$compra_consumidor'); window.parent.Shadowbox.close(); \"";

                $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
?>
                            <tr style='background: <?=$cor?>' <?=$onclick?>>
                                <td><? echo verificaValorCampo($numero_serie);?></td>
                                <td><? echo verificaValorCampo($nome_consumidor);?></td>
                                <td><? echo verificaValorCampo($doc_consumidor);?></td>
                                <td><? echo verificaValorCampo($fone_consumidor);?></td>
                                <td><? echo verificaValorCampo($email_consumidor);?></td>
                                <td><? echo verificaValorCampo($cidade_consumidor);?></td>
                                <td><? echo verificaValorCampo($estado_consumidor);?></td>
                                <td><? echo verificaValorCampo($compra_consumidor);?></td>
                            </tr>
<?
            }
?>
                    </tbody>
                </table>
<?
        }else{
?>
                <div class='lp_msg_erro'>Nehum resultado encontrado</div>
<?
        }
?>
    </body>
</html>