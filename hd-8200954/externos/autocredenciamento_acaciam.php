<?php 
	include dirname(__FILE__) . '/../dbconfig.php';
    include dirname(__FILE__) . '/../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../class_resize.php';
    include dirname(__FILE__) . '/../mlg/mlg_funciones.php';
    include dirname(__FILE__) . '/../trad_site/fn_ttext.php';

    function checaCPF ($cpf,$return_str = true) {
        global $con;    // Para conectar com o banco...
        if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) false;

        $res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
        if ($res_cpf === false) {
            return ($return_str) ? pg_last_error($con) : false;
        }
        return $cpf;
    }

    function validaSenha($senha) {

		$senha         = strtolower($senha);
		$count_tudo    = 0;
		$count_letras  = 0;
		$count_numeros = 0;
		$numeros       = '0123456789';
		$letras        = 'abcdefghijklmnopqrstuvwxyz';
		$tudo          = $letras.$numeros;

		//Confere o mínimo de 2 letras e dois números

		//- verifica qtd de letras e numeros da senha digitada -//
		$count_letras   = preg_match_all('/[a-z]/i', $senha, $a_letras);
		$count_numeros  = preg_match_all('/[0-9]/',  $senha, $a_nums);
		$count_invalido = preg_match_all('/\W/',     $senha, $a_invalidos);
		if ($debug == 'pwd')
			p_echo("Senha: $senha<br />Letras: $count_letras, dígitos: $count_numeros");

		if ($count_letras + $count_numeros > 10)   $msg_erro .= "Senha inválida, a senha não pode ter mais que 10 caracteres<br>";
		if ($count_letras + $count_numeros <  6)   $msg_erro .= "Senha inválida, a senha deve conter um mínimo de 6 caracteres<br>";
		if ($count_letras < 2)  $msg_erro .= "Senha inválida, a senha deve ter pelo menos 2 letras <br>";
		if ($count_numeros < 2) $msg_erro .= "Senha inválida, a senha deve ter pelo menos 2 números<br>";

		return (strlen($msg_erro) > 0) ? $msg_erro : true;

	}

    $pagetitle = "Telecontrol - Auto-Credenciamento Acáciaeletro";

	include('site_estatico/header.php'); 

	$btn_acao = $_POST['btn_acao'];

	if($btn_acao == "Cadastrar"){

		$cnpj  = preg_replace("/\D/", "", $_POST['cnpj']);
		$senha = $_POST['senha'];
		$confirma_senha = $_POST['confirma_senha'];

		if(strlen($cnpj) == 0 OR strlen($senha) == 0){
			$msg_erro = "Informe o CNPJ e a Senha";
		}elseif (checaCPF($cnpj,false)===false){
			$msg_erro = 'CNPJ digitado inválido';
		}elseif($senha <> $confirma_senha){
			$msg_erro = "Senhas informadas são diferentes";
		}else{
			$validaSenha = validaSenha($senha);

			if($validaSenha !== true){
				$msg_erro = $validaSenha;
			}

		}

		if(strlen($msg_erro) == 0){
			$sql = "SELECT posto FROM tbl_posto WHERE cnpj = '$cnpj'";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
				$posto = pg_fetch_result($res, 0, 'posto');

				$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = 168 AND posto = $posto";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					$msg_erro = "CNPJ informa já é credenciado para a AcáciaEletro";
				}else{

					$sqlSenha = "SELECT posto FROM tbl_posto_fabrica WHERE posto = $posto AND senha = '$senha' and length(senha) > 3";
		        	$qrySenha = pg_query($con, $sqlSenha);

		        	if(pg_num_rows($qrySenha) > 0){
		        		$msg_erro = "Senha inválida, favor escolher outra.";
		        	}
		        }

			}else{
				$msg_erro = "CNPJ não encontrado";
			}
		}

		
		if(strlen($msg_erro) == 0){

			$res = pg_query($con,"BEGIN");

			$sql = "INSERT INTO tbl_posto_fabrica(
												posto,
												fabrica,
												senha,
												codigo_posto,
												tipo_posto,
												digita_os,
												credenciamento,
												primeiro_acesso,
												contato_endereco,
												contato_numero        ,
												contato_complemento   ,
												contato_bairro        ,
												contato_cidade        ,
												contato_cep           ,
												contato_email			,
												contato_nome            ,  
												contato_pais             ,
												contato_fone_residencial ,
												contato_fone_comercial   ,
												contato_cel              ,
												contato_fax              ,
												cod_ibge
												)
												SELECT $posto,
												168,
												'$senha',
												'$cnpj',
												630,
												'f',
												'CREDENCIADO',
												CURRENT_TIMESTAMP,
												contato_endereco,
												contato_numero        ,
												contato_complemento   ,
												contato_bairro        ,
												contato_cidade        ,
												contato_cep           ,
												contato_email			,
												contato_nome            ,  
												contato_pais             ,
												contato_fone_residencial ,
												contato_fone_comercial   ,
												contato_cel              ,
												contato_fax              ,
												cod_ibge
												FROM tbl_posto_fabrica 
												WHERE posto = $posto
												AND credenciamento = 'CREDENCIADO'
												ORDER BY fabrica DESC LIMIT 1";
			$res = pg_query($con,$sql);

			if(strlen(pg_last_error()) == 0){
				$sql = "INSERT INTO tbl_posto_linha(posto,tabela,linha) VALUES($posto,1069,1002)";
				$res = pg_query($con,$sql);

				if(strlen(pg_last_error()) > 0){
					$msg_erro = "Erro ao realizar o credenciamento";
				}

			}else{
				echo pg_last_error();
				$msg_erro = "Erro ao realizar o credenciamento";
			}

			if(strlen($msg_erro) == 0){
				pg_query($con,"COMMIT");
				$msg_ok = "Sucesso";
			}else{
				pg_query($con,"ROLLBACK");
			}
						
		}
	}

