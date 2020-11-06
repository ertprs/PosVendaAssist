<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';
include_once __DIR__ . '/../class/AuditorLog.php';

use Posvenda\DistribuidorSLA;
$oDistribuidorSLA = new DistribuidorSLA();
$oDistribuidorSLA->setFabrica($login_fabrica);


$fabrica_usa_marca         = !in_array($login_fabrica, array(156,191)); // por enquanto, apenas Elgin automação NÃO USA
$cliente_admin_responsavel =  in_array($login_fabrica, array(156,190)); // por enquanto, apenas Elgin automação USA
$admin_abre_preos          = !in_array($login_fabrica, array(156,190)); // por enquanto, apenas Elgin automação USA

if ($develmode === 'debug' and count($_POST))
    pre_echo($_POST, 'Dados do POST');

if (isset($_POST['getAuditorLog'])) {
    $auditorLog = new AuditorLog();
    $clienteAdmin = $_GET['cliente_admin'];
    $response = [];

    try {
        $auditorResponse = $auditorLog->getLog("tbl_cliente_admin", $login_fabrica."*".$clienteAdmin);

        foreach ($auditorResponse as $key => $auditor) {
            $qAdmin = "SELECT nome_completo
                       FROM tbl_admin
                       WHERE fabrica = {$login_fabrica}
                       AND admin = " . $auditor['user'];
            $rAdmin = pg_query($con, $qAdmin);
            $adminName = pg_fetch_result($rAdmin, 0, 'nome_completo');

            $changes = [];
            $keyChanges = array_diff($auditor['content']['antes'][0], $auditor['content']['depois'][0]);
            foreach ($keyChanges as $key => $change) {
                $brDate = \DateTime::createFromFormat("U.u", $auditor['created']);
                $brDate->setTimeZone(new DateTimeZone("America/Sao_Paulo"));
                $changes[] = [
                    $key => [
                        'before' => utf8_encode($auditor['content']['antes'][0][$key]),
                        'after' => utf8_encode($auditor['content']['depois'][0][$key]),
                        'date' => $brDate->format('d/m/Y H:i:s')
                    ]
                ];
            }

            $response[] = [
                'admin' => $auditor['user'],
                'full_name' => utf8_encode($adminName),
                'changes' => $changes
            ];
        }
    } catch (Exception $e) {
        echo $e->getMessage();
        exit;
    }
    
    echo json_encode($response);
    exit;
}

if (isset($_POST["dados_acesso"])) {
    $admin = $_POST["admin"];
    $msg = "";
    $msg_erro["msg"] = array();

    $sql = "SELECT nome_completo, email, login, senha FROM tbl_admin WHERE admin = {$admin} AND fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        $nome_completo = pg_fetch_result($res, 0, "nome_completo");
        $email         = pg_fetch_result($res, 0, "email");
        $login         = pg_fetch_result($res, 0, "login");
        $senha         = pg_fetch_result($res, 0, "senha");

        include "../rotinas/funcoes.php";

        $emailClass = new Log2();

        $emailClass->adicionaTituloEmail("Dados de Acesso ao Sistema Telecontrol - Fricon"); // Titulo
        $emailClass->adicionaEmail($email);

        $mensagem = "Prezado {$nome_completo}, <br /> Segue os dados para acesso do Sistema Telecontrol: <br /> <br />";
        $mensagem .= "Login: <strong>{$login}</strong> <br />";
        $mensagem .= "Senha: <strong>{$senha}</strong> <br /> <br />";
        $mensagem .= "<small>Não responda esse email, pois eviado automaticamente.</small> <br /> Atte. strong>Fricon</strong>";

        $emailClass->adicionaLog($mensagem);

        $msg = ($emailClass->enviaEmails() == "200");

    }else{
        $msg = false;
    }
    exit($msg);
}

if (strlen($_POST["cliente_admin"]) > 0) $cliente_admin  = trim($_POST["cliente_admin"]);
if (strlen($_GET["cliente_admin"]) > 0) $cliente_admin  = trim($_GET["cliente_admin"]);

$btn_acao = $_POST['btn_acao'];

#-------------------- 'Descredenciar' -----------------

if ($btn_acao == 'excluir' and strlen($cliente_admin) > 0 ) {
    $cliente_admin = (int)$_POST['cliente_admin'];
    $sql = "DELETE FROM tbl_cliente_admin WHERE cliente_admin = $cliente_admin AND fabrica = $login_fabrica";
    $res = pg_query($con,$sql);
    header ("Location: $PHP_SELF");
    exit;
}

#-------------------- GRAVAR -----------------

