<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

//$admin_privilegios="cadastros,call_center";
//include 'autentica_admin.php';

$tem_mapa = 0 ;
$estado = $_POST['estado'];
$pais   = $_POST['pais'];
$chave = $_GET['chave'];
if(strlen($chave)==0){
	$chave = $_POST['chave'];
}
/*
if($chave <> "1679091c5a880faf6fb5e6087eb1b2dc") {
	echo "Sem permissão de acesso.";
	exit;
}
*/


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
			JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = 6
			WHERE  $cond_estado
			AND    tbl_posto.pais = '$pais'
			AND    tbl_posto_fabrica.credenciamento = 'CREDENCIADO' 
			ORDER BY tbl_posto.cidade, tbl_posto.cep ";
	@$resPosto = pg_exec ($con,$sql);

	if (@pg_numrows ($resPosto) > 0) {
		$tem_mapa = 1;
		$aviso = "<center>";
		$aviso .= "<b>Clique sobre as marcas para ver informações detalhadas do posto</b>";
		$aviso .= "<br>";
		$aviso .= "+) Podem haver postos que não apareçam no mapa, por estarem com o endereço incorreto";
		$aviso .= "<br>";
		$aviso .= "+) A localização dos postos não é exata, podendo haver margem de erro";
		$aviso .= "<br>";
	}else{
		$aviso = "<font color='red'>Sem Postos Cadastrados para essa região.</font>";
	}
}

//HD 406478 - MLG - API-Key para o domínio telecontrol.NET.br
//HD 678667 - MLG - Adicionar mais uma Key. Alterado para um include que gerencia as chaves.
include '../gMapsKeys.inc';
?>




<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<!-- saved from url=(0037)http://asp.tectoy.com.br/oficinas.asp -->
<HTML><HEAD><TITLE>Tec Toy</TITLE>
<!-- #BeginTemplate "/Templates/normal.dwt" --><!-- DW6 -->
<!-- #BeginEditable "doctitle" --><!-- #EndEditable -->
<META http-equiv=Content-Type content="text/html; charset=iso-8859-1">
<!-- #BeginEditable "head" --><!-- #EndEditable -->
<LINK href="mapa_rede/estilos.css" type=text/css rel=stylesheet>
<META content="MSHTML 6.00.2900.3199" name=GENERATOR></HEAD>
<body  leftMargin=0 topMargin=0 marginheight="0" marginwidth="0" onload='load()' onunload='GUnload()'>

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


<TABLE height="100%" cellSpacing=0 cellPadding=0 width=740 align=center 
border=0>
  <TBODY>
  <TR>
    <TD width=740 height=5>
		<IMG height=1 src="mapa_rede/blank.gif" width=1>
	</TD>
  </TR>
  <TR>
    <TD vAlign=top width=740 background="mapa_rede/site_top.gif" 
    height=74>
      <TABLE class=texto height=74 cellSpacing=0 cellPadding=0 width=740 
      border=0>
        <TBODY>
        <TR>
          <TD width=208 height=34><IMG height=34 
            src="mapa_rede/blank.gif" width=208></TD>
          <TD width=532 height=34><IMG height=34 
            src="mapa_rede/blank.gif" width=532></TD></TR>
        <TR>
          <TD width=208 height=12><IMG height=12 
            src="mapa_rede/blank.gif" width=208 useMap=#Map border=0></TD>
          <TD width=532 height=12><SPAN class=menu-principal><A 
            class=menu-principal 
            href="http://www.tectoy.com.br/produtos.asp">Produtos</A></SPAN><SPAN 
            class=menu-principal> :: <A class=menu-principal 
            href="http://www.tectoy.com.br/karaoke/vcd_index.asp">Discos para 
            Karaokê</A> :: </SPAN><A class=menu-principal 
            href="http://posvenda.telecontrol.com.br/assist/admin/mapa_rede_tectoy.php?chave=1679091c5a880faf6fb5e6087eb1b2dc">Autorizadas</A><SPAN 
            class=menu-principal> :: </SPAN><A class=menu-principal 
            href="http://asp.tectoy.com.br/fale_conosco/index.asp">Fale 
            Conosco</A></TD></TR>
        <TR>
          <TD width=208 height=28><IMG height=28 
            src="mapa_rede/blank.gif" width=208 useMap=#Map2 border=0></TD>
          <TD class=data vAlign=center align=right width=532 height=28><IMG 
            height=10 src="mapa_rede/blank.gif" width=532><BR>
