<?
	if ($login_fabrica == 42) {
		if ($banner_rodape == true) {
			$style_makita = "style='margin-top: 350px;'";
		}
	}

	if (function_exists('getmicrotime')) {
		$micro_time_end = getmicrotime();
		$time = round($micro_time_end - $micro_time_start, 4);
		$rodape_Ano = date('Y');
	}

    // envia email para o suporte em caso de erro
    // Desativadoç: não está enviando a mensagem há anos...
	if (false and strlen(trim($msg_erro))>0 and strpos(strtoupper($msg_erro), "ERROR") > 0) {
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
						tbl_posto.email
				FROM tbl_posto_fabrica
				JOIN tbl_posto using(posto)
				WHERE fabrica = $login_fabrica
				AND tbl_posto_fabrica.posto = $login_posto";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) > 0) {
			$codigo_do_posto = pg_result($res,0,codigo_posto);
			$email_do_posto  = pg_result($res,0,email);
		}

		$remetente    = "ERRO NO SITE - TELECONTROL <telecontrol@telecontrol.com.br>";
		$destinatario = "helpdesk@telecontrol.com.br";
		$assunto      = "ERRO NA PÁGINA";
		$mensagem     = "OCORREU O SEGUINTE ERRO: <BR><BR>
            <strong>Programa</strong>: $PHP_SELF<BR>
            <strong>Fabrica</strong>: $login_fabrica<BR>
            <strong>Posto</strong>: $login_posto<BR>
            <strong>Login</strong>: $codigo_do_posto<BR>
            <strong>Email</strong>: $email_do_posto<BR>
            <h3>ERRO:</h3><pre color='#FF0000'>$msg_erro</pre>";
		$headers="Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";

		//Sono - retirei, não estamos acompanhando estes emails e está enchendo a caixa postal do suporte
		//@mail($destinatario,$assunto,$mensagem,$headers);
	}

	#------ Envia email se o programa demorar para terminar de rodar -------#
	/**
	 * Retirado em 27/07/2012
	 */
	/*
	if (round ($time,0) > 60) {
	  $programa_lento = $_SERVER['PHP_SELF'] ;
	  mail ("waldir@telecontrol.com.br" , "Programa Lento" , $programa_lento . "\n Demorou " . round ($time,0) . " segundos \n" . $_SERVER ['QUERY_STRING'] );
	}*/

    $color = ($_serverEnvironment == 'development') ?'#E0123F' : '#ffffff';

?>
<style>

	.footer {
		background-color: <?=$color;?>;
		height: 55px;
		width: 100%;
		overflow-y: hidden;
		padding: 3px;
		position: fixed;
		bottom: 0;
		z-index: 110;
	}

	body {margin-bottom: 50px!important}

	.footer .table .info_footer {
		display: none;
	}

	.footer .table .info_footer a {
		color: #001dff;
	}

	.footer .table div.tal {
		text-align: left;
	}

	.footer .table div.tar {
		text-align: right;
	}

	.footer .foot {
		display: inline-block;
		width: 48%;
	}

	.footer .foot h6 {
		font-size: 11px !important;
		text-align: middle;
	}

	.footer .table div.provedor {
		color: #bdbdbd;
	}

	.footer:hover .table .info_footer {
		display: block;
	}

	.footer:hover .table .copy_footer {
		display: none;
	}

	#adsense {
		border-top: 1px solid #eeeeee;
		background-color: #f9f9f9;
		padding: 20px;
	}

	.footer #teste {
		position: absolute;
		color: #FFFFFF;
		font-weight: bold;
		width: 100%;
		font-size: 18pt;
	}

	#footer_clean{
		margin-bottom: 50px;
		display: block;
		clear:both;
	}

</style>

<?php if($versaoCabecalho != "new"){ ?>

<style>
	body, html, table{
		font-family: arial !important;
	}
	table tr td{
		padding: 2px;
		font-family: arial !important;
	}
    .frm, .frm-on, input[type="text"], select, textarea, fieldset{
        padding: 4px !important;
        border-radius: 2px !important;
        border: 1px solid #888888 !important;
    }
    input[type="button"], input[type="submit"], button{
        padding: 3px 10px !important;
    }
    .titulo_tabela, .subtitulo{
        margin-top: 4px !important;
        margin-bottom: 4px  !important;
        padding: 4px !important;
    }
    .datepick-month-year{
    	height: 1.4em !important;
    	padding: 0px !important;
    }
    .message > .titulo{
    	padding: 10px !important;
    	font-size: 18px !important;
    	margin-bottom: 14px;
    }
</style>

<?php } ?>

<div id="footer_clean">
<br>
</div>
<?php