if ($btn_acao == 'gravar') {

    $msg_erro = array();

    $nome                 = trim($_POST['razao_social']);
    $cnpj                 = preg_replace('/\D/', '', trim($_POST['cnpj']));
    $ie                   = preg_replace('/\D/', '', trim($_POST['ie']));
    $codigo               = trim($_POST['codigo']);
    $endereco             = trim($_POST['endereco']);
    $numero               = trim($_POST['numero']);
    $complemento          = trim($_POST['complemento']);
    $cep                  = preg_replace('/\D/', '', trim($_POST['cep']));
    $bairro               = trim($_POST['bairro']);
    $cidade               = trim($_POST['cidade']);
    $estado               = trim($_POST['estado']);
    $contato              = trim($_POST['contato']);
    $fone                 = trim($_POST['fone']);
    $celular              = trim($_POST['celular']);
    $unidade_negocio      = $_POST['unidade_negocio'];
    $unidade_principal    = $_POST['unidade_principal'];
    $email                = trim($_POST['email']);
    $codigo_representante = trim($_POST['codigo_representante']);
    $tipo_cliente         = trim($_POST['tipo_cliente']);

    if (!empty($unidade_negocio)) {
        $parametros_adicionais = array("unidadeNegocio" => $unidade_negocio, "unidadePrincipal" => $unidade_principal);
    }

    if(!empty($tipo_cliente)){
        $parametros_adicionais["tipo_cliente"] = $tipo_cliente;
    }

    $parametros_adicionais = json_encode($parametros_adicionais);

    if($cliente_admin_responsavel && in_array($login_fabrica, [156,190])){

        $login_cliente_admin = $_POST["login_cliente_admin"];
        $senha_cliente_admin = $_POST["senha_cliente_admin"];

    }

    if ($fabrica_usa_marca) {
        $marca  = trim($_POST['marca']);

        if (empty($marca))
            $marca = 0;
    } else
        $marca = 'NULL';

    $abre_os_admin  = (strtolower(trim($_POST['abre_os_admin']) == 't')) ? 'TRUE' : 'FALSE';

    if (strlen($cnpj) != 14) {
        $msg_erro['CNPJ'] .= 'Por Favor digite um CNPJ válido para o Cliente <br>';
    }

    if (strlen($fone)==0) {
        $msg_erro['telefone'] .= 'Por Favor digite o TELEFONE do Cliente <br>';
    }

    if (strlen($codigo)==0) {
        $msg_erro['codigo'] .= 'Por Favor digite o CÓDIGO do Cliente <br>';
    }else{
        if (empty($cliente_admin)) {
            $sql = "SELECT cliente_admin
                      FROM tbl_cliente_admin
					  WHERE tbl_cliente_admin.codigo = '$codigo'
					  AND tbl_cliente_admin.fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            if (pg_num_rows($res) > 0) {
                $msg_erro = "Código inválido ou já é cadastrado";
            }
        }
    }

    if (strlen($contato)==0) {
        $msg_erro['contato'] = "Por Favor digite o CONTATO do Cliente";
    }

    if (strlen($nome)==0) {
        $msg_erro['razao'] = "Por Favor digite a RAZÃO SOCIAL do Cliente";
    }

    if (strlen($endereco)==0) {
        $msg_erro['endereco'] = "Por Favor digite o ENDEREÇO do Cliente";
    }

    if (strlen($numero)==0) {
        $msg_erro['numero'] = "Por Favor digite o NÚMERO do Cliente";
    }

    if (strlen($bairro)==0) {
        $msg_erro['bairro'] = "Por Favor digite o BAIRRO do Cliente";
    }

    if (strlen($cep) != 8) {
        $msg_erro['cep'] = "Por Favor digite o CEP do Cliente";
    }

    if (strlen($cidade)==0) {
        $msg_erro['cidade'] = "Por Favor digite o CIDADE do Cliente";
    }

    if (strlen($estado)==0) {
        $msg_erro['estado'] = "Por Favor digite o ESTADO do Cliente";
    }

    if ($login_fabrica == 52) {
        if (strlen($codigo_representante)==0) {
            $msg_erro['codigo_representante'] = "Por Favor digite o CÓDIGO DO REPRESENTANTE";
        } else {
            if (strlen($codigo_representante)>6) {
                $msg_erro['codigo_representante'] = "Por Favor digite o CÓDIGO DO REPRESENTANTE com no máximo 6 caracteres";
            }
        }
    }

    if (empty($admin_responsavel) and $cliente_admin_responsavel) {
        $msg_erro['admin'] = "Por favor, selecione o ADMIN responsável";
    } else {
        $admin_responsavel = ($cliente_admin_responsavel) ? "'$admin_responsavel'" : 'NULL';
    }

    if (strlen($cnpj) > 0) {
        // HD 37000
        function validaCNPJ($cnpj, $retStr = true) {
            $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);

            if (strlen($cnpj) < 13)
                return $retStr ? 'errado' : false;

            if (strlen($snpj) < 14)
                $cnpj = '0'.$cnpj;

            // Valida primeiro dígito verificador
            for ($i = 0, $j = 5, $soma = 0; $i < 12; $soma += $cnpj{$i++} * $j, $j = ($j == 2) ? 9 : $j - 1);
            $resto = $soma % 11;

            if ($cnpj{12} != ($resto < 2 ? 0 : 11 - $resto))
                return $retStr ? 'errado' : false;

            // Valida segundo dígito verificador
            for ($i = 0, $j = 6, $soma = 0; $i < 13; $soma += $cnpj{$i++} * $j, $j = ($j == 2) ? 9 : $j - 1);
            $resto = $soma % 11;

            if ($cnpj{13} == ($resto < 2 ? 0 : 11 - $resto))
                return $retStr ? 'certo' : true;
            return $retStr ? 'errado' : false;
        }

        $valida_cnpj = ValidaCNPJ($cnpj);

        if ($valida_cnpj == 'errado') {
            if ($login_fabrica == 1) {
                $msg_erro['CNPJ'] = "CNPJ do cliente inválido";
            }
        }
    }

    if($cliente_admin_responsavel && in_array($login_fabrica, [156,190])){

        if(strlen($login_cliente_admin) > 0 && strlen($senha_cliente_admin) == 0){
            $msg_erro['senha_cliente_admin'] = "Por Favor digite a Senha do Cliente";
        }

         if(strlen($login_cliente_admin) == 0 && strlen($senha_cliente_admin) > 0){
            $msg_erro['login_cliente_admin'] = "Por Favor digite o Login do Cliente";
        }

    }

    if (count($msg_erro) == 0) {
        if (strlen($cliente_admin) > 0) {
            // update
            if (in_array($login_fabrica, [52])) {
                $auditorLog = new AuditorLog();
                $auditorLog->retornaDadosTabela("tbl_cliente_admin", ["cliente_admin" => $cliente_admin, "fabrica" => $login_fabrica]);
            }

            $sql = "UPDATE tbl_cliente_admin
                       SET nome        = '$nome',
                           cnpj        = '$cnpj',
                           ie          = '$ie',
                           endereco    = '$endereco',
                           numero      = '$numero',
                           complemento = '$complemento',
                           bairro      = '$bairro',
                           cep         = '$cep',
                           cidade      = '$cidade',
                           estado      = '$estado',
                           contato     = '$contato',
                           marca       = $marca,
                           email       = '$email',
                           fone        = '$fone',
                           celular     = '$celular',
                           codigo      = '$codigo',
                           parametros_adicionais      = '$parametros_adicionais',
                           codigo_representante = '$codigo_representante',
                           admin_responsavel    = $admin_responsavel,
                           abre_os_admin        = $abre_os_admin
                    WHERE cliente_admin = '$cliente_admin'; ";

            $status_admin = ($abre_os_admin=='t') ? 'TRUE' : 'FALSE';
            $sql .= "UPDATE tbl_admin SET ativo = $status_admin WHERE cliente_admin = $cliente_admin AND fabrica = $login_fabrica;";            

        } else {
            #-------------- INSERT ---------------
            $sql = "INSERT INTO tbl_cliente_admin (
                        nome,
                        cnpj,
                        ie,
                        endereco,
                        numero,
                        complemento,
                        bairro,
                        cep,
                        cidade,
                        estado,
                        contato,
                        email,
                        fone,
                        celular,
                        codigo,
                        codigo_representante,
                        abre_os_admin,
                        marca,
                        admin_responsavel,
                        fabrica,
                        parametros_adicionais
                    ) VALUES (
                        '$nome',
                        '$cnpj',
                        '$ie',
                        '$endereco',
                        '$numero',
                        '$complemento',
                        '$bairro',
                        '$cep',
                        '$cidade',
                        '$estado',
                        '$contato',
                        '$email',
                        '$fone',
                        '$celular',
                        '$codigo',
                        '$codigo_representante',
                        '$abre_os_admin',
                        $marca,
                        $admin_responsavel,
                        $login_fabrica,
                        '$parametros_adicionais'
            )";
        }

        //echo nl2br($sql); die;
        $res = pg_query($con,$sql);
        $auditorActionType = explode(" ", $sql)[0];

        if (strtolower($auditorActionType) == "update" AND in_array($login_fabrica, [52])) {
            $auditorLog->retornaDadosTabela()->enviarLog("update", "tbl_cliente_admin", $login_fabrica."*".$cliente_admin);
        } elseif (strtolower($auditorActionType) == "insert" AND in_array($login_fabrica, [52])) {
            $auditorLog = new AuditorLog("insert");
            $auditorLog->retornaDadosTabela(
                "tbl_cliente_admin",
                [
                    "cliente_admin" => $cliente_admin,
                    "fabrica" => $login_fabrica
                ]
            )->enviarLog("insert", "tbl_cliente_admin", $login_fabrica."*".$cliente_admin);
        }

        if(strlen(trim(pg_last_error($con)))>0){
            $msg_erro['erro_banco'][] = pg_last_error($con);
        }

        if (!empty($msg_erro)) {
            if (strpos($msg_erro, 'tbl_cliente_admin_codigo'))
                $msg_erro['erro'] .= "Código inválido para o Cliente Admin";
        } else {
            $msg_success = "Cliente Gravado com Sucesso!";
            
            if (strlen($cliente_admin)==0) {
                $sql = "SELECT CURRVAL ('seq_cliente_admin')";
                $res = pg_query($con,$sql);
            
                if(strlen(trim(pg_last_error($con)))>0){
                    $msg_erro['erro_banco'][] = pg_last_error($con);
                } 
            
                $cliente_admin = pg_fetch_result($res,0,0);
            }
        }

        /* Login e Senha - Cliente Admin */
        if($cliente_admin_responsavel && in_array($login_fabrica, [156,190]) && strlen($login_cliente_admin) > 0 && strlen($senha_cliente_admin) > 0 && strlen($cliente_admin) > 0 && strlen($msg_erro) == 0){

            $sql_admin = "SELECT admin FROM tbl_admin WHERE cliente_admin = {$cliente_admin} AND fabrica = {$login_fabrica}";
            $res_admin = pg_query($con, $sql_admin);

            if(pg_num_rows($res_admin) == 0){

                $sql_admin = "INSERT INTO tbl_admin 
                                (
                                    login, 
                                    senha,
                                    nome_completo, 
                                    fone,
                                    email,
                                    cliente_admin,
                                    fabrica,
                                    ativo,
                                    cliente_admin_master,
                                    privilegios
                                ) VALUES 
                                (
                                    '$login_cliente_admin',
                                    '$senha_cliente_admin',
                                    '$nome',
                                    '$fone',
                                    '$email',
                                    $cliente_admin,
                                    $login_fabrica,
                                    't',
                                    't',
                                    '*'
                                )"; 

            }else{

                $sql_admin = "UPDATE tbl_admin SET 
                                login = '$login_cliente_admin',
                                senha = '$senha_cliente_admin',
                                nome_completo = '$nome',
                                fone = '$fone',
                                email = '$email',
                                ativo = 't',
                                cliente_admin_master = 't',
                                privilegios = '*' 
                            WHERE cliente_admin = {$cliente_admin} 
                                AND fabrica = {$login_fabrica}";

            }

            $res_admin = pg_query($con, $sql_admin);

            if(strlen(pg_last_error($con)) > 0){
                $msg_erro['erro_banco'][] = pg_last_error($con);
            }

        }

    } 
}
#-------------------- Pesquisa Revenda -----------------
if (strlen($cliente_admin) > 0 and strlen ($msg_erro) == 0 ) {
    $sql = "SELECT  tbl_cliente_admin.cliente_admin,
                    tbl_cliente_admin.nome,
                    tbl_cliente_admin.endereco,
                    tbl_cliente_admin.bairro,
                    tbl_cliente_admin.complemento,
                    tbl_cliente_admin.numero,
                    tbl_cliente_admin.cep,
                    tbl_cliente_admin.cnpj,
                    tbl_cliente_admin.fone,
                    tbl_cliente_admin.contato,
                    tbl_cliente_admin.email,
                    tbl_cliente_admin.ie,
                    tbl_cliente_admin.marca,
                    tbl_cliente_admin.cidade,
                    tbl_cliente_admin.codigo,
                    tbl_cliente_admin.codigo_representante,
                    tbl_cliente_admin.abre_os_admin,
                    tbl_cliente_admin.admin_responsavel,
                    tbl_cliente_admin.parametros_adicionais,
                    tbl_cliente_admin.estado
            FROM    tbl_cliente_admin
            WHERE   tbl_cliente_admin.cliente_admin = $cliente_admin ";
    $res = @pg_exec ($con,$sql);

    if (@pg_num_rows($res) > 0) {
        $nome                  = trim(pg_fetch_result($res,0,'nome'));
        $cnpj                  = trim(pg_fetch_result($res,0,'cnpj'));
        $ie                    = trim(pg_fetch_result($res,0,'ie'));
        $endereco              = trim(pg_fetch_result($res,0,'endereco'));
        $numero                = trim(pg_fetch_result($res,0,'numero'));
        $complemento           = trim(pg_fetch_result($res,0,'complemento'));
        $bairro                = trim(pg_fetch_result($res,0,'bairro'));
        $cep                   = trim(pg_fetch_result($res,0,'cep'));
        $cidade                = trim(pg_fetch_result($res,0,'cidade'));
        $estado                = trim(pg_fetch_result($res,0,'estado'));
        $email                 = trim(pg_fetch_result($res,0,'email'));
        $fone                  = trim(pg_fetch_result($res,0,'fone'));
        $contato               = trim(pg_fetch_result($res,0,'contato'));
        $codigo                = trim(pg_fetch_result($res,0,'codigo'));
        $marca                 = trim(pg_fetch_result($res,0,'marca'));
        $abre_os_admin         = trim(pg_fetch_result($res,0,'abre_os_admin'));
        $admin_responsavel     = trim(pg_fetch_result($res,0,'admin_responsavel'));
        $codigo_representante  = trim(pg_fetch_result($res,0,'codigo_representante'));
        $parametros_adicionais = json_decode(pg_fetch_result($res,0,'parametros_adicionais'), 1);

        if ($login_fabrica == 158 && isset($parametros_adicionais["unidadeNegocio"])) {
            $unidade_negocio   = $parametros_adicionais["unidadeNegocio"];
            $unidade_principal = $parametros_adicionais["unidadePrincipal"];
        }

        if(isset($parametros_adicionais["tipo_cliente"])){
            $tipo_cliente = $parametros_adicionais["tipo_cliente"];
        }

        if($cliente_admin_responsavel && in_array($login_fabrica, [156,190])){

            $sql_cliente_admin = "SELECT login, senha FROM tbl_admin WHERE cliente_admin = {$cliente_admin} AND fabrica = {$login_fabrica}";
            $res_cliente_admin = pg_query($con, $sql_cliente_admin);

            if(pg_num_rows($res_cliente_admin)){
                $login_cliente_admin = pg_fetch_result($res_cliente_admin, 0, "login");
                $senha_cliente_admin = pg_fetch_result($res_cliente_admin, 0, "senha");
            }  

        }

    }
}

