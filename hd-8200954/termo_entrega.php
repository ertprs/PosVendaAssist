<?php 
    include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include 'autentica_usuario.php';

    header("Content-type: text/html; charset=iso-8859-1");

    if (isset($_GET['os']) && $_GET['os'] > 0) {
        $os = $_GET['os'];

        $sql_fabrica = "SELECT tbl_fabrica.nome, 
                               tbl_posto_fabrica.contato_endereco, 
                               tbl_posto_fabrica.contato_numero, 
                               tbl_posto_fabrica.contato_complemento, 
                               tbl_posto_fabrica.contato_bairro, 
                               tbl_posto_fabrica.contato_cidade, 
                               tbl_posto_fabrica.contato_cep, 
                               tbl_posto_fabrica.contato_estado, 
                               tbl_posto_fabrica.contato_email, 
                               tbl_posto_fabrica.contato_fone_comercial
                        FROM tbl_os 
                        JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
                        JOIN tbl_fabrica ON tbl_os.fabrica = tbl_fabrica.fabrica
                        WHERE tbl_os.fabrica = $login_fabrica 
                        AND tbl_os.os = $os";
        $res_fabrica = pg_query($con, $sql_fabrica);
        if (pg_num_rows($res_fabrica) > 0) {
            $fabrica_nome      = pg_fetch_result($res_fabrica, 0, 'nome');  
            $posto_endereco    = pg_fetch_result($res_fabrica, 0, 'contato_endereco');
            $posto_numero      = pg_fetch_result($res_fabrica, 0, 'contato_numero');
            $posto_complemento = pg_fetch_result($res_fabrica, 0, 'contato_complemento');
            $posto_bairro      = pg_fetch_result($res_fabrica, 0, 'contato_bairro');
            $posto_cidade      = pg_fetch_result($res_fabrica, 0, 'contato_cidade');
            $posto_cep         = pg_fetch_result($res_fabrica, 0, 'contato_cep');
            $posto_estado      = pg_fetch_result($res_fabrica, 0, 'contato_estado');
            $posto_email       = pg_fetch_result($res_fabrica, 0, 'contato_email');
            $posto_fone        = pg_fetch_result($res_fabrica, 0, 'contato_fone_comercial');
        }

        $end_1 = "<p>";
        $end_2 = "<p>";
        $end_3 = "<p>";

        if (!empty($posto_endereco)) {
            $end_1 .= "<b>Rua:</b> $posto_endereco";
        }

        if (!empty($posto_numero)) {
            $end_1 .= ", <b>Número:</b> $posto_numero";   
        }

        if (!empty($posto_bairro)) {
            $end_1 .= ", <b>Bairro:</b> $posto_bairro";    
        }

        if (!empty($posto_complemento)) {
            $end_1 .= ", <b>Complemento:</b> $posto_complemento";
        }

        if (!empty($posto_cep)) {
            $end_2 .="<b>Cep:</b> $posto_cep";
        }

        if (!empty($posto_cidade)) {
            $end_2 .= ", <b>Cidade:</b> $posto_cidade";
        }

        if (!empty($posto_estado)) {
            $end_2 .= ", <b>Estado:</b> $posto_estado";
        }

        if (!empty($posto_fone)) {
            $end_3 .= "<b>Fone:</b> $posto_fone";    
        }
        
        if (!empty($posto_email)) {
            $end_3 .= ", <b>Email:</b> $posto_email";
        }

        $end_1 .= "</p>";
        $end_2 .= "</p>";
        $end_3 .= "</p>";


        $sql = "SELECT tbl_os.consumidor_nome,
                       tbl_os.consumidor_cpf, 
                       tbl_posto.nome,
                       tbl_produto.referencia,
                       tbl_produto.descricao
                FROM tbl_os
                JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $consumidor_nome = pg_fetch_result($res, 0, 'consumidor_nome');
            $consumidor_cpf = pg_fetch_result($res, 0, 'consumidor_cpf');
            $posto_nome = pg_fetch_result($res, 0, 'nome');
            $referencia = pg_fetch_result($res, 0, 'referencia');
            $descricao = pg_fetch_result($res, 0, 'descricao');
            $data_hj = date ("d-m-Y");
        }
    }

    if (isset($_POST['imprimir_entrega']) && $_POST['imprimir_entrega'] != '') {
        if (isset($_POST['os'])) {
            $os = $_POST['os'];
            $data = date ('Y-m-d H:i:s');
            
            $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0) {
                $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);
                $campos_adicionais['termo_entrega_produto'] = $data;
                $campos_adicionais = json_encode($campos_adicionais);
                $sql_insert = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais' WHERE os = $os AND fabrica = $login_fabrica";
            } else {
                $campos_adicionais['termo_entrega_produto'] = $data;
                $campos_adicionais = json_encode($campos_adicionais);
                $sql_insert = "INSERT INTO tbl_os_campo_extra (os,fabrica,campos_adicionais) VALUES ($os, $login_fabrica, '$campos_adicionais')";
            }

            $res_insert = pg_query($con, $sql_insert);
            if (pg_last_error($con) > 0) {
                echo 'erro';
            } else {
                echo 'ok';
            }
        }
        exit;
    } 
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    

    <!-- Bootstrap -->
    <link rel="stylesheet" href="plugins/bootstrap3/css/bootstrap.min.css">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

        <style>
            body {
                font-family: arial, verdana;
                color: #444;
                background-color: #fff;
            }

            .text_padrao {
                font-size: 14px !important;
            }
           
            h1{
                font-size: 20px !important;
            }

            h3{
                font-weight: bold;
                font-size: 14px !important;
            }

            h4{
                font-weight: bold;
                font-size: 10px !important;
            }

            p{
                font-size: 14px !important;
            }

            @media print {
                .imprimir { display: none; }
            .col-sm-1, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-11, .col-sm-12 {
                    float: left;
               }
               .col-sm-12 {
                    width: 100%;
               }
               .col-sm-11 {
                    width: 91.66666667%;
               }
               .col-sm-10 {
                    width: 83.33333333%;
               }
               .col-sm-9 {
                    width: 75%;
               }
               .col-sm-8 {
                    width: 66.66666667%;
               }
               .col-sm-7 {
                    width: 58.33333333%;
               }
               .col-sm-6 {
                    width: 50%;
               }
               .col-sm-5 {
                    width: 41.66666667%;
               }
               .col-sm-4 {
                    width: 33.33333333%;
               }
               .col-sm-3 {
                    width: 25%;
               }
               .col-sm-2 {
                    width: 16.66666667%;
               }
               .col-sm-1 {
                    width: 8.33333333%;
               }

               .ajuste_left {
                    margin-left: -60px;
                }
            }

            .tac {
                text-align: center !important;
            }

	    hr {
		border-color : #000;	
	    }
            
        </style>
        <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
        <script type="text/javascript">
            $(function() {
                $('#btn_imprimir_entrega').click(function(){
                    var os = '<?=$os?>';
                    var fabrica = '<?=$login_fabrica?>';
                    window.print();
                    $.ajax({
                        async: true,
                        url:"<?=$PHP_SELF?>",
                        type:"POST",
                        data:{
                            imprimir_entrega: 'sim',
                            os               : os
                        },
                        complete: function(data){
                            var dados = data.responseText;
                            if(dados == 'ok'){
                                alert('Você será direcionado para tela de lançamento de itens');
                                if (fabrica == 160 || fabrica == 188) {
                                    window.location.href="cadastro_os.php?os_id="+os+"&termo=ok";
                                } else {
                                    window.location.href="os_item_new.php?os="+os;
                                }
                            } else {
                                alert('Erro na inserção dos dados');
                            }
                        }
                    });
                });
            });
        </script>
    </head>
    <body>
        <br />
        <div class="row-fluid">
            <div class='row '>
                <div class="col-sm-12 tac imprimir">
                    <button class="btn btn-primary" type="submit" id="btn_imprimir_entrega" name="imprimir">Imprimir</button>
                </div>
            </div>
        </div>
        <div class='container-fluid'>
            <div class='row '>
                <div class="col-sm-12"> 
                    <h1>
                        Termo de Entrega de Produto para Conserto
                    </h1>
                    <h3>
                        OS: <?=$os?>
                    </h3>
                </div>
            </div>
            <div class='row '>
                <div class="col-sm-12">         
                    <br />
                    <p>
                        Eu, <?=$consumidor_nome?>, CPF <?=$consumidor_cpf?>,
                        <br /><br />
                        Declaro que entreguei, na data de hoje: <?=$data_hj?>, a ferramenta <?=$fabrica_nome?> modelo <?=$referencia.' - '.$descricao?>, para
                        conserto, na Assistência Técnica: <?=$posto_nome?>
                    </p>
                    <br /><br />
                </div>    
            </div>
            <div class='row '>
                <div class="col-sm-4"></div>
                <div class="col-sm-8">
                    <hr />
                    <h4>
                        <?=$consumidor_nome?>
                    </h4>
                </div>
            </div>
            <br /><br /><br />
            <div class='row'>
                <div class="col-sm-4">
                    <p class="text_padrao"> 
                        Telefones para contato:
                    </p>
                </div>
                <div class="col-sm-8">
                    <p class="text_padrao ajuste_left">
                    (_____)______________________   /   (_____)______________________ 
                    </p>
                </div>
            </div>
            <br />
            <div class='row'>
                <div class="col-sm-4"></div>
                <div class="col-sm-8">
                    <p class="text_padrao ajuste_left">
                         (_____)______________________   /   (_____)______________________
                    </p>
                </div>
            </div>
            <br /><br /><br />
            <div class='row '>
                <div class="col-sm-12">
                    <h3>
                       Assistência Técnica
                   </h3>
               </div>
            </div>
            <div class='row '>
                <div class="col-sm-12">
                    <p> 
                        Por gentileza; se a Nota fiscal / Cupom fiscal não estiver legí­vel ( borrado, tinta de impressão em tom cinza muito claro, etc ), favor anotar abaixo, o número da chave de acesso: 
                    </p>
                    <br /><br/>
                    <hr />
                </div>
            </div>
            <br /><br/><br />
            <div class='row '>
                <div class="col-sm-8">
                    <hr />
                    <h4>
                        <?=$posto_nome?>
                    </h4> 
                </div>
            </div>
            <br /><br />
            <div class='row '>
                <div class="col-sm-8">
                    <?=$end_1?>
                    <?=$end_2?>
                    <?=$end_3?>  
                </div>
            </div>
        </div>
    </body>
</html>
