<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

$app_ticket = $parametros_adicionais_posto['app_ticket']; 

if(in_array($login_fabrica, array(3))){
	header("location: tecnico_cadastro_new.php");
	exit;
}

if (!function_exists('checaCPF')) {
    function checaCPF ($cpf,$return_str = true) {
        global $con, $login_fabrica;// Para conectar com o banco...
        $cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
        if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) false;

        if(strlen($cpf) > 0){
            $res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
            if ($res_cpf === false) {
                return ($return_str) ? pg_last_error($con) : false;
            }
        }
        return $cpf;
    }
}

function maskCpf($val, $mask){
	$maskared = '';
	$k = 0;
	for($i = 0; $i<=strlen($mask)-1; $i++){
		if($mask[$i] == '#'){
			if(isset($val[$k]))
			$maskared .= $val[$k++];
		}else{
			if(isset($mask[$i]))
			$maskared .= $mask[$i];
		}
	}
	return $maskared;
}




$btn_acao = strtolower($_POST['btn_acao']);
if (strlen($btn_acao) == 0) $btn_acao = $_GET["btn_acao"];

$tecnico = $_POST["tecnico"];
if (strlen($tecnico) == 0) $tecnico = $_GET["tecnico"];

$nome = $_POST["nome"];
if (strlen($nome) == 0) $nome = $_GET["nome"];

if(in_array($login_fabrica, array(169,170,178,184,191,193,198,200)) OR $app_ticket == "true" ){
	$cpf = $_POST['cpf'];
	if (strlen($cpf) == 0) $cpf = $_GET["cpf"];
}

$edita = 0;
if (strlen($tecnico)) {
	$tecnico = intval($tecnico);

	$sql = "SELECT tecnico FROM tbl_tecnico WHERE tecnico=$tecnico AND fabrica=$login_fabrica AND posto=$login_posto";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 0) {
		$msg_erro = traduz('tecnico.nao.encontrado', $con);
		$tecnico = "";
	} else {
		$edita = 1;
	}
}

if (strlen($btn_acao)) {
	if (strlen($nome) < 4) {
		$msg_erro = traduz('o.nome.do.tecnico.deve.ter.no.minimo.%.caracteres', $con, $cook_idioma, array('3'));
	}
	elseif (strlen($nome) > 100) {
		$msg_erro = traduz('o.nome.do.tecnico.deve.ter.no.maximo.%.caracteres', $con, $cook_idioma, array('100'));
	}
}

