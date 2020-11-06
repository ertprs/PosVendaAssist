<?
include '/var/www/assist/www/dbconfig.php';
include '/var/www/includes/dbconnect-inc.php';
include "trad_site/fn_ttext.php";



function tira_acentos ($texto) {
// 	$str  = utf8_decode($texto);
	$from = utf8_decode("áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ");
	$to	  = "aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC";
	return strtr($texto,$from,$to);
}

function change_case($texto, $l_u = 'lower') {
	$acentos      = array("lower"	=> "áâàãäéêèëíîìïóôòõúùüç",
						  "upper"	=> "ÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ");
    if ($l_u[0] == 'l') {
		return strtr(strtolower($texto), $acentos['upper'], $acentos['lower']);
	} else {
		return strtr(strtoupper($texto), $acentos['lower'], $acentos['upper']);
    }
}

$html_titulo	= $a_trad_mapa["titulo"][$cook_idioma];
$body_options	= " onload='load()' onunload='GUnload()'";

include "inc_header.php";
?>
<div id='conteiner'>
    <h2 id='mapa'>&nbsp;</h2>
	<form name="frm_mapa" action="<? echo $PHP_SELF ?>" method="post">
	<fieldset class='colunas'> <? # style='width:60%;margin-left: 20%;min-width: 500px' ?>
		<legend><?=ttext($a_trad_mapa, "procurarPA") ?></legend>
		<p>
			<label><?=ttext($a_trad_mapa, "Marca") ?></label>
			<select name="fabrica" size="1">
				<option value="">---</option>
<?
//	02/12/2009 MLG - Mudando para o campo ativo_fabrica da tbl_fabrica, ao invés de usar o array
//      "WHERE fabrica NOT IN(0,4,9,10,12,13,16,17,18,21,22,23,26,27,28,29,31,33,34,35,36,38,40,41,44,46,48,49,55,56,57,58,60,61,62,63,64,65,67,68,69,70,71,72,73,74,75,76,77,78)".
// HD 205844 Marcos pediu para tirar Lorenzetti na consulta, ele diz que já solicitou anteriormente, talvez quem colocou novo layout não viu.
		$sql = "SELECT fabrica, nome, logo, site FROM tbl_fabrica ".
        "WHERE fabrica not in (10,19)".
        "AND ativo_fabrica	IS TRUE ".
        "AND logo			IS NOT NULL ".
        "ORDER BY nome";
		$res = pg_query ($con,$sql);
		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$x_fabrica = utf8_encode(pg_fetch_result ($res,$i,fabrica));
			$x_nome    = utf8_encode(pg_fetch_result ($res,$i,nome));
			$logo[$x_fabrica] = trim(pg_fetch_result ($res,$i,logo));
			$site[$x_fabrica] = trim(pg_fetch_result ($res,$i,site));
			echo "\t\t\t\t<option value=$x_fabrica>$x_nome</option>\n";
		}
?>			</select>
			<br />
			<label><?=ttext($a_trad_mapa, "Estado") ?></label>
			<select name="estado" size="1">
				<option></option>
				<option value="AC">Acre</option>
				<option value="AL">Alagoas</option>
				<option value="AP">Amapá</option>
				<option value="AM">Amazonas</option>
				<option value="BA">Bahia</option>
				<option value="CE">Ceará</option>
				<option value="DF">Distrito Federal</option>
				<option value="ES">Espírito Santo</option>
				<option value="GO">Goiás</option>
				<option value="MA">Maranhão</option>
				<option value="MT">Mato Grosso</option>
				<option value="MS">Mato Grosso do Sul</option>
				<option value="MG">Minas Gerais</option>
				<option value="PA">Pará</option>
				<option value="PB">Paraíba</option>
				<option value="PR">Paraná</option>
				<option value="PE">Pernambuco</option>
				<option value="PI">Piauí</option>
				<option value="RJ">Rio de Janeiro</option>
				<option value="RN">Rio Grande do Norte</option>
				<option value="RS">Rio Grande do Sul</option>
				<option value="RO">Rondônia</option>
				<option value="RR">Roraima</option>
				<option value="SC">Santa Catarina</option>
				<option value="SP">São Paulo</option>
				<option value="SE">Sergipe</option>
				<option value="TO">Tocantins</option>
			</select>
			<br />
