<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia";

include "autentica_admin.php";
include 'funcoes.php';
require_once '../class/email/mailer/class.phpmailer.php';

include_once '../class/tdocs.class.php';

function validaCPF($cpf)
{	// Verifiva se o número digitado contém todos os digitos
    $cpf = str_pad(preg_replace('/[^0-9]/', '', $cpf), 11, '0', STR_PAD_LEFT);
	
	// Verifica se nenhuma das sequências abaixo foi digitada, caso seja, retorna falso
    if (strlen($cpf) != 11 || $cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' || $cpf == '33333333333' || $cpf == '44444444444' || $cpf == '55555555555' || $cpf == '66666666666' || $cpf == '77777777777' || $cpf == '88888888888' || $cpf == '99999999999')
	{
	return false;
    }
	else
	{   // Calcula os números para verificar se o CPF é verdadeiro
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf{$c} * (($t + 1) - $c);
            }

            $d = ((10 * $d) % 11) % 10;

            if ($cpf{$c} != $d) {
                return false;
            }
        }

        return true;
    }
}

#Pega o POST
if($_POST){



	$tipo_cnpj = $_POST["tipo_cnpj"];
	$ressarcimento	= $_POST['ressarcimento'];
	$os				= (empty($_POST['os_atendimento'])) ? "null" : $_POST['os_atendimento'];
	$hd_chamado		= (empty($_POST['atendimento'])) ? "null" : $_POST['atendimento'];
	$nome			= $_POST['nome'];
	$cpf			= $_POST['cpf'];
	$banco			= $_POST['banco'];
	$agencia		= $_POST['agencia'];
	$conta			= $_POST['conta'];
	$urgencia		= $_POST['urgencia'];
	$valor			= $_POST['valor'];
	$valor_original	= $_POST['valor_original'];
	$nota			= $_FILES['nota_fiscal'];
	$motivo			= $_POST['motivo'];
	$observacao		= $_POST['observacao'];
	$tipo_conta		= $_POST['tipo_conta'];

	$validaCPF = preg_replace("/\D/","",$cpf);
	$validaCPF = pg_query($con,"SELECT fn_valida_cnpj_cpf('$validaCPF')");

	if($validaCPF == FALSE){
		$msg_erro = "CPF/CNPJ inválido por favor informe um CPF/CNPJ válido <br />" ;
	}

	if(substr_count($valor, ',')) {
		$valor = str_replace('.','',$valor);
		$valor = str_replace(',','.',$valor);
	}

	$cpf = str_replace('.','',$cpf);
	$cpf = str_replace('-','',$cpf);
	$cpf = str_replace('/','',$cpf);
	
	$msg_erro .= (empty($nome))    ? "Informe o nome do consumidor <br />" : "";
	//$msg_erro .= (empty($cpf))     ? "Informe o CPF do consumidor <br />" : "";
	$msg_erro .= (empty($banco))   ? "Informe o banco para o depósito <br />" : "";
	$msg_erro .= (empty($tipo_conta)) ? "Informe o tipo da conta para depósito <br />" : "";
 	$msg_erro .= (empty($agencia)) ? "Informe a agência para depósito <br />" : "";
	$msg_erro .= (empty($conta))   ? "Informe a conta para depósito <br />" : "";
	$msg_erro .= (empty($valor))   ? "Informe o valor do ressarcimento <br />" : "";


	$sqlVerf = "SELECT ressarcimento ";


	if($os != 'null') {

		$sql = "SELECT os
				FROM tbl_os
				WHERE os = $os AND
					  fabrica = $login_fabrica";

		$res = pg_query($con,$sql);

		if(pg_num_rows($res) == 0) {
			$msg_erro = "Erro: OS não encontrada";
		}
	}

	if($os > 2147483647) {
		$msg_erro = "Erro: OS não encontrada";
	}

	if($hd_chamado > 2147483647) {
		$msg_erro = "Erro: Atendimento não encontrado";
	}
		
	if(empty($msg_erro)){
		$acao = '';

		$res = pg_query($con,"BEGIN");
		if(empty($ressarcimento)){
			$acao = 'incluir';
			$sql = "INSERT INTO tbl_ressarcimento(
													fabrica,
													os,
													hd_chamado,
													nome,
													cpf,
													banco,
													agencia,
													conta,
													valor_original,
													valor_alterado,
													urgencia,
													admin,
													observacao,
													tipo_conta
													)VALUES(
													$login_fabrica,
													$os,
													$hd_chamado,
													'$nome',
													'$cpf',
													$banco,
													'$agencia',
													'$conta',
													$valor,
													0,
													'$urgencia',
													$login_admin,
													'$observacao',
													'$tipo_conta') RETURNING ressarcimento;";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);

			if(strpos($msg_erro, "tbl_ressarcimento_hd_chamado_fkey") > 0) {
				$msg_erro = "Erro: Atendimento não encontrado";
			}

			if(!strlen($msg_erro)){
				$ressarcimento = pg_result($res,0,0);
			}

		}else{

			$campo_valor = ($valor <> $valor_original) ? " valor_alterado = $valor" : " valor_original = $valor ";

			if(empty($motivo)){
				$msg_erro = 'Informe o motivo da alteração';
			}
			if(empty($msg_erro)){
				$sql = "UPDATE tbl_ressarcimento SET
								os              = $os,
								hd_chamado      = $hd_chamado,
								nome            = '$nome',
								cpf             = '$cpf',
								banco           = $banco,
								conta           = '$conta',
								agencia         = '$agencia',
								$campo_valor,
								urgencia        = '$urgencia',
								admin_altera    = $login_admin,
								data_alteracao  = CURRENT_TIMESTAMP,
								motivo          = '$motivo',
								observacao		= '$observacao',
								tipo_conta		= '$tipo_conta'
							WHERE ressarcimento = $ressarcimento";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
			}

		}
	}
	
	if(empty($msg_erro)){
		if($_FILES['nota_fiscal']['name']){

			$extensao = explode('/',$_FILES['nota_fiscal']['type']);

			if(!in_array($extensao[1],array('pdf','jpg','jpeg','pjpeg','gif','png'))){

				$msg_erro .= "Tipo de arquivo inválido. Os tipos permitidos são: PDF,JPG,JPEG,GIF,PNG";

			}else{

				$tamanho = 2048000; // Tamanho máximo do arquivo (em bytes)

				if ($_FILES['nota_fiscal']["size"] > $tamanho){

					$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";

				}

				/* $arqNotaTemp = $_FILES['nota_fiscal']['tmp_name'];
				$diretorio = 'ressarcimento_comprovante/';
				list($a,$ext) = explode('/',$_FILES['nota_fiscal']['type']);
				$arqName = $ressarcimento."_nf.".$ext;
				$arqName = $diretorio . $arqName;
				if(!move_uploaded_file($arqNotaTemp, $arqName)){
					$msg_erro = "Erro ao anexar Nota Fiscal";
				} */

			}
		}
	}

	if(empty($msg_erro)){

		$res = pg_query($con,"COMMIT");

		if ($_FILES['nota_fiscal']['name']) {
			$tDocs = new TDocs($con, $login_fabrica);

			$anexoID = $tDocs->uploadFileS3($_FILES['nota_fiscal'], $ressarcimento, true, "ressarcimento");

			if (!$anexoID) {

				$msg_erro .= 'Erro ao salvar o arquivo!';

			}
		}

		// HD-940807
		if($telecontrol_distrib and $acao == 'incluir') {

			$mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor

			// Envia e-mail
			if($login_fabrica != 81){				
				$email_para = array("claudio.silva@telecontrol.com.br");	
				//$email_para = array("felipe.marttos@telecontrol.com.br");	teste
			}else{
				$email_para = array("juliane.santosdasilva@la.spectrumbrands.com", "carlos.uzeda@bestwaybrasil.com.br");	
			}
						
			$assunto	= "Ressarcimento Pendente de Aprovação" . ($hd_chamado == "null" ? '' : (' - ' . $hd_chamado)). " - " . $login_fabrica_nome;
			$mensagem	= "Foi cadastrado um novo ressarcimento, o mesmo está pendente aguardando aprovação, favor verificar.";
			$mensagem  .= "<br/><br/>OS: " . ($os == "null" ? 'Não informado' : $os);
			$mensagem  .= "<br/>Atendimento: " . ($hd_chamado == "null" ? 'Não informado' : $hd_chamado);
			$mensagem  .= "<br/>Nome do consumidor: " . (empty($nome) ? 'Não informado' : $nome);

			$mailer->IsSMTP();
		    $mailer->IsHTML();

		    foreach ($email_para as $email) {		    	
		   		$mailer->AddAddress($email);
		    }    		    

		    $mailer->Subject = $assunto;
		    $mailer->Body 	 = $mensagem;

		    if (!$mailer->Send()) {              
		        $msg_erro = "Ocorreu um erro durante o envio de e-mail.";		     
		    }		    
		}

		if ($login_fabrica == 101 && empty($msg_erro)) {
			$aux_sql = "SELECT consumidor_celular FROM tbl_os WHERE fabrica = $login_fabrica AND os = $os LIMIT 1";
			$aux_res = pg_query($con, $aux_sql);

			if (pg_num_rows($aux_res) > 0) {
				$contato = pg_fetch_result($aux_res, 0, 0);
			}

			if (empty($contato) && !empty($hd_chamado)) {
				$aux_sql = "SELECT celular FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado LIMIT 1";
				$aux_res = pg_query($con, $aux_sql);

				if (pg_num_rows($aux_res) > 0) {
					$contato = pg_fetch_result($aux_res, 0, 0);
				}
			}

			if (!empty($contato)) {
				include_once "../class/sms/sms.class.php";
				
				$sms = new SMS();
				$mensagem_sms = "
					Olà, somos da Delonghi/Kenwood, estamos entrando em contato referente a O.S. $os. em breve você receberá um contato telefonico de um consultor Delonghi para lhe passar mais informações. Obrigado!
				";
				$enviar_sms = $sms->enviarMensagem($contato, '', '', $mensagem_sms);
				
				if ($enviar_sms) {
					$msg = "Cadastro efetuado com sucesso.<br>SMS Enviado para o consumidor!";
				} else {
					$msg = "Erro ao enviar SMS ao consumidor";
				}
			} else {
				$msg = "Cadastro efetuado com sucesso.";
			}
			
			$aux_sql = "UPDATE tbl_os SET data_fechamento = current_timestamp, finalizada = current_timestamp WHERE os = $os AND fabrica = $login_fabrica";
			$aux_res = pg_query ($con,$aux_sql);
		} else {
			$msg = "Cadastro efetuado com sucesso";
		}
		header("location: ressarcimento_cadastro.php" . (empty($msg_erro) ? "?msg=$msg" : '?msg=$msg_erro'));

		
	}else{
		$res = pg_query($con,"ROLLBACK");
	}

}

