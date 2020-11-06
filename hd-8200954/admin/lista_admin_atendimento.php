<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$cpf_cnpj    = $_REQUEST["cpf_cnpj"];
$atendimento = $_REQUEST["atendimento"];
$os_troca_subconjunto = $_REQUEST["os_troca_subconjunto"];
$os_troca = $_REQUEST["os_troca"];
$permissao_supervisor = $_REQUEST["permissao_supervisor"];

if($_POST["validar_senha"]){

    $senha = md5($_POST["senha"]);
    $admin = $_POST["admin"];

    $sql = "SELECT admin FROM tbl_admin WHERE intervensor IS TRUE AND md5(senha) = '{$senha}' AND fabrica = {$login_fabrica} AND admin = {$admin}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $desbloqueado = true;
    } else {
        $msg_erro = "Senha inválida";
    }
    
}
?>

<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>

<?php if($desbloqueado === true){ ?>
<script>
    window.parent.desbloqueia_finalizacao_atendimento();
    window.parent.Shadowbox.close();
</script>
<?php } ?>

<body class="container" style="width: 100% !important; background-color: #FFFFFF; overflow: hidden; padding: 10px 20px;" >
    <form method="post" >

        <input type="hidden" name="validar_senha" value="sim">

        <?php

        if(strlen($msg_erro) > 0){
            echo "<div class='alert alert-danger'> <h4> {$msg_erro} </h4> </div>";
        }

        ?>

        <h4 class="tac">Para desbloquear o Atendimento, é necessário informar a senha:</h4>

        <div class="row-fluid" >
            <div class='span4' >
                <div class='control-group' >
                    <label class='control-label' for='admin' >Admins</label>
                    <div class='controls controls-row' >
                        <div class='span12' >
                            <select class="span12" id="admin" name="admin" required="required" >
                                <option></option>
                                <?php

                                $sql = "SELECT tbl_admin.admin, tbl_admin.nome_completo FROM tbl_admin WHERE fabrica = {$login_fabrica} AND ativo IS TRUE AND intervensor IS TRUE order by nome_completo ";
                                $res = pg_query($con , $sql);

                                if(pg_num_rows($res) > 0 ){
                                    for ($i=0; $i < pg_num_rows($res) ; $i++) {
                                        $admin = pg_fetch_result($res, $i, "admin");
                                        $nome_completo = ucwords(strtolower(pg_fetch_result($res, $i, "nome_completo")));
                                        echo "<option value='{$admin}'>{$nome_completo}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='senha' >Senha</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="hidden" id="atendimento" name="senha" value="<?=$atendimento?>" />
                            <input type="password" class="span12" id="senha" name="senha" required="required" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class='control-group'>
                    <div class='span10'>
                        <br />
                        <button type="submit" class="btn btn-primary btn-small btn-block desbloquear" name="enviar" data-loading-text="Desbloqueando..." value="desbloqueia" >Desbloquear</button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</body>
