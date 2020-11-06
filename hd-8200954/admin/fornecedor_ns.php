<?
    include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include 'autentica_admin.php';


    $fornecedor = trim($_REQUEST["fornecedor"]);

    function verificaValorCampo($campo){
        return strlen($campo) > 0 ? $campo : "&nbsp;";
    }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
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
        <script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
        <link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
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
        <?
            echo "<div class='lp_nova_pesquisa'>";
                echo "<form action='$PHP_SELF' method='POST' name='nova_pesquisa'>";
                    echo "<table cellspacing='1' cellpadding='2' border='0'>";
                        echo "<tr>";
                            echo "<td>
                                <label>Nome</label>
                                <input type='text' name='fornecedor' value='$fornecedor' style='width: 150px' maxlength='20' />
                            </td>";
                            echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>"; 
                        echo "</tr>";
                    echo "</table>";
                echo "</form>";
            echo "</div>";

            if (strlen($fornecedor) > 2) {
                echo "<div class='lp_pesquisando_por'>Pesquisando pelo nome: $fornecedor</div>";


                $sql = "SELECT DISTINCT tbl_ns_fornecedor.nome_fornecedor 
                    FROM tbl_ns_fornecedor
                    WHERE    tbl_ns_fornecedor.nome_fornecedor ILIKE '%$fornecedor%'
                        AND tbl_ns_fornecedor.fabrica = $login_fabrica
                    ORDER BY tbl_ns_fornecedor.nome_fornecedor;";
            }else{
                $msg_erro = "Informar toda ou parte da informação para realizar a pesquisa!";
            }

            if(strlen($msg_erro) > 0){
                echo "<div class='lp_msg_erro'>$msg_erro</div>";
            }else{
                $res = pg_query ($con,$sql);

                if (@pg_numrows ($res) > 0) {?>
                    <table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
                        <thead>
                            <tr>
                                <th>Fornecedor</th>
                            </tr>
                        </thead>
                        <tbody><? 
                            for ($i = 0 ; $i < pg_num_rows($res); $i++) {
                                $nome_fornecedor = trim(pg_result($res, $i, 'nome_fornecedor'));

                                if(pg_num_rows($res) == 1){
                                    echo "<script type='text/javascript'>";
                                        echo "window.parent.retorna_dados_fornecedor('$nome_fornecedor'); window.parent.Shadowbox.close();";
                                    echo "</script>";
                                }

                                $onclick = "onclick= \"javascript: window.parent.retorna_dados_fornecedor('$nome_fornecedor'); window.parent.Shadowbox.close();\"";

                                $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
                                echo "<tr style='background: $cor' $onclick>";
                                    echo "<td>".verificaValorCampo($nome_fornecedor)."</td>";
                                echo "</tr>";
                            }
                }else
                    echo "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
            }?>
    </body>
</html>
