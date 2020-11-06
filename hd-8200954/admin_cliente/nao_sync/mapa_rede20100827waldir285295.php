<?
include "dbconfig.php";
include "dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

//HD 7277 Paulo - tirar acento do arquivo upload
function acentos1( $texto ){
	 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" ,"á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" );
	return str_replace( $array1, $array2, $texto );
}
function acentos2( $texto ){
	 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" ,"Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	return str_replace( $array1, $array2, $texto );
}
function acentos3( $texto ){
 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
 return str_replace( $array1, $array2, $texto );
}


if($ajax=='grava'){
	$sql = "SELECT posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING(posto)
			WHERE fabrica = $login_fabrica
			AND   posto   = $posto";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		str_replace("'","",$xdata_abertura);
		$ponto = str_replace("(","",$ponto);
		$ponto = str_replace(")","",$ponto);
		$pontos = explode(",", $ponto);
		$latitude  = $pontos[0];
		$longitude = $pontos[1];
		$sql = "UPDATE tbl_posto SET
					latitude  = '$longitude',
					longitude = '$latitude'
				WHERE posto = $posto";
		$res = pg_exec($con,$sql);
		echo "ok|lat $latitude long $longitude";
	}else echo "NO|";
	exit;
}
?>

<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<title>Telecontrol - Mapa da Rede Autorizada</title>
</head>

<body onload='load()' onunload='GUnload()'>


<?
$tem_mapa = 0 ;
// $cidade = $_POST['cidade'];
// $estado = $_POST['estado'];
// $pais   = $_POST['pais'];
// $cep    = $_POST['cep'];
// $linha  = $_POST['linha'];
// 
// if(strlen($_GET['cidade'])>0)       $cidade     = $_GET['cidade'];
// if(strlen($_GET['estado'])>0)       $estado     = $_GET['estado'];
// if(strlen($_GET['pais'])>0)         $pais       = $_GET['pais'];
// if(strlen($_GET['consumidor'])>0)   $consumidor = $_GET['consumidor'];
// if(strlen($_GET['linha'])>0)        $linha      = $_GET['linha'];
//echo "($pais) [$estado] $cidade";
//$cidade = utf8_decode($cidade);
$cidade     = $_REQUEST['cidade'];
$estad      = $_REQUEST['estado'];
$pais       = $_REQUEST['pais'];  
$cep        = $_REQUEST['cep'];   
$linha      = $_REQUEST['linha']; 
echo $consumidor = $_REQUEST['consumidor'];

$cond_cadence = ($login_fabrica == 35 ) ? " AND tipo_posto <> 163 " : "";

if (strlen ($estado) > 0) {
	if ($estado == "00"){
		$cond_estado = "1=1";
	}elseif ($estado == "BR-CO"){
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('MT','MS','GO','DF','TO')";
	}elseif ($estado == "BR-N"){
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('AM','AC','AP','PA','RO','RR')";
	}elseif ($estado == "BR-NE"){
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('MA','CE','PI','RN','PB','PN','AL','SE','BA','PE')";
	}elseif ($estado == "SUL"){
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('PR','SC','RS')";
	}elseif ($estado == "SP-capital"){
		$cond_estado = "tbl_posto_fabrica.contato_estado ='SP' AND tbl_posto_fabrica.contato_cidade ilike 'sao paulo' ";
	}elseif ($estado == "SP-interior"){
		$cond_estado = "tbl_posto_fabrica.contato_estado ='SP' AND trim(tbl_posto_fabrica.contato_cidade) not in ('SÃO PAULO','SAO PAULO','São Paulo')  AND trim(UPPER(tbl_posto_fabrica.contato_cidade)) <> 'SAO PAULO'";
	}elseif ($estado == "BR-NEES"){
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('MA','CE','PI','RN','PB','PN','AL','SE','ES','PE')";
	}elseif ($estado == "BR-NCO"){
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('AM','AC','AP','PA','RO','RR','MT','MS','GO','DF','TO')";
	}else{
		$cond_estado = "tbl_posto_fabrica.contato_estado = '$estado'";
	}
}else{
	$cond_estado = "1=1";
}

if ($pais != "BR" and strlen($pais)!=0){
	$cond_estado = "1=1";
}

