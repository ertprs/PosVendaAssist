<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';
include '../../class/communicator.class.php';

include 'class/aws/s3_config.php';
include_once S3CLASS;

$login_fabrica = 160;

$array_estado = array(
    'AC' => 'Acre',
    'AL' => 'Alagoas',
    'AM' => 'Amazonas',
    'AP' => 'Amapá',
    'BA' => 'Bahia',
    'CE' => 'Ceara',
    'DF' => 'Distrito Federal',
    'ES' => 'Espírito Santo',
    'GO' => 'Goiás',
    'MA' => 'Maranhão',
    'MG' => 'Minas Gerais',
    'MS' => 'Mato Grosso do Sul',
    'MT' => 'Mato Grosso',
    'PA' => 'Pará',
    'PB' => 'Paraíba',
    'PE' => 'Pernambuco',
    'PI' => 'Piauí­',
    'PR' => 'Paraná',
    'RJ' => 'Rio de Janeiro',
    'RN' => 'Rio Grande do Norte',
    'RO' => 'Rondônia',
    'RR' => 'Roraima',
    'RS' => 'Rio Grande do Sul',
    'SC' => 'Santa Catarina',
    'SE' => 'Sergipe',
    'SP' => 'São Paulo',
    'TO' => 'Tocantins'
);


function validaEstado() {
    global $array_estado, $_POST;

    $estado = strtoupper($_POST["estado"]);

    if (!empty($estado) && !in_array($estado, array_keys($array_estado))) {
        throw new Exception("Estado inválido");
    }
}

function validaCidade() {
    global $con, $_POST;

    $cidade = utf8_decode($_POST["cidade"]);
    $estado = strtoupper($_POST["estado"]);

    if (!empty($cidade) && !empty($estado)) {
        $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(estado) = '{$estado}' AND UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}'))";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("Cidade não encontrada".$sql);
        }
    }
}

function validaEmail() {
    global $_POST;

    $email = $_POST["email"];

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inválido");
    }
}

function checaCPF(){
    global $_POST, $con, $login_fabrica;    // Para conectar com o banco...

    $cpf = $_POST['cpf'];
    $cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
    if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) return false;

    if(strlen($cpf) > 0){
        $res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
        if ($res_cpf === false) {
            $cpf_erro = pg_last_error($con);
            if ($use_savepoint) $n = @pg_query($con,"ROLLBACK TO SAVEPOINT checa_CPF");
            throw new Exception("CPF informado inválido");
        }
    }
}


