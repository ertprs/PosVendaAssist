<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';
include __DIR__.'/funcoes.php';

if ($areaAdmin === true) {
    $admin_privilegios = "info_tecnica";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';    
}

$treinamento = trim($_GET["treinamento"]);

if(!empty($_POST["btn_submit"])) {

    $registro    = array();
    $extensao    = strtolower(preg_replace("/.+\./", "", $_FILES["upload"]["name"]));
    
    if (!in_array($extensao, array("csv", "txt"))) {
        $msg_erro = "Formado de arquivo inválido.";
    }

    $arquivo = fopen($_FILES['upload']['tmp_name'], 'r+');

    if ($arquivo && strlen($msg_erro) == 0) {

        while(!feof($arquivo)){

            $linha = fgets($arquivo,4096);
            
            if (strlen(trim($linha)) > 0) {
                $registro[] = explode(";", $linha);
            }

        }

        fclose($f);
    }

    if (count($registro) > 0 && strlen($msg_erro) == 0) {
        $res = pg_query($con,"BEGIN TRANSACTION");
        $count = 1;
        
        foreach ($registro as $key => $registro) {

            $codigo_posto             = trim($registro[0]);
            $posto_email              = trim($registro[1]);
            $cidade_assistencia       = trim($registro[2]);
            $tecnico_nome             = trim($registro[3]);
            $tecnico_data_nascimento  = trim($registro[4]);
            $tecnico_cpf              = trim($registro[5]);
            $tecnico_rg               = trim($registro[6]);
            $tecnico_email            = trim($registro[7]);
            $tecnico_fone             = trim($registro[8]);
            $tecnico_celular          = trim($registro[9]);
            $observacao               = trim($registro[10]);

			if (empty($observacao) && $login_fabrica != 138) {
				$msg_erro .= "O arquivo enviado não segue o layout estabelecido, algumas informações não estão presentes no arquivo. <br> Favor verificar se os dados apresentados estão corretos.";
                break;
			}
            
            if (strlen($cidade_assistencia) == 0) {
                $msg_erro .= "A cidade do posto $codigo_posto informada na linha $count está em branco <br><br>";
            }

            $tecnico_cpf = substr(preg_replace('/\D/', '', $tecnico_cpf), 0, 14);

            if (strlen($tecnico_nome) == 0) {
                $msg_erro .= "O nome do técnico do posto $codigo_posto informada na linha $count está em branco<br><br>";
            }

            if (strlen($tecnico_cpf) == 0 && $login_fabrica != 138) {
                $msg_erro .= "O CPF do técnico do posto $codigo_posto informada na linha $count está em branco<br><br>";
            }

            if (strlen($tecnico_data_nascimento) == 0) {
                $msg_erro .= "A data de nascimento do técnico do posto $codigo_posto informada na linha $count está em branco<br><br>";
            }

            if (strlen($tecnico_fone) == 0) {
                $msg_erro .= "Favor informar o telefone de contato na linha $count <br><br>";
            }

            if (strlen($tecnico_celular) == 0) {
                $msg_erro .= "Favor informar o celular de contato na linha $count <br><br>";
            }

            if (strlen($posto_email) == 0) {
                $msg_erro .= "Favor informar o email do posto na linha $count <br><br>";
            }

            if (strlen($tecnico_rg) == 0) {
                $msg_erro .= "Favor informar o RG do técnico na linha $count <br><br>";
            }

            if (strlen($treinamento) == 0) {
                $msg_erro .= "Favor informar o treinamento escolhido na linha $count <br><br>";
            }  elseif (strlen($observacao) == 0) {

                $sql = "SELECT adicional FROM tbl_treinamento WHERE treinamento=$treinamento";
                $res = pg_query($con, $sql);
                if ($res) {

                    $adicional = pg_fetch_result($res, 0, 0);
                    if ($adicional) {
                        $msg_erro .= "Favor informar $adicional<br>";
                    }

                } else {
                    $msg_erro .= "Favor informar o treinamento escolhido<br>";
                }
            }

            if (strlen($posto_email) > 0) {
                if (!valida_email($posto_email)) {
                    $msg_erro .= "O e-mail $posto_email do posto $codigo_posto informado na linha $count é inválido. <br><br>";
                }
            }

            if (strlen($codigo_posto) > 0 ) {
                $sql   = "SELECT posto 
                            FROM tbl_posto_fabrica 
                           WHERE codigo_posto = '$codigo_posto' 
                             AND fabrica = $login_fabrica";
                $res   = pg_query ($con,$sql);

                if (pg_num_rows($res) > 0) {
                    $posto = trim(pg_fetch_result($res,0,0));
                } else {
                    $msg_erro .= "O código do posto $codigo_posto informado na linha $count está incorreto, não existe nenhum posto cadastrado com o mesmo. Favor verificar.<br> <br>";
                }
            }

            $aux_tecnico_nome = "'".$tecnico_nome."'";
            if (strlen($tecnico_cpf) > 0) {
                $aux_tecnico_cpf  = "'".$tecnico_cpf."'" ;
            }

            $aux_tecnico_rg   = "'".$tecnico_rg."'"  ;
            $aux_tecnico_fone = "'".$tecnico_fone."'";

            $tecnico_data_nascimento = preg_replace ("/\D/" , '', $tecnico_data_nascimento);

            if (strlen ($tecnico_data_nascimento) == 6) {
                $tecnico_data_nascimento = substr ($tecnico_data_nascimento,0,4) . "20" . substr ($tecnico_data_nascimento,4,2);
            }

            if (strlen ($tecnico_data_nascimento)   > 0) {
                $tecnico_data_nascimento   = substr ($tecnico_data_nascimento,0,2)   . "/" . substr ($tecnico_data_nascimento,2,2)   . "/" . substr ($tecnico_data_nascimento,4,4);
            }

            if (strlen ($tecnico_data_nascimento) < 10) {
                $tecnico_data_nascimento = date ("d/m/Y");
            }

            $x_tecnico_data_nascimento = substr ($tecnico_data_nascimento,6,4) . "-" . substr ($tecnico_data_nascimento,3,2) . "-" . substr ($tecnico_data_nascimento,0,2);

            if (strlen($x_tecnico_data_nascimento) > 0 && in_array($login_fabrica, array(20))) {
                $sql = "SELECT date'$x_tecnico_data_nascimento' > (current_date-interval'18 year')";
                $res = pg_query ($con,$sql);
                if (pg_fetch_result($res,0,0) == 't') {
                    $msg_erro .= 'NÃO É PERMITIDO A PARTICIPAÇÃO DE MENORES DE 18 ANOS NO TREINAMENTO BOSCH';
                }
            }

            if(strlen($aux_tecnico_cpf) > 0 OR strlen($tecnico_nome) > 0) {

                if (strlen($aux_tecnico_cpf) > 0) {
                    $cond_tecnico = "AND tbl_tecnico.cpf = $aux_tecnico_cpf ";
                } else {
                    $cond_tecnico = " AND tbl_tecnico.nome ILIKE '%$tecnico_nome%'";
                }

                $sql = "SELECT tbl_tecnico.nome,
                                tbl_tecnico.cpf
                          FROM tbl_treinamento
                          JOIN tbl_treinamento_posto USING(treinamento)
                          JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico
                         WHERE tbl_treinamento.treinamento = $treinamento
                           AND tbl_treinamento.fabrica = $login_fabrica
                        $cond_tecnico";
                $res = pg_query($con,$sql);
                if (pg_num_rows($res) > 0) {
                    $aux_cpf = pg_fetch_result($res, 0, 'cpf');
                    $msg_erro .= "Já existe um técnico cadastrado para este treinamento com o CPF $cpf informado<br>";
                }
            }

            $aux_promotor             = (strlen($promotor) == 0) ? "null" : "'".$promotor."'";
            $aux_promotor_treinamento = (strlen($promotor_treinamento) == 0) ? "null" : "'".$promotor_treinamento."'";
            $aux_posto                = (strlen($posto) == 0) ? "null" : "'".$posto."'";
            $hotel                    = (strlen($hotel) == 0) ? "'f'" : "'t'";

            if (strlen($msg_erro) == 0) {
                //--==== Controle de Quantidade de vagas existentes no treinamento ======================================
                $sql = "SELECT COUNT(treinamento_posto) AS total_inscritos,
                               prazo_inscricao IS NOT NULL AND prazo_inscricao > CURRENT_DATE AS expirou_prazo,
                               tbl_treinamento.vagas
                          FROM tbl_treinamento
                          JOIN tbl_treinamento_posto USING(treinamento)
                         WHERE tbl_treinamento.treinamento = $treinamento
                           AND tbl_treinamento_posto.ativo IS TRUE
                      GROUP BY tbl_treinamento.vagas,prazo_inscricao;";
                $res = pg_query ($con,$sql);

                if (pg_num_rows($res) > 0) {

                    $total_inscritos = trim(pg_fetch_result($res,0,'total_inscritos'));
                    $expirou_prazo   = trim(pg_fetch_result($res,0,'expirou_prazo'));
                    $vagas           = trim(pg_fetch_result($res,0,'vagas'));

                    if ($total_inscritos >= $vagas) {
                        $msg_erro .= "Todas as Vagas estão preenchidas, procure uma nova data <br><br>";
                        break;
                    }
                }

                // controle de data máxima de inscrição: o admin tem que confirmar no checkbox
                if ($treinamento_prazo_inscricao && $expirou_prazo =='t' && $admin_aut_fora_prazo != 't') {
                    $msg_erro .= "Cadastro do técnico fora do prazo de inscrição!<br />Por favor, confirme que realmente quer inscrever o técnico fora de prazo.";
                }

                if (strlen($aux_tecnico_cpf) > 0) {
                    $cond_cpf = " AND cpf = $aux_tecnico_cpf ";
                } else {
                    $cond_cpf = " AND nome ILIKE '%$tecnico_nome%' ";
                }

                $sql = "SELECT tecnico
                          FROM tbl_tecnico
                         WHERE fabrica  = $login_fabrica 
                           AND posto = $posto $cond_cpf";
                $resTecnico = pg_query($con,$sql);

                if (pg_num_rows($resTecnico) > 0) {
                    $tecnico = pg_fetch_result($resTecnico,0,tecnico);
                } else {
                    if (strlen($aux_tecnico_cpf) == 0) {
                        $aux_tecnico_cpf = 'null';
                    }

                    $sql = "INSERT INTO tbl_tecnico(
                                                   fabrica,
                                                   posto,
                                                   nome,
                                                   cpf,
                                                   data_nascimento,
                                                   telefone, 
                                                   rg
                                                ) VALUES (
                                                   $login_fabrica,
                                                   $posto,
                                                   $aux_tecnico_nome,
                                                   $aux_tecnico_cpf,
                                                   '$x_tecnico_data_nascimento',
                                                   $aux_tecnico_fone,$aux_tecnico_rg
                                                )";
                    $resTecnico = pg_query($con,$sql);

                    $sql = "SELECT tecnico 
                              FROM tbl_tecnico 
                             WHERE fabrica  = $login_fabrica 
                               AND posto = $posto $cond_cpf;";
                    $resTecnico = pg_query($con, $sql);

                    if (pg_num_rows($resTecnico) > 0) {
                        $tecnico = pg_fetch_result($resTecnico, 0, tecnico);
                    } else {
                        $msg_erro .= 'O arquivo enviado não segue o layout estabelecido, algumas informações não estão presentes no arquivo. <br> Favor verificar se os dados apresentados estão corretos.';
                        break;
                    }
                }

                $campoAdmin = '';
                $valorAdmin = '';

                if ($areaAdmin === true) {
                    $campoAdmin = "admin,";
                    $valorAdmin = "$login_admin,";
                }

                $sql = "INSERT INTO tbl_treinamento_posto (
                                                                tecnico ,
                                                                promotor     ,
                                                                posto        ,
                                                                hotel        ,
                                                                treinamento  ,
                                                                $campoAdmin
                                                                promotor_treinamento,
                                                                observacao
                                                            ) VALUES (
                                                                $tecnico         ,
                                                                $aux_promotor    ,
                                                                $posto           ,
                                                                $hotel           ,
                                                                $treinamento     ,
                                                                $valorAdmin
                                                                $aux_promotor_treinamento,
                                                                '$observacao'
                                                            )";
                if (strlen($msg_erro) == 0) {
                    $res = pg_query($con, $sql);
                    $msg_erro .= pg_last_error($con);
                    $sql = "SELECT CURRVAL ('seq_treinamento_posto')";
                    $res = pg_query($con,$sql);
                    $treinamento_posto = pg_fetch_result($res,0,0);

                    $email = $posto_email;

                    if ($msg_erro == 0) {
                        $chave1 = md5($posto);
                        $chave2 = md5($treinamento_posto);
                        $sql=  "SELECT  titulo,
                                        TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')     AS data_inicio,
                                        TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')        AS data_fim,
                                        TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY') AS prazo_inscricao
                                  FROM  tbl_treinamento
                                 WHERE  treinamento = $treinamento";
                        $res = pg_query ($con,$sql);

                        if (pg_num_rows($res) > 0) {
                            $titulo          = pg_fetch_result($res,0,'titulo');
                            $data_inicio     = pg_fetch_result($res,0,'data_inicio');
                            $data_fim        = pg_fetch_result($res,0,'data_fim');
                            $vagas_min       = pg_fetch_result($res,0,'vagas_min');
                            $prazo_inscricao = pg_fetch_result($res,0,'prazo_inscricao');
                        }

                        //ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO
                        $email_origem  = "verificacao@telecontrol.com.br";
                        $email_destino = "$email";
                        $assunto       = "Confirmação de Presença no Treinamento";
                        $corpo.= "Titulo $titulo <br>\n";
                        $corpo.= "Data Início $data_inicio<br> \n";
                        $corpo.= "Data Término $data_fim <p>\n";

                        if ($treinamento_prazo_inscricao) { // se a fábrica usa prazo de inscrição, avisar o posto
                            $corpo .= "<p>Lembramos que o prazo para <b>confirmar a inscrição</b> é até <strong>$prazo_inscricao</strong>: depois desta data, se a inscrição não foi confirmada, a mesma será cancelada.<p/>";
                        }

                        $corpo.="<br>Você recebeu esse email para confirmar a inscrição do técnico.\n\n";
                        $corpo.="<br>Nome $tecnico_nome \n";
                        if($login_fabrica != 117){
                            $corpo.="<br>RG$tecnico_rg \n";
                        }
                        $corpo.="<br>CPF $tecnico_cpf \n";
                        $corpo.="<br>Telefone de Contato $tecnico_fone \n\n";
                        if($adicional) $corpo.="<br>$adicional $observacao \n\n";
                        $corpo.="<br>Email $email\n\n";
                        $corpo.="<br><br><a href='http://posvenda.telecontrol.com.br/assist/treinamento_confirmacao.php?key1=$chave1&key2=$posto&key3=$chave2&key4=$treinamento_posto'>CLIQUE AQUI PARA CONFIRMAR PRESENÇA</a>\n\n";
                        $corpo.="<br>Caso o link acima esteja com problema copie e cole este link em seu navegador: http://posvenda.telecontrol.com.br/assist/treinamento_confirmacao.php?key1=$chave1&key2=$posto&key3=$chave2&key4=$treinamento_posto\n\n";
                        $corpo.="<br><br><br>Telecontrol\n";
                        $corpo.="<br>www.telecontrol.com.br\n";
                        $corpo.="<br>_______________________________________________\n";
                        $corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";


                        $body_top  = "MIME-Version: 1.0\r\n";
                        $body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
                        $body_top .= "From: $email_origem\r\n";

                        if (mail($email_destino, stripslashes($assunto), $corpo, $body_top)) {
                            $msg = "$email";
                        } else {
                            $msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
                        }

                        $sql = "SELECT nome, email
                                  FROM tbl_promotor_treinamento
                                 WHERE promotor_treinamento = $aux_promotor_treinamento";
                        $res = pg_query($con,$sql);

                        if (pg_num_rows($res) > 0) {
    
                            $nome_promotor   = pg_fetch_result($res, 0, nome);
                            $email_promotor  = pg_fetch_result($res, 0, email);

                            if(strlen($email_promotor) > 0) {

                                $sql = "SELECT nome, codigo_posto
                                          FROM tbl_posto
                                          JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
                                           AND tbl_posto_fabrica.fabrica = $login_fabrica
                                         WHERE tbl_posto.posto = $posto";
                                $res = pg_query($con,$sql);

                                if (pg_num_rows($res) > 0) {

                                    $nome_posto    = pg_fetch_result($res,0,nome)        ;
                                    $xcodigo_posto = pg_fetch_result($res,0,codigo_posto);
                                    $corpo         = "";
                                    $email_origem  = "verificacao@telecontrol.com.br";
                                    $email_destino = "$email_promotor";
                                    $assunto       = "Confirmação de Presença no Treinamento";
                                    $corpo        .= "<br>Caro Promotor,";
                                    $corpo        .= "<BR>Segue abaixo informações do posto e o treinamento solicitado\n<BR>";
                                    $corpo        .= "Titulo $titulo <br>\n";
                                    $corpo        .= "Data Início $data_inicio<br> \n";
                                    $corpo        .= "Data Término $data_fim <p>\n";

                                    if ($treinamento_prazo_inscricao) { // se a fábrica usa prazo de inscrição, avisar o posto
                                        $corpo .= "<p>Lembramos que o prazo para <b>confirmar a inscrição</b> é até <strong>$prazo_inscricao</strong>: depois desta data, se a inscrição não foi confirmada, a mesma será cancelada.<p/>";
                                    }

                                    $corpo .= "<BR>Posto $xcodigo_posto - $nome_posto\n";
                                    $corpo .= "<br>Nome $tecnico_nome \n";
                                    $corpo .= "<br>RG$tecnico_rg \n";
                                    $corpo .= "<br>CPF $tecnico_cpf \n";
                                    $corpo .= "<br>Telefone de Contato $tecnico_fone \n\n";
                                    if($adicional) {
                                        $corpo .= "<br>$adicional $observacao \n\n";
                                    }
                                    $corpo    .= "<br>Email $email\n\n";
                                    $corpo    .= "<br><br><br>Telecontrol\n";
                                    $corpo    .= "<br>www.telecontrol.com.br\n";
                                    $corpo    .= "<br>_______________________________________________\n";
                                    $corpo    .= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";
                                    $body_top  = "MIME-Version: 1.0\r\n";
                                    $body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
                                    $body_top .= "From: $email_origem\r\n";

                                    if (mail($email_destino, stripslashes($assunto), $corpo, $body_top ) ){
                                        $msg = "$email";
                                    } else {
                                        $msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
                                    }
                                }
                            }
                        }
                    } else {
                        $msg_erro .= "Erro ao salvar o técnico no treinamento";
                        break;
                    }
                }
            } 
            $count++;
        }
    }

    if (strlen($msg_erro) == 0 ) {
        $res = pg_query($con,"COMMIT TRANSACTION");
        $msg_sucesso = true;
    } else {
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        $msg_sucesso = false;
    }

}

