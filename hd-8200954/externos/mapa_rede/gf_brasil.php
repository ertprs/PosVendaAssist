<?php
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../helpdesk/mlg_funciones.php';

/*  06/04/2010 MLG - Tirei o 'ILIKE' da Query que pega a lista de postos, já que a cidade vem de uma pesquisa anterior
					 e o nome é pego já do banco.   */
//	AJAX
$fabrica = "81";

if ($_GET['action']=='cidades') {
	$estado = $_GET['estado'];
	if ($estado == "") exit;
	if(strlen($estado) > 0) {
		$sql_cidades =	"SELECT  LOWER(mlg_cidade)||'#('||count(mlg_cidade)||')' AS cidade
							FROM (SELECT posto,tipo_posto,UPPER(TRIM(TRANSLATE(contato_cidade,'áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ',
																							  'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
														AS mlg_cidade,
										contato_estado	AS mlg_estado
							FROM tbl_posto_fabrica
								WHERE credenciamento<>'DESCREDENCIADO'
									AND tbl_posto_fabrica.posto NOT IN(6359,20462)
									AND tbl_posto_fabrica.tipo_posto <> 163
									AND divulgar_consumidor IS TRUE
									AND contato_estado='$estado' AND fabrica=81) mlg_posto
							GROUP BY mlg_posto.mlg_cidade ORDER BY cidade ASC";
		$res_cidades = pg_query($con,$sql_cidades);
		$tot_i       = pg_num_rows($res_cidades);
        echo "<OPTION></OPTION>";
        for ($i; $i<$tot_i; $i++) {
            list($cidade_i,$cidade_c) =preg_split('/#/',htmlentities(pg_fetch_result($res_cidades, $i, cidade)));
            $sel      = (strtoupper($cidade) == strtoupper($cidade_i))?" SELECTED":"";
			echo "\t\t\t<OPTION value='$cidade_i'$sel>".ucwords($cidade_i." ".$cidade_c)."</OPTION>\n";
        }
        if ($tot_i==0) echo "<OPTION SELECTED>Sem resultados</OPTION>";
	}
	exit;
}

if ($_GET['action']=='postos') {
	$estado = $_GET['estado'];
	if (isset($_GET['cidade'])) $cidade=strtoupper(utf8_decode($_GET['cidade']));
	if ($estado == "" or $cidade=="") {echo "Erro na consulta!"; exit;}
	$fabrica = "81";
	$sql = "SELECT
				tbl_posto.posto,
				tbl_posto_fabrica.codigo_posto,
				TRIM(tbl_posto_fabrica.contato_endereco)	AS endereco,
				tbl_posto_fabrica.contato_numero			AS numero,
                TRIM(tbl_posto.nome)						AS nome,
				LOWER(TRIM(TRANSLATE(tbl_posto_fabrica.contato_cidade,'ÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ',
																'áâàãäéêèëíîìïóôòõúùüç')))
															AS cidade,
				tbl_posto_fabrica.contato_estado			AS estado,
				tbl_posto_fabrica.contato_bairro			AS bairro,
				tbl_posto_fabrica.contato_cep				AS cep,
				tbl_posto_fabrica.nome_fantasia,
                tbl_posto.latitude,
                tbl_posto.longitude,
                TRIM(tbl_posto_fabrica.contato_email)		AS email,
				tbl_posto_fabrica.contato_fone_residencial	AS fone_alternativo,
				tbl_posto_fabrica.contato_fone_comercial	AS fone,
				tbl_posto_fabrica.contato_complemento AS complemento
			FROM   tbl_posto
			JOIN    tbl_posto_fabrica USING (posto)
			JOIN    tbl_fabrica       USING (fabrica)
			WHERE   tbl_posto_fabrica.fabrica = $fabrica
			AND tbl_posto_fabrica.contato_estado ILIKE '$estado'
			AND UPPER(TRIM(TRANSLATE(contato_cidade,'áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ',
													'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
						= '".tira_acentos($cidade)."'
			AND tbl_posto.posto not in(6359,20462)
			AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
			AND tbl_posto_fabrica.tipo_posto <> 163
			AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
			ORDER BY tbl_posto_fabrica.contato_bairro, tbl_posto.nome";
		$res = pg_query ($con,$sql);
		$total_postos = ($tem_mapa=pg_num_rows($res));
		$cidade = pg_fetch_result($res, $total_postos-1, cidade);
		
		echo "<table align='center' id='postos'>\n";

		if($total_postos > 0){?>
        <thead>
            <tr align='center' class='bold'>
                <th width='256'>Nome do Posto</th>
                <th width='298'>Endere&ccedil;o</th>
                <th width='98'>Telefone</th>
				<th width='98'>Telefone 2</th>
                <th width='32'>Mapa</th>
            </tr>
        </thead>
<?
			for ($i = 0 ; $i < $total_postos ; $i++) {
                $row = pg_fetch_array($res, $i);
                foreach ($row as $campo => $valor) {
                    $$campo = trim($valor);
                }
				$end_completo = $endereco . ", " . $numero  . " - " . $bairro . " - " .$complemento;
				$end_mapa     = "$endereco, $numero, $cep, $cidade, $estado, Brasil";
// 				if (is_numeric($longitude) and is_numeric($latitude)) { // lat e long estão ao contrário no banco
				$link_mapa = "<a title='Localizar no mapa' href='http://maps.google.com/maps?f=q&source=s_q&hl=pt-BR&q=$end_mapa&ie=windows-1252' target='_blank'>".
					"<img src='imagens/gMap.png' width='16' border='0'></a>";
// 				}

				echo "<tr>";
				$posto_nome = iif((strlen($nome_fantasia)>0),$nome_fantasia,$nome);
				$tooltip .= " title='".iif(($posto_nome==$nome_fantasia),"$posto_nome ($nome)",
										iif((strlen($posto_nome)>=40),"$posto_nome",""))."'";
				echo "<td$tooltip>$posto_nome</td>";
				$tooltip = (strlen($end_completo)>=44)?" title='$end_completo'":"";
				echo "<td$tooltip>";
				echo $end_completo;
				echo "</td>";
				echo "<td>$fone</td>";
				if(strlen($fone2 == 0)){
					$fone2 = "&nbsp;";
				}
				echo "<td>$fone_alternativo</td>";
				echo "<td>$link_mapa</td>";
				echo "</tr>";
				unset ($end_mapa, $link_mapa, $end_completo, $posto_nome, $email);
			}
		}else{
			echo "<tr><td class='fontenormal'> Nenhuma Assistência Técnica encontrada.</td></tr>";
		}
		echo "</table>\n<br>";
	exit;
}
//  FIM AJAX

$html_titulo = "GEORGE-Mapa da Rede Autorizada";
$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
$resInsertLog = pg_query($con, $sqlInsertLog);

?>
<!-- CSS -->
<style type="text/css">
<!--
body {
	font-family: Trebuchet MS,Helvetica,Arial,sans-serif;
	line-height: 1.2em;
	font-size: 11px;
	color:#858B8D;
    background: white;
	position: relative;
	top: 0;
	left: 0;
	width: 600px;
	padding: 10px 10px 10px 10px;
}

* {
	font-family: sans-serif, Verdana, Geneva, Arial, Helvetica;
	font-size: 11px;
}
#sel_cidade, #tblres {display: none;}
#mapabr {float: left;position:relative;top: -1em;}
#mapabr h2 {
	padding: 0;
	padding-left: 1em;
	margin-top:   2em;
	font-family:Helvetica ,Arial,sans-serif;
	font-weight: bold;
	color: #333;
	text-align: left;
	text-shadow: -2px 1px 1px #666;
	text-transform: uppercase;
	letter-spacing: 0.2em;
}
#mapabr span {
	padding: 2px 4px;
	color: white;
	background-color: #A10F15;
	text-shadow: 0 0 0 transparent;
	font: inherit
}

area {cursor: pointer}
a img {border: 0 solid transparent;}
form {
	position:relative;
	text-align: left;
	width: 700px;
	margin: 1em 80px;
}

label, select {margin-left: 2em;z-index:10}

fieldset {
	border-radius: 5px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	height: 365px;
	width: 500px;
}

#tblres table {
	position: relative;
	table-layout: fixed;
	margin: 0;
	background-color: transparent;
	padding: 0;
	width: 600px;
	overflow-x: hidden;
}
#tblres table td {
	background: #cccccc;
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
#tblres table td:nth-child(3) {text-align: right}
#tblres table td:nth-child(5) {text-align: center;background-color: white;}

#tblres table caption {
	font: normal bold 1.2em "Trebuchet MS",Helvetica,Arial,sans-serif;
	padding-bottom: 4px;
	border-bottom: 1px solid white;
	text-align: center;
}
.nome_cidade {text-transform:capitalize;text-decoration:underline; font: inherit}
/* content styles */
.highlight {
	background-color: #fffebb;
}
.branco {
	font-weight: bold;
	color: white;
}
.vermelho {color:#D2232A}
.fundo_vermelho {background-color: #A10F15}
.cinza {#666}
.bold {
	font-weight: bold;
}
//-->
</style>

<!-- JavaScript -->
<script src="../../js/jquery-1.6.1.min.js" type="text/javascript"></script>

<script type="text/javascript">
$(document).ready(function() {
//  Adiciona um evento onClick para cada 'area' que vai alterar o valor do SELECT 'estado'
	$('map area').click(function() {
		$('#estado').val($(this).attr('name'));
		$('#estado').change();;
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
		$.get("<?=$PHP_SELF?>", {'action': 'cidades','estado': estado},
		  function(data){
			if (data.indexOf('Sem resultados') < 0) {
				$('#sel_cidade').show(500);
			    $('#cidades').html(data).val('').removeAttr('disabled');
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
</head>

<body style='overflow:scroll;overflow-x:hidden'>
<div id='frmdiv'>
	<form name='frm_mapa_rede' action='<?=$PHP_SELF?>' method='post'>
		<fieldset for="frm_mapa_rede" align='center'>
			<legend>Pesquisa de Postos Autorizados</legend>
			<div id='mapabr'>
				<h2><span>Rede</span>&nbsp;Autorizada</h2>
				<map name="Map2">
					<area shape="poly" name="RS" coords="122,238,142,221,164,232,148,262">
					<area shape="poly" name="SC" coords="143,214,172,215,169,235,143,219">
					<area shape="poly" name="PR" coords="138,202,148,191,166,192,175,207,171,214,139,213">
					<area shape="poly" name="SP" coords="152,187,162,173,182,174,186,187,188,194,197,190,197,198,177,206,168,190">

					<area shape="poly" name="MS" coords="136,195,156,171,138,159,124,159,117,182">
					<area shape="poly" name="MT" coords="117,181,143,181,160,127,160,106,120,105,111,101,98,102,107,117,100,131,102,142">
					<area shape="poly" name="RO" coords="93,126,98,118,94,113,86,105,86,100,80,93,73,102,67,108,67,116,77,121">
					<area shape="poly" name="AC" coords="50,106,10,91,13,101,23,104,29,104,30,112,44,113">
					<area shape="poly" name="AM" coords="11,87,53,101,74,88,105,91,117,55,103,43,89,50,76,43,77,30,62,37,43,30,40,38,33,75,21,75,13,82">
					<area shape="poly" name="RR" coords="74,13,74,18,82,25,84,41,93,40,102,31,96,21,97,9,90,11">
					<area shape="poly" name="PA" coords="112,33,114,40,127,50,117,82,121,95,162,99,174,77,173,68,193,48,172,54,158,55,145,45,133,25">
					<area shape="poly" name="AP" coords="145,25,153,23,157,13,164,29,153,41">
					<area shape="poly" name="MA" coords="196,50,185,72,194,90,212,82,215,59">

					<area shape="poly" name="TO" coords="179,83,165,120,189,128,185,101">
					<area shape="poly" name="GO" coords="159,166,148,157,165,131,188,136,170,181">
					<area shape="poly" name="PI" coords="201,92,216,86,223,64,228,85,219,98,207,99,206,107,199,107">
					<area shape="poly" name="RJ" coords="206,201,202,190,214,189,218,181,226,187">
					<area shape="poly" name="MG" coords="171,164,190,162,192,145,205,140,217,146,224,154,217,169,212,183,193,183,185,170">
					<area shape="poly" name="ES" coords="236,167,228,162,221,177,226,183">
					<area shape="poly" name="BA" coords="198,113,196,134,213,133,230,139,235,146,231,157,235,160,240,142,241,127,249,124,243,113,243,105,234,106,225,107,215,107,207,115">
					<area shape="poly" name="CE" coords="230,59,235,86,241,86,252,70,239,61">
					<area shape="poly" name="SE" coords="250,108,248,113,281,118,257,113,252,109">

					<area shape="poly" name="AL" coords="266,102,258,104,281,102,260,110,266,104">
					<area shape="poly" name="PE" coords="269,94,269,99,262,99,256,101,281,98,246,98,239,96,234,100,231,95,234,92,243,93,281,94,255,96">
					<area shape="poly" name="PB" coords="269,85,262,85,257,88,253,85,248,87,257,90,263,91,268,89">
					<area shape="poly" name="RN" coords="256,73,249,81,256,80,257,83,270,82,265,76">
					<area shape="poly" name="DF" coords="168,162,171,153,183,149,182,161">
				</map>
				<p style='textalign: right; font-weight: bold;'>Selecione o Estado:</p>
				<img src="imagens/mapa_vermelho.jpg" usemap="#Map2" border="0">
			</div>
			<label for='estado'>Selecione o Estado</label><br>
			<select title='Selecione o Estado' name='estado' id='estado'>
				<option></option>
<?        foreach ($estados as $sigla=>$nome) {
			echo "\t\t\t\t<option value='$sigla'>$nome</option>\n";
        }
?>			</select>
			<div id='sel_cidade'>
			    <br>
	            <label for='cidades'>Selecione uma cidade</label><br>
				<select title='Selecione uma cidade' name='cidades' id='cidades'>
					<option></option>
				</select>
			</div>
		</fieldset>
	</form>
	</div>
	<div id='gmapsd'></div>
	<div id='tblres'></div>
</body>
</html>
