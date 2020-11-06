<?php 
    include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include 'autentica_admin.php';

    header("Content-type: text/html; charset=iso-8859-1");

    $cor       = (isset($_REQUEST["cor"])) ? $_REQUEST["cor"] : "";
    $ativo_cor = (isset($_REQUEST["ativo_cor"])) ? $_REQUEST["ativo_cor"] : "";
    $cor_id    = (isset($_REQUEST["cor_id"])) ? $_REQUEST["cor_id"] : "";

    if ($_REQUEST["btn_acao"] == "Gravar") {
        $cor = $_POST["cor"];
        $ativo_cor = ($_POST["ativo_cor"] == 'sim') ? 'TRUE' : 'FALSE';

        if (!empty($cor)) {
            $sql = "INSERT INTO tbl_cor (fabrica, nome_cor, ativo) VALUES ($login_fabrica, '$cor', $ativo_cor)";
            $res = pg_query($con, $sql);
            if (pg_last_error()) {
                $msg_erro = "Erro ao Cadastrar a Cor";
            } else {
                $msg_success = "Cor Cadastrada com Sucesso";
                $cor       = "";
                $ativo_cor = "";
                $cor_id    = "";
            }
        } else {
            $msg_erro = "Informe a Cor da Etiqueta";
        }
    }

    if ($_REQUEST["btn_acao"] == "Alterar") {
        $cor = $_POST["cor"];
        $cor_id = $_POST["cor_id"];
        $ativo_cor = ($_POST["ativo_cor"] == 'sim') ? 'TRUE': 'FALSE';

        if (!empty($cor)) {
            $sql = "UPDATE tbl_cor SET nome_cor = '$cor', ativo = $ativo_cor WHERE cor = $cor_id";
            $res = pg_query($con, $sql);
            if (pg_last_error()) {
                $msg_erro = "Erro ao Alterar a Cor";
            } else {
                $msg_success = "Cor Cadastrada com Sucesso";
                $cor       = "";
                $ativo_cor = "";
                $cor_id    = "";
            }
        } else {
            $msg_erro = "Informe a Cor da Etiqueta";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <link rel="stylesheet" href="plugins/bootstrap3/css/bootstrap.min.css">

        <style>

            body {
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                font-size: 14px;
                line-height: 20px;
                color: #333333;
            }

            .tc_formulario {
                background-color: #D9E2EF;
                text-align: center;
            }

            .titulo_tabela {
                background-color: #596d9b;
                font: bold 16px "Arial";
                color: #FFFFFF;
                text-align: center;
                padding: 5px 0 0 0;
            }

            .titulo_coluna {
                background-color: #596d9b;
                font: bold 11px "Arial";
                color: #FFFFFF;
                text-align: center;
                padding: 5px 0 0 0;
            }

            table.table {
                clear: both;
                margin-bottom: 6px !important;
                margin: 0 auto;
                width: 100%;
                margin: 0 auto;
            }

            .table th, .table td {
                padding: 8px;
                line-height: 20px;
                vertical-align: top;
                border-top: 1px solid #dddddd;
            }

            .arredondar {
                border-radius: 5px;
                -moz-border-radius: 5px;
                -webkit-border-radius: 5px;                
            }

            .centralizar {
                text-align: center;
            }
        </style>
        <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
        <script type="text/javascript">
            $(function() {
               $(".sair").click(function(){
                    window.parent.Shadowbox.close();
               }) 
            });

            $(document).ready(function() {
                setTimeout(function() {
                    $(".alert").hide('slow');
                }, 4000);

            })
        </script>
    </head>
    <body>
         <br />
        <div class="row-fluid">
            <div class='row '>
                <div class="col-sm-12">
                    <?php if (!empty($msg_erro)) { ?>
                            <h4 class="alert alert-danger centralizar"><b><?=$msg_erro?></b></h4>
                            <?php  $msg_erro = ""; ?>
                    <?php } else if (!empty($msg_success)) { ?>
                            <h4 class="alert alert-success centralizar"><b><?=$msg_success?></b></h4>
                            <?php  $msg_success = ""; ?>
                    <?php } ?>
                </div>
            </div>
        </div>
        <form class="tc_formulario" name="frm_etiquetas" method="post" action="cadastro_cor_etiquetas.php">
            <div class='container-fluid'>
                <div class='row-fluid'>
                    <div class="row centralizar">
                        <div class="col-sm-12 titulo_tabela"> 
                            Cadastro de Etiquetas
                        </div>
                    </div>
                </div>
                <br />
                <br />
                <div class='container-fluid'>
                    <div class='row '>
                        <div class="col-sm-2"></div>
                        <div class="col-sm-4">
                            <label for="cor">Cor: &nbsp;</label>
                            <input type="text" name="cor" class="arredondar" value="<?=$cor?>">
                            <input type="hidden" name="cor_id" value="<?=$cor_id?>">
                        </div>
                        <div class="col-sm-4">
                            <label for="ativo_cor">Ativo: &nbsp;</label>
                            <?php $selected = ($ativo_cor == "sim" || !isset($_GET["ativo_cor"])) ? "CHECKED" : ""; ?>
                            <input type="checkbox" name="ativo_cor" <?=$selected?> value="sim">
                        </div>
                        <div class="col-sm-2"></div>
                    </div> 
                </div>
                <br />
                <br />
                <div class='container-fluid'>
                    <div class='row centralizar'>
                        <div class="col-sm-12">
                            <?php $btn_acao = (!empty($_GET["cor"])) ? "Alterar" : "Gravar" ?>
                            <input type="submit" class="btn btn-success" name="btn_acao" value="<?=$btn_acao?>">
                            <input type="button" class="btn btn-danger sair" name="btn_acao" value="Sair">
                        </div>
                    </div> 
                </div>
            </div>
            <br />
            <br />
        </form>
        <br />
        <br />
        <br />
<?php
        $listar = $_REQUEST['listartudo'];
        if($listar || 1==1){ ?>
            <table id="resultado_etiquetas" class='table table-striped table-bordered table-hover table-fixed' >
                <thead>
                    <tr class="titulo_tabela">
                        <th colspan="3" class="centralizar">Etiquetas Cadastradas</th>
                    </tr>
                    <tr class="titulo_coluna">
                        <th class="centralizar">Cor</th>
                        <!-- <th class="centralizar">Fornecedor</th> -->
                        <th class="centralizar">Ativo</th>
                    </tr>
                </thead>
                <tbody>
    <?
            $sql = "SELECT  cor,
                            nome_cor,
                            ativo
                    FROM    tbl_cor
                    WHERE   fabrica = $login_fabrica 
                    ORDER BY nome_cor";
            $res = pg_query ($con,$sql);

            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                $cor_id = trim(pg_fetch_result($res,$i,'cor'));
                $cor    = trim(pg_fetch_result($res,$i,'nome_cor'));
                $cor    = (mb_detect_encoding($cor, "UTF-8") ? utf8_decode($cor) : $cor);
                $ativo  = trim(pg_fetch_result($res,$i,'ativo'));

                $ativo_inativo = '<img title="Inativo" src="imagens/status_vermelho.png">';
                $ativo_cor = "";
                if ($ativo == 't') {
                    $ativo_cor = "sim";
                    $ativo_inativo = '<img title="Ativo" src="imagens/status_verde.png">';
                }

        ?>
            <tr class="centralizar">
                <td><a href="cadastro_cor_etiquetas.php?cor=<?=$cor?>&ativo_cor=<?=$ativo_cor?>&cor_id=<?=$cor_id?>"><?=$cor?></a></td>
                <td><?=$ativo_inativo?></td>
            </tr>
        <?
            }
        ?>
            </tbody>
        </table>
        <?}?>


    </body>
</html>