//if (!empty($msg_erro))
  //  $msg_erro = explode('<br>', $msg_erro);

$visual_black = "manutencao-admin";

$title     = "Cadastro de Clientes Admin";
$cabecalho = "Cadastro de Clientes Admin";

if (isset($_GET['cliente_admin']) or count($_POST) > 2)
    $cabecalho = 'Alteração de Cliente Admin';

$layout_menu = "cadastro";
include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "shadowbox",
    "mask",
    "dataTable",
    "alphanumeric",
    "font_awesome"
);

include("plugin_loader.php");

// Por algum motivo, no bootstrap personalizado, "sumiu" esta classe...
?>
<style>
.remover_unidade_negocio {
    height: 15px;
    width: 42px;
    font-size: 10px;
    font-weight: bold;
    line-height: 4px;
    display: inline-block;
    text-align: center;
    padding: 0px;
    margin-left: 10px;
    cursor: pointer;
    color: rgb(255, 255, 255);
    background-color: rgb(195, 0, 0);
    border-color: rgb(195, 0, 0);
    padding-top: 5px;
}
.text-center {
  text-align:center;
}
</style>
<script type='text/javascript'>
    $(function() {
        Shadowbox.init();

        <?php if ($cliente_admin_responsavel): ?>
        $("#fone").mask("(99)9999-9999");$("#celular").mask("(99)9999-9999");
        <?php endif; ?>

        $("#cnpj").mask("99.999.999/9999-99");
        $("#cep").mask("99.999-999");
        // $("span[rel=lupa]").click(function () {
        //  $.lupa($(this));
        // });

        $("#lupa-nome").click(function() {
            fnc_revenda_pesquisa(document.frm_revenda.razao_social,document.frm_revenda.cnpj,'nome');
        });

        $("#lupa-cnpj").click(function() {
            fnc_revenda_pesquisa(document.frm_revenda.razao_social,document.frm_revenda.cnpj,'cnpj');
        });

        $("button.btn").click(function(evt) {

            if ($(this).attr('readonly') !== undefined) {
                return false;
            }

            $(".btn").attr('readonly', 'readonly');

            if ($(this).attr('id') == 'btn_excluir') {
                if (confirm('Deseja realmente EXCLUIR este cliente?') == false) {
                    $(".btn").removeAttr('readonly');
                    return false;
                }
            }
        });


        $("#cep").blur(function(){
            cep = $("#cep").val();
            busca_cep(cep, '', 'webservice');
        });

    });

    function retiraAcentos(palavra){
        var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
        var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
        var newPalavra = "";

        for(i = 0; i < palavra.length; i++) {
            if (com_acento.search(palavra.substr(i, 1)) >= 0) {
                newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
            } else {
                newPalavra += palavra.substr(i, 1);
            }
        }

        return newPalavra.toUpperCase();
    }


    function busca_cep(cep, consumidor_revenda, method) {
        if (cep.length > 0) {
            var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

            if (typeof method == "undefined" || method.length == 0) {
                            method = "webservice";

                            $.ajaxSetup({
                                    timeout: 3000
                            });
                    } else {
                            $.ajaxSetup({
                                    timeout: 5000
                            });
                    }

            $.ajax({
                async: true,
                url: "ajax_cep.php",
                type: "GET",
                data: { cep: cep, method: method },
                beforeSend: function() {
                    $("#estado").next("img").remove();
                                    $("#cidade").next("img").remove();
                                    $("#bairro").next("img").remove();
                                    $("#endereco").next("img").remove();

                    $("#estado").hide().after(img.clone());
                    $("#cidade").hide().after(img.clone());
                    $("#bairro").hide().after(img.clone());
                    $("#endereco").hide().after(img.clone());
                },
                error: function(xhr, status, error) {
                                busca_cep(cep, consumidor_revenda, "database");
                        },               
                
                success: function(data) {
                    results = data.split(";");

                    if (results[0] != "ok") {
                        alert(results[0]);
                        $("#cidade").show().next().remove();
                    } else {
                        $("#estado").val(results[4]);

                        //busca_cidade(results[4], consumidor_revenda);
                        results[3] = results[3].replace(/[()]/g, '');

                        $("#cidade").val(retiraAcentos(results[3]).toUpperCase());

                        if (results[2].length > 0) {
                            $("#bairro").val(results[2]);
                        }

                        if (results[1].length > 0) {
                            $("#endereco").val(results[1]);
                        }
                    }

                    $("#estado").show().next().remove();
                    $("#bairro").show().next().remove();
                    $("#endereco").show().next().remove();
                    $("#cidade").show().next().remove();

                    if ($("#bairro").val().length == 0) {
                        $("#bairro").focus();
                    } else if ($("#endereco").val().length == 0) {
                        $("#endereco").focus();
                    } else if ($("#numero").val().length == 0) {
                        $("#numero").focus();
                    }


                    
                    $.ajaxSetup({
                        timeout: 0
                    });
                }
            });
        }
    }





    function fnc_revenda_pesquisa (campo, campo2, tipo) {
        if (tipo == "nome" ) {
            var xcampo = campo;
        }

        if (tipo == "cnpj" ) {
            var xcampo = campo2;
        }

        if (xcampo.value != "") {
            var url = "";
            url = "cliente_admin_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
            janela.retorno = document.location.pathname;
            janela.nome = campo;
            janela.cnpj = campo2;
            janela.focus();
        }
    }

    function enviar_dados_acesso(admin) {

        if(admin != "") {

            $.ajax({
                url: document.location.pathname,
                type: "post",
                data: {
                    admin: admin,
                    dados_acesso: true
                },
                beforeSend: function(){
                    $("#enviar_dados_acesso_"+admin).text("enviando, por favor aguarde...");
                },
                complete: function(data){

                    $("#enviar_dados_acesso_"+admin).text("Enviar Dados de Acesso");

                    data = data.responseText;

                    if(data == true){
                        alert("Dados enviados para o Admin-Cliente com Sucesso");
                    }else{
                        alert("Erro ao enviar os dados para o Admin-Cliente");
                    }

                }
            });

        } else {
            alert("Admin não selecionado!");
        }
    }