if (isset($_POST["ajax_enviar"])) {

    $regras = array(
        "notEmpty" => array(
            "nome",
            "email",
            "estado",
            "cidade",
            "telefone",
            "motivo_contato"
        ),
        "validaEstado" => "estado",
        "validaCidade" => "cidade",
        "validaEmail"  => "email"
    );

    $msg_erro = array(
        "msg"    => array(),
        "campos" => array()
    );

    foreach ($regras as $regra => $campo) {
        switch ($regra) {
            case "notEmpty":
                foreach($campo as $input) {
                    $valor = trim($_POST[$input]);

                    if (empty($valor)) {
                        $msg_erro["msg"]["obg"] = utf8_encode("Preencha todos os campos obrigatórios");
                        $msg_erro["campos"][]   = $input;
                    }
                }
                break;

            default:
                $valor = trim($_POST[$campo]);
                if (!empty($valor)) {
                    try {
                        call_user_func($regra);
                    } catch(Exception $e) {
                        $msg_erro["msg"][]    = utf8_encode($e->getMessage());
                        $msg_erro["campos"][] = $campo;
                    }
                }
                break;
        }
    }

    if (count($msg_erro["msg"]) > 0) {
        $retorno = array("erro" => $msg_erro);
    } else {
        $xmotivo_contato = $_POST["motivo_contato"];

        $nome           = pg_escape_literal($con,utf8_decode(trim($_POST["nome"])));
        $email          = pg_escape_literal($con,trim($_POST["email"]));
        $telefone       = pg_escape_literal($con,trim($_POST["telefone"]));
        $estado         = $_POST["estado"];
        $cidade         = utf8_decode($_POST["cidade"]);
        $motivo_contato = pg_escape_literal($con,utf8_decode($_POST["motivo_contato"]));
        $mensagem       = pg_escape_literal($con,utf8_decode(trim($_POST["mensagem"])));

        $array_abre_atendimento = array("onde_comprar",
                                        "reclamacao_at",
                                        "procon",
                                        "reclamacao_empresa",
                                        "sugestao",
                                        "duvida_produto",
                                        "reclamacao_produto"
                                        );

            if (in_array($xmotivo_contato, $array_abre_atendimento)) {

                try {

                    pg_query($con, "BEGIN");


                    $sql = "INSERT INTO tbl_hd_chamado (
                                            admin,
                                            data,
                                            fabrica_responsavel,
                                            fabrica,
                                            titulo,
                                            status,
                                            atendente,
                                            categoria
                                        ) VALUES (
                                            8179,
                                            CURRENT_TIMESTAMP,
                                            $login_fabrica,
                                            $login_fabrica,
                                            'Atendimento Fale Conosco',
                                            'Aberto',
                                            8179,
                                            $motivo_contato
                                        )RETURNING hd_chamado";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao abrir o atendimento");
                    }

                    $hd_chamado = pg_fetch_result($res, 0, "hd_chamado");

                    $cidade = retira_acentos($cidade);

                    $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER('{$cidade}') AND UPPER(estado) = UPPER('{$estado}')";
                    $res = pg_query($con, $sql);

                    $cidade_id = pg_fetch_result($res, 0, 'cidade');


                    $cep = preg_replace("/\D/", "", $cep);
                    $cpf = preg_replace("/\D/", "", $cpf);
                    $telefone = preg_replace("/\D/","", $telefone);
                    $celular = preg_replace("/\D/","", $celular);

                    $sql = "INSERT INTO tbl_hd_chamado_extra
                                (
                                    hd_chamado        ,
                                    nome              ,
                                    fone              ,
                                    email             ,
                                    cidade            ,
                                    reclamado
                                )
                                VALUES
                                (
                                    $hd_chamado       ,
                                    $nome             ,
                                    $telefone         ,
                                    $email            ,
                                    $cidade_id        ,
                                    $mensagem
                                )";
                    pg_query($con, $sql);

                    $sql = "INSERT INTO tbl_hd_chamado_item (
                        admin,
                        status_item,
                        hd_chamado ,
                        comentario
                    ) VALUES (
                        8179,
                        'Aberto',
                        $hd_chamado,
                        '$mensagem'
                    )";


                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao abrir o atendimento");
                    }

                    $sql = "SELECT descricao
                            FROM tbl_natureza
                            WHERE fabrica = $login_fabrica
                            AND nome = $motivo_contato";
                    $res = pg_query($con, $sql);

                    $descricao_assunto = pg_fetch_result($res, 0, 'descricao');

                    if (count($msg_erro["msg"]) > 0) {
                        $retorno = array("erro" => $msg_erro);
                    }else{
                        $assunto        = 'Atendimento fale conosco - Einhell';
                        $mensagem       = "<strong>Foi aberto o Atendimento: <strong>$hd_chamado</strong> via Fale Conosco.</strong>
                                                   <br /><br />
                                                   Assunto: $descricao_assunto<br />
                                                   Mensagem: ".$_POST["mensagem"];

                        $externalId     = 'smtp@posvenda';
                        $externalEmail  = 'Einhell';

                        $mailTc = new TcComm($externalId);
                        $res = $mailTc->sendMail(
                            "posvendas.ein@einhell.com.br",
                            $assunto,
                            $mensagem,
                            $externalEmail
                        );

                        pg_query($con, "COMMIT");
                        $retorno = array("sucesso" => true, "hd_chamado" => $hd_chamado);
                    }

            } catch (Exception $e) {
                $msg_erro["msg"][] = $e->getMessage();
                $retorno = array("erro" => $msg_erro);
                pg_query($con, "ROLLBACK");
            }
        } else {

            switch ($xmotivo_contato) {
                case "contato_einhell_representacao":
                    $email      = "contato@einhell.com.br";
                    $assunto    = 'Fale conosco - Representação';
                    break;
                case "vagas_ancora_curriculo":
                    $email      = "vagas@einhell.com.br";
                    $assunto    = 'Fale conosco - Currículo';
                    break;
                case "contato_einhell_lancamento":
                    $email      = "contato@einhell.com.br";
                    $assunto    = 'Fale conosco - Lançamento de Produtos';
                    break;
                case "contato_einhell_preco":
                    $email = "contato@einhell.com.br";
                    $assunto    = 'Fale conosco - Preço de Produtos';
                    break;
                case "financeiro_einhell_boleto":
                    $email = "financeiro@einhell.com.br";
                    $assunto    = 'Fale conosco - Boleto';
                    break;
                case "contato_einhell_email":
                    $email = "contato@einhell.com.br";
                    $assunto    = 'Fale conosco - E-mail MkT';
                    break;
                default:
                    $email = "";
                    break;
            }

            $mensagem       = "<strong>$assunto</strong>
                                       <br /><br />
                                       Nome: ".$_POST["nome"]."<br />
                                       Email: ".$_POST["email"]."<br />
                                       Telefone: ".$_POST["telefone"]."<br />
                                       Estado: ".$_POST["estado"]."<br />
                                       Cidade: ".$_POST["cidade"]."<br />
                                       Mensagem: ".$_POST["mensagem"]."
                                       ";

            $externalId     = 'smtp@posvenda';
            $externalEmail  = 'Einhell';

            $mailTc = new TcComm($externalId);
            $res = $mailTc->sendMail(
                $email,
                $assunto,
                $mensagem,
                $externalEmail
            );

            $retorno = array("sucesso" => true);
        }
    }

    exit(json_encode($retorno));
}

