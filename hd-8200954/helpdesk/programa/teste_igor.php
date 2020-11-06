<? 
//tbl_help2
//programa text
//help text

	//#######"insert into tbl_help2 (programa, help) values('testeigor', 'help')"
	//$sql= "insert into tbl_help2 (programa, help) values('testeigor', 'help')" ;
	//$sql= "update tbl_help2 programa='$updateprog' help='$updatehelp' where programa='$prog';
	//	update caracteristicabem set caracteristicacbemvc='igor teste' where caracteristicacbemvc='teste' 
	//	delete from caracteristicabem where caracteristicacbemvc='igor teste' 

	include '../dbconfig.php';
	include '../includes/dbconnect-inc.php';
	
	
	if($_POST['btn']=="Excluir"){
		$sqlexcluir= "delete from tbl_help2 where programa='". $_POST['excluir']."'";
		$res = pg_exec ($con,$sqlexcluir);
		echo "<br></br>passou em excluir";
		$botao ="Gravar"; 
	}else{
		if($_GET["btn"]=="Alterar"){
			// Alterar  
			$botao= "Alterar";
			$conteudo_alterar="<input type='hidden' name='txtprog_antigo' value='".$_GET['alt_prog']."'>";
			echo "nao esta vazio";
		}else{
			$conteudo_alterar="";
			//Cadastrar
			$botao= "Gravar";   
			if($_POST['Grava']=="Gravar"){
				if($_POST['txtprog']==""){
					echo "Vazio, nao cadastra!";
				}else{
					$insere = "insert into tbl_help2 (programa, help) values('" . 	$_POST['txtprog']."','".$_POST['txthelp']."')";
					$res = pg_exec ($con,$insere);
				}
			}else{
				if($_POST['txtprog_antigo']==""){
					echo "Nao faz nada aqui";
				}else{
					echo " <br></br> passou update".$_POST['txtprog_antigo'];
					//funcao para update
					$alterar = "update tbl_help2 set programa='" . $_POST['txtprog']."', help='".$_POST['txthelp']."' where programa='". $_POST['txtprog_antigo']."'";
					$res = pg_exec ($con,$alterar);
					$_GET['txprog']="";
					$_GET['txhelp']="";
					$_GET['txprog_antigo']="";
				}
			
			}
			
		}
	}
/*
	$insere = "insert into tbl_help2 (programa, help) values('IGOR','TESTE')";
	echo "insere: $insere";
	$res = pg_exec ($con,$insere);
*/	
	//$sql = "select programa, help from tbl_help2";
	
echo "<table border='1' bordercolor='000000' size='700'>
 	<tr>
 	<td>SISTEMA DE TESTE
	</td>
	</tr>
	<tr>
	<td>";

	echo "<table border='1' bordercolor='000000' size='700'>";
	echo "<form name='formbusca' method='POST' action='teste_igor.php'>";
	echo "<tr>
 		<td><a href='teste_igor.php' >Home</a></td>
		<td></td>
		<td></td>
		</tr>
		<tr>	
			<td>Busca:<input type='text' name='Busca' value=''></input> </td>
			<td>
				<SELECT NAME='busca'>
					<OPTION value='1' SELECTED>Programa
					<OPTION value='2' >Help
				</SELECT>
			</td>	
		</tr>";
	echo "<tr>
		<td>		<td><input type='submit' name='buscar' value='Buscar'></input></td></td>
		<td></td>
		<td></td>
	</tr>";
	echo "</form>";
	
	if($_POST['buscar']=="Buscar"){
		echo "busca:".$_POST['busca']; 
		if($_POST['Busca']==""){
			$sql = "select programa, help from tbl_help2";
			$res = pg_exec ($con,$sql);
		}else{
			if ($_POST['busca']='1')
				$var="programa";
			else
				$var="help";
				
			$sql = "select programa, help from tbl_help2 where $var like '%".$_POST['Busca']."%'";
			$res = pg_exec ($con,$sql);
		}
		$_POST['buscar']="";
	}else{
		$sql = "select programa, help from tbl_help2";
		$res = pg_exec ($con,$sql);
	}
	
	echo "<form name='formdados' method='POST' action='teste_igor.php'>";
	echo "<tr>
			<td valign='top'>Programa 
				<input type='text' name='txtprog' value='$_GET[alt_prog]'></input> 
				$conteudo_alterar
			</td>
			<td valign='top'>
				Help: <textarea name='txthelp' rows='4'cols='10'> $_GET[alt_help]</textarea>
			</td>
 			<td> 
				<input type='submit' name='Grava' value='$botao'></input>
			</td>
	      </tr>";
	echo "</form>";

	echo "<tr > 
			<td>Programa </td>
			<td>Help </td>
			<td>Ações </td>
             </tr>";
		
	//echo "<FORM> <INPUT TYPE='button' Value='Mensagem' onClick='alert('Teste de mensagem! MENSAGEM.')')></FORM>";
	     
	//IMPRIMIR OS VALORES DE HELP		
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$programa = pg_result($res,$i,programa);
		$help     = pg_result($res,$i,help);	
		
		echo "<form name='formexcluir' method='POST' action='teste_igor.php'>";
		echo "<tr> 
				<td>$programa</td>
				<td>$help </td>
				<td> 	
					<a href='teste_igor.php?alt_prog=$programa&alt_help=$help&bt=Alterar'>Alterar </a>
				</td>
				<td>
					<input type='hidden' name='excluir' value='$programa'></input> 
					<input type='submit' name='bt' value='Excluir'></input> 
				</td>
			</tr>";
		echo "</form>";
	}
	$_POST['txtprog']="";
	$_POST['txthelp']="";
	$_POST['txtprog_antigo']="";
echo "</table>";
echo "</td></tr></table>";
?>