</script>

<?php if ($msg) { ?>
    <div class="alert alert-success">
        <h4>Cliente <?php echo $msg; ?> com Sucesso</h4>
    </div>
<?php }

if($msg_success) { ?>
    <div class="alert alert-success">
        <h4><?=$msg_success?></h4>
    </div>
<?php
}

if (count($msg_erro)>0) { ?>
    <div class="alert alert-error">
        <?php 

        if(count($msg_erro['erro_banco']) > 0){   
            echo implode("<br />", $msg_erro['erro_banco']);
        } 

        if(count($msg_erro) > 0){
            echo implode("<br />", $msg_erro);
        }

        ?>
        </h4>
    </div>
<?php }

if ($fabrica_usa_marca or $cliente_admin_responsavel) {

    if ($fabrica_usa_marca)
        $sel_marcas = array2select(
            'marca', 'marca',
            pg_fetch_pairs($con,"SELECT marca, nome FROM tbl_marca WHERE fabrica = $login_fabrica ORDER BY nome"),
            $marca,
            ' class="span12"', 'ESCOLHA', true
        );

    if ($cliente_admin_responsavel)
        $select_admins = array2select(
            'admin_responsavel', 'admin_responsavel',
            pg_fetch_pairs($con,"SELECT admin, nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica AND ativo AND cliente_admin IS NULL"),
            $admin_responsavel,
            ' class="span12"', 'ESCOLHA', true
        );
}

