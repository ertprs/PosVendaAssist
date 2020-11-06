<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';


?>

<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<title>Telecontrol - Mapa da Rede Autorizada</title>
</head>

<body onload='load()' onunload='GUnload()'>


<?
$tem_mapa = 0 ;
$estado = $_POST['estado'];
$pais   = $_POST['pais'];

if (strlen ($estado) > 0) {
	if ($estado == "00"){
		$cond_estado = "1=1";
	}elseif ($estado == "BR-CO"){
		$cond_estado = "tbl_posto.estado IN ('MT','MS','GO','DF','TO')";
	}elseif ($estado == "BR-N"){
		$cond_estado = "tbl_posto.estado IN ('AM','AC','AP','PA','RO','RR')";
	}elseif ($estado == "BR-NE"){
		$cond_estado = "tbl_posto.estado IN ('MA','CE','PI','RN','PB','PN','AL','SE','BA','PE')";
	}else{
		$cond_estado = "tbl_posto.estado = '$estado'";
	}

	if ($pais != "BR"){
		$cond_estado = "1=1";
	}


	$sql = "SELECT tbl_posto.posto, TRIM (tbl_posto.nome) AS nome, TRIM (tbl_posto.endereco) AS endereco, tbl_posto.numero, tbl_posto.fone, tbl_posto.cidade, tbl_posto.cep, tbl_posto.estado, tbl_posto.latitude, tbl_posto.longitude 
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE  $cond_estado
			AND    tbl_posto.pais = '$pais'
			AND    tbl_posto_fabrica.credenciamento = 'CREDENCIADO' 
			ORDER BY tbl_posto.cidade, tbl_posto.cep ";
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
}

//HD 678667 - MLG - Adicionar mais uma Key. Alterado para um include que gerencia as chaves.
include '../gMapsKeys.inc';
?>

<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?=$gAPI_key?>" type="text/javascript"></script>

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
					$fone      = pg_result ($resPosto,$i,fone);
					$cidade    = pg_result ($resPosto,$i,cidade);
					$estado    = pg_result ($resPosto,$i,estado);
					$cep       = pg_result ($resPosto,$i,cep);
					$latitude  = pg_result ($resPosto,$i,latitude);
					$longitude = pg_result ($resPosto,$i,longitude);

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

</script>



<center>
<div id="Gmapa" style="width: 700px; height: 400px ; border: 1px solid #979797; background-color: #e5e3df; margin: auto; margin-top: 2em; margin-bottom: 2em">
   <div style="padding: 1em; color: gray">Carregando Mapa...</div>
</div>

</center>

</form>



<?
echo "<table align='center'>";
if (isset ($resPosto)) {
	echo "<table width='700' align='center' style='border:1px #77aadd solid;height:22px;'>";
	echo "<tr align='center' bgcolor='#eeeeff' >";
	echo "<td style='border:1px #77aadd solid;height:22px;'><b> Nome do Posto </b></td>";
	echo "<td style='border:1px #77aadd solid;height:22px;'><b> Endereço </b></td>";
	echo "<td style='border:1px #77aadd solid;height:22px;'><b> Cidade </b></td>";
	echo "<td style='border:1px #77aadd solid;height:22px;'><b> CEP </b></td>";
	echo "<td style='border:1px #77aadd solid;height:22px;'><b> Mapa </b></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($resPosto) ; $i++){
		$posto     = pg_result ($resPosto,$i,posto);
		$nome      = pg_result ($resPosto,$i,nome);
		$endereco  = pg_result ($resPosto,$i,endereco);
		$numero    = pg_result ($resPosto,$i,numero);
		$fone      = pg_result ($resPosto,$i,fone);
		$cidade    = pg_result ($resPosto,$i,cidade);
		$estado    = pg_result ($resPosto,$i,estado);
		$cep       = pg_result ($resPosto,$i,cep);
		$latitude  = pg_result ($resPosto,$i,latitude);
		$longitude = pg_result ($resPosto,$i,longitude);

		$nome     = str_replace ("\"","",$nome);
		$nome     = str_replace ("'","",$nome);
		$endereco = str_replace ("\"","",$endereco);
		$endereco = str_replace ("'","",$endereco);
		$cidade   = str_replace ("\"","",$cidade);
		$cidade   = str_replace ("'","",$cidade);
		$cep      = str_replace ("\"","",$cep);
		$cep      = str_replace ("'","",$cep);
		$fone     = str_replace ("(","",$fone);
		$fone     = str_replace (")","",$fone);

		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3);

		$cor = '#eeeeff';
		if ($i % 2 == 0) $cor = '#ffffff';

		echo "<tr bgcolor='$cor' style='border:1px #77aadd solid;height:22px; font-size: 10px'>";

		echo "<td>";
		echo $nome ;
		echo "</td>";

		echo "<td>";
		echo $endereco . ", " . $numero ;
		echo "</td>";

		echo "<td nowrap>";
		echo $cidade ;
		echo "</td>";

		echo "<td nowrap>";
		echo $cep ;
		echo "</td>";

		if (strlen ($latitude) > 0 and strlen ($longitude) > 0) {
			echo "<td>";
#			echo "<div onclick='javascript: alert (\"antes\") ; map.setCenter (new GLatLng($longitude,$latitude,0),12); alert (\'ok\')'> ";
#			echo "<div onclick='javascript: var map = new GMap2(document.getElementById(\"map\")); map.setCenter (new GLatLng($longitude,$latitude),10); '> ";
			echo "<a href='#mapa_inicio' onclick='javascript: map.setCenter(new GLatLng($longitude,$latitude),16); '> ";
			echo "mapa" ;
			echo "</a>";
			echo "</td>";


		}

		echo "</tr>";
		
	}

	echo "</table>";
}
?>

</body>
</html>
