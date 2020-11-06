<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'menu.php';
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'compra') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

//redirecionar com javascript
//<script language="JavaScript">
	//window.location= 'requisicao.php';
//</script>


$fornecedor = $_POST["fornecedor"];

if(strlen($_POST["enviar"])>0 and strlen($fornecedor)>0) {
	$linha	= $_POST["linha"];
	$familia= $_POST["familia"];
	$modelo	= $_POST["modelo"];
	$marca	= $_POST["marca"];


	//LINHA
	if(strlen($linha)>0) {
		$sql= " SELECT linha, nome
				FROM tbl_fornecedor_linha 
				JOIN tbl_linha  using(linha) 
				WHERE tbl_fornecedor_linha.empresa= $login_empresa
					and pessoa_fornecedor=$fornecedor
					and linha = $linha;";

		//echo "sql: $sql";
		$res= pg_exec($con, $sql);

		if(@pg_numrows($res)>0){
			echo "ja está cadastrado";
		}else{
			$sql= " INSERT INTO tbl_fornecedor_linha (empresa, pessoa_fornecedor, linha)
					VALUES($login_empresa, $fornecedor, $linha);";
					//echo "sql: $sql";
			$res= pg_exec($con, $sql);
		}
	}

	//FAMILIA
	if(strlen($familia)>0) {
		$sql= " SELECT familia, descricao
				FROM tbl_fornecedor_familia 
				JOIN tbl_familia  using(familia) 
				WHERE tbl_fornecedor_familia.empresa = $login_empresa
					and pessoa_fornecedor=$fornecedor
					and familia = $familia;";

		$res= pg_exec($con, $sql);

		if(@pg_numrows($res)>0){
			echo "ja está cadastrado";
		}else{
			$sql= " INSERT INTO tbl_fornecedor_familia (empresa, pessoa_fornecedor, familia)
					VALUES($login_empresa, $fornecedor, $familia);";
			$res= pg_exec($con, $sql);
		}
	}

	//MODELO
	if(strlen($modelo)>0) {
		$sql= " SELECT modelo, nome
				FROM tbl_fornecedor_modelo 
				JOIN tbl_modelo  using(modelo) 
				WHERE tbl_fornecedor_modelo.empresa = $login_empresa
					and pessoa_fornecedor=$fornecedor
					and modelo = $modelo;";

		$res= pg_exec($con, $sql);

		if(@pg_numrows($res)>0){
			echo "ja está cadastrado";
		}else{
			$sql= " INSERT INTO tbl_fornecedor_modelo (empresa, pessoa_fornecedor, modelo)
					VALUES($login_empresa, $fornecedor, $modelo);";
			$res= pg_exec($con, $sql);
		}
	}

	//MARCA
	if(strlen($marca)>0) {
		$sql= " SELECT marca, nome
				FROM tbl_fornecedor_marca 
				JOIN tbl_marca  using(marca) 
				WHERE tbl_fornecedor_marca.empresa = $login_empresa
					and pessoa_fornecedor=$fornecedor
					and marca = $marca;";

		$res= pg_exec($con, $sql);

		if(@pg_numrows($res)>0){
			echo "ja está cadastrado";
		}else{
			$sql= " INSERT INTO tbl_fornecedor_marca (empresa, pessoa_fornecedor, marca)
					VALUES($login_empresa, $fornecedor, $marca);";
			$res= pg_exec($con, $sql);
		}
	}
}

?>

<style type="text/css">
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}
</style>

<table class='table_line' width='700' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
<FORM ACTION='fornecedor_inf_compra.php' METHOD='POST'>
  <tr bgcolor='#596D9B'>
	<td nowrap colspan='5' class='menu_top' align='center'  background='imagens/azul.gif'><font size='3'>Tipo de produtos fornecidos pelo Fornecedor </font></td>
  </tr>
  <tr >
	<td colspan='5' nowrap align='center'>Selecione o Fornecedor</td>
  </tr>
  <tr bgcolor='#fcfcfc'>
	<td nowrap colspan='5' align='center'><b>Fornecedor:</b><br>
<?

$sql= " SELECT distinct
			tbl_pessoa.nome,
			tbl_pessoa.pessoa
		FROM tbl_pessoa
		JOIN tbl_pessoa_fornecedor on tbl_pessoa_fornecedor.pessoa = tbl_pessoa.pessoa
		WHERE tbl_pessoa_fornecedor.empresa = $login_empresa
		ORDER BY nome ";
