<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'token_cookie.php';
include 'funcoes.php';

    if (isset($_POST["envia_excel"])) {
        $novoExcel = "";
        $msg_erro  = "";
        $erro_csv  = array();
        $excel     = $_FILES["excel"];

        if (empty($excel)) {
            $msg_erro = "Arquivo não enviado";
        }

        $conteudo = file_get_contents($excel["tmp_name"]);
        $conteudo = explode("\n", $conteudo);

        if (empty($conteudo)) {
            $msg_erro = "Arquivo sem conteúdo";
        }

        $conteudo = array_filter(array_map(function($valor){
            return explode(";", utf8_encode(trim($valor)));
        }, $conteudo), function($valor){
            global $erro_csv;
            if (count($valor) <> 3) {
                if (!empty($valor[0])) {
                    $erro_csv[] = "Layout do arquivo fora do padrão";
                }   
                return false;
            } else {
                return true;
            }
        });
        sort($conteudo);

        if (!empty($erro_csv)) {
            $msg_erro = implode("<br />", $erro_csv);
        }
        
        if (empty($msg_erro)) {
            echo "
                <script>
                    window.parent.importaExcel(".json_encode($conteudo).");
                    window.parent.Shadowbox.close();
                </script>
                ";
        }
    }

?>
<style type="text/css">
    body {
        font-size: 80%;
        font-family: 'Arial',sans-serif;
        background: #CDDBF1;
    }
    .error{
        background: #d90000;
        padding: 10px;
        color: #ffffff;
    }
    .titulo_tabela{
        background-color:#596d9b;
        font-size:: 14px;
        font-weight:  bold;
        color:#FFFFFF;
        text-align:center;
        padding: 10px;
    }   
    h2{
        margin: 0px;
        padding: 0px;
    }
    .content {
        text-align: center;
        padding: 5px;
        color:black;
        height: 180px;
    }

    p{line-height: 20px;padding: 5px;}
    .btn-importa{
        padding: 5px 15px;
        font-size: 14px;
        cursor: pointer;
    }
    .input{
        padding: 5px;
        font-size: 14px;
    }
    .label{
        font-size: 14px;
    }
</style>

<div class="content">
    <h2 class="titulo_tabela"><?=traduz('importa.pecas.do.excel', $con)?></h2>
    <?php
        if (!empty($msg_erro)) {
            echo '<div class="error">'.$msg_erro.'</div>';
        }
    ?>
    <p>
    Layout do anexo com delimitador 'ponto e vírgula': Código;Peça;Quantidade<br />
    </p>
    <form action="importa_peca_excel_jacto.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="envia_excel" value="true">
        <b class="label">Arquivo: </b>
        <input type="file" class="input" name="excel" ><br /><br />
        <button type="submit" class="btn-importa">Importar</button>
    </form>
</div>