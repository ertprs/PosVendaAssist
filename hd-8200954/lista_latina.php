<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$buscar = $_POST['btn_buscar'];
$busca_estado = $_POST['estado'];
$busca_cidade = $_POST['cidade'];

echo "<form name='frm_busca' action='$PHP_SELF' method='post' >";
echo "<table width='500' border='0' align='center' cellpadding='2' cellspacing='4' style='font-family: verdana; font-size: 11px'>";
echo "<tr>";
	echo "<td width='250' align='right'>Selecione o Estado: &nbsp;&nbsp;</td>";

	echo "<td width='250' align='left'>&nbsp;&nbsp;";
	echo " <select name='estado' size='1'>";
	echo " <option value=''></option>";
	echo " <option value='AC'"; if($busca_estado=='AC')echo "SELECTED"; echo ">AC</option>";
	echo " <option value='AL'"; if($busca_estado=='AL')echo "SELECTED"; echo ">AL</option>";
	echo " <option value='AM'"; if($busca_estado=='AM')echo "SELECTED"; echo ">AM</option>";
	echo " <option value='AP'"; if($busca_estado=='AP')echo "SELECTED"; echo ">AP</option>";
	echo " <option value='BA'"; if($busca_estado=='BA')echo "SELECTED"; echo ">BA</option>";
	echo " <option value='CE'"; if($busca_estado=='CE')echo "SELECTED"; echo ">CE</option>";
	echo " <option value='DF'"; if($busca_estado=='DF')echo "SELECTED"; echo ">DF</option>";
	echo " <option value='ES'"; if($busca_estado=='ES')echo "SELECTED"; echo ">ES</option>";
	echo " <option value='GO'"; if($busca_estado=='GO')echo "SELECTED"; echo ">GO</option>";
	echo " <option value='MA'"; if($busca_estado=='MA')echo "SELECTED"; echo ">MA</option>";
	echo " <option value='MG'"; if($busca_estado=='MG')echo "SELECTED"; echo ">MG</option>";
	echo " <option value='MS'"; if($busca_estado=='MS')echo "SELECTED"; echo ">MS</option>";
	echo " <option value='MT'"; if($busca_estado=='MT')echo "SELECTED"; echo ">MT</option>";
	echo " <option value='PA'"; if($busca_estado=='PA')echo "SELECTED"; echo ">PA</option>";
	echo " <option value='PB'"; if($busca_estado=='PB')echo "SELECTED"; echo ">PB</option>";
	echo " <option value='PE'"; if($busca_estado=='PE')echo "SELECTED"; echo ">PE</option>";
	echo " <option value='PI'"; if($busca_estado=='PI')echo "SELECTED"; echo ">PI</option>";
	echo " <option value='PR'"; if($busca_estado=='PR')echo "SELECTED"; echo ">PR</option>";
	echo " <option value='RJ'"; if($busca_estado=='RJ')echo "SELECTED"; echo ">RJ</option>";
	echo " <option value='RN'"; if($busca_estado=='RN')echo "SELECTED"; echo ">RN</option>";
	echo " <option value='RO'"; if($busca_estado=='RO')echo "SELECTED"; echo ">RO</option>";
	echo " <option value='RR'"; if($busca_estado=='PR')echo "SELECTED"; echo ">RR</option>";
	echo " <option value='RS'"; if($busca_estado=='RS')echo "SELECTED"; echo ">RS</option>";
	echo " <option value='SC'"; if($busca_estado=='SC')echo "SELECTED"; echo ">SC</option>";
	echo " <option value='SE'"; if($busca_estado=='SE')echo "SELECTED"; echo ">SE</option>";
	echo " <option value='SP'"; if($busca_estado=='SP')echo "SELECTED"; echo ">SP</option>";
	echo " <option value='TO'"; if($busca_estado=='TO')echo "SELECTED"; echo ">TO</option>";
	echo "</select>";  
	echo "</td>";    
echo "</tr>"; 
        
if(strlen($busca_estado)>0){
	echo "<tr>";
		echo "<td width='250' align='right'>Escolha a cidade: &nbsp;&nbsp;</td>";
		
		echo "<td width='250' align='left'>&nbsp;&nbsp;";        
		echo "<select name='cidade' size='1'>";
		echo "<option value=''></option>";
		
		$sql = "SELECT DISTINCT            
					tbl_posto.cidade                
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
				JOIN   tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica 
				WHERE  tbl_posto_fabrica.fabrica = '15'
				AND tbl_posto.estado ILIKE '%$estado%'
				ORDER BY tbl_posto.cidade";
		$res = pg_exec ($con,$sql);
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cidade         = trim(pg_result($res,$i,cidade));
			echo "<option value='$cidade'"; if($busca_cidade==$cidade)echo "SELECTED"; echo ">$cidade</option>";
		}
		echo "</select>";
		echo "</td>";
	echo "</tr>";
}
echo "<tr>";
	echo "<td colspan='2' align='center'><INPUT TYPE='submit' name='btn_buscar' value='Buscar'></td>";
echo "</tr>";
echo "</table>";
echo "</form>";
        
if((strlen($buscar)>0) AND (strlen($busca_estado)>0) AND (strlen($busca_cidade)>0)){
echo "<table width='400' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#d4d4d4'>";

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
			WHERE   tbl_posto_fabrica.fabrica = '15'
			AND tbl_posto.estado ILIKE '%$busca_estado%'
			AND tbl_posto.cidade ILIKE '%$busca_cidade%'
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
        
?>