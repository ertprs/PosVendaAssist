<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

//if ($_GET ['listar'] == 'todos') {
	$sql = "SELECT	tbl_posto.endereco, 
					tbl_posto.numero  , 
					tbl_posto.cep     , 
					tbl_posto.cidade  , 
					tbl_posto.estado  , 
					tbl_posto.bairro  , 
					tbl_posto.nome    , 
					tbl_posto_fabrica.codigo_posto 
			FROM	tbl_posto 
			JOIN	tbl_posto_fabrica USING (posto) 
			WHERE	tbl_posto_fabrica.fabrica = 3
			and		tbl_posto.estado ilike 'sp'
			ORDER BY tbl_posto.cidade";
	$res = pg_exec ($con,$sql);

	echo "<table width='100%' align='center' border='1'>";

	echo "<tr class='top_list'>";

	echo "<td align='center'>";
	echo "<b>Posto (Código - Nome)</b>";
	echo "</td>";

	echo "<td align='center'>";
	echo "<b>Endereco</b>";
	echo "</td>";

	echo "<td align='center'>";
	echo "<b>Cep</b>";
	echo "</td>";

	echo "<td align='center'>";
	echo "<b>Bairro</b>";
	echo "</td>";

	echo "<td align='center'>";
	echo "<b>Cidade</b>";
	echo "</td>";

	echo "<td align='center'>";
	echo "<b>Estado</b>";
	echo "</td>";

	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		echo "<tr class='line_list'>";
		
		echo "<td align='left'>".pg_result ($res,$i,codigo_posto)." - ".pg_result ($res,$i,nome)."</td>";
		echo "<td align='left'>".pg_result ($res,$i,endereco).", ".pg_result ($res,$i,numero)."</td>";
		echo "<td align='left'>".pg_result ($res,$i,cep)."</td>";
		echo "<td align='left'>".pg_result ($res,$i,bairro)."</td>";
		echo "<td align='left'>".pg_result ($res,$i,cidade)."</td>";
		echo "<td align='left'>".pg_result ($res,$i,estado)."</td>";
		
		echo "</tr>";
	}
	echo "</table>";
//}
?>
