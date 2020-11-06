<?php
include '/var/www/assist/www/dbconfig.php';
include '/var/www/includes/dbconnect-inc.php';
include "/var/www/telecontrol/www/trad_site/fn_ttext.php";

function tira_acentos ($texto) {
//     $str  = utf8_decode($texto);
    $from = utf8_decode("áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ");
    $to      = "aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC";
    return strtr($texto,$from,$to);
}

function change_case($texto, $l_u = 'lower') {
    $acentos      = array("lower"    => "áâàãäéêèëíîìïóôòõúùüç",
                          "upper"    => "ÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ");
    if ($l_u[0] == 'l') {
        return strtr(strtolower($texto), $acentos['upper'], $acentos['lower']);
    } else {
        return strtr(strtoupper($texto), $acentos['lower'], $acentos['upper']);
    }
}

$html_titulo    = $a_trad_mapa["titulo"][$cook_idioma];
$body_options    = " onload='loadMap()' onunload='GUnload()'";

include "inc_header.php";
?>
<div id='conteiner'>
    <h2 id='mapa'>&nbsp;</h2>
<?php
$tem_mapa = 0;
$sql = <<<POSTOS
		SELECT DISTINCT posto, CASE WHEN estado IS NULL OR LENGTH(TRIM(estado)) != 2 OR estado='nu' THEN (SELECT tbl_cep.estado FROM tbl_cep WHERE tbl_cep.cep = tbl_posto.cep) ELSE estado END AS estado, TRIM(nome) AS "Razão Social",
				CASE WHEN tbl_posto.latitude IS NOT NULL AND tbl_posto.longitude IS NOT NULL
						THEN (tbl_posto.longitude||','||tbl_posto.latitude)
					 WHEN endereco IS NOT NULL
						THEN TRIM(REGEXP_REPLACE(endereco::text, E'\"|\'', '')) ||','|| TRIM(numero::text) ||','|| cep::text ||','|| estado ||','|| 'BR'
					ELSE TRIM(REGEXP_REPLACE(contato_endereco::text, E'\"|\'', '')) ||','|| TRIM(contato_numero::text) ||','|| contato_cep ||','|| contato_estado ||','|| 'BR'
				END AS situacao,
				CASE WHEN cidade = 'null' THEN  (SELECT tbl_cep.cidade FROM tbl_cep WHERE tbl_cep.cep = tbl_posto.cep) ELSE cidade END AS cidade,
				CASE WHEN cep IS NULL OR LENGTH(cep) != 8 THEN contato_cep ELSE cep END AS cep
			FROM tbl_posto_fabrica
			JOIN tbl_posto USING(posto)
			JOIN tbl_posto_linha USING(posto)
		WHERE linha IN (263,562)
		  AND endereco IS NOT NULL
		  AND credenciamento = 'CREDENCIADO'
		ORDER BY estado,"Razão Social" /*LIMIT 100*/
POSTOS;

    $resPosto = pg_query($con, $sql);
    $tot_postos = pg_num_rows($resPosto);
// 	echo "<div class='";
// 	echo ($resPosto === false)?"erro":"msg";
// 	echo "'>SQL: $sql<p>Total: $tot_postos</p></div><br>\n";
// exit;

    if ($tot_postos > 0) {  // Se colocar demais, o Javascript pára... Aí pode escolher a cidade
?>
        <hr width='75%' align='center'>

		<script type='text/javascript'>
        //  JavaScript do GoogleMaps
        var map;
		var postos = new Array;
<?php
        $tem_mapa = 1;
        if ($tem_mapa == "1") {
            $centro_mapa = 0;
            for ($i = 0; $i < $tot_postos; $i++) {
                list($posto, $uf, $nome, $endereco, $cidade, $cep) = array_map(utf8_encode,(pg_fetch_row($resPosto)));
				echo "\tpostos.push(new Array('$nome','$endereco'));\n";
// 				$attr = ($endereco[0] == '-') ? " onClick=\"map.setZoom(14);map.panTo(new GLatLng($endereco), 14);\"" : "onClick=\"geoCode('$endereco', '$nome', true);\"";//mostraPosto(\"$endereco\", \"$nome\");
				$attr = "onClick=\"mostraPosto('$endereco', '$nome');\"";
				$cep = preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep);
				$tabela.= "<tr><td style='cursor:pointer' $attr>$nome</td><td>$cidade</td><td>$cep</td><td align='center'>$uf</td></tr>\n";
				$tabela_xls.= "<tr><td>$nome</td><td>$cidade</td><td>$cep</td><td align='center'>$uf</td></tr>\n";
			}
		}