#-------------------- GRAVAR -----------------
if ($btn_acao == "gravar") {

	$sql = "BEGIN";
	$res = pg_query($con, $sql);

	$acesso_app = $_POST['acesso_app'];

	if(in_array($login_fabrica, array(169,170,178,184,191,193,198,200)) OR $app_ticket == "true" ){
		if(strlen(trim($cpf)) == 0){
			$msg_erro .= "<br/>Favor preencher o campo CPF";
		}else{

			$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$cpf));
			if(empty($valida_cpf_cnpj)){

				$cnpj_valido = (!is_bool($cpf = checaCPF($cpf,false)));

		  		if ($cnpj_valido) {
					$xcpf = "'".checaCPF($cpf,false)."'";
			  	} else {
			    	$msg_erro .= " CPF inválido<br />";
			    	$xcpf = 'null';
			  	}
			}else{
			  $msg_erro .= $valida_cpf_cnpj."<br />";
			}
		}
	}

	if($acesso_app == 't' and $app_ticket == 'true' and strlen($msg_erro) == 0){
		include "classes/Ticket/classes/User.php"; 
		$urlUser = "https://api2.telecontrol.com.br";
		$header = array( "access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
			"access-env: PRODUCTION",
			"cache-control: no-cache",
			"content-type: application/json"			    
		);

		
		$user = new User($urlUser, $header);
		$retornoemail = $user->buscaUsuario(trim($email));
		$retornoemail = json_decode($retornoemail, true);

		if ($retornoemail['status_code'] == '404') {
			$params['nome'] = utf8_encode($nome);
			$params['sobrenome'] = "";
			$params['email'] = $email;

			$params = json_encode($params);
			$retornoemail = $user->criaUsuario($params);
			$retornoemail = json_decode($retornoemail, true);

		} else { 

		/* 	Atualiza Usuário

			if (!empty($_POST['ativo']) && $acesso_app == 't') {

				$queryCodigo = "SELECT codigo_externo 
								FROM tbl_tecnico 
								WHERE tecnico = $tecnico";

				$resCodigo = pg_query($con, $queryCodigo);

				$resCodigo = pg_fetch_object($resCodigo);

				$params["externalId"]   = $resCodigo->codigo_externo;
				$params["nome"]         = utf8_encode($_POST["nome"]);
				$params["sobrenome"]    = "";
				$params["ativo"]        = "true";

				$params = json_encode($params);
				
				$retornoUpdate = $user->atualizaUsuario($params);
				$retornoUpdate = json_decode($retornoUpdate, true);
				
			} 
		*/
		}

		$codigo_externo = $retornoemail['user']['internal_hash'];

		if(strlen(trim($codigo_externo))>0){
			$campos_codigo_externo = ", codigo_externo ";
			$value_codigo_externo = ", '$codigo_externo' ";
		}
	}

	if (strlen($tecnico)) {

		if (!empty($_POST['ativo'])) {
			$ativo = $_POST['ativo'];
		} else {
			if(in_array($login_fabrica, array(59,148, 158,169,170,178,184,191,198,200))){
				$ativo = 'f';
			} else {
				$ativo = 't';
			}
		}

		if(strlen(trim($msg_erro)) == 0){
			$tof = array('t', 'f');
			if (in_array($ativo, $tof)) {

				if( (in_array($login_fabrica, array(169,170,178,184,191,193,198,200)) OR $app_ticket == "true" )AND strlen(trim($xcpf)) > 0){
					$update_cpf = ", cpf = ".$xcpf;
				}else{
					$update_cpf = "";
				}
				$update_email = '';
				$update_telefone = '';
				if(in_array($login_fabrica, array(169,193)) OR $app_ticket == "true" ){
					$email = (in_array($login_fabrica, [193])) ? str_replace(["'", "\""], "", $email) : $email;
					$update_email = ", email = '$email'";
					$update_telefone = ", telefone = '$telefone'";
				}

				if($app_ticket == "true" and $acesso_app == 't'){
					$update_codigo_externo = " ,codigo_externo = '$codigo_externo' ";
				}
				
				if (empty($acesso_app)) {
					
					$update_codigo_externo= " , codigo_externo = null";
				}

				$sql = "UPDATE tbl_tecnico SET nome='$nome', ativo = '$ativo' $update_cpf $update_email $update_telefone $update_codigo_externo WHERE tecnico=$tecnico";
				
				$res = pg_query($con, $sql);
				if (pg_errormessage($con))  {
					$msg_erro = traduz('ocorreu.um.erro.no.sistema', $con) . ', ' . traduz('contate.o.helpdesk', $con);
				}
			} else {
				$msg_erro = traduz('ocorreu.um.erro.no.sistema', $con) . ', ' . traduz('contate.o.helpdesk', $con);
			}
		}
	} else {
        if(strlen(trim($msg_erro)) == 0){
            $campo_cpf = "";
            $value_cpf = "";
            $campo_email = "";
            $value_email = "";

            $campo_telefone = "";
            $value_telefone = "";

            if(in_array($login_fabrica, array(169,170,178,184,191,193,198,200)) OR $app_ticket == "true" ){
                $campo_email = ", email";
                $value_email = ", '$email'";

                $campo_telefone = ", telefone";
                $value_telefone = ", '$telefone'";
                $flag_midea = false;
            }

            if((in_array($login_fabrica, array(169,170,178,184,191,193,198,200)) OR $app_ticket == "true" ) AND strlen(trim($xcpf)) > 0){
                if(strlen(trim($msg_erro)) == 0){
                    $campo_cpf = ", cpf";
                    $value_cpf = ", $xcpf";
                    
                    $sql_tecnico = "SELECT tecnico, posto FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND cpf = {$xcpf}";
                    $res_tecnico = pg_query($con, $sql_tecnico);

                    if(pg_num_rows($res_tecnico) > 0){
                        $tecnico_id_res = pg_fetch_result($res_tecnico, 0, 'tecnico');
                        $posto_id_res   = pg_fetch_result($res_tecnico, 0, 'posto');

                        if($app_ticket == "false"){
                        	$campo_ativo = ", tbl_posto_fabrica.ativo ";
                        }

                        if (!empty($posto_id_res)) {
                            $sql_posto = "SELECT 
                                          tbl_posto_fabrica.credenciamento
                                          $campo_ativo
                                    FROM  tbl_posto_fabrica
                                          INNER JOIN tbl_tecnico ON tbl_tecnico.tecnico = {$tecnico_id_res}
                                    WHERE tbl_posto_fabrica.fabrica             = {$login_fabrica}
                                          AND tbl_posto_fabrica.posto           = {$posto_id_res}
                                          AND tbl_posto_fabrica.credenciamento  = 'DESCREDENCIADO'";
                            $res_posto = pg_query($con, $sql_posto);
                            
                            if (pg_num_rows($res_posto) > 0) {
                                /* FAZ O INSERT */
                                $sql = "INSERT INTO tbl_tecnico(nome, fabrica, posto $campo_cpf $campo_email $campo_telefone) VALUES('$nome', $login_fabrica, $login_posto $value_cpf $value_email $value_telefone)";
                
                                $res = pg_query($con, $sql);

                                if (pg_errormessage($con))  {
                                    $msg_erro = traduz('ocorreu.um.erro.no.sistema', $con) . ', ' . traduz('contate.o.helpdesk', $con);
                                }
                            }
                        } else if ($posto_id_res != $login_posto) {
                            /* FAZ O INSERT */
                            $sql = "INSERT INTO tbl_tecnico(nome, fabrica, posto $campo_cpf $campo_email $campo_telefone) VALUES('$nome', $login_fabrica, $login_posto $value_cpf $value_email $value_telefone)";
            
                            $res = pg_query($con, $sql);

                            if (pg_errormessage($con))  {
                                $msg_erro = traduz('ocorreu.um.erro.no.sistema', $con) . ', ' . traduz('contate.o.helpdesk', $con);
                            }
                        } else {
                            $msg_erro .= "Já existe um técnico cadastrado com esse CPF";        
                        }
                    } else {
                        $flag_midea = true;
                    }
                }
            }

            if(strlen(trim($msg_erro)) == 0){
                if (!in_array($login_fabrica, [169,170]) || in_array($login_fabrica, [169,170]) && $flag_midea == true) {
                    $sql = "INSERT INTO tbl_tecnico(nome, fabrica, posto $campo_cpf $campo_email $campo_telefone $campos_codigo_externo) VALUES('$nome', $login_fabrica, $login_posto $value_cpf $value_email $value_telefone $value_codigo_externo)";
            
                    $res = pg_query($con, $sql);

                    if (pg_errormessage($con))  {
                        $msg_erro = traduz('ocorreu.um.erro.no.sistema', $con) . ', ' . traduz('contate.o.helpdesk', $con);
                    }
                }
            }
        }
    }

	if (strlen($msg_erro)) {
		$sql = "ROLLBACK";
		$res = pg_query($con, $sql);
	}
	else {
		$sql = "COMMIT";
		$res = pg_query($con, $sql);
		header("Location:$PHP_SELF");
	}
}
elseif ($btn_acao == "apagar") {
	$sql = "BEGIN";
	$res = pg_query($con, $sql);

	if (strlen($tecnico)) {
		$sql = "
		SELECT
		tbl_os.os

		FROM
		tbl_os
		JOIN tbl_tecnico ON tbl_os.tecnico=tbl_tecnico.tecnico
			 AND tbl_os.fabrica=tbl_tecnico.fabrica
			 AND tbl_os.posto=tbl_tecnico.posto

		WHERE
		tbl_tecnico.tecnico=$tecnico
		AND tbl_os.fabrica=$login_fabrica

		LIMIT 1
		";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res)) {
			$msg_erro = traduz('este.tecnico.ja.foi.selecionado.em.pelo.menos.uma.os.no.sistema', $con) .
						'<br />' .
						traduz('operacao.%.nao.permitida', $con, $cook_idioma, traduz('apagar', $con));
		}
		else {
			$sql = "UPDATE tbl_tecnico SET fabrica=0 WHERE tecnico=$tecnico";
			@$res = pg_query($con, $sql);

			if (pg_errormessage($con))  {
				$msg_erro = traduz('ocorreu.um.erro.no.sistema', $con) . ', ' . traduz('contate.o.helpdesk', $con);
			}
		}
	}

	if (strlen($msg_erro)) {
		$sql = "ROLLBACK";
		$res = pg_query($con, $sql);
	}
	else {
		$sql = "COMMIT";
		$res = pg_query($con, $sql);
		header("Location:$PHP_SELF");
	}
}
elseif (strlen($tecnico)) {

	$sql = "SELECT nome, ativo, cpf, telefone, email FROM tbl_tecnico WHERE tecnico=$tecnico";
	$res = pg_query($con, $sql);
	$nome = pg_result($res, "nome");
	$telefone = pg_result($res, "telefone");
	$email = pg_result($res, "email");
	$ativo = pg_result($res, "ativo");
	$cpf = pg_fetch_result($res, 0, 'cpf');

}
$title = traduz('cadastro.de.tecnicos', $con);

