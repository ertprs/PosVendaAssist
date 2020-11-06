<?
include "/www/assist/www/dbconfig.php";
include "/www/assist/www/includes/dbconnect-inc.php";

$estado  = strtoupper($_GET['estado']);
$cidade  = $_GET['cidade'];
$fabrica = $_GET['fabrica'];

$fabrica_escolhida = substr("$fabrica", 2, 2);

$fabrica_escolhida = str_replace("", "0", $fabrica_escolhida);

$sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = $fabrica_escolhida";
$res = pg_exec($con,$sql);
if(pg_numrows($res) == 0){
	echo "<p align='center'>Fábrica não encontrada</p>";
}else{
	echo "<p align='center' style='font-family: verdana; font-size: 16px'>Assistências Técnicas - <b>". pg_result($res,0,nome)."</b></p>";
}

function RemoveAcentos($Msg) 
{ 
  $a = array( 
            '/[ÂÀÁÄÃ]/'=>'A', 
            '/[âãàáä]/'=>'a', 
            '/[ÊÈÉË]/'=>'E', 
            '/[êèéë]/'=>'e', 
            '/[ÎÍÌÏ]/'=>'I', 
            '/[îíìï]/'=>'i', 
            '/[ÔÕÒÓÖ]/'=>'O', 
            '/[ôõòóö]/'=>'o', 
            '/[ÛÙÚÜ]/'=>'U', 
            '/[ûúùü]/'=>'u', 
            '/ç/'=>'c', 
            '/Ç/'=>'C'); 
    // Tira o acento pela chave do array                         
    return preg_replace(array_keys($a), array_values($a), $Msg); 
} 

?>



<table border='0'>
<tr>
	<td>
		<map name="FPMap0">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=rs";?>" shape="rect" coords="204, 319, 228, 328">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=sc";?>" shape="rect" coords="226, 296, 250, 305">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=pr";?>" shape="rect" coords="213, 270, 240, 282">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=sp";?>" shape="rect" coords="233, 245, 256, 255">

			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=rj";?>" shape="rect" coords="297, 257, 322, 273">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=es";?>" shape="rect" coords="321, 225, 347, 239">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=mg";?>" shape="rect" coords="271, 207, 298, 224">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=ba";?>" shape="rect" coords="291, 148, 318, 164">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=se";?>" shape="rect" coords="344, 142, 368, 156">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=al";?>" shape="rect" coords="357, 126, 379, 138">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=pe";?>" shape="rect" coords="364, 113, 390, 126">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=pb";?>" shape="rect" coords="365, 95, 392, 108">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=rn";?>" shape="rect" coords="349, 73, 373, 90">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=ce";?>" shape="rect" coords="311, 78, 337, 95">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=pi";?>" shape="rect" coords="287, 107, 307, 120">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=ma";?>" shape="rect" coords="258, 79, 285, 100">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=to";?>" shape="rect" coords="234, 128, 259, 148">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=go";?>" shape="rect" coords="231, 176, 251, 188">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=df";?>" shape="rect" coords="235, 198, 258, 210">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=ms";?>" shape="rect" coords="180, 226, 215, 248">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=mt";?>" shape="rect" coords="170, 162, 200, 182">

			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=ro";?>" shape="rect" coords="102, 151, 125, 165">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=pa";?>" shape="rect" coords="178, 85, 207, 99">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=rr";?>" shape="rect" coords="103, 24, 130, 48">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=am";?>" shape="rect" coords="84, 88, 120, 108">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=ac";?>" shape="rect" coords="31, 145, 59, 162">
			<area href="<? echo "$PHP_SELF?fabrica=$fabrica&estado=ap";?>" shape="rect" coords="191, 23, 221, 44">
		</map>
		<img src="mapa_brasil2.gif" usemap="#FPMap0" border="0">
	</td>
<? if(strlen($estado) > 0) {?>
	<td>
<?
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
	echo "<br><select name='cidade' size='1'>";
	echo "<option value='cidade_buscada'></option>";
	
	$sql = "SELECT DISTINCT 
				UPPER(TRIM(tbl_posto.cidade)) AS cidade
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
			JOIN   tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica 
			WHERE  tbl_posto_fabrica.fabrica = '$fabrica_escolhida'
			AND tbl_posto.estado ILIKE '%$estado%'
			ORDER BY cidade; ";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res) > 0){
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			
			$cidade_busca_ant     = strtoupper(trim(pg_result($res,$i,cidade)));
			$cidade_busca = strtoupper(RemoveAcentos($cidade_busca_ant));
			if($cidade_busca <> $cidade_busca2){
				echo "<option value='$cidade_busca'"; if($cidade==$cidade_busca)echo "SELECTED"; echo " onClick=\"window.location.href='$PHP_SELF?fabrica=$fabrica&estado=$estado&cidade=$cidade_busca'\">$cidade_busca</option>";
				$cidade_busca2 = $cidade_busca;
			}
		}
	}else{
		echo "<option value='NÃO ENCONTRADO'>NÃO ENCONTRADO</option>";
	}
	echo "</select>";

?>
	</td>
</tr>
<?}


$cidade_busca = trim($_GET['cidade']);
if((strlen($cidade_busca)>0) AND (strlen($estado)>0)){
echo "<tr>";
	echo "<td colspan='2' height='100' align='center'>";
echo "<table width='400' border='0' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#AAC4FF'>";

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
			AND tbl_posto.cidade ILIKE '%$cidade_busca%'
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$posto          = trim(pg_result($res,$i,posto));
		$nome           = trim(pg_result($res,$i,nome));
		$cidade         = trim(pg_result($res,$i,cidade));
		$estado         = trim(pg_result($res,$i,estado));
		$bairro         = trim(pg_result($res,$i,bairro));
		$nome_fantasia  = trim(pg_result($res,$i,nome_fantasia));
		$endereco       = trim(pg_result($res,$i,endereco));
		$numero         = trim(pg_result($res,$i,numero));
		$fone           = trim(pg_result($res,$i,fone));
		echo "<tr>";      
			echo "<td align='center'>$nome</td>";
		echo "</tr>";
		echo "<tr>";      
			echo "<td bgcolor='#ffffff'>Endereço: $endereco $numero - $bairro<BR>Telefone: $fone<BR>Cidade: $cidade - $estado</td>";
		echo "</tr>";       
	}
echo "</table>";
}
echo "</td>";
echo "</tr>";
?>
</table>