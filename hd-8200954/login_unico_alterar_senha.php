<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "login_unico_autentica_usuario.php";

if($_POST['btn_acao']=='Gravar') {

    $senha          = trim($_POST['senha']);
    $confirma_senha = trim($_POST['confirma_senha']);


    $msg_erro = array();
    if(strlen($senha)==0) $msg_erro[2] = "Preencha a senha";

    //VALIDAÇÃO DE SENHA
    if (strlen($senha) > 0) {
        if ($senha === $confirma_senha) {
            if (strlen(trim($senha)) >= 6) {
                $senha = $senha;

                //- verifica qtd de letras e numeros da senha digitada -//
                $count_letras  = 0;
                $count_numeros = 0;
                $numeros = '0123456789';
                $letrasM  = strtoupper('abcdefghijklmnopqrstuvwxyz');
                $letras  = 'abcdefghijklmnopqrstuvwxyz'.$letrasM;

                for ($i = 0; $i <= strlen($senha); $i++) {
                    if ( strpos($letras, substr($senha, $i, 1)) !== false) $count_letras++;
                    if ( strpos ($numeros, substr($senha, $i, 1)) !== false) $count_numeros++;
                }

                if ($count_letras < 2)  {
                    $msg_erro[] = traduz('senha.invalida.a.senha.deve.ter.pelo.menos.2.letras');
                }
                if ($count_numeros < 2) {
                    $msg_erro[] = traduz('senha.invalida.a.senha.deve.ter.pelo.menos.2.numeros');
                }
            } else {
                $msg_erro[] = traduz('a.senha.deve.conter.um.minimo.de.6.caracteres');
            }
        } else {
            $msg_erro[] = traduz('senhas.nao.conferem');
        }
    } else {
        $msg_erro[] = traduz('digite.uma.senha');
    }

    if (!count($msg_erro)) {
        $sql = "UPDATE tbl_login_unico
                   SET senha = '$senha'
                 WHERE login_unico = $cook_login_unico
                   AND posto       = $cook_posto";

        $res = pg_query($con,$sql);
        $msg_erro[3] = pg_last_error($con);
    }
    if (!count($msg_erro)) {
        header("Location: login_unico.php?t=2");
        exit;
    }
}

$error_alert = true;
$title = traduz("alterar.senha") . " &ndash; $login_unico_nome";
include "cabecalho.php";
?>
<script type="text/javascript" src="js/jquery.pstrength-min.1.2.js"></script>
<script type="text/javascript">
$(function() {
    $('#senha').pstrength();
});
</script>
<style>
.pstrength-minchar {
    font-size : 10px;
    color:#777;
}
form table td {text-align: left;}
</style>
<br>
<FORM name='frm_os' METHOD='POST' ACTION='<?=$PHP_SELF?>'>
<input type='hidden' name='login_unico' id='login_unico' value='<?=$login_unico?>'>
    <table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='700' border='0'>
        <tr>
            <td class='Label' align='left' colspan='2'><font size='3'><b>Alterar Senha - Login Único</font></b><br>&nbsp;</td>
        </tr>
        <tr>
            <td class='Label' align='left' valign='top'>Escolha uma senha:*</td>
            <td>
                <div style='width:180px;'>
                    <input name ="senha" id='senha' class ="Caixa" type = "password" size = "30" value ="" />
                </div>
                <div class='pstrength-minchar'>Sendo no minímo 2 letras (de A a Z) e 2 números (de 0 a 9)</div>
            </td>
        </tr>
        <tr>
		    <td class='Label' align='left' nowrap  valign='top'>Digite a senha novamente:*</td>
            <td><input name ="confirma_senha" id='confirma_senha' class ="Caixa" type = "password" size = "30" value ="" ></td>
        </tr>
        <tr>
            <td colspan='2' style='text-align:center;margin-top: 5px'><input name='btn_acao' value='Gravar' type='submit'>&nbsp;&nbsp;<input type='button' name='cancelar' value='Cancelar' onclick="window.location = 'login_unico.php?t=4'"></td>
        </tr>
    </table>
</form>
<?php
include "rodape.php";

