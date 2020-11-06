<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
require_once "class/communicator.class.php";
include_once 'classes/Posvenda/Seguranca.php';

$objSeguranca = new \Posvenda\Seguranca(null,$con);

if($_GET['ajax_verifica'] == true){

    $login = $_POST['login'];
    $id_login = $_POST['id'];
    $programa = $_POST['programa'];
    $ip_cliente = $_SERVER["REMOTE_ADDR"].'/32';

    if(strlen($login) > 0){
        if ($programa == "login_unico") {

            $dadosLogin  = $objSeguranca->getLoginUnico($id_login);
            $login_unico = $dadosLogin['login_unico'];
            $dadosIp  = $objSeguranca->getIpConfiavel(null, $login_unico);
            if (count($dadosIp) == 0){
                exit(json_encode(["tipo" => "login_unico", "primeiro_acesso" => true, "novo_ip" => true]));
            } 

            if (!in_array($ip_cliente, $dadosIp)) {
                exit(json_encode(["tipo" => "login_unico", "primeiro_acesso" => false,"novo_ip" => true]));
            } 
            exit(json_encode(["erro" => true]));       
        }
        
        if ($programa == "login") {

            $dadosLogin    = $objSeguranca->getPostoFabrica(null, null, $id_login);
            $posto_fabrica = $dadosLogin['posto_fabrica'];
            $dadosIp       = $objSeguranca->getIpConfiavel($posto_fabrica,null);

            if (count($dadosIp) == 0){
                exit(json_encode(["tipo" => "login", "primeiro_acesso" => true, "novo_ip" => true]));
            } 

            if (!in_array($ip_cliente, $dadosIp)) {
                exit(json_encode(["tipo" => "login", "primeiro_acesso" => false,"novo_ip" => true]));
            }

            exit(json_encode(["erro" => true]));       
        }
        
    }

}

if($_POST['ajax'] == true){

    $ip_cliente     = $_SERVER["REMOTE_ADDR"];
    $codigo         = $_POST['codigo_seguranca'];
    $tipo           = $_POST['tipo'];
    $id_login       = $_POST['admin'];

    if(strlen($codigo) > 0){

        $campos  = "ip,";
        $valores = "'$ip_cliente',";

        if ($tipo == "login_unico") {

            $dadosLogin = $objSeguranca->getLoginUnicoByEmailCodigoSeguranca($id_login,$codigo);

            $campos  .= "login_unico";
            $valores .= $dadosLogin["login_unico"];
            $login_unico = $dadosLogin["login_unico"];

        } else {

            $dadosLogin = $objSeguranca->getLoginByEmailCodigoSeguranca($id_login,$codigo);

            $campos  .= "posto_fabrica";
            $valores .= $dadosLogin["posto_fabrica"];
            $posto_fabrica = $dadosLogin["posto_fabrica"];

        }

        if(!empty($dadosLogin)){

            $parametros_adicionais = json_decode($dadosLogin['parametros_adicionais'],1);
            $dadosRetorno = $objSeguranca->gravaIpconfiavel($campos,$valores);

            if (!$dadosRetorno) {

                $retorno['erro'] = true;

            } else {

                unset($parametros_adicionais["codigo_seguranca"]);
                unset($parametros_adicionais["token_seguranca"]);
                if (empty($parametros_adicionais)) {
                    $xnovo_parametros = '{}';
                } else {
                    $xnovo_parametros = json_encode($parametros_adicionais);
                }

                if ($tipo == "login_unico") {

                    $dadosUpLogin = $objSeguranca->atualizaParametrosAdicionaisLoginUnico($xnovo_parametros,$login_unico);

                    if (!$dadosUpLogin) {

                        $retorno['erro'] = true;

                    } else {
                        $retorno['success'] = true;
                    }

                } else {

                    $dadosUpLogin = $objSeguranca->atualizaParametrosAdicionaisLogin($xnovo_parametros,$posto_fabrica);

                    if (!$dadosUpLogin) {

                        $retorno['erro'] = true;

                    } else {
                        $retorno['success'] = true;
                    }
                }
            }
        }else{
            $retorno['erro'] = true;
        }
    }
    
    echo json_encode($retorno);

    exit;
}
$xxprimeiro_acesso = $_GET['primeiro_acesso'];
$admin = $_GET['admin'];
$tipo = $_GET['tipo'];
$login = $_GET['login'];
$ip_cliente = $_SERVER["REMOTE_ADDR"];
if ($xxprimeiro_acesso == 'true') {
    $legenda = "Para garantia da sua segurança foi enviado um código para o e-mail cadastrado, por favor, digite o código no campo abaixo.";
} else {
    $legenda = "Identificamos que você está acessando o sistema Telecontrol de um local diferente do habitual, para garantia da sua segurança, foi enviado um código para o e-mail cadastrado, por favor, digite o código no campo abaixo.";
}

