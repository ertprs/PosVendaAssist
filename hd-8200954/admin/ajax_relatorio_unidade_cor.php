<?

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	$unidade_cor   = $_POST['unidade_cor'];
	if ($unidade_cor == 'amarelo')
	{
		$titulo    = 'Amarelas';
		$cor       = '#000';
		$cor_fundo = '#FFAE00';
	}
	else
	{
		$titulo    = 'Pretas';
		$cor       = '#FFFFFF';
		$cor_fundo = '#1E1E1E';
	}
	$xdata_inicial  = $_POST['data_inicial'];
	$xdata_final    = $_POST['data_final'];
	$login_fabrica = $_POST['fabrica'];
	
	$sql = "SELECT
			tbl_os.os,
			tbl_produto.descricao,
			tbl_posto.nome,
			tbl_os.defeito_reclamado_descricao,
			tbl_defeito_constatado.descricao AS defeito_c_descricao,
			tbl_os_campo_extra.cor_produto
			FROM tbl_os
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
			JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
			JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
			AND tbl_os_campo_extra.cor_produto = '$unidade_cor'";
	$res = pg_query($con,$sql);

	echo "<table border='0' class='tabela' width='700' style='background-color: #D9E2EF;'>";
		echo "<tr class='titulo_tabela' style='background-color: $cor_fundo; color: $cor;'>";
			echo "<th colspan='5'>";
				echo "Mostrando Reulstados das Unidades ".$titulo;
			echo "</th>";
		echo "</tr>";
		echo "<tr class='subtitulo'>";
			echo "<th>";
				echo "OS";
			echo "</th>";
			echo "<th>";
				echo "Descrição";
			echo "</th>";
			echo "<th>";
				echo "Nome";
			echo "</th>";
			echo "<th nowrap>";
				echo "Defeito Reclamado";
			echo "</th>";
			echo "<th nowrap>";
				echo "Defeito Constatado";
			echo "</th>";
		echo"</tr>";

	for ($i = 0; $i < pg_num_rows($res); $i++)
	{
		$os = pg_result($res,$i,'os');
		$descricao= pg_result($res,$i,'descricao');
		$nome = pg_result($res,$i,'nome');
		$defeito_reclamado_descricao = pg_result($res,$i,'defeito_reclamado_descricao');
		$defeito_c_descricao = pg_result($res,$i,'defeito_c_descricao');
		$cor_produto = pg_result($res,$i,'cor_produto');

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

		echo "<tr bgcolor='$cor'>";
			echo "<td>";
				echo "<a href='os_press?os=$os' target='_blank'>" . $os . "</a>";
			echo "</td>";
			echo "<td nowrap>";
				echo $descricao;
			echo "</td>";
			echo "<td nowrap>";
				echo $nome;
			echo "</td>";
			echo "<td nowrap>";
				echo $defeito_reclamado_descricao;
			echo "</td>";
			echo "<td>";
				echo $defeito_c_descricao;
			echo "</td>";
		echo"</tr>";
	}

	echo "</table>";

?>