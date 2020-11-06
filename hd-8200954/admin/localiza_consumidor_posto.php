<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
?>


<script language="JavaScript">
var http_forn = new Array();

function verifica_atendimento(tipo_atendimento) {
	/*Verificacao para existencia de componente - HD 22891 */
	if (document.getElementById('div_mapa')){
		var ref = document.getElementById(tipo_atendimento).value;
		url = "<?=$PHP_SELF?>?ajax=tipo_atendimento&id="+ref;
		var curDateTime = new Date();
		http_forn[curDateTime] = createRequestObject();
		http_forn[curDateTime].open('GET',url,true);
		http_forn[curDateTime].onreadystatechange = function(){
			if (http_forn[curDateTime].readyState == 4)
			{
				if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
				{
					var response = http_forn[curDateTime].responseText.split("|");
					if (response[0]=="ok"){
						document.getElementById('div_mapa').style.visibility = "visible";
						document.getElementById('div_mapa').style.position = 'static';
					}else{
						document.getElementById('div_mapa').style.visibility = "hidden";
						document.getElementById('div_mapa').style.position = 'absolute';
					}
				}
			}
		}
		http_forn[curDateTime].send(null);
	}
}
</script>


<?
$posto = 6359;
//--== Calculo de Distância com Google MAPS =========================================
$sql_posto = "SELECT contato_endereco AS endereco,
					 contato_numero   AS numero  ,
					 contato_bairro   AS bairro  ,
					 contato_cidade   AS cidade  ,
					 contato_estado   AS estado  ,
					 contato_cep      AS cep
				FROM tbl_posto_fabrica
				WHERE posto   = $posto
				AND   fabrica = $login_fabrica ";

$res_posto = pg_exec($con,$sql_posto);
if(pg_numrows($res_posto)>0) {
	$endereco_posto = pg_result($res_posto,0,endereco).', '.pg_result($res_posto,0,numero).' '.pg_result($res_posto,0,bairro).' '.pg_result($res_posto,0,cidade).' '.pg_result($res_posto,0,estado);
	if(strlen($distancia_km)==0)$distancia_km=0;

	//hd 40389
	$cep_posto = pg_result($res_posto,0,cep);
}

if(strlen($tipo_atendimento)>0){
	$sql = "SELECT tipo_atendimento,km_google
			FROM tbl_tipo_atendimento
			WHERE tipo_atendimento = $tipo_atendimento";
	$resa = pg_exec($con,$sql);
	if(pg_numrows($resa)>0){
		$km_google = pg_result($resa,0,km_google);
	}
}
//HD 406478 - MLG - API-Key para o domínio telecontrol.NET.br
//HD 678667 - MLG - Adicionar mais uma Key. Alterado para um include que gerencia as chaves.
include '../gMapsKeys.inc';
?>

CEP POSTO<INPUT TYPE="text" NAME="cep_posto" id="cep_posto"><br>
CEP CONSUMIDOR<INPUT TYPE="text" NAME="consumidor_cep" id="consumidor_cep"><br>
ENDEREÇO POSTO<input type="text" id="ponto1"><BR>
CONSUMIDOR ENDEREÇO<input type="text" id="consumidor_endereco"><BR>
consumidor_numero<input type="text" id="consumidor_numero"><BR>
consumidor_bairro<input type="text" id="consumidor_bairro"><BR>
consumidor_cidade<input type="text" id="consumidor_cidade"><BR>
consumidor_estado<input type="text" id="consumidor_estado">

<div id="mapa2" style=" width:500px; height:10px;visibility:hidden;position:absolute; ">
<a href='javascript:escondermapa();'>Fechar Mapa</a>
</div><br>
<div id="mapa" style=" width:500px; height:300px;visibility:hidden;position:absolute;border: 1px #FF0000 solid; "></div>
<script src="http://maps.google.com/maps?file=api&v=2&key=<?=$gAPI_key?>" type="text/javascript"></script>
<script language="javascript">

function formatar(src, mask){
  var i = src.value.length;
  var saida = mask.substring(0,1);
  var texto = mask.substring(i)
if (texto.substring(0,1) != saida)
  {
    src.value += texto.substring(0,1);
  }
}