if (strlen($pais)==0){
	$cond_pais = " AND 1=1 ";
}else{
	$cond_pais = " AND tbl_posto_fabrica.contato_pais = '$pais' ";
}
if(strlen($linha)>0){
	$sql = "SELECT  *
			FROM    tbl_linha
			WHERE   tbl_linha.fabrica = $login_fabrica
			AND     tbl_linha.linha   = $linha
			ORDER BY tbl_linha.nome;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$aux_linha = trim(pg_result($res,$x,linha));
		$aux_nome  = trim(pg_result($res,$x,nome));
		$info = "<br><b>Linha: </b>$aux_nome\n";
	}
	$sql_add = " AND tbl_posto.posto IN (SELECT DISTINCT posto FROM tbl_posto_fabrica JOIN tbl_posto_linha USING(posto) WHERE linha=$linha AND  fabrica =  $login_fabrica) ";
}

if(strlen($cidade)>0){
	$xcidade1 = acentos1($cidade);
	$xcidade2 = acentos2($cidade);
	$xcidade3 = acentos3($cidade);
	$sql_add .= " AND (
						UPPER(tbl_posto_fabrica.contato_cidade) LIKE upper('%$xcidade1%')
						OR UPPER(tbl_posto_fabrica.contato_cidade) LIKE upper('%$xcidade2%')
						OR UPPER(tbl_posto_fabrica.contato_cidade) LIKE upper('%$xcidade3%')
						)";
	$info .= "&nbsp;&nbsp;<b>Cidade:</b> $cidade";
}

$sql = "SELECT tbl_posto.posto,
			   TRIM (tbl_posto.nome) AS nome,
			   TRIM (tbl_posto_fabrica.nome_fantasia) AS nome_fantasia,
			   TRIM (tbl_posto_fabrica.contato_endereco) AS endereco,
			   tbl_posto_fabrica.contato_numero AS numero,
			   tbl_posto_fabrica.contato_fone_comercial,
			   tbl_posto_fabrica.contato_cidade AS cidade,
			   tbl_posto_fabrica.contato_bairro AS bairro,
			   tbl_posto_fabrica.contato_cep AS cep,
			   tbl_posto_fabrica.contato_estado AS estado,
			   tbl_posto.latitude,
			   tbl_posto.longitude ,
			   tbl_posto_fabrica.codigo_posto,
			   tbl_posto.fone
		FROM   tbl_posto
		JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE  $cond_estado
		$cond_pais ";
if($login_fabrica <> 43){
	$sql .= " AND    tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";
}
$sql .= " $sql_add
		$cond_cadence
/*		AND tbl_posto.posto <> 6359*/
		ORDER BY tbl_posto_fabrica.contato_pais, tbl_posto_fabrica.contato_estado,
		tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.contato_cep ";
if ($ip=="189.96.95.181") {
	var_dump(utf8_decode($cidade));
	echo nl2br($sql);
}

$resPosto = pg_exec ($con,$sql);

if (pg_numrows ($resPosto) > 0) {
	$tem_mapa = 1;

	echo "<center>";
	echo "<b>Clique sobre as marcas para ver informações detalhadas do posto</b>";
	echo "<br>";
	echo "+) Podem haver postos que não apareçam no mapa, por estarem com o endereço incorreto";
	echo "<br>";
	echo "+) A localização dos postos não é exata, podendo haver margem de erro";
	echo "<br>";
}

?>

