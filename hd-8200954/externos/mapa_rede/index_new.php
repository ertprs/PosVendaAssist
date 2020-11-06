<?php
include '/var/www/assist/www/dbconfig.php';
include '/var/www/includes/dbconnect-inc.php';
include 'mlg_funciones.php';

// AJAX
if ($_GET['action']=='cidades') {
	$fabrica = $_GET['fabrica'];
	if ($fabrica == "") {echo "Erro na consulta!"; exit;}
	$estado = $_GET['estado'];
	if ($estado == "") {echo "Erro na consulta!"; exit;}

	if(strlen($estado) > 0) {   // SELECIONA AS CIDADES
		$sql_cidades = "   SELECT  LOWER(mlg_cidade) AS cidade
							FROM (SELECT posto,tipo_posto,UPPER(TRIM(TRANSLATE(contato_cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
																							  'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
														AS mlg_cidade,
										contato_estado	AS mlg_estado
							FROM tbl_posto_fabrica JOIN tbl_posto USING (posto)
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
			list($cidade_i,$cidade_c) = preg_split('/#/',htmlentities(pg_fetch_result($res_cidades, $i, cidade)));
			$sel      = ($tot_i == 1)?" SELECTED":"";
			echo "\t\t\t<OPTION value='$cidade_i'$sel>".ucwords($cidade_i." ".$cidade_c)."</OPTION>\n";
		}
		if ($tot_i==0) echo "<OPTION SELECTED>Sem resultados</OPTION>";
	}
	exit;
}

if ($_GET['action']=='postos') {    // SELECIONA OS POSTOS CREDENCIADOS DA CIDADE $cidade
	$fabrica = $_GET['fabrica'];
	if ($fabrica == "") {echo "Erro na consulta!"; exit;}
	$estado = $_GET['estado'];
	if (isset($_GET['cidade'])) $cidade=strtoupper(utf8_decode($_GET['cidade']));
	if ($estado == "" or $cidade=="") {echo "Erro na consulta!"; exit;}
	list ($logo,$site) = pg_fetch_row(pg_query($con,"SELECT logo,site FROM tbl_fabrica WHERE fabrica=$fabrica"),0);
	$sql = "SELECT
				tbl_posto.posto,
				tbl_posto_fabrica.codigo_posto,
				TRIM(tbl_posto_fabrica.contato_endereco) AS endereco,
				tbl_posto_fabrica.contato_numero         AS numero,
				TRIM(tbl_posto.nome)                     AS nome,
				LOWER(TRIM(TRANSLATE(
					tbl_posto_fabrica.contato_cidade,
					'¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
					'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á')))             AS cidade,
				tbl_posto_fabrica.contato_estado         AS estado,
				tbl_posto_fabrica.contato_bairro         AS bairro,
				tbl_posto_fabrica.contato_cep            AS cep,
				tbl_posto_fabrica.nome_fantasia,
				tbl_posto.latitude,
				tbl_posto.longitude,
				TRIM(tbl_posto_fabrica.contato_email)    AS email,
				tbl_posto_fabrica.contato_fone_comercial AS fone
			FROM  tbl_posto
			JOIN  tbl_posto_fabrica USING (posto)
			JOIN  tbl_fabrica       USING (fabrica)
			WHERE tbl_posto_fabrica.fabrica = $fabrica
			AND tbl_posto_fabrica.contato_estado  = '$estado'
			AND (tbl_posto.cidade_pesquisa ILIKE '%".tira_acentos($cidade)."%'
			OR  UPPER(TRIM(TRANSLATE(contato_cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
													 'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
					ILIKE '%".tira_acentos($cidade)."%')
			AND tbl_posto.posto not in(6359,20462)
			AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			/*AND tbl_posto_fabrica.tipo_posto <> 163*/
			AND tbl_posto_fabrica.atende_consumidor IS NOT FALSE
			ORDER BY tbl_posto_fabrica.contato_bairro, tbl_posto.nome";

		$res = pg_query ($con,$sql);
		$total_postos = ($tem_mapa=pg_num_rows($res));
		$cidade = pg_fetch_result($res, $total_postos-1, cidade);
?>
	<table align='center' id='postos'>
		<caption>
<?
		if ($logo and $site) echo "<a href='$site'><img src='/assist/logos/$logo'></a><br>";
		echo "Rela&ccedil;&atilde;o de Postos ";
		echo ($cidade<>"")?"da cidade de <span class='nome_cidade'>".change_case($cidade,'l')."</span>, ":"";
		foreach ($estados_BR_prefixo as $pre => $pre_estado) {
			if (array_search($estado, $pre_estado) <> false) {echo $pre; break;}
		}
		echo " ".$estados[$estado];
		echo "\t</caption>";

		if($total_postos > 0){?>
		<thead>
			<tr align='center' class='bold'>
				<th width='240'>Nome do Posto</th>
				<th width='260'>Endere&ccedil;o</th>
				<th width='150'>E-Mail</th>
				<th width= '98'>Telefone</th>
				<th width= '32'>Mapa</th>
				<th width='180'>Linhas</th>
			</tr>
		</thead>
<?
			for ($i = 0 ; $i < $total_postos ; $i++) {
				$row = pg_fetch_array($res, $i);
				foreach ($row as $campo => $valor) {
					$$campo = trim($valor);
				}
				$end_completo = $endereco . ", " . $numero  . " - " . $bairro;
				$end_mapa     = "$endereco, $numero, $cep, $cidade, $estado, Brasil";
				if (is_numeric($longitude) and is_numeric($latitude)) { // lat e long est„o ao contr·rio no banco
					$end_mapa.= "&ll=$longitude,$latitude";
				}
// 					$link_mapa = "<a title='Localizar no mapa' href='http://maps.google.com/maps?f=q&source=s_q&hl=pt-BR&geocode=&q=$longitude,$latitude&sll=$end_mapa&ie=windows-1252' target='_blank'>";
// 				}else {
					$link_mapa = "<a title='Localizar no mapa' href='http://maps.google.com/maps?f=q&source=s_q&hl=pt-BR&q=$end_mapa&ie=windows-1252' target='_blank'>";
//  				}
				$link_mapa.= "<img src='http://www.impressionar.com.br/multimidia/_arquivos/mapa.jpg' width='16'></a>";

				echo "\t\t<tr>";
				$posto_nome = iif((strlen($nome_fantasia)>0),$nome_fantasia,$nome);
				$tooltip .= " title='".iif(($posto_nome==$nome_fantasia),"$posto_nome ($nome)",
										iif((strlen($posto_nome)>=40),"$posto_nome",""))."'";
				echo "\t\t\t<td$tooltip>$posto_nome</td>";
				$tooltip = (strlen($end_completo)>=44)?" title='$end_completo'":"";
				echo "\t\t\t<td$tooltip>$end_completo</td>";
				$tooltip = (strlen($email)>=30)?" title='$email'":"";
				echo "\t\t\t<td$tooltip>";
				if (strlen($email)>5 and strpos($email,"@",1)>0) {
					echo "<a href='mailto:".strtolower($email)."'>$email</a>";
				} else {
					echo "<i>sem e-mail</i></td>";
				}
				echo "\t\t\t<td><a href='callto:$fone'>$fone</a></td>";
				echo "\t\t\t<td>$link_mapa</td>";

//  Linhas que o Posto Autorizado atende...
					$sql_linhas = "SELECT tbl_linha.nome FROM tbl_posto_linha
									 JOIN tbl_linha USING(linha)
									WHERE tbl_posto_linha.ativo IS NOT FALSE
									  AND posto = $posto AND fabrica=$fabrica";
					$res2 = pg_query ($con,$sql_linhas);
					if(pg_numrows($res2) > 0){
// 						$linhas = "<DIV>Esta assistÍncia atende...<br>";
						$linhas = "<SELECT readonly><option>Esta assistÍncia atende...</option>";
						for ($x = 0 ; $x < pg_numrows ($res2) ; $x++) {
// 							$linhas .= "... ".trim(pg_fetch_result($res2,$x,nome))."<br>\n";
							$linhas .= "<option>... ".trim(pg_fetch_result($res2,$x,nome))."</option>\n";
						}
// 						$linhas.= "</DIV>\n";
					}
				echo "\t\t\t<td>$linhas</td>";
				echo "\t\t</tr>";
				unset ($end_mapa, $link_mapa, $tooltip, $end_completo, $posto_nome, $linhas);
			}
		}else{
			echo "\t<tr><td class='fontenormal'> Nenhuma AssistÍncia TÈcnica encontrada.</td></tr>";
		}
		echo "</table>\n<br>";
	exit;
}
//  FIM AJAX

$html_titulo = "TELECONTROL - Mapa das Redes Autorizadas";

//  Salva as f·bricas num array
$sql = "SELECT tbl_fabrica.fabrica,tbl_fabrica.nome FROM tbl_fabrica ".
		"WHERE fabrica NOT IN(0,4,9,10,12,13,16,17,18,21,22,23,26,27,28,29,31,33,34,35,36,38,40,41,44,46,48,49,55,56,57,58,60,61,62,63,64,65,67,68,69,70,71,72,73,74,75,76,77,78)".
		"ORDER BY nome";
$res = pg_query($con, $sql);
$tmp_fabricas = pg_fetch_all($res);

foreach ($tmp_fabricas as $value) {
	$tmp_fabrica= $value['fabrica'];
	$tmp_nome   = $value['nome'];
	$combo_fabricas .= "\t\t\t\t\t<OPTION value='$tmp_fabrica'>$tmp_nome</OPTION>\n";
}
unset ($tmp_fabricas,$tmp_fabrica,$tmp_nome);

include "../new/inc_header.php";
$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
$resInsertLog = pg_query($con, $sqlInsertLog);

?>

<!-- CSS -->
<style type="text/css">
<!--
#estados, #sel_cidade, #tblres {display: none;}
#sel_cidade {position: relative; top: 2em;}

h2 {
	padding-left: 1em;
	font: normal bold 15px helvetica,Arial,sans-serif;
	color: #333;
	text-align: left;
	text-transform: uppercase;
}
#mapabr {position:relative;float: left;height:340px}
	#mapabr span {
		padding: 2px 4px;
		color: white;
		background-color: #A10F15;
		text-shadow: 0 0 0 transparent;
		font: inherit
}
#mapabr h2 {margin-top: 1.5em}

area {cursor: pointer}

#frmdiv {
	margin:		1em;
	text-align: left;
	width:		512px;
}
#frmdiv a img {border: 0 solid transparent;}
#frmdiv label, #frmdiv select {margin-left: 2em;z-index:10}
#frmdiv select {
	width:135px;
	height:18px;
	font-size: 0.9em;
	border: 1px solid #eee;
	background: url(../new/img/inputLogin.gif) no-repeat scroll left top;
	background-color: white;
}
#frmdiv select option {
	background: white url();
	border-top: 1px dotted #eaeaea;
}
#frmdiv fieldset {
	border-radius: 5px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	position: relative;
	height: 365px;
	width: 500px;
}
#frmdiv button {
	position: absolute;
	top: 60%;
	left: 75%;
}

