<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "auditoria";
include "autentica_admin.php";
include 'funcoes.php';

#$os_os = $os;

$tipo = $_GET["tipo"];
$os   = $_GET["os"];

if( strlen($_POST["os"]) > 0 ){
	$os = $_POST["os"];
}

$tipo        = $_GET["tipo"];
$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){

	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	if($select_acao == "112" AND strlen($observacao) == 0){
		$msg_erro .= "Informe o motivo da reprovação.";
	}

	if(strlen($observacao) > 0){
		$observacao = str_replace("'","",$observacao); //HD 43883
	}else{
		$observacao = "";
	}

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}

	for ($x=0;$x<$qtde_os;$x++){

		$xxos = trim($_POST["check_".$x]);

		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

			$res_os = pg_exec($con,"BEGIN TRANSACTION");

			$sql = "SELECT status_os, tbl_os_status.observacao, tbl_os.posto, tbl_os.sua_os, codigo_posto, tbl_posto_fabrica.contato_email
					FROM tbl_os_status
					JOIN tbl_os ON tbl_os_status.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
					JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE status_os IN (110,111,112)
					AND tbl_os_status.os = $xxos
					ORDER BY data DESC
					LIMIT 1";
			$res_os = pg_exec($con,$sql);

			if (pg_numrows($res_os)>0){
				$status_da_os        = trim(pg_result($res_os,0,'status_os'));
				$status_obs_anterior = trim(pg_result($res_os,0,'observacao'));
				$posto 				 = pg_result($res_os,0,'posto');
				$posto_contato_email = pg_result($res_os,0,'contato_email');
				$codigo_posto		 = pg_result($res_os,0,'codigo_posto');
				$sua_os              = pg_result($res_os,0,'sua_os');
				$num_os_black        = $codigo_posto . $sua_os;

				if($login_fabrica == 1){
					$tem_os_aberta = "";
					$sqlOS = "SELECT tbl_os.os
							FROM tbl_os
							WHERE tbl_os.fabrica = $login_fabrica
							AND   tbl_os.posto   = $posto
							AND   (tbl_os.data_abertura + INTERVAL '60 days') <= current_date
							AND   tbl_os.data_fechamento IS NULL
							AND  tbl_os.excluida is FALSE LIMIT 1";

					$resOS = pg_query ($con,$sqlOS);
					if(pg_num_rows($resOS) > 0){
						$tem_os_aberta = pg_fetch_result($resOS, 0, 'os');
					}

				}

				if(strlen($status_obs_anterior) > 0){
					$status_obs_anterior = str_replace("'","",$status_obs_anterior); //HD 63957
				}

				//hd 48433 - incluido um x no nome da variável
				$xobservacao = "'<p>" . $status_obs_anterior. "</p><p>&nbsp;</p><p>" . $observacao . "</p>'"; //HD 42828

				if( $status_da_os == 110 ){
					//Aprovada
					if( $select_acao == "111" ){
						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin)
								VALUES ($xxos,$select_acao,current_timestamp,$xobservacao,$login_admin)";
						//echo $sql.'<BR><BR>';
						$res = @pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin)
								VALUES ($xxos,15,current_timestamp,$xobservacao,$login_admin)";
						//echo $sql.'<BR><BR>';
						$res = @pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$sql = "SELECT fn_os_excluida($xxos,$login_fabrica,$login_admin);";
						//echo $sql.'<BR><BR>';
						$res = @pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);


						$sql = "SELECT tbl_os_item.parametros_adicionais,
										tbl_os_item.custo_peca,
										tbl_os_item.qtde,
										tbl_peca.referencia,
										tbl_peca.descricao
								FROM tbl_os_item
								JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
								JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
								WHERE tbl_os_produto.os = $xxos";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if(pg_num_rows($res) > 0){
							for($i = 0; $i < pg_num_rows($res); $i++){
								$parametros_adicionais = pg_fetch_result($res, $i, 'parametros_adicionais');
								$custo_peca 		   = pg_fetch_result($res, $i, 'custo_peca');
								$qtde 		   		   = pg_fetch_result($res, $i, 'qtde');
								$referencia	   		   = pg_fetch_result($res, $i, 'referencia');
								$descricao 	   		   = pg_fetch_result($res, $i, 'descricao');

								$valor = number_format($custo_peca * $qtde,2);

								$parametros_adicionais = json_decode($parametros_adicionais,true);
								$opcao_os  = ($parametros_adicionais['debito_peca'] == "t") ? "debito_peca" : "";
								$opcao_os = ($parametros_adicionais['coleta_peca'] == "t") ? "coleta_peca" : $opcao_os;

								$historico = "A peça $referencia - $descricao gerou um débito de R$ ".number_format($valor,2,',','.')." na OS ".$_POST['sua_os_'.$x];
								$valor = $valor * -1;
								if($opcao_os == "debito_peca"){
									$sql = "INSERT INTO tbl_extrato_lancamento(
																				posto,
																				fabrica,
																				lancamento,
																				historico,
																				debito_credito,
																				valor,
																				admin,
																				descricao) VALUES(
																				$posto,
																				$login_fabrica,
																				42,
																				'$historico',
																				'D',
																				$valor,
																				$login_admin,
																				'Débito gerado por lançamento de peça')";
									$res2 = pg_query($con,$sql);
									$msg_erro .= pg_errormessage($con);
								}
							}
						}

					}

					//Recusada
					if($select_acao == "112"){
						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin)
								VALUES ($xxos,$select_acao,current_timestamp,$xobservacao,$login_admin)";
						#echo $sql.'<BR><BR>';
						$res = @pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if($login_fabrica == 1){
							$sqlI = "SELECT parametros_adicionais, os_item
								FROM tbl_os_item
								JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
								WHERE os = $xxos";
							$resI = pg_query($con,$sqlI);
							if(pg_num_rows($resI) > 0){
								for($i = 0; $i < pg_num_rows($resI); $i++){
									$parametros_adicionais = pg_fetch_result($resI, $i, 'parametros_adicionais');
									$os_item = pg_fetch_result($resI, $i, 'os_item');

									if(!empty($parametros_adicionais)){
										$adicionais = json_decode($parametros_adicionais,true);
										unset($adicionais['debito_peca']);
										unset($adicionais['coleta_peca']);
										$adicional_os = json_encode($adicionais);
									}

									$sql = "UPDATE tbl_os_item SET parametros_adicionais = '$adicional_os'
												WHERE os_item = $os_item";
									$res2 = pg_query($con,$sql);
									$msg_erro .= pg_errormessage($con);
								}
							}
						}

						//ROTINA DE EMAIL COLOCADA NO HD 47804
						if(strlen($msg_erro)==0){
							$sqlR = "   SELECT  DISTINCT
                                                tbl_os_status.status_os,
                                                (
                                                    SELECT  email
                                                    FROM    tbl_admin
                                                    WHERE   tbl_admin.admin = tbl_os_status.admin
                                                    AND     tbl_admin.ativo IS TRUE
                                                ) AS email_admin        ,
                                                (
                                                    SELECT  codigo_posto
                                                    FROM    tbl_posto_fabrica
                                                    WHERE   tbl_posto_fabrica.posto     = tbl_os.posto
                                                    AND     tbl_posto_fabrica.fabrica   = $login_fabrica
                                                ) AS codigo_posto       ,
                                                tbl_os.sua_os
                                        FROM    tbl_os_status
                                        JOIN    tbl_os USING(os)
                                        WHERE   tbl_os_status.os = $xxos
                                        AND     status_os IN(110, 112)
                            ";
							#echo nl2br($sqlR);
							$resR = pg_exec($con, $sqlR);

							if(pg_numrows($resR)>0){
								require_once '../class/email/mailer/class.phpmailer.php';

								for($y=0; $y<pg_numrows($resR); $y++){
									$status_os    = pg_result($resR, $y, 'status_os');
									$email_admin  = pg_result($resR, $y, 'email_admin');
									$codigo_posto = pg_result($resR, $y, 'codigo_posto');
									$sua_os       = pg_result($resR, $y, 'sua_os');

									if(empty($email_admin)){

										$sqlE = "SELECT email FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $login_admin";
										$resE = pg_query($con,$sqlE);
										$email_admin = pg_fetch_result($resE, 0, 'email');
									}

									$os_recusada   = $codigo_posto . $sua_os;
									$email_destino = $email_admin;

									$xxobservacao  = str_replace("'", "", $xobservacao);
									$observacao_os = "OBSERVAÇÃO: " . $xxobservacao;

									/*

									$message  = "<p><b>OS AGUARDANDO APROVAÇÃO DE EXCLUSÃO FOI RECUSADA</b></p>";
									$message .= "OS : ". $os_recusada . "<br>\n";
									$message .= "<p>$observacao_os</p>\n<p>&nbsp;</p>\n";

									$assunto = "RECUSA DE EXCLUSÃO";

									$mail = new PHPMailer();
									$mail->IsHTML(true);
									$mail->From = 'helpdesk@telecontrol.com.br';
									$mail->FromName = 'Telecontrol';

									$mail->AddAddress($email_destino);

									$mail->Subject = $assunto;
									$mail->Body = "$message";

									*/

									$destinatario  = $email_destino ;
									#$destinatario = "ronald.santos@telecontrol.com.br";
									$assunto = "RECUSA DE EXCLUSÃO";
									$message  = "<p><b>OS AGUARDANDO APROVAÇÃO DE EXCLUSÃO FOI RECUSADA</b></p>";
									$message .= "OS : ". $os_recusada . "<br>\n";
									$message .= "<p>$observacao_os</p>\n<p>&nbsp;</p>\n";
									$headers  = "MIME-Version: 1.0 \r\n";
									$headers .= "Content-type: text/html \r\n";
									$headers .= "From: Telecontrol Networking <helpdesk@telecontrol.com.br> \r\n";

									if (!mail($destinatario, utf8_encode($assunto), utf8_encode($message), $headers)) {
										$msg_erro = "Mensagem não enviada";
									}
								}
							}
						}
					}
				}

				if (strlen($msg_erro)==0){

					$res = pg_exec($con,"COMMIT TRANSACTION");
					$observacao_exclusao = (!empty($status_obs_anterior)) ? $status_obs_anterior : $observacao;

					if ($login_fabrica == 1 AND $select_acao == 111) {
						  $os = $xxos;
						  $xPrint = true;
						  echo "<div style='display:none;'>";
						  ob_start();
						  include 'os_print_blackedecker.php';
						  $html = ob_get_clean();
						  echo "</div>";

						include_once "../classes/mpdf61/mpdf.php";

			            $mpdf = new mPDF();
			            $mpdf->SetDisplayMode('fullpage');
			            $mpdf->charset_in = 'windows-1252';
			            $mpdf->WriteHTML($html);
			            $nomePDF = "/tmp/ordem_servico_balcao_".$xxos."_".date("Y_m_d_H_i_s").".pdf";
			            $nomePDFx = "ordem_servico_balcao_".$xxos."_".date("Y_m_d_H_i_s").".pdf";
			            $mpdf->Output($nomePDF);

						$destinatario = $posto_contato_email;
						$assunto = "Stanley Black&Decker - Os $num_os_black - Excluida pelo Fabricante";
						$message  = "<p>Prezado Autorizado,</p>";
						$message  .= "<p>A Ordem de Serviço <b>{$num_os_black}</b> foi excluida pelo fabricante.</p>";
						$message  .= "<p>Motivo: <b>$observacao_exclusao</b>.</p>";
						$message  .= "<p>Em caso de dúvidas, gentileza entrar em contato com o suporte de sua região.</p>";
						$message .= "<p>Atenciosamente.</p>\n<p>&nbsp;</p>\n";
						$message .= "<p>Stanley Black&Decker.</p>\n<p>&nbsp;</p>\n";

						//hd-3941859
						require_once '../class/email/mailer/class.phpmailer.php';

						$mail   = new PHPMailer();
						$mail->SetFrom('helpdesk@telecontrol.com.br', 'Stanley Black&Decker');
						$mail->AddAddress($destinatario, "destinatario");
						$mail->Subject = $assunto;
						$mail->MsgHTML($message);
						$mail->AddAttachment($nomePDF, $nomePDFx);

						if(strlen($observacao_exclusao) > 5) {
							if ($mail->Send()) {
								 system("rm -rf $nomePDF");
							}

						}
					}
					if($login_fabrica == 1 AND $tem_os_aberta AND $select_acao == 111){

						$dir = __DIR__."/../rotinas/blackedecker/bloqueia-posto.php";
						echo `/usr/bin/php $dir $posto`;
					}
				}else{
					$res = pg_exec($con,"ROLLBACK TRANSACTION");
				}
			}
		}
	}
}

