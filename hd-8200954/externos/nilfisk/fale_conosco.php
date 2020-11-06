<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';
include '../../class/communicator.class.php';


$login_fabrica = 190;

if ($_serverEnvironment == 'development'){
    $admin      = 12027;//devel
    $atendente  = 12027;//devel
}else{
    $admin      = 12027;//prod
    $atendente  = 12027;//prod
}
$site      = 'www.nilfisk.com';

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


if (isset($_POST["ajax_enviar"])) {
    $atendimento = trim($_POST["atendimento"]);
    
    if (strlen($_POST["atendimento"])) {
        $regras = array(
            "notEmpty" => array(
                "mensagem",
                "cpf"
            )
        );
    } else {
        $regras = array(
            "notEmpty" => array(
                "nome",
                "email",
                "cpf",
                "estado",
                "cidade",
                "celular",
                "cep",
                "endereco",
                "numero",
                "bairro",
                "cidade",
                "motivo_contato",
                "mensagem"
            ),
            "validaEstado" => "estado",
            "validaCidade" => "cidade",
            "validaEmail"  => "email"
        );
    }

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
        $cpf                = pg_escape_literal($con, trim($_POST["cpf"]));
        $cpf                = preg_replace("/\D/", "", $cpf);

        $nome               = utf8_decode(trim($_POST["nome"]));
        $sobrenome          = utf8_decode(trim($_POST["sobrenome"]));

        $nome               = $nome." ".$sobrenome;

        $email              = pg_escape_literal($con, trim($_POST["email"]));
        $telefone           = pg_escape_literal($con, trim($_POST["telefone"]));
        $celular            = pg_escape_literal($con, trim($_POST["celular"]));
        $cep                = pg_escape_literal($con, trim($_POST["cep"]));
        $endereco           = pg_escape_literal($con, utf8_decode(trim($_POST["endereco"])));
        $numero             = pg_escape_literal($con, trim($_POST["numero"]));
        $Complemento        = pg_escape_literal($con, utf8_decode(trim($_POST["complemento"])));
        $bairro             = pg_escape_literal($con, utf8_decode(trim($_POST["bairro"])));
        $estado             = $_POST["estado"];
        $cidade             = utf8_decode($_POST["cidade"]);
        $hd_classificacao   = pg_escape_literal($con,trim($_POST["motivo_contato"]));
        $mensagem           = pg_escape_literal($con,utf8_decode(trim($_POST["mensagem"])));
        
        try {
 
	   $res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
           if ($res_cpf === false) {
	       throw new Exception("CPF/CNPJ Invalido");
           }

           pg_query($con, "BEGIN");
  		$sql_busca_origem = "SELECT 
                                        hd_chamado_origem AS origem_id
                                    FROM  tbl_hd_chamado_origem
                                    WHERE fabrica = {$login_fabrica}
                                      AND UPPER(descricao) = 'FALE CONOSCO'
                                    LIMIT 1";
                $res_busca_origem = pg_query($con, $sql_busca_origem);
                if (pg_num_rows($res_busca_origem) > 0) {
                    $origem_id = pg_fetch_result($res_busca_origem, 0, 'origem_id');
                } else {
                    $origem_id = null;
                }

		$sqlHdO = "SELECT tbl_hd_origem_admin.admin ,tbl_admin.email
                          FROM tbl_hd_origem_admin 
                          JOIN tbl_admin ON tbl_admin.admin = tbl_hd_origem_admin.admin AND tbl_admin.fabrica={$login_fabrica}
                         WHERE tbl_hd_origem_admin.hd_chamado_origem = {$origem_id} 
                           AND tbl_hd_origem_admin.fabrica = {$login_fabrica}";
                $resHdO = pg_query($con, $sqlHdO);
                $admin = null;
		if (pg_num_rows($resHdO) > 0) {

		    for($i = 0; $i < pg_num_rows($resHdO); $i++){
                    	$admin = pg_fetch_result($resHdO, 0, 'admin');
                    	$admin_email[] = pg_fetch_result($resHdO, $i, 'email');
		    }
                }



            if (strlen($atendimento) === 0) {
                $sql = "INSERT INTO tbl_hd_chamado (
                    admin,
                    data,
                    fabrica_responsavel,
                    fabrica,
                    titulo,
                    status,
                    hd_classificacao,
                    atendente,
		    categoria
                ) VALUES (
                    $admin,
                    CURRENT_TIMESTAMP,
                    $login_fabrica,
                    $login_fabrica,
                    'Atendimento Fale Conosco',
                    'Aberto',
                    {$hd_classificacao},
                    $admin,'reclamacao_produto'
                )RETURNING hd_chamado";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao abrir o atendimento #1");
                }

                $hd_chamado = pg_fetch_result($res, 0, "hd_chamado");
                $titulo = "'Abertura de chamado via Fale Conosco - Site $site'";

                $cidade = retira_acentos($cidade);

                $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER('{$cidade}') AND UPPER(estado) = UPPER('{$estado}')";
                $res = pg_query($con, $sql);

                $cidade_id = pg_fetch_result($res, 0, 'cidade');

                $cep = preg_replace("/\D/", "", $cep);
                $telefone = preg_replace("/\D/","", $telefone);
                $celular = preg_replace("/\D/","", $celular);

              
                $sql = "INSERT INTO tbl_hd_chamado_extra (
                    hd_chamado      ,
                    nome            ,
                    fone            ,
                    celular         ,
                    email           ,
                    endereco        ,
                    numero          ,
                    complemento     ,
                    bairro          ,
                    cep             ,
                    cidade          ,
                    reclamado       ,
                    origem          ,
                    consumidor_revenda,
                    cpf
                )
                VALUES
                (
                    $hd_chamado     ,
                    '$nome'         ,
                    '$telefone'     ,
                    '$celular'      ,
                    $email          ,
                    $endereco       ,
                    $numero         ,
                    '$complemento'  ,
                    $bairro         ,
                    '$cep'          ,
                    $cidade_id      ,
                    $mensagem       ,
                    $origem_id         ,
                    'C'             ,
                    $cpf
                )";
                
                $res = pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao abrir o atendimento #2");
                }
            } else {
                $sql = "SELECT
                    thc.hd_chamado,
                    thc.hd_classificacao
                FROM tbl_hd_chamado AS thc
                JOIN tbl_hd_chamado_extra AS thce ON thce.hd_chamado = thc.hd_chamado
                WHERE thc.hd_chamado = $1
                AND thc.fabrica = $2
                AND thce.cpf = $3";

                $res = pg_query_params($con, $sql, [$atendimento, $login_fabrica, $cpf]);
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao interagir no atendimento #1");
                } elseif (pg_num_rows($res) === 0) {
                    throw new Exception("Atendimento vinculado ao CPF/CNPJ ".$cpf." não encontrado.");
                }

                $hd_chamado = pg_fetch_result($res, 0, "hd_chamado");
                $motivo_contato = pg_fetch_result($res, 0, "hd_classificacao");

                $titulo = $mensagem;
            }

            $sql = "INSERT INTO tbl_hd_chamado_item (
                admin,
                status_item,
                hd_chamado ,
                comentario
            ) VALUES (
                $admin,
                'Aberto',
                $hd_chamado,
                $titulo
            )";

            $res = pg_query($con, $sql);
            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao abrir o atendimento #3");
            }

            $sql = "SELECT descricao
            FROM tbl_hd_classificacao
            WHERE fabrica = $login_fabrica
            AND hd_classificacao = $motivo_contato";
            $res = pg_query($con, $sql);

            $descricao_assunto = pg_fetch_result($res, 0, 'descricao');

            if (count($msg_erro["msg"]) > 0) {
                $retorno = array("erro" => $msg_erro);
            }else{
                pg_query($con, "COMMIT");


 		$corpoEmail = "
                <p><b>Nome:</b> {$nome}</p>
                <p><b>Email:</b> {$email}</p>
                <p><b>Telefone:</b> {$telefone} </p>
                <p><b>Celular:</b> {$Celular} </p>
                <p><b>Cidade:</b> {$cidade} - <b>UF:</b> {$estado}</p>
                <p><b>Mensagem:</b> {$mensagem} </p>
            ";



                $mailTc = new TcComm('smtp@posvenda');
                $res = $mailTc->sendMail(
                                            $admin_email,
                                            "Fale Conosco via site Nilfisk - Atendimento N ".$hd_chamado,
                                            $corpoEmail,
                                            'noreply@telecontrol.com.br'
                                        );


                $retorno = array("sucesso" => true, "hd_chamado" => $hd_chamado);
            }

        } catch (Exception $e) {
            $msg_erro["msg"][] = $e->getMessage();
            $retorno = array("erro" => $msg_erro);
            pg_query($con, "ROLLBACK");
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

if ($_GET["ajax_verifica_atedimento"]) {
    $cpf = preg_replace("/\D/", "", trim($_GET["cpf"]));

    $query = "SELECT
        thc.hd_chamado,
        thce.nome,
        thc.fabrica
    FROM tbl_hd_chamado AS thc
    JOIN tbl_hd_chamado_extra thce ON thce.hd_chamado = thc.hd_chamado
    WHERE thce.cpf = $1
    AND thc.fabrica = $2
    AND thc.status = $3
    LIMIT 1";

    $res = pg_query_params($con, $query, [$cpf, $login_fabrica, 'Aberto']);
    if (pg_num_rows($res) === 0) {
        $response = ['message' => 'ok'];
    } else {
        $hd_chamado = pg_fetch_result($res, 0, 'hd_chamado');
        $nome       = pg_fetch_result($res, 0, 'nome');

        $response = [
            'message' => 'ok',
            'hd_chamado' => $hd_chamado,
            'nome' => ucwords(strtolower($nome))
        ];
    }

    exit(json_encode($response));
}

?>

<!DOCTYPE html />
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
    <meta name="language" content="pt-br" />
    <title>Fale Conosco</title>

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


    <script type="text/javascript">
        $(function() {
            $("#cpf").on("blur", function () {
                var value = $(this).val();

                if (value.length > 0)
                    return verificaAtendimento($(this).val());

            });

            $("#cep").on("blur", function(){
                var cep = $(this).val();
                busca_cep(cep);
            });

            $("#limpar").click(function(){
                $("input, textarea").val("");
                $(".selecione").prop("selected", true);
            });


	$("#cpf").focus(function(){
       $(this).unmask();
       $(this).mask("99999999999999");
    });
       
   $("#cpf").blur(function(){
       var el = $(this);
       el.unmask();
       
       if(el.val().length > 11){
           el.mask("99.999.999/9999-99");
       }

       if(el.val().length <= 11){
           el.mask("999.999.999-99");
       }
   });
            $("#telefone").mask("(00) 0000-0000");
            $("#celular").mask("(00) 00000-0000");
            $("#cep").mask("99999-999");

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
                            $("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato e em breve retornaremos.<br />Protocolo: "+data.hd_chamado).show();
                        } else {
                            $("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato e em breve retornaremos.").show();
                        }

                        if ($("input[name=atendimento]").val().length > 0) {
                            $.each($(".toggle-hide"), function (i, e) {
                                if ($(e).hasClass('hide')) {
                                    $(e)
                                    .removeClass('hide')
                                    .fadeIn('fast');
                                } else {
                                    $(e)
                                    .addClass('hide')
                                    .fadeOut('fast')    
                                }
                            })
                        }

                        $("input, textarea, select").val("");
                        $(".msg_welcome").html('Gostaria de esclarecer alguma dúvida? Será um prazer lhe atender!');

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

        function verificaAtendimento(cpf) {
            $.ajax({
                url: location,
                type: 'GET',
                data: {
                    ajax_verifica_atedimento: true,
                    cpf
                }
            })
            .fail(function () { console.log ('erro')})
            .done(function (res) {
                res = JSON.parse(res);

                if (res.hasOwnProperty("hd_chamado")) {
                    $.each($(".toggle-hide"), function (i, e) {
                        if ($(e).hasClass("toggle-hide") && !$(e).hasClass("hide")) {
                            $(e).addClass('hide');
                            $(e).fadeOut('fast');
                        } else {
                            $(e).removeClass('hide');
                        }
                    })

                    var mensagem = 'Olá <b>' + res.nome + '</b>!<br />';
                    mensagem += 'Você já possui um atendimento em andamento. Caso deseje, deixe uma nova mensagem através do campo abaixo.';

                    $(".msg_welcome").html(mensagem);
                    $("#atendimento").val(res.hd_chamado);
                }
            })
        }

        function busca_cep(cep) {
            $("#msg_erro").hide();
            $("#msg_erro").html("");
            var cep = cep;
            var method = "webservice";

            if (cep.length > 0) {

                $.ajax({
                    async: true,
                    url: "../../admin/ajax_cep.php",
                    type: "GET",
                    data: { cep: cep, method: method },
                    beforeSend: function() {
                    },
                    error: function(xhr, status, error) {
                        $("#msg_erro").show("");
                        $("#msg_erro").html("<h4>CEP errado.</h4>");

                    },
                    success: function(data) {
                        results = data.split(";");

                        if (results[0] != "ok") {
                            alert(results[0]);
                        } else {
                            var indexEstado = $("#estado option").removeAttr('selected').filter('[value="'+results[4]+'"]').index();
                            $("#estado option").removeAttr('selected').filter('[value="'+results[4]+'"]').attr('selected', true);
                            $('#estado option:eq('+indexEstado+')').prop('selected', true).trigger('change');

                            $("#bairro").val(results[2]);

                            carregaCidades(results[4],results[3]);

                            if (results[1].length > 0) {
                                $("#endereco").val(results[1]);
                            }
                        }

                        if ($("#endereco").val().length == 0) {
                            $("#endereco").focus();

                        } else {
                            $("#numero").focus();
                        }

                    }
                });
            }
        }

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
    <div class="col-lg-10 col-lg-offset-1">
        <div class="row">
            <div class="col-lg-12">
                <div id="msg_erro" class="alert alert-danger alert-dismissible" style="display: none;"></div>
                <div id="msg_sucesso" class="alert alert-success alert-dismissible" style="display: none;"></div>    
            </div>
        </div>
        <form id="form_fale_conosco" action='fale_conosco.php' enctype="multipart/form-data" method="post">
            <input type="hidden" name="ajax_enviar" value='true' />

            <div class="row">
                <div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
                    <label for="cpf" >CPF/CNPJ:<span class="campo_obrigatorio"> *</span></label>
                    <input type="text" class="form-control" id="cpf" name="cpf" />
                </div>
                <div class="form-group col-xs-12 col-sm-12 col-md-5 col-lg-5 toggle-hide">
                    <label for="nome" >Nome<span class="campo_obrigatorio"> *</span></label>
                    <input type="text" class="form-control" id="nome" name="nome" />
                </div>
                <div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4 toggle-hide">
                    <label for="sobrenome" >Sobrenome<span class="campo_obrigatorio"> *</span></label>
                    <input type="text" class="form-control" id="sobrenome" name="sobrenome" />
                </div>
                <div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4 toggle-hide hide">
                    <label for="cpf" >Protocolo:<span class="campo_obrigatorio">*</span></label>
                    <input type="text" class="form-control" id="atendimento" name="atendimento" readonly=""/>
                </div>
            </div>
            <div class="row toggle-hide">
                <div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-4 toggle-hide">
                    <label for="email" >E-mail<span class="campo_obrigatorio"> *</span></label>
                    <input type="email" class="form-control" id="email" name="email" />
                </div>
                <div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-4 toggle-hide">
                    <label for="telefone" >Telefone</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" />
                </div>
                <div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-4">
                    <label for="celular" >Celular<span class="campo_obrigatorio"> *</span></label>
                    <input type="text" class="form-control" id="celular" name="celular" />
                </div>
            </div>
            <div class="row toggle-hide">
                <div class="form-group col-xs-5 col-sm-5 col-md-3 col-lg-3">
                    <label for="cep" >CEP<span class="campo_obrigatorio"> *</span></label>
                    <input type="text" class="form-control cep" id="cep" name="cep" />
                </div>
                <div class="form-group col-xs-7 col-sm-7 col-md-6 col-lg-6">
                    <label for="endereco" >Endereço<span class="campo_obrigatorio"> *</span></label>
                    <input type="text" class="form-control endereco" id="endereco" name="endereco" />
                </div>
                <div class="form-group col-xs-4 col-sm-4 col-md-1 col-lg-1">
                    <label for="numero" >Nº<span class="campo_obrigatorio"> *</span></label>
                    <input type="text" class="form-control numero" id="numero" name="numero" />
                </div>
                <div class="form-group col-xs-8 col-sm-8 col-md-2 col-lg-2">
                    <label for="complemento" >Complemento</label>
                    <input type="text" class="form-control complemento" id="complemento" name="complemento" />
                </div>
            </div>
            <div class="row toggle-hide">
                <div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4" >
                    <label for="bairro" >Bairro<span class="campo_obrigatorio"> *</span></label>
                    <input type="text" class="form-control bairro" id="bairro" name="bairro" />
                </div>
                <div class="form-group col-xs-9 col-sm-9 col-md-6 col-lg-6" >
                    <label id="cidade_label" for="cidade" >Cidade<span class="campo_obrigatorio"> *</span></label>
                    <select class="form-control" id="cidade" name="cidade" >
                        <option value="" class="selecione">Selecione</option>
                    </select>
                </div>
                <div class="form-group col-xs-3 col-sm-3 col-md-2 col-lg-2" >
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
            </div>
            <div class="row toggle-hide">
                <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                    <label for="motivo_contato" >Assunto<span class="campo_obrigatorio"> *</span></label>
                    <select class="form-control" id="motivo_contato" name="motivo_contato" >

                        <option value='' class="selecione">Selecione</option>
                        <?php
                            $sql = "SELECT * 
                                      FROM tbl_hd_classificacao 
                                     WHERE ativo IS TRUE 
                                       AND fabrica={$login_fabrica}";
                            $res = pg_query($con, $sql);
                            foreach (pg_fetch_all($res) as $key => $row) {
                        ?>
                        <option value='<?php echo $row["hd_classificacao"];?>'><?php echo $row["descricao"];?></option>
                    <?php }?>

                    </select>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                    <label for="mensagem" >Mensagem</label>
                    <textarea class="form-control" name="mensagem" rows="6" ></textarea>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" align="center">
                    <button style="background-color: #056f7d;color: white;width: 100px;" type="button" id="enviar" class="btn btn-md" data-loading-text="ENVIANDO..." >Enviar</button>
                </div>
            </div>
        </form>
    </div>
</div>

