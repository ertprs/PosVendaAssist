<?
include '/var/www/assist/www/dbconfig.php';
include '/var/www/includes/dbconnect-inc.php';

$html_titulo = "Telecontrol - Mapa da Rede Autorizada";

//include "cabecalho.php";

if (count($_POST)>0) {
	
	$cep    = $_POST['cep'];
	$estado = $_POST['estado'];
	$cidade = $_POST['cidade'];
	$bairro = $_POST['bairro'];
	$linha  = $_POST['linha'];
	$cep = (!empty($cep)) ? str_replace('-', '', $cep) : '' ;

	if (!empty($cep)) {
		$sql = "SELECT estado,cidade,bairro FROM tbl_cep where cep = '$cep'";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res)>0) {
			$estado = pg_fetch_result($res, 0, 'estado');
			$cidade = pg_fetch_result($res, 0, 'cidade');
			$bairro = pg_fetch_result($res, 0, 'bairro');
		}
	}
	
}elseif (!empty($_GET)) {
	$estado = strtoupper($_GET['estado']);
	$cidade = strtoupper($_GET['cidade']);
	$bairro = strtoupper($_GET['bairro']);
	$linha  = strtoupper($_GET['linha']);
}
?>


<style type="text/css">

body {
	background-image: url(cadence_bg.gif);
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
.fonte_preta {	font-family: Arial, Helvetica, sans-serif;
	font-size: 14px;
	color: #000000;
	text-decoration: none;
}
.linkk {font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	color: #999999;
	text-decoration: none;
}

.fontenormal {	font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	color: #999999;
	text-decoration: none;
	font-style: normal;
	line-height: 23px;
	font-weight: normal;
}

.frm {
	BORDER-RIGHT: #888888 1px solid; 
	BORDER-TOP: #888888 1px solid; 
	FONT-WEIGHT: bold; 
	FONT-SIZE: 8pt; 
	BORDER-LEFT: #888888 1px solid; 
	BORDER-BOTTOM: #888888 1px solid; 
	FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif; 
	BACKGROUND-COLOR: #f0f0f0
}

.style1 {font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #666666; text-decoration: none; }
.stylebordo {font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #663300; text-decoration: none;
}
v\:* {      behavior:url(#default#VML);    }    
</style>

<?

$fabrica=45;
?>

<body>
<br>

<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script type="text/javascript" src="../../admin/js/jquery.maskedinput.js"></script>
<script type="text/javascript">
	$(function() {
		$('#estado').change(function() {
			
			if ($('#linha').val() != undefined) {
				
				var linha_value = $('#linha').val();

			}else{
				var linha_value = '';
			};
			
			window.location= "<?php echo $PHP_SELF?>?estado="+$(this).val()+"&linha="+linha_value;
			
		});
		$('#cep').maskedinput('99999-999');
	});
</script>
<table width='100%' align='center' border="0" cellspacing='0' cellpadding='0' background="cadence_bg_int.gif">
<tr>
	<td background="cadence_bg_int.gif" align='center'>

		<table width='100%' align='center' border='0'>
		<FORM METHOD="POST" ACTION="<?echo "$PHP_SELF?estado=$estado"?>">
		<tr>
			<td width="40%">
			<map name="Map2">
			<area shape="poly" coords="122,238,142,221,164,232,148,262" href="<? echo "$PHP_SELF?estado=rs";?>">
			<area shape="poly" coords="143,214,172,215,169,235,143,219" href="<? echo "$PHP_SELF?estado=sc";?>">
			<area shape="poly" coords="138,202,148,191,166,192,175,207,171,214,139,213" href="<? echo "$PHP_SELF?estado=pr";?>">
			<area shape="poly" coords="152,187,162,173,182,174,186,187,188,194,197,190,197,198,177,206,168,190" href="<? echo "$PHP_SELF?estado=sp";?>">

			<area shape="poly" coords="136,195,156,171,138,159,124,159,117,182" href="<? echo "$PHP_SELF?estado=ms";?>">
			<area shape="poly" coords="117,151,143,151,160,127,160,106,120,105,111,101,98,102,107,117,100,131,102,142" href="<? echo "$PHP_SELF?estado=mt";?>">
			<area shape="poly" coords="93,126,98,118,94,113,86,105,86,100,80,93,73,102,67,108,67,116,77,121" href="<? echo "$PHP_SELF?estado=ro";?>">
			<area shape="poly" coords="50,106,10,91,13,101,23,104,29,104,30,112,44,113" href="<? echo "$PHP_SELF?estado=ac";?>">
			<area shape="poly" coords="11,87,53,101,74,88,105,91,117,55,103,43,89,50,76,43,77,30,62,37,43,30,40,38,33,75,21,75,13,82" href="<? echo "$PHP_SELF?estado=am";?>">
			<area shape="poly" coords="74,13,74,18,82,25,84,41,93,40,102,31,96,21,97,9,90,11" href="<? echo "$PHP_SELF?estado=rr";?>">
			<area shape="poly" coords="112,33,114,40,127,50,117,82,121,95,162,99,174,77,173,68,193,48,172,54,158,55,145,45,133,25" href="<? echo "$PHP_SELF?estado=pa";?>">
			<area shape="poly" coords="145,25,153,23,157,13,164,29,153,41" href="<? echo "$PHP_SELF?estado=ap";?>">
			<area shape="poly" coords="196,50,185,72,194,90,212,82,215,59" href="<? echo "$PHP_SELF?estado=ma";?>">

			<area shape="poly" coords="179,83,165,120,189,128,185,101" href="<? echo "$PHP_SELF?estado=to";?>">
			<area shape="poly" coords="159,166,148,157,165,131,188,136,170,151" href="<? echo "$PHP_SELF?estado=go";?>">
			<area shape="poly" coords="201,92,216,86,223,64,228,85,219,98,207,99,206,107,199,107" href="<? echo "$PHP_SELF?estado=pi";?>">
			<area shape="poly" coords="206,201,202,190,214,189,218,181,226,187" href="<? echo "$PHP_SELF?estado=rj";?>">
			<area shape="poly" coords="171,164,190,162,192,145,205,140,217,146,224,154,217,169,212,183,193,183,185,170" href="<? echo "$PHP_SELF?estado=mg";?>">
			<area shape="poly" coords="236,167,228,162,221,177,226,183" href="<? echo "$PHP_SELF?estado=es";?>">
			<area shape="poly" coords="198,113,196,134,213,133,230,139,235,146,231,157,235,160,240,142,241,127,249,124,243,113,243,105,234,106,225,107,215,107,207,115" href="<? echo "$PHP_SELF?estado=ba";?>">
			<area shape="poly" coords="230,59,235,86,241,86,252,70,239,61" href="<? echo "$PHP_SELF?estado=ce";?>">
			<area shape="poly" coords="250,108,248,113,251,118,257,113,252,109" href="<? echo "$PHP_SELF?estado=se";?>">

			<area shape="poly" coords="266,102,258,104,251,102,260,110,266,104" href="<? echo "$PHP_SELF?estado=al";?>">
			<area shape="poly" coords="269,94,269,99,262,99,256,101,251,98,246,98,239,96,234,100,231,95,234,92,243,93,251,94,255,96" href="<? echo "$PHP_SELF?estado=pe";?>">
			<area shape="poly" coords="269,85,262,85,257,88,253,85,248,87,257,90,263,91,268,89" href="<? echo "$PHP_SELF?estado=pb";?>">
			<area shape="poly" coords="256,73,249,81,256,80,257,83,270,82,265,76" href="<? echo "$PHP_SELF?estado=rn";?>">
			<area shape="poly" coords="168,162,171,153,183,149,182,161" href="<? echo "$PHP_SELF?estado=df";?>">
			</map>
			<img src="mapa_vermelho.jpg" usemap="#Map2" border="0">
			</td>
		<? 
		$estados = array("AC"=>"Acre", 
		"AL"=>"Alagoas", 
		"AM"=>"Amazonas", 
		"AP"=>"Amapá",
		"BA"=>"Bahia",
		"CE"=>"Ceará",
		"DF"=>"Distrito Federal",
		"ES"=>"Espírito Santo",
		"GO"=>"Goiás",
		"MA"=>"Maranhão",
		"MT"=>"Mato Grosso",
		"MS"=>"Mato Grosso do Sul",
		"MG"=>"Minas Gerais",
		"PA"=>"Pará",
		"PB"=>"Paraíba",
		"PR"=>"Paraná",
		"PE"=>"Pernambuco",
		"PI"=>"Piauí",
		"RJ"=>"Rio de Janeiro",
		"RN"=>"Rio Grande do Norte",
		"RO"=>"Rondônia",
		"RS"=>"Rio Grande do Sul",
		"RR"=>"Roraima",
		"SC"=>"Santa Catarina",
		"SE"=>"Sergipe",
		"SP"=>"São Paulo",
		"TO"=>"Tocantins") ;

		
		echo "<td>";
			echo "<table width='100%' class='fonte_preta'>";
				?>
				<tr>
					<td><b> CEP </b> </td>
					<td>
						<input type="text" name="cep" id="cep" class="frm" value="<?php echo $cep?>" />
						&nbsp;
						<input type="submit" value="Pesquisar CEP" style="font:11px Arial">
					</td>
				</tr>
				<?
				echo "<tr>";
					echo "<td><b>Estado: </b></td>";
					?>
					<td>
						<select name="estado" id="estado" class="frm">
							
							<option value=""></option>
							<?php foreach ($estados as $key => $value): ?>

								<?php 
								$selected_estado = ($estado == $key) ? 'SELECTED' : '' ;
								?>
								<option value="<?php echo $key?>" <?php echo $selected_estado ?>> <?php echo $value ?> </option>
							
							<?php endforeach ?>
						
						</select>
					</td>
					<?
				echo "</tr>";
				
		

			if (!empty($estado)) {
				$cond_estado .= " estado = '$estado'";
			}

			if (!empty($cep)) {
				$cond_cep .= " AND cep = '$cep' ";
			}
			
			if (!empty($cidade)) {
				$cond_cidade .= " AND cidade = '$cidade' ";
			}

			if ((strlen($estado > 0)) and (strlen($cep > 0))){
				$sql = "SELECT DISTINCT cidade FROM tbl_cep WHERE $cond_estado $cond_cep ORDER BY cidade;";
				$res = pg_query($con,$sql);
			}
	
				echo "<tr>";
				echo "<td><b>Cidade: </b></td> <td> <select name='cidade' id='cidade' class='frm' onchange='window.location=\"$PHP_SELF?estado=$estado&cidade=\"+this.value'>\n";
				echo "<option value=''>TODAS</option>\n";
				if (pg_numrows($res) > 0) {
					for ($x = 0 ; $x < pg_numrows($res) ; $x++){

						$aux_cidade = trim(pg_result($res,$x,'cidade'));
						
						$selected_cidade = ($aux_cidade == $cidade) ? 'SELECTED' : '' ;
						
						?>
						<option value="<?php echo $aux_cidade?>" <?php echo $selected_cidade ?> > 
							<?php echo $aux_cidade; ?>
						</option>

						<?php
					}
				}
				echo "</select></td></tr>";
			

			
				if ((strlen($cidade) > 0) and (strlen($estado) >0) and (strlen($cep) > 0)){
					$sql = "SELECT DISTINCT bairro FROM tbl_cep WHERE $cond_estado $cond_cep and cidade='".$cidade."' ORDER BY bairro;";
					$res = pg_query($con,$sql);
				}
					echo "<tr>";
					echo "<td><b>Bairro: </b></td><td><select name='bairro' id='bairro' class='frm' onchange='window.location=\"$PHP_SELF?estado=$estado&cidade=$cidade&bairro=\"+this.value'>\n";
					echo "<option value=''>TODOS</option>\n";
					if (pg_numrows($res) > 0) {
						for ($x = 0 ; $x < pg_numrows($res) ; $x++){

							$aux_bairro = trim(pg_result($res,$x,'bairro'));
							
							$selected_bairro = ($aux_bairro == $bairro) ? 'SELECTED' : '' ;
							
							?>
							<option value="<?php echo $aux_bairro?>" <?php echo $selected_bairro ?> > 
								<?php echo $aux_bairro; ?>
							</option>

							<?php
						}
					}
					echo "</select></td></tr>";

			


			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $fabrica 
					ORDER BY tbl_linha.nome;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {

				if (!empty($cidade)) {
					$showcidade =  "cidade=$cidade";
				}

				if (!empty($bairro)) {
					$showbairro =  "bairro=$bairro";
				}
				?>
				<tr>
					<td colspan="2">
						<hr style="border:1px solid">
					</td>
				</tr>
				<?
				echo "<tr><td>
					<b>Linha: </b></td><td><select name='linha' id='linha' class='frm' onchange='window.location=\"$PHP_SELF?estado=$estado&$showcidade&$showbairro&linha=\"+this.value'>\n";
				echo "<option value=''>TODAS</option>\n";
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha = trim(pg_result($res,$x,linha));
					$aux_nome  = trim(pg_result($res,$x,nome));
					
					echo "<option value='$aux_linha'"; 
					if ($linha == $aux_linha){
						echo " SELECTED "; 
						$mostraMsgLinha = "<br> da LINHA $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select></td></tr>";
			}


			echo "</table>";

			

		
		echo "</form>";
		if(strlen($estado)>0 || strlen($cep)>0){
			echo "<tr>";
			echo "<td colspan='2' height='100' align='center'>";
			echo "<table align='center'>";
			if(strlen($linha)>0) $sql_add = " AND tbl_posto.posto IN (SELECT DISTINCT posto FROM tbl_posto_fabrica JOIN tbl_posto_linha USING(posto) WHERE linha=$linha AND  fabrica =  $fabrica) ";
			if (!empty($estado) and empty($cep)) {
				$sql_add .= " AND tbl_posto_fabrica.contato_estado LIKE upper('%$estado%') ";
			}
			if (!empty($cep)) {
				$sql_add .= " AND tbl_posto_fabrica.contato_cep = '$cep' ";
			}
			if (!empty($cidade) and empty($cep)) {
				$sql_add .= " AND tbl_posto_fabrica.contato_cidade LIKE upper('%$cidade%') ";
			}
			if (!empty($bairro) and empty($cep)) {
				$sql_add .= " AND tbl_posto_fabrica.contato_bairro LIKE upper('%$bairro%') ";
			}
			$sql = "SELECT                          
						tbl_posto.posto                 ,
						tbl_posto_fabrica.contato_endereco              as endereco,
						tbl_posto_fabrica.contato_numero                as numero,
						tbl_posto.nome                  ,
						tbl_posto_fabrica.contato_cidade                as cidade,
						tbl_posto_fabrica.contato_estado                as estado,
						tbl_posto_fabrica.contato_bairro                as bairro,
						tbl_posto_fabrica.contato_fone_comercial        as fone,
						tbl_posto_fabrica.contato_cep        as cep,
						tbl_posto.nome_fantasia         ,
						tbl_posto_fabrica.codigo_posto  ,
						tbl_posto_fabrica.credenciamento 
					FROM   tbl_posto
					JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
					JOIN    tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica 
					WHERE   tbl_posto_fabrica.fabrica = '45'
					
					AND tbl_posto.posto not in(6359,20462)
					AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					$sql_add
					ORDER BY tbl_posto_fabrica.contato_cidade, tbl_posto.nome";
			$res = pg_exec ($con,$sql);

			if(pg_numrows($res) > 0){
				echo "<table width='100%' align='center' cellpadding='2' style='height:22px;'>";
				echo "<tr align='center' bgcolor='#92000b' >";
				echo "<td style='border:1px #92000b solid;height:22px;color:#ffffff' class='fontenormal'><b> Nome do Posto </b></td>";
				echo "<td style='border:1px #92000b solid;height:22px;color:#ffffff' class='fontenormal'><b> Endereço </b></td>";
				echo "<td style='border:1px #92000b solid;height:22px;color:#ffffff' class='fontenormal'><b> Cidade/UF </b></td>";
				echo "<td style='border:1px #92000b solid;height:22px;color:#ffffff' class='fontenormal'><b> Telefone </b></td>";
				echo "<td style='border:1px #92000b solid;height:22px;color:#ffffff' class='fontenormal'><b> Linha </b></td>";
				echo "</tr>";

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					$posto          = trim(pg_result($res,$i,posto));
					$nome           = trim(pg_result($res,$i,nome));
					$cidade         = trim(pg_result($res,$i,cidade));
					$estado         = trim(pg_result($res,$i,estado));
					$bairro         = trim(pg_result($res,$i,bairro));
					$nome_fantasia  = trim(pg_result($res,$i,nome_fantasia));
					$endereco       = trim(pg_result($res,$i,endereco));
					$numero         = trim(pg_result($res,$i,numero));
					$cep            = trim(pg_result($res,$i,cep));
					$fone           = trim(pg_result($res,$i,fone));

					$cor = '#787878';
					$textcolor = "#fff";
					if ($i % 2 == 0) {
						$cor = '#f0f3f0';
						$textcolor = "#000";
					}

					echo "<tr bgcolor='$cor' style='color:$textcolor;border:1px #75706A solid;height:22px; font-size: 10px' class='fontenormal'>";

					echo "<td><b><FONT SIZE='-3'>";
					echo $nome ;
					echo "</b></FONT></td>";

					echo "<td><b>";
					echo $endereco . ", " . $numero  . " - " . $bairro . " - CEP: " . $cep;
					echo "</b></td>";

					echo "<td nowrap><b>";
					echo $cidade." - ".$estado ;
					echo "</b></td>";

					echo "<td nowrap><b>";
					echo $fone ;
					echo "</b></td>";

					$linhas='';
					$sql = "SELECT tbl_linha.nome FROM tbl_posto_linha JOIN tbl_linha USING(linha)WHERE tbl_posto_linha.ativo IS NOT FALSE AND posto = $posto AND fabrica=$fabrica";

					$res2 = pg_exec ($con,$sql);
					if(pg_numrows($res2) > 0){
						for ($x = 0 ; $x < pg_numrows ($res2) ; $x++) {
							$linhas .= trim(pg_result($res2,$x,nome))."<br>";
						}
					}
					echo "<td nowrap>$linhas</td>";

					echo "</tr>";
				}
			}else{
				echo "<p class='fontenormal'> Nenhuma Assistência Técnica encontrada.</p>";
			}
		echo "</table>";}?>
		</td>
		</tr>
	</td>
</tr>
</table>

<!--<table cellpadding='0' cellspacing='0'><tr><td align="center" valign="top"><img src="cadence_rodape.gif" height="74" border="0"></td></tr></table>
-->
</body>
</html>