if ($_GET["ajax_carrega_cidades"]) {
    $estado = strtoupper(trim($_GET["estado"]));

    if (empty($estado)) {
        $retorno = array("erro" => utf8_encode("Estado não informado"));
    } else {
        $sql = "SELECT DISTINCT nome FROM tbl_cidade WHERE UPPER(estado) = '{$estado}' ORDER BY nome ASC";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            $retorno = array("erro" => "Erro ao carregar cidades");
        } else {
            $retorno = array("cidades" => array());

            while ($cidade = pg_fetch_object($res)) {
                $retorno["cidades"][] = utf8_encode(strtoupper($cidade->nome));
            }
        }
    }

    exit(json_encode($retorno));
}

?>

<!DOCTYPE html />
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
    <meta name="language" content="pt-br" />

    <!-- jQuery -->
    <script type="text/javascript" src="../callcenter/plugins/jquery-1.11.3.min.js" ></script>

    <!-- Bootstrap -->
    <script type="text/javascript" src="../callcenter/plugins/bootstrap/js/bootstrap.min.js" ></script>
    <link rel="stylesheet" type="text/css" href="../callcenter/plugins/bootstrap/css/bootstrap.min.css" />

    <!-- Plugins Adicionais -->
    <script type="text/javascript" src="../../plugins/jquery.mask.js"></script>
    <script type="text/javascript" src="../../plugins/jquery.alphanumeric.js"></script>
    <script type="text/javascript" src="../../plugins/fancyselect/fancySelect.js"></script>
    <script type="text/javascript" src="../../plugins/jquery.form.js"></script>
    <link rel="stylesheet" type="text/css" href="../../plugins/fancyselect/fancySelect.css" />

    <link rel="stylesheet" type="text/css" href="css/fale_conosco.css" />

    <script type="text/javascript">
        $(function(){

            $("#limpar").click(function(){
                $("input, textarea").val("");
                $(".selecione").prop("selected", true);
            });

            $("#telefone").keypress(function() {
                if ($(this).val().match(/^\(\d\d\) 9/i)) {
                    $(this).mask("(00) 00000-0000");
                } else {
                   $(this).mask("(00) 0000-0000");
                }
            });

            $("#numero").numeric();
            $("#hd_chamado").numeric();

            $("#estado").on("change.fs", function() {
                $(this).trigger("change.$");
            });

            $("#estado").change(function() {
                var value = $(this).val();

                if (value.length > 0) {
                    carregaCidades(value);
                } else {
                    $("#cidade").find("option:first").nextAll().remove();
                    $("#cidade").trigger("update");
                }
            });

            var btn;
            $("#form_fale_conosco").ajaxForm({
                complete:function(data){
                    data = $.parseJSON(data.responseText);

                    //data = JSON.parse(data);
                    if (data.erro) {
                        var msg_erro = [];

                        $.each(data.erro.msg, function(key, value) {
                            msg_erro.push(value);
                        });

                        $("#msg_erro").html("<span style='font-weight: bold;' >Desculpe!</span><br />"+msg_erro.join("<br />"));

                        data.erro.campos.forEach(function(input) {
                            $("input[name="+input+"], textarea[name="+input+"], select[name="+input+"]").parents("div.form-group").addClass("has-error");
                        });

                        $("#msg_erro").show();
                    } else {
                        if (typeof data.hd_chamado != "undefined") {
                            $("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.<br />Protocolo: "+data.hd_chamado).show();
                        } else {
                            $("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.").show();
                        }

                        $("input, textarea, select").val("");
                        $("#estado, #cidade, #motivo_contato").trigger("update");
                    }

                    $(document).scrollTop(0);
                    $("#enviar").button("reset");
                }
            });

            $("#enviar").click(function() {
                btn      = $(this);

                $("div.input.erro").removeClass("erro");
                $("#msg_erro").html("").hide();
                $("#msg_sucesso").hide();
                $(btn).button("loading");

                $("#form_fale_conosco").submit();
            });
        });

        function carregaCidades(estado,cidade) {
            var select_cidade = $("#cidade");

            $.ajax({
                url: "fale_conosco.php",
                type: "get",
                data: { ajax_carrega_cidades: true, estado: estado },
                beforeSend: function() {
                    $(select_cidade).find("option:first").nextAll().remove();
                    //$("#cidade_label").append("<span class='loading' >carregando...</span>")
                }
            }).done(function(data) {
                data = JSON.parse(data);
                if (data.erro) {
                    alert(data.erro);
                } else {
                    data.cidades.forEach(function(cidade) {
                        var option = $("<option></option>", {
                            value: cidade,
                            text: cidade
                        });

                        $(select_cidade).append(option);
                    });

                    if(cidade != undefined){
                        var indexCidade = $("#cidade option").removeAttr('selected').filter('[value="'+cidade+'"]').index();
                        $('#cidade option:eq('+indexCidade+')').prop('selected', true).trigger('change');
                    }
                    $("#cidade_label span.loading").remove();
                }
                $(select_cidade).trigger("update");
            });
        }
    </script>
    <style>
        label {
            color: black;
        }

        .campo_obrigatorio {
            color: darkred;
        }
    </style>