$meses       = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
$layout_menu = "auditoria";
$title       = "APROVAÇÃO DE EXCLUSÃO DE ORDEM DE NÚMERO";

include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>


<script type="text/javascript">
var ok   = false;
var cont = 0;

function checkaTodos()
{
	f = document.frm_pesquisa2;
	if( !ok )
	{
		for( i=0; i<f.length; i++ )
		{
			if( f.elements[i].type == "checkbox" )
			{
				f.elements[i].checked = true;
				ok = true;
				if( document.getElementById('linha_'+cont) )
				{
					document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
					document.getElementById('linha_aux_'+cont).style.backgroundColor = "#F0F0FF";
				}
				cont++;
			}
		}
	}else{
		for( i=0; i<f.length; i++ )
		{
			if( f.elements[i].type == "checkbox" )
			{
				f.elements[i].checked = false;
				ok=false;
				if( document.getElementById('linha_'+cont) )
				{
					document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
					document.getElementById('linha_aux_'+cont).style.backgroundColor = "#FFFFFF";
				}
				cont++;
			}
		}
	}
}

function setCheck(theCheckbox, mudarcor, mudacor2, cor)
{
	if( document.getElementById(theCheckbox) ){
		//document.getElementById(theCheckbox).checked = (document.getElementById(theCheckbox).checked ? false : true);
	}
	if( document.getElementById(mudarcor) ){
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
	if( document.getElementById(mudacor2) ){
		document.getElementById(mudacor2).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}
</script>

<script type="text/javascript" charset="utf-8">
	$().ready(function(){
		$.datepickerLoad(Array("data_final", "data_inicial"));

<?
if($login_fabrica == 1){
?>
        $("input[type=radio]").each(function(){
            if($(this).is(":checked")){
                if($(this).val() == "aprovacao"){
                    $("#solicitado").attr("disabled",false);
                }else if($(this).val() == "aprovadas"){
                    $("#gerado").attr("disabled",false);
                }else{
                    $("#gerado").attr("disabled",true);

                    $("#solicitado").attr("disabled",true);
                }
            }
        });
		$("input[type=radio]").click(function(){
            if($(this).is(":checked")){
                if($(this).val() == "aprovacao"){
                    $("#solicitado").attr("disabled",false);

                    $("#gerado").attr("checked",false);
                    $("#gerado").attr("disabled",true);
                    $("#extrato").attr("checked",false);
                    $("#extrato").attr("disabled",true);
                }else if($(this).val() == "aprovadas"){
                    $("#gerado").attr("disabled",false);

                    $("#solicitado").attr("checked",false);
                    $("#solicitado").attr("disabled",true);
                    $("#extrato").attr("checked",false);
                    $("#extrato").attr("disabled",true);
                }else{
                    $("#gerado").attr("checked",false);
                    $("#gerado").attr("disabled",true);

                    $("#solicitado").attr("checked",false);
                    $("#solicitado").attr("disabled",true);

                    $("#extrato").attr("checked",false);
                    $("#extrato").attr("disabled",true);
                }
            }
		});

		$("input[type=checkbox]").click(function(){
            if($(this).val() == "gerado" && $(this).is(":checked")){
                $("#extrato").attr("disabled",false);
            }else if($(this).val() == "gerado" && !($(this).is(":checked"))){
                $("#extrato").attr("checked",false);
                $("#extrato").attr("disabled",true);
            }
		});

		$("#observacao").blur(function() {
			if($(this).val() == ""){
				alert("Informe o Motivo !");
				$("#btn_gravar").attr("disabled",true);
			}else {
				$("#btn_gravar").attr("disabled",false);
			}
		});

		<?
		}
		?>

	});
	function abreObs(os, codigo_posto, sua_os)
	{
		janela = window.open("obs_os_troca.php?os=" + os + "&codigo_posto=" + codigo_posto +"&sua_os=" + sua_os,"formularios",'resizable=1,scrollbars=yes,width=400,height=250,top=0,left=0');
		janela.focus();
	}
</script>

<?php

if( $btn_acao == 'Pesquisar' ){
	$data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_POST['data_final']);
	$aprova       = trim($_POST['aprova']);
    $os           = trim($_POST['os']);
    $debito       = $_POST['debito'];

	if( strlen($os) > 0 ){
		$Xos = " AND tbl_posto_fabrica.codigo_posto||tbl_os.sua_os = '$os' ";
	}

	if( strlen($aprova) == 0 ){
		$aprova = "aprovacao";
		$aprovacao = "110";
	}elseif($aprova=="aprovacao"){
		$aprovacao = "110";
	}elseif($aprova=="aprovadas"){
		$aprovacao = "111";
	}elseif($aprova=="reprovadas"){
		$aprovacao = "112";
	}elseif($aprova=="automatico"){
		$aprovacao = "111";
		$automatico = 'and tbl_os_status.automatico';
	}

	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";

		$dat = explode("/", $data_inicial );//tira a barra
		$d   = $dat[0];
		$m   = $dat[1];
		$y   = $dat[2];
		if( !checkdate($m, $d, $y) ) $msg_erro = "Data Inválida";
	}

	if (strlen($data_final) > 0) {

		$dat = explode("/", $data_final );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if( !checkdate($m, $d, $y) ) $msg_erro = "Data Inválida";

		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}

	#if ((empty($data_inicial) OR empty($data_final)) and empty($os)) {
	#	$msg_erro = "É necessário informar o intervalo de datas";
	#   }

	if(strlen($msg_erro)==0 and !empty($data_inicial) and !empty($data_final)){

		$d_ini             = explode("/", $data_inicial);//tira a barra
		$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		$d_fim             = explode("/", $data_final);//tira a barra
		$nova_data_final   = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		$aux_data_inicial  = $nova_data_inicial;
		$aux_data_final    = $nova_data_final;

		if($nova_data_final < $nova_data_inicial){
			$msg_erro = "Data Inválida.";
		}

		$nova_data_inicial = mktime(0,0,0,$d_ini[1],$d_ini[0],$d_ini[2]); // timestamp da data inicial
		$nova_data_final   = mktime(0,0,0,$d_fim[1],$d_fim[0],$d_fim[2]); // timestamp da data final

		if(strlen($msg_erro)==0){
			if (strtotime($aux_data_inicial.'+12 month') <= strtotime($aux_data_final) ) {
		            $msg_erro = 'O intervalo entre as datas não pode ser maior que 12 meses';
			}
		}
	}
}