if(!empty($_GET['ressarcimento']) && empty($msg_erro)){
	$ressarcimento = $_GET['ressarcimento'];
	$sql = "SELECT os,
					hd_chamado,
					nome,cpf,
					banco,
					agencia, 
					conta, 
					valor_original,
					valor_alterado,
					CASE
						WHEN valor_alterado > 0 THEN
							valor_alterado
						ELSE
							valor_original
					END AS valor,
					urgencia, 
					CASE
						WHEN admin_altera is not null THEN
							admin_altera
						ELSE
							admin
					END AS admin,
					CASE 
						WHEN aprovado is null THEN
							'Pendente'
						WHEN finalizado is not null THEN
							'Finalizado'
						ELSE
							'Aprovado'
					END AS status,
					TO_CHAR(aprovado,'DD/MM/YYYY') AS aprovado, 
					TO_CHAR(finalizado,'DD/MM/YYYY') AS finalizado,
					motivo,
					observacao,
					tipo_conta,
					anexo
				FROM tbl_ressarcimento 
				WHERE ressarcimento = $ressarcimento 
				AND fabrica = $login_fabrica";
	$res = pg_query($con,$sql);

	if(pg_numrows($res) > 0){
		$os				= pg_result($res,0,'os');
		$hd_chamado		= pg_result($res,0,'hd_chamado');
		$nome			= pg_result($res,0,'nome');
		$cpf			= pg_result($res,0,'cpf');
		$banco			= pg_result($res,0,'banco');
		$agencia		= pg_result($res,0,'agencia');
		$conta			= pg_result($res,0,'conta');
		$valor_original = pg_result($res,0,'valor_original');
		$valor_alterado = pg_result($res,0,'valor_alterado');
		$valor			= pg_result($res,0,'valor');
		$urgencia		= pg_result($res,0,'urgencia');
		$admin			= pg_result($res,0,'admin');
		$admin_altera	= pg_result($res,0,'admin_altera');
		$status			= pg_result($res,0,'status');
		$aprovado		= pg_result($res,0,'aprovado');
		$finalizado		= pg_result($res,0,'finalizado');
		$motivo			= pg_result($res,0,'motivo');
		$observacao		= pg_result($res,0,'observacao');
		$tipo_conta		= pg_result($res,0,'tipo_conta');
		$anexo			= pg_result($res,0,'anexo');
	}
}
$os = ($os == "null") ? "" : $os;
$hd_chamado = ($hd_chamado == "null") ? "" : $hd_chamado;

