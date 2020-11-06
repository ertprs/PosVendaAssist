<?php
include '/var/www/assist/www/dbconfig.php';
include '/var/www/includes/dbconnect-inc.php';
include '/var/www/telecontrol/www/mapa_rede/mlg_funciones.php';

//	AJAX
if ($_GET['action']=='cidades') {
	$estado = $_GET['estado'];
	$fabrica= $_GET['fabrica'];
	if ($estado == "" or $fabrica=="") {echo "Erro na consulta!"; exit;}
	if(strlen($estado) > 0) {   // SELECIONA AS CIDADES
		$sql_cidades =	"SELECT  LOWER(mlg_cidade) AS cidade
							FROM (SELECT posto,tipo_posto,UPPER(TRIM(TRANSLATE(contato_cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
																							  'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
														AS mlg_cidade,
										contato_estado	AS mlg_estado
							FROM tbl_posto_fabrica
								WHERE credenciamento='CREDENCIADO'
									AND tbl_posto_fabrica.posto NOT IN(6359,20462)
									AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
									AND tbl_posto_fabrica.tipo_posto <> 163
									AND contato_estado='$estado' AND fabrica=$fabrica) mlg_posto
							GROUP BY mlg_posto.mlg_cidade ORDER BY cidade ASC";
		$res_cidades = pg_query($con,$sql_cidades);
		$tot_i       = pg_num_rows($res_cidades);
        echo "<OPTION></OPTION>";
        for ($i; $i<$tot_i; $i++) {
            list($cidade_i,$cidade_c) = split("#",htmlentities(pg_fetch_result($res_cidades, $i, cidade)));
            $sel      = ($tot_i == 1)?" SELECTED":"";
			echo "\t\t\t<OPTION value='".strtoupper($cidade_i)."'$sel>".ucwords($cidade_i." ".$cidade_c)."</OPTION>\n";
        }
        if ($tot_i==0) echo "<OPTION SELECTED>Sem resultados</OPTION>";
	}
	exit;
}
//  FIM AJAX
?>
<!-- CSS -->
<style type="text/css">
<!--
div#mapa_pesquisa {
	font-family: sans-serif, Verdana, Geneva, Arial, Helvetica;
	font-size: 11px;
	line-height: 1.2em;
	color:#88A;
    background: white;
	top: 0;
	left: 0;
	padding: 30px 10px 15px 10px;
/*  display: none;  */
}

h2 {
	padding-left: 1em;
	font: normal bold 15px helvetica,Arial,sans-serif;
	color: #333;
	text-align: left;
    text-transform: uppercase;
}
#mapabr {position:relative;float: left;top: -1em;height:340px}
	#mapabr span {
		padding: 2px 4px;
		color: white;
		background-color: #A10F15;
	    text-shadow: 0 0 0 transparent;
		font: inherit
	}
	#mapabr h2 {margin-top: 1.5em}
	#mapabr area {cursor: pointer}
	#mapabr fieldset {
		border-radius: 5px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		height: 365px;
		width: 500px;
}

#frmdiv {
	float: left;
	margin: 1em;
	text-align: left;
	width: 512px;
}