if(strlen($msg_erro) > 0){
	echo "<p align='center' class='msg_erro' style='width:700px; margin:auto;'>$msg_erro</p>";
}
?>

	<form class='form-search form-inline tc_formulario' name="frm_pesquisa" method="post" action="<?php echo $PHP_SELF; ?>">

		<div class="titulo_tabela">Parâmetros de Pesquisa</div>
		<br />
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='numero_os'>Número da OS</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<input type="text" name="os" id="os" size="20" maxlength="20" value="<? echo $os ?>" class="frm">
							</div>
						</div>
					</div>
				</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
									<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="span12">
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_final'>Data Final</label>
						<div class='controls controls-row'>
							<div class='span4'>
									<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="span12">
							</div>
						</div>
					</div>
				</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span2'>
				 <label class="radio">
			        <input type="radio" name="aprova" value='aprovacao' <?php if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?>>Em aprovação
			    </label>
			</div>
			<div class='span2'>
			    <label class="radio">
					<input type="radio" name="aprova" value='reprovadas' <?php if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas
			    </label>
			</div>

            <?php if( $login_fabrica == 1 ){ ?>

				<div class='span2'>
					 <label class="radio">
				        <input type="radio" name="aprova" value='aprovadas' <?php if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas
				    </label>
				</div>
				<div class='span3'>
					 <label class="radio">
				        <input type="radio" name="aprova" value='automatico' <?php if(trim($aprova) == 'automatico') echo "checked='checked'"; ?> id="automatico" >Aprovadas Automático
				    </label>
				</div>
			</div>
            <? } else { ?>
	            	<div class='span2'></div>
				</div>
            <? } ?>

            <?php if( $login_fabrica == 1 ){ ?>

            <div class='row-fluid'>
				<div class='span2'></div>
				<div class='span2'>
					 <label class="checkbox">
				        <input type="checkbox" disabled="disabled" name="debito[]" id="solicitado" value="solicitado" class="frm" <?=in_array("solicitado",$debito) ? "checked" : ""?> />Débito Solicitado
				    </label>
				</div>
				<div class='span2'>
				    <label class="checkbox">
						<input type="checkbox" disabled="disabled" name="debito[]" id="gerado" value="gerado" class="frm" <?=in_array("gerado",$debito) ? "checked" : ""?> />Débito Gerado
				    </label>
				</div>
				<div class='span2'>
				    <label class="checkbox">
						<input type="checkbox" <? if(!in_array("extrato",$debito)){?>disabled="disabled"<?}?> name="debito[]" id="extrato" value="extrato" class="frm" <?=in_array("extrato",$debito) ? "checked" : ""?> />Débito em extrato
				    </label>
				</div>
				<div class='span2'></div>
			</div>

			<?php } ?>
				<input type='hidden' name='btn_acao' value=''>
				<input type="submit" class="btn" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" value="Pesquisar" />
				<br /><br />
	</form>
