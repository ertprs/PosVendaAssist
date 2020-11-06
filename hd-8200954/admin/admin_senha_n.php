<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

//$admin_privilegios="gerencia";
include 'autentica_admin.php';

include_once 'funcoes.php';
include_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'class/AuditorLog.php';
include_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'class/json.class.php';


//ajax_calcula 
if ($_GET['ajax_calcula']) {
	$retorno = calculaAdmin();
	exit(json_encode($retorno));
}


if ($_GET['ajax_qtde']) {
	$qtde_admin = $_GET['qtde_admin'];
	if(!empty($qtde_admin)) {

		$qtd = calculaAdmin();

		echo ($qtd['qtd_admins_disponiveis'] >= 0) ? "ok" : "no";

		exit;
	}else{
		echo "ok";
	}
	exit;
}

/***************************
 * Configura colunas:      *
 * recebe fale conosco     *
 * atende chamados postos, *
 * atende callcenter, etc. *
 ***************************/
$fabrica_multinacional    = in_array($login_fabrica, array(20));             // A princípio, B&D e Intelbras também deveriam, mas não há uso por enquanto
$usa_recebe_fale_conosco  = in_array($login_fabrica, array(1,11,24,35,81,86,114,137,172));
$usa_atendente_callcenter = (in_array($login_fabrica, array(24,81,114,122,125,174,175,177,183,189)) or $replica_einhell);
$usa_intervensor          = in_array($login_fabrica, array(11,24,30,85,151,172));

$usa_atende_hd_postos     = (in_array($login_fabrica, array(1,3,11,30,42,45,74,151,153,169,170,172,178,183)) OR $helpdeskPostoAutorizado);

$usa_responsavel_postos   = $login_fabrica; /* HD 2284242 - Liberado essa opção para todas as fabricas */
$abre_os_admin_arr        = in_array($login_fabrica, array(7,30,52,85,96,156,158,167)); // HD 372098
$usa_altera_pais_produto  = in_array($login_fabrica, array(20)); // HD 374998 - MLG 2011-11-09 Mudei de lugar, vai que alguém mais quer...
$manda_email_assinatura   = in_array($login_fabrica, array(86)); // HD 1976544
$usa_admin_ramal          = in_array($login_fabrica, array(129));
$fabrica_assina_extrato   = in_array($login_fabrica, array(1));
$usa_somente_callcenter   = in_array($login_fabrica, array(85));

$list_inativo = $_GET['list_inativo'];
if ($login_fabrica == 10 and $_serverEnvironment == 'development') {
	$fabrica_assina_extrato = true;
	$admin_aprova_extrato   = true;
}

if ($fabrica_assina_extrato) {
	$admin_aprova_extrato   = $admin_parametros_adicionais['aprova_extrato'];
	$admin_aprova_protocolo = $admin_parametros_adicionais['aprova_protocolo'];
}

$btn_acao  = strtolower($_POST["btn_acao"]);
$xbtn_acao = strtolower($_POST["xbtn_acao"]);

$debug = 't';

/**
* @author William Castro <william.castro@telecontrol.com.br>
* hd-6641542 
* Funcao verifica se a fabrica deve ou nao mostrar os campos de quantidade
* @return permissão, TRUE OU FALSE
* 
*/

function validaAdmin() {

	global $con, $login_fabrica;

	$fab_com_adicionais = "SELECT parametros_adicionais
						   FROM tbl_fabrica 
						   WHERE parametros_adicionais is not null 
						   AND fabrica = {$login_fabrica}
						   AND parametros_adicionais::jsonb->'qtde_admin' IS NOT NULL";

	$res_com_adicionais = pg_query($con, $fab_com_adicionais);

	if (pg_num_rows($res_com_adicionais) > 0) {

		$res_adicionais = pg_fetch_all($res_com_adicionais);

		$res_json = json_decode($res_adicionais[0]['parametros_adicionais']);

		if ($res_json->qtde_admin > 0) { 
			 
			return true;
		}
	} 

	return false;
}

function valida_celular($celular) {

	$celular = str_replace(["(",")","-", " "], "", $celular);

	if (strlen(trim($celular)) > 0 && strlen(trim($celular)) == 11) {
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

		$celular          = $phoneUtil->parse("+55".$celular, "BR");
		$isValid          = $phoneUtil->isValidNumber($celular);
		$numberType       = $phoneUtil->getNumberType($celular);
		$mobileNumberType = \libphonenumber\PhoneNumberType::MOBILE;

		if (!$isValid || $numberType != $mobileNumberType) {
			return "Número de Celular Inválido ! Válido Somente Números do Brasil. <br />";
		}
		
	} else if (strlen(trim($celular)) > 0 && strlen(trim($celular)) != 11) {
		return "Número de Celular Inválido ! Válido Somente Números do Brasil. <br />";
	}
}

function valida_fone($fone) {

	if (strlen($fone) > 0) {
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

		$fone             = $phoneUtil->parse("+55".$fone, "BR");
		$isValid          = $phoneUtil->isValidNumber($fone);
		$numberType       = $phoneUtil->getNumberType($fone);
		$mobileNumberType = \libphonenumber\PhoneNumberType::FIXED_LINE;

		if (!$isValid || $numberType != $mobileNumberType) {
			return "Número de Telefone inválido <br />";
		}

	}
}

function formataLabelTelefone($num) 
{
	$num = str_replace(" ", "", $num);
	$numFinal = "(";
	$hifen    = 7;
	$tamNum   = strlen($num);

	if ($tamNum == 0) {
		return "";
	}

	if ($tamNum == 10) {
		$hifen = 6;
	}

	for ($i = 0; $i < $tamNum; $i++) {

		if ($i == 2) { 
			$numFinal = $numFinal . ') ';
		} if ($i == $hifen) {
			$numFinal = $numFinal . '-' . $num[$i];
		} else {
			$numFinal = $numFinal . $num[$i];
		}
	}	
	
	return $numFinal;
}
/**
 * @author William Castro <william.castro@telecontrol.com.br>
 * hd-6641542 
 * Calcula qtd de admins disponiveis 
 * @return qtd_admins_disponiveis int
 * @return qtd_contratadas
 */

function calculaAdmin() {

	global $con, $login_fabrica;

	$sql_contratadas = "SELECT parametros_adicionais::jsonb->>'qtde_admin' as total
						   FROM tbl_fabrica 
						   WHERE parametros_adicionais is not null 
						   AND fabrica = {$login_fabrica}
						   AND parametros_adicionais::jsonb->'qtde_admin' IS NOT NULL";

	$res_contratadas = pg_query($con, $sql_contratadas);

	$qtd_contratadas = pg_fetch_result($res_contratadas, 0, 'total');


	if($login_fabrica == 191){
		$cond = " AND cliente_admin IS NULL ";
	}

  	$slq_disponiveis = "SELECT count(*) AS total
			      		FROM tbl_admin 
			      		WHERE fabrica = $login_fabrica 
			      		AND tbl_admin.ativo = 't'
			      		$cond";

	$res_disponiveis = pg_query($con, $slq_disponiveis);

	$qtd_admins_ativos = pg_fetch_result($res_disponiveis, 0, 'total');

	$qtd_admins_disponiveis = $qtd_contratadas - $qtd_admins_ativos;

	if(empty($qtd_contratadas)) $qtd_admins_disponiveis = 1;

	$qtde_callcenter = 0 ;
	$qtde_outros = 0 ;
	if($login_fabrica == 169) {
		$sqlc = "SELECT count(1) FROM tbl_admin where fabrica = $login_fabrica and privilegios='call_center' and ativo";
		$resc = pg_query($con, $sqlc); 
		$qtde_callcenter = pg_fetch_result($resc,0,0); 
		$sqlc = "SELECT count(1) FROM tbl_admin where fabrica = $login_fabrica and privilegios!='call_center' and ativo";
		$resc = pg_query($con, $sqlc); 
		$qtde_outros = pg_fetch_result($resc,0,0); 
	}

	return  [ 
				"qtd_admins_disponiveis" => $qtd_admins_disponiveis, 
				"qtd_contratadas" => $qtd_contratadas,
				"qtde_callcenter" => $qtde_callcenter,
				"qtde_outros" => $qtde_outros,
			];
}

/************************************
 * Devolve TRUE se a senha é válida *
 * (de 6 a 10 caracteres, mínimo de *
 * 2 letras E 2 dígitos)            *
 * Mensagem de erro se houve erro   *
 ************************************/
function validaSenhaAdmin($senha, $login) {

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

	if ($count_letras + $count_numeros > 10)   $msg_erro .= traduz("Senha inválida, a senha não pode ter mais que 10 caracteres para o LOGIN $login <br>");
	if ($count_letras + $count_numeros <  6)   $msg_erro .= traduz("Senha inválida, a senha deve conter um mínimo de 6 caracteres para o LOGIN $login <br>");
	if ($count_letras < 2)  $msg_erro .= traduz("Senha inválida, a senha deve ter pelo menos 2 letras para o LOGIN $login <br>");
	if ($count_numeros < 2) $msg_erro .= traduz("Senha inválida, a senha deve ter pelo menos 2 números para o LOGIN $login <br>");

	return (!empty($msg_erro)) ? $msg_erro : true;

}

if (isset($_GET["ver_assinatura"])) { 
	if ($_GET["ver_assinatura"] != "") {
		
		require_once '../class/tdocs.class.php';
		
		$msg_assinatura = "";
		$adm_id = $_GET["ver_assinatura"];
		$tdocs = new TDocs($con, $login_fabrica, 'assinatura');
		$img_assinatura = $tdocs->getDocumentsByRef($adm_id)->url;


    	if (empty($img_assinatura)) {
    		$msg_assinatura = traduz("Não possui assinatura salva");
    	}
	}
	?>
	<!DOCTYPE html />
    <html>
        <head>
            <meta http-equiv="X-UA-Compatible" content="IE=8"/>
            <meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
            <meta http-equiv="Expires"       content="0">
            <meta http-equiv="Pragma"        content="no-cache, public">
            <meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
            <meta name      ="Author"        content="Telecontrol Networking Ltda">
            <meta http-equiv=pragma content=no-cache>
            <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
            <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
            <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
            <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
            <link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

            <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
            <script src="bootstrap/js/bootstrap.js"></script>
            <script src="plugins/dataTable.js"></script>
            <script src="plugins/resize.js"></script>
            <script src="plugins/shadowbox_lupa/lupa.js"></script>

            <script>
            	
                function submit_gravar(argument) {
                    var motivo = $('#interacao_motivo').val();
                    window.parent.pega_selecionados('recusa_solicitacoes',motivo);
                }
            </script>
        </head>

        <body>
            <div id="container_lupa" style="overflow-y:auto;"> 
                <div class="row-fluid">
                    <div class='titulo_tabela '><?=traduz('Assinatura para Aprovação de Cheque')?></div>
                    <br />
                        <div class="row-fluid" >
                            <div class="span1"></div>
                            <div class="span10" >
                                <div class="control-group" style="text-align: center;">
        							<?php if (empty($msg_assinatura)) { ?>
        								<img src="<?=$img_assinatura?>" height="96" alt=""> 
        							<?php } else { ?>
										<p><b><?=$msg_assinatura?></b></p>
        							<?php } ?>                          

                                </div>
                            </div>
                            <div class="span1"></div>
                        </div>
                        <br />
                        <br />
                        <div class="row-fluid">
                            <div class="span12 tac" >
                                <div class="control-group" >
                                    <label class="control-label" >&nbsp;</label>
                                    <div class="controls controls-row tac" >
                                        <button type="button" id="fechar_assinatura" name="fechar_assinatura" class="btn" onclick='window.parent.Shadowbox.close();' ><?=traduz('fechar')?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>                
            </div>
        </body>
    </html>
    <?php
    exit;
}

function createHTMLInput($type, $name, $id, $value, $valor, $status=null, $enabled=true, $attrs='') {

	if (!$type or !$name)   return false;
	if ($id === true) $id = $name; //Usa o valor do "name" para o ID...

	$input = "<input type='$type' name='$name' value='$value'";
	if ($id         === true)  $input .= " id='$name'";
	if (strlen($id) >   1)     $input .= " id='$id'";
	if ($enabled    === false) $input .= ' disabled';

	if ($type == 'radio' or $type == 'checkbox') {
		$input .= ($status === true or $value == $valor) ? " checked":'';
	}

	if($type == 'checkbox'){
		$rel_id = array_reverse((explode("_", $name)));
		$rel = " rel='{$rel_id[0]}' ";
	}

	return "$input $attrs {$rel} />";

}

function enviaEmail(){
	$mailer = new TcComm('smtp@posvenda');
	$addressList = array('ronaldo@telecontrol.com.br' );

	$mensagem = traduz("Foi cadastrado um novo usuário excedente e será incluído na fatura o valor de R$ 200.00 (duzentos reais) e a cobrança será suspensa somente no mês subsequente ao cancelamento dessa inclusão.");
	$Subject = traduz("Cadastro de admin excedente");

	if (!$mailer->sendMail($addressList, $Subject, $mensagem, 'suporte@telecontrol.com.br')) {
		$msg_erro = traduz("O email de atualização do Admin não foi enviado");
		return $msg_erro;
	}else{
		return true;
	}
}


$ajax = $_REQUEST['ajax'];

if ($ajax == 'ajax') {
	$tipo = $_REQUEST['tipo'];

	if($tipo == "validaChat"){
		$sql = "SELECT qtde_chat FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
		$res = pg_query($con, $sql);

		$qtde_chat = (int) pg_fetch_result($res, 0, 'qtde_chat');

		//$sql = "SELECT MAX(qtde) AS qtde_chat_fabrica FROM tbl_fabrica_chat WHERE fabrica = $login_fabrica AND data BETWEEN ".date("Y-m-01")." AND ".date("Y-m-t")."  LIMIT 1;";
		$sql = "SELECT MAX(qtde) AS qtde_chat_fabrica FROM tbl_fabrica_chat WHERE fabrica = $login_fabrica AND DATE_PART('MONTH', data) = DATE_PART('MONTH', CURRENT_TIMESTAMP)  LIMIT 1;";
		//$res = pg_query($con, $sql);

		//$qtde_chat_fabrica = (int) pg_fetch_result($res, 0, 'qtde_chat_fabrica');
		$qtde_chat_fabrica = 0;

		echo "{$qtde_chat}|{$qtde_chat_fabrica}";
		exit;
	}
}

if ($ajax == 'true') {
	if ($_REQUEST["action"] == "novo"){

		$qtde = $_REQUEST['qtde'];
		?>
		<tr>

			<td nowrap align="center" >
				<input type="text" name='login_novo_<?=$qtde?>'         class="frm" size='20' maxlength='20' value='' onkeyup='retiraAcentos(this)'>
			</td>
			<td nowrap align="center">
				<input type='text' name='senha_novo_<?=$qtde?>'         class="frm" size='20' maxlength='10' value=''>
			</td>
			<td nowrap align="center">
				<input type='text' name='nome_completo_novo_<?=$qtde?>' class="frm" size='20' maxlength=''   value=''>
			</td>
			<td nowrap align="center">
				<?php 
					$class_tel = (!in_array($login_fabrica, [180,181,182])) ? "telefone" : "tel";
				?>
				<input type='text' name='fone_novo_<?=$qtde?>'          class="frm <?=$class_tel?> fone_<?=$qtde?>" size='13' maxlength='17' value=''>
			</td>
			<td nowrap align="center">
				<?php 
					$class_cel = (!in_array($login_fabrica, [180,181,182])) ? "cel" : "tel"; 
				?>
				<input type='text' name='celular_novo_<?=$qtde?>'          class="frm celular_<?=$qtde?> <?=$class_cel?>" size='13' maxlength='18' value=''>
			</td>
			<td nowrap align="center">
				<input type="text" name='whatsapp_novo_<?=$i?>' name='whatsapp_novo_<?=$qtde?>' class="frm fone cel" size='13' maxlength='18' value=''>
			</td>
			<td nowrap align="center">
				<input type='text' name='email_novo_<?=$qtde?>'         class="frm" size='30' maxlength=''   value=''>
			</td>

			<td nowrap>
				<select name='l10n_novo_<?=$qtde?>'>
					<option></option>
					<?php
						$sql_idioma ="SELECT l10n, idioma
									  FROM tbl_l10n
									  ORDER BY idioma";
						$res_idioma = pg_query($con, $sql_idioma);

						if (pg_num_rows($res_idioma) > 0) {

							for ($w = 0; $w < pg_num_rows($res_idioma); $w++) {

								$l10n	= pg_fetch_result($res_idioma, $w, 'l10n');
								$idioma	= pg_fetch_result($res_idioma, $w, 'idioma');
							/*$cidade			= pg_fetch_result($res_cliente_admin, $w, 'cidade');

								$nome           = ucwords(strtolower(substr($nome,0,20)));
								$cidade         = ucwords(strtolower(substr($cidade,0,15)));*/

								echo "<option value='$l10n'>$idioma</option>";
							}
						}
					?>
				</select>
			</td>

			<?php

			if ($fabrica_multinacional) {

				echo "<td nowrap>";
				echo createHTMLInput('text', 'pais_novo', null, $pais_novo, null, null, false, " size='2' maxlength='2' class='novo'");
				echo "</td>\n";

			}

			$enabled = false;

			if ($abre_os_admin_arr) {

				if (in_array($login_fabrica, [158])) {

					$sqlClienteAdmin = "
						SELECT cliente_admin,
							   SUBSTRING(nome, 1, 20) AS nome, 
							   SUBSTRING(cidade, 1, 15) AS cidade
						FROM tbl_cliente_admin
						WHERE fabrica = {$login_fabrica}
						AND abre_os_admin IS TRUE
						ORDER BY nome
					";
					$resClienteAdmin = pg_query($con,$sqlClienteAdmin);
					?>
					
					<td nowrap>
						<select multiple class="cliente_admin_multiplo" name="cliente_admin_multiplo_novo_<?= $qtde ?>[]">
							<option value=""></option>
							<?php
							while ($dados = pg_fetch_object($resClienteAdmin)) { 



							?>
								<option value="<?= $dados->cliente_admin ?>"><?= $dados->cidade ?> - <?= $dados->nome ?></option>
							<?php
							} ?>
						</select>
					</td>   

				<?php
				} else {

					echo "<td nowrap >";

						echo "<select name='cliente_admin_novo_<?=$qtde?>' >";


								echo "<option></option>";
							if (in_array($login_fabrica,array(85,156,167,191,203))) {
								$sql_cliente_admin = "SELECT    cliente_admin,
									nome, cidade
									FROM tbl_cliente_admin
									WHERE fabrica = $login_fabrica
									ORDER BY nome";
							} else {
								$sql_cliente_admin = "SELECT cliente_admin,
														nome, cidade
														FROM tbl_cliente_admin
														WHERE fabrica = $login_fabrica
														AND abre_os_admin IS TRUE
														ORDER BY nome";
							}

							$res_cliente_admin = pg_query($con, $sql_cliente_admin);

							if (pg_num_rows($res_cliente_admin) > 0) {

								for ($w = 0; $w < pg_num_rows($res_cliente_admin); $w++) {

									$cliente_admin  = pg_fetch_result($res_cliente_admin, $w, 'cliente_admin');
									$nome           = pg_fetch_result($res_cliente_admin, $w, 'nome');
									$cidade         = pg_fetch_result($res_cliente_admin, $w, 'cidade');

									$nome           = ucwords(strtolower(substr($nome,0,20)));
									$cidade         = ucwords(strtolower(substr($cidade,0,15)));

									echo "<option value='$cliente_admin'>$nome - $cidade</option>";

								}

							}
						echo "</select>";

					echo "</td>";

				}

				if ($login_fabrica != 96) {

					echo "<td align=\"center\"><input type='checkbox' name='cliente_admin_master_novo_$qtde' value='t'";
					if ($cliente_admin_master == 't') echo " checked";
					echo "</TD>\n";

				}

			}

			echo '<td align="center">' . createHTMLInput('checkbox', "ativo_novo_$qtde"                     , true, 't', $ativo, $enabled, true,"class='ativo'") . '</td>';
			echo '<td align="center">' . createHTMLInput('checkbox', "master_novo_$qtde"                    , true, 'master', $master_novo, ($master == 'master'), $enabled, "class='novo_$qtde' onclick=\"clicaMasterNovo($(this).attr('rel'))\" ") . '</td>';
			if ($login_privilegios == '*')
				echo '<td align="center">' . createHTMLInput('checkbox', "sup_help_desk_$qtde"              , true, 't', $sup_help_desk, null, $enabled, "class='novo_$qtde'") . '</td>';

			if ($login_fabrica == 168) {
				echo '<td align="center">' . createHTMLInput('checkbox', "libera_pedido_$qtde" , null, 't', $libera_pedido, null, $enabled, "class='novo_$qtde libera_pedido'") . '</td>';
			}

			echo '<td align="center">' . createHTMLInput('checkbox', "live_help_$qtde"                      , null, 't', $live_help, null, $enabled, "class='novo_$qtde live_help'") . '</td>';
			echo '<td align="center">' . createHTMLInput('checkbox', "responsavel_ti_novo_$qtde"            , null, 't', $responsavel_ti, null, $enabled, "class='novo_$qtde'") . '</td>';

			// Privilégios
			echo '<td align="center">' . createHTMLInput('checkbox', "gerencia_novo_$qtde"                  , null, 'gerencia'    , $_POST['gerencia_novo'], null, $enabled, "class='novo_".$qtde."'") . '</td>';
			echo '<td align="center">' . createHTMLInput('checkbox', "cadastros_novo_$qtde"                 , null, 'cadastros'   , $_POST['cadastros_novo'],       null, $enabled, "class='novo_".$qtde."'") . '</td>';
			echo '<td align="center">' . createHTMLInput('checkbox', "call_center_novo_$qtde"               , null, 'call_center' , $_POST['call_center_novo'], null, $enabled, "class='novo_".$qtde."'") . '</td>';
			if (in_array($login_fabrica, array(169,170))) {
				echo '<td align="center">' . createHTMLInput('checkbox', "recebe_jornada_novo_$qtde"    , null, 't'           , $recebe_jornada, null, $enabled, "class='novo_".$qtde."'") . '</td>';
			}
			echo '<td align="center">' . createHTMLInput('checkbox', "supervisor_call_center_novo_$qtde"    , null, 't'           , $supervisor_call_center, null, $enabled, "class='novo_".$qtde."'") . '</td>';
			if (!in_array($login_fabrica, array(173,174,175,176,184,191,193,198,200,203)) || $usa_somente_callcenter)
			{
				echo '<td align="center">' . createHTMLInput('checkbox', "somente_visualiza_call_center_novo_$qtde" , null, 't'           , $somente_visualiza_call_center, null, $enabled, "class='novo_".$qtde."'") . '</td>';
			}
			if ($integracaoTelefonia === true || (in_array($login_fabrica, array(10)))) {
				echo '<td align="center">' . createHTMLInput('checkbox', "sac_telecontrol_novo_$qtde"           , null, 'sac_telecontrol', $_POST['sac_telecontrol_novo'],  null, $enabled, "class='novo_".$qtde."'") . '</td>';    
			}
			echo '<td align="center">' . createHTMLInput('checkbox', "info_tecnica_novo_$qtde"              , null, 'info_tecnica', $_POST['info_tecnica_novo'],    null, $enabled, "class='novo_".$qtde."'") . '</td>';
			echo '<td align="center">' . createHTMLInput('checkbox', "financeiro_novo_$qtde"                , null, 'financeiro'  , $_POST['financeiro_novo'],  null, $enabled, "class='novo_".$qtde."'") . '</td>';
			echo '<td align="center">' . createHTMLInput('checkbox', "auditoria_novo_$qtde"                 , null, 'auditoria'   , $_POST['auditoria_novo'],       null, $enabled, "class='novo_".$qtde."'") . '</td>';

			if ($login_fabrica == 91)  // HD 685194
				echo '<td align="center">' . createHTMLInput('checkbox', "promotor_wanke_novo_$qtde", null, 'promotor', $_POST['promotor_wanke_novo'], null, $enabled, "class='novo_".$qtde."'") . '&nbsp;</td>';

			if ($usa_altera_pais_produto)  // HD 374998
				echo '<td align="center">' . createHTMLInput('checkbox', "altera_pais_produto_novo_$qtde", null, 't', $altera_pais_produto, null, $enabled, "class='novo_".$qtde."'") . '&nbsp;</td>';

			if ($usa_atende_hd_postos)
				echo '<td align="center">' . createHTMLInput('checkbox', "sap_novo_$qtde", "sap_novo", 't', $admin_sap, null, $enabled, "class='novo_".$qtde."'") . '</td>';

			if ($usa_responsavel_postos)
				echo '<td align="center">' . createHTMLInput('checkbox', "responsavel_postos_novo_$qtde", null, 't', $_POST['responsavel_postos_novo'], null, $enabled, "class='novo_".$qtde."'") . '&nbsp;</td>';

			if ($login_fabrica == 115)
				echo '<td align="center">' . createHTMLInput('checkbox', "altera_categoria_posto_$qtde", null, 't', $_POST['altera_categoria_posto'], null, $enabled, "class='novo_".$qtde."'") . '&nbsp;</td>';

			if ($usa_atendente_callcenter)
				echo '<td align="center">' . createHTMLInput('checkbox', "atendente_callcenter_novo_$qtde", null, 't', $atendente_callcenter_novo, null, $enabled, "class='novo_".$qtde."'") . '&nbsp;</td>';

			if ($usa_recebe_fale_conosco)
				echo '<td align="center">' . createHTMLInput('checkbox', "fale_conosco_novo_$qtde", null, 't', $fale_conosco, null, $enabled, "class='novo_".$qtde."'") . '&nbsp;</td>';

			if ($manda_email_assinatura)
				echo '<td align="center">' . createHTMLInput('checkbox', "email_assinatura_novo_$qtde", null, 't', $email_assinatura, null, $enabled, "class='novo_".$qtde."'") . '&nbsp;</td>';

			if ($usa_intervensor)
				echo '<td align="center">' . createHTMLInput('checkbox', "intervensor_novo_$qtde", null, 't', $intervensor_novo, null, $enabled, "class='novo_".$qtde."'") . '&nbsp;</td>';

			if ($telecontrol_distrib)
				echo '<td align="center">' . createHTMLInput('checkbox', "visualiza_estoque_distrib_$qtde", null, 't', $_POST['visualiza_estoque_distrib'], null, $enabled, "class='novo_".$qtde."'") . '&nbsp;</td>';

			if ($login_fabrica == 19)
				echo '<td align="center">' . createHTMLInput('checkbox', "consulta_os_novo_$qtde", null, 't', $consulta_os, null, $enabled, "class='novo_".$qtde."'") .  '&nbsp;</td>';?>

		</tr>
		<?php

		exit;
	}

}