//include "log_final.php";
//A variável $tira_adSense vem dos parâmetros adicionais da tbl_fabrica se for TRUE não mostra as propagandas
if (TELA_MENU and !$tira_adSense) {
	#echo "Fábrica $login_fabrica não pediu para retirar os comerciais do Google...<br />";
?>

<div id="adsense">
	<script type="text/javascript"><!--
	google_ad_client = "ca-pub-1744878058140448";
	/* Primeiro */
	google_ad_slot = "9752169639";
	google_ad_width = 468;
	google_ad_height = 60;
	//-->
	</script>

	<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
	<ins class="adsbygoogle"
	     style="display:inline-block;width:728px;height:90px"
	     data-ad-client="ca-pub-1744878058140448"
	     data-ad-slot="9114777631"></ins>
		<script>
	(adsbygoogle = window.adsbygoogle || []).push({});
	</script>

	<script type="text/javascript"
	src="https://pagead2.googlesyndication.com/pagead/show_ads.js">
	</script>
</div>
	<? } ?>

<script type="text/javascript">
if (document.getElementById('div_carregando') != null) document.getElementById('div_carregando').style.display='none';
</script>
</div>
<div class="footer" align="center" <?=$style_makita?>>
	<div class="table">
			<div class="copy_footer">
			<?php
				if($_serverEnvironment == 'development') {
			 ?>
				<div id="teste">AMBIENTE DE TESTES</div>
			<?php } ?>
				<div class="foot tal">
					<h6>© <? echo date("Y"); ?> Telecontrol. Todos os Direitos Reservados.</h6>
				</div>
				<div class="foot tar">
					<h6><div class="provedor">Deus é o Provedor</div></h6>
				</div>
			</div>
			<div class="info_footer">
				<?php if($login_fabrica <> 87){?>

					<div class="foot tal">
						<h6>
							Telecontrol Networking Ltda &ndash; <? echo date("Y"); ?>

							<?php

				    		$script_php = $_SERVER["SCRIPT_FILENAME"];
				    		$ls = exec('ls -l ' . $script_php . ' | awk \'{print $8" "$6" "$7}\'');

				    ?>

				    	Uso de CPU: <?=$time?> segundos : <?=$ls?> <br>
						Dados do seu navegador: <?=$HTTP_USER_AGENT?>
						</h6>
					</div>
					<div class="foot tar">

					<?php

						if($dominio == 'posvenda.telecontrol.com.br'){
				            $PUBLIC_HOSTNAME = `wget -q -O - http://169.254.169.254/latest/meta-data/public-hostname`;
				    	    echo "Webserver: <a href='http://$PUBLIC_HOSTNAME/munin' target='_blank'>$PUBLIC_HOSTNAME</a>";
					    }
					?>

						<h6><a  href="http://www.telecontrol.com.br" target="_blank">www.telecontrol.com.br</a><br><div class="provedor">Deus é o Provedor<div></h6>
					</div>
			</div>
		<? } ?>
	</div>
</div>

<?php // FormToken - Implementação em Javascript para adicionar em todo form da tela um input hidden com o token gerado no php ?>
<script type="text/javascript">

	function detectIE() {
	  var ua = window.navigator.userAgent;

	  var msie = ua.indexOf('MSIE ');
	  if (msie > 0) {
	    return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
	  }

	  var trident = ua.indexOf('Trident/');
	  if (trident > 0) {
	    var rv = ua.indexOf('rv:');
	    return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
	  }

	  var edge = ua.indexOf('Edge/');
	  if (edge > 0) {
	    return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10);
	  }

	  return false;
	}

	var version = detectIE();
	var browser;

	if (version === false) {
	  browser = "another";
	} else if (version >= 12) {
	  browser = "edge";
	} else {
	  browser = "IE";
	}

    var token = "<?=TOKEN?>";
    /*HD - 4125402*/
    var formsToken = $("form");

    if (formsToken.length > 0) {
        for (var i =0, len = formsToken.length; i < len; i++) {
        	if (browser != "edge" && browser != "IE") {
            	formsToken[i].append(makeTokenInputHidden());
            }
        }
    }

    function makeTokenInputHidden() {
        var input = document.createElement("input");
        input.setAttribute("type","hidden");
        input.setAttribute("name","token_form");
        input.setAttribute("class","token_form");
        input.value = token;

        return input;
	}
	
	if (typeof submitForm == "undefined") {
		function submitForm (form, valor) {
			if(valor == undefined){
				valor = "submit";
			}

			var btn = $(form).find("#btn_click");

			if ($(btn).val().length > 0) {
				alert("Aguarde Submissão...");
			} else {
				$(btn).val(valor);
				$(form).submit();
			}
		}
	}
</script>
<?php

//Ebano: encerrando a conexão para que não fique "idle" no sistema, consumindo recursos
@pg_close($con);

?>
</body>
</html>