var map;
function initialize(busca_por){
	// Carrega o Google Maps
	if (GBrowserIsCompatible()) {
		map = new GMap2(document.getElementById("mapa"));
		map.setCenter(new GLatLng(-25.429722,-49.271944), 11)

		// Cria o objeto de roteamento
		 var dir = new GDirections(map);

		//hd 40389
		var pt1 = document.getElementById("cep_posto").value;
		var pt2 = document.getElementById("consumidor_cep").value;

		pt1 = pt1.replace('-','');
		pt2 = pt2.replace('-','');

		if (pt1.length != 8 || pt2.length !=8) {
			//alert ('CEP inválido');
			busca_por = 'endereco';
		}else{
			pt1 = pt1.substr(0,5) + '-' + pt1.substr(5,3);
			pt2 = pt2.substr(0,5) + '-' + pt2.substr(5,3);
		}

		//alert (pt1);
		//alert (pt2);

		if (busca_por == 'endereco'){
			var pt1 = document.getElementById("ponto1").value
			var pt2 = document.getElementById("consumidor_endereco").value + ", "+
			document.getElementById("consumidor_numero").value + " " + document.getElementById("consumidor_bairro").value + " " + document.getElementById("consumidor_cidade").value + " " + document.getElementById("consumidor_estado").value;
		}

		 // Carrega os pontos dados os endereços
		dir.loadFromWaypoints([pt1,pt2], {locale:"pt-br", getSteps:true});
		// O evento load do GDirections é executado quando chega o resultado do geocoding.
		//gdir.load("from: " + fromAddress + " to: " + toAddress, { "locale": locale });

		//alert('INICIANDO..	');
		GEvent.addListener(dir,"load", function() {
			//alert('entrou...');
			for (var i=0; i<dir.getNumRoutes(); i++) {
					var route = dir.getRoute(i);
					var dist = route.getDistance()
					var x = dist.meters*2/1000;
					var y = x.toString().replace(".",",");
					var valor_calculado = parseFloat(x);
					//alert('calculando....');
					//alert(valor_calculado);
					if (valor_calculado==0 && busca_por != 'endereco'){
						//alert('Nao encontrou');
						initialize('endereco');
						return false;
					}
					document.getElementById('distancia_km_conferencia').value = x;
					document.getElementById('distancia_km').value             = y;
					document.getElementById('distancia_km_maps').value        ='maps';
					document.getElementById('div_mapa_msg').innerHTML='Distância calculada <a href= "javascript:vermapa();">Ver mapa</a>';
			 }
		});
		GEvent.addListener(dir,"error", function() {
			//alert('Nao encontrou ou deu erro');
			initialize('endereco');
		});

	}
}
function compara(campo1,campo2){
	var num1 = campo1.value.replace(".",",");
	var num2 = campo2.value.replace(".",",");
	if(num1!=num2){
		document.getElementById('div_mapa_msg').style.visibility = "visible";
		document.getElementById('div_mapa_msg').innerHTML = 'A distância percorrida pelo técnico estará sujeito a auditoria';
	}
	else{
		document.getElementById('div_mapa_msg').style.visibility = "visible";
		document.getElementById('div_mapa_msg').innerHTML='Distância calculada <a href= "javascript:vermapa();">Ver mapa</a>';

	}
}

function vermapa(){
	document.getElementById("mapa").style.visibility="visible";
	document.getElementById("mapa2").style.visibility="visible";
}
function escondermapa(){
	document.getElementById("mapa").style.visibility="hidden";
	document.getElementById("mapa2").style.visibility="hidden";
}

</script>

<div id='div_mapa' style='background:#efefef;border:#999999 1px solid;font-size:10px;padding:5px;' >
<b>Para Calcular a distância percorrida pelo técnico para execução do serviço(ida e volta):<br>
Preencha todos os campos de endereço acima ou preencha o campo de distância</b><br><br>

<input  type="hidden" id="ponto1"value="<?=$endereco_posto?>" >
<input  type="hidden" id="cep_posto"value="<?=$cep_posto?>" >
<input  type="hidden" id="distancia_km_maps"  value="" >
<input type='hidden' name='distancia_km_conferencia' id='distancia_km_conferencia' value='<?=$distancia_km_conferencia?>'>

Distância: <input type='text' name='distancia_km' id='distancia_km' value='<?=$distancia_km?>' size='5' onchange="javascript:compara(distancia_km,distancia_km_conferencia)"> Km
<input  type="button" onclick="initialize('')" value="Calcular Distância" size='5' ><div id='div_mapa_msg' style='color:#FF0000'></div>
<br><B>Endereço do posto:</b> <u><?=$endereco_posto?></u><br>&nbsp;
</div>