#tblres {
	position: relative;
	clear: both;
}
#tblres table {
	position: relative;
	table-layout: fixed;
	margin: 0;
	background-color: transparent;
	padding: 0;
	width: 800px;
	overflow-x: hidden;
}
#tblres table td {
	position: relative;
	background: #ddd;
/*	font-weight: bold;*/
	padding: 4px 2px;
	border:	1px solid #666;
	border-right:  1px solid #eee;
	border-bottom: 1px solid #eee;
	color: black;
	white-space: nowrap;
	overflow: hidden;
	text-transform: uppercase;
	cursor: default;
}

#tblres td a {
	padding-left: 1ex;
	text-decoration: none;
	color:#114;
	text-transform: lowercase
	}
	#tblres td a:hover {border-bottom: 1px dashed #667
}

/*#tblres table td:nth-child(3) {text-transform: lowercase}*/
#tblres table td:nth-child(4) {text-align: right}
#tblres table td:nth-child(5) {text-align: center}
#tblres table td:nth-child(6) {text-align: left}
#tblres table td:nth-child(6):hover {overflow: visible}

#tblres table td div {
	position:relative;
	z-index:5;
	width: 165px;
	height:1.1em;
	overflow: hidden;
	}
#tblres table td div p {
	position: absolute;
	top: 1px; left: 2px;
	}
	#tblres table td div:hover {
		background-color: white;
		height: auto;
		border: 1px dashed #335;
		overflow: visible;
}