.cinza {#667}
.bold {
	font-weight: bold;
}
//-->
</style>

<!-- JavaScript -->
<!--[if lt IE 8]>
	<script src="http://ie7-js.googlecode.com/svn/version/2.0(beta3)/IE8.js" type="text/javascript"></script>
<![endif]-->
<script src="js/jquery-1.3.2.js" type="text/javascript"></script>

<script type="text/javascript">
$(document).ready(function() {
//  Adiciona um evento onClick para cada 'area' que vai alterar o valor do SELECT 'estado'
	$('#mapabr map area').click(function() {
		$('#estado').val($(this).attr('name'));
		$('#estado').change();
	});
	$('#sel_cidade').hide('fast');

//  Quando muda o valor do select 'estado' requisita as cidades onde tem postos autorizados e os
//  insere no select 'cidades'
	$('#estado').change(function() {
	    var estado = $('#estado').val();
	    if (estado == '') {
			$('#sel_cidade').hide(500);
			$('#tblres').html('').fadeOut(400);
			return false;
		}
		$.get("cidade_mapa_rede.php", {'action': 'cidades','estado': estado},
		  function(data){
			if (data.indexOf('Sem resultados') < 0) {
				$('#sel_cidade').show(500);
			    $('#cidades').html(data).val('').removeAttr('disabled');
				if ($('#cidades option').length == 2) {
	                $('#cidades option:last').attr('selected','selected');
	                $('#cidades').change();
				}
			} else {
			    $('#cidades').html(data).val('Sem resultados').attr('disabled','disabled');
			}
			$('#tblres').html('').fadeOut(400);
		  });
	});

	$('#cidades').change(function() {
		$('#tblres').fadeOut('fast');
	    var estado = $('#estado').val();
		var cidade = $('#cidades').val();
		$.get("<?=$PHP_SELF?>", {'action': 'postos','estado': estado,'cidade': cidade},
		  function(data){
		    if (data.indexOf('Nenhuma') < 0) {
				$('#tblres').html(data).fadeIn(500);
			}
		  });
	});
}); // FIM do jQuery
</script>

<div id='mapa_pesquisa'>
<h2>Mapa da Rede de AssistÍncia TÈcnica</h2>
	<div id='frmdiv'>
		<form name='frm_mapa_rede_gama' action='<?=$PHP_SELF?>' method='post'>
			<fieldset for="frm_mapa_rede_gama">
				<legend>Pesquisa de Postos Autorizados</legend>
				<div id='mapabr'>
					<h2><img src="dynacom_logo.png"></h2>
					<map name="Map2">
						<area shape="poly" name="RS" coords="122,238,142,221,164,232,148,262">
						<area shape="poly" name="SC" coords="143,214,172,215,169,235,143,219">
						<area shape="poly" name="PR" coords="138,202,148,191,166,192,175,207,171,214,139,213">
						<area shape="poly" name="SP" coords="152,187,162,173,182,174,186,187,188,194,197,190,197,198,177,206,168,190">

						<area shape="poly" name="MS" coords="136,195,156,171,138,159,124,159,117,182">
						<area shape="poly" name="MT" coords="117,151,143,151,160,127,160,106,120,105,111,101,98,102,107,117,100,131,102,142">
						<area shape="poly" name="RO" coords="93,126,98,118,94,113,86,105,86,100,80,93,73,102,67,108,67,116,77,121">
						<area shape="poly" name="AC" coords="50,106,10,91,13,101,23,104,29,104,30,112,44,113">
						<area shape="poly" name="AM" coords="11,87,53,101,74,88,105,91,117,55,103,43,89,50,76,43,77,30,62,37,43,30,40,38,33,75,21,75,13,82">
						<area shape="poly" name="RR" coords="74,13,74,18,82,25,84,41,93,40,102,31,96,21,97,9,90,11">
						<area shape="poly" name="PA" coords="112,33,114,40,127,50,117,82,121,95,162,99,174,77,173,68,193,48,172,54,158,55,145,45,133,25">
						<area shape="poly" name="AP" coords="145,25,153,23,157,13,164,29,153,41">
						<area shape="poly" name="MA" coords="196,50,185,72,194,90,212,82,215,59">

						<area shape="poly" name="TO" coords="179,83,165,120,189,128,185,101">
						<area shape="poly" name="GO" coords="159,166,148,157,165,131,188,136,170,151">
						<area shape="poly" name="PI" coords="201,92,216,86,223,64,228,85,219,98,207,99,206,107,199,107">
						<area shape="poly" name="RJ" coords="206,201,202,190,214,189,218,181,226,187">
						<area shape="poly" name="MG" coords="171,164,190,162,192,145,205,140,217,146,224,154,217,169,212,183,193,183,185,170">
						<area shape="poly" name="ES" coords="236,167,228,162,221,177,226,183">
						<area shape="poly" name="BA" coords="198,113,196,134,213,133,230,139,235,146,231,157,235,160,240,142,241,127,249,124,243,113,243,105,234,106,225,107,215,107,207,115">
						<area shape="poly" name="CE" coords="230,59,235,86,241,86,252,70,239,61">
						<area shape="poly" name="SE" coords="250,108,248,113,251,118,257,113,252,109">

						<area shape="poly" name="AL" coords="266,102,258,104,251,102,260,110,266,104">
						<area shape="poly" name="PE" coords="269,94,269,99,262,99,256,101,251,98,246,98,239,96,234,100,231,95,234,92,243,93,251,94,255,96">
						<area shape="poly" name="PB" coords="269,85,262,85,257,88,253,85,248,87,257,90,263,91,268,89">
						<area shape="poly" name="RN" coords="256,73,249,81,256,80,257,83,270,82,265,76">
						<area shape="poly" name="DF" coords="168,162,171,153,183,149,182,161">
					</map>
					<p style='textalign: right; font-weight: bold;'>Selecione o Estado:</p>
					<img src="dynacom_mapa.gif" usemap="#Map2" border="0">
				</div>
				<label for='estado'>Selecione o Estado</label><br>
				<select title='Selecione o Estado' name='estado' id='estado'>
					<option></option>
<?				foreach ($estados as $sigla=>$nome) {
					echo "\t\t\t\t<option value='$sigla'>$nome</option>\n";
				}
?>				</select>
				<div id='sel_cidade'>
		            <label for='cidades'>Selecione uma cidade</label><br>
					<select title='Selecione uma cidade' name='cidades' id='cidades'>
						<option></option>
					</select>
				</div>
			</fieldset>
		</form>
	</div>
</body>
</html>