?>
<div class="alert alert-warning">
    <p>
        <br />
        Para incluir um novo cliente, preencha somente seu CNPJ e clique em gravar.
        <br />
        Faremos uma pesquisa para verificar se o cliente já está cadastrada em nosso banco de dados.
    </p>
</div>
<?php if (in_array($login_fabrica, [52])) { ?>
<style type="text/css">
    #modal-log-alteracoes td {
        text-align:center;
        vertical-align:middle;
    }
</style>

<div class="row-fluid" style="margin:-10px 0px -20px 0px">
    <div class="span8"></div>
    <div class="span4" style="text-align:right;">
        <a class="btn btn-small toggle-log-modal">Visualizar log de alterações</a>
    </div>
</div>

<!-- modal -->
<div class="modal hide fade modal-lg" id="modal-log-alteracoes" tabindex="-1" role="dialog" style="width:60%;margin-left:-30%">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4>Log de Alterações</h4>
    </div>
    <div class="modal-body">
        <div class="loading" style="text-align:center;">
            <span style="font-weight:bold;">Carregando</span>
            <i class="fa-spinner fa fa-spin"></i>
        </div>
        <div class="auditor-logs row-fluid">
            <table class="table table-bordered" style="display:none">
                <thead style="width:inherit;text-align:center;background-color:#596D9B;color:#FFF;">
                    <tr>
                        <th rowspan="2" colspan="1" style="vertical-align:middle">Admin</th>
                        <th colspan="4">Alterações</th>
                    </tr>
                    <tr>
                        <th>Campo</th>
                        <th>De</th>
                        <th>Para</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody style="width:inherit;" class="auditor-content">
                    
                </tbody>
                <tfoot style="width:inherit"></tfoot>
            </table>
        </div>
    </div>
    <div class="modal-footer">
        <a class="btn" data-dismiss="modal" aria-hidden="true">Fechar</a>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        $(".toggle-log-modal").on("click", function () {
            let clienteAdmin = "<?= $_GET['cliente_admin'] ?>";
            if (clienteAdmin.length == 0)
                return alert('É necessário selecionar um cliente admin!');

            let modal = $("#modal-log-alteracoes");
            
            let modalBody = $(modal).find(".modal-body");
            $(modalBody).find(".loading").fadeIn(500);
            
            let tableBody = $(modalBody).find(".auditor-content");
            $(tableBody).html("");

            $.ajax({
                url: window.location,
                type: 'POST',
                async: true,
                data: {
                    getAuditorLog: true,
                }
            }).fail(function (response) {
                return alert("Falha ao obter dados!");
            }).done(function (response) {
                $(modalBody).find(".loading").fadeOut(500, function () {
                    $(modalBody).find(".table").fadeIn(1000);
                });

                try {
                    response = JSON.parse(response);
                } catch (err) {
                    return alert("Falha ao obter dados!");
                }
                console.log(response);
                $.each(response, function (index, element) {
                    // nome do admin
                    let rowspan = element.changes.length;

                    let mainLine = $("<tr></tr>");
                    let mainCol = $("<td></td>", {
                        attr: {
                            "rowspan": rowspan
                        },
                        css: {
                            "font-weight": "bold",
                            "text-align": "center",
                            "vertical-align": "middle"
                        },
                        text: element.full_name
                    });

                    let mainChangeName = Object.keys(element.changes[0]);
                    let mainChange = $("<td></td>", {
                        css: {
                            "text-transform": "capitalize",
                            "font-weight": "bold",
                        },
                        text: mainChangeName[0].split("_").join(" ")
                    });

                    let beforeCol = $("<td></td>", {
                        text: element.changes[0][mainChangeName].before,
                    });
                    let afterCol = $("<td></td>", {
                        text: element.changes[0][mainChangeName].after,
                    });
                    let dateCol = $("<td></td>", {
                        attr: {
                            rowspan: rowspan
                        },
                        text: element.changes[0][mainChangeName].date,
                    })

                    $(mainLine).prepend(dateCol);
                    $(mainLine).prepend(afterCol);
                    $(mainLine).prepend(beforeCol);
                    $(mainLine).prepend(mainChange);
                    $(mainLine).prepend(mainCol);
                    $(tableBody).append(mainLine);

                    for (i = 1; i < element.changes.length; i++) {
                        let changeName = Object.keys(element.changes[i]);

                        let row = $("<tr></tr>");
                        let colField = $("<td></td>", {
                            css: {
                                "text-transform": "capitalize",
                                "font-weight": "bold"
                            },
                            text: changeName[0].split("_").join(" ")
                        });
                        
                        let beforeField = $("<td></td>", {
                            text: element.changes[i][changeName].before
                        });

                        let afterField = $("<td></td>", {
                            text: element.changes[i][changeName].after
                        });

                        $(row).prepend(afterField);
                        $(row).prepend(beforeField);
                        $(row).prepend(colField);
                        $(tableBody).append(row);
                    }
                });
            });

            $(modal).modal();
        });
    });
