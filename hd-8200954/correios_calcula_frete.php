<?
/*

<form action="/encomendas/prazo/prazo.cfm" method="post" name="formulario" 
<input type="Hidden" name="resposta" value="paginaCorreios">

<select name="servico" onChange="VerificaServico(document.formulario);">
<option value="">>>Selecione<<</option>
<option value="41106">PAC</option>
<option value="40010">SEDEX</option>
<option value="40215">SEDEX 10</option>
<option value="40045">SEDEX a Cobrar</option>										
<option value="40290">SEDEX HOJE</option>
<option value="81019">e-SEDEX</option>
<option value="44105">MALOTE</option>
</select>

<input type="Text" maxlength="9" onKeyPress="formataCEP('CEP', window.event.keyCode, document.formulario.cepOrigem);pulaCampo(document.formulario.cepOrigem,document.formulario.cepDestino,9);" name="cepOrigem" size="10" value="">

<input type="Text" maxlength="9" onKeyPress="formataCEP('CEP', window.event.keyCode, document.formulario.cepDestino);" name="cepDestino" size="10" value="">						


Peso estimado:<br>
<select name="peso" size="1">
<option selected value=""></option>
<option value="0.3">0.300</option>
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="4">4</option>
<option value="5">5</option>
<option value="6">6</option>
<option value="7">7</option>
<option value="8">8</option>
<option value="9">9</option>
<option value="10">10</option>
<option value="11">11</option>
<option value="12">12</option>
<option value="13">13</option>
<option value="14">14</option>
<option value="15">15</option>
<option value="16">16</option>
<option value="17">17</option>
<option value="18">18</option>
<option value="19">19</option>
<option value="20">20</option>
<option value="21">21</option>
<option value="22">22</option>
<option value="23">23</option>
<option value="24">24</option>
<option value="25">25</option>
<option value="26">26</option>
<option value="27">27</option>
<option value="28">28</option>
<option value="29">29</option>
<option value="30">30</option>
</select> Kg					


M&atilde;o Pr&oacute;pria<br>
<select name="MaoPropria">
<option value="S">Sim</option>
<option value="N" selected>N&atilde;o</option>										
</select>


Aviso de Recebimento<br>
<select name="avisoRecebimento">
<option value="S">Sim</option>
<option value="N" selected>N&atilde;o</option>
</select>

*/

$url = "www.correios.com.br";

$ip = gethostbyname($url);
echo $ip;
$fp = fsockopen($ip, 80, $errno, $errstr, 10);

$servico     = "40010";
$cep_origem  = "02054-100";
$cep_destino = "17519-255";
$peso        = "8";

$saida  = "GET /encomendas/precos/calculo.cfm?servico=$servico&CepOrigem=$cep_origem&CepDestino=$cep_destino&Peso=$peso HTTP/1.1\r\n";
$saida .= "Host: www.correios.com.br\r\n";
$saida .= "Connection: Close\r\n\r\n";

fwrite($fp, $saida);

$resposta = "";
while (!feof($fp)) {
	$resposta .= fgets($fp, 128);
}
fclose($fp);
echo htmlspecialchars ($resposta);

echo "<hr>";
$posicao = strpos ($resposta,"Tarifa=");
$tarifa  = substr ($resposta,$posicao+7);
$posicao = strpos ($tarifa,"&");
$tarifa  = substr ($tarifa,0,$posicao);
echo $tarifa;


?>