$layout_menu = "cadastro";
include 'cabecalho.php';



?>
<script language='JavaScript' src='js/jquery.js'></script>
<script language='JavaScript' src='js/jquery.maskedinput.js'></script>
<script src='plugins/shadowbox_lupa/shadowbox.js'></script>

<script type="text/javascript">

	var traducao = {
		aguarde: '<?=traduz('aguarde', $con)?>'
	}

	function enviarFrm(acao) {
		var frm = document.frm_tecnico;

		if (acao == undefined)
			return false;

		if (acao == 'reset') {
			window.location = window.location.pathname;
			return false;
		}

		if (frm.btn_acao.value == '') {
			frm.btn_acao.value = acao;
			frm.submit();
		} else {
			alert (traducao.aguarde);
		}
	}

	$(document).ready(function() {
		<?php
		if(in_array($login_fabrica, array(169,170))){
			?>
			$("#telefone").maskedinput("(99) 99999-9999");


			Shadowbox.init();

			$(".show-history").click(function(){

				// Parece que essa versão do Jquery ainda não suporta o data
				var tecnico = $(this).attr("data-tecnico");

				Shadowbox.open({
	                content: "historico_treinamento_tecnico.php?tecnico="+tecnico,
	                player: 'iframe',
	                width: 1024,
	                height: 600
            	});
			});



			<?php
		}
		?>
		$("#cpf").maskedinput("999.999.999-99");
		$("#cpf").blur();
		$('button').click(function() {
			enviarFrm($(this).attr('alt'));
		});
	});

