<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
if ( isset($_GET['os']) ) {
	if (is_array($_GET['os'])){
		$os = implode(",", $_GET['os']);
	} else {
		$os = $_GET['os'];
	}

	$sqlInformacoes = "SELECT revenda_nome, serie, referencia, os,TO_CHAR(data_abertura,'DD/MM/YYYY') AS data, sua_os,  codigo_barra FROM tbl_os JOIN tbl_produto USING (produto) WHERE os in ({$os}) AND fabrica = $login_fabrica ";
	$resInformacoes = pg_query($con, $sqlInformacoes);
}

if ( isset($_GET['imprimir']) ) {
	$seque = '000000001';
	header("Content-Type: application/text");
	header("Content-Disposition: attachment;Filename=entradarma.txt");

	//$arquivo = "Número da Ordem de Serviço; Número de Série; Código EAN \n";

	foreach (pg_fetch_all($resInformacoes) as $informacoes) {
		
		if (isset($informacoes['codigo_barra']) == false || $informacoes['codigo_barra'] == '') {
			$informacoes['codigo_barra'] = '0000000000000';
		}		
		if ($informacoes['serie'] == '') {
			$informacoes['serie'] = 0;
		}
		$os = explode("-", $informacoes['sua_os']);
		$arquivo .= "0{$seque};{$informacoes['revenda_nome']};{$informacoes['serie']};{$informacoes['referencia']};{$seque};{$os[0]};{$informacoes['data']};{$informacoes['codigo_barra']}\n";
	}
	echo $arquivo;
	exit;
}

if ( isset($_GET['sua_os']) ) {
	$sua_os = explode('-', $_GET['sua_os'])[0];
	$sqlLista = "SELECT os, sua_os FROM tbl_os WHERE sua_os like '{$sua_os}%' AND fabrica = $login_fabrica  ";
	$resLista = pg_query($con, $sqlLista);
}?>
<style type="text/css">
	tr {
		text-align-last: center;
	}
</style>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

<center>
	<form>
		<h3>Lista de OS</h3>
		<input type='checkbox' name='todas_os'/>Selecionar Todas OS
		<table class="table table-hover" style="margin-left: 10%;margin-right: 10%; width: 80%">
			<thead class="bg-primary">
				<tr>
					<th scope="col">Imprimir</th>
					<th scope="col">OS</th>
				</tr>
			</thead>
		<?php
		foreach (pg_fetch_all($resLista) as $lista) {
			echo "<tr>";
			echo "<td><input type='checkbox' name='os[]' value='{$lista['os']}' /></td>";
			echo "<td>{$lista['sua_os']}</td>";
			echo "</tr>";
		} ?>
		</table>
		<input type="hidden" name="imprimir" value="true">
		<button type="submit" class="btn btn-primary">Imprimir</button>
	</form>
</center>
<script type="text/javascript">
	$('[name=todas_os]').on('click', function () {
		select = this.checked;
		if (select) {
			$('[type=checkbox]').attr('checked', 'true');
		} else {
			$('[type=checkbox]').removeAttr('checked');
		}
	});
</script>