<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAA4k5ZzVjDVAWrCyj3hmFzTxR_fGCUxdSNOqIGjCnpXy7SRGDdcRTb85b5W8d9rUg4N-hhOItnZScQwQ" type="text/javascript"></script>
<?PHP
	if (strlen($cidade) > 0) {
?>
<script type="text/javascript">

	var map;
	var gdir;
	function load() {
		if (GBrowserIsCompatible()) {
			map = new GMap2(document.getElementById("Gmapa"));
			map.addControl(new GMapTypeControl());
			map.addControl(new GLargeMapControl());
			gdir = new GDirections(map, document.getElementById("directions"));
			var pt1 = '17504380';
			var pt2 =  '17505324';
			gdir.loadFromWaypoints([pt1,pt2], {locale:"pt-br", getSteps:true});
			GEvent.addListener(gdir,"load", function() {
			//alert('entrou...');
			for (var i=0; i<gdir.getNumRoutes(); i++) {
					var route = gdir.getRoute(i);
					var dist = route.getDistance()
					var x = dist.meters*2/1000;
					var y = x.toString().replace(".",",");
					var valor_calculado = parseFloat(x);
			 }

 			 document.getElementById('km').value = ((Math.round(x*100))/100);  
		});
			
			GEvent.addListener(gdir, "addoverlay", onGDirectionsAddOverlay);
			map.setCenter(new GLatLng(0,0),0);	// inital setCenter()  added by Esa.

			<?
			if ($tem_mapa == "1") {

				$centro_mapa = "0";

				for ($i = 0 ; $i < pg_numrows ($resPosto) ; $i++){
					$posto     = pg_result ($resPosto,$i,'posto');
					$nome      = pg_result ($resPosto,$i,'nome');
					$fone      = pg_result ($resPosto,$i,'fone');
					$endereco  = pg_result ($resPosto,$i,'endereco');
					$numero    = pg_result ($resPosto,$i,'numero');
					$fone      = pg_result ($resPosto,$i,'contato_fone_comercial');
					$cidade    = pg_result ($resPosto,$i,'cidade');
					$bairro    = pg_result ($resPosto,$i,'bairro');
					$estado    = pg_result ($resPosto,$i,'estado');
					$cep       = pg_result ($resPosto,$i,'cep');
					$latitude  = pg_result ($resPosto,$i,'latitude');
					$longitude = pg_result ($resPosto,$i,'longitude');

					$sql = "SELECT *
							FROM  tbl_empresa_cliente
							WHERE posto   = $posto
							AND   fabrica = $login_fabrica";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;
					$sql = "SELECT *
							FROM  tbl_empresa_fornecedor
							WHERE posto   = $posto
							AND   fabrica = $login_fabrica";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;

					$sql = "SELECT *
							FROM  tbl_erp_login
							WHERE posto   = $posto
							AND   fabrica = $login_fabrica";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;


					if (strlen ($latitude) > 0 AND strlen ($longitude) > 0) {
						if ($centro_mapa == "0"){
							echo "map.setCenter (new GLatLng(" . pg_result ($resPosto,$i, longitude) . "," . pg_result ($resPosto,$i,latitude) . ",0),13);";
							echo "\n\n";
							$centro_mapa = "1";
						}

						$nome     = str_replace ("\"","",$nome);
						$nome     = str_replace ("'","",$nome);
						$endereco = str_replace ("\"","",$endereco);
						$endereco = str_replace ("'","",$endereco);
						$cidade   = str_replace ("\"","",$cidade);
						$cidade   = str_replace ("'","",$cidade);
						$bairro   = str_replace ("\"","",$bairro);
						$bairro   = str_replace ("'","",$bairro);
						$cep      = str_replace ("\"","",$cep);
						$cep      = str_replace ("'","",$cep);
						$fone     = str_replace ("(","",$fone);
						$fone     = str_replace (")","",$fone);

						$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3);

						echo "var point_$posto = new GLatLng(" . pg_result ($resPosto,$i,longitude) . "," . pg_result ($resPosto,$i,latitude) . "); \n";
						echo "var posto_$posto = new GMarker(point_$posto); \n";
						echo "map.addOverlay(posto_$posto); \n";
						echo "GEvent.addListener (posto_$posto, \"click\", function(){	\n";
						echo "posto_$posto.openInfoWindowHtml('<FONT SIZE=\"-1\"><b>$nome</b> <br> $endereco, $numero <br> fone: $fone  <br> $cidade - $estado - $cep </FONT>'); \n";
						echo "}); \n";
						echo "\n\n";
					}
				}
				if (strlen ($latitude) > 0 AND strlen ($longitude) > 0 and strlen($consumidor) > 0) {
					echo "setDirections(\"$consumidor\",\"$longitude,$latitude\",\"pt-br\");";
				}
			}else{
			    echo "map.setCenter (new GLatLng(-15.815279,-48.070252,0),3);";

			}
			?>
			GEvent.addListener(gdir,"error", function() {
				setDirections("<? echo $cep;?>","<? $longitude.",".$latitude; ?>","pt-br");
			});
		}
	}

function setDirections(fromAddress, toAddress, locale) {
  gdir.load("from: " + fromAddress + " to: " + toAddress,
  { "locale": locale , "getSteps":true});
}

/**
* The add-on code for draggable markers
* @author Esa 2008
*/
var newMarkers = [];
var latLngs = [];
var icons = [];

// Note the 'addoverlay' GEvent listener inside initialize() function of the original code (above).
// 'load' event cannot be used