</script>

<link rel='stylesheet' type='text/css' href='plugins/shadowbox_lupa/shadowbox.css' />
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_lst {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_lst {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

#cpf{
	text-align: center;
}

#telefone{
	text-align: center;
}

#email{
	width: 191px;
}

button {margin: auto 1ex;}
</style>

<?
if (strlen ($msg_erro) > 0) {
	echo "<table width='650' align='center' border='0' bgcolor='#ffeeee'  cellspacing='0' cellpadding='5' style='margin-bottom: 10px;'>";
	echo "<tr>";
	echo "<td align='center' class='error'>";
	if (strlen($msg_erro) > 0)

	echo $msg_erro;
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<table width='650' align='center' border='0' bgcolor='#d9e2ef'>
<tr>
	<td align='center'>
		<font face='arial, verdana' color='#596d9b' size='-1'>
			<?=fecho('para.incluir.um.novo.tecnico.preencha.o.nome.e.clique.em.gravar', $con)?>
		</font>
	</td>
</tr>
</table>

<form name="frm_tecnico" method="post" action="<? echo $PHP_SELF ?>">
<table width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td><b><?echo $erro;?></b></td>
	</tr>
</table>

<input type="hidden" name="tecnico" value="<? echo $tecnico ?>">

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="4"class="menu_top">
			<font color='#36425C'><? echo(mb_strtoupper(traduz('informacoes.cadastrais', $con),'ISO-8859-1'));?>
		</td>
	</tr>
	<tr class="menu_top">
		<td><? fecho('nome.do.tecnico', $con)?></td>
		<?php if(in_array($login_fabrica, array(169,170,178,184,191,193,198,200)) OR $app_ticket == "true" ){ ?>
			<td><? fecho('cpf', $con)?></td>
		<?php } ?>
	</tr>
	<tr class="table_line">
		<td>
			<!-- <input type="text" name="nome" size="30" maxlength="100" value="<? echo $nome ?>" onkeyup="somenteMaiusculaSemAcento(this);"> -->
			<input type="text" name="nome" size="30" maxlength="100" value="<? echo $nome ?>">
			<?php
			if ($edita == 1 && in_array($login_fabrica, array(59,148,158,169,170,178,184,191,198,200))) {
				$ckd = '';
				if ($ativo == "t") {
					$ckd = ' checked="checked" ';
				}
				echo '&nbsp;&nbsp;&nbsp;';
				echo '<input type="checkbox" name="ativo" value="t" ' ,  $ckd , ' /> ' . traduz('ativo', $con);
			}
			?>
		</td>
		<?php
			if(in_array($login_fabrica, array(169,170,178,184,191,193,198,200)) OR $app_ticket == "true" ){
				echo "<td>
						<input type='text' id='cpf' name='cpf' value='$cpf'/>
					</td>";
			}
		?>
	</tr>
	<?php
	if(in_array($login_fabrica, array(169,170,178,184,191,193,198,200)) OR $app_ticket == "true" ){
		?>
		<tr class="menu_top">
			<td><? fecho('email', $con)?></td>
			<td><? fecho('telefone', $con)?></td>		
		</tr>
					
		<tr class="table_line">
			<td>
				<input type='text' id='email' name='email' value='<?=$email?>'/>
			</td>	
			<td>
				<input type='text' id='telefone' name='telefone' value='<?=$telefone?>'/>
			</td>				
		</tr>
	<?php			
	}
	if($app_ticket == 'true'){ ?>
		<tr class="menu_top">
			<td colspan="2"><? fecho('Criar Acesso ao Aplicativo', $con)?></td>
		</tr>
		<tr class="table_line">
			<td colspan="2">
				<?php 

				$checked = "";

				if (isset($_GET['tecnico'])) {

					$queryAcesso = "SELECT codigo_externo
									FROM tbl_tecnico 
									WHERE tecnico = {$_GET['tecnico']}";

					$resAcesso = pg_query($con, $queryAcesso);

					$tecnico = pg_fetch_object($resAcesso);
					
					
					if (strlen($tecnico->codigo_externo) > 0) {

						$checked = "checked='true'";
					}

				} ?> 
				<input type='checkbox' id='acesso_app' name='acesso_app' value='t' <?= $checked ?> />
			</td>	
		</tr>
	<?php } 
	?>
</table>
<center>
	<input type='hidden' name='btn_acao' value=''>
	<button type="button" alt='gravar' title="<?fecho("gravar.formulario",$con,$cook_idioma);?>" style='cursor:pointer;'><? fecho('gravar', $con)?></button>

	<? if (strlen($tecnico)) { ?>
		<button type="button" alt='reset'  title="<?fecho("limpar.campos",$con,$cook_idioma);?>" style='cursor:pointer;'><? fecho('limpar', $con)?></button>
		<?php if(!in_array($login_fabrica, array(169,170,178,184,191,198,200))){ ?>
			<button type="button" alt='apagar' title="<?fecho('excluir.tecnico', $con)?>" style='cursor:pointer;'><? fecho('excluir', $con)?></button>
		<?php } ?>
	<? } ?>

</center>
</form>

<?

if(in_array($login_fabrica, array(169,170,178))){
	$cond_ativo = "AND tecnico NOT IN (
	SELECT tecnico
	FROM tbl_tecnico
	WHERE fabrica = $login_fabrica
	AND posto = $login_posto
	AND ativo IS FALSE
	AND cpf IS NULL)";
}