function valida_email($email="") {
    return preg_match("/^[a-z]+([\._\-]?[a-z0-9\._-]+)+@+[a-z0-9\._-]+\.+[a-z]{2,3}$/", $email);
}

function retorna_adicional_fabrica($treinamento) {
    global $con;

    $sql = "SELECT adicional 
            FROM tbl_treinamento
            WHERE treinamento = {$treinamento}";
    $res = pg_query($con, $sql);

    $adicional = pg_fetch_result($res,0,'adicional');

    return $adicional.';';
}

?>
<!DOCTYPE html>
<html>
<head>
    <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="bootstrap/js/bootstrap.js"></script>
    <script src="plugins/dataTable.js"></script>
    <script src="plugins/resize.js"></script>
    <script>
        function grava_procedimento(){

            $(".response").html("");

            var defeito = $('#defeito').val();
            var solucao = $('#solucao').val();
            var produto = $('#produto').val();
            var procedimento = $('#procedimento').val();

            var alert = "";
            var situacao = <?php echo (strlen($procedimento) == 0) ? "'inserida'" : "'alterada'"; ?>;

            if(procedimento == ""){
                alert = "<div class='alert alert-danger tac'><h4>Por favor, insira a Descrição para a Solução</h4></div>";
                $(".response").html(alert);
                $('#procedimento').focus();
                return;
            }

            $.ajax({
                url : "<?php echo $PHP_SELF; ?>",
                type : "POST",
                data : {
                    defeito : defeito,
                    solucao : solucao,
                    produto : produto,
                    procedimento : procedimento,
                    inserir_procedimento : true
                },
                beforeSend: function(){
                    $(".response").html("<em>inserindo... por favor aguarde!</em>");
                },
                complete: function(data){

                    data = $.parseJSON(data.responseText);

                    if(data.status == true){
                        $('#procedimento').val("");
                        alert = "<div class='alert alert-success tac'><h4>Descrição "+situacao+" com sucesso</h4></div>";
                        window.parent.insere_procedimento(data.defeito, data.solucao, data.produto);
                        // console.log(data);
                    }else{
                        alert = "<div class='alert alert-danger tac'><h4>Erro ao "+situacao+" a Descrição</h4></div>";
                    }

                    $(".response").html(alert);

                    setTimeout(function(){
                        $(".response").html("");
                    }, 10000);
                }
            });

        }
    </script>
