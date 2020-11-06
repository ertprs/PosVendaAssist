<?php
/****************************************************************************************
 * Personaliza usuário                                                                  *
 * Esta tela vai ser  um apoio ao cadastro de Admin                                     *
 * O usuário vai poder:                                                                 *
 * - Cadastrar seu aniversário (com ou sem ano!)                                        *
 * - Cadastrar/alterar sua foto                                                         *
 * E qualquer outra novidade à respeito pode vir parar aqui.                            *
 *                                                                                      *
 * A tela pode ser chamada via IFRAME ou como tela cheia.                               *
 *                                                                                      *
 * <iframe src="admin_personaliza.php" width="800" height="80%"                         *
 * style="border:0;position:fixed;margin:auto;top:10%;left: 25%;height:80%;padding:0;"> *
 * </iframe>                                                                            *
 * Não precisa de parâmetros, pois só vai poder alterar o próprio usuário.              *
 ****************************************************************************************/

// Este escript SEMPRE deve ser um `include`.
if (!strlen($_SERVER['HTTP_REFERER']) and $_SERVER['SCRIPT_FILENAME'] === __FILE__) {
    $e = json_decode(mb_convert_encoding('{"code":401,"msg":"A página solicitada não está disponível","url":'.$_SERVER['HTTP_REFERER'].'}', 'utf8', 'latin1'));
    $errorMsg = "A página que está tentando acessar não está respondendo.";
    include(__DIR__ . DIRECTORY_SEPARATOR . '../40x.php');
    // echo gettype($e);
    // echo "<p>Deveria ser um include...</p>";
    die;
}

// O cabeçalho é usado na área do admin/bi/, aqui define os paths relativos,
// Pode ser usado dentro dos programas do BI para pegar as imagens do admin, também.
if (strpos($_SERVER['PHP_SELF'],'/bi/') == true || strpos($_SERVER['PHP_SELF'],'/admin_callcenter/') == true) {
	define('BI_BACK', '../');
} else {
	define('BI_BACK', '');
}

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';

include_once 'autentica_admin.php';
include_once 'funcoes.php';

// 07/08/2012 - Adicionada validação na regEx para celulares de SP
if (!defined('RE_FONE'))
	define('RE_FONE', '/^(\+?\d{2,3})?\s?(\()?0?[1-9]\d(\))?\s?[2-9]\d{2,3}[- .]?\d{4}|(\(?0?\d{2}\)?\s?(9[6-9]\d{3}|[2-5]\d{4})\W?\d{4})$/');