</script>
<? } ?>
<form name="frm_revenda" method="POST" class="form-search form-inline tc_formulario">
    <div class='titulo_tabela '><?=$cabecalho?></div>
    <div class="offset1 span9 text-info">
        
    </div>
    <input type="hidden" name="cliente_admin" value="<? echo $cliente_admin ?>">
    <p>&nbsp;</p>
    <!--<fieldset>
        <legend class="titulo_tabela">Informações cadastrais</legend> -->
        <div class="row-fluid">
            <div class="offset1 span4">
                <div class="control-group <?php echo (strlen($msg_erro['razao'])>0)? "error": "" ?>">
                    <label for="razao_social">Razão Social</label>
                    <div class="input-append">
                        <input id="razao_social" type="text" value="<?=$nome?>" class="span12" name="razao_social" maxlength="60" />
                        <span class="add-on" id="lupa-nome"><i class="icon-search"></i></span>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="control-group <?php echo (strlen($msg_erro['CNPJ'])>0)? "error": "" ?>">
                    <label for="cnpj">CNPJ</label>
                    <div class="input-append">
                        <input id="cnpj" type="text" class="span11" value="<?=$cnpj?>" name="cnpj" maxlength="18" />
                        <span class="add-on" id="lupa-cnpj"><i class="icon-search"></i></span>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="control-group">
                    <label for="ie">Inscrição Estadual</label>
                    <input id="ie" type="text" class="span12" name="ie" value="<?=$ie?>" />
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <div class="row-fluid">
            <div class="offset1 span2">
                <div class="control-group <?php echo (strlen($msg_erro['codigo'])>0)? "error": "" ?>">
                    <label for="codigo">Código</label>
                    <input id="codigo" type="text" class="span12" value="<?=$codigo?>" name="codigo">
                </div>
            </div>
            <div class="span2">
                <div class="control-group <?php echo (strlen($msg_erro['codigo_representante'])>0)? "error": "" ?>">
                    <label for="codigo_representante">Cód. Representante</label>
                    <input id="codigo_representante" type="text" class="span12" maxlength="50" value="<?=$codigo_representante?>" name="codigo_representante">
                </div>
            </div>
        <?php if ($fabrica_usa_marca): ?>
            <div class="span2">
                <div class="control-group">
                    <label for="marca">Marca</label>
                    <?=$sel_marcas?>
                </div>
            </div>
        <?php else: ?>
            <div class="span3"></div>
        <?php endif; ?>
        <?php if ($admin_abre_preos): ?>
            <div class="span3">
                <div class="control-group">
                    <label class="span10">Abre Pré-OS?</label>
                    <label class="control-label" for="preos-sim"> <input id="preos-sim" type="radio" <?=($abre_os_admin=='t') ? 'checked' : "" ?> value="t" name="abre_os_admin"> Sim </label>
                    <label class="control-label" for="preos-nao"> <input id="preos-nao" type="radio" <?=($abre_os_admin=='f') ? 'checked': ""?> value="f" name="abre_os_admin"> Não </label>
                </div>
            </div>
        <?php else: ?>
            <div class="span3"><input type="hidden" name="abre_os_admin" value="f" /></div>
        <?php endif; ?>
        </div>
    <!--</fieldset> -->
    
   <!-- <fieldset>
        <legend class="titulo_tabela">Endereço e Contato</legend> -->
        <div class="row-fluid">
            <div class="offset1 span2">
                <div class="control-group <?php echo (strlen($msg_erro['contato'])>0)? "error": "" ?>">
                    <label for="contato">Contato</label>
                    <input id="contato" type="text" class="span12" maxlength="30" value="<?=$contato?>" name="contato">
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label for="email">E-mail</label>
                    <input id="email" type="text" class="span12" value="<?=$email?>" name="email">
                </div>
            </div>
            <div class="span3">
                <div class="control-group">
                    <label for="tipo_cliente">Tipo de Cliente</label>
                    <select id="tipo_cliente" name="tipo_cliente">
                        <option value="">Selecione</option>
                        <option value="corporativo"  <?php echo $tipo_cliente == "corporativo" ? "selected=selected": ""; ?> >Corporativo</option>
                        <option value="linha_branca" <?php echo $tipo_cliente == "linha_branca" ? "selected=selected": ""; ?> >Linha Branca</option>
                    </select>
                </div>
            </div>
            <?php if ($login_fabrica == 158) { if (!is_array($unidade_negocio)) {
                    $unidade_negocio = str_split($unidade_negocio, 4);
                }
            ?>
            </div>
             <div class="row-fluid">
                <div class="span1"></div>
                <div class="span4">
                    <div class="control-group">
                        <label>Unidade de Negócio</label>
                        <select  name="unidade_negocio_sel"  class="span8" id="unidade_negocio_sel" style='width:100%;padding-bottom:2px;min-width:150px;margin:5px;' size='4'>
                            <?php                             
                                $distribuidores_disponiveis = $oDistribuidorSLA->SelectUnidadeNegocioNotIn();

                                $unidadesMinasGerais = \Posvenda\Regras::getUnidades("unidadesMinasGerais", $login_fabrica);

                                foreach ($distribuidores_disponiveis as $unidadeNegocio) {
                                    if (in_array($unidadeNegocio["unidade_negocio"], $unidadesMinasGerais)) {
                                        unset($unidadeNegocio["unidade_negocio"]);
                                        continue;
                                    }
                                    $unidade_negocio_agrupado[$unidadeNegocio["unidade_negocio"]] = $unidadeNegocio["cidade"];
                                }
                                foreach ($unidade_negocio_agrupado as $unidade => $descricaoUnidade) {
                                    if (in_array($unidade, $unidade_negocio)) {
                                        continue;
                                    }
                                    //$selected = ($unidade == $unidade_negocio) ? 'selected' : '';
                                    echo '<option '.$selected.' value="'.$unidade.'">'.$descricaoUnidade.'</option>';
                                }
                                
                            ?>
                        </select>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <span style="text-align:left;display:inline-block;">
                        Selecionados: <span style="color:#FF0000">(marque a Unidade principal)</span><br />
                        <div id="distribuidores_selected_display" style="min-width:150px;height:100px;margin:5px;background-color:#FFFFFF;border:1px solid #D6D6D6;overflow-y:auto;">
                            <?
                            foreach ($unidade_negocio_agrupado as $unidade => $descricaoUnidade) {
                                if (!in_array($unidade, $unidade_negocio)) {
                                    continue;
                                }
                                if ($unidade_principal == $unidade) {
                                    $checkedUnidade = "checked";
                                } else if ($unidade_principal == null && count($unidade_negocio) == 1) {
                                    $checkedUnidade = "checked";
                                } else {
                                    $checkedUnidade = "";
                                }
                                ?>
                                <div id="unidade_selecionada_<?= $unidade; ?>" style='width:100%;padding-bottom:2px;'>
                                    <input type="radio" name="unidade_principal" <?= $checkedUnidade;?> value="<?= $unidade; ?>" />
                                    <?= $descricaoUnidade; ?>
                                    <button type='button' class='remover_unidade_negocio' title='Remover Unidade de Negócio' rel="<?= $unidade; ?>" style="float:right">X</button>
                                </div>
                            <? } ?>
                        </div>
                    </span>
                    <?php
                    ?>
                    <select multiple="multiple" id="unidade_negocio" name="unidade_negocio[]" style="display:none;">
                        <? foreach ($unidade_negocio_agrupado as $unidade => $descricaoUnidade) {
                            if (!in_array($unidade, $unidade_negocio)) {
                                continue;
                            }?>
                            <option value="<?= $unidade;?>" class="option-selectable" selected><?= $descricaoUnidade; ?></option>
                        <? } ?>
                    </select>
                    </div>
                </div>
                <script>
                    $(function() {
                        $(document).on("click", "#unidade_negocio_sel", function() {
                            var distribuidor = $(this).val();
                            var option_clone = $(this).find("option:selected");
                            var unidade_negocio = $(this).find("option:selected").text();
                            $("#distribuidores_selected_display").append(
                                "<div id='unidade_selecionada_"+distribuidor+"' style='width:100%;padding-bottom:2px;'>\
                                    <input type='radio' name='unidade_principal' value='"+distribuidor+"' />\
                                    "+unidade_negocio+"\
                                    <button type='button' class='remover_unidade_negocio' title='Remover Unidade de Negócio' rel='"+distribuidor+"' style='float:right'>X</button>\
                                </div>"
                            );
                            $(option_clone).prop({ selected: true }).addClass("option-selectable");
                            $("#unidade_negocio").append(option_clone);
                            $(this).find("option:selected").remove();
                        });
                        $(document).on("click", ".remover_unidade_negocio", function() {
                            var distribuidor = $(this).attr('rel');
                            var principal = $("input[name=unidade_principal]:checked").val();
                            var option_clone = $("#distribuidores_selected option[value="+distribuidor+"]").clone();
                            if (distribuidor == principal) {
                                alert("Unidade de negócio principal, desmarque para remover");
                                return false;
                            }
                            $("#unidade_negocio_sel").append(option_clone);
                            $("#unidade_selecionada_"+distribuidor).remove();
                            $("#unidade_negocio option[value="+distribuidor+"]").remove();
                        });
                    });
                </script>
            <?php } ?>
        <?php if ($cliente_admin_responsavel): ?>
            <div class="span4">
                <div class="control-group">
                    <label for="admin_responsavel">Admin Responsável</label>
                    <?=$select_admins?>
                </div>
            </div>
        <?php else: ?>
            <div class="span3"></div>
        <?php endif; ?>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span3">
                <div class="control-group <?php echo (strlen($msg_erro['endereco'])>0)? "error": "" ?>">
                    <label for="endereco">Endereço</label>
                    <input class="span12" type="text" id="endereco" name="endereco" value="<?=$endereco?>">
                </div>
            </div>
            <div class="span1">
                <div class="control-group <?php echo (strlen($msg_erro['numero'])>0)? "error": "" ?>">
                    <label for="numero">Número</label>
                    <input class="span12" id="numero" name="numero" type="text" value="<?=$numero?>">
                </div>
            </div>
            <div class="span2">
                <div class="control-group">
                    <label for="complemento">Complemento</label>
                    <input class="span12" id="complemento" name="complemento" type="text" value="<?=$complemento?>">
                </div>
            </div>
            <div class="span4">
                <div class="control-group <?php echo (strlen($msg_erro['bairro'])>0)? "error": "" ?>">
                    <label for="bairro">Bairro</label>
                    <input class="span12" id="bairro" name="bairro" maxlength="20" type="text" value="<?=$bairro?>">
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span2">
                <div class="control-group <?php echo (strlen($msg_erro['cep'])>0)? "error": "" ?>">
                    <label for="cep">CEP</label>
                    <input class="span12" type="text" id="cep" name="cep" value="<?=$cep?>">
                </div>
            </div>
            <div class="span4">
                <div class="control-group <?php echo (strlen($msg_erro['cidade'])>0)? "error": "" ?>">
                    <label for="cidade">Cidade</label>
                    <input class="span12" id="cidade" name="cidade" type="text" value="<?=$cidade?>">
                </div>
            </div>
            <div class="span4">
                <div class="control-group <?php echo (strlen($msg_erro['estado'])>0)? "error": "" ?>">
                    <label for="estado">Estado</label>
