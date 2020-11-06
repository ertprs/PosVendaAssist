<?php 
/**
 * Tela com todos os chamados de um consumiror
 * HD 59746
 *
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

if ( ! isset($_GET['cpf']) || empty($_GET['cpf']) ) {
	// Caso CPF não seja informado, fechar a janela após alguns segundos ....
	?>
	<div align="center">
		<h1> CPF não informado !!! </h1>
	</div>
	<script type="text/javascript" language="javascript">
		setTimeout('window.close()',3500);
	</script>
	<?php
	exit;
}

// buscar chamados com o CPF do cliente ...
$sql = "SELECT hd.hd_chamado, hd.titulo, hd.status, to_char(hd.data,'dd/mm/yyyy') as data,
			   e.cpf, e.nome, e.rg, e.email, e.os,
			   c.nome as cidade, c.estado,
			   p.descricao as produto
		FROM tbl_hd_chamado hd
		INNER JOIN tbl_hd_chamado_extra e USING (hd_chamado)
		LEFT JOIN tbl_posto_fabrica pf ON (e.posto = pf.posto AND pf.posto = %s)
		LEFT JOIN tbl_cidade c USING (cidade)
		LEFT JOIN tbl_produto p USING (produto)
		WHERE e.cpf = '%s'
		AND hd.fabrica_responsavel = %s
		ORDER BY hd.data ASC";
$sql = sprintf($sql,pg_escape_string($login_fabrica),pg_escape_string($_GET['cpf']),pg_escape_string($login_fabrica));
$res = pg_exec($con,$sql);
$rows= pg_numrows($res);
?>
<style type="text/css" media="all">
	table {
		width: 100%;
		margin: 0 auto;
		font-family: Verdana;
		font-size: 11px;
	}
	table td {
		border-bottom: 1px solid #485989;
	}
	table thead td {
		color: white;
		background-color: #3E83C9;
		font-family: Verdana;
		font-size: 11px;
		font-weight: bold;
	}
	table tbody tr.impar td {
		background-color: #3E83C9;
	}
	table tbody tr.par td {
		background-color: #FFFFFF;
	}
</style>

<h4> Lista de Chamados Do Consumidor</h4>

<table>
	<thead>
		<tr>
			<td colspan="5" align="center"> CPF/CNPJ: <?php echo $_GET['cpf']; ?> </td>
		</tr>
		<tr>
			<td> Chamado </td>
			<td> OS </td>
			<td> Data </td>
			<td> Produto </td>
			<td> Status </td>
		</tr>
	</thead>
	<tbody>
		<?php if ( $rows > 0 ): ?>
			<?php $inc = 0; ?>
			<?php while ($row = pg_fetch_assoc($res)): ?>
				<tr class="<?php echo ($inc%2)?'impar':'par'; ?>">
					<td> <a href="callcenter_interativo_new.php?callcenter=<?php echo $row['hd_chamado']; ?>" target="_blank"><?php echo $row['hd_chamado']; ?></a> </td>
					<td> <?php echo (empty($row['os'])) ? '&nbsp;' : $row['os']; ?> </td>
					<td> <?php echo (empty($row['data'])) ? '&nbsp;' : $row['data']; ?> </td>
					<td> <?php echo (empty($row['produto'])) ? '&nbsp;' : $row['produto']; ?> </td>
					<td> <?php echo (empty($row['status'])) ? '&nbsp;' : $row['status']; ?> </td>
				</tr>
				<?php $inc++; ?>
			<?php endwhile; ?>
		<?php else: ?>
			<tr>
				<td colspan="5" align="center"> Nenhum chamado encontrado ... </td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>
