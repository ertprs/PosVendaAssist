<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';



$title = "Telecontrol - Mapa da Rede Autorizada";

$fabrica = $_GET['fabrica'];
$fabrica_escolhida = substr("$fabrica", 2, 2);
$fabrica_escolhida = str_replace("", "0", $fabrica_escolhida);
$estado  = str_replace ("'","",$estado);
$estado  = strtoupper($_GET['estado']);
$cidade  = strtoupper($_GET['cidade']);
$produto  = strtoupper($_GET['produto']);
$join = "";
if(strlen($produto)>0){
	$sql = "SELECT linha from tbl_produto where upper(referencia) = '$produto'";

	$res = pg_exec($con,$sql);
	$linha_produto = pg_result($res,0,0);
	$join = " JOIN tbl_posto_linha on tbl_posto.posto = tbl_posto_linha.posto and tbl_posto_linha.linha = $linha_produto";

	$sql = "SELECT nome from tbl_linha where fabrica=$login_fabrica and linha = $linha_produto";
	$res = pg_exec($con,$sql);
	$linha_nome = pg_result($res,0,0);
	$mensagem = "Postos que atendem a linha $linha_nome";
}
$cond_1 = " 1=1 ";
if(strlen($cidade)>0){
$cond_1 = " tbl_posto.cidade ILIKE '%$cidade%' ";

}