#tblres td select {
	border: 0;
}

#tblres table caption {
	font: normal bold 1.2em "Trebuchet MS",Helvetica,Arial,sans-serif;
	padding-bottom: 4px;
	border-bottom: 1px solid white;
	text-align: center;
}
#tblres td select {margin-left: 2px;text-transform:capitalize;max-width: 98%;font-size: 0.95em}

.nome_cidade {text-transform:capitalize;text-decoration:underline; font: inherit}

.cinza {#335}
.bold {
	font-weight: bold;
}
//-->
</style>

<!-- JavaScript -->
<!--[if lt IE 8]>
	<script src="http://ie7-js.googlecode.com/svn/version/2.0(beta3)/IE8.js" type="text/javascript"></script>
<![endif]-->
<script src="jquery-1.3.2.min.js" type="text/javascript"></script>

<script type="text/javascript">
jQuery(document).ready(function() {
//  Adiciona um evento onClick para cada 'area' que vai alterar o valor do SELECT 'estado'
	jQuery('button').attr('disabled','disabled');
	jQuery('#mapabr').css('opacity',0.3);
	jQuery('map area').click(function() {
		if (jQuery('#fabrica').val()=="") return false;  // N„o roda se n„o tem f·brica selecionada
		jQuery('#estado').val(jQuery(this).attr('name'));
		jQuery('#estado').change();
	});
	jQuery('#sel_cidade').hide('fast');

//  Quando muda o valor do select 'estado' requisita as cidades onde tem postos autorizados e os
//  insere no select 'cidades'
	jQuery('#fabrica').change(function() {
		var fabrica = jQuery(this).val();
		if (fabrica == '') {
			jQuery('#cidades').val('').change();
			jQuery('#estado').val('').change();
			jQuery('#estados').hide();
			jQuery('#mapabr').css('opacity',0.3);
			jQuery('button').attr('disabled','disabled');
			return false;
		} else {
			jQuery('#cidades').val('').change();
			jQuery('#estados').slideDown('fast');
			jQuery('#estado').val('').change();
			jQuery('#mapabr').css('opacity',1);
			jQuery('button').removeAttr('disabled');
		}
	});
	jQuery('#estado').change(function() {
		var fabrica= jQuery('#fabrica').val();
		var estado = jQuery('#estado').val();
		if (estado == '') {
			jQuery('#sel_cidade').hide(500);
			jQuery('#tblres').html('').fadeOut(400);
			return false;
		}
		jQuery.get("<?=$PHP_SELF?>", {'action': 'cidades','fabrica':fabrica,'estado': estado},
		  function(data){
			if (data.indexOf('Sem resultados') < 0) {
				jQuery('#sel_cidade').show(500);
				jQuery('#cidades').html(data).val('').removeAttr('disabled');
				if (jQuery('#cidades option').length == 2) {
					jQuery('#cidades option:last').attr('selected','selected');
					jQuery('#cidades').change();
				}
			} else {
				jQuery('#cidades').html(data).val('Sem resultados').attr('disabled','disabled');
			}
			jQuery('#tblres').html('').fadeOut(400);
		  });
	});

	jQuery('#cidades').change(function() {
		jQuery('#tblres').fadeOut('fast');
		var fabrica= jQuery('#fabrica').val();
		var estado = jQuery('#estado').val();
		var cidade = jQuery('#cidades').val();
		if (cidade) {
			jQuery.get("<?=$PHP_SELF?>", {'action': 'postos','fabrica':fabrica,'estado': estado,'cidade': cidade},
			  function(data){
				if (data.indexOf('Nenhuma') < 0) {
					jQuery('#tblres').html(data).fadeIn(500);
				}
			  });
		}
	});
	
	jQuery('button').click(function () {
	   jQuery('#sel_cidade').val('').change();
	   jQuery('#estado').val('').change();
	   jQuery('#fabrica').val('').change();
	});
}); // FIM do jQuery
</script>
</head>

<center>
	<div id='frmdiv'>
		<form name='frm_mapa_rede_gama' action='<?=$PHP_SELF?>' method='post'>
			<fieldset for="frm_mapa_rede_gama">
				<legend>Pesquisa de Postos Autorizados</legend>
				<div id='mapabr'>
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
					<img src="/mapa_rede/imagens/mapa_azul.gif" usemap="#Map2" border="0">
				</div>
				<label for='fabrica'>Selecione a Marca</label><br>
				<select title='Selecione a Marca' name='fabrica' id='fabrica'>
					<option></option>
<?                  echo $combo_fabricas;
?>				</select>
				<p>&nbsp;</p>
				<div id='estados'>
					<label for='estado'>Selecione o Estado</label><br>
					<select title='Selecione o Estado' name='estado' id='estado'>
						<option></option>
<?  				foreach ($estados as $sigla=>$nome) {
						echo "\t\t\t\t<option value='$sigla'>$nome</option>\n";
					}
?>  				</select>
				</div>
				<div id='sel_cidade'>
					<label for='cidades'>Selecione uma cidade</label><br>
					<select title='Selecione uma cidade' name='cidades' id='cidades'>
						<option></option>
					</select>
				</div>
				<p>&nbsp;</p>
				<button type="button" id="limpar">&nbsp;Limpar&nbsp;</button>
			</fieldset>
		</form>
	</div>
</center>
<div id='gmapsd'></div>
<div id='tblres'>&nbsp;</div>
<?include '../new/inc_footer.php'?>