?>

		function mostraPosto(coord_posto, nome) {
			coords = parseLatLng(coord_posto);
			if (coords == null) {
				geoCode(coord_posto, nome, true);
// 						map.setZoom(14);
			} else {
				map.setZoom(15);
				map.panTo(coords, 15);
			}
		}

		function geoCode(address, nome, marca) {
		  var geocoder = new GClientGeocoder();
		  if (geocoder) {
		    geocoder.getLatLng(
		      address,
		      function(point) {
		        if (!point) {
// 				          alert(address + " not found");
		        } else {
// 				          map.setCenter(point, 13);
		          var marker = new GMarker(point);
					if (marca == true) {
			          map.addOverlay(marker);
						map.setCenter(point);
						map.setZoom(14);
				        marker.openInfoWindowHtml(nome);
					} else {
						GEvent.addListener(marker, 'click', function() {
					        marker.openInfoWindowHtml(nome);
						});
			          map.addOverlay(marker);
				}
// 				          marker.openInfoWindowHtml(address, nome);
		        }
		      }
		    );
		  }
		}

		function parseLatLng(value) {
			value.replace('/\s//g');
			var coords = value.split(',');
			var lat = parseFloat(coords[0]);
			var lng = parseFloat(coords[1]);
			if (isNaN(lat) || isNaN(lng)) {
				return null;
			} else {
				return new GLatLng(lat, lng);
			}
		}

        function loadMap() {
            if (GBrowserIsCompatible()) {
                map = new GMap2(document.getElementById("Gmapa"));
                map.addControl(new GLargeMapControl());
                map.addControl(new GMapTypeControl());
				map.setCenter (new GLatLng(-15.815279,-48.070252,0),4);

				function novaMarca(ponto, texto) {
					var marker = new GMarker(ponto);
					GEvent.addListener(marker, 'click', function() {
						marker.openInfoWindowHtml(texto);
					});
					return marker;
				}

				var p;
				var ll = 0;
				var adr= 0;
				var tot = postos.length;
				for (p=0; p<tot; p++) {
// 					document.write("<p>"+postos[i++][0]+"</p>");
					var posto_nome  = postos[p][0];
					var posto_coords= postos[p][1];
					if (posto_coords != undefined) {
						var coords = parseLatLng(posto_coords);
						if (coords == null) {
// alert("Processando endereço "+posto_coords);
							if (posto_coords.length > 10) geoCode(posto_coords, posto_nome);
							adr++;
						} else {
// alert("Processando posição do posto "+posto_nome);
	  						map.addOverlay(novaMarca(coords, posto_nome));
							ll++;
						}
					}
				}
            }
		}
  	</script>
<?php
    }

$arquivo = "xls/mapa_rede.xls";

if (file_exists($arquivo)) {
	unlink($arquivo);
}

$file = fopen($arquivo, "w");
fwrite($file, "<table>" . utf8_decode($tabela_xls) . "</table>");
fclose($file);

?>
    <center>
        <div id="Gmapa" style="width: 700px; height: 400px"></div>
		<p>&nbsp;</p>
		<table>
		<thead>
			<tr style='text-align:left'>
				<th>Razão Social</th>
				<th>Cidade</th>
				<th>CEP</th>
				<th>Estado</th>
			</tr>
		</thead>
		<tbody style='text-align:left'><?=$tabela?></tbody>
		</table>
        <div style="width: 700px; margin-top: 20px;"><a href="<?php echo $arquivo; ?>">Arquivo Excel (XLS)</a></div>
    </center>
</div>

<?php include "inc_footer.php" ?>
