<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
?>
<style type="text/css">
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>

<?php
	$hd_chamado = $_REQUEST['hd_chamado'];

	$sql = "SELECT  TO_CHAR(tbl_hd_chamado_item.data,'DD/MM/YYYY') AS data,
					tbl_hd_chamado_item.comentario,
					tbl_hd_chamado_item.status_item,
					tbl_hd_chamado_item.interno
					FROM tbl_hd_chamado_item 
					WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$dados = pg_fetch_all($res);
		
		echo "<table width='700' align='center' class='tabela'>";
		echo "<caption class='titulo_tabela'>Em Acompanhamento</caption>";
		echo "<tr class='titulo_coluna'><td>Data Interação</td><td>Interação</td></tr>";
		$i = 0;
		foreach($dados AS $linha){ 
			if($linha['status_item'] == "Em Acomp."){	
				if ($linha["interno"] == "t") {
					continue;
				}

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		?>
				<tr bgcolor="<?php echo $cor; ?>">
					<td align="center"><?php echo $linha['data']?></td>
					<td><?php echo $linha['comentario']?></td>
				</tr>
	<?		}
			$i++;
		}
		echo "</table> <br><br>";

		echo "<table width='700' align='center' class='tabela'>";
		echo "<caption class='titulo_tabela'>Resposta Conclusiva</caption>";
		echo "<tr class='titulo_coluna'><td>Data Interação</td><td>Interação</td></tr>";
		$i = 0;
		foreach($dados AS $linha){ 
			if ($linha["interno"] == "t") {
				continue;
			}

			if($linha['status_item'] == "Resp.Conclusiva"){
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		?>
				<tr bgcolor="<?php echo $cor; ?>">
					<td align="center"><?php echo $linha['data']?></td>
					<td><?php echo $linha['comentario']?></td>
				</tr>
	<?		}
			$i++;
		}
		echo "</table> <br><br>";
	} else {
		echo "<center>Nenhum histórico para o chamado $hd_chamado</center>";
	}
