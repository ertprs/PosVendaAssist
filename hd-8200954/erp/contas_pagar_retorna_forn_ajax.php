<?
header("Content-Type: text/html; charset=ISO-8859-1",true);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
//include 'autentica_admin.php';
include 'autentica_usuario_empresa.php';


$digito = $_GET["typing"];

$sql= "SELECT count(tbl_pessoa.pessoa) as cont
		FROM tbl_pessoa
		JOIN tbl_pessoa_fornecedor ON tbl_pessoa_fornecedor.pessoa = tbl_pessoa.pessoa
		WHERE tbl_pessoa.empresa=$login_empresa
		AND tbl_pessoa.nome ilike '%$digito%'";
//echo "sql: $sql";
$res = pg_exec ($con,$sql);
$cont	= trim(pg_result($res,0,cont));	

$resultado = array();

if($cont>0) {
	if($cont > 20){
		echo "<div style=' filter: alpha(opacity=90); opacity: .90;' onselect=\"this.text.value = '$nome';$('fornID').value = '$posto'\">";
		echo "<table width='380' >";
		echo "<tr>";
		echo "<td nowrap style='font-size:10px'>$cont reg. encontrados!Especifique mais..";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</div>";
	}else{

		$sql= "SELECT tbl_pessoa.pessoa, 
					nome,
					cidade 	
				FROM tbl_pessoa
				JOIN tbl_pessoa_fornecedor ON tbl_pessoa_fornecedor.pessoa = tbl_pessoa.pessoa
				WHERE tbl_pessoa.empresa=$login_empresa
				AND tbl_pessoa.nome ilike '%$digito%'
				ORDER BY nome ;";
		//echo "sql: $sql";
		$res = pg_exec ($con,$sql);
		
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$posto	= trim(pg_result($res,$i,pessoa));	
			$nome	= trim(pg_result($res,$i,nome));	
			$cidade	= trim(pg_result($res,$i,cidade));	

			if(strlen($nome) > 31){
				$nome_red= substr($nome, 0,30) . "...";
			}else{
				$nome_red= $nome;
			}

			if(strlen($cidade)>22)
				$cidade	= substr($cidade, 0,20) . "...";
			else
				$cidade	= $cidade;


			echo "<div style='border-width: 1px; border-style: solid; border-color: #cccccc; filter: alpha(opacity=90); opacity: .90;' onselect=\"this.text.value = '$nome';$('fornID').value = '$posto';\">";
			//echo "<span width='100' class='informal' style='font-size:10px'></span>";
			echo "<table width='375' >";
			echo "<tr>";
			echo "<td nowrap width='245' style='font-size:9px'>".strtoupper($nome_red);
			echo "</td>";
			echo "<td nowrap width='140' align='right' style='font-size:9px'>".strtoupper($cidade);
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "</div>";
		}
	}
}else{
/*	
	echo "<td nowrap style='font-size:10px'>Nada encontrado com: <b>$digito</b>";
	echo "<script language='javascript'>alerta();</script>\n";
	echo "<input type='hidden' id='forn_nao_cad' value='1'>";
	echo "</td>";
*/	

	echo "<div style=' filter: alpha(opacity=90); opacity: .90;' onselect=\"this.text.value = '$nome';$('fornID').value = '$posto'\">";
	echo "<table width='380' >";
	echo "<tr>";
	echo "<td nowrap style='font-size:10px'>Nada encontrado com: <b>$digito</b>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";


	echo "<div style=' filter: alpha(opacity=90); opacity: .90;' onselect=\"exibeFornec(1);\">";
	echo "<table width='380' >";
	echo "<tr>";
	echo "<td nowrap style='font-size:10px'>";
	echo "<b><a href='#TB_inline?height=330&width=700&inlineId=f1' class='thickbox'>Cadastrar Fornecedor</a></b>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";

//	$resultado[] = "Nenhum fornecedor encontrato";
}

?>