<?
            echo array2select(
                'estado', 'estado',
                $estados,
                $estado,
                "class='span12'", 'ESCOLHA', true
            );
?>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span3">
                <div class="control-group <?php echo (strlen($msg_erro['telefone'])>0)? "error": "" ?>">
                    <label for="fone">Telefone</label>
                    <input class="span12" type="text" id="fone" name="fone" value="<?=$fone?>">
                </div>
            </div>
            <div class="span3">
                <div class="control-group">
                    <label for="celular">Celular</label>
                    <input class="span12" id="celular" name="celular" type="text" value="<?=$celular?>">
                </div>
            </div>
            <div class="span4"> </div>
        </div>

        <?php

        if($cliente_admin_responsavel && in_array($login_fabrica, [156,190])){

            ?>

            <br />
            <strong>Dados de Autenticação</strong>

            <div class="row-fluid">
                <div class="span3"></div>
                <div class="span3">
                    <div class="control-group <?php echo (strlen($msg_erro['login_cliente_admin'])>0)? "error": "" ?>">
                        <label for="login_cliente_admin">Login</label>
                        <input class="span12" type="text" id="login_cliente_admin" name="login_cliente_admin" value="<?=$login_cliente_admin?>">
                    </div>
                </div>
                <div class="span3">
                    <div class="control-group <?php echo (strlen($msg_erro['senha_cliente_admin'])>0)? "error": "" ?>">
                        <label for="senha_cliente_admin">Senha</label>
                        <input class="span12" id="senha_cliente_admin" name="senha_cliente_admin" type="password" value="<?=$senha_cliente_admin?>">
                    </div>
                </div>
                <div class="span4"> </div>
            </div>

            <?php

        }

        ?>

    <!--</fieldset>-->
    <div class="row"></div>
    <div class="row-fluid">
        <div class="span12 text-center">
            <br />
            <button id="btn_gravar"  class="btn btn-primary" type="submit" name="btn_acao" value="gravar">Gravar</button>
            <span class="inptc5">&nbsp;</span>
            <button id="btn_excluir" class="btn btn-danger"  type="submit" name="btn_acao" value="excluir">Excluir</button>
            <span class="inptc5">&nbsp;</span>
            <a href="<?=$_SERVER['PHP_SELF']?>" id="btn_reset" class="btn btn-warning" name="btn_reset" value="limpar">Limpar</a>
            <p>&nbsp;<p>
        </div>
    </div>