/**
 * 2016-11-04 - Mudando para TDocs
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . '../class/tdocs.class.php';

$fa = new TDocs($con, $login_fabrica, 'fa');
$fa->setContext('fa');

$arr_cook_avatar = explode('/', $cook_avatar);
$tDocsId = array_pop($arr_cook_avatar);
$situacao_tdocs = "";

$sql_foto = "SELECT situacao FROM tbl_tdocs WHERE fabrica = $login_fabrica AND tdocs_id = '$tDocsId'";
$res_foto = pg_query($con,$sql_foto);

	if (pg_num_rows($res_foto) > 0) { 
		$situacao_tdocs = pg_fetch_result($res_foto,0,'situacao');
	}

	if ($situacao_tdocs == 'ativo'){
		$link_foto = $cook_avatar;	
	}else{
		$link_foto = "";
	}

//$link_foto = $cook_avatar ?: $fa->getDocumentsByRef($login_admin)->url;
// $link_foto = $fa->getDocumentsByRef($login_admin)->url;

if (!$link_foto) {
	$link_foto = "$admin_fotos/tbl_admin.$login_admin.jpg";
	if (!file_exists($link_foto)) $link_foto = BI_BACK . '../imagens/sem_imagem.jpg';
}

$admin_fotos = BI_BACK . 'admin_fotos';

include_once BI_BACK . '../helpdesk/mlg_funciones.php';

$sql = "  SELECT admin, login, email, fone,
				 nome_completo  AS nome,
				 dia_nascimento AS dia,
				 mes_nascimento AS mes
			FROM tbl_admin
		   WHERE admin = $login_admin";
$res = pg_query($con, $sql);
extract(pg_fetch_assoc($res, 0), EXTR_PREFIX_ALL, 'usr');

// 21-03-2012 - À pedido do Gabriel Rolon, liberar a alteração de nome completo
//				QUANDO este campo estiver vazio (NULL ou '')
$libera_alteracao_nome = ($usr_nome == '' or is_null($usr_nome));

$dia = trim($usr_dia);
$mes = trim($usr_mes);

$msg_erro = Array();
if (count($_POST) or count($_FILES)) {
	
	if (getPost('acao') == 'atualizar_admin') {

		extract(array_map('anti_injection', $_POST));

		if (!is_email($email))
			$msg_erro[] = traduz("O e-mail fornecido é inválido.");

		/* Esta RegEx aceita:
		 *	+DDI DDD telefone
		 *	DDI DDD telefone
		 *	DDD telefone
		 *
		 *	onde DDI pode ser +XX, +XXX ou XX ou XXX
		 *		 DDD pode ser (0XX) (XX) 0XX ou XX (o primeiro X não pode ser 0)
		 *		 telefone pode ser un grupo de 7 ou 8 dígitos, ou dos grupos de 3-4 ou 4-4 dígitos ( o primeiro não pode ser 0 nem 1)
		 *	07/08/2012 - Adicionada validação na regEx para celulares de SP
		 ********/
		if (!preg_match(RE_FONE, $fone_contato))
			$msg_erro[] = traduz("O telefone digitado é inválido.");

		if ($dia_nascimento and $mes_nascimento) {
			if (!checkdate($mes_nascimento, $dia_nascimento, date('Y')))
				$msg_erro[] = traduz('Data de nascimento inválida');
		}

		if (!$dia_nascimento xor !$mes_nascimento)
			$msg_erro[] = traduz('Deve fornecer o DIA e o MÊS');

		if ($libera_alteracao_nome and strlen(trim($nome)) < 3)
			$msg_erro[] = traduz('O nome do usuário é obrigatório!');

		if ($libera_alteracao_nome)
			if ($nome       != $usr_nome) 	$camposUpdate['nome_completo']	= $nome;
		if ($email			!= $usr_email)	$camposUpdate['email']			= $email;
		if ($fone_contato	!= $usr_fone)	$camposUpdate['fone']			= $fone_contato;
		if ($dia_nascimento != $usr_dia)	$camposUpdate['dia_nascimento']	= (is_numeric($dia_nascimento)) ? str_pad($dia_nascimento, 2, '0', STR_PAD_LEFT) : null;
		if ($mes_nascimento != $usr_mes)	$camposUpdate['mes_nascimento']	= (is_numeric($mes_nascimento)) ? str_pad($mes_nascimento, 2, '0', STR_PAD_LEFT) : null;

		if (!count($msg_erro) and count($camposUpdate)) {
			$campos  = implode(',', array_keys($camposUpdate));
			$valores = implode(',', array_values(array_map('pg_quote', $camposUpdate)));
			if(count($camposUpdate) == 1){
				$sql = "UPDATE tbl_admin
                       SET $campos = $valores
					 WHERE admin     = $login_admin";
			}else{
				$sql = "UPDATE tbl_admin
                       SET ($campos) = ($valores)
					 WHERE admin     = $login_admin";
			}
			$res = pg_query($con, $sql);
			if (is_resource($res) and pg_affected_rows($res) == 1) {
				$msg = traduz('Dados atualizados!');
			} else {
				$msg_erro = traduz("Erro ao gravar seus dados") ."<br />";
			}

			// Se atualizou os dados, já tem nome, por tanto, não é mais liberado o campo para digitação!
			$libera_alteracao_nome = false;

			$usr_nome  = (isset($nome)) ? $nome : $usr_nome;
			$usr_fone  = $fone_contato;
			$usr_email = $email;
			$dia       = $dia_nascimento;
			$mes       = $mes_nascimento;

		} else {
			//Caso algum dado estiver errado...
			//Ou não haja nada para atualizar

			if (!count($camposUpdate)) $msg = traduz('Não foi alterada nenhuma informação.');
		}
	}
	if ($_FILES['newPhoto']['name']) {
		$arq = $_FILES['newPhoto'];
		$nome       = $arq['name'];
		$tamanho    = $arq['size'];
		$tipo       = $arq['type'];
		$temporario = $arq['tmp_name'];

		if (is_array($imgData = getimagesize($temporario))) {
			if (!in_array($imgData[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
				$msg_erro[] = traduz('Formato de imagem inválido. Apenas JPG ou PNG!');
			} elseif ($imgData[0] * $imgData[1] > 65536) { // Imagem > 64Kb ou 256px x 256px
				$msg_erro[] = traduz('Imagem muito grande, no máximo 256x256px');

			} else {
                $ext = pathinfo($nome, PATHINFO_EXTENSION);
				$arq['name'] = "admin.{$login_admin}.$ext";
				if ($fa->uploadFileS3($arq, $login_admin)) {
					$link_foto = $fa->getDocumentsByRef($login_admin)->url;
					$msg   = traduz("Imagem atualizada com êxito");
				} else {
					$msg_erro[] = traduz("Erro ao gravar a imagem!");
				}
			}
		} else {
			$msg_erro[] = traduz('Arquivo em formato desconhecido');
		}
	}
}

if (getPost('excluir')=='imagem') {
	// $tDocsId = preg_replace('/.*([0-9a-fA-F]{64})\/.*/', '$1', $link_foto);
	// if ($fa->removeDocumentById($tDocsId)) {
	$arr_cook_avatar = explode('/', $link_foto);
	$tDocsId = array_pop($arr_cook_avatar);
	//if ($fa->removeDocumentsByType($login_admin)) {
	if ($fa->removeDocumentById($tDocsId)) {
		$link_foto='../imagens/sem_imagem.jpg';
		$msg = traduz('Imagem excluída');
	} else {
		$msg_erro[] = traduz("Erro ao excluir a imagem ")."$tDocsId. {$fa->error}";
	}
}

if(getPost("acao") == "altera_senha"){
	$passwordErrors = array();
	
	$sql = "SELECT admin FROM tbl_admin WHERE admin = $login_admin AND senha = '".$_POST['password']."'";
	
	$res = pg_query($con, $sql);
	
	if(pg_num_rows($res) == 0){
		$passwordErrors[] = traduz("Sua senha atual está incorreta");
	}else{
		$senha         = $_POST['new-password'];
		$count_tudo    = 0;
		$count_letras  = 0;
		$count_numeros = 0;
		$numeros       = '0123456789';
		$letras        = 'abcdefghijklmnopqrstuvwxyz';
		$tudo          = $letras.$numeros;

		if($_POST['new-password'] != $_POST['new-password-verify']){
			$passwordErrors[] = traduz("Sua nova senha está diferente da senha confirmação");
		}

		//Confere o mínimo de 2 letras e dois números

		//- verifica qtd de letras e numeros da senha digitada -//
		$count_letras   = preg_match_all('/[a-z]/i', $senha, $a_letras);
		$count_numeros  = preg_match_all('/[0-9]/',  $senha, $a_nums);
		$count_invalido = preg_match_all('/\W/',     $senha, $a_invalidos);
		
		if ($count_letras + $count_numeros > 10)   $passwordErrors[] = traduz("Sua nova senha não pode ter mais que 10 caracteres");
		if ($count_letras + $count_numeros <  6)   $passwordErrors[] = traduz("Sua nova senha deve conter um mínimo de 6 caracteres");
		if ($count_letras < 2)  $passwordErrors[] = traduz("Sua nova senha deve ter pelo menos 2 letras");
		if ($count_numeros < 2) $passwordErrors[] = traduz("Sua nova senha deve ter pelo menos 2 números");

		if(count($passwordErrors)==0){
			$sql = "UPDATE tbl_admin SET senha = '".$_POST['new-password']."' WHERE admin = $login_admin";
			
			$update = pg_query($con, $sql);

			if(pg_last_error($con) AND pg_affected_rows($update) == 0){
				$passwordErrors[] = traduz("Ocorreu um erro ao atualizar a senha, tente novamente por favor");
			}else{
				$passwordMessage = traduz("Senha alterada com sucesso");
			}
		}
	}
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1">
	<title>Configuração do usuário</title>
	<link rel="stylesheet" href="../admin/css/admin_personaliza.css" />
	<script type="text/javascript">
	//This prototype is provided by the Mozilla foundation and
	//is distributed under the MIT license.
	//http://www.ibiblio.org/pub/Linux/LICENSES/mit.license

	if (!Array.prototype.indexOf)
	{
	  Array.prototype.indexOf = function(elt /*, from*/)
	  {
		var len = this.length;

		var from = Number(arguments[1]) || 0;
		from = (from < 0)
			 ? Math.ceil(from)
			 : Math.floor(from);
		if (from < 0)
		  from += len;

		for (; from < len; from++)
		{
		  if (from in this &&
			  this[from] === elt)
			return from;
		}
		return -1;
	  };
	}
	/*
	function ultimoDiaMes(el) {
		var mes    = el.value;
		var maxDia = '31';
		var meses30= array('4','6','9','11');

		if (meses30.indexOf(mes)>=0)
				maxDia = '30';
		if (mes == '2') maxDia = '29';

		document.getElementById('dia').setAttribute('max') = maxDia;
	}*/
	</script>
	<style type="text/css">
		#btn_change_passwd{
			background: #004fb1;
    		color: #fff;
    		margin-right: 56px;
    		border: 0px;
    		border-radius: 4px;
    		padding-right: 10px;
    		padding-left: 10px;
			cursor: pointer;
		}
		.label-el{
			width: 137px;
    		display: block;
    		text-align: right;
    		float: left;
    		margin-right: 10px;
		}
		.line {
    		margin-bottom: 10px;
    		margin-left: 125px;
		}
		.display-none{
			display: none;
		}
		#fotoUpload{
			    margin-top: 7px;
			    z-index: 50;
		}
	</style>