</div>
<?php

if( strlen($msg_erro) == 0 ){ // executa a query

	if( strlen($btn_acao)  > 0 ){
			$sqlx =  "SELECT interv.os
					INTO TEMP tmp_interv_$login_admin
					FROM (
					SELECT
					ultima.os,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN (110,111,112) AND tbl_os_status.os = ultima.os $automatico ORDER BY os_status DESC LIMIT 1) AS ultimo_status
					FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN (110,111,112) $automatico ) ultima
					) interv
					WHERE interv.ultimo_status IN ($aprovacao);

					CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os); ";

			// echo nl2br($sqlx).'<BR>';
			$res = pg_exec($con, $sqlx);

			if( $aprova=="aprovadas" || $aprova == 'automatico' ){ // HD 38363 8/9/2008


				$sqly = " SELECT DISTINCT tbl_os_excluida.os ,
						tbl_os_excluida.sua_os ,
						tbl_admin.login                                AS admin_nome,
						tbl_os_excluida.consumidor_nome ,
						TO_CHAR(tbl_os_excluida.data_abertura,'DD/MM/YYYY') AS data_abertura,
						TO_CHAR(tbl_os_excluida.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
						tbl_os_excluida.fabrica ,
						tbl_os_excluida.consumidor_nome ,
						tbl_os.nota_fiscal_saida ,
						tbl_os_excluida.serie AS produto_serie ,
						to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
						tbl_posto.nome AS posto_nome ,
						tbl_posto_fabrica.codigo_posto ,
						tbl_posto_fabrica.contato_estado ,
						tbl_produto.referencia AS produto_referencia ,
						tbl_produto.descricao AS produto_descricao ,
						tbl_produto.voltagem ,
						(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_excluida.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_os,
						(SELECT observacao FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os_excluida.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_observacao,
						(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os_excluida.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_descricao,
						(SELECT tbl_admin.login FROM tbl_os_status JOIN tbl_admin USING(admin) WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os_excluida.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS admin_exclusao,
						(SELECT TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os_excluida.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_data,
						(SELECT tbl_os_status.data FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os_status.fabrica_status = $login_fabrica AND tbl_os_excluida.os = tbl_os_status.os AND status_os IN (110) ORDER BY data DESC LIMIT 1) AS status_data2
					FROM tmp_interv_$login_admin X
					JOIN tbl_os ON tbl_os.os = X.os
					JOIN tbl_os_excluida ON tbl_os_excluida.os = X.os
					LEFT JOIN tbl_admin         ON  tbl_admin.admin           = tbl_os.admin
					JOIN tbl_produto ON tbl_produto.produto = tbl_os_excluida.produto
					JOIN tbl_posto ON tbl_os_excluida.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os_excluida.fabrica = $login_fabrica
					$Xos
					";
					if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
						$sqly .= " AND tbl_os_excluida.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final' ";
					}
					$sqly .= " ORDER BY status_data2 DESC ";

			}else{

				$sqly = " SELECT tbl_os.os,
						tbl_os.sua_os                                               ,
						tbl_admin.login                                AS admin_nome,
						tbl_os.consumidor_nome                                      ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
						tbl_os.fabrica                                              ,
						tbl_os.consumidor_nome                                      ,
						tbl_os.nota_fiscal_saida                                    ,
						tbl_os.serie                       AS produto_serie         ,
						to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
						tbl_posto.nome                     AS posto_nome            ,
						tbl_posto_fabrica.codigo_posto                              ,
						tbl_posto_fabrica.contato_estado                            ,
						tbl_produto.referencia             AS produto_referencia    ,
						tbl_produto.descricao              AS produto_descricao     ,
						tbl_produto.voltagem                                        ,
						(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_os         ,
						(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_observacao,
						(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_descricao,
						(SELECT tbl_admin.login FROM tbl_os_status JOIN tbl_admin USING(admin) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS admin_exclusao,
						(SELECT TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (110,111,112) ORDER BY data DESC LIMIT 1) AS status_data,
						(SELECT tbl_os_status.data FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (110) ORDER BY data DESC LIMIT 1) AS status_data2
                        $sql_extrato
					FROM tmp_interv_$login_admin X
					JOIN tbl_os ON tbl_os.os = X.os
					JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
					JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
					JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_admin         ON  tbl_admin.admin           = tbl_os.admin
					$join_extrato
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.excluida IS NOT TRUE /* hd 52463 */
					$Xos
					$where_extrato
					";
				if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
					$sqly .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final' ";
				}
					$sqly .= " ORDER BY status_data2 DESC ";
			}
// 		echo nl2br($sqly);
		//echo $sqly;
		$res = pg_exec($con, $sqly);

		if( pg_numrows($res)>0 ){

			echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

			echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
			echo "<input type='hidden' name='data_final'     value='$data_final'>";
			echo "<input type='hidden' name='aprova'         value='$aprova'>";

			echo "<table class='table table-striped table-bordered table-large' id='tabela_exclusao'>";
			echo "<thead><tr class=\"titulo_coluna\">";
			echo "<th><a style='cursor: hand;color: white;' href='#' onclick='javascript: checkaTodos()'>Todas</a></th>";
			echo "<th>OS</th>";
			echo "<th>Data Digitação</th>";
			echo "<th>Data Abertura</th>";
			if(in_array("extrato",$debito)){
                echo "<th>Data Extrato</th>";
                echo "<th>Nº Extrato</th>";
			}
			echo "<th>Posto</th>";
			echo "<th>Produto</th>";
			echo "<th>Descrição</th>";
			if($login_fabrica == 1)
				echo "<th>Débito</th>";
			if($login_fabrica != 1)
				echo "<th>Admin</th>";
			elseif($aprovacao != 110){
				echo "<th>Responsável<br>Solicitação</th>";
				if($aprovacao == 111)
					echo "<th>Responsável<br>Aprovação</th>";
				else
					echo "<th>Responsável<br>Reprovação</th>";
			}else
				echo "<th>Responsável<br>Solicitação</th>";
			if($login_fabrica == 1){
                echo "<th>Data<br>Exclusão</th>";
			}

			#echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Obsevação</B></font></td>";
			echo "</tr></thead>";

			$cores            = '';
			$qtde_intervencao = 0;

			for( $x=0; $x<pg_numrows($res); $x++ ){
				$os						= pg_result($res, $x, os);
				$sua_os					= pg_result($res, $x, sua_os);
				$codigo_posto			= pg_result($res, $x, codigo_posto);
				$posto_nome				= pg_result($res, $x, posto_nome);
				$consumidor_nome		= pg_result($res, $x, consumidor_nome);
				$produto_referencia		= pg_result($res, $x, produto_referencia);
				$produto_descricao		= pg_result($res, $x, produto_descricao);
				$produto_serie			= pg_result($res, $x, produto_serie);
				$produto_voltagem		= pg_result($res, $x, voltagem);
				$data_digitacao			= pg_result($res, $x, data_digitacao);
                $data_abertura          = pg_result($res, $x, data_abertura);
                $os_extrato             = pg_result($res, $x, extrato);
				$data_extrato			= pg_result($res, $x, data_extrato);
				$status_os				= pg_result($res, $x, status_os);
				$status_observacao		= pg_result($res, $x, status_observacao);
				$status_descricao		= pg_result($res, $x, status_descricao);
				$admin_exclusao			= pg_result($res, $x, admin_exclusao);
				$status_data			= pg_result($res, $x, status_data);
				$admin_nome				= pg_result($res, $x, admin_nome);

				if($login_fabrica == 1){
                    $sql2 = "
                            SELECT  tbl_os_item.parametros_adicionais   ,
                                    tbl_os_item.qtde                    ,
                                    tbl_os_item.custo_peca
                            FROM    tbl_os_item
                            JOIN    tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                            WHERE   tbl_os_produto.os = $os";
                    $res2 = pg_query($con,$sql2);
                    $total_debito = 0;
                    if(pg_num_rows($res2) > 0){
                        for($j = 0; $j < pg_num_rows($res2); $j++){
                            $qtde            = pg_fetch_result($res2, $j, 'qtde');
                            $custo_peca      = pg_fetch_result($res2, $j, 'custo_peca');
                            $parametros_adicionais = pg_fetch_result($res2, $j, 'parametros_adicionais');
                            $parametros_adicionais = json_decode($parametros_adicionais,true);

                            $opcao_os = ($parametros_adicionais['debito_peca'] == "t") ? "debito_peca" : "";
                            $opcao_os = ($parametros_adicionais['coleta_peca'] == "t") ? "coleta_peca" : $opcao_os;

                            if($opcao_os == "debito_peca"){
                                $total_debito += number_format($custo_peca * $qtde,2);
                            }
                        }

                        if((($aprova == "aprovacao" && in_array("solicitado",$debito)) || ($aprova == "aprovadas" && in_array("gerado",$debito))) && $total_debito == 0){
                            continue;
                        }
                    }else{

                        if((($aprova == "aprovacao" && in_array("solicitado",$debito)) || ($aprova == "aprovadas" && in_array("gerado",$debito))) && (pg_num_rows($res2) == 0)){
                            continue;
                        }
                    }

                    if(in_array("extrato",$debito)){
                        $sql_extrato = "SELECT  DISTINCT
                                                tbl_extrato.protocolo,
                                                to_char(tbl_extrato.data_geracao, 'dd/mm/yyyy') AS data_extrato
                                        FROM    tbl_extrato
                                        JOIN    tbl_extrato_lancamento USING (extrato)
                                        WHERE   tbl_extrato_lancamento.fabrica      = $login_fabrica
                                        AND     tbl_extrato_lancamento.lancamento   = 42
                                        AND     tbl_extrato_lancamento.historico    LIKE '%$sua_os%'
                        ";
                        $res_extrato = pg_query($con,$sql_extrato);

                        $data_extrato   = pg_fetch_result($res_extrato,0,data_extrato);
                        $os_extrato     = pg_fetch_result($res_extrato,0,protocolo);

                        if(strlen($os_extrato) == 0){
                            continue;
                        }
                    }
                }

				$cores++;
				$cor = ($cores % 2 == 0) ? "#F7F5F0": '#F1F4FA';

				echo "<tr bgcolor='$cor' id='linha_$x'>";
				echo "<td align='center' width='0'>";

				if( $status_os == 110 )
				{
					echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','linha_aux_$x','$cor');\" ";
					if( strlen($msg_erro)>0 ){
						if( strlen($_POST["check_".$x])>0 ){
							echo " CHECKED ";
						}
					}
					echo ">";
				}

				echo "</td>";
				echo "<td style='font-size: 9px; font-family: verdana' nowrap >";
					if($aprova=="aprovadas"){
						echo "$sua_os";
					}else{
						echo "<input type='hidden' name='sua_os_{$x}' value='$sua_os'>";
						echo "<a href='os_press.php?os=$os' target='_blank'>$sua_os</a>";
					}
				echo "</td>";
				echo "<td>".$data_digitacao. "</td>";
				echo "<td>".$data_abertura. "</td>";
				if(in_array("extrato",$debito)){
                    echo "<td>".$data_extrato. "</td>";
                    echo "<td>".$os_extrato. "</td>";
				}
				echo "<td align='left' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";
				echo "<td align='left' nowrap><acronym title='Produto: $produto_referencia - ' style='cursor: help'>". $produto_referencia ."</acronym></td>";
				echo "<td align='left' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";
                if($login_fabrica == 1){
					echo "<td align='rigth'>".number_format($total_debito,2,',','.')."</td>";
				}

				if($login_fabrica != 1){
					echo "<td nowrap>";
						echo "<acronym title='Data da solicitação da exclusão: ".$status_data."'>".$admin_exclusao."</acronym>";
					echo "</td>";
				}else{
					$sql_admin = "  SELECT  tbl_admin.admin,
                                            tbl_admin.login
                                    FROM    tbl_os_status
                                    JOIN    tbl_admin USING(admin)
                                    WHERE   os          = $os
                                    AND     status_os   = 110";
					$res_admin = pg_exec($con,$sql_admin);

					if(pg_numrows($res_admin)>0){
						$admin_solicitou = pg_fetch_result($res_admin, 0, 'login');
						$data_exclusao = pg_fetch_result($res_admin, 0, 'data_exclusao');
						if(strlen($admin_solicitou) > 0){
							echo "<td align='left' nowrap>".ucfirst($admin_solicitou)."</td>";
						}

						if( $aprovacao != 110 ){
							$sql_admin_aut = "  SELECT  tbl_admin.admin,
                                                        tbl_admin.login
                                                FROM    tbl_os_status
                                                JOIN    tbl_admin USING(admin)
                                                WHERE   os          = $os
                                                AND     status_os   = $aprovacao";
							$res_admin_aut = pg_exec($con, $sql_admin_aut);
                            $data_exclusao2 = pg_fetch_result($res_admin_aut, 0, 'data_exclusao');

							echo "<td align='left' nowrap>";

							if(pg_numrows($res_admin_aut)>0){
								$admin_aut = pg_fetch_result($res_admin_aut, 0, 'login');
								echo ucfirst($admin_aut);
							}elseif ($aprovacao == 111){
								$sqlXX = "SELECT automatico from tbl_os_status where os=$os and status_os = 111 and automatico is true";
								$resXX = pg_query($con,$sqlXX);

								if (pg_num_rows($resXX)>0 and pg_fetch_result($resXX, 0, 0) == 't'){
									echo "Excluída Automaticamente";
								}
							}
                            echo "&nbsp;</td>";
						}
					}
					echo "<td>";
                    echo "$status_data";
                    echo "&nbsp;</td>";
				}

				echo "</tr>";
				echo "<tr bgcolor='$cor' id='linha_aux_$x'>";
				echo "<td>";
				echo "</td>";

				if(in_array("extrato",$debito)){
                    $colspan = 10;
                }else{
                    $colspan = 9;
                }
				if( $aprovacao != 110 and $login_fabrica == 1 )
					$colspan+= 2;

				echo "<td align='left' colspan='$colspan'><acronym title='Data da solicitação da exclusão: ".$status_data."'><b>Obs: </b>". nl2br($status_observacao) . "</acronym></td>";
				echo "</tr>";
			}

			echo "<input type='hidden' name='qtde_os' value='$x'>";
			echo "<tr>"; $colspan+= 1;
			echo "<td class='subtitulo' colspan='$colspan' class='subtitulo' style='text-align:left;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

			if( trim($aprova) == 'aprovacao' )
			{
				echo "<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'>";
				echo "&nbsp; Com Marcados:&nbsp;<select name='select_acao' size='1' class='frm' >";
				echo "<option value=''></option>";
				echo "<option value='111'";  if ($_POST["select_acao"] == "111")  echo " selected"; echo ">APROVAR E EXCLUIR</option>";
				echo "<option value='112'";  if ($_POST["select_acao"] == "112")  echo " selected"; echo ">REPROVAR EXCLUSÃO</option>";
				echo "</select>";
				echo "&nbsp;&nbsp; Motivo:<input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value='' >";
				echo "&nbsp;&nbsp;&nbsp;&nbsp;<input type='button' class='btn btn-primary' value='Gravar' id='btn_gravar' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'>";
			}else
				echo "</td>";
			echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
			#echo "<input type='hidden' name='btn_acao' value='$os_os'>";
			echo "</table>";
			echo "</form>";
		}else{
			echo "<div class='container alert alert-warning'><h4>Não foram encontrados resultados para esta pesquisa</h4></div>";
		}
		$msg_erro = '';
	}
}//fim do else (validaçao das datas)
?>
<?php
include "rodape.php";
?>