<?

	$estado = $_POST['estado'];
	$fabrica = $_POST['fabrica'];
	if(strlen($estado) > 0) {   // SELECIONA AS CIDADES





	    $max_cidades = 150;
		$sql_cidades =	"SELECT DISTINCT LOWER(TRIM(contato_cidade))
										AS contato_cidade,
								LOWER(TRANSLATE(CASE WHEN tbl_posto.cidade_pesquisa IS NOT NULL
								     THEN tbl_posto.cidade_pesquisa
									 ELSE tbl_posto.cidade
									 END, '".utf8_decode("áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ")."',".
									 "'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC'))
									   AS cidade_pesquisa
								FROM tbl_posto
								JOIN tbl_posto_fabrica USING (posto)
									WHERE credenciamento='CREDENCIADO'
										AND posto NOT IN(6359,20462)
										AND tipo_posto <> 163
										AND divulgar_consumidor IS TRUE
										AND contato_estado='$estado' AND fabrica=$fabrica
									ORDER BY contato_cidade ASC";
#		$sql_cidades = "SELECT nome from tbl_fabrica where fabrica = $fabrica and 1=1";
		
		$res_cidades = pg_query($con,$sql_cidades);
		$tot_i       = pg_num_rows($res_cidades);
		
echo "aqui2 $tot_i" . $sql_cidades;
echo pg_errormessage ($con);


		
		
		if ($tot_i != 0) { ?>
		<label alt='cidade'><?=ttext($a_trad_mapa, "Cidade") ?></label>
	        <select name='cidade'>
				<option value=''><?=ttext($a_trad_mapa, "Todas") ?></option>
<?	        for ($i=0; $i<$tot_i; $i++) {
	            $contato_cidade = pg_fetch_result($res_cidades, $i, cidade_pesquisa);
	            $cidade_i = change_case(utf8_encode(pg_fetch_result($res_cidades, $i, contato_cidade)));
		        $sel = ($cidade == $cidade_i or $tot_i == 1)?" SELECTED":"";
				echo "\t\t\t\t<option value='$contato_cidade'$sel>".ucwords($cidade_i)."</option>\n";
	        }
	        echo "\t\t\t</select>\n";
        }
	}
?>&nbsp;&nbsp;&nbsp;
		</p>
		<p>&nbsp;</p>
		<p>&nbsp;</p>
		<p>
		<input type="submit" name="btn_mapa" value="<?=ttext($a_trad_mapa, "Pesquisar") ?>" />
          &nbsp;&nbsp;&nbsp;&nbsp;
		<input type="button" name="limpar" value="<?=ttext($a_trad_mapa, "Limpar") ?>" />
		</p>
	</fieldset>
	</form>
<?
$tem_mapa = 0;
if (strlen ($estado) > 0) {
	$sql = "SELECT tbl_posto.posto,
				   TRIM (tbl_posto.nome) AS nome,
				   TRIM (tbl_posto.endereco) AS endereco,
				   tbl_posto.numero,
				   tbl_posto.fone,
				   tbl_posto.cidade,
				   tbl_posto.cep,
				   tbl_posto.estado,
				   tbl_posto.latitude,
				   tbl_posto.longitude
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica USING (posto)
			 WHERE credenciamento = 'CREDENCIADO'
			   AND posto NOT IN(6359,20462)
			   AND tipo_posto <> 163
			   AND divulgar_consumidor IS TRUE
			   AND fabrica = $fabrica
			   AND tbl_posto.estado = '$estado' ";
	$sql.= ($cidade == "") ? $cidade : "			AND 				LOWER(TRANSLATE(CASE WHEN tbl_posto.cidade_pesquisa IS NOT NULL
				     THEN tbl_posto.cidade_pesquisa
					 ELSE tbl_posto.cidade
					 END, '".utf8_decode("áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ")."',".
					 "'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC'))
					   ~* '$cidade' ";
	$sql.= "ORDER BY tbl_posto.cidade, tbl_posto.cep";
	$resPosto = pg_query ($con,$sql);
	$tot_postos = pg_num_rows($resPosto);
// echo "<div class='";
// echo ($resPosto === false)?"erro":"msg";
// echo "'>SQL: $sql<p>Total: $tot_postos</p></div><br>\n";
	if ($tot_postos > 0 and $tot_postos < $max_cidades) {  // Se colocar demais, o Javascript pára... Aí pode escolher a cidade
		$tem_mapa = 1;
?>
		<hr width='75%' align='center'>
		<center>
		<b><?=ttext($a_trad_mapa, "clicar_marcas") ?></b>
		<br />
		+) <?=ttext($a_trad_mapa, "posto_sem_mapa") ?>
		<br />
		+) <?=ttext($a_trad_mapa, "margem_erro") ?>

		<center>
        <a href="<?=$site[$fabrica]?>" target="_blank" title="Visite o site">
			<img  src='/assist/logos/<?=$logo[$fabrica]?>'
				style='margin: 1.5em 0 1em;max-height:80px;'>
        </a>
		</center>
<?
//  JavaScript do GoogleMaps
echo "<script type='text/javascript'>";
?>
	var map;
	function load() {
		if (GBrowserIsCompatible()) {
			map = new GMap2(document.getElementById("Gmapa"));
			map.addControl(new GLargeMapControl());
			map.addControl(new GMapTypeControl());
<?
			if ($tem_mapa == "1") {
				$zoom = ($cidade=="") ? 8 : 11;
				$centro_mapa = "0";
				for ($i = 0 ; $i < pg_num_rows ($resPosto) ; $i++){
					$posto     = utf8_encode(pg_fetch_result ($resPosto,$i,posto));
					$nome      = utf8_encode(pg_fetch_result ($resPosto,$i,nome));
					$endereco  = utf8_encode(pg_fetch_result ($resPosto,$i,endereco));
					$numero    = utf8_encode(pg_fetch_result ($resPosto,$i,numero));
					$fone      = utf8_encode(pg_fetch_result ($resPosto,$i,fone));
					$cidade    = utf8_encode(pg_fetch_result ($resPosto,$i,cidade));
					$estado    = utf8_encode(pg_fetch_result ($resPosto,$i,estado));
					$cep       = utf8_encode(pg_fetch_result ($resPosto,$i,cep));
					$latitude  = utf8_encode(pg_fetch_result ($resPosto,$i,latitude));
					$longitude = utf8_encode(pg_fetch_result ($resPosto,$i,longitude));

					if (strlen ($latitude) > 0 and strlen ($longitude) > 0) {
						if ($centro_mapa == "0"){
							echo "map.setCenter (new GLatLng($longitude,$latitude,0),$zoom);";
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
						echo "var point_$posto = new GLatLng($longitude,$latitude); \n";
						echo "var posto_$posto = new GMarker(point_$posto); \n";
						echo "map.addOverlay(posto_$posto); \n";
						echo "var WINDOW_HTML = '<b>$nome</b> <br> $endereco, $numero <br> fone: $fone  <br> $cidade - $estado - $cep'; \n";
						echo "GEvent.addListener (posto_$posto, \"click\", function(){	";
						echo "posto_$posto.openInfoWindowHtml (\"<b>$nome</b> <br> $endereco, $numero <br> fone: $fone \"); ";
						echo "});";
						echo "\n\n";
					}
				}
			}else{
			    echo "map.setCenter (new GLatLng(-15.815279,-48.070252,0),".$zoom-3 .");";
			}
?>
		}
	}
</script>

<center>
<div id="Gmapa" style="width: 700px; height: 400px"></div>
</center>
<?	}
}
?>
</div>
<?include "inc_footer.php" ?>