//ECHO "sql: $sql<br>";
$res= pg_exec($con, $sql);

if(@pg_numrows($res)>0){

	echo "<select name='fornecedor'>";
	echo "<option value=''>Selecionar";
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$fornecedor_x= trim(pg_result($res,$i,pessoa));
		$nome_fornecedor= trim(pg_result($res,$i,nome));	
		$selected= "";
		if($fornecedor == $fornecedor_x)
			$selected= "selected";

		echo "<option value='$fornecedor_x' $selected>$nome_fornecedor";
	}
	echo "</select>";
}else{
	//echo "nenhum fornecedor encontrado-$sql";
}
?>
    </td>
  </tr>
<?


if(strlen($fornecedor)>0){
	
	
?>
  <tr bgcolor='#fafafa'>
	<td nowrap colspan='2' align='right'>
<?		
		if(strlen($_GET['mensagem'])>0)
			echo "<font size='3' color='#ff0000'>".$_GET['mensagem']."</font>";
?> 
	    <input type='hidden' name='requisicao' value='nova'>
	</td>
  </tr>
  <tr bgcolor='#fafafa'>
	<td nowrap width='150' align='center'>LINHA</td>
	<td nowrap width='150' align='center'>FAMÍLIA</td>
	<td nowrap width='150' align='center'>MODELO</td>
	<td nowrap width='150' align='center'>MARCA</td>
	<td nowrap align='center'>&nbsp;</td>
  </tr>
  <tr bgcolor='#fafafa'>
  <?
	#################### INFORMAÇÕES DE LINHA ##################
	echo "<td nowrap width='150' align='center'>";

	$sql= " SELECT * 
			FROM tbl_linha
			WHERE fabrica = $login_empresa
			ORDER BY nome ";

	$res= pg_exec($con, $sql);

	if(@pg_numrows($res)>0){

		echo "<select name='linha'>";
		echo "<option value=''>Selecionar";
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

			$linha= trim(pg_result($res,$i,linha));	
			$nome = trim(pg_result($res,$i,nome));	
			echo "<option value='$linha'>$nome";
		}
		echo "</select>";
	}else{
		//echo "nenhum fornecedor encontrado-$sql";
	}
	echo "</td>";

	################### INFORMAÇÕES DE FAMILIA ####################
	echo "<td nowrap width='150' align='center'>";

	$sql= " SELECT * 
			FROM tbl_familia
			WHERE fabrica = $login_empresa 
			ORDER BY descricao ";

	$res= pg_exec($con, $sql);

	if(@pg_numrows($res)>0){

		echo "<select name='familia'>";
		echo "<option value=''>Selecionar";
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

			$familia= trim(pg_result($res,$i,familia));	
			$descricao = trim(pg_result($res,$i,descricao));	
			echo "<option value='$familia'>$descricao";
		}
		echo "</select>";
	}else{
		//echo "nenhum fornecedor encontrado-$sql";
	}
	echo "</td>";

	#################### INFORMAÇÕES DE MODELO ##################
	echo "<td nowrap width='150' align='center'>";

	$sql= " SELECT * 
			FROM tbl_modelo
			WHERE fabrica = $login_empresa 
			ORDER BY nome ";

	$res= pg_exec($con, $sql);

	if(@pg_numrows($res)>0){

		echo "<select name='modelo'>";
		echo "<option value=''>Selecionar";
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

			$modelo= trim(pg_result($res,$i,modelo));	
			$nome = trim(pg_result($res,$i,nome));	
			echo "<option value='$modelo'>$nome";
		}
		echo "</select>";
	}else{
		//echo "nenhum fornecedor encontrado-$sql";
	}
	echo "</td>";

	#################### INFORMAÇÕES DE MARCA ##################
	echo "<td nowrap width='150' align='center'>";

	$sql= " SELECT * 
			FROM tbl_marca
			ORDER BY nome ";

	$res= pg_exec($con, $sql);

	if(@pg_numrows($res)>0){

		echo "<select name='marca'>";
		echo "<option value=''>Selecionar";
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

			$marca= trim(pg_result($res,$i,marca));	
			$nome = trim(pg_result($res,$i,nome));	
			echo "<option value='$marca'>$nome";
		}
		echo "</select>";
	}else{
		//echo "nenhum fornecedor encontrado-$sql";
	}
	echo "</td>";
