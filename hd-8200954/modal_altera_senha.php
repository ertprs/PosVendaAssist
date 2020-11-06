<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once 'classes/Posvenda/Seguranca.php';

$objSeguranca = new \Posvenda\Seguranca(null,$con);

$tipo = $_REQUEST["tipo"];
if ($tipo == "login") {
    include 'autentica_usuario.php';
    $checkSkip = $objSeguranca->checaSkipPostoFabrica($login_posto, $login_fabrica);
} else {
    include_once 'login_unico_autentica_usuario.php';
    $checkSkip = $objSeguranca->checaSkipLoginUnico($login_unico);
}
if ($_POST) {
    $senha_new = trim($_POST["nova_senha"]);
    $ip = $_SERVER["REMOTE_ADDR"];
    $resB = pg_query($con,"BEGIN TRANSACTION");
    $msg_success = "";
    $msg_erro    = "";

    if ($tipo == "login") {

        $dados_posto = $objSeguranca->getPostoFabrica($login_posto,$login_fabrica);

        $senha_old = trim($dados_posto["senha"]);
        if (strlen($senha_new) < 6 || $objSeguranca->countDigits($senha_new) < 2 || $objSeguranca->countLetters($senha_new) < 2 || strlen($senha_new) > 10) {
            $msg_erro = "Nova senha é inválida.";
        } elseif ($senha_new == $senha_old) {
            $msg_erro = "Nova senha é a mesma da Senha Atual.";
        } else {

            $validaSenha = $objSeguranca->validaSenhaDuplicada($dados_posto["codigo_posto"], $senha_new);

            if (isset($validaSenha["posto"]) && strlen($validaSenha["posto"]) > 0) {
                $msg_erro = "Nova senha é inválida.";
            } else {
                $retorno = $objSeguranca->updateSenha($senha_old, $senha_new, $ip, 'posto', $dados_posto["posto_fabrica"], null);
            }

        }


    } else {
        $dados_login_unico = $objSeguranca->getLoginUnico($login_unico);
        $senha_old = trim($dados_login_unico["senha"]);

        if (strlen($senha_new) < 6 || $objSeguranca->countDigits($senha_new) < 2 || $objSeguranca->countLetters($senha_new) < 2) {
            $msg_erro = "Nova senha é inválida.";
        } elseif ("md5".md5($senha_new) == $senha_old) {
            $msg_erro = "Nova senha é a mesma da Senha Atual.";
        } else {
            $retorno = $objSeguranca->updateSenha($senha_old, "md5".md5($senha_new), $ip, 'login_unico', null, $dados_login_unico["login_unico"]);
        }

    }
    if (strlen($msg_erro) > 0) {
        $resB = pg_query($con,"ROLLBACK TRANSACTION");
    } else {

        if ($retorno) {
            $resB = pg_query($con,"COMMIT TRANSACTION");
            $msg_success = "Senha Alterada com sucesso.";
            echo "<script>setTimeout(function(){ window.parent.Shadowbox.close();window.parent.retornaLink(true);}, 1000);</script>";

        } else {
            $resB = pg_query($con,"ROLLBACK TRANSACTION");
            $msg_erro = "Erro ao alterar a senha, entre em contato com o suporte.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="ISO-8859-1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alterar Senha</title>
    
    <?php
    $plugins = array(
        'jquery3',
        'bootstrap3',
        'font_awesome',
        'select2',
        'price_format',
    );
    include 'plugin_loader.php';
    ?>
    
    <style>
        body{
            background: #fff !important;
        }
    
        html {
            overflow: auto;
        }
    </style>
    <script>
        $(function(){

            $(document).on('click', '.btn-alterar', function(){
                var nova_senha = $("#nova_senha").val();
                var confirm_nova_senha = $("#confirm_nova_senha").val();
                if (nova_senha == '') {
                    alert("Preencha o campo NOVA SENHA");
                    $("#nova_senha").focus();
                    return false;
                }

                if (nova_senha.length < 6) {
                    alert("O campo NOVA SENHA tem que ter no minimo 6 caracteres");
                    $("#nova_senha").focus();
                    return false;
                }
                
                if (nova_senha.length > 10) {
                    alert("O campo NOVA SENHA tem que ter no máximo 10 caracteres");
                    $("#nova_senha").focus();
                    return false;
                }

                if (confirm_nova_senha == '') {
                    alert("Preencha o campo CONFIRME NOVA SENHA");
                    $("#confirm_nova_senha").focus();
                    return false;
                }

                if (nova_senha != confirm_nova_senha) {
                    alert("O campo NOVA SENHA não é igual ao CONFIRME NOVA SENHA");
                    $("#nova_senha").focus();
                    return false;
                }
                $("form").submit()
            });

        });
    </script>
</head>
<body>
    <div class="container">
        <form action="" method="post">
        
            <h4 style="padding: 20px;text-align: center;line-height: 25px;">
                Identificamos que você não altera sua senha a algum tempo, por motivos de segurança, faça alteração no campo abaixo, caso a senha não seja alterada o seu acesso ao Telecontrol poderá ser <b>bloqueado</b>.
            </h4>
           <div class="alert alert-info"><em>Digite a senha desejada, com mínimo de seis caracteres e no máximo dez, sendo no mínimo 2 letras (de A a Z) e 2 números (de 0 a 9). Por exemplo bra500, tele2007, ou assist0682</em></div>
            <?php if (strlen($msg_erro) > 0) {?>
                <div class="alert alert-danger"><?php echo $msg_erro;?></div>
            <?php }?>
            <?php if (strlen($msg_success) > 0) {?>
                <div class="alert alert-success"><?php echo $msg_success;?></div>
            <?php }?>
            <div class="row">
                <div class="col-xs-2 col-sm-2 col-md-2"></div>
                <div class="col-xs-4 col-sm-4 col-md-4">
                    <label>Nova Senha:</label><br>
                    <input class="form-control" name="nova_senha" id="nova_senha" type="password">
                </div>
                <div class="col-xs-4 col-sm-4 col-md-4">
                    <label>Confirme Nova Senha:</label><br>
                    <input class="form-control" name="confirm_nova_senha" id="confirm_nova_senha" type="password">
                </div>
            </div><hr />
            <div class="row">
                <div class="col-xs-2 col-sm-2 col-md-2"></div>
                <div class="col-xs-4 col-sm-4 col-md-4" style="text-align: right;">
                    <button type="button" class="btn btn-success btn-alterar">Alterar</button>
                </div>
                <?php if ($checkSkip == true) {?>
                <div class="col-xs-4 col-sm-4 col-md-4" style="text-align: left;">
                    <button type="button" onclick="<?php echo ($tipo == "login") ? "window.parent.retornaLink();":"window.parent.Shadowbox.close();";?> " class="btn btn-warning">Alterar Depois</button>

                </div>
                <?php }?>
            </div>
        </form>
    </div>
</body>
</html>