<SCRIPT language=JavaScript>
var name = navigator.appName;
var vers = navigator.appVersion;
vers = vers.substring(0,1); 
// or 0,4  could return 4.5 instead of just 4

if (name == "Microsoft Internet Explorer")
{
// You can edit this message.
  document.write('');
// If you want to redirect your visitors to a
// Internet Explorer-friendly version of your
// page, use this code:
// window.location=browser_specific_url;
}
else
{
// You can edit this message.
  document.write('Este site é melhor visualizado no Internet Explorer - ');
// If you want to redirect your visitors to a
// Internet Explorer-friendly version of your
// page, use this code:
// window.location=browser_specific_url;
}
        
      </SCRIPT>

            <SCRIPT language=JavaScript>
<!--

// Array of day names
var dayNames = new Array("Domingo","Segunda","Terça","Quarta","Quinta","Sexta","Sábado");
var monthNames = new Array("Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro");

var dt = new Date();
var y  = dt.getYear();

// Y2K compliant
if (y < 1000) y +=1900;

document.write(dayNames[dt.getDay()] + ", " + dt.getDate() +" de "+ monthNames[dt.getMonth()] +" de "+ y);
// -->
        </SCRIPT>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</TD></TR></TBODY>
</TABLE></TD></TR>
  <TR>
    <TD vAlign=top width=740 height="100%">
      <TABLE height="100%" cellSpacing=0 cellPadding=0 width=740 border=0>
        <TBODY>
        <TR>
          <TD vAlign=top width=120 height="100%">
            <TABLE height="100%" cellSpacing=0 cellPadding=0 width=120 
              border=0>
			  <TBODY>
              <TR>
                <TD width=120 height=35><IMG height=36 src="mapa_rede/site_left_top.gif" width=120></TD>
			  </TR>
              <TR>
                <TD vAlign=top align=middle width=120  background="mapa_rede/site_left_mid.gif" height="100%"><TABLE cellSpacing=0 cellPadding=0 width=120 border=0>
                    <TBODY>
                    <TR>
                      <TD width=5>&nbsp;</TD>
                      <TD align=left width=115>&nbsp;</TD>
					</TR>
					</TBODY>
				  </TABLE>
				</TD>
			  </TR>
              <TR>
                <TD width=120 height=145>
					<IMG height=145 src="mapa_rede/site_left_bot.gif" width=120>
				</TD>
			  </TR>
			  </TBODY>
			</TABLE>
		  </TD>
          <TD vAlign=top width=620 background="mapa_rede/site_bkg.gif" height="100%">
            <TABLE class=titulo cellSpacing=0 cellPadding=0 width=600 border=0>
			 <TBODY>
              <TR>
                <TD width=600><IMG height=10 src="mapa_rede/blank.gif" width=600>
				</TD>
			  </TR>
              <TR>
                <TD width=600>
				 <P>
                  <OBJECT 
                  codeBase=http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0 
                  height=50 width=400 
                  classid=clsid:D27CDB6E-AE6D-11cf-96B8-444553540000><PARAM NAME="movie" VALUE="http://asp.tectoy.com.br/swfs/titulo_autorizadas.swf"><PARAM NAME="quality" VALUE="high"><PARAM NAME="wmode" VALUE="transparent">
                                                                                 
                                           <embed 
                  src="swfs/titulo_autorizadas.swf" width="400" height="50" 
                  quality="high" 
                  pluginspage="http://www.macromedia.com/go/getflashplayer" 
                  type="application/x-shockwave-flash" 
                  wmode="transparent"></embed>                     </OBJECT></P>
				</TD>
			  </TR>
			 </TBODY>
			</TABLE>
            <TABLE class=texto cellSpacing=0 cellPadding=0 width=600 border=0>
              <TBODY>
              <TR>
                <TD vAlign=top width=15 height=10>
					<IMG height=10 src="mapa_rede/blank.gif" width=15>
				</TD>
                <TD vAlign=top width=395 height=10>
					<IMG height=10 src="mapa_rede/blank.gif" width=395>
				</TD>
                <TD vAlign=top width=10 height=10>
					<IMG height=10 src="mapa_rede/blank.gif" width=10>
				</TD>
                <TD vAlign=top width=180 height=10>
					<IMG height=10 src="mapa_rede/blank.gif" width=180>
				</TD>
			  </TR>
              <TR>
                <TD vAlign=top width=15 height=63>&nbsp;</TD>
                <TD vAlign=top width=395><P align=left>Relação dos postos de atendimento a produtos 
                  fabricados e comercializados pela Tec Toy. Todos estão 
                  devidamente capacitados a resolver qualquer tipo de problema. 
                  São mais de 300 empresas espalhadas por todo o país, inclusive 
                  comercializando acessórios. </P>
                  <P align=left>Caso alguma dúvida persista ou qualquer tipo de 
                  dificuldade o impeça de utilizar seu produto Tec Toy, entre em 
                  contato diretamente com a gente. Selecione um Estado:</P>
                  <P align=center>
				</TD>
                <TD vAlign=top width=10 >&nbsp;</TD>
                <TD vAlign=top width=180></TD>
			 </TR>

              <TR>
                <TD colspan='4'><? echo $aviso;?></TD>
			 </TR>

             <TR>
                <TD colspan='4'>