function onGDirectionsAddOverlay(){
  // Remove the draggable markers from previous function call.
  for (var i=0; i<newMarkers.length; i++){
    map.removeOverlay(newMarkers[i]);
  }

  // Loop through the markers and create draggable copies
  for (var i=0; i<=gdir.getNumRoutes(); i++){
    var originalMarker = gdir.getMarker(i);
    latLngs[i] = originalMarker.getLatLng();
    icons[i] = originalMarker.getIcon();
    newMarkers[i] = new GMarker(latLngs[i],{icon:icons[i], draggable:true, title:'móvel'});
    map.addOverlay(newMarkers[i]);

    // Get the new waypoints from the newMarkers array and call loadFromWaypoints by dragend
    GEvent.addListener(newMarkers[i], "dragend", function(){
      var points = [];
      for (var i=0; i<newMarkers.length; i++){
        points[i]= newMarkers[i].getLatLng();
      }
      gdir.loadFromWaypoints(points);
    });

    //Bind 'click' event to original markers 'click' event
    copyClick(newMarkers[i],originalMarker);

    // Now we can remove the original marker safely
    map.removeOverlay(originalMarker);
  }

  function copyClick(newMarker,oldMarker){
    GEvent.addListener(newMarker, 'click', function(){
      GEvent.trigger(oldMarker,'click');
    });
  }
}


	var geocoder = new GClientGeocoder();
	function showAddress(address,posto) {
		geocoder.getLatLng(
			address,
			function(point) {
				if (!point) {
					alert(address + " Não Encontrado!");
				} else {
					map.setCenter(point, 16);
					//alert(point+"Posto:"+posto);
					grava_ll(posto,point);
					var marker = new GMarker(point);
					map.addOverlay(marker);
					marker.openInfoWindowHtml(address);
				}
			}
		);
	}


	function createRequestObject(){
		var request_;
		var browser = navigator.appName;
		if(browser == "Microsoft Internet Explorer"){
			 request_ = new ActiveXObject("Microsoft.XMLHTTP");
		}else{
			 request_ = new XMLHttpRequest();
		}
		return request_;
	}

	var http_forn = new Array();
	function grava_ll(posto,ponto) {
		url = "<?=$PHP_SELF?>?ajax=grava&posto="+posto+"&ponto="+ponto;
		var curDateTime = new Date();
		http_forn[curDateTime] = createRequestObject();
		http_forn[curDateTime].open('GET',url,true);
		http_forn[curDateTime].onreadystatechange = function(){
			if (http_forn[curDateTime].readyState == 4){
				if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) {
					var response = http_forn[curDateTime].responseText.split("|");
					if (response[0]=="ok"){
						alert('Informações Atualizadas com Sucesso: '+response[1])
					}else{
						alert('Não foi possível atualizar as informações');
					}
				}
			}
		}
		http_forn[curDateTime].send(null);
	}