?>

<script>$('body').addClass('pg log-page')</script>

<script src="../js/jquery.autocomplete.js" type="text/javascript"></script>
<script src="../js/file/jquery.MultiFile_novo.js" type="text/javascript"></script>
<script type="text/javascript" src="../js/jquery.numeric.js"></script>
<script type="text/javascript" src="../admin/js/jquery.mask.js"></script>
<script type="text/javascript">

    function vericaSubmitCNPJ() {
        var cnpj = $("#cnpj").val();
        var senha = $("#senha").val();
        if (!cnpj || !senha) {

            $("#msg_erro").show();
            setTimeout(function(){
                $("#msg_erro").hide();
            },3000);

        } else {
            $("#mensagem_envio").html('');
            $('#verificaa').submit();
            return true;
        }
    }
 
    $(document).ready(function() {
        $("input[type=text][name=cnpj]").mask("99.999.999/9999-99");
    });
</script>

<style type="text/css">
    .input_gravar{
        cursor: pointer;
        border-bottom: none;
        background: #434390;
        color: #FFF;
        padding: 10px 60px;
        display: inline-block;
        width: auto;
        margin-top: 12px;
        display: block;
        width: 100%;
        text-align: center;
    }
    .contato{
        background: none;
    }
</style>

<section class="table h-img">
    <?php include('site_estatico/menu-pgi.php'); ?>
    <div class="cell">
        <div class="title"><h2>Auto-Credenciamento</h2></div>
    </div>
</section>

<section class="pad-1 login cad-autocred">
    <div class="main">

        <?php
            if(strlen($msg_ok) > 0){
                $display="style='display:block;'";
        ?>
                <script language="JavaScript" type="text/javascript">
                    var contador = 5;
                    function conta() {
                        if(contador == 0) {
                            window.location = "login_posvenda_new.php";
                        }
                        if (contador != 0){
                            contador = contador-1;
                            setTimeout("conta()", 1000);
                        }
                    }
                </script>
                <div class="alerts">
                    <div class="alert success" <?=$display?>><i class="fa fa-check-circle"></i>
                        A Telecontrol agradece o seu cadastro!
                    </div>
                </div>
                <div>   

                <script>window.onload = conta();</script>

        <?php
            exit;
            }
        ?>

        <?php if(strlen($msg_erro) > 0){ $display="style='display:block;'"; ?>
            <div class="alert error" <?=$display?>><i class="fa fa-exclamation-circle"></i><?=$msg_erro?></div>
        <?php } ?>
       
        <div class="alerts">
            <div class="alert error" id='msg_erro'><i class="fa fa-exclamation-circle"></i>Por favor, informe seu CNPJ e a sua Senha</div>
        </div>

        <form method='post' id='verificaa' name='frm_verifica' action="<?$PHP_SELF?>">
	        <div class="desc">
	            <h3>
	            Informe o CNPJ
	            </h3>
	        </div>
            <input type="text" name="cnpj" id='cnpj' maxlength="18" value="<?=trim($cnpj)?>" placeholder="CNPJ">

            <div class="desc">
	            <h3>
	            Informe a Senha
	            </h3>
	        </div>
            <input type="password" name="senha" id='senha' maxlength="10" value="<?=trim($senha)?>" placeholder="Digite uma Senha">
            <input type="password" name="confirma_senha" id='confirma_senha' maxlength="10" value="<?=trim($confirma_senha)?>" placeholder="Confirme sua Senha">
            <input type="hidden" name="btn_acao" value="Cadastrar" />
            <input type="button"  id='btn_acao' class='input_gravar' style="cursor: default;" value='<?=ttext ($a_labels, "Cadastrar", $cook_idioma)?>' onClick="vericaSubmitCNPJ()" />
        </form>
		<br><br>

		<div class="desc">
        <h3>Instruções para senha</h3>
        <br>
        <h4>
        Digite a senha desejada, com mínimo de seis caracteres e no máximo dez, sendo no minímo 2 letras (de A a Z) e 2 números (de 0 a 9).
    	<p>por exemplo: bra500, tele2007, ou assist0682.</p>
        </h4>
        </div>
		<br><br>
        <div class="desc">
        </div>
    </div>
</section>

<?php include('site_estatico/footer.php'); ?>