?>
	<td nowrap colspan='1' align='center'>
		<input type='submit' name='enviar' value='ok'>
	</td>
  </tr>

  <tr bgcolor='#596D9B'' class ='menu_top'>
	<td nowrap colspan='5' align='center'>Dados dos Produtos Cadastrados para os Fornecedores</td>
  </tr>
  <tr bgcolor='#fafafa'>
  <?
	#################### INFORMAÇÕES DE LINHA ##################
	echo "<td nowrap width='150' align='center'>";

	$sql= " SELECT linha, nome
			FROM tbl_fornecedor_linha 
			JOIN tbl_linha  using(linha) 
			WHERE tbl_fornecedor_linha.empresa = $login_empresa
				and pessoa_fornecedor=$fornecedor;";
				//echo "sql: $sql";

	$res= pg_exec($con, $sql);

	if(@pg_numrows($res)>0){

		echo "<select size='4' name='fornecedor_linha'>";
 
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

			$linha= trim(pg_result($res,$i,linha));	
			$nome = trim(pg_result($res,$i,nome));	
			echo "<option value='$linha'>$nome";
		}
		echo "</select>";
	}else{
		//echo "nenhum fornecedor encontrado-$sql";
	}
	echo "</td>";

	################### INFORMAÇÕES DE FAMILIA ####################
	echo "<td nowrap width='150' align='center'>";

	$sql= " SELECT familia, descricao
			FROM tbl_fornecedor_familia
			JOIN tbl_familia  using(familia) 
			WHERE tbl_fornecedor_familia.empresa = $login_empresa
				and pessoa_fornecedor=$fornecedor;";

	$res= pg_exec($con, $sql);

	if(@pg_numrows($res)>0){

		echo "<select size='4' name='familia'>";
 
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

			$familia= trim(pg_result($res,$i,familia));	
			$descricao = trim(pg_result($res,$i,descricao));	
			echo "<option value='$familia'>$descricao";
		}
		echo "</select>";
	}else{
		//echo "nenhum fornecedor encontrado-$sql";
	}
	echo "</td>";

	#################### INFORMAÇÕES DE MODELO ##################
	echo "<td nowrap width='150' align='center'>";

	$sql= " SELECT modelo, nome
			FROM tbl_fornecedor_modelo 
			JOIN tbl_modelo  using(modelo) 
			WHERE tbl_fornecedor_modelo.empresa = $login_empresa
				and pessoa_fornecedor=$fornecedor;";

	$res= pg_exec($con, $sql);

	if(@pg_numrows($res)>0){

		echo "<select size='4' name='modelo'>";
 
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

			$modelo	= trim(pg_result($res,$i,modelo));	
			$nome	= trim(pg_result($res,$i,nome));	
			echo "<option value='$modelo'>$nome";
		}
		echo "</select>";
	}else{
		//echo "nenhum fornecedor encontrado-$sql";
	}
	echo "</td>";

	#################### INFORMAÇÕES DE MARCA ##################
	echo "<td nowrap width='150' align='center'>";

	$sql= " SELECT marca, nome
			FROM tbl_fornecedor_marca 
			JOIN tbl_marca  using(marca) 
			WHERE tbl_fornecedor_marca.empresa = $login_empresa
				and pessoa_fornecedor=$fornecedor;";

	$res= pg_exec($con, $sql);

	if(@pg_numrows($res)>0){

		echo "<select size='4' name='marca'>";
 
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

			$marca= trim(pg_result($res,$i,marca));	
			$nome = trim(pg_result($res,$i,nome));	
			echo "<option value='$marca'>$nome";
		}
		echo "</select>";
	}else{
		//echo "nenhum fornecedor encontrado-$sql";
	}
	echo "</td>";

?>
	<td nowrap align='right'> </td>
  </tr>

<?
}else{
	echo "  <tr bgcolor='#fafafa'>
			<td nowrap colspan='5' align='center'>	
				<FONT COLOR='BLUE'>SELECIONE O FORNECEDOR</FONT>
				<input type='submit' name='enviar' value='ok'>				
			</td>
		  </tr>";
}	

?>
  </form>
</table>

</body>
</html>
