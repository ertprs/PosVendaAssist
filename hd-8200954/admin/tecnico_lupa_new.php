<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

$parametro = $_REQUEST["parametro"];
if ($_REQUEST["valor"]) {
	$valor = $_REQUEST["valor"];
}

$tipotecnico = $_REQUEST["tipotecnico"];
?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>

		<script>
			$(function () {
				$.dataTableLupa();
			});
		</script>
	</head>
	
	<body>
		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="imagens/logo_new_telecontrol.png">
				<img class="lupa_img pull-right" src="imagens/lupa_new.png">
			</div>
			<br /><hr />
			<div class="row-fluid">
			<form action="<?php echo $_SERVER['PHP_SELF']?>" method='POST' >
			<input type="hidden" name="tipotecnico"  value='<?php echo $tipotecnico?>' />
				<div class="span1"></div>
				<div class="span4">
					<input type="hidden" name="completo" class="span12" value='<?php echo $completo?>' />
					<select name="parametro" >
						<option value="cpf" <?php echo ($parametro == "cpf") ? "SELECTED" : ""?> >CPF</option>
						<option value="nome" <?php echo ($parametro == "nome") ? "SELECTED" : ""?> >Nome</option>
					</select>
				</div>
				<div class="span4">
					<input type="text" name="valor" class="span12" value="<?php echo $valor?>" />
				</div>
				<div class="span2">
					<button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();">Pesquisar</button>
				</div>
			</form>
			</div>
			<?php
			switch ($parametro) {
				case 'cpf':
					$whereAdc = " tbl_tecnico.cpf ILIKE '%{$valor}%'";
				break;

				case 'nome':
					$whereAdc = " tbl_tecnico.nome ILIKE '%{$valor}%' ";
				break;
			}
			if (!empty($tipotecnico)) {
				$whereAdc .= " AND tbl_tecnico.tipo_tecnico = '{$tipotecnico}' 
							   AND tbl_tecnico.ativo = 't' ";
			}

			$sql = "SELECT cpf, nome
							FROM tbl_tecnico
							WHERE tbl_tecnico.fabrica = {$login_fabrica}
							AND {$whereAdc}			
							ORDER BY tbl_tecnico.nome";
					
					$res = pg_query($con, $sql);
					
					$rows = pg_num_rows($res);
					if ($rows > 0) {
					?>
						<div id="border_table">             
						<table class="table table-striped table-bordered table-hover table-lupa" >
							<thead>
								<tr class='titulo_coluna'>
									<th>CPF</th>
									<th>Nome</th>
								</tr>
							</thead>
							<tbody>
								<?php
								for ($i = 0 ; $i < $rows; $i++) {
									$nome      = pg_fetch_result($res, $i, "nome");
									$nome_utf8      = toUtf8(pg_fetch_result($res, $i, "nome"));
									$cpf      = pg_fetch_result($res, $i, "cpf");
									$r = array("nome"=>$nome_utf8, "cpf" => $cpf)	;
									echo "<tr onclick='window.parent.retorna_tecnico(".json_encode($r)."); window.parent.Shadowbox.close();' >";
										echo "<td class='cursor_lupa'>{$cpf}</td>";
										echo "<td class='cursor_lupa'>{$nome}</td>";
									echo "</tr>";
								}
								?>
							</tbody>
						</table>
						</div>
					<?php
					} else {
						echo '
						<div class="alert alert_shadobox">
							<h4>Nenhum resultado encontrado</h4>
						</div>';
					}
				
					?>
	</div>
	</body>
</html>