if(strlen($estado) > 0) {
	echo "<td>";
	echo "<b>";
	if ($estado == "AC") echo "Acre";
	if ($estado == "AL") echo "Alagoas";
	if ($estado == "AM") echo "Amazonas";
	if ($estado == "AP") echo "Amapá";
	if ($estado == "BA") echo "Bahia";
	if ($estado == "CE") echo "Ceará";
	if ($estado == "DF") echo "Distrito Federal";
	if ($estado == "ES") echo "Espírito Santo";
	if ($estado == "GO") echo "Goiás";
	if ($estado == "MA") echo "Maranhão";
	if ($estado == "MG") echo "Minas Gerais";
	if ($estado == "MS") echo "Mato Grosso do Sul";
	if ($estado == "MT") echo "Mato Grosso";
	if ($estado == "PA") echo "Pará";
	if ($estado == "PB") echo "Paraíba";
	if ($estado == "PE") echo "Pernambuco";
	if ($estado == "PI") echo "Piauí";
	if ($estado == "PR") echo "Paraná";
	if ($estado == "RJ") echo "Rio de Janeiro";
	if ($estado == "RN") echo "Rio Grande do Norte";
	if ($estado == "RO") echo "Rondônia";
	if ($estado == "RR") echo "Roraima";
	if ($estado == "RS") echo "Rio Grande do Sul";
	if ($estado == "SC") echo "Santa Catarina";
	if ($estado == "SE") echo "Sergipe";
	if ($estado == "SP") echo "São Paulo";
	if ($estado == "TO") echo "Tocantins";
	echo "</b>";
	if(strlen($cidade)>0 and $cidade<>"NULL"){
		echo "<BR><B>$cidade</b>";
	
	}
}
if($login_fabrica <>'56' AND $login_fabrica <>'57' AND $login_fabrica <>'58'){
$sql_add1 = " AND tbl_posto.posto not in(6359,20462) "; 
}
if(strlen($estado)>0){

	echo "<table align='center'>";
	$sql = "SELECT                          
				tbl_posto.posto                                  ,
				tbl_posto_fabrica.contato_endereco AS endereco   ,
				tbl_posto_fabrica.contato_numero   AS numero     ,
				tbl_posto.nome                                   ,
				tbl_posto_fabrica.contato_cidade   AS cidade     ,
				tbl_posto_fabrica.contato_estado   AS estado     ,
				tbl_posto_fabrica.contato_bairro   AS bairro     ,
				tbl_posto.fone                                   ,
				tbl_posto_fabrica.nome_fantasia                  ,
				tbl_posto_fabrica.codigo_posto                   ,
				tbl_posto_fabrica.credenciamento 
			FROM   tbl_posto
			JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
			JOIN    tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica 
			$join
			WHERE   tbl_posto_fabrica.fabrica = '$fabrica_escolhida'
			AND tbl_posto.estado ILIKE '%$estado%'
			and $cond_1 
			$sql_add1
			AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			ORDER BY tbl_posto.cidade, tbl_posto.nome";

	$res = pg_exec ($con,$sql);
	//echo nl2br($sql);
	if(pg_numrows($res) == 0){
		$sql = "SELECT                          
					tbl_posto.posto                 ,
					tbl_posto.endereco              ,
					tbl_posto.numero                ,
					tbl_posto.nome                  ,
					tbl_posto.cidade                ,
					tbl_posto.estado                ,
					tbl_posto.bairro                ,
					tbl_posto.fone                  ,
					tbl_posto.nome_fantasia         ,
					tbl_posto_fabrica.codigo_posto  ,
					tbl_posto_fabrica.credenciamento 
				FROM   tbl_posto
				JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
				JOIN    tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica 
				WHERE   tbl_posto_fabrica.fabrica = '$fabrica_escolhida'
				AND tbl_posto.estado ILIKE '%$estado%'
				$sql_add1
				AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				ORDER BY tbl_posto.cidade, tbl_posto.nome";
		$res = pg_exec ($con,$sql);
	}
	//echo "$sql";
	if(pg_numrows($res) > 0){
		echo "<center>$mensagem</center>";
		echo "<table width='700' align='center' style='border:1px #75706A solid;height:22px;'>";
		echo "<tr align='center' bgcolor='#eeeeff' >";
		echo "<td style='border:1px #75706A solid;height:22px;' class='fontenormal'><b> Código</b></td>";
		echo "<td style='border:1px #75706A solid;height:22px;' class='fontenormal'><b> Nome do Posto </b></td>";
		echo "<td style='border:1px #75706A solid;height:22px;' class='fontenormal'><b> Endereço </b></td>";
		echo "<td style='border:1px #75706A solid;height:22px;' class='fontenormal'><b> Cidade </b></td>";
		echo "<td style='border:1px #75706A solid;height:22px;' class='fontenormal'><b> Telefone </b></td>";
		// HD 15868 17922
		if($login_fabrica== 25 or $login_fabrica == 45){
		echo "<td style='border:1px #75706A solid;height:22px;' class='fontenormal'><b> Linha </b></td>";
		}
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$posto          = trim(pg_result($res,$i,posto));
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$nome           = trim(pg_result($res,$i,nome));
			$cidade         = trim(pg_result($res,$i,cidade));
			$estado         = trim(pg_result($res,$i,estado));
			$bairro         = trim(pg_result($res,$i,bairro));
			$nome_fantasia  = trim(pg_result($res,$i,nome_fantasia));
			$endereco       = trim(pg_result($res,$i,endereco));
			$numero         = trim(pg_result($res,$i,numero));
			$fone           = trim(pg_result($res,$i,fone));

			$cor = '#eeeeff';
			if ($i % 2 == 0) $cor = '#ffffff';
	

			echo "<tr bgcolor='$cor' style='border:1px #75706A solid;height:22px; font-size: 10px' class='fontenormal'>";
			$end_completo= $endereco . ", " . $numero  . " - " . $bairro;

			
			echo "<td><b><FONT SIZE='-3'>";
			echo "<a href=\"javascript: posto_tab.value= '$posto'; codigo_posto_tab.value='$codigo_posto' ; posto_nome_tab.value='$nome' ; posto_cidade_tab.value='$cidade' ; posto_endereco_tab.value='$end_completo' ; posto_estado_tab.value='$estado' ; ";
			//if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
			echo "abas.triggerTab(7);";
			echo "this.close(); \">\n";
			echo $codigo_posto ;
			echo "</a></b></FONT></td>";
			
			echo "<td><b><FONT SIZE='-3'>";
			echo "<a href=\"javascript: posto_tab.value= '$posto'; codigo_posto_tab.value='$codigo_posto' ; posto_nome_tab.value='$nome' ; posto_cidade_tab.value='$cidade' ; posto_endereco_tab.value='$end_completo' ; posto_estado_tab.value='$estado' ; ";
			//if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
			echo "abas.triggerTab(7);";
			echo " this.close(); \">\n";
			echo $nome ;
			echo "</a></b></FONT></td>";

			echo "<td><b>";
			echo $endereco . ", " . $numero  . " - " . $bairro;
			echo "</b></td>";

			echo "<td nowrap><b>";
			echo $cidade ;
			echo "</b></td>";

			echo "<td nowrap><b>";
			echo $fone ;
			echo "</b></td>";
			//HD 15868
			if($login_fabrica==25 or $login_fabrica ==45 ){ // HD 17922
				$sql_linha="SELECT nome from tbl_posto_linha join tbl_linha on tbl_posto_linha.linha=tbl_linha.linha where posto=$posto and fabrica= $login_fabrica";
				$res_linha=pg_exec($con,$sql_linha);
			
				echo "<td><b>";
				for($j = 0;$j < pg_numrows($res_linha); $j++){
					$linha=pg_result($res_linha,$j,nome);
					echo "$linha<br>";
				}
				echo "</b></td>";
			}
			echo "</tr>";
		}
	}else{
		echo "<p class='fontenormal'> Nenhuma Assistência Técnica encontrada. </p>";
	}
echo "</table>";
exit;
}

?>

<style type="text/css">

body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
.fonte {	font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color: #999999;
	text-decoration: none;
}
.linkk {font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	color: #999999;
	text-decoration: none;
}