/*HD-4096786*/
if ($_POST["ajax_pesquisar_consumidor"]) {
	$tipo  = $_POST["tipo"];
	$valor = $_POST["valor"];

	if ($tipo == "os") {
		$aux_sql = "SELECT consumidor_nome, consumidor_cpf FROM tbl_os WHERE fabrica = $login_fabrica AND os = $valor LIMIT 1";
	}

	if ($tipo == "atendimento") {
		$aux_sql = "
			SELECT tbl_hd_chamado_extra.nome AS consumidor_nome, tbl_os.consumidor_cpf 
			FROM tbl_hd_chamado_extra
			LEFT JOIN tbl_os USING(os)
			WHERE tbl_hd_chamado_extra.hd_chamado = $valor LIMIT 1
		";
	}

	$aux_res = pg_query($con, $aux_sql);

	if (pg_num_rows($aux_res) > 0) {
		$consumidor_nome = pg_fetch_result($aux_res, 0, 'consumidor_nome');
		$consumidor_cpf = pg_fetch_result($aux_res, 0, 'consumidor_cpf');

		echo "ok|$consumidor_nome|$consumidor_cpf";
	}

	if (pg_last_error()) {
		echo "ko|Erro ao localizar os dados do consumidor";
	}

	exit;
}

$layout_menu = "gerencia";
$title = "CADASTRO DE RESSARCIMENTO";
include "cabecalho.php";