include "../helpdesk/mlg_funciones.php";

if ( ($fabrica_assina_extrato  and ($admin_aprova_extrato || $admin_aprova_protocolo) && $login_fabrica <> 1) || ($fabrica_assina_extrato  and ($admin_aprova_extrato || $admin_aprova_protocolo) && isset($_POST['admin_solicitacao'])) ) {

	if ($login_fabrica == 1) {
		if (isset($_POST['admin_solicitacao']) && $_POST['admin_solicitacao'] != "") {  
			$usuario_assinatura = $_POST['admin_solicitacao'];
		} 
	}

	require_once '../class/tdocs.class.php';

	if ($_POST['anexo'] == 'assinatura' and $_FILES['assinatura']['tmp_name']) {
		$tdocs = new TDocs($con, $login_fabrica);
		$assinatura = $_FILES['assinatura'];

		if ($assinatura['error']) {
			die ("KO|Erro ao receber o arquivo.");
		}

		if ($login_fabrica == 1) {
			$tdocs->uploadFileS3($assinatura, $usuario_assinatura, true, 'assinatura');
		} else {
			$tdocs->uploadFileS3($assinatura, $login_admin, true, 'assinatura');
		} 

		if ($tdocs->error) {
			$msg_erro = $tdocs->error;
		}

		$msg = traduz('Assinatura gravada corretamente.');
	} elseif ($_FILES['assinatura']['tmp_name'] == "") {
		$msg_erro = traduz("Selecione um anexo");
	}

	if (class_exists('TDocs')) {
		$tdocs          = new TDocs($con, $login_fabrica, 'assinatura');
		if ($login_fabrica == 1) {
			$img_assinatura = $tdocs->getDocumentsByRef($usuario_assinatura)->url;  
		} else {
			$img_assinatura = $tdocs->getDocumentsByRef($login_admin)->url;
		}
	}
	
	?>
		<script type="text/javascript">
			var ms_erro = '<?=$msg_erro?>';
			var ms = '<?=$msg?>'; 
			var ms_mostrar = '';

			if (ms != '') {
				ms_mostrar = ms;
			} else if (ms_erro != '')  {
				ms_mostrar = ms_erro;
			}

			alert(ms_mostrar);
		</script>
	<?php
	$msg = "";
	$msg_erro = "";
}

if ($btn_acao == 'gravar'){
	$qtde_novos = $_POST['qtde_novos'];
	$excedeu_admin = $_POST['excedeu_admin'];

	$auditorLog = new AuditorLog('insert');

	pg_begin();

	for ($i=0; $i < $qtde_novos; $i++) {

		$login = '';
		$login                     = strtolower(getPost("login_novo_" . $i));
		$senha                     = strtolower(getPost("senha_novo_" . $i));
		$pais                      = strtoupper(getPost("pais_novo_"  . $i));
		$nome_completo             = getPost("nome_completo_novo_"    . $i);
		$email                     = getPost("email_novo_"            . $i);
		$fone                      = getPost("fone_novo_"             . $i);
		$celular                   = getPost("celular_novo_"          . $i);
		$whatsapp                  = getPost("whatsapp_novo_"         . $i);
		if (!in_array($login_fabrica, [180,181,182])) {
			$fone                  = str_replace(['(',')','-'], '', $fone);
			$celular               = str_replace(['(',')','-'], '', $celular);
			$whatsapp              = str_replace(['(',')','-', " "], '', $whatsapp);
		}
		$cliente_admin             = getPost("cliente_admin_novo_"    . $i);
		$cliente_admin_multiplo    = getPost("cliente_admin_multiplo_novo_".$i);
		$tipo_protocolo    		   = getPost("tipo_protocolo_".$i);
		$l10n          			   = getPost("l10n_novo_"    		  . $i);
		$master                    = getPost("master_novo_"           . $i);
		$sup_help_desk             = getPost("sup_help_desk_"         . $i);
		$gerencia                  = getPost("gerencia_novo_"         . $i);
		$call_center               = getPost("call_center_novo_"      . $i);
		$cadastros                 = getPost("cadastros_novo_"        . $i);
		$info_tecnica              = getPost("info_tecnica_novo_"     . $i);
		$financeiro                = getPost("financeiro_novo_"       . $i);
		$auditoria                 = getPost("auditoria_novo_"        . $i);
		$inspetor                  = getPost("inspetor_"              . $i);
		$promotor_wanke            = getPost("promotor_wanke_novo_"   . $i); // HD 685194
		$ramal                     = getPost("ramal_"                 . $i);
		$email_assinatura          = getPost("email_assinatura_novo_" . $i);
		$consulta_os               = getPost("consulta_os_novo_"            . $i) ? 't' : 'f';
		$ativo                     = getPost("ativo_novo_"                  . $i) ? 't' : 'f';
		$cliente_admin_master      = getPost("cliente_admin_master_novo_"   . $i) ? 't' : 'f';
		$supervisor_call_center    = getPost("supervisor_call_center_novo_" . $i) ? 't' : 'f';
		$intervensor               = getPost("intervensor_novo_"            . $i) ? 't' : 'f';
		$fale_conosco              = getPost("fale_conosco_novo_"           . $i) ? 't' : 'f';
		$atendente_callcenter      = ($call_center == "call_center")              ? 't' : 'f'; // HD 335548
		$admin_sap                 = getPost("sap_novo_"                    . $i) ? 't' : 'f';
		$responsavel_postos        = getPost("responsavel_postos_novo_"     . $i) ? 't' : 'f';
		$altera_categoria_posto    = getPost("altera_categoria_posto_"      . $i) ? 't' : 'f';
		$visualiza_estoque_distrib = getPost("visualiza_estoque_distrib_"   . $i) ? 't' : 'f';
		$altera_pais_produto       = getPost("altera_pais_produto_novo_"    . $i) ? 't' : 'f'; // HD 374998
		$responsavel_ti            = getPost("responsavel_ti_novo_"         . $i) ? 't' : 'f'; // Sem HD, solicitação do Boaz
		$libera_pedido             = getPost("libera_pedido_"               . $i) ? 't' : 'f';
		$live_help                 = getPost("live_help_"                   . $i) ? 't' : 'f';
		$troca_reembolso           = getPost("troca_reembolso_"             . $i) ? 't' : 'f';
		$troca_reembolso           = getPost("troca_reembolso_"             . $i) ? 't' : 'f';
		$aprova_laudo              = getPost("aprova_laudo_"                . $i) ? 't' : 'f';
		$observacao_sac            = getPost("observacao_sac_"              . $i) ? 't' : 'f';
		$aviso_email               = getPost("aviso_email_"                 . $i) ? 't' : 'f';

		$analise_ri                = getPost("analise_ri_"                 . $i) ? 't' : 'f';
		$suporte_tecnico           = getPost("suporte_tecnico_"                 . $i) ? 't' : 'f';

		$aprova_extrato            = getPost("aprova_extrato_"              . $i) ? 't' : 'f';
		$aprova_protocolo          = getPost("aprova_protocolo_"            . $i) ? 't' : 'f';
		$pagamento_garantia        = getPost("pagamento_garantia_"          . $i) ? 't' : 'f';
		$solicitacao_cheque        = getPost("solicitacao_cheque_"          . $i) ? 't' : 'f';
		$supervisao_cheque         = getPost("supervisao_cheque_"           . $i) ? 't' : 'f';

		$contas    				   = $_REQUEST["contas_".$id_linha];

		if($login_fabrica == 3){
			$aprova_avulso_1_novo      = getPost("aprova_avulso_1_novo_". $i) ? 't' : 'f';
			$aprova_avulso_2_novo      = getPost("aprova_avulso_2_novo_". $i) ? 't' : 'f';
		}

		$and_supervisor         = (strlen($sup_help_desk) > 0) ? "'$sup_help_desk' ," : "'f' ,";
		if (in_array($login_fabrica, array(169,170))) {
			$recebe_jornada = getPost("recebe_jornada_novo_".$i)        ? 't' : 'f';
		}
		$somente_visualiza_call_center = getPost("somente_visualiza_call_center_novo_" . $i) ? 't' : 'f';
		if (in_array($login_fabrica, array(10)) || ($integracaoTelefonia === true)) {
			$sac_telecontrol_novo = getPost("sac_telecontrol_novo_" . $i) ? 't' : 'f';
		}

		$privilegios = ($master == 'master') ? '*' :
			implode(',',
				array_filter(
					explode(',', "$gerencia,$call_center,$cadastros,$info_tecnica,$financeiro,$auditoria,$promotor_wanke,$inspetor")
				)
			);

		if (in_array($login_fabrica, array(169,170))) {
			$sql_email = "SELECT admin FROM tbl_admin WHERE email = '$email' AND fabrica = $login_fabrica";
			$qry_email = pg_query($con, $sql_email);


            if (pg_num_rows($qry_email) > 0) {
                $msg_erro .= traduz("Já existe um usuário cadastrado com este email")."<br />";
            }
        }

		if (!empty($celular) && !in_array($login_fabrica, [180,181,182])) {
			$msg_erro .= valida_celular($celular);
		}

		$sql = "SELECT fn_fabrica_chat($login_fabrica, $login_admin);";
		$res = pg_query($con, $sql);
		$msg_erro .= pg_last_error($con);
		$envia_email = pg_fetch_result($res, 0, 0);

		if($envia_email == 't'){ //se a função inseriu registro envia email para o admin

			$sql = "SELECT nome_completo, email FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica};";
			$res = pg_query($con, $sql);
			$msg_erro .= pg_last_error($con);
			$email_admin = pg_fetch_result($res, 0, 'email');

			$sql = "SELECT qtde_chat FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
			$res = pg_query($con, $sql);
			$msg_erro .= pg_last_error($con);
			$qtde_chat = (int) pg_fetch_result($res, 0, 'qtde_chat');

			$sql = "SELECT MAX(qtde) AS qtde_chat_fabrica FROM tbl_fabrica_chat WHERE fabrica = $login_fabrica AND DATE_PART('MONTH', data) = DATE_PART('MONTH', CURRENT_TIMESTAMP)  LIMIT 1;";
			$res = pg_query($con, $sql);
			$msg_erro .= pg_last_error($con);
			$qtde_chat_fabrica = (int) pg_fetch_result($res, 0, 'qtde_chat_fabrica');

			$valor_fatura = $qtde_chat_fabrica - $qtde_chat_fabrica_antigo;
			$valor_total_fatura = $qtde_chat_fabrica - $qtde_chat;

			if($valor_fatura > $valor_total_fatura)
				$valor_fatura = $valor_fatura - $qtde_chat;

			$valor_total_fatura = number_format(($valor_total_fatura) * 200 ,2,",",".");
			$valor_fatura       = number_format(($valor_fatura) * 200 ,2,",",".");

			if($qtde_chat_fabrica > 100){
				$descricao_total = traduz("Que somado aos outros usuários excedentes passa ao total de R$ % na fatura mensal", null, null, [$valor_total_fatura])."<br />";
			}

			$mensagem = "<div>".traduz("Sua empresa cadastrou outro usuário no sistema de atendimento do CHAT, estamos incluindo na fatura o valor de R$ % mensalmente<br />% Valor referente ao uso concomitante de usuários no CHAT, cobrado na fatura mensal sem necessidade de aditivos ao contrato", null, null, [$valor_fatura, $descricao_total])."</div><br /><p>Telecontrol Networking<br>www.telecontrol.com.br</p>";

		}

		if (strlen($login) > 0 and strlen($senha) > 0) {


			if (validaSenhaAdmin($senha, $login) !== true) $msg_erro = validaSenhaAdmin($senha, $login);
			if (!is_email($email)) $msg_erro .= traduz("E-mail digitado (%) inválido!", null, null, [$email])."<br />";

			$pais = (strlen($pais) == 0) ? 'BR' : strtoupper($pais);

			if (strlen($cliente_admin)==0){
				$cliente_admin = 'null';
			}

			if (strlen($msg_erro) == 0) {

				$camposInsert['fabrica']               = pg_quote($login_fabrica, true);
				$camposInsert['login']                 = pg_quote($login);
				$camposInsert['senha']                 = pg_quote($senha);
				$camposInsert['nome_completo']         = pg_quote($nome_completo);
				$camposInsert['email']                 = pg_quote($email);
				$camposInsert['pais']                  = pg_quote($pais);
				$camposInsert['fone']                  = pg_quote($fone);

				if (!empty($whatsapp)) {
					$camposInsert['whatsapp']          = pg_quote($whatsapp);	
				}

				$camposInsert['cliente_admin']         = pg_quote($cliente_admin, true);

				if (!empty($l10n)) {
					$camposInsert['l10n']		       = pg_quote($l10n, true);
				}

				$camposInsert['help_desk_supervisor']  = pg_quote($sup_help_desk);
				$camposInsert['consulta_os']           = pg_quote($consulta_os);
				$camposInsert['ativo']                 = pg_quote($ativo);
				$camposInsert['cliente_admin_master']  = pg_quote($cliente_admin_master);
				$camposInsert['callcenter_supervisor'] = pg_quote($supervisor_call_center);
				// MLG - ParametrosAdicionais é um JSON, não pode gravar mais valor escalar (t, f, 0, 10, FALSE, TRUE)
				// Este elemento já é tratado umas linhas abaixo.
				// $camposInsert['parametros_adicionais'] = pg_quote($somente_visualiza_call_center);
				if (in_array($login_fabrica, array(169,170))) {
					$camposInsert['participa_agenda'] = pg_quote($recebe_jornada);
				}
				$camposInsert['intervensor']           = pg_quote($intervensor);
				$camposInsert['fale_conosco']          = pg_quote($fale_conosco);
				$camposInsert['responsabilidade']      = pg_quote($email_assinatura);
				$camposInsert['atendente_callcenter']  = pg_quote($atendente_callcenter);
				$camposInsert['responsavel_postos']    = pg_quote($responsavel_postos);
				$camposInsert['altera_pais_produto']   = pg_quote($altera_pais_produto); /* HD 374998 */
				$camposInsert['responsavel_ti']        = pg_quote($responsavel_ti);
				$camposInsert['privilegios']           = pg_quote($privilegios);
				$camposInsert['live_help']             = pg_quote($live_help);
				$camposInsert['aprova_laudo']          = pg_quote($aprova_laudo);

				
				if (in_array($login_fabrica, [158])) {
					$arr_parametros_adicionais['clientes_admin'] = $cliente_admin_multiplo;
					$camposInsert['parametros_adicionais'] = "'" . json_encode($arr_parametros_adicionais) . "'";
				}

				if ((in_array($login_fabrica, array(10)) || ($integracaoTelefonia === true)) && $sac_telecontrol_novo === 't') {
					$arr_parametros_adicionais['sacTelecontrol'] = true;
					$camposInsert['parametros_adicionais'] = "'" . json_encode($arr_parametros_adicionais) . "'";
				} else {
					$camposInsert['parametros_adicionais'] = "'{}'"; // JSON vazio
				}

				if (!empty($celular)) {
					$arr_parametros_adicionais['celular'] = $celular;
					$camposInsert['parametros_adicionais'] = "'" . json_encode($arr_parametros_adicionais) . "'";
				}

				if ($login_fabrica == 115) {
					$arr_parametros_adicionais = array('altera_categoria_posto' => $altera_categoria_posto);
					$camposInsert['parametros_adicionais'] = "'" . json_encode($arr_parametros_adicionais) . "'";                       
				}

				if ($telecontrol_distrib) {
					$arr_parametros_adicionais["visualiza_estoque_distrib"] = $visualiza_estoque_distrib;
					$camposInsert['parametros_adicionais'] = "'" . json_encode($arr_parametros_adicionais) . "'";                       
				}

				if($login_fabrica == 3){
					$parametros_adicionais = array(
						'aprova_avulso_1' => "$aprova_avulso_1_novo", 
						'aprova_avulso_2' => "$aprova_avulso_2_novo" );

					$camposInsert['parametros_adicionais'] = "'" . json_encode($parametros_adicionais) . "'";

				}

				if ($usa_admin_ramal) {
					$camposInsert['ramal']      = pg_quote($ramal);
				}

				if (in_array($login_fabrica, array(1,3,169,170))) {
					$camposInsert['admin_sap'] = pg_quote($admin_sap);
				}

				if (in_array($login_fabrica, [169,170])) {

					$arr_parametros_adicionais['analise_ri'] 	   = $analise_ri;
					$arr_parametros_adicionais['suporte_tecnico']  = $suporte_tecnico;

					$camposInsert['parametros_adicionais'] = "'" . json_encode($arr_parametros_adicionais) . "'";

				}

				$campos = implode(',', array_keys($camposInsert));
				$valores= implode(',', array_values($camposInsert));

				if (empty($msg_erro)){
					$sql = "INSERT INTO tbl_admin ($campos)
								VALUES
							($valores) RETURNING admin;";

					$res = pg_query($con,$sql);

					$xadmin = pg_fetch_result($res, 0, "admin");
					

					$msg_erro .= pg_last_error($con);
					if (empty($msg_erro)) {
						$novoAdmin = pg_fetch_result($res, 0, 'admin');
						
						/*HD - 4268666*/
						if (in_array($login_fabrica, array(11, 172))) {
							$camposInsert['login']   = "'".str_replace("'", "", $camposInsert['login'])."@pacific"."'";
							$camposInsert['fabrica'] = "172";

							$campos = implode(',', array_keys($camposInsert));
							$valores= implode(',', array_values($camposInsert));

							$sql = "INSERT INTO tbl_admin ($campos)
									VALUES
								($valores) RETURNING admin;";
							$res = pg_query($con,$sql);
							$adm = pg_fetch_result($res, 0, "admin");

							if (pg_last_error($con)) {
								$msg_erro .= pg_last_error($con);
							} else {
								$sql = "INSERT INTO tbl_admin_igual (admin, admin_igual) VALUES ($novoAdmin, $adm)";
								$res = pg_query($con, $sql);
							}
						}
					}
				}

				if (strlen($novoAdmin) > 0 && (isFabrica(1, 30, 42, 85, 151, 168) or $usa_somente_callcenter)) {
					$sql_admin = "SELECT parametros_adicionais FROM tbl_admin WHERE admin = $novoAdmin";
					$res_admin = pg_query($con, $sql_admin);

					if (pg_num_rows($res_admin) > 0) {
						$jsonPA = (!empty(pg_fetch_result($res_admin, 0, 'parametros_adicionais'))) ? json_decode(pg_fetch_result($res_admin, 0, 'parametros_adicionais'), true) : '{}';
						$adminPA = new Json($jsonPA);

					if (isFabrica(1)) {
							$adminPA->push(compact([
								'aprova_extrato', 'aprova_protocolo',
								'pagamento_garantia', 'solicitacao_cheque', 'supervisao_cheque'
							]));
						}

						if ($usa_somente_callcenter) {
							$adminPA->somente_visualiza_call_center = $somente_visualiza_call_center;
						}

						if ($login_fabrica == 30) {
							$adminPA->observacao_sac = $observacao_sac;
						}

						if ($login_fabrica == 42) {
							$adminPA->aviso_email = $aviso_email;
						}

						if ($login_fabrica ==151) {
							$adminPA->troca_reembolso = $troca_reembolso;
						}

						if($login_fabrica == 168){
							$adminPA->libera_pedido = $libera_pedido;
						}

						$sql = "UPDATE tbl_admin
								   SET parametros_adicionais = '$adminPA'
								 WHERE admin   = $novoAdmin
								   AND fabrica = $login_fabrica";
						$res = pg_query($con,$sql);
					}
				}

				if(in_array($login_fabrica,[169,170]) AND count($tipo_protocolo) > 0){

					$sql = "DELETE FROM tbl_hd_tipo_chamado_vinculo WHERE admin = '{$novoAdmin}'";
					$res = pg_query($con,$sql);
					
					foreach ($tipo_protocolo as $key => $value) {

						$sql = "INSERT INTO tbl_hd_tipo_chamado_vinculo(hd_tipo_chamado,admin) VALUES({$value},{$novoAdmin})";
						$res = pg_query($con, $sql);					
					}

				}

				/*
					#### Deixar comentado até chat começar funcionar para Midea/Carrier #####
					if (in_array($login_fabrica, array(169,170)) and !empty($novoAdmin)) {
						$headers = array(
							"Access-Application-Key" => "084f77e7ff357414d5fe4a25314886fa312b2cff",
							"Access-Env" => "PRODUCTION",
							"Content-Type" => "application/json",
						);

						$client = new Posvenda\Rest\Client(
							"http://api2.telecontrol.com.br/tcchat",
							$headers
						);

						$response = $client->get("/usuario/usuario/{$login}/fabrica/{$login_fabrica}/active/f");

						if (empty($response) or $response["status_code"] == 404) {
							$arr_admin_nome = explode(" ", $nome_completo);
							$admin_chat_nome = $arr_admin_nome[0];
							array_shift($arr_admin_nome);
							$admin_chat_sobrenome = implode(" ", $arr_admin_nome);

							$data = array(
								"nome" => utf8_encode($admin_chat_nome),
								"sobrenome" => utf8_encode($admin_chat_sobrenome),
								"usuario" => $login,
								"email" => $email,
								"externalId" => $novoAdmin,
								"fabrica" => $login_fabrica,
								"aplicacao" => "POSVENDA",
								"tipoUsuario" => "ATENDENTE"
							);
							$client->setJson(true);
							$post = $client->post("/usuario", $data);

							if (!empty($post) and $post["status_code"] == 201) {
								$chat_response = json_decode($post["response"], true);

								$parametros_adicionais = '{"usuario_chat_id":' . $chat_response["id"] . '}';
								$sql = "UPDATE tbl_admin
										   SET parametros_adicionais = '$parametros_adicionais'
										 WHERE admin   = $novoAdmin
										   AND fabrica = $login_fabrica";
								$res = pg_query($con, $sql);
							} else {
								$msg_erro .= "Erro ao cadastrar Usuário no CHAT";
							}
						}
					}
				*/
			}

		}

		if (!empty($whatsapp) && strlen($whatsapp) < 10) {

			$msg_erro = "Número de whatsapp inválido";
		}

		if (strpos($msg_erro, 'duplicate key'))
			$msg_erro = traduz("Este usuário já está cadastrado e não pode ser duplicado.");

		if ($msg_erro){

			pg_rollBack();
			break;

		}

	}
	if (strlen ($msg_erro) == 0) {
		if(!empty($excedeu_admin)){
			enviaEmail();
		}

		pg_commit();
		$sucesso = true;
		$auditorLog->retornaDadosTabela('tbl_admin', array('admin'=>$xadmin, 'fabrica'=>$login_fabrica))
						   ->enviarLog('insert', "tbl_admin", $login_fabrica."*".$xadmin);
	}

}

//ajax para update