</script>
<?PHP
} elseif (strlen($estado)>0) {
?>
<script type="text/javascript">

	var map;
	var gdir;
	function load() {
		if (GBrowserIsCompatible()) {
			map = new GMap2(document.getElementById("Gmapa"));
			map.addControl(new GMapTypeControl());
			map.addControl(new GLargeMapControl());
			gdir = new GDirections(map, document.getElementById("directions"));
			var pt1 = '17504380';
			var pt2 =  '17505324';
			gdir.loadFromWaypoints([pt1,pt2], {locale:"pt-br", getSteps:true});
			GEvent.addListener(gdir,"load", function() {
			//alert('entrou...');
			for (var i=0; i<gdir.getNumRoutes(); i++) {
					var route = dir.getRoute(i);
					var dist = route.getDistance()
					var x = dist.meters;
					var y = x.toString().replace(".",",");
					var valor_calculado = parseFloat(x);
			 }

		});
			GEvent.addListener(gdir, "addoverlay", onGDirectionsAddOverlay);
			map.setCenter(new GLatLng(0,0),0);	// inital setCenter()  added by Esa.
			<?
			if ($tem_mapa == "1") {

				$centro_mapa = "0";

				for ($i = 0 ; $i < pg_numrows ($resPosto) ; $i++){
					$posto     = pg_result ($resPosto,$i,posto);
					$nome      = pg_result ($resPosto,$i,nome);
					$endereco  = pg_result ($resPosto,$i,endereco);
					$numero    = pg_result ($resPosto,$i,numero);
					$fone      = pg_result ($resPosto,$i,contato_fone_comercial);
					$cidade    = pg_result ($resPosto,$i,cidade);
					$bairro    = pg_result ($resPosto,$i,bairro);
					$estado    = pg_result ($resPosto,$i,estado);
					$cep       = pg_result ($resPosto,$i,cep);
					$latitude  = pg_result ($resPosto,$i,latitude);
					$longitude = pg_result ($resPosto,$i,longitude);

					$sql = "SELECT *
							FROM  tbl_empresa_cliente
							WHERE posto   = $posto
							AND   fabrica = $login_fabrica";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;
					$sql = "SELECT *
							FROM  tbl_empresa_fornecedor
							WHERE posto   = $posto
							AND   fabrica = $login_fabrica";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;

					$sql = "SELECT *
							FROM  tbl_erp_login
							WHERE posto   = $posto
							AND   fabrica = $login_fabrica";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;


					if (strlen ($latitude) > 0 AND strlen ($longitude) > 0) {
						if ($centro_mapa == "0"){
							echo "map.setCenter (new GLatLng(" . pg_result ($resPosto,$i, longitude) . "," . pg_result ($resPosto,$i,latitude) . ",0),6);";
							echo "\n\n";
							$centro_mapa = "1";
						}

						$nome     = str_replace ("\"","",$nome);
						$nome     = str_replace ("'","",$nome);
						$endereco = str_replace ("\"","",$endereco);
						$endereco = str_replace ("'","",$endereco);
						$cidade   = str_replace ("\"","",$cidade);
						$cidade   = str_replace ("'","",$cidade);
						$bairro   = str_replace ("\"","",$bairro);
						$bairro   = str_replace ("'","",$bairro);
						$cep      = str_replace ("\"","",$cep);
						$cep      = str_replace ("'","",$cep);
						$fone     = str_replace ("(","",$fone);
						$fone     = str_replace (")","",$fone);

						$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3);

						echo "var point_$posto = new GLatLng(" . pg_result ($resPosto,$i,longitude) . "," . pg_result ($resPosto,$i,latitude) . "); \n";
						echo "var posto_$posto = new GMarker(point_$posto); \n";
						echo "map.addOverlay(posto_$posto); \n";
						echo "GEvent.addListener (posto_$posto, \"click\", function(){	\n";
						echo "posto_$posto.openInfoWindowHtml('<FONT SIZE=\"-1\"><b>$nome</b> <br> $endereco, $numero <br> fone: $fone  <br> $cidade - $estado - $cep </FONT>'); \n";
						echo "}); \n";

						echo "\n\n";

    				}
				}
			}else{
			    echo "map.setCenter (new GLatLng(-15.815279,-48.070252,0),3);";

			}
			?>
			GEvent.addListener(gdir,"error", function() {
				setDirections("<? echo $cep;?>","<? $longitude.",".$latitude; ?>","pt-br");
			});
		}
	}

	function setDirections(fromAddress, toAddress, locale) {
	  gdir.load("from: " + fromAddress + " to: " + toAddress,
	  { "locale": locale , "getSteps":true});
	}

	/**
	* The add-on code for draggable markers
	* @author Esa 2008
	*/
	var newMarkers = [];
	var latLngs = [];
	var icons = [];

	// Note the 'addoverlay' GEvent listener inside initialize() function of the original code (above).
	// 'load' event cannot be used

	function onGDirectionsAddOverlay(){
	  // Remove the draggable markers from previous function call.
	  for (var i=0; i<newMarkers.length; i++){
		map.removeOverlay(newMarkers[i]);
	  }

	  // Loop through the markers and create draggable copies
	  for (var i=0; i<=gdir.getNumRoutes(); i++){
		var originalMarker = gdir.getMarker(i);
		latLngs[i] = originalMarker.getLatLng();
		icons[i] = originalMarker.getIcon();
		newMarkers[i] = new GMarker(latLngs[i],{icon:icons[i], draggable:true, title:'móvel'});
		map.addOverlay(newMarkers[i]);

		// Get the new waypoints from the newMarkers array and call loadFromWaypoints by dragend
		GEvent.addListener(newMarkers[i], "dragend", function(){
		  var points = [];
		  for (var i=0; i<newMarkers.length; i++){
			points[i]= newMarkers[i].getLatLng();
		  }
		  gdir.loadFromWaypoints(points);
		});

		//Bind 'click' event to original markers 'click' event
		copyClick(newMarkers[i],originalMarker);

		// Now we can remove the original marker safely
		map.removeOverlay(originalMarker);
	  }

	  function copyClick(newMarker,oldMarker){
		GEvent.addListener(newMarker, 'click', function(){
		  GEvent.trigger(oldMarker,'click');
		});
	  }
	}

	var geocoder = new GClientGeocoder();
	function showAddress(address,posto) {
		geocoder.getLatLng(
			address,
			function(point) {
				if (!point) {
					alert(address + " Não Encontrado!");
				} else {
					map.setCenter(point, 16);
					//alert(point+"Posto:"+posto);
					grava_ll(posto,point);
					var marker = new GMarker(point);
					map.addOverlay(marker);
					marker.openInfoWindowHtml(address);
				}
			}
		);
	}

	function createRequestObject(){
		var request_;
		var browser = navigator.appName;
		if(browser == "Microsoft Internet Explorer"){
			 request_ = new ActiveXObject("Microsoft.XMLHTTP");
		}else{
			 request_ = new XMLHttpRequest();
		}
		return request_;
	}

	var http_forn = new Array();
	function grava_ll(posto,ponto) {
		url = "<?=$PHP_SELF?>?ajax=grava&posto="+posto+"&ponto="+ponto;
		var curDateTime = new Date();
		http_forn[curDateTime] = createRequestObject();
		http_forn[curDateTime].open('GET',url,true);
		http_forn[curDateTime].onreadystatechange = function(){
			if (http_forn[curDateTime].readyState == 4){
				if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) {
					var response = http_forn[curDateTime].responseText.split("|");
					if (response[0]=="ok"){
						alert('Informações Atualizadas com Sucesso: '+response[1])
					}else{
						alert('Não foi possível atualizar as informações');
					}
				}
			}
		}
		http_forn[curDateTime].send(null);
	}