</head>
<body style="background-color: #ffffff;">

<div class="container">
    <div id="msg_erro" class="alert alert-danger alert-dismissible" style="display: none;">

    </div>
    <div id="msg_sucesso" class="alert alert-success alert-dismissible" style="display: none;">

    </div>
    <form id="form_fale_conosco" action='fale_conosco.php' enctype="multipart/form-data" method="post">
        <input type="hidden" name="ajax_enviar" value='true' />
        <br />
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="nome" >Nome<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="nome" name="nome" />
        </div>
        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="email" >E-mail<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="email" name="email" />
        </div>

        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="telefone" >Telefone<span class="campo_obrigatorio"> *</span></label>
            <input type="text" class="form-control" id="telefone" class="telefone" name="telefone" />
        </div>

        <div class="form-group col-xs-4 col-sm-4 col-md-4 col-lg-4" >
            <label for="estado" >Estado<span class="campo_obrigatorio"> *</span></label>
            <select class="form-control" id="estado" name="estado" >
                <option value="" class="selecione">Selecione</option>
                <?php
                foreach ($array_estado as $sigla => $nome) {
                    echo "<option value='{$sigla}' >{$nome}</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group col-xs-1 col-sm-1 col-md-1 col-lg-1" ></div>
        <div class="form-group col-xs-7 col-sm-7 col-md-7 col-lg-7" >
            <label id="cidade_label" for="cidade" >Cidade<span class="campo_obrigatorio"> *</span></label>
            <select class="form-control" id="cidade" name="cidade" >
                <option value="" class="selecione">Selecione</option>
            </select>
        </div>

        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="motivo_contato" >Assunto<span class="campo_obrigatorio"> *</span></label>
            <select class="form-control" id="motivo_contato" name="motivo_contato" >

                <option value='' class="selecione">Selecione</option>
                <option value='sugestao'>Elogio</option>
                <option value='duvida_produto'>Revender</option>
                <option value='sugestao'>Prestação de Serviço</option>
                <option value='reclamacao_produto'>Peças e Acessórios</option>
                <option value='reclamacao_at'>Assistência Técnica-reclamação</option>
                <option value='duvida_produto'>Dúvida Técnica</option>
                <option value='onde_comprar'>Onde Comprar</option>

                <option value='contato_einhell_representacao'>Representação</option>
                <option value='vagas_ancora_curriculo'>Currículo</option>
                <option value='contato_einhell_lancamento'>Lançamento de produtos</option>
                <option value='contato_einhell_preco'>Preço de produtos</option>
                <option value='financeiro_einhell_boleto'>Boleto</option>
                <option value='contato_einhell_email'>E-mail MkT </option>

            </select>
        </div>

        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
            <label for="mensagem" >Mensagem</label>
            <textarea class="form-control" name="mensagem" rows="6" ></textarea>
        </div>

        <div class="col-xs-6 col-sm-4 col-sm-offset-8 col-md-4 col-md-offset-8 col-lg-4 col-lg-offset-8" ></div>
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="col-xs-8 col-sm-8 col-md-8 col-lg-8"></div>
            <div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <button style="background-color: #ee4123;color: white;width: 100px;" type="button" id="enviar" class="btn btn-md pull-right" data-loading-text="ENVIANDO..." >Enviar</button>
                </div>
            </div>
        </div>
    </form>
</div>

