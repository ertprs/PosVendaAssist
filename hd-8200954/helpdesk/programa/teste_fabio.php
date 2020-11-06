<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';



//programa 
//help     

//tbl_help2 

echo "<a href='teste_fabio.php'>Inserir</a> - ";
echo "<a href='teste_fabio.php'>Todos</a>";


if (isset($_GET['alterar'])){
	$programa_alt = $_GET['alterar'];
	$query ="Select * from tbl_help2 where programa = '".$programa_alt."'";
	$result = pg_exec ($con,$query);
	if (pg_numrows ($result)>0){
		$programa           = pg_result($result,0,programa);
		$help               = pg_result($result,0,help);
	}
}
if (isset($_POST['Excluir'])){
	$programa_alt = $_POST['programa'];
	$query ="DELETE FROM tbl_help2 WHERE programa = '".$programa_alt."'";
	$result = pg_exec ($con,$query);
	echo "<br>Programa '".$programa_alt."' excluido<br>";
}
if (isset($_POST['inserir'])){
	$programa_alt = $_POST['programa'];
	$help_alt = $_POST['help'];

	$query ="Select * from tbl_help2 where programa = '".$programa_alt."'";
	$result = pg_exec ($con,$query);
	if (pg_numrows ($result)==0){
		$query ="INSERT into tbl_help2 values('".$programa_alt."','".$help_alt."')";
		$result = pg_exec ($con,$query);
		echo "<br>Programa '".$programa_alt."' incluido<br>";
	}
	else echo "<br>Program já existente.";
	$programa="";
	$help="";
}
if (isset($_POST['alterar'])){
	$programa_alt    = $_POST['programa'];
	$programa_antigo = $_POST['programa_antigo'];
	$help_alt        = $_POST['help'];
	$query ="UPDATE tbl_help2  set help='".$help_alt."',programa = '".$programa_alt."' WHERE programa='".$programa_antigo."'";
	$result = pg_exec ($con,$query);
	echo "<br>Programa '".$programa_alt."' atualizado<br>";
	$programa="";
	$help="";
}

echo "<form name='formulario' method='post'>";
echo "<table border='1px' bgcolor='#cccccc' bordercolor='#000000'>";
echo "<tr>";
echo "<td>Programa</td>";
echo "<td><input type='text' name='programa' size='20' value=".$programa."><input type='hidden' name='programa_antigo' size='20' value=".$programa."></td>";
echo "</tr><tr>";
echo "<td>Help</td>";
echo "<td><textarea name='help' rows='5' >".$help."</textarea></td>";
echo "<tr><td><input type='submit' name='";


if (isset($_GET['alterar']))
	echo 'alterar'; 
else
	echo 'inserir';


echo "' value='Gravar'>";
echo "<td></tr>";
echo "</table>";
echo "</form>";



echo "Busca: <form name='busca' method='post' action='teste_fabio.php' >
		
		<input type='radio' name='campo' value='programa' checked>Programa<br>
		<input type='radio' name='campo' value='help'> Help<br>
		<input type='text' name='procura'>
		<input type='submit' name='procurar' value='Procurar'>
		</form><br><br>";

$consulta="";
if (isset($_POST['procurar'])){
	$campo = $_POST['campo'];
	$oque = $_POST['procura'];
	$consulta = 'WHERE '.$campo.' like \'%'.$oque.'%\'';
	if ($_POST['campo'] == ""){
		echo "Campo não selecionado!";
		$consulta="";
	}
	else if (trim($_POST['procura']) == ""){
			echo "Digita algo para buscar!";
			$consulta="";
		 }
		  else {
				echo '<br>Filtrando: '.$consulta.'<br>';
		  }

}

$sql = "Select * from tbl_help2 ".$consulta;
$res2 = pg_exec ($con,$sql);

echo "<br>Registros: ".@pg_numrows ($res)."<br>";
echo "<table border=1>";
echo "<tr>";
echo "<td width='150px'>Programa</td>";
echo "<td width='150px'>Help</td>";
echo "<td colspan=\"2\">Acao</td>";
echo "<tr>";
for ($i = 0 ; $i < @pg_numrows ($res2) ; $i++) {
	$programa           = pg_result($res2,$i,programa);
	$help           = pg_result($res2,$i,help);
	echo "<tr >";
	echo "<td> ".$programa."</td>";
	echo "<td>".$help."</td>";
	echo '<td><a href="?alterar='.$programa.'">Alterar</a>';
	echo '<td><form name="Excluir" method=\'post\' action="teste_fabio.php"><input type="hidden" name="programa" value="'.$programa.'"><input type="submit" name="Excluir" value="Excluir"></form></td>';
	echo "<tr>";
}
echo "</table>";
?>