$sql = "SELECT tecnico, nome, ativo,cpf
		  FROM tbl_tecnico
		 WHERE fabrica = $login_fabrica
		   AND posto   = $login_posto
		   $cond_ativo
		 ORDER BY ativo DESC, nome ASC
";

$res = pg_exec($con,$sql);

if (pg_num_rows($res)) {
	if(in_array($login_fabrica, array(169,170,178,184,191,198,200))){
		$td_cpf_titulo = "<td colspan=''>CPF</td>";
	}
	echo "
		<br />
		<table width='650' align='center' border='0'>
			<tr>
				<td colspan='3' style='font-weight: bold; color: #FFFFFF; background-color: #6B6B6B;' >Inativo</td>
			</tr>
			<tr style='background-color: #596D9B; color: #FFFFFF;' >
				<th colspan='3' >Técnicos Cadastrados</th>
			</tr>

			<tr  style='background-color: #596D9B; color: #FFFFFF;'>
				<td colspan=''>NOME</td>
				{$td_cpf_titulo}
				<td>AÇÕES</td>
			</tr>

	";

	for ($i = 0; $i < pg_numrows($res); $i++) {
		$tecnico = pg_result($res, $i, tecnico);
		$nome = pg_result($res, $i, nome);
		$ativo = pg_result($res, $i, ativo);
		$numero_cpf = pg_fetch_result($res, $i, 'cpf');

		$inativo = ($ativo != "t") ? "style='background-color: #6B6B6B;'" : "";

		if(in_array($login_fabrica, array(169,170,178,184,191,198,200))){
			$td_cpf = "<td class='teste'>".maskCpf($numero_cpf,'###.###.###-##')."</td>";
		}else{
			$td_cpf = "";
		}

		$btn_ver_historico = "";
		if(in_array($login_fabrica, array(169,170))){
			$btn_ver_historico = "<button type='button' class='show-history' data-tecnico='$tecnico' >Ver Histórico</button>";
		}

		echo "
			<tr {$inativo} >
				<td>{$nome}</td>
				$td_cpf
				<td style='text-align: center;' >
					<button type='button' onclick='window.location = \"{$_SERVER['PHP_SELF']}?tecnico={$tecnico}\";' >Alterar</button>
					$btn_ver_historico
				</td>
			</tr>
		";

	}

	echo "</table>";
}

?>

<? include "rodape.php"; ?>
