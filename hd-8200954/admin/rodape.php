<?php 
	$urlToShow = [
			"menu_cadastro.php",
			"menu_gerencia.php",
			"menu_callcenter.php",
			"menu_tecnica.php",
			"menu_financeiro.php",
			"menu_auditoria.php",
		];

if(!in_array($login_fabrica, [1,30,169,180,181,182])){ 
?>
	<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css">
	<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js"></script>
	<?php
	$sql = "SELECT email_check, email, celular FROM tbl_email_check WHERE admin = $1";
	$res = pg_query_params($con, $sql, [$login_admin]);
	$resultado = pg_fetch_all($res);foreach ($urlToShow as $url) {
		if(strstr($thisUrl, $url)){
			?>
			<script type="text/javascript">
				var modal = null;
				function showComunicadoCovid(){
					modal = Shadowbox.open({
						content: "<div><img src='imagens/covid19.jpg'></div>",
						player: "html",
						width: 900,
						height: 900,
						options: {
							onClose: function(){				        	
								var today = new Date();
								today = today.getDate();
								var checkLocal = localStorage.setItem("modalCovidTc",today);
							}		
						}
					 });
				 }

				$(function(){				
					Shadowbox.init();
					$(document).on("ShadowboxInit", function() {					
						 var today = new Date();
						 today = today.getDate();

						 var checkLocal = localStorage.getItem("modalCovidTc");
						 if(checkLocal == null){
							showComunicadoCovid();
						 }else{
							if(checkLocal != today){
								showComunicadoCovid();
							}						
						 }
					});
				});
			</script>
			<?php
			break;
		}
	}
	


	$thisUrl = $_SERVER['PHP_SELF'];

	if(pg_num_rows($res) == 0 || strlen($resultado[0]['email']) == 0 || strlen($resultado[0]['celular']) == 0 ){

		foreach ($urlToShow as $url) {
			if(strstr($thisUrl, $url)){
				?>
				<script type="text/javascript">
					var modal = null;
					function showModal(){
						modal = Shadowbox.open({
					        content: "modal_novo_email.php",
					        player: "iframe",
					        title: "Validação de Informações",
					        width: 1000,
					        height: 600,
					        options: {
						        onClose: function(){				        	
						        	var today = new Date();
									today = today.getDate();
									var checkLocal = localStorage.setItem("modalCheckInfo",today);
						        }	
					        }
					        
					    });	
					}

					$(function(){				
						Shadowbox.init();				

						$(document).on("ShadowboxInit", function() {					
				           		 var today = new Date();
							 today = today.getDate();

							 var checkLocal = localStorage.getItem("modalCheckInfo");
							 if(checkLocal == null){
								showModal();
							 }else{
								if(checkLocal != today){
									showModal();
								}						
							 }
				        	});
					});
				</script>
				<?php
				break;
			}
		}
	}
}

if(strtotime(date('Y-m-d')) <= strtotime('2020-05-25')){
	foreach ($urlToShow as $url) {
		if(strstr($thisUrl, $url)){
			?>
			<script type="text/javascript">
				var modal = null;
				function showComunicadoFeriado(){
					modal = Shadowbox.open({
					content: "<div class='alert alert-warning' style='text-align:left'><center><h2><b>Comunicado</b></h2></center><br>A Telecontrol comunica que devido a antecipação do feriado Estadual de 09 de Julho não haverá expediente no dia 25 de Maio. <br>Voltaremos às nossas atividades normalmente no dia 26 de Maio a partir das 08:00hs.</b></div>",
						player: "html",
						width: 600,
						height: 180,
						options: {
							onClose: function(){				        	
								var today = new Date();
								today = today.getDate();
								var checkLocal = localStorage.setItem("modalFeriadoTc",today);
							}		
						}
					 });
				 }

				$(function(){				
					Shadowbox.init();
					$(document).on("ShadowboxInit", function() {					
						 var today = new Date();
						 today = today.getDate();

						 var checkLocal = localStorage.getItem("modalFeriadoTc");
						 if(checkLocal == null){
							showComunicadoFeriado();
						 }else{
							if(checkLocal != today){
								showComunicadoFeriado();
							}						
						 }
					});
				});
			</script>
			<?php
			break;
		}
	}
}