?>

<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:bold 11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco_left{
	padding-left:40px;
}

</style>

<script type='text/javascript' src='js/jquery-1.6.1.min.js'></script>
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type='text/javascript' src='js/jquery.maskedinput.js'></script>
<script type='text/javascript' src='js/jquery.maskmoney.js'></script>
<script type='text/javascript'>
	$(function(){
		
		$("input[name=cpf]").focusout(function () {

			var cpf = $("input[name=cpf]").val();
			$.post("service_valida_ressarcimento.php", {cpf:cpf} , function(data){	
				if(data.status == 2){
					$("#erroCPF").fadeOut("slow");
				}
				if(data.status == 1){
					if(data.hdchamado != null && data.suaos != null){
						$("#msgErroCPF").html("Este CPF já esta sendo utilizado pelo <b>Atendimento número:</b> " + data.hdchamado + " e <b>OS:</b> "+data.suaos);
					}
					if(data.hdchamado != null && data.suaos == null){
						$("#msgErroCPF").html("Este CPF já esta sendo utilizado pelo <b>Atendimento número:</b> " + data.hdchamado);
					}
					if(data.hdchamado == null && data.suaos != null){
						$("#msgErroCPF").html("Este CPF já esta sendo utilizado pela <b>OS:</b> "+data.suaos);
					}
					$("#erroCPF").fadeIn("slow");
				}
				
			},"json");
		});
		$("input[name=os_atendimento]").numeric();
		$("input[name=atendimento]").numeric();
		$("input[name=agencia]").numeric({allow:".-"});
		$("input[name=conta]").numeric({allow:".-"});

		if ($("input[name=tipo_cnpj]:checked").val() == "cpf") {
			$("input[name=cpf]").maskedinput("999.999.999-99");
		} else {
			$("input[name=cpf]").maskedinput("99.999.999/9999-99");
		}

		$("input[name=tipo_cnpj]").change(function () {
			$("input[name=cpf]").unmask();

			if ($(this).val() == "cpf") {
				$("input[name=cpf]").maskedinput("999.999.999-99");
			} else {
				$("input[name=cpf]").maskedinput("99.999.999/9999-99");
			}

			$("input[name=cpf]").val("");
		});
		
		$("input[name=valor]").maskMoney({showSymbol:true, symbol:"R$", decimal:",", thousands:".",precision:2, maxlength:11});
	});

	/*HD-4096786*/
	function fnc_pesquisar_consumidor_lupa(tipo, valor) {
		$.ajax({
            url: "<?=$_SERVER['PHP_SELF'];?>",
            type: "POST",
            data: {ajax_pesquisar_consumidor: true, tipo: tipo, valor: valor},
        }).fail(function(){
            alert("Erro ao pesquisar o consumidor!");
        }).done(function(data){
        	data = data.split('|');
        	if (data[0] == "ok") {
        		var consumidor_nome = data[1]
        		var consumidor_cpf = data[2];

        		$("input[name=nome]").val(consumidor_nome);
        		$("input[name=cpf]").val(consumidor_cpf);
        	} else {
        		alert(data[1]);
        		console.log(data[1]);
        	}
        });
	}