<div id="Gmapa" style="width: 550px; height: 400px ; border: 1px solid #979797; background-color: #e5e3df; margin: auto; margin-top: 2em; margin-bottom: 2em">
	<div style="padding: 1em; color: gray">Carregando Mapa...</div>
</div>

<table width='550' align='left' border='0' cellspacing='2' bgcolor='#ffffff'>
<form name='frm_mapa' method='post' action='<?echo $PHP_SELF?>' >
<tr>
	<td align='center' bgcolor='#d9e2ef'>
		<input type ='hidden' name='chave' value='<?echo $chave;?>'>
		<font face='arial, verdana' color='#596d9b' size='-1'>
		Consulte o Mapa da Rede:
		<br>
		País
		<select name='pais'>
			<option value='BR' selected>Brasil</option>
			<option value='PE'         >Peru</option>
		</select>

		Estado
		<select name='estado'>
			<option value='00' selected>Todos</option>
			<option value='SP'         >São Paulo</option>
			<option value='RJ'         >Rio de Janeiro</option>
			<option value='PR'         >Paraná</option>
			<option value='SC'         >Santa Catarina</option>
			<option value='RS'         >Rio Grande do Sul</option>
			<option value='MG'         >Minas Gerais</option>
			<option value='ES'         >Espírito Santo</option>
			<option value='BR-CO'      >Centro-Oeste</option>
			<option value='BR-NE'      >Nordeste</option>
			<option value='BR-N'       >Norte</option>
		</select>

		<input type='submit' name='btn_mapa' value='mapa'>
		</font>
	</td>
</tr>
</form>
</table>
</TD>
			 </TR>


<?
if (isset ($resPosto)) {
    echo "<TR>";
    echo "<TD colspan='4'>";
	echo "<table width='500' align='center' BORDER='1'style='border:1px #77aadd solid;height:22px;'>";
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
    echo "</TD>";
	echo "</TR>";

}
?>

				</TBODY>
				</TABLE>
			</TD>
			</TR>
			</TBODY>
			</TABLE>
			</TD>
			</TR>
	  <TR>
		<TD vAlign=top width=740 height=20>
			<IMG height=20 src="mapa_rede/site_bot.gif" width=740>
		</TD>
	  </TR>
	</TBODY>
</TABLE>
</body>
</html>