</script>
<?PHP
	}else{
?>
<script type="text/javascript">

	var map;

	function load() {
		if (GBrowserIsCompatible()) {
			map = new GMap2(document.getElementById("Gmapa"));
			map.addControl(new GLargeMapControl());
			map.addControl(new GMapTypeControl());

			<?
			if ($tem_mapa == "1") {

				$centro_mapa = "0";

				for ($i = 0 ; $i < pg_numrows ($resPosto) ; $i++){
					$posto     = pg_result ($resPosto,$i,posto);
					$nome      = pg_result ($resPosto,$i,nome);
					$endereco  = pg_result ($resPosto,$i,endereco);
					$numero    = pg_result ($resPosto,$i,numero);
					$fone      = pg_result ($resPosto,$i,contato_fone_comercial);
					$cidade    = pg_result ($resPosto,$i,cidade);
					$bairro    = pg_result ($resPosto,$i,bairro);
					$estado    = pg_result ($resPosto,$i,estado);
					$cep       = pg_result ($resPosto,$i,cep);
					$latitude  = pg_result ($resPosto,$i,latitude);
					$longitude = pg_result ($resPosto,$i,longitude);

					$sql = "SELECT *
							FROM  tbl_empresa_cliente
							WHERE posto   = $posto
							AND   fabrica = $login_fabrica";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;
					$sql = "SELECT *
							FROM  tbl_empresa_fornecedor
							WHERE posto   = $posto
							AND   fabrica = $login_fabrica";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;

					$sql = "SELECT *
							FROM  tbl_erp_login
							WHERE posto   = $posto
							AND   fabrica = $login_fabrica";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;


					if (strlen ($latitude) > 0 AND strlen ($longitude) > 0) {
						if ($centro_mapa == "0"){
							echo "map.setCenter (new GLatLng(" . pg_result ($resPosto,$i, longitude) . "," . pg_result ($resPosto,$i,latitude) . ",0),3);";
							echo "\n\n";
							$centro_mapa = "1";
						}

						$nome     = str_replace ("\"","",$nome);
						$nome     = str_replace ("'","",$nome);
						$endereco = str_replace ("\"","",$endereco);
						$endereco = str_replace ("'","",$endereco);
						$cidade   = str_replace ("\"","",$cidade);
						$cidade   = str_replace ("'","",$cidade);
						$bairro   = str_replace ("\"","",$bairro);
						$bairro   = str_replace ("'","",$bairro);
						$cep      = str_replace ("\"","",$cep);
						$cep      = str_replace ("'","",$cep);
						$fone     = str_replace ("(","",$fone);
						$fone     = str_replace (")","",$fone);

						$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3);

						echo "var point_$posto = new GLatLng(" . pg_result ($resPosto,$i,longitude) . "," . pg_result ($resPosto,$i,latitude) . "); \n";
						echo "var posto_$posto = new GMarker(point_$posto); \n";
						echo "map.addOverlay(posto_$posto); \n";
						echo "GEvent.addListener (posto_$posto, \"click\", function(){	\n";
						echo "posto_$posto.openInfoWindowHtml('<FONT SIZE=\"-1\"><b>$nome</b> <br> $endereco, $numero <br> fone: $fone  <br> $cidade - $estado - $cep </FONT>'); \n";
						echo "}); \n";

						echo "\n\n";

    				}
				}
			}else{
			    echo "map.setCenter (new GLatLng(-15.815279,-48.070252,0),3);";

			}
			?>

		}
	}
	var geocoder = new GClientGeocoder();
	function showAddress(address,posto) {
		geocoder.getLatLng(
			address,
			function(point) {
				if (!point) {
					alert(address + " Não Encontrado!");
				} else {
					map.setCenter(point, 16);
					//alert(point+"Posto:"+posto);
					grava_ll(posto,point);
					var marker = new GMarker(point);
					map.addOverlay(marker);
					marker.openInfoWindowHtml(address);
				}
			}
		);
	}

	function createRequestObject(){
		var request_;
		var browser = navigator.appName;
		if(browser == "Microsoft Internet Explorer"){
			 request_ = new ActiveXObject("Microsoft.XMLHTTP");
		}else{
			 request_ = new XMLHttpRequest();
		}
		return request_;
	}

	var http_forn = new Array();
	function grava_ll(posto,ponto) {
		url = "<?=$PHP_SELF?>?ajax=grava&posto="+posto+"&ponto="+ponto;
		var curDateTime = new Date();
		http_forn[curDateTime] = createRequestObject();
		http_forn[curDateTime].open('GET',url,true);
		http_forn[curDateTime].onreadystatechange = function(){
			if (http_forn[curDateTime].readyState == 4){
				if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) {
					var response = http_forn[curDateTime].responseText.split("|");
					if (response[0]=="ok"){
						alert('Informações Atualizadas com Sucesso: '+response[1])
					}else{
						alert('Não foi possível atualizar as informações');
					}
				}
			}
		}
		http_forn[curDateTime].send(null);
	}