</script>

<?php if(!empty($msg_erro)){ ?>
	<table align="center" width="700">
		<tr class='msg_erro'><td><?=$msg_erro?></td></tr>
	</table>
<?php } ?>

<?php if(!empty($_GET['msg'])){ ?>
	<table align="center" width="700">
		<tr class='sucesso'><td><?=$_GET['msg']?></td></tr>
	</table>
<?php } ?>
<div id="erroCPF" style="display:none;">
	<table align="center" width="700">
		<tr class='msg_erro'><td><div id="msgErroCPF" style="font-size:14px;"></div></td></tr>
	</table>
</div>

<form name="frm_cadastro" method="post" enctype="multipart/form-data">
	<table width="700" align="center" class="formulario">
		<caption class="titulo_tabela">Cadastro</caption>

		<tr><td colspan='3'>&nbsp;</td></tr>

		<tr>
			<td class="espaco_left" width='200'>
				Ordem de Serviço <br /> <input type="text" id="os_atendimento" name="os_atendimento" value="<?=$os?>" class="frm">
				<?php
					if ($login_fabrica == 101) {
						?><img src="imagens/lupa.png" title="Buscar" onclick="javascrip: if (document.getElementById('os_atendimento').value == '') alert('Preencha o número da O.S'); else fnc_pesquisar_consumidor_lupa('os', document.getElementById('os_atendimento').value)" style="cursor:pointer;"> <?php
					}
				?>
			</td>
			<td colspan='2'>
				Atendimento <br /> <input type="text" id="atendimento" name="atendimento" value="<?=$hd_chamado?>" class="frm">
				<?php
					if ($login_fabrica == 101) {
						?><img src="imagens/lupa.png" title="Buscar" onclick="javascrip: if (document.getElementById('atendimento').value == '') alert('Preencha o número do atendimento'); else fnc_pesquisar_consumidor_lupa('atendimento', document.getElementById('atendimento').value)" style="cursor:pointer;"> <?php
					}
				?>
			</td>
		</tr>
		
		<tr><td colspan='3'>&nbsp;</td></tr>
	</table>
	<table width="700" align="center" class="formulario">
		<tr>
			<td class="espaco_left" width='350'>
				Nome <br /> <input type="text" name="nome" value="<?=$nome?>" size="52" class="frm">
			</td>
			<td style="text-align: left">
				CPF/CNPJ <br /> <input type="text" name="cpf" value="<?=$cpf?>" size="20" class="frm"> <br />
				<input type="radio" name="tipo_cnpj" value="cpf" <?=(($tipo_cnpj == "cpf" or !isset($tipo_cnpj)) ? "CHECKED" : "")?> /> CPF &nbsp; <input type="radio" name="tipo_cnpj" value="cnpj" <?=(($tipo_cnpj == "cnpj") ? "CHECKED" : "")?> /> CNPJ
			</td>
		</tr>
		
		<tr><td colspan='3'>&nbsp;</td></tr>
	</table>
	<table width="700" align="center" class="formulario">
		<tr>
			<td class="espaco_left" colspan='2'>
				Banco <br /> 
				<select name="banco" class="frm">
					<option value="">Selecione um Banco</option>
					<?php
						$sql = "SELECT banco, codigo,nome FROM tbl_banco ORDER BY codigo";
						$res = pg_query($con,$sql);
						for($i = 0; $i < pg_numrows($res); $i++){
							$banco_id		= pg_result($res,$i,'banco');
							$codigo_banco	= pg_result($res,$i,'codigo');
							$nome_banco		= pg_result($res,$i,'nome');
							
							$checked_banco = ($banco_id == $banco) ? "SELECTED" : "";
							echo "<option value='$banco_id' $checked_banco>$codigo_banco - $nome_banco</option>";
						}
					?>
				</select>
			</td>
		</tr>

		<tr><td colspan='3'>&nbsp;</td></tr>

		<tr>
			<td class="espaco_left" width='200'>
				Tipo de Conta <br />
				<select class='frm' name='tipo_conta'>
					<option value='' selected></option>
					<option value='Conta conjunta'   <? if ($tipo_conta == 'Conta conjunta')   echo "selected"; ?>>Conta conjunta</option>
					<option value='Conta corrente'   <? if ($tipo_conta == 'Conta corrente')   echo "selected"; ?>>Conta corrente</option>
					<option value='Conta individual' <? if ($tipo_conta == 'Conta individual') echo "selected"; ?>>Conta individual</option>
					<option value='Conta jurídica'   <? if ($tipo_conta == 'Conta jurídica')   echo "selected"; ?>>Conta jurídica</option>
					<option value='Conta poupança'   <? if ($tipo_conta == 'Conta poupança')   echo "selected"; ?>>Conta poupança</option>
				</select>
			</td>
			<td width='200'>
				Agência <br /> <input type="text" name="agencia" value="<?=$agencia?>" class="frm">
			</td>
			<td>
				Conta <br /> <input type="text" name="conta" value="<?=$conta?>" class="frm">
			</td>
		</tr>
		
		<tr><td colspan='3'>&nbsp;</td></tr>
	</table>
	<table width="700" align="center" class="formulario">
		<tr>
			<td class="espaco_left" width='200'>
				Urgência <br /> 
				<select name='urgencia' class="frm">
					<option value='alta'  <? echo ($urgencia == 'alta') ? 'selected' : ''; ?>>Alta</option>
					<option value='media' <? echo ($urgencia == 'media') ? 'selected' : ''; ?>>Média</option>
					<option value='baixa' <? echo ($urgencia == 'baixa') ? 'selected' : ''; ?>>Baixa</option>
				</select>
			</td>
			<td colspan='2'>
				Valor <br /> <input type="text" name="valor" value="<?=$valor?>" class="frm">
				<input type='hidden' name='valor_original' value="<?=$valor_original?>">
				<?php
					echo ($ressarcimento AND $valor_alterado > 0) ? "Valor Original : ".number_format($valor_original,2,',','.') : "";
				?>
			</td>
		</tr>

		<tr><td colspan='3'>&nbsp;</td></tr>

		<tr>
			<td colspan='3' class="espaco_left">
				Nota Fiscal <br /> <input type="file" name="nota_fiscal" class="frm">
			</td>
		</tr>
		
		<tr><td colspan='3'>&nbsp;</td></tr>

		<tr>
			<td colspan='3' class="espaco_left">
				Observação <br /> <input type='text' name='observacao' value="<?=$observacao?>" size="80" class='frm'>
			</td>
		</tr>
		
		<?php if($ressarcimento){ 
			foreach (glob("ressarcimento_comprovante/".$ressarcimento."_nf.*") as $filename) {
				$anexo_nf = $filename;
			}

		?>
		<tr><td colspan='3'>&nbsp;</td></tr>

		<tr>
			<td colspan='3' class="espaco_left">
				Motivo <br /> <input type='text' name='motivo' value="<?=$motivo?>" size='80'class='frm'>
			</td>
		</tr>

		<?php

		if(strlen($anexo_nf) == 0){

			if(strlen($ressarcimento) > 0){

				$tDocs = new TDocs($con, $login_fabrica);

				$info = $tDocs->getdocumentsByRef($ressarcimento, "ressarcimento");
				$anexo_nf = $info->url;

			}

		}

		?>

		<tr>
			<td colspan='3' align='center'>
				<br />
				<a href='<?=$anexo_nf?>' target='_blank'><img src='<?=$anexo_nf?>' style='width:60px;border-radius: 3px;'> <br /> Nota Fiscal</a>
			</td>
		</tr>

		<?php } ?>

		<tr><td colspan='3'>&nbsp;</td></tr>

		<tr>
			<td colspan='3' align='center'>
				<input type="hidden" name="ressarcimento" value="<?=$ressarcimento?>">
				<input type='submit' value='Gravar'>
				<input type='button' value='Limpar' onclick="javascript: window.location='ressarcimento_cadastro.php'">
			</td>
		</tr>

		<tr><td colspan='3'>&nbsp;</td></tr>

	</table>
</form>


<?php include "rodape.php"; ?>
