<!DOCTYPE html>

<?php

$usuario_padrao = "doctelecontrol";
$senha_padrao = "tele6588";

if ($_POST["btn_acao"] == "Enviar") {

    $usuario    = $_POST['usuario'];
    $senha      = $_POST['senha'];  


    if(strlen($usuario) > 0 AND strlen($senha) > 0){

        if($usuario_padrao == $usuario && $senha_padrao == $senha) {    
            setcookie("login",'true',time()+3600);
            header("location: index.php");
            $_COOKIE['login'];
        } else {
            $msg_erro = "Usuário ou Senha inválidos";
        }
    
    }else{
        $msg_erro = "Usuário ou senha inválidos";
    }
}


?>

<html>
    <head>
        <!-- <title>Documentação Pós-Venda</title> -->
        <link rel="stylesheet" type="text/css" href="public/bootstrap/css/bootstrap.css">        
        <link href="http://posvenda.telecontrol.com.br/assist/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">        
        <link href="http://posvenda.telecontrol.com.br/assist/admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen">
        <link href="http://posvenda.telecontrol.com.br/assist/plugins/dataTable.css" type="text/css" rel="stylesheet" media="screen">
        <link href="http://posvenda.telecontrol.com.br/assist/admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen">        
        <link href="http://posvenda.telecontrol.com.br/assist/admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen">        
        <!--<link href="http://192.168.0.199/~guilherme/assist/plugins/shadowbox_lupa/shadowbox.css" type="text/css" rel="stylesheet" media="screen">-->                
        <!--<link href="http://www.shadowbox-js.com/build/shadowbox.css" type="text/css" rel="stylesheet" media="screen">-->                
        <link href="public/css/shadowbox.css" type="text/css" rel="stylesheet" media="screen">                
        <link rel="stylesheet" type="text/css" href="public/css/login_css.css">        
        <link rel="stylesheet" type="text/css" href="http://posvenda.telecontrol.com.br/assist/admin/plugins/multiselect/multiselect.css">
        <script src="http://posvenda.telecontrol.com.br/assist/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="public/bootstrap/js/bootstrap.min.js"></script>
        <script src="public/js/dataTable.js"></script>
        <script src="public/js/script.js"></script>
        <script src="http://posvenda.telecontrol.com.br/assist/plugins/jquery.alphanumeric.js"></script>
    </head>
 
    <body>
        <!-- <div class="container">
            <div class="row-fluid">
                <div class="span4"></div>
                <div class="span4">
                    <h1 class="text-center login-title">Sign in to continue to Bootsnipp</h1>
                    <div class="account-wall">
                        <img class="profile-img" src="https://lh5.googleusercontent.com/-b0-k99FZlyE/AAAAAAAAAAI/AAAAAAAAAAA/eu7opA4byxI/photo.jpg?sz=120"
                            alt="">
                        <form class="form-signin">
                        <input type="text" class="form-control" placeholder="Email" required autofocus>
                        <input type="password" class="form-control" placeholder="Password" required>
                        <button class="btn btn-lg btn-primary btn-block" type="submit">
                            Sign in</button>
                        <label class="checkbox pull-left">
                            <input type="checkbox" value="remember-me">
                            Remember me
                        </label>
                        <a href="#" class="pull-right need-help">Need help? </a><span class="clearfix"></span>
                        </form>
                    </div>
                    <a href="#" class="text-center new-account">Create an account </a>
                </div>
                <div class="span4"></div>
            </div>
        </div> -->
    
        <div class="container" style="margin-top:120px">
            <div class="row">
                <div class="span3"></div>
                <div class="span4 well">
                    <legend>Login Documentação</legend>
                    <?php 
                        if(strlen($msg_erro) > 0){
                    ?>

                    <div class="alert alert-error">
                        <!-- <a class="close" data-dismiss="alert" href="#">×</a> -->
                        <strong><?php echo $msg_erro; ?></strong>
                    </div>

                    <?php
                        }
                    ?>
                    
                    <form method="POST" action="<?=$PHP_SELF?>" accept-charset="UTF-8">
                        <input type="text" id="usuario" class="span4" name="usuario" placeholder="Usuário">
                        <br /><br />
                        <input type="password" id="senha" class="span4" name="senha" placeholder="Senha">
                        <br /><br /><br />
                        <input type="submit" name="btn_acao" id="btn_acao" value="Enviar" class="btn btn-info btn-block btn-large"></input>
                   
                    </form>    
                </div>
                <div class="span4"></div>
            </div>
        </div>

        <!-- Modelo 3 -->
        <!-- <div class="container" style="margin-top:30px">
            <div class="span4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><strong>Sign in </strong></h3>
                    </div>
                    <div class="panel-body">
                        <form role="form">
                            <div class="form-group">
                                <label for="exampleInputEmail1">Username or Email</label>
                                <input type="email" class="form-control" style="border-radius:0px" id="exampleInputEmail1" placeholder="Enter email">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Password <a href="/sessions/forgot_password">(forgot password)</a></label>
                                <input type="password" class="form-control" style="border-radius:0px" id="exampleInputPassword1" placeholder="Password">
                            </div>
                            <button type="submit" class="btn btn-sm btn-default">Sign in</button>
                        </form>
                    </div>
                </div>
            </div>
        </div> -->


    </body>

    <script src="http://posvenda.telecontrol.com.br/assist/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/admin/plugins/multiselect/multiselect.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/jquery.mask.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/price_format/jquery.price_format.1.7.min.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/price_format/config.js"></script>
    <script src="public/bootstrap/js/bootstrap-tooltip.js"></script>
    
    <!--<script src="http://192.168.0.199/~guilherme/assist/plugins/shadowbox_lupa/shadowbox.js"></script>-->
    <!--<script src="http://www.shadowbox-js.com/build/shadowbox.js"></script>-->
    <script src="public/js/shadowbox.js"></script>

</html>