</form>
<?php if (!isset($_GET['listar'])): ?>
    <div class="row-fluid">
        <div class="span12 text-center">
            <br />
            <a href='<?=$_SERVER['PHP_SELF']?>?listar=todos' class="btn btn-info">Clique aqui para listar os Clientes já Cadastrados</a>
            <p>&nbsp;<p>
        </div>
        <div class="span3"></div>
    </div>
<?php endif; ?>
<p>

<?
if ($_GET['listar'] == 'todos') {
    $sql = "SELECT tbl_cliente_admin.cliente_admin,
                   tbl_cliente_admin.cidade,
                   tbl_cliente_admin.estado,
                   tbl_cliente_admin.nome,
                   tbl_cliente_admin.parametros_adicionais,
                   tbl_cliente_admin.cnpj,
                   tbl_admin.nome_completo,
                   tbl_admin.admin AS id_admin,
                   tbl_admin.login AS login_admin
              FROM tbl_cliente_admin
         LEFT JOIN tbl_admin USING(cliente_admin, fabrica)
             WHERE tbl_cliente_admin.fabrica = $login_fabrica
          ORDER BY estado, cidade, tbl_cliente_admin.cliente_admin";
    $res = pg_query($con,$sql);

    $cssToUpper = "style='text-transform:uppercase'";
    $tabela = array(
        'attrs' => array(
            'tableAttrs' => ' class="table table-hover table-stripe dataTable" id="listagem"',
            'headerAttrs' => " class='titulo_coluna $cssToUpper'"
        )
    );

    if ($rowcount = pg_num_rows($res)) {
        $registros = pg_fetch_all($res);
        $tableData = array();

        foreach ($registros as $i=>$rec) {
            $campos_adicionais = json_decode($rec['parametros_adicionais'], 1);

            $unidadeNegocio = "";
            $xUnidadeNegocio = "";
            $xUnidadeNegocioNome = "";
            if ($login_fabrica == 158 && isset($campos_adicionais["unidadeNegocio"])) {
                $unidadeNegocio = $campos_adicionais["unidadeNegocio"];

                $xunidadeNegocios    = $oDistribuidorSLA->SelectUnidadeNegocioNotIn(null,null,$unidadeNegocio);
                $xUnidadeNegocio     = $xunidadeNegocios[0]["unidade_negocio"];
                $xUnidadeNegocioNome = $xunidadeNegocios[0]["cidade"];
            }

            if ($login_fabrica == 96)
                $rec = array_map('mb_strtoupper', $rec);

            $link = "<a href='{$_SERVER['PHP_SELF']}?cliente_admin={$rec['cliente_admin']}'>{$rec['nome']}</a>";
            $row = array(
                'Cidade' => $rec['cidade'],
                'Estado' => strtoupper($rec['estado']),
                'Nome' => $link,
                'CNPJ' => preg_replace(RE_FMT_CNPJ, '$1.$2.$3/$4-$5', $rec['cnpj'])
            );

            if ($login_fabrica == 158) {
                $row['Unidade de Negócio'] = $xUnidadeNegocioNome;
            }

            if ($login_fabrica == 52) {
                $row['Admin'] = $rec['nome_completo'];
                $row['AÇÃO'] = (empty($rec['id_admin'])) ?
                    'Sem Ação' :
                    "<button type='button' ".
                        "onclick='enviar_dados_acesso({$rec['id_admin']});' id='enviar_dados_acesso_{$rec['id_admin']}'>".
                        "Enviar Dados de Acesso".
                    "</button>";
            }
            $tableData[] = $row;
            unset($row);
        }
    }

    $table_num_rows = count($tableData);

    // Até 40 resultados, mostra uma única tabela, depois, quebra em grupos de 20
    // $page_length é o número de filas por tabela para quebrar.
    $page_length = ($rowcount > 40) ? 60 : 40;
    //for ($offset = 0; $offset < $rowcount; $offset+=$page_length) {
        echo array2table(
            array_merge($tabela, $tableData),
            'CLIENTES CADASTRADOS', false, true
        );
        echo "\n<p>&nbsp;</p>\n"; // sep.
    //}
}


if ($rowcount > 50) { ?>
    <script>
    $.dataTableLoad({
        table : "#listagem"
    });
    </script>

<?php
}
//array_slice($tableData, $offset, $page_length)
include "rodape.php";