if ($_REQUEST['ajax']=='true' and $_REQUEST['action']=='update') {

	$_GET = array_map('utf8_decode', $_GET);

	$id_linha = getPost("id_linha");

	$sql = "SELECT MAX(qtde) AS qtde_chat_fabrica FROM tbl_fabrica_chat WHERE fabrica = $login_fabrica AND DATE_PART('MONTH', data) = DATE_PART('MONTH', CURRENT_TIMESTAMP)  LIMIT 1;";
	$res = pg_query($con, $sql);
	$qtde_chat_fabrica_antigo = (int) pg_fetch_result($res, 0, 'qtde_chat_fabrica');

	$admin                  = getPost("admin_"                  . $id_linha);
	$login                  = getPost("login_"                  . $id_linha);
	$senha                  = getPost("senha_"                  . $id_linha);
	$nome_completo          = getPost("nome_completo_"          . $id_linha);
	$email                  = getPost("email_"                  . $id_linha);
	$pais                   = strtoupper(getPost("pais_"       . $id_linha));
	$fone                   = getPost("fone_"                   . $id_linha);
	$celular                = getPost("celular_"                . $id_linha);
	$whatsapp               = getPost("whatsapp_"               . $id_linha);

	if (!in_array($login_fabrica, [180,181,182])) {
		$fone               = str_replace(['(',')','-'], '', $fone);
		$celular            = str_replace(['(',')','-'], '', $celular);
		$whatsapp           = str_replace(['(',')','-', " "], '', $whatsapp);
	}

	
	if ((strlen(trim($fone)) > 0)) {
		if (strlen(trim($fone)) > 15 && !in_array($login_fabrica, [180,181,182])) {
			echo "erro|Numero do telefone invalido ";
			exit;
		}
	}

	if ((strlen(trim($whatsapp)) > 0)) {

		$ddd = $whatsapp[0] . $whatsapp[1];

		if (intval($ddd) < 11) {
			echo "erro|Número do whatsapp com DDD inválido";
			exit;
		}

		if (!empty($whatsapp) && (strlen(trim($whatsapp)) > 15 || strlen(trim($whatsapp)) < 10) && !in_array($login_fabrica, [180,181,182])) {
			echo "erro|Numero do whatsapp invalido";
			exit;
		}
	}


	if (strlen(trim($celular)) > 15 && !in_array($login_fabrica, [180,181,182])) {
		echo "erro|Numero do celular invalido";
		exit;
	}

	$cliente_admin          = getPost("cliente_admin_"          . $id_linha);
	$cliente_admin_multiplo = getPost("cliente_admin_multiplo_" . $id_linha);
	$tipo_protocolo 		= getPost("tipo_protocolo_" 		. $id_linha);
	$l10n 		 	        = getPost("l10n_"          			. $id_linha);
	$master                 = getPost("master_"                 . $id_linha);
	$gerencia               = getPost("gerencia_"               . $id_linha);
	$call_center            = getPost("call_center_"            . $id_linha);

	$cadastros              = getPost("cadastros_"              . $id_linha);
	$info_tecnica           = getPost("info_tecnica_"           . $id_linha);
	$financeiro             = getPost("financeiro_"             . $id_linha);
	$auditoria              = getPost("auditoria_"              . $id_linha);
	$inspetor               = getPost("inspetor_"               . $id_linha);
	if($login_fabrica       == 129){
		$ramal                 = getPost("ramal_"               . $id_linha);
	}
	$promotor_wanke         = getPost("promotor_wanke_"         . $id_linha); // HD 685194
	$consulta_os            = getPost("consulta_os_"            . $id_linha)          ? 't' : 'f';
	$sup_help_desk          = getPost("sup_help_desk_"          . $id_linha)          ? 't' : 'f';
	$ativo                  = getPost("ativo_"                  . $id_linha)          ? 't' : 'f';
	$cliente_admin_master   = getPost("cliente_admin_master_"   . $id_linha)          ? 't' : 'f';
	$supervisor_call_center = getPost("supervisor_call_center_" . $id_linha)          ? 't' : 'f';
	if (in_array($login_fabrica, array(169,170))) {
		$recebe_jornada = getPost("recebe_jornada_" . $id_linha)          ? 't' : 'f';
	}
	$somente_visualiza_call_center = getPost("somente_visualiza_call_center_" . $id_linha) ? 't' : 'f';
	$intervensor            = getPost("intervensor_"            . $id_linha)          ? 't' : 'f';
	$fale_conosco           = getPost("fale_conosco_"           . $id_linha)          ? 't' : 'f';
	$email_assinatura       = getPost("envia_email_"            . $id_linha)          ? 'envia_email' : 'null';
	
	$atendente_callcenter = in_array($login_fabrica, array(169,170))
		? ($call_center == "call_center")                                           ? 't' : 'f'//HD 335548
		: getPost("atendente_callcenter_"   . $id_linha)                            ? 't' : 'f';//HD 335548

	$admin_sap                 = getPost("sap_"                    . $id_linha)          ? 't' : 'f';
	$responsavel_postos        = getPost("responsavel_postos_"     . $id_linha)          ? 't' : 'f';
	$altera_categoria_posto    = getPost("altera_categoria_posto_" . $id_linha)          ? 't' : 'f';
	$visualiza_estoque_distrib = getPost("visualiza_estoque_distrib_" . $id_linha)          ? 't' : 'f';
	$altera_pais_produto       = (getPost("altera_pais_produto_"   . $id_linha) != null) ? 't' : 'f'; // HD 374998
	$responsavel_ti            = getPost("responsavel_ti_"         . $id_linha)          ? 't' : 'f'; // Sem HD, solicitação do Boaz
	$live_help                 = getPost("live_help_"              . $id_linha)          ? 't' : 'f';
	$troca_reembolso           = getPost("troca_reembolso_"         . $id_linha)          ? 't' : 'f';
	$aprova_laudo              = getPost("aprova_laudo_"           . $id_linha)          ? 't' : 'f';
	$aviso_email               = getPost("aviso_email_"           . $id_linha)          ? 't' : 'f';

	$analise_ri                = getPost("analise_ri_"           . $id_linha)          ? 't' : 'f';
	$suporte_tecnico           = getPost("suporte_tecnico_"           . $id_linha)          ? 't' : 'f';

	$observacao_sac            = getPost("observacao_sac_"           . $id_linha)          ? 't' : 'f';
	$aprova_extrato            = getPost("aprova_extrato_"         . $id_linha)          ? 't' : 'f';
	$aprova_protocolo          = getPost("aprova_protocolo_"         . $id_linha)          ? 't' : 'f';
	$libera_pedido             = getPost("libera_pedido_"           . $id_linha)          ? 't' : 'f';
	$pagamento_garantia        = getPost("pagamento_garantia_"     . $id_linha)          ? 't' : 'f';
	$solicitacao_cheque        = getPost("solicitacao_cheque_"     . $id_linha)          ? 't' : 'f';
	$supervisao_cheque         = getPost("supervisao_cheque_"     . $id_linha)          ? 't' : 'f';
	$sac_telecontrol           = getPost("sac_telecontrol_"     . $id_linha)          ? 't' : 'f';

	$contas    				   = $_REQUEST["contas_".$id_linha];

	/*$msg_erro .= valida_fone($fone);*/
	if (!empty($celular) && !in_array($login_fabrica, [180,181,182])) {
		$msg_erro .= valida_celular($celular);
	}

	if($login_fabrica == 3){        
		$aprova_avulso_1 = getPost("aprova_avulso_1_".$id_linha) ? 't' : 'f';
		$aprova_avulso_2 = getPost("aprova_avulso_2_".$id_linha) ? 't' : 'f';

	}
	
	if (is_numeric($admin)) {
		$auditorLog = new AuditorLog();
		$auditorLog->retornaDadosTabela('tbl_admin', array('admin'=>$admin, 'fabrica'=>$login_fabrica));

		pg_begin();

		$sql_confere = "SELECT * FROM tbl_admin WHERE admin = $admin";
		$res_confere = @pg_query($con, $sql_confere);

		if (is_resource($res_confere)) {

			if(strlen($cliente_admin)==0){
				$cliente_admin = 'null';
			};

			// Cria uma variável adm_* com cada campo do registro adm_admin, adm_login, etc.
			extract(pg_fetch_assoc($res_confere, 0), EXTR_PREFIX_ALL, 'adm');

			$camposUpdate = array(); //Este array irá conter os campos a serem atualizados.
			$pais = ($pais == '') ? 'BR' : $pais;
			$privilegios = ($master == 'master')
				? '*'
				: implode(',',
					array_filter(
						explode(',', "$gerencia,$call_center,$cadastros,$info_tecnica,$financeiro,$auditoria,$promotor_wanke,$inspetor")
					)
				);
			/*  Confere campo por campo se houve alguma alteração.
				Se foi alterado, adiciona o nome do campo e o novo
				valor num array, já com o caracteres especiais "escapados"  */

			if ($admin                  != $adm_admin)                  $camposUpdate['admin']                  = pg_quote($admin, true); // é numérico!
			if ($login                  != $adm_login)                  $camposUpdate['login']                  = pg_quote($login);
			if ($senha                  != sha1($adm_senha))            $camposUpdate['senha']                  = pg_quote($senha);
			if ($nome_completo          != $adm_nome_completo)          $camposUpdate['nome_completo']          = pg_quote($nome_completo);
			if ($email                  != $adm_email)                  $camposUpdate['email']                  = pg_quote($email);
			if ($cliente_admin          != $adm_cliente_admin)          $camposUpdate['cliente_admin']          = pg_quote($cliente_admin, true);
			if (empty($cliente_admin))                                  $camposUpdate['cliente_admin']          = 'null';
			if ($l10n 		            != $adm_l10n && !empty($l10n))	$camposUpdate['l10n']          			= pg_quote($l10n, true);
			if (empty($l10n))                                 			$camposUpdate['l10n']          			= 'null';
			if ($pais                   != $adm_pais)                   $camposUpdate['pais']                   = pg_quote($pais);
			if ($fone                   != $adm_fone)                   $camposUpdate['fone']                   = pg_quote($fone);

			if ($whatsapp         != $adm_fone && !empty($whatsapp))    $camposUpdate['whatsapp']               = pg_quote($whatsapp);
            if ($ramal                  != $adm_ramal)                  $camposUpdate['ramal']                  = pg_quote($ramal);

			if ($ativo                  != $adm_ativo)                  $camposUpdate['ativo']                  = pg_quote($ativo);
			if ($sup_help_desk          != $adm_help_desk_supervisor)   $camposUpdate['help_desk_supervisor']   = pg_quote($sup_help_desk);
			if ($cliente_admin_master   != $adm_cliente_admin_master)   $camposUpdate['cliente_admin_master']   = pg_quote($cliente_admin_master);
			if ($supervisor_call_center != $adm_callcenter_supervisor)  $camposUpdate['callcenter_supervisor']  = pg_quote($supervisor_call_center);
			// if ($somente_visualiza_call_center != $adm_parametros_adicionais)  $camposUpdate['parametros_adicionais']  = pg_quote($somente_visualiza_call_center);
			if (in_array($login_fabrica, array(169,170))) {
				if ($recebe_jornada != $adm_participa_agenda)  $camposUpdate['participa_agenda']  = pg_quote($recebe_jornada);
			}
			if ($consulta_os            != $adm_consulta_os)            $camposUpdate['consulta_os']            = pg_quote($consulta_os);
			if ($intervensor            != $adm_intervensor)            $camposUpdate['intervensor']            = pg_quote($intervensor);
			if ($fale_conosco           != $adm_fale_conosco)           $camposUpdate['fale_conosco']           = pg_quote($fale_conosco);
			if ($email_assinatura       != $adm_email_assinatura)       $camposUpdate['responsabilidade']       = pg_quote($email_assinatura);
			if ($atendente_callcenter   != $adm_atendente_callcenter)   $camposUpdate['atendente_callcenter']   = pg_quote($atendente_callcenter);
			if ($admin_sap              != $adm_admin_sap)              $camposUpdate['admin_sap']              = pg_quote($admin_sap);
			if ($responsavel_postos     != $adm_responsavel_postos)     $camposUpdate['responsavel_postos']     = pg_quote($responsavel_postos);
			if ($altera_pais_produto    != $adm_altera_pais_produto)    $camposUpdate['altera_pais_produto']    = pg_quote($altera_pais_produto);
			if ($responsavel_ti         != $adm_responsavel_ti)         $camposUpdate['responsavel_ti']         = pg_quote($responsavel_ti);
			if ($privilegios            != $adm_privilegios)            $camposUpdate['privilegios']            = pg_quote($privilegios);
			if ($live_help              != $adm_live_help)              $camposUpdate['live_help']              = pg_quote($live_help);
			if ($aprova_laudo           != $adm_aprova_laudo)           $camposUpdate['aprova_laudo']           = pg_quote($aprova_laudo);  

			if (!empty($adm_parametros_adicionais)) {
				$parametros_adicionais = json_decode($adm_parametros_adicionais, true);
			} else {
				$parametros_adicionais = array();
			}

			if (in_array($login_fabrica, [158])) {
				$arrClientesAdmin = explode(",", $cliente_admin_multiplo);
				$parametros_adicionais['clientes_admin'] = $arrClientesAdmin;
			}

			if($login_fabrica == 3){
				$parametros_adicionais['aprova_avulso_1'] = $aprova_avulso_1;
				$parametros_adicionais['aprova_avulso_2'] = $aprova_avulso_2;
			}

			if (in_array($login_fabrica, [169,170])) {
				$parametros_adicionais['analise_ri'] 	   = $analise_ri;
				$parametros_adicionais['suporte_tecnico']  = $suporte_tecnico;
			}

			unset($parametros_adicionais['whatsapp']);

			if ($integracaoTelefonia or $login_fabrica == 10) {
				if ($sac_telecontrol == 't') {
					$parametros_adicionais['sacTelecontrol'] = true;
				} else if ($sac_telecontrol != 't' && array_key_exists('sacTelecontrol', $parametros_adicionais)) {
					unset($parametros_adicionais['sacTelecontrol']);
				}
			}

			if (count($parametros_adicionais) > 0) {
				$parametros_adicionais = array_map_recursive(function($value) {
					if (is_bool($value)) {
						return $value;
					} else {
						return utf8_encode($value);
					}
				}, $parametros_adicionais);
			}

			if ($login_fabrica == 115) {
				$parametros_adicionais = array('altera_categoria_posto' => $altera_categoria_posto);
			}

			if (!empty($celular)) {
				$parametros_adicionais['celular'] = $celular;   
			} else {
				$parametros_adicionais['celular'] = "";
			}

			if ($telecontrol_distrib) {
				$parametros_adicionais['visualiza_estoque_distrib'] = $visualiza_estoque_distrib;
			}

			$parametros_adicionais = json_encode($parametros_adicionais);

			if ($adm_parametros_adicionais != $parametros_adicionais) {
				$camposUpdate['parametros_adicionais'] = "'$parametros_adicionais'";
			}

			if (strlen($admin) > 0 and $ativo == 't') {

				if (isset($camposUpdate['senha'])) { //Só existe essa chave se alterou a senha...
					if (validaSenhaAdmin($senha, $login) !== true) $msg_erro = validaSenhaAdmin($senha, $login);
				}

			}

			if($live_help == 't' AND (empty($nome_completo) OR !is_email($email))){
				$msg_erro = traduz("Para ser cadastrado no chat os usuários devem ter nome e e-mail válidos!");
			}

			if ($login_fabrica == 1) {
				if ($adm_fale_conosco == 't' AND ($fale_conosco  != $adm_fale_conosco)) {
					$sql_adm = "SELECT admin FROM tbl_admin WHERE fabrica = $login_fabrica AND fale_conosco = 't' AND ativo = 't';";
					$res_adm = pg_query($con,$sql_adm);

					$adms_sac = pg_fetch_all($res_adm);

					$adm_novo_hd = $adms_sac[array_rand($adms_sac)]['admin'];

					$sql_hd = "UPDATE tbl_hd_chamado SET admin = $adm_novo_hd where hd_chamado in ( SELECT hd_chamado FROM tbl_hd_chamado WHERE fabrica = $login_fabrica AND admin = $admin AND categoria = 'servico_atendimeto_sac' AND status not ilike 'Resolvido%' AND status not ilike 'Cancelado%');";
				}
			}

			if (strlen($admin) > 0 and strlen($msg_erro) == 0 and count($camposUpdate)) {
				// a pedido do Boulivar, verificar antes de ativar um usuário, se o cliente_admin dele pode abrir OS. HD 372098
				//pre_echo(pg_fetch_assoc($res_confere, 0), "Dados do ADMIN $admin");

				if (is_numeric($cliente_admin) and $ativo == 't') {

					$sql     = "SELECT abre_os_admin FROM tbl_cliente_admin WHERE cliente_admin = $cliente_admin";
					$res     = pg_query($con,$sql);
					$abre_os = pg_fetch_result($res,0,0);

					if ($abre_os == 'f') {
						$sql = "UPDATE tbl_cliente_admin SET abre_os_admin = 't' WHERE cliente_admin = $cliente_admin AND fabrica = $login_fabrica";
						$res = pg_query($con, $sql);
					}

				}

				$listaCamposUpdate = implode(',', array_keys($camposUpdate));
				$valoresParaUpdate = implode(',', array_values($camposUpdate));
				$sql = " UPDATE tbl_admin  ".
						"   SET ($listaCamposUpdate) = ($valoresParaUpdate) ".
						" WHERE admin = $admin";
				$res = pg_query($con,$sql);

				/*HD - 4268666*/
				if (in_array($login_fabrica, array(11, 172))) {
					$sql = "SELECT admin_igual FROM tbl_admin_igual WHERE admin = $admin";
					$res = pg_query($con, $sql);
					$adm = pg_fetch_result($res, 0, 'admin_igual');

					if (empty($adm)) {
						$sql = "SELECT admin FROM tbl_admin_igual WHERE admin_igual = $admin";
						$res = pg_query($con, $sql);
						$adm = pg_fetch_result($res, 0, 'admin');
					}

					if (!empty($adm)) {
						if (!empty($camposUpdate['login'])) {
							if ($login_fabrica == 11) {
								$camposUpdate['login']   = "'".str_replace("'", "", $camposUpdate['login'])."@pacific"."'";
							}
						}

						if (!empty($camposUpdate['fabrica'])) {
							$camposUpdate['fabrica'] = ($login_fabrica == 11) ? "172" : "11";
						}

						$listaCamposUpdate = implode(',', array_keys($camposUpdate));
						$valoresParaUpdate = implode(',', array_values($camposUpdate));

						$sql = "UPDATE tbl_admin SET ($listaCamposUpdate) = ($valoresParaUpdate) WHERE admin = $adm";
						$res = pg_query($con,$sql);
						$adm = pg_fetch_result($res, 0, "admin");
					} else {
						$aux_campos = "
							fabrica, senha, login, privilegios, email, help_desk_supervisor, fone, nome_completo, ativo, data_expira_senha, pais, ultimo_acesso, ultimo_ip, responsabilidade, tela_inicial_posto, callcenter_supervisor, responsavel_troca, participa_agenda, dia_nascimento, mes_nascimento, admin_sap, cliente_admin, l10n, cliente_admin_master, fale_conosco, intervensor, nao_disponivel, altera_pedido, grupo_admin, atendente_callcenter, responsavel_postos, consulta_os, altera_pais_produto, responsavel_ti, live_help, projeto, aprova_laudo, ramal, ano_nascimento, external_id, parametros_adicionais, callcenter_email
						";

						$aux_value = "
							fabrica, senha, login || '@pacific', privilegios, email, help_desk_supervisor, fone, nome_completo, ativo, data_expira_senha, pais, ultimo_acesso, ultimo_ip, responsabilidade, tela_inicial_posto, callcenter_supervisor, responsavel_troca, participa_agenda, dia_nascimento, mes_nascimento, admin_sap, cliente_admin, l10n, cliente_admin_master, fale_conosco, intervensor, nao_disponivel, altera_pedido, grupo_admin, atendente_callcenter, responsavel_postos, consulta_os, altera_pais_produto, responsavel_ti, live_help, projeto, aprova_laudo, ramal, ano_nascimento, external_id, parametros_adicionais, callcenter_email
						";
						
						$sql = "
							INSERT INTO tbl_admin ($aux_campos)
							SELECT $aux_value FROM tbl_admin WHERE admin = $admin RETURNING admin
						";
						$res = pg_query($con, $sql);
						$adm = pg_fetch_result($res, 0, 'admin');

						$sql = "INSERT INTO tbl_admin_igual (admin, admin_igual) VALUES ($admin, $adm)";
						$res = pg_query($con, $sql);

						if ($login_fabrica == 172) {
							$sql = "SELECT login FROM tbl_admin WHERE admin = $amd";
							$res = pg_query($con, $sql);

							$aux_login = pg_fetch_result($aux_res, 0, 'login');
							$aux_login = explode('@', $aux_login);

							$sql = "UPDATE tbl_admin SET login = '". $aux_login[0] . "' WHERE admin = $adm";
							$res = pg_query($con, $sql);
						}
					}
				}

				if ((strlen($novoAdmin) > 0 or !empty($admin)) && (isFabrica(1, 30, 42, 85, 151, 168) or $usa_somente_callcenter)) {
					$sql_admin = "SELECT parametros_adicionais FROM tbl_admin WHERE admin = $admin";
					$res_admin = pg_query($con, $sql_admin);

					if (pg_num_rows($res_admin) > 0) {
						$jsonPA = (!empty(pg_fetch_result($res_admin, 0, 'parametros_adicionais'))) ? json_decode(pg_fetch_result($res_admin, 0, 'parametros_adicionais'), true) : [];
						$adminPA = new Json($jsonPA);

						if (isFabrica(1)) {
							$adminPA->push(compact([
								'aprova_extrato', 'aprova_protocolo',
								'pagamento_garantia', 'solicitacao_cheque', 'supervisao_cheque'
							]));

							$arrAdminPA = json_decode($adminPA, true);

							$arrAdminPA["permissao_contas"] = $contas;

							$jsonAdminPA = json_encode($arrAdminPA);

							$adminPA = new Json($jsonAdminPA);

						}

						if ($login_fabrica == 30) {
							$adminPA->observacao_sac = $observacao_sac;
						}

						if ($login_fabrica == 42) {
							$adminPA->aviso_email = $aviso_email;
						}

						if ($login_fabrica ==151) {
							$adminPA->troca_reembolso = $troca_reembolso;
						}

						if($login_fabrica == 168){
							$adminPA->libera_pedido = $libera_pedido;
						}

						if (isset($jsonPA['celular'])) {
							$adminPA->celular = $jsonPA['celular'];
						} else {
							$adminPA->celular = "";
						}


						$sql = "UPDATE tbl_admin
								   SET parametros_adicionais = '$adminPA'
								 WHERE admin   = $admin
								   AND fabrica = $login_fabrica";
						$res = pg_query($con,$sql);
					}
				}

				if(in_array($login_fabrica,[169,170]) AND count($tipo_protocolo) > 0){

					$sql = "DELETE FROM tbl_hd_tipo_chamado_vinculo WHERE admin = '{$admin}'";
					$res = pg_query($con,$sql);

					$tipo_protocolo = explode(",",$tipo_protocolo);
					
					foreach ($tipo_protocolo as $key => $value) {

						$sql = "INSERT INTO tbl_hd_tipo_chamado_vinculo(hd_tipo_chamado,admin) VALUES({$value},{$admin})";
						$res = pg_query($con, $sql);					
					}

				}

				$msg_erro = pg_last_error($con);

				if (strpos($msg_erro, 'duplicate key'))
					$msg_erro = traduz("Este usuário já está cadastrado e não pode ser duplicado.");

				/*
					#### Deixar comentado até chat começar funcionar para Midea/Carrier #####
					if (in_array($login_fabrica, array(169,170)) and empty($msg_erro)) {
						$headers = array(
							"Access-Application-Key" => "084f77e7ff357414d5fe4a25314886fa312b2cff",
							"Access-Env" => "PRODUCTION",
							"Content-Type" => "application/json",
						);

						$client = new Posvenda\Rest\Client(
							"http://api2.telecontrol.com.br/tcchat",
							$headers
						);

						$response = $client->get("/usuario/usuario/{$adm_login}/fabrica/{$login_fabrica}");
						$admin_chat_usuario = null;
						$admin_chat_ativo = null;

						$admin_tc_ativo = ($ativo == 't') ? true : false;

						if (!empty($response) and $response["status_code"] == 200) {
							$arr_response = json_decode($response["response"], true);
							$admin_chat_usuario = $arr_response["usuario"]["id"];
							$admin_chat_ativo = $arr_response["usuario"]["active"];
						}

						if (empty($admin_chat_usuario)) {
							$arr_admin_nome = explode(" ", $nome_completo);
							$admin_chat_nome = $arr_admin_nome[0];
							array_shift($arr_admin_nome);
							$admin_chat_sobrenome = implode(" ", $arr_admin_nome);

							$data = array(
								"nome" => utf8_encode($admin_chat_nome),
								"sobrenome" => utf8_encode($admin_chat_sobrenome),
								"usuario" => $login,
								"email" => $email,
								"externalId" => $admin,
								"fabrica" => $login_fabrica,
								"aplicacao" => "POSVENDA",
								"tipoUsuario" => "ATENDENTE"
							);

							$client->setJson(true);
							$post = $client->post("/usuario", $data);

							if (!empty($post) and $post["status_code"] == 201) {
								$chat_response = json_decode($post["response"], true);
								$admin_chat_usuario = $chat_response["id"];
							} else {
								$msg_erro .= "Erro ao cadastrar Usuário no CHAT";
							}

						} else {

							$arr_admin_nome = explode(" ", $nome_completo);
							$admin_chat_nome = $arr_admin_nome[0];
							array_shift($arr_admin_nome);
							$admin_chat_sobrenome = implode(" ", $arr_admin_nome);

							$uri = "/usuario/id/{$admin_chat_usuario}/fabrica/{$login_fabrica}";

							$data = array(
								"nome" => utf8_encode($admin_chat_nome),
								"sobrenome" => utf8_encode($admin_chat_sobrenome),
								"usuario" => $login,
								"email" => $email,
								"active" => $admin_tc_ativo
							);

							$client->setJson(true);
							$put = $client->put($uri, $data);

							if (empty($put) or $put["status_code"] <> 200) {
								$msg_erro .= "Erro ao atualizar Usuário no CHAT";
							}

						}

						$sql_admin = "SELECT parametros_adicionais FROM tbl_admin WHERE admin = $admin";
						$res_admin = pg_query($con, $sql_admin);
						$arr_parametros_adicionais = array();

						if (pg_num_rows($res_admin) > 0) {
							$arr_parametros_adicionais = json_decode(pg_fetch_result($res_admin, 0, 'parametros_adicionais'), true);
						}

						if (!empty($admin_chat_usuario)) {
							$arr_parametros_adicionais["usuario_chat_id"] = $admin_chat_usuario;
						}

						if (!empty($arr_parametros_adicionais)) {
							$parametros_adicionais = "'" . json_encode($arr_parametros_adicionais) . "'";
						} else {
							$parametros_adicionais = 'NULL';
						}

						$sql = "UPDATE tbl_admin
									SET parametros_adicionais = $parametros_adicionais
								WHERE admin = $admin
								AND fabrica = $login_fabrica";
						$res = pg_query($con, $sql);

					}
				*/

				if ($ativo == 'f') {
					/**
					 * @hd 763097 - toda vez que o usuario é inativado, excluir todos os registros na tbl_programa_restrito
					 */
					$sqlDelPR = "DELETE FROM tbl_programa_restrito WHERE admin = $admin AND fabrica = $login_fabrica";
					$qryDelPR = pg_query($con, $sqlDelPR);
					$msg_erro = pg_last_error($con);

				}

			}

		}

	}

	if (!empty($msg_erro)){
		pg_rollBack();
		echo "erro|$msg_erro";
	}else{
		$campos = array();
		pg_commit();

		$auditorLog->retornaDadosTabela()
				   ->enviarLog('update', "tbl_admin", $login_fabrica."*".$admin);

		$sql = "SELECT * FROM tbl_admin WHERE admin = " . anti_injection($admin);
		$res = pg_query($con, $sql);

		$arr =  pg_fetch_assoc($res,0);

		foreach ($arr as $key => $value) {
			if ($key == 'senha'){
				$value = sha1($value);
			}
			$campos[] = $value;
			"$key -> $value\n";
		}
		$campos = implode('|', $campos);

		echo "ok|$campos";
	}
	exit;
}