</script>
<?PHP
}
?>
<style>
	.menu{
		height:22px;
		font-weight:bold;
		text-align:center;
		background-color: #eeeeff;
		border:1px #77aadd solid;
	}
</style>
<table width='700' align='center' style='border:0px #77aadd solid;height:22px;'>
<caption><? echo $info; ?></caption>
<? echo ($login_fabrica==2) ? "<tfoot>" : "<thead>";/* HD 45183 */ ?>
<tr>
	<td colspan='7' align='center'><center>
		<div id="Gmapa" style="width: 700px; height: 400px ; border: 1px solid #979797; background-color: #e5e3df; margin: auto; margin-top: 2em; margin-bottom: 2em">
			<div style="padding: 1em; color: gray">Carregando Mapa...</div>
		</div>
		</center>
	</td>
</tr>
<? 	echo ($login_fabrica==2) ? "</tfoot>" : "</thead>";	/* HD 45183 */ ?>
<tbody>
<? if (isset ($resPosto)) { ?>
	<tr>
	<?if($login_fabrica<>59){?>
	<td class='menu'>Nome do Posto </td>
	<? } ?>
	<td class='menu'>Nome Fantasia</td>
	<td class='menu'>Endereço</td>
	<?if($login_fabrica==2 or $login_fabrica==51 or $login_fabrica==59 or $login_fabrica==30){?>
	<td class='menu'>Bairro </td>
	<? } ?>
	<td class='menu'>Cidade </td>
	<td class='menu'>Estado </td>
	<? if($login_fabrica <>2 ){ // HD 52095
		if($login_fabrica<>59 and $login_fabrica <> 51){ ?>
			<td class='menu'>CEP </td>
		<? }else{ ?>
				<td class='menu'>Fone </td>
	<?		}
		}
	?>
	<td class='menu'>Fone </td>
	<td class='menu'>Mapa </td>
	</tr>
	<input type='hidden' id='km' name='km'>
<?
	for ($i = 0 ; $i < pg_numrows ($resPosto) ; $i++){
		$posto         = pg_result ($resPosto,$i,posto);
		$codigo_posto  = pg_result ($resPosto,$i,codigo_posto);
		$nome          = pg_result ($resPosto,$i,nome);
		$nome_fantasia = pg_result ($resPosto,$i,nome_fantasia);
		$endereco      = pg_result ($resPosto,$i,endereco);
		$numero        = pg_result ($resPosto,$i,numero);
		$fone          = pg_result ($resPosto,$i,contato_fone_comercial);
		$cidade        = pg_result ($resPosto,$i,cidade);
		$bairro        = pg_result ($resPosto,$i,bairro);
		$estado        = pg_result ($resPosto,$i,estado);
		$cep           = pg_result ($resPosto,$i,cep);
		$latitude      = pg_result ($resPosto,$i,latitude);
		$longitude     = pg_result ($resPosto,$i,longitude);

		$nome     = str_replace ("\"","",$nome);
		$nome     = str_replace ("'","",$nome);
		$endereco = str_replace ("\"","",$endereco);
		$endereco = str_replace ("'","",$endereco);
		$cidade   = str_replace ("\"","",$cidade);
		$cidade   = str_replace ("'","",$cidade);
		$bairro   = str_replace ("\"","",$bairro);
		$bairro   = str_replace ("'","",$bairro);
		$cep      = str_replace ("\"","",$cep);
		$cep      = str_replace ("'","",$cep);
		$fone     = str_replace ("(","",$fone);
		$fone     = str_replace (")","",$fone);
		$latlin   = "$latitude,$longitude";
		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3);

		$cor= ($i % 2 == 0) ? '#ffffff' : '#eeeeff';

		echo "<tr bgcolor='$cor' style='border:1px #77aadd solid;height:22px; font-size: 10px'>";
		if($login_fabrica<>59){//hd 45740
			echo "<td>";
			if($login_fabrica ==3) {
				echo "<a href=\"javascript: posto_tab.value= '$posto'; codigo_posto_tab.value='$codigo_posto' ; posto_nome_tab.value='$nome' ; posto_nome_fantasia.value='$nome_fantasia' ; posto_endereco.value='$endereco,$numero' ; fone_posto.value='$fone' ; posto_cidade.value='$cidade' ; posto_estado.value='$estado' ; posto_cep.value='$cep' ; this.close(); \">\n";
			}else{
				if(isset($callcenter)) echo "<a href=\"javascript: posto_tab.value= '$posto'; codigo_posto_tab.value='$codigo_posto' ; posto_nome_tab.value='$nome' ; posto_km_tab.value = document.getElementById('km').value;window.close(); \">\n";
			}
			echo $nome ;
			echo "</a>";
			echo "</td>";
		}

		echo "<td>";
		if(isset($callcenter)) {
			if($login_fabrica == 3) {
				echo "<a href=\"javascript: posto_tab.value= '$posto'; codigo_posto_tab.value='$codigo_posto' ; posto_nome_tab.value='$nome' ; posto_nome_fantasia.value='$nome_fantasia' ; posto_endereco.value='$endereco,$numero' ; fone_posto.value='$fone' ; posto_cidade.value='$cidade' ; posto_estado.value='$estado' ; posto_cep.value='$cep' ; this.close(); \">\n";
			}else{
				echo "<a href=\"javascript: posto_tab.value= '$posto'; codigo_posto_tab.value='$codigo_posto' ; posto_nome_tab.value='$nome';posto_km_tab.value = document.getElementById('km').value ; this.close(); \">\n";
			}
		}
		echo $nome_fantasia ;
		echo "</a>";
		echo "</td>";

		echo "<td>";
		echo $endereco . ", " . $numero ;
		echo "</td>";

		if($login_fabrica==2 or $login_fabrica==51 or $login_fabrica==59 or $login_fabrica==30){//hd 45740 52095 71429
			echo "<td nowrap>";
			echo $bairro;
			echo "</td>";
		}
		echo "<td nowrap>";
		echo $cidade ;
		echo "</td>";

		echo "<td nowrap>";
		echo $estado ;
		echo "</td>";
		if($login_fabrica <>2 ){ // HD 52095
			if($login_fabrica <> 59 and $login_fabrica <> 51) { // HD 47757  46408
				echo "<td nowrap>";
				echo $cep ;
				echo "</td>";
			}
		}
		echo "<td nowrap align='center'>";
		echo $fone ;
		echo "</td>";
		if (strlen ($latitude) > 0 and strlen ($longitude) > 0) {
			echo "<td>";
#			echo "<div onclick='javascript: alert (\"antes\") ; map.setCenter (new GLatLng($longitude,$latitude,0),12); alert (\'ok\')'> ";
#			echo "<div onclick='javascript: var map = new GMap2(document.getElementById(\"map\")); map.setCenter (new GLatLng($longitude,$latitude),10); '> ";
			echo "<input id='address_$i' type='hidden' value = '$endereco,$numero,$cidade,br'>";
			echo "<a href='#mapa_inicio' onclick='javascript: map.setCenter(new GLatLng($longitude,$latitude),16); setDirections(\"$consumidor\",\"$longitude,$latitude\",\"pt-br\")'> ";
			echo "mapa" ;
			echo "</a>";
			echo "</td>";
			echo "<td>$km</td>";
		}else{
			echo "<td>";
			echo "<a href='#mapa_inicio' onclick='javascript: showAddress(\"".$endereco." ".$cidade." ".$cep."\",\"$posto\"); '> ";
			echo "localizar" ;
			echo "</a>";
			echo "</td>";
		}

		echo "</tr>";

	}
}
?>
</tbody>
<tfoot>
<tr>
<td colspan='7'><center>
	<div id="directions" style="width: 275px"></div>
	</center>
</td>
</tr>
</tfoot>
</table>

</body>
</html>