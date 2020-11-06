<?php
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
?>
<!DOCTYPE html>
<html xmlns:ng="http://angularjs.org" >
    <head>
        <meta charset="iso-8859-1" />
        <title>Telecontrol Institucional</title>
        <!-- jQuery -->
        <script type="text/javascript"  src="lib/jquery/jquery.min.js"></script>

        <!-- Bootstrap -->
        <link rel="stylesheet" type="text/css" href="lib/bootstrap/css/bootstrap.min.css" />
        <script src="lib/bootstrap/js/bootstrap.min.js"></script>
        
        <style>
            #loading{
               height: 35px;
               width: 200px;
            }
            #spanContainer{
                width:100px;
                height: 40px;
                display:none;
            }
            #map {
                height:600px;
                width:600px;
            }
            .infoWindowContent {
                font-size:  14px !important;
                border-top: 1px solid #ccc;
                padding-top: 10px;
            }
            h2 {
                margin-bottom:0;
                margin-top: 0;
            }
            #link, #link a{
                color:#ffffff;
            }

        </style>

        <!-- Javascript -->

        <script src="js/auth.js"></script>
    
    </head>
    <body>
        <div class="container" id="ng-app"  >
            <br>
            <div>
                <script src="lib/mask/mask.min.js" ></script>

                <div style="width: 100%; height: auto; display: block;">

                    <div id="tit_princ" class="museo museo300">CONSULTAR ORDEM DE SERVIÇO</div><br />
                    <div id="txt_conteudo" class="museo300">Para encontrar a situação de sua Ordem de Serviço, preencha os dados abaixo.</div>
                    <div id="txt_conteudo_menor" class="museo300" style="font-size:14px;margin-bottom:24px">Insira o número da Ordem de Serviço ou CPF/CNPJ</div>
                    <div class="container ">
                        <div class="row">
                            <div class="col-md-12">
                                <div style="position:relative; float:left; width: 100%; height: auto; display: block;">
                                    <form name="statusos_form" role="form" method="POST" >
                                        <div class="row">
                                            <label for="os" class="col-xs-12" >Número da Ordem de Serviço</label>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-5">
                                                <input type="text" value="<?=$_POST['os']?>"  id="os" name="os" class="form-control" />
                                            </div>                                        
                                        </div>
                                        <div class="row">
                                            <label class="col-md-12" for="cpf_cnpj" >
                                                CPF / CNPJ
                                            </label>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-7">
                                                <input type="text"  name="cpf_cnpj" id="cpf_cnpj" class="form-control" value="<?=$_POST['cpf_cnpj']?>"  style="width:200px"/>
                                            </div>
                                        </div>
                                        <br /><br />
                                        <button class="submit_envia" type="submit" style="margin-left:0; padding-left:0;" >
                                            Consultar
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                    <?php 
                    if ($_POST) {
                        $where = "";
                        if (isset($_POST['os']) and $_POST['os'] != '') {
                            $where .= " AND tbl_os.os = '{$_POST['os']}' ";
                        }
                        if (isset($_POST['cpf_cnpj']) and $_POST['cpf_cnpj'] != '') {
                            $where .= " AND tbl_os.consumidor_cpf = '{$_POST['cpf_cnpj']}' ";
                        }
                        if ($where == '') {
                            ?>
                            <div class="alert alert-danger">
                                <strong>Erro!</strong> Preencha pelo menos um campo para a pesquisa.
                            </div>
                            <?php
                            exit;
                        }
                        $sqlConsulta = "SELECT  tbl_os.os,
                                                tbl_os.consumidor_nome,
                                                tbl_produto.descricao AS produto,
                                                tbl_status_checkpoint.descricao AS status,
                                                tbl_posto.nome,
                                                tbl_posto.endereco,
                                                tbl_posto.numero,
                                                tbl_posto.complemento,
                                                tbl_posto.cidade,
                                                tbl_posto.fone
                                        FROM tbl_os
                                            JOIN tbl_produto USING (produto)
                                            JOIN tbl_status_checkpoint USING (status_checkpoint)
                                            JOIN tbl_posto USING (posto)
                                        WHERE tbl_os.fabrica = 174 
                                            $where";
                        $resConsulta = pg_query($con, $sqlConsulta);
                    }
                    if (pg_num_rows($resConsulta) == 0) {
                        exit;
                    }                       
                    foreach (pg_fetch_all($resConsulta) as $os) {
                    ?>
                    <div class="container" >
                        <div class="row">
                            <div class="col-md-12">
                                <div id="resultado"></div>
                                <div id="result" class="panel panel-primary" style="margin-top: 10px; margin-bottom:  0px;">
                                    <ul class="list-group" style="margin-bottom: 0px;">
                                        <li class="list-group-item panel-heading" style="background-color: #428bca; border-color: #428bca">
                                            <h3 style="margin-top:0;margin-bottom:0;font-size:16px;color:inherit"><b>Ordem de serviço: <?=$os['os']?></b></h3>
                                        </li>
                                        <li class="list-group-item"> <b>Consumidor</b>: <?=$os['consumidor_nome']?></li>
                                        <li class="list-group-item"><b>Produto:</b> <?=$os['produto']?></li>
                                        <li class="list-group-item"><b>Situação:</b> <?=$os['status']?></li>
                                        <li class="list-group-item"><b>Posto Autorizado:</b> <?=$os['nome']?></li>
                                        <li class="list-group-item"><b>Endereço</b>: <?=$os['endereco']?>, <?=$os['numero']?>, <?=$os['complemento']?> - <?=$os['cidade']?> </li>
                                        <li class="list-group-item"><b>Telefone:</b> <?=$os['fone']?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }?>
                </div>

            </div>
        </div>


        </body>
</html>