</head>
<body>
    <div class='titulo_tabela' style="padding: 10px;">Cadastro de Técnico via upload</div>
    <FORM name='frm_relatorio' enctype="multipart/form-data"  METHOD='POST' ACTION='#' style='padding-top:20px'  class='tc_formulario'>
        <div class="container">
            <div class="alert">
                Arquivo deve ser no formato .CSV, separados por ponto e virgula(;).<br />
                <b>Layout:</b> Código do Posto; E-mail do Posto; Cidade do Posto; Nome do Técnico; Data de Nascimento do Técnico; CPF do Técnico; RG do Técnico; E-mail do Técnico; Fone do Técnico; Celular do Técnico; <?= retorna_adicional_fabrica($treinamento) ?>

            </div>
        </div>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8 tac' align='center'>
                <b>Arquivo:</b>
                <div class='control-group tac'>
                        <input type='file' required="required" name='upload' id='upload'/>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group'>
                    <div class='controls controls-row tac' align="center">
                        <input type="submit" name="btn_submit" class="btn btn-success" value="Efetuar o Upload">
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
    </FORM>

    <?php if (strlen($msg_erro) > 0) {?>
        <br />
        <div class="alert alert-error"><h4><?php echo $msg_erro;?></h4></div>
    <?php }?>

    <?php if ($msg_sucesso) {?>
        <br />
        <div class="alert alert-success"><h4><b>Treinamento Agendado com sucesso!</b></h4></div>
    <?php }?>

</body>
</html>
