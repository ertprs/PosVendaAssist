<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> Vista Explodida </TITLE>
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">

<style>

.fonttabela{
	font-family: verdana;
	font-size: 9;
}
.fonttitulo{
	font-family: Arial;
	font-size: 13;
	font-weight: bold;
}
</style>

</HEAD>

<BODY>

<?
$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
?>

<table border='1'cellspacing='0' cellpadding='0' bordercolor='#000000' width='750'>
	<tr>
		
		<td width='220'><!-- Linha1 Coluna1 -->
		<?
		echo "<img src='../logos/" . pg_result ($res,0,logo) . "'>";
		?>
		</td>
		
		<td width='430'align='center' class='fonttitulo'><!-- Linha1 Coluna2 -->
		<?
		$produto = $_GET ['produto'];
		$sql = "SELECT tbl_produto.referencia, 
						tbl_produto.descricao, 
						tbl_produto.produto, 
						tbl_produto.nome_comercial 
				FROM	tbl_produto 
				JOIN	tbl_linha USING (linha) 
				WHERE	tbl_produto.produto = $produto 
				AND		tbl_linha.fabrica = $login_fabrica" ;
		$res = pg_exec ($con,$sql);
		echo pg_result ($res,0,referencia);
		echo " - " ;
		echo pg_result ($res,0,descricao);
		?>
		</td>

		<td width='100' align='center' class='fonttitulo'><!-- Linha1 Coluna2 -->
		<?
			$data = date("d/m/Y");
			echo $data;
		?>
		</td>

	</tr>
	
	<tr>
		<td colspan='3' align='center'><br><br>
			<?
			echo "<img src='../vistas/" . pg_result ($res,0,produto) . ".gif'>";
			?>
			<br><br>
<?

//				tbl_peca.codigo         ,

$sql = "SELECT	tbl_lista_basica.peca   ,
				lpad(tbl_lista_basica.ordem,2,0) AS ordem  ,
				tbl_lista_basica.qtde   ,
				tbl_peca.referencia     ,
				tbl_peca.descricao      ,
				tbl_peca.origem      
		FROM	tbl_lista_basica
		JOIN	tbl_peca USING (peca) 
		WHERE	tbl_lista_basica.produto = $produto 
		AND		tbl_lista_basica.fabrica = $login_fabrica
		ORDER BY tbl_lista_basica.ordem" ;
$res = pg_exec ($con,$sql);

for ($i=0; $i < pg_numrows($res); $i++){
	$td[$i] =  "							<tr>\n";
	$td[$i] .= "								<td>\n";
	$td[$i] .= "									<table width='100%'>\n";
	$td[$i] .= "										<tr>\n";
	$td[$i] .= "											<td WIDTH='10%' class='fonttabela'>";
	$td[$i] .=												pg_result($res,$i,ordem);
	if (trim(pg_result($res,$i,origem)) == "IMP") $td[$i] .= " *";
	$td[$i] .= "											</td>\n";
	$td[$i] .= "											<td WIDTH='20%' class='fonttabela'>".pg_result($res,$i,referencia)."</td>\n";
	$td[$i] .= "											<td WIDTH='60%' class='fonttabela'>".pg_result($res,$i,descricao)."</td>\n";
	$td[$i] .= "										</tr>\n";
	$td[$i] .= "									</table>\n";
	$td[$i] .= "								</td>\n";
	$td[$i] .= "							</tr>\n";
}

?>


				<table width='100%' BORDER='0' CELLSPACING='0' cellpadding='5'>
					<tr>
						<td WIDTH='50%' valign='top'><!-- COLUNA1 -->
						
						<table border='1' bordercolor='#000000' cellspacing='0' cellpadding='0' WIDTH='100%'>

							<tr>
								<td>
									<table width='100%'>
										<tr>
											<td WIDTH='10%' class='fonttabela'>REF.</td>
											<td WIDTH='20%' class='fonttabela'>CÓDIGO</td>
											<td WIDTH='60%' class='fonttabela'>DESCRIÇÃO</td>
										</tr>
									</table>
								</td>
							</tr>

<?
$ultimo = intval ((pg_numrows($res) + 1) / 2) ;

for ($i=0; $i < $ultimo ; $i++){
	echo $td[$i];
}
?>
						</table>
						</td>

						<td WIDTH='50%' valign='top'><!-- COLUNA 2 -->
						<table border='1' bordercolor='#000000' cellspacing='0' cellpadding='0' WIDTH='100%'>

							<tr>
								<td>
									<table width='100%' border='0'>
										<tr>
											<td WIDTH='10%' class='fonttabela'>REF.</td>
											<td WIDTH='20%' class='fonttabela'>CÓDIGO</td>
											<td WIDTH='60%' class='fonttabela'>DESCRIÇÃO</td>
										</tr>
									</table>
								</td>
							</tr>

<?
for ($i=$ultimo; $i < pg_numrows($res); $i++){
	echo $td[$i];
}
?>

						</table>

						</td>
					</tr>
				</table>
	</tr>
</table>
</BODY>
</HTML>