.fontenormal {	font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	color: #6A6A6A;
	text-decoration: none;
	font-style: normal;
	line-height: 23px;
	font-weight: normal;
}
.style1 {font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #666666; text-decoration: none; }
.stylebordo {font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #663300; text-decoration: none;
}
v\:* {      behavior:url(#default#VML);    }    
</style>

<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>

<script type="text/javascript" src="js/firebug.js"></script>
<script type="text/javascript" src="js/jquery-1.1.2.pack.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">

<script type="text/javascript">

var http1 = new Array();
function mostraPostos(fabrica,estado){

	var curDateTime = new Date();
	http1[curDateTime] = createRequestObject();

	url = "callcenter_interativo_posto.php?fabrica="+fabrica+"&estado="+estado;
	http1[curDateTime].open('get',url);
	
	var campo = document.getElementById('div_defeitos');
//alert(natureza);
	http1[curDateTime].onreadystatechange = function(){
		if(http1[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http1[curDateTime].readyState == 4){
			if (http1[curDateTime].status == 200 || http1[curDateTime].status == 304){
				var results = http1[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
				
			}
		}
	}
	http1[curDateTime].send(null);
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

</script>



<body>
<br>

<table width='700' align='center' cellspacing='0' cellpadding='0'>
<tr>
	<td background="cadence_bg_int.gif" align='center'>
		<map name="FPMap0">
		<area href="<? echo "http://www.cadence.com.br";?>" shape="rect" coords="30, 50, 230, 115">
		</map>
		<table width='670' align='center' border='0'>
		<FORM METHOD=POST ACTION="<?echo "$PHP_SELF?fabrica=$fabrica&estado=$estado"?>">
		<tr>
			<td>
			<map name="Map2">
			<area shape="poly" coords="122,238,142,221,164,232,148,262" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','rs')">
			<area shape="poly" coords="143,214,172,215,169,235,143,219" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','sc')">
			<area shape="poly" coords="138,202,148,191,166,192,175,207,171,214,139,213" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','pr')">
			<area shape="poly" coords="152,187,162,173,182,174,186,187,188,194,197,190,197,198,177,206,168,190" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','sp')">
			

			<area shape="poly" coords="136,195,156,171,138,159,124,159,117,182" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','ms')">
			<area shape="poly" coords="117,151,143,151,160,127,160,106,120,105,111,101,98,102,107,117,100,131,102,142" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','mt')">
			<area shape="poly" coords="93,126,98,118,94,113,86,105,86,100,80,93,73,102,67,108,67,116,77,121" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','ro')">
			<area shape="poly" coords="50,106,10,91,13,101,23,104,29,104,30,112,44,113" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','ac')">
			<area shape="poly" coords="11,87,53,101,74,88,105,91,117,55,103,43,89,50,76,43,77,30,62,37,43,30,40,38,33,75,21,75,13,82" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','am')">
			<area shape="poly" coords="74,13,74,18,82,25,84,41,93,40,102,31,96,21,97,9,90,11" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','rr')">
			<area shape="poly" coords="112,33,114,40,127,50,117,82,121,95,162,99,174,77,173,68,193,48,172,54,158,55,145,45,133,25" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','pa')">
			<area shape="poly" coords="145,25,153,23,157,13,164,29,153,41" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','ap')">
			<area shape="poly" coords="196,50,185,72,194,90,212,82,215,59" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','ma')">

			<area shape="poly" coords="179,83,165,120,189,128,185,101" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','to')">
			<area shape="poly" coords="159,166,148,157,165,131,188,136,170,151" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','go')">
			<area shape="poly" coords="201,92,216,86,223,64,228,85,219,98,207,99,206,107,199,107" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','pi')">
			<area shape="poly" coords="206,201,202,190,214,189,218,181,226,187" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','rj')">
			<area shape="poly" coords="171,164,190,162,192,145,205,140,217,146,224,154,217,169,212,183,193,183,185,170" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','mg')">
			<area shape="poly" coords="236,167,228,162,221,177,226,183" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','es')">
			<area shape="poly" coords="198,113,196,134,213,133,230,139,235,146,231,157,235,160,240,142,241,127,249,124,243,113,243,105,234,106,225,107,215,107,207,115" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','ba')">
			<area shape="poly" coords="230,59,235,86,241,86,252,70,239,61" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','ce')">
			<area shape="poly" coords="250,108,248,113,251,118,257,113,252,109" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','se')">

			<area shape="poly" coords="266,102,258,104,251,102,260,110,266,104" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','al')">
			<area shape="poly" coords="269,94,269,99,262,99,256,101,251,98,246,98,239,96,234,100,231,95,234,92,243,93,251,94,255,96" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','pe')">
			<area shape="poly" coords="269,85,262,85,257,88,253,85,248,87,257,90,263,91,268,89" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','pb')">
			<area shape="poly" coords="256,73,249,81,256,80,257,83,270,82,265,76" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','rn')">
			<area shape="poly" coords="168,162,171,153,183,149,182,161" style='cursor: pointer' 
			href="javascript: mostraPostos('<?=$fabrica?>','df')">
			</map>
			<img src="/mapa_rede/cadence_mapa.gif" usemap="#Map2" border="0">
			</td>
		</tr>
		</form>
	  </table>
	</td>
</tr>
<?
echo "<tr>";
echo "<td colspan='2' height='100' align='center'>";
echo "<div id='div_defeitos' style='display:inline; Position:relative;width:100%'>";
echo "</td>";
echo "</tr>";
?>
</table>
</body>
</html>