if ($btn_acao == 'gravar2') {

	$admin         = trim($_POST['admin_']);
	$login         = trim($_POST['login_']);
	$senha         = trim($_POST['senha_']);
	$nome_completo = trim($_POST['nome_completo_']);
	$fone          = trim($_POST['fone_']);
	$celular       = trim($_POST['celular_']);
	$whatsapp      = trim($_POST['whatsapp_']);
	if (!in_array($login_fabrica, [180,181,182])) {
		$fone                      = str_replace(['(',')','-'], '', $fone);
		$celular                   = str_replace(['(',')','-'], '', $celular);
		$whatsapp                  = str_replace(['(',')','-', ' '], '', $whatsapp);
	}
	
	if (strlen($whatsapp) > 0) {

		$ddd = $whatsapp[0] . $whatsapp[1];

		if (intval($ddd) < 11) {

			$msg_erro .= 'Número do whatsapp com DDD inválido';
		}
	}

	$email         = trim($_POST['email_']);
 
	$login         = trim(strtolower($login));
	$senha         = trim(strtolower($senha));
	$sql_confere   = "SELECT * FROM tbl_admin WHERE admin = " . anti_injection($admin);
	$res_confere   = @pg_query($con, $sql_confere);

	if (is_resource($res_confere)) {

		// Cria uma variável adm_* com cada campo do registro adm_admin, adm_login, etc.
		extract(pg_fetch_assoc($res_confere, 0), EXTR_PREFIX_ALL, 'adm');

	}

	if (!empty($celular) && !in_array($login_fabrica, [180,181,182])) {
		$msg_erro .= valida_celular($celular);
	}

	/*if (!empty($fone)) {
		$msg_erro .= valida_fone($fone);
	}*/
	if (strlen($whatsapp) > 0) {
	
		if (strlen($whatsapp) < 10 || strlen($whatsapp) > 15) {
		
			$msg_erro .= "Número de whatsapp inválido! <br>";
		}
	}

	if (!is_email($email))
		$msg_erro.= traduz("E-mail digitado (%) inválido!", null, null, [$email])."<br />";
	//echo "<pre>";print_r($_POST);exit;
	if ($senha  != sha1($adm_senha)) {
		$verificar = validaSenhaAdmin($senha,$login);
		if ( $verificar === true){

			$senha_updt = "senha = '$senha' ,";

		}else{
			$msg_erro = $verificar;
		}
	}

	$p_add = '';

	$sql_p = "SELECT parametros_adicionais FROM tbl_admin WHERE admin = $login_admin";
	$res_p = pg_query($con, $sql_p);
	if (pg_num_rows($res_p) > 0) {
		$p_add = json_decode(pg_fetch_result($res_p, 0, 'parametros_adicionais'), true);
		
		if (!empty($celular)) {
			$p_add['celular'] = $celular;
		}

		$p_add = json_encode($p_add);
	} else {
		if (!empty($celular)) {
			$p_add = array('celular' => $celular);
			$p_add = json_encode($p_add);
		}
	}

	if (strlen($admin) > 0  and empty($msg_erro)) {
		if (!empty($whatsapp)) {
			$campo_whats = " whatsapp = '$whatsapp',";
		}

		$sql = "UPDATE tbl_admin SET
					$senha_updt
					nome_completo     = '$nome_completo',
					fone              = '$fone',
					email             = '$email',
					$campo_whats
					parametros_adicionais = '$p_add'
				WHERE tbl_admin.admin = '$login_admin'";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);
	}

	if (strlen($msg_erro) == 0) {
		$sucesso = true;
	}

}

$title       = traduz("Privilégios para o Administrador");
$cabecalho   = traduz("Cadastro de Postos Autorizados");
$layout_menu = "gerencia";

include 'cabecalho.php';

$sql_nome_admin = "SELECT nome_completo FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica};";
$res_nome_admin = pg_query($con, $sql_nome_admin);
$nome_admin     = @pg_fetch_result($res_nome_admin, 0, 'nome_completo');


$sql = "SELECT privilegios FROM tbl_admin WHERE admin = $login_admin";
$res = pg_query($con,$sql);

$privilegios = pg_fetch_result($res, 0, 0);

if(!empty($qtde_admin)){
	$sql = "SELECT count(admin) AS total_admin FROM tbl_admin WHERE fabrica = $login_fabrica and ativo IS TRUE";
	$res = pg_query($con,$sql);
	$total_admin = pg_fetch_result($res, 0, 'total_admin');
}

?>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">

<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>