if ($tipo == "login_unico") {
    $dadosLogin  = $objSeguranca->getLoginUnico($admin);
    $xlogin      = $dadosLogin['login_unico'];
    $xemail      = $dadosLogin['email'];

}
if ($tipo == "login") {
    $dadosLogin  = $objSeguranca->getPostoFabrica(null, null, $admin);
    $xlogin      = $dadosLogin['posto_fabrica'];
    $xemail      = $dadosLogin['contato_email'];

}
$sucesso = false;
if (!empty($dadosLogin)) {
    $xparametros_adicionais                     = json_decode($dadosLogin['parametros_adicionais'],1);
    $codigo_seguranca                           = random_int(100, 99999);
    $xparametros_adicionais["codigo_seguranca"] = $codigo_seguranca;

    $novo_parametros                            = json_encode($xparametros_adicionais);


    if ($tipo == "login_unico") {
        $dadosUpdate  = $objSeguranca->atualizaParametrosAdicionaisLoginUnico($novo_parametros, $xlogin);
    }
    if ($tipo == "login") {
        $dadosUpdate  = $objSeguranca->atualizaParametrosAdicionaisLogin($novo_parametros, $xlogin);
    }


    if (!$dadosUpdate) {
        $meg_erro["msg"][] = "Erro ao gravar";
    } else {
        $email          = explode("@", $xemail);
        $init_email     = substr($email[0], 0,2);
        $fim_email      = str_replace(substr($email[0], 0), "***", $xemail);

        /*dispara email para admin*/
        $mensagemEmail  = "Segue código de segurança para acesso ao Sistema Telecontrol <h3>".$codigo_seguranca."</h3>";
        $assunto        = 'Código de Segurança para acesso ao Sistema Telecontrol';
        $mensagem       = $mensagemEmail;
        $externalId     = 'smtp@posvenda';
        $externalEmail  = 'noreply@telecontrol.com.br';

        $mailTc = new \TcComm($externalId);
        $res    = $mailTc->sendMail(
            $xemail,
            $assunto,
            utf8_encode($mensagem),
            $externalEmail
        );
        if ($res) {
            $sucesso = true;
        }

    }

} else {
    $meg_erro["msg"][] = "Login não encontrado";
}

if ($sucesso) {

?>
<!doctype html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{background: #ddd;}
        .box{
            margin: 0 auto;text-align:center;font-family: 'Arial';width: 100%
        }
        .box-int{
            margin: 0 auto;margin-top: 25px;background:#ddd;border:solid 1px #ddd;width: 90%
        }
    </style>
  </head>
  <body>
    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <?php 
        $plugins = array(
            "bootstrap2"
        );
        include("plugin_loader.php");
    ?>
<script>
    $(function(){

        $("#btn_valida").click(function(){
            var codigo = $("#codigo_seguranca").val();
            var admin = $("#codigo_seguranca").data("id");
            var tipo = $("#codigo_seguranca").data("tipo");
            var link = tipo+".php";
            if(codigo == '') {
                $(".erro").show();
                $(".erro").html("Digite o C&oacute;digo inv&aacute;lido");
                $("#codigo_seguranca").focus();
                return false;
            }
            $("#btn_valida").attr("disabled", true);
            $("#btn_valida").text("Validando...");
            $.ajax({
                url: "verifica_login.php",
                method: "POST",
                data: {ajax:true,codigo_seguranca:codigo,admin:admin,tipo:tipo},
                success: function(retorno){
                    var data = $.parseJSON(retorno);
                    if(data.erro == true){
                        $(".erro").show();
                        $(".erro").html("C&oacute;digo inv&aacute;lido");
                    }else{
                        window.open(link);
                        window.parent.Shadowbox.close();
                    }
                    $("#btn_valida").removeAttr("disabled");
                    $("#btn_valida").text("Validar");
                }
            });
        });
    });

</script>

<div class="box">
    <div class="box-int">
        <div class="alert alert-danger erro" style='display:none'></div>
        <h5><?php echo $legenda;?></h5>
        <h3><?php echo $fim_email;?></h3>
        <label for="">C&oacute;digo de Seguran&ccedil;a</label>
        <div class="row-fluid">
            <div class="span4"></div>
            <div class="span4">
                <div class="input-append">
                    <input type="text" data-tipo='<?php echo $tipo;?>' data-id='<?php echo $admin;?>' class="form-control input-sm" name="codigo_seguranca" id="codigo_seguranca">
                    <button type="button" data-loading-text="Validando..." class="btn btn-sm btn-success" id="btn_valida" >Validar</button>
                </div>
            </div>
        </div>
        <br>
    </div>
</div>
</body>
</html>
<?php }?>