if(strtotime(date('Y-m-d')) <= strtotime('2020-04-22')){

	foreach ($urlToShow as $url) {
		if(strstr($thisUrl, $url)){
			?>
			<script type="text/javascript">
				var modal = null;
				function showComunicadoCovid(){
					modal = Shadowbox.open({
						content: "<div><img src='imagens/covid19.jpg'></div>",
						player: "html",
						width: 900,
						height: 900,
						options: {
							onClose: function(){				        	
								var today = new Date();
								today = today.getDate();
								var checkLocal = localStorage.setItem("modalCovidTc",today);
							}		
						}
					 });
				 }

				$(function(){				
					Shadowbox.init();
					$(document).on("ShadowboxInit", function() {					
						 var today = new Date();
						 today = today.getDate();

						 var checkLocal = localStorage.getItem("modalCovidTc");
						 if(checkLocal == null){
							showComunicadoCovid();
						 }else{
							if(checkLocal != today){
								showComunicadoCovid();
							}						
						 }
					});
				});
			</script>
			<?php
			break;
		}
	}
}
?>


<div class="container">
<?
#------------- Programa Restrito ------------------#
?>
<div id="footer" style="clear:both; width:100%" align="center"  class="no-print">
<table width='100%' border='0' align='center'  class='no-print'>
	<tr>
		<td align='left'>
<?
$sql = "SELECT login
          FROM tbl_programa_restrito
          JOIN tbl_admin USING (admin, fabrica)
         WHERE programa = '{$_SERVER['PHP_SELF']}'
           AND fabrica  = $login_fabrica";
$res = pg_query($con, $sql);

if(is_resource($res)) {
	if (pg_num_rows($res) > 0) {
		echo "<small>Programa restrito aos seguintes usuários: ";
        echo "<strong>" . implode(', ', array_column(pg_fetch_all($res), 'login')) . "</strong></small>";
	}else{
		echo "<small>Programa sem restrição</small>";
	}
}
echo "</td>";

echo "<td style='text-align: right;'>";
	echo "<a target='_blank' href='programa_restrito.php?programa=$PHP_SELF'><font size='-2'>Restringir Programa </font></a>";

echo "</td>";

echo "</tr>";
echo "</table>";

$script_php = $_SERVER["SCRIPT_FILENAME"];
$ls = exec('ls -l ' . $script_php . ' | awk \'{print $8" "$6" "$7}\'');

?>

<div style="border-top: 1px solid #ccc; margin-top: 10px; margin-bottom: 10px;"></div>

<div id="footer" style="clear:both; width:100%"  class="no-print">

	Telecontrol Networking Ltda - <? echo date("Y"); ?><br>
	<a  href="http://www.telecontrol.com.br" target="_blank">www.telecontrol.com.br</a><br>

<?
	if (function_exists('getmicrotime')):
		$micro_time_end = getmicrotime();
		echo "<font face='arial' size='-2'> CPU : ";

		$time = $micro_time_end - $micro_time_start;

		echo round($time,4) . " segundos ";
		echo "</font>";
	endif;

	if ($dominio == "posvenda.telecontrol.com.br"){
    	    $PUBLIC_HOSTNAME = `wget -q -O - http://169.254.169.254/latest/meta-data/public-hostname`;
    	    echo "<br>webserver: <a href='http://$PUBLIC_HOSTNAME/munin' target='_blank'>$PUBLIC_HOSTNAME</a>";
    }

    echo ' :: ' . $ls;

	//Removido apenas para Roca (Solicitação do Cliente e Ronaldo autorizou remover)
	echo ($login_fabrica != 178) ? "<br>Deus &eacute; o Provedor.<br><br>" : "";
	
?>

</div>



<?
    //A variável $tira_adSense vem dos parâmetros adicionais da tbl_fabrica se for TRUE não mostra as propagandas
    if (TELA_MENU and !$tira_adSense) { ?>
<script type="text/javascript"><!--
google_ad_client = "ca-pub-1744878058140448";
/* Primeiro */
google_ad_slot = "9752169639";
google_ad_width = 468;
google_ad_height = 60;
//-->
</script>
<script type="text/javascript"
src="https://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
<?}?>
<!--- <script type='text/javascript' src='js/rotinas/check_online_admin.js' ></script> -->
</body>
</html>
<? include "monitora_rodape.php";?>
<?php @pg_close($con); ?>
</div>


<!-- FormToken - Implementação em Javascript para adicionar em todo form da tela um input hidden com o token gerado no php -->
<script type="text/javascript">
    var token = "<?=TOKEN?>";
	var formsToken = document.getElementsByTagName("form");
	
	if (formsToken.length > 0) {
		for (var i =0,len = formsToken.length; i < len; i++) {
			$(formsToken[i]).append(makeTokenInputHidden());
		}
	}


	function makeTokenInputHidden() {
		var input=  document.createElement("input");	
		input.setAttribute("type","hidden");
		input.setAttribute("name","token_form");
		input.setAttribute("class","token_form");
		input.value = token;

		return input;
	}
</script>




