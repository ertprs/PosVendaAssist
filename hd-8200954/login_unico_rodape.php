<? include "rodape.php"; exit;?>
<br>
<div id="footerwrap">
<!--
Visualize esta p�gina <a href="/mobile/personalized/promo.html">no seu telefone celular</a> quando voc� quiser.
<a href="#privacidade.php">Pol�tica de privacidade</a> - 
<a href="#sla.php">N�veis de qualidade do Servi�o (SLA)</a> - 
<a href="#contato.php">Entre em Contato</a> - 
<a href="#equipe.php">Equipe da Telecontrol</a>
-->
�<? echo date("Y"); ?>&nbsp; &ndash; &nbsp;Telecontrol&nbsp;&nbsp;&ndash;&nbsp;&nbsp;<span style="color:#6A8DAC">Deus � o provedor</span>
<script>document.getElementById('div_carregando').style.visibility = 'hidden';</script>
<?
	if (function_exists('getmicrotime')) {
    	$micro_time_end = getmicrotime();
		$time = round($micro_time_end - $micro_time_start, 4);
		$rodape_Ano = date('Y');
	}
	if($login_fabrica <> 87){?>
<hr>
	<div>
		<a  href="http://www.telecontrol.com.br" target="_blank">www.telecontrol.com.br</a><br>
		Dados do seu navegador:<br /><?=$HTTP_USER_AGENT?><br />
<?	}?>
	</div>
</div>
</body>
</html>
