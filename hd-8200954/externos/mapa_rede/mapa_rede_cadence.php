<?
include '/var/www/assist/www/dbconfig.php';
include '/var/www/includes/dbconnect-inc.php';

$html_titulo = "Telecontrol - Mapa da Rede Autorizada";
$body_options = "onload='load()' onunload='GUnload()'";
$cor_abas = "verdes";

include "cabecalho.php";
include '../../assist/www/gMapsKeys.inc';

?>

<style type="text/css">
	v\:* {      behavior:url(#default#VML);    }    
</style>
	
<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?=$gAPI_key ?>" type="text/javascript"></script>



<?
$fabrica = $_POST['fabrica'];
$fabrica = str_replace ("'","",$fabrica);

$cep = $_POST['cep'];
$cep = str_replace ("'","",$cep);
$cep = substr ($cep,0,1);

$estado = $_POST['estado'];
$estado = str_replace ("'","",$estado);

$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
$resInsertLog = pg_query($con, $sqlInsertLog);

?>


<br>

<a name="mapa_inicio">


<form name="frm_mapa" action="<?=$PHP_SELF ?>#mapa" method="post">

<table width='500' align='center' border='0'>

<tr>
	<td colspan="2">
		<embed src="http://www.agenciapro-v.com/cadence/top.swf" width="770" height="229" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" wmode="transparent" menu="false"></embed>
		<!--
		Para uma localização mais precisa dos postos que atendem sua região, você deve digitar seu CEP. Você também pode escolher a cidade para uma busca mais ampla.
		-->
	</td>
</tr>

<!--
<tr>
	<td>Escolha o fabricante</td>
	<td>
		<select name="fabrica" size="1">
		<option value="">---</option>
		<?
		$sql = "SELECT fabrica, nome FROM tbl_fabrica WHERE logo IS NOT NULL 
				UNION SELECT 14, 'Kronos'
				UNION SELECT  1, 'DeWalt'
				UNION SELECT 20, 'Dremel'
				UNION SELECT 20, 'Skil'
				UNION SELECT 14, 'Durabrand'
				UNION SELECT 14, 'Revlon'
				UNION SELECT  2, 'Dynalux'
				UNION SELECT  2, 'Proditel'
				UNION SELECT  7, 'Sire'
				UNION SELECT 15, 'Vivitar'
				UNION SELECT 15, 'Digitall Lab'
				UNION SELECT 15, 'Aerotec'
				UNION SELECT 15, 'Royal'
				UNION SELECT 15, 'Eagle'
				UNION SELECT 15, 'IHome'
				UNION SELECT 15, 'Atlantic Breeze'
				UNION SELECT 15, 'Coby'
				UNION SELECT 15, 'Digistar'
				UNION SELECT 15, 'Heneywell'
				UNION SELECT 15, 'Conair'
				UNION SELECT 15, 'Babyliss'
				UNION SELECT  4, 'Nike'
				UNION SELECT  4, 'Eterna'
				UNION SELECT  4, 'Festina'
				UNION SELECT  4, 'Porsche Design'
				UNION SELECT 24, 'Fischer'
				ORDER BY nome";
		$res = pg_exec ($con,$sql);
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$x_fabrica = pg_result ($res,$i,fabrica);
			$x_nome    = pg_result ($res,$i,nome);
			echo "<option value=$x_fabrica>$x_nome</option>";
		}
		?>
		</select>
	</td>
</tr>
-->


<tr>
	<td>Escolha o estado</td>
	<td>
		<select name="estado" size="1">
		<option></option>
		<option value="AC">ACRE</option>
		<option value="AL">ALAGOAS</option>
		<option value="AP">AMAPA</option>
		<option value="AM">AMAZONAS</option>
		<option value="BA">BAHIA</option>
		<option value="CE">CEARA</option>
		<option value="DF">DISTRITO FEDERAL</option>
		<option value="ES">ESPIRITO SANTO</option>
		<option value="GO">GOIAS</option>
		<option value="MA">MARANHAO</option>
		<option value="MT">MATO GROSSO</option>
		<option value="MS">MATO GROSSO DO SUL</option>
		<option value="MG">MINAS GERAIS</option>
		<option value="PA">PARA</option>
		<option value="PB">PARAIBA</option>
		<option value="PR">PARANA</option>
		<option value="PE">PERNAMBUCO</option>
		<option value="PI">PIAUI</option>
		<option value="RJ">RIO DE JANEIRO</option>
		<option value="RN">RIO GRANDE DO NORTE</option>
		<option value="RS">RIO GRANDE DO SUL</option>
		<option value="RO">RONDONIA</option>
		<option value="RR">RORAIMA</option>
		<option value="SC">SANTA CATARINA</option>
		<option value="SP">SAO PAULO</option>
		<option value="SE">SERGIPE</option>
		<option value="TO">TOCANTINS</option>
		</select>

		&nbsp;&nbsp;&nbsp;
		<input type="submit" name="btn_mapa" value="pesquisar">

	</td>
</tr>

</table>

</form>

<?
$tem_mapa = 0 ;
if (strlen ($estado) > 0) {
	$sql = "SELECT tbl_posto.posto, TRIM (tbl_posto.nome) AS nome, TRIM (tbl_posto.endereco) AS endereco, tbl_posto.numero, tbl_posto.fone, tbl_posto.cidade, tbl_posto.cep, tbl_posto.estado, tbl_posto.latitude, tbl_posto.longitude 
			FROM   tbl_posto
			JOIN   (SELECT DISTINCT posto FROM tbl_posto_fabrica WHERE credenciamento = 'CREDENCIADO' AND fabrica = 35) pf ON tbl_posto.posto = pf.posto
			WHERE  tbl_posto.estado = '$estado'
			ORDER BY tbl_posto.cidade, tbl_posto.cep";
	$resPosto = pg_exec ($con,$sql);

	if (pg_numrows ($resPosto) > 0) {
		$tem_mapa = 1;

		echo "<hr>";
		echo "<center>";
		echo "<b>Clique sobre as marcas para ver informações detalhadas do posto</b>";
		echo "<br>";
		echo "+) Podem haver postos que não apareçam no mapa, por estarem com o endereço incorreto";
		echo "<br>";
		echo "+) A localização dos postos não é exata, podendo haver margem de erro";
		echo "<br><br><br>";
	}
}

?>


<script type="text/javascript">
//<![CDATA[

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
	#					echo "var WINDOW_HTML = '<b>$nome</b> <br> $endereco, $numero <br> fone: $fone  <br> $cidade - $estado - $cep'; \n";
						echo "var WINDOW_HTML = '<b>$nome</b> <br> $endereco, $numero <br> fone: $fone '; \n";
						echo "GEvent.addListener (posto_$posto, \"click\", function() {	\n";
						echo "posto_$posto.openInfoWindowHtml ('<b>$nome</b> <br> $endereco, $numero <br> fone: $fone '); \n";
						echo "} );";
						echo "\n\n";
					
    				}
				}
			}else{
			    echo "map.setCenter (new GLatLng(-15.815279,-48.070252,0),3);";
			    
			}
			?>
		}
	}
//]]>
</script>



<center>
<div id="Gmapa" style="width: 700px; height: 400px"></div>
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

		echo "<td>";
		echo $cidade ;
		echo "</td>";

		echo "<td>";
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


<? include "rodape.php" ?> 

</body>
</html>