</head>
<body>
	<div id="userCfg">
		<h2 id="cfgHeader"><?=traduz('Informações do Usuário')?><button type="button" id='btnFechar' onClick='window.parent.toggleCustomizePopUp("admCfgFrm")'>X</button></h2>
		<div id="usrData">
			<img id="usrPic" src="<?=$link_foto?>" alt="Sua foto" />
			<form method='post' name="frm_info" enctype="multipart/form-data">
				<label for="nome"><?=traduz('Nome Completo')?></label>
			<?	if (!$libera_alteracao_nome) { ?>
				<span style='font-weight:bold;'
					  title='<?=traduz('O nome só pode ser alterado na tela de cadastro de Usuários, na aba Gerência')?>'><?=$usr_nome?></span><br />
			<?	} else  { ?>
				<input type="text" id="nome" name="nome" value="" required style='width:193px'
					pattern="[A-ÿ]+\s([A-ÿ]\s?)+"
			    placeholder="<?=traduz('Digite seu nome completo')?>" />
			<?	} ?>
				<label for="email"><?=traduz('E-Mail de contato')?></label>
				<input type="text" id='email' name='email' value='<?=$usr_email?>' required style='width:193px' maxlength='60'
				pattern="<?=substr(RE_EMAIL, 1, -1)?>"
				placeholder='<?=traduz("E-mail de contato")?>' /><br />
				<label for="fone_contato"><?=traduz('Telefone para contato')?></label>
				<input type="text" id='fone_contato' name='fone_contato' style='width:193px' value='<?=$usr_fone?>'
					pattern='<?=substr(RE_FONE, 1, -1)?>'
				placeholder='<?=traduz("Fone de contato")?>' /><br />
				<label for="dia"><?=traduz('Data de Aniversário')?></label>
				<input id="dia" name="dia_nascimento" type="number" min='1' max='31' size='4' value='<?=$dia?>' title='<?=traduz("Dia")?>' />&nbsp;do&nbsp;
				<input id="mes" name="mes_nascimento" type="number" min='1' max='12' size='4' value='<?=$mes?>' title='<?=traduz("Mês")?>' />
				<br />
				<br />
				<label for=""></label>
				<input name="acao" type="hidden" value='atualizar_admin' />				
				<button type='submit' id='btn_update'><?=traduz('Atualizar')?></button>
				<button type='button' id='btn_show_change' style="margin-left: 37px;"><?=traduz('Alterar Senha')?></button>
			</form>

			<br />

			<?
			$acaoUpload = 'Subir Imagem';
			if (strpos($link_foto, 'sem_') === false) { ?>
			<form method='POST' style='margin-left: 0px; margin-right: 10px; float: left;'>
				<input name="excluir" value='imagem' type="hidden" />
				<button id="btnExcluirImg"><?=traduz('Excluir')?></button>
			</form>
			<?	$acaoUpload = 'Alterar';
			}?>		
			<button type='button' id='newPic' onClick='document.getElementById("fotoUpload").style.display="block";this.style.display="none"'><?=$acaoUpload?></button>
			<div id="fotoUpload" style='display:none'>
				&nbsp;<?=traduz('Imagem em formato JPG, máximo 256 x 256 px')?>.
				<form enctype="multipart/form-data" method="POST">
					&nbsp;<input id="imagem" name="newPhoto" value='Imagem' type="file" accept="image/jpeg" />
					<button><?=$acaoUpload?></button>
				</form>
			</div>
			<p style='color:red;text-align:center'><?=(is_array($msg_erro))?implode('<br />',$msg_erro):$msg_erro?></p>
			<p style='padding-left: 2em'><?=$msg?></p>		
			<?php
			if($passwordMessage != ""){
			?>
				<p id="password-message" style="text-align: center;"><?=$passwordMessage?></p>
				<script type="text/javascript">
					setTimeout(function(){
						document.getElementById("password-message").setAttribute("style","display:none");	
					},5000);
					
				</script>
			<?php
			}
			?>
			<div id="changePasswd" <?=count($passwordErrors) > 0?"":'class="display-none"' ?> style="border-top: 3px solid #596d9b;;">
				<form method='POST' name="frm_passwd"> 
					<div class="line">
						<p onclick="alert('<?=traduz("Sua senha deve conter entre 6 e 10 caracteres, sendo necessário no mínimo 2 números e 2 letras. Ex: Tele12 ou 1234tc")?>')"  style="font-size: 16px;color: #000;"><?=traduz('Alteração de Senha')?> <img style="cursor: pointer" width="12" src="imagens/ajuda_call.png" title="<?=traduz("Sua senha deve conter entre 6 e 10 caracteres, sendo necessário no mínimo 2 números e 2 letras. Ex: 'Tele12' ou '1234tc'")?>"></p>
					</div>
					<?php
					if(count($passwordErrors) > 0){
					?>
					<div class="line">
						<p style="color: #f00;">
						<?php
						echo implode("<br>",$passwordErrors);
						?>
						</p>
					</div>
					<?php	
					}
					?>
					<div class="line">
						<label class="label-el" for="password"><?=traduz('Senha atual')?></label>
						<input type="password" id="password" name="password" value="" required="" style='width:175px'  placeholder="Digite sua senha atual" />	
					</div>		
					
					<div class="line">
						<label class="label-el" for="new-password"><?=traduz('Sua nova senha')?></label>
						<input type="password" id="new-password" name="new-password" value=""  required="" style='width:175px'  placeholder="Digite sua nova senha" />
					</div>
					<div class="line">
						<label class="label-el" for="new-password-verify"><?=traduz('Confirme sua nova senha')?></label>
						<input type="password" id="new-password-verify" name="new-password-verify" value=""  required="" style='width:175px'  placeholder="Confirme sua nova senha" />
					</div>
					<div class="line" style="text-align: right">
						<button type='submit' name="acao" value="altera_senha" id='btn_change_passwd'><?=traduz('Alterar Senha')?></button>
					</div>
				</form>			
			</div>
			<script type="text/javascript">
				if (typeof document.getElementById("btn_show_change").addEventListener != 'undefined') {
					document.getElementById("btn_show_change").addEventListener("click",function(){
						this.classList.add("display-none");
						document.getElementById("changePasswd").classList.remove("display-none");
					});	
				}
			</script>

		</div>
		
	</div>
<?  if ($msg_erro == 'Imagem excluída' or $alterou) { // Quando exclui a foto, recarrega a tela principal ?>
		<script type="text/javascript">
			var imgFoto = window.parent.document.getElementById('adm_foto')
			var imgLnk  = '<?=$link_foto?>';
			if (window.parent.location.href.indexOf('bi') > 0) imgLnk = '../' + imgLnk; // Diretório BI... volta um.

			imgFoto.src = imgLnk;
			imgFoto.title = (imgLnk.indexOf('em_i') > 0) ? 'Clique aqui para subir sua foto!' : '';
		</script>
<?	}?>
</body>
</html>