<style type="text/css">

	input {
		font-size: 10px;
	}

	.top_list {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		color:#596d9b;
		background-color: #d9e2ef;
	}

	.line_list {
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: x-small;
		font-weight: normal;
		color:#596d9b;
		background-color: #ffffff;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 10px;
		border-collapse: collapse;
		border:1px solid #121768;
	}

	table.tabela tr th{
		font-family: verdana;
		font-size: 10px;
		border-collapse: collapse;
		border:1px solid #121768;
	}

	table.tabela_inativa tr td{
		font-family: verdana;
		font-size: 10px;
		border-collapse: collapse;
		border:1px solid #8D0B0B;
	}

	table.tabela_inativa tr th{
		font-family: verdana;
		font-size: 10px;
		border-collapse: collapse;
		border:1px solid #8D0B0B;
	}

	p{
		font:12px Arial;
		padding:10px;
	}

	.texto_avulso{
		font: 14px Arial !important; color: rgb(89, 109, 155);
		background-color: #d9e2ef;
		text-align: center;
		width:700px;
		margin: auto;
		border-collapse: collapse;
		text-align:justify;
		float: auto;
	}

	.licencas {
		float: left;
		width: 100% !important;
		margin-top: 0px;
		margin-bottom: 10px;
		display: block;
	}

	.licenca_disponivel {

	    font: bold 14px Arial !important;
	    color: white;
	    background-color: #32CD32;
	    width: 165px;
	    display: inline-block;
	    height: 55px;
	    text-align: center;
	    padding: 10px;
	    margin-left: 10px;
	}

	.licenca_contratada {
	    font: bold 14px Arial !important;
	    color: white;
	    background-color: #FFA500;
	    width: 165px;
	    display: inline-block;
	    height: 55px;
	    text-align: center;
	    padding: 10px;
	    margin-right: 10px;
	}
	.texto_licenca {
		font: bold 26px Arial !important; 
		color: white;
	}

	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.titulo_coluna_inativa{
		background-color:#E87272;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial" !important;
		color:#FFFFFF;
		text-align:center;
	}

	.titulo_tabela_inativa{
		background-color:#E87272;
		font: bold 14px "Arial" !important;
		color:#FFFFFF;
		text-align:center;
	}

	.sucesso{
		background-color:#008000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
		margin:auto;
		width:700px;
	}

	.msg_erro, #erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
		margin: auto;
		width: 700px;
	}

	.subtitulo{
		font-family     : verdana;
		font-size       : 16px;
		font-weight     : 700;
		font-style      : normal;
		color           : #FFFFFF;
		text-transform  : none;
		text-decoration : none;
		letter-spacing  : normal;
		word-spacing    : 0;
		line-height     : 20px;
		text-align      : center;
		background-color: #7092BE;
	}
	.fa {
		max-width: 16px;
		max-height:16px;
	}


	table.tabela td{
		vertical-align: middle !important;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	.edit_tr{
		background-color:#A4FC9B !important;
	}

	.bg_error{
		background-color:#FF6262;
	}
	.txt_callcenter {
		font: bold 14px Arial !important; 
		color: white;
		background-color: #33FFD7;
		width: 150px;
		display: inline-block;
		height: 55px;
		text-align: center;
		padding: 10px;
		margin-right: 10px;
	}

	.txt_outros {
		font: bold 14px Arial !important; 
		color: white;
		background-color: #EC33FF;
		width: 150px;
		display: inline-block;
		height: 55px;
		text-align: center;
		padding: 10px;
		margin-right: 10px;
	}
</style>

<?php
	include '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
/****** INCLUDE ******/
$plugins = array(
	"select2"
);
include("plugin_loader.php");
?>
<script type='text/javascript'>
	var self = window.location.pathname; //variavel que pega o endereço da url

	function checkMail(mail){
		var er = new RegExp(/^[A-Za-z0-9_\-\.]+@[A-Za-z0-9_\-\.]{2,}\.[A-Za-z0-9]{2,}(\.[A-Za-z0-9])?/);
		if(typeof(mail) == "string"){
			if(er.test(mail)){
				return true;
			}
		}else if(typeof(mail) == "object"){
			if(er.test(mail.value)){
				return true;
			}
		}else{
			return false;
		}
	}

	function formatCurrency(num) {
		num = isNaN(num) || num === '' || num === null ? 0.00 : num;
		return parseFloat(num).toFixed(2);
	}

	function formSubmit(){

		var chatOK = validaChat();
		var erro;

		if (chatOK == true){

			<?php if (strpos($privilegios,'*') === false) { ?>

				if (!$('input[name=senha_]').val()){
					$('input[name=senha_]').addClass('bg_error');
					$('#erro').show().addClass('msg_erro').html('<?=traduz('Os campos em vermelho são de preenchimento obrigatório')?>');
					$('#erro_php').hide();
					$('.sucesso').hide();
					erro = 1;
				}else{
					if ($('input[name=senha_]').hasClass('bg_error')){
						$('input[name=senha_]').removeClass('bg_error');
						if (erro == 0){
							erro = 0;
						}
					}
				}

				if (!$('input[name=email_]').val()){
					$('input[name=email_]').addClass('bg_error');
					$('#erro').show().addClass('msg_erro').html('<?=traduz('Os campos em vermelho são de preenchimento obrigatório')?>');
					$('#erro_php').hide();
					$('.sucesso').hide();
					erro = 1;
				}else{
					if ($('input[name=email_]').hasClass('bg_error')){
						$('input[name=email_]').removeClass('bg_error');
						if (erro == 0){
							erro = 0;
						}
					}
				}

			<?php }else{ ?>

				qtde_novos = $("#qtde_novos").val();

				for (i = 0; i < qtde_novos; i++) {

					if (!$('input[name=login_novo_'+i+']').val()){

						$('input[name=login_novo_'+i+']').addClass('bg_error');
						$('#erro').show().addClass('msg_erro').html('<?=traduz('Os campos em vermelho são de preenchimento obrigatório')?>');
						$('#erro_php').hide();
						$('.sucesso').hide();
						erro = 1;

					}else{

						if ($('input[name=login_novo_'+i+']').hasClass('bg_error')){

							$('input[name=login_novo_'+i+']').removeClass('bg_error');

							if (erro == 0){

								erro = 0;

							}

						}

					}

					if (!$('input[name=senha_novo_'+i+']').val()){
						$('input[name=senha_novo_'+i+']').addClass('bg_error');
						$('#erro').show().addClass('msg_erro').html('<?=traduz('Os campos em vermelho são de preenchimento obrigatório')?>');
						$('#erro_php').hide();
						$('.sucesso').hide();
						erro = 1;
					}else{
						if ($('input[name=senha_novo_'+i+']').hasClass('bg_error')){
							$('input[name=senha_novo_'+i+']').removeClass('bg_error');
							if (erro == 0){
								erro = 0;
							}
						}
					}

					if (!$('input[name=email_novo_'+i+']').val()){
						$('input[name=email_novo_'+i+']').addClass('bg_error');
						$('#erro').show().addClass('msg_erro').html('<?=traduz('Os campos em vermelho são de preenchimento obrigatório')?>');
						$('#erro_php').hide();
						$('.sucesso').hide();
						erro = 1;
					}else{
						if ($('input[name=email_novo_'+i+']').hasClass('bg_error')){
							$('input[name=email_novo_'+i+']').removeClass('bg_error');
							if (erro == 0){
								erro = 0;
							}
						}
					}

				}
			<?php } ?>
			if (erro == 1){
				return false;
			}
			<?php if (strpos($privilegios,'*') === false) { ?>
				document.frm_admin.btn_acao.value='gravar2';
			<?php }else{ ?>

				document.frm_admin.btn_acao.value='gravar';
			<?php } ?>

			document.frm_admin.submit();
		} else {
			$("#erro").html('<?=traduz("Limite de usuários de chat excedido pelo fabricante")?>').show();
			$(document).scollTop()
		}

	}

	function validaChat() {
		var total_chat_check = $('input.live_help:checked').length;

		var ret = false;

		inputs = 'ajax=ajax&tipo=validaChat';
		$.ajax({
			type:    'GET',
			url:     self,
			data:    inputs,
			async:   false,
			success: function(data){
				data = data.split('|');

				qtde_chat           = parseInt(data[0]);
				qtde_chat_fabrica   = parseInt(data[1]);
				var total_liberado  = qtde_chat_fabrica > qtde_chat ? qtde_chat_fabrica : qtde_chat;
				var diferenca       = total_chat_check - total_liberado;

				if (total_chat_check <= total_liberado) ret = true;

				/*if(total_chat_check > total_liberado){
					perguntaChat(diferenca, qtde_chat_fabrica, qtde_chat);
					return false;
				}*/

			}
		});

		return ret;

	}

	function perguntaChat(diferenca, usuario_cadastrado, numero_permitido){
		var total_chat_check = $('input[name*="live_help_"]:checked').length;

		if(usuario_cadastrado > 0){
			var usuario_acima  = total_chat_check - numero_permitido;
		}else{
			var usuario_acima  = total_chat_check - numero_permitido;
			usuario_cadastrado = numero_permitido;
		}

		var soma_diferenca = formatCurrency(usuario_acima * 200);

		var pergunta  = "<div style='text-align: justify; width: 600px; margin:auto;'><b>"+$("#nome_admin").val()+"</b>, '<?=traduz("você excedeu o número máximo de usuários gratuitos para atendimento on line: ")?>'<br><br>'<?=traduz("Sua empresa hoje tem ")?>'+usuario_cadastrado+ '<?=traduz("usuários já cadastrados, ")?>'+usuario_acima+ '<?=traduz("acima do número máximo de usuários gratuitos, importando na cobrança mensal de R$ ")?>'+soma_diferenca+</div>";

			pergunta += "<div style='text-align: center; padding: 10px 50px;margin:auto; color: #F00;  width: 500px;'>'<?=traduz("Após o aceite será incluído na fatura o valor de R$ 200.00 (duzentos reais) por usuário excedente e a cobrança será suspensa somente no mês subsequente ao cancelamento dessa inclusão.")?>'</div>";

		apprise(pergunta, {

			'verify' 	: true,
			'textYes'	: '<?=traduz('Concordo!')?>',
			'textNo'	: '<?=traduz('Não Concordo')?>',
			'animate'	: true
			},
			function(resposta){
			    if(resposta){
			    	var pergunta = "<div style='text-align: justify; width: 400px;'>'<?=traduz("Concordo com a inclusão desse serviço, estou ciente dos valores que serão cobrados contra nossa empresa, dispensando a necessidade de assinatura de aditivo ao contrato, ficando automaticamente aceita a cobrança após esta interação.<br><br><b>Tem certeza da inclusão?</b></div>")?>'";

			    	apprise(pergunta, {
						'verify' 	: true,
						'textYes'	: '<?=traduz('Sim')?>',
						'textNo'	: '<?=traduz('Não')?>',
						'animate'	: true
						},
						function(resposta){
							if(resposta){
								document.frm_admin.btn_acao.value='gravar';
								document.frm_admin.submit();
							}else{
								validaChat();
							}
						});
				} else {
					//alert('nao concordo');
					return false;
				}
		});
	}

	function verificaQtdeAdmin(nao_salva = false){
		var qtde_admin = $("input[name=qtde_admin]").val();
		var ret;
		var pergunta  = '<?=traduz("Sua empresa hoje tem o limite máximo de ")?>'+qtde_admin+'<?=traduz(" usuários já cadastrados.</br>Para cadastar um novo usuário entre em contato com o suporte Telecontrol.")?>';

		$.ajax({
			async: false,
			url: "admin_senha_n.php?ajax_qtde=sim&qtde_admin="+qtde_admin,
			cache: false,
			complete: function(data){
				if (nao_salva == true) {
						alert(pergunta);
						return false;
					} 
				if (data.responseText == "ok") {
					ret = true;
				} else {
					alert(pergunta);
					ret = false;
				}
			}
		});
		return ret;
	}

	function retiraAcentos(obj) {
		obj.value = obj.value.replace(/\W/g, "");
	}

	function clicaMasterNovo(objeto){

			if ( $("input[name=master_novo_"+objeto+"]").attr('checked') ){

				$("input[name=gerencia_novo_"+objeto+"]").attr('checked',true);
				$("input[name=cadastros_novo_"+objeto+"]").attr('checked',true);
				$("input[name=call_center_novo_"+objeto+"]").attr('checked',true);
				$("input[name=info_tecnica_novo_"+objeto+"]").attr('checked',true);
				$("input[name=financeiro_novo_"+objeto+"]").attr('checked',true);
				$("input[name=auditoria_novo_"+objeto+"]").attr('checked',true);

			}else{

				$("input[name=gerencia_novo_"+objeto+"]").attr('checked',false);
				$("input[name=cadastros_novo_"+objeto+"]").attr('checked',false);
				$("input[name=call_center_novo_"+objeto+"]").attr('checked',false);
				$("input[name=info_tecnica_novo_"+objeto+"]").attr('checked',false);
				$("input[name=financeiro_novo_"+objeto+"]").attr('checked',false);
				$("input[name=auditoria_novo_"+objeto+"]").attr('checked',false);

			}
	}

	function calculaAdmin() {
		$.ajax({
			type:    'GET',
			url:     'admin_senha_n.php',
			data:    {ajax_calcula:true},
			async:   false,
			success: function(data){
				var ret = JSON.parse(data)
				$(".txt_disponivel").text(ret.qtd_admins_disponiveis)
				$(".txt_contratado").text(ret.qtd_contratadas)
			}
		});
	}

	$(function() {
		Shadowbox.init();
		var oldValues;
		var id_edicao;
		var btn_ver_anexo = "";

		//$('.fone').mask("(99) 9999-9999");
		<?php
		if (in_array($login_fabrica, [158])) { ?>
			$(".cliente_admin_multiplo").select2();
		<?php
		}

		if (in_array($login_fabrica, [1])) { ?>
			$(".multi_contas").select2();
		<?php
		}
		?>

		<?php
		if (in_array($login_fabrica, [169,170])) { ?>
			$(".tipo_protocolo").select2();
		<?php
		} ?>

		$(".cel").keyup(function(){         
			var valor = $(this).attr('value');
			valor = valor.replace(/\D/g,'');
			$(this).val(valor);

		});

		$(".tel").keyup(function(){         
			var valor = $(this).attr('value');
			valor = valor.replace(/[a-zA-z]/g,'');
			$(this).val(valor);

		});

		$("#arquivo_firma").change(function() {
			var arquivo  = this.files[0];
			var MIMEType = arquivo.type;

			if (!/^image\//.test(MIMEType)) {
				$("#assinatura button,#imagem_firma").hide('fast');
				alert('<?=traduz("O arquivo deve ser uma imagem PNG ou JPG!")?>');
				return false;
			}

			// mostra a imagem local, ainda não subiu
			var preview = $("#imagem_firma");
			var imagem = new FileReader();
			imagem.onload = function(e) {
				$("#imagem_firma").attr('src', e.target.result);
			};
			imagem.readAsDataURL(arquivo);
		});

		$('.table_update').fixedtableheader({
			 headerrowsize:1
		});

		$('input[name*="live_help_"]').click(function() {

		  	var id       = $(this).attr('rel');
		  	var nome     = "";
		  	var email    = "";
		  	
		  	if ($("input[name='nome_completo_"+id+"']").val() != "" && $("input[name='nome_completo_"+id+"']").val() != undefined) {
		  		nome     = $("input[name='nome_completo_"+id+"']").val();
		  	} else if ($("input[name='nome_completo_novo_"+id+"']").val() != "" && $("input[name='nome_completo_novo_"+id+"']").val() != undefined) {
		  		nome     = $("input[name='nome_completo_novo_"+id+"']").val();
		  	}
		  	if ($("input[name='email_"+id+"']").val() != "" && $("input[name='email_"+id+"']").val() != undefined) {
		  		email    = $("input[name='email_"+id+"']").val();
		  	} else if ($("input[name='email_novo_"+id+"']").val() != "" && $("input[name='email_novo_"+id+"']").val() != undefined) {
		  		email    = $("input[name='email_novo_"+id+"']").val();
		  	}

		  	var msg_erro = 0;

		  	if(nome.length < 3)
		  		msg_erro = 1;

		  	if(!checkMail(email)){
		  		msg_erro = 1
		  	}

		  	if(msg_erro == 1){
		  		$(this).attr("checked",false);
				var pergunta = '<?=traduz("Para ser cadastrado no chat o usuário deve ter nome e e-mail válidos!")?>';

				apprise(pergunta, {
							'animate'   : true
						}
				);
			}
		});

		$('#novo_admin').live('click',function(){

			qtde_novos = $('#qtde_novos').val();
			qtde = qtde_novos;
			$.get(self, {'ajax':'true', 'action': 'novo','qtde': qtde_novos},
			  function(data){

				$(data).appendTo("#cad_novos");
				//$('.fone_'+qtde).mask("(99) 9999-9999");

			});
			qtde_novos++;
			$('#qtde_novos').val(qtde_novos);

		});


		$('.ativo').live('change',function() {
			var id = $(this).attr('rel');
			if ($(this).is(':checked')) {
				$('.novo_'+id).removeAttr('disabled');
			} else {
				$('.novo_'+id).attr('disabled','disabled');
			}
		});

		$('.master').live('click',function() {
			var tr_id = $(this).attr('rel');

			if ( $("input[name=master_"+tr_id+"]").is(':checked') ){

				$(".privilegios_"+tr_id).attr('checked',true).attr('disabled',true).css('cursor','');

			}else{

				$(".privilegios_"+tr_id).attr('checked',false).attr('disabled',false).css('cursor','pointer');

			}

		});

		$('.table_update tr').click(function(){
			tr_id = $(this).attr('rel');

			if ( (oldValues != undefined || oldValues != null) && $(this).attr('id') != id_edicao && btn_ver_anexo != 'ver' ){
				alert('<?=traduz('Feche o admin que está em edição')?>');
				return false;
			}

			id_edicao = $(this).attr('id');
			oldValues = $(this).html();


			if ( !$(this).hasClass('edit_tr') && $(this).hasClass('resultado') ){

				var fab = '<?=$login_fabrica?>';

				if (fab == 1) {
					btn_ver_anexo = $("button[name=ver_"+tr_id+"]").attr("data-ver");;
					if (btn_ver_anexo == 'ver') {
						return false;
					}
				}

				$('tr#dados_'+tr_id+' input[type=text]').show();
				$('#cliente_admin_'+tr_id).attr('disabled',false);
				$('#cliente_admin_multiplo_'+tr_id).attr('disabled',false);
				$('#tipo_protocolo_'+tr_id).attr('disabled',false);
				$('tr#dados_'+tr_id+' select').show();

				$('tr#dados_'+tr_id+' input[type=text]').show();
				$('#l10n_'+tr_id).attr('disabled',false);
				$('tr#dados_'+tr_id+' select').show();

				$('tr#dados_'+tr_id+' label').hide();

				$('#senha_'+tr_id).show();
				$('#senha_'+tr_id).attr('disabled',false);

				if ($(this).hasClass('inativo')){
					$('input[name=ativo_'+tr_id+']').attr('disabled',false);
					$('tr#dados_'+tr_id+' input[type=checkbox]').css('cursor','');
				}else{
					$('tr#dados_'+tr_id+' input[type=checkbox]').attr('disabled',false);
					$('tr#dados_'+tr_id+' input[type=checkbox]').css('cursor','pointer');
				}

				$(this).addClass('edit_tr');
				$('#gravar_'+tr_id).addClass('edit_tr');
				$('#gravar_'+tr_id).show();
				$('#btn_gravar_'+tr_id).show();
				$('#btn_fechar_'+tr_id).show();
				$('#auditor_log_'+tr_id).show();
			}

		});


		$('input[name=btn_gravar_item]').click(function(){

			tr_id = $(this).attr('rel');

			inputs2 = $('#dados_'+tr_id).find('input').serialize()+"&"+$('#dados_'+tr_id).find('select').serialize()+"&ajax=true&action=update&id_linha="+tr_id;

			<?php 
			if (in_array($login_fabrica, [158])) { ?>

				let arrClientes = $("#cliente_admin_multiplo_"+tr_id).val();

				inputs2 = $('#dados_'+tr_id).find('input').serialize()+"&ajax=true&action=update&id_linha="+tr_id;

				if (arrClientes != null) {
					inputs2 += "&cliente_admin_multiplo_"+tr_id+"="+arrClientes.join(",");
				}

			<?php 
			} ?>

			<?php 
			if (in_array($login_fabrica, [169,170])) { ?>

				let arrTipos = $("#tipo_protocolo_"+tr_id).val();

				inputs2 = $('#dados_'+tr_id).find('input').serialize()+"&ajax=true&action=update&id_linha="+tr_id;

				if (arrTipos != null) {
					inputs2 += "&tipo_protocolo_"+tr_id+"="+arrTipos.join(",");
				}

			<?php 
			} ?>

			$('#btn_gravar_'+tr_id).hide();
			$('#btn_fechar_'+tr_id).hide();
			$('#auditor_log_'+tr_id).hide();

			var chatOK = validaChat();
			var qtdeAdmin = true;
		
			var ativo = inputs2.split("_").join("=").split("=").join("&").split("&");

			if (ativo.indexOf("ativo") > -1) {
				ativo = true;
			} else {
				ativo = false;
			}

			<?php 

			$qtd = calculaAdmin();

			if ($qtd['qtd_admins_disponiveis'] < 0) { ?> 
	
				if ( ativo == true) {

					qtdeAdmin = verificaQtdeAdmin(true);
				}

			<?php } ?>

			if (chatOK == true && qtdeAdmin == true){
				$.get(self, inputs2,
				function(data){

					data = data.split("|");
					//alert(data[0]);
					//alert(data[1]);
					if (data[0]== 'erro'){

						$("#gravar_erro_"+tr_id).show();

						$('#btn_gravar_'+tr_id).show();
						$('#btn_fechar_'+tr_id).show();
						$('#auditor_log_'+tr_id).show();

						$("#gravar_erro_"+tr_id+" td").html(data[1]);

					}else{

						//SE NAO DA ERRO, o data[] (a partir do indice 1) recebe os dados atualizados do admin. segue ordem:
						//1 admin
						//2 fabrica
						//3 senha                   ->  OBS: este campo ja vem criptografado com sha1
						//4 login
						//5 privilegios
						//6 email
						//7 help_desk_supervisor
						//8 fone
						//9 nome_completo
						//10 ativo
						//11 data_expira_senha
						//12 pais
						//13 ultimo_acesso
						//14 ultimo_ip
						//15 responsabilidade
						//16 tela_inicial_posto
						//17 callcenter_supervisor
						//18 responsavel_troca
						//19 participa_agenda
						//20 data_aniversario
						//21 dia_nascimento
						//22 mes_nascimento
						//23 admin_sap
						//24 cliente_admin
						//25 cliente_admin_master
						//26 fale_conosco
						//27 intervensor
						//28 nao_disponivel
						//29 altera_pedido
						//30 grupo_admin
						//31 atendente_callcenter
						//32 responsavel_postos
						//33 consulta_os
						//34 altera_pais_produto
						//35 responsavel_ti
						//36 live_help
						$('#admin_'+tr_id).val(data[1]);

						//LOGIN
						$('#lbl_login_'+tr_id).html(data[4]);
						$('#lbl_login_'+tr_id).show();
						$('#login_'+tr_id).hide();
						$('#login_'+tr_id).val(data[4]);

						//SENHA
						$('#senha_'+tr_id).val(data[3]);
						$('#senha_'+tr_id).attr('disabled','disabled');

						//NOME COMPLETO
						$('#nome_completo_'+tr_id).hide();
						$('#nome_completo_'+tr_id).val(data[9]);
						$('#lbl_nome_completo_'+tr_id).html(data[9]);
						$('#lbl_nome_completo_'+tr_id).show();

						//FONE
						$('#fone_'+tr_id).hide();
						$('#fone_'+tr_id).val(data[8]);

						console.log(data);
						//CELULAR
						data_cel = $.parseJSON(data[42]);
						cel = [];
						$.each(data_cel, function (chave,valor)
						{
						  cel[chave] = valor;
						});
						
						$('#celular_'+tr_id).hide();
						$('#celular_'+tr_id).val(cel['celular']);

						$('#whatsapp_'+tr_id).hide();
						$('#whatsapp_'+tr_id).val(data[48]);

						<?php
						if ($usa_admin_ramal) {
						?>
							$('#ramal_'+tr_id).val(data[39]).hide();
							$('#lbl_ramal_'+tr_id).html(data[39]).show();
						<?php
						}
						?>
 
						$('#lbl_fone_'+tr_id).html(data[8]);
						$('#lbl_fone_'+tr_id).show();

						$('#lbl_whatsapp_'+tr_id).html(data[48]);
						$('#lbl_whatsapp_'+tr_id).show();

						$('#lbl_celular_'+tr_id).html(cel['celular']);
						$('#lbl_celular_'+tr_id).show();

/*						$('#lbl_whatsapp_'+tr_id).html(['whatsapp']);
						$('#lbl_whatsapp_'+tr_id).show();*/

						//EMAIL
						$('#email_'+tr_id).hide();
						$('#'+tr_id).val(data[6]);
						$('#lbl_email_'+tr_id).html(data[6]);
						$('#lbl_email_'+tr_id).show();

						//cliente admin
						$('#cliente_admin_'+tr_id).attr('disabled','disabled');

						//idioma
						$('#l10n_'+tr_id).attr('disabled','disabled');

						//país
						$('#pais_'+tr_id).val(data[12]);
						$('#pais_'+tr_id).hide();
						$('#lbl_pais_'+tr_id).html(data[12]);
						$('#lbl_pais_'+tr_id).show();

						//desabilita todos os checkboxes
						$('tr#dados_'+tr_id+' input[type=checkbox]').attr('disabled',true);
						if (data[10]=='f'){
							$('tr#dados_'+tr_id).addClass('inativo');
						}

						if ($('tr#dados_'+tr_id).hasClass('inativo')){
						
						/*  
							if ( $("input[name=ativo_"+tr_id+"]").attr('checked') ){
								window.location.reload();
							}
						*/

							window.location.reload();

						}

						$('#dados_'+tr_id).removeClass('edit_tr');
						$('#gravar_'+tr_id).hide();
						$('#gravar_erro_'+tr_id).hide();

						oldValues = null;
						id_edicao = null;

					}
					

				<?php 

					if (validaAdmin() == true) { ?>

						calculaAdmin();

					<?php } ?>
				});
			}else{

				$('#dados_'+tr_id).html(oldValues);

				$('tr#dados_'+tr_id+' input[type=text]').hide();
				$('tr#dados_'+tr_id+' select').hide();
				$('tr#dados_'+tr_id+' label').show();
				$('#senha_'+tr_id).show();
				$('#senha_'+tr_id).attr('disabled',true);

				$('#cliente_admin_'+tr_id).attr('disabled',true);
				$('#l10n_'+tr_id).attr('disabled',true);
				$('tr#dados_'+tr_id+' input[type=checkbox]').attr('disabled',true);
				oldValues = null;
				id_edicao = null;
			}
			return false;

		});

		$('input[name=btn_fechar_item]').click(function(){

			tr_id = $(this).attr('rel');
			$('#dados_'+tr_id).html(oldValues);

			$('tr#dados_'+tr_id+' input[type=text]').hide();
			$('#gravar_erro_'+tr_id).hide();
			$('tr#dados_'+tr_id+' select').hide();
			$('tr#dados_'+tr_id+' label').show();
			$('#senha_'+tr_id).show();
			$('#senha_'+tr_id).attr('disabled',true);
			$('#cliente_admin_'+tr_id).attr('disabled',true);
			$('#cliente_admin_multiplo_'+tr_id).attr('disabled',true).show();
			$('#tipo_protocolo_'+tr_id).attr('disabled',true).show();
			$('tr#dados_'+tr_id+' input[type=checkbox]').attr('disabled',true);

			$('tr#dados_'+tr_id+' input[type=checkbox]').css('cursor','');

			$('#dados_'+tr_id).removeClass('edit_tr');
			$('#gravar_'+tr_id).removeClass('edit_tr');
			$('#gravar_'+tr_id).hide();
			oldValues = null;
			id_edicao = null;

			return false;

		});

		$('a[name=auditor_log_item]').click(function(){
			return false;
		});

		/* title em todos os checkboxes, para facilitar */
		$(':checkbox[name^=ativo_]').attr('title','<?=traduz('Usuário ativo?')?>');
		$(':checkbox[name^=master_]').attr('title','<?=traduz('Usuario MASTER')?>');
		$(':checkbox[name^=sup_help_desk_]').attr('title','<?=traduz('Supervisor HelpDesk - Gerencia chamado Telecontrol')?>');
		$(':checkbox[name^=troca_reembolso_]').attr('title','<?=traduz('Troca - Reembolso')?>');
		$(':checkbox[name^=responsavel_ti_]').attr('title','<?=traduz('Gerente TI - Responsável integração, FTP, etc.')?>');
		$(':checkbox[name^=gerencia_]').attr('title','<?=traduz('Área de Gerencia (relatórios gerenciais, BI, gerenciamento de Usuários...)')?>');
		$(':checkbox[name^=cadastros_]').attr('title','<?=traduz('Área de Cadastros (postos, produtos, peças...)')?>');
		$(':checkbox[name^=call_center_]').attr('title','<?=traduz('Área de Call-Center')?>');
		$(':checkbox[name^=supervisor_call_center_]').attr('title','<?=traduz('Supervisor do Call-Center')?>');

		<?php if($usa_somente_callcenter){ ?>
		$('input[name^=somente_visualiza_call_center_]').on("click",function(){
			var posicao = $(this).attr('rel');
			if( $(this).is(":checked") ){
				if(  $("#master_"+posicao).is(":checked") ){
					$(this).attr("checked", false);
				}
			}
		});

		$('input[name^=master_]').on("click",function(){
			var posicao = $(this).attr('rel');
			if ($("input[name=somente_visualiza_call_center_"+posicao+"]").is(":checked")) {
				$("input[name=somente_visualiza_call_center_"+posicao+"]").attr("checked", false);
			}

		});
		$(':checkbox[name^=somente_visualiza_call_center_]').attr('title','<?=traduz('Somente Visualizar Call-Center')?>');
		<?php }?>
		$(':checkbox[name^=live_help_]').attr('title','<?=traduz('Tira dúvidas via Chat')?>');
		$(':checkbox[name^=info_tecnica_]').attr('title','<?=traduz('Área de Informações Técnicas e Comunicados')?>');
		$(':checkbox[name^=financeiro_]').attr('title','<?=traduz('Área Financeira')?>');
		$(':checkbox[name^=inspetor_]').attr('title','<?=traduz('Inspetor')?>');
		$(':checkbox[name^=auditoria_]').attr('title','<?=traduz('Área de Auditoria')?>');
		$(':checkbox[name^=promotor_wanke_]').attr('title','<?=traduz('Promotor Wanke')?>');
		$(':checkbox[name^=responsavel_postos_]').attr('title','<?=traduz('Atende Postos Autorizados')?>');
		$(':checkbox[name^=consulta_os_]').attr('title','<?=traduz('Cliente externo consulta OS')?>');
		$(':checkbox[name^=altera_pais_produto_]').attr('title','<?=traduz('O usuário pode alterar para que pais está disponibilizado um produto')?>');
		$(':checkbox[name^=sap_]').attr('title','<?=(in_array($login_fabrica, array(1,151,153))) ? traduz("Atende Helpdesk do Posto") : traduz("Inspetor")?>');
		$(':checkbox[name^=atendente_callcenter_]').attr('title','<?=traduz('Usuário do Call-Center')?>');
		$(':checkbox[name^=fale_conosco_]').attr('title','<?=traduz('Recebe e-mail do "Fale Conosco" integrado com a Telecontrol')?>');
		$(':checkbox[name^=email_assinatura_]').attr('title','<?=traduz('Envia o e-mail com assinatura')?>');
		$(':checkbox[name^=intervensor_]').attr('title','<?=traduz('Interventor de Call-Center')?>');

		$(".btn_ver").on('click', function(){
			let admin_assinatura = $(this).attr('data-admin');
			let data_posicao = $(this).attr('data-posicao');
			$("button[name=ver_"+data_posicao+"]").attr("data-ver", "ver");

			Shadowbox.init();
			Shadowbox.open({
				content: window.location.href+"?ver_assinatura="+admin_assinatura,
				player: "iframe",
				width:  500,
				height: 300
			});
		});

	});
</script>
<div clas="msg_erro" id="erro" style="display:none"></div>
<?php

if (strlen($msg_erro) > 0) {?>
	<div class="msg_erro" id="erro_php"><?=$msg_erro;?></div>
<?php

}

if ($sucesso){?>
	<div class="sucesso"><?=traduz('Gravado com Sucesso')?></div><?php
}


if ($login_privilegios != '*') echo "<center><h1>".traduz("Você não tem permissão para gerenciar usuários.")."</h1></center>";

if ($abre_os_admin_arr) {
	$th_cliente_admin = traduz("<th>Cliente Admin</th>");

	if ($login_fabrica != 96) $th_cliente_admin .= traduz("<th>Cliente Admin Master</th>");
}

if ($usa_atende_hd_postos) {
	$th_hd_posto = "<th width='64px'>";
	$th_hd_posto.= (!in_array($login_fabrica, array(11,169,170,172,183))) ? "<abbr title='".traduz("Atendente de Chamados dos Postos")."'>SAP</abbr>" : traduz("Inspetor");
	$th_hd_posto.= "</th>\n";
}

if ($usa_responsavel_postos) {

    if($login_fabrica != 30){
        $th_hd_posto .= traduz("<th>Responsável Postos</th>\n");
	}else{
        $th_hd_posto .= traduz("<th>Abrir Laudos</th>\n");
	}
}

if($login_fabrica == 115) {
	$th_hd_posto .= traduz("<th>Altera Categoria Posto</th>\n");
}

if($login_fabrica == 3){
	$th_hd_posto .= traduz("<th>Aprova Avulso 1</th>\n");
	$th_hd_posto .= traduz("<th>Aprova Avulso 2</th>\n");
}

unset($th_fale_conosco); //Começa do nada...

if ($usa_atendente_callcenter) {
	$th_fale_conosco .= traduz("<th>Atendente CallCenter</th>\n");
}

if ($usa_recebe_fale_conosco) {
	$th_fale_conosco .= ($login_fabrica == 1) ? traduz("<th>SAC</th>") : traduz("<th>Recebe Fale Conosco</th>\n");
}

if ($manda_email_assinatura) {
	$th_manda_email_assinatura .= traduz("<th>Envia E-mail</th>\n");
}

if ($usa_intervensor) {
	if( in_array($login_fabrica, array(11,172)) )
		$th_fale_conosco .= traduz("<th>Interventor</th>\n");
	else
		$th_fale_conosco .= traduz("<th>Interventor <br>de Callcenter</th>\n");
}

if ($usa_altera_pais_produto)
	$th_altera_pais_prod = traduz('<th title="O usuário poderá liberar produtos para outros países" >Altera País <br> Prod.</th>'."\n"); // HD 374998

if ($fabrica_multinacional)
	$th_pais = traduz("<th>PAÍS</th>\n");

if ($login_privilegios == '*')

	$th_sup_hd	= traduz("<th width='59px' title='Supervisor do Help-Desk'>Sup. <br> Help-Desk</th>\n");
	$th_libera_pedido =  ($login_fabrica == 168) ? traduz('<th title="Libera Pedido">Libera Pedido</th>')."\n" : '';

	$coluna_troca_reembolso = ($login_fabrica == 151) ? traduz("<th width='59px' title='Troca - Reembolso'>Troca <br> Reembolso </th>\n") : "";
	ob_start();?>

	<tr class='titulo_coluna' style='cursor:default'>
		<th>&nbsp;</th>
		<th style="width:150px !important"><?=traduz('Login')?></th>
		<th><?=traduz('Senha')?></th>
		<th><?=traduz('Nome')?></th>
		<th><?=traduz('Fone')?></th>
		<th><?=traduz('Celular')?></th>
		<th>WhatsApp</th>
		<?php if ($usa_admin_ramal) { echo traduz("<td>Ramal</td>"); } ?>
		<th>Email</th>
		<th><?=traduz('Idioma')?></th>
		<?=$th_pais . $th_cliente_admin?>
		<th width="32px"><?=traduz('Ativo')?></th>
		<th width="40px"><?=traduz('Master')?></th>
		<?=$th_sup_hd?>
		<?=$th_libera_pedido;?>
		<th width="30px"><?=traduz('Chat')?></th>
		<th width="46px"><?=traduz('Gerente')?><br />TI</th>
		<th width="50px"><?=traduz('Gerencia')?><br />*</th>
		<th width="57px"><?=traduz('Cadastros')?><br />*</th>
		<th width="63px"><?=traduz('Call-Center')?><br />*</th>
		<?php if (in_array($login_fabrica, array(169,170))) { ?>
			<th width="63px"><?=traduz('Recebe<br />Jornada')?></th>
		<?php } ?>
		<th width="63px"><?=traduz('Supervisor <br> Call-Center')?></th>
		<?php if (!in_array($login_fabrica, array(169,170,171,173,174,175,176,184,191,193,198,200,203)) || $usa_somente_callcenter) { ?>
			<th width="63px"><?=traduz('Somente <br> Visualizar <br> Call-Center')?></th>
		<?php }		
		if ($integracaoTelefonia === true || (in_array($login_fabrica, array(10)))) { ?>
			<th width="63px"><?=traduz('SAC Telecontrol')?></th>
		<?php } ?>
		<th width="42px"><?=traduz('Info Técnica <br> *')?></th>
		<th width="59px"><?=traduz('Financeiro <br> *')?></th>
		<th width="53px"><?=traduz('Auditoria <br> *')?></th>
		<!-- <th><?=traduz('Recebe Integrações <br /> *')?> </th> -->
		<?php
		// Campos especficos dos fabricantes
		echo ($login_fabrica == 91) ? '<th title="'.traduz('Acesso para promotores, limitado ao Call-Center').'">'.traduz('Promotor').'</th>\n' : ''; // HD 685194
		echo $th_altera_pais_prod;
		echo $th_hd_posto;
		echo ($login_fabrica == 19) ? traduz("<th>Usuário <br> Consulta OS</th>\n") : '';
		echo $th_fale_conosco;
		echo ($telecontrol_distrib) ? traduz("<th>Visualiza Estoque Distrib</th>\n") : '';
		echo $coluna_troca_reembolso;
		echo $th_manda_email_assinatura;
		if ($login_fabrica == 52 || $login_fabrica == 24) echo traduz('<th>Inspetor</th>');
		if ($login_fabrica == 30) {
?>

        <th><?=traduz('Auditor Laudo')?></th>
        <th><?=traduz('Observações SAC')?></th>
<?php
		}
		if ($login_fabrica == 1) {
?>

        <th><?=traduz('Aprova Extrato')?></th>
        <th><?=traduz('Aprova Protocolo')?></th>
        <th><?=traduz('Pagamento Garantia')?></th>
        <th><?=traduz('Solicitação de Cheque')?></th>
        <th><?=traduz('Supervisão de Cheque')?></th>
        <th><?=traduz('Analista/Gerente Contas')?></th>
        <th><?=traduz('Visualizar Assinatura')?></th>

<?php
		}
		if ($login_fabrica == 42) {
?>

        <th><?=traduz('Aviso E-mail')?></th>

<?php
		}

		if (in_array($login_fabrica, [169,170])) {
?>

        	<th><?=traduz('Responsável RI')?></th>
        	<th><?=traduz('Suporte Técnico')?></th>
        	<th><?= traduz("Atende Protocolo Call-Center") ?></th>

<?php
		}
?>
	</tr>
<?php

	$tbl_headers = ob_get_clean();

	ob_start();?>

	<tr class='titulo_coluna_inativa' style='cursor:default'>
		<th>&nbsp;</th>
		<th style="width:150px !important">Login</th>
		<th><?=traduz('Senha')?></th>
		<th><?=traduz('Nome')?></th>
		<th><?=traduz('Fone')?></th>
		<th><?=traduz('Celular')?></th>
		<th>WhatsApp</th>
		<th>Email</th>
		<th><?=traduz('Idioma')?></th>
		<?=$th_pais . $th_cliente_admin?>
		<th width="32px"><?=traduz('Ativo')?></th>
		<th width="40px"><?=traduz('Master')?></th>
		<?=$th_sup_hd?>
		<?=$th_libera_pedido?>
		<th width="30px"><?=traduz('Chat')?></th>
		<th width="46px"><?=traduz('Gerente <br /> TI')?></th>
		<th width="50px"><?=traduz('gerencia')?><br />*</th>
		<th width="57px"><?=traduz('Cadastros')?><br />*</th>
		<th width="63px"><?=traduz('Call-Center')?><br />*</th>
		<?php if (in_array($login_fabrica, array(169,170))) { ?>
			<th width="63px"><?=traduz('Recebe<br />Jornada')?></th>
		<?php } ?>
		<th width="63px"><?=traduz('Supervisor <br> Call-Center')?></th>
		<?php if (!in_array($login_fabrica, array(169,170,171,173,184,191,193,198,200,203)) || $usa_somente_callcenter) { ?>
			<th width="63px"><?=traduz('Somente <br> Visualizar <br> Call-Center')?></th>
		<?php } 		
		if ($integracaoTelefonia === true || (in_array($login_fabrica, array(10)))) { ?>
			<th width="63px"><?=traduz('SAC Telecontrol')?></th>
		<?php } ?>
		<th width="42px"><?=traduz('Info Técnica <br> *')?></th>
		<th width="59px"><?=traduz('Financeiro <br> *')?></th>
		<th width="53px"><?=traduz('Auditoria <br> *')?></th><?php
		// Campos especficos dos fabricantes
		echo ($login_fabrica == 91)? traduz('<th title="Acesso para promotores, limitado ao Call-Center">Promotor</th>')."\n" : ''; // HD 685194
		echo $th_altera_pais_prod;
		echo $th_hd_posto;
		echo ($login_fabrica == 19) ? traduz("<th>Usuário <br> Consulta OS</th>\n") : '';
		echo $th_fale_conosco;
		echo ($telecontrol_distrib) ? traduz("<th>Visualiza Estoque Distrib</th>\n") : '';
		echo $th_manda_email_assinatura;
		if ($login_fabrica == 52 || $login_fabrica == 24) echo traduz('<th>Inspetor</th>');
		if($login_fabrica == 30){
?>

        <th><?=traduz('Auditor Laudo')?></th>
        <th><?=traduz('Observações SAC')?></th>
<?php
		}
		if ($login_fabrica == 1) {
?>

        <th><?=traduz('Aprova Extrato')?></th>
        <th><?=traduz('Aprova Protocolo')?></th>
        <th><?=traduz('Pagamento Garantia')?></th>
        <th><?=traduz('Solicitação de Cheque')?></th>
        <th><?=traduz('Visualizar Assinatura')?></th>

<?php
		}
		if($login_fabrica == 42){
?>

        <th><?=traduz('Aviso E-mail')?></th>
<?php
		}
?>
	</tr><?php

	$tbl_headers_inativos = ob_get_clean();

	ob_start();?>

	<tr class='titulo_coluna' style='cursor:default'>
		<th><?=traduz('Login')?></th>
		<th><?=traduz('Senha')?></th>
		<th><?=traduz('Nome')?></th>
		<th><?=traduz('Fone')?></th>
		<th><?=traduz('Celular')?></th>
		<th>WhatsApp</th>
		<?php if ($usa_admin_ramal) { ?>
		<th><?=traduz('Ramal')?></th>
		<?php } ?>
		<th>Email</th>
		<th><?=traduz('Idioma')?></th>
		<?=$th_pais . $th_cliente_admin?>
		<th>&nbsp;<?=traduz('Ativo')?>&nbsp;</th>
		<th><?=traduz('Master')?></th>
		<?=$th_sup_hd?>
		<?=$th_libera_pedido;?>
		<th>&nbsp;<?=traduz('Chat')?>&nbsp;</th>
		<th><?=traduz('Gerente <br /> TI')?></th>
		<th><?=traduz('gerencia')?><br />*</th>
		<th><?=traduz('Cadastros')?><br />*</th>
		<th><?=traduz('Call-Center')?><br />*</th>
		<?php if (in_array($login_fabrica, array(169,170))) { ?>
			<th><?=traduz('Recebe <br> Jornada')?></th>
		<?php } ?>		
		<th><?=traduz('Supervisor <br> Call-Center')?></th>
		<?php if(!in_array($login_fabrica, array(171,173,174,175,176,184,191,193,198,200,203)) || $usa_somente_callcenter){ ?>
			<th><?=traduz('Somente <br> Visualizar <br> Call-Center')?></th>
		<?php }		

		if ($integracaoTelefonia === true || (in_array($login_fabrica, array(10)))) { ?>
			<th width="63px">SAC Telecontrol</th>
		<?php } ?>
		<th><?=traduz('Info Técnica <br> *')?></th>
		<th><?=traduz('Financeiro <br> *')?></th>
		<th><?=traduz('Auditoria <br> *</th>')?><?php
		// Campos especficos dos fabricantes
		echo ($login_fabrica == 91) ? '<th title="'.traduz('Acesso para promotores, limitado ao Call-Center').'">'.traduz('Promotor').'</th>\n' : ''; // HD 685194
		echo $th_altera_pais_prod;
		echo $th_hd_posto;
		echo ($login_fabrica == 19) ? traduz("<th>Usuário <br> Consulta OS</th>\n") : '';
		echo $th_fale_conosco;
		echo ($telecontrol_distrib) ? traduz("<th>Visualiza Estoque Distrib</th>\n") : '';
		if($login_fabrica == 151){
			echo traduz("<th>Troca <br> Reembolso</th>");
		}
		echo $th_manda_email_assinatura;
		if ($login_fabrica == 52 || $login_fabrica == 24) echo traduz('<th>Inspetor</th>');
		if($login_fabrica == 30){

        ?>
        <th><?=traduz('Auditor Laudo')?></th>
        <th><?=traduz('Observações SAC')?></th>
        <?
        }
        if ($login_fabrica == 1) {
?>
        <th><?=traduz('Aprova Extrato')?></th>
        <th><?=traduz('Aprova Protocolo')?></th>
        <th><?=traduz('Pagamento Garantia')?></th>
        <th><?=traduz('Solicitação de Cheque')?></th>
        <th><?=traduz('Supervisão de Cheque')?></th>

<?php
		}
		if ($login_fabrica == 42) {
?>

        <th><?=traduz('Aviso E-mail')?></th>

<?php
		}

		if (in_array($login_fabrica, [169,170])) { ?>

			<th><?= traduz("Responsável RI") ?></th>
			<th><?= traduz("Suporte Técnico") ?></th>
			<th><?= traduz("Atende Protocolo Call-Center") ?></th>

		<?php
		}

?>
	</tr>
<?php

	$tbl_headers_novos = ob_get_clean();

	$colspan1 = (in_array($login_fabrica, array(1,19,20,52,74,81,114))) ? '14' : '15';
	$colspan2 = (in_array($login_fabrica, array(1,19,20,52,74,81,86,114))) ? '6' : '4';

	$colspan2 = ($login_fabrica == 52) ? 7 : $colspan2;

	$width = (in_array($login_fabrica, array(1,19,20,52,74,81,114,191))) ? '1950px' : '1700px';

	if ( $login_fabrica == 35){
		$colspan1 = '15';
		$colspan2 = '6';
	}

	if(in_array($login_fabrica,array(1,24,30))){
		$colspan1 = ($login_fabrica == 30) ? '17' : '15';
		$colspan2 = '7';
	}

if (strpos($privilegios,'*') === false) {?>
<form method="post" name="frm_admin">
	<input name="btn_acao" type="hidden" value="" />
	<table class='formulario' id='admins' align='center' border='0' cellpadding='0' cellspacing='1' width="1000px" align="center">
		<thead>
			<tr>
				<td colspan="7" class="titulo_tabela">
					Parâmetros de Cadastro
				</td>
			</tr>
			<tr class="titulo_coluna">
				<th>Login</th>
				<th>Senha</th>
				<th>Nome Completo</th>
				<th>Fone</th>
				<th>Celular</th>
				<th>Whatsapp</th>
				<th>E-mail</th>
				<th><?=traduz('Idioma')?></th>
				<th><?=traduz('Ativo')?></th>
			</tr>
		</thead>
		<tbody><?php

    $sql = "
        SELECT admin
             , login
             , senha
             , nome_completo
             , fone
             , whatsapp
             , email
             , privilegios
             , cliente_admin_master
             , callcenter_supervisor
             , fale_conosco
             , intervensor
             , atendente_callcenter
             , ativo
             , admin_sap
             , live_help
             , responsavel_postos
             , responsavel_ti
             , altera_pais_produto
             , help_desk_supervisor
             , aprova_laudo
             , participa_agenda
             , parametros_adicionais
             , tbl_l10n.idioma
          FROM tbl_admin
          LEFT JOIN tbl_l10n ON tbl_l10n.l10n = tbl_admin.l10n
         WHERE fabrica = $login_fabrica
           AND admin   = $login_admin";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		
		$admin                  = trim(pg_fetch_result($res, 0, 'admin'));
		$login                  = trim(pg_fetch_result($res, 0, 'login'));
		$senha                  = trim(pg_fetch_result($res, 0, 'senha'));
		$nome_completo          = trim(pg_fetch_result($res, 0, 'nome_completo'));
		$fone                   = trim(pg_fetch_result($res, 0, 'fone'));
		if (!in_array($login_fabrica, [180,181,182])) {
			$fone                      = str_replace(['(',')','-'], '', $fone);
		}
		$email                  = trim(pg_fetch_result($res, 0, 'email'));
		$privilegios            = trim(pg_fetch_result($res, 0, 'privilegios'));
		$cliente_admin_master   = trim(pg_fetch_result($res, 0, 'cliente_admin_master'));
		$supervisor_call_center = trim(pg_fetch_result($res, 0, 'callcenter_supervisor'));
		// $somente_visualiza_call_center   = trim(pg_fetch_result($res, 0, 'somente_visualiza_call_center'));
		$fale_conosco           = trim(pg_fetch_result($res, 0, 'fale_conosco'));
		$intervensor            = trim(pg_fetch_result($res, 0, 'intervensor'));
		$atendente_callcenter   = trim(pg_fetch_result($res, 0, 'atendente_callcenter')); //HD 335548
		$ativo                  = trim(pg_fetch_result($res, 0, 'ativo'));
		$admin_sap              = trim(pg_fetch_result($res, 0, 'admin_sap'));
		$live_help              = trim(pg_fetch_result($res, 0, 'live_help'));
		$responsavel_postos     = trim(pg_fetch_result($res, 0, 'responsavel_postos'));     //HD 233213
		$responsavel_ti         = trim(pg_fetch_result($res, 0, 'responsavel_ti'));         //SEM HD
		$altera_pais_produto    = trim(pg_fetch_result($res, 0, 'altera_pais_produto'));   // HD 374998
		$sup_help_desk          = trim(pg_fetch_result($res, 0, 'help_desk_supervisor'));
		$aprova_laudo           = trim(pg_fetch_result($res, 0, 'aprova_laudo'));

		$whatsapp               = trim(pg_fetch_result($res, 0, 'whatsapp'));
        $parametros_adicionais  = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'), true);
        $celular                = (isset($parametros_adicionais['celular'])) ? $parametros_adicionais['celular'] : "";

		if (in_array($login_fabrica, array(169,170))) {
			$recebe_jornada = pg_fetch_result($res, 0, 'participa_agenda');
		}

		if (count($parametros_adicionais)) {
			extract($parametros_adicionais, EXTR_OVERWRITE);
		}

		if ($debug == 't') {
			echo "<caption>";
				echo traduz("Privilégios de login: %<br />Privilégios de usuário: %", null, null, [$login_privilegios, $privilegios]);
			echo "</caption>\n";
		}

		if ($msg_erro){

			$login         = trim($_POST['login_']);
			$senha         = trim($_POST['senha_']);
			$nome_completo = trim($_POST['nome_completo_']);
			$fone          = trim($_POST['fone_']);
			$celular       = trim($_POST['celular_']);
			if (!in_array($login_fabrica, [180,181,182])) {
				$celular                   = str_replace(['(',')','-'], '', $celular);
			}
			$whatsapp      = trim($_POST['whatsapp_']);
			if (!in_array($login_fabrica, [180,181,182])) {
				$whatsapp                   = str_replace(['(',')','-'], '', $whatsapp);
			}
			$email         = trim($_POST['email_']);

		}else{
			$senha = sha1($senha);
		}

		echo "<tr class='table_line'>";
		echo "<input type='hidden' name='admin_$i' value='$admin'>";
		echo "<input type='hidden' name='login_$i' value='$login'>";
		echo "<td align='center'>$login </td>";
		echo "<td align='center'><input type='password' name='senha_$i'         size='10' maxlength='10' value='$senha' style='width:95%' > </td>";
		echo "<td align='center'><input type='text'     name='nome_completo_$i' size='40' maxlength='' value='$nome_completo'></td>\n";
		$class_tel = (!in_array($login_fabrica, [180,181,182])) ? "telefone" : "tel";
		echo "<td align='center'><input type='text'     name='fone_$i'          size='15' maxlength='17' value='$fone' class='fone $class_tel'></td>\n";
		$class_cel = (!in_array($login_fabrica, [180,181,182])) ? "cel" : "tel"; 
		echo "<td align='center'><input type='text'     name='celular_$i'       size='15' maxlength='18' value='$celular' class='fone $class_cel'></td>\n";
		echo "<td align='center'><input type='text'     name='whatsapp_$i'       size='15' maxlength='18' value='$whatsapp' class='fone $class_cel'></td>\n";
		echo "<td align='center'><input type='text'     name='email_$i'         size='40' maxlength='' value='$email'></td>\n";

		echo '<td align=\'center\'>' . createHTMLInput('checkbox', "ativo_$i",  true, 't',      $ativo,  ($ativo == 't'),       false) . '&nbsp;</td>';
		echo "</tr>";

		?>
		<tr><td>&nbsp;</td></tr>
		<tr class='titulo_coluna'>
			<td colspan="7"><?=traduz('Permissões')?></td>
		</tr>
		<tr>
			<td colspan="7">
				<table width="100%" align="center" cellpadding="1" cellspacing="1">
					<tr class='titulo_coluna'>
						<?= $th_pais . $th_cliente_admin ?>

						<th><?=traduz('Master')?></th>
						<th><?=traduz('Sup. Helpdesk')?></th>
						<th><?=traduz('Chat')?></th>
						<th><?=traduz('Gerente <br /> TI')?></th>
						<th><?=traduz('gerencia')?><br />*</th>
						<th><?=traduz('Cadastros')?><br />*</th>
						<th><?=traduz('Call-Center')?><br />*</th>
                        <th><?=traduz('Supervisor Call-Center')?></th>
                        <?php if ($usa_somente_callcenter): ?>
						<th><?=traduz('Somente Visualizar Call-Center')?></th>
                        <?php endif;                       
                        if ($integracaoTelefonia === true || (in_array($login_fabrica, array(10)))) { ?>
							<th width="63px"><?=traduz('SAC Telecontrol')?></th>
						<?php } ?>
						<th><?=traduz('Info Técnica *')?></th>
						<th><?=traduz('Financeiro *')?></th>
						<th><?=traduz('Auditoria *</th>')?><?php
						// Campos especficos dos fabricantes
						echo ($login_fabrica == 91) ? '<th title="'.traduz('Acesso para promotores, limitado ao Call-Center').'">'.traduz('Promotor').'</th>\n' : ''; // HD 685194
						echo $th_altera_pais_prod;
						echo $th_hd_posto;
						echo ($login_fabrica == 19) ? traduz("<th>Usuário Consulta OS</th>\n") : '';
						echo $th_fale_conosco;

						?>
					</tr>
					<tr>
						<?php if ($th_pais or $th_cliente_admin){ ?>
						<td align="center">
							<?php
								if ($abre_os_admin_arr) {


								echo "<select name='cliente_admin' disabled='disabled'>";
								echo "<option></option>";

								if(!in_array($login_fabrica, [85,156,167,191,203])) {
								$sql_cliente_admin = "
									SELECT cliente_admin, nome, cidade
									FROM tbl_cliente_admin
									WHERE abre_os_admin       IS TRUE
									AND fabrica               =  $login_fabrica
									ORDER BY nome";
								}else{
									$sql_cliente_admin = "
																	 SELECT cliente_admin, nome, cidade
																		 FROM tbl_cliente_admin
																		 WHERE  fabrica =  $login_fabrica
																		 ORDER BY nome";
								}

								$res_cliente_admin = pg_query($con,$sql_cliente_admin);
								$total             = pg_num_rows($res_cliente_admin);

								if ($total > 0) {

									for ($w = 0; $w < $total; $w++) {

										$cliente_admin = pg_fetch_result($res_cliente_admin, $w, 'cliente_admin');
										$nome          = pg_fetch_result($res_cliente_admin, $w, 'nome');
										$cidade        = pg_fetch_result($res_cliente_admin, $w, 'cidade');
										$nome          = ucwords(strtolower(substr($nome   , 0 , 20)));
										$cidade        = ucwords(strtolower(substr($cidade , 0 , 15)));

										echo "<option value='$cliente_admin'>$nome - $cidade</option>";

									}

								}

								echo "</select>";

								if ($login_fabrica != 96) {
									echo createHTMLInput('checkbox', "cliente_admin_master_$i", null, 't', $cliente_admin_master, null, false) ;
								}

							}
							?>
						</td>
						<?php } ?>
					
						<td align="center">
							<?php
							echo createHTMLInput('checkbox', "master_$i", true, 'master', $master, ($master == 'master'), false);
							?>
						</td>
						<td>
						<?php
							echo createHTMLInput('checkbox', "sup_help_desk_$i",  true, 't', $sup_help_desk,  null, false);

						echo "</td>";

						if ($login_fabrica == 168) {
							echo '<td align="center">' . createHTMLInput('checkbox', "libera_pedido_$i",  null, 't', $libera_pedido,  null, false) . '&nbsp;</td>';
						}
						echo '<td align="center">' . createHTMLInput('checkbox', "live_help_$i",      true, 't',            $live_help,      null,                 false) . '&nbsp;</td>';
						echo '<td align="center">' . createHTMLInput('checkbox', "responsavel_ti_$i", null, 't',            $responsavel_ti, null,                 false) . '&nbsp;</td>';
						echo '<td align="center">' . createHTMLInput('checkbox', "gerencia_$i"    ,   null, 'gerencia'    , null,            (strpos($privilegios, 'gerencia')     !== false), false).'&nbsp;</td>';
						echo '<td align="center">' . createHTMLInput('checkbox', "cadastros_$i"   ,   null, 'cadastros'   , null,            (strpos($privilegios, 'cadastros')    !== false), false).'&nbsp;</td>';
						echo '<td align="center">' . createHTMLInput('checkbox', "call_center_$i" ,   null, 'call_center' , null,            (strpos($privilegios, 'call_center')  !== false), false).'&nbsp;</td>';
						if (in_array($login_fabrica, array(169,170))) {
							echo '<td align="center">' . createHTMLInput('checkbox', "recebe_jornada_$i", null, 't' , $recebe_jornada   , null                       , false).'&nbsp;</td>';
						}
						echo '<td align="center">' . createHTMLInput('checkbox', "supervisor_call_center_$i", null, 't' , $supervisor_call_center   , null                       , false).'&nbsp;</td>';

						if ($usa_somente_callcenter) {
							echo '<td align="center">' . createHTMLInput('checkbox', "somente_visualiza_call_center_$i", null, 't', $somente_visualiza_call_center, null, false).'&nbsp;</td>';
						}

						echo '<td align="center">' . createHTMLInput('checkbox', "info_tecnica_$i", null, 'info_tecnica', null, (strpos($privilegios, 'info_tecnica') !== false), false).'&nbsp;</td>';
						echo '<td align="center">' . createHTMLInput('checkbox', "financeiro_$i"  , null, 'financeiro'  , null, (strpos($privilegios, 'financeiro')   !== false), false).'&nbsp;</td>';
						echo '<td align="center">' . createHTMLInput('checkbox', "auditoria_$i"   , null, 'auditoria'   , null, (strpos($privilegios, 'auditoria')    !== false), false).'&nbsp;</td>';

						if ($login_fabrica == 91) {
							echo '<td>' . createHTMLInput('checkbox', "promotor_wanke_$i", null, 'promotor', null, (strpos($privilegios, 'promotor')  !== false), false) . '&nbsp;</td>';
						}

						if ($usa_altera_pais_produto) {
							echo '<td>' . createHTMLInput('checkbox', "altera_pais_produto_$i", null, 't', $altera_pais_produto, null, false) . '&nbsp;</td>';
						}

						if ($usa_atende_hd_postos) {
							echo '<td>' . createHTMLInput('checkbox', "sap_$i", "sap_$i", 't', $admin_sap, null, false) . '&nbsp;</td>';
						}

						if ($usa_responsavel_postos){
							echo '<td>' . createHTMLInput('checkbox', "responsavel_postos_$i", null, 't', $responsavel_postos, null, false) . '&nbsp;</td>';
						}

						if ($usa_atendente_callcenter){
							echo '<td>' . createHTMLInput('checkbox', "atendente_callcenter_$i", null, 't', $atendente_callcenter, null, false) . '&nbsp;</td>';
						}

						if ($usa_recebe_fale_conosco){
							echo '<td>' . createHTMLInput('checkbox', "fale_conosco_$i", null, 't', $fale_conosco, null, false) . '&nbsp;</td>';
						}

						if ($usa_intervensor) {
							echo '<td>' . createHTMLInput('checkbox', "intervensor_$i", null, 't', $intervensor, null, false) . '&nbsp;</td>';
						}

						if ($login_fabrica == 19){
							echo '<td>' . createHTMLInput('checkbox', "consulta_os_$i", null, 't', $consulta_os, null, false) .  '&nbsp;</td>';
						}

						if ($login_fabrica == 52 || $login_fabrica == 24) {
							echo '<td>' . createHTMLInput('checkbox', "inspetor_$i", null, 'inspetor', $inspetor, null, false) .  '&nbsp;</td>';
						}
						if ($login_fabrica == 30) {
							echo '<td>' . createHTMLInput('checkbox', "aprova_laudo_$i", null, 't', $aprova_laudo, null, false) .  '&nbsp;</td>';
							echo '<td>' . createHTMLInput('checkbox', "observacao_sac_$i", null, 't', $observacao_sac, null, false) .  '&nbsp;</td>';
						}
						if ($login_fabrica == 1) {
							echo '<td>' . createHTMLInput('checkbox', "aprova_extrato_$i", null, 't', $aprova_extrato, null, false) .  '&nbsp;</td>';
							echo '<td>' . createHTMLInput('checkbox', "aprova_protocolo_$i", null, 't', $aprova_protocolo, null, false) .  '&nbsp;</td>';
							echo '<td>' . createHTMLInput('checkbox', "pagamento_garantia_$i", null, 't', $pagamento_garantia, null, false) .  '&nbsp;</td>';
							echo '<td align="center">
									<select multiple class="multi_contas" name="contas_'.$i.'[]">
										<option value="analista_posvenda" '.((in_array("analista_posvenda", $contas)) ? "selected" : "").'>Analista de Pós-Vendas</option>
										<option value="gerente_posvenda" '.((in_array("gerente_posvenda", $contas)) ? "selected" : "").'>Gerente de Pós-Vendas</option>
										<option value="analista_contas_pagar" '.((in_array("analista_contas_pagar", $contas)) ? "selected" : "").'>Analista de Contas a Pagar</option>
										<option value="gerente_contas_pagar" '.((in_array("gerente_contas_pagar", $contas)) ? "selected" : "").'>Gerente de Contas a Pagar</option>
									</select>
								  </td>';
						}
						if ($login_fabrica == 42) {
							echo '<td>' . createHTMLInput('checkbox', "aviso_email_$i", null, 't', $aviso_email, null, false) .  '&nbsp;</td>';
						}

						if (in_array($login_fabrica, [169,170])) {
							
							echo '<td>' . createHTMLInput('checkbox', "analise_ri_$i", null, 't', $analise_ri, null, false) .  '&nbsp;</td>';
							echo '<td>' . createHTMLInput('checkbox', "suporte_tecnico_$i", null, 't', $suporte_tecnico, null, false) .  '&nbsp;</td>';
						}
						?>

						<td><?=traduz('Receber Integrações')?></td>

					</tr>


				</table>
			</td>
		</tr>

		<?
	}?>
	</table>
	</form>
	<table align='center'>
		<tr>
			<td colspan="9" align='center'>
				<input type='hidden' name='btn_acao' value='' />
				<center>
					<input type="button" value="<?=traduz("gravar")?>" onclick=" formSubmit();" />
				</center>
			</td>
		</tr>
	</table><?php

} else {

	$sql_nome_admin = "SELECT nome_completo FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica};";
	$res_nome_admin = pg_query($con, $sql_nome_admin);
	$nome_admin     = @pg_fetch_result($res_nome_admin, 0, 'nome_completo');
	$enabled = ($ativo == 't'); // Determina se os checkbox vão estar ativos ou não, dependendo do check 'ativo'
?>
	<input type="hidden" name="nome_admin" id="nome_admin" value="<?=$nome_admin?>">

	<div class="texto_avulso" >
		<p>
			<?=traduz('Para incluir um novo administrador insira o login e senha desejados e selecione os privilégios que deseja conceder a este usuário.
			Para alterar qualquer informação basta clicar sobre o campo desejado e efetuar a troca.')?>
		</p>
		<p>
			<b><?=traduz('OBS.: Clique em gravar logo após inserir ou alterar a configuração de um administrador.')?></B>
		</p>
		<p>
			<?=traduz('O campo <b>LOGIN</b> não pode ser preenchido com os caracteres: "."(ponto), "/"(barra), "-"(hífen)," "(espaço em branco).')?>
		</p>

		<p>
			<?=traduz('A <b>SENHA</b> deve ter entre 06 e 10 caracteres, sendo ao menos 02 letras (de A à Z) e 02 números (de 0 a 9),
			por exemplo: bra500, tele2007, ou assist0682.')?>
		</p>
	</div>
	<br />

	<?php 

	/**
	 * @author William Castro <william.castro@telecontrol.com.br>
	 * hd-6641542 
	 * mostrar qtde total de licenças contratadas
	 * - mostrar total de qtde de admin ativos(tbl_admin.ativo) 
	 */

	$retorno_validacao = validaAdmin();
	if ($retorno_validacao) {
	 
		$retorno = calculaAdmin(); ?>

		<div class="licencas" align="center">
			<div class="licenca_contratada">
					<?= traduz("licencas.contratadas") ?>
				<br> 
				<div class="texto_licenca txt_disponivel">
					<?php echo $retorno['qtd_contratadas']; ?>
				</div>
			</div>
			<div class="licenca_disponivel">
					<?= traduz("licencas.disponiveis") ?>
				<br> 
				<div class="texto_licenca  txt_contratado">
					<?php echo $retorno['qtd_admins_disponiveis']; ?>
				</div>
			</div>
			<?php if(in_array($login_fabrica, [169,170])) { ?>
			<div class="txt_callcenter">
				Callcenter
				<br> 
				<div class="texto_licenca ">
					<?php echo $retorno['qtde_callcenter']; ?>
				</div>
			</div>
			<div class="txt_outros">
				Outros
				<br> 
				<div class="texto_licenca ">
					<?php echo $retorno['qtde_outros']; ?>
				</div>
			</div>
			<? } ?>
		</div>

		<?php } ?>

<?php
if ($fabrica_assina_extrato and ($admin_aprova_extrato || $admin_aprova_protocolo)) {
	if ($admin_aprova_extrato == 'f' && $admin_aprova_protocolo == 't') {
		$tipo = "Protocolo";
	} else if ($admin_aprova_extrato == 't' && $admin_aprova_protocolo == 'f') {
		$tipo = "Extrato";
	} else if ($admin_aprova_extrato == 't' && $admin_aprova_protocolo == 't') {
		$tipo = "Extrato / Protocolo";
	}

	if ($login_fabrica <> 1) {
	?>
		<form action="" name="arquivo_assinatura" method="POST" enctype="multipart/form-data">
			<div class="formulario" style="width: 780px;vertical-align:middle;">
				<div class="titulo_tabela"><?=traduz('Assinatura para Aprovação de %', null, null, [$tipo]);?></div>
				<div style="display:flex;flex-flow:row nowrap;" id="assinatura_digital">
					<span style="flex:1"></span>

					<span style="flex: 3;"><label for="arquivo_firma" style="cursor:pointer;"><?=traduz('Assinatura para o %:', null, null, [$tipo])?></label></span>
		            <span style="flex: 3;"><img id="imagem_firma" src="<?=$img_assinatura?>" height="96" alt="Imagem da Assinatura"></span>
					<span style="flex: 3;"><button type="submit"  name="anexo" value="assinatura"><?=traduz('Subir')?></button></span>
					<input id="arquivo_firma" type="file" accept="image/png,image/jpeg" name="assinatura" style="display:none">
				</div>
			</div>
		</form>
		<p>&nbsp;</p>
	<?php
	} else {
	?>  

		<form action="" name="arquivo_assinatura" method="POST" enctype="multipart/form-data">
			<div class="formulario" style="width: 780px;vertical-align:middle;">
				<div class="titulo_tabela"><?=traduz('Assinatura para Aprovação de Cheque')?></div>
				<div id="assinatura_digital" style="text-align: center;">
					<div class="row-fluid">

				        <div class="span12">
				            <label class="control-label" for='admin_solicitacao' style="font-size: 14px;"><b><?=traduz('Usuário:')?></b></label>   
				        	<select name="admin_solicitacao" id="admin_solicitacao">
		                        <option value=""></option>
		                        <?php
		                        $sql = "SELECT
		                                    admin,
		                                    login
		                                FROM tbl_admin WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY 2";

		                        $res = pg_query($con, $sql);
		                        for ($i = 0; $i < pg_num_rows($res); $i++) { 
		                            $admin = pg_fetch_result($res, $i, 'admin');
		                            $login = pg_fetch_result($res, $i, 'login');
		                            //$selected = ($admin == $admin_solicitacao) ? 'selected' : '';

		                            echo "<option value='$admin' {$selected}>$login</option>";
		                        }
		                        ?>
		                    </select>
		                    &nbsp;&nbsp;&nbsp;&nbsp; 
		                    <input id="arquivo_firma" type="file" accept="image/png,image/jpeg" name="assinatura" >	
		                    &nbsp;&nbsp;&nbsp;&nbsp;
		                    <button type="submit"  name="anexo" value="assinatura"><?=traduz('gravar')?></button>
				        </div>
				    </div>
					<!-- <span style="flex:1"></span>
					<span style="flex: 3;"><label for="arquivo_firma" style="cursor:pointer;">Usuário:</label></span>
					<span style="flex: 3;"><label for="arquivo_firma" style="cursor:pointer;">Usuário:</label></span>
										<span style="flex: 3;"><img id="imagem_firma" src="<?=$img_assinatura?>" height="96" alt="Imagem da Assinatura"></span>
					<span style="flex: 3;"><button type="submit"  name="anexo" value="assinatura">Subir</button></span>
					<input id="arquivo_firma" type="file" accept="image/png,image/jpeg" name="assinatura" style="display:none"> -->
				</div>
			</div>
		</form>
		<p>&nbsp;</p>

	<?php 
	}
}
?>

	<form name="frm_admin" method="post" action="<? echo $PHP_SELF ?>">
	<? if(empty($list_inativo)) {?>
	<input type='button' value='<?= traduz("Listar os inativos") ?>' onclick='window.location.href="<?=$PHP_SELF?>?list_inativo=true"'>
	<? }else{ ?>
	<input type='button' value='<?= traduz("Listar os ativos") ?>' onclick='window.location.href="<?=$PHP_SELF?>?list_ativo=true"'>
	<? } ?>
		<br><br>
	<!-- <div class='tableContainer'> -->
		<table width='700' align='center' border='0' cellpadding="0" cellspacing="0" class="formulario">
			<tbody class='scrollContent'>
				<? if((!$qtde_admin OR $total_admin < $qtde_admin) and empty($list_inativo)){ ?>
				<tr>
					<td>
						<table align="center" class="tabela" id="cad_novos" style="width:<?=$width?>" cellspacing="0" cellpadding="0" border="0" >

							<?php if(empty($_POST['qtde_novos'])){
								$qtde_novos = 1;
							}else{
								$qtde_novos = $_POST['qtde_novos'];
							} ?>

							<input type="hidden" name="qtde_novos" id="qtde_novos" value="<?=$qtde_novos?>" />

							<thead class=''>
								<caption class="titulo_tabela">
									<span style="color:white;font-size: 1.2em;margin-left: 20%;line-height: 1.4em;"><?=traduz('Cadastro Usuários Admin')?></span>
									<span style="float:right;margin-right: 20%;vertical-align: middle;font-size: 0.8em;padding-top: 0.5em;">(*) <?=traduz('Menus do Sistema')?></span>
								</caption>
								<? echo $tbl_headers_novos; ?>
							</thead>
							<?
							for ($i=0; $i < $qtde_novos; $i++){

								if ($msg_erro){

								$login_novo         = trim($_POST['login_novo_'.$i]);
								$senha_novo         = trim($_POST['senha_novo_'.$i]);
								$nome_completo_novo = trim($_POST['nome_completo_novo_'.$i]);
								$fone_novo          = trim($_POST['fone_novo_'.$i]);
								$celular_novo       = trim($_POST['celular_novo_'.$i]);
								$whatsapp_novo      = trim($_POST['whatsapp_novo_'.$i]);
								if (!in_array($login_fabrica, [180,181,182])) {
									$fone_novo                 = str_replace(['(',')','-'], '', $fone_novo);
									$celular_novo              = str_replace(['(',')','-'], '', $celular_novo);
								}
								$email_novo         = trim($_POST['email_novo_'.$i]);
								$pais_novo          = ($fabrica_multinacional) ? trim($_POST['pais_novo_'.$i]) : '';

								}

							?>
								<tr>
									<td align="center">
										<input type="text" name='login_novo_<?=$i?>' class="frm" size='20' maxlength='20' value='<?=$login_novo;?>' onkeyup='retiraAcentos(this)'>
									</td>
									<td nowrap  align="center">
										<input type='text' name='senha_novo_<?=$i?>' class="frm" size='20' maxlength='10' value='<?=$senha_novo ?>'>
									</td>
									<td nowrap  align="center">
										<input type='text' name='nome_completo_novo_<?=$i?>' class="frm" size='20' maxlength='' value='<?=$nome_completo_novo ?>'>
									</td>
									<td nowrap align="center" >
										<?php
											$class_tel = (!in_array($login_fabrica, [180,181,182])) ? "telefone" : "tel";
										?>
										<input type='text' name='fone_novo_<?=$i?>' name='fone_novo_<?=$i?>' class="<?=$class_tel?> frm fone" size='13' maxlength='17' value='<?=$fone_novo ?>'>
									</td>
									<td nowrap align="center" >
										<?php 
											$class_cel = (!in_array($login_fabrica, [180,181,182])) ? "cel" : "tel"; 
										?>
										<input type='text' name='celular_novo_<?=$i?>' name='celular_novo_<?=$i?>' class="frm fone <?=$class_cel?>" size='13' maxlength='18' value='<?=$celular_novo ?>'>
									</td>
									<td nowrap align="center">
										<input type="text" name='whatsapp_novo_<?=$i?>' name='whatsapp_novo_<?=$i?>' class="frm fone cel" size='13' maxlength='18' value='<?=$whatsapp_novo?>'>
									</td>
									<?php if ($usa_admin_ramal) { ?>
									<td nowrap align="center" >
										<input type='text' name='ramal_<?=$i?>' name='ramal_<?=$i?>' class="frm" size='5' maxlength='5' value='<?=$ramal ?>'>
									</td>
									<?php } ?>
									<td nowrap  align="center">
										<input type='text' name='email_novo_<?=$i?>' class="frm" size='30' maxlength='' value='<?=$email_novo ?>'>
									</td>

									<td nowrap>
										<?php
											echo "<select name='l10n_novo_".$i."' >";
												echo "<option></option>";

												$sql_idioma = "SELECT l10n, nome, idioma
                                                        	   FROM tbl_l10n
															   ORDER BY idioma";

												$res_idioma = pg_query($con, $sql_idioma);

												if (pg_num_rows($res_idioma) > 0) {

													for ($w = 0; $w < pg_num_rows($res_idioma); $w++) {

														$l10n	= pg_fetch_result($res_idioma, $w, 'l10n');
														$idioma = pg_fetch_result($res_idioma, $w, 'idioma');
																																								
														$selected = ( ($_POST['l10n_novo_'.$i] == $l10n) and $msg_erro ) ? 'selected' : '' ;

														echo "<option value='$l10n' $selected>$idioma</option>";

													}

												}
											echo "</select>";
										?>
									</td>

									<?php

									if ($fabrica_multinacional) {

										echo "<td nowrap>"; ?>
											<input type="text" class="novo" maxlength="2" size="2" disabled="" value="" name="pais_novo_<?=$i?>">
										<?php
										echo "</td>\n";

									}

									if ($abre_os_admin_arr) {
										if (in_array($login_fabrica, [158])) {

											$sqlClienteAdmin = "
												SELECT cliente_admin,
													   SUBSTRING(nome, 1, 20) AS nome, 
													   SUBSTRING(cidade, 1, 15) AS cidade
												FROM tbl_cliente_admin
												WHERE fabrica = {$login_fabrica}
												AND abre_os_admin IS TRUE
												ORDER BY nome
											";
											$resClienteAdmin = pg_query($con,$sqlClienteAdmin);
											?>
											
											<td nowrap>
												<select multiple class="cliente_admin_multiplo" name="cliente_admin_multiplo_novo_<?= $i ?>[]" id='cliente_admin_multiplo_novo_<?= $i ?>'>
													<option value=""></option>
													<?php
													while ($dados = pg_fetch_object($resClienteAdmin)) {

														$selected = (in_array($dados->cliente_admin, $cliente_admin_multiplo)) ? "selected" : "";

													?>
														<option value="<?= $dados->cliente_admin ?>" <?= $selected ?>><?= $dados->cidade ?> - <?= $dados->nome ?></option>
													<?php
													} ?>
												</select>
											</td>   

										<?php
										} else {

											echo "<td nowrap >";

												echo "<select name='cliente_admin_novo_".$i."' >";

														echo "<option></option>";


												if (in_array($login_fabrica,array(85,156, 167, 191, 203))) {
													$sql_cliente_admin = "SELECT cliente_admin,
																	nome, cidade
															FROM tbl_cliente_admin
															WHERE fabrica = $login_fabrica
															AND abre_os_admin IS TRUE
															ORDER BY nome";

													}

													$res_cliente_admin = pg_query($con, $sql_cliente_admin);

													if (pg_num_rows($res_cliente_admin) > 0) {

														for ($w = 0; $w < pg_num_rows($res_cliente_admin); $w++) {

															$cliente_admin  = pg_fetch_result($res_cliente_admin, $w, 'cliente_admin');
															$nome           = pg_fetch_result($res_cliente_admin, $w, 'nome');
															$cidade         = pg_fetch_result($res_cliente_admin, $w, 'cidade');

															$nome           = ucwords(strtolower(substr($nome,0,20)));
															$cidade         = ucwords(strtolower(substr($cidade,0,15)));

															$selected = ( ($_POST['cliente_admin_novo_'.$i] == $cliente_admin) and $msg_erro ) ? 'selected' : '' ;

															echo "<option value='$cliente_admin' $selected>$nome - $cidade</option>";

														}

													}
												echo "</select>";

											echo "</td>";

										}

										if ($login_fabrica != 96) {

											echo "<td align=\"center\"><input type='checkbox' name='cliente_admin_master_novo' value='t'";
											if ($cliente_admin_master == 't') echo " checked";
											echo "&nbsp;</TD>\n";

										}

									}

									echo        '<td align="center">' . createHTMLInput('checkbox', "ativo_novo_$i"                 , true, 't'             , $_POST['ativo_novo_'.$i]                  , $enabled, true,"class='ativo' rel='$i'") . '&nbsp;</td>';
									echo        '<td align="center">' . createHTMLInput('checkbox', "master_novo_$i"                , true, 'master'        , $_POST['master_novo_'.$i]                 , ($master == 'master'), $enabled, "class='novo_$i' onclick=\"clicaMasterNovo($(this).attr('rel'))\" ") . '&nbsp;</td>';

									if ($login_privilegios == '*')
										echo    '<td align="center">' . createHTMLInput('checkbox', "sup_help_desk_$i"              , true, 't'             , $_POST['sup_help_desk_'.$i]               , null, $enabled, "class='novo_$i'") . '&nbsp;</td>';

									if ($login_fabrica == 168) {
										echo '<td align="center">' . createHTMLInput('checkbox', "libera_pedido_$i"                 , null, 't'             , $_POST['libera_pedido_'.$i]                   , null, $enabled, "class='novo_$i libera_pedido'") . '</td>';
									}
									echo        '<td align="center">' . createHTMLInput('checkbox', "live_help_$i"                  , null, 't'             , $_POST['live_help_'.$i]                   , null, $enabled, "class='novo_$i live_help'") . '</td>';

									echo        '<td align="center">' . createHTMLInput('checkbox', "responsavel_ti_novo_$i"        , null, 't'             , $_POST['responsavel_ti_novo_'.$i]         , null, $enabled, "class='novo_$i'") . '&nbsp;</td>';

									// Privilégios
									echo        '<td align="center">' . createHTMLInput('checkbox', "gerencia_novo_$i"              , null, 'gerencia'      , $_POST['gerencia_novo_'.$i]               , null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									echo        '<td align="center">' . createHTMLInput('checkbox', "cadastros_novo_$i"             , null, 'cadastros'     , $_POST['cadastros_novo_'.$i]              , null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									echo        '<td align="center">' . createHTMLInput('checkbox', "call_center_novo_$i"           , null, 'call_center'   , $_POST['call_center_novo_'.$i]            , null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									if (in_array($login_fabrica, array(169,170))) {
										echo        '<td align="center">' . createHTMLInput('checkbox', "recebe_jornada_novo_$i", null, 't'             , $_POST['recebe_jornada_novo_'.$i] , null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}
									echo        '<td align="center">' . createHTMLInput('checkbox', "supervisor_call_center_novo_$i", null, 't'             , $_POST['supervisor_call_center_novo_'.$i] , null, $enabled, "class='novo_$i'") . '&nbsp;</td>';

									if (!in_array($login_fabrica, array(171,173,174,175,176,184,191,193,198,200,203)) || $usa_somente_callcenter){
										echo 		'<td align="center">' . createHTMLInput('checkbox', "somente_visualiza_call_center_novo_$i", null, 't'           	, $_POST['somente_visualiza_call_center_novo_'.$i]	, null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}										

									if ($integracaoTelefonia === true || (in_array($login_fabrica, array(10)))) {
											echo        '<td align="center">' . createHTMLInput('checkbox', "sac_telecontrol_novo_$i"          , null, 'sac_telecontrol'    , $_POST['sac_telecontrol_novo_'.$i]            , null, $enabled, "class='novo_$i'") . '&nbsp;</td>';                       
									}
									echo        '<td align="center">' . createHTMLInput('checkbox', "info_tecnica_novo_$i"          , null, 'info_tecnica'  , $_POST['info_tecnica_novo_'.$i]           , null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									echo        '<td align="center">' . createHTMLInput('checkbox', "financeiro_novo_$i"            , null, 'financeiro'    , $_POST['financeiro_novo_'.$i]             , null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									echo        '<td align="center">' . createHTMLInput('checkbox', "auditoria_novo_$i"             , null, 'auditoria'     , $_POST['auditoria_novo_'.$i]              , null, $enabled, "class='novo_$i'") . '&nbsp;</td>';

									if ($login_fabrica == 91) {
										echo '<td align="center">' . createHTMLInput('checkbox', "promotor_wanke_novo_$i", null, 'promotor', $_POST['promotor_wanke_novo'], null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}

									if ($usa_altera_pais_produto) {
										echo '<td align="center">' . createHTMLInput('checkbox', "altera_pais_produto_novo_$i", null, 't', $altera_pais_produto, null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}

									if ($usa_atende_hd_postos) {
										echo '<td align="center">' . createHTMLInput('checkbox', "sap_novo_$i", "sap_novo", 't', $admin_sap, null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}

									if ($usa_responsavel_postos) {
										echo '<td align="center">' . createHTMLInput('checkbox', "responsavel_postos_novo_$i", null, 't', $_POST['responsavel_postos_novo'], null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}

									if ($login_fabrica == 115) {
										echo '<td align="center">' . createHTMLInput('checkbox', "altera_categoria_posto_$i", null, 't', $_POST['altera_categoria_posto'], null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}

									if($login_fabrica == 3){
										echo '<td align="center">' . createHTMLInput('checkbox', "aprova_avulso_1_novo_$i", null, 't', $_POST['aprova_avulso_1_novo'], null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
										echo '<td align="center">' . createHTMLInput('checkbox', "aprova_avulso_2_novo_$i", null, 't', $_POST['aprova_avulso_2_novo'], null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}

									if ($usa_atendente_callcenter) {
										echo '<td align="center">' . createHTMLInput('checkbox', "atendente_callcenter_novo_$i", null, 't', $atendente_callcenter_novo, null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}

									if ($usa_recebe_fale_conosco) {
										echo '<td align="center">' . createHTMLInput('checkbox', "fale_conosco_novo_$i", null, 't', $fale_conosco, null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}

									if ($manda_email_assinatura) {
										//echo '<td align="center">' . createHTMLInput('checkbox', "email_assinatura_novo_$i", null, 'envia_email', $email_assinatura, null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
										echo '<td align="center">' . createHTMLInput('checkbox', "email_assinatura_novo_$i", null, 'envia_email', $_POST['email_assinatura_novo_'.$i], null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}

									if ($usa_intervensor) {
										echo '<td align="center">' . createHTMLInput('checkbox', "intervensor_novo_$i", null, 't', $intervensor_novo, null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}

									if ($telecontrol_distrib) {
										echo '<td align="center">' . createHTMLInput('checkbox', "visualiza_estoque_distrib_$i", null, 't', $_POST['visualiza_estoque_distrib'], null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}

									if($login_fabrica == 151){
										echo    '<td align="center">' . createHTMLInput('checkbox', "troca_reembolso_$i"                , true, 't'             , $_POST['troca_reembolso_'.$i]             , null, $enabled, "class='novo_$i'") . '&nbsp;</td>';
									}

									if ($login_fabrica == 19){
										echo '<td align="center">' . createHTMLInput('checkbox', "consulta_os_novo_$i", null, 't', $consulta_os, null, $enabled, "class='novo_$i'") .  '&nbsp;</td>';
									}

									if ($login_fabrica == 52 || $login_fabrica == 24) {
										echo '<td align="center">' . createHTMLInput('checkbox', "inspetor_$i", null, 'inspetor', $inspetor, null, $enabled, "class='novo_$i'") .  '&nbsp;</td>';
									}
									if ($login_fabrica == 30) {
										echo '<td align="center">' . createHTMLInput('checkbox', "aprova_laudo_$i", null, 't', $aprova_laudo, null, $enabled, "class='novo_$i'") .  '&nbsp;</td>';
										echo '<td align="center">' . createHTMLInput('checkbox', "observacao_sac_$i", null, 't', $observacao_sac, null, $enabled,"class='novo_$i'") .  '&nbsp;</td>';
									}
									if ($login_fabrica == 1) {
										echo '<td align="center">' . createHTMLInput('checkbox', "aprova_extrato_$i", null, 't', $aprova_extrato, null, $enabled, "class='novo_$i'") .  '&nbsp;</td>';
										echo '<td align="center">' . createHTMLInput('checkbox', "aprova_protocolo_$i", null, 't', $aprova_protocolo, null, $enabled, "class='novo_$i'") .  '&nbsp;</td>';
										echo '<td align="center">' . createHTMLInput('checkbox', "pagamento_garantia_$i", null, 't', $pagamento_garantia, null, $enabled, "class='novo_$i'") .  '&nbsp;</td>';
										echo '<td align="center">' . createHTMLInput('checkbox', "solicitacao_cheque_$i", null, 't', $solicitacao_cheque, null, $enabled, "class='novo_$i'") .  '&nbsp;</td>';
										echo '<td align="center">' . createHTMLInput('checkbox', "supervisao_cheque_$i", null, 't', $supervisao_cheque, null, $enabled, "class='novo_$i'") .  '&nbsp;</td>';
									}

									if ($login_fabrica == 42) {
										echo '<td align="center">' . createHTMLInput('checkbox', "aviso_email_$i", null, 't', $aviso_email, null, $enabled, "class='novo_$i'") .  '&nbsp;</td>';
									}
									if (in_array($login_fabrica, [169,170])) {

										
										echo '<td align="center">' . createHTMLInput('checkbox', "analise_ri_$i", null, 't', $analise_ri, null, $enabled, "class='novo_$i'") .  '&nbsp;</td>';
										echo '<td align="center">' . createHTMLInput('checkbox', "suporte_tecnico_$i", null, 't', $suporte_tecnico, null, $enabled, "class='novo_$i'") .  '&nbsp;</td>';

										echo "<td align='center'>";

										$sqlTipoProtocolo = "SELECT hd_tipo_chamado, descricao 
																FROM tbl_hd_tipo_chamado
																WHERE fabrica = {$login_fabrica} 
																AND ativo IS TRUE";
										$resTipoProtocolo = pg_query($con,$sqlTipoProtocolo);

										$tipos = pg_fetch_all($resTipoProtocolo);

										echo "<select name='tipo_protocolo_".$i."[]' id='tipo_protocolo_$i' class='tipo_protocolo' multiple='multiple'>";

										foreach ($tipos as $key => $value) {
											
											$selected = (in_array($value['hd_tipo_chamado'], $tipo_protocolo)) ? "selected" : "";

											echo "<option $selected value='".$value['hd_tipo_chamado']."'>".$value['descricao']."</option>";
										}

										echo "</select> </td>";

									}
								?>
								</tr>
							<? } ?>
						</table>
					</td>
				</tr>

				<tr>
					<td>&nbsp;</td>
				</tr>

				<tr>
					<td align="center">
						<input type="button" value="+" id="novo_admin" title="Adicionar nova linha" style="cursor:pointer" /> &nbsp;
						<input type="hidden" name="btn_acao" value="">
						<input type="hidden" name="qtde_admin" value="<?=$qtde_admin?>">
						<input type="hidden" name="excedeu_admin" value="">
						<input type="button" value='<?=traduz("gravar")?>' onclick=" formSubmit();"  style="cursor:pointer"/>
					</td>
				</tr>

				<tr>
					<td>&nbsp;</td>
				</tr>
				<? } ?>
			</table>

		</form>


		<table align='center' border='0' cellpadding="2" style="width:<?=$width?>" cellspacing="0" class="tabela table_update" >
			<? if(empty($list_inativo)) { ?>
			<caption class="titulo_tabela">
				<span style="color:white;font-size: 1.2em;margin-left: 20%;line-height: 1.4em;"><?=traduz('Usuários Ativos')?></span>
				<span style="float:right;margin-right: 20%;vertical-align: middle;font-size: 0.8em;padding-top: 0.5em;">(*) <?=traduz('Menus do Sistema')?></span>
			</caption>
			<thead>
			<?=$tbl_headers?>
			</thead>
			<? } ?>
		<tbody>
		<?php

		if ($login_admin == 828) {
				$cond = " AND ativo   IS TRUE";
		}
		if(in_array($login_fabrica, array(169,170))){
			$cond_fale_conosco = "  AND login != 'midea_fale_conosco'
									AND login != 'carrier_fale_conosco'
									AND login != 'springer_fale_conosco'
									AND login != 'midea_carrier_fale_conosco'
								";
		}
		if (in_array($login_fabrica, array(169,170))) {
			$cond .= ' AND tbl_admin.callcenter_email IS NULL';
		}

		if (in_array($login_fabrica, array(191))) {
			$cond .= ' AND tbl_admin.cliente_admin IS NULL';
		}

		if(empty($list_inativo)) {
			$cond_ativo = " and ativo ";
		}else{
			$cond_ativo = " and ativo is false ";
		}

        $sql = "
          SELECT admin
               , login
               , senha
               , nome_completo
               , email
               , cliente_admin
               , cliente_admin_master
               , pais
               , fone
               , ativo
               , fale_conosco
               , responsabilidade
               , intervensor
               , atendente_callcenter
               , callcenter_supervisor
               , privilegios
               , consulta_os
               , admin_sap
               , live_help
               , responsavel_postos
               , altera_pais_produto
               , help_desk_supervisor
               , responsavel_ti
               , aprova_laudo
               , participa_agenda
               , parametros_adicionais
               , tbl_l10n.idioma
               , tbl_l10n.l10n
               , whatsapp
            FROM tbl_admin
            LEFT JOIN tbl_l10n ON tbl_l10n.l10n = tbl_admin.l10n
           WHERE fabrica = $login_fabrica $cond $cond_fale_conosco
		   $cond_ativo
		   ORDER BY ativo DESC, login";

		$res = pg_query($con,$sql);
		$tot = pg_num_rows($res);
		//echo array2table(pg_fetch_all($res)); exit;
		//die();

		$fa = new TDocs($con, $login_fabrica);
		$fa->setContext('fa');
		for ($i = 0; $i < $tot; $i++) {


			$admin					= trim(pg_fetch_result($res, $i, 'admin'));
			$login					= trim(pg_fetch_result($res, $i, 'login'));
			$senha					= trim(pg_fetch_result($res, $i, 'senha'));
			$nome_completo			= trim(pg_fetch_result($res, $i, 'nome_completo'));
			$email					= trim(pg_fetch_result($res, $i, 'email'));
			$cliente_admin			= trim(pg_fetch_result($res, $i, 'cliente_admin'));
			$l10n 	 				= trim(pg_fetch_result($res, $i, 'l10n'));
			$cliente_admin_master	= trim(pg_fetch_result($res, $i, 'cliente_admin_master'));
            $pais                   = strtoupper(trim(pg_fetch_result($res, $i, 'pais')));
			$fone					= trim(pg_fetch_result($res, $i, 'fone'));

			if (!in_array($login_fabrica, [180,181,182])) {
				$fone                      = str_replace(['(',')','-'], '', $fone);
			}
			$ativo                  = trim(pg_fetch_result($res, $i, 'ativo'));
			$fale_conosco           = trim(pg_fetch_result($res, $i, 'fale_conosco'));
			$email_assinatura       = trim(pg_fetch_result($res, $i, 'responsabilidade'));
			$intervensor            = trim(pg_fetch_result($res, $i, 'intervensor'));
			$atendente_callcenter   = trim(pg_fetch_result($res, $i, 'atendente_callcenter'));//HD 335548
			$supervisor_call_center = trim(pg_fetch_result($res, $i, 'callcenter_supervisor'));
			$privilegios            = trim(pg_fetch_result($res, $i, 'privilegios'));
			$consulta_os            = trim(pg_fetch_result($res, $i, 'consulta_os'));
			$admin_sap              = trim(pg_fetch_result($res, $i, 'admin_sap'));
			$live_help              = trim(pg_fetch_result($res, $i, 'live_help'));
			$responsavel_postos     = trim(pg_fetch_result($res, $i, 'responsavel_postos'));//HD 233213
			$altera_pais_produto    = trim(pg_fetch_result($res, $i, 'altera_pais_produto')); // HD 374998
			$sup_help_desk          = trim(pg_fetch_result($res, $i, 'help_desk_supervisor'));
			$responsavel_ti         = trim(pg_fetch_result($res, $i, 'responsavel_ti'));
			$aprova_laudo           = trim(pg_fetch_result($res, $i, 'aprova_laudo'));
			$libera_pedido          = trim(pg_fetch_result($res, $i, 'libera_pedido'));
			$whatsapp               = trim(pg_fetch_result($res, $i, 'whatsapp'));
			// $somente_visualiza_call_center = trim(pg_fetch_result($res, $i, 'somente_visualiza_call_center'));        
			unset($troca_reembolso);
			$parametros_adicionais  = json_decode(pg_fetch_result($res, $i, 'parametros_adicionais'), true);
			$celular                = (isset($parametros_adicionais['celular'])) ? $parametros_adicionais['celular'] : "";

			$cliente_admin_multiplo = $parametros_adicionais["clientes_admin"];

			$contas = $parametros_adicionais["permissao_contas"];

			if (count($parametros_adicionais)) {
				extract($parametros_adicionais, EXTR_OVERWRITE);
			}

			if ($login_fabrica == 115) {
				$altera_categoria_posto = (empty($parametros_adicionais['altera_categoria_posto'])) ? 'f' : $parametros_adicionais['altera_categoria_posto'];
			}

			if ($telecontrol_distrib) {
				$visualiza_estoque_distrib = (empty($parametros_adicionais['visualiza_estoque_distrib'])) ? 'f' : $parametros_adicionais['visualiza_estoque_distrib'];
			}

			if (in_array($login_fabrica, array(169,170))) {
				$recebe_jornada = pg_fetch_result($res, $i, 'participa_agenda');

				$analise_ri 	  = $parametros_adicionais["analise_ri"];
				$suporte_tecnico  = $parametros_adicionais["suporte_tecnico"];

			}

			if ($usa_admin_ramal) {
				$ramal = trim(pg_fetch_result($res, $i, 'ramal'));
			}

			if(in_array($login_fabrica, [169,170])){

				$sqlTipoProtocolo = "SELECT hd_tipo_chamado FROM tbl_hd_tipo_chamado_vinculo WHERE admin = {$admin}";
				$resTipoProtocolo = pg_query($sqlTipoProtocolo);

				$result = pg_fetch_all($resTipoProtocolo);
				unset($tipos_protocolo);
				if(pg_num_rows($res) > 0){
					foreach ($result as $key => $value) {
						$tipos_protocolo[] = $value['hd_tipo_chamado'];
					}				
				}

			}

			$cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';

			if ($ativo == 'f' && strlen($titulo) == 0) {
				$titulo = "Usuários Inativos";
				echo "</table>
				<table align='center' border='0' cellpadding=\"2\" cellspacing=\"0\" style='width:$width' class=\"tabela_inativa table_update\"  >
					<thead>
						<caption class='titulo_tabela_inativa titulo_tabela'>
							<span style='color:white;font-size: 1.2em;margin-left: 20%;line-height: 1.4em;'>".traduz("Usuários Inativos")."</span>
							<span style='float:right;margin-right: 20%;vertical-align: middle;font-size: 0.8em;padding-top: 0.5em;'>(*) ".traduz("Menus do Sistema")."</span>
						</caption>
						$tbl_headers_inativos
					</thead>";
				$class_inativo = 'inativo';

			}

			if (strlen($titulo)>0){
				$cor        = ($i % 2 == 0) ? '#FFFAFA' : '#FFEEEE';
			}

			$foto_admin = '';
			$fa->getDocumentsByRef($admin);
			if ($fa->temAnexo) {
				$foto_admin = sprintf("<a href='%s'><img class='fa' src='%s' /></a>", $fa->url, $fa->url);
				$tem_fotos  = true;
			}

			$campo_ativo= false;
			// if ( $i % 20==0 and $i>0) {
			//  echo $tbl_headers;
			// }
			$ro         = ($campo_ativo) ? '':'readonly';?>

		<tr style="background-color:<?=$cor?>;" id="dados_<?=$i?>" rel="<?php echo $i ?>" class="resultado <?=$class_inativo ?>" >
			<td width='54px'>
				<input type="button" value="Editar" id="btn_editar_admin_<?=$i?>" style='cursor:pointer' rel='<?=$i?>' name="btn_editar_admin" >
			</td>
			<td align="left">
				<input type='hidden' name='admin_<?=$i?>' value='<?=$admin?>'>
				<label id="lbl_login_<?=$i?>" style='cursor:pointer'><?php echo $login ?></label>
				<input type='text' name='login_<?=$i?>' id="login_<?=$i?>" style="display:none;width:125px;" maxlength='20' value='<?=$login?>' >
			</td>
			<td style="width:106px" align="left">
				<input type='password' name='senha_<?=$i?>' id='senha_<?=$i?>' disabled="disabled" size='20' maxlength='20' value='<?=sha1($senha)?>'>
			</td>
			<td style="width:222px" align="left">
				<label id="lbl_nome_completo_<?=$i?>" style='cursor:pointer'><?php echo $nome_completo ?></label>
				<input type='text' name='nome_completo_<?=$i?>' id='nome_completo_<?=$i?>' style="display:none;width:218px" value='<?=$nome_completo?>' <?=$foto_admin?>
			</td>
			<td style="width:100px">
				<?php 
					$class_tel = (!in_array($login_fabrica, [180,181,182])) ? "telefone" : "tel";
				?>
				<label id="lbl_fone_<?=$i?>" style='cursor:pointer' ><?php echo formataLabelTelefone($fone) ?></label>
				<input type='text' name='fone_<?=$i?>' id='fone_<?=$i?>' size='13' maxlength='17' style="display:none" class="fone <?=$class_tel?>" value='<?=$fone?>' >
			</td>
			<td style="width:100px">
				<label id="lbl_celular_<?=$i?>" style='cursor:pointer' ><?php echo formataLabelTelefone($celular) ?></label>
				<?php 
					$class_cel = (!in_array($login_fabrica, [180,181,182])) ? "cel" : "tel"; 
				?>
				<input type='text' name='celular_<?=$i?>' id='celular_<?=$i?>' size='13' maxlength='18' style="display:none" class="fone <?=$class_cel?>" value='<?=$celular?>' >
			</td>
			<td style="width:100px">
				<label id="lbl_whatsapp_<?=$i?>" style='cursor:pointer' ><?php echo formataLabelTelefone($whatsapp); ?></label>
				<input type='text' name='whatsapp_<?=$i?>' id='whatsapp_<?=$i?>' size='13' maxlength='18' style="display:none" class="fone cel" value='<?=$whatsapp?>' data-id='<?=$i?>'>
			</td>
			<?php if ($usa_admin_ramal) { ?>
			<td style="width:100px">
				<label id="lbl_ramal_<?=$i?>" style='cursor:pointer' ><?php echo $ramal ?></label>
				<input type='text' name='ramal_<?=$i?>' id='ramal_<?=$i?>' size='5' maxlength='5' style="display:none" class="" value='<?=$ramal?>' >
			</td>
			<?php } ?>
			<td nowrap style="width:256px" align="left">
				<label id="lbl_email_<?=$i?>" style='cursor:pointer'  ><?php echo $email ?></label>
				<input type='text' name='email_<?=$i?>' id='email_<?=$i?>' size='30' style="display:none" maxlength='' value='<?=$email?>' >
			</td>
			<?php
			if( $ativo == 't' ) {
					echo "<td nowrap>";
						echo "<select name='l10n_$i' id='l10n_$i' disabled='disabled'>";
						echo "<option></option>";
							
							$sql_idioma = $sql_idioma = "SELECT l10n, nome, idioma
                                           	             FROM tbl_l10n
														 ORDER BY idioma";
															   												
							$res_idioma = pg_query($con, $sql_idioma);

							if (pg_num_rows($res_idioma) > 0) {
								for ($w = 0; $w < pg_num_rows($res_idioma); $w++) {
									$l10n_id = pg_fetch_result($res_idioma, $w, 'l10n');
									$idioma  = pg_fetch_result($res_idioma, $w, 'idioma');
																																								
									$selected =  (in_array($l10n_id, [$_POST['l10n_'.$i], $l10n]) ) ? 'selected' : '' ;
									echo "<option value='$l10n_id' $selected>$idioma</option>";
								}
							}
			} else {
				echo "<td nowrap>";
					$sql_idioma_inativo = "SELECT tbl_l10n.idioma
										   FROM tbl_l10n
										   JOIN tbl_admin USING(l10n)
										   WHERE tbl_admin.admin = $admin";

					$res_inativo = pg_query($con,$sql_idioma_inativo);

					if (pg_num_rows($res_inativo) > 0) {
						echo pg_fetch_result($res_inativo,0,0);
						echo '<input type="hidden" name="l10n_'.$i.'" value="'.pg_fetch_result($res_inativo,0,1).'" />';
					}
					else echo '&nbsp;';

					echo "</td>";

				}
			
			if ($login_fabrica == 20) {?>
				<td nowrap>
					<label id="lbl_pais_<?=$i?>" style='cursor:pointer' ><?php echo $pais ?></label>
					<input type='text' name='pais_<?=$i?>' id='pais_<?=$i?>' size='2' maxlength='2' style="display:none" value='<?=$pais?>' >
				</td><?php
			}

			if ($abre_os_admin_arr) {

				if( $ativo == 't' ) {
					if (in_array($login_fabrica, [158])) { 

						$sqlClienteAdmin = "
							SELECT cliente_admin,
								   SUBSTRING(nome, 1, 20) AS nome, 
								   SUBSTRING(cidade, 1, 15) AS cidade
							FROM tbl_cliente_admin
							WHERE fabrica = {$login_fabrica}
							AND abre_os_admin IS TRUE
							ORDER BY nome
						";
						$resClienteAdmin = pg_query($con,$sqlClienteAdmin);
						?>
						
						<td nowrap>
							<select multiple class="cliente_admin_multiplo" name="cliente_admin_multiplo_<?= $i ?>[]" id='cliente_admin_multiplo_<?= $i ?>' disabled>
								<option value=""></option>
								<?php
								while ($dados = pg_fetch_object($resClienteAdmin)) { 

									$selected = (in_array($dados->cliente_admin, $cliente_admin_multiplo)) ? "selected" : "";

								?>
									<option value="<?= $dados->cliente_admin ?>" <?= $selected ?>><?= $dados->cidade ?> - <?= $dados->nome ?></option>
								<?php
								} ?>
							</select>
						</td>   

					<?php
					} else {

						echo "<td nowrap>";
							echo "<select name='cliente_admin_$i' id='cliente_admin_$i' disabled='disabled'>";
								echo "<option></option>";

								if (in_array($login_fabrica,array(85,156,167,203))) {
									$sql_cliente_admin = "SELECT    cliente_admin,
												nome,cidade
												FROM tbl_cliente_admin
												WHERE fabrica = $login_fabrica
												ORDER BY nome";
								} else {
									$sql_cliente_admin = "
										SELECT  cliente_admin,
												nome, cidade
										FROM    tbl_cliente_admin
										WHERE   fabrica = $login_fabrica
										AND     abre_os_admin is true
								  ORDER BY      nome";

								}

							$res_cliente_admin = pg_query($con,$sql_cliente_admin);
							$total = pg_num_rows($res_cliente_admin);

							if ($total > 0) {

								for ($w = 0; $w < $total; $w++) {

									$xcliente_admin = pg_fetch_result($res_cliente_admin, $w, 'cliente_admin');
									$nome           = pg_fetch_result($res_cliente_admin, $w, 'nome');
									$cidade         = pg_fetch_result($res_cliente_admin, $w, 'cidade');
									$nome           = ucwords(strtolower(substr($nome,0,20)));
									$cidade         = ucwords(strtolower(substr($cidade,0,15)));

									echo "<option value='$xcliente_admin'".($xcliente_admin == $cliente_admin ? "SELECTED" : '').">$nome - $cidade</option>";
								}

							}

						echo "</select></td>";

					}

				} else {
					echo "<td nowrap>";
					$sql_cli_admin_inativo = "SELECT nome, tbl_cliente_admin.cliente_admin
												FROM tbl_cliente_admin
												JOIN tbl_admin USING (cliente_admin)
											   WHERE tbl_admin.admin   = $admin
												 AND tbl_admin.fabrica = $login_fabrica";

					$res_inativo = pg_query($con,$sql_cli_admin_inativo);

					if (pg_num_rows($res_inativo)) {
						echo pg_fetch_result($res_inativo,0,0);
						echo '<input type="hidden" name="cliente_admin_'.$i.'" value="'.pg_fetch_result($res_inativo,0,1).'" />';
					}
					else echo '&nbsp;';

					echo "</td>";

				}

			if ($login_fabrica != 96) {
				echo '<td>' . createHTMLInput('checkbox', "cliente_admin_master_$i", null, 't', $cliente_admin_master, null, $campo_ativo) . '&nbsp;</td>';
			}

		}

		if (strpos(" $privilegios",'*')>0){
			$checked_gerencia = true;
			$checked_cadastros = true;
			$checked_call_center = true;
			$checked_info_tecnica = true;
			$checked_financeiro = true;
			$checked_auditoria = true;
		}else{
			$checked_gerencia = strpos(" $privilegios",'gerencia')>0;
			$checked_cadastros = strpos(" $privilegios",'cadastro')>0;
			$checked_call_center = strpos(" $privilegios",'call_center')>0 ;
			$checked_info_tecnica = strpos(" $privilegios",'info_tecnica' )>0;
			$checked_financeiro = strpos(" $privilegios",'financeiro')>0;
			$checked_auditoria = strpos(" $privilegios",'auditoria')>0;
			$checked_inspetor = strpos(" $privilegios",'inspetor')>0;
		}

		// if(strlen($responsabilidade) > 0){
		//  $checked_responsabilidade = true;
		// }else{
		//  $checked_responsabilidade = false;
		// }

		echo '<td>' . createHTMLInput('checkbox', "ativo_$i", null, 't', $ativo, null, $campo_ativo) . '&nbsp;</td>';

		if ($login_privilegios == '*') {
			echo '<td>' . createHTMLInput('checkbox', "master_$i"               , true, 'master'        , $master                   , strpos(" $privilegios",'*')>0             , $campo_ativo,"class='master'") . '</td>';
		}
		echo '<td>' . createHTMLInput('checkbox', "sup_help_desk_$i"        , true, 't'             , $sup_help_desk            , null                                      , $campo_ativo) . '</td>';

		if ($login_fabrica == 168) {
			echo '<td>' . createHTMLInput('checkbox', "libera_pedido_$i" , null, 't' , $libera_pedido, null, $campo_ativo, "class='libera_pedido'") . '</td>';
		}

		echo '<td>' . createHTMLInput('checkbox', "live_help_$i"            , true, 't'             , $live_help                , null                                      , $campo_ativo, "class='live_help'") . '</td>';

		echo '<td>' . createHTMLInput('checkbox', "responsavel_ti_$i"       , null, 't'             , $responsavel_ti           , null                                      , $campo_ativo) . '</td>';

		// Privilégios
		echo '<td>' . createHTMLInput('checkbox', "gerencia_$i"              , null, 'gerencia'     , null                      , $checked_gerencia         , $campo_ativo,"class='privilegios_$i'") . '</td>';
		echo '<td>' . createHTMLInput('checkbox', "cadastros_$i"             , null, 'cadastros'    , null                      , $checked_cadastros    , $campo_ativo,"class='privilegios_$i'") . '</td>';
		echo '<td>' . createHTMLInput('checkbox', "call_center_$i"           , null, 'call_center'  , null                      , $checked_call_center  , $campo_ativo,"class='privilegios_$i'") . '</td>';

		if (in_array($login_fabrica, array(169,170))) {
			echo '<td>' . createHTMLInput('checkbox', "recebe_jornada_$i", null, 't', $recebe_jornada, null, $campo_ativo) . '</td>';
		}

		echo '<td>' . createHTMLInput('checkbox', "supervisor_call_center_$i", null, 't', $supervisor_call_center, null, $campo_ativo) . '</td>';

		if (!in_array($login_fabrica, array(169,170,171,173,174,175,176,184,191,193,198,200,203)) || $usa_somente_callcenter) {
			echo '<td>' . createHTMLInput('checkbox', "somente_visualiza_call_center_$i", null, 't'           	, $somente_visualiza_call_center 	, null                  					, $campo_ativo) . '</td>';
		}		
		
		if ($integracaoTelefonia === true || (in_array($login_fabrica, array(10)))) {
			if ($parametros_adicionais['sacTelecontrol']) {
				$checked_sac_telecontrol = true;
			} else {
				$checked_sac_telecontrol = false;
			}
			echo '<td>' . createHTMLInput('checkbox',  "sac_telecontrol_$i"          , null, 't',        null                      , $checked_sac_telecontrol ,     $campo_ativo, "class='privilegios_$i'") . '</td>';          
		}

		echo '<td>' . createHTMLInput('checkbox',  "info_tecnica_$i"          , null, 'info_tecnica' ,        null                      , $checked_info_tecnica ,     $campo_ativo, "class='privilegios_$i'") . '</td>';
		echo '<td>' . createHTMLInput('checkbox',  "financeiro_$i"            , null, 'financeiro'   ,        null                      , $checked_financeiro   ,     $campo_ativo, "class='privilegios_$i'") . '</td>';
		echo '<td>' . createHTMLInput('checkbox',  "auditoria_$i"             , null, 'auditoria'    ,        null                      , $checked_auditoria    ,     $campo_ativo, "class='privilegios_$i'") . '</td>';
		#echo '<td>' . createHTMLInput('checkbox', "responsabilidade_$i"      , null, 'responsabilidade'    , null              ,         $checked_responsabilidade , $campo_ativo, "class='privilegios_$i'") . '</td>';

		if ($login_fabrica == 91)  // HD 685194
			echo '<td>' . createHTMLInput('checkbox', "promotor_wanke_$i", null, 'promotor', null, (strpos($privilegios, 'promotor')  !== false), $campo_ativo) . '&nbsp;</td>';

		if ($usa_altera_pais_produto)  // HD 374998
			echo '<td>' . createHTMLInput('checkbox', "altera_pais_produto_$i", null, 't', $altera_pais_produto, null, $campo_ativo) . '&nbsp;</td>';

		if ($usa_atende_hd_postos)
			echo '<td>' . createHTMLInput('checkbox', "sap_$i", true, 't', $admin_sap, null, $campo_ativo) . '</td>';

		if ($usa_responsavel_postos)
			echo '<td>' . createHTMLInput('checkbox', "responsavel_postos_$i", null, 't', $responsavel_postos, null, $campo_ativo) . '&nbsp;</td>';


		if ($login_fabrica == 115) {
			echo '<td>' . createHTMLInput('checkbox', "altera_categoria_posto_$i", null, 't', $altera_categoria_posto, null, $campo_ativo) . '&nbsp;</td>';
		}

		if($login_fabrica == 3){
			$checked1 = ($parametros_adicionais['aprova_avulso_1'] == 't') ? " checked ": " ";
			$checked2 = ($parametros_adicionais['aprova_avulso_2'] == 't') ? " checked ": " ";
			echo "<td> <input type='checkbox' name='aprova_avulso_1_$i' disabled value='t' rel='$i' $campo_ativo  $checked1>    </td>";
			echo "<td> <input type='checkbox' name='aprova_avulso_2_$i'  disabled value='t' rel='$i' $campo_ativo  $checked2>   </td>";
		}

		if ($usa_atendente_callcenter)
			echo '<td>' . createHTMLInput('checkbox', "atendente_callcenter_$i", null, 't', $atendente_callcenter, null, $campo_ativo) . '&nbsp;</td>';

		if ($usa_recebe_fale_conosco)
			echo '<td>' . createHTMLInput('checkbox', "fale_conosco_$i", null, 't', $fale_conosco, null, $campo_ativo) . '&nbsp;</td>';
		if ($manda_email_assinatura)
//          echo '<td>' . createHTMLInput('checkbox', "envia_email_$i", null, 'envia_email', $email_assinatura, null, $campo_ativo) . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "envia_email_$i", null, 'envia_email', $email_assinatura, null, $campo_ativo) . '</td>';

		if ($usa_intervensor)
			echo '<td>' . createHTMLInput('checkbox', "intervensor_$i", null, 't', $intervensor, null, $campo_ativo) . '&nbsp;</td>';

		if ($telecontrol_distrib) {
			echo '<td>' . createHTMLInput('checkbox', "visualiza_estoque_distrib_$i", null, 't', $visualiza_estoque_distrib, null, $campo_ativo) . '&nbsp;</td>';
		}

		if($login_fabrica == 151){
			echo '<td>' . createHTMLInput('checkbox', "troca_reembolso_$i"          , true, 't'             , $troca_reembolso  , null                                      , $campo_ativo, "class='troca_reembolso'") . '</td>';
		}

		if ($login_fabrica == 19)
			echo '<td>' . createHTMLInput('checkbox', "consulta_os_$i", null, 't', $consulta_os, null, $campo_ativo) .  '&nbsp;</td>';

		//@todo fricon
		if ($login_fabrica == 52 || $login_fabrica == 24) {

			echo '<td>' . createHTMLInput('checkbox', "inspetor_$i", null, 'inspetor', $checked_inspetor, null, $campo_ativo) .  '&nbsp;</td>';

		}
		if ($login_fabrica == 30) {

			echo '<td>' . createHTMLInput('checkbox', "aprova_laudo_$i", null, 't', $aprova_laudo, null, $campo_ativo) .  '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "observacao_sac_$i", null, 't', $observacao_sac, null, $campo_ativo) .  '&nbsp;</td>';

		}
		if ($login_fabrica == 1) {

			echo '<td>' . createHTMLInput('checkbox', "aprova_extrato_$i", null, 't', $aprova_extrato, null, $campo_ativo) .  '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "aprova_protocolo_$i", null, 't', $aprova_protocolo, null, $campo_ativo) .  '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "pagamento_garantia_$i", null, 't', $pagamento_garantia, null, $campo_ativo) .  '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "solicitacao_cheque_$i", null, 't', $solicitacao_cheque, null, $campo_ativo) . '&nbsp;</td>';

			if ($parametros_adicionais['supervisao_cheque'] == 't') {
				$checked_supervisao_cheque = true;
			} else {
				$checked_supervisao_cheque = false;
			}
			echo '<td>' . createHTMLInput('checkbox', "supervisao_cheque_$i", null, 't', $checked_supervisao_cheque, null, $campo_ativo) . '&nbsp;</td>';

			echo '<td align="center">
					<select multiple class="multi_contas" name="contas_'.$i.'[]">
						<option value="analista_posvenda" '.((in_array("analista_posvenda", $contas)) ? "selected" : "").'>Analista de Pós-Vendas</option>
						<option value="gerente_posvenda" '.((in_array("gerente_posvenda", $contas)) ? "selected" : "").'>Gerente de Pós-Vendas</option>
						<option value="analista_contas_pagar" '.((in_array("analista_contas_pagar", $contas)) ? "selected" : "").'>Analista de Contas a Pagar</option>
						<option value="gerente_contas_pagar" '.((in_array("gerente_contas_pagar", $contas)) ? "selected" : "").'>Gerente de Contas a Pagar</option>
					</select>
				  </td>';

		}
		if ($login_fabrica == 42) {

			echo '<td>' . createHTMLInput('checkbox', "aviso_email_$i", null, 't', $aviso_email, null, $campo_ativo) .  '&nbsp;</td>';

		}

		if (in_array($login_fabrica, [169,170])) {

			echo '<td>' . createHTMLInput('checkbox', "analise_ri_$i", null, 't', $analise_ri, null, $campo_ativo) .  '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "suporte_tecnico_$i", null, 't', $suporte_tecnico, null, $campo_ativo) .  '&nbsp;</td>';

				echo "<td align='center'>";

				$sqlTipoProtocolo = "SELECT hd_tipo_chamado, descricao 
										FROM tbl_hd_tipo_chamado
										WHERE fabrica = {$login_fabrica} 
										AND ativo IS TRUE";
				$resTipoProtocolo = pg_query($con,$sqlTipoProtocolo);

				$tipos = pg_fetch_all($resTipoProtocolo);

				echo "<select name='tipo_protocolo_".$i."[]' id='tipo_protocolo_$i' class='tipo_protocolo' multiple='multiple'>";

				foreach ($tipos as $key => $value) {
					
					$selected = (in_array($value['hd_tipo_chamado'], $tipos_protocolo)) ? "selected" : "";

					echo "<option $selected value='".$value['hd_tipo_chamado']."'>".$value['descricao']."</option>";
				}

				echo "</select> </td>";

		}

		if ($login_fabrica == 1) {
			echo "<td style='text-align: center;'><button class='btn_ver' name='ver_$i' data-admin='$admin' data-posicao='$i' data-ver='' >Ver</button>&nbsp;</td>";
		}       

		?>

		</tr>

		<tr id="gravar_erro_<?=$i?>" style="display:none" class='msg_erro'>
			<td colspan="100%">

			</td>
		</tr>

		<tr id="gravar_<?=$i?>" rel='<?php echo $i ?>' style="display:none">
			<td colspan="100%">
				<input type="hidden" name="qtde_admin" value="<?=$qtde_admin?>">
				<input type="button" name="btn_gravar_item" rel="<?=$i?>" id="btn_gravar_<?=$i?>" style='cursor:pointer' value="<?= traduz("gravar")?>" />
				<input type="button" name="btn_fechar_item" rel="<?=$i?>" id="btn_fechar_<?=$i?>" style='cursor:pointer' value="<?= traduz("fechar")?>" />
				<center>
					<div class='tac'>
						<a name="auditor_log_item" id="auditor_log_<?=$i?>" rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_admin&id=<?php echo $admin; ?>'><?=traduz('Visualizar Log Auditor')?></a>
					</div>
				</center>
			</td>

		</tr>

	<? } ?>
		</tbody>
		<input type='hidden' name='qtde_item' value="<?=$i?>" />

	</table>
	<!-- </div> -->
	<center>
		<input type="hidden" name="btn_acao" value="" /><?php

		//HD 666788 - Funcionalidades por admin
		$sql = "SELECT fabrica FROM tbl_funcionalidade WHERE fabrica=$login_fabrica OR fabrica IS NULL";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {?>
			<input type="button" style="cursor:pointer;" value='<?=traduz("Funcionalidades")?>' onclick="window.open('funcionalidades_cadastro.php');" /><?php
		}?>
	</center>
	<br />
	<?php

	if ($tem_fotos) {?>
		<script src="../js/FancyZoom.js" type="text/javascript"></script>
		<script src="../js/FancyZoomHTML.js" type="text/javascript"></script>
		<script type="text/javascript">
			setupZoom();
		</script><?php
	}

} ?>

<style type="text/css">
	
</style>
<script type="text/javascript">
	$(function() {

		<?php 
			$admin = calculaAdmin();
			if ($admin['qtd_admins_disponiveis'] <= 0) 
		{?>

			$(".inativo").each(function() {
			
				$(this).click(function() { 
					alert("Sua empresa hoje tem o limite máximo de usuários já cadastrados.\nPara cadastrar um novo usuário entre em contato com o suporte Telecontrol"); 
					return false; 
				});

			});
		<?php } ?>

		// Máscara de telefone //
		$('.fone').focusin(function(){$(this).mask("(99) 999999999");});
        $('.fone').focusout(function() {
            var phone, element;

            element = $(this);
            phone = element.val().replace(/\D/g, '');

            if(phone.length > 10) {
                element.mask("(99) 99999-9999");
            } else {
                element.mask("(99) 9999-9999");
            }
        }).trigger('focusout');
	
	});

</script>
<?php 
include "rodape.php"; ?>

